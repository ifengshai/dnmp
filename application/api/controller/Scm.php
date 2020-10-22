<?php

namespace app\api\controller;

use app\common\controller\Api;

/**
 * 供应链接口类
 * @author lzh
 * @since 2020-10-20
 */
class Scm extends Api
{
    protected $noNeedLogin = '*';
    protected $noNeedRight = '*';
    protected $menu = [//PDA菜单
        [
            'title'=>'配货管理',
            'menu'=>[
                ['name'=>'配货', 'link'=>'', 'href'=>''],
                ['name'=>'镜片分拣', 'link'=>'', 'href'=>''],
                ['name'=>'配镜片', 'link'=>'', 'href'=>''],
                ['name'=>'加工', 'link'=>'', 'href'=>''],
                ['name'=>'成品质检', 'link'=>'', 'href'=>''],
                ['name'=>'合单', 'link'=>'', 'href'=>''],
                ['name'=>'审单', 'link'=>'', 'href'=>''],
                ['name'=>'跟单', 'link'=>'', 'href'=>''],
                ['name'=>'工单', 'link'=>'', 'href'=>'']
            ],
        ],
        [
            'title'=>'质检管理',
            'menu'=>[
                ['name'=>'质检单', 'link'=>'warehouse/check', 'href'=>''],
                ['name'=>'物流检索', 'link'=>'warehouse/logistics_info/index', 'href'=>'']
            ],
        ],
        [
            'title'=>'出入库管理',
            'menu'=>[
                ['name'=>'出库单', 'link'=>'warehouse/outstock', 'href'=>''],
                ['name'=>'入库单', 'link'=>'warehouse/instock', 'href'=>'']
            ],
        ],
    ];

    /**
     * 检测Token
     *
     * @参数 string token  加密值
     * @author lzh
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
        $this->request->isPost() || $this->error(__('Request method must be post'), [], 402);
    }

    /**
     * 登录
     *
     * @参数 string account  账号
     * @参数 string password  密码
     * @author lzh
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
     * @author lzh
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
                unset($value['menu'][$k]['link']);
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
     * @author lzh
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

        //获取质检单列表数据
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
     * @author lzh
     * @return mixed
     */
    public function quality_cancel()
    {
        $id = $this->request->request('id');
        empty($id) && $this->error(__('Id can not be empty'), [], 408);

        //检测质检单状态
        $_check = new \app\admin\model\warehouse\Check;
        $row = $_check->get($id);
        0 != $row['status'] && $this->error(__('只有新建状态才能取消'), [], 409);

        $res = $_check->allowField(true)->isUpdate(true, ['id'=>$id])->save(['status'=>4]);
        $res ? $this->success('取消成功', [],200) : $this->error(__('取消失败'), [], 410);
    }

    /**
     * 新建质检单页面
     *
     * @参数 int id  物流单ID
     * @author lzh
     * @return mixed
     */
    public function quality_add()
    {
        $id = $this->request->request('id');
        empty($id) && $this->error(__('Id can not be empty'), [], 411);

        //获取物流单数据
        $_logistics_info = new \app\admin\model\warehouse\LogisticsInfo;
        $logistics_data = $_logistics_info->where('id', $id)->field('id,purchase_id,batch_id')->find();
        empty($logistics_data) && $this->error(__('物流单不存在'), [], 412);

        //获取采购单数据
        $_purchase_order = new \app\admin\model\purchase\PurchaseOrder;
        $purchase_data = $_purchase_order->where('id', $logistics_data['purchase_id'])->field('purchase_number,supplier_id')->find();
        empty($purchase_data) && $this->error(__('采购单不存在'), [], 413);

        $_purchase_order_item = new \app\admin\model\purchase\PurchaseOrderItem;
        $order_item_list = $_purchase_order_item
            ->where(['purchase_id'=>$logistics_data['purchase_id']])
            ->field('sku,supplier_sku')
            ->select();
        $order_item_list = collection($order_item_list)->toArray();
        $order_item = array_column($order_item_list,NULL,'sku');

        //获取供应商数据
        $_supplier = new \app\admin\model\purchase\Supplier;
        $supplier_data = $_supplier->where('id', $purchase_data['supplier_id'])->field('supplier_name')->find();
        empty($supplier_data) && $this->error(__('供应商不存在'), [], 414);

        //获取采购批次数据
        $batch = 0;
        if($logistics_data['batch_id']){
            $_purchase_batch = new \app\admin\model\purchase\PurchaseBatch;
            $batch_data = $_purchase_batch->where('id', $logistics_data['batch_id'])->field('batch')->find();
            empty($batch_data) && $this->error(__('采购单批次不存在'), [], 415);
            $batch = $batch_data['batch'];
            $_purchase_batch_item = new \app\admin\model\purchase\PurchaseBatchItem;
            $item_list = $_purchase_batch_item
                ->where(['purchase_batch_id'=>$logistics_data['batch_id']])
                ->field('sku')
                ->select();
            $item_list = collection($item_list)->toArray();
            foreach($item_list as $key=>$value){
                $item_list[$key]['supplier_sku'] = isset($order_item[$value['sku']]['supplier_sku']) ? $order_item[$value['sku']]['supplier_sku'] : '';
            }
        }else{
            $item_list = $order_item_list;
        }

        //质检单号
        $info =[
            'check_order_number'=>'QC' . date('YmdHis') . rand(100, 999) . rand(100, 999),
            'purchase_number'=>$purchase_data['purchase_number'],
            'supplier_name'=>$supplier_data['supplier_name'],
            'batch'=>$batch,
            'item_list'=>$item_list,
        ];

        $this->success('', ['info' => $info],200);
    }

}
