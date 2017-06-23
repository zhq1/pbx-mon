<?php

/*
 * The Login Controller
 * Link http://github.com/typefo/pbx-mon
 * By typefo <typefo@qq.com>
 */

use Tool\Filter;

class LoginController extends Yaf\Controller_Abstract {
    public function indexAction() {
        $request = $this->getRequest();
        $response = $this->getResponse();
        $url = 'http://' . $_SERVER['SERVER_ADDR'] . ':' . $_SERVER['SERVER_PORT'];

        /* Check login action */
        if ($request->isPost()) {
            echo '1111111111111111111<br>';
            $data = $request->getPost();
            $login = new LoginModel($data);

            echo '2222222222222222222<br>';
            /* Verify username and password */
            if (!$login->verify()) {
                echo '333333333333333333333333333<br>';
                goto output;
            }

            echo '444444444444444444444444<br>';
            /* Check ip whitelist */
            $config = new ConfigModel();
            if ($config->get('config.security') === '1') {
                echo '5555555555555555555555555555555<br>';
                if (!$login->checkAcl($_SERVER['REMOTE_ADDR'])) {
                    echo '666666666666666666666666<br>';
                    goto output;
                }
            }
            
            echo '#################################<br>';
            exit;
            $session = Yaf\Session::getInstance();
            $session->set('login', true);
            $response->setRedirect($url . '/cdr');
            $response->response();
            return false;
        }
        

        output:
        echo '@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@<br>';
        exit;
        $response->setRedirect($url);
        $response->response();
        return false;
    }
}


