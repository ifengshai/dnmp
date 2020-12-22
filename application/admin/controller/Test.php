<?php

namespace app\admin\controller;

use app\common\controller\Backend;
use app\Common\model\Auth;
use GuzzleHttp\Client;
use think\Db;
use SchGroup\SeventeenTrack\Connectors\TrackingConnector;


class Test extends Backend
{
    protected $noNeedLogin = ['*'];
    protected $apiKey = 'F26A807B685D794C676FA3CC76567035';
    protected $str1 = 'Arrived Shipping Partner Facility, Awaiting Item.';
    protected $str2 = 'Delivered to Air Transport.';
    protected $str3 = 'In Transit to Next Facility.';
    protected $str4 = 'Arrived in the Final Destination Country.';
    protected $str30 = 'Out for delivery or arrived at local facility, you may schedule for delivery or pickup. Please be aware of the collection deadline.'; //到达待取
    protected $str35 = 'Attempted for delivery but failed, this may due to several reasons. Please contact the carrier for clarification.'; //投递失败
    protected $str40 = 'Delivered successfully.'; //投递成功
    protected $str50 = 'Item might undergo unusual shipping condition, this may due to several reasons, most likely item was returned to sender, customs issue etc.'; //可能异常


    public function _initialize()
    {
        parent::_initialize();

        $this->newproduct = new \app\admin\model\NewProduct();
        $this->item = new \app\admin\model\itemmanage\Item();
        $this->zeelool = new \app\admin\model\order\order\Zeelool();
        $this->voogueme = new \app\admin\model\order\order\Voogueme();
        $this->nihao = new \app\admin\model\order\order\Nihao();
        $this->user = new \app\admin\model\Admin();
        $this->ordernodedetail = new \app\admin\model\OrderNodeDetail();
        $this->ordernode = new \app\admin\model\OrderNode();
    }

    public function test001()
    {
        $track_number = '9400111108296818283602';
        $order_number = '100171868';
        //根据物流单号查询发货物流渠道
        $shipment_data_type = Db::connect('database.db_delivery')->table('ld_deliver_order')->where(['track_number' => $track_number, 'increment_id' => $order_number])->value('agent_way_title');

        dump($shipment_data_type);
        die;
    }


    public function site_reg()
    {
        $this->reg_shipment('database.db_zeelool', 1);
        $this->reg_shipment('database.db_voogueme', 2);
        $this->reg_shipment('database.db_nihao', 3);
        $this->reg_shipment('database.db_meeloog', 4);
    }


    /**
     * 批量 注册物流
     * 每天跑一次，查找遗漏注册的物流单号，进行注册操作
     */
    public function reg_shipment($site_str, $site_type)
    {
        $order_shipment = Db::connect($site_str)
            ->table('sales_flat_shipment_track')->alias('a')
            ->join(['sales_flat_order' => 'b'], 'a.order_id=b.entity_id')
            ->field('a.entity_id,a.order_id,a.track_number,a.title,a.updated_at,a.created_at,b.increment_id')
            ->where('a.created_at', '>=', '2020-07-31 00:00:00')
            ->where('a.title', '=', 'noLogisticswaypoolCarriercode')
            // ->where('a.handle', '=', '0')
            ->group('a.order_id')
            ->select();
        $shipment_reg = [];
        foreach ($order_shipment as $k => $v) {
            if ($v['title'] == 'noLogisticswaypoolCarriercode') {
                $title = 'FedEx';
            } else {
                $title = $v['title'];
            }
            $title = strtolower(str_replace(' ', '-', $title));
            $carrier = $this->getCarrier($title);
            $shipment_reg[$k]['number'] = $v['track_number'];
            $shipment_reg[$k]['carrier'] = $carrier['carrierId'];
            $shipment_reg[$k]['order_id'] = $v['order_id'];
        }

        $order_group = array_chunk($shipment_reg, 40);

        $trackingConnector = new TrackingConnector($this->apiKey);
        foreach ($order_group as $key => $val) {
            $trackingConnector->registerMulti($val);
            usleep(500000);
        }
        echo $site_str . ' is ok' . "\n";
    }


    /**
     * 重启跟踪2-7状态的物流
     *
     * @Description
     * @author wpl
     * @since 2020/07/16 09:16:20 
     * @return void
     */
    public function linshi_retrack()
    {
        $order_shipment = Db::name('order_node')->field('track_number as number')->where(['order_node' => 2, 'node_type' => 7, 'create_time' => ['<=', '2020-06-01 00:00:00']])->select();
        $order_shipment = collection($order_shipment)->toArray();
        $res = array_chunk($order_shipment, 40);
        $trackingConnector = new TrackingConnector($this->apiKey);
        echo count($res);
        foreach ($res as $k => $v) {

            $track = $trackingConnector->retrackMulti($v);
            file_put_contents('/www/wwwroot/mojing/runtime/log/test.log', serialize($track) . "\r\n", FILE_APPEND);
            usleep(200000);
            echo $k . "\n";
        }
        echo 'is ok';
    }




    /**
     * 临时批量注册--lixiang
     */
    public function linshi_reg_track()
    {
        $order_shipment = Db::name('z_linshi')->select();
        $order_shipment = collection($order_shipment)->toArray();

        $trackingConnector = new TrackingConnector($this->apiKey);

        foreach ($order_shipment as $k => $v) {

            $trackInfo = $trackingConnector->getTrackInfoMulti([[
                'number' => $v['yundanhao'],
                'carrier' => '21051'
            ]]);

            $update['17status'] = $trackInfo['data']['accepted'][0]['track']['e'];
            Db::name('z_linshi')->where('id', $v['id'])->update($update);

            sleep(1);

            echo  $k . "ok \n";
        }
        echo "all ok \n";
    }

    protected function regitster17Track($params = [])
    {
        $trackingConnector = new TrackingConnector($this->apiKey);
        $track = $trackingConnector->registerMulti($params);
        return $track;
    }

    /**
     * @author wgj
     * @Date 2020/10/21 15:29
     * wgj总物流脚本new_track_total()
     *
     * 更新条件'node_type' => 7, 'order_node' => 2, 'delivery_time' => ['>=', '2020-09-01 00:00:00']
     */
    public function new_track_total()
    {
        //        $order_shipment = Db::name('order_node')->where(['order_node' => 2, 'node_type' => 7, 'create_time' => ['>=', '2020-04-11 10:00:00']])->select();//本地测试数据无发货时间（发货是走发货系统同步的时间，线上有），使用了创建时间
        $order_shipment = Db::name('order_node')->where(['node_type' => 7, 'order_node' => 2, 'delivery_time' => ['>=', '2020-08-30 00:00:00']])->select();
        $order_shipment = collection($order_shipment)->toArray();

        $trackingConnector = new TrackingConnector($this->apiKey);

        foreach ($order_shipment as $k => $v) {

            $title = strtolower(str_replace(' ', '-', $v['title']));

            $carrier = $this->getCarrier($title);

            $trackInfo = $trackingConnector->getTrackInfoMulti([[
                'number' => $v['track_number'],
                'carrier' => $carrier['carrierId']
                //测试数据
                /*'number' => 'LZ358046313CN',//E邮宝
                'carrier' => '03011'*/
                /* 'number' => '3616952791',//DHL
                'carrier' => '100001'*/
                /*'number' => '74890988318620573173', //Fedex
                'carrier' => '100003' */
                /*'number' => '92001902551559000101352584', //usps郭伟峰
                'carrier' => '21051' */
                /*'number' => 'UF127024493YP', //yanwen
                'carrier' => '190012'*/
            ]]);

            $add['site'] = $v['site'];
            $add['order_id'] = $v['order_id'];
            $add['order_number'] = $v['order_number'];
            $add['shipment_type'] = $v['shipment_type'];
            $add['shipment_data_type'] = $v['shipment_data_type'];
            $add['track_number'] = $v['track_number'];

            if ($trackInfo['code'] == 0 && $trackInfo['data']['accepted']) {
                $trackdata = $trackInfo['data']['accepted'][0]['track'];
                $this->track_data($trackdata, $add);
            }
            echo 'site:' . $v['site'] . ';key:' . $k . ';order_id' . $v['order_id'] . "\n";

            usleep(200000);
        }
        echo 'ok';
    }

    /**
     * @author wgj
     * @Date 2020/10/21 14:48
     * @param $data
     * @param $add
     * order_node总track_data
     */
    public function track_data($data, $add)
    {
        $trackdetail = array_reverse($data['z1']);

        $time = '';
        $all_num = count($trackdetail);

        if (!empty($trackdetail)) {
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
                    if ($order_node_date['order_node'] == 2 && $order_node_date['node_type'] == 7) {
                        $update_order_node['order_node'] = 3;
                        $update_order_node['node_type'] = 8;
                        $update_order_node['update_time'] = $v['a'];
                        Db::name('order_node')->where('id', $order_node_date['id'])->update($update_order_node); //更新主表状态

                        $order_node_detail['node_type'] = 8;
                        $order_node_detail['content'] = $this->str1;
                        $order_node_detail['create_time'] = $v['a'];
                        Db::name('order_node_detail')->insert($order_node_detail); //插入节点字表
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

                        $order_node_detail['node_type'] = 10;
                        $order_node_detail['content'] = $this->str3;
                        $order_node_detail['create_time'] = $v['a'];
                        Db::name('order_node_detail')->insert($order_node_detail); //插入节点字表
                    }
                }

                //结果
                if ($all_num - 1 == $k) {
                    if ($data['e'] == 30 || $data['e'] == 35 || $data['e'] == 40 || $data['e'] == 50) {
                        $order_node_date = Db::name('order_node')->where(['track_number' => $add['track_number'], 'shipment_type' => $add['shipment_type']])->find();

                        if (($order_node_date['order_node'] == 3 && $order_node_date['node_type'] == 10) || ($order_node_date['order_node'] == 3 && $order_node_date['node_type'] == 11)) {
                            $update_order_node['order_node'] = 4;
                            $update_order_node['node_type'] = $data['e'];
                            $update_order_node['update_time'] = $v['a'];
                            if ($data['e'] == 40) {
                                $update_order_node['signing_time'] = $v['a']; //更新签收时间
                            }
                            Db::name('order_node')->where('id', $order_node_date['id'])->update($update_order_node); //更新主表状态

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
                            Db::name('order_node_detail')->insert($order_node_detail); //插入节点字表
                        }
                        if ($order_node_date['order_node'] == 4 && $order_node_date['node_type'] != 40) {
                            $update_order_node['order_node'] = 4;
                            $update_order_node['node_type'] = $data['e'];
                            $update_order_node['update_time'] = $v['a'];
                            if ($data['e'] == 40) {
                                $update_order_node['signing_time'] = $v['a']; //更新签收时间
                            }
                            Db::name('order_node')->where('id', $order_node_date['id'])->update($update_order_node); //更新主表状态

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
                            Db::name('order_node_detail')->insert($order_node_detail); //插入节点字表
                        }
                    }
                }
            }
        }
    }

    public function new_track_test()
    {

        $order_shipment = Db::name('order_node')->where(['node_type' => ['<>', 40], 'order_node' => 3, 'shipment_type' => 'USPS'])->select();
        $order_shipment = collection($order_shipment)->toArray();

        $trackingConnector = new TrackingConnector($this->apiKey);

        foreach ($order_shipment as $k => $v) {
            //先把主表状态更新为2-7
            // $update['order_node'] = 2;
            // $update['node_type'] = 7;
            // Db::name('order_node')->where('id', $v['id'])->update($update); //更新主表状态

            $title = strtolower(str_replace(' ', '-', $v['title']));

            $carrier = $this->getCarrier($title);

            $trackInfo = $trackingConnector->getTrackInfoMulti([[
                'number' => $v['track_number'],
                'carrier' => $carrier['carrierId']
                /*'number' => 'LO546092713CN',//E邮宝
                'carrier' => '03011'*/
                /* 'number' => '3616952791',//DHL
                'carrier' => '100001' */
                /* 'number' => '74890988318620573173', //Fedex
                'carrier' => '100003' */
                /* 'number' => '92001902551559000101352584', //usps郭伟峰
                'carrier' => '21051' */
            ]]);

            $add['site'] = $v['site'];
            $add['order_id'] = $v['order_id'];
            $add['order_number'] = $v['order_number'];
            $add['shipment_type'] = $v['shipment_type'];
            $add['shipment_data_type'] = $v['shipment_data_type'];
            $add['track_number'] = $v['track_number'];

            if ($trackInfo['code'] == 0 && $trackInfo['data']['accepted']) {
                $trackdata = $trackInfo['data']['accepted'][0]['track'];


                $this->tongyong($trackdata, $add);

                // if (stripos($v['shipment_type'], 'USPS') !== false) {
                //     if ($v['shipment_data_type'] == 'USPS_1') {
                //         //郭伟峰
                //         $this->usps_1_data($trackdata, $add);
                //     }
                //     if ($v['shipment_data_type'] == 'USPS_2') {
                //         //加诺
                //         $this->usps_2_data($trackdata, $add);
                //     }
                // }

                // if (stripos($v['shipment_type'], 'DHL') !== false) {
                //     $this->new_dhl_data($trackdata, $add);
                // }

                // if (stripos($v['shipment_type'], 'fede') !== false) {
                //     $this->new_fedex_data($trackdata, $add);
                // }
            }
            echo 'site:' . $v['site'] . ';key:' . $k . ';order_id' . $v['order_id'] . "\n";
            usleep(200000);
        }
        echo 'ok';
    }


    //usps_1  郭伟峰
    public function tongyong($data, $add)
    {
        $order_node_detail['handle_user_id'] = 0;
        $order_node_detail['handle_user_name'] = 'system';
        $order_node_detail['site'] = $add['site'];
        $order_node_detail['order_id'] = $add['order_id'];
        $order_node_detail['order_number'] = $add['order_number'];
        $order_node_detail['shipment_type'] = $add['shipment_type'];
        $order_node_detail['shipment_data_type'] = $add['shipment_data_type'];
        $order_node_detail['track_number'] = $add['track_number'];

        if ($data['e'] == 40) {

            $add['create_time'] = $data['z0']['a'];
            $add['content'] = $data['z0']['z'];
            $add['courier_status'] = $data['e'];
            $count = Db::name('order_node_courier')->where(['track_number' => $add['track_number'], 'shipment_type' => $add['shipment_type'], 'content' => $add['content']])->count();
            if ($count < 1) {
                Db::name('order_node_courier')->insert($add); //插入物流日志表
            }

            $order_node_date = Db::name('order_node')->where('track_number', $add['track_number'])->find();
            $update_order_node['order_node'] = 4;
            $update_order_node['node_type'] = $data['e'];
            $update_order_node['update_time'] = $data['z0']['a'];
            $update_order_node['signing_time'] = $data['z0']['a']; //更新签收时间
            Db::name('order_node')->where('id', $order_node_date['id'])->update($update_order_node); //更新主表状态

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

            $order_node_detail['create_time'] = $data['z0']['a'];
            Db::name('order_node_detail')->insert($order_node_detail); //插入节点字表

        }
    }


    /**
     * 批量 获取物流明细
     * 莫删除
     */
    public function track_ship_date()
    {
        $this->new_track_shipment_num(1);
        exit;
    }

    public function new_track_shipment_num()
    {
        $order_shipment = Db::name('order_node')->where('node_type', '<>', 40)->select();
        $order_shipment = collection($order_shipment)->toArray();
        echo count($order_shipment);
        $trackingConnector = new TrackingConnector($this->apiKey);
        foreach ($order_shipment as $k => $v) {
            if ($k < 36869) {
                continue;
            }
            //先把主表状态更新为2-7
            // $update['order_node'] = 2;
            // $update['node_type'] = 7;
            // Db::name('order_node')->where('id', $v['id'])->update($update); //更新主表状态

            $title = strtolower(str_replace(' ', '-', $v['title']));

            $carrier = $this->getCarrier($title);

            $trackInfo = $trackingConnector->getTrackInfoMulti([[
                'number' => $v['track_number'],
                'carrier' => $carrier['carrierId']
                /*'number' => 'LO546092713CN',//E邮宝
                'carrier' => '03011'*/
                /* 'number' => '3616952791',//DHL
                'carrier' => '100001' */
                /* 'number' => '74890988318620573173', //Fedex
                'carrier' => '100003' */
                /* 'number' => '92001902551559000101352584', //usps郭伟峰
                'carrier' => '21051' */
            ]]);

            $add['site'] = $v['site'];
            $add['order_id'] = $v['order_id'];
            $add['order_number'] = $v['order_number'];
            $add['shipment_type'] = $v['shipment_type'];
            $add['shipment_data_type'] = $v['shipment_data_type'];
            $add['track_number'] = $v['track_number'];

            if ($trackInfo['code'] == 0 && $trackInfo['data']['accepted']) {
                $trackdata = $trackInfo['data']['accepted'][0]['track'];

                $this->tongyong_data($trackdata, $add);
            }
            echo 'site:' . $v['site'] . ';key:' . $k . ';order_id' . $v['order_id'] . "\n";
            usleep(200000);
        }
        echo 'ok';
    }

    /**
     * 通用物流处理逻辑
     *
     * @Description
     * @author wpl
     * @since 2020/07/15 10:29:31 
     * @param [type] $data
     * @param [type] $add
     * @return void
     */
    public function tongyong_data($data, $add = [])
    {
        $trackdetail = array_reverse($data['z1']);
        $time = '';
        $all_num = count($trackdetail);

        if (!empty($trackdetail)) {
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
                    if ($order_node_date['order_node'] == 2 && $order_node_date['node_type'] == 7) {
                        $update_order_node['order_node'] = 3;
                        $update_order_node['node_type'] = 8;
                        $update_order_node['update_time'] = $v['a'];
                        Db::name('order_node')->where('id', $order_node_date['id'])->update($update_order_node); //更新主表状态

                        $order_node_detail['node_type'] = 8;
                        $order_node_detail['content'] = $this->str1;
                        $order_node_detail['create_time'] = $v['a'];
                        Db::name('order_node_detail')->insert($order_node_detail); //插入节点字表
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

                        $order_node_detail['node_type'] = 10;
                        $order_node_detail['content'] = $this->str3;
                        $order_node_detail['create_time'] = $v['a'];
                        Db::name('order_node_detail')->insert($order_node_detail); //插入节点字表
                    }
                }
                //结果
                if ($all_num - 1 == $k) {
                    if ($data['e'] == 30 || $data['e'] == 35 || $data['e'] == 40 || $data['e'] == 50) {
                        $order_node_date = Db::name('order_node')->where(['track_number' => $add['track_number'], 'shipment_type' => $add['shipment_type']])->find();

                        if (($order_node_date['order_node'] == 3 && $order_node_date['node_type'] == 10) || ($order_node_date['order_node'] == 3 && $order_node_date['node_type'] == 11)) {
                            $update_order_node['order_node'] = 4;
                            $update_order_node['node_type'] = $data['e'];
                            $update_order_node['update_time'] = $v['a'];
                            if ($data['e'] == 40) {
                                $update_order_node['signing_time'] = $v['a']; //更新签收时间
                            }
                            Db::name('order_node')->where('id', $order_node_date['id'])->update($update_order_node); //更新主表状态

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
                            Db::name('order_node_detail')->insert($order_node_detail); //插入节点字表
                        }
                        if ($order_node_date['order_node'] == 4 && $order_node_date['node_type'] != 40 && $order_node_date['node_type'] != $data['e']) {
                            $update_order_node['order_node'] = 4;
                            $update_order_node['node_type'] = $data['e'];
                            $update_order_node['update_time'] = $v['a'];
                            if ($data['e'] == 40) {
                                $update_order_node['signing_time'] = $v['a']; //更新签收时间
                            }
                            Db::name('order_node')->where('id', $order_node_date['id'])->update($update_order_node); //更新主表状态

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
                            Db::name('order_node_detail')->insert($order_node_detail); //插入节点字表
                        }
                    }
                }
            }
        }
    }

    /**
     * 获取快递号
     * @param $title
     * @return mixed|string
     */
    public function getCarrier($title)
    {
        $carrierId = '';
        if (stripos($title, 'post') !== false) {
            $carrierId = 'chinapost';
            $title = 'China Post';
        } elseif (stripos($title, 'eub') !== false) {
            $carrierId = 'eub';
            $title = 'EUB';
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
        }
        $carrier = [
            'dhl' => '100001',
            'chinapost' => '03011',
            'eub' => '03011',
            'chinaems' => '03013',
            'cpc' =>  '03041',
            'fedex' => '100003',
            'usps' => '21051',
            'yanwen' => '190012'
        ];
        if ($carrierId) {
            return ['title' => $title, 'carrierId' => $carrier[$carrierId]];
        }
        return ['title' => $title, 'carrierId' => $carrierId];
    }

    /**
     * 更新物流表状态
     *
     * @Description
     * @author wpl
     * @since 2020/05/18 18:16:48 
     * @return void
     */
    protected function setLogisticsStatus($params)
    {
        switch ($params['site']) {
            case 1:
                $url = config('url.zeelool_url');
                break;
            case 2:
                $url = config('url.voogueme_url');
                break;
            case 3:
                $url = config('url.nihao_url');
                break;
            default:
                return false;
                break;
        }
        unset($params['site']);
        $url = $url . 'magic/order/logistics';
        $client = new Client(['verify' => false]);
        //请求URL
        $response = $client->request('POST', $url, array('form_params' => $params));
        $body = $response->getBody();
        $stringBody = (string) $body;
        $res = json_decode($stringBody);
        return $res;
    }









    /**
     * 处理SKU编码 - 入库单
     *
     * @Description
     * @author wpl
     * @since 2020/12/18 11:10:38 
     * @return void
     */
    public function process_sku_number()
    {
        $instock = new \app\admin\model\warehouse\Instock();
        $list = $instock->alias('a')->where(['status' => 2,'type_id' => 1])->field('a.id,a.check_id,b.purchase_id,b.sku,b.in_stock_num')->join(['fa_in_stock_item' => 'b'],'a.id=b.in_stock_id')->order('a.createtime desc')->select();
        $list = collection($list)->toArray();
        foreach($list as $k => $v) {
            //查询对应质检单
            $check = Db::name('check_order')->where(['id' => $v['check_id']])->find();
            if ($v['in_stock_num'] < 0) {
                continue;
            }
            $res = Db::name('zzzz_temp')->where(['is_process' => 0,'sku' => $v['sku']])->limit($v['in_stock_num'])->select();
            if (!$res) {
               continue; 
            } 
            foreach($res as $key => $val) {
                $where['code'] = $val['product_number'];
                $params['sku'] = $val['sku'];
                $params['in_stock_id'] = $v['id'];
                $params['purchase_id'] = $v['purchase_id'];
                $params['check_id'] = $v['check_id'];
                $params['is_quantity'] = 1;
                $params['batch_id'] = $check['batch_id'];
                $params['logistics_id'] = $check['logistics_id'];
                Db::name('product_barcode_item')->where($where)->update($params);

                Db::name('zzzz_temp')->where(['id' => $val['id']])->update(['is_process' => 1]);
            }
            echo $k . "\n";
        }
        echo 'ok';
    }

    /**
     * 计算sku实时库存
     *
     * @Description
     * @author wpl
     * @since 2020/12/21 14:01:41 
     * @return void
     */
    public function process_sku_stock()
    {
        $list = Db::name('zzzz_temp')->field('count(1) as stock,sku')->group('sku')->select();
        Db::name('zz_temp2')->insertAll($list);
        echo "ok";
    }

    /************************跑库存数据用START*****勿删*****************************/
    //导入实时库存 第一步
    public function set_product_relstock()
    {

        $list = Db::table('fa_zz_temp2')->select();

        foreach ($list as $k => $v) {
            $p_map['sku'] = $v['sku'];
            $data['real_time_qty'] = $v['stock'];
            $res = $this->item->where($p_map)->update($data);
            echo $v['sku'] . "\n";
        }
        echo 'ok';
        die;
    }

    /**
     * 统计配货占用 第二步 - 配货占用清0 无需跑
     *
     * @Description
     * @author wpl
     * @since 2020/04/11 15:54:25
     * @return void
     */
    public function set_product_process()
    {
        $this->zeelool = new \app\admin\model\order\order\Zeelool;
        $this->voogueme = new \app\admin\model\order\order\Voogueme;
        $this->nihao = new \app\admin\model\order\order\Nihao;
        $this->weseeoptical = new \app\admin\model\order\order\Weseeoptical;
        $this->meeloog = new \app\admin\model\order\order\Meeloog;
        $this->zeelool_es = new \app\admin\model\order\order\ZeeloolEs();
        $this->zeelool_de = new \app\admin\model\order\order\ZeeloolDe();
        $this->zeelool_jp = new \app\admin\model\order\order\ZeeloolJp();
        $this->itemplatformsku = new \app\admin\model\itemmanage\ItemPlatformSku;
        $this->item = new \app\admin\model\itemmanage\Item;

        $skus = Db::table('fa_zz_temp2')->column('sku');

        foreach ($skus as $k => $v) {

            $map = [];
            $zeelool_sku = $this->itemplatformsku->getWebSku($v, 1);
            $voogueme_sku = $this->itemplatformsku->getWebSku($v, 2);
            $nihao_sku = $this->itemplatformsku->getWebSku($v, 3);
            $wesee_sku = $this->itemplatformsku->getWebSku($v, 5);
            $meeloog_sku = $this->itemplatformsku->getWebSku($v, 4);
            $zeelool_es_sku = $this->itemplatformsku->getWebSku($v, 9);
            $zeelool_de_sku = $this->itemplatformsku->getWebSku($v, 10);
            $zeelool_jp_sku = $this->itemplatformsku->getWebSku($v, 11);

            $map['status'] = ['in', ['free_processing', 'processing', 'complete', 'paypal_reversed', 'paypal_canceled_reversal']];
            $map['custom_is_delivery_new'] = 0; //是否提货
            $map['custom_is_match_frame_new'] = 1; //是否配镜架
            $map['a.created_at'] = ['between', ['2020-01-01 00:00:00', date('Y-m-d H:i:s')]]; //时间节点
            $map['sku'] = $zeelool_sku;
            $zeelool_qty = $this->zeelool->alias('a')->where($map)->join(['sales_flat_order_item' => 'b'], 'a.entity_id = b.order_id')->sum('qty_ordered');
            $map['sku'] = $voogueme_sku;
            $voogueme_qty = $this->voogueme->alias('a')->where($map)->join(['sales_flat_order_item' => 'b'], 'a.entity_id = b.order_id')->sum('qty_ordered');
            $map['sku'] = $nihao_sku;
            $nihao_qty = $this->nihao->alias('a')->where($map)->join(['sales_flat_order_item' => 'b'], 'a.entity_id = b.order_id')->sum('qty_ordered');
            $map['sku'] = $wesee_sku;
            $weseeoptical_qty = $this->weseeoptical->alias('a')->where($map)->join(['sales_flat_order_item' => 'b'], 'a.entity_id = b.order_id')->sum('qty_ordered');

            $map['sku'] = $zeelool_es_sku;
            $zeelool_es_qty = $this->zeelool_es->alias('a')->where($map)->join(['sales_flat_order_item' => 'b'], 'a.entity_id = b.order_id')->sum('qty_ordered');
            $map['sku'] = $zeelool_de_sku;
            $zeelool_de_qty = $this->zeelool_de->alias('a')->where($map)->join(['sales_flat_order_item' => 'b'], 'a.entity_id = b.order_id')->sum('qty_ordered');
            $map['sku'] = $zeelool_jp_sku;
            $zeelool_jp_qty = $this->zeelool_jp->alias('a')->where($map)->join(['sales_flat_order_item' => 'b'], 'a.entity_id = b.order_id')->sum('qty_ordered');

            $map['sku'] = $meeloog_sku;
            $map['custom_is_delivery'] = 0; //是否提货
            $map['custom_is_match_frame'] = 1; //是否配镜架
            unset($map['custom_is_delivery_new']);
            unset($map['custom_is_match_frame_new']);
            $meeloog_qty = $this->meeloog->alias('a')->where($map)->join(['sales_flat_order_item' => 'b'], 'a.entity_id = b.order_id')->sum('qty_ordered');

            $p_map['sku'] = $v;
            $data['distribution_occupy_stock'] = $zeelool_qty + $voogueme_qty + $nihao_qty + $weseeoptical_qty + $meeloog_qty + $zeelool_jp_qty + $zeelool_es_qty + $zeelool_de_qty;

            $res = $this->item->where($p_map)->update($data);

            echo $v . "\n";
            usleep(20000);
        }

        echo 'ok';
        die;
    }

    /**
     * 订单占用 第三步
     *
     * @Description
     * @author wpl
     * @since 2020/04/11 15:54:25
     * @return void
     */
    public function set_product_process_order()
    {
        $this->zeelool = new \app\admin\model\order\order\Zeelool;
        $this->voogueme = new \app\admin\model\order\order\Voogueme;
        $this->nihao = new \app\admin\model\order\order\Nihao;
        $this->weseeoptical = new \app\admin\model\order\order\Weseeoptical;
        $this->meeloog = new \app\admin\model\order\order\Meeloog;
        $this->zeelool_es = new \app\admin\model\order\order\ZeeloolEs();
        $this->zeelool_de = new \app\admin\model\order\order\ZeeloolDe();
        $this->zeelool_jp = new \app\admin\model\order\order\ZeeloolJp();
        $this->itemplatformsku = new \app\admin\model\itemmanage\ItemPlatformSku;
        $this->item = new \app\admin\model\itemmanage\Item;
        $skus = Db::table('fa_zz_temp2')->column('sku');

        foreach ($skus as $k => $v) {
            $map = [];
            $zeelool_sku = $this->itemplatformsku->getWebSku($v, 1);
            $voogueme_sku = $this->itemplatformsku->getWebSku($v, 2);
            $nihao_sku = $this->itemplatformsku->getWebSku($v, 3);
            $wesee_sku = $this->itemplatformsku->getWebSku($v, 5);
            $meeloog_sku = $this->itemplatformsku->getWebSku($v, 4);
            $zeelool_es_sku = $this->itemplatformsku->getWebSku($v, 9);
            $zeelool_de_sku = $this->itemplatformsku->getWebSku($v, 10);
            $zeelool_jp_sku = $this->itemplatformsku->getWebSku($v, 11);

            $map['status'] = ['in', ['free_processing', 'processing', 'complete', 'paypal_reversed', 'paypal_canceled_reversal']];
            $map['custom_is_delivery_new'] = 0; //是否提货
            $map['a.created_at'] = ['between', ['2020-01-01 00:00:00', date('Y-m-d H:i:s')]]; //时间节点
            $map['sku'] = $zeelool_sku;
            $zeelool_qty = $this->zeelool->alias('a')->where($map)->join(['sales_flat_order_item' => 'b'], 'a.entity_id = b.order_id')->sum('qty_ordered');
            $map['sku'] = $voogueme_sku;
            $voogueme_qty = $this->voogueme->alias('a')->where($map)->join(['sales_flat_order_item' => 'b'], 'a.entity_id = b.order_id')->sum('qty_ordered');
            $map['sku'] = $nihao_sku;
            $nihao_qty = $this->nihao->alias('a')->where($map)->join(['sales_flat_order_item' => 'b'], 'a.entity_id = b.order_id')->sum('qty_ordered');
            $map['sku'] = $wesee_sku;
            $weseeoptical_qty = $this->weseeoptical->alias('a')->where($map)->join(['sales_flat_order_item' => 'b'], 'a.entity_id = b.order_id')->sum('qty_ordered');

            $map['sku'] = $zeelool_es_sku;
            $zeelool_es_qty = $this->zeelool_es->alias('a')->where($map)->join(['sales_flat_order_item' => 'b'], 'a.entity_id = b.order_id')->sum('qty_ordered');
            $map['sku'] = $zeelool_de_sku;
            $zeelool_de_qty = $this->zeelool_de->alias('a')->where($map)->join(['sales_flat_order_item' => 'b'], 'a.entity_id = b.order_id')->sum('qty_ordered');
            $map['sku'] = $zeelool_jp_sku;
            $zeelool_jp_qty = $this->zeelool_jp->alias('a')->where($map)->join(['sales_flat_order_item' => 'b'], 'a.entity_id = b.order_id')->sum('qty_ordered');

            $map['sku'] = $meeloog_sku;
            $map['custom_is_delivery'] = 0; //是否提货
            unset($map['custom_is_delivery_new']);
            $meeloog_qty = $this->meeloog->alias('a')->where($map)->join(['sales_flat_order_item' => 'b'], 'a.entity_id = b.order_id')->sum('qty_ordered');


            $p_map['sku'] = $v;
            $data['occupy_stock'] = $zeelool_qty + $voogueme_qty + $nihao_qty + $weseeoptical_qty + $meeloog_qty + $zeelool_jp_qty + $zeelool_es_qty + $zeelool_de_qty;
            $res = $this->item->where($p_map)->update($data);

            echo $v . "\n";
            usleep(20000);
        }
        echo 'ok';
        die;
    }

    /**
     * 可用库存计算 第四步
     *
     * @Description
     * @author wpl
     * @since 2020/04/11 15:54:25
     * @return void
     */
    public function set_product_sotck()
    {
        $this->itemplatformsku = new \app\admin\model\itemmanage\ItemPlatformSku;
        $this->item = new \app\admin\model\itemmanage\Item;

        $skus = Db::table('fa_zz_temp2')->column('sku');
        $list = $this->item->field('sku,stock,occupy_stock,available_stock,real_time_qty,distribution_occupy_stock')->where(['sku' => ['in', $skus]])->select();
        foreach ($list as $k => $v) {
            $data['stock'] = $v['real_time_qty'] + $v['distribution_occupy_stock'];
            $data['available_stock'] = ($v['real_time_qty'] + $v['distribution_occupy_stock']) - $v['occupy_stock'];
            $p_map['sku'] = $v['sku'];
            $res = $this->item->where($p_map)->update($data);

            echo $k . "\n";
            usleep(20000);
        }
        echo 'ok';
        die;
    }

    /**
     * 虚拟库存 第五步
     *
     * @Description
     * @author wpl
     * @since 2020/04/11 15:54:25
     * @return void
     */
    public function set_platform_stock()
    {
        $platform = new \app\admin\model\itemmanage\ItemPlatformSku();
        $item = new \app\admin\model\itemmanage\Item();
        // $skus1 = $platform->where(['stock' => ['<', 0]])->column('sku');
        $skus = Db::table('fa_zz_temp2')->column('sku');
        foreach ($skus as $k => $v) {
            // $v = 'OA01901-06';
            //同步对应SKU库存
            //更新商品表商品总库存
            //总库存
            $item_map['sku'] = $v;
            $item_map['is_del'] = 1;
            if ($v) {
                $available_stock = $item->where($item_map)->value('available_stock');
                //查出映射表中此sku对应的所有平台sku 并根据库存数量进行排序（用于遍历数据的时候首先分配到那个站点）
                $item_platform_sku = $platform->where('sku', $v)->order('stock asc')->field('platform_type,stock')->select();
                if (!$item_platform_sku) {
                    continue;
                }
                //站点数量
                $all_num = count($item_platform_sku);
                $whole_num = $platform
                    ->where('sku', $v)
                    ->field('stock')
                    ->select();
                //取绝对值总库存数
                $num_num = 0;
                foreach ($whole_num as $kk => $vv) {
                    $num_num += abs($vv['stock']);
                }
                //总可用库存
                $stock_num = $available_stock;
                //总虚拟库存
                $stock_all_num = array_sum(array_column($item_platform_sku, 'stock'));
                if ($stock_all_num < 0) {
                    $stock_all_num = 0;
                }
                //如果现有总虚拟库存为0 平均分给各站点
                if ($stock_all_num == 0) {
                    $rate_rate = 1 / $all_num;
                    foreach ($item_platform_sku as $key => $val) {
                        //最后一个站点 剩余数量分给最后一个站
                        if (($all_num - $key) == 1) {
                            $platform->where(['sku' => $v, 'platform_type' => $val['platform_type']])->update(['stock' => $stock_num]);
                        } else {
                            $num = round($available_stock * $rate_rate);
                            $stock_num -= $num;
                            $platform->where(['sku' => $v, 'platform_type' => $val['platform_type']])->update(['stock' => $num]);
                        }
                    }
                } else {
                    foreach ($item_platform_sku as $key => $val) {
                        //最后一个站点 剩余数量分给最后一个站
                        if (($all_num - $key) == 1) {
                            $platform->where(['sku' => $v, 'platform_type' => $val['platform_type']])->update(['stock' => $stock_num]);
                        } else {
                            //如果绝对值虚拟库存为0 平均分
                            if ($num_num  == 0) {
                                $rate_rate = 1 / $all_num;
                                $num =  round($available_stock * $rate_rate);
                            } else {
                                $num = round($available_stock * abs($val['stock']) / $num_num);
                            }
                            $stock_num -= $num;
                            $platform->where(['sku' => $v, 'platform_type' => $val['platform_type']])->update(['stock' => $num]);
                        }
                    }
                }
            }
            usleep(10000);
            echo $k . "\n";
        }
        echo "ok";
    }

    /************************跑库存数据用END**********************************/




   

    /***************处理工单旧数据*********************** */
    public function process_worklist_data()
    {

        ini_set('memory_limit', '1280M');
        /**
         * 判断措施是否为 id = 3主单取消   changesku表需插入所有子订单
         * 判断措施如果id = 19 更改镜框 需插入对应sku 所有子订单
         * 判断措施id = 20 更改镜片 需插入对应sku 所有子订单 , 1, 4, 6, 7
         */
        $work = new \app\admin\model\saleaftermanage\WorkOrderList();
        $order = new \app\admin\model\order\order\NewOrder();
        $list = $work->where(['work_status' => ['in', [0]]])->select();
        $list = collection($list)->toArray();
        foreach ($list as $k => $v) {
            //插入主表
            Db::table('fa_work_order_list_copy1')->insert($v);
            //查询措施表
            $res = Db::table('fa_work_order_measure')->where(['work_id' => $v['id']])->select();
            $item_number = [];
            foreach ($res as $k1 => $v1) {
                //查询工单措施承接表
                $recept = Db::table('fa_work_order_recept')->where(['work_id' => $v['id'], 'measure_id' => $v1['id']])->find();

                //措施为取消
                if ($v1['measure_choose_id'] == 3) {

                    //查询change sku表
                    $change_sku_list = Db::table('fa_work_order_change_sku')->where(['work_id' => $v['id'], 'change_type' => 3, 'measure_id' => $v1['id']])->select();
                    foreach ($change_sku_list as $key1 => $val1) {
                        //查询订单号所有子单
                        $order_list = $order->alias('a')->field('b.item_order_number')
                            ->where(['a.increment_id' => $val1['increment_id'], 'a.site' => $val1['platform_type'], 'b.sku' => $val1['sku']])
                            ->join(['fa_order_item_process' => 'b'], 'a.id=b.order_id')
                            ->select();
                        $measure = [];
                        $change_sku_data = [];
                        $recept_data = [];
                        foreach ($order_list as $key => $val) {
                            //插入措施表
                            $measure['work_id'] = $v['id'];
                            $measure['measure_choose_id'] = 18;
                            $measure['measure_content'] = '子单取消';
                            $measure['create_time'] = $v1['create_time'];
                            $measure['operation_type'] = $v1['operation_type'];
                            $measure['operation_time'] = $v1['operation_time'];
                            $measure['sku_change_type'] = $v1['sku_change_type'];
                            $measure['item_order_number'] = $val['item_order_number'];
                            $id = Db::table('fa_work_order_measure_copy1')->insertGetId($measure);

                            unset($recept['id']);
                            $recept_data = $recept;
                            $recept_data['measure_id'] = $id;
                            Db::table('fa_work_order_recept')->insertGetId($recept_data);


                            $change_sku_data = $val1;
                            $change_sku_data['measure_id'] = $id;
                            $change_sku_data['item_order_number'] = $val['item_order_number'];
                            Db::table('fa_work_order_change_sku_copy1')->insert($change_sku_data);

                            $item_number[] = $val['item_order_number'];
                        }
                    }
                } else if ($v1['measure_choose_id'] == 1) { //措施为更改镜框
                    //查询change sku表内容
                    $change_sku_list = Db::table('fa_work_order_change_sku')->where(['work_id' => $v['id'], 'change_type' => 1, 'measure_id' => $v1['id']])->select();
                    foreach ($change_sku_list as $k2 => $v2) {
                        //查询订单号所有子单
                        $order_list = $order->alias('a')->field('b.item_order_number')
                            ->where(['a.increment_id' => $v2['increment_id'], 'a.site' => $v2['platform_type'], 'b.sku' => $v2['sku']])
                            ->join(['fa_order_item_process' => 'b'], 'a.id=b.order_id')
                            ->select();
                        $measure = [];
                        $change_sku_data = [];
                        $recept_data = [];
                        foreach ($order_list as $k3 => $v3) {
                            $measure['work_id'] = $v['id'];
                            $measure['measure_choose_id'] = 19;
                            $measure['measure_content'] = '更改镜框';
                            $measure['create_time'] = $v1['create_time'];
                            $measure['operation_type'] = $v1['operation_type'];
                            $measure['operation_time'] = $v1['operation_time'];
                            $measure['sku_change_type'] = $v1['sku_change_type'];
                            $measure['item_order_number'] = $v3['item_order_number'];
                            $id = Db::table('fa_work_order_measure_copy1')->insertGetId($measure);

                            unset($recept['id']);
                            $recept_data = $recept;
                            $recept_data['measure_id'] = $id;
                            Db::table('fa_work_order_recept')->insertGetId($recept_data);

                            $change_sku_data = $v2;
                            $change_sku_data['measure_id'] = $id;
                            $change_sku_data['item_order_number'] = $v3['item_order_number'];
                            Db::table('fa_work_order_change_sku_copy1')->insert($change_sku_data);

                            $item_number[] = $v3['item_order_number'];
                        }
                    }
                } else if ($v1['measure_choose_id'] == 12) {  //措施为更改镜片
                    //查询change sku表内容
                    $change_sku_list = Db::table('fa_work_order_change_sku')->where(['work_id' => $v['id'], 'change_type' => 2, 'measure_id' => $v1['id']])->select();
                    foreach ($change_sku_list as $k2 => $v2) {
                        //查询订单号所有子单
                        $order_list = $order->alias('a')->field('b.item_order_number')
                            ->where(['a.increment_id' => $v2['increment_id'], 'a.site' => $v2['platform_type'], 'b.sku' => $v2['sku']])
                            ->join(['fa_order_item_process' => 'b'], 'a.id=b.order_id')
                            ->select();
                        $measure = [];
                        $change_sku_data = [];
                        $recept_data = [];
                        foreach ($order_list as $k3 => $v3) {
                            $measure['work_id'] = $v['id'];
                            $measure['measure_choose_id'] = 20;
                            $measure['measure_content'] = '更改镜片';
                            $measure['create_time'] = $v1['create_time'];
                            $measure['operation_type'] = $v1['operation_type'];
                            $measure['operation_time'] = $v1['operation_time'];
                            $measure['sku_change_type'] = $v1['sku_change_type'];
                            $measure['item_order_number'] = $v3['item_order_number'];
                            $id = Db::table('fa_work_order_measure_copy1')->insertGetId($measure);

                            unset($recept['id']);
                            $recept_data = $recept;
                            $recept_data['measure_id'] = $id;
                            Db::table('fa_work_order_recept')->insertGetId($recept_data);

                            $change_sku_data = $v2;
                            $change_sku_data['measure_id'] = $id;
                            $change_sku_data['item_order_number'] = $v3['item_order_number'];
                            Db::table('fa_work_order_change_sku_copy1')->insert($change_sku_data);

                            $item_number[] = $v3['item_order_number'];
                        }
                    }
                } else {
                    //插入措施表
                    $id =  Db::table('fa_work_order_measure_copy1')->insertGetId($v1);

                    //查询change sku表
                    $change_sku_list = Db::table('fa_work_order_change_sku')->where(['work_id' => $v['id'], 'measure_id' => $v1['id']])->select();

                    if (!$change_sku_list) continue;
                    $change_sku_data = [];
                    foreach ($change_sku_list as $key => $val) {
                        $change_sku_data = $val;
                        $change_sku_data['measure_id'] = $id;
                        Db::table('fa_work_order_change_sku_copy1')->insert($change_sku_data);
                    }
                }
            }

            //插入子单号
            if ($item_number) {
                $numbers = implode(',', array_filter($item_number));
                Db::table('fa_work_order_list_copy1')->where(['id' => $v['id']])->update(['order_item_numbers' => $numbers]);
            }

            echo $k . "\n";
        }
        echo "ok";
    }
}
