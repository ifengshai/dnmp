define(['jquery', 'bootstrap', 'backend', 'table', 'form', 'echartsobj', 'custom-css', 'bootstrap-table-jump-to'], function ($, undefined, Backend, Table, Form, EchartsObj) {

    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                showJumpto: true,
                searchFormVisible: true,
                showExport: false,
                pageList: [10, 25, 50, 100],
                extend: {
                    index_url: 'datacenter/index/index' + location.search,
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
                        { field: 'sku', title: __('SKU'), operate: 'like' },
                        { field: 'z_sku', title: __('Zeelool_SKU'), operate: false },
                        { field: 'z_num', title: __('Z站销量'), operate: false },
                        { field: 'v_sku', title: __('Voogueme_SKU'), operate: false },
                        { field: 'v_num', title: __('V站销量'), operate: false },
                        { field: 'n_sku', title: __('Nihao_SKU'), operate: false },
                        { field: 'n_num', title: __('Nihao站销量'), operate: false },
                        { field: 'available_stock', title: __('实时库存'), operate: false },
                        { field: 'all_num', title: __('汇总销量'), operate: false },
                        { field: 'created_at', visible: false, title: __('创建时间'), operate: 'RANGE', addclass: 'datetimerange' },

                    ]
                ]
            });

            // 为表格绑定事件
            Table.api.bindevent(table);

        },
        supply_chain_data: function () {
            Controller.api.bindevent();
        },
        warehouse_data: function () {
            Controller.api.formatter.daterangepicker($("form[role=form1]"));
        },
        top_sale_list: function () {
            Controller.api.formatter.daterangepicker($("form[role=form1]"));

            // 基于准备好的dom，初始化echarts实例
            var myChart = EchartsObj.init(document.getElementById('echart'), 'walden');



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
                    data: [__('Z站销量'), __('V站销量'), __('Nihao站销量')]
                },
                toolbox: {
                    show: false,
                    feature: {
                        magicType: { show: true, type: ['stack', 'tiled'] },
                        saveAsImage: { show: true }
                    }
                },
                xAxis: {
                    type: 'category',
                    boundaryGap: false,
                    data: Orderdata.column
                },
                yAxis: {},
                grid: [{
                    left: 'left',
                    top: 'top',
                    right: '10',
                    bottom: 30
                }],
                series: [{
                    name: __('Z站销量'),
                    type: 'line',
                    smooth: true,
                    areaStyle: {
                        normal: {}
                    },
                    lineStyle: {
                        normal: {
                            width: 1.5
                        }
                    },
                    data: Orderdata.zeeloolSalesNumList
                },
                {
                    name: __('V站销量'),
                    type: 'line',
                    smooth: true,
                    areaStyle: {
                        normal: {}
                    },
                    lineStyle: {
                        normal: {
                            width: 1.5
                        }
                    },
                    data: Orderdata.vooguemeSalesNumList
                },
                {
                    name: __('Nihao站销量'),
                    type: 'line',
                    smooth: true,
                    areaStyle: {
                        normal: {}
                    },
                    lineStyle: {
                        normal: {
                            width: 1.5
                        }
                    },
                    data: Orderdata.nihaoSalesNumList
                }
                ]
            };


            
             //男女比例
             var options = {
                url: '/admin/portrait/index',
                data: {
                    time: _this.time,
                    day: _this.day,
                    key: 'sex',
                }
            };
            if (_this.day == 'zidingyi') {
                options.data.day = '-1';
            }
            _this.options = options; //便于其他方法中使用

            var chart1Options = {
                targetId: 'chart1',
                downLoadID: "#nnbl",
                downLoadTitle: '男女比例',
                type: 'pie',
                pie: {
                    series: [{
                        name: '性别'
                    }]
                }
            };
            EchartObj.api.ajax(options, chart1Options)

            
            // 使用刚指定的配置项和数据显示图表。
            myChart.setOption(option);


            $(window).resize(function () {
                myChart.resize();
            });

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
            },
            bindevent: function () {
                Form.api.bindevent($("form[role=form]"));
            }
        },

    };
    return Controller;
});