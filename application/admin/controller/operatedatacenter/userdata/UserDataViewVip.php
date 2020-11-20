<?php

namespace app\admin\controller\operatedatacenter\userdata;

use app\admin\model\platformManage\MagentoPlatform;
use app\common\controller\Backend;
use think\Cache;
use think\Db;
use think\Request;

class UserDataViewVip extends Backend
{

    public function _initialize()
    {
        parent::_initialize();

        $this->zeelool = new \app\admin\model\order\order\Zeelool();
        $this->voogueme = new \app\admin\model\order\order\Voogueme();
        $this->nihao = new \app\admin\model\order\order\Nihao();
        $this->zeeloolOperate = new \app\admin\model\operatedatacenter\Zeelool;
        $this->vooguemeOperate = new \app\admin\model\operatedatacenter\Voogueme();
        $this->nihaoOperate = new \app\admin\model\operatedatacenter\Nihao();
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
                $model = $this->vooguemeOperate;
                $web_model = Db::connect('database.db_voogueme');
                $site = 2;
            }elseif($filter['order_platform'] == 3){
                $order_model = $this->nihao;
                $model = $this->nihaoOperate;
                $web_model = Db::connect('database.db_nihao');
                $site = 3;
            }else{
                $order_model = $this->zeelool;
                $model = $this->zeeloolOperate;
                $web_model = Db::connect('database.db_zeelool');
                $site = 1;
            }
            $web_model->table('oc_vip_order')->query("set time_zone='+8:00'");
            $map['order_status'] = 'success';
            //新增VIP会员数
            $vip_num = $model->getVipUser($filter['time_str'],$filter['time_str2']);
            //复购VIP会员数
            $again_user_num['again_user_num'] = $model->get_again_user_vip($filter['time_str']);
            if($filter['time_str2']){
                $contrast_again_user_num = $model->get_again_user_vip($filter['time_str2']);
                $again_user_num['contrast_again_user_num'] = $contrast_again_user_num ? round(($again_user_num['again_user_num']-$contrast_again_user_num)/$contrast_again_user_num*100,2) : 100;
            }
            //总VIP会员数
            $sum_vip_num = $web_model->table('customer_entity')->where('is_vip',1)->count();
            $this->view->assign(compact('vip_num', 'again_user_num', 'sum_vip_num'));

            unset($filter['one_time-operate']);
            unset($filter['time_str']);
            unset($filter['time_str2']);
            unset($filter['order_platform']);
            $this->request->get(['filter' => json_encode($filter)]);
            list($where, $sort, $order, $offset, $limit) = $this->buildparams();
            $total = $web_model
                ->table('oc_vip_order')
                ->where($where)
                ->where($map)
                ->count();
            $list = $web_model
                ->table('oc_vip_order')
                ->where($where)
                ->where($map)
                ->order($sort, $order)
                ->limit($offset, $limit)
                ->field('customer_id,customer_email,start_time,end_time')
                ->select();
            $list = collection($list)->toArray();
            foreach ($list as $key=>$value){
                $list[$key]['customer_id'] = $value['customer_id'];  //用户id
                $list[$key]['customer_email'] = $value['customer_email'];          //注册邮箱
                $list[$key]['start_time'] = $value['start_time'];  //VIP开始时间
                $list[$key]['end_time'] = $value['end_time'];  //VIP结束时间
                $end_time = strtotime($value['end_time']);
                $now_time = time();
                if($now_time>$end_time){
                    $list[$key]['rest_days'] = 0;
                }else{
                    $list[$key]['rest_days'] = 1 + ceil(($now_time-$end_time)/60/60/24);
                }
                $order_where['customer_id'] = $value['customer_id'];
                $order_status_where['status'] = ['in', ['free_processing', 'processing', 'complete', 'paypal_reversed', 'payment_review', 'paypal_canceled_reversal']];
                $order_where['created_at'] = ['between',[$value['start_time'],$value['end_time']]];
                $list[$key]['order_num'] = $order_model->where($order_where)->where($order_status_where)->count();  //VIP期间支付订单数
                $list[$key]['order_amount'] = $order_model->where($order_where)->where($order_status_where)->sum('base_grand_total');//VIP期间支付金额
                
            }
            $result = array("total" => $total, "rows" => $list);

            return json($result);
        }
        //新增VIP用户数
        $vip_num = $this->zeeloolOperate->getVipUser();
        //复购VIP会员数
        $again_user_num['again_user_num'] = $this->zeeloolOperate->get_again_user_vip();
        //总VIP会员数
        $sum_vip_num = Db::connect('database.db_zeelool')->table('customer_entity')->where('is_vip',1)->count();
        //查询对应平台权限
        $magentoplatformarr = $this->magentoplatform->getAuthSite();
        foreach ($magentoplatformarr as $key=>$val){
            if(!in_array($val['name'],['zeelool','voogueme','nihao'])){
                unset($magentoplatformarr[$key]);
            }
        }
        $this->view->assign(compact('vip_num', 'again_user_num', 'sum_vip_num', 'magentoplatformarr'));
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
            $time_str2 = $params['time_str2'] ? $params['time_str2'] : '';

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
            $arr = Cache::get('Operatedatacenter_dataviews' . $order_platform . md5(serialize($time_str)));
            if ($arr) {
                // Cache::rm('Operatedatacenter_dataview' . $order_platform . md5(serialize($time_str)));
                $this->success('', '', $arr);
            }
            //活跃用户数
            $active_user_num = $model->getActiveUser(1, $time_str);
            //注册用户数
            $register_user_num = $model->getRegisterUser(1, $time_str);
            //复购用户数
            $again_user_num = $model->getAgainUser($time_str, 1);
            // $again_user_num = 0;
            //vip用户数
            $vip_user_num = $model->getVipUser(1, $time_str);
            //订单数
            $order_num = $model->getOrderNum(1, $time_str);
            //客单价
            $order_unit_price = $model->getOrderUnitPrice(1, $time_str);
            //销售额
            $sales_total_money = $model->getSalesTotalMoney(1, $time_str);
            //邮费
            $shipping_total_money = $model->getShippingTotalMoney(1, $time_str);

            $data = compact('order_num', 'order_unit_price', 'sales_total_money', 'shipping_total_money', 'active_user_num', 'register_user_num', 'again_user_num', 'vip_user_num');
            Cache::set('Operatedatacenter_dataviews' . $order_platform . md5(serialize($time_str)), $data, 7200);
            $this->success('', '', $data);
        }
        $this->view->assign(compact('order_num', 'order_unit_price', 'sales_total_money', 'shipping_total_money', 'active_user_num', 'register_user_num', 'again_user_num', 'vip_user_num'));
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
