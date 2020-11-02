<?php

namespace app\admin\controller\operatedatacenter\orderdata;

use app\admin\model\platformManage\MagentoPlatform;
use app\common\controller\Backend;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use think\Controller;
use think\Db;
use think\Request;

class OrderDataDetail extends Backend
{
    public function _initialize()
    {
        parent::_initialize();
        $this->zeelool = new \app\admin\model\order\order\Zeelool();
        $this->voogueme = new \app\admin\model\order\order\Voogueme();
        $this->nihao = new \app\admin\model\order\order\Nihao();
        $this->zeeloolOperate  = new \app\admin\model\operatedatacenter\Zeelool;
        $this->vooguemeOperate  = new \app\admin\model\operatedatacenter\Voogueme;
        $this->nihaoOperate  = new \app\admin\model\operatedatacenter\Nihao;
        $this->magentoplatform = new \app\admin\model\platformmanage\MagentoPlatform();
    }
    /**
     * 订单数据明细页面展示
     *
     * @return \think\Response
     */
    public function index()
    {
        //设置过滤方法
        $this->request->filter(['strip_tags']);
        if ($this->request->isAjax()) {
            //如果发送的来源是Selectpage，则转发到Selectpage
            if ($this->request->request('keyField')) {
                return $this->selectpage();
            }
            $filter = json_decode($this->request->get('filter'), true);
            if($filter['one_time-operate']){
                unset($filter['one_time-operate']);
                $this->request->get(['filter' => json_encode($filter)]);
            }
            if($filter['order_platform'] == 2){
                $order_model = $this->voogueme;
                $web_model = Db::connect('database.db_voogueme');
                $site = 2;
            }elseif($filter['order_platform'] == 3){
                $order_model = $this->nihao;
                $web_model = Db::connect('database.db_nihao');
                $site = 3;
            }else{
                $order_model = $this->zeelool;
                $web_model = Db::connect('database.db_zeelool');
                $site = 1;
            }
            $web_model->table('customer_entity')->query("set time_zone='+8:00'");
            $web_model->table('sales_flat_order_payment')->query("set time_zone='+8:00'");
            $web_model->table('sales_flat_order_address')->query("set time_zone='+8:00'");
            $web_model->table('sales_flat_order_item_prescription')->query("set time_zone='+8:00'");
            if($filter['time_str']){
                $createat = explode(' ', $filter['time_str']);
                $map['o.created_at'] = ['between', [$createat[0].' '.$createat[1], $createat[3].' '.$createat[4]]];
            }else{
                if(isset($filter['time_str'])){
                    unset($filter['time_str']);
                    $this->request->get(['filter' => json_encode($filter)]);
                }
                $start = date('Y-m-d', strtotime('-6 day'));
                $end   = date('Y-m-d 23:59:59');
                $map['o.created_at'] = ['between', [$start,$end]];
            }
            if($filter['order_status']){
                if($filter['order_status'] == 1){
                    //已发货
                    $node_where['node_type'] = 7;
                }elseif ($filter['order_status'] == 2){
                    $node_where['node_type'] = ['in',[8,10]];
                }elseif ($filter['order_status'] == 3){
                    $node_where['node_type'] = 30;
                }elseif ($filter['order_status'] == 4){
                    $node_where['node_type'] = 40;
                }elseif ($filter['order_status'] == 5){
                    $node_where['node_type'] = 35;
                }
                $order_ids = Db::name('order_node')->where($node_where)->column('order_id');
                $map['o.entity_id'] = ['in',$order_ids];
            }
            if($filter['customer_type']){
                $map['c.group_id'] = $filter['customer_type'];
            }
            if($filter['store_id']){
                $map['o.store_id'] = $filter['store_id'];
            }
            if($filter['increment_id']){
                $map['o.increment_id'] = $filter['increment_id'];
            }
            unset($filter['time_str']);
            unset($filter['order_platform']);
            unset($filter['increment_id']);
            unset($filter['order_status']);
            unset($filter['customer_type']);
            unset($filter['store_id']);
            $this->request->get(['filter' => json_encode($filter)]);
            list($where, $sort, $order, $offset, $limit) = $this->buildparams();
            $sort = 'o.entity_id';
            $total = $order_model->alias('o')
                ->join('customer_entity c','o.customer_id=c.entity_id')
                ->where($where)
                ->where($map)
                ->order($sort, $order)
                ->count('o.entity_id');
            $list = $order_model->alias('o')
                ->join('customer_entity c','o.customer_id=c.entity_id')
                ->where($where)
                ->where($map)
                ->order($sort, $order)
                ->limit($offset, $limit)
                ->field('o.entity_id,o.increment_id,o.created_at,o.base_grand_total,o.base_shipping_amount,o.status,o.store_id,o.protect_code,o.shipping_method,o.customer_email,o.customer_id,o.base_discount_amount')
                ->select();
            $list = collection($list)->toArray();
            foreach ($list as $key=>$value){
                $list[$key]['increment_id'] = $value['increment_id'];
                $list[$key]['created_at'] = $value['created_at'];
                $list[$key]['base_grand_total'] = round($value['base_grand_total'],2);
                $list[$key]['base_shipping_amount'] = round($value['base_shipping_amount'],2);
                $order_node = Db::name('order_node')->where('order_id',$value['entity_id'])->value('node_type');
                if($order_node == 7){
                    $order_shipping_status = '已发货';
                }elseif ($order_node == 8 && $order_node == 10){
                    $order_shipping_status = '运输途中';
                }elseif ($order_node == 30){
                    $order_shipping_status = '到达待取';
                }elseif ($order_node == 40){
                    $order_shipping_status = '成功签收';
                }elseif ($order_node == 35){
                    $order_shipping_status = '投递失败';
                }else{
                    $status_arr = ['free_processing', 'processing', 'complete', 'paypal_reversed', 'payment_review', 'paypal_canceled_reversal'];
                    if(in_array($value['status'],$status_arr)){
                        $order_shipping_status = '支付成功';
                    }else{
                        $order_shipping_status = '-';
                    }
                }
                $list[$key]['status'] = $order_shipping_status;
                switch ($value['store_id']){
                    case 1:
                        $store_id = 'PC';
                        break;
                    case 4:
                        $store_id = 'M';
                        break;
                    case 5:
                        $store_id = 'Ios';
                        break;
                    case 6:
                        $store_id = 'Android';
                        break;
                }
                $list[$key]['store_id'] = $store_id;
                $list[$key]['protect_code'] = $value['protect_code'];
                $list[$key]['shipping_method'] = $value['shipping_method'];  //快递类别
                //收货信息
                $shipping_where['address_type'] = 'shipping';
                $shipping_where['parent_id'] = $value['entity_id'];
                $shipping = $web_model->table('sales_flat_order_address')->where($shipping_where)->field('firstname,lastname,telephone,country_id')->find();
                $list[$key]['shipping_name'] = $shipping['firstname'].''.$shipping['lastname'];  //收货姓名
                $list[$key]['customer_email'] = $value['customer_email'];   //支付邮箱
                //客户信息
                if($value['customer_id']){
                    $customer_where['entity_id'] = $value['customer_id'];
                    $customer = $web_model->table('customer_entity')->where($customer_where)->field('email,group_id,created_at')->find();
                    switch ($customer['group_id']){
                        case 1:
                            $group = '普通';
                            break;
                        case 2:
                            $group = '批发';
                            break;
                        case 4:
                            $group = 'VIP';
                            break;
                    }
                    $register_time = $customer['created_at'];
                    $register_email = $customer['email'];
                }else{
                    $group = '游客';
                    $register_time = '';
                    $register_email = '';
                }
                $list[$key]['customer_type'] = $group;   //客户类型
                $list[$key]['discount_rate'] = $value['base_grand_total'] ? round(($value['base_discount_amount']/$value['base_grand_total']),2).'%' : 0;  //折扣百分比
                $list[$key]['discount_money'] = round($value['base_discount_amount'],2);  //折扣金额
                $work_list_where['platform_order'] = $value['increment_id'];
                $work_list = Db::name('work_order_list')->where($work_list_where)->field('id,is_refund')->select();
                $work_list = collection($work_list)->toArray();
                $work_list_num = count($work_list);
                $work_list_is_refund = array_column($work_list,'is_refund');
                if(in_array(1,$work_list_is_refund)){
                    $is_refund = '有';
                }else{
                    $is_refund = '无';
                }
                $list[$key]['is_refund'] = $is_refund;  //是否退款
                $list[$key]['country_id'] = $shipping['country_id'];   //收货国家
                //支付信息
                $payment_where['parent_id'] = $value['entity_id'];
                $payment = $web_model->table('sales_flat_order_payment')->where($payment_where)->value('method');
                $list[$key]['payment_method'] =  $payment == 'oceanpayment_creditcard' ? '钱海' : 'Paypal';  //支付方式
                //处方信息
                $prescription_where['order_id'] = $value['entity_id'];
                $frame_price = $web_model->table('sales_flat_order_item_prescription')->where($prescription_where)->sum('frame_price');
                $list[$key]['frame_price'] = round($frame_price,2);
                $list[$key]['frame_num'] = $web_model->table('sales_flat_order_item_prescription')->where($prescription_where)->count();
                if($site == 3){
                    $list[$key]['lens_num'] = $web_model->table('sales_flat_order_item_prescription')->where($prescription_where)->where('third_id','neq','')->count();
                }else{
                    $list[$key]['lens_num'] = $web_model->table('sales_flat_order_item_prescription')->where($prescription_where)->where('index_id','neq','')->count();
                }
                $list[$key]['is_box_num'] = $web_model->table('sales_flat_order_item_prescription')->where($prescription_where)->where('goods_type',6)->count();
                $lens_price = $web_model->table('sales_flat_order_item_prescription')->where($prescription_where)->sum('index_price');
                $list[$key]['lens_price'] = round($lens_price,2);
                $list[$key]['telephone'] = $shipping['telephone'];
                $skus = $web_model->table('sales_flat_order_item_prescription')->where($prescription_where)->column('sku');
                $skus = collection($skus)->toArray();
                $list[$key]['sku'] = implode(',',$skus);
                $list[$key]['register_time'] = $register_time;
                $list[$key]['register_email'] = $register_email;
                $list[$key]['work_list_num'] = $work_list_num;
            }
            $result = array("total" => $total, "rows" => $list);

            return json($result);
        }
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
    public function export(){
        $this->model = new \app\admin\model\warehouse\Check;
        $this->check_item = new \app\admin\model\warehouse\CheckItem;
        set_time_limit(0);
        ini_set('memory_limit', '512M');
        $order_platform = input('order_platform');
        $time_str = input('time_str');
        $increment_id = input('increment_id');
        $order_status = input('order_status');
        $customer_type = input('customer_type');
        $store_id = input('store_id');
        $field = input('field');
        if($order_platform == 2){
            $order_model = $this->voogueme;
            $web_model = Db::connect('database.db_voogueme');
            $site = 2;
        }elseif($order_platform == 3){
            $order_model = $this->nihao;
            $web_model = Db::connect('database.db_nihao');
            $site = 3;
        }else{
            $order_model = $this->zeelool;
            $web_model = Db::connect('database.db_zeelool');
            $site = 1;
        }
        $web_model->table('customer_entity')->query("set time_zone='+8:00'");
        $web_model->table('sales_flat_order_payment')->query("set time_zone='+8:00'");
        $web_model->table('sales_flat_order_address')->query("set time_zone='+8:00'");
        $web_model->table('sales_flat_order_item_prescription')->query("set time_zone='+8:00'");
        if($time_str){
            $createat = explode(' ', $time_str);
            $map['o.created_at'] = ['between', [$createat[0].' '.$createat[1], $createat[3].' '.$createat[4]]];
        }else{
            $start = date('Y-m-d', strtotime('-6 day'));
            $end   = date('Y-m-d 23:59:59');
            $map['o.created_at'] = ['between', [$start,$end]];
        }
        if($increment_id){
            $map['o.increment_id'] = $increment_id;
        }
        if($order_status){
            if($order_status == 1){
                //已发货
                $node_where['node_type'] = 7;
            }elseif ($order_status == 2){
                $node_where['node_type'] = ['in',[8,10]];
            }elseif ($order_status == 3){
                $node_where['node_type'] = 30;
            }elseif ($order_status == 4){
                $node_where['node_type'] = 40;
            }elseif ($order_status == 5){
                $node_where['node_type'] = 35;
            }
            $order_ids = Db::name('order_node')->where($node_where)->column('order_id');
            $map['o.entity_id'] = ['in',$order_ids];
        }
        if($customer_type){
            $map['c.group_id'] = $customer_type;
        }
        if($store_id){
            $map['o.store_id'] = $store_id;
        }
        $list = $order_model->alias('o')
            ->join('customer_entity c','o.customer_id=c.entity_id')
            ->where($map)
            ->field('o.entity_id,o.increment_id,o.created_at,o.base_grand_total,o.base_shipping_amount,o.status,o.store_id,o.protect_code,o.shipping_method,o.customer_email,o.customer_id,o.base_discount_amount')
            ->select();
        $list = collection($list)->toArray();
        foreach ($list as $key=>$value){
            $list[$key]['increment_id'] = $value['increment_id'];
            $list[$key]['created_at'] = $value['created_at'];
            $list[$key]['base_grand_total'] = round($value['base_grand_total'],2);
            $list[$key]['base_shipping_amount'] = round($value['base_shipping_amount'],2);
            $order_node = Db::name('order_node')->where('order_id',$value['entity_id'])->value('node_type');
            if($order_node == 7){
                $order_shipping_status = '已发货';
            }elseif ($order_node == 8 && $order_node == 10){
                $order_shipping_status = '运输途中';
            }elseif ($order_node == 30){
                $order_shipping_status = '到达待取';
            }elseif ($order_node == 40){
                $order_shipping_status = '成功签收';
            }elseif ($order_node == 35){
                $order_shipping_status = '投递失败';
            }else{
                $status_arr = ['free_processing', 'processing', 'complete', 'paypal_reversed', 'payment_review', 'paypal_canceled_reversal'];
                if(in_array($value['status'],$status_arr)){
                    $order_shipping_status = '支付成功';
                }else{
                    $order_shipping_status = '-';
                }
            }
            $list[$key]['status'] = $order_shipping_status;
            switch ($value['store_id']){
                case 1:
                    $store_id = 'PC';
                    break;
                case 4:
                    $store_id = 'M';
                    break;
                case 5:
                    $store_id = 'Ios';
                    break;
                case 6:
                    $store_id = 'Android';
                    break;
            }
            $list[$key]['store_id'] = $store_id;
            $list[$key]['protect_code'] = $value['protect_code'];
            $list[$key]['shipping_method'] = $value['shipping_method'];  //快递类别
            //收货信息
            $shipping_where['address_type'] = 'shipping';
            $shipping_where['parent_id'] = $value['entity_id'];
            $shipping = $web_model->table('sales_flat_order_address')->where($shipping_where)->field('firstname,lastname,telephone,country_id')->find();
            $list[$key]['shipping_name'] = $shipping['firstname'].''.$shipping['lastname'];  //收货姓名
            $list[$key]['customer_email'] = $value['customer_email'];   //支付邮箱
            //客户信息
            if($value['customer_id']){
                $customer_where['entity_id'] = $value['customer_id'];
                $customer = $web_model->table('customer_entity')->where($customer_where)->field('email,group_id,created_at')->find();
                switch ($customer['group_id']){
                    case 1:
                        $group = '普通';
                        break;
                    case 2:
                        $group = '批发';
                        break;
                    case 4:
                        $group = 'VIP';
                        break;
                }
                $register_time = $customer['created_at'];
                $register_email = $customer['email'];
            }else{
                $group = '游客';
                $register_time = '';
                $register_email = '';
            }
            $list[$key]['customer_type'] = $group;   //客户类型
            $list[$key]['discount_rate'] = $value['base_grand_total'] ? round(($value['base_discount_amount']/$value['base_grand_total']),2).'%' : 0;  //折扣百分比
            $list[$key]['discount_money'] = round($value['base_discount_amount'],2);  //折扣金额
            $work_list_where['platform_order'] = $value['increment_id'];
            $work_list = Db::name('work_order_list')->where($work_list_where)->field('id,is_refund')->select();
            $work_list = collection($work_list)->toArray();
            $work_list_num = count($work_list);
            $work_list_is_refund = array_column($work_list,'is_refund');
            if(in_array(1,$work_list_is_refund)){
                $is_refund = '有';
            }else{
                $is_refund = '无';
            }
            $list[$key]['is_refund'] = $is_refund;  //是否退款
            $list[$key]['country_id'] = $shipping['country_id'];   //收货国家
            //支付信息
            $payment_where['parent_id'] = $value['entity_id'];
            $payment = $web_model->table('sales_flat_order_payment')->where($payment_where)->value('method');
            $list[$key]['payment_method'] =  $payment == 'oceanpayment_creditcard' ? '钱海' : 'Paypal';  //支付方式
            //处方信息
            $prescription_where['order_id'] = $value['entity_id'];
            $frame_price = $web_model->table('sales_flat_order_item_prescription')->where($prescription_where)->sum('frame_price');
            $list[$key]['frame_price'] = round($frame_price,2);
            $list[$key]['frame_num'] = $web_model->table('sales_flat_order_item_prescription')->where($prescription_where)->count();
            if($site == 3){
                $list[$key]['lens_num'] = $web_model->table('sales_flat_order_item_prescription')->where($prescription_where)->where('third_id','neq','')->count();
            }else{
                $list[$key]['lens_num'] = $web_model->table('sales_flat_order_item_prescription')->where($prescription_where)->where('index_id','neq','')->count();
            }
            $list[$key]['is_box_num'] = $web_model->table('sales_flat_order_item_prescription')->where($prescription_where)->where('goods_type',6)->count();
            $lens_price = $web_model->table('sales_flat_order_item_prescription')->where($prescription_where)->sum('index_price');
            $list[$key]['lens_price'] = round($lens_price,2);
            $list[$key]['telephone'] = $shipping['telephone'];
            $skus = $web_model->table('sales_flat_order_item_prescription')->where($prescription_where)->column('sku');
            $skus = collection($skus)->toArray();
            $list[$key]['sku'] = implode(',',$skus);
            $list[$key]['register_time'] = $register_time;
            $list[$key]['register_email'] = $register_email;
            $list[$key]['work_list_num'] = $work_list_num;
        }
        $field_arr = explode(',',$field);
        $data = array();
        $i = 0;
        foreach ($list as $k=>$v){
            foreach ($field_arr as $kk=>$vv){
                if(isset($v[$vv])){
                    $data[$i][$vv] = $v[$vv];
                }
            }
            $i++;
        }
        $table_arr = ['A1','B1','C1','D1','E1','F1','G1','H1','I1','J1','K1','L1','M1','N1','O1','P1','Q1','R1','S1','T1','U1','V1','W1','X1','Y1','Z1'];
        $table_arr1 = ['A','B','C','D','E','F','G','H','I','J','K','L','M','N','O','P','Q','R','S','T','U','V','W','X','Y','Z'];
        $field_info = array(
            array(
                'name'=>'订单编号',
                'field'=>'increment_id',
            ),
            array(
                'name'=>'订单时间',
                'field'=>'created_at',
            ),
            array(
                'name'=>'订单金额',
                'field'=>'base_grand_total',
            ),
            array(
                'name'=>'邮费',
                'field'=>'base_shipping_amount',
            ),
            array(
                'name'=>'订单状态',
                'field'=>'status',
            ),
            array(
                'name'=>'设备类型',
                'field'=>'store_id',
            ),
            array(
                'name'=>'使用的code码',
                'field'=>'protect_code',
            ),
            array(
                'name'=>'快递类别',
                'field'=>'shipping_method',
            ),
            array(
                'name'=>'收货姓名',
                'field'=>'shipping_name',
            ),
            array(
                'name'=>'支付邮箱',
                'field'=>'customer_email',
            ),
            array(
                'name'=>'客户类型',
                'field'=>'customer_type',
            ),
            array(
                'name'=>'折扣百分比',
                'field'=>'discount_rate',
            ),
            array(
                'name'=>'折扣金额',
                'field'=>'discount_money',
            ),
            array(
                'name'=>'有无退款',
                'field'=>'is_refund',
            ),
            array(
                'name'=>'收货国家',
                'field'=>'country_id',
            ),
            array(
                'name'=>'支付方式',
                'field'=>'payment_method',
            ),
            array(
                'name'=>'镜框价格',
                'field'=>'frame_price',
            ),
            array(
                'name'=>'镜框数量',
                'field'=>'frame_num',
            ),
            array(
                'name'=>'镜片数量',
                'field'=>'lens_num',
            ),
            array(
                'name'=>'配饰数量',
                'field'=>'is_box_num',
            ),
            array(
                'name'=>'镜片价格',
                'field'=>'lens_price',
            ),
            array(
                'name'=>'客户电话',
                'field'=>'telephone',
            ),
            array(
                'name'=>'商品SKU',
                'field'=>'sku',
            ),
            array(
                'name'=>'注册时间',
                'field'=>'register_time',
            ),
            array(
                'name'=>'注册邮箱',
                'field'=>'register_email',
            ),
            array(
                'name'=>'工单数',
                'field'=>'work_list_num',
            )
        );
        //从数据库查询需要的数据
        $spreadsheet = new Spreadsheet();
        $spreadsheet->setActiveSheetIndex(0);
        foreach($field_arr as $kkk=>$vvv){
            $table_name = $this->filter_by_value($field_info,'field',$vvv);
            //常规方式：利用setCellValue()填充数据
            $spreadsheet->getActiveSheet()->setCellValue($table_arr[$kkk], $table_name['name']);
        }
        $spreadsheet->setActiveSheetIndex(0)->setTitle('订单数据');
        $spreadsheet->setActiveSheetIndex(0);
        foreach ($field_arr as $k1=>$v1){
            foreach ($data as $key1 => $value1) {
                $spreadsheet->getActiveSheet()->setCellValue($table_arr1[$k1] . ($key1 * 1 + 2), $value1[$field_arr[$k1]]);
            }
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
    function filter_by_value ($array, $index, $value){
        if(is_array($array) && count($array)>0)
        {
            foreach(array_keys($array) as $key){
                $temp[$key] = $array[$key][$index];
                if ($temp[$key] == $value){
                    $newarray = $array[$key];
                }
            }
        }
        return $newarray;
    }
    public function test(){
        set_time_limit(0);
        header ( "Content-type:application/vnd.ms-excel" );
        header ( "Content-Disposition:filename=" . iconv ( "UTF-8", "GB18030", date('Y-m-d',time()) ) . ".csv" );//导出文件名

        // 打开PHP文件句柄，php://output 表示直接输出到浏览器
        $fp = fopen('php://output', 'a');

        $column_name = "订单号";
        $column_name = explode(',',$column_name);
        // 将中文标题转换编码，否则乱码
        foreach ($column_name as $i => $v) {
            $column_name[$i] = iconv('utf-8', 'GB18030', $v);
        }
        // 将标题名称通过fputcsv写到文件句柄
        fputcsv($fp, $column_name);



        $order_platform = input('order_platform');
        $time_str = input('time_str');
        $increment_id = input('increment_id');
        $order_status = input('order_status');
        $customer_type = input('customer_type');
        $store_id = input('store_id');
        if($order_platform == 2){
            $order_model = $this->voogueme;
            $web_model = Db::connect('database.db_voogueme');
        }elseif($order_platform == 3){
            $order_model = $this->nihao;
            $web_model = Db::connect('database.db_nihao');
        }else{
            $order_model = $this->zeelool;
            $web_model = Db::connect('database.db_zeelool');
        }
        $web_model->table('customer_entity')->query("set time_zone='+8:00'");
        $web_model->table('sales_flat_order_payment')->query("set time_zone='+8:00'");
        $web_model->table('sales_flat_order_address')->query("set time_zone='+8:00'");
        $web_model->table('sales_flat_order_item_prescription')->query("set time_zone='+8:00'");
        if($time_str){
            $createat = explode(' ', $time_str);
            $map['o.created_at'] = ['between', [$createat[0].' '.$createat[1], $createat[3].' '.$createat[4]]];
        }else{
            $start = date('Y-m-d', strtotime('-6 day'));
            $end   = date('Y-m-d 23:59:59');
            $map['o.created_at'] = ['between', [$start,$end]];
        }
        if($increment_id){
            $map['o.increment_id'] = $increment_id;
        }
        if($order_status){
            if($order_status == 1){
                //已发货
                $node_where['node_type'] = 7;
            }elseif ($order_status == 2){
                $node_where['node_type'] = ['in',[8,10]];
            }elseif ($order_status == 3){
                $node_where['node_type'] = 30;
            }elseif ($order_status == 4){
                $node_where['node_type'] = 40;
            }elseif ($order_status == 5){
                $node_where['node_type'] = 35;
            }
            $order_ids = Db::name('order_node')->where($node_where)->column('order_id');
            $map['o.entity_id'] = ['in',$order_ids];
        }
        if($customer_type){
            $map['c.group_id'] = $customer_type;
        }
        if($store_id){
            $map['o.store_id'] = $store_id;
        }
        $total_export_count = $order_model->alias('o')
            ->join('customer_entity c','o.customer_id=c.entity_id')
            ->where($map)
            ->count();


        $pre_count = 20;
        $j=0;
        for ($i=0;$i<intval($total_export_count/$pre_count)+1;$i++){
            //切割每份数据
            $export_data = $order_model->alias('o')
                ->join('customer_entity c','o.customer_id=c.entity_id')
                ->where($map)
                ->field('o.entity_id,o.increment_id,o.created_at,o.base_grand_total,o.base_shipping_amount,o.status,o.store_id,o.protect_code,o.shipping_method,o.customer_email,o.customer_id,o.base_discount_amount')
                ->select();
            $export_data = collection($export_data)->toArray();
            //整理数据
            foreach ( $export_data as &$val ) {
                $tmpRow = [];
                $tmpRow[] =$val['increment_id'];

                $rows = array();
                foreach ( $tmpRow as $export_obj){
                    $rows[] = iconv('utf-8', 'GB18030', $export_obj);
                }
                fputcsv($fp, $rows);
            }

            // 将已经写到csv中的数据存储变量销毁，释放内存占用
            unset($export_data);
            ob_flush();
            flush();
        }
    }
}
