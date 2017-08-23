<?php

/*
 * The Route Controller
 * Link http://github.com/typefo/pbx-mon
 * By typefo <typefo@qq.com>
 */

class RouteController extends Yaf\Controller_Abstract {

    public function indexAction() {
        $route = new RouteModel();
        $data = $route->getAll();
        $dialplan = new DialplanModel();

        foreach ($data as &$obj) {
            $obj['count'] = $dialplan->getCount($obj['id']);
        }
        $this->getView()->assign("data", $data);
        return true;
	}

    public function createAction() {
        $request = $this->getRequest();

        if ($request->isPost()) {
            $route = new RouteModel();
            $route->create($request->getPost());
            $response = $this->getResponse();
            $response->setRedirect('/route');
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
            $response = $this->getResponse();
            $response->setRedirect('/route');
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
        $success = $route->delete($id);
        $response['status'] = $success ? 200 : 400;
        $response['message'] = $success ? 'success' : 'failed';
        header('Content-type: application/json');
        echo json_encode($response);
        return false;
    }

    public function syncAction() {
        $request = $this->getRequest();
        $system = new SystemModel();
        $rid = $this->getRequest()->getQuery('id');
        if ($system->regenPlan($rid)) {
            $system->reloadXml();
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


