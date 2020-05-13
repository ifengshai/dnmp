<?php

namespace app\admin\model\saleaftermanage;

use think\Model;


class WorkOrderNote extends Model
{

    // 表名
    protected $name = 'work_order_note';

    // 自动写入时间戳字段
    protected $autoWriteTimestamp = false;

    // 定义时间戳字段名
    protected $createTime = false;
    protected $updateTime = false;
    protected $deleteTime = false;

    // 追加属性
    protected $append = [];
    /**
     * 导出回复备注记录
     *
     * @Description
     * @author lsw
     * @since 2020/04/30 14:36:12 
     * @param array $arr
     * @return void
     */
    public function fetchNoteRecord($arr=[])
    {
		$where['work_id'] = ['in',$arr];
		$result = $this->where($where)->select();
		if(!$result){
			return false;
		}
		$arrInfo = [];
		foreach($result as $v){
			if(in_array($v['work_id'],$arr)){
				$arrInfo[$v['work_id']].= $v['content'].' '.$v['note_user_name'].' '.$v['note_time'].' | ';
			}
		}
		return $arrInfo;
    }
}
