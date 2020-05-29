<?php

namespace app\admin\controller\customer;

use app\common\controller\Backend;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Reader\Csv;
use PhpOffice\PhpSpreadsheet\Reader\Xls;
use PhpOffice\PhpSpreadsheet\Reader\Xlsx;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use think\Db;
use think\Exception;
use think\exception\PDOException;
use think\exception\ValidateException;

/**
 * B2B批发客户信息
 *
 * @icon fa fa-circle-o
 */
class WholesaleCustomer extends Backend
{

    /**
     * WholesaleCustomer模型对象
     * @var \app\admin\model\customer\WholesaleCustomer
     */
    protected $model = null;
    protected $noNeedLogin = ['detail'];

    public function _initialize()
    {
        parent::_initialize();
        $intention_level = [
            1 => '低',
            2 => '中',
            3 => '高',
        ];
        $isNo = [
            1 => '否',
            2 => '是'
        ];
        $siteType = [
            1 => 'Zeelool',
            2 => 'Voogueme',
            3 => 'Nihao',
            4 => 'Alibaba',
            5 => '主动开发',
            6 =>'Wesee'
        ];
        $this->assign(compact('intention_level', 'isNo', 'siteType'));
        $this->model = new \app\admin\model\customer\WholesaleCustomer;
    }

    /**
     * 默认生成的控制器所继承的父类中有index/add/edit/del/multi五个基础方法、destroy/restore/recyclebin三个回收站方法
     * 因此在当前控制器中可不用编写增删改查的代码,除非需要自己控制这部分逻辑
     * 需要将application/admin/library/traits/Backend.php中对应的方法复制到当前控制器,然后进行修改
     */

    /**
     * 查看 列表页面
     */
    public function index()
    {
        //设置过滤方法
        $this->request->filter(['strip_tags']);
        if ($this->request->isAjax()) {
            //如果发送的来源是Selectpage，则转发到Selectpage
            if ($this->request->request('keyField')) {
                return $this->selectpage();
            }
            $filter = json_decode($this->request->get('filter'), true);

            if ($filter['create_user_name']) {
                $admin = new \app\admin\model\Admin();
                $smap['nickname'] = ['like', '%' . $filter['create_user_name'] . '%'];
                $id = $admin->where($smap)->value('id');
                $filter['create_user_id'] = $id;
                unset($filter['create_user_name']);
            }
            $this->request->get(['filter' => json_encode($filter)]);
            list($where, $sort, $order, $offset, $limit) = $this->buildparams();


            $total = $this->model
                ->where($where)
                ->order($sort, $order)
                ->count();

            $list = $this->model
                ->where($where)
                ->order($sort, $order)
                ->limit($offset, $limit)
                ->select();

            $list = collection($list)->toArray();
            //查询用户表id
            $admin = new \app\admin\model\Admin();
            $userInfo = $admin->where('status', 'normal')->column('nickname', 'id');
            foreach ($list as $k => $val) {
                $list[$k]['create_user_name'] = $userInfo[$val['create_user_id']];

            }
            $result = array("total" => $total, "rows" => $list);
            return json($result);
        }
        $this->assignconfig('username', session('admin.nickname'));

        //添加按钮
        $this->assignconfig('customer_add', $this->auth->check('customer/wholesale_customer/add'));
        //修改按钮
        $this->assignconfig('customer_edit', $this->auth->check('customer/wholesale_customer/edit'));
        //删除按钮
        $this->assignconfig('customer_del', $this->auth->check('customer/wholesale_customer/del'));
        //导入按钮
        $this->assignconfig('customer_import', $this->auth->check('customer/wholesale_customer/import'));
        //导出按钮
        $this->assignconfig('customer_export', $this->auth->check('customer/wholesale_customer/batch_export_xls'));
        return $this->view->fetch();
    }


    /**
     * 添加
     */
    public function add()
    {
        if ($this->request->isPost()) {
            $params = $this->request->post("row/a");
            if ($params) {
                $params = $this->preExcludeFields($params);

                if ($this->dataLimit && $this->dataLimitFieldAutoFill) {
                    $params[$this->dataLimitField] = $this->auth->id;
                }
                $result = false;
                Db::startTrans();
                try {
                    //是否采用模型验证
                    if ($this->modelValidate) {
                        $name = str_replace("\\model\\", "\\validate\\", get_class($this->model));
                        $validate = is_bool($this->modelValidate) ? ($this->modelSceneValidate ? $name . '.add' : $name) : $this->modelValidate;
                        $this->model->validateFailException(true)->validate($validate);
                    }

                    $email_is_only=$this->model->where('email',$params['email'])->where('is_del',1)->where('site_type',$params['site_type'])->count();
                    if ($email_is_only >0){
                        $this->error(__('此邮箱已存在,请勿重复录入', ''));
                    }
                    /*$result = $this->model->getCustomerEmail($params['site_type'], $params['email']);
                    if (isset($result) && $result != 0) {
                        $params['is_order'] = 2;
                    } else {
                        $params['is_order'] = 1;
                    }*/
                    $params['create_user_id'] = session('admin.id');
                    $params['update_user_id'] = session('admin.id');
                    $params['update_time'] = date('Y-m-d H:i:s');
                    $params['create_time'] = date('Y-m-d H:i:s');
                    /*if (!empty($params['logo_images'])) { //有上传图片则 是否logo为有
                        $params['is_logo'] = 2;
                    }else{
                        $params['is_logo'] = 1;
                    }*/

                    $result = $this->model->allowField(true)->save($params);
                    Db::commit();
                } catch (ValidateException $e) {
                    Db::rollback();
                    $this->error($e->getMessage());
                } catch (PDOException $e) {
                    Db::rollback();
                    $this->error($e->getMessage());
                } catch (Exception $e) {
                    Db::rollback();
                    $this->error($e->getMessage());
                }
                if ($result !== false) {
                    $this->success('添加成功');
                } else {
                    $this->error(__('添加失败'));
                }
            }
            $this->error(__('Parameter %s can not be empty', ''));
        }
        $this->view->assign('demand_type', input('demand_type'));
        return $this->view->fetch();
    }

    /**
     * 编辑
     */
    public function edit($ids = null)
    {
        $row = $this->model->get($ids);
        if (!$row) {
            $this->error(__('No Results were found'));
        }
        $adminIds = $this->getDataLimitAdminIds();
        if (is_array($adminIds)) {
            if (!in_array($row[$this->dataLimitField], $adminIds)) {
                $this->error(__('You have no permission'));
            }
        }
        if ($this->request->isPost()) {
            $params = $this->request->post("row/a");
            if ($params) {
                $params = $this->preExcludeFields($params);
                $result = false;
                Db::startTrans();
                try {
                    //是否采用模型验证
                    if ($this->modelValidate) {
                        $name = str_replace("\\model\\", "\\validate\\", get_class($this->model));
                        $validate = is_bool($this->modelValidate) ? ($this->modelSceneValidate ? $name . '.edit' : $name) : $this->modelValidate;
                        $row->validateFailException(true)->validate($validate);
                    }
                    $email_is_only=$this->model->where('email',$params['email'])->where('is_del',1)->where('site_type',$params['site_type'])->count();
                    if ($email_is_only >1){
                        $this->error(__('此邮箱已存在,请勿重复录入', ''));
                    }

                    /*$result = $this->model->getCustomerEmail($params['site_type'], $params['email']);
                    if (isset($result) && $result != 0) {
                        $params['is_order'] = 2;
                    } else {
                        $params['is_order'] = 1;
                    }*/
                    $params['update_user_id'] = session('admin.id');
                    $params['update_time'] = date('Y-m-d H:i:s');

                    /*if (empty($params['logo_images'])) { //有上传图片则 是否logo为有
                        $params['is_logo'] = 1;
                    } else {
                        $params['is_logo'] = 2;
                    }*/

                    $result = $row->allowField(true)->save($params);
                    Db::commit();
                } catch (ValidateException $e) {
                    Db::rollback();
                    $this->error($e->getMessage());
                } catch (PDOException $e) {
                    Db::rollback();
                    $this->error($e->getMessage());
                } catch (Exception $e) {
                    Db::rollback();
                    $this->error($e->getMessage());
                }
                if ($result !== false) {
                    $this->success();
                } else {
                    $this->error(__('No rows were updated'));
                }
            }
            $this->error(__('Parameter %s can not be empty', ''));
        }
        $this->view->assign('demand_type', input('demand_type'));
        $this->view->assign("row", $row);
        return $this->view->fetch();
    }


    /**
     * 逻辑删除
     * */
    public function del($ids = "")
    {
        if ($this->request->isAjax()) {
            $data['is_del'] = 2;
            $data['update_user_id'] = session('admin.id');
            $data['update_time'] = date('Y-m-d H:i:s');
            $res = $this->model->allowField(true)->save($data, ['id' => input('ids')]);
            if ($res) {
                $this->success('成功');
            } else {
                $this->error('失败');
            }
        }
    }


    /**
     * 详情
     * */
    public function detail($ids = "")
    {
        $row = $this->model->get($ids);
        if (!$row) {
            $this->error(__('No Results were found'));
        }
        $adminIds = $this->getDataLimitAdminIds();
        if (is_array($adminIds)) {
            if (!in_array($row[$this->dataLimitField], $adminIds)) {
                $this->error(__('You have no permission'));
            }
        }
        $this->view->assign("row", $row);
        return $this->view->fetch();
    }


    /**
     * 导入镜片库存
     */
    public function import()
    {
        $file = $this->request->request('file');
        if (!$file) {
            $this->error(__('Parameter %s can not be empty', 'file'));
        }
        $filePath = ROOT_PATH . DS . 'public' . DS . $file;
        if (!is_file($filePath)) {
            $this->error(__('No results were found'));
        }
        //实例化reader
        $ext = pathinfo($filePath, PATHINFO_EXTENSION);
        if (!in_array($ext, ['csv', 'xls', 'xlsx'])) {
            $this->error(__('Unknown data format'));
        }
        if ($ext === 'csv') {
            $file = fopen($filePath, 'r');
            $filePath = tempnam(sys_get_temp_dir(), 'import_csv');
            $fp = fopen($filePath, "w");
            $n = 0;
            while ($line = fgets($file)) {
                $line = rtrim($line, "\n\r\0");
                $encoding = mb_detect_encoding($line, ['utf-8', 'gbk', 'latin1', 'big5']);
                if ($encoding != 'utf-8') {
                    $line = mb_convert_encoding($line, 'utf-8', $encoding);
                }
                if ($n == 0 || preg_match('/^".*"$/', $line)) {
                    fwrite($fp, $line . "\n");
                } else {
                    fwrite($fp, '"' . str_replace(['"', ','], ['""', '","'], $line) . "\"\n");
                }
                $n++;
            }
            fclose($file) || fclose($fp);

            $reader = new Csv();
        } elseif ($ext === 'xls') {
            $reader = new Xls();
        } else {
            $reader = new Xlsx();
        }

        //导入文件首行类型,默认是注释,如果需要使用字段名称请使用name
        //$importHeadType = isset($this->importHeadType) ? $this->importHeadType : 'comment';
        //模板文件列名
        $listName = ['电子邮箱', '客户名称', '电话', '国家', '来源类型', '意向等级', '是否下单', '是否一件代发', '是否logo', '备注信息', 'logo图片'];
        try {
            if (!$PHPExcel = $reader->load($filePath)) {
                $this->error(__('Unknown data format'));
            }
            $currentSheet = $PHPExcel->getSheet(0);  //读取文件中的第一个工作表
            $allColumn = $currentSheet->getHighestDataColumn(); //取得最大的列号
            $allRow = $currentSheet->getHighestRow(); //取得一共有多少行
            $maxColumnNumber = Coordinate::columnIndexFromString($allColumn);

            $fields = [];
            for ($currentRow = 1; $currentRow <= 1; $currentRow++) {
                for ($currentColumn = 1; $currentColumn <= 11; $currentColumn++) {
                    $val = $currentSheet->getCellByColumnAndRow($currentColumn, $currentRow)->getValue();
                    $fields[] = $val;
                }
            }
            //模板文件不正确
            if ($listName !== $fields) {
                throw new Exception("模板文件不正确！！");
            }

            $data = [];
            for ($currentRow = 2; $currentRow <= $allRow; $currentRow++) {
                for ($currentColumn = 1; $currentColumn <= $maxColumnNumber; $currentColumn++) {
                    $val = $currentSheet->getCellByColumnAndRow($currentColumn, $currentRow)->getCalculatedValue();
                    $data[$currentRow - 2][$currentColumn - 1] = is_null($val) ? '' : $val;
                }
            }
        } catch (Exception $exception) {
            $this->error($exception->getMessage());
        }
        if (!$data) {
            $this->error('未导入任何数据！！');
        }
        $email_is_only=$this->model->where('is_del',1)->field('email,site_type')->select();
        $old_data = collection($email_is_only)->toArray();

        //批量添加产品
        foreach ($data as $k => $v) {

            if (empty($v[0])) {
                $this->error('导入失败！！,电子邮箱不能为空');
            }
            if (empty($v[5])) {
                $this->error('导入失败！！,意向等级不能为空');
            }
            if (empty($v[4])) {
                $this->error('导入失败！！,来源类型不能为空');
            }
            $params[$k]['email'] = trim($v[0]);
            $params[$k]['customer_name'] = trim($v[1]);
            $params[$k]['mobile'] = $v[2];
            $params[$k]['country'] = $v[3];

            switch (strtolower($v[4])) {
                case 'zeelool':
                    $params[$k]['site_type']  = 1;
                    break;
                case 'voogueme':
                    $params[$k]['site_type']  = 2;
                    break;
                case 'nihao':
                    $params[$k]['site_type']  = 3;
                    break;
                case 'alibaba':
                    $params[$k]['site_type']  = 4;
                    break;
                case '主动开发':
                    $params[$k]['site_type']  = 5;
                    break;
                case 'wesee':
                    $params[$k]['site_type']  = 6;
                    break;
                default:
                    $this->error('导入失败！！,来源类型为:Zeelool,Voogueme,Nihao,Alibaba,主动开发,Wesee');
                    break;

            }
            switch ($v[5]) {
                case '低':
                    $params[$k]['intention_level'] = 1;
                    break;
                case '中':
                    $params[$k]['intention_level']  = 2;
                    break;
                case '高':
                    $params[$k]['intention_level']  = 3;
                    break;
                default:
                    $this->error('导入失败！！,意向等级为:高,中,低');
                    break;
            }


            //判断是否存在相同数据
            foreach ($old_data as $old_k =>$old_v){
                if ($params[$k]['site_type'] ==$old_v['site_type'] && $params[$k]['email'] == $old_v['email'] ){
                    $num=$k+2;
                    $this->error("邮箱:[".$params[$k]['email']."]已存在,请检查,第".$num.'行');
                }
            }



            $params[$k]['is_order'] = $this->checkIsType($v[6]);
            $params[$k]['is_behalf_of'] = $this->checkIsType($v[7]);
            $params[$k]['is_logo'] = $this->checkIsType($v[8]);
            $params[$k]['remark'] = $v[9];
            $params[$k]['logo_images'] = $v[10];


            $params[$k]['create_user_id'] = session('admin.id');
            $params[$k]['update_user_id'] = session('admin.id');
            $params[$k]['create_time'] = date('Y-m-d H:i:s', time());
            $params[$k]['update_time'] = date('Y-m-d H:i:s', time());
            $params[$k]['is_del'] = 1;
        }

        $result = $this->model->allowField(true)->saveAll($params);
        if ($result) {
            $this->success('导入成功！！');
        } else {
            $this->error('导入失败！！');
        }
    }


    public function checkIsType($value = '')
    {
        if (empty($value)) {
            return 1;
        } elseif ($value == '是') {
            return 2;
        } elseif ($value == '否') {
            return 1;
        } else {
            $this->error("是否下单、是否代发、是否logo必须为【是】或【否】！！");
        }
    }

    public function checkIsTypeNam($value = '')
    {
        if (empty($value)) {
            return null;
        } elseif ($value == 2) {
            return '是';
        } elseif ($value == 1) {
            return '否';
        }else{
            return null;
        }
    }


    /**
     * 批量导出xls
     *
     * @Description
     * @return void
     * @since 2020/02/28 14:45:39
     * @author wpl
     */
    public function batch_export_xls()
    {
        set_time_limit(0);
        ini_set('memory_limit', '512M');
        $ids = input('ids');


        if ($ids) {
            $map['id'] = ['in', $ids];
        }
        list($where) = $this->buildparams();
        $list = $this->model
            ->where($where)
            ->where($map)
            ->order('id desc')
            ->select();

        $admin = new \app\admin\model\Admin();
        $userInfo = $admin->where('status', 'normal')->column('nickname', 'id');

        $list = collection($list)->toArray();


        //从数据库查询需要的数据
        $spreadsheet = new Spreadsheet();
        //常规方式：利用setCellValue()填充数据
        $spreadsheet->setActiveSheetIndex(0)->setCellValue("A1", "电子邮箱")
            ->setCellValue("B1", "客户名称")
            ->setCellValue("C1", "电话");   //利用setCellValues()填充数据
        $spreadsheet->setActiveSheetIndex(0)->setCellValue("D1", "国家")
            ->setCellValue("E1", "来源类型");
        $spreadsheet->setActiveSheetIndex(0)->setCellValue("F1", "意向等级")
            ->setCellValue("G1", "是否下单");
        $spreadsheet->setActiveSheetIndex(0)->setCellValue("H1", "是否一件代发")
            ->setCellValue("I1", "是否logo")
            ->setCellValue("J1", "备注信息");
        $spreadsheet->setActiveSheetIndex(0)->setCellValue("K1", "logo图片");
        $spreadsheet->setActiveSheetIndex(0)->setCellValue("L1", "创建时间");
        $spreadsheet->setActiveSheetIndex(0)->setCellValue("M1", "创建人");
        foreach ($list as $key => $value) {

            switch ($value['site_type']) {
                case 1:
                    $value['site_type'] = 'Zeelool';
                    break;
                case 2:
                    $value['site_type'] = 'Voogueme';
                    break;
                case 3:
                    $value['site_type'] = 'Nihao';
                    break;
                case 4:
                    $value['site_type'] = 'Alibaba';
                    break;
                case 5:
                    $value['site_type'] = '主动开发';
                    break;
                case 6:
                    $value['site_type'] = 'Wesee';
                    break;
                default:
                    $value['site_type'] = '';
                    break;

            }

            switch ($value['intention_level']) {
                case 1:
                    $value['intention_level'] = '低';
                    break;
                case 2:
                    $value['intention_level'] = '中';
                    break;
                case 3:
                    $value['intention_level'] = '高';
                    break;
                default:
                    $value['intention_level'] = '';
                    break;
            }


            $spreadsheet->getActiveSheet()->setCellValueExplicit("A" . ($key * 1 + 2), $value['email'], \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
            $spreadsheet->getActiveSheet()->setCellValue("B" . ($key * 1 + 2), $value['customer_name']);
            $spreadsheet->getActiveSheet()->setCellValue("C" . ($key * 1 + 2), $value['mobile']);
            $spreadsheet->getActiveSheet()->setCellValue("D" . ($key * 1 + 2), $value['country']);
            $spreadsheet->getActiveSheet()->setCellValue("E" . ($key * 1 + 2), $value['site_type']);
            $spreadsheet->getActiveSheet()->setCellValue("F" . ($key * 1 + 2), $value['intention_level']);
            $spreadsheet->getActiveSheet()->setCellValue("G" . ($key * 1 + 2), $this->checkIsTypeNam($value['is_order']) );
            $spreadsheet->getActiveSheet()->setCellValue("H" . ($key * 1 + 2), $this->checkIsTypeNam($value['is_behalf_of']));
            $spreadsheet->getActiveSheet()->setCellValue("I" . ($key * 1 + 2), $this->checkIsTypeNam($value['is_logo']));
            $spreadsheet->getActiveSheet()->setCellValue("J" . ($key * 1 + 2), $value['remark']);
            $spreadsheet->getActiveSheet()->setCellValue("K" . ($key * 1 + 2), $value['logo_images']);
            $spreadsheet->getActiveSheet()->setCellValue("L" . ($key * 1 + 2), $value['create_time']);

            $spreadsheet->getActiveSheet()->setCellValue("M" . ($key * 1 + 2), $userInfo[$value['create_user_id']]);
        }

        //设置宽度
        $spreadsheet->getActiveSheet()->getColumnDimension('A')->setWidth(30);
        $spreadsheet->getActiveSheet()->getColumnDimension('B')->setWidth(40);
        $spreadsheet->getActiveSheet()->getColumnDimension('C')->setWidth(30);
        $spreadsheet->getActiveSheet()->getColumnDimension('D')->setWidth(20);
        $spreadsheet->getActiveSheet()->getColumnDimension('E')->setWidth(20);
        $spreadsheet->getActiveSheet()->getColumnDimension('F')->setWidth(15);
        $spreadsheet->getActiveSheet()->getColumnDimension('G')->setWidth(15);

        $spreadsheet->getActiveSheet()->getColumnDimension('H')->setWidth(40);
        $spreadsheet->getActiveSheet()->getColumnDimension('I')->setWidth(15);

        $spreadsheet->getActiveSheet()->getColumnDimension('J')->setWidth(20);
        $spreadsheet->getActiveSheet()->getColumnDimension('K')->setWidth(20);
        $spreadsheet->getActiveSheet()->getColumnDimension('L')->setWidth(30);
        $spreadsheet->getActiveSheet()->getColumnDimension('M')->setWidth(30);


        //设置边框
        $border = [
            'borders' => [
                'allBorders' => [
                    'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN, // 设置border样式
                    'color' => ['argb' => 'FF000000'], // 设置border颜色
                ],
            ],
        ];

        $spreadsheet->getDefaultStyle()->getFont()->setName('微软雅黑')->setSize(12);


        $setBorder = 'A1:' . $spreadsheet->getActiveSheet()->getHighestColumn() . $spreadsheet->getActiveSheet()->getHighestRow();
        $spreadsheet->getActiveSheet()->getStyle($setBorder)->applyFromArray($border);

        $spreadsheet->getActiveSheet()->getStyle('A1:N' . $spreadsheet->getActiveSheet()->getHighestRow())->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
        $spreadsheet->setActiveSheetIndex(0);

        $format = 'xlsx';
        $savename = '批发客户' . date("YmdHis", time());;

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
