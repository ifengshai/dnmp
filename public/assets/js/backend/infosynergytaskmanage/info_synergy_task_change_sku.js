define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {

    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                extend: {
                    index_url: 'infosynergytaskmanage/info_synergy_task_change_sku/index' + location.search,
                    add_url: 'infosynergytaskmanage/info_synergy_task_change_sku/add',
                    edit_url: 'infosynergytaskmanage/info_synergy_task_change_sku/edit',
                    del_url: 'infosynergytaskmanage/info_synergy_task_change_sku/del',
                    multi_url: 'infosynergytaskmanage/info_synergy_task_change_sku/multi',
                    table: 'info_synergy_task_change_sku',
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
                        {field: 'tid', title: __('Tid')},
                        {field: 'original_sku', title: __('Original_sku')},
                        {field: 'original_number', title: __('Original_number')},
                        {field: 'change_sku', title: __('Change_sku')},
                        {field: 'change_number', title: __('Change_number')},
                        {field: 'create_person', title: __('Create_person')},
                        {field: 'create_time', title: __('Create_time'), operate:'RANGE', addclass:'datetimerange'},
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