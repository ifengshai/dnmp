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
    protected function configure()
    {
        $this->setName('sku_day')
            ->setDescription('sku_day run');
    }

    protected function execute(Input $input, Output $output)
    {
        $this->getSkuDayData(5);
        $output->writeln("All is ok");
    }

    public function getSkuDayData($site)
    {
        $_item_platform_sku = new \app\admin\model\itemmanage\ItemPlatformSku();
        $this->order = new \app\admin\model\order\order\NewOrder();
        $this->orderitemoption = new \app\admin\model\order\order\NewOrderItemOption();
        $tStart = strtotime('2020-01-01');
        $tend = time();
        for($i = $tStart;$i<$tend;$i+=3600*24){
            $data = date('Y-m-d', $i);
            $start = $i;
            $end = strtotime(date('Y-m-d 23:59:59', $i));

            $orderWhere['o.payment_time'] = ['between',[$start,$end]];
            $orderWhere['o.site'] = $site;
            $orderWhere['o.order_type'] = 1;
            $orderWhere['o.status'] = ['in',['free_processing','processing', 'complete', 'paypal_reversed', 'payment_review','paypal_canceled_reversal', 'delivered']];
            $sku_data = $_item_platform_sku
                ->field('sku,grade,platform_sku,stock,plat_on_way_stock')
                ->where(['platform_type' => $site, 'outer_sku_status' => 1])
                ->select();
            //当前站点的所有sku映射关系
            $sku_data = collection($sku_data)->toArray();
            foreach ($sku_data as $k => $v) {
                $sku_data[$k]['unique_pageviews'] = 0;
                $sku_data[$k]['goods_grade'] = $v['grade'];
                $sku_data[$k]['day_date'] = $data;
                $sku_data[$k]['site'] = $site;
                $sku_data[$k]['day_stock'] = $v['stock'];
                $sku_data[$k]['day_onway_stock'] = $v['plat_on_way_stock'];
                $map['i.sku'] = ['like', $v['platform_sku'].'%'];
                //某个sku当天的订单数
                $sku_data[$k]['order_num'] = $this->order
                    ->alias('o')
                    ->join(['fa_order_item_option' => 'i'], 'o.entity_id=i.magento_order_id')
                    ->where($orderWhere)
                    ->where($map)
                    ->count('distinct magento_order_id');
                //sku销售总副数
                $sku_data[$k]['glass_num'] = $this->order
                    ->alias('o')
                    ->join(['fa_order_item_option' => 'i'], 'o.entity_id=i.magento_order_id')
                    ->where($orderWhere)
                    ->where($map)
                    ->sum('i.qty');
                //求出眼镜的销售额
                $frame_money_price = $this->orderitemoption
                    ->alias('i')
                    ->join('fa_order o', 'i.magento_order_id=o.entity_id', 'left')
                    ->where($orderWhere)
                    ->where($map)
                    ->sum('base_original_price');
                //sku付费镜片的数量
                $lensWhere = [];
                $lensWhere[] = ['exp', Db::raw("(i.index_price>0 || i.coating_price>0)")];
                $sku_data[$k]['pay_lens_num'] = $this->orderitemoption
                    ->alias('i')
                    ->join('fa_order o', 'i.magento_order_id=o.entity_id', 'left')
                    ->where($orderWhere)
                    ->where($map)
                    ->where($lensWhere)
                    ->sum('i.qty');
                //眼镜的实际支付金额
                $frame_money = $this->orderitemoption
                    ->alias('i')
                    ->join('fa_order o', 'i.magento_order_id=o.entity_id', 'left')
                    ->where($orderWhere)
                    ->where($map)
                    ->value('(sum(base_original_price-i.base_discount_amount)) total');
                //眼镜的实际销售额
                $frame_money = $frame_money ? round($frame_money, 2) : 0;
                $sku_data[$k]['sku_grand_total'] = $frame_money_price;
                $sku_data[$k]['sku_row_total'] = $frame_money;
                $sku_data[$k]['now_pricce'] = Db::connect('database.db_weseeoptical')
                    ->table('goods') //为了获取现价找的表
                    ->where('sku',  $v['platform_sku'])
                    ->value('IF(special_price,special_price,price) price');
                unset($sku_data[$k]['stock']);
                unset($sku_data[$k]['grade']);
                unset($sku_data[$k]['plat_on_way_stock']);
                Db::name('datacenter_sku_day')->insert($sku_data[$k]);
            }
        }
    }

}