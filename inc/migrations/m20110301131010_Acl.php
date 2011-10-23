<?php

class m20110301131010_Acl extends BaseMigration
{
	public function up()
	{
		$this->query("
			CREATE TABLE IF NOT EXISTS `acl` (
				`id` INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
				`node_id` INT(10) UNSIGNED NOT NULL,
				`action` VARCHAR(255) NOT NULL,
				`ident` VARCHAR(255) NOT NULL,
				PRIMARY KEY (`id`),
				KEY `node_id` (`node_id`)
			) ENGINE=InnoDB DEFAULT CHARSET=utf8
		");
	}

	public function down()
	{
		$this->drop_table('acl');
	}
}
