## Extension Hooks

### eedonations_donate

Allows you to perform your own custom actions upon a new donation.

**Hook File**

`class.eedonations.php`

**Hook Parameters**

*   `$payment_id` - The ID of the processed donation payment
*   `$recurring_id` - The ID of the subscription, if available
*   `$member_id` - The Member ID of the subscribing user.
*   `$amount` - The amount of the donation
*   `$interval` - The recurrence interval (FALSE if no-recurrence)

**Hook Returns Data?**

No

**Appearance of Hook in Code**

<pre class="example">
// call "eedonations_donate" hook with: payment_id, recurring_id, member_id, amount, interval
if ($this->EE->extensions->active_hook('eedonations_donate') == TRUE)
{
	$this->EE->extensions->call('eedonations_donate', $response['charge_id'], $recurring_id, $member_id, $amount, $interval);
	if ($this->EE->extensions->end_script === TRUE) return $response;
}
</pre>

### eedonations_pre_donate

Allows you to add to or modify the $$charge OpenGateway API call object prior to the &#8220;Charge/Recur&#8221; API request, and modify any of the soon-to-be created donation&#8217;s details.

**Hook File**

`class.eedonations.php`

**Hook Parameters**

*   `$charge` - The OpenGateway class object storing the developed Charge/Recur API request.
*   `$member_id` - The Member ID of the subscribing user.
*   `$interval` - The recurrence interval (FALSE if no-recurrence)
*   `$end_date` - The date of the next charge to be made.

**Hook Returns Data?**

No

**Appearance of Hook in Code**

<pre class="example">
// call "eedonations_pre_donate" hook with: $charge, $member_id, $interval, $end_date

if ($this->EE->extensions->active_hook('eedonations_pre_donate') == TRUE)
{
	$this->EE->extensions->call('eedonations_pre_donate', $charge, $member_id, $interval, $end_date);
	if ($this->EE->extensions->end_script === TRUE) return FALSE;
}
</pre>

### eedonations_payment

Allows you to perform your own custom actions upon a new payment.

**Hook File**

`class.eedonations.php`

**Hook Parameters**

*   `$payment_id` - The ID of the payment
*   `$recurring_id` - The recurring ID linked to the payment (if available)
*   `$member_id` - The Member ID of the subscribing user.
*   `$amount` - The amount of the donation

**Hook Returns Data?**

No

**Appearance of Hook in Code**

<pre class="example">
// call "eedonations_donate" hook with: payment_id, recurring_id, member_id, amount
if ($this->EE->extensions->active_hook('eedonations_payment') == TRUE) {
	$this->EE->extensions->call('eedonations_payment', $charge_id, $subscription_id, $member_id, $amount);
	if ($this->EE->extensions->end_script === TRUE) return $response;
}
</pre>

### eedonations_cancel

Allows you to perform your own custom actions upon a donation subscription cancellation (i.e., at the time a user cancels or when the card is declined).

**Hook File**

`class.eedonations.php`

**Hook Parameters**

*   `$member_id` - The Member ID of the subscribing user.
*   `$subscription_id` - The ID of the subscription being cancelled.

**Hook Returns Data?**

No

**Appearance of Hook in Code**

<pre class="example">
// call "eedonations_cancel" hook with: member_id, subscription_id, end_date
if ($this->EE->extensions->active_hook('eedonations_cancel') == TRUE)
{
	$this->EE->extensions->call('eedonations_cancel', $subscription['member_id'], $subscription['id'], $subscription['end_date']);
	if ($this->EE->extensions->end_script === TRUE) return;
} 
</pre>