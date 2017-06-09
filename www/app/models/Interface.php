<?php

/*
 * The Gateway Model
 * Link http://github.com/typefo/pbx-mon
 * By typefo <typefo@qq.com>
 */

use Tool\Filter;

class InterfaceModel {

    public $db = null;
    public $config = null;
    private $table = 'interface';
    private $column = ['name', 'ip', 'port', 'in_code', 'out_code', 'description'];
    

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

            if ($sth->execute()) {
                $this->regenSofia();
                return true;
            }
        }

        return false;
    }


    public function delete($id = null) {
        $id = intval($id);

        if ($this->isExist($id)){
            $result = $this->get($id);
            if (count($result) > 0) {
                $file = $this->config->fs->path . '/conf/sofia/' . $result['name'] . '.xml';
                if (file_exists($file)) {
                    unlink($file);
                }
            }

            $sql = 'DELETE FROM ' . $this->table . ' WHERE id = ' . $id;
            $this->db->query($sql);
            $this->regenSofia();
            return true;
        }

        return false;
    }
    
    public function create(array $data = null) {
    	$count = count($this->column);
        $data = $this->checkArgs($data);

        if ((count($data) == $count) && (!in_array(null, $data, true))) {
        	$sql = 'INSERT INTO ' . $this->table . '(name, ip, port, in_code, out_code, description) VALUES(:name, :ip, :port, :in_code, :out_code, :description)';
        	$sth = $this->db->prepare($sql);

        	foreach ($data as $key => $val) {
        		$sth->bindParam(':' . $key, $val, is_int($val) ? PDO::PARAM_INT : PDO::PARAM_STR);
        	}

        	if ($sth->execute()) {
                $this->regenSofia();
                return true;
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
            case 'in_code':
               	$res['in_code'] = Filter::string($val, 'PCMU,PCMA', 1, 64);
               	break;
            case 'out_code':
               	$res['out_code'] = Filter::string($val, 'PCMU,PCMA', 1, 64);
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
    
    public function regenSofia() {
        $result = $this->getAll();

        if (count($result) > 0) {
            foreach ($result as $obj) {
                $xml =  '<profile name="' . $obj['name'] . '">' . "\n";
                $xml .= '  <domains>' . "\n";
                $xml .= '    <domain name="all" alias="false" parse="true"/>' . "\n";
                $xml .= '  </domains>' . "\n";
                $xml .= '  <settings>' . "\n";
                $xml .= '    <param name="debug" value="0"/>' . "\n";
                $xml .= '    <param name="sip-trace" value="no"/>' . "\n";
                $xml .= '    <param name="sip-capture" value="no"/>' . "\n";
                $xml .= '    <param name="rfc2833-pt" value="101"/>' . "\n";
                $xml .= '    <param name="sip-port" value="' . $obj['port'] . '"/>' . "\n";
                $xml .= '    <param name="dialplan" value="XML"/>' . "\n";
                $xml .= '    <param name="context" value="default"/>' . "\n";
                $xml .= '    <param name="dtmf-duration" value="2000"/>' . "\n";
                $xml .= '    <param name="inbound-codec-prefs" value="' . $obj['in_code'] . '"/>' . "\n";
                $xml .= '    <param name="outbound-codec-prefs" value="' . $obj['out_code'] . '"/>' . "\n";
                $xml .= '    <param name="hold-music" value="$${hold_music}"/>' . "\n";
                $xml .= '    <param name="rtp-timer-name" value="soft"/>' . "\n";
                $xml .= '    <param name="local-network-acl" value="localnet.auto"/>' . "\n";
                $xml .= '    <param name="manage-presence" value="false"/>' . "\n";
                $xml .= '    <param name="apply-inbound-acl" value="local"/>' . "\n";
                $xml .= '    <param name="inbound-codec-negotiation" value="generous"/>' . "\n";
                $xml .= '    <param name="nonce-ttl" value="60"/>' . "\n";
                $xml .= '    <param name="auth-calls" value="false"/>' . "\n";
                $xml .= '    <param name="inbound-late-negotiation" value="true"/>' . "\n";
                $xml .= '    <param name="inbound-zrtp-passthru" value="true"/>' . "\n";
                $xml .= '    <param name="rtp-ip" value="' . $obj['ip'] . '"/>' . "\n";
                $xml .= '    <param name="sip-ip" value="' . $obj['ip'] . '"/>' . "\n";
                $xml .= '    <param name="ext-rtp-ip" value="auto-nat"/>' . "\n";
                $xml .= '    <param name="ext-sip-ip" value="auto-nat"/>' . "\n";
                $xml .= '    <param name="rtp-timeout-sec" value="300"/>' . "\n";
                $xml .= '    <param name="rtp-hold-timeout-sec" value="1800"/>' . "\n";
                $xml .= '    <param name="tls" value="false"/>' . "\n";
                $xml .= '    <param name="tls-only" value="false"/>' . "\n";
                $xml .= '    <param name="tls-bind-params" value="transport=tls"/>' . "\n";
                $xml .= '    <param name="tls-sip-port" value="' . $obj['port'] . '"/>' . "\n";
                $xml .= '    <param name="tls-passphrase" value=""/>' . "\n";
                $xml .= '    <param name="tls-verify-date" value="true"/>' . "\n";
                $xml .= '    <param name="tls-verify-policy" value="none"/>' . "\n";
                $xml .= '    <param name="tls-verify-depth" value="2"/>' . "\n";
                $xml .= '    <param name="tls-verify-in-subjects" value=""/>' . "\n";
                $xml .= '    <param name="tls-version" value="tlsv1,tlsv1.1,tlsv1.2"/>' . "\n";
                $xml .= '  </settings>' . "\n";
                $xml .= '</profile>' . "\n";

                $file = $this->config->fs->path . '/conf/sofia/' . $obj['name'] . '.xml';
                /* Check if the file is writable */
                if (!is_writable($file)) {
                    error_log('Cannot write file ' . $file . ' permission denied');
                    continue;
                }

                $fp = fopen($file, "w");
                if ($fp) {
                   fwrite($fp, $xml);
                   fclose($fp);
                }
            }

            return true;
        }

        return false;
    }
}
