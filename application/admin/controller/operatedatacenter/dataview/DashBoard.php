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
        $params = $this->request->param();
        //站点
        $platform = $params['platform'] ? $params['platform'] : 1;
        //时间
        $workload_time = $params['workload_time'];
        switch ($platform) {
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
        // //活跃用户数
        // $active_user_num = $model->getActiveUser();
        // //注册用户数
        // $register_user_num = $model->getRegisterUser();
        // //复购用户数
        // $again_user_num = $model->getAgainUser();
        // //vip用户数
        // $vip_user_num = $model->getVipUser();
        //订单数
        $order_num = $model->getOrderNum('2020-10-11 00:00:00 - 2020-10-12 00:00:00');
        //客单价
        $order_unit_price = $model->getOrderUnitPrice();
        //销售额
        $sales_total_money = $model->getSalesTotalMoney();
        //邮费
        $shipping_total_money = $model->getShippingTotalMoney();
        $this->view->assign(compact('order_num', 'order_unit_price', 'sales_total_money', 'shipping_total_money','active_user_num'));
        return $this->view->fetch();

    }

    /**
     * 订单趋势统计折线图
     *
     * Created by Phpstorm.
     * User: jhh
     * Date: 2020/10/13
     * Time: 13:42:57
     */
    public function order_trend()
    {
        // if ($this->request->isAjax()) {
        $params = $this->request->param();
        //站点
        $platform = $params['platform'] ? $params['platform'] : 1;
        //时间
        $workload_time = $params['workload_time'];
        switch ($platform) {
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
        //订单数
        $order_num = $model->getOrderNum('2020-10-11 00:00:00 - 2020-10-12 00:00:00');
        //客单价
        $order_unit_price = $model->getOrderUnitPrice();
        //销售额
        $sales_total_money = $model->getSalesTotalMoney();
        //邮费
        $shipping_total_money = $model->getShippingTotalMoney();
        // dump($order_num);
        // dump($order_unit_price);
        // dump($sales_total_money);
        // dump($shipping_total_money);
        $date_arr = ["2020-10-07" => 0, "2020-10-08" => 0, "2020-10-09" => 0, "2020-10-10" => 0, "2020-10-11" => 0, "2020-10-12" => 0];

        $name = '订单趋势统计';
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


        // }
    }

    public function active_user_trend()
    {
        // if ($this->request->isAjax()) {
        $params = $this->request->param();
        //站点
        $platform = $params['platform'] ? $params['platform'] : 1;
        //时间
        $workload_time = $params['workload_time'];
        switch ($platform) {
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
        //订单数
        $order_num = $model->getOrderNum('2020-10-11 00:00:00 - 2020-10-12 00:00:00');
        //客单价
        $order_unit_price = $model->getOrderUnitPrice();
        //销售额
        $sales_total_money = $model->getSalesTotalMoney();
        //邮费
        $shipping_total_money = $model->getShippingTotalMoney();

        $date_arr = ["2020-10-07" => 0, "2020-10-08" => 0, "2020-10-09" => 1, "2020-10-10" => 500, "2020-10-11" => 20, "2020-10-12" => 1000];

        $name = '活跃用户趋势统计';
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


        // }
    }
}
