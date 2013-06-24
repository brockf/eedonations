<?php ?>
<form method="get" action="<?=basename($_SERVER['SCRIPT_NAME']);?>">
<label><?=lang('eedonations_search_all_subscriptions');?></label>
<?=form_input('search', $search_query);?><br /><input type="submit" name="go" value="Search" />&nbsp;<a href="<?=$cp_url;?>">View All</a>
<? foreach ($search_fields as $field => $value) { ?>
<input type="hidden" name="<?=$field;?>" value="<?=$value;?>" />
<? } ?>
</form>
<br />
<?php

$this->table->set_template($cp_pad_table_template); // $cp_table_template ?

$this->table->set_heading(
    array('data' => lang('eedonations_id'), 'style' => 'width: 7%;'),
    array('data' => lang('eedonations_user'), 'style' => 'width: 20%;'),
    array('data' => lang('eedonations_recurrence'), 'style' => 'width: 15%;'),
    array('data' => lang('eedonations_amount'), 'style' => 'width: 10%;'),
    array('data' => lang('eedonations_next_charge_date'), 'style' => 'width: 17%;'),
    array('data' => lang('eedonations_status'), 'style' => 'width: 10%;'),
    array('data' => '', 'style' => 'width:26%')
);

if (!$subscriptions) {
	$this->table->add_row(array(
							'data' => lang('eedonations_no_subscriptions_dataset'),
							'colspan' => '7'
						));
}
else {
	foreach ($subscriptions as $subscription) {
		if ($subscription['active'] == '1') {
			$status = $this->lang->line('eedonations_active');
		}	
		elseif ($subscription['expired'] == '1') {
			$status = $this->lang->line('eedonations_expired');
		}
		elseif ($subscription['renewed'] == TRUE) {
			$status = $this->lang->line('eedonations_renewed');
		}
		elseif ($subscription['cancelled'] == '1') {
			$status = $this->lang->line('eedonations_cancelled');
		}
		else {
			$status = '';
		}
		
		$this->table->add_row($subscription['id'],
						(strpos($subscription['member_id'], 'anon') !== FALSE) ? $subscription['first_name'] . ' ' . $subscription['last_name'] : '<a href="' . $subscription['member_link'] . '">' . $subscription['user_screenname'] . '</a>',
						$subscription['interval'] . ' days',
						$config['currency_symbol'] . $subscription['amount'],
						$subscription['next_charge_date'],
						$status,
						$subscription['options']
					);
	}
}

?>

<?=$this->table->generate();?>
<?=$this->table->clear();?>
<?=$pagination;?>