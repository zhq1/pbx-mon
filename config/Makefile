
install:
	/usr/bin/cp -f my.cnf /etc/my.cnf
	echo -e "->\033[37m install mariadb configuration    \033[32m [ OK ] \033[0m"
	/usr/bin/cp -f php.ini /etc/php.ini
	echo -e "->\033[37m install php configuration        \033[32m [ OK ] \033[0m"
	/usr/bin/cp -f nginx.conf /etc/nginx/nginx.conf
	echo -e "->\033[37m install nginx configuration      \033[32m [ OK ] \033[0m"
	/usr/bin/rm -rf /usr/local/freeswitch/conf
	/usr/bin/cp -Rf conf /usr/local/freeswitch/conf
	/usr/bin/chown -R root:pbx /usr/local/freeswitch/conf
	/usr/bin/chmod -R g+w /usr/local/freeswitch/conf
	echo -e "->\033[37m install freeswitch configuration \033[32m [ OK ] \033[0m"

