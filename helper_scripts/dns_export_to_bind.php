<?php
$host="IP-ADRESS";
$user="root";
$password="PASSWORD";
mysql_connect($host,$user,$password) or die(mysql_error());
mysql_select_db("dbispconfig");
$result = "";
$result = mysql_query("SELECT id,origin,ns,ttl,mbox,serial,refresh,retry,expire,minimum FROM dns_soa;");
exec ("rm -f /etc/bind/named.conf.local");

$fx = fopen("/etc/bind/named.conf.local", "a+");

function hostname2ipfunktion($tmp1, $timeout = -1) {
  if ($tmp1 == 0) {
  $query = `nslookup -timeout=$timeout -retry=0 $tmp1`;
  if(preg_match('/\nAddress: (.*)\n/', $query, $matches))
     return trim($matches[1]);
  return $tmp1;
}
}

while($row = mysql_fetch_array($result))
{
### Hier ALLES Aktivieren bei Primary Nameserver ########################################################################################
$tmp1 = substr($row["origin"],0,-1);
fwrite($fx,"zone \"");
fwrite($fx,substr($row["origin"],0,-1));
fwrite($fx,"\" in { type master; file \"");
fwrite($fx,substr($row["origin"],0,-1));
fwrite($fx,"\"; };\n");
$result2 = mysql_query("select name,type,aux,data from dns_rr where zone=$row[id] ORDER BY name ASC;");
exec("rm -f /var/cache/bind/$tmp1");
$f = fopen("/var/cache/bind/$tmp1", "a+");
fwrite($f,"\$TTL ");
fwrite($f,$row['ttl']);
fwrite($f,"\n");
fwrite($f,"@ IN SOA ");
fwrite($f,$row['ns']);
fwrite($f," ");
fwrite($f,$row['mbox']);
fwrite($f," (");
fwrite($f,"\n");
fwrite($f,"            ");
fwrite($f,$row['serial']);
fwrite($f," ;Serial");
fwrite($f,"\n");
fwrite($f,"            ");
fwrite($f,$row['refresh']);
fwrite($f," ;Refresh");
fwrite($f,"\n");
fwrite($f,"            ");
fwrite($f,$row['retry']);
fwrite($f," ;Retry");
fwrite($f,"\n");
fwrite($f,"            ");
fwrite($f,$row['expire']);
fwrite($f," ;Expire");
fwrite($f,"\n");
fwrite($f,"            ");
fwrite($f,$row['minimum']);
fwrite($f," )");
fwrite($f," ;Minimum");
fwrite($f,"\n");
fwrite($f,"\n");
while($row2 = mysql_fetch_row($result2))
{
fwrite($f,$row2['0']);
fwrite($f," IN ");
fwrite($f,$row2['1']);
fwrite($f," ");
if ($row2['2']>0)
{
fwrite($f,$row2['2']);
fwrite($f," ");
}
fwrite($f,$row2['3']);
fwrite($f,"\n");
}
fclose($f);
### ENDE Primärer Namerserver ###########################################################################################################

### Hier ALLES Aktivieren bei Secondary Nameserver ######################################################################################
#$tmp1 = substr($row["ns"],0,-1);
#$tmp2 = substr($row["origin"],0,-1);
#$nsip = hostname2ipfunktion($tmp1);
#if ($nsip == $tmp1) {
#echo "$tmp2 $tmp1 Not a valid Nameserver";
#echo "\n";
#}
#else {
#fwrite($fx,"zone \"");
#fwrite($fx,substr($row["origin"],0,-1));
#fwrite($fx,"\" in { type slave; file \"");
#fwrite($fx,substr($row["origin"],0,-1));
#fwrite($fx,"\"; masters {");
#fwrite($fx,"$nsip; }; };");
#fwrite($fx,"\n");
#}
### ENDE Secondary Nameserver ###########################################################################################################
}
fclose($fx);
exec("/etc/init.d/bind9 reload");
?>