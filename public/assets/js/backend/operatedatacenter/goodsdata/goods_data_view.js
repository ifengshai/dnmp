define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {

    var Controller = {
        index: function () {


            Controller.api.bindevent();

            var val = Config.label;
            if (val == 1) {
                $('.zeelool-div').show();
                $('.voogueme-div').hide();
                $('.nihao-div').hide();
            } else if (val == 2) {
                $('.zeelool-div').hide();
                $('.voogueme-div').show();
                $('.nihao-div').hide();
            } else if (val == 3) {
                $('.zeelool-div').hide();
                $('.voogueme-div').hide();
                $('.nihao-div').show();
            }

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
                    //订单数据概况折线图
                    var chartOptions = {
                        targetId: 'echart',
                        downLoadTitle: '图表',
                        type: 'line'
                    };

                    var options = {
                        type: 'post',
                        url: 'operatedatacenter/goodsdata/goods_data_view/goods_data_view_line',
                        data: {
                            order_platform: $("#order_platform").val(),
                            time_str: $("#time_str").val(),
                            type: $("#type").val()
                        }
                    }
                    EchartObj.api.ajax(options, chartOptions)
                }
            },
            bindevent: function () {
                Form.api.bindevent($("form[role=form]"));
            }
        }
    };
    return Controller;
});