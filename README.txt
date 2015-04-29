Linkedin Moodle Authentication

Author: Bas Brands
Funded by: BrightAlley, Sonsbeekmedia.

- Updates

April 29 2015:
- new version for Moodle 2.8 and newer:
- update event system
- update user fields

August 21st 2013: 
- This plugin now retreives the user email addresses from LinkedIn.

- About this plugin:

This authentication plugin has been created based on the code written by:
https://github.com/mahmudahsan using code published here:
https://github.com/mahmudahsan/Linkedin---Simple-integration-for-your-website

Using Moodle OAuth examples by Jerome Mouneyrac:
http://moodle.org/plugins/view.php?plugin=auth_googleoauth2

The latest versions of these plugins can be downloaded from GitHub:
https://github.com/bmbrands/auth_linkedin


- Plugin Requirements

Requirements:
A Linked in Account
A Linked in integration Key / Secret
The Linkedin Block


- How it works (user)

* Click login with linked in
* Authorize Moodle to get your profile info
* You are now logged in


- Install the LinkedIn auth plugin

1. Add the auth plugin to /auth/linkedin
2. In Moodle go to the plugins page for authentication and enable linkedin.
3. visit https://www.linkedin.com/secure/developer and click Add New Application.
4. Enter your Moodle website details, most important field: Integration URL, use the wwwroot of your Moodle installation.
5. Check the boxes 'r_basicprofile', 'r_fullprofile' and 'r_emailaddress'.
5. Copy your key / secret to the LinkedIn authentication plugin settings page in Moodle.


- Install the LinkedIn block

1. Install the linkedin block.
2. Go to the Siteadmin -> notifications to install the block.
3. Add the block to the moodle frontpage.