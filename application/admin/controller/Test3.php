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
        $zendesk = Db::name('zendesk')->field('assign_id,type,ticket_id,id')->where(['assign_id'=>['neq','0'],'status'=>['neq',5]])->select();
        $i = 0;
        foreach ($zendesk as $item){
            //查询该邮件的负责人的站点
            $admin_type = Db::name('zendesk_agents')->where('admin_id',$item['assign_id'])->value('type');
            if($admin_type){
                if($admin_type != $item['type']){
                    echo $item['id']."\n";
                    $i++;
                    /*//查询该评论的最后一条记录
                    $due_id = Db::name('zendesk_comments')->alias('z')->join('zendesk_agents a','z.due_id=a.admin_id')->where(['z.zid'=>$item['id'],'z.is_admin'=>1,'z.due_id'=>['neq',0],'a.type'=>$item['type']])->order('z.id','desc')->value('z.due_id');
                    if($due_id){
                        if($due_id == 75 || $due_id == 105){
                            $other_due_id = Db::name('zendesk_agents')->where(['type'=>$item['type'],'admin_id'=>['not in','75,105']])->value('admin_id');
                            Db::name('zendesk')->where('id',$item['id'])->update(['assign_id'=>$other_due_id]);
                        }else{
                            Db::name('zendesk')->where('id',$item['id'])->update(['assign_id'=>$due_id]);
                        }
                        echo $item['id']."\n";
                        $i++;
                    }*/
                }
            }
        }
        dump($i);exit;
    }
    //每天的回复量
    public function zendesk_data(){
        $this->zendeskTasks = new \app\admin\model\zendesk\ZendeskTasks;
        $this->zendeskComments = new \app\admin\model\zendesk\ZendeskComments;
        $customer = $this->zendeskTasks->where(['reply_count'=>0])->order('id','desc')->select();
        $customer = collection($customer)->toArray();
        foreach ($customer as $item){
            //获取当前时间
            $create = explode(' ',$item['create_time']);
            $start = $create[0];
            $end = date('Y-m-d 23:59:59',strtotime($start));
            $where['is_admin'] = 1;
            $where['due_id'] = $item['admin_id'];
            $where['update_time'] = ['between', [$start, $end]];
            $count = $this->zendeskComments->where($where)->count();
            Db::name('zendesk_tasks')->where('id',$item['id'])->update(['reply_count'=>$count]);
            echo $item['id'].'--'.$item['admin_id'].'--'.$count.' is ok'."\n";
            sleep(1);
        }
    }
    //没有承接人的数据
    public function zendesk_no_assign(){
        //查询没有承接人的数据
        $where[] = ['exp',Db::raw("assign_id is null or assign_id = 0")];
        $where['due_id'] = ['neq',0];
        $zendesk = Db::name('zendesk')->where($where)->select();
        foreach ($zendesk as $item){
            //查询评论最多的人
            $arr['is_admin'] = 1;
            $arr['zid'] = $item['id'];
            $arr['due_id'] = ['not in','75,105,95,117'];
            $comments = Db::name('zendesk_comments')->where($arr)->group('due_id')->field('due_id,count(due_id) as count')->order('count','desc')->select();
            $assign_id = 0;
            foreach ($comments as $value){
                //查询该用户的站点是否和当前站点一致
                $types = Db::name('zendesk_agents')->where('admin_id',$value['due_id'])->column('type');
                if($types && in_array($item['type'],$types)){
                    $assign_id = $value['due_id'];
                    break;
                }
            }
            if($assign_id == 0){
                $assign_id = $item['due_id'];
            }
            Db::name('zendesk')->where('id',$item['id'])->update(['assign_id'=>$assign_id]);
            echo $item['id'].'--'.$item['assign_id'].'--'.$assign_id.' is ok'."\n";
        }
        echo "all is ok";
    }
    /**
     * 测试
     *
     * @Description
     * @author wpl
     * @since 2020/06/06 15:19:57 
     * @return void
     */
    public function test()
    {
        //session_start();
        $client = new \Google_Client();
        $client->setAuthConfig('./oauth-credentials.json');
        $client->addScope(\Google_Service_Analytics::ANALYTICS_READONLY);
        // Create an authorized analytics service object.
        $analytics = new \Google_Service_AnalyticsReporting($client);
        $startDate = '2020-08-14';
        $endDate = '2020-08-14';
        // Call the Analytics Reporting API V4.
        $response = $this->getReport($analytics, $startDate, $endDate);
        // Print the response.
        $result = $this->printResults($response);
        dump($result);
        dump($result[0]['ga:adCost']);die;


        // if (isset($_SESSION['access_token']) && $_SESSION['access_token']) {
        //     // Set the access token on the client.
        //     $client->setAccessToken($_SESSION['access_token']);

            
        // } else {
        //     $redirect_uri = 'http://' . $_SERVER['HTTP_HOST'] . '/oauth2callback.php';
        //     header('Location: ' . filter_var($redirect_uri, FILTER_SANITIZE_URL));
        // }
    }
    protected function getReport($analytics, $startDate, $endDate)
    {

        // Replace with your view ID, for example XXXX.
        // $VIEW_ID = "168154683";
        // $VIEW_ID = "172731925";
        $VIEW_ID = config('GOOGLE_ANALYTICS_VIEW_ID');


        // Replace with your view ID, for example XXXX.
        // $VIEW_ID = "<REPLACE_WITH_VIEW_ID>";

        $dateRange = new \Google_Service_AnalyticsReporting_DateRange();
        $dateRange->setStartDate($startDate);
        $dateRange->setEndDate($endDate);   

        $adCostMetric = new \Google_Service_AnalyticsReporting_Metric();
        $adCostMetric->setExpression("ga:adCost");
        $adCostMetric->setAlias("ga:adCost");

        // Create the ReportRequest object.
        $request = new \Google_Service_AnalyticsReporting_ReportRequest();
        $request->setViewId($VIEW_ID);
        $request->setDateRanges($dateRange);
        $request->setMetrics(array($adCostMetric));
        // $request->setDimensions(array($sessionDayDimension));

        $body = new \Google_Service_AnalyticsReporting_GetReportsRequest();
        $body->setReportRequests(array($request));
        return $analytics->reports->batchGet($body);

    }
    /**
     * Parses and prints the Analytics Reporting API V4 response.
     *
     * @param An Analytics Reporting API V4 response.
     */
    protected function printResults($reports)
    {
        $finalResult = array();
        for ($reportIndex = 0; $reportIndex < count($reports); $reportIndex++) {
            $report = $reports[$reportIndex];
            $header = $report->getColumnHeader();
            $dimensionHeaders = $header->getDimensions();
            $metricHeaders = $header->getMetricHeader()->getMetricHeaderEntries();
            $rows = $report->getData()->getRows();
            for ($rowIndex = 0; $rowIndex < count($rows); $rowIndex++) {
                $row = $rows[$rowIndex];
                $dimensions = $row->getDimensions();
                $metrics = $row->getMetrics();
                for ($i = 0; $i < count($dimensionHeaders) && $i < count($dimensions); $i++) {
                    $finalResult[$rowIndex][$dimensionHeaders[$i]] = $dimensions[$i];
                }

                for ($j = 0; $j < count($metrics); $j++) {
                    $values = $metrics[$j]->getValues();
                    for ($k = 0; $k < count($values); $k++) {
                        $entry = $metricHeaders[$k];
                        $finalResult[$rowIndex][$entry->getName()] = $values[$k];
                    }
                }
            }
            return $finalResult;
        }
    }
}
