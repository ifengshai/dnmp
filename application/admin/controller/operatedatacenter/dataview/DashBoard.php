<?php

namespace app\admin\controller\operatedatacenter\dataview;

use app\common\controller\Backend;
use think\Request;

class DashBoard extends Backend
{

    public function _initialize()
    {
        parent::_initialize();

        //每日的数据
        $this->zeeloolOperate = new \app\admin\model\operatedatacenter\Zeelool;
        $this->vooguemeOperate = new \app\admin\model\operatedatacenter\Voogueme();
        $this->nihaoOperate = new \app\admin\model\operatedatacenter\Nihao();
        $this->datacenterday = new \app\admin\model\operatedatacenter\Datacenter();
    }

    /**
     *  获取指定日期段内每一天的日期
     * @param Date $startdate 开始日期
     * @param Date $enddate 结束日期
     * @return Array
     *
     * Created by Phpstorm.
     * User: jhh
     * Date: 2020/10/13
     * Time: 16:06:51
     */
    function getDateFromRange($startdate, $enddate)
    {
        $stimestamp = strtotime($startdate);
        $etimestamp = strtotime($enddate);
        // 计算日期段内有多少天
        $days = ($etimestamp - $stimestamp) / 86400 + 1;
        // 保存每天日期
        $date = array();
        for ($i = 0; $i < $days; $i++) {
            $date[] = date('Y-m-d', $stimestamp + (86400 * $i));
        }
        return $date;
    }


    /**
     * Created by Phpstorm.
     * User: jhh
     * Date: 2020/10/13
     * Time: 15:02:03
     */
    public function index()
    {
        // dump(1111);die;
        //默认进入页面是z站的数据
        // 活跃用户数
        $active_user_num = $this->zeeloolOperate->getActiveUser();
        //注册用户数
        $register_user_num = $this->zeeloolOperate->getRegisterUser();
        //复购用户数
        $again_user_num = $this->zeeloolOperate->getAgainUser();
        //vip用户数
        $vip_user_num = $this->zeeloolOperate->getVipUser();
        //订单数
        $order_num = $this->zeeloolOperate->getOrderNum();
        //客单价
        $order_unit_price = $this->zeeloolOperate->getOrderUnitPrice();
        //销售额
        $sales_total_money = $this->zeeloolOperate->getSalesTotalMoney();
        //邮费
        $shipping_total_money = $this->zeeloolOperate->getShippingTotalMoney();
        $this->view->assign(compact('order_num', 'order_unit_price', 'sales_total_money', 'shipping_total_money', 'active_user_num', 'register_user_num', 'again_user_num', 'vip_user_num'));
        return $this->view->fetch();
    }

    /**
     * ajax获取上半部分数据
     *
     * Created by Phpstorm.
     * User: jhh
     * Date: 2020/10/13
     * Time: 13:42:57
     */
    public function ajax_top_data()
    {
        if ($this->request->isAjax()) {
            $params = $this->request->param();
            //站点
            $order_platform = $params['order_platform'] ? $params['order_platform'] : 1;
            $now_day = date('Y-m-d') . ' ' . '00:00:00' . ' - ' . date('Y-m-d');
            //时间
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
                case 4:
                    $model = $this->datacenterday;
                    break;
            }
            //活跃用户数
            $active_user_num = $model->getActiveUser(1,$time_str);
            //注册用户数
            $register_user_num = $model->getRegisterUser(1,$time_str);
            //复购用户数
            $again_user_num = $model->getAgainUser($time_str,1);
            //vip用户数
            $vip_user_num = $model->getVipUser(1,$time_str);
            //订单数
            $order_num = $model->getOrderNum(1,$time_str);
            //客单价
            $order_unit_price = $model->getOrderUnitPrice(1,$time_str);
            //销售额
            $sales_total_money = $model->getSalesTotalMoney(1,$time_str);
            //邮费
            $shipping_total_money = $model->getShippingTotalMoney(1,$time_str);
            $data = compact('order_num', 'order_unit_price', 'sales_total_money', 'shipping_total_money', 'active_user_num', 'register_user_num', 'again_user_num', 'vip_user_num');
            $this->success('', '', $data);
        }
    }

    /*
     * 活跃用户折线图
     */
    public function active_user_trend()
    {
        // $date_arr = ["2020-10-07" => 0, "2020-10-08" => 0, "2020-10-09" => 1, "2020-10-10" => 500, "2020-10-11" => 20, "2020-10-12" => 1000];

        if ($this->request->isAjax()) {
            $params = $this->request->param();
            $order_platform = $params['order_platform'];
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
                case 4:
                    $model = $this->datacenterday;
                    break;
            }
            $time_str = $params['time_str'];

            if ($order_platform) {
                $where['site'] = $order_platform;
            }
            if ($time_str) {
                $createat = explode(' ', $time_str);

                $first_sales_total = $model->getActiveUser(0,$createat[0]);
                $date_arr = array(
                    $createat[0] => $first_sales_total['active_user_num']
                );
                if ($createat[0] != $createat[3]) {
                    for ($i = 0; $i <= 100; $i++) {
                        $m = $i + 1;
                        $deal_date = date_create($createat[0]);
                        date_add($deal_date, date_interval_create_from_date_string("$m days"));
                        $next_day = date_format($deal_date, "Y-m-d");
                        $next_sales_total = $model->getActiveUser(0,$next_day);
                        $date_arr[$next_day] = $next_sales_total['active_user_num'];
                        if ($next_day == $createat[3]) {
                            break;
                        }
                    }
                }
                dump($date_arr);

            } else {
                //默认7天的数据
                $start = date('Y-m-d', strtotime('-6 day'));
                $end   = date('Y-m-d 23:59:59');
                $time_str = $start .' 00:00:00 - ' .$end.' 00:00:00';
                $createat = explode(' ', $time_str);
                $first_sales_total = $model->getActiveUser(0,$createat[0]);
                $date_arr = array(
                    $createat[0] => $first_sales_total['active_user_num']
                );
                if ($createat[0] != $createat[3]) {
                    for ($i = 0; $i <= 100; $i++) {
                        $m = $i + 1;
                        $deal_date = date_create($createat[0]);
                        date_add($deal_date, date_interval_create_from_date_string("$m days"));
                        $next_day = date_format($deal_date, "Y-m-d");
                        $next_sales_total = $model->getActiveUser(0,$next_day);
                        $date_arr[$next_day] = $next_sales_total['active_user_num'];
                        if ($next_day == $createat[3]) {
                            break;
                        }
                    }
                }
                // dump($date_arr);
            }
            $name = '活跃用户数';

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

    /*
     * 订单趋势折线图
     */
    public function order_trend()
    {
        // $date_arr = ["2020-10-07" => 0, "2020-10-08" => 0, "2020-10-09" => 1, "2020-10-10" => 500, "2020-10-11" => 20, "2020-10-12" => 1000];

        if ($this->request->isAjax()) {
            $params = $this->request->param();
            $order_platform = $params['order_platform'];
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
                case 4:
                    $model = $this->datacenterday;
                    break;
            }
            $time_str = $params['time_str'];

            if ($order_platform) {
                $where['site'] = $order_platform;
            }
            if ($time_str) {
                $createat = explode(' ', $time_str);

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
                $now_day = date('Y-m-d');
                //今天的订单数
                $today_order_num = $model->getOrderNum();
                $date_arr[$now_day] = $today_order_num['order_num'];
            }
            $name = '订单数';

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

    /*
     * 用户购买转化漏斗
     */
    public function user_change_trend()
    {
        if ($this->request->isAjax()) {
            $params = $this->request->param();
            $order_platform = $params['order_platform'];
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
                case 4:
                    $model = $this->datacenterday;
                    break;
            }
            $time_str = $params['time_str'];
            if ($time_str) {
                //着陆页数据
                $landing_num = $model->getLanding($time_str,1);
                $detail_num = $model->getDetail($time_str,1);
                $cart_num = $model->getCart($time_str,1);
                $complete_num = $model->getComplete($time_str,1);
            }else {
                //着陆页数据
                $landing_num = $model->getLanding();
                $detail_num = $model->getDetail();
                $cart_num = $model->getCart();
                $complete_num = $model->getComplete();
            }

            if ($order_platform) {
                $where['site'] = $order_platform;
            }

            $name = '用户购买转化漏斗';
            $date_arr = [
                ['value' => $landing_num['landing_num'], 'name' => '着陆页'],
                ['value' => $detail_num['detail_num'], 'name' => '商品详情页'],
                ['value' => $cart_num['cart_num'], 'name' => '加购物车'],
                ['value' => $complete_num['complete_num'], 'name' => '支付转化']
            ];

            $json['column'] = [$name];
            $json['columnData'] = $date_arr;
            return json(['code' => 1, 'data' => $json]);
        }
    }

}
