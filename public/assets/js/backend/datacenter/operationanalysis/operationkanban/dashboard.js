define(['jquery', 'bootstrap', 'backend', 'addtabs', 'table','form', 'echarts', 'echarts-theme', 'template'], function ($, undefined, Backend, Datatable, Table,Form, Echarts, undefined, Template) {

    var Controller = {
        index: function () {
            // 基于准备好的dom，初始化echarts实例
            var myChart  = Echarts.init(document.getElementById('echart'), 'walden');
			var myChart2 = Echarts.init(document.getElementById('echart2'),'walden');
			var myChart3 = Echarts.init(document.getElementById('echart3'),'walden');
			var myChart4 = Echarts.init(document.getElementById('echart4'),'walden');
			var myChart5 = Echarts.init(document.getElementById('echart5'),'walden');
			var myChart6 = Echarts.init(document.getElementById('echart6'),'walden');
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
                    data: [__('Z站销量'), __('V站销量'), __('Nihao站销量')]
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
                    //data: Orderdata.column
					data:[1,2,3]
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
                    //data: Orderdata.zeeloolSalesNumList
					data: [1,2,3,4,5]
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
                    //data: Orderdata.vooguemeSalesNumList
					data:[6,7,8,9,10]
                },
                {
                    name: __('Nihao站销量'),
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
                    //data: Orderdata.nihaoSalesNumList
					data:[11,12,13,14,15]
                }
                ]
            };

            // 使用刚指定的配置项和数据显示图表。
            myChart.setOption(option);
			myChart2.setOption(option);
			myChart3.setOption(option);
            myChart4.setOption(option);
			myChart5.setOption(option);
			myChart6.setOption(option);
            $(window).resize(function () {
                myChart.resize();
				myChart2.resize();
				myChart3.resize();
				myChart4.resize();
				myChart5.resize();
				myChart6.resize();
            });
			Form.api.bindevent($("form[role=form]"));
			$('#c-order_platform').on('change',function(){
				var id = $('#c-order_platform').val();
				console.log(id);
			});
        }
    };
    return Controller;
});