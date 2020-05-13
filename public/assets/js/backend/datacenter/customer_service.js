define(['jquery', 'bootstrap', 'backend', 'addtabs', 'table','form','echartsobj', 'echarts', 'echarts-theme', 'template','custom-css'], function ($, undefined, Backend, Datatable, Table,Form, EchartObj, undefined, Template) {

    var Controller = {
        index: function () {
            // 指定图表的配置项和数据
            Form.api.bindevent($("form[role=form]"));            
			$('#c-order_platform').on('change',function(){
				var order_platform = $('#c-order_platform').val();
                Backend.api.ajax({
                    url:'datacenter/operationanalysis/operationkanban/dashboard/async_data',
                    data:{order_platform:order_platform}
                }, function(data, ret){
                    return false;
                }, function(data, ret){
                    //失败的回调                      
                    //console.log(ret);
                    Layer.alert(ret.msg);
                    return false;
                });
            });
			$('#submit').on('click',function(){
                var create_time = $('#created_at').val();
                Backend.api.ajax({
                    url:'datacenter/operationanalysis/operationkanban/dashboard/async_bottom_data',
                    data:{create_time:create_time}
                }, function(data, ret){
                    console.log(ret);                                                                               
                    //console.log(ret.data);
                    return false;
                }, function(data, ret){
                    Layer.alert(ret.msg);
                    return false;
                });
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
            },
            bindevent: function () {
                Form.api.bindevent($("form[role=form]"));
            }
        },
        detail:function(){
            Controller.api.formatter.daterangepicker($("form[role=form1]"));
            Form.api.bindevent($("form[role=form]"));
            var chartOptions1 = {
                targetId: 'echart1',
                downLoadTitle: '图表',
                type: 'pie',
            };
            var chartOptions2 = {
                targetId: 'echart2',
                downLoadTitle: '图表',
                type: 'pie',               
            };
            var chartOptions3 = {
                targetId: 'echart3',
                downLoadTitle: '图表',
                type: 'pie',               
            };
            var chartOptions4 = {
                targetId: 'echart4',
                downLoadTitle: '图表',
                type: 'pie',               
            };                                   
            var time = $('#create_time').val();
            var platform = $('#c-order_platform').val();           
            var options1 = {
                type: 'post',
                url: 'datacenter/customer_service/detail',
                data: {
                    'time': time,
                    'platform': platform,
                    'key':'echart1' 
                }
           } 
           var options2 = {
                type: 'post',
                url: 'datacenter/customer_service/detail',
                data: {
                    'time': time,
                    'platform': platform,
                    'key':'echart2' 
                }
            }
            var options3 = {
                type: 'post',
                url: 'datacenter/customer_service/detail',
                data: {
                    'time': time,
                    'platform': platform,
                    'key':'echart3' 
                }
            }
            var options4 = {
                type: 'post',
                url: 'datacenter/customer_service/detail',
                data: {
                    'time': time,
                    'platform': platform,
                    'key':'echart4' 
                }
            }                            
            EchartObj.api.ajax(options1, chartOptions1);
            EchartObj.api.ajax(options2, chartOptions2);
            EchartObj.api.ajax(options3, chartOptions3);
            EchartObj.api.ajax(options4, chartOptions4);
            $(".statistics").on('click',function(){
                var value = $(this).data("value");
                if(value>0){
                    var time = $('#create_time').val();
                    var platform = $('#c-order_platform').val();  
                    var chartOptions = {
                        targetId: 'echart2',
                        downLoadTitle: '图表',
                        type: 'pie',
                    };
                    var options = {
                        type: 'post',
                        url: 'datacenter/customer_service/problem',
                        data: {
                            'time': time,
                            'platform': platform,
                            'value':value
                        }
                   };
                   EchartObj.api.ajax(options, chartOptions);                                         
                }
            });
        },
    };
    return Controller;
});