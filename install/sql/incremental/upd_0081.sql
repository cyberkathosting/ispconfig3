UPDATE `dns_template` SET `fields` = 'DOMAIN,IP,NS1,NS2,EMAIL,DKIM' WHERE `dns_template`.`template_id` =1;
UPDATE `dns_template` SET `template` = '[ZONE]
origin={DOMAIN}.
ns={NS1}.
mbox={EMAIL}.
refresh=7200
retry=540
expire=604800
minimum=86400
ttl=3600

[DNS_RECORDS]
A|{DOMAIN}.|{IP}|0|3600
A|www|{IP}|0|3600
A|mail|{IP}|0|3600
NS|{DOMAIN}.|{NS1}.|0|3600
NS|{DOMAIN}.|{NS2}.|0|3600
MX|{DOMAIN}.|mail.{DOMAIN}.|10|3600
TXT|{DOMAIN}.|v=spf1 mx a ~all|0|3600' WHERE `dns_template`.`template_id` = 1;
