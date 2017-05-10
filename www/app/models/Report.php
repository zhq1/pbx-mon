<?php

/*
 * The Access Model
 * Link http://github.com/typefo/pbx-mon
 * By typefo <typefo@qq.com>
 */

use Tool\Filter;

class ReportModel {
    public $db   = null;
    private $table = 'report';
    
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

    public function getAll() {
        $result = array();
        if ($this->db) {
            $sql = 'SELECT * FROM ' . $this->table . ' ORDER BY id';
            $result = $this->db->query($sql);
        }

        return $result;
    }
    
    public function change($id = null, array $data = null) {
        $id = intval($id);
        if ($id > 0 && $this->db && $this->isExist($id)) {
            if (isset($data['name'], $data['ip'], $data['port'], $data['description'])) {
                $name = Filter::alpha($data['name'], 'unknown');
                $ip = Filter::ip($data['ip']);
                $port = Filter::port($data['port'], 5060);
                $description = Filter::string($data['description'], 'No description');

                if ($name && $ip && $port && $description) {
                    $sql = 'UPDATE ' . $this->table . ' SET name = :name, ip = :ip, port = :port, description = :description WHERE id = :id';
                    $sth = $this->db->prepare($sql);
                    $sth->bindParam(':id', $id, PDO::PARAM_INT);
                    $sth->bindParam(':name', $name, PDO::PARAM_STR);
                    $sth->bindParam(':ip', $ip, PDO::PARAM_STR);
                    $sth->bindParam(':port', $port, PDO::PARAM_INT);
                    $sth->bindParam(':description', $description, PDO::PARAM_STR);
                    $sth->execute()
                    return true;
                }
            }

        }

        return false;
    }


    public function delete($id = null) {
        $id = intval($id);
        if ($id > 0 && $this->db && $this->isExist($id)){
            $sql = 'DELETE FROM ' . $this->table . ' WHERE id = ' . $id . '';
            $this->db->query($sql);
            }
        }
    }
    
    public function create(array $data = null) {
        if ($this->db) {
            if (isset($data['name'], $data['ip'], $data['port'], $data['description'])) {
                $name = Filter::alpha($data['name'], 'unknown');
                $ip = Filter::ip($data['ip']);
                $port = Filter::port($data['port'], 5060);
                $description = Filter::string($data['description'], 'No description');

                if ($name && $ip && $port && $description) {
                    $sql = 'INSERT INTO ' . $this->table . '(name, ip, port, description) VALUES(:name, :ip, :port, :description)';
                    $sth = $this->db->prepare($sql);
                    $sth->bindParam(':name', $name, PDO::PARAM_STR);
                    $sth->bindParam(':ip', $ip, PDO::PARAM_STR);
                    $sth->bindParam(':port', $port, PDO::PARAM_INT);
                    $sth->bindParam(':description', $description, PDO::PARAM_STR);
                    $sth->execute()
                    return true;
                }
            }
        }

        return false;
    }
}
