<?php 
include 'db_connect.php'; 

$month = isset($_GET['month']) ? $conn->real_escape_string($_GET['month']) : date('Y-m');
$day   = isset($_GET['day']) ? $conn->real_escape_string($_GET['day']) : '';
$week  = isset($_GET['week']) ? $conn->real_escape_string($_GET['week']) : '';

$where = "";
$report_title = "";

// Determine report type
if (!empty($day)) {
    $where = "DATE(date_created) = '$day'";
    $report_title = "Daily Report for " . date("F d, Y", strtotime($day));
} elseif (!empty($week)) {
    $year = date('Y', strtotime($week));
    $week_num = date('W', strtotime($week));
    $where = "YEAR(date_created) = '$year' AND WEEK(date_created, 1) = '$week_num'";
    $report_title = "Weekly Report for Week $week_num of $year";
} else {
    $where = "DATE_FORMAT(date_created, '%Y-%m') = '$month'";
    $report_title = "Monthly Report for " . date("F, Y", strtotime($month . '-01'));
}
?>

<div class="container-fluid">
    <div class="col-lg-12">
        <div class="card">
            <div class="card-body">
                <div class="row justify-content-center pt-4">
                    <label class="mt-2">Month:</label>
                    <div class="col-sm-3">
                        <input type="month" name="month" id="month" value="<?php echo htmlspecialchars($month) ?>" class="form-control">
                    </div>
                    <label class="mt-2 ml-2">Day:</label>
                    <div class="col-sm-3">
                        <input type="date" name="day" id="day" value="<?php echo htmlspecialchars($day) ?>" class="form-control">
                    </div>
                    <label class="mt-2 ml-2">Week:</label>
                    <div class="col-sm-3">
                        <input type="week" name="week" id="week" value="<?php echo htmlspecialchars($week) ?>" class="form-control">
                    </div>
                </div>
            </div>
            <hr>
            <div class="col-md-12">
                <table class="table table-bordered" id="report-list">
                    <thead class="thead-dark">
                        <tr>
                            <th class="text-center">#</th>
                            <th>Date</th>
                            <th>Order Items</th>
                            <th>Invoice</th>
                            <th>Order Number</th>
                            <th class="text-right">Amount (KSh)</th>
                            <th class="text-right">Discount (KSh)</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        $i = 1; 
                        $total = 0;
                        $change = 0;

                        $query = "SELECT * FROM orders WHERE amount_tendered > 0 AND $where ORDER BY date_created ASC";
                        $sales = $conn->query($query);

                        if ($sales && $sales->num_rows > 0):
                            while($row = $sales->fetch_assoc()):
                                $total += $row['total_amount'];
                                $change += $row['change'];
                        ?>
                        <tr>
                            <td class="text-center"><?php echo $i++ ?></td>
                            <td><b><?php echo date("Y-m-d H:i", strtotime($row['date_created'])) ?></b></td>
                            <td>
                                <?php 
                                $items = $conn->query("SELECT oi.qty, p.name 
                                                       FROM order_items oi 
                                                       INNER JOIN products p ON p.id = oi.product_id 
                                                       WHERE oi.order_id = '".$row['id']."'");
                                if ($items && $items->num_rows > 0):
                                    while($item = $items->fetch_assoc()):
                                ?>
                                    <p>- <?php echo htmlspecialchars($item['name']); ?> (x<?php echo intval($item['qty']); ?>)</p>
                                <?php 
                                    endwhile;
                                else:
                                    echo "<p>No Items</p>";
                                endif;
                                ?>
                            </td>
                            <td><b><?php echo $row['amount_tendered'] > 0 ? htmlspecialchars($row['ref_no']) : 'N/A'; ?></b></td>
                            <td><b><?php echo htmlspecialchars($row['order_number']); ?></b></td>
                            <td class="text-right"><b><?php echo number_format($row['total_amount'], 2); ?></b></td>
                            <td class="text-right"><b><?php echo number_format($row['change'], 2); ?></b></td>
                        </tr>
                        <?php endwhile; else: ?>
                        <tr>
                            <td class="text-center" colspan="7">No Data Found.</td>
                        </tr>
                        <?php endif; ?>
                    </tbody>
                    <tfoot>
                        <tr>
                            <th colspan="5" class="text-right">Total</th>
                            <th class="text-right"><?php echo number_format($total, 2); ?></th>
                            <th class="text-right"><?php echo number_format($change, 2); ?></th>
                        </tr>
                        <tr>
                            <th colspan="5" class="text-right">Grand Total</th>
                            <th colspan="2" class="text-right"><?php echo number_format($total + $change, 2); ?></th>
                        </tr>
                    </tfoot>
                </table>
                <hr>
                <div class="col-md-12 mb-4">
                    <center>
                        <button class="btn btn-success btn-sm col-sm-3" type="button" id="print"><i class="fa fa-print"></i> Print</button>
                    </center>
                </div>
            </div>
        </div>
    </div>
</div>

<noscript>
    <style>
        table#report-list { width:100%; border-collapse:collapse; }
        table#report-list td, table#report-list th { border:1px solid #000; padding:4px; }
        .text-center { text-align:center; }
        .text-right { text-align:right; }
        p { margin:unset; }
    </style>
</noscript>

<script>
$('#month').change(function(){
    location.replace('index.php?page=sales_report&month=' + $(this).val());
});
$('#day').change(function(){
    location.replace('index.php?page=sales_report&day=' + $(this).val());
});
$('#week').change(function(){
    location.replace('index.php?page=sales_report&week=' + $(this).val());
});

$('#print').click(function(){
    var printContents = $('#report-list').clone();
    var printStyles = $('noscript').clone();
    printStyles.append(printContents);
    var printWindow = window.open('', '_blank', 'width=900,height=600');
    printWindow.document.write('<h4 class="text-center"><?php echo $report_title; ?></h4>');
    printWindow.document.write(printStyles.html());
    printWindow.document.close();
    printWindow.focus();
    printWindow.print();
    setTimeout(() => printWindow.close(), 800);
});
</script>
