<?php

/*
 * The System Controller
 * Link http://github.com/typefo/pbx-mon
 * By typefo <typefo@qq.com>
 */

class SystemController extends Yaf\Controller_Abstract {

    public function statusAction() {
        $system = new SystemModel();

        $this->getView()->assign("status", $system->sysInfo());
        return true;
	}

    public function optionAction() {
        return true;
	}

  public function clusterAction() {
    return true;
  }

  public function collectionAction() {
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


