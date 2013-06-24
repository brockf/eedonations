<?php
	
$this->table->set_template($cp_table_template);
$this->table->set_heading(
					array('data' => 'Donation Details', 'colspan' => '2')
						);

$this->table->add_row(
		array('data' => 'Donation ID', 'style' => 'width:30%'),
		$payment['id']
	);
	
$this->table->add_row(
		array('data' => 'Donor', 'style' => 'width:30%'),
		(strpos($payment['member_id'], 'anon') !== FALSE) ? $payment['first_name'] . ' ' . $payment['last_name'] : '<a href="' . $payment['member_link'] . '">' . $payment['user_screenname'] . '</a>'
	);	

$this->table->add_row(
			array('data' => 'Amount'),
			$config['currency_symbol'] . $payment['amount']
		);
		
$this->table->add_row(
			array('data' => 'Refund', 'style' => 'width:30%'),
			$payment['refund_text']
		);

if (!empty($subscription)) {		
	$this->table->add_row(
			array('data' => 'Linked to Subscription?'),
			'<a href="' . $subscription['link'] . '">' . $subscription['id'] . '</a>'
		);

	$this->table->add_row(
			array('data' => 'Subscription Status', 'style' => 'width:30%'),
			$subscription['status']
		);
		
	$this->table->add_row(
			array('data' => 'Subscription Start Date', 'style' => 'width:30%'),
			$subscription['date_created']
		);
		
	if ($subscription['active'] == '1') {
		if ($subscription['next_charge_date'] != FALSE) {
			$this->table->add_row(
				array('data' => 'Next Charge Date', 'style' => 'width:30%'),
				$subscription['next_charge_date']
			);
		}
		if ($subscription['end_date'] != FALSE) {
			$this->table->add_row(
				array('data' => 'End Date', 'style' => 'width:30%'),
				$subscription['end_date'] . $change_expiry
			);
		}
	}
	else {
		$this->table->add_row(
				array('data' => 'Cancellation Date', 'style' => 'width:30%'),
				$subscription['date_cancelled']
			);
		$this->table->add_row(
				array('data' => 'End Date', 'style' => 'width:30%'),
				$subscription['end_date']
			);
	}
}
else {
	$this->table->add_row(
			array('data' => 'Linked to Subscription?'),
			'No'
		);
}

if (!empty($payment['first_name']) and !empty($payment['last_name'])) {
	$this->table->add_row(
			array('Billing Name',$payment['first_name'] . ' ' . $payment['last_name'])
		);
}

if (!empty($payment['company'])) {
	$this->table->add_row(
			array('Billing Company',$payment['company'])
		);
}

if (!empty($payment['city'])) {
	$this->table->add_row(
			array('Billing City',$payment['city'])
		);
}

if (!empty($payment['region'])) {
	if (!empty($payment['region_other'])) {
		$region = $payment['region_other'] . '<br />';
	}
	else {
		$region = $payment['region'] . '<br />';
	}
	
	$this->table->add_row(
			array('Billing Region',$region)
		);
}

if (!empty($payment['country'])) {
	$this->table->add_row(
			array('Billing Country',$payment['country'])
		);
}

if (!empty($payment['postal_code'])) {
	$this->table->add_row(
			array('Billing Postal Code',$payment['postal_code'])
		);
}

if (!empty($payment['phone'])) {
	$this->table->add_row(
			array('Billing Phone',$payment['phone'])
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
	
			
?>

<?=$this->table->generate();?>