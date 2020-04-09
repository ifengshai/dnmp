define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {

    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                extend: {
                    index_url: 'saleaftermanage/workmanage/work_order_measure/index' + location.search,
                    add_url: 'saleaftermanage/workmanage/work_order_measure/add',
                    edit_url: 'saleaftermanage/workmanage/work_order_measure/edit',
                    del_url: 'saleaftermanage/workmanage/work_order_measure/del',
                    multi_url: 'saleaftermanage/workmanage/work_order_measure/multi',
                    table: 'work_order_measure',
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
                        {field: 'work_id', title: __('Work_id')},
                        {field: 'measure_choose_id', title: __('Measure_choose_id')},
                        {field: 'measure_content', title: __('Measure_content')},
                        {field: 'create_time', title: __('Create_time'), operate:'RANGE', addclass:'datetimerange', formatter: Table.api.formatter.datetime},
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