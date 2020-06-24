<?php

namespace app\admin\model\saleaftermanage;

use think\Model;


class WorkOrderRecept extends Model
{

    // 表名
    protected $name = 'work_order_recept';

    // 自动写入时间戳字段
    protected $autoWriteTimestamp = false;

    // 定义时间戳字段名
    protected $createTime = false;
    protected $updateTime = false;
    protected $deleteTime = false;
    /**
     * 根据工单ID获取工单承接人
     * @param work_id 工单ID
     * @Description
     * @author lsw
     * @since 2020/04/21 09:33:24 
     * @return void
     */
    static function getWorkOrderReceptPerson($work_id)
    {
       return WorkOrderRecept::where(['work_id'=>$work_id])->select();
    }
    protected $append = [
        'status_format'
    ];

    public function getStatusFormatAttr($value, $data)
    {
        $status = ['0' => '未处理', '1' => '处理完成', '2' => '处理失败'];
        return $status[$data['recept_status']];
    }
    /**
     * 措施
     * @return \think\model\relation\HasOne
     */
    public function measure()
    {
        return $this->hasOne(WorkOrderMeasure::class,'id','measure_id');
    }
    /**
     * 获取所有承接的记录ID
     *
     * @Description
     * @author lsw
     * @since 2020/04/21 16:01:58 
     * @param [type] $id
     * @return void
     */
    public function getOneRecept($id)
    {
        return  $this->where(['id'=>$id])->find();
    }
    /**
     * 获取所有可以承接的记录ID
     *
     * @Author lsw 1461069578@qq.com
     * @DateTime 2020-06-23 18:56:58
     * @param [type] $id
     * @return void
     */
    public function getAllRecept($id){
        $info = $this->where(['id'=>$id])->field('work_id,measure_id')->find();
        if(!$info){
            return false;
        }
        $where['work_id'] = $info->work_id;
        $where['measure_id'] = $info->measure_id;
        return $this->where($where)->column('recept_person_id');
    }
    /**
     * 导出措施
     *
     * @Description
     * @author lsw
     * @since 2020/04/30 14:16:08 
     * @param array $arr
     * @return void
     */
    public function fetchReceptRecord($arr=[])
    {
		$where['r.work_id'] = ['in',$arr];
		$result = $this->where($where)->alias('r')->join('work_order_measure m','r.measure_id=m.id')->field('r.work_id,r.recept_status,r.recept_person,r.finish_time,r.note,m.measure_content')->select();
		if(!$result){
			return false;
		}
		$arrInfo = [];
		foreach($result as $v){
			if(in_array($v['work_id'],$arr)){
                switch($v['recept_status']){
                    case 1:
                    $v['recept_status'] = '处理完成';
                    break;
                    case 2:
                    $v['recept_status'] = '处理失败';
                    break;
                    default:
                    $v['recept_status'] = '未处理';
                    break;        
                }
				$arrInfo[$v['work_id']].= $v['measure_content'].' '.$v['recept_status'].' '.$v['recept_person'].' '.$v['note'].' '.$v['finish_time'].' | ';
			}
		}
		return $arrInfo;        
    }
}
