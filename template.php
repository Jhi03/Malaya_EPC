<?php
    include('validate_login.php');
    $page_title = "Malaya Solar Energies Inc.";
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $page_title ?></title>
    <link rel="icon" href="images/Malaya_Logo.png" type="image/png">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Atkinson+Hyperlegible&display=swap" rel="stylesheet">
    <link href="css/ms_dashboard.css" rel="stylesheet">
    <link href="css/ms_sidebar.css" rel="stylesheet">
    <link href="css/ms_header.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <style>
        body {
            font-family: 'Atkinson Hyperlegible', sans-serif;
            margin: 0;
            display: flex;
            height: 100vh; /* Full viewport height */
            overflow: hidden; /* Prevent overall page scroll */
        }
        .content-body {
            padding: 20px 40px 20px 40px;
            flex: 1;
            overflow-x: hidden;
            display: flex;
            flex-direction: column;
            width: 100%;
            margin-left: auto;
            margin-right: auto;

            background: rgba(243, 243, 243, 0.8);
            background-image: repeating-linear-gradient(
                45deg,
                rgba(255, 255, 255, 0.700) 0px,
                rgba(255, 255, 255, 0.500) 1px,
                transparent 1px,
                transparent 20px
            );
            backdrop-filter: blur(12px) saturate(180%);
            -webkit-backdrop-filter: blur(12px) saturate(180%);
        }
    </style>
</head>
<body>
    <div class="sidebar" id="sidebar">
        <?php include 'sidebar.php'; ?>
    </div>

    <div class="content-area">
        <?php include 'header.php'; ?>
        <div class="content-body">
            <!-- ms_workforce.php contents here -->
        </div>
    </div>

    <script src="js/sidebar.js"></script>
    <script src="js/header.js"></script>
</body>
</html>