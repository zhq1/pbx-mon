<?php

/*
 * The Route Controller
 * Link http://github.com/typefo/pbx-mon
 * By typefo <typefo@qq.com>
 */

class RouteController extends Yaf\Controller_Abstract {

    public function indexAction() {
        $route = new RouteModel();
        $this->getView()->assign("data", $route->getAll());
        return true;
	}

    public function createAction() {
        $request = $this->getRequest();

        if ($request->isPost()) {
            $route = new RouteModel();
            $route->create($request->getPost());
            $url = 'http://' . $_SERVER['SERVER_ADDR'] . ':' . $_SERVER['SERVER_PORT'] . '/route';
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

    public function dialplanAction() {
        $id = $this->getRequest()->getQuery('id');
        $interface = new InterfaceModel();
        $interfaces = $interface->getAll();

        $dialplan = new DialplanModel();
        $data = $dialplan->getAll();
        foreach ($data as &$obj) {
            $obj['type'] = $obj['type'] == 1 ? '主叫号码' : $obj['type'] == 2 ? '被叫号码' : 'nuknown';
            $sofia = 'unknown';
            foreach ($interfaces as $res) {
                if ($obj['sofia'] == $res['id']) {
                    $sofia = $res['name'];
                }
            }

            $obj['sofia'] = $sofia;
        }
        $this->getView()->assign("data", $route->getAll());
        return true;
    }

    public function syncAction() {
        $request = $this->getRequest();
        $system = new SystemModel();
        if ($system->regenPlan($this->getRequest()->getQuery('id'))) {
            $response['status'] = 200;
            $response['message'] = "success";
            header('Content-type: application/json');
            echo json_encode($response);
            return false;
        }

        $response['status'] = 400;
        $response['message'] = "sync failed";
        header('Content-type: application/json');
        echo json_encode($response);
        return false;
    }
}


