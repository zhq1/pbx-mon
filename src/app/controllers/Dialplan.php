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
            $data = $request->getPost();
            $dialplan->create($data);
            $response = $this->getResponse();
            $response->setRedirect('/dialplan?rid=' . intval($data['rid']));
            $response->response();
            return false;
        }

        return true;
    }

    public function editAction() {
        $request = $this->getRequest();
        $dialplan = new DialplanModel();

        if ($request->isPost()) {
            $data = $request->getPost();
            $dialplan->change($request->getQuery('id'), $data);
            $response = $this->getResponse();
            $response->setRedirect('/dialplan?rid=' . intval($data['rid']));
            $response->response();
            return false;
        }

        $interface = new InterfaceModel();
        $response['status'] = 200;
        $response['message'] = "success";
        $response['sofia'] = $interface->getAll();
        $response['data'] = $dialplan->get($request->getQuery('id'));
        header('Content-type: application/json');
        echo json_encode($response);
        return false;
    }

    public function deleteAction() {
        $id = $this->getRequest()->getQuery('id');
        $dialplan = new DialplanModel();
        $dialplan->delete($id);
        $response['status'] = 200;
        $response['message'] = "success";
        header('Content-type: application/json');
        echo json_encode($response);
        return false;
    }

    public function upAction() {
        $dialplan = new DialplanModel();
        $id = $this->getRequest()->getQuery('id');
        $dialplan->setPriority('up', $id);
        $response['status'] = 200;
        $response['message'] = "success";
        header('Content-type: application/json');
        echo json_encode($response);
        return false;
    }

    public function downAction() {
        $dialplan = new DialplanModel();
        $id = $this->getRequest()->getQuery('id');
        $dialplan->setPriority('down', $id);
        $response['status'] = 200;
        $response['message'] = "success";
        header('Content-type: application/json');
        echo json_encode($response);
        return false;
    }
}

