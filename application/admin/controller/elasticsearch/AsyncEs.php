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
use think\Model;

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
        NewOrder::where('created_at', '>', strtotime('2021-09-01 00:00:00'))->chunk(3000, function ($newOrder) {
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
            dump($this->esService->addMutilToEs('mojing_datacenterday', $data));
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
        $site = 10;

        if($site == 1) {
            $db = Db::connect('database.db_zeelool_online');
        }elseif($site == 3){
            $db = Db::connect('database.db_voogueme_online');
        }elseif($site == 10){
            $db = Db::connect('database.db_zeelool_de_online');
        }elseif($site == 11){
            $db = Db::connect('database.db_zeelool_jp_online');
        }elseif($site == 15){
            $db = Db::connect('database.db_zeelool_fr_online');
        }
        $i = 0;
        $db->table('customer_entity')->where("updated_at >= '2021-11-12 10:00:00' and updated_at <= '2021-11-13 08:15:00'")->chunk(10000, function ($users) use ($site, &$i) {
            $data = array_map(function ($value) use ($site, &$i) {
                $value = array_map(function ($v) {
                    return $v === null ? 0 : $v;
                }, $value);
                $id = Db::name('web_users')->insertGetId(
                    [
                        'site' => $site,
                        'entity_id' => $value['entity_id'],
                        'email' => $value['email'],
                        'is_vip' => $value['is_vip'] ?? 0,
                        'group_id'        => $value['group_id'],
                        'store_id'        => $value['store_id'],
                        'resouce'         => $value['resouce'] ?? 0,
                        'created_at'    => strtotime($value['created_at'])+8*3600,
                        'updated_at'    => strtotime($value['updated_at'])+8*3600,
                    ]
                );
                echo $id . PHP_EOL;
                $mergeData = strtotime($value['created_at']);
                $insertData = [
                    'id'              => $id,
                    'site'            => $site,
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
     * 同步Meeloog购物车
     * @author crasphb
     * @date   2021/5/10 13:51
     */
    public function syncMeeloogCart()
    {
        WebShoppingCart::field('id,site,entity_id,is_active,base_grand_total,updated_at,created_at')
            ->where('site', 3)
            ->where('created_at', '>', strtotime('2021-08-01 00:00:00'))
            ->chunk(10000, function ($carts) {
                array_map(function ($value) {
                    $value = array_map(function ($v) {
                        return $v === null ? 0 : $v;
                    }, $value);
                    $mergeData = $value['created_at'];
                    $insertData = [
                        'id' => $value['id'],
                        'site' => $value['site'],
                        'entity_id' => $value['entity_id'],
                        'status' => $value['is_active'],
                        'base_grand_total' => $value['base_grand_total'],
                        'update_time_day' => date('Ymd', $value['updated_at']),
                        'update_time_hour' => date('H', $value['updated_at']),
                        'update_time' => $value['updated_at'],
                        'create_time' => $mergeData,
                    ];

                    $data = $this->formatDate($insertData, $mergeData);
                    $this->updateEsById('mojing_cart', $data);

                    echo $value['id'].PHP_EOL;
                }, collection($carts)->toArray());
            }, 'id', 'desc');
    }

    /**
     * 同步Meeloog更新购物车
     * @author crasphb
     * @date   2021/5/10 13:51
     */
    public function syncMeeloogUpdateCart()
    {
        WebShoppingCart::field('id,site,entity_id,is_active,base_grand_total,updated_at,created_at')
            ->where('site', 3)
            ->where('updated_at', '>', strtotime('2021-08-01 00:00:00'))
            ->chunk(10000, function ($carts) {
                array_map(function ($value) {
                    $value = array_map(function ($v) {
                        return $v === null ? 0 : $v;
                    }, $value);
                    $mergeData = $value['created_at'];
                    $insertData = [
                        'id' => $value['id'],
                        'site' => $value['site'],
                        'entity_id' => $value['entity_id'],
                        'status' => $value['is_active'],
                        'base_grand_total' => $value['base_grand_total'],
                        'update_time_day' => date('Ymd', $value['updated_at']),
                        'update_time_hour' => date('H', $value['updated_at']),
                        'update_time' => $value['updated_at'],
                        'create_time' => $mergeData,
                    ];

                    $data = $this->formatDate($insertData, $mergeData);
                    $this->updateEsById('mojing_cart', $data);

                    echo $value['id'].PHP_EOL;
                }, collection($carts)->toArray());
            }, 'id', 'desc');
    }

    /**
     * 同步购物车
     *
     * @author fangke
     * @date   2021/8/11 4:24 下午
     */
    public function syncCart()
    {
        WebShoppingCart::field('id,site,entity_id,is_active,base_grand_total,updated_at,created_at')
            ->where('created_at', '>', strtotime('2021-09-01 00:00:00'))
            ->chunk(10000, function ($carts) {
                array_map(function ($value) {
                    $value = array_map(function ($v) {
                        return $v === null ? 0 : $v;
                    }, $value);
                    $mergeData = $value['created_at'];
                    $insertData = [
                        'id' => $value['id'],
                        'site' => $value['site'],
                        'entity_id' => $value['entity_id'],
                        'status' => $value['is_active'],
                        'base_grand_total' => $value['base_grand_total'],
                        'update_time_day' => date('Ymd', $value['updated_at']),
                        'update_time_hour' => date('H', $value['updated_at']),
                        'update_time' => $value['updated_at'],
                        'create_time' => $mergeData,
                    ];

                    $data = $this->formatDate($insertData, $mergeData);
                    $this->updateEsById('mojing_cart', $data);

                    echo $value['id'].PHP_EOL;
                }, collection($carts)->toArray());
            }, 'id', 'desc');
    }

    /**
     *
     * @author huangbinbin
     * @date   2021/10/20 11:35
     */
    public function asyncDatacenterDayUpdate()
    {
        DatacenterDay::where('day_date', 'in', ['2021-10-04','2021-10-10'])->where('site',2)->chunk(10000, function ($newOrder) {
             array_map(function ($value) {
                $value = array_map(function ($v) {
                    return $v === null ? 0 : $v;
                }, $value);

                $mergeData = strtotime($value['day_date']) + 8 * 3600;

                $data = $this->formatDate($value, $mergeData);
                $this->updateEsById('mojing_datacenterday', $data);
            }, collection($newOrder)->toArray());
        });

    }

    /**
     * 同步M更新购物车
     *
     * @author fangke
     * @date   2021/8/11 4:24 下午
     */
    public function syncUpdateCart()
    {
        WebShoppingCart::field('id,site,entity_id,is_active,base_grand_total,updated_at,created_at')
            ->where('updated_at', '>', strtotime('2021-08-11 00:00:00'))
            ->chunk(10000, function ($carts) {
                array_map(function ($value) {
                    $value = array_map(function ($v) {
                        return $v === null ? 0 : $v;
                    }, $value);
                    $mergeData = $value['created_at'];
                    $insertData = [
                        'id' => $value['id'],
                        'site' => $value['site'],
                        'entity_id' => $value['entity_id'],
                        'status' => $value['is_active'],
                        'base_grand_total' => $value['base_grand_total'],
                        'update_time_day' => date('Ymd', $value['updated_at']),
                        'update_time_hour' => date('H', $value['updated_at']),
                        'update_time' => $value['updated_at'],
                        'create_time' => $mergeData,
                    ];

                    $data = $this->formatDate($insertData, $mergeData);
                    $this->updateEsById('mojing_cart', $data);

                    echo $value['id'].PHP_EOL;
                }, collection($carts)->toArray());
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
        $dateTime = date('Y-m-d 00:00:00', strtotime('-1 day'));
        (new OrderNode)->where("delivery_time",'>', $dateTime)->chunk(10000, function ($track) {
            $data = array_map(function ($value) {
                $value = array_map(function ($v) {
                    return $v === null ? 0 : $v;
                }, $value);
                $mergeData = strtotime($value['delivery_time']);
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

                echo $value['id'] . "\n";
            }, collection($track)->toArray());

        }, 'id', 'desc');

    }


    /**
     * 同步物流数据到es
     * @author mjj
     * @date   2021/4/16 10:57:29
     */
    public function asyncTrackTest01()
    {
        (new OrderNode)->where("delivery_time>='2021-07-30' and delivery_time<='2021-08-31'")->chunk(10000, function ($track) {
            $data = array_map(function ($value) {
                $value = array_map(function ($v) {
                    return $v === null ? 0 : $v;
                }, $value);
                $mergeData = strtotime($value['delivery_time']);
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

                $res = $this->updateEsById('mojing_track', $insertData);

                echo $value['id'] . "\n";
                dump($res);
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
        (new OrderNode)->where("delivery_time>='2021-07-30' and delivery_time<='2021-08-31'")->chunk(10000, function ($track) {
            $data = array_map(function ($value) {
                $value = array_map(function ($v) {
                    return $v === null ? 0 : $v;
                }, $value);
                $mergeData = strtotime($value['delivery_time']);
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

                return $this->formatDate($insertData, $mergeData);
            }, collection($track)->toArray());
            print_r($this->esService->addMutilToEs('mojing_track', $data));
        }, 'id', 'desc');

    }


}