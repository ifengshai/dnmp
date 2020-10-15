<?php

namespace app\admin\controller\operatedatacenter\orderdata;

use app\common\controller\Backend;
use think\Controller;
use think\Db;
use think\Request;

class OrderDataView extends Backend
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
     * 订单数据概况
     *
     * @return \think\Response
     */
    public function index()
    {
        //订单数
        $order_num = $this->zeeloolOperate->getOrderNum();
        //客单价
        $order_unit_price = $this->zeeloolOperate->getOrderUnitPrice();
        //销售额
        $sales_total_money = $this->zeeloolOperate->getSalesTotalMoney();
        //邮费
        $shipping_total_money = $this->zeeloolOperate->getShippingTotalMoney();
        //补发单订单数
        $replacement_order_num = $this->zeeloolOperate->getReplacementOrderNum();
        //补发单销售额
        $replacement_order_total = $this->zeeloolOperate->getReplacementOrderTotal();
        //网红单订单数
        $online_celebrity_order_num = $this->zeeloolOperate->getOnlineCelebrityOrderNum();
        //网红单销售额
        $online_celebrity_order_total = $this->zeeloolOperate->getOnlineCelebrityOrderTotal();
        $zeeloolSalesNumList = array(['US', 250], ['AU', 500], ['AS', 750], ['UA', 1000]);
        $this->view->assign(compact('order_num', 'order_unit_price', 'sales_total_money', 'shipping_total_money', 'replacement_order_num', 'replacement_order_total', 'online_celebrity_order_num', 'online_celebrity_order_total', 'zeeloolSalesNumList'));
        return $this->view->fetch();
    }
    /*
     * ajax获取订单数据概况
     * */
    public function ajax_order_data_view()
    {
        if ($this->request->isAjax()) {
            $params = $this->request->param();
            $order_platform = $params['order_platform'] ? $params['order_platform'] : 1;
            $now_day = date('Y-m-d') . ' ' . '00:00:00' . ' - ' . date('Y-m-d');
            $time_str = $params['time_str'] ? $params['time_str'] : $now_day;
            switch ($order_platform) {
                case 1:
                    $model = $this->zeeloolOperate;
                    break;
                case 2:
                    $model = $this->vooguemeOperate;
                    break;
                case 3:
                    $model = $this->nihaoOperate;
                    break;
            }

            $order_num = $model->getOrderNum($time_str, 1);  //订单数
            $order_unit_price = $model->getOrderUnitPrice($time_str, 1); //客单价
            $sales_total_money = $model->getSalesTotalMoney($time_str, 1); //销售额
            $shipping_total_money = $model->getShippingTotalMoney($time_str, 1);  //邮费
            $replacement_order_num = $model->getReplacementOrderNum($time_str, 1);  //补发单订单数
            $replacement_order_total = $model->getReplacementOrderTotal($time_str, 1); //补发单销售额
            $online_celebrity_order_num = $model->getOnlineCelebrityOrderNum($time_str, 1); //网红单订单数
            $online_celebrity_order_total = $model->getOnlineCelebrityOrderTotal($time_str, 1);  //网红单销售额
            $data = compact('order_num', 'order_unit_price', 'sales_total_money', 'shipping_total_money', 'replacement_order_num', 'replacement_order_total', 'online_celebrity_order_num', 'online_celebrity_order_total');
            $this->success('', '', $data);
        }
    }
    /**
     * ajax获取订单数据概况中销售额/订单量的折线图数据
     *
     * @Description
     * @author mjj
     * @since 2020/07/24 13:58:28 
     * @return void
     */
    public function order_data_view_line()
    {
        if ($this->request->isAjax()) {
            $params = $this->request->param();
            $order_platform = $params['order_platform'];
            $time_str = $params['time_str'];
            //0:销售额  1：订单量
            $type = $params['type'] ? $params['type'] : 0;
            if ($order_platform == 1) {
                $model = $this->zeeloolOperate;
            } elseif ($order_platform == 2) {
                $model = $this->vooguemeOperate;
            } elseif ($order_platform == 3) {
                $model = $this->nihaoOperate;
            }
            if ($time_str) {
                $createat = explode(' ', $time_str);
                if ($type == 1) {
                    $first_sales_total = $model->getOrderNum($createat[0]);
                    $date_arr = array(
                        $createat[0] => $first_sales_total['order_num']
                    );
                    if ($createat[0] != $createat[3]) {
                        for ($i = 0; $i <= 100; $i++) {
                            $m = $i + 1;
                            $deal_date = date_create($createat[0]);
                            date_add($deal_date, date_interval_create_from_date_string("$m days"));
                            $next_day = date_format($deal_date, "Y-m-d");
                            $next_sales_total = $model->getOrderNum($next_day);
                            $date_arr[$next_day] = $next_sales_total['order_num'];
                            if ($next_day == $createat[3]) {
                                break;
                            }
                        }
                    }
                } else {
                    $first_sales_total = $model->getSalesTotalMoney($createat[0]);
                    $date_arr = array(
                        $createat[0] => $first_sales_total['sales_total_money']
                    );
                    if ($createat[0] != $createat[3]) {
                        for ($i = 0; $i <= 100; $i++) {
                            $m = $i + 1;
                            $deal_date = date_create($createat[0]);
                            date_add($deal_date, date_interval_create_from_date_string("$m days"));
                            $next_day = date_format($deal_date, "Y-m-d");
                            $next_sales_total = $model->getSalesTotalMoney($next_day);
                            $date_arr[$next_day] = $next_sales_total['sales_total_money'];
                            if ($next_day == $createat[3]) {
                                break;
                            }
                        }
                    }
                }
            } else {
                $now_day = date('Y-m-d');
                if ($type == 1) {
                    //今天的订单数
                    $today_order_num = $model->getOrderNum();
                    $date_arr[$now_day] = $today_order_num['order_num'];
                } else {
                    //今天的销售额
                    $today_sales_total_money = $model->getSalesTotalMoney();
                    $date_arr[$now_day] = $today_sales_total_money['sales_total_money'];
                }
            }
            if ($type == 1) {
                $name = '订单数';
            } else {
                $name = '销售额';
            }
            $json['xcolumnData'] = array_keys($date_arr);
            $json['column'] = [$name];
            $json['columnData'] = [
                [
                    'name' => $name,
                    'type' => 'line',
                    'smooth' => true,
                    'data' => array_values($date_arr)
                ],

            ];
            return json(['code' => 1, 'data' => $json]);
        }
    }
    /**
     * ajax获取订单数据概况中国家占比图数据
     *
     * @Description
     * @author mjj
     * @since 2020/07/24 13:58:28 
     * @return void
     */
    public function order_data_view_country_rate()
    {
        if ($this->request->isAjax()) {
            $params = $this->request->param();
            $order_platform = $params['order_platform'];
            if ($order_platform == 1) {
                $model = $this->zeelool;
            } elseif ($order_platform == 2) {
                $model = $this->voogueme;
            } elseif ($order_platform == 3) {
                $model = $this->nihao;
            }
            $time_str = $params['time_str'];
            $createat = explode(' ', $time_str);
            $order_where['o.created_at'] = ['between', [$createat[0], $createat[3]]];
            $order_where['o.status'] = ['in', ['free_processing', 'processing', 'complete', 'paypal_reversed', 'payment_review', 'paypal_canceled_reversal']];
            $order_where['oa.address_type'] = 'shipping';
            //获取所有的订单的国家
            $country_arr = $model->alias('o')->join('sales_flat_order_address oa','o.entity_id=oa.parent_id')->where($order_where)->group('oa.country_id')->field('oa.country_id,count(oa.country_id) count')->select();
            $arr = array();
            foreach ($country_arr as $key=>$value){
                $arr[$key][] = $value['count'];
                $arr[$key][] = $value['count'];
                $arr[$key][] = $value['country_id'];
                $lens = strlen((string)$value['count']);
                if($lens <= 5){
                    $xishu = str_pad(1,5-$lens,"0",STR_PAD_RIGHT);
                }else{
                    $xishu = 1;
                }
                $arr[$key][] = $value['count']*$xishu/200;
            }
            $data['column'] = ['国家'];
            $data['columnData'] = [
                [
                    'name' => '国家',
                    'data' =>  $arr
                ]
            ];
            return json(['code' => 1, 'data' => $data]);
        }
    }
    /*
     * ajax获取中位数/客单价/标准差柱状图信息
     * */
    public function ajax_histogram(){
        if ($this->request->isAjax()) {
            $params = $this->request->param();
            $order_platform = $params['order_platform'];
            if ($order_platform == 1) {
                $model_operate = $this->zeeloolOperate;
            } elseif ($order_platform == 2) {
                $model_operate = $this->vooguemeOperate;
            } elseif ($order_platform == 3) {
                $model_operate = $this->nihaoOperate;
            }
            //查询时间段内每天的客单价,中位数，标准差
            $time_str = $params['time_str'];
            $createat = explode(' ', $time_str);
            $where['day_date'] = ['between', [$createat[0], $createat[3]]];
            $where['site'] = $order_platform;
            $order_info = $model_operate->where($where)->field('order_unit_price,day_date,order_total_midnum,order_total_standard')->select();
            foreach ($order_info as $key=>$value){
            }
            $json['xColumnName'] = $order_info['day_date'] ? $order_info['day_date'] :[];
            $json['columnData'] = [
                [
                    'type' => 'bar',
                    'barWidth' => '20%',
                    'data' => $order_info['order_unit_price'] ? $order_info['order_unit_price']:[],
                    'name' => '客单价'
                ],
                [
                    'type' => 'bar',
                    'barWidth' => '20%',
                    'data' => $order_info['order_total_midnum'] ? $order_info['order_total_midnum']:[],
                    'name' => '中位数'
                ],
                [
                    'type' => 'line',
                    'yAxisIndex' => 1,
                    'data' => $order_info['order_total_standard'] ? $order_info['order_total_standard']:[],
                    'name' => '标准差'
                ]

            ];
            return json(['code' => 1, 'data'=>$json]);
        }
    }
}
