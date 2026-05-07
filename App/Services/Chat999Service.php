<?php

namespace App\Services;

/**
 * 999.md Chat Integration via GraphQL API (v2.simpalsid.com)
 *
 * Polls unread conversations, sends AI replies, saves to CRM inbox.
 */
class Chat999Service
{
    private const GRAPHQL_URL = 'https://v2.simpalsid.com/graphql';
    private const LOCALE      = 'ro_RO';

    private \PDO   $db;
    private string $prefx;
    private string $account_key; // e.g. 'order_999md'
    private string $access_token;
    private string $refresh_token;

    public function __construct(\PDO $db, string $prefx, string $account_key)
    {
        $this->db          = $db;
        $this->prefx       = $prefx;
        $this->account_key = $account_key;

        // Load tokens from DB
        $this->access_token  = $this->getSetting($account_key . '_access_token');
        $this->refresh_token = $this->getSetting($account_key . '_session_key');
    }

    // ── Public interface ──────────────────────────────────────────────────────

    /**
     * Poll unread conversations and return array of new messages to process.
     */
    public function pollUnread(): array
    {
        $this->maybeRefreshToken();

        $contacts = $this->listContacts();
        $contacts_count = count($contacts);
        if (empty($contacts)) return [[], 0];

        $results = [];
        foreach ($contacts as $contact) {
            $contact_user_id = $contact['contact']['userId'] ?? '';
            $contact_login   = $contact['contact']['login']  ?? '';
            $channel_id      = $contact['channelId']         ?? '';

            if (!$contact_user_id) continue;

            // Skip if no unread messages AND last message is outgoing (already answered)
            $last = $contact['lastMessage'] ?? [];
            $unread = (int)($contact['unreadCounter'] ?? 0);
            if ($unread === 0 && ($last['direction'] ?? '') !== 'INCOMING') continue;

            $messages = $this->listMessages($contact_user_id);

            // Collect all INCOMING messages (to save in CRM)
            // and find which ones need AI reply (after last OUTGOING)
            $new_msgs     = [];
            $last_out_pos = -1;

            foreach ($messages as $i => $msg) {
                if (($msg['direction'] ?? '') === 'OUTGOING') {
                    $last_out_pos = $i;
                }
            }

            foreach ($messages as $i => $msg) {
                if (($msg['direction'] ?? '') !== 'INCOMING') continue;

                $mid  = $msg['msgId'] ?? '';
                $text = trim($msg['text'] ?? '');
                if (!$mid || !$text) continue; // skip empty/media-only messages

                $new_msgs[] = [
                    'mid'           => $mid,
                    'text'          => $text,
                    'needs_reply'   => ($i > $last_out_pos),
                    'sent_at'       => $msg['timestamp'] ?? '',
                    'advert_title'  => $msg['topic']['title'] ?? '',
                    'advert_url'    => $msg['topic']['url']   ?? '',
                ];
            }

            // Filter only msgs that need AI reply
            $unanswered = array_filter($new_msgs, fn($m) => $m['needs_reply']);

            if (empty($new_msgs)) continue;

            // all_mids/all_texts = ALL incoming (for saving to CRM)
            // unanswered = only msgs after last outgoing (for AI reply)
            $last_unans = !empty($unanswered) ? end($unanswered) : null;
            $combined_text = $last_unans
                ? implode("\n", array_column(array_values($unanswered), 'text'))
                : '';

            $last_msg = end($new_msgs);

            // topic e populat pe fiecare mesaj trimis dintr-o pagină de anunț 999.md.
            // Pentru sesiune folosim CEL MAI RECENT topic non-gol — dacă clientul scrie din
            // alt anunț, sursa sesiunii se actualizează la noul anunț.
            $advert_title = '';
            $advert_url   = '';
            foreach ($new_msgs as $nm) {
                if (!empty($nm['advert_url'])) {
                    $advert_title = $nm['advert_title'];
                    $advert_url   = $nm['advert_url'];
                }
            }

            $results[] = [
                'contact_user_id' => $contact_user_id,
                'contact_login'   => $contact_login,
                'channel_id'      => $channel_id,
                'msg_id'          => $last_unans ? $last_unans['mid'] : $last_msg['mid'],
                'text'            => $combined_text, // empty = no AI reply needed
                'sent_at'         => $last_msg['sent_at'],
                'advert_title'    => $advert_title,
                'advert_url'      => $advert_url,
                'all_mids'         => array_column($new_msgs, 'mid'),
                'all_texts'        => array_column($new_msgs, 'text'),
                'all_advert_titles'=> array_column($new_msgs, 'advert_title'),
                'all_advert_urls'  => array_column($new_msgs, 'advert_url'),
            ];
        }

        return [$results, $contacts_count];
    }

    /**
     * Send a message to a contact.
     */
    public function sendMessage(string $receiver_user_id, string $text): bool
    {
        $this->maybeRefreshToken();

        $query = 'mutation ChatAddMessage($input: Chat_AddMessageRequestInput!) {
  addMessage(input: $input) {
    msgId
    text
    direction
    __typename
  }
}';
        $variables = [
            'input' => [
                'receiverUserId' => $receiver_user_id,
                'text'           => $text,
                'files'          => [],
            ],
        ];

        $resp = $this->graphql($query, $variables, 'ChatAddMessage');
        $ok = isset($resp['data']['addMessage']['msgId']);

        // If failed, force token refresh and retry once
        if (!$ok && $this->refresh_token) {
            $this->log("sendMessage RETRY: first attempt failed, refreshing token");
            $this->refreshToken();
            $resp = $this->graphql($query, $variables, 'ChatAddMessage');
            $ok = isset($resp['data']['addMessage']['msgId']);
        }

        $this->log("sendMessage to=$receiver_user_id ok=" . ($ok ? 'YES' : 'NO') . " resp=" . json_encode($resp));
        return $ok;
    }

    // ── GraphQL queries ───────────────────────────────────────────────────────

    private function listContacts(): array
    {
        $query = 'query ChatContacts($input: Chat_ContactListRequestInput!) {
  listContacts(input: $input) {
    contacts {
      channelId
      id
      unreadCounter
      lastMessage { msgId text direction isReadByMe bid { id title url __typename } __typename }
      contact { login userId email avatar __typename }
      __typename
    }
    __typename
  }
}';
        $resp = $this->graphql($query, ['input' => ['limit' => 50, 'skip' => 0]], 'ChatContacts');
        return $resp['data']['listContacts']['contacts'] ?? [];
    }

    private function listMessages(string $contact_user_id): array
    {
        $query = 'query ChatMessages($input: Chat_MessageListRequestInput!) {
  listMessages(input: $input) {
    messages {
      msgId
      text
      direction
      isReadByMe
      isReadByContact
      contentType
      timestamp: sentAt
      topic { id type title url __typename }
      contact { login userId __typename }
      __typename
    }
    __typename
  }
}';
        $resp = $this->graphql($query, [
            'input' => ['limit' => 50, 'contactUserId' => $contact_user_id],
        ], 'ChatMessages');
        return $resp['data']['listMessages']['messages'] ?? [];
    }

    // ── Token management ──────────────────────────────────────────────────────

    private function maybeRefreshToken(): void
    {
        // If no access token, always refresh
        if (!$this->access_token) {
            $this->refreshToken();
            return;
        }

        // Check if access token is expired (JWT exp claim)
        $parts = explode('.', $this->access_token);
        if (count($parts) < 2) {
            $this->refreshToken();
            return;
        }

        $payload = json_decode(base64_decode(str_pad(strtr($parts[1], '-_', '+/'), strlen($parts[1]) % 4, '=', STR_PAD_RIGHT)), true);
        $exp = $payload['exp'] ?? 0;

        if ($exp > time() + 60) return; // still valid

        $this->refreshToken();
    }

    private function refreshToken(): void
    {
        if (!$this->refresh_token) return;

        $query = 'mutation RefreshAccessToken {
  refreshAccessToken(input: { refreshToken: "' . addslashes($this->refresh_token) . '" }) {
    accessToken
    refreshToken
  }
}';
        $resp = $this->graphql($query, [], 'RefreshAccessToken', true);
        $new_access  = $resp['data']['refreshAccessToken']['accessToken']  ?? '';
        $new_refresh = $resp['data']['refreshAccessToken']['refreshToken'] ?? '';

        if ($new_access) {
            $this->access_token = $new_access;
            $this->saveSetting($this->account_key . '_access_token', $new_access);
            if ($new_refresh) {
                $this->refresh_token = $new_refresh;
                $this->saveSetting($this->account_key . '_session_key', $new_refresh);
            }
            $this->log('Token refreshed OK');
        } else {
            $this->log('Token refresh FAILED: ' . json_encode($resp));
        }
    }

    // ── HTTP ──────────────────────────────────────────────────────────────────

    private function graphql(string $query, array $variables, string $operation, bool $skip_auth = false): array
    {
        $body = json_encode([
            'operationName' => $operation,
            'query'         => $query,
            'variables'     => empty($variables) ? new \stdClass() : $variables,
        ]);

        $headers = [
            'Content-Type: application/json',
            'Accept: application/json',
            'Origin: https://999.md',
            'Referer: https://999.md/',
            'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36',
        ];

        if (!$skip_auth && $this->access_token) {
            $headers[] = 'accesstoken: ' . $this->access_token;
        }

        $ch = curl_init(self::GRAPHQL_URL);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 8,
            CURLOPT_CONNECTTIMEOUT => 4,
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => $body,
            CURLOPT_HTTPHEADER     => $headers,
        ]);
        $resp     = curl_exec($ch);
        $curl_err = curl_error($ch);
        $code     = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($curl_err) {
            $this->log("CURL ERROR [$operation]: $curl_err");
            return [];
        }

        $data = json_decode($resp, true);
        if (!is_array($data)) {
            $this->log("JSON ERROR [$operation] HTTP $code: " . substr($resp, 0, 200));
            return [];
        }

        return $data;
    }

    // ── DB helpers ────────────────────────────────────────────────────────────

    private function getSetting(string $key): string
    {
        $stmt = $this->db->prepare("SELECT value FROM {$this->prefx}_settings WHERE name=:k LIMIT 1");
        $stmt->execute([':k' => $key]);
        return $stmt->fetchColumn() ?: '';
    }

    private function saveSetting(string $key, string $value): void
    {
        $check = $this->db->prepare("SELECT COUNT(*) FROM {$this->prefx}_settings WHERE name=:k");
        $check->execute([':k' => $key]);
        if ($check->fetchColumn() > 0) {
            $this->db->prepare("UPDATE {$this->prefx}_settings SET value=:v WHERE name=:k")->execute([':v'=>$value,':k'=>$key]);
        } else {
            $this->db->prepare("INSERT INTO {$this->prefx}_settings (name,value) VALUES (:k,:v)")->execute([':k'=>$key,':v'=>$value]);
        }
    }

    private function log(string $msg): void
    {
        $path = !empty($_SERVER['DOCUMENT_ROOT']) ? $_SERVER['DOCUMENT_ROOT'] : dirname(__DIR__, 2);
        file_put_contents($path . '/logs/999_chat.log', date('Y-m-d H:i:s') . ' [' . $this->account_key . '] ' . $msg . "\n", FILE_APPEND | LOCK_EX);
    }
}
