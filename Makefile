
install:
	/usr/bin/cp -Rf cdr /var
	/usr/bin/cp -Rf www /var
	/usr/bin/chown nginx:pbx /var/cdr
	/usr/bin/chown nginx:pbx /var/www
	/usr/bin/mkdir -p /var/record
	/usr/bin/chown root:pbx /var/record
	/usr/bin/ln -s /usr/local/freeswitch/bin/fs_cli /usr/bin/fs_cli
	/usr/bin/ln -s /usr/local/freeswitch/bin/freeswitch /usr/bin/freeswitch
