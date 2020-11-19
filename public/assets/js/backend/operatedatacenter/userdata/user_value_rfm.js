define(['jquery', 'bootstrap', 'backend', 'addtabs', 'table', 'form', 'echartsobj', 'echarts', 'echarts-theme', 'template', 'custom-css'], function ($, undefined, Backend, Datatable, Table, Form, EchartObj, undefined, Template) {

    var Controller = {
        user_contribution_distribution: function () {
        
        
            //订单数据概况折线图
            Controller.api.formatter.histogram1();
        
            $(document).on('click', '.btn-success', function () {
                Controller.api.formatter.histogram1();
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
                histogram1: function (){
                    //柱状图
                    var chartOptions = {
                        targetId: 'echart1',
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
                                    if(param[0].seriesName == 0){
                                        var percent = 0;
                                    }else{
                                        var percent = (param[0].value/param[0].seriesName*100).toFixed(2);
                                    }
                                    return param[0].name + '<br/>人数：' + param[0].value + '<br/>占比：' + percent + '%';
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
                        url: 'operatedatacenter/userdata/user_value_rfm/ajax_user_order_amount',
                        data: {
                            order_platform: $("#order_platform").val()
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