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
        $this->auth->match($this->noNeedLogin) || $this->check() || $this->error(__('Token invalid, please log in again'), [], 401);
    }

    /**
     * 检测Token
     *
     * @参数 string token  加密值
     * @return bool
     */
    protected function check()
    {
        $this->auth->init($this->request->request('token'));
        return $this->auth->id ? true : false;
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
        empty($account) && $this->error(__('Username can not be empty'), [], 402);
        empty($password) && $this->error(__('Password can not be empty'), [], 403);

        if ($this->auth->login($account, $password)) {
            $user = $this->auth->getUserinfo();
            $data = ['token' => $user['token']];
            $this->success(__('Logged in successful'), $data,200);
        } else {
            $this->error($this->auth->getError(), [], 404);
        }
    }

    /**
     * 首页
     *
     * @return mixed
     */
    public function index()
    {
        $list = [
            [
               'title'=>'配货管理',
               'menu'=>[
                   ['name'=>'配货', 'link'=>''],
                   ['name'=>'镜片分拣', 'link'=>''],
                   ['name'=>'配镜片', 'link'=>''],
                   ['name'=>'加工', 'link'=>''],
                   ['name'=>'成品质检', 'link'=>''],
                   ['name'=>'合单', 'link'=>''],
                   ['name'=>'审单', 'link'=>''],
                   ['name'=>'跟单', 'link'=>''],
                   ['name'=>'工单', 'link'=>'']
               ],
            ],
            [
                'title'=>'质检管理',
                'menu'=>[
                    ['name'=>'质检单', 'link'=>'warehouse/check'],
                    ['name'=>'物流检索', 'link'=>'warehouse/logistics_info/index']
                ],
            ],
            [
                'title'=>'出入库管理',
                'menu'=>[
                    ['name'=>'出库单', 'link'=>'warehouse/outstock'],
                    ['name'=>'入库单', 'link'=>'warehouse/instock']
                ],
            ],
        ];

        //重新组合菜单
        $data = [];
        foreach($list as $key=>$value){
            foreach($value['menu'] as $k=>$val){
                //校验菜单展示权限
                if(!$this->auth->check($val['link'])){
                    unset($value['menu'][$k]);
                }
                unset($val['link']);
            }
            if(!empty($value['menu'])){
                $data[] = $value;
            }
        }

        $this->success('', ['list' => $data],200);
    }

}
