<?php

namespace app\admin\controller\operatedatacenter\userdata;

use app\admin\model\platformManage\MagentoPlatform;
use app\common\controller\Backend;
use think\Cache;
use think\Db;
use think\Request;

class UserDataView extends Backend
{

    public function _initialize()
    {
        parent::_initialize();

        //每日的数据
        $this->zeelool = new \app\admin\model\order\order\Zeelool;
        $this->voogueme = new \app\admin\model\order\order\Voogueme;
        $this->nihao = new \app\admin\model\order\order\Nihao;
        $this->zeeloolOperate = new \app\admin\model\operatedatacenter\Zeelool;
        $this->vooguemeOperate = new \app\admin\model\operatedatacenter\Voogueme;
        $this->nihaoOperate = new \app\admin\model\operatedatacenter\Nihao;
        $this->datacenterday = new \app\admin\model\operatedatacenter\Datacenter;
        $this->magentoplatform = new \app\admin\model\platformmanage\MagentoPlatform;
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
        foreach ($magentoplatformarr as $key => $val) {
            if (!in_array($val, ['zeelool', 'voogueme', 'nihao'])) {
                unset($magentoplatformarr[$key]);
            }
        }
        //默认进入页面是z站的数据
        $arr = Cache::get('Operatedatacenter_userdataview' . 1 . md5(serialize('index')));
        if ($arr) {
            $this->view->assign($arr);
        }else{
            // 活跃用户数
            $active_user_num = $this->zeeloolOperate->getActiveUser();
            //注册用户数
            $register_user_num = $this->zeeloolOperate->getRegisterUser();
            $time_arr = date('Y-m-d 00:00:00', strtotime('-6 day')) . ' - ' . date('Y-m-d H:i:s', time());
            //复购用户数
            $again_user_num = $this->zeeloolOperate->getAgainUser($time_arr, 0);
            $data = compact(  'active_user_num', 'register_user_num', 'again_user_num',  'magentoplatformarr');
            Cache::set('Operatedatacenter_userdataview' . 1 . md5(serialize('index')), $data, 7200);
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
            //时间
            $time_str = $params['time_str'];
            $time_str2 = $params['time_str2'];

            $arr = Cache::get('Operatedatacenter_userdataview' . $order_platform .$time_str.$time_str2. md5(serialize('index')));
            if ($arr) {
                $data = $arr;
            }else{
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
                //活跃用户数
                $active_user_num = $model->getActiveUser($time_str,$time_str2);
                //注册用户数
                $register_user_num = $model->getRegisterUser($time_str,$time_str2);
                //复购用户数
                $again_user_num = $model->getAgainUser($time_str,$time_str2);

                $data = compact('active_user_num', 'register_user_num', 'again_user_num');
                Cache::set('Operatedatacenter_userdataview'  . $order_platform .$time_str.$time_str2. md5(serialize('index')), $data, 7200);
            }
            $this->success('', '', $data);
        }
    }

    /*
     * 活跃用户折线图
     */
    public function active_user_trend()
    {
        if ($this->request->isAjax()) {
            $params = $this->request->param();
            $order_platform = $params['order_platform'];
            $time_str = $params['time_str'];
            $arr = Cache::get('Operatedatacenter_userdataview' . $order_platform . $time_str.md5(serialize('active_user_trend')));
            if ($arr) {
                $date_arr = $arr;
            }else{
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
                if ($order_platform) {
                    $where['site'] = $order_platform;
                }
                if ($time_str) {
                    $createat = explode(' ', $time_str);
                    $where['day_date'] = ['between', [$createat[0], $createat[3]]];
                } else {
                    $start = date('Y-m-d', strtotime('-6 day'));
                    $end = date('Y-m-d');
                    $where['day_date'] = ['between', [$start, $end]];
                }
                $arr = $model->where($where)->column('day_date', 'active_user_num');
                $date_arr = $arr;
                Cache::set('Operatedatacenter_userdataview'  . $order_platform .$time_str. md5(serialize('active_user_trend')), $date_arr, 7200);
            }
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
            return json(['code' => 1, 'data' => $json]);
        }
    }


    /**
     * 新老用户购买转化对比
     *
     * @Description
     * @author wpl
     * @since 2020/10/14 15:02:23 
     * @return void
     */
    public function new_old_change_line()
    {
        if ($this->request->isAjax()) {
            $params = $this->request->param();
            $order_platform = $params['order_platform'] ? $params['order_platform'] : 1;
            $time_str = $params['time_str'];
            $arr = Cache::get('Operatedatacenter_userdataview' . $order_platform . $time_str.md5(serialize('new_old_change_line')));
            if ($arr) {
                $data = $arr;
            }else{
                if($order_platform == 2){
                    $order_model = $this->voogueme;
                    $web_model = Db::connect('database.db_voogueme');
                }elseif($order_platform == 3){
                    $order_model = $this->nihao;
                    $web_model = Db::connect('database.db_nihao');
                }else{
                    $order_model = $this->zeelool;
                    $web_model = Db::connect('database.db_zeelool');
                }
                $web_model->table('customer_entity')->query("set time_zone='+8:00'");
                if ($time_str) {
                    $createat = explode(' ', $time_str);
                    $start = $createat[0];
                    $end = $createat[3];
                } else{
                    $start = date('Y-m-d', strtotime('-6 day'));
                    $end   = date('Y-m-d');
                }
                $time_arr = $this->getDateFromRange($start,$end);
                $new_arr = array();
                $active_arr = array();
                foreach ($time_arr as $val){
                    $order_where['status'] = ['in', ['free_processing', 'processing', 'complete', 'paypal_reversed', 'payment_review', 'paypal_canceled_reversal']];
                    $start_time = $val;
                    $end_time = $val.' 23:59:59';
                    $create_where['created_at'] = $update_where['updated_at'] = ['between',[$start_time,$end_time]];
                    //当天注册用户数
                    $register_userids = $web_model->table('customer_entity')->where($create_where)->column('entity_id');
                    $register_num = count($register_userids);
                    //当天注册用户在当天下单的用户数
                    $order_user_count1 = 0;
                    foreach($register_userids as $register_userid){
                        //判断当前用户在当天是否下单
                        $order = $order_model->where($create_where)->where($order_where)->where('customer_id',$register_userid)->value('entity_id');
                        if($order){
                            $order_user_count1++;
                        }
                    }
                    $new_arr[] = $register_num ? round($order_user_count1/$register_num*100,0) : 0;

                    //当天更新用户数
                    $update_userids = $web_model->table('customer_entity')->where($update_where)->column('entity_id');
                    $update_num = count($update_userids);
                    //当天活跃更新用户数在当天是否下单
                    $order_user_count2 = 0;
                    foreach ($update_userids as $update_userid){
                        //判断活跃用户在当天下单的用户数
                        $order = $order_model->where($create_where)->where($order_where)->where('customer_id',$update_userid)->value('entity_id');
                        if($order){
                            $order_user_count2++;
                        }
                    }
                    $active_arr[] = $update_num ? round($order_user_count2/$update_num*100,0) : 0;
                }
                $data = array(
                    'time'=>$time_arr,
                    'new'=>$new_arr,
                    'active'=>$active_arr,
                );
                Cache::set('Operatedatacenter_userdataview'  . $order_platform .$time_str. md5(serialize('new_old_change_line')), $data, 7200);
            }
            $arr['xdata'] = $data['time'];
            $arr['ydata']['one'] = $data['new'] ? $data['new'] : '无';
            $arr['ydata']['two'] = $data['active'] ? $data['active'] : '无';

            $json['xColumnName'] = $arr['xdata'];
            $json['columnData'] = [
                [
                    'type' => 'line',
                    'data' => $arr['ydata']['one'],
                    'name' => '新用户',
                    'smooth' => true //平滑曲线
                ],
                [
                    'type' => 'line',
                    'data' => $arr['ydata']['two'],
                    'name' => '活跃用户',
                    'smooth' => true //平滑曲线
                ],
            ];

            return json(['code' => 1, 'data' => $json]);
        }
    }
    /**
     * 用户类型分布饼图
     *
     * @Description
     * @author wpl
     * @since 2020/10/14 15:02:23 
     * @return void
     */
    public function user_type_pie()
    {
        if ($this->request->isAjax()) {
            $params = $this->request->param();
            $order_platform = $params['order_platform'] ? $params['order_platform'] : 1;
            if ($params['time_str']) {
                $createat = explode(' ', $params['time_str']);
                $map_where['created_at'] = ['between', [$createat[0], $createat[3].' 23:59:59']];
            } else{
                $start = date('Y-m-d', strtotime('-6 day'));
                $end   = date('Y-m-d 23:59:59');
                $map_where['created_at'] = ['between', [$start,$end]];
            }
            if($order_platform == 2){
                $web_model = Db::connect('database.db_voogueme');
            }elseif($order_platform == 3){
                $web_model = Db::connect('database.db_nihao');
            }else{
                $web_model = Db::connect('database.db_zeelool');
            }
            $web_model->table('customer_entity')->query("set time_zone='+8:00'");
            $data = array(
                array(
                    'name'=>'普通用户',
                    'value'=>$web_model->table('customer_entity')->where($map_where)->where('group_id',1)->count(),  //普通用户人数
                ),
                array(
                    'name'=>'VIP用户',
                    'value'=>$web_model->table('customer_entity')->where($map_where)->where('group_id',4)->count(),    //vip用户人数
                ),
                array(
                    'name'=>'批发',
                    'value'=>$web_model->table('customer_entity')->where($map_where)->where('group_id',2)->count(),  //批发用户人数
                ),
            );
            //总人数
            $count = $web_model->table('customer_entity')->where($map_where)->count();
            $column = ['普通用户','VIP用户','批发'];
            $json['column'] = $column;
            $json['columnData'] = $data;
            $json['total'] = $count;

            return json(['code' => 1, 'data' => $json]);
        }
    }
    /**
     * 不同用户类型销售额贡献饼图
     *
     * @Description
     * @author wpl
     * @since 2020/10/14 15:02:23 
     * @return void
     */
    public function user_order_pie()
    {
        if ($this->request->isAjax()) {
            $params = $this->request->param();
            $order_platform = $params['order_platform'] ? $params['order_platform'] : 1;
            if ($params['time_str']) {
                $createat = explode(' ', $params['time_str']);
                $map_where['o.created_at'] =$order_where['created_at'] = ['between', [$createat[0], $createat[3].' 23:59:59']];
            } else{
                $start = date('Y-m-d', strtotime('-6 day'));
                $end   = date('Y-m-d 23:59:59');
                $map_where['o.created_at'] = ['between', [$start,$end]];
            }
            $map_where['o.status'] = $order_where['status'] = ['in', ['free_processing', 'processing', 'complete', 'paypal_reversed', 'payment_review', 'paypal_canceled_reversal']];
            if($order_platform == 2){
                $model = $this->voogueme;
            }elseif($order_platform == 3){
                $model = $this->nihao;
            }else{
                $model = $this->zeelool;
            }
            $count = $model->where($order_where)->count();  //总订单
            $order_amount = $model->where($order_where)->sum('base_grand_total'); //总订单金额
            $count1 = $model->alias('o')->join('customer_entity c ','o.customer_id=c.entity_id','left')->where($map_where)->where('c.group_id',1)->count();  //普通用户人数
            $count2 = $model->alias('o')->join('customer_entity c ','o.customer_id=c.entity_id','left')->where($map_where)->where('c.group_id',4)->count();    //vip用户人数
            $count3 = $model->alias('o')->join('customer_entity c ','o.customer_id=c.entity_id','left')->where($map_where)->where('c.group_id',2)->count();  //批发用户人数
            $count4 = $count-$count1-$count2-$count3;
            $data = array(
                array(
                    'name'=>'普通用户',
                    'value'=>$count1
                ),
                array(
                    'name'=>'VIP用户',
                    'value'=>$count2
                ),
                array(
                    'name'=>'游客',
                    'value'=>$count4
                ),
                array(
                    'name'=>'批发',
                    'value'=>$count3
                ),
            );
            $column = ['普通用户','VIP用户','游客','批发'];
            $json['column'] = $column;
            $json['columnData'] = $data;
            $json['total'] = $order_amount;

            return json(['code' => 1, 'data' => $json]);
        }
    }
}
