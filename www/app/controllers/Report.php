<?php

/*
 * The Report Controller
 * Link http://github.com/typefo/pbx-mon
 * By typefo <typefo@qq.com>
 */

class ReportController extends Yaf\Controller_Abstract {

    public function indexAction() {
    	$report = new ReportModel();
    	$this->getView()->assign("data", $report->getAll());
    	var_dump($report->getAll())
    	exit;
        return true;
	}
}


