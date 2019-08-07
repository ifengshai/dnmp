define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {

    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                extend: {
                    index_url: 'itemmanage/attribute/item_attribute_property_group/index' + location.search,
                    add_url: 'itemmanage/attribute/item_attribute_property_group/add',
                    edit_url: 'itemmanage/attribute/item_attribute_property_group/edit',
                    del_url: 'itemmanage/attribute/item_attribute_property_group/del',
                    multi_url: 'itemmanage/attribute/item_attribute_property_group/multi',
                    table: 'item_attribute_property_group',
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
                        {field: 'name', title: __('Name')},
                        {field: 'status', title: __('Status'),
                            searchList: { 1: '启用', 2: '禁用' },
                            custom: {  2: 'yellow', 1: 'blue' },
                            formatter: Table.api.formatter.status
                        },
                        {field:'create_person',title:__('Create_person')},
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