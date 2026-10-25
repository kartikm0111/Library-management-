<?php
require_once __DIR__ . '/config.php';
require_login();
$current_page = basename($_SERVER['PHP_SELF']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>College Library Management System</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
<nav class="navbar">
    <a href="index.php" class="navbar-brand">College Library LMS</a>
    <ul class="navbar-links">
        <li><a href="index.php" class="<?= $current_page === 'index.php' ? 'active' : '' ?>">Dashboard</a></li>
        <li><a href="books.php" class="<?= $current_page === 'books.php' ? 'active' : '' ?>">Books</a></li>
        <li><a href="members.php" class="<?= $current_page === 'members.php' ? 'active' : '' ?>">Members</a></li>
        <li><a href="issue.php" class="<?= $current_page === 'issue.php' ? 'active' : '' ?>">Issue Book</a></li>
        <li><a href="return.php" class="<?= $current_page === 'return.php' ? 'active' : '' ?>">Return Book</a></li>
        <li><a href="issued.php" class="<?= $current_page === 'issued.php' ? 'active' : '' ?>">Issued List</a></li>
        <li><a href="overdue.php" class="<?= $current_page === 'overdue.php' ? 'active' : '' ?>">Overdue List</a></li>
        <li><a href="fines.php" class="<?= $current_page === 'fines.php' ? 'active' : '' ?>">Fines</a></li>
    </ul>
    <div class="navbar-user">
        <span>Staff: <strong><?= h($_SESSION['staff_name'] ?? 'Librarian') ?></strong></span>
        <a href="logout.php" class="btn-logout">Logout</a>
    </div>
</nav>
<div class="container">
