<?php

$con = mysqli_connect(
    "localhost",
    "root",
    "",
    "mr-soft"
);

if(!$con){
    die("Connection Failed");
}
date_default_timezone_set('Asia/Kolkata');

define('BASE_URL', '/mr/Admin/');

$base =  '/mr/Admin/';


function url($path = '')
{
    return BASE_URL . ltrim($path, '/');
}