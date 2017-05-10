<?php

/*
 * The Gateway Model
 * Link http://github.com/typefo/pbx-mon
 * By typefo <typefo@qq.com>
 */

use Tool\Filter;

class GatewayModel {

    public $db   = null;
    private $table = 'gateway';
    
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
            if (isset($data['name'], $data['ip'], $data['port'], $data['call'], $data['description'])) {
                $name = Filter::alpha($data['name']);
                $ip = Filter::ip($data['ip']);
                $port = Filter::port($data['port'], 5060);
                $call = in_array(intval($data['call']), [0, 1], true) ? intval($data['call']) : 0;
                $description = Filter::string($data['description'], 'No description');

                if ($name && $ip && $port && $description) {
                    $sql = 'UPDATE ' . $this->table . ' SET name = :name, ip = :ip, port = :port, call = :call, description = :description WHERE id = :id';
                    $sth = $this->db->prepare($sql);
                    $sth->bindParam(':id', $id, PDO::PARAM_INT);
                    $sth->bindParam(':name', $name, PDO::PARAM_STR);
                    $sth->bindParam(':ip', $ip, PDO::PARAM_STR);
                    $sth->bindParam(':port', $port, PDO::PARAM_INT);
                    $sth->bindParam(':call', $port, PDO::PARAM_INT);
                    $sth->bindParam(':description', $description, PDO::PARAM_STR);
                    $sth->execute();
                    return true;
                }
            }
        }

        return false;
    }


    public function delete($id = null) {
        $id = intval($id);
        if ($id > 0 && $this->db && $this->isExist($id)){
            $sql = 'DELETE FROM ' . $this->table . ' WHERE id = ' . $id;
            $this->db->query($sql);
            return true;
        }

        return false;
    }
    
    public function create(array $data = null) {
        if ($this->db) {
            if (isset($data['name'], $data['ip'], $data['port'], $data['call'], $data['description'])) {
                $name = Filter::alpha($data['name']);
                $ip = Filter::ip($data['ip']);
                $port = Filter::port($data['port'], 5060);
                $call = in_array(intval($data['call']), [0, 1], true) ? intval($data['call']) : 0;
                $description = Filter::string($data['description'], 'No description');

                if ($name && $ip && $port && $description) {
                    $sql = 'INSERT INTO ' . $this->table . '(name, ip, port, call, description) VALUES(:name, :ip, :port, :call, :description)';
                    $sth = $this->db->prepare($sql);
                    $sth->bindParam(':name', $name, PDO::PARAM_STR);
                    $sth->bindParam(':ip', $ip, PDO::PARAM_STR);
                    $sth->bindParam(':port', $port, PDO::PARAM_INT);
                    $sth->bindParam(':call', $call, PDO::PARAM_INT);
                    $sth->bindParam(':description', $description, PDO::PARAM_STR);
                    $sth->execute();
                    return true;
                }
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

    public function regenAcl() {
        if ($this->db) {
            $result = $this->getAll();
            if (count($result) > 0) {
                $file = '/usr/local/freeswitch/conf/acl/internal.xml';
                if (is_writable($file)) {
                    $xml = '<list name="internal" default="deny">' . "\n";
                    foreach ($result as $obj) {
                        $xml .= '  <node type="allow" cidr="' . $obj['ip'] . '/32"/>' . "\n";
                    }
                    $xml .= '</list>' . "\n";

                
                    $fp = fopen($file, "w");
                    if ($fp) {
                        fwrite($fp, $xml);
                        fclose($fp);
                        return true;
                    }
                }

                error_log('Cannot write file ' . $file . ' permission denied');
            }
        }

        return false;
    }

    public function reloadAcl() {
        if ($this->eslCmd('bgapi reloadacl')) {
            return true;
        }

        return false;
    }

    public function eslCmd($cmd = null) {
        if ($cmd && is_string($cmd)) {
            $config = Yaf\Registry::get('config');

            // conection to freeswitch
            $esl = new ESLconnection($config->esl->host, $config->esl->port, $config->esl->password);
            
            if ($esl) {
                // exec reloadacl command
                $esl->send($cmd);
                // close esl connection
                $esl->disconnect();
                return true;
            }
            
            error_log('esl cannot connect to freeswitch', 0);
        }
        
        return false;
    }
}
