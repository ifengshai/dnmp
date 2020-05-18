<?php

namespace app\api\controller;

use app\common\controller\Api;
use app\admin\model\OrderNode;
use app\admin\model\OrderNodeDetail;
use GuzzleHttp\Client;
use think\Db;

/**
 * 系统接口
 */
class SelfApi extends Api
{
    protected $noNeedLogin = '*';

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
            ->select();


        //查询节点主表记录
        $row = (new OrderNode())->where(['order_number' => $order_number])->find();
        if (!$row) {
            $this->error(__('订单记录不存在'), [], 400);
        }
        $res_node = $row->allowField(true)->save([
            'order_node' => 2,
            'node_type' => 7,
            'update_time' => date('Y-m-d H:i:s'),
        ]);
    }
}
