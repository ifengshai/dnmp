define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {

    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                extend: {
                    index_url: 'saleaftermanage/sale_after_task_remark/index' + location.search,
                    add_url: 'saleaftermanage/sale_after_task_remark/add',
                    edit_url: 'saleaftermanage/sale_after_task_remark/edit',
                    del_url: 'saleaftermanage/sale_after_task_remark/del',
                    multi_url: 'saleaftermanage/sale_after_task_remark/multi',
                    table: 'sale_after_task_remark',
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
                        {field: '', title: __('序号'), formatter: function (value, row, index) {
                            var options = table.bootstrapTable('getOptions');
                            var pageNumber = options.pageNumber;
                            var pageSize = options.pageSize;

                            //return (pageNumber - 1) * pageSize + 1 + index;
                            return 1+index;
                            }, operate: false
                        },
                        {field: 'id', title: __('Id')},
                        {field: 'tid', title: __('Tid')},
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