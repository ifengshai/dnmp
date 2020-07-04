<?php

/**
 * 执行时间：每天一次
 */

namespace app\admin\controller\shell;

use app\common\controller\Backend;
use GuzzleHttp\Client;
use think\Db;
use SchGroup\SeventeenTrack\Connectors\TrackingConnector;

class TrackReg extends Backend
{
    protected $noNeedLogin = ['*'];
    protected $apiKey = 'F26A807B685D794C676FA3CC76567035';


    public function _initialize()
    {
        parent::_initialize();
        $this->ordernodedetail = new \app\admin\model\OrderNodeDetail();
    }

    public function site_reg()
    {
        $this->reg_shipment('database.db_zeelool', 1);
        $this->reg_shipment('database.db_voogueme', 2);
        $this->reg_shipment('database.db_nihao', 3);
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
            ->where('a.created_at', '>=', '2020-03-31 00:00:00')
            ->where('a.handle', '=', '0')
            ->group('a.order_id')
            ->select();
        foreach ($order_shipment as $k => $v) {
            $title = strtolower(str_replace(' ', '-', $v['title']));
            //区分usps运营商
            if (strtolower($title) == 'usps') {
                $track_num1 = substr($v['track_number'], 0, 4);
                if ($track_num1 == '9200' || $track_num1 == '9205') {
                    //郭伟峰
                    $shipment_data_type = 'USPS_1';
                } else {
                    $track_num2 = substr($v['track_number'], 0, 4);
                    if ($track_num2 == '9400') {
                        //加诺
                        $shipment_data_type = 'USPS_2';
                    } else {
                        //杜明明
                        $shipment_data_type = 'USPS_3';
                    }
                }
            } else {
                $shipment_data_type = $title;
            }
            $carrier = $this->getCarrier($title);
            $shipment_reg[$k]['number'] =  $v['track_number'];
            $shipment_reg[$k]['carrier'] =  $carrier['carrierId'];
            $shipment_reg[$k]['order_id'] =  $v['order_id'];


            $list[$k]['order_node'] = 2;
            $list[$k]['node_type'] = 7; //出库
            $list[$k]['create_time'] = $v['created_at'];
            $list[$k]['site'] = $site_type;
            $list[$k]['order_id'] = $v['entity_id'];
            $list[$k]['order_number'] = $v['increment_id'];
            $list[$k]['shipment_type'] = $v['title'];
            $list[$k]['shipment_data_type'] = $shipment_data_type;
            $list[$k]['track_number'] = $v['track_number'];
            $list[$k]['content'] = 'Leave warehouse, Waiting for being picked up.';

            $data['order_node'] = 2;
            $data['node_type'] = 7;
            $data['update_time'] = $v['created_at'];
            $data['shipment_type'] = $v['title'];
            $data['shipment_data_type'] = $shipment_data_type;
            $data['track_number'] = $v['track_number'];
            $data['delivery_time'] = $v['created_at'];
            Db::name('order_node')->where(['order_id' => $v['order_id'], 'site' => $site_type])->update($data);
        }
        if ($list) {
            $this->ordernodedetail->saveAll($list);
        }

        $order_group = array_chunk($shipment_reg, 40);

        $trackingConnector = new TrackingConnector($this->apiKey);
        $order_ids = array();
        foreach ($order_group as $key => $val) {
            $aa = $trackingConnector->registerMulti($val);

            //请求接口更改物流表状态
            $order_ids = implode(',', array_column($val, 'order_id'));
            $params['ids'] = $order_ids;
            $params['site'] = $site_type;
            $res = $this->setLogisticsStatus($params);
            if ($res->status !== 200) {
                echo $site_str . '更新失败:' . $order_ids . "\n";
            }
            $order_ids = array();

            usleep(500000);
        }
        echo $site_str . ' is ok' . "\n";
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
     * zendesk10分钟更新前20分钟的数据
     * @return [type] [description]
     */
    public function zeelool_zendesk()
    {
        $this->zendeskUpateData('zeelool',1);
        echo 'all ok';
        exit;
    }
    public function voogueme_zendesk()
    {
        $this->zendeskUpateData('voogueme',2);
        echo 'all ok';
        exit;
    }
    /**
     * zendesk10分钟更新前20分钟的数据方法
     * @return [type] [description]
     */
    public function zendeskUpateData($siteType,$type)
    {
        // file_put_contents('/www/wwwroot/mojing/runtime/log/zendesk.log', 'starttime:' . date('Y-m-d H:i:s') . "\r\n", FILE_APPEND);

        $this->model = new \app\admin\model\zendesk\Zendesk;
        $ticketIds = (new \app\admin\controller\zendesk\Notice(request(), ['type' => $siteType]))->autoAsyncUpdate($siteType);
        //判断是否存在
        $nowTicketsIds = $this->model->where("type", $type)->column('ticket_id');

        //求交集的更新
        $intersects = array_intersect($ticketIds, $nowTicketsIds);
        //求差集新增
        $diffs = array_diff($ticketIds, $nowTicketsIds);
        //更新
        foreach ($intersects as $intersect) {
            (new \app\admin\controller\zendesk\Notice(request(), ['type' => $siteType, 'id' => $intersect]))->update();
            echo $intersect . 'is ok' . "\n";
        }
        //新增
        foreach ($diffs as $diff) {
            (new \app\admin\controller\zendesk\Notice(request(), ['type' => $siteType, 'id' => $diff]))->create();
            echo $diff . 'ok' . "\n";
        }
        echo 'all ok';
        // file_put_contents('/www/wwwroot/mojing/runtime/log/zendesk.log', 'endtime:' . date('Y-m-d H:i:s') . "\r\n", FILE_APPEND);
        exit;
    }
}
