<?php
include 'db_connect.php';

$data = [
    'labels' => [],
    'values' => []
];

// Query to get today's sold items
$sql = "SELECT p.name, SUM(oi.qty) as total_sold
        FROM order_items oi
        INNER JOIN orders o ON oi.order_id = o.id
        INNER JOIN products p ON oi.product_id = p.id
        WHERE DATE(o.date_created) = CURDATE()
        GROUP BY p.name";

$qry = $conn->query($sql);

while ($row = $qry->fetch_assoc()) {
    $data['labels'][] = $row['name'];
    $data['values'][] = $row['total_sold'];
}

echo json_encode($data);
