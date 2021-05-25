define(['jquery', 'bootstrap', 'backend', 'addtabs', 'table', 'form', 'echartsobj', 'echarts', 'echarts-theme', 'template', 'custom-css'], function ($, undefined, Backend, Datatable, Table, Form, EchartObj, undefined, Template) {

    var Controller = {
        index: function () {
            Controller.api.formatter.daterangepicker($("span[role=form]"));
            Controller.api.formatter.line_histogram();
            $("#time_str").on("apply.daterangepicker", function () {
                setTimeout(() => {
                    Controller.api.formatter.line_histogram();
                }, 0)
            })
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
                    title:{
                        subtext:'个数',
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

            var options = {
                type: 'post',
                url: 'operatedatacenter/newgoodsdata/good_status/glass_same_data',
                data: {
                    platform_a: $("#platform_a").val(),
                    platform_a_name: $("#platform_a option:selected").text(),
                    platform_b: 2,
                    platform_b_name: 'voogueme',
                }
            };
            EchartObj.api.ajax(options, chartOptions);
            $("#platform_b").val(2)
            Backend.api.ajax({
                url: 'operatedatacenter/newgoodsdata/good_status/again_glass_same_data',
                data: {
                    platform_a: 1,
                    platform_b: 2,
                }
            }, function (data, ret) {
                var again_num = ret.data.again_num;
                var again_rate = ret.data.again_rate;
                $('#again_num').text(again_num);
                $('#again_rate').text(again_rate);
                return false;
            }, function (data, ret) {
                Layer.alert(ret.msg);
                return false;
            });
            $("#platform_submit").click(function () {
                Controller.api.formatter.user_data_pie();
                Backend.api.ajax({
                    url: 'operatedatacenter/newgoodsdata/good_status/again_glass_same_data',
                    data: {
                        platform_a: $("#platform_a").val(),
                        platform_b: $("#platform_b").val(),
                    }
                }, function (data, ret) {
                    var again_num = ret.data.again_num;
                    var again_rate = ret.data.again_rate;
                    $('#again_num').text(again_num);
                    $('#again_rate').text(again_rate);
                    return false;
                }, function (data, ret) {
                    Layer.alert(ret.msg);
                    return false;
                });
            });
            $(document).on('change', '#order_platform', function () {
                Controller.api.formatter.line_histogram();
            });
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
                                timePicker : true,
                                timePickerIncrement : 1
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
                line_histogram: function () {
                    var chartOptions = {
                        targetId: 'echart3',
                        downLoadTitle: '图表',
                        type: 'bar',
                        bar: {
                            color: ['#003366', '#006699', '#4cabce'],
                            // tooltip: {
                            //     trigger: 'axis',
                            //     axisPointer: {
                            //         type: 'shadow'
                            //     },
                            //     formatter: function (param) {
                            //         return param.data.name + '<br/>库存：' + param.data.value + '<br/> 占比：' + param.percent.toFixed(2) + '%';
                            //     }
                            // },
                            tooltip: { //提示框组件。
                                trigger: 'axis', // 触发类型。可选项item:数据项图形触发，主要在散点图，饼图等无类目轴的图表中使用。axis:坐标轴触发，主要在柱状图，折线图等会使用类目轴的图表中使用。
                                axisPointer: { //坐标轴指示器配置项。
                                    type: 'shadow' //指示器类型。可选项'line' 直线指示器。'shadow' 阴影指示器。'cross' 十字准星指示器。其实是种简写，表示启用两个正交的轴的 axisPointer。
                                },
                                formatter: function (param) { //格式化提示信息
                                    if(param.length == 3){
                                        return param[0].name + '<br/>' + param[0].seriesName + '：' + param[0].value + '<br/>' + param[1].seriesName + '：' + param[1].value + '<br/>' + param[2].seriesName + '：' + param[2].value;
                                    }else if(param.length == 2){
                                        return param[0].name + '<br/>' + param[0].seriesName + '：' + param[0].value + '<br/>' + param[1].seriesName + '：' + param[1].value;
                                    }else{
                                        return param[0].name + '<br/>' + param[0].seriesName + '：' + param[0].value;
                                    }
                                }
                            },
                            legend: {
                                top: '2%',
                                data: ['在售', '售罄', '下架']
                            },
                            xAxis: {
                                type: 'category'
                            },
                            yAxis: {
                                type: 'value'
                            }
                        }
                    };

                    var options = {
                        type: 'post',
                        url: 'operatedatacenter/newgoodsdata/good_status/ajax_histogram',
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
                            title:{
                                subtext:'个数',
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

                    var options = {
                        type: 'post',
                        url: 'operatedatacenter/newgoodsdata/good_status/glass_same_data',
                        data: {
                            platform_a: $("#platform_a").val(),
                            platform_a_name: $("#platform_a option:selected").text(),
                            platform_b: $("#platform_b").val(),
                            platform_b_name: $("#platform_b option:selected").text(),
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
