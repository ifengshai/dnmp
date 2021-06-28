define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {

    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                extend: {
                    index_url: 'zendesk/zendesk_admin/index' + location.search,
                    add_url: 'zendesk/zendesk_admin/add',
                    edit_url: 'zendesk/zendesk_admin/edit',
                    del_url: 'zendesk/zendesk_admin/del',
                    table: 'zendesk_admin',
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
                        {field: 'admin.nickname', title: __('用户名')},
                        {
                            field: 'group',
                            title: __('组别'),
                            custom: {1: 'blue'},
                            searchList: {0:'-',1: 'VIP'},
                            formatter: Table.api.formatter.status
                        },
                        {field: 'count', title: __('派单数目'),operate: false},
                        {
                            field: 'operate',
                            title: __('Operate'),
                            table: table,
                            events: Table.api.events.operate,
                            formatter: Table.api.formatter.operate
                        }
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