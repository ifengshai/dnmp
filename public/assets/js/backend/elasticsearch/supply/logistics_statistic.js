define(['jquery', 'bootstrap', 'backend', 'addtabs', 'table','form','echartsobj', 'echarts', 'echarts-theme', 'template','custom-css'], function ($, undefined, Backend, Datatable, Table,Form, EchartObj, undefined, Template) {

    var Controller = {
        index: function () {
            // 指定图表的配置项和数据
            Controller.api.formatter.daterangepicker($("form[role=form1]"));
            Controller.api.formatter.daterangepicker($("div[role=form8]"));

            getTableData();
            Controller.api.formatter.send_num_echart();   //发货数量占比饼图
            Controller.api.formatter.delieved_num_echart();   //妥投比率占比饼图
            //点击提交
            $(document).on('click','#workload-btn',function(){
                getTableData();
                Controller.api.formatter.send_num_echart();   //发货数量占比饼图
                Controller.api.formatter.delieved_num_echart();   //妥投比率占比饼图
            }); 
            $(document).on('click','#export',function(){
                var create_time = $('#workload_time').val();
                var platform    = $('#order_platform_workload').val();
                if (!create_time) {
                    Toastr.error('请先选择时间范围');
                    return false;
                }
                if (platform <= 0) {
                    Toastr.error('请先选择站点');
                    return false;
                }
                window.location.href=Config.moduleurl+'/logistics/logistics_statistic/export_not_shipped?create_time='+create_time+'&platform='+platform;
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
                send_num_echart:function(){
                    //发货数量统计
                    var chartOptions1 = {
                        targetId: 'echart1',
                        downLoadTitle: '图表',
                        type: 'pie',
                        pie: {
                            tooltip: { //提示框组件。
                                trigger: 'item',
                                formatter: function (param) {
                                    return param.data.name + '<br/>数量：' + param.data.value + '<br/> 占比：' + param.percent.toFixed(2) + '%';
                                }
                            },
                        }
                    };
                    var options1 = {
                        type: 'post',
                        url: 'elasticsearch/supply/logistics_statistic/ajaxGetLogistics',
                        data: {
                            'time': $('#workload_time').val(),
                            'platform': $('#order_platform_workload').val(),
                            'type' : 1
                        }
                    }               
                    EchartObj.api.ajax(options1, chartOptions1);
                },
                delieved_num_echart:function(){
                    var chartOptions3 = {
                        targetId: 'echart3',
                        downLoadTitle: '图表',
                        type: 'pie',
                        pie: {
                            tooltip: { //提示框组件。
                                trigger: 'item',
                                formatter: function (param) {
                                    return param.data.name + '<br/>比率：' + param.data.value + '<br/> 占比：' + param.percent.toFixed(2) + '%';
                                }
                            },
                        }               
                    };
                    var options3 = {
                        type: 'post',
                        url: 'elasticsearch/supply/logistics_statistic/ajaxGetLogistics',
                        data: {
                            'time': $('#workload_time').val(),
                            'platform': $('#order_platform_workload').val(),
                            'type' : 2
                        }
                    }                 
                    
                    EchartObj.api.ajax(options3, chartOptions3);
                }
            },
            bindevent: function () {
                Form.api.bindevent($("form[role=form]"));
            }
        }
    };
    return Controller;
});
//获取表格中的数据
function getTableData(){
    var create_time = $('#workload_time').val();
    var platform    = $('#order_platform_workload').val();
    Backend.api.ajax({
        url:'elasticsearch/supply/logistics_statistic/ajaxGetLogistics',
        data:{time:create_time,platform:platform}
    }, function(data, ret){
        $("#workload-info tr").remove();
        console.log(ret.data);
        var str = '<tr>';
        str+='<th style="text-align: center; vertical-align: middle;">物流渠道</th>';
        str+='<th style="text-align: center; vertical-align: middle;">发货数量</th>';
        str+='<th style="text-align: center; vertical-align: middle;">妥投订单数</th>';
        str+='<th style="text-align: center; vertical-align: middle;">总妥投率</th>';
        str+='<th style="text-align: center; vertical-align: middle;">7天妥投率</th>';
        str+='<th style="text-align: center; vertical-align: middle;">10天妥投率</th>';
        str+='<th style="text-align: center; vertical-align: middle;">14天妥投率</th>';
        str+='<th style="text-align: center; vertical-align: middle;">20天妥投率</th>';
        str+='<th style="text-align: center; vertical-align: middle;">20天以上妥投率</th>';
        str+='<th style="text-align: center; vertical-align: middle;">平均妥投时效</th>';
        str+='</tr>';
        for(var i=0;i<ret.data.length;i++){
            str+='<tr>'+
            '<td style="text-align: center; vertical-align: middle;">'+ret.data[i].shipment_data_type+'</td>'+
            '<td style="text-align: center; vertical-align: middle;">'+ret.data[i].send_order_num+'</td>'+
            '<td style="text-align: center; vertical-align: middle;">'+ret.data[i].deliverd_order_num+'</td>'+
            '<td style="text-align: center; vertical-align: middle;">'+ret.data[i].total_deliverd_rate+'%</td>'+
            '<td style="text-align: center; vertical-align: middle;">'+ret.data[i].serven_deliverd_rate+'%</td>'+
            '<td style="text-align: center; vertical-align: middle;">'+ret.data[i].ten_deliverd_rate+'%</td>'+
            '<td style="text-align: center; vertical-align: middle;">'+ret.data[i].fourteen_deliverd_rate+'%</td>'+
            '<td style="text-align: center; vertical-align: middle;">'+ret.data[i].twenty_deliverd_rate+'%</td>'+
            '<td style="text-align: center; vertical-align: middle;">'+ret.data[i].gtTwenty_deliverd_rate+'%</td>'+
            '<td style="text-align: center; vertical-align: middle;">'+ret.data[i].avg_deliverd_rate+'天</td>'+
            '<tr/>';
        }
        $("#workload-info").append(str);                       
        return false;
    }, function(data, ret){
        Layer.alert(ret.msg);
        return false;
    });   
}
//js保留两位小数，不四舍五入
function formatDecimal(num, decimal) {
    num = num.toString()
    let index = num.indexOf('.')
    if (index !== -1) {
      num = num.substring(0, decimal + index + 1)
    } else {
      num = num.substring(0)
    }
    return parseFloat(num).toFixed(decimal)
  }