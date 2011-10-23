<?php

class m20110301131007_Attributes extends BaseMigration
{
	public function up()
	{
		$this->query("
			CREATE TABLE IF NOT EXISTS `attributes` (
				`id` INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
				`node_id` INT(10) UNSIGNED NOT NULL,
				`lang` VARCHAR(32) NOT NULL,
				`name` VARCHAR(255) NOT NULL,
				`value` TEXT NOT NULL,
				PRIMARY KEY (`id`),
				KEY `node_id` (`node_id`)
			) ENGINE=InnoDB DEFAULT CHARSET=utf8
		");
	}

	public function down()
	{
		$this->drop_table('attributes');
	}
}
