<?php

// include('config.php');

// if (!isset($_SESSION['mr_id'])) {
//     header("Location: login");
//     exit();
// }

$mr_id = (int)$_SESSION['mr_id'];

// $q = mysqli_query($con, "
//     SELECT status
//     FROM mr_users
//     WHERE m_id='$mr_id'
//     LIMIT 1
// ");

// if (mysqli_num_rows($q) == 0) {
//     session_destroy();
//     header("Location: login");
//     exit();
// }

// $row = mysqli_fetch_assoc($q);

// if ($row['status'] != 'Active') {
//     session_destroy();
//     header("Location: login");
//     exit();
// }