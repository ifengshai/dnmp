define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {

    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                extend: {
                    index_url: 'purchase/purchase_order/index' + location.search,
                    add_url: 'purchase/purchase_order/add',
                    edit_url: 'purchase/purchase_order/edit',
                    del_url: 'purchase/purchase_order/del',
                    multi_url: 'purchase/purchase_order/multi',
                    table: 'purchase_order',
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
                        {field: 'purchase_number', title: __('Purchase_number')},
                        {field: 'purchase_name', title: __('Purchase_name')},
                        {field: 'purchase_remark', title: __('Purchase_remark')},
                        {field: 'contract_id', title: __('Contract_id')},
                        {field: 'supplier_id', title: __('Supplier_id')},
                        {field: 'item_id', title: __('Item_id')},
                        {field: 'create_person', title: __('Create_person')},
                        {field: 'createtime', title: __('Createtime'), operate:'RANGE', addclass:'datetimerange'},
                        {field: 'updatetime', title: __('Updatetime'), operate:'RANGE', addclass:'datetimerange'},
                        {field: 'product_total', title: __('Product_total'), operate:'BETWEEN'},
                        {field: 'purchase_freight', title: __('Purchase_freight'), operate:'BETWEEN'},
                        {field: 'purchase_total', title: __('Purchase_total'), operate:'BETWEEN'},
                        {field: 'settlement_method', title: __('Settlement_method')},
                        {field: 'deposit_amount', title: __('Deposit_amount'), operate:'BETWEEN'},
                        {field: 'final_amount', title: __('Final_amount'), operate:'BETWEEN'},
                        {field: 'delivery_stime', title: __('Delivery_stime'), operate:'RANGE', addclass:'datetimerange'},
                        {field: 'delivery_etime', title: __('Delivery_etime'), operate:'RANGE', addclass:'datetimerange'},
                        {field: 'delivery_address', title: __('Delivery_address')},
                        {field: 'purchase_status', title: __('Purchase_status')},
                        {field: 'is_add_logistics', title: __('Is_add_logistics')},
                        {field: 'is_new_product', title: __('Is_new_product')},
                        {field: 'payment_status', title: __('Payment_status')},
                        {field: 'payment_images', title: __('Payment_images'), events: Table.api.events.image, formatter: Table.api.formatter.images},
                        {field: 'payment_money', title: __('Payment_money'), operate:'BETWEEN'},
                        {field: 'payment_time', title: __('Payment_time'), operate:'RANGE', addclass:'datetimerange'},
                        {field: 'payment_remark', title: __('Payment_remark')},
                        {field: 'payment_person', title: __('Payment_person')},
                        {field: 'check_status', title: __('Check_status')},
                        {field: 'stock_status', title: __('Stock_status')},
                        {field: 'return_status', title: __('Return_status')},
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