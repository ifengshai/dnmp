<?php

namespace app\admin\controller;

use app\common\controller\Backend;
use think\Db;

class Wangpenglei extends Backend
{

    protected $noNeedLogin = ['*'];

    public function _initialize()
    {
        parent::_initialize();
        $this->zeelool = new \app\admin\model\order\order\Zeelool();
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
        $this->zeelool = new \app\admin\model\order\order\Zeelool;
        $this->voogueme = new \app\admin\model\order\order\Voogueme;
        $this->nihao = new \app\admin\model\order\order\Nihao;
        $this->weseeoptical = new \app\admin\model\order\order\Weseeoptical;
        $this->meeloog = new \app\admin\model\order\order\Meeloog;
        $this->zeelool_es = new \app\admin\model\order\order\ZeeloolEs();
        $this->zeelool_de = new \app\admin\model\order\order\ZeeloolDe();
        $this->zeelool_jp = new \app\admin\model\order\order\ZeeloolJp();
        $this->itemplatformsku = new \app\admin\model\itemmanage\ItemPlatformSku;
        $this->item = new \app\admin\model\itemmanage\Item;

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

            $map['status'] = ['in', ['free_processing', 'processing', 'complete', 'paypal_reversed', 'paypal_canceled_reversal']];
            $map['custom_is_delivery_new'] = 0; //是否提货
            $map['custom_is_match_frame_new'] = 1; //是否配镜架
            $map['a.created_at'] = ['between', ['2020-01-01 00:00:00', date('Y-m-d H:i:s')]]; //时间节点
            $map['sku'] = $zeelool_sku;
            $zeelool_qty = $this->zeelool->alias('a')->where($map)->join(['sales_flat_order_item' => 'b'], 'a.entity_id = b.order_id')->sum('qty_ordered');
            $map['sku'] = $voogueme_sku;
            $voogueme_qty = $this->voogueme->alias('a')->where($map)->join(['sales_flat_order_item' => 'b'], 'a.entity_id = b.order_id')->sum('qty_ordered');
            $map['sku'] = $nihao_sku;
            $nihao_qty = $this->nihao->alias('a')->where($map)->join(['sales_flat_order_item' => 'b'], 'a.entity_id = b.order_id')->sum('qty_ordered');
            $map['sku'] = $wesee_sku;
            $weseeoptical_qty = $this->weseeoptical->alias('a')->where($map)->join(['sales_flat_order_item' => 'b'], 'a.entity_id = b.order_id')->sum('qty_ordered');

            $map['sku'] = $zeelool_es_sku;
            $zeelool_es_qty = $this->zeelool_es->alias('a')->where($map)->join(['sales_flat_order_item' => 'b'], 'a.entity_id = b.order_id')->sum('qty_ordered');
            $map['sku'] = $zeelool_de_sku;
            $zeelool_de_qty = $this->zeelool_de->alias('a')->where($map)->join(['sales_flat_order_item' => 'b'], 'a.entity_id = b.order_id')->sum('qty_ordered');
            $map['sku'] = $zeelool_jp_sku;
            $zeelool_jp_qty = $this->zeelool_jp->alias('a')->where($map)->join(['sales_flat_order_item' => 'b'], 'a.entity_id = b.order_id')->sum('qty_ordered');

            $map['sku'] = $meeloog_sku;
            $map['custom_is_delivery'] = 0; //是否提货
            $map['custom_is_match_frame'] = 1; //是否配镜架
            unset($map['custom_is_delivery_new']);
            unset($map['custom_is_match_frame_new']);
            $meeloog_qty = $this->meeloog->alias('a')->where($map)->join(['sales_flat_order_item' => 'b'], 'a.entity_id = b.order_id')->sum('qty_ordered');

            $p_map['sku'] = $v;
            $data['distribution_occupy_stock'] = $zeelool_qty + $voogueme_qty + $nihao_qty + $weseeoptical_qty + $meeloog_qty + $zeelool_jp_qty + $zeelool_es_qty + $zeelool_de_qty;

            $res = $this->item->where($p_map)->update($data);

            echo $v. "\n";
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
        $this->zeelool = new \app\admin\model\order\order\Zeelool;
        $this->voogueme = new \app\admin\model\order\order\Voogueme;
        $this->nihao = new \app\admin\model\order\order\Nihao;
        $this->weseeoptical = new \app\admin\model\order\order\Weseeoptical;
        $this->meeloog = new \app\admin\model\order\order\Meeloog;
        $this->zeelool_es = new \app\admin\model\order\order\ZeeloolEs();
        $this->zeelool_de = new \app\admin\model\order\order\ZeeloolDe();
        $this->zeelool_jp = new \app\admin\model\order\order\ZeeloolJp();
        $this->itemplatformsku = new \app\admin\model\itemmanage\ItemPlatformSku;
        $this->item = new \app\admin\model\itemmanage\Item;
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

            $map['status'] = ['in', ['free_processing', 'processing', 'complete', 'paypal_reversed', 'paypal_canceled_reversal']];
            $map['custom_is_delivery_new'] = 0; //是否提货
            $map['a.created_at'] = ['between', ['2020-01-01 00:00:00', date('Y-m-d H:i:s')]]; //时间节点
            $map['sku'] = $zeelool_sku;
            $zeelool_qty = $this->zeelool->alias('a')->where($map)->join(['sales_flat_order_item' => 'b'], 'a.entity_id = b.order_id')->sum('qty_ordered');
            $map['sku'] = $voogueme_sku;
            $voogueme_qty = $this->voogueme->alias('a')->where($map)->join(['sales_flat_order_item' => 'b'], 'a.entity_id = b.order_id')->sum('qty_ordered');
            $map['sku'] = $nihao_sku;
            $nihao_qty = $this->nihao->alias('a')->where($map)->join(['sales_flat_order_item' => 'b'], 'a.entity_id = b.order_id')->sum('qty_ordered');
            $map['sku'] = $wesee_sku;
            $weseeoptical_qty = $this->weseeoptical->alias('a')->where($map)->join(['sales_flat_order_item' => 'b'], 'a.entity_id = b.order_id')->sum('qty_ordered');

            $map['sku'] = $zeelool_es_sku;
            $zeelool_es_qty = $this->zeelool_es->alias('a')->where($map)->join(['sales_flat_order_item' => 'b'], 'a.entity_id = b.order_id')->sum('qty_ordered');
            $map['sku'] = $zeelool_de_sku;
            $zeelool_de_qty = $this->zeelool_de->alias('a')->where($map)->join(['sales_flat_order_item' => 'b'], 'a.entity_id = b.order_id')->sum('qty_ordered');
            $map['sku'] = $zeelool_jp_sku;
            $zeelool_jp_qty = $this->zeelool_jp->alias('a')->where($map)->join(['sales_flat_order_item' => 'b'], 'a.entity_id = b.order_id')->sum('qty_ordered');

            $map['sku'] = $meeloog_sku;
            $map['custom_is_delivery'] = 0; //是否提货
            unset($map['custom_is_delivery_new']);
            $meeloog_qty = $this->meeloog->alias('a')->where($map)->join(['sales_flat_order_item' => 'b'], 'a.entity_id = b.order_id')->sum('qty_ordered');


            $p_map['sku'] = $v;
            $data['occupy_stock'] = $zeelool_qty + $voogueme_qty + $nihao_qty + $weseeoptical_qty + $meeloog_qty + $zeelool_jp_qty + $zeelool_es_qty + $zeelool_de_qty;
            $res = $this->item->where($p_map)->update($data);

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
        // $skus = Db::table('fa_zz_temp2')->where(['sku' => ['in',['OA01815-01','OA01822-01','OA01901-06','TT598617-06']]])->column('sku');
        $skus = ['OA01815-01','OA01822-01','OA01901-06','TT598617-06'];
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
                                
                            }  else {
                               
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
}
