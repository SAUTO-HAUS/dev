<?php defined('_DOIT') or die('Restricted access');

function docs_bridge_normalize_phone(string $phone): string {
    $p = preg_replace('/\D/', '', $phone);
    if (strlen($p) === 8)  return '373' . $p;
    if (strlen($p) === 9 && $p[0] === '0') return '373' . substr($p, 1);
    if (strlen($p) === 11 && substr($p, 0, 3) === '373') return $p;
    return $p;
}

function docs_bridge_doc_to_status(string $doc_f): ?string {
    $map = [
        'con_arvon'       => 'transaction',
        'con_arvon_com'   => 'transaction',
        'con_intermed'    => 'transaction',
        'con_plata'       => 'transaction',
        'cesionar'        => 'transaction',
        'act_compensare'  => 'transaction',

        'vinzare_sauto'   => 'closed',
        'vinzare_proc'    => 'closed',

        'invoice'         => null,
        'com_transport'   => null,
        'foaie_parcurs'   => null,
        'foaie_parcurs_cars' => null,
        'anexa_vinzare_proc' => null,
        'vinzare_avans'   => null, // handled via popup
    ];
    return $map[$doc_f] ?? null;
}

function docs_bridge_needs_confirm(string $doc_f): bool {
    return in_array($doc_f, ['vinzare_avans']);
}

function docs_bridge_apply(PDO $db, string $prefx, int $doc_id, string $doc_f, object $lead, int $adm_id = 0, string $dept = ''): array {
    $lead_id    = (int)$lead->id;
    $old_status = $lead->status;

    $db->prepare("UPDATE {$prefx}_docs_ctlg SET lead_id=:lid WHERE id=:id")
       ->execute([':lid' => $lead_id, ':id' => $doc_id]);

    if ($dept && in_array($dept, ['stock', 'order', 'pruncul'])) {
        $db->prepare("UPDATE {$prefx}_crm_leads SET doc_id=:did, doc_type=:dt, department=:dept, updated_at=NOW() WHERE id=:id")
           ->execute([':did' => $doc_id, ':dt' => $doc_f, ':dept' => $dept, ':id' => $lead_id]);
    } else {
        $db->prepare("UPDATE {$prefx}_crm_leads SET doc_id=:did, doc_type=:dt, updated_at=NOW() WHERE id=:id")
           ->execute([':did' => $doc_id, ':dt' => $doc_f, ':id' => $lead_id]);
    }

    $new_status    = docs_bridge_doc_to_status($doc_f);
    $needs_confirm = docs_bridge_needs_confirm($doc_f);

    if ($needs_confirm && $old_status !== 'closed') {
        if (function_exists('crm_audit')) {
            crm_audit($db, $prefx, 'doc_linked', 'lead', $lead_id,
                "doc_f={$doc_f} doc_id={$doc_id} awaiting_confirm",
                $old_status, $adm_id ?: null);
        }
        return ['needs_confirm' => true, 'lead_id' => $lead_id];
    }

    if ($new_status && $new_status !== $old_status && $old_status !== 'closed') {
        $db->prepare("UPDATE {$prefx}_crm_leads SET status=:s, last_action_at=NOW(), updated_at=NOW() WHERE id=:id")
           ->execute([':s' => $new_status, ':id' => $lead_id]);
        if (function_exists('crm_audit')) {
            crm_audit($db, $prefx, 'doc_status_change', 'lead', $lead_id,
                "doc_f={$doc_f} doc_id={$doc_id} {$old_status}→{$new_status}",
                $new_status, $adm_id ?: null);
        }
    } else {
        if (function_exists('crm_audit')) {
            crm_audit($db, $prefx, 'doc_linked', 'lead', $lead_id,
                "doc_f={$doc_f} doc_id={$doc_id}",
                $old_status, $adm_id ?: null);
        }
    }
    return ['lead_id' => $lead_id];
}

function docs_bridge_link(PDO $db, string $prefx, int $doc_id, string $doc_f, string $u_phn, int $adm_id = 0, string $dept = ''): array {
    if (!$u_phn) return [];
    $norm_phone = docs_bridge_normalize_phone($u_phn);
    if (strlen($norm_phone) < 8) return [];

    $stmt = $db->prepare("
        SELECT id, status, phone, owner_id FROM {$prefx}_crm_leads
        WHERE phone = :phone
        ORDER BY FIELD(status,'active','transaction','processed','missed','unprocessed','closed','junk'), updated_at DESC
        LIMIT 1
    ");
    $stmt->execute([':phone' => $norm_phone]);
    $lead = $stmt->fetchObject();
    if (!$lead) return [];

    return docs_bridge_apply($db, $prefx, $doc_id, $doc_f, $lead, $adm_id, $dept);
}

function docs_bridge_link_by_lead(PDO $db, string $prefx, int $doc_id, string $doc_f, int $lead_id, int $adm_id = 0, string $dept = ''): array {
    if (!$lead_id) return [];

    $stmt = $db->prepare("SELECT id, status, phone, owner_id FROM {$prefx}_crm_leads WHERE id = :id LIMIT 1");
    $stmt->execute([':id' => $lead_id]);
    $lead = $stmt->fetchObject();
    if (!$lead) return [];

    return docs_bridge_apply($db, $prefx, $doc_id, $doc_f, $lead, $adm_id, $dept);
}

function docs_bridge_get_for_lead(PDO $db, string $prefx, int $lead_id): array {
    $stmt = $db->prepare("
        SELECT d.id, d.f, d.gr, d.y, d.q, d.n, d.date, d.adm, d.crtd,
               u.nm AS client_name, u.phn AS client_phone
        FROM {$prefx}_docs_ctlg d
        LEFT JOIN {$prefx}_docs_u u ON u.id = d.u
        WHERE d.lead_id = :lid
        ORDER BY d.id DESC
    ");
    $stmt->execute([':lid' => $lead_id]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function docs_bridge_doc_label(string $doc_f): string {
    $labels = [
        'con_plata'          => 'Cont de plată',
        'con_arvon'          => 'Contract de arvună',
        'con_arvon_com'      => 'Contract de arvună (la comandă)',
        'vinzare_avans'      => 'Contract V-C (avans)',
        'vinzare_sauto'      => 'Contract V-C (Sauto cumpărător)',
        'vinzare_proc'       => 'Contract V-C',
        'cesionar'           => 'Anexă cesiune',
        'con_intermed'       => 'Contract de intermediere',
        'act_compensare'     => 'Act de compensare',
        'invoice'            => 'Invoice',
        'com_transport'      => 'Comandă transport',
        'foaie_parcurs'      => 'Foaie de parcurs',
        'foaie_parcurs_cars' => 'Foaie de parcurs (auto)',
    ];
    return $labels[$doc_f] ?? ucfirst(str_replace('_', ' ', $doc_f));
}
