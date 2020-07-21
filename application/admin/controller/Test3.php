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

    /*
     * 跑数据
     * */
    public function track_time2()
    {
        $where['node_type'] = ['neq', 40];
        $order_node = Db::name('order_node')->where($where)->where('signing_time is not null')->select();
        $order_node = collection($order_node)->toArray();

        foreach ($order_node as $k => $v) {
            $update['signing_time'] = null;
            Db::name('order_node')->where('id', $v['id'])->update($update); //更新时间

            echo $v['id'] . "\n";
            usleep(20000);
        }
        echo "ok";
        die;
    }

    /*
     * 跑数据
     * */
    public function track_time()
    {

        set_time_limit(0);
        ini_set('memory_limit', '512M');

        $order_node = Db::name('order_node')->select();
        $order_node = collection($order_node)->toArray();

        foreach ($order_node as $k => $v) {
            if ($k > 88981) {
                if ($v['node_type'] >= 7) {
                    $where['site'] = $v['site'];
                    $where['order_id'] = $v['order_id'];
                    $where['node_type'] = 7;
                    $order_create_time = Db::name('order_node_detail')->where($where)->field('create_time')->find();

                    $update['delivery_time'] = $order_create_time['create_time']; //更新上网时间

                    Db::name('order_node')->where('id', $v['id'])->update($update); //更新时间
                    $update = array();
                    echo $k . '_' . $v['id'] . "\n";
                    usleep(20000);
                }
            }
        }
        echo "ok";
        die;
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


    
    //修改zendesk表中的承接人id
    public function zendesk_assign_modify()
    {
        $list = Db::name('Sheet1')->where('id', 'not in', ['383342686912', '381994479654'])->select();
        foreach ($list as $item) {
            Db::name('zendesk')->where('assignee_id', $item['id'])->update(['assign_id' => $item['admin_id'], 'due_id' => $item['admin_id'], 'recipient' => $item['admin_id']]);
            echo $item['id'] . ' is ok' . "\n";
        }
    }
    public function zendesk_tongyong()
    {
        $list = Db::name('zendesk')->where('assignee_id', 'in', ['383342686912', '381994479654'])->select();
        foreach ($list as $k => $v) {
            $due_id = Db::name('zendesk_comments')->where('zid', $v['id'])->where('is_admin', 1)->order('id desc')->value('due_id');
            Db::name('zendesk')->where('id', $v['id'])->update(['assign_id' => $due_id, 'due_id' => $due_id, 'recipient' => $due_id]);
            echo $k . "\n";
        }
        echo 'ok';
    }

    //修改zendesk表中zendesk的id
    public function zendesk_id_modify()
    {
        $this->zendesk_id1(1);
        $this->zendesk_id1(2);
    }
    public function zendesk_id1($type)
    {
        if ($type == 1) {
            $zendesk_str = '383342686912';
        } else {
            $zendesk_str = '381994479654';
        }
        $zendesk_arr['type'] = $type;
        Db::name('zendesk')->where($zendesk_arr)->update(['assignee_id' => $zendesk_str]);

        echo 'ok';
    }
    //修改comments表中的due_id
    public function zendesk_test()
    {
        //查询zendesk_comments
        $zendesk = Db::name('zendesk_comments')->field('a.id,a.author_id,b.assign_id')->alias('a')->join(['fa_zendesk' => 'b'], 'a.zid=b.id')->where('b.channel', 'email')->where('a.due_id', 0)->where('a.is_admin', 1)->where('a.author_id', 'not in', ['383342686912', '381994479654'])->select();
        $assign_arr = Db::name('zendesk_agents')->column('admin_id', 'old_agent_id');
        foreach ($zendesk as $k => $v) {
            //如果是公用账户 查询zendesk表 获取承接人id 更新评论表due_id
            Db::name('zendesk_comments')->where('id', $v['id'])->update(['due_id' => $assign_arr[$v['author_id']] ?: 0]);

            echo $k . "\n";
        }

        echo 'is ok';
    }
    //排查邮件中所有不匹配站点的邮件
    public function zendesk_plat_modify(){
        $zendesk = Db::name('zendesk')->field('assign_id,type,ticket_id,id')->where(['assign_id'=>['neq',0]])->limit(10)->select();
        $i = 0;
        foreach ($zendesk as $item){
            //查询该邮件的负责人的站点
            $admin_type = Db::name('zendesk_agents')->where('admin_id',$item['assign_id'])->value('type');
            if($admin_type){
                if($admin_type != $item['type']){
                    //查询该评论的最后一条记录
                    $due_id = Db::name('zendesk_comments')->where(['zid'=>$item['id'],'is_admin'=>1,'due_id'=>['neq',0]])->order('id','desc')->value('due_id');
                    if($due_id){
                        Db::name('zendesk')->where('id',$item['id'])->update(['assign_id'=>$due_id]);
                        echo $item['ticket_id']."\n";
                    }
                }
            }

        }
        dump($i);exit;
    }
}
