#!/bin/bash
set -x
echo "" > /etc/resolv.conf
echo "" > /etc/hostname
echo "" > /etc/mailname
rm -f /etc/ssh/ssh_host_*
cat << EOF > /etc/rc2.d/S15ssh_gen_host_keys
#!/bin/bash
ssh-keygen -f /etc/ssh/ssh_host_rsa_key -t rsa -N ''
ssh-keygen -f /etc/ssh/ssh_host_dsa_key -t dsa -N ''
dpkg-reconfigure -u webalizer
postconf -e "myhostname =  $(hostname -f)"
postconf -e "mydestination =  $(hostname -f), localhost"
echo $(hostname -f) > /etc/mailname
dpkg-reconfigure -u postfix
rm -f \$0
EOF
chmod a+x /etc/rc2.d/S15ssh_gen_host_keys
