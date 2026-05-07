<?php
/**
 * Facebook Token Helper — temporary, delete after use
 * Step 1: https://www.sauto.md/fb_token_helper.php?secret=sauto2024
 * Step 2: https://www.sauto.md/fb_token_helper.php?secret=sauto2024&code=CODE_FROM_STEP1
 */
if (($_GET['secret'] ?? '') !== 'sauto2024') { http_response_code(403); exit('Forbidden'); }

define('_DOIT', 1);
require_once __DIR__ . '/environment.php';
require_once __DIR__ . '/content/default/defines.php';
require_once __DIR__ . '/content/default/functions.php';
require_once __DIR__ . '/content/default/config.php';
require_once __DIR__ . '/content/default/dbi.php';

$app_id     = '1137294825141008';
$app_secret = '7648486532d781f4e034f6392c7d9d11';
$redirect   = 'https://www.sauto.md/fb_token_helper.php?secret=sauto2024';
$scope      = 'instagram_manage_messages,pages_messaging,instagram_basic,pages_read_engagement,pages_show_list';

header('Content-Type: text/html; charset=utf-8');

// ── Step 2: exchange code for token ──────────────────────────────────────────
if (!empty($_GET['code'])) {
    $code = $_GET['code'];

    // Exchange code for short-lived user token
    $url = "https://graph.facebook.com/v21.0/oauth/access_token"
         . "?client_id=$app_id&redirect_uri=" . urlencode($redirect)
         . "&client_secret=$app_secret&code=" . urlencode($code);
    $resp = file_get_contents($url);
    $data = json_decode($resp, true);

    if (empty($data['access_token'])) {
        echo "<h2>Eroare la exchange code</h2><pre>$resp</pre>"; exit;
    }
    $short_token = $data['access_token'];

    // Exchange for long-lived user token
    $url2 = "https://graph.facebook.com/v21.0/oauth/access_token"
          . "?grant_type=fb_exchange_token&client_id=$app_id&client_secret=$app_secret"
          . "&fb_exchange_token=" . urlencode($short_token);
    $resp2 = file_get_contents($url2);
    $data2 = json_decode($resp2, true);
    $long_token = $data2['access_token'] ?? $short_token;

    // Get all pages
    $url3  = "https://graph.facebook.com/v21.0/me/accounts?access_token=" . urlencode($long_token);
    $resp3 = file_get_contents($url3);
    $pages = json_decode($resp3, true);

    echo "<h2>Pagini disponibile:</h2><pre>";
    $sauto_token = '';
    foreach (($pages['data'] ?? []) as $page) {
        echo "Pagina: {$page['name']} | ID: {$page['id']}\n";
        echo "Token: " . substr($page['access_token'], 0, 40) . "...\n\n";
        if ($page['id'] === '725963964220309') {
            $sauto_token = $page['access_token'];
        }
    }
    echo "</pre>";

    if ($sauto_token) {
        // Save to DB automatically
        $stmt = $db->prepare("UPDATE {$prefx}_settings SET value=:v WHERE name='instagram_token_1'");
        $stmt->execute([':v' => $sauto_token]);

        // Also update user token
        $stmt2 = $db->prepare("UPDATE {$prefx}_settings SET value=:v WHERE name='location_1_facebook_user_token'");
        $stmt2->execute([':v' => $long_token]);

        echo "<h2 style='color:green'>✓ Token salvat în DB pentru instagram_token_1!</h2>";
        echo "<p>Verifică: <a href='https://www.sauto.md/ig_test.php?secret=sauto2024' target='_blank'>ig_test.php</a></p>";

        // Verify token
        $debug_url = "https://graph.facebook.com/debug_token?input_token=" . urlencode($sauto_token)
                   . "&access_token=" . urlencode($app_id . '|' . $app_secret);
        $debug = json_decode(file_get_contents($debug_url), true);
        echo "<h3>Verificare token:</h3><pre>";
        echo "Valid: " . ($debug['data']['is_valid'] ? 'YES ✓' : 'NO ✗') . "\n";
        echo "Scopes: " . implode(', ', $debug['data']['scopes'] ?? []) . "\n";
        echo "Expires: " . ($debug['data']['expires_at'] ?? 'never (permanent)') . "\n";
        echo "</pre>";
    } else {
        echo "<h2 style='color:red'>Pagina SAUTO (725963964220309) nu a fost găsită în lista de pagini.</h2>";
        echo "<p>Salvează manual unul din tokenurile de mai sus în DB.</p>";
    }
    exit;
}

// ── Step 1: redirect to Facebook login ───────────────────────────────────────
$login_url = "https://www.facebook.com/dialog/oauth"
           . "?client_id=$app_id"
           . "&redirect_uri=" . urlencode($redirect)
           . "&scope=$scope"
           . "&response_type=code";

header("Location: $login_url");
exit;
