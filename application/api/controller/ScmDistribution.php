<?php

namespace app\api\controller;

use app\admin\model\platformmanage\MagentoPlatform;
use app\admin\model\saleaftermanage\WorkOrderChangeSku;
use app\admin\model\saleaftermanage\WorkOrderMeasure;
use app\admin\model\StockLog;
use think\Exception;
use think\exception\PDOException;
use think\exception\ValidateException;
use app\admin\model\order\order\NewOrderItemProcess;
use app\admin\model\warehouse\StockHouse;
use app\admin\model\DistributionLog;
use app\admin\model\DistributionAbnormal;
use app\admin\model\order\order\NewOrderItemOption;
use app\admin\model\order\order\NewOrder;
use app\admin\model\itemmanage\ItemPlatformSku;
use app\admin\model\itemmanage\Item;
use app\admin\model\order\order\NewOrderProcess;
use app\admin\model\warehouse\ProductBarCodeItem;
use app\admin\model\order\order\LensData;
use app\admin\model\saleaftermanage\WorkOrderList;

/**
 * 供应链配货接口类
 * @author lzh
 * @since 2020-10-20
 */
class ScmDistribution extends Scm
{
    /**
     * 子订单模型对象
     * @var object
     * @access protected
     */
    protected $_new_order_item_process = null;

    /**
     * 库位模型对象
     * @var object
     * @access protected
     */
    protected $_stock_house = null;

    /**
     * 配货异常模型对象
     * @var object
     * @access protected
     */
    protected $_distribution_abnormal = null;

    /**
     * 子订单处方模型对象
     * @var object
     * @access protected
     */
    protected $_new_order_item_option = null;

    /**
     * 主订单模型对象
     * @var object
     * @access protected
     */
    protected $_new_order = null;

    /**
     * sku映射关系模型对象
     * @var object
     * @access protected
     */
    protected $_item_platform_sku = null;

    /**
     * 商品库存模型对象
     * @var object
     * @access protected
     */
    protected $_item = null;

    /**
     * 库存日志模型对象
     * @var object
     * @access protected
     */
    protected $_stock_log = null;
    /**
     * 商品条形码模型对象
     * @var object
     * @access protected
     */
    protected $_product_bar_code_item = null;

    /**
     * 主订单状态模型对象
     * @var object
     * @access protected
     */
    protected $_new_order_process = null;

    /**
     * 工单措施模型对象
     * @var object
     * @access protected
     */
    protected $_work_order_measure = null;

    /**
     * 工单sku变动模型对象
     * @var object
     * @access protected
     */
    protected $_work_order_change_sku = null;

    /**
     * 镜片模型对象
     * @var object
     * @access protected
     */
    protected $_lens_data = null;

    protected function _initialize()
    {
        parent::_initialize();

        $this->_new_order_item_process = new NewOrderItemProcess();
        $this->_stock_house = new StockHouse();
        $this->_distribution_abnormal = new DistributionAbnormal();
        $this->_new_order_item_option = new NewOrderItemOption();
        $this->_new_order = new NewOrder();
        $this->_item_platform_sku = new ItemPlatformSku();
        $this->_item = new Item();
        $this->_stock_log = new StockLog();
        $this->_new_order_process = new NewOrderProcess();
        $this->_product_bar_code_item = new ProductBarCodeItem();
        $this->_work_order_measure = new WorkOrderMeasure();
        $this->_work_order_change_sku = new WorkOrderChangeSku();
        $this->_lens_data = new LensData();
        $this->_work_order_list = new WorkOrderList();
    }

    /**
     * 标记异常
     *
     * @参数 string item_order_number  子订单号
     * @参数 int type  异常类型
     * @author lzh
     * @return mixed
     */
    public function sign_abnormal()
    {
        $item_order_number = $this->request->request('item_order_number');
        empty($item_order_number) && $this->error(__('子订单号不能为空'), [], 403);
        $type = $this->request->request('type');
        empty($type) && $this->error(__('异常类型不能为空'), [], 403);
        //获取子订单数据
        $item_process_info = $this->_new_order_item_process
            ->field('id,abnormal_house_id')
            ->where('item_order_number', $item_order_number)
            ->find();
        empty($item_process_info) && $this->error(__('子订单不存在'), [], 403);
        !empty($item_process_info['abnormal_house_id']) && $this->error(__('已标记异常，不能多次标记'), [], 403);
        $item_process_id = $item_process_info['id'];

        //自动分配异常库位号
        $stock_house_info = $this->_stock_house
            ->field('id,coding')
            ->where(['status' => 1, 'type' => 4, 'occupy' => ['<', 10000]])
            ->order('occupy', 'desc')
            ->find();
        if (empty($stock_house_info)) {
            DistributionLog::record($this->auth, $item_process_id, 0, '异常暂存架没有空余库位');
            $this->error(__('异常暂存架没有空余库位'), [], 405);
        }

        $this->_distribution_abnormal->startTrans();
        $this->_new_order_item_process->startTrans();
        try {
            //绑定异常子单号
            $abnormal_data = [
                'item_process_id' => $item_process_id,
                'type' => $type,
                'status' => 1,
                'create_time' => time(),
                'create_person' => $this->auth->nickname
            ];

            $this->_distribution_abnormal->allowField(true)->save($abnormal_data);

            //子订单绑定异常库位号
            $this->_new_order_item_process
                ->allowField(true)
                ->isUpdate(true, ['item_order_number' => $item_order_number])
                ->save(['abnormal_house_id' => $stock_house_info['id']]);

            //异常库位占用数量+1
            $this->_stock_house
                ->where(['id' => $stock_house_info['id']])
                ->setInc('occupy', 1);

            //配货日志
            DistributionLog::record($this->auth, $item_process_id, 9, "子单号{$item_order_number}，异常暂存架{$stock_house_info['coding']}库位");

            //提交事务
            $this->_distribution_abnormal->commit();
            $this->_new_order_item_process->commit();
        } catch (ValidateException $e) {
            $this->_distribution_abnormal->rollback();
            $this->_new_order_item_process->rollback();
            $this->error($e->getMessage(), [], 406);
        } catch (PDOException $e) {
            $this->_distribution_abnormal->rollback();
            $this->_new_order_item_process->rollback();
            $this->error($e->getMessage(), [], 407);
        } catch (Exception $e) {
            $this->_distribution_abnormal->rollback();
            $this->_new_order_item_process->rollback();
            $this->error($e->getMessage(), [], 408);
        }

        $this->success(__("请将子单号{$item_order_number}的商品放入异常暂存架{$stock_house_info['coding']}库位"), ['coding' => $stock_house_info['coding']], 200);

    }

        /**
     * 发货系统标记异常
     *
     * @参数 string item_order_number  子订单号
     * @参数 int type  异常类型
     * @author lzh
     * @return mixed
     */
    public function in_sign_abnormal($item_order_number,$type,$flag = 0)
    {
        empty($item_order_number) && $this->error(__('子订单号不能为空'), [], 403);
        empty($type) && $this->error(__('异常类型不能为空'), [], 403);
        //获取子订单数据
        $item_process_info = $this->_new_order_item_process
            ->field('id,abnormal_house_id')
            ->where('item_order_number', $item_order_number)
            ->find();
        empty($item_process_info) && $this->error(__('子订单不存在'), [], 403);
        !empty($item_process_info['abnormal_house_id']) && $this->error(__('已标记异常，不能多次标记'), [], 403);
        $item_process_id = $item_process_info['id'];

        //自动分配异常库位号
        $stock_house_info = $this->_stock_house
            ->field('id,coding')
            ->where(['status' => 1, 'type' => 4, 'occupy' => ['<', 10000]])
            ->order('occupy', 'desc')
            ->find();
        if (empty($stock_house_info)) {
            DistributionLog::record($this->auth, $item_process_id, 0, '异常暂存架没有空余库位');
            $this->error(__('异常暂存架没有空余库位'), [], 405);
        }

            //绑定异常子单号
            $abnormal_data = [
                'item_process_id' => $item_process_id,
                'type' => $type,
                'status' => 1,
                'create_time' => time(),
                'create_person' => $this->auth->nickname
            ];
            //print_r($this->_distribution_abnormal);die;
            $res = $this->_distribution_abnormal->allowField(true)->isUpdate(false)->save($abnormal_data);
            //子订单绑定异常库位号
            $this->_new_order_item_process
                ->allowField(true)
                ->isUpdate(true, ['item_order_number' => $item_order_number])
                ->save(['abnormal_house_id' => $stock_house_info['id']]);

            //异常库位占用数量+1
            $this->_stock_house
                ->where(['id' => $stock_house_info['id']])
                ->setInc('occupy', 1);

            //配货日志
            DistributionLog::record($this->auth, $item_process_id, 9, "子单号{$item_order_number}，异常暂存架{$stock_house_info['coding']}库位");
    }
    /**
     * 子单号模糊搜索（配货通用）
     *
     * @参数 string query  搜索内容
     * @author lzh
     * @return mixed
     */
    public function fuzzy_search()
    {
        $query = $this->request->request('query');
        $status = $this->request->request('status');
        empty($query) && $this->error(__('搜索内容不能为空'), [], 403);

        //获取子订单数据
        $list = $this->_new_order_item_process
            ->where(['item_order_number' => ['like', "%{$query}%"], 'distribution_status' => $status])
            ->field('item_order_number,sku')
            ->order('created_at', 'desc')
            ->limit(0, 100)
            ->select();
        $list = collection($list)->toArray();

        $this->success('', ['list' => $list], 200);
    }

    /**
     * 获取并校验子订单数据（配货通用）
     *
     * @param string $item_order_number 子订单号
     * @param int $check_status 检测状态
     * @author lzh
     * @return mixed
     */
    protected function info($item_order_number, $check_status)
    {
        empty($item_order_number) && $this->error(__('子订单号不能为空'), [], 403);

        //获取子订单数据
        $item_process_info = $this->_new_order_item_process
            ->where('item_order_number', $item_order_number)
            ->field('id,option_id,distribution_status,temporary_house_id,order_prescription_type,order_id,customize_status')
            ->find();
        empty($item_process_info) && $this->error(__('子订单不存在'), [], 403);

        //查询订单号
        $order_info = $this->_new_order
            ->field('increment_id,status')
            ->where(['id' => $item_process_info['order_id']])
            ->find();
        // 'processing' != $order_info['status'] && $this->error(__('当前订单状态不可操作'), [], 405);
        'processing' != $order_info['status'] && $this->error(__('订单状态异常'), [], 405);

        //检测是否有工单未处理
        $check_work_order = $this->_work_order_measure
            ->alias('a')
            ->field('a.item_order_number,a.measure_choose_id')
            ->join(['fa_work_order_list' => 'b'], 'a.work_id=b.id')
            ->where([
                'a.operation_type' => 0,
                'b.platform_order' => $order_info['increment_id'],
                'b.work_status' => ['in', [1, 2, 3, 5]]
            ])
            ->select();
        if ($check_work_order) {
            foreach ($check_work_order as $val) {
                (3 == $val['measure_choose_id'] //主单取消措施未处理
                    ||
                    $val['item_order_number'] == $item_order_number //子单措施未处理:更改镜框18、更改镜片19、取消20
                )

                // && $this->error(__('有工单未处理，无法操作'), [], 405);
                && $this->error(__('子订单存在工单A-01-01'), [], 405);
                if ($val['measure_choose_id'] == 21){
                    // $this->error(__('有工单存在暂缓措施未处理，无法操作'), [], 405);
                    $this->error(__('子订单存在工单A-01-01'), [], 405);
                }
            }
        }

        //判断异常状态
        $abnormal_id = $this->_distribution_abnormal
            ->where(['item_process_id' => $item_process_info['id'], 'status' => 1])
            ->value('id');
        // $abnormal_id && $this->error(__('有异常待处理，无法操作'), [], 405);
        $abnormal_id && $this->error(__('子订单存在异常A-01-01'), [], 405);

        //检测状态
        $status_arr = [
            2 => '待配货',
            3 => '待配镜片',
            4 => '待加工',
            5 => '待印logo',
            6 => '待成品质检',
            7 => '待合单'
        ];
        $status_arr1 = [
            2 => '配货',
            3 => '配镜片',
            4 => '加工',
            5 => '印logo',
            6 => '成品质检',
            7 => '合单'
        ];
        // $check_status != $item_process_info['distribution_status'] && $this->error(__('只有' . $status_arr[$check_status] . '状态才能操作'), [], 405);
        $check_status != $item_process_info['distribution_status'] && $this->error(__('去'.$status_arr[($item_process_info['distribution_status']+1)]), [], 405);






        //获取子订单处方数据
        $option_info = $this->_new_order_item_option
            ->where('id', $item_process_info['option_id'])
            ->find();
        if ($option_info) $option_info = $option_info->toArray();

        //获取更改镜框最新信息
        $change_sku = $this->_work_order_change_sku
            ->alias('a')
            ->join(['fa_work_order_measure' => 'b'], 'a.measure_id=b.id')
            ->where([
                'a.change_type' => 1,
                'a.item_order_number' => $item_order_number,
                'b.operation_type' => 1
            ])
            ->order('a.id', 'desc')
            ->value('a.change_sku');
        if ($change_sku) {
            $option_info['sku'] = $change_sku;
        }

        //获取更改镜片最新处方信息
        $change_lens = $this->_work_order_change_sku
            ->alias('a')
            ->field('a.od_sph,a.od_cyl,a.od_axis,a.od_add,a.pd_r,a.od_pv,a.od_bd,a.od_pv_r,a.od_bd_r,a.os_sph,a.os_cyl,a.os_axis,a.os_add,a.pd_l,a.os_pv,a.os_bd,a.os_pv_r,a.os_bd_r,a.lens_number,a.recipe_type as prescription_type')
            ->join(['fa_work_order_measure' => 'b'], 'a.measure_id=b.id')
            ->where([
                'a.change_type' => 2,
                'a.item_order_number' => $item_order_number,
                'b.operation_type' => 1
            ])
            ->order('a.id', 'desc')
            ->find();
        if ($change_lens) {
            $change_lens = $change_lens->toArray();

            //处理双pd
            if ($change_lens['pd_l'] && $change_lens['pd_r']) {
                $change_lens['pdcheck'] = 'on';
                $change_lens['pd'] = '';
            } else {
                $change_lens['pdcheck'] = '';
                $change_lens['pd'] = $change_lens['pd_r'] ?: $change_lens['pd_l'];
            }

            //处理斜视值
            if ($change_lens['od_pv'] || $change_lens['os_pv']) {
                $change_lens['prismcheck'] = 'on';
            } else {
                $change_lens['prismcheck'] = '';
            }

            $option_info = array_merge($option_info, $change_lens);
        }

        //获取镜片名称
        $lens_name = '';
        if ($option_info['lens_number']) {
            //获取镜片编码及名称
            $lens_list = $this->_lens_data->column('lens_name', 'lens_number');
            $lens_name = $lens_list[$option_info['lens_number']];
        }
        $option_info['lens_name'] = $lens_name;

        //异常原因列表
        $abnormal_arr = [
            2 => [
                ['id' => 1, 'name' => '缺货'],
                ['id' => 2, 'name' => '商品条码贴错'],
            ],
            3 => [
                ['id' => 3, 'name' => '核实处方'],
                ['id' => 4, 'name' => '镜片缺货'],
                ['id' => 5, 'name' => '镜片重做'],
                ['id' => 6, 'name' => '定制片超时']
            ],
            4 => [
                ['id' => 7, 'name' => '不可加工'],
                ['id' => 8, 'name' => '镜架加工报损'],
                ['id' => 9, 'name' => '镜片加工报损']
            ],
            5 => [
                ['id' => 10, 'name' => 'logo不可加工'],
                ['id' => 11, 'name' => '镜架印logo报损']
            ],
            6 => [
                ['id' => 1, 'name' => '加工调整'],
                ['id' => 2, 'name' => '镜架报损'],
                ['id' => 3, 'name' => '镜片报损'],
                ['id' => 4, 'name' => 'logo调整']
            ],
            7 => [
                ['id' => 12, 'name' => '缺货']
            ]
        ];
        $abnormal_list = $abnormal_arr[$check_status] ?? [];

        //配镜片：判断定制片
        if (3 == $check_status) {
            //判断定制片暂存
            $msg = '';
            $second = 0;
            if (0 < $item_process_info['temporary_house_id']) {
                //获取库位号，有暂存库位号，是第二次扫描，返回展示取出按钮
                $coding = $this->_stock_house
                    ->where(['id' => $item_process_info['temporary_house_id']])
                    ->value('coding');
                $second = 1; //是第二次扫描
                // $msg = "请将子单号{$item_order_number}的商品从定制片暂存架{$coding}库位取出";
                $msg = "请放在暂存架"."\n"."{$coding}";
            } else {
                //判断是否定制片且未处理状态
                if (0 == $item_process_info['customize_status'] && 3 == $item_process_info['order_prescription_type']) {
                    //暂存自动分配库位
                    $stock_house_info = $this->_stock_house
                        ->field('id,coding')
                        ->where(['status' => 1, 'type' => 3, 'occupy' => ['<', 10000]])
                        ->order('occupy', 'desc')
                        ->find();
                    if (!empty($stock_house_info)) {
                        $this->_stock_house->startTrans();
                        $this->_new_order_item_process->startTrans();
                        try {
                            //子订单绑定定制片库位号
                            $this->_new_order_item_process
                                ->allowField(true)
                                ->isUpdate(true, ['item_order_number' => $item_order_number])
                                ->save(['temporary_house_id' => $stock_house_info['id'], 'customize_status' => 1]);

                            //定制片库位号占用数量+1
                            $this->_stock_house
                                ->where(['id' => $stock_house_info['id']])
                                ->setInc('occupy', 1);
                            $coding = $stock_house_info['coding'];

                            //定制片提示库位号信息
                            if ($coding) {
                                DistributionLog::record($this->auth, $item_process_info['id'], 0, "子单号{$item_order_number}，定制片库位号：{$coding}");

                                $second = 0; //是第一次扫描
                                // $msg = "请将子单号{$item_order_number}的商品放入定制片暂存架{$coding}库位";
                                $msg = "请放在暂存架"."\n"."{$coding}";
                            }

                            $this->_stock_house->commit();
                            $this->_new_order_item_process->commit();
                        } catch (ValidateException $e) {
                            $this->_stock_house->rollback();
                            $this->_new_order_item_process->rollback();
                            $this->error($e->getMessage(), [], 406);
                        } catch (PDOException $e) {
                            $this->_stock_house->rollback();
                            $this->_new_order_item_process->rollback();
                            $this->error($e->getMessage(), [], 407);
                        } catch (Exception $e) {
                            $this->_stock_house->rollback();
                            $this->_new_order_item_process->rollback();
                            $this->error($e->getMessage(), [], 408);
                        }
                    } else {
                        DistributionLog::record($this->auth, $item_process_info['id'], 0, '定制片暂存架没有空余库位');
                        $this->error(__('定制片暂存架没有空余库位，请及时处理'), [], 405);
                    }
                }
            }

            //定制片提示库位号信息
            if ($coding) {
                $this->success($msg, ['abnormal_list' => $abnormal_list, 'option_info' => $option_info, 'second' => $second], 200);
            }
        }

        //配货返回数据
        if (7 == $check_status) {
            //获取子订单处方数据
            return $abnormal_list;
        }

        $this->success('', ['abnormal_list' => $abnormal_list, 'option_info' => $option_info], 200);
    }

    /**
     * 提交操作（配货通用）
     *
     * @param string $item_order_number 子订单号
     * @param int $check_status 检测状态
     * @author lzh
     * @return mixed
     */
    protected function save($item_order_number, $check_status)
    {
        empty($item_order_number) && $this->error(__('子订单号不能为空'), [], 403);

        //获取子订单数据
        $item_process_info = $this->_new_order_item_process
            ->field('id,distribution_status,order_prescription_type,option_id,order_id,site,customize_status')
            ->where('item_order_number', $item_order_number)
            ->find();

        //获取子订单处方数据
        $item_option_info = $this->_new_order_item_option
            ->field('is_print_logo,sku,index_name')
            ->where('id', $item_process_info['option_id'])
            ->find();

        //状态类型
        $status_arr = [
            2 => '配货',
            3 => '配镜片',
            4 => '加工',
            5 => '印logo',
            6 => '成品质检'
        ];

        //操作失败记录
        if (empty($item_process_info)) {
            DistributionLog::record($this->auth, $item_process_info['id'], 0, $status_arr[$check_status] . '：子订单不存在');
            $this->error(__('子订单不存在'), [], 403);
        }
        //扫码配镜片，定制片 先取出 暂存库位才可操作
        if (3 == $check_status && 3 == $item_process_info['order_prescription_type'] && 1 == $item_process_info['customize_status']) {
            $this->error(__('请先将定制片从暂存库位取出'), [], 405);
        }

        //操作失败记录
        if ($check_status != $item_process_info['distribution_status']) {
            DistributionLog::record($this->auth, $item_process_info['id'], 0, $status_arr[$check_status] . '：当前状态[' . $status_arr[$item_process_info['distribution_status']] . ']无法操作');
            $this->error(__('当前状态无法操作'), [], 405);
        }

        //检测异常状态
        $abnormal_id = $this->_distribution_abnormal
            ->where(['item_process_id' => $item_process_info['id'], 'status' => 1])
            ->value('id');

        //操作失败记录
        if ($abnormal_id) {
            DistributionLog::record($this->auth, $item_process_info['id'], 0, $status_arr[$check_status] . '：有异常[' . $abnormal_id . ']待处理不可操作');
            // $this->error(__('有异常待处理无法操作'), [], 405);
            $this->error(__('子订单存在异常A-01-01'), [], 405);
        }

        //查询订单号
        $order_info = $this->_new_order
            ->field('increment_id,status')
            ->where(['id' => $item_process_info['order_id']])
            ->find();
        // 'processing' != $order_info['status'] && $this->error(__('当前订单状态不可操作'), [], 405);
        'processing' != $order_info['status'] && $this->error(__('订单状态异常'), [], 405);
        $increment_id = $order_info['increment_id'];

        //检测是否有工单未处理
        $check_work_order = $this->_work_order_measure
            ->alias('a')
            ->field('a.item_order_number,a.measure_choose_id')
            ->join(['fa_work_order_list' => 'b'], 'a.work_id=b.id')
            ->where([
                'a.operation_type' => 0,
                'b.platform_order' => $increment_id,
                'b.work_status' => ['in', [1, 2, 3, 5]]
            ])
            ->select();
        if ($check_work_order) {
            foreach ($check_work_order as $val) {
                (3 == $val['measure_choose_id'] //主单取消措施未处理
                    ||
                    $val['item_order_number'] == $item_order_number //子单措施未处理:更改镜框18、更改镜片19、取消20
                )

                // && $this->error(__('有工单未处理，无法操作'), [], 405);
                && $this->error(__('子订单存在工单A-01-01'), [], 405);

                if ($val['measure_choose_id'] == 21){
                    $this->error(__('有工单存在暂缓措施未处理，无法操作'), [], 405);
                }
            }
        }

        //获取订单购买总数，计算过滤掉取消状态的子单
        $total_qty_ordered = $this->_new_order_item_process
            ->where(['order_id' => $item_process_info['order_id'], 'distribution_status' => ['neq', 0]])
            ->count();
        $back_msg = '';

        $res = false;
        $this->_item->startTrans();
        $this->_stock_log->startTrans();
        $this->_new_order_process->startTrans();
        $this->_new_order_item_process->startTrans();
        $this->_product_bar_code_item->startTrans();
        try {
            //下一步提示信息及状态
            if (2 == $check_status) {

                /**************工单更换镜框******************/
                //查询更改镜框最新信息
                $change_sku = $this->_work_order_change_sku
                    ->alias('a')
                    ->join(['fa_work_order_measure' => 'b'], 'a.measure_id=b.id')
                    ->where([
                        'a.change_type' => 1,
                        'a.item_order_number' => $item_order_number,
                        'b.operation_type' => 1
                    ])
                    ->order('a.id', 'desc')
                    ->value('a.change_sku');
                if ($change_sku) {
                    $item_option_info['sku'] = $change_sku;
                }
                /********************************/

                //配货节点 条形码绑定子单号
                $barcode = $this->request->request('barcode');
                $this->_product_bar_code_item
                    ->allowField(true)
                    ->isUpdate(true, ['code' => $barcode])
                    ->save(['item_order_number' => $item_order_number]);

                //获取true_sku
                $true_sku = $this->_item_platform_sku->getTrueSku($item_option_info['sku'], $item_process_info['site']);

                //获取配货占用库存
                $item_before = $this->_item
                    ->field('distribution_occupy_stock')
                    ->where(['sku' => $true_sku])
                    ->find();

                //增加配货占用库存
                $this->_item
                    ->where(['sku' => $true_sku])
                    ->inc('distribution_occupy_stock', 1)
                    ->update();

                //记录库存日志
                $this->_stock_log->setData([
                    'type' => 2,
                    'site' => $item_process_info['site'],
                    'modular' => 2,
                    'change_type' => 4,
                    'source' => 2,
                    'sku' => $true_sku,
                    'number_type' => 2,
                    'order_number' => $item_order_number,
                    'distribution_stock_before' => $item_before['distribution_occupy_stock'],
                    'distribution_stock_change' => 1,
                    'create_person' => $this->auth->nickname,
                    'create_time' => time()
                ]);

                //根据处方类型字段order_prescription_type(现货处方镜、定制处方镜)判断是否需要配镜片
                if (in_array($item_process_info['order_prescription_type'], [2, 3])) {
                    $save_status = 3;
                } else {
                    if ($item_option_info['is_print_logo']) {
                        $save_status = 5;
                    } else {
                        if ($total_qty_ordered > 1) {
                            $save_status = 7;
                        } else {
                            $save_status = 9;
                        }
                    }
                }
            } elseif (3 == $check_status) {
                $save_status = 4;
            } elseif (4 == $check_status) {
                if ($item_option_info['is_print_logo']) {
                    $save_status = 5;
                } else {
                    $save_status = 6;
                }
            } elseif (5 == $check_status) {
                $save_status = 6;
            } elseif (6 == $check_status) {
                if ($total_qty_ordered > 1) {
                    $save_status = 7;
                } else {
                    $save_status = 9;
                }
            }

            if (empty($save_status)) throw new Exception('未获取到下一步状态');

            //订单主表标记已合单
            if (9 == $save_status) {
                //主订单状态表
                $this->_new_order_process
                    ->allowField(true)
                    ->isUpdate(true, ['order_id' => $item_process_info['order_id']])
                    ->save(['combine_status' => 1, 'combine_time' => time()]);
            }

            //更新子单配货状态
            $res = $this->_new_order_item_process
                ->allowField(true)
                ->isUpdate(true, ['item_order_number' => $item_order_number])
                ->save(['distribution_status' => $save_status]);

            //下一步提示信息
            if (3 == $save_status) {
                //待配镜片
                $back_msg = 2 == $item_process_info['order_prescription_type'] ? '去配现片' : '去配定制片';
            } else {
                $next_step = [
                    4 => '去加工',
                    5 => '印logo',
                    6 => '去质检',
                    7 => '去合单',
                    9 => '去合单'
                ];
                $back_msg = $next_step[$save_status];
            }

            $this->_item->commit();
            $this->_stock_log->commit();
            $this->_new_order_process->commit();
            $this->_new_order_item_process->commit();
            $this->_product_bar_code_item->commit();
        } catch (ValidateException $e) {
            $this->_item->rollback();
            $this->_stock_log->rollback();
            $this->_new_order_process->rollback();
            $this->_new_order_item_process->rollback();
            $this->_product_bar_code_item->rollback();
            $this->error($e->getMessage(), [], 406);
        } catch (PDOException $e) {
            $this->_item->rollback();
            $this->_stock_log->rollback();
            $this->_new_order_process->rollback();
            $this->_new_order_item_process->rollback();
            $this->_product_bar_code_item->rollback();
            $this->error($e->getMessage(), [], 407);
        } catch (Exception $e) {
            $this->_item->rollback();
            $this->_stock_log->rollback();
            $this->_new_order_process->rollback();
            $this->_new_order_item_process->rollback();
            $this->_product_bar_code_item->rollback();
            $this->error($e->getMessage(), [], 408);
        }

        if ($res) {
            //操作成功记录
            DistributionLog::record($this->auth, $item_process_info['id'], $check_status, $status_arr[$check_status] . '完成');
            if (9 == $save_status) {
                DistributionLog::record($this->auth, $item_process_info['id'], 7,  '合单完成');
            }
            //成功返回
            $this->success($back_msg, [], 200);
        } else {
            //操作失败记录
            DistributionLog::record($this->auth, $item_process_info['id'], 0, $status_arr[$check_status] . '：保存失败');

            //失败返回
            $this->error(__($status_arr[$check_status] . '失败'), [], 404);
        }
    }

    /**
     * 配货扫码
     *
     * @参数 string item_order_number  子订单号
     * @author wgj
     * @return mixed
     */
    public function product()
    {
        $item_order_number = $this->request->request('item_order_number');
        $this->info($item_order_number, 2);
    }

    /**
     * 配货提交--ok
     *
     * 商品条形码与商品SKU是多对一关系，paltform_sku与SKU（true_sku）也是多对一关系
     *
     * @参数 string item_order_number  子订单号
     * @参数 string barcode  商品条形码
     * @author wgj
     * @return mixed
     */
    public function product_submit()
    {
        $item_order_number = $this->request->request('item_order_number');
        $barcode = $this->request->request('barcode');
        empty($item_order_number) && $this->error(__('子订单号不能为空'), [], 403);
        empty($barcode) && $this->error(__('商品条形码不能为空'), [], 403);

        //子订单号获取平台platform_sku
        $order_item_id = $this->_new_order_item_process->where('item_order_number', $item_order_number)->value('id');
        empty($order_item_id) && $this->error(__('订单不存在'), [], 403);

        //获取子单sku
        $order_item_info = $this->_new_order_item_process
            ->field('sku,site')
            ->where('item_order_number', $item_order_number)
            ->find();
        $order_item_true_sku = $order_item_info['sku'];

        //获取更改镜框最新信息
        $change_sku = $this->_work_order_change_sku
            ->alias('a')
            ->join(['fa_work_order_measure' => 'b'], 'a.measure_id=b.id')
            ->where([
                'a.change_type' => 1,
                'a.item_order_number' => $item_order_number,
                'b.operation_type' => 1
            ])
            ->order('a.id', 'desc')
            ->value('a.change_sku');
        if ($change_sku) {
            $order_item_true_sku = $change_sku;
        }

        $sku_arr = explode('-', $order_item_true_sku);
        if (2 < count($sku_arr)) {
            $order_item_true_sku = $sku_arr[0] . '-' . $sku_arr[1];
        }

        //获取仓库sku
        $true_sku = $this->_item_platform_sku
            ->where(['platform_sku' => $order_item_true_sku, 'platform_type' => $order_item_info['site']])
            ->value('sku');

        $barcode_item_order_number = $this->_product_bar_code_item->where('code', $barcode)->value('item_order_number');
        !empty($barcode_item_order_number) && $this->error(__('此条形码已经绑定过其他订单'), [], 403);
        $code_item_sku = $this->_product_bar_code_item->where('code', $barcode)->value('sku');
        // empty($code_item_sku) && $this->error(__('此条形码未绑定SKU'), [], 403);
        empty($code_item_sku) && $this->error(__('商品条码没有绑定关系'), [], 403);

        if (strtolower($true_sku) != strtolower($code_item_sku)) {
            //扫描获取的条形码 和 子订单查询出的 SKU(即true_sku)对比失败则配货失败
            //操作失败记录
            DistributionLog::record($this->auth, $order_item_id, 2, '配货失败：sku配错');

            //失败返回
            $this->error(__('sku配错'), [], 404);
        } else {
            $this->save($item_order_number, 2);
        }
    }

    /**
     * 镜片分拣--不做分页，只展示processing状态的订单
     *
     * @参数 string start_time  开始时间
     * @参数 string end_time  结束时间
     * @参数 int page  页码
     * @参数 int page_size  每页显示数量
     * @author wgj
     * @return mixed
     */
    public function sorting()
    {
        $start_time = $this->request->request('start_time');
        $end_time = $this->request->request('end_time');
        $page = $this->request->request('page');
        $page_size = $this->request->request('page_size');
        empty($page) && $this->error(__('Page can not be empty'), [], 403);
        empty($page_size) && $this->error(__('Page size can not be empty'), [], 403);
        $where = [
            'a.distribution_status' => 3,
            'd.status' => 'processing',
            'b.index_name' => ['neq', ''],
            'a.order_prescription_type' => ['neq', 3],
        ];
        if ($start_time && $end_time) {
            $where['a.created_at'] = ['between', [strtotime($start_time), strtotime($end_time)]];
        }
        $offset = ($page - 1) * $page_size;
        $limit = $page_size;

        //获取出库单列表数据【od右眼、os左眼分开查询再组装，order_prescription_type 处方类型，index_name 镜片类型，lens_name 镜片名称】
        //od右镜片
        $list_od = $this->_new_order_item_process
            ->alias('a')
            ->where($where)
            ->field('count(*) as all_count,a.order_prescription_type,b.index_name,b.od_sph,b.od_cyl,c.lens_name')
            ->join(['fa_order_item_option' => 'b'], 'a.option_id=b.id')
            ->join(['fa_lens_data' => 'c'], 'b.lens_number=c.lens_number')
            ->join(['fa_order' => 'd'], 'a.order_id=d.id')
            ->group('a.order_prescription_type,b.lens_number,b.od_sph,b.od_cyl')
            //            ->limit($offset, $limit)
            ->select();
        $list_od = collection($list_od)->toArray();
        //订单处方分类 0待处理  1 仅镜架 2 现货处方镜 3 定制处方镜 4 其他
        $order_prescription_type = [0 => '待处理', 1 => '仅镜架', 2 => '现货处方镜', 3 => '定制处方镜', 4 => '其他'];
        foreach ($list_od as $key => $value) {
            $list_od[$key]['order_prescription_type'] = $order_prescription_type[$value['order_prescription_type']];
            $list_od[$key]['light'] = 'SPH：' . $value['od_sph'] . ' CYL:' . $value['od_cyl'];

            unset($list_od[$key]['od_sph']);
            unset($list_od[$key]['od_cyl']);
        }

        //os左镜片
        $list_os = $this->_new_order_item_process
            ->alias('a')
            ->where($where)
            ->field('count(*) as all_count,a.order_prescription_type,b.os_sph,b.os_cyl,c.lens_name')
            ->join(['fa_order_item_option' => 'b'], 'a.option_id=b.id')
            ->join(['fa_lens_data' => 'c'], 'b.lens_number=c.lens_number')
            ->join(['fa_order' => 'd'], 'a.order_id=d.id')
            ->group('a.order_prescription_type,b.lens_number,b.os_sph,b.os_cyl')
            //            ->limit($offset, $limit)
            ->select();
        $list_os = collection($list_os)->toArray();
        //订单处方分类 0待处理  1 仅镜架 2 现货处方镜 3 定制处方镜 4 其他
        $order_prescription_type = [0 => '待处理', 1 => '仅镜架', 2 => '现货处方镜', 3 => '定制处方镜', 4 => '其他'];
        foreach ($list_os as $key => $value) {
            $list_os[$key]['order_prescription_type'] = $order_prescription_type[$value['order_prescription_type']];
            $list_os[$key]['light'] = 'SPH：' . $value['os_sph'] . ' CYL:' . $value['os_cyl'];

            unset($list_os[$key]['os_sph']);
            unset($list_os[$key]['os_cyl']);
        }

        //左右镜片数组取交集求all_count和，再合并
        foreach ($list_os as $key => $value) {
            foreach ($list_od as $k => $v) {
                if ($value['light'] == $v['light']) {
                    $list_od[$k]['all_count'] = $value['all_count'] + $v['all_count'];
                    unset($list_os[$key]);
                }
            }
        }
        $list = array_merge($list_od, $list_os);
        $list = array_values($list);
        $this->success('', ['list' => $list], 200);
    }

    /**
     * 镜片未分拣数量
     *
     * @author wgj
     * @return mixed
     */
    public function no_sorting()
    {
        $where = [
            'a.distribution_status' => 3,
            'a.order_prescription_type' => 2,
            'b.status' => 'processing',
        ];

        //未分拣子订单数量
        $count = $this->_new_order_item_process
            ->alias('a')
            ->join(['fa_order' => 'b'], 'a.order_id=b.id')
            ->where($where)
            ->count();
        return 2 * $count;
    }

    /**
     * 配镜片扫码
     *
     * @参数 string item_order_number  子订单号
     * @author lzh
     * @return mixed
     */
    public function lens()
    {
        $item_order_number = $this->request->request('item_order_number');
        $this->info($item_order_number, 3);
    }

    /**
     * 配镜片二次扫码取出--释放子单号占用的暂存库位，记录日志
     *
     * @参数 string item_order_number  子订单号
     * @author wgj
     * @return mixed
     */
    public function lens_out()
    {
        $item_order_number = $this->request->request('item_order_number');
        //获取子订单数据
        $item_process_info = $this->_new_order_item_process
            ->where('item_order_number', $item_order_number)
            ->field('id,option_id,distribution_status,temporary_house_id,order_prescription_type')
            ->find();
        empty($item_process_info) && $this->error(__('子订单不存在'), [], 403);
        empty($item_process_info['temporary_house_id']) && $this->error(__('子订单未绑定暂存库位'), [], 403);
        3 != $item_process_info['distribution_status'] && $this->error(__('只有待配镜片状态才能操作'), [], 403);
        //获取库位号
        $coding = $this->_stock_house
            ->where(['id' => $item_process_info['temporary_house_id']])
            ->value('coding');
        //子订单释放定制片库位号
        $result = $this->_new_order_item_process
            ->allowField(true)
            ->isUpdate(true, ['item_order_number' => $item_order_number])
            ->save(['temporary_house_id' => 0, 'customize_status' => 2]);

        $res = false;
        if ($result != false) {
            //定制片库位占用数量-1
            $res = $this->_stock_house
                ->where(['id' => $item_process_info['temporary_house_id']])
                ->setDec('occupy', 1);
            DistributionLog::record($this->auth, $item_process_info['id'], 0, "子单号{$item_order_number}，释放定制片库位号：{$coding}");
        }

        if (false === $res) {
            $this->error(__("取出失败"), [], 403);
        }
        // $this->success("子单号{$item_order_number}的商品从定制片暂存架{$coding}库位取出成功", [], 200);
        $this->success("{$coding}".""."是否将商品取出", [], 200);
    }

    /**
     * 配镜片提交
     *
     * @参数 string item_order_number  子订单号
     * @author lzh
     * @return mixed
     */
    public function lens_submit()
    {
        $item_order_number = $this->request->request('item_order_number');
        $this->save($item_order_number, 3);
    }

    /**
     * 加工扫码
     *
     * @参数 string item_order_number  子订单号
     * @author lzh
     * @return mixed
     */
    public function machining()
    {
        $item_order_number = $this->request->request('item_order_number');
        $this->info($item_order_number, 4);
    }

    /**
     * 加工提交
     *
     * @参数 string item_order_number  子订单号
     * @author lzh
     * @return mixed
     */
    public function machining_submit()
    {
        $item_order_number = $this->request->request('item_order_number');
        $this->save($item_order_number, 4);
    }

    /**
     * 印logo扫码
     *
     * @参数 string item_order_number  子订单号
     * @author lzh
     * @return mixed
     */
    public function logo()
    {
        $item_order_number = $this->request->request('item_order_number');
        $this->info($item_order_number, 5);
    }

    /**
     * 印logo提交
     *
     * @参数 string item_order_number  子订单号
     * @author lzh
     * @return mixed
     */
    public function logo_submit()
    {
        $item_order_number = $this->request->request('item_order_number');
        $this->save($item_order_number, 5);
    }

    /**
     * 成品质检扫码
     *
     * @参数 string item_order_number  子订单号
     * @author lzh
     * @return mixed
     */
    public function finish()
    {
        $item_order_number = $this->request->request('item_order_number');
        $this->info($item_order_number, 6);
    }

    /**
     * 质检通过/拒绝
     *
     * @参数 string item_order_number  子订单号
     * @参数 int do_type  操作类型：1通过，2拒绝
     * @参数 int reason  拒绝原因
     * @author lzh
     * @return mixed
     */
    public function finish_adopt()
    {
        $item_order_number = $this->request->request('item_order_number');
        $do_type = $this->request->request('do_type');
        !in_array($do_type, [1, 2]) && $this->error(__('操作类型错误'), [], 403);

        if ($do_type == 1) {
            $this->save($item_order_number, 6);
        } else {
            $reason = $this->request->request('reason');
            !in_array($reason, [1, 2, 3, 4]) && $this->error(__('拒绝原因错误'), [], 403);

            //获取子订单数据
            $item_process_info = $this->_new_order_item_process
                ->where('item_order_number', $item_order_number)
                ->field('id,option_id,order_id,sku,site')
                ->find();

            //状态
            $status_arr = [
                1 => ['status' => 4, 'name' => '质检拒绝：加工调整'],
                2 => ['status' => 2, 'name' => '质检拒绝：镜架报损'],
                3 => ['status' => 3, 'name' => '质检拒绝：镜片报损'],
                4 => ['status' => 5, 'name' => '质检拒绝：logo调整']
            ];
            $status = $status_arr[$reason]['status'];

            $this->_new_order_item_process->startTrans();
            $this->_item->startTrans();
            $this->_item_platform_sku->startTrans();
            $this->_stock_log->startTrans();
            $this->_product_bar_code_item->startTrans();
            try {
                $save_data['distribution_status'] = $status;
                //如果回退到待加工步骤之前，清空定制片库位ID及定制片处理状态
                if (4 > $status) {
                    $save_data['temporary_house_id'] = 0;
                    $save_data['customize_status'] = 0;
                }

                //子订单状态回退
                $this->_new_order_item_process
                    ->allowField(true)
                    ->isUpdate(true, ['id' => $item_process_info['id']])
                    ->save($save_data);

                //回退到待配货，解绑条形码
                if (2 == $status) {
                    $this->_product_bar_code_item
                        ->allowField(true)
                        ->isUpdate(true, ['item_order_number' => $item_order_number])
                        ->save(['item_order_number' => '']);
                }

                //质检拒绝：镜架报损，扣减可用库存、配货占用、总库存、虚拟仓库存
                if (2 == $reason) {
                    //仓库sku、库存
                    $platform_info = $this->_item_platform_sku
                        ->field('sku,stock')
                        ->where(['platform_sku' => $item_process_info['sku'], 'platform_type' => $item_process_info['site']])
                        ->find();
                    $true_sku = $platform_info['sku'];

                    //检验库存
                    $stock_arr = $this->_item
                        ->where(['sku' => $true_sku])
                        ->field('stock,available_stock,distribution_occupy_stock')
                        ->find();

                    //扣减可用库存、配货占用、总库存
                    $this->_item
                        ->where(['sku' => $true_sku])
                        ->dec('available_stock', 1)
                        ->dec('distribution_occupy_stock', 1)
                        ->dec('stock', 1)
                        ->update();

                    //扣减虚拟仓库存
                    $this->_item_platform_sku
                        ->where(['sku' => $true_sku, 'platform_type' => $item_process_info['site']])
                        ->dec('stock', 1)
                        ->update();

                    //记录库存日志
                    $this->_stock_log->setData([
                        'type' => 2,
                        'site' => $item_process_info['site'],
                        'modular' => 3,
                        'change_type' => 5,
                        'source' => 2,
                        'sku' => $true_sku,
                        'number_type' => 2,
                        'order_number' => $item_order_number,
                        'available_stock_before' => $stock_arr['available_stock'],
                        'available_stock_change' => -1,
                        'distribution_stock_before' => $stock_arr['distribution_occupy_stock'],
                        'distribution_stock_change' => -1,
                        'stock_before' => $stock_arr['stock'],
                        'stock_change' => -1,
                        'fictitious_before' => $platform_info['stock'],
                        'fictitious_change' => -1,
                        'create_person' => $this->auth->nickname,
                        'create_time' => time()
                    ]);
                }

                //记录日志
                DistributionLog::record($this->auth, $item_process_info['id'], 6, $status_arr[$reason]['name']);

                $this->_new_order_item_process->commit();
                $this->_item->commit();
                $this->_item_platform_sku->commit();
                $this->_stock_log->commit();
                $this->_product_bar_code_item->commit();
            } catch (ValidateException $e) {
                $this->_new_order_item_process->rollback();
                $this->_item->rollback();
                $this->_item_platform_sku->rollback();
                $this->_stock_log->rollback();
                $this->_product_bar_code_item->rollback();
                $this->error($e->getMessage(), [], 406);
            } catch (PDOException $e) {
                $this->_new_order_item_process->rollback();
                $this->_item->rollback();
                $this->_item_platform_sku->rollback();
                $this->_stock_log->rollback();
                $this->_product_bar_code_item->rollback();
                $this->error($e->getMessage(), [], 407);
            } catch (Exception $e) {
                $this->_new_order_item_process->rollback();
                $this->_item->rollback();
                $this->_item_platform_sku->rollback();
                $this->_stock_log->rollback();
                $this->_product_bar_code_item->rollback();
                $this->error($e->getMessage(), [], 408);
            }
            $this->success('操作成功', [], 200);
        }
    }

    /**
     * 合单扫描子单号--ok---修改合单主表改为order_proceess表
     *
     * @参数 string item_order_number  子订单号
     * @author wgj
     * @return mixed
     */
    public function merge()
    {
        $item_order_number = $this->request->request('item_order_number');
        empty($item_order_number) && $this->error(__('子订单号不能为空'), [], 403);

        //获取子订单数据
        $item_process_info = $this->_new_order_item_process
            ->where('item_order_number', $item_order_number)
            ->field('id,distribution_status,sku,order_id,temporary_house_id,abnormal_house_id')
            ->find();
        empty($item_process_info) && $this->error(__('子订单不存在'), [], 403);
        9 == $item_process_info['distribution_status'] && $this->error(__('订单合单完成，去审单！'), [], 403);
        !in_array($item_process_info['distribution_status'], [7, 8]) && $this->error(__('子订单当前状态不可合单操作'), [], 403);

        //查询订单号
        $order_info = $this->_new_order
            ->field('increment_id,status')
            ->where(['id' => $item_process_info['order_id']])
            ->find();
        'processing' != $order_info['status'] && $this->error(__('订单状态异常'), [], 405);

        //检测是否有工单未处理
        $check_work_order = $this->_work_order_measure
            ->alias('a')
            ->field('a.item_order_number,a.measure_choose_id')
            ->join(['fa_work_order_list' => 'b'], 'a.work_id=b.id')
            ->where([
                'a.operation_type' => 0,
                'b.platform_order' => $order_info['increment_id'],
                'b.work_status' => ['in', [1, 2, 3, 5]]
            ])
            ->select();
        if ($check_work_order) {
            foreach ($check_work_order as $val) {
                (3 == $val['measure_choose_id'] //主单取消措施未处理
                    ||
                    $val['item_order_number'] == $item_order_number //子单措施未处理:更改镜框18、更改镜片19、取消20
                )

                // && $this->error(__('有工单未处理，无法操作'), [], 405);
                && $this->error(__('子订单存在工单A-01-01'), [], 405);
                if ($val['measure_choose_id'] == 21){
                    $this->error(__('子订单存在工单A-01-01'), [], 405);
                }
            }
        }

        //判断异常状态
        $abnormal_id = $this->_distribution_abnormal
            ->where(['item_process_id' => $item_process_info['id'], 'status' => 1])
            ->value('id');
        // $abnormal_id && $this->error(__('有异常待处理，无法操作'), [], 405);
        $abnormal_id && $this->error(__('子订单存在异常A-01-01'), [], 405);




        $order_process_info = $this->_new_order_process
            ->where('order_id', $item_process_info['order_id'])
            ->field('order_id,store_house_id')
            ->find();

        //第二次扫描提示语
        if (7 < $item_process_info['distribution_status']) {
            if ($order_process_info['store_house_id']) {
                //有主单合单库位
                $store_house_info = $this->_stock_house->field('id,coding,subarea')->where('id', $order_process_info['store_house_id'])->find();
                // $this->error(__('请将子单号' . $item_order_number . '的商品放入合单架' . $store_house_info['coding'] . '合单库位'), [], 403);
                $this->error(__('请放在合单架' ."\n". $store_house_info['coding']), [], 403);
            } else {
                //                $this->_new_order_item_process->allowField(true)->isUpdate(true, ['item_order_number'=>$item_order_number])->save(['distribution_status'=>7]);
                $this->error(__('合单失败，主单未分配合单库位'), [], 403);
            }
        }

        //查询预库位占用
        $fictitious_time = time();
        $fictitious_store_house_info = $this->_stock_house->field('id,coding,subarea')->where(['status' => 1, 'type' => 2, 'occupy' => 0, 'fictitious_occupy_time' => ['>',$fictitious_time], 'order_id' => $item_process_info['order_id']])->find();

        //如果预占用信息不为空。返回预占用库位
        if (empty($fictitious_store_house_info)) {

            //未合单，首次扫描
            if (!$order_process_info['store_house_id']) {
                //主单中无库位号，首个子单进入时，分配一个合单库位给PDA，暂不占用根据是否确认放入合单架占用或取消
                $store_house_info = $this->_stock_house->field('id,coding,subarea')->where(['status' => 1, 'type' => 2, 'occupy' => 0,  'fictitious_occupy_time' => ['<',$fictitious_time]])->find();
                empty($store_house_info) && $this->error(__('合单失败，合单库位已用完，请添加后再操作'), [], 403);
                //绑定预占用库存和有效时间
                $this->_stock_house->where(['id' => $store_house_info['id']])->update(['fictitious_occupy_time' => $fictitious_time+600,'order_id' => $item_process_info['order_id']]);
            } else {
                //主单已绑定合单库位,根据ID查询库位信息
                $store_house_info = $this->_stock_house->field('id,coding,subarea')->where('id', $order_process_info['store_house_id'])->find();
            }
        }else{
            $store_house_info = $fictitious_store_house_info;
        }

        //异常原因列表
        $abnormal_list = [
            ['id' => 12, 'name' => '缺货']
        ];

        $info = [
            'item_order_number' => $item_order_number,
            'sku' => $item_process_info['sku'],
            'store_id' => $store_house_info['id'],
            'coding' => $store_house_info['coding'],
            'abnormal_list' => $abnormal_list
        ];

        $this->success('', ['info' => $info], 200);
    }

    /**
     * 合单--确认放入合单架---最后一个子单扫描合单时，检查子单合单是否有异常，无异常且全部为已合单，则更新主单合单状态和时间--ok
     * 合单库位预先分配，若被占用则提示被占用并分配新合单库位
     *
     * @参数 string item_order_number  子订单号
     * @参数 string store_house_id  合单库位ID
     * @author wgj
     * @return mixed
     */
    public function merge_submit()
    {
        $item_order_number = $this->request->request('item_order_number');
        $store_house_id = $this->request->request('store_house_id');
        empty($item_order_number) && $this->error(__('子订单号不能为空'), [], 403);
        empty($store_house_id) && $this->error(__('合单库位号不能为空'), [], 403);

        //获取子订单数据
        $item_process_info = $this->_new_order_item_process
            ->where('item_order_number', $item_order_number)
            ->field('id,distribution_status,order_id')
            ->find();
        empty($item_process_info) && $this->error(__('子订单不存在'), [], 403);
        !in_array($item_process_info['distribution_status'], [7, 8]) && $this->error(__('子订单当前状态不可合单操作'), [], 403);

        //查询主单数据
        $order_process_info = $this->_new_order
            ->alias('a')
            ->where('a.id', $item_process_info['order_id'])
            ->join(['fa_order_process' => 'b'], 'a.id=b.order_id', 'left')
            ->field('a.id,a.increment_id,b.store_house_id')
            ->find();
        empty($order_process_info) && $this->error(__('主订单不存在'), [], 403);

        //获取库位信息
        $store_house_info = $this->_stock_house->field('id,coding,subarea,occupy,fictitious_occupy_time,order_id')->where('id', $store_house_id)->find();//查询合单库位--占用数量
        empty($store_house_info) && $this->error(__('合单库位不存在'), [], 403);

        if ($order_process_info['store_house_id'] != $store_house_id) {
            if ($store_house_info['occupy'] && empty($order_process_info['store_house_id'])) {
                //主单无绑定库位，且分配的库位被占用，重新分配合单库位后再次提交确认放入新分配合单架
                $new_store_house_info = $this->_stock_house->field('id,coding,subarea')->where(['status' => 1, 'type' => 2, 'occupy' => 0])->find();
                empty($new_store_house_info) && $this->error(__('合单库位已用完，请检查合单库位情况'), [], 403);

                $info['store_id'] = $new_store_house_info['id'];
                $this->error(__('合单架' . $store_house_info['coding'] . '库位已被占用，' . '请将子单号' . $item_order_number . '的商品放入新合单架' . $new_store_house_info['coding'] . '库位'), ['info' => $info], 2001);
            }
        }

        if ($item_process_info['distribution_status'] == 8) {
            //重复扫描子单号--提示语句
            // $this->error(__('请将子单号' . $item_order_number . '的商品放入合单架' . $store_house_info['coding'] . '库位'), [], 511);
            $this->error(__('请放在合单架' ."\n". $store_house_info['coding']), [], 511);
        }

        if (!empty($store_house_info['order_id'])) {
            //检查当前订单和预占用时的订单id是否相同
            if ($store_house_info['order_id'] != $order_process_info['id']) {
                $this->error(__('预占用库位信息错误'), [], 403);
            }
            //检查是否预占用
            if (empty($order_process_info['store_house_id'])) {
               if ($store_house_info['fictitious_occupy_time'] < time()) {
                    $this->error(__('库位预占用超10分钟，请重新操作'), [], 403);
                }    
            }
                 
        }
        

        //主单表有合单库位ID，查询主单商品总数，与子单合单入库计算数量对比
        //获取订单购买总数，计算过滤掉取消状态的子单
        $total_qty_ordered = $this->_new_order_item_process
            ->where(['order_id' => $item_process_info['order_id'], 'distribution_status' => ['neq', 0]])
            ->count();
        $count = $this->_new_order_item_process
            ->where(['distribution_status' => ['in', [0, 8]], 'order_id' => $item_process_info['order_id']])
            ->count();

        $info['order_id'] = $item_process_info['order_id']; //合单确认放入合单架提交 接口返回自带主订单号

        if ($total_qty_ordered > $count + 1) {
            //不是最后一个子单
            $num = '';
            $next = 1; //是否有下一个子单 1有，0没有
        } else {
            //最后一个子单
            $num = '最后一个';
            $next = 0; //是否有下一个子单 1有，0没有
        }
        if ($order_process_info['store_house_id']) {
            //存在合单库位ID，获取合单库位号ID存入
            $info['next'] = $next;
            //更新子单表
            $result = false;
            $result = $this->_new_order_item_process->allowField(true)->isUpdate(true, ['item_order_number' => $item_order_number])->save(['distribution_status' => 8]);
            if ($result !== false) {
                //操作成功记录
                DistributionLog::record($this->auth, $item_process_info['id'], 7, '子单号：' . $item_order_number . '作为主单号' . $order_process_info['increment_id'] . '的' . $num . '子单合单完成，库位' . $store_house_info['coding']);
                if (!$next) {
                    //最后一个子单且合单完成，更新主单、子单状态为合单完成
                    $this->_new_order_item_process
                        ->allowField(true)
                        ->isUpdate(true, ['order_id' => $item_process_info['order_id'], 'distribution_status' => ['neq', 0]])
                        ->save(['distribution_status' => 9]);
                    $this->_new_order_process
                        ->allowField(true)
                        ->isUpdate(true, ['order_id' => $item_process_info['order_id']])
                        ->save(['combine_status' => 1, 'check_status' => 0, 'combine_time' => time()]);
                }

                $this->success('子单号放入合单架成功', ['info' => $info], 200);
            } else {
                //操作失败记录
                DistributionLog::record($this->auth, $item_process_info['id'], 7, '子单号：' . $item_order_number . '作为主单号' . $order_process_info['increment_id'] . '的' . $num . '子单合单失败');

                $this->error(__('No rows were inserted'), [], 511);
            }
        }

        //首个子单进入合单架START
        $result = $return = false;
        $this->_stock_house->startTrans();
        $this->_new_order_process->startTrans();
        $this->_new_order_item_process->startTrans();
        try {
            //更新子单表
            $result = $this->_new_order_item_process->allowField(true)->isUpdate(true, ['item_order_number' => $item_order_number])->save(['distribution_status' => 8]);
            if ($result !== false) {
                $res = $this->_new_order_process->allowField(true)->isUpdate(true, ['order_id' => $item_process_info['order_id']])->save(['store_house_id' => $store_house_id]);
                if ($res !== false) {
                    $return = $this->_stock_house->allowField(true)->isUpdate(true, ['id' => $store_house_id])->save(['occupy' => 1]);
                }
            }
            $this->_stock_house->commit();
            $this->_new_order_process->commit();
            $this->_new_order_item_process->commit();
        } catch (ValidateException $e) {
            $this->_stock_house->rollback();
            $this->_new_order_process->rollback();
            $this->_new_order_item_process->rollback();
            $this->error($e->getMessage(), [], 406);
        } catch (PDOException $e) {
            $this->_stock_house->rollback();
            $this->_new_order_process->rollback();
            $this->_new_order_item_process->rollback();
            $this->error($e->getMessage(), [], 407);
        } catch (Exception $e) {
            $this->_stock_house->rollback();
            $this->_new_order_process->rollback();
            $this->_new_order_item_process->rollback();
            $this->error($e->getMessage(), [], 444);
        }
        if ($return !== false) {
            $info['next'] = $next;
            //操作成功记录
            DistributionLog::record($this->auth, $item_process_info['id'], 7, '子单号：' . $item_order_number . '作为主单号' . $order_process_info['increment_id'] . '的' . $num . '子单合单完成，库位' . $store_house_info['coding']);

            $this->success('子单号放入合单架成功', ['info' => $info], 200);
        } else {
            //操作失败记录
            DistributionLog::record($this->auth, $item_process_info['id'], 7, '子单号：' . $item_order_number . '作为主单号' . $order_process_info['increment_id'] . '的' . $num . '子单合单失败');

            $this->error(__('子单号放入合单架失败'), [], 511);
        }
        //首个子单进入合单架END

    }

    /**
     * 合单--合单完成页面-------修改原型图待定---子单合单状态、异常状态展示--ok
     *
     * @参数 string order_number  主订单号
     * @author wgj
     * @return mixed
     */
    public function order_merge()
    {
        $order_number = $this->request->request('order_number');
        empty($order_number) && $this->error(__('订单号不能为空'), [], 403);

        //获取订单购买总数,商品总数即为子单数量
        $order_process_info = $this->_new_order
            ->alias('a')
            ->where('a.increment_id', $order_number)
            ->join(['fa_order_process' => 'b'], 'a.id=b.order_id', 'left')
            ->field('a.id,a.increment_id,b.store_house_id')
            ->find();
        empty($order_process_info) && $this->error(__('主订单不存在'), [], 403);

        //获取子订单数据
        $item_process_info = $this->_new_order_item_process
            ->where('order_id', $order_process_info['id'])
            ->field('id,item_order_number,distribution_status,abnormal_house_id')
            ->select();
        empty($item_process_info) && $this->error(__('子订单数据异常'), [], 403);

        $distribution_status = [0 => '取消', 1 => '待打印标签', 2 => '待配货', 3 => '待配镜片', 4 => '待加工', 5 => '待印logo', 6 => '待成品质检', 7 => '待合单', 8 => '合单中', 9 => '合单完成'];
        foreach ($item_process_info as $key => $value) {
            $item_process_info[$key]['distribution_status'] = $distribution_status[$value['distribution_status']]; //子单合单状态
            $item_process_info[$key]['abnormal_house_id'] = 0 == $value['abnormal_house_id'] ? '正常' : '异常'; //异常状态
        }
        $info['order_number'] = $order_number;
        $info['list'] = $item_process_info;
        $this->success('', ['info' => $info], 200);
    }

    /**
     * 合单待取列表---ok
     *
     * @参数 string query  查询内容
     * @参数 int type  待取出类型 1 合单 2异常
     * @参数 string start_time  开始时间
     * @参数 string end_time  结束时间
     * @参数 int page  * 页码
     * @参数 int page_size  * 每页显示数量
     * @author wgj
     * @return mixed
     */
    public function merge_out_list()
    {
        $query = $this->request->request('query');
        $type = $this->request->request("type") ?? 1;
        $start_time = $this->request->request('start_time');
        $end_time = $this->request->request('end_time');
        $site = $this->request->request('site');
        $page = $this->request->request('page');
        $page_size = $this->request->request('page_size');
        empty($page) && $this->error(__('Page can not be empty'), [], 520);
        empty($page_size) && $this->error(__('Page size can not be empty'), [], 521);
        $offset = ($page - 1) * $page_size;
        $limit = $page_size;

        $where = [];
        if (1 == $type) {
            $where['combine_status'] = 1; //合单完成状态
            $where['store_house_id'] = ['>', 0];
            //合单待取出列表，主单为合单完成状态且子单都已合单
            if ($query) {
                //线上不允许跨库联合查询，拆分，wang导与产品静确认去除SKU搜索
                $store_house_id_store = $this->_stock_house->where(['type' => 2, 'coding' => ['like', '%' . $query . '%']])->column('id');
                /* $order_id = $this->_new_order_item_process->where(['sku'=> ['like', '%' . $query . '%']])->column('order_id');
                 $order_id = array_unique($order_id);
                 $store_house_id_sku = [];
                 if($order_id) {
                     $store_house_id_sku = $this->_new_order_process->where(['order_id'=> ['in', $order_id]])->column('store_house_id');
                 }
                 $store_house_ids = array_merge(array_filter($store_house_id_store), array_filter($store_house_id_sku));
                 if($store_house_ids) $where['store_house_id'] = ['in', $store_house_ids];*/
                if ($store_house_id_store) {
                    $where['store_house_id'] = ['in', $store_house_id_store];
                } else {
                    $where['store_house_id'] = -1;
                }
            }
            if ($start_time && $end_time) {
                $where['combine_time'] = ['between', [strtotime($start_time), strtotime($end_time)]];
            }
            if ($site){
                $where['site'] =  ['=', $site];
            }
            $list = $this->_new_order_process
                ->where($where)
                ->field('order_id,store_house_id,combine_time')
                ->group('order_id')
                ->limit($offset, $limit)
                ->select();
            foreach (array_filter($list) as $k => $v) {
                $list[$k]['coding'] = $this->_stock_house->where('id', $v['store_house_id'])->value('coding');
                !empty($v['combine_time']) && $list[$k]['combine_time'] = date('Y-m-d H:i:s', $v['combine_time']);
            }
        } else {
            $where['a.distribution_status'] = 7; //待合单状态
            $where['a.abnormal_house_id'] = ['>', 0]; //异常未处理
            $where['b.store_house_id'] = ['>', 0]; //有合单库位
            //异常待处理列表
            if ($query) {
                //线上不允许跨库联合查询，拆分，由于字段值明显差异，可以分别模糊匹配
                $store_house_ids = $this->_stock_house->where(['type' => 2, 'coding' => ['like', '%' . $query . '%']])->column('id');
                $item_order_number_store = [];
                if ($store_house_ids) {
                    $item_order_number_store = $this->_new_order_item_process
                        ->where(['abnormal_house_id' => ['in', $store_house_ids]])
                        ->column('id');
                }
                $item_ids = $this->_new_order_item_process
                    ->where(['item_order_number' => ['like', $query . '%']])
                    ->column('id');
                $item_ids = array_merge($item_ids, $item_order_number_store);
                if ($item_ids) {
                    $where['a.id'] = ['in', $item_ids];
                } else {
                    $where['a.id'] = -1;
                }
            }
            $list = $this->_new_order_item_process
                ->alias('a')
                ->where($where)
                ->join(['fa_order_process' => 'b'], 'a.order_id=b.order_id', 'left')
                ->field('b.store_house_id,b.increment_id,b.order_id')
                ->group('a.item_order_number')
                ->limit($offset, $limit)
                ->select();
            foreach (array_filter($list) as $k => $v) {
                $list[$k]['coding'] = $this->_stock_house->where('id', $v['store_house_id'])->value('coding');
            }
        }

        $magento_platform = new MagentoPlatform();
        $platform_list = $magento_platform->field('id, name')->where(['is_del' => 1, 'status' => 1])->select();

        $this->success('', ['list' => $list,'platform_list' => $platform_list], 200);
    }

    /**
     * 合单取出---释放库位[1.合单待取出 释放合单库位，异常待处理回退其主单下的所有子单为待合单状态并释放合单库位]
     *
     * @参数 string order_number  主订单号 取出时只需传order_number主订单号
     * @参数 int type  取出类型 1合单取出，2异常取出
     * @author wgj
     * @return mixed
     */
    public function merge_out_submit()
    {
        $order_number = $this->request->request('order_number');
        empty($order_number) && $this->error(__('主订单号不能为空'), [], 403);
        $type = $this->request->request('type');
        empty($type) && $this->error(__('请选择取出类型'), [], 403);

        //获取主单信息
        $order_process_info = $this->_new_order
            ->alias('a')
            ->where('a.increment_id', $order_number)
            ->join(['fa_order_process' => 'b'], 'a.id=b.order_id', 'left')
            ->field('a.id,b.combine_status,b.store_house_id')
            ->find();
        empty($order_process_info) && $this->error(__('主订单不存在'), [], 403);

        if ($order_process_info['store_house_id'] != 0) {
            if (1 == $type) {
                empty($order_process_info['combine_status']) && $this->error(__('只有合单完成状态才能取出'), [], 511);
                $item_process_info = $this->_new_order_item_process->field('id,item_order_number')->where('order_id', $order_process_info['id'])->select();
            } else {
                $item_process_info = $this->_new_order_item_process->field('id,item_order_number')->where(['order_id' => $order_process_info['id'], 'distribution_status' => 8])->select();
            }
            $store_house_coding = $this->_stock_house->where('id', $order_process_info['store_house_id'])->value('coding');
            //有合单库位订单--释放库位占用，解绑合单库位ID
            $return = false;
            $res = false;
            $this->_stock_house->startTrans();
            $this->_new_order_process->startTrans();
            $this->_new_order_item_process->startTrans();
            try {
                //更新订单业务处理表，解绑库位号
                $result = $this->_new_order_process->allowField(true)->isUpdate(true, ['order_id' => $order_process_info['id']])->save(['store_house_id' => 0]);
                if ($result != false) {
                    //释放合单库位占用数量
                    $res = $this->_stock_house->allowField(true)->isUpdate(true, ['id' => $order_process_info['store_house_id']])->save(['occupy' => 0]);
                    if ($res != false) {
                        //回退带有异常子单的 合单子单状态
                        if (0 == $order_process_info['combine_status'] && 2 == $type) {
                            $return = $this->_new_order_item_process
                                ->allowField(true)
                                ->isUpdate(true, ['order_id' => $order_process_info['id'], 'distribution_status' => 8])
                                ->save(['distribution_status' => 7]); //回退子订单合单状态至待合单7
                        }
                    }
                }
                $this->_stock_house->commit();
                $this->_new_order_process->commit();
                $this->_new_order_item_process->commit();
            } catch (ValidateException $e) {
                $this->_stock_house->rollback();
                $this->_new_order_process->rollback();
                $this->_new_order_item_process->rollback();
                $this->error($e->getMessage(), [], 444);
            } catch (PDOException $e) {
                $this->_stock_house->rollback();
                $this->_new_order_process->rollback();
                $this->_new_order_item_process->rollback();
                $this->error($e->getMessage(), [], 444);
            } catch (Exception $e) {
                $this->_stock_house->rollback();
                $this->_new_order_process->rollback();
                $this->_new_order_item_process->rollback();
                $this->error($e->getMessage(), [], 444);
            }
            if (1 == $type) {
                //合单完成订单取出
                if ($res !== false) {
                    //操作成功记录，批量日志插入
                    foreach ($item_process_info as $key => $value) {
                        DistributionLog::record($this->auth, $value['id'], 7, '子单号：' . $value['item_order_number'] . '，从合单架' . $store_house_coding . '合单库位取出完成');
                    }
                    $this->success('合单取出成功', [], 200);
                } else {
                    //操作失败记录，批量日志插入
                    foreach ($item_process_info as $key => $value) {
                        DistributionLog::record($this->auth, $value['id'], 7, '子单号：' . $value['item_order_number'] . '，从合单架' . $store_house_coding . '合单库位取出失败');
                    }
                    $this->error(__('No rows were inserted'), [], 511);
                }
            } else {
                //异常子单订单取出 --已合单的子单回退到待合单状态
                if ($return !== false) {
                    //操作成功记录，批量日志插入
                    foreach ($item_process_info as $key => $value) {
                        DistributionLog::record($this->auth, $value['id'], 7, '子单号：' . $value['item_order_number'] . '，从合单架' . $store_house_coding . '合单库位取出完成');
                    }
                    $this->success('异常取出成功', [], 200);
                } else {
                    //操作失败记录，批量日志插入
                    foreach ($item_process_info as $key => $value) {
                        DistributionLog::record($this->auth, $value['id'], 7, '子单号：' . $value['item_order_number'] . '，从合单架' . $store_house_coding . '合单库位取出失败');
                    }
                    $this->error(__('No rows were inserted'), [], 511);
                }
            }
        } else {
            $this->error(__('合单库位已经释放了'), [], 511);
        }
    }

    /**
     * 合单--合单完成页面--合单待取详情页面--修改原型图待定---子单合单状态、异常状态展示--ok
     *
     * @参数 int type  待取出类型 1 合单 2异常
     * @参数 int order_id  主订单ID
     * @参数 string item_order_number  子单号
     * @author wgj
     * @return mixed
     */
    public function merge_out_detail()
    {
        $order_id = $this->request->request('order_id');
        empty($order_id) && $this->error(__('主订单ID不能为空'), [], 403);

        $order_number = $this->_new_order->where(['id' => $order_id])->value('increment_id');
        empty($order_number) && $this->error(__('主订单不存在'), [], 403);
        $info['order_number'] = $order_number;

        //获取子订单数据
        $item_process_info = $this->_new_order_item_process
            ->where('order_id', $order_id)
            ->field('id,item_order_number,distribution_status,abnormal_house_id')
            ->select();
        empty($item_process_info) && $this->error(__('子订单数据不存在'), [], 403);

        $distribution_status = [0 => '取消', 1 => '待打印标签', 2 => '待配货', 3 => '待配镜片', 4 => '待加工', 5 => '待印logo', 6 => '待成品质检', 7 => '待合单', 8 => '合单中', 9 => '合单完成'];
        foreach ($item_process_info as $key => $value) {
            $item_process_info[$key]['distribution_status'] = $distribution_status[$value['distribution_status']]; //子单合单状态
            $item_process_info[$key]['abnormal_house_id'] = 0 == $value['abnormal_house_id'] ? '正常' : '异常'; //异常状态
        }

        $info['list'] = $item_process_info;
        $this->success('', ['info' => $info], 200);
    }

    /**
     * 发货审单
     *
     * @参数 int order_id  主订单ID
     * @参数 string check_refuse  审单拒绝原因 1.SKU缺失  2.配错镜框
     * @参数 int check_status  1审单通过，2审单拒绝
     * @参数 string create_person  操作人名称
     * @参数 array item_order_numbers  子单号列表
     * @author wgj
     * @return mixed
     */
    public function order_examine()
    {
        $order_id = $this->request->request('order_id');
        empty($order_id) && $this->error(__('主订单ID不能为空'), [], 403);
        $check_status = $this->request->request('check_status');
        empty($check_status) && $this->error(__('审单类型不能为空'), [], 403);
        $create_person = $this->request->request('create_person');
        empty($create_person) && $this->error(__('审单操作人不能为空'), [], 403);
        !in_array($check_status, [1, 2]) && $this->error(__('审单类型错误'), [], 403);
        $param = [];
        $param['check_status'] = $check_status;
        $param['check_time'] = time();
        $msg_info = '';
        if ($check_status == 2) {
            $check_refuse = $this->request->request('check_refuse'); //check_refuse   1SKU缺失  2 配错镜框
            empty($check_refuse) && $this->error(__('审单拒绝原因不能为空'), [], 403);
            !in_array($check_refuse, [1, 2 ,999]) && $this->error(__('审单拒绝原因错误'), [], 403);
            if(2 == $check_refuse){
                $item_order_numbers = $this->request->request('item_order_numbers');
                $item_order_numbers = explode(',',$item_order_numbers);
                empty($item_order_numbers) && $this->error(__('请选择子单号'), [], 403);
            }

            switch ($check_refuse) {
                case 1:
                    $param['check_remark'] = 'SKU缺失';
                    $msg_info_l = 'SKU缺失，';
                    $msg_info_r = '退回至待合单';
                    break;
                case 2:
                    $param['check_remark'] = '配错镜框';
                    $msg_info_l = '配错镜框，';
                    $msg_info_r = '退回至待配货';
                    break;
                case 999:
                    $param['check_remark'] = '标记异常';
                    $msg_info_l = '审单拒绝';
                    $msg_info_r = '标记异常';
                    break;
            }
            $msg = '审单拒绝';
        } else {
            $msg = '审单通过';
        }
        //检测订单审单状态
        $row = $this->_new_order_process->where(['order_id' => $order_id])->find();

        empty($row) && $this->error(__('主订单数据不存在'), [], 403);
        //判断有没有子单工单未完成
        $work_order_list = $this->_work_order_list->where(['platform_order' => $row->increment_id, 'work_status' => ['in',[1,2,3,5]]])->find();
        !empty($work_order_list) && $this->error(__('有子单工单未完成'), [], 403);
        $item_ids = $this->_new_order_item_process->where(['order_id' => $order_id])->column('id');
        empty($item_ids) && $this->error(__('子订单数据不存在'), [], 403);
        if (1 == $row['check_status']) {
            $this->success('审单已通过，请勿重复操作！', [], 200);
        }

        $this->_item->startTrans();
        $this->_item_platform_sku->startTrans();
        $this->_stock_log->startTrans();
        $this->_stock_house->startTrans();
        $this->_new_order_process->startTrans();
        $this->_new_order_item_process->startTrans();
        $this->_product_bar_code_item->startTrans();
        try {
            $result = $this->_new_order_process->allowField(true)->isUpdate(true, ['order_id' => $order_id])->save($param);
            false === $result && $this->error(__('订单状态更改失败'), [], 403);

            $log_data = [];
            //审单通过和拒绝都影响库存
            $item_where = [
                'order_id'=>$order_id,
                'distribution_status' => ['neq', 0]
            ];
            if(!empty($item_order_numbers)){
                $item_where['item_order_number'] = ['in',$item_order_numbers];
            }
            $item_info = $this->_new_order_item_process
                ->field('sku,site,item_order_number')
                ->where($item_where)
                ->select();
            if (2 == $check_status) {
                //审单拒绝，回退合单状态
                $this->_new_order_process
                    ->allowField(true)
                    ->isUpdate(true, ['order_id' => $order_id])
                    ->save(['combine_status' => 0, 'combine_time' => null]);
                if (1 == $check_refuse) {
                    //SKU缺失，回退子单号为待合单中状态，不影响库存
                    $this->_new_order_item_process
                        ->allowField(true)
                        ->isUpdate(true, ['order_id' => $order_id, 'distribution_status' => ['neq', 0]])
                        ->save(['distribution_status' => 7]);
                } else if(999 == $check_refuse){
                    //审核拒绝选择标记异常-核实地址
                    $all_item_order_number = $this->_new_order_item_process->where(['id' => ['in',$item_ids]])->column('item_order_number');
                    $abnormal_house_id = $this->_new_order_item_process->where(['id' => ['in',$item_ids] ,'abnormal_house_id' => ['>',1]])->column('abnormal_house_id');//查询当前主单下面是否有已标记异常的子单

                    !empty($abnormal_house_id) && $this->error(__('有子单已存在异常'), [], 403);
                    foreach ($all_item_order_number as $key => $value) {
                        //查询给所有子单标记异常异常库位是否足够
                        $stock_house_info = $this->_stock_house
                            ->field('id,coding')
                            ->where(['status' => 1, 'type' => 4, 'occupy' => ['<', 10000-count($all_item_order_number)]])
                            ->order('occupy', 'desc')
                            ->find();
                        if (empty($stock_house_info)) {
                            DistributionLog::record($this->auth, $item_process_id, 0, '异常暂存架没有空余库位');
                            $this->error(__('异常暂存架没有空余库位'), [], 405);
                        }
                        $this->in_sign_abnormal($value,13,1);
                    }
                }else {
                    //非指定子单回退到待合单
                    $this->_new_order_item_process
                        ->allowField(true)
                        ->isUpdate(true, ['order_id' => $order_id, 'distribution_status' => ['neq', 0]])
                        ->save([
                            'distribution_status' => 7
                        ]);

                    //配错镜框，指定子单回退到待配货，清空定制片库位ID及定制片处理状态
                    $this->_new_order_item_process
                        ->allowField(true)
                        ->isUpdate(true, ['order_id' => $order_id, 'distribution_status' => ['neq', 0], 'item_order_number' => ['in', $item_order_numbers]])
                        ->save([
                            'distribution_status' => 2,
                            'temporary_house_id' => 0,
                            'customize_status' => 0
                        ]);

                    //回退到待配货，解绑条形码
                    $this->_product_bar_code_item
                        ->allowField(true)
                        ->isUpdate(true, ['item_order_number' => ['in', $item_order_numbers]])
                        ->save(['out_stock_time' => null, 'library_status' => 1, 'item_order_number' => '']);

                    //扣减占用库存、配货占用、总库存、虚拟仓库存
                    foreach ($item_info as $key => $value) {
                        //仓库sku、库存
                        $platform_info = $this->_item_platform_sku
                            ->field('sku,stock')
                            ->where(['platform_sku' => $value['sku'], 'platform_type' => $value['site']])
                            ->find();
                        $true_sku = $platform_info['sku'];

                        //检验库存
                        $stock_arr = $this->_item
                            ->where(['sku' => $true_sku])
                            ->field('stock,occupy_stock,distribution_occupy_stock')
                            ->find();

                        //扣减可用库存、配货占用、总库存
                        $this->_item
                            ->where(['sku' => $true_sku])
                            ->dec('available_stock', 1)
                            ->dec('distribution_occupy_stock', 1)
                            ->dec('stock', 1)
                            ->update();

                        //扣减虚拟仓库存
                        $this->_item_platform_sku
                            ->where(['sku' => $true_sku, 'platform_type' => $value['site']])
                            ->dec('stock', 1)
                            ->update();

                        //记录库存日志
                        $log_data[] = [
                            'type' => 2,
                            'site' => $value['site'],
                            'modular' => 4,
                            'change_type' => 7,
                            'source' => 2,
                            'sku' => $true_sku,
                            'number_type' => 2,
                            'order_number' => $value['item_order_number'],
                            'occupy_stock_before' => $stock_arr['occupy_stock'],
                            'occupy_stock_change' => -1,
                            'distribution_stock_before' => $stock_arr['distribution_occupy_stock'],
                            'distribution_stock_change' => -1,
                            'stock_before' => $stock_arr['stock'],
                            'stock_change' => -1,
                            'fictitious_before' => $platform_info['stock'],
                            'fictitious_change' => -1,
                            'create_person' => $create_person,
                            'create_time' => time()
                        ];
                    }
                }
            } else {
                //审单通过，扣减占用库存、配货占用、总库存
                foreach ($item_info as $key => $value) {

                    /**************工单更换镜框******************/
                    //查询更改镜框最新信息
                    $change_sku = $this->_work_order_change_sku
                        ->alias('a')
                        ->join(['fa_work_order_measure' => 'b'], 'a.measure_id=b.id')
                        ->where([
                            'a.change_type' => 1,
                            'a.item_order_number' => $value['item_order_number'],
                            'b.operation_type' => 1
                        ])
                        ->order('a.id', 'desc')
                        ->value('a.change_sku');
                    if ($change_sku) {
                        $sku = $change_sku;
                    } else {
                        $sku = $value['sku'];
                    }
                    /********************************/

                    //仓库sku
                    $true_sku = $this->_item_platform_sku->getTrueSku($sku, $value['site']);

                    //检验库存
                    $stock_arr = $this->_item
                        ->where(['sku' => $true_sku])
                        ->field('stock,occupy_stock,distribution_occupy_stock')
                        ->find();

                    //扣减占用库存、配货占用、总库存
                    $this->_item
                        ->where(['sku' => $true_sku])
                        ->dec('occupy_stock', 1)
                        ->dec('distribution_occupy_stock', 1)
                        ->dec('stock', 1)
                        ->update();

                    //记录库存日志
                    $log_data[] = [
                        'type' => 2,
                        'site' => $value['site'],
                        'modular' => 4,
                        'change_type' => 6,
                        'source' => 2,
                        'sku' => $true_sku,
                        'number_type' => 2,
                        'order_number' => $value['item_order_number'],
                        'occupy_stock_before' => $stock_arr['occupy_stock'],
                        'occupy_stock_change' => -1,
                        'distribution_stock_before' => $stock_arr['distribution_occupy_stock'],
                        'distribution_stock_change' => -1,
                        'stock_before' => $stock_arr['stock'],
                        'stock_change' => -1,
                        'create_person' => $create_person,
                        'create_time' => time()
                    ];
                }

                //子单号集合
                $item_order_numbers = array_column($item_info, 'item_order_number');
                if (empty($item_order_numbers)) throw new Exception("子单号获取失败，请检查");

                //校验条形码是否已出库
                $check_bar_code = $this->_product_bar_code_item
                    ->where(['item_order_number' => ['in', $item_order_numbers], 'library_status' => 2])
                    ->value('code');
                if ($check_bar_code) throw new Exception("条形码：{$check_bar_code}已出库，请检查");

                //条形码出库时间
                $this->_product_bar_code_item
                    ->allowField(true)
                    ->isUpdate(true, ['item_order_number' => ['in', $item_order_numbers]])
                    ->save(['out_stock_time' => date('Y-m-d H:i:s'), 'library_status' => 2]);
            }

            //保存库存日志
            if ($log_data) {
                $this->_stock_log->allowField(true)->saveAll($log_data);
            }

            $this->_item->commit();
            $this->_item_platform_sku->commit();
            $this->_stock_log->commit();
            $this->_stock_house->commit();
            $this->_new_order_process->commit();
            $this->_new_order_item_process->commit();
            $this->_product_bar_code_item->commit();
        } catch (ValidateException $e) {
            $this->_item->rollback();
            $this->_item_platform_sku->rollback();
            $this->_stock_log->rollback();
            $this->_stock_house->rollback();
            $this->_new_order_process->rollback();
            $this->_new_order_item_process->rollback();
            $this->_product_bar_code_item->rollback();
            $this->error($e->getMessage(), [], 406);
        } catch (PDOException $e) {
            $this->_item->rollback();
            $this->_item_platform_sku->rollback();
            $this->_stock_log->rollback();
            $this->_stock_house->rollback();
            $this->_new_order_process->rollback();
            $this->_new_order_item_process->rollback();
            $this->_product_bar_code_item->rollback();
            DistributionLog::record((object)['nickname' => $create_person], $item_ids, 8, $e->getMessage() . '主单ID' . $row['order_id'] . $msg . '失败，原因：库存不足，请检查后操作');
            $this->error('库存不足，请检查后操作', [], 407);
        } catch (Exception $e) {
            $this->_item->rollback();
            $this->_item_platform_sku->rollback();
            $this->_stock_log->rollback();
            $this->_stock_house->rollback();
            $this->_new_order_process->rollback();
            $this->_new_order_item_process->rollback();
            $this->_product_bar_code_item->rollback();
            DistributionLog::record((object)['nickname' => $create_person], $item_ids, 8, $e->getMessage() . '主单ID' . $row['order_id'] . $msg . '失败' . $msg_info_l.$msg_info_r);
            $this->error($e->getMessage(), [], 408);
        }
        //打印操作记录
        if (1 != $check_refuse) {
            if (999 != $check_refuse) {
                $item_order_numbers = $this->_new_order_item_process->where(['item_order_number' => ['in',$item_order_numbers]])->column('id');
                $item_order_numbers = collection($item_order_numbers)->toArray();
                $item_ids_diff = array_diff($item_ids, $item_order_numbers);
                if (!empty($item_ids_diff)) {
                    foreach ($item_ids_diff as $key => $value) {
                        $item_numbers = $this->_new_order_item_process->where(['id' => $value])->column('item_order_number');
                        DistributionLog::record((object)['nickname' => $create_person], [$item_ids_diff[$key]], 8, '主单ID' . $row['order_id'] . $msg . $item_numbers[0].'退回至待合单');
                    }
                    foreach ($item_order_numbers as $key => $value) {
                        $item_numbers = $this->_new_order_item_process->where(['id' => $value])->column('item_order_number');
                        DistributionLog::record((object)['nickname' => $create_person], [$item_order_numbers[$key]], 8, '主单ID' . $row['order_id'] . $msg . '成功配错镜框，'.$item_numbers[0].'退回至待配货');
                    }
                }else{
                    foreach ($item_ids as $key => $value) {
                        $item_numbers = $this->_new_order_item_process->where(['id' => $value])->column('item_order_number');
                        DistributionLog::record((object)['nickname' => $create_person], [$item_ids[$key]], 8, '主单ID' . $row['order_id'] . $msg . '成功' . $msg_info_l.$item_numbers[0].$msg_info_r);
                    }
                }
            }
        }else{
            if (999 != $check_refuse) {
                foreach ($item_ids as $key => $value) {
                    $item_numbers = $this->_new_order_item_process->where(['id' => $value])->column('item_order_number');
                    DistributionLog::record((object)['nickname' => $create_person], [$item_ids[$key]], 8, '主单ID' . $row['order_id'] . $msg . '成功' . $msg_info_l.$item_numbers[0].$msg_info_r);
                } 
            } 
        }
        
        $this->success($msg . '成功', [], 200);
    }

    public function get_purchase_price(){
        $sku = $this->request->request('sku');
        empty($sku) && $this->error(__('sku不能为空'), ['purchase_price' => ''], 403);
        $item_sku = $this->_item
                    ->where(['sku' => $sku])
                    ->find();
        empty($item_sku) && $this->error(__('sku不存在'), ['purchase_price' => ''], 403);
        $PurchaseOrderItem = new \app\admin\model\purchase\PurchaseOrderItem;
        $purchase_price = $PurchaseOrderItem->alias('a')->field('a.purchase_price')->join(['fa_purchase_order' => 'b'], 'a.purchase_id=b.id')->where(['a.sku' => $sku])->order('b.createtime','desc')->find();
        empty($purchase_price['purchase_price']) && $this->error(__('没有采购单价'), ['purchase_price' => ''], 405);
        $this->success('成功', ['purchase_price' => $purchase_price['purchase_price']], 200);
    }

}
