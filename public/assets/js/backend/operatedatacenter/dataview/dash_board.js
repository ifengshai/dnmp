define(['jquery', 'bootstrap', 'backend', 'addtabs', 'table', 'form', 'echartsobj', 'echarts', 'echarts-theme', 'template', 'custom-css'], function ($, undefined, Backend, Datatable, Table, Form, EchartObj, undefined, Template) {

    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                extend: {
                    index_url: 'operatedatacenter/dataview/dash_board/index' + location.search,
                    add_url: 'operatedatacenter/dataview/dash_board/add',
                    edit_url: 'operatedatacenter/dataview/dash_board/edit',
                    del_url: 'operatedatacenter/dataview/dash_board/del',
                    multi_url: 'operatedatacenter/dataview/dash_board/multi',
                    table: 'dash_board',
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
                        {checkbox: true},
                        {field: 'id', title: __('Id')},
                        {field: 'operate', title: __('Operate'), table: table, events: Table.api.events.operate, formatter: Table.api.formatter.operate}
                    ]
                ]
            });
//工作概况中的站点选择
            $(document).on('click', '.plat_form', function () {
                var platform = $(this).data('value');
                $(".plat_form").removeClass('active');
                $(this).addClass('active');
                Backend.api.ajax({
                    url: 'datacenter/customer_service/workorder_situation',
                    data: { platform: platform }
                }, function (data, ret) {
                    var today = ret.data.today;
                    var yesterday = ret.data.yesterday;
                    var seven = ret.data.seven;
                    var thirty = ret.data.thirty;
                    var nowmonth = ret.data.nowmonth;
                    var premonth = ret.data.premonth;
                    var year = ret.data.year;
                    var total = ret.data.total;
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
                }, function (data, ret) {
                    Layer.alert(ret.msg);
                    return false;
                });
            });

            // 指定图表的配置项和数据
            Controller.api.formatter.daterangepicker($("form[role=form2]"));
            Controller.api.formatter.daterangepicker($("div[role=form8]"));

            //获取工作量概况数据
            $(document).on('click', '.plat_form1', function () {
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
            $("#workload_time").on("cancel.daterangepicker",function(){
                setTimeout(()=>{
                    worknum_situation();
                    Controller.api.formatter.line_chart();
                },0)
            })
            $(document).on('click','.title_click',function(){
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
            $(document).on('click', '#question_type_submit', function () {
                Controller.api.formatter.pie_chart('echart1', $("#plat_form2").val(), $("#create_time_one").val());
            });
            $(document).on('click', '#question_type_reset', function () {
                $("#plat_form2").val(1)
                $("#create_time_one").val('')
            });
            Controller.api.formatter.pie_chart('echart1', $("#plat_form2").val(), $("#create_time_one").val());
            //工单处理措施统计
            Controller.api.formatter.daterangepicker($("form[role=form5]"));
            Controller.api.formatter.daterangepicker($("div[role=form6]"));
            $(document).on('click', '#question_type_submit1', function () {
                Controller.api.formatter.pie_chart('echart3', $("#plat_form3").val(), $("#create_time_two").val());
            });
            $(document).on('click', '#question_type_reset1', function () {
                $("#plat_form3").val(1)
                $("#create_time_two").val('')
            });
            Controller.api.formatter.pie_chart('echart3', $("#plat_form3").val(), $("#create_time_two").val());
            // 为表格绑定事件
            Table.api.bindevent(table);
        },
        add: function () {
            Controller.api.bindevent();
        },
        edit: function () {
            Controller.api.bindevent();
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
                                timePicker: true,
                                timePickerIncrement: 1
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
                line_chart: function () {
                    //工单量概况折线图
                    var chartOptions = {
                        targetId: 'echart1',
                        downLoadTitle: '图表',
                        type: 'line'
                    };

                    var options = {
                        type: 'post',
                        url: 'datacenter/customer_service/worknum_line',
                        data: {
                            platform: $("#web_platform").val(),
                            workload_time: $("#workload_time").val(),
                            title_type: $("#title_type").val()
                        }
                    }
                    EchartObj.api.ajax(options, chartOptions)
                },
                workload_line_chart: function(){
                    //工作量概况折线图
                    var chartOptions = {
                        targetId: 'worknum_echart',
                        downLoadTitle: '图表',
                        type: 'line'
                    };

                    var options = {
                        type: 'post',
                        url: 'datacenter/customer_service/dealnum_line',
                        data: {
                            platform:$("#order_platform").val(),
                            time_str:$("#one_time").val(),
                            group_id:$("#customer_type").val()
                        }
                    }
                    EchartObj.api.ajax(options, chartOptions)
                }
            },
            bindevent: function () {
                Form.api.bindevent($("form[role=form]"));
            }
        },
    };
    return Controller;
});