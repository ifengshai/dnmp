<?php

namespace app\admin\controller\operatedatacenter\dataview;

use app\common\controller\Backend;
use think\Cache;
use think\Controller;
use think\Db;
use think\Request;

class TimeData extends Backend
{
    public function _initialize()
    {
        parent::_initialize();
        $this->zeeloolOperate  = new \app\admin\model\operatedatacenter\Zeelool;
        $this->vooguemeOperate  = new \app\admin\model\operatedatacenter\Voogueme;
        $this->nihaoOperate  = new \app\admin\model\operatedatacenter\Nihao;
        $this->magentoplatform = new \app\admin\model\platformmanage\MagentoPlatform();
    }
    /**
     * 分时数据
     *
     * @return \think\Response
     */
    public function index()
    {
        $info = $this->get_data(1,'');
        $data = $info['finalList'];
        $total = $info['total_array'];
        $count = count($info['finalList'])+1;
        //查询对应平台权限
        $magentoplatformarr = $this->magentoplatform->getAuthSite();
        foreach ($magentoplatformarr as $key=>$val){
            if(!in_array($val['name'],['zeelool','voogueme','nihao'])){
                unset($magentoplatformarr[$key]);
            }
        }
        $this->view->assign(compact('data','total','count','web_site','time_str','magentoplatformarr'));
        return $this->view->fetch();
    }
    public function ajax_get_data(){
        if ($this->request->isAjax()) {
            $params = $this->request->param();
            $time_str = $params['time_str'];
            $order_platform = $params['order_platform'] ? $params['order_platform'] : 1;
            $info = $this->get_data($order_platform,$time_str);
            $data = $info['finalList'];
            $total = $info['total_array'];
            $count = count($info['finalList'])+1;
            $str = '';
            foreach ($data as $key=>$val){
                $num = $key+1;
                $str .= '<tr><td>'.$num.'</td><td>'.$val['hour_created'].'</td><td>'.$val['order_counter'].'</td><td>'.$val['orderitem_counter'].'</td><td>'.$val['hour_grand_total'].'</td><td>'.$val['grand_total_order_conversion'].'</td><td>'.$val['sessions'].'</td><td>'.$val['quote_sessions_conversion'].'</td><td>'.$val['order_sessions_conversion'].'</td><td>'.$val['quote_counter'].'</td><td>'.$val['order_quote_conversion'].'</td></tr>';
            }
            $str .= '<tr><td>'.$count.'</td><td>合计</td><td>'.$total['order_counter'].'</td><td>'.$total['orderitem_counter'].'</td><td>'.$total['hour_grand_total'].'</td><td>'.$total['grand_total_order_conversion'].'</td><td>'.$total['sessions'].'</td><td>'.$total['quote_sessions_conversion'].'</td><td>'.$total['order_sessions_conversion'].'</td><td>'.$total['quote_counter'].'</td><td>'.$total['order_quote_conversion'].'</td></tr>';
            $data = compact('time_str', 'order_platform','str');
            $this->success('', '', $data);
        }
    }
    //获取销售量
    public function get_data($site,$time_str){
        $now_date = date('Y-m-d');
        if(!$time_str){
            $start = $end = $time_str = $now_date;
            $time_flag = 'today';
            $time = time().time();
        }else{
            $createat = explode(' ', $time_str);
            $start = $createat[0];
            $end = $createat[3];
            $time_flag = '';
            $time = strtotime($start).strtolower($end);
            if(($start == $end) && ($start == $now_date) && ($end == $now_date)){
                $time_flag = 'today';
            }
        }
        if($site == 2){
            $model = $this->vooguemeOperate;
            $web_model = Db::connect('database.db_voogueme');
        }elseif ($site == 3){
            $model = $this->nihaoOperate;
            $web_model = Db::connect('database.db_nihao');
        }else{
            $model = $this->zeeloolOperate;
            $web_model = Db::connect('database.db_zeelool');
        }
        $web_model->table('sales_flat_order')->query("set time_zone='+8:00'");
        $web_model->table('sales_flat_order')->query("set time_zone='+8:00'");
        $web_model->table('sales_flat_quote')->query("set time_zone='+8:00'");
        $cache_vag = 'day_hour_order_quote_'.$site.$time;
        $cache_arr = Cache::get($cache_vag);
        if(!$cache_arr){
            $time_where['created_at'] = ['between', [$start.' 00:00:00',$end.' 23:59:59']];
            $itemtime_where['i.created_at'] = ['between', [$start.' 00:00:00',$end.' 23:59:59']];
            $order_time['o.status'] = ['in',['free_processing', 'processing', 'complete', 'paypal_reversed', 'payment_review', 'paypal_canceled_reversal']];
            $order_time['o.order_type'] = 1;
            //订单数据
            $order_resultList = $web_model->table('sales_flat_order')->alias('o')->where($time_where)->where($order_time)->field('DATE_FORMAT(o.created_at,"%H") hour_created_at ,count(*) order_counter,round(sum(o.base_grand_total),2) hour_grand_total')->group("DATE_FORMAT(o.created_at,'%H')")->select();

            //销售量
            $orderitem_resultlist = $web_model->table('sales_flat_order_item')->alias('i')->join('sales_flat_order o','i.order_id=o.entity_id')->where($itemtime_where)->where($order_time)->field('DATE_FORMAT(i.created_at,"%H") hour_created_at ,sum(i.qty_ordered) orderitem_counter')->group("DATE_FORMAT(i.created_at,'%H')")->select();

            //购物车数量
            $quote_where['base_grand_total'] = ['>',0];
            $quote_resultList = $web_model->table('sales_flat_quote')->where($time_where)->where($quote_where)->field('DATE_FORMAT(created_at,"%H") hour_created_at ,count(*) quote_counter')->group("DATE_FORMAT(created_at,'%H')")->select();
            //获取session
            $ga_result = $model->ga_hour_data($start,$end);
            $finalList = array();
            for ($i = 0; $i < 24; $i++) {
                if($time_flag){
                    $hour = date('H');
                    if($i <= $hour){
                        $finalList[$i]['hour'] = $i;
                        $finalList[$i]['hour_created'] = "$i:00 - $i:59";
                    }
                }else{
                    $finalList[$i]['hour'] = $i;
                    $finalList[$i]['hour_created'] = "$i:00 - $i:59";
                }
            }
            foreach ($finalList as $final_key => $final_value) {
                foreach ($ga_result as $ga_key => $ga_value) {
                    if ((int)$final_value['hour'] == (int)substr($ga_value['ga:dateHour'], 8)) {
                        $finalList[$final_key]['sessions'] += $ga_value['ga:sessions'];
                    }
                }
            }
            foreach ($finalList as $final_key => $final_value) {
                foreach ($order_resultList as $order_key => $order_value) {
                    if ((int)$final_value['hour'] == (int)$order_value['hour_created_at']) {
                        $finalList[$final_key]['hour_grand_total'] = $order_value['hour_grand_total'];
                        $finalList[$final_key]['order_counter'] = $order_value['order_counter'];
                    }
                }
            }
            foreach ($finalList as $final_key => $final_value) {
                foreach ($orderitem_resultlist as $orderitem_key => $orderitem_value) {
                    if ((int)$final_value['hour'] == (int)$orderitem_value['hour_created_at']) {
                        $finalList[$final_key]['orderitem_counter'] = (int)$orderitem_value['orderitem_counter'];
                    }
                }
            }
            foreach ($finalList as $final_key => $final_value) {
                foreach ($quote_resultList as $quote_key => $quote_value) {
                    if ((int)$final_value['hour'] == (int)$quote_value['hour_created_at']) {
                        $finalList[$final_key]['quote_counter'] = $quote_value['quote_counter'];
                    }
                }
            }
            $total_array = array();
            foreach ($finalList as $key => $value) {
                $total_array['sessions'] += $value['sessions'];
                $total_array['hour_grand_total'] += $value['hour_grand_total'];
                $total_array['order_counter'] += $value['order_counter'];
                $total_array['orderitem_counter'] += $value['orderitem_counter'];
                $total_array['quote_counter'] += $value['quote_counter'];
                //会话转化率 订单/sessions
                $finalList[$key]['order_sessions_conversion'] = $finalList[$key]['sessions'] ? round($finalList[$key]['order_counter'] / $finalList[$key]['sessions'] * 100, 2).'%' : 0;
                $finalList[$key]['order_quote_conversion'] = $finalList[$key]['quote_counter'] ? round($finalList[$key]['order_counter'] / $finalList[$key]['quote_counter'] * 100, 2).'%' : 0;
                $finalList[$key]['quote_sessions_conversion'] = $finalList[$key]['sessions'] ? round($finalList[$key]['quote_counter'] / $finalList[$key]['sessions'] * 100, 2).'%' : 0;
                $finalList[$key]['grand_total_order_conversion'] = $finalList[$key]['order_counter'] ? round($finalList[$key]['hour_grand_total'] / $finalList[$key]['order_counter'], 2) : 0;
            }
            $total_array['order_sessions_conversion'] = $total_array['sessions'] ? round($total_array['order_counter'] / $total_array['sessions'] * 100, 2) . "%" : 0;
            $total_array['order_quote_conversion'] = $total_array['quote_counter'] ? round($total_array['order_counter'] / $total_array['quote_counter'] * 100, 2) . "%" : 0;
            $total_array['quote_sessions_conversion'] = $total_array['sessions'] ? round($total_array['quote_counter'] / $total_array['sessions'] * 100, 2) . "%" : 0;
            $total_array['grand_total_order_conversion'] = $total_array['order_counter'] ? round($total_array['hour_grand_total'] / $total_array['order_counter'], 2) : 0;

            $echart_data['hourStr'] = "";
            $echart_data['sale_amount'] = "";
            $echart_data['order_counter'] = "";
            $echart_data['orderitem_counter'] = "";
            $echart_data['grand_total_order_conversion'] = "";

            for ($i = 0; $i < 24; $i++) {
                if ($finalList[$i]['sessions'] || $finalList[$i]['quote_counter']) {
                    $echart_data['hourStr'] .= "$i:00,";
                    $echart_data['sale_amount'] .= $finalList[$i]['hour_grand_total'] . ",";
                    $echart_data['order_counter'] .= $finalList[$i]['order_counter'] . ",";
                    $echart_data['orderitem_counter'] .= $finalList[$i]['orderitem_counter'] . ",";
                    $echart_data['grand_total_order_conversion'] .= $finalList[$i]['grand_total_order_conversion'] . ",";
                } else {
                    $echart_data['hourStr'] .= "$i:00,";
                    $echart_data['sale_amount'] .= "0,";
                    $echart_data['order_counter'] .= "0,";
                    $echart_data['orderitem_counter'] .= "0,";
                    $echart_data['grand_total_order_conversion'] .= "0,";
                }
            }
            $echart_data['hourStr'] = rtrim($echart_data['hourStr'], ',');
            $echart_data['sale_amount'] = rtrim($echart_data['sale_amount'], ',');
            $echart_data['order_counter'] = rtrim($echart_data['order_counter'], ',');
            $echart_data['orderitem_counter'] = rtrim($echart_data['orderitem_counter'], ',');
            $echart_data['grand_total_order_conversion'] = rtrim($echart_data['grand_total_order_conversion'], ',');
            //缓存
            $cache_data['echart_data'] = $echart_data;
            $cache_data['total_array'] = $total_array;
            $cache_data['finalList'] = $finalList;
            Cache::set($cache_vag, $cache_data, 300);
        }else{
            $cache_data = $cache_arr;
        }
        return $cache_data;
    }
    /**
     * 销售量
     *
     * @Description
     * @author wpl
     * @since 2020/10/15 09:09:27 
     * @return void
     */
    public function sales_num_line()
    {
        if ($this->request->isAjax()) {
            $params = $this->request->param();
            $order_platform = $params['order_platform'] ? $params['order_platform'] : 1;
            $time_str = $params['time_str'];
            $info = $this->get_data($order_platform,$time_str);
            $data = $info['echart_data'];
            $xdata = explode(',',$data['hourStr']);
            $ydata = explode(',',$data['orderitem_counter']);
            $json['xcolumnData'] = $xdata;
            $json['column'] = ['销售量'];
            $json['columnData'] = [
                [
                    'type' => 'line',
                    'data' => $ydata,
                    'name' => '销售量',
                    'smooth' => false //平滑曲线
                ],

            ];

            return json(['code' => 1, 'data' => $json]);
        }
    }

    /**
     * 销售额
     *
     * @Description
     * @author wpl
     * @since 2020/10/15 09:08:49 
     * @return void
     */
    public function sales_money_line()
    {
        if ($this->request->isAjax()) {
            $params = $this->request->param();
            $order_platform = $params['order_platform'] ? $params['order_platform'] : 1;
            $time_str = $params['time_str'];
            $info = $this->get_data($order_platform,$time_str);
            $data = $info['echart_data'];
            $xdata = explode(',',$data['hourStr']);
            $ydata = explode(',',$data['sale_amount']);
            $json['xcolumnData'] = $xdata;
            $json['column'] = ['销售额'];
            $json['columnData'] = [
                [
                    'type' => 'line',
                    'data' => $ydata,
                    'name' => '销售额',
                    'smooth' => false //平滑曲线
                ]

            ];

            return json(['code' => 1, 'data' => $json]);
        }
    }

    /**
     * 订单数
     *
     * @Description
     * @author wpl
     * @since 2020/10/15 09:08:28 
     * @return void
     */
    public function order_num_line()
    {
        if ($this->request->isAjax()) {
            $params = $this->request->param();
            $order_platform = $params['order_platform'] ? $params['order_platform'] : 1;
            $time_str = $params['time_str'];
            $info = $this->get_data($order_platform,$time_str);
            $data = $info['echart_data'];
            $xdata = explode(',',$data['hourStr']);
            $ydata = explode(',',$data['order_counter']);
            $json['xcolumnData'] = $xdata;
            $json['column'] = ['订单数量'];
            $json['columnData'] = [
                [
                    'type' => 'line',
                    'data' => $ydata,
                    'name' => '订单数量',
                    'smooth' => false //平滑曲线
                ],

            ];

            return json(['code' => 1, 'data' => $json]);
        }
    }

    /**
     * 客单价
     *
     * @Description
     * @author wpl
     * @since 2020/10/15 09:08:04 
     * @return void
     */
    public function unit_price_line()
    {
        if ($this->request->isAjax()) {
            $params = $this->request->param();
            $order_platform = $params['order_platform'] ? $params['order_platform'] : 1;
            $time_str = $params['time_str'];
            $info = $this->get_data($order_platform,$time_str);
            $data = $info['echart_data'];
            $xdata = explode(',',$data['hourStr']);
            $ydata = explode(',',$data['grand_total_order_conversion']);
            $json['xcolumnData'] = $xdata;
            $json['column'] = ['客单价'];
            $json['columnData'] = [

                [
                    'type' => 'line',
                    'data' => $ydata,
                    'name' => '客单价',
                    'smooth' => false //平滑曲线
                ],

            ];

            return json(['code' => 1, 'data' => $json]);
        }
    }
}
