<?php include('db_connect.php'); ?>

<div class="container-fluid">
  <div class="col-lg-12">
    <div class="row">

      <!-- FORM Panel -->
      <div class="col-md-4">
        <form id="manage-product">
          <div class="card shadow-sm border-0">
            <div class="card-header bg-primary text-white"> Product Form </div>
            <div class="card-body">
              <input type="hidden" name="id">

              <!-- Category -->
              <div class="form-group">
                <label class="control-label">Category</label>
                <select name="category_id" class="custom-select select2" required>
                  <option value=""></option>
                  <?php 
                  $cname = [];
                  $qry = $conn->query("SELECT * FROM categories ORDER BY name ASC");
                  while($row = $qry->fetch_assoc()){
                    $cname[$row['id']] = ucwords($row['name']);
                  ?>
                    <option value="<?php echo $row['id'] ?>"><?php echo $row['name'] ?></option>
                  <?php } ?>
                </select>
              </div>

              <!-- Name -->
              <div class="form-group">
                <label class="control-label">Name</label>
                <input type="text" class="form-control" name="name" required>
              </div>

              <!-- Total Brought -->
              <div class="form-group">
                <label class="control-label">Quantity</label>
                <input type="number" class="form-control text-right" name="total_brought" min="0" required>
              </div>

              <!-- Price -->
              <div class="form-group">
                <label class="control-label">Price</label>
                <input type="number" class="form-control text-right" name="price" min="0" step="0.01" required>
              </div>

              <!-- Status -->
              <div class="form-group">
                <div class="custom-control custom-switch">
                  <input type="checkbox" class="custom-control-input" id="status" name="status" checked value="1">
                  <label class="custom-control-label" for="status">Available</label>
                </div>
              </div>

            </div>
            <div class="card-footer text-center">
              <button class="btn btn-primary">Save</button>
              <button type="reset" class="btn btn-secondary">Cancel</button>
            </div>
          </div>
        </form>
      </div>
      <!-- FORM Panel -->

      <!-- Table Panel -->
      <div class="col-md-8">
        <div class="card shadow-sm border-0">
          <div class="card-header bg-secondary text-white">
            Product List
          </div>
          <div class="card-body">
            <table class="table table-hover" id="product-list">
              <thead>
                <tr>
                  <th>#</th>
                  <th>Category</th>
                  <th>Name</th>
                  <th>Quantity</th>
                  <th>Price</th>
                  <th>Status</th>
                  <th>Action</th>
                </tr>
              </thead>
              <tbody>
                <?php
                $i = 1;
                $products = $conn->query("SELECT * FROM products ORDER BY id ASC");
                while($row = $products->fetch_assoc()):
                ?>
                  <tr>
                    <td><?php echo $i++ ?></td>
                    <td><?php echo isset($cname[$row['category_id']]) ? $cname[$row['category_id']] : 'N/A'; ?></td>
                    <td><?php echo $row['name'] ?></td>
                    <td><?php echo $row['total_brought'] ?></td>
                    <td><?php echo number_format($row['price'],2) ?></td>
                    <td><?php echo $row['status'] == 1 ? 'Available' : 'Unavailable'; ?></td>
                    <td>
                      <button class="btn btn-sm btn-primary edit_product" 
                        data-id="<?php echo $row['id'] ?>"
                        data-category="<?php echo $row['category_id'] ?>"
                        data-name="<?php echo $row['name'] ?>"
                        data-quantity="<?php echo $row['total_brought'] ?>"
                        data-price="<?php echo $row['price'] ?>"
                        data-status="<?php echo $row['status'] ?>">
                        Edit
                      </button>
                      <button class="btn btn-sm btn-danger delete_product" data-id="<?php echo $row['id'] ?>">Delete</button>
                    </td>
                  </tr>
                <?php endwhile; ?>
              </tbody>
            </table>
          </div>
        </div>
      </div>
      <!-- Table Panel -->

    </div>
  </div>
</div>

<!-- Styles -->
<style>
  td{ vertical-align: middle !important; }
  .custom-switch{ cursor:pointer; }
</style>

<!-- Scripts -->
<script>
$(document).ready(function(){
  $('.select2').select2();
  $('#product-list').DataTable(
    {
  paging: false,        // disable pagination
  info: false,          // remove "showing x of y"
  searching: true,      // keep search bar (optional)
  ordering: true        // keep column sorting
}
  );

  // Reset form
  $('#manage-product').on('reset',function(){
    $('input:hidden').val('');
    $('.select2').val('').trigger('change');
  });

  // Save product
  $('#manage-product').submit(function(e){
    e.preventDefault();
    start_load();
    $.ajax({
      url:'ajax.php?action=save_product',
      method:'POST',
      data:new FormData(this),
      cache:false,
      contentType:false,
      processData:false,
      success:function(resp){
        end_load();
        if(resp==1){
          alert_toast("Product successfully added",'success');
          setTimeout(function(){ location.reload(); },1500);
        }else if(resp==2){
          alert_toast("Duplicate found, quantity updated",'success');
          setTimeout(function(){ location.reload(); },1500);
        }
      }
    })
  });

  // Edit product
  $('.edit_product').click(function(){
    var form = $('#manage-product');
    form.get(0).reset();
    form.find("[name='id']").val($(this).attr('data-id'));
    form.find("[name='name']").val($(this).attr('data-name'));
    form.find("[name='total_brought']").val($(this).attr('data-quantity'));
    form.find("[name='price']").val($(this).attr('data-price'));
    form.find("[name='category_id']").val($(this).attr('data-category')).trigger('change');
    form.find("[name='status']").prop('checked', $(this).attr('data-status') == 1);
    $('html, body').animate({scrollTop:0},'fast');
  });

  // Delete product
  $('.delete_product').click(function(){
    if(confirm("Are you sure to delete this product?")){
      var id = $(this).attr('data-id');
      $.post('ajax.php?action=delete_product',{id:id},function(resp){
        if(resp==1){
          alert_toast("Product deleted",'success');
          setTimeout(function(){ location.reload(); },1000);
        }
      });
    }
  });

  // Toast & loader
  function alert_toast(msg,type='success'){
    var toast = $('<div class="toast-msg '+type+'">'+msg+'</div>');
    $('body').append(toast);
    toast.fadeIn(400).delay(1200).fadeOut(400,function(){ $(this).remove(); });
  }

  function start_load(){ $('body').append('<div id="preloader"></div>'); }
  function end_load(){ $('#preloader').remove(); }
});
</script>

<style>
.toast-msg{ position: fixed; top:20px; right:20px; padding:12px 20px; background:#28a745; color:#fff; border-radius:5px; display:none; z-index:9999; font-weight:500; }
.toast-msg.success{ background:#28a745; }
.toast-msg.error{ background:#dc3545; }
#preloader{ position: fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.2); z-index:9998; }
#preloader::after{ content:""; position:absolute; top:50%; left:50%; transform:translate(-50%,-50%); border:5px solid #f3f3f3; border-top:5px solid #007bff; border-radius:50%; width:40px; height:40px; animation:spin 1s linear infinite; }
@keyframes spin{ 0%{transform:translate(-50%,-50%) rotate(0deg);} 100%{transform:translate(-50%,-50%) rotate(360deg);} }
</style>
