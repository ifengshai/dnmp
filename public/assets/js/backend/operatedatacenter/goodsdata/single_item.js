define(['jquery', 'bootstrap', 'backend', 'table', 'form', 'echartsobj'], function ($, undefined, Backend, Table, Form, EchartObj) {

    var Controller = {
        index: function () {
            Controller.api.bindevent();
            // 初始化表格参数配置
            Table.api.init({
                extend: {
                    // index_url: 'operatedatacenter/goodsdata/single_item/index'+ location.search,
                    index_url: 'operatedatacenter/goodsdata/single_item/index',
                    add_url: 'operatedatacenter/goodsdata/single_item/add',
                    edit_url: 'operatedatacenter/goodsdata/single_item/edit',
                    del_url: 'operatedatacenter/goodsdata/single_item/del',
                    multi_url: 'operatedatacenter/goodsdata/single_item/multi',
                    table: 'single_item',
                }
            });

            var table = $("#table");

            // 初始化表格
            table.bootstrapTable({
                url: $.fn.bootstrapTable.defaults.extend.index_url,
                pk: 'id',
                sortName: 'id',
                columns: [
                    [
                        // { checkbox: true },
                        { field: 'increment_id', title: __('订单编号') },
                        { field: 'status', title: __('订单状态') },
                        { field: 'base_grand_total', title: __('金额') },
                        { field: 'base_discount_amount', title: __('优惠金额') },
                        { field: 'created_at', title: __('订单日期') },
                        { field: 'payer_email', title: __('支付邮箱') }
                    ]
                ]
            });
            // 为表格绑定事件
            Table.api.bindevent(table);
            $("#sku_submit").click(function(){
                var sku = $("#sku").val();
                var time_str = $("#time_str").val();
                if(sku.length <= 0){
                    Layer.alert('请填写平台sku');
                    return false;
                }
                if(time_str.length <= 0){
                    Layer.alert('请选择时间');
                    return false;
                }
                $("#dis1").css('display','block');
                $("#dis3").css('display','block');
                $("#dis2").css('display','block');
                $("#dis4").css('display','block');
                Controller.api.formatter.line_chart();
                Controller.api.formatter.sku_sales_data_bar();
                order_data_view();
                // var params = table.bootstrapTable('getOptions')
                // params.queryParams = function(params) {
                //
                //     //定义参数
                //     var filter = {};
                //     //遍历form 组装json
                //     $.each($("#form").serializeArray(), function(i, field) {
                //         filter[field.name] = field.value;
                //     });
                //
                //     //参数转为json字符串
                //     params.filter = JSON.stringify(filter)
                //     console.info(params);
                //     return params;
                // }

                // table.bootstrapTable('refresh',params);
            });
            // Controller.api.formatter.line_chart();
            // Controller.api.formatter.sku_sales_data_bar();
            // $(document).on('change', '#btnsubmit', function () {
            //     alert(111);
            //     order_data_view();
            //     Controller.api.formatter.line_chart();
            //     Controller.api.formatter.sku_sales_data_bar();
            // });
            // var params = table.bootstrapTable('getOptions')
            // params.queryParams = function(params) {
            //
            //     //定义参数
            //     var filter = {};
            //     //遍历form 组装json
            //     $.each($("#form").serializeArray(), function(i, field) {
            //         filter[field.name] = field.value;
            //     });
            //
            //     //参数转为json字符串
            //     params.filter = JSON.stringify(filter)
            //     console.info(params);
            //     return params;
            // }

            table.bootstrapTable('refresh',params);

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
                                data: ['商品销量', '现价']
                            },
                            xAxis: [
                                {
                                    type: 'category'
                                }
                            ],
                            yAxis: [
                                {
                                    type: 'value',
                                    name: '商品销量',
                                    axisLabel: {
                                        formatter: '{value} 个'
                                    }
                                },
                                {
                                    type: 'value',
                                    name: '现价',
                                    axisLabel: {
                                        formatter: '{value} ¥'
                                    }
                                }
                            ],
                        }
                    };

                    var options = {
                        type: 'post',
                        url: 'operatedatacenter/goodsdata/single_item/sku_sales_data_line',
                        data: {
                            sku:$('#sku').val(),
                            order_platform:$('#order_platform').val(),
                            time_str:$('#time_str').val()
                        }
                    }
                    EchartObj.api.ajax(options, chartOptions)
                },
                sku_sales_data_bar: function () {

                    //销售排行榜图表
                    var chartOptions = {
                        targetId: 'echart2',
                        downLoadTitle: '图表',
                        type: 'bar',
                        bar: {
                            tooltip: { //提示框组件。
                                trigger: 'axis', // 触发类型。可选项item:数据项图形触发，主要在散点图，饼图等无类目轴的图表中使用。axis:坐标轴触发，主要在柱状图，折线图等会使用类目轴的图表中使用。
                                axisPointer: { //坐标轴指示器配置项。
                                    type: 'shadow' //指示器类型。可选项'line' 直线指示器。'shadow' 阴影指示器。'cross' 十字准星指示器。其实是种简写，表示启用两个正交的轴的 axisPointer。
                                },
                                formatter: function (param) { //格式化提示信息
                                    return param[0].seriesName + '<br/>' + param[0].name + '：' + param[0].value;
                                }
                            },
                            xAxis: {
                                type: 'category',
                                data:[]
                            },
                            yAxis: {
                                type: 'value'
                            }
                        }
                    };
              
                    var options = {
                        type: 'post',
                        url: 'operatedatacenter/goodsdata/single_item/sku_sales_data_bar',
                        data: {
                            sku:$('#sku').val(),
                            order_platform:$('#order_platform').val(),
                            time_str:$('#time_str').val()
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
    var order_platform = $('#order_platform').val();
    var sku = $('#sku').val();
    var time_str = $('#time_str').val();
    Backend.api.ajax({
        url: 'operatedatacenter/goodsdata/single_item/ajax_top_data',
        data: { order_platform: order_platform, time_str: time_str,sku:sku}
    }, function (data, ret) {

        var total = ret.data.total;
        $('#total').text(total);
        var whole_platform_order_num = ret.data.whole_platform_order_num;
        $('#whole_platform_order_num').text(whole_platform_order_num);
        var order_rate = ret.data.order_rate;
        $('#order_rate').text(order_rate);
        var avg_order_glass = ret.data.avg_order_glass;
        $('#avg_order_glass').text(avg_order_glass);
        var pay_jingpian_glass = ret.data.pay_jingpian_glass;
        $('#pay_jingpian_glass').text(pay_jingpian_glass);
        var pay_jingpian_glass_rate = ret.data.pay_jingpian_glass_rate;
        $('#pay_jingpian_glass_rate').text(pay_jingpian_glass_rate);
        var only_one_glass_num = ret.data.only_one_glass_num;
        $('#only_one_glass_num').text(only_one_glass_num);
        var only_one_glass_rate = ret.data.only_one_glass_rate;
        $('#only_one_glass_rate').text(only_one_glass_rate);
        var every_price = ret.data.every_price;
        $('#every_price').text(every_price);
        var whole_price = ret.data.whole_price;
        $('#whole_price').text(whole_price);

        return false;
    }, function (data, ret) {
        Layer.alert(ret.msg);
        return false;
    });
}