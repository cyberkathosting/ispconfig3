ALTER TABLE `sys_config` DROP `config_id`,
	ADD PRIMARY KEY (`group`, `name`);
