<!DOCTYPE html>
<html lang="en">
<head>
    <!-- This meta tag is CRITICAL for making it responsive on mobile -->
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=0">
    <title><?= $pageTitle ?? 'Mobile App' ?></title>
    
    <!-- Include Bootstrap 5 (if you aren't already) -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    
    <style>
        body { background-color: #f4f6f9; }
        .mobile-navbar {
            background-color: #007bff;
            color: white;
            padding: 15px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }
        .mobile-navbar h5 { margin: 0; font-weight: 600; }
        .logout-btn { color: white; text-decoration: none; }
    </style>
</head>
<body>

<div class="mobile-navbar d-flex justify-content-between align-items-center mb-3">
    <h5><?= $pageTitle ?? 'Dashboard' ?></h5>
    <a href="<?= BASE_URL ?>login/logout" class="logout-btn"><i class="fa fa-sign-out-alt"></i></a>
</div>

<div class="container pb-5">