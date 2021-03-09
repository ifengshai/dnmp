<?php

namespace app\api\controller;

use app\admin\model\warehouse\WarehouseTransferOrder;
use app\admin\model\warehouse\WarehouseTransferOrderItem;
use app\admin\model\warehouse\WarehouseTransferOrderItemCode;
use think\Db;
use think\Exception;
use think\exception\PDOException;
use think\exception\ValidateException;
use app\admin\model\warehouse\Outstock;
use app\admin\model\warehouse\OutStockItem;
use app\admin\model\warehouse\OutstockType;
use app\admin\model\warehouse\Check;
use app\admin\model\warehouse\CheckItem;
use app\admin\model\warehouse\Instock;
use app\admin\model\warehouse\InstockItem;
use app\admin\model\warehouse\InstockType;
use app\admin\model\purchase\PurchaseOrder;
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
use app\admin\model\warehouse\WarehouseArea;
use app\admin\model\warehouse\StockHouse;

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
     * 质检商品模型对象
     * @var object
     * @access protected
     */
    protected $_check_item = null;

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
     * 采购单商品模型对象
     * @var object
     * @access protected
     */
    protected $_purchase_order = null;

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
     * 库区模型对象
     * @var object
     * @access protected
     */
    protected $_warehouse_area = null;

    /**
     * SKU库位绑定模型对象
     * @var object
     * @access protected
     */
    protected $_store_sku = null;

    /**
     * 库位模型对象
     * @var object
     * @access protected
     */
    protected $_store_house = null;

    protected function _initialize()
    {
        parent::_initialize();

        $this->_out_stock = new Outstock();
        $this->_out_stock_item = new OutStockItem();
        $this->_out_stock_type = new OutstockType();
        $this->_check = new Check();
        $this->_check_item = new CheckItem();
        $this->_in_stock = new Instock();
        $this->_in_stock_item = new InstockItem();
        $this->_in_stock_type = new InstockType();
        $this->_new_product_mapping = new NewProductMapping();
        $this->_purchase_order = new PurchaseOrder();
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
        $this->_warehouse_area = new WarehouseArea();
        $this->_store_house = new StockHouse();
        $this->_warehouse_transfer_order = new WarehouseTransferOrder();
        $this->_warehouse_transfer_order_item = new WarehouseTransferOrderItem();
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
     * @return mixed
     * @author lzh
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
        if ($query) {
            //            $where['a.out_stock_number|a.create_person|b.sku'] = ['like', '%' . $query . '%'];
            $where['a.id'] = ['in', function ($search) use ($query) {
                $search
                    ->table('fa_out_stock')
                    ->field('id')
                    ->union("SELECT out_stock_id FROM fa_out_stock_item WHERE sku like '" . $query . "%'")
                    ->union("SELECT id FROM fa_out_stock WHERE create_person like '" . $query . "%'")
                    ->where(['out_stock_number' => ['like', $query . '%']]);
            }];
        }
        if (isset($status)) {
            $where['a.status'] = $status;
        }
        if ($start_time && $end_time) {
            $where['a.createtime'] = ['between', [$start_time, $end_time]];
        }

        $offset = ($page - 1) * $page_size;
        $limit = $page_size;

        //获取出库单列表数据
        $list = $this->_out_stock
            ->alias('a')
            ->where($where)
            ->field('a.id,a.out_stock_number,a.createtime,a.status,a.type_id,a.remark')
            //            ->join(['fa_out_stock_item' => 'b'], 'a.id=b.out_stock_id', 'left')
            ->order('a.createtime', 'desc')
            ->limit($offset, $limit)
            ->select();
        $list = collection($list)->toArray();

        //获取出库分类数据
        $type_list = $this->_out_stock_type
            ->where('is_del', 1)
            ->column('name', 'id');

        $status = [0 => '新建', 1 => '待审核', 2 => '已审核', 3 => '已拒绝', 4 => '已取消'];
        foreach ($list as $key => $value) {
            $list[$key]['status'] = $status[$value['status']];
            $list[$key]['type_name'] = $type_list[$value['type_id']];
            $list[$key]['cancel_show'] = 0 == $value['status'] ? 1 : 0;
            $list[$key]['edit_show'] = 0 == $value['status'] ? 1 : 0;
            $list[$key]['detail_show'] = 1 < $value['status'] ? 1 : 0;
            $list[$key]['examine_show'] = 1 == $value['status'] ? 1 : 0;
        }

        $this->success('', ['list' => $list], 200);
    }

    /**
     * 新建/编辑/详情出库单页面
     *
     * @参数 int out_stock_id  出库单ID
     * @return mixed
     * @author lzh
     */
    public function out_stock_add()
    {
        $out_stock_id = $this->request->request('out_stock_id');
        if ($out_stock_id) {
            $info = $this->_out_stock
                ->field('out_stock_number,type_id,platform_id')
                ->where('id', $out_stock_id)
                ->find();

            //获取出库单商品数据
            $item_data = $this->_out_stock_item
                ->field('sku,out_stock_num')
                ->where('out_stock_id', $out_stock_id)
                ->select();

            //获取各站点虚拟仓库存
            $stock_list = $this->_item_platform_sku
                ->where('platform_type', $info['platform_id'])
                ->column('stock', 'sku');

            //获取条形码数据
            $bar_code_list = $this->_product_bar_code_item
                ->where(['out_stock_id' => $out_stock_id])
                ->field('sku,code')
                ->select();
            $bar_code_list = collection($bar_code_list)->toArray();

            foreach ($item_data as $key => $value) {
                $sku = $value['sku'];
                //条形码列表
                $sku_agg = array_filter($bar_code_list, function ($v) use ($sku) {
                    if ($v['sku'] == $sku) {
                        return $v;
                    }
                });

                if (!empty($sku_agg)) {
                    array_walk($sku_agg, function (&$value, $k, $p) {
                        $value = array_merge($value, $p);
                    }, ['is_new' => 0]);
                }

                $item_data[$key]['sku_agg'] = array_values($sku_agg);
                $item_data[$key]['stock'] = $stock_list[$sku];
            }

            $info['item_data'] = $item_data;
        } else {
            $info = [
                'out_stock_number' => 'OUT' . date('YmdHis') . rand(100, 999) . rand(100, 999),
                'type_id' => 0,
                'platform_id' => 0,
                'item_data' => []
            ];
        }

        //获取出库分类数据
        $type_list = $this->_out_stock_type
            ->field('id,name')
            ->where('is_del', 1)
            ->where('id', 'not in', [2, 4])
            ->select();

        //站点列表
        $site_list = [
            ['id' => 1, 'title' => 'zeelool'],
            ['id' => 2, 'title' => 'voogueme'],
            ['id' => 3, 'title' => 'nihao'],
            ['id' => 4, 'title' => 'meeloog'],
            ['id' => 5, 'title' => 'wesee'],
            ['id' => 8, 'title' => 'amazon'],
            ['id' => 9, 'title' => 'zeelool_es'],
            ['id' => 10, 'title' => 'zeelool_de'],
            ['id' => 11, 'title' => 'zeelool_jp']
        ];

        $this->success('', ['type_list' => $type_list, 'site_list' => $site_list, 'info' => $info], 200);
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
     * @return mixed
     * @author lzh
     */
    public function out_stock_submit()
    {
        $type_id = $this->request->request('type_id');
        empty($type_id) && $this->error(__('出库分类ID不能为空'), [], 403);

        $platform_id = $this->request->request('platform_id');
        empty($platform_id) && $this->error(__('平台ID不能为空'), [], 403);

        $warehouse_area_id = $this->request->request("warehouse_area_id"); //出库库库位id
        empty($warehouse_area_id) && $this->error(__('请选择出库库位'), [], 510);

        $item_data = $this->request->request('item_data');
        $item_data = json_decode(htmlspecialchars_decode($item_data), true);
        empty($item_data) && $this->error(__('sku集合不能为空'), [], 403);
        $item_data = array_filter($item_data);

        $do_type = $this->request->request('do_type');
        $get_out_stock_id = $this->request->request('out_stock_id');

        //出库库位详情
        $warehouse_area = $this->_store_house->where('id', $warehouse_area_id)->find();

        $this->_out_stock->startTrans();
        $this->_out_stock_item->startTrans();
        $this->_product_bar_code_item->startTrans();
        try {
            //编辑提交
            if ($get_out_stock_id) {
                $row = $this->_out_stock->get($get_out_stock_id);
                if (empty($row)) throw new Exception('出库单不存在');
                if (0 != $row['status']) throw new Exception('只有新建状态才能编辑');

                //更新出库单
                $out_stock_data = [
                    'type_id' => $type_id,
                    'platform_id' => $platform_id,
                    'status' => 1 == $do_type ?: 0
                ];
                $result = $this->_out_stock->allowField(true)->save($out_stock_data, ['id' => $get_out_stock_id]);
                $out_stock_id = $get_out_stock_id;

                $this->_out_stock_item->where(['out_stock_id' => $out_stock_id])->delete();
            } else {
                //新建提交
                $out_stock_number = $this->request->request('out_stock_number');
                if (empty($out_stock_number)) throw new Exception('出库单号不能为空');
                $check_number = $this->_out_stock->where(['out_stock_number' => $out_stock_number])->value('id');
                if (!empty($check_number)) throw new Exception('出库单号已存在');

                //创建出库单
                $out_stock_data = [
                    'out_stock_number' => $out_stock_number,
                    'type_id' => $type_id,
                    'platform_id' => $platform_id,
                    'status' => 1 == $do_type ?: 0,
                    'create_person' => $this->auth->nickname,
                    'createtime' => date('Y-m-d H:i:s')
                ];
                $result = $this->_out_stock->allowField(true)->save($out_stock_data);
                $out_stock_id = $this->_out_stock->id;
            }

            if (false === $result) throw new Exception('提交失败');

            if (count($item_data) != count(array_unique(array_column($item_data, 'sku')))) throw new Exception('sku重复，请检查');

            //获取各站点虚拟仓库存
            $stock_list = $this->_item_platform_sku
                ->where('platform_type', $platform_id)
                ->column('stock', 'sku');

            //校验各站点虚拟仓库存
            foreach ($item_data as $key => $value) {
                if (empty($stock_list[$value['sku']])) throw new Exception('sku: ' . $value['sku'] . ' 没有同步至对应平台');
                if ($value['out_stock_num'] > $stock_list[$value['sku']]) throw new Exception('sku: ' . $value['sku'] . ' 出库数量不能大于虚拟仓库存');
            }

            //检测条形码是否已绑定
            $where['out_stock_id'] = [['>', 0], ['neq', $out_stock_id]];
            foreach ($item_data as $key => $value) {
                $sku_code = array_column($value['sku_agg'], 'code');
                if (count($value['sku_agg']) != count(array_unique($sku_code))) throw new Exception(' 条形码有重复，请检查');

                $where['code'] = ['in', $sku_code];
                $check_quantity = $this->_product_bar_code_item
                    ->where($where)
                    ->field('code')
                    ->find();
                if (!empty($check_quantity['code'])) throw new Exception('条形码:' . $check_quantity['code'] . ' 已绑定,请移除');
            }

            //批量创建或更新出库单商品
            foreach ($item_data as $key => $value) {
                //出库单移除条形码
                if (!empty($value['remove_agg'])) {
                    $this->_product_bar_code_item
                        ->where(['code' => ['in', $value['remove_agg']]])
                        ->update(['out_stock_id' => 0]);
                }

                //新增出库单sku数据
                $item_save = [
                    'out_stock_num' => $value['out_stock_num'],
                    'out_stock_id' => $out_stock_id,
                    'sku' => $value['sku']
                ];
                $this->_out_stock_item->allowField(true)->isUpdate(false)->data($item_save)->save();

                //绑定条形码
                foreach ($value['sku_agg'] as $v) {
                    $this->_product_bar_code_item->where(['code' => $v['code'], 'location_code' => $warehouse_area['coding']])->update(['out_stock_id' => $out_stock_id]);
                }
            }

            $this->_out_stock->commit();
            $this->_out_stock_item->commit();
            $this->_product_bar_code_item->commit();
        } catch (ValidateException $e) {
            $this->_out_stock->rollback();
            $this->_out_stock_item->rollback();
            $this->_product_bar_code_item->rollback();
            $this->error($e->getMessage(), [], 406);
        } catch (PDOException $e) {
            $this->_out_stock->rollback();
            $this->_out_stock_item->rollback();
            $this->_product_bar_code_item->rollback();
            $this->error($e->getMessage(), [], 407);
        } catch (Exception $e) {
            $this->_out_stock->rollback();
            $this->_out_stock_item->rollback();
            $this->_product_bar_code_item->rollback();
            $this->error($e->getMessage(), [], 408);
        }

        $this->success('提交成功', [], 200);
    }

    /**
     * 审核出库单
     *
     * @参数 int out_stock_id  出库单ID
     * @参数 int do_type  2审核通过，3审核拒绝
     * @return mixed
     * @author lzh
     */
    public function out_stock_examine()
    {

        /*****************限制如果有盘点单未结束不能操作出库单审核*******************/
        $count = $this->_inventory->where(['is_del' => 1, 'check_status' => ['in', [0, 1]]])->count();
        if ($count > 0) {
            $this->error(__('存在正在盘点的单据,暂无法审核'), [], 403);
        }

        /****************************end*****************************************/

        $out_stock_id = $this->request->request('out_stock_id');
        empty($out_stock_id) && $this->error(__('出库单ID不能为空'), [], 403);

        $do_type = $this->request->request('do_type');
        empty($do_type) && $this->error(__('审核类型不能为空'), [], 403);
        !in_array($do_type, [2, 3]) && $this->error(__('审核类型错误'), [], 403);

        //检测出库单状态
        $row = $this->_out_stock->get($out_stock_id);
        1 != $row['status'] && $this->error(__('只有待审核状态才能审核'), [], 405);

        $this->_item->startTrans();
        $this->_stock_log->startTrans();
        $this->_item_platform_sku->startTrans();
        $this->_product_bar_code_item->startTrans();
        try {
            //审核通过扣减库存
            if ($do_type == 2) {
                //获取出库单商品数据
                $item_data = $this->_out_stock_item
                    ->field('sku,out_stock_num')
                    ->where('out_stock_id', $out_stock_id)
                    ->select();

                //获取各站点虚拟仓库存
                $stock_list = $this->_item_platform_sku
                    ->where('platform_type', $row['platform_id'])
                    ->column('stock', 'sku');

                //校验各站点虚拟仓库存
                foreach ($item_data as $value) {
                    $value['out_stock_num'] > $stock_list[$value['sku']] && $this->error(__('sku: ' . $value['sku'] . ' 出库数量不能大于虚拟仓库存'), [], 405);
                }

                $stock_data = [];
                //出库扣减库存
                foreach ($item_data as $value) {
                    //扣除商品表总库存
                    $sku = $value['sku'];
                    $sku_item = $this->_item->where(['sku' => $sku])->find();
                    $this->_item->where(['sku' => $sku])->dec('stock', $value['out_stock_num'])->dec('available_stock', $value['out_stock_num'])->update();

                    //扣减对应平台sku库存
                    $item_platform_sku_detail = $this->_item_platform_sku->where(['sku' => $sku, 'platform_type' => $row['platform_id']])->find();
                    $this->_item_platform_sku->where(['sku' => $sku, 'platform_type' => $row['platform_id']])->dec('stock', $value['out_stock_num'])->update();

                    $stock_data[] = [
                        //'大站点类型：1网站 2魔晶',
                        'type' => 2,
                        //'站点类型：1Zeelool  2Voogueme 3Nihao 4Meeloog 5Wesee 8Amazon 9Zeelool_es 10Zeelool_de 11Zeelool_jp'
                        'site' => 0,
                        //'模块：1普通订单 2配货 3质检 4审单 5异常处理 6更改镜架 7取消订单 8补发 9赠品 10采购入库 11出入库 12盘点 13调拨'
                        'modular' => 11,
                        //'变动类型：1非预售下单 2预售下单-虚拟仓>0 3预售下单-虚拟仓<0 4配货 5质检拒绝-镜架报损 6审单-成功 7审单-配错镜框
                        // 8加工异常打回待配货 9印logo异常打回待配货 10更改镜架-配镜架前 11更改镜架-配镜架后 12取消订单-配镜架前 13取消订单-配镜架后
                        // 14补发 15赠品 16采购-有比例入库 17采购-没有比例入库 18手动入库 19手动出库 20盘盈入库 21盘亏出库 22调拨 23调拨 24库存调拨'
                        'change_type' => 19,
                        // '关联sku'
                        'sku' => $sku,
                        //'关联订单号或子单号'
                        'order_number' => $this->_out_stock->where('id', $out_stock_id)->value('out_stock_number'),
                        //'关联变化的ID'
                        'public_id' => 0,
                        //'操作端：1PC端 2PDA'
                        'source' => 2,
                        //'总库存变动前'
                        'stock_before' => $sku_item['stock'],
                        //'总库存变化量：正数为加，负数为减'
                        'stock_change' => -$value['out_stock_num'],
                        //'可用库存变动前'
                        'available_stock_before' => $sku_item['available_stock'],
                        //'可用库存变化量：正数为加，负数为减'
                        'available_stock_change' => -$value['out_stock_num'],
                        'create_person' => $this->auth->nickname,
                        'create_time' => time(),
                        //'关联单号类型：1订单号 2子订单号 3入库单 4出库单 5盘点单 6调拨单'
                        'number_type' => 4,
                    ];
                    //插入日志表
                    $stock_data1[] = [
                        'type' => 2,
                        'site' => $row['platform_id'],
                        'modular' => 11,
                        'change_type' => 19,
                        'sku' => $sku,
                        'order_number' => $this->_out_stock->where('id', $out_stock_id)->value('out_stock_number'),
                        'source' => 2,
                        'fictitious_before' => $item_platform_sku_detail['stock'],
                        'fictitious_change' => -$value['out_stock_num'],
                        'create_person' => $this->auth->nickname,
                        'create_time' => time(),
                        'number_type' => 4,
                    ];
                }

                //库存变动日志不分站点的
                $this->_stock_log->allowField(true)->saveAll($stock_data);

                //库存变动日志分站点的
                $this->_stock_log->allowField(true)->saveAll($stock_data1);

                //校验条形码是否已出库
                $check_bar_code = $this->_product_bar_code_item->where(['out_stock_id' => $out_stock_id, 'library_status' => 2])->value('code');
                if ($check_bar_code) throw new Exception("条形码：{$check_bar_code}已出库，请检查");

                //条形码出库时间
                $this->_product_bar_code_item
                    ->allowField(true)
                    ->isUpdate(true, ['out_stock_id' => $out_stock_id])
                    ->save(['out_stock_time' => date('Y-m-d H:i:s'), 'library_status' => 2, 'location_code' => '', 'location_id' => '']); //出库解除库位号与商品条形码绑定

                //计算出库成本 
                $financecost = new \app\admin\model\finance\FinanceCost();
                $financecost->outstock_cost($out_stock_id, $row['out_stock_number']);
            } else { //审核拒绝解除条形码绑定关系
                $code_clear = [
                    'out_stock_id' => 0
                ];
                $this->_product_bar_code_item
                    ->allowField(true)
                    ->isUpdate(true, ['out_stock_id' => $out_stock_id])
                    ->save($code_clear);
            }
            $this->_item->commit();
            $this->_stock_log->commit();
            $this->_item_platform_sku->commit();
            $this->_product_bar_code_item->commit();
        } catch (ValidateException $e) {
            $this->_item->rollback();
            $this->_stock_log->rollback();
            $this->_item_platform_sku->rollback();
            $this->_product_bar_code_item->rollback();
            $this->error($e->getMessage(), [], 406);
        } catch (PDOException $e) {
            $this->_item->rollback();
            $this->_stock_log->rollback();
            $this->_item_platform_sku->rollback();
            $this->_product_bar_code_item->rollback();
            $this->error($e->getMessage(), [], 407);
        } catch (Exception $e) {
            $this->_item->rollback();
            $this->_stock_log->rollback();
            $this->_item_platform_sku->rollback();
            $this->_product_bar_code_item->rollback();
            $this->error($e->getMessage(), [], 408);
        }

        $res = $this->_out_stock->allowField(true)->isUpdate(true, ['id' => $out_stock_id])->save(['status' => $do_type]);
        false === $res ? $this->error(__('审核失败'), [], 404) : $this->success('审核成功', [], 200);
    }

    /**
     * 取消出库
     *
     * @参数 int out_stock_id  出库单ID
     * @return mixed
     * @author lzh
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

        $res = $this->_out_stock->allowField(true)->isUpdate(true, ['id' => $out_stock_id])->save(['status' => 4]);
        $res ? $this->success('取消成功', [], 200) : $this->error(__('取消失败'), [], 404);
    }

    /**
     * 待入库列表--ok
     * 质检单审核时间examine_time即为完成时间
     *
     * @参数 string query  查询内容
     * @参数 int status  状态
     * @参数 string start_time  开始时间
     * @参数 string end_time  结束时间
     * @参数 int page  页码
     * @参数 int page_size  每页显示数量
     * @return mixed
     * @author wgj
     */
    public function no_in_stock_list()
    {
        $query = $this->request->request('query');
        $start_time = $this->request->request('start_time');
        $end_time = $this->request->request('end_time');
        $page = $this->request->request('page');
        $page_size = $this->request->request('page_size');

        empty($page) && $this->error(__('Page can not be empty'), [], 406);
        empty($page_size) && $this->error(__('Page size can not be empty'), [], 407);

        $where = [];
        $where['a.is_stock'] = 0; //质检单待入库 状态为0
        $where['a.status'] = 2; //质检单待入库 状态为2 已审核
        if ($query) {
            $where['a.check_order_number|b.sku|c.logistics_number'] = ['like', '%' . $query . '%'];
        }
        if ($start_time && $end_time) {

            $where['a.createtime'] = ['between', [$start_time, $end_time]];
        }

        $offset = ($page - 1) * $page_size;
        $limit = $page_size;

        //获取质检单列表数据
        $list = $this->_check
            ->alias('a')
            ->where($where)
            ->field('a.id,a.check_order_number,c.logistics_number,a.createtime,a.examine_time,b.sku,a.create_person,a.logistics_id,a.batch_id,b.remark,d.purchase_number')
            ->join(['fa_check_order_item' => 'b'], 'a.id=b.check_id', 'left')
            ->join(['fa_logistics_info' => 'c'], 'a.logistics_id=c.id', 'left')
            ->join(['fa_purchase_order' => 'd'], 'a.purchase_id=d.id')
            ->group('a.id')
            ->order('a.createtime', 'desc')
            ->limit($offset, $limit)
            ->select();
        $list = collection($list)->toArray();
        // dump($list);
        foreach ($list as $key => $value) {
            //签收编号
            $list[$key]['sign_number'] = Db::name('logistics_info')->where('id', $value['logistics_id'])->value('sign_number');
            //应到货数量
            $list[$key]['should_arrival_num'] = Db::name('check_order_item')->where('check_id', $value['id'])->value('should_arrival_num');
            //供应商名称
            $list[$key]['supplier_name'] = $this->_purchase_order
                ->alias('a')
                ->join(['fa_supplier' => 'b'], 'a.supplier_id=b.id')
                ->where('a.purchase_number', $value['purchase_number'])
                ->value('b.supplier_name');
            $list[$key]['batch_id'] = $value['batch_id'] == 0 ? '无批次' : $value['batch_id'];
        }

        $this->success('', ['list' => $list], 200);
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
     * @return mixed
     * @author wgj
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
        if ($query) {
            //            $where['a.in_stock_number|b.check_order_number|c.sku|a.create_person|b.create_person'] = ['like', '%' . $query . '%'];
            $where['a.check_id'] = ['in', function ($search) use ($query) {
                $search
                    ->table('fa_check_order')
                    ->field('id')
                    ->union("SELECT id FROM fa_check_order WHERE create_person like '" . $query . "%'")
                    ->union("SELECT check_id FROM fa_check_order_item WHERE sku like '" . $query . "%'")
                    ->where(['check_order_number' => ['like', $query . '%']]);
            }];
        }
        if (isset($status)) {
            $where['a.status'] = $status;
        }
        if ($start_time && $end_time) {
            $where['a.createtime'] = ['between', [$start_time, $end_time]];
        }

        $offset = ($page - 1) * $page_size;
        $limit = $page_size;

        //获取入库单列表数据
        $list = $this->_in_stock
            ->alias('a')
            ->where($where)
            ->field('a.id,a.check_id,a.in_stock_number,b.check_order_number,a.createtime,a.status,a.type_id')
            ->join(['fa_check_order' => 'b'], 'a.check_id=b.id', 'left')
            //            ->join(['fa_check_order_item' => 'c'], 'a.check_id=c.check_id', 'left')
            ->group('a.id')
            ->order('a.createtime', 'desc')
            ->limit($offset, $limit)
            ->select();
        $list = collection($list)->toArray();

        $status_list = [0 => '新建', 1 => '待审核', 2 => '已审核', 3 => '已拒绝', 4 => '已取消'];
        foreach ($list as $key => $value) {
            $list[$key]['status'] = $status_list[$value['status']];
            //按钮
            if ($list[$key]['check_id']) {
                $list[$key]['check_in'] = 1; //是否有质检单 1有 0没有
            } else {
                $list[$key]['check_in'] = 0; //是否有质检单 1有 0没有
            }
            //退货入库
            if ($list[$key]['type_id'] == 3) {
                $list[$key]['check_in'] = 0;
                $list[$key]['check_id'] = 0;
            }
            $list[$key]['show_edit'] = 0 == $value['status'] ? 1 : 0; //编辑按钮
            $list[$key]['cancel_show'] = 0 == $value['status'] ? 1 : 0; //取消按钮
            $list[$key]['show_examine'] = 1 == $value['status'] ? 1 : 0; //审核按钮
            $list[$key]['show_detail'] = in_array($value['status'], [2, 3, 4]) ? 1 : 0; //详情按钮
        }

        $this->success('', ['list' => $list], 200);
    }

    /**
     * 取消入库单--ok
     *
     * @参数 int in_stock_id  入库单ID
     * @return mixed
     * @author wgj
     */
    public function in_stock_cancel()
    {
        $in_stock_id = $this->request->request('in_stock_id');
        empty($in_stock_id) && $this->error(__('Id can not be empty'), [], 503);

        //检测入库单状态
        $row = $this->_in_stock->get($in_stock_id);
        empty($row) && $this->error(__('入库单不存在'), [], 503);
        0 != $row['status'] && $this->error(__('只有新建状态才能取消'), [], 504);

        //解除条形码绑定关系
        $this->_product_bar_code_item->allowField(true)->isUpdate(true, ['in_stock_id' => $in_stock_id])->save(['in_stock_id' => 0, 'location_code' => '']);

        $res = $this->_in_stock->allowField(true)->isUpdate(true, ['id' => $in_stock_id])->save(['status' => 4]);
        $res ? $this->success('取消成功', [], 200) : $this->error(__('取消失败'), [], 505);
    }

    /**
     * 新建/编辑入库提交/保存--ok
     *
     * 提交后状态为待审核status=1/保存后状态为新建status=0
     *
     * @参数 int in_stock_id  入库单ID（编辑时必传）
     * @参数 int type_id  入库分类ID（新建时必传）
     * @参数 string in_stock_number  入库单号（必传）
     * @参数 string check_order_number  质检单号（入库单新创建时必传，质检单入口创建时不传）
     * @参数 int platform_id  平台/站点ID（入库单新创建时必传，质检单入口创建时不传）
     * @参数 int do_type  操作类型：1提交2保存
     * @参数 json item_sku  sku数据集合
     * @return mixed
     * @author wgj
     */
    public function in_stock_submit()
    {
        $do_type = $this->request->request('do_type');
        empty($do_type) && $this->error(__('请选择操作类型'), [], 510);
        if ($do_type == 1) {
            $msg = '提交';
        } else {
            $msg = '保存';
        }
        $in_stock_number = $this->request->request("in_stock_number");
        empty($in_stock_number) && $this->error(__('入库单号不能为空'), [], 510);
        $type_id = $this->request->request("type_id"); //入库分类
        empty($type_id) && $this->error(__('请选择入库分类'), [], 510);
        $area_id = $this->request->request("area_id"); //入库库区id
        empty($area_id) && $this->error(__('请选择入库库区'), [], 510);
        $warehouse_area_id = $this->request->request("warehouse_area_id"); //入库库位id
        empty($warehouse_area_id) && $this->error(__('请选择入库库位'), [], 510);
        $item_sku = $this->request->request("item_data");
        empty($item_sku) && $this->error(__('sku集合不能为空！！'), [], 508);
        $item_sku = json_decode(htmlspecialchars_decode($item_sku), true);
        empty($item_sku) && $this->error(__('sku集合不能为空'), [], 403);
        $item_sku = array_filter($item_sku);

        $in_stock_id = $this->request->request("in_stock_id"); //入库单ID，
        $platform_id = $this->request->request("platform_id"); //站点，判断是否是新创建入库 还是 质检单入库
        $result = false;

        //入库库位详情
        $warehouse_area = $this->_store_house->where('id', $warehouse_area_id)->find();
        $area = $this->_warehouse_area->where('id', $area_id)->find();
        foreach ($item_sku as $key1 => $value1) {
            $store_sku = Db::name('store_sku')->where('sku',$value1['sku'])->where('store_id',$warehouse_area_id)->find();
            //判断sku跟库位号是否有绑定关系
            if (empty($store_sku)) {
               $this->error('sku:' . $value1['sku'] . '与'.$warehouse_area['coding'].'没有绑定关系，请先绑定');
            }
            //有库容 入库数量大于库容不能继续
            if ($warehouse_area['volume'] && $value1['in_stock_num'] > $warehouse_area['volume']) {
                $this->error('sku:' . $value1['sku'] . '入库数量大于'.$warehouse_area['coding'].'库容');
            }
        }
        $this->_check->startTrans();
        $this->_in_stock->startTrans();
        $this->_in_stock_item->startTrans();
        $this->_product_bar_code_item->startTrans();
        try {
            if ($in_stock_id) {
                //有入库单ID，编辑
                //检测条形码是否已绑定
                $where['in_stock_id'] = [['>', 0], ['neq', $in_stock_id]];
                foreach ($item_sku as $key => $value) {
                    $sku_code = array_column($value['sku_agg'], 'code');
                    if (count($value['sku_agg']) != count(array_unique($sku_code))) throw new Exception(' 条形码有重复，请检查');

                    $where['code'] = ['in', $sku_code];
                    $check_quantity = $this->_product_bar_code_item
                        ->where($where)
                        ->field('code')
                        ->find();
                    if (!empty($check_quantity['code'])) {
                        throw new Exception('条形码:' . $check_quantity['code'] . ' 已绑定,请移除');
                    }
                }

                $_in_stock_info = $this->_in_stock->get($in_stock_id);
                if (empty($_in_stock_info)) {
                    throw new Exception('入库单不存在');
                }
                if ($_in_stock_info['status'] != 0) {
                    throw new Exception('只有新建状态才可以修改');
                }
                //更新数据组装
                $_in_stock_data = [
                    'type_id' => $type_id,
                    'status' => 1 == $do_type ? 1 : 0
                ];

                if ($platform_id) {
                    //有站点，入库单创建入口
                    $row = $this->_in_stock->get($in_stock_id);
                    if (empty($row)) {
                        throw new Exception('入库单不存在');
                    }

                    //编辑入库单主表
                    $_in_stock_data['platform_id'] = $platform_id;
                    $purchase_id = 0; //无采购单id
                    $check_data = []; //质检单子表数据
                } else {
                    //无站点，是质检单入口
                    $check_order_number = $this->request->request("check_order_number");
                    if (empty($check_order_number)) {
                        throw new Exception('质检单号不能为空');
                    }
                    $check_info = $this->_check->where(['check_order_number' => $check_order_number])->field('id,purchase_id,replenish_id')->find();
                    if (empty($check_info)) {
                        throw new Exception('质检单不存在');
                    }
                    $_in_stock_data['check_id'] = $check_info['id'];
                    $_in_stock_data['replenish_id'] = $check_info['replenish_id']; //补货单ID
                    $purchase_id = $check_info['purchase_id']; //有采购单id

                    //获取质检单子表数据
                    $check_data = $this->_check_item
                        ->where('check_id', $_in_stock_info['check_id'])
                        ->column('sample_num', 'sku');
                }

                //更新数据
                $result = $this->_in_stock->allowField(true)->save($_in_stock_data, ['id' => $in_stock_id]);
                //添加入库商品信息
                if ($result !== false) {
                    $where_code = [];
                    $where_code_sku = [];
                    foreach (array_filter($item_sku) as $k => $v) {
                        $item_save['purchase_id'] = $purchase_id; //采购单id
                        $item_save['in_stock_num'] = $v['in_stock_num']; //入库数量
                        $item_save['price'] = $v['price']; //退货入库采购单单价
                        $item_save['sample_num'] = $check_data[$v['sku']] ?: 0; //留样数量
                        //修改入库单子表
                        $where = ['sku' => $v['sku'], 'in_stock_id' => $in_stock_id];
                        $this->_in_stock_item->where($where)->update($item_save);

                        //入库单绑定条形码数组组装
                        foreach ($v['sku_agg'] as $k_code => $v_code) {
                            if (!empty($v_code['code'])) {
                                $where_code[] = $v_code['code'];
                                $where_code_sku[$v_code['code']] = $v['sku'];
                            }
                        }
                        //入库单移除条形码
                        if (!empty($value['remove_agg'])) {
                            $code_clear = [
                                'in_stock_id' => 0
                            ];
                            $this->_product_bar_code_item->where(['code' => ['in', $value['remove_agg']]])->update($code_clear);
                        }
                    }
                    //入库单绑定条形码执行
                    if ($where_code) {
                        $save_code_data = [];
                        if ($type_id == 3) { //退货入库绑定sku和商品条形码
                            foreach ($where_code_sku as $key => $value) {
                                $save_code_data['in_stock_id'] = $in_stock_id;
                                $save_code_data['sku'] = $where_code_sku[$key];
                                $save_code_data['location_code'] = $warehouse_area['coding']; //绑定条形码与库位号
                                $save_code_data['location_id'] = $area['id']; //绑定条形码与库区id
                                $this->_product_bar_code_item->where(['code' => $key])->update($save_code_data);
                            }
                        } else {
                            $save_code_data['in_stock_id'] = $in_stock_id;
                            $save_code_data['location_code'] = $warehouse_area['coding']; //绑定条形码与库位号
                            $save_code_data['location_id'] = $area['id']; //绑定条形码与库区id
                            $this->_product_bar_code_item->allowField(true)->isUpdate(true, ['code' => ['in', $where_code]])->save($save_code_data);
                        }
                    }
                }
            } else {
                //无入库单ID，新建入库单

                //检测条形码是否已绑定
                $where['in_stock_id'] = ['>', 0];
                foreach ($item_sku as $key => $value) {

                    $sku_code = array_column($value['sku_agg'], 'code');
                    if (count($value['sku_agg']) != count(array_unique($sku_code))) {
                        throw new Exception('条形码有重复，请检查');
                    }

                    $where['code'] = ['in', $sku_code];
                    $check_quantity = $this->_product_bar_code_item
                        ->where($where)
                        ->field('code')
                        ->find();
                    if (!empty($check_quantity['code'])) {
                        throw new Exception('条形码:' . $check_quantity['code'] . ' 已绑定,请移除');
                    }
                }

                //组装新增数据
                $params['in_stock_number'] = $in_stock_number;
                $params['type_id'] = $type_id;
                $params['status'] = 1 == $do_type ? 1 : 0;

                //存在平台id 代表把当前入库单的sku分给这个平台 首先做判断 判断入库单的sku是否都有此平台对应的映射关系
                $params['create_person'] = $this->auth->nickname;
                $params['createtime'] = date('Y-m-d H:i:s', time());
                if ($platform_id) {
                    $params['platform_id'] = $platform_id;

                    foreach (array_filter($item_sku) as $k => $v) {
                        $sku_platform = $this->_item_platform_sku->where(['sku' => $v['sku'], 'platform_type' => $params['platform_id']])->find();
                        if (!$sku_platform) {
                            $this->error('此sku：' . $v['sku'] . '没有同步至此平台，请先同步后重试');
                        }
                    }
                    //新增入库单
                    $result = $this->_in_stock->allowField(true)->save($params);

                    //添加入库商品信息
                    if ($result !== false) {
                        $data = [];
                        $where_code = [];
                        $where_code_sku = [];
                        foreach (array_filter($item_sku) as $k => $v) {
                            $data[$k]['sku'] = $v['sku'];
                            $data[$k]['in_stock_num'] = $v['in_stock_num']; //入库数量
                            $data[$k]['price'] = $v['price']; //退货入库采购单单价
                            $data[$k]['in_stock_id'] = $this->_in_stock->id;
                            if ($type_id == 3) {
                                $data[$k]['price'] = $v['price'];
                            }

                            //入库单绑定条形码数组组装
                            foreach ($v['sku_agg'] as $k_code => $v_code) {
                                if (!empty($v_code['code'])) {
                                    $where_code[] = $v_code['code'];
                                    $where_code_sku[$v_code['code']] = $v['sku'];
                                }
                            }
                        }

                        //入库单绑定条形码执行
                        if ($where_code) {
                            $save_code_data = [];
                            if ($type_id == 3) { //退货入库绑定sku和商品条形码
                                foreach ($where_code_sku as $key => $value) {
                                    $save_code_data['in_stock_id'] = $this->_in_stock->id;
                                    $save_code_data['sku'] = $where_code_sku[$key];
                                    $save_code_data['location_code'] = $warehouse_area['coding']; //绑定条形码与库位号
                                    $save_code_data['location_id'] = $area['id']; //绑定条形码与库区id
                                    $this->_product_bar_code_item->where(['code' => $key])->update($save_code_data);
                                }
                            } else {
                                $save_code_data['in_stock_id'] = $this->_in_stock->id;
                                $save_code_data['location_code'] = $warehouse_area['coding']; //绑定条形码与库位号
                                $save_code_data['location_id'] = $area['id']; //绑定条形码与库区id
                                $this->_product_bar_code_item->allowField(true)->isUpdate(true, ['code' => ['in', $where_code]])->save($save_code_data);
                            }
                        }

                        //批量添加
                        $this->_in_stock_item->allowField(true)->saveAll($data);
                    }
                    //                    $purchase_id = 0;//无采购单id
                } else {
                    //无站点，是质检单入口
                    $check_order_number = $this->request->request("check_order_number");
                    if (empty($check_order_number)) {
                        throw new Exception('质检单号不能为空');
                    }
                    $check_info = $this->_check->where(['check_order_number' => $check_order_number])->field('id,purchase_id,replenish_id')->find();
                    if (empty($check_info)) {
                        throw new Exception('质检单不存在');
                    }

                    //获取质检单子表数据
                    $check_data = $this->_check_item
                        ->where('check_id', $check_info['id'])
                        ->column('sample_num', 'sku');

                    $params['check_id'] = $check_info['id'];
                    $params['replenish_id'] = $check_info['replenish_id']; //补货单ID
                    $purchase_id = $check_info['purchase_id']; //有采购单id
                    //质检单页面去创建入库单
                    $result = $this->_in_stock->allowField(true)->save($params);
                    //添加入库信息
                    if ($result !== false) {
                        //更改质检单为已创建入库单
                        $this->_check->allowField(true)->save(['is_stock' => 1], ['id' => $params['check_id']]);
                        $data = [];
                        $where_code = [];
                        foreach (array_filter($item_sku) as $k => $v) {
                            $data[$k]['sku'] = $v['sku'];
                            $data[$k]['purchase_id'] = $purchase_id; //采购单id
                            $data[$k]['in_stock_num'] = $v['in_stock_num']; //入库数量
                            $data[$k]['price'] = $v['price']; //退货入库采购单价
                            $data[$k]['in_stock_id'] = $this->_in_stock->id; //入库单ID
                            $data[$k]['sample_num'] = $check_data[$v['sku']] ?: 0; //留样数量

                            //入库单绑定条形码数组组装
                            foreach ($v['sku_agg'] as $k_code => $v_code) {
                                if (!empty($v_code['code'])) {
                                    $where_code[] = $v_code['code'];
                                }
                            }
                        }

                        //入库单绑定条形码执行
                        if ($where_code) {
                            $this->_product_bar_code_item
                                ->allowField(true)
                                ->isUpdate(true, ['code' => ['in', $where_code]])
                                ->save(['in_stock_id' => $this->_in_stock->id, 'location_code' => $warehouse_area['coding'], 'location_id' => $area['id']]); //绑定条形码与库位号//绑定条形码与库区id
                        }

                        //批量添加
                        $this->_in_stock_item->allowField(true)->saveAll($data);
                    }
                }
            }
            $this->_check->commit();
            $this->_in_stock->commit();
            $this->_in_stock_item->commit();
            $this->_product_bar_code_item->commit();
        } catch (ValidateException $e) {
            $this->_check->rollback();
            $this->_in_stock->rollback();
            $this->_in_stock_item->rollback();
            $this->_product_bar_code_item->rollback();
            $this->error($e->getMessage(), [], 444);
        } catch (PDOException $e) {
            $this->_check->rollback();
            $this->_in_stock->rollback();
            $this->_in_stock_item->rollback();
            $this->_product_bar_code_item->rollback();
            $this->error($e->getMessage(), [], 444);
        } catch (Exception $e) {
            $this->_check->rollback();
            $this->_in_stock->rollback();
            $this->_in_stock_item->rollback();
            $this->_product_bar_code_item->rollback();
            $this->error($e->getMessage(), [], 444);
        }

        if ($result !== false) {
            $this->success($msg . '成功！！', '', 200);
        } else {
            $this->error(__($msg . '失败'), [], 511);
        }
    }

    //生成退货入库采购单质检单
    public function generate_purchase_check($item_sku)
    {
        $gen_check = new \app\admin\model\warehouse\Check;
        $gen_check_item = new \app\admin\model\warehouse\CheckItem;
        $gen_purchase_order = new \app\admin\model\purchase\PurchaseOrder;
        $gen_purchase_order_item = new \app\admin\model\purchase\PurchaseOrderItem;

        foreach ($item_sku as $key => $value) {
            //计算总金额
            $all_total = $value['price'] * $value['in_stock_num'];
            //生成采购单
            $purchase_number = 'PO' . date('YmdHis') . rand(100, 999) . rand(100, 999);
            $purchase_data = ['purchase_number' => $purchase_number, 'purchase_name' => '退货入库', 'purchase_status' => 10, 'check_status' => 2, 'is_in_stock' => 1, 'stock_status' => 2, 'createtime' => date('Y-m-d H:i:s'), 'product_total' => $all_total, 'purchase_total' => $all_total];

            $purchase = $gen_purchase_order->insertGetId($purchase_data);
            //生成质检单
            $check_order_number = 'QC' . date('YmdHis') . rand(100, 999) . rand(100, 999);
            $check_data = ['check_order_number' => $check_order_number, 'type' => 2, 'purchase_id' => $purchase, 'status' => 2, 'is_in_stock' => 1, 'is_stock' => 1, 'createtime' => date('Y-m-d H:i:s')];
            $check = $gen_check->insertGetId($check_data);

            //生成子数据
            $check_item_data = ['check_id' => $check, 'sku' => $value['sku'], 'purchase_num' => $value['in_stock_num'], 'check_num' => $value['in_stock_num'], 'purchase_id' => $purchase];
            $gen_check_item->insert($check_item_data);
            $purchase_order_item_data = ['purchase_id' => $purchase, 'sku' => $value['sku'], 'purchase_order_number' => $value['in_stock_num'], 'purchase_num' => $value['in_stock_num'], 'purchase_price' => $value['price'], 'purchase_total' => $value['price'] * $value['in_stock_num'], 'instock_num' => $value['in_stock_num']];
            $gen_purchase_order_item->insert($purchase_order_item_data);
        }

        return ['purchase_id' => $purchase, 'check_id' => $check];
    }

    /**
     * 新建入库单页面--ok
     *
     * @参数 int type  新建入口 1.质检单，2.入库单
     * @参数 int check_id  质检单ID（type为1时必填，为2时不填）
     * @return mixed
     * @author wgj
     */
    public function in_stock_add()
    {
        //根据type值判断是从哪个入口进入的添加入库单 type值为1是从质检入口进入 type值为2是从入库单直接添加 直接添加的需要选择站点
        $type = $this->request->request("type");
        empty($type) && $this->error(__('入口类型不能为空'), [], 513);
        $info = [];
        //入库单所需数据
        $info['in_stock_number'] = 'IN' . date('YmdHis') . rand(100, 999) . rand(100, 999);
        //查询入库分类
        $in_stock_type = $this->_in_stock_type->field('id, name')->where('is_del', 1)->select();
        if ($type == 1) {
            //质检单页面进入创建入库单
            $check_id = $this->request->request("check_id");
            empty($check_id) && $this->error(__('质检单号不能为空'), [], 513);
            $check_data = $this->_check->get($check_id);
            empty($check_data) && $this->error(__('质检单不存在'), [], 403);
            //入库单所需数据
            $info['check_id'] = $check_id;
            $info['check_order_number'] = $check_data['check_order_number'];
            $info['check_order_number'] = $check_data['check_order_number'];
            //有关联质检单ID，则入库类型只取第一条数据：采购入库
            //            $in_stock_type = $in_stock_type[0];
            $in_stock_type_list[] = $in_stock_type[0];

            //获取质检单商品数据
            $item_list = $this->_check_item
                ->where(['check_id' => $check_id])
                ->field('sku,quantity_num,sample_num,arrivals_num')
                ->select();
            $item_list = collection($item_list)->toArray();
            //获取条形码数据
            $bar_code_list = $this->_product_bar_code_item
                ->where(['check_id' => $check_id, 'is_sample' => 0])
                ->field('sku,code')
                ->order('id', 'desc')
                ->select();
            $bar_code_list = collection($bar_code_list)->toArray();
            //拼接sku条形码数据
            foreach ($item_list as $key => $value) {
                $sku = $value['sku'];
                //条形码列表
                $sku_agg = [];
                foreach ($bar_code_list as $k => $v) {
                    if ($v['sku'] == $sku) {
                        $v['is_new'] = 0;
                        $sku_agg[] = $v;
                    }
                }
                $item_list[$key]['sku_agg'] = $sku_agg;
                //质检单默认留样数量为1，入库数量为质检合格数量 - 留样数量
                $item_list[$key]['in_stock_num'] = $value['quantity_num'] - $value['sample_num'];
                $item_list[$key]['remark'] = $this->_check_item->where('check_id', $check_id)->value('remark');
            }
            $info['item_list'] = $item_list;
        } else {
            //入库单直接添加，查询站点数据
            $platform_list = $this->_magento_platform->field('id, name')->where(['is_del' => 1, 'status' => 1])->select();
            $info['platform_list'] = $platform_list;
            $in_stock_type_list = $in_stock_type;
        }

        $info['in_stock_type'] = $in_stock_type_list;

        $this->success('', ['info' => $info], 200);
    }

    /**
     * 编辑入库单页面/详情/入库单审核页面--只允许编辑入库分类和SKU入库数量--去除了质检合格数量--ok.
     * 修改编辑页面可更改
     *
     * @参数 int in_stock_id  入库单ID
     * @return mixed
     * @author wgj
     */
    public function in_stock_edit()
    {
        $in_stock_id = $this->request->request('in_stock_id');
        empty($in_stock_id) && $this->error(__('入库单ID不能为空'), [], 514);
        //获取入库单数据
        $_in_stock_info = $this->_in_stock->get($in_stock_id);
        empty($_in_stock_info) && $this->error(__('入库单不存在'), [], 515);

        $item_list = $this->_in_stock_item
            ->where(['in_stock_id' => $in_stock_id])
            ->field('id,sku,in_stock_num,price')
            ->select();
        empty($item_list) && $this->error(__('入库单子单数据异常'), [], 515);

        $item_list = collection($item_list)->toArray();
        $check_order_info = $this->_check->get($_in_stock_info['check_id']);
        //查询入库分类
        $in_stock_type = $this->_in_stock_type->field('id, name')->where('is_del', 1)->select();
        //获取条形码数据
        $bar_code_list = $this->_product_bar_code_item
            ->where(['in_stock_id' => $in_stock_id])
            ->field('sku,code')
            ->order('id', 'desc')
            ->select();
        $bar_code_list = collection($bar_code_list)->toArray();

        foreach ($item_list as $key => $value) {
            $sku = $value['sku'];
            //条形码列表
            $sku_agg = [];
            foreach ($bar_code_list as $k => $v) {
                if ($v['sku'] == $sku) {
                    $v['is_new'] = 0;
                    $sku_agg[] = $v;
                }
            }
            $item_list[$key]['sku_agg'] = $sku_agg;
        }

        $info = [];
        if ($check_order_info && $_in_stock_info['type_id'] != 3) {
            //存在质检单号，则入库类型只取第一条数据：采购入库
            $in_stock_type_list[] = $in_stock_type[0];
            foreach ($item_list as $key => $value) {
                //质检单默认留样数量为1，质检合格数量为入库数量 + 留样数量
                $item_list[$key]['quantity_num'] = $value['in_stock_num'] + $value['sample_num'];
                //从质检单子表获得应到货数量
                $item_list[$key]['arrivals_num'] = $this->_check_item->where('check_id', $check_order_info['id'])->value('arrivals_num');
                $item_list[$key]['remark'] = $this->_check_item->where('check_id', $check_order_info['id'])->value('remark');
            }
            $info['check_order_number'] = $check_order_info['check_order_number'];
        } else {
            $platform_list = $this->_magento_platform->field('id, name')->where(['is_del' => 1, 'status' => 1])->select();
            $info['platform_check_id'] = $_in_stock_info['platform_id'];
            $info['platform_list'] = $platform_list;
            $in_stock_type_list = $in_stock_type;
        }

        //入库单所需数据
        $info['in_stock_id'] = $_in_stock_info['id'];
        $info['in_stock_number'] = $_in_stock_info['in_stock_number'];
        $info['in_stock_type_check_id'] = $_in_stock_info['type_id'];
        $info['in_stock_type'] = $in_stock_type_list;
        $info['item_list'] = $item_list;

        $this->success('', ['info' => $info], 200);
    }

    //入库单编辑删除sku
    public function in_stock_edit_delete_sku()
    {
        $in_stock_item_id = $this->request->request('in_stock_item_id');
        empty($in_stock_item_id) && $this->error(__('入库单子项ID不能为空'), [], 514);
        $instock_id = $this->_in_stock_item
            ->where(['id' => $in_stock_item_id])
            ->find();
        $all_item_count = $this->_in_stock_item
            ->where(['in_stock_id' => $instock_id['instock_id']])
            ->count();
        if ($all_item_count <= 1) {
            $this->success('子单数据不能全部为空，请先添加再删除', '', 515);
        }
        $this->_in_stock_item->startTrans();
        $this->_product_bar_code_item->startTrans();
        try {
            //删除入库单子表的数据
            $item_list = $this->_in_stock_item
                ->where(['id' => $in_stock_item_id])
                ->delete();
            //入库单子表的sku与条形码有绑定 先解除绑定
            $barcode = $this->_product_bar_code_item
                ->where(['in_stock_id' => $instock_id['instock_id'], 'sku' => $instock_id['sku']])
                ->update(['in_stock_id' => 0, 'location_code' => '', 'location_id' => '']); //解除条形码与库位号的绑定关系
            $this->_in_stock_item->commit();
            $this->_product_bar_code_item->commit();
        } catch (ValidateException $e) {
            $this->_in_stock_item->rollback();
            $this->_product_bar_code_item->rollback();
            $this->error($e->getMessage(), [], 4441);
        } catch (PDOException $e) {
            $this->_in_stock_item->rollback();
            $this->_product_bar_code_item->rollback();
            $this->error($e->getMessage(), [], 4442);
        } catch (Exception $e) {
            $this->_in_stock_item->rollback();
            $this->_product_bar_code_item->rollback();
            $this->error($e->getMessage(), [], 4443);
        }
        if ($item_list && $barcode) {
            $this->success('删除成功', '', 200);
        } else {
            $this->success('删除失败', '', 515);
        }
    }

    /**
     * 入库审核 通过/拒绝--ok
     *
     * @参数 int check_id  入库单ID
     * @参数 int do_type  2审核通过，3审核拒绝
     * @return mixed
     * @author wgj
     */
    public function in_stock_examine()
    {
        /*****************限制如果有盘点单未结束不能操作入库单审核*******************/
        $count = $this->_inventory->where(['is_del' => 1, 'check_status' => ['in', [0, 1]]])->count();
        if ($count > 0) {
            $this->error(__('存在正在盘点的单据,暂无法审核'), [], 403);
        }
        /****************************end*****************************************/

        $in_stock_id = $this->request->request('in_stock_id');
        empty($in_stock_id) && $this->error(__('入库单ID不能为空'), [], 516);

        $do_type = $this->request->request('do_type');
        empty($do_type) && $this->error(__('审核类型不能为空'), [], 517);
        !in_array($do_type, [2, 3]) && $this->error(__('审核类型错误'), [], 517);

        //检测入库单状态
        $row = $this->_in_stock->get($in_stock_id);
        empty($row) && $this->error(__('入库单不存在'), [], 516);
        1 != $row['status'] && $this->error(__('只有待审核状态才能操作'), [], 518);

        $data['status'] = $do_type; //审核状态，2通过，3拒绝
        if (2 == $data['status']) {
            $data['check_time'] = date('Y-m-d H:i:s', time());
            $msg = '审核';
        } else {
            $msg = '拒绝';
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
                $this->error('此sku:' . $v['sku'] . '不存在！！', [], 516);
            }
        }

        $res = false;
        $this->_item->startTrans();
        $this->_in_stock->startTrans();
        $this->_stock_log->startTrans();
        $this->_allocated->startTrans();
        $this->_order_return->startTrans();
        $this->_purchase_order->startTrans();
        $this->_item_platform_sku->startTrans();
        $this->_purchase_order_item->startTrans();
        (new StockLog())->startTrans();
        try {
            $data['create_person'] = $this->auth->nickname;
            $res = $this->_in_stock->allowField(true)->isUpdate(true, ['id' => $in_stock_id])->save($data); //审核拒绝不更新数据

            if ($data['status'] == 2) {
                $error_num = [];
                foreach ($list as $k => $v) {
                    $item_map['sku'] = $v['sku'];
                    $item_map['is_del'] = 1;
                    $sku_item = $this->_item->where($item_map)->find();
                    //审核通过对虚拟库存的操作
                    //审核通过时按照补货需求比例 划分各站虚拟库存 如果未关联补货需求单则按照当前各站虚拟库存数量实时计算各站比例（弃用）
                    //采购过来的 有采购单的 1、有补货需求单的直接按比例分配 2、没有补货需求单的都给m站
                    if ($v['purchase_id']) {
                        //采购入库
                        $is_purchase = 10;
                        if ($v['replenish_id']) {
                            //采购有比例入库
                            $change_type = 16;
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
                            //获得应到货数量
                            $check = new \app\admin\model\warehouse\CheckItem();
                            $should_arrivals_num = $check->where('check_id', $v['check_id'])->value('should_arrival_num');
                            foreach ($rate_arr as $key => $val) {
                                //最后一个站点 剩余数量分给最后一个站
                                if (($all_num - $key) == 1) {
                                    //当前sku映射关系详情
                                    $sku_platform = $this->_item_platform_sku->where(['sku' => $v['sku'], 'platform_type' => $val['website_type']])->find();
                                    //如果站点是Z站 且虚拟仓库存为0
                                    if ($val['website_type'] == 1) {
                                        if ($sku_platform['stock'] == 0 && $stock_num > 0) {
                                            $value['sku'] = $sku_platform['platform_sku'];
                                            $url = config('url.zeelool_url') . 'magic/product/productArrival';
                                            $this->submission_post($url, $value);
                                        }
                                    }
                                    //增加站点虚拟仓库存
                                    $this->_item_platform_sku->where(['sku' => $v['sku'], 'platform_type' => $val['website_type']])->setInc('stock', $stock_num);
                                    //入库的时候减少待入库数量
                                    $this->_item_platform_sku->where(['sku' => $v['sku'], 'platform_type' => $val['website_type']])->setDec('wait_instock_num', $should_arrivals_num);

                                    //插入日志表
                                    (new StockLog())->setData([
                                        'type' => 2,
                                        'site' => $val['website_type'],
                                        'modular' => 10,
                                        'change_type' => 16,
                                        'sku' => $v['sku'],
                                        'order_number' => $v['in_stock_number'],
                                        'source' => 2,
                                        'fictitious_before' => $sku_platform['stock'],
                                        'fictitious_change' => $stock_num,
                                        'wait_instock_num_before' => $sku_platform['wait_instock_num'],
                                        'wait_instock_num_change' => -$should_arrivals_num,
                                        'create_person' => $this->auth->nickname,
                                        'create_time' => time(),
                                        'number_type' => 3,
                                    ]);
                                } else {
                                    $num = round($v['in_stock_num'] * $val['rate']);
                                    $should_arrivals_num_plat = round($should_arrivals_num * $val['rate']);
                                    $stock_num -= $num;
                                    $should_arrivals_num -= $should_arrivals_num_plat;
                                    $sku_platform = $this->_item_platform_sku->where(['sku' => $v['sku'], 'platform_type' => $val['website_type']])->find();
                                    if ($val['website_type'] == 1) {
                                        if ($sku_platform['stock'] == 0 && $num > 0) {
                                            $value['sku'] = $sku_platform['platform_sku'];
                                            $url = config('url.zeelool_url') . 'magic/product/productArrival';
                                            $this->submission_post($url, $value);
                                        }
                                    }
                                    //增加站点虚拟仓库存
                                    $this->_item_platform_sku->where(['sku' => $v['sku'], 'platform_type' => $val['website_type']])->setInc('stock', $num);
                                    //入库的时候减少待入库数量
                                    $this->_item_platform_sku->where(['sku' => $v['sku'], 'platform_type' => $val['website_type']])->setDec('wait_instock_num', $should_arrivals_num_plat);
                                    //插入日志表
                                    (new StockLog())->setData([
                                        'type' => 2,
                                        'site' => $val['website_type'],
                                        'modular' => 10,
                                        'change_type' => 16,
                                        'sku' => $v['sku'],
                                        'order_number' => $v['in_stock_number'],
                                        'source' => 2,
                                        'fictitious_before' => $sku_platform['stock'],
                                        'fictitious_change' => $num,
                                        'wait_instock_num_before' => $sku_platform['wait_instock_num'],
                                        'wait_instock_num_change' => -$should_arrivals_num_plat,
                                        'create_person' => $this->auth->nickname,
                                        'create_time' => time(),
                                        'number_type' => 3,
                                    ]);
                                }
                            }
                        } else {
                            //采购没有比例入库
                            $change_type = 17;

                            //记录没有采购比例直接入库的sku
                            $this->_allocated
                                ->allowField(true)
                                ->isUpdate(false)
                                ->data(['sku' => $v['sku'], 'change_num' => $v['in_stock_num'], 'create_time' => date('Y-m-d H:i:s')])
                                ->save();

                            $item_platform_sku = $this->_item_platform_sku->where(['sku' => $v['sku'], 'platform_type' => 4])->find();
                            //sku没有同步meeloog站 无法添加虚拟库存 必须先同步
                            if (empty($item_platform_sku)) {
                                throw new Exception('sku：' . $v['sku'] . '没有同步meeloog站，请先同步');
                            }
                            $this->_item_platform_sku->where(['sku' => $v['sku'], 'platform_type' => 4])->setInc('stock', $v['in_stock_num']);

                            //入库的时候减少待入库数量
                            // $this->_item_platform_sku->where(['sku' => $v['sku'], 'platform_type' => 4])->setDec('wait_instock_num', $v['in_stock_num']);

                            //插入日志表
                            (new StockLog())->setData([
                                'type' => 2,
                                'site' => 4,
                                'modular' => 10,
                                'change_type' => 17,
                                'sku' => $v['sku'],
                                'order_number' => $v['in_stock_number'],
                                'source' => 2,
                                'fictitious_before' => $item_platform_sku['stock'],
                                'fictitious_change' => $v['in_stock_num'],
                                // 'wait_instock_num_before' => $item_platform_sku['wait_instock_num'],
                                // 'wait_instock_num_change' => -$v['in_stock_num'],
                                'create_person' => $this->auth->nickname,
                                'create_time' => time(),
                                'number_type' => 3,
                            ]);
                        }
                    } //不是采购过来的 如果有站点id 说明是指定增加此平台sku
                    elseif ($v['platform_id']) {
                        //手动入库
                        $change_type = 18;
                        //出入库
                        $is_purchase = 11;
                        $item_platform_sku = $this->_item_platform_sku->where(['sku' => $v['sku'], 'platform_type' => $v['platform_id']])->find();
                        $this->_item_platform_sku->where(['sku' => $v['sku'], 'platform_type' => $v['platform_id']])->setInc('stock', $v['in_stock_num']);
                        //退货入库生成采购单质检单
                        if ($v['type_id'] == 3 && $k == 0) {
                            $item_sku = $this->_in_stock_item->where(['in_stock_id' => $in_stock_id])->select();
                            $item_sku = collection($item_sku)->toArray();
                            $generate_purchase_check = $this->generate_purchase_check($item_sku);
                            //更新质检单信息
                            $this->_in_stock->allowField(true)->isUpdate(true, ['id' => $in_stock_id])->save(['check_id' => $generate_purchase_check['check_id']]);
                            $this->_product_bar_code_item
                                ->allowField(true)
                                ->isUpdate(true, ['in_stock_id' => $in_stock_id])
                                ->save(['check_id' => $generate_purchase_check['check_id'], 'purchase_id' => $generate_purchase_check['purchase_id']]);
                        }
                        (new StockLog())->setData([
                            'type' => 2,
                            'site' => $v['platform_id'],
                            'modular' => 11,
                            'change_type' => 18,
                            'sku' => $v['sku'],
                            'order_number' => $v['in_stock_number'],
                            'source' => 2,
                            'fictitious_before' => $item_platform_sku['stock'],
                            'fictitious_change' => $v['in_stock_num'],
                            'create_person' => $this->auth->nickname,
                            'create_time' => time(),
                            'number_type' => 3,
                        ]);

                        //                        if ($v['platform_id'] ==1 && $v['type_id'] !== 3 && $k == 0){
                        //                            \Think\Log::write("第三次");
                        //                            \Think\Log::write($item_platform_sku);
                        //                            if ($item_platform_sku['stock'] == 0  && $v['in_stock_num'] > 0){
                        //                                $value['sku'] = $item_platform_sku['platform_sku'];
                        //                                $url  =  config('url.zeelool_url').'magic/product/productArrival';
                        //                                $this->submission_post($url,$value);
                        //                            }
                        //                        }

                    } //没有采购单也没有站点id 说明是盘点过来的
                    else {
                        //盘点
                        $change_type = 20;
                        //盘点
                        $is_purchase = 12;
                        //根据当前sku 和当前 各站的虚拟库存进行分配
                        $item_platform_sku = $this->_item_platform_sku->where('sku', $v['sku'])->order('stock asc')->select();
                        $all_num = count($item_platform_sku);

                        $stock_num = $v['in_stock_num'];
                        //计算当前sku的总虚拟库存 如果总的为0 表示当前所有平台的此sku都为0 此时入库的话按照平均规则分配 例如五个站都有此品 那么比例就是20%
                        $stock_all_num = array_sum(array_column($item_platform_sku, 'stock'));
                        if ($stock_all_num == 0) {
                            $rate_rate = 1 / $all_num;
                            foreach ($item_platform_sku as $key => $val) {
                                $item_platform_sku_detail = $this->_item_platform_sku->where(['sku' => $v['sku'], 'platform_type' => $val['platform_type']])->find();

                                //                                if ($val['platform_type'] ==1){
                                //                                    \Think\Log::write("第四次");
                                //                                    \Think\Log::write($item_platform_sku_detail);
                                //                                    if ($item_platform_sku_detail['stock'] ==0 && $stock_num >0 ){
                                //                                        $value['sku'] = $item_platform_sku_detail['platform_sku'];
                                //                                        $url  =  config('url.zeelool_url').'magic/product/productArrival';
                                //                                        $this->submission_post($url,$value);
                                //                                    }
                                //                                }
                                //最后一个站点 剩余数量分给最后一个站
                                if (($all_num - $key) == 1) {
                                    $this->_item_platform_sku->where(['sku' => $v['sku'], 'platform_type' => $val['platform_type']])->setInc('stock', $stock_num);
                                    //插入日志表
                                    (new StockLog())->setData([
                                        'type' => 2,
                                        'site' => $val['platform_type'],
                                        'modular' => 12,
                                        'change_type' => 20,
                                        'sku' => $v['sku'],
                                        'order_number' => $v['in_stock_number'],
                                        'source' => 2,
                                        'fictitious_before' => $item_platform_sku_detail['stock'],
                                        'fictitious_change' => $stock_num,
                                        'create_person' => $this->auth->nickname,
                                        'create_time' => time(),
                                        'number_type' => 3,
                                    ]);
                                } else {
                                    $num = round($v['in_stock_num'] * $rate_rate);
                                    $stock_num -= $num;
                                    $this->_item_platform_sku->where(['sku' => $v['sku'], 'platform_type' => $val['platform_type']])->setInc('stock', $num);
                                    //插入日志表
                                    (new StockLog())->setData([
                                        'type' => 2,
                                        'site' => $val['platform_type'],
                                        'modular' => 12,
                                        'change_type' => 20,
                                        'sku' => $v['sku'],
                                        'order_number' => $v['in_stock_number'],
                                        'source' => 2,
                                        'fictitious_before' => $item_platform_sku_detail['stock'],
                                        'fictitious_change' => $num,
                                        'create_person' => $this->auth->nickname,
                                        'create_time' => time(),
                                        'number_type' => 3,
                                    ]);
                                }
                            }
                        } else {
                            //某個平台這個sku存在庫存 就按照當前各站的虛擬庫存進行分配
                            $whole_num = $this->_item_platform_sku->where('sku', $v['sku'])->sum('stock');
                            $stock_num = $v['in_stock_num'];
                            foreach ($item_platform_sku as $key => $val) {
                                $item_platform_sku_detail = $this->_item_platform_sku->where(['sku' => $v['sku'], 'platform_type' => $val['platform_type']])->find();
                                //最后一个站点 剩余数量分给最后一个站
                                //                                if ($val['platform_type'] ==1){
                                //                                    \Think\Log::write("第五次");
                                //                                    \Think\Log::write($item_platform_sku_detail);
                                //                                    if ($item_platform_sku_detail['stock'] ==0 && $stock_num >0 ){
                                //                                        $value['sku'] = $item_platform_sku_detail['platform_sku'];
                                //                                        $url  =  config('url.zeelool_url').'magic/product/productArrival';
                                //                                        $this->submission_post($url,$value);
                                //                                    }
                                //                                }

                                if (($all_num - $key) == 1) {
                                    $this->_item_platform_sku->where(['sku' => $v['sku'], 'platform_type' => $val['platform_type']])->setInc('stock', $stock_num);
                                    //插入日志表
                                    (new StockLog())->setData([
                                        'type' => 2,
                                        'site' => $val['platform_type'],
                                        'modular' => 12,
                                        'change_type' => 20,
                                        'sku' => $v['sku'],
                                        'order_number' => $v['in_stock_number'],
                                        'source' => 2,
                                        'fictitious_before' => $item_platform_sku_detail['stock'],
                                        'fictitious_change' => $stock_num,
                                        'create_person' => $this->auth->nickname,
                                        'create_time' => time(),
                                        'number_type' => 3,
                                    ]);
                                } else {
                                    $num = round($v['in_stock_num'] * $val['stock'] / $whole_num);
                                    $stock_num -= $num;
                                    $this->_item_platform_sku->where(['sku' => $v['sku'], 'platform_type' => $val['platform_type']])->setInc('stock', $num);
                                    //插入日志表
                                    (new StockLog())->setData([
                                        'type' => 2,
                                        'site' => $val['platform_type'],
                                        'modular' => 12,
                                        'change_type' => 20,
                                        'sku' => $v['sku'],
                                        'order_number' => $v['in_stock_number'],
                                        'source' => 2,
                                        'fictitious_before' => $item_platform_sku_detail['stock'],
                                        'fictitious_change' => $num,
                                        'create_person' => $this->auth->nickname,
                                        'create_time' => time(),
                                        'number_type' => 3,
                                    ]);
                                }
                            }
                        }
                    }

                    //更新商品表商品总库存
                    //总库存
                    $item_map['sku'] = $v['sku'];
                    $item_map['is_del'] = 1;
                    $stock_res = false;
                    if ($v['sku']) {
                        //增加商品表里的商品库存、可用库存、留样库存
                        $stock_res = $this->_item->where($item_map)->inc('stock', $v['in_stock_num'])->inc('available_stock', $v['in_stock_num'])->inc('sample_num', $v['sample_num'])->update();

                        //有采购单减少待入库数量
                        if ($v['purchase_id']) {
                            //获得应到货数量
                            $check = new \app\admin\model\warehouse\CheckItem();
                            $should_arrivals_num = $check->where('check_id', $v['check_id'])->value('should_arrival_num');
                            if (!$should_arrivals_num) {
                                $should_arrivals_num = $check->where('check_id', $v['check_id'])->value('purchase_num');
                            }
                            $this->_item->where($item_map)->dec('wait_instock_num', $should_arrivals_num)->update();
                        }

                        //插入日志表
                        (new StockLog())->setData([
                            'type' => 2,
                            'site' => 0,
                            'modular' => $is_purchase,
                            'change_type' => $change_type,
                            'sku' => $v['sku'],
                            'order_number' => $v['in_stock_number'],
                            'source' => 2,
                            'stock_before' => $sku_item['stock'],
                            'stock_change' => $v['in_stock_num'],
                            'available_stock_before' => $sku_item['available_stock'],
                            'available_stock_change' => $v['in_stock_num'],
                            'sample_num_before' => $sku_item['sample_num'],
                            'sample_num_change' => $v['sample_num'],
                            'wait_instock_num_before' => $sku_item['wait_instock_num'],
                            'wait_instock_num_change' => -$should_arrivals_num,
                            'create_person' => $this->auth->nickname,
                            'create_time' => time(),
                            'number_type' => 3,
                        ]);
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
                        $this->_purchase_order->where(['id' => $check_res['purchase_id']])->update($purchase_data);
                    }
                    //如果为退货单 修改退货单状态为入库
                    if ($check_res['order_return_id']) {
                        $this->_order_return->where(['id' => $check_res['order_return_id']])->update(['in_stock_status' => 1]);
                    }
                }

                //有错误 则回滚数据
                if (count($error_num) > 0) {
                    throw new Exception('入库失败！！请检查SKU');
                }

                //条形码入库时间
                $this->_product_bar_code_item
                    ->allowField(true)
                    ->isUpdate(true, ['in_stock_id' => $in_stock_id])
                    ->save(['in_stock_time' => date('Y-m-d H:i:s')]);
            } else {
                //审核拒绝解除条形码绑定关系
                $this->_product_bar_code_item
                    ->allowField(true)
                    ->isUpdate(true, ['in_stock_id' => $in_stock_id])
                    ->save(['in_stock_id' => 0, 'location_code' => '', 'location_id' => '']); //解除条形码与库位号的绑定关系
            }

            $this->_item->commit();
            $this->_in_stock->commit();
            $this->_stock_log->commit();
            $this->_allocated->commit();
            $this->_order_return->commit();
            $this->_purchase_order->commit();
            $this->_item_platform_sku->commit();
            $this->_purchase_order_item->commit();
            (new StockLog())->commit();
        } catch (ValidateException $e) {
            $this->_item->rollback();
            $this->_in_stock->rollback();
            $this->_stock_log->rollback();
            $this->_allocated->rollback();
            $this->_order_return->rollback();
            $this->_purchase_order->rollback();
            $this->_item_platform_sku->rollback();
            $this->_purchase_order_item->rollback();
            (new StockLog())->rollback();
            $this->error($e->getMessage(), [], 4441);
        } catch (PDOException $e) {
            $this->_item->rollback();
            $this->_in_stock->rollback();
            $this->_stock_log->rollback();
            $this->_allocated->rollback();
            $this->_order_return->rollback();
            $this->_purchase_order->rollback();
            $this->_item_platform_sku->rollback();
            $this->_purchase_order_item->rollback();
            (new StockLog())->rollback();
            $this->error($e->getMessage(), [], 4442);
        } catch (Exception $e) {
            $this->_item->rollback();
            $this->_in_stock->rollback();
            $this->_stock_log->rollback();
            $this->_allocated->rollback();
            $this->_order_return->rollback();
            $this->_purchase_order->rollback();
            $this->_item_platform_sku->rollback();
            $this->_purchase_order_item->rollback();
            (new StockLog())->rollback();
            $this->error($e->getMessage(), [], 4443);
        }

        if ($res !== false) {
            $this->success($msg . '成功', [], 200);
        } else {
            $this->error(__($msg . '失败'), [], 519);
        }
    }


    /**
     * post方式请求接口
     *
     * @Description
     * @author zjw
     * @since 
     * @return
     */
    function submission_post($url, $value)
    {
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_USERAGENT, $_SERVER['HTTP_USER_AGENT']);
        curl_setopt($curl, CURLOPT_HEADER, 0);
        curl_setopt($curl, CURLOPT_POST, true);
        curl_setopt($curl, CURLOPT_POSTFIELDS, $value);
        curl_setopt($curl, CURLOPT_FOLLOWLOCATION, 1);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_TIMEOUT, 20);
        $content = json_decode(curl_exec($curl), true);
        curl_close($curl);
        return $content;
    }


    /***************************************盘点单******************************************/
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
     * @return mixed
     * @author wgj
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
        if ($query) {
            $where['a.number|b.sku|a.create_person'] = ['like', '%' . $query . '%'];
        }
        if (isset($status)) {
            $where['a.status'] = $status;
        }
        if ($check_status) {
            $where['a.check_status'] = $check_status;
        }
        if ($start_time && $end_time) {
            $where['a.createtime'] = ['between', [$start_time, $end_time]];
        }

        $offset = ($page - 1) * $page_size;
        $limit = $page_size;

        //获取入库单列表数据
        $list = $this->_inventory
            ->alias('a')
            ->where($where)
            ->field('a.id,a.number,a.createtime,a.status,a.check_status')
            ->join(['fa_inventory_item' => 'b'], 'a.id=b.inventory_id', 'left')
            ->group('a.id')
            ->order('a.createtime', 'desc')
            ->limit($offset, $limit)
            ->select();
        $list = collection($list)->toArray();

        $check_status = [0 => '新建', 1 => '待审核', 2 => '已审核', 3 => '已拒绝', 4 => '已取消'];
        foreach ($list as $key => $value) {
            unset($list[$key]['status']);
            $list[$key]['check_status'] = $check_status[$value['check_status']];
            //按钮
            $list[$key]['show_start'] = 0 == $value['status'] ? 1 : 0; //开始盘点按钮
            $list[$key]['show_continue'] = 1 == $value['status'] ? 1 : 0; //继续盘点按钮
            $list[$key]['show_examine'] = 2 == $value['status'] && 1 == $value['check_status'] ? 1 : 0; //审核按钮
            $list[$key]['show_detail'] = in_array($value['check_status'], [2, 3, 4]) ? 1 : 0; //详情按钮
            //计算已盘点数量
            $count = $this->_inventory_item->where(['inventory_id' => $value['id']])->count();
            $sum = $this->_inventory_item->where(['inventory_id' => $value['id'], 'is_add' => 0])->count();

            //查询盘点的库区
            $warehouse_name = $this->_inventory_item->where(['inventory_id' => $value['id']])->group('warehouse_name')->column('warehouse_name');

            $list[$key]['sum_count'] = $sum . '/' . $count; //需要fa_inventory_item表数据加和
            $list[$key]['warehouse_name'] = implode(',', $warehouse_name); //拼接库区
        }

        $this->success('', ['list' => $list], 200);
    }

    /**
     * 创建盘点单页面/筛选/保存  版本2.0
     *
     * @参数 int type  新建入口 1.筛选，2.保存
     * @参数 json item_sku  sku集合
     * @return mixed
     * @author wgj
     */
    public function inventory_add()
    {
        //根据type值判断是筛选还是保存 type值为1是筛选 type值为2是保存
        $type = $this->request->request("type") ?? 1;
        $info = [];
        if ($type == 1) {
            //创建盘点单筛选 ok
            $query = $this->request->request('query'); //sku 、 库位编码筛选
            $area_name = $this->request->request('area_name'); //库区编码 筛选
            $start_stock = $this->request->request('start_stock');
            $end_stock = $this->request->request('end_stock');
            $page = $this->request->request('page');
            $page_size = $this->request->request('page_size');
            empty($page) && $this->error(__('Page can not be empty'), [], 522);
            empty($page_size) && $this->error(__('Page size can not be empty'), [], 523);

            //排除待盘点sku
            $sku_arr = $this->_inventory_item->where('is_add', 0)->column('sku');
            if ($sku_arr) {
                $where['sku'] = ['not in', $sku_arr];
            }

            //库存范围
            if ($start_stock && $end_stock) {
                $item_where = [
                    'is_open' => ['in', [1, 2]]
                ];
                $item_where['stock'] = ['between', [$start_stock, $end_stock]];
                $item_sku = $this->_item
                    ->where($item_where)
                    ->column('sku');
                $where['a.sku'] = ['in', $item_sku];
            }

            //库区编码筛选
            if ($area_name) {
                $where['c.coding'] = ['like', '%' . $area_name . '%']; //库区编码
            }

            //库位筛选 sku筛选
            if ($query) {
                $where['a.sku|b.coding'] = ['like', '%' . $query . '%']; //coding库位编码，library_name库位名称
            }

            $where['a.is_del'] = 1;
            $where['b.status'] = 1;
            $where['b.type'] = 1;

            $offset = ($page - 1) * $page_size;
            $limit = $page_size;

            //获取SKU库位绑定表（fa_store_sku）数据列表
            $list = $this->_store_sku
                ->alias('a')
                ->field('a.id,a.sku,b.coding as library_name,c.coding as warehouse_name,b.area_id')
                ->where($where)
                ->join(['fa_store_house' => 'b'], 'a.store_id=b.id')
                ->join(['fa_warehouse_area' => 'c'], 'b.area_id=c.id')
                ->order('a.id', 'desc')
                ->limit($offset, $limit)
                ->select();
            $list = collection($list)->toArray();

            //盘点单所需数据
            $info['list'] = !empty($list) ? $list : [];
            $this->success('', ['info' => $info], 200);
        } else {
            //点击保存，创建盘点单
            //继续写
            $item_sku = $this->request->request("item_sku");
            empty($item_sku) && $this->error(__('sku集合不能为空！！'), [], 523);
            $item_sku = html_entity_decode($item_sku);
            $item_sku = array_filter(json_decode($item_sku, true));
            if (count(array_filter($item_sku)) < 1) {
                $this->error(__('sku集合不能为空！！'), [], 524);
            }
            $no_sku = [];
            foreach ($item_sku as $k => $v) {
                $item_id = $this->_item->where('sku', $v['sku'])->value('id');
                if (!$item_id) {
                    $no_sku[] = $v['sku'];
                }
            }
            if ($no_sku) $this->error(__('SKU：' . implode(',', $no_sku) . '不存在'), [], 523);

            $result = false;
            $this->_inventory->startTrans();
            $this->_inventory_item->startTrans();
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
                        $item = $this->_item->field('name,stock,available_stock,distribution_occupy_stock')->where('sku', $v['sku'])->find();
                        if (empty($item)) {
                            $this->error(__($v['sku'] . '不存在'), [], 525);
                        }

                        $list[$k]['name'] = $item['name']; //商品名
                        $list[$k]['distribution_occupy_stock'] = $item['distribution_occupy_stock'] ?? 0; //配货占用数量

                        // //查询库位实时库存
                        // $real_time_qty = $this->_product_bar_code_item->where(['location_id' => $v['area_id'], 'location_code' => $v['library_name'], 'library_status' => 1])->count();
                        // $list[$k]['real_time_qty'] = $real_time_qty; //库区实时库存
                        $list[$k]['available_stock'] = $item['available_stock'] ?? 0; //可用库存
                        $list[$k]['remark'] = $v['remark']; //备注
                        $list[$k]['warehouse_name'] = $v['warehouse_name']; //库区编码
                        $list[$k]['area_id'] = $v['area_id']; //库区id
                        $list[$k]['library_name'] = $v['library_name']; //库位编码
                    }

                    //添加明细表数据
                    $result = $this->_inventory_item->allowField(true)->saveAll($list);
                }

                $this->_inventory->commit();
                $this->_inventory_item->commit();
            } catch (ValidateException $e) {
                $this->_inventory->rollback();
                $this->_inventory_item->rollback();
                $this->error($e->getMessage(), [], 444);
            } catch (PDOException $e) {
                $this->_inventory->rollback();
                $this->_inventory_item->rollback();
                $this->error($e->getMessage(), [], 444);
            } catch (Exception $e) {
                $this->_inventory->rollback();
                $this->_inventory_item->rollback();
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
     * @return mixed
     * @author wgj
     */
    public function inventory_edit()
    {
        $inventory_id = $this->request->request('inventory_id');
        empty($inventory_id) && $this->error(__('盘点单ID不能为空'), [], 530);
        //获取盘点单数据
        $_inventory_info = $this->_inventory->get($inventory_id);
        empty($_inventory_info) && $this->error(__('盘点单不存在'), [], 531);
        //        $inventory_item_info = $_inventory_item->field('id,sku,inventory_qty,error_qty,real_time_qty,available_stock,distribution_occupy_stock')->where(['inventory_id'=>$inventory_id])->select();

        $inventory_item_info = $this->_inventory_item
            ->field('id,sku,inventory_qty,error_qty,real_time_qty,available_stock,distribution_occupy_stock,warehouse_name,area_id,library_name,sku_agg')
            ->where(['inventory_id' => $inventory_id])
            ->order('id', 'desc')
            ->select();
        $item_list = collection($inventory_item_info)->toArray();

        //获取条形码数据
        // $bar_code_list = $this->_product_bar_code_item
        //     ->where(['inventory_id' => $inventory_id])
        //     ->field('sku,code')
        //     ->select();
        // $bar_code_list = collection($bar_code_list)->toArray();

        foreach (array_filter($item_list) as $key => $value) {
            $item_list[$key]['stock'] = $this->_item->where('sku', $value['sku'])->value('stock');
            //            $stock = $this->_item->where('sku',$value['sku'])->value('stock');
            // $sku = $value['sku'];
            //条形码列表
            // $sku_agg = array_filter($bar_code_list, function ($v) use ($sku) {
            //     if ($v['sku'] == $sku) {
            //         return $v;
            //     }
            // });

            // if (!empty($sku_agg)) {
            //     array_walk($sku_agg, function (&$value, $k, $p) {
            //         $value = array_merge($value, $p);
            //     }, ['is_new' => 0]);
            // }

            $item_list[$key]['sku_agg'] = unserialize($value['sku_agg']) ?: [];
        }

        //盘点单所需数据
        $info = [
            'inventory_id' => $_inventory_info['id'],
            'inventory_number' => $_inventory_info['number'],
            //            'status'=>$_inventory_info['status'],
            'item_list' => !empty($item_list) ? $item_list : []
        ];

        $this->success('', ['info' => $info], 200);
    }

    /**
     * 开始盘点页面，保存/提交--ok
     *
     * @参数 int inventory_id  盘点单ID
     * @参数 int do_type  提交类型 1提交-盘点结束 2保存-盘点中
     * @参数 json item_sku  sku数据集合
     * @return mixed
     * @author wgj
     */
    public function inventory_submit()
    {
        $do_type = $this->request->request('do_type');
        $item_sku = $this->request->request("item_data");
        empty($item_sku) && $this->error(__('sku集合不能为空！！'), [], 508);
        $item_sku = json_decode(htmlspecialchars_decode($item_sku), true);
        empty($item_sku) && $this->error(__('sku集合不能为空'), [], 403);
        $item_sku = array_filter($item_sku);

        $inventory_id = $this->request->request("inventory_id");
        empty($inventory_id) && $this->error(__('盘点单号不能为空'), [], 541);
        //获取盘点单数据
        $row = $this->_inventory->get($inventory_id);
        empty($row) && $this->error(__('盘点单不存在'), [], 543);
        if ($row['status'] > 1) {
            $this->error(__('此状态不能编辑'), [], 544);
        }
        // $item_row = $this->_inventory_item
        //     ->where('inventory_id', $inventory_id)
        //     ->column('real_time_qty', 'sku');

        if ($do_type == 1) {
            //提交
            $params['status'] = 2; //盘点完成
            $params['end_time'] = date('Y-m-d H:i:s', time());
            $is_add = 1; //更新为盘点
            $msg = '提交';
        } else {
            //保存
            $is_add = 0; //未盘点
            $params['status'] = 1;
            $msg = '保存';
        }

        //检测条形码是否已绑定
        foreach (array_filter($item_sku) as $key => $value) {
            /*$info_id = $this->_inventory_item->where(['sku' => $value['sku'],'is_add'=>0,'inventory_id'=>['neq',$inventory_id]])->column('id');
            !empty($info_id) && $this->error(__('SKU=>'.$value['sku'].'存在未完成的盘点单'), [], 543);*/
            // 
            // if (count($value['sku_agg']) != count(array_unique($sku_code))) $this->error(__('条形码有重复，请检查'), [], 405);

            $sku_code = array_column($value['sku_agg'], 'code');
            //查询条形码是否已绑定过sku
            $where = [];
            $where['code'] = ['in', array_unique($sku_code)];
            $where['sku'] = ['<>', $value['sku']];
            $code = $this->_product_bar_code_item
                ->where($where)
                ->column('code');
            if ($code) {
                $this->error(__('条形码:' . implode(',', $code) . ' 已绑定,请移除'), [], 405);
                exit;
            }
        }

        //保存不需要编辑盘点单
        //编辑盘点单明细item
        $result = false;
        $this->_inventory_item->startTrans();
        $this->_product_bar_code_item->startTrans();
        try {
            //更新数据
            //提交盘点单状态为已完成，保存盘点单状态为盘点中
            $result = $this->_inventory->allowField(true)->save($params, ['id' => $inventory_id]);
            if ($result !== false) {
                // $where_code = [];
                // $sku_in = [];
                foreach (array_filter($item_sku) as $k => $v) {
                    $item_map['sku'] = $v['sku'];
                    $item_map['is_del'] = 1;
                    $sku_item = $this->_item->where($item_map)->field('stock,available_stock,distribution_occupy_stock')->find();
                    if (empty($sku_item)) {
                        throw new Exception('SKU=>' . $v['sku'] . '不存在');
                    }

                    //查询库位实时库存
                    $real_time_qty = $this->_product_bar_code_item->where(['location_id' => $v['area_id'], 'location_code' => $v['library_name'], 'library_status' => 1])->where("item_order_number=''")->count();

                    $save_data = [];
                    $save_data['is_add'] = $is_add; //是否盘点
                    $save_data['inventory_qty'] = $v['inventory_qty'] ?? 0; //盘点数量
                    $save_data['error_qty'] = $save_data['inventory_qty'] - $real_time_qty; //误差数量
                    $save_data['remark'] = $v['remark']; //备注
                    $save_data['real_time_qty'] = $real_time_qty; //实时库存即为库位实时库存
                    $count = $this->_inventory_item->where(['inventory_id' => $inventory_id, 'sku' => $v['sku']])->count();
                    $save_data['sku_agg'] = serialize($v['sku_agg']); //SKU 条形码集合 
                    $save_data['remove_agg'] = serialize($v['remove_agg']); //SKU需移除的条形码集合
                    if ($count > 0) {
                        $save_data['inventory_id'] = $inventory_id; //SKU
                        $save_data['sku'] = $v['sku']; //SKU
                        $this->_inventory_item->allowField(true)->isUpdate(false)->data($save_data)->save();
                    } else {
                        $this->_inventory_item->where(['inventory_id' => $inventory_id, 'sku' => $v['sku']])->update($save_data);
                    }
                    //                    $this->_inventory_item->where(['inventory_id' => $inventory_id, 'sku' => $v['sku']])->update($save_data);
                    // //盘点单绑定条形码数组组装
                    // foreach ($v['sku_agg'] as $k_code => $v_code) {
                    //     if (!empty($v_code)) {
                    //         $where_code[] = $v_code['code'];
                    //     }
                    // }
                    // //盘点单移除条形码
                    // if (!empty($v['remove_agg'])) {
                    //     $code_clear = [
                    //         'inventory_id' => 0
                    //     ];
                    //     $this->_product_bar_code_item->where(['code' => ['in', $v['remove_agg']]])->update($code_clear);
                    // }
                }

                // //盘点单绑定条形码执行
                // if ($where_code) {
                //     $this->_product_bar_code_item
                //         ->allowField(true)
                //         ->isUpdate(true, ['code' => ['in', $where_code]])
                //         ->save(['inventory_id' => $inventory_id]);
                // }
            }
            $this->_inventory_item->commit();
            $this->_product_bar_code_item->commit();
        } catch (ValidateException $e) {
            $this->_inventory_item->rollback();
            $this->_product_bar_code_item->rollback();
            $this->error($e->getMessage(), [], 444);
        } catch (PDOException $e) {
            $this->_inventory_item->rollback();
            $this->_product_bar_code_item->rollback();
            $this->error($e->getMessage(), [], 444);
        } catch (Exception $e) {
            $this->_inventory_item->rollback();
            $this->_product_bar_code_item->rollback();
            $this->error($e->getMessage(), [], 444);
        }

        if ($result !== false) {
            $this->success($msg . '成功！！', '', 200);
        } else {
            $this->error(__($msg . '失败'), [], 511);
        }
    }

    /**
     * 审核盘点单
     *
     * @参数 int inventory_id  盘点单ID
     * @参数 int do_type  审核类型 2通过-盘点结束-更改状态-创建入库单-盘盈加库存、盘亏扣减库存; 3拒绝-盘点结束-更改状态
     * @return mixed
     * @author wgj
     */
    public function inventory_examine()
    {
        $do_type = $this->request->request('do_type');

        $inventory_id = $this->request->request("inventory_id");
        empty($inventory_id) && $this->error(__('盘点单号不能为空'), [], 545);
        //获取盘点单数据
        $row = $this->_inventory->get($inventory_id);
        empty($row) && $this->error(__('盘点单不存在'), [], 546);
        !in_array($row['check_status'], [1, 2]) && $this->error(__('只有待审核、已完成状态才能操作'), [], 547);
        $data['check_time'] = date('Y-m-d H:i:s', time());
        $data['check_person'] = $this->auth->nickname;

        $msg = '';
        if (3 == $do_type) {
            $data['check_status'] = 3;
            $this->_inventory->allowField(true)->save($data, ['id' => $inventory_id]);
            $msg = '操作成功';
        } else {
            $data['check_status'] = 2;
        }

        $this->_item->startTrans();
        $this->_in_stock->startTrans();
        $this->_out_stock->startTrans();
        $this->_inventory->startTrans();
        $this->_stock_log->startTrans();
        $this->_in_stock_item->startTrans();
        $this->_out_stock_item->startTrans();
        $this->_item_platform_sku->startTrans();
        (new StockLog())->startTrans();
        try {
            $res = $this->_inventory->allowField(true)->isUpdate(true, ['id' => $inventory_id])->save($data);

            //审核通过 生成出、入库单 并同步库存
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
                    //同步对应SKU库存 更新商品表商品总库存 总库存
                    $item_map['sku'] = $v['sku'];
                    $item_map['is_del'] = 1;
                    $sku_item = $this->_item->where($item_map)->field('stock,available_stock,sample_num,wait_instock_num,occupy_stock,distribution_occupy_stock')->find();
                    if ($v['sku']) {
                        $stock = $this->_item->where($item_map)->inc('stock', $v['error_qty'])->inc('available_stock', $v['error_qty'])->update();
                        //插入日志表
                        (new StockLog())->setData([
                            'type' => 2,
                            'site' => 0,
                            'modular' => 12,
                            'change_type' => $v['error_qty'] > 0 ? 20 : 21,
                            'sku' => $v['sku'],
                            'order_number' => $v['inventory_id'],
                            'source' => 2,
                            'stock_before' => $sku_item['stock'],
                            'stock_change' => $v['error_qty'],
                            'available_stock_before' => $sku_item['available_stock'],
                            'available_stock_change' => $v['error_qty'],
                            'create_person' => $this->auth->nickname,
                            'create_time' => time(),
                            'number_type' => 5,
                        ]);
                        //盘点的时候盘盈入库 盘亏出库 的同时要对虚拟库存进行一定的操作
                        //查出映射表中此sku对应的所有平台sku 并根据库存数量进行排序（用于遍历数据的时候首先分配到那个站点）
                        $item_platform_sku = $this->_item_platform_sku->where('sku', $v['sku'])->order('stock asc')->field('platform_type,stock')->select();
                        $all_num = count($item_platform_sku);
                        $whole_num = $this->_item_platform_sku
                            ->where('sku', $v['sku'])
                            ->field('stock')
                            ->select();
                        $num_num = 0;
                        foreach ($whole_num as $kk => $vv) {
                            $num_num += abs($vv['stock']);
                        }
                        //盘盈或者盘亏的数量 根据此数量对平台sku虚拟库存进行操作
                        $stock_num = $v['error_qty'];
                        //计算当前sku的总虚拟库存 如果总的为0 表示当前所有平台的此sku都为0 此时入库的话按照平均规则分配 例如五个站都有此品 那么比例就是20%
                        $stock_all_num = array_sum(array_column($item_platform_sku, 'stock'));
                        if ($stock_all_num == 0) {
                            $rate_rate = 1 / $all_num;
                            foreach ($item_platform_sku as $key => $val) {
                                //最后一个站点 剩余数量分给最后一个站
                                if (($all_num - $key) == 1) {
                                    $item_platform_sku_detail = $this->_item_platform_sku->where(['sku' => $v['sku'], 'platform_type' => $val['platform_type']])->find();
                                    $this->_item_platform_sku->where(['sku' => $v['sku'], 'platform_type' => $val['platform_type']])->inc('stock', $stock_num)->update();
                                    //插入日志表
                                    (new StockLog())->setData([
                                        'type' => 2,
                                        'site' => $val['platform_type'],
                                        'modular' => 12,
                                        'change_type' => $v['error_qty'] > 0 ? 20 : 21,
                                        'sku' => $v['sku'],
                                        'order_number' => $v['inventory_id'],
                                        'source' => 2,
                                        'fictitious_before' => $item_platform_sku_detail['stock'],
                                        'fictitious_change' => $stock_num,
                                        'create_person' => $this->auth->nickname,
                                        'create_time' => time(),
                                        'number_type' => 5,
                                    ]);
                                } else {
                                    $num = round($v['error_qty'] * $rate_rate);
                                    $stock_num -= $num;
                                    $item_platform_sku_detail = $this->_item_platform_sku->where(['sku' => $v['sku'], 'platform_type' => $val['platform_type']])->find();
                                    $this->_item_platform_sku->where(['sku' => $v['sku'], 'platform_type' => $val['platform_type']])->inc('stock', $num)->update();
                                    //插入日志表
                                    (new StockLog())->setData([
                                        'type' => 2,
                                        'site' => $val['platform_type'],
                                        'modular' => 12,
                                        'change_type' => $v['error_qty'] > 0 ? 20 : 21,
                                        'sku' => $v['sku'],
                                        'order_number' => $v['inventory_id'],
                                        'source' => 2,
                                        'fictitious_before' => $item_platform_sku_detail['stock'],
                                        'fictitious_change' => $num,
                                        'create_person' => $this->auth->nickname,
                                        'create_time' => time(),
                                        'number_type' => 5,
                                    ]);
                                }
                            }
                        } else {
                            foreach ($item_platform_sku as $key => $val) {
                                //最后一个站点 剩余数量分给最后一个站
                                if (($all_num - $key) == 1) {
                                    $item_platform_sku_detail = $this->_item_platform_sku->where(['sku' => $v['sku'], 'platform_type' => $val['platform_type']])->find();
                                    $this->_item_platform_sku->where(['sku' => $v['sku'], 'platform_type' => $val['platform_type']])->inc('stock', $stock_num)->update();
                                    //插入日志表
                                    (new StockLog())->setData([
                                        'type' => 2,
                                        'site' => $val['platform_type'],
                                        'modular' => 12,
                                        'change_type' => $v['error_qty'] > 0 ? 20 : 21,
                                        'sku' => $v['sku'],
                                        'order_number' => $v['inventory_id'],
                                        'source' => 2,
                                        'fictitious_before' => $item_platform_sku_detail['stock'],
                                        'fictitious_change' => $stock_num,
                                        'create_person' => $this->auth->nickname,
                                        'create_time' => time(),
                                        'number_type' => 5,
                                    ]);
                                } else {
                                    $num = round($v['error_qty'] * abs($val['stock']) / $num_num);
                                    $stock_num -= $num;
                                    $item_platform_sku_detail = $this->_item_platform_sku->where(['sku' => $v['sku'], 'platform_type' => $val['platform_type']])->find();
                                    $this->_item_platform_sku->where(['sku' => $v['sku'], 'platform_type' => $val['platform_type']])->inc('stock', $num)->update();
                                    //插入日志表
                                    (new StockLog())->setData([
                                        'type' => 2,
                                        'site' => $val['platform_type'],
                                        'modular' => 12,
                                        'change_type' => $v['error_qty'] > 0 ? 20 : 21,
                                        'sku' => $v['sku'],
                                        'order_number' => $v['inventory_id'],
                                        'source' => 2,
                                        'fictitious_before' => $item_platform_sku_detail['stock'],
                                        'fictitious_change' => $num,
                                        'create_person' => $this->auth->nickname,
                                        'create_time' => time(),
                                        'number_type' => 5,
                                    ]);
                                }
                            }
                        }
                    }

                    //修改库存结果为真
                    if ($stock === false) {
                        throw new Exception('同步库存失败,请检查SKU=>' . $v['sku']);
                    }

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

                //重新绑定条形码
                if ($v['sku_agg']) {
                    $sku_code = array_unique(array_column(unserialize($v['sku_agg']), 'code'));
                    $this->_product_bar_code_item
                        ->where(['code' => ['in', $sku_code], 'location_code' => $v['library_name'], 'area_id' => $v['area_id'], 'sku' => $v['sku']])
                        ->update(['inventory_id' => $inventory_id, 'library_status' => 1]);

                    //不在此数组的条形码改为出库状态
                    $this->_product_bar_code_item
                        ->where(['code' => ['not in', $sku_code], 'location_code' => $v['library_name'], 'area_id' => $v['area_id'], 'sku' => $v['sku']])
                        ->update(['library_status' => 2]);
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

                    //更新如果入库单id为空 添加入库单id
                    if ($sku_code) {
                        $this->_product_bar_code_item
                            ->where(['code' => ['in', $sku_code], 'location_code' => $v['library_name'], 'area_id' => $v['area_id'], 'sku' => $v['sku'], 'in_stock_id' => 0])
                            ->update(['in_stock_id' => $this->_in_stock->id, 'in_stock_time' => date('Y-m-d H:i:s')]);
                    }

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
                        throw new Exception('生成入库记录失败！！数据回滚');
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

                    //更新如果出库单id为空 添加出库单id
                    if ($sku_code) {
                        $this->_product_bar_code_item
                            ->where(['code' => ['not in', $sku_code], 'location_code' => $v['library_name'], 'area_id' => $v['area_id'], 'sku' => $v['sku'], 'out_stock_id' => 0])
                            ->update(['out_stock_id' => $this->_out_stock->id, 'out_stock_time' => date('Y-m-d H:i:s')]);
                    }

                    //添加出库信息
                    if ($outstock_res !== false) {
                        $outstockItemList = array_values($list);
                        foreach ($outstockItemList as $k => $v) {
                            $outstockItemList[$k]['out_stock_id'] = $this->_out_stock->id;
                        }
                        //批量添加
                        $this->_out_stock_item->allowField(true)->saveAll($outstockItemList);
                    } else {
                        throw new Exception('生成出库记录失败！！数据回滚');
                    }
                }
            }

            // else {
            //     //审核拒绝 解除条形码绑定的盘点单号
            //     $code_clear = [
            //         'inventory_id' => 0
            //     ];
            //     $this->_product_bar_code_item->where(['inventory_id' => $inventory_id])->update($code_clear);
            // }
            $this->_item->commit();
            $this->_in_stock->commit();
            $this->_out_stock->commit();
            $this->_inventory->commit();
            $this->_stock_log->commit();
            $this->_in_stock_item->commit();
            $this->_out_stock_item->commit();
            $this->_item_platform_sku->commit();
            (new StockLog())->commit();
        } catch (ValidateException $e) {
            $this->_item->rollback();
            $this->_in_stock->rollback();
            $this->_out_stock->rollback();
            $this->_inventory->rollback();
            $this->_stock_log->rollback();
            $this->_in_stock_item->rollback();
            $this->_out_stock_item->rollback();
            $this->_item_platform_sku->rollback();
            (new StockLog())->rollback();
            $this->error($e->getMessage(), [], 443);
        } catch (PDOException $e) {
            $this->_item->rollback();
            $this->_in_stock->rollback();
            $this->_out_stock->rollback();
            $this->_inventory->rollback();
            $this->_stock_log->rollback();
            $this->_in_stock_item->rollback();
            $this->_out_stock_item->rollback();
            $this->_item_platform_sku->rollback();
            (new StockLog())->rollback();
            $this->error($e->getMessage(), [], 442);
        } catch (Exception $e) {
            $this->_item->rollback();
            $this->_in_stock->rollback();
            $this->_out_stock->rollback();
            $this->_inventory->rollback();
            $this->_stock_log->rollback();
            $this->_in_stock_item->rollback();
            $this->_out_stock_item->rollback();
            $this->_item_platform_sku->rollback();
            (new StockLog())->rollback();
            $this->error($e->getMessage(), [], 441);
        }
        if ($res) {
            $msg = '审核成功';
        }

        $this->success($msg, ['info' => ''], 200);
    }

    /**
     * 盘点时检查条形码绑定关系是否正确
     *
     * @Description
     * @author wpl
     * @since 2021/03/08 13:56:55 
     * @return void
     */
    public function check_code()
    {
        if ($this->request->isPost()) {
            $code = $this->request->request('code'); //条形码
            $sku = $this->request->request('sku'); //sku
            empty($code) && $this->error(__('条形码不能为空'), [], 405);
            empty($sku) && $this->error(__('sku不能为空'), [], 406);
            $list = $this->_product_bar_code_item->where(['code' => $code])->find();
            if ($list->sku && $list->sku != $sku) {
                $this->error('条形码和sku不匹配,条形码:' . $code, [], 402);
            }
            if ($list->library_status != 1) {
                $this->error('此条形码已出库,条形码:' . $code, [], 403);
            }
            $this->success('获取成功', [], 200);
        }
        $this->error('网络异常', [], 401);
    }

    /**
     * 获取库区
     *
     * @Description
     * @author wpl
     * @since 2021/03/03 09:14:36 
     * @return void
     */
    public function inventory_warehouse_area()
    {
        if ($this->request->isPost()) {
            $area_name = $this->request->request('area_name'); //库区名称
            $list = $this->_warehouse_area->getRowsData($area_name);
            $this->success('获取成功', $list, 200);
        }
        $this->error('网络异常', [], 401);
    }

    /**
     * 获取库位
     *
     * @Description
     * @author wpl
     * @since 2021/03/03 09:14:36 
     * @return void
     */
    public function inventory_warehouse_location()
    {
        if ($this->request->isPost()) {
            $area_id = $this->request->request('area_id'); //库区id 多个逗号拼接
            $coding = $this->request->request('coding'); //库位编码
            empty($area_id) && $this->error(__('库区id不能为空'), [], 403);
            $list = $this->_store_house->getLocationData($area_id, $coding);
            $this->success('获取成功', $list, 200);
        }
        $this->error('网络异常', [], 401);
    }

    /**
     * 根据库位获取对应SKU
     *
     * @Description
     * @author wpl
     * @since 2021/03/03 13:38:05 
     * @return void
     */
    public function inventory_location_sku()
    {
        if ($this->request->isPost()) {
            $location_id = $this->request->request('location_id'); //库位id 多个逗号拼接
            empty($location_id) && $this->error(__('库位id不能为空'), [], 403);
            $list = $this->_store_sku->getRowsData($location_id);
            $this->success('获取成功', $list, 200);
        }
        $this->error('网络异常', [], 401);
    }




    /***************************************end******************************************/


    //判断条形码是否绑定过sku
    public function is_empty_code()
    {
        $code = $this->request->request('code');
        empty($code) && $this->error(__('条形码不能为空'), [], 403);

        //检测条形码是否存在
        $check_quantity = $this->_product_bar_code_item
            ->field('code,sku')
            ->where(['code' => $code])
            ->find();
        !empty($check_quantity['sku']) && $this->error(__('条形码不可用'), [], 405);

        $this->success('扫码成功', [], 200);
    }
    /***************************************库内调拨单******************************************/
    /**
     * 库内调拨单列表--ok
     *
     * @参数 string query  查询内容
     * @参数 int status  状态
     * @参数 string start_time  开始时间
     * @参数 string end_time  结束时间
     * @参数 int page  * 页码
     * @参数 int page_size  * 每页显示数量
     * Created by Phpstorm.
     * User: jhh
     * Date: 2021/3/3
     * Time: 10:48:36
     */
    public function transfer_order_list()
    {
        $query = $this->request->request('query');
        $status = $this->request->request('status');
        $start_time = $this->request->request('start_time');
        $end_time = $this->request->request('end_time');
        $page = $this->request->request('page');
        $page_size = $this->request->request('page_size');

        empty($page) && $this->error(__('Page can not be empty'), [], 520);
        empty($page_size) && $this->error(__('Page size can not be empty'), [], 521);

        $where = [];
        if ($query) {
            $where['transfer_order_number|create_person'] = ['like', '%' . $query . '%'];
        }
        if (isset($status)) {
            $where['status'] = $status;
        }
        if ($start_time && $end_time) {
            $where['create_time'] = ['between', [$start_time, $end_time]];
        }

        $offset = ($page - 1) * $page_size;
        $limit = $page_size;

        //获取库内调拨单列表数据
        $list = $this->_warehouse_transfer_order
            ->where($where)
            ->order('create_time', 'desc')
            ->limit($offset, $limit)
            ->select();
        $list = collection($list)->toArray();
        $check_status = [0 => '新建', 1 => '待审核', 2 => '已审核', 3 => '已拒绝', 4 => '已取消', 5 => '调拨中', 6 => '已完成'];
        foreach ($list as $key => $value) {
            $list[$key]['status'] = $check_status[$value['status']];
            //按钮
            $list[$key]['show_start'] = 0 == $value['status'] ? 1 : 0; //编辑按钮
            $list[$key]['show_cancel'] = 0 == $value['status'] ? 1 : 0; //取消按钮
            $list[$key]['show_detail'] = 6 == $value['status'] ? 1 : 0; //详情按钮
            $list[$key]['show_trans'] = 5 == $value['status'] ? 1 : 0; //调拨中编辑按钮
        }
        $this->success('', ['list' => $list], 200);
    }

    /**
     * 创建库内调拨单页面/筛选/保存/编辑
     * Created by Phpstorm.
     * User: jhh
     * Date: 2021/3/3
     * Time: 15:54:44
     */
    public function transfer_order_add()
    {
        //库内调拨子单详情
        $item_sku = $this->request->request("item_sku");
        $number = $this->request->request("number");
        $type = $this->request->request("type");
        $id = $this->request->request("transfer_order_id");
        $item_sku = html_entity_decode($item_sku);
        $item_sku = array_filter(json_decode($item_sku, true));
        if (count(array_filter($item_sku)) < 1) {
            $this->error(__('调拨单子数据集合不能为空！！'), [], 524);
        }
        if (!empty($number) && !$id) {
            //有调拨单号没有调拨单id说明是第一次保存
            $result = false;
            $this->_warehouse_transfer_order->startTrans();
            $this->_warehouse_transfer_order_item->startTrans();
            try {
                //保存--创建库内调拨单
                $arr = [];
                $arr['transfer_order_number'] = $number;
                $arr['create_person'] = $this->auth->nickname;
                $arr['status'] = $type;
                $arr['create_time'] = date('Y-m-d H:i:s', time());
                $result = $this->_warehouse_transfer_order->allowField(true)->save($arr);
                if ($result) {
                    $list = [];
                    foreach ($item_sku as $k => $v) {
                        $list[$k]['transfer_order_id'] = $this->_warehouse_transfer_order->id;
                        $list[$k]['sku'] = $v['sku'];
                        $list[$k]['num'] = $v['num'];
                        $list[$k]['outarea'] = $v['outarea']; //调出库区
                        $list[$k]['call_out_site'] = $v['call_out_site']; //调出库位
                        $list[$k]['inarea'] = $v['inarea']; //调入库区
                        $list[$k]['call_in_site'] = $v['call_in_site']; //调入库位
                    }
                    //添加明细表数据
                    $result = $this->_warehouse_transfer_order_item->allowField(true)->saveAll($list);
                }
                $this->_warehouse_transfer_order->commit();
                $this->_warehouse_transfer_order_item->commit();
            } catch (ValidateException $e) {
                $this->_warehouse_transfer_order->rollback();
                $this->_warehouse_transfer_order_item->rollback();
                $this->error($e->getMessage(), [], 444);
            } catch (PDOException $e) {
                $this->_warehouse_transfer_order->rollback();
                $this->_warehouse_transfer_order_item->rollback();
                $this->error($e->getMessage(), [], 444);
            } catch (Exception $e) {
                $this->_warehouse_transfer_order->rollback();
                $this->_warehouse_transfer_order_item->rollback();
                $this->error($e->getMessage(), [], 444);
            }
            if ($result !== false) {
                $this->success('添加成功！！', '', 200);
            } else {
                $this->error(__('No rows were inserted'), [], 525);
            }
        } else if (empty($number) && $id) { //编辑调拨单
            empty($id) && $this->error(__('调拨单id不能为空！！'), [], 523);
            $warehouse_trans_order_detail = $this->_warehouse_transfer_order->where('id', $id)->find();
            $this->_warehouse_transfer_order->startTrans();
            $this->_warehouse_transfer_order_item->startTrans();
            try {
                //状态为5说明是调拨中 只能提交
                if ($warehouse_trans_order_detail['status'] == 5) {
                    //调拨中提交需要判断子调拨单是不是都已经完成了
                    $is_complete = $this->_warehouse_transfer_order_item->where('num', 0)->where('transfer_order_id', $id)->find();
                    if (!empty($is_complete)) {
                        $this->error(__('存在未调拨完成的子单，请调拨后重试'), [], 525);
                    }
                    $res = $this->_warehouse_transfer_order->where('id', $id)->update(['status' => 6]);
                    if ($res !== false) {
                        $this->success('提交成功！！', '', 200);
                    } else {
                        $this->error(__('No rows were inserted'), [], 525);
                    }
                } else {
                    //type为0是保存 1是提交
                    0 != $warehouse_trans_order_detail['status'] && $this->error(__('只有新建状态才能编辑'), [], 405);
                    $result = $this->_warehouse_transfer_order->where('id', $id)->update(['status' => $type]);
                    $results = $this->_warehouse_transfer_order_item->where('transfer_order_id', $id)->delete();
                    if ($results) {
                        $list = [];
                        foreach ($item_sku as $k => $v) {
                            $list[$k]['transfer_order_id'] = $id;
                            $list[$k]['sku'] = $v['sku'];
                            $list[$k]['num'] = $v['num'];
                            $list[$k]['outarea'] = $v['outarea']; //调出库区
                            $list[$k]['call_out_site'] = $v['call_out_site']; //调出库位
                            $list[$k]['inarea'] = $v['inarea']; //调入库区
                            $list[$k]['call_in_site'] = $v['call_in_site']; //调入库位
                        }
                        //添加明细表数据
                        $result1 = $this->_warehouse_transfer_order_item->allowField(true)->saveAll($list);
                    }
                }
                $this->_warehouse_transfer_order->commit();
                $this->_warehouse_transfer_order_item->commit();
            } catch (ValidateException $e) {
                $this->_warehouse_transfer_order->rollback();
                $this->_warehouse_transfer_order_item->rollback();
                $this->error($e->getMessage(), [], 444);
            } catch (PDOException $e) {
                $this->_warehouse_transfer_order->rollback();
                $this->_warehouse_transfer_order_item->rollback();
                $this->error($e->getMessage(), [], 444);
            } catch (Exception $e) {
                $this->_warehouse_transfer_order->rollback();
                $this->_warehouse_transfer_order_item->rollback();
                $this->error($e->getMessage(), [], 444);
            }
            if ($result !== false) {
                $this->success('提交成功！！', '', 200);
            } else {
                $this->error(__('No rows were inserted'), [], 525);
            }
        }
    }

    /**
     * 库内调拨单详情
     * Created by Phpstorm.
     * User: jhh
     * Date: 2021/3/4
     * Time: 13:41:11
     */
    public function transfer_order_detail()
    {
        $id = $this->request->request("transfer_order_id");
        empty($id) && $this->error(__('调拨单id不能为空！！'), [], 523);
        $list['transfer_order_number'] = $this->_warehouse_transfer_order->where('id', $id)->value('transfer_order_number');
        $list['item_list'] = $this->_warehouse_transfer_order_item->where('transfer_order_id', $id)->select();
        foreach ($list['item_list'] as $k => $v) {
            $area_id = Db::name('warehouse_area')->where('coding', $v['outarea'])->value('id');
            $store_id = Db::name('store_house')->where('coding', $v['call_out_site'])->where('area_id', $area_id)->value('id');
            $list['item_list'][$k]['skuid'] = $store_id;
        }
        $this->success('', ['info' => $list], 200);
    }

    /**
     * 调拨中提交子单 扫调出库位二维码-扫商品条形码-扫调入库位条形码
     * Created by Phpstorm.
     * User: jhh
     * Date: 2021/3/4
     * Time: 15:49:12
     */
    public function transfer_ing()
    {
        /*****************限制如果有盘点单未结束不能操作调拨单审核*******************/
        $count = $this->_inventory->where(['is_del' => 1, 'check_status' => ['in', [0, 1]]])->count();
        if ($count > 0) {
            $this->error(__('存在正在盘点的单据,暂无法审核'), [], 403);
        }

        /****************************end*****************************************/


        $id = $this->request->request("transfer_order_item_id");
        $transfer_order_item = $this->_warehouse_transfer_order_item->where('id', $id)->find();
        $area_all = $this->_warehouse_area->column('id', 'coding');
        $num = $this->request->request("num");
        $do_type = $this->request->request("do_type");
        $sku_agg = $this->request->request("sku_agg");
        $remove_agg = $this->request->request("remove_agg");
        $sku_agg = html_entity_decode($sku_agg);
        $sku_agg = array_filter(json_decode($sku_agg, true));
        if ($remove_agg) {
            $remove_agg = html_entity_decode($remove_agg);
            $remove_agg = array_filter(json_decode($remove_agg, true));
        }
        if (count(array_filter($sku_agg)) < 1) {
            $this->error(__('条形码数据不能为空！！'), [], 524);
        }
        $res = false;
        $this->_product_bar_code_item->startTrans();
        $this->_warehouse_transfer_order_item->startTrans();
        try {
            $this->_warehouse_transfer_order_item->where('id', $id)->update(['status' => $do_type]);
            foreach ($sku_agg as $k => $v) {
                //当前条形码详情
                $detail = $this->_product_bar_code_item->where('code', $v['code'])->find();
                if ($transfer_order_item['call_out_site'] != $detail['location_code']) {
                    $this->error(__('条形码' . $v['code'] . '当前未在调出库位！！' . $transfer_order_item['call_out_site']), [], 546);
                }
                //调入仓库位id
                $store_house_id = $this->_store_house->where(['coding' => $transfer_order_item['call_in_site'], 'area_id' => $area_all[$transfer_order_item['in_area']]])->value('id');
                //判断调入库位是否有sku跟库位的绑定关系
                $store_sku = $this->_store_sku->where('sku', $transfer_order_item['sku'])->where('store_id', $store_house_id)->find();
                empty($store_sku) && $this->error(__('调入库位' . $transfer_order_item['call_in_site'] . '与sku：' . $transfer_order_item['sku'] . '没有绑定关系！！'), [], 546);
                //更新条形码当前所属的库区库位
                $this->_product_bar_code_item->where(['code' => $v['code']])->update(['location_code' => $transfer_order_item['call_in_site'], 'location_id' => $area_all[$transfer_order_item['inarea']]]);
            }
            if ($remove_agg) {
                //调拨单移除条形码
                foreach ($remove_agg as $k => $v) {
                    //更新条形码当前所属的库区库位 移除条形码 此条形码应该再次回到调出的库区库位
                    $this->_product_bar_code_item->where('code', $v['code'])->update(['location_code' => $transfer_order_item['call_out_site'], 'location_id' => $area_all[$transfer_order_item['outarea']]]);
                }
            }
            //更新库内调拨单子单的调拨数量
            $res = $this->_warehouse_transfer_order_item->where('id', $id)->update(['num' => $num]);
            $this->_product_bar_code_item->commit();
            $this->_warehouse_transfer_order_item->commit();
        } catch (ValidateException $e) {
            $this->_product_bar_code_item->rollback();
            $this->_warehouse_transfer_order_item->rollback();
            $this->error($e->getMessage(), [], 444);
        } catch (PDOException $e) {
            $this->_product_bar_code_item->rollback();
            $this->_warehouse_transfer_order_item->rollback();
            $this->error($e->getMessage(), [], 444);
        } catch (Exception $e) {
            $this->_product_bar_code_item->rollback();
            $this->_warehouse_transfer_order_item->rollback();
            $this->error($e->getMessage(), [], 444);
        }
        if ($res !== false) {
            $this->success('调拨成功！！', '', 200);
        } else {
            $this->error(__('No rows were inserted'), [], 525);
        }
    }

    /**
     * 库内调拨单 出库记录
     * Created by Phpstorm.
     * User: jhh
     * Date: 2021/3/6
     * Time: 18:58:35
     */
    public function warehouse_item_history()
    {
        $transfer_order_item_id = $this->request->request('transfer_order_item_id');
        $sku_agg = $this->request->request("sku_agg");
        $do_type = $this->request->request("do_type");
        $remove_agg = $this->request->request("remove_agg");
        $sku_agg = html_entity_decode($sku_agg);
        $sku_agg = array_filter(json_decode($sku_agg, true));
        if ($remove_agg) {
            $remove_agg = html_entity_decode($remove_agg);
            $remove_agg = array_filter(json_decode($remove_agg, true));
        }
        if (count(array_filter($sku_agg)) < 1) {
            $this->error(__('条形码数据不能为空！！'), [], 524);
        }
        //当前调拨子单详情
        $transfer_order_item = Db::name('warehouse_transfer_order_item')->where('id', $transfer_order_item_id)->find();
        empty($transfer_order_item) && $this->error(__('调拨子单不存在'), [], 546);
        $transfer_order_item['status'] == 1 && $this->error(__('调拨子单已提交，不要重复提交'), [], 546);
        $res = false;
        $warehouse_transfer_order_item_code = new WarehouseTransferOrderItemCode();
        $warehouse_transfer_order_item_code->startTrans();
        $this->_warehouse_transfer_order_item->startTrans();
        try {
            if ($do_type == 1) {
                $this->_warehouse_transfer_order_item->where('id', $transfer_order_item_id)->update(['status' => $do_type]);
                $msg = '调拨成功';
            } else {
                $warehouse_transfer_order_item_code->where('transfer_order_item_id', $transfer_order_item_id)->delete();
                $msg = '保存成功';
            }
            //所有的库区编码
            $area_all = $this->_warehouse_area->column('id', 'coding');
            foreach ($sku_agg as $k => $v) {
                //当前条形码详情
                $detail = $this->_product_bar_code_item->where('code', $v['code'])->find();
                //判断调拨单子单sku是否与条形码sku一致
                if ($transfer_order_item['sku'] != $detail['sku']) {
                    $this->error(__('条形码' . $v['code'] . '与当前调拨单sku不一致请确认后重试'), [], 546);
                }
                //判断当前扫码的sku是否在当前调出的库位
                if ($transfer_order_item['call_out_site'] != $detail['location_code']) {
                    $this->error(__('条形码' . $v['code'] . '当前未在调出库位！！' . $transfer_order_item['call_out_site']), [], 546);
                }
                //调入仓库位id
                $store_house_id = $this->_store_house->where(['coding' => $transfer_order_item['call_in_site'], 'area_id' => $area_all[$transfer_order_item['inarea']]])->value('id');
                //判断调入库位是否有sku跟库位的绑定关系
                $store_sku = $this->_store_sku->where('sku', $transfer_order_item['sku'])->where('store_id', $store_house_id)->find();
                empty($store_sku) && $this->error(__('调入库位' . $transfer_order_item['call_in_site'] . '与sku：' . $transfer_order_item['sku'] . '没有绑定关系！！'), [], 546);
                $arr['transfer_order_item_id'] = $transfer_order_item_id;
                $arr['sku'] = $transfer_order_item['sku'];
                $arr['area'] = $transfer_order_item['outarea'];
                $arr['location'] = $transfer_order_item['call_out_site'];
                $arr['code'] = $v['code'];
                //生成一条子调拨单的出库记录
                $res = $warehouse_transfer_order_item_code->insert($arr);
            }
            if ($remove_agg) {
                //调拨单移除条形码
                foreach ($remove_agg as $k => $v) {
                    $warehouse_transfer_order_item_code->where('code', $v['code'])->where('transfer_order_item_id', $transfer_order_item_id)->delete();
                }
            }
            $warehouse_transfer_order_item_code->commit();
            $this->_warehouse_transfer_order_item->commit();
        } catch (ValidateException $e) {
            $warehouse_transfer_order_item_code->rollback();
            $this->_warehouse_transfer_order_item->rollback();
            $this->error($e->getMessage(), [], 444);
        } catch (PDOException $e) {
            $warehouse_transfer_order_item_code->rollback();
            $this->_warehouse_transfer_order_item->rollback();
            $this->error($e->getMessage(), [], 444);
        } catch (Exception $e) {
            $warehouse_transfer_order_item_code->rollback();
            $this->_warehouse_transfer_order_item->rollback();
            $this->error($e->getMessage(), [], 444);
        }
        if ($res !== false) {
            $this->success($msg, '', 200);
        } else {
            $this->error(__('No rows were inserted'), [], 525);
        }
    }

    /**
     * 调拨单入库提交
     * Created by Phpstorm.
     * User: jhh
     * Date: 2021/3/8
     * Time: 10:36:31
     */
    public function warehouse_in()
    {
        $transfer_order_item_id = $this->request->request('transfer_order_item_id');
        $transfer_order_item = $this->_warehouse_transfer_order_item->where('id', $transfer_order_item_id)->find();
        $area_all = $this->_warehouse_area->column('id', 'coding');
        $all_history = Db::name('warehouse_transfer_order_item_code')->where('transfer_order_item_id', $transfer_order_item_id)->select();
        //调入仓库位id
        $store_house_id = $this->_store_house->where(['coding' => $transfer_order_item['call_in_site'], 'area_id' => $area_all[$transfer_order_item['inarea']]])->value('id');
        //判断调入库位是否有sku跟库位的绑定关系
        $store_sku = $this->_store_sku->where('sku', $transfer_order_item['sku'])->where('store_id', $store_house_id)->find();
        empty($store_sku) && $this->error(__('调入库位' . $transfer_order_item['call_in_site'] . '与sku：' . $transfer_order_item['sku'] . '没有绑定关系！！'), [], 546);
        $this->_product_bar_code_item->startTrans();
        try {
            foreach ($all_history as $k => $v) {
                //更新条形码当前所属的库区库位
                $res = $this->_product_bar_code_item->where(['code' => $v['code']])->update(['location_code' => $transfer_order_item['call_in_site'], 'location_id' => $area_all[$transfer_order_item['inarea']]]);
            }
            $this->_product_bar_code_item->commit();
        } catch (ValidateException $e) {
            $this->_product_bar_code_item->rollback();
            $this->error($e->getMessage(), [], 444);
        } catch (PDOException $e) {
            $this->_product_bar_code_item->rollback();
            $this->error($e->getMessage(), [], 444);
        } catch (Exception $e) {
            $this->_product_bar_code_item->rollback();
            $this->error($e->getMessage(), [], 444);
        }
        if ($res !== false) {
            $this->success('子单入库成功', '', 200);
        } else {
            $this->error(__('No rows were inserted'), [], 525);
        }
    }

    /**
     *
     * Created by Phpstorm.
     * User: jhh
     * Date: 2021/3/8
     * Time: 13:46:36
     */
    public function transfer_order_item_detail()
    {
        $id = $this->request->request("transfer_order_item_id");
        empty($id) && $this->error(__('调拨单子单id不能为空！！'), [], 523);
        $list['transfer_order_item'] = $this->_warehouse_transfer_order_item->where('id', $id)->find();
        $list['item_list'] = Db::name('warehouse_transfer_order_item_code')->where('transfer_order_item_id', $id)->select();
        $this->success('', ['info' => $list], 200);
    }

    /**
     * 调出的时候校验条形码 子单 sku是否一致
     * Created by Phpstorm.
     * User: jhh
     * Date: 2021/3/8
     * Time: 10:08:24
     */
    public function code_review()
    {
        $code = $this->request->request("code");
        $location = $this->request->request("location");
        $transfer_order_item_id = $this->request->request('transfer_order_item_id');
        //当前调拨子单详情
        $transfer_order_item = Db::name('warehouse_transfer_order_item')->where('id', $transfer_order_item_id)->find();
        empty($transfer_order_item) && $this->error(__('调拨子单不存在'), [], 546);
        //当前条形码详情
        $detail = $this->_product_bar_code_item->where('code', $code)->find();
        //判断调拨单子单sku是否与条形码sku一致
        if ($transfer_order_item['sku'] != $detail['sku']) {
            $this->error(__('条形码' . $code . '与当前调拨子单sku不一致请确认后重试'), [], 546);
        }
        //判断当前扫码的sku是否在当前调出的库位
        if ($transfer_order_item['call_out_site'] != $detail['location_code']) {
            $this->error(__('条形码' . $code . '当前未在调出库位！！' . $transfer_order_item['call_out_site']), [], 546);
        }
        //判断当前扫码的sku是否在当前调出的库位
        if ($transfer_order_item['call_out_site'] != $location) {
            $this->error(__('库位' . $location . '与调拨单' . $transfer_order_item['call_out_site'] . '不一致'), [], 546);
        }
        $this->success('扫码成功！！', '', 200);
    }


    /**
     * //库区库位sku
     * Created by Phpstorm.
     * User: jhh
     * Date: 2021/3/4
     * Time: 10:21:30
     */
    public function area_warehouse_sku()
    {
        $area_id = $this->request->request("area_id");
        $store_id = $this->request->request("store_id");
        $sku = $this->request->request("sku");
        $where = [];
        if ($area_id) {
            $where['b.area_id'] = $area_id;
        }
        if ($store_id) {
            $where['b.id'] = $store_id;
        }
        if ($sku) {
            $where['a.sku'] = ['like', '%' . $sku . '%'];
        }
        $list = Db::name('store_sku')
            ->alias('a')
            ->join(['fa_store_house' => 'b'], 'a.store_id=b.id', 'left')
            ->join(['fa_warehouse_area' => 'c'], 'b.area_id=c.id')
            ->field('a.sku,b.coding,c.name,a.id')
            ->where('is_del', 1)
            ->where($where)
            ->select();

        $this->success('', ['list' => $list], 200);
    }

    /**
     * 审核库内调拨单
     * Created by Phpstorm.
     * User: jhh
     * Date: 2021/3/4
     * Time: 16:00:08
     */
    public function transfer_order_examine()
    {
        $transfer_order_id = $this->request->request('transfer_order_id');
        empty($transfer_order_id) && $this->error(__('库内调拨单ID不能为空'), [], 403);

        $do_type = $this->request->request('do_type');
        empty($do_type) && $this->error(__('审核类型不能为空'), [], 403);
        !in_array($do_type, [2, 3]) && $this->error(__('审核类型错误'), [], 403);

        //检测出库单状态
        $row = $this->_warehouse_transfer_order->where('id', $transfer_order_id)->find();
        1 != $row['status'] && $this->error(__('只有待审核状态才能审核'), [], 405);

        $res = $this->_warehouse_transfer_order->allowField(true)->isUpdate(true, ['id' => $transfer_order_id])->save(['status' => $do_type]);
        false === $res ? $this->error(__('审核失败'), [], 404) : $this->success('审核成功', [], 200);
    }

    /**
     * 取消
     * Created by Phpstorm.
     * User: jhh
     * Date: 2021/3/5
     * Time: 18:17:14
     */
    public function transfer_order_cancel()
    {
        $transfer_order_id = $this->request->request('transfer_order_id');
        empty($transfer_order_id) && $this->error(__('库内调拨单ID不能为空'), [], 403);

        //检测出库单状态
        $row = $this->_warehouse_transfer_order->where('id', $transfer_order_id)->find();
        0 != $row['status'] && $this->error(__('只有新建状态才能取消'), [], 405);

        $res = $this->_warehouse_transfer_order->allowField(true)->isUpdate(true, ['id' => $transfer_order_id])->save(['status' => 4]);
        false === $res ? $this->error(__('审核失败'), [], 404) : $this->success('审核成功', [], 200);
    }

    /***************************************库内调拨单end******************************************/
}
