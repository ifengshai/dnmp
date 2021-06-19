<?php
/**
 * 运营统计--仪表盘折线图脚本
 */

namespace app\admin\controller\shell\operate;

use think\console\Command;
use think\console\Input;
use think\console\Output;
use think\Db;

class skuDayDataAsynData extends Command
{
    protected function configure()
    {
        $this->setName('sku_day_data')
            ->setDescription('sku_day_data run');
    }

    protected function execute(Input $input, Output $output)
    {
        $this->getSkuDayData();
        $output->writeln("All is ok");
    }

    public function getSkuDayData()
    {
        $_item_platform_sku = new \app\admin\model\itemmanage\ItemPlatformSku();
        $this->order = new \app\admin\model\order\order\NewOrder();
        $this->orderitemoption = new \app\admin\model\order\order\NewOrderItemOption();
        $tStart = strtotime('2021-01-01');
        $tend = time();
        for($i = $tStart;$i<$tend;$i+=3600*24){
            $data = date('Y-m-d', $i);
            $start = $i;
            $end = strtotime(date('Y-m-d 23:59:59', $i));
            $orderWhere['o.payment_time'] = ['between',[$start,$end]];
            $orderWhere['o.order_type'] = 1;
            $orderWhere['o.status'] = ['in',['free_processing','processing', 'complete', 'paypal_reversed', 'payment_review','paypal_canceled_reversal', 'delivered']];
            $sku_data = Db::name('datacenter_sku_day')
                ->where(['day_date'=>$data])
                ->field('id,platform_sku,site')
                ->select();
            $skuDatas = $this->order
                ->alias('o')
                ->join(['fa_order_item_option' => 'i'], 'o.entity_id=i.magento_order_id and i.site=o.site')
                ->where($orderWhere)
                ->group('sku,site')
                ->field('sku,i.site,sum(base_row_total-mw_rewardpoint_discount/qty-i.base_discount_amount) sku_grand_total,sum(base_row_total) sku_row_total')
                ->select();
            //当前站点的所有sku映射关系
            foreach ($sku_data as $v) {
                foreach ($skuDatas as $item){
                    if($v['platform_sku'] == $item['sku'] && $v['site'] == $item['site']){
                        $sku_grand_total = $item['sku_grand_total'];
                        $sku_row_total = $item['sku_row_total'];
                        Db::name('datacenter_sku_day')
                            ->where('id',$v['id'])
                            ->update(['sku_grand_total'=>$sku_grand_total,'sku_row_total'=>$sku_row_total]);
                        echo $v['id'].' is ok'."\n";
                    }
                }
                usleep(10000);
            }
        }
    }
}