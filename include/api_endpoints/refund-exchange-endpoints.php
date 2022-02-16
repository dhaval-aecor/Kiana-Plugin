<?php

function add_exchange_request($request) {
	$params = $request->get_params();

	$order_id = $params['orderid'];
	$subject = $params['subject'];
	$reason = $params['reason'];
	if(!isset($params['subject'])) {
		$errors['subject_empty'] = 'Please enter refund subject';
	}
	if(!isset($params['reason'])) {
		$errors['reason_empty'] = 'Please enter refund reason';
	}

	$products = array();
	$data = array();
	if(isset($params["from"]) && isset($params["to"]))
	{
		$data['from'] = $params["from"];
		$to_data = array();
		foreach ($params["to"] as $key => $var) {
			$variation_id = $var['variation_id'];
			$product = wc_get_product($variation_id);
			$attrs = $product->get_attributes();
			foreach ($attrs as $key => $atts) {
				$variation = array(
					"attribute_{$key}" => $atts
				);
			}
			$dat = array(
				'id' => $var['product_id'],
				'variation_id' => $var['variation_id'],
				'qty' => $var['qty'],
				'variations' => $variation,
				'price' => $var['price'],
			);
			array_push($to_data, $dat);
		}
		// die();
		$data['to'] = $to_data;
	} else {
		$errors['products_empty']='Please select products for exchange.';
	}
	
	if(empty($errors)){
		$pending = true;
		if($pending)
		{
			$date = date("d-m-Y");
			$products[$date]['status'] = 'pending';
			$products[$date]['from'] = $data['from'];
			$products[$date]['to'] = $data['to'];
			$products[$date]['orderid'] = $params['orderid'];
			$products[$date]['subject'] = $params['subject'];
			$products[$date]['reason'] = $params['reason'];
		}
		
		update_post_meta($order_id, 'mwb_wrma_exchange_product', $products);
		
		$exchange_subject = $subject;
		$mail_header = stripslashes(get_option('mwb_wrma_notification_mail_header', false));
		$mail_header = apply_filters( 'mwb_wrma_meta_content', $mail_header );
		$mail_footer = stripslashes(get_option('mwb_wrma_notification_mail_footer', false));
		$mail_footer = apply_filters( 'mwb_wrma_meta_content', $mail_footer );
		
		$message = '<html>
						<body>
						<style>
						body {
						    box-shadow: 2px 2px 10px #ccc;
						    color: #767676;
						    font-family: Arial,sans-serif;
						    margin: 80px auto;
						    max-width: 700px;
						    padding-bottom: 30px;
						    width: 100%;
						}
						
						h2 {
							font-size: 30px;
							margin-top: 0;
							color: #fff;
							padding: 40px;
							background-color: #557da1;
						}
						
						h4 {
							color: #557da1;
							font-size: 20px;
							margin-bottom: 10px;
						}
						
						.content {
							padding: 0 40px;
						}
						
						.Customer-detail ul li p {
							margin: 0;
						}
						
						.details .Shipping-detail {
							width: 40%;
							float: right;
						}
						
						.details .Billing-detail {
							width: 60%;
							float: left;
						}
						
						.details .Shipping-detail ul li,.details .Billing-detail ul li {
							list-style-type: none;
							margin: 0;
						}
						
						.details .Billing-detail ul,.details .Shipping-detail ul {
							margin: 0;
							padding: 0;
						}
						
						.clear {
							clear: both;
						}
						
						table,td,th {
							border: 2px solid #ccc;	
							padding: 15px;
							text-align: left;
						}
						
						table {
							border-collapse: collapse;
							width: 100%;
						}
						
						.info {
							display: inline-block;
						}
						
						.bold {
							font-weight: bold;
						}
						
						.footer {
							margin-top: 30px;
							text-align: center;
							color: #99B1D8;
							font-size: 12px;
						}
						dl.variation dd {
					    font-size: 12px;
					    margin: 0;
						}
					</style>
						<div class="header" style="text-align: center; padding: 10px;">
							'.$mail_header.'
						</div>
						<div class="header">
							<h2>'.$exchange_subject.'</h2>
						</div>
						<div class="content">
							<div class="reason">
								<h4>'.__('Reason of Exchange', 'mwb-woocommerce-rma').'</h4>
								<p>'.$reason.'</p>
							</div>
							<div class="Order">
								<h4>Order #'.$order_id.'</h4>
								<h4>'.__('Exchanged From', 'mwb-woocommerce-rma').'</h4>
								<table>
									<tbody>
										<tr>
											<th>'.__('Product', 'mwb-woocommerce-rma').'</th>
											<th>'.__('Quantity', 'mwb-woocommerce-rma').'</th>
											<th>'.__('Price', 'mwb-woocommerce-rma').'</th>
										</tr>';
						$order = wc_get_order($order_id);
						$requested_products = $products[$date]['from'];
						
						$mwb_vendor_emails = array();
						
						if(isset($requested_products) && !empty($requested_products))
						{
							$total = 0;
							foreach( $order->get_items() as $item_id => $item )
							{
								$product = apply_filters( 'woocommerce_order_item_product', $item->get_product(), $item );
								foreach($requested_products as $requested_product)
								{
									if(isset($requested_product['item_id']))
									{	
										if($item_id == $requested_product['item_id'])
										{	
											if(isset($requested_product['variation_id']) && $requested_product['variation_id'] > 0)
											{
												$prod = wc_get_product($requested_product['variation_id']);

											}
											else
											{
												$prod = wc_get_product($requested_product['product_id']);
											}

											if(mwb_wrma_wc_dokan_activated()){
												$author = get_post_field( 'post_author',$requested_product['product_id'] );
												if ( ! user_can( $author, 'dokandar' ) ) {
											        $is_seller = false;
											    }
											    else{
											    	$is_seller = true;
											    }
											    if($is_seller) {
											    	$seller_data = get_userdata($author);
													$mwb_vendor_emails[] = $seller_data->user_email;
											    }
											}
											
											$subtotal = $requested_product['price']*$requested_product['qty'];
											$total += $subtotal;
											if( WC()->version < "3.1.0" )
											{
												$item_meta      = new WC_Order_Item_Meta( $item, $prod );
												$item_meta_html = $item_meta->display( true, true );
											}
											else
											{
												$item_meta      = new WC_Order_Item_Product( $item, $prod );
												$item_meta_html = wc_display_item_meta($item_meta,array('echo'=> false));
											}
											
											$message .= '<tr>
															<td>'.$item['name'].'<br>';
											$message .= '<small>'.$item_meta_html.'</small>
															<td>'.$requested_product['qty'].'</td>
															<td>'.mwb_wrma_format_price($requested_product['price']*$requested_product['qty']).'</td>
														</tr>';
										
										}
									}
								}
							}
						}			
						$message .= '
									<tr>
										<th colspan="2">'.__('Total', 'mwb-woocommerce-rma').':</th>
										<td>'.mwb_wrma_format_price($total).'</td>
									</tr>
								</tbody>
							</table>	
							<h4>'.__('Exchanged To', 'mwb-woocommerce-rma').'</h4>
							<table>
								<tbody>
									<tr>
										<th>'.__('Product', 'mwb-woocommerce-rma').'</th>
										<th>'.__('Quantity', 'mwb-woocommerce-rma').'</th>
										<th>'.__('Price', 'mwb-woocommerce-rma').'</th>
									</tr>';
							$exchanged_to_products = $products[$date]['to'];
							$total_price = 0;
							if(isset($exchanged_to_products) && !empty($exchanged_to_products))
							{
								foreach($exchanged_to_products as $key=>$exchanged_product)
								{
									$variation_attributes = array();
									if(isset($exchanged_product['variation_id']))
									{
										if($exchanged_product['variation_id'])
										{
											$variation_product = new WC_Product_Variation($exchanged_product['variation_id']);
											$variation_attributes = $variation_product->get_variation_attributes();
											$variation_labels = array();
											foreach ($variation_attributes as $label => $value)
											{
												if(is_null($value) || $value == ''){
													$variation_labels[] = $label;
												}
											}
											if(isset($exchanged_product['variations']) && !empty($exchanged_product['variations']))
											{
												$variation_attributes = $exchanged_product['variations'];
											}	
										}
									}

									if(isset($exchanged_product['p_id']))
									{
										if($exchanged_product['p_id'])
										{
											$grouped_product = new WC_Product_Grouped($exchanged_product['p_id']);
											$grouped_product_title = $grouped_product->get_title();
										}
									}

									$pro_price = $exchanged_product['qty']*$exchanged_product['price'];
									$total_price += $pro_price;
									$product = new WC_Product($exchanged_product['id']);
									$title = "";
									if(isset($exchanged_product['p_id']))
									{
										$title .= $grouped_product_title.' -> ';
									}
									$title .=$product->get_title();

									if(isset($variation_attributes) && !empty($variation_attributes))
									{
										$title .= wc_get_formatted_variation( $variation_attributes );
									}
									$message .= '<tr>
													<td>'.$title.'</td>
													<td>'.$exchanged_product['qty'].'</td>
													<td>'.mwb_wrma_format_price($pro_price).'</td>
												</tr>';
								}
							}
							$message .= '<tr>
											<th colspan="2">'.__('Total', 'mwb-woocommerce-rma').':</th>
											<td>'.mwb_wrma_format_price($total_price).'</td>
										</tr>
									</tbody>
								</table>
							</div>';
							if($total_price - $total > 0)
							{
								$extra_amount = $total_price - $total;
								$message .= '<h2>'.__('Extra Amount', 'mwb-woocommerce-rma').' : '.mwb_wrma_format_price($extra_amount).'</h2>';							
							}		
							$message .= ' <div class="Customer-detail">
												<h4>'.__('Customer details', 'mwb-woocommerce-rma').'</h4>
												<ul>
													<li><p class="info">
															<span class="bold">'.__('Email','mwb-woocommerce-rma').': </span>'.get_post_meta($order_id, '_billing_email', true).'
														</p></li>
													<li><p class="info">
															<span class="bold">'.__('Tel','mwb-woocommerce-rma').': </span>'.get_post_meta($order_id, '_billing_phone', true).'
														</p></li>
												</ul>
											</div>
											<div class="details">
												<div class="Shipping-detail">
													<h4>'.__('Shipping Address', 'mwb-woocommerce-rma').'</h4>
													'.$order->get_formatted_shipping_address().'
												</div>
												<div class="Billing-detail">
													<h4>'.__('Billing Address', 'mwb-woocommerce-rma').'</h4>
													'.$order->get_formatted_billing_address().'
												</div>
												<div class="clear"></div>
											</div>
										</div>
										<div class="footer" style="text-align: center; padding: 10px;">
											'.$mail_footer.'
										</div>
									</body>
									</html>';
		
		//Send mail to merchant
		
		$headers = array();
		
		$headers[] = "Content-Type: text/html; charset=UTF-8";
		$to = get_option('mwb_wrma_notification_from_mail');
		$subject = get_option('mwb_wrma_notification_merchant_exchange_subject');
		$subject = str_replace('[order]', "#".$order_id, $subject);
		
		
		wc_mail( $to, $subject, $message, $headers );
		if(mwb_wrma_wc_dokan_activated())
		{
			if(isset($mwb_vendor_emails) && is_array($mwb_vendor_emails) && !empty($mwb_vendor_emails))
			{
				foreach ($mwb_vendor_emails as $vendor_email) {
					wc_mail( $vendor_email, $subject, $message, $headers );
				}
			}
		}
						
		//Send mail to User that we recieved your request
		
		$fname = get_option('mwb_wrma_notification_from_name');
		$fmail = get_option('mwb_wrma_notification_from_mail');
		$to = get_post_meta($order_id, '_billing_email', true);;
		$headers[] = "From: $fname <$fmail>";
		$headers[] = "Content-Type: text/html; charset=UTF-8";
		$subject = get_option('mwb_notification_exchange_subject');
		$message = stripslashes(get_option('mwb_notification_exchange_rcv'));
		$message = apply_filters( 'mwb_wrma_meta_content', $message );
		
		$fname = get_post_meta($order_id, '_billing_first_name', true);
		$lname = get_post_meta($order_id, '_billing_last_name', true);
		$billing_company = get_post_meta($order_id, '_billing_company', true);
		$billing_email = get_post_meta($order_id, '_billing_email', true);
		$billing_phone = get_post_meta($order_id, '_billing_phone', true);
		$billing_country = get_post_meta($order_id, '_billing_country', true);
		$billing_address_1 = get_post_meta($order_id, '_billing_address_1', true);
		$billing_address_2 = get_post_meta($order_id, '_billing_address_2', true);
		$billing_state = get_post_meta($order_id, '_billing_state', true);
		$billing_postcode = get_post_meta($order_id, '_billing_postcode', true);
		$shipping_first_name = get_post_meta($order_id, '_shipping_first_name', true);
		$shipping_last_name = get_post_meta($order_id, '_shipping_last_name', true);
		$shipping_company = get_post_meta($order_id, '_shipping_company', true);
		$shipping_country = get_post_meta($order_id, '_shipping_country', true);
		$shipping_address_1 = get_post_meta($order_id, '_shipping_address_1', true);
		$shipping_address_2 = get_post_meta($order_id, '_shipping_address_2', true);
		$shipping_city = get_post_meta($order_id, '_shipping_city', true);
		$shipping_state = get_post_meta($order_id, '_shipping_state', true);
		$shipping_postcode = get_post_meta($order_id, '_shipping_postcode', true);
		$payment_method_tittle = get_post_meta($order_id, '_payment_method_title', true);
		$order_shipping = get_post_meta($order_id, '_order_shipping', true);
		$order_total = get_post_meta($order_id, '_order_total', true);
		$refundable_amount = get_post_meta($order_id, 'refundable_amount', true);
		
		$fullname = $fname." ".$lname;
		
		$message = str_replace('[username]', $fullname, $message);
		$message = str_replace('[order]', "Order #".$order_id, $message);
		$message = str_replace('[siteurl]', home_url(), $message);
		$message = str_replace('[_billing_company]', $billing_company, $message);
		$message = str_replace('[_billing_email]', $billing_email, $message);
		$message = str_replace('[_billing_phone]', $billing_phone, $message);
		$message = str_replace('[_billing_country]', $billing_country, $message);
		$message = str_replace('[_billing_address_1]', $billing_address_1, $message);
		$message = str_replace('[_billing_address_2]', $billing_address_2, $message);
		$message = str_replace('[_billing_state]', $billing_state, $message);
		$message = str_replace('[_billing_postcode]', $billing_postcode, $message);
		$message = str_replace('[_shipping_first_name]', $shipping_first_name, $message);
		$message = str_replace('[_shipping_last_name]', $shipping_last_name, $message);
		$message = str_replace('[_shipping_company]', $shipping_company, $message);
		$message = str_replace('[_shipping_country]', $shipping_country, $message);
		$message = str_replace('[_shipping_address_1]', $shipping_address_1, $message);
		$message = str_replace('[_shipping_address_2]', $shipping_address_2, $message);
		$message = str_replace('[_shipping_city]', $shipping_city, $message);
		$message = str_replace('[_shipping_state]', $shipping_state, $message);
		$message = str_replace('[_shipping_postcode]', $shipping_postcode, $message);
		$message = str_replace('[_payment_method_title]', $payment_method_tittle, $message);
		$message = str_replace('[_order_shipping]', $order_shipping, $message);
		$message = str_replace('[_order_total]', $order_total, $message);
		$message = str_replace('[_refundable_amount]', $refundable_amount, $message);
		$message = str_replace('[formatted_shipping_address]', $order->get_formatted_shipping_address(), $message);
		$message = str_replace('[formatted_billing_address]', $order->get_formatted_billing_address(), $message);
		
		$mail_header = stripslashes(get_option('mwb_wrma_notification_mail_header', false));
		$mail_header = apply_filters( 'mwb_wrma_meta_content', $mail_header );
		$mail_footer = stripslashes(get_option('mwb_wrma_notification_mail_footer', false));
		$mail_footer = apply_filters( 'mwb_wrma_meta_content', $mail_footer );

		$subject = str_replace('[username]', $fullname, $subject);
		$subject = str_replace('[order]', "Order #".$order_id, $subject);
		$subject = str_replace('[siteurl]', home_url(), $subject);
		
		$mail_header = str_replace('[username]', $fullname, $mail_header);
		$mail_header = str_replace('[order]', "Order #".$order_id, $mail_header);
		$mail_header = str_replace('[siteurl]', home_url(), $mail_header);
		
		$template = get_option('mwb_notification_exchange_template','no');

		if(isset($template) && $template == 'on')
		{
			
			$html_content = $message;
		}
		else
		{
			$html_content = '<html>
								<head>
									<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
									<meta name="viewport" content="width=device-width, initial-scale=1.0"/>
								</head>
								<body>
									<table cellpadding="0" cellspacing="0" width="100%">
										<tr>
											<td style="text-align: center; margin-top: 30px; padding: 10px; color: #99B1D8; font-size: 12px;">
												'.$mail_header.'
											</td>
										</tr>	
										<tr>
											<td>
												<table align="center" cellpadding="0" cellspacing="0" style="border-collapse: collapse; font-family:Open Sans; max-width: 600px; width: 100%;">
													<tr>
														<td style="padding: 36px 48px; width: 100%; background-color:#557DA1;color: #fff; font-size: 30px; font-weight: 300; font-family:helvetica;">'.$subject.'</td>
													</tr>
													<tr>
														<td style="width:100%; padding: 36px 48px 10px; background-color:#fdfdfd; font-size: 14px; color: #737373;">'.$message.'</td>
													</tr>
												</table>
											</td>
										</tr>
										<tr>
											<td style="text-align: center; margin-top: 30px; color: #99B1D8; font-size: 12px;">
												'.$mail_footer.'
											</td>
										</tr>	
									</table>
								</body>
							</html>';
		}
		
		$mwb_wrma_restrict_mails = get_option( 'mwb_wrma_exchange_restrict_customer_mails', true );
		if ( ! empty( $mwb_wrma_restrict_mails ) && 'yes' != $mwb_wrma_restrict_mails ) {
			wc_mail($to, $subject, $html_content, $headers );
		}

		update_post_meta($order_id, "mwb_wrma_request_made", true);
		
		$order = new WC_Order($order_id);
		$order->update_status('wc-exchange-request', __('User Request to Exchange Product','mwb-woocommerce-rma'));
		$data= array(
			'status' => 200,
			'message' => 'Exchange requested successfully.You have received a notification mail regarding this, Please check your mail.',
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
function add_refund_request($request) {
	$params = $request->get_params();

	$secuity_key = wp_create_nonce( "mwb-wrma-ajax-seurity-string" );

	$order_id = $params['orderid'];
	$subject = $params['subject'];
	$reason = $params['reason'];
	$mwb_wrma_refund_method = $params['refund_method'];
	if(!isset($params['products'])) {
		$errors['products_empty'] = 'Please select product you want to refund';
	}
	if(!isset($params['subject'])) {
		$errors['subject_empty'] = 'Please enter refund subject';
	}
	if(!isset($params['reason'])) {
		$errors['reason_empty'] = 'Please enter refund reason';
	}
	if(!isset($params['orderid']))
	{
		$errors['order_invalid'] = 'Please select proper order for refund';	
	}
	
	if(empty($errors)){
		$data = $params;

		$data["action"] = "mwb_wrma_return_product_info";
		$data["security_check"] = $secuity_key;
		$refundable_amount = 0;
		$total = 0;
		foreach ($params['products'] as $key => $item) {
			$total = $item['qty']*$item['price'];
			$refundable_amount += get_refund_amount($order_id,$total);
		}
		$data["amount"] = $refundable_amount;

		update_post_meta($order_id,'mwb_wrma_refund_method' ,$mwb_wrma_refund_method);

		$products = get_post_meta($order_id, 'mwb_wrma_return_product', true);
		$pending = true;
		if(isset($products) && !empty($products))
		{
			foreach($products as $date=>$product)
			{
				if($product['status'] == 'pending')
				{
					$products[$date] = $data;
					$products[$date]['status'] = 'pending'; //update requested products
					$pending = false;
					break;
				}	
			}
		}
		if($pending)
		{
			if(!is_array($products))
			{
				$products = array();
			}

			$date = date("d-m-Y");
			$products[$date] = $data;
			$products[$date]['status'] = 'pending';
		}	
		
		update_post_meta($order_id, "mwb_wrma_request_made", true);
		
		update_post_meta($order_id, 'mwb_wrma_return_product', $products);

		$ref_products = get_option( $order_id.'mwb_wrma_refunded_pro_qty', array() );
		$product_refunded_array = array(
			'status'   => 'pending',
			'products' => empty( $ref_products['products'] ) ? array() : $ref_products['products'],
		);
		update_option( $order_id.'mwb_wrma_refunded_pro_qty', $product_refunded_array );
		
		//Send mail to merchant
		$subject = str_replace('[order]', "#".$order_id, $subject);
		
		$reason_subject = $subject;
		
		$mail_header = stripslashes(get_option('mwb_wrma_notification_mail_header', false));
		$mail_header = apply_filters( 'mwb_wrma_meta_content', $mail_header );
		$mail_footer = stripslashes(get_option('mwb_wrma_notification_mail_footer', false));
		$mail_footer = apply_filters( 'mwb_wrma_meta_content', $mail_footer );
		
		$message ='<html>
		<body>';
		$message .= do_action('wwrma_return_request_before_mail_content', $order_id).'
			<style>
			body {
				box-shadow: 2px 2px 10px #ccc;
				color: #767676;
				font-family: Arial,sans-serif;
				margin: 80px auto;
				max-width: 700px;
				padding-bottom: 30px;
				width: 100%;
			}
			
			h2 {
				font-size: 30px;
				margin-top: 0;
				color: #fff;
				padding: 40px;
				background-color: #557da1;
			}
			
			h4 {
				color: #557da1;
				font-size: 20px;
				margin-bottom: 10px;
			}
			
			.content {
				padding: 0 40px;
			}
			
			.Customer-detail ul li p {
				margin: 0;
			}
			
			.details .Shipping-detail {
				width: 40%;
				float: right;
			}
			
			.details .Billing-detail {
				width: 60%;
				float: left;
			}
			
			.details .Shipping-detail ul li,.details .Billing-detail ul li {
				list-style-type: none;
				margin: 0;
			}
			
			.details .Billing-detail ul,.details .Shipping-detail ul {
				margin: 0;
				padding: 0;
			}
			
			.clear {
				clear: both;
			}
			
			table,td,th {
				border: 2px solid #ccc;
				padding: 15px;
				text-align: left;
			}
			
			table {
				border-collapse: collapse;
				width: 100%;
			}
			
			.info {
				display: inline-block;
			}
			
			.bold {
				font-weight: bold;
			}
			
			.footer {
				margin-top: 30px;
				text-align: center;
				color: #99B1D8;
				font-size: 12px;
			}
			dl.variation dd {
				font-size: 12px;
				margin: 0;
			}
			</style>';
		$message .='<div class="header" style="text-align:center;padding: 10px;">
			'.$mail_header.'
			</div>	
			<div class="header">
			<h2>'.$reason_subject.'</h2>
			</div>
			<div class="content">
			
			<div class="reason">
			<h4>'.__('Reason of Refund', 'mwb-woocommerce-rma').'</h4>
			<p>'.$reason.'</p>
			</div>
			<div class="Order">
			<h4>Order #'.$order_id.'</h4>';

		$product_table ='<table style="width: 100%; text-align: left; border-collapse: collapse; color : #767676 ;">
			<tbody>
			<tr>
			<th style="border: 2px solid #ddd; padding: 5px;">'.__('Product', 'mwb-woocommerce-rma').'</th>
			<th style="border: 2px solid #ddd; padding: 5px;">'.__('Quantity', 'mwb-woocommerce-rma').'</th>
			<th style="border: 2px solid #ddd; padding: 5px;">'.__('Price', 'mwb-woocommerce-rma').'</th>
			</tr>';
		
		$order = new WC_Order($order_id);
		$requested_products = $products[$date]['products'];

		$mwb_vendor_emails = array();
		if(isset($requested_products) && !empty($requested_products))
		{
			$total = 0;
			foreach( $order->get_items() as $item_id => $item )
			{
				$product = apply_filters( 'woocommerce_order_item_product', $item->get_product(), $item );

				foreach($requested_products as $requested_product)
				{
					if(isset($requested_product['item_id']))
					{	
						if($item_id == $requested_product['item_id'])
						{
							if(isset($requested_product['variation_id']) && $requested_product['variation_id'] > 0)
							{
								$prod = wc_get_product($requested_product['variation_id']);

							}
							else
							{
								$prod = wc_get_product($requested_product['product_id']);
							}
							if(mwb_wrma_wc_dokan_activated()){
								$author = get_post_field( 'post_author',$requested_product['product_id'] );
								if ( ! user_can( $author, 'dokandar' ) ) {
									$is_seller = false;
								}
								else{
									$is_seller = true;
								}
								if($is_seller) {
									$seller_data = get_userdata($author);
									$mwb_vendor_emails[] = $seller_data->user_email;
								}
							}
							$subtotal = $requested_product['price']*$requested_product['qty'];
							$total += $subtotal;
							if( WC()->version < "3.1.0" )
							{
								$item_meta      = new WC_Order_Item_Meta( $item, $prod );
								$item_meta_html = $item_meta->display( true, true );
							}
							else
							{
								$item_meta      = new WC_Order_Item_Product( $item, $prod );
								$item_meta_html = wc_display_item_meta($item_meta,array('echo'=> false));
							}
							
							$product_table .='<tr>
							<td style="border: 2px solid #ddd; padding: 5px;">'.$item['name'].'<br>';
							$product_table .='<small>'.$item_meta_html.'</small>
							<td style="border: 2px solid #ddd; padding: 5px;">'.$requested_product['qty'].'</td>
							<td style="border: 2px solid #ddd; padding: 5px;">'.mwb_wrma_format_price($requested_product['price']*$requested_product['qty']).'</td>
							</tr>';
						}
					}
				}	
			}	
		}
		$product_table .='<tr>
			<th style="border: 2px solid #ddd; padding: 5px;" colspan="2">'.__('Refund Total', 'mwb-woocommerce-rma').':</th>
			<td style="border: 2px solid #ddd; padding: 5px;">'.mwb_wrma_format_price($total).'</td>
			</tr>
			</tbody>
			</table>';
		$message .= $product_table;

		$refundable_amount = wc_price($total);
		$message .='</div>
			<div class="Customer-detail">
			<h4>'.__('Customer details', 'mwb-woocommerce-rma').'</h4>
			<ul>
			<li><p class="info">
			<span class="bold">'.__('Email', 'mwb-woocommerce-rma').': </span>'.get_post_meta($order_id, '_billing_email', true).'
			</p></li>
			<li><p class="info">
			<span class="bold">'.__('Tel', 'mwb-woocommerce-rma').': </span>'.get_post_meta($order_id, '_billing_phone', true).'
			</p></li>
			</ul>
			</div>
			<div class="details">
			<div class="Shipping-detail">
			<h4>'.__('Shipping Address', 'mwb-woocommerce-rma').'</h4>
			'.$order->get_formatted_shipping_address().'
			</div>
			<div class="Billing-detail">
			<h4>'.__('Billing Address', 'mwb-woocommerce-rma').'</h4>
			'.$order->get_formatted_billing_address().'
			</div>
			<div class="clear"></div>
			</div>
			
			</div>
			<div class="footer" style="text-align:center;padding: 10px;">
			'.$mail_footer.'
			</div>
			
			</body>
			</html>';
		
		$headers = array();
		$headers[] = "Content-Type: text/html; charset=UTF-8";
		$to = get_option('mwb_wrma_notification_from_mail');
		$subject = get_option('mwb_wrma_notification_merchant_return_subject');
		$subject = str_replace('[order]', "#".$order_id, $subject);

		wc_mail( $to, $subject, $message, $headers );
		
		if(isset($mwb_vendor_emails) && is_array($mwb_vendor_emails) && !empty($mwb_vendor_emails))
		{
			$requested_products = $products[$date]['products'];          
			do_action('mwb_wrma_customer_refund_request_mail_for_vendor',$mwb_vendor_emails,$order_id,$reason,$reason_subject,$requested_products);
		}
		
		//Send mail to User that we recieved your request
		
		$fname = get_option('mwb_wrma_notification_from_name');
		$fmail = get_option('mwb_wrma_notification_from_mail');
		
		$to = get_post_meta($order_id, '_billing_email', true);
		$headers = array();
		$headers[] = "From: $fname <$fmail>";
		$headers[] = "Content-Type: text/html; charset=UTF-8";
		$subject = get_option('mwb_wrma_notification_return_subject');
		$subject = str_replace('[order]', "#".$order_id, $subject);
		$message = stripslashes(get_option('mwb_wrma_notification_return_rcv'));
		$message = apply_filters( 'mwb_wrma_meta_content', $message );

		////////////////shortcode replace variable start//////////////////////

		$fname = get_post_meta($order_id, '_billing_first_name', true);
		$lname = get_post_meta($order_id, '_billing_last_name', true);
		$billing_company = get_post_meta($order_id, '_billing_company', true);
		$billing_email = get_post_meta($order_id, '_billing_email', true);
		$billing_phone = get_post_meta($order_id, '_billing_phone', true);
		$billing_country = get_post_meta($order_id, '_billing_country', true);
		$billing_address_1 = get_post_meta($order_id, '_billing_address_1', true);
		$billing_address_2 = get_post_meta($order_id, '_billing_address_2', true);
		$billing_state = get_post_meta($order_id, '_billing_state', true);
		$billing_postcode = get_post_meta($order_id, '_billing_postcode', true);
		$shipping_first_name = get_post_meta($order_id, '_shipping_first_name', true);
		$shipping_last_name = get_post_meta($order_id, '_shipping_last_name', true);
		$shipping_company = get_post_meta($order_id, '_shipping_company', true);
		$shipping_country = get_post_meta($order_id, '_shipping_country', true);
		$shipping_address_1 = get_post_meta($order_id, '_shipping_address_1', true);
		$shipping_address_2 = get_post_meta($order_id, '_shipping_address_2', true);
		$shipping_city = get_post_meta($order_id, '_shipping_city', true);
		$shipping_state = get_post_meta($order_id, '_shipping_state', true);
		$shipping_postcode = get_post_meta($order_id, '_shipping_postcode', true);
		$payment_method_tittle = get_post_meta($order_id, '_payment_method_title', true);
		$order_shipping = get_post_meta($order_id, '_order_shipping', true);
		$order_total = get_post_meta($order_id, '_order_total', true);

		/////////////////////shortcode replace variable end///////////////////

		$fullname = $fname." ".$lname;
		
		$message = str_replace('[username]', $fullname, $message);
		$message = str_replace('[order]', "#".$order_id, $message);
		$message = str_replace('[siteurl]', home_url(), $message);
		$message = str_replace('[_billing_company]', $billing_company, $message);
		$message = str_replace('[_billing_email]', $billing_email, $message);
		$message = str_replace('[_billing_phone]', $billing_phone, $message);
		$message = str_replace('[_billing_country]', $billing_country, $message);
		$message = str_replace('[_billing_address_1]', $billing_address_1, $message);
		$message = str_replace('[_billing_address_2]', $billing_address_2, $message);
		$message = str_replace('[_billing_state]', $billing_state, $message);
		$message = str_replace('[_billing_postcode]', $billing_postcode, $message);
		$message = str_replace('[_shipping_first_name]', $shipping_first_name, $message);
		$message = str_replace('[_shipping_last_name]', $shipping_last_name, $message);
		$message = str_replace('[_shipping_company]', $shipping_company, $message);
		$message = str_replace('[_shipping_country]', $shipping_country, $message);
		$message = str_replace('[_shipping_address_1]', $shipping_address_1, $message);
		$message = str_replace('[_shipping_address_2]', $shipping_address_2, $message);
		$message = str_replace('[_shipping_city]', $shipping_city, $message);
		$message = str_replace('[_shipping_state]', $shipping_state, $message);
		$message = str_replace('[_shipping_postcode]', $shipping_postcode, $message);
		$message = str_replace('[_payment_method_title]', $payment_method_tittle, $message);
		$message = str_replace('[_order_shipping]', $order_shipping, $message);
		$message = str_replace('[_order_total]', $order_total, $message);
		$message = str_replace('[_refundable_amount]', $refundable_amount, $message);
		$message = str_replace('[formatted_shipping_address]', $order->get_formatted_shipping_address(), $message);
		$message = str_replace('[formatted_billing_address]', $order->get_formatted_billing_address(), $message);
		$message = str_replace('[_product_table]', $product_table, $message);
		
		$mail_header = stripslashes(get_option('mwb_wrma_notification_mail_header', false));
		$mail_header = apply_filters( 'mwb_wrma_meta_content', $mail_header );
		$mail_footer = stripslashes(get_option('mwb_wrma_notification_mail_footer', false));
		$mail_footer = apply_filters( 'mwb_wrma_meta_content', $mail_footer );

		$mail_header = str_replace('[username]', $fullname, $mail_header);
		$mail_header = str_replace('[order]', "#".$order_id, $mail_header);
		$mail_header = str_replace('[siteurl]', home_url(), $mail_header);

		$subject = str_replace('[username]', $fullname, $subject);
		$subject = str_replace('[order]', "#".$order_id, $subject);
		$subject = str_replace('[siteurl]', home_url(), $subject);
		
		$template = get_option('mwb_wrma_notification_return_template','no');
		if(isset($template) && $template == 'on')
		{
			$html_content = $message;
		}
		else
		{
			$html_content = '<html>
			<head>
			<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
			<meta name="viewport" content="width=device-width, initial-scale=1.0"/>
			</head>
			<body style="margin: 1% 0 0; padding: 0;">
			<table cellpadding="0" cellspacing="0" width="100%">
			<tr>
			<td style="text-align: center; margin-top: 30px; padding: 10px; color: #99B1D8; font-size: 12px;">
			'.$mail_header.'
			</td>
			</tr>
			<tr>
			<td>
			<table align="center" cellpadding="0" cellspacing="0" style="border-collapse: collapse; font-family:Open Sans; max-width: 600px; width: 100%;">
			<tr>
			<td style="padding: 36px 48px; width: 100%; background-color:#557DA1;color: #fff; font-size: 30px; font-weight: 300; font-family:helvetica;">'.$subject.'</td>
			</tr>
			<tr>
			<td style="width:100%; padding: 36px 48px 10px; background-color:#fdfdfd; font-size: 14px; color: #737373;">'.$message.'</td>
			</tr>
			</table>
			</td>
			</tr>
			<tr>
			<td style="text-align: center; margin-top: 30px; color: #99B1D8; font-size: 12px;">
			'.$mail_footer.'
			</td>
			</tr>				
			</table>
			
			</body>
			</html>';
		}
		update_post_meta( $order_id , 'mail_product_table' , $product_table);
		update_post_meta( $order_id , 'mail_product_refund_amount' , $refundable_amount);
		
		$mwb_wrma_restrict_mails = get_option( 'mwb_wrma_return_restrict_customer_mails', true );
		if( ! empty( $mwb_wrma_restrict_mails ) && 'yes' != $mwb_wrma_restrict_mails ) {	
			wc_mail($to, $subject, $html_content, $headers );
		}
		
		$order = new WC_Order($order_id);
		$order->update_status('wc-return-requested', 'User Request to Refund Product');
		
		$data= array(
			'status' => 200,
			'message' => 'Refund requested successfully.You have received a notification mail regarding this, Please check your mail.',
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

function get_refund_amount($order_id,$product_total) {
	$mwb_wrma_price_policy = get_option('mwb_wrma_price_policy' ,'none');

	if($mwb_wrma_price_policy == 'none'){
		echo $product_total;wp_die();
	}
	else{
		$mwb_wrma_enable_price_policy = get_option( 'mwb_wrma_enable_price_policy', 'no' );
		$mwb_wrma_enable_fixed_price_policy = get_option( 'mwb_wrma_enable_fixed_price_policy','no');
		
		if($mwb_wrma_price_policy == 'mwb_wrma_percentage' && $mwb_wrma_enable_price_policy == 'on'){
			$mwb_wrma_price_policy_array=array();
			$mwb_wrma_number_of_days = get_option( 'mwb_wrma_number_of_days', array() );
			$mwb_wrma_price_redumwb = get_option( 'mwb_wrma_price_redumwb', array() );
			foreach ($mwb_wrma_number_of_days as $key => $value) {
				foreach ($mwb_wrma_price_redumwb as $key1 => $value1) {
					if($key1===$key)
					{
						$mwb_wrma_price_policy_array[$value]=$value1;
					}
				}
			}
			ksort($mwb_wrma_price_policy_array);
			if ( !empty( $mwb_wrma_number_of_days ) ) {
				$order = wc_get_order($order_id);
				$order_date = $order->order_date;
				$order_date = strtotime( $order_date );
				$current_date = strtotime( current_time('Y-m-d h:i:s') );
				$date_dif = $current_date - $order_date;
				$date_dif = floor($date_dif/(60*60*24));
				foreach ($mwb_wrma_price_policy_array as $key => $value) {
					if ($date_dif > $key) {
						continue;
					}else{
						$product_total = $product_total - $product_total*$value/100;
						break;
					}
				}
			}
			return $product_total;
		}
		else if($mwb_wrma_price_policy == 'mwb_wrma_fixed' && $mwb_wrma_enable_fixed_price_policy == 'on'){
			$mwb_wrma_price_policy_array=array();
			$mwb_wrma_number_of_days = get_option( 'mwb_wrma_fixed_number_of_days', array() );
			$mwb_wrma_price_redumwb = get_option( 'mwb_wrma_fixed_price_redumwb', array() );
			foreach ($mwb_wrma_number_of_days as $key => $value) {
				foreach ($mwb_wrma_price_redumwb as $key1 => $value1) {
					if($key1===$key)
					{
						$mwb_wrma_price_policy_array[$value]=$value1;
					}
				}
			}
			ksort($mwb_wrma_price_policy_array);
			if ( !empty( $mwb_wrma_number_of_days ) ) {
				$order = wc_get_order($order_id);
				// print_r($order->get_items());
				$order_date = $order->order_date;
				$order_date = strtotime( $order_date );
				$current_date = strtotime( current_time('Y-m-d h:i:s') );
				$date_dif = $current_date - $order_date;
				$date_dif = floor($date_dif/(60*60*24));
				foreach ($mwb_wrma_price_policy_array as $key => $value) {
					if ($date_dif > $key) {
						continue;
					}else{
						$product_total = $product_total - $value;
						break;
					}
				}
			}
			return $product_total;
		}
		else{
			return $product_total;
		}
	}
}