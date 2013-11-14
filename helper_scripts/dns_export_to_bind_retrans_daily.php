<?php
$host="IP_ADDRESS";
$user="USERNAME";
$password="PASSWORD";
mysql_connect($host, $user, $password) or die(mysql_error());
mysql_select_db("dbispconfig");
$result = "";
$result = mysql_query("SELECT origin FROM dns_soa ORDER BY origin ASC;");
while($row = mysql_fetch_array($result))
{
	$zone=substr($row["origin"], 0, -1);
	system("rndc retransfer ".$zone);
}
?>
