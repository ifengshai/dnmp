define(['jquery', 'bootstrap', 'backend', 'table', 'form', 'echartsobj'], function ($, undefined, Backend, Table, Form, EchartObj) {

    var Controller = {
        index: function () {
            Controller.api.bindevent();
            Controller.api.formatter.sales_num_line();
            Controller.api.formatter.sales_money_line();
            Controller.api.formatter.order_num_line();
            Controller.api.formatter.unit_price_line();

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

                        }

                    }
                    EchartObj.api.ajax(options, chartOptions)
                },
                sales_money_line: function () {
                    var chartOptions = {
                        targetId: 'echart2',
                        downLoadTitle: '图表',
                        type: 'line'
                    };
                    var options = {
                        type: 'post',
                        url: 'operatedatacenter/dataview/time_data/sales_money_line',
                        data: {

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