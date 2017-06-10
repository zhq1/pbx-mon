<?php

/*
 * The Interface Controller
 * Link http://github.com/typefo/pbx-mon
 * By typefo <typefo@qq.com>
 */

class InterfaceController extends Yaf\Controller_Abstract {

    public function indexAction() {
        $interface = new InterfaceModel();
        $this->getView()->assign("data", $interface->getAll());
        return true;
	}

    public function createAction() {
        $request = $this->getRequest();

        if ($request->isPost()) {
            $interface = new InterfaceModel();
            $interface->create($request->getPost());
            $url = 'http://' . $_SERVER['SERVER_ADDR'] . ':' . $_SERVER['SERVER_PORT'] . '/interface';
            $response = $this->getResponse();
            $response->setRedirect($url);
            $response->response();
            return false;
        }
        
        return true;
    }

    public function editAction() {
        $request = $this->getRequest();
        $interface = new InterfaceModel();

        if ($request->isPost()) {
            $interface->change($request->getQuery('id'), $request->getPost());
            $url = 'http://' . $_SERVER['SERVER_ADDR'] . ':' . $_SERVER['SERVER_PORT'] . '/interface';
            $response = $this->getResponse();
            $response->setRedirect($url);
            $response->response();
            return false;
        }
        
        $this->getView()->assign('data', $interface->get($request->getQuery('id')));
        return true;
    }

    public function deleteAction() {
        $id = $this->getRequest()->getQuery('id');
        $interface = new InterfaceModel();

        $interface->delete($id);
        
        return false;
    }
}


