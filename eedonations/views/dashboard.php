<div class="eedonations_box">
	<?=$this->lang->line('eedonations_dashboard_intro');?>
</div>
<br />
<h4><?=$this->lang->line('eedonations_latest_payments');?></h4>
<br />
<?php

$this->table->set_template($cp_pad_table_template); // $cp_table_template ?

$this->table->set_heading(
    array('data' => lang('eedonations_id'), 'style' => 'width: 10%;'),
    array('data' => lang('eedonations_user'), 'style' => 'width: 20%;'),
    array('data' => lang('eedonations_subscription'), 'style' => 'width: 10%;'),
    array('data' => lang('eedonations_date'), 'style' => 'width: 20%;'),
    array('data' => lang('eedonations_amount'), 'style' => 'width: 15%;'),
    array('data' => '', 'style' => 'width: 10%;')
);

if (!$payments) {
	$this->table->add_row(array(
							'data' => lang('eedonations_no_payments_dataset'),
							'colspan' => '7'
						));
}
else {
	foreach ($payments as $payment) {
		$this->table->add_row($payment['id'],
						(strpos($payment['member_id'], 'anon') !== FALSE) ? $payment['first_name'] . ' ' . $payment['last_name'] : '<a href="' . $payment['member_link'] . '">' . $payment['user_screenname'] . '</a>',
						(!empty($payment['sub_link'])) ? $payment['sub_link'] : '',
						$payment['date'],
						$config['currency_symbol'] . $payment['amount'],
						'<a href="' . $payment['view_link'] . '">view</a>' . $payment['refund_text']
					);
	}
}

?>

<?=$this->table->generate();?>
<?=$this->table->clear();?>

<? if (!empty($months)) { ?>

<br />
<h4><?=$this->lang->line('eedonations_month_by_month');?></h4>
<br />

<?php

$this->table->set_template($cp_pad_table_template); // $cp_table_template ?

$this->table->set_heading(
    array('data' => lang('eedonations_current_month'), 'style' => 'width: 20%;'),
    array('data' => lang('eedonations_current_revenue'), 'style' => 'width: 20%;')
);

foreach ($months as $month) {

	$this->table->add_row(
				$month['month'],
				$month['revenue']
			);
			
}

?>

<?=$this->table->generate();?>
<?=$this->table->clear();?>

<? } ?>