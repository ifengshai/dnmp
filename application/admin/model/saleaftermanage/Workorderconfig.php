<?php

namespace app\admin\model\saleaftermanage;

use think\Db;
use think\Model;


class Workorderconfig extends Model
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
    protected $append = [

    ];

    /**
     * 工单对应问题类型的措施
     *
     * @Description
     * @return void
     * @since 2020/6/20 9:01
     * @author jhh
     */
    public function getQuetionMeasure($ids = null)
    {
        $model = new \app\admin\model\saleaftermanage\Workorderconfig();
        $detail = $model->where('id',$ids)->find();
//        $detail = $this->model
//            ->where(['problem_id'=>$ids])
//            ->alias('s')
//            ->join('work_order_problem_type w','s.problem_id = w.id')
//            ->join('work_order_step_type a','s.step_id = a.id')
//            ->join('auth_group g','s.extend_group_id = g.id')
//            ->field('s.id,s.problem_id,s.step_id,s.extend_group_id,w.problem_belong,w.type,w.problem_name,a.step_name,s.is_check,g.name')
//            ->select();
//        $detail = $this->model
//            ->alias('s')
//            ->where((['s.problem_id' => $ids]))
//            ->join('work_order_problem_type w', 's.problem_id = w.id')
//            ->field('s.id,s.problem_id,s.step_id,s.extend_group_id,w.problem_belong,w.type,w.problem_name,s.is_check')
//            ->select();
//        switch ($detail['problem_belong']) {
//            case 1:
//                $detail['problem_belong_name'] = '订单修改';
//                break;
//            case 2:
//                $detail['problem_belong_name'] = '物流仓库';
//                break;
//            case 3:
//                $detail['problem_belong_name'] = '产品质量';
//                break;
//            case 4:
//                $detail['problem_belong_name'] = '客户问题';
//                break;
//            default:
//                $detail['problem_belong_name'] = '5';
//        }
//        if ($detail['problem_belong'] = 1) {
//            $detail['type_name'] = '客服问题类型';
//        } else {
//            $detail['type_name'] = '仓库问题类型';
//        }
//            ->column('s.step_id');
//        $detail = implode(',',$detail);
//        foreach($detail as $k=>$v){
//            switch ($detail[$k]['problem_belong']){
//                case 1:
//                    $detail[$k]['problem_belong_name'] = '订单修改';
//                    break;
//                case 2:
//                    $detail[$k]['problem_belong_name'] = '物流仓库';
//                    break;
//                case 3:
//                    $detail[$k]['problem_belong_name'] = '产品质量';
//                    break;
//                case 4:
//                    $detail[$k]['problem_belong_name'] = '客户问题';
//                    break;
//                default:
//                    $detail[$k]['problem_belong_name'] = '5';
//            }
//            if ($detail[$k]['problem_belong'] = 1){
//                $detail[$k]['type_name'] = '客服问题类型';
//            }else{
//                $detail[$k]['type_name'] = '仓库问题类型';
//            }
//        }
        return $detail ? $detail : [];
    }


    /**
     * 获取所有的措施
     *
     * @Description
     * @return void
     * @since 2020/6/20 15:38
     * @author jhh
     */
    public function getAllStep()
    {
        $model = new \app\admin\model\saleaftermanage\WorkOrderStepType();
        $stepList = $model->where('is_del', 1)->field('id,step_name')->select();
        return $stepList ? $stepList : [];
    }

    /**
     * 获得所有的承接组
     *
     * @Description
     * @return void
     * @since 2020/6/20 15:51
     * @author jhh
     */
    public function getAllExtend()
    {
        $return = Db::name('auth_group')
            ->where('status', 'normal')
            ->where('id', '>', 1)
            ->field('id,name')
            ->select();
        $return = array_column($return, 'name', 'id');
        return $return ? $return : [];
    }


}
