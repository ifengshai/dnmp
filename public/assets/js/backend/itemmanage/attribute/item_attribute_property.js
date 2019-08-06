define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {

    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                extend: {
                    index_url: 'itemmanage/attribute/item_attribute_property/index' + location.search,
                    add_url: 'itemmanage/attribute/item_attribute_property/add',
                    edit_url: 'itemmanage/attribute/item_attribute_property/edit',
                    del_url: 'itemmanage/attribute/item_attribute_property/del',
                    multi_url: 'itemmanage/attribute/item_attribute_property/multi',
                    table: 'item_attribute_property',
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
                        {field: 'is_required', title: __('Is_required')},
                        {field: 'name_cn', title: __('Name_cn')},
                        {field: 'name_en', title: __('Name_en')},
                        {field: 'input_mode', title: __('Input_mode')},
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
                $(document).on('click', '.btn-add', function () {
                    var content = $('#table-content table tbody').html();
                    $('.caigou table tbody').append(content);
                });
                $(document).on('click', '.btn-del', function () {
                    $(this).parent().parent().remove();
                });
            }
        }
    };
    return Controller;
});