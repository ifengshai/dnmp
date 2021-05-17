<?php
/**
 * 运营统计--用户复购率分析脚本
 */
namespace app\admin\controller\shell\supply;
use think\console\Command;
use think\console\Input;
use think\console\Output;
use think\Db;

class PurchaseAsynData extends Command
{
    public function __construct()
    {
        $this->purchase = new \app\admin\model\purchase\PurchaseOrder();
        parent::__construct();
    }

    protected function configure()
    {
        $this->setName('purchase_asyn_data')
            ->setDescription('purchase run');
    }

    protected function execute(Input $input, Output $output)
    {
        //$this->purchaseDay();   //采购单每天的数据
        $this->purchaseMonth();   //采购单每月的数据
        $output->writeln("All is ok");
    }
    public function purchaseDay()
    {
        $data = Db::name('warehouse_data')
            ->column('create_date','id');
        $map['is_del'] = 1;
        $map['purchase_status'] = ['in', [2, 5, 6, 7, 8, 9, 10]];
        foreach ($data as $k=>$val){
            $map['createtime'] = ['between', [$val . ' 00:00:00', $val . ' 23:59:59']];
            $purchase_num = $this->purchase
                ->alias('a')
                ->where($map)
                ->join('fa_purchase_order_item b', 'a.id=b.purchase_id')
                ->sum('b.purchase_num');
            Db::name('warehouse_data')
                ->where('id',$k)
                ->update(['all_purchase_num'=>$purchase_num]);
            echo $val." is ok"."\n";
            usleep(10000);
        }
    }
    public function purchaseMonth(){
        $data = Db::name('datacenter_supply_month')
            ->column('day_date','id');
        foreach($data as $k=>$v){
            $start =  $v.'-01';
            $end = date('Y-m-t 23:59:59',strtotime($start));
            $map['create_time'] = ['between',[$start,$end]];
            $purchase_num = Db::name('warehouse_data')
                ->where($map)
                ->sum('all_purchase_num');
            Db::name('datacenter_supply_month')
                ->where('id',$k)
                ->update(['purchase_num'=>$purchase_num]);
            echo $v." is ok"."\n";
            usleep(10000);
        }
    }
}