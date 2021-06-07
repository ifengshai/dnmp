<?php
/**
 * 运营统计--仪表盘折线图脚本
 */

namespace app\admin\controller\shell\operate;

use think\console\Command;
use think\console\Input;
use think\console\Output;
use think\Db;

class skuDayAsynData extends Command
{
    public function __construct()
    {
        parent::__construct();
        $this->item_platform_sku = new \app\admin\model\itemmanage\ItemPlatformSku();
        $this->order = new \app\admin\model\order\order\NewOrder();
        $this->orderitemoption = new \app\admin\model\order\order\NewOrderItemOption();
    }
    protected function configure()
    {
        $this->setName('sku_day')
            ->setDescription('sku_day run');
    }

    protected function execute(Input $input, Output $output)
    {
//        $this->getCartData(1);
//        $this->getCartData(2);
//        $this->getCartData(3);
//        $this->getCartData(10);
//        $this->getCartData(11);
        $this->getCartData(15);
        $this->getSkuDayData(15);
        //$this->getSkuDayData(5);
        $output->writeln("All is ok");
    }

    public function getSkuDayData($site)
    {
        switch ($site){
            case 1:
                $model = Db::connect('database.db_zeelool_online');
                break;
            case 2:
                $model = Db::connect('database.db_voogueme_online');
                break;
            case 3:
                $model = Db::connect('database.db_nihao_online');
                break;
            case 10:
                $model = Db::connect('database.db_zeelool_de_online');
                break;
            case 11:
                $model = Db::connect('database.db_zeelool_jp_online');
                break;
            case 15:
                $model = Db::connect('database.db_zeelool_fr_online');
                break;
        }
        $tStart = strtotime('2021-05-15');
        $tend = time();
        for($i = $tStart;$i<$tend;$i+=3600*24){
            $data = date('Y-m-d', $i);
            $start = $i;
            $end = strtotime(date('Y-m-d 23:59:59', $i));

            $orderWhere['o.payment_time'] = ['between',[$start,$end]];
            $orderWhere['o.site'] = $site;
            $orderWhere['o.order_type'] = 1;
            $orderWhere['o.status'] = ['in',['free_processing','processing', 'complete', 'paypal_reversed', 'payment_review','paypal_canceled_reversal', 'delivered']];
            $sku_data = $this->item_platform_sku
                ->field('sku,grade,platform_sku,stock,plat_on_way_stock')
                ->where(['platform_type' => $site, 'outer_sku_status' => 1])
                ->select();
            $sku_data = collection($sku_data)->toArray();
            $skuDatas = $this->order
                ->alias('o')
                ->join(['fa_order_item_option' => 'i'], 'o.entity_id=i.magento_order_id')
                ->where($orderWhere)
                ->whereIn('sku',array_column($sku_data,'platform_sku'))
                ->field('sku,magento_order_id,qty,base_original_price,base_original_price,i.base_discount_amount,i.lens_price,i.coating_price')
                ->select();
            $skuArr = [];
            foreach ($skuDatas as $key=>$value){
                $skuArr[$value['sku']]['order_num'][] = $value['magento_order_id'];
                $skuArr[$value['sku']]['glass_num'] += $value['qty'];
                if($value['lens_price']>0 || $value['coating_price']>0){
                    $skuArr[$value['sku']]['pay_lens_num'] += $value['qty'];
                }
                $skuArr[$value['sku']]['sku_grand_total'] += $value['base_original_price'];
                $skuArr[$value['sku']]['sku_row_total'] += $value['base_original_price'] - $value['base_discount_amount'];
            }
            if($site == 5){
                $nowPrice = Db::connect('database.db_weseeoptical')
                    ->table('goods') //为了获取现价找的表
                    ->whereIn('sku',array_column($sku_data,'platform_sku'))
                    ->column('IF(special_price,special_price,price) price','sku');
            }else{
                $nowPrice = $model->table('catalog_product_index_price') //为了获取现价找的表
                    ->alias('a')
                    ->join(['catalog_product_entity' => 'b'], 'a.entity_id=b.entity_id') //商品主表
                    ->whereIn('sku',array_column($sku_data,'platform_sku'))
                    ->column('a.final_price','b.sku');
            }
            foreach ($sku_data as $k => $v) {
                $sku_data[$k]['unique_pageviews'] = 0;
                $sku_data[$k]['goods_grade'] = $v['grade'];
                $sku_data[$k]['day_date'] = $data;
                $sku_data[$k]['site'] = $site;
                $sku_data[$k]['day_stock'] = $v['stock'];
                $sku_data[$k]['day_onway_stock'] = $v['plat_on_way_stock'];

                if(!empty($skuArr[$v['platform_sku']]['order_num'])){
                    $orderNum = array_unique($skuArr[$v['platform_sku']]['order_num']);
                    //某个sku当天的订单数
                    $sku_data[$k]['order_num'] = count($orderNum);
                }else{
                    $sku_data[$k]['order_num'] = 0;
                }
                //sku销售总副数
                $sku_data[$k]['glass_num'] = $skuArr[$v['platform_sku']]['glass_num'] ? $skuArr[$v['platform_sku']]['glass_num'] : 0;
                //求出眼镜的销售额
                $frame_money_price = $skuArr[$v['platform_sku']]['sku_grand_total'] ? $skuArr[$v['platform_sku']]['sku_grand_total'] : 0;
                //sku付费镜片的数量
                $sku_data[$k]['pay_lens_num'] = $skuArr[$v['platform_sku']]['pay_lens_num'] ? $skuArr[$v['platform_sku']]['pay_lens_num'] : 0;
                //眼镜的实际支付金额
                $frame_money = $skuArr[$v['platform_sku']]['sku_row_total'];
                //眼镜的实际销售额
                $frame_money = $frame_money ? round($frame_money, 2) : 0;
                $sku_data[$k]['sku_grand_total'] = $frame_money_price;
                $sku_data[$k]['sku_row_total'] = $frame_money;
                $now_pricce = $nowPrice[$v['platform_sku']];
                $sku_data[$k]['now_pricce'] = $now_pricce ? $now_pricce : 0;
                unset($sku_data[$k]['stock']);
                unset($sku_data[$k]['grade']);
                unset($sku_data[$k]['plat_on_way_stock']);
                Db::name('datacenter_sku_day')->insert($sku_data[$k]);
                echo $v['platform_sku'].' is ok'."\n";
                usleep(10000);
            }
        }
    }

    public function getCartData($site){
        $tStart = strtotime('2021-05-15');
        $tend = time();
        switch ($site){
            case 1:
                $model = Db::connect('database.db_zeelool_online');
                break;
            case 2:
                $model = Db::connect('database.db_voogueme_online');
                break;
            case 3:
                $model = Db::connect('database.db_nihao_online');
                break;
            case 10:
                $model = Db::connect('database.db_zeelool_de_online');
                break;
            case 11:
                $model = Db::connect('database.db_zeelool_jp_online');
                break;
            case 15:
                $model = Db::connect('database.db_zeelool_fr_online');
                break;
        }
        $cartWhere['a.base_grand_total'] = ['>',0];
        for($i = $tStart;$i<$tend;$i+=3600*24){
            $start = date('Y-m-d', $i);
            $end = date('Y-m-d 23:59:59', $i);
            $createTimeWhere['a.created_at'] = ['between',[$start,$end]];
            $skuList = Db::name('datacenter_sku_day')
                ->where(['day_date' => $start, 'site' => $site])
                ->field('id,platform_sku')
                ->select();
            $cartNum = $model->table('sales_flat_quote')
                ->alias('a')
                ->join(['sales_flat_quote_item' => 'b'], 'a.entity_id=b.quote_id')
                ->where($createTimeWhere)
                ->where($cartWhere)
                ->whereIn('b.sku',array_column($skuList,'platform_sku'))
                ->field('b.sku,a.entity_id')
                ->select();
            $skuArr = [];
            foreach ($cartNum as $key=>$value){
                $skuArr[$value['sku']]['quote_num'][] = $value['entity_id'];
            }
            foreach ($skuList as $k => $v) {
                if(!empty($skuArr[$v['platform_sku']]['quote_num'])){
                    $quoteNum = array_unique($skuArr[$v['platform_sku']]['quote_num']);
                    //某个sku当天的购物车数
                    $skuList[$k]['cart_num'] = count($quoteNum);
                }else{
                    $skuList[$k]['cart_num'] = 0;
                }
                Db::name('datacenter_sku_day')->update($skuList[$k]);
                echo $v['id'].' is ok'."\n";
                usleep(10000);
            }
        }
    }
}