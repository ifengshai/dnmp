define(['jquery', 'bootstrap', 'backend', 'addtabs', 'table','form','echartsobj', 'echarts', 'echarts-theme', 'template','custom-css'], function ($, undefined, Backend, Datatable, Table,Form, EchartObj, undefined, Template) {

    var Controller = {
        index: function () {
            // 指定图表的配置项和数据
            Controller.api.formatter.daterangepicker($("form[role=form1]"));
            Controller.api.formatter.daterangepicker($("div[role=form8]"));
            //Form.api.bindevent($("div[role=form8]"));
            //Form.api.bindevent($("form[role=form]"));
            //显示中间个饼图
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
            var chartOptions3 = {
                targetId: 'echart3',
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
            var time_one = $('#create_time_one').val() ? $('#create_time_one').val() :'';
            //console.log(time_one);
            var time_two = $('#create_time_two').val() ? $('#create_time_two').val() :'';
            //console.log(time_two);
            var platform = 1;
            var options1 = {
                type: 'post',
                url: 'datacenter/customer_service/detail',
                data: {
                    'time': time_one,
                    'platform': platform,
                    'key':'echart1' 
                }
            }             
            var options3 = {
                type: 'post',
                url: 'datacenter/customer_service/detail',
                data: {
                    'time': time_two,
                    'platform': platform,
                    'key':'echart3' 
                }
            }
            EchartObj.api.ajax(options1, chartOptions1);
            EchartObj.api.ajax(options3, chartOptions3);
                        //点击重置按钮
            $(document).on('click', '.btn-workload-time', function () {
                $('#workload_time').val('');
                // $('#customer_email').attr({ "value": "" });
                // $('#customer_name').attr({ "value": "" });
                // $('#customer_phone').attr({ "value": "" });
                // $('#track_number').attr({ "value": "" });
            });
            $(document).on('click','.btn-create_time_one',function(){
                $('#create_time_one').val('');
            });
            $(document).on('click','.btn-create_time_two',function(){
                $("#create_time_two").val('');
            });
            $(document).on('click','.btn-create_time_workorder',function(){
                $("#create_time_workorder").val('');
            });
            $(document).on('click','.btn-create_time_warehouse',function(){
                $("#create_time_warehouse").val('');
            });
            //首页概况统计
            $(document).on('click','#workload-btn',function(){
                var create_time = $('#workload_time').val();
                var platform    = $('#order_platform_workload').val();
                if (!create_time) {
                    Toastr.error('请先选择时间范围');
                    return false;
                }
                Backend.api.ajax({
                    url:'datacenter/customer_service/workload_general',
                    data:{time:create_time,platform:platform}
                }, function(data, ret){
                    var today = ret.data.todayData;
                    var yesterday  = ret.data.yesterdayData;
                    var serven     = ret.data.servenData;
                    var third      = ret.data.thirdData;
                    var starttime  = ret.data.start;
                    var endtime    = ret.data.end;
                    var info       = ret.data.info;
                    $('#today_wait_num').text(today.wait_num);
                    $('#today_increment_num').text(today.increment_num);
                    $('#today_reply_num').text(today.reply_num);
                    $('#today_waiting_num').text(today.waiting_num);
                    $('#today_pending_num').text(today.pending_num);

                    $('#yesterday_wait_num').text(yesterday.wait_num);
                    $('#yesterday_increment_num').text(yesterday.increment_num);
                    $('#yesterday_reply_num').text(yesterday.reply_num);
                    $('#yesterday_waiting_num').text(yesterday.waiting_num);
                    $('#yesterday_pending_num').text(yesterday.pending_num);

                    $('#serven_wait_num').text(serven.wait_num);
                    $('#serven_increment_num').text(serven.increment_num);
                    $('#serven_reply_num').text(serven.reply_num);
                    $('#serven_waiting_num').text(serven.waiting_num);
                    $('#serven_pending_num').text(serven.pending_num);

                    $('#third_wait_num').text(third.wait_num);
                    $('#third_increment_num').text(third.increment_num);
                    $('#third_reply_num').text(third.reply_num);
                    $('#third_waiting_num').text(third.waiting_num);
                    $('#third_pending_num').text(third.pending_num);
                    var tr = '<tr id="new_tr">';
                        tr += '<td style="text-align: center; vertical-align: middle;">'+starttime+':'+endtime+'</td>';
                        tr += '<td style="text-align: center; vertical-align: middle;">'+info.wait_num+'</td>';
                        tr += '<td style="text-align: center; vertical-align: middle;">'+info.increment_num+'</td>';
                        tr += '<td style="text-align: center; vertical-align: middle;">'+info.reply_num+'</td>';
                        tr += '<td style="text-align: center; vertical-align: middle;">'+info.waiting_num+'</td>';
                        tr += '<td style="text-align: center; vertical-align: middle;">'+info.pending_num+'</td>';
                        tr+='</tr>';      
                    $("#workload-info").append(tr);                       
                    return false;
                }, function(data, ret){
                    Layer.alert(ret.msg);
                    return false;
                });                 

            });
            //首页工单问题类型统计
            $(document).on('click', '.echart1-btn', function () {
                var create_time = $('#create_time_one').val();
                console.log(create_time);
                if (!create_time) {
                    Toastr.error('请先选择时间范围');
                    return false;
                }
                var chartOptions3 = {
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
                var platform = $('#order_platform_one').val();                
                var options3 = {
                    type: 'post',
                    url: 'datacenter/customer_service/detail',
                    data: {
                        'time': create_time,
                        'platform': platform,
                        'key':'echart1' 
                    }
                }                
                EchartObj.api.ajax(options3, chartOptions3);
            });
            //首页工单处理措施统计
            $(document).on('click', '.echart3-btn', function () {
                var create_time = $('#create_time_two').val();
                if (!create_time) {
                    Toastr.error('请先选择时间范围');
                    return false;
                }
                var chartOptions3 = {
                    targetId: 'echart3',
                    downLoadTitle: '图表',
                    type: 'pie',               
                };
                var platform = $('#order_platform_two').val();               
                var options3 = {
                    type: 'post',
                    url: 'datacenter/customer_service/detail',
                    data: {
                        'time': create_time,
                        'platform': platform,
                        'key':'echart3' 
                    }
                }                
                EchartObj.api.ajax(options3, chartOptions3);
            });
            //首页工单处理概况
            $(document).on('click', '.workorder-btn', function () {
                var create_time = $('#create_time_workorder').val();
                if (!create_time) {
                    Toastr.error('请先选择时间范围');
                    return false;
                }
                Backend.api.ajax({
                    url:'datacenter/customer_service/workorder_general',
                    data:{time:create_time}
                }, function(data, ret){
                    $("#workorder-left tr").remove();
                    $("#workorder-right tr").remove();
                    var examineArr = ret.data.examineArr;
                    var step       = ret.data.step;
                    var workorder_handle_left_data = ret.data.workorder_handle_left_data;
                    var workorder_handle_right_data = ret.data.workorder_handle_right_data;
                    //工单处理概况左边数据 start
                    var left_tr = '<tr>';
                        left_tr+='<th style="text-align: center; vertical-align: middle;">措施</th>';
                    for(let key  in examineArr){
                        left_tr+='<th style="text-align: center; vertical-align: middle;">'+examineArr[key]+'</th>';
                    }
                        left_tr+='</tr>';
                        left_tr+='<tr>';
                        left_tr+='<td style="text-align: center; vertical-align: middle;">未超时未审批</td>';
                    for(let key in examineArr){
                        if(workorder_handle_left_data != false){
                            left_tr+='<td style="text-align: center; vertical-align: middle;">'+workorder_handle_left_data[key].no_time_out_check+'</td>';
                        }else{
                            left_tr+='<td style="text-align: center; vertical-align: middle;">0</td>';
                        }     
                    }
                        left_tr+='</tr>';
                        left_tr+='<tr>';
                        left_tr+='<td style="text-align: center; vertical-align: middle;">未超时已审批</td>';
                    for(let key in examineArr){
                        if(workorder_handle_left_data != false){
                            left_tr+='<td style="text-align: center; vertical-align: middle;">'+workorder_handle_left_data[key].no_time_out_checked+'</td>';
                        }else{
                            left_tr+='<td style="text-align: center; vertical-align: middle;">0</td>';
                        }     
                    }
                        left_tr+='</tr>';
                        left_tr+='<tr>';
                        left_tr+='<td style="text-align: center; vertical-align: middle;">超时未审批</td>';
                    for(let key in examineArr){
                        if(workorder_handle_left_data != false){
                            left_tr+='<td style="text-align: center; vertical-align: middle;">'+workorder_handle_left_data[key].time_out_check+'</td>';
                        }else{
                            left_tr+='<td style="text-align: center; vertical-align: middle;">0</td>';
                        }     
                    }
                        left_tr+='</tr>';
                        left_tr+='<tr>';
                        left_tr+='<td style="text-align: center; vertical-align: middle;">超时已审批</td>';
                    for(let key in examineArr){
                        if(workorder_handle_left_data != false){
                            left_tr+='<td style="text-align: center; vertical-align: middle;">'+workorder_handle_left_data[key].time_out_checked+'</td>';
                        }else{
                            left_tr+='<td style="text-align: center; vertical-align: middle;">0</td>';
                        }     
                    }
                        left_tr+='</tr>';
                    $("#workorder-left").append(left_tr);     
                    //工单处理概况左边数据 end
                    //工单处理概况右边数据 start
                        var right_tr ='<tr>';
                            right_tr+='<th style="text-align: center; vertical-align: middle;">措施</th>';
                            right_tr+='<th style="text-align: center; vertical-align: middle;">未超时未处理</th>';
                            right_tr+='<th style="text-align: center; vertical-align: middle;">未超时已处理</th>';
                            right_tr+='<th style="text-align: center; vertical-align: middle;">超时未处理</th>';
                            right_tr+='<th style="text-align: center; vertical-align: middle;">超时已处理</th>';
                            right_tr+='</tr>';
                    for(let key in step){
                        if(workorder_handle_right_data != false){
                            right_tr+='<tr>'+
                            '<td style="text-align: center; vertical-align: middle;">'+step[key]+'</td>'+
                            '<td style="text-align: center; vertical-align: middle;">'+workorder_handle_right_data[key].no_time_out_handle+'</td>'+
                            '<td style="text-align: center; vertical-align: middle;">'+workorder_handle_right_data[key].no_time_out_handled+'</td>'+
                            '<td style="text-align: center; vertical-align: middle;">'+workorder_handle_right_data[key].time_out_handle+'</td>'+
                            '<td style="text-align: center; vertical-align: middle;">'+workorder_handle_right_data[key].time_out_handled+'</td>'+
                            '</tr>';
                        }else{
                            right_tr+='<tr>'+
                            '<td style="text-align: center; vertical-align: middle;">'+step[key]+'</td>'+
                            '<td style="text-align: center; vertical-align: middle;">0</td>'+
                            '<td style="text-align: center; vertical-align: middle;">0</td>'+
                            '<td style="text-align: center; vertical-align: middle;">0</td>'+
                            '<td style="text-align: center; vertical-align: middle;">0</td>'+
                            '</tr>';
                        }
                    }      
                    //工单处理概况右边数据 end     
                    $("#workorder-right").append(right_tr);    
                    return false;
                }, function(data, ret){
                    Layer.alert(ret.msg);
                    return false;
                });
            });
            //首页跟单处理概况
            $(document).on('click', '.warehouse-btn', function () {
                var create_time = $('#create_time_warehouse').val();
                if (!create_time) {
                    Toastr.error('请先选择时间范围');
                    return false;
                }
                Backend.api.ajax({
                    url:'datacenter/customer_service/warehouse_general',
                    data:{time:create_time}
                }, function(data, ret){
                    var warehouse_data = ret.data.warehouse_data;
                    var warehouse_problem_type = ret.data.warehouse_problem_type;
                    $("#warehouse tr").remove();
                    var tr ='<tr>';
                        tr+='<th style="text-align: center; vertical-align: middle;">跟单概况</th>';
                        tr+='<th style="text-align: center; vertical-align: middle;">未超时未处理</th>';
                        tr+='<th style="text-align: center; vertical-align: middle;">未超时已处理</th>';
                        tr+='<th style="text-align: center; vertical-align: middle;">超时未处理</th>';
                        tr+='<th style="text-align: center; vertical-align: middle;">超时已处理</th>';
                        tr+='</tr>';
                    for(let key in warehouse_problem_type){
                        if(warehouse_data != false){
                            tr+='<tr>'+
                            '<td style="text-align: center; vertical-align: middle;">'+warehouse_problem_type[key]+'</td>'+
                            '<td style="text-align: center; vertical-align: middle;">'+warehouse_data[key].no_time_out_handle+'</td>'+
                            '<td style="text-align: center; vertical-align: middle;">'+warehouse_data[key].no_time_out_handled+'</td>'+
                            '<td style="text-align: center; vertical-align: middle;">'+warehouse_data[key].time_out_handle+'</td>'+
                            '<td style="text-align: center; vertical-align: middle;">'+warehouse_data[key].time_out_handled+'</td>'+
                            '</tr>';
                        }else{
                            tr+='<tr>'+
                            '<td style="text-align: center; vertical-align: middle;">'+warehouse_problem_type[key]+'</td>'+
                            '<td style="text-align: center; vertical-align: middle;">0</td>'+
                            '<td style="text-align: center; vertical-align: middle;">0</td>'+
                            '<td style="text-align: center; vertical-align: middle;">0</td>'+
                            '<td style="text-align: center; vertical-align: middle;">0</td>'+
                            '</tr>';
                        }
                    }      
                    //工单处理概况右边数据 end     
                    $("#warehouse").append(tr);                       
                    return false;
                }, function(data, ret){
                    Layer.alert(ret.msg);
                    return false;
                });                
            });                                                 
        },
        customer_data_screen: function () {
            //工作概况中的站点选择
            $(document).on('click','.plat_form',function(){
                var platform = $(this).data('value');
                $(".plat_form").removeClass('active');
                $(this).addClass('active');
                Backend.api.ajax({
                    url:'datacenter/customer_service/workorder_situation',
                    data:{platform:platform}
                }, function(data, ret){
                    var today = ret.data.today;
                    var yesterday  = ret.data.yesterday;
                    var seven     = ret.data.seven;
                    var thirty      = ret.data.thirty;
                    var nowmonth  = ret.data.nowmonth;
                    var premonth    = ret.data.premonth;
                    var year       = ret.data.year;
                    var total       = ret.data.total;
                    $('#today_wo_num').text(today.wo_num);
                    $('#today_wo_complete_num').text(today.wo_complete_num);
                    $('#today_wo_bufa_percent').text(today.wo_bufa_percent);
                    $('#today_wo_refund_percent').text(today.wo_refund_percent);
                    $('#today_wo_refund_money_percent').text(today.wo_refund_money_percent);

                    $('#yesterday_wo_num').text(yesterday.wo_num);
                    $('#yesterday_wo_complete_num').text(yesterday.wo_complete_num);
                    $('#yesterday_wo_bufa_percent').text(yesterday.wo_bufa_percent);
                    $('#yesterday_wo_refund_percent').text(yesterday.wo_refund_percent);
                    $('#yesterday_wo_refund_money_percent').text(yesterday.wo_refund_money_percent);

                    $('#seven_wo_num').text(seven.wo_num);
                    $('#seven_wo_complete_num').text(seven.wo_complete_num);
                    $('#seven_wo_bufa_percent').text(seven.wo_bufa_percent);
                    $('#seven_wo_refund_percent').text(seven.wo_refund_percent);
                    $('#seven_wo_refund_money_percent').text(seven.wo_refund_money_percent);

                    $('#thirty_wo_num').text(thirty.wo_num);
                    $('#thirty_wo_complete_num').text(thirty.wo_complete_num);
                    $('#thirty_wo_bufa_percent').text(thirty.wo_bufa_percent);
                    $('#thirty_wo_refund_percent').text(thirty.wo_refund_percent);
                    $('#thirty_wo_refund_money_percent').text(thirty.wo_refund_money_percent);

                    $('#nowmonth_wo_num').text(nowmonth.wo_num);
                    $('#nowmonth_wo_complete_num').text(nowmonth.wo_complete_num);
                    $('#nowmonth_wo_bufa_percent').text(nowmonth.wo_bufa_percent);
                    $('#nowmonth_wo_refund_percent').text(nowmonth.wo_refund_percent);
                    $('#nowmonth_wo_refund_money_percent').text(nowmonth.wo_refund_money_percent);


                    $('#premonth_wo_num').text(premonth.wo_num);
                    $('#premonth_wo_complete_num').text(premonth.wo_complete_num);
                    $('#premonth_wo_bufa_percent').text(premonth.wo_bufa_percent);
                    $('#premonth_wo_refund_percent').text(premonth.wo_refund_percent);
                    $('#premonth_wo_refund_money_percent').text(premonth.wo_refund_money_percent);


                    $('#year_wo_num').text(year.wo_num);
                    $('#year_wo_complete_num').text(year.wo_complete_num);
                    $('#year_wo_bufa_percent').text(year.wo_bufa_percent);
                    $('#year_wo_refund_percent').text(year.wo_refund_percent);
                    $('#year_wo_refund_money_percent').text(year.wo_refund_money_percent);


                    $('#total_wo_num').text(total.wo_num);
                    $('#total_wo_complete_num').text(total.wo_complete_num);
                    $('#total_wo_bufa_percent').text(total.wo_bufa_percent);
                    $('#total_wo_refund_percent').text(total.wo_refund_percent);
                    $('#total_wo_refund_money_percent').text(total.wo_refund_money_percent);
                                        
                    return true;
                }, function(data, ret){
                    Layer.alert(ret.msg);
                    return false;
                });   
            });

            // 指定图表的配置项和数据
            Controller.api.formatter.daterangepicker($("form[role=form2]"));
            Controller.api.formatter.daterangepicker($("div[role=form8]"));
           
            //获取工作量概况数据
            $(document).on('click','.plat_form1',function(){
                $("#web_platform").val($(this).data('value'));
                $(".plat_form1").removeClass('active');
                $(this).addClass('active');
                worknum_situation();
                Controller.api.formatter.line_chart();
            });
            $("#workload_time").on("apply.daterangepicker",function(){
                setTimeout(()=>{
                    worknum_situation();
                    Controller.api.formatter.line_chart();
                },0)
            })
            $(document).on('click','.title',function(){
                if($(this).data('value')){
                    $("#title_type").val($(this).data('value'));
                    $(".title").removeClass('active');
                    $(this).addClass('active');
                }
                worknum_situation();
                //工单量概况折线图
                Controller.api.formatter.line_chart();
            });
            
            //工单量概况折线图
            Controller.api.formatter.line_chart();
            // 基于准备好的dom，初始化echarts实例
            //工单问题类型统计
            Controller.api.formatter.daterangepicker($("form[role=form3]"));
            Controller.api.formatter.daterangepicker($("div[role=form1]"));
            $(document).on('click','#question_type_submit',function(){
                Controller.api.formatter.pie_chart('echart1',$("#plat_form2").val(),$("#create_time_one").val());
            });
            $(document).on('click','#question_type_reset',function(){
                $("#plat_form2").val(1)
                $("#create_time_one").val('')
            });
            Controller.api.formatter.pie_chart('echart1',$("#plat_form2").val(),$("#create_time_one").val());
            //工单处理措施统计
            Controller.api.formatter.daterangepicker($("form[role=form5]"));
            Controller.api.formatter.daterangepicker($("div[role=form6]"));
            $(document).on('click','#question_type_submit1',function(){
                Controller.api.formatter.pie_chart('echart3',$("#plat_form3").val(),$("#create_time_two").val());
            });
            $(document).on('click','#question_type_reset1',function(){
                $("#plat_form3").val(1)
                $("#create_time_two").val('')
            });
            Controller.api.formatter.pie_chart('echart3',$("#plat_form3").val(),$("#create_time_two").val());
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
                pie_chart: function(type,platform,create_time){
                    //工单饼图展示方法
                    var chartOptions1 = {
                        targetId: type,
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
                        url: 'datacenter/customer_service/workorder_question_type',
                        data: {
                            'create_time': create_time,
                            'platform': platform,
                            'key':type 
                        }
                    }             
                    EchartObj.api.ajax(options1, chartOptions1);
                },
                line_chart: function(){
                    //工单量概况折线图
                    var chartOptions = {
                        targetId: 'echart2',
                        downLoadTitle: '图表',
                        type: 'line'
                    };
                    
                    var options = {
                        type: 'post',
                        url: 'datacenter/customer_service/worknum_line',
                        data: {
                            platform:$("#web_platform").val(),
                            workload_time:$("#workload_time").val(),
                            title_type:$("#title_type").val()
                        }
                    }
                    EchartObj.api.ajax(options, chartOptions)
                }
            },
            bindevent: function () {
                Form.api.bindevent($("form[role=form]"));
            }
        },
        //工作量统计
        workload:function()
        {
            Controller.api.formatter.daterangepicker($("form[role=form1]"));
            Form.api.bindevent($("form[role=form]"));
            //点击对比时间段显示第二个时间输入框
            $("#contrast").change(function() { 
                if($("#contrast").is(':checked')){
                    $("#two-time-node").show();
                }else{
                    $("#two-time-node").hide();
                }
            }); 
        },        
        //工单统计
        workstatistics:function()
        {
            Controller.api.formatter.daterangepicker($("form[role=form1]"));
            Form.api.bindevent($("form[role=form]"));
            //点击对比时间段显示第二个时间输入框
            $("#contrast").change(function() { 
                if($("#contrast").is(':checked')){
                    $("#two-time-node").show();
                }else{
                    $("#two-time-node").hide();
                }
            });            
        },
        //工单问题措施详情
        detail:function(){
            Controller.api.formatter.daterangepicker($("form[role=form1]"));
            Form.api.bindevent($("form[role=form]"));
            //显示4个饼图
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
            var chartOptions2 = {
                targetId: 'echart2',
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
            var chartOptions3 = {
                targetId: 'echart3',
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
            var chartOptions4 = {
                targetId: 'echart4',
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
            //第二个饼图点击切换
            $(".statistics").on('click',function(){
                var value = $(this).data("value");
                if(value>0){
                    $(".problem-counter li").siblings('li').removeClass('active');
                    $(this).parent().addClass('active');
                    var time = $('#create_time').val();
                    var platform = $('#c-order_platform').val();  
                    var chartOptions = {
                        targetId: 'echart2',
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
                   //异步获取第二个饼图右边显示的数据
                   Backend.api.ajax({
                        url:'datacenter/customer_service/get_two_pie_data',
                        data:{
                            value:value,
                            time:time,
                            platform:platform
                        }
                    }, function(data, ret){
                    $("#caigou-table2 tr").remove();
                        var table = ret.data;
                        var addtr = '';
                        for(var i=0;i<table.customer_arr.length;i++){
                            addtr+='<tr>'+
                            '<td>'+table.customer_arr[i]+'</td>'+
                            '<td>'+table.problem_data[i]+'</td>'+
                            '<td>占比</td>';
                            if(table.problem_form_total>0){
                                addtr+='<td>'+formatDecimal(table.problem_data[i]/table.problem_form_total*100,2) +'%</td>';
                            }else{
                                addtr+='<td>0</td>';
                            }
                            addtr+='</tr>';                       
                        }
                        $("#caigou-table2").append(addtr);
                    }, function(data, ret){
                        Layer.alert(ret.msg);
                        return false;
                    });                                                            
                }
            });
            //第四个饼图点击一级tab切换二级数据
            $(".statisticsTwo").on('click',function(){
                var value = $(this).data("value");
                if(value>0){
                    $(".step-type li").siblings('li').removeClass('active');
                    $(this).parent().addClass('active');
                    $("#second-level").html('');
                    $("#warehouse").val(value)
                    //异步获取第二个tab数据
                    Backend.api.ajax({
                        url:'datacenter/customer_service/get_problem_by_classify',
                        data:{value:value}
                    }, function(data, ret){
                        console.log(ret.data);
                        var liArr = ret.data;
                        var msg = '';
                        $.each(liArr, function(i, val) {  
                            msg += '<li><a href="javascript:void(0);" data-value="'+i+'" class="statisticsThree">'+val+'</a></li>';
                        });
                        $("#second-level").append(msg);
                        return false;
                    }, function(data, ret){
                        Layer.alert(ret.msg);
                        return false;
                    });                    
                }
            });
            //根据选择的问题类型查找对应的数据
            $(document).on('click', '.statisticsThree', function(e){
                var problem = $("#warehouse").val();
                var value = $(this).data("value");
                if(value>0){
                    $("#second-level li").siblings('li').removeClass('active');
                    $(this).parent().addClass('active');
                    var time = $('#create_time').val();
                    var platform = $('#c-order_platform').val();  
                    var chartOptions = {
                        targetId: 'echart4',
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
                    var options = {
                        type: 'post',
                        url: 'datacenter/customer_service/step',
                        data: {
                            'time': time,
                            'platform': platform,
                            'value':value,
                            'problem':problem
                        }
                   };
                   EchartObj.api.ajax(options, chartOptions);
                   //异步获取第四个饼图右边显示的数据
                   Backend.api.ajax({
                        url:'datacenter/customer_service/get_four_pie_data',
                        data:{
                            value:value,
                            time:time,
                            platform:platform,
                            problem:problem
                        }
                    }, function(data, ret){
                    $("#caigou-table4 tr").remove();
                    var table = ret.data;
                    var addtr = '';
                    for(var i=0;i<table.step.length;i++){
                        addtr+='<tr>'+
                        '<td>'+table.step[i]+'</td>'+
                        '<td>'+table.step_data.step[i]+'</td>'+
                        '<td>占比</td>';
                        if(table.step_four_total>0){
                            addtr+='<td>'+formatDecimal(table.step_data.step[i]/table.step_four_total*100,2) +'%</td>';
                        }else{
                            addtr+='<td>0</td>';
                        }
                        addtr+='</tr>';                       
                    }
                    $("#caigou-table4").append(addtr);
                }, function(data, ret){
                    Layer.alert(ret.msg);
                    return false;
                });                                       
              }
            });            
        },

    };
    return Controller;
});
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
//工作量概况
function worknum_situation(workload_time){
    var platform = $("#web_platform").val();
    var workload_time = $("#workload_time").val();
    var title_type = $("#title_type").val();

    Backend.api.ajax({
        url:'datacenter/customer_service/worknum_situation',
        data:{platform:platform,workload_time:workload_time,title_type:title_type}
    }, function(data, ret){
        var wait_deal_num = ret.data.wait_deal_num;
        var new_create_num  = ret.data.new_create_num;
        var already_reply_num  = ret.data.already_reply_num;
        var wait_allot_num  = ret.data.wait_allot_num;
        var positive_effect_num  = ret.data.positive_effect_num;
        var reply_failure_num  = ret.data.reply_failure_num;
        
        $('#wait_deal_num').text(wait_deal_num);
        $('#new_create_num').text(new_create_num);
        $('#already_reply_num').text(already_reply_num);
        $('#wait_allot_num').text(wait_allot_num);
        $('#positive_effect_num').text(positive_effect_num);
        $('#reply_failure_num').text(reply_failure_num);
        return true;
    }, function(data, ret){
        Layer.alert(ret.msg);
        return false;
    });   
}
