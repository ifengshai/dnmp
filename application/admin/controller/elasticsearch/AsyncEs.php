<?php
/**
 * Class AsyncEs.php
 * @package application\admin\controller\elasticsearch
 * @author  crasphb
 * @date    2021/4/1 14:50
 */

namespace app\admin\controller\elasticsearch;

use app\admin\model\operatedatacenter\DatacenterDay;
use app\admin\model\order\order\NewOrder;
use app\admin\model\OrderNode;
use app\admin\model\web\WebShoppingCart;
use app\admin\model\web\WebUsers;
use think\Db;
use think\Debug;

class AsyncEs extends BaseElasticsearch
{

    /**
     * 同步订单数据
     *
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * @author crasphb
     * @date   2021/4/1 15:21
     */
    public function asyncOrder()
    {
        Debug::remark('begin');
        NewOrder::chunk(3000, function ($newOrder) {
            $data = array_map(function ($value) {
                $value = array_map(function ($v) {
                    return $v === null ? 0 : $v;
                }, $value);

                //nihao站的终端转换
                if ($value['site'] == 3 && $value['store_id'] == 2) {
                    $value['store_id'] = 4;
                }
                $value['shipping_method_type'] = 0;
                //运输类型添加
                if (in_array($value['shipping_method'], ['freeshipping_freeshipping', 'flatrate_flatrate'])) {
                    if ($value['base_shipping_amount'] == 0) {
                        $value['shipping_method_type'] = 0;
                    }
                    if ($value['base_shipping_amount'] > 0) {
                        $value['shipping_method_type'] = 1;
                    }
                }
                if (in_array($value['shipping_method'], ['tablerate_bestway'])) {
                    if ($value['base_shipping_amount'] == 0) {
                        $value['shipping_method_type'] = 2;
                    }
                    if ($value['base_shipping_amount'] > 0) {
                        $value['shipping_method_type'] = 3;
                    }
                }
                $mergeData = $value['payment_time'] >= $value['created_at'] ? $value['payment_time'] : $value['created_at'];
                $value['payment_time'] = $mergeData + 8 * 3600;
                $value['created_at'] = $value['created_at'] + 8 * 3600;
                $value['updated_at'] = $value['updated_at'] + 8 * 3600;
                $value['payment_time'] = $value['payment_time'] >= $value['created_at'] ? $value['payment_time'] + 8 * 3600 : $value['created_at'];
                //删除无用字段
                foreach ($value as $key => $val) {
                    if (!in_array($key, ['id', 'site', 'customer_id', 'increment_id', 'quote_id', 'status', 'store_id', 'base_grand_total', 'total_qty_ordered', 'order_type', 'order_prescription_type', 'shipping_method', 'shipping_title', 'shipping_method_type', 'country_id', 'region', 'region_id', 'payment_method', 'mw_rewardpoint_discount', 'mw_rewardpoint', 'base_shipping_amount', 'payment_time'])) {
                        unset($value[$key]);
                    }
                }
                echo $value['id'] . PHP_EOL;

                return $this->formatDate($value, $mergeData);
            }, collection($newOrder)->toArray());
            $this->esService->addMutilToEs('mojing_order', $data);
        }, 'id', 'desc');
        Debug::remark('end');
        echo Debug::getRangeTime('begin', 'end') . 's';
    }

    public function asyncOrderDeFr()
    {
        $orders = NewOrder::where('site', 'in', '10,15')->where('created_at', '>', '1621569600')->order('id', 'desc')->select();
        $datas = [];
        foreach ($orders as $order) {
            $value = array_map(function ($v) {
                return $v === null ? 0 : $v;
            }, $order->toArray());
            //nihao站的终端转换
            if ($value['site'] == 3 && $value['store_id'] == 2) {
                $value['store_id'] = 4;
            }
            $value['shipping_method_type'] = 0;
            //运输类型添加
            if (in_array($value['shipping_method'], ['freeshipping_freeshipping', 'flatrate_flatrate'])) {
                if ($value['base_shipping_amount'] == 0) {
                    $value['shipping_method_type'] = 0;
                }
                if ($value['base_shipping_amount'] > 0) {
                    $value['shipping_method_type'] = 1;
                }
            }
            if (in_array($value['shipping_method'], ['tablerate_bestway'])) {
                if ($value['base_shipping_amount'] == 0) {
                    $value['shipping_method_type'] = 2;
                }
                if ($value['base_shipping_amount'] > 0) {
                    $value['shipping_method_type'] = 3;
                }
            }
            $mergeData = $value['payment_time'] >= $value['created_at'] ? $value['payment_time'] : $value['created_at'];
            $value['payment_time'] = $mergeData + 8 * 3600;
            $value['created_at'] = $value['created_at'] + 8 * 3600;
            $value['updated_at'] = $value['updated_at'] + 8 * 3600;
            $value['payment_time'] = $value['payment_time'] >= $value['created_at'] ? $value['payment_time'] + 8 * 3600 : $value['created_at'];
            //删除无用字段
            foreach ($value as $key => $val) {
                if (!in_array($key, ['id', 'site', 'customer_id', 'increment_id', 'quote_id', 'status', 'store_id', 'base_grand_total', 'total_qty_ordered', 'order_type', 'order_prescription_type', 'shipping_method', 'shipping_title', 'shipping_method_type', 'country_id', 'region', 'region_id', 'payment_method', 'mw_rewardpoint_discount', 'mw_rewardpoint', 'base_shipping_amount', 'payment_time'])) {
                    unset($value[$key]);
                }
            }
            $datas[] = $this->formatDate($value, $mergeData);

        }
        dump($this->esService->addMutilToEs('mojing_order', $datas));
    }

    /**
     * 同步每日center的数据到es
     * @author crasphb
     * @date   2021/4/13 14:58
     */
    public function asyncDatacenterDay()
    {
        DatacenterDay::chunk(10000, function ($newOrder) {
            $data = array_map(function ($value) {
                $value = array_map(function ($v) {
                    return $v === null ? 0 : $v;
                }, $value);

                $mergeData = strtotime($value['day_date']) + 8 * 3600;

                return $this->formatDate($value, $mergeData);
            }, collection($newOrder)->toArray());
            $this->esService->addMutilToEs('mojing_datacenterday', $data);
        });

    }

    /**
     * 同步购物车
     * @throws \think\Exception
     * @author crasphb
     * @date   2021/4/21 10:24
     */
    public function asyncCartMagento()
    {
        $i = 0;
        Db::connect('database.db_zeelool')->table('sales_flat_quote')->field('entity_id,is_active,base_grand_total,updated_at,created_at')->where('created_at', '<=', '2021-05-25 00:00:00')->chunk(10000, function ($carts) use (&$i) {
            $data = array_map(function ($value) use ($i) {
                $value = array_map(function ($v) {
                    return $v === null ? 0 : $v;
                }, $value);
                dump($value);
                die;
                $mergeData = strtotime($value['created_at']);
                $insertData = [
                    'entity_id'        => $value['entity_id'],
                    'site'             => 1,
                    'status'           => $value['is_active'],
                    'base_grand_total' => $value['base_grand_total'],
                    'update_time_day'  => date('Ymd', strtotime($value['updated_at'])),
                    'update_time'      => strtotime($value['updated_at']),
                    'create_time'      => $mergeData,
                ];

                return $this->formatDate($insertData, $mergeData);
            }, collection($carts)->toArray());
            $this->esService->addMutilToEs('mojing_cart', $data);
        }, 'entity_id', 'desc');
    }

    /**
     * 同步用户数据
     * @throws \think\Exception
     * @author crasphb
     * @date   2021/4/21 16:06
     */
    public function asyncCustomerMagento()
    {
        $i = 0;
        Db::connect('database.db_zeelool_de')->table('customer_entity')->chunk(10000, function ($users) use (&$i) {
            $data = array_map(function ($value) use (&$i) {
                $value = array_map(function ($v) {
                    return $v === null ? 0 : $v;
                }, $value);
                $mergeData = strtotime($value['created_at']);
                $insertData = [
                    'id'              => intval(10 . rand(1000000, 9999999)),
                    'site'            => 10,
                    'email'           => $value['email'],
                    'update_time_day' => date('Ymd', strtotime($value['updated_at'])),
                    'update_time'     => strtotime($value['updated_at']),
                    'create_time'     => $mergeData,
                    'is_vip'          => $value['is_vip'] ?? 0,
                    'group_id'        => $value['group_id'],
                    'store_id'        => $value['store_id'],
                    'resouce'         => $value['resouce'] ?? 0,

                ];
                $i++;
                echo $i . PHP_EOL;

                return $this->formatDate($insertData, $mergeData);
            }, collection($users)->toArray());
            $this->esService->addMutilToEs('mojing_customer', $data);
        });
    }

    /**
     * 同步购物车
     * @author crasphb
     * @date   2021/5/10 13:51
     */
    public function asyncCart()
    {
        WebShoppingCart::field('id,site,entity_id,is_active,base_grand_total,updated_at,updated_at,created_at')->chunk(10000, function ($carts) {
            $data = array_map(function ($value) {
                $value = array_map(function ($v) {
                    return $v === null ? 0 : $v;
                }, $value);
                $mergeData = $value['created_at'] + 8 * 3600;
                $insertData = [
                    'id'               => $value['id'],
                    'site'             => $value['site'],
                    'entity_id'        => $value['entity_id'],
                    'status'           => $value['is_active'],
                    'base_grand_total' => $value['base_grand_total'],
                    'update_time_day'  => date('Ymd', $value['updated_at'] + 8 * 3600),
                    'update_time_hour' => date('H', $value['updated_at'] + 8 * 3600),
                    'update_time'      => $value['updated_at'] + 8 * 3600,
                    'create_time'      => $mergeData,

                ];
                echo $value['id'] . PHP_EOL;

                return $this->formatDate($insertData, $mergeData);
            }, collection($carts)->toArray());
            $this->esService->addMutilToEs('mojing_cart', $data);
        }, 'id', 'desc');
    }

    /**
     * 同步用户数据
     * @author crasphb
     * @date   2021/5/10 13:58
     */
    public function asyncCustomer()
    {
        WebUsers::chunk(10000, function ($carts) {
            $data = array_map(function ($value) {
                $value = array_map(function ($v) {
                    return $v === null ? 0 : $v;
                }, $value);
                $mergeData = $value['created_at'] + 8 * 3600;
                $insertData = [
                    'id'              => $value['id'],
                    'site'            => $value['site'],
                    'email'           => $value['email'],
                    'update_time_day' => date('Ymd', $value['updated_at'] + 8 * 3600),
                    'update_time'     => $value['updated_at'] + 8 * 3600,
                    'create_time'     => $mergeData,
                    'is_vip'          => $value['is_vip'] ?? 0,
                    'group_id'        => $value['group_id'],
                    'store_id'        => $value['store_id'],
                    'resouce'         => $value['resouce'] ?? 0,

                ];
                echo $value['id'] . PHP_EOL;

                return $this->formatDate($insertData, $mergeData);
            }, collection($carts)->toArray());
            $this->esService->addMutilToEs('mojing_customer', $data);
        }, 'id', 'desc');
    }

    /**
     * 同步物流数据到es
     * @author mjj
     * @date   2021/4/16 10:57:29
     */
    public function asyncTrack()
    {
        (new OrderNode)->chunk(10000, function ($track) {
            $data = array_map(function ($value) {
                $value = array_map(function ($v) {
                    return $v === null ? 0 : $v;
                }, $value);
                $mergeData = strtotime($value['delivery_time']);
                $delivery_error_flag = strtotime($value['signing_time']) < $mergeData + 172800 ? 1 : 0;
                $insertData = [
                    'id'                  => $value['id'],
                    'order_node'          => $value['order_node'],
                    'node_type'           => $value['node_type'],
                    'site'                => $value['site'],
                    'order_id'            => $value['order_id'],
                    'order_number'        => $value['order_number'],
                    'shipment_type'       => $value['shipment_type'],
                    'shipment_data_type'  => $value['shipment_data_type'],
                    'track_number'        => $value['track_number'],
                    'signing_time'        => $value['signing_time'] ? strtotime($value['signing_time']) : 0,
                    'delivery_time'       => $mergeData,
                    'delivery_error_flag' => $delivery_error_flag,
                    'shipment_last_msg'   => $value['shipment_last_msg'],
                    'delievered_days'     => (strtotime($value['signing_time']) - $mergeData) / 86400,
                    'wait_time'           => abs(strtotime($value['signing_time']) - $mergeData),
                ];

                return $this->formatDate($insertData, $mergeData);
            }, collection($track)->toArray());
            $this->esService->addMutilToEs('mojing_track', $data);
        }, 'id', 'desc');

    }


    /**
     * 同步物流数据到es
     * @author mjj
     * @date   2021/4/16 10:57:29
     */
    public function asyncTrackTest()
    {
        (new OrderNode)->where("shipment_data_type='郭伟峰-2781郑州超级专线-电'")->where("shipment_data_type='郑州超级专线-含电'")->chunk(10000, function ($track) {
            $data = array_map(function ($value) {
                $value = array_map(function ($v) {
                    return $v === null ? 0 : $v;
                }, $value);
                $mergeData = strtotime($value['delivery_time']);
//                $delivery_error_flag = strtotime($value['signing_time']) < $mergeData + 172800 ? 1 : 0;
                $insertData = [
                    'id'                  => $value['id'],
                    'order_node'          => $value['order_node'],
                    'node_type'           => $value['node_type'],
                    'site'                => $value['site'],
                    'order_id'            => $value['order_id'],
                    'order_number'        => $value['order_number'],
                    'shipment_type'       => $value['shipment_type'],
                    'shipment_data_type'  => $value['shipment_data_type'],
                    'track_number'        => $value['track_number'],
                    'signing_time'        => $value['signing_time'] ? strtotime($value['signing_time']) : 0,
                    'delivery_time'       => $mergeData,
                    'delivery_error_flag' => 0,
                    'shipment_last_msg'   => $value['shipment_last_msg'],
                    'delievered_days'     => (strtotime($value['signing_time']) - $mergeData) / 86400,
                    'wait_time'           => abs(strtotime($value['signing_time']) - $mergeData),
                ];

                $this->updateEsById('mojing_track', $insertData);


                return $this->formatDate($insertData, $mergeData);
            }, collection($track)->toArray());

        }, 'id', 'desc');

    }


    /**
     * 批量更新数据
     * @author huangbinbin
     * @date   2021/6/18 18:20
     */
    public function asyncUpdateTrack()
    {
        OrderNode::where('update_time', '>=', '2021-04-15 00:00:00')->chunk(10000, function ($track) {
            $data = array_map(function ($value) {
                $value = array_map(function ($v) {
                    return $v === null ? 0 : $v;
                }, $value);
                $mergeData = strtotime($value['delivery_time']);
                $delivery_error_flag = strtotime($value['signing_time']) < $mergeData + 172800 ? 1 : 0;
                $insertData = [
                    'id'                  => $value['id'],
                    'order_node'          => $value['order_node'],
                    'node_type'           => $value['node_type'],
                    'site'                => $value['site'],
                    'order_id'            => $value['order_id'],
                    'order_number'        => $value['order_number'],
                    'shipment_type'       => $value['shipment_type'],
                    'shipment_data_type'  => $value['shipment_data_type'],
                    'track_number'        => $value['track_number'],
                    'signing_time'        => $value['signing_time'] ? strtotime($value['signing_time']) : 0,
                    'delivery_time'       => $mergeData,
                    'delivery_error_flag' => $delivery_error_flag,
                    'shipment_last_msg'   => $value['shipment_last_msg'],
                    'delievered_days'     => (strtotime($value['signing_time']) - $mergeData) / 86400,
                    'wait_time'           => abs(strtotime($value['signing_time']) - $mergeData),
                ];

                return $this->formatDate($insertData, $mergeData);
            }, collection($track)->toArray());
            print_r($this->esService->updateMutilToEs('mojing_track', $data));
        }, 'id', 'desc');

    }


}