<?php

namespace app\admin\controller;

use app\common\controller\Backend;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use Think\Db;

/**
 *
 *
 * @icon fa fa-circle-o
 */
class OcPrescriptionPic extends Backend
{

    /**
     * OcPrescriptionPic模型对象
     * @var \app\admin\model\OcPrescriptionPic
     */
    protected $model = null;

    public function _initialize()
    {
        parent::_initialize();
        $this->model = new \app\admin\model\OcPrescriptionPic;
        $this->zeelool = new \app\admin\model\order\order\Zeelool;
        $this->voogueme = new \app\admin\model\order\order\Voogueme;

    }

    /**
     * 默认生成的控制器所继承的父类中有index/add/edit/del/multi五个基础方法、destroy/restore/recyclebin三个回收站方法
     * 因此在当前控制器中可不用编写增删改查的代码,除非需要自己控制这部分逻辑
     * 需要将application/admin/library/traits/Backend.php中对应的方法复制到当前控制器,然后进行修改
     */


    /**
     * 查看
     */
    public function index()
    {
        //当前是否为关联查询
        $this->relationSearch = false;
        //设置过滤方法
        $this->request->filter(['strip_tags']);
        if ($this->request->isAjax())
        {
            //如果发送的来源是Selectpage，则转发到Selectpage
            if ($this->request->request('keyField'))
            {
                return $this->selectpage();
            }
            $WhereSql = ' id > 0';
            $filter = json_decode($this->request->get('filter'), true);
            list($where, $sort, $order,$offset,$limit) = $this->buildparams();
            if ($filter['id']){
                $WhereSql .= ' and id = '.$filter['id'];
            }
            if ($filter['status']){
                $WhereSql .= ' and status='.$filter['status'];
            }
            if ($filter['created_at']){
                $created_at = explode(' - ',$filter['created_at']);
                $WhereSql .= " and created_at between '$created_at[0]' and '$created_at[1]' ";
            }
            if ($filter['completion_time']){
                $completion_time = explode(' - ',$filter['completion_time']);
                $WhereSql .= " and completion_time between '$completion_time[0]' and '$completion_time[1]' ";
            }
            $model  = Db::connect('database.db_zeelool');
            $WhereOrder = '  ORDER BY  created_at desc';
            if ($filter['site']){
                if ($filter['site'] ==1){
                    $count = "SELECT COUNT(1) FROM zeelool_test.oc_prescription_pic where".$WhereSql;
                    $sql  = "SELECT zeelool_test.oc_prescription_pic.id AS id,zeelool_test.oc_prescription_pic.email AS email,zeelool_test.oc_prescription_pic.query AS query,
                                zeelool_test.oc_prescription_pic.pic AS pic ,zeelool_test.oc_prescription_pic.status AS status,zeelool_test.oc_prescription_pic.handler_name AS handler_name,
                                zeelool_test.oc_prescription_pic.created_at AS created_at,zeelool_test.oc_prescription_pic.completion_time AS completion_time,
                                zeelool_test.oc_prescription_pic.remarks AS remarks,1 as site FROM zeelool_test.oc_prescription_pic where".$WhereSql. $WhereOrder. " limit  ". $offset.','.$limit;
                }else{
                    $count = "SELECT COUNT(1) FROM vuetest_voogueme.oc_prescription_pic where".$WhereSql;
                    $sql  = "SELECT vuetest_voogueme.oc_prescription_pic.id AS id,vuetest_voogueme.oc_prescription_pic.email AS email,vuetest_voogueme.oc_prescription_pic.query AS query,
                                vuetest_voogueme.oc_prescription_pic.pic AS pic ,vuetest_voogueme.oc_prescription_pic.status AS status,vuetest_voogueme.oc_prescription_pic.handler_name AS handler_name,
                                vuetest_voogueme.oc_prescription_pic.created_at AS created_at,vuetest_voogueme.oc_prescription_pic.completion_time AS completion_time,
                                vuetest_voogueme.oc_prescription_pic.remarks AS remarks ,2 as site FROM vuetest_voogueme.oc_prescription_pic where".$WhereSql. $WhereOrder. " limit  ". $offset.','.$limit;
                }
                $count = $model->query($count);
                $total = $count[0]['COUNT(1)'];
            }else{
                $count = "SELECT COUNT(1) FROM zeelool_test.oc_prescription_pic where".$WhereSql." union all  SELECT COUNT(1) FROM vuetest_voogueme.oc_prescription_pic where".$WhereSql;
                $sql  = "SELECT zeelool_test.oc_prescription_pic.id AS id,zeelool_test.oc_prescription_pic.email AS email,zeelool_test.oc_prescription_pic.query AS query,
                                zeelool_test.oc_prescription_pic.pic AS pic ,zeelool_test.oc_prescription_pic.status AS status,zeelool_test.oc_prescription_pic.handler_name AS handler_name,
                                zeelool_test.oc_prescription_pic.created_at AS created_at,zeelool_test.oc_prescription_pic.completion_time AS completion_time,
                                zeelool_test.oc_prescription_pic.remarks AS remarks,1 as site FROM zeelool_test.oc_prescription_pic where".$WhereSql." union all  
                         SELECT vuetest_voogueme.oc_prescription_pic.id AS id,vuetest_voogueme.oc_prescription_pic.email AS email,vuetest_voogueme.oc_prescription_pic.query AS query,
                                vuetest_voogueme.oc_prescription_pic.pic AS pic ,vuetest_voogueme.oc_prescription_pic.status AS status,vuetest_voogueme.oc_prescription_pic.handler_name AS handler_name,
                                vuetest_voogueme.oc_prescription_pic.created_at AS created_at,vuetest_voogueme.oc_prescription_pic.completion_time AS completion_time,
                                vuetest_voogueme.oc_prescription_pic.remarks AS remarks,2 as site FROM vuetest_voogueme.oc_prescription_pic where".$WhereSql. $WhereOrder." limit  ". $offset.','.$limit;
                $count = $model->query($count);
                $total = $count[0]['COUNT(1)']  + $count[1]['COUNT(1)'];
            }
            $list  = $model->query($sql);

            foreach ($list as $key=>$item){
                $list[$key]['realy_pk'] = $item['id'].'-'.$item['site'];
                if ($item['status'] ==1){
                    $list[$key]['status']='未处理';
                }else{
                    $list[$key]['status']= '已处理';
                }
//                $list[$key]['created_at'] =date("Y-m-d H:i:s",strtotime($item['created_at'])+28800);;
            }

            $result = array("total" => $total, "rows" => $list);
            return json($result);
        }
        return $this->view->fetch();
    }
    /**
     * @param null $ids
     * @return string
     * @throws \think\Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * @throws \think\exception\PDOException
     * 问题详情
     */
    public function question_message($ids = null){

        if ($this->request->isPost()){
            $params = $this->request->post("row/a");
            if ($params['site'] ==1){
                $model = Db::connect('database.db_zeelool');
            }else{
                $model = Db::connect('database.db_voogueme');
            }
            $updata_queertion =$model->table('oc_prescription_pic')->where('id',$params['id'])->update(['status'=>2,'handler_name'=>$this->auth->nickname,'completion_time'=>date('Y-m-d H:i:s',time()),'remarks'=>$params['remarks']]);
            if ($updata_queertion){
                $this->success('操作成功','oc_prescription_pic/index');
            }else{
                $this->error('操作失败');
            }
        }
        $site = input('param.site');
        if ($site ==1){
            $model = Db::connect('database.db_zeelool');
            $url =config('url.zeelool_url').'/media';
        }else{
            $model = Db::connect('database.db_voogueme');
            $url =config('url.voogueme_url').'/media';
        }
        $row =$model->table('oc_prescription_pic')->where('id',$ids)->find();
        $photo_href = $row['pic'] =explode(',',$row['pic']);
        foreach ($photo_href as $key=>$item){
            $photo_href[$key]= $url.$item;
        }
        $row['pic'] = $photo_href;

        $this->assign('row',$row);
        $this->assign('zhandian',$site);


        return $this->view->fetch();
    }



    public function batch_export_xls()
    {
        $data  =  input('get.ids');
        if ($data){
            $ct =explode(',',$data);
            $ids = explode(',',$data);
            foreach ($ids as $key=>$item){
                $ids[$key] = explode('-',$item);
            }
            foreach ($ids as $key=>$item){
                if ($item[1] ==1 ){
                    $model = Db::connect('database.db_zeelool');
                }else{
                    $model = Db::connect('database.db_voogueme');
                }
                $list[] = $model->table('oc_prescription_pic')->where('id',$item[0])->find();
                $list[$key]['site'] = $item[1];
            }
        }else{

            $filter = json_decode($this->request->get('filter'), true);
            $WhereSql = ' id > 0';
            list($where, $sort, $order,$offset,$limit) = $this->buildparams();
            if ($filter['id']){
                $WhereSql .= ' and id = '.$filter['id'];
            }
            if ($filter['status']){
                $WhereSql .= ' and status='.$filter['status'];
            }
            if ($filter['created_at']){
                $created_at = explode(' - ',$filter['created_at']);
                $WhereSql .= " and created_at between '$created_at[0]' and '$created_at[1]' ";
            }
            if ($filter['completion_time']){
                $completion_time = explode(' - ',$filter['completion_time']);
                $WhereSql .= " and completion_time between '$completion_time[0]' and '$completion_time[1]' ";
            }
            $model  = Db::connect('database.db_zeelool');
            $WhereOrder = '  ORDER BY  created_at desc';
            if ($filter['site']){
                if ($filter['site'] ==1){
                   $sql  = "SELECT zeelool.oc_prescription_pic.id AS id,zeelool.oc_prescription_pic.email AS email,zeelool.oc_prescription_pic.query AS query,
                                zeelool.oc_prescription_pic.pic AS pic ,zeelool.oc_prescription_pic.status AS status,zeelool.oc_prescription_pic.handler_name AS handler_name,
                                zeelool.oc_prescription_pic.created_at AS created_at,zeelool.oc_prescription_pic.completion_time AS completion_time,
                                zeelool.oc_prescription_pic.remarks AS remarks,1 as site FROM zeelool.oc_prescription_pic where".$WhereSql . $WhereOrder;
                }else{
                   $sql  = "SELECT voogueme.oc_prescription_pic.id AS id,voogueme.oc_prescription_pic.email AS email,voogueme.oc_prescription_pic.query AS query,
                                voogueme.oc_prescription_pic.pic AS pic ,voogueme.oc_prescription_pic.status AS status,voogueme.oc_prescription_pic.handler_name AS handler_name,
                                voogueme.oc_prescription_pic.created_at AS created_at,voogueme.oc_prescription_pic.completion_time AS completion_time,
                                voogueme.oc_prescription_pic.remarks AS remarks ,2 as site FROM voogueme.oc_prescription_pic where".$WhereSql . $WhereOrder;
                }

            }else{
              $sql  = "SELECT zeelool.oc_prescription_pic.id AS id,zeelool.oc_prescription_pic.email AS email,zeelool.oc_prescription_pic.query AS query,
                                zeelool.oc_prescription_pic.pic AS pic ,zeelool.oc_prescription_pic.status AS status,zeelool.oc_prescription_pic.handler_name AS handler_name,
                                zeelool.oc_prescription_pic.created_at AS created_at,zeelool.oc_prescription_pic.completion_time AS completion_time,
                                zeelool.oc_prescription_pic.remarks AS remarks,1 as site FROM zeelool.oc_prescription_pic where".$WhereSql." union all  
                         SELECT voogueme.oc_prescription_pic.id AS id,voogueme.oc_prescription_pic.email AS email,voogueme.oc_prescription_pic.query AS query,
                                voogueme.oc_prescription_pic.pic AS pic ,voogueme.oc_prescription_pic.status AS status,voogueme.oc_prescription_pic.handler_name AS handler_name,
                                voogueme.oc_prescription_pic.created_at AS created_at,voogueme.oc_prescription_pic.completion_time AS completion_time,
                                voogueme.oc_prescription_pic.remarks AS remarks,2 as site FROM voogueme.oc_prescription_pic where".$WhereSql. $WhereOrder;


            }

            $list  = $model->query($sql);
        }
        foreach ($list as $key=>$item){
            if ($item['status'] ==1){
                $list[$key]['status']='未处理';
            }else{
                $list[$key]['status']= '已处理';
            }
            if ($item['site'] ==1){
                $list[$key]['site']='Z站';
            }else{
                $list[$key]['site']= 'V站';
            }
        }

        //从数据库查询需要的数据

        $spreadsheet = new Spreadsheet();

        $spreadsheet
            ->setActiveSheetIndex(0)
            ->setCellValue("A1", "站点")
            ->setCellValue("B1", "邮箱")
            ->setCellValue("C1", "问题详情")  //利用setCellValues()填充数据
            ->setCellValue("D1", "状态")
            ->setCellValue("E1", "处理人")
            ->setCellValue("F1", "创建时间")
            ->setCellValue("G1", "处理时间")
            ->setCellValue("H1", "备注");

        foreach ($list as $key => $value) {
            $spreadsheet->getActiveSheet()->setCellValueExplicit("A" . ($key * 1 + 2), $value['site'], \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
            $spreadsheet->getActiveSheet()->setCellValue("B" . ($key * 1 + 2), $value['email']);
            $spreadsheet->getActiveSheet()->setCellValue("C" . ($key * 1 + 2), $value['query']);
            $spreadsheet->getActiveSheet()->setCellValue("D" . ($key * 1 + 2), $value['status']);
            $spreadsheet->getActiveSheet()->setCellValue("E" . ($key * 1 + 2), $value['handler_name']);
            $spreadsheet->getActiveSheet()->setCellValue("F" . ($key * 1 + 2), $value['created_at']);
            $spreadsheet->getActiveSheet()->setCellValue("G" . ($key * 1 + 2), $value['completion_time']);
            $spreadsheet->getActiveSheet()->setCellValue("H" . ($key * 1 + 2), $value['remarks']);
        }
        //设置宽度
        $spreadsheet->getActiveSheet()->getColumnDimension('A')->setWidth(30);
        $spreadsheet->getActiveSheet()->getColumnDimension('B')->setWidth(40);
        $spreadsheet->getActiveSheet()->getColumnDimension('C')->setWidth(30);
        $spreadsheet->getActiveSheet()->getColumnDimension('D')->setWidth(20);
        $spreadsheet->getActiveSheet()->getColumnDimension('E')->setWidth(20);
        $spreadsheet->getActiveSheet()->getColumnDimension('F')->setWidth(15);
        $spreadsheet->getActiveSheet()->getColumnDimension('G')->setWidth(15);
        $spreadsheet->getActiveSheet()->getColumnDimension('H')->setWidth(15);

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

        $spreadsheet->getActiveSheet()->getStyle('A1:H1' . $spreadsheet->getActiveSheet()->getHighestRow())->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
        $spreadsheet->setActiveSheetIndex(0);

        $format = 'xlsx';
        $savename = '售前问题列表' . date("YmdHis", time());

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

}
