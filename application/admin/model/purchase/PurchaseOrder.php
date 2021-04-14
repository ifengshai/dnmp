<?php

namespace app\admin\model\purchase;

use think\Model;
use think\Db;
use fast\Kuaidi100;

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
        $where['purchase_status'] = ['in', [6, 7, 8, 9]];
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
        $where['check_status'] = ['in', $check_status];
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
        foreach ($purchaseResult as $v) {
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
        //退销单的退销金额
        $returnResult = Db::name('purchase_return')->where($map)->field('purchase_id,round(sum(return_money),2) return_money')->group('purchase_id')->select();
        //收货异常的退销金额 start
        $abnormalResult = Db::name('purchase_abnormal_item')->where($map)->where(['error_type' => 2])->field('purchase_id,round(sum((should_arrival_num-arrival_num)*purchase_price)) return_price')->group('purchase_id')->select();
        $arr = [];
        $arr['return_money'] = 0;
        if (!$returnResult && !$abnormalResult) {
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

        if ($abnormalResult) {
            foreach ($abnormalResult as $av) {
                $arr['return_money'] += $av['return_price'];
                if (in_array($av['purchase_id'], $thisPageIdArr)) {
                    $arr['thisPageArr'][$av['purchase_id']] += $av['return_price'];
                }
            }
        }

        return $arr;
    }

    /***
     * 求出采购单核算成本详情页面所需要的信息 create@lsw
     *
     * @param  id 采购单ID
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
     * @return void
     * @since 2020/03/05 17:08:36
     * @author wpl
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
     * @return void
     * @since 2020/03/05 17:08:36
     * @author wpl
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
     * @return void
     * @since 2020/03/05 17:08:36
     * @author wpl
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
     * @return void
     * @since 2020/03/05 17:08:36
     * @author wpl
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
     * @return void
     * @since 2020/03/05 17:08:36
     * @author wpl
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
     * @return void
     * @since 2020/03/05 17:08:36
     * @author wpl
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
     * @return void
     * @since 2020/03/05 17:08:36
     * @author wpl
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
     * @return void
     * @since 2020/03/05 17:08:36
     * @author wpl
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
     * @return void
     * @since 2020/03/05 17:08:36
     * @author wpl
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
     * @return void
     * @since 2020/03/05 17:08:36
     * @author wpl
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
     * @return void
     * @since 2020/03/05 17:08:36
     * @author wpl
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

    /**
     * 查询待入库数量
     *
     * @Description
     * @author wpl
     * @since 2020/07/16 11:07:07 
     * @return void
     */
    public function getWaitInStockNum($skus = [])
    {
        $where['is_del'] = 1;
        $where['purchase_status'] = ['in', [7, 9]];
        $where['stock_status'] = 0;
        $where['b.sku'] = ['in', $skus];
        $list = $this->alias('a')
            ->field('a.id,sum(purchase_num) as purchase_num,b.sku,a.purchase_status')
            ->where($where)
            ->join(['fa_purchase_order_item' => 'b'], 'a.id=b.purchase_id')
            //->join(['fa_purchase_batch' => 'c'], 'c.purchase_id=b.purchase_id')
            //->join(['fa_purchase_batch_item' => 'd'], 'd.purchase_batch_id=c.id')
            //->select();
            ->group('a.id')
            ->select();
        //->column('sum(purchase_num) as purchase_num', 'sku');
        //$list = collection($list)->toArray();
        //dump($list);
        foreach ($list as $k => $v) {
            //如果是部分签收的话 应该是带有批次
            if ($v['purchase_status'] == 9) {
                $v['batch'] = Db::name('logistics_info')
                    ->field('a.batch_id,a.purchase_id,d.*,c.*,sum(arrival_num) as purchase_num')
                    ->alias('a')
                    ->where('a.purchase_id', $v['id'])
                    ->where('a.status', 1)
                    ->where('c.sku', $v['sku'])
                    ->join(['fa_purchase_batch' => 'd'], 'a.batch_id=d.id')
                    ->join(['fa_purchase_batch_item' => 'c'], 'd.id=c.purchase_batch_id')
                    ->select();
            }
        }
        $list = collection($list)->toArray();
        foreach ($list as $key => $val) {
            //存在带有批次的物流单 也有可能批次到了和另一个没划分批次的
            if ($val['batch']) {
                if ($arr[$val['sku']]) {
                    $arr[$val['sku']] = $arr[$val['sku']] + $val['batch'][0]['purchase_num'];
                } else {
                    $arr[$val['sku']] = $val['batch'][0]['purchase_num'];
                }
            } else {
                if ($arr[$val['sku']]) {
                    $arr[$val['sku']] = $arr[$val['sku']] + $val['purchase_num'];
                } else {
                    $arr[$val['sku']] = $val['purchase_num'];
                }
            }
            // dump($arr);
        }
        // dump($list);
        // dump($arr);
        //dump(collection($list)->toArray());
        // die;
        return $arr;
    }


    /**
     * 物流单订阅
     *
     * @Description
     *
     * @param  array  $logistics  物流单号
     * @param  array  $logistics_company_no  公司编码
     *
     * @return false
     * @author: wpl
     * @since: 2021/4/1 9:37
     */
    public function logisticsSubscription($logistics = [], $logistics_company_no = []): bool
    {
        if (!$logistics) {
            return false;
        }
        foreach ($logistics as $k => $v) {
            //根据物流单号查询所有采购单id
            $ids = $this->where(['logistics_number' => ['like', '%'.$v.'%']])->column('id');
            //订阅快递100推送
            Kuaidi100::setPoll($logistics_company_no[$k], $v, implode(',', $ids));
        }

        return true;
    }
}
