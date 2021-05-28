define(['jquery', 'bootstrap', 'backend', 'addtabs', 'table', 'form', 'echartsobj', 'echarts', 'echarts-theme', 'template', 'custom-css'], function ($, undefined, Backend, Datatable, Table, Form, EchartObj, Echarts, Template) {

    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                extend: {
                    index_url: 'operatedatacenter/appdata/dashboard/index' + location.search,
                    add_url: 'operatedatacenter/appdata/dashboard/add',
                    edit_url: 'operatedatacenter/appdata/dashboard/edit',
                    del_url: 'operatedatacenter/appdata/dashboard/del',
                    multi_url: 'operatedatacenter/appdata/dashboard/multi',
                    table: 'dash_board',
                }
            });
            Controller.api.formatter.daterangepicker($("div[role=form]"));

            Controller.api.formatter.app_data_view();

            $("#app_submit").click(function () {
                var time_str = $("#input-date").val();
                if (time_str.length <= 0) {
                    Layer.alert('请选择时间');
                    return false;
                }
                Controller.api.formatter.app_data_view();
            });
            $(".reset").click(function(){
                $('#input-site').val(1);
                $('#input-platform').val(5);
                $('#input-date').val($('#input-date').data('default'));
                $('#input-compare-date').val('');
            });
        },
        add: function () {
            Controller.api.bindevent();
        },
        edit: function () {
            Controller.api.bindevent();
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
                                    format: 'YYYY-MM-DD',
                                    customRangeLabel: __("Custom Range"),
                                    applyLabel: __("Apply"),
                                    cancelLabel: __("Clear"),
                                },
                                ranges: ranges,
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
                app_data_view: function () {
                    var site = $('#input-site').val();
                    var platform = $('#input-platform').val();
                    var date = $('#input-date').val();
                    var compare_date= $('#input-compare-date').val();
                    Backend.api.ajax({
                        url: 'operatedatacenter/appdata/dashboard/index',
                        data: {
                            site: site,
                            platform: platform,
                            date: date,
                            compare_date: compare_date
                        }
                    }, function (data, ret) {
                        let current = data.current;
                        let compare = data.compare;

                        let getProperty = function (obj, pro) {
                            if (obj.hasOwnProperty(pro)) {
                                return obj[pro];
                            } else {
                                return null;
                            }
                        }

                        display_field('ga-sessions', current.ga_sessions, getProperty(compare, 'ga_sessions'))
                        display_field('ga-user', current.ga_users, getProperty(compare, 'ga_users'))
                        display_field('ga-first-open', current.first_open, getProperty(compare, 'first_open'))
                        display_field('ga-app-remove', current.app_remove, getProperty(compare, 'app_remove'))

                        display_field('order-money', current.order_money, getProperty(compare, 'order_money'))
                        display_field('order-num', current.order_num, getProperty(compare, 'order_num'))

                        display_field('conversion-session', current.conversion_session, getProperty(compare, 'conversion_session'), true)
                        display_field('conversion-user', current.conversion_user, getProperty(compare, 'conversion_user'), true)
                        $('#display-conversion-money-per-user').text(current.money_per_user)

                        Controller.api.formatter.echart_ga(current.ga_list);
                    }, function (data, ret) {
                        Layer.alert(ret.msg);
                    });
                },
                echart_ga: function (data) {
                    var chartDom = document.getElementById('echart-ga');
                    var myChart = Echarts.init(chartDom, 'walden');
                    var option;

                    let dates = $.map(data, function(n, i){
                        return n.date;
                    });
                    let sessions = $.map(data, function(n, i){
                        return n.sessions;
                    });
                    let users = $.map(data, function(n, i){
                        return n.activeUsers;
                    });

                    option = {
                        tooltip: {
                            trigger: 'axis'
                        },
                        legend: {
                            data: ['会话数', '用户数']
                        },
                        grid: {
                            left: '3%',
                            right: '4%',
                            bottom: '3%',
                            containLabel: true
                        },
                        xAxis: {
                            type: 'category',
                            boundaryGap: false,
                            data: dates
                        },
                        yAxis: {
                            type: 'value'
                        },
                        series: [
                            {
                                name: '会话数',
                                type: 'line',
                                stack: '总量',
                                data: sessions
                            },
                            {
                                name: '用户数',
                                type: 'line',
                                stack: '总量',
                                data: users
                            },
                        ]
                    };

                    option && myChart.setOption(option);
                },
            }
        }
    };
    return Controller;
});

function display_field(loc, current, compare, is_percent = false)
{
    let dom_num = $('#display-' + loc);
    if (is_percent) {
        dom_num.text(current + '%');
    } else {
        dom_num.text(current);
    }

    let dom_compare = $('#display-compare-' + loc);
    if (compare != null) {
        let subtraction = current - compare;

        let percent = 0;
        if (is_percent) {
            percent = Math.abs(subtraction).toFixed(2);
        } else {
            percent = compare > 0 ? Math.abs(subtraction / compare * 100).toFixed(2) : 0;
        }

        dom_compare.removeClass('text-gray text-red text-green');
        if (subtraction > 0) {
            dom_compare.addClass('text-red');
            dom_compare.html("<i class=\"fa fa-caret-up\"></i> " + percent + '%');
        } else if (subtraction === 0) {
            dom_compare.addClass('text-grey');
            dom_compare.html("<i class=\"fa fa-caret-right\"></i> " + percent + '%');
        } else {
            dom_compare.addClass('text-green');
            dom_compare.html("<i class=\"fa fa-caret-down\"></i> " + percent + '%');
        }
    } else {
        dom_compare.html('');
    }
}
