<?php
/**
 * 运营统计--仪表盘折线图脚本
 */

namespace app\admin\controller\shell\operate;

use think\console\Command;
use think\console\Input;
use think\console\Output;
use think\Db;

class DataMarketAsynData extends Command
{
    protected function configure()
    {
        $this->setName('data_market_asyn_data')
            ->addArgument('site')
            ->setDescription('data_market run');
    }

    protected function execute(Input $input, Output $output)
    {
        //$site = $input->getArgument('site');
        $this->getSalesOrderData();
        $output->writeln("All is ok");
    }

    /**
     * 获取仪表盘中折线图中数据
     * @author mjj
     * @date   2021/4/15 09:24:50
     */
    public function getSalesOrderData()
    {
        $voogmechicModel = Db::connect('database.db_voogueme_acc');
        $voogmechicModel
            ->table('sales_flat_order')
            ->query("set time_zone='+8:00'");
        $voogmechicModel->table('sales_flat_quote')
            ->query("set time_zone='+8:00'");
        $voogmechicModel->table('customer_entity')
            ->query("set time_zone='+8:00'");

        //获取数据储存表中数据
        $arr = Db::name('order_statistics')
            ->where('create_date', '>', '2020-11-04')
            ->field('id,create_date')
            ->select();
        foreach ($arr as $v) {
            //计算当天的销量
            $stime = $v['create_date'] . ' 00:00:00';
            $etime = $v['create_date'] . ' 23:59:59';
            $map['created_at'] = $date['created_at'] = $update['updated_at'] = ['between', [$stime, $etime]];
            $map['status'] = [
                'in',
                [
                    'free_processing',
                    'processing',
                    'paypal_reversed',
                    'paypal_canceled_reversal',
                    'complete',
                    'delivered'
                ]
            ];
            $map['order_type'] = 1;
            //voogmechic
            $voogmechicCount = $voogmechicModel
                ->table('sales_flat_order')
                ->where($map)
                ->count(1);
            $voogmechicTotal = $voogmechicModel
                ->table('sales_flat_order')
                ->where($map)
                ->sum('base_grand_total');
            //voogmechic客单价
            if ($voogmechicCount > 0) {
                $voogmechicUnitPrice = round(($voogmechicTotal / $voogmechicCount), 2);
            } else {
                $voogmechicUnitPrice = 0;
            }
            //voogmechic购物车数
            $voogmechicShoppingcartTotal = $voogmechicModel
                ->table('sales_flat_quote')
                ->where($date)
                ->where('base_grand_total', 'GT', 0)
                ->count('*');
            //voogmechic购物车更新数
            $voogmechicShoppingcartUpdateTotal = $voogmechicModel
                ->table('sales_flat_quote')
                ->where($update)
                ->where('base_grand_total', 'GT', 0)
                ->count('*');
            //voogmechic购物车转化率
            if ($voogmechicShoppingcartTotal > 0) {
                $voogmechicShoppingcartConversion = round(($voogmechicCount / $voogmechicShoppingcartTotal) * 100, 2);
            } else {
                $voogmechicShoppingcartConversion = 0;
            }
            //voogmechic购物车更新转化率
            if ($voogmechicShoppingcartUpdateTotal > 0) {
                $voogmechicShoppingcartUpdateConversion = round(($voogmechicCount / $voogmechicShoppingcartUpdateTotal) * 100,
                    2);
            } else {
                $voogmechicShoppingcartUpdateConversion = 0;
            }
            //voogmechic注册用户数
            $voogmechicRegisterCustomer = $voogmechicModel
                ->table('customer_entity')
                ->where($date)
                ->count('*');
            $data['voogmechic_sales_num'] = $voogmechicCount;
            $data['voogmechic_sales_money'] = $voogmechicTotal;
            $data['voogmechic_unit_price'] = $voogmechicUnitPrice;
            $data['voogmechic_shoppingcart_total'] = $voogmechicShoppingcartTotal;
            $data['voogmechic_shoppingcart_conversion'] = $voogmechicShoppingcartConversion;
            $data['voogmechic_register_customer'] = $voogmechicRegisterCustomer;
            $data['voogmechic_shoppingcart_update_total'] = $voogmechicShoppingcartUpdateTotal;
            $data['voogmechic_shoppingcart_update_conversion'] = $voogmechicShoppingcartUpdateConversion;
            Db::name('order_statistics')
                ->where('id', $v['id'])
                ->update($data);
            echo $v['id'] . '--' . $v['create_date'] . ' is ok' . "\n";
            usleep(10000);
        }
    }
}