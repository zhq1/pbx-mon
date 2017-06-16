<?php

/*
 * The Redis driver
 * Link http://github.com/typefo/pbx-mon
 * By typefo <typefo@qq.com>
 */

namespace Db;

class Redis {
	public $conn = null;
	public $database = 0;

	public function __construct($host = '127.0.0.1', $port = 6379, $password = null, $db = 0) {
        	/* Check redis extension */
    	    if (!extension_loaded('redis')) {
    	    	error_log('Unable to find redis driver extension', 0);
    	    	return $conn;
    	    }
    }
}

