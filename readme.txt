=== Passport WordPress Plugin ===

Contributors: dwklatt, inversoft, degroff
Donate link:
Tags: sso, security, two-factor, twofactor, login,  authenticate,  authentication, oauth
Requires at least: 4.1.10
Tested up to: 4.4.2
Stable tag: 1.0.1
License: GPLv2 or later

== Description ==
This plugin uses OAuth provided by Inversoft Passport to handle user login and registration.

Passport is a user database that allows you easily create, register and manage your users. In addition to managing your WordPress user accounts Passport can manage user registration and login for your forums, chat, games, internal users or whatever else you can dream up. You can download and learn more about Passport at https://www.inversoft.com/products/user-management-sso


== Installation ==
**Make sure your settings are correct before logging out!!**
= In Passport =
1. Add a new API Key by navigating to `Settings` --> `API Keys`. Leaving the default permissions will give this API key access to all of the Passport APIs. You may also choose limit the permissions on this key, the following are required
  * `[GET] /api/user`
  * `[POST] /api/user/registration` **Required only** if you configure the plugin to register new users automatically
2. Create an application named WordPress or another name of your choosing, click on the `OAuth` tab and provide Passport the authorized URLs, request origins and logout URL for your WordPress site.
3. Click `Manage Roles` from the available row actions for the application you just created and add the following WordPress roles:
  * Administrator
  * Author
  * Contributor
  * Editor
  * Subscriber
4. Make note of the API key, Client ID and Client Secret, these will be needed in the WordPress configuration steps.
= In WordPress =
1. To manually install, follow one of the following options:
  * Unzip the plugin into wp-content/plugins in your WordPress installation
  * Log into your WordPress site, navigate to `Plugins` --> `Add New` --> `Upload Plugin` and select the `passport-wordpress-plugin.zip`
2. Once installed, Activate the plugin.
3. Verify your settings and configuration.
  * Ensure Passport Backend and Passport Frontend are running.
  * Verify each item from Step 4 above is provided and correct.
  * Test a login from another browser before logging out of your current session.

= Example Settings =
* Passport Backend URI: `http://127.0.0.1:9011`
* Passport Frontend URI: `http://127.0.0.1:9031`
* Client ID: `e6430720-d546-4491-a80f-567a833530af`
* Client Secret: `f90d331d-a148-49db-9490-f047556c7215`
* API Key: `d9d732fc-92cc-46c1-9008-80cce0cc8b27`


== Upgrade Notice ==
None
== Frequently Asked Questions ==
= What is Passport? =
Found out here https://www.inversoft.com/products/user-management-sso
== Changelog ==
First release
== Screenshots ==
Look at the example settings

== Get the Plugin ==
= http://savant.inversoft.org/com/inversoft/passport/passport-wordpress-plugin/ =
or
= https://github.com/inversoft/passport-wordpress-plugin =
