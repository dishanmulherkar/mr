<?php
session_start();

$host = "localhost";
$user = "root";
$db_pass = "";
$db_name = "mr-soft";
$port = 3307;

$con = new mysqli(
    $host,
    $user,
    $db_pass,
    $db_name
);

if ($con->connect_error) {
    die("Connection Failed: " . $con->connect_error);
}


date_default_timezone_set('Asia/Kolkata');

$title_name = "Rudradeo pharmaceutical";


define('BASE_URL', '/mr/views/');

$base =  '/mr/views/';


function url($path = '')
{
    return BASE_URL . ltrim($path, '/');
}
?>