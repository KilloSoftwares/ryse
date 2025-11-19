<?php
include 'db_connect.php';
if (session_status() === PHP_SESSION_NONE) session_start();

$sql = "
  SELECT
    o.id AS order_id,
    DATE(o.date_created) AS tx_date,
    o.ref_no,
    o.total_amount,
    o.change AS change_in_orders,  -- ✅ Read 'change' from orders table
    COALESCE(
      GROUP_CONCAT(CONCAT(p.name, ' x', oi.qty) ORDER BY p.name SEPARATOR ', '),
      'Cash sale'
    ) AS items_desc
  FROM orders o
  LEFT JOIN order_items oi ON oi.order_id = o.id
  LEFT JOIN products p ON p.id = oi.product_id
  GROUP BY o.id
  ORDER BY o.date_created ASC
";

$res = $conn->query($sql);

$rows = [];
if ($res) {
  while ($r = $res->fetch_assoc()) $rows[] = $r;
}

$total_receipt = 0.0;
$total_payment = 0.0;
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <title>Cash Book</title>
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <script src="assets/css/tailwind.min.css"></script>
  <style>
    @media print {
      body * { visibility: hidden; }
      #printable, #printable * { visibility: visible; }
      #printable {
        position: absolute;
        left: 0;
        top: 0;
        width: 100%;
      }
      #printBtn { display: none; }
    }
  </style>
</head>
<body class="bg-gray-50 min-h-screen p-6">
  <div class="max-w-6xl mx-auto">

    <!-- Header + Print button -->
    <div class="mb-6 flex justify-between items-center">
      <div>
        <h1 class="text-2xl font-bold">📘 Cash Book</h1>
        <p class="text-sm text-gray-600">Showing date, items sold, receipts, and change in orders. Totals and closing balance below.</p>
      </div>
      <button id="printBtn" onclick="window.print()" 
        class="flex items-center gap-2 bg-blue-600 text-white px-4 py-2 rounded-lg shadow hover:bg-blue-700">
        <!-- Printer Icon -->
        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                d="M6 9V2h12v7M6 18H4a2 2 0 01-2-2v-5a2 2 0 012-2h16a2 2 0 012 2v5a2 2 0 01-2 2h-2m-4 0h-4" />
        </svg>
        Print
      </button>
    </div>

    <!-- Printable area -->
    <div id="printable" class="bg-white rounded-2xl shadow overflow-hidden">
      <div class="overflow-x-auto">
        <table class="min-w-full text-sm">
          <thead class="bg-gray-100 text-gray-700 text-xs uppercase sticky top-0">
            <tr>
              <th class="px-3 py-2 border">Date</th>
              <th class="px-3 py-2 border">Ref No</th>
              <th class="px-3 py-2 border">Particulars (Commodity & Qty)</th>
              <th class="px-3 py-2 border text-right">Receipt (KSh)</th>
              <th class="px-3 py-2 border text-right">Change in Orders (KSh)</th>
              <th class="px-3 py-2 border text-right">Balance (KSh)</th>
            </tr>
          </thead>
          <tbody>
            <?php if (!empty($rows)): ?>
              <?php $running = 0.0; ?>
              <?php foreach ($rows as $r): ?>
                <?php
                  $date        = htmlspecialchars($r['tx_date'] ?? '');
                  $ref         = htmlspecialchars($r['ref_no'] ?? '');
                  $items       = htmlspecialchars($r['items_desc'] ?? 'Cash sale');
                  $receipt     = (float)($r['total_amount'] ?? 0);
                  $payment     = (float)($r['change_in_orders'] ?? 0); // ✅ reads from DB
                  $total_receipt += $receipt;
                  $total_payment += $payment;
                  $running += ($receipt + $payment);
                ?>
                <tr class="hover:bg-gray-50">
                  <td class="px-3 py-2 border whitespace-nowrap"><?= $date ?></td>
                  <td class="px-3 py-2 border"><?= $ref ?></td>
                  <td class="px-3 py-2 border"><?= $items ?></td>
                  <td class="px-3 py-2 border text-right"><?= number_format($receipt, 2) ?></td>
                  <td class="px-3 py-2 border text-right text-red-600"><?= number_format($payment, 2) ?></td>
                  <td class="px-3 py-2 border text-right font-medium"><?= number_format($running, 2) ?></td>
                </tr>
              <?php endforeach; ?>
            <?php else: ?>
              <tr>
                <td colspan="6" class="px-4 py-6 text-center text-gray-500">No orders found.</td>
              </tr>
            <?php endif; ?>
          </tbody>

          <!-- Totals -->
          <tfoot class="bg-gray-100 font-semibold">
            <?php $closing = $total_receipt + $total_payment; ?>
            <tr>
              <td colspan="3" class="px-3 py-2 border text-right">Totals</td>
              <td class="px-3 py-2 border text-right text-green-600">
                <?= number_format($total_receipt, 2) ?>
              </td>
              <td class="px-3 py-2 border text-right text-red-600">
                <?= number_format($total_payment, 2) ?>
              </td>
              <td class="px-3 py-2 border text-right text-blue-600">
                <?= number_format($closing, 2) ?>
              </td>
            </tr>
            <tr class="bg-gray-200">
              <td colspan="5" class="px-3 py-2 border text-right font-bold">Closing Balance</td>
              <td class="px-3 py-2 border text-right font-bold text-indigo-700">
                <?= number_format($closing, 2) ?>
              </td>
            </tr>
          </tfoot>
        </table>
      </div>
    </div>
  </div>
</body>
</html>
