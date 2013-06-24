<?php
	
$this->table->set_template($cp_table_template);
$this->table->set_heading(
					array('data' => lang('eedonations_subscription'), 'colspan' => '2')
						);

$this->table->add_row(
		array('data' => lang('eedonations_id'), 'style' => 'width:30%'),
		$subscription['id']
	);
	
$this->table->add_row(
		array('data' => lang('eedonations_status'), 'style' => 'width:30%'),
		$subscription['status']
	);
	
$this->table->add_row(
		array('data' => lang('eedonations_user'), 'style' => 'width:30%'),
		(strpos($subscription['member_id'], 'anon') !== FALSE) ? $subscription['first_name'] . ' ' . $subscription['last_name'] : '<a href="' . $subscription['member_link'] . '">' . $subscription['user_screenname'] . '</a>'
	);
	
$this->table->add_row(
		array('data' => lang('eedonations_recurrence'), 'style' => 'width:30%'),
		$subscription['interval'] . ' days'
	);
	
$this->table->add_row(
		array('data' => lang('eedonations_recurring_amount'), 'style' => 'width:30%'),
		$config['currency_symbol'] . $subscription['amount']
	);
	
$this->table->add_row(
		array('data' => lang('eedonations_total_amount'), 'style' => 'width:30%'),
		$config['currency_symbol'] . money_format("%!^i",$subscription['total_amount'])
	);
	
$this->table->add_row(
		array('data' => lang('eedonations_start_date'), 'style' => 'width:30%'),
		$subscription['date_created']
	);
	
if ($subscription['active'] == '1') {
	if ($subscription['next_charge_date'] != FALSE) {
		$this->table->add_row(
			array('data' => lang('eedonations_next_charge_date'), 'style' => 'width:30%'),
			$subscription['next_charge_date']
		);
	}
	if ($subscription['end_date'] != FALSE) {
		$this->table->add_row(
			array('data' => lang('eedonations_date_ending'), 'style' => 'width:30%'),
			$subscription['end_date'] . $change_expiry
		);
	}
}
else {
	$this->table->add_row(
			array('data' => lang('eedonations_date_cancelled'), 'style' => 'width:30%'),
			$subscription['date_cancelled']
		);
	$this->table->add_row(
			array('data' => lang('eedonations_date_ending'), 'style' => 'width:30%'),
			$subscription['end_date']
		);
}

if (!empty($custom_fields)) {
	$this->table->add_row(
			array('data' => '<b>Custom Fields</b>', 'colspan' => '2')
		);
		
	foreach ($custom_fields as $name => $value) {
		$this->table->add_row(
			$name,
			$value
		);
	}
}

$this->table->add_row(
		array('data' => '<b>' . lang('eedonations_payments') . '</b>', 'colspan' => '2', 'style' => 'width:30%')
	);
	
if (empty($payments)) {
	$this->table->add_row(
		array('data' => lang('eedonations_no_payments'), 'colspan' => '2', 'style' => 'width:30%')
	);
}
else {
	foreach ($payments as $payment) {
		$payment['refund_text'] = (empty($payment['refund_text'])) ? '' : ' (' . $payment['refund_text'] . ')';
		
		$this->table->add_row(
				array('data' => '<a href="' . $payment['link'] . '">' . $config['currency_symbol'] . $payment['amount'] . ' ' . lang('eedonations_received_on') . ' ' . $payment['date'] . '</a> ' . $payment['refund_text'], 'colspan' => '2', 'style' => 'width:30%')
			);
	}	
}
		
?>

<?=$this->table->generate();?>