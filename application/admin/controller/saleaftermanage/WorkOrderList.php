<?php

namespace app\admin\controller\saleaftermanage;

use app\admin\model\DistributionAbnormal;
use app\admin\model\DistributionLog;
use app\admin\model\order\order\LensData;
use app\admin\model\order\order\NewOrder;
use app\admin\model\order\order\NewOrderItemProcess;
use app\admin\model\order\order\NewOrderProcess;
use app\admin\model\saleaftermanage\WorkOrderNote;
use app\admin\model\warehouse\Inventory;
use app\admin\model\warehouse\StockHouse;
use app\common\controller\Backend;
use fast\Excel;
use think\Cache;
use think\Db;
use think\Exception;
use app\admin\model\AuthGroupAccess;
use think\exception\PDOException;
use think\exception\ValidateException;
use Think\Log;
use Util\NihaoPrescriptionDetailHelper;
use Util\ZeeloolPrescriptionDetailHelper;
use Util\VooguemePrescriptionDetailHelper;
use Util\MeeloogPrescriptionDetailHelper;
use Util\WeseeopticalPrescriptionDetailHelper;
use Util\ZeeloolEsPrescriptionDetailHelper;
use Util\ZeeloolDePrescriptionDetailHelper;
use Util\ZeeloolJpPrescriptionDetailHelper;
use app\admin\model\saleaftermanage\WorkOrderMeasure;
use app\admin\model\saleaftermanage\WorkOrderChangeSku;
use app\admin\model\saleaftermanage\WorkOrderRecept;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use app\admin\model\Admin;
use think\Loader;
use Util\SKUHelper;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Reader\Xlsx;
use PhpOffice\PhpSpreadsheet\Reader\Xls;
use PhpOffice\PhpSpreadsheet\Reader\Csv;
use app\admin\model\AuthGroup;
use app\admin\model\warehouse\ProductBarCodeItem;
use app\admin\model\itemmanage\ItemPlatformSku;
use app\admin\model\finance\FinanceCost;


/**
 * 售后工单列管理
 *
 * @icon fa fa-circle-o
 */
class WorkOrderList extends Backend
{
    protected $noNeedRight = ['getMeasureContent', 'batch_export_xls_bak', 'getProblemTypeContent', 'batch_export_xls', 'getDocumentaryRule'];
    /**
     * WorkOrderList模型对象
     * @var \app\admin\model\saleaftermanage\WorkOrderList
     */
    protected $model = null;
    protected $noNeedLogin = ['batch_export_xls_array', 'batch_export_xls_array_copy'];

    public function _initialize()
    {
        parent::_initialize();
        //设置工单的配置值
        ##### start ######
        //global $workOrderConfigValue;
        $workOrderConfigValue = $this->workOrderConfigValue = (new Workorderconfig)->getConfigInfo();
        //print_r($workOrderConfigValue);die;
        $this->assignconfig('workOrderConfigValue', $this->workOrderConfigValue);
        ###### end ######
        $this->model = new \app\admin\model\saleaftermanage\WorkOrderList();
        $this->step = new \app\admin\model\saleaftermanage\WorkOrderMeasure;
        $this->order_change = new \app\admin\model\saleaftermanage\WorkOrderChangeSku;
        $this->order_remark = new \app\admin\model\saleaftermanage\WorkOrderRemark;
        $this->work_order_note = new \app\admin\model\saleaftermanage\WorkOrderNote;
        //$this->view->assign('step', config('workorder.step')); //措施
        $this->view->assign('step', $workOrderConfigValue['step']);
        //$this->assignconfig('workorder', config('workorder')); //JS专用，整个配置文件
        $this->assignconfig('workorder', $workOrderConfigValue);
        $this->_product_bar_code_item = new ProductBarCodeItem();
        $this->_inventory = new Inventory();
        //$this->view->assign('check_coupon', config('workorder.check_coupon')); //不需要审核的优惠券
        //$this->view->assign('need_check_coupon', config('workorder.need_check_coupon')); //需要审核的优惠券
        $this->view->assign('check_coupon', $workOrderConfigValue['check_coupon']);
        $this->view->assign('need_check_coupon', $workOrderConfigValue['need_check_coupon']);
        //获取所有的国家
        $country = json_decode(file_get_contents('assets/js/country.js'), true);
        $this->view->assign('country', $country);
        $this->recept = new \app\admin\model\saleaftermanage\WorkOrderRecept;
        $this->item = new \app\admin\model\itemmanage\Item;
        $this->item_platform_sku = new \app\admin\model\itemmanage\ItemPlatformSku;
        $this->_work_order_change_sku = new WorkOrderChangeSku();
        //获取当前登录用户所属主管id
        //$this->assign_user_id = searchForId(session('admin.id'), config('workorder.kefumanage'));
        $this->assign_user_id = searchForId(session('admin.id'), $workOrderConfigValue['kefumanage']);
        //选项卡
        $this->view->assign('getTabList', $this->model->getTabList());

        $this->assignconfig('admin_id', session('admin.id'));
        //查询用户id对应姓名
        $admin = new \app\admin\model\Admin();
        $this->users = $admin->where('status', 'normal')->column('nickname', 'id');
        //$this->users = $admin->column('nickname', 'id');
        $this->assignconfig('users', $this->users); //返回用户
        $this->assignconfig('userid', session('admin.id'));
        //查询当前登录用户所在A/B组
        $this->customer_group = session('admin.group_id') ?: 0;
    }

    /**
     * 默认生成的控制器所继承的父类中有index/add/edit/del/multi五个基础方法、destroy/restore/recyclebin三个回收站方法
     * 因此在当前控制器中可不用编写增删改查的代码,除非需要自己控制这部分逻辑
     * 需要将application/admin/library/traits/Backend.php中对应的方法复制到当前控制器,然后进行修改
     */

    //根据主记录id，获取措施相关信息
    protected function sel_order_recept($id)
    {
        $step = $this->step->where('work_id', $id)->select();
        $step_arr = collection($step)->toArray();

        foreach ($step_arr as $k => $v) {
            $recept = $this->recept->where('measure_id', $v['id'])->where('work_id', $id)->select();
            $recept_arr = collection($recept)->toArray();
            $step_arr[$k]['recept_user'] = implode(',', array_column($recept_arr, 'recept_person'));
            $step_arr[$k]['recept_person_id'] = implode(',', array_column($recept_arr, 'recept_person_id'));

            $step_arr[$k]['recept'] = $recept_arr;
        }

        return $step_arr ?: [];
    }

    /**
     * 查看
     */
    public function index()
    {
        $workOrderConfigValue = $this->workOrderConfigValue;
        $platform_order = input('platform_order');
        //设置过滤方法
        $this->request->filter(['strip_tags']);
        if ($this->request->isAjax()) {
            //如果发送的来源是Selectpage，则转发到Selectpage
            if ($this->request->request('keyField')) {
                return $this->selectpage();
            }
            $platform_order = input('platform_order');
            if ($platform_order) {
                $map['platform_order'] = $platform_order;
            }
            $work_id = input('work_id');
            if ($work_id) {
                $map['id'] = $work_id;
            }
            //选项卡我的任务切换
            $filter = json_decode($this->request->get('filter'), true);
            if ($filter['recept_person_id'] && !$filter['recept_person']) {
                //承接 经手 审核 包含用户id
                //获取当前用户所有的承接的工单id并且不是取消，新建的
                $workIds = WorkOrderRecept::where('recept_person_id', $filter['recept_person_id'])->column('work_id');
                //如果在我的任务选项卡中 点击了措施按钮
                if ($workIds) {
                    if (!empty($filter['measure_choose_id'])) {
                        $measuerWorkIds = WorkOrderMeasure::where('measure_choose_id', 'in', $filter['measure_choose_id'])->column('work_id');
                        $arr = implode(',', $measuerWorkIds);
                        //将两个数组相同的数据取出
                        $newWorkIds = array_intersect($workIds, $measuerWorkIds);
                        $newWorkIds = implode(',', $newWorkIds);
                        if (strlen($newWorkIds) > 0) {
                            //数据查询的条件
                            $map2 = "(id in ($newWorkIds) or after_user_id = {$filter['recept_person_id']} or find_in_set({$filter['recept_person_id']},all_after_user_id) or assign_user_id = {$filter['recept_person_id']}) and work_status not in (0,1,7) and id in ($arr)";
                        } else {
                            $map2 = "(after_user_id = {$filter['recept_person_id']} or find_in_set({$filter['recept_person_id']},all_after_user_id) or assign_user_id = {$filter['recept_person_id']}) and work_status not in (0,1,7) and id in ($arr)";
                        }
                    } else {
                        $map2 = "(id in (" . join(',', $workIds) . ") or after_user_id = {$filter['recept_person_id']} or find_in_set({$filter['recept_person_id']},all_after_user_id) or assign_user_id = {$filter['recept_person_id']}) and work_status not in (0,1,7)";
                    }
                } else {
                    $map2 = "(after_user_id = {$filter['recept_person_id']} or find_in_set({$filter['recept_person_id']},all_after_user_id) or assign_user_id = {$filter['recept_person_id']}) and work_status not in (0,1,7)";
                }
                unset($filter['recept_person_id']);
                unset($filter['measure_choose_id']);
            }

            if ($filter['recept_person']) {

                $recept_person = $filter['recept_person'];
                $map['id'] = [
                    'IN',
                    function ($query) use ($recept_person) {
                        $query->table('fa_work_order_recept')->where('recept_person_id', 'in', $recept_person)->field('work_id');
                    },
                ];
                unset($filter['recept_person']);
            }

            if ($filter['stock_id']) {
                $stockId = $filter['stock_id'];
                $map['stock_id'] = $stockId;
                unset($filter['stock_id']);
            }

            //筛选措施
            if ($filter['measure_choose_id']) {
                $measure_choose_id = $filter['measure_choose_id'];
                $map['id'] = [
                    'IN',
                    function ($query) use ($measure_choose_id) {
                        $query->table('fa_work_order_measure')->where('measure_choose_id', 'in', $measure_choose_id)->field('work_id');
                    },
                ];
                unset($filter['measure_choose_id']);
            }

            if ($filter['payment_time']) {
                $createat = explode(' ', $filter['payment_time']);
                $map1['payment_time'] = ['between', [$createat[0] . ' ' . $createat[1], $createat[3] . ' ' . $createat[4]]];
                unset($filter['payment_time']);
            }

            $this->request->get(['filter' => json_encode($filter)]);
            [$where, $sort, $order, $offset, $limit] = $this->buildparams();
            $total = $this->model
                ->where($where)
                ->where($map)
                ->where($map1)
                ->where($map2)
                ->order($sort, $order)
                ->count();
            $list = $this->model
                ->where($where)
                ->where($map)
                ->where($map1)
                ->where($map2)
                ->order($sort, $order)
                ->limit($offset, $limit)
                ->select();
            $list = collection($list)->toArray();
            $fa_order = new NewOrder();
            //用户
            $user_list = $this->users;
            foreach ($list as $k => $v) {
                //排列sku
                if ($v['order_sku']) {
                    $list[$k]['order_sku_arr'] = explode(',', $v['order_sku']);
                }

                //取经手人
                if ($v['after_user_id'] != 0) {
                    $list[$k]['after_user_name'] = $user_list[$v['after_user_id']];
                }                //指定经手人
                if ($v['all_after_user_id'] != 0) {
                    $all_after_user_arr = explode(',', $v['all_after_user_id']);
                    foreach ($all_after_user_arr as $aa) {
                        if ($user_list[$aa] != null) {
                            $list[$k]['all_after_user_name'][] = $user_list[$aa];
                        }
                    }
                    $list[$k]['all_after_user_arr'] = $all_after_user_arr;
                } else {
                    $list[$k]['all_after_user_name'][] = $user_list[$v['after_user_id']];
                    $list[$k]['all_after_user_arr'] = [];
                }

                //工单类型
                if ($v['work_type'] == 1) {
                    $list[$k]['work_type_str'] = '客服工单';
                } else {
                    $list[$k]['work_type_str'] = '仓库工单';
                }

                //子单号
                $list[$k]['order_item_number_arr'] = explode(',', $v['order_item_numbers']);

                //是否审核
                if ($v['is_check'] == 1) {
                    $list[$k]['assign_user_name'] = $user_list[$v['assign_user_id']];
                    if ($v['operation_user_id'] != 0) {
                        $list[$k]['operation_user_name'] = $user_list[$v['operation_user_id']];
                    }
                }

                $recept = $this->sel_order_recept($v['id']); //获取措施相关记录
                $list[$k]['step_num'] = $recept;
                //是否有处理权限
                $receptPersonIds = explode(',', implode(',', array_column($recept, 'recept_person_id')));
                //跟单客服跟单处理之后不需要显示处理权限
                // if($v['after_user_id']){
                //     array_unshift($receptPersonIds,$v['after_user_id']);
                // }
                //跟单客服处理权限
                $documentaryIds = explode(',', $v['']);
                //仓库工单并且经手人未处理
                //1、仓库类型：经手人未处理||已处理未审核||
                if (($v['work_type'] == 2 && $v['is_after_deal_with'] == 0) || in_array($v['work_status'], [0, 1, 2, 4, 6, 7]) || !in_array(session('admin.id'), $receptPersonIds)) {
                    $list[$k]['has_recept'] = 0;
                } else {
                    $list[$k]['has_recept'] = 1;
                }
                $list[$k]['order_status'] = $fa_order->where('increment_id', $list[$k]['platform_order'])->value('status');
            }
            $result = ["total" => $total, "rows" => $list];

            return json($result);
        }
        //所有承接人的id
        //客服的所有承接人
        //$kefumanages = config('workorder.kefumanage');
        // $kefumanages = $workOrderConfigValue['kefumanage'];
        // foreach ($kefumanages as $key => $kefumanage) {
        //     $kefumanageIds[] = $key;
        //     foreach ($kefumanage as $k => $v) {
        //         $kefumanageIds[] = $v;
        //     }
        // }
        //array_unshift($kefumanageIds, config('workorder.customer_manager'));
        //array_unshift($kefumanageIds,$workOrderConfigValue['customer_manager']);
        // $receptPersonAllIds = array_merge(config('workorder.warehouse_group'), config('workorder.warehouse_lens_group'), config('workorder.cashier_group'), config('workorder.copy_group'), $kefumanageIds);
        //$admins = Admin::where('id', 'in', $receptPersonAllIds)->select();
        $receptPersonAllIds = $workOrderConfigValue['all_extend_person'];
        $admins = Admin::where('id', 'in', $receptPersonAllIds)->where('status', 'normal')->field('id,nickname')->select();
        $this->assign('admins', $admins);

        //获取用户ID和所在权限组
        $admin_id = session('admin.id');
        $_auth_group_access = new AuthGroupAccess();
        $user_group_access = $_auth_group_access->where(['uid' => $admin_id])->column('group_id');
        $warehouse_department_rule = $workOrderConfigValue['warehouse_department_rule'];
        $is_warehouse = array_intersect($user_group_access, $warehouse_department_rule);
        $this->assign('is_warehouse', $is_warehouse ? 1 : 0);

        $this->assignconfig('platform_order', $platform_order ?: '');

        return $this->view->fetch();
    }

    /**
     * 添加/编辑/详情
     *
     * @Author lzh
     *
     * @param mixed $ids
     *
     * @return void
     */
    public function add($ids = null)
    {
        //获取工单配置信息
        $workOrderConfigValue = $this->workOrderConfigValue;

        //获取用户ID和所在权限组
        $admin_id = session('admin.id');
        $nickname = session('admin.nickname');
        $_auth_group_access = new AuthGroupAccess();
        $user_group_access = $_auth_group_access->where(['uid' => $admin_id])->column('group_id');
        $warehouse_department_rule = $workOrderConfigValue['warehouse_department_rule'];
        $is_warehouse = array_intersect($user_group_access, $warehouse_department_rule);

        if ($this->request->isPost()) {
            $params = $this->request->post("row/a");
            if ($params) {
                $params = $this->preExcludeFields($params);
                if ($this->dataLimit && $this->dataLimitFieldAutoFill) {
                    $params[$this->dataLimitField] = $this->auth->id;
                }

                //是否采用模型验证
                if ($this->modelValidate) {
                    $name = str_replace("\\model\\", "\\validate\\", get_class($this->model));
                    $validate = is_bool($this->modelValidate) ? ($this->modelSceneValidate ? $name . '.add' : $name) : $this->modelValidate;
                    $this->model->validateFailException(true)->validate($validate);
                }

                $platform_order = trim($params['platform_order']);//订单号
                $measureChooseId = $params['measure_choose_id'] ? array_unique(array_filter($params['measure_choose_id'])) : [];//措施ID数组
                $work_type = $params['work_type'];//工单类型：1客服 2仓库
                $item_order_info = $params['item_order_info'];//子订单措施
                $order_sku = $params['order_sku'] ? implode(',', $params['order_sku']) : '';//sku列表

                //校验问题类型、问题描述
                $params['problem_type_id'] = $params['problem_type_id'] ?: $params['problem_id'];
                !$params['problem_type_id'] && $this->error("请选择问题类型");
                !$params['problem_description'] && $this->error("问题描述不能为空");
                !$platform_order && $this->error("订单号不能为空");

                if (!$ids) {
                    //校验是否有未处理工单
                    $count = $this->model->where(['platform_order' => $platform_order, 'work_status' => ['in', [1, 2, 3, 5]]])->count();
                    0 < $count && $this->error("此订单存在未处理完成的工单");

                    $flag = 0;
                    if (is_array($item_order_info)) {
                        $item_choose = array_column($item_order_info, 'item_choose');
                        if (!empty($item_choose)) {
                            foreach ($item_choose as $key => $value) {
                                if (!empty($item_choose[$key][0])) {
                                    $flag = 1;
                                }
                            }
                        }
                    }

                    //判断订单状态
                    $_new_order_process = new NewOrderProcess();
                    $check_status = $_new_order_process
                        ->where('increment_id', $platform_order)
                        ->value('check_status');

                    $_new_order = new NewOrder();
                    $new_order_status = $_new_order
                        ->where('increment_id', $platform_order)
                        ->value('status');
                    //processing状态的判断审单状态
                    if ('processing' == $new_order_status) {
                        //已审单，包含主单取消、子单措施不能创建工单
                        1 == $check_status
                        &&
                        (
                            in_array(3, $measureChooseId)
                            ||
                            $flag
                        )
                        && $this->error("已审单，不能创建工单");
                    } else {
                        (
                            in_array(3, $measureChooseId)
                            ||
                            $flag
                        ) && $this->error("不能创建子单工单");
                    }

                    //指定问题类型校验sku下拉框是否勾选
                    in_array($params['problem_type_id'], [8, 10, 11, 56, 13, 14, 15, 16, 18, 22, 59])
                    && empty($order_sku)
                    && $this->error("请选择sku");

                    //校验工单类型
                    if (1 == $work_type) {
                        //客服
                        !empty($is_warehouse) && $this->error("当前账号不能创建客服工单");

                        //校验工单措施
                        empty($measureChooseId) && empty($item_order_info) && $this->error("请选择实施措施");

                        $params['problem_type_content'] = $workOrderConfigValue['customer_problem_type'][$params['problem_type_id']];
                    } else {
                        //仓库
                        empty($is_warehouse) && $this->error("当前账号不能创建仓库工单");

                        $all_after_user_id = array_filter($params['all_after_user_id']);
                        empty($all_after_user_id) && $this->error("未找到对应承接人,请重新选择");
                        $params['all_after_user_id'] = implode(',', $all_after_user_id);
                        $params['problem_type_content'] = $workOrderConfigValue['warehouse_problem_type'][$params['problem_type_id']];
                    }
                } else {
                    //校验工单措施
                    empty($measureChooseId) && empty($item_order_info) && $this->error("请选择实施措施");

                    //工单是否存在
                    $row = $this->model->get($ids);
                    !$row && $this->error(__('No Results were found'));

                    //跟单人ID
                    $params['after_user_id'] = $admin_id;
                }

                //主单和子单全部的措施id
                $all_choose_ids = [];

                //检测主订单措施
                if (!empty($measureChooseId)) {
                    /**
                     * 审核判断条件
                     * 1、退款金额大于30 经理审核
                     * 2、赠品数量大于1 经理审核
                     * 3、补发数量大于1 经理审核
                     * 4、优惠券等于100% 经理审核  50%主管审核 固定额度无需审核
                     * 5、运营客服组的优惠券都由客服经理审核
                     */

                    //主单取消
                    if (in_array(3, $measureChooseId)) {
                        $_new_order = new NewOrder();
                        $orderList = $_new_order->field('id,stock_id')
                            ->where('increment_id', $params['platform_order'])
                            ->find();
                        $order_id = $orderList->id;
                        if ($order_id) {
                            $order_item_where['order_id'] = $order_id;
                            $_new_order_item_process = new NewOrderItemProcess();
                            $order_item_list = $_new_order_item_process
                                ->where($order_item_where)
                                ->column('sku');

                            //查询仓库库区
                            $area_id = Db::name('warehouse_area')->where(['stock_id' => $orderList->stock_id, 'type' => 2])->value('id');

                            /*****************限制如果有盘点单未结束不能操作配货完成*******************/
                            //配货完成时判断
                            //拣货区盘点时不能操作
                            //查询条形码库区库位
                            $whe_sku['platform_sku'] = ['in', $order_item_list];
                            //转换sku
                            $item_platform_sku = new ItemPlatformSku();
                            $true_sku = $item_platform_sku->where($whe_sku)->column('sku');
                            $whe['sku'] = ['in', $true_sku];
                            $whe['library_status'] = 1;
                            $whe['stock_id'] = $orderList->stock_id;
                            $barcodedata = $this->_product_bar_code_item->where($whe)->column('location_code');
                            if (!empty($barcodedata)) {
                                $count = $this->_inventory->alias('a')
                                    ->join(['fa_inventory_item' => 'b'], 'a.id=b.inventory_id')->where(['a.is_del' => 1, 'a.check_status' => ['in', [0, 1]], 'library_name' => ['in', $barcodedata], 'area_id' => $area_id])
                                    ->count();
                                if ($count > 0) {
                                    return ['result' => false, 'msg' => '此主单下的子订单SKU对应库位正在盘点,暂无法进行出入库操作'];
                                }
                            }
                            /****************************end*****************************************/
                        }
                    }


                    $all_choose_ids = $measureChooseId;

                    //校验退款、vip退款
                    if (array_intersect([2, 15], $measureChooseId)) {
                        !$params['refund_money'] && $this->error("退款金额不能为空");
                        $params['is_refund'] = 1;
                    } else {
                        unset($params['refund_money']);
                    }

                    //校验赠品、补发库存
                    if (array_intersect([6, 7], $measureChooseId)) {
                        $original_sku = [];


                        //赠品
                        if (in_array(6, $measureChooseId)) {
                            $gift_sku = $params['gift']['original_sku'];
                            !$gift_sku && $this->error("赠品sku不能为空");

                            $gift_number = $params['gift']['original_number'];
                            !$gift_number && $this->error("赠品数量不能为空");

                            foreach ($gift_sku as $key => $sku) {
                                $num = $key + 1;
                                !$sku && $this->error("第{$num}个赠品sku不能为空");
                                !$gift_number[$key] && $this->error("第{$num}个赠品数量必须大于0");

                                if (isset($original_sku[$sku])) {
                                    $original_sku[$sku] += $gift_number[$key];
                                } else {
                                    $original_sku[$sku] = $gift_number[$key];
                                }
                            }
                        }

                        //补发
                        if (in_array(7, $measureChooseId)) {
                            !$params['address']['shipping_type'] && $this->error("请选择Shipping Method");

                            $replacement_sku = $params['replacement']['original_sku'];
                            !$replacement_sku && $this->error("补发sku不能为空");

                            $replacement_number = $params['replacement']['original_number'];
                            !$replacement_number && $this->error("补发数量不能为空");

                            foreach ($replacement_sku as $key => $sku) {
                                $num = $key + 1;
                                !$sku && $this->error("第{$num}个补发sku不能为空");
                                !$replacement_number[$key] && $this->error("第{$num}个补发数量必须大于0");

                                if (isset($original_sku[$sku])) {
                                    $original_sku[$sku] += $replacement_number[$key];
                                } else {
                                    $original_sku[$sku] = $replacement_number[$key];
                                }
                            }
                        }

                        //校验库存
                        if ($original_sku) {
                            $back_data = $this->skuIsStock(array_keys($original_sku), $params['work_platform'], array_values($original_sku), $platform_order);
                            !$back_data['result'] && $this->error($back_data['msg']);
                        }

                    }

                    //校验补价措施
                    if (in_array(8, $measureChooseId)) {
                        !$params['replenish_money'] && $this->error("补差价金额不能为空");
                    } else {
                        unset($params['replenish_money']);
                    }

                    //校验优惠券措施
                    if (in_array(9, $measureChooseId)) {
                        !$params['coupon_id'] && !$params['need_coupon_id'] && $this->error("请选择优惠券");

                        //不需要审核的优惠券
                        if ($params['coupon_id']) {
                            $check_coupon = $workOrderConfigValue['check_coupon'];
                        } else {
                            //需要审核的优惠券
                            $params['is_check'] = 1;
                            $params['coupon_id'] = $params['need_coupon_id'];
                            $check_coupon = $workOrderConfigValue['need_check_coupon'];

                            //优惠券折扣
                            $discount = $workOrderConfigValue['need_check_coupon'][$params['need_coupon_id']]['sum'];
                            if (100 == $discount || (0 < $discount && in_array(131, $user_group_access))) {
                                //创建人上级经理
                                $params['assign_user_id'] = $workOrderConfigValue['customer_manager'];
                            } elseif (50 == $discount) {
                                //创建人上级主管
                                $params['assign_user_id'] = $this->assign_user_id ?: $admin_id;
                            }
                        }
                        foreach ($check_coupon as $v) {
                            if ($v['id'] == $params['coupon_id']) {
                                $params['coupon_describe'] = $v['desc'];
                                break;
                            }
                        }
                    }

                    //判断是否选择积分措施
                    if (in_array(10, $measureChooseId)) {
                        (!$params['integral'] || !is_numeric($params['integral']))
                        && $this->error("积分必须是数字");
                    } else {
                        unset($params['integral']);
                    }

                    //判断是否选择退件措施
                    if (in_array(11, $measureChooseId)) {
                        !$params['refund_logistics_num'] && $this->error("退回物流单号不能为空");
                    } else {
                        unset($params['refund_logistics_num']);
                    }
                }

                //检测子订单措施
                if ($item_order_info) {
                    $item_order_info = array_filter($item_order_info);
                    //查询所有子单数量
                    $_new_order_process = new NewOrderProcess();
                    $order_id = $_new_order_process->where('increment_id', $platform_order)->value('order_id');
                    $_new_order_item_process = new NewOrderItemProcess();
                    $count_item_num = $_new_order_item_process->where('order_id', $order_id)->count();

                    $_new_order = new NewOrder();
                    $stock_id = $_new_order->where('increment_id', $platform_order)
                        ->value('stock_id');
                    //查询仓库库区
                    $area_id = Db::name('warehouse_area')->where(['stock_id' => $stock_id, 'type' => 2])->value('id');

                    1 > count($item_order_info) && $this->error("子订单号错误");
                    foreach ($item_order_info as $key => &$item) {
                        $item['item_choose'] = array_unique(array_filter($item['item_choose']));
                        if ($count_item_num != count($item_order_info)) {
                            empty($item['item_choose']) && $this->error("请选择子订单：{$key} 的实施措施");
                        }
                        $all_choose_ids = array_unique(array_merge($all_choose_ids, $item['item_choose']));

                        //获取子单之前处理成功的措施类型
                        $_work_order_change_sku = new WorkOrderChangeSku();
                        $change_type = $_work_order_change_sku
                            ->alias('a')
                            ->join(['fa_work_order_measure' => 'b'], 'a.measure_id=b.id')
                            ->where([
                                'a.item_order_number' => $key,
                                'b.operation_type'    => 1,
                            ])
                            ->column('a.change_type');

                        //子单取消
                        if (in_array(18, $item['item_choose'])) {
                            //检测之前是否处理过子单措施 2021-07-05 bug2213 改为不校验此项
                            //array_intersect([3], $change_type) && $this->error("子订单：{$key} 措施已处理，不能取消");
                            /*****************限制如果有盘点单未结束不能操作配货完成*******************/
                            //拣货区盘点时不能操作
                            //查询条形码库区库位

                            $whe_sku['platform_sku'] = $item['cancel_order']['sku'];
                            //转换sku
                            $item_platform_sku = new ItemPlatformSku();
                            $true_sku = $item_platform_sku->where($whe_sku)->value('sku');
                            $whe['sku'] = $true_sku;
                            $whe['library_status'] = 1;
                            $whe['stock_id'] = $stock_id;
                            $barcodedata = $this->_product_bar_code_item->where($whe)->column('location_code');
                            if (!empty($barcodedata)) {
                                $count = $this->_inventory->alias('a')
                                    ->join(['fa_inventory_item' => 'b'], 'a.id=b.inventory_id')
                                    ->where(['a.is_del' => 1, 'a.check_status' => ['in', [0, 1]], 'library_name' => ['in', $barcodedata], 'area_id' => $area_id])
                                    ->count();
                                if ($count > 0) {
                                    return ['result' => false, 'msg' => '此' . $item['cancel_order']['sku'] . '对应库位正在盘点,暂无法进行出入库操作'];
                                }
                            }
                            /****************************end*****************************************/

                        } elseif (in_array(19, $item['item_choose'])) {//更改镜框
                            /*****************限制如果有盘点单未结束不能操作配货完成*******************/
                            //拣货区盘点时不能操作
                            //查询条形码库区库位

                            //转换sku
                            $whe_sku['platform_sku'] = $item['change_frame']['original_sku'];
                            //转换sku
                            $item_platform_sku = new ItemPlatformSku();
                            $true_sku = $item_platform_sku->where($whe_sku)->value('sku');
                            $whe['sku'] = $true_sku;
                            $whe['library_status'] = 1;
                            $whe['stock_id'] = $stock_id;
                            $barcodedata = $this->_product_bar_code_item->where($whe)->column('location_code');
                            if (!empty($barcodedata)) {
                                $count = $this->_inventory->alias('a')
                                    ->join(['fa_inventory_item' => 'b'], 'a.id=b.inventory_id')->where(['a.is_del' => 1, 'a.check_status' => ['in', [0, 1]], 'library_name' => ['in', $barcodedata], 'area_id' => $area_id])
                                    ->count();
                                if ($count > 0) {
                                    return ['result' => false, 'msg' => '此' . $item['change_frame']['original_sku'] . '对应库位正在盘点,暂无法进行出入库操作'];
                                }
                            }
                            /****************************end*****************************************/

                            /*****************限制如果有盘点单未结束不能操作配货完成*******************/
                            //拣货区盘点时不能操作
                            //查询条形码库区库位
                            //转换sku
                            $whe_sku['platform_sku'] = $item['change_frame']['change_sku'];
                            //转换sku
                            $item_platform_sku = new ItemPlatformSku();
                            $true_sku = $item_platform_sku->where($whe_sku)->value('sku');
                            $whe['sku'] = $true_sku;
                            $whe['library_status'] = 1;
                            $whe['stock_id'] = $stock_id;
                            $barcodedata = $this->_product_bar_code_item->where($whe)->column('location_code');
                            if (!empty($barcodedata)) {
                                $count = $this->_inventory->alias('a')
                                    ->join(['fa_inventory_item' => 'b'], 'a.id=b.inventory_id')->where(['a.is_del' => 1, 'a.check_status' => ['in', [0, 1]], 'library_name' => ['in', $barcodedata], 'area_id' => $area_id])
                                    ->count();
                                if ($count > 0) {
                                    return ['result' => false, 'msg' => '此' . $item['change_frame']['change_sku'] . '对应库位正在盘点,暂无法进行出入库操作'];
                                }
                            }
                            /****************************end*****************************************/
                            //检测之前是否处理过更改镜框措施
//                            in_array(1, $change_type) && $this->error("子订单：{$key} 措施已处理，不能重复创建");
//
//                            //更改镜框校验库存
//                            !$item['change_frame']['change_sku'] && $this->error("子订单：{$key} 的新sku不能为空");
//                            $back_data = $this->skuIsStock([$item['change_frame']['change_sku']], $params['work_platform'], [1]);
//                            !$back_data['result'] && $this->error($back_data['msg']);
                        }
//                        elseif (in_array(20, $item['item_choose'])) {//更改镜片
//                            //检测之前是否处理过更改镜片措施
//                            in_array(2, $change_type) && $this->error("子订单：{$key} 措施已处理，不能重复创建");
//                        }
                    }
                    unset($item);
                }


                /**获取审核人 start*/
                $check_person_weight = $workOrderConfigValue['check_person_weight'];//审核人列表
                $check_group_weight = $workOrderConfigValue['check_group_weight'];//审核组列表
                $all_group = $workOrderConfigValue['group'];//所有的成员组
                //核算审核组
                if (!empty($check_group_weight)) {
                    foreach ($check_group_weight as $gv) {
                        $all_person = [];
                        //获取当前组下的所有成员
                        $subordinate = (new AuthGroup)->getAllNextGroup($gv['work_create_person_id']);
                        if ($subordinate) {
                            array_push($subordinate, $gv['work_create_person_id']);
                            foreach ($subordinate as $av) {
                                if (is_array($all_group[$av])) {
                                    foreach ($all_group[$av] as $vk) {
                                        $all_person[] = $vk;
                                    }
                                }
                            }
                        } else {
                            $all_person = $all_group[$gv['work_create_person_id']];
                        }


                        if (!empty($all_person)) {
                            //如果符合创建组
                            if (in_array($admin_id, array_unique($all_person))) {
                                if (!$this->weight_currency($gv, $all_choose_ids, $params)) {
                                    $params['is_check'] = 1;
                                    $params['assign_user_id'] = $all_group[$gv['check_group_id']][0];
                                    break;
                                }
                            }
                        }
                    }
                }
                //核算审核人
                if (!empty($check_person_weight)) {
                    foreach ($check_person_weight as $wkv) {
                        if ($admin_id == $wkv['work_create_person_id']) {
                            if (!$this->weight_currency($wkv, $all_choose_ids, $params)) {
                                $params['is_check'] = 1;
                                $params['assign_user_id'] = $all_group[$wkv['check_group_id']][0];
                                break;
                            }
                        }
                    }
                }

                //没有审核人则不需要审核
                if (!$params['assign_user_id']) {
                    $params['is_check'] = 0;
                } else {
                    //所有工单都不需要审核（产品确认要改）
                    $params['is_check'] = 0;
                    /*if ($params['assign_user_id'] == 95 && $admin_id == 198){
                        $params['assign_user_id'] = 117;
                    }*/
                }

                /**获取审核人 end*/

                //点击提交按钮
                if (2 == $params['work_status']) {
                    //不需要审核或工单类型为仓库 工单状态默认为审核通过
                    if (0 == $params['is_check'] || 2 == $params['work_type']) {
                        $params['work_status'] = 3;
                    }
                    $params['submit_time'] = date('Y-m-d H:i:s');
                }

                //vip订单
                if (100 == $params['order_type']) {
                    $params['base_grand_total'] = $params['refund_money'];
                    $params['grand_total'] = $params['refund_money'];
                    $params['payment_time'] = date('Y-m-d H:i:s');
                }
                $params['recept_person_id'] = $params['recept_person_id'] ?: $admin_id;

                //配货异常表
                $_distribution_abnormal = new DistributionAbnormal();

                //库位表
                $_stock_house = new StockHouse();

                //子单表
                $_new_order_item_process = new NewOrderItemProcess();

                if (!empty($row)) {
                    $row->startTrans();
                }
                $this->model->startTrans();
                $this->work_order_note->startTrans();
                $_distribution_abnormal->startTrans();
                $_new_order_item_process->startTrans();
                $_stock_house->startTrans();
                try {
                    //跟单处理
                    if (!empty($row)) {
                        //如果需要审核 则修改状态为待审核
                        if (1 == $params['is_check']) {
                            $params['work_status'] = 2;
                        }
                        $params['is_after_deal_with'] = 1;
                        $result = $row->allowField(true)->save($params);
                        if (false === $result) {
                            throw new Exception("跟单处理失败！！");
                        }
                        $work_id = $row->id;
                    } else {
                        //添加
                        $params['create_user_name'] = $nickname;
                        $params['create_user_id'] = $admin_id;
                        $params['create_time'] = date('Y-m-d H:i:s');
                        $params['order_sku'] = $order_sku;
                        $params['assign_user_id'] = $params['assign_user_id'] ?: 0;
                        $params['customer_group'] = $this->customer_group;
                        $stock_id = Db::connect('database.db_mojing_order')->table('fa_order')->where('increment_id', $params['platform_order'])->value('stock_id');
                        $params['stock_id'] = $stock_id ?: 0;
                        $result = $this->model->allowField(true)->save($params);
                        if (false === $result) {
                            throw new Exception("添加失败！！");
                        }
                        $work_id = $this->model->id;
                    }

                    //仓库工单判断未处理异常，有则绑定异常
                    if ($params['order_item_numbers'] || in_array(3, $measureChooseId)) {
                        //主单取消：绑定该订单下所有子单异常
                        if (in_array(3, $measureChooseId)) {
                            $item_process_where['b.increment_id'] = $platform_order;
                            $type = 16;
                        } else {
                            $item_process_where['a.item_order_number'] = ['in', $params['order_item_numbers']];
                            $type = 17;
                        }

                        //获取子单ID集
                        $item_process_ids = $_new_order_item_process
                            ->alias('a')
                            ->join(['fa_order' => 'b'], 'a.order_id=b.id')
                            ->where($item_process_where)
                            ->column('a.id');

                        //获取绑定异常子单ID集
                        $abnormal_binding_ids = $_distribution_abnormal
                            ->where(['item_process_id' => ['in', $item_process_ids], 'status' => 1])
                            ->column('item_process_id');

                        //已经标记异常的子单，绑定异常数据
                        if (!empty($abnormal_binding_ids)) {
                            $_distribution_abnormal
                                ->allowField(true)
                                ->save(['work_id' => $work_id], ['item_process_id' => ['in', $abnormal_binding_ids], 'status' => 1]);

                            //配货操作日志
                            DistributionLog::record((object)session('admin'), $item_process_ids, 0, "创建工单绑定异常");
                            $need_sign_ids = array_diff($item_process_ids, $abnormal_binding_ids);
                        } else {
                            $need_sign_ids = $item_process_ids;
                        }

                        //未标记异常子单，则标记异常
                        if (!empty($need_sign_ids)) {
                            foreach ($need_sign_ids as $val) {
                                //获取异常库位号
                                $stock_house_info = $_stock_house
                                    ->field('id,coding')
                                    ->where(['status' => 1, 'type' => 4, 'occupy' => ['<', 10000]])
                                    ->order('occupy', 'desc')
                                    ->find();
                                if (empty($stock_house_info)) {
                                    throw new Exception("异常暂存架没有空余库位！！");
                                }

                                //创建异常
                                $abnormal_data = [
                                    'work_id'         => $work_id,
                                    'item_process_id' => $val,
                                    'type'            => $type,
                                    'status'          => 1,
                                    'create_time'     => time(),
                                    'create_person'   => $nickname,
                                ];
                                $_distribution_abnormal->allowField(true)->isUpdate(false)->data($abnormal_data)->save();

                                //子订单绑定异常库位号
                                $_new_order_item_process
                                    ->where(['id' => $val])
                                    ->update(['abnormal_house_id' => $stock_house_info['id']]);

                                //异常库位占用数量+1
                                $_stock_house
                                    ->where(['id' => $stock_house_info['id']])
                                    ->setInc('occupy', 1);

                                //配货日志
                                DistributionLog::record((object)session('admin'), $val, 9, "创建工单，异常暂存架{$stock_house_info['coding']}库位");
                            }
                        }
                    }

                    //工单备注
                    if (!empty($params['content'])) {
                        $noteData['note_time'] = date('Y-m-d H:i');
                        $noteData['note_user_id'] = $admin_id;
                        $noteData['note_user_name'] = $nickname;
                        $noteData['work_id'] = $work_id;
                        $noteData['user_group_id'] = 0;
                        $noteData['content'] = $params['content'];
                        $contentResult = $this->work_order_note->allowField(true)->save($noteData);
                        if (false === $contentResult) {
                            throw new Exception("备注添加失败！！");
                        }
                    }

                    //创建主订单措施、承接人数据
                    if (!empty($measureChooseId)) {
                        foreach ($measureChooseId as $v) {
                            //根据措施读取承接组、承接人 默认是客服问题组配置,是否审核之后自动完成
                            $appoint_ids = $params['order_recept']['appoint_ids'][$v];
                            $appoint_users = $params['order_recept']['appoint_users'][$v];
                            $appoint_group = $params['order_recept']['appoint_group'][$v];
                            $auto_complete = $params['order_recept']['auto_complete'][$v];

                            //插入措施、承接人数据
                            $res = $this->handle_measure($work_id, $v, $appoint_ids, $appoint_users, $appoint_group, $auto_complete, $this->assign_user_id, $admin_id, $nickname, $params, '');
                            if (!$res['result']) {
                                throw new Exception($res['msg']);
                            }
                        }
                    }

                    //创建子订单措施、承接人数据
                    if (!empty($item_order_info)) {
                        foreach ($item_order_info as $key => $item) {
                            if ($item['item_choose']) {
                                foreach ($item['item_choose'] as $v) {
                                    //根据措施读取承接组、承接人 默认是客服问题组配置,是否审核之后自动完成
                                    $appoint_ids = $item['appoint_ids'][$v];
                                    $appoint_users = $item['appoint_users'][$v];
                                    $appoint_group = $item['appoint_group'][$v];
                                    $auto_complete = $item['auto_complete'][$v];

                                    //插入措施、承接人数据
                                    $res = $this->handle_measure($work_id, $v, $appoint_ids, $appoint_users, $appoint_group, $auto_complete, $this->assign_user_id, $admin_id, $nickname, $params, $key);
                                    if (!$res['result']) {
                                        throw new Exception($res['msg']);
                                    }
                                }
                            }
                        }
                    }

                    //非草稿状态进入审核阶段
                    1 != $params['work_status'] && $this->model->checkWork($work_id);

                    if (!empty($row)) {
                        $row->commit();
                    }
                    $this->model->commit();
                    $this->work_order_note->commit();
                    $_distribution_abnormal->commit();
                    $_new_order_item_process->commit();
                    $_stock_house->commit();
                } catch (ValidateException $e) {
                    if (!empty($row)) {
                        $row->rollback();
                    }
                    $this->model->rollback();
                    $this->work_order_note->rollback();
                    $_distribution_abnormal->rollback();
                    $_new_order_item_process->rollback();
                    $_stock_house->rollback();
                    $this->error($e->getMessage());
                } catch (PDOException $e) {
                    if (!empty($row)) {
                        $row->rollback();
                    }
                    $this->model->rollback();
                    $this->work_order_note->rollback();
                    $_distribution_abnormal->rollback();
                    $_new_order_item_process->rollback();
                    $_stock_house->rollback();
                    $this->error($e->getMessage());
                } catch (Exception $e) {
                    if (!empty($row)) {
                        $row->rollback();
                    }
                    $this->model->rollback();
                    $this->work_order_note->rollback();
                    $_distribution_abnormal->rollback();
                    $_new_order_item_process->rollback();
                    $_stock_house->rollback();
                    $this->error($e->getMessage());
                }
                $this->success();
            }
            $this->error(__('Parameter %s can not be empty', ''));
        }

        //跟单处理
        $work_type = 1;//1客服 2仓库
        $problem_type = [];
        if ($ids) {
            //编辑、详情
            $row = $this->model->get($ids);
            $this->assignconfig('ids', $row->id);
            $this->assignconfig('problem_id', $row->problem_type_id);
            $this->view->assign('row', $row);

            //子订单措施及数据
            if (!empty($row->order_item_numbers)) {
                $order_data = $this->model->getOrderItem($row->platform_order, $row->order_item_numbers, $row->work_type, $row);
                unset($order_data['item_order_info']);
                $this->view->assign('order_item', $order_data);
            }

            //工单类型
            $work_type = $row->work_type;
        } else {
            //创建
            if (!empty($is_warehouse)) {
                $work_type = 2;
            }
        }

        //仓库创建工单
        $order_number = input('order_number');
        $order_item_numbers = input('order_item_numbers');
        if ($order_number && $order_item_numbers) {
            $order_item = $this->model->getOrderItem($order_number, $order_item_numbers, $work_type, []);
            $this->view->assign('order_item', $order_item);
        }

        //工单类型
        $this->view->assign('work_type', $work_type);
        $this->assignconfig('work_type', $work_type);

        //工单问题
        if (1 == $work_type) {
            $customer_problem_type = $workOrderConfigValue['customer_problem_type'];
            $customer_problem_classify = $workOrderConfigValue['customer_problem_classify'];
            unset($customer_problem_classify['仓库问题']);

            foreach ($customer_problem_classify as $key => $value) {
                $type = [];
                foreach ($value as $v) {
                    $type[] = ['id' => $v, 'name' => $customer_problem_type[$v]];
                }
                $problem_type[] = ['name' => $key, 'type' => $type];
            }
        } else {
            $problem_type = $workOrderConfigValue['warehouse_problem_type'];
        }
        $this->view->assign('problem_type', $problem_type);

        return $this->view->fetch();
    }

    /**
     * 判断是否审核并获取审核人ID
     *
     * @Author lzh
     *
     * @param array $info              审核组|审核人
     * @param array $measure_choose_id 措施ID
     * @param array $params            提交参数
     *
     * @return boolean
     */
    protected function weight_currency($info, $measure_choose_id, $params)
    {
        if (0 == $info['step_id']) {//不需要判断措施只需要判断创建人
            return false;
        } elseif (2 == $info['step_id'] && in_array(2, $measure_choose_id)) { //退款
            $median_value = $params['refund_money'];
        } elseif (3 == $info['step_id'] && in_array(3, $measure_choose_id)) { //取消
            $median_value = $params['refund_money'];
        } elseif (6 == $info['step_id'] && in_array(6, $measure_choose_id)) { //赠品
            $median_value = array_sum($params['gift']['original_number'] ?: []);
        } elseif (7 == $info['step_id'] && in_array(7, $measure_choose_id)) { //补发
            $median_value = array_sum($params['replacement']['original_number'] ?: []);
        } elseif (10 == $info['step_id'] && in_array(10, $measure_choose_id)) { //积分
            $median_value = $params['integral'];
        } elseif (15 == $info['step_id'] && in_array(15, $measure_choose_id)) {//VIP退款
            $median_value = $params['refund_money'];
        }

        $result = false;
        if (!empty($median_value)) {
            switch ($info['symbol']) {
                case 'gt':
                    $result = $median_value > $info['step_value'];
                    break;
                case 'eq':
                    $result = $median_value = $info['step_value'];
                    break;
                case 'lt':
                    $result = $median_value < $info['step_value'];
                    break;
                case 'egt':
                    $result = $median_value >= $info['step_value'];
                    break;
                case 'elt':
                    $result = $median_value <= $info['step_value'];
                    break;
            }
        }
        if ($result) {
            return false;
        }

        return true;
    }

    /**
     * 保存措施、承接人并处理相关流程
     *
     * @Author lzh
     *
     * @param int    $work_id           工单ID
     * @param int    $choose_id         选择的措施ID
     * @param array  $appoint_ids       承接人ID集合
     * @param array  $appoint_users     承接人名称集合
     * @param array  $appoint_group     承接人所在组集合
     * @param int    $auto_complete     是否审核之后自动完成
     * @param int    $assign_user_id    当前用户上级主管ID
     * @param int    $admin_id          当前用户ID
     * @param string $nickname          当前用户名称
     * @param array  $params            提交参数
     * @param string $item_order_number 子订单号
     *
     * @return array
     */
    protected function handle_measure($work_id, $choose_id, $appoint_ids, $appoint_users, $appoint_group, $auto_complete, $assign_user_id, $admin_id, $nickname, $params, $item_order_number)
    {
        //获取工单配置信息
        $workOrderConfigValue = $this->workOrderConfigValue;

        //措施内容
        $measure_content = $workOrderConfigValue['step'][$choose_id] ?: '';

        //措施表
        $_work_order_measure = new WorkOrderMeasure();

        //承接人表
        $_work_order_recept = new WorkOrderRecept();

        $_work_order_measure->startTrans();
        $_work_order_recept->startTrans();
        try {
            //插入措施表
            $res = $_work_order_measure
                ->allowField(true)
                ->save([
                    'work_id'           => $work_id,
                    'measure_choose_id' => $choose_id,
                    'measure_content'   => $measure_content,
                    'item_order_number' => $item_order_number,
                    'create_time'       => date('Y-m-d H:i:s'),
                ]);
            if (false === $res) {
                throw new Exception("添加措施失败！！");
            }

            //工单措施表自增ID
            $measure_id = $_work_order_measure->id;

            //循环插入承接人
            $appoint_save = [];
            if (is_array($appoint_ids) && !empty($appoint_ids)) {
                foreach ($appoint_ids as $key => $val) {
                    if ($appoint_users[$key] == 'undefined') {
                        continue;
                    }
                    //如果没有承接人 默认为创建人
                    if ($val == 'undefined') {
                        $recept_group_id = $assign_user_id;
                        $recept_person_id = $admin_id;
                        $recept_person = $nickname;
                    } else {
                        $recept_group_id = $appoint_group[$key];
                        $recept_person_id = $val;
                        $recept_person = $appoint_users[$key];
                    }
                    $appoint_save[] = [
                        'work_id'          => $work_id,
                        'measure_id'       => $measure_id,
                        'is_auto_complete' => $auto_complete ?: 0,
                        'recept_group_id'  => $recept_group_id,
                        'recept_person_id' => $recept_person_id,
                        'recept_person'    => $recept_person,
                        'create_time'      => date('Y-m-d H:i:s'),
                    ];
                }
            } else {
                $appoint_save[] = [
                    'work_id'          => $work_id,
                    'measure_id'       => $measure_id,
                    'is_auto_complete' => $auto_complete ?: 0,
                    'recept_group_id'  => 0,
                    'recept_person_id' => $admin_id,
                    'recept_person'    => $nickname,
                    'create_time'      => date('Y-m-d H:i:s'),
                ];
            }

            //插入承接人表
            $recept_res = $_work_order_recept->allowField(true)->saveAll($appoint_save);
            if (false === $recept_res) {
                throw new Exception("添加承接人失败！！");
            }

            $_work_order_measure->commit();
            $_work_order_recept->commit();
        } catch (PDOException $e) {
            $_work_order_measure->rollback();
            $_work_order_recept->rollback();

            return ['result' => false, 'msg' => $e->getMessage()];
        } catch (Exception $e) {
            $_work_order_measure->rollback();
            $_work_order_recept->rollback();

            return ['result' => false, 'msg' => $e->getMessage()];
        }

        //更改镜片、赠品、补发
        if (in_array($choose_id, [6, 7, 20])) {
            $this->model->changeLens($params, $work_id, $choose_id, $measure_id, $item_order_number);
        } elseif (19 == $choose_id) {//更改镜框
            $this->model->changeFrame($params, $work_id, $choose_id, $measure_id, $item_order_number);
        } elseif (in_array($choose_id, [3, 18])) {//取消
            $this->model->cancelOrder($params, $work_id, $choose_id, $measure_id, $item_order_number);
        } elseif (13 == $choose_id) {//修改地址
            $this->model->changeAddress($params, $work_id, $choose_id, $measure_id);
        }

        return ['result' => true, 'msg' => ''];
    }

    /**
     * 判断sku是否有库存
     *
     * @Description
     * @author wpl 
     *
     * @param array $skus     sku列表
     * @param int   $siteType 站点类型
     * @param array $num      站点类型
     *
     * @return array
     */
    protected function skuIsStock($skus = [], $siteType, $num = [], $platform_order)
    {
        if (!array_filter($skus)) {
            return ['result' => false, 'msg' => 'SKU不能为空'];
        }

        $itemPlatFormSku = new \app\admin\model\itemmanage\ItemPlatformSku();
        //根据平台sku转sku
        foreach (array_filter($skus) as $k => $v) {
            //判断库存时去掉-s 等
            $arr = explode('-', $v);
            if (!empty($arr[1])) {
                $sku = $arr[0] . '-' . $arr[1];
            } else {
                $sku = trim($v);
            }

            //判断是否开启预售 并且预售时间是否满足 并且预售数量是否足够
            if ($siteType == 13 || $siteType == 14) {
                $itemPlatFormSkuWhere = ['platform_sku' => $sku, 'platform_type' => $siteType];
            } else {
                $itemPlatFormSkuWhere = ['outer_sku_status' => 1, 'platform_sku' => $sku, 'platform_type' => $siteType];
            }
            $res = $itemPlatFormSku->where($itemPlatFormSkuWhere)->find();

            if (!empty($sku)) {

                $_new_order = new NewOrder();
                $stockId = $_new_order
                    ->where('increment_id', $platform_order)
                    ->value('stock_id');
                //查询仓库库区
                $area_id = Db::name('warehouse_area')->where(['stock_id' => $stockId, 'type' => 2])->value('id');

                /*****************限制如果有盘点单未结束不能操作配货完成*******************/
                //配货完成时判断
                //拣货区盘点时不能操作
                //查询条形码库区库位
                $whe_sku['sku'] = $res->sku;
                $whe_sku['library_status'] = 1;
                $whe_sku['stock_id'] = $stockId;
                $barcodedata = $this->_product_bar_code_item->where($whe_sku)->column('location_code');
                if (!empty($barcodedata)) {
                    $count = $this->_inventory->alias('a')
                        ->join(['fa_inventory_item' => 'b'], 'a.id=b.inventory_id')->where(['a.is_del' => 1, 'a.check_status' => ['in', [0, 1]], 'b.library_name' => ['in', $barcodedata], 'b.area_id' => $area_id])
                        ->count();
                    if ($count > 0) {
                        return ['result' => false, 'msg' => '此' . $sku . '对应库位正在盘点,暂无法进行出入库操作'];
                    }
                }
                /****************************end*****************************************/
            }

            //判断是否开启预售
            if ($res['stock'] >= 0 && $res['presell_status'] == 1 && strtotime($res['presell_create_time']) <= time() && strtotime($res['presell_end_time']) >= time()) {
                $stock = $res['stock'] + $res['presell_residue_num'];
            } elseif ($res['stock'] < 0 && $res['presell_status'] == 1 && strtotime($res['presell_create_time']) <= time() && strtotime($res['presell_end_time']) >= time()) {
                $stock = $res['presell_residue_num'];
            } else {
                $stock = $res['stock'];
            }
            //判断库存是否足够
            if ($stock < $num[$k]) {
                // $params = ['sku'=>$sku,'siteType'=>$siteType,'stock'=>$stock,'num'=>$num[$k]];
                // file_put_contents('/www/wwwroot/mojing/runtime/log/stock.txt',json_encode($params),FILE_APPEND);
                return ['result' => false, 'msg' => $sku . '库存不足！！当前虚拟仓库存' . $stock . '，补发所需库存' . $num[$k]];
            }

            //判断此sku是否在第三方平台
            if ($siteType == 13 || $siteType == 14) {
                $res = $this->model->httpRequest($siteType, 'api/mojing/check_sku', ['sku' => $sku, 'platform_order' => $platform_order], 'POST');
                if (empty($res[$sku])) {
                    return ['result' => false, 'msg' => $sku . '不存在！！'];
                }
                if ($res[$sku] < $num[$k]) {
                    return ['result' => false, 'msg' => $sku . '库存不足！！'];
                }
            }

        }

        return ['result' => true, 'msg' => ''];
    }

    /**
     * 编辑
     *
     * @Author   lzh
     * @DateTime 2020-11-23 11:29:24
     *
     * @param    [type] $ids
     *
     * @return void
     */
    public function edit($ids = null)
    {
        //获取工单配置信息
        $workOrderConfigValue = $this->workOrderConfigValue;

        //校验工单信息
        $row = $this->model->get($ids);
        !$row && $this->error(__('No Results were found'));

        //校验用户权限
        $adminIds = $this->getDataLimitAdminIds();
        is_array($adminIds) && !in_array($row[$this->dataLimitField], $adminIds) && $this->error(__('You have no permission'));

        //获取用户ID和所在权限组
        $admin_id = session('admin.id');
        $nickname = session('admin.nickname');
        $_auth_group_access = new AuthGroupAccess();
        $user_group_access = $_auth_group_access->where(['uid' => $admin_id])->column('group_id');

        if ($this->request->isPost()) {
            $params = $this->request->post("row/a");
            if ($params) {
                $params = $this->preExcludeFields($params);
                if ($this->dataLimit && $this->dataLimitFieldAutoFill) {
                    $params[$this->dataLimitField] = $this->auth->id;
                }

                //是否采用模型验证
                if ($this->modelValidate) {
                    $name = str_replace("\\model\\", "\\validate\\", get_class($this->model));
                    $validate = is_bool($this->modelValidate) ? ($this->modelSceneValidate ? $name . '.edit' : $name) : $this->modelValidate;
                    $this->model->validateFailException(true)->validate($validate);
                }

                $platform_order = trim($params['platform_order']);//订单号
                $measure_choose_id = $params['measure_choose_id'] ? array_unique(array_filter($params['measure_choose_id'])) : [];//措施ID数组
                $work_type = $params['work_type'];//工单类型：1客服 2仓库
                $item_order_info = $params['item_order_info'];//子订单措施
                $params['order_sku'] = $params['order_sku'] ? implode(',', $params['order_sku']) : '';//sku列表

                //校验问题类型、问题描述
                $params['problem_type_id'] = $params['problem_type_id'] ?: $params['problem_id'];
                !$params['problem_type_id'] && $this->error("请选择问题类型");
                !$params['problem_description'] && $this->error("问题描述不能为空");
                !$platform_order && $this->error("订单号不能为空");

                //指定问题类型校验sku下拉框是否勾选
                in_array($params['problem_type_id'], [8, 10, 11, 56, 13, 14, 15, 16, 18, 22, 59])
                && empty($params['order_sku'])
                && $this->error("请选择sku");

                //校验工单类型
                if (1 == $work_type) {
                    //校验工单措施
                    empty($measure_choose_id) && empty($item_order_info) && $this->error("请选择实施措施");

                    $params['problem_type_content'] = $workOrderConfigValue['customer_problem_type'][$params['problem_type_id']];
                } else {
                    $all_after_user_id = array_filter($params['all_after_user_id']);
                    empty($all_after_user_id) && $this->error("未找到对应承接人,请重新选择");
                    $params['all_after_user_id'] = implode(',', $all_after_user_id);
                    $params['problem_type_content'] = $workOrderConfigValue['warehouse_problem_type'][$params['problem_type_id']];
                }

                //主单和子单全部的措施id
                $all_choose_ids = [];

                //检测主订单措施
                if (!empty($measure_choose_id)) {
                    /**
                     * 审核判断条件
                     * 1、退款金额大于30 经理审核
                     * 2、赠品数量大于1 经理审核
                     * 3、补发数量大于1 经理审核
                     * 4、优惠券等于100% 经理审核  50%主管审核 固定额度无需审核
                     * 5、运营客服组的优惠券都由客服经理审核
                     */

                    $all_choose_ids = $measure_choose_id;

                    //主单取消
                    if (in_array(3, $measure_choose_id)) {
                        $_new_order = new NewOrder();
                        $orderList = $_new_order->field('id,stock_id')
                            ->where('increment_id', $params['platform_order'])
                            ->find();
                        $order_id = $orderList->id;
                        if ($order_id) {
                            $order_item_where['order_id'] = $order_id;
                            $_new_order_item_process = new NewOrderItemProcess();
                            $order_item_list = $_new_order_item_process
                                ->where($order_item_where)
                                ->column('sku');
                            /*****************限制如果有盘点单未结束不能操作配货完成*******************/

                            //查询仓库库区
                            $area_id = Db::name('warehouse_area')->where(['stock_id' => $orderList->stock_id, 'type' => 2])->value('id');

                            //配货完成时判断
                            //拣货区盘点时不能操作
                            //查询条形码库区库位
                            $whe_sku['platform_sku'] = ['in', $order_item_list];
                            //转换sku
                            $item_platform_sku = new ItemPlatformSku();
                            $true_sku = $item_platform_sku->where($whe_sku)->column('sku');
                            $whe['sku'] = ['in', $true_sku];
                            $whe['library_status'] = 1;
                            $whe['stock_id'] = $orderList->stock_id;
                            $barcodedata = $this->_product_bar_code_item->where($whe)->column('location_code');
                            if (!empty($barcodedata)) {
                                $count = $this->_inventory->alias('a')
                                    ->join(['fa_inventory_item' => 'b'], 'a.id=b.inventory_id')->where(['a.is_del' => 1, 'a.check_status' => ['in', [0, 1]], 'library_name' => ['in', $barcodedata], 'area_id' => $area_id])
                                    ->count();
                                if ($count > 0) {
                                    return ['result' => false, 'msg' => '此主单下的子订单SKU对应库位正在盘点,暂无法进行出入库操作'];
                                }
                            }
                            /****************************end*****************************************/
                        }
                    }

                    //校验退款、vip退款
                    if (array_intersect([2, 15], $measure_choose_id)) {
                        !$params['refund_money'] && $this->error("退款金额不能为空");
                        $params['is_refund'] = 1;
                    } else {
                        unset($params['refund_money']);
                    }

                    //校验赠品、补发库存
                    if (array_intersect([6, 7], $measure_choose_id)) {
                        $original_sku = [];

                        //赠品
                        if (in_array(6, $measure_choose_id)) {
                            $gift_sku = $params['gift']['original_sku'];
                            !$gift_sku && $this->error("赠品sku不能为空");

                            $gift_number = $params['gift']['original_number'];
                            !$gift_number && $this->error("赠品数量不能为空");

                            foreach ($gift_sku as $key => $sku) {
                                $num = $key + 1;
                                !$sku && $this->error("第{$num}个赠品sku不能为空");
                                !$gift_number[$key] && $this->error("第{$num}个赠品数量必须大于0");

                                if (isset($original_sku[$sku])) {
                                    $original_sku[$sku] += $gift_number[$key];
                                } else {
                                    $original_sku[$sku] = $gift_number[$key];
                                }
                            }
                        }

                        //补发
                        if (in_array(7, $measure_choose_id)) {
                            !$params['address']['shipping_type'] && $this->error("请选择Shipping Method");

                            $replacement_sku = $params['replacement']['original_sku'];
                            !$replacement_sku && $this->error("补发sku不能为空");

                            $replacement_number = $params['replacement']['original_number'];
                            !$replacement_number && $this->error("补发数量不能为空");

                            foreach ($replacement_sku as $key => $sku) {
                                $num = $key + 1;
                                !$sku && $this->error("第{$num}个补发sku不能为空");
                                !$replacement_number[$key] && $this->error("第{$num}个补发数量必须大于0");

                                if (isset($original_sku[$sku])) {
                                    $original_sku[$sku] += $replacement_number[$key];
                                } else {
                                    $original_sku[$sku] = $replacement_number[$key];
                                }
                            }
                        }

                        //校验库存
                        if ($original_sku) {
                            $back_data = $this->skuIsStock(array_keys($original_sku), $params['work_platform'], array_values($original_sku), $platform_order);
                            !$back_data['result'] && $this->error($back_data['msg']);
                        }
                    }

                    //校验补价措施
                    if (in_array(8, $measure_choose_id)) {
                        !$params['replenish_money'] && $this->error("补差价金额不能为空");
                    } else {
                        unset($params['replenish_money']);
                    }

                    //校验优惠券措施
                    if (in_array(9, $measure_choose_id)) {
                        !$params['coupon_id'] && !$params['need_coupon_id'] && $this->error("请选择优惠券");

                        //不需要审核的优惠券
                        if ($params['coupon_id']) {
                            $check_coupon = $workOrderConfigValue['check_coupon'];
                        } else {
                            //需要审核的优惠券
                            $params['is_check'] = 1;
                            $params['coupon_id'] = $params['need_coupon_id'];
                            $check_coupon = $workOrderConfigValue['need_check_coupon'];

                            //优惠券折扣
                            $discount = $workOrderConfigValue['need_check_coupon'][$params['need_coupon_id']]['sum'];
                            if (100 == $discount || (0 < $discount && in_array(131, $user_group_access))) {
                                //创建人上级经理
                                $params['assign_user_id'] = $workOrderConfigValue['customer_manager'];
                            } elseif (50 == $discount) {
                                //创建人上级主管
                                $params['assign_user_id'] = $this->assign_user_id ?: $admin_id;
                            }
                        }
                        foreach ($check_coupon as $v) {
                            if ($v['id'] == $params['coupon_id']) {
                                $params['coupon_describe'] = $v['desc'];
                                break;
                            }
                        }
                    }

                    //判断是否选择积分措施
                    if (in_array(10, $measure_choose_id)) {
                        (!$params['integral'] || !is_numeric($params['integral']))
                        && $this->error("积分必须是数字");
                    } else {
                        unset($params['integral']);
                        unset($params['integral_describe']);
                    }

                    //判断是否选择退件措施
                    if (in_array(11, $measure_choose_id)) {
                        !$params['refund_logistics_num'] && $this->error("退回物流单号不能为空");
                    } else {
                        unset($params['refund_logistics_num']);
                    }
                }

                //子单sku变动表
                $_work_order_change_sku = new WorkOrderChangeSku();

                //检测子订单措施
                if ($item_order_info) {

                    $item_order_info = array_filter($item_order_info);
                    //查询所有子单数量
                    $_new_order_process = new NewOrderProcess();
                    $order_id = $_new_order_process->where('increment_id', $platform_order)->value('order_id');
                    $_new_order_item_process = new NewOrderItemProcess();
                    $count_item_num = $_new_order_item_process->where('order_id', $order_id)->count();

                    $_new_order = new NewOrder();
                    $stock_id = $_new_order->where('increment_id', $platform_order)
                        ->value('stock_id');
                    //查询仓库库区
                    $area_id = Db::name('warehouse_area')->where(['stock_id' => $stock_id, 'type' => 2])->value('id');

                    1 > count($item_order_info) && $this->error("子订单号错误");

                    foreach ($item_order_info as $key => &$item) {
                        $item['item_choose'] = array_unique(array_filter($item['item_choose']));
                        if ($count_item_num != count($item_order_info)) {
                            empty($item['item_choose']) && $this->error("请选择子订单：{$key} 的实施措施");
                        }
                        $all_choose_ids = array_unique(array_merge($all_choose_ids, $item['item_choose']));

                        //获取子单之前处理成功的措施类型
                        $change_type = $_work_order_change_sku
                            ->alias('a')
                            ->join(['fa_work_order_measure' => 'b'], 'a.measure_id=b.id')
                            ->where([
                                'a.item_order_number' => $key,
                                'b.operation_type'    => 1,
                            ])
                            ->order('a.id', 'desc')
                            ->group('a.item_order_number')
                            ->column('a.change_type');

                        //子单取消
                        if (in_array(18, $item['item_choose'])) {
                            //检测之前是否处理过子单措施
                            array_intersect([1, 2, 3], $change_type) && $this->error("子订单：{$key} 措施已处理，不能取消");
                            /*****************限制如果有盘点单未结束不能操作配货完成*******************/
                            //拣货区盘点时不能操作
                            //查询条形码库区库位
                            $whe_sku['platform_sku'] = $item['cancel_order']['sku'];
                            //转换sku
                            $item_platform_sku = new ItemPlatformSku();
                            $true_sku = $item_platform_sku->where($whe_sku)->value('sku');
                            $whe['sku'] = $true_sku;
                            $whe['library_status'] = 1;
                            $whe['stock_id'] = $stock_id;
                            $barcodedata = $this->_product_bar_code_item->where($whe)->column('location_code');
                            if (!empty($barcodedata)) {
                                $count = $this->_inventory->alias('a')
                                    ->join(['fa_inventory_item' => 'b'], 'a.id=b.inventory_id')->where(['a.is_del' => 1, 'a.check_status' => ['in', [0, 1]], 'library_name' => ['in', $barcodedata], 'area_id' => $area_id])
                                    ->count();
                                if ($count > 0) {
                                    return ['result' => false, 'msg' => '此' . $item['cancel_order']['sku'] . '对应库位正在盘点,暂无法进行出入库操作'];
                                }
                            }
                            /****************************end*****************************************/

                        } elseif (in_array(19, $item['item_choose'])) {//更改镜框
                            //检测之前是否处理过更改镜框措施
                            in_array(1, $change_type) && $this->error("子订单：{$key} 措施已处理，不能重复创建");
                            /*****************限制如果有盘点单未结束不能操作配货完成*******************/
                            //拣货区盘点时不能操作
                            //查询条形码库区库位

                            $whe_sku['platform_sku'] = $item['change_frame']['original_sku'];
                            //转换sku
                            $item_platform_sku = new ItemPlatformSku();
                            $true_sku = $item_platform_sku->where($whe_sku)->value('sku');
                            $whe['sku'] = $true_sku;
                            $whe['library_status'] = 1;
                            $whe['stock_id'] = $stock_id;
                            $barcodedata = $this->_product_bar_code_item->where($whe)->column('location_code');
                            if (!empty($barcodedata)) {
                                $count = $this->_inventory->alias('a')
                                    ->join(['fa_inventory_item' => 'b'], 'a.id=b.inventory_id')->where(['a.is_del' => 1, 'a.check_status' => ['in', [0, 1]], 'library_name' => ['in', $barcodedata], 'area_id' => $area_id])
                                    ->count();
                                if ($count > 0) {
                                    return ['result' => false, 'msg' => '此' . $item['cancel_order']['sku'] . '对应库位正在盘点,暂无法进行出入库操作'];
                                }
                            }
                            /****************************end*****************************************/

                            /*****************限制如果有盘点单未结束不能操作配货完成*******************/
                            //拣货区盘点时不能操作
                            //查询条形码库区库位
                            $whe_sku['platform_sku'] = $item['change_frame']['change_sku'];
                            //转换sku
                            $item_platform_sku = new ItemPlatformSku();
                            $true_sku = $item_platform_sku->where($whe_sku)->value('sku');
                            $whe['sku'] = $true_sku;
                            $whe['library_status'] = 1;
                            $whe['stock_id'] = $stock_id;
                            $barcodedata = $this->_product_bar_code_item->where($whe)->column('location_code');
                            if (!empty($barcodedata)) {
                                $count = $this->_inventory->alias('a')
                                    ->join(['fa_inventory_item' => 'b'], 'a.id=b.inventory_id')->where(['a.is_del' => 1, 'a.check_status' => ['in', [0, 1]], 'library_name' => ['in', $barcodedata], 'area_id' => $area_id])
                                    ->count();
                                if ($count > 0) {
                                    return ['result' => false, 'msg' => '此' . $item['change_frame']['change_sku'] . '对应库位正在盘点,暂无法进行出入库操作'];
                                }
                            }
                            /****************************end*****************************************/


                            //更改镜框校验库存
                            !$item['change_frame']['change_sku'] && $this->error("子订单：{$key} 的新sku不能为空");
                            $item['change_frame']['change_sku'] = trim($item['change_frame']['change_sku']);
                            $back_data = $this->skuIsStock([$item['change_frame']['change_sku']], $params['work_platform'], [1], $platform_order);
                            !$back_data['result'] && $this->error($back_data['msg']);
                        } /*elseif (in_array(20, $item['item_choose'])) {//更改镜片
                            //检测之前是否处理过更改镜片措施
                            in_array(2, $change_type) && $this->error("子订单：{$key} 措施已处理，不能重复创建");
                        }*/
                    }
                    unset($item);
                }

                /**获取审核人 start*/
                $check_person_weight = $workOrderConfigValue['check_person_weight'];//审核人列表
                $check_group_weight = $workOrderConfigValue['check_group_weight'];//审核组列表
                $all_group = $workOrderConfigValue['group'];//所有的成员组

                //核算审核组
                if (!empty($check_group_weight)) {
                    foreach ($check_group_weight as $gv) {
                        $all_person = [];
                        //获取当前组下的所有成员
                        $subordinate = (new AuthGroup)->getAllNextGroup($gv['work_create_person_id']);
                        if ($subordinate) {
                            array_push($subordinate, $gv['work_create_person_id']);
                            foreach ($subordinate as $av) {
                                if (is_array($all_group[$av])) {
                                    foreach ($all_group[$av] as $vk) {
                                        $all_person[] = $vk;
                                    }
                                }
                            }
                        } else {
                            $all_person = $all_group[$gv['work_create_person_id']];
                        }
                        if (!empty($all_person)) {
                            //如果符合创建组
                            if (in_array($admin_id, array_unique($all_person))) {
                                if (!$this->weight_currency($gv, $all_choose_ids, $params)) {
                                    $params['is_check'] = 1;
                                    $params['assign_user_id'] = $all_group[$gv['check_group_id']][0];
                                    break;
                                }
                            }
                        }
                    }
                }

                //核算审核人
                if (!empty($check_person_weight)) {
                    foreach ($check_person_weight as $wkv) {
                        if ($admin_id == $wkv['work_create_person_id']) {
                            if (!$this->weight_currency($wkv, $all_choose_ids, $params)) {
                                $params['is_check'] = 1;
                                $params['assign_user_id'] = $all_group[$wkv['check_group_id']][0];
                                break;
                            }
                        }
                    }
                }

                //没有审核人则不需要审核
                if (!$params['assign_user_id']) {
                    $params['is_check'] = 0;
                } else {
                    $params['is_check'] = 0;
                }
                /**获取审核人 end*/

                //点击提交按钮
                if (2 == $params['work_status']) {
                    //不需要审核或工单类型为仓库 工单状态默认为审核通过
                    if (0 == $params['is_check'] || 2 == $params['work_type']) {
                        $params['work_status'] = 3;
                    }
                    $params['submit_time'] = date('Y-m-d H:i:s');
                }

                //vip订单
                if (100 == $params['order_type']) {
                    $params['base_grand_total'] = $params['refund_money'];
                    $params['grand_total'] = $params['refund_money'];
                }
                $params['recept_person_id'] = $params['recept_person_id'] ?: $admin_id;

                //措施表
                $_work_order_measure = new WorkOrderMeasure();

                //承接人表
                $_work_order_recept = new WorkOrderRecept();

                //子单表
                $_new_order_item_process = new NewOrderItemProcess();

                //配货异常表
                $_distribution_abnormal = new DistributionAbnormal();

                //库位表
                $_stock_house = new StockHouse();

                $row->startTrans();
                $_work_order_measure->startTrans();
                $_work_order_recept->startTrans();
                $_work_order_change_sku->startTrans();
                $_stock_house->startTrans();
                $_new_order_item_process->startTrans();
                $_distribution_abnormal->startTrans();
                try {
                    //更新之前清除部分字段
                    $update_data = [
                        'replenish_money'        => '',
                        'replenish_increment_id' => '',
                        'coupon_id'              => 0,
                        'coupon_describe'        => '',
                        'coupon_str'             => '',
                        'integral'               => '',
                        'refund_logistics_num'   => '',
                        'refund_money'           => '',
                        'is_refund'              => 0,
                        'replacement_order'      => '',
                        'integral_describe'      => '',
                    ];
                    $update_res = $row->allowField(true)->save($update_data);
                    if (false === $update_res) {
                        throw new Exception('更新失败!!');
                    }

                    //仓库工单判断未处理异常，有则绑定异常
                    if ($params['order_item_numbers'] || in_array(3, $measure_choose_id)) {
                        //主单取消：绑定该订单下所有子单异常
                        if (in_array(3, $measure_choose_id)) {
                            $item_process_where['b.increment_id'] = $platform_order;
                            $type = 16;
                        } else {
                            $item_process_where['a.item_order_number'] = ['in', $params['order_item_numbers']];
                            $type = 17;
                        }

                        //获取子单ID集
                        $item_process_ids = $_new_order_item_process
                            ->alias('a')
                            ->join(['fa_order' => 'b'], 'a.order_id=b.id')
                            ->where($item_process_where)
                            ->column('a.id', 'a.item_order_number');
                        $item_order_numbers = array_keys($item_process_ids);

                        //获取之前的子单列表
                        $bound_item_order_numbers = $_work_order_change_sku->where(['work_id' => $row->id])->column('item_order_number');
                        $remove_numbers = $bound_item_order_numbers ? array_diff($bound_item_order_numbers, $item_order_numbers) : [];

                        //清除之前的子单异常、解绑子单库位、异常库位减1
                        if ($remove_numbers) {
                            foreach ($remove_numbers as $val) {
                                //获取子单信息
                                $item_process_info = $_new_order_item_process
                                    ->field('id,abnormal_house_id')
                                    ->where(['item_order_number' => $val])
                                    ->find();

                                //删除异常
                                $check_abnormal = $_distribution_abnormal
                                    ->field('type,id')
                                    ->where(['work_id' => $row->id, 'item_process_id' => $item_process_info['id']])
                                    ->find();
                                if ($check_abnormal && in_array($check_abnormal['type'], [16, 17])) {
                                    $_distribution_abnormal->where(['id' => $check_abnormal['id']])->delete();

                                    //子订单解绑异常库位
                                    $_new_order_item_process
                                        ->where(['id' => $item_process_info['id']])
                                        ->update(['abnormal_house_id' => 0]);

                                    //异常库位占用数量-1
                                    if ($item_process_info['abnormal_house_id']) {
                                        $_stock_house
                                            ->where(['id' => $item_process_info['abnormal_house_id']])
                                            ->setDec('occupy', 1);
                                    }

                                    //配货日志
                                    DistributionLog::record((object)session('admin'), $val, 10, "编辑工单，解绑异常库位");
                                }
                            }
                        }

                        //获取绑定异常子单ID集
                        $abnormal_binding_ids = $_distribution_abnormal
                            ->where(['item_process_id' => ['in', $item_process_ids], 'status' => 1])
                            ->column('item_process_id');

                        //已经标记异常的子单，绑定异常数据
                        if (!empty($abnormal_binding_ids)) {
                            $_distribution_abnormal
                                ->allowField(true)
                                ->save(['work_id' => $row->id], ['item_process_id' => ['in', $abnormal_binding_ids], 'status' => 1]);

                            //配货操作日志
                            DistributionLog::record((object)session('admin'), $item_process_ids, 0, "编辑工单绑定异常");
                            $need_sign_ids = array_diff($item_process_ids, $abnormal_binding_ids);
                        } else {
                            $need_sign_ids = $item_process_ids;
                        }

                        //未标记异常子单，则标记异常
                        if (!empty($need_sign_ids)) {
                            foreach ($need_sign_ids as $val) {
                                //获取异常库位号
                                $stock_house_info = $_stock_house
                                    ->field('id,coding')
                                    ->where(['status' => 1, 'type' => 4, 'occupy' => ['<', 10000]])
                                    ->order('occupy', 'desc')
                                    ->find();
                                if (empty($stock_house_info)) {
                                    throw new Exception("异常暂存架没有空余库位！！");
                                }

                                //创建异常
                                $abnormal_data = [
                                    'work_id'         => $row->id,
                                    'item_process_id' => $val,
                                    'type'            => $type,
                                    'status'          => 1,
                                    'create_time'     => time(),
                                    'create_person'   => $nickname,
                                ];
                                $_distribution_abnormal->allowField(true)->isUpdate(false)->data($abnormal_data)->save();

                                //子订单绑定异常库位号
                                $_new_order_item_process
                                    ->where(['id' => $val])
                                    ->update(['abnormal_house_id' => $stock_house_info['id']]);

                                //异常库位占用数量+1
                                $_stock_house
                                    ->where(['id' => $stock_house_info['id']])
                                    ->setInc('occupy', 1);

                                //配货日志
                                DistributionLog::record((object)session('admin'), $val, 9, "编辑工单，异常暂存架{$stock_house_info['coding']}库位");
                            }
                        }
                    }

                    //清除措施表、承接表、sku变动表
                    $_work_order_measure->where(['work_id' => $row->id])->delete();
                    $_work_order_recept->where(['work_id' => $row->id])->delete();
                    $_work_order_change_sku->where(['work_id' => $row->id])->delete();

                    //更新工单
                    $result = $row->allowField(true)->save($params);
                    if (false === $result) {
                        throw new Exception("编辑失败！！");
                    }

                    //创建主订单措施、承接人数据
                    if (!empty($measure_choose_id)) {
                        foreach ($measure_choose_id as $v) {
                            //根据措施读取承接组、承接人 默认是客服问题组配置,是否审核之后自动完成
                            $appoint_ids = $params['order_recept']['appoint_ids'][$v];
                            $appoint_users = $params['order_recept']['appoint_users'][$v];
                            $appoint_group = $params['order_recept']['appoint_group'][$v];
                            $auto_complete = $params['order_recept']['auto_complete'][$v];

                            //插入措施、承接人数据
                            $res = $this->handle_measure($row->id, $v, $appoint_ids, $appoint_users, $appoint_group, $auto_complete, $this->assign_user_id, $admin_id, $nickname, $params, '');
                            if (!$res['result']) {
                                throw new Exception($res['msg']);
                            }
                        }
                    }

                    //创建子订单措施、承接人数据
                    if (!empty($item_order_info)) {
                        foreach ($item_order_info as $key => $item) {
                            if ($item['item_choose']) {
                                foreach ($item['item_choose'] as $v) {
                                    //根据措施读取承接组、承接人 默认是客服问题组配置,是否审核之后自动完成
                                    $appoint_ids = $item['appoint_ids'][$v];
                                    $appoint_users = $item['appoint_users'][$v];
                                    $appoint_group = $item['appoint_group'][$v];
                                    $auto_complete = $item['auto_complete'][$v];

                                    //插入措施、承接人数据
                                    $res = $this->handle_measure($row->id, $v, $appoint_ids, $appoint_users, $appoint_group, $auto_complete, $this->assign_user_id, $admin_id, $nickname, $params, $key);
                                    if (!$res['result']) {
                                        throw new Exception($res['msg']);
                                    }
                                }
                            }
                        }
                    }

                    //非草稿状态进入审核阶段
                    1 != $params['work_status'] && $this->model->checkWork($row->id);

                    $row->commit();
                    $_work_order_measure->commit();
                    $_work_order_recept->commit();
                    $_work_order_change_sku->commit();
                    $_stock_house->commit();
                    $_new_order_item_process->commit();
                    $_distribution_abnormal->commit();
                } catch (ValidateException $e) {
                    $row->rollback();
                    $_work_order_measure->rollback();
                    $_work_order_recept->rollback();
                    $_work_order_change_sku->rollback();
                    $_stock_house->rollback();
                    $_new_order_item_process->rollback();
                    $_distribution_abnormal->rollback();
                    $this->error($e->getMessage());
                } catch (PDOException $e) {
                    $row->rollback();
                    $_work_order_measure->rollback();
                    $_work_order_recept->rollback();
                    $_work_order_change_sku->rollback();
                    $_stock_house->rollback();
                    $_new_order_item_process->rollback();
                    $_distribution_abnormal->rollback();
                    $this->error($e->getMessage());
                } catch (Exception $e) {
                    $row->rollback();
                    $_work_order_measure->rollback();
                    $_work_order_recept->rollback();
                    $_work_order_change_sku->rollback();
                    $_stock_house->rollback();
                    $_new_order_item_process->rollback();
                    $_distribution_abnormal->rollback();
                    $this->error($e->getMessage());
                }
                $this->success();
            }
            $this->error(__('Parameter %s can not be empty', ''));
        }

        //工单类型及状态
        $this->view->assign('row', $row);
        $this->assignconfig('work_type', $row->work_type);
        $this->assignconfig('work_status', $row->work_status);
        $this->assignconfig('create_user_id', $row->create_user_id);

        //子订单措施及数据
        $order_data = $this->model->getOrderItem($row->platform_order, $row->order_item_numbers, $row->work_type, $row);
        if (!empty($order_data['item_order_info'])) {
            $this->assignconfig('item_order_info', $order_data['item_order_info']);
            unset($order_data['item_order_info']);
        }
        $this->assignconfig('order_item', $order_data);
        $this->view->assign('order_item', $order_data);

        //把问题类型传递到js页面
        $row->problem_type_id && $this->assignconfig('problem_type_id', $row->problem_type_id);

        //工单措施对应的措施配置表ID数组
        $measureList = WorkOrderMeasure::workMeasureList($row->id, 1);
        !empty($measureList) && $this->assignconfig('measureList', $measureList);

        //回复内容
        $workOrderNote = WorkOrderNote::where('work_id', $ids)->select();
        $this->view->assign('workOrderNote', $workOrderNote);

        //工单问题类型
        $problem_type = [];
        if (1 == $row->work_type) {
            $customer_problem_type = $workOrderConfigValue['customer_problem_type'];
            $customer_problem_classify = $workOrderConfigValue['customer_problem_classify'];
            unset($customer_problem_classify['仓库问题']);

            foreach ($customer_problem_classify as $key => $value) {
                $type = [];
                foreach ($value as $v) {
                    $type[] = ['id' => $v, 'name' => $customer_problem_type[$v]];
                }
                $problem_type[] = ['name' => $key, 'type' => $type];
            }
        } else {
            $problem_type = $workOrderConfigValue['warehouse_problem_type'];
        }
        $this->view->assign('problem_type', $problem_type);

        return $this->view->fetch();
    }

    /**
     * 获取订单sku数据
     *
     * @参数 string order_number  订单号
     * @author lzh
     * @return array
     */
    public function get_sku_list()
    {
        !request()->isAjax() && $this->error('404 not found');

        $order_number = request()->post('order_number');
        empty($order_number) && $this->error('订单号不能为空');

        $result = $this->model->getOrderItem($order_number, '', 0, [], 1);
        empty($result) && $this->error('未获取到数据');
        //empty($result['sku_list']) && $this->error('未获取到子单数据');

        $this->success('', '', $result, 0);
    }

    /**
     * 根据处方获取地址信息以及处方信息
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function ajaxGetAddress()
    {
        if (request()->isAjax()) {
            $incrementId = input('increment_id');
            $siteType = input('site_type');
            $work_id = input('work_id');
            $measure_choose_id = input('measure_choose_id');

            $res = [];
            $lens = [];
            try {
                //获取网站数据库地址,获取地址信息
                $res = $this->model->getAddress($incrementId);
                !$res && $this->error('未获取到数据！！');

                //请求接口获取lens_type，coating_type，prescription_type等信息
                $lens = $this->model->getReissueLens($siteType, $res['showPrescriptions'], 1);

                //判断是否是新建或跟单处理
                if ($work_id && $measure_choose_id) {
                    $work_status = $this->model->where('id', $work_id)->value('work_status');
                    if (0 < $work_status) {
                        //获取魔晶数据库中地址
                        $_work_order_change_sku = new WorkOrderChangeSku();
                        $address = $_work_order_change_sku
                            ->alias('a')
                            ->join(['fa_work_order_measure' => 'b'], 'a.measure_id=b.id')
                            ->where(['a.work_id' => $work_id, 'b.measure_choose_id' => $measure_choose_id])
                            ->value('a.userinfo_option');
                        if ($address) {
                            $address = unserialize($address);
                            $address['address_type'] = $address['address_id'] == 0 ? 'shipping' : 'billing';
                            $res['address'] = $address;
                        }
                    }
                }
            } catch (\Exception $e) {
                $this->error($e->getMessage());
            }
            $this->success('操作成功！！', '', ['address' => $res, 'lens' => $lens]);
        }
        $this->error('404 not found');
    }

    /**
     * 根据country获取Province
     * @return array
     */
    public function ajaxGetProvince()
    {
        $countryId = input('country_id');
        $country = json_decode(file_get_contents('assets/js/country.js'), true);
        $province = $country[$countryId];

        return $province ?: [];
    }

    /**
     * 获取更改镜片的数据
     * @throws Exception
     */
    public function ajaxGetChangeLens()
    {
        if (request()->isAjax()) {
            $incrementId = input('increment_id');
            $siteType = input('site_type');
            $item_order_number = input('item_order_number', '');
            try {
                //获取地址、处方等信息
                $res = $this->model->getAddress($incrementId, $item_order_number);

                //获取更改镜片最新处方信息
                $_work_order_change_sku = new WorkOrderChangeSku();
                $change_lens = $_work_order_change_sku
                    ->alias('a')
                    ->field('a.od_sph,a.od_cyl,a.od_axis,a.od_add,a.pd_r,a.od_pv,a.od_bd,a.od_pv_r,a.od_bd_r,a.os_sph,a.os_cyl,a.os_axis,a.os_add,a.pd_l,a.os_pv,a.os_bd,a.os_pv_r,a.os_bd_r,a.lens_number,a.recipe_type as prescription_type,prescription_option')
                    ->join(['fa_work_order_measure' => 'b'], 'a.measure_id=b.id')
                    ->where([
                        'a.change_type'       => 2,
                        'a.item_order_number' => $item_order_number,
                        'b.operation_type'    => 1,
                    ])
                    ->order('a.id', 'desc')
                    ->find();
                $change_lens = collection([$change_lens])->toArray();
                if ($change_lens[0]) {//之前更改过处方，获取最新的处方
                    $prescription_option = unserialize($change_lens[0]['prescription_option']);
                    //替换处方信息
                    $res['prescriptions'][0]['prescription_type'] = $prescription_option['prescription_type'];
                    $res['prescriptions'][0]['index_id'] = $prescription_option['lens_id'];
                    $res['prescriptions'][0]['index_type'] = $prescription_option['lens_type'];
                    $res['prescriptions'][0]['coating_id'] = $prescription_option['coating_id'];
                    $res['prescriptions'][0]['color_id'] = $prescription_option['color_id'];
                    $res['prescriptions'][0]['od_sph'] = $change_lens[0]['od_sph'];
                    $res['prescriptions'][0]['os_sph'] = $change_lens[0]['os_sph'];
                    $res['prescriptions'][0]['od_cyl'] = $change_lens[0]['od_cyl'];
                    $res['prescriptions'][0]['os_cyl'] = $change_lens[0]['os_cyl'];
                    $res['prescriptions'][0]['od_axis'] = $change_lens[0]['od_axis'];
                    $res['prescriptions'][0]['os_axis'] = $change_lens[0]['os_axis'];
                    if (!empty($change_lens[0]['pd_r']) && empty($change_lens[0]['pd_l'])) {
                        $res['prescriptions'][0]['pd'] = $change_lens[0]['pd_r'];
                    }
                    $res['prescriptions'][0]['pd_l'] = $change_lens[0]['pd_l'];
                    $res['prescriptions'][0]['pd_r'] = $change_lens[0]['pd_r'];
                    $res['prescriptions'][0]['os_add'] = $change_lens[0]['os_add'];
                    $res['prescriptions'][0]['od_add'] = $change_lens[0]['od_add'];
                    $res['prescriptions'][0]['od_pv'] = $change_lens[0]['od_pv'];
                    $res['prescriptions'][0]['os_pv'] = $change_lens[0]['os_pv'];
                    $res['prescriptions'][0]['od_pv_r'] = $change_lens[0]['od_pv_r'];
                    $res['prescriptions'][0]['os_pv_r'] = $change_lens[0]['os_pv_r'];
                    $res['prescriptions'][0]['od_bd'] = $change_lens[0]['od_bd'];
                    $res['prescriptions'][0]['os_bd'] = $change_lens[0]['os_bd'];
                    $res['prescriptions'][0]['od_bd_r'] = $change_lens[0]['od_bd_r'];
                    $res['prescriptions'][0]['os_bd_r'] = $change_lens[0]['os_bd_r'];
                    //获取更改镜框最新信息
                    $change_sku = $_work_order_change_sku
                        ->alias('a')
                        ->join(['fa_work_order_measure' => 'b'], 'a.measure_id=b.id')
                        ->where([
                            'a.change_type'       => 1,
                            'a.item_order_number' => $item_order_number,
                            'b.operation_type'    => 1,
                        ])
                        ->order('a.id', 'desc')
                        ->value('a.change_sku');
                    if ($change_sku) {
                        $res['prescriptions'][0]['sku'] = $change_sku;
                    }
                }
                $lens = $this->model->getReissueLens($siteType, $res['prescriptions'], 2, $item_order_number);
            } catch (\Exception $e) {
                $this->error($e->getMessage());
            }
            if ($res) {
                $this->success('操作成功！！', '', $lens);
            } else {
                $this->error('未获取到数据！！');
            }
        }
        $this->error('404 not found');
    }

    /**
     * 赠品表单
     * @throws Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function ajaxGetGiftLens()
    {
        if (request()->isAjax()) {
            $incrementId = input('increment_id');
            $siteType = input('site_type');
            try {
                //获取地址、处方等信息
                $res = $this->model->getAddress($incrementId);
                $lens = $this->model->getReissueLens($siteType, $res['prescriptions'], 3);
            } catch (\Exception $e) {
                $this->error($e->getMessage());
            }

            if ($res) {
                $this->success('操作成功！！', '', $lens);
            } else {
                $this->error('未获取到数据！！');
            }
        }
        $this->error('404 not found');
    }

    /**
     * ajax根据prescription_type获取镜片信息
     */
    public function ajaxGetLensType()
    {
        if (request()->isAjax()) {
            $siteType = input('site_type');
            $prescriptionType = input('prescription_type', '');
            $key = $siteType . '_get_lens';
            //$data = Cache::get($key);
            if (!$data) {
                if ($siteType == 13 || $siteType == 14) {
                    $data = $this->model->httpRequest($siteType, 'api/mojing/lens_data', ['prescriptionType' => $prescriptionType], 'POST');
                } else {
                    $data = $this->model->httpRequest($siteType, 'magic/product/lensData');
                }
                Cache::set($key, $data, 3600 * 24);
            }
            if ($siteType == 13 || $siteType == 14) {
                $lensType = $data;
            } else {
                $lensType = $data['lens_list'][$prescriptionType] ?: [];
            }

            $this->success('操作成功！！', '', $lensType);
        } else {
            $this->error('404 not found');
        }
    }

    /**
     * 获取订单order的镜框等信息
     *
     * @Description
     * @return void
     * @since  2020/04/13 17:28:49
     * @author lsw
     */
    public function ajax_get_order($ordertype = null, $order_number = null)
    {
        if ($this->request->isAjax()) {
            if ($ordertype < 1 || $ordertype > 15) { //不在平台之内
                return $this->error('选择平台错误,请重新选择', '', 'error', 0);
            }
            if (!$order_number) {
                return $this->error('订单号不存在，请重新选择', '', 'error', 0);
            }
            if ($ordertype == 1) {
                $result = ZeeloolPrescriptionDetailHelper::get_one_by_increment_id($order_number);
            } elseif ($ordertype == 2) {
                $result = VooguemePrescriptionDetailHelper::get_one_by_increment_id($order_number);
            } elseif ($ordertype == 3) {
                $result = NihaoPrescriptionDetailHelper::get_one_by_increment_id(300035202);
            } elseif ($ordertype == 4) {
                $result = MeeloogPrescriptionDetailHelper::get_one_by_increment_id($order_number);
            } elseif ($ordertype == 5) {
                $result = WeseeopticalPrescriptionDetailHelper::get_one_by_increment_id($order_number);
            } elseif ($ordertype == 9) {
                $result = ZeeloolEsPrescriptionDetailHelper::get_one_by_increment_id($order_number);
            } elseif ($ordertype == 10) {
                $result = ZeeloolDePrescriptionDetailHelper::get_one_by_increment_id($order_number);
            } elseif ($ordertype == 11) {
                $result = ZeeloolJpPrescriptionDetailHelper::get_one_by_increment_id($order_number);
            }
            if (!$result) {
                $this->error('找不到这个订单,请重新尝试', '', 'error', 0);
            }
            $arr = [];
            foreach ($result as $val) {
                for ($i = 0; $i < $val['qty_ordered']; $i++) {
                    $arr[] = $val['sku'];
                }
            }

            return $this->success('', '', $arr, 0);
        } else {
            return $this->error('404 Not Found');
        }
    }

    /**
     * 获取已经添加工单中的订单信息-弃用
     *
     * @Description
     * @return void
     * @since  2020/04/16 10:29:02
     * @author lsw
     */
    public function ajax_edit_order($ordertype = null, $order_number = null, $work_id = null, $change_type = null)
    {
        if ($this->request->isAjax()) {
            if ($ordertype < 1 || $ordertype > 15) { //不在平台之内
                return $this->error('选择平台错误,请重新选择', '', 'error', 0);
            }
            if (!$order_number) {
                return $this->error('订单号不存在，请重新选择', '', 'error', 0);
            }
            if (!$work_id) {
                return $this->error('工单不存在，请重新选择', '', 'error', 0);
            }
            $result = WorkOrderChangeSku::getOrderChangeSku($work_id, $ordertype, $order_number, $change_type);
            if (!$result) {
                if ($ordertype == 1) {
                    $result = ZeeloolPrescriptionDetailHelper::get_one_by_increment_id($order_number);
                } elseif ($ordertype == 2) {
                    $result = VooguemePrescriptionDetailHelper::get_one_by_increment_id($order_number);
                } elseif ($ordertype == 3) {
                    $result = NihaoPrescriptionDetailHelper::get_one_by_increment_id($order_number);
                } elseif ($ordertype == 4) {
                    $result = MeeloogPrescriptionDetailHelper::get_one_by_increment_id($order_number);
                } elseif ($ordertype == 5) {
                    $result = WeseeopticalPrescriptionDetailHelper::get_one_by_increment_id($order_number);
                } elseif ($ordertype == 9) {
                    $result = ZeeloolEsPrescriptionDetailHelper::get_one_by_increment_id($order_number);
                } elseif ($ordertype == 10) {
                    $result = ZeeloolDePrescriptionDetailHelper::get_one_by_increment_id($order_number);
                } elseif ($ordertype == 11) {
                    $result = ZeeloolJpPrescriptionDetailHelper::get_one_by_increment_id($order_number);
                }
            } else {
                $result = collection($result)->toArray();
            }
            if (!$result) {
                $this->error('找不到这个订单,请重新尝试', '', 'error', 0);
            }
            $arr = [];
            foreach ($result as $key => $val) {
                if (!$val['qty_ordered']) {
                    $arr[$key]['original_sku'] = $val['original_sku'];
                    $arr[$key]['original_number'] = $val['original_number'];
                    $arr[$key]['change_sku'] = $val['change_sku'];
                    $arr[$key]['change_number'] = $val['change_number'];
                } else {
                    for ($i = 0; $i < $val['qty_ordered']; $i++) {
                        $arr[] = $val['sku'];
                    }
                }
            }

            return $this->success('', '', $arr, 0);
        } else {
            return $this->error('404 Not Found');
        }
    }

    /**
     * 工单详情
     *
     * @Description
     *
     * @param  [type] $ids
     *
     * @return void
     * @author lzh
     * @since  2020/11/23 15:33:36
     */
    public function detail($ids = null)
    {
        //获取工单配置信息
        $workOrderConfigValue = $this->workOrderConfigValue;

        //校验工单信息
        $row = $this->model->get($ids);
        !$row && $this->error(__('No Results were found'));

        //校验用户权限
        $adminIds = $this->getDataLimitAdminIds();
        is_array($adminIds) && !in_array($row[$this->dataLimitField], $adminIds) && $this->error(__('You have no permission'));

        //校验操作权限
        $operateType = input('operate_type', 0);
        $admin_id = session('admin.id');
        2 == $operateType
        &&
        (
            2 != $row->work_status
            || 1 != $row->is_check
            || !in_array($admin_id, [$row->assign_user_id, $workOrderConfigValue['customer_manager']])
        )
        && $this->error('没有审核权限');

        //工单类型及状态
        $this->view->assign('row', $row);
        $this->assignconfig('work_type', $row->work_type);
        $this->assignconfig('work_status', $row->work_status);

        //子订单措施及数据
        $order_data = $this->model->getOrderItem($row->platform_order, $row->order_item_numbers, $row->work_type, $row);
        if (!empty($order_data['item_order_info'])) {
            $this->assignconfig('item_order_info', $order_data['item_order_info']);
            unset($order_data['item_order_info']);
        }
        $this->view->assign('order_item', $order_data);

        //把问题类型传递到js页面
        $row->problem_type_id && $this->assignconfig('problem_type_id', $row->problem_type_id);

        //回复内容
        $workOrderNote = WorkOrderNote::where('work_id', $ids)->select();
        $this->view->assign('workOrderNote', $workOrderNote);

        //工单措施数据
        $measureList = WorkOrderMeasure::workMeasureList($row->id, 1);
        if (!empty($measureList)) {
            $this->assignconfig('measureList', $measureList);
        }
        $this->assignconfig('operate_type', $operateType);

        //获取审核人名称
        if (2 <= $row->work_status) {
            $row->assign_user = Admin::where(['id' => $row->assign_user_id])->value('nickname');
        } else {
            $row->assign_user = Admin::where(['id' => $row->operation_user_id])->value('nickname');
        }
        $this->view->assign("row", $row);
        $this->assignconfig('work_status', $row->work_status);
        $this->assignconfig('create_user_id', $row->create_user_id);

        //工单问题类型
        $problem_type = [];
        if (1 == $row->work_type) {
            $customer_problem_type = $workOrderConfigValue['customer_problem_type'];
            $customer_problem_classify = $workOrderConfigValue['customer_problem_classify'];
            unset($customer_problem_classify['仓库问题']);

            foreach ($customer_problem_classify as $key => $value) {
                $type = [];
                foreach ($value as $v) {
                    $type[] = ['id' => $v, 'name' => $customer_problem_type[$v]];
                }
                $problem_type[] = ['name' => $key, 'type' => $type];
            }
        } else {
            $problem_type = $workOrderConfigValue['warehouse_problem_type'];
        }
        $this->view->assign('problem_type', $problem_type);

        //补差价链接
        if ($row->replenish_money) {
            $domain_list = [
                1  => 'new_zeelool_url',
                2  => 'new_voogueme_url',
                3  => 'new_nihao_url',
                4  => 'meeloog_url',
                9  => 'new_zeelooles_url',
                10 => 'new_zeeloolde_url',
                11 => 'new_zeelooljp_url',
            ];
            //查询币种
            $order = new \app\admin\model\order\order\NewOrder();
            $order_currency_code = $order->where(['increment_id' => $row->platform_order])->value('order_currency_code');
            $url = config('url.' . $domain_list[$row->work_platform]) . 'price-difference?customer_email=' . $row->email . '&origin_order_number=' . $row->platform_order . '&order_amount=' . $row->replenish_money . '&sign=' . $row->id . '&order_currency_code=' . $order_currency_code;
            if ($row->work_platform == 13 || $row->work_platform == 14) {
                $url = '';
            }
            $this->view->assign('url', $url);
        }

        //审核
        if (2 == $operateType) {
            return $this->view->fetch('saleaftermanage/work_order_list/check');
        }

        //获取承接表数据
        $recepts = WorkOrderRecept::where('fa_work_order_recept.work_id', $row->id)
            ->field('fa_work_order_recept.*,b.measure_choose_id,b.measure_content,b.operation_type,b.item_order_number,b.sku_change_type,b.operation_type,b.operation_time')
            ->join(['fa_work_order_measure' => 'b'], 'fa_work_order_recept.measure_id=b.id')
            ->group('recept_group_id,measure_id')
            ->select();
        $this->assignconfig('recepts', $recepts);
        $this->view->assign('recepts', $recepts);

        //处理
        if (3 == $operateType) {
            //查询赠品sku
            $gift_status = 0;
            $gift_sku = $this->order_change->field('id,change_sku,change_number')->where(['work_id' => $ids, 'change_type' => 4])->select();
            if (!empty($gift_sku)) {
                $gift_status = 1;
            }
            $this->view->assign('gift_status', $gift_status);
            $this->view->assign('gift_sku', $gift_sku);

            return $this->view->fetch('saleaftermanage/work_order_list/process');
        }

        //工单处理备注
        $remarkList = $this->order_remark->where('work_id', $ids)->select();
        $this->view->assign('remarkList', $remarkList);

        return $this->view->fetch();
    }

    /**
     * 审核
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function check()
    {
        $params = input('post.row/a');
        !$params['check_note'] && $this->error('审核意见不能为空');

        //开始审核
        try {
            $this->model->checkWork($params['id'], $params);
        } catch (Exception $e) {
            $this->error($e->getMessage());
        }
        $this->success('已审核');
    }

    /**
     * 获取工单的更改镜片、补发、赠品的信息
     *
     * @Description
     *
     * @param  [type] $work_id
     * @param  [type] $order_number
     * @param  [type] $change_type
     *
     * @return void
     * @author lsw
     * @since  2020/04/16 16:49:21
     */
    public function ajax_change_order($work_id = null, $order_type = null, $order_number = null, $change_type = null, $operate_type = '', $item_order_number = null)
    {
        if ($this->request->isAjax()) {
            (1 > $order_type || 15 < $order_type) && $this->error('选择平台错误,请重新选择', '', 'error', 0);
            !$order_number && $this->error('订单号不存在，请重新选择', '', 'error', 0);
            !$work_id && $this->error('工单不存在，请重新选择', '', 'error', 0);

            //获取工单sku相关变动数据
            $result = WorkOrderChangeSku::getOrderChangeSku($work_id, $order_type, $order_number, $change_type, $item_order_number);
            if ($result) {
                $result = collection($result)->toArray();

                foreach ($result as $key => $val) {
                    $result[$key]['prescription_options'] = unserialize($val['prescription_option']);
                }

                $user_info_option = unserialize($result[0]['userinfo_option']);
                if (!empty($user_info_option)) {
                    $arr['userinfo_option'] = $user_info_option;
                }
                $arr['info'] = $result;
            }

            //编辑镜片信息html代码
            if (in_array($change_type, [2, 4, 5])) {
                $res = $this->model->getAddress($order_number, $item_order_number);
                if (2 == $change_type) { //更改镜片
                    $type = 2;
                    $showPrescriptions = $res['prescriptions'];
                } elseif (4 == $change_type) { //赠品
                    $type = 3;
                    $showPrescriptions = $res['prescriptions'];
                } elseif (5 == $change_type) { //补发
                    $type = 1;
                    $showPrescriptions = $res['showPrescriptions'];
                }
                $lens = $this->model->getEditReissueLens($order_type, $showPrescriptions, $type, !empty($arr) ? $result : [], $operate_type, $item_order_number);
                $lensForm = $this->model->getReissueLens($order_type, $showPrescriptions, $type, $item_order_number);

                if ($res) {
                    $back_data = ['lens' => $lens, 'lensform' => $lensForm];
                    if (5 == $change_type) {
                        $back_data['address'] = $res;
                        $back_data['arr'] = $user_info_option;
                    }
                    $this->success('操作成功！！', '', $back_data);
                } else {
                    $this->error('未获取到数据！！');
                }
            }
        } else {
            $this->error('404 Not Found');
        }
    }

    /**
     * 审核
     */
    public function checkWork($ids = null)
    {
        $params = input('post.row/a');
        try {
            $this->model->checkWork($ids, $params);
        } catch (Exception $e) {
            exception('操作失败，请重试');
        }
    }

    /**
     * 工单取消
     *
     * @Description
     * @author wpl
     * @since 2020/04/17 17:16:55 
     * @return void
     */
    public function setStatus($ids = null)
    {
        $row = $this->model->get($ids);
        if (!$row) {
            $this->error(__('No Results were found'));
        }

        if (request()->isAjax()) {
            $params['work_status'] = 0;
            $params['cancel_time'] = date('Y-m-d H:i:s');
            $params['cancel_person'] = session('admin.nickname');
            $result = $row->allowField(true)->save($params);

            //配货异常表
            $_distribution_abnormal = new DistributionAbnormal();

            //获取工单关联未处理异常数据
            $item_process_ids = $_distribution_abnormal
                ->where(['work_id' => $row->id, 'status' => 1])
                ->column('item_process_id');
            if ($item_process_ids) {
                //异常标记为已处理
                $_distribution_abnormal
                    ->allowField(true)
                    ->save(
                        ['status' => 2, 'do_time' => time(), 'do_person' => session('admin.nickname')],
                        ['work_id' => $row->id, 'status' => 1]
                    );

                //获取异常库位id集
                $_new_order_item_process = new NewOrderItemProcess();
                $abnormal_house_ids = $_new_order_item_process
                    ->field('abnormal_house_id')
                    ->where(['id' => ['in', $item_process_ids]])
                    ->select();
                if ($abnormal_house_ids) {
                    //异常库位号占用数量减1
                    $_stock_house = new StockHouse();
                    foreach ($abnormal_house_ids as $v) {
                        $_stock_house
                            ->where(['id' => $v['abnormal_house_id']])
                            ->setDec('occupy', 1);
                    }
                }

                //解绑子订单的异常库位ID
                $_new_order_item_process
                    ->allowField(true)
                    ->save(['abnormal_house_id' => 0], ['id' => ['in', $item_process_ids]]);

                //配货操作日志
                DistributionLog::record((object)session('admin'), $item_process_ids, 10, "工单取消，异常标记为已处理");
            }

            if (false !== $result) {
                $this->success('操作成功！！');
            } else {
                $this->error('操作失败！！');
            }
        }
        $this->error('404 not found');
    }

    /* 处理任务
     *
     * @Description
     * @author wpl
     * @since 2020/04/16 16:29:30 
     * @param [type] $ids
     * @return void
     */
    public function process()
    {
        if ($this->request->isPost()) {
            $params = $this->request->post("row/a");
            if ($params) {
                $row = $this->model->get($params['id']);
                if (!$row) {
                    $this->error(__('No Results were found'));
                }
                if (6 == $row['work_status']) {
                    $this->error(__('工单已经处理完成，请勿重复处理'));
                }
                $recept_id = $params['recept_id'];
                //获取所有可以处理的人
                $receptInfoArr = (new WorkOrderRecept())->getAllRecept($recept_id);
                //本次处理的人
                $receptInfo = (new WorkOrderRecept())->getOneRecept($recept_id, session('admin.id'));
                $result = false;
                if (empty($receptInfo)) {
                    $this->error(__('您无权限处理此工单'));
                }
                if (is_array($receptInfoArr)) {
                    if (!in_array(session('admin.id'), $receptInfoArr)) {
                        $this->error(__('您不能处理此工单'));
                    }

                    //当要处理成功时需要判断库存是否存在
                    if (1 == $params['success']) {
                        //判断该订单是否是vip订单
                        if ($row['order_type'] == 100) {
                            //vip订单,请求网站接口
                            $this->model->vipOrderRefund($row['work_platform'], $row['platform_order']);
                        } else {
                            //其他订单
                            $checkSku = $this->checkMeasure($receptInfo['measure_id']);
                            if ($checkSku) {
                                $this->error(__("以下sku库存不足{$checkSku},无法处理成功"));
                            }
                        }
                    }
                    //赠品绑定条码
                    $_work_order_measure = new WorkOrderMeasure();
                    $measure_choose_id = $_work_order_measure->where('id', $receptInfo['measure_id'])->value('measure_choose_id');
                    if (6 == $measure_choose_id && 1 == $params['success']) {
                        $barcode = $params['barcode'];
                        $product_bar_code_item = new ProductBarCodeItem();
                        $work_order_change_sku = new WorkOrderChangeSku();
                        $item_platform_sku = new ItemPlatformSku();
                        $gift_sku = $work_order_change_sku->field('id,change_sku,change_number')->where(['work_id' => $receptInfo['work_id'], 'change_type' => 4])->select();
                        if (!empty($gift_sku)) {
                            $gift_sku = collection($gift_sku)->toArray();
                            foreach ($gift_sku as $key => $value) {
                                for ($i = 1; $i <= $value['change_number']; $i++) {
                                    $change_sku = $value['change_sku'];
                                    if (empty($barcode[$value['change_sku'] . '_' . $i])) {
                                        $this->error("序号为" . $i . "的sku(" . $value['change_sku'] . ")，条形码不能为空");
                                    }
                                    //仓库sku
                                    $platform_info = $item_platform_sku
                                        ->field('sku,stock')
                                        ->where(['platform_sku' => $value['change_sku'], 'platform_type' => $row['work_platform']])
                                        ->find();
                                    if ($platform_info['sku']) {
                                        $platform_info_sku = $platform_info['sku'];
                                    }
                                    $bar_code_info = $product_bar_code_item->where(['code' => $barcode[$change_sku . '_' . $i]])->find();
                                    if (empty($bar_code_info)) {
                                        $this->error("序号为" . $i . "的，赠品条形码不存在");
                                    }
                                    if ($row['work_platform'] != 13 && $row['work_platform'] != 14) {
                                        if ($bar_code_info['library_status'] == 2) {
                                            $this->error("序号为" . $i . "的sku(" . $change_sku . ")，在库状态为否");
                                        }
                                    }
                                    if ($bar_code_info['sku'] != $platform_info_sku) {
                                        $this->error("序号为" . $i . "的sku(" . $change_sku . ")，条形码所绑定的sku与赠品sku不一致");
                                    }
                                }
                            }
                        }
                    }
                    $result = $this->model->handleRecept($receptInfo['id'], $receptInfo['work_id'], $receptInfo['measure_id'], $receptInfo['recept_group_id'], $params['success'], $params['note'], $receptInfo['is_auto_complete'], $params['barcode']);
                }
                if ($result !== false) {
                    //查询选择的所有措施
                    $measure_choose_ids = $_work_order_measure->where('work_id', $params['id'])
                        ->where('operation_type', '<>', 2)
                        ->column('measure_choose_id');
                    //措施表
                    $_work_order_measure = new WorkOrderMeasure();
                    //查询当前措施
                    $measure_choose_id = $_work_order_measure->where('id', $receptInfo['measure_id'])->value('measure_choose_id');
                    if (3 == $measure_choose_id) {
                        //主单取消收入核算冲减
                        $FinanceCost = new FinanceCost();
                        $FinanceCost->cancel_order_subtract($receptInfo['work_id']);
                        //主单取消解绑所有子单条码
                        $ProductBarCodeItem = new ProductBarCodeItem();
                        $fa_order = new NewOrder();
                        $_new_order_item_process = new NewOrderItemProcess();
                        $order_id = $fa_order->where(['increment_id' => $row['platform_order']])->value('id');
                        $item_order_arr = $_new_order_item_process->where(['order_id' => $order_id])->column('item_order_number');
                        if (!empty($item_order_arr)) {
                            foreach ($item_order_arr as $key => $value) {
                                $ProductBarCodeItem->where(['item_order_number' => $value])->update(['item_order_number' => '', 'library_status' => 1, 'out_stock_time' => null, 'out_stock_id' => 0]);
                            }
                        }
                    }
                    if (15 == $measure_choose_id) {
                        //vip退款收入核算冲减
                        $FinanceCost = new FinanceCost();
                        $FinanceCost->vip_order_subtract($receptInfo['work_id']);
                    }
                    if (19 == $measure_choose_id || 18 == $measure_choose_id) {
                        switch ($measure_choose_id) {
                            case 18://子单取消
                                $change_type = 3;
                                break;

                            case 19://更改镜框
                                $change_type = 1;
                                break;
                        }
                        //更改镜框解绑子单所绑定的条形码
                        $ProductBarCodeItem = new ProductBarCodeItem();
                        //查询子单号
                        $item_order_number = $this->order_change
                            ->where(['work_id' => $receptInfo['work_id'], 'change_type' => $change_type, 'measure_id' => $receptInfo['measure_id']])
                            ->value('item_order_number');
                        if (!empty($item_order_number)) {
                            $ProductBarCodeItem->where(['item_order_number' => $item_order_number])->update(['item_order_number' => '', 'library_status' => 1, 'out_stock_time' => null, 'out_stock_id' => 0]);
                        }
                    }
                    if (8 == $measure_choose_id) {
                        //补价收入核算增加
                        $FinanceCost = new FinanceCost();
                        $FinanceCost->return_order_subtract($receptInfo['work_id'], 3);
                    }
                    //退件或退款
                    if (11 == $measure_choose_id) {
                        //退件退款收入核算冲减
                        $FinanceCost = new FinanceCost();
                        $FinanceCost->return_order_subtract($receptInfo['work_id'], 4);
                    }

                    //其他退款措施
                    if (2 == $measure_choose_id && !array_intersect([3,15,19,18,8,11], $measure_choose_ids)) {
                        $FinanceCost = new FinanceCost();
                        $FinanceCost->return_order_subtract($receptInfo['work_id'], 6);
                    }


                    $this->success();
                } else {
                    $this->error(__('No rows were updated'));
                }
            }
            $this->error(__('Parameter %s can not be empty', ''));
        }
    }

    /**
     * 优惠券列表
     *
     * @Description
     * @author wpl
     * @since 2020/04/21 14:06:32 
     * @return void
     */
    public function couponList()
    {
        //设置过滤方法
        $this->request->filter(['strip_tags']);
        if ($this->request->isAjax()) {
            //如果发送的来源是Selectpage，则转发到Selectpage
            if ($this->request->request('keyField')) {
                return $this->selectpage();
            }
            [$where, $sort, $order, $offset, $limit] = $this->buildparams();
            $map['coupon_id'] = ['>', 0];
            $total = $this->model
                ->where($where)
                ->where($map)
                ->where('work_status', 'in', '5,6')
                ->order($sort, $order)
                ->count();

            $list = $this->model
                ->where($where)
                ->where($map)
                ->where('work_status', 'in', '5,6')
                ->order($sort, $order)
                ->limit($offset, $limit)
                ->select();
            $list = collection($list)->toArray();

            $result = ["total" => $total, "rows" => $list];

            return json($result);
        }

        return $this->view->fetch();
    }

    /**
     * 积分列表
     *
     * @Description
     * @author wpl
     * @since 2020/04/21 14:06:32 
     * @return void
     */
    public function integralList()
    {
        //设置过滤方法
        $this->request->filter(['strip_tags']);
        if ($this->request->isAjax()) {
            //如果发送的来源是Selectpage，则转发到Selectpage
            if ($this->request->request('keyField')) {
                return $this->selectpage();
            }
            $map = [];
            $maps = [];
            $arr1 = '';
            $arr2 = '';
            $filter = json_decode($this->request->get('filter'), true);
            if ($filter['integral']) {
                $integralArr = explode(',', $filter['integral']);
                if ($integralArr[0]) {
                    $arr1 = 'integral >' . intval($integralArr[0]);
                }
                if ($integralArr[1]) {
                    $map['integral'] = ['>', 0];
                    $arr2 = 'integral <' . intval($integralArr[1]);
                }
                unset($filter['integral']);
                $this->request->get(['filter' => json_encode($filter)]);
            } else {
                $map['integral'] = ['>', 0];
            }
            [$where, $sort, $order, $offset, $limit] = $this->buildparams();
            $total = $this->model
                ->where($where)
                ->where($map)
                ->where($maps)
                ->where($arr1)
                ->where($arr2)
                ->where('work_status', 'in', '5,6')
                ->order($sort, $order)
                ->count();
            $list = $this->model
                ->where($where)
                ->where($map)
                ->where($maps)
                ->where($arr1)
                ->where($arr2)
                ->where('work_status', 'in', '5,6')
                ->order($sort, $order)
                ->limit($offset, $limit)
                ->select();
            $list = collection($list)->toArray();

            $result = ["total" => $total, "rows" => $list];

            return json($result);
        }

        return $this->view->fetch();
    }

    /**
     * 批量打印标签-弃用
     *
     * @Description
     * @author wpl
     * @since 2020/04/22 17:23:47 
     * @return void
     */
    public function batch_print_labelOld()
    {
        ob_start();
        $ids = input('ids');
        $where['a.id'] = ['in', $ids];
        $where['b.change_type'] = 2;
        $list = $this->model->alias('a')->where($where)
            ->field('b.*')
            ->join(['fa_work_order_change_sku' => 'b'], 'a.id=b.work_id')
            ->select();
        $list = collection($list)->toArray();
        if (!$list) {
            $this->error('未找到更换镜片的数据');
        }
        $list = $this->qty_order_check($list);


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
        foreach ($list as $processing_value) {
            if ($temp_increment_id != $processing_value['increment_id']) {
                $temp_increment_id = $processing_value['increment_id'];

                $date = substr($processing_value['create_time'], 0, strpos($processing_value['create_time'], " "));
                $fileName = ROOT_PATH . "public" . DS . "uploads" . DS . "printOrder" . DS . "workorder" . DS . "$date" . DS . "$temp_increment_id.png";
                // dump($fileName);
                $dir = ROOT_PATH . "public" . DS . "uploads" . DS . "printOrder" . DS . "workorder" . DS . "$date";
                if (!file_exists($dir)) {
                    mkdir($dir, 0777, true);
                    // echo '创建文件夹$dir成功';
                } else {
                    // echo '需创建的文件夹$dir已经存在';
                }
                $img_url = "/uploads/printOrder/workorder/$date/$temp_increment_id.png";
                //生成条形码
                $this->generate_barcode($temp_increment_id, $fileName);
                // echo '<br>需要打印'.$temp_increment_id;
                $file_content .= "<div  class = 'single_box'>
                <table width='400mm' height='102px' border='0' cellspacing='0' cellpadding='0' class='addpro' style='margin:0px auto;margin-top:0px;padding:0px;'>
                <tr><td rowspan='5' colspan='2' style='padding:2px;width:20%'>" . str_replace(" ", "<br>", $processing_value['create_time']) . "</td>
                <td rowspan='5' colspan='3' style='padding:10px;'><img src='" . $img_url . "' height='80%'><br></td></tr>                
                </table></div>";
            }


            //处理ADD  当ReadingGlasses时 是 双ADD值
            if ($processing_value['recipe_type'] == 'ReadingGlasses' && strlen($processing_value['os_add']) > 0 && strlen($processing_value['od_add']) > 0) {
                // echo '双ADD值';
                $os_add = "<td>" . $processing_value['od_add'] . "</td> ";
                $od_add = "<td>" . $processing_value['os_add'] . "</td> ";
            } else {
                // echo '单ADD值';
                $od_add = "<td rowspan='2'>" . $processing_value['od_add'] . "</td>";
                $os_add = "";
            }

            //处理PD值
            if (strlen($processing_value['pd_r']) > 0 && strlen($processing_value['pd_l']) > 0) {
                // echo '双PD值';
                $od_pd = "<td>" . $processing_value['pd_r'] . "</td> ";
                $os_pd = "<td>" . $processing_value['pd_l'] . "</td> ";
            } else {
                // echo '单PD值';
                $od_pd = "<td rowspan='2'>" . $processing_value['pd_r'] . "</td>";
                $os_pd = "";
            }

            //处理斜视参数
            if ($processing_value['od_pv'] || $processing_value['os_pv']) {
                $prismcheck_title = "<td>Prism</td><td colspan=''>Direc</td><td>Prism</td><td colspan=''>Direc</td>";
                $prismcheck_od_value = "<td>" . $processing_value['od_pv'] . "</td><td colspan=''>" . $processing_value['od_bd'] . "</td>" . "<td>" . $processing_value['od_pv_r'] . "</td><td>" . $processing_value['od_bd_r'] . "</td>";
                $prismcheck_os_value = "<td>" . $processing_value['os_pv'] . "</td><td colspan=''>" . $processing_value['os_bd'] . "</td>" . "<td>" . $processing_value['os_pv_r'] . "</td><td>" . $processing_value['os_bd_r'] . "</td>";
                $coatiing_name = '';
            } else {
                $prismcheck_title = '';
                $prismcheck_od_value = '';
                $prismcheck_os_value = '';
                $coatiing_name = "<td colspan='4' rowspan='3' style='background-color:#fff;word-break: break-word;line-height: 12px;'>" . $processing_value['coating_type'] . "</td>";
            }

            //处方字符串截取
            $final_print['recipe_type'] = substr($processing_value['recipe_type'], 0, 15);

            //判断货号是否存在
            if ($item_res[$processing_value['original_sku']] && $cargo_number[$item_res[$processing_value['original_sku']]]) {
                $cargo_number_str = "<b>" . $cargo_number[$item_res[$processing_value['original_sku']]] . "</b><br>";
            } else {
                $cargo_number_str = "";
            }

            $file_content .= "<div  class = 'single_box'>
            <table width='400mm' height='102px' border='0' cellspacing='0' cellpadding='0' class='addpro' style='margin:0px auto;margin-top:0px;' >
            <tbody cellpadding='0'>
            <tr>
            <td colspan='10' style=' text-align:center;padding:0px 0px 0px 0px;'>                              
            <span>" . $processing_value['recipe_type'] . "</span>
            &nbsp;&nbsp;Order:" . $processing_value['increment_id'] . "
            <span style=' margin-left:5px;'>SKU:" . $processing_value['original_sku'] . "</span>
            <span style=' margin-left:5px;'>Num:<strong>" . $processing_value['original_number'] . "</strong></span>
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
            <td>" . $processing_value['od_sph'] . "</td> 
            <td>" . $processing_value['od_cyl'] . "</td>
            <td>" . $processing_value['od_axis'] . "</td>    
            " . $prismcheck_od_value . $od_add . $od_pd .
                "</tr>
            <tr>
            <td>Left</td> 
            <td>" . $processing_value['os_sph'] . "</td>    
            <td>" . $processing_value['os_cyl'] . "</td>  
            <td>" . $processing_value['os_axis'] . "</td> 
            " . $prismcheck_os_value . $os_add . $os_pd .
                " </tr>
            <tr>
            <td colspan='2'>" . $cargo_number_str . SKUHelper::sku_filter($processing_value['original_sku']) . "</td>
            <td colspan='8' style=' text-align:center'>Lens：" . $processing_value['lens_type'] . "</td>
            </tr>  
            </tbody></table></div>";
        }
        echo $file_header . $file_content;
    }

    /**
     * 批量打印标签
     *
     * @Description
     * @author lzh
     * @since 2020/11/1 10:36:22 
     * @return void
     */
    public function batch_print_label()
    {
        //禁用默认模板
        $this->view->engine->layout(false);
        ob_start();

        $ids = input('ids');
        !$ids && $this->error('请选择要打印的数据');

        //获取更改镜框最新信息
        $change_sku = $this->order_change
            ->alias('a')
            ->join(['fa_work_order_measure' => 'b'], 'a.measure_id=b.id')
            ->where([
                'a.change_type'    => 1,
                'a.work_id'        => ['in', $ids],
                'b.operation_type' => 1,
            ])
            ->order('a.id', 'desc')
            ->group('a.item_order_number')
            ->column('a.change_sku', 'a.item_order_number');

        //获取更改镜片最新处方信息
        $change_lens = $this->order_change
            ->alias('a')
            ->join(['fa_work_order_measure' => 'b'], 'a.measure_id=b.id')
            ->where([
                'a.change_type'    => 2,
                'a.work_id'        => ['in', $ids],
                'b.operation_type' => 1,
            ])
            ->order('a.id', 'desc')
            ->group('a.item_order_number')
            ->column('a.od_sph,a.od_cyl,a.od_axis,a.od_add,a.pd_r,a.od_pv,a.od_bd,a.od_pv_r,a.od_bd_r,a.os_sph,a.os_cyl,a.os_axis,a.os_add,a.pd_l,a.os_pv,a.os_bd,a.os_pv_r,a.os_bd_r,a.lens_number,a.recipe_type as prescription_type', 'a.item_order_number');
        if ($change_lens) {
            foreach ($change_lens as $key => $val) {
                if ($val['pd_l'] && $val['pd_r']) {
                    $change_lens[$key]['pd'] = '';
                    $change_lens[$key]['pdcheck'] = 'on';
                } else {
                    $change_lens[$key]['pd'] = $val['pd_r'] ?: $val['pd_l'];
                    $change_lens[$key]['pdcheck'] = '';
                }
            }
        }

        //获取子单号集合
        $item_order_numbers = array_unique(array_merge(array_keys($change_sku), array_keys($change_lens)));
        !$item_order_numbers && $this->error('未找到更换镜片或更改镜框的数据');

        //获取子订单列表
        $_new_order_item_process = new NewOrderItemProcess();
        $list = $_new_order_item_process
            ->alias('a')
            ->field('a.item_order_number,a.order_id,a.created_at,b.os_add,b.od_add,b.pdcheck,b.prismcheck,b.pd_r,b.pd_l,b.pd,b.od_pv,b.os_pv,b.od_bd,b.os_bd,b.od_bd_r,b.os_bd_r,b.od_pv_r,b.os_pv_r,b.index_name,b.coating_name,b.prescription_type,b.sku,b.od_sph,b.od_cyl,b.od_axis,b.os_sph,b.os_cyl,b.os_axis,b.lens_number')
            ->join(['fa_order_item_option' => 'b'], 'a.option_id=b.id')
            ->where(['a.item_order_number' => ['in', $item_order_numbers]])
            ->select();
        $list = collection($list)->toArray();
        $order_ids = array_column($list, 'order_id');
        $sku_arr = array_column($list, 'sku');

        //查询sku映射表
        $item_res = $this->item_platform_sku->cache(3600)->where(['platform_sku' => ['in', array_unique($sku_arr)]])->column('sku', 'platform_sku');

        //获取订单数据
        $_new_order = new NewOrder();
        $order_list = $_new_order->where(['id' => ['in', array_unique($order_ids)]])->column('total_qty_ordered,increment_id', 'id');

        //查询产品货位号
        $_stock_house = new StockHouse();
        $cargo_number = $_stock_house
            ->alias('a')
            ->where(['status' => 1, 'b.is_del' => 1, 'a.type' => 1])
            ->join(['fa_store_sku' => 'b'], 'a.id=b.store_id')
            ->column('coding', 'sku');

        //获取镜片编码及名称
        $_lens_data = new LensData();
        $lens_list = $_lens_data->column('lens_name', 'lens_number');

        $data = [];
        foreach ($list as $k => $v) {
            //更改镜框最新sku
            if ($change_sku[$v['item_order_number']]) {
                $v['sku'] = $change_sku[$v['item_order_number']];
            }

            //更改镜片最新数据
            if ($change_lens[$v['item_order_number']]) {
                $v = array_merge($v, $change_lens[$v['item_order_number']]);
            }

            $item_order_number = $v['item_order_number'];
            $fileName = ROOT_PATH . "public" . DS . "uploads" . DS . "printOrder" . DS . "distribution" . DS . "new" . DS . "$item_order_number.png";
            $dir = ROOT_PATH . "public" . DS . "uploads" . DS . "printOrder" . DS . "distribution" . DS . "new";
            if (!file_exists($dir)) {
                mkdir($dir, 0777, true);
            }
            $img_url = "/uploads/printOrder/distribution/new/$item_order_number.png";

            //生成条形码
            $this->generate_barcode($item_order_number, $fileName);
            $v['created_at'] = date('Y-m-d H:i:s', $v['created_at']);
            $v['img_url'] = $img_url;

            //序号
            $serial = explode('-', $item_order_number);
            $v['serial'] = $serial[1];
            $v['total_qty_ordered'] = $order_list[$v['order_id']]['total_qty_ordered'];
            $v['increment_id'] = $order_list[$v['order_id']]['increment_id'];

            //库位号
            $v['coding'] = $cargo_number[$item_res[$v['sku']]];

            //判断双ADD逻辑
            if ($v['os_add'] && $v['od_add'] && (float)$v['os_add'] * 1 != 0 && (float)$v['od_add'] * 1 != 0) {
                $v['total_add'] = '';
            } else {
                if ($v['os_add'] && (float)$v['os_add'] * 1 != 0) {
                    $v['total_add'] = $v['os_add'];
                } else {
                    $v['total_add'] = $v['od_add'];
                }
            }

            //获取镜片名称
            $v['lens_name'] = $lens_list[$v['lens_number']] ?: '';

            $data[] = $v;
        }
        $this->assign('list', $data);
        $html = $this->view->fetch('print_label');
        echo $html;
    }

    /**
     * 生成条形码
     */
    protected function generate_barcode($text, $fileName)
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
        $label->setText('');
        $label->setFont($font);
        $drawException = null;
        try {
            // $code = new \BCGcode39();
            $code = new \BCGcode128();
            $code->setScale(4);
            $code->setThickness(18); // 条形码的厚度
            $code->setForegroundColor($color_black); // 条形码颜色
            $code->setBackgroundColor($color_white); // 空白间隙颜色
            $code->setFont(0); //设置字体
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

    /**
     * 根据SKU数量平铺标签
     *
     * @Description
     * @author wpl
     * @since 2020/04/22 17:24:01 
     *
     * @param     [type] $origin_order_item
     *
     * @return void
     */
    protected function qty_order_check($origin_order_item = [])
    {
        foreach ($origin_order_item as $origin_order_key => $origin_order_value) {
            if ($origin_order_value['original_number'] > 1 && strpos($origin_order_value['original_sku'], 'Price') === false) {
                unset($origin_order_item[$origin_order_key]);
                for ($i = 0; $i < $origin_order_value['original_number']; $i++) {
                    $tmp_order_value = $origin_order_value;
                    $tmp_order_value['num'] = 1;
                    array_push($origin_order_item, $tmp_order_value);
                }
                unset($tmp_order_value);
            }
        }

        $origin_order_item = $this->arraySequence($origin_order_item, 'original_number');

        return array_values($origin_order_item);
    }

    /**
     * 按个数排序
     *
     * @Description
     * @author wpl
     * @since 2020/04/22 17:24:23 
     *
     * @param     [type] $array
     * @param     [type] $field
     * @param string $sort
     *
     * @return void
     */
    protected function arraySequence($array, $field, $sort = 'SORT_ASC')
    {
        $arrSort = [];
        foreach ($array as $uniqid => $row) {
            foreach ($row as $key => $value) {
                $arrSort[$key][$uniqid] = $value;
            }
        }
        array_multisort($arrSort[$field], constant($sort), $array);

        return $array;
    }

    /**
     * 判断措施当中的扣减库存是否存在
     *
     * @Description
     *
     * @param array $receptInfo
     *
     * @return void
     * @author lsw
     * @since  2020/04/24 09:30:03
     */
    protected function checkMeasure($measure_id)
    {
        //1.求出措施的类型
        $measuerInfo = WorkOrderMeasure::where(['id' => $measure_id])->value('sku_change_type');
        //没有扣减库存的措施
        if ($measuerInfo < 1) {
            return false;
        }
        //求出措施类型
        if (!in_array($measuerInfo, [1, 4, 5])) {
            return false;
        }
        $whereMeasure['measure_id'] = $measure_id;
        $whereMeasure['change_type'] = $measuerInfo;
        $result = WorkOrderChangeSku::where($whereMeasure)->field('platform_type,original_sku,original_number,change_sku,change_number')->select();
        $result = collection($result)->toArray();
        //更改镜片
        $arr = [];
        foreach ($result as $k => $v) {
            $arr[$k]['original_sku'] = $v['change_sku'];
            $arr[$k]['original_number'] = $v['change_number'];
            $arr[$k]['platform_type'] = $v['platform_type'];
        }
        $itemPlatFormSku = new \app\admin\model\itemmanage\ItemPlatformSku();


        //根据平台sku转sku
        $notEnough = [];
        foreach (array_filter($arr) as $v) {
            //转换sku
            $sku = trim($v['original_sku']);
            if ($v['platform_type'] == 13 || $v['platform_type'] == 14) {
                $itemPlatFormSkuWhere = ['platform_sku' => $sku, 'platform_type' => $v['platform_type']];
            } else {
                $itemPlatFormSkuWhere = ['outer_sku_status' => 1, 'platform_sku' => $sku, 'platform_type' => $v['platform_type']];
            }
            //判断是否开启预售 并且预售时间是否满足 并且预售数量是否足够
            $res = $itemPlatFormSku->where($itemPlatFormSkuWhere)->find();
            //判断是否开启预售
            if ($res['stock'] >= 0 && $res['presell_status'] == 1 && strtotime($res['presell_create_time']) <= time() && strtotime($res['presell_end_time']) >= time()) {
                $stock = $res['stock'] + $res['presell_residue_num'];
            } elseif ($res['stock'] < 0 && $res['presell_status'] == 1 && strtotime($res['presell_create_time']) <= time() && strtotime($res['presell_end_time']) >= time()) {
                $stock = $res['presell_residue_num'];
            } else {
                $stock = $res['stock'];
            }

            //判断可用库存
            if ($stock < $v['original_number']) {
                //判断没库存情况下 是否开启预售 并且预售时间是否满足 并且预售数量是否足够
                $notEnough[] = $sku;
            }
        }
        if ($notEnough) {
            $str = implode(',', $notEnough);
        }

        return $notEnough ? $str : false;
    }

    /**
     * 问题类型筛选的下拉列表
     * @return array
     */
    public function getProblemTypeContent()
    {
        //return array_merge(config('workorder.warehouse_problem_type'), config('workorder.customer_problem_type'));
        return array_merge($this->workOrderConfigValue['warehouse_problem_type'], $this->workOrderConfigValue['customer_problem_type']);
    }

    /**
     * 措施筛选下拉列表
     *
     * @Description
     * @return void
     * @since  2020/05/26 14:01:15
     * @author lsw
     */
    public function getMeasureContent()
    {
        //return config('workorder.step');
        return $this->workOrderConfigValue['step'];
    }

    /**
     * 工单备注
     */

    public function workordernote($ids = null)
    {
        $workOrderConfigValue = $this->workOrderConfigValue;
        if ($this->request->isPost()) {
            $params = $this->request->post("row/a");
            if ($params) {
                $data['note_time'] = date('Y-m-d H:i', time());
                $data['note_user_id'] = session('admin.id');
                $data['note_user_name'] = session('admin.nickname');
                $data['work_id'] = $params['work_id'];
                $data['user_group_id'] = 0;
                $data['content'] = $params['content'];
                Db::startTrans();
                try {
                    $res_status = WorkOrderNote::create($data);
                    //查询用户的角色组id
                    $authGroupIds = AuthGroupAccess::where('uid', session('admin.id'))->column('group_id');
                    $work = $this->model->find($params['work_id']);
                    $work_order_note_status = $work->work_order_note_status;

                    // if (array_intersect($authGroupIds, config('workorder.customer_department_rule'))) {
                    //     //客服组
                    //     $work_order_note_status = 1;
                    // }
                    // if (array_intersect($authGroupIds, config('workorder.warehouse_department_rule'))) {
                    //     //仓库部
                    //     $work_order_note_status = 2;
                    // }
                    // if (array_intersect($authGroupIds, config('workorder.finance_department_rule'))) {
                    //     //财务组
                    //     $work_order_note_status = 3;
                    // }
                    if (array_intersect($authGroupIds, $workOrderConfigValue['customer_department_rule'])) {
                        //客服组
                        $work_order_note_status = 1;
                    }
                    if (array_intersect($authGroupIds, $workOrderConfigValue['warehouse_department_rule'])) {
                        //仓库部
                        $work_order_note_status = 2;
                    }
                    if (array_intersect($authGroupIds, $workOrderConfigValue['finance_department_rule'])) {
                        //财务组
                        $work_order_note_status = 3;
                    }
                    $work->work_order_note_status = $work_order_note_status;
                    $work->save();
                    Db::commit();
                } catch (\Exception $e) {
                    echo 2;
                    echo $e->getMessage();
                    Db::rollback();
                }
                if ($res_status) {
                    $this->success('成功');
                } else {
                    $this->error('失败');
                }
            }
            $this->error(__('Parameter %s can not be empty', ''));
        }
        $row = WorkOrderNote::where(['work_id' => $ids])->order('id desc')->select();
        $this->view->assign("row", $row);
        $this->view->assign('work_id', $ids);

        return $this->view->fetch('work_order_note');
    }


    /**
     * 修改排序之后
     *
     * @Author   lsw 1461069578@qq.com
     * @DateTime 2020-09-26 10:51:10
     * @return void
     */
    public function batch_export_xls()
    {
        set_time_limit(0);
        ini_set('memory_limit', '1024M');
        $ids = input('ids');
        $addWhere = '1=1';
        if ($ids) {
            $addWhere .= " AND id IN ({$ids})";
        }
        $filter = json_decode($this->request->get('filter'), true);
        $map = [];

        if ($filter['recept_person']) {

            $recept_person = $filter['recept_person'];
            $map['id'] = [
                'IN',
                function ($query) use ($recept_person) {
                    $query->table('fa_work_order_recept')->where('recept_person_id', 'in', $recept_person)->field('work_id');
                },
            ];
            unset($filter['recept_person']);
        }

        if ($filter['stock_id']) {
            $stockId = $filter['stock_id'];
            $map['stock_id'] = $stockId;
            unset($filter['stock_id']);
        }

        //筛选措施
        if ($filter['measure_choose_id']) {
            $measure_choose_id = $filter['measure_choose_id'];
            $map['id'] = [
                'IN',
                function ($query) use ($measure_choose_id) {
                    $query->table('fa_work_order_measure')->where('measure_choose_id', 'in', $measure_choose_id)->field('work_id');
                },
            ];
            unset($filter['measure_choose_id']);
        }

        $worklist = new \app\admin\model\saleaftermanage\WorkOrderList();
        $this->request->get(['filter' => json_encode($filter)]);
        [$where] = $this->buildparams();

        $list = $worklist
            ->where($where)
            ->where($map)
            ->where($addWhere)
            ->select();
        $list = collection($list)->toArray();

        //根据平台sku求出商品sku
        $itemPlatFormSku = new \app\admin\model\itemmanage\ItemPlatformSku();
        $_new_order_item_process = new NewOrderItemProcess();
        foreach ($list as $key => $item) {

            $orders = $_new_order_item_process->field('item_order_number,site,sku')
                ->where(['item_order_number' => ['in', $item['order_item_numbers']]])
                ->select();
            $str = '';
            foreach ($orders as $k => $v) {
                $sku = $itemPlatFormSku->getTrueSku($v['sku'], $v['site']);
                $str .= $sku . ",";
            }
            $list[$key]['order_skus'] = trim($str, ',');
        }

        //查询用户id对应姓名
        $admin = new \app\admin\model\Admin();
        $users = $admin->where('status', 'normal')->column('nickname', 'id');
        $arr = array_column($list, 'id');
        //求出所有的措施
        $info = $this->step->fetchMeasureRecord($arr);
        if ($info) {
            $info = collection($info)->toArray();
        } else {
            $info = [];
        }

        //求出所有的回复
        $noteInfo = $this->work_order_note->fetchNoteRecord($arr);
        if ($noteInfo) {
            $noteInfo = collection($noteInfo)->toArray();
        } else {
            $noteInfo = [];
        }

        //求出配置里面信息
        $workOrderConfigValue = $this->workOrderConfigValue;
        //求出配置里面的大分类信息
        $customer_problem_classify = $workOrderConfigValue['customer_problem_classify'];
        //从数据库查询需要的数据
        $spreadsheet = new Spreadsheet();
        //常规方式：利用setCellValue()填充数据
        $spreadsheet->setActiveSheetIndex(0)->setCellValue("A1", "工单平台")
            ->setCellValue("B1", "工单类型")
            ->setCellValue("C1", "平台订单号");   //利用setCellValues()填充数据
        $spreadsheet->setActiveSheetIndex(0)->setCellValue("D1", "客户邮箱")
            ->setCellValue("E1", "订单金额");
        $spreadsheet->setActiveSheetIndex(0)->setCellValue("F1", "订单支付的货币类型")
            ->setCellValue("G1", "订单的支付方式");
        $spreadsheet->setActiveSheetIndex(0)->setCellValue("H1", "订单中的sku")
            ->setCellValue("I1", "对应商品sku")
            ->setCellValue("J1", "工单状态");
        $spreadsheet->setActiveSheetIndex(0)->setCellValue("K1", "问题大分类")
            ->setCellValue("L1", "问题类型")
            ->setCellValue("M1", "工单问题描述")
            ->setCellValue("N1", "工单图片")
            ->setCellValue("O1", "工单创建人")
            ->setCellValue("P1", "工单是否需要审核")
            ->setCellValue("Q1", "指派工单审核人")
            ->setCellValue("R1", "实际审核人")
            ->setCellValue("S1", "审核人备注")
            ->setCellValue("T1", "新建状态时间")
            ->setCellValue("U1", "开始走流程时间")
            ->setCellValue("V1", "工单审核时间")
            ->setCellValue("W1", "经手人处理时间")
            ->setCellValue("X1", "工单完成时间")
            ->setCellValue("Y1", "补差价的金额")
            ->setCellValue("Z1", "补差价的订单号")
            ->setCellValue("AA1", "优惠券类型")
            ->setCellValue("AB1", "优惠券描述")
            ->setCellValue("AC1", "优惠券")
            ->setCellValue("AD1", "积分")
            ->setCellValue("AE1", "退回物流单号")
            ->setCellValue("AF1", "退款金额")
            ->setCellValue("AG1", "退款百分比")
            ->setCellValue("AH1", "措施详情")
            ->setCellValue("AI1", "工单回复备注")
            ->setCellValue("AJ1", "订单支付时间")
            ->setCellValue("AK1", "补发订单号")
            ->setCellValue("AL1", "商品子单号SKU");;
        $spreadsheet->setActiveSheetIndex(0)->setTitle('工单数据');
        foreach ($list as $key => $value) {
            if ($value['after_user_id']) {
                $value['after_user_id'] = $users[$value['after_user_id']];
            }
            if ($value['assign_user_id']) {
                $value['assign_user_id'] = $users[$value['assign_user_id']];
            }
            if ($value['operation_user_id']) {
                $value['operation_user_id'] = $users[$value['operation_user_id']];
            }
            switch ($value['work_platform']) {
                case 2:
                    $work_platform = 'voogueme';
                    break;
                case 3:
                    $work_platform = 'nihao';
                    break;
                case 4:
                    $work_platform = 'meeloog';
                    break;
                case 5:
                    $work_platform = 'wesee';
                    break;
                case 9:
                    $work_platform = 'zeelool_es';
                    break;
                case 10:
                    $work_platform = 'zeelool_de';
                    break;
                case 11:
                    $work_platform = 'zeelool_jp';
                    break;
                default:
                    $work_platform = 'zeelool';
                    break;
            }
            $spreadsheet->getActiveSheet()->setCellValue("A" . ($key * 1 + 2), $work_platform);
            $spreadsheet->getActiveSheet()->setCellValue("B" . ($key * 1 + 2), $value['work_type'] == 1 ? '客服工单' : '仓库工单');
            $spreadsheet->getActiveSheet()->setCellValue("C" . ($key * 1 + 2), $value['platform_order']);
            $spreadsheet->getActiveSheet()->setCellValue("D" . ($key * 1 + 2), $value['email']);
            $spreadsheet->getActiveSheet()->setCellValue("E" . ($key * 1 + 2), $value['base_grand_total']);
            $spreadsheet->getActiveSheet()->setCellValue("F" . ($key * 1 + 2), $value['order_pay_currency']);
            $spreadsheet->getActiveSheet()->setCellValue("G" . ($key * 1 + 2), $value['order_pay_method']);
            $spreadsheet->getActiveSheet()->setCellValue("H" . ($key * 1 + 2), $value['order_sku']);
            //求出对应商品的sku
            if ($value['order_sku']) {
                $order_arr_sku = explode(',', $value['order_sku']);
                if (is_array($order_arr_sku)) {
                    $true_sku = [];
                    foreach ($order_arr_sku as $t_sku) {
                        $true_sku[] = $aa = $itemPlatFormSku->getTrueSku($t_sku, $value['work_platform']);
                    }
                    $true_sku_string = implode(',', $true_sku);
                    $spreadsheet->getActiveSheet()->setCellValue("I" . ($key * 1 + 2), $true_sku_string);
                } else {
                    $spreadsheet->getActiveSheet()->setCellValue("I" . ($key * 1 + 2), '');
                }
            } else {
                $spreadsheet->getActiveSheet()->setCellValue("I" . ($key * 1 + 2), '');
            }
            switch ($value['work_status']) {
                case 1:
                    $value['work_status'] = '新建';
                    break;
                case 2:
                    $value['work_status'] = '待审核';
                    break;
                case 3:
                    $value['work_status'] = '待处理';
                    break;
                case 4:
                    $value['work_status'] = '审核拒绝';
                    break;
                case 5:
                    $value['work_status'] = '部分处理';
                    break;
                case 0:
                    $value['work_status'] = '已取消';
                    break;
                default:
                    $value['work_status'] = '已处理';
                    break;
            }
            $spreadsheet->getActiveSheet()->setCellValue("J" . ($key * 1 + 2), $value['work_status']);
            //对应的问题类型大的分类
            $one_category = '';
            foreach ($customer_problem_classify as $problem => $classify) {
                if (in_array($value['problem_type_id'], $classify)) {
                    $one_category = $problem;
                    break;
                }
            }
            $spreadsheet->getActiveSheet()->setCellValue("K" . ($key * 1 + 2), $one_category);
            $spreadsheet->getActiveSheet()->setCellValue("L" . ($key * 1 + 2), $value['problem_type_content']);
            $spreadsheet->getActiveSheet()->setCellValue("M" . ($key * 1 + 2), $value['problem_description']);
            $spreadsheet->getActiveSheet()->setCellValue("N" . ($key * 1 + 2), $value['work_picture']);
            $spreadsheet->getActiveSheet()->setCellValue("O" . ($key * 1 + 2), $value['create_user_name']);
            $spreadsheet->getActiveSheet()->setCellValue("P" . ($key * 1 + 2), $value['is_after_deal_with'] == 1 ? '是' : '否');
            $spreadsheet->getActiveSheet()->setCellValue("Q" . ($key * 1 + 2), $value['assign_user_id']);
            $spreadsheet->getActiveSheet()->setCellValue("R" . ($key * 1 + 2), $value['operation_user_id']);
            $spreadsheet->getActiveSheet()->setCellValue("S" . ($key * 1 + 2), $value['check_note']);
            $spreadsheet->getActiveSheet()->setCellValue("T" . ($key * 1 + 2), $value['create_time']);
            $spreadsheet->getActiveSheet()->setCellValue("U" . ($key * 1 + 2), $value['submit_time']);
            $spreadsheet->getActiveSheet()->setCellValue("V" . ($key * 1 + 2), $value['check_time']);
            $spreadsheet->getActiveSheet()->setCellValue("W" . ($key * 1 + 2), $value['after_deal_with_time']);
            $spreadsheet->getActiveSheet()->setCellValue("X" . ($key * 1 + 2), $value['complete_time']);
            $spreadsheet->getActiveSheet()->setCellValue("Y" . ($key * 1 + 2), $value['replenish_money']);
            $spreadsheet->getActiveSheet()->setCellValue("Z" . ($key * 1 + 2), $value['replenish_increment_id']);
            $spreadsheet->getActiveSheet()->setCellValue("AA" . ($key * 1 + 2), $value['coupon_id']);
            $spreadsheet->getActiveSheet()->setCellValue("AB" . ($key * 1 + 2), $value['coupon_describe']);
            $spreadsheet->getActiveSheet()->setCellValue("AC" . ($key * 1 + 2), $value['coupon_str']);
            $spreadsheet->getActiveSheet()->setCellValue("AD" . ($key * 1 + 2), $value['integral']);
            $spreadsheet->getActiveSheet()->setCellValue("AE" . ($key * 1 + 2), $value['refund_logistics_num']);
            $spreadsheet->getActiveSheet()->setCellValue("AF" . ($key * 1 + 2), $value['refund_money']);
            //退款百分比
            if ((0 < $value['base_grand_total']) && (is_numeric($value['refund_money']))) {
                $spreadsheet->getActiveSheet()->setCellValue("AG" . ($key * 1 + 2), round($value['refund_money'] / $value['base_grand_total'], 2));
            } else {
                $spreadsheet->getActiveSheet()->setCellValue("AG" . ($key * 1 + 2), 0);
            }
            //措施
            if ($info['step'] && array_key_exists($value['id'], $info['step'])) {
                $spreadsheet->getActiveSheet()->setCellValue("AH" . ($key * 1 + 2), $info['step'][$value['id']] . $info['detail'][$value['id']] . $value['result']);
            } else {
                $spreadsheet->getActiveSheet()->setCellValue("AH" . ($key * 1 + 2), '');
            }

            if ($noteInfo && array_key_exists($value['id'], $noteInfo)) {
                $value['note'] = $noteInfo[$value['id']];
                $spreadsheet->getActiveSheet()->setCellValue("AI" . ($key * 1 + 2), $value['note']);
            } else {
                $spreadsheet->getActiveSheet()->setCellValue("AI" . ($key * 1 + 2), '');
            }
            $spreadsheet->getActiveSheet()->setCellValue("AJ" . ($key * 1 + 2), $value['payment_time']);
            $spreadsheet->getActiveSheet()->setCellValue("AK" . ($key * 1 + 2), $value['replacement_order']);
            $spreadsheet->getActiveSheet()->setCellValue("AL" . ($key * 1 + 2), $value['order_skus']);

        }

        //设置宽度
        $spreadsheet->getActiveSheet()->getColumnDimension('A')->setWidth(30);
        $spreadsheet->getActiveSheet()->getColumnDimension('B')->setWidth(12);
        $spreadsheet->getActiveSheet()->getColumnDimension('C')->setWidth(30);
        $spreadsheet->getActiveSheet()->getColumnDimension('D')->setWidth(12);
        $spreadsheet->getActiveSheet()->getColumnDimension('E')->setWidth(30);
        $spreadsheet->getActiveSheet()->getColumnDimension('F')->setWidth(12);
        $spreadsheet->getActiveSheet()->getColumnDimension('G')->setWidth(40);
        $spreadsheet->getActiveSheet()->getColumnDimension('H')->setWidth(40);
        $spreadsheet->getActiveSheet()->getColumnDimension('I')->setWidth(20);
        $spreadsheet->getActiveSheet()->getColumnDimension('J')->setWidth(20);
        $spreadsheet->getActiveSheet()->getColumnDimension('K')->setWidth(14);
        $spreadsheet->getActiveSheet()->getColumnDimension('L')->setWidth(16);
        $spreadsheet->getActiveSheet()->getColumnDimension('M')->setWidth(16);
        $spreadsheet->getActiveSheet()->getColumnDimension('N')->setWidth(50);
        $spreadsheet->getActiveSheet()->getColumnDimension('N')->setWidth(50);
        $spreadsheet->getActiveSheet()->getColumnDimension('O')->setWidth(50);
        $spreadsheet->getActiveSheet()->getColumnDimension('P')->setWidth(20);
        $spreadsheet->getActiveSheet()->getColumnDimension('Q')->setWidth(20);
        $spreadsheet->getActiveSheet()->getColumnDimension('R')->setWidth(20);
        $spreadsheet->getActiveSheet()->getColumnDimension('S')->setWidth(20);
        $spreadsheet->getActiveSheet()->getColumnDimension('T')->setWidth(20);
        $spreadsheet->getActiveSheet()->getColumnDimension('U')->setWidth(20);
        $spreadsheet->getActiveSheet()->getColumnDimension('V')->setWidth(20);
        $spreadsheet->getActiveSheet()->getColumnDimension('W')->setWidth(20);
        $spreadsheet->getActiveSheet()->getColumnDimension('X')->setWidth(20);
        $spreadsheet->getActiveSheet()->getColumnDimension('Y')->setWidth(20);
        $spreadsheet->getActiveSheet()->getColumnDimension('Z')->setWidth(20);
        $spreadsheet->getActiveSheet()->getColumnDimension('AA')->setWidth(20);
        $spreadsheet->getActiveSheet()->getColumnDimension('AB')->setWidth(20);
        $spreadsheet->getActiveSheet()->getColumnDimension('AC')->setWidth(20);
        $spreadsheet->getActiveSheet()->getColumnDimension('AD')->setWidth(20);
        $spreadsheet->getActiveSheet()->getColumnDimension('AE')->setWidth(20);
        $spreadsheet->getActiveSheet()->getColumnDimension('AF')->setWidth(20);
        $spreadsheet->getActiveSheet()->getColumnDimension('AG')->setWidth(20);
        $spreadsheet->getActiveSheet()->getColumnDimension('AH')->setWidth(20);
        $spreadsheet->getActiveSheet()->getColumnDimension('AI')->setWidth(20);
        $spreadsheet->getActiveSheet()->getColumnDimension('AJ')->setWidth(20);
        $spreadsheet->getActiveSheet()->getColumnDimension('AK')->setWidth(20);
        $spreadsheet->getActiveSheet()->getColumnDimension('AL')->setWidth(100);

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

        $spreadsheet->getActiveSheet()->getStyle('A1:AL' . $spreadsheet->getActiveSheet()->getHighestRow())->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);


        $spreadsheet->setActiveSheetIndex(0);
        // return exportExcel($spreadsheet, 'xls', '登陆日志');
        $format = 'csv';
        $savename = '工单数据' . date("YmdHis", time());;

        if ($format == 'xls') {
            //输出Excel03版本
            header('Content-Type:application/vnd.ms-excel');
            $class = "\PhpOffice\PhpSpreadsheet\Writer\Xls";
        } elseif ($format == 'xlsx') {
            //输出07Excel版本
            header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
            $class = "\PhpOffice\PhpSpreadsheet\Writer\Xlsx";
        } elseif ($format == 'csv') {
            //输出07Excel版本
            header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
            $class = "\PhpOffice\PhpSpreadsheet\Writer\Csv";
        }

        //输出名称
        header('Content-Disposition: attachment;filename="' . $savename . '.' . $format . '"');
        //禁止缓存
        header('Cache-Control: max-age=0');
        $writer = new $class($spreadsheet);
        $writer->setPreCalculateFormulas(false);
        $writer->save('php://output');
    }

    /**
     * 批量导入
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
        $listName = ['订单号', '差额', 'SKU', '货币'];
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

        $work_measure = new \app\admin\model\saleaftermanage\WorkOrderMeasure();
        $order_recept = new \app\admin\model\saleaftermanage\WorkOrderRecept();
        foreach ($data as $k => $v) {
            $params['work_platform'] = 3;
            $params['work_type'] = 1;
            $params['platform_order'] = $v[0];
            $params['order_pay_currency'] = $v[3];
            $params['order_pay_method'] = 'paypal_express';
            $params['order_sku'] = $v[2];
            $params['work_status'] = 3;
            $params['problem_type_id'] = 23;
            $params['problem_type_content'] = '其他';
            $params['problem_description'] = '网站bug 镜片折扣未生效 退款';
            $params['create_user_id'] = 75;
            $params['create_user_name'] = '王伟';
            $params['is_check'] = 1;
            $params['assign_user_id'] = 75;
            $params['operation_user_id'] = 75;
            $params['check_note'] = '网站bug 镜片折扣未生效 退款';
            $params['create_time'] = date('Y-m-d H:i:s');
            $params['submit_time'] = date('Y-m-d H:i:s');
            $params['check_time'] = date('Y-m-d H:i:s');
            $params['refund_money'] = $v[1];
            $params['refund_way'] = 'paypal_express';
            $params['recept_person_id'] = 169;
            $result = $this->model->isUpdate(false)->data($params)->save($params);
            if ($result) {
                $list['work_id'] = $this->model->id;
                $list['measure_choose_id'] = 2;
                $list['measure_content'] = '退款';
                $list['create_time'] = date('Y-m-d H:i:s');
                $work_measure->isUpdate(false)->data($list)->save($list);

                $rlist['work_id'] = $this->model->id;
                $rlist['measure_id'] = $work_measure->id;
                $rlist['recept_group_id'] = 'cashier_group';
                $rlist['recept_person_id'] = 169;
                $rlist['recept_person'] = '李亚芳';
                $rlist['create_time'] = date('Y-m-d H:i:s');
                $order_recept->insert($rlist);
            }
        }
        echo 'ok';
    }


    /**
     * 获取跟单规则
     *
     * @Author   lsw 1461069578@qq.com
     * @DateTime 2020-06-30 10:11:23
     * @return void
     */
    public function getDocumentaryRule()
    {
        if ($this->request->isAjax()) {
            $workOrderConfigValue = $this->workOrderConfigValue;
            $all_group = $workOrderConfigValue['group'];
            $documentary_group = $workOrderConfigValue['documentary_group'];
            //创建人跟单
            $documentary_person = $workOrderConfigValue['documentary_person'];

            if (!empty($documentary_group)) {
                foreach ($documentary_group as $dgv) {
                    $documentary_info = (new AuthGroup)->getAllNextGroup($dgv['create_id']);
                    if ($documentary_info) {
                        array_push($documentary_info, $dgv['create_id']);
                        foreach ($documentary_info as $av) {
                            if (is_array($all_group[$av])) {
                                foreach ($all_group[$av] as $vk) {
                                    $documentary_all_person[] = $vk;
                                }
                            }

                        }
                    } else {
                        $documentary_all_person = $all_group[$dgv['create_id']];
                    }
                    if (count(array_filter($documentary_all_person)) >= 1) {
                        $documentary_true_all_person = array_unique($documentary_all_person);
                        if (in_array(session('admin.id'), $documentary_true_all_person)) {
                            if (is_array($all_group[$dgv['documentary_group_id']])) {
                                $all_after_user_id = $all_group[$dgv['documentary_group_id']];
                                //$this->success('','',$all_after_user_id);
                                break;
                            }
                        }
                    }
                }
            }
            if (!empty($documentary_person)) {
                foreach ($documentary_person as $dpv) {
                    if (session('admin.id') == $dpv['create_id']) {
                        if (is_array($all_group[$dpv['documentary_group_id']])) {
                            $all_after_user_id = $all_group[$dpv['documentary_group_id']];
                            //$this->success('','',$all_after_user_id);
                            break;
                        }
                    }
                }
            }
            if ($all_after_user_id) {
                $this->success('', '', $all_after_user_id);
            } else {
                $this->error('选择的跟单部门没有人，请重新选择');
            }
        }

    }

    /**
     * 判断订单是否已质检
     *
     * @Author   lsw 1461069578@qq.com
     * @DateTime 2020-08-13 18:21:10
     * @return void
     */
    public function check_order_quality($platform, $order)
    {
        switch ($platform) {
            case 1:
                $model = Db::connect('database.db_zeelool');
                break;
            case 2:
                $model = Db::connect('database.db_voogueme');
                break;
            case 3:
                $model = Db::connect('database.db_nihao');
                break;
            case 4:
                $model = Db::connect('database.db_meeloog');
                break;
            case 9:
                $model = Db::connect('database.db_zeelool_es');
                break;
            case 10:
                $model = Db::connect('database.db_zeelool_de');
                break;
            case 11:
                $model = Db::connect('database.db_zeelool_jp');
                break;
            default:
                $model = false;
                break;
        }
        if ($platform == 4) {
            $info = $model->table('sales_flat_order')->where('increment_id', $order)->value('custom_is_delivery');
        } else {
            $info = $model->table('sales_flat_order')->where('increment_id', $order)->value('custom_is_delivery_new');
        }
        if ($info == 1) {
            return true;
        } else {
            return false;
        }
    }
}
