<?php

/*
 * The Cdr model
 * Link http://github.com/typefo/pbx-mon
 * By typefo <typefo@qq.com>
 */

use Tool\Filter;

class CdrModel {

    public $db = null;
    private $table = 'cdr';
    private $where = ['begin', 'end', 'type', 'number', 'class', 'ip', 'duration', 'last'];

    public function __construct() {
        $this->db = Yaf\Registry::get('db');
    }   

    public function query(array $where) {
        print_r($where);
        echo '<br>====================================================<br>';
        $data = $this->checkArgs($where);
        var_dump($data);
        echo '<br>====================================================<br>';
        $where = $this->whereAssembly($data);
        $sql = 'SELECT * FROM `' . $this->table . '` WHERE ' . $where . 'ORDER BY id DESC LIMIT 36';
        echo $sql;
        exit;
        $sth = $this->db->prepare($sql);
        
        if (isset($data['last']) && $data['last'] != null) {
            $sth->bindParam(':id', $data['last'], PDO::PARAM_INT);
        }
            
        if (isset($data['type']) && $data['type'] != null) {
            switch ($data['type']) {
                case 1:
                    $sth->bindParam(':caller', $data['number'], PDO::PARAM_STR);
                    break;
                case 2:
                    $sth->bindParam(':called', $data['number'], PDO::PARAM_STR);
                    break;
                default:
                    break;
            }
        }

        if (isset($data['class']) && $data['class'] != null) {
            switch ($data['class']) {
                case 1:
                    $sth->bindParam(':src_ip', $data['ip'], PDO::PARAM_STR);
                    break;
                case 2:
                    $sth->bindParam(':dst_ip', $data['ip'], PDO::PARAM_STR);
                    break;
                default:
                    break;
            }
        }

        if (isset($data['duration'])) {
            $sth->bindParam(':duration', $data['duration'], PDO::PARAM_INT);
        }

        $sth->bindParam(':begin', $data['begin'], PDO::PARAM_STR);
        $sth->bindParam(':end', $data['end'], PDO::PARAM_STR);
        $sth->execute();
        return $sth->fetchAll();
    }

    public function ToDay() {
        if ($this->db) {
            $where['begin'] = date('Y-m-d 08:00:00');
            $where['end']   = date('Y-m-d 20:00:00');
            return $this->query($where);
        }

        return null;
    }

    public function checkArgs(array $data) {
        $where = array();
        $data = array_intersect_key($data, array_flip($this->where));

        foreach ($data as $key => $val) {
            switch ($key) {
            case 'last':
                $last = Filter::number($val, null);
                if ($last != null && $last > 0) {
                    $where['last'] = $last;
                }
                break;
            case 'begin':
                $begin = Filter::dateTime($val, null);
                $where['begin'] = $begin != null ? $begin : date('Y-m-d 08:00:00');
                break;
            case 'end':
                $end = Filter::dateTime($val, null);
                $where['end'] = $end != null ? $end : date('Y-m-d 20:00:00');
                break;
            case 'type':
                $type = Filter::number($val, null);
                if ($type != null && in_array($type, [1, 2], true)) {
                    $where['type'] = $type;
                }
                break;
            case 'number':
                $where['number'] = Filter::alpha($val, null, 1);
                break;
            case 'class':
                $class = Filter::number($val, null);
                if ($class != null && in_array($class, [1, 2], true)) {
                    $where['class'] = $class;
                }
                break;
            case 'ip':
                $where['ip'] = Filter::ip($val, null);
                break;
            case 'duration':
                $where['duration'] = Filter::number($val, null, 0);
                break;
            default:
                break;
            }
        }

        return $where;
    }

    public function whereAssembly(array $data) {
        $where = '';
        $append = false;

        if (isset($data['last']) && $data['last'] != null) {
            $append = true;
            $where .= 'id < :id ';
        }

        if (isset($data['type']) && $data['type'] != null) {
            switch ($data['type']) {
                case 1:
                    $where .= $append ? 'AND caller = :caller ' : 'caller = :caller ';
                    $append = true;
                    break;
                case 2:
                    $where .= $append ? 'AND called = :called ' : 'called = :called ';
                    $append = true;
                    break;
                default:
                    break;
            }
        }

        if (isset($data['class']) && $data['class'] != null) {
            switch ($data['class']) {
                case 1:
                    $where .= $append ? 'AND src_ip = :src_ip ' : 'src_ip = :src_ip ';
                    $append = true;
                    break;
                case 2:
                    $where .= $append ? 'AND dst_ip = :dst_ip ' : 'dst_ip = :dst_ip ';
                    $append = true;
                    break;
                default:
                    break;
            }
        }

        if (isset($data['duration']) && $data['duration'] != null) {
            $where .= $append ? 'AND duration > :duration ' : 'duration > :duration ';
        }

        $where .= $append ? 'AND create_time BETWEEN :begin AND :end ' : 'create_time BETWEEN :begin AND :end ';

        return $where;
    }
}
