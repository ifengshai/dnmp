define(['jquery', 'bootstrap', 'backend', 'addtabs', 'table', 'form', 'echartsobj', 'echarts', 'echarts-theme', 'template', 'custom-css'], function ($, undefined, Backend, Datatable, Table, Form, EchartObj, undefined, Template) {

    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                extend: {
                    index_url: 'operatedatacenter/dataview/dash_board/index' + location.search,
                    add_url: 'operatedatacenter/dataview/dash_board/add',
                    edit_url: 'operatedatacenter/dataview/dash_board/edit',
                    del_url: 'operatedatacenter/dataview/dash_board/del',
                    multi_url: 'operatedatacenter/dataview/dash_board/multi',
                    table: 'dash_board',
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
                        {field: 'operate', title: __('Operate'), table: table, events: Table.api.events.operate, formatter: Table.api.formatter.operate}
                    ]
                ]
            });

            // 指定图表的配置项和数据
            Controller.api.formatter.daterangepicker($("form[role=form2]"));
            Controller.api.formatter.daterangepicker($("div[role=form8]"));



            //工单量概况折线图
            Controller.api.formatter.line_chart();
            Controller.api.formatter.funnel_chart();
            Controller.api.formatter.workload_line_chart();
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
                    //订单趋势统计折线图
                    var chartOptions = {
                        targetId: 'echart1',
                        downLoadTitle: '图表',
                        type: 'line'
                    };

                    var options = {
                        type: 'post',
                        url: 'operatedatacenter/dataview/dash_board/order_trend',
                        data: {
                            platform: $("#web_platform").val(),
                            workload_time: $("#workload_time").val(),
                            title_type: $("#title_type").val()
                        }
                    }
                    EchartObj.api.ajax(options, chartOptions)
                },
                workload_line_chart: function(){
                    //活跃用户趋势统计折线图
                    var chartOptions = {
                        targetId: 'echart3',
                        downLoadTitle: '图表',
                        type: 'line'
                    };

                    var options = {
                        type: 'post',
                        url: 'operatedatacenter/dataview/dash_board/active_user_trend',
                        data: {
                            platform:$("#order_platform").val(),
                            time_str:$("#one_time").val(),
                            group_id:$("#customer_type").val()
                        }
                    }
                    EchartObj.api.ajax(options, chartOptions)
                },
                funnel_chart: function(){
                    //活跃用户趋势统计折线图
                    var chartOptions = {
                        targetId: 'echart2',
                        downLoadTitle: '图表',
                        type: 'line'
                    };

                    var options = {
                        type: 'post',
                        url: 'operatedatacenter/dataview/dash_board/active_user_trend',
                        data: {
                            platform:$("#order_platform").val(),
                            time_str:$("#one_time").val(),
                            group_id:$("#customer_type").val()
                        }
                    }
                    EchartObj.api.ajax(options, chartOptions)
                }

            },
            bindevent: function () {
                Form.api.bindevent($("form[role=form]"));
            }
        },
    };
    return Controller;
});