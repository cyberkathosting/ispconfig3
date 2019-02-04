<?php

/**
 * If your websites use PHP-FPM and you have incron installed, you can use this plugin to automatically add incron
 * configuration which will take care of reloading the php-fpm pool when the file /private/php-fpm.reload is touched.
 * Projects which use deployment tools can use this to reload php-fpm to clear the opcache at deploy time, without
 * requiring superuser privileges.
 */
class incron_plugin {

	var $plugin_name = 'incron_plugin';
	var $class_name = 'incron_plugin';

	function onInstall() {
		global $conf;

		if ($conf['services']['web'] !== true) {
			return false;
		}

		if ($this->isIncronAvailable() === false) {
			return false;
		}

		return true;
	}

	function onLoad() {
		global $app;

		$app->plugins->registerEvent('web_domain_insert', $this->plugin_name, 'incronInsert');
		$app->plugins->registerEvent('web_domain_update', $this->plugin_name, 'incronUpdate');
		$app->plugins->registerEvent('web_domain_delete', $this->plugin_name, 'incronDelete');
	}

	function incronInsert($eventName, $data) {
		$this->setup($data['new']);
	}

	function incronUpdate($eventName, $data) {
		global $app;

		if ($data['new']['document_root'] === $data['old']['document_root']) {
			$app->log('Document root unchanged. Not updating incron configuration.', LOGLEVEL_DEBUG);

			return;
		}

		$this->teardown($data['old']);
		$this->setup($data['new']);
	}

	function incronDelete($eventName, $data) {
		$this->teardown($data['old']);
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
		exec(sprintf('chattr +i %s', $triggerFile));

		$app->log(sprintf('Ensured incron trigger file "%s"', $triggerFile), LOGLEVEL_DEBUG);
	}

	private function deleteIncronConfiguration($systemUser) {
		global $app;

		$configFile = $this->getIncronConfigurationFilePath($systemUser);
		unlink($configFile);

		$app->log(sprintf('Deleted incron configuration "%s"', $configFile), LOGLEVEL_DEBUG);
	}

	private function deleteTriggerFile($triggerFile) {
		global $app;

		exec(sprintf('chattr -i %s', $triggerFile));
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

		exec($app->system->getinitcommand('incrond', 'restart'));
	}
}