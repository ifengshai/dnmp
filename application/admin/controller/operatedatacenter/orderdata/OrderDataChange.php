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
            if ($filter['time_str']) {
                $createat = explode(' ', $filter['time_str']);
                $map['day_date'] = ['between', [$createat[0], $createat[3]]];
                unset($filter['create_time-operate']);
                unset($filter['time_str']);
                unset($filter['order_platform']);
                $this->request->get(['filter' => json_encode($filter)]);
                if($createat[0] == $createat[3]){
                    $today_flag = date('Y-m-d');
                }
            } else{
                $start = date('Y-m-d');
                $map = [];
                $map[] = ['exp', Db::raw("DATE_FORMAT(day_date, '%Y-%m-%d') = '" . $start . "'")];
                $today_flag = $start;
            }
            //站点
            if ($filter['order_platform']) {
                $site['site'] = $filter['order_platform'] ?: 1;
                unset($filter['create_time-operate']);
                unset($filter['time_str']);
                unset($filter['order_platform']);
                $this->request->get(['filter' => json_encode($filter)]);
            }else{
                $site['site'] = 1;
            }
            if($site['site'] == 2){
                $this->model  = new \app\admin\model\operatedatacenter\Voogueme;
                $this->web  = new \app\admin\model\order\order\Voogueme();
            }elseif($site['site'] == 3){
                $this->model  = new \app\admin\model\operatedatacenter\Nihao;
                $this->web  = new \app\admin\model\order\order\Nihao();
            }else{
                $this->model  = new \app\admin\model\operatedatacenter\Zeelool;
                $this->web  = new \app\admin\model\order\order\Zeelool();
            }
            $this->web->table('sales_flat_quote')->query("set time_zone='+8:00'");
            $this->web->table('customer_entity')->query("set time_zone='+8:00'");
            list($where, $sort, $order, $offset, $limit) = $this->buildparams();
            $total = Db::name('datacenter_day')
                ->where($where)
                ->where($site)
                ->where($map)
                ->order($sort, $order)
                ->count();

            $list = Db::name('datacenter_day')
                ->where($where)
                ->where($site)
                ->where($map)
                ->order($sort, $order)
                ->limit($offset, $limit)
                ->select();
            $list = collection($list)->toArray();
            if($today_flag){
                $data['day_date'] = $today_flag;
                $data['sessions'] = $this->model->google_landing($site['site'],$today_flag);
                $cart_where1 = [];
                $cart_where1[] = ['exp', Db::raw("DATE_FORMAT(created_at, '%Y-%m-%d') = '" . $today_flag . "'")];
                $data['new_cart_num'] = $this->web->table('sales_flat_quote')->where($cart_where1)->count();
                $data['add_cart_rate'] = $data['sessions'] ? round(($data['new_cart_num']/$data['sessions']*100),2) : 0;
                $data['order_num'] = $this->model->getOrderNum($today_flag)['order_num'];
                $data['session_rate'] = $data['sessions'] ? round(($data['order_num']/$data['sessions']*100),2) : 0;
                $data['order_unit_price'] = $this->model->getOrderUnitPrice($today_flag)['order_unit_price'];
                $cart_where2 = [];
                $cart_where2[] = ['exp', Db::raw("DATE_FORMAT(updated_at, '%Y-%m-%d') = '" . $today_flag . "'")];
                $data['update_cart_num'] = $this->web->table('sales_flat_quote')->where($cart_where2)->count();
                $data['sales_total_money'] = $this->model->getSalesTotalMoney($today_flag)['sales_total_money'];
                $register_where = [];
                $register_where[] = ['exp', Db::raw("DATE_FORMAT(created_at, '%Y-%m-%d') = '" . $today_flag . "'")];
                $data['register_num'] = $this->web->table('customer_entity')->where($register_where)->count();
                $list[0] = $data;
            }
            foreach ($list as $key=>$value){
                $list[$key]['add_cart_rate'] = $value['add_cart_rate'].'%';
                $list[$key]['session_rate'] = $value['session_rate'].'%';
            }
            $result = array("total" => $total, "rows" => $list);

            return json($result);
        }
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
            $where['site'] = $params['order_platform'] ? $params['order_platform'] : 1;
            if ($params['time_str']) {
                $createat = explode(' ', $params['time_str']);
                $map['day_date'] = ['between', [$createat[0], $createat[3]]];
            } else{
                $start = date('Y-m-d');
                $map = [];
                $map[] = ['exp', Db::raw("DATE_FORMAT(day_date, '%Y-%m-%d') = '" . $start . "'")];
            }
            $days_data = Db::name('datacenter_day')->where($where)->where($map)->select();
            $days_data = collection($days_data)->toArray();
            $arr['xdata'] = array_column($days_data,'day_date');
            $arr['ydata']['one'] = array_column($days_data,'sessions');
            $arr['ydata']['two'] = array_column($days_data,'sales_total_money');

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
                $map['day_date'] = ['between', [$createat[0], $createat[3]]];
            } else{
                $start = date('Y-m-d');
                $map = [];
                $map[] = ['exp', Db::raw("DATE_FORMAT(day_date, '%Y-%m-%d') = '" . $start . "'")];
            }
            $days_data = Db::name('datacenter_day')->where($where)->where($map)->select();
            $days_data = collection($days_data)->toArray();
            $arr['xdata'] = array_column($days_data,'day_date');
            $arr['ydata']['one'] = array_column($days_data,'new_cart_num');
            $arr['ydata']['two'] = array_column($days_data,'order_num');

            $json['xColumnName'] = $arr['xdata'];
            $json['columnData'] = [
                [
                    'type' => 'line',
                    'data' => $arr['ydata']['one'],
                    'name' => '购物车数量',
                    'yAxisIndex' => 0,
                    'smooth' => true //平滑曲线
                ],
                [
                    'type' => 'line',
                    'data' => $arr['ydata']['two'],
                    'name' => '订单数量',
                    'yAxisIndex' => 1,
                    'smooth' => true //平滑曲线
                ],

            ];

            return json(['code' => 1, 'data' => $json]);
        }
    }
}
