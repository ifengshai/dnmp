<?php

namespace app\admin\model\saleaftermanage;

use think\Model;


class WorkOrderProblemStep extends Model
{

    // 表名
    protected $name = 'work_order_problem_step';
    
    // 自动写入时间戳字段
    protected $autoWriteTimestamp = false;

    // 定义时间戳字段名
    protected $createTime = false;
    protected $updateTime = false;
    protected $deleteTime = false;

    // 追加属性
    protected $append = [

    ];
    protected $resultSetType = 'collection';


    /**
     * 统计各分类占比数量
     *
     * @Description
     * @author wpl
     * @since 2020/07/30 13:58:19 
     * @param integer $problemType 问题类型
     * @param integer $site 站点
     * @param array $map 条件
     * @return void
     */
    public function getProblemData($problemType = 5, $site = 1, $map = [])
    {
        //措施id 
        $step_ids = $this->where(['problem_id' => $problemType, 'is_del' => 1])->column('id');
        $work = new \app\admin\model\saleaftermanage\WorkOrderList();
        //措施id
        if ($step_ids) {
            $map['b.measure_choose_id'] = ['in', $step_ids];
        }

        if ($map['create_time']) {
            $map['a.create_time'] = $map['create_time'];
            unset($map['create_time']);
        }
        if ($site < 10) {
            $map['work_platform'] = $site;
        }
        $map['problem_type_id'] = $problemType;
        $work_data = $work->alias('a')->where($map)->join(['fa_work_order_measure' =>'b'],'a.id=b.work_id')->field('measure_content,count(*) as num')->group('b.measure_choose_id')->select();
        $work_data = collection($work_data)->toArray();
        return $work_data;
    }








}
