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
    $db_name,
    $port
);

if ($con->connect_error) {
    die("Connection Failed: " . $con->connect_error);
}


?>