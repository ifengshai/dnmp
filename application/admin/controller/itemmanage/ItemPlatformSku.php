<?php

namespace app\admin\controller\itemmanage;

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use think\Db;
use think\Request;
use app\common\controller\Backend;
use app\admin\model\itemmanage\Item;
use app\admin\model\platformmanage\MagentoPlatform;
use app\admin\model\platformmanage\PlatformMap;
use think\Exception;
use think\exception\PDOException;
use think\exception\ValidateException;
use fast\Soap;

/**
 * 平台SKU管理
 *
 * @icon fa fa-circle-o
 */
class ItemPlatformSku extends Backend
{

    /**
     * ItemPlatformSku模型对象
     * @var \app\admin\model\itemmanage\ItemPlatformSku
     */
    protected $model = null;
    protected $platform = null;
    protected $relationSearch = true;
    public function _initialize()
    {
        parent::_initialize();
        $this->model = new \app\admin\model\itemmanage\ItemPlatformSku;
        $this->platform = new \app\admin\model\platformmanage\MagentoPlatform;
    }

    /**
     * 默认生成的控制器所继承的父类中有index/add/edit/del/multi五个基础方法、destroy/restore/recyclebin三个回收站方法
     * 因此在当前控制器中可不用编写增删改查的代码,除非需要自己控制这部分逻辑
     * 需要将application/admin/library/traits/Backend.php中对应的方法复制到当前控制器,然后进行修改
     */
    //平台SKU首页
    public function index()
    {
        //设置过滤方法
        //
        $this->request->filter(['strip_tags']);
        if ($this->request->isAjax()) {
            //如果发送的来源是Selectpage，则转发到Selectpage
            if ($this->request->request('keyField')) {
                return $this->selectpage();
            }
            list($where, $sort, $order, $offset, $limit) = $this->buildparams();
            $total = $this->model
                ->with(['item' => ['item_status']])
                ->where($where)
                ->order($sort, $order)
                ->count();
            $list = $this->model
                ->with(['item' => ['item_status']])
                ->where($where)
                ->order($sort, $order)
                ->limit($offset, $limit)
                ->select();
            $list = collection($list)->toArray();
            // var_dump($list);
            if (!empty($list) && is_array($list)) {
                $platform = (new MagentoPlatform())->getOrderPlatformList();
                foreach ($list as $k => $v) {
                    if ($v['platform_type']) {
                        $list[$k]['platform_type'] = $platform[$v['platform_type']];
                    }
                }
            }

            $result = array("total" => $total, "rows" => $list);

            return json($result);
        }
        $this->view->assign('PlatformList', $this->platform->magentoPlatformList());
        return $this->view->fetch();
    }

    /**
     * 批量导出功能 平台sku管理
     *
     * Created by Phpstorm.
     * User: jhh
     * Date: 2020/9/7
     * Time: 15:32:59
     */
    public function batch_export_xls()
    {
        set_time_limit(0);
        ini_set('memory_limit', '512M');
        $ids = input('ids');
        $this->relationSearch = true;
        if ($ids) {
            $map['item_platform_sku.id'] = ['in', $ids];
        }

        //自定义sku搜索
        $filter = json_decode($this->request->get('filter'), true);

        list($where) = $this->buildparams();
        $list = $this->model
            ->with(['item' => ['item_status']])
            ->where($where)
            ->where($map)
            ->select();
        $list = collection($list)->toArray();
        // dump($list);die;

        //从数据库查询需要的数据
        $spreadsheet = new Spreadsheet();

        //常规方式：利用setCellValue()填充数据
        $spreadsheet->setActiveSheetIndex(0)->setCellValue("A1", "商品sku编码")
            ->setCellValue("B1", "平台sku")
            ->setCellValue("C1", "商品名称");   //利用setCellValues()填充数据
        $spreadsheet->setActiveSheetIndex(0)->setCellValue("D1", "站点")
            ->setCellValue("E1", "sku状态");
        $spreadsheet->setActiveSheetIndex(0)->setCellValue("F1", "对应平台SKU状态")
            ->setCellValue("G1", "是否上传到对应平台");
        $spreadsheet->setActiveSheetIndex(0)->setCellValue("H1", "创建人")
            ->setCellValue("I1", "创建时间");

        foreach ($list as $key => $value) {
            $spreadsheet->getActiveSheet()->setCellValueExplicit("A" . ($key * 1 + 2), $value['sku'], \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
            $spreadsheet->getActiveSheet()->setCellValue("B" . ($key * 1 + 2), $value['platform_sku']);
            $spreadsheet->getActiveSheet()->setCellValue("C" . ($key * 1 + 2), $value['name']);
            switch ($value['platform_type']){
                case 1:
                    $plat_name = 'zeelool';
                    break;
                case 2:
                    $plat_name = 'voogueme';
                    break;
                case 3:
                    $plat_name = 'nihao';
                    break;
                case 4:
                    $plat_name = 'meeloog';
                    break;
                case 5:
                    $plat_name = 'wesee';
                    break;
                case 8:
                    $plat_name = 'amazon';
                    break;
            }
            switch ($value['item']['item_status']){
                case 1:
                    $item_status = '新建';
                    break;
                case 2:
                    $item_status = '提交审核';
                    break;
                case 3:
                    $item_status = '审核通过';
                    break;
                case 4:
                    $item_status = '审核拒绝';
                    break;
                case 5:
                    $item_status = '取消';
                    break;
            }
            switch ($value['outer_sku_status']){
                case 1:
                    $outer_sku_status = '上架';
                    break;
                case 2:
                    $outer_sku_status = '下架';
                    break;
            }
            switch ($value['is_upload']){
                case 1:
                    $is_upload = '已上传';
                    break;
                case 2:
                    $is_upload = '未上传';
                    break;
            }


            $spreadsheet->getActiveSheet()->setCellValue("D" . ($key * 1 + 2), $plat_name);
            $spreadsheet->getActiveSheet()->setCellValue("E" . ($key * 1 + 2), $item_status);
            $spreadsheet->getActiveSheet()->setCellValue("F" . ($key * 1 + 2), $outer_sku_status);
            $spreadsheet->getActiveSheet()->setCellValue("G" . ($key * 1 + 2), $is_upload);
            $spreadsheet->getActiveSheet()->setCellValue("H" . ($key * 1 + 2), $value['create_person']);
            $spreadsheet->getActiveSheet()->setCellValue("I" . ($key * 1 + 2), $value['create_time']);
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

        $spreadsheet->getActiveSheet()->getStyle('A1:O' . $spreadsheet->getActiveSheet()->getHighestRow())->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
        $spreadsheet->setActiveSheetIndex(0);

        $format = 'xlsx';
        $savename = '平台sku数据' . date("YmdHis", time());;

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

    //商品预售首页
    public function presell()
    {
        //设置过滤方法
        $this->request->filter(['strip_tags']);
        if ($this->request->isAjax()) {
            //如果发送的来源是Selectpage，则转发到Selectpage
            if ($this->request->request('keyField')) {
                return $this->selectpage();
            }
            list($where, $sort, $order, $offset, $limit) = $this->buildparams();
            $total = $this->model
                ->where($where)
                ->whereNotNull('presell_create_time')
                ->order($sort, $order)
                ->count();

            $list = $this->model
                ->where($where)
                ->whereNotNull('presell_create_time')
                ->order($sort, $order)
                ->limit($offset, $limit)
                ->select();

            $list = collection($list)->toArray();
            if (!empty($list) && is_array($list)) {
                $platform = (new MagentoPlatform())->getOrderPlatformList();
                foreach ($list as $k => $v) {
                    if ($v['platform_type']) {
                        $list[$k]['platform_type'] = $platform[$v['platform_type']];
                    }
                }
            }
            $result = array("total" => $total, "rows" => $list);

            return json($result);
        }
        return $this->view->fetch();
    }
    /***
     * 商品上架
     */
    public function putaway()
    {
        if ($this->request->isAjax()) {
            $id = $this->request->param('ids');
            $row = $this->model->get($id);
            if ($row['platform_sku_status'] == 1) {
                $this->error('商品正在上架中,不能重复上架');
            }
            $map['id'] = $id;
            $data['platform_sku_status'] = 1;
            $res = $this->model->allowField(true)->isUpdate(true, $map)->save($data);
            if ($res) {
                $this->success('上架成功');
            } else {
                $this->error('上架失败');
            }
        } else {
            $this->error('404 Not found');
        }
    }
    /****
     * 商品下架
     */
    public function soldOut()
    {
        if ($this->request->isAjax()) {
            $id = $this->request->param('ids');
            $row = $this->model->get($id);
            if ($row['platform_sku_status'] == 2) {
                $this->error('商品正在下架中,不能重复下架');
            }
            $map['id'] = $id;
            $data['platform_sku_status'] = 2;
            $res = $this->model->allowField(true)->isUpdate(true, $map)->save($data);
            if ($res) {
                $this->success('下架成功');
            } else {
                $this->error('下架失败');
            }
        } else {
            $this->error('404 Not found');
        }
    }
    /***
     * 添加商品预售
     */
    public function addPresell()
    {
        if ($this->request->isPost()) {
            $params = $this->request->post("row/a");
            if ($params) {
                $params = $this->preExcludeFields($params);
                if (empty($params['platform_sku'])) {
                    $this->error(__('Platform sku cannot be empty'));
                }
                if (empty($params['presell_num'])) {
                    $this->error(__('SKU pre-order quantity cannot be empty'));
                }
                if ($params['presell_start_time'] == $params['presell_end_time']) {
                    $this->error('预售开始时间和结束时间不能相等');
                }
                //                echo $params['presell_start_time'];
                //                echo '<br>';
                //                echo $params['presell_end_time'];
                //                echo '<br>';
                //                echo date("Y-m-d H:i:s", time());
                //                exit;
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
                    $params['presell_residue_num'] = $params['presell_num'];
                    $params['presell_create_person'] = session('admin.nickname');
                    $params['presell_create_time'] = $now_time =  date("Y-m-d H:i:s", time());
                    if ($now_time >= $params['presell_start_time']) { //如果当前时间大于开始时间
                        $params['presell_status'] = 2;
                    }
                    $result = $this->model->allowField(true)->save($params, ['platform_sku' => $params['platform_sku']]);
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
    /***
     * 编辑商品预售
     */
    public function editPresell($ids = null)
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
                if (empty($params['platform_sku'])) {
                    $this->error(__('Platform sku cannot be empty'));
                }
                if (empty($params['presell_num'])) {
                    $this->error(__('SKU pre-order quantity cannot be empty'));
                }
                if ($params['presell_start_time'] == $params['presell_end_time']) {
                    $this->error('预售开始时间和结束时间不能相等');
                }
                $result = false;
                Db::startTrans();
                try {
                    //是否采用模型验证
                    if ($this->modelValidate) {
                        $name = str_replace("\\model\\", "\\validate\\", get_class($this->model));
                        $validate = is_bool($this->modelValidate) ? ($this->modelSceneValidate ? $name . '.edit' : $name) : $this->modelValidate;
                        $row->validateFailException(true)->validate($validate);
                    }
                    $params['presell_create_person'] = session('admin.nickname');
                    $params['presell_create_time'] = $now_time =  date("Y-m-d H:i:s", time());
                    if ($now_time >= $params['presell_start_time']) { //如果当前时间大于开始时间
                        $params['presell_status'] = 2;
                    }
                    $result = $row->allowField(true)->save($params, ['platform_sku' => $params['platform_sku']]);
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
    /***
     * 开启预售
     */
    public function openStart($ids = null)
    {
        if ($this->request->isAjax()) {
            $row = $this->model->get($ids);
            if ($row['presell_status'] == 2) {
                $this->error(__('Pre-sale on, do not repeat on'));
            }
            $now_time = date('Y-m-d H:i:s', time());
            if ($row['presell_end_time'] < $now_time) {
                $this->error(__('The closing time has expired, please select again'));
            }
            $map['id'] = $ids;
            $data['presell_status'] = 2;
            $data['presell_open_time'] =  date('Y-m-d H:i:s', time());
            $res = $this->model->allowField(true)->isUpdate(true, $map)->save($data);
            if ($res) {
                $this->success('预售开启成功');
            } else {
                $this->error('预售开启失败');
            }
        } else {
            $this->error('404 Not found');
        }
    }
    /***
     * 关闭预售
     */
    public function openEnd($ids = null)
    {
        if ($this->request->isAjax()) {
            $row = $this->model->get($ids);
            if ($row['presell_status'] == 3) {
                $this->error(__('Pre-sale on, do not repeat on'));
            }
            $map['id'] = $ids;
            $data['presell_status'] = 3;
            $data['presell_open_time'] =  date('Y-m-d H:i:s', time());
            $res = $this->model->allowField(true)->isUpdate(true, $map)->save($data);
            if ($res) {
                $this->success('关闭预售成功');
            } else {
                $this->error('关闭预售失败');
            }
        } else {
            $this->error('404 Not found');
        }
    }
    /***
     * 异步查询平台sku
     */
    public function ajaxGetLikePlatformSku(Request $request)
    {
        if ($this->request->isAjax()) {
            $origin_sku = $request->post('origin_sku');
            $result = $this->model->likePlatformSku($origin_sku);
            if (!$result) {
                return $this->error('商品SKU不存在，请重新尝试');
            }
            return $this->success('', '', $result, 0);
        } else {
            $this->error('404 not found');
        }
    }
    /***
     * 异步查询用户输入的平台sku是否存在
     */
    public function ajaxGetPlatformSkuInfo(Request $request)
    {
        if ($this->request->isAjax()) {
            $platform_sku = $request->post('platform_sku');
            $result = $this->model->getPlatformSku($platform_sku);
            if ($result == -1) {
                return $this->error('此SKU有预售数量,请直接编辑');
            }
            if (!$result) {
                return $this->error('平台商品SKU不存在，请重新填写');
            }
            return $this->success('是正确的SKU');
        } else {
            $this->error('404 not found');
        }
    }
    /***
     * 上传商品到magento平台
     */
    public function uploadItem($ids = null, $platformId = null)
    {
        if ($this->request->isAjax()) {
            if (!is_array($ids) || in_array("", $ids)) {
                $this->error(__('Error selecting item parameters. Please reselect or contact the developer'));
            }
            if (count($ids) > 1) {
                $this->error(__('Multiple data updates are not currently supported, please update one at a time'));
            }
            if (!$platformId) {
                $this->error(__('Error selecting platform parameters. Please reselect or contact the developer'));
            }
            $platformRow = $this->platform->get($platformId);
            if (!$platformRow) {
                $this->error(__('Platform information error, please try again or contact the developer'));
            }
            $magentoUrl = $platformRow->magento_url;
            if (!$magentoUrl) {
                $this->error(__('The platform url does not exist. Please go to edit it and it cannot be empty'));
            }
        } else {
            $this->error('404 Not found');
        }
    }

    /**
     * 同步商品到magento
     *
     * @Description
     * @author wpl
     * @since 2020/07/22 16:41:14 
     * @param [type] $ids
     * @return void
     */
    public function afterUploadItem($ids = null)
    {
        if ($this->request->isAjax()) {

            $itemPlatformRow = $this->model->findItemPlatform($ids);

            if ($itemPlatformRow['is_upload'] == 1) { //商品已经上传，无需再次上传
                $this->error(__('The product has been uploaded, there is no need to upload again'));
            }
           
            //审核通过把SKU同步到有映射关系的平台
            $uploadItemArr['skus']  = [$itemPlatformRow['platform_sku']];
            $uploadItemArr['site'] = $itemPlatformRow['platform_id'];
            $soap_res = Soap::createProduct($uploadItemArr);
            if ($soap_res) {
                $this->model->where(['id' => $ids])->update(['is_upload' => 1]);
                $this->success('同步成功！！');
            } else {
                $this->success('同步失败！！');
            }
        }
    }


    /****
     * 编辑后面的商品上传至对应平台
     */
    public function afterUploadItem_yuan($ids = null)
    {
        if ($this->request->isAjax()) {

            $itemPlatformRow = $this->model->findItemPlatform($ids);
            if (!$itemPlatformRow) { //对应商品不正确或者平台不正确
                $this->error(__('Incorrect product or incorrect platform'));
            }
            if ($itemPlatformRow['is_upload_item'] == 2) { //控制不上传商品信息
                $this->error(__('The corresponding platform does not need to upload product information, please do not upload'));
            }
            if (!$itemPlatformRow['magento_url']) {
                $this->error(__('The platform url does not exist. Please go to edit it and it cannot be empty'));
            }
            if ($itemPlatformRow['is_upload'] == 1) { //商品已经上传，无需再次上传
                $this->error(__('The product has been uploaded, there is no need to upload again'));
            }
            if (empty($itemPlatformRow['item_attr_name']) || empty($itemPlatformRow['item_type'])) { //平台商品类型和商品属性
                $this->error(__('The product attributes or product types of the platform are not filled in'));
            }
            $uploadFieldsArr = (new PlatformMap())->getPlatformMap($itemPlatformRow['platform_id']);
            if (empty($uploadFieldsArr)) {
                $this->error(__('The upload field cannot be empty, please go to the platform to edit'));
            }
            $itemRow = (new Item())->getItemRow($itemPlatformRow['sku'], $itemPlatformRow['platform_type']);
            if ($itemRow['item_status'] != 3) { //该商品没有审核通过
                $this->error(__('This product has not been approved and cannot be uploaded'));
            }
            $magentoUrl = $itemPlatformRow['magento_url'];
            try {
                $client = new \SoapClient($magentoUrl . '/api/soap/?wsdl');
                $session = $client->login($itemPlatformRow['magento_account'], $itemPlatformRow['magento_key']);
                $attributeSets = $client->call($session, 'product_attribute_set.list');
            } catch (\SoapFault $e) {
                $this->error(__('Platform account or key is incorrect, please go to the platform to edit'));
            } catch (\Exception $e) {
                $this->error(__('An error has occurred. Please contact the developer'));
            }
            if (!is_array($attributeSets)) {
                $this->error(__('An error has occurred. Please contact the developer'));
            }
            $attributeSet = [];
            foreach ($attributeSets as $k => $v) {
                if ($v['name'] == $itemPlatformRow['item_attr_name']) { //如果相等的话
                    $attributeSet['set_id'] = $v['set_id'];
                    $attributeSet['name'] = $v['name'];
                }
            }
            $attributeList = $client->call( //求出商品属性列表
                $session,
                "product_attribute.list",
                array(
                    $attributeSet['set_id']
                )
            );

            //求出需要上传的属性和它们的值
            if (is_array($attributeList)) {
                $attributeListArr = [];
                foreach ($attributeList as $k => $v) {
                    if (in_array($v['code'], $uploadFieldsArr)) {
                        //找出键名
                        $platformName =  array_search($v['code'], $uploadFieldsArr);
                        if (!empty($platformName)) {
                            $v['platformValue'] = $itemRow[$platformName];
                        }
                        if (($v['type'] == 'multiselect') || ($v['type'] == 'select')) {
                            $v['valueOptions'] = $client->call(
                                $session,
                                "product_attribute.options",
                                array(
                                    $v['attribute_id']
                                )
                            );
                        }
                        $attributeListArr[] = $v;
                    }
                }
            }

            $uploadItemArr = [];

            foreach ($attributeListArr as $ks => $vs) {
                //如果是多选的话
                if (($vs['type'] == 'select') || ($vs['type'] == 'multiselect')) { //如果是单选的话
                    foreach ($vs['valueOptions'] as $key => $val) {
                        //                        if ((strtolower($val['label'])) == strtolower($v['platformValue'])) {
                        //                            $uploadItemArr[$v['code']] = $val['value'];
                        //                        }
                        //比较属性值字符串是否相等
                        if (strcasecmp($val['label'], $vs['platformValue']) == 0) {
                            $uploadItemArr[$vs['code']] = $val['value'];
                            break;
                        }
                    }
                } else {
                    $uploadItemArr[$vs['code']] = $vs['platformValue'];
                }
            }

            //添加上传商品的信息
            $uploadItemArr['categories']            = array(2);
            $uploadItemArr['websites']              = array(1);
            $uploadItemArr['name']                  = $itemRow['name'];
            $uploadItemArr['description']           = 'Product description';
            $uploadItemArr['short_description']     = 'Product short description';
            $uploadItemArr['url_key']               = $itemPlatformRow['platform_sku'];
            $uploadItemArr['url_path']              = $itemRow['sku'];
            $uploadItemArr['true_sku']              = $itemRow['sku'];
            $uploadItemArr['status']                = '0';
            $uploadItemArr['visibility']            = '4';
            $uploadItemArr['meta_title']            = 'Product meta title';
            $uploadItemArr['meta_keyword']          = 'Product meta keyword';
            $uploadItemArr['meta_description']      = 'Product meta description';
            if ($itemPlatformRow['magento_id'] > 0) { //更新商品
                try {
                    $result = $client->call($session, 'catalog_product.update', array($itemRow['sku'], $uploadItemArr));
                    if ($result) {
                        $where['id'] = $itemPlatformRow['id'];
                        $data['is_upload'] = 1;
                        $categoryRowRs = $this->model->isUpdate(true, $where)->save($data);
                        if ($categoryRowRs) {
                            $this->success('更改成功');
                        } else {
                            $this->error('Update failed. Please try again');
                        }
                    }
                } catch (\SoapFault $e) {
                    $this->error($e->getMessage());
                }
            } else { //添加商品
                try {
                    $result = $client->call($session, 'catalog_product.create', array($itemPlatformRow['item_type'], $attributeSet['set_id'], $itemRow['sku'], $uploadItemArr));
                    if ($result) {
                        $where['id'] = $itemPlatformRow['id'];
                        $data['magento_id'] = $result;
                        $data['is_upload'] = 1;
                        $categoryRowRs = $this->model->isUpdate(true, $where)->save($data);
                        if ($categoryRowRs) {
                            $this->success('上传成功');
                        } else {
                            $this->error('Update failed. Please try again');
                        }
                    }
                } catch (\SoapFault $e) {
                    $this->error($e->getMessage());
                }
            }
        } else {
            $this->error('404 Not found');
        }
    }

    /***
     * 上传商品图片到对应的平台
     */
    public function uploadImagesToPlatform($ids = null)
    {
        if ($this->request->isAjax()) {
            // if(count($ids)>1){ //一次只能上传一个商品
            //     $this->error(__('Multiple data updates are not currently supported, please update one at a time'));
            // }
            $itemPlatformRow = $this->model->findItemPlatform($ids);
            if (!$itemPlatformRow) { //对应商品不正确或者平台不正确
                $this->error(__('Incorrect product or incorrect platform'));
            }
            if ($itemPlatformRow['is_upload_item'] == 2) { //控制不上传商品信息
                $this->error(__('The corresponding platform does not need to upload product information, please do not upload'));
            }
            if (!$itemPlatformRow['magento_url']) {
                $this->error(__('The platform url does not exist. Please go to edit it and it cannot be empty'));
            }
            if (empty($itemPlatformRow['magento_id'])) {
                $this->error(__('The corresponding product Id does not exist, please upload the product to the platform first'));
            }
            //            if($itemPlatformRow['is_upload_images'] == 1){ //商品图片已经上传，无需再次上传
            //                $this->error(__('The product has been uploaded, there is no need to upload again'));
            //            }
            if (empty($itemPlatformRow['item_attr_name']) || empty($itemPlatformRow['item_type'])) { //平台商品类型和商品属性
                $this->error(__('The product attributes or product types of the platform are not filled in'));
            }
            $itemImagesRow = (new Item())->getItemImagesRow($itemPlatformRow['sku']);
            if ($itemImagesRow['item_status'] != 3) { //该商品没有审核通过
                $this->error(__('This product has not been approved and cannot be uploaded'));
            }
            if (empty($itemImagesRow['frame_images'])) {
                $this->error(__('No pictures of the goods have been uploaded. Please upload them'));
            }

            //需要上传的图片
            $itemImagesArr = explode(',', $itemImagesRow['frame_images']);
            $magentoUrl = $itemPlatformRow['magento_url'];
            try {
                $client = new \SoapClient($magentoUrl . '/api/soap/?wsdl');
                $session = $client->login($itemPlatformRow['magento_account'], $itemPlatformRow['magento_key']);
                //如果存在需要删除的图片就删除magento平台上的照片
                if ($itemPlatformRow['uploaded_images']) {
                    $itemImageDelArr = explode(',', $itemPlatformRow['uploaded_images']);
                    if (!empty($itemImageDelArr)) {
                        //需要删除的图片
                        foreach ($itemImageDelArr as $kDel => $vDel) {
                            $client->call($session, 'catalog_product_attribute_media.remove', array('product' => $itemPlatformRow['magento_id'], 'file' => $vDel));
                        }
                    }
                }
                //添加图片到magento平台
                foreach ($itemImagesArr as $k => $v) { //循环照片
                    $file = array(
                        'content' => base64_encode(file_get_contents('./' . $v)),
                        'mime' => 'image/jpeg'
                    );
                    $result[] = $client->call(
                        $session,
                        'catalog_product_attribute_media.create',
                        [
                            $itemPlatformRow['magento_id'],
                            ['file' => $file, 'label' => 'Label', 'position' => '1', 'types' => ['thumbnail'], 'exclude' => 0]
                        ]
                    );
                }
                $client->endSession($session);
            } catch (\SoapFault $e) {
                $this->error($e->getMessage());
                //$this->error(__('Platform account or key is incorrect, please go to the platform to edit'));
            } catch (\Exception $e) {
                $this->error($e->getMessage());
                //$this->error(__('An error has occurred. Please contact the developer'));
            }
            if (is_array($result) && count($result) >= 1) {
                $where['id'] = $itemPlatformRow['id'];
                $data['is_upload_images'] = 1;
                $data['uploaded_images'] = implode(',', $result);
                $updateRow = $this->model->isUpdate(true, $where)->save($data);
                if ($updateRow) {
                    $this->success(__('upload successful'));
                } else {
                    $this->error(__('upload error'));
                }
            } else {
                $this->error(__('upload error'));
            }
        } else {
            $this->error('404 Not found');
        }
    }
    /***
     * 检测平台sku是否存在并且监测库存
     */
    public function checkPlatformSkuAndQty()
    {
        if ($this->request->isAjax()) {
            $change_sku = $this->request->param('change_sku');
            $change_number = $this->request->param('change_number');
            $order_platform = $this->request->param('order_platform');
            if (!$change_sku) {
                return $this->error('请先填写商品sku');
            }
            if (!$order_platform) {
                return $this->error('请选择订单平台');
            }
            if ($change_number < 1) {
                return $this->error('变更数量不能小于1');
            }
            $result = $this->model->check_platform_sku_qty($change_sku, $order_platform);
            if (!$result) {
                return $this->error('填写的sku不存在,请重新填写');
            }
            if ($result['available_stock'] < $change_number) {
                return $this->error('镜架可用数量大于可用库存数量,无法更改镜架');
            }
            return $this->success();
        } else {
            $this->error('404 Not found');
        }
    }
    public function ceshi()
    {
        $where['platform_type'] = 4;
        $result = Db::connect('database.db_stock')->name('item_platform_sku')->where($where)->select();
        $info   = Db::connect('database.db_stock')->name('item_platform_sku_bak')->insertAll($result);
        if ($info) {
            echo 'ok';
        } else {
            echo 'error';
        }
    }
}
