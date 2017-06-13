<?php

/*
 * The Dialplan Controller
 * Link http://github.com/typefo/pbx-mon
 * By typefo <typefo@qq.com>
 */

class DialplanController extends Yaf\Controller_Abstract {

    public function indexAction() {
        $rid = $this->getRequest()->getQuery('rid');
        $interface = new InterfaceModel();
        $interfaces = $interface->getAll();

        $dialplan = new DialplanModel();
        $data = $dialplan->getAll($rid);
        foreach ($data as &$obj) {
            switch ($obj['type']) {
                case '1':
                    $obj['type'] = '主叫号码';
                    break;
                case '2':
                    $obj['type'] = '被叫号码';
                    break;
                default:
                    $obj['type'] = 'unknown';
                    break;
            }

            $sofia = 'unknown';
            foreach ($interfaces as $res) {
                if ($obj['sofia'] == $res['id']) {
                    $sofia = $res['name'];
                }
            }

            $obj['sofia'] = $sofia;
        }

        $route = new RouteModel();
        $this->getView()->assign('route', $route->get($rid));
        $this->getView()->assign('data', $data);
        $this->getView()->assign('interfaces', $interfaces);
        return true;
	}

    public function createAction() {
        $request = $this->getRequest();

        if ($request->isPost()) {
            $dialplan = new DialplanModel();
            $dialplan->create($request->getPost());
            $url = 'http://' . $_SERVER['SERVER_ADDR'] . ':' . $_SERVER['SERVER_PORT'] . '/dialplan?rid=' . $rid;
            $response = $this->getResponse();
            $response->setRedirect($url);
            $response->response();
            return false;
        }

        return true;
    }

    public function editAction() {
        $request = $this->getRequest();
        $route = new RouteModel();

        if ($request->isPost()) {
            $route->change($request->getQuery('id'), $request->getPost());
            $url = 'http://' . $_SERVER['SERVER_ADDR'] . ':' . $_SERVER['SERVER_PORT'] . '/route';
            $response = $this->getResponse();
            $response->setRedirect($url);
            $response->response();
            return false;
        }

        $response['status'] = 200;
        $response['message'] = "success";
        $response['data'] = $route->get($request->getQuery('id'));
        header('Content-type: application/json');
        echo json_encode($response);
        return false;
    }

    public function deleteAction() {
        $id = $this->getRequest()->getQuery('id');
        $route = new RouteModel();
        $route->delete($id);

        return false;
    }

    public function upAction() {
        $rid = $this->getRequest()->getQuery('rid');
        return false;
    }

    public function downAction() {
        return false;
    }
}


