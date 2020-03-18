define(['jquery', 'bootstrap', 'backend', 'addtabs', 'table','form', 'echartsobj', 'echarts-theme', 'template','custom-css'], function ($, undefined, Backend, Datatable, Table,Form, EchartObj, undefined, Template) {

    var Controller = {
        index: function () {            
            Controller.api.formatter.daterangepicker($("form[role=form1]"));
            Form.api.bindevent($("form[role=form]"));
                //购物车图表
                var chartOptions = {
                    targetId: 'echart',
                    downLoadTitle: '图表',
                    type: 'line',
                    line: {
                        xAxis: {
                            type: 'category',
                            boundaryGap: [0, 0.01]
                        },
                        yAxis: [
                            {
                                type: 'value',
                                name: '购物车数量',
								position: 'left',
                                axisLabel: {
                                    formatter: '{value}'
                                }
                            },
                            {
                                type: 'value',
                                name: '购物车转化率',
								position: 'right',
                                axisLabel: {
                                    formatter: '{value} %'
                                }
                            }
                        ],
                    }
                };
                var time = $('#create_time').val();
                var platform = $('#c-order_platform').val();
                // var options = {
                //     type: 'post',
                //     url: 'datacenter/operationanalysis/operationkanban/operationalreport/index',
                //     data: {
                //         'time': time,
                //         'platform': platform
                //     }
                // }
                // EchartObj.api.ajax(options, chartOptions)
                Backend.api.ajax({
                    url: "datacenter/operationanalysis/operationkanban/operationalreport/index",
                    data: { 
                        'time': time,
                        'platform': platform    
                    }
                },function (data, ret) {
                    console.log(ret.rows);
                    console.log(ret.rows.order_status.status.length);
                    $('#general_order').text(ret.rows.general_order);
                    $('#general_money').text(ret.rows.general_money);
                    $('#wholesale_order').text(ret.rows.wholesale_order);
                    $('#wholesale_money').text(ret.rows.wholesale_money);
                    $('#celebrity_order').text(ret.rows.celebrity_order);
                    $('#celebrity_money').text(ret.rows.celebrity_money);
                    $('#reissue_order').text(ret.rows.reissue_order);
                    $('#reissue_money').text(ret.rows.reissue_money);
                    $('#fill_post_order').text(ret.rows.fill_post_order);
                    $('#fill_post_money').text(ret.rows.fill_post_money);
                    $('#general_order_percent').text(ret.rows.general_order_percent);
                    $('#wholesale_order_percent').text(ret.rows.wholesale_order_percent);
                    $('#celebrity_order_percent').text(ret.rows.celebrity_order_percent);
                    $('#reissue_order_percent').text(ret.rows.reissue_order_percent);
                    $('#fill_post_order_percent').text(ret.rows.fill_post_order_percent);
                    $('#usd_order_num').text(ret.rows.usd_order_num);
                    $('#usd_order_percent').text(ret.rows.usd_order_percent);
                    $('#usd_order_money').text(ret.rows.usd_order_money);
                    $('#usd_order_average_amount').text(ret.rows.usd_order_average_amount);
                    $('#cad_order_num').text(ret.rows.cad_order_num);
                    $('#cad_order_percent').text(ret.rows.cad_order_percent);
                    $('#cad_order_money').text(ret.rows.cad_order_money);
                    $('#cad_order_average_amount').text(ret.rows.cad_order_average_amount);
                    $('#aud_order_num').text(ret.rows.aud_order_num);
                    $('#aud_order_percent').text(ret.rows.aud_order_percent);
                    $('#aud_order_money').text(ret.rows.aud_order_money);
                    $('#aud_order_average_amount').text(ret.rows.aud_order_average_amount);
                    $('#eur_order_num').text(ret.rows.eur_order_num);
                    $('#eur_order_percent').text(ret.rows.eur_order_percent);
                    $('#eur_order_money').text(ret.rows.eur_order_money);
                    $('#eur_order_average_amount').text(ret.rows.eur_order_average_amount);
                    $('#gbp_order_num').text(ret.rows.gbp_order_num);
                    $('#gbp_order_percent').text(ret.rows.gbp_order_percent);
                    $('#gbp_order_money').text(ret.rows.gbp_order_money);
                    $('#gbp_order_average_amount').text(ret.rows.gbp_order_average_amount);

                },function(data,ret){
                    alert(ret.msg);
                    return false;
                });                       
        },
        api: {
            formatter: {
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
                                timePicker : true,
                                timePickerIncrement : 1
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
            },
            bindevent: function () {
                Form.api.bindevent($("form[role=form]"));
            }
        },        
    };
    return Controller;
});