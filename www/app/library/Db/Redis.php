<?php

/*
 * The Redis driver
 * Link http://github.com/typefo/pbx-mon
 * By typefo <typefo@qq.com>
 */

namespace Db;

class Redis {
	public $handle = null;
	public $host = '127.0.0.1';
	public $port = 6379;
	public $password = null;
	public $database = 0;

	public function __construct($host = '127.0.0.1', $port = 6379, $password = null, $database = 0) {
        	/* Check redis extension */
    	    if (!extension_loaded('redis')) {
    	    	error_log('Unable to find redis driver extension', 0);
    	    	return $handle;
    	    }

    	    $this->host = $host;
    	    $this->port = $port;
    	    $this->password = $password;
    	    $this->database = $db;

    	    $this->handle = new \Redis($host, $port);
    	    if ($password) {
    	    	$this->handle->auth($password);
    	    }

    	    $this->handle->select($database);
    	    return $this->handle;
    }
}

