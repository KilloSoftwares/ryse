<?php
include('db_connect.php');

// Handle AJAX actions
if(isset($_POST['action'])){
    $action = $_POST['action'];

    if($action == 'save_product'){
        $id = $_POST['id'] ?? '';
        $name = $_POST['name'];
        $category_id = $_POST['category_id'];
        $total_brought = $_POST['total_brought'];
        $price = $_POST['price'];
        $status = isset($_POST['status']) ? 1 : 0;

        // Check duplicate
        $check = $conn->query("SELECT * FROM products WHERE name='$name' AND category_id='$category_id'")->fetch_assoc();
        if($id != ''){
            // Update existing
            $conn->query("UPDATE products SET name='$name', price='$price', total_brought='$total_brought', category_id='$category_id', status='$status' WHERE id='$id'");
            echo 1;
        } else if($check){
            // Update quantity if duplicate
            $cat_qty = $conn->query("SELECT quantity FROM categories WHERE id='$category_id'")->fetch_assoc()['quantity'];
            $current_qty = $check['total_brought'] > 0 ? $check['total_brought'] : $cat_qty;
            $new_qty = $current_qty + $total_brought;
            $conn->query("UPDATE products SET total_brought='$new_qty', price='$price', status='$status' WHERE id='".$check['id']."'");
            echo 2;
        } else {
            // Insert new
            $conn->query("INSERT INTO products (name, price, total_brought, category_id, status) VALUES ('$name','$price','$total_brought','$category_id','$status')");
            echo 1;
        }
        exit;
    }

    if($action == 'restock_product'){
        $id = $_POST['id'];
        // Reset quantity from category if product qty <=0
        $conn->query("UPDATE products p
            JOIN categories c ON c.id = p.category_id
            SET p.status=1, p.total_brought = IF(p.total_brought<=0, c.quantity, p.total_brought)
            WHERE p.id='$id'");
        echo 1;
        exit;
    }
}
?>

<!DOCTYPE html>
<html>
<head>
  <title>Product Management</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap4.min.css" rel="stylesheet">
  <script src="https://code.jquery.com/jquery-3.6.4.min.js"></script>
  <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
  <script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap4.min.js"></script>
</head>
<body style="background:#f7f7f7; font-family:Poppins,sans-serif;">

<div class="container-fluid mt-4">


  <!-- Awaiting Stock Table -->
  <div class="col-lg-12">
    <div class="card shadow-sm border-0">
      <div class="card-header bg-warning text-white d-flex justify-content-between align-items-center">
        <span> Awaiting Stock </span>
        <button class="btn btn-sm btn-info" onclick="window.print()">Print</button>
      </div>
      <div class="card-body" id="printable-area">
        <table class="table table-bordered table-hover" id="awaiting-stock">
          <thead>
            <tr>
              <th>#</th>
              <th>Category</th>
              <th>Product Name</th>
              <th>Total Brought</th>
              <th>Price</th>
              <th>Status</th>
              <th>Action</th>
            </tr>
          </thead>
          <tbody>
            <?php 
            $i = 1;
            $products = $conn->query("SELECT p.*, c.name as category_name, c.quantity as cat_qty 
                FROM products p 
                LEFT JOIN categories c ON c.id = p.category_id 
                WHERE p.status = 0 
                ORDER BY c.name ASC, p.name ASC");
            while($row = $products->fetch_assoc()):
                $total_stock = $row['total_brought'] > 0 ? $row['total_brought'] : $row['cat_qty'];
            ?>
            <tr>
              <td><?php echo $i++; ?></td>
              <td><?php echo $row['category_name'] ?></td>
              <td><?php echo $row['name'] ?></td>
              <td class="text-right"><?php echo $total_stock ?></td>
              <td class="text-right"><?php echo number_format($row['price'],2) ?></td>
              <td><span class="badge badge-danger">Unavailable</span></td>
              <td>
                <button class="btn btn-sm btn-success restock_product" data-id="<?php echo $row['id'] ?>">Restock</button>
              </td>
            </tr>
            <?php endwhile; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>

</div>

<style>
  td{ vertical-align: middle !important; }
  td p { margin:unset; }
  .custom-switch{ cursor: pointer; }
  .custom-switch *{ cursor: pointer; }
  .card-header { font-weight: 600; font-size: 18px; }
  .card { border-radius: 10px; }
  .badge-danger{ background-color:#dc3545; color:#fff; padding:5px 8px; border-radius:5px; }
  .btn-sm{ padding:2px 8px; font-size:13px; }
  .toast-msg{ position: fixed; top: 20px; right: 20px; padding: 12px 20px; background: #28a745; color: #fff; border-radius: 5px; display: none; z-index: 9999; font-weight: 500; }
  .toast-msg.error{ background: #dc3545; }
  #preloader{ position: fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.2); z-index:9998; }
  #preloader::after{ content:""; position:absolute; top:50%; left:50%; transform:translate(-50%, -50%); border:5px solid #f3f3f3; border-top:5px solid #007bff; border-radius:50%; width:40px; height:40px; animation:spin 1s linear infinite;}
  @keyframes spin{ 0%{transform:translate(-50%, -50%) rotate(0deg);} 100%{transform:translate(-50%, -50%) rotate(360deg);} }

  /* PRINT STYLES */
  @media print {
    body * { visibility: hidden; }
    #printable-area, #printable-area * { visibility: visible; }
    #printable-area { position: absolute; left:0; top:0; width:100%; }
    .btn { display: none !important; }
  }
</style>

<script>
$(document).ready(function(){

  $('#awaiting-stock').DataTable();

  $('#manage-product').on('reset',function(){
    $('input:hidden[name=id]').val('')
    $('select').val('').trigger('change')
  });

  $('#manage-product').submit(function(e){
    e.preventDefault();
    start_load();
    $.ajax({
      url: '',
      type: 'POST',
      data: new FormData(this),
      contentType:false,
      processData:false,
      success:function(resp){
        end_load();
        if(resp==1){
          alert_toast("Product successfully added",'success');
          setTimeout(function(){ location.reload(); },1500);
        }else if(resp==2){
          alert_toast("Duplicate found: Quantity updated",'success');
          setTimeout(function(){ location.reload(); },1500);
        }
      }
    });
  });

  // Delegated event for restock
  $('#awaiting-stock').on('click', '.restock_product', function(){
    var id = $(this).data('id');
    if(confirm("Mark this product as available?")){
      start_load();
      $.post('', {action:'restock_product', id:id}, function(resp){
        end_load();
        if(resp==1){
          alert_toast("Product restocked successfully",'success');
          setTimeout(function(){ location.reload(); },1000);
        }
      });
    }
  });

});

// Toast & Preloader functions
function alert_toast(msg,type='success'){
  var toast = $('<div class="toast-msg '+type+'">'+msg+'</div>');
  $('body').append(toast);
  toast.fadeIn(400).delay(1200).fadeOut(400,function(){ $(this).remove(); });
}
function start_load(){ $('body').append('<div id="preloader"></div>'); }
function end_load(){ $('#preloader').remove(); }
</script>
