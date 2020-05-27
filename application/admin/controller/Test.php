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
    protected $str1 = '上网';
    protected $str2 = '交航';
    protected $str3 = '运输中';
    protected $str4 = '到达目的地';
    protected $str30 = '到达待取';
    protected $str35 = '投递失败';
    protected $str40 = '成功签收';
    protected $str50 = '可能异常';

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
    }


    public function tongbu_zendesk(){
        $zend = Db::name('zendesk')->field('id,type')->select();
        foreach($zend as $k => $v){
            $update['platform'] = $v['type'];
            Db::name('zendesk_comments')->where('zid', $v['id'])->update($update); 
            echo $v['id'] . "\n";
        }
        
    }

    /**
     * 批量 获取物流明细
     * 莫删除
     */
    public function track_shipment_num()
    {
        $order_shipment = Db::connect('database.db_zeelool')
            ->table('sales_flat_shipment_track')
            ->field('entity_id,track_number,title,updated_at,order_id')
            ->where('created_at', '>=', '2020-3-31 00:00:00')
            ->where('handle', '=', '1')
            ->select();

        $trackingConnector = new TrackingConnector($this->apiKey);

        foreach ($order_shipment as $k => $v) {
            $order_num = Db::connect('database.db_zeelool')
                ->table('sales_flat_order')
                ->field('increment_id')
                ->where('entity_id', '=', $v['order_id'])
                ->find();

            $title = strtolower(str_replace(' ', '-', $v['title']));

            $carrier = $this->getCarrier($title);

            $trackInfo = $trackingConnector->getTrackInfoMulti([[
                'number' => $v['track_number'],
                'carrier' => $carrier['carrierId']
                /*'number' => 'LO546092713CN',//E邮宝
                'carrier' => '03011'*/
                /* 'number' => '3616952791',//DHL
                'carrier' => '100001' */
                /*'number' => 'UF105842059YP', //燕文
                'carrier' => '190012'*/
                /* 'number' => '74890988318620573173', //Fedex
                'carrier' => '100003' */
            ]]);
            
            $add['site'] = 1;
            $add['order_id'] = $v['order_id'];
            $add['order_number'] = $order_num['increment_id'];
            $add['shipment_type'] = $v['title'];
            $add['track_number'] = $v['track_number'];

            if ($trackInfo['code'] == 0 && $trackInfo['data']['accepted']) {
                $trackdata = $trackInfo['data']['accepted'][0]['track'];
                
                if (stripos($v['title'], 'Post') !== false) {
                    $this->china_post_data($trackdata, $add);
                }

                if (stripos($v['title'], 'DHL') !== false) {
                    $this->dhl_data($trackdata, $add);
                }

                if (stripos($v['title'], 'yanwen') !== false) {
                    $this->yanwen_data($trackdata, $add);
                }

                if (stripos($v['title'], 'USPS') !== false) {
                    $this->usps_data($trackdata, $add);
                }

                if (stripos($v['title'], 'fede') !== false) {
                    $this->fedex_data($trackdata, $add);
                }
            }
            echo $v['order_id'].':'. $v['track_number'] . "\n";
            sleep(1);
        }
        exit;
    }
    //fedex
    public function fedex_data($data, $add)
    {
        $trackdetail = array_reverse($data['z1']);
        $time = '';
        $all_num = count($trackdetail);
        foreach ($trackdetail as $k => $v) {
            $add['create_time'] = $v['a'];
            $add['content'] = $v['z'];
            $add['courier_status'] = $data['e'];
            Db::name('order_node_courier')->insert($add); //插入物流日志表

            $order_node_detail['order_node'] = 3;
            $order_node_detail['create_time'] = $v['a'];

            $order_node_detail['handle_user_id'] = 0;
            $order_node_detail['handle_user_name'] = 'system';
            $order_node_detail['site'] = $add['site'];
            $order_node_detail['order_id'] = $add['order_id'];
            $order_node_detail['order_number'] = $add['order_number'];
            $order_node_detail['shipment_type'] = $add['shipment_type'];
            $order_node_detail['track_number'] = $add['track_number'];

            if (stripos($v['z'], 'Shipment information sent to FedEx') !== false) {
                $order_node_date = Db::name('order_node')->where('track_number', $add['track_number'])->find();
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

            if (stripos($v['z'], 'BEIJING CN, In transit') !== false || stripos($v['z'], 'GUANGZHOU CN, In transit') !== false) {
                $order_node_date = Db::name('order_node')->where('track_number', $add['track_number'])->find();
                if ($order_node_date['order_node'] == 3 && $order_node_date['node_type'] == 8) {
                    if ($data['e'] == 40 || $data['e'] == 30 || $data['e'] == 35) {
                        //如果本快递已经签收，则直接插入运输中的数据，并直接把状态更变为运输中
                        $update_order_node['node_type'] = 10;
                        $update_order_node['update_time'] = $v['a'];
                        Db::name('order_node')->where('id', $order_node_date['id'])->update($update_order_node); //更新主表状态
                        $order_node_detail['node_type'] = 9;
                        $order_node_detail['content'] = $this->str2;
                        $order_node_detail['create_time'] = $v['a'];
                        Db::name('order_node_detail')->insert($order_node_detail); //插入节点字表

                        $order_node_detail['node_type'] = 10;
                        $order_node_detail['content'] = $this->str3;
                        $order_node_detail['create_time'] = date('Y-m-d H:i', strtotime(($v['a'] . " +3 day")));
                        Db::name('order_node_detail')->insert($order_node_detail); //插入节点字表
                    } else {
                        $update_order_node['node_type'] = 9;
                        $update_order_node['update_time'] = $v['a'];
                        Db::name('order_node')->where('id', $order_node_date['id'])->update($update_order_node); //更新主表状态
                        $order_node_detail['node_type'] = 9;
                        $order_node_detail['content'] = $this->str2;
                        $order_node_detail['create_time'] = $v['a'];
                        Db::name('order_node_detail')->insert($order_node_detail); //插入节点字表

                        $time = date('Y-m-d H:i', strtotime(($v['a'] . " +3 day")));
                    }
                }
            }

            if (stripos($v['z'], 'Arrived at FedEx location') !== false || stripos($v['z'], 'Departed FedEx location') !== false || stripos($v['z'], 'clearance') !== false) {
                $order_node_date = Db::name('order_node')->where('track_number', $add['track_number'])->find();
                if ($order_node_date['order_node'] == 3 && $order_node_date['node_type'] == 9) {
                    $update_order_node['node_type'] = 11;
                    $update_order_node['update_time'] = $v['a'];
                    Db::name('order_node')->where('id', $order_node_date['id'])->update($update_order_node); //更新主表状态

                    $order_node_detail['node_type'] = 10;
                    $order_node_detail['content'] = $this->str3;
                    $order_node_detail['create_time'] = $time;
                    Db::name('order_node_detail')->insert($order_node_detail); //插入节点字表
                    $time = '';

                    $order_node_detail['node_type'] = 11;
                    $order_node_detail['content'] = $this->str4;
                    $order_node_detail['create_time'] = $v['a'];
                    Db::name('order_node_detail')->insert($order_node_detail); //插入节点字表
                }
                if ($order_node_date['order_node'] == 3 && $order_node_date['node_type'] == 10) {
                    $update_order_node['node_type'] = 11;
                    $update_order_node['update_time'] = $v['a'];
                    Db::name('order_node')->where('id', $order_node_date['id'])->update($update_order_node); //更新主表状态

                    $order_node_detail['node_type'] = 11;
                    $order_node_detail['content'] = $this->str4;
                    $order_node_detail['create_time'] = $v['a'];
                    Db::name('order_node_detail')->insert($order_node_detail); //插入节点字表
                }
            }

            if ($all_num - 1 == $k) {
                $order_node_date = Db::name('order_node')->where('track_number', $add['track_number'])->find();
                if ($order_node_date['order_node'] == 3 && $order_node_date['node_type'] == 11) {
                    $update_order_node['order_node'] = 4;
                    $update_order_node['node_type'] = $data['e'];
                    $update_order_node['update_time'] = $v['a'];
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

    //usps
    public function usps_data($data, $add)
    {
        $trackdetail = array_reverse($data['z1']);
        $time = '';
        $all_num = count($trackdetail);
        foreach ($trackdetail as $k => $v) {
            $add['create_time'] = $v['a'];
            $add['content'] = $v['z'];
            $add['courier_status'] = $data['e'];
            Db::name('order_node_courier')->insert($add); //插入物流日志表

            $order_node_detail['order_node'] = 3;
            $order_node_detail['create_time'] = $v['a'];

            $order_node_detail['handle_user_id'] = 0;
            $order_node_detail['handle_user_name'] = 'system';
            $order_node_detail['site'] = $add['site'];
            $order_node_detail['order_id'] = $add['order_id'];
            $order_node_detail['order_number'] = $add['order_number'];
            $order_node_detail['shipment_type'] = $add['shipment_type'];
            $order_node_detail['track_number'] = $add['track_number'];

            if (stripos($v['z'], 'Picked Up') !== false || stripos($v['z'], 'Shipping Partner Facility') !== false) {
                $order_node_date = Db::name('order_node')->where('track_number', $add['track_number'])->find();
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

            if (stripos($v['z'], 'Delivered to Air Transport') !== false) {
                $order_node_date = Db::name('order_node')->where('track_number', $add['track_number'])->find();
                if ($order_node_date['order_node'] == 3 && $order_node_date['node_type'] == 8) {
                    $update_order_node['node_type'] = 9;
                    $update_order_node['update_time'] = $v['a'];
                    Db::name('order_node')->where('id', $order_node_date['id'])->update($update_order_node); //更新主表状态
                    $order_node_detail['node_type'] = 9;
                    $order_node_detail['content'] = $this->str2;
                    $order_node_detail['create_time'] = $v['a'];
                    Db::name('order_node_detail')->insert($order_node_detail); //插入节点字表
                }
            }

            if (stripos($v['z'], 'In Transit to Next Facility') !== false) {
                $order_node_date = Db::name('order_node')->where('track_number', $add['track_number'])->find();
                if ($order_node_date['order_node'] == 3 && $order_node_date['node_type'] == 9) {
                    $update_order_node['node_type'] = 10;
                    $update_order_node['update_time'] = $v['a'];
                    Db::name('order_node')->where('id', $order_node_date['id'])->update($update_order_node); //更新主表状态
                    $order_node_detail['node_type'] = 10;
                    $order_node_detail['content'] = $this->str3;
                    $order_node_detail['create_time'] = $v['a'];
                    Db::name('order_node_detail')->insert($order_node_detail); //插入节点字表
                }
            }

            if (stripos($v['z'], 'Arrived in the Final Destination Country') !== false) {
                $order_node_date = Db::name('order_node')->where('track_number', $add['track_number'])->find();
                if ($order_node_date['order_node'] == 3 && $order_node_date['node_type'] == 10) {
                    $update_order_node['node_type'] = 11;
                    $update_order_node['update_time'] = $v['a'];
                    Db::name('order_node')->where('id', $order_node_date['id'])->update($update_order_node); //更新主表状态

                    $order_node_detail['node_type'] = 11;
                    $order_node_detail['content'] = $this->str4;
                    $order_node_detail['create_time'] = $v['a'];
                    Db::name('order_node_detail')->insert($order_node_detail); //插入节点字表
                }
            }

            if ($all_num - 1 == $k) {
                $order_node_date = Db::name('order_node')->where('track_number', $add['track_number'])->find();
                if ($order_node_date['order_node'] == 3 && $order_node_date['node_type'] == 11) {
                    $update_order_node['order_node'] = 4;
                    $update_order_node['node_type'] = $data['e'];
                    $update_order_node['update_time'] = $v['a'];
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
    //燕文专线
    public function yanwen_data($data, $add)
    {
        $trackdetail = array_reverse($data['z1']);

        $time = '';
        $all_num = count($trackdetail);
        foreach ($trackdetail as $k => $v) {
            $add['create_time'] = $v['a'];
            $add['content'] = $v['z'];
            $add['courier_status'] = $data['e'];
            Db::name('order_node_courier')->insert($add); //插入物流日志表

            $order_node_detail['order_node'] = 3;
            $order_node_detail['create_time'] = $v['a'];

            $order_node_detail['handle_user_id'] = 0;
            $order_node_detail['handle_user_name'] = 'system';
            $order_node_detail['site'] = $add['site'];
            $order_node_detail['order_id'] = $add['order_id'];
            $order_node_detail['order_number'] = $add['order_number'];
            $order_node_detail['shipment_type'] = $add['shipment_type'];
            $order_node_detail['track_number'] = $add['track_number'];

            if (stripos($v['z'], 'Picked up') !== false) {
                $order_node_date = Db::name('order_node')->where('track_number', $add['track_number'])->find();
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

            if (stripos($v['z'], 'Last mile') !== false) {
                $order_node_date = Db::name('order_node')->where('track_number', $add['track_number'])->find();
                if ($order_node_date['order_node'] == 3 && $order_node_date['node_type'] == 8) {
                    if ($data['e'] == 40 || $data['e'] == 30 || $data['e'] == 35) {
                        //如果本快递已经签收，则直接插入运输中的数据，并直接把状态更变为运输中
                        $update_order_node['node_type'] = 10;
                        $update_order_node['update_time'] = $v['a'];
                        Db::name('order_node')->where('id', $order_node_date['id'])->update($update_order_node); //更新主表状态
                        $order_node_detail['node_type'] = 9;
                        $order_node_detail['content'] = $this->str2;
                        $order_node_detail['create_time'] = $v['a'];
                        Db::name('order_node_detail')->insert($order_node_detail); //插入节点字表

                        $order_node_detail['node_type'] = 10;
                        $order_node_detail['content'] = $this->str3;
                        $order_node_detail['create_time'] = date('Y-m-d H:i', strtotime(($v['a'] . " +5 day")));
                        Db::name('order_node_detail')->insert($order_node_detail); //插入节点字表
                    } else {
                        $update_order_node['node_type'] = 9;
                        $update_order_node['update_time'] = $v['a'];
                        Db::name('order_node')->where('id', $order_node_date['id'])->update($update_order_node); //更新主表状态
                        $order_node_detail['node_type'] = 9;
                        $order_node_detail['content'] = $this->str2;
                        $order_node_detail['create_time'] = $v['a'];
                        Db::name('order_node_detail')->insert($order_node_detail); //插入节点字表

                        $time = date('Y-m-d H:i', strtotime(($v['a'] . " +5 day")));
                    }
                }
            }

            if (stripos($v['z'], 'Shipping information received by') !== false) {
                $order_node_date = Db::name('order_node')->where('track_number', $add['track_number'])->find();
                if ($order_node_date['order_node'] == 3 && $order_node_date['node_type'] == 9) {
                    $update_order_node['node_type'] = 11;
                    $update_order_node['update_time'] = $v['a'];
                    Db::name('order_node')->where('id', $order_node_date['id'])->update($update_order_node); //更新主表状态

                    $order_node_detail['node_type'] = 10;
                    $order_node_detail['content'] = $this->str3;
                    $order_node_detail['create_time'] = $time;
                    Db::name('order_node_detail')->insert($order_node_detail); //插入节点字表
                    $time = '';

                    $order_node_detail['node_type'] = 11;
                    $order_node_detail['content'] = $this->str4;
                    $order_node_detail['create_time'] = $v['a'];
                    Db::name('order_node_detail')->insert($order_node_detail); //插入节点字表
                }
                if ($order_node_date['order_node'] == 3 && $order_node_date['node_type'] == 10) {
                    $update_order_node['node_type'] = 11;
                    $update_order_node['update_time'] = $v['a'];
                    Db::name('order_node')->where('id', $order_node_date['id'])->update($update_order_node); //更新主表状态

                    $order_node_detail['node_type'] = 11;
                    $order_node_detail['content'] = $this->str4;
                    $order_node_detail['create_time'] = $v['a'];
                    Db::name('order_node_detail')->insert($order_node_detail); //插入节点字表
                }
            }

            if ($all_num - 1 == $k) {
                $order_node_date = Db::name('order_node')->where('track_number', $add['track_number'])->find();
                if ($order_node_date['order_node'] == 3 && $order_node_date['node_type'] == 11) {
                    $update_order_node['order_node'] = 4;
                    $update_order_node['node_type'] = $data['e'];
                    $update_order_node['update_time'] = $v['a'];
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
    //DHL
    public function dhl_data($data, $add)
    {
        $trackdetail = array_reverse($data['z1']);

        $time = '';
        $all_num = count($trackdetail);
        foreach ($trackdetail as $k => $v) {
            $add['create_time'] = $v['a'];
            $add['content'] = $v['z'];
            $add['courier_status'] = $data['e'];
            Db::name('order_node_courier')->insert($add); //插入物流日志表

            $order_node_detail['order_node'] = 3;
            $order_node_detail['create_time'] = $v['a'];

            $order_node_detail['handle_user_id'] = 0;
            $order_node_detail['handle_user_name'] = 'system';
            $order_node_detail['site'] = $add['site'];
            $order_node_detail['order_id'] = $add['order_id'];
            $order_node_detail['order_number'] = $add['order_number'];
            $order_node_detail['shipment_type'] = $add['shipment_type'];
            $order_node_detail['track_number'] = $add['track_number'];

            if ($data['e'] != 0) {
                if ($k == 1) { //第二条作为上网
                    $order_node_date = Db::name('order_node')->where('track_number', $add['track_number'])->find();
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
            }


            if (stripos($v['z'], 'Departed Facility') !== false) {
                $order_node_date = Db::name('order_node')->where('track_number', $add['track_number'])->find();
                if ($order_node_date['order_node'] == 3 && $order_node_date['node_type'] == 8) {
                    if ($data['e'] == 40 || $data['e'] == 30 || $data['e'] == 35) {
                        //如果本快递已经签收，则直接插入运输中的数据，并直接把状态更变为运输中
                        $update_order_node['node_type'] = 10;
                        $update_order_node['update_time'] = $v['a'];
                        Db::name('order_node')->where('id', $order_node_date['id'])->update($update_order_node); //更新主表状态
                        $order_node_detail['node_type'] = 9;
                        $order_node_detail['content'] = $this->str2;
                        $order_node_detail['create_time'] = $v['a'];
                        Db::name('order_node_detail')->insert($order_node_detail); //插入节点字表

                        $order_node_detail['node_type'] = 10;
                        $order_node_detail['content'] = $this->str3;
                        $order_node_detail['create_time'] = date('Y-m-d H:i', strtotime(($v['a'] . " +3 day")));
                        Db::name('order_node_detail')->insert($order_node_detail); //插入节点字表
                    } else {
                        $update_order_node['node_type'] = 9;
                        $update_order_node['update_time'] = $v['a'];
                        Db::name('order_node')->where('id', $order_node_date['id'])->update($update_order_node); //更新主表状态
                        $order_node_detail['node_type'] = 9;
                        $order_node_detail['content'] = $this->str2;
                        $order_node_detail['create_time'] = $v['a'];
                        Db::name('order_node_detail')->insert($order_node_detail); //插入节点字表

                        $time = date('Y-m-d H:i', strtotime(($v['a'] . " +3 day")));
                    }
                }
            }

            if (stripos($v['z'], 'Customs status updated') !== false) {
                $order_node_date = Db::name('order_node')->where('track_number', $add['track_number'])->find();
                if ($order_node_date['order_node'] == 3 && $order_node_date['node_type'] == 9) {
                    $update_order_node['node_type'] = 11;
                    $update_order_node['update_time'] = $v['a'];
                    Db::name('order_node')->where('id', $order_node_date['id'])->update($update_order_node); //更新主表状态

                    $order_node_detail['node_type'] = 10;
                    $order_node_detail['content'] = $this->str3;
                    $order_node_detail['create_time'] = $time;
                    Db::name('order_node_detail')->insert($order_node_detail); //插入节点字表
                    $time = '';

                    $order_node_detail['node_type'] = 11;
                    $order_node_detail['content'] = $this->str4;
                    $order_node_detail['create_time'] = $v['a'];
                    Db::name('order_node_detail')->insert($order_node_detail); //插入节点字表
                }
                if ($order_node_date['order_node'] == 3 && $order_node_date['node_type'] == 10) {
                    $update_order_node['node_type'] = 11;
                    $update_order_node['update_time'] = $v['a'];
                    Db::name('order_node')->where('id', $order_node_date['id'])->update($update_order_node); //更新主表状态

                    $order_node_detail['node_type'] = 11;
                    $order_node_detail['content'] = $this->str4;
                    $order_node_detail['create_time'] = $v['a'];
                    Db::name('order_node_detail')->insert($order_node_detail); //插入节点字表
                }
            }

            if ($all_num - 1 == $k) {
                $order_node_date = Db::name('order_node')->where('track_number', $add['track_number'])->find();
                if ($order_node_date['order_node'] == 3 && $order_node_date['node_type'] == 11) {
                    $update_order_node['order_node'] = 4;
                    $update_order_node['node_type'] = $data['e'];
                    $update_order_node['update_time'] = $v['a'];
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
    //E邮宝
    public function china_post_data($data, $add)
    {
        $trackdetail = array_reverse($data['z1']);

        $time = '';
        $all_num = count($trackdetail);
        foreach ($trackdetail as $k => $v) {
            $add['create_time'] = $v['a'];
            $add['content'] = $v['z'];
            $add['courier_status'] = $data['e'];
            Db::name('order_node_courier')->insert($add); //插入物流日志表

            $order_node_detail['order_node'] = 3;
            $order_node_detail['create_time'] = $v['a'];

            $order_node_detail['handle_user_id'] = 0;
            $order_node_detail['handle_user_name'] = 'system';
            $order_node_detail['site'] = $add['site'];
            $order_node_detail['order_id'] = $add['order_id'];
            $order_node_detail['order_number'] = $add['order_number'];
            $order_node_detail['shipment_type'] = $add['shipment_type'];
            $order_node_detail['track_number'] = $add['track_number'];

            if (stripos($v['z'], '已收件，揽投员') !== false) {
                $order_node_date = Db::name('order_node')->where('track_number', $add['track_number'])->find();
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

            if (stripos($v['z'], '已交航空公司运输') !== false) {
                $order_node_date = Db::name('order_node')->where('track_number', $add['track_number'])->find();
                if ($order_node_date['order_node'] == 3 && $order_node_date['node_type'] == 8) {
                    if ($data['e'] == 40 || $data['e'] == 30 || $data['e'] == 35) {
                        //如果本快递已经签收，则直接插入运输中的数据，并直接把状态更变为运输中
                        $update_order_node['node_type'] = 10;
                        $update_order_node['update_time'] = $v['a'];
                        Db::name('order_node')->where('id', $order_node_date['id'])->update($update_order_node); //更新主表状态
                        $order_node_detail['node_type'] = 9;
                        $order_node_detail['content'] = $this->str2;
                        $order_node_detail['create_time'] = $v['a'];
                        Db::name('order_node_detail')->insert($order_node_detail); //插入节点字表

                        $order_node_detail['node_type'] = 10;
                        $order_node_detail['content'] = $this->str3;
                        $order_node_detail['create_time'] = date('Y-m-d H:i', strtotime(($v['a'] . " +7 day")));
                        Db::name('order_node_detail')->insert($order_node_detail); //插入节点字表
                    } else {
                        $update_order_node['node_type'] = 9;
                        $update_order_node['update_time'] = $v['a'];
                        Db::name('order_node')->where('id', $order_node_date['id'])->update($update_order_node); //更新主表状态
                        $order_node_detail['node_type'] = 9;
                        $order_node_detail['content'] = $this->str2;
                        $order_node_detail['create_time'] = $v['a'];
                        Db::name('order_node_detail')->insert($order_node_detail); //插入节点字表

                        $time = date('Y-m-d H:i', strtotime(($v['a'] . " +7 day")));
                    }
                }
            }

            if (stripos($v['z'], '已到达寄达地') !== false) {
                $order_node_date = Db::name('order_node')->where('track_number', $add['track_number'])->find();
                if ($order_node_date['order_node'] == 3 && $order_node_date['node_type'] == 9) {
                    $update_order_node['node_type'] = 11;
                    $update_order_node['update_time'] = $v['a'];
                    Db::name('order_node')->where('id', $order_node_date['id'])->update($update_order_node); //更新主表状态

                    $order_node_detail['node_type'] = 10;
                    $order_node_detail['content'] = $this->str3;
                    $order_node_detail['create_time'] = $time;
                    Db::name('order_node_detail')->insert($order_node_detail); //插入节点字表
                    $time = '';

                    $order_node_detail['node_type'] = 11;
                    $order_node_detail['content'] = $this->str4;
                    $order_node_detail['create_time'] = $v['a'];
                    Db::name('order_node_detail')->insert($order_node_detail); //插入节点字表
                }
                if ($order_node_date['order_node'] == 3 && $order_node_date['node_type'] == 10) {
                    $update_order_node['node_type'] = 11;
                    $update_order_node['update_time'] = $v['a'];
                    Db::name('order_node')->where('id', $order_node_date['id'])->update($update_order_node); //更新主表状态

                    $order_node_detail['node_type'] = 11;
                    $order_node_detail['content'] = $this->str4;
                    $order_node_detail['create_time'] = $v['a'];
                    Db::name('order_node_detail')->insert($order_node_detail); //插入节点字表
                }
            }

            if ($all_num - 1 == $k) {
                $order_node_date = Db::name('order_node')->where('track_number', $add['track_number'])->find();
                if ($order_node_date['order_node'] == 3 && $order_node_date['node_type'] == 11) {
                    $update_order_node['order_node'] = 4;
                    $update_order_node['node_type'] = $data['e'];
                    $update_order_node['update_time'] = $v['a'];
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


    /**
     * 批量 注册物流
     * 莫删除
     */
    public function reg_shipment()
    {
        $order_shipment = Db::connect('database.db_voogueme')
            ->table('sales_flat_shipment_track')
            ->field('entity_id,order_id,track_number,title,updated_at')
            ->where('created_at', '>=', '2020-03-31 00:00:00')
            ->where('handle', '=', '0')
            ->select();

        foreach ($order_shipment as $k => $v) {
            $title = strtolower(str_replace(' ', '-', $v['title']));
            if ($title == 'china-post') {
                $order_shipment[$k]['title'] = 'china-ems';
            }
            $carrier = $this->getCarrier($v['title']);
            $shipment_reg[$k]['number'] =  $v['track_number'];
            $shipment_reg[$k]['carrier'] =  $carrier['carrierId'];
            $shipment_reg[$k]['order_id'] =  $v['order_id'];
        }

        $order_group = array_chunk($shipment_reg, 40);

        $trackingConnector = new TrackingConnector($this->apiKey);
        $order_ids = array();
        foreach ($order_group as $key => $val) {
            $aa = $trackingConnector->registerMulti($val);

            //请求接口更改物流表状态
            $order_ids = implode(',',array_column($val, 'order_id'));
            $params['ids'] = $order_ids;
            $params['site'] = 2;
            $res = $this->setLogisticsStatus($params);
            if ($res->status !== 200) {
                echo '更新失败:'.$order_ids . "\n";
            }
            $order_ids = array();

            echo $key . "\n";
            sleep(1);
        }
        dump($order_group[$key]);
        echo 'all is ok' . "\n";
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
     * 获取订单节点数据
     *
     * @Description
     * @author wpl
     * @since 2020/05/14 09:55:00 
     * @return void
     */
    public function setOrderNoteData()
    {
        $users = $this->user->column('id', 'nickname');
        $field = 'status,custom_print_label_new,custom_print_label_person_new,custom_print_label_created_at_new,custom_is_match_frame_new,custom_match_frame_person_new,
        custom_match_frame_created_at_new,custom_is_match_lens_new,custom_match_lens_created_at_new,custom_match_lens_person_new,custom_is_send_factory_new,
        custom_match_factory_person_new,custom_match_factory_created_at_new,custom_is_delivery_new,custom_match_delivery_person_new,custom_match_delivery_created_at_new,
        custom_order_prescription_type,a.created_at,a.updated_at,b.track_number,b.created_at as create_time,b.title,a.entity_id,a.increment_id,a.custom_order_prescription_type
        ';
        $map['a.created_at'] = ['>=', '2020-05-25 16:20:00'];
        $map['a.status'] = ['in', ['free_processing', 'processing', 'complete', 'paypal_reversed', 'paypal_canceled_reversal', 'payment_review']];
        $zeelool_data = $this->zeelool->alias('a')->field($field)
            ->join(['sales_flat_shipment_track' => 'b'], 'a.entity_id=b.order_id', 'left')
            ->where($map)->select();
        foreach ($zeelool_data as $key => $v) {
            $list = [];
            $k = 0;
            //下单
            $list[$k]['order_node'] = 0;
            $list[$k]['node_type'] = 0;
            $list[$k]['content'] = 'Your order has been created.';
            $list[$k]['create_time'] = $v['created_at'];
            $list[$k]['site'] = 1;
            $list[$k]['order_id'] = $v['entity_id'];
            $list[$k]['order_number'] = $v['increment_id'];
            $list[$k]['shipment_type'] = '';
            $list[$k]['track_number'] = '';
            $list[$k]['handle_user_id'] = 0;
            $list[$k]['handle_user_name'] = '';
            $data['order_node'] = 0;
            $data['node_type'] = 0;

            if (in_array($v['status'], ['processing', 'complete', 'paypal_reversed', 'paypal_canceled_reversal', 'payment_review'])) {

                //支付
                $list[$k + 1]['order_node'] = 0;
                $list[$k + 1]['node_type'] = 1;
                $list[$k + 1]['content'] = 'Your payment has been successful.';
                $list[$k + 1]['create_time'] = $v['updated_at'];
                $list[$k + 1]['site'] = 1;
                $list[$k + 1]['order_id'] = $v['entity_id'];
                $list[$k + 1]['order_number'] = $v['increment_id'];
                $list[$k + 1]['shipment_type'] = '';
                $list[$k + 1]['track_number'] = '';
                $list[$k + 1]['handle_user_id'] = 0;
                $list[$k + 1]['handle_user_name'] = '';

                $data['order_node'] = 0;
                $data['node_type'] = 1;
            }

            $data['create_time'] = $v['created_at'];
            $data['site'] = 1;
            $data['order_id'] = $v['entity_id'];
            $data['order_number'] = $v['increment_id'];
            $data['update_time'] = $v['created_at'];
            //打标签
            if ($v['custom_print_label_new'] == 1) {
                $list[$k + 2]['order_node'] = 1;
                $list[$k + 2]['node_type'] = 2;
                $list[$k + 2]['content'] = 'Order is under processing';
                $list[$k + 2]['create_time'] = $v['custom_print_label_created_at_new'];
                $list[$k + 2]['site'] = 1;
                $list[$k + 2]['order_id'] = $v['entity_id'];
                $list[$k + 2]['order_number'] = $v['increment_id'];
                $list[$k + 2]['handle_user_id'] = $users[$v['custom_print_label_person_new']];
                $list[$k + 2]['handle_user_name'] = $v['custom_print_label_person_new'];
                $list[$k + 2]['shipment_type'] = '';
                $list[$k + 2]['track_number'] = '';

                $data['order_node'] = 1;
                $data['node_type'] = 2;
                $data['update_time'] = $v['custom_print_label_created_at_new'];
            }

            //判断订单是否为仅镜架
            if ($v['custom_order_prescription_type'] == 1) {
                if ($v['custom_is_match_frame_new'] == 1) {
                    $list[$k + 3]['order_node'] = 2;
                    $list[$k + 3]['node_type'] = 3;
                    $list[$k + 3]['content'] = 'The product(s) is/are ready, waiting for Quality Inspection';
                    $list[$k + 3]['create_time'] = $v['custom_match_frame_created_at_new'];
                    $list[$k + 3]['site'] = 1;
                    $list[$k + 3]['order_id'] = $v['entity_id'];
                    $list[$k + 3]['order_number'] = $v['increment_id'];
                    $list[$k + 3]['handle_user_id'] = $users[$v['custom_match_frame_person_new']];
                    $list[$k + 3]['handle_user_name'] = $v['custom_match_frame_person_new'];
                    $list[$k + 3]['shipment_type'] = '';
                    $list[$k + 3]['track_number'] = '';

                    $data['order_node'] = 2;
                    $data['node_type'] = 3;
                    $data['update_time'] = $v['custom_match_frame_created_at_new'];
                }

                if ($v['custom_is_delivery_new'] == 1) {
                    $list[$k + 4]['order_node'] = 2;
                    $list[$k + 4]['node_type'] = 6;
                    $list[$k + 4]['content'] = 'Quality Inspection completed, preparing to dispatch this mail piece.';
                    $list[$k + 4]['create_time'] = $v['custom_match_delivery_created_at_new'];
                    $list[$k + 4]['site'] = 1;
                    $list[$k + 4]['order_id'] = $v['entity_id'];
                    $list[$k + 4]['order_number'] = $v['increment_id'];
                    $list[$k + 4]['handle_user_id'] = $users[$v['custom_match_delivery_person_new']];
                    $list[$k + 4]['handle_user_name'] = $v['custom_match_delivery_person_new'];
                    $list[$k + 4]['shipment_type'] = '';
                    $list[$k + 4]['track_number'] = '';

                    $data['order_node'] = 2;
                    $data['node_type'] = 6;
                    $data['update_time'] = $v['custom_match_delivery_created_at_new'];
                }

                if ($v['track_number']) {
                    $list[$k + 5]['order_node'] = 2;
                    $list[$k + 5]['node_type'] = 7; //出库
                    $list[$k + 5]['content']  = '';
                    $list[$k + 5]['create_time'] = $v['create_time'];
                    $list[$k + 5]['site'] = 1;
                    $list[$k + 5]['order_id'] = $v['entity_id'];
                    $list[$k + 5]['order_number'] = $v['increment_id'];
                    $list[$k + 5]['shipment_type'] = $v['title'];
                    $list[$k + 5]['track_number'] = $v['track_number'];
                    $list[$k + 5]['handle_user_id'] = 0;
                    $list[$k + 5]['handle_user_name'] = '';

                    $data['order_node'] = 2;
                    $data['node_type'] = 7;
                    $data['update_time'] = $v['create_time'];
                }
            } else {

                if ($v['custom_is_match_frame_new'] == 1) {
                    $list[$k + 3]['order_node'] = 2;
                    $list[$k + 3]['node_type'] = 3; //配镜架
                    $list[$k + 3]['content'] = 'Frame(s) is/are ready, waiting for lenses';
                    $list[$k + 3]['create_time'] = $v['custom_match_frame_created_at_new'];
                    $list[$k + 3]['site'] = 1;
                    $list[$k + 3]['order_id'] = $v['entity_id'];
                    $list[$k + 3]['order_number'] = $v['increment_id'];
                    $list[$k + 3]['handle_user_id'] = $users[$v['custom_match_frame_person_new']];
                    $list[$k + 3]['handle_user_name'] = $v['custom_match_frame_person_new'];
                    $list[$k + 3]['shipment_type'] = '';
                    $list[$k + 3]['track_number'] = '';

                    $data['order_node'] = 2;
                    $data['node_type'] = 3;
                    $data['update_time'] = $v['custom_match_frame_created_at_new'];
                }

                if ($v['custom_is_match_lens_new'] == 1) {
                    $list[$k + 4]['order_node'] = 2;
                    $list[$k + 4]['node_type'] = 4; //配镜片
                    $list[$k + 4]['content'] = 'Lenses production completed, waiting for customizing';
                    $list[$k + 4]['create_time'] = $v['custom_match_lens_created_at_new'];
                    $list[$k + 4]['site'] = 1;
                    $list[$k + 4]['order_id'] = $v['entity_id'];
                    $list[$k + 4]['order_number'] = $v['increment_id'];
                    $list[$k + 4]['handle_user_id'] = $users[$v['custom_match_lens_person_new']];
                    $list[$k + 4]['handle_user_name'] = $v['custom_match_lens_person_new'];
                    $list[$k + 4]['shipment_type'] = '';
                    $list[$k + 4]['track_number'] = '';

                    $data['order_node'] = 2;
                    $data['node_type'] = 4;
                    $data['update_time'] = $v['custom_match_lens_created_at_new'];
                }

                if ($v['custom_is_send_factory_new'] == 1) {
                    $list[$k + 5]['order_node'] = 2;
                    $list[$k + 5]['node_type'] = 5; //加工
                    $list[$k + 5]['content'] = 'Customizing completed, waiting for Quality Inspection';
                    $list[$k + 5]['create_time'] = $v['custom_match_factory_created_at_new'];
                    $list[$k + 5]['site'] = 1;
                    $list[$k + 5]['order_id'] = $v['entity_id'];
                    $list[$k + 5]['order_number'] = $v['increment_id'];
                    $list[$k + 5]['handle_user_id'] = $users[$v['custom_match_factory_person_new']];
                    $list[$k + 5]['handle_user_name'] = $v['custom_match_factory_person_new'];
                    $list[$k + 5]['shipment_type'] = '';
                    $list[$k + 5]['track_number'] = '';

                    $data['order_node'] = 2;
                    $data['node_type'] = 5;
                    $data['update_time'] = $v['custom_match_factory_created_at_new'];
                }


                if ($v['custom_is_delivery_new'] == 1) {
                    $list[$k + 6]['order_node'] = 2;
                    $list[$k + 6]['node_type'] = 6; //质检
                    $list[$k + 6]['content'] = 'Quality Inspection completed, preparing to dispatch this mail piece.';
                    $list[$k + 6]['create_time'] = $v['custom_match_delivery_created_at_new'];
                    $list[$k + 6]['site'] = 1;
                    $list[$k + 6]['order_id'] = $v['entity_id'];
                    $list[$k + 6]['order_number'] = $v['increment_id'];
                    $list[$k + 6]['handle_user_id'] = $users[$v['custom_match_delivery_person_new']];
                    $list[$k + 6]['handle_user_name'] = $v['custom_match_delivery_person_new'];
                    $list[$k + 6]['shipment_type'] = '';
                    $list[$k + 6]['track_number'] = '';

                    $data['order_node'] = 2;
                    $data['node_type'] = 6;
                    $data['update_time'] = $v['custom_match_delivery_created_at_new'];
                }

                if ($v['track_number']) {
                    $list[$k + 7]['order_node'] = 2;
                    $list[$k + 7]['node_type'] = 7; //出库
                    $list[$k + 7]['create_time'] = $v['create_time'];
                    $list[$k + 7]['site'] = 1;
                    $list[$k + 7]['order_id'] = $v['entity_id'];
                    $list[$k + 7]['order_number'] = $v['increment_id'];
                    $list[$k + 7]['shipment_type'] = $v['title'];
                    $list[$k + 7]['track_number'] = $v['track_number'];
                    $list[$k + 7]['handle_user_id'] = 0;
                    $list[$k + 7]['handle_user_name'] = '';
                    $list[$k + 7]['content'] = '';

                    $data['order_node'] = 2;
                    $data['node_type'] = 7;
                    $data['update_time'] = $v['create_time'];
                }
            }
            $data['shipment_type'] = $v['title'];
            $data['track_number'] = $v['track_number'];


            $count = Db::name('order_node')->where('order_id', $v['entity_id'])->count();
            if ($count > 0) {
                Db::name('order_node')->where('order_id', $v['entity_id'])->update($data);
            } else {
                Db::name('order_node')->insert($data);
            }
            $this->ordernodedetail->saveAll($list);
            echo $key . "\n";
        }
        echo 'ok';
    }

    /**
     * 获取订单节点数据
     *
     * @Description
     * @author wpl
     * @since 2020/05/14 09:55:00 
     * @return void
     */
    public function setOrderNoteDataVoogueme()
    {
        $users = $this->user->column('id', 'nickname');
        $field = 'status,custom_print_label_new,custom_print_label_person_new,custom_print_label_created_at_new,custom_is_match_frame_new,custom_match_frame_person_new,
        custom_match_frame_created_at_new,custom_is_match_lens_new,custom_match_lens_created_at_new,custom_match_lens_person_new,custom_is_send_factory_new,
        custom_match_factory_person_new,custom_match_factory_created_at_new,custom_is_delivery_new,custom_match_delivery_person_new,custom_match_delivery_created_at_new,
        custom_order_prescription_type,a.created_at,a.updated_at,b.track_number,b.created_at as create_time,b.title,a.entity_id,a.increment_id,a.custom_order_prescription_type
        ';
        $map['a.created_at'] = ['>=', '2020-05-25 15:40:52'];
        $map['a.status'] = ['in', ['free_processing', 'processing', 'complete', 'paypal_reversed', 'paypal_canceled_reversal', 'payment_review']];
        $zeelool_data = $this->voogueme->alias('a')->field($field)
            ->join(['sales_flat_shipment_track' => 'b'], 'a.entity_id=b.order_id', 'left')
            ->where($map)->select();
        foreach ($zeelool_data as $key => $v) {
            
            $count = Db::name('order_node')->where('order_id', $v['entity_id'])->count();
            if ($count > 0) {
                continue;
            }


            $list = [];
            $k = 0;
            //下单
            $list[$k]['order_node'] = 0;
            $list[$k]['node_type'] = 0;
            $list[$k]['content'] = 'Your order has been created.';
            $list[$k]['create_time'] = $v['created_at'];
            $list[$k]['site'] = 2;
            $list[$k]['order_id'] = $v['entity_id'];
            $list[$k]['order_number'] = $v['increment_id'];
            $list[$k]['shipment_type'] = '';
            $list[$k]['track_number'] = '';
            $list[$k]['handle_user_id'] = 0;
            $list[$k]['handle_user_name'] = '';
            $data['order_node'] = 0;
            $data['node_type'] = 0;

            if (in_array($v['status'], ['processing', 'complete', 'paypal_reversed', 'paypal_canceled_reversal', 'payment_review'])) {

                //支付
                $list[$k + 1]['order_node'] = 0;
                $list[$k + 1]['node_type'] = 1;
                $list[$k + 1]['content'] = 'Your payment has been successful.';
                $list[$k + 1]['create_time'] = $v['updated_at'];
                $list[$k + 1]['site'] = 2;
                $list[$k + 1]['order_id'] = $v['entity_id'];
                $list[$k + 1]['order_number'] = $v['increment_id'];
                $list[$k + 1]['shipment_type'] = '';
                $list[$k + 1]['track_number'] = '';
                $list[$k + 1]['handle_user_id'] = 0;
                $list[$k + 1]['handle_user_name'] = '';

                $data['order_node'] = 0;
                $data['node_type'] = 1;
            }

            $data['create_time'] = $v['created_at'];
            $data['site'] = 2;
            $data['order_id'] = $v['entity_id'];
            $data['order_number'] = $v['increment_id'];
            $data['update_time'] = $v['created_at'];
            //打标签
            if ($v['custom_print_label_new'] == 1) {
                $list[$k + 2]['order_node'] = 1;
                $list[$k + 2]['node_type'] = 2;
                $list[$k + 2]['content'] = 'Order is under processing';
                $list[$k + 2]['create_time'] = $v['custom_print_label_created_at_new'];
                $list[$k + 2]['site'] = 2;
                $list[$k + 2]['order_id'] = $v['entity_id'];
                $list[$k + 2]['order_number'] = $v['increment_id'];
                $list[$k + 2]['handle_user_id'] = $users[$v['custom_print_label_person_new']];
                $list[$k + 2]['handle_user_name'] = $v['custom_print_label_person_new'];
                $list[$k + 2]['shipment_type'] = '';
                $list[$k + 2]['track_number'] = '';

                $data['order_node'] = 1;
                $data['node_type'] = 2;
                $data['update_time'] = $v['custom_print_label_created_at_new'];
            }

            //判断订单是否为仅镜架
            if ($v['custom_order_prescription_type'] == 1) {
                if ($v['custom_is_match_frame_new'] == 1) {
                    $list[$k + 3]['order_node'] = 2;
                    $list[$k + 3]['node_type'] = 3;
                    $list[$k + 3]['content'] = 'The product(s) is/are ready, waiting for Quality Inspection';
                    $list[$k + 3]['create_time'] = $v['custom_match_frame_created_at_new'];
                    $list[$k + 3]['site'] = 2;
                    $list[$k + 3]['order_id'] = $v['entity_id'];
                    $list[$k + 3]['order_number'] = $v['increment_id'];
                    $list[$k + 3]['handle_user_id'] = $users[$v['custom_match_frame_person_new']];
                    $list[$k + 3]['handle_user_name'] = $v['custom_match_frame_person_new'];
                    $list[$k + 3]['shipment_type'] = '';
                    $list[$k + 3]['track_number'] = '';

                    $data['order_node'] = 2;
                    $data['node_type'] = 3;
                    $data['update_time'] = $v['custom_match_frame_created_at_new'];
                }

                if ($v['custom_is_delivery_new'] == 1) {
                    $list[$k + 4]['order_node'] = 2;
                    $list[$k + 4]['node_type'] = 6;
                    $list[$k + 4]['content'] = 'Quality Inspection completed, preparing to dispatch this mail piece.';
                    $list[$k + 4]['create_time'] = $v['custom_match_delivery_created_at_new'];
                    $list[$k + 4]['site'] = 2;
                    $list[$k + 4]['order_id'] = $v['entity_id'];
                    $list[$k + 4]['order_number'] = $v['increment_id'];
                    $list[$k + 4]['handle_user_id'] = $users[$v['custom_match_delivery_person_new']];
                    $list[$k + 4]['handle_user_name'] = $v['custom_match_delivery_person_new'];
                    $list[$k + 4]['shipment_type'] = '';
                    $list[$k + 4]['track_number'] = '';

                    $data['order_node'] = 2;
                    $data['node_type'] = 6;
                    $data['update_time'] = $v['custom_match_delivery_created_at_new'];
                }

                if ($v['track_number']) {
                    $list[$k + 5]['order_node'] = 2;
                    $list[$k + 5]['node_type'] = 7; //出库
                    $list[$k + 5]['content']  = '';
                    $list[$k + 5]['create_time'] = $v['create_time'];
                    $list[$k + 5]['site'] = 2;
                    $list[$k + 5]['order_id'] = $v['entity_id'];
                    $list[$k + 5]['order_number'] = $v['increment_id'];
                    $list[$k + 5]['shipment_type'] = $v['title'];
                    $list[$k + 5]['track_number'] = $v['track_number'];
                    $list[$k + 5]['handle_user_id'] = 0;
                    $list[$k + 5]['handle_user_name'] = '';

                    $data['order_node'] = 2;
                    $data['node_type'] = 7;
                    $data['update_time'] = $v['create_time'];
                }
            } else {

                if ($v['custom_is_match_frame_new'] == 1) {
                    $list[$k + 3]['order_node'] = 2;
                    $list[$k + 3]['node_type'] = 3; //配镜架
                    $list[$k + 3]['content'] = 'Frame(s) is/are ready, waiting for lenses';
                    $list[$k + 3]['create_time'] = $v['custom_match_frame_created_at_new'];
                    $list[$k + 3]['site'] = 2;
                    $list[$k + 3]['order_id'] = $v['entity_id'];
                    $list[$k + 3]['order_number'] = $v['increment_id'];
                    $list[$k + 3]['handle_user_id'] = $users[$v['custom_match_frame_person_new']];
                    $list[$k + 3]['handle_user_name'] = $v['custom_match_frame_person_new'];
                    $list[$k + 3]['shipment_type'] = '';
                    $list[$k + 3]['track_number'] = '';

                    $data['order_node'] = 2;
                    $data['node_type'] = 3;
                    $data['update_time'] = $v['custom_match_frame_created_at_new'];
                }

                if ($v['custom_is_match_lens_new'] == 1) {
                    $list[$k + 4]['order_node'] = 2;
                    $list[$k + 4]['node_type'] = 4; //配镜片
                    $list[$k + 4]['content'] = 'Lenses production completed, waiting for customizing';
                    $list[$k + 4]['create_time'] = $v['custom_match_lens_created_at_new'];
                    $list[$k + 4]['site'] = 2;
                    $list[$k + 4]['order_id'] = $v['entity_id'];
                    $list[$k + 4]['order_number'] = $v['increment_id'];
                    $list[$k + 4]['handle_user_id'] = $users[$v['custom_match_lens_person_new']];
                    $list[$k + 4]['handle_user_name'] = $v['custom_match_lens_person_new'];
                    $list[$k + 4]['shipment_type'] = '';
                    $list[$k + 4]['track_number'] = '';

                    $data['order_node'] = 2;
                    $data['node_type'] = 4;
                    $data['update_time'] = $v['custom_match_lens_created_at_new'];
                }

                if ($v['custom_is_send_factory_new'] == 1) {
                    $list[$k + 5]['order_node'] = 2;
                    $list[$k + 5]['node_type'] = 5; //加工
                    $list[$k + 5]['content'] = 'Customizing completed, waiting for Quality Inspection';
                    $list[$k + 5]['create_time'] = $v['custom_match_factory_created_at_new'];
                    $list[$k + 5]['site'] = 2;
                    $list[$k + 5]['order_id'] = $v['entity_id'];
                    $list[$k + 5]['order_number'] = $v['increment_id'];
                    $list[$k + 5]['handle_user_id'] = $users[$v['custom_match_factory_person_new']];
                    $list[$k + 5]['handle_user_name'] = $v['custom_match_factory_person_new'];
                    $list[$k + 5]['shipment_type'] = '';
                    $list[$k + 5]['track_number'] = '';

                    $data['order_node'] = 2;
                    $data['node_type'] = 5;
                    $data['update_time'] = $v['custom_match_factory_created_at_new'];
                }


                if ($v['custom_is_delivery_new'] == 1) {
                    $list[$k + 6]['order_node'] = 2;
                    $list[$k + 6]['node_type'] = 6; //质检
                    $list[$k + 6]['content'] = 'Quality Inspection completed, preparing to dispatch this mail piece.';
                    $list[$k + 6]['create_time'] = $v['custom_match_delivery_created_at_new'];
                    $list[$k + 6]['site'] = 2;
                    $list[$k + 6]['order_id'] = $v['entity_id'];
                    $list[$k + 6]['order_number'] = $v['increment_id'];
                    $list[$k + 6]['handle_user_id'] = $users[$v['custom_match_delivery_person_new']];
                    $list[$k + 6]['handle_user_name'] = $v['custom_match_delivery_person_new'];
                    $list[$k + 6]['shipment_type'] = '';
                    $list[$k + 6]['track_number'] = '';

                    $data['order_node'] = 2;
                    $data['node_type'] = 6;
                    $data['update_time'] = $v['custom_match_delivery_created_at_new'];
                }

                if ($v['track_number']) {
                    $list[$k + 7]['order_node'] = 2;
                    $list[$k + 7]['node_type'] = 7; //出库
                    $list[$k + 7]['create_time'] = $v['create_time'];
                    $list[$k + 7]['site'] = 2;
                    $list[$k + 7]['order_id'] = $v['entity_id'];
                    $list[$k + 7]['order_number'] = $v['increment_id'];
                    $list[$k + 7]['shipment_type'] = $v['title'];
                    $list[$k + 7]['track_number'] = $v['track_number'];
                    $list[$k + 7]['handle_user_id'] = 0;
                    $list[$k + 7]['handle_user_name'] = '';
                    $list[$k + 7]['content'] = '';

                    $data['order_node'] = 2;
                    $data['node_type'] = 7;
                    $data['update_time'] = $v['create_time'];
                }
            }
            $data['shipment_type'] = $v['title'];
            $data['track_number'] = $v['track_number'];


            if ($count > 0) {
                Db::name('order_node')->where('order_id', $v['entity_id'])->update($data);
            } else {
                Db::name('order_node')->insert($data);
            }
            $this->ordernodedetail->saveAll($list);
            echo $key . "\n";
        }
        echo 'ok';
    }


    /**
     * 获取订单节点数据
     *
     * @Description
     * @author wpl
     * @since 2020/05/14 09:55:00 
     * @return void
     */
    public function setOrderNoteDataNihao()
    {
        $users = $this->user->column('id', 'nickname');
        $field = 'status,custom_print_label_new,custom_print_label_person_new,custom_print_label_created_at_new,custom_is_match_frame_new,custom_match_frame_person_new,
        custom_match_frame_created_at_new,custom_is_match_lens_new,custom_match_lens_created_at_new,custom_match_lens_person_new,custom_is_send_factory_new,
        custom_match_factory_person_new,custom_match_factory_created_at_new,custom_is_delivery_new,custom_match_delivery_person_new,custom_match_delivery_created_at_new,
        custom_order_prescription_type,a.created_at,a.updated_at,b.track_number,b.created_at as create_time,b.title,a.entity_id,a.increment_id,a.custom_order_prescription_type
        ';
        $map['a.created_at'] = ['>=', '2020-03-31 00:00:00'];
        $map['a.status'] = ['in', ['free_processing', 'processing', 'complete', 'paypal_reversed', 'paypal_canceled_reversal', 'payment_review']];
        $zeelool_data = $this->nihao->alias('a')->field($field)
            ->join(['sales_flat_shipment_track' => 'b'], 'a.entity_id=b.order_id', 'left')
            ->where($map)->select();
        foreach ($zeelool_data as $key => $v) {
            $list = [];
            $k = 0;
            //下单
            $list[$k]['order_node'] = 0;
            $list[$k]['node_type'] = 0;
            $list[$k]['content'] = 'Your order has been created.';
            $list[$k]['create_time'] = $v['created_at'];
            $list[$k]['site'] = 3;
            $list[$k]['order_id'] = $v['entity_id'];
            $list[$k]['order_number'] = $v['increment_id'];
            $list[$k]['shipment_type'] = '';
            $list[$k]['track_number'] = '';
            $list[$k]['handle_user_id'] = 0;
            $list[$k]['handle_user_name'] = '';
            $data['order_node'] = 0;
            $data['node_type'] = 0;

            if (in_array($v['status'], ['processing', 'complete', 'paypal_reversed', 'paypal_canceled_reversal', 'payment_review'])) {

                //支付
                $list[$k + 1]['order_node'] = 0;
                $list[$k + 1]['node_type'] = 1;
                $list[$k + 1]['content'] = 'Your payment has been successful.';
                $list[$k + 1]['create_time'] = $v['updated_at'];
                $list[$k + 1]['site'] = 3;
                $list[$k + 1]['order_id'] = $v['entity_id'];
                $list[$k + 1]['order_number'] = $v['increment_id'];
                $list[$k + 1]['shipment_type'] = '';
                $list[$k + 1]['track_number'] = '';
                $list[$k + 1]['handle_user_id'] = 0;
                $list[$k + 1]['handle_user_name'] = '';

                $data['order_node'] = 0;
                $data['node_type'] = 1;
            }

            $data['create_time'] = $v['created_at'];
            $data['site'] = 3;
            $data['order_id'] = $v['entity_id'];
            $data['order_number'] = $v['increment_id'];
            $data['update_time'] = $v['created_at'];
            //打标签
            if ($v['custom_print_label_new'] == 1) {
                $list[$k + 2]['order_node'] = 1;
                $list[$k + 2]['node_type'] = 2;
                $list[$k + 2]['content'] = 'Order is under processing';
                $list[$k + 2]['create_time'] = $v['custom_print_label_created_at_new'];
                $list[$k + 2]['site'] = 3;
                $list[$k + 2]['order_id'] = $v['entity_id'];
                $list[$k + 2]['order_number'] = $v['increment_id'];
                $list[$k + 2]['handle_user_id'] = $users[$v['custom_print_label_person_new']];
                $list[$k + 2]['handle_user_name'] = $v['custom_print_label_person_new'];
                $list[$k + 2]['shipment_type'] = '';
                $list[$k + 2]['track_number'] = '';

                $data['order_node'] = 1;
                $data['node_type'] = 2;
                $data['update_time'] = $v['custom_print_label_created_at_new'];
            }

            //判断订单是否为仅镜架
            if ($v['custom_order_prescription_type'] == 1) {
                if ($v['custom_is_match_frame_new'] == 1) {
                    $list[$k + 3]['order_node'] = 2;
                    $list[$k + 3]['node_type'] = 3;
                    $list[$k + 3]['content'] = 'The product(s) is/are ready, waiting for Quality Inspection';
                    $list[$k + 3]['create_time'] = $v['custom_match_frame_created_at_new'];
                    $list[$k + 3]['site'] = 3;
                    $list[$k + 3]['order_id'] = $v['entity_id'];
                    $list[$k + 3]['order_number'] = $v['increment_id'];
                    $list[$k + 3]['handle_user_id'] = $users[$v['custom_match_frame_person_new']];
                    $list[$k + 3]['handle_user_name'] = $v['custom_match_frame_person_new'];
                    $list[$k + 3]['shipment_type'] = '';
                    $list[$k + 3]['track_number'] = '';

                    $data['order_node'] = 2;
                    $data['node_type'] = 3;
                    $data['update_time'] = $v['custom_match_frame_created_at_new'];
                }

                if ($v['custom_is_delivery_new'] == 1) {
                    $list[$k + 4]['order_node'] = 2;
                    $list[$k + 4]['node_type'] = 6;
                    $list[$k + 4]['content'] = 'Quality Inspection completed, preparing to dispatch this mail piece.';
                    $list[$k + 4]['create_time'] = $v['custom_match_delivery_created_at_new'];
                    $list[$k + 4]['site'] = 3;
                    $list[$k + 4]['order_id'] = $v['entity_id'];
                    $list[$k + 4]['order_number'] = $v['increment_id'];
                    $list[$k + 4]['handle_user_id'] = $users[$v['custom_match_delivery_person_new']];
                    $list[$k + 4]['handle_user_name'] = $v['custom_match_delivery_person_new'];
                    $list[$k + 4]['shipment_type'] = '';
                    $list[$k + 4]['track_number'] = '';

                    $data['order_node'] = 2;
                    $data['node_type'] = 6;
                    $data['update_time'] = $v['custom_match_delivery_created_at_new'];
                }

                if ($v['track_number']) {
                    $list[$k + 5]['order_node'] = 2;
                    $list[$k + 5]['node_type'] = 7; //出库
                    $list[$k + 5]['content']  = '';
                    $list[$k + 5]['create_time'] = $v['create_time'];
                    $list[$k + 5]['site'] = 3;
                    $list[$k + 5]['order_id'] = $v['entity_id'];
                    $list[$k + 5]['order_number'] = $v['increment_id'];
                    $list[$k + 5]['shipment_type'] = $v['title'];
                    $list[$k + 5]['track_number'] = $v['track_number'];
                    $list[$k + 5]['handle_user_id'] = 0;
                    $list[$k + 5]['handle_user_name'] = '';

                    $data['order_node'] = 2;
                    $data['node_type'] = 7;
                    $data['update_time'] = $v['create_time'];
                }
            } else {

                if ($v['custom_is_match_frame_new'] == 1) {
                    $list[$k + 3]['order_node'] = 2;
                    $list[$k + 3]['node_type'] = 3; //配镜架
                    $list[$k + 3]['content'] = 'Frame(s) is/are ready, waiting for lenses';
                    $list[$k + 3]['create_time'] = $v['custom_match_frame_created_at_new'];
                    $list[$k + 3]['site'] = 3;
                    $list[$k + 3]['order_id'] = $v['entity_id'];
                    $list[$k + 3]['order_number'] = $v['increment_id'];
                    $list[$k + 3]['handle_user_id'] = $users[$v['custom_match_frame_person_new']];
                    $list[$k + 3]['handle_user_name'] = $v['custom_match_frame_person_new'];
                    $list[$k + 3]['shipment_type'] = '';
                    $list[$k + 3]['track_number'] = '';

                    $data['order_node'] = 2;
                    $data['node_type'] = 3;
                    $data['update_time'] = $v['custom_match_frame_created_at_new'];
                }

                if ($v['custom_is_match_lens_new'] == 1) {
                    $list[$k + 4]['order_node'] = 2;
                    $list[$k + 4]['node_type'] = 4; //配镜片
                    $list[$k + 4]['content'] = 'Lenses production completed, waiting for customizing';
                    $list[$k + 4]['create_time'] = $v['custom_match_lens_created_at_new'];
                    $list[$k + 4]['site'] = 3;
                    $list[$k + 4]['order_id'] = $v['entity_id'];
                    $list[$k + 4]['order_number'] = $v['increment_id'];
                    $list[$k + 4]['handle_user_id'] = $users[$v['custom_match_lens_person_new']];
                    $list[$k + 4]['handle_user_name'] = $v['custom_match_lens_person_new'];
                    $list[$k + 4]['shipment_type'] = '';
                    $list[$k + 4]['track_number'] = '';

                    $data['order_node'] = 2;
                    $data['node_type'] = 4;
                    $data['update_time'] = $v['custom_match_lens_created_at_new'];
                }

                if ($v['custom_is_send_factory_new'] == 1) {
                    $list[$k + 5]['order_node'] = 2;
                    $list[$k + 5]['node_type'] = 5; //加工
                    $list[$k + 5]['content'] = 'Customizing completed, waiting for Quality Inspection';
                    $list[$k + 5]['create_time'] = $v['custom_match_factory_created_at_new'];
                    $list[$k + 5]['site'] = 3;
                    $list[$k + 5]['order_id'] = $v['entity_id'];
                    $list[$k + 5]['order_number'] = $v['increment_id'];
                    $list[$k + 5]['handle_user_id'] = $users[$v['custom_match_factory_person_new']];
                    $list[$k + 5]['handle_user_name'] = $v['custom_match_factory_person_new'];
                    $list[$k + 5]['shipment_type'] = '';
                    $list[$k + 5]['track_number'] = '';

                    $data['order_node'] = 2;
                    $data['node_type'] = 5;
                    $data['update_time'] = $v['custom_match_factory_created_at_new'];
                }


                if ($v['custom_is_delivery_new'] == 1) {
                    $list[$k + 6]['order_node'] = 2;
                    $list[$k + 6]['node_type'] = 6; //质检
                    $list[$k + 6]['content'] = 'Quality Inspection completed, preparing to dispatch this mail piece.';
                    $list[$k + 6]['create_time'] = $v['custom_match_delivery_created_at_new'];
                    $list[$k + 6]['site'] = 3;
                    $list[$k + 6]['order_id'] = $v['entity_id'];
                    $list[$k + 6]['order_number'] = $v['increment_id'];
                    $list[$k + 6]['handle_user_id'] = $users[$v['custom_match_delivery_person_new']];
                    $list[$k + 6]['handle_user_name'] = $v['custom_match_delivery_person_new'];
                    $list[$k + 6]['shipment_type'] = '';
                    $list[$k + 6]['track_number'] = '';

                    $data['order_node'] = 2;
                    $data['node_type'] = 6;
                    $data['update_time'] = $v['custom_match_delivery_created_at_new'];
                }

                if ($v['track_number']) {
                    $list[$k + 7]['order_node'] = 2;
                    $list[$k + 7]['node_type'] = 7; //出库
                    $list[$k + 7]['create_time'] = $v['create_time'];
                    $list[$k + 7]['site'] = 3;
                    $list[$k + 7]['order_id'] = $v['entity_id'];
                    $list[$k + 7]['order_number'] = $v['increment_id'];
                    $list[$k + 7]['shipment_type'] = $v['title'];
                    $list[$k + 7]['track_number'] = $v['track_number'];
                    $list[$k + 7]['handle_user_id'] = 0;
                    $list[$k + 7]['handle_user_name'] = '';
                    $list[$k + 7]['content'] = '';

                    $data['order_node'] = 2;
                    $data['node_type'] = 7;
                    $data['update_time'] = $v['create_time'];
                }
            }
            $data['shipment_type'] = $v['title'];
            $data['track_number'] = $v['track_number'];


            $count = Db::name('order_node')->where('order_id', $v['entity_id'])->count();
            if ($count > 0) {
                Db::name('order_node')->where('order_id', $v['entity_id'])->update($data);
            } else {
                Db::name('order_node')->insert($data);
            }
            $this->ordernodedetail->saveAll($list);
            echo $key . "\n";
        }
        echo 'ok';
    }
    public function update_base_grand_total()
    {
        $this->worklist = new \app\admin\model\saleaftermanage\WorkOrderList;
        $platform = $this->request->get('platform');
        switch ($platform) {
            case 1:
                $model = Db::connect('database.db_zeelool');
                break;
            case 2:
                $model = Db::connect('database.db_voogueme');
                break;
            case 3:
                $model = Db::connect('database.db_nihao');
                break;
            case 4:
                $model = Db::connect('database.db_meeloog');
                break;
            default:
                $model = false;
                break;
        }
        $where['work_platform'] = $platform;
        $where['base_grand_total'] = 0;
        //求出所有没有订单金额的工单
        $result = $this->worklist->where($where)->column('platform_order');

        if(!$result){
            echo 1;
            exit;
        }
        $info = $model->name('sales_flat_order')->where('increment_id','in',$result)->field('increment_id,base_grand_total')->select();
        if(!$info){
            echo 2;
            exit;
        }
        foreach($info as $v){
            $this->worklist->where(['platform_order'=>$v['increment_id']])->update(['base_grand_total'=>$v['base_grand_total']]);
        }

    }

    public function demo()
    {
        $str = 'a:2:{s:15:"info_buyRequest";a:7:{s:7:"product";s:4:"1237";s:8:"form_key";s:16:"L6dCoq3Glj4zVImc";s:3:"qty";i:1;s:7:"options";a:1:{i:1137;s:4:"1489";}s:13:"cart_currency";s:3:"USD";s:7:"tmplens";a:29:{s:19:"frame_regural_price";d:28.949999999999999;s:11:"frame_price";d:28.949999999999999;s:12:"prescription";s:339:"min_pd=54&max_pd=78&progressive_bifocal=62&customer_rx=0&prescription_type=Progressive&pd=&pd_r=30.0&pd_l=30.0&pdcheck=on&od_sph=-0.50&od_cyl=-0.75&od_axis=125&os_add=1.75&os_sph=0.00&os_cyl=-0.75&os_axis=60&od_pv=0.00&od_pv_r=0.00&od_bd_r=&os_pv=0.00&os_pv_r=0.00&os_bd_r=&year=1968&savePrescription=on&save=Prescription1%28Progressive%29";s:11:"lenstype_id";N;s:13:"lenstype_name";N;s:18:"lenstype_data_name";N;s:21:"lenstype_regual_price";d:0;s:14:"lenstype_price";d:0;s:19:"lenstype_base_price";i:0;s:7:"lens_id";N;s:9:"lens_name";N;s:14:"lens_data_name";N;s:10:"lens_index";N;s:17:"lens_regual_price";d:0;s:10:"lens_price";d:0;s:15:"lens_base_price";i:0;s:8:"color_id";s:0:"";s:10:"color_name";s:0:"";s:15:"color_data_name";N;s:18:"color_regual_price";d:0;s:11:"color_price";d:0;s:16:"color_base_price";i:0;s:10:"coating_id";s:9:"coating_2";s:12:"coating_name";N;s:13:"coating_price";d:5;s:18:"coating_base_price";d:5;s:3:"rid";s:1:"0";s:4:"lens";d:5;s:5:"total";d:33.950000000000003;}s:11:"reset_count";b:1;}s:7:"options";a:1:{i:0;a:7:{s:5:"label";s:5:"Color";s:5:"value";s:5:"White";s:11:"print_value";s:5:"White";s:9:"option_id";s:4:"1137";s:11:"option_type";s:9:"drop_down";s:12:"option_value";s:4:"1489";s:11:"custom_view";b:0;}}}';
        $str1 = 'a:2:{s:15:"info_buyRequest";a:6:{s:7:"product";s:4:"3410";s:8:"form_key";s:16:"1xAFB996YzQgwr4k";s:3:"qty";i:1;s:7:"options";a:1:{i:3342;s:4:"4038";}s:13:"cart_currency";s:3:"USD";s:7:"tmplens";a:29:{s:19:"frame_regural_price";d:26.949999999999999;s:11:"frame_price";d:26.949999999999999;s:12:"prescription";s:261:"prescription_type=SingleVision&od_sph=-1.25&od_cyl=0.75&od_axis=90&os_sph=-1.25&os_cyl=1.50&os_axis=90&pdcheck=on&pd_r=30.00&pd_l=31.50&pd=&os_add=0.00&od_add=0.00&prismcheck=&od_pv=0.00&od_bd=&od_pv_r=0.00&od_bd_r=&os_pv=0.00&os_bd=&os_pv_r=0.00&os_bd_r=&save=";s:11:"lenstype_id";s:10:"lenstype_4";s:13:"lenstype_name";s:19:"Blue Light Blocking";s:18:"lenstype_data_name";s:19:"Blue Light Blocking";s:21:"lenstype_regual_price";i:20;s:14:"lenstype_price";d:20;s:19:"lenstype_base_price";d:20;s:7:"lens_id";s:13:"refractive_11";s:9:"lens_name";s:9:"Recommend";s:14:"lens_data_name";s:24:"1.61 Blue Light Blocking";s:10:"lens_index";s:4:"1.61";s:17:"lens_regual_price";i:10;s:10:"lens_price";d:10;s:15:"lens_base_price";d:10;s:8:"color_id";s:0:"";s:10:"color_name";N;s:15:"color_data_name";N;s:18:"color_regual_price";N;s:11:"color_price";i:0;s:16:"color_base_price";N;s:10:"coating_id";s:0:"";s:12:"coating_name";N;s:13:"coating_price";i:0;s:18:"coating_base_price";N;s:3:"rid";N;s:4:"lens";d:30;s:5:"total";d:56.950000000000003;}}s:7:"options";a:1:{i:0;a:7:{s:5:"label";s:5:"Color";s:5:"value";s:5:"Black";s:11:"print_value";s:5:"Black";s:9:"option_id";s:4:"3342";s:11:"option_type";s:9:"drop_down";s:12:"option_value";s:4:"4038";s:11:"custom_view";b:0;}}}';
        $arr = unserialize($str);
        dump($arr);die;
    }
    public function ceshi(){
        $stime = date("Y-m-d 00:00:00");
        $etime = date("Y-m-d 23:59:59");
        $time  = 123;
    }
}
