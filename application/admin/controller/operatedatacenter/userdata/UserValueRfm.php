<?php

namespace app\admin\controller\operatedatacenter\userdata;

use app\common\controller\Backend;
use think\Cache;
use think\Controller;
use think\Db;
use think\Request;

class UserValueRfm extends Backend
{
    public function _initialize()
    {
        parent::_initialize();
        $this->zeelool = new \app\admin\model\order\order\Zeelool();
        $this->voogueme = new \app\admin\model\order\order\Voogueme();
        $this->nihao = new \app\admin\model\order\order\Nihao();
        $this->zeeloolOperate = new \app\admin\model\operatedatacenter\Zeelool;
        $this->vooguemeOperate = new \app\admin\model\operatedatacenter\Voogueme;
        $this->nihaoOperate = new \app\admin\model\operatedatacenter\Nihao;
        $this->magentoplatform = new \app\admin\model\platformmanage\MagentoPlatform();
    }

    /**
     * 用户平台贡献分布
     *
     * @return \think\Response
     */
    public function user_contribution_distribution()
    {
        //查询对应平台权限
        $magentoplatformarr = $this->magentoplatform->getAuthSite();
        foreach ($magentoplatformarr as $key => $val) {
            if (!in_array($val['name'], ['zeelool', 'voogueme', 'nihao'])) {
                unset($magentoplatformarr[$key]);
            }
        }
        $this->assign('magentoplatformarr', $magentoplatformarr);
        return $this->view->fetch();
    }

    /*
     * ajax获取用户消费金额分布
     * */
    public function ajax_user_order_amount()
    {
        if ($this->request->isAjax()) {
            $params = $this->request->param();
            $order_platform = $params['order_platform'];
            $cache_data = Cache::get('Operatedatacenter_userdata'.$order_platform.md5(serialize('ajax_user_order_amount')));
            if(!$cache_data){
                $result = $this->getOrderAmountUserNum($order_platform);
                $count = $result['count'];
                $count1 = $result['data'][0]['a'];
                $count2 = $result['data'][0]['b'];
                $count3 = $result['data'][0]['c'];
                $count4 = $result['data'][0]['d'];
                $count5 = $result['data'][0]['e'];
                $count6 = $result['data'][0]['f'];
                $arr = array(
                    'data'=>array($count1, $count2, $count3, $count4, $count5, $count6),
                    'count'=>$count
                );
                Cache::set('Operatedatacenter_userdata' . $order_platform . md5(serialize('ajax_user_order_amount')), $arr, 36000);
            }else{
                $arr = $cache_data;
            }
            $data = $arr['data'];
            $json['firtColumnName'] = ['0-40', '40-80', '80-150', '150-200', '200-300', '300+'];
            $json['columnData'] = [[
                'type' => 'bar',
                'barWidth' => '40%',
                'data' => $data,
                'name' => $arr['count'],
                'itemStyle' => [
                    'normal' => [
                        'label' => [
                            'show' => true,
                            'position' => 'right',
                            'formatter'=>"{c}"."人",
                            'textStyle'=>[
                                'color'=> 'black'
                            ],
                        ],
                    ]
                ]
            ]];
            return json(['code' => 1, 'data' => $json]);
        }
    }
    /*
     * 获取金额分布人数
     * type  1:[0-40)  2:[40-80)  3:[80-150)   4:[150-200)    5:[200-300)   6:[300,10000000)
    */
    public function getOrderAmountUserNum($order_platform)
    {
        if($order_platform == 2){
            $web_model = Db::connect('database.db_voogueme');
            $order_model = $this->voogueme;
        }elseif($order_platform == 3){
            $web_model = Db::connect('database.db_nihao');
            $order_model = $this->nihao;
        }else{
            $web_model = Db::connect('database.db_zeelool');
            $order_model = $this->zeelool;
        }
        $web_model->table('customer_entity')->query("set time_zone='+8:00'");
        $today = date('Y-m-d');
        $start = date('Y-m-d', strtotime("$today -12 month"));
        $end = date('Y-m-d 23:59:59', strtotime($today));
        $time_where['created_at'] = ['between', [$start, $end]];
        $where['status'] = ['in', ['free_processing', 'processing', 'complete', 'paypal_reversed', 'payment_review', 'paypal_canceled_reversal']];
        $where['order_type'] = 1;
        $count = $web_model->table('customer_entity')->where($time_where)->count('entity_id');

        $sql1 = $web_model->table('customer_entity')->where($time_where)->field('entity_id')->buildSql();
        $arr_where = [];
        $arr_where[] = ['exp', Db::raw("customer_id in " . $sql1)];

        $sql2 = $order_model->alias('t1')->field('sum( base_grand_total ) AS total')->where($where)->where($arr_where)->group('customer_id')->buildSql();

        $order_customer_count = $web_model->table([$sql2=>'t2'])->field('sum( IF ( total >= 300, 1, 0 ) ) AS f,sum( IF ( total >= 200 AND total < 300, 1, 0 ) ) AS e,sum( IF ( total >= 150 AND total < 200, 1, 0 ) ) AS d,sum( IF ( total >= 80 AND total < 150, 1, 0 ) ) AS c,sum( IF ( total >= 40 AND total < 80, 1, 0 ) ) AS b')->select();

        $order_customer_count[0]['a'] = $count-$order_customer_count[0]['b']-$order_customer_count[0]['c']-$order_customer_count[0]['d']-$order_customer_count[0]['e']-$order_customer_count[0]['f'];

        $arr = array(
            'count'=>$count,
            'data'=>$order_customer_count,
        );
        return $arr;
    }
    /**
     * 用户总消费次数分布
     *
     * @return \think\Response
     */
    public function user_shopping_num_distribution()
    {
        //查询对应平台权限
        $magentoplatformarr = $this->magentoplatform->getAuthSite();
        foreach ($magentoplatformarr as $key => $val) {
            if (!in_array($val['name'], ['zeelool', 'voogueme', 'nihao'])) {
                unset($magentoplatformarr[$key]);
            }
        }
        $this->assign('magentoplatformarr', $magentoplatformarr);
        return $this->view->fetch();
    }
    /*
     * ajax获取用户消费次数分布
     * */
    public function ajax_user_order_num()
    {
        if ($this->request->isAjax()) {
            $params = $this->request->param();
            $order_platform = $params['order_platform'];
            $cache_data = Cache::get('Operatedatacenter_userdata'.$order_platform.md5(serialize('ajax_user_order_num')));
            if(!$cache_data){
                $result = $this->getOrderNumUserNum($order_platform);
                $count = $result['count'];
                $count1 = $result['data'][0]['a'];
                $count2 = $result['data'][0]['b'];
                $count3 = $result['data'][0]['c'];
                $count4 = $result['data'][0]['d'];
                $count5 = $result['data'][0]['e'];
                $count6 = $result['data'][0]['f'];
                $arr = array(
                    'data'=>array($count1, $count2, $count3, $count4, $count5, $count6),
                    'count'=>$count
                );
                Cache::set('Operatedatacenter_userdata' . $order_platform . md5(serialize('ajax_user_order_num')), $arr, 36000);
            }else{
                $arr = $cache_data;
            }
            $data = $arr['data'];
            $json['firtColumnName'] = ['0', '1', '2', '3', '4', '5+'];
            $json['columnData'] = [[
                'type' => 'bar',
                'barWidth' => '40%',
                'data' => $data,
                'name' => $arr['count'],
                'itemStyle' => [
                    'normal' => [
                        'label' => [
                            'show' => true,
                            'position' => 'right',
                            'formatter'=>"{c}"."人",
                            'textStyle'=>[
                                'color'=> 'black'
                            ],
                        ],
                    ]
                ]
            ]];
            return json(['code' => 1, 'data' => $json]);
        }
    }
    /*
     * 获取购买用户消费次数分布人数
     * type  1:0  2:1  3:2   4:3    5:4  6:5+
    */
    public function getOrderNumUserNum($order_platform)
    {
        if($order_platform == 2){
            $web_model = Db::connect('database.db_voogueme');
            $order_model = $this->voogueme;
        }elseif($order_platform == 3){
            $web_model = Db::connect('database.db_nihao');
            $order_model = $this->nihao;
        }else{
            $web_model = Db::connect('database.db_zeelool');
            $order_model = $this->zeelool;
        }
        $web_model->table('customer_entity')->query("set time_zone='+8:00'");
        $today = date('Y-m-d');
        $start = date('Y-m-d', strtotime("$today -12 month"));
        $end = date('Y-m-d 23:59:59', strtotime($today));
        $time_where['created_at'] = ['between', [$start, $end]];
        $where['status'] = ['in', ['free_processing', 'processing', 'complete', 'paypal_reversed', 'payment_review', 'paypal_canceled_reversal']];
        $where['order_type'] = 1;
        $count = $web_model->table('customer_entity')->where($time_where)->count();

        $sql1 = $web_model->table('customer_entity')->where($time_where)->field('entity_id')->buildSql();
        $arr_where = [];
        $arr_where[] = ['exp', Db::raw("customer_id in " . $sql1)];

        $sql2 = $order_model->alias('t1')->field('count( customer_id ) AS total')->where($where)->where($arr_where)->group('customer_id')->buildSql();

        $order_customer_count = $web_model->table([$sql2=>'t2'])->field('sum( IF ( total >= 5, 1, 0 ) ) AS f,sum( IF ( total = 4, 1, 0 ) ) AS e,sum( IF ( total = 3, 1, 0 ) ) AS d,sum( IF ( total = 2, 1, 0 ) ) AS c,sum( IF ( total = 1, 1, 0 ) ) AS b')->select();

        $order_customer_count[0]['a'] = $count-$order_customer_count[0]['b']-$order_customer_count[0]['c']-$order_customer_count[0]['d']-$order_customer_count[0]['e']-$order_customer_count[0]['f'];

        $arr = array(
            'count'=>$count,
            'data'=>$order_customer_count,
        );
        return $arr;
    }
    /**
     * 消费临近天数
     *
     * @return \think\Response
     */
    public function user_shopping_near_days()
    {
        //查询对应平台权限
        $magentoplatformarr = $this->magentoplatform->getAuthSite();
        foreach ($magentoplatformarr as $key => $val) {
            if (!in_array($val['name'], ['zeelool', 'voogueme', 'nihao'])) {
                unset($magentoplatformarr[$key]);
            }
        }
        $this->assign('magentoplatformarr', $magentoplatformarr);
        return $this->view->fetch();
    }
    /*
     * ajax获取用户消费临近天数分布
     * */
    public function ajax_user_shopping_near_days()
    {
        if ($this->request->isAjax()) {
            $params = $this->request->param();
            $order_platform = $params['order_platform'];
            $cache_data = Cache::get('Operatedatacenter_userdata'.$order_platform.md5(serialize('ajax_user_shopping_near_days')));
            if(!$cache_data){
                $result = $this->getUserNearDays($order_platform);
                $count = $result['count'];
                $count1 = $result['data'][0]['a'];
                $count2 = $result['data'][0]['b'];
                $count3 = $result['data'][0]['c'];
                $count4 = $result['data'][0]['d'];
                $count5 = $result['data'][0]['e'];
                $count6 = $result['data'][0]['f'];
                $arr = array(
                    'data'=>array($count1, $count2, $count3, $count4, $count5, $count6),
                    'count'=>$count
                );
                Cache::set('Operatedatacenter_userdata' . $order_platform . md5(serialize('ajax_user_shopping_near_days')), $arr, 36000);
            }else{
                $arr = $cache_data;
            }
            $data = $arr['data'];
            $json['firtColumnName'] = ['0-14', '14-30', '30-60', '60-90', '90-360', '360+'];
            $json['columnData'] = [[
                'type' => 'bar',
                'barWidth' => '40%',
                'data' => $data,
                'name' => $arr['count'],
                'itemStyle' => [
                    'normal' => [
                        'label' => [
                            'show' => true,
                            'position' => 'right',
                            'formatter'=>"{c}"."人",
                            'textStyle'=>[
                                'color'=> 'black'
                            ],
                        ],
                    ]
                ]
            ]];
            return json(['code' => 1, 'data' => $json]);
        }
    }
    /*
     * 获取购买用户消费临近天数分布人数
     * type  1:0-14  2:14-30  3:30-60   4:60-90    5:90-360  6：360+
    */
    public function getUserNearDays($order_platform)
    {
        if($order_platform == 2){
            $web_model = Db::connect('database.db_voogueme');
        }elseif($order_platform == 3){
            $web_model = Db::connect('database.db_nihao');
        }else{
            $web_model = Db::connect('database.db_zeelool');
        }
        $today = date('Y-m-d');
        $start = date('Y-m-d', strtotime("$today -12 month")-8*3600);
        $end = date('Y-m-d 23:59:59', strtotime($today)-8*3600);
        $time_where['created_at'] = ['between', [$start, $end]];
        $where['status'] = ['in', ['free_processing', 'processing', 'complete', 'paypal_reversed', 'payment_review', 'paypal_canceled_reversal']];
        $where['order_type'] = 1;
        $count = $web_model->table('customer_entity')->where($time_where)->count();

        $sql1 = $web_model->table('customer_entity')->where($time_where)->field('entity_id')->buildSql();
        $arr_where = [];
        $arr_where[] = ['exp', Db::raw("customer_id in " . $sql1)];

        $sql2 = $web_model->table('sales_flat_order')->alias('t1')->field('to_days(now()) - to_days(max(updated_at)) AS total')->where($where)->where($arr_where)->group('customer_id')->buildSql();

        $order_customer_count = $web_model->table([$sql2=>'t2'])->field('sum( IF ( total >= 90 and total<360, 1, 0 ) ) AS e,sum( IF ( total >= 60 and total<90, 1, 0 ) ) AS d,sum( IF ( total >= 30 and total<60, 1, 0 ) ) AS c,sum( IF ( total >= 14 and total<30, 1, 0 ) ) AS b,sum( IF ( total >= 0 and total<14, 1, 0 ) ) AS a')->select();

        $order_customer_count[0]['f'] = $count-$order_customer_count[0]['a']-$order_customer_count[0]['b']-$order_customer_count[0]['c']-$order_customer_count[0]['d']-$order_customer_count[0]['e'];
        $arr = array(
            'count'=>$count,
            'data'=>$order_customer_count,
        );
        return $arr;
    }
}
