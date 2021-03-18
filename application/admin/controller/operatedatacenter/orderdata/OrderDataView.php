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
        $this->zeeloolde = new \app\admin\model\order\order\ZeeloolDe();
        $this->zeelooljp = new \app\admin\model\order\order\ZeeloolJp();
        $this->zeeloolOperate  = new \app\admin\model\operatedatacenter\Zeelool;
        $this->vooguemeOperate  = new \app\admin\model\operatedatacenter\Voogueme;
        $this->nihaoOperate  = new \app\admin\model\operatedatacenter\Nihao;
        $this->zeelooldeOperate  = new \app\admin\model\operatedatacenter\ZeeloolDe();
        $this->zeelooljpOperate  = new \app\admin\model\operatedatacenter\ZeeloolJp();
        $this->magentoplatform = new \app\admin\model\platformmanage\MagentoPlatform();
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
        //订单金额分布
        $order_total_distribution = $this->zeeloolOperate->getMoneyOrderNum();
        //订单运费数据统计
        $order_shipping = $this->zeeloolOperate->getOrderShipping();
        //国家地域统计
        $country = $this->zeeloolOperate->getCountryNum();
        //查询对应平台权限
        $magentoplatformarr = $this->magentoplatform->getAuthSite();
        foreach ($magentoplatformarr as $key=>$val){
            if(!in_array($val['name'],['zeelool','voogueme','nihao','zeelool_de','zeelool_jp'])){
                unset($magentoplatformarr[$key]);
            }
        }
        $this->view->assign(compact('order_num', 'order_unit_price', 'sales_total_money', 'shipping_total_money', 'replacement_order_num', 'replacement_order_total', 'online_celebrity_order_num', 'online_celebrity_order_total', 'zeeloolSalesNumList','order_total_distribution','order_shipping','country','magentoplatformarr'));
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
            $start = date('Y-m-d', strtotime('-6 day'));
            $end   = date('Y-m-d 23:59:59');
            $default_day = $start . ' ' . '00:00:00' . ' - ' . $end;
            $time_str = $params['time_str'] ? $params['time_str'] : $default_day;
            $compare_time_str= $params['compare_time_str'] ? $params['compare_time_str'] : $default_day;
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
                case 10:
                    $model = $this->zeelooldeOperate;
                    break;
                case 11:
                    $model = $this->zeelooljpOperate;
                    break;
            }

            $order_num = $model->getOrderNum($time_str,$compare_time_str);  //订单数
            $order_unit_price = $model->getOrderUnitPrice($time_str,$compare_time_str); //客单价
            $sales_total_money = $model->getSalesTotalMoney($time_str,$compare_time_str); //销售额
            $shipping_total_money = $model->getShippingTotalMoney($time_str,$compare_time_str);  //邮费
            $replacement_order_num = $model->getReplacementOrderNum($time_str);  //补发单订单数
            $replacement_order_total = $model->getReplacementOrderTotal($time_str); //补发单销售额
            $online_celebrity_order_num = $model->getOnlineCelebrityOrderNum($time_str); //网红单订单数
            $online_celebrity_order_total = $model->getOnlineCelebrityOrderTotal($time_str);  //网红单销售额
            $order_total_distribution = $model->getMoneyOrderNum($time_str); //订单金额分布
            $order_shipping = $model->getOrderShipping($time_str);//订单运费数据统计
            $country = $model->getCountryNum($time_str);//国家地域统计
            $country_str = '';
            foreach ($country as $value){
                $country_str.= '<tr><td>'.$value['country_id'].'</td><td>'.$value['count'].'</td><td>'.$value['rate'].'</td></tr>';
            }
            $data = compact('order_num', 'order_unit_price', 'sales_total_money', 'shipping_total_money', 'replacement_order_num', 'replacement_order_total', 'online_celebrity_order_num', 'online_celebrity_order_total','order_total_distribution','order_shipping','country_str');
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
                $where['site'] = 1;
                $model = $this->zeeloolOperate;
            } elseif ($order_platform == 2) {
                $where['site'] = 2;
                $model = $this->vooguemeOperate;
            } elseif ($order_platform == 3) {
                $where['site'] = 3;
                $model = $this->nihaoOperate;
            } elseif ($order_platform == 10) {
                $where['site'] = 10;
                $model = $this->zeelooldeOperate;
            } elseif ($order_platform == 11) {
                $where['site'] = 11;
                $model = $this->zeelooljpOperate;
            }
            if ($time_str) {
                $createat = explode(' ', $time_str);
                $where['day_date'] = ['between', [$createat[0], $createat[3].' 23:59:59']];
            } else {
                $start = date('Y-m-d', strtotime('-6 day'));
                $end   = date('Y-m-d 23:59:59');
                $where['day_date'] = ['between', [$start, $end]];
            }
            if ($type == 1) {
                $name = '订单数';
                $date_arr = $model->where($where)->column('order_num','day_date');
            } else {
                $name = '销售额';
                $date_arr = $model->where($where)->column('sales_total_money','day_date');
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
            } elseif ($order_platform == 10) {
                $model = $this->zeeloolde;
            } elseif ($order_platform == 11) {
                $model = $this->zeelooljp;
            }
            $time_str = $params['time_str'];
            if(!$time_str){
                $start = date('Y-m-d 00:00:00', strtotime('-6 day'));
                $end   = date('Y-m-d 23:59:59');
                $time_str = $start . ' - '. $end;
            }
            $createat = explode(' ', $time_str);
            $order_where['o.created_at'] = ['between', [$createat[0], $createat[3].' 23:59:59']];
            $order_where['o.status'] = ['in', ['free_processing', 'processing', 'complete', 'paypal_reversed', 'payment_review', 'paypal_canceled_reversal','delivered']];
            $order_where['oa.address_type'] = 'shipping';
            $order_where['o.order_type'] = 1;
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
            //查询时间段内每天的客单价,中位数，标准差
            $time_str = $params['time_str'];
            if(!$time_str){
                $start = date('Y-m-d 00:00:00', strtotime('-6 day'));
                $end   = date('Y-m-d 23:59:59');
                $time_str = $start . ' - '. $end;
            }
            $createat = explode(' ', $time_str);
            $where['day_date'] = ['between', [$createat[0], $createat[3].' 23:59:59']];
            $where['site'] = $order_platform;
            $order_info = Db::name('datacenter_day')->where($where)->field('day_date,order_unit_price,order_total_midnum,order_total_standard')->select();
            $order_info = collection($order_info)->toArray();
            $json['xColumnName'] = array_column($order_info,'day_date') ? array_column($order_info,'day_date') :[];
            $json['columnData'] = [
                [
                    'type' => 'bar',
                    'barWidth' => '20%',
                    'data' => array_column($order_info,'order_unit_price') ? array_column($order_info,'order_unit_price'):[],
                    'name' => '客单价'
                ],
                [
                    'type' => 'bar',
                    'barWidth' => '20%',
                    'data' => array_column($order_info,'order_total_midnum') ? array_column($order_info,'order_total_midnum'):[],
                    'name' => '中位数'
                ],
                [
                    'type' => 'line',
                    'yAxisIndex' => 1,
                    'data' => array_column($order_info,'order_total_standard') ? array_column($order_info,'order_total_standard'):[],
                    'name' => '标准差'
                ]

            ];
            return json(['code' => 1, 'data'=>$json]);
        }
    }
}
