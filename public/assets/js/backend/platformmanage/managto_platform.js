define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {

    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                extend: {
                    index_url: 'platformmanage/managto_platform/index' + location.search,
                    add_url: 'platformmanage/managto_platform/add',
                    edit_url: 'platformmanage/managto_platform/edit',
                    del_url: 'platformmanage/managto_platform/del',
                    multi_url: 'platformmanage/managto_platform/multi',
                    table: 'managto_platform',
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
                        {
                            field: 'status',
                            title: __('Status'),
                            searchList:{1:'启用',2:'禁用'},
                            custom:{1:'blue',2:'red'},
                            formatter:Table.api.formatter.status,
                        },
                        {field: 'name', title: __('Name')},
                        {field:'managto_account',title:__('Managto_account')},
                        {field:'managto_key',title:__('Managto_key')},
                        {
                            field:'is_upload_item',
                            title:__('Is_upload_item'),
                            searchList:{1:'上传',2:'不上传'},
                            custom:{1:'blue',2:'green'},
                            formatter:Table.api.formatter.status,
                        },
                        {field:'managto_url',title:__('Managto_url')},
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