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
        $data = $this->checkArgs($where);
        $where = $this->whereAssembly($data);
        $sql = 'SELECT * FROM `' . $this->table . '` WHERE ' . $where . 'ORDER BY id DESC LIMIT 32';
        $sth = $this->db->prepare($sql);

        if (isset($data['last'])) {
            $sth->bindParam(':id', $data['last'], PDO::PARAM_INT);
        }
            
        if (isset($data['type'], $data['number'])) {
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

        if (isset($data['class'], $data['ip'])) {
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
                if (($last != null) && $last > 0) {
                    $where['last'] = $last;
                }
                break;
            case 'begin':
                $begin = Filter::dateTime($val, null);
                $where['begin'] = ($begin != null) ? $begin : date('Y-m-d 08:00:00');
                break;
            case 'end':
                $end = Filter::dateTime($val, null);
                $where['end'] = ($end != null) ? $end : date('Y-m-d 20:00:00');
                break;
            case 'type':
                $type = Filter::number($val, null);
                if ($type != null && in_array($type, [1, 2], true)) {
                    $where['type'] = $type;
                }
                break;
            case 'number':
                $number = Filter::alpha($val, null, 1);
                if ($number != null) {
                    $where['number'] = $number;
                }
                break;
            case 'class':
                $class = Filter::number($val, null);
                if (($class != null) && in_array($class, [1, 2], true)) {
                    $where['class'] = $class;
                }
                break;
            case 'ip':
                $ip = Filter::ip($val, null);
                if ($ip != null) {
                    $where['ip'] = $ip;
                }
                break;
            case 'duration':
                $duration = Filter::number($val, null, 0);
                if (($duration != null) && $duration > 0) {
                    $where['duration'] = $duration;
                }
                break;
            default:
                break;
            }
        }

        return $where;
    }

    public function whereAssembly(array $data) {
        $and = '';
        $where = '';
        $append = false;

        if (isset($data['last']) && $data['last'] != null) {
            $append = true;
            $where .= 'id < :id ';
        }

        if (isset($data['type'], $data['number'])) {
            switch ($data['type']) {
                case 1:
                    $and = $append ? 'AND ' : '';
                    $where .= $and . 'caller = :caller ';
                    $append = true;
                    break;
                case 2:
                    $and = $append ? 'AND ' : '';
                    $where .= $and . 'called = :called ';
                    $append = true;
                    break;
                default:
                    break;
            }
        }

        if (isset($data['class'], $data['ip'])) {
            switch ($data['class']) {
                case 1:
                    $and = $append ? 'AND ' : '';
                    $where .= $and . 'src_ip = :src_ip ';
                    $append = true;
                    break;
                case 2:
                    $and = $append ? 'AND ' : '';
                    $where .= $and . 'dst_ip = :dst_ip ';
                    $append = true;
                    break;
                default:
                    break;
            }
        }

        if (isset($data['duration'])) {
            $and = $append ? 'AND ' : '';
            $where .= $and . 'duration > :duration ';
            $append = true;
        }

        $and = $append ? 'AND ' : '';
        $where .= $and . 'create_time BETWEEN :begin AND :end ';

        return $where;
    }
}
