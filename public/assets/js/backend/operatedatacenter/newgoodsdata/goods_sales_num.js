define(['jquery', 'bootstrap', 'backend', 'table', 'form', 'echartsobj', 'custom-css', 'bootstrap-table-jump-to'], function ($, undefined, Backend, Table, Form, EchartObj) {

    var Controller = {
        index: function () {
            Controller.api.bindevent();
            Controller.api.formatter.daterangepicker($("form[role=form1]"));
            Controller.api.formatter.sales_num_line();   //销量排行
            Controller.api.formatter.new_sales_num_line();   //新品销量排行
            // 初始化表格参数配置
            // 初始化表格参数配置
            Table.api.init();
            
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

            $("#sku_submit").click(function () {
                Controller.api.formatter.sales_num_line();   //销量排行
                var params = table.bootstrapTable('getOptions')
                params.queryParams = function (params) {

                    //定义参数
                    var filter = {};
                    //遍历form 组装json
                    $.each($("#form").serializeArray(), function (i, field) {
                        filter[field.name] = field.value;
                    });

                    //参数转为json字符串
                    params.filter = JSON.stringify(filter)
                    console.info(params);
                    return params;
                }

                table.bootstrapTable('refresh', params);
            });

        },
        table: {
            first: function () {
                // 表格1
                var table1 = $("#table1");
                table1.bootstrapTable({
                    url: 'operatedatacenter/newgoodsdata/goods_sales_num/table1',
                    toolbar: '#toolbar1',
                    sortName: 'id',
                    search: false,
                    columns: [
                        [
                            { field: 'platformsku', title: __('平台SKU'), operate: false },
                            { field: 'sku', title: __('SKU'), operate: false },
                            { field: 'shelves_date', title: __('上架时间'), operate: false },
                            { field: 'type_name', title: __('分类'), operate: false },
                            { field: 'available_stock', title: __('虚拟仓库存'), sortable: true,operate: false },
                            { field: 'sales_num', title: __('销量'), operate: false },
                            { field: 'online_day', title: __('在线天数'), operate: false },
                            { field: 'sales_num_day', title: __('日均销量'), operate: false },
                            {
                                field: 'online_status', title: __('在售状态（实时）'), operate: false,
                                custom: { 1: 'success', 2: 'danger',3:'blue' },
                                searchList: { 1: '上架', 2: '售罄' ,3:'下架'},
                                formatter: Table.api.formatter.status
                            },
                        ]
                    ]
                });

                // 为表格1绑定事件
                Table.api.bindevent(table1);
            },
            second: function () {
                // 表格2
                var table2 = $("#table2");
                table2.bootstrapTable({
                    url: 'operatedatacenter/newgoodsdata/goods_sales_num/table2',
                    toolbar: '#toolbar2',
                    sortName: 'id',
                    search: false,
                    columns: [
                        [
                            { field: 'platformsku', title: __('平台SKU'), operate: false },
                            { field: 'sku', title: __('SKU'), operate: false },
                            { field: 'shelves_date', title: __('上架时间'), operate: false },
                            { field: 'type_name', title: __('分类'), operate: false },
                            { field: 'available_stock', title: __('虚拟仓库存'), sortable: true,operate: false },
                            { field: 'sales_num', title: __('销量'), operate: false },
                            { field: 'online_day', title: __('在线天数'), operate: false },
                            { field: 'sales_num_day', title: __('日均销量'), operate: false },
                            {
                                field: 'online_status', title: __('在售状态（实时）'), operate: false,
                                custom: { 1: 'success', 2: 'danger',3:'blue' },
                                searchList: { 1: '上架', 2: '售罄' ,3:'下架'},
                                formatter: Table.api.formatter.status
                            },
                        ]
                    ]
                });

                // 为表格2绑定事件
                Table.api.bindevent(table2);
            }
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
                sales_num_line: function (){
                    //销售排行榜图表
                    var chartOptions = {
                        targetId: 'echart',
                        downLoadTitle: '图表',
                        type: 'bar',
                        bar: {
                            yAxis: [
                                {
                                    type: 'value',
                                    name: '销量',
                                    axisLabel: {
                                        formatter: '{value}'
                                    }
                                },
                            ],
                        }
                    };
                    var time = $('#create_time').val();
                    var site = $('.active').attr('data');
                    var options = {
                        type: 'post',
                        url: 'operatedatacenter/newgoodsdata/goods_sales_num/sales_num_line',
                        data: {
                            'time': time,
                            'site': site,
                            'type': 1,
                        }
                    }
                    EchartObj.api.ajax(options, chartOptions)
                },
                new_sales_num_line: function (){
                    //新品销售排行榜图表
                    var chartOptions = {
                        targetId: 'echart1',
                        downLoadTitle: '图表',
                        type: 'bar',
                        bar: {
                            yAxis: [
                                {
                                    type: 'value',
                                    name: '销量',
                                    axisLabel: {
                                        formatter: '{value}'
                                    }
                                },
                            ],
                        }
                    };
                    var time = $('#create_time').val();
                    var site = $('.active').attr('data');
                    var options = {
                        type: 'post',
                        url: 'operatedatacenter/newgoodsdata/goods_sales_num/sales_num_line',
                        data: {
                            'time': time,
                            'site': site,
                            'type': 2,
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