<p>Adding <b>Custom Fields</b> to your <i>EE Donations</i> donation forms is easy.</p>
<ol class="eedonations">
	<li>Use the <a href="http://www.eedonations.com/docs/template_tags" target="_blank">normal {exp:eedonations:donate_form}</a> tags
	to build your donation form with the standard fields.</li>
	<li>In your templates, add each custom field with a field name prefaced by "custom_field_".  Examples:
		<ol style="margin-left: 45px">
			<li><span class="html">&lt;input type="text" name="custom_field_cause_to_support" value="" /&gt;</span></li>
			<li><span class="html">&lt;input type="radio" name="custom_field_keep_anonymous" value="yes" /&gt;<br />&lt;input type="radio" name="custom_field_keep_anonymous" value="no" /&gt;</span></li>
			<li><span class="html">&lt;select name="custom_field_food_drive"&gt;<br />
			&nbsp;&nbsp;&nbsp;&nbsp;&lt;option value="christmas"&gt;Christmas&lt;/option&gt;<br />
			&nbsp;&nbsp;&nbsp;&nbsp;&lt;option value="easter"&gt;&lt;Easter&lt;/option&gt;<br />
			&lt;/select&gt;</span></li>
		</ol>
	</li>
	<li>
		Each submitted form value will be saved automatically.  The field name will be created automatically from the passed field name.  For example, from above:
		"Cause To Support", "Keep Anonymous", and "Food Drive".
	</li>
	<li>
		You can view each custom field value in the <i>Donations</i> and <i>Subscriptions</i> areas of this EE Donations control panel, associated with
		each donation.
	</li>
</ol>
	