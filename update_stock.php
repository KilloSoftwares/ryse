<?php include('db_connect.php'); ?>

<div class="container-fluid">
  <div class="col-lg-12">
    <div class="row">

      <!-- FORM Panel -->
      <div class="col-md-4">
        <form id="manage-category">
          <div class="card shadow-sm border-0">
            <div class="card-header bg-primary text-white"> Category Form </div>
            <div class="card-body">
              <input type="hidden" name="id">

              <!-- Name -->
              <div class="form-group">
                <label class="control-label">Name</label>
                <input type="text" class="form-control" name="name" required>
              </div>

              <!-- Quantity (using description field) -->
              <div class="form-group">
                <label class="control-label">Quantity</label>
                <input type="number" class="form-control text-right" name="quantity" min="0" required>
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
            Category List
          </div>
          <div class="card-body">
            <table class="table table-hover" id="category-list">
              <thead>
                <tr>
                  <th>#</th>
                  <th>Name</th>
                  <th>Quantity</th>
                  <th>Action</th>
                </tr>
              </thead>
              <tbody>
                <?php
                $i = 1;
                $categories = $conn->query("SELECT * FROM categories ORDER BY id ASC");
                while($row = $categories->fetch_assoc()):
                ?>
                  <tr>
                    <td><?php echo $i++ ?></td>
                    <td><?php echo $row['name'] ?></td>
                    <td><?php echo $row['quantity'] ?></td>
                    <td>
                      <button class="btn btn-sm btn-primary edit_category"
                        data-id="<?php echo $row['id'] ?>"
                        data-name="<?php echo $row['name'] ?>"
                        data-description="<?php echo $row['quantity'] ?>">
                        Edit
                      </button>
                      <button class="btn btn-sm btn-danger delete_category" data-id="<?php echo $row['id'] ?>">Delete</button>
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
  td { vertical-align: middle !important; }
</style>

<!-- Scripts -->
<script>
$(document).ready(function(){
  $('#category-list').DataTable(
     {
  paging: false,        // disable pagination
  info: false,          // remove "showing x of y"
  searching: true,      // keep search bar (optional)
  ordering: true        // keep column sorting
}
  );

  // Reset form
  $('#manage-category').on('reset', function(){
    $('input:hidden').val('');
  });

  // Save category
  $('#manage-category').submit(function(e){
    e.preventDefault();
    start_load();
    $.ajax({
      url:'ajax.php?action=save_category',
      method:'POST',
      data:new FormData(this),
      cache:false,
      contentType:false,
      processData:false,
      success:function(resp){
        end_load();
        if(resp==1){
          alert_toast("Category successfully added",'success');
          setTimeout(function(){ location.reload(); },1500);
        } else if(resp==2){
          alert_toast("Category successfully updated",'success');
          setTimeout(function(){ location.reload(); },1500);
        }
      }
    })
  });

  // Edit category
  $('.edit_category').click(function(){
    var form = $('#manage-category');
    form.get(0).reset();
    form.find("[name='id']").val($(this).attr('data-id'));
    form.find("[name='name']").val($(this).attr('data-name'));
    form.find("[name='quantity']").val($(this).attr('data-description'));
    $('html, body').animate({scrollTop:0},'fast');
  });

  // Delete category
  $('.delete_category').click(function(){
    if(confirm("Are you sure to delete this category?")){
      var id = $(this).attr('data-id');
      $.post('ajax.php?action=delete_category', {id:id}, function(resp){
        if(resp==1){
          alert_toast("Category deleted",'success');
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
