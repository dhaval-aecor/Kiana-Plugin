<?php
function register_user($request)
{
	$user_login		= $_POST["user_login"];	
	$user_email		= $_POST["user_email"];
	$user_pass		= $_POST["user_pass"];
	$pass_confirm 	= $_POST["user_pass_confirm"];

	$errors = array();

	require_once(ABSPATH . WPINC . '/registration.php');

	if(username_exists($user_login)) {
		// Username already registered
		// api_errors()->add('username_unavailable', __('Username already taken'));
		$errors['username_unavailable'] = 'Invalid username';
	}
	if(!validate_username($user_login)) {
		// invalid username
		$errors['username_invalid'] = 'Invalid username';
	} else if($user_login == '') {
		// empty username
		$errors['username_empty'] = 'Please enter a username';
	}
	if(!is_email($user_email)) {
		//invalid email
		$errors['email_invalid'] = 'Invalid email';
	}
	if(email_exists($user_email)) {
		//Email address already registered
		$errors['email_used'] = 'Email already registered';
	}
	if($user_pass == '') {
		// passwords do not match
		$errors['password_empty'] = 'Please enter a password';
	}

	if(empty($errors)) {
		
		$new_user_id = wp_insert_user(array(
				'user_login'		=> $user_login,
				'user_pass'	 		=> $user_pass,
				'user_email'		=> $user_email,
				'user_registered'	=> date('Y-m-d H:i:s'),
				'role'				=> 'customer'
			)
		);
		
		if($new_user_id) {
			$data= array(
				'status' => 200,
				'user_id' => $new_user_id,
				'message' => 'User Created Sucessfully',
			);
			$response = new WP_REST_Response( $data );
			$response->set_status( 200 );
			return $response;
			die();
		}
		
	} else {
		$response = new WP_REST_Response( $errors );
		$response->set_status( 422 );
		return $response;
	}
}

function login_user($request)
{
	$params = $request->get_params();
	$user_login = $params['user_login'];
	$user = get_user_by('login',$params['user_login']);
	$errors = array();
 	if(!$user) {
 		$user = get_user_by('email',$params['user_login']);
 	}
	if(!$user) {
		// if the user name doesn't exist
		$errors['empty_username'] = 'Invalid username or email';
		$data= array(
			'status' => 422,
			'message' => 'Invalid username or email',
		);
		$response = new WP_REST_Response( $data );
		$response->set_status( 422 );
		return $response;
	}

	if(!isset($params['user_pass']) || $params['user_pass'] == '') {
		// if no password was entered
		$errors['empty_password'] = 'Please enter a password';
		$data= array(
			'status' => 422,
			'message' => 'Please enter a password',
		);
		$response = new WP_REST_Response( $data );
		$response->set_status( 200 );
		return $response;
	}

	// check the user's login with their password
	if(!wp_check_password($params['user_pass'], $user->user_pass, $user->ID)) {
		// if the password is incorrect for the specified user
		$errors['empty_password'] = 'Incorrect password';
		$data= array(
			'status' => 422,
			'message' => 'Incorrect password',
		);
		$response = new WP_REST_Response( $data );
		$response->set_status( 200 );
		return $response;
	}



	if(empty($errors)) {
		/*$args = array(
			'username' => ,
			'password' => '',
		);
		$auth_url = site_url().'/wp-json/jwt-auth/v1/token';
		$auth = wp_remote_post( $auth_url , $args );*/

		$data= array(
			'status' => 200,
			'user_id' => $user->ID,
			'message' => 'User login done',
		);
		$response = new WP_REST_Response( $data );
		$response->set_status( 200 );
		return $response;
	}
}
function forget_pass($request)
{
	$user = get_user_by('email',$_POST['user_email']);
	if(!$user)
	{
		$data= array(
			'status' => 422,
			'message' => 'No user regitered with specified email.',
		);
		$response = new WP_REST_Response( $data );
		$response->set_status( 422 );
		return $response;
	}
	
	
	$token = array();
	$otp = mt_rand(1000,9999);
	$now = time();

	$message = '<p>Hello ,'.$user->data->display_name.'</p>';
	$message .= '<p>We have received request for reseting your account password registered with kiana fashion, Here is your OTP to reset your password</p>';
	$message .= '<p><span style="display: inline;padding: 10px 25px;color: #fff;background: #2346ef;">'.$otp.'</span></p>';
	$message .= '<p>This OTP will be expire after 3 minutes.</p>';
	$message .= '<br><br><p>Thanks.</p>';

	$email = get_option('admin_email');
  	$subject = "Reset Password";
  	$to = $_POST['user_email'];
	$headers = 'From: '. $email . "\r\n" . 'Reply-To: ' . $email . "\r\n";

	add_filter( 'wp_mail_content_type','wpse27856_set_content_type' );
	$sent = wp_mail($to, $subject, $message, $headers);
	if($sent)
	{
		$token = array(
			'otp' => $otp,
			'time' => $now,
		); 
		update_user_meta( $user->ID, 'api-reset-pass', $token );

		$data= array(
			'status' => 200,
			'message' => 'OTP Sent to mail id.',
		);
		$response = new WP_REST_Response( $data );
		$response->set_status( 200 );
		return $response;
	}
}

function varify_otp($request)
{
	$user_email = $_POST['user_email'];
	$user = get_user_by( 'email', $user_email );
	$otp = $_POST['user_otp'];

	$token = get_user_meta( $user->ID, 'api-reset-pass' , true );

	$date_text  = date('d-m-Y h:i:s',$token['time']);

	$start_date = new DateTime($date_text);
	$cur_time = new DateTime();
	$since_start = $start_date->diff($cur_time);

	if($otp!=$token['otp']){
		api_errors()->add('otp_invalid', __('Please enter valid OTP.'));
		$data= array(
			'status' => 422,
			'message' => 'Please enter valid OTP.',
		);
		$response = new WP_REST_Response( $data );
		$response->set_status( 422 );
		return $response;
	}
	if ($since_start->i>3) {
		api_errors()->add('otp_expire', __('OTP is expired please regenrate.'));
		delete_user_meta( $user->ID, 'api-reset-pass' );
		$data= array(
			'status' => 422,
			'message' => 'OTP is expired please regenrate.',
		);
		$response = new WP_REST_Response( $data );
		$response->set_status( 422 );
		return $response;
	}

	$errors = api_errors()->get_error_messages();
	if(empty($errors))
	{
		$token['is_verified'] = true;
		$token['verified_at'] = time();
		update_user_meta( $user->ID, 'api-reset-pass', $token );

		$data= array(
			'status' => 200,
			'message' => 'OTP Verified.',
		);
		$response = new WP_REST_Response( $data );
		$response->set_status( 200 );
		return $response;
	}
}
function change_password($request) {
	$user_email = $_POST['user_email'];
	$new_pass = $_POST['new_password'];
	$old_pass = $_POST['old_password'];
	$user = get_user_by( 'email', $user_email );

	if(isset($_POST['is_forget_pass']))
	{
		$token = get_user_meta( $user->ID, 'api-reset-pass', true );
		if($token['is_verified'])
		{
			$date_text  = date('d-m-Y h:i:s',$token['verified_at']);
			$start_date = new DateTime($date_text);
			$cur_time = new DateTime();
			$since_start = $start_date->diff($cur_time);
			if ($since_start->i>5) {
				api_errors()->add('otp_expire', __('Your OTP is expired Please Regenrate.'));
				$data= array(
					'status' => 422,
					'message' => 'OTP is expired please regenrate.',
				);
				$response = new WP_REST_Response( $data );
				$response->set_status( 422 );
				return $response;
			}

			$errors = api_errors()->get_error_messages();
			if(empty($errors))
			{
				wp_set_password( $new_pass ,$user->ID );
				delete_user_meta( $user->ID, 'api-reset-pass' );

				$data= array(
					'status' => 200,
					'message' => 'Password Chnaged Sucessfully.',
				);
				$response = new WP_REST_Response( $data );
				$response->set_status( 200 );
				return $response;
			}
		}
	}
	if(isset($_POST['old_password']) && $_POST['old_password']!='')
	{
		if(wp_check_password($_POST['old_password'], $user->user_pass, $user->ID))
		{
			wp_set_password( $new_pass ,$user->ID );
			$data= array(
				'status' => 200,
				'message' => 'Password Chnaged Sucessfully.',
			);
			$response = new WP_REST_Response( $data );
			$response->set_status( 200 );
			return $response;
		} else {
			$data= array(
				'status' => 422,
				'message' => 'Old pass not verified.',
			);
			$response = new WP_REST_Response( $data );
			$response->set_status( 422 );
			return $response;
		}
	}
}


function user_porfile_detail($request) {
	
	global $wpdb;
	$params = $request->get_params();
	$user_id = $params['user_id'];
	$user = get_user_by('id',$user_id);
	$user_meta = get_user_meta( $user_id );
	$first_name = get_user_meta( $user_id, 'first_name', true );
	$last_name = get_user_meta( $user_id, 'last_name', true );
	$order_count = get_user_meta( $user_id, '_order_count', true );
	$user_login = $user->data->user_login;
	$user_email = $user->data->user_email;
	$shipping_address_count = sizeof(get_user_meta( $user_id, 'shipping_address', true ));
	$wl_list = $wpdb->get_row("SELECT * FROM {$wpdb->prefix}yith_wcwl_lists WHERE user_id=".$user_id,ARRAY_A);
	$wl_count = $wpdb->get_var("SELECT COUNT(prod_id) FROM {$wpdb->prefix}yith_wcwl WHERE wishlist_id=".$wl_list['ID']);

	$args = array(
		'author_email' => $user->data->user_email,
	);
	$comments = get_comments($args);
	$comment_counts = sizeof($comments);

	$user_detail = array(
		'user_login' => $user_login,
		'first_name' => $first_name,
		'last_name' => $last_name,
		'email' => $user_email,
		'user_image' => '',
		'total_order' => $order_count,
		'wishlist_count' => $wl_count,
		'review_count' => $comment_counts,
		'shipping_address_count' => $shipping_address_count,
	);

	$response = new WP_REST_Response( $user_detail );
	$response->set_status( 200 );
	return $response;
}