<?php

namespace app\api\controller;

use app\admin\model\order\OrderProcess;
use app\common\controller\Api;
use app\admin\model\OrderNode;
use app\admin\model\OrderNodeDetail;
use app\admin\model\OrderNodeCourier;
use fast\Kuaidi100;
use GuzzleHttp\Client;
use think\Db;
use SchGroup\SeventeenTrack\Connectors\TrackingConnector;
use app\admin\model\StockLog;
use app\admin\model\finance\FinanceCost;
use app\admin\controller\elasticsearch\AsyncEs;
use think\Model;

/**
 * 系统接口
 */
class SelfApi extends Api
{
    protected $noNeedLogin = '*';

    protected $apiKey = 'F26A807B685D794C676FA3CC76567035';

    public function _initialize()
    {
        $this->node = new OrderNode();
        $this->asyncEs = new AsyncEs();
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
        $order_id = $this->request->request('order_id'); //订单id
        $order_number = $this->request->request('order_number'); //订单号
        $site = $this->request->request('site'); //站点
        if (!$order_id) {
            $this->error(__('缺少订单id参数'), [], 400);
        }

        if (!$order_number) {
            $this->error(__('缺少订单号参数'), [], 400);
        }

        if (!$site) {
            $this->error(__('缺少站点参数'), [], 400);
        }

        //判断如果子节点大于等于0时  不插入
        $order_count = (new OrderNode)->where([
            'order_number' => $order_number,
            'order_id'     => $order_id,
            'site'         => $site,
            'node_type'    => ['>=', 0],
        ])->count();
        if ($order_count <= 0) {
            $res_node = $this->node->allowField(true)->save([
                'order_number' => $order_number,
                'order_id'     => $order_id,
                'site'         => $site,
                'create_time'  => date('Y-m-d H:i:s'),
                'order_node'   => 0,
                'node_type'    => 0,
                'update_time'  => date('Y-m-d H:i:s'),
            ]);
            $insertId = $this->node->getLastInsID();
            $arr = [
                'id'                  => $insertId,
                'order_node'          => 0,
                'node_type'           => 0,
                'site'                => $site,
                'order_id'            => $order_id,
                'order_number'        => $order_number,
                'shipment_type'       => '',
                'shipment_data_type'  => '',
                'track_number'        => '',
                'signing_time'        => 0,
                'delivery_time'       => 0,
                'delivery_error_flag' => 0,
                'shipment_last_msg'   => "",
                'delievered_days'     => 0,
                'wait_time'           => 0,
            ];
            $data[] = $this->asyncEs->formatDate($arr, time());
            $this->asyncEs->esService->addMutilToEs('mojing_track', $data);
        }

        $count = (new OrderNodeDetail())->where([
            'order_number' => $order_number,
            'order_id'     => $order_id,
            'site'         => $site,
            'order_node'   => 0,
            'node_type'    => 0,
        ])->count();
        if ($count > 0) {
            $this->error('已存在', [], 400);
        }

        $res_node_detail = (new OrderNodeDetail())->allowField(true)->save([
            'order_number' => $order_number,
            'order_id'     => $order_id,
            'content'      => 'Your order has been created.',
            'site'         => $site,
            'create_time'  => date('Y-m-d H:i:s'),
            'order_node'   => 0,
            'node_type'    => 0,
        ]);
        if (false !== $res_node && false !== $res_node_detail) {
            $this->success('创建成功', [], 200);
        } else {
            $this->error('创建失败', [], 400);
        }
    }

    /**
     * 订单支付成功节点 订单号 站点 时间
     * @Description
     * @author wpl
     * @since 2020/05/18 14:22:06 
     * @return void
     */
    public function order_pay()
    {
        //校验参数
        $order_id = $this->request->request('order_id'); //订单id
        $order_number = $this->request->request('order_number'); //订单号
        $site = $this->request->request('site'); //站点
        $status = $this->request->request('status'); //站点
        if (!$order_id) {
            $this->error(__('缺少订单id参数'));
        }

        if (!$order_number) {
            $this->error(__('缺少订单号参数'));
        }

        if (!$site) {
            $this->error(__('缺少站点参数'));
        }

        if (!$status) {
            $this->error(__('缺少状态参数'));
        }
        if ($status == 'processing') {
            //判断如果子节点大于等于1时  不更新
            $order_count = (new OrderNode)->where([
                'order_number' => $order_number,
                'order_id'     => $order_id,
                'site'         => $site,
                'node_type'    => ['>=', 1],
            ])->count();
            if ($order_count < 0) {
                $res_node = $this->node->save([
                    'order_node'  => 0,
                    'node_type'   => 1,
                    'update_time' => date('Y-m-d H:i:s'),
                ], ['order_id' => $order_id, 'site' => $site]);
                //获取主表id
                $id = $this->node
                    ->where(['order_id' => $order_id, 'site' => $site])
                    ->value('id');
                //更新order_node表中es数据
                $arr = [
                    'id'        => $id,
                    'node_type' => 1,
                ];
                $this->asyncEs->updateEsById('mojing_track', $arr);
            }

            $count = (new OrderNodeDetail())->where([
                'order_number' => $order_number,
                'order_id'     => $order_id,
                'site'         => $site,
                'order_node'   => 0,
                'node_type'    => 1,
            ])->count();
            if ($count > 0) {
                $this->error('已存在');
            }

            $res_node_detail = (new OrderNodeDetail())->allowField(true)->save([
                'order_number' => $order_number,
                'order_id'     => $order_id,
                'content'      => 'Your payment has been successful.',
                'site'         => $site,
                'create_time'  => date('Y-m-d H:i:s'),
                'order_node'   => 0,
                'node_type'    => 1,
            ]);
            if (false !== $res_node && false !== $res_node_detail) {
                $this->success('创建成功', [], 200);
            } else {
                $this->error('创建失败');
            }
        }
        $this->success('创建成功', [], 200);

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
        $order_id = $this->request->request('order_id'); //订单id
        $order_number = $this->request->request('order_number'); //订单号
        $site = $this->request->request('site'); //站点
        $title = $this->request->request('title'); //运营商
        $shipment_data_type = $this->request->request('shipment_data_type'); //渠道名称
        $track_number = $this->request->request('track_number'); //快递单号
        if (!$order_id) {
            $this->error(__('缺少订单id参数'), [], 400);
        }

        if (!$order_number) {
            $this->error(__('缺少订单号参数'), [], 400);
        }

        if (!$site) {
            $this->error(__('缺少站点参数'), [], 400);
        }

        if (!$title) {
            $this->error(__('缺少运营商参数'), [], 400);
        }

        if (!$track_number) {
            $this->error(__('缺少快递单号参数'), [], 400);
        }

        if (!$shipment_data_type) {
            $this->error(__('缺少渠道名称'), [], 400);
        }

        //查询节点主表记录
        $row = (new OrderNode())->where(['order_number' => $order_number])->find();
        if (!$row) {
            $this->error(__('订单记录不存在'), [], 400);
        }

        //如果已发货 则不再更新发货时间
        //更新节点主表
        $row->allowField(true)->save([
            'order_node'         => 2,
            'node_type'          => 7,
            'update_time'        => date('Y-m-d H:i:s'),
            'shipment_type'      => $title,
            'shipment_data_type' => $shipment_data_type,
            'track_number'       => $track_number,
            'delivery_time'      => date('Y-m-d H:i:s'),
        ]);

        //更新order_node表中es数据
        $arr = [
            'id'                 => $row['id'],
            'order_node'         => 2,
            'node_type'          => 7,
            'shipment_type'      => $title,
            'shipment_data_type' => $shipment_data_type,
            'track_number'       => $track_number,
            'delivery_time'      => time(),
        ];
        $this->asyncEs->updateEsById('mojing_track', $arr);


        if ($site == 13) {
            //插入节点子表
            (new OrderNodeDetail())->allowField(true)->save([
                'order_number'       => $order_number,
                'order_id'           => $order_id,
                'content'            => '订单离开仓库, 等待揽收',
                'site'               => $site,
                'create_time'        => date('Y-m-d H:i:s'),
                'order_node'         => 2,
                'node_type'          => 7,
                'shipment_type'      => $title,
                'shipment_data_type' => $shipment_data_type,
                'track_number'       => $track_number,
            ]);
        } else {
            //插入节点子表
            (new OrderNodeDetail())->allowField(true)->save([
                'order_number'       => $order_number,
                'order_id'           => $order_id,
                'content'            => 'Order leave warehouse, waiting for being picked up.',
                'site'               => $site,
                'create_time'        => date('Y-m-d H:i:s'),
                'order_node'         => 2,
                'node_type'          => 7,
                'shipment_type'      => $title,
                'shipment_data_type' => $shipment_data_type,
                'track_number'       => $track_number,
            ]);
        }

        if ($site == 13) {
            $carrierId = $this->getThirdCarrier($shipment_data_type);
            //订阅快递100推送
            Kuaidi100::setThirdPoll($carrierId, $track_number, $order_number);

            $this->success('提交成功', [], 200);
        }


        //注册17track
        $title = strtolower(str_replace(' ', '-', $title));
        $carrier = $this->getCarrier($title);
        $shipment_reg[0]['number'] = $track_number;
        $shipment_reg[0]['carrier'] = $carrier['carrierId'];
        $track = $this->regitster17Track($shipment_reg);

        if (count($track['data']['rejected']) > 0) {
            $this->error('物流接口注册失败！！', [], $track['data']['rejected']['error']['code']);
        }
        $this->success('提交成功', [], 200);
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
     * 第三方物流匹配
     *
     * @param $shipment_data_type
     *
     * @return string
     * @author wangpenglei
     * @date   2021/6/10 14:17
     */
    protected function getThirdCarrier($shipment_data_type): string
    {
        $carrierId = '';
        switch ($shipment_data_type) {
            case '圆通速递':
                $carrierId = 'yuantong';
                break;
            case '韵达快递':
                $carrierId = 'yunda';
                break;
            case '中通快递':
                $carrierId = 'zhongtong';
                break;
            case '顺丰速运':
                $carrierId = 'shunfeng';
                break;
            case '申通快递':
                $carrierId = 'shentong';
                break;
            default:
                break;

        }

        return $carrierId;
    }

    /**
     * 注册17track
     *
     * @Description
     * @author wpl
     * @since 2020/05/18 18:14:12 
     *
     * @param     [type] $params
     *
     * @return void
     */
    protected function regitster17Track($params = [])
    {
        $trackingConnector = new TrackingConnector($this->apiKey);
        $track = $trackingConnector->registerMulti($params);

        return $track;
    }

    /**
     * 获取订单加工/物流节点流程 -- 新
     *
     * @Description
     * @author mjj
     * @since 2020/06/29 16:16:43 
     * @return void
     */
    public function query_order_node_track_processing()
    {
        $order_number = $this->request->request('order_number'); //订单号
        $other_order_number = $this->request->request('other_order_number/a'); //其他订单号
        $site = $this->request->request('site'); //站点

        $order_node1 = Db::name('order_node_detail')
            ->where('order_number', $order_number)
            ->where('site', $site)
            ->where('node_type', 'in', '1,2,3,4,5,6,7,13')
            ->order('create_time desc')
            ->select();
        $order_node2 = Db::name('order_node_courier')
            ->where('order_number', $order_number)
            ->where('site', $site)
            ->order('create_time desc')
            ->select();
        $order_data['order_data'] = array_merge($order_node2, $order_node1);
        if ($other_order_number) {

            foreach ($other_order_number as $val) {

                $other_order_node1 = Db::name('order_node_detail')
                    ->where('order_number', $val)
                    ->where('site', $site)
                    ->where('node_type', 'in', '1,2,3,4,5,6,7,13')
                    ->order('create_time desc')
                    ->select();
                $other_order_node2 = Db::name('order_node_courier')
                    ->where('order_number', $val)
                    ->where('site', $site)
                    ->order('create_time desc')
                    ->select();
                $order_data['other_order_data'][$val] = array_merge($other_order_node2, $other_order_node1);
            }
        }
        $this->success('成功', $order_data, 200);
    }

    /**
     * 获取订单物流节点流程 -- 新
     *
     * @Description
     * @author mjj
     * @since 2020/06/29 16:16:43 
     * @return void
     */
    public function query_order_node_track()
    {
        $order_number = $this->request->request('order_number'); //订单号
        $other_order_number = $this->request->request('other_order_number/a'); //其他订单号
        $site = $this->request->request('site'); //站点

        $order_data['order_data'] = Db::name('order_node_courier')
            ->where('order_number', $order_number)
            ->where('site', $site)
            ->order('create_time desc')
            ->select();
        if ($other_order_number) {

            foreach ($other_order_number as $val) {
                $order_data['other_order_data'][$val] = Db::name('order_node_courier')
                    ->where('order_number', $val)
                    ->where('site', $site)
                    ->order('create_time desc')
                    ->select();
            }
        }
        $this->success('成功', $order_data, 200);
    }

    /**
     * 获取订单加工节点流程 -- 新
     *
     * @Description
     * @author mjj
     * @since 2020/06/29 16:16:43 
     * @return void
     */
    public function query_order_node_processing()
    {
        $order_number = $this->request->request('order_number'); //订单号
        $other_order_number = $this->request->request('other_order_number/a'); //其他订单号
        $site = $this->request->request('site'); //站点

        $order_data['order_data'] = Db::name('order_node_detail')
            ->where('order_number', $order_number)
            ->where('site', $site)
            ->where('node_type', 'in', '1,2,3,4,5,6,7,13')
            ->order('create_time desc')
            ->select();
        if ($other_order_number) {

            foreach ($other_order_number as $val) {

                $order_data['other_order_data'][$val] = Db::name('order_node_detail')
                    ->where('order_number', $val)
                    ->where('site', $site)
                    ->where('node_type', 'in', '1,2,3,4,5,6,7,13')
                    ->order('create_time desc')
                    ->select();
            }
        }
        $this->success('成功', $order_data, 200);
    }

    /**
     * 获取订单节点流程 -- 旧（暂时不用）
     *
     * @Description
     * @author Lx
     * @since 2020/05/28 13:50:49 
     */
    public function query_order_node()
    {
        //校验参数
        $order_number = $this->request->request('order_number'); //订单号
        $other_order_number = $this->request->request('other_order_number/a'); //其他订单号
        $site = $this->request->request('site'); //站点
        $order_node = $this->request->request('order_node'); //订单节点

        if (!$order_number) {
            $this->error(__('缺少订单号参数'), [], 400);
        }

        if (!$site) {
            $this->error(__('缺少站点参数'), [], 400);
        }

        if (!$order_node) {
            $this->error(__('缺少节点参数'), [], 400);
        }

        if ($order_number) {
            $where['order_number'] = $order_number;
        }
        $where['site'] = $site;
        if ($order_node != 5) {
            if ($order_node == 3) {
                $where['order_node'] = ['in', ['3', '4']];
            } else {
                $where['order_node'] = $order_node;
            }
        }

        $order_node_data = (new OrderNodeDetail())->where($where)->select();
        $order_data['order_data'] = collection($order_node_data)->toArray();

        if ($other_order_number) {
            $orther_where['site'] = $site;
            if ($order_node != 5) {
                if ($order_node == 3) {
                    $orther_where['order_node'] = ['in', ['3', '4']];
                } else {
                    $orther_where['order_node'] = $order_node;
                }
            }
            foreach ($other_order_number as $val) {
                $orther_where['order_number'] = $val;
                $orther_order_node_data = (new OrderNodeDetail())->where($orther_where)->select();
                $order_data['other_order_data'][$val] = collection($orther_order_node_data)->toArray();
            }
        }
        $this->success('成功', $order_data, 200);
    }

    /**
     * 获取订单物流明细
     *
     * @Description
     * @author Lx
     * @since 2020/05/28 15:00:07 
     */
    public function query_track()
    {
        //校验参数
        $order_id = $this->request->request('order_id'); //订单id
        $order_number = $this->request->request('order_number'); //订单号
        $track_number = $this->request->request('track_number'); //快递单号
        $site = $this->request->request('site'); //站点

        if (!$order_id && !$order_number && !$track_number) {
            $this->error(__('缺少订单id或订单号或运单号参数'), [], 400);
        }

        if (!$site) {
            $this->error(__('缺少站点参数'), [], 400);
        }

        if ($order_id) {
            $where['order_id'] = $order_id;
        }
        if ($order_number) {
            $where['order_number'] = $order_number;
        }
        if ($track_number) {
            $where['track_number'] = $track_number;
        }

        $where['site'] = $site;

        $order_track_data = (new OrderNodeCourier())->where($where)->order('create_time desc')->select();
        $order_track_data = collection($order_track_data)->toArray();

        $this->success('成功', $order_track_data, 200);
    }

    /**
     * 补差价订单支付成功 钉钉通知工单创建人
     *
     * @Description
     * @author wpl
     * @since 2020/06/05 13:37:18 
     * @return void
     */
    /**
     * 补差价订单支付成功 钉钉通知工单创建人
     *
     * @Description
     * @author wpl
     * @since 2020/06/05 13:37:18 
     * @return void
     */
    public function order_pay_ding()
    {
        //校验参数
        $work_order_id = $this->request->request('work_order_id'); //魔晶工单id
        $order_number = $this->request->request('order_number'); //补差价单号
        if (!$work_order_id) {
            $this->error(__('缺少工单号参数'));
        }
        if (!$order_number) {
            $this->error(__('缺少补差价单号参数'));
        }
        //根据工单id查询工单
        $workorder = new \app\admin\model\saleaftermanage\WorkOrderList();
        $list = $workorder->where(['id' => $work_order_id])->field('create_user_id,id')->find();
        if ($list) {
            $workorder->where(['id' => $work_order_id])->update(['replenish_increment_id' => $order_number]);
            //Ding::cc_ding($list['create_user_id'], '', '工单ID:' . $list['id'] . '😎😎😎😎补差价订单支付成功需要你处理😎😎😎😎', '补差价订单支付成功需要你处理');
            //判断查询的工单中有没有其他措施
            /*$measure_choose_id = Db::name('work_order_measure')->where('work_id', $list['id'])->column('measure_choose_id');
            if (count($measure_choose_id) == 1 && in_array(8, $measure_choose_id)) {
                //如果只有一个补差价，就更改主表的状态
                $workorder->where('id', $list['id'])->update(['work_status' => 6]);
            }
            $date = date('Y-m-d H:i:s');
            Db::name('work_order_measure')->where(['work_id' => $list['id'], 'measure_choose_id' => 8])->update(['operation_type' => 1, 'operation_time' => $date]);
            $measure_id = Db::name('work_order_measure')->where(['work_id' => $list['id'], 'measure_choose_id' => 8])->value('id');
            //判断该工单中是否有其他措施，判断其他措施的状态去改主工单的状态
            $status_arr = Db::name('work_order_measure')->where(['work_id' => $list['id'], 'measure_choose_id' => ['neq', 8]])->column('operation_type');
            if (!$status_arr) {
                $data['work_status'] = 6;
                $data['complete_time'] = date('Y-m-d H:i:s');
            } elseif (in_array(2, $status_arr) || in_array(0, $status_arr)) {
                $data['work_status'] = 5;
            } else {
                $data['work_status'] = 6;
                $data['complete_time'] = date('Y-m-d H:i:s');
            }
            $workorder->where('id', $list['id'])->update($data);
            Db::name('work_order_recept')->where(['work_id' => $list['id'], 'measure_id' => $measure_id])->update(['recept_status' => 1, 'finish_time' => $date, 'note' => '补差价支付成功']);*/
        } else {
            $this->error(__('未查询到数据'));
        }
        $this->success('成功', [], 200);
    }

    /**
     * 同步商品上下架状态
     *
     * @Description
     * @author wpl
     * @since 2020/07/23 09:26:56 
     * @return void
     */
    public function set_product_status()
    {
        if ($this->request->isPost()) {
            $site = $this->request->request('site'); //站点
            $sku = $this->request->request('sku'); //platform_sku
            $status = $this->request->request('status'); //status 1上架 2下架
            if (!$sku) {
                $this->error(__('缺少SKU参数'), [], 400);
            }

            if (!$site) {
                $this->error(__('缺少站点参数'), [], 400);
            }

            if (!$status) {
                $this->error(__('缺少状态参数'), [], 400);
            }
            $platform = new \app\admin\model\itemmanage\ItemPlatformSku();
            $list = $platform->where(['platform_type' => $site, 'platform_sku' => $sku])->find();
            if (!$list) {
                $this->error(__('未查询到记录'), [], 400);
            }

            $res = $platform->allowField(true)->isUpdate(true, ['platform_type' => $site, 'platform_sku' => $sku])->save(['outer_sku_status' => $status]);
            if (false !== $res) {
                //如果是上架 则查询此sku是否存在当天有效sku表里
                if ($status == 1) {
                    //判断上下架时间记录表中是否有该sku，如果没有，插入一条记录，如果有更新上架时间
                    $isExistShelvesTime = Db::name('sku_shelves_time')
                        ->where(['site' => $site, 'platform_sku' => $sku])
                        ->value('id');
                    $arr = [
                        'site'         => $site,
                        'sku'          => $list['sku'],
                        'platform_sku' => $sku,
                        'shelves_time' => time(),
                    ];
                    if ($isExistShelvesTime) {
                        //有该记录，更新上架时间
                        Db::name('sku_shelves_time')
                            ->where('id', $isExistShelvesTime)
                            ->update($arr);
                    } else {
                        $arr['created_at'] = time();
                        //没有记录，插入数据
                        Db::name('sku_shelves_time')
                            ->insert($arr);
                    }
                    $count = Db::name('sku_sales_num')->where(['platform_sku' => $sku, 'site' => $site, 'createtime' => ['between', [date('Y-m-d 00:00:00'), date('Y-m-d 23:59:59')]]])->count();
                    //如果不存在则插入此sku
                    if ($count < 1) {
                        $data['sku'] = $list['sku'];
                        $data['platform_sku'] = $list['platform_sku'];
                        $data['site'] = $site;
                        Db::name('sku_sales_num')->insert($data);
                    }
                }
                $this->success('同步成功', [], 200);
            } else {
                $this->error('同步失败', [], 400);
            }
        }
    }


    /**
     * 批量同步商品上下架状态
     *
     * @Description
     * @author wpl
     * @since 2020/07/23 09:26:56 
     * @return void
     */
    public function batch_set_product_status()
    {
        $value = $this->request->post();
        $day_date = date('Y-m-d');
        if (!$value['site']) {
            $this->error(__('缺少站点参数'));
        }
        foreach ($value['skus'] as $key => $item) {
            if (!$item['sku']) {
                $this->error(__('缺少SKU参数'));
            }
            if (!$item['status']) {
                $this->error(__('缺少状态参数'));
            }
            $platform = new \app\admin\model\itemmanage\ItemPlatformSku();
            $list = $platform
                ->alias('p')
                ->join('fa_item_category c', 'p.category_id=c.id', 'left')
                ->where(['p.platform_type' => $value['site'], 'p.platform_sku' => $item['sku']])
                ->field('p.sku,p.platform_sku,c.name')
                ->find();
            if (!$list) {
                unset($item);
            } else {
                $res = $platform->allowField(true)->isUpdate(true, ['platform_type' => $value['site'], 'platform_sku' => $item['sku']])->save(['outer_sku_status' => $item['status']]);
                //判断是否有当天sku数据，有就更新，没有就插入
                $isExistSkuDay = Db::name('sku_status_dataday')
                    ->where(['site' => $value['site'], 'platform_sku' => $item['sku'], 'day_date' => $day_date])
                    ->find();
                if ($isExistSkuDay['id']) {
                    Db::name('sku_status_dataday')
                        ->where('id', $isExistSkuDay['id'])
                        ->update(['status' => $item['online_status']]);
                } else {
                    $arr['day_date'] = $day_date;
                    $arr['site'] = $value['site'];
                    $arr['sku'] = $list['sku'];
                    $arr['platform_sku'] = $list['platform_sku'];
                    $arr['category_name'] = $list['name'] ? $list['name'] : '';
                    $arr['status'] = $item['online_status'];
                    Db::name('sku_status_dataday')
                        ->insert($arr);
                }
                if (false !== $res) {
                    //如果是上架 则查询此sku是否存在当天有效sku表里
                    if ($item['status'] == 1) {
                        $count = Db::name('sku_sales_num')->where(['platform_sku' => $item['sku'], 'site' => $value['site'], 'createtime' => ['between', [date('Y-m-d 00:00:00'), date('Y-m-d 23:59:59')]]])->count();
                        //如果不存在则插入此sku
                        if ($count < 1) {
                            $data['sku'] = $list['sku'];
                            $data['platform_sku'] = $list['platform_sku'];
                            $data['site'] = $value['site'];
                            Db::name('sku_sales_num')->insert($data);
                        }
                    }
                } else {
                    $this->error('同步失败');
                }
            }
        }
        $this->success('同步成功', [], 200);
    }


    /**
     * 扣减库存及虚拟库存
     *
     * @Description
     * @author wpl
     * @since 2020/08/03 15:54:42 
     * @return void
     */
    public function set_goods_stock()
    {
        if ($this->request->isPost()) {
            $site = $this->request->request('site'); //站点
            $orderid = $this->request->request('orderid'); //订单id
            $order_number = $this->request->request('order_number'); //订单号
            $order_data = $this->request->request('order_data'); //订单json数据
            if (!$site) {
                $this->error(__('缺少站点参数'), [], 400);
            }

            if (!$orderid) {
                $this->error(__('缺少订单id参数'), [], 400);
            }

            if (!$order_number) {
                $this->error(__('缺少订单号参数'), [], 400);
            }

            $item = new \app\admin\model\itemmanage\Item();
            $platform = new \app\admin\model\itemmanage\ItemPlatformSku();
            //订单json数据 包含sku qty
            $order_data = json_decode(htmlspecialchars_decode($order_data), true);
            if (!$order_data) {
                $this->error(__('缺少数据参数'), [], 400);
            }
            $skus = array_column($order_data, 'sku');
            //查询所有true sku
            $platform_data = $platform->where(['platform_sku' => ['in', $skus], 'platform_type' => $site])->column('*', 'platform_sku');
            foreach ($order_data as $k => $v) {
                $true_sku = $platform_data[$v['sku']]['sku'];
                $qty = $v['qty'];
                //扣减对应站点虚拟仓库存
                $platform_res = $platform->where(['sku' => $true_sku, 'platform_type' => $site])->setDec('stock', $qty);
                if ($platform_res !== false) {
                    //扣减可用库存 增加订单占用库存
                    $item_res = $item->where(['is_del' => 1, 'is_open' => 1, 'sku' => $true_sku])->dec('available_stock', $qty)->inc('occupy_stock', $qty)->update();
                } else {
                    file_put_contents('/www/wwwroot/mojing/runtime/log/set_goods_stock.log', '扣减虚拟库存失败：site:' . $site . '|订单id:' . $orderid . '|sku:' . $true_sku . "\r\n", FILE_APPEND);
                }


                //如果虚拟仓库存不足 判断此sku 对应站点是否开启预售
                if ($platform_data[$v['sku']]['stock'] < $qty) {
                    //判断是否开启预售 并且在有效时间内 并且预售剩余数量大于0
                    if ($platform_data[$v['sku']]['presell_status'] == 1 && strtotime($platform_data[$v['sku']]['presell_create_time']) <= time() && strtotime($platform_data[$v['sku']]['presell_end_time']) >= time() && $platform_data[$v['sku']]['presell_residue_num'] > 0) {
                        $available_stock = $platform_data[$v['sku']]['stock'];
                        //判断可用库存小于0时 应扣减预售数量为当前qty 否则预售数量等于 qty 减去现有的可用库存
                        if ($available_stock <= 0) {
                            $presell_num = $qty;
                        } else {
                            $presell_num = $qty - $available_stock;
                        }

                        //判断如果剩余预售数量 大于 应扣减预售数量时 剩余预售数量= 现有剩余预售数量减去应扣减预售数量   否则 剩余预售数量全部扣减为0
                        if ($platform_data[$v['sku']]['presell_residue_num'] >= $presell_num) {
                            $presell_residue_num = $platform_data[$v['sku']]['presell_residue_num'] - $presell_num;
                        } else {
                            $presell_residue_num = 0;
                        }
                        //扣减剩余预售数量
                        $platform_res = $platform->where(['sku' => $true_sku, 'platform_type' => $site])->update(['presell_residue_num' => $presell_residue_num]);
                        if ($platform_res === false) {
                            file_put_contents('/www/wwwroot/mojing/runtime/log/set_goods_stock.log', '扣减预售数量：site:' . $site . '|订单id:' . $orderid . '|sku:' . $true_sku . '|扣减预售数量：' . $presell_residue_num . "\r\n", FILE_APPEND);
                        }
                    }
                }

                if (false !== $item_res) {
                    //生成扣减库存日志
                    (new StockLog())->setData([
                        'type'                   => 1,
                        'site'                   => $site,
                        'one_type'               => 1,
                        'sku'                    => $true_sku,
                        'order_number'           => $order_number,
                        'public_id'              => $orderid,
                        'occupy_stock_change'    => $qty,
                        'available_stock_change' => -$qty,
                        'create_person'          => 'admin',
                        'create_time'            => date('Y-m-d H:i:s'),
                        'remark'                 => '生成订单扣减可用库存,增加占用库存',
                    ]);
                } else {
                    file_put_contents('/www/wwwroot/mojing/runtime/log/set_goods_stock.log', '可用库存扣减失败：site:' . $site . '|订单id:' . $orderid . '|sku:' . $true_sku . "\r\n", FILE_APPEND);
                }
            }

            if (false !== $item_res) {
                $this->success('处理成功', [], 200);
            } else {
                $this->error('处理失败', [], 400);
            }
        }
    }

    /**
     * 获取sku是否有库存
     *
     * @Description
     * @author wpl
     * @since 2020/08/04 10:00:37 
     * @return void
     */
    public function get_goods_stock()
    {
        if ($this->request->isPost()) {
            $site = $this->request->request('site'); //站点
            $skus = $this->request->request('skus'); // sku 数组
            if (!$site) {
                $this->error(__('缺少站点参数'), [], 400);
            }

            if (!$skus) {
                $this->error(__('缺少sku参数'), [], 400);
            }
            $platform = new \app\admin\model\itemmanage\ItemPlatformSku();
            $skus = json_decode(htmlspecialchars_decode($skus), true);
            //查询所有true sku
            $platform_data = $platform->where(['platform_sku' => ['in', $skus], 'platform_type' => $site])->select();
            $platform_data = collection($platform_data)->toArray();
            if (!$platform_data) {
                $this->error(__('未查询到数据'), [], 400);
            }
            $list = [];
            foreach ($platform_data as $k => $v) {
                //判断是否开启预售
                //如果开启预售并且库存大于0
                if ($v['stock'] >= 0 && $v['presell_status'] == 1 && strtotime($v['presell_create_time']) <= time() && strtotime($v['presell_end_time']) >= time()) {
                    $list[$k]['stock'] = $v['stock'] + $v['presell_residue_num'];
                    //如果开启预售并且库存小于0
                } elseif ($v['stock'] < 0 && $v['presell_status'] == 1 && strtotime($v['presell_create_time']) <= time() && strtotime($v['presell_end_time']) >= time()) {
                    $list[$k]['stock'] = $v['presell_residue_num'];
                } else {
                    $list[$k]['stock'] = $v['stock'];
                }
                $list[$k]['sku'] = $v['platform_sku'];
                if ($list[$k]['stock'] <= 0) {
                    $list[$k]['is_sell_out'] = 1;
                } else {
                    $list[$k]['is_sell_out'] = 0;
                }
            }

            if ($list) {
                $this->success('处理成功', $list, 200);
            } else {
                $this->error('处理失败', [], 400);
            }
        }
    }

    /**
     * 获取全部上架sku库存
     *
     * @Description
     * @author wpl
     * @since 2020/08/04 10:00:37 
     * @return void
     */
    public function get_all_goods_stock()
    {
        if ($this->request->isPost()) {
            $site = $this->request->request('site'); //站点
            if (!$site) {
                $this->error(__('缺少站点参数'), [], 400);
            }
            $platform = new \app\admin\model\itemmanage\ItemPlatformSku();
            //查询所有true sku
            $platform_data = $platform->where(['platform_type' => $site, 'outer_sku_status' => 1])->select();
            $platform_data = collection($platform_data)->toArray();
            if (!$platform_data) {
                $this->error(__('未查询到数据'), [], 400);
            }
            $list = [];
            foreach ($platform_data as $k => $v) {
                //判断是否开启预售
                //如果开启预售并且库存大于0
                if ($v['stock'] >= 0 && $v['presell_status'] == 1 && strtotime($v['presell_create_time']) <= time() && strtotime($v['presell_end_time']) >= time()) {
                    $list[$k]['stock'] = $v['stock'] + $v['presell_residue_num'];
                    //如果开启预售并且库存小于0
                } elseif ($v['stock'] < 0 && $v['presell_status'] == 1 && strtotime($v['presell_create_time']) <= time() && strtotime($v['presell_end_time']) >= time()) {
                    $list[$k]['stock'] = $v['presell_residue_num'];
                } else {
                    $list[$k]['stock'] = $v['stock'];
                }
                $list[$k]['sku'] = $v['platform_sku'];
                if ($list[$k]['stock'] <= 0) {
                    $list[$k]['is_sell_out'] = 1;
                } else {
                    $list[$k]['is_sell_out'] = 0;
                }
            }

            if ($list) {
                $this->success('返回成功', $list, 200);
            } else {
                $this->error('返回失败', [], 400);
            }
        }
    }

    /**
     * 小程序取消订单回滚库存
     *
     * @Description
     * @author wpl
     * @since 2020/08/10 09:23:55 
     * @return void
     */
    public function cancel_order_set_stock()
    {
        if ($this->request->isPost()) {
            $site = $this->request->request('site'); //站点
            $orderid = $this->request->request('orderid'); //订单id
            $order_number = $this->request->request('order_number'); //订单号
            $order_data = $this->request->request('order_data'); //订单json数据
            if (!$site) {
                $this->error(__('缺少站点参数'), [], 400);
            }

            if (!$orderid) {
                $this->error(__('缺少订单id参数'), [], 400);
            }

            if (!$order_number) {
                $this->error(__('缺少订单号参数'), [], 400);
            }

            $item = new \app\admin\model\itemmanage\Item();
            $platform = new \app\admin\model\itemmanage\ItemPlatformSku();
            //订单json数据 包含sku qty
            $order_data = json_decode(htmlspecialchars_decode($order_data), true);
            if (!$order_data) {
                $this->error(__('缺少数据参数'), [], 400);
            }

            foreach ($order_data as $k => $v) {
                $true_sku = $v['sku'];
                $qty = $v['qty'];
                //扣减可用库存 增加订单占用库存
                $item_res = $item->where(['is_del' => 1, 'is_open' => 1, 'sku' => $true_sku])->inc('available_stock', $qty)->dec('occupy_stock', $qty)->update();
                if (false !== $item_res) {
                    //生成扣减库存日志
                    (new StockLog())->setData([
                        'type'                   => 1,
                        'site'                   => $site,
                        'one_type'               => 2,
                        'sku'                    => $true_sku,
                        'order_number'           => $order_number,
                        'public_id'              => $orderid,
                        'occupy_stock_change'    => $qty,
                        'available_stock_change' => -$qty,
                        'create_person'          => 'admin',
                        'create_time'            => date('Y-m-d H:i:s'),
                        'remark'                 => '如佛小程序取消订单增加可用库存,扣减占用库存',
                    ]);
                } else {
                    file_put_contents('/www/wwwroot/mojing/runtime/log/set_goods_stock.log', '如佛小程序取消订单增加可用库存：site:' . $site . '|订单id:' . $orderid . '|sku:' . $true_sku . "\r\n", FILE_APPEND);
                }
            }

            if (false !== $item_res) {
                $this->success('处理成功', [], 200);
            } else {
                $this->error('处理失败', [], 400);
            }
        }
    }

    /**
     * vip订单-增加收入核算
     *
     * @Description
     * @author gyh
     * @param $income_amount 收入金额
     */
    public function vip_order_income($work_id = null)
    {
        $order_detail = $this->request->request();
        $params['type'] = 1;
        $params['bill_type'] = 2;//单据类型
        $params['order_number'] = $order_detail['order_number'];//订单号
        $params['site'] = $order_detail['site'];//站点
        $params['order_type'] = 9;//
        $params['order_money'] = $order_detail['base_grand_total'];//订单金额
        $params['income_amount'] = $order_detail['base_grand_total'];//收入金额
        $params['order_currency_code'] = $order_detail['order_currency_code'];//币种
        $params['payment_time'] = $order_detail['payment_time'];//支付时间
        $params['payment_method'] = $order_detail['payment_method'];//支付方式
        $params['action_type'] = 1;//动作类型：1增加；2冲减；
        $params['createtime'] = time();
        $FinanceCost = new FinanceCost();
        $res = $FinanceCost->insert($params);//vip订单-增加

        if (false !== $res) {
            $this->success('成功', [], 200);
        } else {
            $this->error('失败', [], 400);
        }
    }


    public function deal_track01()
    {
        $arr=[
            '430345249',
            '430345255',
            '430345256',
            '400613601',
            '130112275',
            '130112276',
            '400613613',
            '100252392',
            '100252394',
            '400613642',
            '130112287',
            '100252398',
            '430345304',
            '400613678',
            '400613679',
            '400613683',
            '400613691',
            '430345318',
            '600153126',
            '400613709',
            '400613717',
            '100252422',
            '400613725',
            '430345337',
            '400613731',
            '430345341',
            '100252427',
            '500036522',
            '400613744',
            '430345352',
            '430345358',
            '430345360',
            '400613769',
            '430345382',
            '400613810',
            '530017171',
            '400613835',
            '400613836',
            '400613846',
            '400613849',
            '430237980',
            '400613860',
            '400613873',
            '400613876',
            '400613879',
            '400613896',
            '400613912',
            '400613949',
            '400613950',
            '400613961',
            '130112328',
            '500036538',
            '130112329',
            '100252491',
            '430345496',
            '400614017',
            '400614028',
            '500036552',
            '100252533',
            '400614051',
            '400614058',
            '430345543',
            '500036556',
            '400614111',
            '100252587',
            '400614157',
            '430345587',
            '430345597',
            '430345603',
            '400614218',
            '130112394',
            '130112398',
            '430345702',
            '430345724',
            '130112438',
            '430345808',
            '400614591',
            '530017240',
            '400614669',
            '430345961',
            '100252889',
            '400614957',
            '400614958',
            '400614962',
            '100252957',
            '400614983',
            '400614994',
            '400615010',
            '530017286',
            '130112540',
            '400615059',
            '430346177',
            '430346394',
            '430346413',
            '430346420',
            '400615562',
            '400615563',
            '130112646',
            '430346483',
            '430346485',
            '430322264',
            '400570580',
            '400572846',
            '400577438',
            '430327881',
            '400593243',
            '100247094',
            '430337818',
            '130110606',
            '100249337',
            '100249342',
            '100249360',
            '400342350',
            '100249402',
            '100249408',
            '100249422',
            '100249433',
            '100249439',
            '100249463',
            '500035587',
            '400605747',
            '500035606',
            '430340752',
            '100249700',
            '100249771',
            '400606210',
            '430340877',
            '100249810',
            '100249844',
            '430340934',
            '100249873',
            '430340970',
            '400606458',
            '430341000',
            '400606469',
            '100249913',
            '130111074',
            '430341027',
            '130111083',
            '130111085',
            '100249993',
            '100249995',
            '430341095',
            '400606701',
            '400606726',
            '400606754',
            '530016504',
            '130111103',
            '130111109',
            '100250044',
            '430341183',
            '400606864',
            '400606889',
            '530016514',
            '130111125',
            '130111131',
            '400606992',
            '400607016',
            '130111138',
            '430341266',
            '100250109',
            '100250143',
            '400607142',
            '400607155',
            '500035760',
            '100250204',
            '130111187',
            '430341383',
            '400607287',
            '100250212',
            '430341410',
            '400607329',
            '400607349',
            '400607359',
            '100250227',
            '400607366',
            '530016552',
            '400607448',
            '430341480',
            '400607470',
            '400607516',
            '100250313',
            '430341581',
            '400607672',
            '130111249',
            '400607694',
            '430341614',
            '130111253',
            '130111255',
            '130111260',
            '430341646',
            '100250463',
            '100250492',
            '400607975',
            '400608036',
            '430341844',
            '400608135',
            '100250575',
            '130111405',
            '430341993',
            '400608293',
            '130111433',
            '430342084',
            '100250726',
            '430342186',
            '430342296',
            '530016696',
            '130111495',
            '430342402',
            '400609717',
            '400609861',
            '400610064',
            '400610072',
            '100251286',
            '130111799',
            '100251351',
            '100251354',
            '400610856',
            '530016909',
            '400611149',
            '430343758',
            '400611279',
            '500036496',
            '130112261',
            '400613547',
            '400613598',
            '430345303',
            '400613692',
            '400613736',
            '500036524',
            '400613788',
            '130111915',
            '100251538',
            '400611311',
            '430343888',
            '430343993',
            '400611571',
            '400611575',
            '400611601',
            '400611603',
            '400611612',
            '400611619',
            '430344069',
            '400611680',
            '400611875',
            '400611926',
            '400611933',
            '530016989',
            '400612004',
            '500036323',
            '400612021',
            '100251734',
            '400612025',
            '400612034',
            '500036324',
            '430344388',
            '430344401',
            '100251752',
            '430344414',
            '400612112',
            '500036335',
            '400612149',
            '400612159',
            '400612194',
            '400612195',
            '400612199',
            '400612218',
            '100251790',
            '400612228',
            '400612252',
            '400612259',
            '430344535',
            '400612262',
            '400612291',
            '100251823',
            '400612328',
            '400612337',
            '430344589',
            '430344605',
            '400612370',
            '400612376',
            '100251848',
            '400612380',
            '400612383',
            '500036366',
            '100251851',
            '430344634',
            '400612404',
            '430344640',
            '400612412',
            '430344648',
            '400612423',
            '530017037',
            '400612443',
            '430344664',
            '400612466',
            '430344680',
            '500036380',
            '430344708',
            '500036384',
            '400612518',
            '100251895',
            '430344761',
            '400612614',
            '430344784',
            '400612626',
            '430344794',
            '100251953',
            '400612654',
            '400612659',
            '430344805',
            '130112097',
            '130112098',
            '100251962',
            '400612690',
            '400612721',
            '430344830',
            '400612732',
            '400612734',
            '430344844',
            '100252003',
            '100252005',
            '100252007',
            '500036406',
            '400612776',
            '100252025',
            '100252029',
            '130112122',
            '130112130',
            '400612815',
            '100252086',
            '530017075',
            '400612908',
            '400612912',
            '100252096',
            '400612921',
            '400612932',
            '100252105',
            '500036419',
            '430344916',
            '530017080',
            '430344927',
            '400612976',
            '400612994',
            '400613034',
            '400613037',
            '400613065',
            '430344984',
            '430345003',
            '130112182',
            '530017094',
            '430345032',
            '400613162',
            '400613175',
            '100252242',
            '430345072',
            '430345076',
            '100252274',
            '400613307',
            '430345151',
            '400613455',
            '130112263',
            '400613561',
            '430345268',
            '400613660',
            '430345330',
            '430345332',
            '430345338',
            '430345342',
            '100252430',
            '400613791',
            '430345372',
            '400613802',
            '400613816',
            '130112308',
            '100252448',
            '430345409',
            '430350616',
            '130113652',
            '130108196',
            '130113120',
            '100255500',
            '130113861',
            '100257784',
            '130114794',
            '130114965',
            '100259123',
            '130115314',
            '130115364',
            '100259475',
            '100259549',
            '100259581',
            '100259678',
            '100259696',
            '100259745',
            '130115542',
            '130115571',
            '100259926',
            '130115661',
            '100260159',
            '100252323',
            '430350229',
            '400623526',
            '400624415',
            '430352460',
            '430352578',
            '400626728',
            '300051257',
            '400627887',
            '400628126',
            '400628184',
            '400628312',
            '300050852',
            '600154737',
            '600154744',
            '400628510',
            '100257996',
            '400628768',
            '430353733',
            '430353746',
            '400628864',
            '300051290',
            '300051293',
            '400629312',
            '600155010',
            '400630672',
            '300051378',
            '600155068',
            '600155072',
            '300051388',
            '100259118',
            '430355025',
            '430355077',
            '400631471',
            '400631920',
            '400631950',
            '400632019',
            '400632179',
            '300051431',
            '100259518',
            '400632339',
            '400632349',
            '600155224',
            '400632397',
            '600155231',
            '400632480',
            '400632483',
            '400632484',
            '400632526',
            '400632547',
            '400632549',
            '400632584',
            '430355625',
            '400632641',
            '300051450',
            '400632701',
            '400632714',
            '400632732',
            '600155276',
            '400632763',
            '500038840',
            '300051454',
            '400632823',
            '430355752',
            '400632855',
            '400632857',
            '400632877',
            '400632881',
            '400632886',
            '400632895',
            '400632897',
            '400632962',
            '500038861',
            '400632982',
            '400633000',
            '400633007',
            '430355848',
            '430355870',
            '430355874',
            '400633091',
            '100259822',
            '100259834',
            '500038890',
            '430355962',
            '500038895',
            '400633288',
            '100259909',
            '400633390',
            '400633461',
            '400633591',
            '400633601',
            '400633602',
            '300051478',
            '400633617',
            '530019234',
            '600155382',
            '400633728',
            '400633729',
            '430356264',
            '400633791',
            '400633806',
            '530019255',
            '130115722',
            '400594956',
            '100249853',
            '430341246',
            '400615431',
            '400616180',
            '400616286',
            '100253690',
            '100253834',
            '100254067',
            '430347819',
            '430349405',
            '430350369',
            '430351718',
            '100256906',
            '100257248',
            '530018474',
            '400626693',
            '100257532',
            '530018627',
            '130114798',
            '500038278',
            '130114877',
            '430353822',
            '430353909',
            '400629169',
            '430241960',
            '430353989',
            '400629400',
            '400629428',
            '430354053',
            '500038461',
            '430354145',
            '500038478',
            '130115036',
            '100258405',
            '100258415',
            '400629782',
            '430354209',
            '130115053',
            '430354217',
            '130115059',
            '100258476',
            '430354237',
            '430354276',
            '100258554',
            '100258562',
            '430354302',
            '130115101',
            '400630064',
            '430354356',
            '430091591',
            '430354372',
            '130115130',
            '500038543',
            '400630242',
            '100258666',
            '430354442',
            '430354452',
            '130115145',
            '430354502',
            '400408250',
            '400630408',
            '130115170',
            '130115173',
            '100258733',
            '400630488',
            '100258750',
            '430354567',
            '530018887',
            '130115182',
            '400630522',
            '530018892',
            '430354675',
            '430354718',
            '430354723',
            '100258857',
            '430354881',
            '430355015',
            '400631320',
            '100259152',
            '400631633',
            '130115341',
            '430355185',
            '430355250',
            '100259435',
            '530019083',
            '500038766',
            '500038770',
            '430355433',
            '430355491',
            '430355544',
            '430355564',
            '500038809',
            '500038810',
            '430355599',
            '430355644',
            '500038829',
            '100259697',
            '400632734',
            '400632759',
            '530019151',
            '430355710',
            '500038844',
            '100259732',
            '400632812',
            '430355743',
            '530019166',
            '430355841',
            '430355863',
            '100259796',
            '100259797',
            '500038871',
            '530019187',
            '430355924',
            '400295070',
            '430355940',
            '430355970',
            '430355986',
            '430356036',
            '400633328',
            '430356053',
            '100259903',
            '100259912',
            '400633413',
            '530001292',
            '430356115',
            '400633559',
            '430356151',
            '100259992',
            '400633588',
            '500038923',
            '430356165',
            '100260002',
            '100260006',
            '100260022',
            '400633639',
            '430356194',
            '100260043',
            '100260049',
            '400633681',
            '400633690',
            '400633692',
            '400633695',
            '400633702',
            '430356238',
            '430356239',
            '100260077',
            '400633734',
            '100260082',
            '400633745',
            '100260091',
            '100260093',
            '130115685',
            '130115686',
            '400633814',
            '400633815',
            '400633819',
            '100260132',
            '430356313',
            '100260157',
            '430356323',
            '400633884',
            '430356330',
            '400633888',
            '530019254',
            '400633890',
            '100260171',
            '100260172',
            '400633898',
            '400633899',
            '400633906',
            '400633916',
            '400633922',
            '430356343',
            '100260178',
            '400633937',
            '400633945',
            '130115715',
            '500038968',
            '400633976',
            '100260203',
            '100260210',
            '400633993',
            '400634000',
            '130115724',
            '430356402',
            '400634031',
            '130115732',
            '100260239',
            '500038981',
            '400634106',
            '130115744',
            '400634146',
            '430356461',
            '130115748',
            '400634173',
            '100260270',
            '130115753',
            '430356491',
            '400634207',
            '100260281',
            '430356499',
            '400634227',
            '400634236',
            '100260288',
            '430356509',
            '430356510',
            '400634243',
            '430356513',
            '430356532',
            '430356544',
            '400634318',
            '400634332',
            '430356562',
            '530019302',
            '130115779',
            '400634377',
            '400634381',
            '500039023',
            '130115784',
            '430356590',
            '430356613',
            '400634460',
            '400634476',
            '430356622',
            '430356624',
            '400634506',
            '400634507',
            '430356625',
            '500039035',
            '400634521',
            '430356690',
            '100260402',
            '430356698',
            '430356705',
            '430356736',
            '430356744',
            '130115832',
            '430356763',
            '400634758',
            '430356796',
            '130115856',
            '500039078',
            '430356823',
            '400634812',
            '430356840',
            '400634833',
            '400634837',
            '400634858',
            '430356901',
            '400634920',
            '430356903',
            '430356911',
            '430356922',
            '430356950',
            '100260532',
            '430356969',
            '430357009',
            '430357013',
            '400635032',
            '430357035',
            '500039117',
            '430357099',
            '400635083',
            '130115956',
            '430357106',
            '100260592',
            '430357116',
            '430357124',
            '430357128',
            '430357131',
            '400635144',
            '400635168',
            '130115975',
            '400635177',
            '400635201',
            '400635219',
            '430357182',
            '400635268'];
        $model=new OrderProcess();
        foreach ($arr as $value){
            $dataOrder=$model->where(array('increment_id'=>$value))->find();

            $order_id = $dataOrder['entity_id']; //订单id
            $order_number = $dataOrder['increment_id']; //订单号
            $site =$dataOrder['site']; //站点
            $title = "USPS"; //运营商
            $shipment_data_type = "加诺"; //渠道名称
            $track_number =$dataOrder['track_number']; //快递单号


            //查询节点主表记录
            $row = (new OrderNode())->where(['order_number' => $order_number])->find();
            if (!$row) {
                $this->error(__('订单记录不存在'), [], 400);
            }
            if ($row['track_number']==$track_number){
                continue;
            }

            //如果已发货 则不再更新发货时间
            //更新节点主表
            $row->allowField(true)->save([
                'order_node'         => 2,
                'node_type'          => 7,
                'update_time'        => date('Y-m-d H:i:s'),
                'shipment_type'      => $title,
                'shipment_data_type' => $shipment_data_type,
                'track_number'       => $track_number,
                'delivery_time'      => date('Y-m-d H:i:s'),
            ]);

            //更新order_node表中es数据
            $arr = [
                'id'                 => $row['id'],
                'order_node'         => 2,
                'node_type'          => 7,
                'shipment_type'      => $title,
                'shipment_data_type' => $shipment_data_type,
                'track_number'       => $track_number,
                'delivery_time'      => time(),
            ];
            $this->asyncEs->updateEsById('mojing_track', $arr);


            //插入节点子表
            (new OrderNodeDetail())->allowField(true)->save([
                'order_number'       => $order_number,
                'order_id'           => $order_id,
                'content'            => 'Order leave warehouse, waiting for being picked up.',
                'site'               => $site,
                'create_time'        => date('Y-m-d H:i:s'),
                'order_node'         => 2,
                'node_type'          => 7,
                'shipment_type'      => $title,
                'shipment_data_type' => $shipment_data_type,
                'track_number'       => $track_number,
            ]);


        }
        //校验参数

    }


    public function deal_track02()
    {
        $arr=[ '400639055',
            '430357195',
            '130115988',
            '430357199',
            '500039144',
            '430357206',
            '430357210',
            '400635296',
            '130115995',
            '430357240',
            '430357268',
            '430357277',
            '130116061',
            '600154879',
            '600154890',
            '600155380',
            '600155392',
            '600155436',
            '530019315',
            '400582912',
            '400584467',
            '430342163',
            '100253419',
            '400619611',
            '400621049',
            '130113651',
            '400621727',
            '400622307',
            '100257152',
            '430352421',
            '130114539',
            '430352633',
            '100257505',
            '430352966',
            '400627711',
            '400627770',
            '430353414',
            '430353502',
            '400628626',
            '130114890',
            '400629022',
            '400629150',
            '430353990',
            '400629380',
            '100258296',
            '400629623',
            '400629635',
            '400629688',
            '400629691',
            '430354174',
            '400629730',
            '130115042',
            '100258421',
            '130115045',
            '400629780',
            '400629793',
            '100258430',
            '100258468',
            '430354270',
            '430354274',
            '400629941',
            '100258547',
            '400630018',
            '130115102',
            '130115113',
            '430354352',
            '400630120',
            '100258630',
            '430354380',
            '130115129',
            '400630216',
            '400630221',
            '130115139',
            '100258670',
            '100258673',
            '300051347',
            '430354433',
            '430354493',
            '130115149',
            '430354495',
            '400630399',
            '430354513',
            '130115162',
            '430354519',
            '430354523',
            '400630423',
            '430354538',
            '100258727',
            '130115174',
            '100258749',
            '100258809',
            '130115215',
            '430354689',
            '130115220',
            '130115229',
            '430354715',
            '400630754',
            '130115234',
            '430354738',
            '400630785',
            '100258882',
            '100258885',
            '430354758',
            '400630872',
            '430354789',
            '400630896',
            '300051373',
            '430354813',
            '400631039',
            '430354890',
            '100258999',
            '430354916',
            '400631143',
            '100259041',
            '400631163',
            '430354977',
            '430354991',
            '400631287',
            '400631290',
            '430355013',
            '430355036',
            '430355053',
            '300051394',
            '130115315',
            '430355091',
            '430355108',
            '430355198',
            '400631796',
            '400632477',
            '430356270',
            '430356290',
            '430356291',
            '100260129',
            '400633826',
            '400633889',
            '430356345',
            '100260194',
            '100260246',
            '100260249',
            '400634260',
            '130115770',
            '430356535',
            '430356539',
            '430356548',
            '400634319',
            '400634322',
            '100260315',
            '130115777',
            '100260333',
            '400634383',
            '430356578',
            '400634430',
            '430356609',
            '400634456',
            '400634468',
            '400634469',
            '400634496',
            '130115797',
            '430356638',
            '400634549',
            '430356647',
            '100260380',
            '400634570',
            '100260383',
            '430356660',
            '100260386',
            '400634588',
            '430356673',
            '400634597',
            '400634607',
            '100260397',
            '430356689',
            '300051506',
            '400634627',
            '400634642',
            '400634646',
            '400634648',
            '400634663',
            '100260421',
            '430356742',
            '430356746',
            '400634675',
            '430356754',
            '400634679',
            '400634681',
            '430356756',
            '400634706',
            '430356770',
            '430356778',
            '400634733',
            '400634737',
            '400634771',
            '400634786',
            '400634793',
            '130115857',
            '400634809',
            '430356844',
            '400634844',
            '430356850',
            '100260471',
            '430356863',
            '130115878',
            '130115879',
            '130115885',
            '430356875',
            '430356890',
            '100260514',
            '100260515',
            '400634917',
            '430356914',
            '130115893',
            '400634934',
            '430356921',
            '300051525',
            '430356926',
            '430356930',
            '430356942',
            '430356943',
            '430356949',
            '400634959',
            '400634962',
            '430356956',
            '430356957',
            '430356961',
            '430356965',
            '430356966',
            '430356970',
            '430356971',
            '400634999',
            '430356984',
            '400635003',
            '100260542',
            '430357000',
            '430357004',
            '100260547',
            '100260550',
            '430357011',
            '130115934',
            '430357034',
            '130115941',
            '100260561',
            '430357036',
            '430357044',
            '130115946',
            '130115951',
            '400635075',
            '400635077',
            '100260580',
            '400635085',
            '130115965',
            '400635179',
            '400335870',
            '100260642',
            '400635255',
            '400635274',
            '400635280',
            '400635298',
            '400635303',
            '400635307',
            '100260705',
            '100180089',
            '130116019',
            '130116024',
            '400635481',
            '130116047',
            '100260814',
            '400635729',
            '100260830',
            '400635788',
            '100260850',
            '100260855',
            '400635792',
            '130116123',
            '400607838',
            '100253477',
            '500036829',
            '130112894',
            '100254637',
            '430348890',
            '400620406',
            '100255365',
            '500037407',
            '530017927',
            '500037416',
            '400621440',
            '430350255',
            '400622648',
            '530018090',
            '130114009',
            '130114048',
            '400623609',
            '400623649',
            '430351323',
            '400624317',
            '130114259',
            '100257018',
            '130114518',
            '100257409',
            '100257454',
            '100257511',
            '430352807',
            '400626950',
            '100257641',
            '130114662',
            '400627143',
            '100257715',
            '400627575',
            '400627688',
            '100257791',
            '400627807',
            '100257896',
            '600154725',
            '430353633',
            '400628602',
            '130114868',
            '430353695',
            '400628771',
            '530018711',
            '400628863',
            '430353791',
            '100258107',
            '530018720',
            '400628954',
            '100258131',
            '130114935',
            '400629171',
            '530018749',
            '130114944',
            '130114946',
            '400629264',
            '100258204',
            '400629290',
            '600154846',
            '400629361',
            '130114971',
            '430354045',
            '430354048',
            '400629458',
            '500038462',
            '130115001',
            '130115002',
            '400629535',
            '500038470',
            '600154882',
            '400629604',
            '100258341',
            '100258349',
            '500038479',
            '530018806',
            '400629656',
            '100258385',
            '400629731',
            '130115046',
            '100258427',
            '130115071',
            '400629890',
            '430354250',
            '430354251',
            '130115080',
            '530018824',
            '400629926',
            '400629943',
            '100258553',
            '130115093',
            '100258590',
            '100258607',
            '130115110',
            '100258636',
            '400630139',
            '430354374',
            '400630177',
            '430354388',
            '600154951',
            '100258654',
            '130115135',
            '600154960',
            '130115138',
            '430354423',
            '400630269',
            '400630303',
            '400630308',
            '430354457',
            '400630325',
            '100258689',
            '400630329',
            '430354481',
            '100258704',
            '400630368',
            '400630394',
            '400630398',
            '400630404',
            '400630425',
            '600154983',
            '400630457',
            '600154986',
            '430354566',
            '100258753',
            '100258758',
            '430354601',
            '100258773',
            '430354625',
            '130115199',
            '430354639',
            '400630619',
            '430354657',
            '100258807',
            '430354669',
            '430354672',
            '100258813',
            '400630693',
            '430354692',
            '430354701',
            '530018908',
            '430354731',
            '130115233',
            '500038613',
            '600155026',
            '400630775',
            '530018914',
            '100258892',
            '100258894',
            '400630837',
            '500038624',
            '430354767',
            '430354771',
            '430354777',
            '430354782',
            '430354784',
            '130115257',
            '400630882',
            '130115262',
            '400630923',
            '430354811',
            '100258961',
            '130115268',
            '530018929',
            '530018933',
            '100258980',
            '130115270',
            '430354888',
            '500038643',
            '100258998',
            '430354900',
            '400631073',
            '430354908',
            '430354911',
            '130115278',
            '100259022',
            '530018947',
            '430354946',
            '430354966',
            '400631176',
            '430354973',
            '400631219',
            '430354985',
            '430354986',
            '530018965',
            '400631252',
            '100259110',
            '430355011',
            '500038664',
            '430355014',
            '100259143',
            '400631337',
            '400631349',
            '400631356',
            '500038672',
            '400631361',
            '400631380',
            '400631390',
            '100259166',
            '430355066',
            '400631428',
            '430355106',
            '400631527',
            '130115327',
            '130115332',
            '130115335',
            '530019014',
            '530019017',
            '430355158',
            '430355165',
            '530019024',
            '430355171',
            '130115346',
            '430355178',
            '400631717',
            '430355184',
            '400631776',
            '400631867',
            '130115397',
            '130115399',
            '130115407',
            '130115416',
            '400632273',
            '100259523',
            '430355614',
            '100259682',
            '400632721',
            '130115532',
            '130115535',
            '130115537',
            '130115539',
            '400632936',
            '400632988',
            '400632991',
            '400633003',
            '500038868',
            '400633032',
            '130115565',
            '130115566',
            '400633133',
            '400633149',
            '400633154',
            '100259849',
            '130115578',
            '400633215',
            '130115580',
            '400633260',
            '400633263',
            '400633292',
            '400633293',
            '400633299',
            '400633312',
            '400633315',
            '400633327',
            '400633357',
            '130115611',
            '130115613',
            '100259934',
            '400633440',
            '400633442',
            '400633445',
            '100259941',
            '400633458',
            '130115621',
            '100259949',
            '130115626',
            '400633509',
            '400633523',
            '130115635',
            '100259971',
            '100259974',
            '100259988',
            '400633575',
            '130115650',
            '400633605',
            '400633607',
            '400633612',
            '100260028',
            '430356193',
            '500038937',
            '400633674',
            '400633691',
            '400633706',
            '130115670',
            '130115676',
            '400633742',
            '100260089',
            '130115681',
            '130115682',
            '130115688',
            '100260101',
            '130115694',
            '400633796',
            '400633812',
            '400633818',
            '430356308',
            '400633839',
            '400633869',
            '400633895',
            '100260169',
            '130115721',
            '400633980',
            '500038971',
            '400634011',
            '430356412',
            '100260228',
            '400634044',
            '400634060',
            '100260234',
            '400634069',
            '400634078',
            '400634079',
            '430356434',
            '400634101',
            '430356447',
            '400634124',
            '600155451',
            '400634132',
            '430356467',
            '430356475',
            '100260266',
            '130115751',
            '400634204',
            '100260279',
            '400634217',
            '400634220',
            '130115762',
            '430356511',
            '130115764',
            '400634261',
            '400634262',
            '400634267',
            '600155469',
            '430356528',
            '130115769',
            '400634279',
            '400634282',
            '400634287',
            '100260309',
            '600155474',
            '400634307',
            '400634316',
            '300051501',
            '100260317',
            '430356553',
            '430356555',
            '430356556',
            '130115776',
            '400634336',
            '400634337',
            '400634353',
            '430356568',
            '500039010',
            '400634367',
            '500039015',
            '400634403',
            '530019307',
            '400634427',
            '430356598',
            '400634436',
            '130115787',
            '430356608',
            '100260353',
            '100260354',
            '130115789',
            '100260359',
            '430356617',
            '100260362',
            '100260364',
            '130115793',
            '400634472',
            '130115794',
            '130115795',
            '400634478',
            '400634480',
            '500039031',
            '530019316',
            '400634493',
            '400634510',
            '400634511',
            '600155491',
            '400634526',
            '100260372',
            '400634541',
            '430356639',
            '100260375',
            '400634550',
            '130115800',
            '400634553',
            '430356648',
            '500039043',
            '430356655',
            '400634571',
            '100260385',
            '130115804',
            '430356671',
            '400634592',
            '400634593',
            '130115805',
            '130115806',
            '100260395',
            '430356681',
            '430356682',
            '400634612',
            '430356683',
            '400634618',
            '430356691',
            '130115815',
            '600155499',
            '130115821',
            '100260416',
            '100178012',
            '400634655',
            '430356747',
            '430356748',
            '500039057',
            '430356750',
            '400634686',
            '500039059',
            '130115835',
            '400634695',
            '400634696',
            '400634711',
            '600155514',
            '430356768',
            '430356771',
            '130115838',
            '400634743',
            '430356785',
            '500039066',
            '500039068',
            '400634761',
            '500039071',
            '400634766',
            '430356794',
            '400634772',
            '500039072',
            '430356795',
            '500039074',
            '100260448',
            '400634785',
            '430356802',
            '400634790',
            '100260452',
            '430356809',
            '430356811',
            '400634796',
            '500039077',
            '430356822',
            '430356827',
            '430356828',
            '500039080',
            '400634816',
            '600155528',
            '430356831',
            '130115861',
            '430356835',
            '430356836',
            '400634829',
            '100260465',
            '430356838',
            '400634834',
            '430356841',
            '500039083',
            '400634843',
            '400634846',
            '430356852',
            '530019357',
            '430356854',
            '430356860',
            '430356861',
            '130115880',
            '130115883',
            '430356871',
            '100260489',
            '100260499',
            '400634896',
            '430356882',
            '430356885',
            '400634905',
            '430356898',
            '430356904',
            '600154992',
            '430356913',
            '430356912',
            '530019365',
            '100260519',
            '400634932',
            '430356918',
            '430356919',
            '430356923',
            '430356927',
            '400634942',
            '430356932',
            '430356934',
            '430356935',
            '430356937',
            '430356945',
            '430356948',
            '500039104',
            '430356951',
            '400634965',
            '100260530',
            '430356953',
            '430356954',
            '400634974',
            '400634976',
            '430356959',
            '130115902',
            '430356967',
            '400634993',
            '430356977',
            '430356980',
            '530019369',
            '430356983',
            '500039106',
            '400635001',
            '430356988',
            '530019370',
            '100260543',
            '130115922',
            '130115923',
            '430357005',
            '130115927',
            '430357006',
            '430357007',
            '530019371',
            '130115932',
            '400635031',
            '430357017',
            '430357019',
            '130115936',
            '530019373',
            '130115939',
            '430357028',
            '130115940',
            '430357031',
            '430357032',
            '430357038',
            '130115943',
            '430357039',
            '430357047',
            '430357048',
            '430357053',
            '600155555',
            '430357061',
            '430357066',
            '430357068',
            '400635057',
            '430357072',
            '430357073',
            '430357075',
            '430357078',
            '430357079',
            '430357082',
            '430357085',
            '400635073',
            '430357086',
            '430357089',
            '130115953',
            '430357094',
            '500039118',
            '100260579',
            '430357095',
            '430357096',
            '430357102',
            '400635087',
            '400635095',
            '400635097',
            '430357105',
            '100260589',
            '430357108',
            '600155566',
            '430357110',
            '130115960',
            '430357113',
            '430357119',
            '100260602',
            '430357120',
            '430357123',
            '400635128',
            '430357127',
            '400635136',
            '430357133',
            '130115971',
            '430357137',
            '130115972',
            '400635158',
            '400635160',
            '430357148',
            '430357149',
            '600155574',
            '430357157',
            '400635185',
            '400635186',
            '100260627',
            '430357163',
            '400635203',
            '400635205',
            '430357169',
            '430357171',
            '430357173',
            '430357174',
            '430357181',
            '430357184',
            '400635237',
            '430357185',
            '400635243',
            '400635256',
            '400635259',
            '400635271',
            '500039143',
            '430357207',
            '500039145',
            '400635293',
            '430357212',
            '430357213',
            '430357214',
            '130115992',
            '430357220',
            '600155585',
            '430357228',
            '130115998',
            '430357230',
            '100260686',
            '430357235',
            '430357244',
            '430357252',
            '130116003',
            '400635363',
            '430357263',
            '430357278',
            '130116011',
            '430357282',
            '430357285',
            '400635419',
            '430357290',
            '400635431',
            '430357300',
            '400635452',
            '400635453',
            '430357310',
            '430357313',
            '430357318',
            '430357319',
            '400635497',
            '430357350',
            '430357353',
            '500039182',
            '430357377',
            '430357378',
            '100260785',
            '400635615',
            '400635616',
            '400635618',
            '400635633',
            '430357432',
            '130116054',
            '100260799',
            '400635657',
            '430357449',
            '130116060',
            '430357455',
            '400635676',
            '430357459',
            '130116067',
            '430357483',
            '430357484',
            '500039201',
            '530019460',
            '130116071',
            '430357510',
            '430357511',
            '430357515',
            '430357518',
            '100260834',
            '130116084',
            '400635766',
            '430357535',
            '430357551',
            '430357553',
            '400635782',
            '430357570',
            '430357588',
            '430357592',
            '430357599',
            '400635876',
            '530019466',
            '430357620',
            '130116116',
            '430357653',
            '430357667',
            '100260879',
            '130116126',
            '430357702',
            '430357747',
            '430357748',
            '430357754'];
        $model=new OrderProcess();
        foreach ($arr as $value){
            $dataOrder=$model->where(array('increment_id'=>$value))->find();

            $order_id = $dataOrder['entity_id']; //订单id
            $order_number = $dataOrder['increment_id']; //订单号
            $site =$dataOrder['site']; //站点
            $title = "USPS"; //运营商
            $shipment_data_type = "加诺"; //渠道名称
            $track_number =$dataOrder['track_number']; //快递单号


            //查询节点主表记录
            $row = (new OrderNode())->where(['order_number' => $order_number])->find();
            if (!$row) {
                $this->error(__('订单记录不存在'), [], 400);
            }
            if ($row['track_number']==$track_number){
                continue;
            }
            //如果已发货 则不再更新发货时间
            //更新节点主表
            $row->allowField(true)->save([
                'order_node'         => 2,
                'node_type'          => 7,
                'update_time'        => date('Y-m-d H:i:s'),
                'shipment_type'      => $title,
                'shipment_data_type' => $shipment_data_type,
                'track_number'       => $track_number,
                'delivery_time'      => date('Y-m-d H:i:s'),
            ]);

            //更新order_node表中es数据
            $arr = [
                'id'                 => $row['id'],
                'order_node'         => 2,
                'node_type'          => 7,
                'shipment_type'      => $title,
                'shipment_data_type' => $shipment_data_type,
                'track_number'       => $track_number,
                'delivery_time'      => time(),
            ];
            $this->asyncEs->updateEsById('mojing_track', $arr);


            //插入节点子表
            (new OrderNodeDetail())->allowField(true)->save([
                'order_number'       => $order_number,
                'order_id'           => $order_id,
                'content'            => 'Order leave warehouse, waiting for being picked up.',
                'site'               => $site,
                'create_time'        => date('Y-m-d H:i:s'),
                'order_node'         => 2,
                'node_type'          => 7,
                'shipment_type'      => $title,
                'shipment_data_type' => $shipment_data_type,
                'track_number'       => $track_number,
            ]);


        }
        //校验参数

    }


    public function deal_track03()
    {
        $arr=[
            '430357757',
            '430357775',
            '400636144',
            '430357814',
            '430357821',
            '430357823',
            '430357833',
            '430357878',
            '430357959',
            '130116209',
            '130116212',
            '430358007',
            '430358035',
            '430358043',
            '100253700',
            '430347538',
            '430349135',
            '300051038',
            '100255880',
            '430350824',
            '100256809',
            '400625019',
            '430351935',
            '130114465',
            '430352774',
            '500038140',
            '430352900',
            '400627391',
            '430353088',
            '530018621',
            '100257878',
            '400628481',
            '400628859',
            '500038377',
            '130114915',
            '430353830',
            '300051297',
            '430353843',
            '430353844',
            '430353897',
            '400629249',
            '400629270',
            '400629316',
            '430354014',
            '100258247',
            '430354042',
            '430354056',
            '430354061',
            '430354069',
            '130114993',
            '430354103',
            '130115003',
            '400629547',
            '430354117',
            '430354121',
            '430354126',
            '100258320',
            '130115018',
            '430354148',
            '400629630',
            '430354156',
            '430354176',
            '130115037',
            '430354185',
            '430354193',
            '300051325',
            '400629807',
            '430354206',
            '100258450',
            '530018820',
            '430354232',
            '430354235',
            '430354241',
            '100258509',
            '400629913',
            '430354264',
            '400629920',
            '430354292',
            '400630058',
            '130115109',
            '430354351',
            '400630079',
            '400630090',
            '130115124',
            '430354401',
            '400630228',
            '400630232',
            '430354419',
            '300051345',
            '400630279',
            '100258681',
            '430354467',
            '430354477',
            '430354516',
            '430354530',
            '130115177',
            '100258751',
            '400630531',
            '430354595',
            '430354617',
            '130115208',
            '430354827',
            '400631783',
            '100259319',
            '430355411',
            '430355427',
            '400632244',
            '500038779',
            '400632374',
            '430355516',
            '400632453',
            '400632631',
            '430355671',
            '430355697',
            '400632769',
            '530019156',
            '430355735',
            '400632830',
            '400632837',
            '400632854',
            '430355788',
            '430355790',
            '430355802',
            '400632941',
            '100259767',
            '430355824',
            '430355839',
            '430355842',
            '430355854',
            '430355882',
            '400633071',
            '100259814',
            '430355891',
            '430355904',
            '430355917',
            '430355921',
            '430355951',
            '430355995',
            '430355999',
            '430356006',
            '500038894',
            '400633276',
            '430356033',
            '430356045',
            '430356056',
            '430356058',
            '430356059',
            '430356060',
            '430356063',
            '430356069',
            '430356071',
            '430356073',
            '430356092',
            '430356094',
            '430356095',
            '430356098',
            '430356099',
            '430356102',
            '430356106',
            '430356110',
            '430356118',
            '430356124',
            '430018240',
            '430356134',
            '100259968',
            '400633539',
            '430356155',
            '400633566',
            '300051476',
            '130115642',
            '400633585',
            '130115644',
            '400633608',
            '100260015',
            '430356182',
            '400633618',
            '100260031',
            '100260040',
            '400633650',
            '400633657',
            '400633670',
            '600155385',
            '130115663',
            '400633679',
            '430356231',
            '100260071',
            '400633717',
            '430356235',
            '400633739',
            '100260084',
            '100260086',
            '130115678',
            '400633750',
            '430356257',
            '100260108',
            '430356282',
            '430356286',
            '130115695',
            '400633794',
            '500038959',
            '100260123',
            '400633808',
            '100260124',
            '100260126',
            '100260128',
            '100260130',
            '400633820',
            '430356300',
            '400633831',
            '400633846',
            '100260154',
            '400633852',
            '100260156',
            '400633854',
            '100260158',
            '400633865',
            '100260162',
            '100260165',
            '430356329',
            '130115709',
            '400633887',
            '400633901',
            '400633903',
            '400633907',
            '400633918',
            '130115711',
            '400633942',
            '100260184',
            '400633949',
            '400633951',
            '130115717',
            '430356365',
            '400633977',
            '100260196',
            '430356382',
            '300051491',
            '430356390',
            '530019266',
            '430356405',
            '430356406',
            '400634028',
            '530019269',
            '400634032',
            '130115727',
            '130115728',
            '400634072',
            '430356430',
            '100260243',
            '130115737',
            '430356443',
            '130115739',
            '130115741',
            '400634145',
            '400634154',
            '130115747',
            '430356465',
            '430356486',
            '400634241',
            '100260310',
            '430356545',
            '430356632',
            '500039037',
            '400634534',
            '430356642',
            '430356650',
            '430356657',
            '430356665',
            '430356666',
            '400634589',
            '430356702',
            '400634630',
            '400634639',
            '400634653',
            '400634672',
            '400634712',
            '400634736',
            '100260438',
            '400634746',
            '100260461',
            '130115874',
            '130115875',
            '400634906',
            '100260513',
            '400634910',
            '400634921',
            '400634927',
            '300051524',
            '400634948',
            '400634979',
            '130115903',
            '130115909',
            '100260545',
            '100260549',
            '130115945',
            '400635047',
            '600153870',
            '530018519',
            '600154764',
            '600114412',
            '600154896',
            '500038515',
            '530018862',
            '600155096',
            '600155217',
            '500038797',
            '600155352',
            '600155356',
            '600155355',
            '430356103',
            '500038913',
            '430356113',
            '430356121',
            '430356125',
            '430356157',
            '600155368',
            '430356167',
            '430356169',
            '430356172',
            '500038930',
            '430356185',
            '430356191',
            '430356203',
            '430356208',
            '430356215',
            '500038942',
            '530019242',
            '430356259',
            '430356262',
            '430356295',
            '600097625',
            '430356303',
            '430356311',
            '430356312',
            '430356316',
            '430356325',
            '430356326',
            '500038964',
            '600155418',
            '430356357',
            '430356361',
            '600155426',
            '430356374',
            '600155428',
            '600155429',
            '430356381',
            '430356385',
            '430356386',
            '430356388',
            '500038975',
            '430356403',
            '430356414',
            '430356427',
            '430356431',
            '600155425',
            '430356442',
            '430356446',
            '500038983',
            '600155458',
            '430356482',
            '430356531',
            '430356561',
            '430356614',
            '430356699',
            '430356717',
            '430356733',
            '430356772',
            '430356775',
            '430356799',
            '430356847',
            '430356866',
            '600155540',
            '430356880',
            '500039109',
            '500039112',
            '430357045',
            '430357049',
            '430357055',
            '430324346',
            '600151738',
            '600150853',
            '600151759',
            '600151794',
            '600151796',
            '600151805',
            '300050480',
            '300050487',
            '600151949',
            '600151965',
            '600151985',
            '600151996',
            '600152035',
            '600152041',
            '600152132',
            '600152143',
            '100249880',
            '500035741',
            '400590106',
            '430333135',
            '530015351',
            '430336634',
            '430337265',
            '130110430',
            '400603386',
            '130110612',
            '130110616',
            '430339707',
            '130110626',
            '130110636',
            '130110705',
            '130110768',
            '530016338',
            '400604889',
            '130110829',
            '430340368',
            '430340412',
            '430340506',
            '430340533',
            '430340548',
            '430340612',
            '430340619',
            '400605766',
            '100249625',
            '400605806',
            '130110980',
            '400606078',
            '430340830',
            '400606706',
            '430341138',
            '400606805',
            '100250035',
            '100250069',
            '530016519',
            '400607062',
            '400607245',
            '400607253',
            '430341384',
            '430341421',
            '400607574',
            '500035810',
            '400607966',
            '430341929',
            '100250600',
            '600152426',
            '130111432',
            '400608473',
            '430342131',
            '130111454',
            '400608606',
            '430342212',
            '130111475',
            '400608768',
            '430342539',
            '430342729',
            '430342779',
            '130111670',
            '500036049',
            '430342813',
            '430342822',
            '100251133',
            '430342911',
            '400609687',
            '430342979',
            '430342991',
            '430343050',
            '130111736',
            '130111742',
            '400610034',
            '430343130',
            '430343170',
            '430343211',
            '430343242',
            '430343251',
            '430343264',
            '430343367',
            '430343382',
            '430343407',
            '400610507',
            '430343442',
            '130111813',
            '130111814',
            '430343455',
            '430343473',
            '130111825',
            '400610670',
            '430343516',
            '400610687',
            '430343521',
            '430343552',
            '400610849',
            '400610918',
            '400610984',
            '430343733',
            '430343988',
            '400611542',
            '430344058',
            '130111955',
            '430344156',
            '430344166',
            '400611787',
            '430344196',
            '430344203',
            '500036293',
            '430344230',
            '400611853',
            '130111972',
            '430344248',
            '430344257',
            '530017007',
            '430344480',
            '400612361',
            '500036362',
            '500036373',
            '400612429',
            '400612458',
            '130112058',
            '100251886',
            '430344699',
            '100251890',
            '400612524',
            '400612534',
            '130112068',
            '100251908',
            '300050750',
            '600152983',
            '400612570',
            '130112114',
            '430344852',
            '430344863',
            '100252048',
            '400612891',
            '430344905',
            '430344923',
            '100252160',
            '400613138',
            '400613151',
            '400613155',
            '430345051',
            '400613176',
            '100252256',
            '500036449',
            '300050781',
            '100252297',
            '400613355',
            '400613386',
            '400613415',
            '400613417',
            '500036483',
            '300050790',
            '400614025',
            '400614140',
            '130112377',
            '300050811',
            '400614198',
            '400614201',
            '100252651',
            '400614252',
            '400607711',
            '130111275',
            '130111285',
            '430341753',
            '530016605',
            '430341781',
            '530016608',
            '130111340',
            '130111371',
            '100250545',
            '430341963',
            '430341978',
            '400240343',
            '400608297',
            '530016647',
            '130111428',
            '430342066',
            '130111444',
            '400608535',
            '400309526',
            '430342217',
            '400608655',
            '130111529',
            '430342471',
            '130111542',
            '400611453',
            '430343964',
            '430344012',
            '430344193',
            '400611904',
            '430344310',
            '430344314',
            '430344324',
            '400612182',
            '430344596',
            '430344624',
            '430344626',
            '430344682',
            '400612492',
            '430344707',
            '430344790',
            '130112104',
            '400612729',
            '430344857',
            '400612795',
            '430344911',
            '430344934',
            '400613008',
            '130112160',
            '500036426',
            '130112172',
            '430344998',
            '400613090',
            '430345002',
            '400613112',
            '100252244',
            '430345066',
            '400613283',
            '100252299',
            '400613373',
            '430345142',
            '400613403',
            '130112229',
            '430345175',
            '430345196',
            '430345242',
            '430345248',
            '430345267',
            '530017160',
            '400613908',
            '430345474',
            '430345487',
            '400614098',
            '130112423',
            '400614376',
            '430349518',
            '400629628',
            '100254010',
            '100254598',
            '100255360',
            '100255486',
            '130113864',
            '430350487',
            '130114123',
            '400624580',
            '400624618',
            '430351606',
            '100256732',
            '530018287',
            '100256792',
            '100256933',
            '400625267',
            '400626738',
            '100257681',
            '430352949',
            '530018556',
            '430353078',
            '400627647',
            '400627758',
            '130114874',
            '400629904',
            '130115136',
            '430354480',
            '130115152',
            '400630482',
            '430354637',
            '430354642',
            '130115210',
            '100258814',
            '130115218',
            '400630723',
            '500038605',
            '400630734',
            '400630744',
            '400630804',
            '400630805',
            '100258909',
            '530018922',
            '130115254',
            '400630900',
            '430354795',
            '400630925',
            '430354872',
            '400631079',
            '400631140',
            '500038648',
            '430354960',
            '430354964',
            '400631211',
            '430354984',
            '130115289',
            '100259112',
            '400631296',
            '100259130',
            '400631332',
            '430355035',
            '400631411',
            '100259196',
            '400631498',
            '500038690',
            '400631574',
            '130115344',
            '400631752',
            '400631826',
            '400631908',
            '130115380',
            '100259387',
            '430355350',
            '530019063',
            '530019065',
            '400632072',
            '530019069',
            '400632096',
            '430355378',
            '400632113',
            '100259431',
            '100259458',
            '100249811',
            '400613885',
            '400615836',
            '530017473',
            '100253769',
            '100254173',
            '400618650',
            '100254758',
            '430348929',
            '100254777',
            '130113664',
            '400621718',
            '100255532',
            '100255726',
            '400623239',
            '400623491',
            '400623720',
            '500037806',
            '430351644',
            '400624696',
            '400625403',
            '600154418',
            '400625983',
            '100257243',
            '400626243',
            '530018449',
            '100257405',
            '400626442',
            '400626652',
            '430352725',
            '400626776',
            '100257610',
            '530018586',
            '400627692',
            '100257801',
            '400627857',
            '400628009',
            '530018626',
            '100257907',
            '430353531',
            '100257984',
            '100258007',
            '100258022',
            '400628682',
            '100258030',
            '100258042',
            '430353715',
            '530018701',
            '100258111',
            '100258112',
            '100258129',
            '530018737',
            '600154818',
            '100258193',
            '600154832',
            '430353971',
            '100258209',
            '430354011',
            '400629412',
            '530018786',
            '400629527',
            '400629553',
            '130115008',
            '400629572',
            '400629573',
            '100258315',
            '100258378',
            '400630263',
            '600154971',
            '400630558',
            '400631131',
            '400631272',
            '400631718',
            '100259309',
            '100259320',
            '400632112',
            '400632125',
            '130115420',
            '100259453',
            '130115436',
            '400632289',
            '400632369',
            '600155228',
            '530019104',
            '400632422',
            '400632458',
            '400632472',
            '100259592',
            '400632479',
            '600155245',
            '430355589',
            '400632644',
            '400632674',
            '430355659',
            '100259699',
            '100259707',
            '600155273',
            '430355682',
            '100259724',
            '400632776',
            '100259727',
            '430355717',
            '400632800',
            '400632826',
            '100259746',
            '500038857',
            '400632898',
            '400632900',
            '400632925',
            '400632937',
            '500038859',
            '100259769',
            '500038862',
            '400632983',
            '500038866',
            '600155311',
            '530019182',
            '100259800',
            '400633038',
            '100259809',
            '400633055',
            '400633065',
            '400633072',
            '400633076',
            '430355894',
            '400633087',
            '400633093',
            '400633094',
            '530019191',
            '100259830',
            '400633120',
            '400633141',
            '400633143',
            '400633166',
            '400633171',
            '530019197',
            '600155321',
            '400633198',
            '430355954',
            '400633208',
            '400633227',
            '100259866',
            '130115581',
            '400633234',
            '400633236',
            '400633237',
            '600155337',
            '500038896',
            '400633287',
            '530019207',
            '400633307',
            '430356046',
            '500038902',
            '430356049',
            '430356064',
            '130115603',
            '400633374',
            '400633381',
            '430356097',
            '400633434',
            '400633460',
            '430356122',
            '600155360',
            '400633492',
            '530019224',
            '430356129',
            '400633535',
            '500038920',
            '400633558',
            '600155367',
            '600155370',
            '130115652',
            '400633620',
            '100260037',
            '100260038',
            '100260039',
            '400633659',
            '400633660',
            '600155383',
            '430356209',
            '600155386',
            '400633700',
            '400633752',
            '600155400',
            '400633768',
            '400633781',
            '100260111',
            '400633875',
            '400633881',
            '430356344',
            '400633938',
            '430356419',
            '400634053',
            '400634077',
            '430356453',
            '400634152',
            '530019279',
            '530019281',
            '400634226',
            '600155471',
            '430356523',
            '100260302',
            '400634306',
            '400634309',
            '400634311',
            '400634326',
            '400634335',
            '400634352',
            '400634359',
            '400634428',
            '400634447',
            '400634449',
            '400634462',
            '400634556',
            '400634582',
            '130115814',
            '100255213',
            '130114287',
            '130115157',
            '100258929',
            '130115272',
            '100259024',
            '100259137',
            '100259307',
            '100260349',
            '100260453',
            '100260479',
            '100260500',
            '100260502',
            '100260552',
            '100260575',
            '100260601',
            '100260647',
            '100260749',
            '100261054',
            '400629415',
            '400630599',
            '400631059',
            '400631214',
            '130115296',
            '400631339',
            '130115311',
            '130115316',
            '130115325',
            '130115361',
            '130115442',
            '130115849',
            '130115854',
            '130115872',
            '130116045',
            '130116143',
            '130116215',
            '100257475',
            '400631449',
            '400631468',
            '400631586',
            '400633022',
            '400634206',
            '400634554',
            '400634566',
            '400634616',
            '400634757',
            '400634870',
            '400634875',
            '400634894',
            '400634926',
            '400635062',
            '400635080',
            '400635101',
            '400635109',
            '400635148',
            '400635149',
            '400635156',
            '430351596',
            '430354167',
            '430354920',
            '430354976',
            '430354978',
            '430355039',
            '430355056',
            '430355179',
            '430355192',
            '430355321',
            '430355362',
            '430356017',
            '430356661',
            '430356783',
            '430356798',
            '430356873',
            '430356893',
            '430356973',
            '430357103',
            '430357107',
            '430357115',
            '430357134',
            '430357156',
            '400635196',
            '430357168',
            '400635223',
            '430357175',
            '400635227',
            '430357190',
            '400635258',
            '400635330',
            '430357233',
            '430357280',
            '430357287',
            '430357293',
            '400635447',
            '400635462',
            '400635483',
            '400635590',
            '400635709',
            '400635710',
            '400635857',
            '400635897',
            '400635910',
            '400635915',
            '400635924',
            '400635931',
            '400635942',
            '400635944',
            '400635954',
            '400636000',
            '400636012',
            '400636031',
            '400636119',
            '400636200',
            '400636244',
            '400636299',
            '400636437',
            '400636438',
            '400636475',
            '400636558',
            '400636599',
            '400636604',
            '400636617',
            '400636672',
            '430346202',
            '400618407',
            '400620532',
            '100255714',
            '100256334',
            '400624988',
            '400626381',
            '400626696',
            '400627530',
            '430353403',
            '600154742',
            '130114902',
            '100258114',
            '300051295',
            '100258126',
            '400629318',
            '400629354',
            '530018856',
            '130115167',
            '430354665',
            '430354746',
            '130115255',
            '400630906',
            '130115266',
            '100258958',
            '430354822',
            '430200621',
            '530018936',
            '100259007',
            '430354912',
            '100259019',
            '430354936',
            '130115285',
            '430354987',
            '600155087',
            '100259120',
            '130115310',
            '600155095',
            '400631416',
            '430355068',
            '430355119',
            '400631561',
            '400631590',
            '130115339',
            '430355144',
            '530019018',
            '530019025',
            '430355187',
            '400631750',
            '430355204',
            '430355206',
            '400631819',
            '500038735',
            '430355227',
            '530019036',
            '400631846',
            '430355259',
            '430355262',
            '400631886',
            '400632028',
            '530019061',
            '430355375',
            '400632111',
            '130115411',
            '130115412',
            '530019079',
            '130115419',
            '100259873',
            '430356043',
            '100260160',
            '400634042',
            '600155453',
            '430356500',
            '400634709',
            '400634760',
            '430356801',
            '600155534',
            '100260469',
            '100260472',
            '100260478',
            '400634861',
            '500039086',
            '500039093',
            '430356891',
            '300051523',
            '430357041',
            '600155561',
            '400635090',
            '430357109',
            '100260594',
            '400635139',
            '100260609',
            '400635155',
            '600155575',
            '400635180',
            '400635187',
            '400635192',
            '530019393',
            '430357166',
            '400635197',
            '600155050',
            '300051537',
            '400635234',
            '500039137'];
        $model=new OrderProcess();
        foreach ($arr as $value){
            $dataOrder=$model->where(array('increment_id'=>$value))->find();

            $order_id = $dataOrder['entity_id']; //订单id
            $order_number = $dataOrder['increment_id']; //订单号
            $site =$dataOrder['site']; //站点
            $title = "USPS"; //运营商
            $shipment_data_type = "加诺"; //渠道名称
            $track_number =$dataOrder['track_number']; //快递单号


            //查询节点主表记录
            $row = (new OrderNode())->where(['order_number' => $order_number])->find();
            if (!$row) {
                $this->error(__('订单记录不存在'), [], 400);
            }
            if ($row['track_number']==$track_number){
                continue;
            }
            //如果已发货 则不再更新发货时间
            //更新节点主表
            $row->allowField(true)->save([
                'order_node'         => 2,
                'node_type'          => 7,
                'update_time'        => date('Y-m-d H:i:s'),
                'shipment_type'      => $title,
                'shipment_data_type' => $shipment_data_type,
                'track_number'       => $track_number,
                'delivery_time'      => date('Y-m-d H:i:s'),
            ]);

            //更新order_node表中es数据
            $arr = [
                'id'                 => $row['id'],
                'order_node'         => 2,
                'node_type'          => 7,
                'shipment_type'      => $title,
                'shipment_data_type' => $shipment_data_type,
                'track_number'       => $track_number,
                'delivery_time'      => time(),
            ];
            $this->asyncEs->updateEsById('mojing_track', $arr);


            //插入节点子表
            (new OrderNodeDetail())->allowField(true)->save([
                'order_number'       => $order_number,
                'order_id'           => $order_id,
                'content'            => 'Order leave warehouse, waiting for being picked up.',
                'site'               => $site,
                'create_time'        => date('Y-m-d H:i:s'),
                'order_node'         => 2,
                'node_type'          => 7,
                'shipment_type'      => $title,
                'shipment_data_type' => $shipment_data_type,
                'track_number'       => $track_number,
            ]);

        }
        //校验参数

    }

    public function deal_track04()
    {
        $arr=[
            '100260648',
            '430357186',
            '500039138',
            '100260655',
            '100260658',
            '400635272',
            '500039141',
            '130115989',
            '530019404',
            '300051540',
            '400635299',
            '430357216',
            '600155584',
            '130115997',
            '400635341',
            '400635348',
            '430357248',
            '430357258',
            '400635364',
            '500039160',
            '430357266',
            '430357269',
            '430357270',
            '400635377',
            '500039161',
            '300051546',
            '400635402',
            '400635409',
            '400635412',
            '430357284',
            '400635432',
            '400635437',
            '430357296',
            '400635442',
            '430357301',
            '430357304',
            '430357307',
            '130116021',
            '100260730',
            '430357320',
            '400635474',
            '400635475',
            '430357327',
            '130116028',
            '430357332',
            '430357337',
            '430357339',
            '430357344',
            '430357346',
            '430357347',
            '400635512',
            '400635519',
            '430357352',
            '400635527',
            '430357359',
            '430357364',
            '430357368',
            '130116038',
            '430357373',
            '430357375',
            '400635563',
            '430357385',
            '430357386',
            '130116046',
            '400635593',
            '430357400',
            '400635603',
            '100260784',
            '430357404',
            '430357405',
            '430357408',
            '100260790',
            '430357413',
            '130116050',
            '430357419',
            '100260794',
            '430357421',
            '400635640',
            '430357425',
            '400635645',
            '430357431',
            '400635654',
            '430357438',
            '430357439',
            '430357441',
            '130116059',
            '430096601',
            '430357452',
            '400226848',
            '430357490',
            '430357491',
            '430357493',
            '430357498',
            '430357507',
            '130116077',
            '130116082',
            '400635762',
            '500039209',
            '430357542',
            '430357560',
            '130116101',
            '430357580',
            '430357591',
            '400635877',
            '430357616',
            '130116117',
            '400635928',
            '430357643',
            '430357655',
            '130116120',
            '130116121',
            '500039229',
            '430357669',
            '430357686',
            '400635993',
            '400636002',
            '100260902',
            '430357717',
            '400636079',
            '400636083',
            '530019486',
            '430357762',
            '430357764',
            '130116147',
            '430357777',
            '400636140',
            '600155723',
            '400636151',
            '400636163',
            '430357809',
            '600142052',
            '400636197',
            '430357825',
            '430357844',
            '100260948',
            '430357853',
            '430357858',
            '430357860',
            '430357866',
            '100260959',
            '430357880',
            '430357882',
            '400636278',
            '100260963',
            '130116174',
            '430357896',
            '400636305',
            '400636322',
            '430357907',
            '400636327',
            '430357914',
            '430357924',
            '100260990',
            '100260992',
            '430357940',
            '100260994',
            '100261002',
            '130116194',
            '430357961',
            '130116198',
            '400636506',
            '430357978',
            '400636530',
            '430357986',
            '430357987',
            '400636571',
            '300051580',
            '400636593',
            '430358004',
            '400636615',
            '530019537',
            '430358014',
            '430358023',
            '430358045',
            '100261070',
            '530019544',
            '430358068',
            '130116230',
            '130116231',
            '430358084',
            '430358091',
            '430358093',
            '430358114',
            '430358120',
            '430358132',
            '400636991',
            '400636999',
            '400637030',
            '130111856',
            '100253437',
            '130113058',
            '130113961',
            '130114064',
            '100257398',
            '100259190',
            '130115489',
            '100259661',
            '130115506',
            '130115508',
            '100259703',
            '100259904',
            '100261043',
            '100261106',
            '100261165',
            '400626925',
            '400626956',
            '400181784',
            '400628413',
            '400629542',
            '400631942',
            '400632185',
            '300051436',
            '400632492',
            '400632527',
            '400632704',
            '400632722',
            '300050879',
            '400632805',
            '400632984',
            '400217856',
            '130115557',
            '130115599',
            '400633359',
            '400633450',
            '400633493',
            '300051515',
            '300051539',
            '400635701',
            '400635864',
            '400635882',
            '400636223',
            '400636235',
            '400636308',
            '400636352',
            '400636381',
            '400636528',
            '300051582',
            '130116224',
            '130116240',
            '130116250',
            '400305564',
            '130116277',
            '130116282',
            '130116298',
            '130116324',
            '130116341',
            '130116365',
            '430341131',
            '430350166',
            '430350251',
            '430350441',
            '430350598',
            '430350612',
            '430355504',
            '430355539',
            '430355741',
            '430355779',
            '430355781',
            '430355997',
            '430356131',
            '430357203',
            '430357581',
            '430357970',
            '430358010',
            '400636625',
            '430358066',
            '400636713',
            '400636760',
            '430358082',
            '400636791',
            '400636799',
            '400636831',
            '400636862',
            '400636903',
            '430358150',
            '400636944',
            '400636983',
            '400637000',
            '430358174',
            '400637064',
            '430358228',
            '400637136',
            '430358243',
            '400637142',
            '400637151',
            '400637162',
            '400637164',
            '400637177',
            '430358271',
            '430358275',
            '430358285',
            '430358289',
            '400637256',
            '430358302',
            '430358308',
            '400637284',
            '430358316',
            '400637304',
            '430358321',
            '400637329',
            '400637330',
            '430358337',
            '400637381',
            '400637436',
            '400637460',
            '400637506',
            '400637520',
            '400637535',
            '400637640',
            '400637654',
            '400637673',
            '400637773',
            '400637904',
            '400637951',
            '400637960',
            '130113809',
            '130113811',
            '600154105',
            '100256172',
            '100256610',
            '500037965',
            '130114535',
            '100258082',
            '600154807',
            '530018747',
            '130114943',
            '100258922',
            '130115256',
            '130115275',
            '530018954',
            '130115359',
            '130115437',
            '130115444',
            '100259530',
            '100259566',
            '500038804',
            '130115479',
            '100259632',
            '100259641',
            '100259686',
            '100259698',
            '130115514',
            '530019153',
            '100259731',
            '530019158',
            '600155290',
            '130115529',
            '130115533',
            '100259869',
            '100259905',
            '600155348',
            '600155351',
            '130115616',
            '130115622',
            '100259967',
            '500038919',
            '130115637',
            '130115638',
            '130115680',
            '130115704',
            '100260341',
            '500039147',
            '530019440',
            '600155705',
            '600155718',
            '600155724',
            '600155730',
            '600155762',
            '600155767',
            '500039306',
            '100261069',
            '600155783',
            '500039321',
            '600117116',
            '500039330',
            '100261103',
            '530019553',
            '600155830',
            '100261146',
            '100261148',
            '600155855',
            '500039371',
            '100261172',
            '100261179',
            '100261191',
            '500039386',
            '530019583',
            '100261207',
            '100261211',
            '500039393',
            '600155880',
            '430358351',
            '430358354',
            '430358358',
            '100261219',
            '430358365',
            '430358375',
            '600155892',
            '430358395',
            '100261247',
            '100261255',
            '100261266',
            '430358426',
            '100261279',
            '530019610',
            '100261306',
            '600155934',
            '100261315',
            '600155942',
            '430358563',
            '100261345',
            '430358589',
            '430358600',
            '600155965',
            '430358609',
            '500039449',
            '100261381',
            '430358631',
            '430358642',
            '430350233',
            '430350881',
            '400623678',
            '400623695',
            '430351533',
            '400625076',
            '430352199',
            '400627013',
            '400627051',
            '400628625',
            '430353727',
            '400628928',
            '400628946',
            '400629046',
            '400630917',
            '400631518',
            '430355177',
            '430355214',
            '430355283',
            '430355409',
            '430355415',
            '400632204',
            '430355447',
            '430355458',
            '400632334',
            '430355472',
            '430355484',
            '430355505',
            '430355511',
            '430355536',
            '400276824',
            '400632482',
            '400632502',
            '430355562',
            '430355575',
            '430355578',
            '400632592',
            '400632795',
            '400632848',
            '400632930',
            '400632986',
            '400633001',
            '400633230',
            '400633277',
            '400633385',
            '400633496',
            '130115730',
            '400635911',
            '400313255',
            '400636586',
            '400636595',
            '400636603',
            '400636650',
            '400636654',
            '400636670',
            '400636693',
            '400636733',
            '400636781',
            '400636788',
            '400636792',
            '400636841',
            '400636842',
            '400636846',
            '400636930',
            '400636971',
            '400636982',
            '400637037',
            '400637038',
            '130116247',
            '400637079',
            '400637099',
            '400637110',
            '400637124',
            '400637126',
            '400637129',
            '400637173',
            '400637200',
            '400637215',
            '400637220',
            '400637228',
            '130116269',
            '400637298',
            '400637308',
            '400637310',
            '400637317',
            '400637319',
            '400637331',
            '400637337',
            '400637340',
            '130116280',
            '400637361',
            '400637370',
            '400637372',
            '400637379',
            '400637396',
            '400637406',
            '400637415',
            '400637447',
            '400637458',
            '400637468',
            '400637472',
            '400637480',
            '400637516',
            '130116303',
            '130116305',
            '400637528',
            '400637542',
            '400637560',
            '400637561',
            '130116309',
            '400637570',
            '400637588',
            '400637594',
            '130116317',
            '400637661',
            '400637662',
            '400637671',
            '400637679',
            '130116325',
            '400637706',
            '400637707',
            '400637710',
            '400637718',
            '400637724',
            '400637728',
            '400637731',
            '400637737',
            '400637746',
            '130116337',
            '400637797',
            '400637842',
            '400637846',
            '130116348',
            '400637891',
            '400637909',
            '400251254',
            '400637985',
            '400637991',
            '400637997',
            '400637999',
            '500037500',
            '430355583',
            '430355691',
            '430355776',
            '430355815',
            '430355827',
            '430355840',
            '430355867',
            '430355869',
            '430355877',
            '430355926',
            '430355935',
            '430355945',
            '430355949',
            '430356040',
            '430356096',
            '430356127',
            '430356342',
            '500038982',
            '430356454',
            '500039009',
            '430356567',
            '430356629',
            '430357480',
            '430357711',
            '430357839',
            '430357983',
            '430358031',
            '430358051',
            '430358053',
            '500039310',
            '430358079',
            '430358126',
            '500039335',
            '500039361',
            '430358224',
            '430358246',
            '430358268',
            '430358287',
            '430358310',
            '430358311',
            '430358331',
            '430358345',
            '430358367',
            '430358378',
            '430358380',
            '500039410',
            '500039416',
            '430358442',
            '430358444',
            '430358445',
            '430358454',
            '430358462',
            '430358476',
            '430358483',
            '430358491',
            '500039421',
            '430358517',
            '430358527',
            '430358531',
            '430358543',
            '430358549',
            '430358551',
            '430358553',
            '430358554',
            '430358558',
            '430358566',
            '430358580',
            '430358584',
            '430358623',
            '430358626',
            '430358633',
            '430358640',
            '430358717',
            '100249334',
            '100252385',
            '400619510',
            '130113649',
            '400622383',
            '400622444',
            '400623258',
            '400623629',
            '100256646',
            '400624942',
            '400626251',
            '400627586',
            '100257822',
            '130114842',
            '600154759',
            '400628804',
            '600154840',
            '100258492',
            '100258867',
            '400631913',
            '100259368',
            '400631992',
            '400632064',
            '100259406',
            '530019088',
            '100259461',
            '400632246',
            '100259512',
            '100259520',
            '100259541',
            '100259565',
            '100259595',
            '530019133',
            '530019138',
            '100259691',
            '530019145',
            '100259716',
            '530019155',
            '400632815',
            '600155286',
            '400632831',
            '400632896',
            '130115546',
            '400633006',
            '400633116',
            '400633124',
            '130115573',
            '100259851',
            '100259857',
            '400633202',
            '400633209',
            '400633329',
            '400633430',
            '530019220',
            '400633495',
            '530019225',
            '400633516',
            '130115633',
            '100259977',
            '400633576',
            '400633597',
            '400633610',
            '400633616',
            '130115658',
            '130115664',
            '400633703',
            '100260074',
            '100260099',
            '130115693',
            '100260119',
            '400633828',
            '400633979',
            '100260221',
            '100260233',
            '400634094',
            '400634126',
            '400634171',
            '400634274',
            '100260318',
            '530019299',
            '130115786',
            '530019312',
            '400634488',
            '400634619',
            '400634670',
            '400635558',
            '400636011',
            '100260897',
            '400636540',
            '100261048',
            '400636669',
            '400636746',
            '400636825',
            '530019555',
            '100261117',
            '100261118',
            '400636986',
            '400637013',
            '400637020',
            '400637063',
            '400637149',
            '400637217',
            '600155863',
            '400370443',
            '600155869',
            '400637287',
            '400637301',
            '130116273',
            '400637312',
            '600155874',
            '400637341',
            '600155878',
            '100261214',
            '400637371',
            '400637374',
            '400637397',
            '400637400',
            '400637408',
            '100261229',
            '530019587',
            '600155891',
            '400637432',
            '400637449',
            '400637459',
            '400637491',
            '530019598',
            '100261252',
            '400637519',
            '400637566',
            '400637567',
            '600155921',
            '130116315',
            '100261285',
            '130116319',
            '600155926',
            '400637646',
            '400637663',
            '400637680',
            '400637686',
            '400637695',
            '100261310',
            '400637696',
            '400637698',
            '400637704',
            '400637715',
            '400637721',
            '400637727',
            '400637730',
            '400637738',
            '400637742',
            '400637757',
            '500039428',
            '500039430',
            '600155944',
            '400637778',
            '130116339',
            '400637780',
            '530019628',
            '100261330',
            '400637787',
            '530019630',
            '130116345',
            '400637833',
            '400637835',
            '400637844',
            '100261347',
            '400637859',
            '400637882',
            '100261358',
            '400637884',
            '400637885',
            '400637893',
            '400637907',
            '100261371',
            '100261372',
            '400637910',
            '400264047',
            '130116356',
            '400637924',
            '400637938',
            '400637939',
            '400637948',
            '100261391',
            '400637972',
            '130116360',
            '400638001',
            '400638003',
            '400638021',
            '100261418',
            '400638082',
            '400638091',
            '400638109',
            '400638122',
            '400638161',
            '400638176',
            '100261458',
            '130116406',
            '130116410',
            '100261522',
            '130116435',
            '130116439',
            '100261614',
            '430350272',
            '430351761',
            '430354262',
            '430355346',
            '430355698',
            '430355931',
            '430356007',
            '430356104',
            '430356213',
            '430356223',
            '430356230',
            '430356260',
            '430356284',
            '400638196',
            '400638204',
            '400638219',
            '400638408',
            '400638413',
            '400638526',
            '500014245',
            '500037968',
            '100257886',
            '100257900',
            '100258858',
            '100258887',
            '100259020',
            '100259059',
            '100259164',
            '100259204',
            '100259215',
            '100259280',
            '100259289',
            '100259400',
            '530019093',
            '500038784',
            '530019237',
            '430356352',
            '500038969',
            '530019264',
            '500038970',
            '500038992',
            '500038993',
            '430356527',
            '500039002',
            '430356536',
            '430356569',
            '430356573',
            '530019310',
            '430356604',
            '430356636',
            '100260558',
            '100260632',
            '100260711',
            '100260717',
            '100260721',
            '100260733',
            '100260741',
            '100260742',
            '100260743',
            '100260761',
            '100260769',
            '100260772',
            '100260781',
            '100260783',
            '100260786',
            '100260809',
            '100260817',
            '100260826',
            '100260842',
            '100260867',
            '500039260',
            '430357917',
            '430358006',
            '530019538',
            '500039313',
            '100177915',
            '430358117',
            '430358130',
            '430358131',
            '500039347',
            '430358262',
            '500039385',
            '430358320',
            '430358379',
            '530019588',
            '530019589',
            '500039403',
            '530019595',
            '530019600',
            '430358425',
            '430358432',
            '430358434',
            '430358446',
            '530019609',
            '430358458',
            '430358464',
            '430358477',
            '430358480',
            '430358481',
            '430358492',
            '430358505',
            '430358508',
            '430358509',
            '430358518',
            '500039434',
            '500039439',
            '430358565',
            '430358567',
            '430358572',
            '500039442',
            '530019634',
            '430358583',
            '430358591',
            '430358597',
            '430358604',
            '500039448',
            '430358628',
            '430358630',
            '430358645',
            '530019642',
            '430358670',
            '430358690',
            '430358713',
            '430358742',
            '430358756',
            '430358758',
            '430358781',
            '430358784',
            '430358820',
            '430358821',
            '430358877',
            '530019709',
            '130113951',
            '400626153',
            '400626912',
            '400627135',
            '400627776',
            '400628443',
            '130115070',
            '400630239',
            '400630911',
            '400631240',
            '400631315',
            '400631545',
            '400631621',
            '130115350',
            '130115381',
            '400631969',
            '400631979',
            '400631982',
            '400632034',
            '400632063',
            '130115413',
            '400632300',
            '130115469',
            '400632460',
            '400632657',
            '400633080',
            '130115950',
            '130115984',
            '130115999',
            '130116031',
            '130116073',
            '130116092',
            '400362574',
            '400291108',
            '100260890',
            '130116131',
            '100260926',
            '100260935',
            '130116158',
            '100260937',
            '100260947',
            '100261084',
            '100261132',
            '100254170',
            '500037133',
            '100255602',
            '430351481',
            '100256842',
            '100256956',
            '530018436',
            '500038028',
            '430352643',
            '100257524',
            '430353410',
            '500038387',
            '100258359',
            '100258613',
            '100258819',
            '100258859',
            '100258923',
            '100258984',
            '100259012',
            '100259043',
            '430354972',
            '430354979',
            '100259154',
            '530018996',
            '430355109',
            '100259244',
            '500038704',
            '100259291',
            '430355244',
            '430355349',
            '530019070',
            '430355373',
            '100259432',
            '500038764',
            '430355400',
            '430355439',
            '100259528',
            '100259584',
            '430355759',
            '400634052',
            '430356593',
            '400634664',
            '400634704',
            '400634832',
            '400634911',
            '400635134',
            '400635214',
            '400635289',
            '400635290',
            '430357221',
            '400635317',
            '500039154',
            '400635345',
            '400635347',
            '400635351',
            '400635441',
            '500039174',
            '530019436',
            '400635503',
            '400635514',
            '400635533',
            '400635534',
            '500039185',
            '400635570',
            '400635572',
            '400635579',
            '400635581',
            '400635585',
            '400635589',
            '430357401',
            '530019451',
            '400635617',
            '400635627',
            '400635630',
            '430357416',
            '400635646',
            '430357427',
            '430357428',
            '430357429',
            '400635648',
            '400635649',
            '400635668',
            '400635669',
            '430357450',
            '400635691',
            '400635698',
            '400635704',
            '400635717',
            '400635722',
            '400635726',
            '430357509',
            '400635740',
            '430357517',
            '400635752',
            '400635775',
            '430357540',
            '400635783',
            '500039210',
            '500039213',
            '400635793',
            '400635819',
            '430357583',
            '430357612',
            '430357615',
            '400635891',
            '400635894',
            '430357631',
            '430357645',
            '400635941',
            '500039228',
            '530019471',
            '400635949',
            '430357658',
            '400635953',
            '430357670',
            '430357673',
            '530019476',
            '530019477',
            '500039244',
            '400636027',
            '400636037',
            '400636039',
            '400636041',
            '430357714',
            '400636077',
            '400636113',
            '500039256',
            '400636135',
            '430357778',
            '400636154',
            '530019489',
            '400636158',
            '430357816',
            '400636201',
            '530019494',
            '400636226',
            '400636227',
            '400636228',
            '400636309',
            '500039289',
            '400636734',
            '400636735',
            '400636770',
            '430358088',
            '400636794',
            '400636810',
            '400636829',
            '400636904',
            '430358149',
            '400636937',
            '430358156',
            '430358163',
            '400637017',
            '130112309',
            '100259633',
            '100259670',
            '100259683',
            '100259744',
            '100259754',
            '100259811',
            '100259821',
            '100259842',
            '100259862',
            '100259865',
            '100260396',
            '100260771',
            '100260868',
            '100260936',
            '100260958',
            '100260962',
            '100260964',
            '100260968',
            '100260977',
            '100261049',
            '100261095',
            '100258096',
            '130113640',
            '130114216',
            '130114263',
            '130114448',
            '130114684',
            '130114897',
            '130114996',
            '130115175',
            '130115282',
            '130115286',
            '130115367',
            '130115428',
            '130115474',
            '130115492',
            '130115519',
            '130115577',
            '130115973',
            '300051542',
            '130116049',
            '130116052',
            '130116070',
            '130116095',
            '130116098',
            '130116106',
            '130116149',
            '130116153',
            '130116162',
            '130116168',
            '130116176',
            '130116179',
            '130116181',
            '130116186',
            '130116190',
            '130116201',
            '130116220',
            '130116222',
            '130116260',
            '130116270',
            '400618900',
            '400619296',
            '400622647',
            '400623231',
            '400624414',
            '400626176',
            '400626245',
            '400626248',
            '400627836',
            '400627901',
            '400628167',
            '400628316',
            '400629550',
            '400631096',
            '400631158',
            '400631455',
            '400631756',
            '400631816',
            '400631847',
            '400631894',
            '400632021',
            '400632117',
            '400632175',
            '400632209',
            '400632316',
            '400632322',
            '400632559',
            '400632642',
            '400632702',
            '400632719',
            '400632764',
            '400632787',
            '400632788',
            '300051562',
            '400370966',
            '400414362',
            '400632853',
            '400632869',
            '400632893',
            '400632974',
            '400633051',
            '400633114',
            '400633332',
            '400633447',
            '400634455',
            '400635122',
            '400635380',
            '400635399',
            '400635414',
            '400635439',
            '400635463',
            '400635478',
            '400635486',
            '400635513',
            '400635706',
            '400635713',
            '400635756',
            '400635846',
            '400635870',
            '400635873',
            '400635925',
            '400635965',
            '400635978',
            '400636034',
            '400636071',
            '400636074',
            '400636089',
            '400636091',
            '400636093',
            '400636118',
            '400636129',
            '400636165',
            '400636259',
            '400636265',
            '400636273',
            '400636275',
            '400636276',
            '400636287',
            '400636294',
            '400636298',
            '400636323',
            '400636326',
            '400636332',
            '400636338',
            '400636346',
            '400636347',
            '400636362',
            '400636367',
            '400636374',
            '400636386',
            '400636388',
            '400636392',
            '400636409',
            '400636425',
            '400636442',
            '400636445',
            '400636453',
            '430330416',
            '400589730',
            '530017551',
            '430349184',
            '430349653',
            '500037480',
            '430352158',
            '430352495',
            '400626746',
            '400626895',
            '500038253',
            '400628467',
            '430353635',
            '530018708',
            '430354130',
            '400629683',
            '430354299',
            '400630163',
            '430354500',
            '500038570',
            '430354670',
            '400630660',
            '600155015',
            '130115216',
            '100258898',
            '130115263',
            '430354859',
            '430354875',
            '430354876',
            '430354902',
            '430354931',
            '130115284',
            '500038662',
            '100259174',
            '430355069',
            '400631457',
            '430355101',
            '130115324',
            '400631548',
            '430355118',
            '430355122',
            '430355151',
            '430355155',
            '430355208',
            '400631800',
            '130115374',
            '530019043',
            '430355289',
            '130115385',
            '430355317',
            '600155189',
            '400632056',
            '430355360',
            '430355361',
            '430355366',
            '430355379',
            '400632132',
            '430355410',
            '430355437',
            '430355449',
            '130115448',
            '430355497',
            '430355499',
            '100259580',
            '430355530',
            '530019113',
            '430355543',
            '130115490',
            '400632583',
            '100259665',
            '500038816',
            '430355619',
            '430355620',
            '430355637',
            '430355639',
            '430355642',
            '130115510',
            '130115511',
            '430355656',
            '100259719',
            '430355701',
            '430355712',
            '430355714',
            '430355847',
            '430355875',
            '530019189',
            '430355958',
            '130115677',
            '430356484',
            '430356595',
            '430356697',
            '430356800',
            '600155536',
            '600155545',
            '430357097',
            '400635170',
            '430357205',
            '400635301',
            '530019413',
            '530019414',
            '400635338',
            '400635411',
            '400635454',
            '600155617',
            '530019433',
            '500039177',
            '400635505',
            '430357351',
            '530019443',
            '430357356',
            '430357357',
            '430357369',
            '430357384',
            '600155625',
            '600155623',
            '530019450',
            '430357411',
            '430357414',
            '400635629',
            '430357426',
            '430357437',
            '430357457',
            '400635688',
            '430357465',
            '430357470',
            '400635696',
            '430357475',
            '430357476',
            '430357477',
            '430357495',
            '400635720',
            '430357497',
            '400635723',
            '500039202',
            '600155643',
            '500039205',
            '600155646',
            '500039207',
            '130116085',
            '430357532',
            '430357533',
            '430357543',
            '430357548',
            '430357549',
            '430357555',
            '430357558',
            '600155658',
            '600155665',
            '400635813',
            '430357589',
            '600155677',
            '430357623',
            '600155684',
            '600155685',
            '430357630',
            '500039224',
            '530019468',
            '100260872',
            '530019469',
            '400635938',
            '430357646',
            '130116119',
            '430357654',
            '430357662',
            '430357663',
            '400635960',
            '430357666',
            '500039230',
            '430357676',
            '500039231',
            '400635972',
            '430357678',
            '430357682',
            '430357685',
            '130116127',
            '400635990',
            '430357691',
            '130116129',
            '430357695',
            '100260888',
            '400636006',
            '400636016',
            '430357703',
            '430357705',
            '600155700',
            '430357710',
            '400636033',
            '430357716',
            '600155710',
            '430357719',
            '100260907',
            '430357722',
            '130116135',
            '430357725',
            '430357726',
            '430357729',
            '130116136',
            '430357730',
            '430357731',
            '500039253',
            '430357734',
            '430357740',
            '430357742',
            '430357743',
            '400636101',
            '430357751',
            '430357752',
            '430357760',
            '400636116',
            '600155719',
            '430357767',
            '430357773',
            '430357779',
            '430357780',
            '430357791',
            '430357792',
            '130116151',
            '430357799',
            '430357802',
            '430357812',
            '400636185',
            '430357813',
            '500039261',
            '430357829',
            '500039262',
            '430357840',
            '430357841',
            '400636230',
            '130116171',
            '430357850',
            '430357852',
            '430357863',
            '500039266',
            '430357869',
            '530019503',
            '130116173',
            '400636284',
            '430357887',
            '430357889',
            '430357892',
            '130116175',
            '430357894',
            '500039273',
            '430357909',
            '100260984',
            '430357918',
            '430357927',
            '400636394',
            '430357934',
            '430357935',
            '430357937',
            '430357939',
            '430357942',
            '430357944',
            '530019516',
            '600155756',
            '400636464',
            '430357956',
            '400636467',
            '430357958',
            '400636469',
            '400636471',
            '500039287',
            '130116197',
            '500039288',
            '400636505',
            '430357971',
            '500039292',
            '600155764',
            '400636562',
            '400636591',
            '430358016',
            '400636637',
            '400636644',
            '430358054',
            '400636681'];
        $model=new OrderProcess();
        foreach ($arr as $value){
            $dataOrder=$model->where(array('increment_id'=>$value))->find();

            $order_id = $dataOrder['entity_id']; //订单id
            $order_number = $dataOrder['increment_id']; //订单号
            $site =$dataOrder['site']; //站点
            $title = "USPS"; //运营商
            $shipment_data_type = "加诺"; //渠道名称
            $track_number =$dataOrder['track_number']; //快递单号


            //查询节点主表记录
            $row = (new OrderNode())->where(['order_number' => $order_number])->find();
            if (!$row) {
                $this->error(__('订单记录不存在'), [], 400);
            }
            if ($row['track_number']==$track_number){
                continue;
            }
            //如果已发货 则不再更新发货时间
            //更新节点主表
            $row->allowField(true)->save([
                'order_node'         => 2,
                'node_type'          => 7,
                'update_time'        => date('Y-m-d H:i:s'),
                'shipment_type'      => $title,
                'shipment_data_type' => $shipment_data_type,
                'track_number'       => $track_number,
                'delivery_time'      => date('Y-m-d H:i:s'),
            ]);

            //更新order_node表中es数据
            $arr = [
                'id'                 => $row['id'],
                'order_node'         => 2,
                'node_type'          => 7,
                'shipment_type'      => $title,
                'shipment_data_type' => $shipment_data_type,
                'track_number'       => $track_number,
                'delivery_time'      => time(),
            ];
            $this->asyncEs->updateEsById('mojing_track', $arr);


            //插入节点子表
            (new OrderNodeDetail())->allowField(true)->save([
                'order_number'       => $order_number,
                'order_id'           => $order_id,
                'content'            => 'Order leave warehouse, waiting for being picked up.',
                'site'               => $site,
                'create_time'        => date('Y-m-d H:i:s'),
                'order_node'         => 2,
                'node_type'          => 7,
                'shipment_type'      => $title,
                'shipment_data_type' => $shipment_data_type,
                'track_number'       => $track_number,
            ]);


        }
        //校验参数

    }

    public function deal_track05()
    {
        $arr=[
            '400636696',
            '400636706',
            '430358065',
            '400636724',
            '400636777',
            '430358086',
            '400636801',
            '400636808',
            '430358105',
            '400636828',
            '400636845',
            '400636861',
            '400636872',
            '400636881',
            '400636898',
            '600155809',
            '400636900',
            '400636907',
            '600155817',
            '430358158',
            '400636959',
            '400636965',
            '430358160',
            '100261120',
            '600155829',
            '130116243',
            '400637023',
            '430358208',
            '500039359',
            '430358230',
            '400637104',
            '400637112',
            '500039367',
            '400637172',
            '400637178',
            '400637181',
            '400637195',
            '430358279',
            '400637251',
            '430358296',
            '430358305',
            '430358309',
            '430358329',
            '430358333',
            '430358335',
            '430358339',
            '430358340',
            '430358350',
            '130116285',
            '400637512',
            '430335072',
            '430336756',
            '130110975',
            '430342115',
            '430346133',
            '430349646',
            '130105830',
            '100245898',
            '430338958',
            '430339191',
            '400619501',
            '500037321',
            '430349559',
            '400620974',
            '430350342',
            '430350666',
            '430350997',
            '600154207',
            '300051123',
            '600154301',
            '100256830',
            '400624944',
            '600154358',
            '100256871',
            '100256876',
            '300051186',
            '100256877',
            '400625038',
            '600154371',
            '100256901',
            '130114347',
            '400625112',
            '100256963',
            '400625233',
            '600154403',
            '430352074',
            '600154422',
            '400625440',
            '400625495',
            '500037943',
            '400625648',
            '430352198',
            '500037988',
            '300051218',
            '300051224',
            '430352462',
            '400626120',
            '130114645',
            '430353094',
            '430353166',
            '130114739',
            '400628178',
            '430353412',
            '400628326',
            '430194125',
            '400628718',
            '100258063',
            '430353759',
            '400628896',
            '400629053',
            '430353863',
            '400629105',
            '600154819',
            '400629203',
            '100258201',
            '400629325',
            '400629334',
            '600154851',
            '400629373',
            '100258249',
            '400629447',
            '400629459',
            '400629468',
            '430354079',
            '400629489',
            '300051316',
            '430354104',
            '400629533',
            '400629546',
            '600154886',
            '430354115',
            '400629577',
            '130115016',
            '530018800',
            '100258328',
            '400629605',
            '500038476',
            '130115025',
            '100258360',
            '400629650',
            '100258395',
            '400629739',
            '600154897',
            '400629775',
            '400629776',
            '430354212',
            '130115066',
            '400629869',
            '400629912',
            '430354269',
            '430354279',
            '400629954',
            '100258551',
            '530018832',
            '130115096',
            '400629983',
            '500038519',
            '600154923',
            '600154924',
            '400630049',
            '600154922',
            '400630083',
            '430354354',
            '400630099',
            '400630149',
            '400630188',
            '400630199',
            '600154953',
            '130115134',
            '100258660',
            '130115140',
            '430354439',
            '430354466',
            '430354476',
            '430354489',
            '130115151',
            '530018876',
            '400630386',
            '400630458',
            '600154990',
            '400630496',
            '600154993',
            '400630569',
            '400630577',
            '400621549',
            '430350979',
            '430351081',
            '400623839',
            '130114113',
            '130114174',
            '100256626',
            '500037854',
            '530018295',
            '130114300',
            '130114308',
            '400625007',
            '430351948',
            '430351992',
            '130114415',
            '430352148',
            '130114440',
            '530018376',
            '430352323',
            '400625890',
            '530018405',
            '400625941',
            '400625981',
            '130114510',
            '530018422',
            '400626051',
            '100257235',
            '430352452',
            '130114516',
            '100257283',
            '500038019',
            '130114536',
            '430352529',
            '130114560',
            '400626479',
            '130114605',
            '100257465',
            '430352656',
            '130114609',
            '430352666',
            '400626646',
            '400626739',
            '430352729',
            '100257579',
            '430352786',
            '430352787',
            '100257587',
            '100257605',
            '430352880',
            '130114670',
            '100257655',
            '430352926',
            '430352930',
            '100257728',
            '500038292',
            '430354025',
            '400629461',
            '400629524',
            '430354113',
            '400629588',
            '100258323',
            '130115021',
            '430354150',
            '130115028',
            '130115029',
            '400629657',
            '400629660',
            '400629678',
            '430354168',
            '400629715',
            '400629717',
            '130115040',
            '430354196',
            '400629773',
            '400629786',
            '100258428',
            '400629813',
            '430354208',
            '400629818',
            '100258445',
            '400629865',
            '430354236',
            '400629883',
            '400629910',
            '100258519',
            '100258536',
            '130115092',
            '430354294',
            '400630001',
            '100258576',
            '430354315',
            '100258582',
            '400630017',
            '100258606',
            '400630056',
            '430354346',
            '430354348',
            '400630082',
            '430354365',
            '130115120',
            '430354382',
            '430354390',
            '430354391',
            '430354393',
            '430354399',
            '430354404',
            '430354409',
            '400630227',
            '430354412',
            '530018864',
            '400630235',
            '400630253',
            '500038548',
            '430354444',
            '430354445',
            '430354482',
            '430354492',
            '430354508',
            '430354511',
            '430354532',
            '430354554',
            '430354561',
            '430354580',
            '400630544',
            '400630636',
            '400630677',
            '400630678',
            '530018903',
            '100258845',
            '430354717',
            '430354734',
            '400630823',
            '130115248',
            '400630849',
            '400630884',
            '400631063',
            '400538809'];
        $model=new OrderProcess();
        foreach ($arr as $value){
            $dataOrder=$model->where(array('increment_id'=>$value))->find();

            $order_id = $dataOrder['entity_id']; //订单id
            $order_number = $dataOrder['increment_id']; //订单号
            $site =$dataOrder['site']; //站点
            $title = "USPS"; //运营商
            $shipment_data_type = "加诺"; //渠道名称
            $track_number =$dataOrder['track_number']; //快递单号


            //查询节点主表记录
            $row = (new OrderNode())->where(['order_number' => $order_number])->find();
            if (!$row) {
                $this->error(__('订单记录不存在'), [], 400);
            }
            if ($row['track_number']==$track_number){
                continue;
            }
            //如果已发货 则不再更新发货时间
            //更新节点主表
            $row->allowField(true)->save([
                'order_node'         => 2,
                'node_type'          => 7,
                'update_time'        => date('Y-m-d H:i:s'),
                'shipment_type'      => $title,
                'shipment_data_type' => $shipment_data_type,
                'track_number'       => $track_number,
                'delivery_time'      => date('Y-m-d H:i:s'),
            ]);

            //更新order_node表中es数据
            $arr = [
                'id'                 => $row['id'],
                'order_node'         => 2,
                'node_type'          => 7,
                'shipment_type'      => $title,
                'shipment_data_type' => $shipment_data_type,
                'track_number'       => $track_number,
                'delivery_time'      => time(),
            ];
            $this->asyncEs->updateEsById('mojing_track', $arr);


            //插入节点子表
            (new OrderNodeDetail())->allowField(true)->save([
                'order_number'       => $order_number,
                'order_id'           => $order_id,
                'content'            => 'Order leave warehouse, waiting for being picked up.',
                'site'               => $site,
                'create_time'        => date('Y-m-d H:i:s'),
                'order_node'         => 2,
                'node_type'          => 7,
                'shipment_type'      => $title,
                'shipment_data_type' => $shipment_data_type,
                'track_number'       => $track_number,
            ]);
        }
        //校验参数

    }



}
