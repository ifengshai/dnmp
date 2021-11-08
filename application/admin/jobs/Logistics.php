<?php
/**
 * logistics.php
 * @author wangpenglei
 * @date   2021/7/13 17:49
 */

namespace app\admin\jobs;

use app\admin\model\order\order\NewOrderProcess;
use app\service\elasticsearch\EsFormatData;
use app\service\elasticsearch\EsService;
use Elasticsearch\ClientBuilder;
use fast\Http;
use think\Db;
use think\Env;
use think\Log;
use think\queue\Job;

class Logistics
{
    protected $apiKey = 'F26A807B685D794C676FA3CC76567035';
    protected $str1 = 'Arrived Shipping Partner Facility, Awaiting Item.';
    protected $str2 = 'Delivered to Air Transport.';
    protected $str3 = 'In Transit to Next Facility.';
    protected $str4 = 'Arrived in the Final Destination Country.';
    protected $str30 = 'Out for delivery or arrived at local facility, you may schedule for delivery or pickup. Please be aware of the collection deadline.'; //到达待取
    protected $str35 = 'Attempted for delivery but failed, this may due to several reasons. Please contact the carrier for clarification.'; //投递失败
    protected $str40 = 'Delivered successfully.'; //投递成功
    protected $str50 = 'Item might undergo unusual shipping condition, this may due to several reasons, most likely item was returned to sender, customs issue etc.'; //可能异常

    public function __construct()
    {
        try {
            //es配置
            $params = [
                Env::get('es.es_host', '127.0.0.1:9200'),
            ];
            //获取es的实例
            $this->esClient = ClientBuilder::create()->setHosts($params)->build();
            $this->esService = new EsService($this->esClient);
            $this->esFormatData = new EsFormatData();
        } catch (\Exception $e) {
            Log::error('es:' . $e->getMessage());
        }
    }

    /**
     * fire方法是消息队列默认调用的方法
     *
     * @param Job         $job  当前的任务对象
     * @param array|mixed $data 发布任务时自定义的数据
     */
    public function fire(Job $job, $data)
    {
        try {
            $isJobDone = $this->doTrackReturn($data);
            if ($isJobDone) {
                //如果任务执行成功， 记得删除任务
                $job->delete();
            } else {
                if ($job->attempts() > 3) {
                    //通过这个方法可以检查这个任务已经重试了几次了
                    $job->delete();
                }
            }
        } catch (\Throwable $throwable) {
            Log::error(__CLASS__ . $throwable->getMessage() . '-' . $throwable->getFile() . '-' . $throwable->getLine());
            $job->delete();
        }
    }

    /*
    * 17track物流查询webhook访问方法
    * */
    public function doTrackReturn($data)
    {
        $track_arr = $data;
        //妥投给magento接口
        if ($track_arr['event'] != 'TRACKING_STOPPED') {
            $order_node = Db::name('order_node')->field('site,order_id,order_number,shipment_type,shipment_data_type')->where('track_number', $track_arr['data']['number'])->find();
            Log::write("输出订单号fa_shipment".$track_arr['data']['number']);
            if (empty($order_node)) {
                $count = Db::connect('database.db_mojing_order')->table('fa_shipment')->where('shipment_num', $track_arr['data']['number'])->where('is_del', 1)->count();
                Log::write("输出订单号fa_shipment".$track_arr['data']['number']);
                if ($count > 0) {
                    $courier_status = $track_arr['data']['track']['e'];
                    $shipment_last_msg = $track_arr['data']['track']['z0']['z'];

                    $params = [];
                    $params['courier_status'] = $courier_status;
                    $params['shipment_last_msg'] = $shipment_last_msg;
                    $params['update_at'] = time();
                    if ($courier_status == 40) {
                        $signing_time = strtotime($track_arr['data']['track']['z0']['a']);
                        $params['shipment_signing_time'] = $signing_time;
                    }
                    Log::write("输出订单号fa_shipment,执行数据参数为：".json_encode($params));

                    Db::connect('database.db_mojing_order')->table('fa_shipment')->where('shipment_num', $track_arr['data']['number'])
                        ->update($params);
                }

                return true;
            }

            if ($track_arr['data']['track']['e'] == 40 && in_array($order_node['site'], [1, 2, 3, 5])) {
                //更新加工表中订单妥投状态
                $process = new NewOrderProcess;
                $process->where('increment_id', $order_node['order_number'])->update(['is_tracking' => 5]);
                if ($order_node['site'] == 1) {
                    $url = config('url.zeelool_url') . 'magic/order/updateOrderStatus';
                } elseif ($order_node['site'] == 2) {
                    $url = config('url.voogueme_url') . 'magic/order/updateOrderStatus';
                } elseif ($order_node['site'] == 5) {
                    $url = config('url.new_wesee_url') . 'api/delivered';
                }  elseif ($order_node['site'] == 3) {
                    $url = config('url.nihao_url') . 'api/mj/delivered';
                }
                $value['increment_id'] = $order_node['order_number'];
                Http::post($url, $value);
            }
            $add = [];
            $add['site'] = $order_node['site'];
            $add['order_id'] = $order_node['order_id'];
            $add['order_number'] = $order_node['order_number'];
            $add['shipment_type'] = $order_node['shipment_type'];
            $add['shipment_data_type'] = $order_node['shipment_data_type'];
            $add['track_number'] = $track_arr['data']['number'];

            return $this->total_track_data($track_arr['data']['track'], $add);

        } else {
            return true;
        }

    }

    /**
     * @param $data
     * @param $add
     *
     * @return bool
     * @throws \think\Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * @throws \think\exception\PDOException
     * @author wpl
     * @Date   2021/07/13
     */
    public function total_track_data($data, $add): bool
    {
        $trackdetail = $data['z1'] ? array_reverse($data['z1']) : [];
        $all_num = count($trackdetail);
        if (empty($trackdetail)) {
            return false;
        }

        $order_node_detail['order_node'] = 3;
        $order_node_detail['handle_user_id'] = 0;
        $order_node_detail['handle_user_name'] = 'system';
        $order_node_detail['site'] = $add['site'];
        $order_node_detail['order_id'] = $add['order_id'];
        $order_node_detail['order_number'] = $add['order_number'];
        $order_node_detail['shipment_type'] = $add['shipment_type'];
        $order_node_detail['shipment_data_type'] = $add['shipment_data_type'];
        $order_node_detail['track_number'] = $add['track_number'];

        //获取物流明细表中的描述
        $contents = Db::name('order_node_courier')->where('track_number', $add['track_number'])->column('content');

        foreach ($trackdetail as $k => $v) {
            if (!in_array($v['z'], $contents)) {
                $add['create_time'] = $v['a'];
                $add['content'] = $v['z'];
                $add['courier_status'] = $data['e'];
                Db::name('order_node_courier')->insert($add); //插入物流日志表

            }

            if ($k == 1) {
                //更新上网
                $order_node_date = Db::name('order_node')->where(['track_number' => $add['track_number'], 'shipment_type' => $add['shipment_type']])->find();
                //推送的时间小于发货时间跳过
                if ($v['a'] < $order_node_date['delivery_time']){
                    continue;
                }
                if ($order_node_date['order_node'] == 2 && $order_node_date['node_type'] == 7) {
                    $update_order_node['order_node'] = 3;
                    $update_order_node['node_type'] = 8;
                    $update_order_node['update_time'] = $v['a'];
                    Db::name('order_node')->where('id', $order_node_date['id'])->update($update_order_node); //更新主表状态
                    $arr = [
                        'id'         => $order_node_date['id'],
                        'order_node' => 3,
                        'node_type'  => 8,
                    ];

                    $order_node_detail['node_type'] = 8;
                    $order_node_detail['content'] = $this->str1;
                    $order_node_detail['create_time'] = $v['a'];

                    //查询子表是否已存在此节点数据
                    $count = Db::name('order_node_detail')->where(['order_node' => 3, 'node_type' => 8, 'track_number' => $add['track_number']])->count();
                    if ($count > 0) {
                        Db::name('order_node_detail')
                            ->where(['order_node' => 3, 'node_type' => 8, 'track_number' => $add['track_number']])
                            ->update($order_node_detail); //插入节点字表
                    } else {
                        Db::name('order_node_detail')->insert($order_node_detail); //插入节点字表
                    }

                    $this->esService->updateEs('mojing_track', $arr);
                }
            }
            if ($k == 2) {
                //更新运输
                $order_node_date = Db::name('order_node')->where(['track_number' => $add['track_number'], 'shipment_type' => $add['shipment_type']])->find();
                if ($order_node_date['order_node'] == 3 && $order_node_date['node_type'] == 8) {
                    $update_order_node['order_node'] = 3;
                    $update_order_node['node_type'] = 10;
                    $update_order_node['update_time'] = $v['a'];
                    Db::name('order_node')->where('id', $order_node_date['id'])->update($update_order_node); //更新主表状态

                    $arr = [
                        'id'         => $order_node_date['id'],
                        'order_node' => 3,
                        'node_type'  => 10,
                    ];

                    $order_node_detail['node_type'] = 10;
                    $order_node_detail['content'] = $this->str3;
                    $order_node_detail['create_time'] = $v['a'];

                    //查询子表是否已存在此节点数据
                    $count = Db::name('order_node_detail')->where(['order_node' => 3, 'node_type' => 10, 'track_number' => $add['track_number']])->count();
                    if ($count > 0) {
                        Db::name('order_node_detail')
                            ->where(['order_node' => 3, 'node_type' => 10, 'track_number' => $add['track_number']])
                            ->update($order_node_detail); //插入节点字表
                    } else {
                        Db::name('order_node_detail')->insert($order_node_detail); //插入节点字表
                    }

                    $this->esService->updateEs('mojing_track', $arr);
                }
            }

            //结果
            if ($all_num - 1 == $k) {

                if ($data['e'] == 30 || $data['e'] == 35 || $data['e'] == 40 || $data['e'] == 50) {
                    $order_node_date = Db::name('order_node')->where(['track_number' => $add['track_number'], 'shipment_type' => $add['shipment_type']])->find();

                    if ($data['e'] == 40) {
                        $order_node_date['order_node'] = 3;
                        $order_node_date['node_type'] = 10;
                    }

                    if (($order_node_date['order_node'] == 3 && $order_node_date['node_type'] == 10) || ($order_node_date['order_node'] == 3 && $order_node_date['node_type'] == 11)) {
                        $update_order_node['order_node'] = 4;
                        $update_order_node['node_type'] = $data['e'];
                        $update_order_node['update_time'] = $v['a'];
                        if ($data['e'] == 40) {
                            $update_order_node['signing_time'] = $v['a']; //更新签收时间
                            //更新es
                            $arr['signing_time'] = strtotime($v['a']);
                            // $delivery_error_flag = strtotime($v['a']) < strtotime($order_node_date['delivery_time']) + 172800 ? 1 : 0;
                            $arr['delivery_error_flag'] = 0;
                            $arr['delievered_days'] = $order_node_date['delivery_time'] ? (strtotime($v['a']) - strtotime($order_node_date['delivery_time'])) / 86400 : 0;
                            $arr['wait_time'] = $order_node_date['delivery_time'] ? abs(strtotime($v['a']) - strtotime($order_node_date['delivery_time'])) : 0;
                        }
                        Db::name('order_node')->where('id', $order_node_date['id'])->update($update_order_node); //更新主表状态
                        //更新es
                        $arr['id'] = $order_node_date['id'];
                        $arr['order_node'] = 4;
                        $arr['node_type'] = $data['e'];

                        $order_node_detail['order_node'] = 4;
                        $order_node_detail['node_type'] = $data['e'];
                        switch ($data['e']) {
                            case 30:
                                $order_node_detail['content'] = $this->str30;
                                break;
                            case 35:
                                $order_node_detail['content'] = $this->str35;
                                break;
                            case 40:
                                $order_node_detail['content'] = $this->str40;
                                break;
                            case 50:
                                $order_node_detail['content'] = $this->str50;
                                break;
                        }
                        $order_node_detail['create_time'] = $v['a'];

                        //查询子表是否已存在此节点数据
                        $count = Db::name('order_node_detail')->where(['order_node' => 4, 'node_type' => $data['e'], 'track_number' => $add['track_number']])->count();
                        if ($count > 0) {
                            Db::name('order_node_detail')
                                ->where(['order_node' => 4, 'node_type' => $data['e'], 'track_number' => $add['track_number']])
                                ->update($order_node_detail); //插入节点字表
                        } else {
                            Db::name('order_node_detail')->insert($order_node_detail); //插入节点字表
                        }

                        $this->esService->updateEs('mojing_track', $arr);
                    }
                    if ($order_node_date['order_node'] == 4 && $order_node_date['node_type'] != 40) {
                        $update_order_node['order_node'] = 4;
                        $update_order_node['node_type'] = $data['e'];
                        $update_order_node['update_time'] = $v['a'];
                        if ($data['e'] == 40) {
                            $update_order_node['signing_time'] = $v['a']; //更新签收时间
                            //更新es
                            $arr['signing_time'] = strtotime($v['a']);
                            // $delivery_error_flag = strtotime($v['a']) < strtotime($order_node_date['delivery_time']) + 172800 ? 1 : 0;
                            $arr['delivery_error_flag'] = 0;
                            $arr['delievered_days'] = $order_node_date['delivery_time'] ? (strtotime($v['a']) - strtotime($order_node_date['delivery_time'])) / 86400 : 0;
                            $arr['wait_time'] = $order_node_date['delivery_time'] ? abs(strtotime($v['a']) - strtotime($order_node_date['delivery_time'])) : 0;
                        }
                        Db::name('order_node')->where('id', $order_node_date['id'])->update($update_order_node); //更新主表状态
                        //更新es
                        $arr['id'] = $order_node_date['id'];
                        $arr['order_node'] = 4;
                        $arr['node_type'] = $data['e'];

                        $order_node_detail['order_node'] = 4;
                        $order_node_detail['node_type'] = $data['e'];
                        switch ($data['e']) {
                            case 30:
                                $order_node_detail['content'] = $this->str30;
                                break;
                            case 35:
                                $order_node_detail['content'] = $this->str35;
                                break;
                            case 40:
                                $order_node_detail['content'] = $this->str40;
                                break;
                            case 50:
                                $order_node_detail['content'] = $this->str50;
                                break;
                        }

                        $order_node_detail['create_time'] = $v['a'];

                        //查询子表是否已存在此节点数据
                        $count = Db::name('order_node_detail')->where(['order_node' => 4, 'node_type' => $data['e'], 'track_number' => $add['track_number']])->count();
                        if ($count > 0) {
                            Db::name('order_node_detail')
                                ->where(['order_node' => 4, 'node_type' => $data['e'], 'track_number' => $add['track_number']])
                                ->update($order_node_detail); //插入节点字表
                        } else {
                            Db::name('order_node_detail')->insert($order_node_detail); //插入节点字表
                        }

                        $this->esService->updateEs('mojing_track', $arr);
                    }
                }
                $order_node_date = Db::name('order_node')->where(['track_number' => $add['track_number'], 'shipment_type' => $add['shipment_type']])->find();
                $update_order_node = [];
                $update_order_node['update_time'] = $v['a'];
                $update_order_node['shipment_last_msg'] = $v['z'];
                Db::name('order_node')->where('id', $order_node_date['id'])->update($update_order_node); //更新主表状态
                //更新es
                $arr['id'] = $order_node_date['id'];
                $arr['shipment_last_msg'] = $v['z'];
                $this->esService->updateEs('mojing_track', $arr);

            }
        }

        return true;
    }

    /**
     * 获取快递号
     *
     * @param $title
     *
     * @return mixed|string
     */
    protected function getCarrier($title)
    {
        $carrierId = '';
        if (stripos($title, 'post') !== false) {
            $carrierId = 'chinapost';
            $title = 'China Post';
        } elseif (stripos($title, 'ems') !== false) {
            $carrierId = 'chinaems';
            $title = 'China Ems';
        } elseif (stripos($title, 'dhl') !== false) {
            $carrierId = 'dhl';
            $title = 'DHL';
        } elseif (stripos($title, 'fede') !== false) {
            $carrierId = 'fedex';
            $title = 'Fedex';
        } elseif (stripos($title, 'usps') !== false) {
            $carrierId = 'usps';
            $title = 'Usps';
        } elseif (stripos($title, 'yanwen') !== false) {
            $carrierId = 'yanwen';
            $title = 'YANWEN';
        } elseif (stripos($title, 'cpc') !== false) {
            $carrierId = 'cpc';
            $title = 'Canada Post';
        } elseif (stripos($title, 'sua') !== false) {
            $carrierId = 'sua';
            $title = 'SUA';
        } elseif (stripos($title, 'cod') !== false) {
            $carrierId = 'cod';
            $title = 'COD';
        } elseif (stripos($title, 'tnt') !== false) {
            $carrierId = 'tnt';
            $title = 'TNT';
        } elseif (stripos($title, 'ups') !== false) {
            $carrierId = 'ups';
            $title = 'UPS';
        }

        $carrier = [
            'dhl'       => '100001',
            'chinapost' => '03011',
            'chinaems'  => '03013',
            'cpc'       => '03041',
            'fedex'     => '100003',
            'usps'      => '21051',
            'yanwen'    => '190012',
            'sua'       => '190111',
            'cod'       => '10021',
            'tnt'       => '100004',
            'ups'       => '100002',
        ];
        if ($carrierId) {
            return ['title' => $title, 'carrierId' => $carrier[$carrierId]];
        }

        return ['title' => $title, 'carrierId' => $carrierId];
    }

}