++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++

If mails get transparently forwarded to another mailserver, a mechanism to block
mail for invalid recipients makes sense, and drastically increaes the well-known
backscatter problem.

LDAP queries are used to check for valid recipients, and forwards the mail, if
an entry for the user is found.

For this to work, on Debian/GNU Linux, you also have to install postfix-ldap by

apt install postfix-ldap


Further information can be found @ https://blog.nwsec.de/wordpress/?p=1031

++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++