<?php

namespace app\api\controller;

use app\common\controller\Api;
use think\Db;
use think\Exception;
use think\exception\PDOException;
use think\exception\ValidateException;

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
     * 新建质检单页面
     *
     * @参数 int logistics_id  物流单ID
     * @author lzh
     * @return mixed
     */
    public function quality_add()
    {
        $logistics_id = $this->request->request('logistics_id');
        empty($logistics_id) && $this->error(__('物流单ID不能为空'), [], 411);

        //获取物流单数据
        $_logistics_info = new \app\admin\model\warehouse\LogisticsInfo;
        $logistics_data = $_logistics_info->where('id', $logistics_id)->field('id,purchase_id,batch_id')->find();
        empty($logistics_data) && $this->error(__('物流单不存在'), [], 412);

        //获取采购单数据
        $_purchase_order = new \app\admin\model\purchase\PurchaseOrder;
        $purchase_data = $_purchase_order->where('id', $logistics_data['purchase_id'])->field('purchase_number,supplier_id,replenish_id')->find();
        empty($purchase_data) && $this->error(__('采购单不存在'), [], 413);

        //获取采购单商品数据
        $_purchase_order_item = new \app\admin\model\purchase\PurchaseOrderItem;
        $order_item_list = $_purchase_order_item
            ->where(['purchase_id'=>$logistics_data['purchase_id']])
            ->field('sku,supplier_sku,purchase_num')
            ->select();
        $order_item_list = collection($order_item_list)->toArray();

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
                ->field('sku,arrival_num as should_arrival_num')
                ->select();
            $item_list = collection($item_list)->toArray();
            $order_item = array_column($order_item_list,NULL,'sku');
            foreach($item_list as $key=>$value){
                $item_list[$key]['supplier_sku'] = isset($order_item[$value['sku']]['supplier_sku']) ?? '';
                $item_list[$key]['purchase_num'] = isset($order_item[$value['sku']]['purchase_num']) ?? 0;
            }
        }else{
            $item_list = [];
            foreach($order_item_list as $key=>$value){
                $value['should_arrival_num'] = $value['purchase_num'];
                $item_list[] = $value;
            }
        }

        //质检单所需数据
        $info =[
            'check_order_number'=>'QC' . date('YmdHis') . rand(100, 999) . rand(100, 999),
            'purchase_number'=>$purchase_data['purchase_number'],
            'supplier_name'=>$supplier_data['supplier_name'],
            'batch'=>$batch,
            'purchase_id'=>$logistics_data['purchase_id'],
            'supplier_id'=>$purchase_data['supplier_id'],
            'batch_id'=>$logistics_data['batch_id'],
            'replenish_id'=>$purchase_data['replenish_id'],
            'item_list'=>$item_list,
        ];

        $this->success('', ['info' => $info],200);
    }

    /**
     * 编辑质检单页面
     *
     * @参数 int check_id  质检单ID
     * @author lzh
     * @return mixed
     */
    public function quality_edit()
    {
        $check_id = $this->request->request('check_id');
        empty($check_id) && $this->error(__('质检单ID不能为空'), [], 411);

        //获取质检单数据
        $_check = new \app\admin\model\warehouse\Check;
        $check_data = $_check->where('id', $check_id)->field('purchase_id,batch_id,check_order_number,supplier_id')->find();
        empty($check_data) && $this->error(__('质检单不存在'), [], 412);

        //获取采购单数据
        $_purchase_order = new \app\admin\model\purchase\PurchaseOrder;
        $purchase_data = $_purchase_order->where('id', $check_data['purchase_id'])->field('purchase_number')->find();
        empty($purchase_data) && $this->error(__('采购单不存在'), [], 413);

        //获取供应商数据
        $_supplier = new \app\admin\model\purchase\Supplier;
        $supplier_data = $_supplier->where('id', $check_data['supplier_id'])->field('supplier_name')->find();
        empty($supplier_data) && $this->error(__('供应商不存在'), [], 414);

        //获取采购批次数据
        $batch = 0;
        if($check_data['batch_id']){
            $_purchase_batch = new \app\admin\model\purchase\PurchaseBatch;
            $batch_data = $_purchase_batch->where('id', $check_data['batch_id'])->field('batch')->find();
            empty($batch_data) && $this->error(__('采购单批次不存在'), [], 415);
            $batch = $batch_data['batch'];
        }

        //获取质检单商品数据
        $_check_item = new \app\admin\model\warehouse\CheckItem;
        $item_list = $_check_item
            ->where(['check_id'=>$check_id])
            ->field('sku,supplier_sku,arrival_num,quantity_num,unqualified_num,sample_num,should_arrival_num')
            ->select();
        $item_list = collection($item_list)->toArray();

        //质检单所需数据
        $info =[
            'check_order_number'=>$check_data['check_order_number'],
            'purchase_number'=>$purchase_data['purchase_number'],
            'supplier_name'=>$supplier_data['supplier_name'],
            'batch'=>$batch,
            'item_list'=>$item_list
        ];

        $this->success('', ['info' => $info],200);
    }

    /**
     * 新建/编辑质检单提交
     *
     * @参数 int check_id  质检单ID
     * @参数 int logistics_id  物流单ID
     * @参数 string check_order_number  质检单号
     * @参数 int purchase_id  采购单ID
     * @参数 int supplier_id  供应商ID
     * @参数 int replenish_id  补货单ID
     * @参数 int do_type  提交类型：1提交2保存
     * @参数 int is_error  是否错发：1是2否
     * @参数 int batch_id  批次ID
     * @参数 json item_data  sku数据集合
     * @author lzh
     * @return mixed
     */
    public function quality_submit()
    {
        $item_data = $this->request->request('item_data');
        $item_data = array_filter(json_decode($item_data,true));
        empty($item_data) && $this->error(__('sku集合不能为空'), [], 416);

        $do_type = $this->request->request('do_type');
        $is_error = $this->request->request('is_error');
        $get_check_id = $this->request->request('check_id');

        $_check = new \app\admin\model\warehouse\Check;
        if($get_check_id){
            $row = $_check->get($get_check_id);
            empty($row) && $this->error(__('质检单不存在'), [], 416);
            $check_id = $get_check_id;
            $purchase_id = $row['purchase_id'];
            $logistics_id = $row['logistics_id'];

            //编辑质检单
            $check_data = [
                'is_error'=>1 == $is_error ?? 0,
                'status'=>1 == $do_type ?? 0
            ];
            $result = $row->allowField(true)->save($check_data);
        }else{
            $batch_id = $this->request->request('batch_id');
            $logistics_id = $this->request->request('logistics_id');
            empty($logistics_id) && $this->error(__('物流单ID不能为空'), [], 416);

            $check_order_number = $this->request->request('check_order_number');
            empty($check_order_number) && $this->error(__('质检单号不能为空'), [], 416);

            $purchase_id = $this->request->request('purchase_id');
            empty($purchase_id) && $this->error(__('采购单ID不能为空'), [], 416);

            $supplier_id = $this->request->request('supplier_id');
            empty($supplier_id) && $this->error(__('供应商ID不能为空'), [], 416);

            $replenish_id = $this->request->request('replenish_id');
            empty($replenish_id) && $this->error(__('补货单ID不能为空'), [], 416);

            //创建质检单
            $check_data = [
                'check_order_number'=>$check_order_number,
                'purchase_id'=>$purchase_id,
                'supplier_id'=>$supplier_id,
                'batch_id'=>$batch_id,
                'is_error'=>1 == $is_error ?? 0,
                'status'=>1 == $do_type ?? 0,
                'logistics_id'=>$logistics_id,
                'replenish_id'=>$replenish_id,
                'create_person'=>$this->auth->nickname,
                'createtime'=>date('Y-m-d H:i:s')
            ];
            $result = $_check->allowField(true)->save($check_data);
            $check_id = $_check->id;
        }

        false === $result && $this->error(__('提交失败'), [], 417);

        Db::startTrans();
        try {
            //检测条形码是否已绑定
            $_product_bar_code_item = new \app\admin\model\warehouse\ProductBarCodeItem;
            $where['check_id'] = [['>',0], ['neq',$check_id]];
            foreach ($item_data as $key => $value) {
                //检测合格条形码
                $quantity_agg = array_unique(array_filter(explode(',',$value['quantity_agg'])));
                $where['code'] = ['in',$quantity_agg];
                $check_quantity = $_product_bar_code_item
                    ->where($where)
                    ->field('code')
                    ->find();
                if(!empty($check_quantity['code'])){
                    $this->error(__('合格条形码:'.$check_quantity['code'].' 已绑定,请移除'), [], 418);
                    exit;
                }

                //检测不合格条形码
                $unqualified_agg = array_unique(array_filter(explode(',',$value['unqualified_agg'])));
                $where['code'] = ['in',$unqualified_agg];
                $check_unqualified = $_product_bar_code_item
                    ->where($where)
                    ->field('code')
                    ->find();
                if(!empty($check_unqualified['code'])){
                    $this->error(__('不合格条形码:'.$check_unqualified['code'].' 已绑定,请移除'), [], 418);
                    exit;
                }

                //检测留样条形码
                $sample_agg = array_unique(array_filter(explode(',',$value['sample_agg'])));
                $where['code'] = ['in',$sample_agg];
                $check_sample = $_product_bar_code_item
                    ->where($where)
                    ->field('code')
                    ->find();
                if(!empty($check_sample)){
                    $this->error(__('留样条形码:'.$check_sample['code'].' 已绑定,请移除'), [], 418);
                    exit;
                }

                $item_data[$key]['quantity_agg'] = $quantity_agg;
                $item_data[$key]['unqualified_agg'] = $unqualified_agg;
                $item_data[$key]['sample_agg'] = $sample_agg;
            }

            //批量创建或更新质检单商品
            $_check_item = new \app\admin\model\warehouse\CheckItem;
            foreach ($item_data as $key => $value) {
                //错误类型、合格率
                if($value['should_arrival_num'] > $value['arrival_num']){
                    $error_type = 2;
                }elseif($value['should_arrival_num'] < $value['arrival_num']){
                    $error_type = 1;
                }else{
                    $error_type = 0;
                }
                $quantity_rate = round(($value['quantity_num'] / $value['arrivals_num'] * 100),2);

                $item_save = [
                    'arrivals_num'=>$value['arrivals_num'],
                    'quantity_num'=>$value['quantity_num'],
                    'sample_num'=>$value['sample_num'],
                    'unqualified_num'=>$value['unqualified_num'],
                    'quantity_rate'=>$quantity_rate,
                    'error_type'=>$error_type,
                    'remark'=>$value['remark']
                ];
                if($get_check_id){//更新
                    $where = ['sku' => $value['sku'],'check_id' => $check_id];
                    $_check_item->allowField(true)->isUpdate(true, $where)->save($item_save);

                    //清除质检单旧条形码数据
                    $code_clear = [
                        'sku' => '',
                        'purchase_id' => 0,
                        'logistics_id' => 0,
                        'check_id' => 0
                    ];
                    $_product_bar_code_item->allowField(true)->isUpdate(true, $where)->save($code_clear);
                }else{//新增
                    $item_save['check_id'] = $check_id;
                    $item_save['sku'] = $value['sku'];
                    $item_save['supplier_sku'] = $value['supplier_sku'];
                    $item_save['purchase_id']  = $purchase_id;
                    $item_save['purchase_num'] = $value['purchase_num'];
                    $item_save['should_arrival_num'] = $value['should_arrival_num'];
                    $_check_item->allowField(true)->save($item_save);
                }

                $code_item = [
                    'purchase_id'=>$purchase_id,
                    'sku'=>$value['sku'],
                    'logistics_id'=>$logistics_id,
                    'check_id'=>$check_id,
                    'create_person'=>$this->auth->nickname,
                    'create_time'=>date('Y-m-d H:i:s')
                ];

                //绑定合格条形码
                foreach($value['quantity_agg'] as $v){
                    $code_item['is_quantity'] = 1;
                    $_product_bar_code_item->allowField(true)->isUpdate(true, ['code' => $v])->save($code_item);
                }

                //绑定不合格条形码
                foreach($value['unqualified_agg'] as $v){
                    $code_item['is_quantity'] = 2;
                    $_product_bar_code_item->allowField(true)->isUpdate(true, ['code' => $v])->save($code_item);
                }

                //绑定留样条形码
                foreach($value['sample_agg'] as $v){
                    $code_item['is_sample'] = 1;
                    $_product_bar_code_item->allowField(true)->isUpdate(true, ['code' => $v])->save($code_item);
                }
            }

            Db::commit();
        } catch (ValidateException $e) {
            Db::rollback();
            $this->error($e->getMessage(), [], 419);
        } catch (PDOException $e) {
            Db::rollback();
            $this->error($e->getMessage(), [], 420);
        } catch (Exception $e) {
            Db::rollback();
            $this->error($e->getMessage(), [], 421);
        }

        $this->success('提交成功', [],200);
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

}
