## Installation & Configuration

To set your site up with EE Donations, you must first install the OpenGateway billing engine in a sub-folder or sub-domain on
your web server.  Then, you can upload EE Donations to your ExpressionEngine install to complete the process.

### Installing OpenGateway

1.  [Download the latest OpenGateway release](http://www.github.com/electricfunction/opengateway).
2.  Upload all the application files to your web server.
4.  Rename `/system/opengateway/config/config.example.php` to `/system/opengateway/config/config.php`.
5.  If the ".htaccess" file doesn't exist in your root folder, rename "1.htaccess" to ".htaccess".
6.  Access the "install/" directory on your web server, e.g., http://www.example.com/install.  **This directory does not actually
	exist but the URL routing system in OpenGateway will direct you automatically.**  If you uploaded your files to your root domain
	folder at example.com, you would access example.com/install.  If you uploaded your files to a sub-directory, you would access
	example.com/subdirectory/install.  The same applies to subdomains.
7.  Follow the 2-step installation wizard to configure your database and create your cron jobs.
8.  Login to the OpenGateway control panel with your username/password, go to "Settings > API Access",
	and write down your API credentials for use later.
9.  **Important Note**:  If you are using the same OpenGateway install for two installations of EE Donations, or EE Donations and
	[Membrr](http://www.membrr.com), you should create one OpenGateway client account for each installation
	to avoid serious conflicts.  Each account will have unique API access credentials.

### Installing the EE Donations Plugin

Now that your OpenGateway billing engine is setup, you can install EE Donations and connect it to your OpenGateway software.

1.  Download the latest EE Donations plugin for your ExpressionEngine version.
2.  Unzip and upload the `/eedonations` folder to `/system/expressionengine/third_party/`.  If you have renamed your system
	folder, use that folder instead.
3.  Login to your ExpressionEngine control panel and go to Addons > Modules.  Install the EE Donations module.
Now you are ready to go with EE Donations!