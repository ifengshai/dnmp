define(['jquery', 'bootstrap', 'backend', 'table', 'form', 'echartsobj'], function ($, undefined, Backend, Table, Form, EchartObj) {

    var Controller = {
        index: function () {
            Controller.api.bindevent();
            if(data_view()) {
                Controller.api.formatter.sales_num_line();
                Controller.api.formatter.sales_money_line();
                Controller.api.formatter.order_num_line();
                Controller.api.formatter.unit_price_line();
            }

            $(document).on('change', '#order_platform', function () {
                if(data_view()) {
                    Controller.api.formatter.sales_num_line();
                    Controller.api.formatter.sales_money_line();
                    Controller.api.formatter.order_num_line();
                    Controller.api.formatter.unit_price_line();
                }
            });
            $("#time_str").on("apply.daterangepicker", function () {
                setTimeout(() => {
                    if(data_view()) {
                        Controller.api.formatter.sales_num_line();
                        Controller.api.formatter.sales_money_line();
                        Controller.api.formatter.order_num_line();
                        Controller.api.formatter.unit_price_line();
                    }
                }, 0)
            })
        },
        add: function () {
            Controller.api.bindevent();
        },
        edit: function () {
            Controller.api.bindevent();
        },
        api: {
            formatter: {
                sales_num_line: function () {
                    var chartOptions = {
                        targetId: 'echart1',
                        downLoadTitle: '图表',
                        type: 'line',
                    };
                    var options = {
                        type: 'post',
                        url: 'elasticsearch/operate/hour/ajaxGetResult',
                        data: {
                            'order_platform' : $("#order_platform").val(),
                            'time_str' : $("#time_str").val(),
                            'type' : 1
                        }
                    }
                    EchartObj.api.ajax(options, chartOptions);
                },
                sales_money_line: function () {
                    var chartOptions = {
                        targetId: 'echart2',
                        downLoadTitle: '图表',
                        type: 'line',
                        line: {
                            legend: { //图例配置
                                padding: 5,
                                top: '2%',
                                data: ['销售额']
                            },
                            grid: {
                                left: '15%',
                            },
                        }
                    };
                    var options = {
                        type: 'post',
                        url: 'elasticsearch/operate/hour/ajaxGetResult',
                        data: {
                            'order_platform' : $("#order_platform").val(),
                            'time_str' : $("#time_str").val(),
                            'type' : 2
                        }
                    }
                    EchartObj.api.ajax(options, chartOptions);
                },
                order_num_line: function () {
                    var chartOptions = {
                        targetId: 'echart3',
                        downLoadTitle: '图表',
                        type: 'line'
                    };
                    var options = {
                        type: 'post',
                        url: 'elasticsearch/operate/hour/ajaxGetResult',
                        data: {
                            'order_platform' : $("#order_platform").val(),
                            'time_str' : $("#time_str").val(),
                            'type' : 3
                        }
                    }
                    EchartObj.api.ajax(options, chartOptions);
                },
                unit_price_line: function () {
                    var chartOptions = {
                        targetId: 'echart4',
                        downLoadTitle: '图表',
                        type: 'line'
                    };
                    var options = {
                        type: 'post',
                        url: 'elasticsearch/operate/hour/ajaxGetResult',
                        data: {
                            'order_platform' : $("#order_platform").val(),
                            'time_str' : $("#time_str").val(),
                            'type' : 4
                        }
                    }
                    EchartObj.api.ajax(options, chartOptions);
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
        url: 'elasticsearch/operate/hour/ajaxGetResult',
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