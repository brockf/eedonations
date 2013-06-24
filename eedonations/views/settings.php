<? if ($first_config == TRUE) { ?>
<div class="eedonations_box"><b>Welcome to EE Donations for Expression Engine!</b><br /><br />
Your plugin has not yet been configured.  Enter your API configuration details below so that
the EE Donations plugin can communicate with your billing server.  This integration will:
<ul class="eedonations">
	<li>Process subscription and one-time donation payments via the OpenGateway billing engine.</li>
	<li>Sync up the billing server with your EE Donations installation.</li>
</ul>
</div>
<? } ?>

<? if (validation_errors()) { ?>
	<div class="eedonations_error"><?=validation_errors();?></div>
<? } ?>
<? if ($failed_to_connect) { ?>
	<div class="eedonations_error"><?=$failed_to_connect;?></div>
<? } ?>

<?=form_open($form_action)?>

<?php

$this->table->set_template($cp_table_template);
$this->table->set_heading(
					array('data' => lang('eedonations_settings'), 'colspan' => '2')
						);

$this->table->add_row(
		lang('eedonations_api_url'),
		form_input(array('name' => 'api_url', 'value' => $api_url, 'style' => 'width: 375px'))
	);

$this->table->add_row(
		lang('eedonations_api_id'),
		form_input(array('name' => 'api_id', 'value' => $api_id, 'style' => 'width: 375px'))
	);
	
$this->table->add_row(
		lang('eedonations_secret_key'),
		form_input(array('name' => 'secret_key', 'value' => $secret_key, 'style' => 'width: 375px'))
	);
	
$this->table->add_row(
		lang('eedonations_currency_symbol'),
		form_input(array('name' => 'currency_symbol', 'value' => $currency_symbol, 'style' => 'width: 50px'))
	);
	
$this->table->add_row(
		lang('eedonations_interval_options'),
		form_textarea(array('name' => 'intervals', 'value' => $intervals, 'style' => 'width: 300px; height: 80px'))
	);	
	
$this->table->add_row(
		lang('eedonations_allow_custom_intervals'),
		form_checkbox('allow_custom_intervals', '1', $allow_custom_intervals)
	);			
	
$this->table->add_row(
		lang('eedonations_amount_options'),
		form_textarea(array('name' => 'amounts', 'value' => $amounts, 'style' => 'width: 300px; height: 80px'))
	);	
	
$this->table->add_row(
		lang('eedonations_allow_custom_amounts'),
		form_checkbox('allow_custom_amounts', '1', $allow_custom_amounts)
	);		
	
$this->table->add_row(
		lang('eedonations_minimum_amount'),
		form_input(array('name' => 'minimum_amount', 'value' => $minimum_amount, 'style' => 'width: 80px'))
	);	
	
$this->table->add_row(
		lang('eedonations_maximum_amount'),
		form_input(array('name' => 'maximum_amount', 'value' => $maximum_amount, 'style' => 'width: 80px'))
	);	
	
if ($gateways) {
	$this->table->add_row(
		lang('eedonations_default_gateway'),
		form_dropdown('gateway',$gateways,$gateway)
	);
}

$this->table->add_row(
		lang('eedonations_available_countries'),
		$countries_text
	);

$this->table->add_row(
		'',
		form_submit('submit_form', $this->lang->line('eedonations_save_configuration'))
	);
		
?>
<?=$this->table->generate();?>
<?=form_close();?>