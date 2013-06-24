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

if (!class_exists('EEDonations_class')) {
	class EEDonations_class {
		var $cache;
		
		/**
		* Constructor
		*
		* Deal with expirations, load EE superobject
		*
		* @return void
		*/	
		function __construct () {	
			$this->EE =& get_instance();
			
			// deal with normal expirations
			if ($this->EE->db->table_exists('exp_eedonations_subscriptions')) {	
				$this->EE->db->select('exp_eedonations_subscriptions.member_id');
				$this->EE->db->select('exp_eedonations_subscriptions.recurring_id');
				$this->EE->db->select('exp_eedonations_subscriptions.renewed_recurring_id');
				$this->EE->db->where('(`end_date` <= NOW() and `end_date` != \'0000-00-00 00:00:00\')',NULL,FALSE);
				$this->EE->db->where('exp_eedonations_subscriptions.expiry_processed','0');
				$query = $this->EE->db->get('exp_eedonations_subscriptions');
				
				foreach ($query->result_array() AS $row) {
					$perform_expiration = TRUE;
					// is there an active renewal for this?
					if (!empty($row['renewed_recurring_id'])) {
						$renewing = $this->GetSubscription($row['renewed_recurring_id']);
						
						if ($renewing['active'] == '1') {
							$perform_expiration = FALSE;
						}
					}
				
					if ($perform_expiration == TRUE) {
						// nothing to do!
					}
					
					// this expiry is processed
					$this->EE->db->where('recurring_id',$row['recurring_id']);
					$this->EE->db->update('exp_eedonations_subscriptions',array('expiry_processed' => '1'));
				}
			}
		}
	
		/**
		* Donate
		*
		* Make a donation, essentially a wrapper for OpenGateway's Recur/Charge API Calls.
		* 
		* @param float $amount
		* @param int|boolean $interval (if 0/FALSE, it won't be a subscription)
		* @param int $member_id (leave FALSE for anonymous)
		* @param array $credit_card
		* @param array $customer
		* @param string/boolean $end_date
		* @param int $gateway_id
		* @param string/boolean $cancel_url
		* @param string/boolean $return_url
		* @param int/boolean $renew_subscription
		*
		* @return array Response from OpenGateway
		*/
		function Donate ($amount, $interval, $member_id, $credit_card, $customer, $end_date = FALSE, $gateway_id = FALSE, $cancel_url = '', $return_url = '', $renew_subscription = FALSE ) {
			$amount = (float)$amount;
			$interval = (int)$interval;
		
			$config = $this->GetConfig();
			
			// we allow anonymous subscriptions by creating a random member_id for the user
			if (empty($member_id)) {
				$member_id = 'anon' . str_replace('.','',microtime(TRUE)) . rand(100,999);
			}
			
			if (!class_exists('Opengateway')) {
				require(dirname(__FILE__) . '/opengateway.php');
			}
			
			$connect_url = $config['api_url'] . '/api';
			
			// are we charging or recurring?
			if (empty($interval)) {
				$charge = new Charge;
			}
			else {
				$charge = new Recur;
			}
			
			$charge->Authenticate($config['api_id'], $config['secret_key'], $connect_url);
			
			// use existing customer_id if we can
			$opengateway = new OpenGateway;
			$opengateway->Authenticate($config['api_id'], $config['secret_key'], $connect_url);
			$opengateway->SetMethod('GetCustomers');
			$opengateway->Param('internal_id', $member_id);
			$response = $opengateway->Process();
			
			if ($response['total_results'] > 0) {
				// there is already a customer record here
				$customer_record = (!isset($response['customers']['customer'][0])) ? $response['customers']['customer'] : $response['customers']['customer'][0];
				
				$charge->UseCustomer($customer_record['id']);
			}
			else {
				// no customer records, yet
				$charge->Param('internal_id', $member_id, 'customer');
				
				// let's try to auto-generate a name based if there isn't one there
				if (empty($customer['first_name']) and empty($customer['last_name'])) {
					// do we have a credit card name to generate from?
					if (isset($credit_card['name']) and !empty($credit_card['name'])) {
						$names = explode(' ', $credit_card['name']);
						
						$customer['first_name'] = $names[0];
						$customer['last_name'] = end($names);
					}
				}
				
				$charge->Customer($customer['first_name'],$customer['last_name'],$customer['company'],$customer['address'],$customer['address_2'],$customer['city'],$customer['region'],$customer['country'],$customer['postal_code'],$customer['phone'],$customer['email']);
			}
			
			// update address book
			$this->UpdateAddress($member_id,
				 (isset($customer['first_name'])) ? $customer['first_name'] : FALSE,
				 (isset($customer['last_name'])) ? $customer['last_name'] : FALSE,
				 (isset($customer['address'])) ? $customer['address'] : FALSE,
				 (isset($customer['address_2'])) ? $customer['address_2'] : FALSE,
				 (isset($customer['city'])) ? $customer['city'] : FALSE,
				 (isset($customer['region'])) ? $customer['region'] : FALSE,
				 (isset($customer['region_other'])) ? $customer['region_other'] : FALSE,
				 (isset($customer['country'])) ? $customer['country'] : FALSE,
				 (isset($customer['postal_code'])) ? $customer['postal_code'] : FALSE,
				 (isset($customer['company'])) ? $customer['company'] : FALSE,
				 (isset($customer['phone'])) ? $customer['phone'] : FALSE
				);
			
			// specify amount
			$charge->Amount($amount);

			// specify recurring details if subscription
			if (!empty($interval)) {
				if (!empty($interval)) {
					$charge->Param('interval',$interval,'recur');
				}
				
				if ($end_date != FALSE) {
					$charge->Param('end_date', $end_date, 'recur');
				}
			}
			
			// are we renewing an existing subscription?
			if (!empty($renew_subscription) and !empty($interval)) {
				// is sub active?
				$old_sub = $this->GetSubscription($renew_subscription);
				
				if ($old_sub['active'] == '1') {
					// we will delay the start of the new subscription until the end of this one
					$old_sub = $this->GetSubscription($renew_subscription);
					
					// calculate real end date
					$old_end_date = $this->_calculate_end_date($old_sub['end_date'], $old_sub['next_charge_date'], $old_sub['date_created']);
					
					// convert to timestamp for calcs
					$old_end_date = strtotime($old_end_date);
					
					// postpone the start date of this new subscription from that end_date
					$difference_in_days = ($old_end_date - time()) / (60*60*24);
					
					// add parameters
					$charge->Param('renew',$renew_subscription);
					$charge->Param('start_date', date('Y-m-d', $old_end_date), 'recur');
				}
			}
			
			// pass credit card info?
			if ($credit_card and !empty($credit_card) and isset($credit_card['number']) and !empty($credit_card['number'])) {
				$security = (empty($credit_card['security_code'])) ? FALSE : $credit_card['security_code'];
				$charge->CreditCard($credit_card['name'], $credit_card['number'], $credit_card['expiry_month'], $credit_card['expiry_year'], $security);
			}
			
			// specify the gateway?
			if (!empty($gateway_id)) {
				$charge->Param('gateway_id', $gateway_id);
			}
			elseif (!empty($config['gateway'])) {
				$charge->Param('gateway_id',$config['gateway']);
			}
			
			// add IP address to request
			$current_ip = $this->EE->input->ip_address();
			$charge->Param('customer_ip_address',$current_ip);
			
			// build notification URL for subscriptions
			$this->EE->db->select('action_id');
			$this->EE->db->where('class','EEDonations');
			$this->EE->db->where('method','post_notify');
			$result = $this->EE->db->get('exp_actions');
			$action_id = $result->row_array();
			$action_id = $action_id['action_id'];
		 	$notify_url = $this->EE->functions->create_url('?ACT=' . $action_id, 0);
		 	
		 	if (!empty($interval)) {
		 		$charge->Param('notification_url', $notify_url, 'recur');
		 	}
			 	
			// if they are using PayPal, we need the following parameters
			if (empty($return_url)) {
				$return_url = $notify_url . '&member=' . $member_id . '&amount=' . $amount;
			 	
			 	// if we are renewing, we will append this to the $return_url so that we can cancel old subscriptions
			 	// and update channel entries to the new recurring_id for external gateways like PayPal EC
			 	if (!empty($renew_subscription)) {
			 		$return_url .= '&renew_recurring_id=' . $renew_subscription;
			 	}
			 	
			 	// is this a subscription or a single charge?  this will help our post_notify() method know what to look
			 	// for when scanning OpenGateway
			 	if (!empty($interval)) {
			 		$return_url .= '&type=subscription';
			 	}
			 	else {
			 		$return_url .= '&type=payment';
			 	}
			 	
			 	// sometimes, with query strings, we get index.php?/?ACT=26...
			 	$return_url = str_replace('?/?','?', $return_url);
			}
			$charge->Param('return_url',htmlspecialchars($return_url));
			
			if (empty($cancel_url)) {
				$cancel_url = $this->EE->functions->fetch_current_uri();
			}
			$charge->Param('cancel_url',htmlspecialchars($cancel_url));
			
			// call "eedonations_pre_donate" hook with: $charge, $member_id, $interval, $end_date
			if ($this->EE->extensions->active_hook('eedonations_pre_donate') == TRUE)
			{
				$this->EE->extensions->call('eedonations_pre_donate', $charge, $member_id, $interval, $end_date);
			    if ($this->EE->extensions->end_script === TRUE) return FALSE;
			}
			
			$response = $charge->Charge();
			
			if (isset($response['response_code']) and ($response['response_code'] == '100' or $response['response_code'] == '1')) {
				// success!
				
				// perform renewing subscription maintenance
				if (!empty($interval) and !empty($renew_subscription)) {
					$this->RenewalMaintenance($renew_subscription, $response['recurring_id']);
				}
				
				// get subscription dates if subscription
				if (!empty($interval)) {
					$start_date = $response['start_date'];
					
					// calculate end date
					if ($end_date != FALSE) {
						// we must also account for their signup time
						$time_created = date('H:i:s');
						$end_date = date('Y-m-d',strtotime($end_date)) . ' ' . $time_created;
					}
					elseif ($end_date == FALSE) {
						// unlimited occurrences
						$end_date = '0000-00-00 00:00:00';
					}
					
					// calculate next charge date
					$next_charge_date = strtotime($start_date . ' + ' . $interval . ' days');
					
					if ((date('Y-m-d',$next_charge_date) == date('Y-m-d',strtotime($end_date)) or $next_charge_date > strtotime($end_date)) and $end_date != '0000-00-00 00:00:00') {
						$next_charge_date = '0000-00-00';
					}
					else {
						$next_charge_date = date('Y-m-d',$next_charge_date);
					}
					
					// record subscription
					$this->RecordSubscription($response['recurring_id'], $member_id, $interval, $next_charge_date, $end_date, $amount); 
				}
				 
				// create payment record          
				$recurring_id = (!empty($response['recurring_id'])) ? $response['recurring_id'] : FALSE;     						  
				$this->RecordPayment($recurring_id, $response['charge_id'], $member_id, $amount);
				
				// call "eedonations_donate" hook with: charge_id, recurring_id, member_id, amount, interval
				if ($this->EE->extensions->active_hook('eedonations_donate') == TRUE)
				{
				    $this->EE->extensions->call('eedonations_donate', $response['charge_id'], $recurring_id, $member_id, $amount, $interval);
				    if ($this->EE->extensions->end_script === TRUE) return $response;
				}
			}
			
			return $response;
		}
		
		/**
		* Update Expiry Date
		*
		* Modify the expiration (end) date of a subscription
		*
		* @param int $subscription_id
		* @param date $new_expiry
		*
		* @return boolean
		*/
		function UpdateExpiryDate ($subscription_id, $new_expiry) {
			if (strtotime($new_expiry) < time()) {
				return FALSE;
			}
			
			// get subscription
			$subscription = $this->GetSubscription($subscription_id);
			
			if (empty($subscription)) {
				return FALSE;
			}
		
			// format
			$new_expiry = date('Y-m-d', strtotime($new_expiry));
		
			// update locally
			$this->EE->db->update('exp_eedonations_subscriptions',array('end_date' => $new_expiry), array('recurring_id' => $subscription_id));
			
			// connect to OG
			$config = $this->GetConfig();
			$connect_url = $config['api_url'] . '/api';
			$opengateway = new OpenGateway;
			$opengateway->Authenticate($config['api_id'], $config['secret_key'], $connect_url);
			$opengateway->SetMethod('UpdateRecurring');
			$opengateway->Param('recurring_id', $subscription_id);
			$opengateway->Param('end_date', $new_expiry);
			
			$response = $opengateway->Process();
			
			if (!isset($response['error'])) {
				return TRUE;
			}
			else {
				// revert
				$this->EE->db->update('exp_eedonations_subscriptions',array('end_date' => $subscription['end_date']), array('recurring_id' => $subscription_id));
				
				return FALSE;	
			}
		}
		
		/**
		* Update Credit Card
		*
		* @param int $subscription_id
		* @param array $credit_card with keys 'number', 'name', 'expiry_year', 'expiry_month', 'security_code'
		*
		* @return boolean
		*/
		function UpdateCC ($subscription_id, $credit_card) {
			$config = $this->GetConfig();
			
			if (!class_exists('Opengateway')) {
				require(dirname(__FILE__) . '/opengateway.php');
			}
			
			// connect to OG
			$connect_url = $config['api_url'] . '/api';
			$opengateway = new OpenGateway;
			$opengateway->Authenticate($config['api_id'], $config['secret_key'], $connect_url);
			$opengateway->SetMethod('UpdateCreditCard');
			$opengateway->Param('recurring_id', $subscription_id);
			
			// credit card
			$opengateway->Param('card_num', $credit_card['number'], 'credit_card');
			$opengateway->Param('name', $credit_card['name'], 'credit_card');
			$opengateway->Param('exp_month', $credit_card['expiry_month'], 'credit_card');
			$opengateway->Param('exp_year', $credit_card['expiry_year'], 'credit_card');
			$opengateway->Param('cvv', $credit_card['security_code'], 'credit_card');
			
			$response = $opengateway->Process();
			
			if (isset($response['response_code']) and $response['response_code'] == '104') {
				// we have to update all local subscription ID references
				$this->EE->db->update('exp_eedonations_subscriptions',array('recurring_id' => $response['recurring_id']),array('recurring_id' => $subscription_id));
				$this->EE->db->update('exp_eedonations_payments',array('recurring_id' => $response['recurring_id']),array('recurring_id' => $subscription_id));
			}
			
			return $response;
		}
		
		/**
		* Renewal Maintenance
		*
		* Link renewals to original subscriptions
		*
		* @param int $old_subscription
		* @param int $new_subscription
		*
		* @return boolean
		*/
		function RenewalMaintenance ($old_subscription, $new_subscription) {
			// we should also cancel the old subscription
			// cancel the existing subscription
			$this->CancelSubscription($old_subscription, FALSE, FALSE);
			
			// mark as renewed
			$this->EE->db->update('exp_eedonations_subscriptions', array('renewed_recurring_id' => $new_subscription), array('recurring_id' => $old_subscription));
		
			return TRUE;
		}
		
		/**
		* Record Subscription
		*
		* @param int $recurring_id
		* @param int $member_id
		* @param int $interval
		* @param date $next_charge_date
		* @param date $end_date
		* @param float $payment
		*
		* @return boolean
		*/
		function RecordSubscription ($recurring_id, $member_id, $interval, $next_charge_date, $end_date, $payment) {
			// create subscription record
			$insert_array = array(
								'recurring_id' => $recurring_id,
								'member_id' => $member_id,
								'interval' => $interval,
								'amount' => $payment,
								'date_created' => date('Y-m-d H:i:s'),
								'date_cancelled' => '0000-00-00 00:00:00',
								'next_charge_date' => $next_charge_date,
								'end_date' => $end_date,
								'expired' => '0',
								'cancelled' => '0',
								'active' => '1',
								'renewed_recurring_id' => '0',
								'expiry_processed' => '0'
							);

			$this->EE->db->insert('exp_eedonations_subscriptions',$insert_array);
			
			return TRUE;
		}
		
		/**
		* Record Payment
		*
		* @param int $subscription_id (optional)
		* @param int $charge_id
		* @param int $member_id
		* @param float $amount
		*
		* @return boolean
		*/
		function RecordPayment ($subscription_id = FALSE, $charge_id, $member_id, $amount) {
			$insert_array = array(
								'charge_id' => $charge_id,
								'recurring_id' => (!empty($subscription_id)) ? $subscription_id : '0',
								'amount' => $amount,
								'member_id' => $member_id,
								'date' => date('Y-m-d H:i:s'),
								'refunded' => '0'
							);
							
			$this->EE->db->insert('exp_eedonations_payments',$insert_array);
			
			// call "eedonations_donate" hook with: payment_id, recurring_id, member_id, amount, $charge_id
			if ($this->EE->extensions->active_hook('eedonations_payment') == TRUE) {
    			 $this->EE->extensions->call('eedonations_payment', $charge_id, $subscription_id, $member_id, $amount, $charge_id);
    			 if ($this->EE->extensions->end_script === TRUE) return $response;
			} 
			
			// set receipt cookie
			$this->EE->functions->set_cookie('eedonations_donation_id', $charge_id, 86400); 
			
			return TRUE;
		}
		
		/**
		* Refund Payment
		*
		* @param int $charge_id
		*
		* @return array
		*/
		function Refund ($charge_id) {
			$config = $this->GetConfig();
			$connect_url = $config['api_url'] . '/api';
			require_once(dirname(__FILE__) . '/opengateway.php');
			
			$opengateway = new OpenGateway;
			$opengateway->Authenticate($config['api_id'], $config['secret_key'], $connect_url);
			$opengateway->SetMethod('Refund');
			$opengateway->Param('charge_id', $charge_id);
			$response = $opengateway->Process();
			
			if (isset($response['response_code']) and $response['response_code'] == '50') {
				$this->EE->db->update('exp_eedonations_payments',array('refunded' => '1'),array('charge_id' => $charge_id));
				return array('success' => TRUE);
			}
			elseif (isset($response['response_code'])) {
				return array('success' => FALSE, 'error' => $response['response_text']);
			}
			else {
				return array('success' => FALSE, 'error' => $response['error_text']);
			}
		}
		
		/**
		* Set Next Charge
		*
		* @param int $subscription_id The subscription ID #
		* @param string $next_charge_date YYYY-MM-DD format of the next charge date
		*
		* @return boolean
		*/
		function SetNextCharge ($subscription_id, $next_charge_date) {
			$this->EE->db->update('exp_eedonations_subscriptions',array('next_charge_date' => $next_charge_date),array('recurring_id' => $subscription_id));
			
			return TRUE;
		}
		
		/**
		* Update Address
		*
		* Update the address_book table and send an updated address to OG to update the customer's record
		*
		* @param int $member_id
		* @param string $first_name
		* @param string $last_name
		* @param string $street_address
		* @param string $address_2
		* @param string $city
		* @param string $region
		* @param string $region_other
		* @param string $country
		* @param string $postal_code
		* @param string $company (optional)
		* @param string $phone (optional)
		*
		* @return boolean
		*/
		function UpdateAddress ($member_id, $first_name, $last_name, $street_address, $address_2, $city, $region, $region_other, $country, $postal_code, $company = '', $phone = '') {
			$this->EE->db->select('address_id');
			$this->EE->db->where('member_id',$member_id);
			$result = $this->EE->db->get('exp_eedonations_address_book');
			
			if ($result->num_rows() > 0) {
				// update
				
				$address = $result->row_array();
				$update_array = array(
									'member_id' => $member_id,
									'first_name' => $first_name, 
									'last_name' => $last_name, 
									'address' => $street_address, 
									'address_2' => $address_2, 
									'city' => $city, 
									'region' => $region, 
									'region_other' => $region_other, 
									'country' => $country, 
									'postal_code' => $postal_code,
									'company' => $company,
									'phone' => $phone
								);
												
				$this->EE->db->update('exp_eedonations_address_book', $update_array, array('address_id' => $address['address_id']));
				
				// update OpenGateway customer record
				$config = $this->GetConfig();
					
				if (!class_exists('OpenGateway')) {
					require(dirname(__FILE__) . '/opengateway.php');
				}
				
				$connect_url = $config['api_url'] . '/api';
				$server = new OpenGateway;
				$server->Authenticate($config['api_id'], $config['secret_key'], $connect_url);
				
				// get customer ID
				$server->SetMethod('GetCustomers');
				$server->Param('internal_id', $member_id);
				$response = $server->Process();
				
				if ($response['total_results'] > 0) {	
					// there is already a customer record here
					$customer = (!isset($response['customers']['customer'][0])) ? $response['customers']['customer'] : $response['customers']['customer'][0];
					
					$server->SetMethod('UpdateCustomer');
					$server->Param('customer_id',$customer['id']);
					$server->Param('first_name', $first_name); 
					$server->Param('last_name', $last_name); 
					$server->Param('address_1', $street_address); 
					$server->Param('address_2', $address_2); 
					$server->Param('city', $city); 
					$server->Param('state', (empty($region)) ? $region_other : $region); 
					$server->Param('country', $country); 
					$server->Param('postal_code', $postal_code);
					$server->Param('company',$company);
					$server->Param('phone',$phone);
					$response = $server->Process();
				}
				else {
					// this is unexpected, there should be a record here
				}
			}
			else {
				// insert
				$insert_array = array(
									'member_id' => $member_id,
									'first_name' => (!empty($first_name)) ? $first_name : '', 
									'last_name' => (!empty($last_name)) ? $last_name : '', 
									'address' => (!empty($street_address)) ? $street_address : '', 
									'address_2' => (!empty($address_2)) ? $address_2 : '', 
									'city' => (!empty($city)) ? $city : '', 
									'region' => (!empty($region)) ? $region : '', 
									'region_other' => (!empty($region_other)) ? $region_other : '', 
									'country' => (!empty($country)) ? $country : '', 
									'postal_code' => (!empty($postal_code)) ? $postal_code : '',
									'company' => (!empty($company)) ? $company : '',
									'phone' => (!empty($phone)) ? $phone : ''
								);
				$this->EE->db->insert('exp_eedonations_address_book',$insert_array);
			}
			
			return TRUE;
		}
		
		/**
		* Get Address
		*
		* Retrieve the address from the local address book
		*
		* @param int $member_id
		*
		* @return array
		*/
		function GetAddress ($member_id) {
			$this->EE->db->where('member_id',$member_id);
			$result = $this->EE->db->get('exp_eedonations_address_book');
			
			if ($result->num_rows() > 0) {
				return $result->row_array();
			}
			else {
				return array(
						'first_name' => '', 
						'last_name' => '',
						'address' => '',
						'address_2' => '',
						'city' => '',
						'region' => '',
						'region_other' => '', 
						'country' => '',
						'postal_code' => '',
						'company' => '',
						'phone' => ''
					);
			}
		}
		
		/**
		* Get Payments
		*
		* Retrieve payment records matching filters
		*
		* @param int $offset
		* @param int $limit
		* @param int $filters['subscription_id']
		* @param int $filters['member_id']
		* @param int $filters['id']
		*
		* @return array
		*/
		function GetPayments ($offset = 0, $limit = 50, $filters = false) {
			if ($filters != false and !empty($filters) and is_array($filters)) {
				if (isset($filters['subscription_id'])) {
					$this->EE->db->where('exp_eedonations_payments.recurring_id',$filters['subscription_id']);
				}
				if (isset($filters['member_id'])) {
					$this->EE->db->where('exp_eedonations_payments.member_id',$filters['member_id']);
				}
				if (isset($filters['id'])) {
					$this->EE->db->where('exp_eedonations_payments.charge_id',$filters['id']);
				}
				if (isset($filters['search'])) {
					$this->EE->db->like('exp_eedonations_payments.payment_id',$filters['search']);
					$this->EE->db->or_like('exp_eedonations_subscriptions.recurring_id',$filters['search']);
					$this->EE->db->or_like('exp_members.screen_name',$filters['search']);
					$this->EE->db->or_like('exp_members.email',$filters['search']);
					$this->EE->db->or_like('exp_eedonations_payments.amount',$filters['search']);
				}
			}
			
			$this->EE->db->select('exp_eedonations_payments.*, exp_members.*, exp_eedonations_address_book.*', FALSE);
			$this->EE->db->join('exp_eedonations_subscriptions','exp_eedonations_subscriptions.recurring_id = exp_eedonations_payments.recurring_id','left');
			$this->EE->db->join('exp_members','exp_eedonations_payments.member_id = exp_members.member_id','left');
			$this->EE->db->join('exp_eedonations_address_book','exp_eedonations_address_book.member_id = exp_eedonations_payments.member_id','left');
			$this->EE->db->group_by('exp_eedonations_payments.charge_id');
			$this->EE->db->order_by('exp_eedonations_payments.date','DESC');
			$this->EE->db->where('exp_eedonations_payments.charge_id >','0');
			$this->EE->db->limit($limit, $offset);
				      
			$result = $this->EE->db->get('exp_eedonations_payments');
			
			$payments = array();
			
			foreach ($result->result_array() as $row) {
				$payments[] = array(
								'id' => $row['charge_id'],
								'recurring_id' => $row['recurring_id'],
								'member_id' => $row['member_id'],
								'user_screenname' => $row['screen_name'],
								'user_username' => $row['username'],
								'user_groupid' => $row['group_id'],
								'amount' => money_format("%!^i",$row['amount']), 
								'date' => date('M j, Y h:i a',strtotime($row['date'])),
								'refunded' => $row['refunded'],
								'first_name' => $row['first_name'],
								'last_name' => $row['last_name'],
								'address' => $row['address'],
								'address_2' => $row['address_2'],
								'city' => $row['city'],
								'region' => $row['region'],
								'region_other' => $row['region_other'],
								'country' => $row['country'],
								'postal_code' => $row['postal_code'],
								'company' => $row['company'],
								'phone' => $row['phone']
							);
			}
			
			if (empty($payments)) {
				return false;
			}
			else {
				return $payments;
			}
		}
		
		/**
		* Get Subscriptions
		*
		* Retrieve array of subscriptions matching filters
		*
		* @param int $offset The offset for which to load subscriptions
		* @param array $filters Filter the results
		* @param int $filters['member_id'] The EE member ID
		* @param int $filters['id'] The subscription ID
		* @param int $filters['active'] Set to "1" to retrieve only active subscriptions and "0" only ended subs
		* @param string $filters['search'] searches usernames, screen names, emails, and prices
		*
		* @return array
		*/
		function GetSubscriptions ($offset = FALSE, $limit = 50, $filters = array()) {		
			if ($filters != false and !empty($filters) and is_array($filters)) {
				if (isset($filters['member_id'])) {
					$this->EE->db->where('exp_eedonations_subscriptions.member_id',$filters['member_id']);
				}
				if (isset($filters['active'])) {
					if ($filters['active'] == '1')  {
						$this->EE->db->where('(exp_eedonations_subscriptions.end_date = \'0000-00-00 00:00:00\' OR exp_eedonations_subscriptions.end_date > NOW())',null,FALSE);
					}
					elseif ($filters['active'] == '0') {
						$this->EE->db->where('(exp_eedonations_subscriptions.end_date != \'0000-00-00 00:00:00\' AND exp_eedonations_subscriptions.end_date < NOW())',null,FALSE);
					}
				}
				if (isset($filters['id'])) {
					$this->EE->db->where('exp_eedonations_subscriptions.recurring_id',$filters['id']);
				}
				if (isset($filters['search'])) {
					$this->EE->db->like('exp_eedonations_subscriptions.recurring_id',$filters['search']);
					$this->EE->db->or_like('exp_members.username',$filters['search']);
					$this->EE->db->or_like('exp_members.screen_name',$filters['search']);
					$this->EE->db->or_like('exp_members.email',$filters['search']);
					$this->EE->db->or_like('exp_eedonations_subscriptions.amount',$filters['search']);
				}
			}	
			
			$this->EE->db->select('exp_eedonations_subscriptions.*, exp_eedonations_subscriptions.recurring_id AS subscription_id, exp_members.*, exp_eedonations_address_book.*', FALSE);
			$this->EE->db->join('exp_members','exp_eedonations_subscriptions.member_id = exp_members.member_id','left');
			$this->EE->db->join('exp_eedonations_address_book','exp_eedonations_address_book.member_id = exp_eedonations_subscriptions.member_id','left');
			$this->EE->db->group_by('exp_eedonations_subscriptions.recurring_id');
			$this->EE->db->order_by('exp_eedonations_subscriptions.date_created','DESC');
			$this->EE->db->limit($limit, $offset);
			
			$result = $this->EE->db->get('exp_eedonations_subscriptions');
			
			$subscriptions = array();
			
			foreach ($result->result_array() as $row) {
				$subscriptions[] = array(
								'id' => $row['subscription_id'],
								'member_id' => $row['member_id'],
								'user_screenname' => $row['screen_name'],
								'user_username' => $row['username'],
								'user_groupid' => $row['group_id'],
								'amount' => money_format("%!^i",$row['amount']),
								'interval' => $row['interval'],
								'date_created' => date('M j, Y h:i a',strtotime($row['date_created'])),
								'date_cancelled' => (!strstr($row['date_cancelled'],'0000-00-00')) ? date('M j, Y h:i a',strtotime($row['date_cancelled'])) : FALSE,
								'next_charge_date' => ($row['next_charge_date'] != '0000-00-00') ? date('M j, Y',strtotime($row['next_charge_date'])) : FALSE,
								'end_date' => ($row['end_date'] == '0000-00-00 00:00:00') ? FALSE : date('M j, Y h:i a',strtotime($row['end_date'])),
								'active' => ($row['active'] == '1') ? '1' : '0',
								'cancelled' => $row['cancelled'],
								'expired' => $row['expired'],
								'renewed' => (empty($row['renewed_recurring_id'])) ? FALSE : TRUE,
								'renewed_recurring_id' => $row['renewed_recurring_id'],
								'first_name' => $row['first_name'],
								'last_name' => $row['last_name'],
								'address' => $row['address'],
								'address_2' => $row['address_2'],
								'city' => $row['city'],
								'region' => $row['region'],
								'region_other' => $row['region_other'],
								'country' => $row['country'],
								'postal_code' => $row['postal_code'],
								'company' => $row['company'],
								'phone' => $row['phone']
							);
			}
			
			if (empty($subscriptions)) {
				return FALSE;
			}
			else {
				return $subscriptions;
			}
		}
		
		/**
		* Get Subscription
		*
		* @param int $subscription_id
		*
		* @return array
		*/
		function GetSubscription ($subscription_id) {		
			$subscriptions = $this->GetSubscriptions(FALSE, 1, array('id' => $subscription_id));
	
			if (isset($subscriptions[0])) {
				return $subscriptions[0];
			}
			
			return FALSE;
		}	
	
		/**
		* Cancel Subscription
		*
		* @param int $sub_id
		* @param boolean $make_api_call (default: FALSE) Shall we tell OG?
		* @param boolean $expired (default: FALSE) Is this expiring?
		*
		* @return boolean
		*/
		function CancelSubscription ($sub_id, $make_api_call = TRUE, $expired = FALSE) {
			if (!$subscription = $this->GetSubscription($sub_id)) {
				return FALSE;
			}
						
			// calculate end_date
			// oh wait!  cancel it now - there's no advantage to delaying this
			$end_date = date('Y-m-d H:i:s');
			
			// nullify next charge
			$next_charge_date = '0000-00-00';
			
			// cancel subscription
		 	$update_array = array(
		 						'active' => '0',
		 						'end_date' => $end_date,
		 						'next_charge_date' => $next_charge_date,
		 						'date_cancelled' => date('Y-m-d H:i:s')
		 					);	
		 	// are we cancelling or expiring?
		 	if ($expired == TRUE) {
		 		$update_array['expired'] = '1';
		 	}
		 	else {
		 		$update_array['cancelled'] = '1';
		 	}
		 	
		 	$this->EE->db->update('exp_eedonations_subscriptions',$update_array,array('recurring_id' => $subscription['id']));
			
			if ($make_api_call == true) {
				$config = $this->GetConfig();
				
				if (!class_exists('OpenGateway')) {
					require(dirname(__FILE__) . '/opengateway.php');
				}
				
				$connect_url = $config['api_url'] . '/api';
				$server = new OpenGateway;
				$server->Authenticate($config['api_id'], $config['secret_key'], $connect_url);
				$server->SetMethod('CancelRecurring');
				$server->Param('recurring_id',$subscription['id']);
				$response = $server->Process();
				if (isset($response['error'])) {
					return FALSE;
				}
			}
			
			// call "eedonations_cancel" hook with: member_id, subscription_id, end_date
			if ($this->EE->extensions->active_hook('eedonations_cancel') == TRUE)
			{
			    $this->EE->extensions->call('eedonations_cancel', $subscription['member_id'], $subscription['id'], $subscription['end_date']);
			    if ($this->EE->extensions->end_script === TRUE) return;
			} 
					
			return TRUE;
		}
		
		/**
		* Calculate End Date
		*
		* Calculate the end date for a subscription based on its current state
		*
		* @param datetime $end_date
		* @param date $next_charge_date
		* @param datetime $start_date
		*
		* @return datetime $end_date
		*/
		function _calculate_end_date ($end_date, $next_charge_date, $start_date) {
			$next_charge_date = date('Y-m-d',strtotime($next_charge_date));
			$end_date = date('Y-m-d H:i:s',strtotime($end_date));

			if ($next_charge_date != '0000-00-00' and (strtotime($next_charge_date) + (60*60*24)) > time()) {
				// there's a next charge date which won't be renewed, so we'll end it then
				// we must also account for their signup time
				$time_created = date('H:i:s',strtotime($start_date));
				$end_date = $next_charge_date . ' ' . $time_created;
			}
			elseif ($end_date != '0000-00-00 00:00:00') {
				// there is a set end_date
				$end_date = $end_date['end_date'];
			}
			else {
				// for some reason, neither a next_charge_date or an end_date exist
				// let's end this now
				$end_date = date('Y-m-d H:i:s');
			}
			
			return $end_date;
		}
				
		/**
		* Get Config
		*
		* Retrieve the names/values in the config table
		*
		* @return array
		*/
		function GetConfig () {
			$result = $this->EE->db->get('exp_eedonations_config');
			
			if ($result->num_rows() == 0) {
				return FALSE;
			}
			else {
				$config = $result->row_array();
				$config['intervals'] = unserialize($config['intervals']);
				$config['amounts'] = unserialize($config['amounts']);
				$config['allow_custom_intervals'] = empty($config['allow_custom_intervals']) ? FALSE : TRUE;
				$config['allow_custom_amounts'] = empty($config['allow_custom_amounts']) ? FALSE : TRUE;
				$config['maximum_amount'] = money_format("%^!i",$config['maximum_amount']);
				$config['minimum_amount'] = money_format("%^!i",$config['minimum_amount']);
				
				return $config;
			}
		}
		
		/**
		* Get Regions
		*
		* Return an array of all regions and their shortcodes
		*
		* @return array
		*/
		function GetRegions () {
			return array(
					'AL' => 'Alabama',
					'AK' => 'Alaska',
					'AZ' => 'Arizona',
					'AR' => 'Arkansas',
					'CA' => 'California',
					'CO' => 'Colorado',
					'CT' => 'Connecticut',
					'DE' => 'Delaware',
					'DC' => 'District of Columbia',
					'FL' => 'Florida',
					'GA' => 'Georgia',
					'HI' => 'Hawaii',
					'ID' => 'Idaho',
					'IL' => 'Illinois',
					'IN' => 'Indiana',
					'IA' => 'Iowa',
					'KS' => 'Kansas',
					'KY' => 'Kentucky',
					'LA' => 'Louisiana',
					'ME' => 'Maine',
					'MD' => 'Maryland',
					'MA' => 'Massachusetts',
					'MI' => 'Michigan',
					'MN' => 'Minnesota',
					'MS' => 'Mississippi',
					'MO' => 'Missouri',
					'MT' => 'Montana',
					'NE' => 'Nebraska',
					'NV' => 'Nevada',
					'NH' => 'New Hampshire',
					'NJ' => 'New Jersey',
					'NM' => 'New Mexico',
					'NY' => 'New York',
					'NC' => 'North Carolina',
					'ND' => 'North Dakota',
					'OH' => 'Ohio',
					'OK' => 'Oklahoma',
					'OR' => 'Oregon',
					'PA' => 'Pennsylvania',
					'RI' => 'Rhode Island',
					'SC' => 'South Carolina',
					'SD' => 'South Dakota',
					'TN' => 'Tennessee',
					'TX' => 'Texas',
					'UT' => 'Utah',
					'VT' => 'Vermont',
					'VA' => 'Virginia',
					'WA' => 'Washington',
					'WV' => 'West Virginia',
					'WI' => 'Wisconsin',
					'WY' => 'Wyoming',
					'AB' => 'Alberta',
					'BC' => 'British Columbia',
					'MB' => 'Manitoba',
					'NB' => 'New Brunswick',
					'NL' => 'Newfoundland and Laborador',
					'NT' => 'Northwest Territories',
					'NS' => 'Nova Scotia',
					'NU' => 'Nunavut',
					'ON' => 'Ontario',
					'PE' => 'Prince Edward Island',
					'QC' => 'Quebec',
					'SK' => 'Saskatchewan',
					'YT' => 'Yukon'
				);
		}
		
		/**
		* Get Countries
		*
		* Return an array of all countries and their shortcodes
		*
		* @return array
		*/
		function GetCountries () {
	    	$countries = $this->EE->db->where('available','1')->get('exp_eedonations_countries');
	    	
	    	$return = array();
	    	
	    	foreach ($countries->result_array() as $country) {
	    		$return[$country['iso2']] = $country['name'];
	    	}
	    	
	    	return $return;
		}
	
	}
}

/**
* Define money_format
*/
if (!function_exists("money_format")) {
	function money_format($format, $number)
	{
	    $regex  = '/%((?:[\^!\-]|\+|\(|\=.)*)([0-9]+)?'.
	              '(?:#([0-9]+))?(?:\.([0-9]+))?([in%])/';
	    if (setlocale(LC_MONETARY, 0) == 'C') {
	        setlocale(LC_MONETARY, '');
	    }
	    $locale = localeconv();
	    preg_match_all($regex, $format, $matches, PREG_SET_ORDER);
	    foreach ($matches as $fmatch) {
	        $value = floatval($number);
	        $flags = array(
	            'fillchar'  => preg_match('/\=(.)/', $fmatch[1], $match) ?
	                           $match[1] : ' ',
	            'nogroup'   => preg_match('/\^/', $fmatch[1]) > 0,
	            'usesignal' => preg_match('/\+|\(/', $fmatch[1], $match) ?
	                           $match[0] : '+',
	            'nosimbol'  => preg_match('/\!/', $fmatch[1]) > 0,
	            'isleft'    => preg_match('/\-/', $fmatch[1]) > 0
	        );
	        $width      = trim($fmatch[2]) ? (int)$fmatch[2] : 0;
	        $left       = trim($fmatch[3]) ? (int)$fmatch[3] : 0;
	        $right      = trim($fmatch[4]) ? (int)$fmatch[4] : $locale['int_frac_digits'];
	        $conversion = $fmatch[5];
	
	        $positive = true;
	        if ($value < 0) {
	            $positive = false;
	            $value  *= -1;
	        }
	        $letter = $positive ? 'p' : 'n';
	
	        $prefix = $suffix = $cprefix = $csuffix = $signal = '';
	
	        $signal = $positive ? $locale['positive_sign'] : $locale['negative_sign'];
	        switch (true) {
	            case $locale["{$letter}_sign_posn"] == 1 && $flags['usesignal'] == '+':
	                $prefix = $signal;
	                break;
	            case $locale["{$letter}_sign_posn"] == 2 && $flags['usesignal'] == '+':
	                $suffix = $signal;
	                break;
	            case $locale["{$letter}_sign_posn"] == 3 && $flags['usesignal'] == '+':
	                $cprefix = $signal;
	                break;
	            case $locale["{$letter}_sign_posn"] == 4 && $flags['usesignal'] == '+':
	                $csuffix = $signal;
	                break;
	            case $flags['usesignal'] == '(':
	            case $locale["{$letter}_sign_posn"] == 0:
	                $prefix = '(';
	                $suffix = ')';
	                break;
	        }
	        if (!$flags['nosimbol']) {
	            $currency = $cprefix .
	                        ($conversion == 'i' ? $locale['int_curr_symbol'] : $locale['currency_symbol']) .
	                        $csuffix;
	        } else {
	            $currency = '';
	        }
	        $space  = $locale["{$letter}_sep_by_space"] ? ' ' : '';
	
	        $value = number_format($value, $right, $locale['mon_decimal_point'],
	                 $flags['nogroup'] ? '' : $locale['mon_thousands_sep']);
	        $value = @explode($locale['mon_decimal_point'], $value);
	
	        $n = strlen($prefix) + strlen($currency) + strlen($value[0]);
	        if ($left > 0 && $left > $n) {
	            $value[0] = str_repeat($flags['fillchar'], $left - $n) . $value[0];
	        }
	        $value = implode($locale['mon_decimal_point'], $value);
	        if ($locale["{$letter}_cs_precedes"]) {
	            $value = $prefix . $currency . $space . $value . $suffix;
	        } else {
	            $value = $prefix . $value . $space . $currency . $suffix;
	        }
	        if ($width > 0) {
	            $value = str_pad($value, $width, $flags['fillchar'], $flags['isleft'] ?
	                     STR_PAD_RIGHT : STR_PAD_LEFT);
	        }
	
	        $format = str_replace($fmatch[0], $value, $format);
	    }
	    return $format;
	} 
}