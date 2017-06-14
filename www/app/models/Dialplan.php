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
            $sql = 'SELECT * FROM `' . $this->table . '` WHERE id = :id LIMIT 1';
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
            $sql = 'SELECT * FROM `' . $this->table . '` WHERE rid = :rid ORDER BY id';
            $sth = $this->db->prepare($sql);
            $sth->bindParam(':rid', $rid, PDO::PARAM_INT);
            $sth->execute();
            $result = $sth->fetchAll();
        }

        return $result;
    }
    
    public function getCount($rid = null) {
        $rid = intval($rid);
        $sql = 'SELECT count(id) AS count FROM `' . $this->table . '` WHERE rid = ' . $rid;
        $result = $this->db->query($sql)->fetchAll();
        if (count($result) > 0) {
            return intval($result[0]['count']);
        }

        return 0;
    }

    public function change($id = null, array $data = null) {
        $id = intval($id);
        $data = $this->checkArgs($data);
        unset($data['rid']);
        $column = $this->keyAssembly($data);

        if ($id > 0 && count($data) > 0) {
            $sql = 'UPDATE `' . $this->table . '` SET ' . $column . ' WHERE id = :id';
            $sth = $this->db->prepare($sql);
            $sth->bindParam(':id', $id, PDO::PARAM_INT);
            
            foreach ($data as $key => $val) {
                $sth->bindParam(':' . $key, $data[$key], is_int($val) ? PDO::PARAM_INT : PDO::PARAM_STR);
            }

            if ($sth->execute()) {
                $dialplan = $this->get($id);
                if (count($dialplan) > 0) {
                    $system = new SystemModel();
                    $system->regenPlan($dialplan['rid']);
                    $system->reloadXml();
                }
                return true;
            }
        }

        return false;
    }


    public function delete($id = null) {
        $id = intval($id);

        if (!$this->isExist($id)){
            return false;
        }

        $obj = $this->get($id);
        if (count($obj) > 0) {
            $sql = 'DELETE FROM `dialplan` WHERE id = ' . $id;
            if ($this->db->query($sql)) {
                $max = $this->getLastId($obj['rid']);
                for ($i = $id + 1; $i <= $max; $i++) {
                    $sql = 'UPDATE `dialplan` SET id = ' . ($i - 1) . ' WHERE id = ' . $i;
                    $this->db->query($sql);
                }

                $system = new SystemModel();
                $system->regenPlan($obj['rid']);
                $system->reloadXml();
                return true;
            }
        }

        return false;
    }

    public function deleteAll($rid = null) {
        $rid = intval($rid);
        if ($rid > 0){
            $sql = 'DELETE FROM `' . $this->table . '` WHERE rid = ' . $rid;
            if ($this->db->query($sql)) {
                $system = new SystemModel();
                $system->reloadXml();
            }
            return true;
        }

        return false;
    }

    public function create(array $data = null) {
        $count = count($this->column);
        $data = $this->checkArgs($data);

        if ((count($data) == $count) && (!in_array(null, $data, true))) {
            $max = $this->getLastId($data['rid']);

            if ($max == 0) {
                $data['id'] = $data['rid'] * 100 + 1;
            } else if (($max >= 100) && ($max++ < (($data['rid'] + 1) * 100))) {
                $data['id'] = $max;
            } else {
                return false;
            }

            $sql = 'INSERT INTO `' . $this->table . '` VALUES(:id, :rid, :rexp, :type, :sofia, :server, :description)';
            $sth = $this->db->prepare($sql);

            foreach ($data as $key => $val) {
                $sth->bindParam(':' . $key, $data[$key], is_int($val) ? PDO::PARAM_INT : PDO::PARAM_STR);
            }

            if ($sth->execute()) {
                $system = new SystemModel();
                $system->regenPlan($data['rid']);
                $system->reloadXml();
                return true;
            }
        }

        return false;
    }

    public function setPriority($op = null, $id = null) {
        $id = intval($id);
        $kid = ($op == 'up') ? $id - 1 : $id + 1;

        if ($this->isExist($id) && $this->isExist($kid)) {
            if (($kid % 100) != 0) {
                $sql = 'UPDATE `' . $this->table . '` SET id = 1 WHERE id = ' . $kid;
                if ($this->db->query($sql)) {
                    $sql = 'UPDATE `' . $this->table . '` SET id = ' . $kid . ' WHERE id = ' . $id;
                    if ($this->db->query($sql)) {
                        $sql = 'UPDATE `' . $this->table . '` SET id = ' . $id . ' WHERE id = 1';
                        if ($this->db->query($sql)) {
                            return true;
                        }
                        $sql = 'UPDATE `' . $this->table . '` SET id = ' . $id . ' WHERE id = ' . $kid;
                        $this->db->query($sql);
                    } else {
                        $sql = 'UPDATE `' . $this->table . '` SET id = ' . $kid .' WHERE id = 1';
                    }
                }
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
            case 'rid':
                $rid = Filter::number($val, null, 1);
                if ($rid != null) {
                    $route = new RouteModel();
                    if ($route->isExist($rid)) {
                        $res['rid'] = $rid;
                    }
                }
                break;
            case 'rexp':
                $res['rexp'] = Filter::string(str_replace(' ', '', $val), null);
                break;
            case 'type':
                $type = Filter::number($val, null, 1);
                if (in_array($type, [1, 2], true)) {
                    $res['type'] = $type;
                }
                break;
            case 'sofia':
                $sofia = Filter::number($val, null);
                if ($sofia != null) {
                    $interface = new InterfaceModel();
                    if ($interface->isExist($sofia)) {
                        $res['sofia'] = $sofia;
                    }
                }
                break;
            case 'server':
                $res['server'] = Filter::string(str_replace(' ', '', $val), null, 7, 32);
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

    public function getLastId($rid = null) {
        $rid = intval($rid);
        if ($rid > 0) {
            $ben = $rid * 100;
            $end = ($rid + 1) * 100;
            $sql = 'SELECT max(id) AS id FROM `' . $this->table . '` WHERE id BETWEEN ' . $ben . ' AND ' . $end . ' ORDER BY id';
            $result = $this->db->query($sql)->fetchAll();
            if ((count($result) > 0) && (intval($result[0]['id']) >= $ben)) {
                return intval($result[0]['id']);
            }
        }

        return 0;
    }
}
