define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {

    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                searchFormVisible: true,
                extend: {
                    index_url: 'itemmanage/item_brand/index' + location.search,
                    add_url: 'itemmanage/item_brand/add',
                    edit_url: 'itemmanage/item_brand/edit',
                    del_url: 'itemmanage/item_brand/del',
                    multi_url: 'itemmanage/item_brand/multi',
                    table: 'item_brand',
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
                        {field: 'name_cn', title: __('Name_cn')},
                        {field: 'name_en', title: __('Name_en')},
                        {field: 'status', title: __('Status'),
                            searchList: { 1: '启用', 0: '禁用' },
                            custom: {  0: 'yellow', 1: 'blue' },
                            formatter: Table.api.formatter.status
                        },
                        {field:'images',title:__('Images'),formatter:Table.api.formatter.images,events:Table.api.events.image},
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