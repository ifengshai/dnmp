define(['jquery', 'bootstrap', 'backend', 'addtabs', 'table', 'form', 'echartsobj', 'echarts', 'echarts-theme', 'template', 'custom-css'], function ($, undefined, Backend, Datatable, Table, Form, EchartObj, undefined, Template) {

    var Controller = {
        index: function () {
            Controller.api.formatter.daterangepicker($("div[role=form]"));
            //订单数据概况折线图
            //Controller.api.formatter.line_chart();
            Controller.api.formatter.line_histogram();
            Controller.api.formatter.order_send_overview();
            Controller.api.formatter.process_overview();
            Controller.api.formatter.comleted_time_rate_pie();
            $("#sku_submit").click(function () {
                order_data_view();
                //Controller.api.formatter.line_chart();
                Controller.api.formatter.line_histogram();
            });
            $("#sku_reset").click(function () {
                $("#order_platform").val(1);
                $("#time_str").val('');
                $("#compare_time_str").val('');
            });
            // $(document).on('change', '#order_platform', function () {
            //     order_data_view();
            //     Controller.api.formatter.line_chart();
            //     Controller.api.formatter.line_histogram();
            // });
        },
        add: function () {
            Controller.api.bindevent();
        },
        edit: function () {
            Controller.api.bindevent();
        },
        api: {
            formatter: {
                daterangepicker: function (form) {
                    //绑定日期时间元素事件
                    if ($(".datetimerange", form).size() > 0) {
                        require(['bootstrap-daterangepicker'], function () {
                            var ranges = {};
                            ranges[__('Today')] = [Moment().startOf('day'), Moment().endOf('day')];
                            ranges[__('Yesterday')] = [Moment().subtract(1, 'days').startOf('day'), Moment().subtract(1, 'days').endOf('day')];
                            ranges[__('Last 7 Days')] = [Moment().subtract(6, 'days').startOf('day'), Moment().endOf('day')];
                            ranges[__('Last 30 Days')] = [Moment().subtract(29, 'days').startOf('day'), Moment().endOf('day')];
                            ranges[__('This Month')] = [Moment().startOf('month'), Moment().endOf('month')];
                            ranges[__('Last Month')] = [Moment().subtract(1, 'month').startOf('month'), Moment().subtract(1, 'month').endOf('month')];
                            var options = {
                                timePicker: false,
                                autoUpdateInput: false,
                                timePickerSeconds: true,
                                timePicker24Hour: true,
                                autoApply: true,
                                locale: {
                                    format: 'YYYY-MM-DD HH:mm:ss',
                                    customRangeLabel: __("Custom Range"),
                                    applyLabel: __("Apply"),
                                    cancelLabel: __("Clear"),
                                },
                                ranges: ranges,
                                timePicker: true,
                                timePickerIncrement: 1
                            };
                            var origincallback = function (start, end) {
                                $(this.element).val(start.format(this.locale.format) + " - " + end.format(this.locale.format));
                                $(this.element).trigger('blur');
                            };
                            $(".datetimerange", form).each(function () {
                                var callback = typeof $(this).data('callback') == 'function' ? $(this).data('callback') : origincallback;
                                $(this).on('apply.daterangepicker', function (ev, picker) {
                                    callback.call(picker, picker.startDate, picker.endDate);
                                });
                                $(this).on('cancel.daterangepicker', function (ev, picker) {
                                    $(this).val('').trigger('blur');
                                });
                                $(this).daterangepicker($.extend({}, options, $(this).data()), callback);
                            });
                        });
                    }
                },
                line_histogram: function (){
                    //柱状图和折线图的结合
                    var chartOptions = {
                        targetId: 'echart1',
                        downLoadTitle: '图表',
                        type: 'bar',
                        bar: {
                            tooltip: { //提示框组件。
                                trigger: 'axis', // 触发类型。可选项item:数据项图形触发，主要在散点图，饼图等无类目轴的图表中使用。axis:坐标轴触发，主要在柱状图，折线图等会使用类目轴的图表中使用。
                                axisPointer: { //坐标轴指示器配置项。
                                    type: 'shadow' //指示器类型。可选项'line' 直线指示器。'shadow' 阴影指示器。'cross' 十字准星指示器。其实是种简写，表示启用两个正交的轴的 axisPointer。
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
                                data: ['采购数量', '采购金额']
                            },
                            xAxis: [
                                {
                                    type: 'category'
                                }
                            ],
                            yAxis: [
                                {
                                    type: 'value',
                                    name: '采购数量',
                                    axisLabel: {
                                        formatter: '{value} 个'
                                    }
                                },
                                {
                                    type: 'value',
                                    name: '采购金额',
                                    axisLabel: {
                                        formatter: '{value} ¥'
                                    }
                                }
                            ],
                        }
                    };
        
                    var options = {
                        type: 'post',
                        url: 'supplydatacenter/data_market/purchase_histogram_line',
                        data: {
                            time_str: $("#time_str").val(),
                        }
                    }
                    EchartObj.api.ajax(options, chartOptions)
                },
                order_send_overview: function () {
                    //订单数据概况折线图
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
                                data: ['超时订单', '未超时订单']
                            },
                            xAxis: [
                                {
                                    type: 'category'
                                }
                            ],
                            yAxis: [
                                {
                                    type: 'value'
                                },
                            ],
                        }
                    };

                    var options = {
                        type: 'post',
                        url: 'supplydatacenter/data_market/order_send_overview',
                        data: {
                            time_str: $("#time_str").val(),
                        }
                    }
                    EchartObj.api.ajax(options, chartOptions)
                },
                process_overview: function (){
                    //柱状图
                    var chartOptions = {
                        targetId: 'echart3',
                        downLoadTitle: '图表',
                        type: 'bar',
                        bar:{
                            grid: { //直角坐标系内绘图网格
                                top: '20%', //grid 组件离容器上侧的距离。
                                left: '5%', //grid 组件离容器左侧的距离。
                                right: '10%', //grid 组件离容器右侧的距离。
                                bottom: '10%', //grid 组件离容器下侧的距离。
                                containLabel: true //grid 区域是否包含坐标轴的刻度标签。
                            },                     
                            tooltip: { //提示框组件。
                                trigger: 'axis', // 触发类型。可选项item:数据项图形触发，主要在散点图，饼图等无类目轴的图表中使用。axis:坐标轴触发，主要在柱状图，折线图等会使用类目轴的图表中使用。
                                axisPointer: { //坐标轴指示器配置项。
                                    type: 'shadow' //指示器类型。可选项'line' 直线指示器。'shadow' 阴影指示器。'cross' 十字准星指示器。其实是种简写，表示启用两个正交的轴的 axisPointer。
                                },
                                formatter: function (param) { //格式化提示信息
                                    console.log(param);
                                    return param[0].name + '<br/>人数：' + param[0].value;
                                }
                            },                        
                            xAxis: {
                                type: 'value',
                                //boundaryGap: [0, 0.01]
                            },
                            yAxis: 
                            {
                                type: 'category',
                            },
                                              
                        }
                    };  
                    var options = {
                        type: 'post',
                        url: 'supplydatacenter/data_market/process_overview',
                        data: {
                            time_str: $("#time_str").val()
                        }
                    }                
                    EchartObj.api.ajax(options, chartOptions)
                },
                comleted_time_rate_pie: function () {
                    //妥投时效占比
                    var chartOptions = {
                        targetId: 'echart4',
                        downLoadTitle: '图表',
                        type: 'pie',
                        pie: {

                            tooltip: { //提示框组件。
                                trigger: 'item',
                                formatter: function (param) {
                                    return param.data.name + '<br/>数量：' + param.data.value + '<br/> 占比：' + param.percent.toFixed(2) + '%';
                                }
                            },
                        }
                    };

                    var options = {
                        type: 'post',
                        url: 'supplydatacenter/data_market/comleted_time_rate',
                        data: {
                            'time_str' :  $("#time_str").val(),
                        }

                    };
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
function stock_measure_overview_platform() {
    var order_platform = $('#order_platform').val();
    var time_str = $('#time_str').val();
    Backend.api.ajax({
        url: 'supplydatacenter/data_market/stock_measure_overview_platform',
        data: { order_platform: order_platform, time_str: time_str}
    }, function (data, ret) {
        var order_num = ret.data.order_num;
        var order_unit_price = ret.data.order_unit_price;
        var sales_total_money = ret.data.sales_total_money;
        var shipping_total_money = ret.data.shipping_total_money;
        var replacement_order_num = ret.data.replacement_order_num;
        var replacement_order_total = ret.data.replacement_order_total;
        var online_celebrity_order_num = ret.data.online_celebrity_order_num;
        var online_celebrity_order_total = ret.data.online_celebrity_order_total;
        if(compare_time_str.length > 0){
            $('.rate_class').show();
        }
        if(compare_time_str.length <= 0){
            $('.rate_class').hide();
        }
        $('#huan_order_num').html(huan_rate1+order_num.contrast_order_num);
        $('#order_unit_price').html(order_unit_price.order_unit_price);
        if(order_unit_price.same_order_unit_price >= 0){
            var same_rate2 = '<img src="/shangsheng.png">';
        }else{
            var same_rate2 = '<img src="/xiadie.png">';
        }
        $('#same_order_unit_price').html(same_rate2+order_unit_price.same_order_unit_price);
        
        $('#replacement_order_num').html(replacement_order_num.replacement_order_num);
        $('#replacement_order_total').html(replacement_order_total.replacement_order_total);
        $('#online_celebrity_order_num').html(online_celebrity_order_num.online_celebrity_order_num);
        $('#online_celebrity_order_total').html(online_celebrity_order_total.online_celebrity_order_total);
       
        //国家地域分布
        $("#country_info").html(ret.data.country_str);
        return false;
    }, function (data, ret) {
        Layer.alert(ret.msg);
        return false;
    });
}