<?php

/*
 * The Gateway Model
 * Link http://github.com/typefo/pbx-mon
 * By typefo <typefo@qq.com>
 */

use Tool\Filter;
use Esl\ESLconnection;

class ExtensionModel {

    public $db   = null;
    private $table = 'extension';
    
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
        $data = $this->checkArgs($data, $column);
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
            $success = $this->db->query($sql);
            if ($success) {
                // regenerate the configuration files
                if($this->regenAcl() && $this->regenPlan()){
                    sleep(1);
                    $this->reloadAcl();
                    sleep(1);
                    $this->reloadXml();
                    return true;
                }
            }
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

    public function checkArgs(array $data) {
        $column = ['user', 'password', 'callerid', 'description'];
        $data = array_intersect_key($data, array_flip($column));
         
        switch () {

        } 
    }

    public function regenAcl() {
        if ($this->db) {
            $result = $this->getAll();
            if (count($result) > 0) {
                $file = '/usr/local/freeswitch/conf/acl/external.xml';
                if (is_writable($file)) {
                    $xml = '<list name="external" default="deny">' . "\n";
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

    public function regenPlan() {
        if ($this->db) {
            $result = $this->getAll();
            if (count($result) > 0) {
                $file = '/usr/local/freeswitch/conf/dialplan/default.xml';
                if (is_writable($file)) {
                    $xml = '<?xml version="1.0" encoding="utf-8"?>' . "\n";
                    $xml .= '<include>' . "\n";
                    $xml .= '  <context name="default">' . "\n";
                    $xml .= '    <extension name="unloop">' . "\n";
                    $xml .= '      <condition field="${unroll_loops}" expression="^true$"/>' . "\n";
                    $xml .= '      <condition field="${sip_looped_call}" expression="^true$">' . "\n";
                    $xml .= '        <action application="deflect" data="${destination_number}"/>' . "\n";
                    $xml .= '      </condition>' . "\n";
                    $xml .= '    </extension>' . "\n\n";

                    foreach ($result as $obj) {
                        $xml .= '    <extension name="' . $obj['prefix'] . '">' . "\n";
                        $xml .= '      <condition field="destination_number" expression="^' . $obj['rexp'] . '(.*)$">' . "\n";
                        $xml .= '        <action application="set" data="called=$1"/>' . "\n";
                        $xml .= '        <action application="set" data="call_timeout=60"/>' . "\n";
                        $xml .= '        <action application="set" data="ringback=${cn-ring}"/>' . "\n";
                        $xml .= '        <action application="set" data="RECORD_STEREO=false"/>' . "\n";
                        $xml .= '        <action application="set" data="RECORD_ANSWER_REQ=true"/>' . "\n";
                        $xml .= '        <action application="record_session" data="/var/record/${strftime(%Y/%m/%d}/${caller_id_number}-${called}-${uuid}.wav"/>' . "\n";
                        $xml .= '        <action application="bridge" data="sofia/external/${called}@' . $obj['ip'] . ':' . $obj['port'] . '"/>' . "\n";
                        $xml .= '        <action application="hangup"/>' . "\n";
                        $xml .= '        </condition>' . "\n";
                        $xml .= '    </extension>' . "\n";
                    }
                    
                    $xml .= '  </context>' . "\n";
                    $xml .= '</include>' . "\n";
                
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

    public function reloadXml() {
        if ($this->eslCmd('bgapi reloadxml')) {
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
