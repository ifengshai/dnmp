<?php

namespace app\admin\controller\order;

use app\common\controller\Backend;
use fast\Trackingmore;
use Util\NihaoPrescriptionDetailHelper;
use Util\ZeeloolPrescriptionDetailHelper;
use Util\VooguemePrescriptionDetailHelper;
use Util\WeseeopticalPrescriptionDetailHelper;
use Util\MeeloogPrescriptionDetailHelper;
use PhpOffice\PhpSpreadsheet\Reader\Xlsx;
use PhpOffice\PhpSpreadsheet\Reader\Xls;
use PhpOffice\PhpSpreadsheet\Reader\Csv;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use think\Exception;
use think\Loader;
use think\Db;
use PhpOffice\PhpSpreadsheet\Spreadsheet;


/**
 * 订单列表
 */
class Index extends Backend
{
    protected $noNeedRight = ['orderDetail', 'batch_print_label_new', 'batch_export_xls'];
    protected $model = null;

    public function _initialize()
    {
        parent::_initialize();
        $this->nihao = new \app\admin\model\order\order\Nihao;
        $this->zeelool = new \app\admin\model\order\order\Zeelool;
        $this->voogueme = new \app\admin\model\order\order\Voogueme;
        $this->weseeoptical = new \app\admin\model\order\order\Weseeoptical;
        $this->meeloog = new \app\admin\model\order\order\Meeloog;
        $this->ordernodedeltail = new \app\admin\model\order\order\Ordernodedeltail;
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
            switch ($label) {
                case 1:
                    $db = 'database.db_zeelool';
                    $model = $this->zeelool;
                    break;
                case 2:
                    $db = 'database.db_voogueme';
                    $model = $this->voogueme;
                    break;
                case 3:
                    $db = 'database.db_nihao';
                    $model = $this->nihao;
                    break;
                case 4:
                    $db = 'database.db_weseeoptical';
                    $model = $this->weseeoptical;
                    break;
                case 5:
                    $db = 'database.db_meeloog';
                    $model = $this->meeloog;
                    break;
                default:
                    return false;
                    break;
            }

            $filter = json_decode($this->request->get('filter'), true);
            //SKU搜索
            if ($filter['sku']) {
                $smap['sku'] = ['like', '%' . $filter['sku'] . '%'];
                if ($filter['status']) {
                    $smap['status'] = ['in', $filter['status']];
                }
                $ids = $model->getOrderId($smap);
                $map['entity_id'] = ['in', $ids];
                unset($filter['sku']);
                $this->request->get(['filter' => json_encode($filter)]);
            }


            list($where, $sort, $order, $offset, $limit) = $this->buildparams();

            $total = $model
                ->where($where)
                ->where($map)
                ->order($sort, $order)
                ->count();

            $list = $model
                ->where($where)
                ->where($map)
                ->order($sort, $order)
                ->limit($offset, $limit)
                ->select();

            $list = collection($list)->toArray();

            $arr = [
                'Business express(4-7 business days)',
                'Expedited',
                'Business express(7-14 Days)',
                'Business express(7-12 Days)',
                'Business express',
                'Business express (7-12 days)',
                'Business express(7-12 days)',
                'Express Shipping (3-5 Days)',
                'Express Shipping (5-8Days)',
                'Express Shipping (3-5 Business Days)',
                'Express Shipping (5-8 Business Days)',
                'Business Express(7-12 Days)',
                'Business express(7-12 business days)'
            ];
            foreach ($list as &$v) {
                if (in_array($v['shipping_description'], $arr)) {
                    $v['label'] = 1;
                } else {
                    $v['label'] = 0;
                }
                $smap = [];
                $smap['parent_id'] = $v['entity_id'];
                $smap['address_type'] = 'shipping';
                $country_id = Db::connect($db)
                    ->table('sales_flat_order_address')
                    ->where($smap)
                    ->value('country_id');
                $v['country_id'] = $country_id;
            }
            unset($v);

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
        } elseif ($label == 5) {
            $model = $this->meeloog;
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
        } elseif ($label == 5) {
            $goods = MeeloogPrescriptionDetailHelper::get_list_by_entity_ids($ids);
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
     * 订单信息2
     */
    public function orderDetail($order_number = null)
    {

        $order_number = $order_number ?? $this->request->get('order_number');
        //$order_number = 100077570;
        //查询订单详情		
        $ruleList = collection($this->ordernodedeltail->where(['order_number' => ['eq', $order_number]])->order('node_type asc')->column('node_type,create_time'))->toArray();

        $key_list = array_keys($ruleList);
	
        $entity_id = $this->request->get('id');
        $label = $this->request->get('label', 1);
        $this->view->assign(compact('order_number', 'entity_id', 'label'));
        $this->view->assign("list", $ruleList);
        $this->view->assign("key_list", $key_list);
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
        } elseif ($label == 4) {
            $model = $this->weseeoptical;
        } elseif ($label == 5) {
            $model = $this->meeloog;
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
        $this->view->assign("label", $label);
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
            if ($rep != '{}') {
                // $whereArr = json_decode($rep,true);
                // if(!array_key_exists('created_at',$whereArr)){
                //     $addWhere  .= " AND DATE_SUB(CURDATE(), INTERVAL 7 DAY) <= date(created_at)";
                // }
            } else {
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
                ->order($sort, $order)
                ->limit($offset, $limit)
                ->column('entity_id');
            $costInfo = $model->getOrderCostInfo($totalId, $thisPageId);
            $list = collection($list)->toArray();
            foreach ($list as $k => $v) {
                //原先
                // if(isset($costInfo['thisPagePayPrice'])){
                //     if(array_key_exists($v['entity_id'],$costInfo['thisPagePayPrice'])){
                //         $list[$k]['total_money'] = $costInfo['thisPagePayPrice'][$v['entity_id']];
                //    }
                // }
                //订单支付金额
                if (in_array($v['status'], ['processing', 'complete', 'creditcard_proccessing', 'free_processing'])) {
                    //$costInfo['totalPayInfo'] +=  round($v['base_total_paid']+$v['base_total_due'],2);
                    $list[$k]['total_money']      =  round($v['base_total_paid'] + $v['base_total_due'], 2);
                }
                //订单镜架成本
                if (isset($costInfo['thispageFramePrice'])) {
                    if (array_key_exists($v['increment_id'], $costInfo['thispageFramePrice'])) {
                        $list[$k]['frame_cost']   = $costInfo['thispageFramePrice'][$v['increment_id']];
                    }
                }
                //订单镜片成本
                if (isset($costInfo['thispageLensPrice'])) {
                    if (array_key_exists($v['increment_id'], $costInfo['thispageLensPrice'])) {
                        $list[$k]['lens_cost']    = $costInfo['thispageLensPrice'][$v['increment_id']];
                    }
                }
                //订单退款金额
                if (isset($costInfo['thispageRefundMoney'])) {
                    if (array_key_exists($v['increment_id'], $costInfo['thispageRefundMoney'])) {
                        $list[$k]['refund_money'] = $costInfo['thispageRefundMoney'][$v['increment_id']];
                    }
                }
                //订单补差价金额
                if (isset($costInfo['thispageFullPostMoney'])) {
                    if (array_key_exists($v['increment_id'], $costInfo['thispageFullPostMoney'])) {
                        $list[$k]['fill_post']    = $costInfo['thispageFullPostMoney'][$v['increment_id']];
                    }
                }
                //订单加工费
                if (isset($costInfo['thisPageProcessCost'])) {
                    if (array_key_exists($v['entity_id'], $costInfo['thisPageProcessCost'])) {
                        $list[$k]['process_cost'] = $costInfo['thisPageProcessCost'][$v['entity_id']];
                    }
                }
            }
            $result = array(
                "total"             =>  $total,
                "rows"              =>  $list,
                "totalPayInfo"      =>  round($costInfo['totalPayInfo'], 2),
                "totalLensPrice"    =>  round($costInfo['totalLensPrice'], 2),
                "totalFramePrice"   =>  round($costInfo['totalFramePrice'], 2),
                "totalPostageMoney" =>  round($costInfo['totalPostageMoney'], 2),
                "totalRefundMoney"  =>  round($costInfo['totalRefundMoney'], 2),
                "totalFullPostMoney" =>  round($costInfo['totalFullPostMoney'], 2),
                "totalProcessCost"  =>  round($costInfo['totalProcessCost'], 2)

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
        foreach ($data as $k => $v) {
            $increment_id = $v[0];
            $postage_money = $v[1];
            $result = $model->updatePostageMoney($increment_id, $postage_money);
            if ($result === false) {
                $this->error($this->model->getError());
            }
        }
        $this->success();
    }


    /**
     * 生成新的条形码
     */
    protected function generate_barcode_new($text, $fileName)
    {
        // 引用barcode文件夹对应的类
        Loader::import('BCode.BCGFontFile', EXTEND_PATH);
        //Loader::import('BCode.BCGColor',EXTEND_PATH);
        Loader::import('BCode.BCGDrawing', EXTEND_PATH);
        // 条形码的编码格式
        // Loader::import('BCode.BCGcode39',EXTEND_PATH,'.barcode.php');
        Loader::import('BCode.BCGcode128', EXTEND_PATH, '.barcode.php');

        // $code = '';
        // 加载字体大小
        $font = new \BCGFontFile(EXTEND_PATH . '/BCode/font/Arial.ttf', 18);
        //颜色条形码
        $color_black = new \BCGColor(0, 0, 0);
        $color_white = new \BCGColor(255, 255, 255);
        $label = new \BCGLabel();
        $label->setPosition(\BCGLabel::POSITION_TOP);
        $label->setText('Made In China');
        $label->setFont($font);
        $drawException = null;
        try {
            // $code = new \BCGcode39();
            $code = new \BCGcode128();
            $code->setScale(4);
            $code->setThickness(18); // 条形码的厚度
            $code->setForegroundColor($color_black); // 条形码颜色
            $code->setBackgroundColor($color_white); // 空白间隙颜色
            $code->setFont($font); //设置字体
            $code->addLabel($label); //设置字体
            $code->parse($text); // 条形码需要的数据内容
        } catch (\Exception $exception) {
            $drawException = $exception;
        }
        //根据以上条件绘制条形码
        $drawing = new \BCGDrawing('', $color_white);
        if ($drawException) {
            $drawing->drawException($drawException);
        } else {
            $drawing->setBarcode($code);
            if ($fileName) {
                // echo 'setFilename<br>';
                $drawing->setFilename($fileName);
            }
            $drawing->draw();
        }
        // 生成PNG格式的图片
        header('Content-Type: image/png');
        // header('Content-Disposition:attachment; filename="barcode.png"'); //自动下载
        $drawing->finish(\BCGDrawing::IMG_FORMAT_PNG);
    }

    //批量打印标签
    public function batch_print_label_new()
    {
        //根据传的标签切换对应站点数据库
        $label = $this->request->get('label', 1);
        switch ($label) {
            case 1:
                $db = 'database.db_zeelool';
                $model = $this->zeelool;
                break;
            case 2:
                $db = 'database.db_voogueme';
                $model = $this->voogueme;
                break;
            case 3:
                $db = 'database.db_nihao';
                $model = $this->nihao;
                break;
            case 4:
                $db = 'database.db_weseeoptical';
                $model = $this->weseeoptical;
                break;
            case 5:
                $db = 'database.db_meeloog';
                $model = $this->meeloog;
                break;
            default:
                return false;
                break;
        }
        ob_start();
        $entity_ids = rtrim(input('id_params'), ',');

        if ($entity_ids) {

            //判断是否为美国且 非商业快递
            $smap['parent_id'] = ['in', $entity_ids];
            $smap['country_id'] = ['not in', ['US', 'PR']];
            $smap['address_type'] = 'shipping';
            $count = Db::connect($db)
                ->table('sales_flat_order_address')
                ->where($smap)
                ->count(1);
            if ($count > 0) {
                return $this->error('存在非美国的订单', url('index?ref=addtabs&label=' . $label));
            }


            $processing_order_querySql = "select sfo.shipping_description,sfo.increment_id,round(sfo.total_qty_ordered,0) NUM,sfoi.product_options,sfoi.order_id,sfo.`status`,sfoi.sku,sfoi.qty_ordered,sfo.created_at
from sales_flat_order_item sfoi
left join sales_flat_order sfo on  sfoi.order_id=sfo.entity_id 
where sfo.`status` in ('processing','creditcard_proccessing','free_processing','complete','paypal_reversed','paypal_canceled_reversal') and sfo.entity_id in($entity_ids)
order by NUM asc;";
            $processing_order_list = $model->query($processing_order_querySql);
            $file_header = <<<EOF
                <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<style>
body{ margin:0; padding:0}
.single_box{margin:0 auto;width: 400px;padding:1mm;margin-bottom:2mm;}
table.addpro {clear: both;table-layout: fixed; margin-top:6px; border-top:1px solid #000;border-left:1px solid #000; font-size:12px;}
table.addpro .title {background: none repeat scroll 0 0 #f5f5f5; }
table.addpro .title  td {border-collapse: collapse;color: #000;text-align: center; font-weight:normal; }
table.addpro tbody td {word-break: break-all; text-align: center;border-bottom:1px solid #000;border-right:1px solid #000;}
table.addpro.re tbody td{ position:relative}
</style>
EOF;

            $arr = [
                'Business express(4-7 business days)',
                'Expedited',
                'Business express(7-14 Days)',
                'Business express(7-12 Days)',
                'Business express',
                'Business express (7-12 days)',
                'Business express(7-12 days)',
                'Express Shipping (3-5 Days)',
                'Express Shipping (5-8Days)',
                'Express Shipping (3-5 Business Days)',
                'Express Shipping (5-8 Business Days)',
                'Business Express(7-12 Days)'
            ];

            $file_content = '';
            $temp_increment_id = 0;
            foreach ($processing_order_list as $processing_key => $processing_value) {
                if (in_array($processing_value['shipping_description'], $arr)) {
                    return $this->error('存在商业快递的订单', url('index?ref=addtabs&label=' . $label));
                }

                if ($temp_increment_id != $processing_value['increment_id']) {
                    $temp_increment_id = $processing_value['increment_id'];

                    $date = substr($processing_value['created_at'], 0, strpos($processing_value['created_at'], " "));
                    $fileName = ROOT_PATH . "public" . DS . "uploads" . DS . "printOrder" . DS . "zeelool" . DS . "new" . DS . "$date" . DS . "$temp_increment_id.png";
                    // dump($fileName);
                    $dir = ROOT_PATH . "public" . DS . "uploads" . DS . "printOrder" . DS . "zeelool" . DS . "new"  . DS . "$date";
                    if (!file_exists($dir)) {
                        mkdir($dir, 0777, true);
                        // echo '创建文件夹$dir成功';
                    } else {
                        // echo '需创建的文件夹$dir已经存在';
                    }
                    $img_url = "/uploads/printOrder/zeelool/new/$date/$temp_increment_id.png";
                    //生成条形码
                    $this->generate_barcode_new($temp_increment_id, $fileName);
                    // echo '<br>需要打印'.$temp_increment_id;
                    $file_content .= "<div  class = 'single_box'>
                <table width='400mm' height='102px' border='0' cellspacing='0' cellpadding='0' class='addpro' style='margin:0px auto;margin-top:0px;padding:0px;'>
                <tr>
                <td rowspan='5' colspan='3' style='padding:10px;'><img src='" . $img_url . "' height='80%'><br></td></tr>                
                </table></div>";
                }
            }
            echo $file_header . $file_content;
        }
    }

    /**
     * 批量导出xls
     *
     * @Description
     * @author wpl
     * @since 2020/02/28 14:45:39 
     * @return void
     */
    public function batch_export_xls()
    {
        set_time_limit(0);
        ini_set('memory_limit', '512M');
        //根据传的标签切换对应站点数据库
        $label = $this->request->get('label', 1);
        switch ($label) {
            case 1:
                $model = $this->zeelool;
                break;
            case 2:
                $model = $this->voogueme;
                break;
            case 3:
                $model = $this->nihao;
                break;
            case 4:
                $model = $this->weseeoptical;
                break;
            case 5:
                $model = $this->meeloog;
                break;
            default:
                return false;
                break;
        }

        $ids = input('ids');
        if ($ids) {
            $map['entity_id'] = ['in', $ids];
        }

        $filter = json_decode($this->request->get('filter'), true);
        //SKU搜索
        if ($filter['sku']) {
            $smap['sku'] = ['like', '%' . $filter['sku'] . '%'];
            if ($filter['status']) {
                $smap['status'] = ['in', $filter['status']];
            }
            $ids = $model->getOrderId($smap);
            $map['entity_id'] = ['in', $ids];
            unset($filter['sku']);
            $this->request->get(['filter' => json_encode($filter)]);
        }


        list($where) = $this->buildparams();

        $list = $model
            ->field('increment_id,customer_firstname,customer_email,status,base_grand_total,base_shipping_amount,custom_order_prescription_type,order_type,created_at')
            ->where($where)
            ->where($map)
            ->select();

        $list = collection($list)->toArray();

        //从数据库查询需要的数据
        $spreadsheet = new Spreadsheet();

        //常规方式：利用setCellValue()填充数据
        $spreadsheet->setActiveSheetIndex(0)->setCellValue("A1", "订单号")
            ->setCellValue("B1", "客户名称")
            ->setCellValue("C1", "邮箱");   //利用setCellValues()填充数据
        $spreadsheet->setActiveSheetIndex(0)->setCellValue("D1", "状态")
            ->setCellValue("E1", "订单金额");
        $spreadsheet->setActiveSheetIndex(0)->setCellValue("F1", "邮费")
            ->setCellValue("G1", "处方类型");
        $spreadsheet->setActiveSheetIndex(0)->setCellValue("H1", "订单类型")
            ->setCellValue("I1", "创建时间");
        foreach ($list as $key => $value) {

            $spreadsheet->getActiveSheet()->setCellValueExplicit("A" . ($key * 1 + 2), $value['increment_id'], \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
            $spreadsheet->getActiveSheet()->setCellValue("B" . ($key * 1 + 2), $value['customer_firstname']);
            $spreadsheet->getActiveSheet()->setCellValue("C" . ($key * 1 + 2), $value['customer_email']);
            if ($value['custom_order_prescription_type'] == 1) {
                $custom_order_prescription_type = '仅镜架';
            } elseif ($value['custom_order_prescription_type'] == 2) {
                $custom_order_prescription_type = '现货处方镜';
            } elseif ($value['custom_order_prescription_type'] == 3) {
                $custom_order_prescription_type = '定制处方镜';
            } elseif ($value['custom_order_prescription_type'] == 4) {
                $custom_order_prescription_type = '镜架+现货';
            } elseif ($value['custom_order_prescription_type'] == 5) {
                $custom_order_prescription_type = '镜架+定制';
            } elseif ($value['custom_order_prescription_type'] == 6) {
                $custom_order_prescription_type = '现片+定制片';
            }

            if ($value['order_type'] == 1) {
                $order_type = '普通订单';
            } elseif ($value['order_type'] == 2) {
                $order_type = '批发单';
            } elseif ($value['order_type'] == 3) {
                $order_type = '网红单';
            } elseif ($value['order_type'] == 4) {
                $order_type = '补发单';
            } elseif ($value['order_type'] == 5) {
                $order_type = '补差价';
            } elseif ($value['order_type'] == 6) {
                $order_type = '一件代发';
            }


            $spreadsheet->getActiveSheet()->setCellValue("D" . ($key * 1 + 2), $value['status']);
            $spreadsheet->getActiveSheet()->setCellValue("E" . ($key * 1 + 2), $value['base_grand_total']);
            $spreadsheet->getActiveSheet()->setCellValue("F" . ($key * 1 + 2), $value['base_shipping_amount']);
            $spreadsheet->getActiveSheet()->setCellValue("G" . ($key * 1 + 2), $custom_order_prescription_type);
            $spreadsheet->getActiveSheet()->setCellValue("H" . ($key * 1 + 2), $order_type);
            $spreadsheet->getActiveSheet()->setCellValue("I" . ($key * 1 + 2), $value['created_at']);
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
        $spreadsheet->getActiveSheet()->getColumnDimension('I')->setWidth(30);


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

        $spreadsheet->getActiveSheet()->getStyle('A1:I' . $spreadsheet->getActiveSheet()->getHighestRow())->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
        $spreadsheet->setActiveSheetIndex(0);

        $format = 'xlsx';
        $savename = '订单数据' . date("YmdHis", time());;

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
