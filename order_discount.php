<?php
require_once dirname(__DIR__) . DIRECTORY_SEPARATOR . 'member' . DIRECTORY_SEPARATOR . 'Customorderupdate.php';

$order_updates = new Customorderupdate();

if(isset($_POST))
{
	$order_id=$_POST['order_id'];
	$existing_product_ID=$_POST['existing_product_ID'];
	$next_rebill_product=$_POST['next_rebill_product'];
	$recurring_date=$_POST['recurring_date'];
	$new_recurring_price=$_POST['new_recurring_price'];
	if(isset($_POST['is_change_date'])){
		$is_change_date=$_POST['is_change_date'];
	}else{
		$is_change_date=0;
	}
	if($is_change_date==1){
		$start_date = strtotime($_POST['old_subsricption_date']); 
		$end_date = strtotime($_POST['recurring_date']);
		if((($end_date - $start_date)/60/60/24)<=45){
			$existing_product_ID=$_POST['existing_product_ID'];
			$next_rebill_product=$_POST['next_rebill_product'];
			$recurring_date=$_POST['recurring_date'];
			$new_recurring_price=$_POST['new_recurring_price'];

			$message= $order_updates->subscription_order_update($order_id,$existing_product_ID,$next_rebill_product,$recurring_date,$new_recurring_price,$is_change_date);
			echo $message;
		}else{
			$error_message_new = 'Please select a valid number of days.';
	   		$response_array = array(
	   								'response_code'	=>	420,
	   								'message'	=>	$error_message_new,
	   							);
	   		echo json_encode($response_array);
		} 
	}else{
		$message= $order_updates->subscription_order_update($order_id,$existing_product_ID,$next_rebill_product,$recurring_date,$new_recurring_price,$is_change_date);
		echo $message;
	}
}

