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

        $this->getView()->assign('data', $route->get($request->getQuery('id')));
        return true;
    }

    public function deleteAction() {
        $id = $this->getRequest()->getQuery('id');
        $route = new RouteModel();

        $route->delete($id);

        return false;
    }

    public function dialplan() {
        return true;
    }
}


