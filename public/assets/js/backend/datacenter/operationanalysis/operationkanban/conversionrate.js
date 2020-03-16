define(['jquery', 'bootstrap', 'backend', 'addtabs', 'table','form','echartsobj', 'echarts', 'echarts-theme', 'template','custom-css'], function ($, undefined, Backend, Datatable, Table,Form, EchartObj, undefined, Template) {

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
                var options = {
                    type: 'post',
                    url: 'datacenter/operationanalysis/operationkanban/conversionrate/index',
                    data: {
                        'time': time,
                        'platform': platform
                    }
                }
                EchartObj.api.ajax(options, chartOptions)
            // 初始化表格参数配置
            Table.api.init({
                commonSearch: false,
                search: false,
                showExport: true,
                showColumns: true,
                showToggle: true,
                pagination: false,
                extend: {
                    index_url: 'datacenter/operationanalysis/operationkanban/conversionrate/index' + location.search + '?time=' + Config.create_time + '&platform=' + Config.platform + '&type=list',
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
                        { checkbox: true },
                        {
                            field: '', title: __('序号'), formatter: function (value, row, index) {
                                var options = table.bootstrapTable('getOptions');
                                var pageNumber = options.pageNumber;
                                var pageSize = options.pageSize;
                                return (pageNumber - 1) * pageSize + 1 + index;
                            }, operate: false
                        },
                        { field: 'create_date', title: __('时间'), operate: false },
                        { field: 'sales_money', title: __('销售额（$）'), operate: false },
                        { field: 'unit_price', title: __('客单价'), operate: false },
                        { field: 'shoppingcart_update_total', title: __('购物车数量'), operate: false },
                        { field: 'sales_num', title: __('订单数量'), operate: false },
                        { field: 'shoppingcart_update_conversion', title: __('购物车转化率'), operate: false },
                    ]
                ]
            });

            // 为表格绑定事件
            Table.api.bindevent(table);                           
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