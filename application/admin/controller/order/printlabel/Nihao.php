<?php

namespace app\admin\controller\order\printlabel;

use app\common\controller\Backend;

use think\Db;
use think\Loader;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use think\Exception;
use think\exception\PDOException;
use Util\NihaoPrescriptionDetailHelper;
use Util\SKUHelper;

/**
 * Sales Flat Order
 *
 * @icon fa fa-circle-o
 */
class Nihao extends Backend
{

    /**
     * Sales_flat_order模型对象
     * @var \app\admin\model\order\Sales_flat_order
     */
    protected $model = null;

    public function _initialize()
    {
        parent::_initialize();
        $this->model = new \app\admin\model\order\printlabel\Nihao;
    }

    // public function test(){
    //     // echo '123456';
    //     $entity_id = 1181;
    //     dump(NihaoPrescriptionDetailHelper::get_one_by_entity_id($entity_id));
    //     // $increment_id = '130023358';
    //     // dump(VooguemePrescriptionDetailHelper::get_one_by_increment_id($increment_id));
    // }

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
            $field = 'custom_order_prescription_type,entity_id,status,increment_id,coupon_code,shipping_description,store_id,customer_id,base_discount_amount,base_grand_total,
                     total_qty_ordered,quote_id,base_currency_code,customer_email,customer_firstname,customer_lastname,custom_is_match_frame,custom_is_match_lens,
                     custom_is_send_factory,custom_is_delivery,custom_match_frame_created_at,custom_match_lens_created_at,custom_match_factory_created_at,
                     custom_match_delivery_created_at,custom_print_label,custom_order_prescription,custom_print_label_created_at,custom_service_name';
            $list = $this->model
                // ->field($field)
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
            $swhere['order_platform'] = 3;
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
                    // ->field($field)
                    ->where($map)
                    ->find();
                if ($list) {
                    //查询订单是否存在协同任务
                    $infoSynergyTask = new \app\admin\model\infosynergytaskmanage\InfoSynergyTask;
                    $swhere['synergy_order_number'] = $increment_id;
                    $swhere['is_del'] = 1;
                    $swhere['order_platform'] = 3;
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
            $data['custom_print_label_person'] =  session('admin.username');
            $connect = Db::connect('database.db_nihao')->table('sales_flat_order');
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

            if ($status == 4 && $v['custom_is_match_frame'] == 0) {
                $this->error('存在未配镜架的订单！！');
            }
        }

        if ($entity_ids) {

            switch ($status) {
                case 1:
                    //配镜架
                    $data['custom_is_match_frame'] = 1;
                    $data['custom_match_frame_created_at'] = date('Y-m-d H:i:s', time());
                    $data['custom_match_frame_person'] = session('admin.username');
                    break;
                case 2:
                    //配镜片
                    $data['custom_is_match_lens'] = 1;
                    $data['custom_match_lens_created_at'] = date('Y-m-d H:i:s', time());
                    $data['custom_match_lens_person'] = session('admin.username');
                    break;
                case 3:
                    //移送加工时间
                    $data['custom_is_send_factory'] = 1;
                    $data['custom_match_factory_created_at'] = date('Y-m-d H:i:s', time());
                    $data['custom_match_factory_person'] = session('admin.username');
                    break;
                case 4:
                    //提货
                    $data['custom_is_delivery'] = 1;
                    $data['custom_match_delivery_created_at'] = date('Y-m-d H:i:s', time());
                    $data['custom_match_delivery_person'] = session('admin.username');
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
                            $infoTaskRes = $infotask->getChangeSkuData($v['increment_id'], 3, $v['sku']);

                            $v['sku'] = $infoTaskRes['change_sku'];
                            $v['qty_ordered'] = $infoTaskRes['change_number'];
                        }

                        $trueSku = $ItemPlatformSku->getTrueSku($v['sku'], 3);
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
                            $infoTaskRes = $infotask->getChangeSkuData($v['increment_id'], 3, $v['sku']);

                            $v['sku'] = $infoTaskRes['change_sku'];
                            $v['qty_ordered'] = $infoTaskRes['change_number'];
                        }

                        $trueSku = $ItemPlatformSku->getTrueSku($v['sku'], 3);
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
                return $this->success('操作成功!', '', $list, 200);
            } else {
                return $this->error('操作失败', '', 'error', 0);
            }
        }
    }

    public function detail($ids = null)
    {
        $row = $this->model->get($ids);
        if (!$row) {
            $this->error(__('No Results were found'));
        }
        //查询订单详情
        // $result = $this->model->getOrderDetail(3, $ids);
        $result = NihaoPrescriptionDetailHelper::get_one_by_entity_id($ids);
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
                    'content' => '质检',
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

            $bridge_querySql = "select cpev.attribute_id,cpev.`value`,cpev.entity_id
from catalog_product_entity_varchar cpev
where cpev.attribute_id =149 and cpev.store_id=0 and cpev.entity_id=$product_id";
            $bridge_resultList = Db::connect('database.db_nihao')->query($bridge_querySql);

            $lens_querySql = "select cped.attribute_id,cped.`value`,cped.entity_id
from catalog_product_entity_decimal cped 
where cped.attribute_id in(146,147) and cped.store_id=0 and cped.entity_id=$product_id";

            $lens_resultList = Db::connect('database.db_nihao')->query($lens_querySql);

            $result = array();
            if ($lens_resultList) {
                foreach ($lens_resultList as $key => $value) {
                    if ($value['attribute_id'] == 146) {
                        $result['lens_width'] = $value['value'];
                    }
                    if ($value['attribute_id'] == 147) {
                        $result['lens_height'] = $value['value'];
                    }
                }
            }

            if ($bridge_resultList) {
                foreach ($bridge_resultList as $key => $value) {
                    if ($value['attribute_id'] == 149) {
                        $result['bridge'] = $value['value'];
                    }
                }
            }
        }

        $result['lens_width'] = isset($result['lens_width']) ? $result['lens_width'] : '';
        $result['lens_height'] = isset($result['lens_height']) ? $result['lens_height'] : '';
        $result['bridge'] = isset($result['bridge']) ? $result['bridge'] : '';

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
        $resultList = Db::connect('database.db_nihao')->query($processing_order_querySql);
        // dump($resultList);

        $finalResult = array();
        foreach ($resultList as $key => $value) {
            $finalResult[$key]['increment_id'] = $value['increment_id'];
            $finalResult[$key]['sku'] = $value['sku'];
            $finalResult[$key]['created_at'] = substr($value['created_at'], 0, 10);

            $tmp_product_options = unserialize($value['product_options']);
            $finalResult[$key]['second_name'] = $tmp_product_options['info_buyRequest']['tmplens']['second_name'];
            $finalResult[$key]['third_name'] = $tmp_product_options['info_buyRequest']['tmplens']['third_name'];
            $finalResult[$key]['four_name'] = $tmp_product_options['info_buyRequest']['tmplens']['four_name'];
            $finalResult[$key]['zsl'] = $tmp_product_options['info_buyRequest']['tmplens']['zsl'];

            $tmp_lens_params = array();
            $tmp_lens_params = json_decode($tmp_product_options['info_buyRequest']['tmplens']['prescription'], true);
            // dump($prescription_params);
            // dump($product_options);

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
            } else {
                $finalResult[$key]['od_bd'] = '';
                $finalResult[$key]['od_pv'] = '';
                $finalResult[$key]['os_pv'] = '';
                $finalResult[$key]['os_bd'] = '';

                $finalResult[$key]['od_pv_r'] = '';
                $finalResult[$key]['od_bd_r'] = '';
                $finalResult[$key]['os_pv_r'] = '';
                $finalResult[$key]['os_bd_r'] = '';
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

        // Rename worksheet
        $spreadsheet->setActiveSheetIndex(0)->setTitle('订单处方');

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
        $spreadsheet->setActiveSheetIndex(0)->setCellValue("U1", "SKU转换")
            ->setCellValue("V1", "基片")
            ->setCellValue("W1", "镀膜");

        foreach ($finalResult as $key => $value) {

            $spreadsheet->getActiveSheet()->setCellValue("A" . ($key * 2 + 2), $value['created_at']);
            $spreadsheet->getActiveSheet()->setCellValue("B" . ($key * 2 + 2), $value['increment_id']);
            $spreadsheet->getActiveSheet()->setCellValue("C" . ($key * 2 + 2), $value['sku']);

            $spreadsheet->getActiveSheet()->setCellValue("D" . ($key * 2 + 2), "右眼");
            $spreadsheet->getActiveSheet()->setCellValue("D" . ($key * 2 + 3), "左眼");

            // $objSheet->setCellValue("E" . ($key*2 + 2), $value['od_sph']);
            $spreadsheet->getActiveSheet()->setCellValue("E" . ($key * 2 + 2), $value['od_sph'] > 0 ? ' +' . $value['od_sph'] : ' ' . $value['od_sph']);

            // $objSheet->setCellValue("E" . ($key*2 + 3), $value['os_sph']);
            $spreadsheet->getActiveSheet()->setCellValue("E" . ($key * 2 + 3), $value['os_sph'] > 0 ? ' +' . $value['os_sph'] : ' ' . $value['os_sph']);

            // $objSheet->setCellValue("F" . ($key*2 + 2), $value['od_cyl']);
            $spreadsheet->getActiveSheet()->setCellValue("F" . ($key * 2 + 2), $value['od_cyl'] > 0 ? ' +' . $value['od_cyl'] : ' ' . $value['od_cyl']);

            // $objSheet->setCellValue("F" . ($key*2 + 3), $value['os_cyl']);
            $spreadsheet->getActiveSheet()->setCellValue("F" . ($key * 2 + 3), $value['os_cyl'] > 0 ? ' +' . $value['os_cyl'] : ' ' . $value['os_cyl']);
            $spreadsheet->getActiveSheet()->setCellValue("G" . ($key * 2 + 2), $value['od_axis']);
            $spreadsheet->getActiveSheet()->setCellValue("G" . ($key * 2 + 3), $value['os_axis']);

            $spreadsheet->getActiveSheet()->setCellValue("G" . ($key * 2 + 2), $value['od_axis']);
            $spreadsheet->getActiveSheet()->setCellValue("G" . ($key * 2 + 3), $value['os_axis']);

            $spreadsheet->getActiveSheet()->setCellValue("Q" . ($key * 2 + 2), $value['od_pv']);
            $spreadsheet->getActiveSheet()->setCellValue("Q" . ($key * 2 + 3), $value['os_pv']);

            $spreadsheet->getActiveSheet()->setCellValue("R" . ($key * 2 + 2), $value['od_bd']);
            $spreadsheet->getActiveSheet()->setCellValue("R" . ($key * 2 + 3), $value['os_bd']);

            $spreadsheet->getActiveSheet()->setCellValue("S" . ($key * 2 + 2), $value['od_pv_r']);
            $spreadsheet->getActiveSheet()->setCellValue("S" . ($key * 2 + 3), $value['os_pv_r']);

            $spreadsheet->getActiveSheet()->setCellValue("T" . ($key * 2 + 2), $value['od_bd_r']);
            $spreadsheet->getActiveSheet()->setCellValue("T" . ($key * 2 + 3), $value['os_bd_r']);

            $spreadsheet->getActiveSheet()->setCellValue("U" . ($key * 2 + 2), $value['sku']);
            $spreadsheet->getActiveSheet()->setCellValue("V" . ($key * 2 + 2), $value['second_name']);
            $spreadsheet->getActiveSheet()->setCellValue("W" . ($key * 2 + 2), $value['four_name']);

            if ($value['prescription_type'] == 'Reading Glasses' && strlen($value['od_add']) > 0 && strlen($value['os_add']) > 0) {
                $spreadsheet->getActiveSheet()->setCellValue("H" . ($key * 2 + 2), $value['od_add']);
                $spreadsheet->getActiveSheet()->setCellValue("H" . ($key * 2 + 3), $value['os_add']);
            } else {
                //数值在上一行合并有效，数值在下一行合并后为空
                $spreadsheet->getActiveSheet()->setCellValue("H" . ($key * 2 + 2), $value['od_add']);
                $spreadsheet->getActiveSheet()->mergeCells("H" . ($key * 2 + 2) . ":H" . ($key * 2 + 3));
            }

            if ($value['pdcheck'] == 'on' && $value['pd_r'] && $value['pd_l']) {
                $spreadsheet->getActiveSheet()->setCellValue("I" . ($key * 2 + 2), $value['pd_r']);
                $spreadsheet->getActiveSheet()->setCellValue("I" . ($key * 2 + 3), $value['pd_l']);
            } else {
                $spreadsheet->getActiveSheet()->setCellValue("J" . ($key * 2 + 2), $value['pd']);
            }

            $spreadsheet->getActiveSheet()->setCellValue("K" . ($key * 2 + 2), $value['zsl'] . ' ' . $value['third_name']);
            $spreadsheet->getActiveSheet()->setCellValue("L" . ($key * 2 + 2), $value['lens_width']);
            $spreadsheet->getActiveSheet()->setCellValue("M" . ($key * 2 + 2), $value['lens_height']);
            $spreadsheet->getActiveSheet()->setCellValue("N" . ($key * 2 + 2), $value['bridge']);
            $spreadsheet->getActiveSheet()->setCellValue("O" . ($key * 2 + 2), $value['prescription_type']);

            $spreadsheet->getActiveSheet()->mergeCells("A" . ($key * 2 + 2) . ":A" . ($key * 2 + 3));
            $spreadsheet->getActiveSheet()->mergeCells("B" . ($key * 2 + 2) . ":B" . ($key * 2 + 3));
            $spreadsheet->getActiveSheet()->mergeCells("C" . ($key * 2 + 2) . ":C" . ($key * 2 + 3));
            $spreadsheet->getActiveSheet()->mergeCells("J" . ($key * 2 + 2) . ":J" . ($key * 2 + 3));
            $spreadsheet->getActiveSheet()->mergeCells("K" . ($key * 2 + 2) . ":K" . ($key * 2 + 3));
            $spreadsheet->getActiveSheet()->mergeCells("L" . ($key * 2 + 2) . ":L" . ($key * 2 + 3));
            $spreadsheet->getActiveSheet()->mergeCells("M" . ($key * 2 + 2) . ":M" . ($key * 2 + 3));
            $spreadsheet->getActiveSheet()->mergeCells("N" . ($key * 2 + 2) . ":N" . ($key * 2 + 3));
            $spreadsheet->getActiveSheet()->mergeCells("O" . ($key * 2 + 2) . ":O" . ($key * 2 + 3));
            $spreadsheet->getActiveSheet()->mergeCells("U" . ($key * 2 + 2) . ":U" . ($key * 2 + 3));
            $spreadsheet->getActiveSheet()->mergeCells("V" . ($key * 2 + 2) . ":V" . ($key * 2 + 3));
            $spreadsheet->getActiveSheet()->mergeCells("W" . ($key * 2 + 2) . ":W" . ($key * 2 + 3));

            if ($value['information']) {
                $value['information'] = urldecode($value['information']);
                $value['information'] = urldecode($value['information']);
                $value['information'] = urldecode($value['information']);

                $value['information'] = str_replace('+', ' ', $value['information']);
                $spreadsheet->getActiveSheet()->setCellValue("P" . ($key * 2 + 2), $value['information']);
                $spreadsheet->getActiveSheet()->mergeCells("P" . ($key * 2 + 2) . ":P" . ($key * 2 + 3));
            }
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

        $spreadsheet->getActiveSheet()->getColumnDimension('U')->setWidth(24);
        $spreadsheet->getActiveSheet()->getColumnDimension('V')->setWidth(24);
        $spreadsheet->getActiveSheet()->getColumnDimension('W')->setWidth(32);
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
            $processing_order_list = Db::connect('database.db_nihao')->query($processing_order_querySql);
            // dump($processing_order_list);exit;

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
            $cargo_number = $store_sku->alias('a')->where('status', 1)->join(['fa_store_sku' => 'b'], 'a.id=b.store_id')->column('coding', 'sku');

            //查询sku映射表
            $item = new \app\admin\model\itemmanage\ItemPlatformSku;
            $item_res = $item->cache(3600)->column('sku', 'platform_sku');

            $file_content = '';
            $temp_increment_id = 0;
            foreach ($processing_order_list as $processing_key => $processing_value) {
                if ($temp_increment_id != $processing_value['increment_id']) {
                    $temp_increment_id = $processing_value['increment_id'];

                    $date = substr($processing_value['created_at'], 0, strpos($processing_value['created_at'], " "));
                    $fileName = ROOT_PATH . "public" . DS . "uploads" . DS . "printOrder" . DS . "nihao" . DS . "$date" . DS . "$temp_increment_id.png";
                    // dump($fileName);
                    $dir = ROOT_PATH . "public" . DS . "uploads" . DS . "printOrder" . DS . "nihao" . DS . "$date";
                    if (!file_exists($dir)) {
                        mkdir($dir, 0777, true);
                        // echo '创建文件夹$dir成功';
                    } else {
                        // echo '需创建的文件夹$dir已经存在';
                    }
                    $img_url = "/uploads/printOrder/nihao/$date/$temp_increment_id.png";
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

                $final_print['second_name'] = $product_options['info_buyRequest']['tmplens']['second_name'];
                $final_print['third_name'] = $product_options['info_buyRequest']['tmplens']['third_name'];
                $final_print['four_name'] = $product_options['info_buyRequest']['tmplens']['four_name'];
                $final_print['zsl'] = $product_options['info_buyRequest']['tmplens']['zsl'];

                $final_print['index_type'] = $final_print['zsl'] . ' ' . $final_print['third_name'];

                $prescription_params = json_decode($product_options['info_buyRequest']['tmplens']['prescription'], true);

                $final_print = array_merge($final_print, $prescription_params);

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


                $final_print['second_name'] = $product_options['info_buyRequest']['tmplens']['second_name'];
                $final_print['third_name'] = $product_options['info_buyRequest']['tmplens']['third_name'];
                $final_print['four_name'] = $product_options['info_buyRequest']['tmplens']['four_name'];
                $final_print['zsl'] = $product_options['info_buyRequest']['tmplens']['zsl'];

                $final_print['index_type'] = $final_print['zsl'] . ' ' . $final_print['third_name'];

                $prescription_params = json_decode($product_options['info_buyRequest']['tmplens']['prescription'], true);

                $final_print = array_merge($final_print, $prescription_params);
                // dump($final_print);
                // exit;

                //处理ADD  当ReadingGlasses时 是 双PD值
                if ($final_print['prescription_type'] == 'Reading Glasses' &&  strlen($final_print['os_add']) > 0 && strlen($final_print['od_add']) > 0) {
                    // echo '双PD值';
                    $od_add = "<td>" . $final_print['od_add'] . "</td> ";
                    $os_add = "<td>" . $final_print['os_add'] . "</td> ";
                } else {
                    // echo '单ADD值';
                    $od_add = "<td rowspan='2'>" . $final_print['od_add'] . "</td>";
                    $os_add = "";
                }

                // dump($os_add);
                // dump($od_add);

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

                // dump($od_pd);
                // dump($os_pd);

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
                    $coatiing_name = "<td colspan='4' rowspan='3' style='background-color:#fff;word-break: break-word;line-height: 12px;'>" .  $final_print['second_name'] . '<br>' . $final_print['four_name'] . "</td>";
                }

                //处方字符串截取
                $final_print['prescription_type'] = substr($final_print['prescription_type'], 0, 15);

                //判断货号是否存在
                if ($cargo_number[$item_res[$processing_value['sku']]]) {
                    $cargo_number_str = "<b>" . $cargo_number[$item_res[$processing_value['sku']]] . "</b><br>";
                } else {
                    $cargo_number_str = "";
                }

                $file_content .= "<div  class = 'single_box'>
            <table width='400mm' height='102px' border='0' cellspacing='0' cellpadding='0' class='addpro' style='margin:0px auto;margin-top:0px;' >
                        <tbody cellpadding='0'>
                            <tr>
                              <td colspan='10' style=' text-align:center;padding:0px 0px 0px 0px;'>                              
                                  <span>" . $final_print['prescription_type'] . "</span>
                                  &nbsp;&nbsp;Order:" . $processing_value['increment_id'] . "
                                  <span style=' margin-left:8px;'>SKU:" . $processing_value['sku'] . "</span>
                                  <span style=' margin-left:8px;'>Num:<strong>" . $processing_order_list[$processing_key]['NUM'] . "</strong></span>
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
                                " . $prismcheck_od_value . $od_add .
                    $od_pd . "   
                            </tr>
                            <tr>
                                <td>Left</td> 
                                <td>" . $final_print['os_sph'] . "</td>    
                                <td>" . $final_print['os_cyl'] . "</td>  
                                <td>" . $final_print['os_axis'] . "</td> 
                                 " . $prismcheck_os_value . $os_add . $os_pd . " 
                            </tr>
                            <tr>
                              <td colspan='2'>" . $cargo_number_str . SKUHelper::sku_filter($processing_value['sku']) . "</td>
                              <td colspan='8' style=' text-align:center'>Lens：" . $final_print['index_type'] . "</td>
                            </tr>  
                            </tbody></table></div>";
            }
            echo $file_header . $file_content;
        }
    }
}
