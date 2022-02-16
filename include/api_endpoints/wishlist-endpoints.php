<?php
// ini_set('display_errors', 1);
// ini_set('display_startup_errors', 1);
// error_reporting(E_ALL);
function add_to_wishlist($request)
{
	global $wpdb;
	
	$defaults = array(
		'add_to_wishlist'     => 0,
		'wishlist_id'         => 0,
		'quantity'            => 1,
		'user_id'             => false,
		'dateadded'           => '',
		'wishlist_name'       => '',
		'wishlist_visibility' => 0,
	);
	$params = $request->get_params();

	$header = $request->get_headers();
	if(!isset($header['authorization'][0]) || $header['authorization'][0]=='')
	{
		$errors['invalid_user'] = 'User is not logedin';
	}
	$token = str_replace('Bearer ', '', $header['authorization'][0]);
	$user_id = validate_token($token);


	$atts = wp_parse_args( $atts, $params );
	$atts = wp_parse_args( $atts, $defaults );
	$atts['user_id'] = $user_id;
	// $user_id = $params['user_id'];
	$wl_list = $wpdb->get_row("SELECT * FROM {$wpdb->prefix}yith_wcwl_lists WHERE user_id=".$user_id,ARRAY_A);
	if(!empty($wl_list)){
		$atts['wishlist_id'] = $wl_list['ID'];
	}

	$prod_id     = apply_filters( 'yith_wcwl_adding_to_wishlist_prod_id', intval( $atts['add_to_wishlist'] ) );
	$wishlist_id = apply_filters( 'yith_wcwl_adding_to_wishlist_wishlist_id', $atts['wishlist_id'] );
	$quantity    = apply_filters( 'yith_wcwl_adding_to_wishlist_quantity', intval( $atts['quantity'] ) );
	$user_id     = apply_filters( 'yith_wcwl_adding_to_wishlist_user_id', intval( $atts['user_id'] ) );
	$dateadded   = apply_filters( 'yith_wcwl_adding_to_wishlist_dateadded', $atts['dateadded'] );
	do_action( 'yith_wcwl_adding_to_wishlist', $prod_id, $wishlist_id, $user_id );

	if($wishlist_id == 0) {
		
		$wishlist =  YITH_WCWL_Wishlist_Factory::get_wishlist( $wishlist_id, 'edit' );
		$token = $wishlist->get_token();

		$wpdb->update( "{$wpdb->prefix}yith_wcwl_lists", 
			array( 
				'user_id' => $user_id,
				'session_id' => NULL,
				'expiration' => NULL,
			),
			array(
				'wishlist_token'=>$token
			)
		);

		$new_wl = $wpdb->get_row("SELECT * FROM {$wpdb->prefix}yith_wcwl_lists WHERE wishlist_token=".$token,ARRAY_A);

		// $wishlist =  YITH_WCWL_Wishlist_Factory::get_wishlist( $new_wl['ID'], 'edit' );
		

	} else {
		$wishlist =  YITH_WCWL_Wishlist_Factory::get_wishlist( $wishlist_id, 'edit' );
	}

	if(!empty($wishlist)){
		$item = new YITH_WCWL_Wishlist_Item();

		$item->set_product_id( $prod_id );
		$item->set_quantity( $quantity );
		$item->set_wishlist_id( $wishlist->get_id() );
		$item->set_user_id( $wishlist->get_user_id() );

		if ( $dateadded ) {
			$item->set_date_added( $dateadded );
		}

		$wishlist->add_item( $item );
		$wishlist->save();

		// print_r($wishlist);
		// die();

		wp_cache_delete( 'wishlist-count-' . $wishlist->get_token(), 'wishlists' );

		$user_id = $wishlist->get_user_id();

		if ( $user_id ) {
			wp_cache_delete( 'wishlist-user-total-count-' . $user_id, 'wishlists' );
		}
		
		do_action( 'yith_wcwl_added_to_wishlist', $prod_id, $item->get_wishlist_id(), $item->get_user_id() );
		$wl_id = $item->get_wishlist_id();
		$item_id = $item->get_id();
		// die();
		$data= array(
			'status' => 200,
			'message' => "Product Added to wishlist",
			'wishlist_id' => $wl_id,
			'item_id' => $item_id,
		);
		$response = new WP_REST_Response( $data );
		$response->set_status( 200 );
		return $response;
	}
}

function list_wishlist($request)
{
	global $wpdb;
	$params = $request->get_params();

	$header = $request->get_headers();
	if(!isset($header['authorization'][0]) || $header['authorization'][0]=='')
	{
		$errors['invalid_user'] = 'User is not logedin';
	}
	$token = str_replace('Bearer ', '', $header['authorization'][0]);
	$user_id = validate_token($token);

	// $user_id = $params['user_id'];
	
	$per_page = (isset($params['per_page'])) ? $params['per_page'] : 5;
	$page = (isset($params['page'])) ? $params['page'] : 1;
	$offset = $per_page*($page-1);
	// yith_wcwl
	// print_r($wpdb->get_results("SELECT * FROM {$wpdb->prefix}yith_wcwl_lists"));
	$wishlist = array();
	$items = array();
	$wl_list = $wpdb->get_row("SELECT * FROM {$wpdb->prefix}yith_wcwl_lists WHERE user_id=".$user_id,ARRAY_A);

	$total_items = $wpdb->get_var("SELECT COUNT(prod_id) FROM {$wpdb->prefix}yith_wcwl WHERE wishlist_id=".$wl_list['ID']);
	$last_page = ceil($total_items/$per_page);
	
	if(1 == $last_page)
	{
		$condition = "";
	} else {
		$condition = " LIMIT $offset,$per_page";
	}

	
	$wl_items = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}yith_wcwl WHERE wishlist_id=".$wl_list['ID']." ORDER BY dateadded desc $condition",ARRAY_A);
	// print_r($wl_items);
	$pagination = array(
		'per_page' => (int)$per_page,
		'total_items' => (int)$total_items,
		'total_pages' => (int)$last_page,
		'current_page' => (int)$page,
		'last_page' => (int)$last_page,
		'first_page' => 1,
	);

	foreach ($wl_items as $key => $wl_it) {
		$on_sale = false;
		$product = wc_get_product( $wl_it['prod_id'] );
		$image_url = wp_get_attachment_url($product->get_image_id());
		if($product->get_sale_price()){
          $on_sale = true;
          $befor_sale = $product->get_regular_price();
          $after_sale = $product->get_sale_price();
        }
		if(!$image_url){
			$image_url = wc_placeholder_img_src();
		}
		$attributes = array();
		foreach ($product->get_attributes() as $key => $at) {
			
			$attribute = array(
				'name' => wc_attribute_label($key),
				'value' => $at
			);
			array_push($attributes, $attribute);
		}

		$item = array(
			'ID' => $wl_it['ID'],
            'prod_id' => $wl_it['prod_id'],
            'name' => $product->get_name(),
            'slug' => $product->get_slug(),
            'type' => $product->get_type(),
            'sku' => $product->get_sku(),
            'price' => $product->get_price(),
            'regular_price' => $product->get_regular_price(),
        	'sale_price' => $product->get_sale_price(),
        	'image' => $image_url,
        	'stock_status' => $product->get_stock_status(),
        	'stock_quantity' => '',
            'quantity' => $wl_it['quantity'],
            'user_id' => $wl_it['user_id'],
            'wishlist_id' => $wl_it['wishlist_id'],
            'position' => $wl_it['position'],
            'original_price' => $wl_it['original_price'],
            'original_currency' => $wl_it['original_currency'],
            'dateadded' => $wl_it['dateadded'],
		);
		$lowest_sale_price = (int)$product->get_regular_price();
		$product_variation = $product->get_children();
		foreach ($product_variation as $key => $var_id) {
          $variation = wc_get_product( $var_id );
          if($on_sale==false){
            if($variation->get_sale_price())
            {
              $on_sale = true;
              $befor_sale = $variation->get_regular_price();
              $after_sale = $variation->get_sale_price();
            } else {
              $on_sale = false;
              $befor_sale = $variation->get_regular_price();
              $after_sale = $variation->get_sale_price();
            }
          }
          if($lowest_sale_price==0)
          {
            if($on_sale==true)
            {
              $lowest_sale_price = $variation->get_sale_price();
            } else {
              $lowest_sale_price = $variation->get_price();
            }
          } else {
            if($variation->get_sale_price()) {
              if((int)$variation->get_sale_price()<$lowest_sale_price){
                $lowest_sale_price = (int)$variation->get_sale_price();
              }
            }
          }
        }

        $item['on_sale'] = $on_sale;
        $item['price_befor_sale'] = $befor_sale;
        $item['price_after_sale'] = $after_sale;
        $sale_per = ceil(($befor_sale-$lowest_sale_price)*100/$befor_sale);
        if($sale_per==-INF){
        	$sale_per = 0;
        }
        $item['sale_percentage'] = (is_numeric($sale_per)) ? $sale_per : 0;

		if ( has_term( 'new-arrivals', 'product_tag', $product->get_id() ) ) {
            $item['is_new'] = true;
        } else {
          $item['is_new'] = false;
        }

        $item['size_chart'] = 'https://www.kianafashion.com/wp-content/uploads/2020/10/New-Size-Chart.jpg';

		if($product->get_type() == 'variable'){
			// print_r($product->get_attributes());
			$product_variation = $product->get_children();
		    $var_data = array();
		    $attributes = array();
			foreach ($product->get_attributes() as $key => $at) {
				$data = $at->get_data();
				$options = explode(', ', $product->get_attribute( $data['name'] ));
				$attribute = array(
					'id' => $data['id'],
					'name' => wc_attribute_label($data['name']),
					'position' => $data['position'],
					'visible' => $data['visible'],
					'variation' => $data['variation'],
					'options' => $options,
				);
				array_push($attributes, $attribute);
			}
		    $item['attributes'] = $attributes;
		    foreach ($product_variation as $key => $var_id) {
		      $variation = wc_get_product( $var_id );
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
		    $item['variation_detail'] = $var_data;
		} elseif($product->get_type() == 'variation') {
        	$item['attributes'] = $attributes;

		}
		array_push($items, $item);
	}
	if(!empty($items)){
		$wishlist = array(
			'wl_id' => $wl_list['ID'],
			'user_id' => $wl_list['user_id'],
			'wl_token' => $wl_list['wishlist_token'],
			'page_meta' => $pagination,
			'items' => $items,
		);
	}

	if(empty($errors)){
		if(!empty($wishlist)){
			$response = new WP_REST_Response( $wishlist );
			$response->set_status( 200 );
			return $response;
		} else {
			$data= array(
				'status' => 422,
				'cart' => "Wishlist Is empty",
			);
			$response = new WP_REST_Response( $data );
			$response->set_status( 422 );
			return $response;
		}
	} else {
		$response = new WP_REST_Response( $errors );
		$response->set_status( 422 );
		return $response;
	}
}

function wishlist_delete_item($request)
{
	global $wpdb;
	$params = $request->get_params();
	// $user_id = $params['user_id'];

	$header = $request->get_headers();
	if(!isset($header['authorization'][0]) || $header['authorization'][0]=='')
	{
		$errors['invalid_user'] = 'User is not logedin';
	}
	$token = str_replace('Bearer ', '', $header['authorization'][0]);
	$user_id = validate_token($token);

	$wl_id = $params['wishlist_id'];
	$item_id = $params['item_id'];

	$wl_items = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}yith_wcwl WHERE wishlist_id=".$wl_id,ARRAY_A);

	$is_in_array = array_search($item_id, array_column($wl_items, 'ID'));
	
	if(empty($errors)){
		if($is_in_array !== false){
			$wpdb->delete( "{$wpdb->prefix}yith_wcwl" , array( 'ID' => $item_id ) );
			$data= array(
				'status' => 200,
				'cart' => "Item Removed From Wishlist",
			);
			$response = new WP_REST_Response( $data );
			$response->set_status( 200 );
			return $response;
		} else {
			$data= array(
				'status' => 422,
				'cart' => "Selected Item is not exits in wishlist",
			);
			$response = new WP_REST_Response( $data );
			$response->set_status( 422 );
			return $response;
		}
	} else {
		$response = new WP_REST_Response( $errors );
		$response->set_status( 422 );
		return $response;
	}
}