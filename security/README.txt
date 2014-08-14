
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


