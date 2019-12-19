<?php

namespace app\admin\controller\order\printlabel;

use app\common\controller\Backend;

use think\Db;
use think\Loader;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use think\Exception;
use think\exception\PDOException;
use Util\ZeeloolPrescriptionDetailHelper;
use Util\SKUHelper;

/**
 * Sales Flat Order
 *
 * @icon fa fa-circle-o
 */
class Zeelool extends Backend
{

    /**
     * Sales_flat_order模型对象
     * @var \app\admin\model\order\Sales_flat_order
     */
    protected $model = null;

    public function _initialize()
    {
        parent::_initialize();
        $this->model = new \app\admin\model\order\printlabel\Zeelool;
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

            list($where, $sort, $order, $offset, $limit) = $this->buildparams();

            $filter = json_decode($this->request->get('filter'), true);

            if ($filter['increment_id']) {
                $map['status'] = ['in', ['free_processing', 'processing', 'complete']];
            } else {
                $map['status'] = ['in', ['free_processing', 'processing']];
            }

            $total = $this->model
                ->where($map)
                ->where($where)
                ->order($sort, $order)
                ->count();
            // var_dump($total);die;                                                                            
            $field = 'custom_order_prescription_type,entity_id,status,base_shipping_amount,increment_id,coupon_code,shipping_description,store_id,customer_id,base_discount_amount,base_grand_total,
                     total_qty_ordered,quote_id,base_currency_code,customer_email,customer_firstname,customer_lastname,custom_is_match_frame,custom_is_match_lens,
                     custom_is_send_factory,custom_is_delivery,custom_match_frame_created_at,custom_match_lens_created_at,custom_match_factory_created_at,
                     custom_match_delivery_created_at,custom_print_label,custom_order_prescription,custom_print_label_created_at,custom_service_name,created_at';
            $list = $this->model
                ->field($field)
                ->where($map)
                ->where($where)
                ->order($sort, $order)
                ->limit($offset, $limit)
                ->select();

            $list = collection($list)->toArray();
            //查询订单是否存在协同任务
            $increment_ids = array_column($list, 'increment_id');
            $infoSynergyTask = new \app\admin\model\infosynergytaskmanage\InfoSynergyTask;
            $swhere['synergy_order_number'] = ['in', $increment_ids];
            $swhere['is_del'] = 1;
            $swhere['order_platform'] = 1;
            $swhere['synergy_order_id'] = 2;
            $order_arr = $infoSynergyTask->where($swhere)->column('synergy_order_number');


            //查询是否存在协同任务
            foreach ($list as $k => $v) {
                if (in_array($v['increment_id'], $order_arr)) {
                    $list[$k]['task_info'] = 1;
                }
            }

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
            if ($increment_id) {
                $map['increment_id'] = $increment_id;
                $map['status'] = ['in', ['free_processing', 'processing', 'complete']];
                $list = $this->model
                    ->where($map)
                    ->find();
                if ($list) {
                    //查询订单是否存在协同任务
                    $infoSynergyTask = new \app\admin\model\infosynergytaskmanage\InfoSynergyTask;
                    $swhere['synergy_order_number'] = $increment_id;
                    $swhere['is_del'] = 1;
                    $swhere['order_platform'] = 1;
                    $swhere['synergy_order_id'] = 2;
                    $count = $infoSynergyTask->where($swhere)->count();
                    //查询是否存在协同任务
                    if ($count > 0) {
                        $list['task_info'] = 1;
                    }
                }
                $result = ['code' => 1, 'data' => $list ?? []];
            } else {
                $result = array("total" => 0, "rows" => []);
            }
            return json($result);
        }
        return $this->view->fetch('_list');
    }

    public function detail($ids = null)
    {
        $row = $this->model->get($ids);
        if (!$row) {
            $this->error(__('No Results were found'));
        }
        //查询订单详情
        // $result = $this->model->getOrderDetail(1, $ids);
        $result = ZeeloolPrescriptionDetailHelper::get_one_by_entity_id($ids);
        $this->assign('result', $result);
        return $this->view->fetch();
    }

    //标记为已打印标签
    public function tag_printed()
    {
        // echo 'tag_printed';
        $entity_ids = input('id_params/a');
        $label = input('label');
        if ($entity_ids) {
            //多数据库
            $map['entity_id'] = ['in', $entity_ids];
            $data['custom_print_label'] = 1;
            $data['custom_print_label_created_at'] = date('Y-m-d H:i:s', time());
            $data['custom_print_label_person'] =  session('admin.nickname');
            $this->model->startTrans();
            try {
                $result = $this->model->where($map)->update($data);
                $this->model->commit();
            } catch (PDOException $e) {
                $this->model->rollback();
                $this->error($e->getMessage());
            } catch (Exception $e) {
                $this->model->rollback();
                $this->error($e->getMessage());
            }
            if ($result) {
                //用来判断是否从_list列表页进来
                if ($label == 'list') {
                    //订单号
                    $map['entity_id'] = ['in', $entity_ids];
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

    //配镜架 配镜片 加工 质检通过
    public function setOrderStatus()
    {
        $entity_ids = input('id_params/a');
        $status = input('status');
        $label = input('label');
        $map['entity_id'] = ['in', $entity_ids];
        $res = $this->model->where($map)->select();
        foreach ($res as $v) {
            if ($status == 1 && $v['custom_is_match_frame'] == 1) {
                $this->error('存在已配过镜架的订单！！');
             }
            if ($v['custom_is_delivery'] == 1) {
                $this->error('存在已质检通过的订单！！');
            }
        }


        if ($entity_ids) {
            switch ($status) {
                case 1:
                    //配镜架
                    $data['custom_is_match_frame'] = 1;
                    $data['custom_match_frame_created_at'] = date('Y-m-d H:i:s', time());
                    $data['custom_match_frame_person'] = session('admin.nickname');
                    break;
                case 2:
                    //配镜片
                    $data['custom_is_match_lens'] = 1;
                    $data['custom_match_lens_created_at'] = date('Y-m-d H:i:s', time());
                    $data['custom_match_lens_person'] = session('admin.nickname');
                    break;
                case 3:
                    //移送加工时间
                    $data['custom_is_send_factory'] = 1;
                    $data['custom_match_factory_created_at'] = date('Y-m-d H:i:s', time());
                    $data['custom_match_factory_person'] = session('admin.nickname');
                    break;
                case 4:
                    //质检通过
                    $data['custom_is_delivery'] = 1;
                    $data['custom_match_delivery_created_at'] = date('Y-m-d H:i:s', time());
                    $data['custom_match_delivery_person'] = session('admin.nickname');
                    break;
                default:
            }
            $item = new \app\admin\model\itemmanage\Item;
            $outStockItem = new \app\admin\model\warehouse\OutStockItem;
            $this->model->startTrans();
            $item->startTrans();
            try {
                $result = $this->model->where($map)->update($data);

                if ($status == 1) {
                    //查询出质检通过的订单
                    $res = $this->model->alias('a')->where($map)->field('a.increment_id,b.sku,b.qty_ordered,b.is_change_frame')->join(['sales_flat_order_item' => 'b'], 'a.entity_id = b.order_id')->select();
                    if (!$res) {
                        throw new Exception("未查询到订单数据！！");
                    };

                    $ItemPlatformSku = new \app\admin\model\itemmanage\ItemPlatformSku;
                    //查出订单SKU映射表对应的仓库SKU
                    foreach ($res as $k => &$v) {

                        //是否为更换镜架 如果为更换镜架 需处理更换之后SKU的库存
                        if ($v['is_change_frame'] == 2) {
                            //根据订单号 SKU查询更换镜架记录表 处理更换之后SKU库存
                            $infotask = new \app\admin\model\infosynergytaskmanage\InfoSynergyTaskChangeSku;
                            $infoTaskRes = $infotask->getChangeSkuData($v['increment_id'], 1, $v['sku']);

                            $v['sku'] = $infoTaskRes['change_sku'];
                            $v['qty_ordered'] = $infoTaskRes['change_number'];
                        }

                        $trueSku = $ItemPlatformSku->getTrueSku($v['sku'], 1);
                        //总库存
                        $item_map['sku'] = $trueSku;
                        $item_map['is_del'] = 1;
                        if ($v['sku']) {
                            //增加配货占用
                            $res_three = $item->where($item_map)->setInc('distribution_occupy_stock', $v['qty_ordered']);
                        }

                        if (!$res_three) {
                            $error[] = $k;
                        }
                    }
                    unset($v);

                    if (count($error)) {
                        throw new Exception("增加配货占用库存失败！！请检查SKU");
                    };
                    $item->commit();
                }


                //质检通过扣减库存
                if ($status == 4) {
                    //查询出质检通过的订单
                    $res = $this->model->alias('a')->where($map)->field('a.increment_id,b.sku,b.qty_ordered,b.is_change_frame')->join(['sales_flat_order_item' => 'b'], 'a.entity_id = b.order_id')->select();
                    if (!$res) {
                        throw new Exception("未查询到订单数据！！");
                    };

                    $ItemPlatformSku = new \app\admin\model\itemmanage\ItemPlatformSku;
                    //查出订单SKU映射表对应的仓库SKU
                    foreach ($res as $k => &$v) {
                        //是否为更换镜架 如果为更换镜架 需处理更换之后SKU的库存
                        if ($v['is_change_frame'] == 2) {
                            //根据订单号 SKU查询更换镜架记录表 处理更换之后SKU库存
                            $infotask = new \app\admin\model\infosynergytaskmanage\InfoSynergyTaskChangeSku;
                            $infoTaskRes = $infotask->getChangeSkuData($v['increment_id'], 1, $v['sku']);
                            $v['sku'] = $infoTaskRes['change_sku'];
                            $v['qty_ordered'] = $infoTaskRes['change_number'];
                        }

                        $trueSku = $ItemPlatformSku->getTrueSku($v['sku'], 1);

                        //总库存
                        $item_map['sku'] = $trueSku;
                        $item_map['is_del'] = 1;
                        if ($v['sku']) {
                            //扣减总库存 扣减占用库存
                            $res_one = $item->where($item_map)->setDec('stock', $v['qty_ordered']);
                            //占用库存
                            $res_two = $item->where($item_map)->setDec('occupy_stock', $v['qty_ordered']);

                            //扣减配货占用
                            $res_three = $item->where($item_map)->setDec('distribution_occupy_stock', $v['qty_ordered']);
                        }

                        if (!$res_one || !$res_two || !$res_three) {
                            $error[] = $k;
                        }

                        //先入先出逻辑
                        $rows['sku'] = $trueSku;
                        $rows['out_stock_num'] = $v['qty_ordered'];
                        $rows['increment_id'] = $v['increment_id'];
                        $outStockItem->setOrderOutStock($rows);
                    }
                    unset($v);
                    if (count($error)) {
                        throw new Exception("扣减库存失败！！请检查SKU");
                    };
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
            if ($result) {
                //用来判断是否从_list列表页进来
                if ($label == 'list') {
                    //订单号
                    $map['entity_id'] = ['in', $entity_ids];
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
                    'createtime' => $row['custom_print_label_created_at'],
                    'person' => $row['custom_print_label_person']
                ],
                [
                    'id' => 2,
                    'content' => '配镜架',
                    'createtime' => $row['custom_match_frame_created_at'],
                    'person' => $row['custom_match_frame_person']
                ],
                [
                    'id' => 3,
                    'content' => '配镜片',
                    'createtime' => $row['custom_match_lens_created_at'],
                    'person' => $row['custom_match_lens_person']
                ],
                [
                    'id' => 4,
                    'content' => '加工',
                    'createtime' => $row['custom_match_factory_created_at'],
                    'person' => $row['custom_match_factory_person']
                ],
                [
                    'id' => 5,
                    'content' => '提货',
                    'createtime' => $row['custom_match_delivery_created_at'],
                    'person' => $row['custom_match_delivery_person']
                ],
            ];
            $total = count($list);
            $result = array("total" => $total, "rows" => $list);
            return json($result);
        }
        $this->assignconfig('ids', $ids);
        return $this->view->fetch();
    }


    //生成条形码
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
            $resultList = Db::connect('database.db_zeelool')->query($querySql);
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
        $entity_ids = rtrim(input('id_params'), ',');
        // dump($entity_ids);        
        $processing_order_querySql = "select sfo.increment_id,sfoi.product_options,sfoi.order_id,sfo.`status`,sfoi.sku,sfoi.product_id,sfoi.qty_ordered,sfo.created_at
from sales_flat_order_item sfoi
left join sales_flat_order sfo on  sfoi.order_id=sfo.entity_id 
where sfo.`status` in ('processing','creditcard_proccessing','free_processing','paypal_reversed','complete') and sfo.entity_id in($entity_ids)
order by sfoi.order_id desc;";
        $resultList = Db::connect('database.db_zeelool')->query($processing_order_querySql);
        // dump($resultList);

        $resultList = $this->qty_order_check($resultList);

        $finalResult = array();
        foreach ($resultList as $key => $value) {
            $finalResult[$key]['increment_id'] = $value['increment_id'];
            $finalResult[$key]['sku'] = $value['sku'];
            $finalResult[$key]['created_at'] = substr($value['created_at'], 0, 10);

            $tmp_product_options = unserialize($value['product_options']);
            // dump($product_options);
            $finalResult[$key]['coatiing_name'] = $tmp_product_options['info_buyRequest']['tmplens']['coatiing_name'];
            $finalResult[$key]['index_type'] = $tmp_product_options['info_buyRequest']['tmplens']['index_type'];

            $tmp_prescription_params = $tmp_product_options['info_buyRequest']['tmplens']['prescription'];
            if (isset($tmp_prescription_params)) {
                $tmp_prescription_params = explode("&", $tmp_prescription_params);
                $tmp_lens_params = array();
                foreach ($tmp_prescription_params as $tmp_key => $tmp_value) {
                    // dump($value);
                    $arr_value = explode("=", $tmp_value);
                    if (isset($arr_value[1])) {
                        $tmp_lens_params[$arr_value[0]] = $arr_value[1];
                    }
                }
            }

            $finalResult[$key]['prescription_type'] = isset($tmp_lens_params['prescription_type']) ? $tmp_lens_params['prescription_type'] : '';
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

            $tmp_bridge = $this->get_frame_lens_width_height_bridge($value['product_id']);
            $finalResult[$key]['lens_width'] = $tmp_bridge['lens_width'];
            $finalResult[$key]['lens_height'] = $tmp_bridge['lens_height'];
            $finalResult[$key]['bridge'] = $tmp_bridge['bridge'];
        }
        // dump($finalResult);
        // exit;
        //从数据库查询需要的数据
        // $data = model('admin/Loginlog')->where($where)->order('id','desc')->select();
        // Create new Spreadsheet object
        $spreadsheet = new Spreadsheet();
        // Add title
        // $spreadsheet->setActiveSheetIndex(0)
        // ->setCellValue('A1', 'ID')
        // ->setCellValue('B1', '用户')
        // ->setCellValue('C1', '详情')
        // ->setCellValue('D1', '结果')
        // ->setCellValue('E1', '时间')
        // ->setCellValue('F1', 'IP');

        //常规方式：利用setCellValue()填充数据
        $spreadsheet->setActiveSheetIndex(0)->setCellValue("A1", "日期")
            ->setCellValue("B1", "订单号")
            ->setCellValue("C1", "SKU");   //利用setCellValues()填充数据
        $spreadsheet->setActiveSheetIndex(0)->setCellValue("D1", "眼球")
            ->setCellValue("E1", "SPH");
        $spreadsheet->setActiveSheetIndex(0)->setCellValue("F1", "CYL")
            ->setCellValue("G1", "AXI");
        $spreadsheet->setActiveSheetIndex(0)->setCellValue("H1", "ADD")
            ->setCellValue("I1", "单PD")
            ->setCellValue("J1", "PD");
        $spreadsheet->setActiveSheetIndex(0)->setCellValue("K1", "镜片")
            ->setCellValue("L1", "镜框宽度")
            ->setCellValue("M1", "镜框高度")
            ->setCellValue("N1", "bridge")
            ->setCellValue("O1", "处方类型")
            ->setCellValue("P1", "顾客留言");
        $spreadsheet->setActiveSheetIndex(0)->setCellValue("Q1", "Prism\n(out/in)")
            ->setCellValue("R1", "Direct\n(out/in)")
            ->setCellValue("S1", "Prism\n(up/down)")
            ->setCellValue("T1", "Direct\n(up/down)");
        // Rename worksheet
        $spreadsheet->setActiveSheetIndex(0)->setTitle('订单处方');

        foreach ($finalResult as $key => $value) {

            $value['prescription_type'] = isset($value['prescription_type']) ? $value['prescription_type'] : '';

            $value['od_sph'] = isset($value['od_sph']) ? $value['od_sph'] : '';
            $value['os_sph'] = isset($value['os_sph']) ? $value['os_sph'] : '';
            $value['od_cyl'] = isset($value['od_cyl']) ? $value['od_cyl'] : '';
            $value['os_cyl'] = isset($value['os_cyl']) ? $value['os_cyl'] : '';
            $value['od_axis'] = isset($value['od_axis']) ? $value['od_axis'] : '';
            $value['os_axis'] = isset($value['os_axis']) ? $value['os_axis'] : '';

            $value['od_add'] = isset($value['od_add']) ? $value['od_add'] : '';
            $value['os_add'] = isset($value['os_add']) ? $value['os_add'] : '';

            $value['pdcheck'] = isset($value['pdcheck']) ? $value['pdcheck'] : '';
            $value['pd_r'] = isset($value['pd_r']) ? $value['pd_r'] : '';
            $value['pd_l'] = isset($value['pd_l']) ? $value['pd_l'] : '';
            $value['pd'] = isset($value['pd']) ? $value['pd'] : '';

            $value['prismcheck'] = isset($value['prismcheck']) ? $value['prismcheck'] : '';


            $spreadsheet->getActiveSheet()->setCellValue("A" . ($key * 2 + 2), $value['created_at']);
            $spreadsheet->getActiveSheet()->setCellValue("B" . ($key * 2 + 2), $value['increment_id']);
            $spreadsheet->getActiveSheet()->setCellValue("C" . ($key * 2 + 2), $value['sku']);
            $spreadsheet->getActiveSheet()->setCellValue("D" . ($key * 2 + 2), '右眼');
            $spreadsheet->getActiveSheet()->setCellValue("D" . ($key * 2 + 3), '左眼');

            $spreadsheet->getActiveSheet()->setCellValue("E" . ($key * 2 + 2), $value['od_sph'] > 0 ? ' +' . $value['od_sph'] : ' ' . $value['od_sph']);
            $spreadsheet->getActiveSheet()->setCellValue("E" . ($key * 2 + 3), $value['os_sph'] > 0 ? ' +' . $value['os_sph'] : ' ' . $value['os_sph']);

            $spreadsheet->getActiveSheet()->setCellValue("F" . ($key * 2 + 2), $value['od_cyl'] > 0 ? ' +' . $value['od_cyl'] : ' ' . $value['od_cyl']);
            $spreadsheet->getActiveSheet()->setCellValue("F" . ($key * 2 + 3), $value['os_cyl'] > 0 ? ' +' . $value['os_cyl'] : ' ' . $value['os_cyl']);

            $spreadsheet->getActiveSheet()->setCellValue("G" . ($key * 2 + 2), $value['od_axis'] > 0 ? ' +' . $value['od_axis'] : ' ' . $value['od_axis']);
            $spreadsheet->getActiveSheet()->setCellValue("G" . ($key * 2 + 3), $value['os_axis'] > 0 ? ' +' . $value['os_axis'] : ' ' . $value['os_axis']);

            if ($value['od_add'] && $value['os_add']) {
                // 双ADD值时，左右眼互换
                $spreadsheet->getActiveSheet()->setCellValue("H" . ($key * 2 + 2), $value['os_add']);
                $spreadsheet->getActiveSheet()->setCellValue("H" . ($key * 2 + 3), $value['od_add']);
            } else {
                //数值在上一行合并有效，数值在下一行合并后为空
                $spreadsheet->getActiveSheet()->setCellValue("H" . ($key * 2 + 2), $value['os_add']);
                $spreadsheet->getActiveSheet()->mergeCells("H" . ($key * 2 + 2) . ":H" . ($key * 2 + 3));
            }

            if ($value['pdcheck'] == 'on' && $value['pd_r'] && $value['pd_l']) {
                $spreadsheet->getActiveSheet()->setCellValue("I" . ($key * 2 + 2), $value['pd_r']);
                $spreadsheet->getActiveSheet()->setCellValue("I" . ($key * 2 + 3), $value['pd_l']);
            } else {
                $spreadsheet->getActiveSheet()->setCellValue("J" . ($key * 2 + 2), $value['pd']);
                $spreadsheet->getActiveSheet()->mergeCells("J" . ($key * 2 + 2) . ":J" . ($key * 2 + 3));
            }

            $spreadsheet->getActiveSheet()->setCellValue("K" . ($key * 2 + 2), $value['index_type']);
            $spreadsheet->getActiveSheet()->setCellValue("L" . ($key * 2 + 2), $value['lens_width']);
            $spreadsheet->getActiveSheet()->setCellValue("M" . ($key * 2 + 2), $value['lens_height']);
            $spreadsheet->getActiveSheet()->setCellValue("N" . ($key * 2 + 2), $value['bridge']);
            $spreadsheet->getActiveSheet()->setCellValue("O" . ($key * 2 + 2), $value['prescription_type']);

            if (isset($value['information'])) {
                $value['information'] = urldecode($value['information']);
                $value['information'] = urldecode($value['information']);
                $value['information'] = urldecode($value['information']);

                $value['information'] = str_replace('+', ' ', $value['information']);
                $spreadsheet->getActiveSheet()->setCellValue("P" . ($key * 2 + 2), $value['information']);
                $spreadsheet->getActiveSheet()->mergeCells("P" . ($key * 2 + 2) . ":P" . ($key * 2 + 3));
            }

            $spreadsheet->getActiveSheet()->setCellValue("Q" . ($key * 2 + 2), isset($value['od_pv']) ? $value['od_pv'] : '');
            $spreadsheet->getActiveSheet()->setCellValue("Q" . ($key * 2 + 3), isset($value['os_pv']) ? $value['os_pv'] : '');

            $spreadsheet->getActiveSheet()->setCellValue("R" . ($key * 2 + 2), isset($value['od_bd']) ? $value['od_bd'] : '');
            $spreadsheet->getActiveSheet()->setCellValue("R" . ($key * 2 + 3), isset($value['os_bd']) ? $value['os_bd'] : '');

            $spreadsheet->getActiveSheet()->setCellValue("S" . ($key * 2 + 2), isset($value['od_pv_r']) ? $value['od_pv_r'] : '');
            $spreadsheet->getActiveSheet()->setCellValue("S" . ($key * 2 + 3), isset($value['os_pv_r']) ? $value['os_pv_r'] : '');

            $spreadsheet->getActiveSheet()->setCellValue("T" . ($key * 2 + 2), isset($value['od_bd_r']) ? $value['od_bd_r'] : '');
            $spreadsheet->getActiveSheet()->setCellValue("T" . ($key * 2 + 3), isset($value['os_bd_r']) ? $value['os_bd_r'] : '');

            //合并单元格
            $spreadsheet->getActiveSheet()->mergeCells("A" . ($key * 2 + 2) . ":A" . ($key * 2 + 3));
            $spreadsheet->getActiveSheet()->mergeCells("B" . ($key * 2 + 2) . ":B" . ($key * 2 + 3));
            $spreadsheet->getActiveSheet()->mergeCells("C" . ($key * 2 + 2) . ":C" . ($key * 2 + 3));
            $spreadsheet->getActiveSheet()->mergeCells("K" . ($key * 2 + 2) . ":K" . ($key * 2 + 3));

            $spreadsheet->getActiveSheet()->mergeCells("L" . ($key * 2 + 2) . ":L" . ($key * 2 + 3));
            $spreadsheet->getActiveSheet()->mergeCells("M" . ($key * 2 + 2) . ":M" . ($key * 2 + 3));
            $spreadsheet->getActiveSheet()->mergeCells("N" . ($key * 2 + 2) . ":N" . ($key * 2 + 3));
            $spreadsheet->getActiveSheet()->mergeCells("O" . ($key * 2 + 2) . ":O" . ($key * 2 + 3));
            $spreadsheet->getActiveSheet()->mergeCells("P" . ($key * 2 + 2) . ":P" . ($key * 2 + 3));
        }

        //设置宽度
        $spreadsheet->getActiveSheet()->getColumnDimension('A')->setWidth(12);
        $spreadsheet->getActiveSheet()->getColumnDimension('B')->setWidth(12);
        $spreadsheet->getActiveSheet()->getColumnDimension('C')->setWidth(16);
        $spreadsheet->getActiveSheet()->getColumnDimension('K')->setWidth(32);
        $spreadsheet->getActiveSheet()->getColumnDimension('L')->setWidth(12);
        $spreadsheet->getActiveSheet()->getColumnDimension('M')->setWidth(12);
        $spreadsheet->getActiveSheet()->getColumnDimension('N')->setWidth(12);

        $spreadsheet->getActiveSheet()->getColumnDimension('O')->setWidth(18);
        $spreadsheet->getActiveSheet()->getColumnDimension('P')->setWidth(14);

        $spreadsheet->getActiveSheet()->getColumnDimension('Q')->setWidth(14);
        $spreadsheet->getActiveSheet()->getColumnDimension('R')->setWidth(14);
        $spreadsheet->getActiveSheet()->getColumnDimension('S')->setWidth(16);
        $spreadsheet->getActiveSheet()->getColumnDimension('T')->setWidth(16);

        //自动换行
        // $spreadsheet->getActiveSheet()->getAlignment()->setWrapText(true);
        // $spreadsheet->getActiveSheet()->getStyle('K1')->getAlignment()->setWrapText(true);

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
        $spreadsheet->getActiveSheet()->getStyle('A1:Z' . $spreadsheet->getActiveSheet()->getHighestRow())->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
        $spreadsheet->getActiveSheet()->getStyle('A1:Z' . $spreadsheet->getActiveSheet()->getHighestRow())->getAlignment()->setVertical(\PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER);

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
        // echo 'batch_print_label';
        $entity_ids = rtrim(input('id_params'), ',');
        // dump($entity_ids);
        if ($entity_ids) {
            $processing_order_querySql = "select sfo.increment_id,round(sfo.total_qty_ordered,0) NUM,sfoi.product_options,sfoi.order_id,sfo.`status`,sfoi.sku,sfoi.qty_ordered,sfo.created_at
from sales_flat_order_item sfoi
left join sales_flat_order sfo on  sfoi.order_id=sfo.entity_id 
where sfo.`status` in ('processing','creditcard_proccessing','free_processing','complete','paypal_reversed','paypal_canceled_reversal') and sfo.entity_id in($entity_ids)
order by sfoi.order_id desc;";
            $processing_order_list = Db::connect('database.db_zeelool')->query($processing_order_querySql);

            $processing_order_list = $this->qty_order_check($processing_order_list);
            // dump($processing_order_list);

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
            // $product = M('product', 'zeelool_', 'DB_STOCK')->field('magento_sku,cargo_location_number')->where('is_active=1')->select();
            // //重组数组
            // $cargo_number = [];
            // foreach ($product as $k => $v) {
            //     $cargo_number[$v['magento_sku']] = $v['cargo_location_number'];
            // }

            $file_content = '';
            $temp_increment_id = 0;
            foreach ($processing_order_list as $processing_key => $processing_value) {
                if ($temp_increment_id != $processing_value['increment_id']) {
                    $temp_increment_id = $processing_value['increment_id'];

                    $date = substr($processing_value['created_at'], 0, strpos($processing_value['created_at'], " "));
                    $fileName = ROOT_PATH . "public" . DS . "uploads" . DS . "printOrder" . DS . "zeelool" . DS . "$date" . DS . "$temp_increment_id.png";
                    // dump($fileName);
                    $dir = ROOT_PATH . "public" . DS . "uploads" . DS . "printOrder" . DS . "zeelool" . DS . "$date";
                    if (!file_exists($dir)) {
                        mkdir($dir, 0777, true);
                        // echo '创建文件夹$dir成功';
                    } else {
                        // echo '需创建的文件夹$dir已经存在';
                    }
                    $img_url = "/uploads/printOrder/zeelool/$date/$temp_increment_id.png";
                    //生成条形码
                    $this->generate_barcode($temp_increment_id, $fileName);
                    // echo '<br>需要打印'.$temp_increment_id;
                    $file_content .= "<div  class = 'single_box'>
                <table width='400mm' height='102px' border='0' cellspacing='0' cellpadding='0' class='addpro' style='margin:0px auto;margin-top:0px;padding:0px;'>
                <tr><td rowspan='5' colspan='2' style='padding:2px;width:20%'>" . str_replace(" ", "<br>", $processing_value['created_at']) . "</td>
                <td rowspan='5' colspan='3' style='padding:10px;'><img src='" . $img_url . "' height='80%'><br></td></tr>                
                </table></div>";
                }

                $final_print = array();
                $product_options = unserialize($processing_value['product_options']);
                // dump($product_options);
                $final_print['coatiing_name'] = substr($product_options['info_buyRequest']['tmplens']['coatiing_name'], 0, 60);
                // $final_print['index_type'] = substr($product_options['info_buyRequest']['tmplens']['index_type'],0,60);
                $final_print['index_type'] = $product_options['info_buyRequest']['tmplens']['index_type'];

                $prescription_params = $product_options['info_buyRequest']['tmplens']['prescription'];
                if ($prescription_params) {
                    $prescription_params = explode("&", $prescription_params);
                    $lens_params = array();
                    foreach ($prescription_params as $key => $value) {
                        // dump($value);
                        $arr_value = explode("=", $value);
                        $lens_params[$arr_value[0]] = $arr_value[1];
                    }
                    // dump($lens_params);
                    $final_print = array_merge($lens_params, $final_print);
                }

                // dump($final_print);

                $final_print['prescription_type'] = isset($final_print['prescription_type']) ? $final_print['prescription_type'] : '';

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

                $final_print['prismcheck'] = isset($final_print['prismcheck']) ? $final_print['prismcheck'] : '';


                //处理ADD  当ReadingGlasses时 是 双ADD值
                if (strlen($final_print['os_add']) > 0 && strlen($final_print['od_add']) > 0) {
                    // echo '双ADD值';
                    $os_add = "<td>" . $final_print['od_add'] . "</td> ";
                    $od_add = "<td>" . $final_print['os_add'] . "</td> ";
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

                // dump($os_add);
                // dump($od_add);

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
                // if ($cargo_number[$processing_value['sku']]) {
                //     $cargo_number_str = $cargo_number[$processing_value['sku']] . "<br>";
                // } else {
                //     $cargo_number_str = "";
                // }

                $file_content .= "<div  class = 'single_box'>
            <table width='400mm' height='102px' border='0' cellspacing='0' cellpadding='0' class='addpro' style='margin:0px auto;margin-top:0px;' >
            <tbody cellpadding='0'>
            <tr>
            <td colspan='10' style=' text-align:center;padding:0px 0px 0px 0px;'>                              
            <span>" . $final_print['prescription_type'] . "</span>
            &nbsp;&nbsp;Order:" . $processing_value['increment_id'] . "
            <span style=' margin-left:5px;'>SKU:" . $processing_value['sku'] . "</span>
            <span style=' margin-left:5px;'>Num:<strong>" . $processing_order_list[$processing_key]['NUM'] . "</strong></span>
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
            <td colspan='2'>" . SKUHelper::sku_filter($processing_value['sku']) . "</td>
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
                // array_splice($origin_order_item,$origin_order_key,1);
                for ($i = 0; $i < $origin_order_value['qty_ordered']; $i++) {
                    $tmp_order_value = $origin_order_value;
                    $tmp_order_value['qty_ordered'] = 1;
                    array_push($origin_order_item, $tmp_order_value);
                }
                unset($tmp_order_value);
            }
        }
        $origin_order_item = $this->arraySequence($origin_order_item, 'increment_id');
        return array_values($origin_order_item);
    }

    //  二维数组排序
    protected function arraySequence($array, $field, $sort = 'SORT_DESC')
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
