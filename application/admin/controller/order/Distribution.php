<?php

namespace app\admin\controller\order;

use app\admin\model\DistributionLog;
use app\common\controller\Backend;
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

/**
 * 配货列表
 */
class Distribution extends Backend
{
    protected $noNeedRight = ['orderDetail', 'batch_print_label_new', 'batch_export_xls', 'account_order_batch_export_xls'];
    protected $model = null;

    public function _initialize()
    {
        parent::_initialize();
        $this->model = new NewOrderItemProcess();
        $this->orderitemoption = new NewOrderItemOption();
        $this->lensdata = new LensData();
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
                $map['a.temporary_house_id|a.abnormal_house_id'] = 0;
            }

            $_stock_house = new StockHouse();
            $filter = json_decode($this->request->get('filter'), true);
            if ($filter['abnormal'] || $filter['stock_house_num']) {
                //筛选异常
                if ($filter['abnormal']) {
                    $_distribution_abnormal = new DistributionAbnormal();
                    $abnormal_where['type'] = ['in', $filter['abnormal']];
                    if (8 == $label) {
                        $abnormal_where['status'] = 1;
                    }
                    $item_process_id = $_distribution_abnormal
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
                    $stock_house_id = $_stock_house
                        ->where($stock_house_where)
                        ->column('id');
                    $map['a.temporary_house_id|a.abnormal_house_id|c.store_house_id'] = ['in', $stock_house_id];
                }
                unset($filter['abnormal']);
                unset($filter['stock_house_num']);
            }

            if ($filter['site']) {
                $map['a.site'] = ['in', $filter['site']];
                unset($filter['site']);
            }
            $this->request->get(['filter' => json_encode($filter)]);

            list($where, $sort, $order, $offset, $limit) = $this->buildparams();
            $total = $this->model
                ->alias('a')
                ->join(['fa_order' => 'b'], 'a.order_id=b.id')
                ->join(['fa_order_process' => 'c'], 'a.order_id=c.order_id')
                ->where($where)
                ->where($map)
                ->order($sort, $order)
                ->count();

            //TODO::是否有工单
            $list = $this->model
                ->alias('a')
                ->field('a.id,a.item_order_number,a.sku,a.order_prescription_type,b.increment_id,b.total_qty_ordered,b.site,b.order_type,b.status,a.distribution_status,a.temporary_house_id,a.abnormal_house_id,order_type,a.created_at,c.store_house_id')
                ->join(['fa_order' => 'b'], 'a.order_id=b.id')
                ->join(['fa_order_process' => 'c'], 'a.order_id=c.order_id')
                ->where($where)
                ->where($map)
                ->order($sort, $order)
                ->limit($offset, $limit)
                ->select();
            $list = collection($list)->toArray();

            $stock_house_data = $_stock_house
                ->where(['status' => 1, 'type' => ['>', 1], 'occupy' => ['>', 0]])
                ->column('coding', 'id');

            foreach ($list as $key => $value) {
                if (!empty($value['temporary_house_id'])) {
                    $stock_house_num = $stock_house_data[$value['temporary_house_id']]['coding'];
                } elseif (!empty($value['temporary_house_id'])) {
                    $stock_house_num = $stock_house_data[$value['abnormal_house_id']]['coding'];
                } elseif (!empty($value['store_house_id'])) {
                    $stock_house_num = $stock_house_data[$value['store_house_id']]['coding'];
                }
                $list[$key]['stock_house_num'] = $stock_house_num ?? '-';
                $list[$key]['created_at'] = date('Y-m-d H:i:s', $value['created_at']);
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
        $result = $this->orderitemoption->get($row['option_id'])->toArray();

        //根据镜片编码查询仓库镜片名称
        $result['lens_name'] = $this->lensdata->where('lens_number', $result['lens_number'])->value('lens_name');
        $this->assign('result', $result);
        return $this->view->fetch();
    }

    /**
     * 批量导出xls
     * @todo 弃用
     * @Description
     * @author lzh
     * @since 2020/10/28 14:45:39
     * @return void
     */
    public function batch_export_xls_bak()
    {
        set_time_limit(0);
        ini_set('memory_limit', '512M');
        //根据传的标签切换对应站点数据库
        $label = $this->request->get('label', 0);

        $ids = input('ids');
        $map = [];
        if ($ids) {
            $map['id'] = ['in', $ids];
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
                $map['a.temporary_house_id|a.abnormal_house_id|c.store_house_id'] = 0;
            }

            $_stock_house = new StockHouse();
            $filter = json_decode($this->request->get('filter'), true);
            if ($filter['abnormal'] || $filter['stock_house_num']) {
                //筛选异常
                if ($filter['abnormal']) {
                    $_distribution_abnormal = new DistributionAbnormal();
                    $abnormal_where['type'] = $filter['abnormal'];
                    if (8 == $label) {
                        $abnormal_where['status'] = 1;
                    }
                    $item_process_id = $_distribution_abnormal
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
                    $stock_house_id = $_stock_house
                        ->where($stock_house_where)
                        ->column('id');
                    $map['a.temporary_house_id|a.abnormal_house_id|c.store_house_id'] = ['in', $stock_house_id];
                }
                unset($filter['abnormal']);
                unset($filter['stock_house_num']);
                $this->request->get(['filter' => json_encode($filter)]);
            }

            list($where, $sort, $order) = $this->buildparams();
        }

        $list = $this->model
            ->alias('a')
            ->field('a.id,a.item_order_number,a.sku,a.order_prescription_type,b.increment_id,b.total_qty_ordered,b.site,b.order_type,b.status,a.distribution_status,a.temporary_house_id,a.abnormal_house_id,order_type,a.created_at,c.store_house_id')
            ->join(['fa_order' => 'b'], 'a.order_id=b.id')
            ->join(['fa_order_process' => 'c'], 'a.order_id=c.order_id')
            ->where($where)
            ->where($map)
            ->order($sort, $order)
            ->select();
        $list = collection($list)->toArray();

        $_stock_house = new StockHouse();
        $stock_house_data = $_stock_house
            ->where(['status' => 1, 'type' => ['>', 1], 'occupy' => ['>', 0]])
            ->column('coding', 'id');

        foreach ($list as $key => $value) {
            if (!empty($value['temporary_house_id'])) {
                $stock_house_num = $stock_house_data[$value['temporary_house_id']]['coding'];
            } elseif (!empty($value['temporary_house_id'])) {
                $stock_house_num = $stock_house_data[$value['abnormal_house_id']]['coding'];
            } elseif (!empty($value['store_house_id'])) {
                $stock_house_num = $stock_house_data[$value['store_house_id']]['coding'];
            }
            $list[$key]['stock_house_num'] = $stock_house_num ?? '-';
            $list[$key]['created_at'] = date('Y-m-d H:i:s', $value['created_at']);
        }

        //从数据库查询需要的数据
        $spreadsheet = new Spreadsheet();

        //常规方式：利用setCellValue()填充数据
        $spreadsheet->setActiveSheetIndex(0)
            ->setCellValue("A1", "订单号")
            ->setCellValue("B1", "子单号")
            ->setCellValue("C1", "SKU")
            ->setCellValue("D1", "订单副数")
            //            ->setCellValue("E1", "工单")
            ->setCellValue("F1", "站点")
            ->setCellValue("G1", "加工类型")
            ->setCellValue("H1", "订单类型")
            ->setCellValue("I1", "订单状态")
            ->setCellValue("J1", "子单号状态")
            ->setCellValue("K1", "库位号")
            ->setCellValue("L1", "创建时间");

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

        //加工类型
        $prescription_type_list = [
            1 => '仅镜架',
            2 => '现货处方镜',
            3 => '定制处方镜',
            4 => '镜架+现货',
            5 => '镜架+定制',
            6 => '现片+定制片'
        ];

        //订单类型
        $order_type_list = [
            1 => '普通订单',
            2 => '批发单',
            3 => '网红单',
            4 => '补发单',
            5 => '补差价',
            6 => '一件代发'
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

        foreach ($list as $key => $value) {
            $line = $key + 2;
            $spreadsheet->getActiveSheet()->setCellValueExplicit("A{$line}", $value['increment_id'], \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
            $spreadsheet->getActiveSheet()->setCellValue("B{$line}", $value['item_order_number']);
            $spreadsheet->getActiveSheet()->setCellValue("C{$line}", $value['sku']);
            $spreadsheet->getActiveSheet()->setCellValue("D{$line}", $value['total_qty_ordered']);
            //            $spreadsheet->getActiveSheet()->setCellValue("E{$line}", $value['base_grand_total']);
            $spreadsheet->getActiveSheet()->setCellValue("F{$line}", $site_list[$value['site']]);
            $spreadsheet->getActiveSheet()->setCellValue("G{$line}", $prescription_type_list[$value['order_prescription_type']]);
            $spreadsheet->getActiveSheet()->setCellValue("H{$line}", $order_type_list[$value['order_type']]);
            $spreadsheet->getActiveSheet()->setCellValue("I{$line}", $value['status']);
            $spreadsheet->getActiveSheet()->setCellValue("J{$line}", $distribution_status_list[$value['distribution_status']]);
            $spreadsheet->getActiveSheet()->setCellValue("K{$line}", $value['stock_house_num']);
            $spreadsheet->getActiveSheet()->setCellValue("L{$line}", $value['created_at']);
        }

        //设置宽度
        $spreadsheet->getActiveSheet()->getColumnDimension('A')->setWidth(30);
        $spreadsheet->getActiveSheet()->getColumnDimension('B')->setWidth(40);
        $spreadsheet->getActiveSheet()->getColumnDimension('C')->setWidth(30);
        $spreadsheet->getActiveSheet()->getColumnDimension('D')->setWidth(20);
        //        $spreadsheet->getActiveSheet()->getColumnDimension('E')->setWidth(20);
        $spreadsheet->getActiveSheet()->getColumnDimension('F')->setWidth(15);
        $spreadsheet->getActiveSheet()->getColumnDimension('G')->setWidth(15);
        $spreadsheet->getActiveSheet()->getColumnDimension('H')->setWidth(15);
        $spreadsheet->getActiveSheet()->getColumnDimension('I')->setWidth(30);
        $spreadsheet->getActiveSheet()->getColumnDimension('J')->setWidth(30);
        $spreadsheet->getActiveSheet()->getColumnDimension('K')->setWidth(30);
        $spreadsheet->getActiveSheet()->getColumnDimension('L')->setWidth(30);

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
            $map['id'] = ['in', $ids];
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

            $_stock_house = new StockHouse();
            $filter = json_decode($this->request->get('filter'), true);
            if ($filter['abnormal'] || $filter['stock_house_num']) {
                //筛选异常
                if ($filter['abnormal']) {
                    $_distribution_abnormal = new DistributionAbnormal();
                    $abnormal_where['type'] = $filter['abnormal'];
                    if (8 == $label) {
                        $abnormal_where['status'] = 1;
                    }
                    $item_process_id = $_distribution_abnormal
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
                    $stock_house_id = $_stock_house
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
            ->field('a.id,a.item_order_number,a.sku,a.order_prescription_type,b.increment_id,b.total_qty_ordered,b.site,a.distribution_status,a.temporary_house_id,a.abnormal_house_id,a.created_at')
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
            ->setCellValue("A1", "订单号")
            ->setCellValue("B1", "子单号")
            ->setCellValue("C1", "SKU")
            ->setCellValue("D1", "订单副数")
            //            ->setCellValue("E1", "工单")
            ->setCellValue("F1", "站点")
            ->setCellValue("G1", "加工类型")
            ->setCellValue("H1", "订单类型")
            ->setCellValue("I1", "订单状态")
            ->setCellValue("J1", "子单号状态")
            ->setCellValue("K1", "库位号")
            ->setCellValue("L1", "创建时间");

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

        //加工类型
        $prescription_type_list = [
            1 => '仅镜架',
            2 => '现货处方镜',
            3 => '定制处方镜',
            4 => '镜架+现货',
            5 => '镜架+定制',
            6 => '现片+定制片'
        ];

        //订单类型
        $order_type_list = [
            1 => '普通订单',
            2 => '批发单',
            3 => '网红单',
            4 => '补发单',
            5 => '补差价',
            6 => '一件代发'
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

        foreach ($list as $key => $value) {
            $line = $key + 2;
            $spreadsheet->getActiveSheet()->setCellValueExplicit("A{$line}", $value['increment_id'], \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
            $spreadsheet->getActiveSheet()->setCellValue("B{$line}", $value['item_order_number']);
            $spreadsheet->getActiveSheet()->setCellValue("C{$line}", $value['sku']);
            $spreadsheet->getActiveSheet()->setCellValue("D{$line}", $value['total_qty_ordered']);
            //            $spreadsheet->getActiveSheet()->setCellValue("E{$line}", $value['base_grand_total']);
            $spreadsheet->getActiveSheet()->setCellValue("F{$line}", $site_list[$value['site']]);
            $spreadsheet->getActiveSheet()->setCellValue("G{$line}", $prescription_type_list[$value['order_prescription_type']]);
            $spreadsheet->getActiveSheet()->setCellValue("H{$line}", $order_type_list[$value['order_type']]);
            $spreadsheet->getActiveSheet()->setCellValue("I{$line}", $value['status']);
            $spreadsheet->getActiveSheet()->setCellValue("J{$line}", $distribution_status_list[$value['distribution_status']]);
            $spreadsheet->getActiveSheet()->setCellValue("K{$line}", $value['stock_house_num']);
            $spreadsheet->getActiveSheet()->setCellValue("L{$line}", $value['created_at']);
        }

        //设置宽度
        $spreadsheet->getActiveSheet()->getColumnDimension('A')->setWidth(30);
        $spreadsheet->getActiveSheet()->getColumnDimension('B')->setWidth(40);
        $spreadsheet->getActiveSheet()->getColumnDimension('C')->setWidth(30);
        $spreadsheet->getActiveSheet()->getColumnDimension('D')->setWidth(20);
        //        $spreadsheet->getActiveSheet()->getColumnDimension('E')->setWidth(20);
        $spreadsheet->getActiveSheet()->getColumnDimension('F')->setWidth(15);
        $spreadsheet->getActiveSheet()->getColumnDimension('G')->setWidth(15);
        $spreadsheet->getActiveSheet()->getColumnDimension('H')->setWidth(15);
        $spreadsheet->getActiveSheet()->getColumnDimension('I')->setWidth(30);
        $spreadsheet->getActiveSheet()->getColumnDimension('J')->setWidth(30);
        $spreadsheet->getActiveSheet()->getColumnDimension('K')->setWidth(30);
        $spreadsheet->getActiveSheet()->getColumnDimension('L')->setWidth(30);

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
        Db::startTrans();
        try {
            //标记状态
            $this->model
                ->allowField(true)
                ->isUpdate(true, ['id' => ['in', $ids]])
                ->save(['distribution_status' => 2]);

            //记录配货日志
            $admin = (object)session('admin');
            DistributionLog::record($admin, $ids, 1, '标记打印完成');

            Db::commit();
        } catch (PDOException $e) {
            Db::rollback();
            $this->error($e->getMessage());
        } catch (Exception $e) {
            Db::rollback();
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
            ->field('a.item_order_number,a.order_id,a.created_at,b.os_add,b.od_add,b.pdcheck,b.prismcheck,b.pd_r,b.pd_l,b.pd,b.od_pv,b.os_pv,b.od_bd,b.os_bd,b.od_bd_r,b.os_bd_r,b.od_pv_r,b.os_pv_r,b.index_name,b.coatiing_name,b.prescription_type,b.sku,b.od_sph,b.od_cyl,b.od_axis,b.os_sph,b.os_cyl,b.os_axis,b.lens_number')
            ->join(['fa_order_item_option' => 'b'], 'a.option_id=b.id')
            ->where(['a.id' => ['in', $ids]])
            ->select();
        $list = collection($list)->toArray();
        $order_ids = array_column($list, 'order_id');
        $sku_arr = array_column($list, 'sku');
        $lens_number = array_column($list, 'lens_number');

        //查询sku映射表
        $item = new \app\admin\model\itemmanage\ItemPlatformSku;
        $item_res = $item->cache(3600)->where(['platform_sku' => ['in', array_unique($sku_arr)]])->column('sku', 'platform_sku');

        //获取订单数据
        $_new_order = new NewOrder();
        $order_list = $_new_order->where(['id' => ['in', array_unique($order_ids)]])->column('total_qty_ordered,increment_id', 'id');

        //查询产品货位号
        $store_sku = new \app\admin\model\warehouse\StockHouse;
        $cargo_number = $store_sku->alias('a')->where(['status' => 1, 'b.is_del' => 1, 'a.type' => 1])->join(['fa_store_sku' => 'b'], 'a.id=b.store_id')->column('coding', 'sku');

        //根据镜片编码查询仓库镜片名称
        $index_name = $this->lensdata->where(['lens_number' => ['in', $lens_number]])->column('lens_name', 'lens_number');

        foreach ($list as $k => $v) {
            $item_order_number = $v['item_order_number'];
            $fileName = ROOT_PATH . "public" . DS . "uploads" . DS . "printOrder" . DS . "distribution" . DS . "new" . DS . "$item_order_number.png";
            $dir = ROOT_PATH . "public" . DS . "uploads" . DS . "printOrder" . DS . "distribution" . DS . "new";
            if (!file_exists($dir)) {
                mkdir($dir, 0777, true);
            }
            $img_url = "/uploads/printOrder/distribution/new/$item_order_number.png";

            //生成条形码
            $this->generate_barcode_new($item_order_number, $fileName);
            $list[$k]['created_at'] = date('Y-m-d H:i:s', $v['created_at']);
            $list[$k]['img_url'] = $img_url;
            //序号
            $serial = explode('-', $item_order_number);
            $list[$k]['serial'] = $serial[1];
            $list[$k]['total_qty_ordered'] = $order_list[$v['order_id']]['total_qty_ordered'];
            $list[$k]['increment_id'] = $order_list[$v['order_id']]['increment_id'];
            //库位号
            $list[$k]['coding'] = $cargo_number[$item_res[$v['sku']]];

            //判断双ADD逻辑
            if ($v['os_add'] && $v['od_add'] && (float) $v['os_add'] * 1 != 0 && (float) $v['od_add'] * 1 != 0) {
                $list[$k]['total_add'] = '';
            } else {
                if ($v['os_add'] && (float) $v['os_add'] * 1 != 0) {
                    $list[$k]['total_add'] = $v['os_add'];
                } else {
                    $list[$k]['total_add'] = $v['od_add'];
                }
            }
            $list[$k]['lens_name'] = $index_name[$v['lens_number']];
        }
        $this->assign('list', $list);
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
        $_distribution_abnormal = new DistributionAbnormal();
        $abnormal_count = $_distribution_abnormal
            ->where(['item_process_id' => ['in', $ids], 'status' => 1])
            ->count();
        0 < $abnormal_count && $this->error('有异常待处理的子订单');

        //TODO::检测工单状态

        //检测配货状态
        $item_list = $this->model
            ->field('id,site,distribution_status,order_id,option_id')
            ->where(['id' => ['in', $ids]])
            ->select();
        $order_ids = [];
        $option_ids = [];
        foreach ($item_list as $value) {
            $value['distribution_status'] != $check_status && $this->error('存在非当前节点的子订单');
            $order_ids[] = $value['order_id'];
            $option_ids[] = $value['option_id'];
        }

        //获取订单购买总数
        $_new_order = new NewOrder();
        $total_list = $_new_order
            ->where(['id' => ['in', array_unique($order_ids)]])
            ->column('total_qty_ordered', 'id');

        //获取子订单处方数据
        $_new_order_item_option = new NewOrderItemOption();
        $option_list = $_new_order_item_option
            ->field('id,is_print_logo,sku,index_name')
            ->where(['id' => ['in', array_unique($option_ids)]])
            ->select();
        $option_list = array_column($option_list, NULL, 'id');

        //库存、关系映射表
        $_item_platform_sku = new ItemPlatformSku();
        $_item = new Item();

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

        Db::startTrans();
        try {
            //主订单状态表
            $_new_order_process = new NewOrderProcess();

            //更新状态
            foreach ($item_list as $value) {
                //下一步状态
                if (2 == $check_status) {
                    if ($option_list[$value['option_id']]['index_name']) {
                        $save_status = 3;
                    } else {
                        if ($option_list[$value['option_id']]['is_print_logo']) {
                            $save_status = 5;
                        } else {
                            if ($total_list[$value['order_id']]['total_qty_ordered'] > 1) {
                                $save_status = 7;
                            } else {
                                $save_status = 9;
                            }
                        }
                    }
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

                    //获取true_sku
                    $true_sku = $_item_platform_sku->getTrueSku($option_list[$value['option_id']]['sku'], $value['site']);

                    //扣减订单占用库存、配货占用库存、总库存
                    $_item
                        ->where(['sku' => $true_sku])
                        ->dec('occupy_stock', 1)
                        ->dec('distribution_occupy_stock', 1)
                        ->dec('stock', 1)
                        ->update();
                }

                //订单主表标记已合单
                if (9 == $save_status) {
                    $_new_order_process
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

            Db::commit();
        } catch (PDOException $e) {
            Db::rollback();
            $this->error($e->getMessage());
        } catch (Exception $e) {
            Db::rollback();
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
        $_distribution_abnormal = new DistributionAbnormal();
        $abnormal_count = $_distribution_abnormal
            ->where(['item_process_id' => ['in', $ids], 'status' => 1])
            ->count();
        0 < $abnormal_count && $this->error('有异常待处理的子订单');

        //TODO::检测工单状态

        //检测配货状态
        $item_list = $this->model
            ->field('id,site,sku,distribution_status')
            ->where(['id' => ['in', $ids]])
            ->select();
        empty($item_list) && $this->error('数据不存在');
        foreach ($item_list as $value) {
            6 != $value['distribution_status'] && $this->error('存在非当前节点的子订单');
        }

        //库存、关系映射、库存日志表
        $_item_platform_sku = new ItemPlatformSku();
        $_item = new Item();
        $_stock_log = new StockLog();

        //状态
        $status_arr = [
            1 => ['status' => 4, 'name' => '质检拒绝：加工调整'],
            2 => ['status' => 2, 'name' => '质检拒绝：镜架报损'],
            3 => ['status' => 3, 'name' => '质检拒绝：镜片报损'],
            4 => ['status' => 5, 'name' => '质检拒绝：logo调整']
        ];

        //操作人信息
        $admin = (object)session('admin');

        Db::startTrans();
        try {
            //子订单状态回滚
            $this->model
                ->allowField(true)
                ->isUpdate(true, ['id' => ['in', $ids]])
                ->save(['distribution_status' => $status_arr[$reason]['status']]);

            //更新状态
            foreach ($item_list as $value) {
                //镜片报损扣减可用库存、虚拟仓库存、配货占用库存、总库存
                if (2 == $reason) {
                    //获取true_sku
                    $true_sku = $_item_platform_sku->getTrueSku($value['sku'], $value['site']);

                    //扣减虚拟仓库存
                    $_item_platform_sku
                        ->where(['sku' => $true_sku, 'platform_type' => $value['site']])
                        ->dec('stock', 1)
                        ->update();

                    //扣减可用库存、配货占用库存、总库存
                    $_item
                        ->where(['sku' => $true_sku])
                        ->dec('available_stock', 1)
                        ->dec('distribution_occupy_stock', 1)
                        ->dec('stock', 1)
                        ->update();

                    //扣库存日志
                    $stock_data = [
                        'type'                      => 2,
                        'two_type'                  => 4,
                        'sku'                       => $value['sku'],
                        'public_id'                 => $value['id'],
                        'stock_change'              => -1,
                        'available_stock_change'    => -1,
                        'create_person'             => $admin->nickname,
                        'create_time'               => date('Y-m-d H:i:s'),
                        'remark'                    => '成检拒绝：减少总库存,减少可用库存'
                    ];
                    $_stock_log->allowField(true)->save($stock_data);
                }

                //记录日志
                DistributionLog::record($admin, $value['id'], 6, $status_arr[$reason]['name']);
            }

            Db::commit();
        } catch (PDOException $e) {
            Db::rollback();
            $this->error($e->getMessage());
        } catch (Exception $e) {
            Db::rollback();
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
        print_r($ids);
        print_r('----------');
        print_r($item_info);
        empty($item_info) && $this->error('子订单不存在');
        empty($item_info['abnormal_house_id']) && $this->error('当前子订单未标记异常');

        //检测异常状态
        $_distribution_abnormal = new DistributionAbnormal();
        $abnormal_info = $_distribution_abnormal
            ->field('id,type')
            ->where(['item_process_id' => $ids, 'status' => 1])
            ->find();
        print_r('----------');
        print_r($abnormal_info);
        exit;
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

            Db::startTrans();
            try {
                //子订单状态回滚
                $this->model
                    ->allowField(true)
                    ->isUpdate(true, ['id' => $ids])
                    ->save(['distribution_status' => $status,'abnormal_house_id' => 0]);

                //标记异常状态
                $_distribution_abnormal
                    ->allowField(true)
                    ->isUpdate(true, ['id' => $abnormal_info['id']])
                    ->save(['status' => 2, 'do_time' => time(), 'do_person' => $admin->nickname]);

                //镜片报损扣减可用库存、虚拟仓库存、配货占用库存、总库存
                $remark = '处理异常：' . $abnormal_arr[$abnormal_info['type']] . ',当前节点：' . $status_arr[$item_info['distribution_status']] . ',返回节点：' . $status_arr[$status];
                if (3 == $status) {
                    //获取true_sku
                    $_item_platform_sku = new ItemPlatformSku();
                    $true_sku = $_item_platform_sku->getTrueSku($item_info['sku'], $item_info['site']);

                    //扣减虚拟仓库存
                    $_item_platform_sku
                        ->where(['sku' => $true_sku, 'platform_type' => $item_info['site']])
                        ->dec('stock', 1)
                        ->update();

                    //扣减可用库存、配货占用库存、总库存
                    $_item = new Item();
                    $_item
                        ->where(['sku' => $true_sku])
                        ->dec('available_stock', 1)
                        ->dec('distribution_occupy_stock', 1)
                        ->dec('stock', 1)
                        ->update();

                    //扣库存日志
                    $stock_data = [
                        'type'                      => 2,
                        'two_type'                  => 4,
                        'sku'                       => $item_info['sku'],
                        'public_id'                 => $item_info['id'],
                        'stock_change'              => -1,
                        'available_stock_change'    => -1,
                        'create_person'             => $admin->nickname,
                        'create_time'               => date('Y-m-d H:i:s'),
                        'remark'                    => '成检拒绝：减少总库存,减少可用库存'
                    ];
                    $_stock_log = new StockLog();
                    $_stock_log->allowField(true)->save($stock_data);

                    $remark .= ',扣减可用库存、虚拟仓库存、配货占用库存、总库存数量：1';
                }

                //记录日志
                DistributionLog::record($admin, $ids, 10, $remark);

                Db::commit();
            } catch (PDOException $e) {
                Db::rollback();
                $this->error($e->getMessage());
            } catch (Exception $e) {
                Db::rollback();
                $this->error($e->getMessage());
            }
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
}
