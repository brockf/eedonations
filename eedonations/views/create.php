<?=form_open($form_action)?>

<?php

$this->table->set_template($cp_table_template);
$this->table->set_heading(
					array('data' => lang('eedonations_create_donation'), 'colspan' => '2')
						);

$this->table->add_row(
		lang('eedonations_user'),
		form_input(array('name' => 'member_search', 'style' => 'width: 200px; margin-bottom: 5px;')) . '&nbsp;&nbsp;' . form_submit('submit_form', $this->lang->line('eedonations_search_for_member'))
		. '<br />' . $this->lang->line('eedonations_search_by')
	);
	
if ($searching === TRUE) {
	if (empty($members)) {
		$this->table->add_row(
			'',
			$this->lang->line('eedonations_no_members')
		);
	}
	else {
		foreach ($members as $member) {
			$this->table->add_row(
				'',
				form_radio(array('name' => 'member_id', 'value' => $member['member_id'])) . '&nbsp;' . $member['screen_name'] . ' (' . $member['email'] . ')'
			);
		}
		
		$this->table->add_row(
			'',
			form_submit(array('name' => 'form_submit', 'value' => $this->lang->line('eedonations_continue_creating_donation')))
		);
	}
}
		
?>
<?=$this->table->generate();?>
<?=form_close();?>