define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {

    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                extend: {
                    index_url: 'itemmanage/itempresell/index' + location.search,
                    add_url: 'itemmanage/itempresell/add',
                    edit_url: 'itemmanage/itempresell/edit',
                    del_url: 'itemmanage/itempresell/del',
                    multi_url: 'itemmanage/itempresell/multi',
                    table: 'item_presell',
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
                        {field: 'platform_sku', title: __('Platform_sku')},
                        {field: 'name', title: __('Name')},
                        {field: 'platform_type', title: __('Platform_type')},
                        {field: 'platform_sku_status', title: __('Platform_sku_status')},
                        {field: 'presell_num', title: __('Presell_num')},
                        {field: 'presell_residue_num', title: __('Presell_residue_num')},
                        {field: 'presell_create_time', title: __('Presell_create_time'), operate:'RANGE', addclass:'datetimerange', formatter: Table.api.formatter.datetime},
                        {field: 'presell_open_time', title: __('Presell_open_time'), operate:'RANGE', addclass:'datetimerange', formatter: Table.api.formatter.datetime},
                        {field: 'presell_start_time', title: __('Presell_start_time'), operate:'RANGE', addclass:'datetimerange', formatter: Table.api.formatter.datetime},
                        {field: 'presell_end_time', title: __('Presell_end_time'), operate:'RANGE', addclass:'datetimerange', formatter: Table.api.formatter.datetime},
                        {field: 'presell_status', title: __('Presell_status')},
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