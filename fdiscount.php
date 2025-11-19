<?php include('db_connect.php'); ?>

<div class="container-fluid">
    <div class="col-lg-12">
        <div class="row">
            <!-- Discount Form Panel -->
            <div class="col-md-4">
                <form action="" id="manage-discount">
                    <div class="card">
                        <div class="card-header">
                            Set Product Discount
                        </div>
                        <div class="card-body">
                            <input type="hidden" name="id">
                            <div class="form-group">
                                <label class="control-label">Select Product</label>
                                <select name="product_id" id="product_id" class="custom-select select2" required>
                                    <option value="">-- Select Product --</option>
                                    <?php
                                    $products = $conn->query("SELECT * FROM products ORDER BY name ASC");
                                    while($row = $products->fetch_assoc()):
                                    ?>
                                    <option value="<?php echo $row['id'] ?>">
                                        <?php echo $row['name'].' ('.$cname[$row['category_id']].')'; ?>
                                    </option>
                                    <?php endwhile; ?>
                                </select>
                            </div>
                            <div class="form-group">
                                <label class="control-label">Discount (%)</label>
                                <input type="number" class="form-control" name="discount" min="0" max="100" placeholder="Enter discount in %" required>
                            </div>
                        </div>
                        <div class="card-footer text-center">
                            <button class="btn btn-primary">Save Discount</button>
                            <button class="btn btn-secondary" type="button" onclick="$('#manage-discount').get(0).reset()">Cancel</button>
                        </div>
                    </div>
                </form>
            </div>
            <!-- Discount Table Panel -->
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header">
                        <b>Product Discounts</b>
                    </div>
                    <div class="card-body">
                        <table class="table table-hover" id="discount-table">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>Product</th>
                                    <th>Category</th>
                                    <th>Price</th>
                                    <th>Discount (%)</th>
                                    <th>Discounted Price</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                $i = 1;
                                $discounts = $conn->query("SELECT p.*, d.discount 
                                                          FROM products p 
                                                          LEFT JOIN discounts d ON d.product_id = p.id 
                                                          ORDER BY p.name ASC");
                                while($row = $discounts->fetch_assoc()):
                                    $discounted_price = $row['discount'] ? $row['price'] * (1 - $row['discount']/100) : $row['price'];
                                ?>
                                <tr>
                                    <td><?php echo $i++ ?></td>
                                    <td><?php echo $row['name'] ?></td>
                                    <td><?php echo $cname[$row['category_id']] ?></td>
                                    <td><?php echo number_format($row['price'],2) ?></td>
                                    <td><?php echo $row['discount'] ? $row['discount'].'%' : '0%' ?></td>
                                    <td><?php echo number_format($discounted_price,2) ?></td>
                                    <td>
                                        <button class="btn btn-sm btn-primary edit_discount" 
                                                data-id="<?php echo $row['id'] ?>" 
                                                data-product="<?php echo $row['id'] ?>" 
                                                data-discount="<?php echo $row['discount'] ?>">
                                                <i class="fa fa-edit"></i>
                                        </button>
                                        <button class="btn btn-sm btn-danger delete_discount" data-id="<?php echo $row['id'] ?>">
                                                <i class="fa fa-trash-alt"></i>
                                        </button>
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

<script>
$('#manage-discount').submit(function(e){
    e.preventDefault();
    start_load();
    $.ajax({
        url:'ajax.php?action=save_discount',
        data: new FormData($(this)[0]),
        cache:false,
        contentType:false,
        processData:false,
        method:'POST',
        success:function(resp){
            if(resp==1){
                alert_toast("Discount successfully added",'success');
                setTimeout(()=>location.reload(),1500);
            } else if(resp==2){
                alert_toast("Discount successfully updated",'success');
                setTimeout(()=>location.reload(),1500);
            }
        }
    });
});

$('.edit_discount').click(function(){
    var form = $('#manage-discount');
    form.get(0).reset();
    form.find("[name='id']").val($(this).attr('data-id'));
    form.find("[name='product_id']").val($(this).attr('data-product')).trigger('change');
    form.find("[name='discount']").val($(this).attr('data-discount'));
});

$('.delete_discount').click(function(){
    if(confirm("Are you sure you want to delete this discount?")){
        var id = $(this).attr('data-id');
        start_load();
        $.ajax({
            url:'ajax.php?action=delete_discount',
            method:'POST',
            data:{id:id},
            success:function(resp){
                if(resp==1){
                    alert_toast("Discount removed",'success');
                    setTimeout(()=>location.reload(),1500);
                }
            }
        });
    }
});

$('#discount-table').dataTable();
</script>
