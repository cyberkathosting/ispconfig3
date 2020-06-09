<?php

/**
 * If your websites use PHP-FPM and you have incron installed, you can use this plugin to automatically add incron
 * configuration which will take care of reloading the php-fpm pool when the file /private/php-fpm.reload is touched.
 * Projects which use deployment tools can use this to reload php-fpm to clear the opcache at deploy time, without
 * requiring superuser privileges.
 *
 * The plugin is prefixed with `z_` because plugins are executed alphabetically, and this plugin
 * must only run after apache2/nginx plugins so we are sure the directories and user/group exist.
 */
class z_php_fpm_incron_reload_plugin {

	var $plugin_name = 'z_php_fpm_incron_reload_plugin';
	var $class_name = 'z_php_fpm_incron_reload_plugin';

	function onInstall() {
		global $conf;

		return $conf['services']['web'] == true;
	}

	function onLoad() {
		global $app;

		if ($this->isPluginEnabled() === false) {
			return;
		}

		if ($this->isIncronAvailable() === false) {
			$app->log('You must install incron in order to use this plugin', LOGLEVEL_DEBUG);

			return;
		}

		$app->plugins->registerEvent('web_domain_insert', $this->plugin_name, 'incronInsert');
		$app->plugins->registerEvent('web_domain_update', $this->plugin_name, 'incronUpdate');
		$app->plugins->registerEvent('web_domain_delete', $this->plugin_name, 'incronDelete');
	}

	function incronInsert($eventName, $data) {
		$this->setup($data['new']);
	}

	function incronUpdate($eventName, $data) {
		global $app;

		if ($this->documentRootUnchanged($data) && $this->phpVersionUnchanged($data)) {
			$app->log('Document root and PHP version unchanged. Not updating incron configuration.', LOGLEVEL_DEBUG);

			return;
		}

		$this->teardown($data['old']);
		$this->setup($data['new']);
	}

	function incronDelete($eventName, $data) {
		$this->teardown($data['old']);
	}

	private function documentRootUnchanged($data)
	{
		return $data['new']['document_root'] === $data['old']['document_root'];
	}

	private function phpVersionUnchanged($data)
	{
		return $data['new']['fastcgi_php_version'] === $data['old']['fastcgi_php_version'];
	}

	private function setup($data)
	{
		$triggerFile = $this->getTriggerFilePath($data['document_root']);

		$this->createTriggerFile($triggerFile, $data['system_user'], $data['system_group']);
		$this->createIncronConfiguration(
			$triggerFile,
			$data['system_user'],
			$data['fastcgi_php_version']
		);

		$this->restartIncronService();
	}

	private function teardown($data) {
		$this->deleteIncronConfiguration($data['system_user']);
		$this->deleteTriggerFile($this->getTriggerFilePath($data['document_root']));

		$file = sprintf('/etc/incron.d/%s.conf', $data['system_user']);

		@unlink($file);

		$this->restartIncronService();
	}

	private function isIncronAvailable() {
		exec('which incrond', $output, $retval);

		return $retval === 0;
	}

	private function isPluginEnabled() {
		global $app, $conf;

		$app->uses('getconf');
		$serverConfig = $app->getconf->get_server_config($conf['server_id'], 'web');

		return $serverConfig['php_fpm_incron_reload'] === 'y';
	}

	private function createIncronConfiguration($triggerFile, $systemUser, $fastcgiPhpVersion) {
		global $app;

		$phpService = $this->getPhpService($fastcgiPhpVersion);
		$configFile = $this->getIncronConfigurationFilePath($systemUser);

		$content = sprintf(
			'%s %s %s',
			$triggerFile,
			'IN_CLOSE_WRITE',
			$app->system->getinitcommand($phpService, 'reload')
		);

		file_put_contents($configFile, $content);

		$app->log(sprintf('Created incron configuration "%s"', $configFile), LOGLEVEL_DEBUG);
	}

	private function createTriggerFile($triggerFile, $systemUser, $systemGroup) {
		global $app;

		if (!file_exists($triggerFile)) {
			exec(sprintf('touch %s', $triggerFile));
		}

		exec(sprintf('chown %s:%s %s', $systemUser, $systemGroup, $triggerFile));

		$app->log(sprintf('Ensured incron trigger file "%s"', $triggerFile), LOGLEVEL_DEBUG);
	}

	private function deleteIncronConfiguration($systemUser) {
		global $app;

		$configFile = $this->getIncronConfigurationFilePath($systemUser);
		if (!file_exists($configFile)) {
			return;
		}

		unlink($configFile);

		$app->log(sprintf('Deleted incron configuration "%s"', $configFile), LOGLEVEL_DEBUG);
	}

	private function deleteTriggerFile($triggerFile) {
		global $app;

		if (!file_exists($triggerFile)) {
			return;
		}

		unlink($triggerFile);

		$app->log(sprintf('Deleted incron trigger file "%s"', $triggerFile), LOGLEVEL_DEBUG);
	}

	private function getTriggerFilePath($documentRoot) {
		return sprintf('%s/private/php-fpm.reload', $documentRoot);
	}

	private function getIncronConfigurationFilePath($systemUser) {
		return sprintf('/etc/incron.d/%s.conf', $systemUser);
	}

	private function getPhpService($fastcgiPhpVersion) {
		$phpInfo = explode(':', $fastcgiPhpVersion);
		if (empty($phpInfo)) {
			return null;
		}

		$phpService = $phpInfo[1];
		if (empty($phpService)) {
			return null;
		}

		return $phpService;
	}

	private function restartIncronService() {
		global $app;

		$serviceName = 'incrond';
		if (file_exists('/etc/debian_version')) {
			$serviceName = 'incron';
		}

		exec($app->system->getinitcommand($serviceName, 'restart'));
	}
}