<?php
ob_start();
$action = $_GET['action'];
include 'admin_class.php';
$crud = new Action();
if($action == 'login'){
	$login = $crud->login();
	if($login)
		echo $login;
}
if($action == 'login2'){
	$login = $crud->login2();
	if($login)
		echo $login;
}
if($action == 'logout'){
	$logout = $crud->logout();
	if($logout)
		echo $logout;
}
if($action == 'logout2'){
	$logout = $crud->logout2();
	if($logout)
		echo $logout;
}
if($action == 'save_user'){
	$save = $crud->save_user();
	if($save)
		echo $save;
}
if($action == 'delete_user'){
	$save = $crud->delete_user();
	if($save)
		echo $save;
}
if($action == 'signup'){
	$save = $crud->signup();
	if($save)
		echo $save;
}
if($action == 'update_account'){
	$save = $crud->update_account();
	if($save)
		echo $save;
}
if($action == "save_settings"){
	$save = $crud->save_settings();
	if($save)
		echo $save;
}
if($action == "save_category"){
	$save = $crud->save_category();
	if($save)
		echo $save;
}
if($action == "delete_category"){
	$delete = $crud->delete_category();
	if($delete)
		echo $delete;
}
if($action == "save_product"){
	$save = $crud->save_product();
	if($save)
		echo $save;
}
if($action == "delete_product"){
	$delete = $crud->delete_product();
	if($delete)
		echo $delete;
}
if($action == "restock_product"){
    $restock = $crud->restock_product();
    if($restock) echo $restock;
}


if($action == "save_order"){
	// Generate auto order number
$prefix = "ELIAM"; // you can change this (e.g., INV-, POS-)
$date   = date("Ymd"); // adds today's date
$rand   = sprintf("%04d", mt_rand(1,9999)); // random 4-digit

$order_no = $prefix.$date.$rand;

	$save = $crud->save_order();
	if($save)
		echo $save;
}

if($action == "delete_order"){
	$delete = $crud->delete_order();
	if($delete)
		echo $delete;
}
ob_end_flush();
?>
