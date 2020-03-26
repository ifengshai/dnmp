define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {

    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                extend: {
                    index_url: 'zendesk/zendesk_mail_template/index' + location.search,
                    add_url: 'zendesk/zendesk_mail_template/add',
                    edit_url: 'zendesk/zendesk_mail_template/edit',
                    del_url: 'zendesk/zendesk_mail_template/del',
                    multi_url: 'zendesk/zendesk_mail_template/multi',
                    table: 'zendesk_mail_template',
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
                            field: 'template_platform', 
                            title: __('Template_platform'),
                            searchList:$.getJSON('saleaftermanage/sale_after_task/getAjaxOrderPlatformList'),
                            custom:{1:'blue',2:'yellow',3:'green',4:'red'},
                            formatter:Table.api.formatter.status,
                        },
                        {field: 'template_name', title: __('Template_name')},
                        {field: 'template_description', title: __('Template_description')},
                        {
                            field: 'template_permission', 
                            title: __('Template_permission'),
                            searchList: { 1: '公共', 2: '私有'},
                            custom: { 1: 'yellow', 2: 'blue'},
                            formatter: Table.api.formatter.status
                        },
                        {field: 'template_content', title: __('Template_content')},
                        {field: 'template_category', title: __('Template_category')},
                        {
                            field: 'is_active', 
                            title: __('Is_active'),
                            searchList: { 1: '启用', 2: '禁用'},
                            custom: { 1: 'blue', 2: 'yellow'},
                            formatter: Table.api.formatter.status                            
                        },
                        {field: 'used_time', title: __('Used_time')},
                        {field: 'extra_param', title: __('Extra_param')},
                        {field: 'create_person', title: __('Create_person')},
                        {field: 'update_time', title: __('Update_time'), operate:'RANGE', addclass:'datetimerange', formatter: Table.api.formatter.datetime},
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