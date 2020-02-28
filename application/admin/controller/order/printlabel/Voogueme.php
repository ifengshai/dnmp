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
            $swhere['order_platform'] = 2;
            $swhere['synergy_order_id'] = 2;
            $order_arr = $infoSynergyTask->where($swhere)->order('create_time desc')->column('synergy_order_number');
            $map['sfo.increment_id'] = ['in', $order_arr];
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
        
        $str = '430091226
        130033720
        130033759
        130033683
        130033666
        130033746
        430092524
        430092538
        430091315
        130033608
        430091145
        130033657
        430070322
        430083059
        430091275
        130033727
        130033728
        130033655
        130033634
        430092529
        430090740
        430091288
        430092526
        130033711
        430091236
        130033538
        430092216
        130033663
        130033682
        130033634
        430092518
        130033705
        430091348
        130033283
        430092537
        430091335
        130033721
        430092258
        430092260
        130033276
        130033293
        430092522
        130033708
        130033609
        130033686
        430092428
        130033525
        430091243
        130033612
        430090128
        130033268
        430087011
        430090283
        430090301
        430077464
        430091931
        130033275
        130033264
        130033103
        130033650
        130033582
        430092310
        130032999
        430092514
        430091930
        430077412
        430091959
        130033260
        130033529
        130032980
        430090292
        130033091
        130033160
        430090170
        130033287
        130033703
        430090262
        430091106
        430090316
        130033056
        130033593
        430092188
        430091962
        130033573
        130033725
        130032953
        430092206
        130033718
        130033652
        430091346
        130032936
        430091940
        430092543
        130033280
        430091968
        130032994
        430091908
        130032945
        130033671
        430092189
        430092164
        430091957
        430092542
        130032973
        430090182
        130032932
        130033023
        430090311
        430090289
        430090296
        130033739
        430091202
        430086394
        130033700
        430090217
        430092176
        430091932
        130033107
        130032987
        130032961
        430090277
        430090194
        130033575
        430090242
        130033135
        130032971
        130033288
        130032910
        430091950
        430090127
        430090171
        430090297
        130033021
        430090144
        130031302
        130031242
        130033029
        130033011
        130033033
        130033105
        130033117
        130033173
        130033078
        430090198
        430090203
        130033175
        430090240
        130032991
        430090162
        430092535
        130033192
        130033045
        130033174
        130033189
        430092465
        130033178
        430090310
        130032911
        130032947
        430090227
        430092157
        430092251
        130033163
        430090254
        130033143
        130033099
        130033112
        130033738
        430092406
        430091918
        430092166
        130033599
        130033040
        130033849
        130033723
        430092160
        430092246
        130033714
        130033712
        430091176
        130033548
        430091316
        430092278
        430091225
        130033647
        130033692
        130033202
        430091289
        430091960
        430092162
        430090272
        430091248
        430091306
        430091910
        430090214
        430090233
        130032972
        130033100
        130032977
        430091171
        130033186
        430090275
        130033064
        130033126
        130033187
        130033190
        130033219
        130033853
        130033251
        130033221
        130033818
        130033249
        130033019
        130033799
        430092140
        430090314
        130032937
        130033237
        130033819
        130033827
        130033779
        130033780
        130033831
        130033240
        430091157
        430090219
        430091919
        430090263
        130032976
        130032921
        430090255
        430090167
        130033095
        130033835
        430090223
        130033810
        430090291
        130033234
        430090315
        130033773
        130033195
        130033805
        130032955
        130033843
        430090156
        430092036
        430092053
        430092116
        430092069
        130033569
        430090184
        430092134
        130033845
        130033156
        430092111
        430092001
        130033844
        130033235
        430092121
        130033783
        130033828
        430092005
        130033176
        130033132
        130033238
        130033211
        130033169
        430092114
        430092006
        430092118
        130033772
        130033256
        430092059
        130033197
        130033179
        430092150
        130033225
        130033850
        130033218
        130033546
        130033695
        430092108
        130033833
        430092106
        430092039
        430092033
        130033253
        130033257
        130033557
        130032957
        130033630
        430092182
        130032916
        430092042
        430092091
        430092089
        430092008
        430091980
        130032948
        430090146
        430090245
        430091117
        130033024
        130033087
        130032968
        430092013
        430092163
        430090267
        430090261
        430091928
        430091921
        130032922
        430090148
        430092090
        430092063
        430092081
        430092130
        130033016
        130033062
        430092088
        130033793
        430092086
        430090300
        430091986
        130033151
        430092040
        430092154
        130033071
        430090196
        430092082
        430092030
        430092025
        430091996
        430091979
        430091972
        130033817
        430092141
        430092075
        130033243
        430092041
        430092037
        430091985
        430091994
        430092099
        430092102
        130033233
        430092093
        430092480
        130033215
        130033842
        130033801
        130033825
        430092064
        130033183
        430090124
        430092084
        130033177
        130033203
        130033776
        130033193
        430092016
        430091992
        430092122
        430091989
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
