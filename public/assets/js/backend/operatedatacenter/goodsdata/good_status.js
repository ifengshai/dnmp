define(['jquery', 'bootstrap', 'backend', 'addtabs', 'table', 'form', 'echartsobj', 'echarts', 'echarts-theme', 'template', 'custom-css'], function ($, undefined, Backend, Datatable, Table, Form, EchartObj, undefined, Template) {

    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                extend: {
                    index_url: 'operatedatacenter/orderdata/order_data_view/index' + location.search,
                    add_url: 'operatedatacenter/orderdata/order_data_view/add',
                    edit_url: 'operatedatacenter/orderdata/order_data_view/edit',
                    del_url: 'operatedatacenter/orderdata/order_data_view/del',
                    multi_url: 'operatedatacenter/orderdata/order_data_view/multi',
                    table: 'order_data_view',
                }
            });
            Controller.api.formatter.line_histogram();
            Controller.api.formatter.user_data_pie();
            $("#platform_submit").click(function() {
                Controller.api.formatter.user_data_pie();
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
                line_histogram: function (){
                    //柱状图和折线图的结合
                    var chartOptions = {
                        targetId: 'echart3',
                        downLoadTitle: '图表',
                        type: 'bar',
                        bar:{
                            legend: {},
                            tooltip: {},
                            xAxis: {type: 'category'},
                            yAxis: {},
                            series: [
                                {type: 'bar'},
                                {type: 'bar'},
                                {type: 'bar'},
                                {type: 'bar'}
                            ]
                        }
                    };

                    var options = {
                        type: 'post',
                        url: 'operatedatacenter/goodsdata/good_status/ajax_histogram',
                        data: {
                            order_platform: $("#order_platform").val(),
                            time_str: $("#time_str").val(),
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
                            platform_a:$("#platform_a").val(),
                            platform_a_name:$("#platform_a option:selected").text(),
                            platform_b:$("#platform_b").val(),
                            platform_b_name:$("#platform_b option:selected").text(),
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
