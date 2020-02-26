<?php

namespace app\admin\controller\order\printlabel;

use app\common\controller\Backend;

use think\Db;
use think\Loader;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use think\Exception;
use think\exception\PDOException;
use Util\VooguemePrescriptionDetailHelper;
use Util\SKUHelper;
use app\admin\model\OrderLog;

/**
 * Sales Flat Order
 *
 * @icon fa fa-circle-o
 */
class Voogueme extends Backend
{

    /**
     * Sales_flat_order模型对象
     * @var \app\admin\model\order\Sales_flat_order
     */
    protected $model = null;

    protected $searchFields = 'entity_id';

    public function _initialize()
    {
        parent::_initialize();
        $this->model = new \app\admin\model\order\printlabel\Voogueme;
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

            if ($filter['increment_id']) {
                $map['status'] = ['in', ['free_processing', 'processing', 'complete']];
            } elseif (!$filter['status']) {
                $map['status'] = ['in', ['free_processing', 'processing']];
            } 
            //是否有协同任务
            $infoSynergyTask = new \app\admin\model\infosynergytaskmanage\InfoSynergyTask;
            if ($filter['task_label'] == 1 || $filter['task_label'] == '0') {
                $swhere['is_del'] = 1;
                $swhere['order_platform'] = 2;
                $swhere['synergy_order_id'] = 2;
                $order_arr = $infoSynergyTask->where($swhere)->order('create_time desc')->column('synergy_order_number');
                $map['increment_id'] = ['in', $order_arr];
                unset($filter['task_label']);
                $this->request->get(['filter' => json_encode($filter)]);
            } 

            //协同任务分类id搜索
            if ($filter['category_id'] || $filter['c_id']) {
                $swhere['is_del'] = 1;
                $swhere['order_platform'] = 2;
                $swhere['synergy_order_id'] = 2;
                $swhere['synergy_task_id'] = $filter['category_id'] ?? $filter['c_id'];

                $order_arr = $infoSynergyTask->where($swhere)->order('create_time desc')->column('synergy_order_number');
                $map['increment_id'] = ['in', $order_arr];
                unset($filter['category_id']);
                unset($filter['c_id']);
                $this->request->get(['filter' => json_encode($filter)]);
            }

            //SKU搜索
            if ($filter['sku']) {
                $smap['sku'] = ['like', '%' . $filter['sku'] . '%'];
                $smap['status'] = $filter['status'] ? ['in', $filter['status']] : $map['status'];
                $ids = $this->model->getOrderId($smap);
                $map['entity_id'] = ['in', $ids];
                unset($filter['sku']);
                $this->request->get(['filter' => json_encode($filter)]);
            }

            list($where, $sort, $order, $offset, $limit) = $this->buildparams();
            $total = $this->model
                ->where($map)
                ->where($where)
                ->order($sort, $order)
                ->count();                                                                        
            $field = 'order_type,custom_order_prescription_type,entity_id,status,base_shipping_amount,increment_id,coupon_code,shipping_description,store_id,customer_id,base_discount_amount,base_grand_total,
                     total_qty_ordered,quote_id,base_currency_code,customer_email,customer_firstname,customer_lastname,custom_is_match_frame_new,custom_is_match_lens_new,
                     custom_is_send_factory_new,custom_is_delivery_new,custom_print_label_new,custom_order_prescription,custom_service_name,created_at';
            $list = $this->model
                ->where($map)
                ->field($field)
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
            $swhere['order_platform'] = 2;
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
                    $swhere['order_platform'] = 2;
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

    //订单详情
    public function detail($ids = null)
    {
        $row = $this->model->get($ids);
        if (!$row) {
            $this->error(__('No Results were found'));
        }
        //查询订单详情
        // $result = $this->zeelool->getOrderDetail(2, $ids);
        $result = VooguemePrescriptionDetailHelper::get_one_by_entity_id($ids);

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
                    'content' => '提货',
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
            //多数据库
            $map['entity_id'] = ['in', $entity_ids];
            $data['custom_print_label_new'] = 1;
            $data['custom_print_label_created_at_new'] = date('Y-m-d H:i:s', time());
            $data['custom_print_label_person_new'] =  session('admin.nickname');
            $connect = Db::connect('database.db_voogueme')->table('sales_flat_order');
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
                $params['type'] = 1;
                $params['num'] = count($entity_ids);
                $params['order_ids'] = implode(',', $entity_ids);
                $params['site'] = 2;
                (new OrderLog())->setOrderLog($params);

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

        if ($entity_ids) {

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
                    //移送加工时间
                    $data['custom_is_send_factory_new'] = 1;
                    $data['custom_match_factory_created_at_new'] = date('Y-m-d H:i:s', time());
                    $data['custom_match_factory_person_new'] = session('admin.nickname');
                    $params['type'] = 4;
                    break;
                case 4:
                    //提货
                    $data['custom_is_delivery_new'] = 1;
                    $data['custom_match_delivery_created_at_new'] = date('Y-m-d H:i:s', time());
                    $data['custom_match_delivery_person_new'] = session('admin.nickname');
                    $params['type'] = 5;
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
                    $error = [];
                    foreach ($res as $k => &$v) {

                        //是否为更换镜架 如果为更换镜架 需处理更换之后SKU的库存
                        if ($v['is_change_frame'] == 2) {
                            //根据订单号 SKU查询更换镜架记录表 处理更换之后SKU库存
                            $infotask = new \app\admin\model\infosynergytaskmanage\InfoSynergyTaskChangeSku;
                            $infoTaskRes = $infotask->getChangeSkuData($v['increment_id'], 2, $v['sku']);

                            $v['sku'] = $infoTaskRes['change_sku'];
                            $v['qty_ordered'] = $infoTaskRes['change_number'];
                        }

                        $trueSku = $ItemPlatformSku->getTrueSku($v['sku'], 2);
                        //总库存
                        $item_map['sku'] = $trueSku;
                        $item_map['is_del'] = 1;
                        if ($trueSku) {
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
                    $error = [];
                    foreach ($res as $k => &$v) {

                        //是否为更换镜架 如果为更换镜架 需处理更换之后SKU的库存
                        if ($v['is_change_frame'] == 2) {
                            //根据订单号 SKU查询更换镜架记录表 处理更换之后SKU库存
                            $infotask = new \app\admin\model\infosynergytaskmanage\InfoSynergyTaskChangeSku;
                            $infoTaskRes = $infotask->getChangeSkuData($v['increment_id'], 2, $v['sku']);

                            $v['sku'] = $infoTaskRes['change_sku'];
                            $v['qty_ordered'] = $infoTaskRes['change_number'];
                        }


                        $trueSku = $ItemPlatformSku->getTrueSku($v['sku'], 2);
                        //总库存
                        $item_map['sku'] = $trueSku;
                        $item_map['is_del'] = 1;
                        if ($trueSku) {
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

                $params['num'] = count($entity_ids);
                $params['order_ids'] = implode(',', $entity_ids);
                $params['site'] = 2;
                (new OrderLog())->setOrderLog($params);

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
            $resultList = Db::connect('database.db_voogueme')->query($querySql);
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

        $filter = json_decode($this->request->get('filter'), true);

        if ($filter['increment_id']) {
            $map['sfo.status'] = ['in', ['free_processing', 'processing', 'complete']];
        } elseif (!$filter['status']) {
            $map['sfo.status'] = ['in', ['free_processing', 'processing']];
        }

        $infoSynergyTask = new \app\admin\model\infosynergytaskmanage\InfoSynergyTask;
        if ($filter['task_label'] == 1 || $filter['task_label'] == '0') {
            $swhere['is_del'] = 1;
            $swhere['order_platform'] = 1;
            $swhere['synergy_order_id'] = 2;
            $order_arr = $infoSynergyTask->where($swhere)->order('create_time desc')->column('synergy_order_number');
            $map['sfo.increment_id'] = ['in', $order_arr];
            unset($filter['task_label']);
            $this->request->get(['filter' => json_encode($filter)]);
        }

        if ($ids) {
            $map['sfo.entity_id'] = ['in', $ids];
        }

        if ($filter['created_at']) {
            $created_at = explode(' - ', $filter['created_at']);
            $map['sfo.created_at'] = ['between', [$created_at[0], $created_at[1]]];
            unset($filter['created_at']);
            $this->request->get(['filter' => json_encode($filter)]);
        }

        //SKU搜索
        if ($filter['sku']) {
            $map['sku'] = ['like', '%' . $filter['sku'] . '%'];
            unset($filter['sku']);
            $this->request->get(['filter' => json_encode($filter)]);
        }

        list($where) = $this->buildparams();
        $field = 'sfo.increment_id,sfoi.product_options,sfoi.order_id,sfo.`status`,sfoi.sku,sfoi.product_id,sfoi.qty_ordered,sfo.created_at';
        $resultList = $this->model->alias('sfo')
            ->join(['sales_flat_order_item' => 'sfoi'], 'sfoi.order_id=sfo.entity_id')
            ->field($field)
            ->where($map)
            ->where($where)
            ->order('sfoi.order_id desc')
            ->select();

        $resultList = collection($resultList)->toArray();

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

        $ItemPlatformSku = new \app\admin\model\itemmanage\ItemPlatformSku;

        //查询商品管理SKU对应ID
        $item = new \app\admin\model\itemmanage\Item;
        $itemArr = $item->where('is_del',1)->column('id','sku');

        foreach ($finalResult as $key => $value) {

            //网站SKU转换仓库SKU
            $sku = $ItemPlatformSku->getTrueSku($value['sku'], 2);
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
            $spreadsheet->getActiveSheet()->setCellValue("C" . ($key * 2 + 2), $itemArr[$sku]);
            $spreadsheet->getActiveSheet()->setCellValue("D" . ($key * 2 + 2), $value['sku']);
            
            $spreadsheet->getActiveSheet()->setCellValue("E" . ($key * 2 + 2), '右眼');
            $spreadsheet->getActiveSheet()->setCellValue("E" . ($key * 2 + 3), '左眼');

            $spreadsheet->getActiveSheet()->setCellValue("F" . ($key * 2 + 2), $value['od_sph'] > 0 ? ' +' . $value['od_sph'] : ' ' . $value['od_sph']);
            $spreadsheet->getActiveSheet()->setCellValue("F" . ($key * 2 + 3), $value['os_sph'] > 0 ? ' +' . $value['os_sph'] : ' ' . $value['os_sph']);

            $spreadsheet->getActiveSheet()->setCellValue("G" . ($key * 2 + 2), $value['od_cyl'] > 0 ? ' +' . $value['od_cyl'] : ' ' . $value['od_cyl']);
            $spreadsheet->getActiveSheet()->setCellValue("G" . ($key * 2 + 3), $value['os_cyl'] > 0 ? ' +' . $value['os_cyl'] : ' ' . $value['os_cyl']);

            $spreadsheet->getActiveSheet()->setCellValue("H" . ($key * 2 + 2), $value['od_axis']);
            $spreadsheet->getActiveSheet()->setCellValue("H" . ($key * 2 + 3), $value['os_axis']);

            if ($value['prescription_type'] == 'Reading Glasses' && strlen($value['os_add']) > 0 && strlen($value['od_add']) > 0) {
                // 双ADD值时，左右眼互换
                $spreadsheet->getActiveSheet()->setCellValue("I" . ($key * 2 + 2), $value['os_add']);
                $spreadsheet->getActiveSheet()->setCellValue("I" . ($key * 2 + 3), $value['od_add']);
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

    //批量导出xls
    public function batch_export_xls_test()
    {

        /*************修改为筛选导出****************/

        set_time_limit(0);
        ini_set('memory_limit', '512M');
        
        $str = '130033493
        130033462
        130033467
        130033484
        130033461
        130033468
        130033406
        130033421
        130033410
        130033336
        130033451
        130033368
        130033405
        130033320
        430091111
        130033487
        430091025
        130033500
        130033415
        130033470
        130033381
        130033498
        130033491
        130033059
        130033488
        130033433
        130033387
        130033411
        430091099
        430091024
        130033363
        130033346
        130033503
        130033471
        130033420
        130033416
        130033507
        130033497
        130033304
        430091085
        430091076
        430091045
        130033323
        130033388
        130033369
        130033522
        430090898
        430090896
        430090830
        430090711
        430091033
        430091098
        430091017
        430091003
        430090938
        430090835
        430091100
        430091050
        430090919
        430090692
        430091106
        430091028
        130033390
        430090954
        430091004
        430090667
        430090893
        430090884
        130033394
        430090967
        430091011
        430090957
        430090765
        430090694
        430090921
        430090612
        430090594
        430090909
        430091056
        430090688
        430090922
        430090789
        130033362
        130033480
        430090610
        430090839
        430090613
        430090609
        430090580
        430090589
        130033432
        430090419
        430090996
        130033499
        130033465
        130033331
        430090970
        430091096
        430090924
        430090718
        430090621
        130033309
        430090978
        430091079
        430090863
        130033441
        430090973
        430090861
        430090905
        430091095
        430090866
        430090833
        430090614
        430090907
        430090982
        430090512
        430090521
        430090528
        130033439
        430089860
        130033430
        430090805
        430091008
        430090785
        430090751
        430091108
        430091080
        430091065
        130033431
        430090794
        430090756
        430090729
        430090766
        430090702
        430090605
        430090516
        430090992
        430090646
        430090495
        130033328
        430090430
        430090760
        430090428
        430090433
        430090844
        130033345
        430090771
        430090514
        430090742
        430091867
        430091789
        430090413
        130033294
        430091861
        430090466
        430091854
        130033423
        130033463
        430090531
        430090719
        430090487
        430090478
        430090445
        430091653
        430090541
        430091103
        130033389
        430090326
        430090336
        430091856
        430090501
        430091026
        130033472
        430090821
        430090857
        430090647
        430090792
        430090748
        430090858
        430090658
        430090563
        430091046
        430090347
        430091871
        430090400
        430090684
        130033412
        430091620
        430091855
        430091624
        430090918
        430090935
        430090758
        430090674
        130033519
        430090676
        430090801
        130033337
        130033437
        130033454
        130033313
        430090850
        430090929
        430090959
        430090606
        430090508
        130033377
        430091052
        430090948
        430090911
        430090735
        430090953
        430090741
        430090818
        430090664
        430090619
        430090474
        430090414
        430091882
        430090966
        430091074
        430090356
        130033322
        430090476
        430091783
        430090554
        430090418
        130033297
        430090537
        430091814
        430091746
        430091626
        430090384
        430091721
        430091640
        430090576
        430091550
        430090724
        430090403
        430090399
        430090823
        430090599
        130033444
        130033380
        130033401
        430091668
        130033310
        430090990
        430091070
        430090637
        430090577
        430090379
        430090380
        430090371
        430091712
        430091555
        430091578
        430090456
        430090513
        430090562
        130033314
        430090452
        430090717
        430090639
        430090653
        430090663
        430090349
        430090335
        430090462
        430090341
        430090338
        430091893
        430091899
        430091650
        430091804
        430090342
        430091577
        430091566
        430091432
        430091738
        430091507
        430091639
        430091551
        430091486
        430091825
        430091522
        430091517
        430091717
        430091655
        130033298
        430091661
        430090708
        430091719
        430091691
        430090540
        430090871
        430090543
        430090351
        130033366
        430091885
        430091863
        430091864
        430090405
        430090360
        430090550
        430091054
        430090557
        430090649
        430090353
        430090358
        430091598
        430090560
        430091060
        430090683
        430091870
        430091898
        430091627
        430090449
        430090697
        430088856
        430090319
        130033408
        430091539
        430090607
        430090544
        430091403
        430091537
        430091638
        430091796
        130033296
        430090334
        430091467
        430091675
        430091490
        430091549
        430091792
        430091852
        430091901
        430091493
        430091515
        430091429
        430091447
        430091480
        430091582
        430091658
        430091677
        430091376
        430091810
        430091618
        430091708
        430091819
        430091646
        430090650
        430090366
        430090386
        430091408
        430091565
        430091409
        430090855
        430091769
        430090705
        430091412
        430091635
        430091665
        430091739
        430091790
        430091744
        430091662
        430091736
        430091829
        430091091
        430091832
        430091648
        430091521
        430091465
        430091538
        430091767
        430091453
        430090368
        430091788
        430091390
        430091436
        430091414
        430091667
        430091449
        430091474
        430091535
        430091727
        430091803
        430091589
        430091827
        430091800
        430091742
        430091427
        430091367
        430091362
        430091350
        430091362
        430091399
        430091805
        430091775
        430091895
        430091636
        430091559
        430090673
        430091385
        430091509
        430091670
        430091772
        430091713
        430091753
        430091762
        430091630
        430091526
        430090573
        430090443
        430091647
        430091476
        430091710
        430091520
        430091381
        430091370
        430092462
        430092476
        430092478
        430092470
        430092396
        430092487
        430092512
        430092441
        430092474
        430092355
        430092292
        430092451
        430092440
        430092306
        430092256
        430092468
        430091234
        430092417
        430092266
        430092351
        430092326
        430092379
        430092293
        430092359
        430092389
        430092459
        430092348
        430092197
        430092273
        430092248
        430092286
        430092238
        430092312
        430092472
        430092424
        430092192
        430092350
        430092202
        430092338
        430092373
        430092450
        430091300
        430091261
        430091244
        430092307
        430092276
        430092264
        430091338
        430092308
        430091247
        430091146
        430091137
        430091144
        430092447
        430092471
        430092457
        430092358
        430091130
        430091270
        430092207
        430092240
        430092242
        130033594
        430091282
        130033568
        130033729
        430092394
        430092407
        430091280
        430091266
        430092198
        430091153
        430092363
        430092212
        430091245
        430092241
        430092199
        430092205
        430091329
        430092243
        430091115
        430091119
        430091215
        430092486
        430092452
        430092412
        430092449
        430092235
        430092203
        430091318
        430091207
        430091296
        430091228
        430091149
        430091294
        430091254
        430091216
        430091272
        430091197
        130033583
        430092261
        430091163
        130033735
        130033550
        130033715
        130033574
        130033605
        130033585
        430092353
        430092280
        430092399
        130033528
        130033604
        130033572
        430091259
        430091235
        130033737
        130033673
        130033706
        130033693
        130033602
        130033667
        430092464
        430092497
        430091208
        130033571
        430092316
        130033543
        430092385
        430092375
        430091164
        430092220        
        ';
        $str = explode('
        ',$str);
        
        $map['sfo.increment_id'] = ['in',$str];

        list($where) = $this->buildparams();
        $field = 'sfo.increment_id,sfoi.product_options,sfoi.order_id,sfo.`status`,sfoi.sku,sfoi.product_id,sfoi.qty_ordered,sfo.created_at';
        $resultList = $this->model->alias('sfo')
            ->join(['sales_flat_order_item' => 'sfoi'], 'sfoi.order_id=sfo.entity_id')
            ->field($field)
            ->where($map)
            ->where($where)
            ->order('sfoi.order_id desc')
            ->select();

        $resultList = collection($resultList)->toArray();

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

        $ItemPlatformSku = new \app\admin\model\itemmanage\ItemPlatformSku;

        //查询商品管理SKU对应ID
        $item = new \app\admin\model\itemmanage\Item;
        $itemArr = $item->where('is_del',1)->column('id','sku');

        foreach ($finalResult as $key => $value) {

            //网站SKU转换仓库SKU
            $sku = $ItemPlatformSku->getTrueSku($value['sku'], 2);
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
            $spreadsheet->getActiveSheet()->setCellValue("C" . ($key * 2 + 2), $itemArr[$sku]);
            $spreadsheet->getActiveSheet()->setCellValue("D" . ($key * 2 + 2), $value['sku']);
            
            $spreadsheet->getActiveSheet()->setCellValue("E" . ($key * 2 + 2), '右眼');
            $spreadsheet->getActiveSheet()->setCellValue("E" . ($key * 2 + 3), '左眼');

            $spreadsheet->getActiveSheet()->setCellValue("F" . ($key * 2 + 2), $value['od_sph'] > 0 ? ' +' . $value['od_sph'] : ' ' . $value['od_sph']);
            $spreadsheet->getActiveSheet()->setCellValue("F" . ($key * 2 + 3), $value['os_sph'] > 0 ? ' +' . $value['os_sph'] : ' ' . $value['os_sph']);

            $spreadsheet->getActiveSheet()->setCellValue("G" . ($key * 2 + 2), $value['od_cyl'] > 0 ? ' +' . $value['od_cyl'] : ' ' . $value['od_cyl']);
            $spreadsheet->getActiveSheet()->setCellValue("G" . ($key * 2 + 3), $value['os_cyl'] > 0 ? ' +' . $value['os_cyl'] : ' ' . $value['os_cyl']);

            $spreadsheet->getActiveSheet()->setCellValue("H" . ($key * 2 + 2), $value['od_axis']);
            $spreadsheet->getActiveSheet()->setCellValue("H" . ($key * 2 + 3), $value['os_axis']);

            if ($value['prescription_type'] == 'Reading Glasses' && strlen($value['os_add']) > 0 && strlen($value['od_add']) > 0) {
                // 双ADD值时，左右眼互换
                $spreadsheet->getActiveSheet()->setCellValue("I" . ($key * 2 + 2), $value['os_add']);
                $spreadsheet->getActiveSheet()->setCellValue("I" . ($key * 2 + 3), $value['od_add']);
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
        // echo 'batch_print_label';
        $entity_ids = rtrim(input('id_params'), ',');
        // dump($entity_ids);
        if ($entity_ids) {
            $processing_order_querySql = "select sfo.increment_id,round(sfo.total_qty_ordered,0) NUM,sfoi.product_options,sfoi.order_id,sfo.`status`,sfoi.sku,sfoi.qty_ordered,sfo.created_at
from sales_flat_order_item sfoi
left join sales_flat_order sfo on  sfoi.order_id=sfo.entity_id 
where sfo.`status` in ('processing','creditcard_proccessing','free_processing','complete','paypal_reversed','paypal_canceled_reversal') and sfo.entity_id in($entity_ids)
order by sfoi.order_id desc;";
            $processing_order_list = Db::connect('database.db_voogueme')->query($processing_order_querySql);
            // dump($processing_order_list);
            $processing_order_list = $this->qty_order_check($processing_order_list);
            
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
            $cargo_number = $store_sku->alias('a')->where(['status' => 1,'b.is_del' => 1])->join(['fa_store_sku' => 'b'], 'a.id=b.store_id')->column('coding', 'sku');

            //查询sku映射表
            $item = new \app\admin\model\itemmanage\ItemPlatformSku;
            $item_res = $item->cache(3600)->column('sku', 'platform_sku');

            $file_content = '';
            $temp_increment_id = 0;
            foreach ($processing_order_list as $processing_key => $processing_value) {
                if ($temp_increment_id != $processing_value['increment_id']) {
                    $temp_increment_id = $processing_value['increment_id'];

                    $date = substr($processing_value['created_at'], 0, strpos($processing_value['created_at'], " "));
                    $fileName = ROOT_PATH . "public" . DS . "uploads" . DS . "printOrder" . DS . "voogueme" . DS . "$date" . DS . "$temp_increment_id.png";
                    // dump($fileName);
                    $dir = ROOT_PATH . "public" . DS . "uploads" . DS . "printOrder" . DS . "voogueme" . DS . "$date";
                    if (!file_exists($dir)) {
                        mkdir($dir, 0777, true);
                        // echo '创建文件夹$dir成功';
                    } else {
                        // echo '需创建的文件夹$dir已经存在';
                    }
                    $img_url = "/uploads/printOrder/voogueme/$date/$temp_increment_id.png";
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
                if ($final_print['prescription_type'] == 'Reading Glasses' && strlen($final_print['os_add']) > 0 && strlen($final_print['od_add']) > 0) {
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
                if ($item_res[$processing_value['sku']] && $cargo_number[$item_res[$processing_value['sku']]]) {
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
