define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {

    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                extend: {
                    index_url: 'saleaftermanage/workmanage/work_order_list/index' + location.search,
                    add_url: 'saleaftermanage/workmanage/work_order_list/add',
                    edit_url: 'saleaftermanage/workmanage/work_order_list/edit',
                    del_url: 'saleaftermanage/workmanage/work_order_list/del',
                    multi_url: 'saleaftermanage/workmanage/work_order_list/multi',
                    table: 'work_order_list',
                }
            });

            var table = $("#table");

            // 初始化表格
            table.bootstrapTable({
                url: $.fn.bootstrapTable.defaults.extend.index_url,
                pk: 'id',
                sortName: 'id',
                columns: [
                    [
                        {checkbox: true},
                        {field: 'id', title: __('Id')},
                        {field: 'work_platform', title: __('Work_platform')},
                        {field: 'work_type', title: __('Work_type')},
                        {field: 'platform_order', title: __('Platform_order')},
                        {field: 'order_sku', title: __('Order_sku')},
                        {field: 'work_status', title: __('Work_status')},
                        {field: 'work_level', title: __('Work_level')},
                        {field: 'problem_type_id', title: __('Problem_type_id')},
                        {field: 'problem_type_content', title: __('Problem_type_content')},
                        {field: 'problem_description', title: __('Problem_description')},
                        {field: 'refund_way', title: __('Refund_way')},
                        {field: 'refund_money', title: __('Refund_money'), operate:'BETWEEN'},
                        {field: 'order_pay_currency', title: __('Order_pay_currency')},
                        {field: 'is_refund', title: __('Is_refund')},
                        {field: 'make_up_price_order', title: __('Make_up_price_order')},
                        {field: 'replacement_order', title: __('Replacement_order')},
                        {field: 'logistics_order', title: __('Logistics_order')},
                        {field: 'integral', title: __('Integral')},
                        {field: 'coupon', title: __('Coupon')},
                        {field: 'create_id', title: __('Create_id')},
                        {field: 'create_person', title: __('Create_person')},
                        {field: 'handle_person', title: __('Handle_person')},
                        {field: 'check_person', title: __('Check_person')},
                        {field: 'create_time', title: __('Create_time'), operate:'RANGE', addclass:'datetimerange', formatter: Table.api.formatter.datetime},
                        {field: 'check_time', title: __('Check_time'), operate:'RANGE', addclass:'datetimerange', formatter: Table.api.formatter.datetime},
                        {field: 'handle_time', title: __('Handle_time'), operate:'RANGE', addclass:'datetimerange', formatter: Table.api.formatter.datetime},
                        {field: 'complete_time', title: __('Complete_time'), operate:'RANGE', addclass:'datetimerange', formatter: Table.api.formatter.datetime},
                        {field: 'operate', title: __('Operate'), table: table, events: Table.api.events.operate, formatter: Table.api.formatter.operate}
                    ]
                ]
            });

            // 为表格绑定事件
            Table.api.bindevent(table);
        },
        add: function () {
            Controller.api.bindevent();
        },
        edit: function () {
            Controller.api.bindevent();
        },
        api: {
            bindevent: function () {
                Form.api.bindevent($("form[role=form]"));
            }
        }
    };
    return Controller;
});