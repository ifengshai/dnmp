<?php

namespace app\api\controller;

use app\common\controller\Api;

/**
 * 供应链接口类
 */
class Scm extends Api
{
    protected $noNeedLogin = ['login'];
    protected $noNeedRight = '*';

    public function _initialize()
    {
        parent::_initialize();

        //校验Token
        $this->auth->match($this->noNeedLogin) || $this->check() || $this->error(__('Token invalid, please log in again'), [], 400);
    }

    /**
     * 登录
     *
     * @参数 string account  账号
     * @参数 string password  密码
     * @return mixed
     */
    public function login()
    {
        $account = $this->request->request('account');
        $password = $this->request->request('password');
        empty($account) && $this->error(__('Username can not be empty'));
        empty($password) && $this->error(__('Password can not be empty'));

        if ($this->auth->login($account, $password)) {
            $user = $this->auth->getUserinfo();
            $data = ['token' => $user['token']];
            $this->success(__('Logged in successful'), $data,200);
        } else {
            $this->error($this->auth->getError());
        }
    }

}
