<?php

/*
 * The Route Model
 * Link http://github.com/typefo/pbx-mon
 * By typefo <typefo@qq.com>
 */

use Tool\Filter;
use Esl\ESLconnection;

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
        $column = $this->keyAssembly($data);

        if ($id > 0 && count($data) > 0) {
            $sql = 'UPDATE `' . $this->table . '` SET ' . $column . ' WHERE id = :id';
            $sth = $this->db->prepare($sql);
            $sth->bindParam(':id', $id, PDO::PARAM_INT);

            foreach ($data as $key => $val) {
                $sth->bindParam(':' . $key, $val, is_int($val) ? PDO::PARAM_INT : PDO::PARAM_STR);
            }

            if ($sth->execute()) {
                $this->regenPlan();
                $this->reloadXml();
                return true;
            }
        }

        return false;
    }


    public function delete($id = null) {
        $id = intval($id);
        $result = $this->get($id);
        if (count($result) > 0) {
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
            $this->regenPlan();
            $this->reloadXml();
            return true;
        }

        return false;
    }
    
    public function create(array $data = null) {
        $count = count($this->column);
        $data = $this->checkArgs($data);

        if ((count($data) == $count) && (!in_array(null, $data, true))) {
            $sql = 'INSERT INTO `' . $this->table . '`(`name`, `ip`, `port`, `call`, `route`, `description`) VALUES(:name, :ip, :port, :call, :route, :description)';
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
            case 'ip':
                $res['ip'] = Filter::ip($val, null);
                break;
            case 'port':
                $res['port'] = Filter::port($val, null, 5060);
                break;
            case 'call':
                $res['call'] = Filter::number($val, null, 0);
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
    
    public function regenPlan() {
        $routes = $this->getAll();
        if (count($routes) > 0) {
            $dialplan = new DialplanModel();
            foreach ($routes as $route) {
                $file = $this->config->fs->path . '/conf/dialplan/' . $route['name']. '.xml';
                $extensions = $dialplan->getAll($route['id']);

                $xml = '<?xml version="1.0" encoding="utf-8"?>' . "\n";
                $xml .= '<include>' . "\n";
                $xml .= '  <context name="' . $route['name'] . '">' . "\n";
                $xml .= '    <extension name="unloop">' . "\n";
                $xml .= '      <condition field="${unroll_loops}" expression="^true$"/>' . "\n";
                $xml .= '      <condition field="${sip_looped_call}" expression="^true$">' . "\n";
                $xml .= '        <action application="deflect" data="${destination_number}"/>' . "\n";
                $xml .= '      </condition>' . "\n";
                $xml .= '    </extension>' . "\n\n";

                foreach ($extensions as $obj) {
                    $xml .= '    <extension name="' . $obj['id'] . '">' . "\n";

                    $field = 'destination_number';
                    switch ($obj['type']) {
                        case 1:
                            $field = 'caller_id_number';
                            break;
                        case 2:
                            $field = 'destination_number';
                            break;
                        default:
                            break;
                    }

                    $xml .= '      <condition field="' . $field . '" expression="' . $obj['rexp'] . '">' . "\n";
                    $xml .= '        <action application="set" data="called=$1"/>' . "\n";
                    $xml .= '        <action application="set" data="call_timeout=60"/>' . "\n";
                    $xml .= '        <action application="set" data="ringback=${cn-ring}"/>' . "\n";
                    $xml .= '        <action application="set" data="RECORD_STEREO=false"/>' . "\n";
                    $xml .= '        <action application="set" data="RECORD_ANSWER_REQ=true"/>' . "\n";
                    $xml .= '        <action application="record_session" data="/var/record/${strftime(%Y/%m/%d}/${caller_id_number}-${called}-${uuid}.wav"/>' . "\n";
                    $xml .= '        <action application="bridge" data="sofia/' . $obj['sofia'] . '/${called}@' . $obj['server'] . '"/>' . "\n";
                    $xml .= '        <action application="hangup"/>' . "\n";
                    $xml .= '      </condition>' . "\n";
                    $xml .= '    </extension>' . "\n\n";
                }

                $xml .= '  </context>' . "\n";
                $xml .= '</include>' . "\n";

                $fp = fopen($file, "w");
                if (!$fp) {
                    error_log('Cannot open file ' . $file . ' permission denied');
                    contione;
                }

                fwrite($fp, $xml);
                fclose($fp);
            }

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
