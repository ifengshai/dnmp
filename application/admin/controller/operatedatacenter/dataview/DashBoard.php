<?php

namespace app\admin\controller\operatedatacenter\dataview;

use app\admin\controller\elasticsearch\async\AsyncOrder;
use app\admin\model\platformManage\MagentoPlatform;
use app\common\controller\Backend;
use think\Cache;
use think\Db;
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
        $this->zeeloolDeOperate = new \app\admin\model\operatedatacenter\ZeeloolDe;
        $this->zeeloolJpOperate = new \app\admin\model\operatedatacenter\ZeeloolJp;
        $this->datacenterday = new \app\admin\model\operatedatacenter\Datacenter();
        $this->magentoplatform = new \app\admin\model\platformmanage\MagentoPlatform();
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
        //查询对应平台权限
        $magentoplatformarr = $this->magentoplatform->getNewAuthSite();
        // dump(collection($magentoplatformarr)->toArray());
        foreach ($magentoplatformarr as $key => $val) {
            if (!in_array($val, ['zeelool', 'voogueme', 'nihao', '全部'])) {
                unset($magentoplatformarr[$key]);
            }
            if ($key == 100) {
                unset($magentoplatformarr[$key]);
                $magentoplatformarr[4] = '全部';
            }
        }
        // dump(collection($magentoplatformarr)->toArray());
        //默认进入页面是z站的数据
        // $arr = Cache::get('Operatedatacenter_dataviews' . 1 . md5(serialize('index')));
        $arr = [];
        if ($arr) {
            $this->view->assign($arr);
        } else {
            // 活跃用户数
            $active_user_num = $this->zeeloolOperate->getActiveUser();
            //注册用户数
            $register_user_num = $this->zeeloolOperate->getRegisterUser();
            $time_arr = date('Y-m-d 00:00:00', strtotime('-6 day')) . ' - ' . date('Y-m-d H:i:s', time());
            // dump($time_arr);die;
            //复购用户数
            $again_user_num = $this->zeeloolOperate->getAgainUser($time_arr, 0);
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
            $data = compact('order_num', 'order_unit_price', 'sales_total_money', 'shipping_total_money', 'active_user_num', 'register_user_num', 'again_user_num', 'vip_user_num', 'magentoplatformarr');
            Cache::set('Operatedatacenter_dataviews' . 1 . md5(serialize('index')), $data, 7200);
            $this->view->assign($data);
        }
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
            $compare_time_str = $params['compare_time_str'] ? $params['compare_time_str'] : '';

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
            $arr = Cache::get('Operatedatacenter_dataviews' . $order_platform . md5(serialize($time_str)));
            if ($arr) {
                // Cache::rm('Operatedatacenter_dataview' . $order_platform . md5(serialize($time_str)));
                // $this->success('', '', $arr);
            }
            // dump($time_str);
            // dump($compare_time_str);
            //活跃用户数
            $active_user_num = $model->getActiveUser($time_str, $compare_time_str);

            //注册用户数
            $register_user_num = $model->getRegisterUser($time_str, $compare_time_str);

            //复购用户数
            $again_user_num = $model->getAgainUser($time_str, $compare_time_str);

            // $again_user_num = 0;
            //vip用户数
            $vip_user_num = $model->getVipUser($time_str, $compare_time_str);

            //订单数
            $order_num = $model->getOrderNum($time_str, $compare_time_str);


            //销售额
            $sales_total_money = $model->getSalesTotalMoney($time_str, $compare_time_str);

            //邮费
            $shipping_total_money = $model->getShippingTotalMoney($time_str, $compare_time_str);

            //客单价
            $order_unit_price = $model->getOrderUnitPrice($time_str, $compare_time_str);

            // dump($active_user_num);
            // dump($register_user_num);
            // dump($again_user_num);
            // dump($vip_user_num);
            // dump($order_num);
            // dump($sales_total_money);
            // dump($shipping_total_money);
            // dump($order_unit_price);

            $data = compact('order_num', 'order_unit_price', 'sales_total_money', 'shipping_total_money', 'active_user_num', 'register_user_num', 'again_user_num', 'vip_user_num');
            Cache::set('Operatedatacenter_dataviews' . $order_platform . md5(serialize($time_str)), $data, 7200);
            $this->success('', '', $data);
        }
        $this->view->assign(compact('order_num', 'order_unit_price', 'sales_total_money', 'shipping_total_money', 'active_user_num', 'register_user_num', 'again_user_num', 'vip_user_num'));
    }

    /*
     * 活跃用户折线图 弃用
     */
    public function active_user_trend()
    {
        // $date_arr = ["2020-10-07" => 0, "2020-10-08" => 0, "2020-10-09" => 1, "2020-10-10" => 500, "2020-10-11" => 20, "2020-10-12" => 1000];

        if ($this->request->isAjax()) {
            $params = $this->request->param();
            $order_platform = $params['order_platform'];
            switch ($order_platform) {
                case 1:
                    $model = new \app\admin\model\operatedatacenter\Zeelool;
                    break;
                case 2:
                    $model = new \app\admin\model\operatedatacenter\Voogueme();
                    break;
                case 3:
                    $model = new \app\admin\model\operatedatacenter\Nihao();
                    break;
                case 4:
                    $model = new \app\admin\model\operatedatacenter\Datacenter();
                    break;
            }
            $time_str = $params['time_str'];

            if ($order_platform) {
                $where['site'] = $order_platform;
            }
            if ($time_str) {
                $createat = explode(' ', $time_str);
                $where['day_date'] = ['between', [$createat[0], $createat[3]]];
            } else {
                $start = date('Y-m-d', strtotime('-6 day'));
                $end = date('Y-m-d 23:59:59');
                $where['day_date'] = ['between', [$start, $end]];
            }
            if ($order_platform == 4) {
                unset($where['site']);
                $sales_total = $model->where($where)->column('day_date', 'active_user_num');
                $arr = array();
                foreach ($sales_total as $k => $v) {
                    if ($arr[$v]) {
                        $arr[$v] += $k;
                    } else {
                        $arr[$v] = $k;
                    }
                }
                $date_arr = $arr;
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
            } else {
                $arr = $model->where($where)->column('day_date', 'active_user_num');
                $date_arr = $arr;
                $name = '活跃用户数';

                $json['xcolumnData'] = array_values($date_arr);
                $json['column'] = [$name];
                $json['columnData'] = [
                    [
                        'name' => $name,
                        'type' => 'line',
                        'smooth' => true,
                        'data' => array_keys($date_arr)
                    ],

                ];
            }
            return json(['code' => 1, 'data' => $json]);
        }
    }

    /*
     * 订单趋势折线图 弃用
     */
    public function order_trend()
    {
        // $date_arr = ["2020-10-07" => 0, "2020-10-08" => 0, "2020-10-09" => 1, "2020-10-10" => 500, "2020-10-11" => 20, "2020-10-12" => 1000];

        if ($this->request->isAjax()) {
            $params = $this->request->param();
            $order_platform = $params['order_platform'];
            switch ($order_platform) {
                case 1:
                    $model = new \app\admin\model\operatedatacenter\Zeelool;
                    break;
                case 2:
                    $model = new \app\admin\model\operatedatacenter\Voogueme();
                    break;
                case 3:
                    $model = new \app\admin\model\operatedatacenter\Nihao();
                    break;
                case 4:
                    $model = new \app\admin\model\operatedatacenter\Datacenter();
                    break;
            }
            $time_str = $params['time_str'];

            if ($order_platform) {
                $where['site'] = $order_platform;
            }
            if ($time_str) {
                $createat = explode(' ', $time_str);
                $where['day_date'] = ['between', [$createat[0], $createat[3]]];
            } else {
                $start = date('Y-m-d', strtotime('-6 day'));
                $end = date('Y-m-d 23:59:59');
                $where['day_date'] = ['between', [$start, $end]];
            }
            if ($order_platform == 4) {
                unset($where['site']);

                $sales_total = Db::name('datacenter_day')->where($where)->order('day_date', 'asc')->field('day_date,order_num')->select();

                $arr = array();
                foreach ($sales_total as $k => $v) {
                    if ($arr[$v['day_date']]) {
                        $arr[$v['day_date']] += $v['order_num'];
                    } else {
                        $arr[$v['day_date']] = $v['order_num'];
                    }
                }
                $date_arr = $arr;
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
            } else {
                // $arr = $model->where($where)->order('day_date', 'asc')->column('day_date', 'order_num');
                $sales_total = Db::name('datacenter_day')->where($where)->order('day_date', 'asc')->field('day_date,order_num')->select();
                $arr = array();
                foreach ($sales_total as $k => $v) {
                    if ($arr[$v['day_date']]) {
                        $arr[$v['day_date']] += $v['order_num'];
                    } else {
                        $arr[$v['day_date']] = $v['order_num'];
                    }
                }
                $date_arr = $arr;
                // $name = '订单数';

                $json['xcolumnData'] = array_keys($date_arr);
                // $json['column'] = [$name];
                $json['columnData'] = [
                    [
                        'name' => '订单数',
                        'type' => 'line',
                        'smooth' => true,
                        'data' => array_values($date_arr)
                    ],

                ];
            }

            return json(['code' => 1, 'data' => $json]);
        }
    }

    //活跃用户和订单趋势合二为一的折线图
    public function order_trend_active_user_trend()
    {
        if ($this->request->isAjax()) {
            $params = $this->request->param();
            $order_platform = $params['order_platform'];
            switch ($order_platform) {
                case 1:
                    $model = new \app\admin\model\operatedatacenter\Zeelool;
                    break;
                case 2:
                    $model = new \app\admin\model\operatedatacenter\Voogueme();
                    break;
                case 3:
                    $model = new \app\admin\model\operatedatacenter\Nihao();
                    break;
                case 4:
                    $model = new \app\admin\model\operatedatacenter\Datacenter();
                    break;
            }
            $time_str = $params['time_str'];

            if ($order_platform) {
                $where['site'] = $order_platform;
            }
            if ($time_str) {
                $createat = explode(' ', $time_str);
                $where['day_date'] = ['between', [$createat[0], $createat[3]]];
            } else {
                $start = date('Y-m-d', strtotime('-6 day'));
                $end = date('Y-m-d 23:59:59');
                $where['day_date'] = ['between', [$start, $end]];
            }
            if ($order_platform == 4) {
                unset($where['site']);
                $sales_total = $model->where($where)->column('day_date', 'active_user_num');
                $arr = array();
                foreach ($sales_total as $k => $v) {
                    if ($arr[$v]) {
                        $arr[$v] += $k;
                    } else {
                        $arr[$v] = $k;
                    }
                }
                $date_arr = $arr;

                $sales_total1 = Db::name('datacenter_day')->where($where)->order('day_date', 'asc')->field('day_date,order_num')->select();
                $arr1 = array();
                foreach ($sales_total1 as $k => $v) {
                    if ($arr1[$v['day_date']]) {
                        $arr1[$v['day_date']] += $v['order_num'];
                    } else {
                        $arr1[$v['day_date']] = $v['order_num'];
                    }
                }
                $date_arr1 = $arr1;


                $json['xColumnName'] = array_keys($date_arr);
                $json['columnData'] = [
                    [
                        'type' => 'line',
                        'data' => array_values($date_arr),
                        'name' => '活跃用户数',
                        'yAxisIndex' => 0,
                        'smooth' => true //平滑曲线
                    ],
                    [
                        'type' => 'line',
                        'data' => array_values($date_arr1),
                        'name' => '订单数',
                        'yAxisIndex' => 1,
                        'smooth' => true //平滑曲线
                    ],

                ];

            } else {
                $arr = $model->where($where)->column('active_user_num', 'day_date');
                $date_arr = $arr;
                $arr1 = $model->where($where)->column('order_num', 'day_date');
                $date_arr1 = $arr1;
                $json['xColumnName'] = array_keys($date_arr);

                $json['columnData'] = [
                    [
                        'type' => 'line',
                        'data' => array_values($date_arr),
                        'name' => '活跃用户数',
                        'yAxisIndex' => 0,
                        'smooth' => true //平滑曲线
                    ],
                    [
                        'type' => 'line',
                        'data' => array_values($date_arr1),
                        'name' => '订单数',
                        'yAxisIndex' => 1,
                        'smooth' => true //平滑曲线
                    ],

                ];
            }
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
                $landing_num = $model->getLanding($time_str, 1);
                $detail_num = $model->getDetail($time_str, 1);
                $cart_num = $model->getCart($time_str, 1);
                $complete_num = $model->getComplete($time_str, 1);
            } else {
                $start = date('Y-m-d', strtotime('-6 day'));
                $end = date('Y-m-d 23:59:59');
                $time_str = $start . ' 00:00:00 - ' . $end . ' 00:00:00';
                //着陆页数据
                $landing_num = $model->getLanding($time_str, 1);
                $detail_num = $model->getDetail($time_str, 1);
                $cart_num = $model->getCart($time_str, 1);
                $complete_num = $model->getComplete($time_str, 1);
            }

            if ($order_platform) {
                $where['site'] = $order_platform;
            }

            $name = '用户购买转化漏斗';
            $date_arr = [
                ['value' => round($landing_num['landing_num'], 0), 'percent' => '100%', 'name' => '着陆页'],
                ['value' => round($detail_num['detail_num'], 0), 'percent' => $landing_num['landing_num'] == 0 ? '0%' : round($detail_num['detail_num'] / $landing_num['landing_num'] * 100, 2) . '%', 'name' => '商品详情页'],
                ['value' => round($cart_num['cart_num'], 0), 'percent' => $detail_num['detail_num'] == 0 ? '0%' : round($cart_num['cart_num'] / $detail_num['detail_num'] * 100, 2) . '%', 'name' => '加购物车'],
                ['value' => round($complete_num['complete_num'], 0), 'percent' => $cart_num['cart_num'] == 0 ? '0%' : round($complete_num['complete_num'] / $cart_num['cart_num'] * 100, 2) . '%', 'name' => '支付转化']
            ];

            $json['column'] = [$name];
            $json['columnData'] = $date_arr;
            // $json['legendData'] = ['着陆页','商品详情页','加购物车','支付转化'];
            // $json['legendShow'] = true;
            return json(['code' => 1, 'data' => $json]);
        }
    }

}
