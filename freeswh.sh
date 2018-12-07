#!/bin/bash
#安装基础依赖包

#firewall-cmd --state
sudo systemctl stop firewalld.service
sudo systemctl disable firewalld.service
setenforce 0
sudo sed -i 's/SELINUX=enforcing/SELINUX=disabled/' /etc/selinux/config
sudo systemctl stop iptables.service
sudo systemctl disable iptables.service
sudo systemctl disable auditd.service
sudo systemctl disable microcode.service
sudo systemctl disable NetworkManager.service
sudo systemctl disable postfix.service
sudo systemctl disable tuned.service
sync
sudo yum -y install epel-release
sudo yum makecache fast
sudo yum install -y git wget vim tcl sudo
sudo yum install -y gcc gcc-c++ autoconf automake libtool make unzip
sudo yum install -y python ncurses-devel zlib-devel ldns-devel
sudo yum install -y openssl-devel libevent libevent-devel sqlite-devel libcurl-devel pcre-devel libxml2-devel
sudo yum install -y libdb4* libidn-devel unbound-devel libuuid-devel lua-devel libsndfile-devel libjpeg-devel
sudo yum install -y speex-devel libedit-devel e2fsprogs-devel gsm gsm-devel re2c redis mariadb mariadb-server
sudo yum install -y nginx php php-fpm php-devel php-pdo php-mysql php-mysqli php-mcrypt php-mbstring 
sudo yum install -y gcc-c++ sqlite-devel zlib-devel libcurl-devel pcre-devel  speex-devel ldns-devel  libedit-devel  openssl-devel -y
sudo yum install -y libjpeg-devel lua-devel libsndfile-devel libyuv-devel git libtool -y

#优化内核参数
sudo \cp /etc/sysctl.conf /etc/sysctl.conf.`date +"%Y-%m-%d_%H-%M-%S"`
sudo tee /etc/sysctl.conf <<-'EOF'
kernel.printk = 4 4 1 7 
kernel.panic = 10 
kernel.sysrq = 0 
kernel.shmmax = 4294967296 
kernel.shmall = 4194304 
kernel.core_uses_pid = 1 
kernel.msgmnb = 65536 
kernel.msgmax = 65536 
vm.swappiness = 20 
vm.dirty_ratio = 80 
vm.dirty_background_ratio = 5 
fs.file-max = 2097152 
net.core.netdev_max_backlog = 262144 
net.core.rmem_default = 31457280 
net.core.rmem_max = 67108864 
net.core.wmem_default = 31457280 
net.core.wmem_max = 67108864 
net.core.somaxconn = 65535 
net.core.optmem_max = 25165824 
net.ipv4.neigh.default.gc_thresh1 = 4096 
net.ipv4.neigh.default.gc_thresh2 = 8192 
net.ipv4.neigh.default.gc_thresh3 = 16384 
net.ipv4.neigh.default.gc_interval = 5 
net.ipv4.neigh.default.gc_stale_time = 120 
net.netfilter.nf_conntrack_max = 10000000 
net.netfilter.nf_conntrack_tcp_loose = 0 
net.netfilter.nf_conntrack_tcp_timeout_established = 1800 
net.netfilter.nf_conntrack_tcp_timeout_close = 10 
net.netfilter.nf_conntrack_tcp_timeout_close_wait = 10 
net.netfilter.nf_conntrack_tcp_timeout_fin_wait = 20 
net.netfilter.nf_conntrack_tcp_timeout_last_ack = 20 
net.netfilter.nf_conntrack_tcp_timeout_syn_recv = 20 
net.netfilter.nf_conntrack_tcp_timeout_syn_sent = 20 
net.netfilter.nf_conntrack_tcp_timeout_time_wait = 10 
net.ipv6.conf.all.disable_ipv6 = 1
net.ipv6.conf.default.disable_ipv6 = 1
net.ipv4.tcp_slow_start_after_idle = 0 
net.ipv4.ip_local_port_range = 1024 65000 
net.ipv4.ip_no_pmtu_disc = 1 
net.ipv4.route.flush = 1 
net.ipv4.route.max_size = 8048576 
net.ipv4.icmp_echo_ignore_broadcasts = 1 
net.ipv4.icmp_ignore_bogus_error_responses = 1 
net.ipv4.tcp_congestion_control = htcp 
net.ipv4.tcp_mem = 65536 131072 262144 
net.ipv4.udp_mem = 65536 131072 262144 
net.ipv4.tcp_rmem = 4096 87380 33554432 
net.ipv4.udp_rmem_min = 16384 
net.ipv4.tcp_wmem = 4096 87380 33554432 
net.ipv4.udp_wmem_min = 16384 
net.ipv4.tcp_max_tw_buckets = 1440000 
net.ipv4.tcp_tw_recycle = 0 
net.ipv4.tcp_tw_reuse = 1 
net.ipv4.tcp_max_orphans = 400000 
net.ipv4.tcp_window_scaling = 1 
net.ipv4.tcp_rfc1337 = 1 
net.ipv4.tcp_syncookies = 1 
net.ipv4.tcp_synack_retries = 1 
net.ipv4.tcp_syn_retries = 2 
net.ipv4.tcp_max_syn_backlog = 16384 
net.ipv4.tcp_timestamps = 1 
net.ipv4.tcp_sack = 1 
net.ipv4.tcp_fack = 1 
net.ipv4.tcp_ecn = 2 
net.ipv4.tcp_fin_timeout = 10 
net.ipv4.tcp_keepalive_time = 600 
net.ipv4.tcp_keepalive_intvl = 60 
net.ipv4.tcp_keepalive_probes = 10 
net.ipv4.tcp_no_metrics_save = 1 
net.ipv4.ip_forward = 1 
net.ipv4.conf.all.accept_redirects = 0 
net.ipv4.conf.all.send_redirects = 0 
net.ipv4.conf.all.accept_source_route = 0 
net.ipv4.conf.all.rp_filter = 1
fs.file-max = 2048000
fs.nr_open = 2048000
fs.aio-max-nr = 1048576
fs.mqueue.msg_default = 10240
fs.mqueue.msg_max = 10240
fs.mqueue.msgsize_default = 8192
fs.mqueue.msgsize_max = 8192
fs.mqueue.queues_max = 256
EOF
sudo sysctl -p

# 增加文件描述符限制
sudo \cp /etc/security/limits.conf /etc/security/limits.conf.`date +"%Y-%m-%d_%H-%M-%S"`
sudo tee /etc/security/limits.conf <<-'EOF'
* soft    nofile  1024000
* hard    nofile  1024000
* soft    nproc   unlimited
* hard    nproc   unlimited
* soft    core    unlimited
* hard    core    unlimited
* soft    memlock unlimited
* hard    memlock unlimited
EOF

#创建 pbx 运行用户组，并下载 pbxMon 源码包
sudo groupadd pbx
sudo usermod -g pbx nginx

git clone https://github.com/typefo/pbx-mon.git

cd && git clone https://freeswitch.org/stash/scm/sd/opus.git
cd opus
./autogen.sh
./configure
sudo make -j4
sudo make install
sudo \cp /usr/local/lib/pkgconfig/opus.pc /usr/lib64/pkgconfig

cd .. && git clone https://freeswitch.org/stash/scm/sd/libpng.git
cd libpng
./configure
sudo make -j4
sudo make install
sudo \cp /usr/local/lib/pkgconfig/libpng* /usr/lib64/pkgconfig/

#git clone https://freeswitch.org/stash/scm/sd/libvpx.git
#cd libvpx
#chmod -R 777 *
#./configure --enable-pic --disable-static --enable-shared
#make -j4
#make install
#\cp -r /usr/local/lib/pkgconfig/vpx.pc /usr/lib64/pkgconfig/

#cd ..
#git clone https://freeswitch.org/stash/scm/sd/libyuv.git /freeswitch-1.6.15/libs/libyuv
#cd /./freeswitch-1.6.15/libs/libyuv
#make -f linux.mk CXXFLAGS="-fPIC -O2 -fomit-frame-pointer -Iinclude/"
#make install
#\cp /usr/lib/pkgconfig/libyuv.pc /usr/lib64/pkgconfig/
#\cp /usr/lib/libyuv.so /usr/lib64/

#编译安装 FreeSWITCH
cd && wget http://files.freeswitch.org/freeswitch-releases/freeswitch-1.6.15.tar.gz
tar -xzvf freeswitch-1.6.15.tar.gz
cd freeswitch-1.6.15

sudo \cp modules.conf modules.conf.`date +"%Y-%m-%d_%H-%M-%S"`
sudo tee modules.conf <<-'EOF'
#applications/mod_abstraction
#applications/mod_av
#applications/mod_avmd
#applications/mod_bert
#applications/mod_blacklist
#applications/mod_callcenter
#applications/mod_cidlookup
#applications/mod_cluechoo
applications/mod_commands
applications/mod_conference
applications/mod_curl
#applications/mod_cv
applications/mod_db
#applications/mod_directory
#applications/mod_distributor
applications/mod_dptools
#applications/mod_easyroute
applications/mod_enum
applications/mod_esf
#applications/mod_esl
applications/mod_expr
applications/mod_fifo
#applications/mod_fsk
applications/mod_fsv
applications/mod_hash
#applications/mod_hiredis
applications/mod_httapi
#applications/mod_http_cache
#applications/mod_ladspa
#applications/mod_lcr
#applications/mod_memcache
#applications/mod_mongo
#applications/mod_mp4
#applications/mod_mp4v2
#applications/mod_nibblebill
#applications/mod_oreka
#applications/mod_osp
#applications/mod_prefix
#applications/mod_rad_auth
#applications/mod_redis
#applications/mod_rss
applications/mod_sms
#applications/mod_sms_flowroute
#applications/mod_snapshot
#applications/mod_snom
#applications/mod_sonar
#applications/mod_soundtouch
applications/mod_spandsp
#applications/mod_spy
#applications/mod_stress
#applications/mod_translate
applications/mod_valet_parking
#applications/mod_vmd
applications/mod_voicemail
#applications/mod_voicemail_ivr
#asr_tts/mod_cepstral
#asr_tts/mod_flite
#asr_tts/mod_pocketsphinx
#asr_tts/mod_tts_commandline
#asr_tts/mod_unimrcp
codecs/mod_amr
#codecs/mod_amrwb
codecs/mod_b64
#codecs/mod_bv
#codecs/mod_codec2
#codecs/mod_com_g729
#codecs/mod_dahdi_codec
codecs/mod_g723_1
codecs/mod_g729
codecs/mod_h26x
#codecs/mod_ilbc
#codecs/mod_isac
#codecs/mod_mp4v
codecs/mod_opus
#codecs/mod_sangoma_codec
#codecs/mod_silk
#codecs/mod_siren
#codecs/mod_theora
dialplans/mod_dialplan_asterisk
#dialplans/mod_dialplan_directory
dialplans/mod_dialplan_xml
#directories/mod_ldap
#endpoints/mod_alsa
#endpoints/mod_dingaling
#endpoints/mod_gsmopen
#endpoints/mod_h323
#endpoints/mod_khomp
endpoints/mod_loopback
#endpoints/mod_opal
#endpoints/mod_portaudio
endpoints/mod_rtc
#endpoints/mod_rtmp
endpoints/mod_skinny
#endpoints/mod_skypopen
endpoints/mod_sofia
endpoints/mod_verto
#event_handlers/mod_amqp
event_handlers/mod_cdr_csv
#event_handlers/mod_cdr_mongodb
#event_handlers/mod_cdr_pg_csv
event_handlers/mod_cdr_sqlite
#event_handlers/mod_erlang_event
#event_handlers/mod_event_multicast
event_handlers/mod_event_socket
#event_handlers/mod_format_cdr
event_handlers/mod_json_cdr
#event_handlers/mod_radius_cdr
#event_handlers/mod_odbc_cdr
#event_handlers/mod_kazoo
#event_handlers/mod_rayo
#event_handlers/mod_smpp
#event_handlers/mod_snmp
#event_handlers/mod_event_zmq
#formats/mod_imagick
formats/mod_local_stream
formats/mod_native_file
formats/mod_png
#formats/mod_portaudio_stream
#formats/mod_shell_stream
#formats/mod_shout
formats/mod_sndfile
#formats/mod_ssml
formats/mod_tone_stream
#formats/mod_vlc
#languages/mod_basic
#languages/mod_java
languages/mod_lua
#languages/mod_managed
#languages/mod_perl
#languages/mod_python
#languages/mod_v8
#languages/mod_yaml
loggers/mod_console
#loggers/mod_graylog2
loggers/mod_logfile
loggers/mod_syslog
#say/mod_say_de
say/mod_say_en
#say/mod_say_es
#say/mod_say_es_ar
#say/mod_say_fa
#say/mod_say_fr
#say/mod_say_he
#say/mod_say_hr
#say/mod_say_hu
#say/mod_say_it
#say/mod_say_ja
#say/mod_say_nl
#say/mod_say_pl
#say/mod_say_pt
#say/mod_say_ru
#say/mod_say_sv
#say/mod_say_th
#say/mod_say_zh
#timers/mod_posix_timer
#timers/mod_timerfd
xml_int/mod_xml_cdr
#xml_int/mod_xml_curl
#xml_int/mod_xml_ldap
#xml_int/mod_xml_radius
xml_int/mod_xml_rpc
xml_int/mod_xml_scgi

#../../libs/freetdm/mod_freetdm

## Experimental Modules (don't cry if they're broken)
#../../contrib/mod/xml_int/mod_xml_odbc
EOF

./configure --disable-debug --disable-libyuv --disable-libvpx
sudo make -j4
sudo make install


#然后根据需要安装语音包
#make cd-sounds-install
#make cd-moh-install
#安装简单的配置文件
#make samples

#安装 ESL PHP 模块
cd libs/esl
sudo make phpmod
\cp php/ESL.so /usr/lib64/php/modules

#安装 G729 语音模块
cd && git clone https://github.com/typefo/mod_g729.git
cd mod_g729
sudo make -j4
sudo make install


#安装 phpredis
cd && git clone https://github.com/phpredis/phpredis.git
cd phpredis
phpize
./configure
sudo make -j4
sudo make install


#安装 yaf 框架
cd && wget https://pecl.php.net/get/yaf-2.3.5.tgz
tar -xzvf yaf-2.3.5.tgz
cd yaf-2.3.5
phpize
./configure
sudo make -j4
sudo make install

systemctl enable mariadb.service
systemctl enable redis.service
systemctl enable freeswitch.service
systemctl enable php-fpm.service
systemctl enable nginx.service

systemctl restart mariadb.service
systemctl restart redis.service
systemctl restart freeswitch.service
systemctl restart php-fpm.service
systemctl restart nginx.service

cd && cd /root/pbx-mon
mv src www
#安装配置文件
sudo make config
#安装服务脚本
sudo make script
#安装 Web 系统
sudo make install

echo "extension=yaf.so" >> /etc/php.ini
echo "" >> /etc/php.ini
echo "[redis]" >> /etc/php.ini
echo "extension=redis.so" >> /etc/php.ini
echo "" >> /etc/php.ini
echo "[ESL]" >> /etc/php.ini
echo "extension=ESL.so" >> /etc/php.ini


mysql -u root mysql
UPDATE user SET password=PASSWORD("tx4pn28y7n4cy735kv") WHERE user='root';
FLUSH PRIVILEGES;
quit

tee /etc/my.cnf <<-'EOF'
[mysqld]
datadir=/var/lib/mysql
socket=/var/lib/mysql/mysql.sock
symbolic-links=0
bind-address=127.0.0.1
port=3306
character-set-server=utf8
default_storage_engine=MyISAM
max_connections=160
interactive_timeout=310000
wait_timeout=31000
query_cache_size=48M
table_cache=320
tmp_table_size=52M
thread_cache_size=8
sort_buffer_size=256K
innodb_thread_concurrency=8
myisam-recover=FORCE
max_allowed_packet=32M
innodb_file_per_table=1

[mysqld_safe]
log-error=/var/log/mariadb/mariadb.log
pid-file=/var/run/mariadb/mariadb.pid
EOF

systemctl restart mariadb.service

sudo chown -R nginx:pbx /var/www/*
sudo chmod -R 777 /var/www/*
sudo chown -R nginx:pbx /var/cdr/*
sudo chmod -R 777 /var/cdr/*
sudo chown -R root:pbx /var/record
sudo chmod -R 777 /var/record/
sudo chown -R root:pbx /usr/local/freeswitch/
sudo chown -R root:pbx /usr/local/freeswitch/conf
sudo chmod -R 777 /usr/local/freeswitch/*
sudo chmod -R 777 /usr/local/freeswitch/conf/*
sudo chmod 777 /usr/bin/fs_cli
sudo chown root:pbx /etc/systemd/system/freeswitch.service
sudo chmod 777 /etc/systemd/system/freeswitch.service
chmod 644 /etc/my.cnf
chown -R mysql:mysql /var/lib/mysql

路由规则   ^(.*)$



#当root密码丢失的时候

mysql -u root -p

vim /etc/my.cnf
mysqld 加入skip-grant-tables
systemctl restart mariadb.service
mysql -u root mysql
UPDATE user SET password=PASSWORD("tx4pn28y7n4cy735kv") WHERE user='root';
FLUSH PRIVILEGES;

vim /etc/php.ini

[yaf]
yaf.environ = "product"
yaf.cache_config = 0
yaf.name_suffix = 1
yaf.name_separator = ""
yaf.forward_limit = 5
yaf.use_namespace = 1
yaf.use_spl_autoload = 0
extension=yaf.so

[redis]
extension=redis.so

[ESL]
extension=ESL.so



先安装依赖包：
yum install git gcc-c++ autoconf automake libtool wget python ncurses-devel zlib-devel libjpeg-devel openssl-devel e2fsprogs-devel sqlite-devel libcurl-devel pcre-devel speex-devel ldns-devel libedit-devel

#tar zxvf  freeswitch-1.6.0.tar.gz 
#cd freeswitch-1.6.0
#./configure 
#make

make 报错：
make[4]: Entering directory `/usr/local/src/freeswitch-1.6.0/src/mod/applications/mod_fsv'
Makefile:797: *** You must install libyuv-dev to build mod_fsv.  Stop.
    
解决：
（1）下载libyuv源码并编译

cd freeswitch/libs
git clone https://freeswitch.org/stash/scm/sd/libyuv.git
cd libyuv
make -f linux.mk CXXFLAGS="-fPIC -O2 -fomit-frame-pointer -Iinclude/"
make install
cp /usr/lib/pkgconfig/libyuv.pc /usr/lib64/pkgconfig/

（如果只是安装libyuv，接下来还会有报错，我把我报错而需要安装的文件统一罗列如下）

　　　　　　（2）下载libvpx源码并编译

cd ..
git clone https://freeswitch.org/stash/scm/sd/libvpx.git
cd libvpx
./configure --enable-pic --disable-static --enable-shared
（如果出现Configuration failed。错误原因为：Neither yasm nor nasm have been found，则参考以下“※”解决该错误.）
make
make install
cp /usr/local/lib/pkgconfig/vpx.pc /usr/lib64/pkgconfig/

　　　　　　（※）下载yasm并编译

yasm是一个汇编编译器，是nasm的升级版
                        可以直接yum install yasm
                        
                    或者下载源码包安装
yasm下载地址：http://www.tortall.net/projects/yasm/releases/
yasm解压命令：tar -zxvf ****.tar.gz
yasm编译安装：① ./configure， ② make, ③make install
yasm安装完毕之后回到第二步重新安装libvpx

　　　　　　（3）下载opus并编译

cd ..
git clone https://freeswitch.org/stash/scm/sd/opus.git
cd opus
./autogen.sh
./configure
make
make install
cp /usr/local/lib/pkgconfig/opus.pc /usr/lib64/pkgconfig

　　　　　　（4）下载libpng并编译

cd ..
git clone https://freeswitch.org/stash/scm/sd/libpng.git
cd libpng
./configure
make
make install
cp /usr/local/lib/pkgconfig/libpng* /usr/lib64/pkgconfig/


    　下载并安装以上四个依赖文件后，重新执行FreeSWITCH的“./configure”之后，“make && make install”安装FreeSWITCH了

    又出现了下面的错误
     CXX    mod_lua_la-mod_lua.lo
mod_lua.cpp:37:17: error: lua.h: No such file or directory
mod_lua.cpp:38:21: error: lauxlib.h: No such file or directory
mod_lua.cpp:39:20: error: lualib.h: No such file or directory
    解决方法：
    yum install lua lua-devel

又出现了下面的错误：
make[4]: Entering directory `/usr/local/src/freeswitch-1.6.0/src/mod/formats/mod_sndfile'
Makefile:796: *** You must install libsndfile-dev to build mod_sndfile.  Stop
   解决方法：
下载包libsndfile-1.0.26.tar.gz 上传到服务器
      下载地址  http://www.mega-nerd.com/libsndfile/#Download
    tar zxvf  libsndfile-1.0.26.tar.gz 
    ./configure    
    make
    make install
    cp /usr/local/lib/pkgconfig/sndfile.pc /usr/lib64/pkgconfig
   重新执行重新执行FreeSWITCH的“./configure”，再make   make install
    
freeswitch  make 成功后执行
make install
然后根据需要安装语音包
make cd-sounds-install 
make cd-moh-install
安装简单的配置文件
make samples

以上便完成了安装，但是启动的时候又报错了：
root@localhost bin]# ./freeswitch 
./freeswitch: error while loading shared libraries: libyuv.so: cannot open shared object file: No such file or directory

解决办法： cp /usr/lib/libyuv.so /usr/lib64/

然后就可以正常启动的
