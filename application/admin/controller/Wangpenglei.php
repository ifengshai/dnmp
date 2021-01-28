<?php

namespace app\admin\controller;

use app\common\controller\Backend;
use think\Db;
use FacebookAds\Api;
use FacebookAds\Object\Campaign;
use app\admin\model\financial\Fackbook;

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
        $skus = $this->item->where(['is_open' => 1, 'is_del' => 1, 'category_id' => ['<>', 43]])->column('sku');
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
        $skus = $this->item->where(['is_open' => 1, 'is_del' => 1, 'category_id' => ['<>', 43]])->column('sku');
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
}
