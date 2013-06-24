<?php

$this->table->set_template($cp_pad_table_template); // $cp_table_template ?
?>
<p style="text-align: right;"><a href="<?= $export_link ?>">Export CSV</a></p>
<?php
$this->table->set_heading(
    array('data' => lang('eedonations_id'), 'style' => 'width: 10%;'),
    array('data' => lang('eedonations_user'), 'style' => 'width: 20%;'),
    array('data' => lang('eedonations_subscription'), 'style' => 'width: 15%;'),
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
<?=$pagination;?>