<?php

conf_set('sitename', '<Site Name>');
conf_set('domain', 'domain.tld');
conf_set('http.root', '/');

conf_set('db.type', 'mysql');
conf_set('db.host', 'localhost');
conf_set('db.name', '<Database Name>');
conf_set('db.user', '<Database User>');
conf_set('db.pass', '<Database Password>');

conf_set('mail.send', true);

if (!defined('DISABLE_AUTOLOAD'))
{
	conf_set('modules.autoload', array(
		'db',
		'web',
		'data/json',
		'data/date'
	));
}

conf_set('cms.salt', '<Salt>');
conf_set('cms.lang', 'en'); // "en" or "ru"

conf_set('log_errors', true);

conf_set('debug', true);
conf_set('show_debug_info', false);
// conf_set('log_debug_info', true);

function set_utf8_connection($db)
{
	$cmd = new SDBCommand('SET NAMES utf8', $db);
	$cmd->execute();
}

conf_set('db.init_hook', 'set_utf8_connection');

if (is_readable(dirname(__FILE__) . '/local_config.php')) {
	require_once(dirname(__FILE__) . '/local_config.php');
}
