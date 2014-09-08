## Custom Fields

**EE Donations** supports custom fields in your donation forms without any extra setup or customization.

All you need to do is add your custom fields to your donation form created with the [{exp:eedonations:donate_form}](/docs/template_tags.md)
form tag.

For example, you may add fields like "Food Drive" and "Keep my donation anonymous".  Each field is prefaced with "custom_field_", as in `custom_field_food_drive`.

Note: To retrieve custom field data in a template, use the `{exp:eedonations:custom_fields}` tag documented [in the Template Tags documentation](/docs/template_tags.md)

See the form below for an example of custom fields in an order form:

<pre class="example">
{exp:eedonations:donate_form redirect_url=&quot;/&quot;}
{if errors}
	&lt;div class=&quot;errors&quot;&gt;
	{errors}
	&lt;/div&gt;
{/if}
&lt;form method=&quot;{form_method}&quot; action=&quot;{form_action}&quot;&gt;
&lt;input type=&quot;hidden&quot; name=&quot;eedonations_donate_form&quot; value=&quot;1&quot; /&gt;
&lt;!-- This credit card fieldset is not required for external checkout (e.g., PayPal Express Checkout) payment methods. --&gt;
&lt;fieldset&gt;
	&lt;legend&gt;Donation&lt;/legend&gt;
	&lt;ul&gt;
		&lt;li&gt;
			&lt;label&gt;Amount&lt;/label&gt;
			&lt;select name=&quot;amount&quot;&gt;{amount_options}&lt;/select&gt;&amp;nbsp;&amp;nbsp;or enter amount $&lt;input type=&quot;text&quot; name=&quot;custom_amount&quot; value=&quot;&quot; /&gt;
		&lt;/li&gt;
		&lt;li&gt;
			&lt;label&gt;Recurrence&lt;/label&gt;
			&lt;select name=&quot;interval&quot;&gt;{interval_options}&lt;/select&gt;&amp;nbsp;&amp;nbsp;or enter number of days between recurring charges: &lt;input type=&quot;text&quot; name=&quot;custom_interval&quot; value=&quot;&quot; /&gt;
		&lt;/li&gt;
		&lt;li&gt;
			&lt;label&gt;Food Drive&lt;/label&gt;
			&lt;select name=&quot;custom_field_food_drive&quot;&gt;
				&lt;option value="Christmas"&gt;Christmas&lt;/option&gt;
				&lt;option value="Easter"&gt;Easter&lt;/option&gt;
			&lt;/select&gt;
		&lt;/li&gt;
		&lt;li&gt;
			&lt;label&gt;Keep Donation Anonymous&lt;/label&gt;
			&lt;input type="checkbox" name="custom_field_keep_donation_anonymous" value="Yes" &lt;/input&gt;
		&lt;/li&gt;
	&lt;/ul&gt;
&lt;/fieldset&gt;
&lt;fieldset&gt;
	&lt;legend&gt;Billing Information&lt;/legend&gt;
	&lt;ul&gt;
		&lt;li&gt;
			&lt;label&gt;Credit Card Number&lt;/label&gt;
			&lt;input type=&quot;text&quot; name=&quot;cc_number&quot; value=&quot;&quot; /&gt;
		&lt;/li&gt;
		&lt;li&gt;
			&lt;label&gt;Credit Card Name&lt;/label&gt;
			&lt;input type=&quot;text&quot; name=&quot;cc_name&quot; value=&quot;&quot; /&gt;
		&lt;/li&gt;
		&lt;li&gt;
			&lt;label&gt;Expiry Date&lt;/label&gt;
			&lt;select name=&quot;cc_expiry_month&quot;&gt;{cc_expiry_month_options}&lt;/select&gt;&amp;nbsp;&amp;nbsp;&lt;select name=&quot;cc_expiry_year&quot;&gt;{cc_expiry_year_options}&lt;/select&gt;
		&lt;/li&gt;
		&lt;li&gt;
			&lt;label&gt;Security Code&lt;/label&gt;
			&lt;input type=&quot;text&quot; name=&quot;cc_cvv2&quot; value=&quot;&quot; /&gt;
		&lt;/li&gt;
	&lt;/ul&gt;
&lt;/fieldset&gt;
&lt;fieldset&gt;
	&lt;legend&gt;Billing Address&lt;/legend&gt;
	&lt;ul&gt;
		&lt;li&gt;
			&lt;label for=&quot;first_name&quot;&gt;First Name&lt;/label&gt;
			&lt;input type=&quot;text&quot; id=&quot;first_name&quot; name=&quot;first_name&quot; maxlength=&quot;100&quot; value=&quot;{first_name}&quot; /&gt;
		&lt;/li&gt;
		&lt;li&gt;
			&lt;label for=&quot;last_name&quot;&gt;Last Name&lt;/label&gt;
			&lt;input type=&quot;text&quot; id=&quot;last_name&quot; name=&quot;last_name&quot; maxlength=&quot;100&quot; value=&quot;{last_name}&quot; /&gt;
		&lt;/li&gt;
		&lt;li&gt;
			&lt;label for=&quot;address&quot;&gt;Street Address&lt;/label&gt;
			&lt;input type=&quot;text&quot; id=&quot;address&quot; name=&quot;address&quot; maxlength=&quot;100&quot; value=&quot;{address}&quot; /&gt;
		&lt;/li&gt;
		&lt;li&gt;
			&lt;label for=&quot;address_2&quot;&gt;Address Line 2&lt;/label&gt;
			&lt;input type=&quot;text&quot; id=&quot;address_2&quot; name=&quot;address_2&quot; maxlength=&quot;100&quot; value=&quot;{address_2}&quot; /&gt;
		&lt;/li&gt;
		&lt;li&gt;
			&lt;label for=&quot;city&quot;&gt;City&lt;/label&gt;				
			&lt;input type=&quot;text&quot; id=&quot;city&quot; name=&quot;city&quot; maxlength=&quot;100&quot; value=&quot;{city}&quot; /&gt;
		&lt;/li&gt;
		&lt;li&gt;
			&lt;label for=&quot;region&quot;&gt;State/Province&lt;/label&gt;
			&lt;select id=&quot;region&quot; name=&quot;region&quot;&gt;{region_options}&lt;/select&gt;&amp;nbsp;&amp;nbsp;&lt;input type=&quot;text&quot; id=&quot;region_other&quot; name=&quot;region_other&quot; value=&quot;{region_other}&quot; /&gt;
		&lt;/li&gt;
		&lt;li&gt;
			&lt;label for=&quot;country&quot;&gt;Country&lt;/label&gt;
			&lt;select name=&quot;country&quot; id=&quot;country&quot;&gt;{country_options}&lt;/select&gt;
		&lt;/li&gt;
		&lt;li&gt;
			&lt;label for=&quot;postal_code&quot;&gt;Zip/Postal Code&lt;/label&gt;
			&lt;input type=&quot;text&quot; id=&quot;postal_code&quot; name=&quot;postal_code&quot; maxlength=&quot;100&quot; value=&quot;{postal_code}&quot; /&gt;
		&lt;/li&gt;
		&lt;li&gt;
			&lt;label for=&quot;email&quot;&gt;Email Address&lt;/label&gt;
			&lt;input type=&quot;text&quot; id=&quot;email&quot; name=&quot;email&quot; maxlength=&quot;100&quot; value=&quot;{email}&quot; /&gt;
		&lt;/li&gt;
{if logged_out}
	&lt;li&gt;
			&lt;label for=&quot;password&quot;&gt;Password&lt;/label&gt;
			&lt;input type=&quot;text&quot; id=&quot;password&quot; name=&quot;password&quot; maxlength=&quot;100&quot; value=&quot;&quot; /&gt;
	&lt;/li&gt;
{/if}
	&lt;/ul&gt;
&lt;/fieldset&gt;
&lt;fieldset&gt;
	&lt;legend&gt;Review and Confirm&lt;/legend&gt;
	&lt;input type=&quot;submit&quot; value=&quot;Donate Now!&quot;&gt;
&lt;/fieldset&gt;
&lt;/form&gt;
{/exp:eedonations:donate_form}
</pre>