<?php

namespace app\api\controller;

use app\common\controller\Api;
use app\admin\model\OrderNode;
use app\admin\model\OrderNodeDetail;
use GuzzleHttp\Client;
use think\Db;
use SchGroup\SeventeenTrack\Connectors\TrackingConnector;


/**
 * 系统接口
 */
class SelfApi extends Api
{
    protected $noNeedLogin = '*';

    protected $apiKey = 'F26A807B685D794C676FA3CC76567035';

    public function _initialize()
    {
        parent::_initialize();
    }

    /**
     * 创建订单节点 订单号 站点 时间
     * @Description
     * @author wpl
     * @since 2020/05/18 14:22:06 
     * @return void
     */
    public function create_order()
    {
        //校验参数
        $order_id = $this->request->request('order_id');
        $order_number = $this->request->request('order_number');
        $site = $this->request->request('site');
        $create_time = $this->request->request('create_time');
        if (!$order_id) {
            $this->error(__('缺少订单id参数'), [], 400);
        }

        if (!$order_number) {
            $this->error(__('缺少订单号参数'), [], 400);
        }

        if (!$site) {
            $this->error(__('缺少站点参数'), [], 400);
        }

        if (!$create_time) {
            $this->error(__('缺少创建时间参数'), [], 400);
        }
        $res_node = (new OrderNode())->allowField(true)->save([
            'order_number' => $order_number,
            'order_id' => $order_id,
            'site' => $site,
            'create_time' => $create_time,
            'order_node' => 0,
            'node_type' => 0,
            'update_time' => date('Y-m-d H:i:s'),
        ]);

        $res_node_detail = (new OrderNodeDetail())->allowField(true)->save([
            'order_number' => $order_number,
            'order_id' => $order_id,
            'content' => 'Your order has been created.',
            'site' => $site,
            'create_time' => $create_time,
            'order_node' => 0,
            'node_type' => 0
        ]);
        if (false !== $res_node && false !== $res_node_detail) {
            $this->success('创建成功', [], 200);
        } else {
            $this->error('创建失败', [], 400);
        }
    }

    /**
     * 发货接口
     *
     * @Description
     * @author wpl
     * @since 2020/05/18 15:44:19 
     * @return void
     */
    public function order_delivery()
    {
        //校验参数
        $order_id = $this->request->request('order_id');
        $order_number = $this->request->request('order_number');
        $site = $this->request->request('site');
        if (!$order_id) {
            $this->error(__('缺少订单id参数'), [], 400);
        }

        if (!$order_number) {
            $this->error(__('缺少订单号参数'), [], 400);
        }

        if (!$site) {
            $this->error(__('缺少站点参数'), [], 400);
        }

        switch ($site) {
            case 1:
                $db = 'database.db_zeelool';
                break;
            case 2:
                $db = 'database.db_voogueme';
                break;
            case 3:
                $db = 'database.db_nihao';
                break;
            case 4:
                $db = 'database.db_weseeoptical';
                break;
            case 5:
                $db = 'database.db_meeloog';
                break;
            default:
                return false;
                break;
        }
        //根据订单id查询运单号
        $order_shipment = Db::connect($db)
            ->table('sales_flat_shipment_track')
            ->field('entity_id,track_number,title')
            ->where('order_id', $order_id)
            ->find();

        //查询节点主表记录
        $row = (new OrderNode())->where(['order_number' => $order_number])->find();
        if (!$row) {
            $this->error(__('订单记录不存在'), [], 400);
        }
        //更新节点主表
        $res_node = $row->allowField(true)->save([
            'order_node' => 2,
            'node_type' => 7,
            'update_time' => date('Y-m-d H:i:s'),
            'shipment_type' => $order_shipment->title,
            'track_number' => $order_shipment->track_number,
        ]);

        //插入节点子表
        $res_node_detail = (new OrderNodeDetail())->allowField(true)->save([
            'order_number' => $order_number,
            'order_id' => $order_id,
            'content' => 'Your order has been created.',
            'site' => $site,
            'create_time' => date('Y-m-d H:i:s'),
            'order_node' => 2,
            'node_type' => 7,
            'shipment_type' => $order_shipment->title,
            'track_number' => $order_shipment->track_number
        ]);

        //请求接口更改物流表状态


        //注册17track
        $title = strtolower(str_replace(' ', '-', $order_shipment->title));
        if ($title == 'china-post') {
            $title = 'china-ems';
        }
        $carrier = $this->getCarrier($title);
        $shipment_reg['number'] =  $order_shipment->track_number;
        $shipment_reg['carrier'] =  $carrier['carrierId'];
        $track = $this->regitster17Track($shipment_reg);
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
                $db = 'database.db_zeelool';
                break;
            case 2:
                $db = 'database.db_voogueme';
                break;
            case 3:
                $db = 'database.db_nihao';
                break;
            case 4:
                $db = 'database.db_weseeoptical';
                break;
            case 5:
                $db = 'database.db_meeloog';
                break;
            default:
                return false;
                break;
        }
       
        $client = new Client(['verify' => false]);
        $client->request('POST', $url, array('form_params' => $params));
    }

    /**
     * 注册17track
     *
     * @Description
     * @author wpl
     * @since 2020/05/18 18:14:12 
     * @param [type] $params
     * @return void
     */
    protected function regitster17Track($params = [])
    {
        $trackingConnector = new TrackingConnector($this->apiKey);
        $track = $trackingConnector->registerMulti($params);
        return $track;
    }
}
