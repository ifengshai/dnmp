define(['jquery', 'bootstrap', 'backend', 'addtabs', 'table', 'form', 'echartsobj', 'echarts', 'echarts-theme', 'template', 'custom-css'], function ($, undefined, Backend, Datatable, Table, Form, EchartObj, undefined, Template) {

    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                extend: {
                    index_url: 'operatedatacenter/orderdata/order_data_view/index' + location.search,
                    add_url: 'operatedatacenter/orderdata/order_data_view/add',
                    edit_url: 'operatedatacenter/orderdata/order_data_view/edit',
                    del_url: 'operatedatacenter/orderdata/order_data_view/del',
                    multi_url: 'operatedatacenter/orderdata/order_data_view/multi',
                    table: 'order_data_view',
                }
            });
            Controller.api.formatter.daterangepicker($("div[role=form]"));
            //订单数据概况折线图
            Controller.api.formatter.line_chart();
            $("#time_str").on("apply.daterangepicker",function(){
                setTimeout(()=>{
                    order_data_view();
                    Controller.api.formatter.line_chart();
                },0)
            })
            $(document).on('change', '#type', function () {
                order_data_view();
                Controller.api.formatter.line_chart();
            });
            $(document).on('change', '#order_platform', function () {
                order_data_view();
                Controller.api.formatter.line_chart();
            });
            Controller.api.formatter.country_rate();
            
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
            formatter:{
                daterangepicker: function (form) {
                    //绑定日期时间元素事件
                    if ($(".datetimerange", form).size() > 0) {
                        require(['bootstrap-daterangepicker'], function () {
                            var ranges = {};
                            ranges[__('Today')] = [Moment().startOf('day'), Moment().endOf('day')];
                            ranges[__('Yesterday')] = [Moment().subtract(1, 'days').startOf('day'), Moment().subtract(1, 'days').endOf('day')];
                            ranges[__('Last 7 Days')] = [Moment().subtract(6, 'days').startOf('day'), Moment().endOf('day')];
                            ranges[__('Last 30 Days')] = [Moment().subtract(29, 'days').startOf('day'), Moment().endOf('day')];
                            ranges[__('This Month')] = [Moment().startOf('month'), Moment().endOf('month')];
                            ranges[__('Last Month')] = [Moment().subtract(1, 'month').startOf('month'), Moment().subtract(1, 'month').endOf('month')];
                            var options = {
                                timePicker: false,
                                autoUpdateInput: false,
                                timePickerSeconds: true,
                                timePicker24Hour: true,
                                autoApply: true,
                                locale: {
                                    format: 'YYYY-MM-DD HH:mm:ss',
                                    customRangeLabel: __("Custom Range"),
                                    applyLabel: __("Apply"),
                                    cancelLabel: __("Clear"),
                                },
                                ranges: ranges,
                                timePicker: true,
                                timePickerIncrement: 1
                            };
                            var origincallback = function (start, end) {
                                $(this.element).val(start.format(this.locale.format) + " - " + end.format(this.locale.format));
                                $(this.element).trigger('blur');
                            };
                            $(".datetimerange", form).each(function () {
                                var callback = typeof $(this).data('callback') == 'function' ? $(this).data('callback') : origincallback;
                                $(this).on('apply.daterangepicker', function (ev, picker) {
                                    callback.call(picker, picker.startDate, picker.endDate);
                                });
                                $(this).on('cancel.daterangepicker', function (ev, picker) {
                                    $(this).val('').trigger('blur');
                                });
                                $(this).daterangepicker($.extend({}, options, $(this).data()), callback);
                            });
                        });
                    }
                },
                line_chart: function () {
                    //订单数据概况折线图
                    var chartOptions = {
                        targetId: 'echart1',
                        downLoadTitle: '图表',
                        type: 'line'
                    };

                    var options = {
                        type: 'post',
                        url: 'operatedatacenter/orderdata/order_data_view/order_data_view_line',
                        data: {
                            order_platform: $("#order_platform").val(),
                            time_str: $("#time_str").val(),
                            type: $("#type").val()
                        }
                    }
                    EchartObj.api.ajax(options, chartOptions)
                },
                country_rate: function () {
                    //订单数据概况国家占比图
                    var myChart = Echarts.init(document.getElementById('echart'), 'walden');

                    // 指定图表的配置项和数据
                    var option = {
                        title: {
                            text: '',
                            subtext: ''
                        },
                        tooltip: {
                            trigger: 'axis'
                        },
                        legend: {
                            data: [__('国家分布')]
                        },
                        xAxis: {
                            type: 'dashed',
                            boundaryGap: false,
                            data: Orderdata.column
                        },
                        yAxis: {
                            splitLine: {
                                lineStyle: {
                                    type: 'dashed'
                                }
                            },
                            scale: true
                        },
                        grid: [{
                            left: 'left',
                            top: 'top',
                            right: '10',
                            bottom: 30
                        }],
                        series: [{
                            name: __('国家分布'),
                            type: 'scatter',
                            smooth: true,
                            areaStyle: {
                                normal: {}
                            },
                            lineStyle: {
                                shadowBlur: 10,
                                shadowColor: 'rgba(120, 36, 50, 0.5)',
                                shadowOffsetY: 5,
                                color: new echarts.graphic.RadialGradient(0.4, 0.3, 1, [{
                                    offset: 0,
                                    color: 'rgb(251, 118, 123)'
                                }, {
                                    offset: 1,
                                    color: 'rgb(204, 46, 72)'
                                }])
                            },
                            data: Orderdata.zeeloolSalesNumList
                        }]
                    };
                    // 使用刚指定的配置项和数据显示图表。
                    myChart.setOption(option);
                },
            },
            bindevent: function () {
                Form.api.bindevent($("form[role=form]"));
            }
        }
    };
    return Controller;
});
function order_data_view(){
    var order_platform = $('#order_platform').val();
    var time_str = $('#time_str').val();
    Backend.api.ajax({
        url: 'operatedatacenter/orderdata/order_data_view/ajax_order_data_view',
        data: { order_platform: order_platform, time_str: time_str}
    }, function (data, ret) {
        var order_num = ret.data.order_num;
        var order_unit_price = ret.data.order_unit_price;
        var sales_total_money = ret.data.sales_total_money;
        var shipping_total_money = ret.data.shipping_total_money;
        var replacement_order_num = ret.data.replacement_order_num;
        var replacement_order_total = ret.data.replacement_order_total;
        var online_celebrity_order_num = ret.data.online_celebrity_order_num;
        var online_celebrity_order_total = ret.data.online_celebrity_order_total;
        $('#order_num').text(order_num.order_num);
        $('#same_order_num').text(order_num.same_order_num);
        $('#huan_order_num').text(order_num.huan_order_num);
        $('#order_unit_price').text(order_unit_price.order_unit_price);
        $('#same_order_unit_price').text(order_unit_price.same_order_unit_price);
        $('#huan_order_unit_price').text(order_unit_price.huan_order_unit_price);
        $('#sales_total_money').text(sales_total_money.sales_total_money);
        $('#same_sales_total_money').text(sales_total_money.same_sales_total_money);
        $('#huan_sales_total_money').text(sales_total_money.huan_sales_total_money);
        $('#shipping_total_money').text(shipping_total_money.shipping_total_money);
        $('#same_shipping_total_money').text(shipping_total_money.same_shipping_total_money);
        $('#huan_shipping_total_money').text(shipping_total_money.huan_shipping_total_money);
        $('#replacement_order_num').text(replacement_order_num.replacement_order_num);
        $('#replacement_order_total').text(replacement_order_total.replacement_order_total);
        $('#online_celebrity_order_num').text(online_celebrity_order_num.online_celebrity_order_num);
        $('#online_celebrity_order_total').text(online_celebrity_order_total.online_celebrity_order_total);
        return false;
    }, function (data, ret) {
        Layer.alert(ret.msg);
        return false;
    });
}