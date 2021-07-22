<?php
/**
 * Shell.php
 * @author wangpenglei
 * @date   2021/7/1 10:13
 */

namespace app\admin\controller\shell;

use app\admin\model\order\order\NewOrder;
use app\admin\model\order\order\NewOrderItemProcess;
use think\console\Command;
use think\console\Input;
use think\console\Output;

class OrderPrescriptionType extends Command
{
    protected function configure()
    {
        $this->setName('orderPrescriptionType')->setDescription('根据订单加工类型分仓');
    }

    protected function execute(Input $input, Output $output)
    {
        $this->setOrderPrescriptionType();

        $output->writeln("ok");
    }

    protected function setOrderPrescriptionType()
    {
        $order = new NewOrder();
        $orderitemprocess = new NewOrderItemProcess();
        //查询Z站所有订单
        $list = $order->where('order_prescription_type', 0)
            ->where('site', 1)
            ->where('created_at', '>', strtotime('2021-07-22 16:30:00'))
            ->column('id');
        foreach ($list as $key => $value) {
            $order_type = $orderitemprocess->where('order_id', $value)->column('order_prescription_type');
            //查不到结果跳过 防止子单表延迟两分钟查不到数据
            if (!$order_type) {
                continue;
            }

            $data = [];
            if (in_array(3, $order_type)) {
                $type = 3;
            } elseif (in_array(2, $order_type)) {
                $type = 2;
            } else {
                $type = 1;
                //如果Z站全为仅镜框 则分到丹阳仓
                $data['stock_id'] = 2;
                $orderitemprocess->where('order_id', $value)->update(['stock_id' => 2]);
            }

            $data['order_prescription_type'] = $type;
            $order->where('id', $value)->update($data);
            echo $value . ' is ok' . "\n";
            usleep(100000);
        }
    }

}