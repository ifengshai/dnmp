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
            '430295866',
            '130103917',
            '130104359',
            '430318436',
            '130104197',
            '430318037',
            '430318090',
            '430321223',
            '430321051',
            '430317783',
            '430321443',
            '130104420',
            '430321750',
            '430313456',
            '430321935',
            '430321778',
            '430321528',
            '430321907',
            '430315154',
            '430322288',
            '430323091',
            '430321567',
            '130102539',
            '430321804',
            '130099003',
            '430319563',
            '430318764',
            '430321595',
            '430317847',
            '430322850',
            '130105283',
            '430322034',
            '430319252',
            '130104449',
            '130104208',
            '430322093',
            '130104390',
            '130104483',
            '130104317',
            '430319000',
            '430319852',
            '430319582',
            '430319761',
            '130104257',
            '430317779',
            '430322982',
            '430321719',
            '430320884',
            '130105321',
            '430322529',
            '430322780',
            '430322285',
            '430319864',
            '430318859',
            '430322441',
            '430322987',
            '130104102',
            '430322323',
            '430322211',
            '430324096',
            '430318500',
            '430322294',
            '430317829',
            '430322253',
            '430321814',
            '430319484',
            '430322375',
            '430321819',
            '130105400',
            '430323531',
            '130104474',
            '430319142',
            '430319292',
            '130104533',
            '130104512',
            '130104702',
            '430319072',
            '430319102',
            '130104656',
            '430319216',
            '430319225',
            '130104583',
            '130105512',
            '130105363',
            '430319293',
            '430321741',
            '430322888',
            '430319311',
            '430322617',
            '130105402',
            '430323017',
            '430323120',
            '430322972',
            '430323191',
            '430318812',
            '430318542',
            '430320006',
            '430323350',
            '430319985',
            '130104828',
            '430319837',
            '130104862',
            '130104851',
            '430321203',
            '430323568',
            '430324494',
            '130105525',
            '430323086',
            '430321372',
            '130104846',
            '430321710',
            '430319696',
            '430322823',
            '430320502',
            '130103300',
            '430323006',
            '430323156',
            '130105996',
            '130076014',
            '130105698',
            '430319977',
            '430323266',
            '430323206',
            '430323634',
            '430323650',
            '130104626',
            '430323508',
            '430319819',
            '430319597',
            '430323408',
            '430323139',
            '430319932',
            '430318746',
            '430320323',
            '130104872',
            '130106055',
            '430323609',
            '430323670',
            '430319895',
            '430324252',
            '430319943',
            '430323742',
            '430320153',
            '130103848',
            '430320528',
            '430324342',
            '430324193',
            '130104175',
            '430320447',
            '430320555',
            '130105111',
            '430320297',
            '430321070',
            '430054191',
            '430323763',
            '430324077',
            '430324423',
            '430321859',
            '130105556',
            '430322770',
            '430322787',
            '430322760',
            '130106002',
            '130105993',
            '130105282',
            '130099982',
            '130105905',
            '130104922',
            '430321915',
            '430322648',
            '130105787',
            '430322629',
            '130105192',
            '430321245',
            '430303138',
            '430325070',
            '130105896',
            '430324364',
            '430306683',
            '430320432',
            '430320978',
            '130105277',
            '130106339',
            '430323945',
            '130105962',
            '430321594',
            '430323789',
            '430324022',
            '430322729',
            '430325409',
            '430324430',
            '430320739',
            '430322328',
            '430324507',
            '430322413',
            '430324644',
            '430321672',
            '130105438',
            '430322305',
            '430316660',
            '430325014',
            '430326176',
            '430321744',
            '430325291',
            '130105520',
            '430324962',
            '430325422',
            '430325394',
            '430324982',
            '130106486',
            '130105516',
            '430326814',
            '430322246',
            '130106614',
            '130106593',
            '430322058',
            '430319584',
            '430324305',
            '430324550',
            '430324608',
            '130105175',
            '430325928',
            '430325036',
            '430325254',
            '430318647',
            '130105459',
            '430322610',
            '430321486',
            '430324361',
            '130106160',
            '430324927',
            '430325440',
            '430325441',
            '130106362',
            '430325559',
            '430324869',
            '430325702',
            '430322748',
            '130105610',
            '430325982',
            '430325790',
            '130106626',
            '130106504',
            '130106716',
            '430327086',
            '430318061',
            '430318269',
            '430325894',
            '430323342',
            '130105702',
            '130105560',
            '430322084',
            '430327132',
            '430327217',
            '430323374',
            '130105824',
            '430326666',
            '430326657',
            '430327632',
            '430326076',
            '430326859',
            '430327752',
            '430323126',
            '430323716',
            '430322688',
            '130106638',
            '430326168',
            '130106336',
            '130106073',
            '430324609',
            '430327062',
            '430314305',
            '430326110',
            '130106347',
            '430326243',
            '430326201',
            '430326254',
            '430322799',
            '130105596',
            '430324657',
            '430323464',
            '430323784',
            '430324648',
            '130105710',
            '130105802',
            '430323549',
            '130105655',
            '130105727',
            '430327067',
            '430327070',
            '430327844',
            '430318916',
            '430323099',
            '130105799',
            '130106023',
            '430323270',
            '430327131',
            '430326805',
            '430324367',
            '430328365',
            '430323123',
            '430326356',
            '430323762',
            '430328414',
            '430324092',
            '430324672',
            '430324556',
            '430324086',
            '130105870',
            '130105773',
            '130105742',
            '130106641',
            '430323310',
            '130106860',
            '430323373',
            '430326458',
            '430323200',
            '430324181',
            '430326822',
            '430326328',
            '130106887',
            '130106971',
            '430326504',
            '130106747',
            '130106040',
            '430328227',
            '430327166',
            '130106020',
            '430323989',
            '430326371',
            '430324101',
            '430318685',
            '130105922',
            '130107241',
            '130107230',
            '430326948',
            '430327196',
            '130105885',
            '430327361',
            '130106737',
            '430327071',
            '130105939',
            '430318931',
            '430327598',
            '430327622',
            '130106278',
            '130106167',
            '430325300',
            '430324517',
            '430327992',
            '430328109',
            '430327760',
            '430327757',
            '130106296',
            '130106426',
            '430325537',
            '430328114',
            '430324136',
            '430324446',
            '130107261',
            '430327596',
            '430327749',
            '430328059',
            '430327447',
            '430327899',
            '430327273',
            '430327655',
            '430327339',
            '430327534',
            '430327781',
            '430327479',
            '430328057',
            '430323971',
            '130106369',
            '430327399',
            '430328055',
            '130107195',
            '430328461',
            '430323863',
            '430327414',
            '130106410',
            '430323275',
            '430325499',
            '430323984',
            '430324747',
            '430325278',
            '430328151',
            '130107474',
            '130106085',
            '430325289',
            '430328524',
            '130106251',
            '430325205',
            '430306987',
            '430328549',
            '130106619',
            '130107255',
            '130106741',
            '430320985',
            '430325626',
            '430325648',
            '430326138',
            '430326258',
            '130106628',
            '430326399',
            '430328946',
            '130106266',
            '430329903',
            '430328272',
            '430329032',
            '130106260',
            '430324807',
            '430328255',
            '430329175',
            '430328621',
            '430327819',
            '430327617',
            '130104980',
            '130107507',
            '130106236',
            '430328339',
            '430325361',
            '430328504',
            '130107204',
            '130106399',
            '130107243',
            '130106194',
            '430328779',
            '130107428',
            '430326190',
            '430328770',
            '130107633',
            '430324801',
            '430325194',
            '130107235',
            '430328158',
            '130106334',
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