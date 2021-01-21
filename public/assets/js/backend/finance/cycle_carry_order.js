define(['jquery', 'bootstrap', 'backend', 'table', 'form', 'upload'], function ($, undefined, Backend, Table, Form, Upload) {

    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                searchFormVisible: true,
                extend: {
                    index_url: 'finance/cycle_carry_order/index' + location.search,
                }
            });

            var table = $("#table");

            // 初始化表格
            table.bootstrapTable({
                url: $.fn.bootstrapTable.defaults.extend.index_url,
                pk: 'id',
                sortName: 'id',
                showToggle: false,
                showExport: false,
                columns: [
                    [
                        { checkbox: true },
                        { field: 'id', title: __('ID'), operate: false },
                        { field: 'cycle_number', title: __('结转单号') },
                        { field: 'createtime', title: __('结转时间'), operate: 'RANGE', addclass: 'datetimerange', formatter: Table.api.formatter.datetime },
                        {
                            field: 'operate', title: __('Operate'), table: table, events: Table.api.events.operate, buttons: [
                                {
                                    name: 'detail',
                                    text: '查看详情',
                                    title: __('查看详情'),
                                    extend: 'data-area = \'["80%","70%"]\'',
                                    classname: 'btn btn-xs btn-primary btn-dialog',
                                    icon: 'fa fa-list',
                                    url: 'finance/cycle_carry_order/detail',
                                    callback: function (data) {
                                        Layer.alert("接收到回传数据：" + JSON.stringify(data), { title: "回传数据" });
                                    },
                                    visible: function (row) {
                                        //返回true时按钮显示,返回false隐藏
                                        return true;
                                    }
                                },

                            ],
                            formatter: Table.api.formatter.operate
                        }
                    ]
                ]
            });

            // 为表格绑定事件
            Table.api.bindevent(table);
        },
        detail: function () {
            // 初始化表格参数配置
            Table.api.init({
                searchFormVisible: true,
                extend: {
                    index_url: 'finance/cycle_carry_order/detail' + location.search + '&id=' + Config.id,
                }
            });

            var table = $("#table");

            // 初始化表格
            table.bootstrapTable({
                url: $.fn.bootstrapTable.defaults.extend.index_url,
                pk: 'id',
                sortName: 'id',
                commonSearch: true,
                search: false,
                showExport: false,
                showColumns: false,
                showToggle: false,
                pagination: false,
                columns: [
                    [
                        { field: 'id', title: __('ID'), operate: false },
                        { field: 'bill_type', title: __('关联单据类型'), custom: { 1: 'danger', 2: 'success', 3: 'orange', 4: 'warning', 5: 'purple', 6: 'primary', 7: 'primary', 8: 'primary', 9: 'primary', 10: 'primary' }, searchList: { 1: '订单', 2: 'VIP订单', 3: '工单补差价', 4: '退货退款', 5: '订单取消', 6: '部分退款', 7: 'Vip退款', 8: '订单出库', 9: '出库单出库', 10: '冲减暂估' }, formatter: Table.api.formatter.status },
                        { field: 'order_number', title: '订单号' },
                        {
                            field: 'site', title: __('站点'), addClass: 'selectpicker', data: 'multiple',
                            searchList: {
                                1: 'Zeelool',
                                2: 'Voogueme',
                                3: 'Nihao',
                                4: 'Meeloog',
                                5: 'Wesee',
                                8: 'Amazon',
                                9: 'Zeelool_es',
                                10: 'Zeelool_de',
                                11: 'Zeelool_jp'
                            }, operate: 'IN',
                            formatter: Table.api.formatter.status
                        },
                        { field: 'order_currency_code', title: __('币种'), operate: false },
                        { field: 'order_type', title: __('订单类型'), custom: { 1: 'blue', 2: 'blue', 3: 'blue', 4: 'blue', 5: 'blue', 6: 'blue', 10: 'blue' }, searchList: { 1: '普通订单', 2: '批发单', 3: '网红单', 4: '补发单', 5: '补差价', 6: '一件代发', 10: '货到付款' }, formatter: Table.api.formatter.status },
                        { field: 'order_money', title: __('订单金额'), operate: false },
                        { field: 'frame_cost', title: __('镜架成本'), operate: false },
                        { field: 'lens_cost', title: __('镜片成本'), operate: false },
                        { field: 'payment_time', title: __('支付时间'), operate: 'RANGE', addclass: 'datetimerange', formatter: Table.api.formatter.datetime }

                    ]
                ]
            });

            // 为表格绑定事件
            Table.api.bindevent(table);
        },

        api: {
            bindevent: function () {
                Form.api.bindevent($("form[role=form]"));
            }
        }
    };
    return Controller;
});