<?php

function add_product_to_cart($request){
	// ini_set('display_errors', 1);
	// ini_set('display_startup_errors', 1);
	// error_reporting(E_ALL);
	
	global $woocommerce;
	include_once WC_ABSPATH . 'includes/wc-cart-functions.php';
	include_once WC_ABSPATH . 'includes/wc-notice-functions.php';
	include_once WC_ABSPATH . 'includes/wc-template-hooks.php';
	
	$params = $request->get_params();
	$header = $request->get_headers();
	if(!isset($header['authorization'][0]) || $header['authorization'][0]=='')
	{
  		$errors['invalid_user'] = 'User is not logedin';
	}
	$token = str_replace('Bearer ', '', $header['authorization'][0]);
	$user_id = validate_token($token);
	// $user_id = $params['user_id'];
	$product_id = $params['product_id'];
	$quantity = $params['quantity'];
	
	$persistent_cart = get_user_meta( $user_id, '_woocommerce_persistent_cart_1', true );

	$existing_cart_key = '';
	if(empty($persistent_cart)){
		$persistent_cart = array();
		$persistent_cart['cart'] = array();
	} elseif (empty($persistent_cart['cart']))  {
		$persistent_cart = array();
		$persistent_cart['cart'] = array();
	}
	else {
		$existing_cart_key = array_search($product_id, array_column($persistent_cart['cart'], 'variation_id','key'));
		$new_persistent_cart = $persistent_cart;
	}
	
	if($existing_cart_key) {
		$new_persistent_cart['cart'][$existing_cart_key]['quantity'] += $quantity;
		if(!empty($new_persistent_cart))
		{
			update_user_meta( $user_id, '_woocommerce_persistent_cart_1', $new_persistent_cart );

			$data= array(
				'status' => 200,
				'cart' => "Added to cart Sucessfully",
			);
			$response = new WP_REST_Response( $data );
			$response->set_status( 200 );
			return $response;
		}

	} else {
		WC()->init();
		if ( null === WC()->session ) {
		    $session_class = apply_filters( 'woocommerce_session_handler', 'WC_Session_Handler' );
		    WC()->session = new $session_class();
		    WC()->session->init();
		}
		if ( null === WC()->customer ) {
		    WC()->customer = new WC_Customer( $user_id, true );
		}
		if ( null === WC()->cart ) {
		    WC()->cart = new WC_Cart();
			WC()->cart->empty_cart();
		}
		$key = WC()->cart->add_to_cart( $product_id, $quantity );

		foreach (WC()->cart->cart_contents as $key => $value) {
			unset($value['data']);
			$action = array(
				'action' => 'etheme_svp_cart',
			);
			$item_data = $value;
			$data_arr = array_merge($action,$item_data);
			$cart_data[$key] = $data_arr;
			if(!empty($persistent_cart['cart'])){
				$new_cart = array_merge($persistent_cart['cart'], $cart_data);
			} else {
				$new_cart = $cart_data;
			}
		}
		
		$new_persistent_cart['cart'] = $new_cart;
		// print_r(WC()->cart);
		// print_r($new_persistent_cart);
		// die();
		if(!empty($new_persistent_cart))
		{
			update_user_meta( $user_id, '_woocommerce_persistent_cart_1', $new_persistent_cart );
			wc()->session->destroy_session();
			wp_clear_auth_cookie();
			$data= array(
				'status' => 200,
				'cart' => "Added to cart Sucessfully",
			);
			$response = new WP_REST_Response( $data );
			$response->set_status( 200 );
			return $response;
		}
	}
	
}

function get_cart_list($request)
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
	$cart_data = get_user_meta( $user_id ,'_woocommerce_persistent_cart_1',true );
	/*print_r($cart_data);
	die()*/;
	if(!empty($cart_data)){
		/*foreach ($cart_data['cart'] as $key => $item) {
			print_r($item);
		}
		die();*/
		$data= array(
			'status' => 200,
			'cart' => $cart_data['cart'],
		);
		$response = new WP_REST_Response( $data );
		$response->set_status( 200 );
		return $response;
	} else {
		$data= array(
			'status' => 422,
			'cart' => "Cart Is Empty",
		);
		$response = new WP_REST_Response( $data );
		$response->set_status( 422 );
		return $response;
	}
}

function update_cart_item($request)
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
	$cart_key = $params['cart_key'];
	$new_qty = $params['qty'];
	$cart_data = get_user_meta( $user_id ,'_woocommerce_persistent_cart_1',true );
	if(array_key_exists($cart_key, $cart_data['cart'])){
		$product = wc_get_product( $cart_data['cart'][$cart_key]['variation_id'] );
		$price = $product->get_price();
		$cart_data['cart'][$cart_key]["quantity"] = $new_qty;
		$cart_data['cart'][$cart_key]["line_subtotal"] = $price*$new_qty;
		$cart_data['cart'][$cart_key]["line_total"] = $price*$new_qty;
		
		update_user_meta( $user_id, '_woocommerce_persistent_cart_1', $cart_data );

		$data= array(
			'status' => 200,
			'cart' => "Cart Updated Sucessfully",
		);
		$response = new WP_REST_Response( $data );
		$response->set_status( 200 );
		return $response;
	} else {
		$data= array(
			'status' => 422,
			'cart' => "Specified Cart is not available",
		);
		$response = new WP_REST_Response( $data );
		$response->set_status( 422 );
		return $response;
	}
}
function delete_cart_item($request)
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
	$cart_key = $params['cart_key'];

	$cart_data = get_user_meta( $user_id ,'_woocommerce_persistent_cart_1',true );
	if(array_key_exists($cart_key, $cart_data['cart'])){
		
		unset($cart_data['cart'][$cart_key]);
		update_user_meta( $user_id, '_woocommerce_persistent_cart_1', $cart_data );

		$data= array(
			'status' => 200,
			'cart' => "Cart item deleted sucessfully",
		);
		$response = new WP_REST_Response( $data );
		$response->set_status( 200 );
		return $response;
	} else {
		$data= array(
			'status' => 422,
			'cart' => "Specified Cart is not available",
		);
		$response = new WP_REST_Response( $data );
		$response->set_status( 422 );
		return $response;
	}
}