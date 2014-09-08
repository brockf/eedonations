## Template Tags

### &#123;exp:eedonations:donate_form&#125;</a>

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

This tag allows you to create a customized donation setup form with your own HTML.  It supplies default
and existing form values but you must create the &lt;form&gt; and &lt;input&gt; elements in your custom
order form.

This tag can also register a new user in the ExpressionEngine member database, providing certain conditions are met.  This allows you
to condense the donation process into one form.  For more information, see "Combined Registration &amp; Donation" below.

Parameters:

*   **redirect_url** - The user is redirected here after they successfully create their donation.
*   **anonymous** - (optional) Set to "true" to allow anonymous donations via this form.

The order form must be configured in the following manner:

*   Method: "POST"
*   Action: "" (leave blank so the form submits to the page which the order form is on)
*   The user should be able to select their region in a "region" dropdown if they are from North America.
	However, if they are outside of North America, they should populate a text input field named "region_other".
	It is recommended to use JavaScript to show/hide the "region_other" input box, depending on their
	country selection.

Between the two <span class="tag">{exp:eedonations:donate_form}</span> <span class="tag">{/exp:eedonations:donate_form}</span> tags,
you can use any of the following variables in the building of your form:

*   **first_name**
*   **last_name**
*   **company**
*   **address**
*   **address_2**
*   **city**
*   **region**
*   **region_other**
*   **country**
*   **postal_code**
*   **email**
*   **phone**
*   **region_options** - A string containing all of the &lt;option&gt; tags for use in the "region" &lt;select&gt; input element (e.g., "&lt;option value="AB"&gt;Alberta&lt;/option&gt;&lt;option value="BC"&gt;British Columbia&lt;/option&gt;).  If available, the member's stored region is pre-selected in this string.
*   **region_raw_options** (EE2 only) - An array containing each region in ID => Name pairings.
*   **country_options** - Identical to "region_options", except for country options and the "country" &lt;select&gt; element.
*   **country_raw_options** (EE2 only) - An array containing each country in ID => Name pairings.
*   **cc_expiry_month_options** -  A string containing all of the &lt;option&gt; tags for use in the "cc_expiry_month" &lt;select&gt; element.
*   **cc_expiry_year_options** -  A string containing all of the &lt;option&gt; tags for use in the "cc_expiry_year" &lt;select&gt; element.
*   **amount_options** -  A string containing all of the &lt;option&gt; tags for use in a "amount" &lt;select&gt; element.
*   **interval_options** -  A string containing all of the &lt;option&gt; tags for use in a "interval" &lt;select&gt; element.
*   **gateway_options** - A string containing all of the &lt;option&gt; tags for use in the "gateway" &lt;select&gt; element.
*   **gateway_raw_options** (EE2 only) - An array containing each gateway in ID => Name pairings.
*   **errors_array** (EE2 only) - An array of all the errors in the processed form.  Only available upon submission.
*   **errors** - A string of all errors in the processed form, with each error in a &lt;div class="error"&gt; element.  Only available upon form submission.
*   **form_action - The current URL**
*   **form_method - "POST"**

Upon submission of the form, the following values are **required**:

*   **amount** or **custom_amount**
*   **interval** or **custom_interval** (set to "0" or empty for no recurrence)
*   **eedonations_donate_form** - Set to any value
*   _If you are not using PayPal Express Checkout:_

        *   **cc_number** - Credit card number
    *   **cc_name** - Name on the credit card
    *   **cc_expiry_month** - 2 digit representation of the year
    *   **cc_expiry_year** - 4 digit representation of the expiry year
    *   **cc_cvv2** - (Optional) The 3-4 digit security code on the credit card

The following customer information fields _can also be submitted in the order form submission_, but they are **NOT** required for all gateways:

*   **first_name**
*   **last_name**
*   **company**
*   **address**
*   **address_2**
*   **city**
*   **region** (for North American customers)
*   **region_other** (for Non-North American customers)
*   **country**
*   **postal_code**
*   **email**
*   **phone**

Finally, these fields are also optional:

*   **gateway** - Specify the OpenGateway gateway_id to process the transaction with.  This allows you to give your users
	a choice of gateways when donating.

### Renewals

If you would like the donation subscription being created to act as a renewal for a previous subscription, you can pass along a hidden input field named
"renew" with the value of the subscription ID being renewed.

Renewal subscription ID's are validated as (a) existing and, (b) being tied to this member's account.  Only subscriptions can renew old subscriptions,
so the "interval" value must be greater than zero.

Renewal subscriptions do not start until the previous subscription ends (i.e., the user will continue with their regular donation schedule).

Example renewal form (where <span class="tag">{segment_3}</span> holds an old subscription_id):

<pre class="example">
	{exp:eedonations:donate_form}
	&lt;form method=&quot;post&quot; action=&quot;{form_action}&quot;&gt;
	&lt;input type=&quot;hidden&quot; name=&quot;renew&quot; value=&quot;{segment_3}&quot; /&gt;
	&lt;!-- ALL YOUR OTHER ORDER FORM FIELDS WOULD GO HERE --&gt;
	&lt;/form&gt;
</pre>

### Combined Member Registration &amp; Donation

This function allows for EE member registration and donation to happen all in one order form.  Thus, users are not
required to be logged-in to an existing EE member account before using the donation form.

Implementation Notes:

*   To enable member registration alongside donation, simply pass at least one input field in the form called "password".
*   If you pass a second "password2" field, it will be compared to "password" to ensure that they match (or throw an error).
*   By default, the "email" field will be used for the user's email, username, and screen name.  However, you can optionally pass
"username" and "screen_name" fields to override the value for these fields.
*   Custom profile fields are not used.
*   Members will be placed in the Default Member Group for new members.
*   If the payment fails, the newly created account will be immediately deleted so as to let them complete the form again.

If you do use this registration feature, you should wrap your "password" (and, optionally, "username" and "screen_name") fields
with <span class="tag">{if logged_out}</span> and <span class="tag">{/if}</span> so that logged in users do not create new accounts.

### &#123;exp:eedonations:receipt&#125;</a>

<pre class="example">
{exp:eedonations:receipt date_format=&quot;M j, Y&quot;}
&lt;p&gt;Donation ID: {charge_id}&lt;/p&gt;
&lt;p&gt;Thank you for your donation, {donor_first_name}!&lt;/p&gt;
&lt;p&gt;We have received your donation in the amount of &lt;b&gt;${amount}&lt;/b&gt;.
{if next_charge_date}
&lt;p&gt;Your next donation will be automatically made on &lt;b&gt;{next_charge_date}&lt;/b&gt; in the amount of $&lt;b&gt;{amount}&lt;/b&gt;.&lt;/p&gt;
{/if}
{/exp:eedonations:receipt}
</pre>

Retrieves data about the latest donation for the purposes of showing a receipt to the user, post-donation form.

Optional Parameters:

*   **date_format** - The format for returned full dates, as per the [PHP date() function standard](http://www.php.net/date).

Returns:

*   Single Variable Values:

    *   <span class="tag">{charge_id}</span>
    *   <span class="tag">{member_id}</span> (if applicable)
    *   <span class="tag">{amount}</span>
    *   <span class="tag">{subscription_id}</span> (if applicable)
    *   <span class="tag">{next_charge_date}</span> (if applicable)
    *   <span class="tag">{donor_first_name}</span>
    *   <span class="tag">{donor_last_name}</span>
    *   <span class="tag">{donor_address}</span>
    *   <span class="tag">{donor_address_2}</span>
    *   <span class="tag">{donor_city}</span>
    *   <span class="tag">{donor_region}</span>
    *   <span class="tag">{donor_country}</span>
    *   <span class="tag">{donor_postal_code}</span>
    *   <span class="tag">{donor_company}</span>
    *   <span class="tag">{donor_phone}</span>

### &#123;exp:eedonations:update_form&#125;</a>

<pre class="example">
{exp:eedonations:update_form subscription_id=&quot;{segment_2}&quot; redirect_url=&quot;http://www.example.com/successful_update&quot;}
{if errors}
	&lt;div class=&quot;errors&quot;&gt;
	{errors}
	&lt;/div&gt;
{/if}
&lt;form method=&quot;{form_method}&quot; action=&quot;{form_action}&quot;&gt;
&lt;input type=&quot;hidden&quot; name=&quot;eedonations_update_form&quot; value=&quot;1&quot; /&gt;
&lt;input type=&quot;hidden&quot; name=&quot;subscription_id&quot; value=&quot;{subscription_id}&quot; /&gt;
&lt;fieldset&gt;
	&lt;legend&gt;Update Your Credit Card Information&lt;/legend&gt;
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
	&lt;legend&gt;Update Your Billing Address&lt;/legend&gt;
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
	&lt;/ul&gt;
&lt;/fieldset&gt;
&lt;fieldset&gt;
	&lt;legend&gt;Review and Save Changes&lt;/legend&gt;
	&lt;input type=&quot;submit&quot; value=&quot;Update Billing Information&quot;&gt;
&lt;/fieldset&gt;
&lt;/form&gt;
{/exp:eedonations:update_form}
</pre>

This tag allows you to create a customized form for the user to update their credit card information and/or **upgrade/downgrade their subscription plan**.
By updating the credit card
information associated with an existing subscription, the user can keep their subscription alive with a new credit card.  It supplies default
and existing form values but you must create the &lt;form&gt; and &lt;input&gt; elements in your custom form.

Note: The subscription ID will change when the user updates their subscription.

Parameters:

*   **subscription_id** - The subscription to update.
*   **redirect_url** - The user is redirected here after they update their subscription.

The form must be configured in the following manner:

*   Method: "POST"
*   Action: "" (leave blank so the form submits to the page which the order form is on)

_It is not necessary to include the customer address fields in this form_.  If they are not included, the billing address will simply stay as is.
However,
if you are showing these fields, the user should be able to select their region in a "region" dropdown if they are from North America.
If they are outside of North America, they should populate a text input field named "region_other".
It is recommended to use JavaScript to show/hide the "region_other" input box, depending on their
country selection.

Between the two <span class="tag">{exp:eedonations:update_form}</span> <span class="tag">{/exp:eedonations:update_form}</span> tags,
you can use any of the following variables in the building of your form:

*   **first_name**
*   **last_name**
*   **address**
*   **address_2**
*   **city**
*   **region**
*   **region_other**
*   **country**
*   **postal_code**
*   **email**
*   **region_options** - A string containing all of the &lt;option&gt; tags for use in the "region" &lt;select&gt; input element (e.g., "&lt;option value="AB"&gt;Alberta&lt;/option&gt;&lt;option value="BC"&gt;British Columbia&lt;/option&gt;).  If available, the member's stored region is pre-selected in this string.
*   **region_raw_options** (EE2 only) - An array containing each region in ID => Name pairings.
*   **country_options** - Identical to "region_options", except for country options and the "country" &lt;select&gt; element.
*   **country_raw_options** (EE2 only) - An array containing each country in ID => Name pairings.
*   **cc_expiry_month_options** -  A string containing all of the &lt;option&gt; tags for use in the "cc_expiry_month" &lt;select&gt; element.
*   **cc_expiry_year_options** -  A string containing all of the &lt;option&gt; tags for use in the "cc_expiry_year" &lt;select&gt; element.
*   **errors_array** (EE2 only) - An array of all the errors in the processed form.  Only available upon submission.
*   **errors** - A string of all errors in the processed form, with each error in a &lt;div class="error"&gt; element.  Only available upon form submission.
*   **form_action - The current URL**
*   **form_method - "POST"**

Upon submission of the form, the following values are **required**:

*   **subscription_id**
*   **eedonations_update_form** - Set to any value
*   **cc_number** - Credit card number
*   **cc_name** - Name on the credit card
*   **cc_expiry_month** - 2 digit representation of the year
*   **cc_expiry_year** - 4 digit representation of the expiry year
*   **cc_cvv2** - (Optional) The 3-4 digit security code on the credit card

The following customer information fields _can also be submitted in the order form submission_, but they are **NOT** required:

*   **first_name**
*   **last_name**
*   **address**
*   **address_2**
*   **city**
*   **region** (for North American customers)
*   **region_other** (for Non-North American customers)
*   **country**
*   **postal_code**
*   **email**

### &#123;exp:eedonations:custom_fields&#125;</a>

<pre class="example">
{exp:eedonations:payments id="768082"}
	{amount}
	{if plan_name}Plan: {plan_name}{/if}

	{exp:eedonations:custom_fields donation_id='{charge_id}'}
		{!-- We have a custom field called "Food Drive" --}
		{Food Drive}
	{/exp:eedonations:custom_fields}
{/exp:eedonations:payments}	
</pre>

If you are using custom fields in your donation form, the data for any donation ID or donation subscription ID can be retrieved in a template
with this tag.

Parameters:

*   **donation_id** - The ID of a particular donation charge (required if no subscription ID is passed)
*   **subscription_id** - The ID of a recurring donation subscription (required if no donation ID is passed)

### &#123;exp:eedonations:subscriptions&#125;</a>

Returns a list of donation subscriptions for the logged-in user in the format of the HTML between the tags.&nbsp; A number of tags and conditionals can be used between the tags.

Optional Parameters:

*   **member_id** - Specify a specific member to return subscription records for (default: logged in user)
*   **date_format** - The format for returned full dates, as per the [PHP date() function standard](http://www.php.net/date).
*   **status** - Set to "active" or "inactive" to retrieve only those plans.
*   **inactive** - Set to &#8220;1&#8221; to retrieve only expired subscriptions.
*   **id** - Specify a particular subscription ID # to return only that subscription.

Returns:

*   Single Variable Values:

    *   <span class="tag">{subscription_id}</span>
    *   <span class="tag">{amount}</span>
    *   <span class="tag">{interval}</span>
    *   <span class="tag">{date_created}</span>
    *   <span class="tag">{date_cancelled}</span> (if exists) - The date the subscription was cancelled by the user.
    *   <span class="tag">{next_charge_date}</span> (if exists) - The date of the next charge
    *   <span class="tag">{end_date}</span> (if exists) - The date this subscription will expire
*   Conditionals:

        *   <span class="tag">active</span> - Subscription is still actively recurring.
<pre class="example"><div class="codeblock">`<span style="color: #000000">
<span style="color: #0000BB">{if active}Your next charge will be {next_charge_date}{</span><span style="color: #007700">/</span><span style="color: #0000BB">if} </span>
</span>
`</div></pre>
    *   <span class="tag">renewed</span> - Subscription has been renewed.  Another subscription will simultaneously be active (the renewing subscription).
<pre class="example"><div class="codeblock">`
{if renewed}This subscription has been renewed.{/if}
`</div></pre>
    *   <span class="tag">cancelled</span> - Subscription was cancelled by the user

        <pre class="example"><div class="codeblock">`<span style="color: #000000">
<span style="color: #0000BB">{if user_cancelled}You cancelled this subscription on {date_cancelled} </span><span style="color: #007700">and </span><span style="color: #0000BB">it will expire on {end_date}{</span><span style="color: #007700">/</span><span style="color: #0000BB">if} </span>

        </span>
`</div></pre>
    *   <span class="tag">expired</span> - Subscription has expired completely.

        <pre class="example"><div class="codeblock">`<span style="color: #000000">
<span style="color: #0000BB">{if expired}This subscription has expired</span><span style="color: #007700">, </span><span style="color: #0000BB">either by user cancellation </span><span style="color: #007700">or </span><span style="color: #0000BB">failed payment</span><span style="color: #007700">.  </span><span style="color: #0000BB">But its over</span><span style="color: #007700">.  And </span><span style="color: #0000BB">it ended on {end_date}{</span><span style="color: #007700">/</span><span style="color: #0000BB">if} </span>

        </span>
`</div></pre>

### &#123;exp:eedonations:payments&#125;</a>

Returns donation payment history for the logged-in user in the format of the HTML between the tags.&nbsp; A number of tags can be used between the tags.

Optional Parameters:

*   **member_id** - Specify a specific member to return payment records for (default: logged in user)
*   **date_format** - The format for returned full dates, as per the [PHP date() function standard](http://www.php.net/date).
*   **id** - Specify a particular charge ID # to return only that charge.
*   **offset** - Number of records to offset the results from (e.g., offset of &#8220;5&#8221; returns from 6th record on).
*   **limit** - Number of records to return in total.
*   **subscription_id** - Specify a particular subscription ID # to return only charges pertaining to that subscription.

Returns:

*   Single Variable Values:

    *   <span class="tag">{charge_id}</span>
    *   <span class="tag">{subscription_id}</span> (if linked to a subscription)
    *   <span class="tag">{amount}</span>
    *   <span class="tag">{date}</span>
*   Conditionals:

### &#123;exp:eedonations:billing_address&#125;</a>

Retrieve and display the current billing address (entered on an order form) of the logged-in member.

Returns:

*   <span class="tag">first_name</span>
*   <span class="tag">last_name</span>
*   <span class="tag">address</span>
*   <span class="tag">address_2</span>
*   <span class="tag">city</span>
*   <span class="tag">region</span>
*   <span class="tag">region_other</span>
*   <span class="tag">country</span>
*   <span class="tag">postal_code</span>
*   <span class="tag">company</span>
*   <span class="tag">phone</span>

### &#123;exp:eedonations:cancel&#125;</a>

Cancels an active donation subscription.

Parameters:

*   **id** - The ID # of the subscription to cancel.

Returns:

*   Returns the HTML between the tags.&nbsp; It also implements the following conditionals to be used for confirmation/failure.

    *   <span class="tag">cancelled</span> - The subscription was cancelled successfully.
    *   <span class="tag">failed</span> - The cancellation failed either due to system error, the user not owning the subscription, or the subscription already being cancelled.

Example:

    <pre class="example"><div class="codeblock">`<span style="color: #000000">
<span style="color: #0000BB">{exp</span><span style="color: #007700">:</span><span style="color: #0000BB">eedonations</span><span style="color: #007700">:</span><span style="color: #0000BB">cancel id</span><span style="color: #007700">=</span><span style="color: #DD0000">"{segment_3}"</span><span style="color: #0000BB">}
{if cancelled}Your subscription was cancelled</span><span style="color: #007700">!</span><span style="color: #0000BB">{</span><span style="color: #007700">/</span><span style="color: #0000BB">if}
{if failure}Subscription could not be cancelled</span><span style="color: #007700">.</span><span style="color: #0000BB">{</span><span style="color: #007700">/</span><span style="color: #0000BB">if}
{</span><span style="color: #007700">/</span><span style="color: #0000BB">exp</span><span style="color: #007700">:</span><span style="color: #0000BB">eedonations</span><span style="color: #007700">:</span><span style="color: #0000BB">cancel} </span>
</span>
`</div></pre>