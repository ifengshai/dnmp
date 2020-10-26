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
                $map['created_at'] = ['between', [$createat[0], $createat[3].' 23:59:59']];
                unset($filter['time_str']);
                $this->request->get(['filter' => json_encode($filter)]);
            }else{
                if(isset($filter['time_str'])){
                    unset($filter['time_str']);
                    $this->request->get(['filter' => json_encode($filter)]);
                }
                $start = date('Y-m-d', strtotime('-6 day'));
                $end   = date('Y-m-d 23:59:59');
                $map['created_at'] = ['between', [$start,$end]];
            }
            if($filter['order_platform']){
                unset($filter['order_platform']);
                $this->request->get(['filter' => json_encode($filter)]);
            }
            list($where, $sort, $order, $offset, $limit) = $this->buildparams();
            $total = $order_model
                ->where($where)
                ->where($map)
                ->order($sort, $order)
                ->count();
            $list = $order_model
                ->where($where)
                ->where($map)
                ->order($sort, $order)
                ->limit($offset, $limit)
                ->field('entity_id,increment_id,created_at,base_grand_total,base_shipping_amount,status,store_id,protect_code,shipping_method,customer_email,customer_id,base_discount_amount')
                ->select();
            $list = collection($list)->toArray();
            $i = 0;
            foreach ($list as $key=>$value){
                $list[$key]['increment_id'] = $value['increment_id'];
                $list[$key]['created_at'] = $value['created_at'];
                $list[$key]['base_grand_total'] = $value['base_grand_total'];
                $list[$key]['base_shipping_amount'] = $value['base_shipping_amount'];
                $list[$key]['status'] = $value['status'];
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
                $i++;
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
}
