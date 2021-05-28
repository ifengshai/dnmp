define(['jquery', 'bootstrap', 'backend', 'table', 'form', 'echartsobj'], function ($, undefined, Backend, Table, Form, EchartObj) {

    var Controller = {
        index: function () {
            Controller.api.bindevent();
            if(data_view()) {
                Controller.api.formatter.line_chart();
                Controller.api.formatter.line_chart2();
                Controller.api.formatter.line_chart3();
                Controller.api.formatter.line_chart4();
            }

            $("#sku_submit").click(function () {
                var time_str = $("#time_str").val();
                if (time_str.length <= 0) {
                    Layer.alert('请选择时间');
                    return false;
                }
                data_view();
                Controller.api.formatter.line_chart();
                Controller.api.formatter.line_chart2();
                Controller.api.formatter.line_chart3();
                Controller.api.formatter.line_chart4();
                return false;
            });
            $("#sku_reset").click(function () {
                $("#order_platform").val(1);
                $("#time_str").val('');
                $("#compare_time_str").val('');
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
                line_chart: function () {
                    var chartOptions = {
                        targetId: 'echart1',
                        downLoadTitle: '图表',
                        type: 'bar',
                        bar: {
                            tooltip: { //提示框组件。
                                trigger: 'axis', // 触发类型。可选项item:数据项图形触发，主要在散点图，饼图等无类目轴的图表中使用。axis:坐标轴触发，主要在柱状图，折线图等会使用类目轴的图表中使用。
                                axisPointer: { //坐标轴指示器配置项。
                                    type: 'line' //指示器类型。可选项'line' 直线指示器。'shadow' 阴影指示器。'cross' 十字准星指示器。其实是种简写，表示启用两个正交的轴的 axisPointer。
                                },
                                formatter: function (param) { //格式化提示信息
                                    console.log(param)
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
                                data: ['选择时间', '对比时间']
                            },
                            xAxis: [
                                {
                                    type: 'category'
                                }
                            ],
                            yAxis: [
                                {
                                    type: 'value',
                                    name: '选择时间',
                                    axisLabel: {
                                        formatter: '{value} '
                                    }
                                },
                                {
                                    type: 'value',
                                    name: '对比时间',
                                    axisLabel: {
                                        formatter: '{value} '
                                    }
                                }
                            ],
                        }
                    };

                    var options = {
                        type: 'post',
                        url: 'elasticsearch/customer/order/hour/ajaxGetChartsResult',
                        data: {
                            order_platform: $("#order_platform").val(),
                            time_str: $("#time_str").val(),
                            type: 1,
                            compare_time_str :$('#compare_time_str').val()
                        }

                    }
                    EchartObj.api.ajax(options, chartOptions)
                },
                line_chart2: function () {
                    var chartOptions = {
                        targetId: 'echart2',
                        downLoadTitle: '图表',
                        type: 'bar',
                        bar: {
                            tooltip: { //提示框组件。
                                trigger: 'axis', // 触发类型。可选项item:数据项图形触发，主要在散点图，饼图等无类目轴的图表中使用。axis:坐标轴触发，主要在柱状图，折线图等会使用类目轴的图表中使用。
                                axisPointer: { //坐标轴指示器配置项。
                                    type: 'line' //指示器类型。可选项'line' 直线指示器。'shadow' 阴影指示器。'cross' 十字准星指示器。其实是种简写，表示启用两个正交的轴的 axisPointer。
                                },
                                formatter: function (param) { //格式化提示信息
                                    console.log(param)
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
                                data: ['选择时间', '对比时间']
                            },
                            xAxis: [
                                {
                                    type: 'category'
                                }
                            ],
                            yAxis: [
                                {
                                    type: 'value',
                                    name: '选择时间',
                                    axisLabel: {
                                        formatter: '{value} '
                                    }
                                },
                                {
                                    type: 'value',
                                    name: '对比时间',
                                    axisLabel: {
                                        formatter: '{value} '
                                    }
                                }
                            ],
                        }
                    };

                    var options = {
                        type: 'post',
                        url: 'elasticsearch/customer/order/hour/ajaxGetChartsResult',
                        data: {
                            order_platform: $("#order_platform").val(),
                            time_str: $("#time_str").val(),
                            type: 2,
                            compare_time_str :$('#compare_time_str').val()
                        }

                    }
                    EchartObj.api.ajax(options, chartOptions)
                },
                line_chart3: function () {
                    var chartOptions = {
                        targetId: 'echart3',
                        downLoadTitle: '图表',
                        type: 'bar',
                        bar: {
                            tooltip: { //提示框组件。
                                trigger: 'axis', // 触发类型。可选项item:数据项图形触发，主要在散点图，饼图等无类目轴的图表中使用。axis:坐标轴触发，主要在柱状图，折线图等会使用类目轴的图表中使用。
                                axisPointer: { //坐标轴指示器配置项。
                                    type: 'line' //指示器类型。可选项'line' 直线指示器。'shadow' 阴影指示器。'cross' 十字准星指示器。其实是种简写，表示启用两个正交的轴的 axisPointer。
                                },
                                formatter: function (param) { //格式化提示信息
                                    console.log(param)
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
                                data: ['选择时间', '对比时间']
                            },
                            xAxis: [
                                {
                                    type: 'category'
                                }
                            ],
                            yAxis: [
                                {
                                    type: 'value',
                                    name: '选择时间',
                                    axisLabel: {
                                        formatter: '{value} '
                                    }
                                },
                                {
                                    type: 'value',
                                    name: '对比时间',
                                    axisLabel: {
                                        formatter: '{value} '
                                    }
                                }
                            ],
                        }
                    };

                    var options = {
                        type: 'post',
                        url: 'elasticsearch/customer/order/hour/ajaxGetChartsResult',
                        data: {
                            order_platform: $("#order_platform").val(),
                            time_str: $("#time_str").val(),
                            type: 3,
                            compare_time_str :$('#compare_time_str').val()
                        }

                    }
                    EchartObj.api.ajax(options, chartOptions)
                },
                line_chart4: function () {
                    var chartOptions = {
                        targetId: 'echart4',
                        downLoadTitle: '图表',
                        type: 'bar',
                        bar: {
                            tooltip: { //提示框组件。
                                trigger: 'axis', // 触发类型。可选项item:数据项图形触发，主要在散点图，饼图等无类目轴的图表中使用。axis:坐标轴触发，主要在柱状图，折线图等会使用类目轴的图表中使用。
                                axisPointer: { //坐标轴指示器配置项。
                                    type: 'line' //指示器类型。可选项'line' 直线指示器。'shadow' 阴影指示器。'cross' 十字准星指示器。其实是种简写，表示启用两个正交的轴的 axisPointer。
                                },
                                formatter: function (param) { //格式化提示信息
                                    console.log(param)
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
                                data: ['选择时间', '对比时间']
                            },
                            xAxis: [
                                {
                                    type: 'category'
                                }
                            ],
                            yAxis: [
                                {
                                    type: 'value',
                                    name: '选择时间',
                                    axisLabel: {
                                        formatter: '{value} '
                                    }
                                },
                                {
                                    type: 'value',
                                    name: '对比时间',
                                    axisLabel: {
                                        formatter: '{value} '
                                    }
                                }
                            ],
                        }
                    };

                    var options = {
                        type: 'post',
                        url: 'elasticsearch/customer/order/hour/ajaxGetChartsResult',
                        data: {
                            order_platform: $("#order_platform").val(),
                            time_str: $("#time_str").val(),
                            type: 4,
                            compare_time_str :$('#compare_time_str').val()
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
function data_view() {
    var order_platform = $('#order_platform').val();
    var time_str = $('#time_str').val();
    Backend.api.ajax({
        url: 'elasticsearch/customer/order/hour/ajaxGetResult',
        data: { order_platform: order_platform, time_str: time_str}
    }, function (data, ret) {
        var str = ret.data.str;
        $('#table_data').html(str);

        return true;
    }, function (data, ret) {
        Layer.alert(ret.msg);
        return false;
    });
    return true;
}