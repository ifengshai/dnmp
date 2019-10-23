define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {

    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                extend: {
                    index_url: 'platformmanage/platform_map/index' + location.search,
                    add_url: 'platformmanage/platform_map/add',
                    edit_url: 'platformmanage/platform_map/edit',
                    del_url: 'platformmanage/platform_map/del',
                    multi_url: 'platformmanage/platform_map/multi',
                    table: 'platform_map',
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
                        {
                            field: 'platform_id',
                            title: __('Platform_id'),
                            searchList: { 1: 'zeelool', 2: 'voogueme', 3: 'nihao', 4: 'amazon' },
                            custom: { 1: 'yellow', 2: 'blue', 3: 'green', 4: 'red'},
                            formatter: Table.api.formatter.status
                        },
                        {field: 'field_name', title: __('Field_name')},
                        {field: 'platform_field', title: __('Platform_field')},
                        {field: 'magento_field', title: __('Magento_field')},
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