<?php

echo '
<h2>'.$adm_lang['monitoring'].'</h2>
<br><br>
<a href="/'.$_COOKIE['lang'].'/'.$admin_dir.'/sett/monitoring/feeds" style="display:block; padding:15px; background:#007bff; color:white; text-decoration:none; border-radius:8px; margin-bottom:15px; font-size:18px;">📱 '.$adm_lang['feeds'].'</a>

<a href="/'.$_COOKIE['lang'].'/'.$admin_dir.'/sett/monitoring/sitemap_gen" style="display:block; padding:15px; background:#28a745; color:white; text-decoration:none; border-radius:8px; margin-bottom:15px; font-size:18px;">🗺️ '.$adm_lang['sitemap_gen'].'</a>

<a href="/'.$_COOKIE['lang'].'/'.$admin_dir.'/sett/monitoring/sitemap_logs" style="display:block; padding:15px; background:#6f42c1; color:white; text-decoration:none; border-radius:8px; margin-bottom:15px; font-size:18px;">📋 '.$adm_lang['sitemap_logs'].'</a>
';

?>
