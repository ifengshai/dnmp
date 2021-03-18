<?php

namespace app\admin\controller;

use app\common\controller\Backend;
use think\Db;
use FacebookAds\Api;
use FacebookAds\Object\Campaign;
use app\admin\model\financial\Fackbook;
use fast\Excel;

class Wangpenglei extends Backend
{

    protected $noNeedLogin = ['*'];

    public function _initialize()
    {
        parent::_initialize();
        $this->zeelool = new \app\admin\model\order\order\Zeelool();

        $this->facebook = Fackbook::where('platform', 1)->find();
        $this->app_id = $this->facebook->app_id;
        $this->app_secret = $this->facebook->app_secret;
        $this->access_token = $this->facebook->access_token;
        $this->accounts   = $this->facebook->accounts;
    }

    /************************跑库存数据用START*****勿删*****************************/
    //导入实时库存 第一步
    public function set_product_relstock()
    {

        $list = Db::table('fa_zz_temp2')->select();
        foreach ($list as $k => $v) {
            $p_map['sku'] = $v['sku'];
            $data['real_time_qty'] = $v['stock'];
            $res = $this->item->where($p_map)->update($data);
            echo $v['sku'] . "\n";
        }
        echo 'ok';
        die;
    }

    /**
     * 统计配货占用 第二步
     *
     * @Description
     * @author wpl
     * @since 2020/04/11 15:54:25
     * @return void
     */
    public function set_product_process()
    {
        $this->orderitemprocess = new \app\admin\model\order\order\NewOrderItemProcess();
        $this->itemplatformsku = new \app\admin\model\itemmanage\ItemPlatformSku;
        $this->item = new \app\admin\model\itemmanage\Item;
        // $skus = $this->item->where(['is_open' => 1, 'is_del' => 1, 'category_id' => ['<>', 43]])->column('sku');

        $skus = Db::table('fa_zz_temp2')->column('sku');

        foreach ($skus as $k => $v) {
            $map = [];
            $zeelool_sku = $this->itemplatformsku->getWebSku($v, 1);
            $voogueme_sku = $this->itemplatformsku->getWebSku($v, 2);
            $nihao_sku = $this->itemplatformsku->getWebSku($v, 3);
            $wesee_sku = $this->itemplatformsku->getWebSku($v, 5);
            $meeloog_sku = $this->itemplatformsku->getWebSku($v, 4);
            $zeelool_es_sku = $this->itemplatformsku->getWebSku($v, 9);
            $zeelool_de_sku = $this->itemplatformsku->getWebSku($v, 10);
            $zeelool_jp_sku = $this->itemplatformsku->getWebSku($v, 11);
            $voogueme_acc_sku = $this->itemplatformsku->getWebSku($v, 12);
            $skus = [];
            $skus = [
                $zeelool_sku,
                $voogueme_sku,
                $nihao_sku,
                $wesee_sku,
                $meeloog_sku,
                $zeelool_es_sku,
                $zeelool_de_sku,
                $zeelool_jp_sku,
                $voogueme_acc_sku
            ];

            $map['a.sku'] = ['in', array_filter($skus)];
            $map['b.status'] = ['in', ['processing', 'paypal_reversed', 'paypal_canceled_reversal']];
            $map['a.distribution_status'] = ['>', 1]; //大于待打印标签
            $map['c.check_status'] = 0; //未审单计算订单占用
            $map['b.created_at'] = ['between', [strtotime('2020-01-01 00:00:00'), time()]]; //时间节点
            $distribution_occupy_stock = $this->orderitemprocess->alias('a')->where($map)
                ->join(['fa_order' => 'b'], 'a.order_id = b.id')
                ->join(['fa_order_process' => 'c'], 'a.order_id = c.order_id')
                ->count(1);

            $p_map['sku'] = $v;
            $data['distribution_occupy_stock'] = $distribution_occupy_stock;
            $this->item->where($p_map)->update($data);
            echo $v . "\n";
            usleep(20000);
        }
        echo 'ok';
        die;
    }

    /**
     * 订单占用 第三步
     *
     * @Description
     * @author wpl
     * @since 2020/04/11 15:54:25
     * @return void
     */
    public function set_product_process_order()
    {
        $this->orderitemprocess = new \app\admin\model\order\order\NewOrderItemProcess();
        $this->itemplatformsku = new \app\admin\model\itemmanage\ItemPlatformSku;
        $this->item = new \app\admin\model\itemmanage\Item;
        // $skus = $this->item->where(['is_open' => 1, 'is_del' => 1, 'category_id' => ['<>', 43]])->column('sku');

        $skus = Db::table('fa_zz_temp2')->column('sku');
        foreach ($skus as $k => $v) {
            $map = [];
            $zeelool_sku = $this->itemplatformsku->getWebSku($v, 1);
            $voogueme_sku = $this->itemplatformsku->getWebSku($v, 2);
            $nihao_sku = $this->itemplatformsku->getWebSku($v, 3);
            $wesee_sku = $this->itemplatformsku->getWebSku($v, 5);
            $meeloog_sku = $this->itemplatformsku->getWebSku($v, 4);
            $zeelool_es_sku = $this->itemplatformsku->getWebSku($v, 9);
            $zeelool_de_sku = $this->itemplatformsku->getWebSku($v, 10);
            $zeelool_jp_sku = $this->itemplatformsku->getWebSku($v, 11);
            $voogueme_acc_sku = $this->itemplatformsku->getWebSku($v, 12);
            $skus = [];
            $skus = [
                $zeelool_sku,
                $voogueme_sku,
                $nihao_sku,
                $wesee_sku,
                $meeloog_sku,
                $zeelool_es_sku,
                $zeelool_de_sku,
                $zeelool_jp_sku,
                $voogueme_acc_sku
            ];

            $map['a.sku'] = ['in', array_filter($skus)];
            $map['b.status'] = ['in', ['processing', 'paypal_reversed', 'paypal_canceled_reversal']];
            $map['a.distribution_status'] = ['<>', 0]; //排除取消状态
            $map['c.check_status'] = 0; //未审单计算订单占用
            $map['b.created_at'] = ['between', [strtotime('2020-01-01 00:00:00'), time()]]; //时间节点
            $occupy_stock = $this->orderitemprocess->alias('a')->where($map)
                ->join(['fa_order' => 'b'], 'a.order_id = b.id')
                ->join(['fa_order_process' => 'c'], 'a.order_id = c.order_id')
                ->count(1);

            $p_map['sku'] = $v;
            $data['occupy_stock'] = $occupy_stock;
            $this->item->where($p_map)->update($data);
            echo $v . "\n";
            usleep(20000);
        }
        echo 'ok';
        die;
    }

    /**
     * 可用库存计算 第四步
     *
     * @Description
     * @author wpl
     * @since 2020/04/11 15:54:25
     * @return void
     */
    public function set_product_sotck()
    {
        $this->itemplatformsku = new \app\admin\model\itemmanage\ItemPlatformSku;
        $this->item = new \app\admin\model\itemmanage\Item;

        $skus = Db::table('fa_zz_temp2')->column('sku');
        $list = $this->item->field('sku,stock,occupy_stock,available_stock,real_time_qty,distribution_occupy_stock')->where(['sku' => ['in', $skus]])->select();
        foreach ($list as $k => $v) {
            $data['stock'] = $v['real_time_qty'] + $v['distribution_occupy_stock'];
            $data['available_stock'] = ($v['real_time_qty'] + $v['distribution_occupy_stock']) - $v['occupy_stock'];
            $p_map['sku'] = $v['sku'];
            $res = $this->item->where($p_map)->update($data);

            echo $k . "\n";
            usleep(20000);
        }
        echo 'ok';
        die;
    }

    /**
     * 虚拟库存 第五步
     *
     * @Description
     * @author wpl
     * @since 2020/04/11 15:54:25
     * @return void
     */
    public function set_platform_stock()
    {
        $platform = new \app\admin\model\itemmanage\ItemPlatformSku();
        $item = new \app\admin\model\itemmanage\Item();
        $skus1 = $platform->where(['stock' => ['<', 0]])->column('sku');
        $skus = Db::table('fa_zz_temp2')->where(['sku' => ['in', $skus1]])->column('sku');
        // dump($skus);die;
        foreach ($skus as $k => $v) {
            // $v = 'OA01901-06';
            //同步对应SKU库存
            //更新商品表商品总库存
            //总库存
            $item_map['sku'] = $v;
            $item_map['is_del'] = 1;
            if ($v) {
                $available_stock = $item->where($item_map)->value('available_stock');

                //盘点的时候盘盈入库 盘亏出库 的同时要对虚拟库存进行一定的操作
                //查出映射表中此sku对应的所有平台sku 并根据库存数量进行排序（用于遍历数据的时候首先分配到那个站点）
                $item_platform_sku = $platform->where('sku', $v)->order('stock asc')->field('platform_type,stock')->select();
                if (!$item_platform_sku) {
                    continue;
                }
                $all_num = count($item_platform_sku);
                $whole_num = $platform
                    ->where('sku', $v)
                    ->field('stock')
                    ->select();
                $num_num = 0;
                foreach ($whole_num as $kk => $vv) {
                    $num_num += abs($vv['stock']);
                }
                $stock_num = $available_stock;
                // dump($available_stock);
                // dump($stock_num);

                $stock_all_num = array_sum(array_column($item_platform_sku, 'stock'));
                if ($stock_all_num < 0) {
                    $stock_all_num = 0;
                }
                //如果现有总库存为0 平均分给各站点
                if ($stock_all_num == 0) {
                    $rate_rate = 1 / $all_num;
                    foreach ($item_platform_sku as $key => $val) {
                        //最后一个站点 剩余数量分给最后一个站
                        if (($all_num - $key) == 1) {
                            // dump($stock_num);
                            $platform->where(['sku' => $v, 'platform_type' => $val['platform_type']])->update(['stock' => $stock_num]);
                        } else {
                            $num = round($available_stock * $rate_rate);
                            $stock_num -= $num;
                            // dump($num);
                            $platform->where(['sku' => $v, 'platform_type' => $val['platform_type']])->update(['stock' => $num]);
                        }
                    }
                } else {
                    // echo 1111;die;
                    foreach ($item_platform_sku as $key => $val) {
                        //最后一个站点 剩余数量分给最后一个站
                        if (($all_num - $key) == 1) {
                            $platform->where(['sku' => $v, 'platform_type' => $val['platform_type']])->update(['stock' => $stock_num]);
                        } else {
                            if ($num_num  == 0) {
                                $rate_rate = 1 / $all_num;
                                $num_num =  round($available_stock * $rate_rate);
                            } else {

                                $num = round($available_stock * abs($val['stock']) / $num_num);
                            }

                            $stock_num -= $num;
                            $platform->where(['sku' => $v, 'platform_type' => $val['platform_type']])->update(['stock' => $num]);
                        }
                    }
                }
            }
            usleep(10000);
            echo $k . "\n";
        }
        echo "ok";
    }

    /************************跑库存数据用END**********************************/



    /**
     * facebook调取用户评论
     *
     * @Description
     * @author wpl
     * @since 2020/11/20 17:31:02 
     * @return void
     */
    public function facebookTest()
    {
        Api::init($this->app_id, $this->app_secret, $this->access_token);

        $all_facebook_spend = 0;
        $accounts = explode(",", $this->accounts);
        foreach ($accounts as $key => $value) {
            $campaign = new Campaign($value);
            $params = array(
                'time_range' => array('since' => $start_time, 'until' => $end_time),
            );
            $cursor = $campaign->getInsights([], $params);
            foreach ($cursor->getObjects() as $key => $value) {
                if ($value) {
                    $all_facebook_spend += $cursor->getObjects()[0]->getData()['spend'];
                }
            }
        }
        return $all_facebook_spend ? round($all_facebook_spend, 2) : 0;
    }


    /**
     * 处理订单日志
     *
     * @Description
     * @author wpl
     * @since 2020/11/28 15:01:15 
     * @return void
     */
    public function process_order_log()
    {
        $order_log = new \app\admin\model\OrderLog();
        $list = $order_log->where(['site' => 2])->select();
        foreach ($list as $k => $v) {
            $arr = explode(',', $v['order_ids']);
            if ($arr[0] < 50000 && $arr[0] > 5000) {
                $order_log->where(['id' => $v['id']])->update(['site' => 3]);
            }
        }
        echo "ok";
    }


    /**
     * 获取前一天有效SKU销量
     * 记录当天有效SKU
     *
     * @Description
     * @author wpl
     * @since 2020/07/31 16:52:46 
     * @return void
     */
    public function get_sku_sales_num()
    {
        ini_set('memory_limit', '512M');
        //记录当天上架的SKU 
        $skuSalesNum = new \app\admin\model\SkuSalesNum();
        $order = new \app\admin\model\order\order\Order();
        //查询昨天上架SKU 并统计当天销量
        $data = $skuSalesNum->where(['createtime' => ['between', ['2020-12-01', '2020-12-02']]])->where('site<>8')->select();
        $data = collection($data)->toArray();
        if ($data) {
            foreach ($data as $k => $v) {
                if ($v['platform_sku']) {
                    $map['a.created_at'] = ['between', [date("Y-m-d 00:00:00", strtotime($v['createtime'])), date("Y-m-d 23:59:59", strtotime($v['createtime']))]];
                    $params[$k]['sales_num'] = $order->getSkuSalesNumTest($v['platform_sku'], $map, $v['site']);
                    $params[$k]['id'] = $v['id'];
                }
                echo $v['id'] . "\n";
                usleep(100000);
            }
            if ($params) {
                $skuSalesNum->saveAll($params);
            }
        }

        echo "ok";
    }

    /**
     * 处理订单节点数据
     *
     * @Description
     * @author wpl
     * @since 2020/12/09 16:24:45 
     * @return void
     */
    public function process_order_node()
    {
        $this->ordernode = new \app\admin\model\OrderNode();
        $this->ordernodedetail = new \app\admin\model\OrderNodeDetail();
        $list = $this->ordernode->where(['shipment_data_type' => '郭伟峰-广州美国专线'])->select();
        $params = [];
        foreach ($list as $k => $v) {
            $create_time = $this->ordernodedetail->where(['order_number' => $v['order_number'], 'site' => $v['site'], 'order_node' => 2, 'node_type' => 7])->order('id asc')->value('create_time');
            $params[$k]['delivery_time'] = $create_time;
            $params[$k]['id'] = $v['id'];
            echo $k . "\n";
        }
        $this->ordernode->saveAll($params);
        echo "ok";
    }


    /**
     * 获取前一天有效SKU销量
     * 记录当天有效SKU
     *
     * @Description
     * @author wpl
     * @since 2020/07/31 16:52:46 
     * @return void
     */
    public function set_sku_sales_num()
    {
        ini_set('memory_limit', '512M');
        //记录当天上架的SKU 
        $skuSalesNum = new \app\admin\model\SkuSalesNum();
        $order = new \app\admin\model\order\order\NewOrder();
        //查询昨天上架SKU 并统计当天销量

        $start = date('Ymd', strtotime("-30 day"));
        $end = date('Ymd', strtotime("-2 day"));
        $where['createtime'] = ['between', [$start, $end]];
        $data = $skuSalesNum->where($where)->where('site<>8')->select();
        $data = collection($data)->toArray();
        if ($data) {
            foreach ($data as $k => $v) {
                $time = ['between', [strtotime(date('Y-m-d 00:00:00', strtotime($v['createtime']))), strtotime(date('Y-m-d 23:59:59', strtotime($v['createtime'])))]];
                if ($v['platform_sku']) {
                    $params[$k]['sales_num'] = $order->getSkuSalesNumShell($v['platform_sku'], $v['site'], $time);
                    $params[$k]['id'] = $v['id'];
                }

                echo $k . "\n";
                usleep(50000);
            }
            if ($params) {
                $skuSalesNum->saveAll($params);
            }
        }
        echo "ok";
    }


    /**
     * 库龄旧数据
     *
     * @Description
     * @author wpl
     * @since 2021/01/28 14:35:19 
     * @return void
     */
    public function stock_time()
    {
        ini_set('memory_limit', '512M');
        $product_barcode = new \app\admin\model\warehouse\ProductBarCodeItem();
        $instock = new \app\admin\model\warehouse\Instock();
        $list = $product_barcode->where(['library_status' => 1, 'in_stock_id' => ['<>', 0]])->where('in_stock_time is null')->limit(100000)->select();
        foreach ($list as $k => $v) {
            //查询入库审核时间
            $check_time = $instock->where(['id' => $v['in_stock_id']])->value('check_time');
            $product_barcode->where(['id' => $v['id']])->update(['in_stock_time' => $check_time]);

            echo $k . "\n";
            usleep(50000);
        }
    }

    /**
     * 退货入库
     *
     * @Description
     * @author wpl
     * @since 2021/02/02 10:32:10 
     * @return void
     */
    public function return_purchase_order()
    {
        //查询退货入库采购单
        $purchase = new \app\admin\model\purchase\PurchaseOrder();
        $list = $purchase->where(['is_in_stock' => 1])->select();
        $purchase_item = new \app\admin\model\purchase\PurchaseOrderItem();

        $params = [];
        foreach ($list as $k => $v) {
            //查询子表商品总价
            $product_price =  $purchase_item->where(['purchase_id' => $v['id']])->sum('purchase_price*purchase_num');
            $params[$k]['id'] = $v['id'];
            $params[$k]['product_total'] = $product_price;
            $params[$k]['purchase_total'] = $product_price + $v['purchase_freight'];
        }
        $purchase->saveAll($params);
    }

    /**
     * 跑订单节点最后一条物流数据
     *
     * @Description
     * @author wpl
     * @since 2021/02/20 17:50:13 
     * @return void
     */
    public function order_node()
    {
        ini_set('memory_limit', '512M');
        $ordernode = new \app\admin\model\OrderNode();
        $ordernodecourier = new \app\admin\model\OrderNodeCourier();
        $list = $ordernode->where(['create_time' => ['between', ['2020-11-20 00:00:00', '2021-02-20 00:00:00']]])->where("track_number!=''")->where("shipment_last_msg=''")->select();
        foreach ($list as $k => $v) {
            $content = $ordernodecourier->where(['order_id' => $v['order_id'], 'site' => $v['site']])->order('id desc')->value('content');
            if ($content) {
                $ordernode->where(['id' => $v['id']])->update(['shipment_last_msg' => $content]);
            }
            echo $k . "\n";
            usleep(100000);
        }
        echo 'ok';
    }

    /**
     * 导出sku各站活跃天数销售额
     *
     * @Description
     * @author wpl
     * @since 2021/02/22 10:34:57 
     * @return void
     */
    public function derver_data()
    {
        $this->orderitemprocess = new \app\admin\model\order\order\NewOrderItemProcess();
        $this->itemplatformsku = new \app\admin\model\itemmanage\ItemPlatformSku;
        $this->item = new \app\admin\model\itemmanage\Item;
        $sql = "select sku,site from fa_sku_sales_num where site in (1,2,3) GROUP BY sku,site having count(1) > 30";
        $list = db()->query($sql);
        foreach ($list as $k => $v) {
            if ($v['site'] == 1) {
                $sku = $this->itemplatformsku->getWebSku($v['sku'], 1);
            } elseif ($v['site'] == 2) {
                $sku = $this->itemplatformsku->getWebSku($v['sku'], 2);
            } elseif ($v['site'] == 3) {
                $sku = $this->itemplatformsku->getWebSku($v['sku'], 3);
            }
            $skus = [];
            $skus = [
                $sku
            ];

            $map['a.sku'] = ['in', array_filter($skus)];
            $map['b.status'] = ['in', ['processing', 'paypal_reversed', 'paypal_canceled_reversal', 'complete', 'delivered']];
            $map['a.distribution_status'] = ['<>', 0]; //排除取消状态
            $map['b.created_at'] = ['between', [strtotime('2021-01-28 00:00:00'), strtotime('2021-02-31 23:59:59')]]; //时间节点
            $map['b.site'] = $v['site'];
            $sales_money = $this->orderitemprocess->alias('a')->where($map)
                ->join(['fa_order' => 'b'], 'a.order_id = b.id')
                ->join(['fa_order_item_option' => 'c'], 'a.order_id = c.order_id and a.option_id = c.id')
                ->sum('c.base_row_total');

            $list[$k]['sales_money'] = $sales_money;
        }
        $headlist = ['sku', '站点', '销售额'];
        Excel::writeCsv($list, $headlist, '12月份sku销量');
        die;
    }

    /**
     * 导出sku各站活跃天数销售额
     *
     * @Description
     * @author wpl
     * @since 2021/02/22 10:34:57 
     * @return void
     */
    public function derver_data2()
    {
        $this->orderitemprocess = new \app\admin\model\order\order\NewOrderItemProcess();
        $this->itemplatformsku = new \app\admin\model\itemmanage\ItemPlatformSku;
        $this->item = new \app\admin\model\itemmanage\Item;
        $sales_num = new \app\admin\model\SkuSalesNum();
        // $sql = "select sku,site from fa_sku_sales_num where site in (1,2,3) GROUP BY sku,site";
        // $list = db()->query($sql);


        $list = $this->item->where(['is_open' => 1, 'is_del' => 1, 'category_id' => ['<>', 43]])->column('sku');
        // $list = $sales_num->field('sku,site')->where(['site' => ['in', [1, 2, 3]], 'sku' => ['in', $sku_list]])->group('sku,site')->select();
        $list = collection($list)->toArray();
        $params = [];
        foreach ($list as $k => $v) {

            // if ($v['site'] == 1) {
            //     $site = $this->itemplatformsku->getWebSku($v['sku'], 1);
            // } elseif ($v['site'] == 2) {
            //     $sku = $this->itemplatformsku->getWebSku($v['sku'], 2);
            // } elseif ($v['site'] == 3) {
            //     $sku = $this->itemplatformsku->getWebSku($v['sku'], 3);
            // } elseif ($v['site'] == 4) {
            //     $sku = $this->itemplatformsku->getWebSku($v['sku'], 4);
            // } elseif ($v['site'] == 5) {
            //     $sku = $this->itemplatformsku->getWebSku($v['sku'], 5);
            // } elseif ($v['site'] == 9) {
            //     $sku = $this->itemplatformsku->getWebSku($v['sku'], 9);
            // } elseif ($v['site'] == 10) {
            //     $sku = $this->itemplatformsku->getWebSku($v['sku'], 10);
            // } elseif ($v['site'] == 11) {
            //     $sku = $this->itemplatformsku->getWebSku($v['sku'], 11);
            // }
            $skus =  $this->itemplatformsku->where(['sku' => $v])->column('platform_sku');
            // $skus = [
            //     $sku
            // ];

            //查询开始上架时间
            // $res = db('sku_sales_num')->where(['sku' => $v['sku'], 'site' => $v['site']])->order('createtime asc')->limit(30)->select();
            // if (!$res) {
            //     continue;
            // }
            // $res = array_column($res, 'createtime');
            // $first = $res[0];
            // $last = end($res);
            $map['a.sku'] = ['in', array_filter($skus)];
            $map['b.status'] = ['in', ['processing', 'paypal_reversed', 'paypal_canceled_reversal', 'complete', 'delivered']];
            $map['a.distribution_status'] = ['<>', 0]; //排除取消状态
            $map['b.created_at'] = ['between', [strtotime('2020-12-01 00:00:00'), strtotime('2021-02-31 23:59:59')]]; //时间节点
            // $map['b.site'] = $v['site'];

            $sales_num = $this->orderitemprocess->alias('a')
                ->where($map)
                ->join(['fa_order' => 'b'], 'a.order_id = b.id')
                ->count(1);

            $map['b.created_at'] = ['between', [strtotime('2010-12-01 00:00:00'), strtotime('2021-03-31 23:59:59')]]; //时间节点
            $all_sales_num = $this->orderitemprocess->alias('a')
                ->where($map)
                ->join(['fa_order' => 'b'], 'a.order_id = b.id')
                ->count(1);
            $params[$k]['sku'] = $v;
            $params[$k]['sales_num'] = $sales_num;
            $params[$k]['all_sales_num'] = $all_sales_num;
            // $list[$k]['sales_money'] = $sales_money;
        }
        $headlist = ['sku',  '近3个月销量', '历史累计销量'];
        Excel::writeCsv($params, $headlist, 'sku销量');
        die;
    }

    /**
     * 修改禁用状态
     */
    public function edit_product_status()
    {
        //查询库存、在途库存为0的sku 并且其他站点未上架 修改为禁用状态
        $item = new \app\admin\model\itemmanage\Item();
        $itemplatform = new \app\admin\model\itemmanage\ItemPlatformSku();
        $list = $item->where(['available_stock' => 0, 'item_status' => 3, 'on_way_stock' => 0, 'stock' => 0, 'is_open' => 1])->column('sku');
        $skus = [];
        foreach ($list as $k => $v) {
            $count = $itemplatform->where(['sku' => $v, 'outer_sku_status' => 1])->count();
            if ($count > 0) {
                continue;
            } else {
                $skus[] = $v;
            }
        }

        $item->where(['sku' => ['in', $skus]])->update(['is_open' => 2]);
    }

    public function edit_order_status()
    {
        //查询所有子单状态为8的子单
        $orderItem = new \app\admin\model\order\order\NewOrderItemProcess();
        $orderProcess = new \app\admin\model\order\order\NewOrderProcess();
        $worklist = new \app\admin\model\saleaftermanage\WorkOrderList();
        $list = $orderItem->where(['distribution_status' => 8])->select();
        foreach ($list as $k => $v) {
            $allcount = $orderItem->where(['order_id' => $v['order_id']])->count();

            $count = $orderItem->where(['distribution_status' => ['in', [0, 8, 9]], 'order_id' => $v['order_id']])->count();

            //查询工单是否处理完成
            $workcount = $worklist->where(['order_item_numbers' => ['like', '%' . $v['item_order_number'] . '%'], 'work_status' => ['in', [1, 2, 3, 5]]])->count();
            if ($allcount == $count && $workcount < 1) {
                $orderItem->where(['order_id' => $v['order_id'], 'distribution_status' => 8])->update(['distribution_status' => 9]);
                $orderProcess->where(['order_id' => $v['order_id']])->update(['combine_status' => 1, 'combine_time' => time()]);

                echo $v['id'] . "\n";
            }

            usleep(100000);
        }

        echo "ok";
    }


    //跑订单加工分类 - 仅镜架重新跑
    public function order_send_time()
    {
        ini_set('memory_limit', '512M');
        $process = new \app\admin\model\order\order\NewOrderProcess;
        $orderitemprocess = new \app\admin\model\order\order\NewOrderItemProcess();
        //查询所有订单 2月1号
        $order = $process->where('order_prescription_type', 1)->where(['order_id' => ['>', 1197029]])->column('order_id');
        foreach ($order as $key => $value) {
            $order_type = $orderitemprocess->where('order_id', $value)->column('order_prescription_type');
            //查不到结果跳过 防止子单表延迟两分钟查不到数据
            if (!$order_type) {
                continue;
            }

            if (in_array(3, $order_type)) {
                $type = 3;
            } elseif (in_array(2, $order_type)) {
                $type = 2;
            } else {
                $type = 1;
            }
            $process->where('order_id', $value)->update(['order_prescription_type' => $type]);
            echo $value . ' is ok' . "\n";
            usleep(100000);
        }
    }



    /**
     * 处理在途库存 - 更新在途库存
     *
     * @Description
     * @author wpl
     * @since 2020/06/09 10:08:03 
     * @return void
     */
    public function proccess_stock()
    {
        $item = new \app\admin\model\itemmanage\Item();
        $result = $item->where(['is_open' => 1, 'is_del' => 1])->field('sku,id')->select();
        $result = collection($result)->toArray();
        // $skus = array_column($result, 'sku');

        //查询签收的采购单
        $logistics = new \app\admin\model\LogisticsInfo();
        $purchase_id = $logistics->where(['status' => 1, 'purchase_id' => ['>', 0]])->column('purchase_id');
        $purchase = new \app\admin\model\purchase\PurchaseOrder;
        // $res = $purchase->where(['id' => ['in', $purchase_id], 'purchase_status' => 6])->update(['purchase_status' => 7]);
        //计算SKU总采购数量
        $purchase = new \app\admin\model\purchase\PurchaseOrder;
        // $hasWhere['sku'] = ['in', $skus];
        $purchase_map['purchase_status'] = ['in', [2, 5, 6]];
        $purchase_map['is_del'] = 1;
        $purchase_map['PurchaseOrder.id'] = ['not in', $purchase_id];
        $purchase_list = $purchase->hasWhere('purchaseOrderItem')
            ->where($purchase_map)
            ->group('sku')
            ->column('sum(purchase_num) as purchase_num', 'sku');

        foreach ($result as &$v) {
            $v['on_way_stock'] = $purchase_list[$v['sku']] ?? 0;
            unset($v['sku']);
        }
        unset($v);
        $res = $item->saveAll($result);
        die;
    }

    //导出sku库龄数据
    public function derive_list()
    {
        $barcode = new \app\admin\model\warehouse\ProductBarCodeItem();
        $sql = 'select sku,TIMESTAMPDIFF( MONTH, min(in_stock_time), now()) AS total,count(1) as num from fa_product_barcode_item a where library_status = 1 and in_stock_time is not null GROUP BY sku';
        $list = db()->query($sql);
        foreach ($list as $k => $v) {
            $where['i.sku'] = $v['sku'];
            $where['i.library_status'] = 1;
            $total = $barcode->alias('i')->join('fa_purchase_order_item oi', 'i.purchase_id=oi.purchase_id and i.sku=oi.sku')->join('fa_purchase_order o', 'o.id=i.purchase_id')->where($where)->where('in_stock_time is not null')->value('SUM(IF(actual_purchase_price,actual_purchase_price,o.purchase_total/purchase_num)) price');
            $list[$k]['price'] = $total;
        }
        $headlist = ['sku',  '库龄', '库存', '库存金额'];
        Excel::writeCsv($list, $headlist, 'sku库龄数据');
        die;
    }
}
