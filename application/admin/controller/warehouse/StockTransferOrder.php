<?php

namespace app\admin\controller\warehouse;

use app\admin\model\AuthGroup;
use app\admin\model\itemmanage\Item;
use app\admin\model\itemmanage\ItemPlatformSku;
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
use think\Loader;
use think\Model;

/**
 * 库位管理
 *
 * @icon fa fa-circle-o
 */
class StockTransferOrder extends Backend
{
    
    /**
     * StockHouse模型对象
     * @var \app\admin\model\warehouse\StockHouse
     */
    protected $model = null;

    /**
     * 无需鉴权的方法,但需要登录
     * @var array
     */
    protected $noNeedRight = ['print_label','getStockHouse','setStatus'];

    public function _initialize()
    {
        parent::_initialize();
        $this->model = new \app\admin\model\warehouse\StockTransferOrder();

    }
    
    /**
     * 默认生成的控制器所继承的父类中有index/add/edit/del/multi五个基础方法、destroy/restore/recyclebin三个回收站方法
     * 因此在当前控制器中可不用编写增删改查的代码,除非需要自己控制这部分逻辑
     * 需要将application/admin/library/traits/Backend.php中对应的方法复制到当前控制器,然后进行修改
     */

    /**
     * 实体仓调拨单列表
     * Interface index
     * @package app\admin\controller\warehouse
     * @author  jhh
     * @date    2021/5/18 9:16:42
     */
    public function index()
    {
        //设置过滤方法
        $this->request->filter(['strip_tags']);
        if ($this->request->isAjax()) {
            if ($this->request->request('keyField')) {
                return $this->selectpage();
            }
            $map = [];
            //自定义sku搜索
            $filter = json_decode($this->request->get('filter'), true);
            if ($filter['sku']) {
                $allIds = Db::name('stock_transfer_order_item')->where('sku','like','%'.$filter['sku'].'%')->group('transfer_order_id')->column('transfer_order_id');
                $map['id'] = ['in',$allIds];
                unset($filter['sku']);
                $this->request->get(['filter' => json_encode($filter)]);
            }

            [$where, $sort, $order, $offset, $limit] = $this->buildparams();
            $total = $this->model
                ->where($where)
                ->where($map)
                ->order($sort, $order)
                ->count();

            $list = $this->model
                ->where($where)
                ->where($map)
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
     * 添加
     */
    public function add()
    {
        if ($this->request->isPost()) {
            $params = $this->request->post("row/a");
            $sku = $this->request->post("sku/a");
            $num = $this->request->post("num/a");
            if ($params['out_stock_id'] == $params['in_stock_id']) {
                $this->error('调入仓和调出仓不能为同一个站');
            }
            if (empty($params['response_person'])) {
                $this->error('调拨负责人不能为空');
            }
            $platSku = new Item();
            //添加调拨单保存或提交审核时数据有效性的判断
            foreach ($sku as $k => $v) {
                if (empty($v)) {
                    $this->error('sku不能为空');
                }
                $isExistSku = $platSku->where('sku',$v)->find();
                if (!$isExistSku){
                    $this->error('sku'.$v.'不存在，请检查');
                }
                if ($num[$k] <= 0 || empty($num[$k])) {
                    $this->error('期望调拨数量不能为0，请确认' . $v . '期望调拨数量');
                }
            }
            if (count($sku) != count(array_unique($sku))) {
                $this->error('当前调拨单中存在相同的sku，请检查后重试');
            }

            if ($params) {
                $result = false;
                Db::startTrans();
                try {
                    $params['create_time'] = time();
                    $params['create_person'] = session('admin.nickname');
                    $result = Db::name('stock_transfer_order')->insertGetId($params);
                    if (false !== $result) {
                        $data = [];
                        foreach (array_filter($sku) as $k => $v) {
                            $data[$k]['sku'] = trim($v);
                            $data[$k]['hope_num'] = trim($num[$k]);
                            $data[$k]['real_num'] = 0;
                            $data[$k]['transfer_order_id'] = $result;
                        }
                        //批量添加
                        $result = Db::name('stock_transfer_order_item')->insertAll($data);
                    }
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

        $allStock = Db::name('warehouse_stock')->column('name','id');
        //调拨单号
        $transferOrderNumber = 'TO' . date('YmdHis') . rand(100, 999) . rand(100, 999);
        $this->assign('transfer_order_number', $transferOrderNumber);
        $this->assign('all_stock', $allStock);
        $allTerm = AuthGroup::getAllNextGroup(135);
        $allPersonIds = Db::name('auth_group_access')->where('group_id','in',$allTerm)->column('uid');
        $allPerson1 = Db::name('admin')->where('id','in',$allPersonIds)->column('nickname');
        foreach ($allPerson1 as $k=>$v){
            $allPerson[$v] = $v;
        }
        $this->assign('all_person', $allPerson);
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
        if ($this->request->isPost()) {
            $params = $this->request->post("row/a");
            $sku = $this->request->post("sku/a");
            $num = $this->request->post("num/a");
            if ($params['out_stock_id'] == $params['in_stock_id']) {
                $this->error('调入仓和调出仓不能为同一个站');
            }
            if (empty($params['response_person'])) {
                $this->error('调拨负责人不能为空');
            }
            $platSku = new Item();
            //添加调拨单保存或提交审核时数据有效性的判断
            foreach ($sku as $k => $v) {
                if (empty($v)) {
                    $this->error('sku不能为空');
                }
                $isExistSku = $platSku->where('sku',$v)->find();
                if (!$isExistSku){
                    $this->error('sku'.$v.'不存在，请检查');
                }
                if ($num[$k] <= 0 || empty($num[$k])) {
                    $this->error('期望调拨数量不能为0，请确认' . $v . '期望调拨数量');
                }
            }
            if (count($sku) != count(array_unique($sku))) {
                $this->error('当前调拨单中存在相同的sku，请检查后重试');
            }
            if ($params) {
                $result = false;
                Db::startTrans();
                try {
                    $params['create_time'] = time();
                    $params['create_person'] = session('admin.nickname');
                    $result = Db::name('stock_transfer_order')->where('id',$ids)->update($params);
                    Db::name('stock_transfer_order_item')->where('transfer_order_id',$ids)->delete();
                    if (false !== $result) {
                        $data = [];
                        foreach (array_filter($sku) as $k => $v) {
                            $data[$k]['sku'] = trim($v);
                            $data[$k]['hope_num'] = trim($num[$k]);
                            $data[$k]['real_num'] = 0;
                            $data[$k]['transfer_order_id'] = $ids;
                        }
                        //批量添加
                        $result = Db::name('stock_transfer_order_item')->insertAll($data);
                    }
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
        $this->view->assign("row", $row);

        //查询产品信息
        $map['transfer_order_id'] = $ids;
        $item = Db::name('stock_transfer_order_item')->where($map)->select();
        $this->assign('item', $item);
        $allStock = Db::name('warehouse_stock')->column('name','id');
        $this->assign('all_stock', $allStock);
        $allTerm = AuthGroup::getAllNextGroup(135);
        $allPersonIds = Db::name('auth_group_access')->where('group_id','in',$allTerm)->column('uid');
        $allPerson1 = Db::name('admin')->where('id','in',$allPersonIds)->column('nickname');
        foreach ($allPerson1 as $k=>$v){
            $allPerson[$v] = $v;
        }
        $this->assign('all_person', $allPerson);
        return $this->view->fetch();
    }

    /**
     * 查看详情
     * Interface detail
     * @package app\admin\controller\warehouse
     * @author  jhh
     * @date    2021/5/18 14:41:50
     */
    public function detail($ids = null)
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
        //查询产品信息
        $map['transfer_order_id'] = $ids;
        $item = Db::name('stock_transfer_order_item')->where($map)->select();
        $this->assign('item', $item);
        $allStock = Db::name('warehouse_stock')->column('name','id');
        $this->assign('all_stock', $allStock);
        return $this->view->fetch();
    }
    /**
     * 审核
     */
    public function setStatus()
    {
        $ids = $this->request->post("ids/a");
        if (!$ids) {
            $this->error('缺少参数！！');
        }
        foreach ($ids as $k=>$v){
            $detail = Db::name('stock_transfer_order')->where('id',$v)->find();
            if ($detail['status'] !== 1){
                $this->error($detail['transfer_order_number'].'非待审核状态！！');
            }
        }
        $map['id'] = ['in', $ids];
        $data['status'] = input('status');
        $res = $this->model->allowField(true)->isUpdate(true, $map)->save($data);
        if ($res) {
            $this->success();
        } else {
            $this->error('修改失败！！');
        }
    }

    /**
     * 取消
     * Interface cancel
     * @package app\admin\controller\warehouse
     * @author  jhh
     * @date    2021/5/18 15:14:07
     */
    public function cancel()
    {
        if ($this->request->isAjax()) {
            $id = input('ids');
            $detail = Db::name('stock_transfer_order')->where('id',$id)->find();
            if ($detail['status'] == 0){
                $res = Db::name('stock_transfer_order')->where('id',$id)->update(['status'=>8]);
                if ($res) {
                    $this->success('取消成功');
                } else {
                    $this->error('取消失败');
                }
            }else{
                $this->error('非新建状态无法取消');
            }
        } else {
            $this->error('404 Not found');
        }
    }

    /**
     * 实体仓调拨单异常列表
     * Interface danger_list
     * @package app\admin\controller\warehouse
     * @author  jhh
     * @date    2021/5/21 9:57:06
     */
    public function danger_list()
    {
        //设置过滤方法
        $this->request->filter(['strip_tags']);
        if ($this->request->isAjax()) {
            //如果发送的来源是Selectpage，则转发到Selectpage
            if ($this->request->request('keyField')) {
                return $this->selectpage();
            }
            [$where, $sort, $order, $offset, $limit] = $this->buildparams();
            $allIds = Db::name('stock_transfer_order_item')
                ->where('real_instock_num','>',0)
                ->where('real_num','>',0)
                ->where('`real_instock_num`<`real_num`')
                ->group('transfer_order_id')
                ->column('transfer_order_id');
            $map['id'] = ['in',$allIds];
            $total = $this->model
                ->where($where)
                ->where($map)
                ->order($sort, $order)
                ->count();

            $list = $this->model
                ->where($where)
                ->where($map)
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
     * 异常详情
     * Interface danger_detail
     * @package app\admin\controller\warehouse
     * @author  jhh
     * @date    2021/5/21 10:28:53
     */
    public function danger_detail($ids = null)
    {
        $row = $this->model->get($ids);
        if (!$row) {
            $this->error(__('No Results were found'));
        }
        $this->view->assign("row", $row);
        //查询产品信息
        $map['transfer_order_id'] = $ids;
        $item = Db::name('stock_transfer_order_item')->where($map)->select();
        $this->assign('item', $item);
        $allStock = Db::name('warehouse_stock')->column('name','id');
        $this->assign('all_stock', $allStock);
        return $this->view->fetch();
    }

    /**
     * 实体仓调拨单批量导出
     * @throws \PhpOffice\PhpSpreadsheet\Exception
     * @throws \PhpOffice\PhpSpreadsheet\Writer\Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * @author jianghaohui
     * @date   2021/8/13 9:38:41
     */
    public function batch_export_xls()
    {
        set_time_limit(0);
        ini_set('memory_limit', '512M');
        $ids = input('id_params');
        if (!empty($ids)) {
            $item_map['s.id'] = ['in', $ids];
        }
        $map = [];
        //自定义sku搜索
        $filter = json_decode($this->request->get('filter'), true);
        if ($filter['sku']) {
            $allIds = Db::name('stock_transfer_order_item')->where('sku','like','%'.$filter['sku'].'%')->group('transfer_order_id')->column('transfer_order_id');
            $map['s.id'] = ['in',$allIds];
            unset($filter['sku']);
            $this->request->get(['filter' => json_encode($filter)]);
        }
        if ($filter['status']) {
            $map['s.status'] = ['=',$filter['status']];
            $this->request->get(['filter' => json_encode($filter)]);
        }
        if ($filter['id']) {
            $map['s.id'] = ['=',$filter['id']];
            $this->request->get(['filter' => json_encode($filter)]);
        }
        if ($filter['response_person']) {
            $map['s.response_person'] = ['like','%'.$filter['response_person'].'%'];
            $this->request->get(['filter' => json_encode($filter)]);
        }
        if ($filter['create_person']) {
            $map['s.create_person'] = ['like','%'.$filter['create_person'].'%'];
            $this->request->get(['filter' => json_encode($filter)]);
        }
        if ($filter['transfer_order_number']) {
            $map['s.transfer_order_number'] = ['like','%'.$filter['transfer_order_number'].'%'];
            $this->request->get(['filter' => json_encode($filter)]);
        }
        if ($filter['create_time']) {
            $createat = explode(' ', $filter['create_time']);
            $map['s.create_time'] = ['between', [strtotime($createat[0].$createat[1]), strtotime($createat[3].$createat[4])]];
            $this->request->get(['filter' => json_encode($filter)]);
        }
        $list = Db::name('stock_transfer_order_item')
            ->alias('o')
            ->join(['fa_stock_transfer_order' => 's'], 'o.transfer_order_id = s.id')
            ->field('o.sku,o.hope_num,o.real_num,o.real_instock_num,s.transfer_order_number,s.status,s.out_stock_id,s.in_stock_id')
            ->order('s.id desc')
            ->where($item_map)
            ->where($map)
            ->select();
        $list = collection($list)->toArray();
        $statusArr = ['新建','待审核','待配货','待物流揽收','待收货','待入库','已完成','审核拒绝','已取消'];
        $stockArr = [1=>'郑州仓',2=>'丹阳仓'];
        $spreadsheet = new Spreadsheet();
        //常规方式：利用setCellValue()填充数据
        $spreadsheet->setActiveSheetIndex(0)
            ->setCellValue("A1", "实体仓调拨单号")
            ->setCellValue("B1", "实体仓调拨单状态")
            ->setCellValue("C1", "调出仓")
            ->setCellValue("D1", "调入仓")
            ->setCellValue("E1", "调拨单中的SKU")
            ->setCellValue("F1", "期望调拨数量")
            ->setCellValue("G1", "实际调出数量")
            ->setCellValue("H1", "实际调入数量");
        // Rename worksheet
        $spreadsheet->setActiveSheetIndex(0)->setTitle('出库列表数据');
        foreach ($list as $key => $value) {
            $spreadsheet->getActiveSheet()->setCellValue("A" . ($key * 1 + 2), $value['transfer_order_number']);
            $spreadsheet->getActiveSheet()->setCellValue("B" . ($key * 1 + 2), $statusArr[$value['status']]);
            $spreadsheet->getActiveSheet()->setCellValue("C" . ($key * 1 + 2), $stockArr[$value['out_stock_id']]);
            $spreadsheet->getActiveSheet()->setCellValue("D" . ($key * 1 + 2), $stockArr[$value['in_stock_id']]);
            $spreadsheet->getActiveSheet()->setCellValue("E" . ($key * 1 + 2), $value['sku']);
            $spreadsheet->getActiveSheet()->setCellValue("F" . ($key * 1 + 2), $value['hope_num']);
            $spreadsheet->getActiveSheet()->setCellValue("G" . ($key * 1 + 2), $value['real_num']);
            $spreadsheet->getActiveSheet()->setCellValue("H" . ($key * 1 + 2), $value['real_instock_num']);
        }

        //设置宽度
        $spreadsheet->getActiveSheet()->getColumnDimension('A')->setWidth(32);
        $spreadsheet->getActiveSheet()->getColumnDimension('B')->setWidth(20);
        $spreadsheet->getActiveSheet()->getColumnDimension('C')->setWidth(20);
        $spreadsheet->getActiveSheet()->getColumnDimension('D')->setWidth(20);
        $spreadsheet->getActiveSheet()->getColumnDimension('E')->setWidth(20);
        $spreadsheet->getActiveSheet()->getColumnDimension('F')->setWidth(20);
        $spreadsheet->getActiveSheet()->getColumnDimension('G')->setWidth(20);
        $spreadsheet->getActiveSheet()->getColumnDimension('H')->setWidth(20);

        $spreadsheet->getDefaultStyle()->getFont()->setName('微软雅黑')->setSize(12);

        //自动换行
        $spreadsheet->getDefaultStyle()->getAlignment()->setWrapText(true);

        //设置边框
        $border = [
            'borders' => [
                'allBorders' => [
                    'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN, // 设置border样式
                    'color' => ['argb' => 'FF000000'], // 设置border颜色
                ],
            ],
        ];

        $setBorder = 'A1:' . $spreadsheet->getActiveSheet()->getHighestColumn() . $spreadsheet->getActiveSheet()->getHighestRow();
        $spreadsheet->getActiveSheet()->getStyle($setBorder)->applyFromArray($border);

        // $spreadsheet->getActiveSheet()->getStyle('A1:Z'.$key)->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
        $spreadsheet->getActiveSheet()->getStyle('A1:E' . $spreadsheet->getActiveSheet()->getHighestRow())->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
        $spreadsheet->getActiveSheet()->getStyle('A1:E' . $spreadsheet->getActiveSheet()->getHighestRow())->getAlignment()->setVertical(\PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER);

        // Set active sheet index to the first sheet, so Excel opens this as the first sheet
        $spreadsheet->setActiveSheetIndex(0);
        $format = 'xlsx';
        $savename = '实体仓调拨单导出数据';

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
