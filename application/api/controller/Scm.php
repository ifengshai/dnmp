<?php

namespace app\api\controller;

use app\admin\model\DistributionLog;
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
                ['name'=>'配货', 'link'=>'distribution/index', 'href'=>'com.nextmar.mojing.ui.distribution.OrderDistributionActivity'],
                ['name'=>'镜片分拣', 'link'=>'distribution/sorting', 'href'=>'com.nextmar.mojing.ui.sorting.OrderSortingActivity'],
                ['name'=>'配镜片', 'link'=>'distribution/withlens', 'href'=>'com.nextmar.mojing.ui.withlens.OrderWithlensActivity'],
                ['name'=>'加工', 'link'=>'distribution/machining', 'href'=>'com.nextmar.mojing.ui.machining.OrderMachiningActivity'],
                ['name'=>'成品质检', 'link'=>'distribution/quality', 'href'=>'com.nextmar.mojing.ui.quality.OrderQualityActivity'],
                ['name'=>'合单', 'link'=>'distribution/merge', 'href'=>'com.nextmar.mojing.ui.merge.OrderMergeActivity'],
                ['name'=>'合单待取', 'link'=>'distribution/waitmerge', 'href'=>'com.nextmar.mojing.ui.merge.OrderMergeCompletedActivity'],
                ['name'=>'审单', 'link'=>'distribution/audit', 'href'=>'com.nextmar.mojing.ui.audit.AuditOrderActivity']
            ],
        ],
        [
            'title'=>'质检管理',
            'menu'=>[
                ['name'=>'物流检索', 'link'=>'warehouse/logistics_info/index', 'href'=>'com.nextmar.mojing.ui.logistics.LogisticsActivity'],
                ['name'=>'质检单', 'link'=>'warehouse/check', 'href'=>'com.nextmar.mojing.ui.quality.QualityListActivity']
            ],
        ],
        [
            'title'=>'出入库管理',
            'menu'=>[
                ['name'=>'出库单', 'link'=>'warehouse/outstock', 'href'=>'com.nextmar.mojing.ui.outstock.OutStockActivity'],
                ['name'=>'入库单', 'link'=>'warehouse/instock', 'href'=>'com.nextmar.mojing.ui.instock.InStockActivity'],
                ['name'=>'待入库', 'link'=>'warehouse/prestock', 'href'=>'com.nextmar.mojing".ui.prestock.PreStockActivity'],
                ['name'=>'盘点', 'link'=>'warehouse/inventory', 'href'=>'com.nextmar.mojing.ui.inventory.InventoryActivity'],
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

        empty($page) && $this->error(__('Page can not be empty'), [], 403);
        empty($page_size) && $this->error(__('Page size can not be empty'), [], 403);

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
        $_check = new \app\admin\model\warehouse\Check();
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
            $list[$key]['examine_show'] = 1 == $value['status'] ?: 0;
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
        empty($logistics_id) && $this->error(__('物流单ID不能为空'), [], 403);

        //获取物流单数据
        $_logistics_info = new \app\admin\model\warehouse\LogisticsInfo();
        $logistics_data = $_logistics_info->where('id', $logistics_id)->field('id,purchase_id,batch_id')->find();
        empty($logistics_data) && $this->error(__('物流单不存在'), [], 403);

        //获取采购单数据
        $_purchase_order = new \app\admin\model\purchase\PurchaseOrder();
        $purchase_data = $_purchase_order->where('id', $logistics_data['purchase_id'])->field('purchase_number,supplier_id,replenish_id')->find();
        empty($purchase_data) && $this->error(__('采购单不存在'), [], 403);

        //获取采购单商品数据
        $_purchase_order_item = new \app\admin\model\purchase\PurchaseOrderItem();
        $order_item_list = $_purchase_order_item
            ->where(['purchase_id'=>$logistics_data['purchase_id']])
            ->field('sku,supplier_sku,purchase_num')
            ->select();
        $order_item_list = collection($order_item_list)->toArray();

        //获取供应商数据
        $_supplier = new \app\admin\model\purchase\Supplier();
        $supplier_data = $_supplier->where('id', $purchase_data['supplier_id'])->field('supplier_name')->find();
        empty($supplier_data) && $this->error(__('供应商不存在'), [], 403);

        //获取采购批次数据
        $batch = 0;
        if($logistics_data['batch_id']){
            $_purchase_batch = new \app\admin\model\purchase\PurchaseBatch();
            $batch_data = $_purchase_batch->where('id', $logistics_data['batch_id'])->field('batch')->find();
            empty($batch_data) && $this->error(__('采购单批次不存在'), [], 403);

            $batch = $batch_data['batch'];
            $_purchase_batch_item = new \app\admin\model\purchase\PurchaseBatchItem();
            $item_list = $_purchase_batch_item
                ->where(['purchase_batch_id'=>$logistics_data['batch_id']])
                ->field('sku,arrival_num as should_arrival_num')
                ->select();
            $item_list = collection($item_list)->toArray();
            $order_item = array_column($order_item_list,NULL,'sku');
            foreach($item_list as $key=>$value){
                $item_list[$key]['supplier_sku'] = $order_item[$value['sku']]['supplier_sku'] ?? '';
                $item_list[$key]['purchase_num'] = $order_item[$value['sku']]['purchase_num'] ?? 0;
            }
        }else{
            $item_list = [];
            foreach($order_item_list as $key=>$value){
                $value['should_arrival_num'] = $value['purchase_num'];
                $item_list[] = $value;
            }
        }

        //默认合格、不合格、留样sku集合
        array_walk($item_list, function (&$value, $k, $p) {
            $value = array_merge($value, $p);
        },['quantity_agg' => [],'unqualified_agg' => [],'sample_agg' => []]);

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
        empty($check_id) && $this->error(__('质检单ID不能为空'), [], 403);

        //获取质检单数据
        $_check = new \app\admin\model\warehouse\Check();
        $check_data = $_check->where('id', $check_id)->field('purchase_id,batch_id,check_order_number,supplier_id')->find();
        empty($check_data) && $this->error(__('质检单不存在'), [], 403);

        //获取采购单数据
        $_purchase_order = new \app\admin\model\purchase\PurchaseOrder();
        $purchase_data = $_purchase_order->where('id', $check_data['purchase_id'])->field('purchase_number')->find();
        empty($purchase_data) && $this->error(__('采购单不存在'), [], 403);

        //获取供应商数据
        $_supplier = new \app\admin\model\purchase\Supplier();
        $supplier_data = $_supplier->where('id', $check_data['supplier_id'])->field('supplier_name')->find();
        empty($supplier_data) && $this->error(__('供应商不存在'), [], 403);

        //获取采购批次数据
        $batch = 0;
        if($check_data['batch_id']){
            $_purchase_batch = new \app\admin\model\purchase\PurchaseBatch();
            $batch_data = $_purchase_batch->where('id', $check_data['batch_id'])->field('batch')->find();
            empty($batch_data) && $this->error(__('采购单批次不存在'), [], 403);
            $batch = $batch_data['batch'];
        }

        //获取质检单商品数据
        $_check_item = new \app\admin\model\warehouse\CheckItem();
        $item_list = $_check_item
            ->where(['check_id'=>$check_id])
            ->field('sku,supplier_sku,arrivals_num,quantity_num,unqualified_num,sample_num,should_arrival_num')
            ->select();
        $item_list = collection($item_list)->toArray();

        //获取条形码数据
        $_product_bar_code_item = new \app\admin\model\warehouse\ProductBarCodeItem();
        $bar_code_list = $_product_bar_code_item
            ->where(['check_id'=>$check_id])
            ->field('sku,code,is_quantity,is_sample')
            ->select();

        //合格
        $quantity_list = array_filter($bar_code_list,function($v){
            if($v['is_quantity'] == 1){
                return $v;
            }
        });

        //不合格
        $unqualified_list = array_filter($bar_code_list,function($v){
            if($v['is_quantity'] == 2){
                return $v;
            }
        });

        //留样合格
        $sample_list = array_filter($bar_code_list,function($v){
            if($v['is_sample'] == 1){
                return $v;
            }
        });

        //拼接sku条形码数据
        foreach($item_list as $key=>$value){
            $quantity_agg = [];
            $unqualified_agg = [];
            $sample_agg = [];

            //合格条形码集合
            if(!empty($quantity_list)){
                foreach($quantity_list as $val){
                    if($value['sku'] == $val['sku']){
                        $quantity_agg[] = [
                            'code'=>$val['sku'],
                            'is_new'=>0
                        ];
                    }
                }
            }

            //不合格条形码集合
            if(!empty($unqualified_list)){
                foreach($unqualified_list as $val){
                    if($value['sku'] == $val['sku']){
                        $unqualified_agg[] = [
                            'code'=>$val['sku'],
                            'is_new'=>0
                        ];
                    }
                }
            }

            //留样条形码集合
            if(!empty($sample_list)){
                foreach($sample_list as $val){
                    if($value['sku'] == $val['sku']){
                        $sample_agg[] = [
                            'code'=>$val['sku'],
                            'is_new'=>0
                        ];
                    }
                }
            }

            $item_list[$key]['quantity_agg'] = $quantity_agg;
            $item_list[$key]['unqualified_agg'] = $unqualified_agg;
            $item_list[$key]['sample_agg'] = $sample_agg;
        }

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
        empty($item_data) && $this->error(__('sku集合不能为空'), [], 403);

        $do_type = $this->request->request('do_type');
        $is_error = $this->request->request('is_error');
        $get_check_id = $this->request->request('check_id');

        $_check = new \app\admin\model\warehouse\Check();
        if($get_check_id){
            $row = $_check->get($get_check_id);
            empty($row) && $this->error(__('质检单不存在'), [], 403);
            0 != $row['status'] && $this->error(__('只有新建状态才能编辑'), [], 405);

            $check_id = $get_check_id;
            $purchase_id = $row['purchase_id'];
            $logistics_id = $row['logistics_id'];

            //编辑质检单
            $check_data = [
                'is_error'=>1 == $is_error ?: 0,
                'status'=>1 == $do_type ?: 0
            ];
            $result = $row->allowField(true)->save($check_data);
        }else{
            $batch_id = $this->request->request('batch_id');
            $logistics_id = $this->request->request('logistics_id');
            empty($logistics_id) && $this->error(__('物流单ID不能为空'), [], 403);

            $check_order_number = $this->request->request('check_order_number');
            empty($check_order_number) && $this->error(__('质检单号不能为空'), [], 403);

            $purchase_id = $this->request->request('purchase_id');
            empty($purchase_id) && $this->error(__('采购单ID不能为空'), [], 403);

            $supplier_id = $this->request->request('supplier_id');
            empty($supplier_id) && $this->error(__('供应商ID不能为空'), [], 403);

            $replenish_id = $this->request->request('replenish_id');
            empty($replenish_id) && $this->error(__('补货单ID不能为空'), [], 403);

            //创建质检单
            $check_data = [
                'check_order_number'=>$check_order_number,
                'purchase_id'=>$purchase_id,
                'supplier_id'=>$supplier_id,
                'batch_id'=>$batch_id,
                'is_error'=>1 == $is_error ?: 0,
                'status'=>1 == $do_type ?: 0,
                'logistics_id'=>$logistics_id,
                'replenish_id'=>$replenish_id,
                'create_person'=>$this->auth->nickname,
                'createtime'=>date('Y-m-d H:i:s')
            ];
            $result = $_check->allowField(true)->save($check_data);
            $check_id = $_check->id;
        }

        false === $result && $this->error(__('提交失败'), [], 404);

        Db::startTrans();
        try {
            //检测条形码是否已绑定
            $_product_bar_code_item = new \app\admin\model\warehouse\ProductBarCodeItem();
            $where['check_id'] = [['>',0], ['neq',$check_id]];
            foreach ($item_data as $key => $value) {
                //检测合格条形码
                $quantity_code = array_column($value['quantity_agg'],'code');
                count($value['quantity_agg']) != count(array_unique($quantity_code))
                &&
                $this->error(__('合格条形码有重复，请检查'), [], 405);

                $where['code'] = ['in',$quantity_code];
                $check_quantity = $_product_bar_code_item
                    ->where($where)
                    ->field('code')
                    ->find();
                if(!empty($check_quantity['code'])){
                    $this->error(__('合格条形码:'.$check_quantity['code'].' 已绑定,请移除'), [], 405);
                    exit;
                }

                //检测不合格条形码
                $unqualified_code = array_column($value['unqualified_agg'],'code');
                count($value['unqualified_agg']) != count(array_unique($unqualified_code))
                &&
                $this->error(__('不合格条形码有重复，请检查'), [], 405);

                $where['code'] = ['in',$unqualified_code];
                $check_unqualified = $_product_bar_code_item
                    ->where($where)
                    ->field('code')
                    ->find();
                if(!empty($check_unqualified['code'])){
                    $this->error(__('不合格条形码:'.$check_unqualified['code'].' 已绑定,请移除'), [], 405);
                    exit;
                }

                //检测留样条形码
                $sample_code = array_column($value['sample_agg'],'code');
                count($value['sample_agg']) != count(array_unique($sample_code))
                &&
                $this->error(__('不合格条形码有重复，请检查'), [], 405);

                $where['code'] = ['in',$sample_code];
                $check_sample = $_product_bar_code_item
                    ->where($where)
                    ->field('code')
                    ->find();
                if(!empty($check_sample)){
                    $this->error(__('留样条形码:'.$check_sample['code'].' 已绑定,请移除'), [], 405);
                    exit;
                }
            }

            //批量创建或更新质检单商品
            $_check_item = new \app\admin\model\warehouse\CheckItem();
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

                    //质检单移除条形码
                    if(!empty($value['remove_agg'])){
                        $code_clear = [
                            'sku' => '',
                            'purchase_id' => 0,
                            'logistics_id' => 0,
                            'check_id' => 0
                        ];
                        $_product_bar_code_item->allowField(true)->isUpdate(true, ['code' => ['in',$value['remove_agg']]])->save($code_clear);
                    }
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
                if(!empty($value['quantity_agg'])){
                    foreach($value['quantity_agg'] as $v){
                        if($v['is_new'] == 1){
                            $code_item['is_quantity'] = 1;
                            $_product_bar_code_item->allowField(true)->isUpdate(true, ['code' => $v['code']])->save($code_item);
                        }
                    }
                }

                //绑定不合格条形码
                if(!empty($value['unqualified_agg'])){
                    foreach($value['unqualified_agg'] as $v){
                        if($v['is_new'] == 1){
                            $code_item['is_quantity'] = 2;
                            $_product_bar_code_item->allowField(true)->isUpdate(true, ['code' => $v['code']])->save($code_item);
                        }
                    }
                }

                //绑定留样条形码
                if(!empty($value['sample_agg'])){
                    foreach($value['sample_agg'] as $v){
                        if($v['is_new'] == 1){
                            $code_item['is_sample'] = 1;
                            $_product_bar_code_item->allowField(true)->isUpdate(true, ['code' => $v['code']])->save($code_item);
                        }
                    }
                }
            }

            Db::commit();
        } catch (ValidateException $e) {
            Db::rollback();
            $this->error($e->getMessage(), [], 406);
        } catch (PDOException $e) {
            Db::rollback();
            $this->error($e->getMessage(), [], 407);
        } catch (Exception $e) {
            Db::rollback();
            $this->error($e->getMessage(), [], 408);
        }

        $this->success('提交成功', [],200);
    }

    /**
     * 取消质检
     *
     * @参数 int check_id  质检单ID
     * @author lzh
     * @return mixed
     */
    public function quality_cancel()
    {
        $check_id = $this->request->request('check_id');
        empty($check_id) && $this->error(__('质检单ID不能为空'), [], 403);

        //检测质检单状态
        $_check = new \app\admin\model\warehouse\Check();
        $row = $_check->get($check_id);
        0 != $row['status'] && $this->error(__('只有新建状态才能取消'), [], 405);

        Db::startTrans();
        try {
            //移除质检单条形码绑定关系
            $code_clear = [
                'sku' => '',
                'purchase_id' => 0,
                'logistics_id' => 0,
                'check_id' => 0
            ];
            $_product_bar_code_item = new \app\admin\model\warehouse\ProductBarCodeItem();
            $_product_bar_code_item->allowField(true)->isUpdate(true, ['check_id' => $check_id])->save($code_clear);

            $res = $_check->allowField(true)->isUpdate(true, ['id'=>$check_id])->save(['status'=>4]);
            $res ? $this->success('取消成功', [],200) : $this->error(__('取消失败'), [], 404);

            Db::commit();
        } catch (ValidateException $e) {
            Db::rollback();
            $this->error($e->getMessage(), [], 406);
        } catch (PDOException $e) {
            Db::rollback();
            $this->error($e->getMessage(), [], 407);
        } catch (Exception $e) {
            Db::rollback();
            $this->error($e->getMessage(), [], 408);
        }
    }

    /**
     * 审核质检
     *
     * @参数 int check_id  质检单ID
     * @参数 int do_type  2审核通过，3审核拒绝
     * @author lzh
     * @return mixed
     */
    public function quality_examine()
    {
        $check_id = $this->request->request('check_id');
        empty($check_id) && $this->error(__('质检单ID不能为空'), [], 403);

        $do_type = $this->request->request('do_type');
        empty($do_type) && $this->error(__('审核类型不能为空'), [], 403);
        !in_array($do_type,[2,3]) && $this->error(__('审核类型错误'), [], 403);

        //检测质检单状态
        $_check = new \app\admin\model\warehouse\Check();
        $row = $_check->get($check_id);
        1 != $row['status'] && $this->error(__('只有待审核状态才能审核'), [], 405);

        $res = $_check->allowField(true)->isUpdate(true, ['id'=>$check_id])->save(['status'=>$do_type,'examine_time'=>date('Y-m-d H:i:s')]);
        false === $res && $this->error(__('审核失败'), [], 404);

        Db::startTrans();
        try {
            //审核通过关联操作
            if ($do_type == 2) {
                //标记物流单检索为已创建质检单
                $_logistics_info = new \app\admin\model\warehouse\LogisticsInfo();
                $_logistics_info->allowField(true)->isUpdate(true, ['id'=>$row['logistics_id']])->save(['is_check_order'=>1]);

                //查询物流信息表对应采购单数据是否全部质检完毕
                if ($row['purchase_id']) {
                    //查询质检信息
                    $count = $_logistics_info->where(['purchase_id' => $row['purchase_id'], 'is_check_order' => 0])->count();

                    //修改采购单质检状态
                    $_purchase_order = new \app\admin\model\purchase\PurchaseOrder();
                    $_purchase_order->allowField(true)->isUpdate(true, ['id'=>$row['purchase_id']])->save(['check_status'=>$count > 0 ? 1 : 2]);
                }

                //查询质检单明细表有样品的数据
                $_check_item = new \app\admin\model\warehouse\CheckItem();
                $list = $_check_item->where(['check_id' => $check_id, 'sample_num' => ['>', 0]])->select();
                if ($list) {
                    //生成样品入库主表数据
                    $work_order_data = [
                        'location_number'=>'IN2' . date('YmdHis') . rand(100, 999) . rand(100, 999),
                        'status'=>1,
                        'type'=>1,
                        'description'=>'质检入库',
                        'create_user'=>$this->auth->nickname,
                        'createtime'=>date('Y-m-d H:i:s')
                    ];
                    $_sample_work_order = new \app\admin\model\purchase\SampleWorkorder();
                    $_sample_work_order->allowField(true)->save($work_order_data);

                    //生成样品入库子表数据
                    $work_order_item_data = [];
                    foreach ($list as $value) {
                        $work_order_item_data[] = [
                            'parent_id'=>$_sample_work_order->id,
                            'sku'=>$value['sku'],
                            'stock'=>$value['sample_num'],
                        ];
                    }
                    $_sample_work_order_item = new \app\admin\model\purchase\SampleWorkorderItem();
                    $_sample_work_order_item->allowField(true)->saveAll($work_order_item_data);
                }

                //检测批次或采购单是否全部质检完成
                $where = ['purchase_id' => $row['purchase_id'], 'is_check_order' => 0];
                if($row['batch_id'] ){
                    $where['batch_id'] = $row['batch_id'];
                }
                $count = $_logistics_info->where($where)->count();

                //检测是否有异常单
                if ($count <= 0) {
                    $check_item_list = $_check
                        ->alias('a')
                        ->join(['fa_check_order_item' => 'b'], 'a.id=b.check_id')
                        ->where(['a.batch_id' => $row['batch_id'], 'a.purchase_id' => $row['purchase_id'], 'a.status' => 2])
                        ->where('a.is_error = :is_error or b.error_type > :error_type ',['is_error'=>1, 'error_type'=>0])
                        ->select()
                    ;
                    if ($check_item_list) {
                        //获取采购价格
                        $_purchase_order_item = new \app\admin\model\purchase\PurchaseOrderItem();
                        $purchase_item_list = $_purchase_order_item
                            ->field('sku,purchase_price')
                            ->where(['purchase_id' => $row['purchase_id']])
                            ->select()
                        ;
                        $purchase_item_list = array_column($purchase_item_list,NULL,'sku');

                        //获取采购单商品数据
                        $abnormal_item_save = [];
                        $is_error = 0;
                        foreach ($check_item_list as $v) {
                            $abnormal_item_save[] = [
                                'sku'=>$v['sku'],
                                'supplier_sku'=>$v['supplier_sku'],
                                'purchase_num'=>$v['purchase_num'],
                                'should_arrival_num'=>$v['should_arrival_num'],
                                'arrival_num'=>$v['arrival_num'],
                                'error_type'=>$v['error_type'],
                                'purchase_id'=>$row['purchase_id'],
                                'purchase_price'=>$purchase_item_list[$v['sku']] ?? 0
                            ];
                            if (1 == $v['is_error']) {
                                $is_error = 1;
                            }
                        }

                        //新增收货异常主表数据
                        $_purchase_abnormal = new \app\admin\model\purchase\PurchaseAbnormal();
                        $abnormal_save = [
                            'error_number'=>'YC' . date('YmdHis') . rand(100, 999) . rand(100, 999),
                            'supplier_id'=>$row['supplier_id'],
                            'purchase_id'=>$row['purchase_id'],
                            'batch_id'=>$row['batch_id'],
                            'createtime'=>date('Y-m-d H:i:s'),
                            'is_error'=>$is_error
                        ];
                        $_purchase_abnormal->allowField(true)->save($abnormal_save);

                        //新增收货异常子表数据
                        array_walk($abnormal_item_save, function (&$value, $k, $p) {
                            $value = array_merge($value, $p);
                        },['abnormal_id' => $_purchase_abnormal->id]);

                        $_purchase_abnormal_item = new \app\admin\model\purchase\PurchaseAbnormalItem();
                        $_purchase_abnormal_item->allowField(true)->saveAll($abnormal_item_save);
                    }
                }
            }else{//审核拒绝关联操作
                //移除质检单条形码绑定关系
                $code_clear = [
                    'sku' => '',
                    'purchase_id' => 0,
                    'logistics_id' => 0,
                    'check_id' => 0
                ];
                $_product_bar_code_item = new \app\admin\model\warehouse\ProductBarCodeItem();
                $_product_bar_code_item->allowField(true)->isUpdate(true, ['check_id' => $check_id])->save($code_clear);
            }

            Db::commit();
        } catch (ValidateException $e) {
            Db::rollback();
            $this->error($e->getMessage(), [], 406);
        } catch (PDOException $e) {
            Db::rollback();
            $this->error($e->getMessage(), [], 407);
        } catch (Exception $e) {
            Db::rollback();
            $this->error($e->getMessage(), [], 408);
        }

        $this->success('审核成功', [],200);
    }

    /**
     * 物流检索列表
     *
     * @参数 string logistics_number  物流单号
     * @参数 string sign_number  签收编号
     * @参数 int status  签收状态：1已签收 0未签收
     * @参数 int is_new_product  是否为新品采购单：1是 0否
     * @参数 string start_time  开始时间
     * @参数 string end_time  结束时间
     * @参数 int page  页码
     * @参数 int page_size  每页显示数量
     * @author lzh
     * @return mixed
     */
    public function logistics_list()
    {
        $logistics_number = $this->request->request('logistics_number');
        $sign_number = $this->request->request('sign_number');
        $status = $this->request->request('status');
        $is_new_product = $this->request->request('is_new_product');
        $start_time = $this->request->request('start_time');
        $end_time = $this->request->request('end_time');
        $page = $this->request->request('page');
        $page_size = $this->request->request('page_size');

        empty($page) && $this->error(__('Page can not be empty'), [], 403);
        empty($page_size) && $this->error(__('Page size can not be empty'), [], 403);

        $where = [];
        if($logistics_number){
            $where['logistics_number'] = ['like', '%' . $logistics_number . '%'];
        }
        if($sign_number){
            $where['sign_number'] = ['like', '%' . $sign_number . '%'];
        }
        if(isset($status)){
            $where['status'] = $status;
        }
        if($start_time && $end_time){
            $where['createtime'] = ['between', [$start_time, $end_time]];
        }

        //获取采购单数据
        $_purchase_order = new \app\admin\model\purchase\PurchaseOrder();
        $purchase_list = $_purchase_order
            ->where(['purchase_status'=>[['=',6], ['=',7], 'or']])
            ->field('id,purchase_number,is_new_product')
            ->select();
        $purchase_list = array_column($purchase_list,NULL,'id');

        //拼接采购单条件
        if(isset($is_new_product)){
            $purchase_ids = array_filter($purchase_list,function($v) use ($is_new_product){
                if($v['is_new_product'] == $is_new_product){
                    return $v;
                }
            });
            $purchase_ids = array_keys($purchase_ids);
            $where['purchase_id'] = ['in',$purchase_ids];
        }

        $offset = ($page - 1) * $page_size;
        $limit = $page_size;

        //获取物流单列表数据
        $_logistics_info = new \app\admin\model\warehouse\LogisticsInfo();
        $list = $_logistics_info
            ->where($where)
            ->field('id,logistics_number,sign_number,createtime,sign_time,status,purchase_id,type')
            ->order('createtime', 'desc')
            ->limit($offset, $limit)
            ->select();
        $list = collection($list)->toArray();

        foreach($list as $key=>$value){
            $purchase_number = '';
            $is_new_product = 0;
            if($value['purchase_id']){
                $purchase_number = $purchase_list[$value['purchase_id']]['purchase_number'];
                $is_new_product = 1 == $purchase_list[$value['purchase_id']]['is_new_product'] ?: 0;
            }
            $list[$key]['purchase_number'] = $purchase_number;
            $list[$key]['is_new_product'] = $is_new_product;
            $list[$key]['status'] = 1 == $value['status'] ? '已签收' : '未签收';
            $list[$key]['show_sign'] = 0 == $value['status'] && 1 == $value['type'] ? 1 : 0;
            $list[$key]['show_quality'] = 1 == $value['status'] && 1 == $value['type'] ? 1 : 0;
        }

        $this->success('', ['list' => $list],200);
    }

    /**
     * 物流单签收
     *
     * @参数 int logistics_id  物流单ID
     * @author lzh
     * @return mixed
     */
    public function logistics_sign()
    {
        $logistics_id = $this->request->request('logistics_id');
        empty($logistics_id) && $this->error(__('物流单ID不能为空'), [], 403);

        //检测质检单状态
        $_logistics_info = new \app\admin\model\warehouse\LogisticsInfo();
        $row = $_logistics_info->get($logistics_id);
        (0 != $row['status'] || 1 != $row['type']) && $this->error(__('只有未签收状态才能操作'), [], 405);

        //签收关联操作
        Db::startTrans();
        try {
            $logistics_save = [
                'sign_person'=>$this->auth->nickname,
                'sign_time'=>date('Y-m-d H:i:s'),
                'status'=>1
            ];
            $res = $_logistics_info->allowField(true)->isUpdate(true, ['id'=>$logistics_id])->save($logistics_save);
            false === $res && $this->error(__('签收失败'), [], 404);

            //签收成功时更改采购单签收状态
            $count = $_logistics_info->where(['purchase_id' => $row['purchase_id'], 'status' => 0])->count();
            $purchase_save = [
                'purchase_status'=>$count > 0 ? 9 : 7,
                'receiving_time'=>date('Y-m-d H:i:s')
            ];
            $_purchase_order = new \app\admin\model\purchase\PurchaseOrder();
            $_purchase_order->allowField(true)->isUpdate(true, ['id'=>$row['purchase_id']])->save($purchase_save);

            //签收扣减在途库存
            $_purchase_order_item = new \app\admin\model\purchase\PurchaseOrderItem();
            $_purchase_batch_item = new \app\admin\model\purchase\PurchaseBatchItem();
            $_new_product_mapping = new \app\admin\model\NewProductMapping();
            $_item = new \app\admin\model\itemmanage\Item();
            $_item_platform_sku = new \app\admin\model\itemmanage\ItemPlatformSku();

            //根据采购单获取补货单ID
            $replenish_id = $_purchase_order->where(['id' => $row['purchase_id']])->value('replenish_id');

            //获取补货单数据
            $mapping_list = $_new_product_mapping
                ->field('website_type,rate,sku')
                ->where(['replenish_id'=>$replenish_id])
                ->select()
            ;

            //批次物流签收
            if ($row['batch_id']) {
                //获取批次商品数据
                $batch_item_list = $_purchase_batch_item
                    ->field('sku,arrival_num')
                    ->where(['purchase_batch_id' => $row['batch_id']])
                    ->select()
                ;
                foreach ($batch_item_list as $v) {
                    $sku = $v['sku'];
                    $arrival_num = $v['arrival_num'];
                    //获取各站虚拟仓占比
                    $rate_arr = array_filter($mapping_list,function($value) use ($sku){
                        if($value['sku'] == $sku){
                            return $value;
                        }
                    });
                    //各站点列表总数
                    $all_num = count($rate_arr);
                    //在途库存数量
                    $stock_num = $arrival_num;
                    foreach ($rate_arr as $key => $val) {
                        $website_type = $val['website_type'];
                        //剩余数量分给最后一个站点
                        if (($all_num - $key) == 1) {
                            $num = $stock_num;
                        } else {
                            $num = round($arrival_num * $val['rate']);
                            $stock_num -= $num;
                        }
                        //减站点在途
                        $_item_platform_sku->where(['sku'=>$sku,'platform_type'=>$website_type])->setDec('plat_on_way_stock',$num);
                        //加站点待入库数量
                        $_item_platform_sku->where(['sku'=>$sku,'platform_type'=>$website_type])->setInc('wait_instock_num',$num);
                    }
                    //减全部在途
                    $_item->where(['sku' => $sku])->setDec('on_way_stock', $arrival_num);
                    //加全部待入库数量
                    $_item->where(['sku' => $sku])->setInc('wait_instock_num', $arrival_num);
                }
            } elseif ($row['purchase_id']) {//采购物流签收
                //获取采购单商品数据
                $purchase_item_list = $_purchase_order_item
                    ->field('sku,purchase_num')
                    ->where(['purchase_id' => $row['purchase_id']])
                    ->select()
                ;
                foreach ($purchase_item_list as $v) {
                    $sku = $v['sku'];
                    $purchase_num = $v['purchase_num'];
                    //获取各站虚拟仓占比
                    $rate_arr = array_filter($mapping_list,function($value) use ($sku){
                        if($value['sku'] == $sku){
                            return $value;
                        }
                    });
                    //各站点列表总数
                    $all_num = count($rate_arr);
                    //在途库存数量
                    $stock_num = $purchase_num;
                    foreach ($rate_arr as $key => $val) {
                        $website_type = $val['website_type'];
                        //剩余数量分给最后一个站点
                        if (($all_num - $key) == 1) {
                            $num = $stock_num;
                        } else {
                            $num = round($purchase_num * $val['rate']);
                            $stock_num -= $num;
                        }
                        //减站点在途
                        $_item_platform_sku->where(['sku'=>$sku,'platform_type'=>$website_type])->setDec('plat_on_way_stock',$num);
                        //加站点待入库数量
                        $_item_platform_sku->where(['sku'=>$sku,'platform_type'=>$website_type])->setInc('wait_instock_num',$num);
                    }
                    //减全部在途
                    $_item->where(['sku' => $sku])->setDec('on_way_stock', $purchase_num);
                    //加全部待入库数量
                    $_item->where(['sku' => $sku])->setInc('wait_instock_num', $purchase_num);
                }
            }

            Db::commit();
        } catch (ValidateException $e) {
            Db::rollback();
            $this->error($e->getMessage(), [], 406);
        } catch (PDOException $e) {
            Db::rollback();
            $this->error($e->getMessage(), [], 407);
        } catch (Exception $e) {
            Db::rollback();
            $this->error($e->getMessage(), [], 408);
        }

        $this->success('签收成功', [],200);
    }

    /**
     * 出库单列表
     *
     * @参数 string query  查询内容
     * @参数 int status  状态：0新建 1待审核 2 已审核 3已拒绝 4已取消
     * @参数 string start_time  开始时间
     * @参数 string end_time  结束时间
     * @参数 int page  页码
     * @参数 int page_size  每页显示数量
     * @author lzh
     * @return mixed
     */
    public function out_stock_list()
    {
        $query = $this->request->request('query');
        $status = $this->request->request('status');
        $start_time = $this->request->request('start_time');
        $end_time = $this->request->request('end_time');
        $page = $this->request->request('page');
        $page_size = $this->request->request('page_size');

        empty($page) && $this->error(__('Page can not be empty'), [], 403);
        empty($page_size) && $this->error(__('Page size can not be empty'), [], 403);

        $where = [];
        if($query){
            $where['a.out_stock_number|a.create_person|b.sku'] = ['like', '%' . $query . '%'];
        }
        if($status){
            $where['a.status'] = $status;
        }
        if($start_time && $end_time){
            $where['a.createtime'] = ['between', [$start_time, $end_time]];
        }

        $offset = ($page - 1) * $page_size;
        $limit = $page_size;

        //获取出库单列表数据
        $_out_stock = new \app\admin\model\warehouse\Outstock();
        $list = $_out_stock
            ->alias('a')
            ->where($where)
            ->field('a.id,a.out_stock_number,a.createtime,a.status,a.type_id,a.remark')
            ->join(['fa_out_stock_item' => 'b'], 'a.id=b.out_stock_id','left')
            ->order('a.createtime', 'desc')
            ->limit($offset, $limit)
            ->select();
        $list = collection($list)->toArray();

        //获取出库分类数据
        $_out_stock_type = new \app\admin\model\warehouse\OutstockType();
        $type_list = $_out_stock_type
            ->where('is_del', 1)
            ->column('name','id')
        ;

        $status = [ 0=>'新建',1=>'待审核',2=>'已审核',3=>'已拒绝',4=>'已取消' ];
        foreach($list as $key=>$value){
            $list[$key]['status'] = $status[$value['status']];
            $list[$key]['type_name'] = $type_list[$value['type_id']];
            $list[$key]['cancel_show'] = 0 == $value['status'] ? 1 : 0;
            $list[$key]['edit_show'] = 0 == $value['status'] ? 1 : 0;
            $list[$key]['detail_show'] = 1 < $value['status'] ? 1 : 0;
            $list[$key]['examine_show'] = 1 == $value['status'] ?: 0;
        }

        $this->success('', ['list' => $list],200);
    }

    /**
     * 新建/编辑/详情出库单页面
     *
     * @参数 int out_stock_id  出库单ID
     * @author lzh
     * @return mixed
     */
    public function out_stock_add()
    {
        $out_stock_id = $this->request->request('out_stock_id');

        //获取出库分类数据
        $_out_stock_type = new \app\admin\model\warehouse\OutstockType();
        $type_list = $_out_stock_type
            ->field('id,name')
            ->where('is_del', 1)
            ->select()
        ;

        //站点列表
        $site_list = [
            ['id'=>1,'title'=>'zeelool'],
            ['id'=>2,'title'=>'voogueme'],
            ['id'=>3,'title'=>'nihao'],
            ['id'=>4,'title'=>'meeloog'],
            ['id'=>5,'title'=>'wesee'],
            ['id'=>8,'title'=>'amazon'],
            ['id'=>9,'title'=>'zeelool_es'],
            ['id'=>10,'title'=>'zeelool_de'],
            ['id'=>11,'title'=>'zeelool_jp']
        ];

        if($out_stock_id){
            $_out_stock = new \app\admin\model\warehouse\Outstock();
            $info = $_out_stock
                ->field('out_stock_number,type_id,platform_id,status')
                ->where('is_del', 1)
                ->find()
            ;
            0 != $info['status'] && $this->error(__('只有新建状态才能编辑'), [], 405);
            unset($info['status']);

            //获取出库单商品数据
            $_out_stock_item = new \app\admin\model\warehouse\OutStockItem();
            $item_data = $_out_stock_item
                ->field('sku,out_stock_num')
                ->where('out_stock_id', $out_stock_id)
                ->select()
            ;

            //获取各站点虚拟仓库存
            $_item_platform_sku = new \app\admin\model\itemmanage\ItemPlatformSku();
            $stock_list = $_item_platform_sku
                ->where('platform_type', $info['platform_id'])
                ->column('stock','sku')
            ;

            //获取条形码数据
            $_product_bar_code_item = new \app\admin\model\warehouse\ProductBarCodeItem();
            $bar_code_list = $_product_bar_code_item
                ->where(['out_stock_id'=>$out_stock_id])
                ->field('sku,code')
                ->select();

            foreach($item_data as $key=>$value){
                $sku = $value['sku'];
                //条形码列表
                $sku_agg = array_filter($bar_code_list,function($v) use ($sku){
                    if($v['sku'] == $sku){
                        return $v;
                    }
                });
                array_walk($sku_agg, function (&$value, $k, $p) {
                    $value = array_merge($value, $p);
                },['is_new' => 0]);
                $item_data[$key]['sku_agg'] = $sku_agg;
                $item_data[$key]['stock'] = $stock_list[$sku];
            }

            $info['item_data'] = $item_data;
        }else{
            $info = [
                'out_stock_number'=>'OUT' . date('YmdHis') . rand(100, 999) . rand(100, 999),
                'type_id'=>0,
                'platform_id'=>0,
                'item_data'=>[]
            ];
        }

        $this->success('', ['type_list' => $type_list,'site_list' => $site_list,'info' => $info],200);
    }

    /**
     * 新建/编辑出库单提交
     *
     * @参数 int out_stock_id  出库单ID
     * @参数 string out_stock_number  出库单号
     * @参数 int do_type  提交类型：1提交，2保存
     * @参数 int type_id  出库分类ID
     * @参数 int platform_id  平台ID
     * @参数 json item_data  sku集合
     * @author lzh
     * @return mixed
     */
    public function out_stock_submit()
    {
        $type_id = $this->request->request('type_id');
        empty($type_id) && $this->error(__('出库分类ID不能为空'), [], 403);

        $platform_id = $this->request->request('platform_id');
        empty($platform_id) && $this->error(__('平台ID不能为空'), [], 403);

        $item_data = $this->request->request('item_data');
        $item_data = array_filter(json_decode($item_data,true));
        empty($item_data) && $this->error(__('sku集合不能为空'), [], 403);

        $do_type = $this->request->request('do_type');
        $get_out_stock_id = $this->request->request('out_stock_id');

        $_out_stock = new \app\admin\model\warehouse\Outstock();
        if($get_out_stock_id){
            $row = $_out_stock->get($get_out_stock_id);
            empty($row) && $this->error(__('出库单不存在'), [], 403);
            0 != $row['status'] && $this->error(__('只有新建状态才能编辑'), [], 405);

            //更新出库单
            $out_stock_data = [
                'type_id'=>$type_id,
                'platform_id'=>$platform_id,
                'status'=>1 == $do_type ?: 0
            ];
            $result = $row->allowField(true)->save($out_stock_data);
            $out_stock_id = $get_out_stock_id;
        }else{
            $out_stock_number = $this->request->request('out_stock_number');
            empty($out_stock_number) && $this->error(__('出库单号不能为空'), [], 403);

            //创建出库单
            $out_stock_data = [
                'out_stock_number'=>$out_stock_number,
                'type_id'=>$type_id,
                'platform_id'=>$platform_id,
                'status'=>1 == $do_type ?: 0,
                'create_person'=>$this->auth->nickname,
                'createtime'=>date('Y-m-d H:i:s')
            ];
            $result = $_out_stock->allowField(true)->save($out_stock_data);
            $out_stock_id = $_out_stock->id;
        }

        false === $result && $this->error(__('提交失败'), [], 404);

        Db::startTrans();
        try {
            count($item_data) != count(array_unique(array_column($item_data,'sku'))) && $this->error(__('sku重复，请检查'), [], 405);

            //获取各站点虚拟仓库存
            $_item_platform_sku = new \app\admin\model\itemmanage\ItemPlatformSku();
            $stock_list = $_item_platform_sku
                ->where('platform_type', $platform_id)
                ->column('stock','sku')
            ;

            //校验各站点虚拟仓库存
            foreach ($item_data as $key => $value) {
                empty($stock_list[$value['sku']]) && $this->error(__('sku: '.$value['sku'].' 没有同步至对应平台'), [], 405);
                $value['out_stock_num'] > $stock_list[$value['sku']] && $this->error(__('sku: '.$value['sku'].' 出库数量不能大于虚拟仓库存'), [], 405);
            }

            //检测条形码是否已绑定
            $_product_bar_code_item = new \app\admin\model\warehouse\ProductBarCodeItem();
            $where['out_stock_id'] = [['>',0], ['neq',$out_stock_id]];
            foreach ($item_data as $key => $value) {
                $sku_code = array_column($value['sku_agg'],'code');
                count($value['sku_agg']) != count(array_unique($sku_code))
                &&
                $this->error(__('条形码有重复，请检查'), [], 405);

                $where['code'] = ['in',$sku_code];
                $check_quantity = $_product_bar_code_item
                    ->where($where)
                    ->field('code')
                    ->find();
                if(!empty($check_quantity['code'])){
                    $this->error(__('条形码:'.$check_quantity['code'].' 已绑定,请移除'), [], 405);
                    exit;
                }
            }

            //批量创建或更新出库单商品
            $_out_stock_item = new \app\admin\model\warehouse\OutstockItem();
            foreach ($item_data as $key => $value) {
                $item_save = [
                    'out_stock_num'=>$value['out_stock_num']
                ];
                if($get_out_stock_id){//更新
                    $where = ['sku' => $value['sku'],'out_stock_id' => $out_stock_id];
                    $_out_stock_item->allowField(true)->isUpdate(true, $where)->save($item_save);

                    //出库单移除条形码
                    if(!empty($value['remove_agg'])){
                        $code_clear = [
                            'out_stock_id' => 0
                        ];
                        $_product_bar_code_item->allowField(true)->isUpdate(true, ['code' => ['in',$value['remove_agg']]])->save($code_clear);
                    }
                }else{//新增
                    $item_save['out_stock_id'] = $out_stock_id;
                    $item_save['sku'] = $value['sku'];
                    $_out_stock_item->allowField(true)->save($item_save);
                }

                //绑定条形码
                foreach($value['sku_agg'] as $v){
                    $_product_bar_code_item->allowField(true)->isUpdate(true, ['code' => $v['code']])->save(['out_stock_id'=>$out_stock_id]);
                }
            }

            Db::commit();
        } catch (ValidateException $e) {
            Db::rollback();
            $this->error($e->getMessage(), [], 406);
        } catch (PDOException $e) {
            Db::rollback();
            $this->error($e->getMessage(), [], 407);
        } catch (Exception $e) {
            Db::rollback();
            $this->error($e->getMessage(), [], 408);
        }

        $this->success('提交成功', [],200);
    }

    /**
     * 审核出库单
     *
     * @参数 int out_stock_id  出库单ID
     * @参数 int do_type  2审核通过，3审核拒绝
     * @author lzh
     * @return mixed
     */
    public function out_stock_examine()
    {
        $out_stock_id = $this->request->request('out_stock_id');
        empty($out_stock_id) && $this->error(__('出库单ID不能为空'), [], 403);

        $do_type = $this->request->request('do_type');
        empty($do_type) && $this->error(__('审核类型不能为空'), [], 403);
        !in_array($do_type,[2,3]) && $this->error(__('审核类型错误'), [], 403);

        //检测出库单状态
        $_out_stock = new \app\admin\model\warehouse\Outstock();
        $row = $_out_stock->get($out_stock_id);
        1 != $row['status'] && $this->error(__('只有待审核状态才能审核'), [], 405);

        Db::startTrans();
        try {
            //审核通过扣减库存
            if ($do_type == 2) {
                //获取出库单商品数据
                $_out_stock_item = new \app\admin\model\warehouse\OutStockItem();
                $item_data = $_out_stock_item
                    ->field('sku,out_stock_num')
                    ->where('out_stock_id', $out_stock_id)
                    ->select()
                ;

                //获取各站点虚拟仓库存
                $_item_platform_sku = new \app\admin\model\itemmanage\ItemPlatformSku();
                $stock_list = $_item_platform_sku
                    ->where('platform_type', $row['platform_id'])
                    ->column('stock','sku')
                ;

                //校验各站点虚拟仓库存
                foreach ($item_data as $key => $value) {
                    $value['out_stock_num'] > $stock_list[$value['sku']] && $this->error(__('sku: '.$value['sku'].' 出库数量不能大于虚拟仓库存'), [], 405);
                }

                $stock_data = [];
                //出库扣减库存
                $_item = new \app\admin\model\itemmanage\Item();
                foreach ($item_data as $key => $value) {
                    //扣除商品表总库存
                    $sku = $value['sku'];
                    $_item->where(['sku'=>$sku])->dec('stock', $value['out_stock_num'])->dec('available_stock', $value['out_stock_num'])->update();

                    //扣减对应平台sku库存
                    $_item_platform_sku->where(['sku' => $sku, 'platform_type' => $row['platform_id']])->dec('stock', $value['out_stock_num'])->update();

                    $stock_data[] = [
                        'type'                      => 2,
                        'two_type'                  => 4,
                        'sku'                       => $sku,
                        'public_id'                 => $value['out_stock_id'],
                        'stock_change'              => -$value['out_stock_num'],
                        'available_stock_change'    => -$value['out_stock_num'],
                        'create_person'             => $this->auth->nickname,
                        'create_time'               => date('Y-m-d H:i:s'),
                        'remark'                    => '出库单减少总库存,减少可用库存'
                    ];
                }

                //库存变动日志
                $_stock_log = new \app\admin\model\StockLog();
                $_stock_log->allowField(true)->saveAll($stock_data);
            }else{//审核拒绝解除条形码绑定关系
                $code_clear = [
                    'out_stock_id' => 0
                ];
                $_product_bar_code_item = new \app\admin\model\warehouse\ProductBarCodeItem();
                $_product_bar_code_item->allowField(true)->isUpdate(true, ['out_stock_id' => $out_stock_id])->save($code_clear);
            }

            Db::commit();
        } catch (ValidateException $e) {
            Db::rollback();
            $this->error($e->getMessage(), [], 406);
        } catch (PDOException $e) {
            Db::rollback();
            $this->error($e->getMessage(), [], 407);
        } catch (Exception $e) {
            Db::rollback();
            $this->error($e->getMessage(), [], 408);
        }

        $res = $_out_stock->allowField(true)->isUpdate(true, ['id'=>$out_stock_id])->save(['status'=>$do_type]);
        false === $res ? $this->error(__('审核失败'), [], 404) : $this->success('审核成功', [],200);
    }

    /**
     * 取消出库
     *
     * @参数 int out_stock_id  出库单ID
     * @author lzh
     * @return mixed
     */
    public function out_stock_cancel()
    {
        $out_stock_id = $this->request->request('out_stock_id');
        empty($out_stock_id) && $this->error(__('出库单ID不能为空'), [], 403);

        //检测出库单状态
        $_out_stock = new \app\admin\model\warehouse\Outstock();
        $row = $_out_stock->get($out_stock_id);
        0 != $row['status'] && $this->error(__('只有新建状态才能取消'), [], 405);

        //解除条形码绑定关系
        $code_clear = [
            'out_stock_id' => 0
        ];
        $_product_bar_code_item = new \app\admin\model\warehouse\ProductBarCodeItem();
        $_product_bar_code_item->allowField(true)->isUpdate(true, ['out_stock_id' => $out_stock_id])->save($code_clear);

        $res = $_out_stock->allowField(true)->isUpdate(true, ['id'=>$out_stock_id])->save(['status'=>4]);
        $res ? $this->success('取消成功', [],200) : $this->error(__('取消失败'), [], 404);
    }

    /**
     * 标记异常
     *
     * @参数 string item_order_number  子订单号
     * @参数 int type  异常类型
     * @author lzh
     * @return mixed
     */
    public function sign_abnormal()
    {
        $item_order_number = $this->request->request('item_order_number');
        empty($item_order_number) && $this->error(__('子订单号不能为空'), [], 403);
        $type = $this->request->request('type');
        empty($type) && $this->error(__('异常类型不能为空'), [], 403);

        //获取子订单数据
        $_new_order_item_process = new \app\admin\model\order\order\NewOrderItemProcess();
        $item_process_id = $_new_order_item_process
            ->where('item_order_number', $item_order_number)
            ->value('id')
        ;
        empty($item_process_id) && $this->error(__('子订单不存在'), [], 403);

        //绑定异常子单号
        $abnormal_data = [
            'item_process_id' => $item_process_id,
            'type' => $type,
            'status' => 1,
            'create_time' => date('Y-m-d H:i:s'),
            'create_person' => $this->auth->nickname
        ];
        $_distribution_abnormal = new \app\admin\model\DistributionAbnormal();
        $res = $_distribution_abnormal->allowField(true)->save($abnormal_data);
        $res ? $this->success('标记成功', [],200) : $this->error(__('标记失败'), [], 404);
    }

    /**
     * 镜片分拣（待定）
     *
     * @参数 string start_time  开始时间
     * @参数 string end_time  结束时间
     * @参数 int page  页码
     * @参数 int page_size  每页显示数量
     * @author lzh
     * @return mixed
     */
    public function distribution_sorting()
    {
        $start_time = $this->request->request('start_time');
        $end_time = $this->request->request('end_time');
        $page = $this->request->request('page');
        $page_size = $this->request->request('page_size');

        empty($page) && $this->error(__('Page can not be empty'), [], 403);
        empty($page_size) && $this->error(__('Page size can not be empty'), [], 403);

        $where = [
            'a.status'=>3,
            'b.index_name'=>['neq',''],
        ];
        if($start_time && $end_time){
            $where['a.created_at'] = ['between', [strtotime($start_time), strtotime($end_time)]];
        }

        $offset = ($page - 1) * $page_size;
        $limit = $page_size;

        //获取出库单列表数据
        $_new_order_item_process = new \app\admin\model\order\order\NewOrderItemProcess();
        $list = $_new_order_item_process
            ->alias('a')
            ->where($where)
            ->field('count(*) as all_count,b.prescription_type,b.index_type,b.index_name')
            ->join(['fa_order_item_option' => 'b'], 'a.option_id=b.id')
            ->group('b.prescription_type,b.index_type,b.index_name')
            ->limit($offset, $limit)
            ->select();
        $list = collection($list)->toArray();

        $this->success('', ['list' => $list],200);
    }

    /**
     * 获取并校验子订单数据（配货通用）
     *
     * @param string $item_order_number  子订单号
     * @param int $check_status  检测状态
     * @author lzh
     * @return mixed
     */
    public function distribution_info($item_order_number,$check_status)
    {
        empty($item_order_number) && $this->error(__('子订单号不能为空'), [], 403);

        //获取子订单数据
        $_new_order_item_process = new \app\admin\model\order\order\NewOrderItemProcess();
        $item_process_info = $_new_order_item_process
            ->where('item_order_number', $item_order_number)
            ->field('id,option_id,distribution_status')
            ->find()
        ;
        empty($item_process_info) && $this->error(__('子订单不存在'), [], 403);

        //检测状态
        $status_arr = [
            3=>'待配镜片',
            4=>'待加工',
            6=>'待成品质检'
        ];
        $check_status != $item_process_info['distribution_status'] && $this->error(__('只有'.$status_arr[$check_status].'状态才能操作'), [], 405);

        //TODO::判断工单状态

        //判断异常状态
        $_distribution_abnormal = new \app\admin\model\DistributionAbnormal();
        $abnormal_id = $_distribution_abnormal
            ->where(['item_process_id'=>$item_process_info['id'],'status'=>1])
            ->value('id')
        ;
        $abnormal_id && $this->error(__('有异常待处理，无法操作'), [], 405);

        //获取子订单处方数据
        $_new_order_item_option = new \app\admin\model\order\order\NewOrderItemOption();
        $option_info = $_new_order_item_option
            ->where('id', $item_process_info['option_id'])
            ->find()
        ;

        //异常原因列表
        $abnormal_arr = [
            3=>[
                ['id'=>3,'name'=>'核实处方'],
                ['id'=>4,'name'=>'镜片缺货'],
                ['id'=>5,'name'=>'镜片重做'],
                ['id'=>6,'name'=>'定制片超时']
            ],
            4=>[
                ['id'=>7,'name'=>'不可加工'],
                ['id'=>8,'name'=>'镜架加工报损'],
                ['id'=>9,'name'=>'镜片加工报损']
            ],
            6=>[
                ['id'=>1,'name'=>'加工调整'],
                ['id'=>2,'name'=>'镜架报损'],
                ['id'=>3,'name'=>'镜片报损'],
                ['id'=>4,'name'=>'logo调整']
            ]
        ];
        $abnormal_list = $abnormal_arr[$check_status] ?? [];

        $this->success('', ['abnormal_list' => $abnormal_list,'option_info' => $option_info],200);
    }

    /**
     * 提交操作（配货通用）
     *
     * @param string $item_order_number  子订单号
     * @param int $check_status  检测状态
     * @author lzh
     * @return mixed
     */
    public function distribution_save($item_order_number,$check_status)
    {
        empty($item_order_number) && $this->error(__('子订单号不能为空'), [], 403);

        //获取子订单数据
        $_new_order_item_process = new \app\admin\model\order\order\NewOrderItemProcess();
        $item_process_info = $_new_order_item_process
            ->where('item_order_number', $item_order_number)
            ->value('id,distribution_status,option_id,order_id')
            ->find()
        ;

        //获取子订单处方数据
        $_new_order_item_option = new \app\admin\model\order\order\NewOrderItemOption();
        $item_option_info = $_new_order_item_option
            ->where('id', $item_process_info['option_id'])
            ->value('is_print_logo,qty,sku')
            ->find()
        ;

        //状态类型
        $status_arr = [
            3=>'配镜片',
            4=>'加工',
            5=>'印logo',
            6=>'成品质检'
        ];

        //操作失败记录
        if(empty($item_process_info)){
            DistributionLog::record($this->auth,$item_process_info['id'],$status_arr[$check_status].'：子订单不存在');
            $this->error(__('子订单不存在'), [], 403);
        }

        //操作失败记录
        if($check_status != $item_process_info['distribution_status']){
            DistributionLog::record($this->auth,$item_process_info['id'],$status_arr[$check_status].'：当前状态['.$status_arr[$item_process_info['distribution_status']].']无法操作');
            $this->error(__('当前状态无法操作'), [], 405);
        }

        //检测异常状态
        $_distribution_abnormal = new \app\admin\model\DistributionAbnormal();
        $abnormal_id = $_distribution_abnormal
            ->where(['item_process_id'=>$item_process_info['id'],'status'=>1])
            ->value('id')
        ;

        //操作失败记录
        if($abnormal_id){
            DistributionLog::record($this->auth,$item_process_info['id'],$status_arr[$check_status].'：有异常['.$abnormal_id.']待处理不可操作');
            $this->error(__('有异常待处理无法操作'), [], 405);
        }

        //TODO::检测工单状态

        //获取订单购买总数
        $_new_order = new \app\admin\model\order\order\NewOrder();
        $total_qty_ordered = $_new_order
            ->where('id', $item_process_info['order_id'])
            ->value('total_qty_ordered')
        ;

        //下一步提示信息及状态
        if(3 == $check_status){
            $save_status = 4;
        }elseif(4 == $check_status){
            if($item_option_info['is_print_logo']){
                $save_status = 5;
            }else{
                $save_status = 6;
            }
        }elseif(5 == $check_status){
            $save_status = 6;
        }elseif(6 == $check_status){
            if($total_qty_ordered > 1){
                $save_status = 7;
            }else{
                $save_status = 9;
            }

            //扣减订单占用库存、配货占用库存、总库存
            $_item = new \app\admin\model\itemmanage\Item;
            $_item
                ->where(['sku'=>$item_option_info['sku']])
                ->dec('occupy_stock', $item_option_info['qty'])
                ->dec('distribution_occupy_stock', $item_option_info['qty'])
                ->dec('stock', $item_option_info['qty'])
                ->update()
            ;
        }

        //订单主表标记已合单
        if(9 == $save_status){
            $_new_order
                ->allowField(true)
                ->isUpdate(true, ['id'=>$item_process_info['order_id']])
                ->save(['combined_order_status'=>1])
            ;
        }

        $res = $_new_order_item_process
            ->allowField(true)
            ->isUpdate(true, ['item_order_number'=>$item_order_number])
            ->save(['distribution_status'=>$save_status])
        ;
        if($res){
            //操作成功记录
            DistributionLog::record($this->auth,$item_process_info['id'],$status_arr[$check_status].'完成');

            //成功返回
            $next_step = [
                4=>'去加工',
                5=>'印logo',
                6=>'去质检',
                7=>'去合单',
                9=>'去审单'
            ];
            $this->success($next_step[$save_status], [],200);
        }else{
            //操作失败记录
            DistributionLog::record($this->auth,$item_process_info['id'],$status_arr[$check_status].'：保存失败');

            //失败返回
            $this->error(__($status_arr[$check_status].'失败'), [], 404);
        }
    }

    /**
     * 配镜片扫码
     *
     * @参数 string item_order_number  子订单号
     * @author lzh
     * @return mixed
     */
    public function distribution_lens()
    {
        $item_order_number = $this->request->request('item_order_number');
        $this->distribution_info($item_order_number,3);
    }

    /**
     * 配镜片提交
     *
     * @参数 string item_order_number  子订单号
     * @author lzh
     * @return mixed
     */
    public function distribution_lens_submit()
    {
        $item_order_number = $this->request->request('item_order_number');
        $this->distribution_save($item_order_number,3);
    }

    /**
     * 加工扫码
     *
     * @参数 string item_order_number  子订单号
     * @author lzh
     * @return mixed
     */
    public function distribution_machining()
    {
        $item_order_number = $this->request->request('item_order_number');
        $this->distribution_info($item_order_number,4);
    }

    /**
     * 加工提交
     *
     * @参数 string item_order_number  子订单号
     * @author lzh
     * @return mixed
     */
    public function distribution_machining_submit()
    {
        $item_order_number = $this->request->request('item_order_number');
        $this->distribution_save($item_order_number,4);
    }

    /**
     * 成品质检扫码
     *
     * @参数 string item_order_number  子订单号
     * @author lzh
     * @return mixed
     */
    public function distribution_finish()
    {
        $item_order_number = $this->request->request('item_order_number');
        $this->distribution_info($item_order_number,6);
    }

    /**
     * 质检通过/拒绝
     *
     * @参数 string item_order_number  子订单号
     * @参数 int do_type  操作类型：1通过，2拒绝
     * @参数 int reason  拒绝原因
     * @author lzh
     * @return mixed
     */
    public function distribution_finish_adopt()
    {
        $item_order_number = $this->request->request('item_order_number');
        $do_type = $this->request->request('do_type');
        !in_array($do_type,[1,2]) && $this->error(__('操作类型错误'), [], 403);

        if($do_type == 1){
            $this->distribution_save($item_order_number,6);
        }else{
            $reason = $this->request->request('reason');
            !in_array($reason,[1,2,3,4]) && $this->error(__('拒绝原因错误'), [], 403);

            //获取子订单数据
            $_new_order_item_process = new \app\admin\model\order\order\NewOrderItemProcess();
            $item_process_info = $_new_order_item_process
                ->where('item_order_number', $item_order_number)
                ->field('id,option_id,order_id')
                ->find()
            ;

            //状态
            $status_arr = [
                1=>['status'=>4,'name'=>'质检拒绝：加工调整'],
                2=>['status'=>2,'name'=>'质检拒绝：镜架报损'],
                3=>['status'=>3,'name'=>'质检拒绝：镜片报损'],
                4=>['status'=>5,'name'=>'质检拒绝：logo调整']
            ];

            Db::startTrans();
            try {
                //子订单状态回滚
                $_new_order_item_process
                    ->allowField(true)
                    ->isUpdate(true, ['id'=>$item_process_info['id']])
                    ->save(['distribution_status'=>$status_arr[$reason]['status']])
                ;

                //镜片报损扣减可用库存、虚拟仓库存、配货占用库存、总库存
                if(2 == $reason){
                    //获取订单主表数据
                    $_new_order = new \app\admin\model\order\order\NewOrder();
                    $order_info = $_new_order
                        ->where('id', $item_process_info['order_id'])
                        ->value('site')
                        ->find()
                    ;

                    //获取子订单处方数据
                    $_new_order_item_option = new \app\admin\model\order\order\NewOrderItemOption();
                    $item_option_info = $_new_order_item_option
                        ->where('id', $item_process_info['option_id'])
                        ->value('qty,sku')
                        ->find()
                    ;

                    //获取true_sku
                    $_item_platform_sku = new \app\admin\model\itemmanage\ItemPlatformSku();
                    $true_sku = $_item_platform_sku->getTrueSku($item_option_info['sku'], $order_info['site']);

                    //扣减虚拟仓库存
                    $_item_platform_sku
                        ->where(['sku'=>$true_sku,'platform_type'=>$order_info['site']])
                        ->dec('stock', $item_option_info['qty'])
                        ->update()
                    ;

                    //扣减可用库存、配货占用库存、总库存
                    $_item = new \app\admin\model\itemmanage\Item();
                    $_item
                        ->where(['sku'=>$true_sku])
                        ->dec('available_stock', $item_option_info['qty'])
                        ->dec('distribution_occupy_stock', $item_option_info['qty'])
                        ->dec('stock', $item_option_info['qty'])
                        ->update()
                    ;
                }

                //记录日志
                DistributionLog::record($this->auth,$item_process_info['id'],$status_arr[$reason]['name']);

                Db::commit();
            } catch (ValidateException $e) {
                Db::rollback();
                $this->error($e->getMessage(), [], 406);
            } catch (PDOException $e) {
                Db::rollback();
                $this->error($e->getMessage(), [], 407);
            } catch (Exception $e) {
                Db::rollback();
                $this->error($e->getMessage(), [], 408);
            }
        }
    }

    /**
     * 印logo扫码
     *
     * @参数 string item_order_number  子订单号
     * @author lzh
     * @return mixed
     */
    public function distribution_logo()
    {
        $item_order_number = $this->request->request('item_order_number');
        $this->distribution_info($item_order_number,5);
    }

    /**
     * 印logo提交
     *
     * @参数 string item_order_number  子订单号
     * @author lzh
     * @return mixed
     */
    public function distribution_logo_submit()
    {
        $item_order_number = $this->request->request('item_order_number');
        $this->distribution_save($item_order_number,5);
    }

    //--------------------合并前备份---------------------//
    /**
     * 待入库列表
     *
     * 需求不明确，暂时滞留，等待原型图
     *
     * @参数 string query  查询内容
     * @参数 int status  状态
     * @参数 string start_time  开始时间
     * @参数 string end_time  结束时间
     * @参数 int page  页码
     * @参数 int page_size  每页显示数量
     * @author wgj
     * @return mixed
     */
    public function no_in_stock_list()
    {
        $query = $this->request->request('query');
        $status = $this->request->request('status');
        $start_time = $this->request->request('start_time');
        $end_time = $this->request->request('end_time');
        $page = $this->request->request('page');
        $page_size = $this->request->request('page_size');

        empty($page) && $this->error(__('Page can not be empty'), [], 406);
        empty($page_size) && $this->error(__('Page size can not be empty'), [], 407);

        $where = [];
        $where['a.is_stock'] = 0;//质检单待入库状态为0
        if($query){
            $where['a.check_order_number|b.sku|c.logistics_number'] = ['like', '%' . $query . '%'];
        }
        if($status){
            $where['a.status'] = $status;
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
            ->field('a.id,a.check_order_number,c.logistics_number,a.createtime createtime,')
            ->join(['fa_check_order_item' => 'b'], 'a.id=b.check_id','left')
            ->join(['fa_logistics_info' => 'c'], 'a.logistics_id=c.id')
            ->order('a.createtime', 'desc')
            ->limit($offset, $limit)
            ->select();
        $list = collection($list)->toArray();

        $this->success('', ['list' => $list],200);
    }

    /**
     * 入库单列表
     *
     * @参数 string query  查询内容
     * @参数 int status  状态
     * @参数 string start_time  开始时间
     * @参数 string end_time  结束时间
     * @参数 int page  * 页码
     * @参数 int page_size  * 每页显示数量
     * @author wgj
     * @return mixed
     */
    public function in_stock_list()
    {
        $query = $this->request->request('query');
        $status = $this->request->request('status');
        $start_time = $this->request->request('start_time');
        $end_time = $this->request->request('end_time');
        $page = $this->request->request('page');
        $page_size = $this->request->request('page_size');

        empty($page) && $this->error(__('Page can not be empty'), [], 501);
        empty($page_size) && $this->error(__('Page size can not be empty'), [], 502);

        $where = [];
        if($query){
            $where['a.in_stock_number|c.check_order_number|b.sku|a.create_person|c.create_person'] = ['like', '%' . $query . '%'];
        }
        if($status){
            $where['a.status'] = $status;
        }
        if($start_time && $end_time){
            $where['a.createtime'] = ['between', [$start_time, $end_time]];
        }

        $offset = ($page - 1) * $page_size;
        $limit = $page_size;

        //获取入库单列表数据
        $_in_stock = new \app\admin\model\warehouse\Instock;
        $list = $_in_stock
            ->alias('a')
            ->where($where)
            ->field('a.id,a.in_stock_number,b.check_order_number,a.createtime,a.status')
            ->join(['fa_check_order' => 'b'], 'a.check_id=b.id')
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
     * 取消入库单
     *
     * @参数 int in_stock_id  入库单ID
     * @author wgj
     * @return mixed
     */
    public function in_stock_cancel()
    {
        $in_stock_id = $this->request->request('in_stock_id');
        empty($in_stock_id) && $this->error(__('Id can not be empty'), [], 503);

        //检测入库单状态
        $_in_stock = new \app\admin\model\warehouse\Instock;
        $row = $_in_stock->get($in_stock_id);
        0 != $row['status'] && $this->error(__('只有新建状态才能取消'), [], 504);

        $res = $_in_stock->allowField(true)->isUpdate(true, ['id'=>$in_stock_id])->save(['status'=>4]);
        $res ? $this->success('取消成功', [],200) : $this->error(__('取消失败'), [], 505);
    }

    /**
     * 新建/编辑入库提交/保存
     *
     * 提交后状态为待审核status=1/保存后状态为新建status=0
     *
     * 需要再次确定添加和编辑页面的字段
     *
     * @参数 int in_stock_id  入库单ID（编辑时必传）
     * @参数 int type_id  入库分类ID（新建时必传）
     * @参数 string in_stock_number  入库单号(新建时必传）
     * @参数 int platform_id  平台/站点ID（入库单新创建时必传，质检单入口创建时不传）
     * @参数 int do_type  提交类型：1提交2保存
     * @参数 json item_sku  sku数据集合
     * @author wgj
     * @return mixed
     */
    public function in_stock_submit()
    {
        //----------------需要再次确定添加和编辑页面的字段----------------//
        //----------------原型图未确定,暂停----------------//
        //----------------确定质检单入库时是否可以修改SKU类目和数量,逻辑需要----------------//
        $do_type = $this->request->request('do_type');
        $item_sku = $this->request->request("sku");
        $item_sku = array_filter(json_decode($item_sku,true));
        if (count(array_filter($item_sku)) < 1) {
            $this->error(__('sku集合不能为空！！'), [], 507);
        }

        $in_stock_number = $this->request->request("in_stock_number");
        $check_id = $this->request->request("check_id");
        $type_id = $this->request->request("type_id");
        empty($in_stock_number) && $this->error(__('入库单号不能为空'), [], 508);
        empty($check_id) && $this->error(__('质检单号不能为空'), [], 509);
        empty($type_id) && $this->error(__('请选择入库分类'), [], 510);

        $params['in_stock_number'] = $in_stock_number;
        $params['check_id'] = $check_id;
        $params['type_id'] = $type_id;
        $params['status'] = 1 == $do_type ?? 0;

        $platform_id = $this->request->request("platform_id");
        $in_stock_id = $this->request->request("in_stock_id");

        $_in_stock = new \app\admin\model\warehouse\Instock;
        $_in_stock_item = new \app\admin\model\warehouse\InstockItem;

        if ($in_stock_id) {
            //编辑入库单
            $row = $_in_stock->get($in_stock_id);
            empty($row) && $this->error(__('入库单不存在'), [], 512);

            //编辑入库单
            $_in_stock_data = [
                'type_id'=> $type_id,
                'status'=>1 == $do_type ?? 0
            ];
            $result = $_in_stock->allowField(true)->save($_in_stock_data, ['id' => $in_stock_id]);

            //修改入库信息
            if ($in_stock_number !== $row['in_stock_number']) {
                //更改质检单为已创建入库单
                $_check = new \app\admin\model\warehouse\Check;
                $_check->allowField(true)->save(['is_stock' => 1], ['id' => $check_id]);

                $save_data = [];
                foreach (array_filter($item_sku) as $k => $v) {
                    $save_data['sku'] = $v['sku'];
                    $save_data['in_stock_num'] = $v['in_stock_num'];//入库数量
//                            $save_data['sample_num'] = $v['sample_num'];//留样数量
//                            $save_data['no_stock_num'] = $v['no_stock_num'];//未入库数量
//                            $save_data['purchase_id'] = $v['purchase_id'];//采购单ID
                    $save_data['in_stock_id'] = $in_stock_id;
                    $_in_stock_item->allowField(true)->save($save_data, ['id' => $in_stock_id]);
                }
            }

        } else {

            //新建入库单
            $result = false;
            Db::startTrans();
            try {

                //存在平台id 代表把当前入库单的sku分给这个平台 首先做判断 判断入库单的sku是否都有此平台对应的映射关系
                if ($platform_id) {
                    $params['platform_id'] = $platform_id;
                    foreach (array_filter($item_sku) as $k => $v) {
                        $item_platform_sku = new \app\admin\model\itemmanage\ItemPlatformSku();

                        $sku_platform = $item_platform_sku->where(['sku' => $v['sku'], 'platform_type' => $params['platform_id']])->find();
                        if (!$sku_platform) {
                            $this->error('此sku：' . $v['sku'] . '没有同步至此平台，请先同步后重试');
                        }
                    }
                    $params['create_person'] = $this->auth->nickname;
                    $params['createtime'] = date('Y-m-d H:i:s', time());
                    //新增入库单
                    $result = $_in_stock->allowField(true)->save($params);

                    //添加入库信息
                    if ($result !== false) {
                        $data = [];
                        foreach (array_filter($item_sku) as $k => $v) {
                            $data[$k]['sku'] = $v['sku'];
                            $data[$k]['in_stock_num'] = $v['in_stock_num'];//入库数量
//                            $data[$k]['sample_num'] = $v['sample_num'];//留样数量
//                            $data[$k]['no_stock_num'] = $v['no_stock_num'];//未入库数量
//                            $data[$k]['purchase_id'] = $v['purchase_id'];//采购单ID
                            $data[$k]['in_stock_id'] = $result;
                        }
                        //批量添加
                        $_in_stock_item->allowField(true)->saveAll($data);
                    }
                } else {
                    //质检单页面去入库单
                    $params['create_person'] = $this->auth->nickname;
                    $params['createtime'] = date('Y-m-d H:i:s', time());
                    $result = $_in_stock->allowField(true)->save($params);

                    //添加入库信息
                    if ($result !== false) {
                        //更改质检单为已创建入库单
                        $_check = new \app\admin\model\warehouse\Check;
                        $_check->allowField(true)->save(['is_stock' => 1], ['id' => $params['check_id']]);

                        $data = [];
                        foreach (array_filter($item_sku) as $k => $v) {
                            $data[$k]['sku'] = $v['sku'];
                            $data[$k]['in_stock_num'] = $v['in_stock_num'];//入库数量
//                            $data[$k]['sample_num'] = $v['sample_num'];//留样数量
//                            $data[$k]['no_stock_num'] = $v['no_stock_num'];//未入库数量
//                            $data[$k]['purchase_id'] = $v['purchase_id'];//采购单ID
                            $data[$k]['in_stock_id'] = $result;
                        }
                        //批量添加
                        $_in_stock_item->allowField(true)->saveAll($data);
                    }
                }

                Db::commit();
            } catch (ValidateException $e) {
                Db::rollback();
                $this->error($e->getMessage(), [], 444);
            } catch (PDOException $e) {
                Db::rollback();
                $this->error($e->getMessage(), [], 444);
            } catch (Exception $e) {
                Db::rollback();
                $this->error($e->getMessage(), [], 444);
            }
            if ($result !== false) {
                $this->success('添加成功！！', '', 200);
            } else {
                $this->error(__('No rows were inserted'), [], 511);
            }
        }

    }

    /**
     * 新建入库单页面
     *
     * @参数 int type  新建入口 1.质检单，2.入库单
     * @参数 int check_id  质检单ID（type为1时必填，为2时不填）
     * @author wgj
     * @return mixed
     */
    public function in_stock_add()
    {
        //根据type值判断是从哪个入口进入的添加入库单 type值为1是从质检入口进入 type值为2是从入库单直接添加 直接添加的需要选择站点
        $type = $this->request->request("type");
        $info = [];
        if ($type == 1){
            //质检单页面进入创建入库单
            $check_id = $this->request->request("check_id");
            empty($check_id) && $this->error(__('质检单号不能为空'), [], 513);
            $_check = new \app\admin\model\warehouse\Check;
            $check_info = $_check->get($check_id);
            //入库单所需数据
            $info['check_id'] = $check_id;
            $info['order_number'] = $check_info['order_number'];

        } else {
            //入库单直接添加，查询站点数据
            $magento_platform = new \app\admin\model\platformmanage\MagentoPlatform;
            $platform_list = $magento_platform->field('id, name')->where('is_del=>1, status=>1')->select();
            $info['platform_list'] = $platform_list;

        }

        //查询入库分类
        $_in_stock_type = new \app\admin\model\warehouse\InstockType;
        $in_stock_type = $_in_stock_type->field('id, name')->where('is_del', 1)->select();

        //入库单所需数据
        $info['in_stock_number'] = 'IN' . date('YmdHis') . rand(100, 999) . rand(100, 999);
        $info['in_stock_type'] = $in_stock_type;

        $this->success('', ['info' => $info],200);
    }

    /**
     * 编辑入库单页面/详情/入库单审核页面
     *
     * @参数 int in_stock_id  入库单ID
     * @author wgj
     * @return mixed
     */
    public function in_stock_edit()
    {
        $in_stock_id = $this->request->request('in_stock_id');
        empty($in_stock_id) && $this->error(__('入库单ID不能为空'), [], 514);
        //获取入库单数据
        $_in_stock = new \app\admin\model\warehouse\Instock;
        $_in_stock_info = $_in_stock->get($in_stock_id);
        empty($_in_stock_info) && $this->error(__('入库单不存在'), [], 515);
        $_check = new \app\admin\model\warehouse\Check;
        $check_order_number = $_check->get($_in_stock_info['check_id']);
//        var_dump($check_order_number);
//        die;

//        $_in_stock_check_info = $_in_stock
//            ->alias('a')
//            ->where(['a.id'=>$in_stock_id])
//            ->field('a.id, a.in_stock_number, b.check_order_number')
//            ->join(['fa_check_order' => 'b'], 'a.check_id=b.id')
//            ->find();
//        $_in_stock_check_info = collection($_in_stock_check_info)->toArray();

        //获取入库单列表数据
        $_in_stock = new \app\admin\model\warehouse\Instock;
        $item_list = $_in_stock
            ->alias('a')
            ->where(['a.id'=>$in_stock_id])
            ->field('b.sku,c.quantity_num,b.in_stock_num')
            ->join(['fa_in_stock_item' => 'b'], 'a.id=b.in_stock_id')
            ->join(['fa_check_order_item' => 'c'], 'a.check_id=c.check_id')
            ->join(['fa_check_order' => 'd'], 'a.check_id=d.id')
            ->order('a.createtime', 'desc')
            ->select();
        $item_list = collection($item_list)->toArray();

        /*        //获取质检单商品数据
                $_in_stock_item = new \app\admin\model\warehouse\InstockItem;
                $item_list = $_in_stock_item
                    ->alias('a')
                    ->where(['a.in_stock_id'=>$in_stock_id])
                    ->field('sku,supplier_sku,arrival_num,quantity_num,unqualified_num,sample_num,should_arrival_num')
                    ->select();
                $item_list = collection($item_list)->toArray();*/

        //入库单所需数据
        $info =[
            'in_stock_id'=>$_in_stock_info['id'],
            'in_stock_number'=>$_in_stock_info['in_stock_number'],
            'check_order_number'=>$check_order_number['check_order_number'],
            'item_list'=>$item_list,
        ];

        //查询入库分类
        $_in_stock_type = new \app\admin\model\warehouse\InstockType;
        $in_stock_type = $_in_stock_type->field('id, name')->where('is_del', 1)->select();

        $info['in_stock_type_list'] = $in_stock_type;

        $this->success('', ['info' => $info],200);
    }

    /**
     * 入库审核 通过/拒绝
     *
     * @参数 int check_id  入库单ID
     * @参数 int do_type  1审核通过，2审核拒绝
     * @author wgj
     * @return mixed
     */
    public function in_stock_examine()
    {
        $in_stock_id = $this->request->request('in_stock_id');
        empty($in_stock_id) && $this->error(__('入库单ID不能为空'), [], 516);

        $do_type = $this->request->request('do_type');
        empty($do_type) && $this->error(__('审核类型不能为空'), [], 517);

        //检测入库单状态
        $_in_stock = new \app\admin\model\warehouse\Instock;
        $row = $_in_stock->get($in_stock_id);
        1 != $row['status'] && $this->error(__('只有待审核状态才能操作'), [], 518);

        $data['status'] = $this->request->request('do_type');//审核状态，2通过，3拒绝
        if ($data['status'] == 2) {
            $data['check_time'] = date('Y-m-d H:i:s', time());
        }

        //查询入库明细数据
        $list = $_in_stock
            ->alias('a')
            ->join(['fa_in_stock_item' => 'b'], 'a.id=b.in_stock_id')
            ->where(['a.id' => $in_stock_id])
            ->select();
        $list = collection($list)->toArray();
        //查询存在产品库的sku
        $item = new \app\admin\model\itemmanage\Item;
        $skus = array_column($list, 'sku');
        $skus = $item->where(['sku' => ['in', $skus]])->column('sku');
        foreach ($list as $v) {
            if (!in_array($v['sku'], $skus)) {
                $this->error('此sku:' . $v['sku'] . '不存在！！');
            }
        }
        $new_product_mapp = new \app\admin\model\NewProductMapping();
        $platform = new \app\admin\model\itemmanage\ItemPlatformSku();
        $_in_stock->startTrans();
        $item = new \app\admin\model\itemmanage\Item;
        $item->startTrans();
        $purchase = new \app\admin\model\purchase\PurchaseOrderItem;
        $allocated = new \app\admin\model\itemmanage\GoodsStockAllocated;
        $purchase->startTrans();
        $this->purchase->startTrans();

        try {
            $data['create_person'] = $this->auth->nickname;
            $res = $_in_stock->allowField(true)->isUpdate(true, ['id'=>$in_stock_id])->save($data);

            if ($data['status'] == 2) {
                /**
                 * @todo 审核通过增加库存 并添加入库单入库数量
                 */
                $error_num = [];
                foreach ($list as $k => $v) {

                    //审核通过对虚拟库存的操作
                    //审核通过时按照补货需求比例 划分各站虚拟库存 如果未关联补货需求单则按照当前各站虚拟库存数量实时计算各站比例（弃用）
                    //采购过来的 有采购单的 1、有补货需求单的直接按比例分配 2、没有补货需求单的都给m站
                    if ($v['purchase_id']) {
                        if ($v['replenish_id']) {
                            //查询各站补货需求量占比
                            $rate_arr = $new_product_mapp
                                ->where(['replenish_id' => $v['replenish_id'], 'sku' => $v['sku'], 'is_show' => 0])
                                // ->order('rate asc')
                                ->field('rate,website_type')
                                ->select();
                            // dump(collection($rate_arr)->toArray());die;
                            //根据入库数量插入各站虚拟仓库存
                            $all_num = count($rate_arr);
                            $stock_num = $v['in_stock_num'];
                            foreach ($rate_arr as $key => $val) {
                                //最后一个站点 剩余数量分给最后一个站
                                if (($all_num - $key) == 1) {
                                    //增加站点虚拟仓库存
                                    $platform->where(['sku' => $v['sku'], 'platform_type' => $val['website_type']])->setInc('stock', $stock_num);
                                    //入库的时候减少待入库数量
                                    $platform->where(['sku' => $v['sku'], 'platform_type' => $val['website_type']])->setDec('wait_instock_num', $stock_num);

                                } else {
                                    $num = round($v['in_stock_num'] * $val['rate']);
                                    $stock_num -= $num;
                                    //增加站点虚拟仓库存
                                    $platform->where(['sku' => $v['sku'], 'platform_type' => $val['website_type']])->setInc('stock', $num);
                                    //入库的时候减少待入库数量
                                    $platform->where(['sku' => $v['sku'], 'platform_type' => $val['website_type']])->setDec('wait_instock_num', $num);
                                }
                            }
                        } else {
                            //记录没有采购比例直接入库的sku
                            $allocated->allowField(true)->save(['sku' => $v['sku'], 'change_num' => $v['in_stock_num'], 'create_time' => date('Y-m-d H:i:s')]);

                            $item_platform_sku = $platform->where(['sku' => $v['sku'], 'platform_type' => 4])->field('platform_type,stock')->find();
                            //sku没有同步meeloog站 无法添加虚拟库存 必须先同步
                            if (empty($item_platform_sku)) {
                                $this->error('sku：' . $v['sku'] . '没有同步meeloog站，请先同步');
                            }
                            $platform->where(['sku' => $v['sku'], 'platform_type' => $item_platform_sku['platform_type']])->setInc('stock', $v['in_stock_num']);
                        }
                    } //不是采购过来的 如果有站点id 说明是指定增加此平台sku
                    elseif ($v['platform_id']) {
                        $platform->where(['sku' => $v['sku'], 'platform_type' => $v['platform_id']])->setInc('stock', $v['in_stock_num']);
                    } //没有采购单也没有站点id 说明是盘点过来的
                    else {
                        //根据当前sku 和当前 各站的虚拟库存进行分配
                        $item_platform_sku = $platform->where('sku', $v['sku'])->order('stock asc')->field('platform_type,stock')->select();
                        $all_num = count($item_platform_sku);

                        $stock_num = $v['in_stock_num'];
                        //计算当前sku的总虚拟库存 如果总的为0 表示当前所有平台的此sku都为0 此时入库的话按照平均规则分配 例如五个站都有此品 那么比例就是20%
                        $stock_all_num = array_sum(array_column($item_platform_sku, 'stock'));
                        if ($stock_all_num == 0) {
                            $rate_rate = 1/$all_num;
                            foreach ($item_platform_sku as $key => $val) {
                                //最后一个站点 剩余数量分给最后一个站
                                if (($all_num - $key) == 1) {
                                    $platform->where(['sku' => $v['sku'], 'platform_type' => $val['platform_type']])->setInc('stock', $stock_num);
                                } else {
                                    $num = round($v['in_stock_num'] * $rate_rate);
                                    $stock_num -= $num;
                                    $platform->where(['sku' => $v['sku'], 'platform_type' => $val['platform_type']])->setInc('stock', $num);
                                }
                            }
                        } else {
                            //某個平台這個sku存在庫存 就按照當前各站的虛擬庫存進行分配
                            $whole_num = $platform->where('sku', $v['sku'])->sum('stock');
                            $stock_num = $v['in_stock_num'];
                            foreach ($item_platform_sku as $key => $val) {
                                //最后一个站点 剩余数量分给最后一个站
                                if (($all_num - $key) == 1) {
                                    $platform->where(['sku' => $v['sku'], 'platform_type' => $val['platform_type']])->setInc('stock', $stock_num);
                                } else {
                                    $num = round($v['in_stock_num'] * $val['stock'] / $whole_num);
                                    $stock_num -= $num;
                                    $platform->where(['sku' => $v['sku'], 'platform_type' => $val['platform_type']])->setInc('stock', $num);
                                }
                            }
                        }
                    }
                    // if ($v['replenish_id']) {
                    //     //查询各站补货需求量占比
                    //     $rate_arr = $new_product_mapp->where(['replenish_id' => $v['replenish_id'], 'sku' => $v['sku'], 'is_show' => 0])->order('rate asc')->field('rate,website_type')->select();
                    //     // dump(collection($rate_arr)->toArray());die;
                    //     //根据入库数量插入各站虚拟仓库存
                    //     $all_num = count($rate_arr);
                    //     $stock_num = $v['in_stock_num'];
                    //     foreach ($rate_arr as $key => $val) {
                    //         //最后一个站点 剩余数量分给最后一个站
                    //         if (($all_num - $key) == 1) {
                    //             $platform->where(['sku' => $v['sku'], 'platform_type' => $val['website_type']])->setInc('stock', $stock_num);
                    //         } else {
                    //             $num = round($v['in_stock_num'] * $val['rate']);
                    //             $stock_num -= $num;
                    //             $platform->where(['sku' => $v['sku'], 'platform_type' => $val['website_type']])->setInc('stock', $num);
                    //         }
                    //     }
                    // }
                    // else {
                    //     //样品入库单独逻辑给现在库存最大的那个站
                    //     if ($v['type_id'] == 6) {
                    //         $item_platform_sku = $platform->where('sku', $v['sku'])->order('stock desc')->field('platform_type,stock')->find();
                    //         $stock_num = $v['in_stock_num'];
                    //         $platform->where(['sku' => $v['sku'], 'platform_type' => $item_platform_sku['platform_type']])->setInc('stock', $stock_num);
                    //     }
                    //     //现在先使用此规则 没有关联到采购需求比例的入库单，默认分配到杭州站点的虚拟仓（meeloog）
                    //     else{
                    //         $item_platform_sku = $platform->where(['sku'=>$v['sku'],'platform_type'=>4])->field('platform_type,stock')->find();
                    //         //sku没有同步meeloog站 无法添加虚拟库存 必须先同步
                    //         if (empty($item_platform_sku)){
                    //             $this->error('sku：'.$v['sku'].'没有同步meeloog站，请先同步');
                    //         }
                    //         $platform->where(['sku' => $v['sku'], 'platform_type' => $item_platform_sku['platform_type']])->setInc('stock', $v['in_stock_num']);
                    //     }
                    //     // else {
                    //     //     //没有补货需求单的入库单 根据当前sku 和当前 各站的虚拟库存进行分配
                    //     //     $item_platform_sku = $platform->where('sku', $v['sku'])->order('stock asc')->field('platform_type,stock')->select();
                    //     //     $all_num = count($item_platform_sku);
                    //     //
                    //     //     $stock_num = $v['in_stock_num'];
                    //     //     //计算当前sku的总虚拟库存 如果总的为0 表示当前所有平台的此sku都为0 此时入库的话按照‘发牌’规则进行分库存
                    //     //     $stock_all_num = array_sum(array_column($item_platform_sku, 'stock'));
                    //     //     if ($stock_all_num == 0) {
                    //     //         //当前入库数量有几个就循环几次
                    //     //         foreach ($item_platform_sku as $key => $val) {
                    //     //
                    //     //             //一直发直到$v['in_stock_num']为0
                    //     //             $platform->where(['sku' => $v['sku'], 'platform_type' => $val['platform_type']])->setInc('stock');
                    //     //             $stock_num--;
                    //     //             if ($stock_num == 0) {
                    //     //                 break;
                    //     //             } else {
                    //     //                 if (($all_num - $key) == 1) {
                    //     //                     $this->send_stock($item_platform_sku, $stock_num, $v['sku'], $all_num);
                    //     //                 }
                    //     //             }
                    //     //         }
                    //     //     } else {
                    //     //         //某個平台這個sku存在庫存 就按照當前各站的虛擬庫存進行分配
                    //     //         $whole_num = $platform->where('sku', $v['sku'])->sum('stock');
                    //     //         //                                dump($whole_num);die;
                    //     //         $stock_num = $v['in_stock_num'];
                    //     //         foreach ($item_platform_sku as $key => $val) {
                    //     //             //最后一个站点 剩余数量分给最后一个站
                    //     //             if (($all_num - $key) == 1) {
                    //     //                 $platform->where(['sku' => $v['sku'], 'platform_type' => $val['platform_type']])->setInc('stock', $stock_num);
                    //     //             } else {
                    //     //                 $num = round($v['in_stock_num'] * $val['stock'] / $whole_num);
                    //     //                 $stock_num -= $num;
                    //     //                 $platform->where(['sku' => $v['sku'], 'platform_type' => $val['platform_type']])->setInc('stock', $num);
                    //     //             }
                    //     //         }
                    //     //     }
                    //     // }
                    // }

                    //更新商品表商品总库存
                    //总库存
                    $item_map['sku'] = $v['sku'];
                    $item_map['is_del'] = 1;
                    if ($v['sku']) {
                        //增加商品表里的商品库存、可用库存、留样库存
                        $stock_res = $item->where($item_map)->inc('stock', $v['in_stock_num'])->inc('available_stock', $v['in_stock_num'])->inc('sample_num', $v['sample_num'])->update();
                        //减少待入库数量
                        $stock_res1 = $item->where($item_map)->dec('wait_instock_num', $v['in_stock_num'])->update();
                    }

                    if ($stock_res === false) {
                        $error_num[] = $k;
                    }

                    //根据质检id 查询采购单id
                    $check = new \app\admin\model\warehouse\Check;
                    $check_res = $check->where('id', $v['check_id'])->find();
                    //更新采购商品表 入库数量 如果为真则为采购入库
                    if ($check_res['purchase_id']) {
                        $purchase_map['sku'] = $v['sku'];
                        $purchase_map['purchase_id'] = $check_res['purchase_id'];
                        $purchase->where($purchase_map)->setInc('instock_num', $v['in_stock_num']);

                        //更新采购单状态 已入库 或 部分入库
                        //查询采购单商品总到货数量 以及采购数量
                        //查询质检信息
                        $check_map['Check.purchase_id'] = $check_res['purchase_id'];
                        $check_map['type'] = 1;
                        $check = new \app\admin\model\warehouse\Check;
                        //总到货数量
                        $all_arrivals_num = $check->hasWhere('checkItem')->where($check_map)->group('Check.purchase_id')->sum('arrivals_num');

                        $all_purchase_num = $purchase->where('purchase_id', $check_res['purchase_id'])->sum('purchase_num');
                        //总到货数量 小于 采购单采购数量 则为部分入库
                        if ($all_arrivals_num < $all_purchase_num) {
                            $stock_status = 1;
                        } else {
                            $stock_status = 2;
                        }
                        //修改采购单入库状态
                        $purchase_data['stock_status'] = $stock_status;
                        $this->purchase->where(['id' => $check_res['purchase_id']])->update($purchase_data);
                    }
                    //如果为退货单 修改退货单状态为入库
                    if ($check_res['order_return_id']) {
                        $orderReturn = new \app\admin\model\saleaftermanage\OrderReturn;
                        $orderReturn->where(['id' => $check_res['order_return_id']])->update(['in_stock_status' => 1]);
                    }


                    //插入日志表
                    (new StockLog())->setData([
                        'type' => 2,
                        'two_type' => 3,
                        'sku' => $v['sku'],
                        'public_id' => $v['in_stock_id'],
                        'stock_change' => $v['in_stock_num'],
                        'available_stock_change' => $v['in_stock_num'],
                        'sample_num_change' => $v['sample_num'],
                        'create_person' => $this->auth->nickname,
                        'create_time' => date('Y-m-d H:i:s'),
                        'remark' => '入库单增加总库存,可用库存,样品库存'
                    ]);
                }

                //有错误 则回滚数据
                if (count($error_num) > 0) {
                    $this->error(__('入库失败！！请检查SKU'), [], 444);
                }
            }

            $_in_stock->commit();
            $item->commit();
            $purchase->commit();
            $this->purchase->commit();
        } catch (ValidateException $e) {
            $_in_stock->rollback();
            $item->rollback();
            $purchase->rollback();
            $this->purchase->rollback();
            $this->error($e->getMessage(), [], 444);
        } catch (PDOException $e) {
            $_in_stock->rollback();
            $item->rollback();
            $purchase->rollback();
            $this->purchase->rollback();
            $this->error($e->getMessage(), [], 444);
        } catch (Exception $e) {
            $_in_stock->rollback();
            $item->rollback();
            $purchase->rollback();
            $this->purchase->rollback();
            $this->error($e->getMessage(), [], 444);
        }

        if ($res !== false) {
            $this->success('审核成功', [],200);
        } else {
            $this->error(__('审核失败'), [], 519);
        }

    }

    /**
     * 盘点单列表
     *
     * @参数 string query  查询内容
     * @参数 int status  状态
     * @参数 int check_status  审核状态
     * @参数 string start_time  开始时间
     * @参数 string end_time  结束时间
     * @参数 int page  * 页码
     * @参数 int page_size  * 每页显示数量
     * @author wgj
     * @return mixed
     */
    //join 关联查询出错数据量未与主表保持一致-----------改
    //子查询待调整
    public function inventory_list()
    {
        $query = $this->request->request('query');
        $status = $this->request->request('status');
        $check_status = $this->request->request('check_status');
        $start_time = $this->request->request('start_time');
        $end_time = $this->request->request('end_time');
        $page = $this->request->request('page');
        $page_size = $this->request->request('page_size');

        empty($page) && $this->error(__('Page can not be empty'), [], 520);
        empty($page_size) && $this->error(__('Page size can not be empty'), [], 521);

        $where = [];
        if($query){
            $where['a.number|b.sku|a.create_person'] = ['like', '%' . $query . '%'];
        }
        if($status){
            $where['a.status'] = $status;
        }
        if($check_status){
            $where['a.check_status'] = $check_status;
        }
        if($start_time && $end_time){
            $where['a.createtime'] = ['between', [$start_time, $end_time]];
        }

        $offset = ($page - 1) * $page_size;
        $limit = $page_size;

        //获取入库单列表数据
        $_inventory = new \app\admin\model\warehouse\Inventory;
        $list = $_inventory
            ->alias('a')
            ->where($where)
            ->field('a.id,a.number,a.createtime,a.status,a.check_status')
            ->join(['fa_inventory_item' => 'b'], 'a.id=b.inventory_id','left')
            ->order('a.createtime', 'desc')
            ->limit($offset, $limit)
            ->select();
        $list = collection($list)->toArray();

        $status = [ 0=>'待盘点',1=>'盘点中',2=>'已完成' ];
        $check_status = [ 0=>'新建',1=>'待审核',2=>'已审核',3=>'已拒绝',4=>'已取消' ];
        foreach($list as $key=>$value){
            $list[$key]['status'] = $check_status[$value['status']];
            $list[$key]['check_status'] = $status[$value['check_status']];
            $list[$key]['inventory_qty'] = '';//需要fa_inventory_item表数据加和
            $list[$key]['cancel_show'] = 0 == $value['status'] ? 1 : 0;
        }

        $this->success('', ['list' => $list],200);
    }

    /**
     * 创建盘点单页面/筛选/保存
     *
     * @参数 int type  新建入口 1.筛选，2.保存
     * @author wgj
     * @return mixed
     */
    public function inventory_add()
    {
        //根据type值判断是筛选还是保存 type值为1是筛选 type值为2是保存
        $type = $this->request->request("type") ?? 1;
        $info = [];
        if ($type == 1){
            //创建盘点单筛选 ok
            $query = $this->request->request('query');
            $start_stock = $this->request->request('start_stock');
            $end_stock = $this->request->request('end_stock');
            $page = $this->request->request('page');
            $page_size = $this->request->request('page_size');

            empty($page) && $this->error(__('Page can not be empty'), [], 522);
            empty($page_size) && $this->error(__('Page size can not be empty'), [], 523);

            $where['a.is_del'] = 1;
            $_inventory_item = new \app\admin\model\warehouse\InventoryItem;
            $skus = $_inventory_item
                ->alias('a')
                ->field('a.sku')
                ->where('b.status','in',[0,1])
                ->join(['fa_inventory_list'=>'b'],'a.inventory_id=b.id','left')
                ->select();
            $skus = collection($skus)->toArray();
            $skus = array_column($skus, 'sku');
            if($skus){
                $where['a.sku'] = ['not in', $skus];
            }
            if($query){
                $where['a.sku|b.library_name'] = ['like', '%' . $query . '%'];
            }
            if($start_stock && $end_stock){
                $where['c.stock'] = ['between', [$start_stock, $end_stock]];
            }

            $offset = ($page - 1) * $page_size;
            $limit = $page_size;

            //获取SKU库位绑定表（fa_store_sku）数据列表
            $_store_sku = new \app\admin\model\warehouse\StockSku;
            $list = $_store_sku
                ->alias('a')
                ->field('a.id,a.sku,b.library_name')
                ->where($where)
                ->join(['fa_store_house'=> 'b'],'a.store_id=b.id','left')
                ->join(['stock.fa_item'=> 'c'],'a.sku=c.sku','left')
                ->order('a.id', 'desc')
                ->limit($offset, $limit)
                ->select();
            $list = collection($list)->toArray();
            //盘点单所需数据
            $info['list'] = $list;
            $this->success('', ['info' => $info],200);

        } else {
            //点击保存，创建盘点单
            //继续写
            $item_sku = $this->request->request("sku");
            $item_sku = array_filter(json_decode($item_sku,true));
            if (count(array_filter($item_sku)) < 1) {
                $this->error(__('sku集合不能为空！！'), [], 524);
            }

            $result = false;
            Db::startTrans();
            try {

                //保存--创建盘点单
                $_inventory = new \app\admin\model\warehouse\Inventory;
                $_inventory_item = new \app\admin\model\warehouse\InventoryItem;
                $arr['number'] = 'IS' . date('YmdHis') . rand(100, 999) . rand(100, 999);
                $arr['create_person'] = $this->auth->nickname;
                $arr['createtime'] = date('Y-m-d H:i:s', time());
                $inventory_id = $_inventory->allowField(true)->save($arr);
                if ($inventory_id) {
                    $list = [];
                    foreach (array_filter($item_sku) as $k => $v) {
                        $list['in_stock_id'] = $inventory_id;
                        $list['sku'] = $v['sku'];
                        $_item = new \app\admin\model\itemmanage\Item;
                        $item = $_item->field('name,stock,available_stock,distribution_occupy_stock')->where('sku',$v['sku'])->find();
                        $list['name'] = $item['name'];//商品名
                        $list['real_time_qty'] = $item['real_time_qty'];//实时库存
                        $list['distribution_occupy_stock'] = $item['distribution_occupy_stock'];//配货站用数量
                        $real_time_qty = ($item['stock'] * 1 - $item['distribution_occupy_stock'] * 1);
                        $list['available_stock'] = $real_time_qty ?? 0;//可用库存
                        $list['inventory_qty'] = $v['inventory_qty'];//盘点数量
                        $list['error_qty'] = $v['error_qty'];//误差数量
                        $list['remark'] = $v['remark'];//备注
                    }
                    //添加明细表数据
                    $result = $this->item->allowField(true)->saveAll($list);
                }

                Db::commit();
            } catch (ValidateException $e) {
                Db::rollback();
                $this->error($e->getMessage(), [], 444);
            } catch (PDOException $e) {
                Db::rollback();
                $this->error($e->getMessage(), [], 444);
            } catch (Exception $e) {
                Db::rollback();
                $this->error($e->getMessage(), [], 444);
            }
            if ($result !== false) {
                $this->success('添加成功！！', '', 200);
            } else {
                $this->error(__('No rows were inserted'), [], 525);
            }

        }

    }

    /**
     * 编辑盘点单页面/详情/开始盘点/继续盘点页面
     *
     * @参数 int inventory_id  盘点单ID
     * @author wgj
     * @return mixed
     */
    public function inventory_edit()
    {
        $inventory_id = $this->request->request('inventory_id');
        empty($inventory_id) && $this->error(__('盘点单ID不能为空'), [], 530);
        //获取盘点单数据
        $_inventory = new \app\admin\model\warehouse\Inventory;
        $_inventory_item = new \app\admin\model\warehouse\InventoryItem;
        $_inventory_info = $_inventory->get($inventory_id);
        empty($_inventory_info) && $this->error(__('盘点单不存在'), [], 531);
        if ($_inventory_info['status'] > 0) {
            $this->error(__('此状态不能编辑'), [], 512);
        }
//        $inventory_item_info = $_inventory_item->field('id,sku,inventory_qty,error_qty,real_time_qty,available_stock,distribution_occupy_stock')->where(['inventory_id'=>$inventory_id])->select();
        $inventory_item_info = $_inventory_item
            ->alias('a')
            ->field('a.id,a.sku,a.inventory_qty,b.stock,a.error_qty,a.real_time_qty,a.available_stock,a.distribution_occupy_stock')
            ->where(['a.inventory_id'=>$inventory_id])
            ->join(['stock.fa_item'=> 'b'],'a.sku=b.sku','left')
            ->order('a.id', 'desc')
            ->select();
        $item_list = collection($inventory_item_info)->toArray();

        //盘点单所需数据
        $info =[
            'inventory_id'=>$_inventory_info['id'],
            'inventory_number'=>$_inventory_info['number'],
//            'status'=>$_inventory_info['status'],
            'item_list'=>$item_list,
        ];

        $this->success('', ['info' => $info],200);
    }

    /**
     * 开始盘点页面，提交
     *
     * @参数 int inventory_id  盘点单ID
     * @参数 int do_type  提交类型 1提交-盘点结束 2保存-盘点中
     * @参数 json item_sku  sku数据集合
     * @author wgj
     * @return mixed
     */
    public function inventory_submit()
    {
        $do_type = $this->request->request('do_type');
        $item_sku = $this->request->request("item_sku");
        $item_sku = array_filter(json_decode($item_sku,true));
        if (count(array_filter($item_sku)) < 1) {
            $this->error(__('sku集合不能为空！！'), [], 540);
        }

        $inventory_id = $this->request->request("inventory_id");
        empty($inventory_id) && $this->error(__('盘点单号不能为空'), [], 541);
        //获取盘点单数据
        $_inventory = new \app\admin\model\warehouse\Inventory;
        $_inventory_item = new \app\admin\model\warehouse\InventoryItem;
        $row = $_inventory->get($inventory_id);
        empty($row) && $this->error(__('盘点单不存在'), [], 543);
        if ($row['status'] > 0) {
            $this->error(__('此状态不能编辑'), [], 544);
        }

        $save_data = [];

        if ($do_type == 1) {
            $params['status'] = 2;
            $params['end_time'] = date('Y-m-d H:i:s', time());
            $save_data['is_add'] = 1;//更新为盘点
        } else {
            $params['status'] = 1;
        }

        //保存
        //不需要编辑盘点单
        //编辑盘点单明细item
        foreach (array_filter($item_sku) as $k => $v) {
            $save_data['inventory_qty'] = $v['inventory_qty'];//盘点数量
            $save_data['error_qty'] = $v['error_qty'];//误差数量
            $save_data['remark'] = $v['remark'];//备注
            $_inventory_item->allowField(true)->save($save_data, ['inventory_id' => $inventory_id,'sku' => $v['sku']]);
        }

        //提交盘点单状态为已完成，保存盘点单状态为盘点中
        $_inventory->allowField(true)->save($params, ['id' => $inventory_id]);
        $this->success('', ['info' => ''],200);
    }

    /**
     * 审核盘点单
     *
     * @参数 int inventory_id  盘点单ID
     * @参数 int do_type  审核类型 1通过-盘点结束-更改状态-创建入库单-盘盈加库存、盘亏扣减库存; 2拒绝-盘点结束-更改状态
     * @author wgj
     * @return mixed
     */
    public function inventory_examine()
    {
        $do_type = $this->request->request('do_type');

        $inventory_id = $this->request->request("inventory_id");
        empty($inventory_id) && $this->error(__('盘点单号不能为空'), [], 545);
        //获取盘点单数据
        $_inventory = new \app\admin\model\warehouse\Inventory;
        $_inventory_item = new \app\admin\model\warehouse\InventoryItem;
        $row = $_inventory->get($inventory_id);
        empty($row) && $this->error(__('盘点单不存在'), [], 546);
        if ($row['check_status'] != 1 || $row['status'] !=2) {
            $this->error(__('只有待审核、已完成状态才能操作'), [], 547);
        }
        $data['check_time'] = date('Y-m-d H:i:s', time());
        $data['check_person'] = $this->auth->nickname;

        if ($do_type == 2){
            $data['check_status'] = 4;
            $_inventory->allowField(true)->save($data, ['id' => $inventory_id]);
            $this->success('', ['info' => ''],200);
        }

        $data['check_status'] = 2;

        $item = new \app\admin\model\itemmanage\Item;
        $instock = new \app\admin\model\warehouse\Instock;
        $instockItem = new \app\admin\model\warehouse\InstockItem;
        $outstock = new \app\admin\model\warehouse\Outstock;
        $outstockItem = new \app\admin\model\warehouse\OutStockItem;

        $platform = new \app\admin\model\itemmanage\ItemPlatformSku();
        //回滚
        Db::startTrans();
        try {
            $res = $_inventory->allowField(true)->isUpdate(true, ['id'=>$inventory_id])->save($data);
            //审核通过 生成入库单 并同步库存
            if ($data['check_status'] == 2) {
                $infos = $_inventory_item->where(['inventory_id' => $inventory_id])
                    ->field('sku,error_qty,inventory_id')
                    ->group('sku')
                    ->select();
                $infos = collection($infos)->toArray();
                foreach ($infos as $k => $v) {
                    //如果误差为0则跳过
                    if ($v['error_qty'] == 0) {
                        continue;
                    }
                    //同步对应SKU库存
                    //更新商品表商品总库存
                    //总库存
                    $item_map['sku'] = $v['sku'];
                    $item_map['is_del'] = 1;
                    if ($v['sku']) {
                        $stock = $item->where($item_map)->inc('stock', $v['error_qty'])->inc('available_stock', $v['error_qty'])->update();

                        //盘点的时候盘盈入库 盘亏出库 的同时要对虚拟库存进行一定的操作
                        //查出映射表中此sku对应的所有平台sku 并根据库存数量进行排序（用于遍历数据的时候首先分配到那个站点）
                        $item_platform_sku = $platform->where('sku',$v['sku'])->order('stock asc')->field('platform_type,stock')->select();
                        $all_num = count($item_platform_sku);
                        // $whole_num = $platform->where('sku',$v['sku'])->sum('stock');
                        $whole_num = $platform
                            ->where('sku',$v['sku'])
                            ->field('stock')
                            ->select();
                        $num_num = 0;
                        foreach ($whole_num as $kk =>$vv){
                            $num_num += abs($vv['stock']);
                        }
                        //盘盈或者盘亏的数量 根据此数量对平台sku虚拟库存进行操作
                        $stock_num = $v['error_qty'];
                        //计算当前sku的总虚拟库存 如果总的为0 表示当前所有平台的此sku都为0 此时入库的话按照平均规则分配 例如五个站都有此品 那么比例就是20%
                        $stock_all_num = array_sum(array_column($item_platform_sku, 'stock'));
                        if ($stock_all_num == 0) {
                            $rate_rate = 1/$all_num;
                            foreach ($item_platform_sku as $key => $val) {
                                //最后一个站点 剩余数量分给最后一个站
                                if (($all_num - $key) == 1) {
                                    $platform->where(['sku' => $v['sku'], 'platform_type' => $val['platform_type']])->inc('stock', $stock_num)->update();
                                } else {
                                    $num = round($v['error_qty'] * $rate_rate);
                                    $stock_num -= $num;
                                    $platform->where(['sku' => $v['sku'], 'platform_type' => $val['platform_type']])->inc('stock', $num)->update();
                                }
                            }
                        }else{
                            foreach ($item_platform_sku as $key => $val) {
                                // dump($item_platform_sku);die;
                                //最后一个站点 剩余数量分给最后一个站
                                if (($all_num - $key) == 1) {
                                    $platform->where(['sku' => $v['sku'], 'platform_type' => $val['platform_type']])->inc('stock', $stock_num)->update();
                                } else {
                                    $num = round($v['error_qty'] * abs($val['stock'])/$num_num);
                                    $stock_num -= $num;
                                    $platform->where(['sku' => $v['sku'], 'platform_type' => $val['platform_type']])->inc('stock', $num)->update();
                                }
                            }
                        }

                    }

                    //修改库存结果为真
                    if ($stock === false) {
                        $this->error(__('同步库存失败,请检查SKU=>' . $v['sku']), [], 548);
                        break;
                    }

                    //插入日志表
                    (new StockLog())->setData([
                        'type'                      => 2,
                        'two_type'                  => 5,
                        'sku'                       => $v['sku'],
                        'public_id'                 => $v['inventory_id'],
                        'stock_change'              => $v['error_qty'],
                        'available_stock_change'    => $v['error_qty'],
                        'create_person'             => $this->auth->nickname,
                        'create_time'               => date('Y-m-d H:i:s'),
                        'remark'                    => '出库单减少总库存,减少可用库存'
                    ]);

                    if ($v['error_qty'] > 0) {
                        //生成入库单
                        $info[$k]['sku'] = $v['sku'];
                        $info[$k]['in_stock_num'] = abs($v['error_qty']);
                        $info[$k]['no_stock_num'] = abs($v['error_qty']);
                    } else {
                        $list[$k]['sku'] = $v['sku'];
                        $list[$k]['out_stock_num'] = abs($v['error_qty']);
                    }
                }
                //入库记录
                if ($info) {
                    $params['in_stock_number'] = 'IN' . date('YmdHis') . rand(100, 999) . rand(100, 999);
                    $params['create_person'] = $this->auth->nickname;
                    $params['createtime'] = date('Y-m-d H:i:s', time());
                    $params['type_id'] = 2;
                    $params['status'] = 2;
                    $params['remark'] = '盘盈入库';
                    $params['check_time'] = date('Y-m-d H:i:s', time());
                    $params['check_person'] = $this->auth->nickname;
                    $instorck_res = $instock->isUpdate(false)->allowField(true)->data($params, true)->save();

                    //添加入库信息
                    if ($instorck_res !== false) {
                        $instockItemList = array_values($info);
                        unset($info);
                        foreach ($instockItemList as &$v) {
                            $v['in_stock_id'] = $instock->id;
                        }
                        unset($v);
                        //批量添加
                        $instockItem->allowField(true)->saveAll($instockItemList);
                    } else {
                        $this->error(__('生成入库记录失败！！数据回滚'), [], 549);
                    }
                }

                //出库记录
                if ($list) {
                    $params = [];
                    $params['out_stock_number'] = 'OUT' . date('YmdHis') . rand(100, 999) . rand(100, 999);
                    $params['create_person'] = $this->auth->nickname;
                    $params['createtime'] = date('Y-m-d H:i:s', time());
                    $params['type_id'] = 1;
                    $params['status'] = 2;
                    $params['remark'] = '盘亏出库';
                    $params['check_time'] = date('Y-m-d H:i:s', time());
                    $params['check_person'] = $this->auth->nickname;
                    $outstock_res = $outstock->isUpdate(false)->allowField(true)->data($params, true)->save();


                    //添加入库信息
                    if ($outstock_res !== false) {
                        $outstockItemList = array_values($list);
                        foreach ($outstockItemList as $k => $v) {
                            $outstockItemList[$k]['out_stock_id'] = $outstock->id;
                        }
                        //批量添加
                        $outstockItem->allowField(true)->saveAll($outstockItemList);
                    } else {
                        $this->error(__('生成入库记录失败！！数据回滚'), [], 550);
                    }
                }
            }
            Db::commit();
        } catch (ValidateException $e) {
            Db::rollback();
            $this->error($e->getMessage(), [], 444);
        } catch (PDOException $e) {
            Db::rollback();
            $this->error($e->getMessage(), [], 444);
        } catch (Exception $e) {
            Db::rollback();
            $this->error($e->getMessage(), [], 444);
        }

        $this->success('', ['info' => ''],200);
    }

}
