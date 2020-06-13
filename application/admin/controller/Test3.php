<?php

namespace app\admin\controller;

use app\admin\model\Elaticsearch;
use app\common\controller\Backend;
use think\Db;
use PhpOffice\PhpSpreadsheet\Spreadsheet;


class Test3 extends Backend
{

    protected $noNeedLogin = ['*'];
    public function _initialize()
    {
        parent::_initialize();

        //$this->es = new Elaticsearch();
    }
    /**
     * id 订单号，物流商，运单号，当前节点状态，从上网到最终状态的时间有多久(如果大状态为4，则代表最终状态)
     *
     * @Description
     * @author mjj
     * @since 2020/06/02 10:11:53 
     * @return void
     */
    public function export_order_node()
    {
        set_time_limit(0);
        ini_set('memory_limit', '512M');
        //查询物流结点
        $where['d.order_node'] = 3;
        $where['d.node_type'] = 8;
        $where['d.create_time'] = ['between', ['2020-05-01', '2020-05-10']];
        $order = Db::name('order_node')->alias('o')->field('o.order_id,o.shipment_type,o.track_number,o.order_node,d.create_time')->where($where)->join(['fa_order_node_detail' => 'd'], 'o.order_id=d.order_id')->select();
        $arr = array();
        $i = 0;
        foreach ($order as $key => $item) {
            $arr[$i]['order_id'] = $item['order_id'];
            $arr[$i]['shipment_type'] = $item['shipment_type'];
            $arr[$i]['track_number'] = $item['track_number'];
            if ($item['order_node'] == 0) {
                $order_node = '客户';
            } elseif ($item['order_node'] == 1) {
                $order_node = '等待加工';
            } elseif ($item['order_node'] == 2) {
                $order_node = '加工备货';
            } elseif ($item['order_node'] == 3) {
                $order_node = '快递物流';
            } elseif ($item['order_node'] == 4) {
                $order_node = '完成';
            }
            $arr[$i]['node_type'] = $order_node;
            $arr[$i]['create_time'] = $item['create_time'];
            //查询是否有最终状态时间
            $endtime = Db('order_node_detail')->where(['order_node' => 4, 'order_id' => $item['order_id']])->order('id asc')->value('create_time');
            if ($endtime) {
                $arr[$i]['complete_time'] = $endtime;
                $time = floor((strtotime($endtime) - strtotime($item['create_time'])) / 3600);
                $hour_num = $time % 24;
                $arr[$i]['day'] = floor($time / 24) . '天' . $hour_num . '个小时';
            } else {
                $arr[$i]['complete_time'] = '';
                $arr[$i]['day'] = 0;
            }
            $i++;
        }
        //从数据库查询需要的数据
        $spreadsheet = new Spreadsheet();

        //常规方式：利用setCellValue()填充数据
        $spreadsheet->setActiveSheetIndex(0)->setCellValue("A1", "订单号")
            ->setCellValue("B1", "物流商")
            ->setCellValue("C1", "运单号");   //利用setCellValues()填充数据
        $spreadsheet->setActiveSheetIndex(0)->setCellValue("D1", "当前节点状态")
            ->setCellValue("E1", "上网时间");
        $spreadsheet->setActiveSheetIndex(0)->setCellValue("F1", "完成时间")
            ->setCellValue("G1", "时长");

        foreach ($arr as $key => $value) {

            $spreadsheet->getActiveSheet()->setCellValueExplicit("A" . ($key * 1 + 2), $value['order_id'], \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
            $spreadsheet->getActiveSheet()->setCellValue("B" . ($key * 1 + 2), $value['shipment_type']);
            $spreadsheet->getActiveSheet()->setCellValue("C" . ($key * 1 + 2), $value['track_number']);
            $spreadsheet->getActiveSheet()->setCellValue("D" . ($key * 1 + 2), $value['node_type']);
            $spreadsheet->getActiveSheet()->setCellValue("E" . ($key * 1 + 2), $value['create_time']);
            $spreadsheet->getActiveSheet()->setCellValue("F" . ($key * 1 + 2), $value['complete_time']);
            $spreadsheet->getActiveSheet()->setCellValue("G" . ($key * 1 + 2), $value['day']);
        }

        //设置宽度
        $spreadsheet->getActiveSheet()->getColumnDimension('A')->setWidth(30);
        $spreadsheet->getActiveSheet()->getColumnDimension('B')->setWidth(40);
        $spreadsheet->getActiveSheet()->getColumnDimension('C')->setWidth(30);
        $spreadsheet->getActiveSheet()->getColumnDimension('D')->setWidth(20);
        $spreadsheet->getActiveSheet()->getColumnDimension('E')->setWidth(20);
        $spreadsheet->getActiveSheet()->getColumnDimension('F')->setWidth(15);
        $spreadsheet->getActiveSheet()->getColumnDimension('G')->setWidth(15);

        //设置边框
        $border = [
            'borders' => [
                'allBorders' => [
                    'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN, // 设置border样式
                    'color'       => ['argb' => 'FF000000'], // 设置border颜色
                ],
            ],
        ];

        $spreadsheet->getDefaultStyle()->getFont()->setName('微软雅黑')->setSize(12);


        $setBorder = 'A1:' . $spreadsheet->getActiveSheet()->getHighestColumn() . $spreadsheet->getActiveSheet()->getHighestRow();
        $spreadsheet->getActiveSheet()->getStyle($setBorder)->applyFromArray($border);

        $spreadsheet->getActiveSheet()->getStyle('A1:N' . $spreadsheet->getActiveSheet()->getHighestRow())->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
        $spreadsheet->setActiveSheetIndex(0);

        $format = 'xlsx';
        $savename = '物流信息' . date("YmdHis", time());;

        if ($format == 'xls') {
            //输出Excel03版本
            header('Content-Type:application/vnd.ms-excel');
            $class = "\PhpOffice\PhpSpreadsheet\Writer\Xls";
        } elseif ($format == 'xlsx') {
            //输出07Excel版本
            header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
            $class = "\PhpOffice\PhpSpreadsheet\Writer\Xlsx";
        }

        //输出名称
        header('Content-Disposition: attachment;filename="' . $savename . '.' . $format . '"');
        //禁止缓存
        header('Cache-Control: max-age=0');
        $writer = new $class($spreadsheet);

        $writer->save('php://output');
    }


    /**
     * 处理在途库存
     *
     * @Description
     * @author wpl
     * @since 2020/06/09 10:08:03 
     * @return void
     */
    public function proccess_stock()
    {
        $item = new \app\admin\model\itemmanage\Item();
        $result = $item->where(['is_open' => 1, 'is_del' => 1])->field('sku,id')->select();
        $result = collection($result)->toArray();
        $skus = array_column($result, 'sku');
        //计算SKU总采购数量
        $purchase = new \app\admin\model\purchase\PurchaseOrder;
        $hasWhere['sku'] = ['in', $skus];
        $purchase_map['purchase_status'] = ['in', [2, 5, 6, 7]];
        $purchase_map['stock_status'] = ['in', [0, 1]];
        $purchase_map['is_del'] = 1;
        $purchase_list = $purchase->hasWhere('purchaseOrderItem', $hasWhere)
            ->where($purchase_map)
            ->group('sku')
            ->column('sum(purchase_num) as purchase_num', 'sku');

        //查询出满足条件的采购单号
        $ids = $purchase->hasWhere('purchaseOrderItem', $hasWhere)
            ->where($purchase_map)
            ->group('PurchaseOrder.id')
            ->column('PurchaseOrder.id');

        //查询留样库存
        //查询实际采购信息 查询在途库存 = 采购数量 减去 到货数量
        $check_map['status'] = 2;
        $check_map['type'] = 1;
        $check_map['Check.purchase_id'] = ['in', $ids];
        $check = new \app\admin\model\warehouse\Check;
        $hasWhere['sku'] = ['in', $skus];
        $check_list = $check->hasWhere('checkItem', $hasWhere)
            ->where($check_map)
            ->group('sku')
            ->column('sum(arrivals_num) as arrivals_num', 'sku');
        foreach ($result as &$v) {
            $on_way_stock = @$purchase_list[$v['sku']] - @$check_list[$v['sku']];
            $v['on_way_stock'] = $on_way_stock > 0 ? $on_way_stock : 0;
        }
        unset($v);
        $res = $item->saveAll($result);
        echo $res;
        die;
    }

    /**
     * 处理质检单状态
     *
     * @Description
     * @author wpl
     * @since 2020/06/09 10:09:17 
     * @return void
     */
    public function process_status()
    {
        $logistics_info = new \app\admin\model\LogisticsInfo();
        $purchase = new \app\admin\model\purchase\PurchaseOrder();
        $list = $logistics_info->select();
        $list = collection($list)->toArray();
        foreach ($list as $k => $v) {
            $status = $purchase->where(['id' => $v['purchase_id']])->value('check_status');
            if ($status == 2) {
                $logistics_info->where(['id' => $v['id']])->update(['status' => 1]);
            }
            echo $k . "\n";
        }
        echo 'ok';
    }

    /**
     * 处理质检单状态
     *
     * @Description
     * @author wpl
     * @since 2020/06/09 10:09:17 
     * @return void
     */
    public function process_check()
    {
        $logistics_info = new \app\admin\model\LogisticsInfo();
        $check = new \app\admin\model\warehouse\Check();
        $list = $logistics_info->select();
        $list = collection($list)->toArray();
        foreach ($list as $k => $v) {
            $count = $check->where(['purchase_id' => $v['purchase_id'], 'status' => 2])->count();
            if ($count > 0) {
                $logistics_info->where(['id' => $v['id']])->update(['is_check_order' => 1]);
            }
            echo $k . "\n";
            usleep(50000);
        }
        echo 'ok';
    }

    public function process_logstatic()
    {
        $logistics_info = new \app\admin\model\LogisticsInfo();
        $purchase = new \app\admin\model\purchase\PurchaseOrder();
        $list = $logistics_info->where(['type' => 1])->select();
        $list = collection($list)->toArray();
        foreach ($list as $k => $v) {
            $purchase_info = $purchase->where(['id' => $v['purchase_id']])->field('logistics_company_no,purchase_type')->find();
            if ($purchase_info['purchase_type'] == 1) {
                $data['source'] = 1;
            } else {
                $data['source'] = 2;
            }
            $data['logistics_company_no'] = $purchase_info['logistics_company_no'];
            $logistics_info->where(['id' => $v['id']])->update($data);
            echo $k . "\n";
        }
        echo 'ok';
    }

    /**
     * 修改支付时间
     *
     * @Description
     * @author wpl
     * @since 2020/06/08 17:02:59 
     * @return void
     */
    // public function setPayTime()
    // {
    //     ini_set('memory_limit', '512M');
    //     $order_node_detail = new \app\admin\model\OrderNodeDetail();
    //     $list = $order_node_detail->where(['order_node' => 0, 'node_type' => 0])->field('create_time,order_id,site')->select();
    //     $list = collection($list)->toArray();
    //     foreach ($list as $k => $v) {
    //         $order_node_detail->where(['order_id' => $v['order_id'], 'order_node' => 0, 'node_type' => 1, 'site' => $v['site']])->update(['create_time' => $v['create_time']]);

    //         echo $v['order_id'] . "\n";
    //         usleep(50000);
    //     }
    //     echo 'ok';
    //     die;
    // }

}
