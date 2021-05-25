define(['jquery', 'bootstrap', 'backend', 'addtabs', 'table', 'echarts', 'echarts-theme', 'template'], function ($, undefined, Backend, Datatable, Table, Echarts, undefined, Template) {

    var Controller = {
        index: function () {
            // 基于准备好的dom，初始化echarts实例
            var myChart = Echarts.init(document.getElementById('echart'), 'walden');

            // 指定图表的配置项和数据
            var option = {
                title: {
                    text: '',
                    subtext: ''
                },
                tooltip: {
                    trigger: 'axis'
                },
                legend: {
                    data: [__('Z站销量'), __('V站销量'), __('Meeloog站销量'), __('ZeeloolDe站销量'), __('ZeeloolJp站销量')]
                },
                toolbox: {
                    show: false,
                    feature: {
                        magicType: { show: true, type: ['stack', 'tiled'] },
                        saveAsImage: { show: true }
                    }
                },
                xAxis: {
                    type: 'category',
                    boundaryGap: false,
                    data: Orderdata.column
                },
                yAxis: {},
                grid: [{
                    left: 'left',
                    top: 'top',
                    right: '10',
                    bottom: 30
                }],
                series: [{
                    name: __('Z站销量'),
                    type: 'line',
                    smooth: true,
                    areaStyle: {
                        normal: {}
                    },
                    lineStyle: {
                        normal: {
                            width: 1.5
                        }
                    },
                    data: Orderdata.zeeloolSalesNumList
                },
                {
                    name: __('V站销量'),
                    type: 'line',
                    smooth: true,
                    areaStyle: {
                        normal: {}
                    },
                    lineStyle: {
                        normal: {
                            width: 1.5
                        }
                    },
                    data: Orderdata.vooguemeSalesNumList
                },
                    {
                        name: __('Meeloog站销量'),
                        type: 'line',
                        smooth: true,
                        areaStyle: {
                            normal: {}
                        },
                        lineStyle: {
                            normal: {
                                width: 1.5
                            }
                        },
                    data: Orderdata.nihaoSalesNumList
                },
                // {
                //     name: __('ZeeloolEs站销量'),
                //     type: 'line',
                //     smooth: true,
                //     areaStyle: {
                //         normal: {}
                //     },
                //     lineStyle: {
                //         normal: {
                //             width: 1.5
                //         }
                //     },
                //     data: Orderdata.zeeloolEsSalesNumList
                // }, 
                {
                    name: __('ZeeloolDe站销量'),
                    type: 'line',
                    smooth: true,
                    areaStyle: {
                        normal: {}
                    },
                    lineStyle: {
                        normal: {
                            width: 1.5
                        }
                    },
                    data: Orderdata.zeeloolDeSalesNumList
                },
                {
                    name: __('ZeeloolJp站销量'),
                    type: 'line',
                    smooth: true,
                    areaStyle: {
                        normal: {}
                    },
                    lineStyle: {
                        normal: {
                            width: 1.5
                        }
                    },
                    data: Orderdata.zeeloolJpSalesNumList
                }
                ]
            };

            // 使用刚指定的配置项和数据显示图表。
            myChart.setOption(option);


            $(window).resize(function () {
                myChart.resize();
            });

            $(document).on("click", ".btn-checkversion", function () {
                top.window.$("[data-toggle=checkupdate]").trigger("click");
            });

        }
    };

    return Controller;
});