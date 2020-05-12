<?php

namespace app\admin\controller;

use app\common\controller\Backend;
use app\Common\model\Auth;
use think\Db;
use SchGroup\SeventeenTrack\Connectors\TrackingConnector;

class Test extends Backend
{
    protected $noNeedLogin = ['*'];
    protected $apiKey = 'F26A807B685D794C676FA3CC76567035';

    public function _initialize()
    {
        parent::_initialize();

        $this->newproduct = new \app\admin\model\NewProduct();
        $this->item = new \app\admin\model\itemmanage\Item();
    }

    /**
     * 更新采购负责人
     *
     * @Description
     * @author wpl
     * @since 2020/04/29 15:43:38 
     * @return void
     */
    public function test()
    {
        $order_shipment = Db::connect('database.db_voogueme')
            ->table('sales_flat_shipment_track')
            ->field('entity_id,track_number,title,updated_at')
            ->where('created_at','>=','2020-04-10 00:00:00')
            ->select();

        foreach($order_shipment as $k => $v){
            $title = strtolower(str_replace(' ', '-', $v['title']));
            if($title == 'china-post'){
                $order_shipment[$k]['title'] = 'china-ems';
            }
            $carrier = $this->getCarrier($v['title']);
            $shipment_reg[$k]['number'] =  $v['track_number'];
            $shipment_reg[$k]['carrier'] =  $carrier['carrierId'];
        }

        $order_group = array_chunk($shipment_reg, 40);

        $trackingConnector = new TrackingConnector($this->apiKey);
        foreach ($order_group as $key => $val){
            $aa = $trackingConnector->registerMulti($val);
            sleep(1);
            echo $key."\n";
        }
        dump($order_group[$key]);
        echo 'all is ok'."\n";
    }
    /**
     * 获取快递号
     * @param $title
     * @return mixed|string
     */
    public function getCarrier($title)
    {
        $carrierId = '';
        if(stripos($title,'post') !== false){
            $carrierId = 'chinapost';
            $title = 'China Post';
        }elseif(stripos($title,'ems') !== false){
            $carrierId = 'chinaems';
            $title = 'China Ems';
        }elseif(stripos($title,'dhl') !== false){
            $carrierId = 'dhl';
            $title = 'DHL';
        }elseif(stripos($title,'fede') !== false){
            $carrierId = 'fedex';
            $title = 'Fedex';
        }elseif(stripos($title,'usps') !== false){
            $carrierId = 'usps';
            $title = 'Usps';
        }elseif(stripos($title,'yanwen') !== false){
            $carrierId = 'yanwen';
            $title = 'YANWEN';
        }elseif(stripos($title,'cpc') !== false){
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
        if($carrierId){
            return ['title' => $title,'carrierId' => $carrier[$carrierId]];
        }
        return ['title' => $title,'carrierId' => $carrierId];
    }

}
