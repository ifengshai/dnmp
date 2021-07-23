<?php

namespace app\admin\controller;

use app\admin\controller\zendesk\Notice;
use app\admin\model\finance\FinanceCost;
use app\admin\model\itemmanage\Item;
use app\admin\model\itemmanage\ItemPlatformSku;
use app\admin\model\lens\LensPrice;
use app\admin\model\operatedatacenter\DatacenterDay;
use app\admin\model\order\order\NewOrder;
use app\admin\model\order\order\NewOrderItemProcess;
use app\admin\model\order\order\NewOrderProcess;
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
            $storehouse->where(['type' => 1, 'stock_id' => 1, 'area_id' => 3, 'coding' => $v['store_house']])->update(['picking_sort' => $v['sort']]);
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
                echo $i . '->' . $ticket->type . '->';
                $isPushed = Queue::push("app\admin\jobs\Zendesk", $ticket, "zendeskJobQueue");
                if ($isPushed !== false) {
                    echo $ticket->ticket_id . "->推送成功" . PHP_EOL;
                } else {
                    echo $ticket->ticket_id . "->推送失败" . PHP_EOL;
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
        $this->item->where(['is_open' => 1, 'is_del' => 1, 'category_id' => ['<>', 43]])->chunk(1000, function ($row) use ($productbarcode, $itemplatformsku, $order, $headList, &$z) {
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
                        ->where(['sku' => $val['sku'], 'stock_id' => $v, 'library_status' => 1])
                        ->where("item_order_number=''")
                        ->count();

                    $dahuo_location_id = $v == 1 ? 1 : 4;
                    $data[$i]['dahuo_stock'] = $productbarcode
                        ->where(['sku' => $val['sku'], 'stock_id' => $v, 'library_status' => 1, 'location_id' => $dahuo_location_id])
                        ->where("item_order_number=''")
                        ->count();

                    $huojia_location_id = $v == 1 ? 2 : 5;
                    $data[$i]['huojia_stock'] = $productbarcode
                        ->where(['sku' => $val['sku'], 'stock_id' => $v, 'library_status' => 1, 'location_id' => $huojia_location_id])
                        ->where("item_order_number=''")
                        ->count();

                    $jianhuojia_location_id = $v == 1 ? 3 : 6;
                    $data[$i]['jianhuojia_stock'] = $productbarcode
                        ->where(['sku' => $val['sku'], 'stock_id' => $v, 'library_status' => 1, 'location_id' => $jianhuojia_location_id])
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
        $dayBefore = date('Y-m-d', strtotime('-2 day', '1624899600'));
        $dayNow = date('Y-m-d', strtotime('-1 day', '1624899600'));
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
        ini_set('memory_limit', '-1');
        $orderNodes = OrderNode::where('shipment_data_type', '加诺')->whereTime('delivery_time', '>', '2021-06-20 00:00:00')->select();
        $orderNumbers = array_column($orderNodes, 'order_number');
        $orders = Db::connect('database.db_mojing_order')->table('fa_order')->where('increment_id', 'in', $orderNumbers)->field('increment_id,country_id,region')->select();
        foreach ($orderNodes as $orderNode) {
            foreach ($orders as $order) {
                if ($order['increment_id'] != $orderNode['order_number']) {
                    continue;
                }
                $shipment_data_type = '加诺-其他';
                if (!empty($order['country_id']) && $order['country_id'] == 'US') {
                    //美国
                    $shipment_data_type = '加诺-美国';
                    if ($order['region'] == 'PR' || $order['region'] == 'Puerto Rico') {
                        //波多黎各
                        $shipment_data_type = '加诺-波多黎各';
                    }
                }
                if (!empty($order['country_id']) && $order['country_id'] == 'CA') {
                    //加拿大
                    $shipment_data_type = '加诺-加拿大';
                }

                if (!empty($order['country_id']) && $order['country_id'] == 'PR') {
                    //波多黎各
                    $shipment_data_type = '加诺-波多黎各';
                }
                echo $shipment_data_type . PHP_EOL;
                echo $orderNode['id'] . PHP_EOL;
                echo $orderNode['order_number'] . PHP_EOL;
                //修改节点信息
                OrderNode::where('id', $orderNode['id'])->setField('shipment_data_type', $shipment_data_type);
            }
        }
    }

    public function editCost()
    {
        $finanace = new FinanceCost();
        $order = new NewOrder();
        $list = $finanace->where('type', 1)->where('bill_type', 1)->where('site', 13)->select();
        foreach ($list as $k => $v) {
            $grand_total = $order->where('site', 13)->where('increment_id', $v['order_number'])->value('grand_total');
            $finanace->where('id', $v['id'])->update(['order_money' => $grand_total, 'income_amount' => $grand_total]);
            echo $v['order_number'] . "\n";
        }
        echo "ok";
    }

    public function editOrder()
    {
        $order_number = [
            '430360882',
            '400698872',
            '430389123',
            '430389146',
            '400692021',
            '430390599',
            '130128550',
            '430392133',
            '400691738',
            '430390848',
            '130128086',
            '400694633',
            '130127986',
            '400695134',
            '430388991',
            '400694718',
            '100285033',
            '130128541',
            '100285305',
        ];
        $orderprocess = new NewOrderProcess();
        $orderitemprocess = new NewOrderItemProcess();
        $list = $orderprocess->where(['increment_id' => ['in', $order_number]])->select();
        foreach ($list as $k => $v) {
            $orderprocess->where('id', $v['id'])->update(['combine_status' => 1, 'combine_time' => time(), 'store_house_id' => 0]);
            $orderitemprocess->where('order_id', $v['order_id'])
                ->where('site', $v['site'])
                ->where('distribution_status', 8)
                ->update(['distribution_status' => 9]);
        }


    }

}
