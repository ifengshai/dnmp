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
        var same_order_num = ret.data.same_order_num;
        var huan_order_num = ret.data.huan_order_num;
        var order_unit_price = ret.data.order_unit_price;
        var same_order_unit_price = ret.data.same_order_unit_price;
        var huan_order_unit_price = ret.data.huan_order_unit_price;
        var sales_total_money = ret.data.sales_total_money;
        var same_sales_total_money = ret.data.same_sales_total_money;
        var huan_sales_total_money = ret.data.huan_sales_total_money;
        var shipping_total_money = ret.data.shipping_total_money;
        var same_shipping_total_money = ret.data.same_shipping_total_money;
        var huan_shipping_total_money = ret.data.huan_shipping_total_money;
        $('#order_num').text(today.order_num);
        $('#today_increment_num').text(today.increment_num);
        $('#today_reply_num').text(today.reply_num);
        $('#today_waiting_num').text(today.waiting_num);
        $('#today_pending_num').text(today.pending_num);

        $('#yesterday_wait_num').text(yesterday.wait_num);
        $('#yesterday_increment_num').text(yesterday.increment_num);
        $('#yesterday_reply_num').text(yesterday.reply_num);
        $('#yesterday_waiting_num').text(yesterday.waiting_num);
        $('#yesterday_pending_num').text(yesterday.pending_num);

        $('#serven_wait_num').text(serven.wait_num);
        $('#serven_increment_num').text(serven.increment_num);
        $('#serven_reply_num').text(serven.reply_num);
        $('#serven_waiting_num').text(serven.waiting_num);
        $('#serven_pending_num').text(serven.pending_num);

        $('#third_wait_num').text(third.wait_num);
        $('#third_increment_num').text(third.increment_num);
        $('#third_reply_num').text(third.reply_num);
        $('#third_waiting_num').text(third.waiting_num);
        $('#third_pending_num').text(third.pending_num);
        var tr = '<tr id="new_tr">';
        tr += '<td style="text-align: center; vertical-align: middle;">' + starttime + ':' + endtime + '</td>';
        tr += '<td style="text-align: center; vertical-align: middle;">' + info.wait_num + '</td>';
        tr += '<td style="text-align: center; vertical-align: middle;">' + info.increment_num + '</td>';
        tr += '<td style="text-align: center; vertical-align: middle;">' + info.reply_num + '</td>';
        tr += '<td style="text-align: center; vertical-align: middle;">' + info.waiting_num + '</td>';
        tr += '<td style="text-align: center; vertical-align: middle;">' + info.pending_num + '</td>';
        tr += '</tr>';
        $("#workload-info").append(tr);
        return false;
    }, function (data, ret) {
        Layer.alert(ret.msg);
        return false;
    });
}