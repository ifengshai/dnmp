<?php

namespace app\admin\controller\operatedatacenter\orderdata;

use app\admin\model\platformManage\MagentoPlatform;
use app\common\controller\Backend;
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

            if($filter['order_platform'] == 2){
                $order_model = $this->voogueme;
                $web_model = Db::connect('database.db_voogueme');
            }elseif($filter['order_platform'] == 3){
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
            if($filter['time_str']){
                $createat = explode(' ', $filter['time_str']);
                $where['created_at'] = ['between', [$createat[0], $createat[3].' 23:59:59']];
            }
            list($where, $sort, $order, $offset, $limit) = $this->buildparams();
            $total = $order_model
                ->where($where)
                ->order($sort, $order)
                ->count();
            $list = $order_model
                ->where($where)
                ->order($sort, $order)
                ->limit($offset, $limit)
                ->field('entity_id,increment_id,created_at,base_grand_total,base_shipping_amount,status,store_id,protect_code,shipping_method,customer_email,customer_id,base_discount_amount')
                ->select();
            $list = collection($list)->toArray();
            $data = array();
            $i = 0;
            foreach ($list as $key=>$value){
                $data[$key]['increment_id'] = $value['increment_id'];
                $data[$key]['created_at'] = $value['created_at'];
                $data[$key]['base_grand_total'] = $value['base_grand_total'];
                $data[$key]['base_shipping_amount'] = $value['base_shipping_amount'];
                $data[$key]['status'] = $value['status'];
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
                $data[$key]['store_id'] = $store_id;
                $data[$key]['protect_code'] = $value['protect_code'];
                $data[$key]['shipping_method'] = $value['shipping_method'];  //快递类别
                //收货信息
                $shipping_where['address_type'] = 'shipping';
                $shipping_where['parent_id'] = $value['entity_id'];
                $shipping = $web_model->table('sales_flat_order_address')->where($shipping_where)->field('firstname,lastname,telephone,country_id')->find();
                $data[$key]['shipping_name'] = $shipping['firstname'].''.$shipping['lastname'];  //收货姓名
                $data[$key]['customer_email'] = $value['customer_email'];   //支付邮箱
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
                $data[$key]['customer_type'] = $group;   //客户类型
                $data[$key]['discount_rate'] = $value['base_grand_total'] ? round(($value['base_discount_amount']/$value['base_grand_total']),2).'%' : 0;  //折扣百分比
                $data[$key]['discount_money'] = round($value['base_discount_amount'],2);  //折扣金额
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
                $data[$key]['is_refund'] = $is_refund;  //是否退款
                $data[$key]['country_id'] = $shipping['country_id'];   //收货国家
                //支付信息
                $payment_where['parent_id'] = $value['entity_id'];
                $payment = $web_model->table('sales_flat_order_payment')->where($payment_where)->value('method');
                $data[$key]['payment_method'] =  $payment == 'oceanpayment_creditcard' ? '钱海' : 'Paypal';  //支付方式
                //处方信息
                $prescription_where['order_id'] = $value['entity_id'];
                $frame_price = $web_model->table('sales_flat_order_item_prescription')->where($prescription_where)->sum('frame_price');
                $data[$key]['frame_price'] = round($frame_price,2);
                $data[$key]['frame_num'] = $web_model->table('sales_flat_order_item_prescription')->where($prescription_where)->count();
                $data[$key]['lens_num'] = $web_model->table('sales_flat_order_item_prescription')->where($prescription_where)->where('index_id','neq','')->count();
                $data[$key]['is_box_num'] = $web_model->table('sales_flat_order_item_prescription')->where($prescription_where)->where('goods_type',6)->count();
                $lens_price = $web_model->table('sales_flat_order_item_prescription')->where($prescription_where)->sum('index_price');
                $data[$key]['lens_price'] = round($lens_price,2);
                $data[$key]['telephone'] = $shipping['telephone'];
                $skus = $web_model->table('sales_flat_order_item_prescription')->where($prescription_where)->column('sku');
                $skus = collection($skus)->toArray();
                $data[$key]['sku'] = implode(',',$skus);
                $data[$key]['register_time'] = $register_time;
                $data[$key]['register_email'] = $register_email;
                $data[$key]['work_list_num'] = $work_list_num;
                $i++;
            }
            $result = array("total" => $total, "rows" => $data);

            return json($result);
        }
        return $this->view->fetch();
    }
}
