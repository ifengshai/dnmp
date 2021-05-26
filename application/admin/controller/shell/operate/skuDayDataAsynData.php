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
        $this->getSkuDayData(1);
        $this->getSkuDayData(2);
        $this->getSkuDayData(3);
        $this->getSkuDayData(5);
        $this->getSkuDayData(10);
        $this->getSkuDayData(11);
        $output->writeln("All is ok");
    }

    public function getSkuDayData($site)
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
            $orderWhere['o.site'] = $site;
            $orderWhere['o.order_type'] = 1;
            $orderWhere['o.status'] = ['in',['free_processing','processing', 'complete', 'paypal_reversed', 'payment_review','paypal_canceled_reversal', 'delivered']];
            $sku_data = Db::name('datacenter_sku_day')
                ->where(['site' => $site,'day_date'=>$data])
                ->field('id,platform_sku')
                ->select();
            $sku_data = collection($sku_data)->toArray();
            $skuDatas = $this->order
                ->alias('o')
                ->join(['fa_order_item_option' => 'i'], 'o.entity_id=i.magento_order_id')
                ->where($orderWhere)
                ->whereIn('sku',array_column($sku_data,'platform_sku'))
                ->field('sku,qty,i.lens_price,i.coating_price')
                ->select();
            $skuArr = [];
            foreach ($skuDatas as $key=>$value){
                if($value['lens_price']>0 || $value['coating_price']>0){
                    $skuArr[$value['sku']]['pay_lens_num'] += $value['qty'];
                }
            }
            //当前站点的所有sku映射关系
            foreach ($sku_data as $k => $v) {
                $pay_lens_num = $skuArr[$v['platform_sku']]['pay_lens_num'] ? $skuArr[$v['platform_sku']]['pay_lens_num'] : 0;
                Db::name('datacenter_sku_day')
                    ->where('id',$v['id'])
                    ->update(['pay_lens_num'=>$pay_lens_num]);
                echo $v['id'].' is ok'."\n";
                usleep(10000);
            }
        }
    }

}