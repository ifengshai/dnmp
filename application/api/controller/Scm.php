<?php

namespace app\api\controller;

use app\common\controller\Api;

/**
 * 供应链接口类
 */
class Scm extends Api
{
    protected $noNeedLogin = '*';
    protected $noNeedRight = '*';
    protected $menu = [//PDA菜单
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

    public function _initialize()
    {
        parent::_initialize();

        //校验Token
        $this->auth->match(['login']) || $this->check() || $this->error(__('Token invalid, please log in again'), [], 401);

        //校验请求类型
//        $this->request->isPost() || $this->error(__('Request method must be post'), [], 402);
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
        empty($account) && $this->error(__('Username can not be empty'), [], 403);
        empty($password) && $this->error(__('Password can not be empty'), [], 404);

        if ($this->auth->login($account, $password)) {
            $user = $this->auth->getUserinfo();
            $data = ['token' => $user['token']];
            $this->success(__('Logged in successful'), $data,200);
        } else {
            $this->error($this->auth->getError(), [], 405);
        }
    }

    /**
     * 首页
     *
     * @return mixed
     */
    public function index()
    {
        //重新组合菜单
        $list = [];
        foreach($this->menu as $key=>$value){
            foreach($value['menu'] as $k=>$val){
                //校验菜单展示权限
                if(!$this->auth->check($val['link'])){
                    unset($value['menu'][$k]);
                }
                unset($val['link']);
            }
            if(!empty($value['menu'])){
                $list[] = $value;
            }
        }

        $this->success('', ['list' => $list],200);
    }

    /**
     * 质检列表
     *
     * @参数 string query  查询内容
     * @参数 int status  状态
     * @参数 int is_stock  是否创建入库单
     * @参数 string start_time  开始时间
     * @参数 string end_time  结束时间
     * @参数 int page  页码
     * @参数 int page_size  每页显示数量
     * @return mixed
     */
    public function quality_list()
    {
        $query = $this->request->request('query');
        $status = $this->request->request('status');
        $is_stock = $this->request->request('is_stock');
        $start_time = $this->request->request('start_time');
        $end_time = $this->request->request('end_time');
        $page = $this->request->request('page');
        $page_size = $this->request->request('page_size');

        empty($page) && $this->error(__('Page can not be empty'), [], 406);
        empty($page_size) && $this->error(__('Page size can not be empty'), [], 407);

        $where = [];
        if($query){
            $where['a.check_order_number|a.create_person|b.sku|c.purchase_number|c.create_person'] = ['like', '%' . $query . '%'];
        }
        if($status){
            $where['a.status'] = $status;
        }
        if($is_stock){
            $where['a.is_stock'] = $is_stock;
        }
        if($start_time && $end_time){
            $where['a.createtime'] = ['between', [$start_time, $end_time]];
        }

        $offset = ($page - 1) * $page_size;
        $limit = $page_size;

        $_check = new \app\admin\model\warehouse\Check;
        $list = $_check
            ->alias('a')
            ->where($where)
            ->field('a.id,a.check_order_number,a.createtime,a.status,c.purchase_number')
            ->join(['fa_check_order_item' => 'b'], 'a.id=b.check_id','left')
            ->join(['fa_purchase_order' => 'c'], 'a.purchase_id=c.id')
            ->order('a.createtime', 'desc')
            ->limit($offset, $limit)
            ->select();
        $list = collection($list)->toArray();

        $status = [ 0=>'新建',1=>'待审核',2=>'已审核',3=>'已拒绝',4=>'已取消' ];
        foreach($list as $key=>$value){
            $list[$key]['status'] = $status[$value['status']];
            $list[$key]['cancel_show'] = 0 == $value['status'] ? 1 : 0;
        }

        $this->success('', ['list' => $list],200);
    }

    /**
     * 取消质检
     *
     * @参数 int id  质检单ID
     * @return mixed
     */
    public function quality_cancel()
    {
        $id = $this->request->request('id');
        empty($id) && $this->error(__('Id can not be empty'), [], 408);

        $_check = new \app\admin\model\warehouse\Check;
        $row = $_check->get($id);
        0 != $row['status'] && $this->error(__('只有新建状态才能取消'), [], 409);

        $res = $_check->allowField(true)->isUpdate(true, ['id'=>$id])->save(['status'=>4]);
        $res ? $this->success('取消成功', [],200) : $this->error(__('取消失败'), [], 410);
    }

}
