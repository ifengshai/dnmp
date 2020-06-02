<?php

namespace app\admin\controller\saleaftermanage;

use app\admin\model\saleaftermanage\WorkOrderNote;
use app\common\controller\Backend;
use think\Cache;
use think\Db;
use think\Exception;
use app\admin\model\AuthGroupAccess;
use think\exception\PDOException;
use think\exception\ValidateException;
use Util\NihaoPrescriptionDetailHelper;
use Util\ZeeloolPrescriptionDetailHelper;
use Util\VooguemePrescriptionDetailHelper;
use Util\WeseeopticalPrescriptionDetailHelper;
use app\admin\model\saleaftermanage\WorkOrderMeasure;
use app\admin\model\saleaftermanage\WorkOrderChangeSku;
use app\admin\model\saleaftermanage\WorkOrderRecept;
use app\admin\model\saleAfterManage\WorkOrderRemark;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use app\admin\model\Admin;
use think\Loader;
use Util\SKUHelper;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Reader\Xlsx;
use PhpOffice\PhpSpreadsheet\Reader\Xls;
use PhpOffice\PhpSpreadsheet\Reader\Csv;
use app\api\controller\Ding;

/**
 * 售后工单列管理
 *
 * @icon fa fa-circle-o
 */
class WorkOrderList extends Backend
{
    protected $noNeedRight = ['getMeasureContent', 'getProblemTypeContent'];
    /**
     * WorkOrderList模型对象
     * @var \app\admin\model\saleaftermanage\WorkOrderList
     */
    protected $model = null;

    public function _initialize()
    {
        parent::_initialize();
        $this->model = new \app\admin\model\saleaftermanage\WorkOrderList;
        $this->step = new \app\admin\model\saleaftermanage\WorkOrderMeasure;
        $this->order_change = new \app\admin\model\saleaftermanage\WorkOrderChangeSku;
        $this->order_remark = new \app\admin\model\saleaftermanage\WorkOrderRemark;
        $this->work_order_note = new \app\admin\model\saleaftermanage\WorkOrderNote;
        $this->view->assign('step', config('workorder.step')); //措施
        $this->assignconfig('workorder', config('workorder')); //JS专用，整个配置文件

        $this->view->assign('check_coupon', config('workorder.check_coupon')); //不需要审核的优惠券
        $this->view->assign('need_check_coupon', config('workorder.need_check_coupon')); //需要审核的优惠券

        //获取所有的国家
        $country = json_decode(file_get_contents('assets/js/country.js'), true);
        $this->view->assign('country', $country);
        $this->recept = new \app\admin\model\saleaftermanage\WorkOrderRecept;
        $this->item = new \app\admin\model\itemmanage\Item;

        //获取当前登录用户所属主管id
        $this->assign_user_id = searchForId(session('admin.id'), config('workorder.kefumanage'));
        //选项卡
        $this->view->assign('getTabList', $this->model->getTabList());

        $this->assignconfig('admin_id', session('admin.id'));
        //查询用户id对应姓名
        $admin = new \app\admin\model\Admin();
        $this->users = $admin->where('status', 'normal')->column('nickname', 'id');
        $this->assignconfig('users', $this->users); //返回用户
        $this->assignconfig('userid', session('admin.id'));
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

            $step_arr[$k]['recept'] = $recept_arr;
        }
        return $step_arr ?: [];
    }

    /**
     * 查看
     */
    public function index()
    {
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
                if ($workIds) {
                    $map = "(id in (" . join(',', $workIds) . ") or after_user_id = {$filter['recept_person_id']} or assign_user_id = {$filter['recept_person_id']}) and work_status not in (0,1,7)";
                } else {
                    $map = "(after_user_id = {$filter['recept_person_id']} or assign_user_id = {$filter['recept_person_id']}) and work_status not in (0,1,7)";
                }
                unset($filter['recept_person_id']);
            }
            if ($filter['recept_person']) {
                $workIds = WorkOrderRecept::where('recept_person_id', 'in', $filter['recept_person'])->column('work_id');
                $map['id'] = ['in', $workIds];
                unset($filter['recept_person']);
            }
            //筛选措施
            if ($filter['measure_choose_id']) {
                $measuerWorkIds = WorkOrderMeasure::where('measure_choose_id', 'in', $filter['measure_choose_id'])->column('work_id');
                if (!empty($map['id'])) {
                    $newWorkIds = array_intersect($workIds, $measuerWorkIds);
                    $map['id']  = ['in', $newWorkIds];
                } else {
                    $map['id']  = ['in', $measuerWorkIds];
                }
                unset($filter['measure_choose_id']);
            }

            $this->request->get(['filter' => json_encode($filter)]);

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
                }

                //工单类型
                if ($v['work_type'] == 1) {
                    $list[$k]['work_type_str'] = '客服工单';
                } else {
                    $list[$k]['work_type_str'] = '仓库工单';
                }

                //是否审核
                if ($v['is_check'] == 1) {
                    $list[$k]['assign_user_name'] = $user_list[$v['assign_user_id']];
                    if ($v['operation_user_id'] != 0) {
                        $list[$k]['operation_user_name'] = $user_list[$v['operation_user_id']];
                    }
                }

                $list[$k]['step_num'] = $this->sel_order_recept($v['id']); //获取措施相关记录
                //是否有处理权限
                $receptPersonIds = explode(',', $v['recept_person_id']);
                //跟单客服跟单处理之后不需要显示处理权限
                // if($v['after_user_id']){
                //     array_unshift($receptPersonIds,$v['after_user_id']);
                // }

                //仓库工单并且经手人未处理
                //1、仓库类型：经手人未处理||已处理未审核||
                if (($v['work_type'] == 2 && $v['is_after_deal_with'] == 0) || in_array($v['work_status'], [0, 1, 2, 4, 6, 7]) || !in_array(session('admin.id'), $receptPersonIds)) {
                    $list[$k]['has_recept'] = 0;
                } else {
                    $list[$k]['has_recept'] = 1;
                }
            }
            $result = array("total" => $total, "rows" => $list);

            return json($result);
        }
        //所有承接人的id
        //客服的所有承接人
        $kefumanages = config('workorder.kefumanage');
        foreach ($kefumanages as $key => $kefumanage) {
            $kefumanageIds[] = $key;
            foreach ($kefumanage as $k => $v) {
                $kefumanageIds[] = $v;
            }
        }
        array_unshift($kefumanageIds, config('workorder.customer_manager'));
        $receptPersonAllIds = array_merge(config('workorder.warehouse_group'), config('workorder.warehouse_lens_group'), config('workorder.cashier_group'), config('workorder.copy_group'), $kefumanageIds);
        $admins = Admin::where('id', 'in', $receptPersonAllIds)->select();
        $this->assign('admins', $admins);
        $this->assignconfig('platform_order', $platform_order ?: '');
        return $this->view->fetch();
    }

    /**
     * 添加
     */
    public function add($ids = null)
    {
        if ($this->request->isPost()) {
            $params = $this->request->post("row/a");
            if ($params) {
                $params = $this->preExcludeFields($params);
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

                    if (!$ids) {
                        //限制不能存在两个相同的未完成的工单
                        $count = $this->model->where(['platform_order' => $params['platform_order'], 'work_status' => ['in', [1, 2, 3, 5]]])->count();
                        if ($count > 0) {
                            throw new Exception("此订单存在未处理完成的工单");
                        }
                    }

                    if (!$params['platform_order']) {
                        throw new Exception("订单号不能为空");
                    }

                    if (!$params['order_pay_currency']) {
                        throw new Exception("请先点击载入数据");
                    }

                    $params['platform_order'] = trim($params['platform_order']);
                    if (!$params['problem_description']) {
                        throw new Exception("问题描述不能为空");
                    }
                    //判断是否选择措施
                    if (!$params['problem_type_id'] && !$params['id']) {
                        throw new Exception("问题类型不能为空");
                    }

                    if (in_array($params['problem_type_id'], [11, 13, 14, 16]) && empty(array_filter($params['order_sku']))) {
                        throw new Exception("Sku不能为空");
                    }
                    $userId = session('admin.id');
                    $userGroupAccess = AuthGroupAccess::where(['uid' => $userId])->column('group_id');
                    $warehouseArr = config('workorder.warehouse_department_rule');
                    $checkIsWarehouse = array_intersect($userGroupAccess, $warehouseArr);
                    if(!empty($checkIsWarehouse)){
                        if (count(array_filter($params['measure_choose_id'])) < 1 && $params['work_type'] == 1 && $params['work_status'] == 2) {
                            throw new Exception("措施不能为空");
                        }
                    }else{
                        if (count(array_filter($params['measure_choose_id'])) < 1 && $params['work_status'] == 2) {
                            throw new Exception("措施不能为空");
                        }                        
                    }
                    //判断是否选择措施

                    //更换镜框判断是否有库存 
                    if (($params['change_frame'] && $params['problem_type_id'] == 1  && $params['work_type'] == 1) || ($params['change_frame'] && $params['work_type'] == 2 && in_array($params['problem_id'], [2, 3]))) {
                        $skus = $params['change_frame']['change_sku'];
                        $num = $params['change_frame']['change_number'];
                        if (count(array_filter($skus)) < 1) throw new Exception("SKU不能为空");
                        //判断SKU是否有库存
                        $this->skuIsStock($skus, $params['work_platform'], $num);
                    }

                    //判断赠品是否有库存
                    //判断补发是否有库存
                    if (in_array(7, array_filter($params['measure_choose_id'])) || in_array(6, array_filter($params['measure_choose_id']))) {
                        if (in_array(7, array_filter($params['measure_choose_id']))) {
                            $originalSkus = $params['replacement']['original_sku'];
                            $originalNums = $params['replacement']['original_number'];
                        } else {
                            $originalSkus = $params['gift']['original_sku'];
                            $originalNums = $params['gift']['original_number'];
                        }

                        foreach ($originalSkus as $key => $originalSku) {
                            if (!$originalSku) exception('sku不能为空');
                            if (!$originalNums[$key]) exception('数量必须大于0');
                            $this->skuIsStock([$originalSku], $params['work_platform'], [$originalNums[$key]]);
                        }
                    }

                    //判断工单类型 1客服 2仓库
                    if ($params['work_type'] == 1) {
                        $params['problem_type_content'] = config('workorder.customer_problem_type')[$params['problem_type_id']];
                    } elseif ($params['work_type'] == 2) {
                        $params['problem_type_content'] = config('workorder.warehouse_problem_type')[$params['problem_type_id']];
                        $params['after_user_id'] = implode(',', config('workorder.copy_group')); //经手人
                    }
                    //判断是否选择退款措施
                    if (!in_array(2, array_filter($params['measure_choose_id']))) {
                        unset($params['refund_money']);
                    } else {
                        if (!$params['refund_money']) {
                            throw new Exception("退款金额不能为空");
                        }
                    }

                    //判断是否选择补价措施
                    if (!in_array(8, array_filter($params['measure_choose_id']))) {
                        unset($params['replenish_increment_id']);
                        unset($params['replenish_money']);
                    } else {
                        if (!$params['replenish_increment_id']) {
                            throw new Exception("补差价订单号不能为空");
                        }
                    }

                    //判断是否选择积分措施
                    if (!in_array(10, array_filter($params['measure_choose_id']))) {
                        unset($params['integral']);
                    } else {
                        if (!$params['integral'] || !$params['email']) {
                            throw new Exception("积分和邮箱不能为空");
                        }
                    }

                    //判断是否选择退件措施
                    if (!in_array(11, array_filter($params['measure_choose_id']))) {
                        unset($params['refund_logistics_num']);
                    } else {
                        if (!$params['refund_logistics_num']) {
                            throw new Exception("退回物流单号不能为空");
                        }
                    }

                    //判断优惠券 不需要审核的优惠券
                    if ($params['coupon_id'] && in_array(9, array_filter($params['measure_choose_id']))) {

                        foreach (config('workorder.check_coupon') as $v) {
                            if ($v['id'] == $params['coupon_id']) {
                                $params['coupon_describe'] = $v['desc'];
                                break;
                            }
                        }
                    }
                    //判断优惠券 需要审核的优惠券
                    if ($params['need_coupon_id'] && in_array(9, array_filter($params['measure_choose_id']))) {
                        $params['coupon_id'] = $params['need_coupon_id'];
                        foreach (config('workorder.need_check_coupon') as $v) {
                            if ($v['id'] == $params['coupon_id']) {
                                $params['coupon_describe'] = $v['desc'];
                                break;
                            }
                        }
                        $params['is_check'] = 1;
                    }

                    //选择有优惠券时 值必须为真
                    if (in_array(9, array_filter($params['measure_choose_id'])) && !$params['coupon_id']) {
                        throw new Exception("优惠券不能为空");
                    }

                    //如果积分大于200需要审核
                    if ($params['integral'] > 200) {
                        //需要审核
                        $params['is_check'] = 1;
                        //创建人对应主管
                        $params['assign_user_id'] = $this->assign_user_id;
                    }

                    //如果退款金额大于30 需要审核
                    if ($params['refund_money'] > 30) {
                        $params['is_check'] = 1;
                    }
                    //增加是否退款值
                    if ($params['refund_money'] > 0) {
                        $params['is_refund'] = 1;
                    }
                    //判断审核人
                    if ($params['is_check'] == 1 || $params['need_coupon_id']) {
                        /**
                         * 1、退款金额大于30 经理审核
                         * 2、赠品数量大于1 经理审核
                         * 3、补发数量大于1 经理审核
                         * 4、优惠券等于100% 经理审核  50%主管审核 固定额度无需审核
                         */
                        $coupon = config('workorder.need_check_coupon')[$params['need_coupon_id']]['sum'];
                        $giftOriginalNumber = $params['gift']['original_number'] ?: [];
                        $replacementOriginalNumber = $params['replacement']['original_number'] ?: [];
                        if ($params['refund_money'] > 30 || array_sum($giftOriginalNumber) > 1 || array_sum($replacementOriginalNumber) > 1 || $coupon == 100) {
                            //客服经理
                            $params['assign_user_id'] = config('workorder.customer_manager');
                        } else {
                            //创建人对应主管
                            $params['assign_user_id'] = $this->assign_user_id ?: session('admin.id');
                        }
                    }
                    //提交时间
                    if ($params['work_status'] == 2) {
                        $params['submit_time'] = date('Y-m-d H:i:s');
                    }

                    //判断如果不需要审核 或者工单类型为仓库 工单状态默认为审核通过
                    if (($params['is_check'] == 0 && $params['work_status'] == 2) || ($params['work_type'] == 2 && $params['work_status'] == 2)) {
                        $params['work_status'] = 3;
                    }
                    if ($params['content']) {
                        //取出备注记录并且销毁
                        $content = $params['content'];
                        unset($params['content']);
                    }

                    //如果为真则为处理任务
                    if (!$params['id']) {
                        $params['recept_person_id'] = $params['recept_person_id'] ?: session('admin.id');
                        $params['create_user_name'] = session('admin.nickname');
                        $params['create_user_id'] = session('admin.id');
                        $params['create_time'] = date('Y-m-d H:i:s');
                        $params['order_sku'] = implode(',', $params['order_sku']);
                        $params['assign_user_id'] = $params['assign_user_id'] ?: 0;
                        $result = $this->model->allowField(true)->save($params);
                        if (false === $result) {
                            throw new Exception("添加失败！！");
                        }
                        $work_id = $this->model->id;
                    } else {
                        //如果需要审核 则修改状态为待审核
                        if ($params['is_check'] == 1) {
                            $params['work_status'] = 2;
                        }
                        $work_id = $params['id'];
                        unset($params['problem_type_content']);
                        unset($params['work_picture']);
                        unset($params['work_level']);
                        unset($params['order_sku']);
                        unset($params['problem_description']);
                        $params['is_after_deal_with'] = 1;
                        $result = $this->model->allowField(true)->save($params, ['id' => $work_id]);
                    }
                    if ($content) {
                        $noteData['note_time'] =  date('Y-m-d H:i', time());
                        $noteData['note_user_id'] =  session('admin.id');
                        $noteData['note_user_name'] =  session('admin.nickname');
                        $noteData['work_id'] =  $work_id;
                        $noteData['user_group_id'] =  0;
                        $noteData['content'] =  $content;
                        $contentResult = $this->work_order_note->allowField(true)->save($noteData);
                        if (false === $contentResult) {
                            throw new Exception("备注添加失败！！");
                        }
                    }


                    $params['problem_type_id'] = $params['problem_type_id'] ?: $params['problem_id'];

                    //循环插入措施
                    if (count(array_filter($params['measure_choose_id'])) > 0) {
                        //措施
                        foreach ($params['measure_choose_id'] as $k => $v) {
                            $measureList['work_id'] = $work_id;
                            $measureList['measure_choose_id'] = $v;
                            $measureList['measure_content'] = config('workorder.step')[$v];
                            $measureList['create_time'] = date('Y-m-d H:i:s');

                            //插入措施表
                            $res = $this->step->insertGetId($measureList);
                            if (false === $res) {
                                throw new Exception("添加失败！！");
                            }

                            //根据措施读取承接组、承接人 默认是客服问题组配置
                            $appoint_ids = $params['order_recept']['appoint_ids'][$v];
                            $appoint_users = $params['order_recept']['appoint_users'][$v];
                            $appoint_group = $params['order_recept']['appoint_group'][$v];
                            //循环插入承接人
                            $appointList = [];
                            foreach ($appoint_ids as $key => $val) {
                                $appointList[$key]['work_id'] = $work_id;
                                $appointList[$key]['measure_id'] = $res;
                                //如果没有承接人 默认为创建人
                                if ($val == 'undefined') {
                                    $appointList[$key]['recept_group_id'] = $this->assign_user_id;
                                    $appointList[$key]['recept_person_id'] = session('admin.id');
                                    $appointList[$key]['recept_person'] = session('admin.nickname');
                                } else {

                                    $appointList[$key]['recept_group_id'] = $appoint_group[$key];
                                    $appointList[$key]['recept_person_id'] = $val;
                                    $appointList[$key]['recept_person'] = $appoint_users[$key];
                                }

                                $appointList[$key]['create_time'] = date('Y-m-d H:i:s');
                            }
                            //插入承接人表
                            $receptRes = $this->recept->saveAll($appointList);
                            if (false === $receptRes) {
                                throw new Exception("添加失败！！");
                            }

                            //更改镜片，补发，赠品
                            $this->model->changeLens($params, $work_id, $v, $res);
                            $this->model->changeFrame($params, $work_id, $v, $res);
                            $this->model->cancelOrder($params, $work_id, $v, $res);
                        }
                    }


                    //不需要审核且是非草稿状态时直接发送积分，赠送优惠券
                    if ($params['is_check'] != 1 && $this->model->work_status != 1) {
                        //赠送积分
                        if (in_array(10, array_filter($params['measure_choose_id']))) {
                            $this->model->presentIntegral($work_id);
                        }
                        //直接发送优惠券
                        if (in_array(9, array_filter($params['measure_choose_id']))) {
                            $this->model->presentCoupon($work_id);
                        }
                    }
                    //非草稿状态进入审核阶段
                    if ($this->model->work_status != 1) {
                        $this->model->checkWork($work_id);
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
                    //通知
                    // if ($this->model->work_type == 1) {
                    //     if ($this->model->work_status == 2) {
                    //         Ding::cc_ding($this->model->assign_user_id, '', '工单ID:' . $work_id . '😎😎😎😎有新工单需要你审核😎😎😎😎', '有新工单需要你审核');
                    //     } elseif ($this->model->work_status == 3) {
                    //         $usersId = explode(',', $this->model->recept_person_id);
                    //         Ding::cc_ding($usersId, '', '工单ID:' . $work_id . '😎😎😎😎有新工单需要你处理😎😎😎😎', '有新工单需要你处理');
                    //     }
                    // }

                    // //经手人
                    // if ($this->model->work_type == 2 && $this->model->work_status == 3 && !$params['id']) {

                    //     Ding::cc_ding($this->model->after_user_id, '', '工单ID:' . $work_id . '😎😎😎😎有新工单需要你处理😎😎😎😎', '有新工单需要你处理');
                    // }

                    // //跟单处理
                    // if ($this->model->work_type == 2 && $this->model->work_status == 3 && $params['id']) {

                    //     Ding::cc_ding($params['recept_person_id'], '', '工单ID:' . $work_id . '😎😎😎😎有新工单需要你处理😎😎😎😎', '有新工单需要你处理');
                    // }

                    $this->success();
                } else {
                    $this->error(__('No rows were inserted'));
                }
            }
            $this->error(__('Parameter %s can not be empty', ''));
        }
        if ($ids) {
            $row = $this->model->get($ids);
            //求出订单sku列表,传输到页面当中
            $skus = $this->model->getSkuList($row->work_platform, $row->platform_order);
            if (is_array($skus['sku'])) {
                $arrSkus = [];
                foreach ($skus['sku'] as $val) {
                    $arrSkus[$val] = $val;
                }
                //查询用户id对应姓名
                $admin = new \app\admin\model\Admin();
                $users = $admin->where('status', 'normal')->column('nickname', 'id');
                $this->assignconfig('users', $users); //返回用户            
                $this->view->assign('skus', $arrSkus);
            }

            if (1 == $row->work_type) { //判断工单类型，客服工单
                $this->view->assign('work_type', 1);
                $this->assignconfig('work_type', 1);
                $this->view->assign('problem_type', config('workorder.customer_problem_type')); //客服问题类型          
            } else { //仓库工单
                $this->view->assign('work_type', 2);
                $this->assignconfig('work_type', 2);
                $this->view->assign('problem_type', config('workorder.warehouse_problem_type')); //仓库问题类型
            }

            //把问题类型传递到js页面
            if (!empty($row->problem_type_id)) {
                $this->assignconfig('problem_id', $row->problem_type_id);
            }
            $this->assignconfig('work_type', $row->work_type);

            $this->assignconfig('ids', $row->id);
            //求出工单选择的措施传递到js页面
            $measureList = WorkOrderMeasure::workMeasureList($row->id);
            // dump(!empty($measureList));
            // exit;
            if (!empty($measureList)) {
                $this->assignconfig('measureList', $measureList);
            }
            $this->view->assign('row', $row);
        } else {
            //获取用户ID和所在权限组
            $userId = session('admin.id');
            $userGroupAccess = AuthGroupAccess::where(['uid' => $userId])->column('group_id');
            $warehouseArr = config('workorder.warehouse_department_rule');
            $checkIsWarehouse = array_intersect($userGroupAccess, $warehouseArr);
            if (!empty($checkIsWarehouse)) {
                $this->view->assign('work_type', 2);
                $this->assignconfig('work_type', 2);
                $this->view->assign('problem_type', config('workorder.warehouse_problem_type')); //仓库问题类型   
            } else {
                $this->view->assign('work_type', 1);
                $this->assignconfig('work_type', 1);
                $customer_problem_classifys = config('workorder.customer_problem_classify');
                $problem_types = config('workorder.customer_problem_type');
                $problem_type = [];
                $i = 0;
                foreach ($customer_problem_classifys as $key => $customer_problem_classify) {
                    $problem_type[$i]['name'] = $key;
                    foreach ($customer_problem_classify as $k => $v) {
                        $problem_type[$i]['type'][$k] = [
                            'id' => $v,
                            'name' => $problem_types[$v]
                        ];
                    }
                    $i++;
                }
                $this->view->assign('problem_type', $problem_type); //客服问题类型
            }
        }

        $this->assignconfig('userid', session('admin.id'));
        return $this->view->fetch();
    }

    /**
     * 判断sku是否有库存
     *
     * @Description
     * @author wpl
     * @since 2020/04/16 10:59:53 
     * @param [type] $skus sku数组
     * @param [type] $siteType 站点类型
     * @return void
     */
    protected function skuIsStock($skus = [], $siteType, $num = [])
    {
        if (!array_filter($skus)) {
            throw new Exception("SKU不能为空");
        }

        $itemPlatFormSku = new \app\admin\model\itemmanage\ItemPlatformSku();
        $item = new \app\admin\model\itemmanage\Item();
        //根据平台sku转sku
        foreach (array_filter($skus) as $k => $v) {
            //转换sku
            $sku = $itemPlatFormSku->getTrueSku(trim($v), $siteType);
            //查询库存 判断是否开启预售
            $res = $item->where(['is_open' => 1, 'is_del' => 1, 'sku' => $sku])->field('available_stock,presell_status,presell_create_time,presell_end_time,presell_residue_num')->find();
            //判断可用库存
            if ($res->available_stock < $num[$k]) {
                //判断没库存情况下 是否开启预售 并且预售时间是否满足 并且预售数量是否足够
                if ($res->presell_status != 1 ||  ($res->presell_status == 1  && (time() < strtotime($res->presell_create_time) || time() > strtotime($res->presell_end_time) || $res->presell_residue_num < $num[$k]))) {
                    throw new Exception($v . '暂无库存！！');
                }
            }
        }
        return true;
    }

    /**
     * 编辑
     *
     * @Description
     * @author lsw
     * @since 2020/04/14 15:00:19 
     * @param [type] $ids
     * @return void
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
                    if (!$params['problem_description']) {
                        throw new Exception("问题描述不能为空");
                    }
                    if (in_array($params['problem_type_id'], [11, 13, 14, 16]) && empty(array_filter($params['order_sku']))) {
                        throw new Exception("Sku不能为空");
                    }
                    //判断是否选择措施
                    $params['measure_choose_id'] = $params['measure_choose_id'] ?? [];

                    $userId = session('admin.id');
                    $userGroupAccess = AuthGroupAccess::where(['uid' => $userId])->column('group_id');
                    $warehouseArr = config('workorder.warehouse_department_rule');
                    $checkIsWarehouse = array_intersect($userGroupAccess, $warehouseArr);
                    if(!empty($checkIsWarehouse)){
                        if (count(array_filter($params['measure_choose_id'])) < 1 && $params['work_type'] == 1 && $params['work_status'] == 2) {
                            throw new Exception("措施不能为空");
                        }
                    }else{
                        if (count(array_filter($params['measure_choose_id'])) < 1 && $params['work_status'] == 2) {
                            throw new Exception("措施不能为空");
                        }                        
                    }

                    //更换镜框判断是否有库存
                    if ($params['change_frame'] && $params['problem_type_id'] == 1) {
                        $skus = $params['change_frame']['change_sku'];
                        $num = $params['change_frame']['change_number'];
                        if (count(array_filter($skus)) < 1) throw new Exception("SKU不能为空");
                        //判断SKU是否有库存
                        $this->skuIsStock($skus, $params['work_platform'], $num);
                    }
                    //判断赠品是否有库存
                    //判断补发是否有库存
                    if (in_array(7, array_filter($params['measure_choose_id'])) || in_array(6, array_filter($params['measure_choose_id']))) {
                        if (in_array(7, array_filter($params['measure_choose_id']))) {
                            $originalSkus = $params['replacement']['original_sku'];
                            $originalNums = $params['replacement']['original_number'];
                        } else {
                            $originalSkus = $params['gift']['original_sku'];
                            $originalNums = $params['gift']['original_number'];
                        }

                        foreach ($originalSkus as $key => $originalSku) {
                            if (!$originalSku) exception('sku不能为空');
                            if (!$originalNums[$key]) exception('数量必须大于0');
                            $this->skuIsStock([$originalSku], $params['work_platform'], [$originalNums[$key]]);
                        }
                    }

                    //判断工单类型 1客服 2仓库
                    if ($params['work_type'] == 1) {
                        $params['problem_type_content'] = config('workorder.customer_problem_type')[$params['problem_type_id']];
                    } elseif ($params['work_type'] == 2) {
                        $params['problem_type_content'] = config('workorder.warehouse_problem_type')[$params['problem_type_id']];
                        $params['after_user_id'] = implode(',', config('workorder.copy_group')); //经手人
                    }
                    //判断是否选择退款措施
                    if (!in_array(2, array_filter($params['measure_choose_id']))) {
                        unset($params['refund_money']);
                    } else {
                        if (!$params['refund_money']) {
                            throw new Exception("退款金额不能为空");
                        }
                    }

                    //判断是否选择补价措施
                    if (!in_array(8, array_filter($params['measure_choose_id']))) {
                        unset($params['replenish_increment_id']);
                        unset($params['replenish_money']);
                    } else {
                        if (!$params['replenish_increment_id']) {
                            throw new Exception("补差价订单号不能为空");
                        }
                    }

                    //判断是否选择积分措施
                    if (!in_array(10, array_filter($params['measure_choose_id']))) {
                        unset($params['integral']);
                        unset($params['integral_describe']);
                    } else {
                        if (!$params['integral'] || !$params['email']) {
                            throw new Exception("积分和邮箱不能为空");
                        }
                    }

                    //判断是否选择退件措施
                    if (!in_array(11, array_filter($params['measure_choose_id']))) {
                        unset($params['refund_logistics_num']);
                    } else {
                        if (!$params['refund_logistics_num']) {
                            throw new Exception("退回物流单号不能为空");
                        }
                    }

                    //判断优惠券 不需要审核的优惠券
                    if ($params['coupon_id'] && in_array(9, array_filter($params['measure_choose_id']))) {
                        foreach (config('workorder.check_coupon') as $v) {
                            if ($v['id'] == $params['coupon_id']) {
                                $params['coupon_describe'] = $v['desc'];
                                break;
                            }
                        }
                    }
                    //判断优惠券 需要审核的优惠券
                    if ($params['need_coupon_id'] && in_array(9, array_filter($params['measure_choose_id']))) {
                        $params['coupon_id'] = $params['need_coupon_id'];
                        foreach (config('workorder.need_check_coupon') as $v) {
                            if ($v['id'] == $params['coupon_id']) {
                                $params['coupon_describe'] = $v['desc'];
                                break;
                            }
                        }
                        $params['is_check'] = 1;
                    }

                    //选择有优惠券时 值必须为真
                    if (in_array(9, array_filter($params['measure_choose_id'])) && !$params['coupon_id']) {
                        throw new Exception("优惠券不能为空");
                    }

                    //如果积分大于200需要审核
                    if ($params['integral'] > 200) {
                        //需要审核
                        $params['is_check'] = 1;
                        //创建人对应主管
                        $params['assign_user_id'] = $this->assign_user_id;
                    }

                    //如果退款金额大于30 需要审核
                    if ($params['refund_money'] > 30) {
                        $params['is_check'] = 1;
                    }
                    if ($params['refund_money'] > 0) {
                        $params['is_refund'] = 1;
                    }
                    //判断审核人
                    if ($params['is_check'] == 1 || $params['need_coupon_id']) {
                        /**
                         * 1、退款金额大于30 经理审核
                         * 2、赠品数量大于1 经理审核
                         * 3、补发数量大于1 经理审核
                         * 4、优惠券等于100% 经理审核  50%主管审核 固定额度无需审核
                         */
                        $coupon = config('workorder.need_check_coupon')[$params['need_coupon_id']]['sum'];
                        $giftOriginalNumber = $params['gift']['original_number'] ?: [];
                        $replacementOriginalNumber = $params['replacement']['original_number'] ?: [];
                        if ($params['refund_money'] > 30 || array_sum($giftOriginalNumber) > 1 || array_sum($replacementOriginalNumber) > 1 || $coupon == 100) {
                            //客服经理
                            $params['assign_user_id'] = config('workorder.customer_manager');
                        } else {
                            //创建人对应主管
                            $params['assign_user_id'] = $this->assign_user_id ?: session('admin.id');
                        }
                    }

                    //提交时间
                    if ($params['work_status'] == 2) {
                        $params['submit_time'] = date('Y-m-d H:i:s');
                    }

                    $params['recept_person_id'] = $params['recept_person_id'] ?: session('admin.id');
                    //更新之前的措施全部去掉
                    $updateData['replenish_money'] = '';
                    $updateData['replenish_increment_id'] = '';
                    $updateData['coupon_id'] = 0;
                    $updateData['coupon_describe'] = '';
                    $updateData['coupon_str'] = '';
                    $updateData['integral'] = '';
                    $updateData['refund_logistics_num'] = '';
                    $updateData['refund_money'] = '';
                    $updateData['is_refund'] = 0;
                    $updateData['replacement_order'] = '';
                    $updateData['integral_describe'] = '';
                    $updateInfo = $row->allowField(true)->save($updateData);
                    if (false === $updateInfo) {
                        throw new Exception('更新失败!!');
                    }
                    $result = $row->allowField(true)->save($params);
                    if (false === $result) {
                        throw new Exception("编辑失败！！");
                    }
                    //循环插入措施
                    if (count(array_filter($params['measure_choose_id'])) > 0) {

                        //措施
                        WorkOrderMeasure::where(['work_id' => $row->id])->delete();
                        WorkOrderRecept::where(['work_id' => $row->id])->delete();
                        WorkOrderChangeSku::where(['work_id' => $row->id])->delete();
                        foreach ($params['measure_choose_id'] as $k => $v) {
                            $measureList['work_id'] = $row->id;
                            $measureList['measure_choose_id'] = $v;
                            $measureList['measure_content'] = config('workorder.step')[$v];
                            $measureList['create_time']     = date('Y-m-d H:i:s');
                            //插入措施表
                            $res = $this->step->insertGetId($measureList);
                            if (false === $res) {
                                throw new Exception("添加失败！！");
                            }

                            //根据措施读取承接组、承接人 默认是客服问题组配置
                            $appoint_ids = $params['order_recept']['appoint_ids'][$v];
                            $appoint_users = $params['order_recept']['appoint_users'][$v];
                            $appoint_group = $params['order_recept']['appoint_group'][$v];
                            //循环插入承接人
                            $appointList = [];
                            foreach ($appoint_ids as $key => $val) {
                                $appointList[$key]['work_id'] = $row->id;
                                $appointList[$key]['measure_id'] = $res;
                                //如果没有承接人 默认为创建人
                                if ($val == 'undefined') {
                                    $appointList[$key]['recept_group_id'] = $this->assign_user_id;
                                    $appointList[$key]['recept_person_id'] = session('admin.id');
                                    $appointList[$key]['recept_person'] = session('admin.nickname');
                                } else {

                                    $appointList[$key]['recept_group_id'] = $appoint_group[$key];
                                    $appointList[$key]['recept_person_id'] = $val;
                                    $appointList[$key]['recept_person'] = $appoint_users[$key];
                                }

                                $appointList[$key]['create_time'] = date('Y-m-d H:i:s');
                            }
                            //插入承接人表
                            $receptRes = $this->recept->saveAll($appointList);
                            if (false === $receptRes) {
                                throw new Exception("添加失败！！");
                            }
                            //更改镜片，补发，赠品
                            $this->model->changeLens($params, $row->id, $v, $res);
                            $this->model->changeFrame($params, $row->id, $v, $res);
                            $this->model->cancelOrder($params, $row->id, $v, $res);
                        }
                    }


                    //不需要审核时直接发送积分，赠送优惠券
                    if (!$params['is_check']  && $params['work_status'] != 1) {
                        //赠送积分
                        if (in_array(10, array_filter($params['measure_choose_id']))) {
                            $this->model->presentIntegral($row->id);
                        }
                        //直接发送优惠券
                        if (in_array(9, array_filter($params['measure_choose_id']))) {
                            $this->model->presentCoupon($row->id);
                        }
                    }
                    //非草稿状态进入审核阶段
                    if ($params['work_status'] != 1) {
                        $this->model->checkWork($row->id);
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

                    //通知
                    if ($row->work_type == 1) {
                        if ($row->work_status == 2) {
                            Ding::cc_ding($row->assign_user_id, '', '工单ID:' . $row->id . '😎😎😎😎有新工单需要你审核😎😎😎😎', '有新工单需要你审核');
                        } elseif ($row->work_status == 3) {
                            $usersId = explode(',', $row->recept_person_id);
                            Ding::cc_ding($usersId, '', '工单ID:' . $row->id . '😎😎😎😎有新工单需要你处理😎😎😎😎', '有新工单需要你处理');
                        }
                    }
                    //经手人
                    if ($row->work_type == 2 && $row->work_status == 3) {

                        Ding::cc_ding($row->after_user_id, '', '工单ID:' . $row->id . '😎😎😎😎有新工单需要你处理😎😎😎😎', '有新工单需要你处理');
                    }

                    $this->success();
                } else {
                    $this->error(__('No rows were updated'));
                }
            }
            $this->error(__('Parameter %s can not be empty', ''));
        }
        $this->view->assign("row", $row);
        if (1 == $row->work_type) { //判断工单类型，客服工单
            $this->view->assign('work_type', 1);
            $this->assignconfig('work_type', 1);
            $customer_problem_classifys = config('workorder.customer_problem_classify');
            $problem_types = config('workorder.customer_problem_type');
            $problem_type = [];
            $i = 0;
            foreach ($customer_problem_classifys as $key => $customer_problem_classify) {
                $problem_type[$i]['name'] = $key;
                foreach ($customer_problem_classify as $k => $v) {
                    $problem_type[$i]['type'][$k] = [
                        'id' => $v,
                        'name' => $problem_types[$v]
                    ];
                }
                $i++;
            }
            $this->view->assign('problem_type', $problem_type); //客服问题类型
        } else { //仓库工单
            $this->view->assign('work_type', 2);
            $this->assignconfig('work_type', 2);
            $this->view->assign('problem_type', config('workorder.warehouse_problem_type')); //仓库问题类型
        }
        //求出订单sku列表,传输到页面当中
        $skus = $this->model->getSkuList($row->work_platform, $row->platform_order);
        if (is_array($skus['sku'])) {
            $arrSkus = [];
            foreach ($skus['sku'] as $val) {
                $arrSkus[$val] = $val;
            }
            //查询用户id对应姓名
            $admin = new \app\admin\model\Admin();
            $users = $admin->where('status', 'normal')->column('nickname', 'id');
            $this->assignconfig('users', $users); //返回用户            
            $this->view->assign('skus', $arrSkus);
        }
        //把问题类型传递到js页面
        if (!empty($row->problem_type_id)) {
            $this->assignconfig('problem_type_id', $row->problem_type_id);
        }

        //求出工单选择的措施传递到js页面
        $measureList = WorkOrderMeasure::workMeasureList($row->id);
        if (!empty($measureList)) {
            $this->assignconfig('measureList', $measureList);
        }
        return $this->view->fetch();
    }

    /**
     * 获取订单sku数据
     *
     * @Description
     * @author wpl
     * @since 2020/04/10 15:41:09 
     * @return void
     */
    public function get_sku_list()
    {
        if (request()->isAjax()) {
            $sitetype = input('sitetype');
            $order_number = input('order_number');
            $skus = $this->model->getSkuList($sitetype, $order_number);
            if ($skus) {
                $this->success('操作成功！！', '', $skus);
            } else {
                $this->error('未获取到数据！！');
            }
        }
        $this->error('404 not found');
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

            try {
                //获取地址、处方等信息
                $res = $this->model->getAddress($siteType, $incrementId);
                //请求接口获取lens_type，coating_type，prescription_type等信息
                $lens = $this->model->getReissueLens($siteType, $res['showPrescriptions']);
            } catch (\Exception $e) {
                $this->error($e->getMessage());
            }

            if ($res) {
                $this->success('操作成功！！', '', ['address' => $res, 'lens' => $lens]);
            } else {
                $this->error('未获取到数据！！');
            }
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
            try {
                //获取地址、处方等信息
                $res = $this->model->getAddress($siteType, $incrementId);
                $lens = $this->model->getReissueLens($siteType, $res['prescriptions'], 2);
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
                $res = $this->model->getAddress($siteType, $incrementId);
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
            $color_id = input('color_id', '');
            $key = $siteType . '_getlens';
            $data = Cache::get($key);
            if (!$data) {
                $data = $this->model->httpRequest($siteType, 'magic/product/lensData');
                Cache::set($key, $data, 3600 * 24);
            }
            if ($color_id) {
                $lensType = $data['lens_color_list'] ?: [];
            } else {
                $lensType = $data['lens_list'][$prescriptionType] ?: [];
            }
            if ($lensType) {
                $this->success('操作成功！！', '', $lensType);
            } else {
                $this->error('未获取到数据！！');
            }
        }
        $this->error('404 not found');
    }
    /**
     * 获取订单order的镜框等信息
     *
     * @Description
     * @author lsw
     * @since 2020/04/13 17:28:49 
     * @return void
     */
    public function ajax_get_order($ordertype = null, $order_number = null)
    {
        if ($this->request->isAjax()) {
            if ($ordertype < 1 || $ordertype > 5) { //不在平台之内
                return $this->error('选择平台错误,请重新选择', '', 'error', 0);
            }
            if (!$order_number) {
                return  $this->error('订单号不存在，请重新选择', '', 'error', 0);
            }
            if ($ordertype == 1) {
                $result = ZeeloolPrescriptionDetailHelper::get_one_by_increment_id($order_number);
            } elseif ($ordertype == 2) {
                $result = VooguemePrescriptionDetailHelper::get_one_by_increment_id($order_number);
            } elseif ($ordertype == 3) {
                $result = NihaoPrescriptionDetailHelper::get_one_by_increment_id($order_number);
            } elseif (5 == $ordertype) {
                $result = WeseeopticalPrescriptionDetailHelper::get_one_by_increment_id($order_number);
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
     * 获取已经添加工单中的订单信息
     *
     * @Description
     * @author lsw
     * @since 2020/04/16 10:29:02 
     * @return void
     */
    public function ajax_edit_order($ordertype = null, $order_number = null, $work_id = null, $change_type = null)
    {
        if ($this->request->isAjax()) {
            if ($ordertype < 1 || $ordertype > 5) { //不在平台之内
                return $this->error('选择平台错误,请重新选择', '', 'error', 0);
            }
            if (!$order_number) {
                return  $this->error('订单号不存在，请重新选择', '', 'error', 0);
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
                } elseif (5 == $ordertype) {
                    $result = WeseeopticalPrescriptionDetailHelper::get_one_by_increment_id($order_number);
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
     * 测试
     * @throws \Exception
     */
    public function test()
    {
        //$this->model->presentCoupon(235);
        //$this->model->presentIntegral(233);
        //$this->model->createOrder(3, 338);
        $result = $this->model->deductionStock(496, 521);
        dump($result);
    }
    /**
     * 工单详情
     *
     * @Description
     * @author lsw
     * @since 2020/04/16 15:33:36 
     * @param [type] $ids
     * @return void
     */
    public function detail($ids = null)
    {
        $row = $this->model->get($ids);
		    
        $operateType = input('operate_type', 0);
        if (!$row) {
            $this->error(__('No Results were found'));
        }

        if ($operateType == 2) {
            if ($row->work_status != 2 || $row->is_check != 1 || !in_array(session('admin.id'), [$row->assign_user_id, config('workorder.customer_manager')])) {
                $this->error('没有审核权限');
            }
        } elseif ($operateType == 3) {
            //找出工单的所有承接人
            $receptPersonIds = explode(',', $row->recept_person_id);
            if ($row->after_user_id) {
                array_unshift($receptPersonIds, $row->after_user_id);
            }
            //仓库工单并且经手人未处理
            //1、仓库类型：经手人未处理||已处理未审核||
            if (($row->work_type == 2 && $row->is_after_deal_with == 0) || in_array($row->work_status, [0, 1, 2, 4, 6, 7]) || !in_array(session('admin.id'), $receptPersonIds)) {
                $this->error('没有处理的权限');
            }
        }

        $adminIds = $this->getDataLimitAdminIds();
        if (is_array($adminIds)) {
            if (!in_array($row[$this->dataLimitField], $adminIds)) {
                $this->error(__('You have no permission'));
            }
        }

        if (1 == $row->work_type) { //判断工单类型，客服工单
            $this->view->assign('work_type', 1);
            $this->assignconfig('work_type', 1);
            $customer_problem_classifys = config('workorder.customer_problem_classify');
            $problem_types = config('workorder.customer_problem_type');
            $problem_type = [];
            $i = 0;
            foreach ($customer_problem_classifys as $key => $customer_problem_classify) {
                $problem_type[$i]['name'] = $key;
                foreach ($customer_problem_classify as $k => $v) {
                    $problem_type[$i]['type'][$k] = [
                        'id' => $v,
                        'name' => $problem_types[$v]
                    ];
                }
                $i++;
            }
            $this->view->assign('problem_type', $problem_type); //客服问题类型
        } else { //仓库工单
            $this->view->assign('work_type', 2);
            $this->assignconfig('work_type', 2);
            $this->view->assign('problem_type', config('workorder.warehouse_problem_type')); //仓库问题类型
        }
        //求出订单sku列表,传输到页面当中
        $skus = $this->model->getSkuList($row->work_platform, $row->platform_order);
        if (is_array($skus['sku'])) {
            $arrSkus = [];
            foreach ($skus['sku'] as $val) {
                $arrSkus[$val] = $val;
            }
            //查询用户id对应姓名
            $admin = new \app\admin\model\Admin();
            $users = $admin->where('status', 'normal')->column('nickname', 'id');
            $this->assignconfig('users', $users); //返回用户            
            $this->view->assign('skus', $arrSkus);
        }
        //把问题类型传递到js页面
        if (!empty($row->problem_type_id)) {
            $this->assignconfig('problem_type_id', $row->problem_type_id);
        }
		//$ids = 520;
		$workOrderNote = WorkOrderNote::where('work_id', $ids)->select(); //回复内容
        $this->view->assign('workOrderNote', $workOrderNote);
		
		
		
		//求出工单选择的措施传递到js页面
        $measureList = WorkOrderMeasure::workMeasureList($row->id);  
        if (!empty($measureList)) {
            $this->assignconfig('measureList', $measureList);
        }
        $this->assignconfig('operate_type', $operateType);
        if (2 <= $row->work_status) {
            $row->assign_user = Admin::where(['id' => $row->assign_user_id])->value('nickname');
        } else {
            $row->assign_user  = Admin::where(['id' => $row->operation_user_id])->value('nickname');
        }
        $this->view->assign("row", $row);
        if ($operateType == 2) { //审核
            return $this->view->fetch('saleaftermanage/work_order_list/check');
        }
        if ($operateType == 3) { //处理
            //获取处理的措施
            $recepts = WorkOrderRecept::where('work_id', $row->id)->with('measure')->group('recept_group_id,measure_id')->select();
            $this->view->assign('recepts', $recepts);
            return $this->view->fetch('saleaftermanage/work_order_list/process');
        }
		
		//查询工单处理备注
        $remarkList = $this->order_remark->where('work_id', $ids)->select();
        //获取处理的措施
        $recepts = WorkOrderRecept::where('work_id', $row->id)->with('measure')->group('recept_group_id,measure_id')->select();
        $this->view->assign('recepts', $recepts);

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
        $workId = $params['id'];
        $workType = $params['work_type'];
        $success = $params['success'];
        if (!$params['check_note']) {
            $this->error('审核意见不能为空');
        }
        $work = $this->model->find($workId);
        if (!$work) {
            $this->error('工单不存在');
        }
        //开始审核
        try {
            $this->model->checkWork($workId, $params);
        } catch (Exception $e) {
            $this->error($e->getMessage());
        }
        $this->success('已审核');
    }
    /**
     * 获取工单的更改镜片、补发、赠品的信息
     *
     * @Description
     * @author lsw
     * @since 2020/04/16 16:49:21 
     * @param [type] $work_id
     * @param [type] $order_number
     * @param [type] $change_type
     * @return void
     */
    public function ajax_change_order($work_id = null, $order_type = null, $order_number = null, $change_type = null, $operate_type = '')
    {
        if ($this->request->isAjax()) {
            if ($order_type < 1 || $order_type > 5) { //不在平台之内
                return $this->error('选择平台错误,请重新选择', '', 'error', 0);
            }
            if (!$order_number) {
                return  $this->error('订单号不存在，请重新选择', '', 'error', 0);
            }
            if (!$work_id) {
                return $this->error('工单不存在，请重新选择', '', 'error', 0);
            }
            $result = WorkOrderChangeSku::getOrderChangeSku($work_id, $order_type, $order_number, $change_type);
            if ($result) {
                $result = collection($result)->toArray();
                $userinfo_option = unserialize($result[0]['userinfo_option']);
                $arr = [];
                foreach ($result as $keys => $val) {
                    $result[$keys]['prescription_options'] = unserialize($val['prescription_option']);
                }
                if (!empty($userinfo_option)) {
                    $arr['userinfo_option'] = $userinfo_option;
                }
                $arr['info']            = $result;
            }
            if (5 == $change_type) { //补发信息
                //获取地址、处方等信息
                $res = $this->model->getAddress($order_type, $order_number);
                //请求接口获取lens_type，coating_type，prescription_type等信息
                if (isset($arr) && !empty($arr)) {
                    $lens = $this->model->getEditReissueLens($order_type, $res['showPrescriptions'], 1, $result, $operate_type);
                } else {
                    $lens = $this->model->getEditReissueLens($order_type, $res['showPrescriptions'], 1, [], $operate_type);
                }
                $lensForm = $this->model->getReissueLens($order_type, $res['showPrescriptions'], 1);
            } elseif (2 == $change_type) { //更改镜片信息
                $res = $this->model->getAddress($order_type, $order_number);
                if (isset($arr) && !empty($arr)) {
                    $lens = $this->model->getEditReissueLens($order_type, $res['prescriptions'], 2, $result, $operate_type);
                } else {
                    $lens = $this->model->getEditReissueLens($order_type, $res['prescriptions'], 2, [], $operate_type);
                }
                $lensForm = $this->model->getReissueLens($order_type, $res['prescriptions'], 2);
            } elseif (4 == $change_type) { //赠品信息
                $res = $this->model->getAddress($order_type, $order_number);
                if (isset($arr) && !empty($arr)) {
                    $lens = $this->model->getEditReissueLens($order_type, $res['prescriptions'], 3, $result, $operate_type);
                } else {
                    $lens = $this->model->getEditReissueLens($order_type, $res['prescriptions'], 3, [], $operate_type);
                }
                $lensForm = $this->model->getReissueLens($order_type, $res['prescriptions'], 3);
            }
            if ($res) {
                if (5 == $change_type) {
                    $this->success('操作成功！！', '', ['address' => $res, 'lens' => $lens, 'arr' => $userinfo_option, 'lensform' => $lensForm]);
                } else {
                    $this->success('操作成功！！', '', ['lens' => $lens, 'lensform' => $lensForm]);
                }
            } else {
                $this->error('未获取到数据！！');
            }
        } else {
            return $this->error('404 Not Found');
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
     * 修改工单状态
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
                $receptInfo =  (new WorkOrderRecept())->getOneRecept($recept_id);
                $result = false;
                if ($receptInfo) {
                    if ($receptInfo->recept_person_id != session('admin.id')) {
                        $this->error(__('您不能处理此工单'));
                    }
                    //当要处理成功时需要判断库存是否存在
                    if (1 == $params['success']) {
                        $checkSku = $this->checkMeasure($receptInfo['measure_id']);
                        if ($checkSku) {
                            $this->error(__("以下sku库存不足{$checkSku},无法处理成功"));
                        }
                    }
                    $result = $this->model->handleRecept($receptInfo['id'], $receptInfo['work_id'], $receptInfo['measure_id'], $receptInfo['recept_group_id'], $params['success'], $params['note']);
                }
                if ($result !== false) {
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
            list($where, $sort, $order, $offset, $limit) = $this->buildparams();
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

            $result = array("total" => $total, "rows" => $list);

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
            list($where, $sort, $order, $offset, $limit) = $this->buildparams();
            $map['integral'] = ['>', 0];
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

            $result = array("total" => $total, "rows" => $list);

            return json($result);
        }
        return $this->view->fetch();
    }

    /**
     * 批量打印标签
     *
     * @Description
     * @author wpl
     * @since 2020/04/22 17:23:47 
     * @return void
     */
    public function batch_print_label()
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
     * 根据SKU数量平铺标签
     *
     * @Description
     * @author wpl
     * @since 2020/04/22 17:24:01 
     * @param [type] $origin_order_item
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
     * @param [type] $array
     * @param [type] $field
     * @param string $sort
     * @return void
     */
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
    /**
     * 判断措施当中的扣减库存是否存在
     *
     * @Description
     * @author lsw
     * @since 2020/04/24 09:30:03 
     * @param array $receptInfo
     * @return void
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
            $arr[$k]['platform_type']   = $v['platform_type'];
        }
        $itemPlatFormSku = new \app\admin\model\itemmanage\ItemPlatformSku();


        //根据平台sku转sku
        $notEnough = [];
        foreach (array_filter($arr) as $v) {
            //转换sku
            $sku = $itemPlatFormSku->getTrueSku(trim($v['original_sku']), $v['platform_type']);
            //查询库存 判断是否开启预售
            $res = $this->item->where(['is_open' => 1, 'is_del' => 1, 'sku' => $sku])->field('available_stock,presell_status,presell_create_time,presell_end_time,presell_residue_num')->find();
            //判断可用库存
            if ($res->available_stock < $v['original_number']) {
                //判断没库存情况下 是否开启预售 并且预售时间是否满足 并且预售数量是否足够
                if ($res->presell_status != 1 ||  ($res->presell_status == 1  && (time() < strtotime($res->presell_create_time) || time() > strtotime($res->presell_end_time) || $res->presell_residue_num <  $v['original_number']))) {
                    $notEnough[] = $sku;
                }
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
        return array_merge(config('workorder.warehouse_problem_type'), config('workorder.customer_problem_type'));
    }

    /**
     * 措施筛选下拉列表
     *
     * @Description
     * @author lsw
     * @since 2020/05/26 14:01:15 
     * @return void
     */
    public function getMeasureContent()
    {
        return config('workorder.step');
    }
    /**
     * 工单备注
     */

    public function workordernote($ids = null)
    {
        if ($this->request->isPost()) {
            $params = $this->request->post("row/a");
            if ($params) {
                $data['note_time'] =  date('Y-m-d H:i', time());
                $data['note_user_id'] =  session('admin.id');
                $data['note_user_name'] =  session('admin.nickname');
                $data['work_id'] =  $params['work_id'];
                $data['user_group_id'] =  0;
                $data['content'] =  $params['content'];
                Db::startTrans();
                try {
                    $res_status = WorkOrderNote::create($data);
                    //查询用户的角色组id
                    $authGroupIds = AuthGroupAccess::where('uid', session('admin.id'))->column('group_id');
                    $work = $this->model->find($params['work_id']);
                    $work_order_note_status = $work->work_order_note_status;

                    if (array_intersect($authGroupIds, config('workorder.customer_department_rule'))) {
                        //客服组
                        $work_order_note_status = 1;
                    }
                    if (array_intersect($authGroupIds, config('workorder.warehouse_department_rule'))) {
                        //仓库部
                        $work_order_note_status = 2;
                    }
                    if (array_intersect($authGroupIds, config('workorder.finance_department_rule'))) {
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
     * 导出工单
     *
     * @Description
     * @author lsw
     * @since 2020/04/30 09:34:48 
     * @return void
     */
    public function batch_export_xls()
    {
        set_time_limit(0);
        ini_set('memory_limit', '512M');
        $ids = input('ids');
        $addWhere = '1=1';
        if ($ids) {
            $addWhere .= " AND id IN ({$ids})";
        }

        list($where) = $this->buildparams();
        $list = $this->model
            ->where($where)
            ->where($addWhere)
            ->select();
        $list = collection($list)->toArray();
        //查询用户id对应姓名
        $admin = new \app\admin\model\Admin();
        $users = $admin->where('status', 'normal')->column('nickname', 'id');
        $arr = [];
        foreach ($list as $vals) {
            $arr[] = $vals['id'];
        }
        //求出所有的措施
        $info = $this->step->fetchMeasureRecord($arr);
        if ($info) {
            $info = collection($info)->toArray();
        } else {
            $info = [];
        }
        //求出所有的承接详情
        $this->recept = new \app\admin\model\saleaftermanage\WorkOrderRecept;
        $receptInfo = $this->recept->fetchReceptRecord($arr);
        if ($receptInfo) {
            $receptInfo = collection($receptInfo)->toArray();
        } else {
            $receptInfo = [];
        }
        //求出所有的回复
        $noteInfo = $this->work_order_note->fetchNoteRecord($arr);
        if ($noteInfo) {
            $noteInfo = collection($noteInfo)->toArray();
        } else {
            $noteInfo = [];
        }
        //从数据库查询需要的数据
        $spreadsheet = new Spreadsheet();
        //常规方式：利用setCellValue()填充数据
        $spreadsheet->setActiveSheetIndex(0)->setCellValue("A1", "工单平台")
            ->setCellValue("B1", "工单类型")
            ->setCellValue("C1", "平台订单号");   //利用setCellValues()填充数据
        $spreadsheet->setActiveSheetIndex(0)->setCellValue("D1", "订单支付的货币类型")
            ->setCellValue("E1", "订单的支付方式");
        $spreadsheet->setActiveSheetIndex(0)->setCellValue("F1", "订单中的sku")
            ->setCellValue("G1", "工单状态");
        $spreadsheet->setActiveSheetIndex(0)->setCellValue("H1", "工单级别")
            ->setCellValue("I1", "问题类型")
            ->setCellValue("J1", "工单问题描述");
        $spreadsheet->setActiveSheetIndex(0)->setCellValue("K1", "工单图片")
            ->setCellValue("L1", "工单创建人")
            ->setCellValue("M1", "工单经手人")
            ->setCellValue("N1", "经手人是否处理")
            ->setCellValue("O1", "工单是否需要审核")
            ->setCellValue("P1", "指派工单审核人")
            ->setCellValue("Q1", "实际审核人")
            ->setCellValue("R1", "审核人备注")
            ->setCellValue("S1", "新建状态时间")
            ->setCellValue("T1", "开始走流程时间")
            ->setCellValue("U1", "工单审核时间")
            ->setCellValue("V1", "经手人处理时间")
            ->setCellValue("W1", "工单完成时间")
            ->setCellValue("X1", "取消、撤销时间")
            ->setCellValue("Y1", "取消、撤销操作人")
            ->setCellValue("Z1", "补差价的金额")
            ->setCellValue("AA1", "补差价的订单号")
            ->setCellValue("AB1", "优惠券类型")
            ->setCellValue("AC1", "优惠券描述")
            ->setCellValue("AD1", "优惠券")
            ->setCellValue("AE1", "积分")
            ->setCellValue("AF1", "客户邮箱")
            ->setCellValue("AG1", "退回物流单号")
            ->setCellValue("AH1", "退款金额")
            ->setCellValue("AI1", "退款方式")
            ->setCellValue("AJ1", "积分描述")
            ->setCellValue("AK1", "补发订单号")
            ->setCellValue("AL1", "措施")
            ->setCellValue("AM1", "措施详情")
            ->setCellValue("AN1", "承接详情")
            ->setCellValue("AO1", "工单回复备注");
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
                    $value['work_platform'] = 'voogueme';
                    break;
                case 3:
                    $value['work_platform'] = 'nihao';
                    break;
                case 4:
                    $value['work_platform'] = 'amazon';
                    break;
                case 5:
                    $value['work_platform'] = 'wesee';
                    break;
                default:
                    $value['work_platform'] = 'zeelool';
                    break;
            }
            $spreadsheet->getActiveSheet()->setCellValue("A" . ($key * 1 + 2), $value['work_platform']);
            $spreadsheet->getActiveSheet()->setCellValue("B" . ($key * 1 + 2), $value['work_type'] == 1 ? '客服工单' : '仓库工单');
            $spreadsheet->getActiveSheet()->setCellValue("C" . ($key * 1 + 2), $value['platform_order']);
            $spreadsheet->getActiveSheet()->setCellValue("D" . ($key * 1 + 2), $value['order_pay_currency']);
            $spreadsheet->getActiveSheet()->setCellValue("E" . ($key * 1 + 2), $value['order_pay_method']);
            $spreadsheet->getActiveSheet()->setCellValue("F" . ($key * 1 + 2), $value['order_sku']);
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
            $spreadsheet->getActiveSheet()->setCellValue("G" . ($key * 1 + 2), $value['work_status']);
            switch ($value['work_level']) {
                case 1:
                    $value['work_level'] = '低';
                    break;
                case 2:
                    $value['work_level'] = '中';
                    break;
                    $value['work_level'] = '高';
                    break;
            }
            $spreadsheet->getActiveSheet()->setCellValue("H" . ($key * 1 + 2), $value['work_level']);
            $spreadsheet->getActiveSheet()->setCellValue("I" . ($key * 1 + 2), $value['problem_type_content']);
            $spreadsheet->getActiveSheet()->setCellValue("J" . ($key * 1 + 2), $value['problem_description']);
            $spreadsheet->getActiveSheet()->setCellValue("K" . ($key * 1 + 2), $value['work_picture']);
            $spreadsheet->getActiveSheet()->setCellValue("L" . ($key * 1 + 2), $value['create_user_name']);
            $spreadsheet->getActiveSheet()->setCellValue("M" . ($key * 1 + 2), $value['after_user_id']);
            $spreadsheet->getActiveSheet()->setCellValue("N" . ($key * 1 + 2), $value['is_after_deal_with'] == 1 ? '是' : '否');
            $spreadsheet->getActiveSheet()->setCellValue("O" . ($key * 1 + 2), $value['is_check'] == 1 ? '是' : '否');
            $spreadsheet->getActiveSheet()->setCellValue("P" . ($key * 1 + 2), $value['assign_user_id']);
            $spreadsheet->getActiveSheet()->setCellValue("Q" . ($key * 1 + 2), $value['operation_user_id']);
            $spreadsheet->getActiveSheet()->setCellValue("R" . ($key * 1 + 2), $value['check_note']);
            $spreadsheet->getActiveSheet()->setCellValue("S" . ($key * 1 + 2), $value['create_time']);
            $spreadsheet->getActiveSheet()->setCellValue("T" . ($key * 1 + 2), $value['submit_time']);
            $spreadsheet->getActiveSheet()->setCellValue("U" . ($key * 1 + 2), $value['check_time']);
            $spreadsheet->getActiveSheet()->setCellValue("V" . ($key * 1 + 2), $value['after_deal_with_time']);
            $spreadsheet->getActiveSheet()->setCellValue("W" . ($key * 1 + 2), $value['complete_time']);
            $spreadsheet->getActiveSheet()->setCellValue("X" . ($key * 1 + 2), $value['cancel_time']);
            $spreadsheet->getActiveSheet()->setCellValue("Y" . ($key * 1 + 2), $value['cancel_person']);
            $spreadsheet->getActiveSheet()->setCellValue("Z" . ($key * 1 + 2), $value['replenish_money']);
            $spreadsheet->getActiveSheet()->setCellValue("AA" . ($key * 1 + 2), $value['replenish_increment_id']);
            $spreadsheet->getActiveSheet()->setCellValue("AB" . ($key * 1 + 2), $value['coupon_id']);
            $spreadsheet->getActiveSheet()->setCellValue("AC" . ($key * 1 + 2), $value['coupon_describe']);
            $spreadsheet->getActiveSheet()->setCellValue("AD" . ($key * 1 + 2), $value['coupon_str']);
            $spreadsheet->getActiveSheet()->setCellValue("AE" . ($key * 1 + 2), $value['integral']);
            $spreadsheet->getActiveSheet()->setCellValue("AF" . ($key * 1 + 2), $value['email']);
            $spreadsheet->getActiveSheet()->setCellValue("AG" . ($key * 1 + 2), $value['refund_logistics_num']);
            $spreadsheet->getActiveSheet()->setCellValue("AH" . ($key * 1 + 2), $value['refund_money']);
            $spreadsheet->getActiveSheet()->setCellValue("AI" . ($key * 1 + 2), $value['refund_way']);
            $spreadsheet->getActiveSheet()->setCellValue("AJ" . ($key * 1 + 2), $value['integral_describe']);
            $spreadsheet->getActiveSheet()->setCellValue("AK" . ($key * 1 + 2), $value['replacement_order']);
            //措施
            if (array_key_exists($value['id'], $info['step'])) {
                $spreadsheet->getActiveSheet()->setCellValue("AL" . ($key * 1 + 2), $info['step'][$value['id']]);
            } else {
                $spreadsheet->getActiveSheet()->setCellValue("AL" . ($key * 1 + 2), '');
            }
            //措施详情
            if (array_key_exists($value['id'], $info['detail'])) {
                $spreadsheet->getActiveSheet()->setCellValue("AM" . ($key * 1 + 2), $info['detail'][$value['id']]);
            } else {
                $spreadsheet->getActiveSheet()->setCellValue("AM" . ($key * 1 + 2), '');
            }
            //承接
            if (array_key_exists($value['id'], $receptInfo)) {

                $value['result'] = $receptInfo[$value['id']];
                $spreadsheet->getActiveSheet()->setCellValue("AN" . ($key * 1 + 2), $value['result']);
            } else {
                $spreadsheet->getActiveSheet()->setCellValue("AN" . ($key * 1 + 2), '');
            }
            //回复
            if (array_key_exists($value['id'], $noteInfo)) {
                $value['note'] = $noteInfo[$value['id']];
                $spreadsheet->getActiveSheet()->setCellValue("AO" . ($key * 1 + 2), $value['note']);
            } else {
                $spreadsheet->getActiveSheet()->setCellValue("AO" . ($key * 1 + 2), '');
            }
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
        $spreadsheet->getActiveSheet()->getColumnDimension('AM')->setWidth(100);
        $spreadsheet->getActiveSheet()->getColumnDimension('AN')->setWidth(100);
        $spreadsheet->getActiveSheet()->getColumnDimension('AO')->setWidth(100);
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

        $spreadsheet->getActiveSheet()->getStyle('A1:P' . $spreadsheet->getActiveSheet()->getHighestRow())->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);


        $spreadsheet->setActiveSheetIndex(0);
        // return exportExcel($spreadsheet, 'xls', '登陆日志');
        $format = 'xlsx';
        $savename = '工单数据' . date("YmdHis", time());;
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
        $listName = ['订单号', '差额',  'SKU', '货币'];
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
}
