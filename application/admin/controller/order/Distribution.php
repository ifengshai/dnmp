<?php

namespace app\admin\controller\order;

use app\admin\model\DistributionLog;
use app\admin\model\order\Order;
use app\admin\model\saleaftermanage\WorkOrderChangeSku;
use app\admin\model\saleaftermanage\WorkOrderList;
use app\admin\model\warehouse\Outstock;
use app\admin\model\warehouse\OutStockItem;
use app\common\controller\Backend;
use fast\Excel;
use Monolog\Handler\IFTTTHandler;
use think\Request;
use think\exception\PDOException;
use think\Exception;
use think\Loader;
use think\Db;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use app\admin\model\order\order\NewOrderItemProcess;
use app\admin\model\warehouse\StockHouse;
use app\admin\model\DistributionAbnormal;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use app\admin\model\order\order\NewOrder;
use app\admin\model\order\order\NewOrderItemOption;
use app\admin\model\itemmanage\ItemPlatformSku;
use app\admin\model\itemmanage\Item;
use app\admin\model\order\order\NewOrderProcess;
use app\admin\model\StockLog;
use app\admin\model\order\order\LensData;
use app\admin\model\saleaftermanage\WorkOrderMeasure;
use app\admin\model\warehouse\ProductBarCodeItem;
use GuzzleHttp\Client;
use app\admin\model\warehouse\Inventory;

/**
 * 配货列表
 */
class Distribution extends Backend
{
    protected $noNeedRight = [
        'orderDetail',
        'batch_print_label_new',
        'batch_export_xls',
        'account_order_batch_export_xls',
        'add',
        'detail',
        'operation_log'
    ];
    /**
     * 子订单模型对象
     * @var object
     * @access protected
     */
    protected $model = null;

    /**
     * 库位模型对象
     * @var object
     * @access protected
     */
    protected $_stock_house = null;

    /**
     * 配货异常模型对象
     * @var object
     * @access protected
     */
    protected $_distribution_abnormal = null;

    /**
     * 子订单处方模型对象
     * @var object
     * @access protected
     */
    protected $_new_order_item_option = null;

    /**
     * 主订单模型对象
     * @var object
     * @access protected
     */
    protected $_new_order = null;

    /**
     * 主订单状态模型对象
     * @var object
     * @access protected
     */
    protected $_new_order_process = null;

    /**
     * sku映射关系模型对象
     * @var object
     * @access protected
     */
    protected $_item_platform_sku = null;

    /**
     * 商品库存模型对象
     * @var object
     * @access protected
     */
    protected $_item = null;

    /**
     * 库存日志模型对象
     * @var object
     * @access protected
     */
    protected $_stock_log = null;

    /**
     * 镜片模型对象
     * @var object
     * @access protected
     */
    protected $_lens_data = null;

    /**
     * 工单模型对象
     * @var object
     * @access protected
     */
    protected $_work_order_list = null;

    /**
     * 工单措施模型对象
     * @var object
     * @access protected
     */
    protected $_work_order_measure = null;

    /**
     * 工单措施sku数据变动关联模型对象
     * @var object
     * @access protected
     */
    protected $_work_order_change_sku = null;

    /**
     * 商品条形码模型对象
     * @var object
     * @access protected
     */
    protected $_product_bar_code_item = null;

    public function _initialize()
    {
        parent::_initialize();
        $this->model = new NewOrderItemProcess();
        $this->_lens_data = new LensData();
        $this->_stock_house = new StockHouse();
        $this->_distribution_abnormal = new DistributionAbnormal();
        $this->_new_order = new NewOrder();
        $this->_new_order_item_option = new NewOrderItemOption();
        $this->_new_order_process = new NewOrderProcess();
        $this->_new_order_item_process = new NewOrderItemProcess();
        $this->_item_platform_sku = new ItemPlatformSku();
        $this->_item = new Item();
        $this->_stock_log = new StockLog();
        $this->_work_order_list = new WorkOrderList();
        $this->_work_order_measure = new WorkOrderMeasure();
        $this->_work_order_change_sku = new WorkOrderChangeSku();
        $this->_product_bar_code_item = new ProductBarCodeItem();
        $this->_outstock = new Outstock();
        $this->_outstock_item = new OutStockItem();
        $this->_inventory = new Inventory();
        $this->_wave_order = new \app\admin\model\order\order\WaveOrder();
    }

    /**
     * 列表
     */
    public function index()
    {
        $label = $this->request->get('label', 0);
        //设置过滤方法
        $this->request->filter(['strip_tags']);
        if ($this->request->isAjax()) {
            //如果发送的来源是Selectpage，则转发到Selectpage
            if ($this->request->request('keyField')) {
                return $this->selectpage();
            }

            $map = [];
            //普通状态剔除跟单数据
            if (!in_array($label, [0, 8])) {
                if (7 == $label) {
                    $map['a.distribution_status'] = [['>', 6], ['<', 9]];
                } else {
                    $map['a.distribution_status'] = $label;
                }
            }

            $filter = json_decode($this->request->get('filter'), true);
            //默认展示3个月内的数据
            if (!$filter) {
                $map['a.created_at'] = ['between', [strtotime('-3 month'), time()]];
            } else {
                if ($filter['a.created_at']) {
                    $time = explode(' - ', $filter['a.created_at']);
                    $map['a.created_at'] = ['between', [strtotime($time[0]), strtotime($time[1])]];
                }
            }

            //默认展示订单状态
            if ($filter) {
                if ($filter['status']) {
                    $map['b.status'] = ['in', $filter['status']];
                } else {
                    if ($label !== '0') {
                        $map['b.status'] = ['in', ['processing', 'paypal_reversed', 'paypal_canceled_reversal']];
                    }
                }
                unset($filter['status']);
            } else {
                $map['b.status'] = ['in', ['processing', 'paypal_reversed', 'paypal_canceled_reversal']];
                unset($filter['status']);
            }


            //查询子单ID合集
            $item_process_ids = [];

            //工单、异常筛选
            if ($filter['is_work_order']) {
                $is_work_order = $filter['is_work_order'];
                unset($filter['is_work_order']);
            }

            //跟单或筛选异常
            if ($filter['abnormal'] || 8 == $label) {
                //异常类型
                if ($filter['abnormal']) {
                    $abnormal_where['type'] = ['in', $filter['abnormal']];
                    unset($filter['abnormal']);
                }

                //获取未处理异常
                if (8 == $label) {
                    $abnormal_where['status'] = 1;
                }
                //获取异常的子订单id
                $item_process_ids = $this->_distribution_abnormal
                    ->where($abnormal_where)
                    ->column('item_process_id');

                if ($item_process_ids == null) {
                    $map['a.id'] = ['eq', null];
                }
            };

            //筛选货架号
            $sort_flag = 0;
            if ($filter['shelf_number']) {
                if (1 == $label) {
                    $shelf_number =
                        $this->_stock_house
                        ->alias('a')
                        ->join(['fa_store_sku' => 'b'], 'a.id=b.store_id')
                        ->where([
                            'a.shelf_number' => ['in', $filter['shelf_number']],
                            'a.type' => 1,
                            'a.status' => 1,
                            'b.is_del' => 1
                        ])
                        ->order('a.coding')
                        ->column('b.sku');

                    //平台SKU表替换sku
                    $sku = Db::connect('database.db_stock');
                    $sku_array = $sku->table('fa_item_platform_sku')->where(['sku' => ['in', $shelf_number]])->column('platform_sku');
                    $map['a.sku'] = ['in', $sku_array];
                    $sort_flag = 1;
                }
                unset($filter['shelf_number']);
            }

            //筛选库位号
            if ($filter['stock_house_num']) {
                if (8 == $label) { //跟单
                    $house_type = 4;
                } elseif (3 == $label) { //待配镜片-定制片
                    $house_type = 3;
                } else { //合单
                    $house_type = 2;
                }
                $stock_house_id = $this->_stock_house
                    ->where([
                        'coding' => ['like', $filter['stock_house_num'] . '%'],
                        'type' => $house_type
                    ])
                    ->column('id');
                //查询合单库位号
                if ($house_type == 2) {
                    if ($stock_house_id) {
                        $order_ids = $this->_new_order_process->where(['store_house_id' => ['in', $stock_house_id]])->column('order_id');
                        $map['a.order_id'] = ['in', $order_ids];
                    }
                } else {
                    $map['a.temporary_house_id|a.abnormal_house_id'] = ['in', $stock_house_id ?: [-1]];
                }

                unset($filter['stock_house_num']);
            }

            //筛选订单号
            if ($filter['increment_id']) {
                $map['b.increment_id'] = ['like', $filter['increment_id'] . '%'];
                unset($filter['increment_id']);
            }

            //筛选子单号
            if ($filter['item_order_number']) {
                $ex_fil_arr = explode(' ', $filter['item_order_number']);
                if (count($ex_fil_arr) > 1) {
                    $map['a.item_order_number'] = ['in', $ex_fil_arr];
                } else {
                    $map['a.item_order_number'] = ['like', $filter['item_order_number'] . '%'];
                }

                unset($filter['item_order_number']);
            }

            //筛选站点
            if ($filter['site']) {
                $map['a.site'] = ['in', $filter['site']];
                unset($filter['site']);
            }

            //加工类型筛选
            if (isset($filter['order_prescription_type'])) {
                $map['a.order_prescription_type'] = ['in', $filter['order_prescription_type']];
                unset($filter['order_prescription_type']);
            }

            //工单状态
            $work_order_status_map = [1, 2, 3, 5];
            $flag = false;
            if ($filter['work_status'] && 8 == $label) {
                $work_order_status_map = [];
                $work_order_status_map = $filter['work_status'];
                unset($filter['work_status']);
                $flag = true;
            }

            //工单类型
            $work_order_type = [1, 2];
            if ($filter['work_type'] && 8 == $label) {
                $work_order_type = [];
                $work_order_type = [$filter['work_type']];
                unset($filter['work_type']);
                $flag = true;
            }
            $this->request->get(['filter' => json_encode($filter)]);

            // if (8 == $label || 1 == $label || 0 == $label) {
            //     //查询子单的主单是否也含有工单

            //     // if (!empty($platform_order)) {
            //     //     $order_id = $this->_new_order_process->where(['increment_id' => ['in', $platform_order]])->group('order_id')->column('order_id');
            //     //     $item_order_numbers = $this->model->where(['order_id' => ['in', $order_id]])->order('created_at', 'desc')->group('item_order_number')->column('item_order_number');
            //     // }
            // }

            $platform_order = $this->_work_order_list->where([
                'work_status' => ['in', $work_order_status_map],
                'work_type' => ['in', $work_order_type]
            ])->group('platform_order')->column('platform_order');

            if (8 == $label) {
                //展示子工单的子单
                if ($flag || $is_work_order == 1) {
                    $map['b.increment_id'] = ['in', $platform_order];
                } else if ($is_work_order == 2) {
                    $map['a.id'] = ['in', $item_process_ids];
                    $map['b.increment_id'] = ['not in', $platform_order];
                } else {
                    $whereOr = [
                        'a.id' => ['in', $item_process_ids],
                        'b.increment_id' => ['in', $platform_order]
                    ];
                }
            }

            list($where, $sort, $order, $offset, $limit) = $this->buildparams();
            if ($sort_flag == 1) {
                $sort = 'a.sku';
                $order = 'asc';
            }
            $total = $this->model
                ->alias('a')
                ->join(['fa_order' => 'b'], 'a.order_id=b.id')
                ->where($where)
                ->where($map)
                ->where(function ($query) use ($whereOr) {
                    $query->whereOr($whereOr);
                })
                ->order($sort, $order)
                ->count();
            //combine_time  合单时间  delivery_time 打印时间 check_time审单时间  update_time更新时间  created_at创建时间

            $list = $this->model
                ->alias('a')
                ->field('a.id,a.order_id,a.item_order_number,a.sku,a.order_prescription_type,b.increment_id,b.total_qty_ordered,b.site,b.order_type,b.status,a.distribution_status,a.temporary_house_id,a.abnormal_house_id,a.created_at')
                ->join(['fa_order' => 'b'], 'a.order_id=b.id')
                ->where($where)
                ->where($map)
                ->where(function ($query) use ($whereOr) {
                    $query->whereOr($whereOr);
                })
                ->order($sort, $order)
                ->limit($offset, $limit)
                ->select();
            $list = collection($list)->toArray();

            foreach ($list as $key => $item) {
                $list[$key]['label'] = $label;
                //订单副数，去除掉取消的子单
                $list[$key]['total_qty_ordered'] = $this->model
                    ->where(['order_id' => $list[$key]['order_id'], 'distribution_status' => ['neq', 0]])
                    ->count();
                //待打印标签时间
                if ($label == 2) {
                    $list[$key]['created_at'] = Db::table('fa_distribution_log')->where('item_process_id', $item['id'])->where('distribution_node', 1)->value('create_time');
                }
                //待配货
                if ($label == 3) {
                    $list[$key]['created_at'] = Db::table('fa_distribution_log')->where('item_process_id', $item['id'])->where('distribution_node', 2)->value('create_time');
                }
                //待配镜片
                if ($label == 4) {
                    $list[$key]['created_at'] = Db::table('fa_distribution_log')->where('item_process_id', $item['id'])->where('distribution_node', 3)->value('create_time');
                }
                //待加工
                if ($label == 5) {
                    $list[$key]['created_at'] = Db::table('fa_distribution_log')->where('item_process_id', $item['id'])->where('distribution_node', 4)->value('create_time');
                }
                //待印logo
                if ($label == 6) {
                    $list[$key]['created_at'] = Db::table('fa_distribution_log')->where('item_process_id', $item['id'])->where('distribution_node', 5)->value('create_time');
                }
                //待成品质检
                if ($label == 7) {
                    $list[$key]['created_at'] = Db::table('fa_distribution_log')->where('item_process_id', $item['id'])->where('distribution_node', 6)->value('create_time');
                }
                //待合单
                if ($label == 8) {
                    $list[$key]['created_at'] = Db::table('fa_distribution_log')->where('item_process_id', $item['id'])->where('distribution_node', 7)->value('create_time');
                }
            }

            //库位号列表
            $stock_house_data = $this->_stock_house
                ->where(['status' => 1, 'type' => ['>', 1], 'occupy' => ['>', 0]])
                ->column('coding', 'id');
            //获取异常数据
            $abnormal_data = $this->_distribution_abnormal
                ->where(['item_process_id' => ['in', array_column($list, 'id')], 'status' => 1])
                ->column('work_id', 'item_process_id');
            foreach ($list as $key => $value) {

                //查询合单库位id
                $store_house_id = $this->_new_order_process->where(['order_id' => $value['order_id']])->where('store_house_id is not null')->value('store_house_id');
                $stock_house_num = '';
                if (!empty($value['temporary_house_id']) && 3 == $label) {
                    $stock_house_num = $stock_house_data[$value['temporary_house_id']]; //定制片库位号
                } elseif (!empty($value['abnormal_house_id']) && 8 == $label) {
                    $stock_house_num = $stock_house_data[$value['abnormal_house_id']]; //异常库位号
                } elseif (!empty($store_house_id) && 7 == $label && in_array($value['distribution_status'], [7, 8, 9])) {
                    $stock_house_num = $stock_house_data[$store_house_id]; //合单库位号
                }

                if ($list[$key]['created_at'] == '') {
                    $list[$key]['created_at'] == '暂无';
                } else {
                    $list[$key]['created_at'] = date('Y-m-d H:i:s', $value['created_at']);
                }
                $list[$key]['stock_house_num'] = $stock_house_num;


                //跟单：异常未处理且未创建工单的显示处理异常按钮
                $work_id = $abnormal_data[$value['id']] ?? 0;
                if (8 == $label && 0 < $value['abnormal_house_id'] && 0 == $work_id) {
                    $handle_abnormal = 1;
                } else {
                    $handle_abnormal = 0;
                }
                $list[$key]['handle_abnormal'] = $handle_abnormal;

                //判断是否显示工单按钮
                $list[$key]['task_info'] = in_array($value['increment_id'], $platform_order) ? 1 : 0;
                /*if (8 == $label || 1 == $label || 0 == $label) {
                    //查询子单的主单是否也含有工单
                    if ($handle_abnormal == 0 && $list[$key]['task_info'] == 0) {
                        $platform_order = $this->_new_order_process->where(['order_id' => $list[$key]['order_id']])->value('increment_id');
                        $work_order_list_task = $this->_work_order_list->where(['work_status' => ['in',[1,2,3,5]],'platform_order' =>$platform_order])->find();
                        if (!empty($work_order_list_task)) {
                            $list[$key]['task_info'] = 1;
                        } 
                    }
                }*/
                //获取工单更改镜框最新信息
                $change_sku = $this->_work_order_change_sku
                    ->alias('a')
                    ->join(['fa_work_order_measure' => 'b'], 'a.measure_id=b.id')
                    ->where([
                        'a.change_type' => 1,
                        'a.item_order_number' => $value['item_order_number'],
                        'b.operation_type' => 1
                    ])
                    ->order('a.id', 'desc')
                    ->limit(1)
                    ->value('a.change_sku');
                if ($change_sku) {
                    $list[$key]['sku'] = $change_sku;
                }
            }
            $result = array("total" => $total, "rows" => $list);
            return json($result);
        }
        $this->assign('label', $label);
        $this->assignconfig('label', $label);

        $label_list = [2 => '待配货', 3 => '待配镜片', 4 => '待加工', 5 => '待印logo', 6 => '待成品质检', 7 => '待合单', 8 => '跟单', 0 => '全部'];
        $this->assign('label_list', $label_list);

        return $this->view->fetch();
    }

    /**
     * 波次单列表
     *
     * @Description
     * @author wpl
     * @since 2021/03/23 15:48:02 
     * @return void
     */
    public function wave_order_list()
    {
        $label = $this->request->get('label', 0);
        //设置过滤方法
        $this->request->filter(['strip_tags']);
        if ($this->request->isAjax()) {
            //如果发送的来源是Selectpage，则转发到Selectpage
            if ($this->request->request('keyField')) {
                return $this->selectpage();
            }

            $filter = json_decode($this->request->get('filter'), true);
            //查询子订单
            if ($filter['item_order_number']) {
                $smap['item_order_number'] = ['like', $filter['item_order_number'].'%'];
                $wave_order_id = $this->_new_order_item_process->where($smap)->value('wave_order_id');
                $map['id'] = $wave_order_id;
                unset($filter['item_order_number']);
            }
            $this->request->get(['filter' => json_encode($filter)]);

            list($where, $sort, $order, $offset, $limit) = $this->buildparams();
            $total = $this->_wave_order
                ->where($where)
                ->where($map)
                ->count();

            $list = $this->_wave_order
                ->where($where)
                ->where($map)
                ->order($sort, $order)
                ->limit($offset, $limit)
                ->select();
            $list = collection($list)->toArray();

            foreach ($list as $k => $v) {
                $list[$k]['order_date'] = date('Y-m-d H:i:s', $v['order_date']);
                $list[$k]['createtime'] = date('Y-m-d H:i:s', $v['createtime']);
            }

            $result = array("total" => $total, "rows" => $list);
            return json($result);
        }
        $this->assign('label', $label);
        $this->assignconfig('label', $label);

        $label_list = [2 => '待配货', 3 => '待配镜片', 4 => '待加工', 5 => '待印logo', 6 => '待成品质检', 7 => '待合单', 8 => '跟单', 0 => '全部'];
        $this->assign('label_list', $label_list);

        return $this->view->fetch();
    }


    /**
     * 波次单列表
     *
     * @Description
     * @author wpl
     * @since 2021/03/23 15:48:02 
     * @return void
     */
    public function wave_order_detail($ids = null)
    {
        //设置过滤方法
        $this->request->filter(['strip_tags']);
        if ($this->request->isAjax()) {
            //如果发送的来源是Selectpage，则转发到Selectpage
            if ($this->request->request('keyField')) {
                return $this->selectpage();
            }
            $filter = json_decode($this->request->get('filter'), true);
            //默认展示订单状态
            if ($filter) {
                if ($filter['status']) {
                    $map['b.status'] = ['in', $filter['status']];
                    unset($filter['status']);
                } else {
                    $map['b.status'] = ['in', ['processing', 'paypal_reversed', 'paypal_canceled_reversal']];
                }
            } else {
                $map['b.status'] = ['in', ['processing', 'paypal_reversed', 'paypal_canceled_reversal']];
                unset($filter['status']);
            }

            $map['a.distribution_status'] = ['<>', 0];

            //筛选站点
            if ($filter['site']) {
                $map['a.site'] = ['in', $filter['site']];
                unset($filter['site']);
            }

            //加工类型筛选
            if (isset($filter['order_prescription_type'])) {
                $map['a.order_prescription_type'] = ['in', $filter['order_prescription_type']];
                unset($filter['order_prescription_type']);
            }

            //工单状态
            $work_order_status_map = [1, 2, 3, 5];
            //工单类型
            $work_order_type = [1, 2];
            $platform_order = $this->_work_order_list->where([
                'work_status' => ['in', $work_order_status_map],
                'work_type' => ['in', $work_order_type]
            ])->group('platform_order')->column('platform_order');

            //波次单id
            $ids = input('ids');
            if ($ids) $map['wave_order_id'] = $ids;

            $this->request->get(['filter' => json_encode($filter)]);

            list($where, $sort, $order, $offset, $limit) = $this->buildparams();

            $total = $this->model
                ->alias('a')
                ->join(['fa_order' => 'b'], 'a.order_id=b.id')
                ->where($where)
                ->where($map)
                ->count();
            $list = $this->model
                ->alias('a')
                ->field('a.id,a.order_id,a.item_order_number,a.sku,a.order_prescription_type,b.increment_id,b.total_qty_ordered,b.site,b.order_type,b.status,a.distribution_status,a.created_at,a.picking_sort')
                ->join(['fa_order' => 'b'], 'a.order_id=b.id')
                ->where($where)
                ->where($map)
                ->order($sort, $order)
                ->limit($offset, $limit)
                ->select();
            $list = collection($list)->toArray();

            foreach ($list as $key => $value) {
                //订单副数，去除掉取消的子单
                $list[$key]['total_qty_ordered'] = $this->model
                    ->where(['order_id' => $list[$key]['order_id'], 'distribution_status' => ['neq', 0]])
                    ->count();

                if ($list[$key]['created_at'] == '') {
                    $list[$key]['created_at'] == '暂无';
                } else {
                    $list[$key]['created_at'] = date('Y-m-d H:i:s', $value['created_at']);
                }

                //判断是否显示工单按钮
                $list[$key]['task_info'] = in_array($value['increment_id'], $platform_order) ? 1 : 0;

                //获取工单更改镜框最新信息
                $change_sku = $this->_work_order_change_sku
                    ->alias('a')
                    ->join(['fa_work_order_measure' => 'b'], 'a.measure_id=b.id')
                    ->where([
                        'a.change_type' => 1,
                        'a.item_order_number' => $value['item_order_number'],
                        'b.operation_type' => 1
                    ])
                    ->order('a.id', 'desc')
                    ->limit(1)
                    ->value('a.change_sku');
                if ($change_sku) {
                    $list[$key]['sku'] = $change_sku;
                }
            }
            $result = array("total" => $total, "rows" => $list);
            return json($result);
        }
        $this->assignconfig('ids', $ids);
        return $this->view->fetch();
    }


    public function csv_array()
    {
        set_time_limit(0);
        ini_set('memory_limit', '512M');
        $map = [];
        $map['a.site'] = 1;
        $map['a.created_at'] = ['between', ['1606752000', '1609430399']];

        //子单工单未处理
        $item_order_numbers = $this->_work_order_change_sku
            ->alias('a')
            ->join(['fa_work_order_list' => 'b'], 'a.work_id=b.id')
            ->where([
                'a.change_type' => ['in', [1, 2, 3]], //1更改镜架  2更改镜片 3取消订单
                'b.work_status' => ['in', [1, 2, 3, 5]] //工单未处理
            ])
            ->order('a.create_time', 'desc')
            ->group('a.item_order_number')
            ->column('a.item_order_number');

        list($where, $sort, $order, $offset, $limit) = $this->buildparams();

        $list = $this->model
            ->alias('a')
            ->field('a.id,a.order_id,a.item_order_number,a.sku,a.order_prescription_type,b.increment_id,b.total_qty_ordered,b.site,b.order_type,b.status,a.distribution_status,a.temporary_house_id,a.abnormal_house_id,a.created_at,c.store_house_id')
            ->join(['fa_order' => 'b'], 'a.order_id=b.id')
            ->join(['fa_order_process' => 'c'], 'a.order_id=c.order_id')
            ->where($where)
            ->where($map)
            ->order($sort, $order)
            ->select();

        $list = collection($list)->toArray();


        //获取工单更改镜框最新信息
        $change_sku = $this->_work_order_change_sku
            ->alias('a')
            ->join(['fa_work_order_measure' => 'b'], 'a.measure_id=b.id')
            ->where([
                'a.change_type' => 1,
                'a.item_order_number' => ['in', array_column($list, 'item_order_number')],
                'b.operation_type' => 1
            ])
            ->order('a.id', 'desc')
            ->group('a.item_order_number')
            ->column('a.change_sku', 'a.item_order_number');

        foreach ($list as $key => $value) {
            $stock_house_num = '-';

            if ($list[$key]['created_at'] == '') {
                $list[$key]['created_at'] == '暂无';
            } else {
                $list[$key]['created_at'] = date('Y-m-d H:i:s', $value['created_at']);
            }
            $list[$key]['stock_house_num'] = $stock_house_num;

            //判断是否显示工单按钮
            $list[$key]['task_info'] = in_array($value['item_order_number'], $item_order_numbers) ? 1 : 0;

            if ($change_sku[$value['item_order_number']]) {
                $list[$key]['sku'] = $change_sku[$value['item_order_number']];
            }
            //站点
            switch ($value['site']) {
                case 1:
                    $list[$key]['site'] = 'Zeelool';
                    break;
                case 2:
                    $list[$key]['site'] = 'Voogueme';
                    break;
                case 3:
                    $list[$key]['site'] = 'Nihao';
                    break;
                case 4:
                    $list[$key]['site'] = 'Meeloog';
                    break;
                case 5:
                    $list[$key]['site'] = 'Wesee';
                    break;
                case 8:
                    $list[$key]['site'] = 'Amazon';
                    break;
                case 9:
                    $list[$key]['site'] = 'Zeelool_es';
                    break;
                case 10:
                    $list[$key]['site'] = 'Zeelool_de';
                    break;
                case 11:
                    $list[$key]['site'] = 'Zeelool_jp';
                    break;
                default:
                    break;
            }
            //加工类型
            switch ($value['order_prescription_type']) {
                case 0:
                    $list[$key]['order_prescription_type'] = '待处理';
                    break;
                case 1:
                    $list[$key]['order_prescription_type'] = '仅镜架';
                    break;
                case 2:
                    $list[$key]['order_prescription_type'] = '现货处方镜';
                    break;
                case 3:
                    $list[$key]['order_prescription_type'] = '定制处方镜';
                    break;
                case 4:
                    $list[$key]['order_prescription_type'] = '其他';
                    break;
                default:
                    break;
            }
            //订单类型
            switch ($value['order_type']) {

                case 1:
                    $list[$key]['order_type'] = '普通订单';
                    break;
                case 2:
                    $list[$key]['order_type'] = '批发单';
                    break;
                case 3:
                    $list[$key]['order_type'] = '网红单';
                    break;
                case 4:
                    $list[$key]['order_type'] = '补发单';
                    break;
                case 5:
                    $list[$key]['order_type'] = '补差价';
                    break;
                case 6:
                    $list[$key]['order_type'] = '一件代发';
                    break;
                case 10:
                    $list[$key]['order_type'] = '货到付款';
                    break;
                default:
                    break;
            }

            //子订单状态
            switch ($value['distribution_status']) {
                case 0:
                    $list[$key]['distribution_status'] = '取消';
                    break;
                case 1:
                    $list[$key]['distribution_status'] = '待打印标签';
                    break;
                case 2:
                    $list[$key]['distribution_status'] = '待配货';
                    break;
                case 3:
                    $list[$key]['distribution_status'] = '待配镜片';
                    break;
                case 4:
                    $list[$key]['distribution_status'] = '待加工';
                    break;
                case 5:
                    $list[$key]['distribution_status'] = '待印logo';
                    break;
                case 6:
                    $list[$key]['distribution_status'] = '待成品质检';
                    break;
                case 7:
                    $list[$key]['distribution_status'] = '待合单';
                    break;
                case 8:
                    $list[$key]['distribution_status'] = '合单中';
                    break;
                case 9:
                    $list[$key]['distribution_status'] = '合单完成';
                    break;
                default:
                    break;
            }
        }

        foreach ($list as $key => $item) {
            $csv[$key]['increment_id'] = $item['increment_id'];
            $csv[$key]['item_order_number'] = $item['item_order_number'];
            $csv[$key]['sku'] = $item['sku'];
            $csv[$key]['total_qty_ordered'] = $item['total_qty_ordered'];
            $csv[$key]['task_info'] = $item['task_info'];
            $csv[$key]['site'] = $item['site'];
            $csv[$key]['order_prescription_type'] = $item['order_prescription_type'];
            $csv[$key]['order_type'] = $item['order_type'];
            $csv[$key]['status'] = $item['status'];
            $csv[$key]['distribution_status'] = $item['distribution_status'];
            $csv[$key]['created_at'] = $item['created_at'];
        }
        $headlist = [
            '订单号', '子单号', 'SKU', '订单副数', '工单', '站点', '加工类型', '订单类型', '订单状态', '子单号状态', '创建时间'

        ];

        $path = "/uploads/";
        $fileName = 'Zeelool站配货列表十二月份数据';
        Excel::writeCsv($csv, $headlist, $path . $fileName);
    }


    /**
     * 待印logo数据导出
     *
     */
    public function printing_batch_export_xls()
    {

        $data = input('');
        if ($data['ids']) {
            $where['a.id'] = ['in', $data['ids']];
        } else {
            $where['a.distribution_status'] = 1;
        }
        $map = [];
        $WhereSql = 'a.id > 0';
        //普通状态剔除跟单数据

        //处理异常选项
        $filter = json_decode($this->request->get('filter'), true);

        if (!$filter) {
            $map['a.created_at'] = ['between', [strtotime('-3 month'), time()]];
            $WhereSql .= " and a.created_at between " . strtotime('-3 month') . " and " . time();
        } else {
            if ($filter['a.created_at']) {
                $time = explode(' - ', $filter['a.created_at']);

                $map['a.created_at'] = ['between', [strtotime($time[0]), strtotime($time[1])]];
            }
        }

        if (!$filter['status']) {
            $map['b.status'] = ['in', ['processing', 'paypal_reversed', 'paypal_canceled_reversal']];
        } else {
            $map['b.status'] = ['in', $filter['status']];
        }
        unset($filter['status']);

        //跟单或筛选异常

        if ($filter['abnormal']) {
            //异常类型
            if ($filter['abnormal']) {
                $abnormal_where['type'] = ['in', $filter['abnormal']];
                unset($filter['abnormal']);
            }

            //获取异常的子订单id
            $item_process_ids = $this->_distribution_abnormal
                ->where($abnormal_where)
                ->column('item_process_id');

            if ($item_process_ids == null) {
                $map['a.id'] = ['eq', null];
                $WhereSql .= "a.id =  null";
            }
        };

        if ($filter['increment_id']) {
            $map['b.increment_id'] = ['like', $filter['increment_id'] . '%'];
            $map['b.status'] = ['in', ['free_processing', 'processing', 'complete', 'paypal_reversed', 'paypal_canceled_reversal']];
            unset($filter['increment_id']);
        }

        if ($filter['site']) {
            $map['a.site'] = ['in', $filter['site']];
            unset($filter['site']);
        }

        if (isset($filter['order_prescription_type'])) {
            $map['a.order_prescription_type'] = ['in', $filter['order_prescription_type']];
            unset($filter['order_prescription_type']);
        }
        $this->request->get(['filter' => json_encode($filter)]);

        $map['a.abnormal_house_id'] = 0;

        //订单里面所有的
        $list = $this->model
            ->alias('a')
            ->field('a.sku,a.site')
            ->join(['fa_order' => 'b'], 'a.order_id=b.id')
            ->join(['fa_order_process' => 'c'], 'a.order_id=c.order_id')
            ->where($where)
            ->where($map)
            ->select();

        $list = collection($list)->toArray();
        $data = array();
        foreach ($list as $k => $v) {
            $item_platform_sku = Db::connect('database.db_stock');
            $sku = $item_platform_sku->table('fa_item_platform_sku')
                ->alias('pla')
                ->join(['fa_item' => 'it'], 'pla.sku=it.sku')
                ->where('pla.platform_sku', $v['sku'])
                ->where('pla.platform_type', $v['site'])->field('pla.sku,it.real_time_qty')->find();

            $data[$sku['sku']]['location'] =
                Db::table('fa_store_sku')
                ->alias('a')
                ->join(['fa_store_house' => 'b'], 'a.store_id=b.id')
                ->where('a.sku', $sku['sku'])
                ->value('b.coding');
            $data[$sku['sku']]['sku'] = $sku;
            $data[$sku['sku']]['number']++;
        }
        // $b=array();
        // foreach($sku as $v){
        //     $b[]=$v['sku'];
        // }
        // dump($b);
        // $c=array_unique($b);
        // foreach($c as$k => $v){
        //     $n=0;
        //     foreach($sku as $t){
        //         if($v==$t['sku'])
        //             $n++;
        //     }
        //     $new[$v]=$n;
        // }
        // dump($new);
        // foreach ($sku as $ky=>$ite){
        //     $new_value = array_keys($new);
        //     $count = count($new_value)-1;
        //     for ($i=0;$i<=$count;$i++){
        //         if ($new_value[$i] == $ite['sku']){
        //             $sku[$ky]['number'] = $new[$new_value[$i]];
        //         }
        //     }
        // }
        // dump($sku);
        // $sku =array_merge(array_unique($sku, SORT_REGULAR));
        // dump($sku);die();

        $data = array_values($data);
        $spreadsheet = new Spreadsheet();
        //常规方式：利用setCellValue()填充数据
        $spreadsheet
            ->setActiveSheetIndex(0)->setCellValue("A1", "仓库SKU")
            ->setCellValue("B1", "库位号")
            ->setCellValue("C1", "数量")
            ->setCellValue("D1", "仓库实时库存");
        foreach ($data as $key => $value) {
            $spreadsheet->getActiveSheet()->setCellValueExplicit("A" . ($key * 1 + 2), $value['sku']['sku'], \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
            $spreadsheet->getActiveSheet()->setCellValue("B" . ($key * 1 + 2), $value['location']);
            $spreadsheet->getActiveSheet()->setCellValue("C" . ($key * 1 + 2), $value['number']);
            $spreadsheet->getActiveSheet()->setCellValue("D" . ($key * 1 + 2), $value['sku']['real_time_qty']);
        }
        //设置宽度
        $spreadsheet->getActiveSheet()->getColumnDimension('A')->setWidth(20);
        $spreadsheet->getActiveSheet()->getColumnDimension('B')->setWidth(20);
        $spreadsheet->getActiveSheet()->getColumnDimension('C')->setWidth(20);
        $spreadsheet->getActiveSheet()->getColumnDimension('D')->setWidth(20);

        //设置边框
        $border = [
            'borders' => [
                'allBorders' => [
                    'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN, // 设置border样式
                    'color' => ['argb' => 'FF000000'], // 设置border颜色
                ],
            ],
        ];

        $spreadsheet->getDefaultStyle()->getFont()->setName('微软雅黑')->setSize(12);

        $setBorder = 'A1:' . $spreadsheet->getActiveSheet()->getHighestColumn() . $spreadsheet->getActiveSheet()->getHighestRow();
        $spreadsheet->getActiveSheet()->getStyle($setBorder)->applyFromArray($border);

        $spreadsheet->getActiveSheet()->getStyle('A1:D' . $spreadsheet->getActiveSheet()->getHighestRow())->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
        $spreadsheet->setActiveSheetIndex(0);

        $format = 'xlsx';
        $savename = '配货列表待打印数据' . date("YmdHis", time());

        if ($format == 'xls') {
            //输出Excel03版本
            header('Content-Type:application/vnd.ms-excel');
            $class = "\PhpOffice\PhpSpreadsheet\Writer\Xls";
        } elseif ($format == 'xlsx') {
            //输出07Excel版本
            header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
            $class = "\PhpOffice\PhpSpreadsheet\Writer\Xlsx";
        }

        //输出名称
        header('Content-Disposition: attachment;filename="' . $savename . '.' . $format . '"');
        //禁止缓存
        header('Cache-Control: max-age=0');
        $writer = new $class($spreadsheet);

        $writer->save('php://output');
    }


    /**
     * 镜片详情
     *
     * @Description
     * @author wpl
     * @since 2020/11/09 18:03:41 
     * @param [type] $ids
     * @return void
     */
    public function detail($ids = null)
    {
        $row = $this->model->get($ids);
        !$row && $this->error(__('No Results were found'));

        //查询处方详情
        $result = $this->_new_order_item_option->get($row->option_id)->toArray();

        //获取更改镜框最新信息
        $change_sku = $this->_work_order_change_sku
            ->alias('a')
            ->join(['fa_work_order_measure' => 'b'], 'a.measure_id=b.id')
            ->where([
                'a.change_type' => 1,
                'a.item_order_number' => $row->item_order_number,
                'b.operation_type' => 1
            ])
            ->order('a.id', 'desc')
            ->value('a.change_sku');
        if ($change_sku) {
            $result['sku'] = $change_sku;
        }

        //获取更改镜片最新处方信息
        $change_lens = $this->_work_order_change_sku
            ->alias('a')
            ->field('a.od_sph,a.od_cyl,a.od_axis,a.od_add,a.pd_r,a.od_pv,a.od_bd,a.od_pv_r,a.od_bd_r,a.os_sph,a.os_cyl,a.os_axis,a.os_add,a.pd_l,a.os_pv,a.os_bd,a.os_pv_r,a.os_bd_r,a.lens_number,a.recipe_type as prescription_type,prescription_option')
            ->join(['fa_work_order_measure' => 'b'], 'a.measure_id=b.id')
            ->where([
                'a.change_type' => 2,
                'a.item_order_number' => $row->item_order_number,
                'b.operation_type' => 1
            ])
            ->order('a.id', 'desc')
            ->find();
        if ($change_lens) {
            $change_lens = $change_lens->toArray();

            //处理pd值
            if ($change_lens['pd_l'] && $change_lens['pd_r']) {
                $change_lens['pd'] = '';
                $change_lens['pdcheck'] = 'on';
            } else {
                $change_lens['pd'] = $change_lens['pd_r'] ?: $change_lens['pd_l'];
                $change_lens['pdcheck'] = '';
            }

            //处理斜视值
            if ($change_lens['od_pv'] || $change_lens['os_pv']) {
                $change_lens['prismcheck'] = 'on';
            } else {
                $change_lens['prismcheck'] = '';
            }

            //处理镀膜
            $prescription_option = unserialize($change_lens['prescription_option']);
            $change_lens['coating_name'] = $prescription_option['coating_name'] ?: '';
            unset($change_lens['prescription_option']);

            $result = array_merge($result, $change_lens);
        }

        //获取镜片名称
        $lens_name = '';
        if ($result['lens_number']) {
            //获取镜片编码及名称
            $lens_name = $this->_lens_data->where('lens_number', $result['lens_number'])->value('lens_name');
        }
        $result['lens_name'] = $lens_name;
        $result['web_lens_name'] = $result['web_lens_name'] ?: $result['index_name'];
        $this->assign('result', $result);
        return $this->view->fetch();
    }

    /**
     * 获取镜架尺寸
     *
     * @Description
     * @author wpl
     * @since 2020/11/13 10:08:45 
     * @param [type] $product_id
     * @param [type] $site
     * @return void
     */
    protected function get_frame_lens_width_height_bridge($product_id, $site)
    {
        if ($product_id) {

            if ($site == 3) {
                $querySql = "select cpev.entity_type_id,cpev.attribute_id,cpev.`value`,cpev.entity_id
                from catalog_product_entity_varchar cpev LEFT JOIN catalog_product_entity cpe on cpe.entity_id=cpev.entity_id 
                where cpev.attribute_id in(146,147,149) and cpev.store_id=0 and cpev.entity_id=$product_id";

                $lensSql = "select cpev.entity_type_id,cpev.attribute_id,cpev.`value`,cpev.entity_id
                from catalog_product_entity_decimal cpev LEFT JOIN catalog_product_entity cpe on cpe.entity_id=cpev.entity_id 
                where cpev.attribute_id in(146,147) and cpev.store_id=0 and cpev.entity_id=$product_id";
            } else {
                $querySql = "select cpev.entity_type_id,cpev.attribute_id,cpev.`value`,cpev.entity_id
                from catalog_product_entity_varchar cpev LEFT JOIN catalog_product_entity cpe on cpe.entity_id=cpev.entity_id 
                where cpev.attribute_id in(161,163,164) and cpev.store_id=0 and cpev.entity_id=$product_id";
            }

            switch ($site) {
                case 1:
                    $model = Db::connect('database.db_zeelool');
                    break;
                case 2:
                    $model = Db::connect('database.db_voogueme');
                    break;
                case 3:
                    $model = Db::connect('database.db_nihao');
                    break;
                case 4:
                    $model = Db::connect('database.db_meeloog');
                    break;
                case 5:
                    $model = Db::connect('database.db_weseeoptical');
                    break;
                case 9:
                    $model = Db::connect('database.db_zeelool_es_online');
                    break;
                case 10:
                    $model = Db::connect('database.db_zeelool_de_online');
                    break;
                case 11:
                    $model = Db::connect('database.db_zeelool_jp_online');
                    break;
                case 12:
                    $model = Db::connect('database.db_voogueme_acc');
                    break;
                default:
                    break;
            }

            $resultList = $model->query($querySql);
            $result = array();
            //你好站
            if ($site == 3) {
                $lensList = $model->query($lensSql);
                if ($lensList) {
                    foreach ($lensList as $key => $value) {

                        if ($value['attribute_id'] == 146) {
                            $result['lens_width'] = $value['value'];
                        }
                        if ($value['attribute_id'] == 147) {
                            $result['lens_height'] = $value['value'];
                        }
                    }
                }
            }
            if ($resultList) {
                foreach ($resultList as $key => $value) {
                    //你好站
                    if ($site == 3) {

                        if ($value['attribute_id'] == 149) {
                            $result['bridge'] = $value['value'];
                        }
                    } else {
                        if ($value['attribute_id'] == 161) {
                            $result['lens_width'] = $value['value'];
                        }
                        if ($value['attribute_id'] == 164) {
                            $result['lens_height'] = $value['value'];
                        }
                        if ($value['attribute_id'] == 163) {
                            $result['bridge'] = $value['value'];
                        }
                    }
                }
            }
        }
        return $result;
    }

    /**
     * 批量导出
     *
     * @Description
     * @author wpl
     * @since 2020/11/12 08:54:05 
     * @return void
     */
    public function batch_export_xls()
    {
        set_time_limit(0);
        ini_set('memory_limit', '512M');
        //根据传的标签切换状态
        $label = $this->request->get('label', 0);
        $ids = input('ids');
        $map = [];
        if ($ids) {
            $map['a.id'] = ['in', $ids];
            $where = [];
            $sort = 'a.created_at';
            $order = 'desc';
        } else {

            //普通状态剔除跟单数据
            if (!in_array($label, [0, 8])) {
                if (7 == $label) {
                    $map['a.status'] = [['>', 6], ['<', 9]];
                } else {
                    $map['a.status'] = $label;
                }
                $map['a.temporary_house_id|a.abnormal_house_id'] = 0;
            }

            $filter = json_decode($this->request->get('filter'), true);

            if ($filter['abnormal'] || $filter['stock_house_num']) {
                //                //筛选异常
                if ($filter['abnormal']) {
                    $abnormal_where['type'] = $filter['abnormal'][0];
                    if (8 == $label) {
                        $abnormal_where['status'] = 1;
                    }

                    $item_process_id = $this->_distribution_abnormal
                        ->where($abnormal_where)
                        ->column('item_process_id');
                    $map['a.id'] = ['in', $item_process_id];
                }

                //筛选库位号
                if ($filter['stock_house_num']) {
                    $stock_house_where['coding'] = ['like', $filter['stock_house_num'] . '%'];
                    if (8 == $label) {
                        $stock_house_where['type'] = ['>', 2];
                    } else {
                        $stock_house_where['type'] = 2;
                    }
                    $stock_house_id = $this->_stock_house
                        ->where($stock_house_where)
                        ->column('id');
                    $map['a.temporary_house_id|a.abnormal_house_id'] = ['in', $stock_house_id];
                }
                unset($filter['abnormal']);
                unset($filter['stock_house_num']);
                unset($filter['is_task']);
            }

            if ($filter['a.created_at']) {
                $time = explode(' - ', $filter['a.created_at']);

                $map['a.created_at'] = ['between', [strtotime($time[0]), strtotime($time[1])]];
            }
            if ($filter['site']) {
                $map['a.site'] = ['in', $filter['site']];
                unset($filter['site']);
            }
            //加工类型
            if ($filter['order_prescription_type']) {
                $map['a.order_prescription_type'] = ['in', $filter['order_prescription_type']];
                unset($filter['order_prescription_type']);
            }
            //订单类型
            if ($filter['order_type']) {
                $map['b.order_type'] = ['in', $filter['order_type']];
                unset($filter['order_type']);
            }
            if ($filter['distribution_status']) {
                $map['a.distribution_status'] = ['in', $filter['distribution_status']];
                unset($filter['distribution_status']);
            }
            if ($filter['status']) {
                $map['b.status'] = ['in', $filter['status']];
                unset($filter['status']);
            }

            if ($filter['sku']) {
                $map['a.sku'] = ['like', '%' . $filter['sku'] . '%'];
                unset($filter['sku']);
            }
            $this->request->get(['filter' => json_encode($filter)]);

            list($where, $sort, $order) = $this->buildparams();
        }

        $sort = 'a.id';


        $list = $this->model
            ->alias('a')
            ->field('a.id as aid,a.item_order_number,a.sku,a.order_prescription_type,b.increment_id,b.status,b.total_qty_ordered,b.site,a.distribution_status,a.created_at,c.*,b.base_grand_total,b.order_type,b.base_currency_code,b.payment_time,b.payment_method')
            ->join(['fa_order' => 'b'], 'a.order_id=b.id')
            ->join(['fa_order_item_option' => 'c'], 'a.option_id=c.id')
            ->where($where)
            ->where($map)
            ->order($sort, $order)
            ->select();
        $list = collection($list)->toArray();


        //从数据库查询需要的数据
        $spreadsheet = new Spreadsheet();

        //常规方式：利用setCellValue()填充数据
        $spreadsheet->setActiveSheetIndex(0)
            ->setCellValue("A1", "ID")
            ->setCellValue("B1", "日期")
            ->setCellValue("C1", "订单号")
            ->setCellValue("D1", "站点")
            ->setCellValue("E1", "订单类型")
            ->setCellValue("F1", "订单状态")
            ->setCellValue("G1", "子单号")
            ->setCellValue("H1", "SKU")
            ->setCellValue("I1", "眼球")
            ->setCellValue("J1", "SPH")
            ->setCellValue("K1", "CYL")
            ->setCellValue("L1", "AXI")
            ->setCellValue("M1", "ADD")
            ->setCellValue("N1", "PD")
            ->setCellValue("O1", "镜片")
            ->setCellValue("P1", "镜框宽度")
            ->setCellValue("Q1", "镜框高度")
            ->setCellValue("R1", "bridge")
            ->setCellValue("S1", "处方类型")
            ->setCellValue("T1", "Prism\n(out/in)")
            ->setCellValue("U1", "Direct\n(out/in)")
            ->setCellValue("V1", "Prism\n(up/down)")
            ->setCellValue("W1", "Direct\n(up/down)")
            ->setCellValue("X1", "订单金额")
            ->setCellValue("Y1", "原币种")
            ->setCellValue("Z1", "原支付金额")
            ->setCellValue("AA1", "支付方式")
            ->setCellValue("AB1", "订单支付时间");
        $spreadsheet->setActiveSheetIndex(0)->setTitle('订单处方');

        //站点列表
        $site_list = [
            1 => 'Zeelool',
            2 => 'Voogueme',
            3 => 'Nihao',
            4 => 'Meeloog',
            5 => 'Wesee',
            8 => 'Amazon',
            9 => 'Zeelool_es',
            10 => 'Zeelool_de',
            11 => 'Zeelool_jp'
        ];

        //子单号状态
        $distribution_status_list = [
            1 => '待打印标签',
            2 => '待配货',
            3 => '待配镜片',
            4 => '待加工',
            5 => '待印logo',
            6 => '待成品质检',
            7 => '待合单',
            8 => '合单中',
            9 => '合单完成'
        ];

        //获取更改镜框最新信息
        $change_sku = $this->_work_order_change_sku
            ->alias('a')
            ->join(['fa_work_order_measure' => 'b'], 'a.measure_id=b.id')
            ->where([
                'a.change_type' => 1,
                'a.item_order_number' => ['in', array_column($list, 'item_order_number')],
                'b.operation_type' => 1
            ])
            ->column('a.change_sku', 'a.item_order_number');

        //获取更改镜片最新处方信息
        $change_lens = $this->_work_order_change_sku
            ->alias('a')
            ->join(['fa_work_order_measure' => 'b'], 'a.measure_id=b.id')
            ->where([
                'a.change_type' => 2,
                'a.item_order_number' => ['in', array_column($list, 'item_order_number')],
                'b.operation_type' => 1
            ])
            ->column('a.od_sph,a.od_cyl,a.od_axis,a.od_add,a.pd_r,a.od_pv,a.od_bd,a.od_pv_r,a.od_bd_r,a.os_sph,a.os_cyl,a.os_axis,a.os_add,a.pd_l,a.os_pv,a.os_bd,a.os_pv_r,a.os_bd_r,a.lens_number,a.recipe_type as prescription_type,a.web_lens_name', 'a.item_order_number');

        if ($change_lens) {
            foreach ($change_lens as $key => $val) {
                if ($val['pd_l'] && $val['pd_r']) {
                    $change_lens[$key]['pd'] = '';
                    $change_lens[$key]['pdcheck'] = 'on';
                } else {
                    $change_lens[$key]['pd'] = $val['pd_r'] ?: $val['pd_l'];
                    $change_lens[$key]['pdcheck'] = '';
                }
            }
        }
        //获取镜片编码及名称
        $lens_list = $this->_lens_data->column('lens_name', 'lens_number');
        foreach ($list as $key => &$value) {

            //更改镜框最新sku
            if ($change_sku[$value['item_order_number']] && $value['site'] != 5) {
                $value['sku'] = $change_sku[$value['item_order_number']];

                $getGlassInfo = $this->httpRequest($value['site'], 'magic/order/getGlassInfo', ['skus' => $value['sku']], 'POST');
                $tmp_bridge = $getGlassInfo[0];
            } else {
                //过滤饰品站 批发站
                if ($value['site'] != 12) {
                    //查询镜框尺寸
                    $tmp_bridge = $this->get_frame_lens_width_height_bridge($value['product_id'], $value['site']);
                }
            }

            $data[$value['increment_id']]['item_order'][$key]['lens_width'] = $tmp_bridge['lens_width'];
            $data[$value['increment_id']]['item_order'][$key]['lens_height'] = $tmp_bridge['lens_height'];
            $data[$value['increment_id']]['item_order'][$key]['bridge'] = $tmp_bridge['bridge'];

            //更改镜片最新数据
            if ($change_lens[$value['item_order_number']]) {
                $value = array_merge($value, $change_lens[$value['item_order_number']]);
            }

            $data[$value['increment_id']]['id'] = $value['id'];
            $data[$value['increment_id']]['created_at'] = $value['created_at'];
            $data[$value['increment_id']]['increment_id'] = $value['increment_id'];
            $data[$value['increment_id']]['site'] = $value['site'];
            $data[$value['increment_id']]['order_type'] = $value['order_type'];
            $data[$value['increment_id']]['status'] = $value['status'];
            $data[$value['increment_id']]['item_order'][$key]['item_order_number'] = $value['item_order_number'];
            $data[$value['increment_id']]['item_order'][$key]['sku'] = $value['sku'];
            $data[$value['increment_id']]['item_order'][$key]['od_sph'] = $value['od_sph'];
            $data[$value['increment_id']]['item_order'][$key]['od_cyl'] = $value['od_cyl'];
            $data[$value['increment_id']]['item_order'][$key]['od_axis'] = $value['od_axis'];
            $data[$value['increment_id']]['item_order'][$key]['od_add'] = $value['od_add'];
            $data[$value['increment_id']]['item_order'][$key]['os_add'] = $value['os_add'];
            $data[$value['increment_id']]['item_order'][$key]['pd'] = $value['pd'];
            $data[$value['increment_id']]['item_order'][$key]['pdcheck'] = $value['pdcheck'];
            $data[$value['increment_id']]['item_order'][$key]['product_id'] = $value['product_id'];

            $data[$value['increment_id']]['item_order'][$key]['prescription_type'] = $value['prescription_type'];
            $data[$value['increment_id']]['item_order'][$key]['od_pv'] = $value['od_pv'];
            $data[$value['increment_id']]['item_order'][$key]['os_pv'] = $value['os_pv'];
            $data[$value['increment_id']]['item_order'][$key]['pd_r'] = $value['pd_r'];
            $data[$value['increment_id']]['item_order'][$key]['pd_l'] = $value['pd_l'];
            $data[$value['increment_id']]['item_order'][$key]['os_pv'] = $value['os_pv'];
            $data[$value['increment_id']]['item_order'][$key]['os_bd'] = $value['os_bd'];
            $data[$value['increment_id']]['item_order'][$key]['od_bd'] = $value['od_bd'];
            $data[$value['increment_id']]['item_order'][$key]['od_pv_r'] = $value['od_pv_r'];
            $data[$value['increment_id']]['item_order'][$key]['os_bd_r'] = $value['os_bd_r'];
            $data[$value['increment_id']]['item_order'][$key]['od_bd_r'] = $value['od_bd_r'];
            $data[$value['increment_id']]['item_order'][$key]['os_pv_r'] = $value['os_pv_r'];
            $data[$value['increment_id']]['item_order'][$key]['os_sph'] = $value['os_sph'];
            $data[$value['increment_id']]['item_order'][$key]['od_sph'] = $value['od_sph'];
            $data[$value['increment_id']]['item_order'][$key]['os_cyl'] = $value['os_cyl'];
            $data[$value['increment_id']]['item_order'][$key]['od_cyl'] = $value['od_cyl'];
            $data[$value['increment_id']]['item_order'][$key]['os_axis'] = $value['os_axis'];
            $data[$value['increment_id']]['item_order'][$key]['od_axis'] = $value['od_axis'];
            $data[$value['increment_id']]['item_order'][$key]['lens_number'] = $value['lens_number'];
            $data[$value['increment_id']]['item_order'][$key]['web_lens_name'] = $value['web_lens_name'] ?: $value['index_name'];
            $data[$value['increment_id']]['item_order'][$key]['product_id'] = $value['product_id'];
            $data[$value['increment_id']]['base_grand_total'] = $value['base_grand_total'];
            $data[$value['increment_id']]['base_currency_code'] = $value['base_currency_code'];
            $data[$value['increment_id']]['base_grand_total'] = $value['base_grand_total'];
            $data[$value['increment_id']]['payment_method'] = $value['payment_method'];
            $data[$value['increment_id']]['payment_time'] = $value['payment_time'];
        }
        unset($value);
        $cat = '0';
        foreach ($data as  $key => &$value) {
            $num = $cat + 2;


            //网站SKU转换仓库SKU
            $value['prescription_type'] = isset($value['prescription_type']) ? $value['prescription_type'] : '';

            $spreadsheet->getActiveSheet()->setCellValue("A" . ($num), $value['id']); //id
            $spreadsheet->getActiveSheet()->setCellValue("B" . ($num), date('Y-m-d', $value['created_at'])); //日期
            $spreadsheet->getActiveSheet()->setCellValue("C" . ($num), $value['increment_id']); //订单号
            $spreadsheet->getActiveSheet()->setCellValue("D" . ($num), $site_list[$value['site']]); //站点
            switch ($value['order_type']) {
                case 1:
                    $value['order_type'] = '普通订单';
                    break;
                case 2:
                    $value['order_type'] = '批发';
                    break;
                case 3:
                    $value['order_type'] = '网红';
                    break;
                case 4:
                    $value['order_type'] = '补发';
                    break;
                case 5:
                    $value['order_type'] = '补差价';
                    break;
                case 6:
                    $value['order_type'] = '一件代发';
                    break;
            }
            $spreadsheet->getActiveSheet()->setCellValue("E" . ($num), $value['order_type']); //订单类型
            $spreadsheet->getActiveSheet()->setCellValue("F" . ($num), $value['status']); //订单状态
            foreach ($value['item_order'] as $k => $v) {
                $v['od_sph'] = isset($v['od_sph']) ? urldecode($v['od_sph']) : '';
                $v['os_sph'] = isset($v['os_sph']) ? urldecode($v['os_sph']) : '';
                $v['od_cyl'] = isset($v['od_cyl']) ? urldecode($v['od_cyl']) : '';
                $v['os_cyl'] = isset($v['os_cyl']) ? urldecode($v['os_cyl']) : '';
                $cat += 2;
                $spreadsheet->getActiveSheet()->setCellValue("G" . ($cat), $v['item_order_number']); //子单号
                $spreadsheet->getActiveSheet()->setCellValue("H" . ($cat), $v['sku']); //sku
                $spreadsheet->getActiveSheet()->setCellValue("I" . ($cat), '右眼'); //眼球
                $spreadsheet->getActiveSheet()->setCellValue("I" . ($cat + 1), '左眼'); //眼球
                $spreadsheet->getActiveSheet()->setCellValue("J" . ($cat), (float)$v['od_sph'] > 0 ? ' +' . number_format($v['od_sph'] * 1, 2) : ' ' . $v['od_sph']); //SPH
                $spreadsheet->getActiveSheet()->setCellValue("J" . ($cat + 1), (float)$v['os_sph'] > 0 ? ' +' . number_format($v['os_sph'] * 1, 2) : ' ' . $v['os_sph']); //SPH
                $spreadsheet->getActiveSheet()->setCellValue("K" . ($cat), (float)$v['od_cyl'] > 0 ? ' +' . number_format($v['od_cyl'] * 1, 2) : ' ' . $v['od_cyl']); //CYL
                $spreadsheet->getActiveSheet()->setCellValue("K" . ($cat + 1), (float)$v['os_cyl'] > 0 ? ' +' . number_format($v['os_cyl'] * 1, 2) : ' ' . $v['os_cyl']); //CYL
                $spreadsheet->getActiveSheet()->setCellValue("L" . ($cat), $v['od_axis']); //AXI
                $spreadsheet->getActiveSheet()->setCellValue("L" . ($cat + 1), $v['os_axis']); //AXI
                $v['os_add'] = urldecode($v['os_add']);
                $v['od_add'] = urldecode($v['od_add']);

                if ($v['os_add'] && $v['od_add'] && (float)($v['os_add']) * 1 != 0 && (float)($v['od_add']) * 1 != 0) {
                    $spreadsheet->getActiveSheet()->setCellValue("M" . ($cat), $v['od_add']); //ADD
                    $spreadsheet->getActiveSheet()->setCellValue("M" . ($cat + 1), $v['os_add']); //ADD
                } else {

                    if ($v['os_add'] && (float)$v['os_add'] * 1 != 0) {
                        //数值在上一行合并有效，数值在下一行合并后为空
                        $spreadsheet->getActiveSheet()->setCellValue("M" . ($cat), $v['os_add']);
                        $spreadsheet->getActiveSheet()->mergeCells("M" . ($cat) . ":M" . ($cat + 1));
                    } else {
                        //数值在上一行合并有效，数值在下一行合并后为空
                        $spreadsheet->getActiveSheet()->setCellValue("M" . ($cat), $v['od_add']);
                        $spreadsheet->getActiveSheet()->mergeCells("M" . ($cat) . ":M" . ($cat + 1));
                    }
                }

                //            $spreadsheet->getActiveSheet()->setCellValue("C" . ($key * 2 + 2), $value['item_order_number']); //子单号
                //            $spreadsheet->getActiveSheet()->setCellValue("D" . ($key * 2 + 2), $value['sku']); //sku
                //            $spreadsheet->getActiveSheet()->setCellValue("E" . ($key * 2 + 2), $site_list[$value['site']]);//站点
                //            $spreadsheet->getActiveSheet()->setCellValue("F" . ($key * 2 + 2), $distribution_status_list[$value['distribution_status']]);//子单号状态



                if ($v['pdcheck'] == 'on') {
                    $spreadsheet->getActiveSheet()->setCellValue("N" . ($cat), $v['pd_r']); //单PD
                    $spreadsheet->getActiveSheet()->setCellValue("N" . ($cat + 1), $v['pd_l']); //单PD
                } else {
                    $spreadsheet->getActiveSheet()->setCellValue("N" . ($cat), $v['pd']); //PD
                    $spreadsheet->getActiveSheet()->mergeCells("N" . ($cat) . ":N" . ($cat + 1)); //PD
                }

                $lens_name = $lens_list[$v['lens_number']] ?: $v['web_lens_name'];
                $spreadsheet->getActiveSheet()->setCellValue("O" . ($cat), $lens_name); //镜片
                $spreadsheet->getActiveSheet()->setCellValue("P" . ($cat), $v['lens_width']); //镜框宽度
                $spreadsheet->getActiveSheet()->setCellValue("Q" . ($cat), $v['lens_height']); //镜框高度
                $spreadsheet->getActiveSheet()->setCellValue("R" . ($cat), $v['bridge']); //bridge
                $spreadsheet->getActiveSheet()->setCellValue("S" . ($cat), $v['prescription_type']); //处方类型

                $spreadsheet->getActiveSheet()->setCellValue("T" . ($cat), isset($v['od_pv']) ? $v['od_pv'] : ''); //Prism
                $spreadsheet->getActiveSheet()->setCellValue("T" . ($cat + 1), isset($v['os_pv']) ? $v['os_pv'] : ''); //Prism

                $spreadsheet->getActiveSheet()->setCellValue("U" . ($cat), isset($v['od_bd']) ? $v['od_bd'] : ''); //Direct
                $spreadsheet->getActiveSheet()->setCellValue("U" . ($cat + 1), isset($v['os_bd']) ? $v['os_bd'] : ''); //Direct

                $spreadsheet->getActiveSheet()->setCellValue("V" . ($cat), isset($v['od_pv_r']) ? $v['od_pv_r'] : ''); //Prism
                $spreadsheet->getActiveSheet()->setCellValue("V" . ($cat + 1), isset($v['os_pv_r']) ? $v['os_pv_r'] : ''); //Prism

                $spreadsheet->getActiveSheet()->setCellValue("W" . ($cat), isset($v['od_bd_r']) ? $v['od_bd_r'] : ''); //Direct
                $spreadsheet->getActiveSheet()->setCellValue("W" . ($cat + 1), isset($v['os_bd_r']) ? $v['os_bd_r'] : ''); //Direct
                //单元格合并
                $spreadsheet->getActiveSheet()->mergeCells("G" . ($cat) . ":G" . ($cat + 1));
                $spreadsheet->getActiveSheet()->mergeCells("H" . ($cat) . ":H" . ($cat + 1));
                $spreadsheet->getActiveSheet()->mergeCells("O" . ($cat) . ":O" . ($cat + 1));
                $spreadsheet->getActiveSheet()->mergeCells("P" . ($cat) . ":P" . ($cat + 1));
                $spreadsheet->getActiveSheet()->mergeCells("Q" . ($cat) . ":Q" . ($cat + 1));
                $spreadsheet->getActiveSheet()->mergeCells("R" . ($cat) . ":R" . ($cat + 1));
                $spreadsheet->getActiveSheet()->mergeCells("S" . ($cat) . ":S" . ($cat + 1));
            }


            $spreadsheet->getActiveSheet()->setCellValue("X" . ($num), $value['base_grand_total']); //订单金额
            $spreadsheet->getActiveSheet()->setCellValue("Y" . ($num), $value['base_currency_code']); //原币种
            $spreadsheet->getActiveSheet()->setCellValue("Z" . ($num), $value['base_grand_total']); //原支付金额
            $spreadsheet->getActiveSheet()->setCellValue("AA" . ($num), $value['payment_method']); //支付方式
            $spreadsheet->getActiveSheet()->setCellValue("AB" . ($num),  date('Y-m-d H:i:s', $value['payment_time'])); //订单支付时间

            //合并单元格

            $spreadsheet->getActiveSheet()->mergeCells("A" . ($num) . ":A" . ($cat + 1));
            $spreadsheet->getActiveSheet()->mergeCells("B" . ($num) . ":B" . ($cat + 1));
            $spreadsheet->getActiveSheet()->mergeCells("C" . ($num) . ":C" . ($cat + 1));
            $spreadsheet->getActiveSheet()->mergeCells("D" . ($num) . ":D" . ($cat + 1));
            $spreadsheet->getActiveSheet()->mergeCells("E" . ($num) . ":E" . ($cat + 1));
            $spreadsheet->getActiveSheet()->mergeCells("F" . ($num) . ":F" . ($cat + 1));
            $spreadsheet->getActiveSheet()->mergeCells("G" . ($num) . ":G" . ($num + 1));
            $spreadsheet->getActiveSheet()->mergeCells("H" . ($num) . ":H" . ($num + 1));


            $spreadsheet->getActiveSheet()->mergeCells("X" . ($num) . ":X" . ($cat + 1));
            $spreadsheet->getActiveSheet()->mergeCells("Y" . ($num) . ":Y" . ($cat + 1));
            $spreadsheet->getActiveSheet()->mergeCells("Z" . ($num) . ":Z" . ($cat + 1));
            $spreadsheet->getActiveSheet()->mergeCells("AA" . ($num) . ":AA" . ($cat + 1));
            $spreadsheet->getActiveSheet()->mergeCells("AB" . ($num) . ":AB" . ($cat + 1));
        }

        //设置宽度
        $spreadsheet->getActiveSheet()->getColumnDimension('A')->setWidth(12);
        $spreadsheet->getActiveSheet()->getColumnDimension('B')->setWidth(12);
        $spreadsheet->getActiveSheet()->getColumnDimension('C')->setWidth(20);
        $spreadsheet->getActiveSheet()->getColumnDimension('D')->setWidth(20);
        $spreadsheet->getActiveSheet()->getColumnDimension('E')->setWidth(20);
        $spreadsheet->getActiveSheet()->getColumnDimension('N')->setWidth(15);
        $spreadsheet->getActiveSheet()->getColumnDimension('F')->setWidth(15);
        $spreadsheet->getActiveSheet()->getColumnDimension('G')->setWidth(15);
        $spreadsheet->getActiveSheet()->getColumnDimension('H')->setWidth(15);
        $spreadsheet->getActiveSheet()->getColumnDimension('I')->setWidth(15);
        $spreadsheet->getActiveSheet()->getColumnDimension('J')->setWidth(15);
        $spreadsheet->getActiveSheet()->getColumnDimension('K')->setWidth(15);
        $spreadsheet->getActiveSheet()->getColumnDimension('L')->setWidth(15);
        $spreadsheet->getActiveSheet()->getColumnDimension('O')->setWidth(15);
        $spreadsheet->getActiveSheet()->getColumnDimension('P')->setWidth(15);
        $spreadsheet->getActiveSheet()->getColumnDimension('Q')->setWidth(15);
        $spreadsheet->getActiveSheet()->getColumnDimension('R')->setWidth(20);
        $spreadsheet->getActiveSheet()->getColumnDimension('S')->setWidth(15);
        $spreadsheet->getActiveSheet()->getColumnDimension('T')->setWidth(15);
        $spreadsheet->getActiveSheet()->getColumnDimension('U')->setWidth(15);
        $spreadsheet->getActiveSheet()->getColumnDimension('V')->setWidth(15);
        $spreadsheet->getActiveSheet()->getColumnDimension('AA')->setWidth(30);
        $spreadsheet->getActiveSheet()->getColumnDimension('AB')->setWidth(30);
        //自动换行
        $spreadsheet->getDefaultStyle()->getAlignment()->setWrapText(true);
        $spreadsheet->getDefaultStyle()->getFont()->setName('微软雅黑')->setSize(12);

        //设置边框
        $border = [
            'borders' => [
                'allBorders' => [
                    'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN, // 设置border样式
                    'color' => ['argb' => 'FF000000'], // 设置border颜色
                ],
            ],
        ];


        $setBorder = 'A1:' . $spreadsheet->getActiveSheet()->getHighestColumn() . $spreadsheet->getActiveSheet()->getHighestRow();
        $spreadsheet->getActiveSheet()->getStyle($setBorder)->applyFromArray($border);

        $spreadsheet->getActiveSheet()->getStyle('A1:AB' . $spreadsheet->getActiveSheet()->getHighestRow())->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
        $spreadsheet->getActiveSheet()->getStyle('A1:AB' . $spreadsheet->getActiveSheet()->getHighestRow())->getAlignment()->setVertical(\PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER);

        $spreadsheet->setActiveSheetIndex(0);

        $save_name = '配货列表' . date("YmdHis", time());
        //输出07Excel版本
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        //输出名称
        header('Content-Disposition: attachment;filename="' . $save_name . '.xlsx"');
        //禁止缓存
        header('Cache-Control: max-age=0');
        $writer = new Xlsx($spreadsheet);
        $writer->save('php://output');
    }


    /**
     * 标记已打印
     * @Description
     * @return void
     * @since 2020/10/28 14:45:39
     * @author lzh
     */
    public function tag_printed()
    {

        /*****************限制如果有盘点单未结束不能操作配货完成*******************/
        //拣货区盘点时不能操作
        $count = $this->_inventory->alias('a')->join(['fa_inventory_item' => 'b'], 'a.id=b.inventory_id')->where(['a.is_del' => 1, 'a.check_status' => ['in', [0, 1]], 'b.area_id' => 3])->count();
        if ($count > 0) {
            $this->error(__('存在正在盘点的单据,暂无法审核'));
        }
        /****************************end*****************************************/


        $ids = input('id_params/a');
        !$ids && $this->error('请选择要标记的数据');

        //检测子订单状态
        $where = [
            'id' => ['in', $ids],
            'distribution_status' => ['neq', 1]
        ];
        $count = $this->model->where($where)->count();
        0 < $count && $this->error('存在非当前节点的子订单');


        //标记打印状态
        $this->model->startTrans();
        try {
            $distribution_value = $this->model->where(['id' => ['in', $ids]])->field('magento_order_id,order_id, item_order_number,site,wave_order_id')->select();
            $distribution_value = collection($distribution_value)->toArray();
            foreach ($distribution_value as $key => $value) {
                $value['item_order_number'] =  substr($value['item_order_number'], 0, strpos($value['item_order_number'], '-'));
                Order::rulesto_adjust($value['magento_order_id'], $value['item_order_number'], $value['site'], 2, 2);
                $wave_order_id = $value['wave_order_id'];
            }
            //标记状态
            $this->model->where(['id' => ['in', $ids]])->update(['distribution_status' => 2, 'is_print' => 1]);

            //添加波次单打印状态为已打印
            $count = $this->model->alias('a')
                ->join(['fa_order' => 'b'], 'a.order_id=b.id')
                ->where(['wave_order_id' => $wave_order_id, 'is_print' => 0, 'distribution_status' => ['<>', 0]])
                ->where(['b.status' => ['in', ['processing', 'paypal_reversed', 'paypal_canceled_reversal', 'complete', 'delivered']]])
                ->count();
            if ($count > 0) {
                $status = 1;
            } elseif ($count == 0) {
                $status = 2;
            }
            $this->_wave_order->where(['id' => $wave_order_id])->update(['status' => $status]);

            //记录配货日志
            $admin = (object)session('admin');
            DistributionLog::record($admin, $ids, 1, '标记打印完成');

            $this->model->commit();
        } catch (PDOException $e) {
            $this->model->rollback();
            $this->error($e->getMessage());
        } catch (Exception $e) {
            $this->model->rollback();
            $this->error($e->getMessage());
        }
        $this->success('标记成功!', '', 'success', 200);
    }

    /**
     * 打印标签
     *
     * @Description
     * @author wpl
     * @since 2020/11/10 10:36:22 
     * @return void
     */
    public function batch_print_label()
    {
        /*****************限制如果有盘点单未结束不能操作配货完成*******************/
        //拣货区盘点时不能操作
        $count = $this->_inventory->alias('a')->join(['fa_inventory_item' => 'b'], 'a.id=b.inventory_id')->where(['a.is_del' => 1, 'a.check_status' => ['in', [0, 1]], 'b.area_id' => 3])->count();
        if ($count > 0) {
            $this->error(__('存在正在盘点的单据,暂无法审核'));
        }
        /****************************end*****************************************/

        //禁用默认模板
        $this->view->engine->layout(false);
        ob_start();
        $ids = input('ids');
        !$ids && $this->error('缺少参数', url('index?ref=addtabs'));

        //获取子订单列表
        $list = $this->model
            ->alias('a')
            ->field('a.site,a.item_order_number,a.order_id,a.created_at,b.os_add,b.od_add,b.pdcheck,b.prismcheck,b.pd_r,b.pd_l,b.pd,b.od_pv,b.os_pv,b.od_bd,b.os_bd,b.od_bd_r,b.os_bd_r,b.od_pv_r,b.os_pv_r,b.index_name,b.coating_name,b.prescription_type,b.sku,b.od_sph,b.od_cyl,b.od_axis,b.os_sph,b.os_cyl,b.os_axis,b.lens_number,b.web_lens_name,b.gra_certificate,b.ring_size,b.stone_type,b.type,b.Metal')
            ->join(['fa_order_item_option' => 'b'], 'a.option_id=b.id')
            ->where(['a.id' => ['in', $ids]])
            ->order('a.picking_sort asc')
            ->select();
        $list = collection($list)->toArray();
        $order_ids = array_column($list, 'order_id');

        //查询sku映射表
        // $item_res = $this->_item_platform_sku->cache(3600)->where(['platform_sku' => ['in', array_unique($sku_arr)]])->column('sku', 'platform_sku');

        //获取订单数据
        $order_list = $this->_new_order->where(['id' => ['in', array_unique($order_ids)]])->column('total_qty_ordered,increment_id', 'id');

        //查询产品货位号
        $cargo_number = $this->_stock_house->alias('a')->where(['status' => 1, 'b.is_del' => 1, 'a.type' => 1, 'a.area_id' => 3])->join(['fa_store_sku' => 'b'], 'a.id=b.store_id')->column('coding', 'sku');

        //获取更改镜框最新信息
        $change_sku = $this->_work_order_change_sku
            ->alias('a')
            ->join(['fa_work_order_measure' => 'b'], 'a.measure_id=b.id')
            ->where([
                'a.change_type' => 1,
                'a.item_order_number' => ['in', array_column($list, 'item_order_number')],
                'b.operation_type' => 1
            ])
            ->column('a.change_sku', 'a.item_order_number');

        //获取更改镜片最新处方信息
        $change_lens = $this->_work_order_change_sku
            ->alias('a')
            ->join(['fa_work_order_measure' => 'b'], 'a.measure_id=b.id')
            ->where([
                'a.change_type' => 2,
                'a.item_order_number' => ['in', array_column($list, 'item_order_number')],
                'b.operation_type' => 1
            ])
            ->column('a.od_sph,a.od_cyl,a.od_axis,a.od_add,a.pd_r,a.od_pv,a.od_bd,a.od_pv_r,a.od_bd_r,a.os_sph,a.os_cyl,a.os_axis,a.os_add,a.pd_l,a.os_pv,a.os_bd,a.os_pv_r,a.os_bd_r,a.lens_number,a.recipe_type as prescription_type,a.web_lens_name', 'a.item_order_number');
        if ($change_lens) {
            foreach ($change_lens as $key => $val) {
                if ($val['pd_l'] && $val['pd_r']) {
                    $change_lens[$key]['pd'] = '';
                    $change_lens[$key]['pdcheck'] = 'on';
                } else {
                    $change_lens[$key]['pd'] = $val['pd_r'] ?: $val['pd_l'];
                    $change_lens[$key]['pdcheck'] = '';
                }
            }
        }

        //获取镜片编码及名称
        $lens_list = $this->_lens_data->column('lens_name', 'lens_number');

        $data = [];
        foreach ($list as $k => &$v) {
            //更改镜框最新sku
            if ($change_sku[$v['item_order_number']]) {
                $v['sku'] = $change_sku[$v['item_order_number']];
            }

            //转仓库SKU
            $trueSku = $this->_item_platform_sku->getTrueSku(trim($v['sku']), $v['site']);

            //更改镜片最新数据
            if ($change_lens[$v['item_order_number']]) {
                $v = array_merge($v, $change_lens[$v['item_order_number']]);
            }

            $item_order_number = $v['item_order_number'];
            $fileName = ROOT_PATH . "public" . DS . "uploads" . DS . "printOrder" . DS . "distribution" . DS . "new" . DS . "$item_order_number.png";
            $dir = ROOT_PATH . "public" . DS . "uploads" . DS . "printOrder" . DS . "distribution" . DS . "new";
            if (!file_exists($dir)) {
                mkdir($dir, 0777, true);
            }
            $img_url = "/uploads/printOrder/distribution/new/$item_order_number.png";

            //生成条形码
            $this->generate_barcode_new($item_order_number, $fileName);
            $v['created_at'] = date('Y-m-d H:i:s', $v['created_at']);
            $v['img_url'] = $img_url;

            //序号
            $serial = explode('-', $item_order_number);
            $v['serial'] = $serial[1];
            $v['total_qty_ordered'] = $order_list[$v['order_id']]['total_qty_ordered'];
            $v['increment_id'] = $order_list[$v['order_id']]['increment_id'];

            //库位号
            $v['coding'] = $cargo_number[$trueSku];

            //判断双ADD逻辑
            if ($v['os_add'] && $v['od_add'] && (float)$v['os_add'] * 1 != 0 && (float)$v['od_add'] * 1 != 0) {
                $v['total_add'] = '';
            } else {
                if ($v['os_add'] && (float)$v['os_add'] * 1 != 0) {
                    $v['total_add'] = $v['os_add'];
                } else {
                    $v['total_add'] = $v['od_add'];
                }
            }

            $v['web_lens_name'] = $v['web_lens_name'] ?: $v['index_name'];
            //获取镜片名称
            $v['lens_name'] = $lens_list[$v['lens_number']] ?: $v['web_lens_name'];

            $data[] = $v;
        }
        $this->assign('list', $data);
        $html = $this->view->fetch('print_label');
        echo $html;
    }


    public function save_order_statsu()
    {
        $map['increment_id'] = ['in', [
            '100181408',
            '400409680',
            '100180688',
            '100179774',
            '400414709',
            '400425817',
            '500016847',
            '130079900',
            '300044713',
            '400425744',
            '400421790',
            '130078015',
            '400426437',
            '430241978',
            '430242375',
            '430238882',
            '600122332',
            '600122873',
            '100181629',
            '400426702',
            '400427440',
            '400421813',
        ]];
        $model = Db::connect('database.db_mojing_order');
        $data = $model->table('fa_order')->where($map)->field('id')->select();
        $result = array_reduce($data, function ($result, $value) {
            return array_merge($result, array_values($value));
        }, array());
        $where['order_id'] = ['in', $result];
        $values['distribution_status'] = 9;
        $values['updated_at'] = time();
        $model->table('fa_order_item_process')->where($where)->update($values);
        $cat['combine_status'] = 1;
        $cat['store_house_id'] = 0;
        $cat['check_status'] = 1;
        $cat['check_time'] = time();
        $model->table('fa_order_process')->where($where)->update($cat);

        //记录配货日志
        $admin = (object)session('admin');
        DistributionLog::record($admin, '100181408', 7, '将100181408,400409680,100180688,100179774,400414709, 400425817,500016847,130079900,300044713等部分订单配货状态改为已合单');
    }


    /**
     * 生成新的条形码
     */
    protected function generate_barcode_new($text, $fileName)
    {
        // 引用barcode文件夹对应的类
        Loader::import('BCode.BCGFontFile', EXTEND_PATH);
        //Loader::import('BCode.BCGColor',EXTEND_PATH);
        Loader::import('BCode.BCGDrawing', EXTEND_PATH);
        // 条形码的编码格式
        // Loader::import('BCode.BCGcode39',EXTEND_PATH,'.barcode.php');
        Loader::import('BCode.BCGcode128', EXTEND_PATH, '.barcode.php');

        // $code = '';
        // 加载字体大小
        $font = new \BCGFontFile(EXTEND_PATH . '/BCode/font/Arial.ttf', 18);
        //颜色条形码
        $color_black = new \BCGColor(0, 0, 0);
        $color_white = new \BCGColor(255, 255, 255);
        $label = new \BCGLabel();
        $label->setPosition(\BCGLabel::POSITION_TOP);
        $label->setText('');
        $label->setFont($font);
        $drawException = null;
        try {
            // $code = new \BCGcode39();
            $code = new \BCGcode128();
            $code->setScale(4);
            $code->setThickness(18); // 条形码的厚度
            $code->setForegroundColor($color_black); // 条形码颜色
            $code->setBackgroundColor($color_white); // 空白间隙颜色
            $code->setFont(0); //设置字体
            $code->addLabel($label); //设置字体
            $code->parse($text); // 条形码需要的数据内容
        } catch (\Exception $exception) {
            $drawException = $exception;
        }
        //根据以上条件绘制条形码
        $drawing = new \BCGDrawing('', $color_white);
        if ($drawException) {
            $drawing->drawException($drawException);
        } else {
            $drawing->setBarcode($code);
            if ($fileName) {
                // echo 'setFilename<br>';
                $drawing->setFilename($fileName);
            }
            $drawing->draw();
        }
        // 生成PNG格式的图片
        header('Content-Type: image/png');
        // header('Content-Disposition:attachment; filename="barcode.png"'); //自动下载
        $drawing->finish(\BCGDrawing::IMG_FORMAT_PNG);
    }

    /**
     * 更新配货状态
     *
     * @Description
     * @return void
     * @since 2020/10/28 14:45:39
     * @author lzh
     */
    public function set_status()
    {
        $ids = input('id_params/a');
        !$ids && $this->error('请选择要标记的数据');

        $check_status = input('status');
        empty($check_status) && $this->error('状态值不能为空');

        //检测异常状态
        $abnormal_count = $this->_distribution_abnormal
            ->where(['item_process_id' => ['in', $ids], 'status' => 1])
            ->count();
        0 < $abnormal_count && $this->error('有异常待处理的子订单');

        //检测配货状态
        $item_list = $this->model
            ->field('id,site,distribution_status,magento_order_id,order_id,option_id,sku,item_order_number,order_prescription_type')
            ->where(['id' => ['in', $ids]])
            ->select();
        $item_list = collection($item_list)->toArray();

        $order_ids = [];
        $option_ids = [];
        $item_order_numbers = [];
        foreach ($item_list as $value) {
            $value['distribution_status'] != $check_status && $this->error('存在非当前节点的子订单');
            $order_ids[] = $value['order_id'];
            $option_ids[] = $value['option_id'];
            $item_order_numbers[] = $value['item_order_number'];
        }

        //查询订单号
        $order_ids = array_unique($order_ids);
        $increment_ids = $this->_new_order
            ->where(['id' => ['in', $order_ids], 'status' => 'processing'])
            ->column('increment_id');
        count($order_ids) != count($increment_ids) && $this->error('当前订单状态不可操作');

        //检测是否有工单未处理
        $check_work_order = $this->_work_order_measure
            ->alias('a')
            ->field('a.item_order_number,a.measure_choose_id')
            ->join(['fa_work_order_list' => 'b'], 'a.work_id=b.id')
            ->where([
                'a.operation_type' => 0,
                'b.platform_order' => ['in', $increment_ids],
                'b.work_status' => ['in', [1, 2, 3, 5]]
            ])
            ->select();
        if ($check_work_order) {
            foreach ($check_work_order as $val) {
                (3 == $val['measure_choose_id'] //主单取消措施未处理
                    ||
                    in_array($val['item_order_number'], $item_order_numbers) //子单措施未处理:更改镜框18、更改镜片19、取消20
                )

                    && $this->error('子单号：' . $val['item_order_number'] . '有工单未处理');
                if ($val['measure_choose_id'] == 21) {
                    $this->error(__('有工单存在暂缓措施未处理，无法操作'), [], 405);
                }
            }
        }

        //是否有子订单取消
        $check_cancel_order = $this->_work_order_change_sku
            ->alias('a')
            ->join(['fa_work_order_measure' => 'b'], 'a.measure_id=b.id')
            ->where([
                'a.change_type' => 3,
                'a.item_order_number' => ['in', $item_order_numbers],
                'b.operation_type' => 1
            ])
            ->value('a.item_order_number');
        $check_cancel_order && $this->error('子单号：' . $check_cancel_order . ' 已取消');

        //获取订单购买总数
        $total_list = $this->_new_order
            ->where(['id' => ['in', array_unique($order_ids)]])
            ->column('total_qty_ordered', 'id');

        //获取子订单处方数据
        $option_list = $this->_new_order_item_option
            ->field('id,is_print_logo')
            ->where(['id' => ['in', array_unique($option_ids)]])
            ->select();
        $option_list = array_column($option_list, NULL, 'id');

        //状态类型
        $status_arr = [
            2 => '配货',
            3 => '配镜片',
            4 => '加工',
            5 => '印logo',
            6 => '成品质检',
            8 => '合单'
        ];

        //操作人信息
        $admin = (object)session('admin');

        $this->_item->startTrans();
        $this->_stock_log->startTrans();
        $this->_new_order_process->startTrans();
        $this->model->startTrans();

        try {
            //更新状态
            foreach ($item_list as $value) {
                //下一步状态
                if (2 == $check_status) {
                    //配货完成
                    $node_status = 3;
                    //根据处方类型字段order_prescription_type(现货处方镜、定制处方镜)判断是否需要配镜片
                    if (in_array($value['order_prescription_type'], [2, 3])) {
                        $save_status = 3;
                    } else {
                        if ($option_list[$value['option_id']]['is_print_logo']) {
                            $save_status = 5; //待印logo
                        } else {
                            if ($total_list[$value['order_id']]['total_qty_ordered'] > 1) {
                                $save_status = 7;
                            } else {
                                $save_status = 9;
                            }
                        }
                    }

                    //获取true_sku
                    $true_sku = $this->_item_platform_sku->getTrueSku($value['sku'], $value['site']);

                    //获取配货占用库存
                    $item_before = $this->_item
                        ->field('distribution_occupy_stock')
                        ->where(['sku' => $true_sku])
                        ->find();

                    //增加配货占用库存
                    $this->_item
                        ->where(['sku' => $true_sku])
                        ->setInc('distribution_occupy_stock', 1);

                    //记录库存日志
                    $this->_stock_log->setData([
                        'type' => 2,
                        'site' => $value['site'],
                        'modular' => 2,
                        'change_type' => 4,
                        'source' => 1,
                        'sku' => $true_sku,
                        'number_type' => 2,
                        'order_number' => $value['item_order_number'],
                        'distribution_stock_before' => $item_before['distribution_occupy_stock'],
                        'distribution_stock_change' => 1,
                        'create_person' => session('admin.nickname'),
                        'create_time' => time()
                    ]);
                } elseif (3 == $check_status) {
                    //配镜片完成
                    $node_status = 4;
                    if (in_array($value['order_prescription_type'], [2, 3])) {
                        $save_status = 4;
                    } else {
                        if ($option_list[$value['option_id']]['is_print_logo']) {

                            $save_status = 5; //待印logo
                        } else {

                            if ($total_list[$value['order_id']]['total_qty_ordered'] > 1) {
                                $save_status = 7;
                            } else {
                                $save_status = 9;
                            }
                        }
                    }

                    // $save_status = 4;
                } elseif (4 == $check_status) {
                    if ($option_list[$value['option_id']]['is_print_logo']) {
                        //需要印logo
                        $node_status = 5;
                        $save_status = 5;
                    } else {
                        //无需印logo
                        $node_status = 13;
                        $save_status = 6;
                    }
                } elseif (5 == $check_status) {
                    //印logo完成 质检中
                    $node_status = 6;
                    $save_status = 6;
                } elseif (6 == $check_status) {
                    //质检完成 已出库
                    $node_status = 7;
                    if ($total_list[$value['order_id']]['total_qty_ordered'] > 1) {
                        $save_status = 7;
                    } else {
                        $save_status = 9;
                    }
                }

                //订单主表标记已合单
                if (9 == $save_status) {
                    $this->_new_order_process->where(['order_id' => $value['order_id']])
                        ->update(['combine_status' => 1, 'check_status' => 0, 'combine_time' => time()]);
                }

                $this->model->where(['id' => $value['id']])->update(['distribution_status' => $save_status]);
                //操作成功记录
                DistributionLog::record($admin, $value['id'], $check_status, $status_arr[$check_status] . '完成');
                //节点记录
                //将订单号截取处理
                $value['item_order_number'] =  substr($value['item_order_number'], 0, strpos($value['item_order_number'], '-'));
                Order::rulesto_adjust($value['magento_order_id'], $value['item_order_number'], $value['site'], 2, $node_status);
            }

            $this->_item->commit();
            $this->_stock_log->commit();
            $this->_new_order_process->commit();
            $this->model->commit();
        } catch (PDOException $e) {
            $this->_item->rollback();
            $this->_stock_log->rollback();
            $this->_new_order_process->rollback();
            $this->model->rollback();
            $this->error($e->getMessage());
        } catch (Exception $e) {
            $this->_item->rollback();
            $this->_stock_log->rollback();
            $this->_new_order_process->rollback();
            $this->model->rollback();
            $this->error($e->getMessage());
        }

        $this->success('操作成功!', '', 'success', 200);
    }

    /**
     * 成检拒绝操作
     *
     * @Description
     * @return void
     * @since 2020/10/28 14:45:39
     * @author lzh
     */
    public function finish_refuse()
    {
        $ids = input('id_params/a');
        !$ids && $this->error('请选择要标记的数据');

        $reason = input('reason');
        !in_array($reason, [1, 2, 3, 4]) && $this->error('拒绝原因错误');

        //检测异常状态
        $abnormal_count = $this->_distribution_abnormal
            ->where(['item_process_id' => ['in', $ids], 'status' => 1])
            ->count();
        0 < $abnormal_count && $this->error('有异常待处理的子订单');

        //获取配货信息
        $item_list = $this->model
            ->field('id,site,sku,distribution_status,order_id,item_order_number')
            ->where(['id' => ['in', $ids]])
            ->select();
        empty($item_list) && $this->error('数据不存在');

        //检测配货状态
        $order_ids = [];
        $item_order_numbers = [];
        foreach ($item_list as $value) {
            6 != $value['distribution_status'] && $this->error('存在非当前节点的子订单');
            $order_ids[] = $value['order_id'];
            $item_order_numbers[] = $value['item_order_number'];
        }

        //查询订单号
        $order_ids = array_unique($order_ids);
        $increment_ids = $this->_new_order
            ->where(['id' => ['in', $order_ids], 'status' => 'processing'])
            ->column('increment_id');
        count($order_ids) != count($increment_ids) && $this->error('当前订单状态不可操作');

        //检测是否有工单未处理
        $check_work_order = $this->_work_order_measure
            ->alias('a')
            ->field('a.item_order_number,a.measure_choose_id')
            ->join(['fa_work_order_list' => 'b'], 'a.work_id=b.id')
            ->where([
                'a.operation_type' => 0,
                'b.platform_order' => ['in', $increment_ids],
                'b.work_status' => ['in', [1, 2, 3, 5]]
            ])
            ->select();
        if ($check_work_order) {
            foreach ($check_work_order as $val) {
                (3 == $val['measure_choose_id'] //主单取消措施未处理
                    ||
                    in_array($val['item_order_number'], $item_order_numbers) //子单措施未处理:更改镜框18、更改镜片19、取消20
                )
                    && $this->error('子单号：' . $val['item_order_number'] . '有工单未处理');
            }
        }

        //是否有子订单取消
        $check_cancel_order = $this->_work_order_change_sku
            ->alias('a')
            ->join(['fa_work_order_measure' => 'b'], 'a.measure_id=b.id')
            ->where([
                'a.change_type' => 3,
                'a.item_order_number' => ['in', $item_order_numbers],
                'b.operation_type' => 1
            ])
            ->value('a.item_order_number');
        $check_cancel_order && $this->error('子单号：' . $check_cancel_order . ' 已取消');

        //状态
        $status_arr = [
            1 => ['status' => 4, 'name' => '质检拒绝：加工调整'],
            2 => ['status' => 2, 'name' => '质检拒绝：镜架报损'],
            3 => ['status' => 3, 'name' => '质检拒绝：镜片报损'],
            4 => ['status' => 5, 'name' => '质检拒绝：logo调整']
        ];
        $status = $status_arr[$reason]['status'];

        //操作人信息
        $admin = (object)session('admin');

        $this->model->startTrans();
        $this->_item->startTrans();
        $this->_item_platform_sku->startTrans();
        $this->_stock_log->startTrans();
        try {
            $save_data['distribution_status'] = $status;
            //如果回退到待加工步骤之前，清空定制片库位ID及定制片处理状态
            if (4 > $status) {
                $save_data['temporary_house_id'] = 0;

                $save_data['customize_status'] = 0;
            }

            //子订单状态回滚
            $this->model->where(['id' => ['in', $ids]])->update($save_data);

            /*//回退到待配货，解绑条形码
            if (2 == $status) {
                $this->_product_bar_code_item
                    ->allowField(true)
                    ->isUpdate(true, ['item_order_number' => ['in', $item_order_numbers]])
                    ->save(['item_order_number' => '']);
            }*/

            //记录日志
            DistributionLog::record($admin, array_column($item_list, 'id'), 6, $status_arr[$reason]['name']);

            //更新状态

            //质检拒绝：镜架报损，扣减可用库存、配货占用、总库存、虚拟仓库存
            if (2 == $reason) {
                foreach ($item_list as $value) {
                    //仓库sku、库存
                    $platform_info = $this->_item_platform_sku
                        ->field('sku,stock')
                        ->where(['platform_sku' => $value['sku'], 'platform_type' => $value['site']])
                        ->find();
                    $true_sku = $platform_info['sku'];

                    //检验库存
                    $stock_arr = $this->_item
                        ->where(['sku' => $true_sku])
                        ->field('stock,available_stock,distribution_occupy_stock')
                        ->find();

                    //扣减可用库存、配货占用、总库存
                    $this->_item
                        ->where(['sku' => $true_sku])
                        ->dec('available_stock', 1)
                        ->dec('distribution_occupy_stock', 1)
                        ->dec('stock', 1)
                        ->update();

                    //扣减总库存自动生成一条出库单 审核通过 分类为成品质检报损
                    $outstock['out_stock_number'] = 'OUT' . date('YmdHis') . rand(100, 999) . rand(100, 999);
                    $outstock['type_id'] = 2;
                    $outstock['remark'] = 'PDA质检拒绝：镜架报损自动生成出库单';
                    $outstock['status'] = 2;
                    $outstock['create_person'] = session('admin.nickname');
                    $outstock['createtime'] = date('Y-m-d H:i:s', time());
                    $outstock['platform_id'] = $value['site'];
                    $outstock_id = $this->_outstock->insertGetid($outstock);

                    $outstock_item['sku'] = $true_sku;
                    $outstock_item['out_stock_num'] = 1;
                    $outstock_item['out_stock_id'] = $outstock_id;
                    $this->_outstock_item->insert($outstock_item);



                    //条码出库
                    $this->_product_bar_code_item
                        ->allowField(true)
                        ->isUpdate(true, ['item_order_number' => ['in', $item_order_numbers]])
                        ->save(['out_stock_time' => date('Y-m-d H:i:s'), 'library_status' => 2, 'is_loss_report_out' => 1, 'out_stock_id' => $outstock_id]);

                    //计算出库成本
                    $financecost = new \app\admin\model\finance\FinanceCost();
                    $financecost->outstock_cost($outstock_id, $outstock['out_stock_number']);

                    //扣减虚拟仓库存
                    $this->_item_platform_sku
                        ->where(['sku' => $true_sku, 'platform_type' => $value['site']])
                        ->dec('stock', 1)
                        ->update();

                    //记录库存日志
                    $this->_stock_log->setData([
                        'type' => 2,
                        'site' => $value['site'],
                        'modular' => 3,
                        'change_type' => 5,
                        'source' => 2,
                        'sku' => $true_sku,
                        'number_type' => 2,
                        'order_number' => $value['item_order_number'],
                        'available_stock_before' => $stock_arr['available_stock'],
                        'available_stock_change' => -1,
                        'distribution_stock_before' => $stock_arr['distribution_occupy_stock'],
                        'distribution_stock_change' => -1,
                        'stock_before' => $stock_arr['stock'],
                        'stock_change' => -1,
                        'fictitious_before' => $platform_info['stock'],
                        'fictitious_change' => -1,
                        'create_person' => session('admin.nickname'),
                        'create_time' => time()
                    ]);
                }
            }

            $this->model->commit();
            $this->_item->commit();
            $this->_item_platform_sku->commit();
            $this->_stock_log->commit();
        } catch (PDOException $e) {
            $this->model->rollback();
            $this->_item->rollback();
            $this->_item_platform_sku->rollback();
            $this->_stock_log->rollback();
            $this->error($e->getMessage());
        } catch (Exception $e) {
            $this->model->rollback();
            $this->_item->rollback();
            $this->_item_platform_sku->rollback();
            $this->_stock_log->rollback();
            $this->error($e->getMessage());
        }
        $this->success('操作成功!', '', 'success', 200);
    }

    /**
     * 处理异常
     *
     * @Description
     * @return void
     * @since 2020/10/28 14:45:39
     * @author lzh
     */
    public function handle_abnormal($ids = null)
    {
        //检测配货状态
        $item_info = $this->model
            ->field('id,site,sku,distribution_status,abnormal_house_id,temporary_house_id,item_order_number')
            ->where(['id' => $ids])
            ->find();
        empty($item_info) && $this->error('子订单不存在');
        empty($item_info['abnormal_house_id']) && $this->error('当前子订单未标记异常');

        //检测异常状态
        $abnormal_info = $this->_distribution_abnormal
            ->field('id,type,remark')
            ->where(['item_process_id' => $ids, 'status' => 1])
            ->find();
        empty($abnormal_info) && $this->error('当前子订单异常信息获取失败');

        //状态列表
        $status_arr = [
            //1 => '待打印标签',
            2 => '待配货',
            3 => '待配镜片',
            4 => '待加工',
            5 => '待印logo',
            6 => '待成品质检'
        ];

        switch ($item_info['distribution_status']) {
            case $item_info['distribution_status'] < 4:
                unset($status_arr);
                $status_arr = [];
                break;
            case 4:
                unset($status_arr[4]);
                unset($status_arr[5]);
                unset($status_arr[6]);
                break;
            case 5:
                unset($status_arr[5]);
                unset($status_arr[6]);
                break;
            case 6:
                unset($status_arr[6]);
                break;
            case 7:
                unset($status_arr[1]);
                unset($status_arr[2]);
                unset($status_arr[3]);
                unset($status_arr[4]);
                unset($status_arr[5]);
                break;
        }
        if ($item_info['distribution_status'] == 3) {
            $status_arr[2] = '待配货';
        }
        //核实地址
        if ($abnormal_info['type'] == 13) {
            $status_arr = [];
        }

        //异常原因列表
        $abnormal_arr = [
            1 => '配货缺货',
            2 => '商品条码贴错',
            3 => '核实处方',
            4 => '镜片缺货',
            5 => '镜片重做',
            6 => '定制片超时',
            7 => '不可加工',
            8 => '镜架加工报损',
            9 => '镜片加工报损',
            10 => 'logo不可加工',
            11 => '镜架印logo报损',
            12 => '合单缺货',
            13 => '核实地址',
            14 => '物流退件',
            15 => '客户退件'
        ];

        if ($this->request->isAjax()) {
            //操作人信息
            $admin = (object)session('admin');

            //检测状态
            $check_status = [];

            //根据返回节点处理相关逻辑
            $status = input('status');
            switch ($status) {
                case 1:
                    $check_status = [4, 5, 6];
                    break;
                case 2:
                    $check_status = [4, 5, 6];
                    break;
                case 3:
                    $check_status = [4, 5, 6];
                    break;
                case 4:
                    $check_status = [5, 6];
                    break;
                case 5:
                    $check_status = [6];
                    break;
                case 6:
                    $check_status = [7];
                    break;
            }

            //检测状态
            !in_array($item_info['distribution_status'], $check_status) && $this->error('当前子订单不可返回至此节点');

            $this->model->startTrans();
            $this->_distribution_abnormal->startTrans();
            $this->_item_platform_sku->startTrans();
            $this->_item->startTrans();
            $this->_outstock->startTrans();
            $this->_stock_log->startTrans();
            $this->_product_bar_code_item->startTrans();
            try {
                //异常库位占用数量-1
                $this->_stock_house
                    ->where(['id' => $item_info['abnormal_house_id']])
                    ->setDec('occupy', 1);

                //子订单状态回滚
                $save_data = [
                    'distribution_status' => $status, //配货状态
                    'abnormal_house_id' => 0 //异常库位ID
                ];

                //如果回退到待加工步骤之前，清空定制片库位ID及定制片处理状态
                if (4 > $status) {
                    $save_data['temporary_house_id'] = 0;
                    $save_data['customize_status'] = 0;

                    //定制片库位占用数量-1
                    if ($item_info['temporary_house_id']) {
                        $this->_stock_house
                            ->where(['id' => $item_info['temporary_house_id']])
                            ->setDec('occupy', 1);
                    }
                }

                $this->model->where(['id' => $ids])->update($save_data);

                /*//回退到待配货、待打印标签，解绑条形码
                if (3 > $status) {
                    $this->_product_bar_code_item
                        ->allowField(true)
                        ->isUpdate(true, ['item_order_number' => $item_info['item_order_number']])
                        ->save(['item_order_number' => '']);
                }*/

                //标记处理异常状态及时间
                $this->_distribution_abnormal->where(['id' => $abnormal_info['id']])->update(['status' => 2, 'do_time' => time(), 'do_person' => $admin->nickname]);

                //配货操作内容
                $remark = '处理异常：' . $abnormal_arr[$abnormal_info['type']] . ',当前节点：' . $status_arr[$item_info['distribution_status']] . ',返回节点：' . $status_arr[$status];

                //回滚至待配货扣减可用库存、虚拟仓库存、配货占用、总库存
                if (2 == $status) {
                    //获取工单更改镜框最新信息
                    $change_sku = $this->_work_order_change_sku
                        ->alias('a')
                        ->join(['fa_work_order_measure' => 'b'], 'a.measure_id=b.id')
                        ->where([
                            'a.change_type' => 1,
                            'a.item_order_number' => $item_info['item_order_number'],
                            'b.operation_type' => 1
                        ])
                        ->order('a.id', 'desc')
                        ->limit(1)
                        ->value('a.change_sku');
                    if (!empty($change_sku)) { //存在已完成的更改镜片的工单，替换更改的sku
                        $item_info['sku'] = $change_sku;
                    }
                    //仓库sku、库存
                    $platform_info = $this->_item_platform_sku
                        ->field('sku,stock')
                        ->where(['platform_sku' => $item_info['sku'], 'platform_type' => $item_info['site']])
                        ->find();
                    $true_sku = $platform_info['sku'];

                    //检验库存
                    $stock_arr = $this->_item
                        ->where(['sku' => $true_sku])
                        ->field('stock,available_stock,distribution_occupy_stock')
                        ->find();

                    //扣减虚拟仓库存
                    $this->_item_platform_sku
                        ->where(['sku' => $true_sku, 'platform_type' => $item_info['site']])
                        ->dec('stock', 1)
                        ->update();

                    //扣减可用库存、配货占用库存、总库存
                    $this->_item
                        ->where(['sku' => $true_sku])
                        ->dec('available_stock', 1)
                        ->dec('distribution_occupy_stock', 1)
                        ->dec('stock', 1)
                        ->update();

                    //扣减总库存自动生成一条出库单 审核通过 分类为成品质检报损
                    $outstock['out_stock_number'] = 'OUT' . date('YmdHis') . rand(100, 999) . rand(100, 999);
                    //加工报损
                    $outstock['type_id'] = 4;
                    $outstock['remark'] = '回滚至待配货自动生成出库单';
                    $outstock['status'] = 2;
                    $outstock['create_person'] = session('admin.nickname');
                    $outstock['createtime'] = date('Y-m-d H:i:s', time());
                    $outstock['platform_id'] = $item_info['site'];
                    $outstock_id = $this->_outstock->insertGetid($outstock);

                    $outstock_item['sku'] = $true_sku;
                    $outstock_item['out_stock_num'] = 1;
                    $outstock_item['out_stock_id'] = $outstock_id;
                    $this->_outstock_item->insert($outstock_item);

                    //条码出库
                    $this->_product_bar_code_item
                        ->allowField(true)
                        ->isUpdate(true, ['item_order_number' => $item_info['item_order_number']])
                        ->save(['out_stock_time' => date('Y-m-d H:i:s'), 'library_status' => 2, 'is_loss_report_out' => 1, 'out_stock_id' => $outstock_id]);

                    //计算出库成本
                    $financecost = new \app\admin\model\finance\FinanceCost();
                    $financecost->outstock_cost($outstock_id, $outstock['out_stock_number']);

                    //记录库存日志
                    $this->_stock_log->setData([
                        'type' => 2,
                        'site' => $item_info['site'],
                        'modular' => 5,
                        'change_type' => 4 == $item_info['distribution_status'] ? 8 : 9,
                        'source' => 1,
                        'sku' => $true_sku,
                        'number_type' => 2,
                        'order_number' => $item_info['item_order_number'],
                        'available_stock_before' => $stock_arr['available_stock'],
                        'available_stock_change' => -1,
                        'distribution_stock_before' => $stock_arr['distribution_occupy_stock'],
                        'distribution_stock_change' => -1,
                        'stock_before' => $stock_arr['stock'],
                        'stock_change' => -1,
                        'fictitious_before' => $platform_info['stock'],
                        'fictitious_change' => -1,
                        'create_person' => session('admin.nickname'),
                        'create_time' => time()
                    ]);

                    $remark .= ',扣减可用库存、虚拟仓库存、配货占用库存、总库存';
                }

                //记录日志
                DistributionLog::record($admin, $ids, 10, $remark);

                $this->model->commit();
                $this->_distribution_abnormal->commit();
                $this->_item_platform_sku->commit();
                $this->_item->commit();
                $this->_outstock->commit();
                $this->_stock_log->commit();
                $this->_product_bar_code_item->commit();
            } catch (PDOException $e) {
                $this->model->rollback();
                $this->_distribution_abnormal->rollback();
                $this->_item_platform_sku->rollback();
                $this->_item->rollback();
                $this->_outstock->rollback();
                $this->_stock_log->rollback();
                $this->_product_bar_code_item->rollback();
                $this->error($e->getMessage());
            } catch (Exception $e) {
                $this->model->rollback();
                $this->_distribution_abnormal->rollback();
                $this->_item_platform_sku->rollback();
                $this->_item->rollback();
                $this->_outstock->rollback();
                $this->_stock_log->rollback();
                $this->_product_bar_code_item->rollback();
                $this->error($e->getMessage());
            }

            $this->success('处理成功!', '', 'success', 200);
        }

        $this->view->assign("status_arr", $status_arr);
        $this->view->assign("abnormal_arr", $abnormal_arr);
        $this->view->assign("row", $item_info);
        $this->view->assign("abnormal_info", $abnormal_info);
        return $this->view->fetch();
    }

    /**
     * 操作记录
     *
     * @Description
     * @return void
     * @since 2020/10/28 14:45:39
     * @author lzh
     */
    public function operation_log($ids = null)
    {
        //检测配货状态
        $item_info = $this->model
            ->field('id')
            ->where(['id' => $ids])
            ->find();
        empty($item_info) && $this->error('子订单不存在');

        //检测异常状态
        $list = (new DistributionLog())
            ->where(['item_process_id' => $ids])
            ->select();
        $list = collection($list)->toArray();

        $this->view->assign("list", $list);
        return $this->view->fetch();
    }

    /**
     * 批量创建工单
     *
     * @Description
     * @return void
     * @since 2020/11/20 14:54:39
     * @author wgj
     */
    public function add()
    {
        $ids = input('id_params/a'); //子单ID
        //!$ids && $this->error('请选择要创建工单的数据');
        if ($ids) {

            //获取子单号
            $item_process_numbers = $this->model->where(['id' => ['in', $ids]])->column('item_order_number');
            !$item_process_numbers && $this->error('子单不存在');

            //判断子单是否为同一主单
            $order_id = $this->model
                ->alias('a')
                ->join(['fa_order' => 'b'], 'a.order_id=b.id')
                ->where(['a.id' => ['in', $ids]])
                ->column('b.increment_id');
            !$order_id && $this->error('订单不存在');
            $order_id = array_unique(array_filter($order_id)); //数组去空、去重
            1 < count($order_id) && $this->error('所选子订单的主单不唯一');

            //检测是否有未处理工单
            $check_work_order = $this->_work_order_list
                ->where([
                    'work_status' => ['in', [1, 2, 3, 5]],
                    'platform_order' => $order_id[0]
                ])
                ->value('id');
            $check_work_order && $this->error('当前订单有未完成工单，不可创建工单');
        }

        //调用创建工单接口
        //saleaftermanage/work_order_list/add?order_number=123&order_item_numbers=35456,23465,1111
        $request = Request::instance();
        $url_domain = $request->domain();
        $url_root = $request->root();
        $url = $url_domain . $url_root;
        if ($ids) {
            $url = $url . '/saleaftermanage/work_order_list/add?order_number=' . $order_id[0] . '&order_item_numbers=' . implode(',', $item_process_numbers);
        } else {
            $url = $url . '/saleaftermanage/work_order_list/add';
        }
        //http://www.mojing.cn/admin_1biSSnWyfW.php/saleaftermanage/work_order_list/add?order_number=859063&order_item_numbers=430224120-03,430224120-04
        $this->success('跳转!', '', ['url' => $url], 200);
    }

    /**
     * 配货旧数据处理
     *
     * @Description
     * @return mixed
     * @since 2020/12/8 10:54:39
     * @author lzh
     */
    function legacy_data()
    {
        ini_set('memory_limit', '1024M');
        //站点列表
        $site_arr = [
            // 1 => [
            //     'name' => 'zeelool',
            //     'obj' => new \app\admin\model\order\printlabel\Zeelool,
            // ],
            // 2 => [
            //     'name' => 'voogueme',
            //     'obj' => new \app\admin\model\order\printlabel\Voogueme,
            // ], 
            3 => [
                'name' => 'nihao',
                'obj' => new \app\admin\model\order\printlabel\Nihao,
            ],
            4 => [
                'name' => 'weseeoptical',
                'obj' => new \app\admin\model\order\printlabel\Weseeoptical,
            ],
            // 5 => [
            //     'name' => 'meeloog',
            //     'obj' => new \app\admin\model\order\printlabel\Meeloog,
            // ],
            9 => [
                'name' => 'zeelool_es',
                'obj' => new \app\admin\model\order\printlabel\ZeeloolEs,
            ],
            10 => [
                'name' => 'zeelool_de',
                'obj' => new \app\admin\model\order\printlabel\ZeeloolDe,
            ],
            11 => [
                'name' => 'zeelool_jp',
                'obj' => new \app\admin\model\order\printlabel\ZeeloolJp,
            ]
        ];

        foreach ($site_arr as $key => $item) {
            echo $item['name'] . " Start\n";
            //获取已质检旧数据
            $list = $item['obj']
                ->field('entity_id,increment_id,
                custom_print_label_created_at_new,custom_print_label_person_new,
                custom_match_frame_created_at_new,custom_match_frame_person_new,
                custom_match_lens_created_at_new,custom_match_lens_person_new,
                custom_match_factory_created_at_new,custom_match_factory_person_new,
                custom_match_delivery_created_at_new,custom_match_delivery_person_new
               ')
                ->where([
                    'custom_is_delivery_new' => 1,
                    'custom_match_delivery_created_at_new' => ['between', ['2019-10-01', '2020-10-01']]
                ])
                ->select();

            $count = count($list);
            $handle = 0;
            if ($list) {
                foreach ($list as $value) {
                    try {
                        //主单业务表：fa_order_process：check_status=审单状态、check_time=审单时间、combine_status=合单状态、combine_time=合单状态
                        $do_time = strtotime($value['custom_match_delivery_created_at_new']) + 28800;
                        $this->_new_order_process
                            ->allowField(true)
                            ->save(
                                ['check_status' => 1, 'check_time' => $do_time, 'combine_status' => 1, 'combine_time' => $do_time],
                                ['entity_id' => $value['entity_id'], 'site' => $key]
                            );

                        //获取子单表id集
                        $item_process_ids = $this->model->where(['magento_order_id' => $value['entity_id'], 'site' => $key])->column('id');
                        if ($item_process_ids) {
                            //子单表：fa_order_item_process：distribution_status=配货状态
                            $this->model
                                ->allowField(true)
                                ->save(
                                    ['distribution_status' => 9],
                                    ['id' => ['in', $item_process_ids]]
                                );

                            /**配货日志 Start*/
                            //打印标签
                            if ($value['custom_print_label_created_at_new']) {
                                DistributionLog::record(
                                    (object)['nickname' => $value['custom_print_label_person_new']], //操作人
                                    $item_process_ids, //子单ID
                                    1, //操作类型
                                    '标记打印完成', //备注
                                    strtotime($value['custom_print_label_created_at_new']) //操作时间
                                );
                            }

                            //配货
                            if ($value['custom_match_frame_created_at_new']) {
                                DistributionLog::record(
                                    (object)['nickname' => $value['custom_match_frame_person_new']], //操作人
                                    $item_process_ids, //子单ID
                                    2, //操作类型
                                    '配货完成', //备注
                                    strtotime($value['custom_match_frame_created_at_new']) //操作时间
                                );
                            }

                            //配镜片
                            if ($value['custom_match_lens_created_at_new']) {
                                DistributionLog::record(
                                    (object)['nickname' => $value['custom_match_lens_person_new']], //操作人
                                    $item_process_ids, //子单ID
                                    3, //操作类型
                                    '配镜片完成', //备注
                                    strtotime($value['custom_match_lens_created_at_new']) //操作时间
                                );
                            }

                            //加工
                            if ($value['custom_match_factory_created_at_new']) {
                                DistributionLog::record(
                                    (object)['nickname' => $value['custom_match_factory_person_new']], //操作人
                                    $item_process_ids, //子单ID
                                    4, //操作类型
                                    '加工完成', //备注
                                    strtotime($value['custom_match_factory_created_at_new']) //操作时间
                                );

                                //成品质检
                                DistributionLog::record(
                                    (object)['nickname' => $value['custom_match_factory_person_new']], //操作人
                                    $item_process_ids, //子单ID
                                    6, //操作类型
                                    '成品质检完成', //备注
                                    strtotime($value['custom_match_factory_created_at_new']) //操作时间
                                );
                            }

                            //合单
                            if ($value['custom_match_delivery_created_at_new']) {
                                DistributionLog::record(
                                    (object)['nickname' => $value['custom_match_delivery_person_new']], //操作人
                                    $item_process_ids, //子单ID
                                    7, //操作类型
                                    '合单完成', //备注
                                    strtotime($value['custom_match_delivery_created_at_new']) //操作时间
                                );

                                //审单
                                DistributionLog::record(
                                    (object)['nickname' => $value['custom_match_delivery_person_new']], //操作人
                                    $item_process_ids, //子单ID
                                    8, //操作类型
                                    '审单完成', //备注
                                    strtotime($value['custom_match_delivery_created_at_new']) //操作时间
                                );
                            }
                            /**配货日志 End*/

                            $handle += 1;
                        } else {
                            echo $item['name'] . '-' . $value['increment_id'] . '：未获取到子单数据' . "\n";
                        }
                        echo 'id:' . $value['entity_id'] . '站点' . $key . 'ok';
                    } catch (PDOException $e) {
                        echo $item['name'] . '-' . $value['increment_id'] . '：' . $e->getMessage() . "\n";
                    } catch (Exception $e) {
                        echo $item['name'] . '-' . $value['increment_id'] . '：' . $e->getMessage() . "\n";
                    }
                }
            }

            echo $item['name'] . "：已质检-{$count}，已处理-{$handle} End\n";
        }
    }

    /**
     * 配货旧数据处理
     *
     * @Description
     * @return mixed
     * @since 2020/12/8 10:54:39
     * @author lzh
     */
    function legacy_data1()
    {
        ini_set('memory_limit', '1024M');
        //站点列表
        $site_arr = [
            1 => [
                'name' => 'zeelool',
                'obj' => new \app\admin\model\order\printlabel\Zeelool,
            ],
            2 => [
                'name' => 'voogueme',
                'obj' => new \app\admin\model\order\printlabel\Voogueme,
            ],
            3 => [
                'name' => 'nihao',
                'obj' => new \app\admin\model\order\printlabel\Nihao,
            ],
            4 => [
                'name' => 'weseeoptical',
                'obj' => new \app\admin\model\order\printlabel\Weseeoptical,
            ],
            // 5 => [
            //     'name' => 'meeloog',
            //     'obj' => new \app\admin\model\order\printlabel\Meeloog,
            // ],
            9 => [
                'name' => 'zeelool_es',
                'obj' => new \app\admin\model\order\printlabel\ZeeloolEs,
            ],
            10 => [
                'name' => 'zeelool_de',
                'obj' => new \app\admin\model\order\printlabel\ZeeloolDe,
            ],
            11 => [
                'name' => 'zeelool_jp',
                'obj' => new \app\admin\model\order\printlabel\ZeeloolJp,
            ]
        ];

        foreach ($site_arr as $key => $item) {
            echo $item['name'] . " Start\n";
            //获取已质检旧数据
            $list = $item['obj']
                ->field('entity_id,increment_id,
                custom_print_label_created_at_new,custom_print_label_person_new,
                custom_match_frame_created_at_new,custom_match_frame_person_new,
                custom_match_lens_created_at_new,custom_match_lens_person_new,
                custom_match_factory_created_at_new,custom_match_factory_person_new,
                custom_match_delivery_created_at_new,custom_match_delivery_person_new
               ')
                ->where([
                    'custom_is_delivery_new' => 1,
                    'custom_match_delivery_created_at_new' => ['between', ['2020-10-01', '2020-12-23']]
                ])
                ->select();

            // dump(collection($list)->toArray());die;
            $count = count($list);
            $handle = 0;
            if ($list) {
                foreach ($list as $value) {
                    try {
                        //主单业务表：fa_order_process：check_status=审单状态、check_time=审单时间、combine_status=合单状态、combine_time=合单时间
                        $do_time = strtotime($value['custom_match_delivery_created_at_new']) + 28800;
                        $this->_new_order_process
                            ->allowField(true)
                            ->where(['entity_id' => $value['entity_id'], 'site' => $key])
                            ->update(['check_status' => 1, 'check_time' => $do_time, 'combine_status' => 1, 'combine_time' => $do_time]);

                        //获取子单表id集
                        $item_process_ids = $this->model->where(['magento_order_id' => $value['entity_id'], 'site' => $key])->column('id');
                        if ($item_process_ids) {
                            //子单表：fa_order_item_process：distribution_status=配货状态
                            $this->model
                                ->allowField(true)
                                ->where(['id' => ['in', $item_process_ids]])
                                ->update(['distribution_status' => 9]);
                            /**配货日志 Start*/
                            //打印标签
                            if ($value['custom_print_label_created_at_new']) {
                                DistributionLog::record(
                                    (object)['nickname' => $value['custom_print_label_person_new']], //操作人
                                    $item_process_ids, //子单ID
                                    1, //操作类型
                                    '标记打印完成', //备注
                                    strtotime($value['custom_print_label_created_at_new']) //操作时间
                                );
                            }

                            //配货
                            if ($value['custom_match_frame_created_at_new']) {
                                DistributionLog::record(
                                    (object)['nickname' => $value['custom_match_frame_person_new']], //操作人
                                    $item_process_ids, //子单ID
                                    2, //操作类型
                                    '配货完成', //备注
                                    strtotime($value['custom_match_frame_created_at_new']) //操作时间
                                );
                            }

                            //配镜片
                            if ($value['custom_match_lens_created_at_new']) {
                                DistributionLog::record(
                                    (object)['nickname' => $value['custom_match_lens_person_new']], //操作人
                                    $item_process_ids, //子单ID
                                    3, //操作类型
                                    '配镜片完成', //备注
                                    strtotime($value['custom_match_lens_created_at_new']) //操作时间
                                );
                            }

                            //加工
                            if ($value['custom_match_factory_created_at_new']) {
                                DistributionLog::record(
                                    (object)['nickname' => $value['custom_match_factory_person_new']], //操作人
                                    $item_process_ids, //子单ID
                                    4, //操作类型
                                    '加工完成', //备注
                                    strtotime($value['custom_match_factory_created_at_new']) //操作时间
                                );

                                //成品质检
                                DistributionLog::record(
                                    (object)['nickname' => $value['custom_match_factory_person_new']], //操作人
                                    $item_process_ids, //子单ID
                                    6, //操作类型
                                    '成品质检完成', //备注
                                    strtotime($value['custom_match_factory_created_at_new']) //操作时间
                                );
                            }

                            //合单
                            if ($value['custom_match_delivery_created_at_new']) {
                                DistributionLog::record(
                                    (object)['nickname' => $value['custom_match_delivery_person_new']], //操作人
                                    $item_process_ids, //子单ID
                                    7, //操作类型
                                    '合单完成', //备注
                                    strtotime($value['custom_match_delivery_created_at_new']) //操作时间
                                );

                                //审单
                                DistributionLog::record(
                                    (object)['nickname' => $value['custom_match_delivery_person_new']], //操作人
                                    $item_process_ids, //子单ID
                                    8, //操作类型
                                    '审单完成', //备注
                                    strtotime($value['custom_match_delivery_created_at_new']) //操作时间
                                );
                            }
                            /**配货日志 End*/

                            $handle += 1;
                        } else {
                            echo $item['name'] . '-' . $value['increment_id'] . '：未获取到子单数据' . "\n";
                        }
                        echo 'id:' . $value['entity_id'] . '站点' . $key . 'ok' . "\n";
                    } catch (PDOException $e) {
                        echo $item['name'] . '-' . $value['increment_id'] . '：' . $e->getMessage() . "\n";
                    } catch (Exception $e) {
                        echo $item['name'] . '-' . $value['increment_id'] . '：' . $e->getMessage() . "\n";
                    }
                }
            }

            echo $item['name'] . "：已质检-{$count}，已处理-{$handle} End\n";
        }
    }

    /**
     * 配货旧数据处理
     *
     * @Description
     * @return mixed
     * @since 2020/12/8 10:54:39
     * @author lzh
     */
    function legacy_data2()
    {
        ini_set('memory_limit', '1024M');
        //站点列表
        $site_arr = [
            1 => [
                'name' => 'zeelool',
                'obj' => new \app\admin\model\order\printlabel\Zeelool,
            ],
            2 => [
                'name' => 'voogueme',
                'obj' => new \app\admin\model\order\printlabel\Voogueme,
            ],
            3 => [
                'name' => 'nihao',
                'obj' => new \app\admin\model\order\printlabel\Nihao,
            ],
            4 => [
                'name' => 'weseeoptical',
                'obj' => new \app\admin\model\order\printlabel\Weseeoptical,
            ],
            // 5 => [
            //     'name' => 'meeloog',
            //     'obj' => new \app\admin\model\order\printlabel\Meeloog,
            // ],
            9 => [
                'name' => 'zeelool_es',
                'obj' => new \app\admin\model\order\printlabel\ZeeloolEs,
            ],
            10 => [
                'name' => 'zeelool_de',
                'obj' => new \app\admin\model\order\printlabel\ZeeloolDe,
            ],
            11 => [
                'name' => 'zeelool_jp',
                'obj' => new \app\admin\model\order\printlabel\ZeeloolJp,
            ]
        ];

        foreach ($site_arr as $key => $item) {
            echo $item['name'] . " Start\n";
            //获取已质检旧数据
            $list = $item['obj']
                ->field('entity_id,increment_id,
                custom_print_label_created_at_new,custom_print_label_person_new,
                custom_match_frame_created_at_new,custom_match_frame_person_new,
                custom_match_lens_created_at_new,custom_match_lens_person_new,
                custom_match_factory_created_at_new,custom_match_factory_person_new,
                custom_match_delivery_created_at_new,custom_match_delivery_person_new
               ')
                ->where([
                    'custom_is_delivery_new' => 1,
                    'custom_match_delivery_created_at_new' => ['between', ['2019-10-01', '2020-12-23']]
                ])
                ->select();

            // dump(collection($list)->toArray());die;
            $count = count($list);
            $handle = 0;
            if ($list) {
                foreach ($list as $value) {
                    try {
                        //主单业务表：fa_order_process：check_status=审单状态、check_time=审单时间、combine_status=合单状态、combine_time=合单时间
                        $do_time = strtotime($value['custom_match_delivery_created_at_new']) + 28800;
                        $this->_new_order_process
                            ->allowField(true)
                            ->where(['entity_id' => $value['entity_id'], 'site' => $key])
                            ->update(['check_status' => 1, 'check_time' => $do_time, 'combine_status' => 1, 'combine_time' => $do_time]);

                        //获取子单表id集
                        $item_process_ids = $this->model->where(['magento_order_id' => $value['entity_id'], 'site' => $key])->column('id');
                        if ($item_process_ids) {
                            //子单表：fa_order_item_process：distribution_status=配货状态
                            $this->model
                                ->allowField(true)
                                ->where(['id' => ['in', $item_process_ids]])
                                ->update(['distribution_status' => 9]);

                            $handle += 1;
                        } else {
                            echo $item['name'] . '-' . $value['increment_id'] . '：未获取到子单数据' . "\n";
                        }
                        echo 'id:' . $value['entity_id'] . '站点' . $key . 'ok' . "\n";
                    } catch (PDOException $e) {
                        echo $item['name'] . '-' . $value['increment_id'] . '：' . $e->getMessage() . "\n";
                    } catch (Exception $e) {
                        echo $item['name'] . '-' . $value['increment_id'] . '：' . $e->getMessage() . "\n";
                    }
                }
            }

            echo $item['name'] . "：已质检-{$count}，已处理-{$handle} End\n";
        }
    }

    /**
     * 配货旧数据处理 跑未质检已打印标签的数据
     *
     * Created by Phpstorm.
     * User: jhh
     * Date: 2020/12/22
     * Time: 9:55:14
     */
    function legacy_data_wait_print_label()
    {
        ini_set('memory_limit', '512M');
        //站点列表
        $site_arr = [
            // 1 => [
            //     'name' => 'zeelool',
            //     'obj' => new \app\admin\model\order\printlabel\Zeelool,
            // ],
            // 2 => [
            //     'name' => 'voogueme',
            //     'obj' => new \app\admin\model\order\printlabel\Voogueme,
            // ],
            // 3 => [
            //     'name' => 'nihao',
            //     'obj' => new \app\admin\model\order\printlabel\Nihao,
            // ],
            // 4 => [
            //     'name' => 'weseeoptical',
            //     'obj' => new \app\admin\model\order\printlabel\Weseeoptical,
            // ],
            // 5 => [
            //     'name' => 'meeloog',
            //     'obj' => new \app\admin\model\order\printlabel\Meeloog,
            // ],
            9 => [
                'name' => 'zeelool_es',
                'obj' => new \app\admin\model\order\printlabel\ZeeloolEs,
            ],
            10 => [
                'name' => 'zeelool_de',
                'obj' => new \app\admin\model\order\printlabel\ZeeloolDe,
            ],
            11 => [
                'name' => 'zeelool_jp',
                'obj' => new \app\admin\model\order\printlabel\ZeeloolJp,
            ]
        ];

        foreach ($site_arr as $key => $item) {
            echo $item['name'] . " Start\n";
            //获取已质检旧数据
            $list = $item['obj']
                ->field('entity_id,increment_id')
                ->where([
                    //未质检
                    'custom_is_delivery_new' => 0,
                    //已打印标签
                    'custom_print_label_new' => 1,
                    //'custom_match_delivery_created_at_new' => ['between', ['2018-01-01', '2020-10-01']]
                ])
                ->select();

            $count = count($list);
            $handle = 0;
            if ($list) {
                foreach ($list as $value) {
                    try {
                        //主单业务表：fa_order_process：check_status=审单状态、check_time=审单时间、combine_status=合单状态、combine_time=合单状态
                        $this->_new_order_process
                            ->allowField(true)
                            ->save(
                                ['check_status' => 0, 'combine_status' => 0],
                                ['entity_id' => $value['entity_id'], 'site' => $key]
                            );

                        //获取子单表id集
                        $item_process_ids = $this->model->where(['magento_order_id' => $value['entity_id'], 'site' => $key])->column('id');
                        if ($item_process_ids) {
                            //子单表：fa_order_item_process：distribution_status=配货状态
                            $this->model
                                ->allowField(true)
                                ->save(
                                    ['distribution_status' => 1],
                                    ['id' => ['in', $item_process_ids]]
                                );
                            $handle += 1;
                        } else {
                            echo $item['name'] . '-' . $value['increment_id'] . '：未获取到子单数据' . "\n";
                        }
                    } catch (PDOException $e) {
                        echo $item['name'] . '-' . $value['increment_id'] . '：' . $e->getMessage() . "\n";
                    } catch (Exception $e) {
                        echo $item['name'] . '-' . $value['increment_id'] . '：' . $e->getMessage() . "\n";
                    }
                }
            }

            echo $item['name'] . "：未质检已打印标签-{$count}，已处理-{$handle} End\n";
        }
    }

    public function export()
    {
        //站点列表
        $site_arr = [
            1 => [
                'name' => 'zeelool',
                'obj' => new \app\admin\model\order\printlabel\Zeelool,
            ],
            2 => [
                'name' => 'voogueme',
                'obj' => new \app\admin\model\order\printlabel\Voogueme,
            ],
            3 => [
                'name' => 'nihao',
                'obj' => new \app\admin\model\order\printlabel\Nihao,
            ],
            4 => [
                'name' => 'weseeoptical',
                'obj' => new \app\admin\model\order\printlabel\Weseeoptical,
            ],
            5 => [
                'name' => 'meeloog',
                'obj' => new \app\admin\model\order\printlabel\Meeloog,
            ],
            9 => [
                'name' => 'zeelool_es',
                'obj' => new \app\admin\model\order\printlabel\ZeeloolEs,
            ],
            10 => [
                'name' => 'zeelool_de',
                'obj' => new \app\admin\model\order\printlabel\ZeeloolDe,
            ],
            11 => [
                'name' => 'zeelool_jp',
                'obj' => new \app\admin\model\order\printlabel\ZeeloolJp,
            ]
        ];

        foreach ($site_arr as $key => $item) {
            //获取已质检旧数据
            $list = $item['obj']
                ->field('entity_id,increment_id,
                custom_print_label_created_at_new,custom_print_label_person_new,custom_is_delivery_new,custom_print_label_new,
                custom_match_frame_created_at_new,custom_match_frame_person_new,
                custom_match_lens_created_at_new,custom_match_lens_person_new,
                custom_match_factory_created_at_new,custom_match_factory_person_new,
                custom_match_delivery_created_at_new,custom_match_delivery_person_new
               ')
                ->where([
                    //未质检
                    'custom_is_delivery_new' => 0,
                    //已打印标签
                    'custom_print_label_new' => 1,
                    //'custom_match_delivery_created_at_new' => ['between', ['2018-01-01', '2020-10-01']]
                ])
                ->select();

            //从数据库查询需要的数据
            $spreadsheet = new Spreadsheet();
            $spreadsheet->setActiveSheetIndex(0);
            $spreadsheet->getActiveSheet()->setCellValue("A1", "entity_id");
            $spreadsheet->getActiveSheet()->setCellValue("B1", "increment_id");
            $spreadsheet->getActiveSheet()->setCellValue("C1", "是否质检 1是 0否");
            $spreadsheet->getActiveSheet()->setCellValue("D1", "质检操作人");
            $spreadsheet->getActiveSheet()->setCellValue("E1", "是否打标签 1是 0否");
            $spreadsheet->getActiveSheet()->setCellValue("F1", "打标签操作人");
            //设置宽度
            $spreadsheet->getActiveSheet()->getColumnDimension('A')->setWidth(60);
            $spreadsheet->getActiveSheet()->getColumnDimension('B')->setWidth(12);
            $spreadsheet->getActiveSheet()->getColumnDimension('C')->setWidth(12);
            $spreadsheet->getActiveSheet()->getColumnDimension('D')->setWidth(12);
            $spreadsheet->getActiveSheet()->getColumnDimension('E')->setWidth(12);
            $spreadsheet->getActiveSheet()->getColumnDimension('F')->setWidth(12);
            $spreadsheet->setActiveSheetIndex(0)->setTitle('配货旧数据');
            $spreadsheet->setActiveSheetIndex(0);
            $num = 0;
            foreach ($list as $k => $v) {
                $spreadsheet->getActiveSheet()->setCellValue('A' . ($num * 1 + 2), $v['entity_id']);
                $spreadsheet->getActiveSheet()->setCellValue('B' . ($num * 1 + 2), $v['increment_id']);
                $spreadsheet->getActiveSheet()->setCellValue('C' . ($num * 1 + 2), $v['custom_is_delivery_new'] == 1 ? '是' : '否');
                $spreadsheet->getActiveSheet()->setCellValue('D' . ($num * 1 + 2), $v['custom_match_delivery_person_new']);
                $spreadsheet->getActiveSheet()->setCellValue('E' . ($num * 1 + 2), $v['custom_print_label_new'] == 1 ? '是' : '否');
                $spreadsheet->getActiveSheet()->setCellValue('F' . ($num * 1 + 2), $v['custom_print_label_person_new']);
                $num += 1;
            }
            //设置边框
            $border = [
                'borders' => [
                    'allBorders' => [
                        'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN, // 设置border样式
                        'color' => ['argb' => 'FF000000'], // 设置border颜色
                    ],
                ],
            ];
            $spreadsheet->getDefaultStyle()->getFont()->setName('微软雅黑')->setSize(12);
            $setBorder = 'A1:' . $spreadsheet->getActiveSheet()->getHighestColumn() . $spreadsheet->getActiveSheet()->getHighestRow();
            $spreadsheet->getActiveSheet()->getStyle($setBorder)->applyFromArray($border);
            $spreadsheet->getActiveSheet()->getStyle('A1:Q' . $spreadsheet->getActiveSheet()->getHighestRow())->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
            $spreadsheet->setActiveSheetIndex(0);
            $format = 'xlsx';
            $savename = '配货未质检已打印标签数据';
            if ($format == 'xls') {
                //输出Excel03版本
                header('Content-Type:application/vnd.ms-excel');
                $class = "\PhpOffice\PhpSpreadsheet\Writer\Xls";
            } elseif ($format == 'xlsx') {
                //输出07Excel版本
                header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
                $class = "\PhpOffice\PhpSpreadsheet\Writer\Xlsx";
            }
            //输出名称
            header('Content-Disposition: attachment;filename="' . $savename . '.' . $format . '"');
            //禁止缓存
            header('Cache-Control: max-age=0');
            $writer = new $class($spreadsheet);
            $writer->save('php://output');
        }
    }


    //待配镜片批量标记异常
    public function sign_abnormals($ids = null)
    {

        //异常原因列表
        $abnormal_arr = [
            3 => '核实处方',
            4 => '镜片缺货',
            5 => '镜片重做',
            6 => '定制片超时'
        ];
        $status_arr = [
            1 => '核实轴位（AXI）',
            2 => '核实瞳距（PD）',
            3 => '核实处方光度符号',
            4 => '核实镜片类型',
            5 => '核实处方光度（左右眼光度相差过多）',
        ];
        if ($this->request->post()) {
            $ids = $this->request->post('ids', 0);
            $ids = explode(',', $ids);
            $type = $this->request->post('abnormal');
            $status = $this->request->post('status', 0);
            empty($ids) && $this->error('子订单号不能为空');
            empty($type) && $this->error('异常类型不能为空');

            foreach ($ids as $key => $value) {
                //获取子订单数据
                $item_process_info = $this->model
                    ->field('id,abnormal_house_id,item_order_number')
                    ->where('id', $ids[$key])
                    ->find();
                empty($item_process_info) && $this->error(__('子订单不存在'), [], 403);
                !empty($item_process_info['abnormal_house_id']) && $this->error(__('已标记异常，不能多次标记'), [], 403);
                $item_process_id = $item_process_info['id'];
                $item_order_number = $item_process_info['item_order_number'];
                //自动分配异常库位号
                $stock_house_info = $this->_stock_house
                    ->field('id,coding')
                    ->where(['status' => 1, 'type' => 4, 'occupy' => ['<', 10000]])
                    ->order('occupy', 'desc')
                    ->find();
                if (empty($stock_house_info)) {
                    DistributionLog::record($this->auth, $item_process_id, 0, '异常暂存架没有空余库位');
                    $this->error(__('异常暂存架没有空余库位'), [], 405);
                }

                //绑定异常子单号
                $abnormal_data = [
                    'item_process_id' => $item_process_id,
                    'type' => $type,
                    'status' => 1,
                    'create_time' => time(),
                    'create_person' => $this->auth->nickname
                ];
                if ($status) {
                    $abnormal_data['remark'] = $status;
                }


                $res = $this->_distribution_abnormal->insert($abnormal_data);

                //子订单绑定异常库位号
                $this->model
                    ->where(['id' => $item_process_id])
                    ->update(['abnormal_house_id' => $stock_house_info['id']]);

                //异常库位占用数量+1
                $this->_stock_house
                    ->where(['id' => $stock_house_info['id']])
                    ->setInc('occupy', 1);

                //配货日志
                DistributionLog::record($this->auth, $item_process_id, 9, "子单号{$item_order_number}，异常暂存架{$stock_house_info['coding']}库位");
            }

            $this->success('处理成功!', '', 'success', 200);
        }

        $this->view->assign("abnormal_arr", $abnormal_arr);
        $this->view->assign("status_arr", $status_arr);
        $this->view->assign("ids", $ids);
        return $this->view->fetch('sign_abnormals');
    }

    //取消异常
    public function cancel_abnormal($ids = null)
    {
        $admin = (object)session('admin');
        foreach ($ids as $key => $value) {
            $item_info = $this->model
                ->field('id,site,sku,distribution_status,abnormal_house_id,temporary_house_id,item_order_number')
                ->where(['id' => $ids[$key]])
                ->find();
            empty($item_info) && $this->error('子订单' . $item_info['item_order_number'] . '不存在');
            empty($item_info['abnormal_house_id']) && $this->error('子订单' . $item_info['item_order_number'] . '没有异常存在');
            //检测工单
            $work_order_list = $this->_work_order_list->where(['order_item_numbers' => ['like', $item_info['item_order_number'] . '%'], 'work_status' => ['in', [1, 2, 3, 5]]])->find();
            !empty($work_order_list) && $this->error('子订单' . $item_info['item_order_number'] . '存在未完成的工单');
            $abnormal_house_id[] = $item_info['abnormal_house_id'];
            //配货日志
            DistributionLog::record($this->auth, $ids[$key], 10, "子单号{$item_info['item_order_number']}，异常取消");
        }

        //异常库位占用数量-1
        $this->_stock_house
            ->where(['id' => ['in', $abnormal_house_id]])
            ->setDec('occupy', 1);

        //子订单状态回滚
        $save_data = [
            'abnormal_house_id' => 0 //异常库位ID
        ];

        //标记处理异常状态及时间
        $this->_distribution_abnormal->where(['item_process_id' => ['in', $ids]])->update(['status' => 2, 'do_time' => time(), 'do_person' => $admin->nickname]);
        $this->model->where(['id' => ['in', $ids]])->update($save_data);

        $this->success('操作成功!', '', 'success', 200);
    }

    /**
     * http请求
     * @param $siteType
     * @param $pathinfo
     * @param array $params
     * @param string $method
     * @return bool
     * @throws \Exception
     */
    public function httpRequest($siteType, $pathinfo, $params = [], $method = 'GET')
    {
        switch ($siteType) {
            case 1:
                $url = config('url.zeelool_url');
                break;
            case 2:
                $url = config('url.voogueme_url');
                break;
            case 3:
                $url = config('url.nihao_url');
                break;
            case 4:
                $url = config('url.meeloog_url');
                break;
            case 5:
                $url = config('url.wesee_url');
                break;
            case 9:
                $url = config('url.zeelooles_url');
                break;
            case 10:
                $url = config('url.zeeloolde_url');
                break;
            case 11:
                $url = config('url.zeelooljp_url');
                break;
            default:
                return false;
                break;
        }
        $url = $url . $pathinfo;

        $client = new Client(['verify' => false]);
        //file_put_contents('/www/wwwroot/mojing/runtime/log/a.txt',json_encode($params),FILE_APPEND);
        try {
            if ($method == 'GET') {
                $response = $client->request('GET', $url, array('query' => $params));
            } else {
                $response = $client->request('POST', $url, array('form_params' => $params));
            }
            $body = $response->getBody();
            //file_put_contents('/www/wwwroot/mojing/runtime/log/a.txt',$body,FILE_APPEND);
            $stringBody = (string) $body;
            $res = json_decode($stringBody, true);
            //file_put_contents('/www/wwwroot/mojing/runtime/log/a.txt',$stringBody,FILE_APPEND);
            if ($res === null) {
                exception('网络异常');
            }

            $status = -1 == $siteType ? $res['code'] : $res['status'];
            if (200 == $status) {
                return $res['data'];
            }

            exception($res['msg']);
        } catch (Exception $e) {
            exception($e->getMessage());
        }
    }



    //导出配镜片记录数据
    public function with_the_lens()
    {
        $where['a.order_prescription_type'] = ['in', [2, 3]];
        $where['a.created_at'] = ['between', ['1612108800', '1612195199']];
        $total = $this->model
            ->alias('a')
            ->join(['fa_order' => 'b'], 'a.order_id=b.id')
            ->join(['fa_order_process' => 'c'], 'c.order_id=b.id')
            ->field('a.*,b.increment_id,c.check_time')
            ->where($where)
            ->order('a.created_at desc')
            ->select();
        $total = collection($total)->toArray();
        foreach ($total as $key => $item) {
            $data = (new DistributionLog())
                ->where(['item_process_id' => $item['id']])
                ->where(['remark' => '配镜片完成'])
                ->field('create_person,create_time')
                ->select();
            if (!empty($data)) {
                $total[$key]['mesage'] = collection($data)->toArray();
            }
        }
        $spreadsheet = new Spreadsheet();
        //常规方式：利用setCellValue()填充数据
        $spreadsheet->setActiveSheetIndex(0)->setCellValue("A1", "子单创建时间")
            ->setCellValue("B1", "子单号")
            ->setCellValue("C1", "加工类型");   //利用setCellValues()填充数据
        $spreadsheet->setActiveSheetIndex(0)->setCellValue("D1", "子单状态")
            ->setCellValue("E1", "操作内容")
            ->setCellValue("F1", "配镜片操作记录")
            ->setCellValue("G1", "订单号")
            ->setCellValue("H1", "站点")
            ->setCellValue("I1", "仓库审单时间");
        foreach ($total as $key => $value) {
            if ($value['order_prescription_type'] == 2) {
                $value['order_prescription_type'] = '现货处方镜';
            } else {
                $value['order_prescription_type'] = '定制处方镜';
            }
            $spreadsheet->getActiveSheet()->setCellValue("A" . ($key * 1 + 2), date('Y-m-d H:i:s', $value['created_at'])); //子单创建时间
            $spreadsheet->getActiveSheet()->setCellValue("B" . ($key * 1 + 2), $value['item_order_number']); //子单号
            $spreadsheet->getActiveSheet()->setCellValue("C" . ($key * 1 + 2), $value['order_prescription_type']); //处方类型
            switch ($value['distribution_status']) {
                case 0:
                    $value['distribution_status'] = '取消';
                    break;
                case 1:
                    $value['distribution_status'] = '待打印标签';
                    break;
                case 2:
                    $value['distribution_status'] = '待配货';
                    break;
                case 3:
                    $value['distribution_status'] = '待配镜片';
                    break;
                case 4:
                    $value['distribution_status'] = '待加工';
                    break;
                case 5:
                    $value['distribution_status'] = '待印logo';
                    break;
                case 6:
                    $value['distribution_status'] = '待成品质检';
                    break;
                case 7:
                    $value['distribution_status'] = '待合单';
                    break;
                case 8:
                    $value['distribution_status'] = '合单中';
                    break;
                case 9:
                    $value['distribution_status'] = '合单完成';
                    break;
            }
            switch ($value['site']) {
                case 1:
                    $value['site'] = 'zeelool';
                    break;
                case 2:
                    $value['site'] = 'voogueme';
                    break;
                case 3:
                    $value['site'] = 'nihao';
                    break;
                case 4:
                    $value['site'] = 'meeloog';
                    break;
                case 5:
                    $value['site'] = 'wesee';
                    break;
                case 9:
                    $value['site'] = 'zeelool_es';
                    break;
                case 10:
                    $value['site'] = 'zeelool_de';
                    break;
                case 11:
                    $value['site'] = 'zeelool_jp';
                    break;
                case 12:
                    $value['site'] = 'voogmechic';
                    break;
            }
            $spreadsheet->getActiveSheet()->setCellValue("D" . ($key * 1 + 2), $value['distribution_status']);
            $spreadsheet->getActiveSheet()->setCellValue("E" . ($key * 1 + 2), '配镜片完成');

            if (!empty($value['mesage'])) {
                foreach ($value['mesage'] as $k => $v) {
                    $bt[] = $v['create_person'] . ',' . date('Y-m-d H:i:s', $v['create_time']);
                }
                $bt = implode(';', $bt);
            } else {
                $bt = '暂无数据';
            }
            $spreadsheet->getActiveSheet()->setCellValue("F" . ($key * 1 + 2), $bt);
            unset($bt);
            $spreadsheet->getActiveSheet()->setCellValue("G" . ($key * 1 + 2), $value['increment_id']);
            $spreadsheet->getActiveSheet()->setCellValue("H" . ($key * 1 + 2), $value['site']);
            $spreadsheet->getActiveSheet()->setCellValue("I" . ($key * 1 + 2),  date('Y-m-d H:i:s', $value['check_time']));
        }
        //设置宽度
        $spreadsheet->getActiveSheet()->getColumnDimension('A')->setWidth(20);
        $spreadsheet->getActiveSheet()->getColumnDimension('B')->setWidth(20);
        $spreadsheet->getActiveSheet()->getColumnDimension('C')->setWidth(40);
        $spreadsheet->getActiveSheet()->getColumnDimension('D')->setWidth(20);
        $spreadsheet->getActiveSheet()->getColumnDimension('E')->setWidth(20);
        $spreadsheet->getActiveSheet()->getColumnDimension('F')->setWidth(20);
        $spreadsheet->getActiveSheet()->getColumnDimension('G')->setWidth(20);
        $spreadsheet->getActiveSheet()->getColumnDimension('H')->setWidth(20);
        $spreadsheet->getActiveSheet()->getColumnDimension('I')->setWidth(20);
        //设置边框
        $border = [
            'borders' => [
                'allBorders' => [
                    'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN, // 设置border样式
                    'color' => ['argb' => 'FF000000'], // 设置border颜色
                ],
            ],
        ];

        $spreadsheet->getDefaultStyle()->getFont()->setName('微软雅黑')->setSize(12);


        $setBorder = 'A1:' . $spreadsheet->getActiveSheet()->getHighestColumn() . $spreadsheet->getActiveSheet()->getHighestRow();
        $spreadsheet->getActiveSheet()->getStyle($setBorder)->applyFromArray($border);

        $spreadsheet->getActiveSheet()->getStyle('A1:I' . $spreadsheet->getActiveSheet()->getHighestRow())->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
        $spreadsheet->setActiveSheetIndex(0);

        $format = 'xlsx';
        $savename = '数据查询' . date("YmdHis", time());;

        if ($format == 'xls') {
            //输出Excel03版本
            header('Content-Type:application/vnd.ms-excel');
            $class = "\PhpOffice\PhpSpreadsheet\Writer\Xls";
        } elseif ($format == 'xlsx') {
            //输出07Excel版本
            header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
            $class = "\PhpOffice\PhpSpreadsheet\Writer\Xlsx";
        }

        //输出名称
        header('Content-Disposition: attachment;filename="' . $savename . '.' . $format . '"');
        //禁止缓存
        header('Cache-Control: max-age=0');
        $writer = new $class($spreadsheet);

        $writer->save('php://output');
    }
}
