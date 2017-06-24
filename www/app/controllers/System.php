<?php

/*
 * The System Controller
 * Link http://github.com/typefo/pbx-mon
 * By typefo <typefo@qq.com>
 */

class SystemController extends Yaf\Controller_Abstract {

    public function statusAction() {
        $system = new SystemModel();

        $this->getView()->assign('status', $system->sysInfo());
        return true;
	}

    public function optionAction() {
        $request = $this->getRequest();
        $config = new ConfigModel();

        $key = $request->getQuery('key');
        if ($key) {
            $val = intval($request->getQuery('val'));
            if (in_array($val, [0, 1], true)) {
                $config->set($key, $val);
            }

            $response['status'] = 200;
            $response['message'] = 'success';
            header('Content-type: application/json');
            echo json_encode($response);
            return false;
        }

        $this->getView()->assign('config', $config->getAll());
        return true;
    }

    public function securityAction() {
        $acl = new AclModel();
        $request = $this->getRequest();

        if ($request->isPost()) {
            $ip = $request->getPost('ip');
            $acl->add($ip);
            $response['status'] = 200;
            $response['message'] = 'success';
            header('Content-type: application/json');
            echo json_encode($response);
            return false;
        }

        $op = $request->getQuery('op');
        if ($op && $op === 'delete') {
            $ip = intval($request->getQuery('ip'));
            $acl->delete(long2ip($ip));
            $response['status'] = 200;
            $response['message'] = 'success';
            header('Content-type: application/json');
            echo json_encode($response);
            return false;
        }

        $this->getView()->assign('data', $acl->getAll());
        return true;
    }

    public function aboutAction() {
        return true;
    }

    public function passwordAction() {
        $message = null;
        $request = $this->getRequest();
	    
        if ($request->isPost()) {
            $oldpassword = $request->getPost('oldpassword');
            $newpassword = $request->getPost('newpassword');
            $user = new UserModel('admin');
            if ($user->changePassword($oldpassword, $newpassword)) {
                $message = '<div class="alert alert-success alert-dismissible" style="text-align:center" role="alert">'.
                           '<button type="button" class="close" data-dismiss="alert" aria-label="Close">'.
                           '<span aria-hidden="true">&times;</span></button>'.
                           '<strong>提示: </strong> 密码修改成功! </div>';
            } else {
                $message = '<div class="alert alert-warning alert-dismissible" style="text-align:center" role="alert">'.
                           '<button type="button" class="close" data-dismiss="alert" aria-label="Close">'.
                           '<span aria-hidden="true">&times;</span></button>'.
                           '<strong>警告: </strong> 密码修改失败，请检查原始密码是否正确，新密码长度必须大于 6 位 </div>';
            }
        }

        $this->getView()->assign("message", $message);
        return true;
    }
}


