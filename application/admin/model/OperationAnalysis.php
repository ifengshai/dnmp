<?php

namespace app\admin\model;

use think\Model;


class OperationAnalysis extends Model
{
    // 表名
    protected $name = 'operation_analysis';

    // 自动写入时间戳字段
    protected $autoWriteTimestamp = false;

    // 定义时间戳字段名
    protected $createTime = false;
    protected $updateTime = false;
    protected $deleteTime = false;

    // 追加属性
    protected $append = [];
	/***
	 *获取数据
	 *@param id 平台ID
	 */
	public function getList($id)
	{
		$where['order_platform'] = $id;
		$result = $this->where($where)->field('id,order_platform')->select();
		return $result;
	}
}