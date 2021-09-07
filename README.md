# ISPConfig - Hosting Control Panel
![ISPConfig logo](https://www.ispconfig.org/wp-content/themes/ispconfig/images/ispconfig_logo.png "") \
Development branch: [![pipeline status](https://git.ispconfig.org/ispconfig/ispconfig3/badges/develop/pipeline.svg)](https://git.ispconfig.org/ispconfig/ispconfig3/commits/develop)

## Functions
- Manage multiple servers from one control panel
- Single server, multiserver and mirrored clusters.
- Webserver management
- Mailserver management
- DNS server management
- Virtualization (OpenVZ)
- Administrator, reseller, client and mailuser login
- Open Source software ([BSD license](LICENSE))

## Supported daemons
- HTTP: Apache2 and NGINX
- HTTP stats: Webalizer, GoAccess and AWStats
- Let's Encrypt: Acme.sh and certbot
- SMTP: Postfix
- POP3/IMAP: Dovecot
- Spamfilter: Rspamd and Amavis
- FTP: PureFTPD
- DNS: BIND9 and PowerDNS[^1]
- Database: MariaDB and MySQL

[^1]: not actively tested

## Supported operating systems
- Debian 9, 10, and testing
- Ubuntu 16.04 - 20.04
- CentOS 7 and 8

## Auto-install script
You can install the "Perfect Server" with ISPConfig using [our official autoinstaller](https://www.howtoforge.com/ispconfig-autoinstall-debian-ubuntu/)

## Migration tool
The Migration Tool helps you to import data from other control panels (currently ISPConfig 2 and 3 – 3.2, Plesk 10 – 12.5, Plesk Onyx, CPanel[^2] and Confixx 3). For more information, see https://www.ispconfig.org/add-ons/ispconfig-migration-tool/

[^2]: The Migration Toolkit now contains beta support for migrating CPanel to ISPConfig.

## Documentation
You can support ISPConfig development by buying the manual: https://www.ispconfig.org/documentation/

## Contributing
If you like to contribute to the ISPConfig development, please read the contributing guidelines: [CONTRIBUTING.MD](CONTRIBUTING.md)

