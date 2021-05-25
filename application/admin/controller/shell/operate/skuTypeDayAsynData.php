<?php
/**
 * 运营统计--仪表盘折线图脚本
 */

namespace app\admin\controller\shell\operate;

use think\console\Command;
use think\console\Input;
use think\console\Output;
use think\Db;

class skuTypeDayAsynData extends Command
{
    protected function configure()
    {
        $this->setName('sku_type_day')
            ->setDescription('sku_type_day run');
    }

    protected function execute(Input $input, Output $output)
    {
        $this->getSkuDayData(5);
        $output->writeln("All is ok");
    }


    /**
     * 统计各品类镜框的销量
     * @param $site  站点
     * @param $goods_type   商品类型
     * @author mjj
     * @date   2021/5/22 11:47:52
     */
    public function getSkuDayData($site)
    {
        $this->order = new \app\admin\model\order\order\NewOrder();
        $this->orderitemoption = new \app\admin\model\order\order\NewOrderItemOption();
        $tStart = strtotime('2020-01-01');
        $tend = time();
        for($i = $tStart;$i<$tend;$i+=3600*24){
            $dayDate = date('Y-m-d',$i);
            $start = $i;
            $end = strtotime(date('Y-m-d 23:59:59', $i));
            $timeWhere['o.payment_time'] = ['between',[$start,$end]];
            $res1 = Db::name('datacenter_goods_type_data')
                ->insert($this->getSkuData($site,$dayDate,$timeWhere,1));
            if ($res1) {
                echo $dayDate.'-站点：'.$site.'-平光镜ok';
            } else {
                echo $dayDate.'-站点：'.$site.'-平光镜不ok';
            }
            $res2 = Db::name('datacenter_goods_type_data')
                ->insert($this->getSkuData($site,$dayDate,$timeWhere,2));
            if ($res2) {
                echo $dayDate.'-站点：'.$site.'-太阳镜ok';
            } else {
                echo $dayDate.'-站点：'.$site.'-太阳镜不ok';
            }
            $res3 = Db::name('datacenter_goods_type_data')
                ->insert($this->getSkuData($site,$dayDate,$timeWhere,3));
            if ($res3) {
                echo $dayDate.'-站点：'.$site.'-老花镜ok';
            } else {
                echo $dayDate.'-站点：'.$site.'-老花镜不ok';
            }
            $res4 = Db::name('datacenter_goods_type_data')
                ->insert($this->getSkuData($site,$dayDate,$timeWhere,4));
            if ($res4) {
                echo $dayDate.'-站点：'.$site.'-儿童镜ok';
            } else {
                echo $dayDate.'-站点：'.$site.'-儿童镜不ok';
            }
            $res5 = Db::name('datacenter_goods_type_data')
                ->insert($this->getSkuData($site,$dayDate,$timeWhere,5));
            if ($res5) {
                echo $dayDate.'-站点：'.$site.'-运动镜ok';
            } else {
                echo $dayDate.'-站点：'.$site.'-运动镜不ok';
            }
            $res6 = Db::name('datacenter_goods_type_data')
                ->insert($this->getSkuData($site,$dayDate,$timeWhere,6));
            if ($res6) {
                echo $dayDate.'-站点：'.$site.'-配饰ok';
            } else {
                echo $dayDate.'-站点：'.$site.'-配饰不ok';
            }
        }
    }
    public function getSkuData($site,$dayDate,$timeWhere,$goods_type)
    {
        $this->order = new \app\admin\model\order\order\NewOrder();
        $this->orderitemoption = new \app\admin\model\order\order\NewOrderItemOption();
        $where['o.site'] = $site;
        $where['o.order_type'] = 1;
        $where['o.status'] = ['in',['free_processing','processing', 'complete', 'paypal_reversed', 'payment_review','paypal_canceled_reversal', 'delivered']];
        $where['goods_type'] = $goods_type;
        //某个品类眼镜的销售副数
        $frame_sales_num = $this->orderitemoption
            ->alias('i')
            ->join('fa_order o', 'i.magento_order_id=o.entity_id', 'left')
            ->where($timeWhere)
            ->where($where)
            ->sum('i.qty');
        //眼镜的折扣价格
        $frame_money = $this->orderitemoption
            ->alias('i')
            ->join('fa_order o', 'i.magento_order_id=o.entity_id', 'left')
            ->where($timeWhere)
            ->where($where)
            ->value('sum(base_original_price-i.base_discount_amount) as price');
        $frame_money = $frame_money ? round($frame_money, 2) :0;
        $arr['day_date'] = $dayDate;
        $arr['site'] = $site;
        $arr['goods_type'] = $goods_type;
        $arr['glass_num'] = $frame_sales_num;
        $arr['sales_total_money'] = $frame_money;
        return $arr;
    }

}