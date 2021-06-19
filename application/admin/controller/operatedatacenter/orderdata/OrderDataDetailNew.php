<?php

namespace app\admin\controller\operatedatacenter\orderdata;

use app\admin\model\platformManage\MagentoPlatform;
use app\common\controller\Backend;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use think\Controller;
use think\Db;
use think\Request;

class OrderDataDetailNew extends Backend
{
    protected $noNeedRight = ['*'];
    public function _initialize()
    {
        parent::_initialize();
        $this->orderitemoption = new \app\admin\model\order\order\NewOrderItemOption();
        $this->zeelool = new \app\admin\model\order\order\Zeelool();
        $this->voogueme = new \app\admin\model\order\order\Voogueme();
        $this->nihao = new \app\admin\model\order\order\Nihao();
        $this->zeeloolde = new \app\admin\model\order\order\ZeeloolDe();
        $this->zeelooljp = new \app\admin\model\order\order\ZeeloolJp();
        $this->zeeloolfr = new \app\admin\model\order\order\ZeeloolFr();
        $this->zeeloolOperate  = new \app\admin\model\operatedatacenter\Zeelool;
        $this->vooguemeOperate  = new \app\admin\model\operatedatacenter\Voogueme;
        $this->nihaoOperate  = new \app\admin\model\operatedatacenter\Nihao;
        $this->zeelooldeOperate  = new \app\admin\model\operatedatacenter\ZeeloolDe();
        $this->zeelooljpOperate  = new \app\admin\model\operatedatacenter\ZeeloolJp();
        $this->zeeloolfrOperate  = new \app\admin\model\operatedatacenter\ZeeloolFr();
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
            $site = $filter['order_platform'] ?? 1;
            if($filter['order_platform'] == 2){
                $order_model = $this->voogueme;
                $web_model = Db::connect('database.db_voogueme');
            }elseif($filter['order_platform'] == 3){
                $order_model = $this->nihao;
                $web_model = Db::connect('database.db_nihao');
            }elseif($filter['order_platform'] == 10){
                $order_model = $this->zeeloolde;
                $web_model = Db::connect('database.db_zeelool_de');
            }elseif($filter['order_platform'] == 11){
                $order_model = $this->zeelooljp;
                $web_model = Db::connect('database.db_zeelool_jp');
            }elseif($filter['order_platform'] == 15){
                $order_model = $this->zeeloolfr;
                $web_model = Db::connect('database.db_zeelool_fr');
            }else {
                $order_model = $this->zeelool;
                $web_model = Db::connect('database.db_zeelool');
            }

            $map = [];
            $node_where = [];
            $mapWesee = [];
            $nodeWhereWesee = [];
            $whereWesee = [];
            if($filter['time_str']){
                $createat = explode(' ', $filter['time_str']);
                $mapWesee['o.created_at'] = $map['o.created_at'] = ['between', [$createat[0].' '.$createat[1], $createat[3].' '.$createat[4]]];
            }else{
                if(isset($filter['time_str'])){
                    unset($filter['time_str']);
                    $this->request->get(['filter' => json_encode($filter)]);
                }
                $start = date('Y-m-d', strtotime('-6 day'));
                $end   = date('Y-m-d 23:59:59');
                $mapWesee['o.created_at'] = $map['o.created_at'] = ['between', [$start,$end]];
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
                $node_where['site'] = $site;
                $order_ids = Db::name('order_node')->where($node_where)->column('order_id');
                $map['o.entity_id'] = ['in',$order_ids];
                $mapWesee['o.id'] = ['in',$order_ids];
            }
            if($filter['customer_type']){
                $map['c.group_id'] = $filter['customer_type'];
                $mapWesee['c.group_id'] = ['in',$order_ids];
            }
            if($filter['store_id']){
                $map['o.store_id'] = $filter['store_id'];
                $weseeStoreId = '';
                if($filter['store_id'] == 4) {
                    $weseeStoreId = 2;
                }elseif($filter['store_id'] == 1) {
                    $weseeStoreId = 1;
                }
                $mapWesee['o.source'] = $weseeStoreId;
            }
            if($filter['increment_id']){
                $map['o.increment_id'] = $filter['increment_id'];
                $mapWesee['o.order_no'] = $filter['increment_id'];
            }
            $has_filter_is_refund = isset($filter['is_refund']) ? 1 : 0;
            if($filter['is_refund'] && $filter['is_refund'] > 0){
                $refund = $filter['is_refund'];
            }else{
                $refund = 0;
            }
            unset($filter['time_str']);
            unset($filter['order_platform']);
            unset($filter['increment_id']);
            unset($filter['order_status']);
            unset($filter['customer_type']);
            unset($filter['store_id']);
            unset($filter['is_refund']);
            $this->request->get(['filter' => json_encode($filter)]);
            [$where, $sort, $order, $offset, $limit] = $this->buildparams();
            if($site == 5) {
                $order_model = Db::connect('database.db_wesee_temp');
                $order_model->table('order')->query("set time_zone='+8:00'");
                $sort = 'o.id';
                $list = $order_model->table('orders')->alias('o')
                    ->join('users c','o.user_id=c.id','left')
                    ->join('orders_addresses d','d.order_id = o.id')
                    ->where($where)
                    ->where($mapWesee)
                    ->where('d.type',1)
                    ->order($sort, $order)
                    ->limit($offset, $limit)
                    ->field('o.id as entity_id,o.order_no as increment_id,o.created_at,o.discount_code_id as coupon_code,o.discount_coupon_id,o.order_type,o.base_original_total_price as base_grand_total,o.base_freight_price as base_shipping_amount,o.status,o.source as store_id,o.freight_type as shipping_method,o.email as customer_email,o.user_id as customer_id,o.base_discounts_price as base_discount_amount,o.payment_time,d.firstname,d.lastname,c.email as register_email,c.created_at as register_time,d.country,d.telephone,o.payment_type as payment_method,c.group_id')
                    ->select();
                $arr = array();
                $i = 0;
                foreach ($list as $key=>$value){
                    $arr[$i]['increment_id'] = $value['increment_id'];
                    $arr[$i]['created_at'] = $value['created_at'];
                    $arr[$i]['payment_time'] = $value['payment_time'];
                    $arr[$i]['base_grand_total'] = round($value['base_grand_total'], 2);
                    $arr[$i]['base_shipping_amount'] = round($value['base_shipping_amount'],2);
                    switch ($value['order_type']){
                        case 1:
                            $arr[$key]['order_type'] = '普通订单';
                            break;
                        case 2:
                            $arr[$key]['order_type']  = '批发';
                            break;
                        case 3:
                            $arr[$key]['order_type']  = '网红';
                            break;
                        case 4:
                            $arr[$key]['order_type']  = '补发';
                            break;
                    }
                    $order_node = Db::name('order_node')->where('order_id',$value['entity_id'])->where('site',$site)->value('node_type');
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
                        $order_shipping_status = $value['status'];
                    }
                    $arr[$i]['status'] = $order_shipping_status;
                    switch ($value['store_id']){
                        case 1:
                            $store_id = 'PC';
                            break;
                        case 2:
                            $store_id = 'M';
                            break;
                    }
                    $arr[$i]['store_id'] = $store_id;
                    $arr[$i]['coupon_code'] = $value['coupon_code'];
                    $arr[$i]['coupon_rule_name'] = $order_model->table('user_coupons')->alias('a')
                    ->join('discount_coupons b','b.id=a.discount_coupon_id')->field('b.*')->value('b.name');
                    $arr[$i]['shipping_method'] = $value['shipping_method'];  //快递类别
                    $value['firstname'] = mb_convert_encoding( $value['firstname'], 'UTF-8', 'UTF-8,GBK,GB2312,BIG5' );
                    $value['lastname'] = mb_convert_encoding( $value['lastname'], 'UTF-8', 'UTF-8,GBK,GB2312,BIG5' );
                    //收货信息
                    $arr[$i]['shipping_name'] = $value['firstname'].''.$value['lastname'];  //收货姓名
                    $arr[$i]['customer_email'] = $value['customer_email'];   //支付邮箱
                    //客户信息

                    switch ($value['group_id']){
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
                    $arr[$i]['customer_type'] = $group;   //客户类型
                    $arr[$i]['discount_rate'] = $value['base_grand_total'] ? round(($value['base_discount_amount'] / $value['base_grand_total'] * (-1)), 2).'%' : 0;  //折扣百分比
                    $arr[$i]['discount_money'] = round($value['base_discount_amount'], 2);  //折扣金额

                    $work_list_where['platform_order'] = $value['increment_id'];
                    $work_list = Db::name('work_order_list')->where($work_list_where)->field('id,is_refund')->select();
                    $work_list = collection($work_list)->toArray();
                    $work_list_num = count($work_list);
                    $work_list_is_refund = array_column($work_list, 'is_refund');
                    if (in_array(1, $work_list_is_refund)) {

                        if($has_filter_is_refund) {
                            if(!$refund) {
                                continue;
                            }
                        }
                        $is_refund = '有';
                    } else {

                        if($has_filter_is_refund) {
                            if($refund) {
                                continue;
                            }
                        }
                        $is_refund = '无';
                    }

                    $arr[$i]['is_refund'] = $is_refund;  //是否退款
                    $arr[$i]['country_id'] = $value['country'];   //收货国家

                    $arr[$i]['payment_method'] = $value['payment_method'];  //支付方式
                    //处方信息
                    $frame_price = $order_model->table('orders_items')->where('order_id',$value['entity_id'])->sum('base_goods_total_price');
                    $arr[$i]['frame_price'] = round($frame_price,2);
                    $arr[$i]['frame_num'] = $order_model->table('orders_items')->where('order_id',$value['entity_id'])->sum('goods_count');
                    $arr[$i]['lens_num'] = $order_model->table('orders_items')->where('order_id',$value['entity_id'])->where('lens_name','neq','')->count();
                    $arr[$i]['is_box_num'] = 0;

                    $lens_price = $order_model->table('orders_items')->where('order_id',$value['entity_id'])->sum('base_lens_total_price');
                    $arr[$i]['lens_price'] = round($lens_price,2);

                    $arr[$i]['telephone'] = $value['telephone'];
                    $skus = $order_model->table('orders_items')->where('order_id',$value['entity_id'])->column('goods_sku');
                    $skus = collection($skus)->toArray();
                    $arr[$i]['sku'] = implode(',',$skus);
                    $arr[$i]['register_time'] = $value['register_time'];
                    $arr[$i]['register_email'] = $value['register_email'];
                    $arr[$i]['work_list_num'] = $work_list_num;
                    $i++;
                }
            }else{
                $web_model->table('customer_entity')->query("set time_zone='+8:00'");
                $web_model->table('sales_flat_order_payment')->query("set time_zone='+8:00'");
                $web_model->table('sales_flat_order_address')->query("set time_zone='+8:00'");
                $web_model->table('sales_flat_order_item_prescription')->query("set time_zone='+8:00'");

                $sort = 'o.entity_id';
                $list = $order_model->alias('o')
                    ->join('customer_entity c', 'o.customer_id=c.entity_id', 'left')
                    ->where($where)
                    ->where($map)
                    ->order($sort, $order)
                    ->limit($offset, $limit)
                    ->field('o.entity_id,o.increment_id,o.created_at,o.coupon_rule_name,o.order_type,o.base_grand_total,o.base_shipping_amount,o.status,o.store_id,o.coupon_code,o.shipping_method,o.customer_email,o.customer_id,o.base_discount_amount,o.payment_time')
                    ->select();
                $count = $order_model->alias('o')
                    ->join('customer_entity c', 'o.customer_id=c.entity_id', 'left')
                    ->where($where)
                    ->where($map)
                    ->order($sort, $order)
                    ->limit($offset, $limit)
                    ->field('o.entity_id,o.increment_id,o.created_at,o.coupon_rule_name,o.order_type,o.base_grand_total,o.base_shipping_amount,o.status,o.store_id,o.coupon_code,o.shipping_method,o.customer_email,o.customer_id,o.base_discount_amount,o.payment_time')
                    ->count();
                $list = collection($list)->toArray();
                $arr = array();
                $i = 0;
                foreach ($list as $key=>$value){
                    $arr[$i]['increment_id'] = $value['increment_id'];
                    $arr[$i]['created_at'] = $value['created_at'];
                    $arr[$i]['payment_time'] = $value['payment_time'];
                    $arr[$i]['base_grand_total'] = round($value['base_grand_total'], 2);
                    $arr[$i]['base_shipping_amount'] = round($value['base_shipping_amount'],2);
                    switch ($value['order_type']){
                        case 1:
                            $list[$key]['order_type'] = '普通订单';
                            break;
                        case 2:
                            $list[$key]['order_type']  = '批发';
                            break;
                        case 3:
                            $list[$key]['order_type']  = '网红';
                            break;
                        case 4:
                            $list[$key]['order_type']  = '补发';
                            break;
                    }
                    $order_node = Db::name('order_node')->where('order_id',$value['entity_id'])->where('site',$site)->value('node_type');
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
                        $order_shipping_status = $value['status'];
                    }
                    $arr[$i]['status'] = $order_shipping_status;
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
                    $arr[$i]['store_id'] = $store_id;
                    $arr[$i]['coupon_code'] = $value['coupon_code'];
                    $arr[$i]['coupon_rule_name'] = $value['coupon_rule_name'];
                    $arr[$i]['shipping_method'] = $value['shipping_method'];  //快递类别
                    //收货信息
                    $shipping_where['address_type'] = 'shipping';
                    $shipping_where['parent_id'] = $value['entity_id'];
                    $shipping = $web_model->table('sales_flat_order_address')->where($shipping_where)->field('firstname,lastname,telephone,country_id')->find();
                    $arr[$i]['shipping_name'] = $shipping['firstname'].''.$shipping['lastname'];  //收货姓名
                    $arr[$i]['customer_email'] = $value['customer_email'];   //支付邮箱
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
                    } else {
                        $group = '游客';
                        $register_time = '';
                        $register_email = '';
                    }
                    $arr[$i]['customer_type'] = $group;   //客户类型
                    $arr[$i]['discount_rate'] = $value['base_grand_total'] ? round(($value['base_discount_amount'] / $value['base_grand_total'] * (-1)), 2).'%' : 0;  //折扣百分比
                    $arr[$i]['discount_money'] = round($value['base_discount_amount'], 2);  //折扣金额
                    $work_list_where['platform_order'] = $value['increment_id'];
                    $work_list = Db::name('work_order_list')->where($work_list_where)->field('id,is_refund')->select();
                    $work_list = collection($work_list)->toArray();
                    $work_list_num = count($work_list);
                    $work_list_is_refund = array_column($work_list, 'is_refund');
                    if (in_array(1, $work_list_is_refund)) {

                        if($has_filter_is_refund) {
                            if(!$refund) {
                                continue;
                            }
                        }
                        $is_refund = '有';
                    } else {

                        if($has_filter_is_refund) {
                            if($refund) {
                                continue;
                            }
                        }
                        $is_refund = '无';
                    }

                    $arr[$i]['is_refund'] = $is_refund;  //是否退款
                    $arr[$i]['country_id'] = $shipping['country_id'];   //收货国家
                    //支付信息
                    $payment_where['parent_id'] = $value['entity_id'];
                    $payment = $web_model->table('sales_flat_order_payment')->where($payment_where)->value('method');
                    $arr[$i]['payment_method'] = $payment == 'oceanpayment_creditcard' ? '钱海' : 'Paypal';  //支付方式
                    //处方信息
                    $prescription_where['magento_order_id'] = $value['entity_id'];
                    $prescription_where['site'] = $site;
                    $frame_price = $this->orderitemoption->where($prescription_where)->sum('frame_price');
                    $arr[$i]['frame_price'] = round($frame_price,2);
                    $arr[$i]['frame_num'] = $this->orderitemoption->where($prescription_where)->sum('qty');
                    $arr[$i]['lens_num'] = $this->orderitemoption->where($prescription_where)->where('lens_number','neq','')->sum('qty');
                    $arr[$i]['is_box_num'] = $this->orderitemoption->where($prescription_where)->where('goods_type',6)->sum('qty');
                    $lens_price = $this->orderitemoption->where($prescription_where)->sum('index_price');
                    $arr[$i]['lens_price'] = round($lens_price,2);
                    $arr[$i]['telephone'] = $shipping['telephone'];
                    $skus = $this->orderitemoption->where($prescription_where)->column('sku');
                    $skus = collection($skus)->toArray();
                    $arr[$i]['sku'] = implode(',',$skus);
                    $arr[$i]['register_time'] = $register_time;
                    $arr[$i]['register_email'] = $register_email;
                    $arr[$i]['work_list_num'] = $work_list_num;
                    $i++;
                }
            }

            $result = array("total" => $count, "rows" => $arr);
            return json($result);
        }
        //查询对应平台权限
        $magentoplatformarr = $this->magentoplatform->getAuthSite();
        foreach ($magentoplatformarr as $key=>$val){
            if(!in_array($val['name'],['zeelool','voogueme','meeloog','zeelool_de','zeelool_jp','wesee','zeelool_fr'])){
                unset($magentoplatformarr[$key]);
            }
        }
        $this->view->assign('magentoplatformarr',$magentoplatformarr);
        return $this->view->fetch();
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
    public function export(){
        set_time_limit(0);
        header ( "Content-type:application/vnd.ms-excel" );
        header ( "Content-Disposition:filename=" . iconv ( "UTF-8", "GB18030", date('Y-m-d-His',time()) ) . ".csv" );//导出文件名

        // 打开PHP文件句柄，php://output 表示直接输出到浏览器
        $fp = fopen('php://output', 'a');
        $order_platform = input('order_platform');
        $time_str = input('time_str');
        $increment_id = input('increment_id');
        $order_status = input('order_status');
        $customer_type = input('customer_type');
        $store_id = input('store_id');
        $field = input('field');
        $field_arr = explode(',',$field);
        $field_info = array(
            array(
                'name'  => '订单编号',
                'field' => 'increment_id',
            ),
            array(
                'name'  => '创建时间',
                'field' => 'created_at',
            ),
            array(
                'name'  => '支付时间',
                'field' => 'payment_time',
            ),
            array(
                'name'  => '订单金额',
                'field' => 'base_grand_total',
            ),
            array(
                'name'  => '邮费',
                'field' => 'base_shipping_amount',
            ),
            array(
                'name' => '订单状态',
                'field'=>'status',
            ),
            array(
                'name'=>'设备类型',
                'field'=>'store_id',
            ),
            array(
                'name'=>'使用的code码',
                'field'=>'coupon_code',
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
            ),
            array(
                'name'=>'订单类型',
                'field'=>'order_type',
            )
        ,array(
                'name'=>'优惠券名称',
                'field'=>'coupon_rule_name',
            )
        );
        $column_name = [];
        // 将中文标题转换编码，否则乱码
        foreach ($field_arr as $i => $v) {
            $title_name = $this->filter_by_value($field_info,'field',$v);
            $field_arr[$i] = iconv('utf-8', 'GB18030', $title_name['name']);
            $column_name[$i] = $v;
        }
        // 将标题名称通过fputcsv写到文件句柄
        fputcsv($fp, $field_arr);
        $site = $order_platform;
        if($order_platform == 2){
            $order_model = $this->voogueme;
            $web_model = Db::connect('database.db_voogueme');
        }elseif($order_platform == 3){
            $order_model = $this->nihao;
            $web_model = Db::connect('database.db_nihao');
        }elseif($order_platform == 10){
            $order_model = $this->zeeloolde;
            $web_model = Db::connect('database.db_zeelool_de');
        }elseif($order_platform == 11){
            $order_model = $this->zeelooljp;
            $web_model = Db::connect('database.db_zeelool_jp');
        }elseif($order_platform == 15){
            $order_model = $this->zeeloolfr;
            $web_model = Db::connect('database.db_zeelool_fr');
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
            ->join('customer_entity c','o.customer_id=c.entity_id','left')
            ->where($map)
            ->count();
        $pre_count = 5000;
        for ($i=0;$i<intval($total_export_count/$pre_count)+1;$i++){
            $start = $i*$pre_count;
            //切割每份数据
            $list = $order_model->alias('o')
                ->join('customer_entity c', 'o.customer_id=c.entity_id', 'left')
                ->where($map)
                ->field('o.entity_id,o.increment_id,o.created_at,o.base_grand_total,o.coupon_rule_name,o.order_type,o.base_shipping_amount,o.status,o.store_id,o.coupon_code,o.shipping_method,o.customer_email,o.customer_id,o.base_discount_amount,o.payment_time')
                ->limit($start,$pre_count)
                ->select();
            $list = collection($list)->toArray();
            //整理数据
            foreach ( $list as &$val ) {
                $tmpRow = [];
                if (in_array('increment_id', $column_name)) {
                    $index = array_keys($column_name, 'increment_id');
                    $tmpRow[$index[0]] = $val['increment_id'];
                }
                if (in_array('created_at', $column_name)) {
                    $index = array_keys($column_name, 'created_at');
                    $tmpRow[$index[0]] = $val['created_at'];
                }
                if (in_array('payment_time', $column_name)) {
                    $index = array_keys($column_name, 'payment_time');
                    $tmpRow[$index[0]] = $val['payment_time'];
                }
                if (in_array('base_grand_total', $column_name)) {
                    $index = array_keys($column_name, 'base_grand_total');
                    $tmpRow[$index[0]] = round($val['base_grand_total'], 2);
                }
                if (in_array('base_shipping_amount', $column_name)) {
                    $index = array_keys($column_name, 'base_shipping_amount');
                    $tmpRow[$index[0]] = round($val['base_shipping_amount'], 2);
                }
                if (in_array('status', $column_name)) {
                    $order_node = Db::name('order_node')->where('order_id', $val['entity_id'])->value('node_type');
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
                        $order_shipping_status = $val['status'];
                    }
                    $index = array_keys($column_name,'status');
                    $tmpRow[$index[0]] =$order_shipping_status;
                }
                if(in_array('order_type',$column_name)) {
                    switch ($val['order_type']) {
                        case 1:
                            $order_type = '普通订单';
                            break;
                        case 2:
                            $order_type = '批发';
                            break;
                        case 3:
                            $order_type = '网红';
                            break;
                        case 4:
                            $order_type = '补发';
                            break;
                    }
                    $index = array_keys($column_name,'order_type');
                    $tmpRow[$index[0]] =$order_type;
                }
                if(in_array('coupon_rule_name',$column_name)){
                    $index = array_keys($column_name,'coupon_rule_name');
                    $tmpRow[$index[0]] =$val['coupon_rule_name'];
                }
                if(in_array('store_id',$column_name)){
                    switch ($val['store_id']){
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
                    $index = array_keys($column_name,'store_id');
                    $tmpRow[$index[0]] =$store_id;
                }
                if(in_array('coupon_code',$column_name)){
                    $index = array_keys($column_name,'coupon_code');
                    $tmpRow[$index[0]] =$val['coupon_code'];
                }
                if(in_array('shipping_method',$column_name)){
                    $index = array_keys($column_name,'shipping_method');
                    $tmpRow[$index[0]] =$val['shipping_method'];
                }
                if(in_array('address_type',$column_name)){
                    $index = array_keys($column_name,'address_type');
                    $tmpRow[$index[0]] ='shipping';
                }
                if(in_array('parent_id',$column_name)){
                    $index = array_keys($column_name,'parent_id');
                    $tmpRow[$index[0]] =$val['entity_id'];
                }
                if(in_array('shipping_name',$column_name) || in_array('country_id',$column_name) || in_array('telephone',$column_name)){
                    //收货信息
                    $shipping_where['address_type'] = 'shipping';
                    $shipping_where['parent_id'] = $val['entity_id'];
                    $shipping = $web_model->table('sales_flat_order_address')->where($shipping_where)->field('firstname,lastname,telephone,country_id')->find();
                    $index1 = array_keys($column_name,'shipping_name');
                    if($index1){
                        $tmpRow[$index1[0]] =$shipping['firstname'].''.$shipping['lastname'];
                    }
                    $index2 = array_keys($column_name,'country_id');
                    if($index2){
                        $tmpRow[$index2[0]] =$shipping['country_id']; //收货国家
                    }
                    $index3 = array_keys($column_name,'telephone');
                    if($index3){
                        $tmpRow[$index3[0]] =$shipping['telephone'];
                    }
                }
                if(in_array('customer_email',$column_name)){
                    $index = array_keys($column_name,'customer_email');
                    $tmpRow[$index[0]] =$val['customer_email'];
                }
                if(in_array('customer_type',$column_name) || in_array('register_time',$column_name) || in_array('register_email',$column_name)){
                    //客户信息
                    if($val['customer_id']){
                        $customer_where['entity_id'] = $val['customer_id'];
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
                    $index1 = array_keys($column_name,'customer_type');
                    if($index1){
                        $tmpRow[$index1[0]] =$group;//客户类型
                    }
                    $index2 = array_keys($column_name,'register_time');
                    if($index2){
                        $tmpRow[$index2[0]] =$register_time;
                    }
                    $index3 = array_keys($column_name,'register_email');
                    if($index3){
                        $tmpRow[$index3[0]] =$register_email;
                    }
                }
                if(in_array('discount_rate',$column_name)) {
                    $index = array_keys($column_name, 'discount_rate');
                    $tmpRow[$index[0]] = $val['base_grand_total'] ? round(($val['base_discount_amount'] / $val['base_grand_total'] * (-1)), 2).'%' : 0;//折扣百分比
                }
                if(in_array('discount_money',$column_name)){
                    $index = array_keys($column_name,'discount_money');
                    $tmpRow[$index[0]] =round($val['base_discount_amount'],2);  //折扣金额
                }
                if(in_array('is_refund',$column_name) || in_array('work_list_num',$column_name)){
                    $work_list_where['platform_order'] = $val['increment_id'];
                    $work_list = Db::name('work_order_list')->where($work_list_where)->field('id,is_refund')->select();
                    $work_list = collection($work_list)->toArray();
                    $work_list_num = count($work_list);
                    $work_list_is_refund = array_column($work_list,'is_refund');
                    if(in_array(1,$work_list_is_refund)){
                        $is_refund = '有';
                    }else{
                        $is_refund = '无';
                    }
                    $index1 = array_keys($column_name,'is_refund');
                    if($index1){
                        $tmpRow[$index1[0]] =$is_refund;//是否退款
                    }
                    $index2 = array_keys($column_name,'work_list_num');
                    if($index2){
                        $tmpRow[$index2[0]] =$work_list_num;
                    }
                }
                if(in_array('payment_method',$column_name)){
                    //支付信息
                    $payment_where['parent_id'] = $val['entity_id'];
                    $payment = $web_model->table('sales_flat_order_payment')->where($payment_where)->value('method');
                    $index = array_keys($column_name,'payment_method');
                    $tmpRow[$index[0]] = $payment == 'oceanpayment_creditcard' ? '钱海' : 'Paypal';  //支付方式
                }
                if(in_array('frame_price',$column_name) || in_array('frame_num',$column_name) || in_array('lens_price',$column_name)){
                    //处方信息
                    $prescription_where['magento_order_id'] = $val['entity_id'];
                    $prescription_where['site'] = $site;
                    $frame_info = $this->orderitemoption->where($prescription_where)->field('sum(frame_price) frame_amount,sum(qty) count,sum(index_price) lens_amount,sku')->select();
                    $frame_info = collection($frame_info)->toArray();
                    $index1 = array_keys($column_name,'frame_price');
                    if($index1){
                        $tmpRow[$index1[0]] =round($frame_info[0]['frame_amount'],2);
                    }
                    $index2 = array_keys($column_name,'frame_num');
                    if($index2){
                        $tmpRow[$index2[0]] =$frame_info[0]['count'];
                    }
                    $index3 = array_keys($column_name,'lens_price');
                    if($index3){
                        $tmpRow[$index3[0]] =round($frame_info[0]['lens_amount'],2);
                    }
                }
                if(in_array('sku',$column_name)){
                    $prescription_where['order_id'] = $val['entity_id'];
                    $skus = $this->orderitemoption->where($prescription_where)->column('sku');
                    $index = array_keys($column_name,'sku');
                    $tmpRow[$index[0]] =implode('|',$skus);

                }
                if(in_array('lens_num',$column_name)){
                    $prescription_where['order_id'] = $val['entity_id'];
                    $val['lens_num'] = $this->orderitemoption->where($prescription_where)->where('lens_number','neq','')->sum('qty');
                    $index = array_keys($column_name,'lens_num');
                    $tmpRow[$index[0]] =$val['lens_num'];
                }
                if(in_array('is_box_num',$column_name)){
                    $prescription_where['order_id'] = $val['entity_id'];
                    $index = array_keys($column_name,'is_box_num');
                    $tmpRow[$index[0]] =$this->orderitemoption->where($prescription_where)->where('goods_type',6)->sum('qty');
                }
                ksort($tmpRow);
                $rows = array();
                foreach ( $tmpRow as $export_obj){
                    $rows[] = iconv('utf-8', 'GB18030', $export_obj);
                }
                fputcsv($fp, $rows);
            }

            // 将已经写到csv中的数据存储变量销毁，释放内存占用
            unset($list);
            ob_flush();
            flush();
        }
        fclose($fp);
    }
}
