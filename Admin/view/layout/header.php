<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title><?php echo isset($pageTitle) ? htmlspecialchars($pageTitle) : 'Dashboard'; ?></title>

    <link rel="stylesheet" href="<?= BASE_URL ?>config/Addons/style.css">
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    
    <link rel="stylesheet" href="https://cdn.datatables.net/2.3.8/css/dataTables.dataTables.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/buttons/2.4.2/css/buttons.dataTables.min.css">

    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css">

<link rel="apple-touch-icon" sizes="180x180" href="<?= BASE_URL ?>config/image/r-logo.png">
<link rel="icon" type="image/png" sizes="32x32" href="<?= BASE_URL ?>config/image/r-logo.png">
<link rel="icon" type="image/png" sizes="16x16" href="<?= BASE_URL ?>config/image/r-logo.png">
<link rel="manifest" href="<?= BASE_URL ?>config/image/site.webmanifest">


<!-- <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script> -->
    <style>
        .detail { display:flex; justify-content:flex-end; padding:6px; }
    </style>
</head>
<body>



    <div class="wrapper">
        <?php include('config/Addons/sidebar.php'); ?>
            <main class="content px-3 py-2 bg-light">
                <div class="container-fluid">
                    <div class="mb-3">