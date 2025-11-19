<?php
// --- DB CONNECTION ---
$conn = new mysqli("localhost", "root", "", "eliam");
if ($conn->connect_error) { die("Connection failed: " . $conn->connect_error); }

// --- FETCH CHART DATA ---
$daily = $conn->query("SELECT DATE(date_created) as day, SUM(total_amount) as total 
                       FROM orders GROUP BY DATE(date_created) ORDER BY day ASC");
$weekly = $conn->query("SELECT YEARWEEK(date_created, 1) as week, SUM(total_amount) as total 
                        FROM orders GROUP BY YEARWEEK(date_created, 1) ORDER BY week ASC");
$monthly = $conn->query("SELECT DATE_FORMAT(date_created, '%Y-%m') as month, SUM(total_amount) as total 
                         FROM orders GROUP BY DATE_FORMAT(date_created, '%Y-%m') ORDER BY month ASC");
$categories = $conn->query("SELECT c.name, SUM(oi.amount) as total 
                            FROM order_items oi
                            INNER JOIN products p ON oi.product_id = p.id
                            INNER JOIN categories c ON p.category_id = c.id
                            GROUP BY c.name");

function fetchData($result) {
    $arr = [];
    while($row = $result->fetch_assoc()) { $arr[] = $row; }
    return $arr;
}

$dailyData = fetchData($daily);
$weeklyData = fetchData($weekly);
$monthlyData = fetchData($monthly);
$categoryData = fetchData($categories);
?>
<!DOCTYPE html>
<html>
<head>
    <title>Sales Dashboard</title>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        body {
            font-family: Arial, sans-serif;
            background: #f4f4f4;
            margin: 0;
            padding: 20px;
            text-align: center;
        }
        h1 {
            margin-bottom: 20px;
            color: #333;
        }
        .charts-container {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(400px, 1fr));
            gap: 30px;
            max-width: 1400px;
            margin: auto;
        }
        .chart-box {
            background: white;
            padding: 20px;
            border-radius: 12px;
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
            page-break-inside: avoid;
        }
        canvas {
            width: 100% !important;
            height: 300px !important;
        }
        button#printBtn {
            margin-bottom: 20px;
            padding: 10px 20px;
            font-size: 16px;
            cursor: pointer;
        }

        /* PRINT STYLES */
        @media print {
            body {
                background: #fff;
                padding: 0;
            }
            h1 {
                color: #000;
            }
            button#printBtn {
                display: none;
            }
            .charts-container {
                grid-template-columns: 1fr; /* One chart per row */
                gap: 20px;
            }
            .chart-box {
                box-shadow: none;
                border: 1px solid #000;
                padding: 10px;
            }
            canvas {
                height: 400px !important;
            }
        }
    </style>
</head>
<body>
    <h1>Sales Dashboard</h1>
    <button id="printBtn" onclick="window.print()">🖨️ Print Dashboard</button>

    <div class="charts-container">
        <div class="chart-box">
            <h3>Daily Sales</h3>
            <canvas id="dailyChart"></canvas>
        </div>
        <div class="chart-box">
            <h3>Weekly Sales</h3>
            <canvas id="weeklyChart"></canvas>
        </div>
        <div class="chart-box">
            <h3>Monthly Sales</h3>
            <canvas id="monthlyChart"></canvas>
        </div>
        <div class="chart-box">
            <h3>Sales by Category</h3>
            <canvas id="categoryChart"></canvas>
        </div>
    </div>

    <script>
    const dailyLabels = <?= json_encode(array_column($dailyData ?? [], 'day')) ?>;
    const dailyTotals = <?= json_encode(array_map('floatval', array_column($dailyData ?? [], 'total'))) ?>;

    const weeklyLabels = <?= json_encode(array_map(fn($w) => 'Week ' . $w['week'], $weeklyData ?? [])) ?>;
    const weeklyTotals = <?= json_encode(array_map('floatval', array_column($weeklyData ?? [], 'total'))) ?>;

    const monthlyLabels = <?= json_encode(array_column($monthlyData ?? [], 'month')) ?>;
    const monthlyTotals = <?= json_encode(array_map('floatval', array_column($monthlyData ?? [], 'total'))) ?>;

    const catLabels = <?= json_encode(array_column($categoryData ?? [], 'name')) ?>;
    const catTotals = <?= json_encode(array_map('floatval', array_column($categoryData ?? [], 'total'))) ?>;
    const catColors = catLabels.map(() => '#' + Math.floor(Math.random()*16777215).toString(16));

    const chartOptions = { responsive: true, maintainAspectRatio: false };

    new Chart(document.getElementById('dailyChart'), {
        type: 'bar',
        data: { labels: dailyLabels, datasets: [{ label: 'Daily Sales (Ksh)', data: dailyTotals, backgroundColor: 'rgba(54,162,235,0.7)' }] },
        options: chartOptions
    });

    new Chart(document.getElementById('weeklyChart'), {
        type: 'line',
        data: { labels: weeklyLabels, datasets: [{ label: 'Weekly Sales (Ksh)', data: weeklyTotals, borderColor: 'rgba(75,192,192,1)', fill: false, tension: 0.3 }] },
        options: chartOptions
    });

    new Chart(document.getElementById('monthlyChart'), {
        type: 'bar',
        data: { labels: monthlyLabels, datasets: [{ label: 'Monthly Sales (Ksh)', data: monthlyTotals, backgroundColor: 'rgba(255,159,64,0.7)' }] },
        options: chartOptions
    });

    new Chart(document.getElementById('categoryChart'), {
        type: 'pie',
        data: { labels: catLabels, datasets: [{ data: catTotals, backgroundColor: catColors }] },
        options: chartOptions
    });
    </script>
</body>
</html>
