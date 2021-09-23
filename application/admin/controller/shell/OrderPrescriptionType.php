<?php
/**
 * Shell.php
 * @author wangpenglei
 * @date   2021/7/1 10:13
 */

namespace app\admin\controller\shell;

use app\admin\model\order\order\NewOrder;
use app\admin\model\order\order\NewOrderItemOption;
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
        $orderitemoption = new NewOrderItemOption();
        //查询Z站所有订单
        $list = $order->where('order_prescription_type', 0)
            ->where('site', 1)
            ->where('created_at', '>', strtotime('2021-07-22 16:30:00'))
            ->field('id,entity_id')
            ->select();
        foreach ($list as $key => $value) {
            $order_type = $orderitemprocess->where('magento_order_id', $value['entity_id'])->where('site', 1)->column('order_prescription_type');
            //查不到结果跳过 防止子单表延迟两分钟查不到数据
            if (!$order_type) {
                continue;
            }

            $data = [];
            if (in_array(3, $order_type)) {
                $type = 3;
                $orderitemprocess->where('magento_order_id', $value['entity_id'])->where('site', 1)->update(['stock_id' => 2]);
            } elseif (in_array(2, $order_type)) {
                $type = 2;
                $orderitemprocess->where('magento_order_id', $value['entity_id'])->where('site', 1)->update(['stock_id' => 2]);
            } else {
                $type = 1;
                $orderitemprocess->where('magento_order_id', $value['entity_id'])->where('site', 1)->update(['stock_id' => 2, 'wave_order_id' => 0]);
            }

            // z站全部订单分配到丹阳仓
            $data['stock_id'] = 2;
            $data['order_prescription_type'] = $type;
            $data['updated_at'] = time();
            $order->where('id', $value['id'])->update($data);
            echo $value['id'] . ' is ok' . "\n";
            usleep(100000);
        }
    }

}