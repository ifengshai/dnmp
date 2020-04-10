<?php

return [
    'Id'                   => '自增ID',
    'Work_platform'        => '工单平台',
    'Work_type'            => '工单类型 1 客服工单 2 仓库工单 默认 1',
    'platform_order'       => '订单号',
    'Order_pay_currency'   => '订单支付的货币类型',
    'Order_sku'            => 'SKU',
    'Work_status'          => '工单状态 0 取消 1 草稿 2 待审核 3 审核通过(当前为待处理状态） 4 审核拒绝 5部分处理 6 处理完成 7 处理失败 8 已撤销',
    'Work_level'           => '工单级别',
    'Problem_type_id'      => '问题类型ID',
    'Problem_type_content' => '问题类型描述',
    'Problem_description'  => '问题描述',
    'Work_picture'         => '工单图片',
    'Create_id'            => '创建人ID',
    'Handle_person'        => '工单经手人id(第一次承接人,客服创建默认自己)',
    'Is_check'             => '本条工单是否需要审核 0否 1是',
    'Check_person_id'      => '指派工单审核人,默认0',
    'Operation_person'     => '实际审核人',
    'Shenhe_beizhu'        => '审核人备注',
    'Create_time'          => '工单创建时间',
    'Check_time'           => '工单审核时间',
    'Complete_time'        => '工单完成时间',
    'problem_type'        => '问题类型',
    'step'        => '实时措施',

];
