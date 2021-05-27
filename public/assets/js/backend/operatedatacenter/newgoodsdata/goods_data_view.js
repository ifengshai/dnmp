define(['jquery', 'bootstrap', 'backend', 'table', 'form', 'echartsobj'], function ($, undefined, Backend, Table, Form, EchartObj) {

    var Controller = {
        index: function () {

            Controller.api.bindevent();
            order_data_view();   //商品销售概况
            Controller.api.formatter.line_chart();   //镜框销量、副单价趋势
            goods_grade();     //商品等级分布
            glass_box_data();  //关键指标
            mid_data();   //其他关键指标
            
          
            $("#sku_submit").click(function(){
                var order_platform =$('#order_platform').val();
                if (order_platform == 1 || order_platform == 5) {
                    $('.zeelool-div').show();
                    $('.voogueme-div').hide();
                    $('.nihao-div').hide();

                    $('#run_glass').show();
                    $('#old_glass').show();
                    $('#son_glass').show();
                } else if (order_platform == 2 || order_platform == 10 || order_platform == 11) {
                    $('.zeelool-div').hide();
                    $('.voogueme-div').show();
                    $('.nihao-div').hide();

                    $('#run_glass').hide();
                    $('#old_glass').hide();
                    $('#son_glass').hide();
                    // $('#c-order_platform').show();
                } else if (order_platform == 3) {
                    $('.zeelool-div').hide();
                    $('.voogueme-div').hide();
                    $('.nihao-div').show();
                }
                site_goods_type();
                order_data_view();   //商品销售概况
                Controller.api.formatter.line_chart();   //镜框销量、副单价趋势
                goods_grade();     //商品等级分布
                glass_box_data();  //关键指标
                mid_data();   //其他关键指标
            
                Form.api.bindevent($("form[role=form]"));

                // table.bootstrapTable('refresh',params);

            });
            $(document).on('change', '#goods_type', function () {
                goods_grade();     //商品等级分布
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
                        url: 'operatedatacenter/newgoodsdata/goods_data_view/goods_sales_data_line',
                        data: { order_platform: $('#order_platform').val(), time_str: $('#create_time').val()}

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
function site_goods_type(){
    var order_platform =$('#order_platform').val();
    Backend.api.ajax({
        url: 'operatedatacenter/newgoodsdata/goods_data_view/site_goods_type',
        data: { order_platform: order_platform}
    }, function (data, ret) {
        var data = ret.data;
        $('#goods_type').html(data);
        return false;
    }, function (data, ret) {
        Layer.alert(ret.msg);
        return false;
    });
}
function order_data_view(){
    var order_platform =Config.label;
    var time_str = $('#create_time').val();
    var order_platform =$('#order_platform').val();
    Backend.api.ajax({
        url: 'operatedatacenter/newgoodsdata/goods_data_view/ajax_top_data',
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
function goods_grade(){
    var time_str = $('#create_time').val();
    var goods_type = $('#goods_type').val();
    var order_platform =$('#order_platform').val();
    Backend.api.ajax({
        url: 'operatedatacenter/newgoodsdata/goods_data_view/ajax_dowm_data',
        data: { order_platform: order_platform, time_str: time_str,goods_type:goods_type}
    }, function (data, ret) {
        var data = ret.data;
        $('#grade_table').html(data);
        return false;
    }, function (data, ret) {
        Layer.alert(ret.msg);
        return false;
    });
}
function glass_box_data(){
    var time_str = $('#create_time').val();
    var order_platform =$('#order_platform').val();
    Backend.api.ajax({
        url: "operatedatacenter/newgoodsdata/goods_data_view/glass_box_data",
        data: {
            'time': time_str,
            'platform': order_platform
        }
    }, function (data, ret) {
        var data = ret.data;
        $('#frame_money').text(data.frame_money);
        $('#frame_sales_num').text(data.frame_sales_num);
        $('#frame_avg_money').text(data.frame_avg_money);
        $('#frame_onsales_num').text(data.frame_onsales_num);
        $('#decoration_money').text(data.decoration_money);
        $('#decoration_sales_num').text(data.decoration_sales_num);
        $('#decoration_avg_money').text(data.decoration_avg_money);
        $('#decoration_onsales_num').text(data.decoration_onsales_num);
        $('#frame_in_print_num').text(data.frame_in_print_num);
        $('#frame_in_print_rate').text(data.frame_in_print_rate);
        $('#decoration_in_print_num').text(data.decoration_in_print_num);
        $('#decoration_in_print_rate').text(data.decoration_in_print_rate);
        $('#frame_new_money').text(data.frame_new_money);
        $('#decoration_new_money').text(data.decoration_new_money);
        $('#frame_order_customer').text(data.frame_order_customer);
        $('#frame_avg_customer').text(data.frame_avg_customer);
        $('#decoration_order_customer').text(data.decoration_order_customer);
        $('#decoration_avg_customer').text(data.decoration_avg_customer);
        $('#frame_new_num').text(data.frame_new_num);
        $('#decoration_new_num').text(data.decoration_new_num);
        $('#frame_new_in_print_num').text(data.frame_new_in_print_num);
        $('#frame_new_in_print_rate').text(data.frame_new_in_print_rate);
        $('#decoration_new_in_print_num').text(data.decoration_new_in_print_num);
        $('#decoration_new_in_print_rate').text(data.decoration_new_in_print_rate);

    }, function (data, ret) {
        alert(ret.msg);
        return false;
    });
}
function mid_data(){
    var time_str = $('#create_time').val();
    var order_platform =$('#order_platform').val();
    Backend.api.ajax({
        url: "operatedatacenter/newgoodsdata/goods_data_view/mid_data",
        data: {
            'time_str': time_str,
            'order_platform': order_platform
        }
    }, function (data, ret) {
        $('#sun_sales_num').text(ret.data.sun_glass.frame_money);
        $('#sun_run_sales_num').text(ret.data.sun_glass.frame_in_print_num);
        $('#sun_run_sales_rate').text(ret.data.sun_glass.frame_in_print_rate);
        $('#new_sun_run_sales_num').text(ret.data.sun_glass.frame_new_money);
        $('#avg_sun_run_sales_num').text(ret.data.sun_glass.frame_avg_money);
        $('#user_avg_sun_run_sales_num').text(ret.data.sun_glass.frame_avg_customer);
        $('#sun_zhengchang_shoumai').text(ret.data.sun_glass.frame_onsales_num);
        $('#sun_xinpin_shuliang').text(ret.data.sun_glass.frame_new_num);
        $('#sun_xinpin_dongxiao_shu').text(ret.data.sun_glass.frame_new_in_print_num);
        $('#sun_xinpin_dongxiao_lv').text(ret.data.sun_glass.frame_new_in_print_rate);

        $('#glass_sales_num').text(ret.data.glass.frame_money);
        $('#glass_run_sales_num').text(ret.data.glass.frame_in_print_num);
        $('#glass_run_sales_rate').text(ret.data.glass.frame_in_print_rate);
        $('#new_glass_run_sales_num').text(ret.data.glass.frame_new_money);
        $('#avg_glass_run_sales_num').text(ret.data.glass.frame_avg_money);
        $('#user_avg_glass_run_sales_num').text(ret.data.glass.frame_avg_customer);
        $('#glass_zhengchang_shoumai').text(ret.data.glass.frame_onsales_num);
        $('#glass_xinpin_shuliang').text(ret.data.glass.frame_new_num);
        $('#glass_xinpin_dongxiao_shu').text(ret.data.glass.frame_new_in_print_num);
        $('#glass_xinpin_dongxiao_lv').text(ret.data.glass.frame_new_in_print_rate);

        $('#run_sales_num').text(ret.data.run_glass.frame_money);
        $('#run_run_sales_num').text(ret.data.run_glass.frame_in_print_num);
        $('#run_run_sales_rate').text(ret.data.run_glass.frame_in_print_rate);
        $('#new_run_run_sales_num').text(ret.data.run_glass.frame_new_money);
        $('#avg_run_run_sales_num').text(ret.data.run_glass.frame_avg_money);
        $('#user_avg_run_run_sales_num').text(ret.data.run_glass.frame_avg_customer);
        $('#run_zhengchang_shoumai').text(ret.data.run_glass.frame_onsales_num);
        $('#run_xinpin_shuliang').text(ret.data.run_glass.frame_new_num);
        $('#run_xinpin_dongxiao_shu').text(ret.data.run_glass.frame_new_in_print_num);
        $('#run_xinpin_dongxiao_lv').text(ret.data.run_glass.frame_new_in_print_rate);

        $('#old_sales_num').text(ret.data.old_glass.frame_money);
        $('#old_run_sales_num').text(ret.data.old_glass.frame_in_print_num);
        $('#old_run_sales_rate').text(ret.data.old_glass.frame_in_print_rate);
        $('#new_old_run_sales_num').text(ret.data.old_glass.frame_new_money);
        $('#avg_old_run_sales_num').text(ret.data.old_glass.frame_avg_money);
        $('#user_avg_old_run_sales_num').text(ret.data.old_glass.frame_avg_customer);
        $('#old_zhengchang_shoumai').text(ret.data.old_glass.frame_onsales_num);
        $('#old_xinpin_shuliang').text(ret.data.old_glass.frame_new_num);
        $('#old_xinpin_dongxiao_shu').text(ret.data.old_glass.frame_new_in_print_num);
        $('#old_xinpin_dongxiao_lv').text(ret.data.old_glass.frame_new_in_print_rate);

        $('#child_sales_num').text(ret.data.son_glass.frame_money);
        $('#child_run_sales_num').text(ret.data.son_glass.frame_in_print_num);
        $('#child_run_sales_rate').text(ret.data.son_glass.frame_in_print_rate);
        $('#new_child_run_sales_num').text(ret.data.son_glass.frame_new_money);
        $('#avg_child_run_sales_num').text(ret.data.son_glass.frame_avg_money);
        $('#user_avg_child_run_sales_num').text(ret.data.son_glass.frame_avg_customer);
        $('#child_zhengchang_shoumai').text(ret.data.son_glass.frame_onsales_num);
        $('#gchild_xinpin_shuliang').text(ret.data.son_glass.frame_new_num);
        $('#child_xinpin_dongxiao_shu').text(ret.data.son_glass.frame_new_in_print_num);
        $('#child_xinpin_dongxiao_lv').text(ret.data.son_glass.frame_new_in_print_rate);

    }, function (data, ret) {
        alert(ret.msg);
        return false;
    });
}