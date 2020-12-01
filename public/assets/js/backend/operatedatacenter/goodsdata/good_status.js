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
            $(document).on('change', '#order_platform', function () {
                Controller.api.formatter.line_histogram();
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
                            // tooltip: {
                            //     trigger: 'axis',
                            //     axisPointer: {
                            //         type: 'shadow'
                            //     },
                            //     formatter: function (param) {
                            //         return param.data.name + '<br/>库存：' + param.data.value + '<br/> 占比：' + param.percent.toFixed(2) + '%';
                            //     }
                            // },
                            tooltip: { //提示框组件。
                                trigger: 'axis', // 触发类型。可选项item:数据项图形触发，主要在散点图，饼图等无类目轴的图表中使用。axis:坐标轴触发，主要在柱状图，折线图等会使用类目轴的图表中使用。
                                axisPointer: { //坐标轴指示器配置项。
                                    type: 'shadow' //指示器类型。可选项'line' 直线指示器。'shadow' 阴影指示器。'cross' 十字准星指示器。其实是种简写，表示启用两个正交的轴的 axisPointer。
                                },
                                formatter: function (param) { //格式化提示信息
                                    if(param.length == 3){
                                        return param[0].name + '<br/>' + param[0].seriesName + '：' + param[0].value + '<br/>' + param[1].seriesName + '：' + param[1].value + '<br/>' + param[2].seriesName + '：' + param[2].value;
                                    }else if(param.length == 2){
                                        return param[0].name + '<br/>' + param[0].seriesName + '：' + param[0].value + '<br/>' + param[1].seriesName + '：' + param[1].value;
                                    }else{
                                        return param[0].name + '<br/>' + param[0].seriesName + '：' + param[0].value;
                                    }
                                }
                            },
                            legend: {
                                top: '2%',
                                data: ['在售', '预售', '下架']
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
