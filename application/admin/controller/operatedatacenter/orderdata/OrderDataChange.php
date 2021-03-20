<?php

namespace app\admin\controller\operatedatacenter\orderdata;

use app\common\controller\Backend;
use think\Db;

class OrderDataChange extends Backend
{
    public function _initialize()
    {
        parent::_initialize();
        $this->zeeloolOperate  = new \app\admin\model\operatedatacenter\Zeelool;
        $this->vooguemeOperate  = new \app\admin\model\operatedatacenter\Voogueme;
        $this->nihaoOperate  = new \app\admin\model\operatedatacenter\Nihao;
        $this->zeelooldeOperate  = new \app\admin\model\operatedatacenter\ZeeloolDe();
        $this->zeelooljpOperate  = new \app\admin\model\operatedatacenter\ZeeloolJp();
        $this->magentoplatform = new \app\admin\model\platformmanage\MagentoPlatform();
    }

    /**
     * 订单数据-转化率分析
     *
     * @return \think\Response
     */
    public function index()
    {
        //设置过滤方法
        $this->request->filter(['strip_tags']);
        if ($this->request->isAjax()) {
            $filter = json_decode($this->request->get('filter'), true);
            //如果发送的来源是Selectpage，则转发到Selectpage
            if ($this->request->request('keyField')) {
                return $this->selectpage();
            }
            if($filter['create_time-operate']){
                unset($filter['create_time-operate']);
                $this->request->get(['filter' => json_encode($filter)]);
            }
            if ($filter['time_str']) {
                $createat = explode(' ', $filter['time_str']);
                $map['day_date'] = ['between', [$createat[0], $createat[3].' 23:59:59']];
                unset($filter['time_str']);
                $this->request->get(['filter' => json_encode($filter)]);
            } else{
                if(isset($filter['time_str'])){
                    unset($filter['time_str']);
                    $this->request->get(['filter' => json_encode($filter)]);
                }
                $start = date('Y-m-d', strtotime('-6 day'));
                $end   = date('Y-m-d 23:59:59');
                $map['day_date'] = ['between', [$start,$end]];
            }
            //站点
            if ($filter['order_platform']) {
                $map['site'] = $filter['order_platform'] ?: 1;
                unset($filter['order_platform']);
                $this->request->get(['filter' => json_encode($filter)]);
            }else{
                $map['site'] = 1;
            }
            list($where, $sort, $order, $offset, $limit) = $this->buildparams();
            $total = Db::name('datacenter_day')
                ->where($where)
                ->where($map)
                ->order($sort, $order)
                ->count();

            $list = Db::name('datacenter_day')
                ->where($where)
                ->where($map)
                ->order($sort, $order)
                ->limit($offset, $limit)
                ->select();
            $list = collection($list)->toArray();
            foreach ($list as $key=>$value){
                $list[$key]['add_cart_rate'] = $value['add_cart_rate'].'%';
                $list[$key]['session_rate'] = $value['session_rate'].'%';
            }
            //合计
            $sessions = Db::name('datacenter_day')->where($map)->sum('sessions');
            $order_num = Db::name('datacenter_day')->where($map)->sum('order_num');
            $new_cart_num = Db::name('datacenter_day')->where($map)->sum('new_cart_num');
            $update_cart_num = Db::name('datacenter_day')->where($map)->sum('update_cart_num');
            $sales_total_money = Db::name('datacenter_day')->where($map)->sum('sales_total_money');
            $register_num = Db::name('datacenter_day')->where($map)->sum('register_num');
            $add_cart_rate = $sessions ? round($new_cart_num / $sessions * 100, 2) : 0;
            $session_rate = $sessions ? round($order_num / $sessions * 100, 2) : 0;
            $order_unit_price = $order_num ? round($sales_total_money / $order_num, 2) : 0;

            $data =array(
                'sessions'=>$sessions,
                'add_cart_rate'=>$add_cart_rate,
                'session_rate'=>$session_rate,
                'order_num'=>$order_num,
                'order_unit_price'=>$order_unit_price,
                'new_cart_num'=>$new_cart_num,
                'update_cart_num'=>$update_cart_num,
                'sales_total_money'=>$sales_total_money,
                'register_num'=>$register_num,
            );
            $result = array("total" => $total, "rows" => $list,'data'=>$data);

            return json($result);
        }
        //查询对应平台权限
        $magentoplatformarr = $this->magentoplatform->getAuthSite();
        foreach ($magentoplatformarr as $key=>$val){
            if(!in_array($val['name'],['zeelool','voogueme','nihao','zeelool_de','zeelool_jp'])){
                unset($magentoplatformarr[$key]);
            }
        }
        $this->view->assign('magentoplatformarr',$magentoplatformarr);
        return $this->view->fetch();
    }

    /**
     * 会话/销售额
     *
     * @Description
     * @author wpl
     * @since 2020/10/14 15:02:23 
     * @return void
     */
    public function order_sales_data_line()
    {
        if ($this->request->isAjax()) {
            $params = $this->request->param();
            $map['site'] = $params['order_platform'] ? $params['order_platform'] : 1;
            if ($params['time_str']) {
                $createat = explode(' ', $params['time_str']);
                $map['day_date'] = ['between', [$createat[0], $createat[3].' 23:59:59']];
            } else{
                $start = date('Y-m-d', strtotime('-6 day'));
                $end   = date('Y-m-d 23:59:59');
                $map['day_date'] = ['between', [$start,$end]];
            }
            $days_data = Db::name('datacenter_day')->where($map)->select();
            $days_data = collection($days_data)->toArray();
            $arr['xdata'] = array_column($days_data,'day_date');
            $arr['ydata']['one'] = array_column($days_data,'sessions') ? array_column($days_data,'sessions') : '无';
            $arr['ydata']['two'] = array_column($days_data,'sales_total_money') ? array_column($days_data,'sales_total_money') : '无';

            $json['xColumnName'] = $arr['xdata'];
            $json['columnData'] = [
                [
                    'type' => 'line',
                    'data' => $arr['ydata']['one'],
                    'name' => '会话数',
                    'yAxisIndex' => 0,
                    'smooth' => true //平滑曲线
                ],
                [
                    'type' => 'line',
                    'data' => $arr['ydata']['two'],
                    'name' => '销售额',
                    'yAxisIndex' => 1,
                    'smooth' => true //平滑曲线
                ],
            ];

            return json(['code' => 1, 'data' => $json]);
        }
    }
    /**
     * 购物车数量/订单数量
     *
     * @Description
     * @author wpl
     * @since 2020/10/14 15:02:23 
     * @return void
     */
    public function order_num_data_line()
    {
        if ($this->request->isAjax()) {
            $params = $this->request->param();
            $where['site'] = $params['order_platform'] ? $params['order_platform'] : 1;
            if ($params['time_str']) {
                $createat = explode(' ', $params['time_str']);
                $map['day_date'] = ['between', [$createat[0], $createat[3].' 23:59:59']];
            } else{
                $start = date('Y-m-d', strtotime('-6 day'));
                $end   = date('Y-m-d 23:59:59');
                $map['day_date'] = ['between', [$start,$end]];
            }
            $days_data = Db::name('datacenter_day')->where($where)->where($map)->select();
            $days_data = collection($days_data)->toArray();
            $arr['xdata'] = array_column($days_data,'day_date');
            $arr['ydata']['one'] = array_column($days_data,'new_cart_num') ? array_column($days_data,'new_cart_num') : '无';
            $arr['ydata']['two'] = array_column($days_data,'order_num') ? array_column($days_data,'order_num') : '无';

            $json['xColumnName'] = $arr['xdata'];
            $json['columnData'] = [
                [
                    'type' => 'line',
                    'data' => $arr['ydata']['one'],
                    'name' => '购物车数量',
                    'smooth' => true //平滑曲线
                ],
                [
                    'type' => 'line',
                    'data' => $arr['ydata']['two'],
                    'name' => '订单数量',
                    'smooth' => true //平滑曲线
                ],
            ];

            return json(['code' => 1, 'data' => $json]);
        }
    }
}
