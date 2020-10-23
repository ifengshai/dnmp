define(['jquery', 'bootstrap', 'backend', 'table', 'form', 'echartsobj'], function ($, undefined, Backend, Table, Form, EchartObj) {

    var Controller = {
        index: function () {
            Controller.api.bindevent();
            // 初始化表格参数配置
            Table.api.init({
                extend: {
                    index_url: 'operatedatacenter/orderdata/order_data_change/index',
                    add_url: 'operatedatacenter/orderdata/order_data_change/add',
                    edit_url: 'operatedatacenter/orderdata/order_data_change/edit',
                    del_url: 'operatedatacenter/orderdata/order_data_change/del',
                    multi_url: 'operatedatacenter/orderdata/order_data_change/multi',
                    table: 'order_data_change',
                }
            });

            var table = $("#table");
            // 初始化表格
            table.bootstrapTable({
                url: $.fn.bootstrapTable.defaults.extend.index_url,
                pk: 'day_date',
                sortName: 'day_date',
                sortOrder: 'desc',
                search: false,
                showToggle:false,
                cardView: false,
                searchFormVisible:false,
                commonSearch: false,
                columns: [
                    [
                        {field: 'day_date', title: __('日期'),sortable: true},
                        { field: 'sessions', title: __('会话数'),sortable: true},
                        { field: 'add_cart_rate', title: __('加购率'),sortable: true},
                        { field: 'session_rate', title: __('会话转化率'),sortable: true },
                        { field: 'order_num', title: __('订单数'),sortable: true},
                        { field: 'order_unit_price', title: __('客单价'),sortable: true },
                        { field: 'new_cart_num', title: __('新增购物车数量'),sortable: true },
                        { field: 'update_cart_num', title: __('更新购物车数量'),sortable: true },
                        { field: 'sales_total_money', title: __('订单金额'),sortable: true },
                        { field: 'register_num', title: __('注册量'),sortable: true }
                    ]
                ]
            });
            // 为表格绑定事件
            Table.api.bindevent(table);

            Controller.api.formatter.order_sales_data_line();
            Controller.api.formatter.order_num_data_line();
            $("#change_submit").click(function(){
                Controller.api.formatter.order_sales_data_line();
                Controller.api.formatter.order_num_data_line();

                var params = table.bootstrapTable('getOptions')
                params.queryParams = function(params) {
         
                    //定义参数
                    var filter = {};
                    //遍历form 组装json
                    $.each($("#form").serializeArray(), function(i, field) {
                        filter[field.name] = field.value;
                    });
         
                    //参数转为json字符串
                    params.filter = JSON.stringify(filter)
                    console.info(params);
                    return params;
                }

                table.bootstrapTable('refresh',params);
            });
            $("#change_reset").click(function(){
                $("#order_platform").val(1),
                $("#time_str").val(''),
                Controller.api.formatter.order_sales_data_line();
                Controller.api.formatter.order_num_data_line();

                var params = table.bootstrapTable('getOptions')
                params.queryParams = function(params) {
         
                    //定义参数
                    var filter = {};
                    //遍历form 组装json
                    $.each($("#form").serializeArray(), function(i, field) {
                        filter[field.name] = field.value;
                    });
         
                    //参数转为json字符串
                    params.filter = JSON.stringify(filter)
                    console.info(params);
                    return params;
                }

                table.bootstrapTable('refresh',params);
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
                order_num_data_line: function () {
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
                                data: ['购物车数量', '订单数量']
                            },
                            xAxis: [
                                {
                                    type: 'category'
                                }
                            ],
                            yAxis: [
                                {
                                    type: 'value',
                                    name: '数量',
                                    axisLabel: {
                                        formatter: '{value} 个'
                                    }
                                }
                            ],
                        }
                    };
                    
                    var options1 = {
                        type: 'post',
                        url: 'operatedatacenter/orderdata/order_data_change/order_num_data_line',
                        data: {
                            'order_platform': $("#order_platform").val(),
                            'time_str': $("#time_str").val(),
                        }
                    }
                    EchartObj.api.ajax(options1, chartOptions1)
                },
                order_sales_data_line: function () {
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
                                data: ['会话数', '销售额']
                            },
                            xAxis: [
                                {
                                    type: 'category'
                                }
                            ],
                            yAxis: [
                                {
                                    type: 'value',
                                    name: '会话数',
                                    axisLabel: {
                                        formatter: '{value} 个'
                                    }
                                },
                                {
                                    type: 'value',
                                    name: '销售额',
                                    axisLabel: {
                                        formatter: '$ {value}'
                                    }
                                }
                            ],
                        }
                    };

                    var options = {
                        type: 'post',
                        url: 'operatedatacenter/orderdata/order_data_change/order_sales_data_line',
                        data: {
                            'order_platform': $("#order_platform").val(),
                            'time_str': $("#time_str").val(),
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