## Creating Automatic Emails

Emails can be sent out to the donor, yourself, or any other email address when certain actions are performed.  The following actions
can trigger email notifications:

*   Charge
*   Recurring Charge
*   Recurring Expiration
*   Recurring Cancellation
*   Recurring to Expire in a Week
*   Recurring to Expire in a Month
*   Recurring to Autocharge in a Week
*   Recurring to Autocharge in a Month
*   New Customer
*   New Recurring (i.e., New Subscription)

Emails are created and managed in your OpenGateway control panel.

### Creating Emails in OpenGateway

In your OpenGateway control panel, go to "Settings" > "Emails" to create and manage your automatic emails.

As stated above, the first criteria you'll specify for your email is when it should be sent.  Emails are
associated with a particular event such as a cancellation or subscription.  They can also be associated
with a specific subscription plan.

When creating an email, you can include dynamic information like the donor's name, the charge amount/date,
the subscription plan name, etc.  Each event has a unique set of data which will be available for use in your
email subject and body.  When creating a plan, there will be a list of all dynamic data tags (e.g., `[[donor_LAST_NAME]]`)
displayed beside the email editor, for use as a reference.

### Formatting Dates in Emails

Date variables are often available in emails for things like the Next Charge Date, Order Date, etc.  You are able to specify the
format for these dates.  For example, while the date variable may be "2010-09-19", you can print this as:

*   September 19, 2010
*   Sep 19, 2010
*   2010.09.19
*   19-Sep-2010
*   etc.

Formatting is done by passing a parameter with the variable.  For Example: `[[NEXT_CHARGE_DATE|"M d, Y"]]`.  The second parameter (in quotation marks)
tells the application how to display the dates.  You can specify any date format using either of PHP's [date()](http://www.php.net/date)
and [strftime()](http://www.php.net/strftime) formatting styles.