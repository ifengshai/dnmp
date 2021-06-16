<?php

namespace app\admin\controller\operatedatacenter\appdata;

use app\common\controller\Backend;
use app\enum\Store;
use think\Db;

class Dashboard extends Backend
{
    protected $noNeedRight = ['*'];

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

    public function index()
    {
        //查询对应平台权限
        $magentoplatformarr = $this->magentoplatform->getAuthSite();
        foreach ($magentoplatformarr as $key => $val) {
            if (!in_array($val['name'], ['zeelool', 'voogueme'])) {
                unset($magentoplatformarr[$key]);
            }
        }
        $platform_arr = [Store::IOS => 'ios', Store::ANDROID => 'android', 999 => '全部'];

        $site = input('site');
        $platform = input('platform');
        $date = input('date');
        $compare_date = input('compare_date');
        //默认最近一周
        if (!$date) {
            $date = date('Y-m-d', time() - 7 * 86400) . ' - ' . date('Y-m-d');
        }

        if ($this->request->isAjax()) {
            if (!in_array($site, array_column($magentoplatformarr, 'id'))) {
                return ['msg' => '平台未知'];
            }
            if (!isset($platform_arr[$platform])) {
                return ['msg' => '客户端未知'];
            }

            $time = explode(' - ', $date);
            $start_time = date('Y-m-d', strtotime($time[0]));
            $end_time = date('Y-m-d', strtotime($time[1]));

            // 当日获取数据
            $current = $this->getDashboardData($site, $platform, $start_time, $end_time);

            // 比较数据
            $compare = [];
            if ($compare_date) {
                $compare_time = explode(' - ', $compare_date);
                $compare_start_time = date('Y-m-d', strtotime($compare_time[0]));
                $compare_end_time = date('Y-m-d', strtotime($compare_time[1]));
                $compare = $this->getDashboardData($site, $platform, $compare_start_time, $compare_end_time);
            }

            return json([
                'code' => 1,
                'data' => [
                    'current' => $current,
                    'compare' => $compare,
                ]
            ]);
        }
        $this->view->assign('magentoplatformarr', $magentoplatformarr);
        $this->view->assign('platform_arr', $platform_arr);
        $this->view->assign('date', $date);
        return $this->view->fetch();
    }

    protected function getDashboardData($site, $platform, $start_time, $end_time)
    {
        if ($platform == 999) {
            $platforms = [Store::IOS, Store::ANDROID];
        } else {
            $platforms = [$platform];
        }

        $data = [];
        foreach ($platforms as $plat) {
            // 获取订单数据
            $list = $this->getOrderData($site, $plat, $start_time, $end_time);
            $data['order_list'] = self::arraySum($data['order_list'], $list) ?: [];

            // 获取GA数据
            $list = $this->getGaData($site, $plat, $start_time, $end_time);
            $data['ga_list'] = self::arraySum($data['ga_list'], $list) ?: [];

            // 获取googleads数据
//            $list = $this->getAdData($site, $plat, $start_time, $end_time);
//            $data['ad_list'] = self::arraySum($data['ad_list'], $list) ?: [];
        }
        $data['ga_list'] = array_values($data['ga_list']);

        $data['order_money'] = round(array_sum(array_column($data['order_list'], 'order_money')), 2);
        $data['order_num'] = array_sum(array_column($data['order_list'], 'order_num'));

        $data['ga_users'] = array_sum(array_column($data['ga_list'], 'activeUsers'));
        $data['ga_sessions'] = array_sum(array_column($data['ga_list'], 'sessions'));
        $data['first_open'] = array_sum(array_column($data['ga_list'], 'first_open'));
        $data['app_remove'] = array_sum(array_column($data['ga_list'], 'app_remove'));

        // 转化率
        $data['conversion_session'] = $data['ga_sessions'] > 0 ? round($data['order_num'] / $data['ga_sessions'] * 100, 2) : 100;
        $data['conversion_user'] = $data['ga_users'] > 0 ? round($data['order_num'] / $data['ga_users'] * 100, 2) : 100;
        $data['money_per_user'] = $data['order_num'] > 0 ? round($data['order_money'] / $data['order_num'], 2) : 0;
        return $data;
    }

    protected function getGaData($site, $platform, $start_time, $end_time)
    {
        $ga = new \app\service\google\AnalyticsData($site, $platform);
        $data = $this->initArray($start_time, $end_time, ['first_open', 'app_remove', 'sessions', 'activeUsers']);
        // 选择时间的会话数与用户数
        $result = $ga->getReport($start_time, $end_time, ['sessions', 'activeUsers'], ['date']);
        foreach ($result as $key => $item) {
            $date = date('Y-m-d', strtotime($key));
            $data[$date] = array_merge($item, ['date' => $date]);
        }
        // 选择时间的首次打开数
        $result = $ga->getReport($start_time, $end_time, ['eventCount'], ['date', 'eventName']);
        foreach ($result as $key => $item) {
            $date = date('Y-m-d', strtotime($key));
            $data[$date]['date'] = $date;
            $data[$date]['first_open'] = $item['first_open']['eventCount'] ?? 0;
            $data[$date]['app_remove'] = $item['app_remove']['eventCount'] ?? 0;
        }
        ksort($data);

        return $data;
    }

    protected function getAdData($site, $platform, $start_time, $end_time)
    {
        $ad = new \app\service\google\Ads($site, $platform);
        $data = [];
        $result = $ad->getReport($start_time, $end_time);

        return $data;
    }

    protected function getOrderData($site, $platform, $start_time, $end_time)
    {
        $db = Db::connect('database.db_mojing_order');
        $db->table('fa_order')->query("set time_zone='+8:00'");
        $data = $db->table('fa_order')
            ->where([
                'site' => $site,
                'store_id' => $platform,
                'status' => [
                    'in',
                    ['free_processing', 'processing', 'paypal_reversed', 'paypal_canceled_reversal', 'complete', 'delivered','delivery']
                ],
                'created_at' => ['between', [strtotime($start_time), strtotime($end_time) + 86400]]
            ])
            ->field("DATE_FORMAT(FROM_UNIXTIME(created_at),'%Y-%m-%d') as date, round(sum(base_grand_total), 2) as order_money, count(*) as order_num")
            ->group('date')
            ->select();



        return array_combine(array_column($data, 'date'), $data);
    }

    protected function initArray($start_time, $end_time, $attributes)
    {
        $data = [];
        for ($time = strtotime($start_time); $time <= strtotime($end_time); $time+= 86400) {
            $date = date('Y-m-d', $time);
            $data[$date]['date'] = $date;
            foreach ($attributes as $attribute) {
                $data[$date][$attribute] = 0;
            }
        }
        return $data;
    }

    protected function arraySum($a, $b)
    {
        $args = func_get_args();
        $res = array_shift($args);
        while (!empty($args)) {
            foreach (array_shift($args) as $k => $v) {
                if (is_array($v) && isset($res[$k]) && is_array($res[$k])) {
                    $res[$k] = static::arraySum($res[$k], $v);
                } elseif (is_numeric($v)) {
                    $res[$k] = intval($res[$k]) + intval($v);
                } else {
                    $res[$k] = $v;
                }
            }
        }
        return $res;
    }
}
