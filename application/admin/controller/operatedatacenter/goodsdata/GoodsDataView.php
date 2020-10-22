<?php

namespace app\admin\controller\operatedatacenter\GoodsData;

use app\admin\model\itemmanage\ItemPlatformSku;
use app\common\controller\Backend;
use think\Controller;
use think\Db;
use think\Request;

class GoodsDataView extends Backend
{
    public function _initialize()
    {
        parent::_initialize();

        $this->item_platform = new ItemPlatformSku();
    }

    /**
     * 商品数据-数据概览
     *
     * @return \think\Response
     */
    public function index()
    {
        $label = input('label', 1);
        switch ($label) {
            case 1:
                $goods_type = [1 => '光学镜', 2 => '太阳镜', 3 => '运动镜', 4 => '老花镜', 5 => '儿童镜', 6 => '配饰'];
                break;
            case 2:
                $goods_type = [1 => '平光镜', 2 => '太阳镜', 6 => '配饰'];
                break;
            case 3:
                $goods_type = [1 => '平光镜', 2 => '太阳镜'];
                break;
        }
        if ($this->request->isAjax()) {
            $result = [];
            return json(['code' => 1, 'rows' => $result]);
        }
        $this->assign('label', $label);
        $this->assignconfig('label', $label);
        $this->assign('goods_type', $goods_type);
        return $this->view->fetch();
    }

    /**
     * 镜框销量/客单价趋势
     *
     * @Description
     * @author wpl
     * @since 2020/10/14 15:02:02 
     * @return void
     */
    public function goods_sales_data_line()
    {
        if ($this->request->isAjax()) {
            $json['xColumnName'] = ['2020-07-01', '2020-07-02', '2020-07-03', '2020-07-04', '2020-07-05', '2020-07-06', '2020-07-07', '2020-07-08'];
            $json['columnData'] = [
                [
                    'type' => 'line',
                    'data' => [430, 550, 800, 650, 410, 520, 430, 870],
                    'name' => '镜框销量',
                    'yAxisIndex' => 0,
                    'smooth' => true //平滑曲线
                ],
                [
                    'type' => 'line',
                    'data' => [10, 26, 45, 40, 40, 65, 73, 80],
                    'name' => '副单价',
                    'yAxisIndex' => 1,
                    'smooth' => true //平滑曲线
                ],

            ];

            return json(['code' => 1, 'data' => $json]);
        }
    }

    /**
     * 各品类商品销量趋势
     *
     * @Description
     * @author wpl
     * @since 2020/10/14 15:02:23 
     * @return void
     */
    public function goods_type_data_line()
    {
        if ($this->request->isAjax()) {
            $json['xcolumnData'] = ['2020-07-01', '2020-07-02', '2020-07-03', '2020-07-04', '2020-07-05', '2020-07-06', '2020-07-07', '2020-07-08'];
            $json['column'] = ['平光镜', '太阳镜'];
            $json['columnData'] = [
                [
                    'type' => 'line',
                    'data' => [430, 550, 800, 650, 410, 520, 430, 870],
                    'name' => '平光镜',
                    'smooth' => true //平滑曲线
                ],
                [
                    'type' => 'line',
                    'data' => [100, 260, 450, 400, 400, 650, 730, 800],
                    'name' => '太阳镜',
                    'smooth' => true //平滑曲线
                ],

            ];

            return json(['code' => 1, 'data' => $json]);
        }
    }

    public function ajax_top_data()
    {
        if ($this->request->isAjax()) {
            $params = $this->request->param();
            if ($params['time_str']) {
                //时间段总和
                $createat = explode(' ', $params['time_str']);
            } else {
                $start = date('Y-m-d', strtotime('-6 day'));
                $end = date('Y-m-d 23:59:59');
                $seven_days = $start . ' 00:00:00 - ' . $end . ' 00:00:00';
                $createat = explode(' ', $seven_days);
            }
            $map['day_date'] = ['between', [$createat[0], $createat[3]]];
            //判断站点
            switch ($params['order_platform']) {
                case 1:
                    $plat = 1;
                    break;
                case 2:
                    $plat = 2;
                    break;
                case 3:
                    $plat = 3;
                    break;
            }
            $data_center_day = Db::name('datacenter_sku_day')->where(['site' => $plat])->where($map)->group('goods_type')->field('site,sum(order_num) as total_order_num,goods_type')->select();
            $data_center_day = array_column($data_center_day, null, 'goods_type');

            //goods_type:1光学镜,2太阳镜,,3运动镜,4老花镜,5儿童镜,6配饰
            $glass_num = $data_center_day[1]['total_order_num'] ? $data_center_day[1]['total_order_num'] : 0;
            $sun_glass_num = $data_center_day[2]['total_order_num'] ? $data_center_day[2]['total_order_num'] : 0;
            $run_glass_num = $data_center_day[3]['total_order_num'] ? $data_center_day[3]['total_order_num'] : 0;
            $old_glass_num = $data_center_day[4]['total_order_num'] ? $data_center_day[4]['total_order_num'] : 0;
            $son_glass_num = $data_center_day[5]['total_order_num'] ? $data_center_day[5]['total_order_num'] : 0;
            $other_num = $data_center_day[6]['total_order_num'] ? $data_center_day[6]['total_order_num'] : 0;
            $total_num = $glass_num + $sun_glass_num + $run_glass_num + $old_glass_num + $son_glass_num + $other_num;
        }
        $data = compact('a_plus_data', 'a_data', 'b_data', 'c_plus_data', 'd_data', 'e_data', 'f_data', 'glass_num', 'sun_glass_num', 'run_glass_num', 'old_glass_num', 'son_glass_num', 'other_num', 'total_num');
        $this->success('', '', $data);
    }

    public function ajax_dowm_data()
    {
        // if ($this->request->isAjax()) {
        $params = $this->request->param();
        if ($params['time_str']) {
            //时间段总和
            $createat = explode(' ', $params['time_str']);
        } else {
            $start = date('Y-m-d', strtotime('-6 day'));
            $end = date('Y-m-d 23:59:59');
            $seven_days = $start . ' 00:00:00 - ' . $end . ' 00:00:00';
            $createat = explode(' ', $seven_days);
        }

        $params['order_platform'] = $params['order_platform'] ? $params['order_platform'] : 1;
        $map['day_date'] = ['between', [$createat[0], $createat[3]]];
        $map1 = $map;
        if ($params['goods_type']){
            $map1['goods_type'] = ['=',$params['goods_type']];
        }
        $data_center_day = Db::name('datacenter_sku_day')
            ->where(['site' => $params['order_platform']])
            ->where($map)
            ->group('goods_grade')
            ->field('site,sum(order_num) as total_order_num,goods_type,goods_grade,count(site) as goods_num')
            ->select();
        $skus = Db::name('datacenter_sku_day')
            ->where(['site' => $params['order_platform']])
            ->where($map1)
            ->group('goods_grade,sku')
            ->field('site,sku,day_date,goods_grade,goods_type')
            ->select();
        // foreach ($skus)
        dump($skus);
        $data_center_day = array_column($data_center_day, null, 'goods_grade');
        dump($data_center_day);
        $a_plus_data = ['total_order_num' => $data_center_day['A+']['total_order_num'], 'goods_num' => $data_center_day['A+']['goods_num']];
        $a_data = [];
        $b_data = [];
        $c_plus_data = [];
        $d_data = [];
        $e_data = [];
        $f_data = [];
        // }
        $data = compact('a_plus_data', 'a_data', 'b_data', 'c_plus_data', 'd_data', 'e_data', 'f_data', 'glass_num', 'sun_glass_num', 'run_glass_num', 'old_glass_num', 'son_glass_num', 'other_num', 'total_num');
        $this->success('', '', $data);
    }

}
