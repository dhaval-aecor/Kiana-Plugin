<?php
function add_shipping_address_to_user($request = null)
{
    $params = $request->get_params();
    $header = $request->get_headers();
	if(!isset($header['authorization'][0]) || $header['authorization'][0]=='')
	{
  		$errors['invalid_user'] = 'User is not logedin';
	}
	$token = str_replace('Bearer ', '', $header['authorization'][0]);
	$user_id = validate_token($token);
    // $user_id = $params['user_id'];
    $shipping_addresses = get_user_meta( $user_id ,'kiana_shipping_address',true);
    $new_address_list = array();
    $address = array();
    $errors = array();
    if(empty($shipping_addresses)) {
    	$shipping_addresses = array();
    	$add_id = 1;
    } else {
	    $index = count($shipping_addresses)-1;
	    $add_id = $shipping_addresses[$index]['address_id']+1;
    }
    if(!is_email($params['email'])) {
		$errors['email_invalid'] = 'Invalid email';
	}
	if(!isset($params['email']) || $params['email']==''){
		$errors['email_empty'] = 'Please enter E-mail.';	
	}
	if(!isset($params['first_name']) || $params['first_name']==''){
		$errors['first_name_empty'] = 'Please Enter First Name.';	
	}
	if(!isset($params['last_name']) || $params['last_name']==''){
		$errors['last_name_empty'] = 'Please Enter Last Name.';	
	}
	if(!isset($params['address_1']) || $params['address_1']==''){
		$errors['address_1_empty'] = 'Please Enter Address 1.';	
	}
	if(!isset($params['city']) || $params['city']==''){
		$errors['city_empty'] = 'Please Enter City.';	
	}
	if(!isset($params['state']) || $params['state']==''){
		$errors['state_empty'] = 'Please Enter State.';	
	}
	if(!isset($params['postcode']) || $params['postcode']==''){
		$errors['postcode_empty'] = 'Please Enter Post Code.';	
	}
	if(!isset($params['country']) || $params['country']==''){
		$errors['country_empty'] = 'Please Enter Country.';	
	}
	if(!isset($params['phone']) || $params['phone']==''){
		$errors['phone_empty'] = 'Please Enter Phone.';	
	}
	// var_dump($params['is_default']);
	if(isset($params['is_default'])){
		if($params['is_default']==1 || $params['is_default']==0)
		{
			if($params['is_default']==1){
				$def = true;
			} elseif ($params['is_default']==0) {
				$def = false;
			} else {
				$errors['default_invalid'] = 'Only send true if address is default';	
			}
		} else {
			$errors['default_invalid'] = 'Only send true if address is default';	
		}
	} else {
		$def = false;
	}
	
	if(!empty($errors)){
		$response = new WP_REST_Response( $errors );
		$response->set_status( 422 );
		return $response;
	}
    $address = array(
    	"address_id" => $add_id,
    	"first_name" =>  $params['first_name'],
	    "last_name" =>  $params['last_name'],
	    "address_1" =>  $params['address_1'],
	    "address_2" =>  $params['address_2'],
	    "city" =>  $params['city'],
	    "state" =>  $params['state'],
	    "postcode" =>  $params['postcode'],
	    "country" =>  $params['country'],
	    "email" =>  $params['email'],
	    "phone" =>  $params['phone'],
	    "is_default" => $def,
    );
    if($def==true){
		foreach ($shipping_addresses as $key => $ad) 
		{
			$ad['is_default'] = false;
			array_push($new_address_list, $ad);
    	}
    } else {
    	foreach ($shipping_addresses as $key => $ad) 
		{
			array_push($new_address_list, $ad);
    	}

    }

    array_push($new_address_list, $address);

	if(!empty($new_address_list))
	{
		update_user_meta( $user_id, 'kiana_shipping_address', $new_address_list );

		$data= array(
			'status' => 200,
			'message' => 'Address Added Sucessfully.',
		);
		$response = new WP_REST_Response( $data );
		$response->set_status( 200 );
		return $response;
	}
}

function list_shipping_address_from_user($request){

	$params = $request->get_params();
	$header = $request->get_headers();
	if(!isset($header['authorization'][0]) || $header['authorization'][0]=='')
	{
  		$errors['invalid_user'] = 'User is not logedin';
	}
	$token = str_replace('Bearer ', '', $header['authorization'][0]);
	$user_id = validate_token($token);
	// $user_id = $params['id'];

	$shipping_addresses = get_user_meta( $user_id ,'kiana_shipping_address',true);
	$addresses = array();
	if(empty($shipping_addresses)){
		$data= array(
			'status' => 422,
			'message' => 'No address available.',
		);
		$response = new WP_REST_Response( $data );
		$response->set_status( 422 );
		return $response;	
	}

	foreach ($shipping_addresses as $key => $address) {
		array_push($addresses, $address);
	}

	$data= array(
		'status' => 200,
		'address' => $addresses,
	);
	$response = new WP_REST_Response( $data );
	$response->set_status( 200 );
	return $response;
}

function edit_shipping_address_to_user($request){
	$params = $request->get_params();
	$errors = array();
	$header = $request->get_headers();
	if(!isset($header['authorization'][0]) || $header['authorization'][0]=='')
	{
  		$errors['invalid_user'] = 'User is not logedin';
	}
	$token = str_replace('Bearer ', '', $header['authorization'][0]);
	$user_id = validate_token($token);
	// $user_id = $params['user_id'];
	$add_id = $params['address_id'];
	$shipping_addresses = get_user_meta( $user_id ,'kiana_shipping_address',true);
	$new_address_list = array();
	$add_key = array_search($add_id, array_column($shipping_addresses, 'address_id'));

	if(empty($user_id)) {
		$errors['invalid_user_id'] = 'Invalid UserId';
	}
	if(empty($add_id)) {
		$errors['invalid_address_id'] = 'Invalid Address ID';
	}
	if($add_key==false && $add_key!=0){
		$errors['invalid_address_id'] = 'Invalid Address ID';	
	}

	if(empty($errors)) {

		if(isset($params['first_name']) && $params['first_name']!='')
		{
			$shipping_addresses[$add_key]['first_name'] = $params['first_name'];
		}
		if(isset($params['last_name']) && $params['last_name']!='')
		{
			$shipping_addresses[$add_key]['last_name'] = $params['last_name'];
		}
		if(isset($params['address_1']) && $params['address_1']!='')
		{
			$shipping_addresses[$add_key]['address_1'] = $params['address_1'];
		}
		if(isset($params['address_2']) && $params['address_2']!='')
		{
			$shipping_addresses[$add_key]['address_2'] = $params['address_2'];
		}
		if(isset($params['city']) && $params['city']!='')
		{
			$shipping_addresses[$add_key]['city'] = $params['city'];
		}
		if(isset($params['state']) && $params['state']!='')
		{
			$shipping_addresses[$add_key]['state'] = $params['state'];
		}
		if(isset($params['postcode']) && $params['postcode']!='')
		{
			$shipping_addresses[$add_key]['postcode'] = $params['postcode'];
		}
		if(isset($params['country']) && $params['country']!='')
		{
			$shipping_addresses[$add_key]['country'] = $params['country'];
		}
		if(isset($params['email']) && $params['email']!='')
		{
			$shipping_addresses[$add_key]['email'] = $params['email'];
		}
		if(isset($params['phone']) && $params['phone']!='')
		{
			$shipping_addresses[$add_key]['phone'] = $params['phone'];
		}

		if(isset($params['is_default'])){
			if($params['is_default']==1){
				foreach ($shipping_addresses as $key => $ad) 
				{
					if($ad['address_id'] == $add_id){
						$ad['is_default'] = true;
					} else {
						$ad['is_default'] = false;
					}
					array_push($new_address_list, $ad);
				}
			} elseif ($params['is_default']==0) {
				$shipping_addresses[$add_key]['is_default'] = false;
				foreach ($shipping_addresses as $key => $ad) 
				{
					array_push($new_address_list, $ad);
				}
			}
		} else {
			foreach ($shipping_addresses as $key => $ad) 
			{
				array_push($new_address_list, $ad);
			}
		}

		update_user_meta( $user_id, 'kiana_shipping_address', $new_address_list );

		$data= array(
			'status' => 200,
			'message' => 'Address Edited Sucessfully.',
		);
		$response = new WP_REST_Response( $data );
		$response->set_status( 200 );
		return $response;
	} else {
		$response = new WP_REST_Response( $errors );
		$response->set_status( 422 );
		return $response;
	}

}

function delete_shipping_address_to_user($request){
	$params = $request->get_params();
	$errors = array();
	$header = $request->get_headers();
	if(!isset($header['authorization'][0]) || $header['authorization'][0]=='')
	{
  		$errors['invalid_user'] = 'User is not logedin';
	}
	$token = str_replace('Bearer ', '', $header['authorization'][0]);
	$user_id = validate_token($token);
	// $user_id = $params['user_id'];
	$add_id = $params['address_id'];
	// delete_user_meta( $user_id ,'kiana_shipping_address' );
	$shipping_addresses = get_user_meta( $user_id ,'kiana_shipping_address',true);
	$add_key = array_search($add_id, array_column($shipping_addresses, 'address_id'));

	if(empty($user_id)) {
		$errors['invalid_user_id'] = 'Invalid UserId';
	}
	if(empty($add_id)) {
		$errors['invalid_address_id'] = 'Invalid Address ID';
	}
	if($add_key==false && $add_key!=0){
		$errors['invalid_address_id'] = 'Invalid Address ID';	
	}
	// print_r($shipping_addresses);
	// var_dump(get_user_meta( $user_id ,'kiana_shipping_address',true));
	if(empty($errors)) {

		unset($shipping_addresses[$add_key]);

		$shipping_addresses = array_values($shipping_addresses);

		update_user_meta( $user_id, 'kiana_shipping_address', $shipping_addresses );

		$data= array(
			'status' => 200,
			'message' => 'Address Deleted Sucessfully.',
		);
		$response = new WP_REST_Response( $data );
		$response->set_status( 200 );
		return $response;
	} else {
		$response = new WP_REST_Response( $errors );
		$response->set_status( 422 );
		return $response;
	}

}