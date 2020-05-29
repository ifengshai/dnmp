<?php

namespace app\admin\model\purchase;

use think\Model;
use think\Db;

class PurchaseOrder extends Model
{



    //数据库
    protected $connection = 'database';
    // 表名
    protected $name = 'purchase_order';

    // 自动写入时间戳字段
    protected $autoWriteTimestamp = false;

    // 定义时间戳字段名
    protected $createTime = 'createtime';
    protected $updateTime = 'updatetime';
    protected $deleteTime = false;

    // 追加属性
    protected $append = [];


    /**
     * 获取采购单
     */
    public function getPurchaseData()
    {
        $where['purchase_status'] = ['in', [6, 7, 9]];
        $where['is_del'] = 1;
        $data = $this->where($where)->order('createtime desc')->column('purchase_number', 'id');
        return $data;
    }



    /**
     * 获取采购单
     */
    public function getPurchaseReturnData($check_status = [0, 1], $instock_status, $return_status = [])
    {
        if ($instock_status) {
            $where['stock_status'] = ['in', $instock_status];
        }

        if ($return_status) {
            $where['return_status'] = ['in', $return_status];
        }

        $where['purchase_status'] = ['in', [6, 7]];
        $where['check_status']  = ['in', $check_status];
        $data = $this->where($where)->order('createtime desc')->column('purchase_number', 'id');
        return $data;
    }


    /**
     * 采购单明细表
     */
    public function purchaseOrderItem()
    {
        return $this->hasMany('PurchaseOrderItem', 'purchase_id');
    }

    //关联模型
    public function supplier()
    {
        return $this->belongsTo('supplier', 'supplier_id', '', [], 'left')->setEagerlyType(0);;
    }

    /**
     * 获取供应商名称(废弃) create@lsw
     */
    public function fetchSupplierAccountPurchaseOrder($arr = [])
    {
        $map['id'] = ['in', $arr];
        $result = Db::name('supplier')->where($map)->field('id,supplier_name')->select();
        $info = collection($result)->toArray($result);
        if (!$info) {
            return false;
        }
        $arr = [];
        foreach ($info as $val) {
            $arr[$val['id']] = $arr[$val['supplier_name']];
        }
        return $arr;
    }
    /***
     * 求出总共的实际采购金额和本页面的实际采购金额 create@lsw
     */
    public function calculatePurchaseOrderMoney($totalArr = [], $thisPageIdArr = [])
    {
        if ((0 == count($totalArr)) || (0 == count($thisPageIdArr))) {
            return 0.00;
        }
        //求出有确认差异的采购单
        $where['id'] = ['in', $totalArr];
        $where['is_diff'] = 1;
        $trueTotalArr = $this->where($where)->column('id');
        //首先求出总的邮费
        $postAgeMap['id'] = ['in', $totalArr];
        $totalPostage = $this->where($postAgeMap)->field('sum(purchase_total) purchase_total,sum(purchase_freight) purchase_freight')->select();
        $totalPostage = collection($totalPostage)->toArray();
        $totalMap['p.purchase_id'] = ['in', $trueTotalArr];
        //求出所有的实际采购金额
        $arr = [];
        $arr['total_money'] = 0;
        $purchaseResult = Db::name('purchase_order_item')->alias('p')->where($totalMap)->join('check_order_item m', 'p.sku=m.sku and p.purchase_id = m.purchase_id')
            ->field('p.purchase_id,p.purchase_price,m.quantity_num,m.unqualified_num')->select();
        if (!$purchaseResult) {
            $arr['total_money'] = $totalPostage[0]['purchase_total'];
            $arr['thisPageArr'] = [];
            return $arr;
        }
        $purchaseResult = collection($purchaseResult)->toArray();
        foreach ($purchaseResult  as $v) {
            //$arr['total_money'] += round($v['purchase_price']*($v['quantity_num']+$v['unqualified_num']),2);
            if (in_array($v['purchase_id'], $thisPageIdArr)) {
                $arr['thisPageArr'][$v['purchase_id']] = round($v['purchase_price'] * ($v['quantity_num'] + $v['unqualified_num']), 2);
            }
        }
        $arr['total_money'] = $totalPostage[0]['purchase_total'];
        return $arr;
    }
    /***
     * 求出总共退款金额和本页面的实际退款金额 create@lsw
     */
    public function calculatePurchaseReturnMoney($totalArr = [], $thisPageIdArr = [])
    {
        if ((0 == count($totalArr)) || (0 == count($thisPageIdArr))) {
            return false;
        }
        $map['purchase_id'] = ['in', $totalArr];
        $returnResult = Db::name('purchase_return')->where($map)->field('purchase_id,round(sum(return_money),2) return_money')->group('purchase_id')->select();
        $arr = [];
        $arr['return_money'] = 0;
        if (!$returnResult) {
            $arr['thisPageArr'] = [];
            return $arr;
        }
        $returnResult = collection($returnResult)->toArray();
        foreach ($returnResult as $v) {
            $arr['return_money'] += $v['return_money'];
            if (in_array($v['purchase_id'], $thisPageIdArr)) {
                $arr['thisPageArr'][$v['purchase_id']] = $v['return_money'];
            }
        }
        return $arr;
    }
    /***
     * 求出采购单核算成本详情页面所需要的信息 create@lsw
     * @param id 采购单ID
     */
    public function getPurchaseOrderItemInfo($id)
    {
        $map['purchase_id'] = $id;
        Db::name('purchase_order_pay')->query("set time_zone='+8:00'");
        $info = Db::name('purchase_order_pay')->where($map)->select();
        if (!$info) {
            return false;
        }
        foreach ($info as $k => $v) {
            $info[$k]['pay_photos'] = explode(',', $v['pay_photos']);
        }
        return $info;
    }

    /**
     * 当月采购总数
     *
     * @Description
     * @author wpl
     * @since 2020/03/05 17:08:36 
     * @return void
     */
    public function getPurchaseNum()
    {
        $where['createtime'] = ['between', [date('Y-m-01 00:00:00', time()), date('Y-m-d H:i:s', time())]];
        $where['is_del'] = 1;
        $where['purchase_status'] = ['in', [2, 5, 6, 7]];
        return $this->alias('a')->where($where)->join(['fa_purchase_order_item' => 'b'], 'a.id=b.purchase_id')->sum('b.purchase_num');
    }

    /**
     * 当月线上采购数量
     *
     * @Description
     * @author wpl
     * @since 2020/03/05 17:08:36 
     * @return void
     */
    public function getOnlinePurchaseNum()
    {
        $where['createtime'] = ['between', [date('Y-m-01 00:00:00', time()), date('Y-m-d H:i:s', time())]];
        $where['is_del'] = 1;
        $where['purchase_type'] = 2;
        $where['purchase_status'] = ['in', [2, 5, 6, 7]];
        return $this->alias('a')->where($where)->join(['fa_purchase_order_item' => 'b'], 'a.id=b.purchase_id')->sum('b.purchase_num');
    }

    /**
     * 当月线下采购数量
     *
     * @Description
     * @author wpl
     * @since 2020/03/05 17:08:36 
     * @return void
     */
    public function getUnderPurchaseNum()
    {
        $where['createtime'] = ['between', [date('Y-m-01 00:00:00', time()), date('Y-m-d H:i:s', time())]];
        $where['is_del'] = 1;
        $where['purchase_type'] = 1;
        $where['purchase_status'] = ['in', [2, 5, 6, 7]];
        return $this->alias('a')->where($where)->join(['fa_purchase_order_item' => 'b'], 'a.id=b.purchase_id')->sum('b.purchase_num');
    }

    /**
     * 当月采购总金额
     *
     * @Description
     * @author wpl
     * @since 2020/03/05 17:08:36 
     * @return void
     */
    public function getPurchasePrice()
    {
        $where['createtime'] = ['between', [date('Y-m-01 00:00:00', time()), date('Y-m-d H:i:s', time())]];
        $where['is_del'] = 1;
        $where['purchase_status'] = ['in', [2, 5, 6, 7]];
        return $this->alias('a')->where($where)->join(['fa_purchase_order_item' => 'b'], 'a.id=b.purchase_id')->sum('purchase_num*purchase_price');
    }

    /**
     * 当月采购镜架总数
     *
     * @Description
     * @author wpl
     * @since 2020/03/05 17:08:36 
     * @return void
     */
    public function getPurchaseFrameNum()
    {
        //查询镜架SKU
        $item = new \app\admin\model\itemmanage\Item();
        $skus = $item->getFrameSku();
        $where['createtime'] = ['between', [date('Y-m-01 00:00:00', time()), date('Y-m-d H:i:s', time())]];
        $where['is_del'] = 1;
        $where['sku'] = ['in', $skus];
        $where['purchase_status'] = ['in', [2, 5, 6, 7]];
        return $this->alias('a')->where($where)->join(['fa_purchase_order_item' => 'b'], 'a.id=b.purchase_id')->sum('purchase_num');
    }

    /**
     * 当月采购镜架总金额
     *
     * @Description
     * @author wpl
     * @since 2020/03/05 17:08:36 
     * @return void
     */
    public function getPurchaseFramePrice()
    {
        //查询镜架SKU
        $item = new \app\admin\model\itemmanage\Item();
        $skus = $item->getFrameSku();
        $where['createtime'] = ['between', [date('Y-m-01 00:00:00', time()), date('Y-m-d H:i:s', time())]];
        $where['is_del'] = 1;
        $where['sku'] = ['in', $skus];
        $where['purchase_status'] = ['in', [2, 5, 6, 7]];
        return $this->alias('a')->where($where)->join(['fa_purchase_order_item' => 'b'], 'a.id=b.purchase_id')->sum('purchase_num*purchase_price');
    }

    /**
     * 当月采购总SKU数
     *
     * @Description
     * @author wpl
     * @since 2020/03/05 17:08:36 
     * @return void
     */
    public function getPurchaseSkuNum()
    {
        $where['createtime'] = ['between', [date('Y-m-01 00:00:00', time()), date('Y-m-d H:i:s', time())]];
        $where['is_del'] = 1;
        $where['purchase_status'] = ['in', [2, 5, 6, 7]];
        return $this->alias('a')->where($where)->join(['fa_purchase_order_item' => 'b'], 'a.id=b.purchase_id')->group('sku')->count(1);
    }

    /**
     * 当日采购总数
     *
     * @Description
     * @author wpl
     * @since 2020/03/05 17:08:36 
     * @return void
     */
    public function getPurchaseNumNow($where = [], $time = [])
    {
        if ($time) {
            $where['createtime'] = ['between', $time];
        } else {
            $where['createtime'] = ['between', [date('Y-m-d 00:00:00', time()), date('Y-m-d H:i:s', time())]];
        }

        $where['is_del'] = 1;
        $where['purchase_status'] = ['in', [2, 5, 6, 7]];
        return $this->alias('a')->where($where)->join(['fa_purchase_order_item' => 'b'], 'a.id=b.purchase_id')->sum('b.purchase_num');
    }

    /**
     * 当日采购总金额
     *
     * @Description
     * @author wpl
     * @since 2020/03/05 17:08:36 
     * @return void
     */
    public function getPurchasePriceNow($where = [], $time = [])
    {
        if ($time) {
            $where['createtime'] = ['between', $time];
        } else {
            $where['createtime'] = ['between', [date('Y-m-d 00:00:00', time()), date('Y-m-d H:i:s', time())]];
        }
        $where['is_del'] = 1;
        $where['purchase_status'] = ['in', [2, 5, 6, 7]];
        return $this->alias('a')->where($where)->join(['fa_purchase_order_item' => 'b'], 'a.id=b.purchase_id')->sum('purchase_num*purchase_price');
    }


    /**
     * 每个人当月采购总数 
     *
     * @Description
     * @author wpl
     * @since 2020/03/05 17:08:36 
     * @return void
     */
    public function getPurchaseNumNowPerson($where = [], $time = [])
    {
        if ($time) {
            $where['createtime'] = ['between', $time];
        } else {
            $where['createtime'] = ['between', [date('Y-m-01 00:00:00', time()), date('Y-m-d H:i:s', time())]];
        }
        $where['is_del'] = 1;
        $where['purchase_status'] = ['in', [2, 5, 6, 7]];
        return $this->alias('a')->where($where)->join(['fa_purchase_order_item' => 'b'], 'a.id=b.purchase_id')->group('a.create_person')->column('sum(b.purchase_num)', 'a.create_person');
    }

    /**
     * 每个人当月采购总单量 
     *
     * @Description
     * @author wpl
     * @since 2020/03/05 17:08:36 
     * @return void
     */
    public function getPurchaseOrderNumNowPerson($where = [], $time = [])
    {
        if ($time) {
            $where['createtime'] = ['between', $time];
        } else {
            $where['createtime'] = ['between', [date('Y-m-01 00:00:00', time()), date('Y-m-d H:i:s', time())]];
        }
        $where['is_del'] = 1;
        $where['purchase_status'] = ['in', [2, 5, 6, 7]];
        return $this->where($where)->group('create_person')->column('count(1)', 'create_person');
    }

    /**
     * 本月SKU采购数量排行榜
     *
     * @Description
     * @author wpl
     * @since 2020/03/24 11:13:42 
     * @return void
     */
    public function getPurchaseNumRanking()
    {
        $where['createtime'] = ['between', [date('Y-m-01 00:00:00', time()), date('Y-m-d H:i:s', time())]];
        $where['is_del'] = 1;
        $where['purchase_status'] = ['in', [2, 5, 6, 7]];
        $list = $this->alias('a')
            ->where($where)
            ->field('sum(b.purchase_num) as num,sku')
            ->join(['fa_purchase_order_item' => 'b'], 'a.id=b.purchase_id')
            ->group('sku')
            ->order('num desc')
            ->limit(30)
            ->select();
        $list = collection($list)->toArray();
        //查询SKU分类名称
        $item = new \app\admin\model\itemmanage\Item();
        $skuCategoryName = $item->getSkuCategoryName();
        foreach ($list as &$v) {
            $v['category_name'] = $skuCategoryName[$v['sku']];
        }
        unset($v);
        return $list ?? [];
    }
}
