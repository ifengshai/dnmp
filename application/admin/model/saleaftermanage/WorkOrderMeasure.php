<?php

namespace app\admin\model\saleaftermanage;

use think\Model;


class WorkOrderMeasure extends Model
{

    // 表名
    protected $name = 'work_order_measure';

    // 自动写入时间戳字段
    protected $autoWriteTimestamp = false;

    // 定义时间戳字段名
    protected $createTime = false;
    protected $updateTime = false;
    protected $deleteTime = false;

    // 追加属性
    protected $append = [];
    /**
     * 求出工单的措施列表
     *
     * @Description
     * @author lsw
     * @since 2020/04/15 16:25:24 
     * @param [type] $id
     * @return void
     */
     static public function workMeasureList($id)
    {
        return WorkOrderMeasure::where(['work_id'=>$id])->column('measure_choose_id');
    }
    /**
     * 
     *导出措施
     * @Description
     * @author lsw
     * @since 2020/04/30 14:04:52 
     * @param [type] $arr
     * @return void
     */
    public function fetchMeasureRecord($arr=[])
    {
		$where['work_id'] = ['in',$arr];
		$result = $this->where($where)->select();
		if(!$result){
			return false;
		}
		$arrInfo = [];
		foreach($result as $v){
			if(in_array($v['work_id'],$arr)){
                switch($v['operation_type']){
                    case 1:
                    $v['operation_type'] = '处理完成';
                    break;
                    case 2:
                    $v['operation_type'] = '处理失败';
                    break;
                    default:
                    $v['operation_type'] = '未处理';
                    break;        
                }
                $arrInfo['step'][$v['work_id']].=$v['measure_content'].' | ';
				$arrInfo['detail'][$v['work_id']].= $v['measure_content'].' '.$v['operation_type'].' '.$v['operation_time'].' | ';
			}
		}
		return $arrInfo;
    }
}
