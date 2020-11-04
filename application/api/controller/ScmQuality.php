<?php

namespace app\api\controller;

use think\Db;
use think\Exception;
use think\exception\PDOException;
use think\exception\ValidateException;
use app\admin\model\warehouse\Check;
use app\admin\model\warehouse\CheckItem;
use app\admin\model\warehouse\LogisticsInfo;
use app\admin\model\purchase\PurchaseOrder;
use app\admin\model\purchase\PurchaseOrderItem;
use app\admin\model\purchase\PurchaseBatchItem;
use app\admin\model\purchase\PurchaseBatch;
use app\admin\model\purchase\PurchaseAbnormal;
use app\admin\model\purchase\PurchaseAbnormalItem;
use app\admin\model\purchase\Supplier;
use app\admin\model\warehouse\ProductBarCodeItem;
use app\admin\model\purchase\SampleWorkorder;
use app\admin\model\purchase\SampleWorkorderItem;
use app\admin\model\NewProductMapping;
use app\admin\model\itemmanage\Item;
use app\admin\controller\itemmanage\ItemPlatformSku;

/**
 * 供应链质检接口类
 * @author lzh
 * @since 2020-10-20
 */
class ScmQuality extends Scm
{
    /**
     * 质检模型对象
     * @var object
     * @access protected
     */
    protected $_check = null;

    /**
     * 质检商品模型对象
     * @var object
     * @access protected
     */
    protected $_check_item = null;

    /**
     * 物流单模型对象
     * @var object
     * @access protected
     */
    protected $_logistics_info = null;

    /**
     * 采购单模型对象
     * @var object
     * @access protected
     */
    protected $_purchase_order = null;

    /**
     * 采购单商品模型对象
     * @var object
     * @access protected
     */
    protected $_purchase_order_item = null;

    /**
     * 采购批次主模型对象
     * @var object
     * @access protected
     */
    protected $_purchase_batch = null;

    /**
     * 采购批次子模型对象
     * @var object
     * @access protected
     */
    protected $_purchase_batch_item = null;

    /**
     * 收货异常主模型对象
     * @var object
     * @access protected
     */
    protected $_purchase_abnormal = null;

    /**
     * 收货异常子模型对象
     * @var object
     * @access protected
     */
    protected $_purchase_abnormal_item = null;

    /**
     * 供应商模型对象
     * @var object
     * @access protected
     */
    protected $_supplier = null;

    /**
     * 补货需求清单模型对象
     * @var object
     * @access protected
     */
    protected $_new_product_mapping = null;

    /**
     * 商品条形码模型对象
     * @var object
     * @access protected
     */
    protected $_product_bar_code_item = null;

    /**
     * 样品入库主模型对象
     * @var object
     * @access protected
     */
    protected $_sample_work_order = null;

    /**
     * 样品入库子模型对象
     * @var object
     * @access protected
     */
    protected $_sample_work_order_item = null;

    /**
     * 商品库存模型对象
     * @var object
     * @access protected
     */
    protected $_item = null;

    /**
     * sku映射关系模型对象
     * @var object
     * @access protected
     */
    protected $_item_platform_sku = null;

    protected function _initialize()
    {
        parent::_initialize();

        $this->_check = new Check();
        $this->_check_item = new CheckItem();
        $this->_logistics_info = new LogisticsInfo();
        $this->_purchase_order = new PurchaseOrder();
        $this->_purchase_order_item = new PurchaseOrderItem();
        $this->_purchase_batch = new PurchaseBatch();
        $this->_purchase_batch_item = new PurchaseBatchItem();
        $this->_purchase_abnormal = new PurchaseAbnormal();
        $this->_purchase_abnormal_item = new PurchaseAbnormalItem();
        $this->_supplier = new Supplier();
        $this->_new_product_mapping = new NewProductMapping();
        $this->_product_bar_code_item = new ProductBarCodeItem();
        $this->_sample_work_order = new SampleWorkorder();
        $this->_sample_work_order_item = new SampleWorkorderItem();
        $this->_item = new Item();
        $this->_item_platform_sku = new ItemPlatformSku();
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
    public function list()
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
        if(isset($status)){
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
        $list = $this->_check
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
            $list[$key]['edit_show'] = 0 == $value['status'] ? 1 : 0;
            $list[$key]['examine_show'] = 1 == $value['status'] ? 1 : 0;
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
    public function add()
    {
        $logistics_id = $this->request->request('logistics_id');
        empty($logistics_id) && $this->error(__('物流单ID不能为空'), [], 403);

        //获取物流单数据
        $logistics_data = $this->_logistics_info->where('id', $logistics_id)->field('id,purchase_id,batch_id')->find();
        empty($logistics_data) && $this->error(__('物流单不存在'), [], 403);

        //获取采购单数据
        $purchase_data = $this->_purchase_order->where('id', $logistics_data['purchase_id'])->field('purchase_number,supplier_id,replenish_id')->find();
        empty($purchase_data) && $this->error(__('采购单不存在'), [], 403);

        //获取采购单商品数据
        $order_item_list = $this->_purchase_order_item
            ->where(['purchase_id'=>$logistics_data['purchase_id']])
            ->field('sku,supplier_sku,purchase_num')
            ->select();
        $order_item_list = collection($order_item_list)->toArray();

        //获取供应商数据
        $supplier_data = $this->_supplier->where('id', $purchase_data['supplier_id'])->field('supplier_name')->find();
        empty($supplier_data) && $this->error(__('供应商不存在'), [], 403);

        //获取采购批次数据
        $batch = 0;
        if($logistics_data['batch_id']){
            $batch_data = $this->_purchase_batch->where('id', $logistics_data['batch_id'])->field('batch')->find();
            empty($batch_data) && $this->error(__('采购单批次不存在'), [], 403);

            $batch = $batch_data['batch'];
            $item_list = $this->_purchase_batch_item
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
    public function edit()
    {
        $check_id = $this->request->request('check_id');
        empty($check_id) && $this->error(__('质检单ID不能为空'), [], 403);

        //获取质检单数据
        $check_data = $this->_check->where('id', $check_id)->field('purchase_id,batch_id,check_order_number,supplier_id,is_error')->find();
        empty($check_data) && $this->error(__('质检单不存在'), [], 403);

        //获取采购单数据
        $purchase_data = $this->_purchase_order->where('id', $check_data['purchase_id'])->field('purchase_number')->find();
        empty($purchase_data) && $this->error(__('采购单不存在'), [], 403);

        //获取供应商数据
        $supplier_data = $this->_supplier->where('id', $check_data['supplier_id'])->field('supplier_name')->find();
        empty($supplier_data) && $this->error(__('供应商不存在'), [], 403);

        //获取采购批次数据
        $batch = 0;
        if($check_data['batch_id']){
            $batch_data = $this->_purchase_batch->where('id', $check_data['batch_id'])->field('batch')->find();
            empty($batch_data) && $this->error(__('采购单批次不存在'), [], 403);
            $batch = $batch_data['batch'];
        }

        //获取质检单商品数据
        $item_list = $this->_check_item
            ->where(['check_id'=>$check_id])
            ->field('sku,supplier_sku,arrivals_num,quantity_num,unqualified_num,sample_num,should_arrival_num')
            ->select();
        $item_list = collection($item_list)->toArray();

        //获取条形码数据
        $bar_code_list = $this->_product_bar_code_item
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
            'is_error'=>$check_data['is_error'],
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
    public function submit()
    {
        $item_data = $this->request->request('item_data');
        $item_data = array_filter(json_decode($item_data,true));
        empty($item_data) && $this->error(__('sku集合不能为空'), [], 403);

        $do_type = $this->request->request('do_type');
        $is_error = $this->request->request('is_error');
        $get_check_id = $this->request->request('check_id');

        if($get_check_id){
            $row = $this->_check->get($get_check_id);
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
            $result = $this->_check->allowField(true)->save($check_data);
            $check_id = $this->_check->id;
        }

        false === $result && $this->error(__('提交失败'), [], 404);

        Db::startTrans();
        try {
            //检测条形码是否已绑定
            $where['check_id'] = [['>',0], ['neq',$check_id]];
            foreach ($item_data as $key => $value) {
                //检测合格条形码
                $quantity_code = array_column($value['quantity_agg'],'code');
                count($value['quantity_agg']) != count(array_unique($quantity_code))
                &&
                $this->error(__('合格条形码有重复，请检查'), [], 405);

                $where['code'] = ['in',$quantity_code];
                $check_quantity = $this->_product_bar_code_item
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
                $check_unqualified = $this->_product_bar_code_item
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
                $check_sample = $this->_product_bar_code_item
                    ->where($where)
                    ->field('code')
                    ->find();
                if(!empty($check_sample)){
                    $this->error(__('留样条形码:'.$check_sample['code'].' 已绑定,请移除'), [], 405);
                    exit;
                }
            }

            //批量创建或更新质检单商品
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
                    $this->_check_item->allowField(true)->isUpdate(true, $where)->save($item_save);

                    //质检单移除条形码
                    if(!empty($value['remove_agg'])){
                        $code_clear = [
                            'sku' => '',
                            'purchase_id' => 0,
                            'logistics_id' => 0,
                            'check_id' => 0
                        ];
                        $this->_product_bar_code_item->allowField(true)->isUpdate(true, ['code' => ['in',$value['remove_agg']]])->save($code_clear);
                    }
                }else{//新增
                    $item_save['check_id'] = $check_id;
                    $item_save['sku'] = $value['sku'];
                    $item_save['supplier_sku'] = $value['supplier_sku'];
                    $item_save['purchase_id']  = $purchase_id;
                    $item_save['purchase_num'] = $value['purchase_num'];
                    $item_save['should_arrival_num'] = $value['should_arrival_num'];
                    $this->_check_item->allowField(true)->save($item_save);
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
                            $this->_product_bar_code_item->allowField(true)->isUpdate(true, ['code' => $v['code']])->save($code_item);
                        }
                    }
                }

                //绑定不合格条形码
                if(!empty($value['unqualified_agg'])){
                    foreach($value['unqualified_agg'] as $v){
                        if($v['is_new'] == 1){
                            $code_item['is_quantity'] = 2;
                            $this->_product_bar_code_item->allowField(true)->isUpdate(true, ['code' => $v['code']])->save($code_item);
                        }
                    }
                }

                //绑定留样条形码
                if(!empty($value['sample_agg'])){
                    foreach($value['sample_agg'] as $v){
                        if($v['is_new'] == 1){
                            $code_item['is_sample'] = 1;
                            $this->_product_bar_code_item->allowField(true)->isUpdate(true, ['code' => $v['code']])->save($code_item);
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
    public function cancel()
    {
        $check_id = $this->request->request('check_id');
        empty($check_id) && $this->error(__('质检单ID不能为空'), [], 403);

        //检测质检单状态
        $row = $this->_check->get($check_id);
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
            $this->_product_bar_code_item->allowField(true)->isUpdate(true, ['check_id' => $check_id])->save($code_clear);

            $res = $this->_check->allowField(true)->isUpdate(true, ['id'=>$check_id])->save(['status'=>4]);
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
    public function examine()
    {
        $check_id = $this->request->request('check_id');
        empty($check_id) && $this->error(__('质检单ID不能为空'), [], 403);

        $do_type = $this->request->request('do_type');
        empty($do_type) && $this->error(__('审核类型不能为空'), [], 403);
        !in_array($do_type,[2,3]) && $this->error(__('审核类型错误'), [], 403);

        //检测质检单状态
        $row = $this->_check->get($check_id);
        1 != $row['status'] && $this->error(__('只有待审核状态才能审核'), [], 405);

        $res = $this->_check->allowField(true)->isUpdate(true, ['id'=>$check_id])->save(['status'=>$do_type,'examine_time'=>date('Y-m-d H:i:s')]);
        false === $res && $this->error(__('审核失败'), [], 404);

        Db::startTrans();
        try {
            //审核通过关联操作
            if ($do_type == 2) {
                //标记物流单检索为已创建质检单
                $this->_logistics_info->allowField(true)->isUpdate(true, ['id'=>$row['logistics_id']])->save(['is_check_order'=>1]);

                //查询物流信息表对应采购单数据是否全部质检完毕
                if ($row['purchase_id']) {
                    //查询质检信息
                    $count = $this->_logistics_info->where(['purchase_id' => $row['purchase_id'], 'is_check_order' => 0])->count();

                    //修改采购单质检状态
                    $this->_purchase_order->allowField(true)->isUpdate(true, ['id'=>$row['purchase_id']])->save(['check_status'=>$count > 0 ? 1 : 2]);
                }

                //查询质检单明细表有样品的数据
                $list = $this->_check_item->where(['check_id' => $check_id, 'sample_num' => ['>', 0]])->select();
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
                    $this->_sample_work_order->allowField(true)->save($work_order_data);

                    //生成样品入库子表数据
                    $work_order_item_data = [];
                    foreach ($list as $value) {
                        $work_order_item_data[] = [
                            'parent_id'=>$this->_sample_work_order->id,
                            'sku'=>$value['sku'],
                            'stock'=>$value['sample_num'],
                        ];
                    }
                    $this->_sample_work_order_item->allowField(true)->saveAll($work_order_item_data);
                }

                //检测批次或采购单是否全部质检完成
                $where = ['purchase_id' => $row['purchase_id'], 'is_check_order' => 0];
                if($row['batch_id'] ){
                    $where['batch_id'] = $row['batch_id'];
                }
                $count = $this->_logistics_info->where($where)->count();

                //检测是否有异常单
                if ($count <= 0) {
                    $check_item_list = $this->_check
                        ->alias('a')
                        ->join(['fa_check_order_item' => 'b'], 'a.id=b.check_id')
                        ->where(['a.batch_id' => $row['batch_id'], 'a.purchase_id' => $row['purchase_id'], 'a.status' => 2])
                        ->where('a.is_error = :is_error or b.error_type > :error_type ',['is_error'=>1, 'error_type'=>0])
                        ->select()
                    ;
                    if ($check_item_list) {
                        //获取采购价格
                        $purchase_item_list = $this->_purchase_order_item
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
                        $abnormal_save = [
                            'error_number'=>'YC' . date('YmdHis') . rand(100, 999) . rand(100, 999),
                            'supplier_id'=>$row['supplier_id'],
                            'purchase_id'=>$row['purchase_id'],
                            'batch_id'=>$row['batch_id'],
                            'createtime'=>date('Y-m-d H:i:s'),
                            'is_error'=>$is_error
                        ];
                        $this->_purchase_abnormal->allowField(true)->save($abnormal_save);

                        //新增收货异常子表数据
                        array_walk($abnormal_item_save, function (&$value, $k, $p) {
                            $value = array_merge($value, $p);
                        },['abnormal_id' => $this->_purchase_abnormal->id]);

                        $this->_purchase_abnormal_item->allowField(true)->saveAll($abnormal_item_save);
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
                $this->_product_bar_code_item->allowField(true)->isUpdate(true, ['check_id' => $check_id])->save($code_clear);
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
        $purchase_list = $this->_purchase_order
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
        $list = $this->_logistics_info
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
     * @参数 string sign_number  签收编号
     * @author lzh
     * @return mixed
     */
    public function logistics_sign()
    {
        $logistics_id = $this->request->request('logistics_id');
        empty($logistics_id) && $this->error(__('物流单ID不能为空'), [], 403);
        $sign_number = $this->request->request('sign_number');
        empty($sign_number) && $this->error(__('签收编号不能为空'), [], 403);

        //检测质检单状态
        $row = $this->_logistics_info->get($logistics_id);
        (0 != $row['status'] || 1 != $row['type']) && $this->error(__('只有未签收状态才能操作'), [], 405);

        //签收关联操作
        Db::startTrans();
        try {
            $logistics_save = [
                'sign_person'=>$this->auth->nickname,
                'sign_time'=>date('Y-m-d H:i:s'),
                'status'=>1,
                'sign_number'=>$sign_number
            ];
            $res = $this->_logistics_info->allowField(true)->isUpdate(true, ['id'=>$logistics_id])->save($logistics_save);
            false === $res && $this->error(__('签收失败'), [], 404);

            //签收成功时更改采购单签收状态
            $count = $this->_logistics_info->where(['purchase_id' => $row['purchase_id'], 'status' => 0])->count();
            $purchase_save = [
                'purchase_status'=>$count > 0 ? 9 : 7,
                'receiving_time'=>date('Y-m-d H:i:s')
            ];
            $this->_purchase_order->allowField(true)->isUpdate(true, ['id'=>$row['purchase_id']])->save($purchase_save);

            //根据采购单获取补货单ID
            $replenish_id = $this->_purchase_order->where(['id' => $row['purchase_id']])->value('replenish_id');

            //获取补货单数据
            $mapping_list = $this->_new_product_mapping
                ->field('website_type,rate,sku')
                ->where(['replenish_id'=>$replenish_id])
                ->select()
            ;

            //批次物流签收
            if ($row['batch_id']) {
                //获取批次商品数据
                $batch_item_list = $this->_purchase_batch_item
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
                        $this->_item_platform_sku->where(['sku'=>$sku,'platform_type'=>$website_type])->setDec('plat_on_way_stock',$num);
                        //加站点待入库数量
                        $this->_item_platform_sku->where(['sku'=>$sku,'platform_type'=>$website_type])->setInc('wait_instock_num',$num);
                    }
                    //减全部在途
                    $this->_item->where(['sku' => $sku])->setDec('on_way_stock', $arrival_num);
                    //加全部待入库数量
                    $this->_item->where(['sku' => $sku])->setInc('wait_instock_num', $arrival_num);
                }
            } elseif ($row['purchase_id']) {//采购物流签收
                //获取采购单商品数据
                $purchase_item_list = $this->_purchase_order_item
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
                        $this->_item_platform_sku->where(['sku'=>$sku,'platform_type'=>$website_type])->setDec('plat_on_way_stock',$num);
                        //加站点待入库数量
                        $this->_item_platform_sku->where(['sku'=>$sku,'platform_type'=>$website_type])->setInc('wait_instock_num',$num);
                    }
                    //减全部在途
                    $this->_item->where(['sku' => $sku])->setDec('on_way_stock', $purchase_num);
                    //加全部待入库数量
                    $this->_item->where(['sku' => $sku])->setInc('wait_instock_num', $purchase_num);
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

}
