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
    private $column = ['id', 'name', 'ip', 'port', 'call', 'route', 'description'];

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
        $data = $this->checkArgs($data, $this->column);
        $key = $this->keyAssembly($data);

        if ($id > 0 && count($data) > 0) {
            $sql = 'UPDATE ' . $this->table . ' SET ' . $key . ' WHERE id = :id';
            $sth = $this->db->prepare($sql);
            $sth->bindParam(':id', $id, PDO::PARAM_INT);
            
            foreach ($data as $key => $val) {
                $sth->bindParam($key, $val, is_int($val) ? PDO::PARAM_INT : PDO::PARAM_STR);
            }

            return $sth->execute() ? true : false;
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
        $column = ['user' => 'def:feji,type:string', 'password', 'callerid', 'description'];
        $data = array_intersect_key($data, array_flip($column));
        
        foreach ($data as $key => $val) {
            switch ($key) {
            case 'user':
                $data['user'] = Filter::alpha($val, null, 1, 32);
                break;
            case 'password':
                $data['password'] = Filter::string($val, null, 8, 40);
                break;
            case 'callerid':
                $data['callerid'] = Filter::alpha($val, null, 1, 32);
                break;
            case 'description':
                $data['description'] = Filter::string($val, null, 1, 64);
                break;
            }
        }
        
        $data = $this->keyAssembly($data);
        
        if ($id > 0 && count($data) > 0) {
            $sql = 'INSERT INTO ' . $this->table . '(user, password, callerid, description) VALUES(:user, :password, :callerid, :description)';
            $sth = $this->db->prepare($sql);

            foreach ($data as $key => $val) {
                $sth->bindParam($key, $val, is_int($val) ? PDO::PARAM_INT : PDO::PARAM_STR);
            }

            return $sth->execute() ? true : false;
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
        $column = ['user', 'password', 'callerid', 'description'];
        $data = array_intersect_key($data, array_flip($column));
         
        switch () {

        } 
    }


    public function keyAssembly(array $data, &$text = null) {
        $result = [];
        $append = false;
        foreach ($data as $key => $val) {
            if ($val != null) {
                $result[':' . $key] = $val;
                if ($text != null && $append) {
                    $text .= ", $key = :$key";
                } else {
                    $append = true;
                    $text .= "$key = :$key";
                }
            }
        }

        return $result;
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
