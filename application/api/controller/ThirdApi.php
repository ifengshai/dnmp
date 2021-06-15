<?php

namespace app\api\controller;

use app\admin\model\OrderNode;
use app\admin\model\OrderNodeCourier;
use app\admin\model\OrderNodeCourierThird;
use app\admin\model\OrderNodeDetail;
use app\common\controller\Api;
use think\Db;
use app\admin\controller\elasticsearch\AsyncEs;


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
        $track_arr = json_decode($track_info, true);
        $verify_sign = $track_arr['event'] . '/' . json_encode($track_arr['data']) . '/' . $this->apiKey;
        $verify_sign = hash("sha256", $verify_sign);
        // if($verify_sign == $track_arr['sign']){
        //妥投给maagento接口
        $paths = ROOT_PATH . "/public/uploads/17track.json";
        $path_txt = date('Y-m-d H:i:s', time()) . '单号：' . $track_arr['data']['number'] . ',内容:' . json_encode($track_arr);
        file_put_contents($paths, $path_txt, FILE_APPEND);
        if ($track_arr['event'] != 'TRACKING_STOPPED') {
            $order_node = Db::name('order_node')->field('site,order_id,order_number,shipment_type,shipment_data_type')->where('track_number', $track_arr['data']['number'])->find();
            if ($track_arr['data']['track']['e'] == 40) {
                //更新加工表中订单妥投状态
                $process = new \app\admin\model\order\order\NewOrderProcess;
                $process->where('increment_id', $order_node['order_number'])->update(['is_tracking' => 5]);
                if ($order_node['site'] == 1) {
                    $url = config('url.zeelool_url') . 'magic/order/updateOrderStatus';
                } elseif ($order_node['site'] == 2) {
                    $url = config('url.voogueme_url') . 'magic/order/updateOrderStatus';
                }

                $value['increment_id'] = $order_node['order_number'];
                $curl = curl_init();
                curl_setopt($curl, CURLOPT_URL, $url);
                curl_setopt($curl, CURLOPT_USERAGENT, $_SERVER['HTTP_USER_AGENT']); //在HTTP请求中包含一个"User-Agent: "头的字符串。
                curl_setopt($curl, CURLOPT_HEADER, 0); //启用时会将头文件的信息作为数据流输出。
                curl_setopt($curl, CURLOPT_POST, true); //发送一个常规的Post请求
                curl_setopt($curl, CURLOPT_POSTFIELDS, $value);//Post提交的数据包
                curl_setopt($curl, CURLOPT_FOLLOWLOCATION, 1); //启用时会将服务器服务器返回的"Location: "放在header中递归的返回给服务器，使用CURLOPT_MAXREDIRS可以限定递归返回的数量。
                curl_setopt($curl, CURLOPT_RETURNTRANSFER, true); //文件流形式
                curl_setopt($curl, CURLOPT_TIMEOUT, 20); //设置cURL允许执行的最长秒数。
                $content = json_decode(curl_exec($curl), true);
                curl_close($curl);
            }
            $add['site'] = $order_node['site'];
            $add['order_id'] = $order_node['order_id'];
            $add['order_number'] = $order_node['order_number'];
            $add['shipment_type'] = $order_node['shipment_type'];
            $add['shipment_data_type'] = $order_node['shipment_data_type'];
            $add['track_number'] = $track_arr['data']['number'];

            $this->total_track_data($track_arr['data']['track'], $add);

        }
        // }
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
                        $arr = [
                            'id'         => $order_node_date['id'],
                            'order_node' => 3,
                            'node_type'  => 10,
                        ];
                        $this->asyncEs->updateEsById('mojing_track', $arr);

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
            'cod'       => '100040',
            'tnt'       => '100004',
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
            $data['courier_status'] = $v['status'];
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
                if ($order_node_date['order_node'] == 3 && $order_node_date['node_type'] == 10) {
                    $update_order_node['order_node'] = 4;
                    $update_order_node['node_type'] = 40;
                    $update_order_node['update_time'] = $v['time'];
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
