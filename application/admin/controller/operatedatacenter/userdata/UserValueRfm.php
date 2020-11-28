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
            $cache_data = Cache::get('Operatedatacenter_userdata1'.$order_platform.md5(serialize('ajax_user_order_amount')));
            if(!$cache_data){
                $count = $this->getOrderAmountUserNum($order_platform);

                $count1 = $this->getOrderAmountUserNum($order_platform, 1);
                $count2 = $this->getOrderAmountUserNum($order_platform, 2);
                $count3 = $this->getOrderAmountUserNum($order_platform, 3);
                $count4 = $this->getOrderAmountUserNum($order_platform, 4);
                $count5 = $this->getOrderAmountUserNum($order_platform, 5);
                $count6 = $this->getOrderAmountUserNum($order_platform, 6);
                dump($count1);
                dump($count2);
                dump($count3);
                dump($count4);
                dump($count5);
                dump($count6);
                dump($count);exit;
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
    public function getOrderAmountUserNum($order_platform, $type = 0)
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
        dump($time_where);exit;
        $where['status'] = ['in', ['free_processing', 'processing', 'complete', 'paypal_reversed', 'payment_review', 'paypal_canceled_reversal']];
        $customer_ids = $web_model->table('customer_entity')->where($time_where)->column('entity_id');
        $where['customer_id'] = ['in',$customer_ids];
        dump($customer_ids);exit;
        switch ($type) {
            case 1:
                $order_customerids = $order_model->where($where)->group('customer_id')->having('sum(base_grand_total)>=0 and sum(base_grand_total)<40')->column('customer_id');
                break;
            case 2:
                $order_customerids = $order_model->where($where)->group('customer_id')->having('sum(base_grand_total)>=40 and sum(base_grand_total)<80')->column('customer_id');
                break;
            case 3:
                $order_customerids = $order_model->where($where)->group('customer_id')->having('sum(base_grand_total)>=80 and sum(base_grand_total)<150')->column('customer_id');
                break;
            case 4:
                $order_customerids = $order_model->where($where)->group('customer_id')->having('sum(base_grand_total)>=150 and sum(base_grand_total)<200')->column('customer_id');
                break;
            case 5:
                $order_customerids = $order_model->where($where)->group('customer_id')->having('sum(base_grand_total)>=200 and sum(base_grand_total)<300')->column('customer_id');
                break;
            case 6:
                $order_customerids = $order_model->where($where)->group('customer_id')->having('sum(base_grand_total)>=300')->column('customer_id');
                break;
            default:
                $order_customerids = $customer_ids;
                break;
        }
        $count = count($order_customerids);
        return $count;
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
                $count = $this->getOrderNumUserNum($order_platform);
                $count1 = $this->getOrderNumUserNum($order_platform, 1);
                $count2 = $this->getOrderNumUserNum($order_platform, 2);
                $count3 = $this->getOrderNumUserNum($order_platform, 3);
                $count4 = $this->getOrderNumUserNum($order_platform, 4);
                $count5 = $this->getOrderNumUserNum($order_platform, 5);
                $count6 = $this->getOrderNumUserNum($order_platform, 6);
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
    public function getOrderNumUserNum($order_platform, $type=0)
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
        $customer_ids = $web_model->table('customer_entity')->where($time_where)->column('entity_id');
        $where['customer_id'] = ['in',$customer_ids];
        switch ($type) {
            case 1:
                $order_customerids = $order_model->where($where)->group('customer_id')->having('count(entity_id)=0')->column('customer_id');
                break;
            case 2:
                $order_customerids = $order_model->where($where)->group('customer_id')->having('count(entity_id)=1')->column('customer_id');
                break;
            case 3:
                $order_customerids = $order_model->where($where)->group('customer_id')->having('count(entity_id)=2')->column('customer_id');
                break;
            case 4:
                $order_customerids = $order_model->where($where)->group('customer_id')->having('count(entity_id)=3')->column('customer_id');
                break;
            case 5:
                $order_customerids = $order_model->where($where)->group('customer_id')->having('count(entity_id)=4')->column('customer_id');
                break;
            case 6:
                $order_customerids = $order_model->where($where)->group('customer_id')->having('count(entity_id)>=5')->column('customer_id');
                break;
            default:
                $order_customerids = $customer_ids;
                break;
        }
        $count = count($order_customerids);
        return $count;
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
            $cache_data = Cache::get('Operatedatacenter_userdata1'.$order_platform.md5(serialize('ajax_user_shopping_near_days')));
            if(!$cache_data){
                $count = $this->getUserNearDays($order_platform);
                $count1 = $this->getUserNearDays($order_platform, 1);
                $count2 = $this->getUserNearDays($order_platform, 2);
                $count3 = $this->getUserNearDays($order_platform, 3);
                $count4 = $this->getUserNearDays($order_platform, 4);
                $count5 = $this->getUserNearDays($order_platform, 5);
                $count6 = $this->getUserNearDays($order_platform, 6);
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
    public function getUserNearDays($order_platform, $type=0)
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
        $customer_ids = $web_model->table('customer_entity')->where($time_where)->column('entity_id');
        $where['customer_id'] = ['in',$customer_ids];
        switch ($type) {
            case 1:
                $order_customerids = $order_model->where($where)->group('customer_id')->having('to_days(now()) - to_days(max(created_at))>=0 and to_days(now()) - to_days(max(created_at))<14')->column('customer_id');
                break;
            case 2:
                $order_customerids = $order_model->where($where)->group('customer_id')->having('to_days(now()) - to_days(max(created_at))>=14 and to_days(now()) - to_days(max(created_at))<30')->column('customer_id');
                break;
            case 3:
                $order_customerids = $order_model->where($where)->group('customer_id')->having('to_days(now()) - to_days(max(created_at))>=30 and to_days(now()) - to_days(max(created_at))<60')->column('customer_id');
                break;
            case 4:
                $order_customerids = $order_model->where($where)->group('customer_id')->having('to_days(now()) - to_days(max(created_at))>=60 and to_days(now()) - to_days(max(created_at))<90')->column('customer_id');
                break;
            case 5:
                $order_customerids = $order_model->where($where)->group('customer_id')->having('to_days(now()) - to_days(max(created_at))>=90 and to_days(now()) - to_days(max(created_at))<360')->column('customer_id');
                break;
            case 6:
                $order_customerids = $order_model->where($where)->group('customer_id')->having('to_days(now()) - to_days(max(created_at))>=360')->column('customer_id');
                break;
            default:
                $order_customerids = $customer_ids;
                break;
        }
        $count = count($order_customerids);
        return $count;
    }
}
