<?php

namespace app\admin\controller\order;

use app\common\controller\Backend;
use think\Hook;
use fast\Trackingmore;
use Util\NihaoPrescriptionDetailHelper;
use Util\ZeeloolPrescriptionDetailHelper;
use Util\VooguemePrescriptionDetailHelper;
use Util\WeseeopticalPrescriptionDetailHelper;
use PhpOffice\PhpSpreadsheet\Reader\Xlsx;
use PhpOffice\PhpSpreadsheet\Reader\Xls;
use PhpOffice\PhpSpreadsheet\Reader\Csv;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use think\Exception;
use think\exception\PDOException;

/**
 * 订单列表
 */
class Index extends Backend
{

    protected $model = null;

    public function _initialize()
    {
        parent::_initialize();
        $this->nihao = new \app\admin\model\order\order\Nihao;
        $this->zeelool = new \app\admin\model\order\order\Zeelool;
        $this->voogueme = new \app\admin\model\order\order\Voogueme;        
        $this->weseeoptical = new \app\admin\model\order\order\Weseeoptical;        
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
        $label = $this->request->get('label', 1);
        //设置过滤方法
        $this->request->filter(['strip_tags']);
        if ($this->request->isAjax()) {
            //如果发送的来源是Selectpage，则转发到Selectpage
            if ($this->request->request('keyField')) {
                return $this->selectpage();
            }
            //根据传的标签切换对应站点数据库
            $label = $this->request->get('label', 1);
            if ($label == 1) {
                $model = $this->zeelool;
            } elseif ($label == 2) {
                $model = $this->voogueme;
            } elseif ($label == 3) {
                $model = $this->nihao;
            } elseif ($label == 4) {
                $model = $this->weseeoptical;
            }
            list($where, $sort, $order, $offset, $limit) = $this->buildparams();
            
            $total = $model
                ->where($where)
                ->order($sort, $order)
                ->count();

            $list = $model
                ->where($where)
                ->order($sort, $order)
                ->limit($offset, $limit)
                ->select();

            $list = collection($list)->toArray();

            $result = array("total" => $total, "rows" => $list);

            return json($result);
        }
        $this->assign('label', $label);
        $this->assignconfig('label', $label);
        return $this->view->fetch();
    }

    /**
     * 详情
     */
    public function detail($ids = null)
    {
        $ids = $ids ?? $this->request->get('id');
        //根据传的标签切换对应站点数据库
        $label = $this->request->get('label', 1);
        if ($label == 1) {
            $model = $this->zeelool;
        } elseif ($label == 2) {
            $model = $this->voogueme;
        } elseif ($label == 3) {
            $model = $this->nihao;
        } elseif ($label == 4) {
            $model = $this->weseeoptical;
        }

        //查询订单详情
        $row = $model->where('entity_id', '=', $ids)->find();
        if (!$row) {
            $this->error(__('No Results were found'));
        }
        $adminIds = $this->getDataLimitAdminIds();
        if (is_array($adminIds)) {
            if (!in_array($row[$this->dataLimitField], $adminIds)) {
                $this->error(__('You have no permission'));
            }
        }

        //获取订单收货信息
        $address = $this->zeelool->getOrderDetail($label, $ids);

        //获取订单处方信息
        if ($label == 1) {
            $goods = ZeeloolPrescriptionDetailHelper::get_list_by_entity_ids($ids);
        } elseif ($label == 2) {
            $goods = VooguemePrescriptionDetailHelper::get_list_by_entity_ids($ids);
        } elseif ($label == 3) {
            $goods = NihaoPrescriptionDetailHelper::get_list_by_entity_ids($ids);
        } elseif ($label == 4) {
            $goods = WeseeopticalPrescriptionDetailHelper::get_list_by_entity_ids($ids);
        }
    
        //获取支付信息
        $pay = $this->zeelool->getPayDetail($label, $ids);

        $this->view->assign("label", $label);
        $this->view->assign("row", $row);
        $this->view->assign("address", $address);
        $this->view->assign("goods", $goods);
        $this->view->assign("pay", $pay);
        return $this->view->fetch();
    }

    /**
     * 订单执行信息
     */
    public function checkDetail($ids = null)
    {
        $ids = $ids ?? $this->request->get('id');
        //根据传的标签切换对应站点数据库
        $label = $this->request->get('label', 1);
        if ($label == 1) {
            $model = $this->zeelool;
        } elseif ($label == 2) {
            $model = $this->voogueme;
        } elseif ($label == 3) {
            $model = $this->nihao;
        }

        //查询订单详情
        $row = $model->where('entity_id', '=', $ids)->find();
        if (!$row) {
            $this->error(__('No Results were found'));
        }
        //查询订单快递单号
        $express = $this->zeelool->getExpressData($label, $ids);
    
        if ($express) {
            //缓存一个小时
            $express_data = session('order_checkDetail_' . $express['track_number'] . '_' . date('YmdH'));
            if (!$express_data) {
                try {
                    //查询物流信息
                    $title = str_replace(' ', '-', $express['title']);
                    $track = new Trackingmore();
                    $track = $track->getRealtimeTrackingResults($title, $express['track_number']);
                    $express_data = $track['data']['items'][0];
                    session('order_checkDetail_' . $express['track_number'] . '_' . date('YmdH'), $express_data);
                } catch (\Exception $e) {
                    $this->error($e->getMessage());
                }
            }

            $this->view->assign("express_data", $express_data);
        }

        $this->view->assign("row", $row);
        $this->view->assign("label",$label);
        return $this->view->fetch();
    }
    /**
     * 订单成本核算 create@lsw
     */
    public function account_order()
    {
        $label = $this->request->get('label', 1);
        

        //设置过滤方法
        $this->request->filter(['strip_tags']);
        if ($this->request->isAjax()) {
            //如果发送的来源是Selectpage，则转发到Selectpage
            if ($this->request->request('keyField')) {

                return $this->selectpage();
            }
            $rep    = $this->request->get('filter');
            $addWhere = '1=1';
            if($rep != '{}'){
                // $whereArr = json_decode($rep,true);
                // if(!array_key_exists('created_at',$whereArr)){
                //     $addWhere  .= " AND DATE_SUB(CURDATE(), INTERVAL 7 DAY) <= date(created_at)";
                // }
            }else{
                    $addWhere  .= " AND DATE_SUB(CURDATE(), INTERVAL 7 DAY) <= date(created_at)";
            }
            // echo $addWhere;
            // exit;
            //根据传的标签切换对应站点数据库
            $label = $this->request->get('label', 1);
            if ($label == 1) {
                $model = $this->zeelool;
            } elseif ($label == 2) {
                $model = $this->voogueme;
            } elseif ($label == 3) {
                $model = $this->nihao;
            }

            list($where, $sort, $order, $offset, $limit) = $this->buildparams();
            $total = $model
                ->where($where)
                ->order($sort, $order)
                ->count();

            $list = $model
                ->where($where)
                ->order($sort, $order)
                ->limit($offset, $limit)
                ->select();
            $totalId = $model
                   ->where($where)
                   ->where($addWhere)
                   ->column('entity_id');
            $thisPageId = $model
                   ->where($where)
                   ->order($sort,$order)
                   ->limit($offset,$limit)
                   ->column('entity_id');
            $costInfo = $model->getOrderCostInfo($totalId,$thisPageId);       
            $list = collection($list)->toArray();
            foreach($list as $k =>$v){
                //原先
                // if(isset($costInfo['thisPagePayPrice'])){
                //     if(array_key_exists($v['entity_id'],$costInfo['thisPagePayPrice'])){
                //         $list[$k]['total_money'] = $costInfo['thisPagePayPrice'][$v['entity_id']];
                //    }
                // }
                //订单支付金额
                if(in_array($v['status'],['processing','complete','creditcard_proccessing','free_processing'])){
                    //$costInfo['totalPayInfo'] +=  round($v['base_total_paid']+$v['base_total_due'],2);
                    $list[$k]['total_money']      =  round($v['base_total_paid']+$v['base_total_due'],2);
                }
                //订单镜架成本
                if(isset($costInfo['thispageFramePrice'])){
                    if(array_key_exists($v['increment_id'],$costInfo['thispageFramePrice'])){
                        $list[$k]['frame_cost']   = $costInfo['thispageFramePrice'][$v['increment_id']];
                    }
                }
                //订单镜片成本
                if(isset($costInfo['thispageLensPrice'])){
                    if(array_key_exists($v['increment_id'],$costInfo['thispageLensPrice'])){
                        $list[$k]['lens_cost']    = $costInfo['thispageLensPrice'][$v['increment_id']];
                    }
                }
                //订单退款金额
                if(isset($costInfo['thispageRefundMoney'])){
                    if(array_key_exists($v['increment_id'],$costInfo['thispageRefundMoney'])){
                        $list[$k]['refund_money'] = $costInfo['thispageRefundMoney'][$v['increment_id']];
                    }
                }
                //订单补差价金额
                if(isset($costInfo['thispageFullPostMoney'])){
                    if(array_key_exists($v['increment_id'],$costInfo['thispageFullPostMoney'])){
                        $list[$k]['fill_post']    = $costInfo['thispageFullPostMoney'][$v['increment_id']];
                    }
                }
                //订单加工费
                if(isset($costInfo['thisPageProcessCost'])){
                    if(array_key_exists($v['entity_id'],$costInfo['thisPageProcessCost'])){
                        $list[$k]['process_cost'] = $costInfo['thisPageProcessCost'][$v['entity_id']];
                    }
                }
            }
            $result = array(
                "total"             =>  $total, 
                "rows"              =>  $list, 
                "totalPayInfo"      =>  round($costInfo['totalPayInfo'],2),
                "totalLensPrice"    =>  round($costInfo['totalLensPrice'],2),
                "totalFramePrice"   =>  round($costInfo['totalFramePrice'],2),
                "totalPostageMoney" =>  round($costInfo['totalPostageMoney'],2),
                "totalRefundMoney"  =>  round($costInfo['totalRefundMoney'],2),
                "totalFullPostMoney"=>  round($costInfo['totalFullPostMoney'],2),
                "totalProcessCost"  =>  round($costInfo['totalProcessCost'],2)

            );

            return json($result);
        }
        $this->assign('label', $label);
        $this->assignconfig('label', $label);
        return $this->view->fetch();
    }
    /***
     * 导入邮费页面 create@lsw
     */
    public function postage_import()
    {
        $label = $this->request->get('label', 1);
        //设置过滤方法
        $this->request->filter(['strip_tags']);
        if ($this->request->isAjax()) {
            //如果发送的来源是Selectpage，则转发到Selectpage
            if ($this->request->request('keyField')) {
                return $this->selectpage();
            }
            //根据传的标签切换对应站点数据库
            $label = $this->request->get('label', 1);
            if ($label == 1) {
                $model = $this->zeelool;
            } elseif ($label == 2) {
                $model = $this->voogueme;
            } elseif ($label == 3) {
                $model = $this->nihao;
            }
            list($where, $sort, $order, $offset, $limit) = $this->buildparams();
            
            $total = $model
                ->where($where)
                ->order($sort, $order)
                ->count();

            $list = $model
                ->where($where)
                ->order($sort, $order)
                ->limit($offset, $limit)
                ->select();

            $list = collection($list)->toArray();

            $result = array("total" => $total, "rows" => $list);

            return json($result);
        }
        $this->assign('label', $label);
        $this->assignconfig('label', $label);
        return $this->view->fetch();
    }

    /***
     * 邮费导入   create@lsw
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
            $listName = ['订单号', '邮费'];
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
                if ($listName !== $fields) {
                    throw new Exception("模板文件不正确！！");
                }
    
                $data = [];
                for ($currentRow = 2; $currentRow <= $allRow; $currentRow++) {
                    for ($currentColumn = 1; $currentColumn <= $maxColumnNumber; $currentColumn++) {
                        $val = $currentSheet->getCellByColumnAndRow($currentColumn, $currentRow)->getValue();
                        $data[$currentRow - 2][$currentColumn - 1] = is_null($val) ? '' : $val;
                    }
                }
            } catch (Exception $exception) {
                $this->error($exception->getMessage());
            }
            $model = $this->zeelool;
            foreach($data as $k => $v) {
                    $increment_id = $v[0];
                    $postage_money = $v[1];
                    $result = $model->updatePostageMoney($increment_id,$postage_money);
                    if ($result === false) {
                        $this->error($this->model->getError());
                    }
            }
            $this->success();
        }
}
