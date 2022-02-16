<?php
function register_user($request)
{
	global $wpdb;
	$params = $request->get_params();
	$user_login		= $params["user_login"];	
	$user_email		= $params["user_email"];
	$user_phone		= $params["user_phone"];
	$user_pass		= $params["user_pass"];
	$pass_confirm 	= $params["user_pass_confirm"];

	$errors = array();

	require_once(ABSPATH . WPINC . '/registration.php');

	if(username_exists($user_login)) {
		// Username already registered
		// $errors= array('username_unavailable' =>  __('Username already taken'));
		$errors['username_used'] = 'Username already taken';
	}
	if(!validate_username($user_login)) {
		// invalid username
		$errors['username_invalid'] = 'Invalid username';
	} else if($user_login == '') {
		// empty username
		$errors['username_empty'] = 'Please enter a username';
	}
	if(!isset($params["user_email"]) && !isset($params["user_phone"])){
		$errors['user_detail_invalid'] = 'Please insert atleast one detail either email or phone';
	}
	if($user_pass == '') {
		// passwords do not match
		$errors['password_empty'] = 'Please enter a password';
	}
	if(isset($params["user_email"])) {
		if(!is_email($user_email)) {
			//invalid email
			$errors['email_invalid'] = 'Invalid email';
		}
		if(email_exists($user_email)) {
			//Email address already registered
			$errors['email_used'] = 'Email already registered';
		}
	}
	if(isset($params["user_phone"])) {
		if($params["user_phone"]=='') {
			$errors['phone_empty'] = 'Phone number field is empty.';
		}
		if(get_user_by_mobile($params["user_phone"])) {
			$errors['phone_used'] = 'Phone already registered by another user.';	
		}
		if(preg_match('/^[0-9]{10}+$/', $params['user_phone']))
		{
			$phone_number = $params['user_phone'];
		} else {
			$errors['invalid_phone'] = "Phone number is not valid";
		}
	} 
	
	if(empty($errors)) 
	{
		$args = array(
			'user_login'		=> $user_login,
			'user_pass'	 		=> $user_pass,
			'user_registered'	=> date('Y-m-d H:i:s'),
			'role'				=> 'customer'
		);

		if(isset($params["user_email"]))
		{
			$args['user_email']	= $user_email;
		}

		$new_user_id = wp_insert_user($args);

		if($new_user_id) 
		{
			update_user_meta( $new_user_id, 'user_activation_status', false );
			$otp = mt_rand(1000,9999);
			// $otp = 1111;
			$now = time();
			if(isset($params['user_phone'])) {
				
				update_user_meta( $new_user_id, 'phone_number', $phone_number );
				update_user_meta( $new_user_id, 'registered_with', "mobile_no" );

				$message = "$otp is your Kiana Fashion OTP. The otp expires within 3 mins. Do not share this code. For more info, logon to https://www.kianafashion.com.";
				// $message = "$otp is your Towntips OTP. The otp expires within 5 mins. Do not share this code. For more info, logon to www.towntips.in. - DREnNo5ZBOL";

				$msg = send_sms($phone_number,$message);
				if($msg->status=='success'){
					$sent = true;
				} else {
					$sent = false;
				}

			} elseif($user_email) {

				$message = '<p>Hello,'.$user_email.'</p>';
				$message .= '<p>Here is your OTP for varify your email. Please varify and activate your account for login.</p>';
				$message .= '<p><span style="display: inline;padding: 10px 25px;color: #fff;background: #2346ef;">'.$otp.'</span></p>';
				$message .= '<p>This OTP will be expire after 3 minutes.</p>';
				$message .= '<br><br><p>Thanks.</p>';

				$email = get_option('admin_email');
			  	$subject = "Varify Email";
			  	$to = $user_email;
				$headers = 'From: '. $email . "\r\n" . 'Reply-To: ' . $email . "\r\n";

				add_filter( 'wp_mail_content_type','wpse27856_set_content_type' );
				update_user_meta( $new_user_id, 'registered_with', "email_id" );
				$sent = wp_mail($to, $subject, $message, $headers);
			} 


			if($sent)
			{
				$token = array(
					'otp' => $otp,
					'time' => $now,
				); 
				update_user_meta( $new_user_id, 'api-activate_user', $token );
			}
			$data= array(
				'status' => 200,
				'user_id' => $new_user_id,
				'message' => 'User Created Sucessfully',
			);
			$response = new WP_REST_Response( $data );
			$response->set_status( 200 );
			return $response;
		}
		
	} else {
		$errors['status'] = 422;
		$errors['message'] = "Error in registration, Please check the details.";
		$response = new WP_REST_Response( $errors );
		$response->set_status( 422 );
		return $response;
	}
}

function resend_activation_otp($request){
	$params = $request->get_params();
	$user = get_user_by('login',$params['user_login']);
 	
 	if(!$user) {
 		$user = get_user_by('email',$params['user_login']);
 	}
 	if(!$user) {
 		$user = get_user_by('ID',$params['user_login']);
 	}
 	if(!$user) {
 		$user = get_user_by_mobile($params["user_login"]);
 	}
 	if(!$user) {
 		$data = array(
 			'status' => 422,
 			'message' => "No User available with specified detail, Please register.",
 		); 
 		$response = new WP_REST_Response( $data );
		$response->set_status( 422 );
		return $response;
 	}
 	$user_id = $user->ID;

 	$registered_with = get_user_meta( $user_id, 'registered_with', true );
	
	if($registered_with==''){
		if(get_user_meta( $user_id, 'phone_number', true ))
		{
			$registered_with='mobile_no';
		} else {
			$registered_with='email_id';
		}
	}

	update_user_meta( $new_user_id, 'user_activation_status', false );

	$otp = mt_rand(1000,9999);
	// $otp = 1111;
	$now = time();
	if($registered_with == 'email_id') {
		$user_email = $user->data->user_email;

		$message = '<p>Hello,'.$user_email.'</p>';
		$message .= '<p>Here is your OTP for varify your email. Please varify and activate your account for login.</p>';
		$message .= '<p><span style="display: inline;padding: 10px 25px;color: #fff;background: #2346ef;">'.$otp.'</span></p>';
		$message .= '<p>This OTP will be expire after 3 minutes.</p>';
		$message .= '<br><br><p>Thanks.</p>';

		$email = get_option('admin_email');
	  	$subject = "Varify Email";
	  	$to = $user_email;
		$headers = 'From: '. $email . "\r\n" . 'Reply-To: ' . $email . "\r\n";

		add_filter( 'wp_mail_content_type','wpse27856_set_content_type' );
		$sent = wp_mail($to, $subject, $message, $headers);
		$sent_to = "E-mail";
	} else {
		$phone_number = get_user_meta( $user_id, 'phone_number', true );
		$message = "$otp is your Kiana Fashion OTP. The otp expires within 3 mins. Do not share this code. For more info, logon to https://www.kianafashion.com.";
		// $message = "$otp is your Towntips OTP. The otp expires within 5 mins. Do not share this code. For more info, logon to www.towntips.in. - DREnNo5ZBOL";

		$msg = send_sms($phone_number,$message);
		if($msg->status=='success'){
			$sent = true;
		} else {
			$sent = false;
		}
		$sent_to = "Phone Number";
	}

	if($sent)
	{
		$token = array(
			'otp' => $otp,
			'time' => $now,
		); 
		update_user_meta( $user_id, 'api-activate_user', $token );
	}

	$data= array(
		'status' => 200,
		'message' => 'OTP Sent to '.$sent_to,
	);
	$response = new WP_REST_Response( $data );
	$response->set_status( 200 );
	return $response;


}
function active_user($request) 
{
	/*ini_set('display_errors', 1);
	ini_set('display_startup_errors', 1);
	error_reporting(E_ALL);*/
	$params = $request->get_params();

	$user_id = $params['user_id'];
	$otp = $params['user_otp'];
	$device_token = $params['device_token'];
	$device_type = $params['device_type'];

	$token = get_user_meta( $user_id, 'api-activate_user' , true );

	if(!$token){
		if(get_user_meta( $user_id, 'user_activation_status' , true )==true){
			$errors= array(
				'status' => 422,
				'message' => 'User already activated.',
			);
			$response = new WP_REST_Response( $errors );
			$response->set_status( 422 );
			return $response;
		}
	}
	
	$date_text  = date('d-m-Y h:i:s',$token['time']);
	$start_date = new DateTime($date_text);
	$cur_time = new DateTime();
	$since_start = $start_date->diff($cur_time);
	
	if($otp!=$token['otp']){
		$errors= array(
			'status' => 422,
			'message' => 'Please enter valid OTP.',
		);
		$response = new WP_REST_Response( $errors );
		$response->set_status( 422 );
		return $response;
	}
	if ($since_start->i>1) {
		$errors= array(
			'status' => 422,
			'message' => 'OTP is expired, Please contact admin for your account activation.',
		);
		$response = new WP_REST_Response( $data );
		$response->set_status( 422 );
		return $response;
	}

	if(empty($errors))
	{
		update_user_meta( $user_id, 'user_activation_status', true );
		delete_user_meta( $user_id, 'api-activate_user' );

		$user = get_user_by( 'ID', $user_id );
		$registered_with = get_user_meta( $user_id, 'registered_with', true );
		if($registered_with==''){
			if(get_user_meta( $user_id, 'phone_number', true ))
			{
				$registered_with='mobile_no';
			} else {
				$registered_with='email_id';
			}
		}
		$login = $user->data->user_login;
		$args = array(
			'body' => array(
				'username' => $login,
				'password' => $params['user_pass'],
			)
		);
		$auth_url = site_url().'/wp-json/jwt-auth/v1/token';
		$auth = wp_remote_post( $auth_url , $args );
		$auth_data = json_decode( $auth['body'] );

		update_user_meta( $user->ID, 'user_device_token', $device_token );
		update_user_meta( $user->ID, 'user_device_type', $device_type );
		$data= array(
			'status' => 200,
			'user_id' => $user->ID,
			'registered_with' => $registered_with,
			'user_name' => $auth_data->user_display_name,
			'token' => $auth_data->token,
			'message' => 'User Activated sucessfully.',
			// 'status' => 200,
			// 'message' => 'User Activated sucessfully.',
		);
		$response = new WP_REST_Response( $data );
		$response->set_status( 200 );
		return $response;
	}
}

function login_user($request)
{
	global $wpdb;
	$params = $request->get_params();
	$user_login = $params['user_login'];
	$device_token = $params['device_token'];
	$device_type = $params['device_type'];
	
	$errors = array();
	$user = get_user_by('login',$params['user_login']);
 	
 	if(!$user) {
 		$user = get_user_by('email',$params['user_login']);
 	}

 	if(!$user) {
 		$user = get_user_by_mobile($params["user_login"]);
 	}

	if(!$user) {
		// if the user name doesn't exist

		$errors['empty_username'] = 'Invalid email address/phone number';
		$data= array(
			'status' => 422,
			// 'message' => 'Invalid email address/phone number',
			'message' =>"No user registered with specified detail.",
		);
		if(preg_match('/^[0-9]{10}+$/', $params['user_login'])){
			$data['phone_invalid'] = 'Invalid phone';
		} else {
			$data['email_invalid'] = 'Invalid email';
		}

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

	$is_active = get_user_meta( $user->ID, 'user_activation_status', true ); 
	if($is_active===false || !$is_active)
	{
		$errors['user_inactive'] = 'Your Account is not active. Please contact admin regarding this.';
		$registered_with = get_user_meta( $user->ID, 'registered_with', true );
		// print_r($registered_with);

		if($registered_with==''){
			if(get_user_meta( $user->ID, 'phone_number', true ))
			{
				$registered_with='mobile_no';
			} else {
				$registered_with='email_id';
			}
		}
		update_user_meta( $user->ID, 'user_activation_status', false );

		$otp = mt_rand(1000,9999);
		// $otp = 1111;
		$now = time();
		if($registered_with == 'email_id') {
			$user_email = $user->data->user_email;

			$message = '<p>Hello,'.$user_email.'</p>';
			$message .= '<p>Here is your OTP for varify your email. Please varify and activate your account for login.</p>';
			$message .= '<p><span style="display: inline;padding: 10px 25px;color: #fff;background: #2346ef;">'.$otp.'</span></p>';
			$message .= '<p>This OTP will be expire after 3 minutes.</p>';
			$message .= '<br><br><p>Thanks.</p>';

			$email = get_option('admin_email');
		  	$subject = "Varify Email";
		  	$to = $user_email;
			$headers = 'From: '. $email . "\r\n" . 'Reply-To: ' . $email . "\r\n";

			add_filter( 'wp_mail_content_type','wpse27856_set_content_type' );
			$sent = wp_mail($to, $subject, $message, $headers);
			$sent_to = "E-mail";
		} else {
			$phone_number = get_user_meta( $user->ID, 'phone_number', true );
			$message = "$otp is your Kiana Fashion OTP. The otp expires within 3 mins. Do not share this code. For more info, logon to https://www.kianafashion.com.";
			// $message = "$otp is your Towntips OTP. The otp expires within 5 mins. Do not share this code. For more info, logon to www.towntips.in. - DREnNo5ZBOL";

			$msg = send_sms($phone_number,$message);
			if($msg->status=='success'){
				$sent = true;
			} else {
				$sent = false;
			}
			$sent_to = "Phone Number";
		}
		if($sent)
		{
			$token = array(
				'otp' => $otp,
				'time' => $now,
			); 
			update_user_meta( $user->ID, 'api-activate_user', $token );
		}
		$data= array(
			'status' => 422,
			'user_id' => $user->ID,
			'message' => 'Your account is not active. Activation OTP has been sent to your mobile number. Please activate your account.',
		);
		$response = new WP_REST_Response( $data );
		$response->set_status( 200 );
		return $response;
	}


	if(empty($errors)) {
		$registered_with = get_user_meta( $user->ID, 'registered_with', true );
		if($registered_with==''){
			if(get_user_meta( $user->ID, 'phone_number', true ))
			{
				$registered_with='mobile_no';
			} else {
				$registered_with='email_id';
			}
		}
		$login = $user->data->user_login;
		$args = array(
			'body' => array(
				'username' => $login,
				'password' => $params['user_pass'],
			)
		);
		$auth_url = site_url().'/wp-json/jwt-auth/v1/token';
		$auth = wp_remote_post( $auth_url , $args );
		$auth_data = json_decode( $auth['body'] );


		/*$url = "https://staging.kianafashion.com/wp-json/pd/fcm/subscribe";
		$data = array(
			'api_secret_key' => 'tnx&q#fVJISFx!RaZ7ZL#EQ3',
			'user_email' => $user->data->user_email,
			'device_token' => $device_token,
			'subscribed' => 'android',
		);
		$query_url = $url.'?'.http_build_query($data);
		$response = wp_remote_get($query_url);*/

		$ex_user_ids = $wpdb->get_results("SELECT user_id FROM {$wpdb->prefix}usermeta WHERE meta_key LIKE 'user_device_token' AND meta_value LIKE '".$device_token."'");

		foreach ($ex_user_ids as $key => $ex_uids) {
			delete_user_meta( $ex_uids->user_id, 'user_device_token' );
			delete_user_meta( $ex_uids->user_id, 'user_device_type' );
			/*print_r($ex_uids);
			print_r(get_user_meta( $ex_uids, $key, $single );)*/
		}

		update_user_meta( $user->ID, 'user_device_token', $device_token );
		update_user_meta( $user->ID, 'user_device_type', $device_type );

		$data= array(
			'status' => 200,
			'user_id' => $user->ID,
			'registered_with' => $registered_with,
			'user_name' => $auth_data->user_display_name,
			'token' => $auth_data->token,
			'message' => 'User login done',
		);
		$response = new WP_REST_Response( $data );
		$response->set_status( 200 );
		return $response;
	}
}
function forget_pass($request)
{
	$params = $request->get_params();
	$errors = array();
	
	$is_mobile = preg_match('/^[0-9]{10}+$/', $params['user_login']);

	$user = get_user_by('email',$params['user_login']);

 	if(!$user) {
 		$user = get_user_by('login',$params['user_login']);
 	}
 	if(!$user) {
 		$user = get_user_by_mobile($params["user_login"]);
 	}
	
	if(!$user)
	{
		$data= array(
			'status' => 422,
			'message' => 'No user regitered with specified detail.',
		);
		$response = new WP_REST_Response( $data );
		$response->set_status( 422 );
		return $response;
	} else {
		$token = array();
		$otp = mt_rand(1000,9999);
		// $otp = 1111;
		$now = time();

		if($is_mobile==false)
		{
			$message = '<p>Hello ,'.$user->data->display_name.'</p>';
			$message .= '<p>We have received request for reseting your account password registered with kiana fashion, Here is your OTP to reset your password</p>';
			$message .= '<p><span style="display: inline;padding: 10px 25px;color: #fff;background: #2346ef;">'.$otp.'</span></p>';
			$message .= '<p>This OTP will be expire after 3 minutes.</p>';
			$message .= '<br><br><p>Thanks.</p>';

			$email = get_option('admin_email');
		  	$subject = "Reset Password";
		  	// $to = $params['user_login'];
		  	$to = $user->user_email;
			$headers = 'From: '. $email . "\r\n" . 'Reply-To: ' . $email . "\r\n";

			add_filter( 'wp_mail_content_type','wpse27856_set_content_type' );
			$sent = wp_mail($to, $subject, $message, $headers);
			$sent_on = "OTP Sent on E-mail";
		} else {
			// $params["user_login"];
			$message = "$otp is your Kiana Fashion OTP. The otp expires within 3 mins. Do not share this code. For more info, logon to https://www.kianafashion.com.";
			// $message = "$otp is your Towntips OTP. The otp expires within 5 mins. Do not share this code. For more info, logon to www.towntips.in. - DREnNo5ZBOL";

			$msg = send_sms($params["user_login"],$message);
			if($msg->status=='success'){
				$sent = true;
			} else {
				$sent = false;
			}
			$sent_on = "OTP Sent on Mobile";
		}
		
		if($sent)
		{
			$token = array(
				'otp' => $otp,
				'time' => $now,
			); 
			update_user_meta( $user->ID, 'api-reset-pass', $token );

			$data= array(
				'status' => 200,
				'user_id' => $user->ID,
				'message' => $sent_on,
			);
			$response = new WP_REST_Response( $data );
			$response->set_status( 200 );
			return $response;
		}
	}
	
	
}

function varify_otp($request)
{
	$params = $request->get_params();
	$user_id = $params['user_id'];
	$user = get_user_by( 'ID', $user_id );
	/*print_r($user);
	die()*/
	$otp = $params['user_otp'];

	$token = get_user_meta( $user->ID, 'api-reset-pass' , true );

	$date_text  = date('d-m-Y h:i:s',$token['time']);

	$start_date = new DateTime($date_text);
	$cur_time = new DateTime();
	$since_start = $start_date->diff($cur_time);
	if($otp!=$token['otp']){
		$errors= array('otp_invalid' => 'Please enter valid OTP.');
		$data= array(
			'status' => 422,
			'message' => 'Please enter valid OTP.',
		);
		$response = new WP_REST_Response( $data );
		$response->set_status( 422 );
		return $response;
	}
	if ($since_start->i>3) {
		$errors= array('otp_expire' => 'OTP is expired please regenrate.');
		delete_user_meta( $user->ID, 'api-reset-pass' );
		$data= array(
			'status' => 422,
			'message' => 'OTP is expired please regenrate.',
		);
		$response = new WP_REST_Response( $data );
		$response->set_status( 422 );
		return $response;
	}

	
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
function change_password($request) 
{
	$params = $request->get_params();
	$new_pass = $params['new_password'];

	if(isset($params['is_forget_pass']))
	{
		$user_id = $params['user_id'];
		$user = get_user_by( 'ID', $user_id );
		$token = get_user_meta( $user->ID, 'api-reset-pass', true );
		// print_r($token);
		if($token['is_verified'])
		{
			$date_text  = date('d-m-Y h:i:s',$token['verified_at']);
			$start_date = new DateTime($date_text);
			$cur_time = new DateTime();
			$since_start = $start_date->diff($cur_time);
			if ($since_start->i>5) {
				$errors= array('otp_expire' =>  __('Your OTP is expired Please Regenrate.'));
				$data= array(
					'status' => 422,
					'message' => 'OTP is expired please regenrate.',
				);
				$response = new WP_REST_Response( $data );
				$response->set_status( 422 );
				return $response;
			}

			// $errors = api_errors()->get_error_messages();
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
		} else {
			$data= array(
				'status' => 422,
				'message' => 'No reset request found for this user.',
			);
			$response = new WP_REST_Response( $data );
			$response->set_status( 422 );
			return $response;
		}
	}
	else 
	{
		$header = $request->get_headers();
		if(!isset($header['authorization'][0]) || $header['authorization'][0]=='')
		{
	  		$errors['invalid_user'] = 'User is not logedin';
		}
		$token = str_replace('Bearer ', '', $header['authorization'][0]);
		$user_id = validate_token($token);
		$user = get_user_by('id',$user_id);

		if(isset($params['old_password']) && $params['old_password']!='')
		{
			$old_pass = $params['old_password'];
			if(wp_check_password($params['old_password'], $user->user_pass, $user->ID))
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
		} else {
			$data= array(
				'status' => 422,
				'message' => 'Old Password Filed Is blank.',
			);
			$response = new WP_REST_Response( $data );
			$response->set_status( 422 );
			return $response;
		}
	}
}


function user_profile_detail($request) {
	
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
	$user = get_user_by('id',$user_id);
	$user_meta = get_user_meta( $user_id );
	$first_name = get_user_meta( $user_id, 'first_name', true );
	$last_name = get_user_meta( $user_id, 'last_name', true );
	// $order_count = get_user_meta( $user_id, '_order_count', true );
	$order_count = wc_get_customer_order_count($user_id);
	$profile_pic = get_user_meta( $user_id, 'profile_iamge', true );
	$registered_with = get_user_meta( $user_id, 'registered_with', true );
	if($registered_with==''){
		if(get_user_meta( $user_id, 'phone_number', true ))
		{
			$registered_with='mobile_no';
		} else {
			$registered_with='email_id';
		}
	}
	$user_phone = get_user_meta( $user_id, 'phone_number', true );
	// 
	$coupon_code = get_user_meta( $user_id , 'mwb_wrma_refund_wallet_coupon' , true);
	$the_coupon = new WC_Coupon( $coupon_code );
	$customer_coupon_id = $the_coupon->get_id();
	$wallet_amount = get_post_meta( $customer_coupon_id, 'coupon_amount', true );
	$shipping_address_count = 0;

	$user_login = $user->data->user_login;
	$user_email = $user->data->user_email;
	$display_name = $user->data->display_name;
	if(get_user_meta($user_id, 'kiana_shipping_address', true)) {
		$shipping_address_count = sizeof(get_user_meta( $user_id, 'kiana_shipping_address', true ));
	} 
	$wl_list = $wpdb->get_row("SELECT * FROM {$wpdb->prefix}yith_wcwl_lists WHERE user_id=".$user_id,ARRAY_A);
	$wl_count = $wpdb->get_var("SELECT COUNT(prod_id) FROM {$wpdb->prefix}yith_wcwl WHERE wishlist_id=".$wl_list['ID']);
	$wl_counts = ($wl_count) ? $wl_count : 0;

	$notification_settings = get_user_meta( $user_id, 'notification_settings', true );
	if(empty($notification_settings)) {
		$notification_settings = array(
			"sale_notification" => false,
			"new_product_notification" => false,
			"order_notification" => false,
		);
	}

	$args = array(
		'author_email' => $user->data->user_email,
	);
	$comments = get_comments($args);
	$comment_counts = 0;
	if(is_array($comments)){
		$comment_counts = sizeof($comments);
	}

	$user_detail = array(
		'user_login' => $user_login,
		'display_name' => $display_name,
		'first_name' => $first_name,
		'last_name' => $last_name,
		'email' => $user_email,
		'user_image' => $profile_pic['url'],
		'registered_with' => $registered_with,
		'user_phone' => $user_phone,
		'total_order' => $order_count,
		'wishlist_count' => $wl_counts,
		'review_count' => $comment_counts,
		'wallet_coupon_code' => $coupon_code,
		'wallet_amount' => $wallet_amount,
		'shipping_address_count' => $shipping_address_count,
		'notifications' => $notification_settings,
	);

	$response = new WP_REST_Response( $user_detail );
	$response->set_status( 200 );
	return $response;
}

function update_profile($request)
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
	
	$user = get_user_by('ID',$user_id);
	$user_email = $user->data->user_email;

	if(isset($params['email']) && $params['email']!='')
	{
		if($user_email!=$params['email'])
		{
			if(!is_email($params['email'])) {
			//invalid email
				$errors['email_invalid'] = 'Invalid email';
			}
			if(email_exists($params['email'])) {
				//Email address already registered
				$errors['email_used'] = 'Email already registered';
			}

		}
	}
	if(isset($params['first_name']) && $params['first_name']!=''){
		$first_name = $params['first_name'];
	} /*else {
		$errors['first_name_empty'] = 'First Name is empty';
	}*/
	if(isset($params['last_name']) && $params['last_name']!=''){
		$last_name = $params['last_name'];
	} /*else {
		$errors['last_name_empty'] = 'Last Name is empty';
	}*/
	if(isset($params['display_name'])){
		$display_name = $params['display_name'];
	}
	// if(isset($params['phone_number']) && $params['phone_number']!='' && preg_match('/^[0-9]{10}+$/', $params['phone_number']))
	if(isset($params['phone_number']) && $params['phone_number']!='')
	{
		if(preg_match('/^[0-9]{10}+$/', $params['phone_number']))
		{
			$phone = $params['phone_number'];
		} else {
			$errors['invalid_phone'] = "Phone Number is not valid";
		}
	}

	$query = "SELECT $wpdb->users.ID  FROM $wpdb->users  INNER JOIN $wpdb->usermeta ON ( $wpdb->users.ID = $wpdb->usermeta.user_id )  INNER JOIN $wpdb->usermeta AS mt1 ON ( $wpdb->users.ID = mt1.user_id ) WHERE 1=1  AND 
          ( $wpdb->usermeta.meta_key = 'phone_number' AND $wpdb->usermeta.meta_value = $phone_number ) ";

    $existing_uid = $wpdb->get_var($query);
    
    if($existing_uid && $user_id!=$existing_uid){
    	$errors['phone_used'] = "Phone Number is already registered with another user.";
    }

  	if(isset($_FILES['profile_pic']) && $_FILES['profile_pic']['error']==0)
  	{
  		
		$file_name = $_FILES['profile_pic']['name'];
		$file_temp = $_FILES['profile_pic']['tmp_name'];
		$filetype = wp_check_filetype($file_name);

		if($filetype['ext']=='png' || $filetype['ext']=='jpg' || $filetype['ext']=='jpeg')
		{
			$filename = basename( $file_name );
			$upload_dir = wp_upload_dir();
			$image_data = file_get_contents( $file_temp );
			
			if ( wp_mkdir_p( $upload_dir['path'] ) ) {
			  $file = $upload_dir['path'] . '/' . $file_name;
			  $file_url = $upload_dir['url'] . '/' . $file_name;
			}
			else {
			  $file = $upload_dir['basedir'] . '/' . $file_name;
			  $file_url = $upload_dir['baseurl'] . '/' . $file_name;
			}

			file_put_contents( $file, $image_data );
			$wp_filetype = wp_check_filetype( $file_name, null );

			$attachment = array(
			  'post_mime_type' => $wp_filetype['type'],
			  'post_title' => sanitize_file_name( $filename ),
			  'post_content' => '',
			  'guid' => esc_url_raw($file_url),
			  'post_status' => 'inherit'
			);

			$attach_id = wp_insert_attachment( $attachment, $file );
			require_once( ABSPATH . 'wp-admin/includes/image.php' );
			$attach_data = wp_generate_attachment_metadata( $attach_id, $file );
			wp_update_attachment_metadata( $attach_id, $attach_data );
		} else {
			$errors['invalid_iamge_format']	= "Image file is not in proper formate, Please upload png, jpg or jpeg file";
		}
	}

	if(empty($errors)){
		$args['ID'] = $user_id;
		if($user_email!=$params['email'])
		{
			$args['user_email'] = $params['email'];
		}
		if($params['display_name']!='') {
			$args['display_name'] = $params['display_name'];
		}

		wp_update_user( $args );
		update_user_meta( $user_id, 'first_name', $first_name );
		update_user_meta( $user_id, 'last_name', $last_name );
		if(isset($params['phone_number']) && $params['phone_number']!='')
		{
			update_user_meta( $user_id, 'phone_number', $phone );
		}
		
		if(isset($_FILES['profile_pic']) && $_FILES['profile_pic']['error']==0) {
			$profile_image = array(
				'id' => $attach_id,
				'url' => $file_url,
			);
			update_user_meta( $user_id, 'profile_iamge', $profile_image );
		}

		$data= array(
			'status' => 200,
			'message' => 'Profile Edited Sucessfully.',
		);
		$response = new WP_REST_Response( $data );
		$response->set_status( 200 );
		return $response;

	} else {
		$errors['status'] = 422;
		$response = new WP_REST_Response( $errors );
		$response->set_status( 422 );
		return $response;
	}
}
function test_fun()
{
	if(isset($_GET['test']) && $_GET['test']=='true')
	{
		// $user = get_user_by( 'id',  );
		// global $wpdb;
		// $total_items = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}yith_wcwl WHERE wishlist_id=88");
		// $wl_list = $wpdb->get_row("SELECT * FROM {$wpdb->prefix}yith_wcwl_lists WHERE user_id=".$_GET['id'],ARRAY_A);
		// $wl_list2 = $wpdb->get_row("SELECT * FROM {$wpdb->prefix}yith_wcwl_lists WHERE ID=88",ARRAY_A);
		// echo "<pre>";
		// print_r($wl_list);
		// print_r($wl_list2);
		// print_r(get_user_meta( 2736 ));
		// print_r(get_device_ids('new_product_notification'));
		// // print_r(get_user_by( $field, $value );)
		// // get_user_by('email','u.anandjiwala@aecordigital.com');
		// // $user = get_user_by_mobile(9724210884);
		// // print_r($user);
		// // ;( 2805 )
		// // $user = get_user_by( 'id', 2805 );
		// print_r($total_items);
		// print_r(get_user_meta( $_GET['id'] ));
		// echo "</pre>";
		
		// die();
		foreach (get_users() as $key => $user) {
		    delete_user_meta( $user->data->ID, 'shipping_address');
		    // print_r(get_user_meta( $user->data->ID,'user_activation_status'));
		  }
	}
}
add_action('init','test_fun');