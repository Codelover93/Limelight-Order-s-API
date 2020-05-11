<?php

require_once dirname(__DIR__) . DIRECTORY_SEPARATOR . 'member' . DIRECTORY_SEPARATOR . 'Customorderupdate.php';

if(isset($_POST)){

	$orderid=$_POST['order_id'];
	$cc_payment_type=$_POST['creditCardType'];
	$cc_number=$_POST['creditCardNumber'];
	$cc_expiration_date=$_POST['expmonth']."".$_POST['expyear'];
	$check_cvv=$_POST['CVV'];

	$card_updates = new Customorderupdate();
	$message= $card_updates->cardupdate($orderid,$cc_payment_type,$cc_number,$cc_expiration_date,$check_cvv);
	echo $message;
}

