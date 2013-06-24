<? if (validation_errors()) { ?>
	<div class="eedonations_error"><?=validation_errors();?></div>
<? } ?>
<? if ($failed_transaction) { ?>
	<div class="eedonations_error"><?=$failed_transaction;?></div>
<? } ?>

<?=form_open($form_action)?>

<?php

$this->table->set_template($cp_table_template);
$this->table->set_heading(
					array('data' => lang('eedonations_create_donation'), 'colspan' => '2')
						);

$this->table->add_row(
		lang('eedonations_user'),
		$member['screen_name'] . ' (' . $member['email'] . ')' . form_hidden('member_id',$member['member_id']) . form_hidden('process_transaction','1')
	);

$this->table->add_row(
		lang('eedonations_recurrence'),
		form_dropdown('interval',$config['intervals']) . ' or enter # of days: ' . form_input(array('name' => 'custom_interval', 'style' => 'width: 60px'))
	);
	
$this->table->add_row(
		lang('eedonations_amount'),
		form_dropdown('amount',$config['amounts']) . ' or enter amount: ' . $config['currency_symbol'] . form_input(array('name' => 'custom_amount', 'style' => 'width: 60px'))
	);	
	
$this->table->add_row(
		lang('eedonations_gateway'),
		form_dropdown('gateway', $gateways, '')
	);	
	
$this->table->add_row(
		array('data' => '<b>' . lang('eedonations_donate_form_credit_card') . '</b>', 'colspan' => '2')
	);
	
$this->table->add_row(
		lang('eedonations_donate_form_cc_number'),
		form_input(array('name' => 'cc_number', 'style' => 'width: 170px'))
	);

$this->table->add_row(
		lang('eedonations_donate_form_cc_name'),
		form_input(array('name' => 'cc_name', 'style' => 'width: 170px'))
	);
	
$this->table->add_row(
		lang('eedonations_donate_form_cc_cvv2'),
		form_input(array('name' => 'cc_cvv2', 'style' => 'width: 50px'))
	);
	
$this->table->add_row(
		lang('eedonations_donate_form_cc_expiry'),
		form_dropdown('cc_expiry_month',$end_date_months) . '&nbsp;&nbsp;' . form_dropdown('cc_expiry_year',$expiry_date_years)
	);
	
$this->table->add_row(
		array('data' => '<b>' . lang('eedonations_donate_form_customer_info') . '</b>', 'colspan' => '2')
	);
	
$this->table->add_row(
		lang('eedonations_donate_form_customer_first_name'),
		form_input(array('name' => 'first_name', 'value' => $address['first_name'], 'style' => 'width: 250px'))
	);
	
$this->table->add_row(
		lang('eedonations_donate_form_customer_last_name'),
		form_input(array('name' => 'last_name', 'value' => $address['last_name'], 'style' => 'width: 250px'))
	);
	
$this->table->add_row(
		lang('eedonations_donate_form_customer_company'),
		form_input(array('name' => 'company', 'value' => $address['company'], 'style' => 'width: 250px'))
	);
	
$this->table->add_row(
		lang('eedonations_donate_form_customer_address'),
		form_input(array('name' => 'address', 'value' => $address['address'], 'style' => 'width: 250px'))
	);
	
$this->table->add_row(
		lang('eedonations_donate_form_customer_address_2'),
		form_input(array('name' => 'address_2', 'value' => $address['address_2'], 'style' => 'width: 250px'))
	);
	
$this->table->add_row(
		lang('eedonations_donate_form_customer_city'),
		form_input(array('name' => 'city', 'value' => $address['city'], 'style' => 'width: 250px'))
	);
	
$this->table->add_row(
		lang('eedonations_donate_form_customer_region'),
		form_dropdown('region', $regions, $address['region']) . '&nbsp;&nbsp;' . form_input(array('name' => 'region_other', 'value' => $address['region_other'], 'style' => 'width: 250px'))
	);
	
$this->table->add_row(
		lang('eedonations_donate_form_customer_country'),
		form_dropdown('country', $countries, $address['country'])
	);
	
$this->table->add_row(
		lang('eedonations_donate_form_customer_postal_code'),
		form_input(array('name' => 'postal_code', 'value' => $address['postal_code'], 'style' => 'width: 250px'))
	);
	
$this->table->add_row(
		lang('eedonations_donate_form_customer_phone'),
		form_input(array('name' => 'phone', 'value' => $address['phone'], 'style' => 'width: 250px'))
	);
	
$this->table->add_row(
		'',
		form_submit('submit_form', $this->lang->line('eedonations_process'))
	);
		
?>
<?=$this->table->generate();?>
<?=form_close();?>