define(['jquery', 'bootstrap', 'backend', 'addtabs', 'table', 'form', 'echartsobj', 'echarts', 'echarts-theme', 'template', 'custom-css'], function ($, undefined, Backend, Datatable, Table, Form, EchartObj, undefined, Template) {

    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                extend: {
                    index_url: 'operatedatacenter/userdata/user_data_view_vip/index' + location.search,
                    add_url: 'operatedatacenter/userdata/user_data_view_vip/add',
                    edit_url: 'operatedatacenter/userdata/user_data_view_vip/edit',
                    del_url: 'operatedatacenter/userdata/user_data_view_vip/del',
                    multi_url: 'operatedatacenter/userdata/user_data_view_vip/multi',
                    table: 'user_data_view_vip',
                }
            });
            Controller.api.formatter.daterangepicker($("div[role=form]"));
            //订单数据概况折线图
            Controller.api.formatter.user_chart();
            
            $("#sku_submit").click(function () {
                order_data_view();
                Controller.api.formatter.user_chart();
            
            });
            $("#sku_reset").click(function () {
                $("#order_platform").val(1);
                $("#time_str").val('');
                $("#time_str2").val('');
            });
            
            var table = $("#table");

            // 初始化表格
            table.bootstrapTable({
                url: $.fn.bootstrapTable.defaults.extend.index_url,
                pk: 'id',
                sortName: 'id',
                search: false,//通用搜索
                commonSearch: false,
                showToggle: false,
                showColumns: false,
                showExport: false,
                columns: [
                    [
                        {field: 'id', title: __('用户ID')},
                        {field: 'email', title: __('注册邮箱')},
                        {field: 'start_time', title: __('VIP开始时间')},
                        {field: 'end_time', title: __('VIP结束时间')},
                        {field: 'rest_days', title: __('VIP剩余天数')},
                        {field: 'vip_order_num', title: __('VIP期间订单数')},
                        {field: 'vip_order_amount', title: __('VIP期间订单金额')},
                        {field: 'avg_order_amount', title: __('平均订单金额')},
                        {field: 'order_num', title: __('总订单数')},
                    ]
                ]
            });

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
                user_chart: function () {
                    //活跃用户数折线图
                    var chartOptions = {
                        targetId: 'echart1',
                        downLoadTitle: '图表',
                        type: 'line'
                    };

                    var options = {
                        type: 'post',
                        url: 'operatedatacenter/dataview/dash_board/active_user_trend',
                        data: {
                            order_platform: $("#order_platform").val(),
                            time_str: $("#time_str").val(),
                            type: $("#type").val()
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

function order_data_view() {
    var order_platform = $('#order_platform').val();
    var time_str = $('#time_str').val();
    var time_str2 = $('#time_str2').val();
    Backend.api.ajax({
        url: 'operatedatacenter/dataview/dash_board/ajax_top_data',
        data: {order_platform: order_platform, time_str: time_str}
    }, function (data, ret) {
        var active_user_num = ret.data.active_user_num;
        var register_user_num = ret.data.register_user_num;
        var again_user_num = ret.data.again_user_num;
        $('#order_num').text(order_num.order_num);
        if (parseInt(order_num.same_order_num) < 0) {
            $('#same_order_num').html("<img src='/shangzhang.png'>" + order_num.same_order_num + '%');
        } else {
            $('#same_order_num').html("<img  style='transform:rotate(180deg);' src='/shangzhang.png'>" + order_num.same_order_num + '%');
        }
        
        
        $('#active_user_num').text(active_user_num.active_user_num);
        // $('#same_active_user_num').text(active_user_num.same_active_user_num);
        if (parseInt(active_user_num.same_active_user_num) < 0) {
            $('#same_active_user_num').html("<img src='/xiadie.png'>" + active_user_num.same_active_user_num);
        } else {
            $('#same_active_user_num').html("<img  style='transform:rotate(180deg);' src='/shangzhang.png'>" + active_user_num.same_active_user_num);
        }
        // $('#huan_active_user_num').text(active_user_num.huan_active_user_num);
        if (parseInt(active_user_num.huan_active_user_num) < 0) {
            $('#huan_active_user_num').html("<img src='/xiadie.png'>" + active_user_num.huan_active_user_num);
        } else {
            $('#huan_active_user_num').html("<img  style='transform:rotate(180deg);' src='/shangzhang.png'>" + active_user_num.huan_active_user_num);
        }

        $('#register_user_num').text(register_user_num.register_user_num);
        // $('#same_register_user_num').text(register_user_num.same_register_user_num);
        if (parseInt(register_user_num.same_register_user_num) < 0) {
            $('#same_register_user_num').html("<img src='/xiadie.png'>" + register_user_num.same_register_user_num);
        } else {
            $('#same_register_user_num').html("<img  style='transform:rotate(180deg);' src='/shangzhang.png'>" + register_user_num.same_register_user_num);
        }
        // $('#huan_register_user_num').text(register_user_num.huan_register_user_num);
        if (parseInt(register_user_num.huan_register_user_num) < 0) {
            $('#huan_register_user_num').html("<img src='/xiadie.png'>" + register_user_num.huan_register_user_num);
        } else {
            $('#huan_register_user_num').html("<img  style='transform:rotate(180deg);' src='/shangzhang.png'>" + register_user_num.huan_register_user_num);
        }

        $('#again_user_num').text(again_user_num.again_user_num);
        // $('#same_again_user_num').text(again_user_num.same_again_user_num);
        if (parseInt(again_user_num.same_again_user_num) < 0) {
            $('#same_again_user_num').html("<img src='/xiadie.png'>" + again_user_num.same_again_user_num);
        } else {
            $('#same_again_user_num').html("<img  style='transform:rotate(180deg);' src='/shangzhang.png'>" + again_user_num.same_again_user_num);
        }
        // $('#huan_again_user_num').text(again_user_num.huan_again_user_num);
        if (parseInt(again_user_num.huan_again_user_num) < 0) {
            $('#huan_again_user_num').html("<img src='/xiadie.png'>" + again_user_num.huan_again_user_num);
        } else {
            $('#huan_again_user_num').html("<img  style='transform:rotate(180deg);' src='/shangzhang.png'>" + again_user_num.huan_again_user_num);
        }

        return false;
    }, function (data, ret) {
        Layer.alert(ret.msg);
        return false;
    });
}