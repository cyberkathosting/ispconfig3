<?php

class iptables_plugin
{
	var $plugin_name = 'iptables_plugin';
	var $class_name  = 'iptables_plugin';

	function onInstall()
	{
		global $conf;
		/*
  if($conf['iptables']['installed'] = true) return true;
  else return false;
  */
		return false;
	}

	function onLoad()
	{
		global $app;
		$app->plugins->registerEvent('iptables_insert', $this->plugin_name, 'insert');
		$app->plugins->registerEvent('iptables_update', $this->plugin_name, 'update');
		$app->plugins->registerEvent('iptables_delete', $this->plugin_name, 'delete');
	}

	function insert($event_name, $data)
	{
		global $app, $conf;
		$this->update($event_name, $data);
	}

	function update($event_name, $data)
	{
		global $app, $conf;
		/*
ok, here is where we do some fun stuff.  First off we need to see the currently
running iptables (sans the fail2ban) and compare with the database.  This is
the method that is good for multi servers and keeping the firewall read only so
a comromised box will not corrupt the master server.

If the running iptables and the new iptables don't match, lets send a note to
the monitoring data to say that there is a difference.  Maybe we can have the
iptables gui inteface check the data field for changes and post a warning and
or the changes as disabled rules.  If an admin adds a rule on the comand line
we should make it easy to add to the database, but hard to overwrite the data.

1.
So first is a reading of the current rules by filter:table with our friend awk

2.
Compare with database

3.
Send notices or updates

4.
Apply rules from database

5.
Preform some type of sainity check like the apache restart script

6.
Profit

# automate this with a loop, but here it is for santity sake.
exec('iptables -S INPUT');
exec('iptables -S OUTPUT');
exec('iptables -S FORWARD');

$data['new'] should have lots of fun stuff
exec('iptables -I XYZ');
*/
	}

	function delete($event_name, $data)
	{
		global $app, $conf;
		exec('iptables -D xyz');
	}

}

?>
