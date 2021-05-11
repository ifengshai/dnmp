<?php
/**
 * 运营统计--用户复购率分析脚本
 */
namespace app\admin\controller\shell\supply;
use think\console\Command;
use think\console\Input;
use think\console\Output;
use SchGroup\SeventeenTrack\Connectors\TrackingConnector;
use think\Db;

class TrackAsynData extends Command
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
        parent::__construct();
    }

    protected function configure()
    {
        $this->setName('track_asyn_data')
            ->setDescription('track run');
    }

    protected function execute(Input $input, Output $output)
    {
        $this->dealData();  //更新环比数据
        $output->writeln("All is ok");
    }

    public function dealData()
    {
        $trackingConnector = new TrackingConnector($this->apiKey);
        $orderNumber = [
            "430344760",
            "400611602",
            "100251522",
            "660012715",
            "660012701",
            "100251436",
            "660012595",
            "660012583",
            "660012578",
            "600152582",
            "100251160",
            "660012560",
            "100251100",
            "660012555",
            "600152557",
            "430342657",
            "660012520",
            "660012510",
            "600152497",
            "660012486",
            "360002653",
            "130111517",
            "360002650",
            "360002586",
            "660012430",
            "660012417",
            "430342318",
            "660012414",
            "130111477",
            "130111470",
            "130111464",
            "300050636",
            "660012410",
            "430341938",
            "130111376",
            "400608092",
            "430341775",
            "430341750",
            "400607927",
            "600152360",
            "130111292",
            "430341691",
            "660012369",
            "400607667",
            "600152292",
            "130111230",
            "430341540",
            "430341526",
            "100250273",
            "660012338",
            "100250256",
            "100130606",
            "430341450",
            "400606233",
            "130110984",
            "660012281",
            "400606023",
            "660012266",
            "430340702",
            "660012256",
            "130110946",
            "600152039",
            "360002576",
            "130110758",
            "360002569",
            "660012172",
            "100248928",
            "300050450",
            "360002543",
            "430339326",
            "430338876",
            "400602009",
            "100248332",
            "600151492",
            "660011978",
            "660011974",
            "100248014",
            "100247999",
            "660011955",
            "360002491",
            "360002489",
            "430338030",
            "600151184",
            "660011858",
            "100247488",
            "500034158",
            "400578708",
            "130106511"
        ];
        //查询有问题的订单物流数据
        $track = Db::name('order_node')
            ->where(['order_number' => ['in', $orderNumber]])
//            ->where('order_number','430321223')
            ->order('delivery_time desc')
            ->select();

        foreach ($track as $value){
            $carrier = $this->getCarrier(strtolower($value['shipment_type']));

            $trackingConnector->registerMulti(array($value['track_number']));
            $trackInfo = $trackingConnector->getTrackInfo($value['track_number'],$carrier);
            //删除courier表中的数据
            Db::name('order_node_courier')
                ->where('order_number',$value['order_number'])
                ->delete();
            $add['site'] = $value['site'];
            $add['order_id'] = $value['order_id'];
            $add['order_number'] = $value['order_number'];
            $add['shipment_type'] = $value['shipment_type'];
            $add['shipment_data_type'] = $value['shipment_data_type'];
            $add['track_number'] = $value['track_number'];
            $this->total_track_data($trackInfo['track'],$add,$value['id']);
            echo $value['track_number'].'--'.$value['id'].' is ok'."\n";
            usleep(10000);
        }
    }
    public function total_track_data($data, $add,$id)
    {
        //删除detail表中的签收数据
        Db::name('order_node_detail')
            ->where('track_number', $add['track_number'])
            ->where('shipment_type', $add['shipment_type'])
            ->where('order_node',4)
            ->where('node_type',40)
            ->delete();

        $trackdetail = array_reverse($data['z1']);

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
            $contents = Db::name('order_node_courier')
                ->where('track_number', $add['track_number'])
                ->column('content');

            foreach ($trackdetail as $k => $v) {

                if (!in_array($v['z'], $contents)) {
                    $add['create_time'] = $v['a'];
                    $add['content'] = $v['z'];
                    $add['courier_status'] = $data['e'];
                    Db::name('order_node_courier')
                        ->insert($add); //插入物流日志表

                }
                if ($k == 1) {
                    //更新上网
                    $order_node_date = Db::name('order_node_detail')
                        ->where(['track_number' => $add['track_number'], 'shipment_type' => $add['shipment_type']])
                        ->where('order_node',3)
                        ->where('node_type',8)
                        ->find();
                    $update_order_node['order_node'] = 3;
                    $update_order_node['node_type'] = 8;
                    $update_order_node['update_time'] = $v['a'];
                    $update_order_node['signing_time'] = '';
                    Db::name('order_node')
                        ->where('id', $id)
                        ->update($update_order_node); //更新主表状态
                    if ($order_node_date['id']) {
                        $order_node_detail['node_type'] = 8;
                        $order_node_detail['content'] = $this->str1;
                        $order_node_detail['create_time'] = $v['a'];
                        Db::name('order_node_detail')
                            ->where('id',$order_node_date['id'])
                            ->update($order_node_detail); //插入节点字表
                    }else{
                        $order_node_detail['node_type'] = 8;
                        $order_node_detail['content'] = $this->str1;
                        $order_node_detail['create_time'] = $v['a'];
                        Db::name('order_node_detail')
                            ->insert($order_node_detail); //插入节点字表
                    }
                }
                if ($k == 2) {
                    //更新运输
                    $order_node_date = Db::name('order_node_detail')
                        ->where(['track_number' => $add['track_number'], 'shipment_type' => $add['shipment_type']])
                        ->where('order_node',3)
                        ->where('node_type',10)
                        ->find();
                    $update_order_node['order_node'] = 3;
                    $update_order_node['node_type'] = 10;
                    $update_order_node['update_time'] = $v['a'];
                    $update_order_node['signing_time'] = '';
                    Db::name('order_node')
                        ->where('id', $id)
                        ->update($update_order_node); //更新主表状态
                    if ($order_node_date['id']) {
                        $order_node_detail['node_type'] = 10;
                        $order_node_detail['content'] = $this->str3;
                        $order_node_detail['create_time'] = $v['a'];
                        Db::name('order_node_detail')
                            ->where('id', $order_node_date['id'])
                            ->update($order_node_detail); //插入节点字表
                    }else{
                        $order_node_detail['node_type'] = 10;
                        $order_node_detail['content'] = $this->str3;
                        $order_node_detail['create_time'] = $v['a'];
                        Db::name('order_node_detail')
                            ->where('id', $order_node_date['id'])
                            ->insert($order_node_detail); //插入节点字表
                    }
                }

                //结果
                if($all_num - 1 == $k){
                    if ($data['e'] == 30 || $data['e'] == 35 || $data['e'] == 40 || $data['e'] == 50) {
                        $order_node_date = Db::name('order_node')->where(['track_number' => $add['track_number'], 'shipment_type' => $add['shipment_type']])->find();

                        if (($order_node_date['order_node'] == 3 && $order_node_date['node_type'] == 10)||($order_node_date['order_node'] == 3 && $order_node_date['node_type'] == 11)) {
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
                    $order_node_date = Db::name('order_node')->where(['track_number' => $add['track_number'], 'shipment_type' => $add['shipment_type']])->find();
                    $update_order_node = [];
                    $update_order_node['update_time'] = $v['a'];
                    $update_order_node['shipment_last_msg'] =  $v['z'];
                    Db::name('order_node')->where('id', $order_node_date['id'])->update($update_order_node); //更新主表状态
                }
            }
        }
    }
    /**
     * 获取快递号
     * @param $title
     * @return mixed|string
     */
    protected function getCarrier($title)
    {
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
        return $carrier[$title];
    }
}