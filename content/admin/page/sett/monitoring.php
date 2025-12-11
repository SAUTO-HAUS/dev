<?php

echo '
<h2>'.$adm_lang['monitoring'].'</h2>
<br><br>
<a href="/'.$_COOKIE['lang'].'/'.$admin_dir.'/sett/monitoring/feeds" style="display:flex; align-items:center; gap:10px; padding:15px; background:#007bff; color:white; text-decoration:none; border-radius:8px; margin-bottom:15px; font-size:18px;">
    <img src="/content/admin/page/sett/monitoring/icons/facebook.svg" style="width:28px; height:28px; filter:brightness(0) invert(1);" alt="Facebook">
    '.$adm_lang['feeds'].'
</a>

<a href="/'.$_COOKIE['lang'].'/'.$admin_dir.'/sett/monitoring/feeds_gen" style="display:flex; align-items:center; gap:10px; padding:15px; background:#e2001a; color:white; text-decoration:none; border-radius:8px; margin-bottom:15px; font-size:18px;">
    <img src="/content/admin/page/sett/monitoring/icons/refresh.svg" style="width:28px; height:28px; filter:brightness(0) invert(1);" alt="Generate Feeds">
    Генерация фидов вручную
</a>

<a href="/'.$_COOKIE['lang'].'/'.$admin_dir.'/sett/monitoring/sitemap_gen" style="display:flex; align-items:center; gap:10px; padding:15px; background:#28a745; color:white; text-decoration:none; border-radius:8px; margin-bottom:15px; font-size:18px;">
    <img src="/content/admin/page/sett/monitoring/icons/sitemap.svg" style="width:28px; height:28px; filter:brightness(0) invert(1);" alt="Sitemap">
    '.$adm_lang['sitemap_gen'].'
</a>

<a href="/'.$_COOKIE['lang'].'/'.$admin_dir.'/sett/monitoring/sitemap_logs" style="display:flex; align-items:center; gap:10px; padding:15px; background:#6f42c1; color:white; text-decoration:none; border-radius:8px; margin-bottom:15px; font-size:18px;">
    <img src="/content/admin/page/sett/monitoring/icons/logs.svg" style="width:24px; height:24px; filter:brightness(0) invert(1);" alt="Logs">
    '.$adm_lang['sitemap_logs'].'
</a>
';

?>
