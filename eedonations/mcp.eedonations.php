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

class Eedonations_mcp {
	var $eedonations_class; // EEDonations Class
	var $EE;	 // EE SuperObject
	var $server; // OpenGateway
	var $per_page = 50;

	function __construct () {
		// load EE superobject
		$this->EE =& get_instance();

		// load EEDonations class
		require(dirname(__FILE__) . '/class.eedonations.php');
		$this->eedonations_class = new EEDonations_class;

		// load config
		$this->config = $this->eedonations_class->GetConfig();

		// load OpenGateway
		if (isset($this->config['api_url'])) {
			require(dirname(__FILE__) . '/opengateway.php');
			$this->server = new OpenGateway;

			$this->server->Authenticate($this->config['api_id'], $this->config['secret_key'], $this->config['api_url'] . '/api');
		}

        // prep navigation
        $this->EE->cp->set_right_nav(array(
        					'eedonations_dashboard' => $this->cp_url(),
        					'eedonations_create_donation' => $this->cp_url('create'),
        					'eedonations_payments' => $this->cp_url('payments'),
        					'eedonations_subscriptions' => $this->cp_url('subscriptions'),
        					'eedonations_custom_fields' => $this->cp_url('custom_fields'),
        					'eedonations_settings' => $this->cp_url('settings')
        				));

        // set breadcrumb for the entire module
        $this->EE->cp->set_breadcrumb($this->cp_url(), $this->EE->lang->line('eedonations_module_name'));

        // load required libraries
        $this->EE->load->library('table');

        // load CSS
        $this->EE->cp->add_to_head('<style type="text/css" media="screen">
        								div.eedonations_box {
        									border: 1px solid #ccc;
        									background-color: #fff;
        									padding: 10px;
        									margin: 10px;
        									line-height: 1.4em;
        								}

        								div.eedonations_error {
        									border: 1px solid #aa0303;
        									background-color: #aa0303;
        									color: #fff;
        									font-weight: bold;
        									padding: 10px;
        									margin: 10px;
        									line-height: 1.4em;
        								}

        								ul.eedonations {
        									list-style-type: square;
        									margin-left: 25px;
        									margin-top: 10px;
        								}

        								ul.eedonations li {
        									padding: 5px;
        								}

        								ol.eedonations {
        									margin-left: 25px;
        									margin-top: 10px;
        								}

        								ol.eedonations li {
        									padding: 5px;
        								}

        								span.html {
        									font-family: Monaco, \'Bitstream Vera Sans Mono\',\'Courier\',monospace;
        									background-color: #fff;
        									padding: 1px 2px;
        								}

        							</style>');

        // add JavaScript
        $this->EE->cp->add_to_head('<script type="text/javascript">
        								$(document).ready(function() {
        									$(\'a.confirm\').click(function () {
        										if (!confirm(\'Are you sure you want to delete this?\')) {
        											return false;
        										}
        									});
        								});
        							</script>');
	}

	function index () {
		// if not configured, send to settings
		if (!$this->config) {
			$this->EE->functions->redirect(htmlspecialchars_decode($this->cp_url('settings')));
			die();
		}

		// page title
		$this->EE->cp->set_variable('cp_page_title', $this->EE->lang->line('eedonations_module_name'));

		// get latest payments
		$payments = $this->eedonations_class->GetPayments(0,10);

		if (is_array($payments)) {
			foreach ($payments as $key => $payment) {
				$payments[$key]['sub_link'] = empty($payment['recurring_id']) ? FALSE : '<a href="' . $this->cp_url('subscription',array('id' => $payment['recurring_id'])) . '">' . $payment['recurring_id'] . '</a>';
				if ($payment['amount'] != '0.00') {
					$payments[$key]['refund_text'] = ($payment['refunded'] == '0') ? ' | <a href="' . $this->cp_url('refund',array('id' => $payment['id'], 'return' => urlencode(base64_encode(htmlspecialchars_decode($this->cp_url('index')))))) . '">' . $this->EE->lang->line('eedonations_refund') . '</a>' : 'refunded';
				}
				else {
					$payments[$key]['refund_text'] = '';
				}

				$payments[$key]['member_link'] = $this->member_link($payment['member_id']);
				$payments[$key]['view_link'] = $this->cp_url('payment', array('id' => $payment['id']));
			}
			reset($payments);
		}

		// get monthly totals
		$result = $this->EE->db->query('SELECT SUM(amount) AS `revenue`, MONTH(date) AS `date_month`, YEAR(date) AS `date_year`
									    FROM `exp_eedonations_payments`
									    WHERE YEAR(date) > 0 and `refunded` = \'0\'
									    GROUP BY YEAR(date), MONTH(date)
									    ORDER BY `date` DESC');

		$months = array();
		if ($result->num_rows() > 0) {
			foreach ($result->result_array() as $month) {
				$month['date_month'] = str_pad($month['date_month'], 2, '0', STR_PAD_LEFT);

				$months[$month['date_month'] . $month['date_year']] = array(
								'code' => $month['date_month'] . $month['date_year'],
								'year' => $month['date_year'],
								'month' => date('F', strtotime('2011-' . $month['date_month'] . '-01 12:12:12')),
								'revenue' => $this->config['currency_symbol'] . money_format("%^!i",$month['revenue'])
							);
			}
		}

		$vars = array();
		$vars['payments'] = $payments;
		$vars['config'] = $this->config;
		$vars['months'] = $months;

		return $this->EE->load->view('dashboard',$vars, TRUE);
	}

	function current_action ($action) {
		$DSP->title = $this->nav[$action];

		$this->current_action = $action;

		return true;
	}

	function cp_url ($action = 'index', $variables = array()) {
		$url = BASE.AMP.'C=addons_modules'.AMP.'M=show_module_cp' . AMP . 'module=eedonations'.AMP.'method=' . $action;

		foreach ($variables as $variable => $value) {
			$url .= AMP . $variable . '=' . $value;
		}

		return $url;
	}

	function form_url ($action = 'index', $variables = array()) {
		$url = AMP.'C=addons_modules'.AMP.'M=show_module_cp' . AMP . 'module=eedonations'.AMP.'method=' . $action;

		foreach ($variables as $variable => $value) {
			$url .= AMP . $variable . '=' . $value;
		}

		return $url;
	}

	function member_link ($member_id) {
		// if they are anonymous, they don't have a member link
		if (strpos($member_id,'anon') !== FALSE) {
			return FALSE;
		}

		$url = BASE.AMP.'D=cp'.AMP.'C=myaccount'.AMP.'id='. $member_id;

		return $url;
	}

	function custom_fields () {
		// page title
		$this->EE->cp->set_variable('cp_page_title', $this->EE->lang->line('eedonations_custom_fields'));

		return $this->EE->load->view('custom_fields', FALSE, TRUE);
	}

	function payments () {
		// page title
		$this->EE->cp->set_variable('cp_page_title', $this->EE->lang->line('eedonations_payments'));

		// get pagination
		$offset = ($this->EE->input->get('rownum')) ? $this->EE->input->get('rownum') : 0;

		// get latest payments
		$payments = $this->eedonations_class->GetPayments($offset,$this->per_page);

		if (is_array($payments)) {
			foreach ($payments as $key => $payment) {
				$payments[$key]['sub_link'] = (empty($payment['recurring_id'])) ? FALSE : '<a href="' . $this->cp_url('subscription',array('id' => $payment['recurring_id'])) . '">' . $payment['recurring_id'] . '</a>';
				if ($payment['amount'] != '0.00') {
					$payments[$key]['refund_text'] = ($payment['refunded'] == '0') ? '&nbsp;|&nbsp;<a href="' . $this->cp_url('refund',array('id' => $payment['id'], 'return' => urlencode(base64_encode(htmlspecialchars_decode($this->cp_url('payments')))))) . '">' . $this->EE->lang->line('eedonations_refund') . '</a>' : '&nbsp;|&nbsp;refunded';
				}
				else {
					$payments[$key]['refund_text'] = '';
				}

				$payments[$key]['member_link'] = $this->member_link($payment['member_id']);
				$payments[$key]['view_link'] = $this->cp_url('payment', array('id' => $payment['id']));
			}
			reset($payments);
		}

		// pagination
		$total = $this->EE->db->count_all('exp_eedonations_payments');

		// pass the relevant data to the paginate class so it can display the "next page" links
		$this->EE->load->library('pagination');
		$p_config = $this->pagination_config('payments', $total);

		$this->EE->pagination->initialize($p_config);

		$vars = array();
		$vars['export_link'] = $this->cp_url('export_payments');
		$vars['payments'] = $payments;
		$vars['config'] = $this->config;
		$vars['pagination'] = $this->EE->pagination->create_links();

		return $this->EE->load->view('payments',$vars, TRUE);
	}

	//---------------------------------------------------------------

	function export_payments()
	{
		// Get the results from the database
		$this->EE->db->select('exp_eedonations_payments.*, exp_members.*, exp_eedonations_address_book.*', FALSE);
		$this->EE->db->join('exp_eedonations_subscriptions','exp_eedonations_subscriptions.recurring_id = exp_eedonations_payments.recurring_id','left');
		$this->EE->db->join('exp_members','exp_eedonations_payments.member_id = exp_members.member_id','left');
		$this->EE->db->join('exp_eedonations_address_book','exp_eedonations_address_book.member_id = exp_eedonations_payments.member_id','left');
		$this->EE->db->join('exp_eedonations_custom_fields', 'exp_eedonations_custom_fields.payment_id=exp_eedonations_payments.payment_id', 'left');
		$this->EE->db->group_by('exp_eedonations_payments.charge_id');
		$this->EE->db->order_by('exp_eedonations_payments.date','DESC');
		$this->EE->db->where('exp_eedonations_payments.charge_id >','0');

		$result = $this->EE->db->get('exp_eedonations_payments');

		$this->EE->load->dbutil();

		$rows = $this->EE->dbutil->csv_from_result($result);

		$this->EE->load->helper('download');
		force_download('EEDonationsExport.csv', $rows);
	}

	//---------------------------------------------------------------

	function payment () {
		// get donation
		$payment = $this->eedonations_class->GetPayments(0,1,array('id' => $this->EE->input->get('id')));
		$payment = $payment[0];

		// page title
		$this->EE->cp->set_variable('cp_page_title', 'Donation ID #' . $payment['id']);

		$payment['member_link'] = (!empty($payment['member_id'])) ? $this->member_link($payment['member_id']) : '';

		// get subscription?
		if (!empty($payment['recurring_id'])) {
			$subscription = $this->eedonations_class->GetSubscription($payment['recurring_id']);

			$subscription['link'] = $this->cp_url('subscription', array('id' => $subscription['id']));

			if ($subscription['active'] == '1') {
				$status = 'Active';
			}
			elseif ($subscription['expired'] == '1') {
				$status = 'Expired';
			}
			elseif ($subscription['renewed'] == TRUE) {
				$status = 'Renewed with subscription #<a href="' . $this->cp_url('subscription', array('id' => $subscription['renewed_recurring_id'])) . '">' . $subscription['renewed_recurring_id'] . '</a>';
			}
			elseif ($subscription['cancelled'] == '1') {
				$status = 'Cancelled';
			}
			else {
				$status = 'Unknown';
			}

			$subscription['status'] = $status;
		}
		else {
			$subscription = FALSE;
		}

		// refund text
		if ((float)$payment['amount'] != 0) {
			$payment['refund_text'] = ($payment['refunded'] == '0') ? '<a href="' . $this->cp_url('refund',array('id' => $payment['id'], 'return' => urlencode(base64_encode(htmlspecialchars_decode($this->cp_url('payment',array('id' => $this->EE->input->get('id')))))))) . '">' . $this->EE->lang->line('eedonations_refund') . '</a>' : 'refunded';
		}
		else {
			$payment['refund_text'] = 'n/a';
		}

		// get custom field data
		if (!empty($payment['recurring_id'])) {
			$this->EE->db->where('recurring_id', $payment['recurring_id']);
		}
		else {
			$this->EE->db->where('payment_id', $payment['id']);
		}
		$result = $this->EE->db->get('exp_eedonations_custom_fields');

		$custom_fields = array();

		if ($result->num_rows()) {
			foreach ($result->result_array() as $field) {
				$custom_fields[$field['custom_field_name']] = $field['custom_field_value'];
			}
		}

		$vars = array();
		$vars['payment'] = $payment;
		$vars['subscription'] = $subscription;
		$vars['custom_fields'] = $custom_fields;
		$vars['config'] = $this->config;

		return $this->EE->load->view('payment',$vars, TRUE);
	}

	function refund () {
		$response = $this->eedonations_class->Refund($this->EE->input->get('id'));

		if ($response['success'] != TRUE) {
			return $this->EE->load->view('error',array('error' => $response['error']), TRUE);
		}
		else {
			// it refunded
			header('Location: ' . base64_decode(urldecode($this->EE->input->get('return'))));
			die();
		}
	}

	function subscriptions () {
		// page title
		$this->EE->cp->set_variable('cp_page_title', $this->EE->lang->line('eedonations_subscriptions'));

		// get pagination
		$offset = ($this->EE->input->get('rownum')) ? $this->EE->input->get('rownum') : 0;

		// is there a query
		if ($this->EE->input->get('search')) {
			$filters = array('search' => $this->EE->input->get('search'));
		}
		else {
			$filters = array();
		}

		// get latest payments
		$subscriptions = $this->eedonations_class->GetSubscriptions($offset,$this->per_page, $filters);

		if (is_array($subscriptions)) {
			// append $options links
			foreach ($subscriptions as $key => $subscription) {
				$options = '<a href="' . $this->cp_url('subscription', array('id' => $subscription['id'])) . '">' . $this->EE->lang->line('eedonations_view') . '</a>';

				if ($subscription['active'] == '1') {
					$options .= ' | <a href="' . $this->cp_url('update_cc', array('id' => $subscription['id'])) . '">' . $this->EE->lang->line('eedonations_update_cc') . '</a>';

					if ($subscription['end_date'] != FALSE) {
	   					$options .= ' | <a href="' . $this->cp_url('expiry', array('id' => $subscription['id'])) . '">' . $this->EE->lang->line('eedonations_change_expiration') . '</a>';
	   				}

					$options .= ' | <a class="confirm" href="' . $this->cp_url('cancel_subscription',array('id' => $subscription['id'])) . '">' . $this->EE->lang->line('eedonations_cancel') . '</a>';
				}

				$subscriptions[$key]['options'] = $options;
				$subscriptions[$key]['member_link'] = $this->member_link($subscription['member_id']);
			}

			reset($subscriptions);
		}

		// pagination
		if (!empty($filters)) {
			$total = count($this->eedonations_class->GetSubscriptions(0,10000,$filters));
		}
		else {
			$result = $this->EE->db->select('count(recurring_id) AS total_rows',FALSE)->from('exp_eedonations_subscriptions')->get();
			$total = $result->row()->total_rows;
		}

		// pass the relevant data to the paginate class so it can display the "next page" links
		$this->EE->load->library('pagination');
		$p_config = $this->pagination_config('subscriptions', $total);

		$this->EE->pagination->initialize($p_config);

		// get search fields
		$url = htmlspecialchars_decode($this->cp_url('subscriptions'));
		$url = explode('?',$url);
		$params = array();
		parse_str($url[1],$params);

		$vars = array();
		$vars['subscriptions'] = $subscriptions;
		$vars['pagination'] = $this->EE->pagination->create_links();
		$vars['config'] = $this->config;
		$vars['search_fields'] = $params;
		$vars['search_query'] = $this->EE->input->get('search');
		$vars['cp_url'] = $this->cp_url('subscriptions');

		return $this->EE->load->view('subscriptions',$vars, TRUE);
	}

	function subscription () {
		// page title
		$this->EE->cp->set_variable('cp_page_title', $this->EE->lang->line('eedonations_subscription'));

		$subscription = $this->eedonations_class->GetSubscription($this->EE->input->get('id'));

		// load payments
		$payments = $this->eedonations_class->GetPayments(0, 100, array('subscription_id' => $subscription['id']));

		// calculate total money received
		$total_amount = 0;
		if (is_array($payments)) {
			foreach ($payments as $key => $payment) {
				$total_amount = $total_amount + $payment['amount'];
				if ($payment['amount'] != '0.00') {
					$payments[$key]['refund_text'] = ($payment['refunded'] == '0') ? '<a href="' . $this->cp_url('refund',array('id' => $payment['id'], 'return' => urlencode(base64_encode(htmlspecialchars_decode($this->cp_url('subscription',array('id' => $this->EE->input->get('id')))))))) . '">' . $this->EE->lang->line('eedonations_refund') . '</a>' : 'refunded';
				}
				else {
					$payments[$key]['refund_text'] = '';
				}

				$payments[$key]['link'] = $this->cp_url('payment', array('id' => $payment['id']));
			}
			reset($payments);
		}

		$subscription['total_amount'] = $total_amount;

		if ($subscription['active'] == '1') {
			$status = $this->EE->lang->line('eedonations_active');
			$status .= ' | <a href="' . $this->cp_url('cancel_subscription',array('id' => $subscription['id'])) . '">' . $this->EE->lang->line('eedonations_cancel') . '</a>';
		}
		elseif ($subscription['expired'] == '1') {
			$status = $this->EE->lang->line('eedonations_expired');
		}
		elseif ($subscription['renewed'] == TRUE) {
			$status = 'Renewed with <a href=" ' . $this->cp_url('subscription',array('id' => $subscription['renewed_recurring_id'])) . '">subscription #' . $subscription['renewed_recurring_id'] . '</a>';
		}
		elseif ($subscription['cancelled'] == '1') {
			$status = $this->EE->lang->line('eedonations_cancelled');
		}
		else {
			$status = 'Unknown';
		}

		$subscription['status'] = $status;

		$subscription['member_link'] = $this->member_link($subscription['member_id']);

		// should we have an expiry mod?
		if ($subscription['end_date'] != FALSE) {
			$change_expiry = ' (<a href="' . $this->cp_url('expiry',array('id' => $subscription['id'])) . '">modify expiration date</a>)';
		}
		else {
			$change_expiry = FALSE;
		}

		// get custom field data
		if (!empty($payment['recurring_id'])) {
			$this->EE->db->where('recurring_id', $payment['recurring_id']);
		}
		else {
			$this->EE->db->where('payment_id', $payment['id']);
		}
		$result = $this->EE->db->get('exp_eedonations_custom_fields');

		$custom_fields = array();

		if ($result->num_rows()) {
			foreach ($result->result_array() as $field) {
				$custom_fields[$field['custom_field_name']] = $field['custom_field_value'];
			}
		}

		$vars = array();
		$vars['subscription'] = $subscription;
		$vars['payments'] = $payments;
		$vars['config'] = $this->config;
		$vars['change_expiry'] = $change_expiry;
		$vars['custom_fields'] = $custom_fields;

		return $this->EE->load->view('subscription',$vars,TRUE);
	}

	function expiry () {
		$this->EE->cp->set_variable('cp_page_title', $this->EE->lang->line('eedonations_change_expiry_title'));

		$recurring_id = $this->EE->input->get('id');
		$subscription = $this->eedonations_class->GetSubscription($recurring_id);

		// end date
	    $end_date_days = array();
	    for ($i = 1; $i <= 31; $i++) {
        	$end_date_days[$i] = $i;
        }

        $end_date_months = array();
	    for ($i = 1; $i <= 12; $i++) {
        	$end_date_months[$i] = date('m - M',mktime(1, 1, 1, $i, 1, 2010));
        }

        $end_date_years = array();
	    for ($i = date('Y'); $i <= (date('Y') + 3); $i++) {
        	$end_date_years[$i] = $i;
        }

        $subscription['end_date'] = array(
        								'day' => date('d', strtotime($subscription['end_date'])),
        								'month' => date('m', strtotime($subscription['end_date'])),
        								'year' => date('Y', strtotime($subscription['end_date']))
        							);

		// errors
		$errors = ($this->EE->session->flashdata('errors')) ? $this->EE->session->flashdata('errors') : FALSE;

		$vars = array();
		$vars['end_date_days'] = $end_date_days;
		$vars['end_date_months'] = $end_date_months;
		$vars['end_date_years'] = $end_date_years;
		$vars['form_action'] = $this->form_url('post_expiry');
		$vars['subscription'] = $subscription;
		$vars['errors'] = $errors;

		return $this->EE->load->view('expiry',$vars, TRUE);
	}

	function post_expiry () {
		// setup validation
		$this->EE->load->library('form_validation');
		$this->EE->form_validation->set_rules('subscription_id','Subscription ID','required');

		// get subscription
		$subscription = $this->eedonations_class->GetSubscription($this->EE->input->post('subscription_id'));

		if ($this->EE->form_validation->run() !== FALSE) {
			$new_expiry = $this->EE->input->post('end_date_year') . '-' . $this->EE->input->post('end_date_month') . '-' . $this->EE->input->post('end_date_day');

			$this->eedonations_class->UpdateExpiryDate($subscription['id'], $new_expiry);

			// record payment
			if ($this->EE->input->post('record_payment') == '1') {
				// connect to OG
				$this->server->SetMethod('RecordSubscriptionPayment');
				$this->server->Param('recurring_id', $subscription['id']);
				$this->server->Param('amount', $this->EE->input->post('payment_amount'));

				$response = $this->server->Process();

				if (!isset($response['error'])) {
					$this->eedonations_class->RecordPayment($subscription['id'], $response['charge_id'], $this->EE->input->post('payment_amount'));
				}
			}

			// success!
			$this->EE->session->set_flashdata('message_success', 'You have successfully updated this subscription.');

			// redirect to URL
			$this->EE->functions->redirect(htmlspecialchars_decode($this->cp_url('subscription', array('id' => $subscription['id']))));

			die();
			return TRUE;
		}
		else {
			$this->EE->session->set_flashdata('errors',validation_errors());
			$this->EE->functions->redirect(htmlspecialchars_decode($this->cp_url('expiry', array('id' => $subscription['id']))));

			die();
			return FALSE;
		}
	}

	function update_cc () {
		$this->EE->cp->set_variable('cp_page_title', $this->EE->lang->line('eedonations_update_cc_title'));

		$recurring_id = $this->EE->input->get('id');
		$subscription = $this->eedonations_class->GetSubscription($recurring_id);

		// get select user
		$this->EE->load->model('member_model');
	    $member = $this->EE->member_model->get_member_data($subscription['member_id']);
	    $member = $member->row_array();

	    // end date
	    $end_date_days = array();
	    for ($i = 1; $i <= 31; $i++) {
        	$end_date_days[$i] = $i;
        }

        $end_date_months = array();
	    for ($i = 1; $i <= 12; $i++) {
        	$end_date_months[$i] = date('m - M',mktime(1, 1, 1, $i, 1, 2010));
        }

        $end_date_years = array();
	    for ($i = date('Y'); $i <= (date('Y') + 3); $i++) {
        	$end_date_years[$i] = $i;
        }

        // cc expiry date
        $expiry_date_years = array();

        for ($i = date('Y'); $i <= (date('Y') + 10); $i++) {
        	$expiry_date_years[$i] = $i;
        }

        // get address if available
        $address = $this->eedonations_class->GetAddress($member['member_id']);

        // get regions
        $regions = $this->eedonations_class->GetRegions();

		$region_options = array();
		$region_options[] = '';
		foreach ($regions as $code => $region) {
			$region_options[$code] = $region;
		}

        // get countries
        $countries = $this->eedonations_class->GetCountries();

		$country_options = array();
		$country_options[] = '';
		foreach ($countries as $country_code => $country) {
			$country_options[$country_code] = $country;
		}

		// errors
		$errors = ($this->EE->session->flashdata('errors')) ? $this->EE->session->flashdata('errors') : FALSE;

		$vars = array();
		$vars['config'] = $this->config;
		$vars['member'] = $member;
		$vars['end_date_days'] = $end_date_days;
		$vars['end_date_months'] = $end_date_months;
		$vars['end_date_years'] = $end_date_years;
		$vars['expiry_date_years'] = $expiry_date_years;
		$vars['form_action'] = $this->form_url('post_update_cc');
		$vars['regions'] = $region_options;
		$vars['countries'] = $country_options;
		$vars['address'] = $address;
		$vars['subscription'] = $subscription;
		$vars['errors'] = $errors;

		return $this->EE->load->view('update_cc',$vars, TRUE);
	}

	function post_update_cc () {
		// setup validation
		$this->EE->load->library('form_validation');
		$this->EE->form_validation->set_rules('subscription_id','Subscription ID','required');

		$this->EE->form_validation->set_rules('first_name','lang:eedonations_order_form_customer_first_name','trim|required');
		$this->EE->form_validation->set_rules('last_name','lang:eedonations_order_form_customer_last_name','trim|required');
		$this->EE->form_validation->set_rules('address','lang:eedonations_order_form_customer_address','trim|required');
		$this->EE->form_validation->set_rules('city','lang:eedonations_order_form_customer_city','trim|required');
		$this->EE->form_validation->set_rules('country','lang:eedonations_order_form_customer_country','trim|required');
		$this->EE->form_validation->set_rules('postal_code','lang:eedonations_order_form_customer_postal_code','trim|required');

		$this->EE->form_validation->set_rules('cc_number','Credit Card Number','trim|required');
		$this->EE->form_validation->set_rules('cc_name','Credit Card Name','trim|required');

		// get subscription
		$subscription = $this->eedonations_class->GetSubscription($this->EE->input->post('subscription_id'));

		if ($this->EE->form_validation->run() !== FALSE) {
			// update address
			$this->eedonations_class->UpdateAddress($subscription['member_id'],$this->EE->input->post('first_name'),$this->EE->input->post('last_name'),$this->EE->input->post('address'),$this->EE->input->post('address_2'),$this->EE->input->post('city'),$this->EE->input->post('region'),$this->EE->input->post('region_other'),$this->EE->input->post('country'),$this->EE->input->post('postal_code'),$this->EE->input->post('company'),$this->EE->input->post('phone'),$this->EE->input->post('company'),$this->EE->input->post('phone'));

			// process subscription update
			$member_id = $subscription['member_id'];

			$credit_card = array(
								'number' => $this->EE->input->post('cc_number'),
								'name' => $this->EE->input->post('cc_name'),
								'expiry_month' => $this->EE->input->post('cc_expiry_month'),
								'expiry_year' => $this->EE->input->post('cc_expiry_year'),
								'security_code' => $this->EE->input->post('cc_cvv2')
							);

			$response = $this->eedonations_class->UpdateCC($subscription['id'], $credit_card);

			if (!is_array($response) or isset($response['error'])) {
				$this->EE->session->set_flashdata('errors',$this->EE->lang->line('eedonations_order_form_error_processing') . ': ' . $response['error_text'] . ' (#' . $response['error'] . ')');
			}
			elseif ($response['response_code'] != '104') {
				$this->EE->session->set_flashdata('errors',$this->EE->lang->line('eedonations_order_form_error_processing') . ': ' . $response['response_text'] . '. ' . $response['reason'] . ' (#' . $response['response_code'] . ')');
			}
			else {
				// success!
				$this->EE->session->set_flashdata('message_success', 'You have successfully updated this subscription.');

				// redirect to URL
				$this->EE->functions->redirect(htmlspecialchars_decode($this->cp_url('subscription', array('id' => $response['recurring_id']))));
				die();
				return TRUE;
			}

			$this->EE->functions->redirect(htmlspecialchars_decode($this->cp_url('update_cc', array('id' => $subscription['id']))));
			die();
		}
		else {
			$this->EE->session->set_flashdata('errors',validation_errors());
			$this->EE->functions->redirect(htmlspecialchars_decode($this->cp_url('update_cc', array('id' => $subscription['id']))));
			die();
		}
	}

	function cancel_subscription () {
		$this->eedonations_class->CancelSubscription($this->EE->input->get('id'));

		$this->EE->session->set_flashdata('message_success', $this->EE->lang->line('eedonations_cancelled_subscription'));

		$this->EE->functions->redirect(htmlspecialchars_decode($this->cp_url('subscription',array('id' => $this->EE->input->get('id')))));
		die();

		return true;
	}

	//--------------------------------------------------------------------

	function create () {
		$this->EE->cp->set_variable('cp_page_title', $this->EE->lang->line('eedonations_create_donation'));

		// shall we pass this off?
		if (!$this->EE->input->post('member_search') and $this->EE->input->post('member_id')) {
			return $this->create_2();
		}

		// get users
		if ($this->EE->input->post('member_search')) {
			$searching = TRUE;

			$this->EE->load->model('member_model');
		    $members_db = $this->EE->member_model->get_members('','250','',$this->EE->input->post('member_search'),array('screen_name' => 'ASC'));

		    $members = array();

		    if (is_object($members_db) and $members_db->num_rows() > 0) {
			    foreach ($members_db->result_array() as $member) {
			    	$members[] = $member;
			    }
			}
	    }
	    else {
	    	$searching = FALSE;

	    	$members = array();
	    }

		$vars = array();
		$vars['searching'] = $searching;
		$vars['members'] = $members;
		$vars['form_action'] = $this->form_url('create');

		return $this->EE->load->view('create',$vars, TRUE);
	}

	//--------------------------------------------------------------------

	function create_2 () {
		// do we have the required info to be here?
		if ($this->EE->input->post('member_id') == '') {
			return $this->create();
		}

		$this->EE->cp->set_variable('cp_page_title', $this->EE->lang->line('eedonations_create_donation'));

		$this->EE->load->helper('form');
		$this->EE->load->library('form_validation');

		if ($this->EE->input->post('process_transaction') == '1') {
			// setup validation
			$this->EE->form_validation->set_rules('member_id','lang:eedonations_user','trim|required');
			$this->EE->form_validation->set_rules('first_name','lang:eedonations_order_form_customer_first_name','trim|required');
			$this->EE->form_validation->set_rules('last_name','lang:eedonations_order_form_customer_last_name','trim|required');
			$this->EE->form_validation->set_rules('address','lang:eedonations_order_form_customer_address','trim|required');
			$this->EE->form_validation->set_rules('city','lang:eedonations_order_form_customer_city','trim|required');
			$this->EE->form_validation->set_rules('country','lang:eedonations_order_form_customer_country','trim|required');
			$this->EE->form_validation->set_rules('postal_code','lang:eedonations_order_form_customer_postal_code','trim|required');

			if ($this->EE->form_validation->run() != FALSE) {
				// update address
				$this->eedonations_class->UpdateAddress($this->EE->input->post('member_id'),$this->EE->input->post('first_name'),$this->EE->input->post('last_name'),$this->EE->input->post('address'),$this->EE->input->post('address_2'),$this->EE->input->post('city'),$this->EE->input->post('region'),$this->EE->input->post('region_other'),$this->EE->input->post('country'),$this->EE->input->post('postal_code'),$this->EE->input->post('company'),$this->EE->input->post('phone'));

				// process subscription
				// prep arrays to send to EEDonations class
				$member_id = $this->EE->input->post('member_id');

				$credit_card = array(
									'number' => $this->EE->input->post('cc_number'),
									'name' => $this->EE->input->post('cc_name'),
									'expiry_month' => $this->EE->input->post('cc_expiry_month'),
									'expiry_year' => $this->EE->input->post('cc_expiry_year'),
									'security_code' => $this->EE->input->post('cc_cvv2')
								);

				$this->EE->load->model('member_model');
			    $member = $this->EE->member_model->get_member_data($this->EE->input->post('member_id'));
			    $member = $member->row_array();

				$customer = array(
								'first_name' => $this->EE->input->post('first_name'),
								'last_name' => $this->EE->input->post('last_name'),
								'address' => $this->EE->input->post('address'),
								'address_2' => $this->EE->input->post('address_2'),
								'city' => $this->EE->input->post('city'),
								'region' => ($this->EE->input->post('region_other') != '') ? $this->EE->input->post('region_other') : $this->EE->input->post('region'),
								'country' => $this->EE->input->post('country'),
								'postal_code' => $this->EE->input->post('postal_code'),
								'email' => $member['email'],
								'company' => $this->EE->input->post('company'),
								'phone' => $this->EE->input->post('phone')
							);

				// calculate amount
				$amount = ($this->EE->input->post('custom_amount')) ? $this->EE->input->post('custom_amount') : $this->EE->input->post('amount');

				// calculate interval
				$interval = ($this->EE->input->post('custom_interval')) ? $this->EE->input->post('custom_interval') : $this->EE->input->post('interval');

				// set a cookie, in case they go offsite and get redirected from there...
				$this->EE->functions->set_cookie('eedonations_redirect_url', htmlspecialchars_decode($this->cp_url('payments')), (60*60));

				// Get our gateway to use
				$gateway_id = ($this->EE->input->post('gateway')) ? $this->EE->input->post('gateway') : false;

				// send donation
				$response = $this->eedonations_class->Donate($amount, $interval, $member_id, $credit_card, $customer, false, $gateway_id);

				if (!is_array($response) or isset($response['error'])) {
					$failed_transaction = $this->EE->lang->line('eedonations_order_form_error_processing') . ': ' . $response['error_text'] . ' (#' . $response['error'] . ')';
				}
				elseif ($response['response_code'] == '2') {
					$failed_transaction = $this->EE->lang->line('eedonations_order_form_error_processing') . ': ' . $response['response_text'] . '. ' . $response['reason'] . ' (#' . $response['response_code'] . ')';
				}
				else {
					// success!
					$this->EE->session->set_flashdata('message_success', $this->EE->lang->line('eedonations_created_donation'));

					// redirect to URL
					if (isset($response['recurring_id'])) {
						$this->EE->functions->redirect(htmlspecialchars_decode($this->cp_url('subscription', array('id' => $response['recurring_id']))));
					}
					else {
						$this->EE->functions->redirect(htmlspecialchars_decode($this->cp_url('payments')));
					}
					die();
					return TRUE;
				}
			}
		}

		// get select user
		$this->EE->load->model('member_model');
	    $member = $this->EE->member_model->get_member_data($this->EE->input->post('member_id'));
	    $member = $member->row_array();

	    // end date
	    $end_date_days = array();
	    for ($i = 1; $i <= 31; $i++) {
        	$end_date_days[$i] = $i;
        }

        $end_date_months = array();
	    for ($i = 1; $i <= 12; $i++) {
        	$end_date_months[$i] = date('m - M',mktime(1, 1, 1, $i, 1, 2010));
        }

        $end_date_years = array();
	    for ($i = date('Y'); $i <= (date('Y') + 3); $i++) {
        	$end_date_years[$i] = $i;
        }

        // cc expiry date
        $expiry_date_years = array();

        for ($i = date('Y'); $i <= (date('Y') + 10); $i++) {
        	$expiry_date_years[$i] = $i;
        }

        // get address if available
        $address = $this->eedonations_class->GetAddress($member['member_id']);

        // get regions
        $regions = $this->eedonations_class->GetRegions();

		$region_options = array();
		$region_options[] = '';
		foreach ($regions as $code => $region) {
			$region_options[$code] = $region;
		}

        // get countries
        $countries = $this->eedonations_class->GetCountries();

		$country_options = array();
		$country_options[] = '';
		foreach ($countries as $country_code => $country) {
			$country_options[$country_code] = $country;
		}

		 // get gateways
		$this->server->SetMethod('GetGateways');
		$response = $this->server->Process();

		// we may get one gateway or many
		$gateways = isset($response['gateways']) ? $response['gateways'] : FALSE;

		// hold our list of available options
		$gateway_options = array();
		$gateway_options[''] = 'Default Gateway';

		if (is_array($gateways) and isset($gateways['gateway'][0])) {
			foreach ($gateways['gateway'] as $gateway) {
				$gateway_options[$gateway['id']] = $gateway['gateway'];
			}
		}
		elseif (is_array($gateways)) {
			$gateway = $gateways['gateway'];

			$gateway_options[$gateway['id']] = $gateway['gateway'];
		}

		$vars = array();
		$vars['config'] = $this->config;
		$vars['member'] = $member;
		$vars['end_date_days'] = $end_date_days;
		$vars['end_date_months'] = $end_date_months;
		$vars['end_date_years'] = $end_date_years;
		$vars['expiry_date_years'] = $expiry_date_years;
		$vars['form_action'] = $this->form_url('create_2');
		$vars['regions'] = $region_options;
		$vars['countries'] = $country_options;
		$vars['address'] = $address;
		$vars['failed_transaction'] = (isset($failed_transaction)) ? $failed_transaction : FALSE;
		$vars['gateways'] = $gateway_options;

		return $this->EE->load->view('create_2',$vars, TRUE);
	}

	function settings () {
		$this->EE->cp->set_variable('cp_page_title', $this->EE->lang->line('eedonations_settings'));

		$this->EE->load->helper('form');
		$this->EE->load->library('form_validation');

		// handle possible submission
		if ($_SERVER["REQUEST_METHOD"] == "POST") {
			$this->EE->form_validation->set_rules('api_url','lang:eedonations_api_url','trim|required');
			$this->EE->form_validation->set_rules('api_id','lang:eedonations_api_id','trim|required');
			$this->EE->form_validation->set_rules('secret_key','lang:eedonations_secret_key','trim|required');
			$this->EE->form_validation->set_rules('intervals','lang:eedonations_interval_options','trim|required');
			$this->EE->form_validation->set_rules('amounts','lang:eedonations_amounts','trim|required');
			$this->EE->form_validation->set_rules('minimum_amount','lang:eedonations_minimum_amount','trim|required');
			$this->EE->form_validation->set_rules('maximum_amount','lang:eedonations_maximum_amount','trim|required');

			if ($this->EE->form_validation->run() != FALSE) {
				$post_url = $this->EE->input->post('api_url');
				$post_id = $this->EE->input->post('api_id');
				$post_key = $this->EE->input->post('secret_key');
				$post_currency_symbol = $this->EE->input->post('currency_symbol');
				$post_gateway = $this->EE->input->post('gateway');
				$post_intervals = $this->EE->input->post('intervals');
				$post_amounts = $this->EE->input->post('amounts');
				$post_allow_custom_intervals = $this->EE->input->post('allow_custom_intervals');
				$post_allow_custom_amounts = $this->EE->input->post('allow_custom_amounts');
				$post_minimum_amount = $this->EE->input->post('minimum_amount');
				$post_minimum_amount = (float)$post_minimum_amount;
				$post_maximum_amount = $this->EE->input->post('maximum_amount');
				$post_maximum_amount = (float)$post_maximum_amount;

				$post_url = rtrim($post_url, '/');

				if (substr($post_url,3,-3) == 'api') {
					$post_url = substr_replace($post_url,'',-4,4);
				}

				$post_url = rtrim($post_url, '/');

				// validate API connection
				if (!$this->validate_api($post_url, $post_id, $post_key)) {
					$failed_to_connect = $this->EE->lang->line('eedonations_config_failed');
				}
				else {
					$is_first_config = (!$this->config) ? TRUE : FALSE;

					// create intervals array
					$intervals = array();
					$post_intervals = explode("\n", $post_intervals);

					foreach ($post_intervals as $line) {
						list($number,$name) = explode('|', $line);

						$intervals[$number] = $name;
					}

					$intervals = serialize($intervals);

					// create amounts array
					$amounts = array();
					$post_amounts = explode("\n", $post_amounts);

					foreach ($post_amounts as $line) {
						list($number,$name) = explode('|', $line);

						$amounts[$number] = $name;
					}

					$amounts = serialize($amounts);

					$update_vars = array(
							         'api_url' => $post_url,
							         'api_id' => $post_id,
							         'secret_key' => $post_key,
							         'currency_symbol' => $post_currency_symbol,
							         'gateway' => $post_gateway,
							         'intervals' => $intervals,
							         'amounts' => $amounts,
							         'allow_custom_intervals' => $post_allow_custom_intervals,
							         'allow_custom_amounts' => $post_allow_custom_amounts,
							         'minimum_amount' => $post_minimum_amount,
							         'maximum_amount' => $post_maximum_amount
									);

					if (!$is_first_config) {
						$this->EE->db->update('exp_eedonations_config',$update_vars);
				 	}
				 	else {
				 		$this->EE->db->insert('exp_eedonations_config', $update_vars);
				 	}

				 	$this->EE->session->set_flashdata('message_success', $this->EE->lang->line('eedonations_config_updated'));

				 	$this->EE->functions->redirect(htmlspecialchars_decode($this->cp_url('settings')));
				 	die();
				 }
			}
		}

		// is this the first config?
		$is_first_config = (!$this->config) ? TRUE : FALSE;

		// set default interval options
		$default_intervals = '0|No Recurrence
7|Weekly
30|Monthly
60|Bi-monthly
365|Annually';

		// ... and default amounts
		$default_amounts = '10|Bronze
50|Silver
100|Gold
250|Platinum';

		// process stored config intervals array
		if (!empty($this->config['intervals'])) {
			$intervals = '';

			foreach ($this->config['intervals'] as $key => $interval) {
				$intervals .= $key . '|' . $interval . "\n";
			}

			$intervals = trim($intervals);

			$this->config['intervals'] = $intervals;
		}

		// process stored config amounts array
		if (!empty($this->config['amounts'])) {
			$amounts = '';

			foreach ($this->config['amounts'] as $key => $amount) {
				$amounts .= $key . '|' . $amount . "\n";
			}

			$amounts = trim($amounts);

			$this->config['amounts'] = $amounts;
		}

		// get values
		if (!$is_first_config) {
			$api_url = ($this->EE->input->post('api_url')) ? $this->EE->input->post('api_url') : $this->config['api_url'];
			$api_id = ($this->EE->input->post('api_id')) ? $this->EE->input->post('api_id') : $this->config['api_id'];
			$secret_key = ($this->EE->input->post('secret_key')) ? $this->EE->input->post('secret_key') : $this->config['secret_key'];
			$currency_symbol = ($this->EE->input->post('currency_symbol')) ? $this->EE->input->post('currency_symbol') : $this->config['currency_symbol'];
			$default_gateway = ($this->EE->input->post('gateway')) ? $this->EE->input->post('gateway') : $this->config['gateway'];
			$intervals = ($this->EE->input->post('intervals')) ? $this->EE->input->post('intervals') : $this->config['intervals'];
			$amounts = ($this->EE->input->post('amounts')) ? $this->EE->input->post('amounts') : $this->config['amounts'];
			$allow_custom_amounts = (($this->EE->input->post('amounts') and !$this->EE->input->post('allow_custom_amounts')) or empty($this->config['allow_custom_amounts'])) ? FALSE : TRUE;
			$allow_custom_intervals = (($this->EE->input->post('amounts') and !$this->EE->input->post('allow_custom_amounts')) or empty($this->config['allow_custom_intervals'])) ? FALSE : TRUE;
			$maximum_amount = ($this->EE->input->post('maximum_amount')) ? $this->EE->input->post('maximum_amount') : $this->config['maximum_amount'];
			$minimum_amount = ($this->EE->input->post('minimum_amount')) ? $this->EE->input->post('minimum_amount') : $this->config['minimum_amount'];
		}
		else {
			$api_url = ($this->EE->input->post('api_url')) ? $this->EE->input->post('api_url') : 'https://www.yourdomain.com/opengateway';
			$api_id = ($this->EE->input->post('api_id')) ? $this->EE->input->post('api_id') : '';
			$secret_key = ($this->EE->input->post('secret_key')) ? $this->EE->input->post('secret_key') : '';
			$currency_symbol = ($this->EE->input->post('currency_symbol')) ? $this->EE->input->post('currency_symbol') : '$';
			$default_gateway = ($this->EE->input->post('gateway')) ? $this->EE->input->post('gateway') : '';
			$intervals = ($this->EE->input->post('intervals')) ? $this->EE->input->post('intervals') : $default_intervals;
			$amounts = ($this->EE->input->post('amounts')) ? $this->EE->input->post('amounts') : $default_amounts;
			$allow_custom_amounts = ($this->EE->input->post('amounts') and !$this->EE->input->post('allow_custom_amounts')) ? FALSE : TRUE;
			$allow_custom_intervals = ($this->EE->input->post('amounts') and !$this->EE->input->post('allow_custom_amounts')) ? FALSE : TRUE;
			$maximum_amount = ($this->EE->input->post('maximum_amount')) ? $this->EE->input->post('maximum_amount') : '500.00';
			$minimum_amount = ($this->EE->input->post('minimum_amount')) ? $this->EE->input->post('minimum_amount') : '5.00';
		}

		// load possible gateways
		if (!$is_first_config) {
   			$this->server->SetMethod('GetGateways');
			$response = $this->server->Process();

			// we may get one gateway or many
			$gateways = isset($response['gateways']) ? $response['gateways'] : FALSE;

			// hold our list of available options
			$gateway_options = array();
			$gateway_options[''] = 'Default Gateway';

			if (is_array($gateways) and isset($gateways['gateway'][0])) {
				foreach ($gateways['gateway'] as $gateway) {
					$gateway_options[$gateway['id']] = $gateway['gateway'];
				}
			}
			elseif (is_array($gateways)) {
				$gateway = $gateways['gateway'];

				$gateway_options[$gateway['id']] = $gateway['gateway'];
			}
		}

		// countries
		$countries = $this->EE->db->where('available','1')->get('exp_eedonations_countries')->num_rows();
		$countries_text = '<a href="' . $this->cp_url('countries') . '">' . $countries . ' countries</a>';

		// load view
		$vars = array();
		$vars['form_action'] = $this->form_url('settings');
		$vars['first_config'] = ($is_first_config == true) ? true : false;
		$vars['api_url'] = $api_url;
		$vars['api_id'] = $api_id;
		$vars['secret_key'] = $secret_key;
		$vars['currency_symbol'] = $currency_symbol;
		$vars['intervals'] = $intervals;
		$vars['amounts'] = $amounts;
		$vars['allow_custom_intervals'] = $allow_custom_intervals;
		$vars['allow_custom_amounts'] = $allow_custom_amounts;
		$vars['maximum_amount'] = $maximum_amount;
		$vars['minimum_amount'] = $minimum_amount;
		$vars['gateway'] = $default_gateway;
		$vars['gateways'] = (isset($gateway_options)) ? $gateway_options : FALSE;
		$vars['countries_text'] = $countries_text;
		$vars['failed_to_connect'] = isset($failed_to_connect) ? $failed_to_connect : FALSE;

		return $this->EE->load->view('settings',$vars,TRUE);
	}

	function countries () {
		$this->EE->cp->set_variable('cp_page_title', $this->EE->lang->line('eedonations_available_countries'));

		 $this->EE->cp->add_to_head('<script type="text/javascript">
        								function uncheck_countries () {
        									$(\'input.countries\').attr(\'checked\',false);
        								}

        								function check_countries () {
        									$(\'input.countries\').attr(\'checked\',\'checked\');
        								}
        							</script>');

		$countries = $this->EE->db->order_by('name')->get('exp_eedonations_countries');

		$vars = array();
		$vars['countries'] = $countries;
		$vars['form_action'] = $this->form_url('set_countries');

		return $this->EE->load->view('countries', $vars, TRUE);
	}

	function set_countries () {
		$countries = $this->EE->db->get('exp_eedonations_countries');

		foreach ($countries->result_array() as $country) {
			if ($country['available'] == '1' and !isset($_POST['country_' . $country['country_id']])) {
				$this->EE->db->update('exp_eedonations_countries', array('available' => '0'), array('country_id' => $country['country_id']));
			}
			elseif ($country['available'] == '0' and isset($_POST['country_' . $country['country_id']]) and $_POST['country_' . $country['country_id']] == '1') {
				$this->EE->db->update('exp_eedonations_countries', array('available' => '1'), array('country_id' => $country['country_id']));
			}
		}

		return $this->EE->functions->redirect(htmlspecialchars_decode($this->cp_url('settings')));
	}

	function validate_api ($api_url, $api_id, $secret_key) {
		// does URL exist?
		$headers = @get_headers($api_url);
		$file = @file_get_contents($api_url);
		if ((ini_get('allow_url_fopen') and $file == FALSE) or (!empty($headers) and (!isset($headers[0]) or strstr($headers[0],'404') or strstr($headers[0],'403')))) {
			return FALSE;
		}
		else {
			include_once(dirname(__FILE__) . '/opengateway.php');
			$server = new OpenGateway;

			$server->Authenticate($api_id, $secret_key, $api_url . '/api');
			$server->SetMethod('GetCharges');
			$response = $server->Process();

			if (!isset($response['error'])) {
				return TRUE;
			}
			else {
				return FALSE;
			}
		}

		return FALSE;
	}

	function pagination_config($method, $total_rows, $parameters = array())
	{
		// Pass the relevant data to the paginate class
		$config['base_url'] = $this->cp_url($method, $parameters);
		$config['total_rows'] = $total_rows;
		$config['per_page'] = $this->per_page;
		$config['page_query_string'] = TRUE;
		$config['query_string_segment'] = 'rownum';
		$config['full_tag_open'] = '<p id="paginationLinks">';
		$config['full_tag_close'] = '</p>';
		$config['prev_link'] = '<img src="'.$this->EE->cp->cp_theme_url.'images/pagination_prev_button.gif" width="13" height="13" alt="<" />';
		$config['next_link'] = '<img src="'.$this->EE->cp->cp_theme_url.'images/pagination_next_button.gif" width="13" height="13" alt=">" />';
		$config['first_link'] = '<img src="'.$this->EE->cp->cp_theme_url.'images/pagination_first_button.gif" width="13" height="13" alt="< <" />';
		$config['last_link'] = '<img src="'.$this->EE->cp->cp_theme_url.'images/pagination_last_button.gif" width="13" height="13" alt="> >" />';

		return $config;
	}
}

if (!function_exists('htmlspecialchars_decode')) {
	function htmlspecialchars_decode($string,$style = ENT_COMPAT)
	{
	    $translation = array_flip(get_html_translation_table(HTML_SPECIALCHARS,$style));
	    if ($style === ENT_QUOTES){ $translation['&#039;'] = '\''; }
	    return strtr($string,$translation);
	}
}