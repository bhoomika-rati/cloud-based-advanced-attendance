<?php
$pageTitle = isset($pageTitle) ? (string)$pageTitle : 'Student Portal';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= studentEscape($pageTitle) ?> | Attendly</title>
    <link href="../vendore/fontawesome-free/css/all.min.css" rel="stylesheet">
    <link href="vendor/bootstrap5/bootstrap.min.css" rel="stylesheet">
    <link href="css/student-panel.css?v=<?= (int) filemtime(__DIR__ . '/../css/student-panel.css') ?>" rel="stylesheet">
</head>
<body class="student-app" id="page-top">
<div id="wrapper">
    <?php include __DIR__ . '/sidebar.php'; ?>
    <div id="content-wrapper" class="d-flex flex-column">
        <div id="content">
            <?php include __DIR__ . '/topbar.php'; ?>
            <main class="student-page">
