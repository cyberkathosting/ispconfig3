<?php

/**
 * When installed, this plugin will create a relative symlink from $HOME/website to /web when:
 * - domain is created or updated
 * - shell user is created or updated
 *
 * Its purpose is to make it easier for users to navigate to their web folder. If a file named website/ already exists
 * it is not overwritten.
 *
 * Example:
 *
 * $ ls -al /var/www/domain.com/home/username
 * total 12
 * drwxr-x--- 3 web1    client1    4096 Nov  4 22:19 .
 * drwxr-xr-x 4 root    root       4096 Nov  4 22:19 ..
 * lrwxrwxrwx 1 root    root          9 Nov  4 22:19 website -> ../../web
 */
class website_symlink_plugin {

	var $plugin_name = 'website_symlink_plugin';
	var $class_name = 'website_symlink_plugin';

	public function onInstall() {
		global $conf;

		// Enable the following code section to activate the plugin automatically at install time
		/*
		if ($conf['services']['web'] == true) {
			return true;
		}
		*/

		return false;
	}

	public function onLoad() {
		global $app;

		$app->plugins->registerEvent('web_domain_insert', $this->plugin_name, 'createSymlinkForWebDomain');
		$app->plugins->registerEvent('web_domain_update', $this->plugin_name, 'createSymlinkForWebDomain');

		$app->plugins->registerEvent('shell_user_insert', $this->plugin_name, 'createSymlinkForShellUser');
		$app->plugins->registerEvent('shell_user_update', $this->plugin_name, 'createSymlinkForShellUser');
	}

	public function createSymlinkForWebDomain($event_name, $data) {
		$homeDirectories = glob(sprintf('%s/home', $data['new']['document_root']) . '/*', GLOB_ONLYDIR);

		foreach ($homeDirectories as $dir) {
			$target = sprintf('%s/web', $data['new']['document_root']);
			$link = sprintf('%s/website', $dir);

			$this->createSymlink($target, $link);
		}
	}

	public function createSymlinkForShellUser($event_name, $data) {
		$target = sprintf('%s/web', $data['new']['dir']);
		$link = sprintf('%s/home/%s/website', $data['new']['dir'], $data['new']['username']);

		$this->createSymlink($target, $link);
	}

	private function createSymlink($target, $link) {
		global $app;

		if (file_exists($link)) {
			$app->log(sprintf('Not creating symlink because "%s" already exists', $link), LOGLEVEL_DEBUG);

			return;
		}

		if ($app->system->create_relative_link($target, $link)) {
			$app->log(sprintf('Created symlink from "%s" to "%s"', $link, $target), LOGLEVEL_DEBUG);
		} else {
			$app->log(sprintf('Failed to create symlink from "%s" to "%s"', $link, $target), LOGLEVEL_WARN);
		}
	}
}