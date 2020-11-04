<?php

namespace app\api\controller;

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
     * 主订单状态模型对象
     * @var object
     * @access protected
     */
    protected $_new_order_process = null;

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
        $item_process_id = $this->_new_order_item_process
            ->where('item_order_number', $item_order_number)
            ->value('id')
        ;
        empty($item_process_id) && $this->error(__('子订单不存在'), [], 403);

        //自动分配异常库位号
        $stock_house_info = $this->_stock_house
            ->field('id,coding')
            ->where(['status'=>1,'type'=>4,'occupy'=>['<',10]])
            ->order('occupy', 'desc')
            ->find()
        ;
        if(!empty($stock_house_info)){
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

            DistributionLog::record($this->auth,$item_process_id,9,"子单号{$item_order_number}，异常暂存架{$stock_house_info['coding']}库位");
            $this->success(__("请将子单号{$item_order_number}的商品放入异常暂存架{$stock_house_info['coding']}库位"), [], 200);
        }else{
            DistributionLog::record($this->auth,$item_process_id,0,'异常暂存架没有空余库位');
            $this->error(__('异常暂存架没有空余库位'), [], 405);
        }
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
            ->field('id,option_id,distribution_status,temporary_house_id')
            ->find()
        ;
        empty($item_process_info) && $this->error(__('子订单不存在'), [], 403);

        //检测状态
        $status_arr = [
            2=>'待配货',
            3=>'待配镜片',
            4=>'待加工',
            5=>'待印logo',
            6=>'待成品质检'
        ];
        $check_status != $item_process_info['distribution_status'] && $this->error(__('只有'.$status_arr[$check_status].'状态才能操作'), [], 405);

        //TODO::判断工单状态

        //判断异常状态
        $abnormal_id = $this->_distribution_abnormal
            ->where(['item_process_id'=>$item_process_info['id'],'status'=>1])
            ->value('id')
        ;
        $abnormal_id && $this->error(__('有异常待处理，无法操作'), [], 405);

        //配镜片：判断定制片
        if(3 == $check_status){
            //判断定制片暂存
            if(1 == $item_process_info['temporary_house_id']){
                //获取库位号
                $coding = $this->_stock_house
                    ->where(['id'=>$item_process_info['temporary_house_id']])
                    ->value('coding')
                ;
            }else{
                //获取子订单处方数据
                $is_custom_lens = $this->_new_order_item_option
                    ->where('id', $item_process_info['option_id'])
                    ->value('is_custom_lens')
                ;

                //判断是否定制片
                if(1 == $is_custom_lens){
                    //暂存自动分配库位
                    $stock_house_info = $this->_stock_house
                        ->field('id,coding')
                        ->where(['status'=>1,'type'=>3,'occupy'=>['<',10]])
                        ->order('occupy', 'desc')
                        ->find()
                    ;
                    if(!empty($stock_house_info)){
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
                    }else{
                        DistributionLog::record($this->auth,$item_process_info['id'],0,'定制片暂存架没有空余库位');
                        $this->error(__('定制片暂存架没有空余库位，请及时处理'), [], 405);
                    }
                }
            }

            //定制片提示库位号信息
            if($coding){
                DistributionLog::record($this->auth,$item_process_info['id'],0,"子单号{$item_order_number}，定制片库位号：{$coding}");
                $this->error(__("请将子单号{$item_order_number}的商品放入定制片暂存架{$coding}库位"), [], 405);
            }
        }

        //获取子订单处方数据
        $option_info = $this->_new_order_item_option
            ->where('id', $item_process_info['option_id'])
            ->find()
        ;

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
                [['id'=>12,'name'=>'缺货']]
            ]
        ];
        $abnormal_list = $abnormal_arr[$check_status] ?? [];

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
            ->field('id,distribution_status,option_id,order_id,site')
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

        //TODO::检测工单状态

        //获取订单购买总数
        $total_qty_ordered = $this->_new_order
            ->where('id', $item_process_info['order_id'])
            ->value('total_qty_ordered')
        ;

        //下一步提示信息及状态
        if(2 == $check_status){
            if($item_option_info['index_name']){
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

            //获取true_sku
            $true_sku = $this->_item_platform_sku->getTrueSku($item_option_info['sku'], $item_process_info['site']);

            //扣减订单占用库存、配货占用库存、总库存
            $this->_item
                ->where(['sku'=>$true_sku])
                ->dec('occupy_stock', 1)
                ->dec('distribution_occupy_stock', 1)
                ->dec('stock', 1)
                ->update()
            ;
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
     * 配货提交
     *
     * @参数 string item_order_number  子订单号
     * @author wgj
     * @return mixed
     */
    public function product_submit()
    {
        $item_order_number = $this->request->request('item_order_number');
        $item_order_number = $this->request->request('item_order_number');
//        var_dump($item_order_number);
//        die;
        $this->save($item_order_number,2);
    }

    /**
     * 镜片分拣（待定）
     *
     * @参数 string start_time  开始时间
     * @参数 string end_time  结束时间
     * @参数 int page  页码
     * @参数 int page_size  每页显示数量
     * @author lzh
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
            'a.status'=>3,
            'b.index_name'=>['neq',''],
        ];
        if($start_time && $end_time){
            $where['a.created_at'] = ['between', [strtotime($start_time), strtotime($end_time)]];
        }

        $offset = ($page - 1) * $page_size;
        $limit = $page_size;

        //获取出库单列表数据
        $list = $this->_new_order_item_process
            ->alias('a')
            ->where($where)
            ->field('count(*) as all_count,b.prescription_type,b.index_type,b.index_name')
            ->join(['fa_order_item_option' => 'b'], 'a.option_id=b.id')
            ->group('b.prescription_type,b.index_type,b.index_name')
            ->limit($offset, $limit)
            ->select();
        $list = collection($list)->toArray();

        $this->success('', ['list' => $list],200);
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
                //子订单状态回滚
                $this->_new_order_item_process
                    ->allowField(true)
                    ->isUpdate(true, ['id'=>$item_process_info['id']])
                    ->save(['distribution_status'=>$status_arr[$reason]['status']])
                ;

                //镜片报损扣减可用库存、虚拟仓库存、配货占用库存、总库存
                if(2 == $reason){
                    //获取true_sku
                    $true_sku = $this->_item_platform_sku->getTrueSku($item_process_info['sku'], $item_process_info['site']);

                    //扣减虚拟仓库存
                    $this->_item_platform_sku
                        ->where(['sku'=>$true_sku,'platform_type'=>$item_process_info['site']])
                        ->dec('stock', 1)
                        ->update()
                    ;

                    //扣减可用库存、配货占用库存、总库存
                    $this->_item
                        ->where(['sku'=>$true_sku])
                        ->dec('available_stock', 1)
                        ->dec('distribution_occupy_stock', 1)
                        ->dec('stock', 1)
                        ->update()
                    ;
                }

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
        $_new_order_item_process = new \app\admin\model\order\order\NewOrderItemProcess();
        $item_process_info = $_new_order_item_process
            ->where('item_order_number', $item_order_number)
            ->field('id,distribution_status,order_id,temporary_house_id,abnormal_house_id')
            ->find();
        empty($item_process_info) && $this->error(__('子订单不存在'), [], 403);

        //获取订单购买总数,商品总数即为子单数量
        $_new_order_process = new \app\admin\model\order\order\NewOrderProcess();
        $_stock_house = new \app\admin\model\warehouse\StockHouse;
        $order_process_info = $_new_order_process
            ->where('order_id', $item_process_info['order_id'])
            ->field('order_id,store_house_id')
            ->find();

        //第二次扫描提示语
        if ($item_process_info['distribution_status'] == 8){
            //判断子订单类型，是否为异常库位、暂存库为、正常库位
            if ($item_process_info['abnormal_house_id']){
                //有异常库位ID
                $store_house_info = $_stock_house->field('id,coding,subarea')->where('id',$item_process_info['abnormal_house_id'])->find();
                $this->error(__('请将子单号'.$item_order_number.'的商品放入'.$store_house_info['coding'].'异常库位'), [], 403);
            } elseif ($item_process_info['temporary_house_id']){
                //有暂存库位ID
                $store_house_info = $_stock_house->field('id,coding,subarea')->where('id',$item_process_info['temporary_house_id'])->find();
                $this->error(__('请将子单号'.$item_order_number.'的商品放入'.$store_house_info['coding'].'异常库位'), [], 403);
            }elseif ($order_process_info['store_house_id']){
                //有主单合单库位
                $store_house_info = $_stock_house->field('id,coding,subarea')->where('id',$order_process_info['store_house_id'])->find();
                $this->error(__('请将子单号'.$item_order_number.'的商品放入合单架'.$store_house_info['coding'].'库位'), [], 403);
            }

        }

        //未合单，首次扫描
        $info['item_order_number'] = $item_order_number;
        if (!$order_process_info['store_house_id']){
            //主单中无库位号，首个子单进入时，分配一个合单库位给PDA，暂不占用根据是否确认放入合单架占用或取消
            $store_house_info = $_stock_house->field('id,coding,subarea')->where(['status'=>1,'type'=>2])->find();
            $info['store_id'] = $store_house_info['id'];
        } else {
            //主单已绑定合单库位,根据ID查询库位信息
            $store_house_info = $_stock_house->field('id,coding,subarea')->where('id',$order_process_info['store_house_id'])->find();
            $info['store_id'] = $store_house_info['id'];
        }

        $this->success('', ['info' => $info],200);
    }


    /**
     * 合单--确认放入合单架---最后一个子单扫描合单时，检查子单合单是否有异常，无异常且全部为已合单，则更新主单合单状态和时间--ok
     *
     * @参数 string item_order_number  子订单号
     * @author wgj
     * @return mixed
     */
    public function item_order_merge_submit()
    {
        $item_order_number = $this->request->request('item_order_number');
        $store_house_id = $this->request->request('store_house_id');
        empty($item_order_number) && $this->error(__('子订单号不能为空'), [], 403);
        empty($store_house_id) && $this->error(__('合单库位号不能为空'), [], 403);

        //获取子订单数据
        $_new_order_item_process = new \app\admin\model\order\order\NewOrderItemProcess();
        $item_process_info = $_new_order_item_process
            ->where('item_order_number', $item_order_number)
            ->field('id,distribution_status,order_id')
            ->find();
        empty($item_process_info) && $this->error(__('子订单不存在'), [], 403);

        //获取订单购买总数,商品总数即为子单数量
        $_stock_house = new \app\admin\model\warehouse\StockHouse;
        $_new_order = new \app\admin\model\order\order\NewOrder();
        $_new_order_process = new \app\admin\model\order\order\NewOrderProcess();
        $order_process_info = $_new_order
            ->alias('a')
            ->where('a.id', $item_process_info['order_id'])
            ->join(['mojing_order.fa_order_process'=> 'b'],'a.id=b.order_id','left')
            ->field('a.id,a.increment_id,b.store_house_id')
            ->find();
        empty($order_process_info) && $this->error(__('主订单不存在'), [], 403);

        //获取库位信息，判断是否被占用
        $store_house_info = $_stock_house->field('id,coding,subarea,occupy')->where('id',$store_house_id)->find();//查询合单库位--占用数量
        empty($store_house_info) && $this->error(__('合单库位不存在'), [], 403);

        if ($store_house_info['occupy'] && empty($order_process_info['store_house_id'])){
            //主单无绑定库位，且分配的库位被占用，重新分配合单库位后再次提交确认放入新分配合单架
            $new_store_house_info = $_stock_house->field('id,coding,subarea')->where(['status'=>1,'type'=>2,'occupy'=>0])->find();
            empty($new_store_house_info) && $this->error(__('合单库位已用完，请检查合单库位情况'), [], 403);

            $info['store_id'] = $new_store_house_info['id'];
            $this->error(__('合单架'.$store_house_info['coding'].'库位已被占用，'.'请将子单号'.$item_order_number.'的商品放入新合单架'.$new_store_house_info['coding'].'库位'), ['info' => $info], 403);
        }

        if ($item_process_info['distribution_status'] == 8){
            //重复扫描子单号--提示语句
            $this->error(__('请将子单号'.$item_order_number.'的商品放入合单架'.$store_house_info['coding'].'库位'), [], 511);
        }

        //主单表有合单库位ID，查询主单商品总数，与子单合单入库计算数量对比
        //获取订单购买总数
        $_new_order = new \app\admin\model\order\order\NewOrder();
        $total_qty_ordered = $_new_order
            ->where('id', $item_process_info['order_id'])
            ->value('total_qty_ordered')
        ;
        $count = $_new_order_item_process->where(['distribution_status'=>8,'order_id'=>$item_process_info['order_id']])->count();

        $info['order_id'] = $item_process_info['order_id'];//合单确认放入合单架提交 接口返回自带主订单号

        if($order_process_info['store_house_id']){
            //存在合单库位ID，获取合单库位号存入
            if ($total_qty_ordered > $count){
                //不是最后一个子单
                $num = '';
                $next = 1;//是否有下一个子单 1有，0没有
            } else {
                //最后一个子单
                $num = '最后一个';
                $next = 0;//是否有下一个子单 1有，0没有
            }
            $info['next'] = $next;
            //更新子单表
            $result = false;
            $result = $_new_order_item_process->allowField(true)->isUpdate(true, ['item_order_number'=>$item_order_number])->save(['distribution_status'=>8]);
            if ($result != false){
                //操作成功记录
                DistributionLog::record($this->auth,$item_process_info['id'],7,'子单号：'.$item_order_number.'作为主单号'.$order_process_info['increment_id'].'的'.$num.'子单合单完成');
                if (!$next){
                    //最后一个子单且合单完成，更新主单、子单状态为合单完成
                    $_new_order_item_process->allowField(true)->isUpdate(true, ['item_order_number'=>$item_order_number])->save(['distribution_status'=>9]);
                    $_new_order_process = new \app\admin\model\order\order\NewOrderProcess();
                    $_new_order_process->allowField(true)->isUpdate(true, ['order_id'=>$item_process_info['order_id']])->save(['combine_status'=>1,'combine_time'=>time()]);
                }

                $this->success('子单号放入合单架成功', ['info'=>$info], 200);
            } else {
                //操作失败记录
                DistributionLog::record($this->auth,$item_process_info['id'],7,'子单号：'.$item_order_number.'作为主单号'.$order_process_info['increment_id'].'的'.$num.'子单合单失败');

                $this->error(__('No rows were inserted'), [], 511);
            }
        }

        //首个子单进入合单架START
        $result = false;
        $return = false;
        Db::startTrans();
        try {
            //更新子单表
            $result = $_new_order_item_process->allowField(true)->isUpdate(true, ['item_order_number'=>$item_order_number])->save(['distribution_status'=>8]);
            if ($result != false){
                $res = $_new_order_process->allowField(true)->isUpdate(true, ['id'=>$item_process_info['order_id']])->save(['store_house_id'=>$store_house_id]);
                if ($res != false){
                    $return = $_stock_house->allowField(true)->isUpdate(true, ['id'=>$store_house_id])->save(['occupy'=>1]);
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
        if ($return !== false) {
            if ($total_qty_ordered == 1){
                //只有一个子单
                $num = '';
                $next = 0;//是否有下一个子单 1有，0没有
            } else {
                //多个子单的首个子单
                $num = '首个';
                $next = 1;//是否有下一个子单 1有，0没有
            }
            if (!$next){
                //只有一个子单且合单完成，更新主单、子单状态为合单完成
                $_new_order_item_process->allowField(true)->isUpdate(true, ['item_order_number'=>$item_order_number])->save(['distribution_status'=>9]);
                $_new_order_process = new \app\admin\model\order\order\NewOrderProcess();
                $_new_order_process->allowField(true)->isUpdate(true, ['order_id'=>$item_process_info['order_id']])->save(['combine_status'=>1,'combine_time'=>time()]);
            }
            $info['next'] = $next;
            //操作成功记录
            DistributionLog::record($this->auth,$item_process_info['id'],7,'子单号：'.$item_order_number.'作为主单号'.$order_process_info['increment_id'].'的'.$num.'子单合单完成');

            $this->success('子单号放入合单架成功', ['info'=>$info], 200);
        } else {
            //操作失败记录
            DistributionLog::record($this->auth,$item_process_info['id'],7,'子单号：'.$item_order_number.'作为主单号'.$order_process_info['increment_id'].'的'.$num.'子单合单失败');

            $this->error(__('No rows were inserted'), [], 511);
        }
        //首个子单进入合单架END

    }

    /**
     * 合单--合单完成页面-------修改原型图待定---子单合单状态、异常状态展示
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
        $_new_order_process = new \app\admin\model\order\order\NewOrderProcess();
        $_new_order = new \app\admin\model\order\order\NewOrder();
        $order_process_info = $_new_order
            ->alias('a')
            ->where('a.increment_id', $order_number)
            ->join(['mojing_order.fa_order_process'=> 'b'],'a.id=b.order_id','left')
            ->field('a.id,a.increment_id,b.store_house_id')
            ->find();
        empty($order_process_info) && $this->error(__('主订单不存在'), [], 403);

        //获取子订单数据
        $_new_order_item_process = new \app\admin\model\order\order\NewOrderItemProcess();
        $item_process_info = $_new_order_item_process
            ->where('order_id', $order_process_info['id'])
            ->field('id,item_order_number,distribution_status,abnormal_house_id')
            ->select();
        empty($item_process_info) && $this->error(__('子订单数据异常'), [], 403);

        $distribution_status = [1=>'待打印标签',2=>'待配货',3=>'待配镜片',4=>'待加工',5=>'待印logo',6=>'待成品质检',7=>'待合单',8=>'合单中',9=>'合单完成'];
        foreach($item_process_info as $key => $value){
            $item_process_info[$key]['distribution_status'] = $distribution_status[$value['distribution_status']];//子单合单状态
            $item_process_info[$key]['abnormal_house_id'] = 0 == $value['abnormal_house_id'] ? '正常' : '异常';//异常状态
        }
        $info['order_number'] = $order_number;
        $info['list'] = $item_process_info;
        $this->success('', ['info'=>$info], 200);

    }


    /**
     * 合单--合单完成提交-------修改原型图待定----合单完成（下一步）、失败（展示失败原因）---产品修改ing---可能删除不用，在最后一个子单合单完成时判断更改
     *
     * @参数 string order_number  主订单号
     * @author wgj
     * @return mixed
     */
    public function merge_submit()
    {
        $order_number = $this->request->request('order_number');
        empty($order_number) && $this->error(__('订单号不能为空'), [], 403);

        //获取订单购买总数,商品总数即为子单数量
        $_stock_house = new \app\admin\model\warehouse\StockHouse;
        $_new_order = new \app\admin\model\order\order\NewOrder();

        $order_process_info = $_new_order
            ->alias('a')
            ->where('a.increment_id', $order_number)
            ->join(['mojing_order.fa_order_process'=> 'b'],'a.id=b.order_id','left')
            ->field('a.id,a.increment_id,b.store_house_id')
            ->find();
        empty($order_process_info) && $this->error(__('订单不存在'), [], 403);

        //获取子订单数据----验证子单状态
        $_new_order_item_process = new \app\admin\model\order\order\NewOrderItemProcess();
        $item_process_info = $_new_order_item_process
            ->where('order_id', $order_process_info['id'])
            ->field('id,item_order_number,distribution_status,abnormal_house_id')
            ->select();
        empty($item_process_info) && $this->error(__('子订单数据异常'), [], 403);

        $_new_order_item_process = new \app\admin\model\order\order\NewOrderItemProcess();
        $item_process_info = $_new_order_item_process
            ->where('order_number', $order_number)
            ->field('id,distribution_status,order_id')
            ->select();
        empty($item_process_info) && $this->error(__('订单数据异常'), [], 403);

        foreach($item_process_info as $key => $value){
        }

        //获取库位信息，判断是否被占用
        $store_house_info = $_stock_house->field('id,coding,subarea,occupy')->where('id',$order_process_info['store_house_id'])->find();//查询合单库位--占用数量


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

        $where = [];
        $where['a.combine_status'] = 1;//合单中状态
        $offset = ($page - 1) * $page_size;
        $limit = $page_size;

        $_new_order_process = new \app\admin\model\order\order\NewOrderProcess();
        if ($type == 1){
            //合单待取出列表，主单为合单完成状态且子单都已合单
            if($query){
                $where['b.sku|c.coding'] = ['like', '%' . $query . '%'];
            }
            if($start_time && $end_time){
                $where['a.combine_time'] = ['between', [$start_time, $end_time]];
            }

            $list = $_new_order_process
                ->alias('a')
                ->where($where)
                ->join(['mojing_order.fa_order_item_process'=> 'b'],'a.order_id=b.order_id','left')
                ->join(['mojing.fa_store_house'=> 'c'],'a.store_house_id=c.id','left')
                ->field('a.order_id,c.coding,a.combine_time')
                ->limit($offset, $limit)
                ->select();
            empty($list) && $this->error(__('订单不存在'), [], 403);

        } else {
            //异常待处理列表
            if($query){
                $where['b.item_order_number|c.coding'] = ['like', '%' . $query . '%'];
            }
            $where['b.abnormal_house_id'] = ['>',0];
            $list = $_new_order_process
                ->alias('a')
                ->where($where)
                ->join(['mojing_order.fa_order_item_process'=> 'b'],'a.order_id=b.order_id','left')
                ->join(['mojing.fa_store_house'=> 'c'],'b.abnormal_house_id=c.id','left')
                ->field('c.coding,b.item_order_number')
                ->limit($offset, $limit)
                ->select();
            empty($list) && $this->error(__('暂无合单异常待处理'), [], 403);
        }

        $info['list'] = $list;
        $this->success('', ['list' => $list],200);
    }

    /**
     * 合单取出---释放库位[1.正常状态取出释放合单库位，异常单则回退其主单下的所有子单为待合单状态并释放合单库位]
     *
     * @参数 string order_number  主订单号
     * @author wgj
     * @return mixed
     */
    public function merge_out_submit()
    {
        $type = $this->request->request("type") ?? 1;
        $order_number = $this->request->request('order_number');
        empty($order_number) && $this->error(__('主订单号不能为空'), [], 403);

        //取出时只需传order_number主订单号
        //获取主单库位信息
        $_new_order_process = new \app\admin\model\order\order\NewOrderProcess();
        $_new_order_item_process = new \app\admin\model\order\order\NewOrderItemProcess();
        $_new_order = new \app\admin\model\order\order\NewOrder();
        $_stock_house = new \app\admin\model\warehouse\StockHouse;
        $order_process_info = $_new_order
            ->alias('a')
            ->where('a.increment_id', $order_number)
            ->join(['mojing_order.fa_order_process'=> 'b'],'a.id=b.order_id','left')
            ->field('a.id,b.combine_status,b.store_house_id')
            ->find();
        empty($order_process_info) && $this->error(__('主订单不存在'), [], 403);

        if ($order_process_info['combine_status'] == 1 && $order_process_info['store_house_id'] != 0){
            //合单完成释放合单库位
            $result = false;
            Db::startTrans();
            try {
                //更新订单业务处理表，解绑库位号
                $result = $_new_order_process->allowField(true)->isUpdate(true, ['order_id'=>$order_process_info['id']])->save(['store_house_id'=>0]);
                if ($result != false){
                    //释放合单库位占用数量
                    $res = $_stock_house->allowField(true)->isUpdate(true, ['id'=>$order_process_info['store_house_id']])->save(['occupy'=>0]);
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
            if ($res !== false) {
                //操作成功记录，批量日志插入
                $item_process_info = $_new_order_item_process->field('id,item_order_number')->where('order_id',$order_number)->select();
                foreach($item_process_info as $key => $value){
                    DistributionLog::record($this->auth,$value['id'],7,'子单号：'.$value['item_order_number'].'作为主单号'.$order_number.'的子单取出合单库完成');
                }

                $this->success('合单取出成功', [], 200);
            } else {
                //操作失败记录，批量日志插入
                $item_process_info = $_new_order_item_process->field('id,item_order_number')->where('order_id',$order_number)->select();
                foreach($item_process_info as $key => $value){
                    DistributionLog::record($this->auth,$value['id'],7,'子单号：'.$value['item_order_number'].'作为主单号'.$order_number.'的子单取出合单库失败');
                }

                $this->error(__('No rows were inserted'), [], 511);
            }
        } else {

        }



    }

    /**
     * 合单--合单完成页面--合单待取详情页面--修改原型图待定---子单合单状态、异常状态展示--ok
     *
     * @参数 int type  待取出类型 1 合单 2异常
     * @参数 string order_number  主订单号
     * @参数 string item_order_number  子单号
     * @author wgj
     * @return mixed
     */
    public function merge_out_detail()
    {
        $type = $this->request->request("type") ?? 1;
        $item_order_number = $this->request->request('item_order_number');
        $order_number = $this->request->request('order_number');

        $_new_order_process = new \app\admin\model\order\order\NewOrderProcess();
        $_new_order_item_process = new \app\admin\model\order\order\NewOrderItemProcess();
        $_new_order = new \app\admin\model\order\order\NewOrder();
        if ($type == 1){
            empty($order_number) && $this->error(__('主订单号不能为空'), [], 403);
            $order_process_info = $_new_order
                ->alias('a')
                ->where('a.increment_id', $order_number)
                ->join(['mojing_order.fa_order_process'=> 'b'],'a.id=b.order_id','left')
                ->field('a.id,a.increment_id,b.store_house_id')
                ->find();
            empty($order_process_info) && $this->error(__('主订单不存在'), [], 403);

            //获取子订单数据
            $item_process_info = $_new_order_item_process
                ->where('order_id', $order_process_info['id'])
                ->field('id,item_order_number,distribution_status,abnormal_house_id')
                ->select();
            empty($item_process_info) && $this->error(__('子订单数据异常'), [], 403);
            $info['order_number'] = $order_number;

        } else {
            empty($item_order_number) && $this->error(__('子订单号不能为空'), [], 403);
            //子单号获取主单号
            $order_id = $_new_order_item_process->where(['item_order_number' => $item_order_number])->value('order_id');
            $order_number = $_new_order->where(['id' => $order_id])->value('increment_id');
            empty($order_number) && $this->error(__('子订单数据异常'), [], 403);
            $info['order_number'] = $order_number;

            //获取子订单数据
            $_new_order_item_process = new \app\admin\model\order\order\NewOrderItemProcess();
            $item_process_info = $_new_order_item_process
                ->where('order_id', $order_id)
                ->field('id,item_order_number,distribution_status,abnormal_house_id')
                ->select();
            empty($item_process_info) && $this->error(__('子订单数据异常'), [], 403);
        }

        $distribution_status = [1=>'待打印标签',2=>'待配货',3=>'待配镜片',4=>'待加工',5=>'待印logo',6=>'待成品质检',7=>'待合单',8=>'合单中',9=>'合单完成'];
        foreach($item_process_info as $key => $value){
            $item_process_info[$key]['distribution_status'] = $distribution_status[$value['distribution_status']];//子单合单状态
            $item_process_info[$key]['abnormal_house_id'] = 0 == $value['abnormal_house_id'] ? '正常' : '异常';//异常状态
        }

        $info['list'] = $item_process_info;
        $this->success('', ['info'=>$info], 200);

    }

}
