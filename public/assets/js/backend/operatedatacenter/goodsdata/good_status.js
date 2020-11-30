define(['jquery', 'bootstrap', 'backend', 'addtabs', 'table', 'form', 'echartsobj', 'echarts', 'echarts-theme', 'template', 'custom-css'], function ($, undefined, Backend, Datatable, Table, Form, EchartObj, undefined, Template) {

    var Controller = {
        index: function () {
            Controller.api.formatter.line_histogram();
            Controller.api.formatter.user_data_pie();
            $("#platform_submit").click(function () {
                Controller.api.formatter.user_data_pie();
                Backend.api.ajax({
                    url: 'operatedatacenter/goodsdata/good_status/again_glass_same_data',
                    data: {
                        platform_a: $("#platform_a").val(),
                        platform_b: $("#platform_b").val(),
                    }
                }, function (data, ret) {
                    var again_num = ret.data.again_num;
                    var again_rate = ret.data.again_rate;
                    $('#again_num').text(again_num);
                    $('#again_rate').text(again_rate);
                    return false;
                }, function (data, ret) {
                    Layer.alert(ret.msg);
                    return false;
                });
            });
        },
        api: {
            formatter: {
                line_histogram: function () {
                    var chartOptions = {
                        targetId: 'echart3',
                        downLoadTitle: '图表',
                        type: 'bar',
                        bar: {
                            color: ['#003366', '#006699', '#4cabce'],
                            tooltip: {
                                trigger: 'axis',
                                axisPointer: {
                                    type: 'shadow'
                                },
                                formatter: function (param) {
                                    return param.data.name + '<br/>库存：' + param.data.value + '<br/> 占比：' + param.percent.toFixed(2) + '%';
                                }
                            },
                            legend: {
                                top: '2%',
                                data: ['客单价', '标准差', '中位数']
                            },
                            xAxis: {
                                type: 'category'
                            },
                            yAxis: {
                                type: 'value'
                            }
                        }
                    };

                    var options = {
                        type: 'post',
                        url: 'operatedatacenter/goodsdata/good_status/ajax_histogram',
                        data: {
                            order_platform: $("#order_platform").val(),
                        }
                    }
                    EchartObj.api.ajax(options, chartOptions);
                },
                user_data_pie: function () {
                    //库存分布
                    var chartOptions = {
                        targetId: 'echart2',
                        downLoadTitle: '图表',
                        type: 'pie',
                        pie: {

                            tooltip: { //提示框组件。
                                trigger: 'item',
                                formatter: function (param) {
                                    return param.data.name + '<br/>数量：' + param.data.value;
                                }
                            },
                        }
                    };

                    var options = {
                        type: 'post',
                        url: 'operatedatacenter/goodsdata/good_status/glass_same_data',
                        data: {
                            platform_a: $("#platform_a").val(),
                            platform_a_name: $("#platform_a option:selected").text(),
                            platform_b: $("#platform_b").val(),
                            platform_b_name: $("#platform_b option:selected").text(),
                        }
                    };
                    EchartObj.api.ajax(options, chartOptions);
                }
            },
            bindevent: function () {
                Form.api.bindevent($("form[role=form]"));
            }
        }
    };
    return Controller;
});
