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

        $res = $_check->allowField(true)->isUpdate(true, ['id'=>$check_id])->save(['status'=>$do_type]);
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
                    //生成入库主表数据
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

                    //生成入库子表数据
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
            ->field('a.id,a.out_stock_number,a.createtime,a.status,a.type_id')
            ->join(['fa_out_stock_item' => 'b'], 'a.id=b.check_id','left')
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

        //解除条形码绑定关系
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
     * @参数 string query  查询内容
     * @参数 int status  状态：0新建 1待审核 2 已审核 3已拒绝 4已取消
     * @参数 string start_time  开始时间
     * @参数 string end_time  结束时间
     * @参数 int page  页码
     * @参数 int page_size  每页显示数量
     * @author lzh
     * @return mixed
     */
    public function distribution_sorting()
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
            ->field('a.id,a.out_stock_number,a.createtime,a.status,a.type_id')
            ->join(['fa_out_stock_item' => 'b'], 'a.id=b.check_id','left')
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
     * 记录配货流程操作日志
     *
     * @param int $item_process_id  子订单表ID
     * @param string $remark  备注
     * @author lzh
     * @return bool
     */
    public function distribution_log($item_process_id,$remark)
    {
        $_distribution_log = new \app\admin\model\DistributionLog();
        $log_data = [
            'item_process_id' => $item_process_id,
            'remark' => $remark,
            'create_time' => date('Y-m-d H:i:s'),
            'create_person' => $this->auth->nickname
        ];
        $res = $_distribution_log->allowField(true)->save($log_data);
        return $res;
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
     * @param int $save_status  保存状态
     * @author lzh
     * @return mixed
     */
    public function distribution_save($item_order_number,$check_status,$save_status)
    {
        empty($item_order_number) && $this->error(__('子订单号不能为空'), [], 403);

        //获取子订单数据
        $_new_order_item_process = new \app\admin\model\order\order\NewOrderItemProcess();
        $item_process_info = $_new_order_item_process
            ->where('item_order_number', $item_order_number)
            ->value('id,distribution_status')
            ->find()
        ;
        empty($item_process_info) && $this->error(__('子订单不存在'), [], 403);

        //检测状态
        $status_arr = [
            3=>'待配镜片',
            4=>'待加工',
            6=>'待成品质检'
        ];
        $msg_arr = [
            3=>'配镜片',
            4=>'加工',
            6=>'成品质检'
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

        $res = $_new_order_item_process
            ->allowField(true)
            ->isUpdate(true, ['item_order_number'=>$item_order_number])
            ->save(['distribution_status'=>$save_status])
        ;
        if($res){
            //记录操作日志
            $remark_arr = [
                4=>'配镜片完成',
                6=>'加工完成',
                7=>'成品质检完成'
            ];
            $this->distribution_log($item_process_info['id'],$remark_arr[$save_status]);

            $this->success($msg_arr[$check_status].'成功', [],200);
        }else{
            $this->error(__($msg_arr[$check_status].'失败'), [], 404);
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
        $this->distribution_save($item_order_number,3,4);
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
        $this->distribution_save($item_order_number,4,6);
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
            $this->distribution_save($item_order_number,6,7);
        }else{
            $reason = $this->request->request('reason');
            !in_array($do_type,[1,2,3,4]) && $this->error(__('拒绝原因错误'), [], 403);

            //获取子订单数据
            $_new_order_item_process = new \app\admin\model\order\order\NewOrderItemProcess();
            $item_process_id = $_new_order_item_process
                ->where('item_order_number', $item_order_number)
                ->value('id')
            ;

            //状态
            $status_arr = [
                1=>['status'=>4,'name'=>'加工调整'],
                2=>['status'=>2,'name'=>'镜架报损'],
                3=>['status'=>3,'name'=>'镜片报损'],
                4=>['status'=>5,'name'=>'logo调整']
            ];

            //状态回滚
            $_new_order_item_process
                ->allowField(true)
                ->isUpdate(true, ['id'=>$item_process_id])
                ->save(['distribution_status'=>$status_arr[$reason]['status']])
            ;

            //记录日志
            $this->distribution_log($item_process_id,$status_arr[$reason]['name']);
        }
    }

}
