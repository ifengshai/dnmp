define(['jquery', 'bootstrap', 'backend', 'table', 'form', 'echartsobj'], function ($, undefined, Backend, Table, Form, EchartObj) {

    var Controller = {
        index: function () {
            Controller.api.bindevent();
            Controller.api.formatter.sales_num_line();
            Controller.api.formatter.sales_money_line();
            Controller.api.formatter.order_num_line();
            Controller.api.formatter.unit_price_line();
            $(document).on('change', '#order_platform', function () {
                data_view();
                Controller.api.formatter.sales_num_line();
                Controller.api.formatter.sales_money_line();
                Controller.api.formatter.order_num_line();
                Controller.api.formatter.unit_price_line();
            });
            $("#time_str").on("apply.daterangepicker", function () {
                setTimeout(() => {
                    data_view();
                    Controller.api.formatter.sales_num_line();
                    Controller.api.formatter.sales_money_line();
                    Controller.api.formatter.order_num_line();
                    Controller.api.formatter.unit_price_line();
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
                        type: 'line'
                    };
                    var options = {
                        type: 'post',
                        url: 'operatedatacenter/dataview/time_data/sales_num_line',
                        data: {
                            'order_platform' : $("#order_platform").val(),
                            'time_str' : $("#time_str").val(),
                        }
                    }
                    EchartObj.api.ajax(options, chartOptions)
                },
                sales_money_line: function () {
                    var chartOptions = {
                        targetId: 'echart2',
                        downLoadTitle: '图表',
                        type: 'line',
                        bar: {
                            yAxis: [
                                {
                                    type: 'value',
                                    name: '销售额',
                                    axisLabel: {
                                        formatter: '$ {value}'
                                    },
                                    offset: -5
                                }
                                
                            ],
                        }
                    };
                    var options = {
                        type: 'post',
                        url: 'operatedatacenter/dataview/time_data/sales_money_line',
                        data: {
                            'order_platform' : $("#order_platform").val(),
                            'time_str' : $("#time_str").val(),
                        }
                    }
                    EchartObj.api.ajax(options, chartOptions)
                },
                order_num_line: function () {
                    var chartOptions = {
                        targetId: 'echart3',
                        downLoadTitle: '图表',
                        type: 'line'
                    };
                    var options = {
                        type: 'post',
                        url: 'operatedatacenter/dataview/time_data/order_num_line',
                        data: {
                            'order_platform' : $("#order_platform").val(),
                            'time_str' : $("#time_str").val(),
                        }
                    }
                    EchartObj.api.ajax(options, chartOptions)
                },
                unit_price_line: function () {
                    var chartOptions = {
                        targetId: 'echart4',
                        downLoadTitle: '图表',
                        type: 'line'
                    };
                    var options = {
                        type: 'post',
                        url: 'operatedatacenter/dataview/time_data/unit_price_line',
                        data: {
                            'order_platform' : $("#order_platform").val(),
                            'time_str' : $("#time_str").val(),
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
        url: 'operatedatacenter/dataview/time_data/ajax_get_data',
        data: { order_platform: order_platform, time_str: time_str }
    }, function (data, ret) {
        var order_platform = ret.data.order_platform;
        var time_str = ret.data.time_str;
        var str = ret.data.str;
  
        $('#order_platform').val(order_platform);
        $('#time_str').val(time_str);
        $('#table_data').html(str);
   
        return true;
    }, function (data, ret) {
        Layer.alert(ret.msg);
        return false;
    });
}