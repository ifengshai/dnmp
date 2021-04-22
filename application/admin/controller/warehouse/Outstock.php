<?php

namespace app\admin\controller\warehouse;

use app\admin\model\warehouse\ProductBarCodeItem;
use app\common\controller\Backend;
use app\enum\PlatformType;
use fast\Excel;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Reader\Csv;
use PhpOffice\PhpSpreadsheet\Reader\Xls;
use PhpOffice\PhpSpreadsheet\Reader\Xlsx;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use think\Db;
use think\Exception;
use think\exception\PDOException;
use think\exception\ValidateException;
use app\admin\model\warehouse\OutStockLog;
use app\admin\model\StockLog;

/**
 * 出库单管理
 *
 * @icon fa fa-circle-o
 */
class Outstock extends Backend
{

    /**
     * 取消权限验证
     * @var string[]
     * @author crasphb
     * @date   2021/4/7 15:46
     */
    protected $noNeedRight = ['batch_export_xls'];
    /**
     * Outstock模型对象
     * @var \app\admin\model\warehouse\Outstock
     */
    protected $model = null;

    //当前是否为关联查询
    protected $relationSearch = true;

    public function _initialize()
    {
        parent::_initialize();
        $this->model = new \app\admin\model\warehouse\Outstock;
        $this->type = new \app\admin\model\warehouse\OutstockType;
        $this->item = new \app\admin\model\warehouse\OutStockItem;
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
        //设置过滤方法
        $this->request->filter(['strip_tags']);
        if ($this->request->isAjax()) {
            //如果发送的来源是Selectpage，则转发到Selectpage
            if ($this->request->request('keyField')) {
                return $this->selectpage();
            }

            //自定义sku搜索
            $filter = json_decode($this->request->get('filter'), true);
            if ($filter['sku']) {
                $smap['sku'] = ['like', '%' . $filter['sku'] . '%'];
                $ids = $this->item->where($smap)->column('out_stock_id');
                $map['outstock.id'] = ['in', $ids];
                unset($filter['sku']);
                $this->request->get(['filter' => json_encode($filter)]);
            }


            [$where, $sort, $order, $offset, $limit] = $this->buildparams();
            $total = $this->model
                ->with(['outstocktype'])
                ->where($where)
                ->where($map)
                ->order($sort, $order)
                ->count();

            $list = $this->model
                ->with(['outstocktype'])
                ->where($where)
                ->where($map)
                ->order($sort, $order)
                ->limit($offset, $limit)
                ->select();
            $list = collection($list)->toArray();
            foreach ($list as $key=>$item){
                $productBarcodeItem = new ProductBarCodeItem();
                $location =  $productBarcodeItem->where('out_stock_id',$item['id'])->order('id desc')->field('location_code,location_id')->find();
                $list[$key]['location_code'] = $location->location_code;
                $list[$key]['location_id'] = $location->location_id;
            }
            $result = array("total" => $total, "rows" => $list);

            return json($result);
        }
        return $this->view->fetch();
    }


    /**
     * 出库单批量导出
     */
    public function batch_export_xls()
    {
        set_time_limit(0);
        ini_set('memory_limit', '512M');

        $ids = input('id_params');
        if (!empty($ids)) {
            $item_map['s.out_stock_id'] = ['in', $ids];
        }
        $list = $this->model
            ->alias('o')
            ->join(['fa_out_stock_item' => 's'], 'o.id = s.out_stock_id')
            ->field('o.create_person,o.createtime,o.out_stock_number,s.sku,s.out_stock_num')
            ->order('o.id desc')
            ->where($item_map)
            ->select();
        $list = collection($list)->toArray();


        $spreadsheet = new Spreadsheet();

        //常规方式：利用setCellValue()填充数据
        $spreadsheet->setActiveSheetIndex(0)
            ->setCellValue("A1", "出库单号")
            ->setCellValue("B1", "SKU")
            ->setCellValue("C1", "出库数量")
            ->setCellValue("D1", "创建人")
            ->setCellValue("E1", "创建时间");


        // Rename worksheet
        $spreadsheet->setActiveSheetIndex(0)->setTitle('出库列表数据');


        foreach ($list as $key => $value) {
            $spreadsheet->getActiveSheet()->setCellValue("A" . ($key * 1 + 2), $value['out_stock_number']);
            $spreadsheet->getActiveSheet()->setCellValue("B" . ($key * 1 + 2), $value['sku']);
            $spreadsheet->getActiveSheet()->setCellValue("C" . ($key * 1 + 2), $value['out_stock_num']);
            $spreadsheet->getActiveSheet()->setCellValue("D" . ($key * 1 + 2), $value['create_person']);
            $spreadsheet->getActiveSheet()->setCellValue("E" . ($key * 1 + 2), $value['createtime']);
        }

        //设置宽度
        $spreadsheet->getActiveSheet()->getColumnDimension('A')->setWidth(32);
        $spreadsheet->getActiveSheet()->getColumnDimension('B')->setWidth(20);
        $spreadsheet->getActiveSheet()->getColumnDimension('C')->setWidth(20);
        $spreadsheet->getActiveSheet()->getColumnDimension('D')->setWidth(20);
        $spreadsheet->getActiveSheet()->getColumnDimension('E')->setWidth(20);

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
        // return exportExcel($spreadsheet, 'xls', '登陆日志');
        $format = 'xlsx';
        $savename = '出库单' . date("YmdHis", time());;
        // dump($spreadsheet);

        // if (!$spreadsheet) return false;
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
     * 添加
     */
    public function add()
    {
        if ($this->request->isPost()) {
            $params = $this->request->post("row/a");
            if ($params) {
                $params = $this->preExcludeFields($params);
                // dump($params);die;

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

                    $sku = $this->request->post("sku/a");
                    $out_stock_num = $this->request->post("out_stock_num/a");
                    // dump($sku);dump($out_stock_num);die;
                    if (count(array_filter($sku)) < 1) {
                        $this->error('sku不能为空！！');
                    }
                    if (count($sku) != count(array_unique($sku))) {
                        $this->error('请不要填写相同的sku');
                    }
                    foreach (array_filter($sku) as $k => $v) {
                        $item_platform_sku = new \app\admin\model\itemmanage\ItemPlatformSku();

                        $sku_platform = $item_platform_sku->where(['sku' => $v, 'platform_type' => $params['platform_id']])->find();
                        if (!$sku_platform) {
                            $this->error('此sku：' . $v . '没有同步至此平台，请先同步后重试');
                        }
                        if ($out_stock_num[$k] > $sku_platform['stock']) {
                            $this->error('sku：' . $v . '出库数量不能大于当前站点虚拟仓库存');
                        }
                    }

                    $params['create_person'] = session('admin.nickname');
                    $params['createtime'] = date('Y-m-d H:i:s', time());
                    $result = $this->model->allowField(true)->save($params);

                    //添加入库信息
                    if ($result !== false) {

                        $data = [];
                        foreach (array_filter($sku) as $k => $v) {
                            $data[$k]['sku'] = $v;
                            $data[$k]['out_stock_num'] = $out_stock_num[$k];
                            $data[$k]['out_stock_id'] = $this->model->id;
                        }
                        //批量添加
                        $this->item->allowField(true)->saveAll($data);
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
                    $this->success('添加成功！！', '', url('index'));
                } else {
                    $this->error(__('No rows were inserted'));
                }
            }
            $this->error(__('Parameter %s can not be empty', ''));
        }
        //查询出库分类
        $type = $this->type->where('is_del', 1)->select();
        $this->assign('type', $type);


        //质检单
        $outstock_number = 'OUT' . date('YmdHis') . rand(100, 999) . rand(100, 999);
        $this->assign('outstock_number', $outstock_number);
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

        //判断状态是否为新建
        if ($row['status'] > 0) {
            $this->error('只有新建状态才能编辑！！', url('index'));
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

                    $sku = $this->request->post("sku/a");
                    $out_stock_num = $this->request->post("out_stock_num/a");

                    if (count(array_filter($sku)) < 1) {
                        $this->error('sku不能为空！！');
                    }
                    if (count($sku) != count(array_unique($sku))) {
                        $this->error('请不要填写相同的sku');
                    }
                    foreach (array_filter($sku) as $k => $v) {
                        $item_platform_sku = new \app\admin\model\itemmanage\ItemPlatformSku();

                        $sku_platform = $item_platform_sku->where(['sku' => $v, 'platform_type' => $params['platform_id']])->find();
                        if (!$sku_platform) {
                            $this->error('此sku：' . $v . '没有同步至此平台，请先同步后重试');
                        }
                        if ($out_stock_num[$k] > $sku_platform['stock']) {
                            $this->error('sku：' . $v . '出库数量不能大于当前站点虚拟仓库存');
                        }
                    }
                    $result = $row->allowField(true)->save($params);

                    //修改产品
                    if ($result !== false) {
                        $item_id = $this->request->post("item_id/a");
                        $data = [];
                        foreach (array_filter($sku) as $k => $v) {
                            $data[$k]['sku'] = $v;
                            $data[$k]['out_stock_num'] = $out_stock_num[$k];
                            if (@$item_id[$k]) {
                                $data[$k]['id'] = $item_id[$k];
                            } else {
                                $data[$k]['out_stock_id'] = $ids;
                            }
                        }
                        //批量添加
                        $this->item->allowField(true)->saveAll($data);
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
                    $this->error(__('No rows were updated'));
                }
            }
            $this->error(__('Parameter %s can not be empty', ''));
        }

        //查询入库分类
        $type = $this->type->where('is_del', 1)->select();
        $this->assign('type', $type);


        /***********查询出库商品信息***************/
        //查询入库单商品信息
        $item_map['out_stock_id'] = $ids;
        $item = $this->item->where($item_map)->select();
        $this->iitem = new \app\admin\model\itemmanage\Item;
        $itemplatform = new \app\admin\model\itemmanage\ItemPlatformSku();
        //查询数据以显示在出库单编辑界面
        foreach ($item as $k => $v) {
            $res = $this->iitem->getGoodsInfo($item[$k]['sku']);
            $item[$k]['stock'] = $res['stock'];
            //名字
            $item[$k]['name'] = $res['name'];
            //实时库存
            $item[$k]['now_stock'] = $res['stock'] - $res['distribution_occupy_stock'];
            //可用库存
            $item[$k]['available_stock'] = $res['available_stock'];
            //占用库存
            $item[$k]['occupy_stock'] = $res['occupy_stock'];
            $info = $itemplatform->where(['sku' => $item[$k]['sku'], 'platform_type' => $row['platform_id']])->field('stock')->find();
            //虚拟仓库存
            $item[$k]['platform_stock'] = $info['stock'];
        }
        //
        //         dump(collection($row)->toArray());
        // dump(collection($item)->toArray());die;
        $this->assign('item', $item);
        $this->view->assign("row", $row);
        return $this->view->fetch();
    }

    /**
     * 编辑
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

        //查询入库分类
        $type = $this->type->where('is_del', 1)->select();

        $this->assign('type', $type);

        $barCodeItem = new ProductBarCodeItem();
        $ared = $barCodeItem->where('out_stock_id',$row->id)->field('location_id,location_code')->find();
        /***********查询出库商品信息***************/
        //查询入库单商品信息
        $item_map['out_stock_id'] = $ids;
        $item = $this->item->where($item_map)->select();
        $this->assign('item', $item);
        $this->view->assign("row", $row);
        $this->view->assign("ared", $ared);
        return $this->view->fetch();
    }

    //删除入库单里的商品信息
    public function deleteItem()
    {
        $id = input('id');
        $res = $this->item->destroy($id);
        if ($res) {
            $this->success();
        } else {
            $this->error();
        }
    }

    /**
     * 审核
     */
    public function setStatus()
    {

        $this->_inventory = new \app\admin\model\warehouse\Inventory();
        $this->_product_bar_code_item = new \app\admin\model\warehouse\ProductBarCodeItem();
        $ids = $this->request->post("ids/a");
        if (!$ids) {
            $this->error('缺少参数！！');
        }
        /*****************限制如果有盘点单未结束不能操作配货完成*******************/
        //配货完成时判断
        //拣货区盘点时不能操作
        //查询条形码库区库位
        $barcodedata = $this->_product_bar_code_item->where(['out_stock_id' => ['in', $ids]])->column('location_code');
        $count = $this->_inventory->alias('a')
            ->join(['fa_inventory_item' => 'b'], 'a.id=b.inventory_id')->where(['a.is_del' => 1, 'a.check_status' => ['in', [0, 1]], 'library_name' => ['in', $barcodedata]])
            ->count();
        if ($count > 0) {
            $this->error('此库位正在盘点,暂无法入库审核');
        }
        /****************************end*****************************************/

        $map['id'] = ['in', $ids];
        $row = $this->model->where($map)->select();
        foreach ($row as $v) {
            if ($v['status'] !== 1) {
                $this->error('只有待审核状态才能操作！！');
            }
        }

        //查询出库单商品信息
        $where['out_stock_id'] = ['in', $ids];
        $list = $this->item
            ->alias('a')
            ->join('fa_out_stock o', 'a.out_stock_id = o.id')
            ->where($where)
            ->select();
        $data['status'] = input('status');
        $platform = new \app\admin\model\itemmanage\ItemPlatformSku();
        if ($data['status'] == 2) {
            //批量审核出库 扣减sku的总数量不能大于当前sku的虚拟仓库存量
            $arr = [];
            foreach ($list as $k => $v) {
                if (!$arr[$v['sku']]) {
                    $arr[$v['sku']]['num'] = $v['out_stock_num'];
                    $arr[$v['sku']]['platform_type'] = $v['platform_id'];
                } else {
                    $arr[$v['sku']]['num'] = $v['out_stock_num'] + $arr[$v['sku']]['num'];
                }
            }
            foreach ($arr as $k => $v) {
                $item_platform_sku = $platform->where(['sku' => $k, 'platform_type' => $v['platform_type']])->find();
                if ($v['num'] > $item_platform_sku['stock']) {
                    $this->error('出库的数量大于sku:' . $k . '的虚拟仓库存，请检查后重试');
                }
            }
        }
        $res = $this->model->allowField(true)->isUpdate(true, $map)->save($data);
        $item = new \app\admin\model\itemmanage\Item;
        if ($res != false) {
            /**
             * @todo 审核通过扣减库存逻辑
             */ (new StockLog())->startTrans();
            $platform->startTrans();
            $item->startTrans();
            $this->_product_bar_code_item->startTrans();
            Db::startTrans();
            try {
                if ($data['status'] == 2) {

                    //出库扣减库存
                    $stock_data = [];
                    foreach ($list as $v) {
                        //扣除商品表商品总库存
                        //总库存

                        $item_map['sku'] = $v['sku'];
                        $sku_item = $item->where($item_map)->find();
                        $item_platform_sku = $platform->where(['sku' => $v['sku'], 'platform_type' => $v['platform_id']])->find();

                        $item->where($item_map)->dec('stock', $v['out_stock_num'])->dec('available_stock', $v['out_stock_num'])->update();
                        //直接扣减此平台sku的库存
                        $platform->where(['sku' => $v['sku'], 'platform_type' => $v['platform_id']])->dec('stock', $v['out_stock_num'])->update();

                        //插入日志表
                        (new StockLog())->setData([
                            //'大站点类型：1网站 2魔晶',
                            'type' => 2,
                            //'站点类型：1Zeelool  2Voogueme 3Nihao 4Meeloog 5Wesee 8Amazon 9Zeelool_es 10Zeelool_de 11Zeelool_jp'
                            'site' => $v['platform_id'],
                            //'模块：1普通订单 2配货 3质检 4审单 5异常处理 6更改镜架 7取消订单 8补发 9赠品 10采购入库 11出入库 12盘点 13调拨'
                            'modular' => 11,
                            //'变动类型：1非预售下单 2预售下单-虚拟仓>0 3预售下单-虚拟仓<0 4配货 5质检拒绝-镜架报损 6审单-成功 7审单-配错镜框
                            // 8加工异常打回待配货 9印logo异常打回待配货 10更改镜架-配镜架前 11更改镜架-配镜架后 12取消订单-配镜架前 13取消订单-配镜架后
                            // 14补发 15赠品 16采购-有比例入库 17采购-没有比例入库 18手动入库 19手动出库 20盘盈入库 21盘亏出库 22调拨 23调拨 24库存调拨'
                            'change_type' => 19,
                            // '关联sku'
                            'sku' => $v['sku'],
                            //'关联订单号或子单号'
                            'order_number' => $v['out_stock_number'],
                            //'关联变化的ID'
                            'public_id' => 0,
                            //'操作端：1PC端 2PDA'
                            'source' => 1,
                            //'总库存变动前'
                            'stock_before' => $sku_item['stock'],
                            //'总库存变化量：正数为加，负数为减'
                            'stock_change' => -$v['out_stock_num'],
                            //'可用库存变动前'
                            'available_stock_before' => $sku_item['available_stock'],
                            //'可用库存变化量：正数为加，负数为减'
                            'available_stock_change' => -$v['out_stock_num'],
                            // '虚拟仓库存变动前'
                            'fictitious_before' => $item_platform_sku['stock'],
                            // '虚拟仓库存变化量：正数为加，负数为减'
                            'fictitious_change' => -$v['out_stock_num'],
                            'create_person' => session('admin.nickname'),
                            'create_time' => time(),
                            //'关联单号类型：1订单号 2子订单号 3入库单 4出库单 5盘点单 6调拨单'
                            'number_type' => 4,
                        ]);

                        //排除盘点出库
                        if ($v['type_id'] != 1) {
                            //计算出库成本 
                            $financecost = new \app\admin\model\finance\FinanceCost();
                            $financecost->outstock_cost($v['out_stock_id'], $v['out_stock_number']);
                        }
                        //更改商品条形码子表在库状态
                        $this->_product_bar_code_item->where('out_stock_id',$v['id'])->update(['library_status'=>2]);

                    }
                } else {
                    //审核拒绝解除条形码绑定关系
                    $_product_bar_code_item = new ProductBarCodeItem();
                    $_product_bar_code_item
                        ->allowField(true)
                        ->isUpdate(true, ['out_stock_id' => ['in', $ids]])
                        ->save(['out_stock_id' => 0]);
                }

                Db::commit();
                (new StockLog())->commit();
                $platform->commit();
                $item->commit();
                $this->_product_bar_code_item->commit();
            } catch (ValidateException $e) {
                Db::rollback();
                (new StockLog())->rollback();
                $platform->rollback();
                $item->rollback();
                $this->_product_bar_code_item->rollback();
                $this->error($e->getMessage());
            } catch (PDOException $e) {
                Db::rollback();
                (new StockLog())->rollback();
                $platform->rollback();
                $item->rollback();
                $this->_product_bar_code_item->rollback();
                $this->error($e->getMessage());
            } catch (Exception $e) {
                Db::rollback();
                (new StockLog())->rollback();
                $platform->rollback();
                $item->rollback();
                $this->_product_bar_code_item->rollback();
                $this->error($e->getMessage());
            }

            $this->success();
        } else {
            $this->error('修改失败！！');
        }
    }

    /**
     * 取消
     */
    public function cancel($ids = null)
    {
        if (!$ids) {
            $this->error('缺少参数！！');
        }
        $row = $this->model->get($ids);
        if ($row['status'] !== 0) {
            $this->error('只有新建状态才能取消！！');
        }
        $map['id'] = ['in', $ids];
        $data['status'] = input('status');
        $res = $this->model->allowField(true)->isUpdate(true, $map)->save($data);
        if ($res) {
            $_product_bar_code_item = new ProductBarCodeItem();
            $_product_bar_code_item
                ->allowField(true)
                ->isUpdate(true, ['out_stock_id' => ['eq', $ids]])
                ->save(['out_stock_id' => 0,'location_code'=>'','location_id'=>'0','location_code_id'=>'0']);
            $this->success();
        } else {
            $this->error('取消失败！！');
        }
    }


    /***
     * 编辑之后提交审核
     */
    public function audit()
    {
        if ($this->request->isAjax()) {
            $id = $this->request->param('ids');
            $row = $this->model->get($id);
            if ($row['status'] != 0) {
                $this->error('此状态不能提交审核');
            }

            //查询入库明细数据
            $list = $this->item
                ->where(['out_stock_id' => ['in', $id]])
                ->select();
            $list = collection($list)->toArray();
            $skus = array_column($list, 'sku');

            //查询存在产品库的sku
            $item = new \app\admin\model\itemmanage\Item;
            $skus = $item->where(['sku' => ['in', $skus]])->column('sku');

            foreach ($list as $v) {
                if (!in_array($v['sku'], $skus)) {
                    $this->error('此sku:' . $v['sku'] . '不存在！！');
                }
            }
            $map['id'] = $id;
            $data['status'] = 1;
            $res = $this->model->allowField(true)->isUpdate(true, $map)->save($data);
            if ($res) {
                $this->success('提交审核成功');
            } else {
                $this->error('提交审核失败');
            }
        } else {
            $this->error('404 Not found');
        }
    }

    /***
     * 出库单成本核算 create@lsw
     */
    public function out_stock_order()
    {
        //设置过滤方法
        $this->request->filter(['strip_tags']);
        if ($this->request->isAjax()) {
            //如果发送的来源是Selectpage，则转发到Selectpage
            if ($this->request->request('keyField')) {
                return $this->selectpage();
            }
            [$where, $sort, $order, $offset, $limit] = $this->buildparams();
            $total = $this->model
                ->with(['outstocktype'])
                ->where(['status' => 2])
                ->where($where)
                ->order($sort, $order)
                ->count();

            $list = $this->model
                ->with(['outstocktype'])
                ->where(['status' => 2])
                ->where($where)
                ->order($sort, $order)
                ->limit($offset, $limit)
                ->select();
            $list = collection($list)->toArray();
            //总共的
            $totalId = $this->model
                ->with(['outstocktype'])
                ->where(['status' => 2])
                ->where($where)
                ->column('outstock.id');
            $totalPriceInfo = (new OutStockLog())->calculateMoneyAccordOutStock($totalId);
            // echo '<pre>';
            // var_dump($totalPriceInfo);
            //本页的
            $thisPageId = $this->model
                ->with(['outstocktype'])
                ->where(['status' => 2])
                ->where($where)
                ->order($sort, $order)
                ->limit($offset, $limit)
                ->column('outstock.id');
            $thisPagePriceInfo = (new OutStockLog())->calculateMoneyAccordThisPageId($thisPageId);
            if (0 != $thisPagePriceInfo) {
                foreach ($list as $keys => $vals) {
                    if (array_key_exists($vals['id'], $thisPagePriceInfo)) {
                        $list[$keys]['total_money'] = round($thisPagePriceInfo[$vals['id']], 2);
                    }
                }
            }
            $total_money = round($totalPriceInfo['total_money'], 2);
            $result = array("total" => $total, "rows" => $list, "totalPriceInfo" => $total_money);

            return json($result);
        }
        return $this->view->fetch();
    }

    /****
     * 出库单成本核算详情 create@lsw
     */
    public function out_stock_order_detail($ids = null)
    {
        $row = $this->model->get($ids, ['outstocktype']);
        if (!$row) {
            $this->error(__('No Results were found'));
        }
        $adminIds = $this->getDataLimitAdminIds();
        if (is_array($adminIds)) {
            if (!in_array($row[$this->dataLimitField], $adminIds)) {
                $this->error(__('You have no permission'));
            }
        }
        $item = (new OutStockLog())->getPurchaseItemInfo($ids);
        //查询入库分类
        $type = $this->type->select();
        $this->assign('type', $type);
        if ($item) {
            $this->assign('item', $item);
        }
        $this->assign("row", $row);
        return $this->view->fetch();
    }

    /**
     * 出库单批量导入
     *
     * Created by Phpstorm.
     * User: jhh
     * Date: 2020/9/24
     * Time: 15:11:52
     */
    public function import1()
    {
        $this->model = new \app\admin\model\warehouse\Outstock();
        $_item = new \app\admin\model\warehouse\OutStockItem();
        $_platform = new \app\admin\model\itemmanage\ItemPlatformSku();

        //校验参数空值
        $file = $this->request->request('file');
        !$file && $this->error(__('Parameter %s can not be empty', 'file'));

        //校验文件路径
        $filePath = ROOT_PATH . DS . 'public' . DS . $file;
        !is_file($filePath) && $this->error(__('No results were found'));

        //实例化reader
        $ext = pathinfo($filePath, PATHINFO_EXTENSION);
        !in_array($ext, ['csv', 'xls', 'xlsx']) && $this->error(__('Unknown data format'));
        if ('csv' === $ext) {
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
                if (0 == $n || preg_match('/^".*"$/', $line)) {
                    fwrite($fp, $line . "\n");
                } else {
                    fwrite($fp, '"' . str_replace(['"', ','], ['""', '","'], $line) . "\"\n");
                }
                $n++;
            }
            fclose($file) || fclose($fp);

            $reader = new Csv();
        } elseif ('xls' === $ext) {
            $reader = new Xls();
        } else {
            $reader = new Xlsx();
        }

        //模板文件列名
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
                    if (!empty($val)) {
                        $fields[] = $val;
                    }
                }
            }

            //校验模板文件格式
            // $listName = ['商品SKU', '类型', '补货需求数量'];
            $listName = ['出库分类', '平台', 'SKU', '出库数量'];

            $listName !== $fields && $this->error(__('模板文件格式错误！'));

            $data = [];
            for ($currentRow = 2; $currentRow <= $allRow; $currentRow++) {
                for ($currentColumn = 1; $currentColumn <= $maxColumnNumber; $currentColumn++) {
                    $val = $currentSheet->getCellByColumnAndRow($currentColumn, $currentRow)->getCalculatedValue();
                    $data[$currentRow - 2][$currentColumn - 1] = is_null($val) ? '' : $val;
                }
            }
            empty($data) && $this->error('表格数据为空！');

            //获取表格中sku集合
            $sku_arr = [];
            foreach ($data as $k => $v) {
                //获取sku
                $sku = trim($v[2]);
                empty($sku) && $this->error(__('导入失败,第 ' . ($k + 1) . ' 行SKU为空！'));
                $sku_arr[] = $sku;
            }
            //获取出库平台
            $out_plat = $data[0][1];
            switch (trim($out_plat)) {
                case 'zeelool':
                    $out_label = 1;
                    break;
                case 'voogueme':
                    $out_label = 2;
                    break;
                case 'nihao':
                    $out_label = 3;
                    break;
                case 'meeloog':
                    $out_label = 4;
                    break;
                case 'wesee':
                    $out_label = 5;
                    break;
                case 'amazon':
                    $out_label = 8;
                    break;
                case 'zeelool_es':
                    $out_label = 9;
                    break;
                // case 'zeelool_jp':
                //     $label = 1;
                case 'zeelool_de':
                    $out_label = 10;
                    break;
                default:
                    $this->error(__('请检查表格中调出仓的名称'));
            };
            $instock_type = Db::name('out_stock_type')->where('is_del', 1)->field('id,name')->select();
            $instock_type = array_column(collection($instock_type)->toArray(), 'id', 'name');

            //插入一条数据到入库单主表
            $transfer_order['out_stock_number'] = 'OUT' . date('YmdHis') . rand(100, 999) . rand(100, 999);
            $transfer_order['type_id'] = $instock_type[$data[0][0]];
            $transfer_order['status'] = 0;
            $transfer_order['platform_id'] = $out_label;
            $transfer_order['createtime'] = date('Y-m-d H:i:s');
            $transfer_order['create_person'] = session('admin.nickname');
            $transfer_order_id = $this->model->insertGetId($transfer_order);

            //批量导入
            $params = [];
            foreach ($data as $v) {
                //获取sku
                $sku = trim($v[2]);

                $sku_plat = $_platform->where(['platform_type' => $out_label, 'sku' => $sku])->find();
                //校验当前平台是否存在此sku映射关系
                if (empty($sku_plat)) {
                    $this->model->where('id', $transfer_order_id)->delete() && $this->error(__('导入失败,商品 ' . $sku . '在' . $out_plat . ' 平台没有映射关系！'));
                }

                //校验sku是否重复
                isset($params[$sku]) && $this->model->where('id', $transfer_order_id)->delete() && $this->error(__('导入失败,商品 ' . $sku . ' 重复！'));

                //获取出库数量
                $replenish_num = (int)$v[3];
                empty($replenish_num) && $this->model->where('id', $transfer_order_id)->delete() && $this->error(__('导入失败,商品 ' . $sku . ' 出库库数量不能为空！'));


                //校验出库数量是否大于当前虚拟仓库存量
                if ($replenish_num > $sku_plat['stock']) {
                    $this->model->where('id', $transfer_order_id)->delete() && $this->error(__('导入失败,商品 ' . $sku . ' 出库数量大于当前虚拟仓库库存！'));
                }

                //拼接参数 插入出库单详情表中
                $params[$sku] = [
                    'out_stock_num' => $replenish_num,
                    'sku' => $sku,
                    'out_stock_id' => $transfer_order_id,
                ];
            }

            $_item->allowField(true)->saveAll($params) ? $this->success('导入成功！') : $this->error('导入失败！');
        } catch (Exception $e) {
            $this->error($e->getMessage());
        }
    }

    public function import()
    {
        set_time_limit(0);
        ini_set('memory_limit', '512M');

        $this->model = new \app\admin\model\warehouse\Outstock();
        $_item = new \app\admin\model\warehouse\OutStockItem();
        $_platform = new \app\admin\model\itemmanage\ItemPlatformSku();
        $_product_bar_code_item = new \app\admin\model\warehouse\ProductBarCodeItem();

        //校验参数空值
        $file = $this->request->request('file');
        !$file && $this->error(__('Parameter %s can not be empty', 'file'));

        //校验文件路径
        $filePath = ROOT_PATH . DS . 'public' . DS . $file;
        !is_file($filePath) && $this->error(__('No results were found'));

        //实例化reader
        $ext = pathinfo($filePath, PATHINFO_EXTENSION);
        !in_array($ext, ['csv', 'xls', 'xlsx']) && $this->error(__('Unknown data format'));
        if ('csv' === $ext) {
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
                if (0 == $n || preg_match('/^".*"$/', $line)) {
                    fwrite($fp, $line . "\n");
                } else {
                    fwrite($fp, '"' . str_replace(['"', ','], ['""', '","'], $line) . "\"\n");
                }
                $n++;
            }
            fclose($file) || fclose($fp);

            $reader = new Csv();
        } elseif ('xls' === $ext) {
            $reader = new Xls();
        } else {
            $reader = new Xlsx();
        }
        $resultMsg=array();
        //模板文件列名
        $this->model->startTrans();
        $_item->startTrans();
        $_product_bar_code_item->startTrans();
        //模板文件列名
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
                    if (!empty($val)) {
                        $fields[] = $val;
                    }
                }
            }

            //校验模板文件格式
            $listName = ['出库分类', '平台', '商品条码'];

            $listName !== $fields && $this->error(__('模板文件格式错误！'));

            $data = [];
            for ($currentRow = 2; $currentRow <= $allRow; $currentRow++) {
                for ($currentColumn = 1; $currentColumn <= $maxColumnNumber; $currentColumn++) {
                    $val = $currentSheet->getCellByColumnAndRow($currentColumn, $currentRow)->getFormattedValue();
                    $data[$currentRow - 2][$currentColumn - 1] = is_null($val) ? '' : trim($val);
                }
            }
            empty($data) && $this->error('表格数据为空！');

            //获取表格中条码集合
            $skuArr = [];
            $siteType = [];
            foreach ($data as $k => $v) {
                //获取sku
                $sku = trim($v[2]);
                $site = trim($v[1]);
                $outType = trim($v[0]);
                $cherckOutType[$v[0]] = trim($v[0]);
                $siteType[$v[1]] = trim($v[1]);
                empty($sku) && $this->error(__('导入失败,第 ' . ($k + 1) . ' 行商品条码为空！'));
                empty($site) && $this->error(__('导入失败,第 ' . ($k + 1) . ' 行平台类型为空！'));
                empty($outType) && $this->error(__('导入失败,第 ' . ($k + 1) . ' 出库类型为空！'));
                $skuArr[] = $sku;
            }

            $skuCode = array_column($data, '2');
            //检测条形码是否重复
            if (count($data) != count(array_unique($skuCode))) $this->error(__(' 条形码有重复，请检查'));
            if (count($siteType) > 1) $this->error("一次只能导入一个平台类型");
            if (count($cherckOutType) > 1) $this->error("一次只能导入一种出库单类型");
            $where['code'] = ['in', $skuCode];
            $selectlist = $_product_bar_code_item->where($where)->select();
            $selectlist = collection($selectlist)->toArray();

            $databaseData = array_column($selectlist, 'code');
            foreach ($data as $check_k => $check_v) {
                if (!in_array($check_v[2], $databaseData)) {
                    $this->error('条码[' . $check_v[2] . ']不存在');
                }
            }


            $insertOutStoce=array();
            //数据库中条码信息进行验证与数据拼装   如果验证不通过则记录原因
            foreach ($selectlist as $k => $v) {
                if ($v['library_status'] == 2) {
                    $this->error(__('条码[' . $v['code'] . ']已出库'));
                }
                if ($v['out_stock_id']) {
                    $this->error(__('条码[' . $v['code'] . ']已存在出库单,请检查出库单' . $v['out_stock_id']));
                }
                if (!$v['location_id']) {
                    $this->error(__('条码[' . $v['code'] . ']未绑定库区'));
                }
                if (!$v['location_code_id']) {
                    $this->error(__('条码[' . $v['code'] . ']未绑定库位'));
                }
                $un_key = $v['location_id'] . $v['location_code_id'];
                $insertOutStoce[$un_key][$v['code']] = $v['id'];

                $insertOutStoce[$un_key]['sku'][$v['sku']][$k]=1;
            }
            $outPlat = $data[0][1];
            switch (trim($outPlat)) {
                case PlatformType::ZEELOOL:
                    $outLabel = 1;
                    break;
                case PlatformType::VOOGUEME:
                    $outLabel = 2;
                    break;
                case PlatformType::NIHAO:
                    $outLabel = 3;
                    break;
                case PlatformType::MEELOOG:
                    $outLabel = 4;
                    break;
                case PlatformType::WESEE:
                    $outLabel = 5;
                    break;
                case PlatformType::AMAZON:
                    $outLabel = 8;
                    break;
                case PlatformType::ZEELOOL_ES:
                    $outLabel = 9;
                    break;
                case PlatformType::ZEELOOL_DE:
                    $outLabel = 10;
                    break;
                case PlatformType::ZEELOOL_JP:
                    $outLabel = 11;
                    break;
                case PlatformType::VOOGMECHIC:
                    $outLabel = 12;
                    break;
                case PlatformType::ZEELOOL_CN:
                    $outLabel = 13;
                    break;
                case PlatformType::ALIBABA:
                    $outLabel = 14;
                    break;
                case PlatformType::ZEELOOL_FR:
                    $outLabel = 15;
                    break;
                default:
                    $this->error(__('请检查表格中调出仓的名称'));
            };
            $instockType = Db::name('out_stock_type')->where('is_del', 1)->field('id,name')->select();
            $instockType = array_column(collection($instockType)->toArray(), 'id', 'name');

            $params=array();
            foreach ($insertOutStoce as $inset_k => $insert_v) {
                $outStock['out_stock_number'] = 'OUT' . date('YmdHis') . rand(100, 999) . rand(100, 999);
                $outStock['type_id'] = $instockType[$data[0][0]];
                $outStock['status'] = 0;
                $outStock['platform_id'] = $outLabel;
                $outStock['createtime'] = date('Y-m-d H:i:s');
                $outStock['create_person'] = session('admin.nickname');
                $outStockId = $this->model->insertGetId($outStock);
                foreach ($insert_v as $item => $value) {
                    if (is_array($value)) {
                        foreach ($value as $sku_k=>$sku_v){
                            $params[$sku_k] = [
                                'out_stock_num' => count($sku_v),
                                'sku' => $sku_k,
                                'out_stock_id' => $outStockId,
                            ];
                        }
                    } else {
                        $_product_bar_code_item->where(['id' => trim($value)])->update(['out_stock_id' => $outStockId]);
                    }
                }
            }
            if ($params){
                $_item->allowField(true)->saveAll($params);
            }
            $this->model->commit();
            $_item->commit();
            $_product_bar_code_item->commit();
        } catch (ValidateException $e) {
            $this->model->rollback();
            $_item->rollback();
            $_product_bar_code_item->rollback();
            $this->error($e->getMessage());
        } catch (PDOException $e) {
            $this->model->rollback();
            $_item->rollback();
            $_product_bar_code_item->rollback();
            $this->error($e->getMessage());
        } catch (Exception $e) {
            $this->model->rollback();
            $_item->rollback();
            $_product_bar_code_item->rollback();
            $this->error($e->getMessage());
        }
        if ($resultMsg){
        $savename = '/uploads/批量出库剩余数据' . date("YmdHis", time());
        $this->writeCsv($resultMsg,array('code','msg'),$savename,false);
        return json(['msg' => "uploads",'code'=>1,'url' => "https://".$_SERVER['HTTP_HOST']."/".$savename.".csv"]);
        }else{
            $this->success("导入成功");
        }
    }

    public static function writeCsv($data = array(), $headlist = array(), $fileName, $export = false)
    {
        if ($export) {
            header('Content-Type: application/vnd.ms-excel');
            header('Content-Disposition: attachment;filename="' . $fileName . '.csv"');
            header('Cache-Control: max-age=0');

            //打开PHP文件句柄,php://output 表示直接输出到浏览器
            $fp = fopen('php://output', 'a');
        } else {
            $fp = fopen('.' . $fileName . '.csv', 'a');
        }
        //输出Excel列名信息
        foreach ($headlist as $key => $value) {
            //CSV的Excel支持GBK编码，一定要转换，否则乱码
            $headlist[$key] = iconv('utf-8', 'gbk', $value);
        }
        //将数据通过fputcsv写到文件句柄
        fputcsv($fp, $headlist);

        //计数器
        $num = 0;

        //每隔$limit行，刷新一下输出buffer，不要太大，也不要太小
        $limit = 100000;

        //逐行取出数据，不浪费内存
        $count = count($data); //print_r($data);die;
        for ($i = 0; $i < $count; $i++) {
            $num++;
            //刷新一下输出buffer，防止由于数据过多造成问题
            if ($limit == $num) {
                ob_flush();
                flush();
                $num = 0;
            }

            $row = $data[$i];
            foreach ($row as $key => $value) {
                $row[$key] = iconv('utf-8', 'gbk', $value);
            }
            fputcsv($fp, $row);
        }
        if (!$export) {
            return $fileName . '.csv';
        }
    }

}
