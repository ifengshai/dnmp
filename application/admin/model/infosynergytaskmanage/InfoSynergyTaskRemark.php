<?php

namespace app\admin\model\infosynergytaskmanage;

use think\Model;


class InfoSynergyTaskRemark extends Model
{

    

    //数据库
    protected $connection = 'database';
    // 表名
    protected $name = 'info_synergy_task_remark';
    
    // 自动写入时间戳字段
    protected $autoWriteTimestamp = false;

    // 定义时间戳字段名
    protected $createTime = false;
    protected $updateTime = false;
    protected $deleteTime = false;

    // 追加属性
    protected $append = [

    ];
    

    /***
     * 根据id获取关联的备注信息
     */
    public function getSynergyTaskRemarkById($id){
        return $this->where('tid','=',$id)->select();
    }

		/**
	 *获取任务关联记录
	 */	
    public function fetchRelevanceRecord($arr=[])
	{
		$where['tid'] = ['in',$arr];
		$result = $this->where($where)->select();
		if(!$result){
			return false;
		}
		$arrInfo = [];
		foreach($result as $k =>$v){
			if(in_array($v['tid'],$arr)){
				$arrInfo[$v['tid']].= $v['remark_record'].' '.$v['create_person'].' '.$v['create_time'].' | ';
			}
		}
		return $arrInfo;
	}




}
