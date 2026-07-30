<?php

error_reporting(E_ALL);
ini_set('display_errors', 1);
include 'config/Addons/config.php'; // change path if required

$result = mysqli_query($con, "
    SELECT p_id
    FROM products
    ORDER BY product_name ASC
");

$i = 1;

while($row = mysqli_fetch_assoc($result))
{
    $code = 'P' . str_pad($i, 3, '0', STR_PAD_LEFT);

    mysqli_query($con,"
        UPDATE products
        SET product_code='$code'
        WHERE p_id='{$row['p_id']}'
    ");

    $i++;
}

echo "Done";