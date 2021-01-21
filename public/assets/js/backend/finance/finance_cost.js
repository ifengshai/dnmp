define(['jquery', 'bootstrap', 'backend', 'table', 'form','upload'], function ($, undefined, Backend, Table, Form,Upload) {

    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                searchFormVisible: true,
            });
            
            //绑定事件
            $('a[data-toggle="tab"]').on('shown.bs.tab', function (e) {
                var panel = $($(this).attr("href"));
                if (panel.size() > 0) {
                    Controller.table[panel.attr("id")].call(this);
                    $(this).on('click', function (e) {
                        $($(this).attr("href")).find(".btn-refresh").trigger("click");
                    });
                }
                //移除绑定的事件
                $(this).unbind('shown.bs.tab');
            });
            
            //必须默认触发shown.bs.tab事件
            $('ul.nav-tabs li.active a[data-toggle="tab"]').trigger("shown.bs.tab");
        },
        table: {
            first: function () {
                // 表格1
                var table1 = $("#table1");
                table1.bootstrapTable({
                    url: 'finance/finance_cost/table1',
                    toolbar: '#toolbar1',
                    sortName: 'createtime',
                    columns: [
                        [
                            {checkbox: true, },
                            {field: 'id', title: 'ID',operate:false},
                            {field: 'bill_type', title: __('关联单据类型'),custom: { 1: 'success', 2: 'success', 3: 'success', 4: 'danger', 5: 'danger', 6: 'danger' , 7: 'danger'}, searchList: { 1: '订单', 2: 'VIP订单', 3: '工单补差价', 4: '退货退款', 5: '订单取消',6:'部分退款' ,7:'Vip退款' ,8:'出库单出库'},formatter: Table.api.formatter.status},
                            {field: 'order_number', title: '订单号'},
                            {field: 'site', title: '平台'},
                            {field: 'order_type', title: __('订单类型'),custom: { 1: 'success', 2: 'orange', 3: 'danger'}, searchList: { 1: '普通订单', 2: '批发', 3: '网红单'},formatter: Table.api.formatter.status},
                            {field: 'order_money', title: __('订单金额'),operate:false},
                            {field: 'income_amount', title: __('收入金额'),operate:false},
                            {field: 'order_currency_code', title: __('币种')},
                            {field: 'is_carry_forward', title: __('是否结转'),custom: { 1: 'danger', 0: 'success'}, searchList: { 1: '已结转', 0: '未结转'},formatter: Table.api.formatter.status},
                            {field: 'action_type', title: __('增加/冲减'),custom: { 1: 'success', 2: 'danger'}, searchList: { 1: '增加', 2: '冲减'},formatter: Table.api.formatter.status},
                            {field: 'payment_time', title: __('订单支付时间'),operate:false},
                            {field: 'payment_method', title: __('支付方式'),operate:false},
                            {field: 'createtime', title: __('创建时间')},
                        ]
                    ]
                });

                // 为表格1绑定事件
                Table.api.bindevent(table1);
            },
            second: function () {
                // 表格2
                var table2 = $("#table2");
                table2.bootstrapTable({
                    url: 'finance/finance_cost/table2',
                    toolbar: '#toolbar2',
                    sortName: 'id',
                    columns: [
                        [
                            {field: 'id', title: 'ID',operate:false},
                            {field: 'bill_type', title: __('关联单据类型'),custom: { 1: 'danger', 2: 'success', 3: 'orange', 4: 'warning', 5: 'purple', 6: 'primary' , 7: 'primary'}, searchList: { 1: '订单', 2: 'VIP订单', 3: '工单补差价', 4: '退货退款', 5: '部分退款',6:'VIP退款' ,7:'冲减'},formatter: Table.api.formatter.status},
                            {field: 'order_number', title: __('关联单号')},
                            {field: 'frame_cost', title: __('镜架成本'),operate:false},
                            {field: 'lens_cost', title: __('镜片成本'),operate:false},
                            {field: 'createtime', title: __('创建时间')}, 
                            {field: 'is_carry_forward', title: __('是否结转'),visible:false,searchList: { 1: '已结转', 2: '未结转'},formatter: Table.api.formatter.status},
                        ]
                    ]
                });

                // 为表格2绑定事件
                Table.api.bindevent(table2);
            }
        },
        api: {
            bindevent: function () {
                Form.api.bindevent($("form[role=form]"));
            }
        }
    };
    return Controller;
});