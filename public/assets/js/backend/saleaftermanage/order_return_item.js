define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {

    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                extend: {
                    index_url: 'saleaftermanage/order_return_item/index' + location.search,
                    add_url: 'saleaftermanage/order_return_item/add',
                    edit_url: 'saleaftermanage/order_return_item/edit',
                    del_url: 'saleaftermanage/order_return_item/del',
                    multi_url: 'saleaftermanage/order_return_item/multi',
                    table: 'order_return_item',
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
                        {field: 'order_return_id', title: __('Order_return_id')},
                        {field: 'return_sku', title: __('Return_sku')},
                        {field: 'return_sku_qty', title: __('Return_sku_qty')},
                        {field: 'arrived_sku_qty', title: __('Arrived_sku_qty')},
                        {field: 'check_sku_qty', title: __('Check_sku_qty')},
                        {field: 'damage_sku_qty', title: __('Damage_sku_qty')},
                        {field: 'is_visable', title: __('Is_visable')},
                        {field: 'created_time', title: __('Created_time'), operate:'RANGE', addclass:'datetimerange'},
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