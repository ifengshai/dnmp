define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {

    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                extend: {
                    index_url: 'itemmanage/item_presell_log/index' + location.search,
                    add_url: 'itemmanage/item_presell_log/add',
                    edit_url: 'itemmanage/item_presell_log/edit',
                    del_url: 'itemmanage/item_presell_log/del',
                    multi_url: 'itemmanage/item_presell_log/multi',
                    table: 'item_presell_log',
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
                        {field: 'sku', title: __('Sku')},
                        {field: 'presell_num', title: __('Presell_num')},
                        {field: 'presell_residue_num', title: __('Presell_residue_num')},
                        {field: 'virtual_presell_num', title: __('Virtual_presell_num')},
                        {field: 'presell_start_time', title: __('Presell_start_time'), operate:'RANGE', addclass:'datetimerange', formatter: Table.api.formatter.datetime},
                        {field: 'presell_end_time', title: __('Presell_end_time'), operate:'RANGE', addclass:'datetimerange', formatter: Table.api.formatter.datetime},
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