<?php

class Customorderupdate{
  	protected $uesrname, $password, $cradential,$crm_url, $offer_details=array(), $product_id=0;

	public function __construct(){
	    $this->uesrname=CRM_API_USERNAME;
	    $this->password=CRM_API_PASSWORD;
	    $this->cradential=base64_encode($this->uesrname.' : '.$this->password);
	    $this->crm_url=CRM_URL;
	 }

  	public function cardupdate($orderid,$cc_payment_type,$cc_number,$cc_expiration_date,$check_cvv){
	    $order_details=$this->getorderdetails($orderid);
	    if($order_details->response_code==100){
	      $authorizaton_details=$this->card_authorizaton($order_details,$cc_payment_type,$cc_number,$cc_expiration_date,$check_cvv);
	      if($authorizaton_details->response_code==100){
	        $order_refund=$this->order_refund($authorizaton_details);
	        if($order_refund->response_code==100){
	          $order_update=$this->new_subscription_order_update($order_details,$authorizaton_details,$this->product_id);
	          if($order_update->response_code==100){
	            $order_cancle=$this->order_cancle($orderid,$this->product_id);
	            if($order_cancle->response_code==100){
	              $note[0]="Card details has been updated.";
	              $note[1]="The new order id is #".$authorizaton_details->order_id;
	              foreach ($note as $value) {
	                $this->add_order_note($orderid,$note);
	              }
	              $response_array = array(
	                                        'response_code' =>  $order_cancle->response_code,
	                                        'update_card_type'=> $cc_payment_type,
	                                        'update_card_number'=> substr_replace($cc_number,'************',0,-4),
	                                        'new_order'=> $authorizaton_details->order_id,
	                                        'message' =>  "Success",
	                                      );
	              return json_encode($response_array);
	            }else{
	              $error_message_new = $order_cancle->error_message;
	              if(empty($order_cancle->error_message))
	              {
	                  $error_message_new = 'We are unable to process your request at this moment. Please try again later.';
	              }
	              $response_array = array(
	                                        'response_code' =>  $order_cancle->response_code,
	                                        'message'   =>  $error_message_new,
	                                      );
	              echo json_encode($response_array);
	            }
	          }else{
	            $error_message_new = $order_update->error_message;
	            if(empty($order_update->error_message))
	            {
	                $error_message_new = 'We are unable to process your request at this moment. Please try again later.';
	            }
	            $response_array = array(
	                                      'response_code' =>  $order_update->response_code,
	                                      'message'   =>  $error_message_new,
	                                    );
	            echo json_encode($response_array);
	          }
	        }else{
	          $error_message_new = $order_refund->error_message;
	          if(empty($order_refund->error_message))
	          {
	              $error_message_new = 'We are unable to process your request at this moment. Please try again later.';
	          }
	          $response_array = array(
	                                    'response_code' =>  $order_refund->response_code,
	                                    'message'   =>  $error_message_new,
	                                  );
	          echo json_encode($response_array);
	        }
	      }else{
	        $error_message_new = $authorizaton_details->error_message;
	        if(empty($authorizaton_details->error_message))
	        {
	            $error_message_new = 'We are unable to process your request at this moment. Please try again later.';
	        }
	        $response_array = array(
	                                  'response_code' =>  $authorizaton_details->response_code,
	                                  'message'   =>  $error_message_new,
	                                );
	        return json_encode($response_array);
	      }
	    }else{
	      $error_message_new = $order_details->error_message;
	      if(empty($order_details->error_message))
	      {
	          $error_message_new = 'We are unable to process your request at this moment. Please try again later.';
	      }
	      $response_array = array(
	                                'response_code' =>  $order_details->response_code,
	                                'message'   =>  $error_message_new,
	                              );
	      return json_encode($response_array);
	    }
  	}

  	public function getorderdetails($orderid){
	    $alldata=array(
	                    "order_id"=>$orderid,
	                  );
	    $postdaat=json_encode($alldata);

	    $curl = curl_init();

	    curl_setopt_array($curl, array(
	      CURLOPT_URL => $this->crm_url."/api/v1/order_view",
	      CURLOPT_RETURNTRANSFER => true,
	      CURLOPT_ENCODING => "",
	      CURLOPT_MAXREDIRS => 10,
	      CURLOPT_TIMEOUT => 30,
	      CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
	      CURLOPT_CUSTOMREQUEST => "POST",
	      CURLOPT_POSTFIELDS => $postdaat,
	      CURLOPT_HTTPHEADER => array(
	        "authorization: Basic ".$this->cradential,
	        "cache-control: no-cache",
	        "content-type: application/json",
	      ),
	    ));

	    $response = curl_exec($curl);
	    $err = curl_error($curl);

	    curl_close($curl);

	    if($err){
	      return "cURL Error #:" . $err; exit();
	    }else{
	      return json_decode($response);
	    }
  	}

  	public function card_authorizaton($order_details,$cc_payment_type,$cc_number,$cc_expiration_date,$check_cvv){
	    foreach ($order_details->products as $value) {
	      $a=$value->offer;
	      $this->offer_details["offer_id"]=$a->id;
	      $this->offer_details["product_id"]=$value->product_id;
	      $this->product_id=$value->product_id;
	      $b=$value->billing_model;
	      $this->offer_details["billing_model_id"]=$b->id;
	      $this->offer_details["quantity"]=$value->product_qty;
	      $this->offer_details["step_num"]=$value->step_number;
	      $this->offer_details["trial"]=array("product_id"=>$value->product_id);
	    }
	    $alldata=array(
	                    "firstName"=>$order_details->first_name,
	                    "lastName"=>$order_details->last_name,
	                    "billingFirstName"=>$order_details->billing_first_name,
	                    "billingLastName"=>$order_details->billing_last_name,
	                    "billingAddress1"=>$order_details->billing_street_address,
	                    "billingAddress2"=>$order_details->billing_street_address2,
	                    "billingCity"=>$order_details->billing_city,
	                    "billingState"=>$order_details->billing_state,
	                    "billingZip"=>$order_details->billing_postcode,
	                    "billingCountry"=>$order_details->billing_country,
	                    "phone"=>$order_details->customers_telephone,
	                    "email"=>$order_details->email_address,
	                    "creditCardNumber"=>$cc_number,
	                    "expirationDate"=>$cc_expiration_date,
	                    "CVV"=>$check_cvv,
	                    "creditCardType"=>$cc_payment_type,
	                    "shippingId"=>$order_details->shipping_id,
	                    "tranType"=>"Sale",
	                    "ipAddress"=>$order_details->ip_address,
	                    "campaignId"=>$order_details->campaign_id,
	                    "productId"=>$this->product_id,
	                    "product_qty_".$this->product_id=>$this->offer_details["quantity"],
	                    "dynamic_product_price_".$this->product_id=>"1.00",
	                    "offers"=>array(
	                                  $this->offer_details  
	                              ),
	                    "billingSameAsShipping"=>"NO",
	                    "shippingAddress1"=>$order_details->shipping_street_address,
	                    "shippingAddress2"=>$order_details->shipping_street_address2,
	                    "shippingCity"=>$order_details->shipping_city,
	                    "shippingState"=>$order_details->shipping_state,
	                    "shippingZip"=>$order_details->shipping_postcode,
	                    "shippingCountry"=>$order_details->shipping_country,
	                    "three_d_redirect_url"=>1,
	                  );
	    $postdaat=json_encode($alldata);

	    $curl = curl_init();

	    curl_setopt_array($curl, array(
	      CURLOPT_URL => $this->crm_url."/api/v1/new_order",
	      CURLOPT_RETURNTRANSFER => true,
	      CURLOPT_ENCODING => "",
	      CURLOPT_MAXREDIRS => 10,
	      CURLOPT_TIMEOUT => 30,
	      CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
	      CURLOPT_CUSTOMREQUEST => "POST",
	      CURLOPT_POSTFIELDS => $postdaat,
	      CURLOPT_HTTPHEADER => array(
	        "authorization: Basic ".$this->cradential,
	        "cache-control: no-cache",
	        "content-type: application/json",
	      ),
	    ));

	    $response = curl_exec($curl);
	    $err = curl_error($curl);

	    curl_close($curl);

	    if($err){
	      return "cURL Error #:" . $err;
	    }else{
	      return json_decode($response);
	    }
  	}

  	public function order_refund($authorizaton_details){
	    $alldata=array(
	                    "order_id"=>$authorizaton_details->order_id,
	                    "amount"=>$authorizaton_details->orderTotal,
	                    "keep_recurring"=>"1",
	                    "note_id"=>1
	                  );
	    $postdaat=json_encode($alldata);

	    $curl = curl_init();

	    curl_setopt_array($curl, array(
	      CURLOPT_URL => $this->crm_url."/api/v1/order_refund",
	      CURLOPT_RETURNTRANSFER => true,
	      CURLOPT_ENCODING => "",
	      CURLOPT_MAXREDIRS => 10,
	      CURLOPT_TIMEOUT => 30,
	      CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
	      CURLOPT_CUSTOMREQUEST => "POST",
	      CURLOPT_POSTFIELDS => $postdaat,
	      CURLOPT_HTTPHEADER => array(
	        "authorization: Basic ".$this->cradential,
	        "cache-control: no-cache",
	        "content-type: application/json",
	      ),
	    ));

	    $response = curl_exec($curl);
	    $err = curl_error($curl);

	    curl_close($curl);

	    if($err){
	      return "cURL Error #:" . $err; exit();
	    }else{
	      return json_decode($response);
	    }
  	}

  	public function new_subscription_order_update($order_details,$authorizaton_details,$product_id){
	    $alldata=array(
	                    "order_id"=> $authorizaton_details->order_id,
	                    "product_id"=> $product_id,
	                    "new_recurring_product_id"=> $order_details->next_subscription_product_id,
	                    "new_recurring_date"=> $order_details->recurring_date,
	                    "preserve_new_recurring_price"=> 1
	                  );
	    $postdaat=json_encode($alldata);

	    $curl = curl_init();

	    curl_setopt_array($curl, array(
	      CURLOPT_URL => $this->crm_url."/api/v1/subscription_order_update",
	      CURLOPT_RETURNTRANSFER => true,
	      CURLOPT_ENCODING => "",
	      CURLOPT_MAXREDIRS => 10,
	      CURLOPT_TIMEOUT => 30,
	      CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
	      CURLOPT_CUSTOMREQUEST => "POST",
	      CURLOPT_POSTFIELDS => $postdaat,
	      CURLOPT_HTTPHEADER => array(
	        "authorization: Basic ".$this->cradential,
	        "cache-control: no-cache",
	        "content-type: application/json",
	      ),
	    ));

	    $response = curl_exec($curl);
	    $err = curl_error($curl);

	    curl_close($curl);

	    if($err){
	      return "cURL Error #:" . $err; exit();
	    }else {
	      return json_decode($response);
	    }
  	}

  	public function order_cancle($orderid,$product_id){
	    $alldata=array(
	                    "order_id"=> $orderid,
	                    "product_id"=> $product_id,
	                    "status"=> "stop"
	                  );
	    $postdaat=json_encode($alldata);

	    $curl = curl_init();

	    curl_setopt_array($curl, array(
	      CURLOPT_URL => $this->crm_url."/api/v1/subscription_order_update",
	      CURLOPT_RETURNTRANSFER => true,
	      CURLOPT_ENCODING => "",
	      CURLOPT_MAXREDIRS => 10,
	      CURLOPT_TIMEOUT => 30,
	      CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
	      CURLOPT_CUSTOMREQUEST => "POST",
	      CURLOPT_POSTFIELDS => $postdaat,
	      CURLOPT_HTTPHEADER => array(
	        "authorization: Basic ".$this->cradential,
	        "cache-control: no-cache",
	        "content-type: application/json",
	      ),
	    ));

	    $response = curl_exec($curl);
	    $err = curl_error($curl);

	    curl_close($curl);
	    if($err){
	      return "cURL Error #:" . $err; exit();
	    }else {
	      return json_decode($response);
	    }
  	}

  	public function add_order_note($orderid,$note){
	    $ord_dtls[$orderid]=array(
	                                "notes"=> $note
	                              );
	    $alldata=array(
	                    "order_id"=>$ord_dtls
	                  );
	    $postdaat=json_encode($alldata);

	    $curl = curl_init();

	    curl_setopt_array($curl, array(
	      CURLOPT_URL => $this->crm_url."/api/v1/order_update",
	      CURLOPT_RETURNTRANSFER => true,
	      CURLOPT_ENCODING => "",
	      CURLOPT_MAXREDIRS => 10,
	      CURLOPT_TIMEOUT => 30,
	      CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
	      CURLOPT_CUSTOMREQUEST => "POST",
	      CURLOPT_POSTFIELDS => $postdaat,
	      CURLOPT_HTTPHEADER => array(
	        "authorization: Basic ".$this->cradential,
	        "cache-control: no-cache",
	        "content-type: application/json",
	      ),
	    ));

	    $response = curl_exec($curl);
	    $err = curl_error($curl);

	    curl_close($curl);

	    if($err){
	      return "cURL Error #:" . $err; exit();
	    }else{
	      return json_decode($response);
	    }
  	}

  	public function subscription_order_update($order_id,$existing_product_ID,$next_rebill_product,$recurring_date,$new_recurring_price,$is_change_date){
	    if($is_change_date==1){
	      $alldata=array(
	            "order_id"=> $order_id,
	            "product_id"=> $existing_product_ID,
	            "new_recurring_date"=> $recurring_date,
	          );
	    }else{
	      if(empty($existing_product_ID) && empty($next_rebill_product) && empty($new_recurring_price)){
	        $order_details=$this->getorderdetails($order_id);
	        foreach ($order_details->products as $value) {
	            $this->product_id=$value->product_id;
	            $next_subscription_product_price=$value->next_subscription_product_price;
	        }
	        $existing_product_ID=$this->product_id;
	        $next_rebill_product=$order_details->next_subscription_product_id;
	        $new_recurring_price=number_format(($next_subscription_product_price-(($next_subscription_product_price*25)/100)),2);
	      }
	      $alldata=array(
	                      "order_id"=> $order_id,
	                      "product_id"=> $existing_product_ID,
	                      "new_recurring_product_id"=> $next_rebill_product,
	                      "new_recurring_price"=> $new_recurring_price,
	                      "preserve_new_recurring_price"=> 1
	                    );
	    }

	    $postdaat=json_encode($alldata);

	    $curl = curl_init();

	    curl_setopt_array($curl, array(
	      CURLOPT_URL => $this->crm_url."/api/v1/subscription_order_update",
	      CURLOPT_RETURNTRANSFER => true,
	      CURLOPT_ENCODING => "",
	      CURLOPT_MAXREDIRS => 10,
	      CURLOPT_TIMEOUT => 30,
	      CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
	      CURLOPT_CUSTOMREQUEST => "POST",
	      CURLOPT_POSTFIELDS => $postdaat,
	      CURLOPT_HTTPHEADER => array(
	        "authorization: Basic ".$this->cradential,
	        "cache-control: no-cache",
	        "content-type: application/json",
	      ),
	    ));

	    $result = curl_exec($curl);
	    $err = curl_error($curl);

	    curl_close($curl);

	    if($err){
	      return "cURL Error #:" . $err; exit();
	    }else{
	      $response=json_decode($result);
	      if($response->response_code==100){
	        $response->odr_id=$order_id;
	        if($is_change_date==1){
	          $note="Subscription date has been changed.";
	        }else{
	          $note="Subscription price has been changed.";
	        }
	        $this->add_order_note($order_id,$note);
	        return json_encode($response);
	      }else{
	        $error_message_new = $response->error_message;
	        if(empty($response->error_message))
	        {
	          $error_message_new = 'We are unable to process your request at this moment. Please try again later.';
	        }
	        $response_array = array(
	                                  'response_code' =>  $response->response_code,
	                                  'message' =>  $error_message_new,
	                                );
	        return json_encode($response_array);
	      }
	    }
	}
}

?>