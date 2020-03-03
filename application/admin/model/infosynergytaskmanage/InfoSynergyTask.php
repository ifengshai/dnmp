<?php

namespace app\admin\model\infosynergytaskmanage;
use think\Db;
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
            7=>'库存盘点单',
            8=>'VIP订单'
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
            7=>'库存盘点单',
            8=>'VIP订单'
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
        return $this->belongsTo('app\admin\model\infosynergytaskmanage\InfoSynergyTaskCategory', 'synergy_task_id','id','','left')->setEagerlyType(0);
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
	//检测是否存在任务单号
	public function checkOrderInfo($synergy_order_number,$synergy_task_id)
	{
		$where['synergy_order_number'] = $synergy_order_number;
		$where['synergy_task_id']   = $synergy_task_id;
		$where['synergy_status']  = ['in',[0,1]];
		$result = $this->where($where)->field('id,synergy_order_number')->find();
		return $result ? $result : false;
    }
    

    /**
     * 获取未处理协同事件数量
     *
     * @Description
     * @author wpl
     * @since 2020/03/02 14:27:30 
     * @return void
     */
    public function getTaskNum()
    {
        $map['is_del'] = 1;
        $map['synergy_status'] = 0;
        return $this->where($map)->count(1);
    }
    /**
     * 检查VIP订单是否正确
     * @author lsw
     * @param order_plaftorm 平台
     * @param synergy_order_number 订单号
     */
    public function checkVipOrder($order_platform,$synergy_order_number)
    {
        switch($order_platform){
            case 1:
                $db='database.db_zeelool_online';
            break;
            case 2:
                $db='database.db_voogueme_online';
            break;    
        }
        $where['order_number'] = $synergy_order_number;
        $result = Db::connect($db)->name('oc_vip_order')->where($where)->field('id,order_number')->find();
        return $result ? $result : false;
    } 
}
