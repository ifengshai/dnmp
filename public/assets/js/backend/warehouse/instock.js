define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {

    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                searchFormVisible: true,
                extend: {
                    index_url: 'warehouse/instock/index' + location.search,
                    add_url: 'warehouse/instock/add',
                    edit_url: 'warehouse/instock/edit',
                    del_url: 'warehouse/instock/del',
                    multi_url: 'warehouse/instock/multi',
                    table: 'in_stock',
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
                        {field: 'in_stock_number', title: __('In_stock_number')},
                        {field: 'type_id', title: __('In_stock_type')},
                        {field: 'purchase_id', title: __('Purchase_id')},
                        {field: 'order_number', title: __('Order_number')},
                        {field: 'remark', title: __('Remark')},
                        {field: 'status', title: __('Status')},
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