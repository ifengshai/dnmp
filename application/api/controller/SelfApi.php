<?php

namespace app\api\controller;

use app\common\controller\Api;
use app\admin\model\OrderNode;
use GuzzleHttp\Client;

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
        $res = (new OrderNode())->allowField(true)->save([
            'order_number' => $order_number,
            'order_id' => $order_id,
            'site' => $site,
            'create_time' => $create_time,
            'order_node' => 0,
            'node_type' => 0,
            'update_time' => date('Y-m-d H:i:s'),
        ]);
        if (false !== $res) {
            $this->success('创建成功', [], 200);
        } else {
            $this->error('创建失败', [], 400);
        }
    }

    public function order_delivery()
    {
        dump(1);
        exit;
    }
}
