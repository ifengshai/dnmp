define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {

    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                extend: {
                    index_url: 'zendesk/zendesk_comments/index' + location.search,
                    add_url: 'zendesk/zendesk_comments/add',
                    edit_url: 'zendesk/zendesk_comments/edit',
                    del_url: 'zendesk/zendesk_comments/del',
                    multi_url: 'zendesk/zendesk_comments/multi',
                    table: 'zendesk_comments',
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
                        {field: 'ticket_id', title: __('Ticket_id')},
                        {field: 'zid', title: __('Zid')},
                        {field: 'author_id', title: __('Author_id')},
                        {field: 'due_id', title: __('Due_id')},
                        {field: 'is_public', title: __('Is_public')},
                        {field: 'is_admin', title: __('Is_admin')},
                        {field: 'create_time', title: __('Create_time'), operate:'RANGE', addclass:'datetimerange'},
                        {field: 'update_time', title: __('Update_time'), operate:'RANGE', addclass:'datetimerange'},
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