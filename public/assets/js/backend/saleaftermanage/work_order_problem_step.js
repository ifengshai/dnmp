define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {

    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                extend: {
                    index_url: 'saleaftermanage/work_order_problem_step/index' + location.search,
                    add_url: 'saleaftermanage/work_order_problem_step/add',
                    edit_url: 'saleaftermanage/work_order_problem_step/edit',
                    del_url: 'saleaftermanage/work_order_problem_step/del',
                    multi_url: 'saleaftermanage/work_order_problem_step/multi',
                    table: 'work_order_problem_step',
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
                        {field: 'problem_id', title: __('Problem_id')},
                        {field: 'step_id', title: __('Step_id')},
                        {field: 'extend_group_id', title: __('Extend_group_id')},
                        {field: 'is_check', title: __('Is_check')},
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