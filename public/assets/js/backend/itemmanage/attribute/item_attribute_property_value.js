define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {

    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                extend: {
                    index_url: 'itemmanage/attribute/item_attribute_property_value/index' + location.search,
                    add_url: 'itemmanage/attribute/item_attribute_property_value/add',
                    edit_url: 'itemmanage/attribute/item_attribute_property_value/edit',
                    del_url: 'itemmanage/attribute/item_attribute_property_value/del',
                    multi_url: 'itemmanage/attribute/item_attribute_property_value/multi',
                    table: 'item_attribute_property_value',
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
                        {field: 'property_id', title: __('Property_id')},
                        {field: 'name_value_cn', title: __('Name_value_cn')},
                        {field: 'name_value_en', title: __('Name_value_en')},
                        {field: 'descb', title: __('Descb')},
                        {field: 'code_rule', title: __('Code_rule')},
                        {field: 'create_person', title: __('Create_person')},
                        {field: 'update_time', title: __('Update_time'), operate:'RANGE', addclass:'datetimerange'},
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