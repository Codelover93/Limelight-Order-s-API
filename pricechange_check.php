<?php
require_once dirname(__DIR__) . DIRECTORY_SEPARATOR . 'member' . DIRECTORY_SEPARATOR . 'Customorderupdate.php';

$order_details = new Customorderupdate();

if(isset($_POST))
{
	$orderid=$_POST['order_id'];
	
	$orderdetails= $order_details->getorderdetails($orderid);
	$employeeNotes=$orderdetails->employeeNotes;
	$csp_string= 'Subscription price has been changed';
	$csp=0;
	foreach($employeeNotes as $value){
	    if (strpos($value, $csp_string) !== false)
	    {
	      $csp++;
	    }
	}
	if($csp>0){
		echo 1;
	}else{
		echo 0;
	}
}

