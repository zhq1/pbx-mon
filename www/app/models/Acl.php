<?php

/*
 * The Acl Model
 * Link http://github.com/typefo/pbx-mon
 * By typefo <typefo@qq.com>
 */

use Db\Redis;
use Tool\Filter;

class AclModel {
    public $redis = null;
    public $config = null;

    public function __construct() {
        $this->config = Yaf\Registry::get('config');

        if ($this->config) {
            $config = $this->config->redis;
            $redis = new Redis($config->host, $config->port, $config->password, $config->db);
            $this->redis = $redis->handle;
        }
        
    }

    public function check($ip = null) {
        $ip = Filter::ip($ip, null);
        if ($ip != null) {
            return $this->redis->exists('whitelist.' . ip2long($ip));
        }

        return false;
    }

    public function getAll() {
        $reply = array();
        $list = $this->redis->keys('whitelist.*');
        foreach ($list as $val) {
            $ip = explode('.', $val)[1];
            $obj['ip'] = long2ip($ip);
            $obj['create_time'] = date('Y-m-d H:i:s', intval($this->redis->get('whitelist.' . $ip)));
            $reply[] = $obj;
        }

        return $reply;
    }

    public function add($ip) {
        $ip = Filter::ip($ip, null);
        if ($ip != null) {
            $this->redis->set('whitelist.' . ip2long($ip), time());
            return true;
        }

        return false;
    }

    public function delete($ip) {
        $ip = Filter::ip($ip, null);
        if ($ip != null) {
            $this->redis->delete('whitelist.' . ip2long($ip));
            return true;
        }

        return false;
    }
}
