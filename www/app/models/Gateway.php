<?php

/*
 * The Gateway Model
 * Link http://github.com/typefo/pbx-mon
 * By typefo <typefo@qq.com>
 */

use Tool\Filter;

class GatewayModel {

    public $db = null;
    public $config = null;
    private $table = 'gateway';
    private $column = ['name', 'ip', 'port', 'call', 'route', 'description'];
    

    public function __construct() {
        $this->db = Yaf\Registry::get('db');
        $this->config = Yaf\Registry::get('config');
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
        $result = $this->get($id);
        if ($id > 0 && $this->db && $this->isExist($id)){
            $sql = 'DELETE FROM ' . $this->table . ' WHERE id = ' . $id;
            $this->db->query($sql);

            if ($result && $result['call'] == 1) {
            	$this->regenAcl();
            	$this->reloadAcl();
            }
            return true;
        }

        return false;
    }
    
    public function create(array $data = null) {
    	$count = count($this->column);
        $data = $this->checkArgs($data);

        if ((count($data) == $count) && (!in_array(null, $data, true))) {
        	$sql = 'INSERT INTO ' . $this->table . '(name, ip, port, call, route, description) VALUES(:name, :ip, :port, :call, :route, :description)';
        	$sth = $this->db->prepare($sql);

        	foreach ($data as $key => $val) {
        		$sth->bindParam(':' . $key, $val, is_int($val) ? PDO::PARAM_INT : PDO::PARAM_STR);
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
    	$res = [];
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
            case 'call':
               	$res['call'] = Filter::number($val, 0);
               	break;
            case 'route':
               	$res['route'] = Filter::string($val, null, 1, 64);
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
    
    public function regenAcl() {
        $result = $this->getAll();

        $file = $this->config->fs->path . '/conf/acl.xml';
        if (count($result) > 0 && is_writable($file)) {
            $xml = '<list name="local" default="deny">' . "\n";
            foreach ($result as $obj) {
                $xml .= $obj['call'] == 1 ? '  <node type="allow" cidr="' . $obj['ip'] . '/32"/>' . "\n" : '';
            }
            $xml .= '</list>' . "\n";

            $fp = fopen($file, "w");
            if ($fp) {
                fwrite($fp, $xml);
                fclose($fp);
                return true;
            }

            error_log('Cannot write file ' . $file . ' permission denied');
        }

        return false;
    }

    public function regenXml() {
        $result = $this->getAll();
        if (count($result) > 0) {
            $file = $this->config->fs->path . '/conf/dialplan/default.xml';
            if (is_writable($file)) {
                $xml = '<?xml version="1.0" encoding="utf-8"?>' . "\n";
                $xml .= '<include>' . "\n";
                $xml .= '  <context name="' . $route['name'] . '">' . "\n";
                $xml .= '    <extension name="unloop">' . "\n";
                $xml .= '      <condition field="${unroll_loops}" expression="^true$"/>' . "\n";
                $xml .= '      <condition field="${sip_looped_call}" expression="^true$">' . "\n";
                $xml .= '        <action application="deflect" data="${destination_number}"/>' . "\n";
                $xml .= '      </condition>' . "\n";
                $xml .= '    </extension>' . "\n\n";

                foreach ($result as $obj) {
                    $xml .= '    <extension name="' . $obj['ip'] . '">' . "\n";
                    $xml .= '      <condition field="network_addr" expression="^' . $obj['ip'] . '$">' . "\n";
                    $xml .= '        <action application="transfer" data="${destination_number} XML ' . $obj['route'] . '"/>'
                    $xml .= '      </condition>' . "\n";
                    $xml .= '    </extension>' . "\n";
                }
            }
        }
    }

    public function reloadXml() {
        if ($this->eslCmd('bgapi reloadxml')) {
            return true;
        }
        return false;
    }

    public function eslCmd($cmd = null) {
        if ($cmd && is_string($cmd)) {
            $config = $this->config->esl;

            // conection to freeswitch
            $esl = new ESLconnection($config->host, $config->port, $config->password);
            
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
