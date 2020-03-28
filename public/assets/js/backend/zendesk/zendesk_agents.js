define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {

    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                extend: {
                    index_url: 'zendesk/zendesk_agents/index' + location.search,
                    add_url: 'zendesk/zendesk_agents/add',
                    edit_url: 'zendesk/zendesk_agents/edit',
                    del_url: 'zendesk/zendesk_agents/del',
                    table: 'zendesk_agents',
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
                        {field: 'admin.nickname', title: __('admin.nickname')},
                        {field: 'admin.email', title: __('admin.email')},
                        {field: 'name', title: __('Name')},
                        {field: 'type', title: __('type'), custom: { 1: 'blue', 2: 'yellow' }, searchList: { 1: 'Zeelool', 2: 'Voogueme' }, formatter: Table.api.formatter.status },
                        {field: 'agent_type', title: __('Agent_type'), custom: { 1: 'success', 2: 'danger' }, searchList: { 1: '邮件组', 2: '电话组' }, formatter: Table.api.formatter.status },
                        {field: 'count', title: __('Count')},
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