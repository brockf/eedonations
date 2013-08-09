<?php

/**
=====================================================
 EEDonations
-----------------------------------------------------
 http://www.eedonations.com/
-----------------------------------------------------
 Copyright (c) 2011, Electric Function, Inc.
 Use this software at your own risk.  Electric
 Function, Inc. assumes no responsibility for
 loss or damages as a result of using this software.

 This software is copyrighted.
=====================================================
*/

/**
* EEDonations Module
*
* Enables frontend template tags:
*   - {exp:eedonations:update_form}
*	- {exp:eedonations:donate_form}
*	- {exp:eedonations:billing_address}
*	- {exp:eedonations:subscriptions}{/exp:eedonations:subscriptions} e.g. {exp:eedonations:subscriptions id="X" date_format="Y-m-d"}
*	- {exp:eedonations:payments}{/exp:eedonations:payments} e.g. {exp:eedonations:payments id="X" subscription_id="X" offset="X" limit="X" date_format="Y-m-d"}
*	- {exp:eedonations:cancel id="X"}{/exp:eedonations:cancel} (returns {if cancelled} and {if failed} to tagdata)
*	- {exp:eedonations:receipt}{/exp:eedonations:receipt}
*
* @author Electric Function, Inc.
* @package OpenGateway
*/

class Eedonations {
	var $return_data = '';
	var $eedonations_class; // holds the EEDonations class
	var $EE; // holds the EE superobject

    /**
    * Constructor
    */
    function __construct ()
    {
		// initialize EEdonations class
		if (!class_exists('EEDonations_class')) {
			require(dirname(__FILE__) . '/class.eedonations.php');
		}
		$this->eedonations_class = new EEDonations_class;

		// load config
		$this->config = $this->eedonations_class->GetConfig();

		// load EE superobject
		$this->EE =& get_instance();

		// load language file
		$this->EE->lang->loadfile('eedonations');

		// just in case they don't specify a method, we'll let them know
		$this->return_data = "You must reference a specific function of the plugin, ie. {exp:eedonations:function_to_call}";
    }

    /**
    * Print error
    *
    * @param string $error The text of the error
    *
    * @return string HTML-formatted error text
    */
    function error ($error) {
    	return '<p class="error">' . $error . '</p>';
    }

    /**
    * Custom Fields
    *
    * Retrieve custom fields for a payment or subscription
    *
    * @param int $donation_id
    * @param int $subscription_id
    *
    * @return custom fields
    */
    function custom_fields () {
    	$donation_id = $this->EE->TMPL->fetch_param('donation_id');
    	$subscription_id = $this->EE->TMPL->fetch_param('subscription_id');

    	// get custom field data
		if (!empty($subscription_id)) {
			$this->EE->db->where('recurring_id', $subscription_id);
		}
		else {
			$this->EE->db->where('payment_id', $donation_id);
		}

		$result = $this->EE->db->get('exp_eedonations_custom_fields');

		$custom_fields = array();

		if ($result->num_rows()) {
			foreach ($result->result_array() as $field) {
				$custom_fields[$field['custom_field_name']] = $field['custom_field_value'];
				$custom_fields[strtolower(str_replace(' ','_',$field['custom_field_name']))] = $field['custom_field_value'];
			}
		}

		$variables = array();
		$variables[0] = $custom_fields;

		// get template tag info
		$return = $this->EE->TMPL->tagdata;

		// swap in the variables
		$return = $this->EE->TMPL->parse_variables($return, $variables);

		$this->return_data = $return;
		return $this->return_data;
    }

    /**
    * Cancels an active subscription
    *
    * @return boolean TRUE upon success, FALSE upon failure
    */
    function cancel () {
    	$id = $this->EE->TMPL->fetch_param('id');

    	if (empty($id)) {
    		return 'You are missing the required "id" parameter to specify which subscription to cancel.';
    	}

    	// can this person cancel this sub?
    	$filters = array(
    					'member_id' => $this->EE->session->userdata('member_id'),
    					'active' => '1',
    					'id' => $id
    				);
    	$subscriptions = $this->eedonations_class->GetSubscriptions(0,1,$filters);

    	$owns_sub = FALSE;
    	foreach ((array)$subscriptions as $subscription) {
    		if ($subscription['id'] == $id) {
    			$owns_sub = TRUE;
    		}
    	}

    	$cancelled_sub = FALSE;

    	if ($owns_sub == TRUE and $this->eedonations_class->CancelSubscription($id) == TRUE) {
    		$cancelled_sub = TRUE;
    	}

    	// prep conditionals
		$conditionals = array();

		$conditionals['cancelled'] = ($cancelled_sub == TRUE) ? TRUE : FALSE;
		$conditionals['failed'] = ($cancelled_sub == FALSE) ? TRUE : FALSE;

		$this->EE->TMPL->tagdata = $this->EE->functions->prep_conditionals($this->EE->TMPL->tagdata, $conditionals);

		$this->return_data = $this->EE->TMPL->tagdata;

		return $this->return_data;
    }

    /**
    * Displays a receipt for the latest donation
    *
    * For each payment, replaces tags:
    *	- {charge_id}
    *	- {subscription_id} (if applicable)
    *	- {member_id} (if applicable)
    *	- {amount}
    *	- {date}
    *	- {donor_first_name}
    *	- {donor_last_name}
    *	- {donor_address}
    *	- {donor_address_2}
    * 	- {donor_city}
    *	- {donor_region}
    *	- {donor_country}
    *	- {donor_postal_code}
    *	- {donor_company}
    *	- {donor_phone}
    *
    * ... and, if it's a subscription...
    *
    *	- {next_charge_date}
    *
    * Conditionals
    *	- All above tags
    *
    * e.g.  {exp:eedonations:receipt date_format="M d, Y"}
    *
    * @param string $date_format The PHP format of dates
    *
    * @return string The latest payment, if available, for that user
	*/
	function receipt () {
		$charge_id = $this->EE->input->cookie('eedonations_donation_id');

		if (empty($charge_id)) {
			return '<!-- no receipt available -->';
		}

		$filters = array(
						'id' => $charge_id
					);

		$payments = $this->eedonations_class->GetPayments(0, 1, $filters);

		if (empty($payments)) {
			// no payments matching parameters
			return '<!-- invalid receipt ID -->';
		}

		$return = '';

		foreach ($payments as $payment) {
			// get the return format
			$sub_return = $this->EE->TMPL->tagdata;

			if ($this->EE->TMPL->fetch_param('date_format')) {
				$payment['date'] = (!empty($payment['date'])) ? date($this->EE->TMPL->fetch_param('date_format'),strtotime($payment['date'])) : FALSE;
			}

			$variables = array();
			$variables[0] = array(
						'charge_id' =>  $payment['id'],
						'subscription_id' =>  $payment['recurring_id'],
						'amount' =>  $payment['amount'],
						'date' => $payment['date'],
						'member_id' => $payment['member_id'],
						'donor_first_name' => $payment['first_name'],
						'donor_last_name' => $payment['last_name'],
						'donor_address' => $payment['address'],
						'donor_address_2' => $payment['address_2'],
						'donor_city' => $payment['city'],
						'donor_region' => (!empty($payment['region_other'])) ? $payment['region_other'] : $payment['region'],
						'donor_country' => $payment['country'],
						'donor_postal_code' => $payment['postal_code'],
						'donor_company' => $payment['company'],
						'donor_phone' => $payment['phone']
					);

			if (!empty($payment['recurring_id'])) {
				$subscription = $this->eedonations_class->GetSubscription($payment['recurring_id']);

				if ($this->EE->TMPL->fetch_param('date_format')) {
					$subscription['next_charge_date'] = (!empty($subscription['next_charge_date'])) ? date($this->EE->TMPL->fetch_param('date_format'),strtotime($subscription['next_charge_date'])) : FALSE;
				}

				$variables[0] = array_merge($variables[0], array(
															'next_charge_date' => $subscription['next_charge_date']
													));
			}

			// swap in the variables
			$sub_return = $this->EE->TMPL->parse_variables($sub_return, $variables);

			// add to return HTML
			$return .= $sub_return;

			unset($sub_return);
		}

		$this->return_data = $return;

		return $return;
	}

	/**
	* Billing Address
	*
	* Returns the latest billing address for the logged-in user
	*
	* @return billing address fields
	*/
	function billing_address () {
		$tagdata = $this->EE->TMPL->tagdata;

		$address = $this->eedonations_class->GetAddress($this->EE->session->userdata('member_id'));

		$variables = array();
		$variables[0] = $address;

		$tagdata = $this->EE->TMPL->parse_variables($tagdata, $variables);

		$this->return_data = $tagdata;

		return $this->return_data;
	}

	/**
    * Displays payments for the logged in user
    *
    * For each payment, replaces tags:
    *	- {charge_id}
    *	- {subscription_id}
    *	- {amount}
    *	- {date}
    *
    * Conditionals
    *	- All above tags
    *
    * e.g.  {exp:eedonations:payments id="X" subscription_id="X" offset="X" limit="X" date_format="Y-m-d"}
    *
    * @param string $date_format The PHP format of dates
    * @param int $id Retrieve only this payment's details
    * @param int $offset Offset results by this number
    * @param int $limit Retrieve only this many results
    * @param int $subscription_id Retrieve only payments related to this subscription
    * @return string Each payment in the format of the HTML between the plugin tags.
	*/
	function payments () {
		$member_id = ($this->EE->TMPL->fetch_param('member_id')) ? $this->EE->TMPL->fetch_param('member_id') : $this->EE->session->userdata('member_id');

		$filters = array();

		if (empty($member_id)) {
			return 'User is not logged in and you have not passed a "member_id" parameter.';
		}
		else {
			$filters['member_id'] = $member_id;
		}

		if ($this->EE->TMPL->fetch_param('id')) {
			$filters['id'] = $this->EE->TMPL->fetch_param('id');
		}

		if ($this->EE->TMPL->fetch_param('subscription_id')) {
			$filters['subscription_id'] = $this->EE->TMPL->fetch_param('subscription_id');
		}

		if ($this->EE->TMPL->fetch_param('offset')) {
			$offset = $this->EE->TMPL->fetch_param('offset');
		}
		else {
			$offset = 0;
		}

		if ($this->EE->TMPL->fetch_param('limit')) {
			$limit = $this->EE->TMPL->fetch_param('limit');
		}
		else {
			$limit = 50;
		}

		$payments = $this->eedonations_class->GetPayments(0, $limit, $filters);

		if (empty($payments)) {
			// no payments matching parameters
			return '';
		}

		$return = '';

		foreach ($payments as $payment) {
			// get the return format
			$sub_return = $this->EE->TMPL->tagdata;

			if ($this->EE->TMPL->fetch_param('date_format')) {
				$payment['date'] = (!empty($payment['date'])) ? date($this->EE->TMPL->fetch_param('date_format'),strtotime($payment['date'])) : FALSE;
			}

			$variables = array();
			$variables[0] = array(
						'charge_id' =>  $payment['id'],
						'subscription_id' =>  $payment['recurring_id'],
						'amount' =>  $payment['amount'],
						'date' => $payment['date'],
						'refunded' => (empty($payment['refunded'])) ? FALSE : TRUE
					);

			// swap in the variables
			$sub_return = $this->EE->TMPL->parse_variables($sub_return, $variables);

			// add to return HTML
			$return .= $sub_return;

			unset($sub_return);
		}

		$this->return_data = $return;

		return $return;
	}

    /**
    * Displays subscription(s) for the logged in user
    *
    * For each subscription, replaces tags:
    *	- {subscription_id}
    *	- {amount}
    *	- {interval}
    *	- {date_created}
    *	- {date_cancelled} (if exists)
    *	- {next_charge_date} (if exists)
    *	- {end_date} (if exists)
    *
    * Conditionals:
    *	- All above tag
    *   - {if active}{/if}   (still auto-recurring)
    *   - {if user_cancelled}{/if}  (the user cancelled this actively)
    *   - {if expired}{/if}   (it expired)
    *	- {if renewed}{/if}
    *	- {if cancelled}{/if}
    *
    * @param string $date_format The PHP format of dates
    * @param int $inactive Set to "1" to retrieve ended subscriptions
    * @param int $id Set to the subscription ID to retrieve only that subscription
    *
    * @return string Each subscription in the format of the HTML between the plugin tags.
	*/

	function subscriptions () {
		$member_id = ($this->EE->TMPL->fetch_param('member_id')) ? $this->EE->TMPL->fetch_param('member_id') : $this->EE->session->userdata('member_id');

		$filters = array();

		if (empty($member_id)) {
			return 'User is not logged in and you have not passed a "member_id" parameter.';
		}
		else {
			$filters['member_id'] = $member_id;
		}

		if ($this->EE->TMPL->fetch_param('inactive') == '1') {
			$filters['active'] = '0';
		}

		if ($this->EE->TMPL->fetch_param('status') == 'active') {
			$filters['active'] = '1';
		}
		elseif ($this->EE->TMPL->fetch_param('status') == 'inactive') {
			$filters['active'] = '0';
		}

		if ($this->EE->TMPL->fetch_param('id')) {
			$filters['id'] = $this->EE->TMPL->fetch_param('id');
		}

		$limit = ($this->EE->TMPL->fetch_param('limit')) ? $this->EE->TMPL->fetch_param('limit') : 100;

		$subscriptions = $this->eedonations_class->GetSubscriptions(0,$limit,$filters);

		if (empty($subscriptions)) {
			// no subscriptions matching parameters
			return '';
		}

		$return = '';

		foreach ($subscriptions as $subscription) {
			// get the return format
			$sub_return = $this->EE->TMPL->tagdata;

			if ($this->EE->TMPL->fetch_param('date_format')) {
				$subscription['date_created'] = date($this->EE->TMPL->fetch_param('date_format'),strtotime($subscription['date_created']));
				$subscription['date_cancelled'] = ($subscription['date_cancelled'] != FALSE) ? date($this->EE->TMPL->fetch_param('date_format'),strtotime($subscription['date_cancelled'])) : FALSE;
				$subscription['next_charge_date'] = ($subscription['next_charge_date'] != FALSE) ? date($this->EE->TMPL->fetch_param('date_format'),strtotime($subscription['next_charge_date'])) : FALSE;
				$subscription['end_date'] = ($subscription['end_date'] != FALSE) ? date($this->EE->TMPL->fetch_param('date_format'),strtotime($subscription['end_date'])) : FALSE;
			}

			$variables = array();
			$variables[0] = array(
							'subscription_id' => $subscription['id'],
							'amount' => $subscription['amount'],
							'date_created' => $subscription['date_created'],
							'date_cancelled' => $subscription['date_cancelled'],
							'next_charge_date' => $subscription['next_charge_date'],
							'interval' => $subscription['interval']
						);

			$sub_return = $this->EE->TMPL->parse_variables($sub_return, $variables);

			// prep conditionals
			$conditionals = array();

			// put all data in conditionals so they can use {if subscription_id == "4343"} etc.
			$conditionals['active'] = ($subscription['active'] == '1') ? TRUE : FALSE;
			// user_cancelled is deprecated
			$conditionals['user_cancelled'] = ($subscription['cancelled'] == '1') ? TRUE : FALSE;
			$conditionals['cancelled'] = ($subscription['cancelled'] == '1') ? TRUE : FALSE;
			$conditionals['renewed'] = ($subscription['renewed'] == TRUE) ? TRUE : FALSE;
			$conditionals['expired'] = ($subscription['expired'] == '1') ? TRUE : FALSE;

			$sub_return = $this->EE->functions->prep_conditionals($sub_return, $conditionals);

			// add to return HTML
			$return .= $sub_return;

			unset($sub_return);
		}

		$this->return_data = $return;

		return $return;
	}

	/**
    * Displays a customizable update credit card form to update a subscription
    *
    * @param int $subscription_id
    * @param string $redirect_url
    *
    * Requires upon POST submission: "subscription_id", logged in user, credit card fields
    *
    * Note: You should validate form fields client side to avoid unnecessary API calls.
    *
    * Submission requires:
    *	user be logged in
    *	subscription_id
	*	cc_number
	*	cc_name
	*	cc_expiry_month
	*	cc_expiry_year
	*   cc_cvv2
	*	eedonations_update_form (hidden field) == 1
	*	if sending a region, use "region" for North American regions and "region_other" for non-NA regions
    *
    * @returns all form related data:
    *
    *	first_name
	*	last_name
	*	address
	*	address_2
	*	company
	*	phone
	*	city
	*	region
	*	region_other
	*	country
	*	postal_code
	*	email
	*	region_options
	*	region_raw_options (array of regions)
	*	country_options
	*	country_raw_options (array of countries)
	*	cc_expiry_month_options
	*	cc_expiry_year_options
	*	errors
	*	form_action (the current URL)
	*	form_method (POST)
	*	subscription_id of the subscription to be updated
    */
    function update_form () {
    	$this->EE->load->helper('form');
		$this->EE->load->library('form_validation');

    	// user must be logged in
    	if ($this->EE->session->userdata('member_id') == '' or $this->EE->session->userdata('member_id') == '0') {
    		return 'EEDonations **WARNING** This user is not logged in.  This form should be seen by only logged in members.';
    	}

    	if (!$this->EE->TMPL->fetch_param('redirect_url')) {
    		return 'You are missing the required "redirect_url" parameter.  This is the URL to send the user to after they have updated their subscription.';
    	}

    	// store all errors in here
    	$errors = array();

    	// get subscription and validate member ownership
    	$subscription_id = ($this->EE->input->post('subscription_id')) ? $this->EE->input->post('subscription_id') : $this->EE->TMPL->fetch_param('subscription_id');
    	$subscription = $this->eedonations_class->GetSubscription($subscription_id);

    	if (empty($subscription) or $subscription['member_id'] != $this->EE->session->userdata('member_id')) {
    		return 'Invalid subscription ID.';
    	}

		// handle potential form submission
		if ($this->EE->input->post('eedonations_update_form')) {
			// validate email if it is there, or if we're creating an account
			if ($this->EE->input->post('email') or $this->EE->input->post('password')) {
				$this->EE->form_validation->set_rules('email','lang:eedonations_donate_form_customer_email','trim|valid_email');
			}
			// and credit card...
			$this->EE->form_validation->set_rules('cc_number','lang:eedonations_donate_form_cc_number','trim|numeric');
			$this->EE->form_validation->set_rules('cc_name','lang:eedonations_donate_form_cc_name','trim');
			$this->EE->form_validation->set_rules('cc_expiry_month','lang:eedonations_donate_form_cc_expiry_month','trim|numeric');
			$this->EE->form_validation->set_rules('cc_expiry_year','lang:eedonations_donate_form_cc_expiry_year','trim|numeric');

			if ($this->EE->session->userdata('member_id') and $this->EE->form_validation->run() !== FALSE) {
				$member_id = $this->EE->session->userdata('member_id');

				$this->EE->load->model('member_model');
			    $member = $this->EE->member_model->get_member_data($this->EE->session->userdata('member_id'));
			    $member = $member->row_array();

				// update address book
				if ($this->EE->input->post('address')) {
					$this->eedonations_class->UpdateAddress($member_id,
												 $this->EE->input->post('first_name'),
												 $this->EE->input->post('last_name'),
												 $this->EE->input->post('address'),
												 $this->EE->input->post('address_2'),
												 $this->EE->input->post('city'),
												 $this->EE->input->post('region'),
												 $this->EE->input->post('region_other'),
												 $this->EE->input->post('country'),
												 $this->EE->input->post('postal_code'),
												 $this->EE->input->post('company'),
												 $this->EE->input->post('phone')
												);
				}

				// prep arrays to send to EEDonations class
				$credit_card = array(
									'number' => $this->EE->input->post('cc_number'),
									'name' => $this->EE->input->post('cc_name'),
									'expiry_month' => $this->EE->input->post('cc_expiry_month'),
									'expiry_year' => $this->EE->input->post('cc_expiry_year'),
									'security_code' => $this->EE->input->post('cc_cvv2')
								);

				$response = $this->eedonations_class->UpdateCC($subscription['id'], $credit_card);

				if (isset($response['error'])) {
					$errors[] = $this->EE->lang->line('eedonations_donate_form_error_processing') . ': ' . $response['error_text'] . ' (#' . $response['error'] . ')';
				}
				elseif ($response['response_code'] != '104') {
					$errors[] = $this->EE->lang->line('eedonations_donate_form_error_processing') . ': ' . $response['response_text'] . '. ' . $response['reason'] . ' (#' . $response['response_code'] . ')';
				}
				else {
					// success!
					// redirect to URL
					header('Location: ' . $this->EE->TMPL->fetch_param('redirect_url'));
					die();
				}
			}
		}

		if (validation_errors()) {
			// neat little hack to get an array of errors
			$form_errors = validation_errors('', '[|]');
			$form_errors = explode('[|]',$form_errors);

			foreach ($form_errors as $form_error) {
				$errors[] = $form_error;
			}
		}

    	// get content of templates
    	$sub_return = $this->EE->TMPL->tagdata;

		// get customer information
		$address = $this->eedonations_class->GetAddress($this->EE->session->userdata('member_id'));

		$this->EE->load->model('member_model');
	    $member = $this->EE->member_model->get_member_data($this->EE->session->userdata('member_id'));
	    $member = $member->row_array();

		$variables = array(
						'first_name' => ($this->EE->input->post('first_name')) ? $this->EE->input->post('first_name') : $address['first_name'],
						'last_name' => ($this->EE->input->post('last_name')) ? $this->EE->input->post('last_name') : $address['last_name'],
						'address' => ($this->EE->input->post('address')) ? $this->EE->input->post('address') : $address['address'],
						'address_2' => ($this->EE->input->post('address_2')) ? $this->EE->input->post('address_2') : $address['address_2'],
						'city' => ($this->EE->input->post('city')) ? $this->EE->input->post('city') : $address['city'],
						'region' => ($this->EE->input->post('region')) ? $this->EE->input->post('region') : $address['region'],
						'region_other' => ($this->EE->input->post('region_other')) ? $this->EE->input->post('region_other') : $address['region_other'],
						'country' => ($this->EE->input->post('country')) ? $this->EE->input->post('country') : $address['country'],
						'postal_code' => ($this->EE->input->post('postal_code')) ? $this->EE->input->post('postal_code') : $address['postal_code'],
						'email' => ($this->EE->input->post('email')) ? $this->EE->input->post('email') : $member['email'],
						'company' => ($this->EE->input->post('company')) ? $this->EE->input->post('company') : $address['company'],
						'phone' => ($this->EE->input->post('phone')) ? $this->EE->input->post('phone') : $address['phone']
					);

		// subscription_id
		$variables['subscription_id'] = $subscription['id'];

		// prep credit card fields

		// prep expiry month options
		$months = '';
		for ($i = 1; $i <= 12; $i++) {
	       $month = str_pad($i, 2, "0", STR_PAD_LEFT);
	       $month_text = date('M',strtotime('2010-' . $month . '-01'));

	       $months .= '<option value="' . $month . '">' . $month . ' - ' . $month_text . '</option>' . "\n";
	    }

	    $variables['cc_expiry_month_options'] = $months;

	    // prep same for years

	    $years = '';
		$now = date('Y');
		$future = $now + 10;
		for ($i = $now; $i <= $future; $i++) {
			$years .= '<option value="' . $i . '">' . $i . '</option>';
		}

	    $variables['cc_expiry_year_options'] = $years;

	    // prep regions
	    $regions = $this->eedonations_class->GetRegions();

		if ($this->EE->input->post('region')) {
			$customer_region = $this->EE->input->post('region');
		}
		elseif (isset($address['region'])) {
			$customer_region = $address['region'];
		}
		else {
			$customer_region = '';
		}

		$return = '';
		foreach ($regions as $region_code => $region) {
			$selected = ($customer_region == $region_code) ? ' selected="selected"' : '';
			$return .= '<option value="' . $region_code . '"' . $selected . '>' . $region . '</option>';
		}

		$region_options = $return;

		$variables['region_options'] = $region_options;
		reset($regions);
		$variables['region_raw_options'] = $regions;

		// field: customer country
		$countries = $this->eedonations_class->GetCountries();

		if ($this->EE->input->post('country')) {
			$customer_country = $this->EE->input->post('country');
		}
		elseif (isset($address['country'])) {
			$customer_country = $address['country'];
		}
		else {
			$customer_country = '';
		}

		$return = '';
		foreach ($countries as $country_code => $country) {
			$selected = ($customer_country == $country_code) ? ' selected="selected"' : '';
			$return .= '<option value="' . $country_code . '"' . $selected . '>' . $country . '</option>';
		}

		$country_options = $return;

		$variables['country_options'] = $country_options;
		reset($countries);
		$variables['country_raw_options'] = $countries;

		// prep form action
	    $variables['form_action'] = ($_SERVER["SERVER_PORT"] == "443") ? str_replace('http://','https://',$this->EE->functions->fetch_current_uri()) : $this->EE->functions->fetch_current_uri();
	    $variables['form_method'] = 'POST';

	    // prep errors
	    $variables['errors_array'] = $errors;

	    $error_string = '';
	    foreach ($errors as $error) {
	    	$error_string .= '<div>' . $error . '</div>';
	    }
	    $variables['errors'] = $error_string;

	    // parse the tag content with our new variables
	    $var_data = array();
	    $var_data[0] = $variables;

		$sub_return = $this->EE->TMPL->parse_variables($sub_return, $var_data);

    	$this->return_data = $sub_return;

    	return $sub_return;
    }

	/**
    * Displays a customizable donation setup form
    *
    * Requires upon POST submission: "amount", logged in user, Customer information fields
    *
    * Submission requires:
    *	user be logged in
    *	amount
	*	cc_number (if not PayPal and subscription not free)
	*	cc_name (if not PayPal and subscription not free)
	*	cc_expiry_month (if not PayPal and subscription not free)
	*	cc_expiry_year (if not PayPal and subscription not free)
	*   cc_cvv2 (if not PayPal and subscription not free)
	*	eedonations_donate_form (hidden field) == 1
	*	if sending a region, use "region" for North American regions and "region_other" for non-NA regions
    *
    *   interval can also be sent to specify recurrence
    *
    * @returns all form related data:
    *
    *	first_name
	*	last_name
	*	address
	*	address_2
	*	company
	*	phone
	*	city
	*	region
	*	region_other
	*	country
	*	postal_code
	*	email
	*	region_options
	*	region_raw_options (array of regions)
	*	country_options
	*	country_raw_options (array of countries)
	*	interval_options
	*	amount_options
	*	gateway_options
	*	gateway_raw_optiosn (array of gateways)
	*	cc_expiry_month_options
	*	cc_expiry_year_options
	*	errors_array
	*	errors
	*	form_action (the current URL)
	*	form_method (POST)
	*
    */
    function donate_form () {
    	$this->EE->load->helper('form');
		$this->EE->load->library('form_validation');

    	// store all errors in here
    	$errors = array();

    	if (!$this->EE->TMPL->fetch_param('redirect_url')) {
    		return 'You are missing the required "redirect_url" parameter.  This is the URL to send the user to after they have donated.';
    	}

		// handle potential form submission
		if ($this->EE->input->post('eedonations_donate_form')) {
			// add an empty validation field so that form_validation doesn't return FALSE without any rules
			$this->EE->form_validation->set_rules('eedonations_donate_form', 'Donation Form', 'required');

			// validate email if it is there, or if we're creating an account
			if ($this->EE->input->post('email') or $this->EE->input->post('password')) {
				$this->EE->form_validation->set_rules('email','lang:eedonations_donate_form_customer_email','trim|valid_email');
			}
			// and credit card if they are there
			if ($this->EE->input->post('cc_number')) {
				$this->EE->form_validation->set_rules('cc_number','lang:eedonations_donate_form_cc_number','trim|numeric');
			}
			if ($this->EE->input->post('cc_expiry_month')) {
				$this->EE->form_validation->set_rules('cc_expiry_month','lang:eedonations_donate_form_cc_expiry_month','trim|numeric');
			}
			if ($this->EE->input->post('cc_expiry_year')) {
				$this->EE->form_validation->set_rules('cc_expiry_year','lang:eedonations_donate_form_cc_expiry_year','trim|numeric');
			}

			// validate renewal subscription if we have one
			if ($this->EE->input->post('renew')) {
				$renewed_subscription = $this->eedonations_class->GetSubscription($this->EE->input->post('renew'));

				if (empty($renewed_subscription)) {
					$errors[] = 'The subscription you are trying to renew does not exist.';
				}
				elseif ($renewed_subscription['member_id'] != $this->EE->session->userdata('member_id')) {
					$errors[] = 'You are trying to renew a subscription that is not yours.';
				}
				else {
					// looks good, let's mark this as a renewal
					$renew_subscription = $renewed_subscription['id'];
				}
			}
			else {
				$renew_subscription = FALSE;
			}

			if ($this->EE->input->post('password') and empty($errors)) {
				$password = preg_replace('#\s#i','',$this->EE->input->post('password'));

				// does password meet requirements?
				if (strlen($password) < $this->EE->config->item('pw_min_len')) {
					$errors[] = 'Your password must be at least '. $pml = $this->EE->config->item('pw_min_len') .' characters in length.';
				}

				// check if passwords match, if there are two passwords
				if (isset($_POST['password2']) and $this->EE->input->post('password2') != $password) {
					$errors[] = 'Your passwords do not match.';
				}

				// set screen_name, username, and email from email
				$email = $this->EE->input->post('email');
				$username = $this->EE->input->post('email');
				$screen_name = $this->EE->input->post('email');

				// set random unique_id
				$unique_id = md5(time() + rand(10,1000));

				// override screen_name?
				if ($this->EE->input->post('username')) {
					$username = $this->EE->input->post('username');
				}

				// override username?
				if ($this->EE->input->post('screen_name')) {
					$screen_name = $this->EE->input->post('screen_name');
				}

				// check email/username uniqueness
				$result = $this->EE->db->where('email',$email)
									   ->get('exp_members');

				if ($result->num_rows() > 0) {
					$errors[] = 'Your email is already registered to an account.  Please login to your account if you have already registered.';
				}

				if ($username != $email) {
					$result = $this->EE->db->where('username',$username)
										   ->get('exp_members');

					if ($result->num_rows() > 0) {
						$errors[] = 'Your username is already being used and must be unique.  Please select another.';
					}
				}

				if (empty($errors)) {
					// attempt to create an account, put an error if $errors[] if failed
					$member_data = array(
										'group_id' => $this->EE->config->item('default_member_group'),
										'language' => $this->EE->config->item('language'),
										'timezone' => $this->EE->config->item('server_timezone'),
										'time_format' => $this->EE->config->item('time_format'),
										'daylight_savings' => $this->EE->config->item('daylight_savings'),
										'ip_address' => $this->EE->input->ip_address(),
										'join_date' => $this->EE->localize->now,
										'email' => $email,
										'unique_id' => $unique_id,
										'username' => $username,
										'screen_name' => $screen_name,
										'password' => sha1($password)
									);

					$this->EE->load->model('member_model');
					$member_id = $this->EE->member_model->create_member($member_data);

					if (empty($member_id)) {
						$errors[] = 'Member account could not be created.';
					}

					// handle custom fields passed in POST
					$result = $this->EE->db->get('exp_member_fields');
					$fields = array();
					if ($result->num_rows() > 0) {
						foreach ($result->result_array() as $field) {
							$fields[$field['m_field_name']] = 'm_field_id_' . $field['m_field_id'];
						}

						$update_fields = array();

						foreach ($fields as $name => $column) {
							$update_fields[$column] = ($this->EE->input->post($name)) ? $this->EE->input->post($name) : '';
						}

						$this->EE->member_model->update_member_data($member_id, $update_fields);
					}
					// end custom fields

					// call member_member_register hook
					$edata = $this->EE->extensions->call('member_member_register', $member_data, $member_id);
					if ($this->EE->extensions->end_script === TRUE) return;

					$member_created = TRUE;
				}
			}
			else {
				if (!$this->EE->session->userdata('member_id')) {
					// allow anonymous?

					if (strtolower($this->EE->TMPL->fetch_param('anonymous')) !== 'true') {
						$errors[] = 'Please complete all required fields.';
						$member_id = FALSE;
						$member_created = FALSE;
					}
					else {
						// anonymous
						$member_id = FALSE;
						$member_created = FALSE;
					}
				}
				else {
					$member_id = $this->EE->session->userdata('member_id');
					$member_created = FALSE;
				}
			}

			// validate amount and interval
			$amount = ($this->EE->input->post('custom_amount') and $this->config['allow_custom_amounts'] == TRUE) ? $this->EE->input->post('custom_amount') : $this->EE->input->post('amount');
			$interval = ($this->EE->input->post('custom_interval') and $this->config['allow_custom_intervals'] == TRUE) ? $this->EE->input->post('custom_interval') : $this->EE->input->post('interval');
            $occurrences = ($this->EE->input->post('occurrences') and $this->config['allow_custom_intervals'] == TRUE) ? $this->EE->input->post('occurrences') : false;

			// clean amount and interval and occurrences
			if (!empty($amount)) {
				$amount = preg_replace('/[^0-9\.]+/i','',$amount);
			}

			if (!empty($interval)) {
				$interval = preg_replace('/[^0-9]+/i','',$interval);
			}

            if (!empty($occurrences)) {
                $occurrences = preg_replace('/[^0-9]+/i','',$occurrences);
            }

			// do we have an interval that needs validating?
			if (!empty($interval)) {
				$interval = (int)$interval;

				if ($interval < 1 or $interval > 365) {
					$errors[] = 'Recurrence interval must be between 1 and 365 days.';
				}
				elseif ($this->config['allow_custom_intervals'] == FALSE and !array_key_exists($interval, $this->config['intervals'])) {
					$errors[] = 'Please select a valid recurrence interval for your donation.';
				}
				elseif ($interval == FALSE) {
					$errors[] = 'Recurrence interval is invalid.';
				}
			}
			else {
				// set it as a nice clean boolean value
				$interval = FALSE;
			}

            // Determine our end date if we have an interval and occurrences.
            $end_date = $interval && $occurrences ? date('Y-m-d', strtotime( '+'. ($interval * $occurrences) .' days' ) ) : false;

			// if we are renewing, we require an interval
			if (!empty($renew_subscription) and empty($interval)) {
				$errors[] = 'You can only renew a subscription with a new subscription.';
			}

			// validate amount
			if (empty($amount) and $this->config['allow_custom_amounts'] == TRUE) {
				$errors[] = 'Please enter a valid donation amount.';
			}
			elseif (empty($amount) and $this->config['allow_custom_amounts'] == FALSE) {
				$errors[] = 'Please select a valid donation amount';
			}
			elseif ((float)$amount < $this->config['minimum_amount']) {
				$errors[] = 'Donation amount is below minimum amount of ' . $this->config['currency_symbol'] . $this->config['minimum_amount'];
			}
			elseif ((float)$amount > $this->config['maximum_amount']) {
				$errors[] = 'Donation amount is above maximum amount of ' . $this->config['currency_symbol'] . $this->config['maximum_amount'];
			}

			if ($this->EE->form_validation->run() != FALSE and empty($errors)) {
				if (!empty($member_id)) {
					$this->EE->load->model('member_model');
				    $member = $this->EE->member_model->get_member_data($member_id);
				    $member = $member->row_array();

				    // update address book
					if ($this->EE->input->post('address')) {
						$this->eedonations_class->UpdateAddress($member_id,
													 $this->EE->input->post('first_name'),
													 $this->EE->input->post('last_name'),
													 $this->EE->input->post('address'),
													 $this->EE->input->post('address_2'),
													 $this->EE->input->post('city'),
													 $this->EE->input->post('region'),
													 $this->EE->input->post('region_other'),
													 $this->EE->input->post('country'),
													 $this->EE->input->post('postal_code'),
													 $this->EE->input->post('company'),
													 $this->EE->input->post('phone')
												);
					}
				}
				else {
					// create placeholder array
					$member = array(
									'email' => 'default@example.com'
								);
				}

				// prep arrays to send to EEDonations class
				if ($this->EE->input->post('cc_number')) {
					$credit_card = array(
										'number' => $this->EE->input->post('cc_number'),
										'name' => $this->EE->input->post('cc_name'),
										'expiry_month' => $this->EE->input->post('cc_expiry_month'),
										'expiry_year' => $this->EE->input->post('cc_expiry_year'),
										'security_code' => $this->EE->input->post('cc_cvv2')
									);
				}
				else {
					$credit_card = array();
				}

				$customer = array(
								 'first_name' => $this->EE->input->post('first_name'),
								 'last_name' => $this->EE->input->post('last_name'),
								 'address' => $this->EE->input->post('address'),
								 'address_2' => $this->EE->input->post('address_2'),
								 'city' => $this->EE->input->post('city'),
								 'region' => ($this->EE->input->post('region_other') and $this->EE->input->post('region_other') != '') ? $this->EE->input->post('region_other') : $this->EE->input->post('region'),
								 'country' => $this->EE->input->post('country'),
								 'postal_code' => $this->EE->input->post('postal_code'),
								 'email' => ($this->EE->input->post('email')) ? $this->EE->input->post('email') : $member['email'],
								 'company' => $this->EE->input->post('company'),
								 'phone' => $this->EE->input->post('phone')
							);

				// have they selected a gateway?
				$gateway_id = ($this->EE->input->post('gateway') and $this->EE->input->post('gateway') != FALSE) ? $this->EE->input->post('gateway') : FALSE;

				// set a cookie, in case they go offsite and get redirected from there...
				$this->EE->functions->set_cookie('eedonations_redirect_url', $this->EE->TMPL->fetch_param('redirect_url'), (60*60));

				// place custom field values in an array and a cookie (in case they are using a 3rd party gateway)
				$custom_fields = array();

				foreach ($_POST as $field => $value) {
					if (strpos($field, 'custom_field_') === 0) {
						$name = substr_replace($field, '', 0, strlen('custom_field_'));
						$name = str_replace('_', ' ', $name);
						$name = ucwords($name);
					}
					else {
						continue;
					}

					$custom_fields[$name] = $value;
				}

				$this->EE->functions->set_cookie('eedonations_custom_fields', serialize($custom_fields), (60*60));

				// process donation
				$response = $this->eedonations_class->Donate($amount, $interval, $member_id, $credit_card, $customer, $end_date, $gateway_id, FALSE, FALSE, $renew_subscription);

				if (isset($response['error'])) {
					$errors[] = $this->EE->lang->line('eedonations_donate_form_error_processing') . ': ' . $response['error_text'] . ' (#' . $response['error'] . ')';

					// delete the member we just created if we created one
					if ($member_created == TRUE) {
						$this->EE->db->delete('exp_members', array('member_id' => $member_id));
					}
				}
				elseif ($response['response_code'] == '2') {
					$errors[] = $this->EE->lang->line('eedonations_donate_form_error_processing') . ': ' . $response['response_text'] . '. ' . $response['reason'] . ' (#' . $response['response_code'] . ')';

					// delete the member we just created if we created one
					if ($member_created == TRUE) {
						$this->EE->db->delete('exp_members', array('member_id' => $member_id));
					}
				}
				else {
					// success!
					if ($member_created == TRUE) {
						// let's log the user in
						$this->EE->session->userdata['ip_address'] = (isset($_SERVER['REMOTE_ADDR'])) ? $_SERVER['REMOTE_ADDR'] : '0.0.0.0';
						$this->EE->session->userdata['user_agent'] = (isset($_SERVER['HTTP_USER_AGENT'])) ? $_SERVER['HTTP_USER_AGENT'] : '';

						$expire = (60*60*24); // 1 day expire

						$this->EE->functions->set_cookie($this->EE->session->c_expire , time()+$expire, $expire);

						// we have to check for these variables because EE2.2 removed them
						if (isset($this->EE->session->c_uniqueid)) {
					        $this->EE->functions->set_cookie($this->EE->session->c_uniqueid , $unique_id, $expire);
					    }
					    if (isset($this->EE->session->c_password)) {
					    	$this->EE->functions->set_cookie($this->EE->session->c_password , sha1($password),  $expire);
					    }

						$this->EE->session->create_new_session($member_id);
						$this->EE->session->userdata['username']  = $username;
					}

					// save custom fields
					if (!empty($custom_fields)) {
						foreach ($custom_fields as $name => $value) {
							$this->EE->db->insert('exp_eedonations_custom_fields', array(
																	'member_id' => $member_id,
																	'payment_id' => isset($response['charge_id']) ? $response['charge_id'] : '0',
																	'recurring_id' => isset($response['recurring_id']) ? $response['recurring_id'] : '0',
																	'custom_field_name' => $name,
																	'custom_field_value' => $value
																));
						}
					}

					// redirect to URL
					header('Location: ' . $this->EE->TMPL->fetch_param('redirect_url'));
					die();
				}
			}
		}

		if (validation_errors()) {
			// neat little hack to get an array of errors
			$form_errors = validation_errors('', '[|]');
			$form_errors = explode('[|]',$form_errors);

			foreach ($form_errors as $form_error) {
				$errors[] = $form_error;
			}
		}

    	// get content of templates
    	$sub_return = $this->EE->TMPL->tagdata;

		// prep customer information tags
		if ($this->EE->session->userdata('member_id')) {
			// get customer information
			$address = $this->eedonations_class->GetAddress($this->EE->session->userdata('member_id'));

			$this->EE->load->model('member_model');
		    $member = $this->EE->member_model->get_member_data($this->EE->session->userdata('member_id'));
		    $member = $member->row_array();
		}
		else {
			$address = array(
							'first_name' => '',
							'last_name' => '',
							'address' => '',
							'address_2' => '',
							'city' => '',
							'region' => '',
							'region_other' => '',
							'country' => '',
							'postal_code' => '',
							'email' => '',
							'company' => '',
							'phone' => ''
						);

			$member = array(
							'email' => ''
						);
		}

		$variables = array(
						'first_name' => ($this->EE->input->post('first_name')) ? $this->EE->input->post('first_name') : $address['first_name'],
						'last_name' => ($this->EE->input->post('last_name')) ? $this->EE->input->post('last_name') : $address['last_name'],
						'address' => ($this->EE->input->post('address')) ? $this->EE->input->post('address') : $address['address'],
						'address_2' => ($this->EE->input->post('address_2')) ? $this->EE->input->post('address_2') : $address['address_2'],
						'city' => ($this->EE->input->post('city')) ? $this->EE->input->post('city') : $address['city'],
						'region' => ($this->EE->input->post('region')) ? $this->EE->input->post('region') : $address['region'],
						'region_other' => ($this->EE->input->post('region_other')) ? $this->EE->input->post('region_other') : $address['region_other'],
						'country' => ($this->EE->input->post('country')) ? $this->EE->input->post('country') : $address['country'],
						'postal_code' => ($this->EE->input->post('postal_code')) ? $this->EE->input->post('postal_code') : $address['postal_code'],
						'email' => ($this->EE->input->post('email')) ? $this->EE->input->post('email') : $member['email'],
						'company' => ($this->EE->input->post('company')) ? $this->EE->input->post('company') : $address['company'],
						'phone' => ($this->EE->input->post('phone')) ? $this->EE->input->post('phone') : $address['phone']
					);

		// prep interval fields
		$intervals = $this->config['intervals'];

		$interval_options = '';
		foreach ($intervals as $interval => $name) {
			$interval_options .= '<option value="' . $interval . '">' . $name . '</option>';
		}

		$variables['interval_options'] = $interval_options;

		// prep amount fields
		$amounts = $this->config['amounts'];

		$amount_options = '';
		foreach ($amounts as $amount => $name) {
			$amount_options .= '<option value="' . $amount . '">' . $name . ' (' . $this->config['currency_symbol'] . $amount . ')</option>';
		}

		$variables['amount_options'] = $amount_options;

		// prep credit card fields

		// prep expiry month options
		$months = '';
		for ($i = 1; $i <= 12; $i++) {
	       $month = str_pad($i, 2, "0", STR_PAD_LEFT);
	       $month_text = date('M',strtotime('2010-' . $month . '-01'));

	       $months .= '<option value="' . $month . '">' . $month . ' - ' . $month_text . '</option>' . "\n";
	    }

	    $variables['cc_expiry_month_options'] = $months;

	    // prep same for years

	    $years = '';
		$now = date('Y');
		$future = $now + 10;
		for ($i = $now; $i <= $future; $i++) {
			$years .= '<option value="' . $i . '">' . $i . '</option>';
		}

	    $variables['cc_expiry_year_options'] = $years;

	    // prep regions
	    $regions = $this->eedonations_class->GetRegions();

		if ($this->EE->input->post('region')) {
			$customer_region = $this->EE->input->post('region');
		}
		elseif (isset($address['region'])) {
			$customer_region = $address['region'];
		}
		else {
			$customer_region = '';
		}

		$return = '';
		foreach ($regions as $region_code => $region) {
			$selected = ($customer_region == $region_code) ? ' selected="selected"' : '';
			$return .= '<option value="' . $region_code . '"' . $selected . '>' . $region . '</option>';
		}

		$region_options = $return;

		$variables['region_options'] = $region_options;
		reset($regions);
		$variables['region_raw_options'] = $regions;

		// field: customer country
		$countries = $this->eedonations_class->GetCountries();

		if ($this->EE->input->post('country')) {
			$customer_country = $this->EE->input->post('country');
		}
		elseif (isset($address['country'])) {
			$customer_country = $address['country'];
		}
		else {
			$customer_country = '';
		}

		$return = '';
		foreach ($countries as $country_code => $country) {
			$selected = ($customer_country == $country_code) ? ' selected="selected"' : '';
			$return .= '<option value="' . $country_code . '"' . $selected . '>' . $country . '</option>';
		}

		$country_options = $return;

		$variables['country_options'] = $country_options;
		reset($countries);
		$variables['country_raw_options'] = $countries;

		// prep gateway options
		require(dirname(__FILE__) . '/opengateway.php');
		$this->server = new OpenGateway;
		$this->server->Authenticate($this->config['api_id'], $this->config['secret_key'], $this->config['api_url'] . '/api');
		$this->server->SetMethod('GetGateways');
		$response = $this->server->Process();

		// we may get one gateway or many
		$gateways = isset($response['gateways']) ? $response['gateways'] : FALSE;

		// hold our list of available options
		$gateway_raw_options = array();

		if (is_array($gateways) and isset($gateways['gateway'][0])) {
			foreach ($gateways['gateway'] as $gateway) {
				$gateway_raw_options[] = array('id' => $gateway['id'], 'name' => $gateway['gateway']);
			}
		}
		elseif (is_array($gateways)) {
			$gateway = $gateways['gateway'];
			$gateway_raw_options[] = array('id' => $gateway['id'], 'name' => $gateway['gateway']);
		}

		$gateway_options = '';
		foreach ($gateway_raw_options as $gateway) {
			$gateway_options .= '<option value="' . $gateway['id'] . '">' . $gateway['name'] . '</option>';
		}

		$variables['gateway_options'] = $gateway_options;
		reset($gateway_raw_options);
		$variables['gateway_raw_options'] = $gateway_raw_options;

	    // prep form action
	    $variables['form_action'] = ($_SERVER["SERVER_PORT"] == "443") ? str_replace('http://','https://',$this->EE->functions->fetch_current_uri()) : $this->EE->functions->fetch_current_uri();
	    $variables['form_method'] = 'POST';

	    // prep errors
	    $variables['errors_array'] = $errors;

	    $error_string = '';
	    foreach ($errors as $error) {
	    	$error_string .= '<div>' . $error . '</div>';
	    }
	    $variables['errors'] = $error_string;

        // Any custom variable values to make avaiable to the form?
        if (isset($custom_fields) && is_array($custom_fields) && count($custom_fields))
        {
            foreach ($custom_fields as $name => $value)
            {
                $variables[ 'custom_field_'. str_replace(' ', '_', strtolower($name)) ] = $value;
            }
        }

	    // parse the tag content with our new variables
	    $var_data = array();
	    $var_data[0] = $variables;

		$sub_return = $this->EE->TMPL->parse_variables($sub_return, $var_data);

    	$this->return_data = $sub_return;

    	return $sub_return;
    }

    /**
    * POST Notification Handler
    *
    * Handles POST notifications from the EE Donations/OpenGateway billing server.
    */

    function post_notify () {
    	// connect to API
    	require_once(dirname(__FILE__) . '/opengateway.php');
		$connect_url = $this->config['api_url'] . '/api';
		$server = new OpenGateway;
		$server->Authenticate($this->config['api_id'], $this->config['secret_key'], $connect_url);

    	// first, we'll check for external payment API redirects
    	if ($this->EE->input->get('member') and !$this->EE->input->post('action')) {
    		// get customer ID
    		$server->SetMethod('GetCustomers');
    		$server->Param('internal_id',$this->EE->input->get('member'));
    		$response = $server->Process();
    		$customer = (!isset($response['customers']['customer'][0])) ? $response['customers']['customer'] : $response['customers']['customer'][0];

    		if (empty($customer)) {
    			die('Invalid customer record.');
    		}

    		if ($this->EE->input->get('type') == 'subscription') {
	    		$server->SetMethod('GetRecurrings');
	    		$server->Param('customer_id',$customer['id']);
	    		$response = $server->Process();

	    		if (isset($response['recurrings']['recurring'][0])) {
					$recurrings = $response['recurrings']['recurring'];
				}
				elseif (isset($response['recurrings'])) {
					$recurrings = array();
					$recurrings[] = $response['recurrings']['recurring'];
				}
				else {
					$recurrings = array();
				}

				// is there a new recurring charge for this client?
				foreach ($recurrings as $recurring) {
					if (!$this->eedonations_class->GetSubscription($recurring['id'])) {
						// we have a new charge!
						$end_date = date('Y-m-d H:i:s',strtotime($recurring['end_date']));
						$next_charge_date = date('Y-m-d H:i:s',strtotime($recurring['next_charge_date']));

						// get the first charge
						$server->SetMethod('GetCharges');
						$server->Param('recurring_id',$recurring['id']);
						$charge = $server->Process();

						// if there was an initial payment, charge should be an array, but there shouldn't be multiple charges!
						if (!empty($charge) and isset($charge['charges']) and is_array($charge['charges']) and !isset($charge['charges']['charge'][0])) {
							$charge = $charge['charges']['charge'];
							$payment = $charge['amount'];
							$this->eedonations_class->RecordPayment($recurring['id'], $charge['id'], $customer['internal_id'], $payment);
						}

						// do we need to perform some maintenance for renewals?
						if ($this->EE->input->get('renew_recurring_id')) {
							// validate old subscription
							$result = $this->EE->db->where('member_id', $this->EE->input->get('member'))
												   ->where('recurring_id', $this->EE->input->get('renew_recurring_id'))
												   ->where('active','1')
												   ->get('exp_eedonations_subscriptions');

							if ($result->num_rows() > 0) {
								$this->eedonations_class->RenewalMaintenance($this->EE->input->get('renew_recurring_id'), $recurring['id']);
							}
						}

						// call "eedonations_donate" hook with: member_id, amount, interval
						if ($this->EE->extensions->active_hook('eedonations_donate') == TRUE)
						{
							$charge_id = (!empty($charge) and isset($charge['charge_id'])) ? $charge['charge_id'] : FALSE;
						    $this->EE->extensions->call('eedonations_donate', $charge_id, $recurring['id'], $this->EE->input->get('member'), $recurring['amount'], $recurring['interval']);
						    if ($this->EE->extensions->end_script === TRUE) return $response;
						}

						$this->eedonations_class->RecordSubscription($recurring['id'], $this->EE->input->get('member'), $recurring['interval'], $next_charge_date, $end_date, $recurring['amount']);

						$charge_id = isset($charge['id']) ? $charge['id'] : 0;
						$recurring_id = isset($recurring['id']) ? $recurring['id'] : 0;
						$interval = (isset($recurring['interval'])) ? $recurring['interval'] : 0;
						$member_id = $this->EE->input->get('member');

						// do we have custom fields to insert?
						$custom_fields = @unserialize($this->EE->input->cookie('eedonations_custom_fields'));

						if (is_array($custom_fields) and !empty($custom_fields)) {
							foreach ($custom_fields as $name => $value) {
								$this->EE->db->insert('exp_eedonations_custom_fields', array(
																		'member_id' => $member_id,
																		'payment_id' => isset($charge_id) ? $charge_id : '0',
																		'recurring_id' => isset($recurring_id) ? $recurring_id : '0',
																		'custom_field_name' => $name,
																		'custom_field_value' => $value
																	));
							}
						}

						// call "eedonations_donate" hook with: charge_id, recurring_id, member_id, amount, interval
						if ($this->EE->extensions->active_hook('eedonations_donate') == TRUE)
						{
							$this->EE->extensions->call('eedonations_donate', $charge_id, $recurring_id, $member_id, $payment, $interval);
						    if ($this->EE->extensions->end_script === TRUE) return;
						}

						// redirect
						header('Location: ' . $this->EE->input->cookie('eedonations_redirect_url'));
						die();
					}
				}
			}
			elseif ($this->EE->input->get('type') == 'payment') {
				// find the charge
				$server->SetMethod('GetCharges');
				$server->Param('customer_id',$customer['id']);
				$charge = $server->Process();

				// if there was an initial payment, charge should be an array, but there shouldn't be multiple charges!
				if (!empty($charge) and isset($charge['charges']) and is_array($charge['charges'])) {
					$charge = (isset($charge['charges']['charge'][0])) ? $charge['charges']['charge'][0] : $charge['charges']['charge'];
					$payment = $charge['amount'];

					$this->eedonations_class->RecordPayment(FALSE, $charge['id'], $customer['internal_id'], $payment);

					$charge_id = isset($charge['id']) ? $charge['id'] : 0;
					$recurring_id = 0;
					$interval = 0;
					$member_id = $this->EE->input->get('member');

					// call "eedonations_donate" hook with: charge_id, recurring_id, member_id, amount, interval
					if ($this->EE->extensions->active_hook('eedonations_donate') == TRUE)
					{
						$this->EE->extensions->call('eedonations_donate', $charge_id, $recurring_id, $member_id, $payment, $interval);
					    if ($this->EE->extensions->end_script === TRUE) return;
					}

					// do we have custom fields to insert?
					$custom_fields = @unserialize($this->EE->input->cookie('eedonations_custom_fields'));

					if (is_array($custom_fields) and !empty($custom_fields)) {
						foreach ($custom_fields as $name => $value) {
							$this->EE->db->insert('exp_eedonations_custom_fields', array(
																	'member_id' => $member_id,
																	'payment_id' => isset($charge_id) ? $charge_id : '0',
																	'recurring_id' => '0',
																	'custom_field_name' => $name,
																	'custom_field_value' => $value
																));
						}
					}
				}
				else {
					die('No charges found.');
				}

				// redirect
				header('Location: ' . $this->EE->input->cookie('eedonations_redirect_url'));
				die();
			}
    	}

    	// is the secret key OK?  ie. is this a legitimate call?
		if ($this->EE->input->post('secret_key') != $this->config['secret_key']) {
			die('Invalid secret key.');
		}

		if (!$this->EE->input->post('customer_id') or !$this->EE->input->post('recurring_id')) {
			die('Insufficient data.');
		}

		// get customer data from server
		$server->SetMethod('GetCustomer');
		$server->Param('customer_id',$this->EE->input->post('customer_id'));
		$response = $server->Process();

		if (!is_array($response) or !isset($response['customer'])) {
			die('Error retrieving customer data.');
		}
		else {
			$customer = $response['customer'];
		}

		// get subscription data locally
		$subscription = $this->eedonations_class->GetSubscription($this->EE->input->post('recurring_id'));

		if (!$subscription) {
			die('Error retrieving subscription data locally.');
		}

		if ($this->EE->input->post('action') == 'recurring_charge') {
			if (!$this->EE->input->post('charge_id')) {
				die('No charge ID.');
			}

			if (is_array($this->eedonations_class->GetPayments(0,1,array('id' => $this->EE->input->post('charge_id'))))) {
		 		die('Charge already recorded.');
		 	}

			$server->SetMethod('GetCharge');
			$server->Param('charge_id',$this->EE->input->post('charge_id'));
			$charge = $server->Process();

			$charge = $charge['charge'];

		 	// record charge
			$this->eedonations_class->RecordPayment($this->EE->input->post('recurring_id'), $this->EE->input->post('charge_id'), $customer['internal_id'], $charge['amount']);

			// set next charge
			$next_charge_date = strtotime('now + ' . $subscription['interval'] . ' days');

			if (!empty($subscription['end_date']) and (strtotime($subscription['end_date']) < $next_charge_date)) {
				// there won't be a next charge
				// subscription will expire beforehand
				$next_charge_date = '0000-00-00';
			}
			else {
				$next_charge_date = date('Y-m-d',$next_charge_date);
			}

			$this->eedonations_class->SetNextCharge($this->EE->input->post('recurring_id'),$next_charge_date);
		}
		elseif ($this->EE->input->post('action') == 'recurring_cancel') {
			$this->eedonations_class->CancelSubscription($subscription['id'],FALSE);
		}
		elseif ($this->EE->input->post('action') == 'recurring_expire' or $this->EE->input->post('action') == 'recurring_fail') {
			$this->eedonations_class->CancelSubscription($subscription['id'],FALSE,TRUE);
		}
    }
}
