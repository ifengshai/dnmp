define(['jquery', 'bootstrap', 'backend', 'table', 'form', 'echartsobj'], function ($, undefined, Backend, Table, Form, EchartObj) {

    var Controller = {
        index: function () {


            Controller.api.bindevent();

            var val = Config.label;
            if (val == 1) {
                $('.zeelool-div').show();
                $('.voogueme-div').hide();
                $('.nihao-div').hide();
                $('#c-order_platform').hide();

            } else if (val == 2) {
                $('.zeelool-div').hide();
                $('.voogueme-div').show();
                $('.nihao-div').hide();
            } else if (val == 3) {
                $('.zeelool-div').hide();
                $('.voogueme-div').hide();
                $('.nihao-div').show();
            }
            order_data_view();
            $("#sku_submit").click(function(){
                order_data_view();
                Controller.api.formatter.line_chart();
                Controller.api.formatter.goods_type_chart();
                Form.api.bindevent($("form[role=form]"));

                table.bootstrapTable('refresh',params);

            });

            Controller.api.formatter.line_chart();
            Controller.api.formatter.goods_type_chart();
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
                        targetId: 'echart',
                        downLoadTitle: '图表',
                        type: 'bar',
                        bar: {
                            tooltip: { //提示框组件。
                                trigger: 'axis', // 触发类型。可选项item:数据项图形触发，主要在散点图，饼图等无类目轴的图表中使用。axis:坐标轴触发，主要在柱状图，折线图等会使用类目轴的图表中使用。
                                axisPointer: { //坐标轴指示器配置项。
                                    type: 'line' //指示器类型。可选项'line' 直线指示器。'shadow' 阴影指示器。'cross' 十字准星指示器。其实是种简写，表示启用两个正交的轴的 axisPointer。
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
                                data: ['镜框销量', '副单价']
                            },
                            xAxis: [
                                {
                                    type: 'category'
                                }
                            ],
                            yAxis: [
                                {
                                    type: 'value',
                                    name: '镜框销量',
                                    axisLabel: {
                                        formatter: '{value} 个'
                                    }
                                },
                                {
                                    type: 'value',
                                    name: '副单价',
                                    axisLabel: {
                                        formatter: '{value} ¥'
                                    }
                                }
                            ],
                        }
                    };

                    var options = {
                        type: 'post',
                        url: 'operatedatacenter/goodsdata/goods_data_view/goods_sales_data_line',
                        data: {

                        }

                    }
                    EchartObj.api.ajax(options, chartOptions)
                },
                goods_type_chart: function () {
                    var chartOptions = {
                        targetId: 'echart2',
                        downLoadTitle: '图表',
                        type: 'line'
                    };
                    var options = {
                        type: 'post',
                        url: 'operatedatacenter/goodsdata/goods_data_view/goods_type_data_line',
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
function order_data_view(){
    var order_platform =Config.label;
    var time_str = $('#create_time').val();
    Backend.api.ajax({
        url: 'operatedatacenter/goodsdata/goods_data_view/ajax_top_data',
        data: { order_platform: order_platform, time_str: time_str}
    }, function (data, ret) {

        var sun_glass_num = ret.data.sun_glass_num;
        $('#sun_glass_num').text(sun_glass_num);

        var glass_num = ret.data.glass_num;
        $('#glass_num').text(glass_num);

        var run_glass_num = ret.data.run_glass_num;
        $('#run_glass_num').text(run_glass_num);

        var old_glass_num = ret.data.old_glass_num;
        $('#old_glass_num').text(old_glass_num);

        var son_glass_num = ret.data.son_glass_num;
        $('#son_glass_num').text(son_glass_num);

        var other_num = ret.data.other_num;
        $('#other_num').text(other_num);

        var total_num = ret.data.total_num;
        $('#total_num').text(total_num);

        var v_sun_glass_num = ret.data.sun_glass_num;
        $('#v_sun_glass_num').text(v_sun_glass_num);
        var v_glass_num = ret.data.glass_num;
        $('#v_glass_num').text(v_glass_num);
        var v_other_num = ret.data.other_num;
        $('#v_other_num').text(v_other_num);

        var n_sun_glass_num = ret.data.sun_glass_num;
        $('#n_sun_glass_num').text(n_sun_glass_num);
        var n_glass_num = ret.data.glass_num;
        $('#n_glass_num').text(n_glass_num);

        return false;
    }, function (data, ret) {
        Layer.alert(ret.msg);
        return false;
    });
}