<?php

require_once('s/s.php');
require_once(BASE . 'inc/core/core.php');

Request::process(_SERVER('REQUEST_URI'));

if (LOG_DEBUG_INFO) {
	$debuglog_str = dflush_str();
	_log("[[ Info ]]\n\n$debuglog_str\n\n");
}
