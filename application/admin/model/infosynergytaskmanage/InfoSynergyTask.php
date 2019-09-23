<?php

namespace app\admin\model\infosynergytaskmanage;

use think\Model;
use app\admin\model\infosynergytaskmanage\InfoSynergyTaskRemark;
use app\admin\model\infosynergytaskmanage\InfoSynergyTaskChangeSku;

class InfoSynergyTask extends Model
{

    

    //数据库
    protected $connection = 'database';
    // 表名
    protected $name = 'info_synergy_task';
    
    // 自动写入时间戳字段
    protected $autoWriteTimestamp = false;

    // 定义时间戳字段名
    protected $createTime = false;
    protected $updateTime = false;
    protected $deleteTime = false;

    // 追加属性
    protected $append = [

    ];

    /**
     * 关联单据类型
     * @return array
     */
    public function orderType()
    {
        return [
            1=>'无',
            2=>'订单',
            3=>'采购单',
            4=>'质检单',
            5=>'入库单',
            6=>'出库单',
            7=>'库存盘点单'
        ];
    }
    /***
     * 根据下标获取订单类型
     */
    public function getOrderType($id)
    {
        $arr = [
            1=>'无',
            2=>'订单',
            3=>'采购单',
            4=>'质检单',
            5=>'入库单',
            6=>'出库单',
            7=>'库存盘点单'
        ];
        return $arr[$id];
    }
    /***
     * 测试部门
     */
    public function testDepId()
    {
        return [
            1=>'产品部',
            2=>'技术部',
            3=>'采购部',
            4=>'仓库部',
            5=>'运营部',
            6=>'人事部',
            7=>'行政部'
        ];
    }
    /***
     * 测试承接人
     */
    public function testRepId()
    {
        return [
            1=>'A',
            2=>'B',
            3=>'C',
            4=>'D',
            5=>'E',
            6=>'F',
            7=>'G'
        ];
    }
    public function infoSynergyTaskCategory(){
        return $this->belongsTo('info_synergy_task_category', 'synergy_task_id')->setEagerlyType(0);
    }

    /**
     * 获取任务详情
     */
    public function getInfoSynergyDetail($id)
    {
        $result = $this->where('id','=',$id)->find();
        if(!$result){
            return false;
        }
//        $result['info_synergy_task_remark'] = (new InfoSynergyTaskRemark())->getSynergyTaskRemarkById($id);
//        $result['info_synergy_task_change_sku'] = (new InfoSynergyTaskChangeSku())->getChangeSkuList($id);
        return $result;
    }

}
