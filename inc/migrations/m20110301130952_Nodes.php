<?php

class m20110301130952_Nodes extends BaseMigration
{
	public function up()
	{
		$this->query("
			CREATE TABLE IF NOT EXISTS `nodes` (
				`id` INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
				`path` VARCHAR(1024) NOT NULL,
				`name` VARCHAR(255) NOT NULL,
				`title` VARCHAR(255) NOT NULL,
				`type` VARCHAR(255) NOT NULL,
				`flags` INT(10) UNSIGNED NOT NULL,
				`position` INT(10) UNSIGNED NOT NULL,
				PRIMARY KEY (`id`),
				KEY `path` (`path`(255))
			) ENGINE=InnoDB DEFAULT CHARSET=utf8
		");
	}

	public function down()
	{
		$this->drop_table('nodes');
	}
}
