<?php

require_once(KIAPIINC.'api_endpoints/login-register-endpoints.php');
require_once(KIAPIINC.'api_endpoints/prodcut-filter-endpoints.php');
// require_once(KIAPIINC.'api_endpoints/prodcut-filter-endpoints_dumy.php');
require_once(KIAPIINC.'api_endpoints/wishlist-endpoints.php');
require_once(KIAPIINC.'api_endpoints/cart-endpoints.php');
require_once(KIAPIINC.'api_endpoints/refund-exchange-endpoints.php');
require_once(KIAPIINC.'api_endpoints/shipping-address-endpoints.php');
require_once(KIAPIINC.'cmb2/init.php');

// ini_set('display_errors', 1);
// ini_set('display_startup_errors', 1);
// error_reporting(E_ALL);

add_action( 'rest_api_init', function () {

  /*Login Api and registration APIs*/
  register_rest_route( 'kianaapi/v1', '/login/', array(
    'methods' => 'POST',
    'callback' => 'login_user',
  ) );
  register_rest_route( 'kianaapi/v1', '/register/', array(
    'methods' => 'POST',
    'callback' => 'register_user',
  ));
  register_rest_route( 'kianaapi/v1', '/resend_activation_otp/', array(
    'methods' => 'POST',
    'callback' => 'resend_activation_otp',
  ));
  register_rest_route( 'kianaapi/v1', '/logout', array(
    'methods' => 'GET',
    'callback' => 'logout_from_mobile',
  ));
  register_rest_route( 'kianaapi/v1', '/active_user/', array(
    'methods' => 'POST',
    'callback' => 'active_user',
  ));
  register_rest_route( 'kianaapi/v1', '/forget_pass/', array(
    'methods' => 'POST',
    'callback' => 'forget_pass',
  ));
  register_rest_route( 'kianaapi/v1', '/varifyotp/', array(
    'methods' => 'POST',
    'callback' => 'varify_otp',
  ));
  register_rest_route( 'kianaapi/v1', '/changepassword/', array(
    'methods' => 'POST',
    'callback' => 'change_password',
  ));
  register_rest_route('kianaapi/v1', '/user_profile/', array(
        'methods' => 'GET',
        'callback' => 'user_profile_detail',
    ));
  register_rest_route( 'kianaapi/v1', '/user_profile/update/', array(
    'methods' => 'POST',
    'callback' => 'update_profile',
  ) );


  /*Test Notification*/
  register_rest_route( 'kianaapi/v1', '/push_notification_android/', array(
    'methods' => 'GET',
    'callback' => 'push_notification_android',
  ));



  /* */
  register_rest_route('kianaapi/v1', '/mobile_slider', array(
        'methods' => 'GET',
        'callback' => 'get_slider_images',
    ));
  register_rest_route('kianaapi/v1', '/notification_setting', array(
        'methods' => 'POST',
        'callback' => 'set_notification_setting',
        'args' => array(
        	'sale_notification' => array(
				'type' => 'boolean'
			),
			'new_product_notification' => array(
				'type' => 'boolean'
			),
			'order_notification' => array(
				'type' => 'boolean'
			),
        ),
    ));



  /*Product Filter API*/
  // register_rest_route('kianaapi/v1', 'filter/products_dumy', array(
  //       'methods' => 'GET',
  //       'callback' => 'wp_rest_filterproducts_endpoint_handler_dumy',
  //   ));
  register_rest_route('kianaapi/v1', 'filter/products', array(
        'methods' => 'GET',
        'callback' => 'wp_rest_filterproducts_endpoint_handler',
    ));
  register_rest_route('kianaapi/v1', 'products/price-range', array(
        'methods' => 'GET',
        'callback' => 'get_price_range',
    ));


  /*User Shipping APIs*/
  register_rest_route('kianaapi/v1', 'user/shipping_address', array(
        'methods' => 'GET',
        'callback' => 'list_shipping_address_from_user',
    ));
  register_rest_route('kianaapi/v1', 'user/shipping_address/edit', array(
        'methods' => 'POST',
        'callback' => 'edit_shipping_address_to_user',
    ));
  register_rest_route('kianaapi/v1', 'user/shipping_address/delete', array(
        'methods' => 'POST',
        'callback' => 'delete_shipping_address_to_user',
    ));
  register_rest_route('kianaapi/v1', 'user/shipping_address/add', array(
        'methods' => 'POST',
        'callback' => 'add_shipping_address_to_user',
    ));



  register_rest_route('kianaapi/v1', 'order/refund_request', array(
        'methods' => 'POST',
        'callback' => 'add_refund_request',
    ));
  register_rest_route('kianaapi/v1', 'order/exchange_request', array(
        'methods' => 'POST',
        'callback' => 'add_exchange_request',
    ));


  register_rest_route('kianaapi/v1', 'product/reviews/(?P<user_id>\d+)', array(
        'methods' => 'GET',
        'callback' => 'get_reviews',
    ));



  register_rest_route('kianaapi/v1', 'cart/add/', array(
        'methods' => 'POST',
        'callback' => 'add_product_to_cart',
    ));
  register_rest_route('kianaapi/v1', 'cart/list/', array(
        'methods' => 'GET',
        'callback' => 'get_cart_list',
    ));
  register_rest_route('kianaapi/v1', 'cart/update/', array(
        'methods' => 'POST',
        'callback' => 'update_cart_item',
    ));
  register_rest_route('kianaapi/v1', 'cart/delete/', array(
        'methods' => 'POST',
        'callback' => 'delete_cart_item',
    ));


  
  register_rest_route('kianaapi/v1', 'wishlist/add/', array(
        'methods' => 'POST',
        'callback' => 'add_to_wishlist',
    ));
  register_rest_route('kianaapi/v1', 'wishlist/list/', array(
        'methods' => 'GET',
        'callback' => 'list_wishlist',
    ));
  register_rest_route('kianaapi/v1', 'wishlist/delete/', array(
        'methods' => 'POST',
        'callback' => 'wishlist_delete_item',
    ));


  /*Wallet Detail*/
  register_rest_route('kianaapi/v1', '/wallet_details', array(
        'methods' => 'GET',
        'callback' => 'get_wallet_detail',
    ));
  

} );

function get_wallet_detail($request) {

  $header = $request->get_headers();
  if(!isset($header['authorization'][0]) || $header['authorization'][0]=='')
  {
    $errors['invalid_user'] = 'User is not logedin';
  }
  $token = str_replace('Bearer ', '', $header['authorization'][0]);
  $user_id = validate_token($token);
  $coupon_code = get_user_meta( $user_id , 'mwb_wrma_refund_wallet_coupon' , true);
  $the_coupon = new WC_Coupon( $coupon_code );
  $customer_coupon_id = $the_coupon->get_id();
  $wallet_amount = get_post_meta( $customer_coupon_id, 'coupon_amount', true );

  $data = array(
    'status' => 200,
    'wallet_code' => $coupon_code,
    'wallet_amount' => $wallet_amount,
  );
  $response = new WP_REST_Response( $data );
  $response->set_status( 200 );
  return $response;
}

// add_action('woocommerce_order_status_changed', 'order_confirmation',10, 3);
function order_confirmation($order_id, $old_status, $new_status){  
    $order = wc_get_order( $order_id );
    $coupon_code = $order->get_coupon_codes()[0];
    if($coupon_code) {
      $the_coupon = new WC_Coupon( $coupon_code );
      $customer_coupon_id = $the_coupon->get_id();
      $wallet_amount = get_post_meta( $customer_coupon_id, 'coupon_amount', true );
      $discount_total = $order->get_discount_total();
      $new_amount = $wallet_amount-$discount_total;
      update_post_meta( $customer_coupon_id, 'coupon_amount',$new_amount );
    }
}

add_action( "woocommerce_rest_insert_shop_order_object", 'your_prefix_on_insert_rest_api', 10, 3 );
function your_prefix_on_insert_rest_api( $object, $request, $is_creating  ) {
   
    if ( ! $is_creating ) {
        return;
    }

    $order_id = $object->get_id();
    $wc_order = new WC_Order( $order_id );
    $coupon_code = $wc_order->get_coupon_codes()[0];
    if($coupon_code) {
      $the_coupon = new WC_Coupon( $coupon_code );
      $customer_coupon_id = $the_coupon->get_id();
      $wallet_amount = get_post_meta( $customer_coupon_id, 'coupon_amount', true );
      $discount_total = $wc_order->get_discount_total();
      $new_amount = $wallet_amount-$discount_total;
      update_post_meta( $customer_coupon_id, 'coupon_amount',$new_amount );
    }

    do_action( 'woocommerce_new_order', $order_id, $wc_order );
}

function wpse27856_set_content_type(){
    return "text/html";
}

function set_notification_setting($request) {
	$header = $request->get_headers();
	$params = $request->get_params();
    if(!isset($header['authorization'][0]) || $header['authorization'][0]=='')
    {
      $errors['invalid_user'] = 'User is not logedin';
    }
    $token = str_replace('Bearer ', '', $header['authorization'][0]);
    $user_id = validate_token($token);
    
    $notification_settings = get_user_meta( $user_id, 'notification_settings', true );
    if(empty($notification_settings)){
    	$notification_settings = array(
        'sale_notification'=> false,
        'new_product_notification'=> false,
        'order_notification'=> false,
      );
    }

    if(isset($params['sale_notification'])){
    	$notification_settings['sale_notification'] = $params['sale_notification'];
    }
    if(isset($params['new_product_notification'])){
    	$notification_settings['new_product_notification'] = $params['new_product_notification'];
    }
    if(isset($params['order_notification'])){
    	$notification_settings['order_notification'] = $params['order_notification'];
    }
    if(empty($errors))
    {
    	update_user_meta( $user_id, 'notification_settings' ,  $notification_settings);
    	$data = array(
			'status' => 200,
			'message' => 'Settings updated',
		);
		$response = new WP_REST_Response( $data );
		$response->set_status( 200 );
		return $response;
    } else {
    	$errors ['status'] = 422;
		$response = new WP_REST_Response( $errors );
		$response->set_status( 422 );
		return $response;
    }
}

function get_slider_images($request) {
	$slider_images = array();
  $banners=get_posts( array('post_type' => 'kiana_mob_banner','numberposts' => -1) );
  foreach ($banners as $key => $banner) {
    // print_r(get_post_meta( $banner->ID ));
    $image_url = get_post_meta( $banner->ID, 'kiana_mob_bannerbanner_iamge', true );
    $redirect_to = get_post_meta( $banner->ID, 'kiana_mob_bannerredirect_to', true );
    // kiana_mob_bannerredirect_to_cat
    if($redirect_to=='new-arrivals')
    {
      $red_to = 'tags';
      $red_value = $redirect_to;
    } elseif($redirect_to=='featured') {
      $red_to = $redirect_to;
      $red_value = $redirect_to;
    } elseif($redirect_to=='category') {
      $red_to = $redirect_to;
      $red_value = get_post_meta( $banner->ID, 'kiana_mob_bannerredirect_to_cat', true );
    }
    $slider_image = array(
      'id' => $banner->ID,
      'title' => $banner->post_title,
      'image_url' => $image_url,
      'redirect_to' => $red_to,
      'redirect_value' => $red_value
    );
    array_push($slider_images, $slider_image);
  }
  // post_title
  // ID
	// for ($i=1; $i <=20 ; $i++) { 
	// 	$slug = 'mobile_banner_'.$i;
	// 	$image = get_attachment_url_by_slug($slug);
	// 	if($image==''){
	// 		break;
	// 	}
	// 	array_push($slider_images, $image);
	// }
	if(empty($slider_images)){
		$errors = array(
			'status' => 422,
			'message' => 'No Images Found',
		);
		$response = new WP_REST_Response( $errors );
		$response->set_status( 422 );
		return $response;
	} else {
		$response = new WP_REST_Response( $slider_images );
		$response->set_status( 422 );
		return $response;
	}
}

function get_attachment_url_by_slug( $slug ) {
  $args = array(
    'post_type' => 'attachment',
    'name' => sanitize_title($slug),
    'posts_per_page' => 1,
    'post_status' => 'inherit',
  );
  $_header = get_posts( $args );
  $header = $_header ? array_pop($_header) : null;
  return $header ? wp_get_attachment_url($header->ID) : '';
}

function get_reviews($request) {

	$params = $request->get_params();
	$user_id = $params['user_id'];
	$user_data = get_user_by( 'ID', $user_id )->data;
	$reviews = array();
	$args = array(
		'author_email' => $user_data->user_email,
	);
	$comments = get_comments($args);
	foreach ($comments as $key => $comment) {
		$review = array();
		$rating = get_comment_meta( $comment->comment_ID , 'rating', true);
		$review = array(
			'comment_ID' => $comment->comment_ID,
			'comment_post_ID' => $comment->comment_post_ID,
			'comment_author' => $comment->comment_author,
			'comment_author_email' => $comment->comment_author_email,
			'comment_content' => $comment->comment_content,
			'comment_approved' => $comment->comment_approved,
			'user_id' => $comment->user_id,
			'rating' => $rating,
		);
		array_push($reviews, $review);
	}

	$data= array(
		'status' => 200,
		'reviews' => $reviews,
	);
	$response = new WP_REST_Response( $data );
	$response->set_status( 200 );
	return $response;
	
}

add_filter('woocommerce_rest_prepare_product_object', 'custom_product_response', 10, 3);
function custom_product_response($response, $post, $request){
    global $wpdb;

    $header = $request->get_headers();
    $in_wishlist = false;
    $in_cart = false;

    if(!isset($header['authorization'][0]) || $header['authorization'][0]=='')
    {
      $errors['invalid_user'] = 'User is not logedin';
    }
    $token = str_replace('Bearer ', '', $header['authorization'][0]);
    $user_id = validate_token($token);
    if(!$user_id){
      $errors['invalid_token'] = 'token is not valid';
    }
    // print_r($errors);
    $wl_list = $wpdb->get_row("SELECT * FROM {$wpdb->prefix}yith_wcwl_lists WHERE user_id=".$user_id,ARRAY_A);
    $wl_items = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}yith_wcwl WHERE wishlist_id=".$wl_list['ID']."$condition",ARRAY_A);
    
    $post_id = $post->get_id();
    $in_wishlist = is_in_array($post_id,$wl_items,'prod_id');
    if($in_wishlist!=false){
    	$key = array_search($post_id,$wl_items,'prod_id');
    	$item_id = $wl_items[$key]['ID'];
    	$response->data["wl_item_id"] = $item_id;
    }
    if($in_wishlist==false){
      $post_child = $post->get_children();
      foreach ($post_child as $key => $child_id) {
        $in_wishlist = is_in_array($child_id,$wl_items,'prod_id');
        if ($in_wishlist) {
          	$key = array_search($child_id,$wl_items,'prod_id');
	    	$item_id = $wl_items[$key]['ID'];
	    	$response->data["wl_item_id"] = $item_id;
          	break;
        }
      }
    }
    if(empty($errors)){
      if($in_wishlist==true)
      {
        $response->data["in_wishlist"] = true;
    	$response->data["wishlist_id"] = $wl_list['ID'];
      } else {
        $response->data["in_wishlist"] = false;
      }
    }
    // $product_variation = $response->data['variations'];
    $product_variation = $post->get_children();
    $var_data = array();
    foreach ($product_variation as $key => $var_id) {
      $variation = wc_get_product( $var_id );
      print_r();
      if($variation->get_stock_status()!=='outofstock'){
        $attributes = array();
        foreach ($variation->get_attributes() as $key => $at) {
          $attribute = array(
            'name' => wc_attribute_label($key),
            'value' => $at
          );
        }
        $var = array(
          'id' => $var_id,
          'name' => $variation->get_name(),
          'sku' => $variation->get_sku(),
          'attributes' => $attribute,
          'price'=> $variation->get_price(),
          'regular_price'=> $variation->get_regular_price(),
          'sale_price'=> $variation->get_sale_price(),
        );
        array_push($var_data, $var);
      }
    }
    
    $response->data['variation_detail'] = $var_data;
    
    return $response;
}
function is_in_array($value,$array,$key)
{
  $is_in_array = array_search($value, array_column($array, $key));
  if($is_in_array === false){
    return false;
  } 
  return true;
}
add_filter( 'woocommerce_rest_check_permissions', 'my_woocommerce_rest_check_permissions', 90, 4 );
function my_woocommerce_rest_check_permissions( $permission, $context, $object_id, $post_type  )
{
  if($context !== 'read')
  {
    if($context !== 'create'){
      return $permission;
    } else {
      if($post_type == 'shop_order'){
        return true;
      } else {
        return $permission;
      }  
    }
  } else {
  // print_r($context);
    // print_r($post_type);
    // pa_fabric
    //pa_occasion-wear
    if($post_type == 'shop_order' || $post_type == 'product_cat' || $post_type == 'product_variation' || $post_type == 'attributes' || $post_type == 'product_tag' || $post_type == 'product_review' || $post_type == 'shop_coupon' || $post_type =='pa_color' || $post_type =='pa_size' || $post_type =='pa_fabric' || $post_type =='pa_occasion-wear' || $post_type =='pa_neck-style' || $post_type =='pa_pattern' || $post_type =='pa_shape' || $post_type =='pa_sleeves'){
      // || $post_type =='pa_size'
      return true;
    } else {
      $post_type_object = get_post_type_object( $post_type );
      return $post_type_object->has_archive; 
    }
  }
}

add_filter( "woocommerce_rest_prepare_shop_order_object", "get_product_order_image", 10, 3 );
function get_product_order_image( $response, $object, $request ) {
 
    if( empty( $response->data ) )
        return $response;

    foreach ($response->data['line_items'] as $key => $items) {
      $order_pid= $items['product_id'];
      $image_id = get_post_meta($items['product_id'],'_thumbnail_id',true);
      $order_imgUrl= wp_get_attachment_url( $image_id, 'full' );

      $response->data['line_items'][$key]['product_thumb'] = $order_imgUrl;
    }
 
    return $response;
} 

// Return User id from token
function validate_token($token) {
  $secret_key = defined('JWT_AUTH_SECRET_KEY') ? JWT_AUTH_SECRET_KEY : false;
  $data = json_decode(base64_decode(str_replace('_', '/', str_replace('-','+',explode('.', $token)[1]))));
  return ($data->data->user->id) ? $data->data->user->id : false;
}

function validate_token_data($token) {
  $secret_key = defined('JWT_AUTH_SECRET_KEY') ? JWT_AUTH_SECRET_KEY : false;
  $data = json_decode(base64_decode(str_replace('_', '/', str_replace('-','+',explode('.', $token)[1]))));
  return ($data->data->user->id) ? $data : false;
}


function get_user_by_mobile($mobile_no){
  if(!$mobile_no){
    return false;
  }
  $user = reset( get_users( array(
     'meta_key' => 'phone_number',
     'meta_value' => $mobile_no,
     'number' => 1
    )));

  if(!$user){
    return false;
  }

  return $user;
}
  /*foreach (get_users() as $key => $user) {
    update_user_meta( $user->data->ID, 'user_activation_status', true );
    print_r(get_user_meta( $user->data->ID,'user_activation_status'));
  }*/
function SandPushNotification($device_ids,$notification,$data) {
    $url = 'https://fcm.googleapis.com/fcm/send';
    $api_key = FCMAPIKEY;

    $fields = array (
        'registration_ids' => $device_ids,
        "collapse_key"=> "type_a",
        "priority"=> 10,
        "notification"=> $notification,
        "data"=> $data
    );

    $headers = array(
        'Content-Type:application/json',
        'Authorization:key='.$api_key
    );
                
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($fields));
    $result = curl_exec($ch);
    if ($result === FALSE) {
        die('FCM Send Error: ' . curl_error($ch));
    }
    curl_close($ch);
    return $result;
}
function push_notification_android($request){

    //API URL of FCM
    $url = 'https://fcm.googleapis.com/fcm/send';


    /*api_key available in:
    Firebase Console -> Project Settings -> CLOUD MESSAGING -> Server key*/    
    // $api_key = 'AAAAodNH55w:APA91bGvZKmzQOXUUqs623ri39VxzwJ6moz5P_yk7goN0e9ZrUWopLxWPPRGkh5ecGKeDxMY2yIaPyalBAUmiub8WjOP1WIHueViJ4_FowrnCYvZMWY2v-t0uLhNHqMcW5wlXmNWVc27';
    $api_key = FCMAPIKEY;
    
    // $message = "test message";
    $device_id = get_device_ids('new_product_notification');
                
    $fields = array (
        'registration_ids' => $device_id,
        "collapse_key"=> "Updates Available",
        "priority"=> 10,
        "notification"=> array(
            "title"=> "Check this Mobile (title)",
            "body"=> "Rich Notification testing (body)",
            "image"=> "https://staging.kianafashion.com/wp-content/uploads/2021/12/photo.jpg",
            "mutable_content"=> true,
            "dl"=> "<deeplink action on tap of notification>"
        ),
        "data"=> array(
            "image"=> "https://staging.kianafashion.com/wp-content/uploads/2021/12/photo.jpg",
            "title"=> "Check this Mobile (title)",
            "body"=> "Rich Notification testing (body)",
        )
    );

    //header includes Content type and api key
    $headers = array(
        'Content-Type:application/json',
        'Authorization:key='.$api_key
    );
                
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($fields));
    $result = curl_exec($ch);
    if ($result === FALSE) {
        die('FCM Send Error: ' . curl_error($ch));
    }
    curl_close($ch);
    return $result;
}

add_action( 'transition_post_status', 'add_new_product_send_notification', 10, 3 );
function add_new_product_send_notification($new_status, $old_status, $post)
{
  if ( 'product' !== $post->post_type || 'publish' !== $new_status ) {
      return;
  }
  $product = wc_get_product( $post->ID );
  $title = $product->get_name();
  if($new_status==='publish' && 'publish' !== $old_status){
    $body = "New porduct available, Please visit store.";
    $device_ids = get_device_ids('new_product_notification');
  } /*elseif ($new_status==='publish' && 'publish' === $old_status) {
    $product = wc_get_product( $post->ID );
    // $body = "Product on sale";
    echo "<pre>";
    print_r($product->get_name());
    print_r($product->get_price());
    print_r($product->get_regular_price());
    print_r($product->get_sale_price());
    print_r($product->get_date_on_sale_from());
    print_r($product->get_date_on_sale_to());
    echo "</pre>";
    die();
  }*/
  $image = wp_get_attachment_thumb_url($product->get_image_id());
  $notification = array(
      "title"=> $title,
      "body"=> $body,
      "image"=> $image,
      "mutable_content"=> true,
      // "dl"=> "<deeplink action on tap of notification>"
  );
  $data = array(
      "image"=> $image,
      "title"=> $title,
      "body"=> $body,
  );


  SandPushNotification($device_ids,$notification,$data);
}


function action_woocommerce_order_status_changed( $order_id, $old_status, $new_status, $order ) { 
    if($old_status==='pending' && $new_status==='processing') {
      $title = "New Order #".$order_id;
      $body = "Order Cost Rs".$order->get_total()." Total Ordered Items - ".$order->get_item_count();
    } else {
      $title = "Order #".$order_id;
      $body = "Order status chnaged to ".$order->get_status();
    }
    foreach ( $order->get_items() as $item_id => $item ) {
       $product = $item->get_product();
       $image = wp_get_attachment_url($product->get_image_id());
       if(!empty($image)){
          break;
       }
    }
    $notification = array(
        "title"=> $title,
        "body"=> $body,
        "image"=> $image,
        "mutable_content"=> true,
        "dl"=> "<deeplink action on tap of notification>"
    );
    $data = array(
        "image"=> $image,
        "title"=> $title,
        "body"=> $body,
    );
    $user_id = $order->get_user_id();
    
    $device_ids = get_device_ids('order_notification',$user_id);

    SandPushNotification($device_ids,$notification,$data);
}; 
         
add_action( 'woocommerce_order_status_changed', 'action_woocommerce_order_status_changed', 10, 4 ); 



function get_device_ids($notification_type=null,$user_id=null) {
  global $wpdb;
  if(!$notification_type) {
    return false;
  }
  if($notification_type=='order_notification')
  {
    if(!$user_id) {
      return false;
    }
    $notification_settings = get_user_meta( $user_id, 'notification_settings', true );
    if($notification_settings['order_notification']==true){
      $device_id = get_user_meta( $user_id, 'user_device_token' );
      return $device_id;
    }
  }
  if($notification_type=='new_product_notification')
  {
    $user_devices = array();
    $devices = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}usermeta WHERE meta_key LIKE 'user_device_token'");
    foreach ($devices as $key => $dev) {
      if(!$dev->meta_value)
      {
        continue;
      }
      $notification_settings = get_user_meta( $dev->user_id, 'notification_settings', true );
      if(!$notification_settings){
          array_push($user_devices, $dev->meta_value);
      } else {
        if(!$notification_settings['new_product_notification']){
          continue;
        } else {
          array_push($user_devices, $dev->meta_value);
        }
      }
    }
      // die();
    return $user_devices;
    // return $devices;
  }
  if($notification_type=='sale_notification')
  {
    $user_devices = array();
    $devices = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}usermeta WHERE meta_key LIKE 'user_device_token'");
    foreach ($devices as $key => $dev) {
      if(!$dev->meta_value)
      {
        continue;
      }
      $notification_settings = get_user_meta( $dev->user_id, 'notification_settings', true );
      if(!$notification_settings){
          array_push($user_devices, $dev->meta_value);
      } else {
        if(!$notification_settings['sale_notification']){
          continue;
        } else {
          array_push($user_devices, $dev->meta_value);
        }
      }
      return $user_devices;
    }
  }
  return false;
}

// auto_login_cart
add_action( 'init', 'auto_login_on_cart_page_for_web_view' );
function auto_login_on_cart_page_for_web_view()
{
	global $wpdb;

	if(isset($_GET['type']) && $_GET['type']=='app_webview')
	{

    if(isset($_GET['action']) && $_GET['action']=='guest_order')
    {
      // print_r(get_current_user_id());
      // die();
      // wc()->session->destroy_session();
      wp_clear_auth_cookie();
      WC()->cart->empty_cart();
      $prods = urldecode($_GET['products']);
      $products = explode('|', $prods);
      foreach ($products as $key => $product) {
        $prod_data = explode(',', $product);
        $product_id = $prod_data[0];
        $quantity = $prod_data[1];
        // $cartObj->add_to_cart( $product_id, $quantity  );
        WC()->cart->add_to_cart( $product_id, $quantity );
      }
      // print_r(WC()->cart);
      wp_safe_redirect(site_url('cart').'?type='.$_GET['type'].'&cart_type=guest');
    }
    else
    {
  		if(get_current_user_id()==0){
    		$token = $_GET['token'];
    		$login_detail = validate_token_data($token);
        if(!isset($_GET['cart_type'])){
          if($login_detail->exp < time()){
            // wp_redirect( site_url( 'my-account'));
          } else {
            $user_id = $login_detail->data->user->id;
            $user = get_user_by( 'ID',$user_id );
            $user_login = $user->data->user_login;
            $user_id = $user->ID;
            wp_set_current_user( $user_id, $user_login );
            wp_set_auth_cookie( $user_id );
            if(isset($_GET['action']) && $_GET['action']=='reorder')
            {
              $objProduct = new WC_Session_Handler();
              $wc_session_data = $objProduct->get_session($user_id);
              $full_user_meta = get_user_meta($user_id,'_woocommerce_persistent_cart_1',true);

              // create new Cart Object
              $cartObj = new WC_Cart();

              $order_id = $_GET['order_id'];
              $order = wc_get_order( $order_id );
              foreach ($order->get_items() as $key => $item) {
                $prod_data = $item->get_data();
                if(!$prod_data['variation_id']){
                  $cartObj->add_to_cart( $prod_data['product_id'], $prod_data['quantity']  );
                } else {
                   $cartObj->add_to_cart( $prod_data['variation_id'], $prod_data['quantity']  );

                }
              }
              $updatedCart = [];
              foreach($cartObj->cart_contents as $key => $val) {
                  unset($val['data']);
                  $updatedCart[$key] = $val;
              }
              if($wc_session_data) {
                  $wc_session_data['cart'] = serialize($updatedCart);
                  $serializedObj = maybe_serialize($wc_session_data);

                  $table_name = 'wp_woocommerce_sessions';
                  $sql ="UPDATE $table_name SET 'session_value'= '".$serializedObj."', WHERE  'session_key' = '".$user_id."'";

                  $rez = $wpdb->query($sql);
              }

              $full_user_meta['cart'] = $updatedCart;
              update_user_meta($user_id, '_woocommerce_persistent_cart_1', $full_user_meta);
              wp_safe_redirect( site_url( 'cart').'?type='.$_GET['type']);
            } else {
              // create new Cart Object
              $objProduct = new WC_Session_Handler();
              $wc_session_data = $objProduct->get_session($user_id);
              $full_user_meta = get_user_meta($user_id,'_woocommerce_persistent_cart_1',true);
              WC()->cart->empty_cart();
              $cartObj = new WC_Cart();

              // Add old cart data to newly created cart object
              if($full_user_meta['cart']) {
                  foreach($full_user_meta['cart'] as $sinle_user_meta) {
                    // print_r($sinle_user_meta);
                    if(!$sinle_user_meta['variation_id']){
                      $cartObj->add_to_cart( $sinle_user_meta['product_id'], $sinle_user_meta['quantity']  );
                    } else {
                       $cartObj->add_to_cart( $sinle_user_meta['variation_id'], $sinle_user_meta['quantity']  );

                    }
                  }
              }

              $updatedCart = [];
              foreach($cartObj->cart_contents as $key => $val) {
                  unset($val['data']);
                  $updatedCart[$key] = $val;
              }

              // If there is a current session cart, overwrite it with the new cart
              if($wc_session_data) {
                  $wc_session_data['cart'] = serialize($updatedCart);
                  $serializedObj = maybe_serialize($wc_session_data);

                  $table_name = 'wp_woocommerce_sessions';
                  $sql ="UPDATE $table_name SET 'session_value'= '".$serializedObj."', WHERE  'session_key' = '".$user_id."'";

                  $rez = $wpdb->query($sql);
              }

              $full_user_meta['cart'] = $updatedCart;
              update_user_meta($user_id, '_woocommerce_persistent_cart_1', $full_user_meta);
              wp_safe_redirect( site_url( 'cart').'?type='.$_GET['type']);
            }
            // wp_die();
          }  
        }
  				
  		} 
      // else {
  			
  		// }
    }
		?>
    <style type="text/css">
      .et-mobile-panel-wrapper {
          display: none;
      }

      .et_element.mob_cat.et_b_header-cart {
          display: none;
      }
      .woocommerce-checkout.woocommerce-order-received .mobile-current-cat{
        display: none;
      }
      .woocommerce-checkout.woocommerce-order-received .continue_shopping_btn {
          text-align: center;
          padding: 20px 0;
      }
    </style>
    <?php
    wp_enqueue_script('kiana_webview',KIAPIINCURL.'js/webview.js',array( 'jquery-core' ),null,true);
	}
}
add_action( 'wp_head', 'webview_close_message' );
function webview_close_message(){
    // On Order received endpoint only
    if( is_wc_endpoint_url( 'order-received' ) ) :

    $order_id = absint( get_query_var('order-received') ); // Get order ID

    if( get_post_type( $order_id ) !== 'shop_order' ) return; // Exit

    $order = wc_get_order( $order_id ); // Get the WC_Order Object instance
    // print_r($order->order_key);
    if(get_post_meta( $order_id, 'order_view_type' , true )=='app_webview' && $_GET['type']!='app_webview'){
      ?>
      <script>
        jQuery(document).ready(function($) {
          if(jQuery(document).find('body').hasClass('woocommerce-order-received')){
            if(window.location.href.indexOf('?')) {
                var url = window.location.href+"&type=app_webview";
            }else{
                var url = window.location.href+"?type=app_webview";
            }
            window.location = url;
          }
        });
      </script>
      <?php   
    }
    if(get_post_meta( $order_id, 'order_view_type' , true )=='app_webview' && $_GET['type']=='app_webview'){
      ?>
      <script type="text/javascript">
        var response = {};
          response.button = '';
          response.cartData = '';
          response.message = 'success';
        window.postMessage(JSON.stringify(response));
      </script>
      <?php
    }
    endif;
}

add_action('woocommerce_after_checkout_billing_form', 'mob_webview_hidden_field');

function mob_webview_hidden_field($checkout)
{

  if (isset($_GET['type'])) {
    $myvalue = sanitize_text_field($_GET['type']);
  }

  echo '<div id="mob_webview_hidden_field">
            <input type="hidden" class="input-hidden" name="view_type" id="type" value="' . $myvalue . '">
    </div>';
}
add_action('woocommerce_checkout_update_order_meta', 'save_your_custom_checkout_hidden_field');

function save_your_custom_checkout_hidden_field($order_id)
{
  if (!empty($_POST['view_type'])) {
    update_post_meta($order_id, 'order_view_type', sanitize_text_field($_POST['view_type']));
  }
}


$page_keys = explode('/', $_SERVER['SCRIPT_URL']);
$page_params = explode('&', $_SERVER['QUERY_STRING']);
if(in_array("order-received", $page_keys) && in_array("type=app_webview", $page_params)){
	add_action( 'woocommerce_thankyou', 'bbloomer_add_content_thankyou' );
}
  
function bbloomer_add_content_thankyou() {
   echo '<div class="continue_shopping_btn"><a class="btn black" onclick=returnToShopMsg();>Continue Shopping.</a></div>';
}

// send_sms();
function send_sms($user_number,$user_message)
{
	
	/*$apiKey = urlencode(TEXTLOCALAPIKEY);
	// $sender = urlencode('KIANA');
  $sender = urlencode('TWNTPS');
	$message = rawurlencode($user_message);
 
	$numbers = implode(',', $numbers);
 
	// Prepare data for POST request
	$data = array('apikey' => $apiKey, 'numbers' => $user_number, "sender" => $sender, "message" => $message);
 
	// Send the POST request with cURL
	$ch = curl_init('https://api.textlocal.in/send/');
	curl_setopt($ch, CURLOPT_POST, true);
	curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	$response = curl_exec($ch);
	curl_close($ch);
	$response = json_decode( $response );*/

  $response = new stdClass();
  $response->status = 'success';

	// Process your response here
	return $response;
}



/*
* Creating a function to create our CPT
*/
 
function custom_post_type() {
 
// Set UI labels for Custom Post Type
    $labels = array(
        'name'                => _x( 'Kiana Mobile Banners', 'Post Type General Name', 'twentytwenty' ),
        'singular_name'       => _x( 'Kiana Mobile Banner', 'Post Type Singular Name', 'twentytwenty' ),
        'menu_name'           => __( 'Kiana Mobile Banners', 'twentytwenty' ),
        'all_items'           => __( 'All Banners', 'twentytwenty' ),
        'view_item'           => __( 'View Banner', 'twentytwenty' ),
        'add_new_item'        => __( 'Add New Banner', 'twentytwenty' ),
        'add_new'             => __( 'Add New', 'twentytwenty' ),
        'edit_item'           => __( 'Edit Banner', 'twentytwenty' ),
        'update_item'         => __( 'Update Banner', 'twentytwenty' ),
        'search_items'        => __( 'Search Banner', 'twentytwenty' ),
        'not_found'           => __( 'Not Found', 'twentytwenty' ),
        'not_found_in_trash'  => __( 'Not found in Trash', 'twentytwenty' ),
    );
     
     
    $args = array(
        'label'               => __( 'kiana_mob_banner', 'twentytwenty' ),
        'description'         => __( 'Mobile Banner Images', 'twentytwenty' ),
        'labels'              => $labels,
        'supports'            => array( 'title'),
        // You can associate this CPT with a taxonomy or custom taxonomy. 
        'hierarchical'        => false,
        'public'              => true,
        'show_ui'             => true,
        'show_in_menu'        => true,
        'show_in_nav_menus'   => true,
        'show_in_admin_bar'   => true,
        'menu_position'       => 5,
        'can_export'          => true,
        'has_archive'         => true,
        'exclude_from_search' => false,
        'publicly_queryable'  => true,
        'capability_type'     => 'post',
        'show_in_rest' => true,
 
    );
     
    // Registering your Custom Post Type
    register_post_type( 'kiana_mob_banner', $args );
 
}

add_action( 'init', 'custom_post_type', 0 );



add_action( 'init', 'wpse9870_init_external' );
function wpse9870_init_external()
{
    global $wp_rewrite;
    $plugin_url = plugins_url( 'reorder.php', __FILE__ );
    $plugin_url = substr( $plugin_url, strlen( home_url() ) + 1 );
    $wp_rewrite->add_external_rule( 'reorder.php$', $plugin_url );
}

add_action( 'cmb2_init', 'yourprefix_register_conditionals_demo_metabox' );
/**
 * Hook in and add a demo metabox. Can only happen on the 'cmb2_init' hook.
 */
function yourprefix_register_conditionals_demo_metabox() {

  // Start with an underscore to hide fields from custom fields list.
  $prefix = 'kiana_mob_banner';

  /**
   * Sample metabox to demonstrate the different conditions you can set.
   */
  $cmb_demo = new_cmb2_box( array(
    'id'            => $prefix . 'banner_settings',
    'title'         => 'Banner Settings',
    'object_types'  => array( 'kiana_mob_banner' ), // Post type.
  ) );

  $cmb_demo->add_field( array(
    'name' => esc_html__( 'Banner Image', 'cmb2' ),
    'desc' => esc_html__( 'Upload an image or enter a URL.', 'cmb2' ),
    'id'   => $prefix . 'banner_iamge',
    'type' => 'file',
  ) );

  $cmb_demo->add_field( array(
    'name'             => 'Redirect To',
    'id'               => $prefix . 'redirect_to',
    'type'             => 'radio',
    'show_option_none' => true,
    'options'          => array(
        'new-arrivals'  => 'New Arrivals',
        'featured'      => 'Featured',
        'category'      => 'category',
    ),
    'attributes'       => array(
      'required'       => 'required',
    ),
  ) );

  $args = array(
      'taxonomy'   => "product_cat",
  );
  $product_categories = get_terms($args);
  $prod_cate = array();
  foreach ($product_categories as $key => $cat) {
    $prod_cate[$cat->slug] = $cat->name;
  }

  $cmb_demo->add_field( array(
    'name'             => esc_html__( 'Rediraction Category', 'cmb2' ),
    'id'               => $prefix . 'redirect_to_cat',
    'type'             => 'select',
    'show_option_none' => true,
    'options'          => $prod_cate,
    'attributes' => array(
      'data-conditional-id'    => $prefix . 'redirect_to',
      'data-conditional-value' => 'category',
    ),
  ) );
}