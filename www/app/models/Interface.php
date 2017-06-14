<?php

/*
 * The Interface Model
 * Link http://github.com/typefo/pbx-mon
 * By typefo <typefo@qq.com>
 */

use Tool\Filter;

class InterfaceModel {

    public $db = null;
    public $config = null;
    private $table = 'interface';
    private $column = ['name', 'ip', 'port', 'in_code', 'out_code', 'description'];
    

    public function __construct() {
        $this->db = Yaf\Registry::get('db');
        $this->config = Yaf\Registry::get('config');
    }

    public function get($id = null) {
        $id = intval($id);
        if ($id > 0 && $this->db) {
            $sql = 'SELECT * FROM `' . $this->table . '` WHERE id = :id LIMIT 1';
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
            $sql = 'SELECT * FROM `' . $this->table . '` ORDER BY id';
            $result = $this->db->query($sql)->fetchAll();
        }

        return $result;
    }
    
    public function change($id = null, array $data = null) {
        $id = intval($id);
        $data = $this->checkArgs($data);
        unset($data['name']);
        $column = $this->keyAssembly($data);

        if ($id > 0 && count($data) > 0) {
            $sql = 'UPDATE `' . $this->table . '` SET ' . $column . ' WHERE id = :id';
            $sth = $this->db->prepare($sql);
            $sth->bindParam(':id', $id, PDO::PARAM_INT);
            
            foreach ($data as $key => $val) {
                $sth->bindParam(':' . $key, $data[$key], is_int($val) ? PDO::PARAM_INT : PDO::PARAM_STR);
            }

            if ($sth->execute()) {
                $system = new SystemModel();
                $system->regenSofia();
                return true;
            }
        }

        return false;
    }


    public function delete($id = null) {
        $id = intval($id);
        $result = $this->get($id);
        if (count($result) > 0){
            $file = $this->config->fs->path . '/conf/sofia/' . $result['name'] . '.xml';
            if (file_exists($file)) {
                unlink($file);
            }

            $sql = 'DELETE FROM ' . $this->table . ' WHERE id = ' . $id;
            $this->db->query($sql);
            return true;
        }

        return false;
    }
    
    public function create(array $data = null) {
        var_dump($data);
        echo '<br>===================================<br>';
    	$count = count($this->column);
        $data = $this->checkArgs($data);
        var_dump($data);
        echo '======================================<br>';
        if ((count($data) == $count) && (!in_array(null, $data, true))) {
            echo '1111111111111111111111111111<br>';
            /* Check that the name has been used */
            if (!$this->checkNmae($data['name'])) {
                return false;
            }
            echo '222222222222222222222222222222222<br>';
        	$sql = 'INSERT INTO `' . $this->table . '`(`name`, `ip`, `port`, `in_code`, `out_code`, `description`) VALUES(:name, :ip, :port, :in_code, :out_code, :description)';
        	$sth = $this->db->prepare($sql);

        	foreach ($data as $key => $val) {
        		$sth->bindParam(':' . $key, $data[$key], is_int($val) ? PDO::PARAM_INT : PDO::PARAM_STR);
        	}

            echo '333333333333333333333333333333<br>';
            exit;
        	if ($sth->execute()) {
                $system = new SystemModel();
                $system->regenSofia();
                return true;
            }
        }
        exit;
        return false;
    }

    public function isExist($id = null) {
        $id = intval($id);
        if ($id > 0 && $this->db) {
            $sql = 'SELECT id FROM `' . $this->table . '` WHERE id = ' . $id . ' LIMIT 1';
            $result = $this->db->query($sql)->fetchAll();
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
               	$res['port'] = Filter::port($val, 5060);
               	break;
            case 'in_code':
               	$res['in_code'] = Filter::string($val, 'PCMU,PCMA', 1, 64);
               	break;
            case 'out_code':
               	$res['out_code'] = Filter::string($val, 'PCMU,PCMA', 1, 64);
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

    public function checkNmae($name = null) {
        $name = Filter::alpha($name, null, 1, 32);
        if ($name != null) {
            $sql = 'SELECT * FROM `interface` WHERE name = :name';
            $sth = $this->db->prepare($sql);
            $sth->bindParam(':name', $name, PDO::PARAM_STR);
            $sth->execute();
            if (count($sth->fetch()) > 0) {
                return false;
            }

            return true;
        }

        return false;
    }
}
