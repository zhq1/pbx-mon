<?php

/*
 * The Play Controller
 * Link http://github.com/typefo/pbx-mon
 * By typefo <typefo@qq.com>
 */

class PlayController extends Yaf\Controller_Abstract {

    public function indexAction() {
        $request = $this->getRequest();
        $file = $request->getQuery('file');
        $record = '/record/'. $file;
        $this->getView()->assign("record", $record);
        return true;
    }
}
