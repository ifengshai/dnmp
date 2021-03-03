define(['jquery', 'bootstrap', 'backend', 'addtabs', 'table', 'form', 'echartsobj', 'echarts', 'echarts-theme', 'template', 'custom-css'], function ($, undefined, Backend, Datatable, Table, Form, EchartObj, undefined, Template) {

    var Controller = {
        index: function () {
            Controller.api.formatter.daterangepicker($("div[role=form]"));
            //订单数据概况折线图
            stock_measure_overview_platform();
            Controller.api.formatter.bar_chart();
            Controller.api.formatter.dull_stock_change_barline();
            Controller.api.formatter.purchase_sales_barline();
            Controller.api.formatter.line_histogram();
            Controller.api.formatter.track_logistics_barline();
            Controller.api.formatter.comleted_time_rate_pie();
            $("#time_str5").on("apply.daterangepicker", function () {
                setTimeout(() => {
                    Controller.api.formatter.bar_chart();   //库存变化折线图
                }, 0)
            })
            $("#time_str5").on("cancel.daterangepicker", function () {
                setTimeout(() => {
                    Controller.api.formatter.bar_chart();   //库存变化折线图
                }, 0)
            })

            $("#time_str6").on("apply.daterangepicker", function () {
                setTimeout(() => {
                    Controller.api.formatter.dull_stock_change_barline();   //呆滞库存变化折线图
                }, 0)
            })
            $("#time_str6").on("cancel.daterangepicker", function () {
                setTimeout(() => {
                    Controller.api.formatter.dull_stock_change_barline();   //呆滞库存变化折线图
                }, 0)
            })
            $("#time_str7").on("apply.daterangepicker", function () {
                setTimeout(() => {
                    Controller.api.formatter.purchase_sales_barline();   //月度采购数量、采销比折线图
                }, 0)
            })
            $("#time_str7").on("cancel.daterangepicker", function () {
                setTimeout(() => {
                    Controller.api.formatter.purchase_sales_barline();   //月度采购数量、采销比折线图
                }, 0)
            })
            $("#time_str8").on("apply.daterangepicker", function () {
                setTimeout(() => {
                    Controller.api.formatter.track_logistics_barline();   //物流妥投概况折线图
                }, 0)
            })
            $("#time_str8").on("cancel.daterangepicker", function () {
                setTimeout(() => {
                    Controller.api.formatter.track_logistics_barline();   //物流妥投概况折线图
                }, 0)
            })
            $("#time_str9").on("apply.daterangepicker", function () {
                setTimeout(() => {
                    index_data();   //仓库指标总览
                }, 0)
            })
            $("#time_str9").on("cancel.daterangepicker", function () {
                setTimeout(() => {
                    index_data();   //仓库指标总览
                }, 0)
            })

            $("#time_str1").on("apply.daterangepicker", function () {
                setTimeout(() => {
                    stock_measure_overview_platform();   //仓库和站点有关的指标
                }, 0)
            })
            $("#time_str1").on("cancel.daterangepicker", function () {
                setTimeout(() => {
                    stock_measure_overview_platform();   //仓库和站点有关的指标
                }, 0)
            })

            $("#time_str2").on("apply.daterangepicker", function () {
                setTimeout(() => {
                    purchase_data();     //采购概况
                }, 0)
            })
            $("#time_str2").on("cancel.daterangepicker", function () {
                setTimeout(() => {
                    purchase_data();     //采购概况
                }, 0)
            })

            $("#time_str3").on("apply.daterangepicker", function () {
                setTimeout(() => {
                    Controller.api.formatter.line_histogram();   //订单发货及时率
                }, 0)
            })
            $("#time_str3").on("cancel.daterangepicker", function () {
                setTimeout(() => {
                    Controller.api.formatter.line_histogram();   //订单发货及时率
                }, 0)
            })

            $("#time_str4").on("apply.daterangepicker", function () {
                setTimeout(() => {
                    track_data();   //物流妥投
                    Controller.api.formatter.comleted_time_rate_pie();   //妥投占比
                }, 0)
            })
            $("#time_str4").on("cancel.daterangepicker", function () {
                setTimeout(() => {
                    track_data();   //物流妥投
                    Controller.api.formatter.comleted_time_rate_pie();   //妥投占比
                }, 0)
            })
            $(document).on('change', '#order_platform', function () {
                stock_measure_overview_platform();
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
                bar_chart: function () {
                    //月平均库存变化
                    var chartOptions = {
                        targetId: 'echart1',
                        downLoadTitle: '图表',
                        type: 'bar',
                        bar: {
                            tooltip: { //提示框组件。
                                trigger: 'axis', // 触发类型。可选项item:数据项图形触发，主要在散点图，饼图等无类目轴的图表中使用。axis:坐标轴触发，主要在柱状图，折线图等会使用类目轴的图表中使用。
                                axisPointer: { //坐标轴指示器配置项。
                                    type: 'shadow' //指示器类型。可选项'line' 直线指示器。'shadow' 阴影指示器。'cross' 十字准星指示器。其实是种简写，表示启用两个正交的轴的 axisPointer。
                                },
                                formatter: function (param) { //格式化提示信息
                                    return param[0].name + '<br/>' + param[0].seriesName + '：' + param[0].value + '<br/>' + param[1].seriesName + '：' + param[1].value + '%';
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
                                data: ['月平均库存', '采销比']
                            },
                            xAxis: [
                                {
                                    type: 'category'
                                }
                            ],
                            yAxis: [
                                {
                                    type: 'value',
                                    name: '月平均库存',
                                    axisLabel: {
                                        formatter: '{value} '
                                    }
                                },
                                {
                                    type: 'value',
                                    name: '采销比',
                                    axisLabel: {
                                        formatter: '{value} %'
                                    }
                                },
                            ],
                        }
                    };
                    var options = {
                        type: 'post',
                        url: 'supplydatacenter/data_market/stock_change_bar',
                        data: {
                            time_str: $("#time_str5").val()
                        }
                    }
                    EchartObj.api.ajax(options, chartOptions)
                },
                dull_stock_change_barline: function (){
                    //柱状图和折线图的结合
                    var chartOptions = {
                        targetId: 'echart3',
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
                                    return param[0].name + '<br/>' + param[0].seriesName + '：' + param[0].value + '<br/>' + param[1].seriesName + '：' + param[1].value + '%';
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
                                data: ['平均呆滞库存', '呆滞库存占比']
                            },
                            xAxis: [
                                {
                                    type: 'category'
                                }
                            ],
                            yAxis: [
                                {
                                    type: 'value',
                                    name: '平均呆滞库存',
                                    axisLabel: {
                                        formatter: '{value} 个'
                                    }
                                },
                                {
                                    type: 'value',
                                    name: '呆滞库存占比',
                                    axisLabel: {
                                        formatter: '{value} %'
                                    }
                                },
                            ],
                        }
                    };
        
                    var options = {
                        type: 'post',
                        url: 'supplydatacenter/data_market/dull_stock_change_barline',
                        data: {
                            time_str: $("#time_str6").val(),
                        }
                    }
                    EchartObj.api.ajax(options, chartOptions)
                },
                purchase_sales_barline: function (){
                    //柱状图和折线图的结合
                    var chartOptions = {
                        targetId: 'echart5',
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
                                    return param[0].name + '<br/>' + param[0].seriesName + '：' + param[0].value + '<br/>' + param[1].seriesName + '：' + param[1].value + '%';
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
                                data: ['月度采购数量', '采销比']
                            },
                            xAxis: [
                                {
                                    type: 'category'
                                }
                            ],
                            yAxis: [
                                {
                                    type: 'value',
                                    name: '月度采购数量',
                                    axisLabel: {
                                        formatter: '{value} 个'
                                    }
                                },
                                {
                                    type: 'value',
                                    name: '采销比',
                                    axisLabel: {
                                        formatter: '{value} %'
                                    }
                                },
                            ],
                        }
                    };
        
                    var options = {
                        type: 'post',
                        url: 'supplydatacenter/data_market/purchase_sales_barline',
                        data: {
                            time_str: $("#time_str7").val(),
                        }
                    }
                    EchartObj.api.ajax(options, chartOptions)
                },
                line_histogram: function (){
                    //柱状图和折线图的结合
                    var chartOptions = {
                        targetId: 'echart2',
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
                                    return param[0].name + '<br/>' + param[0].seriesName + '：' + param[0].value + '<br/>' + param[1].seriesName + '：' + param[1].value + '%<br/>' + param[2].seriesName + '：' + param[2].value+'%';
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
                                data: ['订单数', '及时率', '平均及时率']
                            },
                            xAxis: [
                                {
                                    type: 'category'
                                }
                            ],
                            yAxis: [
                                {
                                    type: 'value',
                                    name: '订单数',
                                    axisLabel: {
                                        formatter: '{value} 个'
                                    }
                                },
                                {
                                    type: 'value',
                                    name: '及时率',
                                    axisLabel: {
                                        formatter: '{value} %'
                                    }
                                },
                            ],
                        }
                    };
        
                    var options = {
                        type: 'post',
                        url: 'supplydatacenter/data_market/order_histogram_line',
                        data: {
                            time_str: $("#time_str3").val(),
                        }
                    }
                    EchartObj.api.ajax(options, chartOptions)
                },
                track_logistics_barline: function (){
                    //柱状图和折线图的结合
                    var chartOptions = {
                        targetId: 'echart6',
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
                                    return param[0].name + '<br/>' + param[0].seriesName + '：' + param[0].value + '<br/>' + param[1].seriesName + '：' + param[1].value + '%';
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
                                data: ['发货数量', '及时妥投率']
                            },
                            xAxis: [
                                {
                                    type: 'category'
                                }
                            ],
                            yAxis: [
                                {
                                    type: 'value',
                                    name: '发货数量',
                                    axisLabel: {
                                        formatter: '{value} 个'
                                    }
                                },
                                {
                                    type: 'value',
                                    name: '及时妥投率',
                                    axisLabel: {
                                        formatter: '{value} %'
                                    }
                                },
                            ],
                        }
                    };
        
                    var options = {
                        type: 'post',
                        url: 'supplydatacenter/data_market/track_logistics_barline',
                        data: {
                            time_str: $("#time_str8").val(),
                        }
                    }
                    EchartObj.api.ajax(options, chartOptions)
                },
                comleted_time_rate_pie: function () {
                    //妥投时效占比
                    var chartOptions = {
                        targetId: 'echart4',
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

                    var options = {
                        type: 'post',
                        url: 'supplydatacenter/data_market/comleted_time_rate',
                        data: {
                            'time_str' :  $("#time_str4").val(),
                        }

                    };
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
function index_data(){
    var time_str = $('#time_str9').val();
    Backend.api.ajax({
        url: 'supplydatacenter/data_market/index',
        data: {time_str: time_str}
    }, function (data, ret) {
        var stock_measure_overview = ret.data;
        //仓库指标总览
        $('#turnover_rate').html(stock_measure_overview.turnover_rate);
        $('#turnover_days_rate').html(stock_measure_overview.turnover_days_rate);
        return false;
    }, function (data, ret) {
        Layer.alert(ret.msg);
        return false;
    });
}
function purchase_data(){
    var time_str = $('#time_str2').val();
    Backend.api.ajax({
        url: 'supplydatacenter/data_market/purchase_data',
        data: {time_str: time_str}
    }, function (data, ret) {
        console.log(ret.data);
        var purchase_overview = ret.data;
        //采购概况
        $('#purchase_num').html(purchase_overview.purchase_num);
        $('#purchase_num_big').html(purchase_overview.purchase_num_big);
        $('#purchase_num_big_rate').html(purchase_overview.purchase_num_big_rate);
        $('#purchase_num_now').html(purchase_overview.purchase_num_now);
        $('#purchase_num_now_rate').html(purchase_overview.purchase_num_now_rate);

        $('#purchase_amount').html(purchase_overview.purchase_amount);
        $('#purchase_amount_big').html(purchase_overview.purchase_amount_big);
        $('#purchase_amount_big_rate').html(purchase_overview.purchase_amount_big_rate);
        $('#purchase_amount_now').html(purchase_overview.purchase_amount_now);
        $('#purchase_amount_now_rate').html(purchase_overview.purchase_amount_now_rate);

        $('#purchase_sku_num').html(purchase_overview.purchase_sku_num);
        $('#purchase_sku_num_big').html(purchase_overview.purchase_sku_num_big);
        $('#purchase_sku_num_big_rate').html(purchase_overview.purchase_sku_num_big_rate);
        $('#purchase_sku_num_now').html(purchase_overview.purchase_sku_num_now);
        $('#purchase_sku_num_now_rate').html(purchase_overview.purchase_sku_num_now_rate);

        $('#purchase_delay_rate').html(purchase_overview.purchase_delay_rate);
        $('#purchase_delay_rate_big').html(purchase_overview.purchase_delay_rate_big);
        $('#purchase_delay_rate_now').html(purchase_overview.purchase_delay_rate_now);
        
        $('#purchase_qualified_rate').html(purchase_overview.purchase_qualified_rate);
        $('#purchase_qualified_rate_big').html(purchase_overview.purchase_qualified_rate_big);
        $('#purchase_qualified_rate_now').html(purchase_overview.purchase_qualified_rate_now);

        $('#purchase_price').html(purchase_overview.purchase_price);
        $('#purchase_price_big').html(purchase_overview.purchase_price_big);
        $('#purchase_price_now').html(purchase_overview.purchase_price_now);
        return false;
    }, function (data, ret) {
        Layer.alert(ret.msg);
        return false;
    });
}
function track_data(){
    var time_str = $('#time_str4').val();
    Backend.api.ajax({
        url: 'supplydatacenter/data_market/track_data',
        data: {time_str: time_str}
    }, function (data, ret) {
        var logistics_completed_overview = ret.data;
        //物流妥投概况
        $('#delivery_count').html(logistics_completed_overview.delivery_count);
        $('#completed_count').html(logistics_completed_overview.completed_count);
        $('#uncompleted_count').html(logistics_completed_overview.uncompleted_count);
        $('#timeout_uncompleted_count').html(logistics_completed_overview.timeout_uncompleted_count);
        return false;
    }, function (data, ret) {
        Layer.alert(ret.msg);
        return false;
    });
}
function stock_measure_overview_platform() {
    var order_platform = $('#order_platform').val();
    var time_str = $('#time_str1').val();
    Backend.api.ajax({
        url: 'supplydatacenter/data_market/stock_measure_overview_platform',
        data: { order_platform: order_platform, time_str: time_str}
    }, function (data, ret) {
        var virtual_turnover_rate = ret.data.virtual_turnover_rate;
        var virtual_turnover_days_rate = ret.data.virtual_turnover_days_rate;
        var virtual_month_in_out_rate = ret.data.virtual_month_in_out_rate;
        
        $('#virtual_turnover_rate').html(virtual_turnover_rate);
        $('#virtual_turnover_days_rate').html(virtual_turnover_days_rate);
        $('#virtual_month_in_out_rate').html(virtual_month_in_out_rate);
        return false;
    }, function (data, ret) {
        Layer.alert(ret.msg);
        return false;
    });
}