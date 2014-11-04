
Description for security_settings.ini values.

The option "superadmin" means that a setting is only available to the admin user with userid 1 in the interface. 
If there are other amdins, then they cant access this setting.

-----------------------------------------------------------
Setting:     allow_shell_user
Options:     yes/no
Description: Disables the shell user plugins in ispconfig

Setting:     admin_allow_server_config
Options:     yes/no/superadmin
Description: Disables System > Server config

Setting:     admin_allow_server_services
Options:     yes/no/superadmin
Description: Disables System > Server services

Setting:     admin_allow_server_ip
Options:     yes/no/superadmin
Description: Disables System > Server IP

Setting:     admin_allow_remote_users
Options:     yes/no/superadmin
Description: Disables System > Remote Users

Setting:     admin_allow_system_config
Options:     yes/no/superadmin
Description: Disables System > Interface > Main Config

Setting:     admin_allow_server_php
Options:     yes/no/superadmin
Description: Disables System > Additional PHP versions

Setting:     admin_allow_langedit
Options:     yes/no/superadmin
Description: Disables System > Language editor functions

Setting:     admin_allow_new_admin
Options:     yes/no/superadmin
Description: Disables the ability to add new admin users trough the interface

Setting:     admin_allow_del_cpuser
Options:     yes/no/superadmin
Description: Disables the ability to delete CP users

Setting:     admin_allow_cpuser_group
Options:     yes/no/superadmin
Description: Disables cp user group editing

Setting:     admin_allow_firewall_config
Options:     yes/no/superadmin
Description: Disables System > Firewall

Setting:     admin_allow_osupdate
Options:     yes/no/superadmin
Description: Disables System > OS update

Setting:     admin_allow_software_packages
Options:     yes/no/superadmin
Description: Disables System > Apps & Addons > Packages and Update

Setting:     admin_allow_software_repo
Options:     yes/no/superadmin
Description: Disables System > Apps & Addons > Repo

Setting:     remote_api_allowed
Options:     yes/no
Description: Disables the remote API

Setting:     password_reset_allowed
Options:     yes/no
Description: Disables the password reset function.

Setting:     ids_enabled
Options:     yes/no
Description: Enables the Intrusion Detection System

Setting:     ids_log_level
Options:     1 (number, default = 1)
Description: IDS score that triggers the log in /usr/local/ispconfig/interface/temp/ids.log
             This log can be used to feed the whitelist. 
			 
			 Example:
			 
			 cat /usr/local/ispconfig/interface/temp/ids.log >> /usr/local/ispconfig/security/ids.whitelist
			 rm -f /usr/local/ispconfig/interface/temp/ids.log
			 
			 If you want to use a custom whitelist, then store it as /usr/local/ispconfig/security/ids.whitelist.custom

Setting:     ids_warn_level
Options:     5 (number, default = 5)
Description: When the IDS score exceeds this level, a error message is logged into the system log. No message is displayed to the user.

Setting:     ids_block_level
Options:     100 (number, default = 100)
Description: When the IDS score exceeds this level, a error message is shown to the user and further processing is blocked. A score of 100 will most likely never be reached. 
             We have choosen such a high score as default until we have more complete whitelists for this new feature.

Setting:     sql_scan_enabled
Options:     yes/no
Description: Enables the scan for SQL injections in the DB library.

Setting:     sql_scan_action
Options:     warn/block
Description: warn = write errot message to log only. Block = block user action and show error to the user.

Setting:     apache_directives_scan_enabled
Options:     yes/no
Description: Scan apache directives field for potentially malicious directives. This function uses the regex
             list from /usr/local/ispconfig/security/apache_directives.blacklist file.
			 If you want to use a custom blacklist, then store it as /usr/local/ispconfig/security/apache_directives.blacklist.custom

Setting:     security_admin_email
Options:     email address
Description: Email address of the security admin

Setting:     security_admin_email_subject
Options:     Text
Description: Subject of the notification email

Setting:     warn_new_admin
Options:     yes/no
Description: Warn by email when a new admin user in ISPConfig has been added.

Setting:     warn_passwd_change
Options:     yes/no
Description: Warn by email when /etc/passwd has been changed.

Setting:     warn_shadow_change
Options:     yes/no
Description: Warn by email when /etc/shadow has been changed.

Setting:     warn_group_change
Options:     yes/no
Description: Warn by email when /etc/group has been changed.


