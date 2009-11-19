<?php
$host="IP-Adresse-ISP-CONFIG-Master-Server";
$user="MYSQL-USER";
$password="PASSWORD";
mysql_connect($host,$user,$password) or die(mysql_error());
mysql_select_db("dbispconfig");
$result = "";
$result = mysql_query("SELECT id,origin,ns,ttl,mbox,serial,refresh,retry,expire,minimum FROM dns_soa;");
function hostname2ipfunktion($tmp1, $timeout = 1)
       {
               if ($tmp1 == 0)
               {
               $query = `nslookup -timeout=$timeout -retry=0 $tmp1`;
               if(preg_match('/\nAddress: (.*)\n/', $query, $matches))
               return trim($matches[1]);
               return $tmp1;
               }
       }

while($row = mysql_fetch_array($result))
       {
### Hier ALLES Aktivieren bei Primary Nameserver ########################################################################################
       $varx11=substr($row["origin"],0,-1);
       unlink("/var/cache/bind/$varx11");
       $arr1[$x11]="zone \"$varx11\" in { type master; file \"$varx11\"; };\n";
       $x11=$x11+1;
       $result2 = mysql_query("select name,type,aux,data from dns_rr where zone=$row[id] ORDER BY name ASC;");
       $arr3[0]="\$TTL ".$row['ttl']."\n@ IN SOA ".$row['ns']." ".$row['mbox']." (\n           ".$row['serial']." ;Serial\n"."         ".$row['refresh']." ;Refresh\n"."               ".$row['retry']." ;Retry\n"."           ".$row['expire']." ;Expire\n"."         ".$row['minimum']." ) ;Minimum\n\n";

               $xx1=1;
               while($row2 = mysql_fetch_row($result2))
               {
               $arr2[$xx1]=$row2['0']." IN ".$row2['1']." ";

                       if ($row2['2']>0)
                       {
                       $arr3[$xx1]=$arr2[$xx1].$row2['2']." ".$row2['3']."\n";
                       }
                       else
                       {
                       $arr3[$xx1]=$arr2[$xx1].$row2['3']."\n";
                       }
               $xx1=$xx1+1;
               }
       $f = fopen("/var/cache/bind/$varx11", "a+");
       foreach($arr3 as $values) fputs($f, $values);
       fclose($f);
       $arr2=array();
       $arr3=array();
### ENDE Primärer Nameserver ###########################################################################################################

### Hier ALLES Aktivieren bei Secondary Nameserver ######################################################################################
#       $tmp1 = substr($row["ns"],0,-1);
#       $tmp2 = substr($row["origin"],0,-1);
#       $nsip = hostname2ipfunktion($tmp1);
#               if ($nsip == $tmp1) #               {
#               echo "$tmp2 $tmp1 Not a valid Nameserver";
#               echo "\n";
#               }
#               else #               {
#               $arr1[$x11]="zone \"".$tmp2."\" in { type slave; file \"".$tmp2."\"; masters {".$nsip."; }; };\n";
#               $x11=$x11+1;
#               }
### ENDE Secondary Nameserver ###########################################################################################################
       }

unlink ("/etc/bind/named.conf.local");
$fx = fopen("/etc/bind/named.conf.local", "a+");
foreach($arr1 as $values) fputs($fx, $values);
fclose($fx);
exec("/etc/init.d/bind9 reload");
?>