<?php

namespace app\api\controller;

use think\Db;
use think\Exception;
use think\exception\PDOException;
use think\exception\ValidateException;
use app\admin\model\warehouse\Outstock;
use app\admin\model\warehouse\OutStockItem;
use app\admin\model\warehouse\OutstockType;
use app\admin\model\warehouse\Check;
use app\admin\model\warehouse\Instock;
use app\admin\model\warehouse\InstockItem;
use app\admin\model\warehouse\InstockType;
use app\admin\model\purchase\PurchaseOrderItem;
use app\admin\model\warehouse\ProductBarCodeItem;
use app\admin\model\NewProductMapping;
use app\admin\model\itemmanage\Item;
use app\admin\model\itemmanage\ItemPlatformSku;
use app\admin\model\StockLog;
use app\admin\model\platformmanage\MagentoPlatform;
use app\admin\model\itemmanage\GoodsStockAllocated;
use app\admin\model\saleaftermanage\OrderReturn;
use app\admin\model\warehouse\Inventory;
use app\admin\model\warehouse\InventoryItem;
use app\admin\model\warehouse\StockSku;

/**
 * 供应链出入库接口类
 * @author lzh
 * @since 2020-10-20
 */
class ScmWarehouse extends Scm
{
    /**
     * 出库主模型对象
     * @var object
     * @access protected
     */
    protected $_out_stock = null;

    /**
     * 出库子模型对象
     * @var object
     * @access protected
     */
    protected $_out_stock_item = null;

    /**
     * 出库类型模型对象
     * @var object
     * @access protected
     */
    protected $_out_stock_type = null;

    /**
     * 质检模型对象
     * @var object
     * @access protected
     */
    protected $_check = null;

    /**
     * 入库主模型对象
     * @var object
     * @access protected
     */
    protected $_in_stock = null;

    /**
     * 入库子模型对象
     * @var object
     * @access protected
     */
    protected $_in_stock_item = null;

    /**
     * 入库类型模型对象
     * @var object
     * @access protected
     */
    protected $_in_stock_type = null;

    /**
     * 采购单商品模型对象
     * @var object
     * @access protected
     */
    protected $_purchase_order_item = null;

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

    /**
     * 平台模型对象
     * @var object
     * @access protected
     */
    protected $_magento_platform = null;

    /**
     * 库存日志模型对象
     * @var object
     * @access protected
     */
    protected $_stock_log = null;

    /**
     * 入库待分配模型对象
     * @var object
     * @access protected
     */
    protected $_allocated = null;

    /**
     * 退货模型对象
     * @var object
     * @access protected
     */
    protected $_order_return = null;

    /**
     * 盘点单主模型对象
     * @var object
     * @access protected
     */
    protected $_inventory = null;

    /**
     * 盘点单子模型对象
     * @var object
     * @access protected
     */
    protected $_inventory_item = null;

    /**
     * SKU库位绑定模型对象
     * @var object
     * @access protected
     */
    protected $_store_sku = null;

    protected function _initialize()
    {
        parent::_initialize();

        $this->_out_stock = new Outstock();
        $this->_out_stock_item = new OutStockItem();
        $this->_out_stock_type = new OutstockType();
        $this->_check = new Check();
        $this->_in_stock = new Instock();
        $this->_in_stock_item = new InstockItem();
        $this->_in_stock_type = new InstockType();
        $this->_new_product_mapping = new NewProductMapping();
        $this->_purchase_order_item = new PurchaseOrderItem();
        $this->_product_bar_code_item = new ProductBarCodeItem();
        $this->_item = new Item();
        $this->_item_platform_sku = new ItemPlatformSku();
        $this->_stock_log = new StockLog();
        $this->_magento_platform = new MagentoPlatform();
        $this->_allocated = new GoodsStockAllocated();
        $this->_order_return = new OrderReturn();
        $this->_inventory = new Inventory();
        $this->_inventory_item = new InventoryItem();
        $this->_store_sku = new StockSku();
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
        if(isset($status)){
            $where['a.status'] = $status;
        }
        if($start_time && $end_time){
            $where['a.createtime'] = ['between', [$start_time, $end_time]];
        }

        $offset = ($page - 1) * $page_size;
        $limit = $page_size;

        //获取出库单列表数据
        $list = $this->_out_stock
            ->alias('a')
            ->where($where)
            ->field('a.id,a.out_stock_number,a.createtime,a.status,a.type_id,a.remark')
            ->join(['fa_out_stock_item' => 'b'], 'a.id=b.out_stock_id','left')
            ->order('a.createtime', 'desc')
            ->limit($offset, $limit)
            ->select();
        $list = collection($list)->toArray();

        //获取出库分类数据
        $type_list = $this->_out_stock_type
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
            $list[$key]['examine_show'] = 1 == $value['status'] ? 1 : 0;
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
        if($out_stock_id){
            $info = $this->_out_stock
                ->field('out_stock_number,type_id,platform_id,status')
                ->where('id', $out_stock_id)
                ->find()
            ;
            0 != $info['status'] && $this->error(__('只有新建状态才能编辑'), [], 405);
            unset($info['status']);

            //获取出库单商品数据
            $item_data = $this->_out_stock_item
                ->field('sku,out_stock_num')
                ->where('out_stock_id', $out_stock_id)
                ->select()
            ;

            //获取各站点虚拟仓库存
            $stock_list = $this->_item_platform_sku
                ->where('platform_type', $info['platform_id'])
                ->column('stock','sku')
            ;

            //获取条形码数据
            $bar_code_list = $this->_product_bar_code_item
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

        //获取出库分类数据
        $type_list = $this->_out_stock_type
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
        $item_data = json_decode(htmlspecialchars_decode($item_data),true);
        empty($item_data) && $this->error(__('sku集合不能为空'), [], 403);
        $item_data = array_filter($item_data);

        $do_type = $this->request->request('do_type');
        $get_out_stock_id = $this->request->request('out_stock_id');

        if($get_out_stock_id){
            $row = $this->_out_stock->get($get_out_stock_id);
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
            $result = $this->_out_stock->allowField(true)->save($out_stock_data);
            $out_stock_id = $this->_out_stock->id;
        }

        false === $result && $this->error(__('提交失败'), [], 404);

        Db::startTrans();
        try {
            count($item_data) != count(array_unique(array_column($item_data,'sku'))) && $this->error(__('sku重复，请检查'), [], 405);

            //获取各站点虚拟仓库存
            $stock_list = $this->_item_platform_sku
                ->where('platform_type', $platform_id)
                ->column('stock','sku')
            ;

            //校验各站点虚拟仓库存
            foreach ($item_data as $key => $value) {
                empty($stock_list[$value['sku']]) && $this->error(__('sku: '.$value['sku'].' 没有同步至对应平台'), [], 405);
                $value['out_stock_num'] > $stock_list[$value['sku']] && $this->error(__('sku: '.$value['sku'].' 出库数量不能大于虚拟仓库存'), [], 405);
            }

            //检测条形码是否已绑定
            $where['out_stock_id'] = [['>',0], ['neq',$out_stock_id]];
            foreach ($item_data as $key => $value) {
                $sku_code = array_column($value['sku_agg'],'code');
                count($value['sku_agg']) != count(array_unique($sku_code))
                &&
                $this->error(__('条形码有重复，请检查'), [], 405);

                $where['code'] = ['in',$sku_code];
                $check_quantity = $this->_product_bar_code_item
                    ->where($where)
                    ->field('code')
                    ->find();
                if(!empty($check_quantity['code'])){
                    $this->error(__('条形码:'.$check_quantity['code'].' 已绑定,请移除'), [], 405);
                    exit;
                }
            }

            //批量创建或更新出库单商品
            foreach ($item_data as $key => $value) {
                $item_save = [
                    'out_stock_num'=>$value['out_stock_num']
                ];
                if($get_out_stock_id){//更新
                    $where = ['sku' => $value['sku'],'out_stock_id' => $out_stock_id];
                    $this->_out_stock_item->allowField(true)->isUpdate(true, $where)->save($item_save);

                    //出库单移除条形码
                    if(!empty($value['remove_agg'])){
                        $code_clear = [
                            'out_stock_id' => 0
                        ];
                        $this->_product_bar_code_item->allowField(true)->isUpdate(true, ['code' => ['in',$value['remove_agg']]])->save($code_clear);
                    }
                }else{//新增
                    $item_save['out_stock_id'] = $out_stock_id;
                    $item_save['sku'] = $value['sku'];
                    $this->_out_stock_item->allowField(true)->save($item_save);
                }

                //绑定条形码
                foreach($value['sku_agg'] as $v){
                    $this->_product_bar_code_item->allowField(true)->isUpdate(true, ['code' => $v['code']])->save(['out_stock_id'=>$out_stock_id]);
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
        $row = $this->_out_stock->get($out_stock_id);
        1 != $row['status'] && $this->error(__('只有待审核状态才能审核'), [], 405);

        Db::startTrans();
        try {
            //审核通过扣减库存
            if ($do_type == 2) {
                //获取出库单商品数据
                $item_data = $this->_out_stock_item
                    ->field('sku,out_stock_num')
                    ->where('out_stock_id', $out_stock_id)
                    ->select()
                ;

                //获取各站点虚拟仓库存
                $stock_list = $this->_item_platform_sku
                    ->where('platform_type', $row['platform_id'])
                    ->column('stock','sku')
                ;

                //校验各站点虚拟仓库存
                foreach ($item_data as $value) {
                    $value['out_stock_num'] > $stock_list[$value['sku']] && $this->error(__('sku: '.$value['sku'].' 出库数量不能大于虚拟仓库存'), [], 405);
                }

                $stock_data = [];
                //出库扣减库存
                foreach ($item_data as $value) {
                    //扣除商品表总库存
                    $sku = $value['sku'];
                    $this->_item->where(['sku'=>$sku])->dec('stock', $value['out_stock_num'])->dec('available_stock', $value['out_stock_num'])->update();

                    //扣减对应平台sku库存
                    $this->_item_platform_sku->where(['sku' => $sku, 'platform_type' => $row['platform_id']])->dec('stock', $value['out_stock_num'])->update();

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
                $this->_stock_log->allowField(true)->saveAll($stock_data);
            }else{//审核拒绝解除条形码绑定关系
                $code_clear = [
                    'out_stock_id' => 0
                ];
                $this->_product_bar_code_item->allowField(true)->isUpdate(true, ['out_stock_id' => $out_stock_id])->save($code_clear);
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

        $res = $this->_out_stock->allowField(true)->isUpdate(true, ['id'=>$out_stock_id])->save(['status'=>$do_type]);
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
        $row = $this->_out_stock->get($out_stock_id);
        0 != $row['status'] && $this->error(__('只有新建状态才能取消'), [], 405);

        //解除条形码绑定关系
        $code_clear = [
            'out_stock_id' => 0
        ];
        $this->_product_bar_code_item->allowField(true)->isUpdate(true, ['out_stock_id' => $out_stock_id])->save($code_clear);

        $res = $this->_out_stock->allowField(true)->isUpdate(true, ['id'=>$out_stock_id])->save(['status'=>4]);
        $res ? $this->success('取消成功', [],200) : $this->error(__('取消失败'), [], 404);
    }

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
        if(isset($status)){
            $where['a.status'] = $status;
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
            ->field('a.id,a.check_order_number,c.logistics_number,a.createtime,a.examine_time')
            ->join(['fa_check_order_item' => 'b'], 'a.id=b.check_id','left')
            ->join(['fa_logistics_info' => 'c'], 'a.logistics_id=c.id','left')
            ->group('a.id')
            ->order('a.createtime', 'desc')
            ->limit($offset, $limit)
            ->select();
        $list = collection($list)->toArray();

        $this->success('', ['list' => $list],200);
    }

    /**
     * 入库单列表--ok
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
        if(isset($status)){
            $where['a.status'] = $status;
        }
        if($start_time && $end_time){
            $where['a.createtime'] = ['between', [$start_time, $end_time]];
        }

        $offset = ($page - 1) * $page_size;
        $limit = $page_size;

        //获取入库单列表数据
        $list = $this->_in_stock
            ->alias('a')
            ->where($where)
            ->field('a.id,a.in_stock_number,b.check_order_number,a.createtime,a.status')
            ->join(['fa_check_order' => 'b'], 'a.check_id=b.id')
            ->group('a.id')
            ->order('a.createtime', 'desc')
            ->limit($offset, $limit)
            ->select();
        $list = collection($list)->toArray();

        $status_list = [ 0=>'新建',1=>'待审核',2=>'已审核',3=>'已拒绝',4=>'已取消' ];
        foreach($list as $key=>$value){
            $list[$key]['status'] = $status_list[$value['status']];
            //按钮
            $list[$key]['show_edit'] = 0 == $value['status'] ? 1 : 0;//编辑按钮
            $list[$key]['cancel_show'] = 0 == $value['status'] ? 1 : 0;//取消按钮
            $list[$key]['show_examine'] = 1 == $value['status'] ? 1 : 0;//审核按钮
            $list[$key]['show_detail'] = in_array($value['status'], [3,4]) ? 1 : 0;//详情按钮
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
        $row = $this->_in_stock->get($in_stock_id);
        0 != $row['status'] && $this->error(__('只有新建状态才能取消'), [], 504);

        $res = $this->_in_stock->allowField(true)->isUpdate(true, ['id'=>$in_stock_id])->save(['status'=>4]);
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

        if ($in_stock_id) {
            //编辑入库单
            $row = $this->_in_stock->get($in_stock_id);
            empty($row) && $this->error(__('入库单不存在'), [], 512);

            //编辑入库单
            $_in_stock_data = [
                'type_id'=> $type_id,
                'status'=>1 == $do_type ?? 0
            ];
            $result = $this->_in_stock->allowField(true)->save($_in_stock_data, ['id' => $in_stock_id]);

            //修改入库信息
            if ($in_stock_number !== $row['in_stock_number']) {
                //更改质检单为已创建入库单
                $this->_check->allowField(true)->save(['is_stock' => 1], ['id' => $check_id]);

                $save_data = [];
                foreach (array_filter($item_sku) as $k => $v) {
                    $save_data['sku'] = $v['sku'];
                    $save_data['in_stock_num'] = $v['in_stock_num'];//入库数量
//                            $save_data['sample_num'] = $v['sample_num'];//留样数量
//                            $save_data['no_stock_num'] = $v['no_stock_num'];//未入库数量
//                            $save_data['purchase_id'] = $v['purchase_id'];//采购单ID
                    $save_data['in_stock_id'] = $in_stock_id;
                    $this->_in_stock_item->allowField(true)->save($save_data, ['id' => $in_stock_id]);
                }
            }
            $this->success('保存成功', ['info' => ''],200);

        } else {

            //新建入库单
            $result = false;
            Db::startTrans();
            try {

                //存在平台id 代表把当前入库单的sku分给这个平台 首先做判断 判断入库单的sku是否都有此平台对应的映射关系
                if ($platform_id) {
                    $params['platform_id'] = $platform_id;
                    foreach (array_filter($item_sku) as $k => $v) {
                        $sku_platform = $this->_item_platform_sku->where(['sku' => $v['sku'], 'platform_type' => $params['platform_id']])->find();
                        if (!$sku_platform) {
                            $this->error('此sku：' . $v['sku'] . '没有同步至此平台，请先同步后重试');
                        }
                    }
                    $params['create_person'] = $this->auth->nickname;
                    $params['createtime'] = date('Y-m-d H:i:s', time());
                    //新增入库单
                    $result = $this->_in_stock->allowField(true)->save($params);

                    //添加入库信息
                    if ($result !== false) {
                        $data = [];
                        foreach (array_filter($item_sku) as $k => $v) {
                            $data[$k]['sku'] = $v['sku'];
                            $data[$k]['in_stock_num'] = $v['in_stock_num'];//入库数量
//                            $data[$k]['sample_num'] = $v['sample_num'];//留样数量
//                            $data[$k]['no_stock_num'] = $v['no_stock_num'];//未入库数量
//                            $data[$k]['purchase_id'] = $v['purchase_id'];//采购单ID
                            $data[$k]['in_stock_id'] = $this->_in_stock->id;
                        }
                        //批量添加
                        $this->_in_stock_item->allowField(true)->saveAll($data);
                    }
                } else {
                    //质检单页面去入库单
                    $params['create_person'] = $this->auth->nickname;
                    $params['createtime'] = date('Y-m-d H:i:s', time());
                    $result = $this->_in_stock->allowField(true)->save($params);

                    //添加入库信息
                    if ($result !== false) {
                        //更改质检单为已创建入库单
                        $this->_check->allowField(true)->save(['is_stock' => 1], ['id' => $params['check_id']]);

                        $data = [];
                        foreach (array_filter($item_sku) as $k => $v) {
                            $data[$k]['sku'] = $v['sku'];
                            $data[$k]['in_stock_num'] = $v['in_stock_num'];//入库数量
//                            $data[$k]['sample_num'] = $v['sample_num'];//留样数量
//                            $data[$k]['no_stock_num'] = $v['no_stock_num'];//未入库数量
//                            $data[$k]['purchase_id'] = $v['purchase_id'];//采购单ID
                            $data[$k]['in_stock_id'] = $this->_in_stock->id;
                        }
                        //批量添加
                        $this->_in_stock_item->allowField(true)->saveAll($data);
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
                $this->success('提交成功！！', '', 200);
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
            $check_info = $this->_check->get($check_id);
            //入库单所需数据
            $info['check_id'] = $check_id;
            $info['order_number'] = $check_info['order_number'];

        } else {
            //入库单直接添加，查询站点数据
            $platform_list = $this->_magento_platform->field('id, name')->where('is_del=>1, status=>1')->select();
            $info['platform_list'] = $platform_list;

        }

        //查询入库分类
        $in_stock_type = $this->_in_stock_type->field('id, name')->where('is_del', 1)->select();

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
        $_in_stock_info = $this->_in_stock->get($in_stock_id);
        empty($_in_stock_info) && $this->error(__('入库单不存在'), [], 515);
        $check_order_number = $this->_check->get($_in_stock_info['check_id']);

        //获取入库单列表数据
        $item_list = $this->_in_stock
            ->alias('a')
            ->where(['a.id'=>$in_stock_id])
            ->field('b.sku,c.quantity_num,b.in_stock_num')
            ->join(['fa_in_stock_item' => 'b'], 'a.id=b.in_stock_id')
            ->join(['fa_check_order_item' => 'c'], 'a.check_id=c.check_id')
            ->join(['fa_check_order' => 'd'], 'a.check_id=d.id')
            ->order('a.createtime', 'desc')
            ->select();
        $item_list = collection($item_list)->toArray();

        //入库单所需数据
        $info =[
            'in_stock_id'=>$_in_stock_info['id'],
            'in_stock_number'=>$_in_stock_info['in_stock_number'],
            'check_order_number'=>$check_order_number['check_order_number'],
            'item_list'=>$item_list,
        ];

        //查询入库分类
        $in_stock_type = $this->_in_stock_type->field('id, name')->where('is_del', 1)->select();

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
        $row = $this->_in_stock->get($in_stock_id);
        1 != $row['status'] && $this->error(__('只有待审核状态才能操作'), [], 518);

        $data['status'] = $this->request->request('do_type');//审核状态，2通过，3拒绝
        if ($data['status'] == 2) {
            $data['check_time'] = date('Y-m-d H:i:s', time());
        }

        //查询入库明细数据
        $list = $this->_in_stock
            ->alias('a')
            ->join(['fa_in_stock_item' => 'b'], 'a.id=b.in_stock_id')
            ->where(['a.id' => $in_stock_id])
            ->select();
        $list = collection($list)->toArray();
        //查询存在产品库的sku
        $skus = array_column($list, 'sku');
        $skus = $this->_item->where(['sku' => ['in', $skus]])->column('sku');
        foreach ($list as $v) {
            if (!in_array($v['sku'], $skus)) {
                $this->error('此sku:' . $v['sku'] . '不存在！！');
            }
        }
        $this->_in_stock->startTrans();
        $this->_item->startTrans();
        $this->_purchase_order_item->startTrans();

        try {
            $data['create_person'] = $this->auth->nickname;
            $res = $this->_in_stock->allowField(true)->isUpdate(true, ['id'=>$in_stock_id])->save($data);

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
                            $rate_arr = $this->_new_product_mapping
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
                                    $this->_item_platform_sku->where(['sku' => $v['sku'], 'platform_type' => $val['website_type']])->setInc('stock', $stock_num);
                                    //入库的时候减少待入库数量
                                    $this->_item_platform_sku->where(['sku' => $v['sku'], 'platform_type' => $val['website_type']])->setDec('wait_instock_num', $stock_num);

                                } else {
                                    $num = round($v['in_stock_num'] * $val['rate']);
                                    $stock_num -= $num;
                                    //增加站点虚拟仓库存
                                    $this->_item_platform_sku->where(['sku' => $v['sku'], 'platform_type' => $val['website_type']])->setInc('stock', $num);
                                    //入库的时候减少待入库数量
                                    $this->_item_platform_sku->where(['sku' => $v['sku'], 'platform_type' => $val['website_type']])->setDec('wait_instock_num', $num);
                                }
                            }
                        } else {
                            //记录没有采购比例直接入库的sku
                            $this->_allocated->allowField(true)->save(['sku' => $v['sku'], 'change_num' => $v['in_stock_num'], 'create_time' => date('Y-m-d H:i:s')]);

                            $item_platform_sku = $this->_item_platform_sku->where(['sku' => $v['sku'], 'platform_type' => 4])->field('platform_type,stock')->find();
                            //sku没有同步meeloog站 无法添加虚拟库存 必须先同步
                            if (empty($item_platform_sku)) {
                                $this->error('sku：' . $v['sku'] . '没有同步meeloog站，请先同步');
                            }
                            $this->_item_platform_sku->where(['sku' => $v['sku'], 'platform_type' => $item_platform_sku['platform_type']])->setInc('stock', $v['in_stock_num']);
                        }
                    } //不是采购过来的 如果有站点id 说明是指定增加此平台sku
                    elseif ($v['platform_id']) {
                        $this->_item_platform_sku->where(['sku' => $v['sku'], 'platform_type' => $v['platform_id']])->setInc('stock', $v['in_stock_num']);
                    } //没有采购单也没有站点id 说明是盘点过来的
                    else {
                        //根据当前sku 和当前 各站的虚拟库存进行分配
                        $item_platform_sku = $this->_item_platform_sku->where('sku', $v['sku'])->order('stock asc')->field('platform_type,stock')->select();
                        $all_num = count($item_platform_sku);

                        $stock_num = $v['in_stock_num'];
                        //计算当前sku的总虚拟库存 如果总的为0 表示当前所有平台的此sku都为0 此时入库的话按照平均规则分配 例如五个站都有此品 那么比例就是20%
                        $stock_all_num = array_sum(array_column($item_platform_sku, 'stock'));
                        if ($stock_all_num == 0) {
                            $rate_rate = 1/$all_num;
                            foreach ($item_platform_sku as $key => $val) {
                                //最后一个站点 剩余数量分给最后一个站
                                if (($all_num - $key) == 1) {
                                    $this->_item_platform_sku->where(['sku' => $v['sku'], 'platform_type' => $val['platform_type']])->setInc('stock', $stock_num);
                                } else {
                                    $num = round($v['in_stock_num'] * $rate_rate);
                                    $stock_num -= $num;
                                    $this->_item_platform_sku->where(['sku' => $v['sku'], 'platform_type' => $val['platform_type']])->setInc('stock', $num);
                                }
                            }
                        } else {
                            //某個平台這個sku存在庫存 就按照當前各站的虛擬庫存進行分配
                            $whole_num = $this->_item_platform_sku->where('sku', $v['sku'])->sum('stock');
                            $stock_num = $v['in_stock_num'];
                            foreach ($item_platform_sku as $key => $val) {
                                //最后一个站点 剩余数量分给最后一个站
                                if (($all_num - $key) == 1) {
                                    $this->_item_platform_sku->where(['sku' => $v['sku'], 'platform_type' => $val['platform_type']])->setInc('stock', $stock_num);
                                } else {
                                    $num = round($v['in_stock_num'] * $val['stock'] / $whole_num);
                                    $stock_num -= $num;
                                    $this->_item_platform_sku->where(['sku' => $v['sku'], 'platform_type' => $val['platform_type']])->setInc('stock', $num);
                                }
                            }
                        }
                    }

                    //更新商品表商品总库存
                    //总库存
                    $item_map['sku'] = $v['sku'];
                    $item_map['is_del'] = 1;
                    if ($v['sku']) {
                        //增加商品表里的商品库存、可用库存、留样库存
                        $stock_res = $this->_item->where($item_map)->inc('stock', $v['in_stock_num'])->inc('available_stock', $v['in_stock_num'])->inc('sample_num', $v['sample_num'])->update();
                        //减少待入库数量
                        $stock_res1 = $this->_item->where($item_map)->dec('wait_instock_num', $v['in_stock_num'])->update();
                    }

                    if ($stock_res === false) {
                        $error_num[] = $k;
                    }

                    //根据质检id 查询采购单id
                    $check_res = $this->_check->where('id', $v['check_id'])->find();
                    //更新采购商品表 入库数量 如果为真则为采购入库
                    if ($check_res['purchase_id']) {
                        $purchase_map['sku'] = $v['sku'];
                        $purchase_map['purchase_id'] = $check_res['purchase_id'];
                        $this->_purchase_order_item->where($purchase_map)->setInc('instock_num', $v['in_stock_num']);

                        //更新采购单状态 已入库 或 部分入库
                        //查询采购单商品总到货数量 以及采购数量
                        //查询质检信息
                        $check_map['Check.purchase_id'] = $check_res['purchase_id'];
                        $check_map['type'] = 1;
                        //总到货数量
                        $all_arrivals_num = $this->_check->hasWhere('checkItem')->where($check_map)->group('Check.purchase_id')->sum('arrivals_num');

                        $all_purchase_num = $this->_purchase_order_item->where('purchase_id', $check_res['purchase_id'])->sum('purchase_num');
                        //总到货数量 小于 采购单采购数量 则为部分入库
                        if ($all_arrivals_num < $all_purchase_num) {
                            $stock_status = 1;
                        } else {
                            $stock_status = 2;
                        }
                        //修改采购单入库状态
                        $purchase_data['stock_status'] = $stock_status;
                        $this->_purchase_order_item->where(['id' => $check_res['purchase_id']])->update($purchase_data);
                    }
                    //如果为退货单 修改退货单状态为入库
                    if ($check_res['order_return_id']) {
                        $this->_order_return->where(['id' => $check_res['order_return_id']])->update(['in_stock_status' => 1]);
                    }


                    //插入日志表
                    $this->_stock_log->setData([
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

            $this->_in_stock->commit();
            $this->_item->commit();
            $this->_purchase_order_item->commit();
        } catch (ValidateException $e) {
            $this->_in_stock->rollback();
            $this->_item->rollback();
            $this->_purchase_order_item->rollback();
            $this->error($e->getMessage(), [], 444);
        } catch (PDOException $e) {
            $this->_in_stock->rollback();
            $this->_item->rollback();
            $this->_purchase_order_item->rollback();
            $this->error($e->getMessage(), [], 444);
        } catch (Exception $e) {
            $this->_in_stock->rollback();
            $this->_item->rollback();
            $this->_purchase_order_item->rollback();
            $this->error($e->getMessage(), [], 444);
        }

        if ($res !== false) {
            $this->success('审核成功', [],200);
        } else {
            $this->error(__('审核失败'), [], 519);
        }

    }

    /**
     * 盘点单列表--ok
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
        if(isset($status)){
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
        $list = $this->_inventory
            ->alias('a')
            ->where($where)
            ->field('a.id,a.number,a.createtime,a.status,a.check_status')
            ->join(['fa_inventory_item' => 'b'], 'a.id=b.inventory_id','left')
            ->group('a.id')
            ->order('a.createtime', 'desc')
            ->limit($offset, $limit)
            ->select();
        $list = collection($list)->toArray();

        $check_status = [ 0=>'新建',1=>'待审核',2=>'已审核',3=>'已拒绝',4=>'已取消'];
        foreach($list as $key=>$value){
            unset($list[$key]['status']);
            $list[$key]['check_status'] = $check_status[$value['check_status']];
            //按钮
            $list[$key]['show_start'] = 0 == $value['status'] ? 1 : 0;//开始盘点按钮
            $list[$key]['show_continue'] = 1 == $value['status'] ? 1 : 0;//继续盘点按钮
            $list[$key]['show_examine'] = 2 == $value['status'] && 1 == $value['check_status'] ? 1 : 0;//审核按钮
            $list[$key]['show_detail'] = in_array($value['check_status'], [2,3]) ? 1 : 0;//详情按钮
            //计算已盘点数量
            $count = $this->_inventory_item->where(['inventory_id' => $value['id']])->count();
            $sum = $this->_inventory_item->where(['inventory_id' => $value['id'], 'is_add' => 0])->count();

            $list[$key]['sum_count'] = $sum.'/'.$count;//需要fa_inventory_item表数据加和
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
            $skus = $this->_inventory_item
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
                $where['a.sku|b.coding'] = ['like', '%' . $query . '%'];//coding库位编码，library_name库位名称
            }
            if($start_stock && $end_stock){
                $where['c.stock'] = ['between', [$start_stock, $end_stock]];
            }

            $offset = ($page - 1) * $page_size;
            $limit = $page_size;

            //获取SKU库位绑定表（fa_store_sku）数据列表
            $list = $this->_store_sku
                ->alias('a')
                ->field('a.id,a.sku,b.coding')
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
            $item_sku = $this->request->request("item_sku");
            $item_sku = html_entity_decode($item_sku);
            $item_sku = array_filter(json_decode($item_sku,true));
            if (count(array_filter($item_sku)) < 1) {
                $this->error(__('sku集合不能为空！！'), [], 524);
            }

            $result = false;
            Db::startTrans();
            try {
                //保存--创建盘点单
                $arr = [];
                $arr['number'] = 'IS' . date('YmdHis') . rand(100, 999) . rand(100, 999);
                $arr['create_person'] = $this->auth->nickname;
                $arr['createtime'] = date('Y-m-d H:i:s', time());
                $result = $this->_inventory->allowField(true)->save($arr);

                if ($result) {
                    $list = [];
                    foreach ($item_sku as $k => $v) {
                        $list[$k]['inventory_id'] = $this->_inventory->id;
                        $list[$k]['sku'] = $v['sku'];
                        $item = $this->_item->field('name,stock,available_stock,distribution_occupy_stock')->where('sku',$v['sku'])->find();

                        $list[$k]['name'] = $item['name'];//商品名
                        $list[$k]['distribution_occupy_stock'] = $item['distribution_occupy_stock'];//配货站用数量
                        $real_time_qty = ($item['stock'] * 1 - $item['distribution_occupy_stock'] * 1);//实时库存
                        $list[$k]['real_time_qty'] = $real_time_qty ?? 0;
                        $list[$k]['available_stock'] = $item['available_stock'];//可用库存
//                        $list[$k]['inventory_qty'] = $v['inventory_qty'];//盘点数量
//                        $list[$k]['error_qty'] = $v['error_qty'];//误差数量
                        $list[$k]['remark'] = $v['remark'];//备注
                    }
                    //添加明细表数据
                    $result = $this->_inventory_item->allowField(true)->saveAll($list);
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
     * 盘点单详情/开始盘点/继续盘点页面--ok
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
        $_inventory_info = $this->_inventory->get($inventory_id);
        empty($_inventory_info) && $this->error(__('盘点单不存在'), [], 531);
        if ($_inventory_info['status'] > 0) {
            $this->error(__('此状态不能编辑'), [], 512);
        }
//        $inventory_item_info = $_inventory_item->field('id,sku,inventory_qty,error_qty,real_time_qty,available_stock,distribution_occupy_stock')->where(['inventory_id'=>$inventory_id])->select();
        $inventory_item_info = $this->_inventory_item
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
     * 开始盘点页面，保存/提交--ok
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
        $item_sku = html_entity_decode($item_sku);
        $item_sku = array_filter(json_decode($item_sku,true));
        if (count(array_filter($item_sku)) < 1) {
            $this->error(__('sku集合不能为空！！'), [], 540);
        }

        $inventory_id = $this->request->request("inventory_id");
        empty($inventory_id) && $this->error(__('盘点单号不能为空'), [], 541);
        //获取盘点单数据
        $row = $this->_inventory->get($inventory_id);
        empty($row) && $this->error(__('盘点单不存在'), [], 543);
        if ($row['status'] > 1) {
            $this->error(__('此状态不能编辑'), [], 544);
        }

        if ($do_type == 1) {
            //提交
            $params['status'] = 2;//盘点完成
            $params['end_time'] = date('Y-m-d H:i:s', time());
            $is_add = 1;//更新为盘点
            $msg = '提交成功';
        } else {
            //保存
            $is_add = 0;//未盘点
            $params['status'] = 1;
            $msg = '保存成功';
        }

        //保存不需要编辑盘点单
        //编辑盘点单明细item
        foreach (array_filter($item_sku) as $k => $v) {
            $save_data = [];
            $save_data['is_add'] = $is_add;//是否盘点
            $save_data['inventory_qty'] = $v['inventory_qty'];//盘点数量
            $save_data['error_qty'] = $v['error_qty'];//误差数量
            $save_data['remark'] = $v['remark'];//备注
            $this->_inventory_item->where(['inventory_id' => $inventory_id,'sku' => $v['sku']])->update($save_data);
        }

        //提交盘点单状态为已完成，保存盘点单状态为盘点中
        $this->_inventory->allowField(true)->save($params, ['id' => $inventory_id]);
        $this->success($msg, ['info' => ''],200);
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
        $row = $this->_inventory->get($inventory_id);
        empty($row) && $this->error(__('盘点单不存在'), [], 546);
        if ($row['check_status'] != 1 || $row['status'] !=2) {
            $this->error(__('只有待审核、已完成状态才能操作'), [], 547);
        }
        $data['check_time'] = date('Y-m-d H:i:s', time());
        $data['check_person'] = $this->auth->nickname;

        $msg = '';
        if ($do_type == 2){
            $data['check_status'] = 4;
            $this->_inventory->allowField(true)->save($data, ['id' => $inventory_id]);
            $msg = '操作成功';
        }

        $data['check_status'] = 2;
        //回滚
        Db::startTrans();
        try {
            $res = $this->_inventory->allowField(true)->isUpdate(true, ['id'=>$inventory_id])->save($data);
            //审核通过 生成入库单 并同步库存
            if ($data['check_status'] == 2) {
                $infos = $this->_inventory_item->where(['inventory_id' => $inventory_id])
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
                        $stock = $this->_item->where($item_map)->inc('stock', $v['error_qty'])->inc('available_stock', $v['error_qty'])->update();

                        //盘点的时候盘盈入库 盘亏出库 的同时要对虚拟库存进行一定的操作
                        //查出映射表中此sku对应的所有平台sku 并根据库存数量进行排序（用于遍历数据的时候首先分配到那个站点）
                        $item_platform_sku = $this->_item_platform_sku->where('sku',$v['sku'])->order('stock asc')->field('platform_type,stock')->select();
                        $all_num = count($item_platform_sku);
                        // $whole_num = $this->_item_platform_sku->where('sku',$v['sku'])->sum('stock');
                        $whole_num = $this->_item_platform_sku
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
                                    $this->_item_platform_sku->where(['sku' => $v['sku'], 'platform_type' => $val['platform_type']])->inc('stock', $stock_num)->update();
                                } else {
                                    $num = round($v['error_qty'] * $rate_rate);
                                    $stock_num -= $num;
                                    $this->_item_platform_sku->where(['sku' => $v['sku'], 'platform_type' => $val['platform_type']])->inc('stock', $num)->update();
                                }
                            }
                        }else{
                            foreach ($item_platform_sku as $key => $val) {
                                // dump($item_platform_sku);die;
                                //最后一个站点 剩余数量分给最后一个站
                                if (($all_num - $key) == 1) {
                                    $this->_item_platform_sku->where(['sku' => $v['sku'], 'platform_type' => $val['platform_type']])->inc('stock', $stock_num)->update();
                                } else {
                                    $num = round($v['error_qty'] * abs($val['stock'])/$num_num);
                                    $stock_num -= $num;
                                    $this->_item_platform_sku->where(['sku' => $v['sku'], 'platform_type' => $val['platform_type']])->inc('stock', $num)->update();
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
                    $this->_stock_log->setData([
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
                    $instorck_res = $this->_in_stock->isUpdate(false)->allowField(true)->data($params, true)->save();

                    //添加入库信息
                    if ($instorck_res !== false) {
                        $instockItemList = array_values($info);
                        unset($info);
                        foreach ($instockItemList as &$v) {
                            $v['in_stock_id'] = $this->_in_stock->id;
                        }
                        unset($v);
                        //批量添加
                        $this->_in_stock_item->allowField(true)->saveAll($instockItemList);
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
                    $outstock_res = $this->_out_stock->isUpdate(false)->allowField(true)->data($params, true)->save();


                    //添加入库信息
                    if ($outstock_res !== false) {
                        $outstockItemList = array_values($list);
                        foreach ($outstockItemList as $k => $v) {
                            $outstockItemList[$k]['out_stock_id'] = $this->_out_stock->id;
                        }
                        //批量添加
                        $this->_out_stock_item->allowField(true)->saveAll($outstockItemList);
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
        if ($res){
            $msg = '审核成功';
        }

        $this->success($msg, ['info' => ''],200);
    }

}
