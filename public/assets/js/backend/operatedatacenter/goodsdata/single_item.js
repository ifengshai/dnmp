define(['jquery', 'bootstrap', 'backend', 'table', 'form', 'echartsobj'], function ($, undefined, Backend, Table, Form, EchartObj) {

    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                extend: {
                    index_url: 'operatedatacenter/goodsdata/single_item/index' + location.search,
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
                        { checkbox: true },
                        { field: 'id', title: __('订单编号') },
                        { field: 'id', title: __('订单状态') },
                        { field: 'id', title: __('金额') },
                        { field: 'id', title: __('优惠金额') },
                        { field: 'id', title: __('订单日期') },
                        { field: 'id', title: __('支付邮箱') }
                    ]
                ]
            });

            // 为表格绑定事件
            Table.api.bindevent(table);

            Controller.api.formatter.line_chart();
            Controller.api.formatter.sku_sales_data_bar();
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