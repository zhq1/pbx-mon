
install:
	echo -en "->\033[37m install mariadb configuration    ";echo -e "\033[32m [ OK ] \033[0m"
	/usr/bin/cp -f my.cnf /etc/my.cnf
	echo -en "->\033[37m install php configuration        ";echo -e "\033[32m [ OK ] \033[0m"
	/usr/bin/cp -f php.ini /etc/php.ini
	echo -en "->\033[37m install nginx configuration      ";echo -e "\033[32m [ OK ] \033[0m"
	/usr/bin/cp -f nginx.conf /etc/nginx/nginx.conf
	/usr/bin/rm -rf /usr/local/freeswitch/conf
	/usr/bin/cp -Rf conf /usr/local/freeswitch/conf
	/usr/bin/chown -R root:pbx /usr/local/freeswitch/conf
	/usr/bin/chmod -R g+w /usr/local/freeswitch/conf
	echo -en "->\033[37m install freeswitch configuration ";echo -e "\033[32m [ OK ] \033[0m"

