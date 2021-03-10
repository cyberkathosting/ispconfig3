<?php

$function_list['server_get,server_config_set,get_function_list,client_templates_get_all,server_get_serverid_by_ip,server_ip_get,server_ip_add,server_ip_update,server_ip_delete,system_config_set,system_config_get,config_value_get,config_value_add,config_value_update,config_value_replace,config_value_delete'] = 'Server functions';
$function_list['admin_record_permissions'] = 'Record permission changes';

# Roundcube: generate list of actual soap methods used with:
# grep soap-\> /usr/share/roundcube/plugins/ispconfig3_*/ispconfig3_*.php | sed -e 's/^.*soap->//g' -e 's/(.*$//g' | sort -u | xargs | sed -e 's/ /,/g'
#
$function_list['client_get_id,login,logout,mail_alias_get,mail_fetchmail_add,mail_fetchmail_delete,mail_fetchmail_get,mail_fetchmail_update,mail_policy_get,mail_spamfilter_blacklist_add,mail_spamfilter_blacklist_delete,mail_spamfilter_blacklist_get,mail_spamfilter_blacklist_update,mail_spamfilter_user_add,mail_spamfilter_user_get,mail_spamfilter_user_update,mail_spamfilter_whitelist_add,mail_spamfilter_whitelist_delete,mail_spamfilter_whitelist_get,mail_spamfilter_whitelist_update,mail_user_filter_add,mail_user_filter_delete,mail_user_filter_get,mail_user_filter_update,mail_user_get,mail_user_update,server_get,server_get_app_version'] = 'Roundcube plugins functions';

?>
