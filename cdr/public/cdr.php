<?php

/*
 * The cdr api interface
 * Link http://github.com/typefo/pbx-mon
 * By typefo <typefo@qq.com>
 */

/* load configure file */
require('../config.php');

try {
    /* get request data */
    $data = json_decode(file_get_contents('php://input'), true);
    
    if ($data) {
    	/* Variable processing */
        $req = isset($data['variables']) ? $data['variables'] : '';
        $uuid = isset($req['uuid']) ? $req['uuid'] : 'unknown';
        $src_ip = isset($req['sip_from_host']) ? ip2long($req['sip_from_host']) : 0;
        $dst_ip = isset($req['sip_dest_host']) ? ip2long($req['sip_dest_host']) : 0;
        $caller = isset($req['sip_from_user']) ? $req['sip_from_user'] : 'unknown';
        $called = isset($req['called']) ? $req['called'] : 'unknown';
        $duration = isset($req['billsec']) ? intval($req['billsec']) : 0;
        $file = date('Y/m/d/', intval($req['start_epoch'])) . $caller . '-' . $called . '-' . $uuid . '.wav';
        $create_time = isset($req['start_stamp']) ? urldecode($req['start_stamp']) : '1970-01-01 08:00:00';

        if ($duration > 0) {
        	/* Initialize mysql connection */
    		$db = new PDO('mysql:host=' . DB_HOST . ';port=' . DB_PORT . ';dbname=' . DB_NAME, DB_USER, DB_PASS);

            $db->query("INSERT INTO cdr(caller, called, duration, src_ip, rpf, file, create_time) values('$caller', '$called', $duration, $src_ip, $dst_ip, '$rpf', '$file', '$create_time')");

            /* Close mysql connection */
    	    $db = null;

    	    /* Check redis extension */
    	    if (!extension_loaded('redis')) {
    	    	error_log('Unable to find redis driver extension', 0);
    	    	exit(0);
    	    }

    	    /* initialize redis connection */
    	    $redis = new Redis(REDIS_HOST, REDIS_PORT);
    	    if (REDIS_PASS) {
    	    	$redis->auth(REDIS_PASS);
    	    }

    	    $key = date('Ymd');
    	    $redis->hIncrBy('server.' . $key . '.' . $src_ip, 'in', 1);
    	    $redis->hIncrBy('server.' . $key . '.' . $dst_ip, 'out', 1);
    	    $redis->hIncrBy('server.' . $key . '.' . $dst_ip, 'duration', $duration);

    	    /* Close redis connection */
    	    $redis->close();
        }
    } else {
        error_log('Cannot parse requests appliation/json data', 0);
    }

} catch (PDOException $e) {
    error_log($e->getMessage(), 0);
}
