<?php

namespace app\admin\controller;

use app\admin\controller\zendesk\Notice;
use app\admin\model\itemmanage\Item;
use app\admin\model\itemmanage\ItemPlatformSku;
use app\admin\model\lens\LensPrice;
use app\admin\model\operatedatacenter\DatacenterDay;
use app\admin\model\order\order\NewOrder;
use app\admin\model\order\order\NewOrderItemProcess;
use app\admin\model\OrderNode;
use app\admin\model\saleaftermanage\WorkOrderList;
use app\admin\model\saleaftermanage\WorkOrderMeasure;
use app\admin\model\saleaftermanage\WorkOrderRecept;
use app\admin\model\warehouse\ProductBarCodeItem;
use app\admin\model\warehouse\StockHouse;
use app\admin\model\zendesk\Zendesk;
use app\common\controller\Backend;
use think\Db;
use FacebookAds\Api;
use FacebookAds\Object\Campaign;
use app\admin\model\financial\Fackbook;
use fast\Excel;
use think\Exception;
use think\Model;
use think\Queue;
use Zendesk\API\HttpClient;

class Process extends Backend
{

    protected $noNeedLogin = ['*'];
    /**
     * @var
     * @author wangpenglei
     * @date   2021/6/9 18:18
     */
    private $orderitemprocess;

    public function _initialize()
    {
        parent::_initialize();
        $this->zeelool = new \app\admin\model\order\order\Zeelool();

        $this->facebook = Fackbook::where('platform', 1)->find();
        $this->app_id = $this->facebook->app_id;
        $this->app_secret = $this->facebook->app_secret;
        $this->access_token = $this->facebook->access_token;
        $this->accounts = $this->facebook->accounts;
        $this->orderitemprocess = new  NewOrderItemProcess();
    }

    public function select_sku()
    {
        $itemPlatformSku = new ItemPlatformSku();
        $productbarcodeitem = new ProductBarCodeItem();
        $skus = $itemPlatformSku
            ->where('platform_sku', 'in', [
                'DTT089380-01',
                'NDA283634-01',
                'ACC566389-01',
                'ZOX063349-03',
                'VFP0248-01',
                'GACC566389-01',
                'GACC6041-02',
                'GTT598617-05',
                'ZGM947526-01',
                'GOX742171-01',
                'VFP0263-01',
                'GOM427874-02',
                'ZOA02083-02',
                'GOT668795-01',
                'GOX272286-01',
                'GACC6032-02',
                'ZGX322474-02',
                'ZWA071469-03',
                'ZE20031-1',
                'ZER015738-01',
                'ZE20037-1',
                'GOP01912-06',
                'GACC285895-01',
                'GOP375333-02',
                'GOP006896-01',
                'OT668795-01',
                'OT668795-02',
                'VFP0248-01',
                'ZWA583669-01',
                'ZWM706044-01',
                'ZOA02083-02',
                'GOT703228-01',
                'GOT668795-01',
                'ZGA329222-01',
                'GOX272286-01',
                'GOX963140-01',
                'GOX742171-01',
            ])
            ->field('sku')
            ->group('sku')
            ->select();
        $skus = collection($skus)->toArray();
        $arr = ['SX0019-05', 'OP527327-01', 'JS771317-01', 'CH672798-08', 'TT598617-05', 'OP449452-02', 'OP02048-03', 'OP421241-02', 'OI913496-02', 'OP01990-03', 'FM0361-01', 'WA034265-01', 'WA245023-03', 'ER134040-01', 'WA065152-01', 'WA192071-01', 'SX0019-03', 'SX0019-02', 'DM638949-01', 'OP358317-02', 'WA034265-01', 'Glasses Pocket-02'];

        foreach ($skus as $k => $v) {
            $list[$k]['sku'] = $v['sku'];
            $list[$k]['stock'] = $productbarcodeitem
                ->where(['library_status' => 1, 'item_order_number' => '', 'sku' => $v['sku']])
                ->where('location_code_id', '>', 0)
                ->count();
        }
        Db::name('zz_temp1')->insertAll($list);

    }

    /************************跑库存数据用START*****勿删*****************************/

    public function set_stock()
    {
        $this->getStockList();
        $this->set_product_relstock();
        $this->set_product_process();
        $this->set_product_process_order();
        $this->set_product_sotck();
        $this->set_platform_stock();
    }

    /**
     *  根据条码计算实时库存
     * @Description
     * @author: wpl
     * @since : 2021/4/1 17:40
     */
    public function getStockList()
    {
        //查询镜架成本为0的财务数据
        $barcode = new \app\admin\model\warehouse\ProductBarCodeItem();
        $skus = Db::table('fa_zz_temp1')->column('sku');
        $list = $barcode
            ->alias('a')
            ->where(['a.library_status' => 1])
            ->where(['a.sku' => ['in', $skus]])
            ->where(['a.location_code_id' => ['>', 0]])
            ->where("a.item_order_number=''")
            ->group('sku')
            ->column('count(1) as stock', 'sku');
        foreach ($skus as $v) {
            Db::table('fa_zz_temp1')->where(['sku' => $v])->update(['stock' => $list[$v] ?: 0]);
        }
    }


    //导入实时库存 第一步
    public function set_product_relstock()
    {
        $this->item = new \app\admin\model\itemmanage\Item;
        $list = Db::table('fa_zz_temp1')->select();
        foreach ($list as $k => $v) {
            $p_map['sku'] = $v['sku'];
            $data['real_time_qty'] = $v['stock'];
            $res = $this->item->where($p_map)->update($data);
            echo $v['sku'] . "\n";
        }
        echo 'ok';
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

        $skus = Db::table('fa_zz_temp1')->column('sku');

        foreach ($skus as $k => $v) {
            $map = [];
            $skus = [];
            $skus = $this->itemplatformsku->where(['sku' => $v])->column('platform_sku');

            $map['a.sku'] = ['in', array_filter($skus)];
            $map['b.status'] = ['in', ['processing']];
            $map['a.distribution_status'] = ['>', 2]; //大于待配货
            $map['c.check_status'] = 0; //未审单计算配货占用
            $map['b.created_at'] = ['between', [strtotime('2021-01-01 00:00:00'), time()]]; //时间节点
            $map['c.is_repeat'] = 0;
            $map['c.is_split'] = 0;
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
        $skus = Db::table('fa_zz_temp1')->column('sku');
        foreach ($skus as $k => $v) {
            $map = [];
            $skus = [];
            $skus = $this->itemplatformsku->where(['sku' => $v])->column('platform_sku');
            $map['a.sku'] = ['in', array_filter($skus)];
            $map['b.status'] = ['in', ['processing', 'complete', 'delivered', 'delivery']];
            $map['a.distribution_status'] = ['<>', 0]; //排除取消状态
            $map['c.check_status'] = 0; //未审单计算订单占用
            $map['b.created_at'] = ['between', [strtotime('2021-01-01 00:00:00'), time()]]; //时间节点
            $map['c.is_repeat'] = 0;
            $map['c.is_split'] = 0;
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

        $skus = Db::table('fa_zz_temp1')->column('sku');
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
        $skus = Db::table('fa_zz_temp1')->column('sku');
        foreach ($skus as $k => $v) {
            //同步对应SKU库存
            //更新商品表商品总库存
            //总库存
            $item_map['sku'] = $v;
            $item_map['is_del'] = 1;
            if (!$v) {
                continue;
            }
            //可用库存
            $stockList = $item->where($item_map)->field('available_stock,stock,distribution_occupy_stock')->find();
            $available_stock = $stockList['available_stock'];
            $real_stock = $stockList['stock'] - $stockList['distribution_occupy_stock'];

            $item_platform_sku = $platform->where('sku', $v)->order('stock asc')->field('sku,platform_type,stock,platform_sku')->select();
            if (!$item_platform_sku) {
                continue;
            }
            //平台个数
            $all_num = count($item_platform_sku);

            $whole_num = $platform
                ->where('sku', $v)
                ->field('stock')
                ->select();

            //绝对值总库存
            $num_num = 0;
            foreach ($whole_num as $kk => $vv) {
                $num_num += abs($vv['stock']);
            }

            $stock_num = $available_stock;
            /**
             * 跑脚本逻辑
             * 1、可用库存 < 0时,按现有库存比例分配
             * 2、可用库存 = 0时,虚拟仓库存全为0
             * 3、可用库存 < 0时,无库存超卖情况,按对应站点订单占用分配虚拟仓库存
             */

            if ($available_stock > 0) {
                foreach ($item_platform_sku as $key => $val) {
                    //最后一个站点 剩余数量分给最后一个站
                    if (($all_num - $key) == 1) {
                        $platform->where(['sku' => $v, 'platform_type' => $val['platform_type']])->update(['stock' => $stock_num]);
                    } else {
                        if ($num_num == 0) {
                            $rate_rate = 1 / $all_num;
                            $num = round($available_stock * $rate_rate);
                        } else {
                            $rate_rate = abs($val['stock']) / $num_num;
                            $num = round($available_stock * $rate_rate);
                        }

                        $stock_num -= $num;
                        $platform->where(['sku' => $v, 'platform_type' => $val['platform_type']])->update(['stock' => $num]);
                    }
                }
            } elseif ($available_stock == 0) {
                $platform->where(['sku' => $v])->update(['stock' => 0]);
            } elseif ($available_stock < 0) {
                if ($real_stock > 0) {
                    $available_num = abs($available_stock);
                    $skus = $platform->where(['sku' => $v])->column('platform_sku');
                    $map['a.sku'] = ['in', $skus];
                    $map['b.status'] = ['in', ['processing', 'complete', 'delivered', 'delivery']];
                    $map['a.distribution_status'] = ['<>', 0]; //排除取消状态
                    $map['c.check_status'] = 0; //未审单计算订单占用
                    $map['b.created_at'] = ['between', [strtotime('2021-01-01 00:00:00'), time()]]; //时间节点
                    $map['c.is_repeat'] = 0;
                    $map['c.is_split'] = 0;
                    $map['a.distribution_status'] = ['<=', 2];
                    $sql = $this->orderitemprocess->alias('a')->where($map)
                        ->join(['fa_order' => 'b'], 'a.order_id = b.id')
                        ->join(['fa_order_process' => 'c'], 'a.order_id = c.order_id')
                        ->field('a.id,a.site')
                        ->order('b.created_at desc')
                        ->limit($available_num)
                        ->buildSql();

                    $occupyList = Db::connect('database.db_mojing_order')->table($sql . ' d')->field('count(1) as num,d.site')->group('d.site')->select();
                    foreach ($occupyList as $keys => $value) {
                        $platform->where(['sku' => $v, 'platform_type' => $value['site']])->update(['stock' => '-' . $value['num']]);
                    }

                } else {
                    foreach ($item_platform_sku as $key => $val) {
                        $map['a.sku'] = $val['platform_sku'];
                        $map['b.status'] = ['in', ['processing', 'complete', 'delivered', 'delivery']];
                        $map['c.check_status'] = 0; //未审单计算订单占用
                        $map['b.created_at'] = ['between', [strtotime('2021-01-01 00:00:00'), time()]]; //时间节点
                        $map['c.is_repeat'] = 0;
                        $map['c.is_split'] = 0;
                        $map['a.site'] = $val['platform_type'];
                        $map['a.distribution_status'] = ['in', [1, 2]];
                        $occupy_stock = $this->orderitemprocess->alias('a')->where($map)
                            ->join(['fa_order' => 'b'], 'a.order_id = b.id')
                            ->join(['fa_order_process' => 'c'], 'a.order_id = c.order_id')
                            ->count(1);
                        $platform->where(['sku' => $v, 'platform_type' => $val['platform_type']])->update(['stock' => '-' . $occupy_stock]);
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
            $map['b.status'] = ['in', ['processing', 'paypal_reversed', 'paypal_canceled_reversal', 'complete', 'delivered', 'delivery']];
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
            $map['b.status'] = ['in', ['processing', 'paypal_reversed', 'paypal_canceled_reversal', 'complete', 'delivered', 'delivery']];
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
        $where['a.status'] = ['in', ['processing', 'paypal_reversed', 'paypal_canceled_reversal', 'complete', 'delivered', 'delivery']];
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
            $storehouse->where(['type' => 1,'stock_id' => 1, 'area_id' => 3, 'coding' => $v['store_house']])->update(['picking_sort' => $v['sort']]);
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
                ->where(['b.status' => ['in', ['processing', 'paypal_reversed', 'paypal_canceled_reversal', 'complete', 'delivered', 'delivery']]])
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
     * @param array $params
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

        if ($od_sph == 0 && ($os_sph > 0 || $os_sph < 0)) {
            $list['is_prescription_abnormal'] = 1;
        }

        if ($os_sph == 0 && ($od_sph > 0 || $od_sph < 0)) {
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
        $params['od_sph'] = '0.00';
        $params['os_sph'] = '0';
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
            ->where(['is_del' => 1, 'category_id' => ['<>', 43], 'sku' => 'FP0044-09'])
            ->column('stock,distribution_occupy_stock', 'sku');
        $list = $barcode
            ->alias('a')
            ->field('sku,count(1) as stock')
            ->where(['a.library_status' => 1])
            ->where(['b.status' => 2, 'a.sku' => 'FP0044-09'])
            ->where(['a.location_code_id' => ['>', 0]])
            ->join(['fa_in_stock' => 'b'], 'a.in_stock_id=b.id')
            ->where("a.item_order_number=''")
            ->group('sku')
            ->select();
        $list = collection($list)->toArray();
        foreach ($list as $k => &$v) {
            $v['real_stock'] = $data[$v['sku']]['stock'] - $data[$v['sku']]['distribution_occupy_stock'];
            if ($v['real_stock'] == $v['stock']) {
                unset($list[$k]);
            }
            unset($v['real_stock']);
        }
        $list = array_values($list);
        Db::table('fa_zz_temp1')->query('truncate table fa_zz_temp1');
        Db::table('fa_zz_temp1')->insertAll($list);
    }


    public function test002()
    {
        $barcode = new \app\admin\model\warehouse\ProductBarCodeItem();
        $list = $barcode
            ->alias('a')
            ->field('a.id,a.check_id')
            ->where(['a.in_stock_id' => 0, 'a.check_id' => ['>', 0], 'b.status' => 2, 'b.is_stock' => 1, 'a.library_status' => 1])
            ->join(['fa_check_order' => 'b'], 'a.check_id=b.id')
            ->select();

        foreach ($list as $k => $v) {
            $id = Db::table('fa_in_stock')->where(['check_id' => $v['check_id']])->value('id');
            $barcode->where(['id' => $v['id']])->update(['in_stock_id' => $id]);
            echo $k . "\n";
        }
        echo "ok";
    }

    /**
     * 同步系统中工单数据
     *
     * @author fangke
     * @date   2021/7/20 9:45 上午
     */
    public function syncAllZendeskTicker()
    {
        for ($i = 4; $i < 8; $i++) {
            $startTime = "2021-0{$i}-01 00:00:00";
            $endTime = "2021-0{$i}-31 23:59:59";
            $zendeskTickets = (new Zendesk())->field(['ticket_id', 'type', 'id'])
                ->whereTime('update_time', [$startTime, $endTime])
                ->select();
            /** @var Zendesk $ticket */
            foreach ($zendeskTickets as $ticket) {
                echo $i.'->'.$ticket->type.'->';
                $isPushed = Queue::push("app\admin\jobs\Zendesk", $ticket, "zendeskJobQueue");
                if ($isPushed !== false) {
                    echo $ticket->ticket_id."->推送成功".PHP_EOL;
                } else {
                    echo $ticket->ticket_id."->推送失败".PHP_EOL;
                }
            }
        }
    }


    public function asyncTicketHttps($type, $site, $start, $end)
    {
        echo $start . '-' . $end . "\n";
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
            echo $intersect . 'is ok' . "\n";
        }
        //新增
        foreach ($diffs as $diff) {
            (new Notice(request(), ['type' => $site, 'id' => $diff]))->create();
            echo $diff . 'ok' . "\n";
        }
        echo 'all ok';
    }


    public function test()
    {
        $type = 2;
        $site = 'voogueme';
        $start = '2021-07-19T01:00:00Z';
        $end = '2021-07-19T07:59:59Z';

        $this->asyncTicketHttps($type, $site, $start, $end);
    }

    public function test011()
    {
        $type = 2;
        $site = 'voogueme';
        for ($i = 0; $i < 7; $i++) {
            $start = '2021-07-19T' . $i . ':00:00Z';
            $end = '2021-07-19T' . $i . ':59:59Z';
            try {
                $this->asyncTicketHttps($type, $site, $start, $end);
                usleep(10000);
            } catch (Exception $e) {
                echo $e->getMessage();
            }
        }
    }
    public function test012()
    {
        $type = 3;
        $site = 'nihao';
        for ($i = 0; $i < 7; $i++) {
            $start = '2021-07-19T' . $i . ':00:00Z';
            $end = '2021-07-19T' . $i . ':59:59Z';
            try {
                $this->asyncTicketHttps($type, $site, $start, $end);
                usleep(10000);
            } catch (Exception $e) {
                echo $e->getMessage();
            }
        }
    }
    public function test013()
    {
        $type = 1;
        $site = 'zeelool';
        for ($i = 4; $i < 7; $i++) {
            $start = '2021-07-19T' . $i . ':00:00Z';
            $end = '2021-07-19T' . $i . ':03:59Z';
            try {
                $this->asyncTicketHttps($type, $site, $start, $end);
                usleep(10000);
            } catch (Exception $e) {
                echo $e->getMessage();
            }
            $start = '2021-07-19T' . $i . ':04:00Z';
            $end = '2021-07-19T' . $i . ':59:59Z';
            try {
                $this->asyncTicketHttps($type, $site, $start, $end);
                usleep(10000);
            } catch (Exception $e) {
                echo $e->getMessage();
            }
        }
    }

    /**
     * 每天 凌晨 1点同步 UTC时间前一天的数据
     * @author crasphb
     * @date   2021/5/18 17:26
     */
    public function asyncZendeskZeeloolDay()
    {
        $type = 1;
        $site = 'zeelool';
        $dayBefore = date('Y-m-d', strtotime('-2 day'));
        $dayNow = date('Y-m-d', strtotime('-1 day'));
        //UTC时间前一天的数据
        for ($i = 16; $i < 24; $i++) {
            $start = $dayBefore . 'T' . $i . ':00:00Z';
            $end = $dayBefore . 'T' . $i . ':59:59Z';
            try {
                $this->asyncTicketHttps($type, $site, $start, $end);
                usleep(10000);
            } catch (Exception $e) {
                echo $e->getMessage();
            }
        }
        //同步当天0点到16点的
        for ($i = 0; $i < 16; $i++) {
            $start = $dayNow . 'T' . $i . ':00:00Z';
            $end = $dayNow . 'T' . $i . ':59:59Z';
            try {
                $this->asyncTicketHttps($type, $site, $start, $end);
                usleep(10000);
            } catch (Exception $e) {
                echo $e->getMessage();
            }
        }
    }

    /**
     * 判断定制现片逻辑
     */
    public function set_processing_type($params = [])
    {
        $arr = [];
        //判断处方是否异常
        $list = $this->is_prescription_abnormal($params);
        $arr = array_merge($arr, $list);

        $arr['order_prescription_type'] = 0;
        //仅镜框
        if ($params['lens_number'] == '10000000' || !$params['lens_number']) {
            $arr['order_prescription_type'] = 1;
        } else {
            $od_sph = (float)urldecode($params['od_sph']);
            $os_sph = (float)urldecode($params['os_sph']);
            $od_cyl = (float)urldecode($params['od_cyl']);
            $os_cyl = (float)urldecode($params['os_cyl']);
            //判断是否为现片，其余为定制
            $lensData = LensPrice::where(['lens_number' => $params['lens_number'], 'type' => 1])->select();
            $tempArr = [];
            foreach ($lensData as &$v) {
                if (!$v['sph_start']) {
                    $v['sph_start'] = $v['sph_end'];
                }

                if (!$v['cyl_start']) {
                    $v['cyl_start'] = $v['cyl_end'];
                }

                $v['sph_start'] = (float)$v['sph_start'];
                $v['sph_end'] = (float)$v['sph_end'];
                $v['cyl_start'] = (float)$v['cyl_start'];
                $v['cyl_end'] = (float)$v['cyl_end'];

                if ($od_sph >= $v['sph_start'] && $od_sph <= $v['sph_end'] && $od_cyl >= $v['cyl_start'] && $od_cyl <= $v['cyl_end']) {
                    $tempArr['od'] = 1;
                }

                if ($os_sph >= $v['sph_start'] && $os_sph <= $v['sph_end'] && $os_cyl >= $v['cyl_start'] && $os_cyl <= $v['cyl_end']) {
                    $tempArr['os'] = 1;
                }
            }


            if ($tempArr['od'] == 1 && $tempArr['os'] == 1) {
                $arr['order_prescription_type'] = 2;
            }
        }

        //默认如果不是仅镜架 或定制片 则为现货处方镜
        if ($arr['order_prescription_type'] != 1 && $arr['order_prescription_type'] != 2) {
            $arr['order_prescription_type'] = 3;
        }

        return $arr;
    }

    /**
     * 测试镜片处方
     * @author wpl
     * @date   2021/5/6 13:54
     */
    public function test_lens()
    {
        $params['od_sph'] = '-7.25';
        $params['os_sph'] = '-7.50';
        $params['od_cyl'] = '-1.50';
        $params['os_cyl'] = '-2.50';
        $params['pd'] = 60;
        $params['lens_number'] = 24200000;
        $data = $this->set_processing_type($params);
        dump($data);
        die;
    }

    public function test02()
    {
        $date_time_start = date('Y-m-d 00:00:00');
        $date_time_end = date('Y-m-d 23:59:59');
        $today_sales_money_sql = "SELECT round(sum(base_grand_total),2)  base_grand_total FROM sales_flat_order WHERE created_at between '$date_time_start' and '$date_time_end' $order_status";
        echo $today_sales_money_sql;
        die;
    }


    /**
     * 库位排序
     * @author wpl
     * @date   2021/6/5 10:26
     */
    public function set_store_sort()
    {
        $stock_house = new StockHouse();
        $list = Db::table('fa_zz_temp2')->select();
        foreach ($list as $k => $v) {
            $stock_house->where(['stock_id' => 2, 'coding' => $v['store_house']])->update(['picking_sort' => $v['sort']]);
            echo $k . "\n";
        }
        echo "ok";
    }


    public function set_order_sort()
    {
        $order = new NewOrderItemProcess();
        $list = $order->where(['stock_id' => 2])->select();
        $itemPlatform = new ItemPlatformSku();
        foreach ($list as $k => $v) {
            //转换平台SKU
            $sku = $itemPlatform->getTrueSku($v['sku'], $v['site']);
            //根据sku查询库位排序
            $stockSku = new StockSku();
            $where = [];
            $where['c.type'] = 2;//默认拣货区
            $where['b.status'] = 1;//启用状态
            $where['a.is_del'] = 1;//正常状态
            $where['b.stock_id'] = 2;//查询对应仓库
            $location_data = $stockSku
                ->alias('a')
                ->where($where)
                ->where(['a.sku' => $sku])
                ->field('b.coding,b.picking_sort')
                ->join(['fa_store_house' => 'b'], 'a.store_id=b.id')
                ->join(['fa_warehouse_area' => 'c'], 'b.area_id=c.id')
                ->find();
            $order->where(['id' => $v['id']])->update(['picking_sort' => $location_data['picking_sort']]);
        }

    }


    /**
     * 库位排序
     * @author wpl
     * @date   2021/6/5 10:26
     */
    public function set_store_house()
    {
        $stock_house = new StockHouse();
        $list = Db::table('fa_zz_temp2')->select();
        foreach ($list as $k => $v) {
            $params = [];
            $params['coding'] = $v['store_house'];
            $params['status'] = 1;
            $params['createtime'] = date('Y-m-d H:i:s');
            $params['create_person'] = 'admin';
            $params['type'] = 3;
            $params['shelf_number'] = 'D';
            $params['volume'] = 5;
            $params['stock_id'] = 2;
            $stock_house->insert($params);
            echo $k . "\n";
        }
        echo "ok";
    }


    /**
     * 导出数据-定期清理不卖的SKU
     * @throws \think\db\exception\BindParamException
     * @throws \think\exception\PDOException
     * @author wangpenglei
     * @date   2021/6/18 9:00
     */
    public function export_sku_data()
    {
        $order = new \app\admin\model\order\order\NewOrderItemProcess();
        $itemplatformsku = new \app\admin\model\itemmanage\ItemPlatformSku;
        $this->item = new \app\admin\model\itemmanage\Item;
        $productbarcode = new ProductBarCodeItem();
        $headList = ['SKU', '仓库', '总库存', '仓库实时库存', '大货区库存', '货架区库存', '拣货区库存', '最近1个月的销量'];
        $z = 0;
        $this->item->where(['is_open' => 1, 'is_del' => 1, 'category_id' => ['<>', 43]])->chunk(1000, function ($row) use ($productbarcode,$itemplatformsku,$order,$headList,&$z) {
            $data = [];
            $stock_id = [1, 2];
            $i = 0;
            foreach ($row as $key => $val) {
                $skus = [];
                $skus = $itemplatformsku->where(['sku' => $val['sku']])->column('platform_sku');

                //查询最近一个月销量
                $order_where['b.status'] = [
                    'in',
                    [
                        'processing',
                        'complete',
                        'delivered',
                        'delivery',
                    ],
                ];
                $order_where['b.created_at'] = ['between', [strtotime('2021-05-18'), strtotime('2021-06-18') + 86399]];
                $order_where['a.sku'] = ['in', $skus];

                $order_num = $order->alias('a')->where($order_where)->where('order_type', 1)
                    ->join(['fa_order' => 'b'], 'a.order_id = b.id')
                    ->count(1);

                foreach ($stock_id as $k => $v) {
                    $data[$i]['sku'] = $val['sku'];
                    $data[$i]['stock_id'] = $v == 1 ? '郑州' : '丹阳';
                    $data[$i]['stock'] = $val['stock'];
                    $data[$i]['real_stock'] = $productbarcode
                        ->where(['sku' => $val['sku'],'stock_id' => $v,'library_status' => 1])
                        ->where("item_order_number=''")
                        ->count();

                    $dahuo_location_id = $v == 1 ? 1 : 4;
                    $data[$i]['dahuo_stock'] = $productbarcode
                        ->where(['sku' => $val['sku'],'stock_id' => $v,'library_status' => 1, 'location_id' => $dahuo_location_id])
                        ->where("item_order_number=''")
                        ->count();

                    $huojia_location_id = $v == 1 ? 2 : 5;
                    $data[$i]['huojia_stock'] = $productbarcode
                        ->where(['sku' => $val['sku'],'stock_id' => $v,'library_status' => 1, 'location_id' => $huojia_location_id])
                        ->where("item_order_number=''")
                        ->count();

                    $jianhuojia_location_id = $v == 1 ? 3 : 6;
                    $data[$i]['jianhuojia_stock'] = $productbarcode
                        ->where(['sku' => $val['sku'],'stock_id' => $v,'library_status' => 1, 'location_id' => $jianhuojia_location_id])
                        ->where("item_order_number=''")
                        ->count();
                    $data[$i]['xiaoliang'] = $order_num;

                    $i++;
                }
            }

            if ($z > 0) {
                $headList = [];
            }

            $z++;
            Excel::writeCsv($data, $headList, '/uploads/financeCost/sku.csv', false);
        });

    }

    /**
     * 更新V站每天订单数
     * @author wangpenglei
     * @date   2021/6/18 10:04
     */
    public function update_voogueme_order()
    {
        $order = new NewOrder();
        $dataCenter = new DatacenterDay();
        $list = $dataCenter->where(['site' => 2, 'day_date' => ['>=', '2021-05-01']])->select();
        foreach ($list as $k => $v) {
            $order_where['status'] = [
                'in',
                [
                    'processing',
                    'complete',
                    'delivered',
                    'delivery',
                ],
            ];
            $order_where['created_at'] = ['between', [strtotime($v['day_date']), strtotime($v['day_date']) + 86399]];
            $order_where['site'] = 2;
            $arr = [];
            $arr['order_num'] = $order->where($order_where)->where('order_type', 1)->count();
            //销售额
            $arr['sales_total_money'] = $order->where($order_where)->where('order_type',
                1)->sum('base_grand_total');
            //邮费
            $arr['shipping_total_money'] = $order->where($order_where)->where('order_type',
                1)->sum('base_shipping_amount');
            $arr['order_unit_price'] = $arr['order_num'] == 0 ? 0 : round($arr['sales_total_money'] / $arr['order_num'], 2);
            //中位数
            $sales_total_money = $order->where($order_where)->where('order_type', 1)->column('base_grand_total');
            $arr['order_total_midnum'] = $this->median($sales_total_money);
            //标准差
            $arr['order_total_standard'] = $this->getVariance($sales_total_money);
            //补发订单数
            $arr['replacement_order_num'] = $order->where($order_where)->where('order_type', 4)->count();
            //补发销售额
            $arr['replacement_order_total'] = $order->where($order_where)->where('order_type',
                4)->sum('base_grand_total');
            //网红订单数
            $arr['online_celebrity_order_num'] = $order->where($order_where)->where('order_type', 3)->count();
            //补发销售额
            $arr['online_celebrity_order_total'] = $order->where($order_where)->where('order_type',
                3)->sum('base_grand_total');

            $dataCenter->where('id', $v['id'])->update($arr);
        }
    }


    /**
     *计算中位数 中位数：是指一组数据从小到大排列，位于中间的那个数。可以是一个（数据为奇数），也可以是2个的平均（数据为偶数）
     */
    public function median($numbers)
    {
        sort($numbers);
        $totalNumbers = count($numbers);
        $mid = floor($totalNumbers / 2);

        return ($totalNumbers % 2) === 0 ? ($numbers[$mid - 1] + $numbers[$mid]) / 2 : $numbers[$mid];
    }

    /**
     * @param $arr
     *
     * @return float|int
     * @author wangpenglei
     * @date   2021/6/18 10:52
     */
    public function getVariance($arr)
    {
        $length = count($arr);
        if ($length == 0) {
            return 0;
        }
        $average = array_sum($arr) / $length;
        $count = 0;
        foreach ($arr as $v) {
            $count += pow($average - $v, 2);
        }
        $variance = $count / $length;

        return sqrt($variance);
    }


    public function testBatchAddWorkList()
    {
        $incrementId = input('id');
        echo $incrementId;
        $this->batchAddWorkList([$incrementId]);
    }

    /**
     * 批量添加工单
     * @author wangpenglei
     * @date   2021/6/25 14:45
     */
    public function batchAddWorkList()
    {

        $increment_id = Db::table('fa_zzzz_es')->column('sku');

        $order = new NewOrder();
        $list = $order->where(['increment_id' => ['in', $increment_id]])->column('*', 'increment_id');
        $work = new WorkOrderList();
        $measure = new WorkOrderMeasure();
        $recept = new WorkOrderRecept();
        $wangwei = new Wangwei();
        $work->startTrans();
        $measure->startTrans();
        $recept->startTrans();
        try {
            $i = 0;
            foreach ($increment_id as $k => $v) {

                $params = [];
                $params['work_platform'] = $list[$v]['site'];
                $params['work_type'] = 1;
                $params['platform_order'] = $v;
                $params['order_pay_currency'] = $list[$v]['order_currency_code'];
                $params['order_pay_method'] = $list[$v]['payment_method'];
                $params['base_grand_total'] = $list[$v]['base_grand_total'];
                $params['grand_total'] = $list[$v]['grand_total'];
                $params['base_to_order_rate'] = $list[$v]['base_to_order_rate'];
                $params['work_status'] = 6;
                $params['problem_type_id'] = 9;
                $params['problem_type_content'] = '物流超时';
                $params['problem_description'] = '物流超时';
                $params['create_user_id'] = 75;
                $params['create_user_name'] = '王伟';
                $params['after_user_id'] = 75;
                $params['all_after_user_id'] = 75;
                $params['payment_time'] = date('Y-m-d H:i:s');
                $params['create_time'] = date('Y-m-d H:i:s');
                $params['complete_time'] = date('Y-m-d H:i:s');
                $params['email'] = $list[$v]['customer_email'];
                $params['recept_person_id'] = 75;
                $params['stock_id'] = $list[$v]['stock_id'];
                $params['order_item_numbers'] = '';


                $id = $work->insertGetId($params);
                if (!$id) {
                    throw new Exception('插入失败');
                }

                $measureParams = [];
                $measureParams['work_id'] = $id;
                $measureParams['measure_choose_id'] = 7;
                $measureParams['measure_content'] = '补发';
                $measureParams['create_time'] = date('Y-m-d H:i:s');
                $measureParams['operation_type'] = 1;
                $measureParams['operation_time'] = date('Y-m-d H:i:s');
                $measureParams['sku_change_type'] = 5;
                $measureId = $measure->insertGetId($measureParams);
                if (!$measureId) {
                    throw new Exception('插入失败');
                }

                $receptParams = [];
                $receptParams['work_id'] = $id;
                $receptParams['measure_id'] = $measureId;
                $receptParams['recept_status'] = 1;
                $receptParams['recept_group_id'] = 0;
                $receptParams['recept_person_id'] = 75;
                $receptParams['recept_person'] = '王伟';
                $receptParams['create_time'] = date('Y-m-d H:i:s');
                $receptParams['finish_time'] = date('Y-m-d H:i:s');
                $receptId = $recept->insertGetId($receptParams);
                if (!$receptId) {
                    throw new Exception('插入失败');
                }

                $wangwei->createOrder($list[$v]['site'], $id, $v, $measureId);

                if ($i == 100) {
                    $i = 0;
                    $work->commit();
                    $measure->commit();
                    $recept->commit();
                }
                $i++;
            }
            $work->commit();
            $measure->commit();
            $recept->commit();
        } catch (Exception $exception) {
            // 回滚事务
            $work->rollback();
            $measure->rollback();
            $recept->rollback();
            echo $exception->getMessage();
        }


    }
    public function test11111111()
    {
        $dayBefore = date('Y-m-d', strtotime('-2 day','1624899600'));
        $dayNow = date('Y-m-d', strtotime('-1 day','1624899600'));
        echo $dayBefore . '---- ' . $dayNow;
    }

    /**
     * 加诺订单修改物流内容
     *
     * @throws Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * @author huangbinbin
     * @date   2021/7/22 14:45
     */
    public function deliveryTest()
    {
        ini_set('memory_limit','-1');
        $orderNodes = OrderNode::where('shipment_data_type','加诺')->whereTime('delivery_time','>','2021-06-20 00:00:00')->select();
        $orderNumbers = array_column($orderNodes,'order_number');
        $orders = Db::connect('database.db_mojing_order')->table('fa_order')->where('increment_id','in',$orderNumbers)->field('increment_id,country_id,region')->select();
        foreach($orderNodes as $orderNode) {
            foreach($orders as $order) {
                if($order['increment_id'] != $orderNode['order_number']) continue;
                $shipment_data_type='加诺-其他';
                if (!empty($order['country_id']) && $order['country_id']=='US'){
                    //美国
                    $shipment_data_type='加诺-美国';
                    if ($order['region']=='PR'||$order['region']=='Puerto Rico'){
                        //波多黎各
                        $shipment_data_type ='加诺-波多黎各';
                    }
                }
                if (!empty($order['country_id']) && $order['country_id']=='CA'){
                    //加拿大
                    $shipment_data_type ='加诺-加拿大';
                }

                if (!empty($order['country_id']) && $order['country_id']=='PR'){
                    //波多黎各
                    $shipment_data_type ='加诺-波多黎各';
                }
                echo $shipment_data_type .PHP_EOL;
                echo $orderNode['id'] .PHP_EOL;
                echo $orderNode['order_number'] .PHP_EOL;
                //修改节点信息
                OrderNode::where('id',$orderNode['id'])->setField('shipment_data_type',$shipment_data_type);
            }
        }
    }

    /**
     * 王伟加诺补发的订单
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * @author huangbinbin
     * @date   2021/7/27 9:04
     */
    public function jianuoWorkOrderList()
    {
        $incrementIds = [400621600,400621249,400613640,400620841,100253898,100255020,100255237,430348658,400606998,430346855,530017367,130112675,430346448,400620855,430349381,130112663,400621051,400620483,400620503,130110556,400620185,100255093,400615514,100255112,430349527,400620947,430346151,400621384,430349568,100253422,130113716,400620588,130112741,530017882,400620391,130113509,130113077,430349394,530017846,430344992,430349225,430349413,400616043,430349735,430349287,430346675,130112637,430346872,400618968,400621352,400621009,430349957,430349663,430346209,130111792,430349406,400619872,100253551,500036861,100254189,100255034,400615830,400616628,430349518,500037451,400608721,400620869,400599287,400622613,430349759,430347081,430350432,430346972,100255427,400616853,400622963,400612046,130112926,500035310,430349878,430350194,400595360,130113004,400616319,430344564,130113875,130113171,400608709,400616914,400621464,430351386,430350845,430348668,430350789,430350680,430348714,530017564,100256549,130113936,130113948,400588386,400595991,500037531,400622683,500034292,400593167,400593667,500037553,430344720,430348720,430348526,430350776,430351256,430350756,400622653,130113937,430350691,430350943,100254361,400623521,100254015,400618499,430351020,400622569,130113228,530017689,430350870,430349630,430348054,430349871,430351024,400623743,430350334,430351826,430351706,400626000,100256998,430351967,130114256,430349985,430351684,430349981,430351656,400624505,100256808,400619963,430350960,130114258,430350967,430349275,130114021,100256075,400293095,430347493,430350987,430348617,430351089,430351320,430349239,430351062,430351410,430351674,600154247,400623050,430350749,430350722,430351034,430351165,430352031,430351049,430350710,430351326,130114417,130113970,430348988,430351391,130114059,430351303,430351702,130113406,430351815,600153852,430351408,430351406,430351364,130113955,430351304,400623183,430350640,430351021,130113213,430347263,430350363,430347170,400616479,430349882,400584418,400622484,400622462,400370146,400620741,100256365,130113570,430347540,430350278,500037471,130110811,430351136,430351066,130112974,430351097,430348436,130113900,130113446,490001367,490001384,490001352,490001287,100255913,430349547,190000204,430350467,430350618,430347086,430350707,130114050,430349923,130113632,430346700,430351088,400619139,130113208,130113851,430351000,430350544,430350269,430350495,430350854,430350471,430351260,100255228,430348256,400622589,430347817,300051069,430350404,100255786,430347172,130114115,100255771,400623263,100255692,100255120,100255943,400621410,100255736,100253864,400625089,400626039,530017959,400621225,400624160,400621928,100257112,100252849,400625162,100253899,400621027,100255802,100255377,430351177,400621233,100255075,430351875,430345442,430345468,530016517,430345199,130112399,430344718,130112064,400625677,430345090,130114108,100250118,430351807,430342437,400624494,430351672,400612479,130111344,430344610,100256275,400613071,400618172,400620140,130114325,400608364,100250475,100250624,130114227,400613332,430342524,500037248,100252290,500036379,400603926,400613344,430341662,400607938,400613083,430344756,400623383,500037681,430344793,500036943,400613022,100255293,130113094,600153579,600153580,400617684,430349658,100256241,430351588,430352063,430348961,400620455,130114285,100250199,400624935,400625177,400624861,100256846,400625311,400625041,400623328,400625385,430350230,400616644,130113769,430347984,400622194,130113729,430349734,430347358,100255484,430350173,100255812,430349762,400623417,530018190,400620109,400623788,400376663,400622930,430349099,400620875,430351232,430350944,130114037,430351592,130113773,130114550,430353393,430352800,430353409,430352810,400626654,130113501,400622126,600153959,130113687,130114750,430352509,130114576,130113553,500038067,430352597,130114796,430352848,430350351,430349385,430353273,400627787,130114578,130113715,430352333,100257435,430353285,430352562,130114537,430352567,430352262,500037514,430352760,430351973,100255414,430349844,400626017,130113835,400626385,430352575,130113676,130113666,430352573,430350436,430353155,430352433,430353444,100257234,130113623,400625819,430351903,400620276,130114731,130114562,430352455,430352664,600154183,430351500,100257315,430351873,430352143,100256360,430350584,400623990,430350287,430350138,530018149,400625065,400624435,430351776,400619712,400623778,400619899,400623232,400623961,100256768,400625285,400623789,400620248,430352649,530017329,400624631,430352058,400623580,400618513,430352701,400625126,500037731,400623760,400627660,430352439,100255976,430351732,400627448,130113992,400623917,130113513,430351578,400620173,100254079,400627340,400624891,100255562,100256008,400624699,100256002,100257047,100249542,130114239,500037578,400623951,430351589,130114245,400622082,430348647,430351397,400621700,430348599,430351801,400627374,430350708,430351413,430353435,130114538,130113291,130114056,430351432,430351387,100257323,430351298,430346539,400627914,400628216,400628171,400591855,400626379,530018491,400627465,400623880,430348741,100257032,430351131,100257332,400230901,400623928,430353367,430351988,430351382,130114027,430351371,430351299,430348848,130113561,400626046,400628003,100257182,400625852,400626060,100256643,430349625,400622505,400626592,400626027,430323393,430340416,400626273,130114154,130113462,100257379,430352655,430351227,430345218,430351874,400625687,430352118,130114573,430352219,400620091,400625605,130113210,430352110,430352025,430352170,400620852,430351791,430352078,130114363,430352172,130113599,430352488,430352014,430351025,500035624,430352623,430349429,130114283,400625721,430352107,430352120,400593708,500016236,400595291,400596249,400619637,430343276,100254903,400615583,400619790,430348731,400620033,400625837,100255663,400625459,400625931,400626499,400626111,400627224,530015107,100255607,400618540,500037599,400627100,530018355,500012090,400625457,400391045,400619080,400626202,600154500,400621052,400625670,400625618,300051213,600154333,600154379,600149492,600154431,300051053,600154414,600153744,300051193,600154433,600153531,300051048,600154407,300051243,600154430,430352227,600154389,600154440,600154511,600154409,100257310,430352059,430351890,400626760,500037948,430352402,400626844,130114457,430352332,400625699,430347603,400625370,400625583,400625532,400621361,100255164,400625646,400625528,400625681,400624946,430352142,400625622,100254769,400625824,100256944,130113611,400623776,430352248,430352300,430352140,430351633,400625394,100257006,400625214,430351812,400623586,500037957,400625103,130112807,430351999,430348101,500037457,530017841,430351997,400625745,430351722,500037862,400625693,400623283,400624807,400624479,400608638,430349947,100255226,130114313,430351556,400624747,130112631,400616285,130114288,430351491,100256878,400624830,430351035,400625704,400624241,100254691,100256462,400622474,530018354,100256540,400625295,400561064,500037757,400625682,130114215,430333661,130112046,430351246,130114001,400618945,400617848,100256100,130112579,130113888,400624109,100256054,500037597,400622933,500037660,400623161,400349417,400622391,100252803,100256553,400623155,400621324,430349775,130113813,400616573,430348003,400621520,400616813,130113822,430349756,430349760,130112869,500037380,430349679,430349421,430347533,130113001,130107200,430347256,430349819,100254003,130113076,130113692,430347619,100255059,400620847,400616878,530017480,500036654,100253555,500035411,400620821,100254017,400620723,400620801,400621087,530017889,100255347,400621111,430349798,130112596,430345746,430349647,430348690,430341080,430349374,130113612,130113012,430350172,400619656,130113774,430345156,430346630,100255978,400622694,100254124,500036874,400623304,400618036,400621430,430350522,500033759,130113154,400618564,130113857,100255744,500037506,100254167,430347878,530017995,130112898,400620938,430347714,100256198,100253855,430350332,400618315,400622355,400622318,400618274,400621878,130114015,100253211,400618321,400622339,400622240,400622275,530018012,430350464,400617765,530018038,400589163,100253154,400616902,400621885,400622199,100255814,530018003,400621022,400618235,400622123,100255619,530018052,400621432,100255665,400621990,400617782,100254140,500037546,500037079,400624933,430351043,430350879,530018168,430350500,530016756,430351705,400624615,130110188,430351530,430350971,100255446,430349420,400625529,100257147,430351398,430351396,530016765,130113897,430351231,130114231,100254160,430351242,400625020,190000193,490001276,190000212,400625260,430351084,430351056,430350733,130114177,430351167,430351210,300051165,130113906,430351938,430345923,430351145,400625175,400625223,430351414,600154210,400623637,430351989,430351296,600154115,400622875,100254609,100137712,400618602,400622530,400622953,130113313,100256061,130114212,100256339,430351540,130113512,130113950,430350837,430351237,430351435,430350454,400622700,500037071,400623387,130114185,530018241,400622251,600153726,100255908,400621300,100255762,130113980,130114206,130108587,430350766,100256153,430351349,400622517,130108505,400623135,400623022,400622964,430351482,100254556,400623893,100256251,430351690,400625653,430351509,430351636,400624890,430350717,100256695,130114323,400619802,530018359,400624245,400619731,430351362,430351713,400623773,430351859,400624343,430351696,400623595,400625348,400624482,400625172,100257014,130114434,430351855,100257084,430351772,400624736,400611938,430351843,430351861,400624413,430351492,530018197,130113652,430349212,130114278,400618954,100255426,400624257,400620069,500037343,400623421,430352122,530018394,400624322,400619866,400624197,100255048,130114314,400623241,400623585,500037228,400625131,100256755,400622499,400625766,400624368,400624688,400624264,400620359,100255888,400623913,530017683,400623248,100255355,400623799,400624122,400624633,400621341,100256743,400620667,400624360,100255011,530018260,400624710,400624805,400622415,100256713,400624662,500037850,400622419,400623579,100257016,400624236,100256409,400623292,400624550,400598707,430351621,100256740,400623650,100255893,500037396,400618610,530018163,400620564,530015171,430346311,430351270,430347205,430346936,430349537,130113761,400624146,400619562,430348939,400625697,430351057,400624027,130114326,130113602,400625507,400625692,500037272,130114306,130114194,130111789,400624196,500037263,400607480,100256575,100256088,530018054,400622246,400617530,430348949,400622955,530018009,400623952,400622630,100256444,100256162,500037084,430350982,600154093,600154202,600154125,300051116,600154149,400598592,300047258,600154299,400624356,100255042,430351124,130113597,400622939,430349253,430348821,130113586,400622767,100250877,600154154,400622858,600153530,400624043,300051148,600154122,300050981,600154313,100256508,600154124,100256592,500037576,600154286,600154055,600154281,430348408,600153724,300051088,300051011,600154190,600153926,600154082,600153695,600154157,600154212,100255116,600154201,100254591,100256000,600153769,400622664,430350820,100256113,400622249,430350805,430350818,400621477,430350135,430349933,400622103,400621125,130113710,130113763,430350426,430343187,130113192,430350738,430350213,430350422,430350352,400621588,430350392,400621256,430347938,530018015,430343030,430350062,430350197,400622053,130113742,430350208,400621764,500037581,130113034,400622542,430349822,130114128,400622394,530018237,130113892,530018131,400624433,530017902,430328563,100254624,430350774,100255319,430348239,430350827,430350318,400622695,400619098,430351288,400624153,100253990,400622659,130111562,100256077,530017767,430350740,430351447,500037547,400618319,530017661,400622024,130113791,400622253,400622212,430349711,400620695,400617921,400618043,400622165,430350431,430347419,430347810,130111977,430349965,430350180,430349548,430347962,400615775,100255627,100253927,400622090,400621767,400617611,430350108,400615526,130113883,400617630,400617682,100253152,430350090,500037007,130113816,130113768,400621856,500036882,430347983,430349799,400621554,100253783,400621862,400621852,400622975,430350748,530017892,400622717,430350152,400621800,130113744,400621748,400620235,100231194,430346279,130111398,530017968,100255138,100255517,130113840,430345873,430350068,400621230,100255489,430350445,530017974,430349758,130113733,130113781,100140254,430349792,430350081,130113786,430346029,130114427,400627138,530018513,430352923,430352740,430351932,130114620,130114496,430352808,430350060,430352265,130114521,430351746,430351993,430352448,400625796,400626191,400625611,400257071,100257121,430352738,400620579,400624790,130114632,430352261,130113637,430352233,430352084,400621929,400615511,100255721,100255504,430350220,400621370,530017997,500037040,430350041,400621708,100253026,400621768,500036567,400621736,400614243,400617557,400622221,400622138,400617083,430349770,130113275,430350165,430350156,400621998,100246010,100255674,500037513,400622136,430346142,430347772,400621964,430349890,430349910,430351027,430351085,430350561,100253969,400617451,130113293,430345070,430333453,530017675,130113775,430349683,400623293,530017552,400623512,430339315,130113214,130113113,530017640,430351178,430350416,430350292,400618128,530017578,430350146,430350555,430347757,430350425,400623672,300051080,600154143,430348326,100254663,430350350,430350477,400622099,400267008,400618159,430347725,130113266,400622179,430350294,430350326,400621967,400622256,600126234,430348337,400621970,400621968,430351198,430351139,130113145,430350277,130113853,430350079,430351092,130113048,430349161,130114094,400618974,530017175,400623424,430351134,100256277,500037520,100256092,100256289,400622323,400620204,400623233,100253031,400624800,100252257,400623347,400619290,400609458,100256293,100254904,400622736,400622826,400624500,100254393,400619788,100256791,400625772,400620863,400624970,500037275,400624664,100255165,400621096,530017873,100255099,530018280,400624869,130113724,400625908,130114467,100257191,430351138,430350559,400625391,430349753,430350663,400625838,430348417,130114268,400624713,430345537,600153218,530016657,430345131,400608869,400613457,400613536,400613645,400613548,500035948,430345519,430345558,130112274,430345768,430345461,130111563,400608945,130111734,100252269,400608193,430345144,100252741,130111395,430318689,430344957,430345213,430346054,430345949,130112491,430342831,430345498,400626510,100257328,400626954,400626583,100257262,100257428,530018528,400605146,530018460,500038059,400626735,100257704,400627253,430350491,130114649,400627078,430342765,400627039,400628214,430352621,400627389,100256016,130114621,400628300,430352897,130114822,100257350,430350284,500036516,430342082,430319121,430337628,130112367,400613555,130111537,400614704,400613434,130112354,400608839,400494105,430342449,130114962,130114410,430350547,400628100,500037102,100256781,130114805,430354133,130112256,430354178,430353981,130115031,400629339,400629689,400623106,100258230,400628023,100258154,400629929,500038494,400625294,400628866,130114065,100257992,100258288,400628921,400628256,500038436,100258165,100258363,500038438,100255682,400623613,400629185,500038298,430351785,430352515,130115154,430353877,130114914,430353855,430353871,430353948,130114949,130114444,430354028,430351920,430354304,100256488,400625631,400630007,400625300,100258362,400629429,400629891,400625433,430352662,130113843,100258076,400627659,600154513,600154606,400627937,430353569,100257659,430352849,430350349,430353357,400628956,430353306,100257697,400626619,400621704,100257471,430352283,430353116,430353020,430352967,100258096,430352641,430353926,430351960,400629117,500038472,400629628,400629175,400629601,400628544,430353499,400627276,130113959,430351611,430352646,400629549,130114846,400629359,100258160,530018703,430350699,400626733,400629242,400627554,130113506,400629895,400627557,100258306,400609827,130113933,430352988,430353154,130114745,100258225,100255939,400625519,400627690,400629114,400628649,400624972,130114383,430354297,430353960,100257959,400627929,430353521,430353234,430351200,430353641,130114856,430352612,430353262,100257712,130114034,130114882,130114587,130114756,400626555,400631607,500038700,100257812,400623216,400630811,600154968,100250945,400630182,100254021,400627223,400618219,500038546,100258393,100256936,400631594,600155131,130115551,430355968,530018353,400631253,400631849,600155127,100258003,400631951,530018423,400627287,530018886,400630538,530018874,400626421,430354550,400630535,100258454,400610942,400630989,430354807,400629435,430354560,100256995,430355766,500038280,400631623,400613342,400570652,130112215,130111380,430342435,400614420,400608680,430345233,400613957,500035951,430342090,400613658,100252365,100250172,430342583,100252343,400613904,430342266,430345219,100249939,100252910,400609982,100252377,130112304,130112283,130112359,400613869,430342211,530017156,400613470,430346096,430343483,430345427,130111022,430345043,400613734,130112284,430345457,400614826,430345444,130112485,430345977,400612971,130112258,430340288,400612951,130111177,400613348,430344511,130109682,130110987,400607743,400611292,130112331,430344773,100250005,500035782,430341215,400612678,130112121,130111120,130112242,100252393,400612293,500035646,430341177,500035718,400609745,530017122,400612609,430340537,400611879,400612320,500036316,430344247,400611846,400613395,400611616,400613268,400607100,530016569,400612326,430344483,430341063,530016999,430344389,130111089,430345100,430344443,430342135,130112073,430342719,400611906,130112061,400611793,130111152,430341001,530017059,400612830,400607717,400612019,600152158,430344557,400613267,100250029,100250056,430344569,100250146,400605309,430342145,430344341,430344813,430344428,130112077,430341513,400611950,400612579,100249856,400612338,430344839,500036399,400612584,500036501,130112145,400613214,530016977,130111107,400613451,100251696,100250543,400612786,430345174,430344105,300049376,430345170,430343957,430344078,430352502,100257747,500038224,400627804,500038213,400627656,530018579,500038170,430352418,430352882,430348213,430353141,430352712,100255769,430353114,400619024,430352480,400622972,130114635,130114700,430350111,400628244,430345150,430352938,130114132,430352721,130109640,130113904,430352920,100256123,430348596,430350894,400628681,130114648,430353072,430353026,400621810,400605001,400622522,530017813,530018486,100256259,400627207,100257921,500038299,430352895,430349855,430353294,400622371,430338225,430350883,400627673,400627602,430353040,430353061,400626797,100256249,100257533,400623029,400627387,500038107,100256107,100257765,130113836,100255581,400627218,400628217,400626688,400627668,400627351,400627147,100257609,100255544,400627848,400618700,100256017,400623134,400627314,400622077,430352585,400628540,430353021,500037499,400622083,100257344,400625769,100253583,400628545,400622337,100256127,430349608,400626503,430338659,430352948,500037897,530018453,400627578,100256133,100257797,400627181,430352481,530018598,430352935,430352526,430353099,130113965,430350856,400626703,400627717,130114333,430353100,130114788,130114749,430352829,400627708,100255655,430352536,430349688,430353789,430350210,430346362,430353203,430353562,530018432,100257467,130114005,130114748,430353148,400626930,430350390,130114675,430350751,430352912,130113868,400626137,130113848,430352919,130114741,400622576,130114655,130113890,400626730,430352850,430352706,430353292,430350602,100257215,430352908,400626324,100255169,500037999,530018125,430353038,400622744,430350515,430353074,400626768,430341017,100257345,430353328,430352864,100257178,430352799,400625877,400092257,400621723,100257631,400625863,430352278,400627109,430347735,130114682,400626952,400627082,430349295,430352723,400626157,400625604,400625034,100255497,400625809,400621744,400625807,100257365,400626314,100115280,530018375,400621097,100256983,400626305,400621408,400620867,400620752,400625574,530016174,400625632,100254796,430351769,400313358,400621433,100257015,530018306,400620713,400627279,100255691,400621184,400615448,100256979,430349468,500038160,130113894,400621658,100250197,100257109,400625502,400625185,400626135,130114494,400626835,500035887,100257628,400627146,400625805,400625602,400625735,500035488,400620705,400626884,530018398,430352375,430352814,430352767,530017876,430352827,430347105,400625876,130114619,400625958,500037991,100257676,400625709,100255447,100257617,100257189,430352841,300051183,430350198,600153778,400622008,430350533,430352187,430352470,430352472,430353008,430352305,430352381,430352468,130113712,430351784,430352397,430352318,130113479,400625919,130113778,500037990,400625768,130114424,430352284,430352258,130113804,430352164,430352257,430352312,430349856,500037992,430352343,430349573,430352188,430349818,430352129,400627284,600154223,100254296,430351835,600154227,130114630,100255113,430352266,400626770,400626678,430352755,400625722,430343765,430351493,130114469,400625683,130114647,430341462,430352286,100255546,430352277,130109355,400626924,100255857,430352780,100257360,400626980,400628024,400627126,400622120,100257367,100255896,400626219,400627001,400626669,430353540,530018469,100257601,130113540,100257742,400625771,430350215,100257679,530018563,530018465,500037994,400621765,400619523,400626186,400628460,400628394,100257592,400626036,400626869,100257419,530018418,130113877,100255672,100257503,400626302,500038123,400627090,400622692,400626932,430349782,530018022,100257548,430353080,100257220,400628458,430350116,430350097,400625727,400624269,130113736,400627566,130110374,400627200,100257651,400627260,400627317,400627475,430353209,430353208,130114804,400625977,430352910,430352854,430352855,130114783,500038126,400627460,400627293,430353263,430353224,430350227,100257375,100258012,400627364,400627312,400626772,400625944,400622248,100257673,430353299,490001215,430353377,430352416,430352089,430352171,130114468,130113571,500037975,430331162,430352225,130114480,100257142,500038004,400625978,400625606,500037978,400625160,400625700,400625857,400625489,400625847,400626355,100257185,400626336,500037534,400625917,400622273,530018379,400625465,400625660,400626020,600154416,400625381,500036100,100256985,530018425,530018411,400626088,600154675,400626308,100257265,400625206,430352156,430352453,400625873,130114514,430350103,500037989,130114694,130114499,130114389,430352434,130114733,600154491,600153836,600154662,430353093,430352518,430352450,300051219,430352449,430350639,400628424,400628071,500038164,100257586,430352393,400626639,400627328,500037555,400626270,100257550,100257754,400622873,100255957,400627413,500037549,400626460,100257920,100257650,400621930,400627083,400627764,130113938,530018559,400627593,400627446,400626926,100257557,400626935,530018530,400627763,500036495,400627336,530018154,400626936,400626896,100257677,400627666,430350853,500037519,400627275,400627196,130113886,400627373,400626366,100257806,400625684,400617006,100257685,100251051,400627415,400627219,400621911,400627159,100257321,400626673,400627757,100255927,100257514,500038202,100256285,400621850,400627157,130114281,400624852,400625329,130113738,430351998,130114350,430330329,500037877,130114404,130114204,400621030,430349666,400624866,430352055,430351925,130114488,430352363,130114370,430351594,430351676,430349514,430351900,430352591,130114582,130113629,400624670,430352492,130113587,400625078,400624968,400625048,130114381,100257267,130114318,100255320,400625222,400625356,400619117,100249386,100256388,400624018,400625501,400624845,500037406,400624776,530017921,400624893,430351983,100256790,430349954,400620940,500037945,400625972,400624903,400625902,100256825,100255903,100254897,500037455,400624989,400588998,400624231,400624474,400622406,400625856,400624596,400618898,100256038,400622468,400624267,400624386,400618865,400624509,400620161,400617382,530018297,400624434,400622399,400623513,400623327,100256733,500037822,400621231,430351366,430351638,400625013,400625042,500037711,430350970,400624417,130113718,430350573,100256465,100258009,400627176,400627215,400628149,100256268,100256558,400624337,400628447,400624108,400618779,400628338,530018599,530018668,500037842,100255876,400627858,400616717,100256507,400623969,430351099,100257938,530018673,430353578,430353340,430350370,430353043,400624227,400628659,130114844,400627342,400626083,400627833,400627928,530018553,400626609,400629383,400628380,400622598,400628144,400619361,100255940,430353605,400628223,430350737,430353975,400628693,600154239,130114141,100256458,130114976,430351506,400628536,400628664,430353824,600153188,530018695,430351306,430354037,130114098,500037654,400628700,130114193,130113622,400628712,100258037,430353916,100255145,400628868,430351075,600154197,430353653,430353011,530018132,130114998,130114829,400624574,500038366,430353913,430353825,400618424,430350379,130113893,400628816,100258093,400623303,430350875,130112568,100258017,100252821,100255848,400628645,100256207,400622299,400628418,100256539,400629437,400628924,130113579,100256302,100258001,500038457,430352269,430353848,430353898,430353923,400600634,100256814,400627263,400623936,100255805,130114839,430353757,400628378,430351759,430353629,430353482,430353309,530017258,130114030,100256204,430335225,430353980,400628609,100255852,100256398,400629206,430353859,430351070,400626222,430353500,400628176,400623620,530018630,130114809,430353853,430349350,100256344,400623505,100256256,100258177,430353228,100256320,400629322,430353593,400627821,100256513,430353969,430353359,400628391,100256517,400627855,430350983,130114136,400628453,430353358,130114036,430353741,400628529,130114652,430353632,430347465,430353755,130114861,430353678,430353375,430353668,430353579,500037758,430351241,430353331,430351264,430353642,430352992,300051273,430353254,400628375,130113972,430353820,130114894,430351580,130114210,130114153,430350925,130114864,430353212,430353643,430353828,430349053,130114184,430350502,430353223,430353202,400615910,130114166,430351452,430353803,430353720,430351129,430353616,430351497,430353670,500038315,130112602,400628532,400623631,130114085,430351283,430353385,430351093,430353914,130114456,430353060,430349832,430353763,430353519,100257455,500038321,430353496,500037610,400617171,430353233,130114872,400624332,430353134,400627967,430353575,430349383,430353754,430352069,430353634,430353836,430351616,300051108,400623154,400615078,600153835,400628782,300051277,600154825,600154272,600154637,600138589,430350833,600154711,300046432,130114091,130114824,300051271,600151784,600154716,430350727,430354044,600154723,600154710,430350995,600154344,400628303,100257969,300051280,400623308,400624034,400629193,400623966,400624088,500038324,400623857,400623080,400621640,400628478,300051105,400622057,600154155,430353485,130113921,400629315,600154248,400623599,430353878,600154685,430353924,600154314,130113558,500037365,100257777,400621089,400592905,100256006,400624130,400622442,130113646,130113757,100257908,430351624,400628150,400628269,100256399,100257431,530018696,430353533,430349927,430352690,430353734,430353817,500037750,430352584,130107764,430351654,400624190,130114887,430351282,400624216,500038331,130114673,400627698,130113956,400626002,400626822,400627310,400628161,430353551,130114497,430353030,400627193,530018729,400628199,500038421,400627584,100257708,100252493,430353029,400627897,400629044,100256197,400629143,400626684,400627969,100256034,400626773,500038323,400627922,530018156,400627080,400627953,500038230,400622520,400628997,430352890,430350517,130114589,100257494,400629148,530018643,400627906,400616308,430353891,400629152,400628038,400627789,100255025,400626504,100255874,400627297,100256209,400627961,400629036,400621368,530018743,100257833,400628062,400623065,400628505,430353086,300050891,430353640,600154610,430353207,300051261,600153186,100257810,430353615,530017994,130114584,400622027,100257388,600154771,100257847,430353883,130114696,400619811,430350418,400627385,430353874,600154176,600154784,600154193,600154203,600154693,430353296,600154582,430353384,300051300,400629279,430353288,530018765,430353516,100256550,530018157,100256222,430353387,430353938,430353453,400624358,400623192,400618774,400628524,430353586,130114919,430353456,430352986,430351519,430331197,600154249,430344890,400628549,430353454,430353503,130114885,130114105,430351297,430351566,130113805,130114722,430353594,400628241,430350930,430353833,500037692,130114889,430349969,430350836,430351714,430351110,430353272,500038291,430353954,130114863,430353675,430351562,430354080,430353321,430353630,430353896,430351079,430351423,400623977,600126231,430350452,400628754,500037792,430353852,400622620,130112717,500038365,400628564,430345824,430353901,430353442,430353602,430353713,430351619,430351742,130114304,430351708,430354051,430350945,500037326,430352916,500038147,500037345,430350626,400627032,430353608,400627517,430353598,500038138,400606963,500038142,400627204,400622338,430350592,400622572,400623434,500038127,400627543,130114035,400626656,430352279,430353054,430352950,430352845,430350682,430350693,430352821,400627874,130113902,430352618,430352843,400627632,430353832,500038182,400621762,430353181,400627012,430353621,400622376,430353172,130114519,100255475,430344484,430350873,400621796,400626866,600154605,600154045,530018713,500037450,430353821,400628829,600154537,100255653,400626428,400627683,300051236,300051241,430352798,400628332,430353189,100256005,430353120,430350868,430353183,430353096,530017947,430350744,430352340,500038233,430349570,430352958,400623998,430353920,430350407,130114612,500038157,400627699,430353003,430353033,400627473,430353243,400628801,400628753,400626415,400628713,530018704,400623206,530018650,400628738,400618281,430353391,530018004,530018162,400627984,100254175,100257849,100258080,400627635,430350728,400627102,100257761,130114800,100255855,130114871,400628164,100257743,430351086,100258062,430353431,430351158,400628760,430351515,600154745,430350949,430351595,430353691,600154681,130114080,430353666,400627721,430353106,300051240,430350604,430353964,130114119,430353274,400624719,430353581,100256379,500038327,400628472,100114625,400622014,400622509,530018710,400629286,400619695,530018128,400627412,100256408,600154732,600154858,300051286,300044227,430351117,430354050,130112955,600134380,530018250,600154625,100258072,530018667,400624579,100257626,100255701,400619758,400624390,400624061,400629386,400628487,400628823,400626594,400622870,100256134,100256246,400628783,400612304,400628421,400623099,100255529,500038293,100255212,400624471,400628594,530018784,530018700,400628620,400629430,400623706,400621590,100255971,100258070,400628662,400624181,100256770,100255770,100256482,100257783,100256566,400627909,400614770,100252035,400623794,100256607,400622650,500038302,500037747,400623349,100257943,400624484,100256665,400622991,530018768,100256571,100256298,400628411,100255599,400628599,500038249,400628474,400624636,400629422,400621586,400624076,400629336,400627823,100256644,100253331,400628242,400628878,400628313,500038325,400628295,100256221,100256432,500037680,100256417,530018049,530018659,400615713,400629364,400621495,400629031,400623244,100248236,400628402,100255956,130114866,430343237,430341198,100255662,430353438,430353716,400623035,400626636,400622111,400608976,400624436,430353123,430350389,100255836,400628261,100255503,430353590,130114156,430350642,430351274,430353497,430353230,400627106,430350679,400628398,130114071,400629341,430353812,430351074,530018216,430350651,430353866,430351171,130113633,430353945,130114845,430346769,430354015,100257606,430354204,430346172,430353813,130114178,430354157,400628673,430353867,430353845,100258124,400630959,100257963,400630861,400631160,100257067,400631053,400627546,100259100,400629469,100254414,100257293,400626747,430354906,400629560,100259001,430350337,130113940,130115217,430354578,130115238,430354620,400631043,400631229,500038599,400630755,100255177,430355266,430354349,100258921,100257724,430354589,400630985,400630724,130115228,400630005,430341871,130114643,100258976,130115362,130114808,430354988,430351679,430353554,130115430,130114718,430352652,430355316,430353278,430355057,430354958,430355045,130114780,130115302,430352769,130115180,130115400,430355425,430355384,430354720,430353346,130114272,430353303,430353433,430353469,430354923,430352737,430351986,430355007,430354729,130115363,130114633,430352778,430353660,100259191,400631177,400625515,100256168,430355594,430354914,400627085,400632046,100259117,100257238,100258996,100259235,530017553,400632000,430355586,400632522,430352704,430355195,100256290,430352959,100259230,430355147,100259336,400632360,100259321,400606254,600154973,600155031,100251933,430352987,430355203,430355292,500038716,430355804,100259639,400627038,100259297,400626902,430354446,400610425,500037122,430355225,400599088,130115468,400630355,600154889,500009388,100259269,400631162,100258663,130113994,400632780,400626891,500038593,130115163,400629021,500038579,400631404,430353073,400631475,430355061,430355143,430354473,100256611,400630156,400615287,100258786,400631199,430350082,100258561,100258618,430354619,500038575,430355835,100259557,430353311,430353370,430353449,400631873,130115554,430356477,430354790,430355392,430356047,400632509,400632810,400628278,530019114,600155244,130115608,430353271,430353846,400632349,130112374,430353637,430355719,400630376,100259689,500038414,500038796,400632864,400628321,530018644,100257437,500038820,430355801,430355812,430355808,430355395,430355405,130114865,430355903,430355976,400628643,400634214,400634923,130115798,100260205,400629599,400634143,400610034,500038484,400633766,430356480,530019300,400634243,400634608,400634559,100260122,400635666,400364475,130115719,400633508,430357272,430356202,430356339,430354261,400633686,530019127,430357398,100260280,130115771,430356438,430357208,130116061,430352899,430355898,430354396,400633959,130114950,430352761,400634918,430356411,430356115,400633925,400631962,100255572,530018488,430355042,430353323,100257450,400631119,100258821,100253966,400631202,400632131,400630207,400631960,430353028,430352624,100259138,430354961,530018177,100259125,530019049,130113771,530019022,130114594,400616942,430349915,430354293,400631986,430354814,430354935,130115276,430354732,430354968,430354845,430355744,430354646,430342878,430354975,430352826,130115307,430354824,130112169,400624818,430354839,400631209,500038259,430354898,400627877,430353055,430345014,600154658,130115293,130115194,130114729,130114641,430352985,430353032,430353067,400631516,430352811,130114736,130115203,400632076,430353298,430354892,430355617,430354408,130114725,400626241,430355064,430352132,130115465,430355424,100256966,130115352,130115330,430352398,500016047,430353520,400631599,400630650,500038722,400632410,400631640,100259264,400627829,430351160,400632178,400632270,130115486,430353389,400631275,430355740,430355193,430355088,430354918,400627944,400630818,400631276,530019090,400629502,400631153,430355572,400631591,400368835,400631560,500038699,430352733,430355092,400631795,400631395,430354819,130115477,300050594,430342794,600152951,600153014,100251981,600129343,430340798,430344275,130111045,500035795,430345405,130112314,430346060,430345549,430338768,430341503,130110591,430345786,100250669,130109456,400614359,100252802,530013866,130112350,400613824,400613633,400613108,100252113,430345080,400608660,400614355,400613236,400599582,400614548,400613294,430345915,530017235,400614944,430344712,130112198,130112493,430345243,500036497,130112175,400613262,100252736,100252321,400606900,400612793,400613539,100249902,400600259,500036351,430344274,400613104,400612211,400608054,400612841,600153026,400613228,100252127,400611993,400612890,100252056,430344970,100252220,300049535,400612868,400611699,130110052,600152897,600152176,400606504,100249467,130111030,430340861,600152624,600152958,100249936,600152995,130111144,400603938,100250169,100250349,130110969,600153011,400613352,130112206,300050743,400634392,400633269,400634086,400627618,400631028,400633685,500038994,400633245,400634024,400629503,400628330,500038800,500039004,400618199,400632241,100260017,400633793,400633224,530018798,400632544,130115623,100259478,100259712,400633530,400632485,100258245,100259778,100255491,530018803,400632095,500038914,400632859,400631487,100259451,400631807,400628283,400632313,400631898,100256932,400634156,100259783,500037201,530019304,530019202,430353528,530019136,400627797,400629096,400634375,400617626,400632532,100259170,600155325,130115433,130115498,130114771,130115590,430353282,430355478,430355440,430353758,430355989,600155239,430355615,500038758,100257840,100259893,500038813,500038193,400632569,600155247,600155282,400632247,600154786,100259544,130115628,100259242,600154775,430355500,400632038,100259233,600155209,130115473,600155279,600154806,130115443,130115462,400632467,400633330,100259396,600155222,130115607,600155323,600155199,430353532,100259392,400632558,430355336,430355138,430354889,400629014,600154101,130114931,400629092,100257751,400632388,430353750,130115439,430355477,600154524,430354564,130115504,430355509,100257300,400631136,430354036,430356135,130114699,400631426,130114920,430355348,430356076,530018951,100259442,400623601,430355574,430356055,430355746,400631739,430355548,400631910,400631981,400628852,400629068,500038287,400633037,400628448,530019058,500038399,530019106,400632684,100258176,400632176,400632173,100257866,400632662,400632920,400632137,400632134,400632335,400629245,100257993,400628658,530019102,100259097,100259394,400628070,400627411,400632274,400632345,100259551,400633503,400632487,400632870,400629093,100259643,530019140,100259500,400632574,400632272,100259294,400632825,400623659,400632086,400633345,400263312,400616666,400631383,400623379,100259376,400632041,400629349,100259901,100257836,500038780,100255541,400631930,400633456,100259385,400626361,400628970,500037518,100259499,100259382,100259635,400633057,400633081,100259078,100259427,430355913,400633232,430353063,430355456,100259438,430355549,500038677,100256922,400631797,400631630,100259108,400628975,400632118,400632609,400624311,500038666,100259844,100259015,400633104,400631672,400632319,400631788,130114858,430355708,500038183,130115309,430355145,130114834,430355887,430355318,430355256,430355929,130113841,430355806,430354928,430355857,430353430,430353535,130114787,130114744,130115523,430353225,430355093,130115460,430355927,130114785,530019159,400628048,100259365,130115402,600154656,500038754,600146683,130114118,100259459,100257981,130114905,400631405,400631521,430353171,130114886,430353538,430353164,430353570,430353732,130115495,400633241,400625911,400628691,430355538,100258069,430355224,400628101,430352731,400629141,130114836,430355983,400632935,400631935,130115415,100128835,130115552,430356000,500038726,130115384,430350563,130114167,400631397,430353324,400627790,130115404,430355221,400628991,400626505,430353286,500038743,430353526,400631424,430350296,400270492,100146824,130115320,400631452,430353420,430355047,430352366,130114669,430355327,400626786,130114760,430355129,100257436,130114639,430355293,430354848,430355049,430355335,430355513,430355031,430352477,400631335,130114807,130113732,430355116,430355010,430352942,430352975,130115250,430352896,430355162,130114452,130114557,500038201,400627228,130115326,100258790,430354887,130115457,430353354,430354938,400625125,430355275,100258941,430355189,400627095,430355422,130115198,400632005,400630500,400631087,430354213,430355037,400631371,430355081,130115334,430355394,430355072,430353138,430354780,130112624,430355383,430352925,430353104,130114599,430354815,430351356,130115122,430349829,430350274,430354190,530018391,400630357,400629344,530018413,400629446,500038480,400630132,500038418,400629382,500038473,400630346,400324090,400630455,400630315,100258297,100258340,130115143,430352057,130115013,430353947,430340563,130109814,130114767,400629541,130114891,430354091,300051292,100258239,430354552,100258170,400630198,600153911,130114517,600154756,600154084,300051170,430353186,600154735,600154404,300051291,100258364,600154743,530018429,400630382,430350346,430354458,400630374,430352238,130114464,130114431,430354375,430350421,400630518,400629610,430354721,430352051,130114569,430352006,430354757,430354533,130115211,430353959,100257281,430354677,100256835,530018337,430352726,430354744,400629725,430351315,430354697,100258532,100257218,400629975,530018312,130115222,430354392,100257264,400629721,400630661,400630627,400629957,400629991,530018838,100256900,530018829,400630241,100258369,400604964,400629399,100258128,430354026,400626867,400629897,500038516,530018818,400630607,400625142,400627065,100256837,100258346,100258548,130114895,130114266,400628884,430353664,130114938,430353424,430353775,430352114,130114903,430353889,430353423,430353466,430353652,400627956,430353834,430353787,430353659,430350862,430351646,430353738,430350551,130113574,130114893,130115074,430350312,130115078,430354006,430354326,430354153,100256402,400622431,400628663,530018575,100256633,130114918,400629027,100258073,400628757,400614988,400622281,430350360,500038339,400623947,400336525,530018179,400616693,100256474,400622739,100256425,400628373,100258065,500036610,400628476,100256288,400624380,400628415,400628260,400628978,430353245,400624786,400628624,100256769,400623766,530018741,400628703,400629879,600154895,430354101,130114345,100257133,430352237,430354256,500038483,430354112,400629950,400621629,430354320,400625541,130114575,130112289,430352806,400630137,400596905,530018298,430354074,100258566,130114676,430352196,430354309,130114355,430354576,430354332,430354340,400627098,530018823,530018415,100258517,400629854,100257441,530018790,400627092,400615330,400630795,100258832,100257535,400630987,400626282,100258915,400630728,500038055,100257735,400630866,400631863,100257538,430355220,130115287,430354703,430354754,430354878,430354234,400630860,430355272,400630981,500038156,400630875,100259052,100258599,400630735,100255952,100253961,430354522,600155166,430354792,130113484,100257159,130115191,600155079,130115246,300051370,430354370,430352519,400625746,130114348,430354220,400630224,530018810,430350538,400626345,100258370,100258357,430352018,600154961,530018752,400628684,100258207,430354282,100257117,100257078,400629835,400630498,130115543,430355219,400632365,500038753,400632499,100259223,400632181,530018135,130115640,400632073,100259348,430355446,130115592,400632180,400631812,530019122,100257979,400627526,400629575,400632284,400632590,400633370,100259911,100259477,100259479,530019121,530019109,400630825,530018748,100259807,100257214,430353985,430355895,100259509,130115440,430355253,430355557,130114708,400630910,130114356,130115107,130114041,400627520,400630701,100258843,400630954,400630713,100258736,400630922,500038093,400623368,530018901,100258971,400627187,430354680,400631027,100258897,500038696,400630705,500036988,530017549,130114701,130114617,430354951,430354866,430352553,100254869,100258962,430354498,430353039,400625230,400631470,100253792,430354762,430352627,400631012,430346652,430353547,430356057,130115438,430355585,430355114,430353112,100257887,430353395,130114841,430353159,400626971,430355359,400626022,430355471,400633020,430355635,400632130,400626834,400632168,100257798,400624648,100259332,400627603,400631506,100253663,430353707,400633146,400628522,430355311,100259386,400631522,400632953,400632213,430354580,430355267
        ];

        $list = \app\admin\model\saleaftermanage\WorkOrderList::where('platform_order','in',$incrementIds)
            ->select();
        $list = collection($list)->toArray();
        $fa_order = new NewOrder();
        //用户
        $admin = new \app\admin\model\Admin();
        $user_list = $admin->where('status', 'normal')->column('nickname', 'id');

        $i = 0;
        $file_content = '';
        foreach ($list as $k => $v) {
            $arr = [];
            $recept = $this->sel_order_recept($v['id']); //获取措施相关记录
            $list[$k]['step_num'] = $recept;
            $list[$k]['status'] = '';
            if($v['replacement_order']) {
                $list[$k]['status'] = $fa_order->where('increment_id', $list[$k]['replacement_order'])->value('status');
            }
            $step = '';
            foreach($list[$k]['step_num'] as $kk => $vv){
                $status = '';
                switch($vv['operation_type']) {
                    case 0:
                        $status = '未处理/';
                        break;
                    case 1:
                        $status = '处理成功/';
                        break;
                    case 2:
                        $status = '处理失败/';
                        break;
                }
                $step .= $vv['measure_content'] .':' . $status;
            }
            $arr = [
                $v['platform_order'],
                $v['order_pay_currency'].$v['base_grand_total'],
                $list[$k]['problem_type_content'],
                $step,
                $v['refund_money'],
                $list[$k]['replacement_order'],
                $list[$k]['status'],
            ];
            $file_content = $file_content . implode(',',$arr);
            $file_content = $file_content . "\n";
            echo $v['platform_order']." is ok\n";
            $i++;
        }
        $export_str = array('订单号','订单金额','工单问题类型','退款金额','措施状态','补发订单号','补发订单号状态');
        $file_title = implode(',',$export_str) ." \n";
        $file = $file_title . $file_content;
        file_put_contents('./jianuobufa.csv',$file);
        exit;
    }

    //根据主记录id，获取措施相关信息
    protected function sel_order_recept($id)
    {
        $step = \app\admin\model\saleaftermanage\WorkOrderMeasure::where('work_id', $id)->select();
        $step_arr = collection($step)->toArray();

        foreach ($step_arr as $k => $v) {
            $recept = \app\admin\model\saleaftermanage\WorkOrderRecept::where('measure_id', $v['id'])->where('work_id', $id)->select();
            $recept_arr = collection($recept)->toArray();
            $step_arr[$k]['recept_user'] = implode(',', array_column($recept_arr, 'recept_person'));
            $step_arr[$k]['recept_person_id'] = implode(',', array_column($recept_arr, 'recept_person_id'));

            $step_arr[$k]['recept'] = $recept_arr;
        }

        return $step_arr ?: [];
    }

}
