define(['jquery', 'bootstrap', 'backend', 'table', 'form', 'echartsobj', 'custom-css', 'bootstrap-table-jump-to'], function ($, undefined, Backend, Table, Form, EchartObj) {

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
                        { field: 'm_sku', title: __('Meeloog_SKU'), operate: false },
                        { field: 'm_num', title: __('Meeloog站销量'), operate: false },
                        { field: 'available_stock', title: __('可用库存'), operate: false },
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

            //销售排行榜图表
            var chartOptions = {
                targetId: 'echart',
                downLoadTitle: '图表',
                type: 'bar',
                bar: {
                    xAxis: {
                        type: 'value',
                        boundaryGap: [0, 0.01]
                    },
                    yAxis: {
                        type: 'category',
                        data: []
                    }
                }
            };
            var time = $('#create_time').val();
            var site = $('.active').attr('data');
            var options = {
                type: 'post',
                url: 'datacenter/index/top_sale_list',
                data: {
                    'time': time,
                    'site': site
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
                    index_url: 'datacenter/index/top_sale_list' + location.search + '?time=' + Config.create_time + '&site=' + Config.label + '&type=list',
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
                        { field: 'platformsku', title: __('平台SKU'), operate: false },
                        { field: 'sku', title: __('SKU'), operate: false },
                        { field: 'name', title: __('商品名称'), operate: false },
                        { field: 'type_name', title: __('分类'), operate: false },
                        { field: 'available_stock', title: __('可用库存'), sortable: true,operate: false },
                        { field: 'sales_num', title: __('销量'), operate: false },
                        {
                            field: 'is_up', title: __('平台上下架状态'), operate: false,
                            custom: { 1: 'success', 2: 'danger' },
                            searchList: { 1: '上架', 2: '下架' },
                            formatter: Table.api.formatter.status
                        },


                    ]
                ]
            });

            // 为表格绑定事件
            Table.api.bindevent(table);

        },
        warehouse_data_analysis: function () {
            //库存分布
            var chartOptions = {
                targetId: 'echart',
                downLoadTitle: '图表',
                type: 'pie'
            };

            var options = {
                type: 'post',
                url: 'datacenter/index/warehouse_data_analysis',
                data: {
                    'key': 'pie'
                }

            }
            EchartObj.api.ajax(options, chartOptions)


            //订单处理
            var chartOptions = {
                targetId: 'echart2',
                downLoadTitle: '图表',
                type: 'line'
            };

            var options = {
                type: 'post',
                url: 'datacenter/index/warehouse_data_analysis',
                data: {
                    'key': 'line'
                }

            }
            EchartObj.api.ajax(options, chartOptions)


            // 初始化表格参数配置
            Table.api.init({
                commonSearch: false,
                search: false,
                showExport: false,
                showColumns: false,
                showToggle: false,
                pageList: [10, 25, 50, 100],
                extend: {
                    index_url: 'datacenter/index/warehouse_data_analysis' + location.search + '&key=list',
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
                        { field: 'create_date', title: __('日期'), operate: 'like' },
                        { field: 'arrival_num', title: __('到货'), operate: false },
                        { field: 'check_num', title: __('质检'), operate: false },
                        { field: 'print_label_num', title: __('打印标签'), operate: false },
                        { field: 'frame_num', title: __('配镜架'), operate: false },
                        { field: 'lens_num', title: __('配镜片'), operate: false },
                        { field: 'machining_num', title: __('加工'), operate: false },
                        { field: 'quality_num', title: __('成品质检'), operate: false }
                    ]
                ]
            });

            // 为表格绑定事件
            Table.api.bindevent(table);
        },
        purchase_data_analysis: function () {
            Controller.api.formatter.daterangepicker($("form[role=form1]"));
            //库存分布
            //销售排行榜图表
            var chartOptions = {
                targetId: 'echart',
                downLoadTitle: '图表',
                type: 'bar',
                bar: {
                    tooltip: { //提示框组件。
                        trigger: 'axis', // 触发类型。可选项item:数据项图形触发，主要在散点图，饼图等无类目轴的图表中使用。axis:坐标轴触发，主要在柱状图，折线图等会使用类目轴的图表中使用。
                        axisPointer: { //坐标轴指示器配置项。
                            type: 'shadow' //指示器类型。可选项'line' 直线指示器。'shadow' 阴影指示器。'cross' 十字准星指示器。其实是种简写，表示启用两个正交的轴的 axisPointer。
                        },
                        formatter: function (param) { //格式化提示信息
                            console.log(param);
                            return param[0].name + '<br/>' + param[0].seriesName + '：' + param[0].value + '<br/>' + param[1].seriesName + '：' + param[1].value;
                        }
                    },
                    grid: { //直角坐标系内绘图网格
                        top: '10%', //grid 组件离容器上侧的距离。
                        left: '5%', //grid 组件离容器左侧的距离。
                        right: '10%', //grid 组件离容器右侧的距离。
                        bottom: '10%', //grid 组件离容器下侧的距离。
                        containLabel: true //grid 区域是否包含坐标轴的刻度标签。
                    },
                    legend: { //图例配置
                        padding: 5,
                        top: '2%',
                        data: ['采购数量', '采购金额']
                    },
                    xAxis: [
                        {
                            type: 'category'
                        }
                    ],
                    yAxis: [
                        {
                            type: 'value',
                            name: '采购数量',
                            axisLabel: {
                                formatter: '{value} 个'
                            }
                        },
                        {
                            type: 'value',
                            name: '采购金额',
                            axisLabel: {
                                formatter: '{value} ¥'
                            }
                        }
                    ],
                }
            };

            var options = {
                type: 'post',
                url: 'datacenter/index/purchase_data_analysis'

            }
            EchartObj.api.ajax(options, chartOptions)

            //切换图表
            $(document).on('change', '.purchase_type', function () {
                var options = {
                    type: 'post',
                    url: 'datacenter/index/purchase_data_analysis',
                    data: {
                        purchase_type: $(this).val()
                    }
                }
                EchartObj.api.ajax(options, chartOptions)
            })


            //库存分布
            var chartOptions01 = {
                targetId: 'echart1',
                downLoadTitle: '图表',
                type: 'pie',
                pie: {
                    
                    tooltip: { //提示框组件。
                        trigger: 'item',
                        formatter: function (param) {
                            return param.data.name + '<br/>数量：' + param.data.value + '<br/> 占比：' + param.percent.toFixed(2) + '%';
                        }
                    },
                }
            };

            var options01 = {
                type: 'post',
                url: 'datacenter/index/purchase_data_analysis',
                data: {
                    'key': 'pie01'
                }

            }
            EchartObj.api.ajax(options01, chartOptions01)


            //库存分布
            var chartOptions02 = {
                targetId: 'echart2',
                downLoadTitle: '图表',
                type: 'pie',
                pie: {
                    tooltip: { //提示框组件。
                        trigger: 'item',
                        formatter: function (param) {
                            return param.data.name + '<br/>数量：' + param.data.value + '<br/> 占比：' + param.percent.toFixed(2) + '%';
                        }
                    },
                }
            };

            var options02 = {
                type: 'post',
                url: 'datacenter/index/purchase_data_analysis',
                data: {
                    'key': 'pie02'
                }

            }
            EchartObj.api.ajax(options02, chartOptions02)


            //切换图表
            $(document).on('click', '.btn-success', function () {
                var create_time = $('#create_time').val();
                if (!create_time) {
                    Toastr.error('请先选择时间范围');
                    return false;
                }
                var options01 = {
                    type: 'post',
                    url: 'datacenter/index/purchase_data_analysis',
                    data: {
                        'key': 'pie01',
                        'time': create_time
                    }

                }
                EchartObj.api.ajax(options01, chartOptions01)

                var options02 = {
                    type: 'post',
                    url: 'datacenter/index/purchase_data_analysis',
                    data: {
                        'key': 'pie02',
                        'time': create_time
                    }

                }
                EchartObj.api.ajax(options02, chartOptions02)
            })

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