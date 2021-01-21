define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {

    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                extend: {
                    index_url: 'lens/lens_price/index' + location.search,
                    add_url: 'lens/lens_price/add',
                    import_url: 'lens/lens_price/import',
                    // edit_url: 'lens/lens_price/edit',
                    // del_url: 'lens/lens_price/del',
                    // multi_url: 'lens/lens_price/multi',
                    table: 'lens_price',
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
                        { checkbox: true },
                        { field: 'id', title: __('Id') },
                        { field: 'lens_number', title: __('Lens_number') },
                        { field: 'lens_name', title: __('Lens_name') },
                        { field: 'options', title: __('光度'), operate: false },
                        { field: 'price', title: __('镜片价格'), operate: 'BETWEEN' },
                        {
                            field: 'type', title: __('现片/定制'),
                            searchList: { 1: '现片', 2: '定制'},
                            formatter: Table.api.formatter.status
                        },
                        { field: 'createtime', title: __('Createtime'), operate: 'RANGE', addclass: 'datetimerange', formatter: Table.api.formatter.datetime },
                        { field: 'create_person', title: __('Create_person') },
                        // { field: 'operate', title: __('Operate'), table: table, events: Table.api.events.operate, formatter: Table.api.formatter.operate }
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