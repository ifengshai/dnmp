define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {

    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                extend: {
                    index_url: 'zendesk/zendesk_account/index' + location.search,
                    add_url: 'zendesk/zendesk_account/add',
                    // edit_url: 'zendesk/zendesk_account/edit',
                    // del_url: 'zendesk/zendesk_account/del',
                    multi_url: 'zendesk/zendesk_account/multi',
                    table: 'zendesk_account',
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
                        {field: 'user_type',
                         title:__('User_type'),
                         searchList:{1:'admin',2:'agent'},
                         custom: { 1: 'yellow', 2: 'blue'},
                         formatter:Table.api.formatter.status
                        },
                        {field: 'account_id', title: __('Account_id')},
                        {field: 'account_type', title: __('Account_type')},
                        {field: 'account_user', title: __('Account_user')},
                        {field: 'account_email', title: __('Account_email')},
                        {
                            field:'is_used',
                            title:__('Is_used'),
                            searchList:{1:'未使用',2:'已使用'},
                            custom: { 1: 'blue', 2: 'red'},
                            formatter:Table.api.formatter.status                            
                        }
                        // {field: 'create_time', title: __('Create_time'), operate:'RANGE', addclass:'datetimerange', formatter: Table.api.formatter.datetime},
                        // {field: 'operate', title: __('Operate'), table: table, events: Table.api.events.operate, formatter: Table.api.formatter.operate}
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