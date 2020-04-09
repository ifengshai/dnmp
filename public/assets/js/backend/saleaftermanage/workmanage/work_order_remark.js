define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {

    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                extend: {
                    index_url: 'saleaftermanage/workmanage/work_order_remark/index' + location.search,
                    add_url: 'saleaftermanage/workmanage/work_order_remark/add',
                    edit_url: 'saleaftermanage/workmanage/work_order_remark/edit',
                    del_url: 'saleaftermanage/workmanage/work_order_remark/del',
                    multi_url: 'saleaftermanage/workmanage/work_order_remark/multi',
                    table: 'work_order_remark',
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
                        {field: 'remark_type', title: __('Remark_type')},
                        {field: 'remark_record', title: __('Remark_record')},
                        {field: 'create_person_id', title: __('Create_person_id')},
                        {field: 'create_person', title: __('Create_person')},
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