<?php

/*
 * The Test Controller
 * Link http://github.com/typefo/pbx-mon
 * By typefo <typefo@qq.com>
 */

class TestController extends Yaf\Controller_Abstract {

    public function indexAction() {
    	$config = new ConfigModel();
    	echo '<pre>';
    	var_dump($config->getAll());
    	echo '</pre>';
        return false;
	}
}


