define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {

    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                extend: {
                    index_url: 'infosynergytaskmanage/info_synergy_task_category/index' + location.search,
                    add_url: 'infosynergytaskmanage/info_synergy_task_category/add',
                    edit_url: 'infosynergytaskmanage/info_synergy_task_category/edit',
                    del_url: 'infosynergytaskmanage/info_synergy_task_category/del',
                    multi_url: 'infosynergytaskmanage/info_synergy_task_category/multi',
                    table: 'info_synergy_task_category',
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
                        {field: 'pid', title: __('Pid')},
                        {field: 'name', title: __('Name')},
                        {field: 'level', title: __('Level'),
                            custom: { 1: 'yellow', 2: 'blue', 3: 'danger'},
                            searchList: { 1: '一级分类', 2: '二级分类', 3: '三级分类'},
                            formatter: Table.api.formatter.status
                        },
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