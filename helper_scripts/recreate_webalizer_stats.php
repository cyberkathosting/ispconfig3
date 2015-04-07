<?php

//######################################################################################################
// Re-Create webalizer statistics
//######################################################################################################


$sql = "SELECT domain_id, domain, document_root FROM web_domain WHERE server_id = ?";
$records = $app->db->queryAllRecords($sql, $conf["server_id"]);
foreach($records as $rec) {
	$domain = escapeshellcmd($rec["domain"]);
	$logdir = escapeshellcmd($rec["document_root"].'/log');
	$statsdir = escapeshellcmd($rec["document_root"].'/web/stats');
	$webalizer = '/usr/bin/webalizer';

	$webalizer_conf_main = '/etc/webalizer/webalizer.conf';
	$webalizer_conf = escapeshellcmd($rec["document_root"].'/log/webalizer.conf');
	exec("rm -rf $webalizer_conf");
	if(!@is_file($webalizer_conf)) {
		exec("cp $webalizer_conf_main $webalizer_conf");

		setConfigVar($webalizer_conf, 'Incremental', 'yes');
		setConfigVar($webalizer_conf, 'IncrementalName', $logdir.'/webalizer.current');
		setConfigVar($webalizer_conf, 'HistoryName', $logdir.'/webalizer.hist');
	}

	if(!@is_dir($statsdir)) mkdir($statsdir);


	echo "Remove stats dir $statsdir ...\n";
	exec("rm -rf $statsdir/*");

	echo "Re-Create stats for $domain...\n";
	exec("for logfile in $logdir/*access*; do\n$webalizer -c $webalizer_conf -n $domain -s $domain -r $domain -q -T -p -o $statsdir ".'$logfile'."\ndone");
	echo "done.\n";
}

die("finished.\n");
?>
