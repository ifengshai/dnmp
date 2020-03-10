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
				var order_platform = $('#c-order_platform').val();
                Backend.api.ajax({
                    url:'datacenter/operationanalysis/operationkanban/dashboard/async_data',
                    data:{order_platform:order_platform}
                }, function(data, ret){
                    console.log(ret.data.pastsevenday_sales_money);
                    var datas= ret.data.pastsevenday_sales_money.toFixed(2);
                    console.log(datas);
                    $('#today_sales_money').text(ret.data.today_sales_money.toFixed(2));
                    $('#yesterday_sales_money').text(ret.data.yesterday_sales_money.toFixed(2));
                    $('#pastsevenday_sales_money').text(ret.data.pastsevenday_sales_money.toFixed(2));
                    $('#pastthirtyday_sales_money').text(ret.data.pastthirtyday_sales_money.toFixed(2));
                    $('#thismonth_sales_money').text(ret.data.thismonth_sales_money.toFixed(2));
                    $('#lastmonth_sales_money').text(ret.data.lastmonth_sales_money.toFixed(2));
                    $('#thisyear_sales_money').text(ret.data.thisyear_sales_money.toFixed(2));
                    $('#lastyear_sales_money').text(ret.data.lastyear_sales_money.toFixed(2));
                    $('#total_sales_money').text(ret.data.total_sales_money.toFixed(2));
                    $('#today_order_num').text(ret.data.today_order_num);
                    $('#yesterday_order_num').text(ret.data.yesterday_order_num);
                    $('#pastsevenday_order_num').text(ret.data.pastsevenday_order_num);
                    $('#pastthirtyday_order_num').text(ret.data.pastthirtyday_order_num);
                    $('#thismonth_order_num').text(ret.data.thismonth_order_num);
                    $('#lastmonth_order_num').text(ret.data.lastmonth_order_num);
                    $('#thisyear_order_num').text(ret.data.thisyear_order_num);
                    $('#lastyear_order_num').text(ret.data.lastyear_order_num);
                    $('#total_order_num').text(ret.data.total_order_num);
                    $('#today_order_success').text(ret.data.today_order_success);
                    $('#yesterday_order_success').text(ret.data.yesterday_order_success);
                    $('#pastsevenday_order_success').text(ret.data.pastsevenday_order_success);
                    $('#pastthirtyday_order_success').text(ret.data.pastthirtyday_order_success);
                    $('#thismonth_order_success').text(ret.data.thismonth_order_success);
                    $('#lastmonth_order_success').text(ret.data.lastmonth_order_success);
                    $('#thisyear_order_success').text(ret.data.thisyear_order_success);
                    $('#lastyear_order_success').text(ret.data.lastyear_order_success);
                    $('#total_order_success').text(ret.data.total_order_success);
                    $('#today_unit_price').text(ret.data.today_unit_price.toFixed(2));
                    $('#yesterday_unit_price').text(ret.data.yesterday_unit_price.toFixed(2));
                    $('#pastsevenday_unit_price').text(ret.data.pastsevenday_unit_price.toFixed(2));
                    $('#pastthirtyday_unit_price').text(ret.data.pastthirtyday_unit_price.toFixed(2));
                    $('#thismonth_unit_price').text(ret.data.thismonth_unit_price.toFixed(2));
                    $('#lastmonth_unit_price').text(ret.data.lastmonth_unit_price.toFixed(2));
                    $('#thisyear_unit_price').text(ret.data.thisyear_unit_price.toFixed(2));
                    $('#lastyear_unit_price').text(ret.data.lastyear_unit_price.toFixed(2));
                    $('#total_unit_price').text(ret.data.total_unit_price.toFixed(2));
                    $('#today_shoppingcart_total').text(ret.data.today_shoppingcart_total);
                    $('#yesterday_shoppingcart_total').text(ret.data.yesterday_shoppingcart_total);
                    $('#pastsevenday_shoppingcart_total').text(ret.data.pastsevenday_shoppingcart_total);
                    $('#pastthirtyday_shoppingcart_total').text(ret.data.pastthirtyday_shoppingcart_total);
                    $('#thismonth_shoppingcart_total').text(ret.data.thismonth_shoppingcart_total);
                    $('#lastmonth_shoppingcart_total').text(ret.data.lastmonth_shoppingcart_total);
                    $('#thisyear_shoppingcart_total').text(ret.data.thisyear_shoppingcart_total);
                    $('#lastyear_shoppingcart_total').text(ret.data.lastyear_shoppingcart_total);
                    $('#total_shoppingcart_total').text(ret.data.total_shoppingcart_total);
                    $('#today_shoppingcart_conversion').text(ret.data.today_shoppingcart_conversion.toFixed(2));
                    $('#yesterday_shoppingcart_conversion').text(ret.data.yesterday_shoppingcart_conversion.toFixed(2));
                    $('#pastsevenday_shoppingcart_conversion').text(ret.data.pastsevenday_shoppingcart_conversion.toFixed(2));
                    $('#pastthirtyday_shoppingcart_conversion').text(ret.data.pastthirtyday_shoppingcart_conversion.toFixed(2));
                    $('#thismonth_shoppingcart_conversion').text(ret.data.thismonth_shoppingcart_conversion.toFixed(2));
                    $('#lastmonth_shoppingcart_conversion').text(ret.data.lastmonth_shoppingcart_conversion.toFixed(2));
                    $('#thisyear_shoppingcart_conversion').text(ret.data.thisyear_shoppingcart_conversion.toFixed(2));
                    $('#lastyear_shoppingcart_conversion').text(ret.data.lastyear_shoppingcart_conversion.toFixed(2));
                    $('#total_shoppingcart_conversion').text(ret.data.total_shoppingcart_conversion.toFixed(2));
                    $('#today_shoppingcart_new').text(ret.data.today_shoppingcart_new);
                    $('#yesterday_shoppingcart_new').text(ret.data.yesterday_shoppingcart_new);
                    $('#pastsevenday_shoppingcart_new').text(ret.data.pastsevenday_shoppingcart_new);
                    $('#pastthirtyday_shoppingcart_new').text(ret.data.pastthirtyday_shoppingcart_new);
                    $('#thismonth_shoppingcart_new').text(ret.data.thismonth_shoppingcart_new);
                    $('#lastmonth_shoppingcart_new').text(ret.data.lastmonth_shoppingcart_new);
                    $('#thisyear_shoppingcart_new').text(ret.data.thisyear_shoppingcart_new);
                    $('#lastyear_shoppingcart_new').text(ret.data.lastyear_shoppingcart_new);
                    $('#total_shoppingcart_new').text(ret.data.total_shoppingcart_new);
                    $('#today_shoppingcart_newconversion').text(ret.data.today_shoppingcart_newconversion.toFixed(2));
                    $('#yesterday_shoppingcart_newconversion').text(ret.data.yesterday_shoppingcart_newconversion.toFixed(2));
                    $('#pastsevenday_shoppingcart_newconversion').text(ret.data.pastsevenday_shoppingcart_newconversion.toFixed(2));
                    $('#pastthirtyday_shoppingcart_newconversion').text(ret.data.pastthirtyday_shoppingcart_newconversion.toFixed(2));
                    $('#thismonth_shoppingcart_newconversion').text(ret.data.thismonth_shoppingcart_newconversion.toFixed(2));
                    $('#lastmonth_shoppingcart_newconversion').text(ret.data.lastmonth_shoppingcart_newconversion.toFixed(2));
                    $('#thisyear_shoppingcart_newconversion').text(ret.data.thisyear_shoppingcart_newconversion.toFixed(2));
                    $('#lastyear_shoppingcart_newconversion').text(ret.data.lastyear_shoppingcart_newconversion.toFixed(2));
                    $('#total_shoppingcart_newconversion').text(ret.data.total_shoppingcart_newconversion.toFixed(2));
                    $('#today_register_customer').text(ret.data.today_register_customer);
                    $('#yesterday_register_customer').text(ret.data.yesterday_register_customer);
                    $('#pastsevenday_register_customer').text(ret.data.pastsevenday_register_customer);
                    $('#pastthirtyday_register_customer').text(ret.data.pastthirtyday_register_customer);
                    $('#thismonth_register_customer').text(ret.data.thismonth_register_customer);
                    $('#lastmonth_register_customer').text(ret.data.lastmonth_register_customer);
                    $('#thisyear_register_customer').text(ret.data.thisyear_register_customer);
                    $('#lastyear_register_customer').text(ret.data.lastyear_register_customer);
                    $('#total_register_customer').text(ret.data.total_register_customer);
                    $('#today_sign_customer').text(ret.data.today_sign_customer);
                    $('#yesterday_sign_customer').text(ret.data.yesterday_sign_customer);
                    $('#pastsevenday_sign_customer').text(ret.data.pastsevenday_sign_customer);
                    $('#pastthirtyday_sign_customer').text(ret.data.pastthirtyday_sign_customer);
                    $('#thismonth_sign_customer').text(ret.data.thismonth_sign_customer);
                    $('#lastmonth_sign_customer').text(ret.data.lastmonth_sign_customer);
                    $('#thisyear_sign_customer').text(ret.data.thisyear_sign_customer);
                    $('#lastyear_sign_customer').text(ret.data.lastyear_sign_customer);
                    $('#total_sign_customer').text(ret.data.total_sign_customer);                                                                                    
                    //console.log(ret.data);
                    return false;
                }, function(data, ret){
                    //失败的回调
                    $('#today_sales_money').text(0);
                    $('#yesterday_sales_money').text(0);
                    $('#pastsevenday_sales_money').text(0);
                    $('#pastthirtyday_sales_money').text(0);
                    $('#thismonth_sales_money').text(0);
                    $('#lastmonth_sales_money').text(0);
                    $('#thisyear_sales_money').text(0);
                    $('#lastyear_sales_money').text(0);
                    $('#total_sales_money').text(0);
                    $('#today_order_num').text(0);
                    $('#yesterday_order_num').text(0);
                    $('#pastsevenday_order_num').text(0);
                    $('#pastthirtyday_order_num').text(0);
                    $('#thismonth_order_num').text(0);
                    $('#lastmonth_order_num').text(0);
                    $('#thisyear_order_num').text(0);
                    $('#lastyear_order_num').text(0);
                    $('#total_order_num').text(0);
                    $('#today_order_success').text(0);
                    $('#yesterday_order_success').text(0);
                    $('#pastsevenday_order_success').text(0);
                    $('#pastthirtyday_order_success').text(0);
                    $('#thismonth_order_success').text(0);
                    $('#lastmonth_order_success').text(0);
                    $('#thisyear_order_success').text(0);
                    $('#lastyear_order_success').text(0);
                    $('#total_order_success').text(0);
                    $('#today_unit_price').text(0);
                    $('#yesterday_unit_price').text(0);
                    $('#pastsevenday_unit_price').text(0);
                    $('#pastthirtyday_unit_price').text(0);
                    $('#thismonth_unit_price').text(0);
                    $('#lastmonth_unit_price').text(0);
                    $('#thisyear_unit_price').text(0);
                    $('#lastyear_unit_price').text(0);
                    $('#total_unit_price').text(0);
                    $('#today_shoppingcart_total').text(0);
                    $('#yesterday_shoppingcart_total').text(0);
                    $('#pastsevenday_shoppingcart_total').text(0);
                    $('#pastthirtyday_shoppingcart_total').text(0);
                    $('#thismonth_shoppingcart_total').text(0);
                    $('#lastmonth_shoppingcart_total').text(0);
                    $('#thisyear_shoppingcart_total').text(0);
                    $('#lastyear_shoppingcart_total').text(0);
                    $('#total_shoppingcart_total').text(0);
                    $('#today_shoppingcart_conversion').text(0);
                    $('#yesterday_shoppingcart_conversion').text(0);
                    $('#pastsevenday_shoppingcart_conversion').text(0);
                    $('#pastthirtyday_shoppingcart_conversion').text(0);
                    $('#thismonth_shoppingcart_conversion').text(0);
                    $('#lastmonth_shoppingcart_conversion').text(0);
                    $('#thisyear_shoppingcart_conversion').text(0);
                    $('#lastyear_shoppingcart_conversion').text(0);
                    $('#total_shoppingcart_conversion').text(0);
                    $('#today_shoppingcart_new').text(0);
                    $('#yesterday_shoppingcart_new').text(0);
                    $('#pastsevenday_shoppingcart_new').text(0);
                    $('#pastthirtyday_shoppingcart_new').text(0);
                    $('#thismonth_shoppingcart_new').text(0);
                    $('#lastmonth_shoppingcart_new').text(0);
                    $('#thisyear_shoppingcart_new').text(0);
                    $('#lastyear_shoppingcart_new').text(0);
                    $('#total_shoppingcart_new').text(0);
                    $('#today_shoppingcart_newconversion').text(0);
                    $('#yesterday_shoppingcart_newconversion').text(0);
                    $('#pastsevenday_shoppingcart_newconversion').text(0);
                    $('#pastthirtyday_shoppingcart_newconversion').text(0);
                    $('#thismonth_shoppingcart_newconversion').text(0);
                    $('#lastmonth_shoppingcart_newconversion').text(0);
                    $('#thisyear_shoppingcart_newconversion').text(0);
                    $('#lastyear_shoppingcart_newconversion').text(0);
                    $('#total_shoppingcart_newconversion').text(0);
                    $('#today_register_customer').text(0);
                    $('#yesterday_register_customer').text(0);
                    $('#pastsevenday_register_customer').text(0);
                    $('#pastthirtyday_register_customer').text(0);
                    $('#thismonth_register_customer').text(0);
                    $('#lastmonth_register_customer').text(0);
                    $('#thisyear_register_customer').text(0);
                    $('#lastyear_register_customer').text(0);
                    $('#total_register_customer').text(0);
                    $('#today_sign_customer').text(0);
                    $('#yesterday_sign_customer').text(0);
                    $('#pastsevenday_sign_customer').text(0);
                    $('#pastthirtyday_sign_customer').text(0);
                    $('#thismonth_sign_customer').text(0);
                    $('#lastmonth_sign_customer').text(0);
                    $('#thisyear_sign_customer').text(0);
                    $('#lastyear_sign_customer').text(0);
                    $('#total_sign_customer').text(0);                      
                    //console.log(ret);
                    Layer.alert(ret.msg);
                    return false;
                });
			});
        }
    };
    return Controller;
});