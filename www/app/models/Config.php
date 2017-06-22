<?php

/*
 * The Config Model
 * Link http://github.com/typefo/pbx-mon
 * By typefo <typefo@qq.com>
 */

use Db\Redis;

class ConfigModel {
    public $redis = null;
    public $config = null;
    public $option = ['record', 'blacklist', 'multiple'];

    public function __construct() {
        $this->config = Yaf\Registry::get('config');

        if ($this->config) {
            $config = $this->config->redis;
            $redis = new Redis($config->host, $config->port, $config->password, $config->db);
            $this->redis = $redis->handle;
        }
        
    }

    public function get($key = null) {
        $reply = null;

        if ($key == null || !in_array($key, $this->option, true)) {
            return $reply;
        }

        $reply = $this->redis->get('config.' . $key);

        return ($reply != false) ? $reply : null;
    }

    public function getAll() {
        $reply = $this->column;

        foreach ($this->column as $val) {
            $reply[$val] = $this->get($val);
        }

        return $reply;
    }

    public function set($key = null, $val = null) {
        if ($key == null || $val == null) {
            return false;
        }

        if (in_array($key, $this->column, true)) {
            return $this->redis->set($key, $val);
        }

        return false;
    }
}
