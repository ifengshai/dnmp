<?php

namespace app\admin\controller\order;

use app\admin\model\DistributionLog;
use app\admin\model\saleaftermanage\WorkOrderChangeSku;
use app\admin\model\saleaftermanage\WorkOrderList;
use app\common\controller\Backend;
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
     * 无需登录验证
     * @var array|string
     * @access protected
     */
    protected $noNeedLogin = '*';
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

    public function _initialize()
    {
        parent::_initialize();
        $this->model = new NewOrderItemProcess();
        $this->_lens_data = new LensData();
        $this->_stock_house = new StockHouse();
        $this->_distribution_abnormal = new DistributionAbnormal();
        $this->_new_order_item_option = new NewOrderItemOption();
        $this->_new_order = new NewOrder();
        $this->_new_order_process = new NewOrderProcess();
        $this->_item_platform_sku = new ItemPlatformSku();
        $this->_item = new Item();
        $this->_stock_log = new StockLog();
        $this->_work_order_list = new WorkOrderList();
        $this->_work_order_measure = new WorkOrderMeasure();
        $this->_work_order_change_sku = new WorkOrderChangeSku();
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
            $WhereSql = 'a.id > 0';
            //普通状态剔除跟单数据

            if (!in_array($label, [0, 8])) {

                if (7 == $label) {
                    $map['a.distribution_status'] = [['>', 6], ['<', 9]];
                } else {
                    $map['a.distribution_status'] = $label;
                }
//                if ($label == 2) {
//                    $WhereSql .= '  and  d.distribution_node   = 1';
//                    $WhereSql .= '  and  a.distribution_status   = ' . $label;
//                } elseif ($label == 3) {
//                    $WhereSql .= '  and  d.distribution_node   = 2';
//                    $WhereSql .= '  and  a.distribution_status   = ' . $label;
//                } elseif ($label == 4) {
//                    $WhereSql .= '  and  d.distribution_node   = 3';
//                    $WhereSql .= '  and  a.distribution_status   = ' . $label;
//                } elseif ($label == 5) {
//                    $WhereSql .= '  and  d.distribution_node   = 4';
//                    $WhereSql .= '  and  a.distribution_status   = ' . $label;
//                } elseif ($label == 6) {
//                    $WhereSql .= '  and  d.distribution_node   = 5';
//                    $WhereSql .= '  and  a.distribution_status   = ' . $label;
//                } elseif ($label == 7) {
//                    $WhereSql .= '  and  d.distribution_node   = 6';
//                    $WhereSql .= '  and  a.distribution_status >6  and a.distribution_status <9  ';
//                } else {
//                    $WhereSql .= '  and  d.distribution_node   = null';
//                }

                $map['a.abnormal_house_id'] = 0;
//                $WhereSql .= ' and  a.abnormal_house_id   = ' . $label;
            }

            //处理异常选项
            $filter = json_decode($this->request->get('filter'), true);

            if (!$filter) {
                $map['a.created_at'] = ['between', [strtotime('-3 month'), time()]];
//                $WhereSql .= " and a.created_at between " . strtotime('-3 month') . " and " . time();
            }
            if ($label !== 0) {
                if (!$filter['status']) {
                    $map['b.status'] = ['in', ['processing', 'paypal_reversed', 'paypal_canceled_reversal']];
//                    $WhereSql .= "  and b.status = 'processing' ";
                }
                unset($filter['status']);
//                if (!$filter['status']) {
//                    $map['b.status'] = ['in', ['free_processing', 'processing', 'paypal_reversed', 'paypal_canceled_reversal']];
//                    $WhereSql .= "  and b.status in ('processing','free_processing','paypal_reversed','creditcard_proccessing','paypal_canceled_reversal','complete')";
//                } else {
//                    $map['b.status'] = ['in', $filter['status']];
//                    $WhereSql .= "  and b.status in ('" . $filter["status"] . "')";
//                }
            }
            if ($filter['status']) {
                $map['b.status'] = ['in', $filter['status']];
//              $WhereSql .= "  and b.status in ('" . $filter["status"] . "')";
            }

            //查询子单ID合集
            $item_process_ids = [];

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
                    $WhereSql .= "a.id =  null";
                }
            };
            //筛选库位号
            if ($filter['stock_house_num'] || $filter['shelf_number']) {
                if (8 == $label) { //跟单
                    $house_type = 4;
                } elseif (3 == $label) { //待配镜片-定制片
                    $house_type = 3;
                } elseif (1 == $label){
                    $house_type = 1;
                }else { //合单
                    $house_type = 2;
                }
                $stock_where = ['type' => $house_type];
                if ($filter['stock_house_num']) {
                    $stock_where['coding'] = ['like', $filter['stock_house_num']. '%'];
                }
                if ($filter['shelf_number']) {
                    $arr =['A','B','C','D','E','F','G','H','I','J','K','L','M','N','O','P','Q','R','S','T','U','V','W','X','Y','Z'];
                    $stock_where['shelf_number'] = $arr[$filter['shelf_number']-1];
                }
                $stock_house = $this->_stock_house
                    ->alias('a')
                    ->field('a.id,b.sku')
                    ->join(['fa_store_sku' => 'b'], 'a.id=b.store_id')
                    ->where($stock_where)
                    ->select();
                $stock_house = collection($stock_house)->toArray();
                $stock_house_id = array_column($stock_house, 'id');
                $stock_house_sku = array_column($stock_house, 'sku');
                if ($filter['shelf_number']) {
                    $map['a.sku'] = ['in',$stock_house_sku];
                    unset($filter['shelf_number']);
                }else{
                    $map['a.temporary_house_id|a.abnormal_house_id|c.store_house_id'] = ['in', $stock_house_id ?: [-1]];
                    unset($filter['stock_house_num']);
                }
            }

            if ($filter['increment_id']) {
                $map['b.increment_id'] = ['like', $filter['increment_id'] . '%'];
                $map['b.status'] = ['in', ['free_processing', 'processing', 'complete', 'paypal_reversed', 'paypal_canceled_reversal']];
                unset($filter['increment_id']);
            }

            if ($filter['item_order_number']) {
                $ex_fil_arr = explode(' ' , $filter['item_order_number']);
                if (count($ex_fil_arr) > 1) {
                    $map['a.item_order_number'] = ['in', $ex_fil_arr];
                }else{
                    $map['a.item_order_number'] = ['like', $filter['item_order_number'] . '%'];
                }
                
                unset($filter['item_order_number']);
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

            //跟单
            if (8 == $label && $item_order_numbers) {
                $item_process_id_work = $this->model->where(['item_order_number' => ['in', $item_order_numbers]])->column('id');
                $item_process_ids = array_unique(array_merge($item_process_ids, $item_process_id_work));
            }

            if ($item_process_ids) {
                $map['a.id'] = ['in', $item_process_ids];
            }

            list($where, $sort, $order, $offset, $limit) = $this->buildparams();

            $total = $this->model
                ->alias('a')
                ->join(['fa_order' => 'b'], 'a.order_id=b.id')
                ->join(['fa_order_process' => 'c'], 'a.order_id=c.order_id')
                ->where($where)
                ->where($map)
                ->order($sort, $order)
                ->count();
            //combine_time  合单时间  delivery_time 打印时间 check_time审单时间  update_time更新时间  created_at创建时间
            $WhereOrder = '  ORDER BY  a.created_at desc';

            $sql = "SELECT a.id,a.order_id,a.item_order_number,a.sku,a.order_prescription_type,b.increment_id,b.total_qty_ordered,b.site,b.order_type,b.status,a.distribution_status,a.temporary_house_id,a.abnormal_house_id,a.created_at,c.store_house_id,d.distribution_node,d.create_time as create_time_log FROM fa_order_item_process as a 
                    LEFT JOIN fa_order AS b ON (a.`order_id`=b.`id`)
                    LEFT JOIN fa_order_process AS c ON (a.`order_id`=c.`order_id`)
                    LEFT JOIN mojing.fa_distribution_log AS d ON (a.`id`=d.`item_process_id`) where " . $WhereSql . $WhereOrder . " limit  " . $offset . ',' . $limit;;
            //            dump($sql);
            //                    $data = $this->model->query($sql);
            //            dump($data);die();
            $list = $this->model
                ->alias('a')
                ->field('a.id,a.order_id,a.item_order_number,a.sku,a.order_prescription_type,b.increment_id,b.total_qty_ordered,b.site,b.order_type,b.status,a.distribution_status,a.temporary_house_id,a.abnormal_house_id,a.created_at,c.store_house_id')
                ->join(['fa_order' => 'b'], 'a.order_id=b.id')
                ->join(['fa_order_process' => 'c'], 'a.order_id=c.order_id')
                ->where($where)
                ->where($map)
                ->order($sort, $order)
                ->limit($offset, $limit)
                ->select();

            $list = collection($list)->toArray();

            foreach ($list as $key => $item) {
                $list[$key]['label'] = $label;
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
                if (!empty($value['temporary_house_id']) && 3 == $label) {
                    $stock_house_num = $stock_house_data[$value['temporary_house_id']]; //定制片库位号
                } elseif (!empty($value['abnormal_house_id']) && 8 == $label) {
                    $stock_house_num = $stock_house_data[$value['abnormal_house_id']]; //异常库位号
                } elseif (!empty($value['store_house_id']) && 7 == $label && in_array($value['distribution_status'], [8, 9])) {
                    $stock_house_num = $stock_house_data[$value['store_house_id']]; //合单库位号
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
                $list[$key]['task_info'] = in_array($value['item_order_number'], $item_order_numbers) ? 1 : 0;

                if ($change_sku[$value['item_order_number']]) {
                    $list[$key]['sku'] = $change_sku[$value['item_order_number']];
                }
            }

            $result = array("total" => $total, "rows" => $list);
            return json($result);
        }
        $this->assign('label', $label);
        $this->assignconfig('label', $label);

        $label_list = ['全部', '待打印标签', '待配货', '待配镜片', '待加工', '待印logo', '待成品质检', '待合单', '跟单'];
        $this->assign('label_list', $label_list);

        return $this->view->fetch();
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
            $sku = $item_platform_sku->table('fa_item_platform_sku')->where('platform_sku', $v['sku'])->where('platform_type', $v['site'])->value('sku');
            $data[$sku]['location'] =
                Db::table('fa_store_sku')
                    ->alias('a')
                    ->join(['fa_store_house' => 'b'], 'a.store_id=b.id')
                    ->where('a.sku', $sku)
                    ->value('b.coding');
            $data[$sku]['sku'] = $sku;
            $data[$sku]['number']++;
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
            ->setCellValue("C1", "数量");
        foreach ($data as $key => $value) {
            $spreadsheet->getActiveSheet()->setCellValueExplicit("A" . ($key * 1 + 2), $value['sku'], \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
            $spreadsheet->getActiveSheet()->setCellValue("B" . ($key * 1 + 2), $value['location']);
            $spreadsheet->getActiveSheet()->setCellValue("C" . ($key * 1 + 2), $value['number']);
        }
        //设置宽度
        $spreadsheet->getActiveSheet()->getColumnDimension('A')->setWidth(20);
        $spreadsheet->getActiveSheet()->getColumnDimension('B')->setWidth(20);
        $spreadsheet->getActiveSheet()->getColumnDimension('C')->setWidth(20);

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

        $spreadsheet->getActiveSheet()->getStyle('A1:C' . $spreadsheet->getActiveSheet()->getHighestRow())->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
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
                    $model = Db::connect('database.db_zeelool_es');
                    break;
                case 10:
                    $model = Db::connect('database.db_zeelool_de');
                    break;
                case 11:
                    $model = Db::connect('database.db_zeelool_jp');
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
            } else {
                $map['b.status'] = ['in', ['free_processing', 'processing', 'paypal_reversed', 'paypal_canceled_reversal']];
            }
            $this->request->get(['filter' => json_encode($filter)]);

            list($where, $sort, $order) = $this->buildparams();
        }

        $sort = 'a.id';
        $list = $this->model
            ->alias('a')
            ->field('a.id,a.item_order_number,a.sku,a.order_prescription_type,b.increment_id,b.total_qty_ordered,b.site,a.distribution_status,a.created_at,c.*,b.base_grand_total')
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
            ->setCellValue("A1", "日期")
            ->setCellValue("B1", "订单号")
            ->setCellValue("C1", "子单号")
            ->setCellValue("D1", "SKU")
            ->setCellValue("E1", "站点")
            ->setCellValue("F1", "子单号状态")
            ->setCellValue("G1", "眼球")
            ->setCellValue("H1", "SPH")
            ->setCellValue("I1", "CYL")
            ->setCellValue("J1", "AXI")
            ->setCellValue("K1", "ADD")
            ->setCellValue("L1", "单PD")
            ->setCellValue("M1", "PD")
            ->setCellValue("N1", "镜片")
            ->setCellValue("O1", "镜框宽度")
            ->setCellValue("P1", "镜框高度")
            ->setCellValue("Q1", "bridge")
            ->setCellValue("R1", "处方类型")
            ->setCellValue("S1", "Prism\n(out/in)")
            ->setCellValue("T1", "Direct\n(out/in)")
            ->setCellValue("U1", "Prism\n(up/down)")
            ->setCellValue("V1", "Direct\n(up/down)")
            ->setCellValue("W1", "订单金额");
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
            ->order('a.id', 'desc')
            ->group('a.item_order_number')
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
            ->order('a.id', 'desc')
            ->group('a.item_order_number')
            ->column('a.od_sph,a.od_cyl,a.od_axis,a.od_add,a.pd_r,a.od_pv,a.od_bd,a.od_pv_r,a.od_bd_r,a.os_sph,a.os_cyl,a.os_axis,a.os_add,a.pd_l,a.os_pv,a.os_bd,a.os_pv_r,a.os_bd_r,a.lens_number,a.recipe_type as prescription_type', 'a.item_order_number');
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
            if ($change_sku[$value['item_order_number']]) {
                $value['sku'] = $change_sku[$value['item_order_number']];
            }

            //更改镜片最新数据
            if ($change_lens[$value['item_order_number']]) {
                $value = array_merge($value, $change_lens[$value['item_order_number']]);
            }

            //网站SKU转换仓库SKU
            $value['prescription_type'] = isset($value['prescription_type']) ? $value['prescription_type'] : '';
            $value['od_sph'] = isset($value['od_sph']) ? urldecode($value['od_sph']) : '';
            $value['os_sph'] = isset($value['os_sph']) ? urldecode($value['os_sph']) : '';
            $value['od_cyl'] = isset($value['od_cyl']) ? urldecode($value['od_cyl']) : '';
            $value['os_cyl'] = isset($value['os_cyl']) ? urldecode($value['os_cyl']) : '';
            $spreadsheet->getActiveSheet()->setCellValue("A" . ($key * 2 + 2), date('Y-m-d', $value['created_at']));
            $spreadsheet->getActiveSheet()->setCellValue("B" . ($key * 2 + 2), $value['increment_id']);
            $spreadsheet->getActiveSheet()->setCellValue("C" . ($key * 2 + 2), $value['item_order_number']);
            $spreadsheet->getActiveSheet()->setCellValue("D" . ($key * 2 + 2), $value['sku']);
            $spreadsheet->getActiveSheet()->setCellValue("E" . ($key * 2 + 2), $site_list[$value['site']]);
            $spreadsheet->getActiveSheet()->setCellValue("F" . ($key * 2 + 2), $distribution_status_list[$value['distribution_status']]);
            $spreadsheet->getActiveSheet()->setCellValue("G" . ($key * 2 + 2), '右眼');
            $spreadsheet->getActiveSheet()->setCellValue("G" . ($key * 2 + 3), '左眼');
            $spreadsheet->getActiveSheet()->setCellValue("H" . ($key * 2 + 2), (float)$value['od_sph'] > 0 ? ' +' . number_format($value['od_sph'] * 1, 2) : ' ' . $value['od_sph']);
            $spreadsheet->getActiveSheet()->setCellValue("H" . ($key * 2 + 3), (float)$value['os_sph'] > 0 ? ' +' . number_format($value['os_sph'] * 1, 2) : ' ' . $value['os_sph']);
            $spreadsheet->getActiveSheet()->setCellValue("I" . ($key * 2 + 2), (float)$value['od_cyl'] > 0 ? ' +' . number_format($value['od_cyl'] * 1, 2) : ' ' . $value['od_cyl']);
            $spreadsheet->getActiveSheet()->setCellValue("I" . ($key * 2 + 3), (float)$value['os_cyl'] > 0 ? ' +' . number_format($value['os_cyl'] * 1, 2) : ' ' . $value['os_cyl']);
            $spreadsheet->getActiveSheet()->setCellValue("J" . ($key * 2 + 2), $value['od_axis']);
            $spreadsheet->getActiveSheet()->setCellValue("J" . ($key * 2 + 3), $value['os_axis']);
            $value['os_add'] = urldecode($value['os_add']);
            $value['od_add'] = urldecode($value['od_add']);
            if ($value['os_add'] && $value['os_add'] && (float)($value['os_add']) * 1 != 0 && (float)($value['od_add']) * 1 != 0) {
                $spreadsheet->getActiveSheet()->setCellValue("K" . ($key * 2 + 2), $value['od_add']);
                $spreadsheet->getActiveSheet()->setCellValue("K" . ($key * 2 + 3), $value['os_add']);
            } else {

                if ($value['os_add'] && (float)$value['os_add'] * 1 != 0) {
                    //数值在上一行合并有效，数值在下一行合并后为空
                    $spreadsheet->getActiveSheet()->setCellValue("K" . ($key * 2 + 2), $value['os_add']);
                    $spreadsheet->getActiveSheet()->mergeCells("K" . ($key * 2 + 2) . ":K" . ($key * 2 + 3));
                } else {
                    //数值在上一行合并有效，数值在下一行合并后为空
                    $spreadsheet->getActiveSheet()->setCellValue("K" . ($key * 2 + 2), $value['od_add']);
                    $spreadsheet->getActiveSheet()->mergeCells("K" . ($key * 2 + 2) . ":K" . ($key * 2 + 3));
                }
            }

            //            if ($value['pdcheck'] == 'on') {
            $spreadsheet->getActiveSheet()->setCellValue("L" . ($key * 2 + 2), $value['pd_r']);
            $spreadsheet->getActiveSheet()->setCellValue("L" . ($key * 2 + 3), $value['pd_l']);
            //            } else {
            $spreadsheet->getActiveSheet()->setCellValue("M" . ($key * 2 + 2), $value['pd']);
            $spreadsheet->getActiveSheet()->mergeCells("M" . ($key * 2 + 2) . ":M" . ($key * 2 + 3));
            //            }

            //查询镜框尺寸
            $tmp_bridge = $this->get_frame_lens_width_height_bridge($value['product_id'], $value['site']);
            $lens_name = $lens_list[$value['lens_number']] ?: $value['web_lens_name'];
            $spreadsheet->getActiveSheet()->setCellValue("N" . ($key * 2 + 2), $lens_name);
            $spreadsheet->getActiveSheet()->setCellValue("O" . ($key * 2 + 2), $tmp_bridge['lens_width']);
            $spreadsheet->getActiveSheet()->setCellValue("P" . ($key * 2 + 2), $tmp_bridge['lens_height']);
            $spreadsheet->getActiveSheet()->setCellValue("Q" . ($key * 2 + 2), $tmp_bridge['bridge']);
            $spreadsheet->getActiveSheet()->setCellValue("R" . ($key * 2 + 2), $value['prescription_type']);
            $spreadsheet->getActiveSheet()->setCellValue("S" . ($key * 2 + 2), isset($value['od_pv']) ? $value['od_pv'] : '');
            $spreadsheet->getActiveSheet()->setCellValue("S" . ($key * 2 + 3), isset($value['os_pv']) ? $value['os_pv'] : '');

            $spreadsheet->getActiveSheet()->setCellValue("T" . ($key * 2 + 2), isset($value['od_bd']) ? $value['od_bd'] : '');
            $spreadsheet->getActiveSheet()->setCellValue("T" . ($key * 2 + 3), isset($value['os_bd']) ? $value['os_bd'] : '');

            $spreadsheet->getActiveSheet()->setCellValue("U" . ($key * 2 + 2), isset($value['od_pv_r']) ? $value['od_pv_r'] : '');
            $spreadsheet->getActiveSheet()->setCellValue("U" . ($key * 2 + 3), isset($value['os_pv_r']) ? $value['os_pv_r'] : '');

            $spreadsheet->getActiveSheet()->setCellValue("V" . ($key * 2 + 2), isset($value['od_bd_r']) ? $value['od_bd_r'] : '');
            $spreadsheet->getActiveSheet()->setCellValue("V" . ($key * 2 + 3), isset($value['os_bd_r']) ? $value['os_bd_r'] : '');
            $spreadsheet->getActiveSheet()->setCellValue("W" . ($key * 2 + 2), $value['base_grand_total']);

            //合并单元格
            $spreadsheet->getActiveSheet()->mergeCells("A" . ($key * 2 + 2) . ":A" . ($key * 2 + 3));
            $spreadsheet->getActiveSheet()->mergeCells("B" . ($key * 2 + 2) . ":B" . ($key * 2 + 3));
            $spreadsheet->getActiveSheet()->mergeCells("C" . ($key * 2 + 2) . ":C" . ($key * 2 + 3));
            $spreadsheet->getActiveSheet()->mergeCells("D" . ($key * 2 + 2) . ":D" . ($key * 2 + 3));
            $spreadsheet->getActiveSheet()->mergeCells("E" . ($key * 2 + 2) . ":E" . ($key * 2 + 3));
            $spreadsheet->getActiveSheet()->mergeCells("F" . ($key * 2 + 2) . ":F" . ($key * 2 + 3));


            $spreadsheet->getActiveSheet()->mergeCells("M" . ($key * 2 + 2) . ":M" . ($key * 2 + 3));
            $spreadsheet->getActiveSheet()->mergeCells("N" . ($key * 2 + 2) . ":N" . ($key * 2 + 3));
            $spreadsheet->getActiveSheet()->mergeCells("O" . ($key * 2 + 2) . ":O" . ($key * 2 + 3));
            $spreadsheet->getActiveSheet()->mergeCells("P" . ($key * 2 + 2) . ":P" . ($key * 2 + 3));
            $spreadsheet->getActiveSheet()->mergeCells("Q" . ($key * 2 + 2) . ":Q" . ($key * 2 + 3));
            $spreadsheet->getActiveSheet()->mergeCells("R" . ($key * 2 + 2) . ":R" . ($key * 2 + 3));
            $spreadsheet->getActiveSheet()->mergeCells("W" . ($key * 2 + 2) . ":W" . ($key * 2 + 3));
        }

        //设置宽度
        $spreadsheet->getActiveSheet()->getColumnDimension('A')->setWidth(12);
        $spreadsheet->getActiveSheet()->getColumnDimension('B')->setWidth(12);
        $spreadsheet->getActiveSheet()->getColumnDimension('C')->setWidth(20);
        $spreadsheet->getActiveSheet()->getColumnDimension('D')->setWidth(20);
        $spreadsheet->getActiveSheet()->getColumnDimension('E')->setWidth(20);
        $spreadsheet->getActiveSheet()->getColumnDimension('N')->setWidth(40);
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

        $spreadsheet->getActiveSheet()->getStyle('A1:W' . $spreadsheet->getActiveSheet()->getHighestRow())->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
        $spreadsheet->getActiveSheet()->getStyle('A1:W' . $spreadsheet->getActiveSheet()->getHighestRow())->getAlignment()->setVertical(\PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER);

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
            //标记状态
            $this->model->where(['id' => ['in', $ids]])->update(['distribution_status' => 2]);

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
        //禁用默认模板
        $this->view->engine->layout(false);
        ob_start();
        $ids = input('ids');
        !$ids && $this->error('缺少参数', url('index?ref=addtabs'));

        //获取子订单列表
        $list = $this->model
            ->alias('a')
            ->field('a.site,a.item_order_number,a.order_id,a.created_at,b.os_add,b.od_add,b.pdcheck,b.prismcheck,b.pd_r,b.pd_l,b.pd,b.od_pv,b.os_pv,b.od_bd,b.os_bd,b.od_bd_r,b.os_bd_r,b.od_pv_r,b.os_pv_r,b.index_name,b.coating_name,b.prescription_type,b.sku,b.od_sph,b.od_cyl,b.od_axis,b.os_sph,b.os_cyl,b.os_axis,b.lens_number,b.web_lens_name')
            ->join(['fa_order_item_option' => 'b'], 'a.option_id=b.id')
            ->where(['a.id' => ['in', $ids]])
            ->select();
        $list = collection($list)->toArray();
        $order_ids = array_column($list, 'order_id');
        $sku_arr = array_column($list, 'sku');

        //查询sku映射表
        // $item_res = $this->_item_platform_sku->cache(3600)->where(['platform_sku' => ['in', array_unique($sku_arr)]])->column('sku', 'platform_sku');

        //获取订单数据
        $order_list = $this->_new_order->where(['id' => ['in', array_unique($order_ids)]])->column('total_qty_ordered,increment_id', 'id');

        //查询产品货位号
        $cargo_number = $this->_stock_house->alias('a')->where(['status' => 1, 'b.is_del' => 1, 'a.type' => 1])->join(['fa_store_sku' => 'b'], 'a.id=b.store_id')->column('coding', 'sku');

        //获取更改镜框最新信息
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

        //获取更改镜片最新处方信息
        $change_lens = $this->_work_order_change_sku
            ->alias('a')
            ->join(['fa_work_order_measure' => 'b'], 'a.measure_id=b.id')
            ->where([
                'a.change_type' => 2,
                'a.item_order_number' => ['in', array_column($list, 'item_order_number')],
                'b.operation_type' => 1
            ])
            ->order('a.id', 'desc')
            ->group('a.item_order_number')
            ->column('a.od_sph,a.od_cyl,a.od_axis,a.od_add,a.pd_r,a.od_pv,a.od_bd,a.od_pv_r,a.od_bd_r,a.os_sph,a.os_cyl,a.os_axis,a.os_add,a.pd_l,a.os_pv,a.os_bd,a.os_pv_r,a.os_bd_r,a.lens_number,a.recipe_type as prescription_type', 'a.item_order_number');
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

            //获取镜片名称
            $v['lens_name'] = $lens_list[$v['lens_number']] ?: $v['web_lens_name'];

            $data[] = $v;
        }
        $this->assign('list', $data);
        $html = $this->view->fetch('print_label');
        echo $html;
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
            ->field('id,site,distribution_status,order_id,option_id,sku,item_order_number,order_prescription_type')
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
                        $save_status = 5;
                    } else {
                        $save_status = 6;
                    }
                } elseif (5 == $check_status) {
                    $save_status = 6;
                } elseif (6 == $check_status) {
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
            ->field('id,type')
            ->where(['item_process_id' => $ids, 'status' => 1])
            ->find();
        empty($abnormal_info) && $this->error('当前子订单异常信息获取失败');

        //状态列表
        $status_arr = [
            1 => '待打印标签',
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
            $this->_stock_log->startTrans();
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

                //标记处理异常状态及时间
                $this->_distribution_abnormal->where(['id' => $abnormal_info['id']])->update(['status' => 2, 'do_time' => time(), 'do_person' => $admin->nickname]);

                //配货操作内容
                $remark = '处理异常：' . $abnormal_arr[$abnormal_info['type']] . ',当前节点：' . $status_arr[$item_info['distribution_status']] . ',返回节点：' . $status_arr[$status];

                //回滚至待配货扣减可用库存、虚拟仓库存、配货占用、总库存
                if (2 == $status) {
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
                $this->_stock_log->commit();
            } catch (PDOException $e) {
                $this->model->rollback();
                $this->_distribution_abnormal->rollback();
                $this->_item_platform_sku->rollback();
                $this->_item->rollback();
                $this->_stock_log->rollback();
                $this->error($e->getMessage());
            } catch (Exception $e) {
                $this->model->rollback();
                $this->_distribution_abnormal->rollback();
                $this->_item_platform_sku->rollback();
                $this->_item->rollback();
                $this->_stock_log->rollback();
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


    //取消异常
    public function cancel_abnormal($ids = null){
        foreach ($ids as $key => $value) {
            $item_info = $this->model
            ->field('id,site,sku,distribution_status,abnormal_house_id,temporary_house_id,item_order_number')
            ->where(['id' => $ids[$key]])
            ->find();
            empty($item_info) && $this->error('子订单'.$item_info['item_order_number'].'不存在');
            empty($item_info['abnormal_house_id']) && $this->error('子订单'.$item_info['item_order_number'].'没有异常存在');
            //检测工单
            $work_order_list = $this->_work_order_list->where(['order_item_numbers' => ['like',$item_info['item_order_number'].'%'], 'work_status' => ['in',[1,2,3,5]]])->find();
            !empty($work_order_list) && $this->error('子订单'.$item_info['item_order_number'].'存在未完成的工单');
            $abnormal_house_id[] = $item_info['abnormal_house_id'];
        }
        
        //异常库位占用数量-1
        $this->_stock_house
            ->where(['id' => ['in',$abnormal_house_id]])
            ->setDec('occupy', 1);

        //子订单状态回滚
        $save_data = [
            'abnormal_house_id' => 0 //异常库位ID
        ];
        $this->model->where(['id' => ['in',$ids]])->update($save_data);
        $this->success('操作成功!', '', 'success', 200);
    }
}
