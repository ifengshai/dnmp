<?php

namespace app\api\controller;

use app\admin\model\saleaftermanage\WorkOrderChangeSku;
use app\admin\model\saleaftermanage\WorkOrderMeasure;
use think\Db;
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
        $this->_new_order_process = new NewOrderProcess();
        $this->_product_bar_code_item = new ProductBarCodeItem();
        $this->_work_order_measure = new WorkOrderMeasure();
        $this->_work_order_change_sku = new WorkOrderChangeSku();
        $this->_lens_data = new LensData();
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
            ->find()
        ;
        empty($item_process_info) && $this->error(__('子订单不存在'), [], 403);
        !empty($item_process_info['abnormal_house_id']) && $this->error(__('已标记异常，不能多次标记'), [], 403);
        $item_process_id = $item_process_info['id'];

        //自动分配异常库位号
        $stock_house_info = $this->_stock_house
            ->field('id,coding')
            ->where(['status'=>1,'type'=>4,'occupy'=>['<',10]])
            ->order('occupy', 'desc')
            ->find()
        ;
        if(empty($stock_house_info)){
            DistributionLog::record($this->auth,$item_process_id,0,'异常暂存架没有空余库位');
            $this->error(__('异常暂存架没有空余库位'), [], 405);
        }

        Db::startTrans();
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
                ->isUpdate(true, ['item_order_number'=>$item_order_number])
                ->save(['abnormal_house_id'=>$stock_house_info['id']])
            ;

            //异常库位号占用数量+1
            $this->_stock_house
                ->where(['id' => $stock_house_info['id']])
                ->setInc('occupy', 1)
            ;

            //配货日志
            DistributionLog::record($this->auth,$item_process_id,9,"子单号{$item_order_number}，异常暂存架{$stock_house_info['coding']}库位");

            //提交事务
            Db::commit();
        } catch (ValidateException $e) {
            Db::rollback();
            $this->error($e->getMessage(), [], 406);
        } catch (PDOException $e) {
            Db::rollback();
            $this->error($e->getMessage(), [], 407);
        } catch (Exception $e) {
            Db::rollback();
            $this->error($e->getMessage(), [], 408);
        }

        $this->success(__("请将子单号{$item_order_number}的商品放入异常暂存架{$stock_house_info['coding']}库位"), ['coding' => $stock_house_info['coding']], 200);
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
        empty($query) && $this->error(__('搜索内容不能为空'), [], 403);

        //获取子订单数据
        $list = $this->_new_order_item_process
            ->where(['item_order_number'=>['like',"%{$query}%"]])
            ->field('item_order_number,sku')
            ->order('created_at','desc')
            ->limit(0,100)
            ->select()
        ;
        $list = collection($list)->toArray();

        $this->success('', ['list' => $list],200);
    }

    /**
     * 获取并校验子订单数据（配货通用）
     *
     * @param string $item_order_number  子订单号
     * @param int $check_status  检测状态
     * @author lzh
     * @return mixed
     */
    protected function info($item_order_number,$check_status)
    {
        empty($item_order_number) && $this->error(__('子订单号不能为空'), [], 403);

        //获取子订单数据
        $item_process_info = $this->_new_order_item_process
            ->where('item_order_number', $item_order_number)
            ->field('id,option_id,distribution_status,temporary_house_id,order_prescription_type,order_id')
            ->find()
        ;
        empty($item_process_info) && $this->error(__('子订单不存在'), [], 403);

        //检测状态
        $status_arr = [
            2=>'待配货',
            3=>'待配镜片',
            4=>'待加工',
            5=>'待印logo',
            6=>'待成品质检',
            7=>'待合单'
        ];
        $check_status != $item_process_info['distribution_status'] && $this->error(__('只有'.$status_arr[$check_status].'状态才能操作'), [], 405);

        //判断异常状态
        $abnormal_id = $this->_distribution_abnormal
            ->where(['item_process_id'=>$item_process_info['id'],'status'=>1])
            ->value('id')
        ;
        $abnormal_id && $this->error(__('有异常待处理，无法操作'), [], 405);

        //查询订单号
        $increment_id = $this->_new_order->where(['id'=>$item_process_info['order_id']])->value('increment_id');

        //检测是否有工单未处理
        $check_work_order = $this->_work_order_measure
            ->alias('a')
            ->field('a.item_order_number,a.measure_choose_id')
            ->join(['fa_work_order_list' => 'b'], 'a.work_id=b.id')
            ->where([
                'a.operation_type'=>0,
                'b.platform_order'=>$increment_id,
                'b.work_status'=>['in',[1,2,3,5]]
            ])
            ->select();
        if($check_work_order){
            foreach ($check_work_order as $val){
                (
                    3 == $val['measure_choose_id']//主单取消措施未处理
                    ||
                    $val['item_order_number'] == $item_order_number//子单措施未处理:更改镜框18、更改镜片19、取消20
                )
                && $this->error(__('有工单未处理，无法操作'), [], 405);
            }
        }

        //获取子订单处方数据
        $option_info = $this->_new_order_item_option
            ->where('id', $item_process_info['option_id'])
            ->find()
        ;
        if($option_info) $option_info = $option_info->toArray();

        //获取更改镜框最新信息
        $change_sku = $this->_work_order_change_sku
            ->alias('a')
            ->join(['fa_work_order_measure' => 'b'], 'a.measure_id=b.id')
            ->where([
                'a.change_type'=>1,
                'a.item_order_number'=>$item_order_number,
                'b.operation_type'=>1
            ])
            ->order('a.id','desc')
            ->value('a.change_sku');
        if($change_sku){
            $option_info['sku'] = $change_sku;
        }

        //获取更改镜片最新处方信息
        $change_lens = $this->_work_order_change_sku
            ->alias('a')
            ->field('a.od_sph,a.od_cyl,a.od_axis,a.od_add,a.pd_r,a.od_pv,a.od_bd,a.od_pv_r,a.od_bd_r,a.os_sph,a.os_cyl,a.os_axis,a.os_add,a.pd_l,a.os_pv,a.os_bd,a.os_pv_r,a.os_bd_r,a.lens_number')
            ->join(['fa_work_order_measure' => 'b'], 'a.measure_id=b.id')
            ->where([
                'a.change_type'=>2,
                'a.item_order_number'=>$item_order_number,
                'b.operation_type'=>1
            ])
            ->order('a.id','desc')
            ->find()
        ;
        if($change_lens){
            $change_lens = $change_lens->toArray();
            if($change_lens['pd_l'] && $change_lens['pd_r']){
                $change_lens['pd'] = '';
            }else{
                $change_lens['pd'] = $change_lens['pd_r'] ?: $change_lens['pd_l'];
            }
            $option_info = array_merge($option_info,$change_lens);
        }

        //获取镜片名称
        $lens_name = '';
        if($option_info['lens_number']){
            //获取镜片编码及名称
            $lens_list = $this->_lens_data->column('lens_name','lens_number');
            $lens_name = $lens_list[$option_info['lens_number']];
        }
        $option_info['lens_name'] = $lens_name;

        //异常原因列表
        $abnormal_arr = [
            2=>[
                ['id'=>1,'name'=>'缺货'],
                ['id'=>2,'name'=>'商品条码贴错'],
            ],
            3=>[
                ['id'=>3,'name'=>'核实处方'],
                ['id'=>4,'name'=>'镜片缺货'],
                ['id'=>5,'name'=>'镜片重做'],
                ['id'=>6,'name'=>'定制片超时']
            ],
            4=>[
                ['id'=>7,'name'=>'不可加工'],
                ['id'=>8,'name'=>'镜架加工报损'],
                ['id'=>9,'name'=>'镜片加工报损']
            ],
            5=>[
                ['id'=>10,'name'=>'logo不可加工'],
                ['id'=>11,'name'=>'镜架印logo报损']
            ],
            6=>[
                ['id'=>1,'name'=>'加工调整'],
                ['id'=>2,'name'=>'镜架报损'],
                ['id'=>3,'name'=>'镜片报损'],
                ['id'=>4,'name'=>'logo调整']
            ],
            7=>[
                ['id'=>12,'name'=>'缺货']
            ]
        ];
        $abnormal_list = $abnormal_arr[$check_status] ?? [];

        //配镜片：判断定制片
        if(3 == $check_status){
            //判断定制片暂存
            $msg = '';
            $second = 0;
            if(0 < $item_process_info['temporary_house_id']){
                //获取库位号，有暂存库位号，是第二次扫描，返回展示取出按钮
                $coding = $this->_stock_house
                    ->where(['id'=>$item_process_info['temporary_house_id']])
                    ->value('coding')
                ;
                $second = 1;//是第二次扫描
                $msg = "请将子单号{$item_order_number}的商品从定制片暂存架{$coding}库位取出";
            }else{
                //判断是否定制片
                if(3 == $item_process_info['order_prescription_type']){
                    //暂存自动分配库位
                    $stock_house_info = $this->_stock_house
                        ->field('id,coding')
                        ->where(['status'=>1,'type'=>3,'occupy'=>['<',10]])
                        ->order('occupy', 'desc')
                        ->find()
                    ;
                    if(!empty($stock_house_info)){
                        try {
                            //子订单绑定定制片库位号
                            $this->_new_order_item_process
                                ->allowField(true)
                                ->isUpdate(true, ['item_order_number'=>$item_order_number])
                                ->save(['temporary_house_id'=>$stock_house_info['id']])
                            ;

                            //定制片库位号占用数量+1
                            $this->_stock_house
                                ->where(['id' => $stock_house_info['id']])
                                ->setInc('occupy', 1)
                            ;
                            $coding = $stock_house_info['coding'];

                            //定制片提示库位号信息
                            if($coding){
                                DistributionLog::record($this->auth,$item_process_info['id'],0,"子单号{$item_order_number}，定制片库位号：{$coding}");

                                $second = 0;//是第一次扫描
                                $msg = "请将子单号{$item_order_number}的商品放入定制片暂存架{$coding}库位";
                            }

                            Db::commit();
                        } catch (ValidateException $e) {
                            Db::rollback();
                            $this->error($e->getMessage(), [], 406);
                        } catch (PDOException $e) {
                            Db::rollback();
                            $this->error($e->getMessage(), [], 407);
                        } catch (Exception $e) {
                            Db::rollback();
                            $this->error($e->getMessage(), [], 408);
                        }
                    }else{
                        DistributionLog::record($this->auth,$item_process_info['id'],0,'定制片暂存架没有空余库位');
                        $this->error(__('定制片暂存架没有空余库位，请及时处理'), [], 405);
                    }
                }
            }

            //定制片提示库位号信息
            if($coding){
                $this->success($msg, ['abnormal_list' => $abnormal_list,'option_info' => $option_info,'second' => $second],200);
            }
        }

        //配货返回数据
        if(7 == $check_status){
            //获取子订单处方数据
            return $abnormal_list;
        }
        
        $this->success('', ['abnormal_list' => $abnormal_list,'option_info' => $option_info],200);
    }

    /**
     * 提交操作（配货通用）
     *
     * @param string $item_order_number  子订单号
     * @param int $check_status  检测状态
     * @author lzh
     * @return mixed
     */
    protected function save($item_order_number,$check_status)
    {
        empty($item_order_number) && $this->error(__('子订单号不能为空'), [], 403);

        //获取子订单数据
        $item_process_info = $this->_new_order_item_process
            ->field('id,distribution_status,order_prescription_type,option_id,order_id,site')
            ->where('item_order_number', $item_order_number)
            ->find()
        ;

        //获取子订单处方数据
        $item_option_info = $this->_new_order_item_option
            ->field('is_print_logo,sku,index_name')
            ->where('id', $item_process_info['option_id'])
            ->find()
        ;

        //状态类型
        $status_arr = [
            2=>'配货',
            3=>'配镜片',
            4=>'加工',
            5=>'印logo',
            6=>'成品质检'
        ];

        //操作失败记录
        if(empty($item_process_info)){
            DistributionLog::record($this->auth,$item_process_info['id'],0,$status_arr[$check_status].'：子订单不存在');
            $this->error(__('子订单不存在'), [], 403);
        }

        //操作失败记录
        if($check_status != $item_process_info['distribution_status']){
            DistributionLog::record($this->auth,$item_process_info['id'],0,$status_arr[$check_status].'：当前状态['.$status_arr[$item_process_info['distribution_status']].']无法操作');
            $this->error(__('当前状态无法操作'), [], 405);
        }

        //检测异常状态
        $abnormal_id = $this->_distribution_abnormal
            ->where(['item_process_id'=>$item_process_info['id'],'status'=>1])
            ->value('id')
        ;

        //操作失败记录
        if($abnormal_id){
            DistributionLog::record($this->auth,$item_process_info['id'],0,$status_arr[$check_status].'：有异常['.$abnormal_id.']待处理不可操作');
            $this->error(__('有异常待处理无法操作'), [], 405);
        }

        //查询订单号
        $increment_id = $this->_new_order->where(['id'=>$item_process_info['order_id']])->value('increment_id');

        //检测是否有工单未处理
        $check_work_order = $this->_work_order_measure
            ->alias('a')
            ->field('a.item_order_number,a.measure_choose_id')
            ->join(['fa_work_order_list' => 'b'], 'a.work_id=b.id')
            ->where([
                'a.operation_type'=>0,
                'b.platform_order'=>$increment_id,
                'b.work_status'=>['in',[1,2,3,5]]
            ])
            ->select();
        if($check_work_order){
            foreach ($check_work_order as $val){
                (
                    3 == $val['measure_choose_id']//主单取消措施未处理
                    ||
                    $val['item_order_number'] == $item_order_number//子单措施未处理:更改镜框18、更改镜片19、取消20
                )
                && $this->error(__('有工单未处理，无法操作'), [], 405);
            }
        }

        //获取订单购买总数
        $total_qty_ordered = $this->_new_order
            ->where('id', $item_process_info['order_id'])
            ->value('total_qty_ordered')
        ;

        $res = false;
        Db::startTrans();
        try {
            //下一步提示信息及状态
            if(2 == $check_status){
                //配货节点 条形码绑定子单号
                $barcode = $this->request->request('barcode');
                $this->_product_bar_code_item
                    ->allowField(true)
                    ->isUpdate(true, ['code'=>$barcode])
                    ->save(['item_order_number'=>$item_order_number])
                ;

                //获取true_sku
                $true_sku = $this->_item_platform_sku->getTrueSku($item_option_info['sku'], $item_process_info['site']);

                //增加配货占用库存
                $this->_item
                    ->where(['sku'=>$true_sku])
                    ->inc('distribution_occupy_stock', 1)
                    ->update()
                ;

                //根据处方类型字段order_prescription_type(现货处方镜、定制处方镜)判断是否需要配镜片
                if(in_array($item_process_info['order_prescription_type'],[2,3])){
                    $save_status = 3;
                }else{
                    if($item_option_info['is_print_logo']){
                        $save_status = 5;
                    }else{
                        if($total_qty_ordered > 1){
                            $save_status = 7;
                        }else{
                            $save_status = 9;
                        }
                    }
                }
            }elseif(3 == $check_status){
                $save_status = 4;
            }elseif(4 == $check_status){
                if($item_option_info['is_print_logo']){
                    $save_status = 5;
                }else{
                    $save_status = 6;
                }
            }elseif(5 == $check_status){
                $save_status = 6;
            }elseif(6 == $check_status){
                if($total_qty_ordered > 1){
                    $save_status = 7;
                }else{
                    $save_status = 9;
                }
            }

            //订单主表标记已合单
            if(9 == $save_status){
                //主订单状态表
                $this->_new_order_process
                    ->allowField(true)
                    ->isUpdate(true, ['order_id'=>$item_process_info['order_id']])
                    ->save(['combine_status'=>1,'combine_time'=>time()])
                ;
            }

            $res = $this->_new_order_item_process
                ->allowField(true)
                ->isUpdate(true, ['item_order_number'=>$item_order_number])
                ->save(['distribution_status'=>$save_status])
            ;

            Db::commit();
        } catch (ValidateException $e) {
            Db::rollback();
            $this->error($e->getMessage(), [], 406);
        } catch (PDOException $e) {
            Db::rollback();
            $this->error($e->getMessage(), [], 407);
        } catch (Exception $e) {
            Db::rollback();
            $this->error($e->getMessage(), [], 408);
        }

        if($res){
            //操作成功记录
            DistributionLog::record($this->auth,$item_process_info['id'],$check_status,$status_arr[$check_status].'完成');

            //成功返回
            $next_step = [
                3=>'去配镜片',
                4=>'去加工',
                5=>'印logo',
                6=>'去质检',
                7=>'去合单',
                9=>'去审单'
            ];
            $this->success($next_step[$save_status], [],200);
        }else{
            //操作失败记录
            DistributionLog::record($this->auth,$item_process_info['id'],0,$status_arr[$check_status].'：保存失败');

            //失败返回
            $this->error(__($status_arr[$check_status].'失败'), [], 404);
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
        $this->info($item_order_number,2);
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
        $order_item_id = $this->_new_order_item_process->where('item_order_number',$item_order_number)->value('id');
        empty($order_item_id) && $this->error(__('订单不存在'), [], 403);

        $order_item_true_sku = $this->_new_order_item_process->where('item_order_number',$item_order_number)->value('sku');
        $order_item_sku = $this->_item_platform_sku->where('platform_sku',$order_item_true_sku)->value('sku');

        $barcode_item_order_number = $this->_product_bar_code_item->where('code',$barcode)->value('item_order_number');
        !empty($barcode_item_order_number) && $this->error(__('此条形码已经绑定过其他订单'), [], 403);
        $code_item_sku = $this->_product_bar_code_item->where('code',$barcode)->value('sku');
        empty($code_item_sku) && $this->error(__('此条形码未绑定SKU'), [], 403);

        if ($order_item_sku != $code_item_sku){
            //扫描获取的条形码 和 子订单查询出的 SKU(即true_sku)对比失败则配货失败
            //操作失败记录
            DistributionLog::record($this->auth,$order_item_id,2,'配货失败：sku配错');

            //失败返回
            $this->error(__('sku配错'), [], 404);
        } else {
            $this->save($item_order_number,2);
        }
    }

    /**
     * 镜片分拣
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
            'a.distribution_status'=>3,
            'b.index_name'=>['neq',''],
            'a.order_prescription_type'=>['neq',3],
        ];
        if($start_time && $end_time){
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
            ->group('a.order_prescription_type,b.lens_number,b.od_sph,b.od_cyl')
//            ->limit($offset, $limit)
            ->select();
        $list_od = collection($list_od)->toArray();
        //订单处方分类 0待处理  1 仅镜架 2 现货处方镜 3 定制处方镜 4 其他
        $order_prescription_type = [ 0=>'待处理',1=>'仅镜架',2=>'现货处方镜',3=>'定制处方镜',4=>'其他' ];
        foreach($list_od as $key=>$value){
            $list_od[$key]['order_prescription_type'] = $order_prescription_type[$value['order_prescription_type']];
            $list_od[$key]['light'] = 'SPH：'.$value['od_sph'].' CYL:'.$value['od_cyl'];

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
            ->group('a.order_prescription_type,b.lens_number,b.os_sph,b.os_cyl')
//            ->limit($offset, $limit)
            ->select();
        $list_os = collection($list_os)->toArray();
        //订单处方分类 0待处理  1 仅镜架 2 现货处方镜 3 定制处方镜 4 其他
        $order_prescription_type = [ 0=>'待处理',1=>'仅镜架',2=>'现货处方镜',3=>'定制处方镜',4=>'其他' ];
        foreach($list_os as $key=>$value){
            $list_os[$key]['order_prescription_type'] = $order_prescription_type[$value['order_prescription_type']];
            $list_os[$key]['light'] = 'SPH：'.$value['os_sph'].' CYL:'.$value['os_cyl'];

            unset($list_os[$key]['os_sph']);
            unset($list_os[$key]['os_cyl']);
        }
        
        //左右镜片数组取交集求all_count和，再合并
        foreach($list_os as $key=>$value){
            foreach($list_od as $k=>$v){
                if ($value['light'] == $v['light']){
                    $list_od[$k]['all_count'] = $value['all_count'] + $v['all_count'];
                    unset($list_os[$key]);
                }
            }
        }
        $list = array_merge($list_od,$list_os);
        $list = array_values($list);
        $this->success('', ['list' => $list],200);
    }

    /**
     * 镜片未分拣数量
     *
     * @参数 string start_time  开始时间
     * @参数 string end_time  结束时间
     * @参数 int page  页码
     * @参数 int page_size  每页显示数量
     * @author wgj
     * @return mixed
     */
    public function no_sorting()
    {
        $where = [
            'distribution_status'=>3,
            'order_prescription_type'=>2,
        ];

        //未分拣子订单数量
        $count = $this->_new_order_item_process
            ->alias('a')
            ->where($where)
            ->count();
        return 2*$count;
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
        $this->info($item_order_number,3);
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
            ->find()
        ;
        empty($item_process_info) && $this->error(__('子订单不存在'), [], 403);
        empty($item_process_info['temporary_house_id']) && $this->error(__('子订单未绑定暂存库位'), [], 403);
        3 != $item_process_info['distribution_status'] && $this->error(__('只有待配镜片状态才能操作'), [], 403);
        //获取库位号
        $coding = $this->_stock_house
            ->where(['id'=>$item_process_info['temporary_house_id']])
            ->value('coding')
        ;
        //子订单释放定制片库位号
        $result = $this->_new_order_item_process
            ->allowField(true)
            ->isUpdate(true, ['item_order_number'=>$item_order_number])
            ->save(['temporary_house_id'=>0])
        ;

        $res = false;
        if ($result != false){
            //定制片库位号占用数量-1
            $res = $this->_stock_house
                ->where(['id' => $item_process_info['temporary_house_id']])
                ->setDec('occupy', 1)
            ;
            DistributionLog::record($this->auth,$item_process_info['id'],0,"子单号{$item_order_number}，释放定制片库位号：{$coding}");
        }

        if ($res){
            $this->success("子单号{$item_order_number}的商品从定制片暂存架{$coding}库位取出成功", [],200);
        }
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
        $this->save($item_order_number,3);
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
        $this->info($item_order_number,4);
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
        $this->save($item_order_number,4);
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
        $this->info($item_order_number,5);
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
        $this->save($item_order_number,5);
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
        $this->info($item_order_number,6);
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
        !in_array($do_type,[1,2]) && $this->error(__('操作类型错误'), [], 403);

        if($do_type == 1){
            $this->save($item_order_number,6);
        }else{
            $reason = $this->request->request('reason');
            !in_array($reason,[1,2,3,4]) && $this->error(__('拒绝原因错误'), [], 403);

            //获取子订单数据
            $item_process_info = $this->_new_order_item_process
                ->where('item_order_number', $item_order_number)
                ->field('id,option_id,order_id,sku,site')
                ->find()
            ;

            //状态
            $status_arr = [
                1=>['status'=>4,'name'=>'质检拒绝：加工调整'],
                2=>['status'=>2,'name'=>'质检拒绝：镜架报损'],
                3=>['status'=>3,'name'=>'质检拒绝：镜片报损'],
                4=>['status'=>5,'name'=>'质检拒绝：logo调整']
            ];

            Db::startTrans();
            try {
                //子订单状态回退
                $this->_new_order_item_process
                    ->allowField(true)
                    ->isUpdate(true, ['id'=>$item_process_info['id']])
                    ->save(['distribution_status'=>$status_arr[$reason]['status']])
                ;
                
                //记录日志
                DistributionLog::record($this->auth,$item_process_info['id'],6,$status_arr[$reason]['name']);

                Db::commit();
            } catch (ValidateException $e) {
                Db::rollback();
                $this->error($e->getMessage(), [], 406);
            } catch (PDOException $e) {
                Db::rollback();
                $this->error($e->getMessage(), [], 407);
            } catch (Exception $e) {
                Db::rollback();
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
    public function merge(){
        $item_order_number = $this->request->request('item_order_number');
        empty($item_order_number) && $this->error(__('子订单号不能为空'), [], 403);

        //获取子订单数据
        $item_process_info = $this->_new_order_item_process
            ->where('item_order_number', $item_order_number)
            ->field('id,distribution_status,sku,order_id,temporary_house_id,abnormal_house_id')
            ->find();
        empty($item_process_info) && $this->error(__('子订单不存在'), [], 403);
        !in_array($item_process_info['distribution_status'],[7,8]) && $this->error(__('子订单当前状态不可合单操作'), [], 403);

        //获取订单购买总数
        $total_qty_ordered = $this->_new_order_item_process
            ->where('order_id', $item_process_info['order_id'])
            ->count();
        if (1 == $total_qty_ordered) $this->error(__('订单数量为1，不需要合单'), [], 403);

        $order_process_info = $this->_new_order_process
            ->where('order_id', $item_process_info['order_id'])
            ->field('order_id,store_house_id')
            ->find();

        //第二次扫描提示语
        if (7 < $item_process_info['distribution_status']){
            if ($order_process_info['store_house_id']){
                //有主单合单库位
                $store_house_info = $this->_stock_house->field('id,coding,subarea')->where('id',$order_process_info['store_house_id'])->find();
                $this->error(__('请将子单号'.$item_order_number.'的商品放入合单架'.$store_house_info['coding'].'合单库位'), [], 403);
            } else {
//                $this->_new_order_item_process->allowField(true)->isUpdate(true, ['item_order_number'=>$item_order_number])->save(['distribution_status'=>7]);
                $this->error(__('数据异常，合单失败'), [], 403);
            }
        }

        //未合单，首次扫描
        if (!$order_process_info['store_house_id']){
            //主单中无库位号，首个子单进入时，分配一个合单库位给PDA，暂不占用根据是否确认放入合单架占用或取消
            $store_house_info = $this->_stock_house->field('id,coding,subarea')->where(['status'=>1,'type'=>2,'occupy'=>0])->find();
            empty($store_house_info) && $this->error(__('合单失败，合单库位已用完，请添加后再操作'), [], 403);
        } else {
            //主单已绑定合单库位,根据ID查询库位信息
            $store_house_info = $this->_stock_house->field('id,coding,subarea')->where('id',$order_process_info['store_house_id'])->find();
        }
        $abnormal_list = $this->info($item_order_number,7);
        $info = [
            'item_order_number' => $item_order_number,
            'sku' => $item_process_info['sku'],
            'store_id' => $store_house_info['id'],
            'coding' => $store_house_info['coding'],
            'abnormal_list' => $abnormal_list
        ];

        $this->success('', ['info' => $info],200);
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
        !in_array($item_process_info['distribution_status'],[7,8]) && $this->error(__('子订单当前状态不可合单操作'), [], 403);

        //查询主单数据
        $order_process_info = $this->_new_order
            ->alias('a')
            ->where('a.id', $item_process_info['order_id'])
            ->join(['fa_order_process'=> 'b'],'a.id=b.order_id','left')
            ->field('a.id,a.increment_id,b.store_house_id')
            ->find();
        empty($order_process_info) && $this->error(__('主订单不存在'), [], 403);

        //获取库位信息
        $store_house_info = $this->_stock_house->field('id,coding,subarea,occupy')->where('id',$store_house_id)->find();//查询合单库位--占用数量
        empty($store_house_info) && $this->error(__('合单库位不存在'), [], 403);

        if ($order_process_info['store_house_id'] != $store_house_id){
            if ($store_house_info['occupy'] && empty($order_process_info['store_house_id'])){
                //主单无绑定库位，且分配的库位被占用，重新分配合单库位后再次提交确认放入新分配合单架
                $new_store_house_info = $this->_stock_house->field('id,coding,subarea')->where(['status'=>1,'type'=>2,'occupy'=>0])->find();
                empty($new_store_house_info) && $this->error(__('合单库位已用完，请检查合单库位情况'), [], 403);

                $info['store_id'] = $new_store_house_info['id'];
                $this->error(__('合单架'.$store_house_info['coding'].'库位已被占用，'.'请将子单号'.$item_order_number.'的商品放入新合单架'.$new_store_house_info['coding'].'库位'), ['info' => $info], 2001);
            }
        }

        if ($item_process_info['distribution_status'] == 8){
            //重复扫描子单号--提示语句
            $this->error(__('请将子单号'.$item_order_number.'的商品放入合单架'.$store_house_info['coding'].'库位'), [], 511);
        }

        //主单表有合单库位ID，查询主单商品总数，与子单合单入库计算数量对比
        //获取订单购买总数
        $total_qty_ordered = $this->_new_order_item_process
            ->where('order_id', $item_process_info['order_id'])
            ->count();
        $count = $this->_new_order_item_process
            ->where(['distribution_status'=>['in',[0,8]],'order_id'=>$item_process_info['order_id']])
            ->count();

        $info['order_id'] = $item_process_info['order_id'];//合单确认放入合单架提交 接口返回自带主订单号

        if ($total_qty_ordered > $count+1){
            //不是最后一个子单
            $num = '';
            $next = 1;//是否有下一个子单 1有，0没有
        } else {
            //最后一个子单
            $num = '最后一个';
            $next = 0;//是否有下一个子单 1有，0没有
        }
        if($order_process_info['store_house_id']){
            //存在合单库位ID，获取合单库位号ID存入
            $info['next'] = $next;
            //更新子单表
            $result = false;
            $result = $this->_new_order_item_process->allowField(true)->isUpdate(true, ['item_order_number'=>$item_order_number])->save(['distribution_status'=>8]);
            if ($result !== false){
                //操作成功记录
                DistributionLog::record($this->auth,$item_process_info['id'],7,'子单号：'.$item_order_number.'作为主单号'.$order_process_info['increment_id'].'的'.$num.'子单合单完成');
                if (!$next){
                    //最后一个子单且合单完成，更新主单、子单状态为合单完成
                    $this->_new_order_item_process
                        ->allowField(true)
                        ->isUpdate(true, ['order_id'=>$item_process_info['order_id'],'distribution_status'=>['neq',0]])
                        ->save(['distribution_status'=>9])
                    ;
                    $this->_new_order_process
                        ->allowField(true)
                        ->isUpdate(true, ['order_id'=>$item_process_info['order_id']])
                        ->save(['combine_status'=>1,'combine_time'=>time()])
                    ;
                }

                $this->success('子单号放入合单架成功', ['info'=>$info], 200);
            } else {
                //操作失败记录
                DistributionLog::record($this->auth,$item_process_info['id'],7,'子单号：'.$item_order_number.'作为主单号'.$order_process_info['increment_id'].'的'.$num.'子单合单失败');

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
            $result = $this->_new_order_item_process->allowField(true)->isUpdate(true, ['item_order_number'=>$item_order_number])->save(['distribution_status'=>8]);
            if ($result !== false){
                $res = $this->_new_order_process->allowField(true)->isUpdate(true, ['order_id'=>$item_process_info['order_id']])->save(['store_house_id'=>$store_house_id]);
                if ($res !== false){
                    $return = $this->_stock_house->allowField(true)->isUpdate(true, ['id'=>$store_house_id])->save(['occupy'=>1]);
                }
            }
            $this->_stock_house->commit();
            $this->_new_order_process->commit();
            $this->_new_order_item_process->commit();
        } catch (Exception $e) {
            $this->_stock_house->rollback();
            $this->_new_order_process->rollback();
            $this->_new_order_item_process->rollback();
            $this->error($e->getMessage(), [], 444);
        }
        if ($return !== false) {
            $info['next'] = $next;
            //操作成功记录
            DistributionLog::record($this->auth,$item_process_info['id'],7,'子单号：'.$item_order_number.'作为主单号'.$order_process_info['increment_id'].'的'.$num.'子单合单完成');

            $this->success('子单号放入合单架成功', ['info'=>$info], 200);
        } else {
            //操作失败记录
            DistributionLog::record($this->auth,$item_process_info['id'],7,'子单号：'.$item_order_number.'作为主单号'.$order_process_info['increment_id'].'的'.$num.'子单合单失败');

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
            ->join(['fa_order_process'=> 'b'],'a.id=b.order_id','left')
            ->field('a.id,a.increment_id,b.store_house_id')
            ->find();
        empty($order_process_info) && $this->error(__('主订单不存在'), [], 403);

        //获取子订单数据
        $item_process_info = $this->_new_order_item_process
            ->where('order_id', $order_process_info['id'])
            ->field('id,item_order_number,distribution_status,abnormal_house_id')
            ->select();
        empty($item_process_info) && $this->error(__('子订单数据异常'), [], 403);

        $distribution_status = [0=>'取消',1=>'待打印标签',2=>'待配货',3=>'待配镜片',4=>'待加工',5=>'待印logo',6=>'待成品质检',7=>'待合单',8=>'合单中',9=>'合单完成'];
        foreach($item_process_info as $key => $value){
            $item_process_info[$key]['distribution_status'] = $distribution_status[$value['distribution_status']];//子单合单状态
            $item_process_info[$key]['abnormal_house_id'] = 0 == $value['abnormal_house_id'] ? '正常' : '异常';//异常状态
        }
        $info['order_number'] = $order_number;
        $info['list'] = $item_process_info;
        $this->success('', ['info'=>$info], 200);

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
        $page = $this->request->request('page');
        $page_size = $this->request->request('page_size');
        empty($page) && $this->error(__('Page can not be empty'), [], 520);
        empty($page_size) && $this->error(__('Page size can not be empty'), [], 521);
        $offset = ($page - 1) * $page_size;
        $limit = $page_size;

        if (1 == $type){
            $where = [];
            $where['combine_status'] = 1;//合单完成状态
            $where['store_house_id'] = ['>', 0];
            //合单待取出列表，主单为合单完成状态且子单都已合单
            if($query){
                //线上不允许跨库联合查询，拆分，由于字段值明显差异，可以分别模糊匹配
                $store_house_id_store = $this->_stock_house->where(['coding'=> ['like', '%' . $query . '%']])->column('id');
               /* $order_id = $this->_new_order_item_process->where(['sku'=> ['like', '%' . $query . '%']])->column('order_id');
                $order_id = array_unique($order_id);
                $store_house_id_sku = [];
                if($order_id) {
                    $store_house_id_sku = $this->_new_order_process->where(['order_id'=> ['in', $order_id]])->column('store_house_id');
                }
                $store_house_ids = array_merge(array_filter($store_house_id_store), array_filter($store_house_id_sku));
                if($store_house_ids) $where['store_house_id'] = ['in', $store_house_ids];*/
                if($store_house_id_store) $where['store_house_id'] = ['in', $store_house_id_store];
            }
            if($start_time && $end_time){
                $where['combine_time'] = ['between', [strtotime($start_time), strtotime($end_time)]];
            }
            $list = $this->_new_order_process
                ->where($where)
                ->field('order_id,store_house_id,combine_time')
                ->group('order_id')
                ->limit($offset, $limit)
                ->select();
            foreach (array_filter($list) as $k => $v) {
                $list[$k]['coding'] = $this->_stock_house->where('id',$v['store_house_id'])->value('coding');
                !empty($v['combine_time']) && $list[$k]['combine_time'] = date('Y-m-d H:i:s', $v['combine_time']);
            }
        } else {
            $where = [];
            $where['abnormal_house_id'] = ['>',0];
            //异常待处理列表
            if($query){
                //线上不允许跨库联合查询，拆分，由于字段值明显差异，可以分别模糊匹配
                $store_house_ids = $this->_stock_house->where(['coding'=> ['like', '%' . $query . '%']])->column('id');
                $item_order_number_store = [];
                if($store_house_ids) {
                    $item_order_number_store = $this->_new_order_item_process->where(['abnormal_house_id'=>['in', $store_house_ids]])->column('item_order_number');
                }
                $item_order_number_item = $this->_new_order_item_process->where(['item_order_number'=> ['like', '%' . $query . '%']])->column('item_order_number');
                $item_order_number = array_merge($item_order_number_item, $item_order_number_store);
                if($item_order_number) $where['item_order_number'] = ['in', $item_order_number];

            }
            $list = $this->_new_order_item_process
                ->where($where)
                ->field('abnormal_house_id,item_order_number')
                ->limit($offset, $limit)
                ->select();
            foreach (array_filter($list) as $k => $v) {
                $list[$k]['coding'] = $this->_stock_house->where('id',$v['abnormal_house_id'])->value('coding');
            }
        }

        $this->success('', ['list' => $list],200);
    }

    /**
     * 合单取出---释放库位[1.正常状态取出释放合单库位，异常单则回退其主单下的所有子单为待合单状态并释放合单库位]
     *
     * @参数 string order_number  主订单号 取出时只需传order_number主订单号
     * @author wgj
     * @return mixed
     */
    public function merge_out_submit()
    {
        $order_number = $this->request->request('order_number');
        empty($order_number) && $this->error(__('主订单号不能为空'), [], 403);

        //获取主单库位信息
        $order_process_info = $this->_new_order
            ->alias('a')
            ->where('a.increment_id', $order_number)
            ->join(['fa_order_process'=> 'b'],'a.id=b.order_id','left')
            ->field('a.id,b.combine_status,b.store_house_id')
            ->find();
        empty($order_process_info) && $this->error(__('主订单不存在'), [], 403);
        empty($order_process_info['combine_status']) && $this->error(__('只有合单完成状态才能取出'), [], 511);

        if ($order_process_info['store_house_id'] != 0){
            //有合单库位订单--释放库位占用，解绑合单库位ID
            $return = false;
            $res = false;
            Db::startTrans();
            try {
                //更新订单业务处理表，解绑库位号
                $result = $this->_new_order_process->allowField(true)->isUpdate(true, ['order_id'=>$order_process_info['id']])->save(['store_house_id'=>0]);
                if ($result != false){
                    //释放合单库位占用数量
                    $res = $this->_stock_house->allowField(true)->isUpdate(true, ['id'=>$order_process_info['store_house_id']])->save(['occupy'=>0]);
                    if ($res != false){
                        //回退带有异常子单的 合单子单状态
                        if ($order_process_info['combine_status'] == 0){
                            $where = [];
                            $where['abnormal_house_id'] = 0;
                            $where['order_id'] = $order_process_info['id'];
                            $item_process_info = $this->_new_order_item_process->field('id,item_order_number')->where($where)->select();
                            $ids = array_column($item_process_info,'id');
                            $return = $this->_new_order_item_process
                                ->allowField(true)
                                ->isUpdate(true, ['id'=>['in', $ids],'distribution_status'=>['neq', 0]])
                                ->save(['distribution_status'=>7]);//回退子订单合单状态至待合单7
                        }

                    }
                }

                Db::commit();
            } catch (ValidateException $e) {
                Db::rollback();
                $this->error($e->getMessage(), [], 444);
            } catch (PDOException $e) {
                Db::rollback();
                $this->error($e->getMessage(), [], 444);
            } catch (Exception $e) {
                Db::rollback();
                $this->error($e->getMessage(), [], 444);
            }
            if ($order_process_info['combine_status'] == 1){
                //合单完成订单
                $item_process_info = $this->_new_order_item_process->field('id,item_order_number')->where('order_id',$order_process_info['id'])->select();
                if ($res !== false) {
                    //操作成功记录，批量日志插入
                    foreach($item_process_info as $key => $value){
                        DistributionLog::record($this->auth,$value['id'],7,'子单号：'.$value['item_order_number'].'作为主单号'.$order_number.'的子单取出合单库完成');
                    }

                    $this->success('合单取出成功', [], 200);
                } else {
                    //操作失败记录，批量日志插入
                    foreach($item_process_info as $key => $value){
                        DistributionLog::record($this->auth,$value['id'],7,'子单号：'.$value['item_order_number'].'作为主单号'.$order_number.'的子单取出合单库失败');
                    }

                    $this->error(__('No rows were inserted'), [], 511);
                }
            } else {
                //异常子单订单 --已合单的子单回退到待合单状态

                $where = [];
                $where['abnormal_house_id'] = 0;
                $where['order_id'] = $order_process_info['id'];
                $item_process_info = $this->_new_order_item_process->field('id,item_order_number')->where($where)->select();
                if ($return !== false) {
                    //操作成功记录，批量日志插入
                    foreach($item_process_info as $key => $value){
                        DistributionLog::record($this->auth,$value['id'],7,'子单号：'.$value['item_order_number'].'作为主单号'.$order_number.'的子单取出异常订单完成');
                    }

                    $this->success('合单取出成功', [], 200);
                } else {
                    //操作失败记录，批量日志插入
                    foreach($item_process_info as $key => $value){
                        DistributionLog::record($this->auth,$value['id'],7,'子单号：'.$value['item_order_number'].'作为主单号'.$order_number.'的子单取出异常订单失败');
                    }

                    $this->error(__('No rows were inserted'), [], 511);
                }
            }

        } else {
            $this->error(__('合单库位已经取出了'), [], 511);
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
        $type = $this->request->request("type") ?? 1;
        $item_order_number = $this->request->request('item_order_number');
        $order_id = $this->request->request('order_id');

        if ($type == 1){
            empty($order_id) && $this->error(__('主订单ID不能为空'), [], 403);
            $order_number = $this->_new_order->where(['id' => $order_id])->value('increment_id');
            empty($order_number) && $this->error(__('主订单不存在'), [], 403);
            $order_process_info = $this->_new_order
                ->alias('a')
                ->where('a.id', $order_id)
                ->join(['fa_order_process'=> 'b'],'a.id=b.order_id','left')
                ->field('a.id,a.increment_id,b.store_house_id')
                ->find();

            //获取子订单数据
            $item_process_info = $this->_new_order_item_process
                ->where('order_id', $order_process_info['id'])
                ->field('id,item_order_number,distribution_status,abnormal_house_id')
                ->select();
            empty($item_process_info) && $this->error(__('子订单数据异常'), [], 403);
            $info['order_number'] = $order_number;

        } else {
            empty($item_order_number) && $this->error(__('子订单号不能为空'), [], 403);
            //子单号获取主单号
            $order_id = $this->_new_order_item_process->where(['item_order_number' => $item_order_number])->value('order_id');
            $order_number = $this->_new_order->where(['id' => $order_id])->value('increment_id');
            empty($order_number) && $this->error(__('子订单数据异常'), [], 403);
            $info['order_number'] = $order_number;

            //获取子订单数据
            $item_process_info = $this->_new_order_item_process
                ->where('order_id', $order_id)
                ->field('id,item_order_number,distribution_status,abnormal_house_id')
                ->select();
            empty($item_process_info) && $this->error(__('子订单数据异常'), [], 403);
        }

        $distribution_status = [0=>'取消',1=>'待打印标签',2=>'待配货',3=>'待配镜片',4=>'待加工',5=>'待印logo',6=>'待成品质检',7=>'待合单',8=>'合单中',9=>'合单完成'];
        foreach($item_process_info as $key => $value){
            $item_process_info[$key]['distribution_status'] = $distribution_status[$value['distribution_status']];//子单合单状态
            $item_process_info[$key]['abnormal_house_id'] = 0 == $value['abnormal_house_id'] ? '正常' : '异常';//异常状态
        }

        $info['list'] = $item_process_info;
        $this->success('', ['info'=>$info], 200);

    }

    /**
     * 发货审单
     *
     * @参数 int order_id  主订单ID
     * @参数 string check_refuse  审单拒绝原因 1.SKU缺失  2.配错镜框
     * @参数 int check_status  1审单通过，2审单拒绝
     * @author wgj
     * @return mixed
     */
    public function order_examine()
    {
        $order_id = $this->request->request('order_id');
        empty($order_id) && $this->error(__('主订单ID不能为空'), [], 403);
        $check_status = $this->request->request('check_status');
        empty($check_status) && $this->error(__('审单类型不能为空'), [], 403);
        !in_array($check_status, [1, 2]) && $this->error(__('审单类型错误'), [], 403);
        $param = [];
        $param['check_status'] = $check_status;
        $param['check_time'] = time();
        $msg = '审单通过';
        $msg_info = '';
        if ($check_status == 2) {
            $check_refuse = $this->request->request('check_refuse');//check_refuse   1SKU缺失  2 配错镜框
            empty($check_refuse) && $this->error(__('审单拒绝原因不能为空'), [], 403);
            !in_array($check_refuse, [1, 2]) && $this->error(__('审单拒绝原因错误'), [], 403);

            switch ($check_refuse)
            {
                case 1:
                    $param['check_remark'] = 'SKU缺失';
                    $msg_info = 'SKU缺失，退回至待合单';
                    break;
                case 2:
                    $param['check_remark'] = '配错镜框';
                    $msg_info = '配错镜框，退回至待配货';
                    break;
            }
            $msg = '审单拒绝';
        }

        //检测订单审单状态
        $row = $this->_new_order_process->where(['order_id'=>$order_id])->find();
        $item_ids = $this->_new_order_item_process->where(['order_id'=>$order_id])->column('id');
        0 != $row['check_status'] && $this->success(__($msg.'成功'), [], 200);

        $result = false;
        $this->_item->startTrans();
        $this->_stock_house->startTrans();
        $this->_new_order_process->startTrans();
        $this->_new_order_item_process->startTrans();
        try {
            $result = $this->_new_order_process->allowField(true)->isUpdate(true, ['order_id' => $order_id])->save($param);
            if (false !== $result){
                //审单通过和拒绝都影响库存
                $item_info = $this->_new_order_item_process->field('sku,site')->where(['order_id'=>$order_id])->select();
                if (2 == $check_status) {
                    //审单拒绝，回退合单状态
                    $this->_new_order_process->allowField(true)->isUpdate(true, ['order_id' => $order_id])->save(['combine_status'=>0,'combine_time'=>null]);
                    if (1 == $check_refuse){
                        //SKU缺失，绑定合单库位，回退子单号为合单中状态，不影响库存
                        $store_house_id = $this->_stock_house->field('id,coding,subarea')->where(['status'=>1,'type'=>2,'occupy'=>0])->value('id');
                        if (empty($store_house_id)){
                            throw new Exception('合单库位已用完，请检查合单库位情况');
                        }
                        $this->_new_order_process->allowField(true)->isUpdate(true, ['order_id' => $order_id])->save(['store_house_id'=>$store_house_id]);
                        $this->_new_order_item_process
                            ->allowField(true)
                            ->isUpdate(true, ['order_id' => $order_id,'distribution_status'=>['neq', 0]])
                            ->save(['distribution_status'=>8]);
                        $this->_stock_house->allowField(true)->isUpdate(true, ['id'=>$store_house_id])->save(['occupy'=>1]);
                    } else {
                        //配错镜框，回退子单为待配货
                        $this->_new_order_item_process
                            ->allowField(true)
                            ->isUpdate(true, ['order_id' => $order_id,'distribution_status'=>['neq', 0]])
                            ->save(['distribution_status'=>2]);

                        //扣减占用库存、配货占用、总库存、虚拟仓库存
                        foreach($item_info as $key => $value){
                            //仓库sku
                            $true_sku = $this->_item_platform_sku->getTrueSku($value['sku'], $value['site']);

                            //检验库存
                            $stock_arr = $this->_item
                                ->where(['sku'=>$true_sku])
                                ->field('stock,occupy_stock,distribution_occupy_stock')
                                ->find()
                            ;
                            $stock_arr = $stock_arr ? $stock_arr->toArray() : [];
                            $stock = $this->_item->where(['sku'=>$true_sku])->value('stock');
                            if ( in_array(0,$stock_arr) || empty($stock)){
                                throw new Exception($value['sku'].':库存不足');
                            }

                            //扣减占用库存、配货占用、总库存
                            $this->_item
                                ->where(['sku'=>$true_sku])
                                ->dec('occupy_stock', 1)
                                ->dec('distribution_occupy_stock', 1)
                                ->dec('stock', 1)
                                ->update()
                            ;

                            //扣减虚拟仓库存
                            $this->_item_platform_sku
                                ->where(['sku' => $true_sku, 'platform_type' => $value['site']])
                                ->dec('stock', 1)
                                ->update();
                        }
                    }
                } else {
                    //审单通过，扣减占用库存、配货占用、总库存
                    foreach($item_info as $key => $value){
                        //仓库sku
                        $true_sku = $this->_item_platform_sku->getTrueSku($value['sku'], $value['site']);

                        //检验库存
                        $stock_arr = $this->_item->where(['sku'=>$true_sku])->field('stock,occupy_stock,distribution_occupy_stock');
                        if (in_array(0,$stock_arr)){
                            throw new Exception($value['sku'].':库存不足');
                        }

                        //扣减占用库存、配货占用、总库存
                        $this->_item
                            ->where(['sku'=>$true_sku])
                            ->dec('occupy_stock', 1)
                            ->dec('distribution_occupy_stock', 1)
                            ->dec('stock', 1)
                            ->update()
                        ;
                    }
                }
            }
            $this->_item->commit();
            $this->_stock_house->commit();
            $this->_new_order_process->commit();
            $this->_new_order_item_process->commit();
        } catch (Exception $e) {
            $this->_item->rollback();
            $this->_stock_house->rollback();
            $this->_new_order_process->rollback();
            $this->_new_order_item_process->rollback();
            DistributionLog::record($this->auth,$item_ids,8,$e->getMessage().'主单ID'.$row['order_id'].$msg.'失败'.$msg_info);
            $this->error($e->getMessage(), [], 408);
        }

        DistributionLog::record($this->auth,$item_ids,8,'主单ID'.$row['order_id'].$msg.'成功'.$msg_info);
        $this->success($msg.'成功', [], 200);
    }

}
