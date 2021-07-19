<?php

namespace app\api\controller;

use app\admin\model\OrderNode;
use app\admin\model\OrderNodeCourierThird;
use app\common\controller\Api;
use SchGroup\SeventeenTrack\Connectors\TrackingConnector;
use think\Db;
use app\admin\controller\elasticsearch\AsyncEs;
use think\Queue;


/**
 * 会员接口
 */
class ThirdApi extends Api
{
    protected $noNeedLogin = '*';
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
        $this->asyncEs = new AsyncEs();
        parent::_initialize();
    }

    /*
     * 17track物流查询webhook访问方法
     * */
    public function track_return()
    {
        $track_info = file_get_contents("php://input");
        tp_log('物流信息:' . $track_info, '17track');

        $track_info = '{"event":"TRACKING_UPDATED","sign":"d5dc66bedc1973e9accb94b527386364a808c82253a5738db098c3d31270cb90","data":{"number":"9214490221582737950192","track":{"b":2105,"c":0,"e":10,"f":-1,"w1":21051,"w2":0,"is1":1,"is2":0,"hs":-2128872419,"z0":{"a":"2021-07-17 14:35","b":null,"c":"","d":"ANN ARBOR, MI 48104","z":"Shipping Label Created, USPS Awaiting Item -> A shipping label has been prepared for your item at 2:35 pm on July 17, 2021 in ANN ARBOR, MI 48104. This does not indicate receipt by the USPS or the actual mailing date."},"ln9":null,"ln1":"en","ln2":null,"ygt9":0,"ygt1":0,"ygt2":0,"ylt9":"2079-01-01 00:00:00","ylt1":"2021-07-17 15:07:13","ylt2":"2079-01-01 00:00:00","z9":[],"z1":[{"a":"2021-07-17 14:35","b":null,"c":"","d":"ANN ARBOR, MI 48104","z":"Shipping Label Created, USPS Awaiting Item -> A shipping label has been prepared for your item at 2:35 pm on July 17, 2021 in ANN ARBOR, MI 48104. This does not indicate receipt by the USPS or the actual mailing date."}],"z2":[],"yt":"","zex":{"trN":"","trC":0,"psex":0,"dt":1626532500000,"dtS":1626532500000,"dtP":0,"dtD":0,"dtL":1626532500000}}}}';

        // 1.当前任务将由哪个类来负责处理。
        //   当轮到该任务时，系统将生成一个该类的实例，并调用其 fire 方法
        $jobHandlerClassName = 'app\admin\jobs\Logistics';
        // 2.当前任务归属的队列名称，如果为新队列，会自动创建
        $jobQueueName = "logisticsJobQueue";
        // 3.当前任务所需的业务数据 . 不能为 resource 类型，其他类型最终将转化为json形式的字符串
        //   ( jobData 为对象时，需要在先在此处手动序列化，否则只存储其public属性的键值对)
        $jobData = json_decode($track_info, true);

        // 4.将该任务推送到消息队列，等待对应的消费者去执行
        $isPushed = Queue::push($jobHandlerClassName, $jobData, $jobQueueName);
        // database 驱动时，返回值为 1|false  ;   redis 驱动时，返回值为 随机字符串|false
        if ($isPushed !== false) {
            $this->success('推送成功');
        } else {
            $this->error('推送失败');
        }

    }


    /**
     * 临时批量注册--lixiang
     */
    public function get_track()
    {
        $order_shipment = Db::name('order_node')->where(['delivery_time' => ['>', '2021-07-13'], 'order_node' => ['>', 2]])->select();
        $order_shipment = collection($order_shipment)->toArray();
        $trackingConnector = new TrackingConnector($this->apiKey);

        $shipment_reg = [];
        foreach ($order_shipment as $k => $v) {
            $title = $v['shipment_type'];
            $carrier = $this->getCarrier($title);
            $shipment_reg[$k]['number'] = $v['track_number'];
            $shipment_reg[$k]['carrier'] = $carrier['carrierId'];
            $shipment_reg[$k]['order_id'] = $v['order_id'];
            $shipment_reg[$k]['site'] = $v['site'];
            $shipment_reg[$k]['order_number'] = $v['order_number'];
            $shipment_reg[$k]['shipment_data_type'] = $v['shipment_data_type'];
            $shipment_reg[$k]['shipment_type'] = $v['shipment_type'];
        }

        $order_group = array_chunk($shipment_reg, 40);
        foreach ($order_group as $k => $v) {
            $trackInfo = $trackingConnector->getTrackInfoMulti($v);
            if (!$trackInfo['data']) {
                return false;
            }
            foreach ($trackInfo['data']['accepted'] as $val) {
                $add = [];
                $order_node_date = Db::name('order_node')
                    ->where(['track_number' => $val['number']])
                    ->find();
                $add['site'] = $order_node_date['site'];
                $add['order_id'] = $order_node_date['order_id'];
                $add['order_number'] = $order_node_date['order_number'];
                $add['shipment_type'] = $order_node_date['shipment_type'];
                $add['shipment_data_type'] = $order_node_date['shipment_data_type'];
                $add['track_number'] = $val['number'];
                $this->total_track_data($val['track'], $add);
            }
            usleep(100000);
            echo $k . "ok \n";
        }
        echo "all ok \n";
    }


    /**
     * @param $data
     * @param $add
     * order_node总物流total_track_data()
     *
     * @author wgj
     * @Date   2020/10/21 14:48
     */
    public function total_track_data($data, $add)
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
                        $order_node_detail_count = Db::name('order_node_detail')
                            ->where(['track_number' => $add['track_number'], 'shipment_type' => $add['shipment_type'], 'order_node' => 3, 'node_type' => 8])
                            ->count();

                        $update_order_node['order_node'] = 3;
                        $update_order_node['node_type'] = 8;
                        $update_order_node['update_time'] = $v['a'];
                        Db::name('order_node')->where('id', $order_node_date['id'])->update($update_order_node); //更新主表状态
                        $arr = [
                            'id'         => $order_node_date['id'],
                            'order_node' => 3,
                            'node_type'  => 8,
                        ];
                        $this->asyncEs->updateEsById('mojing_track', $arr);

                        $order_node_detail['node_type'] = 8;
                        $order_node_detail['content'] = $this->str1;
                        $order_node_detail['create_time'] = $v['a'];
                        if ($order_node_detail_count < 1) {
                            Db::name('order_node_detail')->insert($order_node_detail); //插入节点字表
                        }

                    }
                }
                if ($k == 2) {
                    //更新运输
                    $order_node_date = Db::name('order_node')->where(['track_number' => $add['track_number'], 'shipment_type' => $add['shipment_type']])->find();
                    if ($order_node_date['order_node'] == 3 && $order_node_date['node_type'] == 8) {
                        $order_node_detail_count = Db::name('order_node_detail')
                            ->where(['track_number' => $add['track_number'], 'shipment_type' => $add['shipment_type'], 'order_node' => 3, 'node_type' => 10])
                            ->count();
                        $update_order_node['order_node'] = 3;
                        $update_order_node['node_type'] = 10;
                        $update_order_node['update_time'] = $v['a'];
                        Db::name('order_node')->where('id', $order_node_date['id'])->update($update_order_node); //更新主表状态
                        $arr = [
                            'id'         => $order_node_date['id'],
                            'order_node' => 3,
                            'node_type'  => 10,
                        ];
                        $this->asyncEs->updateEsById('mojing_track', $arr);

                        $order_node_detail['node_type'] = 10;
                        $order_node_detail['content'] = $this->str3;
                        $order_node_detail['create_time'] = $v['a'];
                        if ($order_node_detail_count < 1) {
                            Db::name('order_node_detail')->insert($order_node_detail); //插入节点字表
                        }
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
                                $delivery_error_flag = strtotime($v['a']) < strtotime($order_node_date['delivery_time']) + 172800 ? 1 : 0;
                                $arr['delivery_error_flag'] = $delivery_error_flag;
                                $arr['delievered_days'] = (strtotime($v['a']) - strtotime($order_node_date['delivery_time'])) / 86400;
                                $arr['wait_time'] = abs(strtotime($v['a']) - strtotime($order_node_date['delivery_time']));
                            }
                            Db::name('order_node')->where('id', $order_node_date['id'])->update($update_order_node); //更新主表状态
                            //更新es
                            $arr['id'] = $order_node_date['id'];
                            $arr['order_node'] = 4;
                            $arr['node_type'] = $data['e'];
                            $this->asyncEs->updateEsById('mojing_track', $arr);

                            $order_node_detail_count = Db::name('order_node_detail')
                                ->where(['track_number' => $add['track_number'], 'shipment_type' => $add['shipment_type'], 'order_node' => 4, 'node_type' =>  $data['e']])
                                ->count();

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
                            if ($order_node_detail_count < 1) {
                                Db::name('order_node_detail')->insert($order_node_detail); //插入节点字表
                            }
                        }
                        if ($order_node_date['order_node'] == 4 && $order_node_date['node_type'] != 40) {
                            $update_order_node['order_node'] = 4;
                            $update_order_node['node_type'] = $data['e'];
                            $update_order_node['update_time'] = $v['a'];
                            if ($data['e'] == 40) {
                                $update_order_node['signing_time'] = $v['a']; //更新签收时间
                                //更新es
                                $arr['signing_time'] = strtotime($v['a']);
                                $delivery_error_flag = strtotime($v['a']) < strtotime($order_node_date['delivery_time']) + 172800 ? 1 : 0;
                                $arr['delivery_error_flag'] = $delivery_error_flag;
                                $arr['delievered_days'] = (strtotime($v['a']) - strtotime($order_node_date['delivery_time'])) / 86400;
                                $arr['wait_time'] = abs(strtotime($v['a']) - strtotime($order_node_date['delivery_time']));
                            }
                            Db::name('order_node')->where('id', $order_node_date['id'])->update($update_order_node); //更新主表状态
                            //更新es
                            $arr['id'] = $order_node_date['id'];
                            $arr['order_node'] = 4;
                            $arr['node_type'] = $data['e'];
                            $this->asyncEs->updateEsById('mojing_track', $arr);

                            $order_node_detail_count = Db::name('order_node_detail')
                                ->where(['track_number' => $add['track_number'], 'shipment_type' => $add['shipment_type'], 'order_node' => 4, 'node_type' =>  $data['e']])
                                ->count();

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
                            if ($order_node_detail_count < 1) {
                                Db::name('order_node_detail')->insert($order_node_detail); //插入节点字表
                            }
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
                    $this->asyncEs->updateEsById('mojing_track', $arr);

                }
            }
        }
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


    /**
     * 回调测试
     * @author wangpenglei
     * @date   2021/6/10 14:04
     */
    public function callback(): \think\response\Json
    {
        $order_number = input('order_number');
        if (!$order_number) {
            return json(['result' => false, 'returnCode' => 302, 'message' => '参数丢失']);
        }
        $params = $this->request->post('param');
        $params = htmlspecialchars_decode($params);
        file_put_contents("/var/www/mojing/public/uploads/kuaidi100_error.log", $params, FILE_APPEND);
        if (!$params) {
            return json(['result' => false, 'returnCode' => 304, 'message' => '未接收到数据']);
        }
        $params = json_decode($params, true);
        //根据单号查询
        $orderNode = new OrderNode();
        $orderList = $orderNode->where(['order_number' => $order_number, 'site' => 13])->find();
        if (!$orderList) {
            $paths = "/var/www/mojing/public/uploads/kuaidi100_error.log";
            $path_txt = date('Y-m-d H:i:s', time()) . '单号：' . $order_number . "\n" . '内容:' . $params . "\n";
            file_put_contents($paths, $path_txt, FILE_APPEND);
        }

        $orderNodeCourierThird = new OrderNodeCourierThird();
        $state = $params['lastResult']['state'];
        foreach ($params['lastResult']['data'] as $k => $v) {
            $count = $orderNodeCourierThird->where(['content' => $v['context'], 'order_number' => $order_number])->count();
            if ($count > 0) {
                continue;
            }
            $data = [];
            $data['create_time'] = $v['time'];
            $data['content'] = $v['context'];
            $data['courier_status'] = $v['status'] ?: '';
            $data['site'] = 13;
            $data['order_id'] = $orderList['order_id'];
            $data['order_number'] = $orderList['order_number'];
            $data['shipment_type'] = $orderList['shipment_type'];
            $data['shipment_data_type'] = $orderList['shipment_data_type'];
            $data['track_number'] = $orderList['track_number'];
            $orderNodeCourierThird->insert($data); //插入物流日志表
            $order_node_date = $orderNode->where(['order_number' => $orderList['order_number'], 'site' => 13])->find();
            if ($state == 3) {
                //更新签收
                if (($order_node_date['order_node'] == 3 && $order_node_date['node_type'] == 10) || ($order_node_date['order_node'] == 2 && $order_node_date['node_type'] == 7)) {
                    $update_order_node['order_node'] = 4;
                    $update_order_node['node_type'] = 40;
                    $update_order_node['update_time'] = $v['time'];
                    $update_order_node['signing_time'] = $v['time'];
                    Db::name('order_node')->where('id', $order_node_date['id'])->update($update_order_node); //更新主表状态

                    $order_node_detail['order_node'] = 4;
                    $order_node_detail['node_type'] = 40;
                    $order_node_detail['content'] = $v['context'];
                    $order_node_detail['create_time'] = $v['time'];
                    $order_node_detail['order_id'] = $orderList['order_id'];
                    $order_node_detail['order_number'] = $orderList['order_number'];
                    $order_node_detail['shipment_type'] = $orderList['shipment_type'];
                    $order_node_detail['shipment_data_type'] = $orderList['shipment_data_type'];
                    $order_node_detail['track_number'] = $orderList['track_number'];
                    Db::name('order_node_detail')->insert($order_node_detail); //插入节点字表
                }
            } else {
                //更新运输
                if ($order_node_date['order_node'] == 2 && $order_node_date['node_type'] == 7) {
                    $update_order_node['order_node'] = 3;
                    $update_order_node['node_type'] = 10;
                    $update_order_node['update_time'] = $v['time'];
                    Db::name('order_node')->where('id', $order_node_date['id'])->update($update_order_node); //更新主表状态

                    $order_node_detail['order_node'] = 3;
                    $order_node_detail['node_type'] = 10;
                    $order_node_detail['content'] = $v['context'];
                    $order_node_detail['create_time'] = $v['time'];
                    $order_node_detail['order_id'] = $orderList['order_id'];
                    $order_node_detail['order_number'] = $orderList['order_number'];
                    $order_node_detail['shipment_type'] = $orderList['shipment_type'];
                    $order_node_detail['shipment_data_type'] = $orderList['shipment_data_type'];
                    $order_node_detail['track_number'] = $orderList['track_number'];
                    Db::name('order_node_detail')->insert($order_node_detail); //插入节点字表
                }
            }
        }

        return json(['result' => true, 'returnCode' => 200, 'message' => '接收成功']);

    }

}
