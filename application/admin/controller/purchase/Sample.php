<?php

namespace app\admin\controller\purchase;

use app\common\controller\Backend;
use think\Db;
use think\Exception;
use think\exception\PDOException;
use think\exception\ValidateException;

/**
 * 
 *
 * @icon fa fa-circle-o
 */
class Sample extends Backend
{
    
    /**
     * Sample模型对象
     * @var \app\admin\model\purchase\Sample
     */
    protected $model = null;

    public function _initialize()
    {
        parent::_initialize();
        $this->sample = new \app\admin\model\purchase\Sample;
        $this->samplelocation = new \app\admin\model\purchase\SampleLocation;
        $this->sampleworkorder = new \app\admin\model\purchase\SampleWorkorder;
        $this->samplelendlog = new \app\admin\model\purchase\SampleLendlog;
        $this->item = new \app\admin\model\itemmanage\Item;

    }
    
    /**
     * 默认生成的控制器所继承的父类中有index/add/edit/del/multi五个基础方法、destroy/restore/recyclebin三个回收站方法
     * 因此在当前控制器中可不用编写增删改查的代码,除非需要自己控制这部分逻辑
     * 需要将application/admin/library/traits/Backend.php中对应的方法复制到当前控制器,然后进行修改
     */

   /**
    * 样品间列表
    *
    * @Description
    * @author mjj
    * @since 2020/05/23 15:04:06 
    * @return void
    */
    public function sample_index()
    {
        //设置过滤方法
        $this->request->filter(['strip_tags']);
        if ($this->request->isAjax()) {
            //如果发送的来源是Selectpage，则转发到Selectpage
            if ($this->request->request('keyField')) {
                return $this->selectpage();
            }
            list($where, $sort, $order, $offset, $limit) = $this->buildparams();
            $total = $this->sample
                ->where($where)
                ->order($sort, $order)
                ->count();
            $list = $this->sample
                ->where($where)
                ->order($sort, $order)
                ->limit($offset, $limit)
                ->select();

            $list = collection($list)->toArray();
            foreach ($list as $key=>$value){
                $list[$key]['location'] = $this->samplelocation->getLocationName($value['location_id']);
                $list[$key]['is_lend'] = $value['is_lend'] == 1 ? '是' : '否';
                $list[$key]['product_name'] = $this->item->where('sku',$value['sku'])->value('name');
            }
            $result = array("total" => $total, "rows" => $list);

            return json($result);
        }
        return $this->view->fetch();
    }
    public function sample_import_xls(){
        set_time_limit(0);
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
        $listName = ['日期', '订单号', 'SKU', '眼球', 'SPH', 'CYL', 'AXI', 'ADD', '镜片', '处方类型'];
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
                for ($currentColumn = 1; $currentColumn <= $maxColumnNumber; $currentColumn++) {
                    $val = $currentSheet->getCellByColumnAndRow($currentColumn, $currentRow)->getValue();
                    $fields[] = $val;
                }
            }

            //模板文件不正确
            if ($allRow > 1000) {
                throw new Exception("表格行数过大");
            }

            //模板文件不正确
            if ($listName !== array_filter($fields)) {
                throw new Exception("模板文件不正确！！");
            }

            $data = [];
            for ($currentRow = 2; $currentRow <= $allRow; $currentRow++) {
                for ($currentColumn = 1; $currentColumn <= $maxColumnNumber; $currentColumn++) {
                    $val = $currentSheet->getCellByColumnAndRow($currentColumn, $currentRow)->getValue();
                    $data[$currentRow - 2][$currentColumn - 1] = is_null(trim($val)) ? 0 : trim($val);
                }
            }
        } catch (Exception $exception) {
            $this->error($exception->getMessage());
        }

        /*********************镜片出库计算逻辑***********************/
        /**
         * 镜片扣减逻辑
         * SHP,CYL都是“-” 直接带入扣减库存
         * 若SPH为+，CYL为- 直接带入扣减库存，若SPH为“-”CYL为“+”则 sph=SPH+CYL,cyl变正负号，用新得到的sph,cyl扣减库存
         * 若带有ADD，sph=SPH+ADD,用新sph带入上面正负号判断里判断
         */
        //补充第二列订单号
        foreach ($data as $k => $v) {
            if (!$v[1]) {
                $data[$k][0] = $data[$k - 1][0];
                $data[$k][1] = $data[$k - 1][1];
                $data[$k][2] = $data[$k - 1][2];
                if (!$v[7]) {
                    $data[$k][7] = $data[$k - 1][7];
                }
                $data[$k][8] = $data[$k - 1][8];
                $data[$k][9] = $data[$k - 1][9];
            }
        }

        foreach ($data as $k => $v) {
            $lens_type = trim($v[8]);
            //如果ADD为真  sph = sph + ADD;
            $sph = $v[4];
            $cyl = $v[5];
            if ($sph) {
                $sph = $sph * 1;
                if ($v[7]) {
                    $sph = $sph + $v[7] * 1;
                }
                
                //如果cyl 为+;则sph = sph + cyl;cyl 正号变为负号
                if ($cyl && $cyl * 1 > 0) {
                    $sph = $sph + $cyl * 1;
                    $cyl = '-' . number_format($cyl * 1, 2);
                } else {
                    if ($cyl) {
                        $cyl = number_format($cyl * 1, 2);
                    } 
                }
                
                if ($sph > 0) {
                    $sph = '+' . number_format($sph, 2);
                } else {
                    $sph = number_format($sph, 2);
                }
            }

            if (!$cyl || $cyl * 1 == 0) {
                $cyl = '+0.00';
            }
            if (!$sph || $sph * 1 == 0) {
                $sph = '+0.00';
            }

            if ($lens_type) {

                //扣减库存
                $map['sph'] = trim($sph);
                $map['cyl'] = trim($cyl);
                $map['lens_type'] = ['like', '%' . $lens_type . '%'];
                $res = $this->model->where($map)->setDec('stock_num');

                //生成出库单
                if ($res) {
                    $params[$k]['num'] = 1;
                } else {
                    $params[$k]['num'] = 0;
                }
                //查询镜片单价
                $price = $this->model->where($map)->value('price');
                $params[$k]['lens_type'] = $lens_type;
                $params[$k]['sph'] = trim($sph);
                $params[$k]['cyl'] = trim($cyl);
                $params[$k]['createtime'] = date('Y-m-d H:i:s', time());
                $params[$k]['create_person'] = session('admin.nickname');
                $params[$k]['price'] = $price * $params[$k]['num'];
                $params[$k]['order_number'] = $v[1];
                $params[$k]['sku'] = trim($v[2]);
                $params[$k]['eye_type'] = $v[3];
                $params[$k]['order_sph'] = trim($v[4]);
                $params[$k]['order_cyl'] = trim($v[5]);
                $params[$k]['order_date'] = $v[0];
                $params[$k]['axi'] = $v[6];
                $params[$k]['add'] = trim($v[7]);
                $params[$k]['order_lens_type'] = $v[8];
                $params[$k]['prescription_type'] = $v[9];
            }
        }

        $this->outorder->saveAll($params);
        /*********************end***********************/
        $this->success('导入成功！！');
    }
    /**
     * 库位列表
     *
     * @Description
     * @author mjj
     * @since 2020/05/23 15:03:40 
     * @return void
     */
    public function sample_location_index()
    {
        //设置过滤方法
        $this->request->filter(['strip_tags']);
        if ($this->request->isAjax()) {
            //如果发送的来源是Selectpage，则转发到Selectpage
            if ($this->request->request('keyField')) {
                return $this->selectpage();
            }
            list($where, $sort, $order, $offset, $limit) = $this->buildparams();
            $total = $this->samplelocation
                ->where($where)
                ->order($sort, $order)
                ->count();

            $list = $this->samplelocation
                ->where($where)
                ->order($sort, $order)
                ->limit($offset, $limit)
                ->select();

            $list = collection($list)->toArray();
            $result = array("total" => $total, "rows" => $list);

            return json($result);
        }
        return $this->view->fetch();
    }
    /**
     * 库位增加
     *
     * @Description
     * @author mjj
     * @since 2020/05/23 14:59:04 
     * @return void
     */
    public function sample_location_add()
    {
        if ($this->request->isPost()) {
            $params = $this->request->post("row/a");
            if ($params) {
                $params = $this->preExcludeFields($params);

                if ($this->dataLimit && $this->dataLimitFieldAutoFill) {
                    $params[$this->dataLimitField] = $this->auth->id;
                }
                //判断库位号是否重复
                $location_repeat = Db::name('purchase_sample_location')
                    ->where('location',$params['location'])
                    ->find();
                if($location_repeat){
                    $this->error(__('库位号不能重复'));
                }
                $params['createtime'] = date('Y-m-d H:i:s',time());
                $params['create_user'] = session('admin.nickname');
                $result = false;
                Db::startTrans();
                try {
                    //是否采用模型验证
                    if ($this->modelValidate) {
                        $name = str_replace("\\model\\", "\\validate\\", get_class($this->samplelocation));
                        $validate = is_bool($this->modelValidate) ? ($this->modelSceneValidate ? $name . '.add' : $name) : $this->modelValidate;
                        $this->samplelocation->validateFailException(true)->validate($validate);
                    }
                    $result = $this->samplelocation->allowField(true)->save($params);
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
                    $this->error(__('No rows were inserted'));
                }
            }
            $this->error(__('Parameter %s can not be empty', ''));
        }
        return $this->view->fetch();
    }

    /**
     * 库位编辑
     *
     * @Description
     * @author mjj
     * @since 2020/05/23 15:05:29 
     * @param [type] $ids
     * @return void
     */
    public function sample_location_edit($ids = null)
    {
        $row = $this->samplelocation->get($ids);
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
                //判断库位号是否重复
                $location_repeat = Db::name('purchase_sample_location')
                    ->where('location',$params['location'])
                    ->find();
                if($location_repeat){
                    $this->error(__('库位号不能重复'));
                }
                $result = false;
                Db::startTrans();
                try {
                    //是否采用模型验证
                    if ($this->modelValidate) {
                        $name = str_replace("\\model\\", "\\validate\\", get_class($this->samplelocation));
                        $validate = is_bool($this->modelValidate) ? ($this->modelSceneValidate ? $name . '.edit' : $name) : $this->modelValidate;
                        $row->validateFailException(true)->validate($validate);
                    }
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
        $this->view->assign("row", $row);
        return $this->view->fetch();
    }
    /**
     * 库位删除
     *
     * @Description
     * @author mjj
     * @since 2020/05/23 15:05:41 
     * @param string $ids
     * @return void
     */
    public function sample_location_del($ids = "")
    {
        if ($ids) {
            $pk = $this->samplelocation->getPk();
            $adminIds = $this->getDataLimitAdminIds();
            if (is_array($adminIds)) {
                $this->samplelocation->where($this->dataLimitField, 'in', $adminIds);
            }
            $list = $this->samplelocation->where($pk, 'in', $ids)->select();

            $count = 0;
            Db::startTrans();
            try {
                if (!empty($this->samplelocation)) {
                    $fieldArr = $this->samplelocation->getTableFields();
                    if (in_array('is_del', $fieldArr)) {
                        $this->samplelocation->where($pk, 'in', $ids)->update(['is_del' => 2]);
                        $count = 1;
                    } else {
                        foreach ($list as $k => $v) {
                            $count += $v->delete();
                        }
                    }
                } else {
                    foreach ($list as $k => $v) {
                        $count += $v->delete();
                    }
                }

                Db::commit();
            } catch (PDOException $e) {
                Db::rollback();
                $this->error($e->getMessage());
            } catch (Exception $e) {
                Db::rollback();
                $this->error($e->getMessage());
            }
            if ($count) {
                $this->success();
            } else {
                $this->error(__('No rows were deleted'));
            }
        }
        $this->error(__('Parameter %s can not be empty', 'ids'));
    }
    /**
     * 入库列表
     *
     * @Description
     * @author mjj
     * @since 2020/05/23 15:08:11 
     * @return void
     */
    public function sample_workorder_index()
    {
        //设置过滤方法
        $this->request->filter(['strip_tags']);
        if ($this->request->isAjax()) {
            //如果发送的来源是Selectpage，则转发到Selectpage
            if ($this->request->request('keyField')) {
                return $this->selectpage();
            }
            $where_arr['type'] = 1;
            $where_arr['is_del'] = 1;
            list($where, $sort, $order, $offset, $limit) = $this->buildparams();
            $total = $this->sampleworkorder
                ->where($where)
                ->where($where_arr)
                ->order($sort, $order)
                ->count();

            $list = $this->sampleworkorder
                ->where($where)
                ->where($where_arr)
                ->order($sort, $order)
                ->limit($offset, $limit)
                ->select();

            $list = collection($list)->toArray();
            foreach ($list as $key=>$value){
                $list[$key]['status_id'] = $value['status'];
                if($value['status'] == 1){
                    $list[$key]['status'] = '新建';
                }elseif($value['status'] == 2){
                    $list[$key]['status'] = '待审核';
                }elseif($value['status'] == 3){
                    $list[$key]['status'] = '已审核';
                }elseif($value['status'] == 4){
                    $list[$key]['status'] = '已拒绝';
                }elseif($value['status'] == 5){
                    $list[$key]['status'] = '已取消';
                }
            }
            $result = array("total" => $total, "rows" => $list);

            return json($result);
        }
        return $this->view->fetch();
    }
    /**
     * 入库添加
     *
     * @Description
     * @author mjj
     * @since 2020/05/23 15:08:22 
     * @return void
     */
    public function sample_workorder_add()
    {
        $location_number = 'IN2'.date('YmdHis').rand(100,999).rand(100,999);
        if ($this->request->isPost()) {
            $params = $this->request->post("row/a");
            if ($params) {
                $params = $this->preExcludeFields($params);

                if ($this->dataLimit && $this->dataLimitFieldAutoFill) {
                    $params[$this->dataLimitField] = $this->auth->id;
                }
                if(!$params['goods']){
                    $this->error(__('提交信息不能为空', ''));
                }
                //判断数据中是否有空值
                $sku_arr = array_column($params['goods'],'sku');
                if(in_array('',$sku_arr)){
                    $this->error(__('商品信息不能为空', ''));
                }
                //生成入库主表数据
                $workorder['location_number'] = $location_number;
                $workorder['status'] = $params['status'];
                $workorder['create_user'] = session('admin.nickname');
                $workorder['createtime'] = date('Y-m-d H:i:s',time());
                $workorder['type'] = 1;
                $workorder['description'] = $params['description'];
                $this->sampleworkorder->save($workorder);
                $parent_id = $this->sampleworkorder->id;
                foreach ($params['goods'] as $key=>$value){
                    $workorder_item['parent_id'] = $parent_id;
                    $workorder_item['sku'] = $value['sku'];
                    $workorder_item['stock'] = $value['stock'];
                    $workorder_item['location_id'] = $value['location_id'];
                    Db::name('purchase_sample_workorder_item')->insert($workorder_item);
                }
                $this->success();
            }
            $this->error(__('Parameter %s can not be empty', ''));
        }

        //获取库位数据
        $location_data = $this->samplelocation->getPurchaseLocationData();
        $this->assign('location_data', $location_data);

        $this->assign('location_number', $location_number);

        return $this->view->fetch();
    }
    /**
     * 入库编辑
     *
     * @Description
     * @author mjj
     * @since 2020/05/23 15:08:32 
     * @param [type] $ids
     * @return void
     */
    public function sample_workorder_edit($ids = null)
    {
        $row = $this->sampleworkorder->get($ids);
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
                if(!$params['goods']){
                    $this->error(__('提交信息不能为空', ''));
                }
                //判断数据中是否有空值
                $sku_arr = array_column($params['goods'],'sku');
                if(in_array('',$sku_arr)){
                    $this->error(__('商品信息不能为空', ''));
                }
                //获取该入库单下的商品sku，并将不在该列表的数据进行删除
                $save_sku_arr = Db('purchase_sample_workorder_item')->where(['parent_id'=>$ids])->column('sku');
                $diff_sku_arr = array_diff($save_sku_arr,$sku_arr);
                Db('purchase_sample_workorder_item')->where('sku','in',$diff_sku_arr)->where('parent_id',$ids)->delete();
                //处理商品
                foreach($params['goods'] as $value){
                    //判断入库表中是否有该商品
                    $is_exist = Db('purchase_sample_workorder_item')->where(['sku'=>$value['sku'],'parent_id'=>$ids])->value('id');
                    $workorder_item = array();
                    if($is_exist){
                        //更新
                        $workorder_item['stock'] = $value['stock'];
                        $workorder_item['location_id'] = $value['location_id'];
                        Db::name('purchase_sample_workorder_item')->where(['sku'=>$value['sku'],'parent_id'=>$ids])->update($workorder_item);
                    }else{
                        //插入
                        $workorder_item['parent_id'] = $ids;
                        $workorder_item['sku'] = $value['sku'];
                        $workorder_item['stock'] = $value['stock'];
                        $workorder_item['location_id'] = $value['location_id'];
                        Db::name('purchase_sample_workorder_item')->insert($workorder_item);
                    }
                }
                //更新备注
                $workorder['description'] = $params['description'];
                $this->sampleworkorder->save($workorder,['id'=> input('ids')]);
                $this->success();
            }
            $this->error(__('Parameter %s can not be empty', ''));
        }
        $this->view->assign("row", $row);

        //获取库位数据
        $location_data = $this->samplelocation->getPurchaseLocationData();
        $this->assign('location_data', $location_data);

        //获取入库商品信息
        $product_list = Db::name('purchase_sample_workorder_item')->where('parent_id',$ids)->order('id asc')->select();
        $product_arr = array();
        foreach ($product_list as $key=>$value){
            $product_arr[] =  $value['sku'].'_'.$value['stock'].'_'.$value['location_id'];
            $sku_arr[] = $value['sku'];
        }
        $product_str = implode(',',$product_arr);
        $sku_str = implode(',',$sku_arr);
        $this->assign('product_str', $product_str);
        $this->assign('sku_str', $sku_str);
        $this->assign('product_list', $product_list);


        return $this->view->fetch();
    }
    /**
     * 入库详情/审核
     *
     * @Description
     * @author mjj
     * @since 2020/05/23 15:38:27 
     * @param [type] $ids
     * @return void
     */
    public function sample_workorder_detail($ids = null)
    {
        $row = $this->sampleworkorder->get($ids);
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
                $workorder['status'] = $params['workorder_status'];
                if($params['workorder_status'] == 3){
                    //审核通过后添加商品到样品间列表
                    $product_arr = Db::name('purchase_sample_workorder_item')->where('parent_id',$ids)->order('id asc')->select();
                    foreach($product_arr as $item){
                        $is_exist = $this->sample->where('sku',$item['sku'])->value('id');
                        if($is_exist){
                            $this->sample->where('sku',$item['sku'])->inc('stock',$item['stock'])->update();
                        }else{
                            $sample['sku'] = $item['sku'];
                            $sample['location_id'] = $item['location_id'];
                            $sample['stock'] = $item['stock'];
                            $sample['is_lend'] = 0;
                            $sample['lend_num'] = 0;
                            $this->sample->insert($sample);
                        }
                    }
                }
                $this->sampleworkorder->save($workorder,['id'=> input('ids')]);
                $this->success();
            }
            $this->error(__('Parameter %s can not be empty', ''));
        }
        $this->view->assign("row", $row);

        //获取入库商品信息
        $product_list = Db::name('purchase_sample_workorder_item')->where('parent_id',$ids)->order('id asc')->select();
        $product_arr = array();
        foreach ($product_list as $key=>$value){
            $product_arr[] =  $value['sku'].'_'.$value['stock'].'_'.$value['location_id'];
            $product_list[$key]['location'] = $this->samplelocation->where('id',$value['location_id'])->value('location');
        }
        $product_str = implode(',',$product_arr);
        $this->assign('product_str', $product_str);
        $this->assign('product_list', $product_list);

        return $this->view->fetch();
    }
    /**
     * 入库删除
     *
     * @Description
     * @author mjj
     * @since 2020/05/23 16:48:44 
     * @param string $ids
     * @return void
     */
    public function sample_workorder_del($ids = "")
    {
        if ($ids) {
            $pk = $this->sampleworkorder->getPk();
            $adminIds = $this->getDataLimitAdminIds();
            if (is_array($adminIds)) {
                $this->sampleworkorder->where($this->dataLimitField, 'in', $adminIds);
            }
            $list = $this->sampleworkorder->where($pk, 'in', $ids)->select();

            $count = 0;
            Db::startTrans();
            try {
                if (!empty($this->sampleworkorder)) {
                    $fieldArr = $this->sampleworkorder->getTableFields();
                    if (in_array('is_del', $fieldArr)) {
                        $this->sampleworkorder->where($pk, 'in', $ids)->update(['is_del' => 2]);
                        $count = 1;
                    } else {
                        foreach ($list as $k => $v) {
                            $count += $v->delete();
                        }
                    }
                } else {
                    foreach ($list as $k => $v) {
                        $count += $v->delete();
                    }
                }

                Db::commit();
            } catch (PDOException $e) {
                Db::rollback();
                $this->error($e->getMessage());
            } catch (Exception $e) {
                Db::rollback();
                $this->error($e->getMessage());
            }
            if ($count) {
                $this->success();
            } else {
                $this->error(__('No rows were deleted'));
            }
        }
        $this->error(__('Parameter %s can not be empty', 'ids'));
    }
    /**
     * 入库取消
     *
     * @Description
     * @author mjj
     * @since 2020/05/23 16:13:54 
     * @param [type] $ids
     * @return void
     */
    public function sample_workorder_cancel($ids = null){
        $workorder['status'] = 5;
        $this->sampleworkorder->save($workorder,['id'=> input('ids')]);
        $this->success();
    }
    /**
     * 入库批量审核
     *
     * @Description
     * @author mjj
     * @since 2020/05/23 17:26:57 
     * @return void
     */
    public function sample_workorder_setstatus($ids = null){
        $ids = $this->request->post("ids/a");
        $status = input('status');
        if (!$ids) {
            $this->error('缺少参数！！');
        }
        $is_update = 0;
        $where['id'] = ['in', $ids];
        $row = $this->sampleworkorder->where($where)->select();
        foreach ($row as $v) {
            if ($status == 3 || $status == 4 || $status == 5) {
                if ($v['status'] >= 3) {
                    $this->error('只有新建状态和待审核状态才能操作！！');
                    $is_update = 0;
                    break;
                }else{
                    $is_update = 1;
                }
            }
        }
        if($is_update == 1){
            $this->sampleworkorder->where($where)->update(['status'=>$status]);
            if($status == 3){
                //审核通过后将商品信息添加到样品间列表
                foreach($ids as $id){
                    $product_arr = Db::name('purchase_sample_workorder_item')->where('parent_id',$id)->order('id asc')->select();
                    foreach($product_arr as $item){
                        $is_exist = $this->sample->where('sku',$item['sku'])->value('id');
                        if($is_exist){
                            $this->sample->where('sku',$item['sku'])->inc('stock',$item['stock'])->update();
                        }else{
                            $sample['sku'] = $item['sku'];
                            $sample['location_id'] = $item['location_id'];
                            $sample['stock'] = $item['stock'];
                            $sample['is_lend'] = 0;
                            $sample['lend_num'] = 0;
                            $this->sample->insert($sample);
                        }
                    }
                }
            }
            $this->success();
        }
    }
    /**
     * 出库列表
     *
     * @Description
     * @author mjj
     * @since 2020/05/23 15:08:11 
     * @return void
     */
    public function sample_workorder_out_index()
    {
        //设置过滤方法
        $this->request->filter(['strip_tags']);
        if ($this->request->isAjax()) {
            //如果发送的来源是Selectpage，则转发到Selectpage
            if ($this->request->request('keyField')) {
                return $this->selectpage();
            }
            $where_arr['type'] = 2;
            $where_arr['is_del'] = 1;
            list($where, $sort, $order, $offset, $limit) = $this->buildparams();
            $total = $this->sampleworkorder
                ->where($where)
                ->where($where_arr)
                ->order($sort, $order)
                ->count();

            $list = $this->sampleworkorder
                ->where($where)
                ->where($where_arr)
                ->order($sort, $order)
                ->limit($offset, $limit)
                ->select();

            $list = collection($list)->toArray();
            foreach ($list as $key=>$value){
                $list[$key]['status_id'] = $value['status'];
                if($value['status'] == 1){
                    $list[$key]['status'] = '新建';
                }elseif($value['status'] == 2){
                    $list[$key]['status'] = '待审核';
                }elseif($value['status'] == 3){
                    $list[$key]['status'] = '已审核';
                }elseif($value['status'] == 4){
                    $list[$key]['status'] = '已拒绝';
                }elseif($value['status'] == 5){
                    $list[$key]['status'] = '已取消';
                }
            }
            $result = array("total" => $total, "rows" => $list);

            return json($result);
        }
        return $this->view->fetch();
    }
    /**
     * 出库添加
     *
     * @Description
     * @author mjj
     * @since 2020/05/23 15:08:22 
     * @return void
     */
    public function sample_workorder_out_add()
    {
        $location_number = 'OUT2'.date('YmdHis').rand(100,999).rand(100,999);
        if ($this->request->isPost()) {
            $params = $this->request->post("row/a");
            if ($params) {
                $params = $this->preExcludeFields($params);

                if ($this->dataLimit && $this->dataLimitFieldAutoFill) {
                    $params[$this->dataLimitField] = $this->auth->id;
                }
                if(!$params['goods']){
                    $this->error(__('提交信息不能为空', ''));
                }
                $sku_arr = array_column($params['goods'],'sku');
                //判断是否有重复项
                if (count($sku_arr) != count(array_unique($sku_arr))) { 
                    $this->error(__('sku不能重复', ''));
                }
                //判断数据中是否有空值
                if(in_array('',$sku_arr)){
                    $this->error(__('商品信息不能为空', ''));
                }
                //生成出库主表数据
                $workorder['location_number'] = $location_number;
                $workorder['status'] = $params['status'];
                $workorder['create_user'] = session('admin.nickname');
                $workorder['createtime'] = date('Y-m-d H:i:s',time());
                $workorder['type'] = 2;
                $workorder['description'] = $params['description'];
                $this->sampleworkorder->save($workorder);
                $parent_id = $this->sampleworkorder->id;
                foreach ($params['goods'] as $key=>$value){
                    $workorder_item['parent_id'] = $parent_id;
                    $workorder_item['sku'] = $value['sku'];
                    $workorder_item['stock'] = $value['stock'];
                    $workorder_item['location_id'] = $this->sample->where('sku',$value['sku'])->value('location_id');
                    Db::name('purchase_sample_workorder_item')->insert($workorder_item);
                }
                $this->success();
            }
            $this->error(__('Parameter %s can not be empty', ''));
        }

        //获取样品间商品列表
        $sku_data = $this->sample->getlenddata();
        $this->assign('sku_data', $sku_data);

        $this->assign('location_number', $location_number);

        return $this->view->fetch();
    }
     /**
     * 出库编辑
     *
     * @Description
     * @author mjj
     * @since 2020/05/23 15:08:32 
     * @param [type] $ids
     * @return void
     */
    public function sample_workorder_out_edit($ids = null)
    {
        $row = $this->sampleworkorder->get($ids);
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
                if(!$params['goods']){
                    $this->error(__('提交信息不能为空', ''));
                }
                $sku_arr = array_column($params['goods'],'sku');
                //判断是否有重复项
                if (count($sku_arr) != count(array_unique($sku_arr))) { 
                    $this->error(__('sku不能重复', ''));
                }
                //判断数据中是否有空值
                if(in_array('',$sku_arr)){
                    $this->error(__('商品信息不能为空', ''));
                }
                //获取该入库单下的商品sku，并将不在该列表的数据进行删除
                $save_sku_arr = Db('purchase_sample_workorder_item')->where(['parent_id'=>$ids])->column('sku');
                $diff_sku_arr = array_diff($save_sku_arr,$sku_arr);
                Db('purchase_sample_workorder_item')->where('sku','in',$diff_sku_arr)->where('parent_id',$ids)->delete();
                //处理商品
                foreach ($params['goods'] as $key=>$value){
                    $is_exist = Db::name('purchase_sample_workorder_item')->where(['sku'=>$value['sku'],'parent_id'=>$ids])->value('id');
                    if($is_exist){
                        //更新
                        Db::name('purchase_sample_workorder_item')->where(['sku'=>$value['sku'],'parent_id'=>$ids])->update(['stock'=>$value['stock']]);
                    }else{
                        //插入
                        $workorder_item = array();
                        $workorder_item['parent_id'] = $ids;
                        $workorder_item['sku'] = $value['sku'];
                        $workorder_item['stock'] = $value['stock'];
                        $workorder_item['location_id'] = $this->sample->where('sku',$value['sku'])->value('location_id');
                        Db::name('purchase_sample_workorder_item')->insert($workorder_item);
                    }
                }
                $workorder['description'] = $params['description'];
                $this->sampleworkorder->save($workorder,['id'=> input('ids')]);

                $this->success();
            }
            $this->error(__('Parameter %s can not be empty', ''));
        }
        $this->view->assign("row", $row);

        //获取样品间商品列表
        $sku_data = $this->sample->getlenddata();
        $this->assign('sku_data', $sku_data);

        //获取出库商品信息
        $product_list = Db::name('purchase_sample_workorder_item')->where('parent_id',$ids)->order('id asc')->select();
        $product_arr = array();
        foreach ($product_list as $key=>$value){
            $product_arr[] =  $value['sku'].'_'.$value['stock'].'_'.$value['location_id'];
            $sku_arr[] = $value['sku'];
            $product_list[$key]['location'] = $this->samplelocation->where('id',$value['location_id'])->value('location');
        }
        $product_str = implode(',',$product_arr);
        $sku_str = implode(',',$sku_arr);
        $this->assign('product_str', $product_str);
        $this->assign('sku_str', $sku_str);
        $this->assign('product_list', $product_list);


        return $this->view->fetch();
    }
    /**
     * 出库详情/审核
     *
     * @Description
     * @author mjj
     * @since 2020/05/23 15:38:27 
     * @param [type] $ids
     * @return void
     */
    public function sample_workorder_out_detail($ids = null)
    {
        $row = $this->sampleworkorder->get($ids);
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
                $workorder['status'] = $params['workorder_status'];
                if($params['workorder_status'] == 3){
                    $is_check = 0;
                    $check_arr = array();
                    //审核通过后判断样品间数量是否充足
                    $product_arr = Db::name('purchase_sample_workorder_item')->where('parent_id',$ids)->order('id asc')->select();
                    foreach($product_arr as $item){
                        $sample = $this->sample->where('sku',$item['sku'])->find();
                        $rest_stock = $sample['stock'] - $sample['lend_num'];
                        if($rest_stock >= $item['stock']){
                            $check_arr[] = array(
                                'sku' => $item['sku'],
                                'stock' => $item['stock']
                            );
                        }else{
                            $is_check++;
                        }
                    }
                    if($is_check == 0){
                        //审核通过
                        if(count($check_arr) > 0){
                            foreach($check_arr as $value){
                                $this->sample->where('sku',$value['sku'])->dec('stock',$value['stock'])->update();
                            }
                            $this->sampleworkorder->save($workorder,['id'=> input('ids')]);
                        }
                    }else{
                        $this->error(__('样品间商品不足', ''));
                    }
                }else{
                    $this->sampleworkorder->save($workorder,['id'=> input('ids')]);
                }
                $this->success();
            }
            $this->error(__('Parameter %s can not be empty', ''));
        }
        $this->view->assign("row", $row);

        //获取出库商品信息
        $product_list = Db::name('purchase_sample_workorder_item')->where('parent_id',$ids)->order('id asc')->select();
        $product_arr = array();
        foreach ($product_list as $key=>$value){
            $product_arr[] =  $value['sku'].'_'.$value['stock'].'_'.$value['location_id'];
            $product_list[$key]['location'] = $this->samplelocation->where('id',$value['location_id'])->value('location');
        }
        $product_str = implode(',',$product_arr);
        $this->assign('product_str', $product_str);
        $this->assign('product_list', $product_list);

        return $this->view->fetch();
    }
    /**
     * 出库批量审核
     *
     * @Description
     * @author mjj
     * @since 2020/05/23 17:26:57 
     * @return void
     */
    public function sample_workorder_out_setstatus($ids = null){
        $ids = $this->request->post("ids/a");
        $status = input('status');
        if (!$ids) {
            $this->error('缺少参数！！');
        }
        $is_update = 0;
        $where['id'] = ['in', $ids];
        $row = $this->sampleworkorder->where($where)->select();
        foreach ($row as $v) {
            if ($status == 3 || $status == 4 || $status == 5) {
                if ($v['status'] >= 3) {
                    $this->error('只有新建状态和待审核状态才能操作！！');
                    $is_update = 0;
                    break;
                }else{
                    $is_update = 1;
                }
            }
        }
        if($is_update == 1){
            if($status == 3){
                $is_check = 0;
                $check_arr = array();
                //审核通过后将商品信息添加到样品间列表
                foreach($ids as $id){
                    $product_arr = Db::name('purchase_sample_workorder_item')->where('parent_id',$id)->order('id asc')->select();
                    foreach($product_arr as $item){
                        $sample = $this->sample->where('sku',$item['sku'])->find();
                        $rest_stock = $sample['stock'] - $sample['lend_num'];
                        if($rest_stock >= $item['stock']){
                            $check_arr[] = array(
                                'sku'=>$item['sku'],
                                'stock'=>$item['stock'],
                            );
                        }else{
                            $is_check++;
                            break;
                        }
                    }
                }
                if($is_check == 0){
                    //审核通过
                    if(count($check_arr) > 0){
                        foreach($check_arr as $value){
                            $this->sample->where('sku',$value['sku'])->dec('stock',$value['stock'])->update();
                        }
                        $this->sampleworkorder->where($where)->update(['status'=>$status]);
                    }
                }else{
                    $this->error(__('样品间商品不足', ''));
                }
            }else{
                $this->sampleworkorder->where($where)->update(['status'=>$status]);
            }
            $this->success();
        }
    }
    /**
     * 出库删除
     *
     * @Description
     * @author mjj
     * @since 2020/05/23 17:26:57 
     * @return void
     */
    public function sample_workorder_out_del($ids = null){
        if (!$ids) {
            $this->error('缺少参数！！');
        }
        $this->sampleworkorder->where(['id'=>$ids])->update(['is_del'=>2]);
        $this->success();
    }
    /**
     * 借出记录列表
     *
     * @Description
     * @author mjj
     * @since 2020/05/25 09:49:12 
     * @return void
     */
    public function sample_lendlog_index()
    {
        //设置过滤方法
        $this->request->filter(['strip_tags']);
        if ($this->request->isAjax()) {
            //如果发送的来源是Selectpage，则转发到Selectpage
            if ($this->request->request('keyField')) {
                return $this->selectpage();
            }
            list($where, $sort, $order, $offset, $limit) = $this->buildparams();
            $total = $this->samplelendlog
                ->where($where)
                ->order($sort, $order)
                ->count();

            $list = $this->samplelendlog
                ->where($where)
                ->order($sort, $order)
                ->limit($offset, $limit)
                ->select();

            $list = collection($list)->toArray();
            foreach ($list as $key=>$value){
                $list[$key]['status_id'] = $value['status'];
                if($value['status'] == 1){
                    $list[$key]['status'] = '待审核';
                }elseif($value['status'] == 2){
                    $list[$key]['status'] = '已借出';
                }elseif($value['status'] == 3){
                    $list[$key]['status'] = '已拒绝';
                }elseif($value['status'] == 4){
                    $list[$key]['status'] = '已归还';
                }elseif($value['status'] == 5){
                    $list[$key]['status'] = '已取消';
                }
            }
            $result = array("total" => $total, "rows" => $list);

            return json($result);
        }
        return $this->view->fetch();
    }
    /**
     * 借出记录申请
     *
     * @Description
     * @author mjj
     * @since 2020/05/25 17:02:44 
     * @return void
     */
    public function sample_lendlog_add()
    {
        if ($this->request->isPost()) {
            $params = $this->request->post("row/a");
            if ($params) {
                $params = $this->preExcludeFields($params);

                if ($this->dataLimit && $this->dataLimitFieldAutoFill) {
                    $params[$this->dataLimitField] = $this->auth->id;
                }
                if(!$params['goods']){
                    $this->error(__('提交信息不能为空', ''));
                }
                $sku_arr = array_column($params['goods'],'sku');
                //判断是否有重复项
                if (count($sku_arr) != count(array_unique($sku_arr))) { 
                    $this->error(__('sku不能重复', ''));
                }
                //判断数据中是否有空值
                if(in_array('',$sku_arr)){
                    $this->error(__('商品信息不能为空', ''));
                }
                //生成入库主表数据
                $lendlog['status'] = 1;
                $lendlog['create_user'] = session('admin.nickname');
                $lendlog['createtime'] = date('Y-m-d H:i:s',time());
                $this->samplelendlog->save($lendlog);
                $log_id = $this->samplelendlog->id;
                foreach ($params['goods'] as $value){
                    $lendlog_item['log_id'] = $log_id;
                    $lendlog_item['sku'] = $value['sku'];
                    $lendlog_item['lend_num'] = $value['lend_num'];
                    Db::name('purchase_sample_lendlog_item')->insert($lendlog_item);
                }
                $this->success();
               
            }
            $this->error(__('Parameter %s can not be empty', ''));
        }

        //获取样品间商品列表
        $sku_data = $this->sample->getlenddata();
        $this->assign('sku_data', $sku_data);

        return $this->view->fetch();
    }
    /**
     * 借出记录详情
     *
     * @Description
     * @author mjj
     * @since 2020/05/25 17:02:58 
     * @return void
     */
    public function sample_lendlog_detail($ids = null){
        $row = $this->samplelendlog->get($ids);
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

        //获取样品借出商品信息
        $product_list = Db::name('purchase_sample_lendlog_item')->field('sku,lend_num')->where('log_id',$ids)->order('id asc')->select();
        foreach ($product_list as $key=>$value){
            $location_id = $this->sample->where('sku',$value['sku'])->value('location_id');
            $product_list[$key]['location'] = $this->samplelocation->where('id',$location_id)->value('location');
        }
        $this->assign('product_list', $product_list);

        return $this->view->fetch();
    }
    /**
     * 借出记录编辑
     *
     * @Description
     * @author mjj
     * @since 2020/05/25 17:23:40 
     * @param [type] $ids
     * @return void
     */
    public function sample_lendlog_edit($ids = null){
        $row = $this->samplelendlog->get($ids);
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
                if(!$params['goods']){
                    $this->error(__('提交信息不能为空', ''));
                }
                $sku_arr = array_column($params['goods'],'sku');
                //判断是否有重复项
                if (count($sku_arr) != count(array_unique($sku_arr))) { 
                    $this->error(__('sku不能重复', ''));
                }
                //判断数据中是否有空值
                if(in_array('',$sku_arr)){
                    $this->error(__('商品信息不能为空', ''));
                }
                //获取该入库单下的商品sku，并将不在该列表的数据进行删除
                $save_sku_arr = Db('purchase_sample_lendlog_item')->where(['log_id'=>$ids])->column('sku');
                $diff_sku_arr = array_diff($save_sku_arr,$sku_arr);
                Db('purchase_sample_lendlog_item')->where('sku','in',$diff_sku_arr)->where('log_id',$ids)->delete();
                //处理商品
                foreach ($params['goods'] as $key=>$value){
                    $is_exist = Db::name('purchase_sample_lendlog_item')->where(['log_id'=>$ids,'sku'=>$value['sku']])->value('id');
                    if($is_exist){
                        //更新
                        Db::name('purchase_sample_lendlog_item')->where(['log_id'=>$ids,'sku'=>$value['sku']])->update(['lend_num'=>$value['lend_num']]);
                    }else{
                        //插入
                        $lendlog_item = array();
                        $lendlog_item['log_id'] = $ids;
                        $lendlog_item['sku'] = $value['sku'];
                        $lendlog_item['lend_num'] = $value['lend_num'];
                        Db::name('purchase_sample_lendlog_item')->insert($lendlog_item);
                    }
                }
                $this->success();
            }
            $this->error(__('Parameter %s can not be empty', ''));
        }
        $this->view->assign("row", $row);

        //获取样品间商品列表
        $sku_data = $this->sample->getlenddata();
        $this->assign('sku_data', $sku_data);

        //获取样品借出商品信息
        $product_list = Db::name('purchase_sample_lendlog_item')->field('sku,lend_num')->where('log_id',$ids)->order('id asc')->select();
        $sku_arr = array();
        $product_arr = array();
        foreach ($product_list as $key=>$value){
            $sku_arr[] = $value['sku'];
            $product_arr[] = $value['sku'].'_'.$value['lend_num'];
            $location_id = $this->sample->where('sku',$value['sku'])->value('location_id');
            $product_list[$key]['location'] = $this->samplelocation->where('id',$location_id)->value('location');
        }
        $sku_str = implode(',',$sku_arr);
        $product_str = implode(',',$product_arr);
        $this->assign('sku_str',$sku_str);
        $this->assign('product_str',$product_str);
        $this->assign('product_list', $product_list);

        return $this->view->fetch();
    }
    /**
     * 借出记录审核
     *
     * @Description
     * @author mjj
     * @since 2020/05/26 10:25:09 
     * @return void
     */
    public function sample_lendlog_check($ids = null){
        $params = input();
        if (!$params['ids']) {
            $this->error('缺少参数！！');
        }
        $where['id'] = $params['ids'];
       
        if($params['status'] == 2){
            $is_check = 0;
            $lend_arr = array();
            //判断审核单中的商品是否可以借出
            $lendlog_items = Db::name('purchase_sample_lendlog_item')->where('log_id',$ids)->select();
            foreach($lendlog_items as $item){
                $sample = $this->sample->where('sku',$item['sku'])->find();
                $rest_stock = $sample['stock'] - $sample['lend_num'];
                if($rest_stock>=$item['lend_num']){
                    $lend_arr[] = array(
                        'sku' => $item['sku'],
                        'lend_num' => $item['lend_num'],
                    );
                }else{
                    //借出单中存在商品数量不足，无法借出
                    $is_check++; 
                }
            }
            if($is_check == 0){
                //审核通过
                if(count($lend_arr) > 0){
                    foreach($lend_arr as $value){
                        //借出商品并更新状态
                        $this->sample->where('sku',$value['sku'])->inc('lend_num',$value['lend_num'])->update(['is_lend'=>1]);
                    }
                    $this->samplelendlog->where($where)->update(['status'=>$params['status']]);
                }
            }else{
                $this->error('借出单中存在商品数量不足，无法借出');
            }
        }elseif($params['status'] == 4){
            //归还
            $lendlog_items = Db::name('purchase_sample_lendlog_item')->where('log_id',$ids)->select();
            foreach($lendlog_items as $item){
                $sample = $this->sample->where('sku',$item['sku'])->dec('lend_num',$item['lend_num'])->update();
                //判断是否没有借出数量，如果没有修改样品间列表的状态
                $already_lend_num = $this->sample->where('sku',$item['sku'])->value('lend_num');
                if($already_lend_num == 0){
                    $this->sample->where('sku',$item['sku'])->update(['is_lend'=>0]);
                }
            }
            $this->samplelendlog->where($where)->update(['status'=>$params['status']]);
        }else{
            $this->samplelendlog->where($where)->update(['status'=>$params['status']]);
        }
        $this->success();
    }
    /**
     * 借出记录批量审核
     *
     * @Description
     * @author mjj
     * @since 2020/05/26 11:52:38 
     * @return void
     */
    public function sample_lendlog_setstatus($ids = null){
        $ids = $this->request->post("ids/a");
        $status = input('status');
        if (!$ids) {
            $this->error('缺少参数！！');
        }
        $is_update = 0;
        $where['id'] = ['in', $ids];
        $row = $this->samplelendlog->where($where)->select();
        foreach ($row as $v) {
            if ($status == 2 || $status == 3) {
                if ($v['status'] > 1) {
                    $this->error('只有待审核状态才能操作！！');
                    $is_update = 0;
                    break;
                }else{
                    $is_update = 1;
                }
            }
        }
        if($is_update == 1){
            if($status == 2){
                //批量审核通过
                $is_check = 0;
                $lend_arr = array();
                foreach($ids as $id){
                    $lendlog_items = Db::name('purchase_sample_lendlog_item')->where('log_id',$id)->select();
                    foreach($lendlog_items as $item){
                        $sample = $this->sample->where('sku',$item['sku'])->find();
                        $rest_stock = $sample['stock'] - $sample['lend_num'];
                        if($rest_stock>=$item['lend_num']){
                            //借出商品并更新状态
                            $lend_arr[] = array(
                                'sku'=>$item['sku'],
                                'lend_num'=>$item['lend_num']
                            );
                        }else{
                            //借出单中存在商品数量不足，无法借出
                            $is_check++;
                            break;
                        }
                    }
                }
                if($is_check == 0){
                    //审核通过
                    foreach($lend_arr as $value){
                        $this->sample->where('sku',$value['sku'])->inc('lend_num',$value['lend_num'])->update(); 
                    }
                    $this->samplelendlog->where($where)->update(['status'=>$status]);
                }else{
                    $this->error('借出单中存在商品数量不足，无法借出');
                }
            }else{
                //批量审核拒绝
                $this->samplelendlog->where($where)->update(['status'=>$status]);
            }
            $this->success();
        }
    }
}
