define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {

    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                extend: {
                    index_url: 'platformmanage/magento_platform/index' + location.search,
                    add_url: 'platformmanage/magento_platform/add',
                    edit_url: 'platformmanage/magento_platform/edit',
                    del_url: 'platformmanage/magento_platform/del',
                    multi_url: 'platformmanage/magento_platform/multi',
                    table: 'magento_platform',
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
                            field: 'status',
                            title: __('Status'),
                            searchList:{1:'启用',2:'禁用'},
                            custom:{1:'blue',2:'red'},
                            formatter:Table.api.formatter.status,
                        },
                        {field: 'name', title: __('Name')},
                        {field: 'prefix', title: __('前缀')},
                        {field:'item_attr_name',title:__('Item_attr_name')},
                        {field:'item_type',title:__('Item_type')},
                        {field:'magento_account',title:__('Magento_account')},
                        {field:'magento_key',title:__('Magento_key')},
                        {
                            field:'is_upload_item',
                            title:__('Is_upload_item'),
                            searchList:{1:'上传',2:'不上传'},
                            custom:{1:'blue',2:'green'},
                            formatter:Table.api.formatter.status,
                        },
                        {field:'magento_url',title:__('Magento_url')},
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