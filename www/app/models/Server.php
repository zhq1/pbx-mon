<?php

/*
 * The Server Model
 * Link http://github.com/typefo/pbx-mon
 * By typefo <typefo@qq.com>
 */

use Tool\Filter;

class ServerModel {

    public $db = null;
    public $config = null;
    private $table = 'server';
    private $column = ['name', 'ip', 'port', 'call', 'route', 'description'];

    public function __construct() {
        $this->db = Yaf\Registry::get('db');
        $this->config = Yaf\Registry::get('config');
    }

    public function get($id = null) {
        $id = intval($id);
        if ($id > 0 && $this->db) {
            $sql = 'SELECT * FROM ' . $this->table . ' WHERE id = :id LIMIT 1';
            $sth = $this->db->prepare($sql);
            $sth->bindParam(':id', $id, PDO::PARAM_INT);
            $sth->execute();
            return $sth->fetch();
        }

        return null;
    }

    public function getAll() {
        $result = array();
        if ($this->db) {
            $sql = 'SELECT * FROM ' . $this->table . ' ORDER BY id';
            $result = $this->db->query($sql)->fetchAll();
        }

        return $result;
    }
    
    public function change($id = null, array $data = null) {
        $id = intval($id);
        $data = $this->checkArgs($data);
        $column = $this->keyAssembly($data);

        if ($id > 0 && count($data) > 0) {
            $sql = 'UPDATE ' . $this->table . ' SET ' . $column . ' WHERE id = :id';
            $sth = $this->db->prepare($sql);
            $sth->bindParam(':id', $id, PDO::PARAM_INT);

            foreach ($data as $key => $val) {
                $sth->bindParam(':' . $key, $val, is_int($val) ? PDO::PARAM_INT : PDO::PARAM_STR);
            }

            if ($sth->execute()) {
                if (isset($data['call'])) {
                    $system = new SystemModel();
                    $system->regenAcl();
                    $system->reloadAcl();
                }

                return true;
            }
        }

        return false;
    }

    public function delete($id = null) {
        $id = intval($id);
        $result = $this->get($id);

        if (count($result) > 0) {
            $sql = 'DELETE FROM ' . $this->table . ' WHERE id = ' . $id;
            $this->db->query($sql);

            if ($result['call'] == 1) {
                $system = new SystemModel();
                $system->regenAcl();
                $system->reloadAcl();
            }
            return true;
        }

        return false;
    }

    public function create(array $data = null) {
    	$count = count($this->column);
        $data = $this->checkArgs($data);
        
        if (!isset($data['call'])) {
            $data['call'] = 0;
        }

        if ((count($data) == $count) && (!in_array(null, $data, true))) {
        	$sql = 'INSERT INTO ' . $this->table . '(`name`, `ip`, `port`, `call`, `route`, `description`) VALUES(:name, :ip, :port, :call, :route, :description)';
        	$sth = $this->db->prepare($sql);

        	foreach ($data as $key => $val) {
        		$sth->bindParam(':' . $key, $val, is_int($val) ? PDO::PARAM_INT : PDO::PARAM_STR);
        	}

        	if ($sth->execute()) {
                if ($data['call'] == 1) {
                    $system = new SystemModel();
                    $system->regenAcl();
                    $system->reloadAcl();
                }
                return true;
            }
        }

        return false;
    }

    public function isExist($id = null) {
        $id = intval($id);
        if ($id > 0 && $this->db) {
            $sql = 'SELECT id FROM ' . $this->table . ' WHERE id = ' . $id . ' LIMIT 1';
            $result = $this->db->query($sql);
            if (count($result) > 0) {
                return true;
            }
        }

        return false;
    }

    public function checkArgs(array $data) {
    	$res = array();
        $data = array_intersect_key($data, array_flip($this->column));

        foreach ($data as $key => $val) {
            switch ($key) {
            case 'name':
               	$res['name'] = Filter::alpha($val, null, 1, 32);
               	break;
            case 'ip':
               	$res['ip'] = Filter::ip($val, null);
               	break;
            case 'port':
               	$res['port'] = Filter::port($val, null, 5060);
               	break;
            case 'call':
                $res['call'] = ($val == 'on') ? 1 : 0;
               	break;
            case 'route':
                $rid = intval($val);
                $route = new RouteModel();
                $res['route'] = $route->isExist($rid) ? $rid : null;
               	break;
            case 'description':
               	$res['description'] = Filter::string($val, 'no description', 1, 64);
              	break;
            }
        }

        return $res;
    }

    public function keyAssembly(array $data) {
    	$text = '';
        $append = false;
        foreach ($data as $key => $val) {
            if ($val != null) {
                if ($text != '' && $append) {
                    $text .= ", `$key` = :$key";
                } else {
                    $append = true;
                    $text .= "`$key` = :$key";
                }
            }
        }

        return $text;
    }
}
