<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"/>
<meta name="viewport" content="width=device-width, initial-scale=1.0"/>
    <title><?php echo isset($pageTitle) ? htmlspecialchars($pageTitle) : 'Dashboard'; ?></title>

    <link rel="stylesheet" href="<?= BASE_URL ?>config/config/style.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/2.3.8/css/dataTables.dataTables.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/buttons/2.4.2/css/buttons.dataTables.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css">

    <style>
        .detail { display:flex; justify-content:flex-end; padding:6px; }
    </style>
</head>
<body>
<?php
include __DIR__ . '/../../config/config/auth.php';  ?>

    <div id="sidebar-overlay" onclick="closeSidebar()"></div>

<div id="page-dashboard" class="page active">
  <div class="app-shell">
    <?php 
    include "sidebar.php";
   
    ?>

    <div class="main-area">
      <?php include "header-top.php";  ?>