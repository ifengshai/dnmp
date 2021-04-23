<?php

namespace app\api\controller;

use app\common\controller\Api;
use app\admin\model\OrderNode;
use app\admin\model\OrderNodeDetail;
use app\admin\model\OrderNodeCourier;
use GuzzleHttp\Client;
use think\Db;
use SchGroup\SeventeenTrack\Connectors\TrackingConnector;
use app\admin\model\StockLog;
use app\admin\model\finance\FinanceCost;
use app\admin\controller\elasticsearch\AsyncEs;

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
            'order_id' => $order_id,
            'site' => $site,
            'node_type' => ['>=', 0]
        ])->count();
        if ($order_count <= 0) {
            $res_node = $this->node->allowField(true)->save([
                'order_number' => $order_number,
                'order_id' => $order_id,
                'site' => $site,
                'create_time' => date('Y-m-d H:i:s'),
                'order_node' => 0,
                'node_type' => 0,
                'update_time' => date('Y-m-d H:i:s'),
            ]);
            $insertId = $this->node->getLastInsID();
            $arr = [
                'id'=>$insertId,
                'order_node' => 0,
                'node_type' => 0,
                'site' => $site,
                'order_id' => $order_id,
                'order_number' => $order_number,
                'shipment_type' => '',
                'shipment_data_type' => '',
                'track_number' => '',
                'signing_time' => 0,
                'delivery_time' => 0,
                'delivery_error_flag' => 0,
                'shipment_last_msg' => "",
                'delievered_days' => 0,
                'wait_time' => 0,
            ];
            $data[] = $this->asyncEs->formatDate($arr,time());
            $this->asyncEs->esService->addMutilToEs('mojing_track',$data);
        }

        $count = (new OrderNodeDetail())->where([
            'order_number' => $order_number,
            'order_id' => $order_id,
            'site' => $site,
            'order_node' => 0,
            'node_type' => 0
        ])->count();
        if ($count > 0) {
            $this->error('已存在', [], 400);
        }

        $res_node_detail = (new OrderNodeDetail())->allowField(true)->save([
            'order_number' => $order_number,
            'order_id' => $order_id,
            'content' => 'Your order has been created.',
            'site' => $site,
            'create_time' => date('Y-m-d H:i:s'),
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
        if ($status == 'processing'){
            //判断如果子节点大于等于1时  不更新
            $order_count = (new OrderNode)->where([
                'order_number' => $order_number,
                'order_id' => $order_id,
                'site' => $site,
                'node_type' => ['>=', 1]
            ])->count();
            if ($order_count < 0) {
                $res_node = $this->node->save([
                    'order_node' => 0,
                    'node_type' => 1,
                    'update_time' => date('Y-m-d H:i:s'),
                ], ['order_id' => $order_id, 'site' => $site]);
                //获取主表id
                $id = $this->node
                    ->where(['order_id' => $order_id, 'site' => $site])
                    ->value('id');
                //更新order_node表中es数据
                $arr = [
                    'id' => $id,
                    'node_type' => 1,
                ];
                $this->asyncEs->updateEsById('mojing_track',$arr);
            }

            $count = (new OrderNodeDetail())->where([
                'order_number' => $order_number,
                'order_id' => $order_id,
                'site' => $site,
                'order_node' => 0,
                'node_type' => 1
            ])->count();
            if ($count > 0) {
                $this->error('已存在');
            }

            $res_node_detail = (new OrderNodeDetail())->allowField(true)->save([
                'order_number' => $order_number,
                'order_id' => $order_id,
                'content' => 'Your payment has been successful.',
                'site' => $site,
                'create_time' => date('Y-m-d H:i:s'),
                'order_node' => 0,
                'node_type' => 1
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
        if ($row->order_node >= 2 && $row->node_type >= 7) {
            $this->error(__('订单节点已存在'), [], 400);
        }
       
        //更新节点主表
        $row->allowField(true)->save([
            'order_node' => 2,
            'node_type' => 7,
            'update_time' => date('Y-m-d H:i:s'),
            'shipment_type' => $title,
            'shipment_data_type' => $shipment_data_type,
            'track_number' => $track_number,
            'delivery_time' => date('Y-m-d H:i:s')
        ]);
        //更新order_node表中es数据
        $arr = [
            'id' => $row['id'],
            'order_node' => 2,
            'node_type' => 7,
            'shipment_type' => $title,
            'shipment_data_type' => $shipment_data_type,
            'track_number' => $track_number,
            'delivery_time' => time()
        ];
        $this->asyncEs->updateEsById('mojing_track',$arr);

        //插入节点子表
        (new OrderNodeDetail())->allowField(true)->save([
            'order_number' => $order_number,
            'order_id' => $order_id,
//            'content' => 'Leave warehouse, Waiting for being picked up.',
            'content' => 'Order leave warehouse, waiting for being picked up.',
            'site' => $site,
            'create_time' => date('Y-m-d H:i:s'),
            'order_node' => 2,
            'node_type' => 7,
            'shipment_type' => $title,
            'shipment_data_type' => $shipment_data_type,
            'track_number' => $track_number,
        ]);
        
        file_put_contents('/www/wwwroot/mojing/runtime/log/order_delivery.log', $track_number . '-' . $shipment_data_type . "\r\n", FILE_APPEND);
        //注册17track
        $title = strtolower(str_replace(' ', '-', $title));
        $carrier = $this->getCarrier($title);
        $shipment_reg[0]['number'] =  $track_number;
        $shipment_reg[0]['carrier'] =  $carrier['carrierId'];
        $track = $this->regitster17Track($shipment_reg);

        if (count($track['data']['rejected']) > 0) {
            $this->error('物流接口注册失败！！', [], $track['data']['rejected']['error']['code']);
        }
        $this->success('提交成功', [], 200);
    }

    /**
     * 获取快递号
     * @param $title
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
            $list = $platform->where(['platform_type' => $value['site'], 'platform_sku' => $item['sku']])->find();
            if (!$list) {
                unset($item);
            } else {
                $res = $platform->allowField(true)->isUpdate(true, ['platform_type' => $value['site'], 'platform_sku' => $item['sku']])->save(['outer_sku_status' => $item['status']]);
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
                        'type'                      => 1,
                        'site'                      => $site,
                        'one_type'                  => 1,
                        'sku'                       => $true_sku,
                        'order_number'              => $order_number,
                        'public_id'                 => $orderid,
                        'occupy_stock_change'       => $qty,
                        'available_stock_change'    => -$qty,
                        'create_person'             => 'admin',
                        'create_time'               => date('Y-m-d H:i:s'),
                        'remark'                    => '生成订单扣减可用库存,增加占用库存'
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
                        'type'                      => 1,
                        'site'                      => $site,
                        'one_type'                  => 2,
                        'sku'                       => $true_sku,
                        'order_number'              => $order_number,
                        'public_id'                 => $orderid,
                        'occupy_stock_change'       => $qty,
                        'available_stock_change'    => -$qty,
                        'create_person'             => 'admin',
                        'create_time'               => date('Y-m-d H:i:s'),
                        'remark'                    => '如佛小程序取消订单增加可用库存,扣减占用库存'
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
    public function vip_order_income($work_id = null){
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
}
