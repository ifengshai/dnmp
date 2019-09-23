define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {

    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                searchFormVisible: true,
                pageList: [10, 25, 50, 100],
                extend: {
                    index_url: 'order/index/index' + location.search + '&label=' + Config.label,
                    table: 'sales_flat_order',
                }
            });

            var table = $("#table");

            // 初始化表格
            table.bootstrapTable({
                url: $.fn.bootstrapTable.defaults.extend.index_url,
                pk: 'entity_id',
                sortName: 'entity_id',
                columns: [
                    [
                        { checkbox: true },
                        { field: 'entity_id', title: __('记录标识'), operate: false },
                        { field: 'increment_id', title: __('订单号') },
                        { field: 'customer_firstname', title: __('客户名称') },
                        { field: 'customer_email', title: __('邮箱') },
                        { field: 'status', title: __('状态'), searchList: { "processing": __('processing'), 'complete': 'complete', 'creditcard_failed': 'creditcard_failed', 'creditcard_pending': 'creditcard_pending', 'holded': 'holded', 'payment_review': 'payment_review', 'paypal_canceled_reversal': 'paypal_canceled_reversal', 'paypal_reversed': 'paypal_reversed', 'pending': 'pending', 'canceled': 'canceled', 'closed': 'closed', "free_processing": __('free_processing') } },
                        { field: 'base_grand_total', title: __('订单金额'), operate: false, formatter: Controller.api.formatter.float_format },
                        { field: 'base_total_paid', title: __('支付金额'), operate: false, formatter: Controller.api.formatter.float_format },
                        { field: 'base_shipping_amount', title: __('邮费'), operate: false, formatter: Controller.api.formatter.float_format },

                        { field: 'store_id', title: __('订单来源'), custom: { 1: 'blue', 4: 'blue', 2: 'blue' }, searchList: { 1: 'PC端', 4: '移动端', 2: '未知' }, formatter: Table.api.formatter.status },
                        { field: 'created_at', title: __('创建时间'), operate: 'RANGE', addclass: 'datetimerange' },
                        {
                            field: 'operate', title: __('Operate'), table: table, events: Table.api.events.operate, buttons: [
                                {
                                    name: 'detail',
                                    text: '详情',
                                    title: __('Detail'),
                                    classname: 'btn btn-xs  btn-primary  btn-dialog',
                                    icon: 'fa fa-list',
                                    url: 'order/index/detail?label=' + Config.label,
                                    extend: 'data-area = \'["100%","100%"]\'',
                                    callback: function (data) {
                                        Layer.alert("接收到回传数据：" + JSON.stringify(data), { title: "回传数据" });
                                    },
                                    visible: function (row) {
                                        //返回true时按钮显示,返回false隐藏
                                        return true;
                                    }
                                }

                            ], formatter: Table.api.formatter.operate
                        }
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
        detail: function () {
            Controller.api.bindevent();
        },
        api: {

            formatter: {
                device: function (value, row, index) {
                    var str = '';
                    if (value == 1) {
                        str = '电脑';
                    } else if (value == 4) {
                        str = '移动';
                    } else {
                        str = '未知';
                    }
                    return str;
                },
                printLabel: function (value, row, index) {
                    var str = '';
                    if (value == 0) {
                        str = '否';
                    } else if (value == 1) {
                        str = '<span style="font-weight:bold;color:#18bc9c;">是</span>';
                    } else {
                        str = '未知';
                    }
                    return str;
                },
                float_format: function (value, row, index) {
                    if (value) {
                        return parseFloat(value).toFixed(2);
                    }
                },
                int_format: function (value, row, index) {
                    return parseInt(value);
                },
            },
            bindevent: function () {
                Form.api.bindevent($("form[role=form]"));
            }
        }
    };
    return Controller;
});