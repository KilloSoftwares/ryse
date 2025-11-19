<?php
include 'db_connect.php';
include 'admin_class.php';
$crud = new Action();
?>

<!DOCTYPE html>
<html>
<head>
  <title>Product Management & Restock History</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap4.min.css" rel="stylesheet">
  <script src="https://code.jquery.com/jquery-3.6.4.min.js"></script>
  <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
  <script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap4.min.js"></script>
</head>
<body style="background:#f7f7f7; font-family:Poppins,sans-serif;">

<div class="container-fluid mt-4">

  <!-- Product Form -->
  <div class="col-lg-12 mb-4">
    <div class="card shadow-sm border-0">
      <div class="card-header bg-primary text-white"> Add / Update Product </div>
      <div class="card-body">
        <form id="manage-product">
          <input type="hidden" name="id">
          <input type="hidden" name="action" value="save_product">

          <div class="form-group">
            <label>Category</label>
            <select name="category_id" class="custom-select select2" required>
              <option value=""></option>
              <?php 
                $qry = $conn->query("SELECT * FROM categories ORDER BY name ASC");
                while($row=$qry->fetch_assoc()):
              ?>
              <option value="<?php echo $row['id'] ?>"><?php echo $row['name'] ?></option>
              <?php endwhile; ?>
            </select>
          </div>

          <div class="form-group">
            <label>Name</label>
            <input type="text" class="form-control" name="name" required>
          </div>

          <div class="form-group">
            <label>Total Brought</label>
            <input type="number" class="form-control text-right" name="total_brought" min="0" required>
          </div>

          <div class="form-group">
            <label>Price</label>
            <input type="number" class="form-control text-right" name="price" min="0" step="0.01" required>
          </div>

          <div class="form-group">
            <div class="custom-control custom-switch">
              <input type="checkbox" class="custom-control-input" id="status" name="status" checked value="1">
              <label class="custom-control-label" for="status">Available</label>
            </div>
            <small class="text-muted">Uncheck if unavailable</small>
          </div>

          <div class="text-center">
            <button type="submit" class="btn btn-primary"> Save</button>
            <button type="reset" class="btn btn-secondary"> Cancel</button>
          </div>
        </form>
      </div>
    </div>
  </div>

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
            $products = $conn->query("SELECT p.*, c.name as category_name 
                FROM products p 
                LEFT JOIN categories c ON c.id = p.category_id 
                WHERE p.status = 0 
                ORDER BY c.name ASC, p.name ASC");
            while($row = $products->fetch_assoc()):
            ?>
            <tr>
              <td><?php echo $i++; ?></td>
              <td><?php echo $row['category_name'] ?></td>
              <td><?php echo $row['name'] ?></td>
              <td class="text-right"><?php echo $row['total_brought'] ?></td>
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

  <!-- Restock History Table -->
  <div class="col-lg-12 mt-4">
    <div class="card shadow-sm border-0">
      <div class="card-header bg-success text-white d-flex justify-content-between align-items-center">
        <span>Restock History</span>
        <button class="btn btn-sm btn-info" onclick="window.print()">Print</button>
      </div>
      <div class="card-body" id="restock-printable">
        <table class="table table-bordered table-hover" id="restock-history">
          <thead>
            <tr>
              <th>#</th>
              <th>Product Name</th>
              <th>Category</th>
              <th>Restocked By</th>
              <th>Date Restocked</th>
            </tr>
          </thead>
          <tbody>
            <?php 
            $i = 1;
            $history = $conn->query("
              SELECT p.*, c.name as category_name, u.name as restocker_name
              FROM products p
              LEFT JOIN categories c ON p.category_id = c.id
              LEFT JOIN users u ON p.restocked_by = u.id
              WHERE p.status = 1 AND p.date_restocked IS NOT NULL
              ORDER BY p.date_restocked DESC
            ");
            while($row = $history->fetch_assoc()):
            ?>
            <tr>
              <td><?php echo $i++; ?></td>
              <td><?php echo $row['name']; ?></td>
              <td><?php echo $row['category_name']; ?></td>
              <td><?php echo $row['restocker_name']; ?></td>
              <td><?php echo date('d-M-Y H:i', strtotime($row['date_restocked'])); ?></td>
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
  .custom-switch{ cursor: pointer; }
  .card-header { font-weight: 600; font-size: 18px; }
  .card { border-radius: 10px; }
  .badge-danger{ background-color:#dc3545; color:#fff; padding:5px 8px; border-radius:5px; }
  .btn-sm{ padding:2px 8px; font-size:13px; }
  .toast-msg{ position: fixed; top: 20px; right: 20px; padding: 12px 20px; background: #28a745; color: #fff; border-radius: 5px; display: none; z-index: 9999; font-weight: 500; }
  .toast-msg.error{ background: #dc3545; }
  #preloader{ position: fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.2); z-index:9998; }
  #preloader::after{ content:""; position:absolute; top:50%; left:50%; transform:translate(-50%, -50%); border:5px solid #f3f3f3; border-top:5px solid #007bff; border-radius:50%; width:40px; height:40px; animation:spin 1s linear infinite;}
  @keyframes spin{ 0%{transform:translate(-50%, -50%) rotate(0deg);} 100%{transform:translate(-50%, -50%) rotate(360deg);} }

  @media print {
    body * { visibility: hidden; }
    #printable-area, #printable-area * { visibility: visible; }
    #restock-printable, #restock-printable * { visibility: visible; }
    #printable-area, #restock-printable { position: absolute; left:0; top:0; width:100%; }
    .btn { display: none !important; }
  }
</style>

<script>
$(document).ready(function(){

  $('#awaiting-stock, #restock-history').DataTable();

  $('#manage-product').on('reset',function(){
    $('input:hidden[name=id]').val('')
    $('select').val('').trigger('change')
  });

  // Save Product
  $('#manage-product').submit(function(e){
    e.preventDefault();
    start_load();
    $.ajax({
      url: 'ajax.php?action=save_product',
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
        } else {
          alert_toast("Error saving product",'error');
        }
      }
    });
  });

  // Restock Product
  $('#awaiting-stock').on('click', '.restock_product', function(){
    var id = $(this).data('id');
    if(confirm("Mark this product as available?")){
      start_load();
      $.post('ajax.php?action=restock_product', {id:id}, function(resp){
        end_load();
        if(resp==1){
          alert_toast("Product restocked successfully",'success');
          setTimeout(function(){ location.reload(); },1000);
        }
      });
    }
  });

});

// Toast & Preloader
function alert_toast(msg,type='success'){
  var toast = $('<div class="toast-msg '+type+'">'+msg+'</div>');
  $('body').append(toast);
  toast.fadeIn(400).delay(1200).fadeOut(400,function(){ $(this).remove(); });
}
function start_load(){ $('body').append('<div id="preloader"></div>'); }
function end_load(){ $('#preloader').remove(); }
</script>
