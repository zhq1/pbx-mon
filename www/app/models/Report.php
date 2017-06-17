<?php

/*
 * The Report Model
 * Link http://github.com/typefo/pbx-mon
 * By typefo <typefo@qq.com>
 */

use Db\Redis;
use Tool\Filter;


class ReportModel {
    public $db   = null;
    public $redis = null;
    public $config = null;
    public $prefix = 'server';
    public $column = ['in', 'out', 'duration'];

    public function __construct() {
        $this->db = Yaf\Registry::get('db');
        $this->config = Yaf\Registry::get('config');

        if ($this->config) {
            $config = $this->config->redis;
            $redis = new Redis($config->host, $config->port, $config->password, $config->db);
            $this->redis = $redis->handle;
        }
        
    }

    public function get($server = null, $date = null) {
        $ip = ip2long($server);
        $reply = array();
        if (($ip !== false) && $this->redis) {
            if ($data != null) {
                $prefix = $this->prefix . '.' . $date . '.';
                $reply = $this->redis->hMGet($prefix . $ip, $this->column);
                var_dump($reply);
                exit;
            }
            
        }

        return $reply;
    }

    public function getAll($date = null) {
        $result = array();
        if ($this->db && $this->redis) {
            $server = new ServerModel();
            $res = $server->getAll();
            if (count($res) > 0) {
                $date = $date ? $date : date('Ymd');
                foreach ($res as $r) {
                    $result[$r['id']] = array_merge($r, $this->get($r['ip'], $date));
                }
            } 
        }

        return $result;
    }
    
}
