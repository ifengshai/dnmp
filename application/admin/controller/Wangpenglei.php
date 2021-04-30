<?php

namespace app\admin\controller;

use app\admin\controller\zendesk\Notice;
use app\admin\model\itemmanage\Item;
use app\admin\model\itemmanage\ItemPlatformSku;
use app\admin\model\warehouse\ProductBarCodeItem;
use app\common\controller\Backend;
use think\Db;
use FacebookAds\Api;
use FacebookAds\Object\Campaign;
use app\admin\model\financial\Fackbook;
use fast\Excel;
use think\Exception;

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
        $this->accounts = $this->facebook->accounts;
    }

    /************************跑库存数据用START*****勿删*****************************/
    //导入实时库存 第一步
    public function set_product_relstock()
    {
        $this->item = new \app\admin\model\itemmanage\Item;
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
                $voogueme_acc_sku,
            ];

            $map['a.sku'] = ['in', array_filter($skus)];
            $map['b.status'] = ['in', ['processing', 'paypal_reversed', 'paypal_canceled_reversal']];
            $map['a.distribution_status'] = ['>', 2]; //大于待配货
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
                $voogueme_acc_sku,
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
        $skus = Db::table('fa_zz_temp2')->column('sku');
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
                            if ($num_num == 0) {
                                $rate_rate = 1 / $all_num;
                                $num_num = round($available_stock * $rate_rate);
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
            $product_price = $purchase_item->where(['purchase_id' => $v['id']])->sum('purchase_price*purchase_num');
            $params[$k]['id'] = $v['id'];
            $params[$k]['product_total'] = $product_price;
            $params[$k]['purchase_total'] = $product_price + $v['purchase_freight'];
        }
        $purchase->saveAll($params);
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
                $sku,
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
            $skus = $this->itemplatformsku->where(['sku' => $v])->column('platform_sku');
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
        $headlist = ['sku', '近3个月销量', '历史累计销量'];
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

    //导出订单数据
    public function derive_order_data()
    {
        ini_set('memory_limit', '1512M');
        $order = new \app\admin\model\order\Order();
        $lensdata = new \app\admin\model\order\order\LensData();
        $where['a.status'] = ['in', ['processing', 'paypal_reversed', 'paypal_canceled_reversal', 'complete', 'delivered']];
        $where['a.created_at'] = ['between', [strtotime(date('2021-03-01 00:00:00')), strtotime(date('2021-03-25 23:59:59'))]];
        $where['d.is_repeat'] = 0;
        $where['d.is_split'] = 0;
        $list = $order->where($where)->alias('a')->field('a.increment_id,b.item_order_number,b.sku,d.order_prescription_type,prescription_type,web_lens_name,coating_name,od_sph,os_sph,od_cyl,os_cyl,od_axis,os_axis,pd_l,pd_r,pd,os_add,od_add,od_pv,os_pv,od_pv_r,os_pv_r,od_bd,os_bd,od_bd_r,os_bd_r,lens_number')
            ->join(['fa_order_item_process' => 'b'], 'a.id=b.order_id')
            ->join(['fa_order_item_option' => 'c'], 'b.option_id=c.id')
            ->join(['fa_order_process' => 'd'], 'd.order_id=a.id')
            ->select();
        $lenslist = $lensdata->column('lens_name', 'lens_number');
        $params = [];
        foreach ($list as $k => $v) {
            $params[$k]['increment_id'] = $v['increment_id'];
            $params[$k]['item_order_number'] = $v['item_order_number'];
            $params[$k]['sku'] = $v['sku'];
            $str = '';
            if ($v['order_prescription_type'] == 1) {
                $str = '仅镜架';
            } elseif ($v['order_prescription_type'] == 2) {
                $str = '现货处方镜';
            } elseif ($v['order_prescription_type'] == 3) {
                $str = '定制处方镜';
            }

            $params[$k]['order_prescription_type'] = $str;
            $params[$k]['prescription_type'] = $v['prescription_type'];
            $params[$k]['lensname'] = $lenslist[$v['lens_number']];
            $params[$k]['coating_name'] = $v['coating_name'];
            $params[$k]['od_sph'] = $v['od_sph'];
            $params[$k]['os_sph'] = $v['os_sph'];
            $params[$k]['od_cyl'] = $v['od_cyl'];
            $params[$k]['os_cyl'] = $v['os_cyl'];
            $params[$k]['od_axis'] = $v['od_axis'];
            $params[$k]['os_axis'] = $v['os_axis'];
            $params[$k]['pd_r'] = $v['pd_r'];
            $params[$k]['pd_l'] = $v['pd_l'];
            $params[$k]['pd'] = $v['pd'];
            $params[$k]['od_add'] = $v['od_add'];
            $params[$k]['os_add'] = $v['os_add'];
            $params[$k]['od_pv'] = $v['od_pv'];
            $params[$k]['os_pv'] = $v['os_pv'];
            $params[$k]['od_pv_r'] = $v['od_pv_r'];
            $params[$k]['os_pv_r'] = $v['os_pv_r'];
            $params[$k]['od_bd'] = $v['od_bd'];
            $params[$k]['os_bd'] = $v['os_bd'];
            $params[$k]['od_bd_r'] = $v['od_bd_r'];
            $params[$k]['os_bd_r'] = $v['os_bd_r'];
        }

        $headlist = ['订单号', '子单号', 'sku', '加工类型', '处方类型', '镜片名称', '镀膜名称', '右眼SPH', '左眼SPH', '右眼CYL', '左眼CYL', '右眼AXIS', '左眼AXIS', '左眼PD', '右眼PD', 'PD', '右眼ADD', '左眼ADD', '右眼Prism(out/in)', '左眼Prism(out/in)', '右眼Direction(out/in)', '左眼Direction(out/in)', '右眼Prism(up/down)', '左眼Prism(up/down)', '右眼Direction(up/down)', '左眼Direction(up/down)'];
        Excel::writeCsv($params, $headlist, '3月份订单数据');
        die;
    }


    /**
     * 处理库位顺序
     */
    public function process_store_sort()
    {
        $list = db('zz_temp2')->select();
        $storehouse = new \app\admin\model\warehouse\StockHouse();
        foreach ($list as $k => $v) {
            $storehouse->where(['type' => 1, 'area_id' => 3, 'coding' => $v['store_house']])->update(['picking_sort' => $v['sort']]);
        }
    }

    /**
     *  处理采购成本单价
     * @Description
     * @author: wpl
     * @since : 2021/4/1 17:40
     */
    public function getPurchasePrice()
    {
        //查询镜架成本为0的财务数据
        $finace_cost = new \app\admin\model\finance\FinanceCost();
        $barcode = new \app\admin\model\warehouse\ProductBarCodeItem();
        $list = $finace_cost->where(['type' => 2, 'frame_cost' => 0, 'bill_type' => 8])->select();
        $list = collection($list)->toArray();
        $params = [];
        $i = 0;
        foreach ($list as $k => $v) {
            $data = $barcode->alias('a')
                ->where(['item_order_number' => ['like', $v['order_number'] . '%']])
                ->field('a.sku,a.in_stock_id,b.price')
                ->join(['fa_in_stock_item' => 'b'], 'a.in_stock_id=b.in_stock_id and a.sku=b.sku')
                ->select();
            $data = collection($data)->toArray();
            foreach ($data as $key => $val) {
                $params[$i]['order_number'] = $v['order_number'];
                $params[$i]['sku'] = $val['sku'];
                $params[$i]['price'] = $val['price'];
                $i++;
            }
        }
        $headlist = ['订单号', 'sku', '采购成本'];
        Excel::writeCsv($params, $headlist, '采购成本');
        die;
    }

    /**
     *  重新计算三月份财务成本
     * @Description
     * @author: wpl
     * @since : 2021/4/2 11:52
     */
    public function getFinanceCost()
    {
        ini_set('memory_limit', '1512M');
        $finace_cost = new \app\admin\model\finance\FinanceCost();
        $list = $finace_cost->where(['type' => 2, 'bill_type' => 8, 'frame_cost' => 0, 'createtime' => ['>', 1614528000]])->select();
        $params = [];
        foreach ($list as $k => $v) {
            $frame_cost = $this->order_frame_cost($v['order_number']);
            $finace_cost->where(['id' => $v['id']])->update(['frame_cost' => $frame_cost]);
            echo $v['id'] . "\n";
            usleep(100000);
        }
    }

    /**
     *  重新计算三月份财务成本
     * @Description
     * @author: wpl
     * @since : 2021/4/2 11:52
     */
    public function getOrderGrandTotal()
    {
        ini_set('memory_limit', '1512M');
        $finace_cost = new \app\admin\model\finance\FinanceCost();
        $list = $finace_cost->where(['bill_type' => 1, 'income_amount' => 0, 'createtime' => ['>', 1614528000]])->select();
        $order = new \app\admin\model\order\order\NewOrder();
        $params = [];
        foreach ($list as $k => $v) {
            //查询订单支付金额
            $grand_total = $order->where(['increment_id' => $v['order_number'], 'site' => $v['site']])->value('grand_total');
            $finace_cost->where(['id' => $v['id']])->update(['income_amount' => $grand_total, 'order_money' => $grand_total]);
            echo $v['id'] . "\n";
            usleep(100000);
        }
    }

    /**
     *  重新计算三月份财务成本
     * @Description
     * @author: wpl
     * @since : 2021/4/2 11:52
     */
    public function getOrderGrandTotalTwo()
    {
        ini_set('memory_limit', '1512M');
        $finace_cost = new \app\admin\model\finance\FinanceCost();
        $list = $finace_cost->where(['bill_type' => 8, 'income_amount' => 0, 'createtime' => ['>', 1614528000]])->select();
        $order = new \app\admin\model\order\order\NewOrder();
        $params = [];
        foreach ($list as $k => $v) {
            //查询订单支付金额
            $grand_total = $order->where(['increment_id' => $v['order_number'], 'site' => $v['site']])->value('grand_total');
            $finace_cost->where(['id' => $v['id']])->update(['income_amount' => $grand_total, 'order_money' => $grand_total]);
            echo $v['id'] . "\n";
            usleep(100000);
        }
    }

    /**
     * 订单镜架成本
     *
     * @Description
     * @author wpl
     * @since 2021/01/19 18:20:45 
     *
     * @param     [type] $order_id     订单id
     * @param     [type] $order_number 订单号
     *
     * @return void
     */
    protected function order_frame_cost($order_number = null)
    {
        $product_barcode_item = new \app\admin\model\warehouse\ProductBarCodeItem();
        $order_item_process = new \app\admin\model\order\order\NewOrderItemProcess();
        //查询订单子单号
        $item_order_number = $order_item_process
            ->alias('a')
            ->join(['fa_order' => 'b'], 'a.order_id=b.id')
            ->where(['increment_id' => $order_number])
            ->column('item_order_number');

        //判断是否有工单
        $worklist = new \app\admin\model\saleaftermanage\WorkOrderList();

        //查询更改类型为赠品
        $goods_number = $worklist->alias('a')
            ->join(['fa_work_order_change_sku' => 'b'], 'a.id=b.work_id')
            ->where(['platform_order' => $order_number, 'work_status' => 6, 'change_type' => 4])
            ->column('b.goods_number');
        $workcost = 0;
        if ($goods_number) {
            //计算成本
            $workdata = $product_barcode_item->alias('a')->field('purchase_price,actual_purchase_price,c.purchase_total,purchase_num')
                ->where(['code' => ['in', $goods_number]])
                ->join(['fa_purchase_order_item' => 'b'], 'a.purchase_id=b.purchase_id and a.sku=b.sku')
                ->join(['fa_purchase_order' => 'c'], 'a.purchase_id=c.id')
                ->select();
            foreach ($workdata as $k => $v) {
                $workcost += $v['actual_purchase_price'] > 0 ? $v['actual_purchase_price'] : $v['purchase_total'] / $v['purchase_num'];
            }
        }

        //根据子单号查询条形码绑定关系
        $list = $product_barcode_item->alias('a')->field('a.sku,b.price')
            ->where(['item_order_number' => ['in', $item_order_number]])
            ->join(['fa_in_stock_item' => 'b'], 'a.in_stock_id=b.in_stock_id and a.sku=b.sku')
            ->select();
        $list = collection($list)->toArray();
        $allcost = 0;
        $purchase_item = new \app\admin\model\purchase\PurchaseOrderItem();
        foreach ($list as $k => $v) {
            $purchase_price = $v['price'] > 0 ? $v['price'] : $v['price'];
            $allcost += $purchase_price;
        }

        return $allcost + $workcost;
    }


    /**
     * 镜片成本测试
     *
     * @Description
     * @author wpl
     * @since 2021/01/19 16:31:21 
     * @return void
     */
    public function order_lens_cost()
    {
        $order_id = 1388996;
        $order_number = 100232168;
        //判断是否有工单
        $worklist = new \app\admin\model\saleaftermanage\WorkOrderList();
        $workchangesku = new \app\admin\model\saleaftermanage\WorkOrderChangeSku();
        $work_id = $worklist->where(['platform_order' => $order_number, 'work_status' => 6])->order('id desc')->value('id');
        //查询更改类型为更改镜片
        $work_data = $workchangesku->where(['work_id' => $work_id, 'change_type' => 2])
            ->field('od_sph,os_sph,od_cyl,os_cyl,os_add,od_add,lens_number,item_order_number')
            ->select();
        $work_data = collection($work_data)->toArray();
        //工单计算镜片成本
        if ($work_data) {
            $where['item_order_number'] = ['not in', array_column($work_data, 'item_order_number')];
            $lens_number = array_column($work_data, 'lens_number');
            //查询镜片编码对应价格
            $lens_price = new \app\admin\model\lens\LensPrice();
            $lens_list = $lens_price->where(['lens_number' => ['in', $lens_number]])->order('price asc')->select();
            $work_cost = 0;
            foreach ($work_data as $k => $v) {
                $data = [];
                foreach ($lens_list as $key => $val) {
                    $od_temp_cost = 0;
                    $os_temp_cost = 0;
                    //判断子单右眼是否已判断
                    if (!in_array('od' . '-' . $val['lens_number'], $data)) {
                        if ($v['od_cyl'] == '-0.25') {
                            //右眼
                            if ($v['lens_number'] == $val['lens_number'] && ((float)$v['od_sph'] >= (float)$val['sph_start'] && (float)$v['od_sph'] <= (float)$val['sph_end']) && ((float)$v['od_cyl'] == (float)$val['cyl_end'] && (float)$v['od_cyl'] == (float)$val['cyl_end'])) {
                                $work_cost += $val['price'];
                                $od_temp_cost += $val['price'];
                            } elseif ($v['lens_number'] == $val['lens_number'] && ((float)$v['od_sph'] >= (float)$val['sph_start'] && (float)$v['od_sph'] <= (float)$val['sph_end']) && ((float)$v['od_cyl'] >= (float)$val['cyl_start'] && (float)$v['od_cyl'] <= (float)$val['cyl_end'])) {
                                $work_cost += $val['price'];
                                $od_temp_cost += $val['price'];
                            }
                        } else {
                            //右眼
                            if ($v['lens_number'] == $val['lens_number'] && ((float)$v['od_sph'] >= (float)$val['sph_start'] && (float)$v['od_sph'] <= (float)$val['sph_end']) && ((float)$v['od_cyl'] >= (float)$val['cyl_start'] && (float)$v['od_cyl'] <= (float)$val['cyl_end'])) {
                                $work_cost += $val['price'];
                                $od_temp_cost += $val['price'];
                            }
                        }
                        if ($od_temp_cost > 0) {
                            $data[] = 'od' . '-' . $v['lens_number'];
                        }
                    }

                    //判断子单左眼是否已判断
                    if (!in_array('os' . '-' . $val['lens_number'], $data)) {

                        if ($v['os_cyl'] == '-0.25') {
                            //左眼
                            if ($v['lens_number'] == $val['lens_number'] && ((float)$v['os_sph'] >= (float)$val['sph_start'] && (float)$v['os_sph'] <= (float)$val['sph_end']) && ((float)$v['os_cyl'] == (float)$val['cyl_end'] && (float)$v['os_cyl'] == (float)$val['cyl_end'])) {
                                $work_cost += $val['price'];
                                $os_temp_cost += $val['price'];
                            } elseif ($v['lens_number'] == $val['lens_number'] && ((float)$v['os_sph'] >= (float)$val['sph_start'] && (float)$v['os_sph'] <= (float)$val['sph_end']) && ((float)$v['os_cyl'] >= (float)$val['cyl_start'] && (float)$v['os_cyl'] <= (float)$val['cyl_end'])) {
                                $work_cost += $val['price'];
                                $os_temp_cost += $val['price'];
                            }
                        } else {
                            //左眼
                            if ($v['lens_number'] == $val['lens_number'] && ((float)$v['os_sph'] >= (float)$val['sph_start'] && (float)$v['os_sph'] <= (float)$val['sph_end']) && ((float)$v['os_cyl'] >= (float)$val['cyl_start'] && (float)$v['os_cyl'] <= (float)$val['cyl_end'])) {
                                $work_cost += $val['price'];
                                $os_temp_cost += $val['price'];
                            }
                        }

                        if ($os_temp_cost > 0) {
                            $data[] = 'os' . '-' . $v['lens_number'];
                        }
                    }
                }
            }
        }

        //查询处方数据
        $order_item_process = new \app\admin\model\order\order\NewOrderItemProcess();
        $order_prescription = $order_item_process->alias('a')->field('b.od_sph,b.os_sph,b.od_cyl,b.os_cyl,b.os_add,b.od_add,b.lens_number')
            ->where(['a.order_id' => $order_id, 'distribution_status' => 9])
            ->where($where)
            ->join(['fa_order_item_option' => 'b'], 'a.option_id=b.id')
            ->select();

        $order_prescription = collection($order_prescription)->toArray();
        $lens_number = array_column($order_prescription, 'lens_number');
        //查询镜片编码对应价格
        $lens_price = new \app\admin\model\lens\LensPrice();
        $lens_list = $lens_price->where(['lens_number' => ['in', $lens_number]])->order('price asc')->select();
        $cost = 0;

        foreach ($order_prescription as $k => $v) {
            $data = [];
            foreach ($lens_list as $key => $val) {
                $od_temp_cost = 0;
                $os_temp_cost = 0;
                //判断子单右眼是否已判断
                if (!in_array('od' . '-' . $val['lens_number'], $data)) {
                    if ($v['od_cyl'] == '-0.25') {
                        //右眼
                        if ($v['lens_number'] == $val['lens_number'] && ((float)$v['od_sph'] >= (float)$val['sph_start'] && (float)$v['od_sph'] <= (float)$val['sph_end']) && ((float)$v['od_cyl'] == (float)$val['cyl_end'] && (float)$v['od_cyl'] == (float)$val['cyl_end'])) {
                            $cost += $val['price'];
                            $od_temp_cost += $val['price'];
                        } elseif ($v['lens_number'] == $val['lens_number'] && ((float)$v['od_sph'] >= (float)$val['sph_start'] && (float)$v['od_sph'] <= (float)$val['sph_end']) && ((float)$v['od_cyl'] >= (float)$val['cyl_start'] && (float)$v['od_cyl'] <= (float)$val['cyl_end'])) {
                            $cost += $val['price'];
                            $od_temp_cost += $val['price'];
                        }
                    } else {
                        //右眼
                        if ($v['lens_number'] == $val['lens_number'] && ((float)$v['od_sph'] >= (float)$val['sph_start'] && (float)$v['od_sph'] <= (float)$val['sph_end']) && ((float)$v['od_cyl'] >= (float)$val['cyl_start'] && (float)$v['od_cyl'] <= (float)$val['cyl_end'])) {
                            $cost += $val['price'];
                            $od_temp_cost += $val['price'];
                        }
                    }
                    if ($od_temp_cost > 0) {
                        $data[] = 'od' . '-' . $v['lens_number'];
                    }
                }

                //判断子单左眼是否已判断
                if (!in_array('os' . '-' . $val['lens_number'], $data)) {

                    if ($v['os_cyl'] == '-0.25') {
                        //左眼
                        if ($v['lens_number'] == $val['lens_number'] && ((float)$v['os_sph'] >= (float)$val['sph_start'] && (float)$v['os_sph'] <= (float)$val['sph_end']) && ((float)$v['os_cyl'] == (float)$val['cyl_end'] && (float)$v['os_cyl'] == (float)$val['cyl_end'])) {
                            $cost += $val['price'];
                            $os_temp_cost += $val['price'];
                        } elseif ($v['lens_number'] == $val['lens_number'] && ((float)$v['os_sph'] >= (float)$val['sph_start'] && (float)$v['os_sph'] <= (float)$val['sph_end']) && ((float)$v['os_cyl'] >= (float)$val['cyl_start'] && (float)$v['os_cyl'] <= (float)$val['cyl_end'])) {
                            $cost += $val['price'];
                            $os_temp_cost += $val['price'];
                        }
                    } else {
                        //左眼
                        if ($v['lens_number'] == $val['lens_number'] && ((float)$v['os_sph'] >= (float)$val['sph_start'] && (float)$v['os_sph'] <= (float)$val['sph_end']) && ((float)$v['os_cyl'] >= (float)$val['cyl_start'] && (float)$v['os_cyl'] <= (float)$val['cyl_end'])) {
                            $cost += $val['price'];
                            $os_temp_cost += $val['price'];
                        }
                    }

                    if ($os_temp_cost > 0) {
                        $data[] = 'os' . '-' . $v['lens_number'];
                    }
                }
            }
        }

        dump($cost);
        dump($work_cost);
        dump($cost + $work_cost);
        die;
    }

    /**
     * 处理波次单数据
     * @author wpl
     * @date   2021/4/9 14:35
     */
    public function process_wave_data()
    {
        $wave = new \app\admin\model\order\order\WaveOrder();
        $orderItem = new \app\admin\model\order\order\NewOrderItemProcess();
        $list = $wave->where(['status' => 1])->select();
        foreach ($list as $k => $v) {
            //添加波次单打印状态为已打印
            $count = $orderItem->alias('a')
                ->join(['fa_order' => 'b'], 'a.order_id=b.id')
                ->where(['wave_order_id' => $v['id'], 'is_print' => 0, 'distribution_status' => ['<>', 0]])
                ->where(['b.status' => ['in', ['processing', 'paypal_reversed', 'paypal_canceled_reversal', 'complete', 'delivered']]])
                ->count();
            if ($count > 0) {
                $status = 1;
            } elseif ($count == 0) {
                $status = 2;
            }
            $wave->where(['id' => $v['id']])->update(['status' => $status]);
        }
    }


    /**
     * 判断处方是否异常
     *
     * @param  array  $params
     *
     * @author wpl
     * @date   2021/4/23 9:31
     */
    protected function is_prescription_abnormal($params = [])
    {
        $list = [];
        $od_sph = (float)urldecode($params['od_sph']);
        $os_sph = (float)urldecode($params['os_sph']);
        $od_cyl = (float)urldecode($params['od_cyl']);
        $os_cyl = (float)urldecode($params['os_cyl']);
        //截取镜片编码第一位
        $str = substr($params['lens_number'], 0, 1);
        /**
         * 判断处方是否异常规则
         * 1、SPH值或CYL值的“+”“_”号不一致
         * 2、左右的SPH或CYL 绝对值相差超过3
         * 3、有SPH或CYL无PD
         * 4、有PD无SPH及CYL
         */
        if (($od_sph < 0 && $os_sph > 0) || ($od_sph > 0 && $os_sph < 0)) {
            $list['is_prescription_abnormal'] = 1;
        }

        if (($os_cyl < 0 && $od_cyl > 0) || ($os_cyl > 0 && $od_cyl < 0)) {
            $list['is_prescription_abnormal'] = 1;
        }

        //绝对值相差超过3
        $odDifference = abs($od_sph) - abs($os_sph);
        $osDifference = abs($od_cyl) - abs($os_cyl);
        if (abs($odDifference) > 3 || abs($osDifference) > 3) {
            $list['is_prescription_abnormal'] = 1;
        }

        //有PD无SPH和CYL
        if (($params['pdcheck'] == 'on' || $params['pd']) && (!$od_sph && !$os_sph && !$od_cyl && !$os_cyl && $str == '2')) {
            $list['is_prescription_abnormal'] = 1;
        }

        //有SPH或CYL无PD
        if (($params['pdcheck'] != 'on' && !$params['pd']) && ($od_sph || $os_sph || $od_cyl || $os_cyl) && $str == '3') {
            $list['is_prescription_abnormal'] = 1;
        }

        $list['is_prescription_abnormal'] = $list['is_prescription_abnormal'] ?: 0;

        return $list;
    }

    public function test001()
    {
        $params['od_sph'] = '3.00';
        $params['os_sph'] = '2.00';
        $params['od_cyl'] = '-0.75';
        $params['os_cyl'] = '-0.50';
        $params['pd'] = 60;
        $params['lens_number'] = 32302000;
        $list = $this->is_prescription_abnormal($params);
        dump($list);
    }

    public function test01()
    {
        echo shell_exec('cd /var/www/mojing/public && php admin_1biSSnWyfW.php shell/order_data/create_wave_order');
    }


    /**
     *  导出库存数据对比
     * @Description
     * @author: wpl
     * @since : 2021/4/1 17:40
     */
    public function getStockData()
    {
        //查询镜架成本为0的财务数据
        $barcode = new \app\admin\model\warehouse\ProductBarCodeItem();

        $item = new Item();
        $data = $item
            ->where(['is_open' => 1, 'is_del' => 1, 'category_id' => ['<>', 43]])
            ->column('stock,distribution_occupy_stock', 'sku');
        $list = $barcode
            ->field('sku,count(1) as num')
            ->where(['library_status' => 1])
            ->where("item_order_number=''")
            ->group('sku')
            ->select();
        $list = collection($list)->toArray();
        foreach ($list as $k => $v) {
            $list[$k]['stock'] = $data[$v['sku']]['stock'] - $data[$v['sku']]['distribution_occupy_stock'];
        }

        $headlist = ['sku', '在库实时库存', '系统实时库存'];
        Excel::writeCsv($list, $headlist, '库存', true);
        die;
    }

    public function test002()
    {
        $c_url = '';
        $frist = substr($c_url, 0, 1);
        echo $frist;
        die;
    }


    public function asyncTicketHttps($type, $site, $start, $end)
    {
        echo $start.'-'.$end."\n";
        $this->model = new \app\admin\model\zendesk\Zendesk;
        $ticketIds = (new Notice(request(), ['type' => $site]))->asyncUpdate($start, $end);

        //判断是否存在
        $nowTicketsIds = $this->model->where("type", $type)->column('ticket_id');

        //求交集的更新

        $intersects = array_intersect($ticketIds, $nowTicketsIds);
        //求差集新增
        $diffs = array_diff($ticketIds, $nowTicketsIds);
        //更新

        //$intersects = array('142871','142869');//测试是否更新
        //$diffs = array('144352','144349');//测试是否新增
        foreach ($intersects as $intersect) {
            (new Notice(request(), ['type' => $site, 'id' => $intersect]))->update();
            echo $intersect.'is ok'."\n";
        }
        //新增
        foreach ($diffs as $diff) {
            (new Notice(request(), ['type' => $site, 'id' => $diff]))->create();
            echo $diff.'ok'."\n";
        }
        echo 'all ok';
//        exit;
    }


    public function test()
    {
        $type = 1;
        $site = 'zeelool';
        $start = '2021-04-26T23:00:00Z';
        $end = '2021-04-26T23:59:59Z';

        $this->asyncTicketHttps($type, $site, $start, $end);
    }

    public function test011()
    {
        $type = 1;
        $site = 'zeelool';
        for ($i = 0; $i < 24; $i++) {
            $start = '2021-04-27T'.$i.':00:00Z';
            $end = '2021-04-27T'.($i + 1).':00:00Z';
            try {
                $this->asyncTicketHttps($type, $site, $start, $end);
                usleep(100000);
            } catch (Exception $e) {
                echo $e->getMessage();
            }

        }
    }
}
