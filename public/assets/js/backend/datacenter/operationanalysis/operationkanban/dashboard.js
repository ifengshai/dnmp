define(['jquery', 'bootstrap', 'backend', 'addtabs', 'table','form', 'echarts', 'echarts-theme', 'template'], function ($, undefined, Backend, Datatable, Table,Form, Echarts, undefined, Template) {

    var Controller = {
        index: function () {
            // 基于准备好的dom，初始化echarts实例
            //销售额
            var myChart  = Echarts.init(document.getElementById('echart'), 'walden');
            //订单支付成功数
            var myChart2 = Echarts.init(document.getElementById('echart2'),'walden');
            //客单价
            var myChart3 = Echarts.init(document.getElementById('echart3'),'walden');
            //购物车数
            var myChart4 = Echarts.init(document.getElementById('echart4'),'walden');
            //购物车转化率
            var myChart5 = Echarts.init(document.getElementById('echart5'),'walden');
            //注册用户数
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
                    data: [__('Z站销量额'), __('V站销量额'), __('Nihao站销量额')]
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
                yAxis: [{
                    type:'value',
                    max:function(value){
                        return value.max + 200;
                    },
                    min:0
                }],
                grid: [{
                    left: 'left',
                    top: 'top',
                    right: '10',
                    bottom: 30
                }],
                series: [{
                    name: __('Z站销售额'),
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
                    data: Orderdata.zeeloolSalesMoneyList
                },
                {
                    name: __('V站销售额'),
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
                    data: Orderdata.vooguemeSalesMoneyList
                },
                {
                    name: __('Nihao站销售额'),
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
                    data: Orderdata.nihaoSalesMoneyList
                }
                ]
            };
            var option2 = {
                title: {
                    text: '',
                    subtext: ''
                },
                tooltip: {
                    trigger: 'axis'
                },
                legend: {
                    data: [__('Z站订单支付成功数'), __('V站订单支付成功数'), __('Nihao站订单支付成功数')]
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
                yAxis: [{
                    type:'value',
                    max:function(value){
                        return value.max + 200;
                    },
                    min:0
                }],
                grid: [{
                    left: 'left',
                    top: 'top',
                    right: '10',
                    bottom: 30
                }],
                series: [{
                    name: __('Z站订单支付成功数'),
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
                    name: __('V站销量V站订单支付成功数'),
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
                    name: __('Nihao站订单支付成功数'),
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
                }
                ]
            };            
            var option3 = {
                title: {
                    text: '',
                    subtext: ''
                },
                tooltip: {
                    trigger: 'axis'
                },
                legend: {
                    data: [__('Z站客单价'), __('V站客单价'), __('Nihao站客单价')]
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
                    name: __('Z站客单价'),
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
                    data: Orderdata.zeeloolUnitPriceList
                },
                {
                    name: __('V站客单价'),
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
                    data: Orderdata.vooguemeUnitPriceList
                },
                {
                    name: __('Nihao站客单价'),
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
                    data: Orderdata.nihaoUnitPriceList
                }
                ]
            };
            var option4 = {
                title: {
                    text: '',
                    subtext: ''
                },
                tooltip: {
                    trigger: 'axis'
                },
                legend: {
                    data: [__('Z站购物车数'), __('V站购物车数'), __('Nihao站购物车数')]
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
                    name: __('Z站购物车数'),
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
                    data: Orderdata.zeeloolShoppingcartTotal
                },
                {
                    name: __('V站购物车数'),
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
                    data: Orderdata.vooguemeShoppingcartTotal
                },
                {
                    name: __('Nihao站购物车数'),
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
                    data: Orderdata.nihaoShoppingcartTotal
                }
                ]
            };
            var option5 = {
                title: {
                    text: '',
                    subtext: ''
                },
                tooltip: {
                    trigger: 'axis'
                },
                legend: {
                    data: [__('Z站购物车转化率'), __('V站购物车数转化率'), __('Nihao站购物车数转化率')]
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
                    name: __('Z站购物车转化率'),
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
                    data: Orderdata.zeeloolShoppingcartConversion
                },
                {
                    name: __('V站购物车数转化率'),
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
                    data: Orderdata.vooguemeShoppingcartConversion
                },
                {
                    name: __('Nihao站购物车数转化率'),
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
                    data: Orderdata.nihaoShoppingcartConversion
                }
                ]
            };
            var option6 = {
                title: {
                    text: '',
                    subtext: ''
                },
                tooltip: {
                    trigger: 'axis'
                },
                legend: {
                    data: [__('Z站注册用户数'), __('V站注册用户数'), __('Nihao站注册用户数')]
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
                    name: __('Z站注册用户数'),
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
                    data: Orderdata.zeeloolRegisterCustomer
                },
                {
                    name: __('V站注册用户数'),
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
                    data: Orderdata.vooguemeRegisterCustomer
                },
                {
                    name: __('Nihao站注册用户数'),
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
                    data: Orderdata.nihaoRegisterCustomer
                }
                ]
            };                                     
            // 使用刚指定的配置项和数据显示图表。
            myChart.setOption(option);
			myChart2.setOption(option2);
			myChart3.setOption(option3);
            myChart4.setOption(option4);
			myChart5.setOption(option5);
			myChart6.setOption(option6);
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
                    $('#today_sales_money').text(ret.data.today_sales_money);
                    $('#yesterday_sales_money').text(ret.data.yesterday_sales_money);
                    $('#pastsevenday_sales_money').text(ret.data.pastsevenday_sales_money);
                    $('#pastthirtyday_sales_money').text(ret.data.pastthirtyday_sales_money);
                    $('#thismonth_sales_money').text(ret.data.thismonth_sales_money);
                    $('#lastmonth_sales_money').text(ret.data.lastmonth_sales_money);
                    $('#thisyear_sales_money').text(ret.data.thisyear_sales_money);
                    $('#lastyear_sales_money').text(ret.data.lastyear_sales_money);
                    $('#total_sales_money').text(ret.data.total_sales_money);
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
                    $('#today_unit_price').text(ret.data.today_unit_price);
                    $('#yesterday_unit_price').text(ret.data.yesterday_unit_price);
                    $('#pastsevenday_unit_price').text(ret.data.pastsevenday_unit_price);
                    $('#pastthirtyday_unit_price').text(ret.data.pastthirtyday_unit_price);
                    $('#thismonth_unit_price').text(ret.data.thismonth_unit_price);
                    $('#lastmonth_unit_price').text(ret.data.lastmonth_unit_price);
                    $('#thisyear_unit_price').text(ret.data.thisyear_unit_price);
                    $('#lastyear_unit_price').text(ret.data.lastyear_unit_price);
                    $('#total_unit_price').text(ret.data.total_unit_price);
                    $('#today_shoppingcart_total').text(ret.data.today_shoppingcart_total);
                    $('#yesterday_shoppingcart_total').text(ret.data.yesterday_shoppingcart_total);
                    $('#pastsevenday_shoppingcart_total').text(ret.data.pastsevenday_shoppingcart_total);
                    $('#pastthirtyday_shoppingcart_total').text(ret.data.pastthirtyday_shoppingcart_total);
                    $('#thismonth_shoppingcart_total').text(ret.data.thismonth_shoppingcart_total);
                    $('#lastmonth_shoppingcart_total').text(ret.data.lastmonth_shoppingcart_total);
                    $('#thisyear_shoppingcart_total').text(ret.data.thisyear_shoppingcart_total);
                    $('#lastyear_shoppingcart_total').text(ret.data.lastyear_shoppingcart_total);
                    $('#total_shoppingcart_total').text(ret.data.total_shoppingcart_total);
                    $('#today_shoppingcart_conversion').text(ret.data.today_shoppingcart_conversion);
                    $('#yesterday_shoppingcart_conversion').text(ret.data.yesterday_shoppingcart_conversion);
                    $('#pastsevenday_shoppingcart_conversion').text(ret.data.pastsevenday_shoppingcart_conversion);
                    $('#pastthirtyday_shoppingcart_conversion').text(ret.data.pastthirtyday_shoppingcart_conversion);
                    $('#thismonth_shoppingcart_conversion').text(ret.data.thismonth_shoppingcart_conversion);
                    $('#lastmonth_shoppingcart_conversion').text(ret.data.lastmonth_shoppingcart_conversion);
                    $('#thisyear_shoppingcart_conversion').text(ret.data.thisyear_shoppingcart_conversion);
                    $('#lastyear_shoppingcart_conversion').text(ret.data.lastyear_shoppingcart_conversion);
                    $('#total_shoppingcart_conversion').text(ret.data.total_shoppingcart_conversion);
                    $('#today_shoppingcart_new').text(ret.data.today_shoppingcart_new);
                    $('#yesterday_shoppingcart_new').text(ret.data.yesterday_shoppingcart_new);
                    $('#pastsevenday_shoppingcart_new').text(ret.data.pastsevenday_shoppingcart_new);
                    $('#pastthirtyday_shoppingcart_new').text(ret.data.pastthirtyday_shoppingcart_new);
                    $('#thismonth_shoppingcart_new').text(ret.data.thismonth_shoppingcart_new);
                    $('#lastmonth_shoppingcart_new').text(ret.data.lastmonth_shoppingcart_new);
                    $('#thisyear_shoppingcart_new').text(ret.data.thisyear_shoppingcart_new);
                    $('#lastyear_shoppingcart_new').text(ret.data.lastyear_shoppingcart_new);
                    $('#total_shoppingcart_new').text(ret.data.total_shoppingcart_new);
                    $('#today_shoppingcart_newconversion').text(ret.data.today_shoppingcart_newconversion);
                    $('#yesterday_shoppingcart_newconversion').text(ret.data.yesterday_shoppingcart_newconversion);
                    $('#pastsevenday_shoppingcart_newconversion').text(ret.data.pastsevenday_shoppingcart_newconversion);
                    $('#pastthirtyday_shoppingcart_newconversion').text(ret.data.pastthirtyday_shoppingcart_newconversion);
                    $('#thismonth_shoppingcart_newconversion').text(ret.data.thismonth_shoppingcart_newconversion);
                    $('#lastmonth_shoppingcart_newconversion').text(ret.data.lastmonth_shoppingcart_newconversion);
                    $('#thisyear_shoppingcart_newconversion').text(ret.data.thisyear_shoppingcart_newconversion);
                    $('#lastyear_shoppingcart_newconversion').text(ret.data.lastyear_shoppingcart_newconversion);
                    $('#total_shoppingcart_newconversion').text(ret.data.total_shoppingcart_newconversion);
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
			$('#c-order_date').on('change',function(){
                var id = $('#c-order_date').val();
                Backend.api.ajax({
                    url:'datacenter/operationanalysis/operationkanban/dashboard/async_bottom_data',
                    data:{id:id}
                }, function(data, ret){
                    $('#zeelool_pc_sales_money').text(ret.data.zeelool_pc_sales_money);
                    $('#zeelool_pc_sales_num').text(ret.data.zeelool_pc_sales_num);
                    $('#zeelool_pc_unit_price').text(ret.data.zeelool_pc_unit_price);
                    $('#zeelool_wap_sales_money').text(ret.data.zeelool_wap_sales_money);
                    $('#zeelool_wap_sales_num').text(ret.data.zeelool_wap_sales_num);
                    $('#zeelool_wap_unit_price').text(ret.data.zeelool_wap_unit_price);
                    $('#zeelool_app_sales_money').text(ret.data.zeelool_app_sales_money);
                    $('#zeelool_app_sales_num').text(ret.data.zeelool_app_sales_num);
                    $('#zeelool_app_unit_price').text(ret.data.zeelool_app_unit_price);
                    $('#voogueme_pc_sales_money').text(ret.data.voogueme_pc_sales_money);
                    $('#voogueme_pc_sales_num').text(ret.data.voogueme_pc_sales_num);
                    $('#voogueme_pc_unit_price').text(ret.data.voogueme_pc_unit_price);
                    $('#voogueme_wap_sales_money').text(ret.data.voogueme_wap_sales_money);
                    $('#voogueme_wap_sales_num').text(ret.data.voogueme_wap_sales_num);
                    $('#voogueme_wap_unit_price').text(ret.data.voogueme_wap_unit_price);
                    $('#nihao_pc_sales_money').text(ret.data.nihao_pc_sales_money);
                    $('#nihao_pc_sales_num').text(ret.data.nihao_pc_sales_num);
                    $('#nihao_pc_unit_price').text(ret.data.nihao_pc_unit_price);
                    $('#nihao_wap_sales_money').text(ret.data.nihao_wap_sales_money);
                    $('#nihao_wap_sales_num').text(ret.data.nihao_wap_sales_num);
                    $('#nihao_wap_unit_price').text(ret.data.nihao_wap_unit_price);                                                                                  
                    //console.log(ret.data);
                    return false;
                }, function(data, ret){
                    //失败的回调
                    $('#zeelool_pc_sales_money').text(0);
                    $('#zeelool_pc_sales_num').text(0);
                    $('#zeelool_pc_unit_price').text(0);
                    $('#zeelool_wap_sales_money').text(0);
                    $('#zeelool_wap_sales_num').text(0);
                    $('#zeelool_wap_unit_price').text(0);
                    $('#zeelool_app_sales_money').text(0);
                    $('#zeelool_app_sales_num').text(0);
                    $('#zeelool_app_unit_price').text(0);
                    $('#voogueme_pc_sales_money').text(0);
                    $('#voogueme_pc_sales_num').text(0);
                    $('#voogueme_pc_unit_price').text(0);
                    $('#voogueme_wap_sales_money').text(0);
                    $('#voogueme_wap_sales_num').text(0);
                    $('#voogueme_wap_unit_price').text(0);
                    $('#nihao_pc_sales_money').text(0);
                    $('#nihao_pc_sales_num').text(0);
                    $('#nihao_pc_unit_price').text(0);
                    $('#nihao_wap_sales_money').text(0);
                    $('#nihao_wap_sales_num').text(0);
                    $('#nihao_wap_unit_price').text(0);                    
                    //console.log(ret);
                    Layer.alert(ret.msg);
                    return false;
                });
			});            
        }
    };
    return Controller;
});