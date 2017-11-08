
.PHONY: install config script

install:
	@/usr/bin/cp -Rf cdr /var
	@/usr/bin/chown nginx:pbx /var/cdr
	echo -en "->\033[37m install cdr module               ";echo -e "\033[32m [ OK ] \033[0m"
	@/usr/bin/cp -Rf www /var
	@/usr/bin/chown nginx:pbx /var/www
	echo -en "->\033[37m install web module               ";echo -e "\033[32m [ OK ] \033[0m"
	@/usr/bin/mkdir -p /var/record
	@/usr/bin/chown root:pbx /var/record
	echo -en "->\033[37m create recording directory       ";echo -e "\033[32m [ OK ] \033[0m"
	@/usr/bin/ln -s /usr/local/freeswitch/bin/fs_cli /usr/bin/fs_cli
	@/usr/bin/ln -s /usr/local/freeswitch/bin/freeswitch /usr/bin/freeswitch
	echo -en "->\033[37m create freeswitch relevant links ";echo -e "\033[32m [ OK ] \033[0m"

config:
	$(MAKE) --no-print-directory --quiet -C config

script:
	$(MAKE) --no-print-directory --quiet -C script
