<?php
$host="localhost";
$user="root";
$password="MYSQL-ROOT-PASSWD";
mysql_connect($host, $user, $password) or die(mysql_error());

mysql_select_db("dbispconfig");
$sql1 = mysql_query("SELECT id, substr(origin,1, LENGTH(origin)-1) AS origin, substr(ns,1, LENGTH(ns)-1) AS ns, substr(mbox,1, LENGTH(mbox)-1) AS mbox,ttl FROM dns_soa order by id asc;");
mysql_select_db("powerdns");
while($row1 = mysql_fetch_array($sql1))
{
	mysql_query("INSERT INTO domains (id,name,type,ispconfig_id) values ('$row1[id]','$row1[origin]','NATIVE','$row1[id]');");
	mysql_query("INSERT INTO records (domain_id,name,content,ispconfig_id,type,ttl,prio,change_date) values ('$row1[id]','$row1[origin]','$row1[ns] $row1[mbox] 0','$row1[id]','SOA','$row1[ttl]','0','1260446221');");
}

mysql_select_db("dbispconfig");
$sql2 = mysql_query("SELECT id,zone,name,data,aux,ttl,type FROM dns_rr order by id asc;");
mysql_select_db("powerdns");
while($row2 = mysql_fetch_array($sql2))
{
	if (strlen($row2['name']))
	{
		$file1=substr($row2['data'], -1);
		if ($file1==".")
		{
			$text = $row2['data'];
			$laenge = strlen($row2['data'])-1;
			$file2 = substr($text, 0, strlen($text)-1);
		}
		else
		{
			$file2=$row2['data'];
		}

                //
                // Fix for 'domain.ext.' apex notation
                //
                $record_name_end=substr($row2['name'], -1);
                if ($record_name_end==".")
                {
                       // remove trailing dot from apex
                       $record_name = substr($row2['name'], 0, strlen($row2['name'])-1);
                }
                else
                {
                       // add domain to make it a fqdn
                       $record_name = $row2['name'] . "." . $row3['origin'];
                }

                print "$row2[name].$row3[origin]" . " $record_name\r\n"; 
		mysql_select_db("dbispconfig");
		$sql3 = mysql_query("SELECT substr(origin,1, LENGTH(origin)-1) AS origin FROM dns_soa where id=$row2[zone];");
		$row3 = mysql_fetch_array($sql3);
		mysql_select_db("powerdns");
		mysql_query("INSERT INTO records (domain_id,name,content,ispconfig_id,type,ttl,prio,change_date) values ('$row2[zone]','$record_name','$file2','$row2[id]','$row2[type]','$row2[ttl]','$row2[aux]','1260446221');");
	}
	else
	{
		$file1=substr($row2['data'], -1);
		if ($file1==".")
		{
			$text = $row2['data'];
			$laenge = strlen($row2['data'])-1;
			$file2 = substr($text, 0, strlen($text)-1);
		}
		else
		{
			$file2=$row2['data'];
		}
		mysql_select_db("dbispconfig");
		$sql3 = mysql_query("SELECT substr(origin,1, LENGTH(origin)-1) AS origin FROM dns_soa where id=$row2[zone];");
		$row3 = mysql_fetch_array($sql3);
		mysql_select_db("powerdns");
		mysql_query("INSERT INTO records (domain_id,name,content,ispconfig_id,type,ttl,prio,change_date) values ('$row2[zone]','$row3[origin]','$file2','$row2[id]','$row2[type]','$row2[ttl]','$row2[aux]','1260446221');");
	}
}

mysql_select_db("powerdns");
$sql4 = mysql_query("SELECT records.id,records.content,records.type,domains.name FROM records,domains where records.domain_id=domains.id and records.content NOT LIKE '%.%' and (records.type='CNAME' or records.type='NS' or records.type='MX') order by domain_id asc;");

while($row4 = mysql_fetch_array($sql4))
{
	mysql_query("UPDATE records SET content = '$row4[content].$row4[name]' where id='$row4[id]';");
}


?>
