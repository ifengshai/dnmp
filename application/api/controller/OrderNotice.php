<?php
namespace app\api\controller;
use app\admin\model\DistributionAbnormal;
use app\admin\model\DistributionLog;
use app\admin\model\order\order\NewOrder;
use app\admin\model\warehouse\StockHouse;
use app\common\controller\Api;
use app\admin\model\order\order\NewOrderItemProcess;
use app\admin\model\order\order\NewOrderProcess;
use think\Exception;
use think\exception\PDOException;
use think\exception\ValidateException;

class OrderNotice extends Api{
    protected $noNeedLogin = ['cancel_order'];
    public function _initialize()
    {
        parent::_initialize();
        $this->order = new NewOrder();
        $this->orderprocess = new NewOrderProcess();
        $this->orderitemprocess = new NewOrderItemProcess();
        $this->_stock_house = new StockHouse();
        $this->_distribution_abnormal = new DistributionAbnormal();
    }
    public function cancel_order()
    {
        $site = $this->request->request('site');
        $increment_id = $this->request->request('increment_id');
        if(empty($site) || empty($increment_id)){
            $this->error(__('参数错误'), [], 405);
        }
        //求出fa_order 里面的订单ID
        $fa_order_id = $this->order->where(['site'=>$site,'increment_id'=>$increment_id])->value('id');
        if(!$fa_order_id){
            $this->error(__('订单数据不存在'),[],403);
        }
        //获取子订单数据
        $item_process_info = $this->orderitemprocess
            ->field('id,item_order_number,abnormal_house_id,stock_id')
            ->where('order_id','=', $fa_order_id)
            ->select();
        empty($item_process_info) && $this->error(__('子订单不存在'), [], 403);
        $item_process_info = collection($item_process_info)->toArray();
        foreach($item_process_info as $v){
            $this->handleCancelOrder($v,18);
            $this->_distribution_abnormal = new DistributionAbnormal();
            $this->orderitemprocess = new NewOrderItemProcess();
            $this->_stock_house = new StockHouse();
        }
        $this->success(__("操作成功"), [], 200);

    }
    private function handleCancelOrder($item_process_info,$type)
    {
        //参数错误
        if(empty($type) || empty($item_process_info)){
            return false;
        }
        //分配过库存，已经标记异常
        if(!empty($item_process_info['abnormal_house_id'])){
            return false;
        }
        $item_process_id = $item_process_info['id'];
        //自动分配异常库位号
        $stock_house_info = $this->_stock_house
            ->field('id,coding')
            ->where(['status' => 1, 'type' => 4, 'stock_id' => $item_process_info['stock_id'], 'occupy' => ['<', 10000]])
            ->order('occupy', 'desc')
            ->find();
        if (empty($stock_house_info)) { //异常库位没有了
            DistributionLog::record($this->auth, $item_process_id, 0, '异常暂存架没有空余库位');
            return  false;
        }
        $this->auth='Admin';
        $this->_distribution_abnormal->startTrans();
        $this->orderitemprocess->startTrans();
        try {
            //绑定异常子单号
            $abnormal_data = [
                'item_process_id' => $item_process_id,
                'type'            => $type,
                'status'          => 1,
                'create_time'     => time(),
                'create_person'   => 'Admin',
            ];

            $this->_distribution_abnormal->allowField(true)->save($abnormal_data);

            //子订单绑定异常库位号
            $this->orderitemprocess
                ->allowField(true)
                ->isUpdate(true, ['item_order_number' => $item_process_info['item_order_number']])
                ->save(['abnormal_house_id' => $stock_house_info['id']]);

            //异常库位占用数量+1
            $this->_stock_house
                ->where(['id' => $stock_house_info['id']])
                ->setInc('occupy', 1);

            //配货日志

            DistributionLog::record($this->auth, $item_process_id, 9, "子单号{$item_process_info['item_order_number']}，异常暂存架{$stock_house_info['coding']}库位");

            //提交事务
            $this->_distribution_abnormal->commit();
            $this->orderitemprocess->commit();
        } catch (ValidateException $e) {
            $this->_distribution_abnormal->rollback();
            $this->orderitemprocess->rollback();
            $this->error($e->getMessage(), [], 406);
        } catch (PDOException $e) {
            $this->_distribution_abnormal->rollback();
            $this->orderitemprocess->rollback();
            $this->error($e->getMessage(), [], 407);
        } catch (Exception $e) {
            $this->_distribution_abnormal->rollback();
            $this->orderitemprocess->rollback();
            $this->error($e->getMessage(), [], 408);
        }
    }
}