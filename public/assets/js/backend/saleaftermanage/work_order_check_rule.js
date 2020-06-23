define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {

    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                extend: {
                    index_url: 'saleaftermanage/work_order_check_rule/index' + location.search,
                    add_url: 'saleaftermanage/work_order_check_rule/add',
                    edit_url: 'saleaftermanage/work_order_check_rule/edit',
                    del_url: 'saleaftermanage/work_order_check_rule/del',
                    multi_url: 'saleaftermanage/work_order_check_rule/multi',
                    table: 'work_order_check_rule',
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
                        {field: 'id', title: __('id')},
                        {field: 'work_create_person_id', title: __('Work_create_person_id')},
                        {field: 'work_create_person', title: __('创建人')},
                        {field: 'create_group_id', title: __('创建组')},

                        {field: 'step_id', title: __('措施')},
                        {field: 'step_value', title: __('Step_value')},
                        {field: 'symbol', title: __('Symbol')},
                        {field: 'check_group_name', title: __('审核组')},
                        {field: 'weight', title: __('Weight')},
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