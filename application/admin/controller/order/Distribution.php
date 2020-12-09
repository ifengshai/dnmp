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
            //普通状态剔除跟单数据
            if (!in_array($label, [0, 8])) {
                if (7 == $label) {
                    $map['a.distribution_status'] = [['>', 6], ['<', 9]];
                } else {
                    $map['a.distribution_status'] = $label;
                }
                $map['a.abnormal_house_id'] = 0;
            }

            //处理异常选项
            $filter = json_decode($this->request->get('filter'), true);

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
                $item_process_ids = $this->_distribution_abnormal
                    ->where($abnormal_where)
                    ->column('item_process_id');
            }

            //筛选库位号
            if ($filter['stock_house_num']) {
                $stock_house_id = $this->_stock_house
                    ->where([
                        'coding'=>['like', $filter['stock_house_num'] . '%'],
                        'type'=>8 == $label ? 4 : 2//2合单库位  4异常库位
                    ])
                    ->column('id');
                $map['a.temporary_house_id|a.abnormal_house_id|c.store_house_id'] = ['in', $stock_house_id];
                unset($filter['stock_house_num']);
            }

            if ($filter['increment_id']) {
                $map['b.increment_id'] = ['like', $filter['increment_id'] . '%'];
                unset($filter['increment_id']);
            }

            if ($filter['site']) {
                $map['a.site'] = ['in', $filter['site']];
                unset($filter['site']);
            }

            if (!$filter) {
                $map['a.created_at'] = ['between', [strtotime('-3 month'), time()]];
            }
            $this->request->get(['filter' => json_encode($filter)]);

            //跟单
            $item_order_numbers = [];
            if (8 == $label) {
                //子单工单未处理
                $item_order_numbers = $this->_work_order_change_sku
                    ->alias('a')
                    ->join(['fa_work_order_list' => 'b'], 'a.work_id=b.id')
                    ->where([
                        'a.change_type'=>['in',[1,2,3]],//1更改镜架  2更改镜片 3取消订单
                        'b.work_status'=>['in',[1,2,3,5]]//工单未处理
                    ])
                    ->order('a.id','desc')
                    ->group('a.item_order_number')
                    ->column('a.item_order_number')
                ;
                if($item_order_numbers){
                    $item_process_id_work = $this->model->where(['item_order_number' => ['in', $item_order_numbers]])->column('id');
                    $item_process_ids = array_unique(array_merge($item_process_ids,$item_process_id_work));
                }
            }

            if($item_process_ids){
                $map['a.id'] = ['in', $item_process_ids];
            }

            list($where, $sort, $order, $offset, $limit) = $this->buildparams();

            $total = $this->model
                ->alias('a')
                ->join(['fa_order' => 'b'], 'a.order_id=b.id')
                ->join(['fa_order_process' => 'c'], 'a.order_id=c.order_id')
                ->where($where)
                ->where($map)
                ->order('b.created_at desc')
                ->count();

            $list = $this->model
                ->alias('a')
                ->field('a.id,a.order_id,a.item_order_number,a.sku,a.order_prescription_type,b.increment_id,b.total_qty_ordered,b.site,b.order_type,b.status,a.distribution_status,a.temporary_house_id,a.abnormal_house_id,order_type,a.created_at,c.store_house_id')
                ->join(['fa_order' => 'b'], 'a.order_id=b.id')
                ->join(['fa_order_process' => 'c'], 'a.order_id=c.order_id')
                ->where($where)
                ->where($map)
                ->order('b.created_at desc')
                ->limit($offset, $limit)
                ->select();
            $list = collection($list)->toArray();

            //库位号列表
            $stock_house_data = $this->_stock_house
                ->where(['status' => 1, 'type' => ['>', 1], 'occupy' => ['>', 0]])
                ->column('coding', 'id');

            //获取异常数据
            $abnormal_data = $this->_distribution_abnormal
                ->where(['item_process_id'=>['in',array_column($list,'id')],'status'=>1])
                ->column('work_id','item_process_id');

            //获取工单更改镜框最新信息
            $change_sku = $this->_work_order_change_sku
                ->alias('a')
                ->join(['fa_work_order_measure' => 'b'], 'a.measure_id=b.id')
                ->where([
                    'a.change_type'=>1,
                    'a.item_order_number'=>['in',array_column($list,'item_order_number')],
                    'b.operation_type'=>1
                ])
                ->order('a.id','desc')
                ->group('a.item_order_number')
                ->column('a.change_sku','a.item_order_number')
            ;

            foreach ($list as $key => $value) {
                $stock_house_num = '';
                if (!empty($value['temporary_house_id'])) {
                    $stock_house_num = $stock_house_data[$value['temporary_house_id']];//定制片库位号
                } elseif (!empty($value['abnormal_house_id'])) {
                    $stock_house_num = $stock_house_data[$value['abnormal_house_id']];//异常库位号
                } elseif (!empty($value['store_house_id'])) {
                    $stock_house_num = $stock_house_data[$value['store_house_id']];//合单库位号
                }

                $list[$key]['stock_house_num'] = $stock_house_num ?? '-';
                $list[$key]['created_at'] = date('Y-m-d H:i:s', $value['created_at']);

                //跟单：异常未处理且未创建工单的显示处理异常按钮
                $work_id = $abnormal_data[$value['id']] ?? 0;
                if (0 < $value['abnormal_house_id'] && 0 == $work_id){
                    $handle_abnormal = 1;
                }else{
                    $handle_abnormal = 0;
                }
                $list[$key]['handle_abnormal'] = $handle_abnormal;

                //判断是否显示工单按钮
                $list[$key]['task_info'] = in_array($value['item_order_number'],$item_order_numbers) ? 1 : 0;

                if($change_sku[$value['item_order_number']]){
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
                'a.change_type'=>1,
                'a.item_order_number'=>$row->item_order_number,
                'b.operation_type'=>1
            ])
            ->order('a.id','desc')
            ->value('a.change_sku')
        ;
        if($change_sku){
            $result['sku'] = $change_sku;
        }

        //获取更改镜片最新处方信息
        $change_lens = $this->_work_order_change_sku
            ->alias('a')
            ->field('a.od_sph,a.od_cyl,a.od_axis,a.od_add,a.pd_r,a.od_pv,a.od_bd,a.od_pv_r,a.od_bd_r,a.os_sph,a.os_cyl,a.os_axis,a.os_add,a.pd_l,a.os_pv,a.os_bd,a.os_pv_r,a.os_bd_r,a.lens_number,a.recipe_type as prescription_type')
            ->join(['fa_work_order_measure' => 'b'], 'a.measure_id=b.id')
            ->where([
                'a.change_type'=>2,
                'a.item_order_number'=>$row->item_order_number,
                'b.operation_type'=>1
            ])
            ->order('a.id','desc')
            ->find()
        ;
        if($change_lens){
            $change_lens = $change_lens->toArray();
            if($change_lens['pd_l'] && $change_lens['pd_r']){
                $change_lens['pd'] = '';
            }else{
                $change_lens['pd'] = $change_lens['pd_r'] ?: $change_lens['pd_l'];
            }
            $result = array_merge($result,$change_lens);
        }

        //获取镜片名称
        $lens_name = '';
        if($result['lens_number']){
            //获取镜片编码及名称
            $lens_name = $this->_lens_data->where('lens_number',$result['lens_number'])->value('lens_name');
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
            $querySql = "select cpev.entity_type_id,cpev.attribute_id,cpev.`value`,cpev.entity_id
            from catalog_product_entity_varchar cpev LEFT JOIN catalog_product_entity cpe on cpe.entity_id=cpev.entity_id 
            where cpev.attribute_id in(161,163,164) and cpev.store_id=0 and cpev.entity_id=$product_id";
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
            if ($resultList) {
                $result = array();
                foreach ($resultList as $key => $value) {
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
            } else {
                $result['lens_width'] = '';
                $result['lens_height'] = '';
                $result['bridge'] = '';
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
                //筛选异常
                if ($filter['abnormal']) {
                    $abnormal_where['type'] = $filter['abnormal'];
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
                $this->request->get(['filter' => json_encode($filter)]);
            }

            list($where, $sort, $order) = $this->buildparams();
        }

        $list = $this->model
            ->alias('a')
            ->field('a.id,a.item_order_number,a.sku,a.order_prescription_type,b.increment_id,b.total_qty_ordered,b.site,a.distribution_status,a.created_at,c.*')
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
            ->setCellValue("V1", "Direct\n(up/down)");
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
                'a.change_type'=>1,
                'a.item_order_number'=>['in',array_column($list,'item_order_number')],
                'b.operation_type'=>1
            ])
            ->order('a.id','desc')
            ->group('a.item_order_number')
            ->column('a.change_sku','a.item_order_number')
        ;

        //获取更改镜片最新处方信息
        $change_lens = $this->_work_order_change_sku
            ->alias('a')
            ->join(['fa_work_order_measure' => 'b'], 'a.measure_id=b.id')
            ->where([
                'a.change_type'=>2,
                'a.item_order_number'=>['in',array_column($list,'item_order_number')],
                'b.operation_type'=>1
            ])
            ->order('a.id','desc')
            ->group('a.item_order_number')
            ->column('a.od_sph,a.od_cyl,a.od_axis,a.od_add,a.pd_r,a.od_pv,a.od_bd,a.od_pv_r,a.od_bd_r,a.os_sph,a.os_cyl,a.os_axis,a.os_add,a.pd_l,a.os_pv,a.os_bd,a.os_pv_r,a.os_bd_r,a.lens_number,a.recipe_type as prescription_type','a.item_order_number')
        ;
        if($change_lens){
            foreach($change_lens as $key=>$val){
                if($val['pd_l'] && $val['pd_r']){
                    $change_lens[$key]['pd'] = '';
                    $change_lens[$key]['pdcheck'] = 'on';
                }else{
                    $change_lens[$key]['pd'] = $val['pd_r'] ?: $val['pd_l'];
                    $change_lens[$key]['pdcheck'] = '';
                }
            }
        }

        //获取镜片编码及名称
        $lens_list = $this->_lens_data->column('lens_name','lens_number');

        foreach ($list as $key => &$value) {
            //更改镜框最新sku
            if($change_sku[$value['item_order_number']]){
                $value['sku'] = $change_sku[$value['item_order_number']];
            }

            //更改镜片最新数据
            if($change_lens[$value['item_order_number']]){
                $value = array_merge($value,$change_lens[$value['item_order_number']]);
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

            if ($value['pdcheck'] == 'on') {
                $spreadsheet->getActiveSheet()->setCellValue("L" . ($key * 2 + 2), $value['pd_r']);
                $spreadsheet->getActiveSheet()->setCellValue("L" . ($key * 2 + 3), $value['pd_l']);
            } else {
                $spreadsheet->getActiveSheet()->setCellValue("M" . ($key * 2 + 2), $value['pd']);
                $spreadsheet->getActiveSheet()->mergeCells("M" . ($key * 2 + 2) . ":M" . ($key * 2 + 3));
            }

            //查询镜框尺寸
            $tmp_bridge = $this->get_frame_lens_width_height_bridge($value['product_id'], $value['site']);
            $lens_name = $lens_list[$value['lens_number']] ?: '';
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

            //合并单元格
            $spreadsheet->getActiveSheet()->mergeCells("A" . ($key * 2 + 2) . ":A" . ($key * 2 + 3));
            $spreadsheet->getActiveSheet()->mergeCells("B" . ($key * 2 + 2) . ":B" . ($key * 2 + 3));
            $spreadsheet->getActiveSheet()->mergeCells("C" . ($key * 2 + 2) . ":C" . ($key * 2 + 3));
            $spreadsheet->getActiveSheet()->mergeCells("D" . ($key * 2 + 2) . ":D" . ($key * 2 + 3));
            $spreadsheet->getActiveSheet()->mergeCells("E" . ($key * 2 + 2) . ":E" . ($key * 2 + 3));
            $spreadsheet->getActiveSheet()->mergeCells("F" . ($key * 2 + 2) . ":F" . ($key * 2 + 3));
            $spreadsheet->getActiveSheet()->mergeCells("L" . ($key * 2 + 2) . ":L" . ($key * 2 + 3));

            $spreadsheet->getActiveSheet()->mergeCells("M" . ($key * 2 + 2) . ":M" . ($key * 2 + 3));
            $spreadsheet->getActiveSheet()->mergeCells("N" . ($key * 2 + 2) . ":N" . ($key * 2 + 3));
            $spreadsheet->getActiveSheet()->mergeCells("O" . ($key * 2 + 2) . ":O" . ($key * 2 + 3));
            $spreadsheet->getActiveSheet()->mergeCells("P" . ($key * 2 + 2) . ":P" . ($key * 2 + 3));
            $spreadsheet->getActiveSheet()->mergeCells("Q" . ($key * 2 + 2) . ":Q" . ($key * 2 + 3));
            $spreadsheet->getActiveSheet()->mergeCells("R" . ($key * 2 + 2) . ":R" . ($key * 2 + 3));
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

        $spreadsheet->getActiveSheet()->getStyle('A1:V' . $spreadsheet->getActiveSheet()->getHighestRow())->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
        $spreadsheet->getActiveSheet()->getStyle('A1:V' . $spreadsheet->getActiveSheet()->getHighestRow())->getAlignment()->setVertical(\PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER);

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
     * @author lzh
     * @since 2020/10/28 14:45:39
     * @return void
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
            $this->model
                ->allowField(true)
                ->isUpdate(true, ['id' => ['in', $ids]])
                ->save(['distribution_status' => 2]);

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
            ->field('a.item_order_number,a.order_id,a.created_at,b.os_add,b.od_add,b.pdcheck,b.prismcheck,b.pd_r,b.pd_l,b.pd,b.od_pv,b.os_pv,b.od_bd,b.os_bd,b.od_bd_r,b.os_bd_r,b.od_pv_r,b.os_pv_r,b.index_name,b.coating_name,b.prescription_type,b.sku,b.od_sph,b.od_cyl,b.od_axis,b.os_sph,b.os_cyl,b.os_axis,b.lens_number')
            ->join(['fa_order_item_option' => 'b'], 'a.option_id=b.id')
            ->where(['a.id' => ['in', $ids]])
            ->select();
        $list = collection($list)->toArray();
        $order_ids = array_column($list, 'order_id');
        $sku_arr = array_column($list, 'sku');

        //查询sku映射表
        $item_res = $this->_item_platform_sku->cache(3600)->where(['platform_sku' => ['in', array_unique($sku_arr)]])->column('sku', 'platform_sku');

        //获取订单数据
        $order_list = $this->_new_order->where(['id' => ['in', array_unique($order_ids)]])->column('total_qty_ordered,increment_id', 'id');

        //查询产品货位号
        $cargo_number = $this->_stock_house->alias('a')->where(['status' => 1, 'b.is_del' => 1, 'a.type' => 1])->join(['fa_store_sku' => 'b'], 'a.id=b.store_id')->column('coding', 'sku');

        //获取更改镜框最新信息
        $change_sku = $this->_work_order_change_sku
            ->alias('a')
            ->join(['fa_work_order_measure' => 'b'], 'a.measure_id=b.id')
            ->where([
                'a.change_type'=>1,
                'a.item_order_number'=>['in',array_column($list,'item_order_number')],
                'b.operation_type'=>1
            ])
            ->order('a.id','desc')
            ->group('a.item_order_number')
            ->column('a.change_sku','a.item_order_number')
        ;

        //获取更改镜片最新处方信息
        $change_lens = $this->_work_order_change_sku
            ->alias('a')
            ->join(['fa_work_order_measure' => 'b'], 'a.measure_id=b.id')
            ->where([
                'a.change_type'=>2,
                'a.item_order_number'=>['in',array_column($list,'item_order_number')],
                'b.operation_type'=>1
            ])
            ->order('a.id','desc')
            ->group('a.item_order_number')
            ->column('a.od_sph,a.od_cyl,a.od_axis,a.od_add,a.pd_r,a.od_pv,a.od_bd,a.od_pv_r,a.od_bd_r,a.os_sph,a.os_cyl,a.os_axis,a.os_add,a.pd_l,a.os_pv,a.os_bd,a.os_pv_r,a.os_bd_r,a.lens_number,a.recipe_type as prescription_type','a.item_order_number')
        ;
        if($change_lens){
            foreach($change_lens as $key=>$val){
                if($val['pd_l'] && $val['pd_r']){
                    $change_lens[$key]['pd'] = '';
                    $change_lens[$key]['pdcheck'] = 'on';
                }else{
                    $change_lens[$key]['pd'] = $val['pd_r'] ?: $val['pd_l'];
                    $change_lens[$key]['pdcheck'] = '';
                }
            }
        }

        //获取镜片编码及名称
        $lens_list = $this->_lens_data->column('lens_name','lens_number');

        $data = [];
        foreach ($list as $k => $v) {
            //更改镜框最新sku
            if($change_sku[$v['item_order_number']]){
                $v['sku'] = $change_sku[$v['item_order_number']];
            }

            //更改镜片最新数据
            if($change_lens[$v['item_order_number']]){
                $v = array_merge($v,$change_lens[$v['item_order_number']]);
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
            $v['coding'] = $cargo_number[$item_res[$v['sku']]];

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
            $v['lens_name'] = $lens_list[$v['lens_number']] ?: '';

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
     * @author lzh
     * @since 2020/10/28 14:45:39
     * @return void
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
            ->field('id,site,distribution_status,order_id,option_id,sku,item_order_number')
            ->where(['id' => ['in', $ids]])
            ->select();
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
            ->where(['id' => ['in', $order_ids],'status' => 'processing'])
            ->column('increment_id')
        ;
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
                (
                    3 == $val['measure_choose_id']//主单取消措施未处理
                    ||
                    in_array($val['item_order_number'], $item_order_numbers)//子单措施未处理:更改镜框18、更改镜片19、取消20
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
            ->field('id,is_print_logo,index_name')
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
                    if ($option_list[$value['option_id']]['index_name']) {
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

                    /*//检验库存
                    $stock = $this->_item_platform_sku->where($value['sku'], $value['site'])->value('stock');
                    if (1 > $stock) {
                        throw new Exception($value['sku'].':库存不足');
                    }*/

                    //增加配货占用库存
                    $this->_item
                        ->where(['sku' => $true_sku])
                        ->setInc('distribution_occupy_stock', 1);

                    //扣库存日志
                    $stock_data = [
                        'type' => 2,
                        'two_type' => 4,
                        'sku' => $value['sku'],
                        'public_id' => $value['id'],
                        'stock_change' => -1,
                        'available_stock_change' => -1,
                        'create_person' => $admin->nickname,
                        'create_time' => date('Y-m-d H:i:s'),
                        'remark' => '配货完成：增加配货占用库存'
                    ];
                    $this->_stock_log->allowField(true)->save($stock_data);
                } elseif (3 == $check_status) {
                    $save_status = 4;
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
                    $this->_new_order_process
                        ->allowField(true)
                        ->isUpdate(true, ['order_id' => $value['order_id']])
                        ->save(['combine_status' => 1, 'combine_time' => time()]);
                }

                $this->model
                    ->allowField(true)
                    ->isUpdate(true, ['id' => $value['id']])
                    ->save(['distribution_status' => $save_status]);

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
     * @author lzh
     * @since 2020/10/28 14:45:39
     * @return void
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
            ->where(['id' => ['in', $order_ids],'status' => 'processing'])
            ->column('increment_id')
        ;
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
                (
                    3 == $val['measure_choose_id']//主单取消措施未处理
                    ||
                    in_array($val['item_order_number'], $item_order_numbers)//子单措施未处理:更改镜框18、更改镜片19、取消20
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

        //操作人信息
        $admin = (object)session('admin');

        $this->model->startTrans();
        try {
            //子订单状态回滚
            $this->model
                ->allowField(true)
                ->isUpdate(true, ['id' => ['in', $ids]])
                ->save(['distribution_status' => $status_arr[$reason]['status']]);

            //更新状态
            foreach ($item_list as $value) {
                //记录日志
                DistributionLog::record($admin, $value['id'], 6, $status_arr[$reason]['name']);
            }

            $this->model->commit();
        } catch (PDOException $e) {
            $this->model->rollback();
            $this->error($e->getMessage());
        } catch (Exception $e) {
            $this->model->rollback();
            $this->error($e->getMessage());
        }
        $this->success('操作成功!', '', 'success', 200);
    }

    /**
     * 处理异常
     *
     * @Description
     * @author lzh
     * @since 2020/10/28 14:45:39
     * @return void
     */
    public function handle_abnormal($ids = null)
    {
        //检测配货状态
        $item_info = $this->model
            ->field('id,site,sku,distribution_status,abnormal_house_id')
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
            case $item_info['distribution_status']<4:
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
                //子订单状态回滚
                $this->model
                    ->allowField(true)
                    ->isUpdate(true, ['id' => $ids])
                    ->save(['distribution_status' => $status, 'abnormal_house_id' => 0]);

                //标记异常状态
                $this->_distribution_abnormal
                    ->allowField(true)
                    ->isUpdate(true, ['id' => $abnormal_info['id']])
                    ->save(['status' => 2, 'do_time' => time(), 'do_person' => $admin->nickname]);

                //配货操作内容
                $remark = '处理异常：' . $abnormal_arr[$abnormal_info['type']] . ',当前节点：' . $status_arr[$item_info['distribution_status']] . ',返回节点：' . $status_arr[$status];

                //回滚至待配货扣减可用库存、虚拟仓库存、配货占用、总库存
                if (2 == $status) {
                    //获取true_sku
                    $true_sku = $this->_item_platform_sku->getTrueSku($item_info['sku'], $item_info['site']);

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
                    $this->_stock_log
                        ->allowField(true)
                        ->save([
                            'type' => 2,
                            'two_type' => 4,
                            'sku' => $item_info['sku'],
                            'public_id' => $item_info['id'],
                            'stock_change' => -1,
                            'available_stock_change' => -1,
                            'create_person' => $admin->nickname,
                            'create_time' => date('Y-m-d H:i:s'),
                            'remark' => '处理异常：回滚至待配货，减少可用库存、虚拟仓库存、配货占用库存、总库存'
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
     * @author lzh
     * @since 2020/10/28 14:45:39
     * @return void
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
     * @author wgj
     * @since 2020/11/20 14:54:39
     * @return void
     */
    public function add()
    {
        $ids = input('id_params/a');//子单ID
        !$ids && $this->error('请选择要创建工单的数据');

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
        $order_id = array_unique(array_filter($order_id));//数组去空、去重
        1 < count($order_id) && $this->error('所选子订单的主单不唯一');

        //检测是否有未处理工单
        $check_work_order = $this->_work_order_list
            ->where([
                'work_status'=>['in',[1,2,3,5]],
                'platform_order'=>$order_id[0]
            ])
            ->value('id');
        $check_work_order && $this->error('当前订单有未完成工单，不可创建工单');

        //调用创建工单接口
        //saleaftermanage/work_order_list/add?order_number=123&order_item_numbers=35456,23465,1111
        $request = Request::instance();
        $url_domain = $request->domain();
        $url_root = $request->root();
        $url = $url_domain . $url_root;
        $url = $url . '/saleaftermanage/work_order_list/add?order_number=' . $order_id[0] . '&order_item_numbers=' . implode(',', $item_process_numbers);
        //http://www.mojing.cn/admin_1biSSnWyfW.php/saleaftermanage/work_order_list/add?order_number=859063&order_item_numbers=430224120-03,430224120-04
        $this->success('跳转!', '', ['url' => $url], 200);
    }

    /**
     * 配货旧数据处理
     *
     * @Description
     * @author lzh
     * @since 2020/12/8 10:54:39
     * @return mixed
     */
    function legacy_data(){

        //站点列表
        $site_arr = [
            1=>[
                'name'=>'zeelool',
                'obj' => new \app\admin\model\order\printlabel\Zeelool,
            ]

        ];

        $this->model = new NewOrderItemProcess();
        $this->_lens_data = new LensData();
        $this->_stock_house = new StockHouse();
        $this->_distribution_abnormal = new DistributionAbnormal();
        $this->_new_order_item_option = new NewOrderItemOption();
        $this->_new_order = new NewOrder();
        $this->_new_order_process = new NewOrderProcess();

        foreach($site_arr as $key=>$item){
            echo $item['name']." Start\n";
            //获取已质检旧数据
            $field = 'order_type,custom_order_prescription_type,entity_id,status,base_shipping_amount,increment_id,base_grand_total,
                     total_qty_ordered,custom_is_match_frame_new,custom_is_match_lens_new,
                     custom_is_send_factory_new,custom_is_delivery_new,custom_print_label_new,custom_order_prescription,created_at';
            $list = $item['obj']
                ->field($field)
                ->where($map)
                ->where($where)
                ->order($sort, $order)
                ->limit($offset, $limit)
                ->select();

            $count = count($list);
            $handle = 0;
            if($list){
                foreach ($list as $value){
                    //fa_order_process表：check_status、check_time、combine_status、combine_time
                    $do_time = strtotime($value['custom_match_delivery_created_at_new']) + 28800;
                    $this->_new_order_process
                        ->allowField(true)
                        ->save(
                            ['check_status' => 1, 'check_time' => $do_time,'combine_status' => 1, 'combine_time' => $do_time],
                            ['increment_id' => $value['increment_id'], 'site' => $key]
                        );
                }
            }



            //fa_order_item_process表：distribution_status、check_time

            echo $item['name']."：已质检-{$count}，已处理-{$handle} End\n";
        }

    }

}
