<?php

/*
 * The System Model
 * Link http://github.com/typefo/pbx-mon
 * By typefo <typefo@qq.com>
 */

use Tool\Filter;
use Esl\ESLconnection;

class SystemModel {

    public $db = null;
    public $config = null;

    public function __construct() {
        $this->db = Yaf\Registry::get('db');
        $this->config = Yaf\Registry::get('config');
        umask(0117);
    }

    public function regenAcl() {
        $server = new ServerModel();
        $result = $server->getAll();

        if (count($result) > 0) {
            $file = $this->config->fs->path . '/conf/acl.xml';

            /* Check if the file is writable */
            if (!is_writable($file)) {
                error_log('Cannot write file ' . $file . ' permission denied');
                return false;
            }

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
        }

        return false;
    }

    public function regenPlan($rid = null) {
        $route = new RouteModel();
        $result = $route->get($rid);

        if (count($result) > 0) {
            $file = $this->config->fs->path . '/conf/dialplan/' . $result['name']. '.xml';
            $dialplan = new DialplanModel();
            $extensions = $dialplan->getAll($rid);

            $xml = '<?xml version="1.0" encoding="utf-8"?>' . "\n";
            $xml .= '<include>' . "\n";
            $xml .= '  <context name="' . $result['name'] . '">' . "\n";
            $xml .= '    <extension name="unloop">' . "\n";
            $xml .= '      <condition field="${unroll_loops}" expression="^true$"/>' . "\n";
            $xml .= '      <condition field="${sip_looped_call}" expression="^true$">' . "\n";
            $xml .= '        <action application="deflect" data="${destination_number}"/>' . "\n";
            $xml .= '      </condition>' . "\n";
            $xml .= '    </extension>' . "\n\n";

            if (count($extensions) > 0) {
            	$config = new ConfigModel();
            	$record = ($config->get('record') === '1') ? true : false;
                $interface = new InterfaceModel();
                foreach ($extensions as $obj) {
                    $xml .= '    <extension name="' . $obj['id'] . '">' . "\n";

                    $field = 'destination_number';
                    switch ($obj['type']) {
                        case 1:
                            $field = 'caller_id_number';
                            $number = '${destination_number}';
                            break;
                        case 2:
                            $field = 'destination_number';
                            $number = '$1';
                            break;
                        default:
                            break;
                    }

                    $xml .= '      <condition field="' . $field . '" expression="' . $obj['rexp'] . '">' . "\n";
                    $xml .= '        <action application="set" data="called=' . $number . '"/>' . "\n";
                    $xml .= '        <action application="set" data="call_timeout=60"/>' . "\n";
                    $xml .= '        <action application="set" data="ringback=${cn-ring}"/>' . "\n";

                    /* Check record */
                    if ($record) {
                    	$xml .= '        <action application="set" data="RECORD_STEREO=false"/>' . "\n";
                    	$xml .= '        <action application="set" data="RECORD_ANSWER_REQ=true"/>' . "\n";
                    	
                    	$xml .= '        <action application="record_session" data="/var/record/${strftime(%Y/%m/%d}/${caller_id_number}-${called}-${uuid}.wav"/>' . "\n";
                    }

                    $xml .= '        <action application="set" data="sip_dest_host=' . explode(':', $obj['server'])[0] . '"/>' . "\n";
                    $sofia = $interface->get($obj['sofia']);
                    $xml .= '        <action application="export" data="nolocal:absolute_codec_string=' . $sofia['out_code'] . '"/>' . "\n";
                    $xml .= '        <action application="bridge" data="sofia/' . $sofia['name'] . '/${called}@' . $obj['server'] . '"/>' . "\n";
                    $xml .= '        <action application="hangup"/>' . "\n";
                    $xml .= '      </condition>' . "\n";
                    $xml .= '    </extension>' . "\n\n";
                }
            }

            $xml .= '  </context>' . "\n";
            $xml .= '</include>' . "\n";

            $fp = fopen($file, "w");
            if ($fp) {
                fwrite($fp, $xml);
                fclose($fp);
                return true;
            }
            error_log('Cannot open file ' . $file . ' permission denied');
        }

        return false;
    }

    public function regenDefXml() {
        $server = new ServerModel();
        $result = $server->getAll();
        if (count($result) > 0) {
            $file = $this->config->fs->path . '/conf/dialplan/route.xml';

            /* Check if the file is writable */
            if (!is_writable($file)) {
                error_log('Cannot write file ' . $file . ' permission denied');
                return false;
            }

            $xml = '<?xml version="1.0" encoding="utf-8"?>' . "\n";
            $xml .= '<include>' . "\n";
            $xml .= '  <context name="route">' . "\n";
            $xml .= '    <extension name="unloop">' . "\n";
            $xml .= '      <condition field="${unroll_loops}" expression="^true$"/>' . "\n";
            $xml .= '      <condition field="${sip_looped_call}" expression="^true$">' . "\n";
            $xml .= '        <action application="deflect" data="${destination_number}"/>' . "\n";
            $xml .= '      </condition>' . "\n";
            $xml .= '    </extension>' . "\n\n";

            $route = new RouteModel();
            foreach ($result as $obj) {
                $res = $route->get($obj['route']);
                $value = (count($res) > 0) ? $res['name'] : 'default';
                $xml .= '    <extension name="' . $obj['ip'] . '">' . "\n";
                $xml .= '      <condition field="network_addr" expression="^' . str_replace('.', '\.', $obj['ip']) . '$">' . "\n";
                $xml .= '        <action application="transfer" data="${destination_number} XML ' . $value . '"/>' . "\n";
                $xml .= '      </condition>' . "\n";
                $xml .= '    </extension>' . "\n\n";
            }

            $xml .= '  </context>' . "\n";
            $xml .= '</include>' . "\n";

            /* Write configuration file */
            $fp = fopen($file, "w");
            if ($fp) {
                fwrite($fp, $xml);
                fclose($fp);
                return true;
            }
        }

        return false;
    }

    public function regenSofia() {
        $interface = new InterfaceModel();
        $result = $interface->getAll();

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
                $xml .= '    <param name="context" value="route"/>' . "\n";
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

    public function reloadXml() {
        if ($this->eslCmd('bgapi reloadxml')) {
            return true;
        }
        return false;
    }

    public function reloadAcl() {
        if ($this->eslCmd('bgapi reloadacl')) {
            return true;
        }
        return false;
    }

    public function eslCmd($cmd = null, $recv = false) {
        if ($cmd && is_string($cmd)) {
            $config = $this->config->esl;

            /* Conection to freeswitch */
            $esl = new ESLconnection($config->host, $config->port, $config->password);

            if ($esl) {
                /* Send command to freeswitch execute */
                if ($recv) {
                    $reply = $esl->sendRecv($cmd);
                    $esl->disconnect();
                    return $reply ? $reply->getBody() : 'Connect to freeswitch socket error, It may not be running';
                }

                $esl->send($cmd);
                /* Close esl connection */
                $esl->disconnect();
                return true;
            }

            error_log('esl cannot connect to freeswitch', 0);
        }

        return false;
    }

    public function getVersion() {
        return $this->config->version;
    }

    public function sysInfo($esl = null) {
        $status['uptime'] = $this->getUptime();
        $status['cpuinfo'] = $this->getCpuInfo();
        $status['memory'] = $this->getMemory();
        $status['hard'] = $this->getDisk();
        $status['uname'] = $this->getUname();

        return $status;
    }

    public function getUptime() {
        $str = "";
        $uptime = "";
    
        if (($str = @file("/proc/uptime")) === false) {
            return "";
        }
    
        $str = explode(" ", implode("", $str));
        $str = trim($str[0]);
        $min = $str / 60;
        $hours = $min / 60;
        $days = floor($hours / 24);
        $hours = floor($hours - ($days * 24));
        $min = floor($min - ($days * 60 * 24) - ($hours * 60));

        if ($days !== 0) {
            $uptime = $days."天";
        }
        if ($hours !== 0) {
            $uptime .= $hours."小时";
        }

        $uptime .= $min."分钟";

        return $uptime;
    }
    
    public function getCpuInfo() {
        if (($str = @file("/proc/cpuinfo")) === false) {
            return false;
        }
    
        $str = implode("", $str);
        @preg_match_all("/model\s+name\s{0,}\:+\s{0,}([\w\s\)\(\@.-]+)([\r\n]+)/s", $str, $model);

        if (false !== is_array($model[1])) {
            $core = sizeof($model[1]);
            $cpu = $model[1][0].' x '.$core.'核';
            return $cpu;
        }

        return "Unknown";
    }
    
    public function getDisk() {
        $total = round(@disk_total_space("/var/record")/(1024*1024*1024),3); //总
        $avail = round(@disk_free_space("/var/record")/(1024*1024*1024),3); //可用
        $use = $total - $avail; //已用
        $percentage = (floatval($total) != 0) ? round($avail / $total * 100, 0) : 0;

        return ['total' => $total, 'avail' => $avail, 'use' => $use, 'percentage' => $percentage];
    }
    
    public function getLoadavg() {
        if (($str = @file("/proc/loadavg")) === false) {
            return 'Unknown';
        }

        $str = explode(" ", implode("", $str));
        $str = array_chunk($str, 4);
        $loadavg = implode(" ", $str[0]);

        return $loadavg;
    }
    
    public function getMemory() {
        if (false === ($str = @file("/proc/meminfo"))) {
            return ['total' => 0, 'free' => 0, 'use' => 0, 'percentage' => 0];
        }
    
        $str = implode("", $str);
        preg_match_all("/MemTotal\s{0,}\:+\s{0,}([\d\.]+).+?MemFree\s{0,}\:+\s{0,}([\d\.]+).+?Cached\s{0,}\:+\s{0,}([\d\.]+).+?SwapTotal\s{0,}\:+\s{0,}([\d\.]+).+?SwapFree\s{0,}\:+\s{0,}([\d\.]+)/s", $str, $buf);
        preg_match_all("/Buffers\s{0,}\:+\s{0,}([\d\.]+)/s", $str, $buffers);
    
        $total = round($buf[1][0] / 1024, 2);
        $free = round($buf[2][0] / 1024, 2);
        $buffers = round($buffers[1][0] / 1024, 2);
        $cached = round($buf[3][0] / 1024, 2);
        $use = $total - $free - $cached - $buffers; //真实内存使用
        $percentage = (floatval($total) != 0) ? round($use / $total * 100, 0) : 0; //真实内存使用率

        return ['total' => $total, 'free' => $free, 'use' => $use, 'percentage' => $percentage];
    }
    
    public function getUname() {
        return php_uname();
    }

    public function getPbx() {
        return $this->eslCmd('api status', true);
    }
}
