<?php

/*
 * The Route Model
 * Link http://github.com/typefo/pbx-mon
 * By typefo <typefo@qq.com>
 */

use Tool\Filter;

class RouteModel {

    public $db = null;
    public $config = null;
    private $table = 'route';
    private $column = ['name', 'type', 'description'];

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
                $system->regenPlan($id);
                $system->reloadXml();
                return true;
            }
        }

        return false;
    }


    public function delete($id = null) {
        $id = intval($id);

        /* Check whether it has been referenced */
        if ($this->checkUsed($id)) {
            return false;
        }

        $result = $this->get($id);
        if (count($result) > 0) {
            /* Check system reserved route */
            $reserved = ['route', 'default'];
            if (in_array($result['name'], $reserved)) {
                return false;
            }
            
            $file = $this->config->fs->path . '/conf/dialplan/' . $result['name'] . '.xml';

            /* Delete dialplan file */
            if (file_exists($file)) {
                unlink($file);
            }

            $sql = 'DELETE FROM `' . $this->table . '` WHERE id = ' . $id;
            $this->db->query($sql);

            /* Delete dialplan */
            $dialplan = new DialplanModel();
            $dialplan->deleteAll($id);

            /* Reload configure file */
            $system = new SystemModel();
            $system->reloadXml();
            return true;
        }

        return false;
    }
    
    public function create(array $data = null) {
        $count = count($this->column);
        $data = $this->checkArgs($data);

        /* Reserved routing table */
        if (in_array($data['name'], ['route', 'default'])) {
            return false;
        }

        if ((count($data) == $count) && (!in_array(null, $data, true))) {
            $sql = 'INSERT INTO `' . $this->table . '`(`name`, `type`, `description`) VALUES(:name, :type, :description)';
            $sth = $this->db->prepare($sql);

            foreach ($data as $key => $val) {
                $sth->bindParam(':' . $key, $data[$key], is_int($val) ? PDO::PARAM_INT : PDO::PARAM_STR);
            }

            if ($sth->execute()) {
            	$system = new SystemModel();
            	$id = $this->db->lastInsertId('id');
            	$system->regenPlan($id);
            	return true;
            }
        }

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
            case 'type':
                $res['type'] = Filter::number($val, 1);
                break;
            case 'description':
                $res['description'] = Filter::string($val, 'no description', 1, 64);
                break;
             default:
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
                    $text .= ", $key = :$key";
                } else {
                    $append = true;
                    $text .= "$key = :$key";
                }
            }
        }

        return $text;
    }

    public function checkUsed($rid = null) {
        $rid = intval($rid);
        $sql = 'SELECT * FROM `server` WHERE route = ' . $rid;
        $result = $this->db->query($sql)->fetchAll();
        if (count($result) > 0) {
            return true;
        }

        return false;
    }
}
