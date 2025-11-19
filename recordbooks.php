<?php
// --- Enable errors ---
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// --- DB connection ---
$conn = new mysqli("localhost", "root", "", "eliam");
if ($conn->connect_error) die("Connection failed: " . $conn->connect_error);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Record Keeping Books</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
<div class="container mt-5">
    <h1 class="mb-4 text-center">Record Keeping Books</h1>
    <div class="row g-3">
        <div class="col-md-4">
            <a href="index.php?page=cash_book" class="btn btn-primary btn-lg w-100">Cash Book</a>
        </div>
        <div class="col-md-4">
            <a href="index.php?page=profits" class="btn btn-success btn-lg w-100">Profits Book</a>
        </div>
        <div class="col-md-4">
            <a href="index.php?page=discount" class="btn btn-warning btn-lg w-100">Discount Account</a>
        </div>
        <div class="col-md-4">
            <a href="index.php?page=fdiscount" class="btn btn-info btn-lg w-100">Sales Account</a>
        </div>
        <div class="col-md-4">
            <a href="index.php?page=recordTransaction" class="btn btn-info btn-lg w-100">Supplier's Account</a>
        </div>
        <div class="col-md-4">
            <a href="index.php?page=view_stock" class="btn btn-danger btn-lg w-100">Purchase Account</a>
        </div>
    </div>
</div>
</body>
</html>
