define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {

    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                extend: {
                    index_url: 'lens/index/index' + location.search,
                    add_url: 'lens/index/add',
                    edit_url: 'lens/index/edit',
                    del_url: 'lens/index/del',
                    multi_url: 'lens/index/multi',
                    table: 'lens',
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
                        {field: 'refractive_index', title: __('Refractive_index')},
                        {field: 'lens_type', title: __('Lens_type')},
                        {field: 'sph', title: __('Sph')},
                        {field: 'cyl', title: __('Cyl')},
                        {field: 'stock_num', title: __('Stock_num')},
                        {field: 'price', title: __('Price'), operate:'BETWEEN'},
                        {field: 'createtime', title: __('Createtime'), operate:'RANGE', addclass:'datetimerange'},
                        {field: 'create_person', title: __('Create_person')},
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