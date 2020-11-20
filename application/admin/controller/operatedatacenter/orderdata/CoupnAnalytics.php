<?php

namespace app\admin\controller\operatedatacenter\orderdata;

use app\common\controller\Backend;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use think\Controller;
use think\Db;
use think\Request;

class CoupnAnalytics extends Backend
{
    /**
     * sku明细分析
     *
     * @return \think\Response
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
            if($filter['create_time-operate']){
                unset($filter['create_time-operate']);
                $this->request->get(['filter' => json_encode($filter)]);
            }
            if ($filter['time_str']) {
                $createat = explode(' ', $filter['time_str']);
                $map['p.created_at'] = ['between', [$createat[0], $createat[3].' 23:59:59']];
                unset($filter['time_str']);
                $this->request->get(['filter' => json_encode($filter)]);
            } else{
                if(isset($filter['time_str'])){
                    unset($filter['time_str']);
                    $this->request->get(['filter' => json_encode($filter)]);
                }
                $start = date('Y-m-d', strtotime('-6 day'));
                $end   = date('Y-m-d 23:59:59');
                $map['p.created_at'] = ['between', [$start,$end]];
            }

            if($filter['sku']){
                $map['p.sku'] = $filter['sku'];
                unset($filter['sku']);
                $this->request->get(['filter' => json_encode($filter)]);
            }else{
                $map['p.sku'] = '';
            }
            if($filter['order_platform']){
                $site = $filter['order_platform'];
                unset($filter['order_platform']);
                $this->request->get(['filter' => json_encode($filter)]);
            }else{
                $site = 1;
            }
            $field = 'p.id,o.increment_id,o.created_at,o.customer_email,p.prescription_type,p.coatiing_name,p.frame_price,p.index_price';
            if($site == 2){
                $order_model = Db::connect('database.db_voogueme');

            }elseif($site == 3){
                $order_model = Db::connect('database.db_nihao');
                $field = 'p.id,o.increment_id,o.created_at,o.customer_email,p.prescription_type,p.frame_price,p.index_price';
            }else{
                $order_model = Db::connect('database.db_zeelool');
            }
            $order_model->table('sales_flat_order_item_prescription')->query("set time_zone='+8:00'");
            $map['o.status'] = ['in', ['free_processing', 'processing', 'complete', 'paypal_reversed', 'payment_review', 'paypal_canceled_reversal']];
            $map['o.order_type'] = 1;
            list($where, $sort, $order, $offset, $limit) = $this->buildparams();
            $total = $order_model->table('sales_flat_order_item_prescription')
                ->alias('p')
                ->join('sales_flat_order o','p.order_id=o.entity_id')
                ->field($field)
                ->where($where)
                ->where($map)
                ->order($sort, $order)
                ->count();

            $list = $order_model->table('sales_flat_order_item_prescription')
                ->alias('p')
                ->join('sales_flat_order o','p.order_id=o.entity_id')
                ->field($field)
                ->where($where)
                ->where($map)
                ->order($sort, $order)
                ->limit($offset, $limit)
                ->select();
            $list = collection($list)->toArray();
            foreach ($list as $key=>$value){
                $list[$key]['number'] = $key+1;
                $list[$key]['price'] = round($value['frame_price']+$value['index_price'],2);
            }
            $result = array("total" => $total, "rows" => $list);

            return json($result);
        }
        $this->magentoplatform = new \app\admin\model\platformmanage\MagentoPlatform();
        //查询对应平台权限
        $magentoplatformarr = $this->magentoplatform->getAuthSite();
        foreach ($magentoplatformarr as $key=>$val){
            if(!in_array($val['name'],['zeelool','voogueme','nihao'])){
                unset($magentoplatformarr[$key]);
            }
        }
        $this->view->assign('magentoplatformarr',$magentoplatformarr);
        return $this->view->fetch();
    }

    /**
     * 首购人数/复购人数
     *
     * @Description
     * @author wpl
     * @since 2020/10/14 15:02:02 
     * @return void
     */
    public function user_data_pie()
    {
        if ($this->request->isAjax()) {
            $params = $this->request->param();
            $site = $params['order_platform'] ? $params['order_platform'] : 1;
            if ($params['time_str']) {
                $createat = explode(' ', $params['time_str']);
            } else{
                $start = date('Y-m-d', strtotime('-6 day'));
                $start = date('Y-m-d', strtotime('-1000 day'));
                $end = date('Y-m-d 23:59:59');
                $seven_days = $start . ' 00:00:00 - ' . $end . ' 00:00:00';
                $createat = explode(' ', $seven_days);
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
            }
            $model->table('sales_flat_order')->query("set time_zone='+8:00'");

            $map['status'] = ['in', ['free_processing', 'processing', 'complete', 'paypal_reversed', 'payment_review', 'paypal_canceled_reversal']];
            $map['created_at'] = ['between', [$createat[0] . ' ' . $createat[1], $createat[3] . ' ' . $createat[4]]];
            $map['order_type'] = ['=', 1];
            $total = $model->table('sales_flat_order')
                ->where($map)
                ->field('entity_id,created_at,applied_rule_ids')
                ->select();
            dump($total);die;

            $json['column'] = ['网站优惠券', '主页优惠券','用户优惠券','渠道优惠券','客服优惠券','未使用优惠券',];
            $json['columnData'] = [
                [
                    'name' => '网站优惠券',
                    'value' => 1,
                ],
                [
                    'name' => '主页优惠券',
                    'value' => 2,
                ],
                [
                    'name' => '用户优惠券',
                    'value' => 3,
                ],
                [
                    'name' => '渠道优惠券',
                    'value' => 4,
                ],
                [
                    'name' => '客服优惠券',
                    'value' => 5,
                ],
                [
                    'name' => '未使用优惠券',
                    'value' => 6,
                ],
            ];
            return json(['code' => 1, 'data' => $json]);
        }
    }


    /**
     * 处方类型占比饼图
     *
     * @Description
     * @author wpl
     * @since 2020/10/14 15:02:23 
     * @return void
     */
    public function lens_data_pie()
    {
        if ($this->request->isAjax()) {
            $params = $this->request->param();
            $site = $params['order_platform'] ? $params['order_platform'] : 1;
            $json['column'] = ['网站优惠券', '主页优惠券','用户优惠券','渠道优惠券','客服优惠券','未使用优惠券',];
            $json['columnData'] = [
                [
                    'name' => '网站优惠券',
                    'value' => 1000.25,
                ],
                [
                    'name' => '主页优惠券',
                    'value' => 2000.25,
                ],
                [
                    'name' => '用户优惠券',
                    'value' => 3000.25,
                ],
                [
                    'name' => '渠道优惠券',
                    'value' => 4000.25,
                ],
                [
                    'name' => '客服优惠券',
                    'value' => 5000.25,
                ],
                [
                    'name' => '未使用优惠券',
                    'value' => 6000.25,
                ],
            ];
            return json(['code' => 1, 'data' => $json]);
        }
    }

    public function export(){
        $this->model = new \app\admin\model\warehouse\Check;
        $this->check_item = new \app\admin\model\warehouse\CheckItem;
        set_time_limit(0);
        ini_set('memory_limit', '512M');
        $order_platform = input('order_platform');
        $time_str = input('time_str');
        $sku = input('sku');

        if ($time_str) {
            $createat = explode(' ', $time_str);
            $map['p.created_at'] = ['between', [$createat[0], $createat[3].' 23:59:59']];
        }
        if($sku){
            $map['p.sku'] = $sku;
        }
        $field = 'p.id,o.increment_id,o.created_at,o.customer_email,p.prescription_type,p.coatiing_name,p.frame_price,p.index_price';
        if($order_platform == 2){
            $order_model = Db::connect('database.db_voogueme');
        }elseif($order_platform == 3){
            $order_model = Db::connect('database.db_nihao');
            $field = 'p.id,o.increment_id,o.created_at,o.customer_email,p.prescription_type,p.frame_price,p.index_price';
        }else{
            $order_model = Db::connect('database.db_zeelool');
        }
        $order_model->table('sales_flat_order_item_prescription')->query("set time_zone='+8:00'");
        $map['o.status'] = ['in', ['free_processing', 'processing', 'complete', 'paypal_reversed', 'payment_review', 'paypal_canceled_reversal']];
        $map['o.order_type'] = 1;

        $list = $order_model->table('sales_flat_order_item_prescription')
            ->alias('p')
            ->join('sales_flat_order o','p.order_id=o.entity_id')
            ->field($field)
            ->where($map)
            ->select();
        $list = collection($list)->toArray();
        foreach ($list as $key=>$value){
            $list[$key]['number'] = $key+1;
            $list[$key]['price'] = round($value['frame_price']+$value['index_price'],2);
        }
        //从数据库查询需要的数据
        $spreadsheet = new Spreadsheet();
        $spreadsheet->setActiveSheetIndex(0);
        $spreadsheet->getActiveSheet()->setCellValue("A1", "序号");
        $spreadsheet->getActiveSheet()->setCellValue("B1", "订单号");
        $spreadsheet->getActiveSheet()->setCellValue("C1", "订单时间");
        $spreadsheet->getActiveSheet()->setCellValue("D1", "支付邮箱");
        $spreadsheet->getActiveSheet()->setCellValue("E1", "处方类型");
        $spreadsheet->getActiveSheet()->setCellValue("F1", "镀膜类型");
        $spreadsheet->getActiveSheet()->setCellValue("G1", "价格（镜框+镜片）");


        $spreadsheet->setActiveSheetIndex(0)->setTitle('SKU明细');
        $spreadsheet->setActiveSheetIndex(0);
        foreach ($list as $k=>$v){
            $spreadsheet->getActiveSheet()->setCellValue('A' . ($k * 1 + 2), $v['number']);
            $spreadsheet->getActiveSheet()->setCellValue('B' . ($k * 1 + 2), $v['increment_id']);
            $spreadsheet->getActiveSheet()->setCellValue('C' . ($k * 1 + 2), $v['created_at']);
            $spreadsheet->getActiveSheet()->setCellValue('D' . ($k * 1 + 2), $v['customer_email']);
            $spreadsheet->getActiveSheet()->setCellValue('E' . ($k * 1 + 2), $v['prescription_type']);
            $spreadsheet->getActiveSheet()->setCellValue('F' . ($k * 1 + 2), $v['coatiing_name']);
            $spreadsheet->getActiveSheet()->setCellValue('G' . ($k * 1 + 2), $v['price']);
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
        $savename = '订单数据' . date("YmdHis", time());;

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










