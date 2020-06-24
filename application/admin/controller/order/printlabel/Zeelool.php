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
use app\admin\model\OrderLog;
use app\admin\model\StockLog;

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

    protected $searchFields = 'entity_id';

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

            $filter = json_decode($this->request->get('filter'), true);

            if ($filter['increment_id']) {
                $map['status'] = ['in', ['free_processing', 'processing', 'complete', 'paypal_reversed', 'paypal_canceled_reversal']];
            } elseif (!$filter['status']) {
                $map['status'] = ['in', ['free_processing', 'processing', 'paypal_reversed', 'paypal_canceled_reversal']];
            }

            //是否有工单
            $workorder = new \app\admin\model\saleaftermanage\WorkOrderList();
            if ($filter['is_task'] == 1 || $filter['is_task'] == '0') {
                $swhere = [];
                $swhere['work_platform'] = 1;
                $swhere['work_status'] = ['not in', [0, 4, 6]];
                $order_arr = $workorder->where($swhere)->column('platform_order');
                if ($filter['is_task'] == 1) {
                    $map['increment_id'] = ['in', $order_arr];
                } elseif ($filter['is_task'] == '0') {
                    $map['increment_id'] = ['not in', $order_arr];
                }
                unset($filter['is_task']);
                $this->request->get(['filter' => json_encode($filter)]);
            }

            //是否有协同任务
            $infoSynergyTask = new \app\admin\model\infosynergytaskmanage\InfoSynergyTask;
            if ($filter['task_label'] == 1 || $filter['task_label'] == '0') {
                $swhere = [];
                $swhere['is_del'] = 1;
                $swhere['order_platform'] = 1;
                $swhere['synergy_order_id'] = 2;
                $order_arr = $infoSynergyTask->where($swhere)->order('create_time desc')->column('synergy_order_number');
                if ($filter['task_label'] == 1) {
                    $map['increment_id'] = ['in', $order_arr];
                } elseif ($filter['task_label'] == '0') {
                    $map['increment_id'] = ['not in', $order_arr];
                }
                unset($filter['task_label']);
                $this->request->get(['filter' => json_encode($filter)]);
            }

            //任务分类id搜索
            if ($filter['category_id']) {
                $swhere = [];
                $swhere['problem_type_id'] = $filter['category_id'];
                $swhere['work_type'] = $filter['p_id'];
                $swhere['work_platform'] = 1;
                $order_arr = $workorder->where($swhere)->column('platform_order');
                $map['increment_id'] = ['in', $order_arr];
                unset($filter['category_id']);
                unset($filter['p_id']);
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
            $field = 'order_type,custom_order_prescription_type,entity_id,status,base_shipping_amount,increment_id,base_grand_total,
                     total_qty_ordered,custom_is_match_frame_new,custom_is_match_lens_new,
                     custom_is_send_factory_new,custom_is_delivery_new,custom_print_label_new,custom_order_prescription,created_at';
            $list = $this->model
                ->field($field)
                ->where($map)
                ->where($where)
                ->order($sort, $order)
                ->limit($offset, $limit)
                ->select();

            $list = collection($list)->toArray();
            //查询订单是否存在工单
            $swhere = [];
            $increment_ids = array_column($list, 'increment_id');
            $swhere['platform_order'] = ['in', $increment_ids];
            $swhere['work_platform'] = 1;
            $swhere['work_status'] = ['not in', [0, 4, 6]];
            $order_arr = $workorder->where($swhere)->column('platform_order');


            //查询是否存在协同任务
            $swhere = [];
            $swhere['synergy_order_number'] = ['in', $increment_ids];
            $swhere['is_del'] = 1;
            $swhere['order_platform'] = 1;
            $swhere['synergy_order_id'] = 2;
            $synergy_order_arr = $infoSynergyTask->where($swhere)->column('synergy_order_number');

            foreach ($list as $k => $v) {
                if (in_array($v['increment_id'], $order_arr)) {
                    $list[$k]['task_info'] = 1;
                }

                if (in_array($v['increment_id'], $synergy_order_arr)) {
                    $list[$k]['is_task_info'] = 1;
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
                $map['status'] = ['in', ['free_processing', 'processing', 'complete', 'paypal_reversed', 'paypal_canceled_reversal']];
                $field = 'order_type,custom_order_prescription_type,entity_id,status,base_shipping_amount,increment_id,base_grand_total,
                     total_qty_ordered,custom_is_match_frame_new,custom_is_match_lens_new,
                     custom_is_send_factory_new,custom_is_delivery_new,custom_print_label_new,custom_order_prescription,created_at';
                $list = $this->model
                    ->field($field)
                    ->where($map)
                    ->find();
                if ($list) {
                    //查询订单是否存在协同任务
                    $workorder = new \app\admin\model\saleaftermanage\WorkOrderList();
                    $swhere['platform_order'] = $increment_id;
                    $swhere['work_platform'] = 1;
                    $swhere['work_status'] = ['not in', [0, 4, 6]];
                    $count = $workorder->where($swhere)->count();
                    //查询是否存在协同任务
                    $infoSynergyTask = new \app\admin\model\infosynergytaskmanage\InfoSynergyTask;
                    $swhere = [];
                    $swhere['synergy_order_number'] = $increment_id;
                    $swhere['is_del'] = 1;
                    $swhere['order_platform'] = 1;
                    $swhere['synergy_order_id'] = 2;
                    $info_count = $infoSynergyTask->where($swhere)->count();
                    if ($count > 0) {
                        $list['task_info'] = 1;
                    }

                    if ($info_count > 0) {
                        $list['is_task_info'] = 1;
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

    /**
     * 详情
     */
    public function detail($ids = null)
    {
        $row = $this->model->get($ids);
        if (!$row) {
            $this->error(__('No Results were found'));
        }
        //查询订单详情
        $result = ZeeloolPrescriptionDetailHelper::get_one_by_entity_id($ids);
        $this->assign('result', $result);
        return $this->view->fetch();
    }

    /**
     * 标记为已打印标签
     */
    public function tag_printed()
    {
        $entity_ids = input('id_params/a');
        $label = input('label');
        if (!$entity_ids) {
            $this->error('缺少参数');
        }

        //多数据库
        $map['entity_id'] = ['in', $entity_ids];
        $data['custom_print_label_new'] = 1;
        $data['custom_print_label_created_at_new'] = date('Y-m-d H:i:s', time());
        $data['custom_print_label_person_new'] =  session('admin.nickname');
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
        if (false !== $result) {
            //操作日志
            $params['type'] = 1;
            $params['num'] = count($entity_ids);
            $params['order_ids'] = implode(',', $entity_ids);
            $params['site'] = 1;
            (new OrderLog())->setOrderLog($params);

            $map['entity_id'] = ['in', $entity_ids];
            $res = $this->model->field('entity_id,increment_id')->where($map)->select();
            //插入订单节点
            $data = [];
            $list = [];
            foreach ($res as $k => $v) {
                $data['update_time'] = date('Y-m-d H:i:s');
                //打标签
                $list[$k]['order_node'] = 1;
                $list[$k]['node_type'] = 2; //配镜架
                $list[$k]['content'] = 'Order is under processing';
                $list[$k]['create_time'] = date('Y-m-d H:i:s');
                $list[$k]['site'] = 1;
                $list[$k]['order_id'] = $v['entity_id'];
                $list[$k]['order_number'] = $v['increment_id'];
                $list[$k]['handle_user_id'] = session('admin.id');
                $list[$k]['handle_user_name'] = session('admin.nickname');;

                $data['order_node'] = 1;
                $data['node_type'] = 2;
                Db::name('order_node')->where(['order_id' => $v['entity_id'], 'site' => 1])->update($data);
            }
            if ($list) {
                $ordernodedetail = new \app\admin\model\OrderNodeDetail();
                $ordernodedetail->saveAll($list);
            }

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
        $map['entity_id'] = ['in', $entity_ids];
        $order_res = $this->model->field('entity_id,increment_id,custom_is_match_frame_new,custom_is_delivery_new,custom_is_match_frame_new')->where($map)->select();
        if (!$order_res) {
            $this->error('未查询到订单数据！！');
        }
        $orderList = [];
        foreach ($order_res as $v) {
            if ($status == 1 && $v['custom_is_match_frame_new'] == 1) {
                $this->error('存在已配过镜架的订单！！');
            }
            if ($v['custom_is_delivery_new'] == 1) {
                $this->error('存在已质检通过的订单！！');
            }

            if ($status == 4 && $v['custom_is_match_frame_new'] == 0) {
                $this->error('存在未配镜架的订单！！');
            }

            $orderList[$v['increment_id']] = $v['entity_id'];
        }

        //判断订单是否存在未处理完成的工单
        $arr = array_column($order_res, 'increment_id');
        $workorder = new \app\admin\model\saleaftermanage\WorkOrderList();
        $count = $workorder->where([
            'platform_order' => ['in', $arr],
            'work_status' => ['in', [1, 2, 3, 5]],
            'work_platform' => 1
        ])->count();
        if ($count > 0) {
            $this->error('存在未处理的工单');
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
                //查询出质检通过的订单
                $list = $this->model->alias('a')->where($map)->field('a.increment_id,b.sku,b.qty_ordered')->join(['sales_flat_order_item' => 'b'], 'a.entity_id = b.order_id')->select();
                if (!$list) {
                    throw new Exception("未查询到订单数据！！");
                };
                //sku映射表
                $ItemPlatformSku = new \app\admin\model\itemmanage\ItemPlatformSku;
                $infotask = new \app\admin\model\saleaftermanage\WorkOrderChangeSku();

                //查询是否存在更换镜架的订单
                $infoRes = $infotask->field('sum(change_number) as qty,change_sku,original_sku,increment_id')
                    ->where([
                        'increment_id' => ['in', $arr],
                        'change_type' => 1,    //更改类型 1更改镜架
                        'platform_type' => 1, //平台类型
                    ])
                    ->group('change_sku')
                    ->select();
                $sku = [];
                if ($infoRes) {
                    foreach ($infoRes as $k => $v) {
                        //sku转换
                        $trueSku = $ItemPlatformSku->getTrueSku(trim($v['change_sku']), 1);
                        if (!$trueSku) {
                            throw new Exception("增加配货占用库存失败！！请检查更换镜框SKU:" . $v['change_sku'] . ',订单号：' . $v['increment_id']);
                        }
                        // //判断是否有实时库存
                        // $realStock = $item->getRealStock($trueSku);
                        // if ($v['qty'] > $realStock) {
                        //     throw new Exception("SKU:" . $v['change_sku'] . "实时库存不足,订单号：" . $v['increment_id']);
                        // }
                        //增加配货占用
                        $map = [];
                        $map['sku'] = $trueSku;
                        $map['is_del'] = 1;
                        $res = $item->where($map)->setInc('distribution_occupy_stock', $v['qty']);
                        if (false === $res) {
                            throw new Exception("增加配货占用库存失败！！请检查更换镜框SKU:" . $v['change_sku'] . ',订单号：' . $v['increment_id']);
                        }
                        $sku[$v['increment_id']][$v['original_sku']] += $v['qty'];

                        //插入日志表
                        (new StockLog())->setData([
                            'type'                      => 2,
                            'site'                      => 1,
                            'two_type'                  => 1,
                            'sku'                       => $trueSku,
                            'order_number'              => $v['increment_id'],
                            'public_id'                 => $orderList[$v['increment_id']],
                            'distribution_stock_change' => $v['qty'],
                            'create_person'             => session('admin.nickname'),
                            'create_time'               => date('Y-m-d H:i:s'),
                            'remark'                    => '配镜架增加配货占用库存,存在更换镜框工单'
                        ]);
                    }
                }
                //查出订单SKU映射表对应的仓库SKU
                $number = 0;
                foreach ($list as $k => &$v) {
                    //转仓库SKU
                    $trueSku = $ItemPlatformSku->getTrueSku(trim($v['sku']), 1);
                    if (!$trueSku) {
                        throw new Exception("增加配货占用库存失败！！请检查SKU:" . $v['sku'] . ',订单号：' . $v['increment_id']);
                    }

                    //如果为真 则存在更换镜架的数量 则订单需要扣减的数量为原数量-更换镜架的数量
                    if ($sku[$v['increment_id']][$v['sku']]) {
                        $qty = $v['qty_ordered'] - $sku[$v['increment_id']][$v['sku']];
                        $qty = $qty > 0 ? $qty : 0;
                    } else {
                        $qty = $v['qty_ordered'];
                    }

                    if ($qty == 0) {
                        continue;
                    }

                    // //判断是否有实时库存
                    // $realStock = $item->getRealStock($trueSku);
                    // if ($qty > $realStock) {
                    //     throw new Exception("SKU:" . $v['sku'] . "实时库存不足". ',订单号：' . $v['increment_id']);
                    // }

                    $map = [];
                    $map['sku'] = $trueSku;
                    $map['is_del'] = 1;
                    //增加配货占用
                    $res = $item->where($map)->setInc('distribution_occupy_stock', $qty);
                    if (false === $res) {
                        throw new Exception("增加配货占用库存失败！！请检查SKU:" . $v['sku'] . ',订单号：' . $v['increment_id']);
                    }

                    $number++;
                    //100条提交一次
                    if ($number == 100) {
                        $item->commit();
                        $number = 0;
                    }

                    //插入日志表
                    (new StockLog())->setData([
                        'type'                      => 2,
                        'site'                      => 1,
                        'two_type'                  => 1,
                        'sku'                       => $trueSku,
                        'order_number'              => $v['increment_id'],
                        'public_id'                 => $orderList[$v['increment_id']],
                        'distribution_stock_change' => $qty,
                        'create_person'             => session('admin.nickname'),
                        'create_time'               => date('Y-m-d H:i:s'),
                        'remark'                    => '配镜架增加配货占用库存'
                    ]);
                }
                unset($v);
                $item->commit();
            }

            //质检通过扣减库存
            if ($status == 4) {
                //查询出质检通过的订单
                $list = $this->model->alias('a')->where($map)->field('a.increment_id,b.sku,b.qty_ordered')->join(['sales_flat_order_item' => 'b'], 'a.entity_id = b.order_id')->select();
                if (!$list) {
                    throw new Exception("未查询到订单数据！！");
                };
                //sku映射表
                $ItemPlatformSku = new \app\admin\model\itemmanage\ItemPlatformSku;
                $infotask = new \app\admin\model\saleaftermanage\WorkOrderChangeSku();
                //查询是否存在更换镜架的订单
                $infoRes = $infotask->field('sum(change_number) as qty,change_sku,original_sku,increment_id')
                    ->where([
                        'increment_id' => ['in', $arr],
                        'change_type' => 1,    //更改类型 1更改镜架
                        'platform_type' => 1, //平台类型
                    ])
                    ->group('change_sku')
                    ->select();
                $infoRes = collection($infoRes)->toArray();
                $sku = [];
                if ($infoRes) {
                    foreach ($infoRes as $k => $v) {
                        $trueSku = $ItemPlatformSku->getTrueSku(trim($v['change_sku']), 1);
                        if (!$trueSku) {
                            throw new Exception("扣减库存失败！！请检查更换镜框SKU:" . $v['sku'] . ',订单号：' . $v['increment_id']);
                        }
                        //扣减总库存 扣减占用库存 扣减配货占用
                        $map = [];
                        $map['sku'] = $trueSku;
                        $map['is_del'] = 1;
                        $res = $item->where($map)->dec('stock', $v['qty'])->dec('occupy_stock', $v['qty'])->dec('distribution_occupy_stock', $v['qty'])->update();
                        if (false === $res) {
                            throw new Exception("扣减库存失败！！请检查更换镜框SKU:" . $v['sku'] . ',订单号：' . $v['increment_id']);
                        }
                        $sku[$v['increment_id']][$v['original_sku']] += $v['qty'];

                        //插入日志表
                        (new StockLog())->setData([
                            'type'                      => 2,
                            'site'                      => 1,
                            'two_type'                  => 2,
                            'sku'                       => $trueSku,
                            'order_number'              => $v['increment_id'],
                            'public_id'                 => $orderList[$v['increment_id']],
                            'distribution_stock_change' => -$v['qty'],
                            'stock_change'              => -$v['qty'],
                            'occupy_stock_change'       => -$v['qty'],
                            'create_person'             => session('admin.nickname'),
                            'create_time'               => date('Y-m-d H:i:s'),
                            'remark'                    => '质检通过减少配货占用库存,减少总库存,减少订单占用库存,存在更换镜框工单'
                        ]);
                    }
                }
                $number = 0; //记录更新次数
                foreach ($list as &$v) {
                    //查出订单SKU映射表对应的仓库SKU
                    $trueSku = $ItemPlatformSku->getTrueSku(trim($v['sku']), 1);
                    if (!$trueSku) {
                        throw new Exception("扣减库存失败！！请检查SKU:" . $v['sku'] . ',订单号：' . $v['increment_id']);
                    }
                    //如果为真 则存在更换镜架的数量 则订单需要扣减的数量为原数量-更换镜架的数量
                    if ($sku[$v['increment_id']][$v['sku']]) {
                        $qty = $v['qty_ordered'] - $sku[$v['increment_id']][$v['sku']];
                        $qty = $qty > 0 ? $qty : 0;
                    } else {
                        $qty = $v['qty_ordered'];
                    }
                    if ($qty == 0) {
                        continue;
                    }

                    //总库存
                    $item_map['sku'] = $trueSku;
                    $item_map['is_del'] = 1;
                    //扣减总库存 扣减占用库存 扣减配货占用
                    $res = $item->where($item_map)->dec('stock', $qty)->dec('occupy_stock', $qty)->dec('distribution_occupy_stock', $qty)->update();
                    if (false === $res) {
                        throw new Exception("扣减库存失败！！请检查SKU:" . $v['sku'] . ',订单号：' . $v['increment_id']);
                    }
                    $number++;
                    //100条提交一次
                    if ($number == 100) {
                        $item->commit();
                        $number = 0;
                    }
                    //插入日志表
                    (new StockLog())->setData([
                        'type'                      => 2,
                        'site'                      => 1,
                        'two_type'                  => 2,
                        'sku'                       => $trueSku,
                        'order_number'              => $v['increment_id'],
                        'public_id'                 => $orderList[$v['increment_id']],
                        'distribution_stock_change' => -$qty,
                        'stock_change'              => -$qty,
                        'occupy_stock_change'       => -$qty,
                        'create_person'             => session('admin.nickname'),
                        'create_time'               => date('Y-m-d H:i:s'),
                        'remark'                    => '质检通过减少配货占用库存,减少总库存,减少订单占用库存'
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
            //添加日志
            $params['num'] = count($entity_ids);
            $params['order_ids'] = implode(',', $entity_ids);
            $params['site'] = 1;
            (new OrderLog())->setOrderLog($params);

            //插入订单节点
            $data = [];
            $list = [];
            foreach ($order_res as $k => $v) {
                $data['update_time'] = date('Y-m-d H:i:s');

                $list[$k]['create_time'] = date('Y-m-d H:i:s');
                $list[$k]['site'] = 1;
                $list[$k]['order_id'] = $v['entity_id'];
                $list[$k]['order_number'] = $v['increment_id'];
                $list[$k]['handle_user_id'] = session('admin.id');
                $list[$k]['handle_user_name'] = session('admin.nickname');

                //配镜架
                if ($status == 1) {
                    $list[$k]['order_node'] = 2;
                    $list[$k]['node_type'] = 3; //配镜架
                    $list[$k]['content'] = 'Frame(s) is/are ready, waiting for lenses';

                    $data['order_node'] = 2;
                    $data['node_type'] = 3;
                }

                //配镜片
                if ($status == 2) {
                    $list[$k]['order_node'] = 2;
                    $list[$k]['node_type'] = 4; //配镜片
                    $list[$k]['content'] = 'Lenses production completed, waiting for customizing';

                    $data['order_node'] = 2;
                    $data['node_type'] = 4;
                }

                //加工
                if ($status == 3) {
                    $list[$k]['order_node'] = 2;
                    $list[$k]['node_type'] = 5; //加工
                    $list[$k]['content'] = 'Customizing completed, waiting for Quality Inspection';

                    $data['order_node'] = 2;
                    $data['node_type'] = 5;
                }

                //质检
                if ($status == 4) {
                    $list[$k]['order_node'] = 2;
                    $list[$k]['node_type'] = 6; //加工
                    $list[$k]['content'] = 'Quality Inspection completed, preparing to dispatch this mail piece.';

                    $data['order_node'] = 2;
                    $data['node_type'] = 6;
                }

                Db::name('order_node')->where(['order_id' => $v['entity_id'], 'site' => 1])->update($data);
            }
            if ($list) {
                $ordernodedetail = new \app\admin\model\OrderNodeDetail();
                $ordernodedetail->saveAll($list);
            }

            //用来判断是否从_list列表页进来
            if ($label == 'list') {
                //订单号
                $map = [];
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

    /**
     * 操作记录
     */
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


    /**
     * 生成条形码
     */
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

    /**
     * 获取镜架尺寸
     */
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
        set_time_limit(0);
        ini_set('memory_limit', '512M');

        $ids = input('id_params');

        $filter = json_decode($this->request->get('filter'), true);

        if ($filter['increment_id']) {
            $map['sfo.status'] = ['in', ['free_processing', 'processing', 'complete', 'paypal_reversed', 'paypal_canceled_reversal']];
        } elseif (!$filter['status'] && !$ids) {
            $map['status'] = ['in', ['free_processing', 'processing',  'paypal_reversed', 'paypal_canceled_reversal']];
        }
        //是否有工单
        $workorder = new \app\admin\model\saleaftermanage\WorkOrderList();
        if ($filter['is_task'] == 1 || $filter['is_task'] == '0') {
            $swhere = [];
            $swhere['work_platform'] = 1;
            $swhere['work_status'] = ['<>', 0];
            $order_arr = $workorder->where($swhere)->column('platform_order');
            if ($filter['is_task'] == 1) {
                $map['increment_id'] = ['in', $order_arr];
            } elseif ($filter['is_task'] == '0') {
                $map['increment_id'] = ['not in', $order_arr];
            }
            unset($filter['is_task']);
            $this->request->get(['filter' => json_encode($filter)]);
        }
        //是否有协同任务
        $infoSynergyTask = new \app\admin\model\infosynergytaskmanage\InfoSynergyTask;
        if ($filter['task_label'] == 1 || $filter['task_label'] == '0') {
            $swhere['is_del'] = 1;
            $swhere['order_platform'] = 1;
            $swhere['synergy_order_id'] = 2;
            $order_arr = $infoSynergyTask->where($swhere)->order('create_time desc')->column('synergy_order_number');
            if ($filter['task_label'] == 1) {
                $map['sfo.increment_id'] = ['in', $order_arr];
            } elseif ($filter['task_label'] == '0') {
                $map['sfo.increment_id'] = ['not in', $order_arr];
            }
            unset($filter['task_label']);
            $this->request->get(['filter' => json_encode($filter)]);
        }

        //协同任务分类id搜索
        if ($filter['category_id'] || $filter['c_id']) {
            $swhere['is_del'] = 1;
            $swhere['order_platform'] = 1;
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
            $map['sku'] = ['like', '%' . $filter['sku'] . '%'];
            unset($filter['sku']);
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

        list($where) = $this->buildparams();
        $field = 'sfo.is_new_version,sfo.increment_id,sfoi.product_options,total_qty_ordered as NUM,sfoi.order_id,sfo.`status`,sfoi.sku,sfoi.product_id,sfoi.qty_ordered,sfo.created_at';
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
            //新处方
            if ($value['is_new_version'] == 1) {
                //镀膜
                $finalResult[$key]['coatiing_name'] = $tmp_product_options['info_buyRequest']['tmplens']['coating_name'];
                //镜片类型
                $finalResult[$key]['index_type'] = $tmp_product_options['info_buyRequest']['tmplens']['lens_data_name'];
                //镜片类型拼接颜色字段
                if ($tmp_product_options['info_buyRequest']['tmplens']['color_id']) {
                    $finalResult[$key]['index_type'] .= '-' . $tmp_product_options['info_buyRequest']['tmplens']['color_data_name'];
                }
            } else {
                $finalResult[$key]['coatiing_name'] = $tmp_product_options['info_buyRequest']['tmplens']['coatiing_name'];
                $finalResult[$key]['index_type'] = $tmp_product_options['info_buyRequest']['tmplens']['index_type'];
                //镜片类型拼接颜色字段
                if ($tmp_product_options['info_buyRequest']['tmplens']['color_name']) {
                    $finalResult[$key]['index_type'] .= '-' . $tmp_product_options['info_buyRequest']['tmplens']['color_name'];
                }
            }

            //如果为太阳镜 拼接颜色
            if (@$tmp_product_options['info_buyRequest']['tmplens']['sungless_color_name']) {
                $finalResult[$key]['index_type'] .= '-' . $tmp_product_options['info_buyRequest']['tmplens']['sungless_color_name'];
            }


            $tmp_prescription_params = $tmp_product_options['info_buyRequest']['tmplens']['prescription'];
            if (isset($tmp_prescription_params)) {
                $tmp_prescription_params = explode("&", $tmp_prescription_params);
                $tmp_lens_params = array();
                foreach ($tmp_prescription_params as $tmp_key => $tmp_value) {
                    $arr_value = explode("=", $tmp_value);
                    if (isset($arr_value[1])) {
                        $tmp_lens_params[$arr_value[0]] = $arr_value[1];
                    }
                }
            }

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


            $tmp_bridge = $this->get_frame_lens_width_height_bridge($value['product_id']);
            $finalResult[$key]['lens_width'] = $tmp_bridge['lens_width'];
            $finalResult[$key]['lens_height'] = $tmp_bridge['lens_height'];
            $finalResult[$key]['bridge'] = $tmp_bridge['bridge'];
            $finalResult[$key]['is_new_version'] = $value['is_new_version'];
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

        $ItemPlatformSku = new \app\admin\model\itemmanage\ItemPlatformSku;

        //查询商品管理SKU对应ID
        $item = new \app\admin\model\itemmanage\Item;
        $itemArr = $item->where('is_del', 1)->column('id', 'sku');

        foreach ($finalResult as $key => $value) {
            //网站SKU转换仓库SKU
            $sku = $ItemPlatformSku->getTrueSku($value['sku'], 1);
            $value['prescription_type'] = isset($value['prescription_type']) ? $value['prescription_type'] : '';

            $value['od_sph'] = isset($value['od_sph']) ? urldecode($value['od_sph']) : '';
            $value['os_sph'] = isset($value['os_sph']) ? urldecode($value['os_sph']) : '';
            $value['od_cyl'] = isset($value['od_cyl']) ? urldecode($value['od_cyl']) : '';
            $value['os_cyl'] = isset($value['os_cyl']) ? urldecode($value['os_cyl']) : '';
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

            $spreadsheet->getActiveSheet()->setCellValue("F" . ($key * 2 + 2), (float) $value['od_sph'] > 0 ? ' +' . number_format($value['od_sph'] * 1, 2) : ' ' . $value['od_sph']);
            $spreadsheet->getActiveSheet()->setCellValue("F" . ($key * 2 + 3), (float) $value['os_sph'] > 0 ? ' +' . number_format($value['os_sph'] * 1, 2) : ' ' . $value['os_sph']);

            $spreadsheet->getActiveSheet()->setCellValue("G" . ($key * 2 + 2), (float) $value['od_cyl'] > 0 ? ' +' . number_format($value['od_cyl'] * 1, 2) : ' ' . $value['od_cyl']);
            $spreadsheet->getActiveSheet()->setCellValue("G" . ($key * 2 + 3), (float) $value['os_cyl'] > 0 ? ' +' . number_format($value['os_cyl'] * 1, 2) : ' ' . $value['os_cyl']);

            $spreadsheet->getActiveSheet()->setCellValue("H" . ($key * 2 + 2), $value['od_axis']);
            $spreadsheet->getActiveSheet()->setCellValue("H" . ($key * 2 + 3), $value['os_axis']);

            $value['os_add'] = urldecode($value['os_add']);
            $value['od_add'] = urldecode($value['od_add']);

            if ($value['os_add'] && $value['os_add'] && (float) ($value['os_add']) * 1 != 0 && (float) ($value['od_add']) * 1 != 0) {
                //新处方版本
                if ($value['is_new_version'] == 1) {
                    $spreadsheet->getActiveSheet()->setCellValue("I" . ($key * 2 + 2), $value['od_add']);
                    $spreadsheet->getActiveSheet()->setCellValue("I" . ($key * 2 + 3), $value['os_add']);
                } else {
                    // 旧处方 双ADD值时，左右眼互换 
                    $spreadsheet->getActiveSheet()->setCellValue("I" . ($key * 2 + 2), $value['os_add']);
                    $spreadsheet->getActiveSheet()->setCellValue("I" . ($key * 2 + 3), $value['od_add']);
                }
            } else {

                if ($value['os_add'] && (float) $value['os_add'] * 1 != 0) {
                    //数值在上一行合并有效，数值在下一行合并后为空
                    $spreadsheet->getActiveSheet()->setCellValue("I" . ($key * 2 + 2), $value['os_add']);
                    $spreadsheet->getActiveSheet()->mergeCells("I" . ($key * 2 + 2) . ":I" . ($key * 2 + 3));
                } else {
                    //数值在上一行合并有效，数值在下一行合并后为空
                    $spreadsheet->getActiveSheet()->setCellValue("I" . ($key * 2 + 2), $value['od_add']);
                    $spreadsheet->getActiveSheet()->mergeCells("I" . ($key * 2 + 2) . ":I" . ($key * 2 + 3));
                }
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

        $writer->save('php://output');
    }

    //批量导出xls
    public function batch_export_xls_test()
    {

        /*************修改为筛选导出****************/
        set_time_limit(0);
        ini_set('memory_limit', '512M');

        $str = ['100117395', '400250565', '400250621', '400250705', '400249311', '100117261', '100117544', '400250865', '100117573', '100117582', '400250924', '500003521', '500003522', '100117621', '100117653', '100117671', '100117266', '100117692', '100117718', '400251347', '400251347', '400251347', '100117735', '100117748', '100117749', '400251382', '400251383', '100117759', '100117766', '100117796', '100117800', '100117801', '400251480', '400251506', '100117892', '400251728', '400251774', '400251782', '400251856', '400251865', '100117933', '100117944', '400251910', '400251990', '400252004', '100118010', '400252061', '400252070', '400252086', '400252088', '400252103', '400252119', '400252125', '100118031', '400252320', '100118121', '400252454', '500003611', '500003612', '100118144', '100118147', '400252521', '100118186', '400252748', '400252838', '100118274', '400252873', '100118297', '100118340', '100118346', '100118392', '400253095', '100118407', '100118412', '100118419', '100118419', '400253210', '100118444', '100118444', '400055496', '100118493', '100118493', '100118516', '100118518', '100118541', '100118582', '100118590', '100118606', '400253623', '100118653', '500003722', '100118688'];

        $map['sfo.increment_id'] = ['in', $str];

        list($where) = $this->buildparams();
        $field = 'sfo.increment_id,sfoi.product_options,total_qty_ordered as NUM,sfoi.order_id,sfo.`status`,sfoi.sku,sfoi.product_id,sfoi.qty_ordered,sfo.created_at,sfo.is_new_version';
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
            //新处方
            if ($value['is_new_version'] == 1) {
                //镀膜
                $finalResult[$key]['coatiing_name'] = $tmp_product_options['info_buyRequest']['tmplens']['coating_name'];
                //镜片类型
                $finalResult[$key]['index_type'] = $tmp_product_options['info_buyRequest']['tmplens']['lens_data_name'];
                //镜片类型拼接颜色字段
                if ($tmp_product_options['info_buyRequest']['tmplens']['color_id']) {
                    $finalResult[$key]['index_type'] .= '-' . $tmp_product_options['info_buyRequest']['tmplens']['color_data_name'];
                }
            } else {
                $finalResult[$key]['coatiing_name'] = $tmp_product_options['info_buyRequest']['tmplens']['coatiing_name'];
                $finalResult[$key]['index_type'] = $tmp_product_options['info_buyRequest']['tmplens']['index_type'];
                //镜片类型拼接颜色字段
                if ($tmp_product_options['info_buyRequest']['tmplens']['color_name']) {
                    $finalResult[$key]['index_type'] .= '-' . $tmp_product_options['info_buyRequest']['tmplens']['color_name'];
                }
            }

            //如果为太阳镜 拼接颜色
            if (@$tmp_product_options['info_buyRequest']['tmplens']['sungless_color_name']) {
                $finalResult[$key]['index_type'] .= '-' . $tmp_product_options['info_buyRequest']['tmplens']['sungless_color_name'];
            }


            $tmp_prescription_params = $tmp_product_options['info_buyRequest']['tmplens']['prescription'];
            if (isset($tmp_prescription_params)) {
                $tmp_prescription_params = explode("&", $tmp_prescription_params);
                $tmp_lens_params = array();
                foreach ($tmp_prescription_params as $tmp_key => $tmp_value) {
                    $arr_value = explode("=", $tmp_value);
                    if (isset($arr_value[1])) {
                        $tmp_lens_params[$arr_value[0]] = $arr_value[1];
                    }
                }
            }

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


            $tmp_bridge = $this->get_frame_lens_width_height_bridge($value['product_id']);
            $finalResult[$key]['lens_width'] = $tmp_bridge['lens_width'];
            $finalResult[$key]['lens_height'] = $tmp_bridge['lens_height'];
            $finalResult[$key]['bridge'] = $tmp_bridge['bridge'];
            $finalResult[$key]['is_new_version'] = $value['is_new_version'];
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

        $ItemPlatformSku = new \app\admin\model\itemmanage\ItemPlatformSku;

        //查询商品管理SKU对应ID
        $item = new \app\admin\model\itemmanage\Item;
        $itemArr = $item->where('is_del', 1)->column('id', 'sku');

        foreach ($finalResult as $key => $value) {
            //网站SKU转换仓库SKU
            $sku = $ItemPlatformSku->getTrueSku($value['sku'], 1);
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

            $spreadsheet->getActiveSheet()->setCellValue("F" . ($key * 2 + 2), $value['od_sph'] > 0 ? ' +' . number_format($value['od_sph'] * 1, 2) : ' ' . $value['od_sph']);
            $spreadsheet->getActiveSheet()->setCellValue("F" . ($key * 2 + 3), $value['os_sph'] > 0 ? ' +' . number_format($value['os_sph'] * 1, 2) : ' ' . $value['os_sph']);

            $spreadsheet->getActiveSheet()->setCellValue("G" . ($key * 2 + 2), $value['od_cyl'] > 0 ? ' +' . number_format($value['od_cyl'] * 1, 2) : ' ' . $value['od_cyl']);
            $spreadsheet->getActiveSheet()->setCellValue("G" . ($key * 2 + 3), $value['os_cyl'] > 0 ? ' +' . number_format($value['os_cyl'] * 1, 2) : ' ' . $value['os_cyl']);

            $spreadsheet->getActiveSheet()->setCellValue("H" . ($key * 2 + 2), $value['od_axis']);
            $spreadsheet->getActiveSheet()->setCellValue("H" . ($key * 2 + 3), $value['os_axis']);

            if ($value['os_add'] && $value['od_add'] && $value['os_add'] * 1 != 0 && $value['od_add'] * 1 != 0) {
                //新处方版本
                if ($value['is_new_version'] == 1) {
                    $spreadsheet->getActiveSheet()->setCellValue("I" . ($key * 2 + 2), $value['od_add']);
                    $spreadsheet->getActiveSheet()->setCellValue("I" . ($key * 2 + 3), $value['os_add']);
                } else {
                    // 旧处方 双ADD值时，左右眼互换 
                    $spreadsheet->getActiveSheet()->setCellValue("I" . ($key * 2 + 2), $value['os_add']);
                    $spreadsheet->getActiveSheet()->setCellValue("I" . ($key * 2 + 3), $value['od_add']);
                }
            } else {

                if ($value['os_add'] && $value['os_add'] * 1 != 0) {
                    //数值在上一行合并有效，数值在下一行合并后为空
                    $spreadsheet->getActiveSheet()->setCellValue("I" . ($key * 2 + 2), $value['os_add']);
                    $spreadsheet->getActiveSheet()->mergeCells("I" . ($key * 2 + 2) . ":I" . ($key * 2 + 3));
                } else {
                    //数值在上一行合并有效，数值在下一行合并后为空
                    $spreadsheet->getActiveSheet()->setCellValue("I" . ($key * 2 + 2), $value['od_add']);
                    $spreadsheet->getActiveSheet()->mergeCells("I" . ($key * 2 + 2) . ":I" . ($key * 2 + 3));
                }
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

        $writer->save('php://output');
    }


    //批量打印标签
    public function batch_print_label()
    {
        ob_start();
        // echo 'batch_print_label';
        $entity_ids = rtrim(input('id_params'), ',');
        // dump($entity_ids);
        if ($entity_ids) {
            $processing_order_querySql = "select sfo.is_new_version,sfo.increment_id,round(sfo.total_qty_ordered,0) NUM,sfoi.product_options,sfoi.order_id,sfo.`status`,sfoi.sku,sfoi.qty_ordered,sfo.created_at
from sales_flat_order_item sfoi
left join sales_flat_order sfo on  sfoi.order_id=sfo.entity_id 
where sfo.`status` in ('processing','creditcard_proccessing','free_processing','complete','paypal_reversed','paypal_canceled_reversal') and sfo.entity_id in($entity_ids)
order by NUM asc;";
            $processing_order_list = Db::connect('database.db_zeelool')->query($processing_order_querySql);

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
            $cargo_number = $store_sku->alias('a')->where(['status' => 1, 'b.is_del' => 1])->join(['fa_store_sku' => 'b'], 'a.id=b.store_id')->column('coding', 'sku');

            //查询sku映射表
            $item = new \app\admin\model\itemmanage\ItemPlatformSku;
            $item_res = $item->cache(3600)->column('sku', 'platform_sku');
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


                //新处方字段
                if ($processing_value['is_new_version'] == 1) {
                    //镀膜名称
                    $final_print['coatiing_name'] = substr($product_options['info_buyRequest']['tmplens']['coating_name'], 0, 60);
                    //镜片类型名称
                    $final_print['index_type'] = $product_options['info_buyRequest']['tmplens']['lens_data_name'];
                    //镜片类型拼接颜色字段
                    if ($product_options['info_buyRequest']['tmplens']['color_id']) {
                        $final_print['index_type'] .= '-' . $product_options['info_buyRequest']['tmplens']['color_data_name'];
                    }

                    //处方参数
                    $prescription_params = $product_options['info_buyRequest']['tmplens']['prescription'];
                    if ($prescription_params) {
                        $prescription_params = explode("&", $prescription_params);
                        $lens_params = array();
                        foreach ($prescription_params as $key => $value) {
                            $arr_value = explode("=", $value);
                            $lens_params[$arr_value[0]] = $arr_value[1];
                        }
                        $final_print = array_merge($lens_params, $final_print);
                    }

                    //处方类型
                    $final_print['prescription_type'] = $final_print['prescription_type'] ?: 'Frame Only';

                    $final_print['os_add'] = urldecode($final_print['os_add']);
                    $final_print['od_add'] = urldecode($final_print['od_add']);

                    //处理ADD
                    if ($final_print['os_add'] && $final_print['od_add'] && (float) $final_print['os_add'] * 1 != 0 && (float) $final_print['od_add'] * 1 != 0) {
                        $od_add = "<td>" . $final_print['od_add'] . "</td> ";
                        $os_add = "<td>" . $final_print['os_add'] . "</td> ";
                    } else {
                        if ($final_print['os_add'] && (float) $final_print['os_add'] * 1 != 0) {
                            $od_add = "<td rowspan='2'>" . $final_print['os_add'] . "</td>";
                            $os_add = "";
                        } else {
                            $od_add = "<td rowspan='2'>" . $final_print['od_add'] . "</td>";
                            $os_add = "";
                        }
                    }
                } else {
                    $final_print['coatiing_name'] = substr($product_options['info_buyRequest']['tmplens']['coatiing_name'], 0, 60);
                    $final_print['index_type'] = $product_options['info_buyRequest']['tmplens']['index_type'];
                    //镜片类型拼接颜色字段
                    if ($product_options['info_buyRequest']['tmplens']['color_name']) {
                        $final_print['index_type'] .= '-' . $product_options['info_buyRequest']['tmplens']['color_name'];
                    }

                    //处方参数
                    $prescription_params = $product_options['info_buyRequest']['tmplens']['prescription'];
                    if ($prescription_params) {
                        $prescription_params = explode("&", $prescription_params);
                        $lens_params = array();
                        foreach ($prescription_params as $key => $value) {
                            $arr_value = explode("=", $value);
                            $lens_params[$arr_value[0]] = $arr_value[1];
                        }
                        $final_print = array_merge($lens_params, $final_print);
                    }

                    $final_print['prescription_type'] = isset($final_print['prescription_type']) ? $final_print['prescription_type'] : '';

                    $final_print['od_sph'] = isset($final_print['od_sph']) ? urldecode($final_print['od_sph']) : '';
                    $final_print['os_sph'] = isset($final_print['os_sph']) ? urldecode($final_print['os_sph']) : '';
                    $final_print['od_cyl'] = isset($final_print['od_cyl']) ? urldecode($final_print['od_cyl']) : '';
                    $final_print['os_cyl'] = isset($final_print['os_cyl']) ? urldecode($final_print['os_cyl']) : '';
                    $final_print['od_axis'] = isset($final_print['od_axis']) ? $final_print['od_axis'] : '';
                    $final_print['os_axis'] = isset($final_print['os_axis']) ? $final_print['os_axis'] : '';

                    $final_print['od_add'] = isset($final_print['od_add']) ? urldecode($final_print['od_add']) : '';
                    $final_print['os_add'] = isset($final_print['os_add']) ? urldecode($final_print['os_add']) : '';

                    $final_print['pdcheck'] = isset($final_print['pdcheck']) ? $final_print['pdcheck'] : '';
                    $final_print['pd_r'] = isset($final_print['pd_r']) ? $final_print['pd_r'] : '';
                    $final_print['pd_l'] = isset($final_print['pd_l']) ? $final_print['pd_l'] : '';
                    $final_print['pd'] = isset($final_print['pd']) ? $final_print['pd'] : '';

                    $final_print['prismcheck'] = isset($final_print['prismcheck']) ? $final_print['prismcheck'] : '';


                    //处理ADD  当ReadingGlasses时 是 双ADD值 双ADD值时，左右眼互换
                    if ($final_print['os_add'] && $final_print['od_add'] && (float) $final_print['os_add'] * 1 != 0 && (float) $final_print['od_add'] * 1 != 0) {
                        // echo '双ADD值';
                        $os_add = "<td>" . $final_print['od_add'] . "</td> ";
                        $od_add = "<td>" . $final_print['os_add'] . "</td> ";
                    } else {
                        // echo '单ADD值';
                        $od_add = "<td rowspan='2'>" . $final_print['os_add'] . "</td>";
                        $os_add = "";
                    }
                }

                //如果为太阳镜 拼接颜色
                if (@$product_options['info_buyRequest']['tmplens']['sungless_color_name']) {
                    $final_print['index_type'] .= '-' . $product_options['info_buyRequest']['tmplens']['sungless_color_name'];
                }

                //处理PD值
                if ($final_print['pdcheck'] == 'on' && strlen($final_print['pd_r']) > 0 && strlen($final_print['pd_l']) > 0) {
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
                    $coatiing_name = "<td colspan='4' rowspan='3' width='45' style='background-color:#fff;word-break: break-word;line-height: 12px;'>" . $final_print['coatiing_name'] . "</td>";
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
            <table width='400mm' height='102px' border='0' cellspacing='0' cellpadding='0' class='addpro' style='margin:0px auto;margin-top:0px;table-layout: inherit;' >
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
            <td width='20px'></td>  
            <td width='42px'>SPH</td>
            <td width='50px'>CYL</td>
            <td width='42px'>AXI</td>
            " . $prismcheck_title . "
            <td width='42px'>ADD</td>
            <td width='36px'>PD</td> 
            " . $coatiing_name . "
            </tr>   
            <tr>  
            <td>R</td>      
            <td>" . $final_print['od_sph'] . "</td> 
            <td>" . $final_print['od_cyl'] . "</td>
            <td>" . $final_print['od_axis'] . "</td>    
            " . $prismcheck_od_value . $od_add . $od_pd .
                    "</tr>
            <tr>
            <td>L</td> 
            <td>" . $final_print['os_sph'] . "</td>    
            <td>" . $final_print['os_cyl'] . "</td>  
            <td>" . $final_print['os_axis'] . "</td> 
            " . $prismcheck_os_value . $os_add . $os_pd .
                    " </tr>
            <tr>
            <td colspan='2'>" . $cargo_number_str . substr(SKUHelper::sku_filter($processing_value['sku']), -7) . "</td>
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
