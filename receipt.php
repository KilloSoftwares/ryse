<?php 
include 'db_connect.php';
$id = (int)$_GET['id'];

// Fetch order and items
$order = $conn->query("SELECT * FROM orders WHERE id = $id");
foreach($order->fetch_array() as $k => $v){
    $$k = $v;
}
$items = $conn->query("SELECT o.*, p.name 
                        FROM order_items o 
                        INNER JOIN products p ON p.id = o.product_id 
                        WHERE o.order_id = $id");

// ✅ NEW CODE: Compute and store change in DB if payment was made
if ($amount_tendered > 0) {
    $change = $amount_tendered - $total_amount;
    // Update the order record
    $update = $conn->prepare("UPDATE orders SET `change` = ? WHERE id = ?");
    $update->bind_param("di", $change, $id);
    $update->execute();
}

ob_start();
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Receipt/Bill</title>
    <style>
        .flex{ display:inline-flex; width:100%; }
        .w-50{ width:50%; }
        .text-center{ text-align:center; }
        .text-right{ text-align:right; }
        table.wborder{ width:100%; border-collapse:collapse; }
        table.wborder>tbody>tr, table.wborder>tbody>tr>td{ border:1px solid; }
        p{ margin:unset; }
        @media print { 
            button#printBtn { display:none; }
            @page {
                margin: 0;
            }
            body {
                margin: 10mm;
            }
        }
    </style>
</head>
<body>
<div class="container-fluid">
    <button id="printBtn" onclick="window.print()">Print / Save</button>
    <p class="text-center"><b><?php echo $amount_tendered > 0 ? "Receipt" : "Bill" ?></b></p>
    <hr>
    <div class="flex">
        <div class="w-100">
            <?php if($amount_tendered > 0): ?>
                <p>Invoice Number: <b><?php echo htmlspecialchars($ref_no) ?></b></p>
            <?php endif; ?>
            <p>Date: <b><?php echo date("m-d-Y H:i", strtotime($date_created)) ?></b></p>
        </div>
    </div>
    <hr>
    <p><b>Order List</b></p>
    <table width="100%">
        <thead>
            <tr>
                <td><b>QTY</b></td>
                <td><b>Order</b></td>
                <td class="text-right"><b>Amount</b></td>
            </tr>
        </thead>
        <tbody>
        <?php while($row = $items->fetch_assoc()): ?>
            <tr>
                <td><?php echo (int)$row['qty'] ?></td>
                <td>
                    <p><?php echo htmlspecialchars($row['name']) ?></p>
                    <?php if($row['qty'] > 0): ?>
                        <small>(<?php echo number_format($row['price'],2) ?>)</small>
                    <?php endif; ?>
                </td>
                <td class="text-right"><?php echo number_format($row['amount'],2) ?></td>
            </tr>
        <?php endwhile; ?>
        </tbody>
    </table>
    <hr>
    <table width="100%">
        <tbody>
            <tr>
                <td><b>Total Amount</b></td>
                <td class="text-right"><b><?php echo number_format($total_amount,2) ?></b></td>
            </tr>
            <?php if($amount_tendered > 0): ?>
            <tr>
                <td><b>Amount Paid</b></td>
                <td class="text-right"><b><?php echo number_format($amount_tendered,2) ?></b></td>
            </tr>
            <tr>
                <td><b>Change</b></td>
                <td class="text-right"><b><?php echo number_format($change,2) ?></b></td>
            </tr>
            <?php endif; ?>
        </tbody>
    </table>
    <hr>
    <p class="text-center"><b>Order No.</b></p>
    <h4 class="text-center"><b><?php echo htmlspecialchars($order_number) ?></b></h4>
</div>
</body>
</html>
<?php
// Capture the HTML into a variable
$html = ob_get_clean();

// Auto-save the HTML as a file in a local folder
$saveDir = __DIR__ . '/receipts';
if (!is_dir($saveDir)) {
    mkdir($saveDir, 0777, true);
}
$filename = $saveDir . "/receipt_" . $id . ".html";
file_put_contents($filename, $html);

// Output the HTML to the browser
echo $html;
?>
