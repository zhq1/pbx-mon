<?php

/*
 * The Dialplan Model
 * Link http://github.com/typefo/pbx-mon
 * By typefo <typefo@qq.com>
 */

use Tool\Filter;

class DialplanModel {

    public $db = null;
    private $table = 'dialplan';
    private $column = ['rid', 'rexp', 'type', 'sofia', 'server', 'description'];
    

    public function __construct() {
        $this->db = Yaf\Registry::get('db');
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

    public function getAll($rid = null) {
        $rid = intval($rid);
        $result = array();
        if ($rid > 0) {
            $sql = 'SELECT * FROM ' . $this->table . ' WHERE rid = :rid ORDER BY id';
            $sth = $this->db->prepare($sql);
            $sth->bindParam(':rid', $rid, PDO::PARAM_INT);
            $result = $this->db->query($sql);
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

            return $sth->execute() ? true : false;
        }

        return false;
    }


    public function delete($id = null) {
        $id = intval($id);
        if ($id > 0){
            $sql = 'DELETE FROM ' . $this->table . ' WHERE id = ' . $id;
            $this->db->query($sql);
            return true;
        }

        return false;
    }

    public function deleteAll($rid = null) {
        $rid = intval($rid);
        if ($rid > 0){
            $sql = 'DELETE FROM ' . $this->table . ' WHERE rid = ' . $rid;
            $this->db->query($sql);
            return true;
        }

        return false;
    }

    public function create(array $data = null) {
        $count = count($this->column);
        $data = $this->checkArgs($data);

        if ((count($data) == $count) && (!in_array(null, $data, true))) {
            $sql = 'INSERT INTO ' . $this->table . '(rid, rexp, type, sofia, server, description) VALUES(:rid, :rexp, :type, :sofia, :server, :description)';
            $sth = $this->db->prepare($sql);

            foreach ($data as $key => $val) {
                $sth->bindParam(':' . $key, $val, is_int($val) ? PDO::PARAM_INT : PDO::PARAM_STR);
            }

            return $sth->execute() ? true : false;
        }

        return false;
    }

    public function setUp($id = null) {
        $id = intval($id);
    }

    public function setDown($id = null) {
        $id = intval($id);
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
            case 'rid':
                $res['rid'] = Filter::number($val, null, 1);
                break;
            case 'rexp':
                $res['rexp'] = Filter::string($val, null);
                break;
            case 'type':
                $res['type'] = Filter::number($val, null, 1);
                break;
            case 'sofia':
                $res['sofia'] = Filter::alpha($val, null);
                break;
            case 'server':
                $res['server'] = Filter::string($val, null);
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
                    $text .= ", $key = :$key";
                } else {
                    $append = true;
                    $text .= "$key = :$key";
                }
            }
        }

        return $text;
    }
}
