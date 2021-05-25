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
            order_data_view();
            Controller.api.formatter.daterangepicker($("div[role=form]"));
            //订单数据概况折线图
            Controller.api.formatter.line_chart1();
            // Controller.api.formatter.user_chart();
            Controller.api.formatter.user_change_chart();
            customer_num();
            re_buy_num();
            $("#sku_submit").click(function () {
                var time_str = $("#time_str").val();
                if (time_str.length <= 0) {
                    Layer.alert('请选择时间');
                    return false;
                }
                order_data_view();
                Controller.api.formatter.line_chart1();
                // Controller.api.formatter.user_chart();
                Controller.api.formatter.user_change_chart();
                re_buy_num();
                customer_num();
            });
            $("#sku_reset").click(function () {
                $("#order_platform").val(1);
                $("#time_str").val('');
                $("#compare_time_str").val('');
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
                        {
                            field: 'operate',
                            title: __('Operate'),
                            table: table,
                            events: Table.api.events.operate,
                            formatter: Table.api.formatter.operate
                        }
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
                line_chart1: function () {
                    var chartOptions = {
                        targetId: 'echart1',
                        downLoadTitle: '图表',
                        type: 'bar',
                        bar: {
                            tooltip: { //提示框组件。
                                trigger: 'axis', // 触发类型。可选项item:数据项图形触发，主要在散点图，饼图等无类目轴的图表中使用。axis:坐标轴触发，主要在柱状图，折线图等会使用类目轴的图表中使用。
                                axisPointer: { //坐标轴指示器配置项。
                                    type: 'line' //指示器类型。可选项'line' 直线指示器。'shadow' 阴影指示器。'cross' 十字准星指示器。其实是种简写，表示启用两个正交的轴的 axisPointer。
                                },
                                formatter: function (param) { //格式化提示信息
                                    return param[0].name + '<br/>' + param[0].seriesName + '：' + param[0].value + '<br/>' + param[1].seriesName + '：' + param[1].value;
                                }
                            },
                            grid: { //直角坐标系内绘图网格
                                top: '10%', //grid 组件离容器上侧的距离。
                                left: '5%', //grid 组件离容器左侧的距离。
                                right: '10%', //grid 组件离容器右侧的距离。
                                bottom: '10%', //grid 组件离容器下侧的距离。
                                containLabel: true //grid 区域是否包含坐标轴的刻度标签。
                            },
                            legend: { //图例配置
                                padding: 5,
                                top: '2%',
                                data: ['销售额', '订单数']
                            },
                            xAxis: [
                                {
                                    type: 'category'
                                }
                            ],
                            yAxis: [
                                {
                                    type: 'value',
                                    name: '销售额',
                                    axisLabel: {
                                        formatter: '{value} '
                                    }
                                },
                                {
                                    type: 'value',
                                    name: '订单数',
                                    axisLabel: {
                                        formatter: '{value} '
                                    }
                                }
                            ],
                        }
                    };

                    var options = {
                        type: 'post',
                        url: 'elasticsearch/customer/order/all_data/ajaxGetCustomerOrderData',
                        data: {
                            order_platform: $("#order_platform").val(),
                            time_str: $("#time_str").val(),
                            type: 2
                        }

                    }
                    EchartObj.api.ajax(options, chartOptions)
                },

                user_change_chart: function () {
                    var chartOptions = {
                        targetId: 'echart2',
                        downLoadTitle: '图表',
                        type: 'bar',
                        bar: {
                            tooltip: { //提示框组件。
                                trigger: 'axis', // 触发类型。可选项item:数据项图形触发，主要在散点图，饼图等无类目轴的图表中使用。axis:坐标轴触发，主要在柱状图，折线图等会使用类目轴的图表中使用。
                                axisPointer: { //坐标轴指示器配置项。
                                    type: 'line' //指示器类型。可选项'line' 直线指示器。'shadow' 阴影指示器。'cross' 十字准星指示器。其实是种简写，表示启用两个正交的轴的 axisPointer。
                                },
                                formatter: function (param) { //格式化提示信息
                                    return param[0].name + '<br/>' + param[0].seriesName + '：' + param[0].value + '<br/>' + param[1].seriesName + '：' + param[1].value;
                                }
                            },
                            grid: { //直角坐标系内绘图网格
                                top: '10%', //grid 组件离容器上侧的距离。
                                left: '5%', //grid 组件离容器左侧的距离。
                                right: '10%', //grid 组件离容器右侧的距离。
                                bottom: '10%', //grid 组件离容器下侧的距离。
                                containLabel: true //grid 区域是否包含坐标轴的刻度标签。
                            },
                            legend: { //图例配置
                                padding: 5,
                                top: '2%',
                                data: ['活跃用户数','会话数']
                            },
                            xAxis: [
                                {
                                    type: 'category'
                                }
                            ],
                            yAxis: [
                                {
                                    type: 'value',
                                    name: '活跃用户数',
                                    axisLabel: {
                                        formatter: '{value} '
                                    }
                                },
                                {
                                    type: 'value',
                                    name: '会话数',
                                    axisLabel: {
                                        formatter: '{value} '
                                    }
                                }
                            ],
                        }
                    };

                    var options = {
                        type: 'post',
                        url: 'elasticsearch/customer/order/all_data/ajaxGetCustomerOrderData',
                        data: {
                            order_platform: $("#order_platform").val(),
                            time_str: $("#time_str").val(),
                            type: 1
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
    var compare_time_str = $('#compare_time_str').val();
    Backend.api.ajax({
        url: 'elasticsearch/customer/order/all_data/ajaxGetCustomerOrderData',
        data: {order_platform: order_platform, time_str: time_str, compare_time_str: compare_time_str,type:3}
    }, function (data, ret) {

        var active_user_num = ret.data.activeUserNum;
        var register_user_num = ret.data.registerNum;

        var compare_active_user_num_rate = ret.data.compareActiveUserNumRate;
        var compare_register_user_num_rate = ret.data.compareRegisterNumRate;

        if(compare_time_str.length > 0){
            $('.rate_class').show();
        }
        if(compare_time_str.length <= 0){
            $('.rate_class').hide();
        }


        $('#active_user_num').text(active_user_num);
        if (parseInt(compare_active_user_num_rate) < 0) {
            $('#huan_active_user_num').html("<img src='/xiadie.png'>" + compare_active_user_num_rate + '%');
        } else {
            $('#huan_active_user_num').html("<img  style='transform:rotate(180deg);' src='/shangzhang.png'>" + compare_active_user_num_rate + '%');
        }

        $('#register_user_num').text(register_user_num);
        if (parseInt(compare_register_user_num_rate) < 0) {
            $('#huan_register_user_num').html("<img src='/xiadie.png'>" + compare_register_user_num_rate + '%');
        } else {
            $('#huan_register_user_num').html("<img  style='transform:rotate(180deg);' src='/shangzhang.png'>" + compare_register_user_num_rate + '%');
        }

        return false;
    }, function (data, ret) {
        Layer.alert(ret.msg);
        return false;
    });
}
function re_buy_num() {
    var order_platform = $('#order_platform').val();
    var time_str = $('#time_str').val();
    var compare_time_str = $('#compare_time_str').val();
    Backend.api.ajax({
        url: 'elasticsearch/customer/order/all_data/getReBuyNum',
        data: {order_platform: order_platform, time_str: time_str, compare_time_str: compare_time_str}
    }, function (data, ret) {
        var again_user_num = ret.data.again_user_num;
        var again_user_num_rate = ret.data.again_user_num_rate;
        var compare_again_user_num_rate = ret.data.contrast_again_user_num;
        var compare_again_user_num_rate_all = ret.data.all_contrast_again_user_num;
        $('#again_user_num').text(again_user_num);
        $('#again_user_num_rate').text(again_user_num_rate);
        if (parseInt(compare_again_user_num_rate) < 0) {
            $('#huan_again_user_num').html("<img src='/xiadie.png'>" + compare_again_user_num_rate+ '%');
        } else {
            $('#huan_again_user_num').html("<img  style='transform:rotate(180deg);' src='/shangzhang.png'>" + compare_again_user_num_rate + '%');
        }
        if (parseInt(compare_again_user_num_rate_all) < 0) {
            $('#huan_again_user_num_rate').html("<img src='/xiadie.png'>" + compare_again_user_num_rate_all+ '%');
        } else {
            $('#huan_again_user_num_rate').html("<img  style='transform:rotate(180deg);' src='/shangzhang.png'>" + compare_again_user_num_rate_all + '%');
        }
        return false;
    }, function (data, ret) {
        Layer.alert(ret.msg);
        return false;
    });
}
function customer_num()
{
    var order_platform = $('#order_platform').val();
    var time_str = $('#time_str').val();
    var compare_time_str = $('#compare_time_str').val();
    Backend.api.ajax({
        url: 'elasticsearch/customer/order/all_data/getCustomer',
        data: {order_platform: order_platform, time_str: time_str}
    }, function (data, ret) {
        $('#customer_info').html('')
        $('#customer_info').html(ret.data)
        return false;
    }, function (data, ret) {
        Layer.alert(ret.msg);
        return false;
    });
}