<?php

namespace app\admin\controller\datacenter;

use app\common\controller\Backend;

/**
 * 数据中心
 *
 * @icon fa fa-circle-o
 */
class Index extends Backend
{

    /**
     * 无需鉴权的方法,但需要登录
     * @var array
     */
    protected $noNeedRight = [];


    /**
     * Index模型对象
     * @var 
     */
    protected $model = null;

    public function _initialize()
    {
        parent::_initialize();

        $this->zeelool = new \app\admin\model\order\order\Zeelool;
        $this->voogueme = new \app\admin\model\order\order\Voogueme;
        $this->nihao = new \app\admin\model\order\order\Nihao;
        $this->itemplatformsku = new \app\admin\model\itemmanage\ItemPlatformSku;
        $this->item = new \app\admin\model\itemmanage\Item;
        $this->lens = new \app\admin\model\lens\Index;
    }

    /**
     * 默认生成的控制器所继承的父类中有index/add/edit/del/multi五个基础方法、destroy/restore/recyclebin三个回收站方法
     * 因此在当前控制器中可不用编写增删改查的代码,除非需要自己控制这部分逻辑
     * 需要将application/admin/library/traits/Backend.php中对应的方法复制到当前控制器,然后进行修改
     */

    /**
     * 销量统计
     *
     * @Description
     * @author wpl
     * @since 2020/02/21 14:20:44 
     * @return void
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

            //统计三个站销量
            //自定义时间搜索
            $filter = json_decode($this->request->get('filter'), true);
            if ($filter['created_at']) {
                $createat = explode(' ', $filter['created_at']);
                $map['a.created_at'] = ['between', [$createat[0] . ' ' . $createat[1], $createat[3]  . ' ' . $createat[4]]];
                unset($filter['created_at']);
                $this->request->get(['filter' => json_encode($filter)]);
            } else {
                $map['a.created_at'] = ['between', [date("Y-m-d 00:00:00"), date("Y-m-d H:i:s", time())]];
            }
            list($where, $sort, $order, $offset, $limit) = $this->buildparams();

            $total = $this->item
                ->where($where)
                ->order($sort, $order)
                ->count();

            $list = $this->item
                ->where($where)
                ->order($sort, $order)
                ->limit($offset, $limit)
                ->select();

            foreach ($list as &$v) {
                //sku转换
                $v['z_sku'] = $this->itemplatformsku->getWebSku($v['sku'], 1);

                $v['v_sku'] = $this->itemplatformsku->getWebSku($v['sku'], 2);

                $v['n_sku'] = $this->itemplatformsku->getWebSku($v['sku'], 3);
            }
            unset($v);

            $z_sku = array_column($list, 'z_sku');
            $v_sku = array_column($list, 'v_sku');
            $n_sku = array_column($list, 'n_sku');

            //获取三个站销量数据
            $zeelool = $this->zeelool->getOrderSalesNum($z_sku, $map);
            $voogueme = $this->voogueme->getOrderSalesNum($v_sku, $map);
            $nihao = $this->nihao->getOrderSalesNum($n_sku, $map);
            //重组数组
            foreach ($list as &$v) {

                $v['z_num'] = round($zeelool[$v['z_sku']]) ?? 0;

                $v['v_num'] = round($voogueme[$v['v_sku']]) ?? 0;

                $v['n_num'] = round($nihao[$v['n_sku']]) ?? 0;

                $v['all_num'] = $v['z_num'] + $v['v_num'] + $v['n_num'];
            }
            unset($v);

            $list = collection($list)->toArray();
            $result = array("total" => $total, "rows" => $list);

            return json($result);
        }
        return $this->view->fetch();
    }

    /**
     * 供应链数据大屏
     *
     * @Description
     * @author wpl
     * @since 2020/02/21 14:23:45 
     * @return void
     */
    public function supply_chain_data()
    {
        //仓库总库存
        $cachename = 'supply_chain_data_' . 'allStock';
        $allStock = cache($cachename);
        if (!$allStock) {
            $allStock = $this->item->getAllStock();
            cache($cachename, $allStock, 86400);
        }

        //仓库库存总金额       
        $cachename = 'supply_chain_data_' . 'allStockPrice';
        $allStockPrice = cache($cachename);
        if (!$allStockPrice) {
            $allStockPrice = $this->item->getAllStockPrice();
            cache($cachename, $allStockPrice, 86400);
        }

        //镜架库存统计
        $cachename = 'supply_chain_data_' . 'frameStock';
        $frameStock = cache($cachename);
        if (!$frameStock) {
            $frameStock = $this->item->getFrameStock();
            cache($cachename, $frameStock, 86400);
        }

        //镜架总金额 
        $cachename = 'supply_chain_data_' . 'frameStockPrice';
        $frameStockPrice = cache($cachename);
        if (!$frameStockPrice) {
            $frameStockPrice = $this->item->getFrameStockPrice();
            cache($cachename, $frameStockPrice, 86400);
        }

        //镜片库存
        $cachename = 'supply_chain_data_' . 'lensStock';
        $lensStock = cache($cachename);
        if (!$lensStock) {
            $lensStock = $this->lens->getLensStock();
            cache($cachename, $lensStock, 86400);
        }

        //镜片库存总金额
        $cachename = 'supply_chain_data_' . 'lensStockPrice';
        $lensStockPrice = cache($cachename);
        if (!$lensStockPrice) {
            $lensStockPrice = $this->lens->getLensStockPrice();
            cache($cachename, $lensStockPrice, 86400);
        }

        //饰品库存
        $cachename = 'supply_chain_data_' . 'ornamentsStock';
        $ornamentsStock = cache($cachename);
        if (!$ornamentsStock) {
            $ornamentsStock = $this->item->getOrnamentsStock();
            cache($cachename, $ornamentsStock, 86400);
        }
        //饰品库存总金额
        $cachename = 'supply_chain_data_' . 'ornamentsStockPrice';
        $ornamentsStockPrice = cache($cachename);
        if (!$ornamentsStockPrice) {
            $ornamentsStockPrice = $this->item->getOrnamentsStockPrice();
            cache($cachename, $ornamentsStockPrice, 86400);
        }

        //样品库存
        $cachename = 'supply_chain_data_' . 'SampleNumStock';
        $sampleNumStock = cache($cachename);
        if (!$sampleNumStock) {
            $sampleNumStock = $this->item->getSampleNumStock();
            cache($cachename, $sampleNumStock, 86400);
        }
        //样品库存总金额
        $cachename = 'supply_chain_data_' . 'SampleNumStockPrice';
        $sampleNumStockPrice = cache($cachename);
        if (!$sampleNumStockPrice) {
            $sampleNumStockPrice = $this->item->getSampleNumStockPrice();
            cache($cachename, $sampleNumStockPrice, 86400);
        }

        //查询总SKU数
        $cachename = 'supply_chain_data_' . 'SkuNum';
        $skuNum = cache($cachename);
        if (!$skuNum) {
            $skuNum = $this->item->getSkuNum();
            cache($cachename, $skuNum, 86400);
        }

        //查询待处理事件 售后未处理 + 协同未处理
        $cachename = 'supply_chain_data_' . 'taskAllNum';
        $taskAllNum = cache($cachename);
        if (!$taskAllNum) {
            //售后事件个数
            $saleTask = new \app\admin\model\saleaftermanage\SaleAfterTask;
            $salesNum = $saleTask->getTaskNum();

            //查询协同任务待处理事件
            $infoTask = new \app\admin\model\infosynergytaskmanage\InfoSynergyTask();
            $infoNum = $infoTask->getTaskNum();
            $taskAllNum = $salesNum + $infoNum;
            cache($cachename, $taskAllNum, 3600);
        }

        //三个站待处理订单
        $cachename = 'supply_chain_data_' . 'allPendingOrderNum';
        $allPendingOrderNum = cache($cachename);
        if (!$allPendingOrderNum) {
            $zeeloolNum = $this->zeelool->getPendingOrderNum();
            $vooguemeNum = $this->voogueme->getPendingOrderNum();
            $nihaoNum = $this->nihao->getPendingOrderNum();
            $allPendingOrderNum = $zeeloolNum + $vooguemeNum + $nihaoNum;
            cache($cachename, $allPendingOrderNum, 14400);
        }

        /***
         * 库存周转天数 库存周转率
         * 库存周转天数 = 7*(期初总库存+期末总库存)/2/7天总销量
         * 库存周转率 =  360/库存周转天数
         */

        //查询最近7天总销量
        $orderStatistics = new \app\admin\model\OrderStatistics();
        $stime = date("Y-m-d", strtotime("-7 day"));
        $etime = date("Y-m-d", strtotime("-1 day"));
        $map['create_date'] = ['between', [$stime, $etime]];
        $allSalesNum = $orderStatistics->where($map)->sum('all_sales_num');

        //期初总库存
        $productAllStockLog = new \app\admin\model\ProductAllStock();
        $start7days = $productAllStockLog->where('createtime', 'like', $stime . '%')->value('allnum');
        $end7days = $productAllStockLog->where('createtime', 'like', $etime . '%')->value('allnum');
        //库存周转天数
        $stock7days = round(7 * ($start7days + $end7days) / 2 / $allSalesNum, 2);
        //库存周转率
        $stock7daysPercent = round(360 / $stock7days, 2);

        //在途库存
        $onwayAllStock = $this->onway_all_stock();

        //在途库存总金额
        $onwayAllStockPrice = $this->onway_all_stock_price();

        //在途镜架库存
        $onwayFrameAllStock = $this->onway_frame_all_stock();

        //在途镜架库存总金额
        $onwayFrameAllStockPrice = $this->onway_frame_all_stock_price();

        //在途饰品库存
        $onwayOrnamentAllStock = $this->onway_ornament_all_stock();

        //在途饰品库存总金额
        $onwayOrnamentAllStockPrice = $this->onway_ornament_all_stock_price();

        $this->view->assign('allStock', $allStock);
        $this->view->assign('allStockPrice', $allStockPrice);
        $this->view->assign('frameStock', $frameStock);
        $this->view->assign('frameStockPrice', $frameStockPrice);
        $this->view->assign('lensStock', $lensStock);
        $this->view->assign('lensStockPrice', $lensStockPrice);
        $this->view->assign('ornamentsStock', $ornamentsStock);
        $this->view->assign('ornamentsStockPrice', $ornamentsStockPrice);
        $this->view->assign('sampleNumStock', $sampleNumStock);
        $this->view->assign('sampleNumStockPrice', $sampleNumStockPrice);
        $this->view->assign('skuNum', $skuNum);
        $this->view->assign('taskAllNum', $taskAllNum);
        $this->view->assign('allPendingOrderNum', $allPendingOrderNum);
        $this->view->assign('stock7days', $stock7days);
        $this->view->assign('stock7daysPercent', $stock7daysPercent);
        $this->view->assign('onway_all_stock', $onwayAllStock);
        $this->view->assign('onway_all_stock_price', $onwayAllStockPrice);
        $this->view->assign('onway_frame_all_stock', $onwayFrameAllStock);
        $this->view->assign('onway_frame_all_stock_price', $onwayFrameAllStockPrice);
        $this->view->assign('onway_ornament_all_stock', $onwayOrnamentAllStock);
        $this->view->assign('onway_ornament_all_stock_price', $onwayOrnamentAllStockPrice);

        return $this->view->fetch();
    }

    /**
     * 在途库存
     *
     * @Description
     * @author wpl
     * @since 2020/03/02 17:20:21 
     * @return void
     */
    protected function onway_all_stock()
    {
        //计算SKU总采购数量
        $purchase = new \app\admin\model\purchase\PurchaseOrder;
        $purchase_map['purchase_status'] = ['in', [2, 5, 6, 7]];
        $purchase_map['stock_status'] = ['in', [0, 1]];
        $purchase_num = $purchase->alias('a')->join(['fa_purchase_order_item' => 'b'], 'a.id=b.purchase_id')
            ->where($purchase_map)
            ->whereExp('sku', 'is not null')
            ->cache(86400)
            ->sum('purchase_num');

        $check_map['a.status'] = 2;
        $check_map['a.type'] = 1;
        $check_map['b.purchase_status'] = ['in', [2, 5, 6, 7]];
        $check_map['b.stock_status'] = ['in', [0, 1]];
        $check = new \app\admin\model\warehouse\Check;
        $arrivals_num = $check->alias('a')
            ->where($check_map)
            ->join(['fa_purchase_order' => 'b'], 'b.id=a.purchase_id')
            ->join(['fa_check_order_item' => 'c'], 'a.id=c.check_id')
            ->cache(86400)
            ->sum('arrivals_num');
        return $purchase_num - $arrivals_num;
    }

    /**
     * 在途库存总金额
     *
     * @Description
     * @author wpl
     * @since 2020/03/02 17:20:21 
     * @return void
     */
    protected function onway_all_stock_price()
    {
        //计算SKU总采购金额
        $purchase = new \app\admin\model\purchase\PurchaseOrder;
        $purchase_map['purchase_status'] = ['in', [2, 5, 6, 7]];
        $purchase_map['stock_status'] = ['in', [0, 1]];
        $purchase_price = $purchase->alias('a')->join(['fa_purchase_order_item' => 'b'], 'a.id=b.purchase_id')
            ->where($purchase_map)
            ->whereExp('sku', 'is not null')
            ->cache(86400)
            ->sum('purchase_num*purchase_price');

        $check_map['a.status'] = 2;
        $check_map['a.type'] = 1;
        $check_map['b.purchase_status'] = ['in', [2, 5, 6, 7]];
        $check_map['b.stock_status'] = ['in', [0, 1]];
        $check = new \app\admin\model\warehouse\Check;
        $arrivals_price = $check->alias('a')
            ->where($check_map)
            ->join(['fa_purchase_order' => 'b'], 'b.id=a.purchase_id')
            ->join(['fa_check_order_item' => 'c'], 'a.id=c.check_id')
            ->join(['fa_purchase_order_item' => 'd'], 'd.purchase_id=c.purchase_id and c.sku=d.sku', 'left')
            ->cache(86400)
            ->sum('arrivals_num*purchase_price');
        return $purchase_price - $arrivals_price;
    }

    /**
     * 在途镜架库存
     *
     * @Description
     * @author wpl
     * @since 2020/03/02 17:20:21 
     * @return void
     */
    protected function onway_frame_all_stock()
    {
        //镜架SKU
        $skus = $this->item->getFrameSku();
        if ($skus) {
            $purchase_map['sku'] = ['in', $skus];
            //计算SKU总采购数量
            $purchase = new \app\admin\model\purchase\PurchaseOrder;
            $purchase_map['purchase_status'] = ['in', [2, 5, 6, 7]];
            $purchase_map['stock_status'] = ['in', [0, 1]];
            $purchase_num = $purchase->alias('a')->join(['fa_purchase_order_item' => 'b'], 'a.id=b.purchase_id')
                ->where($purchase_map)
                ->whereExp('sku', 'is not null')
                ->cache(86400)
                ->sum('purchase_num');

            $check_map['c.sku'] = ['in', $skus];
            $check_map['a.status'] = 2;
            $check_map['a.type'] = 1;
            $check_map['b.purchase_status'] = ['in', [2, 5, 6, 7]];
            $check_map['b.stock_status'] = ['in', [0, 1]];
            $check = new \app\admin\model\warehouse\Check;
            $arrivals_num = $check->alias('a')
                ->where($check_map)
                ->join(['fa_purchase_order' => 'b'], 'b.id=a.purchase_id')
                ->join(['fa_check_order_item' => 'c'], 'a.id=c.check_id')
                ->cache(86400)
                ->sum('arrivals_num');
        }
        return $purchase_num - $arrivals_num;
    }

    /**
     * 在途镜架库存总金额
     *
     * @Description
     * @author wpl
     * @since 2020/03/02 17:20:21 
     * @return void
     */
    protected function onway_frame_all_stock_price()
    {
        //镜架SKU
        $skus = $this->item->getFrameSku();
        if ($skus) {
            $purchase_map['sku'] = ['in', $skus];
            //计算SKU总采购金额
            $purchase = new \app\admin\model\purchase\PurchaseOrder;
            $purchase_map['purchase_status'] = ['in', [2, 5, 6, 7]];
            $purchase_map['stock_status'] = ['in', [0, 1]];
            $purchase_price = $purchase->alias('a')->join(['fa_purchase_order_item' => 'b'], 'a.id=b.purchase_id')
                ->where($purchase_map)
                ->whereExp('sku', 'is not null')
                ->cache(86400)
                ->sum('purchase_num*purchase_price');

            //计算到货sku总金额
            $check_map['c.sku'] = ['in', $skus];
            $check_map['a.status'] = 2;
            $check_map['a.type'] = 1;
            $check_map['b.purchase_status'] = ['in', [2, 5, 6, 7]];
            $check_map['b.stock_status'] = ['in', [0, 1]];
            $check = new \app\admin\model\warehouse\Check;
            $arrivals_price = $check->alias('a')
                ->where($check_map)
                ->join(['fa_purchase_order' => 'b'], 'b.id=a.purchase_id')
                ->join(['fa_check_order_item' => 'c'], 'a.id=c.check_id')
                ->join(['fa_purchase_order_item' => 'd'], 'd.purchase_id=c.purchase_id and c.sku=d.sku', 'left')
                ->cache(86400)
                ->sum('arrivals_num*purchase_price');
        }

        return $purchase_price - $arrivals_price;
    }

    /**
     * 在途镜架库存
     *
     * @Description
     * @author wpl
     * @since 2020/03/02 17:20:21 
     * @return void
     */
    protected function onway_ornament_all_stock()
    {
        //镜架SKU
        $skus = $this->item->getOrnamentsSku();
        if ($skus) {
            $purchase_map['sku'] = ['in', $skus];
            //计算SKU总采购数量
            $purchase = new \app\admin\model\purchase\PurchaseOrder;
            $purchase_map['purchase_status'] = ['in', [2, 5, 6, 7]];
            $purchase_map['stock_status'] = ['in', [0, 1]];
            $purchase_num = $purchase->alias('a')->join(['fa_purchase_order_item' => 'b'], 'a.id=b.purchase_id')
                ->where($purchase_map)
                ->whereExp('sku', 'is not null')
                ->cache(86400)
                ->sum('purchase_num');

            $check_map['c.sku'] = ['in', $skus];
            $check_map['a.status'] = 2;
            $check_map['a.type'] = 1;
            $check_map['b.purchase_status'] = ['in', [2, 5, 6, 7]];
            $check_map['b.stock_status'] = ['in', [0, 1]];
            $check = new \app\admin\model\warehouse\Check;
            $arrivals_num = $check->alias('a')
                ->where($check_map)
                ->join(['fa_purchase_order' => 'b'], 'b.id=a.purchase_id')
                ->join(['fa_check_order_item' => 'c'], 'a.id=c.check_id')
                ->cache(86400)
                ->sum('arrivals_num');
        }
        return $purchase_num - $arrivals_num;
    }

    /**
     * 在途镜架库存总金额
     *
     * @Description
     * @author wpl
     * @since 2020/03/02 17:20:21 
     * @return void
     */
    protected function onway_ornament_all_stock_price()
    {
        //镜架SKU
        $skus = $this->item->getOrnamentsSku();
        if ($skus) {
            $purchase_map['sku'] = ['in', $skus];
            //计算SKU总采购金额
            $purchase = new \app\admin\model\purchase\PurchaseOrder;
            $purchase_map['purchase_status'] = ['in', [2, 5, 6, 7]];
            $purchase_map['stock_status'] = ['in', [0, 1]];
            $purchase_price = $purchase->alias('a')->join(['fa_purchase_order_item' => 'b'], 'a.id=b.purchase_id')
                ->where($purchase_map)
                ->whereExp('sku', 'is not null')
                ->cache(86400)
                ->sum('purchase_num*purchase_price');

            //计算到货sku总金额
            $check_map['c.sku'] = ['in', $skus];
            $check_map['a.status'] = 2;
            $check_map['a.type'] = 1;
            $check_map['b.purchase_status'] = ['in', [2, 5, 6, 7]];
            $check_map['b.stock_status'] = ['in', [0, 1]];
            $check = new \app\admin\model\warehouse\Check;
            $arrivals_price = $check->alias('a')
                ->where($check_map)
                ->join(['fa_purchase_order' => 'b'], 'b.id=a.purchase_id')
                ->join(['fa_check_order_item' => 'c'], 'a.id=c.check_id')
                ->join(['fa_purchase_order_item' => 'd'], 'd.purchase_id=c.purchase_id and c.sku=d.sku', 'left')
                ->cache(86400)
                ->sum('arrivals_num*purchase_price');
        }

        return $purchase_price - $arrivals_price;
    }

    /**
     * 仓库数据
     *
     * @Description
     * @author wpl
     * @since 2020/02/25 13:52:27 
     * @return void
     */
    public function warehouse_data()
    {
        //默认当天
        $create_time = input('create_time');
        if ($create_time) {
            $time = explode(' ', $create_time);
            $map['a.created_at'] = ['between', [$time[0] . ' ' . $time[1], $time[3] . ' ' . $time[4]]];
        } else {
            $map['a.created_at'] = ['between', [date('Y-m-d 00:00:00'), date('Y-m-d H:i:s', time())]];
        }

        //统计时间段内未发货订单
        $zeeloolUnorderNum = $this->zeelool->undeliveredOrder($map);
        $vooguemeUnorderNum = $this->voogueme->undeliveredOrder($map);
        $nihaoUnorderNum = $this->nihao->undeliveredOrder($map);

        //统计时间段内未发货订单副数
        $zeeloolNum = $this->zeelool->undeliveredOrderNum($map);
        $vooguemeNum = $this->voogueme->undeliveredOrderNum($map);
        $nihaoNum = $this->nihao->undeliveredOrderNum($map);

        //统计处方镜
        $zeeloolOrderPrescriptionNum = $this->zeelool->getOrderPrescriptionNum($map);
        $vooguemeOrderPrescriptionNum = $this->voogueme->getOrderPrescriptionNum($map);
        $nihaoOrderPrescriptionNum = $this->nihao->getOrderPrescriptionNum($map);

        //统计现货处方镜
        $zeeloolSpotOrderPrescriptionNum = $this->zeelool->getSpotOrderPrescriptionNum($map);
        $vooguemeSpotOrderPrescriptionNum = $this->voogueme->getSpotOrderPrescriptionNum($map);
        $nihaoSpotOrderPrescriptionNum = $this->nihao->getSpotOrderPrescriptionNum($map);

        //统计定制处方镜副数
        $zeeloolCustomOrderPrescriptionNum = $this->zeelool->getCustomOrderPrescriptionNum($map);
        $vooguemeCustomOrderPrescriptionNum = $this->voogueme->getCustomOrderPrescriptionNum($map);
        $nihaoCustomOrderPrescriptionNum = $this->nihao->getCustomOrderPrescriptionNum($map);

        //统计仅镜架订单
        $zeeloolFrameOrderNum = $this->zeelool->frameOrder($map);
        $vooguemeFrameOrderNum = $this->voogueme->frameOrder($map);
        $nihaoFrameOrderNum = $this->nihao->frameOrder($map);

        $this->view->assign('zeeloolUnorderNum', $zeeloolUnorderNum);
        $this->view->assign('vooguemeUnorderNum', $vooguemeUnorderNum);
        $this->view->assign('nihaoUnorderNum', $nihaoUnorderNum);
        $this->view->assign('zeeloolNum', $zeeloolNum);
        $this->view->assign('vooguemeNum', $vooguemeNum);
        $this->view->assign('nihaoNum', $nihaoNum);
        $this->view->assign('zeeloolOrderPrescriptionNum', $zeeloolOrderPrescriptionNum);
        $this->view->assign('vooguemeOrderPrescriptionNum', $vooguemeOrderPrescriptionNum);
        $this->view->assign('nihaoOrderPrescriptionNum', $nihaoOrderPrescriptionNum);
        $this->view->assign('zeeloolSpotOrderPrescriptionNum', $zeeloolSpotOrderPrescriptionNum);
        $this->view->assign('vooguemeSpotOrderPrescriptionNum', $vooguemeSpotOrderPrescriptionNum);
        $this->view->assign('nihaoSpotOrderPrescriptionNum', $nihaoSpotOrderPrescriptionNum);
        $this->view->assign('zeeloolCustomOrderPrescriptionNum', $zeeloolCustomOrderPrescriptionNum);
        $this->view->assign('vooguemeCustomOrderPrescriptionNum', $vooguemeCustomOrderPrescriptionNum);
        $this->view->assign('nihaoCustomOrderPrescriptionNum', $nihaoCustomOrderPrescriptionNum);
        $this->view->assign('zeeloolFrameOrderNum', $zeeloolFrameOrderNum);
        $this->view->assign('vooguemeFrameOrderNum', $vooguemeFrameOrderNum);
        $this->view->assign('nihaoFrameOrderNum', $nihaoFrameOrderNum);
        $this->view->assign('created_at', $create_time);
        return $this->view->fetch();
    }
}
