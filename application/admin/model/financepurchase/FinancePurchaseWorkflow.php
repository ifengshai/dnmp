<?php

namespace app\admin\model\financepurchase;

use think\Db;
use think\Model;
use app\admin\model\financepurchase\FinancePurchaseWorkflowRecords;
use app\api\controller\Ding;

class FinancePurchaseWorkflow extends Model
{

    // 表名
    protected $name = 'finance_purchase_workflow';

    // 自动写入时间戳字段
    // protected $autoWriteTimestamp = 'int';


    // 追加属性
    protected $append = [];

    /**
     * 添加付款申请单工作流
     *
     * @Description
     * @author wpl
     * @since 2021/03/06 14:54:10 
     * @param [type] $finance_purchase_id 付款申请单id
     * @param [type] $total 金额
     * @return void
     */
    public function setData($finance_purchase_id = 0, $total = 0)
    {
        if (!$finance_purchase_id) return false;
        //金额大于30w 包含总监审核
        if ($total > 300000) {
            $check_userids = [56, 50, 1, 232,154];
            $check_usernickname = ['采购主管', '供应链总监', '总监', '总账会计', '财务经理'];
        } else {
            $check_userids = [56, 50, 232,154];
            $check_usernickname = ['采购主管', '供应链总监', '总账会计', '财务经理'];
        }
        $params = [];
        foreach ($check_userids as $k => $v) {
            $params[$k]['finance_purchase_id'] = $finance_purchase_id;
            $params[$k]['flow_sort'] = $k + 1;
            $params[$k]['flow_name'] = $check_usernickname[$k];
            $params[$k]['post_id'] = $v;
            $params[$k]['createtime'] = time();
        }
        //插入配置表
        if ($params) $this->saveAll($params);
        //插入记录表第一个审核人
        $financePurchaseWorkflowRecords = new FinancePurchaseWorkflowRecords();
        $financePurchaseWorkflowRecords->save(['finance_purchase_id' => $finance_purchase_id, 'assignee_id' => $check_userids[0], 'createtime' => time()]);

        Ding::cc_ding($check_userids[0], '', '魔晶系统有一个新的付款申请单需要你审核', '有一个新的付款申请单需要你审核,申请单id为' . $finance_purchase_id);
        return true;
    }
}
