<?php

/*
 * The Server Controller
 * Link http://github.com/typefo/pbx-mon
 * By typefo <typefo@qq.com>
 */

class ServerController extends Yaf\Controller_Abstract {

    public function indexAction() {
        $server = new ServerModel();
        $route = new RouteModel();
        $this->getView()->assign("data", $server->getAll());
        $this->getView()->assign("routes", $route->getAll());
        return true;
	}

    public function createAction() {
        $request = $this->getRequest();

        if ($request->isPost()) {
            $server = new ServerModel();
            $server->create($request->getPost());
            $url = 'http://' . $_SERVER['SERVER_ADDR'] . ':' . $_SERVER['SERVER_PORT'] . '/server';
            $response = $this->getResponse();
            $response->setRedirect($url);
            $response->response();
            return false;
        }
        
        $route = new RouteModel();
        $this->getView()->assign('routes', $route->getAll());
        return true;
    }

    public function editAction() {
        $request = $this->getRequest();
        $server = new ServerModel();

        if ($request->isPost()) {
            $server->change($request->getQuery('id'), $request->getPost());
            $url = 'http://' . $_SERVER['SERVER_ADDR'] . ':' . $_SERVER['SERVER_PORT'] . '/server';
            $response = $this->getResponse();
            $response->setRedirect($url);
            $response->response();
            return false;
        }
        
        $route = new RouteModel();
        $this->getView()->assign('routes', $route->getAll());
        $this->getView()->assign('data', $server->get($request->getQuery('id')));
        return true;
    }

    public function deleteAction() {
        $id = $this->getRequest()->getQuery('id');
        $server = new ServerModel();

        $server->delete($id);
        
        return false;
    }
}


