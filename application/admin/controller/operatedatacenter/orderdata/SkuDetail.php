<?php

namespace app\admin\controller\operatedatacenter\orderdata;

use app\common\controller\Backend;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use think\Controller;
use think\Db;
use think\Request;

class SkuDetail extends Backend
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
                $map_where['o.created_at'] = ['between', [$createat[0], $createat[3].' 23:59:59']];
                $order_where['o.created_at'] = ['lt',$createat[0]];
            } else{
                $start = date('Y-m-d', strtotime('-6 day'));
                $end   = date('Y-m-d 23:59:59');
                $map_where['o.created_at'] = ['between', [$start,$end]];
                $order_where['o.created_at'] = ['lt',$start];
            }
            //首购人数
            if($site == 2){
                $order_model = new \app\admin\model\order\order\Voogueme();
            }elseif($site == 3){
                $order_model = new \app\admin\model\order\order\Nihao();
            }else{
                $order_model = new \app\admin\model\order\order\Zeelool();
            }
            $map['o.status'] = ['in', ['free_processing', 'processing', 'complete', 'paypal_reversed', 'payment_review', 'paypal_canceled_reversal']];
            $map['o.customer_id'] = ['>',0];
            $map['i.sku'] = $params['sku'];
            $map['o.order_type'] = 1;
            $customer_ids = $order_model->where($map_where)->alias('o')->join('sales_flat_order_item i','o.entity_id=i.order_id')->where($map)->column('distinct o.customer_id');
            $first_shopping_num = 0;
            foreach ($customer_ids as $val){
                $order_where_arr['customer_id'] = $val;
                $is_buy = $order_model->where($order_where)->where($order_where_arr)->alias('o')->join('sales_flat_order_item i','o.entity_id=i.order_id')->where($map)->value('o.entity_id');
                if(!$is_buy){
                    $first_shopping_num++;
                }
            }
            //复购用户数
            //查询时间段内的订单 根据customer_id先计算出此事件段内的复购用户数
            $again_buy_num1 = $order_model->alias('o')
                ->join('sales_flat_order_item i','o.entity_id=i.order_id')
                ->where($map_where)
                ->where($map)
                ->group('customer_id')
                ->having('count(customer_id)>1')
                ->count('customer_id');
            $again_buy_data2 = $order_model->alias('o')
                ->join('sales_flat_order_item i','o.entity_id=i.order_id')
                ->where($map_where)
                ->where($map)
                ->group('customer_id')
                ->having('count(customer_id)<=1')
                ->field('customer_id')
                ->select();
            $again_buy_num2 = 0;
            foreach ($again_buy_data2 as $v){
                //查询时间段内是否进行购买行为
                $order_where_arr['customer_id'] = $v['customer_id'];
                $is_buy = $order_model->where($order_where)->where($order_where_arr)->alias('o')->join('sales_flat_order_item i','o.entity_id=i.order_id')->where($map)->value('o.entity_id');
                if($is_buy){
                    $again_buy_num2++;
                }
            }

            $again_buy_num = $again_buy_num1+$again_buy_num2;
            $json['column'] = ['首购人数', '复购人数'];
            $json['columnData'] = [
                [
                    'name' => '首购人数',
                    'value' => $first_shopping_num,
                ],
                [
                    'name' => '复购人数',
                    'value' => $again_buy_num,
                ],
            ];
            return json(['code' => 1, 'data' => $json]);
        }
    }


    /**
     * 各品类商品销量趋势
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
            $data = $this->prescrtion_data($site,$params['time_str'],$params['sku']);
            $column = array_column($data,'name');
            $json['column'] = $column;
            $json['columnData'] = $data;

            return json(['code' => 1, 'data' => $json]);
        }
    }
    function prescrtion_data($site = 1,$time_str = '',$sku = ''){
        $order_num = $this->prescrtion_num('',$site,$time_str,$sku);

        $single_vision_num = $this->prescrtion_num('SingleVision',$site,$time_str,$sku);
        $single_vision_arr = array(
            'name'=>'single vision',
            'value'=>$single_vision_num,
        );
        $progressive_num = $this->prescrtion_num('Progressive',$site,$time_str,$sku);
        $progressive_arr = array(
            'name'=>'progressive',
            'value'=>$progressive_num,
        );
        $reading_glasses_num = $this->prescrtion_num('Readingglasses',$site,$time_str,$sku);
        $reading_glasses_arr = array(
            'name'=>'reading glasses',
            'value'=>$reading_glasses_num,
        );
        $reading_glassesno_num = $this->prescrtion_num('ReadingGlassesNon',$site,$time_str,$sku);
        $reading_glassesno_arr = array(
            'name'=>'reading glasses no prescription',
            'value'=>$reading_glassesno_num,
        );
        $no_prescription_num = $this->prescrtion_num('NonPrescription',$site,$time_str,$sku);
        $no_prescription_arr = array(
            'name'=>'no prescription',
            'value'=>$no_prescription_num,
        );
        $sunglasses_num = $this->prescrtion_num('Sunglasses',$site,$time_str,$sku);
        $sunglasses_arr = array(
            'name'=>'sunglasses',
            'value'=>$sunglasses_num,
        );
        $sunglassesno_num = $this->prescrtion_num('SunGlassesNoprescription',$site,$time_str,$sku);
        $sunglassesno_arr = array(
            'name'=>'sunglasses non-prescription',
            'value'=>$sunglassesno_num,
        );
        $sports_single_vision_num = $this->prescrtion_num('SportsSingleVision',$site,$time_str,$sku);
        $sports_single_vision_arr = array(
            'name'=>'sports single vision',
            'value'=>$sports_single_vision_num,
        );
        $sports_progressive_num = $this->prescrtion_num('SportsProgressive',$site,$time_str,$sku);
        $sports_progressive_arr = array(
            'name'=>'sports progressive',
            'value'=>$sports_progressive_num,
        );
        $frame_only_num = $order_num-$single_vision_num-$progressive_num-$reading_glasses_num-$reading_glassesno_num-$no_prescription_num-$sunglasses_num-$sunglassesno_num-$sports_single_vision_num-$sports_progressive_num;
        $frame_only_arr = array(
            'name'=>'frame only',
            'value'=>$frame_only_num,
        );
        $arr = [$frame_only_arr,$single_vision_arr,$progressive_arr,$reading_glasses_arr,$reading_glassesno_arr,$no_prescription_arr,$sunglasses_arr,$sunglassesno_arr,$sports_single_vision_arr,$sports_progressive_arr];
        return $arr;
    }
    function prescrtion_num($flag = '',$site = 1,$time_str = '',$sku = ''){
        if($site == 2){
            $order_model = Db::connect('database.db_voogueme');
        }elseif($site == 3){
            $order_model = Db::connect('database.db_nihao');
        }else{
            $order_model = Db::connect('database.db_zeelool');
        }
        $order_model->table('sales_flat_order_item_prescription')->query("set time_zone='+8:00'");
        if(!$time_str){
            $start = date('Y-m-d', strtotime('-6 day'));
            $end   = date('Y-m-d 23:59:59');
            $time_str = $start .' 00:00:00 - ' .$end.' 00:00:00';
        }
        $createat = explode(' ', $time_str);
        $where['p.created_at'] = ['between', [$createat[0], $createat[3].' 23:59:59']];
        $where['o.status'] = ['in', ['free_processing', 'processing', 'complete', 'paypal_reversed', 'payment_review', 'paypal_canceled_reversal']];
        $where['p.sku'] = $sku;
        $where['o.order_type'] = 1;
        if($flag){
            $map['p.prescription_type'] = $flag;
            $count = $order_model->table('sales_flat_order_item_prescription')->alias('p')->join('sales_flat_order o','p.order_id=o.entity_id')->where($where)->where($map)->count();
        }else{
            $count = $order_model->table('sales_flat_order_item_prescription')->alias('p')->join('sales_flat_order o','p.order_id=o.entity_id')->where($where)->count();
        }
        return $count;
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










