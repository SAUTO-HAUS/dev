<?php defined( '_DOIT' ) or die( 'Restricted access' );

//PDO start

$sql_host = 'localhost';
$sql_db = 'sautom_db';
$sql_user = 'sautom_admin';
$sql_pass = 'I4gU7kdv5gsYZuZbBZ';
$sql_charset = 'utf8';

$dsn = "mysql:host=$sql_host;dbname=$sql_db;charset=$sql_charset";
$opt = [
    PDO::ATTR_PERSISTENT         => true,
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
	PDO::ATTR_EMULATE_PREPARES	 => false,
    PDO::MYSQL_ATTR_USE_BUFFERED_QUERY => true,
];
$db = new PDO($dsn, $sql_user, $sql_pass, $opt) or die('Error can\'t connect.');

//$database->setAttribute( PDO::ATTR_ERRMODE, PDO::ERRMODE_SILENT );  
//$database->setAttribute( PDO::ATTR_ERRMODE, PDO::ERRMODE_WARNING );  
//$database->setAttribute( PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION );

?>