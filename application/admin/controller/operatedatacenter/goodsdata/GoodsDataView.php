<?php

namespace app\admin\controller\operatedatacenter\GoodsData;

use app\admin\model\itemmanage\ItemPlatformSku;
use app\common\controller\Backend;
use function GuzzleHttp\describe_type;
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
     * 镜框销量/幅单价趋势
     *
     * @Description
     * @author wpl
     * @since 2020/10/14 15:02:02 
     * @return void
     */
    //old
    public function goods_sales_data_line1()
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

            $data_center_day = Db::name('datacenter_sku_day')
                ->where(['site' => $params['order_platform']])
                ->where($map)
                ->group('day_date')
                ->order('day_date', 'asc')
                ->field('sum(sales_num) as total_sales_num,day_date')
                ->select();
            $data_center_day1 = Db::name('datacenter_sku_day')
                ->where(['site' => $params['order_platform']])
                ->where($map)
                ->group('day_date')
                ->order('day_date', 'asc')
                ->field('sum(sku_row_total) as total_sku_row_total,day_date')
                ->select();
            $data_center_day = array_column($data_center_day, 'total_sales_num', 'day_date');
            $data_center_day1 = array_column($data_center_day1, 'total_sku_row_total', 'day_date');
            // dump($data_center_day1);
            foreach ($data_center_day1 as $key => $value) {
                $data_center_day1[$key] = $data_center_day[$key] != 0 ? round($value / $data_center_day[$key], 2) : 0;
            }
            // dump($data_center_day);
            // dump($data_center_day1);
            // die;

            // $json['xColumnName'] = ['2020-07-01', '2020-07-02', '2020-07-03', '2020-07-04', '2020-07-05', '2020-07-06', '2020-07-07', '2020-07-08'];
            $json['xColumnName'] = array_keys($data_center_day);
            $json['columnData'] = [
                [
                    'type' => 'line',
                    'data' => array_values($data_center_day),
                    'name' => '镜框销量',
                    'yAxisIndex' => 0,
                    'smooth' => true //平滑曲线
                ],
                [
                    'type' => 'line',
                    'data' => array_values($data_center_day1),
                    // 'data' => [10, 26, 45, 40, 40, 65, 73, 80],
                    'name' => '副单价',
                    'yAxisIndex' => 1,
                    'smooth' => true //平滑曲线
                ],

            ];

            return json(['code' => 1, 'data' => $json]);
        }
    }

    //new
    public function goods_sales_data_line()
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
            // $params['order_platform'] = 2;
            $data_center_day = Db::name('datacenter_goods_type_data')
                ->where(['site' => $params['order_platform']])
                ->order('day_date', 'asc')
                ->where($map)
                ->select();

            // dump($data_center_day1);
            //销售总金额
            $data_center_day1 = array();
            foreach ($data_center_day as $key => $value) {
                if (!$data_center_day1[$value['day_date']]) {
                    $data_center_day1[$value['day_date']] = $value['sales_total_money'];
                } else {
                    $data_center_day1[$value['day_date']] += $value['sales_total_money'];
                }
            }
            //每天每个类型销售副数
            $data_center_day2 = array();
            foreach ($data_center_day as $key => $value) {
                if (!$data_center_day2[$value['day_date']]) {
                    $data_center_day2[$value['day_date']] = $value['glass_num'];
                } else {
                    $data_center_day2[$value['day_date']] += $value['glass_num'];
                }
            }

            //副单价
            foreach ($data_center_day1 as $key => $value) {
                $data_center_day3[$key] = $data_center_day2[$key] == 0 ? 0 : round($data_center_day1[$key] / $data_center_day2[$key],2);
            }
            // dump($data_center_day1);
            // dump($data_center_day2);
            // dump($data_center_day3);
            // die;

            // $json['xColumnName'] = ['2020-07-01', '2020-07-02', '2020-07-03', '2020-07-04', '2020-07-05', '2020-07-06', '2020-07-07', '2020-07-08'];
            $json['xColumnName'] = array_keys($data_center_day1);
            $json['columnData'] = [
                [
                    'type' => 'line',
                    'data' => array_values($data_center_day2),
                    'name' => '镜框销量',
                    'yAxisIndex' => 0,
                    'smooth' => true //平滑曲线
                ],
                [
                    'type' => 'line',
                    'data' => array_values($data_center_day3),
                    // 'data' => [10, 26, 45, 40, 40, 65, 73, 80],
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
    //old
    public function goods_type_data_line1()
    {
        if ($this->request->isAjax()) {
            $params = $this->request->param();
            if ($params['time_str']) {
                //时间段总和
                $createat = explode(' ', $params['time_str']);
            } else {
                //默认七天
                $start = date('Y-m-d', strtotime('-6 day'));
                $end = date('Y-m-d 23:59:59');
                $seven_days = $start . ' 00:00:00 - ' . $end . ' 00:00:00';
                $createat = explode(' ', $seven_days);
            }
            $map['day_date'] = ['between', [$createat[0], $createat[3]]];


            $data_center_day = Db::name('datacenter_sku_day')
                ->where(['site' => $params['order_platform']])
                ->where($map)
                ->group('goods_type,day_date')
                ->order('day_date', 'asc')
                ->field('day_date,goods_type,sales_num,sum(sales_num) as total_sales_num')
                ->select();
            // dump($data_center_day);
            $date_arr = array_keys(array_column($data_center_day, null, 'day_date'));
            // $date_arr = array_column($data_center_day, null, 'day_date');
            //没有某个分类的数据拼接数组 当天的此数据全部为0
            foreach ($date_arr as $vv) {
                $date_arrs[$vv] = 0;
            }
            // dump($data_center_day);
            // dump($date_arrs);
            $arr = [];
            foreach ($data_center_day as $key => $value) {
                if ($arr[$value['goods_type']][$value['day_date']]) {
                    $arr[$value['goods_type']][$value['day_date']] += $value['total_sales_num'];
                } else {
                    $arr[$value['goods_type']][$value['day_date']] = $value['total_sales_num'];
                }
            }
            // dump($arr);die;
            //判断站点
            switch ($params['order_platform']) {
                case 1:
                    $goods_type = [1 => '光学镜', 2 => '太阳镜', 5 => '运动镜', 3 => '老花镜', 4 => '儿童镜', 6 => '配饰'];
                    // $json['xcolumnData'] = ['2020-07-01', '2020-07-02', '2020-07-03', '2020-07-04', '2020-07-05', '2020-07-06', '2020-07-07', '2020-07-08'];
                    $json['xcolumnData'] = $date_arr;


                    // $json['column'] = ['平光镜', '太阳镜'];
                    $json['column'] = $goods_type;
                    $json['columnData'] = [
                        [
                            'type' => 'line',
                            'data' => array_values($arr[1] ? $arr[1] : $date_arrs),
                            'name' => '光学镜',
                            'smooth' => true //平滑曲线
                        ],
                        [
                            'type' => 'line',
                            'data' => array_values($arr[2] ? $arr[2] : $date_arrs),
                            'name' => '太阳镜',
                            'smooth' => true //平滑曲线
                        ],
                        [
                            'type' => 'line',
                            'data' => array_values($arr[5] ? $arr[5] : $date_arrs),
                            'name' => '运动镜',
                            'smooth' => true //平滑曲线
                        ],
                        [
                            'type' => 'line',
                            'data' => array_values($arr[3] ? $arr[3] : $date_arrs),
                            'name' => '老花镜',
                            'smooth' => true //平滑曲线
                        ],
                        [
                            'type' => 'line',
                            'data' => array_values($arr[4] ? $arr[4] : $date_arrs),
                            'name' => '儿童镜',
                            'smooth' => true //平滑曲线
                        ],
                        [
                            'type' => 'line',
                            'data' => array_values($arr[6] ? $arr[6] : $date_arrs),
                            'name' => '配饰',
                            'smooth' => true //平滑曲线
                        ],

                    ];
                    break;
                case 2:
                    $goods_type = [1 => '平光镜', 2 => '太阳镜', 6 => '配饰'];
                    $json['xcolumnData'] = $date_arr;
                    $arr[$value['goods_type']][$value['day_date']] = $value['total_sales_num'];
                    // $json['column'] = ['平光镜', '太阳镜'];
                    $json['column'] = $goods_type;
                    $json['columnData'] = [
                        [
                            'type' => 'line',
                            'data' => array_values($arr[1] ? $arr[1] : $date_arrs),
                            'name' => '平光镜',
                            'smooth' => true //平滑曲线
                        ],
                        [
                            'type' => 'line',
                            'data' => array_values($arr[2] ? $arr[2] : $date_arrs),
                            'name' => '太阳镜',
                            'smooth' => true //平滑曲线
                        ],
                        [
                            'type' => 'line',
                            'data' => array_values($arr[6] ? $arr[6] : $date_arrs),
                            'name' => '配饰',
                            'smooth' => true //平滑曲线
                        ],

                    ];
                    break;
                case 3:
                    $goods_type = [1 => '平光镜', 2 => '太阳镜'];
                    $json['xcolumnData'] = $date_arr;
                    $arr[$value['goods_type']][$value['day_date']] = $value['total_sales_num'];
                    // $json['column'] = ['平光镜', '太阳镜'];
                    $json['column'] = $goods_type;
                    $json['columnData'] = [
                        [
                            'type' => 'line',
                            'data' => array_values($arr[1] ? $arr[1] : $date_arrs),
                            'name' => '平光镜',
                            'smooth' => true //平滑曲线
                        ],
                        [
                            'type' => 'line',
                            'data' => array_values($arr[2] ? $arr[2] : $date_arrs),
                            'name' => '太阳镜',
                            'smooth' => true //平滑曲线
                        ],

                    ];
                    break;
            }

            return json(['code' => 1, 'data' => $json]);
        }
    }

    //new
    public function goods_type_data_line()
    {
        if ($this->request->isAjax()) {
            $params = $this->request->param();
            if ($params['time_str']) {
                //时间段总和
                $createat = explode(' ', $params['time_str']);
            } else {
                //默认七天
                $start = date('Y-m-d', strtotime('-6 day'));
                $end = date('Y-m-d 23:59:59');
                $seven_days = $start . ' 00:00:00 - ' . $end . ' 00:00:00';
                $createat = explode(' ', $seven_days);
            }
            $map['day_date'] = ['between', [$createat[0], $createat[3]]];


            $data_center_day = Db::name('datacenter_goods_type_data')
                ->where(['site' => $params['order_platform']])
                ->group('goods_type,day_date')
                ->order('day_date', 'asc')
                ->field('day_date,goods_type,glass_num,sum(glass_num) as total_sales_num')
                ->select();
            $date_arr = array_keys(array_column($data_center_day, null, 'day_date'));
            //没有某个分类的数据拼接数组 当天的此数据全部为0
            foreach ($date_arr as $vv) {
                $date_arrs[$vv] = 0;
            }

            $arr = [];
            foreach ($data_center_day as $key => $value) {
                if ($arr[$value['goods_type']][$value['day_date']]) {
                    $arr[$value['goods_type']][$value['day_date']] += $value['total_sales_num'];
                } else {
                    $arr[$value['goods_type']][$value['day_date']] = $value['total_sales_num'];
                }
            }
            // dump($arr);die;
            //判断站点
            switch ($params['order_platform']) {
                case 1:
                    $goods_type = [1 => '光学镜', 2 => '太阳镜', 5 => '运动镜', 3 => '老花镜', 4 => '儿童镜', 6 => '配饰'];
                    // $json['xcolumnData'] = ['2020-07-01', '2020-07-02', '2020-07-03', '2020-07-04', '2020-07-05', '2020-07-06', '2020-07-07', '2020-07-08'];
                    $json['xcolumnData'] = $date_arr;


                    // $json['column'] = ['平光镜', '太阳镜'];
                    $json['column'] = $goods_type;
                    $json['columnData'] = [
                        [
                            'type' => 'line',
                            'data' => array_values($arr[1] ? $arr[1] : $date_arrs),
                            'name' => '光学镜',
                            'smooth' => true //平滑曲线
                        ],
                        [
                            'type' => 'line',
                            'data' => array_values($arr[2] ? $arr[2] : $date_arrs),
                            'name' => '太阳镜',
                            'smooth' => true //平滑曲线
                        ],
                        [
                            'type' => 'line',
                            'data' => array_values($arr[5] ? $arr[5] : $date_arrs),
                            'name' => '运动镜',
                            'smooth' => true //平滑曲线
                        ],
                        [
                            'type' => 'line',
                            'data' => array_values($arr[3] ? $arr[3] : $date_arrs),
                            'name' => '老花镜',
                            'smooth' => true //平滑曲线
                        ],
                        [
                            'type' => 'line',
                            'data' => array_values($arr[4] ? $arr[4] : $date_arrs),
                            'name' => '儿童镜',
                            'smooth' => true //平滑曲线
                        ],
                        [
                            'type' => 'line',
                            'data' => array_values($arr[6] ? $arr[6] : $date_arrs),
                            'name' => '配饰',
                            'smooth' => true //平滑曲线
                        ],

                    ];
                    break;
                case 2:
                    $goods_type = [1 => '平光镜', 2 => '太阳镜', 6 => '配饰'];
                    $json['xcolumnData'] = $date_arr;
                    $arr[$value['goods_type']][$value['day_date']] = $value['total_sales_num'];
                    // $json['column'] = ['平光镜', '太阳镜'];
                    $json['column'] = $goods_type;
                    $json['columnData'] = [
                        [
                            'type' => 'line',
                            'data' => array_values($arr[1] ? $arr[1] : $date_arrs),
                            'name' => '平光镜',
                            'smooth' => true //平滑曲线
                        ],
                        [
                            'type' => 'line',
                            'data' => array_values($arr[2] ? $arr[2] : $date_arrs),
                            'name' => '太阳镜',
                            'smooth' => true //平滑曲线
                        ],
                        [
                            'type' => 'line',
                            'data' => array_values($arr[6] ? $arr[6] : $date_arrs),
                            'name' => '配饰',
                            'smooth' => true //平滑曲线
                        ],

                    ];
                    break;
                case 3:
                    $goods_type = [1 => '平光镜', 2 => '太阳镜'];
                    $json['xcolumnData'] = $date_arr;
                    $arr[$value['goods_type']][$value['day_date']] = $value['total_sales_num'];
                    // $json['column'] = ['平光镜', '太阳镜'];
                    $json['column'] = $goods_type;
                    $json['columnData'] = [
                        [
                            'type' => 'line',
                            'data' => array_values($arr[1] ? $arr[1] : $date_arrs),
                            'name' => '平光镜',
                            'smooth' => true //平滑曲线
                        ],
                        [
                            'type' => 'line',
                            'data' => array_values($arr[2] ? $arr[2] : $date_arrs),
                            'name' => '太阳镜',
                            'smooth' => true //平滑曲线
                        ],

                    ];
                    break;
            }

            return json(['code' => 1, 'data' => $json]);
        }
    }

    //商品销量概况 old
    public function ajax_top_data1()
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

            // goods_type:1光学镜,2太阳镜,,3运动镜,4老花镜,5儿童镜,6配饰
            //goods_type:1光学镜,2太阳镜,,3老花镜,4儿童镜,5运动镜,6配饰
            $glass_num = $data_center_day[1]['total_order_num'] ? $data_center_day[1]['total_order_num'] : 0;
            $sun_glass_num = $data_center_day[2]['total_order_num'] ? $data_center_day[2]['total_order_num'] : 0;
            $run_glass_num = $data_center_day[3]['total_order_num'] ? $data_center_day[5]['total_order_num'] : 0;
            $old_glass_num = $data_center_day[4]['total_order_num'] ? $data_center_day[3]['total_order_num'] : 0;
            $son_glass_num = $data_center_day[5]['total_order_num'] ? $data_center_day[4]['total_order_num'] : 0;
            $other_num = $data_center_day[6]['total_order_num'] ? $data_center_day[6]['total_order_num'] : 0;
            $total_num = $glass_num + $sun_glass_num + $run_glass_num + $old_glass_num + $son_glass_num + $other_num;
        }
        $data = compact('a_plus_data', 'a_data', 'b_data', 'c_plus_data', 'd_data', 'e_data', 'f_data', 'glass_num', 'sun_glass_num', 'run_glass_num', 'old_glass_num', 'son_glass_num', 'other_num', 'total_num');
        $this->success('', '', $data);
    }

    //商品销量概况
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
            $itemMap['m.created_at'] = ['between', [$createat[0] . ' ' . $createat[1], $createat[3] . ' ' . $createat[4]]];

            //判断站点
            switch ($params['order_platform']) {
                case 1:
                    $glass_num = $this->glass_sales_num($itemMap, 1, 1);
                    $sun_glass_num = $this->glass_sales_num($itemMap, 2, 1);
                    $run_glass_num = $this->glass_sales_num($itemMap, 5, 1);
                    $old_glass_num = $this->glass_sales_num($itemMap, 3, 1);
                    $son_glass_num = $this->glass_sales_num($itemMap, 4, 1);
                    $other_num = $this->glass_sales_num($itemMap, 6, 1);
                    $total_num = $glass_num + $sun_glass_num + $run_glass_num + $old_glass_num + $son_glass_num + $other_num;
                    break;
                case 2:
                    $glass_num = $this->glass_sales_num($itemMap, 1, 2);
                    $sun_glass_num = $this->glass_sales_num($itemMap, 2, 2);
                    $other_num = $this->glass_sales_num($itemMap, 6, 2);
                    $total_num = $glass_num + $sun_glass_num + $other_num;
                    break;
                case 3:
                    $glass_num = $this->glass_sales_num($itemMap, 1, 3);
                    $sun_glass_num = $this->glass_sales_num($itemMap, 2, 3);
                    $total_num = $glass_num + $sun_glass_num;
                    break;
                default:
                    $model = false;
                    break;
            }

            // goods_type:1光学镜,2太阳镜,,3运动镜,4老花镜,5儿童镜,6配饰
            //goods_type:1光学镜,2太阳镜,,3老花镜,4儿童镜,5运动镜,6配饰 现在用的
            //            $glass_num = $data_center_day[1]['total_order_num'] ? $data_center_day[1]['total_order_num'] : 0;
            //            $sun_glass_num = $data_center_day[2]['total_order_num'] ? $data_center_day[2]['total_order_num'] : 0;
            //            $run_glass_num = $data_center_day[3]['total_order_num'] ? $data_center_day[5]['total_order_num'] : 0;
            //            $old_glass_num = $data_center_day[4]['total_order_num'] ? $data_center_day[3]['total_order_num'] : 0;
            //            $son_glass_num = $data_center_day[5]['total_order_num'] ? $data_center_day[4]['total_order_num'] : 0;
            //            $other_num = $data_center_day[6]['total_order_num'] ? $data_center_day[6]['total_order_num'] : 0;
            //            $total_num = $glass_num + $sun_glass_num + $run_glass_num + $old_glass_num + $son_glass_num + $other_num;
            $glass_num = $glass_num ? $glass_num : 0;
            $sun_glass_num = $sun_glass_num ? $sun_glass_num : 0;
            $run_glass_num = $run_glass_num ? $run_glass_num : 0;
            $old_glass_num = $old_glass_num ? $old_glass_num : 0;
            $son_glass_num = $son_glass_num ? $son_glass_num : 0;
            $other_num = $other_num ? $other_num : 0;
            $total_num = $glass_num + $sun_glass_num + $run_glass_num + $old_glass_num + $son_glass_num + $other_num;
        }
        $data = compact('a_plus_data', 'a_data', 'b_data', 'c_plus_data', 'd_data', 'e_data', 'f_data', 'glass_num', 'sun_glass_num', 'run_glass_num', 'old_glass_num', 'son_glass_num', 'other_num', 'total_num');
        $this->success('', '', $data);
    }

    //统计某个品类的眼睛的销量 $goods_type1光学镜,2太阳镜,,3老花镜,4儿童镜,5运动镜,6配饰 $plat站点
    public function glass_sales_num($itemMap, $goods_type, $plat)
    {
        //判断站点
        switch ($plat) {
            case 1:
                $model = Db::connect('database.db_zeelool');
                break;
            case 2:
                $model = Db::connect('database.db_voogueme');
                break;
            case 3:
                $model = Db::connect('database.db_nihao');
                break;
            default:
                $model = false;
                break;
        }
        $model->table('sales_flat_order')->query("set time_zone='+8:00'");
        $model->table('sales_flat_order_item')->query("set time_zone='+8:00'");
        $model->table('sales_flat_order_item_prescription')->query("set time_zone='+8:00'");
        //        $whereItem = " o.status in ('processing','complete','creditcard_proccessing','free_processing')";
        $whereItem = " o.status in ('free_processing', 'processing', 'complete', 'paypal_reversed', 'payment_review', 'paypal_canceled_reversal')";
        //某个品类眼镜的销售副数
        $frame_sales_num = $model->table('sales_flat_order_item m')
            ->join('sales_flat_order o', 'm.order_id=o.entity_id', 'left')
            ->join('sales_flat_order_item_prescription p', 'm.item_id=p.item_id', 'left')
            ->where('p.goods_type', '=', $goods_type)
            ->where($whereItem)
            ->where($itemMap)
            //            ->count('*');
            ->sum('m.qty_ordered');
        return $frame_sales_num;
    }

    //产品等级分布表格 old
    public function ajax_dowm_data1()
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

            $params['order_platform'] = $params['order_platform'] ? $params['order_platform'] : 1;
            $map['day_date'] = ['between', [$createat[0], $createat[3]]];
            $map1 = $map;
            if ($params['goods_type']) {
                $map['goods_type'] = ['=', $params['goods_type']];
            }
            //根据产品等级分组
            $data_center_day = Db::name('datacenter_sku_day')
                ->where(['site' => $params['order_platform']])
                ->where($map)
                ->group('goods_grade')
                ->field('site,sum(order_num) as total_order_num,goods_type,goods_grade,count(site) as goods_num,sum(sales_num) as total_sales_num')
                ->select();
            $skus = Db::name('datacenter_sku_day')
                ->where(['site' => $params['order_platform']])
                ->where($map)
                ->order('day_date', 'asc')
                ->field('sku,day_date,day_stock,day_onway_stock,goods_grade')
                ->select();


            $data_center_day = array_column($data_center_day, null, 'goods_grade');
            $skus = array_column($skus, null, 'sku');
            $arr = [];
            //统计筛选时间最近一天的在途和虚拟仓库存
            foreach ($skus as $k => $v) {
                if ($arr[$v['goods_grade']]) {
                    $arr[$v['goods_grade']]['day_stock'] += $v['day_stock'];
                    $arr[$v['goods_grade']]['day_onway_stock'] += $v['day_onway_stock'];
                } else {
                    $arr[$v['goods_grade']]['day_stock'] = $v['day_stock'];
                    $arr[$v['goods_grade']]['day_onway_stock'] = $v['day_onway_stock'];
                }

            }

            $a_plus_data['day_stock'] = $arr['A+']['day_stock'] ? $arr['A+']['day_stock'] : 0;
            $a_plus_data['day_onway_stock'] = $arr['A+']['day_onway_stock'] ? $arr['A+']['day_onway_stock'] : 0;
            $a_data['day_stock'] = $arr['A']['day_stock'] ? $arr['A']['day_stock'] : 0;
            $a_data['day_onway_stock'] = $arr['A']['day_onway_stock'] ? $arr['A']['day_onway_stock'] : 0;
            $b_data['day_stock'] = $arr['B']['day_stock'] ? $arr['B']['day_stock'] : 0;
            $b_data['day_onway_stock'] = $arr['B']['day_onway_stock'] ? $arr['B']['day_onway_stock'] : 0;
            $c_plus_data['day_stock'] = $arr['C+']['day_stock'] ? $arr['C+']['day_stock'] : 0;
            $c_plus_data['day_onway_stock'] = $arr['C+']['day_onway_stock'] ? $arr['C+']['day_onway_stock'] : 0;
            $c_data['day_stock'] = $arr['C']['day_stock'] ? $arr['C']['day_stock'] : 0;
            $c_data['day_onway_stock'] = $arr['C']['day_onway_stock'] ? $arr['C']['day_onway_stock'] : 0;
            $d_data['day_stock'] = $arr['D']['day_stock'] ? $arr['D']['day_stock'] : 0;
            $d_data['day_onway_stock'] = $arr['D']['day_onway_stock'] ? $arr['D']['day_onway_stock'] : 0;
            $e_data['day_stock'] = $arr['E']['day_stock'] ? $arr['E']['day_stock'] : 0;
            $e_data['day_onway_stock'] = $arr['E']['day_onway_stock'] ? $arr['E']['day_onway_stock'] : 0;
            // dump($data_center_day);
            //总数
            $total_sales_num = $data_center_day['A+']['total_sales_num'] + $data_center_day['A']['total_sales_num']
                + $data_center_day['B']['total_sales_num'] + $data_center_day['C+']['total_sales_num']
                + $data_center_day['C']['total_sales_num'] + $data_center_day['D']['total_sales_num']
                + $data_center_day['E']['total_sales_num'];
            $goods_num = $data_center_day['A+']['goods_num'] + $data_center_day['A']['goods_num']
                + $data_center_day['B']['goods_num'] + $data_center_day['C+']['goods_num']
                + $data_center_day['C']['goods_num'] + $data_center_day['D']['goods_num']
                + $data_center_day['E']['goods_num'];
            $total_stock = $a_plus_data['day_stock'] + $a_data['day_stock']
                + $b_data['day_stock'] + $c_plus_data['day_stock']
                + $c_data['day_stock'] + $d_data['day_stock']
                + $e_data['day_stock'];
            $total_onway_stock = $a_plus_data['day_onway_stock'] + $a_data['day_onway_stock']
                + $b_data['day_onway_stock'] + $c_plus_data['day_onway_stock']
                + $c_data['day_onway_stock'] + $d_data['day_onway_stock']
                + $e_data['day_onway_stock'];
            //goods_num 某个等级产品个数
            $a_plus_data['total_sales_num'] = $data_center_day['A+']['total_sales_num'] ? $data_center_day['A+']['total_sales_num'] : 0;
            $a_plus_data['goods_num'] = $data_center_day['A+']['goods_num'] ? $data_center_day['A+']['goods_num'] : 0;
            $a_plus_data['total_sales_num_rate'] = $total_sales_num != 0 ? round($data_center_day['A+']['total_sales_num'] / $total_sales_num * 100, 2) . '%' : '0%';
            $a_plus_data['goods_num_rate'] = $goods_num != 0 ? round($data_center_day['A+']['goods_num'] / $goods_num * 100, 2) . '%' : '0%';
            $a_plus_data['day_stock_rate'] = $total_stock != 0 ? round($a_plus_data['day_stock'] / $total_stock * 100, 2) . '%' : '0%';
            $a_plus_data['day_onway_stock_rate'] = $total_onway_stock != 0 ? round($a_plus_data['day_onway_stock'] / $total_onway_stock * 100, 2) . '%' : '0%';

            $a_data['total_sales_num'] = $data_center_day['A']['total_sales_num'] ? $data_center_day['A']['total_sales_num'] : 0;
            $a_data['goods_num'] = $data_center_day['A']['goods_num'] ? $data_center_day['A']['goods_num'] : 0;
            $a_data['total_sales_num_rate'] = $total_sales_num != 0 ? round($data_center_day['A']['total_sales_num'] / $total_sales_num * 100, 2) . '%' : '0%';
            $a_data['goods_num_rate'] = $goods_num != 0 ? round($data_center_day['A']['goods_num'] / $goods_num * 100, 2) . '%' : '0%';
            $a_data['day_stock_rate'] = $total_stock != 0 ? round($a_data['day_stock'] / $total_stock * 100, 2) . '%' : '0%';
            $a_data['day_onway_stock_rate'] = $total_onway_stock != 0 ? round($a_data['day_onway_stock'] / $total_onway_stock * 100, 2) . '%' : '0%';

            $b_data['total_sales_num'] = $data_center_day['B']['total_sales_num'] ? $data_center_day['B']['total_sales_num'] : 0;
            $b_data['goods_num'] = $data_center_day['B']['goods_num'] ? $data_center_day['B']['goods_num'] : 0;
            $b_data['total_sales_num_rate'] = $total_sales_num != 0 ? round($data_center_day['B']['total_sales_num'] / $total_sales_num * 100, 2) . '%' : '0%';
            $b_data['goods_num_rate'] = $goods_num != 0 ? round($data_center_day['B']['goods_num'] / $goods_num * 100, 2) . '%' : '0%';
            $b_data['day_stock_rate'] = $total_stock != 0 ? round($b_data['day_stock'] / $total_stock * 100, 2) . '%' : '0%';
            $b_data['day_onway_stock_rate'] = $total_onway_stock != 0 ? round($b_data['day_onway_stock'] / $total_onway_stock * 100, 2) . '%' : '0%';

            $c_plus_data['total_sales_num'] = $data_center_day['C+']['total_sales_num'] ? $data_center_day['C+']['total_sales_num'] : 0;
            $c_plus_data['goods_num'] = $data_center_day['C+']['goods_num'] ? $data_center_day['C+']['goods_num'] : 0;
            $c_plus_data['total_sales_num_rate'] = $total_sales_num != 0 ? round($data_center_day['C+']['total_sales_num'] / $total_sales_num * 100, 2) . '%' : '0%';
            $c_plus_data['goods_num_rate'] = $goods_num != 0 ? round($data_center_day['C+']['goods_num'] / $goods_num * 100, 2) . '%' : '0%';
            $c_plus_data['day_stock_rate'] = $total_stock != 0 ? round($c_plus_data['day_stock'] / $total_stock * 100, 2) . '%' : '0%';
            $c_plus_data['day_onway_stock_rate'] = $total_onway_stock != 0 ? round($c_plus_data['day_onway_stock'] / $total_onway_stock * 100, 2) . '%' : '0%';

            $c_data ['total_sales_num'] = $data_center_day['C']['total_sales_num'] ? $data_center_day['C']['total_sales_num'] : 0;
            $c_data ['goods_num'] = $data_center_day['C']['goods_num'] ? $data_center_day['C']['goods_num'] : 0;
            $c_data ['total_sales_num_rate'] = $total_sales_num != 0 ? round($data_center_day['C']['total_sales_num'] / $total_sales_num * 100, 2) . '%' : '0%';
            $c_data ['goods_num_rate'] = $goods_num != 0 ? round($data_center_day['C']['goods_num'] / $goods_num * 100, 2) . '%' : '0%';
            $c_data['day_stock_rate'] = $total_stock != 0 ? round($c_data['day_stock'] / $total_stock * 100, 2) . '%' : '0%';
            $c_data['day_onway_stock_rate'] = $total_onway_stock != 0 ? round($c_data['day_onway_stock'] / $total_onway_stock * 100, 2) . '%' : '0%';

            $d_data['total_sales_num'] = $data_center_day['D']['total_sales_num'] ? $data_center_day['D']['total_sales_num'] : 0;
            $d_data['goods_num'] = $data_center_day['D']['goods_num'] ? $data_center_day['D']['goods_num'] : 0;
            $d_data['total_sales_num_rate'] = $total_sales_num != 0 ? round($data_center_day['D']['total_sales_num'] / $total_sales_num * 100, 2) . '%' : '0%';
            $d_data['goods_num_rate'] = $goods_num != 0 ? round($data_center_day['D']['goods_num'] / $goods_num * 100, 2) . '%' : '0%';
            $d_data['day_stock_rate'] = $total_stock != 0 ? round($d_data['day_stock'] / $total_stock * 100, 2) . '%' : '0%';
            $d_data['day_onway_stock_rate'] = $total_onway_stock != 0 ? round($d_data['day_onway_stock'] / $total_onway_stock * 100, 2) . '%' : '0%';

            $e_data ['total_sales_num'] = $data_center_day['E']['total_sales_num'] ? $data_center_day['E']['total_sales_num'] : 0;
            $e_data ['goods_num'] = $data_center_day['E']['goods_num'] ? $data_center_day['E']['goods_num'] : 0;
            $e_data ['total_sales_num_rate'] = $total_sales_num != 0 ? round($data_center_day['E']['total_sales_num'] / $total_sales_num * 100, 2) . '%' : '0%';
            $e_data ['goods_num_rate'] = $goods_num != 0 ? round($data_center_day['E']['goods_num'] / $goods_num * 100, 2) . '%' : '0%';
            $e_data['day_stock_rate'] = $total_stock != 0 ? round($e_data['day_stock'] / $total_stock * 100, 2) . '%' : '0%';
            $e_data['day_onway_stock_rate'] = $total_onway_stock != 0 ? round($e_data['day_onway_stock'] / $total_onway_stock * 100, 2) . '%' : '0%';
            // dump($a_plus_data);
            // dump($a_data);
            // dump($b_data);
            // dump($c_plus_data);
            // dump($c_data);
            // dump($d_data);
            // dump($e_data);
        }
        $data = compact('a_plus_data', 'a_data', 'b_data', 'c_plus_data', 'c_data', 'd_data', 'e_data', 'f_data', 'total_sales_num', 'goods_num', 'total_stock', 'total_onway_stock');
        $this->success('', '', $data);
    }

    //产品等级分布表格
    public function ajax_dowm_data()
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

            $params['order_platform'] = $params['order_platform'] ? $params['order_platform'] : 1;
            $map['day_date'] = ['between', [$createat[0], $createat[3]]];
            $map1 = $map;
            if ($params['goods_type']) {
                $map['goods_type'] = ['=', $params['goods_type']];
            }
            //根据产品等级分组
            $data_center_day = Db::name('datacenter_sku_day')
                ->where(['site' => $params['order_platform']])
                ->where($map)
                ->group('goods_grade')
                ->field('site,sum(order_num) as total_order_num,goods_type,goods_grade,count(site) as goods_num,sum(sales_num) as total_sales_num')
                ->select();
            $skus = Db::name('datacenter_sku_day')
                ->where(['site' => $params['order_platform']])
                ->where($map)
                ->order('day_date', 'asc')
                ->field('sku,day_date,day_stock,day_onway_stock,goods_grade')
                ->select();


            $data_center_day = array_column($data_center_day, null, 'goods_grade');
            $skus = array_column($skus, null, 'sku');
            $arr = [];
            //统计筛选时间最近一天的在途和虚拟仓库存
            foreach ($skus as $k => $v) {
                if ($arr[$v['goods_grade']]) {
                    $arr[$v['goods_grade']]['day_stock'] += $v['day_stock'];
                    $arr[$v['goods_grade']]['day_onway_stock'] += $v['day_onway_stock'];
                } else {
                    $arr[$v['goods_grade']]['day_stock'] = $v['day_stock'];
                    $arr[$v['goods_grade']]['day_onway_stock'] = $v['day_onway_stock'];
                }

            }

            $a_plus_data['day_stock'] = $arr['A+']['day_stock'] ? $arr['A+']['day_stock'] : 0;
            $a_plus_data['day_onway_stock'] = $arr['A+']['day_onway_stock'] ? $arr['A+']['day_onway_stock'] : 0;
            $a_data['day_stock'] = $arr['A']['day_stock'] ? $arr['A']['day_stock'] : 0;
            $a_data['day_onway_stock'] = $arr['A']['day_onway_stock'] ? $arr['A']['day_onway_stock'] : 0;
            $b_data['day_stock'] = $arr['B']['day_stock'] ? $arr['B']['day_stock'] : 0;
            $b_data['day_onway_stock'] = $arr['B']['day_onway_stock'] ? $arr['B']['day_onway_stock'] : 0;
            $c_plus_data['day_stock'] = $arr['C+']['day_stock'] ? $arr['C+']['day_stock'] : 0;
            $c_plus_data['day_onway_stock'] = $arr['C+']['day_onway_stock'] ? $arr['C+']['day_onway_stock'] : 0;
            $c_data['day_stock'] = $arr['C']['day_stock'] ? $arr['C']['day_stock'] : 0;
            $c_data['day_onway_stock'] = $arr['C']['day_onway_stock'] ? $arr['C']['day_onway_stock'] : 0;
            $d_data['day_stock'] = $arr['D']['day_stock'] ? $arr['D']['day_stock'] : 0;
            $d_data['day_onway_stock'] = $arr['D']['day_onway_stock'] ? $arr['D']['day_onway_stock'] : 0;
            $e_data['day_stock'] = $arr['E']['day_stock'] ? $arr['E']['day_stock'] : 0;
            $e_data['day_onway_stock'] = $arr['E']['day_onway_stock'] ? $arr['E']['day_onway_stock'] : 0;
            // dump($data_center_day);
            //总数
            $total_sales_num = $data_center_day['A+']['total_sales_num'] + $data_center_day['A']['total_sales_num']
                + $data_center_day['B']['total_sales_num'] + $data_center_day['C+']['total_sales_num']
                + $data_center_day['C']['total_sales_num'] + $data_center_day['D']['total_sales_num']
                + $data_center_day['E']['total_sales_num'];
            $goods_num = $data_center_day['A+']['goods_num'] + $data_center_day['A']['goods_num']
                + $data_center_day['B']['goods_num'] + $data_center_day['C+']['goods_num']
                + $data_center_day['C']['goods_num'] + $data_center_day['D']['goods_num']
                + $data_center_day['E']['goods_num'];
            $total_stock = $a_plus_data['day_stock'] + $a_data['day_stock']
                + $b_data['day_stock'] + $c_plus_data['day_stock']
                + $c_data['day_stock'] + $d_data['day_stock']
                + $e_data['day_stock'];
            $total_onway_stock = $a_plus_data['day_onway_stock'] + $a_data['day_onway_stock']
                + $b_data['day_onway_stock'] + $c_plus_data['day_onway_stock']
                + $c_data['day_onway_stock'] + $d_data['day_onway_stock']
                + $e_data['day_onway_stock'];
            //goods_num 某个等级产品个数
            $a_plus_data['total_sales_num'] = $data_center_day['A+']['total_sales_num'] ? $data_center_day['A+']['total_sales_num'] : 0;
            $a_plus_data['goods_num'] = $data_center_day['A+']['goods_num'] ? $data_center_day['A+']['goods_num'] : 0;
            $a_plus_data['total_sales_num_rate'] = $total_sales_num != 0 ? round($data_center_day['A+']['total_sales_num'] / $total_sales_num * 100, 2) . '%' : '0%';
            $a_plus_data['goods_num_rate'] = $goods_num != 0 ? round($data_center_day['A+']['goods_num'] / $goods_num * 100, 2) . '%' : '0%';
            $a_plus_data['day_stock_rate'] = $total_stock != 0 ? round($a_plus_data['day_stock'] / $total_stock * 100, 2) . '%' : '0%';
            $a_plus_data['day_onway_stock_rate'] = $total_onway_stock != 0 ? round($a_plus_data['day_onway_stock'] / $total_onway_stock * 100, 2) . '%' : '0%';

            $a_data['total_sales_num'] = $data_center_day['A']['total_sales_num'] ? $data_center_day['A']['total_sales_num'] : 0;
            $a_data['goods_num'] = $data_center_day['A']['goods_num'] ? $data_center_day['A']['goods_num'] : 0;
            $a_data['total_sales_num_rate'] = $total_sales_num != 0 ? round($data_center_day['A']['total_sales_num'] / $total_sales_num * 100, 2) . '%' : '0%';
            $a_data['goods_num_rate'] = $goods_num != 0 ? round($data_center_day['A']['goods_num'] / $goods_num * 100, 2) . '%' : '0%';
            $a_data['day_stock_rate'] = $total_stock != 0 ? round($a_data['day_stock'] / $total_stock * 100, 2) . '%' : '0%';
            $a_data['day_onway_stock_rate'] = $total_onway_stock != 0 ? round($a_data['day_onway_stock'] / $total_onway_stock * 100, 2) . '%' : '0%';

            $b_data['total_sales_num'] = $data_center_day['B']['total_sales_num'] ? $data_center_day['B']['total_sales_num'] : 0;
            $b_data['goods_num'] = $data_center_day['B']['goods_num'] ? $data_center_day['B']['goods_num'] : 0;
            $b_data['total_sales_num_rate'] = $total_sales_num != 0 ? round($data_center_day['B']['total_sales_num'] / $total_sales_num * 100, 2) . '%' : '0%';
            $b_data['goods_num_rate'] = $goods_num != 0 ? round($data_center_day['B']['goods_num'] / $goods_num * 100, 2) . '%' : '0%';
            $b_data['day_stock_rate'] = $total_stock != 0 ? round($b_data['day_stock'] / $total_stock * 100, 2) . '%' : '0%';
            $b_data['day_onway_stock_rate'] = $total_onway_stock != 0 ? round($b_data['day_onway_stock'] / $total_onway_stock * 100, 2) . '%' : '0%';

            $c_plus_data['total_sales_num'] = $data_center_day['C+']['total_sales_num'] ? $data_center_day['C+']['total_sales_num'] : 0;
            $c_plus_data['goods_num'] = $data_center_day['C+']['goods_num'] ? $data_center_day['C+']['goods_num'] : 0;
            $c_plus_data['total_sales_num_rate'] = $total_sales_num != 0 ? round($data_center_day['C+']['total_sales_num'] / $total_sales_num * 100, 2) . '%' : '0%';
            $c_plus_data['goods_num_rate'] = $goods_num != 0 ? round($data_center_day['C+']['goods_num'] / $goods_num * 100, 2) . '%' : '0%';
            $c_plus_data['day_stock_rate'] = $total_stock != 0 ? round($c_plus_data['day_stock'] / $total_stock * 100, 2) . '%' : '0%';
            $c_plus_data['day_onway_stock_rate'] = $total_onway_stock != 0 ? round($c_plus_data['day_onway_stock'] / $total_onway_stock * 100, 2) . '%' : '0%';

            $c_data ['total_sales_num'] = $data_center_day['C']['total_sales_num'] ? $data_center_day['C']['total_sales_num'] : 0;
            $c_data ['goods_num'] = $data_center_day['C']['goods_num'] ? $data_center_day['C']['goods_num'] : 0;
            $c_data ['total_sales_num_rate'] = $total_sales_num != 0 ? round($data_center_day['C']['total_sales_num'] / $total_sales_num * 100, 2) . '%' : '0%';
            $c_data ['goods_num_rate'] = $goods_num != 0 ? round($data_center_day['C']['goods_num'] / $goods_num * 100, 2) . '%' : '0%';
            $c_data['day_stock_rate'] = $total_stock != 0 ? round($c_data['day_stock'] / $total_stock * 100, 2) . '%' : '0%';
            $c_data['day_onway_stock_rate'] = $total_onway_stock != 0 ? round($c_data['day_onway_stock'] / $total_onway_stock * 100, 2) . '%' : '0%';

            $d_data['total_sales_num'] = $data_center_day['D']['total_sales_num'] ? $data_center_day['D']['total_sales_num'] : 0;
            $d_data['goods_num'] = $data_center_day['D']['goods_num'] ? $data_center_day['D']['goods_num'] : 0;
            $d_data['total_sales_num_rate'] = $total_sales_num != 0 ? round($data_center_day['D']['total_sales_num'] / $total_sales_num * 100, 2) . '%' : '0%';
            $d_data['goods_num_rate'] = $goods_num != 0 ? round($data_center_day['D']['goods_num'] / $goods_num * 100, 2) . '%' : '0%';
            $d_data['day_stock_rate'] = $total_stock != 0 ? round($d_data['day_stock'] / $total_stock * 100, 2) . '%' : '0%';
            $d_data['day_onway_stock_rate'] = $total_onway_stock != 0 ? round($d_data['day_onway_stock'] / $total_onway_stock * 100, 2) . '%' : '0%';

            $e_data ['total_sales_num'] = $data_center_day['E']['total_sales_num'] ? $data_center_day['E']['total_sales_num'] : 0;
            $e_data ['goods_num'] = $data_center_day['E']['goods_num'] ? $data_center_day['E']['goods_num'] : 0;
            $e_data ['total_sales_num_rate'] = $total_sales_num != 0 ? round($data_center_day['E']['total_sales_num'] / $total_sales_num * 100, 2) . '%' : '0%';
            $e_data ['goods_num_rate'] = $goods_num != 0 ? round($data_center_day['E']['goods_num'] / $goods_num * 100, 2) . '%' : '0%';
            $e_data['day_stock_rate'] = $total_stock != 0 ? round($e_data['day_stock'] / $total_stock * 100, 2) . '%' : '0%';
            $e_data['day_onway_stock_rate'] = $total_onway_stock != 0 ? round($e_data['day_onway_stock'] / $total_onway_stock * 100, 2) . '%' : '0%';
            // dump($a_plus_data);
            // dump($a_data);
            // dump($b_data);
            // dump($c_plus_data);
            // dump($c_data);
            // dump($d_data);
            // dump($e_data);
        }
        $data = compact('a_plus_data', 'a_data', 'b_data', 'c_plus_data', 'c_data', 'd_data', 'e_data', 'f_data', 'total_sales_num', 'goods_num', 'total_stock', 'total_onway_stock');
        $this->success('', '', $data);
    }

}
