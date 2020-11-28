define(['jquery', 'bootstrap', 'backend', 'addtabs', 'table', 'form', 'echartsobj', 'echarts', 'echarts-theme', 'template', 'custom-css'], function ($, undefined, Backend, Datatable, Table, Form, EchartObj, undefined, Template) {

    var Controller = {
        index: function () {
            Controller.api.formatter.daterangepicker($("div[role=form]"));
            //订单数据概况折线图
            Controller.api.formatter.user_chart(); 
            Controller.api.formatter.new_update_change_line();
            Controller.api.formatter.user_type_pie();
            Controller.api.formatter.user_order_pie();
            order_data_view();
            $("#sku_submit").click(function () {
                Controller.api.formatter.user_chart();
                Controller.api.formatter.new_update_change_line();
                Controller.api.formatter.user_type_pie();
                Controller.api.formatter.user_order_pie();
                order_data_view();
            });
            $("#sku_reset").click(function () {
                $("#order_platform").val(1);
                $("#time_str").val('');
                $("#time_str2").val('');
                Controller.api.formatter.user_chart();
                Controller.api.formatter.new_update_change_line();
                Controller.api.formatter.user_type_pie();
                Controller.api.formatter.user_order_pie();
                order_data_view();
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
                user_chart: function () {
                    //活跃用户数折线图
                    var chartOptions = {
                        targetId: 'echart1',
                        downLoadTitle: '图表',
                        type: 'line'
                    };

                    var options = {
                        type: 'post',
                        url: 'operatedatacenter/userdata/user_data_view/active_user_trend',
                        data: {
                            order_platform: $("#order_platform").val(),
                            time_str: $("#time_str").val(),
                            type: $("#type").val()
                        }
                    }
                    EchartObj.api.ajax(options, chartOptions)
                },
                new_update_change_line: function () {
                    var chartOptions1 = {
                        targetId: 'echart2',
                        downLoadTitle: '图表',
                        type: 'bar',
                        bar: {
                            tooltip: { //提示框组件。
                                trigger: 'axis', // 触发类型。可选项item:数据项图形触发，主要在散点图，饼图等无类目轴的图表中使用。axis:坐标轴触发，主要在柱状图，折线图等会使用类目轴的图表中使用。
                                axisPointer: { //坐标轴指示器配置项。
                                    type: 'line' //指示器类型。可选项'line' 直线指示器。'shadow' 阴影指示器。'cross' 十字准星指示器。其实是种简写，表示启用两个正交的轴的 axisPointer。
                                },
                                formatter: function (param) { //格式化提示信息
                                    if(param.length == 2){
                                        return param[0].name + '<br/>' + param[0].seriesName + '：' + param[0].value + '%<br/>' + param[1].seriesName + '：' + param[1].value+'%';
                                    }else{
                                        return param[0].name + '<br/>' + param[0].seriesName + '：' + param[0].value+'%';
                                    }
                                    
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
                                data: ['新用户', '活跃用户']
                            },
                            xAxis: 
                            {
                                type: 'category',
                                boundaryGap:false
                            },
                            yAxis: 
                            {
                                type: 'value',
                                name: '转化趋势，总体的转化率',
                                axisLabel: {
                                    formatter: '{value} %'
                                }
                            },
                        }
                    };
                    
                    var options1 = {
                        type: 'post',
                        url: 'operatedatacenter/userdata/user_data_view/new_old_change_line',
                        data: {
                            'order_platform': $("#order_platform").val(),
                            'time_str': $("#time_str").val(),
                        }
                    }
                    EchartObj.api.ajax(options1, chartOptions1)
                },
                user_type_pie: function () {
                    //库存分布
                    var chartOptions = {
                        targetId: 'echart3',
                        downLoadTitle: '图表',
                        type: 'pie',
                        pie: {
                            tooltip: { //提示框组件。
                                trigger: 'item',
                                formatter: function (param) {
                                    console.log(param)
                                    return param.data.name + '<br/>数量：' + param.data.value + '<br/> 占比：' + param.percent.toFixed(2) + '%';
                                }
                            },
                            title:{
                                subtext:'用户数',
                                left:"center",
                                top:"40%",
                                subtextStyle:{
                                    textAlign:"center",
                                    fill:"#333",
                                    fontSize:16,
                                    fontWeight:700
                                },
                                textStyle:{
                                    color:"#27D9C8",
                                    fontSize:32,
                                    align:"center"
                                }
                            },
                            series:[{
                                radius: ['50%', '70%'],
                            }]
                        }
                    };

                    var options3 = {
                        type: 'post',
                        url: 'operatedatacenter/userdata/user_data_view/user_type_pie',
                        data: {
                            'time_str' :  $("#time_str").val(),
                            'order_platform' :  $("#order_platform").val(),
                        }

                    };
                    EchartObj.api.ajax(options3, chartOptions)
                },
                user_order_pie: function () {
                    //库存分布
                    var chartOptions = {
                        targetId: 'echart4',
                        downLoadTitle: '图表',
                        type: 'pie',
                        pie: {
                            tooltip: { //提示框组件。
                                trigger: 'item',
                                formatter: function (param) {
                                    console.log(param)
                                    return param.data.name + '<br/>数量：' + param.data.value + '<br/> 占比：' + param.percent.toFixed(2) + '%';
                                }
                            },
                            title:{
                                subtext:'总销售额',
                                left:"center",
                                top:"40%",
                                subtextStyle:{
                                    textAlign:"center",
                                    fill:"#333",
                                    fontSize:16,
                                    fontWeight:700
                                },
                                textStyle:{
                                    color:"#27D9C8",
                                    fontSize:36,
                                    align:"center"
                                }
                            },
                            series:[{
                                radius: ['50%', '70%'],
                            }]
                        }
                    };

                    var options4 = {
                        type: 'post',
                        url: 'operatedatacenter/userdata/user_data_view/user_order_pie',
                        data: {
                            'time_str' :  $("#time_str").val(),
                            'order_platform' :  $("#order_platform").val(),
                        }

                    };
                    EchartObj.api.ajax(options4, chartOptions)
                },
            },
            bindevent: function () {
                Form.api.bindevent($("form[role=form]"));
            }
        }
    };
    return Controller;
});

function order_data_view() {
    var order_platform = $('#order_platform').val();
    var time_str = $('#time_str').val();
    var time_str2 = $('#time_str2').val();
    Backend.api.ajax({
        url: 'operatedatacenter/userdata/user_data_view/ajax_top_data',
        data: {order_platform: order_platform, time_str: time_str, time_str2: time_str2}
    }, function (data, ret) {
        var active_user_num = ret.data.active_user_num;
        var register_user_num = ret.data.register_user_num;
        var again_user_num = ret.data.again_user_num;
        $("#active_user_num").html(active_user_num.active_user_num);
        $("#register_user_num").html(register_user_num.register_user_num);
        $("#again_user_num").html(again_user_num.again_user_num);
        var str1 = '';
        if(active_user_num.contrast_active_user_num){
            str1 += '<div class="rate_class"><span>';
            if(active_user_num.contrast_active_user_num < 0){
                str1 += '<img src="/xiadie.png">';
            }else{
                str1 += '<img style="transform:rotate(180deg);"  src="/shangzhang.png">';
            }
            str1 += active_user_num.contrast_active_user_num+'%</span></div>';   
            $("#contrast_active_user_num").html(str1);               
        }
        var str2 = '';
        if(register_user_num.contrast_register_user_num){
            str2 += '<div class="rate_class"><span>';
            if(register_user_num.contrast_register_user_num < 0){
                str2 += '<img src="/xiadie.png">';
            }else{
                str2 += '<img style="transform:rotate(180deg);"  src="/shangzhang.png">';
            }              
            str2 += register_user_num.contrast_register_user_num+'%</span></div>'; 
            $("#contrast_register_user_num").html(str2);           
        }
        var str3 = '';
        if(again_user_num.contrast_again_user_num){
            str3 += '<div class="rate_class"><span>';
            if(again_user_num.contrast_again_user_num < 0){
                str3 += '<img src="/xiadie.png">';
            }else{
                str3 += '<img style="transform:rotate(180deg);"  src="/shangzhang.png">';
            }              
            str3 += again_user_num.contrast_again_user_num+'%</span></div>'; 
            $("#contrast_again_user_num").html(str3);           
        }

        return false;
    }, function (data, ret) {
        Layer.alert(ret.msg);
        return false;
    });
}