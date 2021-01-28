define(['jquery', 'bootstrap', 'backend', 'table', 'form','upload'], function ($, undefined, Backend, Table, Form,Upload) {

    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                searchFormVisible: true,
            });
            
            //绑定事件
            $('a[data-toggle="tab"]').on('shown.bs.tab', function (e) {
                var panel = $($(this).attr("href"));
                if (panel.size() > 0) {
                    Controller.table[panel.attr("id")].call(this);
                    $(this).on('click', function (e) {
                        $($(this).attr("href")).find(".btn-refresh").trigger("click");
                    });
                }
                //移除绑定的事件
                $(this).unbind('shown.bs.tab');
            });
            
            //必须默认触发shown.bs.tab事件
            $('ul.nav-tabs li.active a[data-toggle="tab"]').trigger("shown.bs.tab");
        },
        table: {
            first: function () {
                // 表格1
                var table1 = $("#table");
                table1.bootstrapTable({
                    url: 'finance/finance_order/index',
                    toolbar: '#toolbar1',
                    sortName: 'createtime',
                    columns: [
                        [
                            {checkbox: true, },
                            {field: 'id', title: __('ID'),visible: true,sortable: true,operate:false},
                            {field: 'order_number', title: '订单号'},
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
                            {field: 'order_type', title: __('订单类型'),custom: { 1: 'success', 2: 'orange', 3: 'danger', 4: 'warning', 9: 'warning'}, searchList: { 1: '普通订单', 2: '批发', 3: '网红单', 4: '补发单', 9: 'vip订单'},formatter: Table.api.formatter.status},
                            {field: 'order_money', title: __('支付金额'),operate:false,formatter: Controller.api.float_format},
                            {field: 'income_amount', title: __('订单总金额'),operate:false,formatter: Controller.api.float_format},
                            {field: 'frame_cost', title: __('镜架成本'),operate:false,formatter: Controller.api.float_format},
                            {field: 'lens_cost',title: __('镜片成本'),operate:false,formatter: Controller.api.float_format},
                            {field: 'fi_actual_payment_fee',title: __('物流成本'),operate:false,formatter: Controller.api.float_format},
                            /*{field: 'coupon_order_num',title: __('售后成本'),operate:false},*/
                            {field: 'order_currency_code',title: __('币种'),operate:false},
                            /*{field: 'recommend_register_num',title: __('营销成本'),operate:false},
                            {field: 'recommend_register_num',title: __('售后退款'),operate:false},*/
                            {field: 'payment_time', title: __('支付时间'), operate: 'RANGE', addclass: 'datetimerange', formatter: Table.api.formatter.datetime },
                            {field: 'createtime', title: __('创建时间'), operate: 'RANGE', addclass: 'datetimerange', formatter: Table.api.formatter.datetime },
                        ]
                    ]
                });

                // 为表格1绑定事件
                Table.api.bindevent(table1);
                //批量导出xls
            $('.btn-batch-export-xls').click(function () {
                var ids = Table.api.selectedids(table1);


                if (ids.length > 0) {
                    window.open(Config.moduleurl + '/finance/Finance_order/batch_export_xls?ids=' + ids + '&label=' + Config.label, '_blank');
                } else {
                    var options = table1.bootstrapTable('getOptions');
                    var search = options.queryParams({});
                    var filter = search.filter;
                    var op = search.op;
                    window.open(Config.moduleurl + '/finance/Finance_order/batch_export_xls?filter=' + filter + '&op=' + op + '&label=' + Config.label, '_blank');
                }

            });
            }
        },
        api: {
            bindevent: function () {
                Form.api.bindevent($("form[role=form]"));
            },
            float_format: function (value, row, index) {
                if (value) {
                    return parseFloat(value).toFixed(2);
                }
            }
        }
    };
    return Controller;
});