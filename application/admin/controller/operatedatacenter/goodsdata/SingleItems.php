<?php

namespace app\admin\controller\operatedatacenter\goodsdata;

use app\admin\model\itemmanage\ItemPlatformSku;
use app\common\controller\Backend;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use think\Controller;
use think\Db;
use think\Request;

class SingleItems extends Backend
{
    /**
     * 单品查询某个sku的订单列表
     *
     * Created by Phpstorm.
     * User: jhh
     * Date: 2020/12/17
     * Time: 13:43:45
     */
    public function index()
    {
        //设置过滤方法
        $this->request->filter(['strip_tags']);
        if ($this->request->isAjax()) {
            $filter = json_decode($this->request->get('filter'), true);
            //如果发送的来源是Selectpage，则转发到Selectpage
            if ($this->request->request('keyField')) {
                return $this->selectpage();
            }
            if ($filter['create_time-operate']) {
                unset($filter['create_time-operate']);
                $this->request->get(['filter' => json_encode($filter)]);
            }
            if ($filter['time_str']) {
                $createat = explode(' ', $filter['time_str']);
                $map['p.created_at'] = ['between', [$createat[0], $createat[3] . ' 23:59:59']];
                unset($filter['time_str']);
                $this->request->get(['filter' => json_encode($filter)]);
            } else {
                if (isset($filter['time_str'])) {
                    unset($filter['time_str']);
                    $this->request->get(['filter' => json_encode($filter)]);
                }
                $start = date('Y-m-d', strtotime('-6 day'));
                $end = date('Y-m-d 23:59:59');
                $map['p.created_at'] = ['between', [$start, $end]];
            }

            if ($filter['sku']) {
                $map['p.sku'] = ['like',$filter['sku']];
                unset($filter['sku']);
                $this->request->get(['filter' => json_encode($filter)]);
            }
            if ($filter['order_platform']) {
                $site = $filter['order_platform'];
                unset($filter['order_platform']);
                $this->request->get(['filter' => json_encode($filter)]);
            } else {
                $site = 1;
            }
            if ($site == 2) {
                $order_model = Db::connect('database.db_voogueme');
            } elseif ($site == 3) {
                $order_model = Db::connect('database.db_nihao');
            } else {
                $order_model = Db::connect('database.db_zeelool');
            }
            $order_model->table('sales_flat_order_item_prescription')->query("set time_zone='+8:00'");
            $map['o.status'] = ['in', ['free_processing', 'processing', 'complete', 'paypal_reversed', 'payment_review', 'paypal_canceled_reversal']];
            $map['o.order_type'] = 1;
            list($where, $sort, $order, $offset, $limit) = $this->buildparams();
            $total = $order_model
                ->table('sales_flat_order')
                ->where($map)
                ->alias('o')
                ->join(['sales_flat_order_item' => 'p'], 'o.entity_id=p.order_id')
                ->group('o.entity_id')
                ->count();
            $list = $order_model
                ->table('sales_flat_order')
                ->where($map)
                ->alias('o')
                ->join(['sales_flat_order_item' => 'p'], 'o.entity_id=p.order_id')
                ->group('o.entity_id')
                ->order('o.created_at','desc')
                ->limit($offset, $limit)
                ->select();
            $list = collection($list)->toArray();
            $result = array("total" => $total, "rows" => $list);

            return json($result);
        }
        $this->magentoplatform = new \app\admin\model\platformmanage\MagentoPlatform();
        //查询对应平台权限
        $magentoplatformarr = $this->magentoplatform->getAuthSite();
        foreach ($magentoplatformarr as $key => $val) {
            if (!in_array($val['name'], ['zeelool', 'voogueme', 'nihao'])) {
                unset($magentoplatformarr[$key]);
            }
        }
        $this->view->assign('magentoplatformarr', $magentoplatformarr);
        return $this->view->fetch();
    }

    /**
     * 中间表格sku的订单各项指标
     *
     * Created by Phpstorm.
     * User: jhh
     * Date: 2020/12/17
     * Time: 13:44:18
     */
    public function ajax_top_data()
    {
        if ($this->request->isAjax()) {
            $params = $this->request->param();
            //站点
            $order_platform = $params['order_platform'] ? $params['order_platform'] : 1;
            //时间
            $time_str = $params['time_str'] ? $params['time_str'] : '';
            $createat = explode(' ', $time_str);
            $same_where['day_date'] = ['between', [$createat[0], $createat[3]]];
            $same_where['site'] = ['=', $order_platform];
            $sku = input('sku');
            $item_platform = new ItemPlatformSku();
            $sku = $item_platform->where('sku', $sku)->where('platform_sku', $order_platform)->value('platform_sku') ? $item_platform->where('sku', $sku)->where('platform_sku', $order_platform)->value('platform_sku') : $sku;
            switch ($order_platform) {
                case 1:
                    $model = Db::connect('database.db_zeelool');
                    $coatiing_price['b.coatiing_price'] = ['=',0];
                    break;
                case 2:
                    $model = Db::connect('database.db_voogueme');
                    $coatiing_price['b.coatiing_price'] = ['=',0];
                    break;
                case 3:
                    $model = Db::connect('database.db_nihao');
                    $coatiing_price =[];
                    break;
            }
            $model->table('sales_flat_order')->query("set time_zone='+8:00'");
            $model->table('sales_flat_order_item')->query("set time_zone='+8:00'");
            $model->table('sales_flat_order_item_prescription')->query("set time_zone='+8:00'");
            //此sku的总订单量
            $map['sku'] = ['like', $sku . '%'];
            $map['a.status'] = ['in', ['free_processing', 'processing', 'complete', 'paypal_reversed', 'payment_review', 'paypal_canceled_reversal']];
            $map['a.created_at'] = ['between', [$createat[0] . ' ' . $createat[1], $createat[3] . ' ' . $createat[4]]];
            $map['a.order_type'] = ['=', 1];
            $total = $model->table('sales_flat_order')
                ->where($map)
                ->alias('a')
                ->join(['sales_flat_order_item' => 'b'], 'a.entity_id=b.order_id')
                ->group('order_id')
                ->field('entity_id,sku,a.created_at,a.order_type,a.status')
                ->count();

            //整站订单量
            $whole_platform_order_num = Db::name('datacenter_day')->where($same_where)->sum('order_num');
            //订单占比
            $order_rate = $whole_platform_order_num == 0 ? 0 : round($total / $whole_platform_order_num * 100, 2) . '%';
            //平均订单副数
            $whole_glass = $model
                ->table('sales_flat_order_item')
                ->where('sku', 'like', $sku . '%')
                ->where('created_at', 'between', [$createat[0] . ' ' . $createat[1], $createat[3] . ' ' . $createat[4]])
                ->sum('qty_ordered');//sku总副数
            $avg_order_glass = $total == 0 ? 0 : round($whole_glass / $total, 2);
            if ($order_platform != 3) {
                //付费镜片订单数
                $nopay_jingpian_glass = $model
                    ->table('sales_flat_order')
                    ->alias('a')
                    ->join(['sales_flat_order_item_prescription' => 'b'], 'a.entity_id=b.order_id')
                    ->where('a.created_at', 'between', [$createat[0] . ' ' . $createat[1], $createat[3] . ' ' . $createat[4]])
                    ->where('sku', 'like', $sku . '%')
                    ->where('a.order_type', '=', 1)
                    ->where($coatiing_price)
                    ->where('a.status', 'in', ['free_processing', 'processing', 'complete', 'paypal_reversed', 'payment_review', 'paypal_canceled_reversal'])
                    ->where('b.index_price', '=', 0)
                    ->group('order_id')
                    ->count();
            } else {
                //付费镜片订单数
                $nopay_jingpian_glass = $model
                    ->table('sales_flat_order')
                    ->alias('a')
                    ->join(['sales_flat_order_item_prescription' => 'b'], 'a.entity_id=b.order_id')
                    ->where('a.created_at', 'between', [$createat[0] . ' ' . $createat[1], $createat[3] . ' ' . $createat[4]])
                    ->where('sku', 'like', $sku . '%')
                    ->where('a.order_type', '=', 1)
                    // ->where('b.coatiing_price', '=', 0)
                    ->where($coatiing_price)
                    ->where('a.status', 'in', ['free_processing', 'processing', 'complete', 'paypal_reversed', 'payment_review', 'paypal_canceled_reversal'])
                    ->where('b.index_price', '=', 0)
                    ->group('order_id')
                    ->count();
            }
            $pay_jingpian_glass = $total - $nopay_jingpian_glass;
            //付费镜片订单数占比
            $pay_jingpian_glass_rate = $total == 0 ? 0 : round($pay_jingpian_glass / $total * 100, 2) . '%';
            //只买一副的订单
            $only_one_glass_order_list = $model
                ->table('sales_flat_order_item')
                ->where('sku', 'like', $sku . '%')
                ->where('b.created_at', 'between', [$createat[0] . ' ' . $createat[1], $createat[3] . ' ' . $createat[4]])
                ->alias('a')
                ->join(['sales_flat_order' => 'b'], 'a.order_id=b.entity_id')
                ->where('b.order_type', '=', 1)
                ->where('b.status', 'in', ['free_processing', 'processing', 'complete', 'paypal_reversed', 'payment_review', 'paypal_canceled_reversal'])
                ->field('order_id,sum(qty_ordered) as all_qty_ordered')
                ->group('a.order_id')
                ->select();
            $only_one_glass_order_list = $model->table('sales_flat_order')
                ->where($map)
                ->alias('a')
                ->join(['sales_flat_order_item' => 'b'], 'a.entity_id=b.order_id')
                ->group('order_id')
                ->field('entity_id,sku,a.created_at,a.order_type,a.status,order_id,sum(qty_ordered) as all_qty_ordered')
                ->select();
            $only_one_glass_num = 0;
            foreach ($only_one_glass_order_list as $k=>$v) {
                $one = $model->table('sales_flat_order_item')->where('order_id',$v['order_id'])->sum('qty_ordered');
                if ($one == 1){
                    $only_one_glass_num += 1;
                }
            }
            //只买一副的订单占比
            $only_one_glass_rate = $total == 0 ? 0 : round($only_one_glass_num / $total * 100, 2) . '%';
            //订单总金额
            $whole_price = $model
                ->table('sales_flat_order')
                ->where($map)
                ->where('a.created_at', 'between', [$createat[0] . ' ' . $createat[1], $createat[3] . ' ' . $createat[4]])
                ->alias('a')
                ->join(['sales_flat_order_item' => 'b'], 'a.entity_id=b.order_id')
                ->field('base_grand_total')
                ->sum('base_grand_total');
            //订单客单价
            $every_price = $total == 0 ? 0 : round($whole_price / $total, 2);
            //关联购买
            $connect_buy = $model->table('sales_flat_order_item')
                ->where('sku', 'like', $sku . '%')
                ->where('created_at', 'between', [$createat[0] . ' ' . $createat[1], $createat[3] . ' ' . $createat[4]])
                ->distinct('order_id')
                ->field('order_id')
                ->select();//包含此sku的所有订单好
            $connect_buy = array_column($connect_buy, 'order_id');
            $skus = array();
            foreach ($connect_buy as $value) {
                $arr = $model->table('sales_flat_order_item')
                    ->where('order_id', $value)
                    ->field('sku')
                    ->select();//这些订单号内的所有sku
                $skus[] = array_column($arr, 'sku');
            }
            $array_sku = [];
            //获取关联购买的数量
            foreach ($skus as $k => $v) {
                foreach ($v as $vv) {
                    if ($vv != $sku) {
                        $array_sku[$vv] += 1;
                    }
                }
            }

            //平均每副订单金额
            $every_money = $total ? round($whole_price/$total,2) : 0;
            arsort($array_sku);
            $data = compact('sku', 'array_sku', 'total', 'orderPlatformList', 'whole_platform_order_num', 'order_rate', 'avg_order_glass', 'pay_jingpian_glass', 'pay_jingpian_glass_rate', 'only_one_glass_num', 'only_one_glass_rate', 'every_price', 'whole_price','every_money');

            $this->success('', '', $data);
        }
    }

    /**
     * 商品销量/现价折线图
     *
     * Created by Phpstorm.
     * User: jhh
     * Date: 2020/12/17
     * Time: 13:45:00
     */
    public function sku_sales_data_line()
    {
        if ($this->request->isAjax()) {
            $sku = input('sku');
            $site = input('order_platform');
            $time_str = input('time_str');
            $createat = explode(' ', $time_str);
            $same_where['day_date'] = ['between', [$createat[0], $createat[3]]];
            $same_where['site'] = ['=', $site];
            $same_where['platform_sku'] = ['like', $sku . '%'];
            $recent_day_num = Db::name('datacenter_sku_day')->where($same_where)->order('day_date', 'asc')->column('glass_num', 'day_date');
            $recent_day_now = Db::name('datacenter_sku_day')->where($same_where)->order('day_date', 'asc')->column('now_pricce', 'day_date');
            $json['xColumnName'] = array_keys($recent_day_num);
            $json['columnData'] = [
                [
                    'type' => 'line',
                    'data' => array_values($recent_day_num),
                    'name' => '商品销量',
                    'yAxisIndex' => 0,
                    'smooth' => true //平滑曲线
                ],
                [
                    'type' => 'line',
                    'data' => array_values($recent_day_now),
                    'name' => '现价',
                    'yAxisIndex' => 1,
                    'smooth' => true //平滑曲线
                ],

            ];
            return json(['code' => 1, 'data' => $json]);
        }
    }

    /**
     * 最近30天销量柱状图
     *
     * Created by Phpstorm.
     * User: jhh
     * Date: 2020/12/17
     * Time: 13:45:24
     */
    public function sku_sales_data_bar()
    {
        if ($this->request->isAjax()) {
            $sku = input('sku');
            $site = input('order_platform');
            $end = date('Y-m-d');
            $start = date('Y-m-d', strtotime("-30 days", strtotime($end)));
            $same_where['day_date'] = ['between', [$start, $end]];
            $same_where['site'] = ['=', $site];
            $same_where['platform_sku'] = ['like', $sku . '%'];
            $recent_30_day = Db::name('datacenter_sku_day')->where($same_where)->order('day_date', 'asc')->column('glass_num', 'day_date');
            $json['xColumnName'] = array_keys($recent_30_day);
            $json['columnData'] = [
                'type' => 'bar',
                'data' => array_values($recent_30_day),
                'name' => '销量'
            ];
            return json(['code' => 1, 'data' => $json]);
        }
    }

    /**
     * 导出关联购买数据
     *
     * Created by Phpstorm.
     * User: jhh
     * Date: 2020/12/17
     * Time: 13:45:51
     */
    public function export(){
        set_time_limit(0);
        ini_set('memory_limit', '512M');
        $order_platform = input('order_platform');
        $time_str = input('time_str');
        $sku = input('sku');
        if ($time_str) {
            $createat = explode(' ', $time_str);
        }
        switch ($order_platform) {
            case 1:
                $model = Db::connect('database.db_zeelool');
                break;
            case 2:
                $model = Db::connect('database.db_voogueme');
                break;
            case 3:
                $model = Db::connect('database.db_nihao');
                break;
        }
        $model->table('sales_flat_order')->query("set time_zone='+8:00'");
        $model->table('sales_flat_order_item')->query("set time_zone='+8:00'");
        $model->table('sales_flat_order_item_prescription')->query("set time_zone='+8:00'");
        //关联购买
        $connect_buy = $model->table('sales_flat_order_item')
            ->where('sku', 'like', $sku . '%')
            ->where('created_at', 'between', [$createat[0] . ' ' . $createat[1], $createat[3] . ' ' . $createat[4]])
            ->distinct('order_id')
            ->field('order_id')
            ->select();//包含此sku的所有订单好
        $connect_buy = array_column($connect_buy, 'order_id');
        $skus = array();
        foreach ($connect_buy as $value) {
            $arr = $model->table('sales_flat_order_item')
                ->where('order_id', $value)
                ->field('sku')
                ->select();//这些订单号内的所有sku
            $skus[] = array_column($arr, 'sku');
        }
        $array_sku = [];
        //获取关联购买的数量
        foreach ($skus as $k => $v) {
            foreach ($v as $vv) {
                if ($vv != $sku) {
                    $array_sku[$vv] += 1;
                }
            }
        }
        //从数据库查询需要的数据
        $spreadsheet = new Spreadsheet();
        $spreadsheet->setActiveSheetIndex(0);
        $spreadsheet->getActiveSheet()->setCellValue("A1", "sku");
        $spreadsheet->getActiveSheet()->setCellValue("B1", "数量");
        //设置宽度
        $spreadsheet->getActiveSheet()->getColumnDimension('A')->setWidth(60);
        $spreadsheet->getActiveSheet()->getColumnDimension('B')->setWidth(12);
        $spreadsheet->setActiveSheetIndex(0)->setTitle('SKU明细');
        $spreadsheet->setActiveSheetIndex(0);
        $num = 0;
        foreach ($array_sku as $k=>$v){
            $spreadsheet->getActiveSheet()->setCellValue('A' . ($num * 1 + 2), $k);
            $spreadsheet->getActiveSheet()->setCellValue('B' . ($num * 1 + 2), $v);
            $num += 1;
        }
        //设置边框
        $border = [
            'borders' => [
                'allBorders' => [
                    'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN, // 设置border样式
                    'color'       => ['argb' => 'FF000000'], // 设置border颜色
                ],
            ],
        ];
        $spreadsheet->getDefaultStyle()->getFont()->setName('微软雅黑')->setSize(12);
        $setBorder = 'A1:' . $spreadsheet->getActiveSheet()->getHighestColumn() . $spreadsheet->getActiveSheet()->getHighestRow();
        $spreadsheet->getActiveSheet()->getStyle($setBorder)->applyFromArray($border);
        $spreadsheet->getActiveSheet()->getStyle('A1:Q' . $spreadsheet->getActiveSheet()->getHighestRow())->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
        $spreadsheet->setActiveSheetIndex(0);
        $format = 'xlsx';
        $savename = 'sku:'.$sku .' '. $createat[0] .'至'.$createat[3] .'关联购买情况';
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










