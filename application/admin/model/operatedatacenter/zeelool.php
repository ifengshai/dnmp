<?php

namespace app\admin\model\operatedatacenter;

use think\Model;


class Index extends Model
{

    // 表名
    protected $name = 'lens';

    // 自动写入时间戳字段
    protected $autoWriteTimestamp = 'int';

    // 定义时间戳字段名
    protected $createTime = 'createtime';
    protected $updateTime = false;
    protected $deleteTime = false;

    // 追加属性
    protected $append = [

    ];

    /**
     * 统计镜片库存
     *
     * @Description
     * @author wpl
     * @since 2020/02/26 17:36:58
     * @return void
     */
    public function getLensStock()
    {
        return $this->sum('stock_num');
    }

    /**
     * 统计镜片库存总金额
     *
     * @Description
     * @author wpl
     * @since 2020/02/26 17:36:58
     * @return void
     */
    public function getLensStockPrice()
    {
        return $this->sum('stock_num*price');
    }








}
