define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {

    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                extend: {
                    index_url: 'saleaftermanage/work_order_documentary/index' + location.search,
                    add_url: 'saleaftermanage/work_order_documentary/add',
                    edit_url: 'saleaftermanage/work_order_documentary/edit',
                    del_url: 'saleaftermanage/work_order_documentary/del',
                    multi_url: 'saleaftermanage/work_order_documentary/multi',
                    table: 'work_order_documentary',
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
                        {field: 'type', title: __('创建者类型'),custom: { 1: 'blue', 2: 'danger'}, searchList: { 1: '组创建', 2: '人创建' }, formatter: Table.api.formatter.status },
                        {field: 'create_id', title: __('Create_id')},
                        {field: 'create_name', title: __('Create_name')},
                        {field: 'documentary_group_id', title: __('Documentary_group_id')},
                        {field: 'documentary_group_name', title: __('Documentary_group_name')},
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