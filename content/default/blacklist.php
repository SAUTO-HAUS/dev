<?php defined( '_DOIT' ) or die( 'Restricted access' );

$pdo = $db->prepare('SELECT id FROM '.$prefx.'_blacklist WHERE `ip` = :user_ip AND `allow` = 0');
$pdo->execute(array( 'user_ip' => $user_ip ));
if( $pdo->fetchColumn() >= 1 ) { die(); }
unset($pdo);

?>