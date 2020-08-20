<?php

namespace app\admin\controller\logistics;

use app\common\controller\Backend;
use think\Cache;
use think\Db;
use think\Exception;
use think\exception\ValidateException;

class LogisticsStatistic extends Backend
{
    protected $model = null;

    /**
     *初始化方法
     *
     * @Description
     * @return void
     * @since 2020/06/09 09:25:38
     * @author lsw
     */
    public function _initialize()
    {
        parent::_initialize();
        $this->model = new \app\admin\model\OrderNodeDetail;
        $this->orderNode = new \app\admin\model\OrderNode;
    }

    /**
     *默认首页
     *
     * @Description
     * @return void
     * @since 2020/06/09 09:25:53
     * @author lsw
     */
    public function index()
    {
        set_time_limit(0);
        ini_set('memory_limit', '512M');
        if ($this->request->isAjax()) {
            $params = $this->request->param();
            if ($params['time']) {
                $timeOne = explode(' ', $params['time']);
                $map['delivery_time'] = ['between', [$timeOne[0] . ' ' . $timeOne[1], $timeOne[3] . ' ' . $timeOne[4]]];
            } else {
                $BeginDate = date('Y-m-01 H:i:s', strtotime(date("Y-m-d")));
                $map['delivery_time'] = ['between', [$BeginDate, date('Y-m-d H:i:s', strtotime("$BeginDate +1 month -1 day"))]];
            }
            $site = $params['platform'] ?: 10;
            if ($site == 10) {
                $whereSite['site'] = ['in', [1, 2, 3]];
            } else {
                $whereSite['site'] = $site;
            }
            $result = $this->logistics_data($site, $map);
            $deliverd_order_num = $result['deliverd_order_num_all'];
            $rate = $result['rate'];
            unset($result['deliverd_order_num_all']);
            unset($result['rate']);
            //所有的物流渠道
            //$column = $this->orderNode->distinct(true)->where($whereSite)->field('shipment_data_type')->whereNotIn('shipment_data_type', ['', 'CPC', 'EYB','China Post','CHINA_EMS','USPS_3'])->column('shipment_data_type');
            $column = $this->orderNode->distinct(true)->where($whereSite)->where($map)->where('track_number is not null')->field('shipment_data_type')->column('shipment_data_type');
            if ('echart1' == $params['key']) {
                //妥投订单数
                foreach ($column as $k => $v) {
                    $columnData[$k]['value'] = $deliverd_order_num[$v];
                    if ('USPS_1' == $v) {
                        $v = '郭伟峰';
                    } elseif ('USPS_2' == $v) {
                        $v = '加诺';
                    } elseif ('USPS_3' == $v) {
                        $v = '杜明明';
                    }
                    $columnData[$k]['name'] = $v;
                }
                $json['column'] = $column;
                $json['columnData'] = $columnData;
                return json(['code' => 1, 'data' => $json]);
            } elseif ('echart3' == $params['key']) {
                $column = [
                    0 => '7天妥投率',
                    1 => '14天妥投率',
                    2 => '20天妥投率',
                    3 => '20天以上妥投率',
                ];
                foreach ($column as $ck => $cv) {
                    $columnData[$ck]['name'] = $cv;
                    if ($rate['total_num'] > 0) {
                        $columnData[$ck]['value'] = round($rate[$ck] / $rate['total_num'] * 100, 2);
                    } else {
                        $columnData[$ck]['value'] = 0;
                    }
                }
                $json['column'] = $column;
                $json['columnData'] = $columnData;
                return json(['code' => 1, 'data' => $json]);
            }
            $this->success('', '', $result);
        }
        //默认当天
        if ($params['time']) {
            $time = explode(' ', $params['time']);
            $map['delivery_time'] = ['between', [$time[0] . ' ' . $time[1], $time[3] . ' ' . $time[4]]];
        } else {
            $BeginDate = date('Y-m-01 H:i:s', strtotime(date("Y-m-d")));
            $map['delivery_time'] = ['between', [$BeginDate, date('Y-m-d H:i:s', strtotime("$BeginDate +1 month -1 day"))]];
        }
        $site = $params['platform'] ?: 10;
        $result = $this->logistics_data($site, $map);
        unset($result['deliverd_order_num_all']);
        unset($result['rate']);
        $orderPlatformList = config('logistics.platform');
        $this->view->assign(compact(
            'orderPlatformList',
            'result'
        ));
        return $this->view->fetch();
    }

    /**
     * 清除缓存
     *
     * @Description
     * @return void
     * @author jhh
     * @since 2020/6/11 10:25
     */
    public function clear_cache()
    {
        if ($this->request->isAjax()) {
            $params = $this->request->param();
            if ($params['time']) {
                $timeOne = explode(' ', $params['time']);
                $map['delivery_time'] = ['between', [$timeOne[0] . ' ' . $timeOne[1], $timeOne[3] . ' ' . $timeOne[4]]];
            } else {
                $BeginDate = date('Y-m-01 H:i:s', strtotime(date("Y-m-d")));
                $map['delivery_time'] = ['between', [$BeginDate, date('Y-m-d H:i:s', strtotime("$BeginDate +1 month -1 day"))]];
            }
            $site = $params['platform'] ?: 10;
            $judge = Cache::has('LogisticsStatistic_logistics_list_' . $site . md5(serialize($map)));
            //判断缓存是否存在
            if ($judge === true) {
                //清除单个缓存文件
                $result = Cache::rm('LogisticsStatistic_logistics_list_' . $site . md5(serialize($map)));
                if ($result === true) {
                    $this->success('清除缓存成功', '');
                } else {
                    $this->error('清除缓存失败', '');
                }
            } else {
                $this->error('当前条件暂无缓存', '');
            }
        }
    }

    /**
     *物流数据
     *
     * @Description
     * @param [type] $site
     * @param [type] $map
     * @return void
     * @since 2020/06/09 09:27:25
     * @author lsw
     */
    // public function logistics_data_yuan($site, $map)
    // {
    //     $arr = Cache::get('LogisticsStatistic_logistics_data_' . $site . md5(serialize($map)));
    //     if ($arr) {
    //         return $arr;
    //     }
    //     if ($site != 10) {
    //         $where['site'] = $whereSite['site'] = $site;
    //     }
    //     $where['node_type'] = 40;
    //     //$shipmentArr['shipment_type'] = ['','CPC','EYB'];
    //     $orderNode['order_node'] = ['egt', 3];
    //     $all_shipment_type = $this->orderNode->where($whereSite)->distinct(true)->field('shipment_type')->whereNotIn('shipment_type', ['', 'CPC', 'EYB'])->cache(86400)->select();
    //     if ($all_shipment_type) {
    //         $arr = $rs = $rate = [];
    //         $rate['serven'] = $rate['fourteen'] = $rate['twenty'] = $rate['gtTwenty'] = 0;
    //         $all_shipment_type = collection($all_shipment_type)->toArray();
    //         //总共的妥投数量,妥投时间
    //         $all_total_num = $all_total_wait_time = 0;
    //         //循环所有的物流渠道
    //         foreach ($all_shipment_type as $k => $v) {
    //             //物流渠道
    //             $arr['shipment_type'][$k] = $v['shipment_type'];
    //             //订单数
    //             //$arr['order_num'][$k]     =  $this->model->where(['shipment_type'=>$v['shipment_type']])->where($map)->where($whereSite)->count("*");
    //             //发货数量
    //             $arr['send_order_num'][$k] = $rs[$v['shipment_type']] = $this->model->where(['shipment_type' => $v['shipment_type']])->where($orderNode)->where($whereSite)->where($map)->count("distinct order_number");
    //             //发货订单号
    //             $all_send_order = $this->model->where(['shipment_type' => $v['shipment_type']])->where($orderNode)->where($whereSite)->where($map)->distinct(true)->column("order_number");
    //             //妥投单数
    //             //$arr['deliverd_order_num'][$k] = $deliverd_order_num = $this->model->where(['shipment_type' => $v['shipment_type']])->where($map)->where($where)->count("*");
    //             //各个日期妥投单数
    //             $date_order = $this->calculate_delievered_num($site, $v['shipment_type'], $map, $all_send_order);
    //             //妥投单数
    //             $arr['deliverd_order_num'][$k] = $deliverd_order_num = $date_order['serven_num'] + $date_order['fourteen_num'] + $date_order['twenty_num'] + $date_order['gtTwenty_num'];
    //             //7天妥投单数
    //             $arr['serven_deliverd_order_num'][$k] = $date_order['serven_num'];
    //             //14天妥投单数
    //             $arr['fourteen_deliverd_order_num'][$k] = $date_order['fourteen_num'];
    //             //20天妥投单数
    //             $arr['twenty_deliverd_order_num'][$k] = $date_order['twenty_num'];
    //             //20天以上妥投单数
    //             $arr['gtTwenty_deliverd_order_num'][$k] = $date_order['gtTwenty_num'];
    //             //总共花费时间(单位s)
    //             //$arr['expend_time'][$k] = $data_order['wait_time'];

    //             //妥投比率
    //             //$arr['deliverd_order_rate'][$k] = $this->calculate_delievered_num($site,$v['shipment_type'],$map);
    //             //妥投率
    //             if ($deliverd_order_num > 0) {
    //                 //7天妥投率
    //                 $arr['serven_deliverd_rate'][$k] = round(($date_order['serven_num'] / $deliverd_order_num) * 100, 2);
    //                 $arr['fourteen_deliverd_rate'][$k] = round(($date_order['fourteen_num'] / $deliverd_order_num) * 100, 2);
    //                 $arr['twenty_deliverd_rate'][$k] = round(($date_order['twenty_num'] / $deliverd_order_num) * 100, 2);
    //                 $arr['gtTwenty_deliverd_rate'][$k] = round(($date_order['gtTwenty_num'] / $deliverd_order_num) * 100, 2);
    //             } else {
    //                 $arr['serven_deliverd_rate'][$k] = 0;
    //                 $arr['fourteen_deliverd_rate'][$k] = 0;
    //                 $arr['twenty_deliverd_rate'][$k] = 0;
    //                 $arr['gtTwenty_deliverd_rate'][$k] = 0;
    //             }
    //             //总共妥投数量
    //             $rate[0] += $date_order['serven_num'];
    //             $rate[1] += $date_order['fourteen_num'];
    //             $rate[2] += $date_order['twenty_num'];
    //             $rate[3] += $date_order['gtTwenty_num'];
    //             $rate['total_num'] += $total_num = $date_order['serven_num'] + $date_order['fourteen_num'] + $date_order['twenty_num'] + $date_order['gtTwenty_num'];
    //             //平均妥投时效
    //             if ($total_num > 0) {
    //                 $arr['avg_deliverd_rate'][$k] = round(($date_order['wait_time'] / $total_num / 86400), 2);
    //             } else {
    //                 $arr['avg_deliverd_rate'][$k] = 0;
    //             }
    //             $all_total_num += $total_num;
    //             $all_total_wait_time += $date_order['wait_time'];
    //         }
    //         //设置发货总数量 妥投订单总数量数为0
    //         $total_send_order_num = $total_deliverd_order_num = 0;
    //         $info = [];
    //         foreach ($arr['shipment_type'] as $ak => $av) {
    //             $info[$ak]['shipment_type'] = $av;
    //             // $info[$ak]['order_num'] = $arr['order_num'][$ak];
    //             $info[$ak]['send_order_num'] = $arr['send_order_num'][$ak];
    //             $info[$ak]['deliverd_order_num'] = $arr['deliverd_order_num'][$ak];
    //             $info[$ak]['serven_deliverd_rate'] = $arr['serven_deliverd_rate'][$ak];
    //             $info[$ak]['fourteen_deliverd_rate'] = $arr['fourteen_deliverd_rate'][$ak];
    //             $info[$ak]['twenty_deliverd_rate'] = $arr['twenty_deliverd_rate'][$ak];
    //             $info[$ak]['gtTwenty_deliverd_rate'] = $arr['gtTwenty_deliverd_rate'][$ak];
    //             $info[$ak]['avg_deliverd_rate'] = $arr['avg_deliverd_rate'][$ak];
    //             //计算总妥投率
    //             if ($arr['send_order_num'][$ak] > 0) {
    //                 $info[$ak]['total_deliverd_rate'] = round($arr['deliverd_order_num'][$ak] / $arr['send_order_num'][$ak] * 100, 2);
    //             } else {
    //                 $info[$ak]['total_deliverd_rate'] = 0;
    //             }
    //             $total_send_order_num += $arr['send_order_num'][$ak];
    //             $total_deliverd_order_num += $arr['deliverd_order_num'][$ak];
    //         }
    //         //求出合计的数据
    //         $info[$ak + 1]['shipment_type'] = '合计';
    //         $info[$ak + 1]['send_order_num'] = $total_send_order_num;
    //         $info[$ak + 1]['deliverd_order_num'] = $total_deliverd_order_num;
    //         //总妥投率
    //         if (0 < $total_send_order_num) {
    //             $info[$ak + 1]['total_deliverd_rate'] = round($total_deliverd_order_num / $total_send_order_num * 100, 2);
    //         } else {
    //             $info[$ak + 1]['total_deliverd_rate'] = 0;
    //         }

    //         //7天妥投率、14天妥投率、20天妥投率、21天妥投率总和
    //         if ($all_total_num > 0) {
    //             $info[$ak + 1]['serven_deliverd_rate'] = round($rate[0] / $all_total_num * 100, 2);
    //             $info[$ak + 1]['fourteen_deliverd_rate'] = round($rate[1] / $all_total_num * 100, 2);
    //             $info[$ak + 1]['twenty_deliverd_rate'] = round($rate[2] / $all_total_num * 100, 2);
    //             $info[$ak + 1]['gtTwenty_deliverd_rate'] = round($rate[3] / $all_total_num * 100, 2);
    //         } else {
    //             $info[$ak + 1]['serven_deliverd_rate'] = 0;
    //             $info[$ak + 1]['fourteen_deliverd_rate'] = 0;
    //             $info[$ak + 1]['twenty_deliverd_rate'] = 0;
    //             $info[$ak + 1]['gtTwenty_deliverd_rate'] = 0;
    //         }
    //         //总共妥投时效
    //         if ($all_total_num > 0) {
    //             $info[$ak + 1]['avg_deliverd_rate'] = round($all_total_wait_time / $all_total_num / 86400, 2);
    //         } else {
    //             $info[$ak + 1]['avg_deliverd_rate'] = 0;
    //         }
    //         $info['deliverd_order_num_all'] = $rs;
    //         $info['rate'] = $rate;
    //     } else {
    //         $info['shipment_type'] = 0;
    //         // $info['order_num'] = 0;
    //         $info['send_order_num'] = 0;
    //         $info['deliverd_order_num'] = 0;
    //         $info['serven_deliverd_rate'] = 0;
    //         $info['avg_deliverd_rate'] = 0;
    //         $info['fourteen_deliverd_rate'] = 0;
    //         $info['twenty_deliverd_rate'] = 0;
    //         $info['gtTwenty_deliverd_rate'] = 0;
    //         $info['total_deliverd_rate'] = 0;
    //         $info['deliverd_order_num_all'] = 0;
    //         $info['rate'] = 0;
    //     }
    //     Cache::set('LogisticsStatistic_logistics_data_' . $site . md5(serialize($map)), $info, 7200);
    //     return $info;
    // }
    /**
     *物流数据原先库
     *
     * @Description
     * @param [type] $site
     * @param [type] $map
     * @return void
     * @since 2020/06/09 09:27:25
     * @author lsw
     */
    // public function logistics_data_detail($site, $map)
    // {
    //     $arr = Cache::get('LogisticsStatistic_logistics_data_' . $site . md5(serialize($map)));
    //     if ($arr) {
    //         return $arr;
    //     }
    //     if ($site != 10) {
    //         $where['site'] = $whereSite['site'] = $site;
    //     }
    //     $where['node_type'] = 40;
    //     //$shipmentArr['shipment_type'] = ['','CPC','EYB'];
    //     $orderNode['order_node'] = ['egt', 3];
    //     $all_shipment_type = $this->orderNode->where($whereSite)->distinct(true)->field('shipment_type')->whereNotIn('shipment_type', ['', 'CPC', 'EYB'])->cache(86400)->select();
    //     if ($all_shipment_type) {
    //         $arr = $rs = $rate = [];
    //         $rate['serven'] = $rate['fourteen'] = $rate['twenty'] = $rate['gtTwenty'] = 0;
    //         $all_shipment_type = collection($all_shipment_type)->toArray();
    //         //总共的妥投数量,妥投时间
    //         $all_total_num = $all_total_wait_time = 0;
    //         //求出新的方法的结果
    //         $shipment_date_order = $this->calculate_all_delievered_num($all_shipment_type, $site, $map);
    //         //循环所有的物流渠道
    //         foreach ($all_shipment_type as $k => $v) {
    //             //物流渠道
    //             $arr['shipment_type'][$k] = $v['shipment_type'];
    //             //订单数
    //             //$arr['order_num'][$k]     =  $this->model->where(['shipment_type'=>$v['shipment_type']])->where($map)->where($whereSite)->count("*");
    //             //发货数量
    //             $arr['send_order_num'][$k] = $rs[$v['shipment_type']] = $this->model->where(['shipment_type' => $v['shipment_type']])->where($orderNode)->where($whereSite)->where($map)->count("distinct order_number");
    //             //发货订单号
    //             $all_send_order = $this->model->where(['shipment_type' => $v['shipment_type']])->where($orderNode)->where($whereSite)->where($map)->distinct(true)->column("order_number");
    //             //妥投单数
    //             //$arr['deliverd_order_num'][$k] = $deliverd_order_num = $this->model->where(['shipment_type' => $v['shipment_type']])->where($map)->where($where)->count("*");
    //             //各个日期妥投单数
    //             //$date_order = $this->calculate_delievered_num($site, $v['shipment_type'], $map, $all_send_order);
    //             //妥投单数
    //             $arr['deliverd_order_num'][$k] = $deliverd_order_num = $shipment_date_order[$v['shipment_type']]['serven_num'] + $shipment_date_order[$v['shipment_type']]['fourteen_num'] + $shipment_date_order[$v['shipment_type']]['twenty_num'] + $shipment_date_order[$v['shipment_type']]['gtTwenty_num'];
    //             //7天妥投单数
    //             $arr['serven_deliverd_order_num'][$k] = $shipment_date_order[$v['shipment_type']]['serven_num'];
    //             //14天妥投单数
    //             $arr['fourteen_deliverd_order_num'][$k] = $shipment_date_order[$v['shipment_type']]['fourteen_num'];
    //             //20天妥投单数
    //             $arr['twenty_deliverd_order_num'][$k] = $shipment_date_order[$v['shipment_type']]['twenty_num'];
    //             //20天以上妥投单数
    //             $arr['gtTwenty_deliverd_order_num'][$k] = $shipment_date_order[$v['shipment_type']]['gtTwenty_num'];
    //             //总共花费时间(单位s)
    //             //$arr['expend_time'][$k] = $data_order['wait_time'];

    //             //妥投比率
    //             //$arr['deliverd_order_rate'][$k] = $this->calculate_delievered_num($site,$v['shipment_type'],$map);
    //             //妥投率
    //             if ($deliverd_order_num > 0) {
    //                 //7天妥投率
    //                 $arr['serven_deliverd_rate'][$k] = round(($shipment_date_order[$v['shipment_type']]['serven_num'] / $deliverd_order_num) * 100, 2);
    //                 $arr['fourteen_deliverd_rate'][$k] = round(($shipment_date_order[$v['shipment_type']]['fourteen_num'] / $deliverd_order_num) * 100, 2);
    //                 $arr['twenty_deliverd_rate'][$k] = round(($shipment_date_order[$v['shipment_type']]['twenty_num'] / $deliverd_order_num) * 100, 2);
    //                 $arr['gtTwenty_deliverd_rate'][$k] = round(($shipment_date_order[$v['shipment_type']]['gtTwenty_num'] / $deliverd_order_num) * 100, 2);
    //             } else {
    //                 $arr['serven_deliverd_rate'][$k] = 0;
    //                 $arr['fourteen_deliverd_rate'][$k] = 0;
    //                 $arr['twenty_deliverd_rate'][$k] = 0;
    //                 $arr['gtTwenty_deliverd_rate'][$k] = 0;
    //             }
    //             //总共妥投数量
    //             $rate[0] += $date_order['serven_num'];
    //             $rate[1] += $date_order['fourteen_num'];
    //             $rate[2] += $date_order['twenty_num'];
    //             $rate[3] += $date_order['gtTwenty_num'];
    //             $rate['total_num'] += $total_num = $shipment_date_order[$v['shipment_type']]['serven_num'] + $shipment_date_order[$v['shipment_type']]['fourteen_num'] + $shipment_date_order[$v['shipment_type']]['twenty_num'] + $shipment_date_order[$v['shipment_type']]['gtTwenty_num'];
    //             //平均妥投时效
    //             if ($total_num > 0) {
    //                 $arr['avg_deliverd_rate'][$k] = round(($shipment_date_order[$v['shipment_type']]['wait_time'] / $total_num / 86400), 2);
    //             } else {
    //                 $arr['avg_deliverd_rate'][$k] = 0;
    //             }
    //             $all_total_num += $total_num;
    //             $all_total_wait_time += $shipment_date_order[$v['shipment_type']]['wait_time'];
    //         }
    //         //设置发货总数量 妥投订单总数量数为0
    //         $total_send_order_num = $total_deliverd_order_num = 0;
    //         $info = [];
    //         foreach ($arr['shipment_type'] as $ak => $av) {
    //             $info[$ak]['shipment_type'] = $av;
    //             // $info[$ak]['order_num'] = $arr['order_num'][$ak];
    //             $info[$ak]['send_order_num'] = $arr['send_order_num'][$ak];
    //             $info[$ak]['deliverd_order_num'] = $arr['deliverd_order_num'][$ak];
    //             $info[$ak]['serven_deliverd_rate'] = $arr['serven_deliverd_rate'][$ak];
    //             $info[$ak]['fourteen_deliverd_rate'] = $arr['fourteen_deliverd_rate'][$ak];
    //             $info[$ak]['twenty_deliverd_rate'] = $arr['twenty_deliverd_rate'][$ak];
    //             $info[$ak]['gtTwenty_deliverd_rate'] = $arr['gtTwenty_deliverd_rate'][$ak];
    //             $info[$ak]['avg_deliverd_rate'] = $arr['avg_deliverd_rate'][$ak];
    //             //计算总妥投率
    //             if ($arr['send_order_num'][$ak] > 0) {
    //                 $info[$ak]['total_deliverd_rate'] = round($arr['deliverd_order_num'][$ak] / $arr['send_order_num'][$ak] * 100, 2);
    //             } else {
    //                 $info[$ak]['total_deliverd_rate'] = 0;
    //             }
    //             $total_send_order_num += $arr['send_order_num'][$ak];
    //             $total_deliverd_order_num += $arr['deliverd_order_num'][$ak];
    //         }
    //         //求出合计的数据
    //         $info[$ak + 1]['shipment_type'] = '合计';
    //         $info[$ak + 1]['send_order_num'] = $total_send_order_num;
    //         $info[$ak + 1]['deliverd_order_num'] = $total_deliverd_order_num;
    //         //总妥投率
    //         if (0 < $total_send_order_num) {
    //             $info[$ak + 1]['total_deliverd_rate'] = round($total_deliverd_order_num / $total_send_order_num * 100, 2);
    //         } else {
    //             $info[$ak + 1]['total_deliverd_rate'] = 0;
    //         }

    //         //7天妥投率、14天妥投率、20天妥投率、21天妥投率总和
    //         if ($all_total_num > 0) {
    //             $info[$ak + 1]['serven_deliverd_rate'] = round($rate[0] / $all_total_num * 100, 2);
    //             $info[$ak + 1]['fourteen_deliverd_rate'] = round($rate[1] / $all_total_num * 100, 2);
    //             $info[$ak + 1]['twenty_deliverd_rate'] = round($rate[2] / $all_total_num * 100, 2);
    //             $info[$ak + 1]['gtTwenty_deliverd_rate'] = round($rate[3] / $all_total_num * 100, 2);
    //         } else {
    //             $info[$ak + 1]['serven_deliverd_rate'] = 0;
    //             $info[$ak + 1]['fourteen_deliverd_rate'] = 0;
    //             $info[$ak + 1]['twenty_deliverd_rate'] = 0;
    //             $info[$ak + 1]['gtTwenty_deliverd_rate'] = 0;
    //         }
    //         //总共妥投时效
    //         if ($all_total_num > 0) {
    //             $info[$ak + 1]['avg_deliverd_rate'] = round($all_total_wait_time / $all_total_num / 86400, 2);
    //         } else {
    //             $info[$ak + 1]['avg_deliverd_rate'] = 0;
    //         }
    //         $info['deliverd_order_num_all'] = $rs;
    //         $info['rate'] = $rate;
    //     } else {
    //         $info['shipment_type'] = 0;
    //         // $info['order_num'] = 0;
    //         $info['send_order_num'] = 0;
    //         $info['deliverd_order_num'] = 0;
    //         $info['serven_deliverd_rate'] = 0;
    //         $info['avg_deliverd_rate'] = 0;
    //         $info['fourteen_deliverd_rate'] = 0;
    //         $info['twenty_deliverd_rate'] = 0;
    //         $info['gtTwenty_deliverd_rate'] = 0;
    //         $info['total_deliverd_rate'] = 0;
    //         $info['deliverd_order_num_all'] = 0;
    //         $info['rate'] = 0;
    //     }
    //     Cache::set('LogisticsStatistic_logistics_data_' . $site . md5(serialize($map)), $info, 7200);
    //     return $info;
    // }
    /**
     *物流数据最新
     *
     * @Description
     * @param [type] $site
     * @param [type] $map
     * @return void
     * @since 2020/06/09 09:27:25
     * @author lsw
     */
    public function logistics_data($site, $map)
    {
        $arr = Cache::get('LogisticsStatistic_logistics_list_' . $site . md5(serialize($map)));
        if ($arr) {
            return $arr;
        }
        if ($site != 10) {
            $where['site'] = $whereSite['site'] = $site;
        }
        $where['node_type'] = 40;
        //7天妥投时间
        $serven_time_out = config('logistics.delievered_time_out')['serven'];
        //14天妥投时间
        $fourteen_time_out = config('logistics.delievered_time_out')['fourteen'];
        //20天妥投时间
        $twenty_time_out = config('logistics.delievered_time_out')['twenty'];
        //$orderNode['order_node'] = ['egt', 3];
        $orderNode['node_type'] = ['egt', 7];
        //$all_shipment_type = $this->orderNode->where($whereSite)->distinct(true)->field('shipment_data_type')->whereNotIn('shipment_data_type', ['', 'CPC', 'EYB','China Post','CHINA_EMS','USPS_3'])->select();
        $all_shipment_type = $this->orderNode->where($whereSite)->where($map)->where('track_number is not null')->distinct(true)->field('shipment_data_type')->select();
        if ($all_shipment_type) {
            $arr = $rs = $rate = [];
            //$rate['serven'] = $rate['fourteen'] = $rate['twenty'] = $rate['gtTwenty'] = 0;
            $all_shipment_type = collection($all_shipment_type)->toArray();
            //总共的妥投数量,妥投时间
            $all_total_num = $all_total_wait_time = 0;
            //求出新的方法的结果
            //$shipment_date_order = $this->calculate_all_delievered_num($all_shipment_type, $site, $map);
            //循环所有的物流渠道
            foreach ($all_shipment_type as $k => $v) {
                //物流渠道
                $arr['shipment_data_type'][$k] = $v['shipment_data_type'];
                //发货订单号
                $delievered_order = $this->orderNode->where(['shipment_data_type' => $v['shipment_data_type']])->where($orderNode)->where($whereSite)->where($map)->field('order_number,delivery_time,signing_time')->select();
                $delievered_order = collection($delievered_order)->toArray();
                if (!$delievered_order) {
                    $arr['send_order_num'][$k] = 0;
                    $arr['deliverd_order_num'][$k] = 0;
                    $arr['serven_deliverd_rate'][$k] = 0;
                    $arr['fourteen_deliverd_rate'][$k] = 0;
                    $arr['twenty_deliverd_rate'][$k] = 0;
                    $arr['gtTwenty_deliverd_rate'][$k] = 0;
                    $arr['avg_deliverd_rate'][$k] = 0;
                    $rs[$v['shipment_data_type']] = 0;
                    continue;
                }
                //发货数量
                $send_order_num = count(array_column($delievered_order, 'order_number'));

                $serven_num = $fourteen_num = $twenty_num = $gtTwenty_num = $wait_time = 0;
                foreach ($delievered_order as $key => $val) {
                    /**
                     * 判断有签收时间，并且签收时间大于发货时间，并且签收时间大于发货时间两天 则计算正常发货数量  否则不计算在内
                     */
                    if (!empty($val['signing_time']) && $val['signing_time'] > $val['delivery_time'] && ((strtotime($val['signing_time']) - strtotime($val['delivery_time'])) / 86400) > 2) {
                        $distance_time = strtotime($val['signing_time']) - strtotime($val['delivery_time']);
                        $wait_time += $distance_time;
                        //时间小于7天的
                        if ($serven_time_out >= $distance_time) {
                            $serven_num++;
                        } elseif (($serven_time_out < $distance_time) && ($distance_time <= $fourteen_time_out)) {
                            $fourteen_num++;
                        } elseif (($fourteen_time_out < $distance_time) && ($distance_time <= $twenty_time_out)) {
                            $twenty_num++;
                        } else {
                            $gtTwenty_num++;
                        }
                    } elseif ($val['signing_time']) {
                        $send_order_num--;
                    }
                }

                $arr['send_order_num'][$k] = $rs[$v['shipment_data_type']] = $send_order_num;

                //妥投单数
                $arr['deliverd_order_num'][$k] = $deliverd_order_num = $serven_num + $fourteen_num + $twenty_num + $gtTwenty_num;
                //7天妥投单数
                $arr['serven_deliverd_order_num'][$k] = $serven_num;
                //14天妥投单数
                $arr['fourteen_deliverd_order_num'][$k] = $fourteen_num;
                //20天妥投单数
                $arr['twenty_deliverd_order_num'][$k] = $twenty_num;
                //20天以上妥投单数
                $arr['gtTwenty_deliverd_order_num'][$k] = $gtTwenty_num;
                //总共花费时间(单位s)
                //$arr['expend_time'][$k] = $data_order['wait_time'];

                //妥投比率
                //$arr['deliverd_order_rate'][$k] = $this->calculate_delievered_num($site,$v['shipment_type'],$map);
                //妥投率
                if ($deliverd_order_num > 0) {
                    //7天妥投率
                    $arr['serven_deliverd_rate'][$k] = round(($serven_num / $deliverd_order_num) * 100, 2);
                    $arr['fourteen_deliverd_rate'][$k] = round(($fourteen_num / $deliverd_order_num) * 100, 2);
                    $arr['twenty_deliverd_rate'][$k] = round(($twenty_num / $deliverd_order_num) * 100, 2);
                    $arr['gtTwenty_deliverd_rate'][$k] = round(($gtTwenty_num / $deliverd_order_num) * 100, 2);
                } else {
                    $arr['serven_deliverd_rate'][$k] = 0;
                    $arr['fourteen_deliverd_rate'][$k] = 0;
                    $arr['twenty_deliverd_rate'][$k] = 0;
                    $arr['gtTwenty_deliverd_rate'][$k] = 0;
                }
                //总共妥投数量
                $rate[0] += $serven_num;
                $rate[1] += $fourteen_num;
                $rate[2] += $twenty_num;
                $rate[3] += $gtTwenty_num;
                $rate['total_num'] += $deliverd_order_num;
                //平均妥投时效
                if ($deliverd_order_num > 0) {
                    $arr['avg_deliverd_rate'][$k] = round(($wait_time / $deliverd_order_num / 86400), 2);
                } else {
                    $arr['avg_deliverd_rate'][$k] = 0;
                }
                $all_total_num += $deliverd_order_num;
                $all_total_wait_time += $wait_time;
            }
            //设置发货总数量 妥投订单总数量数为0
            $total_send_order_num = $total_deliverd_order_num = 0;
            $info = [];
            foreach ($arr['shipment_data_type'] as $ak => $av) {
                if ('USPS_1' == $av) {
                    $av = '郭伟峰';
                } elseif ('USPS_2' == $av) {
                    $av = '加诺';
                } elseif ('USPS_3' == $av) {
                    $av = '杜明明';
                }
                $info[$ak]['shipment_data_type'] = $av;
                $info[$ak]['send_order_num'] = $arr['send_order_num'][$ak];
                $info[$ak]['deliverd_order_num'] = $arr['deliverd_order_num'][$ak];
                $info[$ak]['serven_deliverd_rate'] = $arr['serven_deliverd_rate'][$ak];
                $info[$ak]['fourteen_deliverd_rate'] = $arr['fourteen_deliverd_rate'][$ak];
                $info[$ak]['twenty_deliverd_rate'] = $arr['twenty_deliverd_rate'][$ak];
                $info[$ak]['gtTwenty_deliverd_rate'] = $arr['gtTwenty_deliverd_rate'][$ak];
                $info[$ak]['avg_deliverd_rate'] = $arr['avg_deliverd_rate'][$ak];
                //计算总妥投率
                if ($arr['send_order_num'][$ak] > 0) {
                    $info[$ak]['total_deliverd_rate'] = round($arr['deliverd_order_num'][$ak] / $arr['send_order_num'][$ak] * 100, 2);
                } else {
                    $info[$ak]['total_deliverd_rate'] = 0;
                }
                $total_send_order_num += $arr['send_order_num'][$ak];
                $total_deliverd_order_num += $arr['deliverd_order_num'][$ak];
            }
            //求出合计的数据
            $info[$ak + 1]['shipment_data_type'] = '合计';
            $info[$ak + 1]['send_order_num'] = $total_send_order_num;
            $info[$ak + 1]['deliverd_order_num'] = $total_deliverd_order_num;
            //总妥投率
            if (0 < $total_send_order_num) {
                $info[$ak + 1]['total_deliverd_rate'] = round($total_deliverd_order_num / $total_send_order_num * 100, 2);
            } else {
                $info[$ak + 1]['total_deliverd_rate'] = 0;
            }

            //7天妥投率、14天妥投率、20天妥投率、21天妥投率总和
            if ($all_total_num > 0) {
                $info[$ak + 1]['serven_deliverd_rate'] = round($rate[0] / $all_total_num * 100, 2);
                $info[$ak + 1]['fourteen_deliverd_rate'] = round($rate[1] / $all_total_num * 100, 2);
                $info[$ak + 1]['twenty_deliverd_rate'] = round($rate[2] / $all_total_num * 100, 2);
                $info[$ak + 1]['gtTwenty_deliverd_rate'] = round($rate[3] / $all_total_num * 100, 2);
            } else {
                $info[$ak + 1]['serven_deliverd_rate'] = 0;
                $info[$ak + 1]['fourteen_deliverd_rate'] = 0;
                $info[$ak + 1]['twenty_deliverd_rate'] = 0;
                $info[$ak + 1]['gtTwenty_deliverd_rate'] = 0;
            }
            //总共妥投时效
            if ($all_total_num > 0) {
                $info[$ak + 1]['avg_deliverd_rate'] = round($all_total_wait_time / $all_total_num / 86400, 2);
            } else {
                $info[$ak + 1]['avg_deliverd_rate'] = 0;
            }
            $info['deliverd_order_num_all'] = $rs;
            $info['rate'] = $rate;
        } else {
            $info['shipment_data_type'] = 0;
            // $info['order_num'] = 0;
            $info['send_order_num'] = 0;
            $info['deliverd_order_num'] = 0;
            $info['serven_deliverd_rate'] = 0;
            $info['avg_deliverd_rate'] = 0;
            $info['fourteen_deliverd_rate'] = 0;
            $info['twenty_deliverd_rate'] = 0;
            $info['gtTwenty_deliverd_rate'] = 0;
            $info['total_deliverd_rate'] = 0;
            $info['deliverd_order_num_all'] = 0;
            $info['rate'] = 0;
        }
        Cache::set('LogisticsStatistic_logistics_list_' . $site . md5(serialize($map)), $info, 7200);
        return $info;
    }
    /**
     * 计算某个物流渠道下某个时间段的妥投单数
     *
     * @Description
     * @param [type] $shipment_type
     * @param [type] $type 1 7天 2 14天  3 20天
     * @return void
     * @since 2020/06/09 14:13:34
     * @author lsw
     */
    // public function calculate_delievered_num($site, $shipment_type, $map, $all_send_order)
    // {
    //     if (10 != $site) {
    //         $where['site'] = $whereSite['site'] = $site;
    //     }
    //     $where['node_type'] = 40;
    //     $whereSite['node_type'] = 8;
    //     //求出这段时间内所有的发货订单号

    //     //7天妥投时间
    //     $serven_time_out = config('logistics.delievered_time_out')['serven'];
    //     //14天妥投时间
    //     $fourteen_time_out = config('logistics.delievered_time_out')['fourteen'];
    //     //20天妥投时间
    //     $twenty_time_out = config('logistics.delievered_time_out')['twenty'];
    //     //求出所有的妥投订单号
    //     //$all_order = $this->model->where(['shipment_type' => $shipment_type])->where($map)->where($where)->where('order_number','in',$all_send_order)->column('order_number');
    //     //求出所有的妥投订单号妥投时间
    //     $delievered_order = $this->model->where(['shipment_type' => $shipment_type])->where($map)->where($where)->where('order_number', 'in', $all_send_order)->field('order_id,order_number,create_time')->select();
    //     if (!$delievered_order) {
    //         return [
    //             'serven_num' => 0,
    //             'fourteen_num' => 0,
    //             'twenty_num' => 0,
    //             'gtTwenty_num' => 0,
    //             'wait_time' => 0
    //         ];
    //     }
    //     $delievered_order = collection($delievered_order)->toArray();
    //     $all_order = array_column($delievered_order, 'order_number');

    //     //求出所有妥投订单号出库时间
    //     $out_stock_order = $this->model->where($whereSite)->where('order_number', 'in', $all_order)->column('order_number,create_time');
    //     $serven_num = $fourteen_num = $twenty_num = $gtTwenty_num = $wait_time = 0;
    //     foreach ($delievered_order as $key => $val) {
    //         if (array_key_exists($val['order_number'], $out_stock_order)) {
    //             $distance_time = strtotime($val['create_time']) - strtotime($out_stock_order[$val['order_number']]);
    //             $wait_time += $distance_time;
    //             //时间小于7天的
    //             if ($serven_time_out >= $distance_time) {
    //                 $serven_num++;
    //             } elseif (($serven_time_out < $distance_time) && ($distance_time <= $fourteen_time_out)) {
    //                 $fourteen_num++;
    //             } elseif (($fourteen_time_out < $distance_time) && ($distance_time <= $twenty_time_out)) {
    //                 $twenty_num++;
    //             } else {
    //                 $gtTwenty_num++;
    //             }
    //         }
    //     }
    //     $arr = [
    //         'serven_num' => $serven_num,
    //         'fourteen_num' => $fourteen_num,
    //         'twenty_num' => $twenty_num,
    //         'gtTwenty_num' => $gtTwenty_num,
    //         'wait_time' => $wait_time
    //     ];
    //     return $arr;
    // }
    // /**
    //  * 计算所有物流渠道下某个时间段的妥投单数
    //  *
    //  * @Description
    //  * @param [type] $shipment_type
    //  * @param [type] $type 1 7天 2 14天  3 20天
    //  * @return void
    //  * @since 2020/06/09 14:13:34
    //  * @author lsw
    //  */
    // public function calculate_all_delievered_num($all_shipment_type, $site, $map)
    // {

    //     if (10 != $site) {
    //         $where['site'] = $whereSite['site'] = $site;
    //     }
    //     $where['node_type'] = 40;
    //     $whereSite['node_type'] = 8;
    //     $orderNode['order_node'] = ['egt', 3];
    //     //求出这段时间内所有的发货订单号

    //     //7天妥投时间
    //     $serven_time_out = config('logistics.delievered_time_out')['serven'];
    //     //14天妥投时间
    //     $fourteen_time_out = config('logistics.delievered_time_out')['fourteen'];
    //     //20天妥投时间
    //     $twenty_time_out = config('logistics.delievered_time_out')['twenty'];
    //     //如果存在平台的话
    //     $arr = [];
    //     if($all_shipment_type){
    //         //循环所有的平台
    //         foreach($all_shipment_type as $k => $v){
    //             $all_send_order = $this->model->where(['shipment_type' => $v['shipment_type']])->where($orderNode)->where($whereSite)->where($map)->distinct(true)->column("order_number");
    //             $delievered_order = $this->model->where(['shipment_type' => $shipment_type])->where($map)->where($where)->where('order_number', 'in', $all_send_order)->field('order_id,order_number,create_time')->select();
    //             if (!$delievered_order) {
    //                 $arr[$v['shipment_type']] = [
    //                     'serven_num' => 0,
    //                     'fourteen_num' => 0,
    //                     'twenty_num' => 0,
    //                     'gtTwenty_num' => 0,
    //                     'wait_time' => 0
    //                 ];
    //                 continue;
    //             }
    //             $delievered_order = collection($delievered_order)->toArray();
    //             $all_order = array_column($delievered_order, 'order_number');
    //             //求出所有妥投订单号出库时间
    //             $out_stock_order = $this->model->where($whereSite)->where('order_number', 'in', $all_order)->column('order_number,create_time');
    //             $serven_num = $fourteen_num = $twenty_num = $gtTwenty_num = $wait_time = 0;
    //             foreach ($delievered_order as $key => $val) {
    //                 if (array_key_exists($val['order_number'], $out_stock_order)) {
    //                     $distance_time = strtotime($val['create_time']) - strtotime($out_stock_order[$val['order_number']]);
    //                     $wait_time += $distance_time;
    //                     //时间小于7天的
    //                     if ($serven_time_out >= $distance_time) {
    //                         $serven_num++;
    //                     } elseif (($serven_time_out < $distance_time) && ($distance_time <= $fourteen_time_out)) {
    //                         $fourteen_num++;
    //                     } elseif (($fourteen_time_out < $distance_time) && ($distance_time <= $twenty_time_out)) {
    //                         $twenty_num++;
    //                     } else {
    //                         $gtTwenty_num++;
    //                     }
    //                 }
    //             }
    //             $arr[$v['shipment_type']] = [
    //                 'serven_num' => $serven_num,
    //                 'fourteen_num' => $fourteen_num,
    //                 'twenty_num' => $twenty_num,
    //                 'gtTwenty_num' => $gtTwenty_num,
    //                 'wait_time' => $wait_time
    //             ];
    //         }
    //     }else{
    //         $arr = [
    //             'serven_num' => 0,
    //             'fourteen_num' => 0,
    //             'twenty_num' => 0,
    //             'gtTwenty_num' => 0,
    //             'wait_time' => 0
    //         ];
    //     }
    //     return $arr;
    // }
}
