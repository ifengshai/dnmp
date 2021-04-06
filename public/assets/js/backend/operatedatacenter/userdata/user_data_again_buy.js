define(['jquery', 'bootstrap', 'backend', 'table', 'form', 'echartsobj'], function ($, undefined, Backend, Table, Form, EchartObj) {

    var Controller = {
        year_again_buy_rate: function () {
            Controller.api.bindevent();
            
            Controller.api.formatter.year_again_buy_num_line(); //年复购频次折线图
            Controller.api.formatter.year_again_buy_rate_line(); //年复购率折线图

            $('#order_platform').on('change',function(){
                
                Controller.api.formatter.year_again_buy_num_line(); //年复购频次折线图
                Controller.api.formatter.year_again_buy_rate_line(); //年复购率折线图

				var order_platform = $('#order_platform').val();
                Backend.api.ajax({
                    url:'operatedatacenter/userdata/user_data_again_buy/year_again_buy_rate',
                    data:{order_platform:order_platform}
                }, function(data, ret){
                    $('#table_data').html(ret.data);
                    return false;
                });
            });
            $("#export").click(function(){
                var order_platform = $('#order_platform').val();
                window.location.href=Config.moduleurl+'/operatedatacenter/userdata/user_data_again_buy/year_again_buy_export?order_platform='+order_platform;
            });
        },
        old_user_rate: function () {
            Controller.api.bindevent();
            
            Controller.api.formatter.old_user_rate_line(); //老用户折线图
            Controller.api.formatter.new_old_user_rate_line(); //新老用户环比折线图

            $('#order_platform').on('change',function(){
                
                Controller.api.formatter.old_user_rate_line(); //老用户折线图
                Controller.api.formatter.new_old_user_rate_line(); //新老用户环比折线图

				var order_platform = $('#order_platform').val();
                Backend.api.ajax({
                    url:'operatedatacenter/userdata/user_data_again_buy/old_user_rate',
                    data:{order_platform:order_platform}
                }, function(data, ret){
                    $('#table_data').html(ret.data);
                    return false;
                });
            });
            $("#export").click(function(){
                var order_platform = $('#order_platform').val();
                window.location.href=Config.moduleurl+'/operatedatacenter/userdata/user_data_again_buy/old_user_export?order_platform='+order_platform;
            });
        },
        user_define_repurchase_rate: function () {
            Controller.api.bindevent();
            
            Controller.api.formatter.user_define_repurchase_num_line(); //年复购频次折线图
            Controller.api.formatter.user_define_repurchase_rate_line(); //年复购率折线图

            $('#order_platform').on('change',function(){
                
                Controller.api.formatter.user_define_repurchase_num_line(); //年复购频次折线图
                Controller.api.formatter.user_define_repurchase_rate_line(); //年复购率折线图

				var order_platform = $('#order_platform').val();
				var repurchase_week = $('#repurchase_week').val();
                Backend.api.ajax({
                    url:'operatedatacenter/userdata/user_data_again_buy/user_define_repurchase_rate',
                    data:{order_platform:order_platform,repurchase_week:repurchase_week}
                }, function(data, ret){
                    $('#table_data').html(ret.data);
                    return false;
                });
            });
            $('#repurchase_week').on('change',function(){
                
                Controller.api.formatter.user_define_repurchase_num_line(); //年复购频次折线图
                Controller.api.formatter.user_define_repurchase_rate_line(); //年复购率折线图

				var order_platform = $('#order_platform').val();
				var repurchase_week = $('#repurchase_week').val();
                Backend.api.ajax({
                    url:'operatedatacenter/userdata/user_data_again_buy/user_define_repurchase_rate',
                    data:{order_platform:order_platform,repurchase_week:repurchase_week}
                }, function(data, ret){
                    $('#table_data').html(ret.data);
                    return false;
                });
            });
            $("#export").click(function(){
                var order_platform = $('#order_platform').val();
                var repurchase_week = $('#repurchase_week').val();
                window.location.href=Config.moduleurl+'/operatedatacenter/userdata/user_data_again_buy/user_define_repurchase_rate_export?order_platform='+order_platform+'&repurchase_week='+repurchase_week;
            });
        },
        api: {
            formatter: {
                year_again_buy_num_line: function () {
                    //年复购频次折线图
                    var chartOptions = {
                        targetId: 'echart2',
                        downLoadTitle: '图表',
                        type: 'line',
                        line: {
                            tooltip: { 
                                formatter: function (param) { //格式化提示信息
                                    return param[0].name + '<br/>' + param[0].seriesName + '：' + param[0].value;
                                }
                            },
                            legend: { //图例配置
                                padding: 5,
                                top: '2%',
                                data: ['年复购频次']
                            },
                            grid: {
                                left: '15%',
                            },
                        },
                        yAxis: [
                            {
                                type: 'value',
                                axisLabel: {
                                    formatter: '{value}'
                                }
                            }
                        ],
                    };
                    var options = {
                        type: 'post',
                        url: 'operatedatacenter/userdata/user_data_again_buy/year_again_buy_num_line',
                        data: {
                            'order_platform' : $("#order_platform").val(),
                        }
                    }
                    EchartObj.api.ajax(options, chartOptions)
                },
                year_again_buy_rate_line: function () {
                    //年复购率折线图
                    var chartOptions = {
                        targetId: 'echart1',
                        downLoadTitle: '图表',
                        type: 'line',
                        line: {
                            tooltip: { 
                                formatter: function (param) { //格式化提示信息
                                    return param[0].name + '<br/>' + param[0].seriesName + '：' + param[0].value+'%';
                                }
                            },
                            legend: { //图例配置
                                padding: 5,
                                top: '2%',
                                data: ['年复购率']
                            },
                            grid: {
                                left: '15%',
                            },
                            yAxis: [
                                {
                                    type: 'value',
                                    axisLabel: {
                                        formatter: '{value} %'
                                    }
                                }
                            ],
                        },
                    };
                    var options = {
                        type: 'post',
                        url: 'operatedatacenter/userdata/user_data_again_buy/year_again_buy_rate_line',
                        data: {
                            'order_platform' : $("#order_platform").val(),
                        }
                    }
                    EchartObj.api.ajax(options, chartOptions)
                },
                old_user_rate_line: function () {
                    //老用户占比折线图
                    var chartOptions = {
                        targetId: 'echart1',
                        downLoadTitle: '图表',
                        type: 'line',
                        line: {
                            tooltip: { 
                                formatter: function (param) { //格式化提示信息
                                    return param[0].name + '<br/>' + param[0].seriesName + '：' + param[0].value+'%';
                                }
                            },
                            legend: { //图例配置
                                padding: 5,
                                top: '2%',
                                data: ['老用户占比']
                            },
                            grid: {
                                left: '15%',
                            },
                            yAxis: [
                                {
                                    type: 'value',
                                    axisLabel: {
                                        formatter: '{value} %'
                                    }
                                }
                            ],
                        },
                    };
                    var options = {
                        type: 'post',
                        url: 'operatedatacenter/userdata/user_data_again_buy/old_user_rate_line',
                        data: {
                            'order_platform' : $("#order_platform").val(),
                        }
                    }
                    EchartObj.api.ajax(options, chartOptions)
                },
                new_old_user_rate_line: function () {
                    //新老用户环比折线图
                    var chartOptions = {
                        targetId: 'echart20',
                        downLoadTitle: '图表',
                        type: 'bar',
                        bar: {
                            tooltip: { //提示框组件。
                                trigger: 'axis', // 触发类型。可选项item:数据项图形触发，主要在散点图，饼图等无类目轴的图表中使用。axis:坐标轴触发，主要在柱状图，折线图等会使用类目轴的图表中使用。
                                axisPointer: { //坐标轴指示器配置项。
                                    type: 'line' //指示器类型。可选项'line' 直线指示器。'shadow' 阴影指示器。'cross' 十字准星指示器。其实是种简写，表示启用两个正交的轴的 axisPointer。
                                },
                                formatter: function (param) { //格式化提示信息
                                    console.log(param)
                                    return param[0].name + '<br/>' + param[0].seriesName + '：' + param[0].value + '%<br/>' + param[1].seriesName + '：' + param[1].value+'%';
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
                                data: ['老用户环比', '新用户环比']
                            },
                            xAxis: [
                                {
                                    type: 'category'
                                }
                            ],
                            yAxis: [
                                {
                                    type: 'value',
                                    axisLabel: {
                                        formatter: '{value} %'
                                    }
                                }
                            ],
                        }
                    };
                    var options = {
                        type: 'post',
                        url: 'operatedatacenter/userdata/user_data_again_buy/new_old_user_rate_line',
                        data: {
                            'order_platform': $("#order_platform").val()
                        }
                    }
                    EchartObj.api.ajax(options, chartOptions)
                },
                user_define_repurchase_num_line: function () {
                    //年复购频次折线图--自定义复购率数据
                    var chartOptions = {
                        targetId: 'echart2',
                        downLoadTitle: '图表',
                        type: 'line',
                        line: {
                            tooltip: { 
                                formatter: function (param) { //格式化提示信息
                                    return param[0].name + '<br/>' + param[0].seriesName + '：' + param[0].value;
                                }
                            },
                            legend: { //图例配置
                                padding: 5,
                                top: '2%',
                                data: ['年复购频次']
                            },
                            grid: {
                                left: '15%',
                            },
                        },
                        yAxis: [
                            {
                                type: 'value',
                                axisLabel: {
                                    formatter: '{value}'
                                }
                            }
                        ],
                    };
                    var options = {
                        type: 'post',
                        url: 'operatedatacenter/userdata/user_data_again_buy/user_define_repurchase_num_line',
                        data: {
                            'order_platform' : $("#order_platform").val(),
                            'repurchase_week' : $("#repurchase_week").val(),
                        }
                    }
                    EchartObj.api.ajax(options, chartOptions)
                },
                user_define_repurchase_rate_line: function () {
                    //年复购率折线图--自定义复购率数据
                    var chartOptions = {
                        targetId: 'echart1',
                        downLoadTitle: '图表',
                        type: 'line',
                        line: {
                            tooltip: { 
                                formatter: function (param) { //格式化提示信息
                                    return param[0].name + '<br/>' + param[0].seriesName + '：' + param[0].value+'%';
                                }
                            },
                            legend: { //图例配置
                                padding: 5,
                                top: '2%',
                                data: ['年复购率']
                            },
                            grid: {
                                left: '15%',
                            },
                            yAxis: [
                                {
                                    type: 'value',
                                    axisLabel: {
                                        formatter: '{value} %'
                                    }
                                }
                            ],
                        },
                    };
                    var options = {
                        type: 'post',
                        url: 'operatedatacenter/userdata/user_data_again_buy/user_define_repurchase_rate_line',
                        data: {
                            'order_platform' : $("#order_platform").val(),
                            'repurchase_week' : $("#repurchase_week").val(),
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