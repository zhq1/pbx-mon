<?php

/*
 * The Api Controller
 * Link http://github.com/typefo/pbx-mon
 * By typefo <typefo@qq.com>
 */

class ApiController extends Yaf\Controller_Abstract {

    public function serverAction() {
        $server = new ServerModel();
        $response['status'] = 200;
        $response['message'] = 'success';
        $response['data'] = $server->getAll();
        header('Content-type: application/json');
        echo json_encode($response);
        return false;
	}
}


