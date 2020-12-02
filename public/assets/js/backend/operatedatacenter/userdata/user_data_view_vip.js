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
            order_data_view();
            $("#sku_submit").click(function () {
                var time_str = $('#time_str').val();
                var time_str2 = $('#time_str2').val();
                if(time_str.length == 0 && time_str2.length > 0){
                    Layer.alert('请选择时间');
                    return false;
                }
                order_data_view();
            });
            $("#sku_reset").click(function () {
                $("#order_platform").val(1);
                $("#time_str").val('');
                $("#time_str2").val('');
                order_data_view();
            });
            $("#export").click(function(){
                var order_platform = $('#order_platform').val();
                window.location.href=Config.moduleurl+'/operatedatacenter/userdata/user_data_view_vip/export?order_platform='+order_platform;
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
                        {field: 'customer_id', title: __('用户ID')},
                        {field: 'customer_email', title: __('注册邮箱')},
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
        url: 'operatedatacenter/userdata/user_data_view_vip/ajax_top_data',
        data: {order_platform: order_platform, time_str: time_str, time_str2: time_str2}
    }, function (data, ret) {
        var vip_num = ret.data.vip_num;
        var again_user_num = ret.data.again_user_num;
        var sum_vip_num = ret.data.sum_vip_num;
        $("#vip_user_num").html(vip_num.vip_user_num);
        $("#again_user_num").html(again_user_num.again_user_num);
        $("#sum_vip_num").html(sum_vip_num);
        var str1 = '';
        if(vip_num.contrast_vip_user_num){
            str1 += '<div class="rate_class"><span>';
            if(vip_num.contrast_vip_user_num < 0){
                str1 += '<img src="/xiadie.png">';
            }else{
                str1 += '<img style="transform:rotate(180deg);"  src="/shangzhang.png">';
            }
            str1 += vip_num.contrast_vip_user_num+'%</span></div>';   
            $("#contrast_active_user_num").html(str1);               
        }else{
            $("#contrast_active_user_num").html(''); 
        }
        var str2 = '';
        if(again_user_num.contrast_again_user_num){
            str2 += '<div class="rate_class"><span>';
            if(again_user_num.contrast_again_user_num < 0){
                str2 += '<img src="/xiadie.png">';
            }else{
                str2 += '<img style="transform:rotate(180deg);"  src="/shangzhang.png">';
            }              
            str2 += again_user_num.contrast_again_user_num+'%</span></div>'; 
            $("#contrast_again_user_num").html(str2);           
        }else{
            $("#contrast_again_user_num").html('');    
        }

        return false;
    }, function (data, ret) {
        Layer.alert(ret.msg);
        return false;
    });
}