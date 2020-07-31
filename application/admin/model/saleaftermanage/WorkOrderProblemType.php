<?php

namespace app\admin\model\saleaftermanage;

use think\Model;


class WorkOrderProblemType extends Model
{
    // 表名
    protected $name = 'work_order_problem_type';

    // 自动写入时间戳字段
    protected $autoWriteTimestamp = false;

    // 定义时间戳字段名
    protected $createTime = false;
    protected $updateTime = false;
    protected $deleteTime = false;

    // 追加属性
    protected $append = [];
    protected $resultSetType = 'collection';


    /**
     * 获取工单大类
     *
     * @Description
     * @author wpl
     * @since 2020/07/30 11:25:56 
     * @return void
     */
    public function getProblemBelongType()
    {
        return config('workorder.problem_Belong_type');
    }

    /**
     * 统计各分类占比数量
     *
     * @Description
     * @author wpl
     * @since 2020/07/30 13:58:19 
     * @param integer $problemBelongType
     * @param integer $site
     * @return void
     */
    public function getProblemTypeData($problemBelongType = 2, $site = 1, $map = [])
    {
        //问题类型id
        $problem_ids = $this->where(['problem_belong' => $problemBelongType, 'is_del' => 1])->column('id');
        $work = new \app\admin\model\saleaftermanage\WorkOrderList();
        if ($problem_ids) {
            $map['problem_type_id'] = ['in', $problem_ids];
        }
        //全部
        if ($site < 10) {
            $map['work_platform'] = $site;
        } else {
            unset($map['create_time']);
        }
        
        $work_data = $work->where($map)->field('problem_type_id,count(*) as num')->group('problem_type_id')->select();

        $work_data = collection($work_data)->toArray();
        
        return $work_data;
    }
    
    /**
     * 根据大类查询子分类
     *
     * @Description
     * @author wpl
     * @since 2020/07/30 14:42:57 
     * @param integer $problemBelongType
     * @return void
     */
    public function getProblemType($problemBelongType = 1)
    {
        return $this->where(['problem_belong' => $problemBelongType, 'is_del' => 1])->column('problem_name','id');
    }
}
