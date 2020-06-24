<?php

namespace app\admin\controller\order\printlabel;

use app\common\controller\Backend;

use think\Db;
use think\Loader;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use think\Exception;
use think\exception\PDOException;
use Util\RufooPrescriptionDetailHelper;
use Util\SKUHelper;
use app\admin\model\OrderLog;
use app\admin\model\WorkChangeSkuLog;

/**
 * Sales Flat Order
 *
 * @icon fa fa-circle-o
 */
class Rufoo extends Backend
{

    /**
     * Sales_flat_order模型对象
     * @var \app\admin\model\order\Sales_flat_order
     */
    protected $model = null;

    protected $searchFields = 'id';

    public function _initialize()
    {
        parent::_initialize();
        $this->model = new \app\admin\model\order\printlabel\Rufoo;
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

            $filter = json_decode($this->request->get('filter'), true);

            if (!$filter['ordersn'] && !$filter['status']) {
                $map['status'] = 1;
            }

            list($where, $sort, $order, $offset, $limit) = $this->buildparams();
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
     * 条形码扫码处理
     */
    public function _list()
    {
        //设置过滤方法
        $this->request->filter(['strip_tags']);
        if ($this->request->isAjax()) {
            //订单号
            $increment_id = $this->request->post('increment_id');
            if (!$increment_id) {
                return json([]);
            }
            $map['ordersn'] = $increment_id;
            $map['status'] = ['in', [1, 2, 3]];
            $list = $this->model->where($map)->find();
            $result = ['code' => 1, 'data' => $list ?? []];
            return json($result);
        }
        return $this->view->fetch('_list');
    }

    //订单详情
    public function detail($ids = null)
    {
        $row = $this->model->get($ids);
        if (!$row) {
            $this->error(__('No Results were found'));
        }
        //解析处方
        $result = RufooPrescriptionDetailHelper::get_one_by_entity_id($ids);
        $this->assign('result', $result);
        return $this->view->fetch();
    }

    //操作记录
    public function operational($ids = null)
    {
        $row = $this->model->get($ids);
        if (!$row) {
            $this->error(__('No Results were found'));
        }
        if ($this->request->isAjax()) {
            $ids = $this->request->get('ids');
            $row = $this->model->get($ids);
            $list = [
                [
                    'id' => 1,
                    'content' => '打标签',
                    'createtime' => $row['custom_print_label_created_at_new'],
                    'person' => $row['custom_print_label_person_new']
                ],
                [
                    'id' => 2,
                    'content' => '配镜架',
                    'createtime' => $row['custom_match_frame_created_at_new'],
                    'person' => $row['custom_match_frame_person_new']
                ],
                [
                    'id' => 3,
                    'content' => '配镜片',
                    'createtime' => $row['custom_match_lens_created_at_new'],
                    'person' => $row['custom_match_lens_person_new']
                ],
                [
                    'id' => 4,
                    'content' => '加工',
                    'createtime' => $row['custom_match_factory_created_at_new'],
                    'person' => $row['custom_match_factory_person_new']
                ],
                [
                    'id' => 5,
                    'content' => '质检',
                    'createtime' => $row['custom_match_delivery_created_at_new'],
                    'person' => $row['custom_match_delivery_person_new']
                ],
            ];
            $total = count($list);
            $result = array("total" => $total, "rows" => $list);
            return json($result);
        }
        $this->assignconfig('ids', $ids);
        return $this->view->fetch();
    }


    //标记为已打印标签
    public function tag_printed()
    {
        $entity_ids = input('id_params/a');
        $label = input('label');
        if ($entity_ids) {
            $map['id'] = ['in', $entity_ids];
            $data['custom_print_label_new'] = 1;
            $data['custom_print_label_created_at_new'] = date('Y-m-d H:i:s', time());
            $data['custom_print_label_person_new'] =  session('admin.nickname');
            $connect = $this->model;
            $connect->startTrans();
            try {
                $result = $connect->where($map)->update($data);
                $connect->commit();
            } catch (PDOException $e) {
                $connect->rollback();
                $this->error($e->getMessage());
            } catch (Exception $e) {
                $connect->rollback();
                $this->error($e->getMessage());
            }
            if (false !== $result) {
                //用来判断是否从_list列表页进来
                if ($label == 'list') {
                    //订单号
                    $map['id'] = ['in', $entity_ids];
                    $list = $this->model
                        ->where($map)
                        ->select();
                    $list = collection($list)->toArray();
                } else {
                    $list = 'success';
                }
                return $this->success('标记成功!', '', $list, 200);
            } else {
                return $this->error('失败', '', 'error', 0);
            }
        }
    }

    /**
     * 配镜架 配镜片 加工 质检通过
     */
    public function setOrderStatus()
    {
        $entity_ids = input('id_params/a');
        if (!$entity_ids) {
            $this->error('参数错误！！');
        }
        $status = input('status');
        $label = input('label');
        $where['a.id'] =  ['in', $entity_ids];
        $map['id'] = ['in', $entity_ids];
        $res = $this->model->alias('a')->field('ordersn,custom_is_match_frame_new,custom_is_delivery_new,custom_is_match_frame_new')->where($map)->select();
        if (!$res) {
            $this->error('未查询到订单数据！！');
        }
        foreach ($res as $v) {
            if ($status == 1 && $v['custom_is_match_frame_new'] == 1) {
                $this->error('存在已配过镜架的订单！！');
            }
            if ($v['custom_is_delivery_new'] == 1) {
                $this->error('存在已质检通过的订单！！');
            }

            if ($status == 4 && $v['custom_is_match_frame_new'] == 0) {
                $this->error('存在未配镜架的订单！！');
            }
        }

        switch ($status) {
            case 1:
                //配镜架
                $data['custom_is_match_frame_new'] = 1;
                $data['custom_match_frame_created_at_new'] = date('Y-m-d H:i:s', time());
                $data['custom_match_frame_person_new'] = session('admin.nickname');
                $params['type'] = 2;
                break;
            case 2:
                //配镜片
                $data['custom_is_match_lens_new'] = 1;
                $data['custom_match_lens_created_at_new'] = date('Y-m-d H:i:s', time());
                $data['custom_match_lens_person_new'] = session('admin.nickname');
                $params['type'] = 3;
                break;
            case 3:
                //移送加工
                $data['custom_is_send_factory_new'] = 1;
                $data['custom_match_factory_created_at_new'] = date('Y-m-d H:i:s', time());
                $data['custom_match_factory_person_new'] = session('admin.nickname');
                $params['type'] = 4;
                break;
            case 4:
                //质检通过
                $data['custom_is_delivery_new'] = 1;
                $data['custom_match_delivery_created_at_new'] = date('Y-m-d H:i:s', time());
                $data['custom_match_delivery_person_new'] = session('admin.nickname');
                $params['type'] = 5;
                break;
            default:
                $this->error('参数错误');
        }
        $item = new \app\admin\model\itemmanage\Item;
        $this->model->startTrans();
        $item->startTrans();
        try {
            $result = $this->model->where($map)->update($data);
            if (false === $result) {
                throw new Exception("操作失败！！");
            }
            //配镜架
            if ($status == 1) {
                //查询出订单数据
                $list = $this->model->field('sku,a.ordersn,b.total')->where($where)->alias('a')
                    ->join(['ims_ewei_shop_order_goods' => 'b'], 'a.id=b.orderid', 'left')
                    ->join(['ims_ewei_shop_goods' => 'c'], 'b.goodsid=c.id', 'left')
                    ->select();
                $list = collection($list)->toArray();
                if (!$list) {
                    throw new Exception("未查询到订单数据！！");
                };

                //查出订单SKU映射表对应的仓库SKU
                $number = 0;
                foreach ($list as $k => &$v) {
                    if (!$v['sku']) {
                        throw new Exception("增加配货占用库存失败！！请检查更换镜框SKU:" . $v['sku']);
                    }

                    $qty = $v['total'];

                    $map = [];
                    $map['sku'] = $v['sku'];
                    $map['is_del'] = 1;
                    //增加配货占用
                    $res = $item->where($map)->setInc('distribution_occupy_stock', $qty);
                    if (false === $res) {
                        throw new Exception("增加配货占用库存失败！！请检查更换镜框SKU:" . $v['sku']);
                    }

                    $number++;
                    //100条提交一次
                    if ($number == 100) {
                        $item->commit();
                        $number = 0;
                    }

                    //插入日志表
                    (new WorkChangeSkuLog())->setData([
                        'increment_id'            => $v['ordersn'],
                        'site'                     => 6, //如弗小程序
                        'type'                     => 5, //配镜架
                        'sku'                     => $v['sku'],
                        'distribution_change_num' => $qty,
                        'operation_person'        => session('admin.nickname'),
                        'create_time'             => date('Y-m-d H:i:s')
                    ]);
                }
                unset($v);
                $item->commit();
            }

            //质检通过扣减库存
            if ($status == 4) {
                //查询出质检通过的订单
                $list = $this->model->field('sku,a.ordersn,b.total')->where($where)->alias('a')
                    ->join(['ims_ewei_shop_order_goods' => 'b'], 'a.id=b.orderid', 'left')
                    ->join(['ims_ewei_shop_goods' => 'c'], 'b.goodsid=c.id', 'left')
                    ->select();
                $list = collection($list)->toArray();
                if (!$list) {
                    throw new Exception("未查询到订单数据！！");
                };
                $number = 0; //记录更新次数
                foreach ($list as &$v) {
                    if (!$v['sku']) {
                        throw new Exception("扣减库存失败！！请检查SKU:" . $v['sku']);
                    }
                    $qty = $v['total'];
                    if ($qty == 0) {
                        continue;
                    }

                    //总库存
                    $item_map['sku'] = $v['sku'];
                    $item_map['is_del'] = 1;
                    //扣减总库存 扣减占用库存 扣减配货占用
                    $res = $item->where($item_map)->dec('stock', $qty)->dec('occupy_stock', $qty)->dec('distribution_occupy_stock', $qty)->update();
                    if (false === $res) {
                        throw new Exception("扣减库存失败！！请检查SKU:" . $v['sku']);
                    }
                    $number++;
                    //100条提交一次
                    if ($number == 100) {
                        $item->commit();
                        $number = 0;
                    }
                    //插入日志表
                    (new WorkChangeSkuLog())->setData([
                        'increment_id'            => $v['ordersn'],
                        'site'                    => 6,
                        'type'                    => 6, //质检通过
                        'sku'                     => $v['sku'],
                        'stock_change_num'        => $qty,
                        'occupy_change_num'       => $qty,
                        'distribution_change_num' => $qty,
                        'operation_person'        => session('admin.nickname'),
                        'create_time'             => date('Y-m-d H:i:s')
                    ]);
                }
                unset($v);
                $item->commit();
            }

            $this->model->commit();
        } catch (PDOException $e) {
            $item->rollback();
            $this->model->rollback();
            $this->error($e->getMessage());
        } catch (Exception $e) {
            $item->rollback();
            $this->model->rollback();
            $this->error($e->getMessage());
        }
        if (false !== $result) {
            //用来判断是否从_list列表页进来
            if ($label == 'list') {
                //订单号
                $map = [];
                $map['id'] = ['in', $entity_ids];
                $list = $this->model
                    ->where($map)
                    ->select();
                $list = collection($list)->toArray();
            } else {
                $list = 'success';
            }
            return $this->success('操作成功!', '', $list, 200);
        } else {
            return $this->error('操作失败', '', 'error', 0);
        }
    }


    public function generate_barcode($text, $fileName)
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
        $drawException = null;
        try {
            // $code = new \BCGcode39();
            $code = new \BCGcode128();
            $code->setScale(3);
            $code->setThickness(25); // 条形码的厚度
            $code->setForegroundColor($color_black); // 条形码颜色
            $code->setBackgroundColor($color_white); // 空白间隙颜色
            $code->setFont($font); //设置字体
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

    //获取镜架尺寸
    protected function get_frame_lens_width_height_bridge($product_id)
    {
        if ($product_id) {
            $querySql = "select cpev.entity_type_id,cpev.attribute_id,cpev.`value`,cpev.entity_id
from catalog_product_entity_varchar cpev
LEFT JOIN catalog_product_entity cpe on cpe.entity_id=cpev.entity_id 
where cpev.attribute_id in(161,163,164) and cpev.store_id=0 and cpev.entity_id=$product_id";
            $resultList = Db::connect('database.db_meeloog')->query($querySql);
            if ($resultList) {
                $result = array();
                foreach ($resultList as $key => $value) {
                    if ($value['attribute_id'] == 161) {
                        $result['lens_width'] = $value['value'];
                    }
                    if ($value['attribute_id'] == 164) {
                        $result['lens_height'] = $value['value'];
                    }
                    if ($value['attribute_id'] == 163) {
                        $result['bridge'] = $value['value'];
                    }
                }
            } else {
                $result['lens_width'] = '';
                $result['lens_height'] = '';
                $result['bridge'] = '';
            }
        }
        return $result;
    }


    //批量导出xls
    public function batch_export_xls()
    {

        /*************修改为筛选导出****************/

        set_time_limit(0);
        ini_set('memory_limit', '512M');

        $ids = input('id_params');

        //默认状态为待发货
        $filter = json_decode($this->request->get('filter'), true);
        if (!$filter['status'] && !$ids && !$filter['ordersn']) {
            $map['a.status'] = 1;
        }

        if ($ids) {
            $map['a.id'] = ['in', $ids];
        }

        //SKU搜索
        if ($filter['sku']) {
            $map['sku'] = ['like', '%' . $filter['sku'] . '%'];
            unset($filter['sku']);
            $this->request->get(['filter' => json_encode($filter)]);
        }

        list($where) = $this->buildparams();

        $resultList = $this->model->field('a.status,sku,b.total,optionname,lens_data,a.createtime,ordersn')
            ->where($where)
            ->where($map)
            ->alias('a')
            ->join(['ims_ewei_shop_order_goods' => 'b'], 'a.id=b.orderid', 'left')
            ->join(['ims_ewei_shop_goods' => 'c'], 'b.goodsid=c.id', 'left')
            ->select();
        $resultList = collection($resultList)->toArray();
        $finalResult = array();
        foreach ($resultList as $key => $value) {
            $finalResult[$key]['increment_id'] = $value['ordersn'];
            $finalResult[$key]['sku'] = $value['sku'];
            $finalResult[$key]['created_at'] = date('Y-m-d', $value['createtime']);

            $tmp_lens_params = json_decode($value['lens_data'], true);
            $finalResult[$key]['index_type'] = $value['optionname'];
            $finalResult[$key]['prescription_type'] = isset($tmp_lens_params['type']) ? $tmp_lens_params['type'] : '';
            $finalResult[$key]['od_sph'] = isset($tmp_lens_params['od_sph']) ? $tmp_lens_params['od_sph'] : '';
            $finalResult[$key]['od_cyl'] = isset($tmp_lens_params['od_cyl']) ? $tmp_lens_params['od_cyl'] : '';
            $finalResult[$key]['od_axis'] = isset($tmp_lens_params['od_axis']) ? $tmp_lens_params['od_axis'] : '';
            $finalResult[$key]['od_add'] = isset($tmp_lens_params['od_add']) ? $tmp_lens_params['od_add'] : '';

            $finalResult[$key]['os_sph'] = isset($tmp_lens_params['os_sph']) ? $tmp_lens_params['os_sph'] : '';
            $finalResult[$key]['os_cyl'] = isset($tmp_lens_params['os_cyl']) ? $tmp_lens_params['os_cyl'] : '';
            $finalResult[$key]['os_axis'] = isset($tmp_lens_params['os_axis']) ? $tmp_lens_params['os_axis'] : '';
            $finalResult[$key]['os_add'] = isset($tmp_lens_params['os_add']) ? $tmp_lens_params['os_add'] : '';

            $finalResult[$key]['pd_r'] = isset($tmp_lens_params['pd_r']) ? $tmp_lens_params['pd_r'] : '';
            $finalResult[$key]['pd_l'] = isset($tmp_lens_params['pd_l']) ? $tmp_lens_params['pd_l'] : '';
            $finalResult[$key]['pd'] = isset($tmp_lens_params['pd']) ? $tmp_lens_params['pd'] : '';
            $finalResult[$key]['pdcheck'] = isset($tmp_lens_params['pdcheck']) ? $tmp_lens_params['pdcheck'] : '';

            //斜视值
            if (isset($tmp_lens_params['prismcheck']) && $tmp_lens_params['prismcheck'] == 'on') {
                $finalResult[$key]['od_bd'] = $tmp_lens_params['od_bd'];
                $finalResult[$key]['od_pv'] = $tmp_lens_params['od_pv'];
                $finalResult[$key]['os_pv'] = $tmp_lens_params['os_pv'];
                $finalResult[$key]['os_bd'] = $tmp_lens_params['os_bd'];

                $finalResult[$key]['od_pv_r'] = $tmp_lens_params['od_pv_r'];
                $finalResult[$key]['od_bd_r'] = $tmp_lens_params['od_bd_r'];
                $finalResult[$key]['os_pv_r'] = $tmp_lens_params['os_pv_r'];
                $finalResult[$key]['os_bd_r'] = $tmp_lens_params['os_bd_r'];
            }

            //用户留言
            $finalResult[$key]['information'] = isset($tmp_lens_params['information']) ? $tmp_lens_params['information'] : '';

            // $tmp_bridge = $this->get_frame_lens_width_height_bridge($value['product_id']);
            // $finalResult[$key]['lens_width'] = $tmp_bridge['lens_width'];
            // $finalResult[$key]['lens_height'] = $tmp_bridge['lens_height'];
            // $finalResult[$key]['bridge'] = $tmp_bridge['bridge'];
        }

        $spreadsheet = new Spreadsheet();
        //常规方式：利用setCellValue()填充数据
        $spreadsheet->setActiveSheetIndex(0)->setCellValue("A1", "日期")
            ->setCellValue("B1", "订单号")
            ->setCellValue("C1", "SKUID")
            ->setCellValue("D1", "SKU");   //利用setCellValues()填充数据
        $spreadsheet->setActiveSheetIndex(0)->setCellValue("E1", "眼球")
            ->setCellValue("F1", "SPH");
        $spreadsheet->setActiveSheetIndex(0)->setCellValue("G1", "CYL")
            ->setCellValue("H1", "AXI");
        $spreadsheet->setActiveSheetIndex(0)->setCellValue("I1", "ADD")
            ->setCellValue("J1", "单PD")
            ->setCellValue("K1", "PD");
        $spreadsheet->setActiveSheetIndex(0)->setCellValue("L1", "镜片")
            ->setCellValue("M1", "镜框宽度")
            ->setCellValue("N1", "镜框高度")
            ->setCellValue("O1", "bridge")
            ->setCellValue("P1", "处方类型")
            ->setCellValue("Q1", "顾客留言");
        $spreadsheet->setActiveSheetIndex(0)->setCellValue("R1", "Prism\n(out/in)")
            ->setCellValue("S1", "Direct\n(out/in)")
            ->setCellValue("T1", "Prism\n(up/down)")
            ->setCellValue("U1", "Direct\n(up/down)");
        // Rename worksheet
        $spreadsheet->setActiveSheetIndex(0)->setTitle('订单处方');

        foreach ($finalResult as $key => $value) {

            $value['prescription_type'] = isset($value['prescription_type']) ? $value['prescription_type'] : '';

            $value['od_sph'] = isset($value['od_sph']) ? $value['od_sph'] : '';
            $value['os_sph'] = isset($value['os_sph']) ? $value['os_sph'] : '';
            $value['od_cyl'] = isset($value['od_cyl']) ? $value['od_cyl'] : '';
            $value['os_cyl'] = isset($value['os_cyl']) ? $value['os_cyl'] : '';
            if (isset($value['od_axis']) && $value['od_axis'] !== 'None') {
                $value['od_axis'] =  $value['od_axis'];
            } else {
                $value['od_axis'] = '';
            }

            if (isset($value['os_axis']) && $value['os_axis'] !== 'None') {
                $value['os_axis'] =  $value['os_axis'];
            } else {
                $value['os_axis'] = '';
            }

            $value['od_add'] = isset($value['od_add']) ? $value['od_add'] : '';
            $value['os_add'] = isset($value['os_add']) ? $value['os_add'] : '';

            $value['pdcheck'] = isset($value['pdcheck']) ? $value['pdcheck'] : '';
            $value['pd_r'] = isset($value['pd_r']) ? $value['pd_r'] : '';
            $value['pd_l'] = isset($value['pd_l']) ? $value['pd_l'] : '';
            $value['pd'] = isset($value['pd']) ? $value['pd'] : '';

            $value['prismcheck'] = isset($value['prismcheck']) ? $value['prismcheck'] : '';


            $spreadsheet->getActiveSheet()->setCellValue("A" . ($key * 2 + 2), $value['created_at']);
            $spreadsheet->getActiveSheet()->setCellValue("B" . ($key * 2 + 2), $value['increment_id']);
            $spreadsheet->getActiveSheet()->setCellValue("C" . ($key * 2 + 2), '');
            $spreadsheet->getActiveSheet()->setCellValue("D" . ($key * 2 + 2), $value['sku']);

            $spreadsheet->getActiveSheet()->setCellValue("E" . ($key * 2 + 2), '右眼');
            $spreadsheet->getActiveSheet()->setCellValue("E" . ($key * 2 + 3), '左眼');

            $spreadsheet->getActiveSheet()->setCellValue("F" . ($key * 2 + 2), $value['od_sph'] > 0 ? ' +' . $value['od_sph'] : ' ' . $value['od_sph']);
            $spreadsheet->getActiveSheet()->setCellValue("F" . ($key * 2 + 3), $value['os_sph'] > 0 ? ' +' . $value['os_sph'] : ' ' . $value['os_sph']);

            $spreadsheet->getActiveSheet()->setCellValue("G" . ($key * 2 + 2), $value['od_cyl'] > 0 ? ' +' . $value['od_cyl'] : ' ' . $value['od_cyl']);
            $spreadsheet->getActiveSheet()->setCellValue("G" . ($key * 2 + 3), $value['os_cyl'] > 0 ? ' +' . $value['os_cyl'] : ' ' . $value['os_cyl']);

            $spreadsheet->getActiveSheet()->setCellValue("H" . ($key * 2 + 2), $value['od_axis']);
            $spreadsheet->getActiveSheet()->setCellValue("H" . ($key * 2 + 3), $value['os_axis']);

            if (strlen($value['os_add']) > 0 && strlen($value['od_add']) > 0 && $value['od_add'] * 1 != 0 && $value['os_add'] * 1 != 0) {
                $spreadsheet->getActiveSheet()->setCellValue("I" . ($key * 2 + 2), $value['od_add']);
                $spreadsheet->getActiveSheet()->setCellValue("I" . ($key * 2 + 3), $value['os_add']);
            } else {
                //数值在上一行合并有效，数值在下一行合并后为空
                $spreadsheet->getActiveSheet()->setCellValue("I" . ($key * 2 + 2), $value['os_add']);
                $spreadsheet->getActiveSheet()->mergeCells("I" . ($key * 2 + 2) . ":I" . ($key * 2 + 3));
            }

            if ($value['pdcheck'] == 'on' && $value['pd_r'] && $value['pd_l']) {
                $spreadsheet->getActiveSheet()->setCellValue("J" . ($key * 2 + 2), $value['pd_r']);
                $spreadsheet->getActiveSheet()->setCellValue("J" . ($key * 2 + 3), $value['pd_l']);
            } else {
                $spreadsheet->getActiveSheet()->setCellValue("K" . ($key * 2 + 2), $value['pd']);
                $spreadsheet->getActiveSheet()->mergeCells("K" . ($key * 2 + 2) . ":K" . ($key * 2 + 3));
            }

            $spreadsheet->getActiveSheet()->setCellValue("L" . ($key * 2 + 2), $value['index_type']);
            $spreadsheet->getActiveSheet()->setCellValue("M" . ($key * 2 + 2), $value['lens_width']);
            $spreadsheet->getActiveSheet()->setCellValue("N" . ($key * 2 + 2), $value['lens_height']);
            $spreadsheet->getActiveSheet()->setCellValue("O" . ($key * 2 + 2), $value['bridge']);
            $spreadsheet->getActiveSheet()->setCellValue("P" . ($key * 2 + 2), $value['prescription_type']);

            if (isset($value['information'])) {
                $value['information'] = urldecode($value['information']);
                $value['information'] = urldecode($value['information']);
                $value['information'] = urldecode($value['information']);

                $value['information'] = str_replace('+', ' ', $value['information']);
                $spreadsheet->getActiveSheet()->setCellValue("Q" . ($key * 2 + 2), $value['information']);
                $spreadsheet->getActiveSheet()->mergeCells("Q" . ($key * 2 + 2) . ":Q" . ($key * 2 + 3));
            }

            $spreadsheet->getActiveSheet()->setCellValue("R" . ($key * 2 + 2), isset($value['od_pv']) ? $value['od_pv'] : '');
            $spreadsheet->getActiveSheet()->setCellValue("R" . ($key * 2 + 3), isset($value['os_pv']) ? $value['os_pv'] : '');

            $spreadsheet->getActiveSheet()->setCellValue("S" . ($key * 2 + 2), isset($value['od_bd']) ? $value['od_bd'] : '');
            $spreadsheet->getActiveSheet()->setCellValue("S" . ($key * 2 + 3), isset($value['os_bd']) ? $value['os_bd'] : '');

            $spreadsheet->getActiveSheet()->setCellValue("T" . ($key * 2 + 2), isset($value['od_pv_r']) ? $value['od_pv_r'] : '');
            $spreadsheet->getActiveSheet()->setCellValue("T" . ($key * 2 + 3), isset($value['os_pv_r']) ? $value['os_pv_r'] : '');

            $spreadsheet->getActiveSheet()->setCellValue("U" . ($key * 2 + 2), isset($value['od_bd_r']) ? $value['od_bd_r'] : '');
            $spreadsheet->getActiveSheet()->setCellValue("U" . ($key * 2 + 3), isset($value['os_bd_r']) ? $value['os_bd_r'] : '');

            //合并单元格
            $spreadsheet->getActiveSheet()->mergeCells("A" . ($key * 2 + 2) . ":A" . ($key * 2 + 3));
            $spreadsheet->getActiveSheet()->mergeCells("B" . ($key * 2 + 2) . ":B" . ($key * 2 + 3));
            $spreadsheet->getActiveSheet()->mergeCells("C" . ($key * 2 + 2) . ":C" . ($key * 2 + 3));
            $spreadsheet->getActiveSheet()->mergeCells("D" . ($key * 2 + 2) . ":D" . ($key * 2 + 3));
            $spreadsheet->getActiveSheet()->mergeCells("L" . ($key * 2 + 2) . ":L" . ($key * 2 + 3));

            $spreadsheet->getActiveSheet()->mergeCells("M" . ($key * 2 + 2) . ":M" . ($key * 2 + 3));
            $spreadsheet->getActiveSheet()->mergeCells("N" . ($key * 2 + 2) . ":N" . ($key * 2 + 3));
            $spreadsheet->getActiveSheet()->mergeCells("O" . ($key * 2 + 2) . ":O" . ($key * 2 + 3));
            $spreadsheet->getActiveSheet()->mergeCells("P" . ($key * 2 + 2) . ":P" . ($key * 2 + 3));
            $spreadsheet->getActiveSheet()->mergeCells("Q" . ($key * 2 + 2) . ":Q" . ($key * 2 + 3));
        }

        //设置宽度
        $spreadsheet->getActiveSheet()->getColumnDimension('A')->setWidth(12);
        $spreadsheet->getActiveSheet()->getColumnDimension('B')->setWidth(12);
        $spreadsheet->getActiveSheet()->getColumnDimension('D')->setWidth(16);
        $spreadsheet->getActiveSheet()->getColumnDimension('L')->setWidth(32);
        $spreadsheet->getActiveSheet()->getColumnDimension('M')->setWidth(12);
        $spreadsheet->getActiveSheet()->getColumnDimension('N')->setWidth(12);
        $spreadsheet->getActiveSheet()->getColumnDimension('O')->setWidth(12);

        $spreadsheet->getActiveSheet()->getColumnDimension('P')->setWidth(18);
        $spreadsheet->getActiveSheet()->getColumnDimension('Q')->setWidth(14);

        $spreadsheet->getActiveSheet()->getColumnDimension('R')->setWidth(14);
        $spreadsheet->getActiveSheet()->getColumnDimension('S')->setWidth(14);
        $spreadsheet->getActiveSheet()->getColumnDimension('T')->setWidth(16);
        $spreadsheet->getActiveSheet()->getColumnDimension('U')->setWidth(16);


        $spreadsheet->getDefaultStyle()->getFont()->setName('微软雅黑')->setSize(12);

        //自动换行
        $spreadsheet->getDefaultStyle()->getAlignment()->setWrapText(true);

        //设置边框
        $border = [
            'borders' => [
                'allBorders' => [
                    'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN, // 设置border样式
                    'color'       => ['argb' => 'FF000000'], // 设置border颜色
                ],
            ],
        ];
        $setBorder = 'A1:' . $spreadsheet->getActiveSheet()->getHighestColumn() . $spreadsheet->getActiveSheet()->getHighestRow();
        $spreadsheet->getActiveSheet()->getStyle($setBorder)->applyFromArray($border);

        // $spreadsheet->getActiveSheet()->getStyle('A1:Z'.$key)->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
        $spreadsheet->getActiveSheet()->getStyle('A1:U' . $spreadsheet->getActiveSheet()->getHighestRow())->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
        $spreadsheet->getActiveSheet()->getStyle('A1:U' . $spreadsheet->getActiveSheet()->getHighestRow())->getAlignment()->setVertical(\PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER);

        //水平垂直居中   
        // $objSheet->getDefaultStyle()->getAlignment()->setHorizontal(\PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
        // $objSheet->getDefaultStyle()->getAlignment()->setVertical(\PHPExcel_Style_Alignment::VERTICAL_CENTER);
        // //自动换行
        // $objSheet->getDefaultStyle()->getAlignment()->setWrapText(true);

        // Set active sheet index to the first sheet, so Excel opens this as the first sheet
        $spreadsheet->setActiveSheetIndex(0);
        // return exportExcel($spreadsheet, 'xls', '登陆日志');
        $format = 'xlsx';
        $savename = '订单打印处方' . date("YmdHis", time());;
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
        // exit;
        // $filePath = env('runtime_path')."temp/".time().microtime(true).".tmp";
        // $writer->save($filePath);
        $writer->save('php://output');
        // $writer->save('file.xlsx');

        // readfile($filePath);
        // unlink($filePath);
    }

    //批量打印标签
    public function batch_print_label()
    {
        ob_start();
        $ids = rtrim(input('id_params'), ',');
        if ($ids) {
            $map['a.id'] = ['in', $ids];
            $processing_order_list = $this->model->field('sku,b.total,optionname,lens_data,a.createtime,ordersn')->where($map)->alias('a')
                ->join(['ims_ewei_shop_order_goods' => 'b'], 'a.id=b.orderid', 'left')
                ->join(['ims_ewei_shop_goods' => 'c'], 'b.goodsid=c.id', 'left')
                ->select();
            $processing_order_list = collection($processing_order_list)->toArray();

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

            //查询产品货位号
            $store_sku = new \app\admin\model\warehouse\StockHouse;
            $cargo_number = $store_sku->alias('a')->where(['status' => 1, 'b.is_del' => 1])->join(['fa_store_sku' => 'b'], 'a.id=b.store_id')->column('coding', 'sku');

            $file_content = '';
            $temp_increment_id = [];
            foreach ($processing_order_list as $processing_key => $processing_value) {
                if (!in_array($processing_value['ordersn'], $temp_increment_id)) {
                    $temp_increment_id[] = $processing_value['ordersn'];
                    $processing_value['ordersn'] = substr($processing_value['ordersn'], -12);
                    $date = substr($processing_value['createtime'], 0, strpos($processing_value['createtime'], " "));
                    $fileName = ROOT_PATH . "public" . DS . "uploads" . DS . "printOrder" . DS . "rufoo" . DS . "$date" . DS . $processing_value['ordersn'] . ".png";
                    // dump($fileName);
                    $dir = ROOT_PATH . "public" . DS . "uploads" . DS . "printOrder" . DS . "rufoo" . DS . "$date";

                    if (!file_exists($dir)) {
                        mkdir($dir, 0777, true);
                        // echo '创建文件夹$dir成功';
                    } else {
                        // echo '需创建的文件夹$dir已经存在';
                    }
                    $img_url = "/uploads/printOrder/rufoo/$date/{$processing_value['ordersn']}.png";
                    //生成条形码
                    $this->generate_barcode($processing_value['ordersn'], $fileName);
                    // echo '<br>需要打印'.$temp_increment_id;
                    $file_content .= "<div  class = 'single_box'>
                <table width='400mm' height='102px' border='0' cellspacing='0' cellpadding='0' class='addpro' style='margin:0px auto;margin-top:0px;padding:0px;'>
                <tr><td rowspan='5' colspan='2' style='padding:2px;width:20%'>" . str_replace(" ", "<br>", date('Y-m-d H:i:s', $processing_value['createtime'])) . "</td>
                <td rowspan='5' colspan='3' style='padding:10px;'><img src='" . $img_url . "' height='80%'><br></td></tr>                
                </table></div>";
                }
                $final_print = json_decode($processing_value['lens_data'], true);
                $final_print['index_type'] = $processing_value['optionname'];
                $final_print['prescription_type'] = $final_print['type'];

                $final_print['od_sph'] = isset($final_print['od_sph']) ? $final_print['od_sph'] : '';
                $final_print['os_sph'] = isset($final_print['os_sph']) ? $final_print['os_sph'] : '';
                $final_print['od_cyl'] = isset($final_print['od_cyl']) ? $final_print['od_cyl'] : '';
                $final_print['os_cyl'] = isset($final_print['os_cyl']) ? $final_print['os_cyl'] : '';
                $final_print['od_axis'] = isset($final_print['od_axis']) ? $final_print['od_axis'] : '';
                $final_print['os_axis'] = isset($final_print['os_axis']) ? $final_print['os_axis'] : '';

                $final_print['od_add'] = isset($final_print['od_add']) ? $final_print['od_add'] : '';
                $final_print['os_add'] = isset($final_print['os_add']) ? $final_print['os_add'] : '';

                $final_print['pdcheck'] = isset($final_print['pdcheck']) ? $final_print['pdcheck'] : '';
                $final_print['pd_r'] = isset($final_print['pd_r']) ? $final_print['pd_r'] : '';
                $final_print['pd_l'] = isset($final_print['pd_l']) ? $final_print['pd_l'] : '';
                $final_print['pd'] = isset($final_print['pd']) ? $final_print['pd'] : '';


                //处理ADD  当ReadingGlasses时 是 双ADD值
                if ($final_print['os_add'] && $final_print['od_add'] && (float) $final_print['od_add'] * 1 != 0 &&  (float) $final_print['os_add'] * 1 != 0) {
                    // echo '双ADD值';
                    $od_add = "<td>" . $final_print['od_add'] . "</td> ";
                    $os_add = "<td>" . $final_print['os_add'] . "</td> ";
                } else {
                    // echo '单ADD值';
                    $od_add = "<td rowspan='2'>" . $final_print['os_add'] . "</td>";
                    $os_add = "";
                }

                //处理PD值
                if ($final_print['pdcheck'] && strlen($final_print['pd_r']) > 0 && strlen($final_print['pd_l']) > 0) {
                    // echo '双PD值';
                    $od_pd = "<td>" . $final_print['pd_r'] . "</td> ";
                    $os_pd = "<td>" . $final_print['pd_l'] . "</td> ";
                } else {
                    // echo '单PD值';
                    $od_pd = "<td rowspan='2'>" . $final_print['pd'] . "</td>";
                    $os_pd = "";
                }

                //处理斜视参数
                if ($final_print['prismcheck'] == 'on') {
                    $prismcheck_title = "<td>Prism</td><td colspan=''>Direc</td><td>Prism</td><td colspan=''>Direc</td>";
                    $prismcheck_od_value = "<td>" . $final_print['od_pv'] . "</td><td colspan=''>" . $final_print['od_bd'] . "</td>" . "<td>" . $final_print['od_pv_r'] . "</td><td>" . $final_print['od_bd_r'] . "</td>";
                    $prismcheck_os_value = "<td>" . $final_print['os_pv'] . "</td><td colspan=''>" . $final_print['os_bd'] . "</td>" . "<td>" . $final_print['os_pv_r'] . "</td><td>" . $final_print['os_bd_r'] . "</td>";
                    $coatiing_name = '';
                } else {
                    $prismcheck_title = '';
                    $prismcheck_od_value = '';
                    $prismcheck_os_value = '';
                    $coatiing_name = "<td colspan='4' rowspan='3' style='background-color:#fff;word-break: break-word;line-height: 12px;'>" . $final_print['coatiing_name'] . "</td>";
                }

                //处方字符串截取
                $final_print['prescription_type'] = substr($final_print['prescription_type'], 0, 15);

                //判断货号是否存在
                if ($cargo_number[$processing_value['sku']]) {
                    $cargo_number_str = "<b>" . $cargo_number[$processing_value['sku']] . "</b><br>";
                } else {
                    $cargo_number_str = "";
                }

                $file_content .= "<div  class = 'single_box'>
            <table width='400mm' height='102px' border='0' cellspacing='0' cellpadding='0' class='addpro' style='margin:0px auto;margin-top:0px;' >
            <tbody cellpadding='0'>
            <tr>
            <td colspan='10' style=' text-align:center;padding:0px 0px 0px 0px;'>                              
            <span>" . $final_print['prescription_type'] . "</span>
            &nbsp;&nbsp;Order:" . $processing_value['ordersn'] . "
            <span style=' margin-left:5px;'>SKU:" . $processing_value['sku'] . "</span>
            <span style=' margin-left:5px;'>Num:<strong>" . $processing_value['total'] . "</strong></span>
            </td>
            </tr>  
            <tr class='title'>      
            <td></td>  
            <td>SPH</td>
            <td>CYL</td>
            <td>AXI</td>
            " . $prismcheck_title . "
            <td>ADD</td>
            <td>PD</td> 
            " . $coatiing_name . "
            </tr>   
            <tr>  
            <td>Right</td>      
            <td>" . $final_print['od_sph'] . "</td> 
            <td>" . $final_print['od_cyl'] . "</td>
            <td>" . $final_print['od_axis'] . "</td>    
            " . $prismcheck_od_value . $od_add . $od_pd .
                    "</tr>
            <tr>
            <td>Left</td> 
            <td>" . $final_print['os_sph'] . "</td>    
            <td>" . $final_print['os_cyl'] . "</td>  
            <td>" . $final_print['os_axis'] . "</td> 
            " . $prismcheck_os_value . $os_add . $os_pd .
                    " </tr>
            <tr>
            <td colspan='2'>" . $cargo_number_str . SKUHelper::sku_filter($processing_value['sku']) . "</td>
            <td colspan='8' style=' text-align:center'>Lens：" . $final_print['index_type'] . "</td>
            </tr>  
            </tbody></table></div>";
            }
            echo $file_header . $file_content;
        }
    }


    //  一个SKU的qty_order > 1时平铺开来
    protected function qty_order_check($origin_order_item)
    {
        foreach ($origin_order_item as $origin_order_key => $origin_order_value) {
            if ($origin_order_value['qty_ordered'] > 1 && strpos($origin_order_value['sku'], 'Price') === false) {
                unset($origin_order_item[$origin_order_key]);
                for ($i = 0; $i < $origin_order_value['qty_ordered']; $i++) {
                    $tmp_order_value = $origin_order_value;
                    $tmp_order_value['qty_ordered'] = 1;
                    array_push($origin_order_item, $tmp_order_value);
                }
                unset($tmp_order_value);
            }
        }
        $origin_order_item = $this->arraySequence($origin_order_item, 'NUM');
        return array_values($origin_order_item);
    }

    //  二维数组排序
    protected function arraySequence($array, $field, $sort = 'SORT_ASC')
    {
        $arrSort = array();
        foreach ($array as $uniqid => $row) {
            foreach ($row as $key => $value) {
                $arrSort[$key][$uniqid] = $value;
            }
        }
        array_multisort($arrSort[$field], constant($sort), $array);
        return $array;
    }
}
