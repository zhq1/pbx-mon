
install:
	/usr/bin/cp -f freeswitch.service /etc/systemd/system/freeswitch.service
	echo -e "->\033[37m install freeswitch service      \033[32m [ OK ] \033[0m"
	/usr/bin/systemctl enable freeswitch.service
	echo -e "->\033[37m enable freeswitch service       \033[32m [ OK ] \033[0m"
	/usr/bin/systemctl enable nginx.service
	echo -e "->\033[37m enable nginx service            \033[32m [ OK ] \033[0m"
	/usr/bin/systemctl enable php-fpm.service
	echo -e "->\033[37m enable php-fpm service          \033[32m [ OK ] \033[0m"
	/usr/bin/systemctl enable redis.service
	echo -e "->\033[37m enable redis service            \033[32m [ OK ] \033[0m"
	/usr/bin/systemctl enable mariadb.service
	echo -e "->\033[37m enable mariadb service          \033[32m [ OK ] \033[0m"
	/usr/bin/redis-cli < config.txt
	echo -e "->\033[37m redis database initialization   \033[32m [ OK ] \033[0m"
	/usr/bin/mysql < pbxmon.sql
	echo -e "->\033[37m mariadb database initialization \033[32m [ OK ] \033[0m"

