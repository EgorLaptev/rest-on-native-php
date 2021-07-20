<?php

$db_host = 'localhost';
$db_name   = 'flightpool_rest';
$db_user = 'root';
$db_pass = '';
$db_charset = 'utf8';

$dsn = "mysql:host=$db_host;dbname=$db_name;charset=$db_charset";

$pdo = new PDO($dsn, $db_user, $db_pass);