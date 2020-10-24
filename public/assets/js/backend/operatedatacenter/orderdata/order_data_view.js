define(['jquery', 'bootstrap', 'backend', 'addtabs', 'table', 'form', 'echartsobj', 'echarts', 'echarts-theme', 'template', 'custom-css'], function ($, undefined, Backend, Datatable, Table, Form, EchartObj, undefined, Template) {

    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                extend: {
                    index_url: 'operatedatacenter/orderdata/order_data_view/index' + location.search,
                    add_url: 'operatedatacenter/orderdata/order_data_view/add',
                    edit_url: 'operatedatacenter/orderdata/order_data_view/edit',
                    del_url: 'operatedatacenter/orderdata/order_data_view/del',
                    multi_url: 'operatedatacenter/orderdata/order_data_view/multi',
                    table: 'order_data_view',
                }
            });
            Controller.api.formatter.daterangepicker($("div[role=form]"));
            //订单数据概况折线图
            Controller.api.formatter.line_chart();
            Controller.api.formatter.line_histogram();
            $("#time_str").on("apply.daterangepicker", function () {
                setTimeout(() => {
                    order_data_view();
                    Controller.api.formatter.line_chart();
                    Controller.api.formatter.line_histogram();
                }, 0)
            })
            $(document).on('change', '#type', function () {
                Controller.api.formatter.line_chart();
            });
            $(document).on('change', '#order_platform', function () {
                order_data_view();
                Controller.api.formatter.line_chart();
                Controller.api.formatter.line_histogram();
            });
            
            
            var table = $("#table");

            // 初始化表格
            table.bootstrapTable({
                url: $.fn.bootstrapTable.defaults.extend.index_url,
                pk: 'id',
                sortName: 'id',
                columns: [
                    [
                        { checkbox: true },
                        { field: 'id', title: __('Id') },
                        { field: 'operate', title: __('Operate'), table: table, events: Table.api.events.operate, formatter: Table.api.formatter.operate }
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
                line_chart: function () {
                    //订单数据概况折线图
                    var chartOptions = {
                        targetId: 'echart1',
                        downLoadTitle: '图表',
                        type: 'line'
                    };

                    var options = {
                        type: 'post',
                        url: 'operatedatacenter/orderdata/order_data_view/order_data_view_line',
                        data: {
                            order_platform: $("#order_platform").val(),
                            time_str: $("#time_str").val(),
                            type: $("#type").val()
                        }
                    }
                    EchartObj.api.ajax(options, chartOptions)
                },
                line_histogram: function (){
                    //柱状图和折线图的结合
                    var chartOptions = {
                        targetId: 'echart3',
                        downLoadTitle: '图表',
                        type: 'bar',
                        bar:{
                            legend: { //图例配置
                                padding: 5,
                                top: '2%',
                                data: ['客单价', '中位数','标准差']
                            },   
                            grid: { //直角坐标系内绘图网格
                                top: '20%', //grid 组件离容器上侧的距离。
                                left: '5%', //grid 组件离容器左侧的距离。
                                right: '10%', //grid 组件离容器右侧的距离。
                                bottom: '10%', //grid 组件离容器下侧的距离。
                                containLabel: true //grid 区域是否包含坐标轴的刻度标签。
                            },                     
                            tooltip: { //提示框组件。
                                trigger: 'axis', // 触发类型。可选项item:数据项图形触发，主要在散点图，饼图等无类目轴的图表中使用。axis:坐标轴触发，主要在柱状图，折线图等会使用类目轴的图表中使用。
                                axisPointer: { //坐标轴指示器配置项。
                                    type: 'shadow' //指示器类型。可选项'line' 直线指示器。'shadow' 阴影指示器。'cross' 十字准星指示器。其实是种简写，表示启用两个正交的轴的 axisPointer。
                                },
                                formatter: function (param) { //格式化提示信息
                                    console.log(param);
                                    return param[0].name + '<br/>' + param[0].seriesName + '：' + param[0].value + '<br/>' + param[1].seriesName + '：' + param[1].value + '<br/>' + param[2].seriesName + '：' + param[2].value;
                                }
                            },                        
                            xAxis: {
                                type: 'category',
                            },
                            yAxis: [
                                {
                                    type: 'value',
                                    name: '客单价',
                                    position: 'left',
                                    axisLabel: {
                                        formatter: '{value}'
                                    }
                                },
                                {
                                    type: 'value',
                                    name: '标准差',
                                    position: 'right',
                                    axisLabel: {
                                        formatter: '{value}'
                                    }
                                }
                            ],                        
                        }
                    };  
                    var options = {
                        type: 'post',
                        url: 'operatedatacenter/orderdata/order_data_view/ajax_histogram',
                        data: {
                            order_platform: $("#order_platform").val(),
                            time_str: $("#time_str").val(),
                        }
                    }                
                    EchartObj.api.ajax(options, chartOptions)
                }
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
    Backend.api.ajax({
        url: 'operatedatacenter/orderdata/order_data_view/ajax_order_data_view',
        data: { order_platform: order_platform, time_str: time_str }
    }, function (data, ret) {
        var order_num = ret.data.order_num;
        var order_unit_price = ret.data.order_unit_price;
        var sales_total_money = ret.data.sales_total_money;
        var shipping_total_money = ret.data.shipping_total_money;
        var replacement_order_num = ret.data.replacement_order_num;
        var replacement_order_total = ret.data.replacement_order_total;
        var online_celebrity_order_num = ret.data.online_celebrity_order_num;
        var online_celebrity_order_total = ret.data.online_celebrity_order_total;

        $('#order_num').text(order_num.order_num);
        $('#same_order_num').text(order_num.same_order_num);
        $('#huan_order_num').text(order_num.huan_order_num);
        $('#order_unit_price').text(order_unit_price.order_unit_price);
        $('#same_order_unit_price').text(order_unit_price.same_order_unit_price);
        $('#huan_order_unit_price').text(order_unit_price.huan_order_unit_price);
        $('#sales_total_money').text(sales_total_money.sales_total_money);
        $('#same_sales_total_money').text(sales_total_money.same_sales_total_money);
        $('#huan_sales_total_money').text(sales_total_money.huan_sales_total_money);
        $('#shipping_total_money').text(shipping_total_money.shipping_total_money);
        $('#same_shipping_total_money').text(shipping_total_money.same_shipping_total_money);
        $('#huan_shipping_total_money').text(shipping_total_money.huan_shipping_total_money);
        $('#replacement_order_num').text(replacement_order_num.replacement_order_num);
        $('#replacement_order_total').text(replacement_order_total.replacement_order_total);
        $('#online_celebrity_order_num').text(online_celebrity_order_num.online_celebrity_order_num);
        $('#online_celebrity_order_total').text(online_celebrity_order_total.online_celebrity_order_total);
        //订单金额分布表数据
        var order_total0 = ret.data.order_total_distribution.order_total0;
        var order_total20 = ret.data.order_total_distribution.order_total20;
        var order_total30 = ret.data.order_total_distribution.order_total30;
        var order_total40 = ret.data.order_total_distribution.order_total40;
        var order_total50 = ret.data.order_total_distribution.order_total50;
        var order_total60 = ret.data.order_total_distribution.order_total60;
        var order_total80 = ret.data.order_total_distribution.order_total80;
        var order_total100 = ret.data.order_total_distribution.order_total100;
        var order_total200 = ret.data.order_total_distribution.order_total200;
        
        $('#ordernum0').text(order_total0.order_num);
        $('#ordernum_rate0').text(order_total0.order_num_rate);
        $('#ordernum20').text(order_total20.order_num);
        $('#ordernum_rate20').text(order_total20.order_num_rate);
        $('#ordernum30').text(order_total30.order_num);
        $('#ordernum_rate30').text(order_total30.order_num_rate);
        $('#ordernum40').text(order_total40.order_num);
        $('#ordernum_rate40').text(order_total40.order_num_rate);
        $('#ordernum50').text(order_total50.order_num);
        $('#ordernum_rate50').text(order_total50.order_num_rate);
        $('#ordernum60').text(order_total60.order_num);
        $('#ordernum_rate60').text(order_total60.order_num_rate);
        $('#ordernum80').text(order_total80.order_num);
        $('#ordernum_rate80').text(order_total80.order_num_rate);
        $('#ordernum100').text(order_total100.order_num);
        $('#ordernum_rate100').text(order_total100.order_num_rate);
        $('#ordernum200').text(order_total200.order_num);
        $('#ordernum_rate200').text(order_total200.order_num_rate);
        //订单运费数据统计
        var flatrate_free = ret.data.order_shipping.flatrate_free;
        var flatrate_nofree = ret.data.order_shipping.flatrate_nofree;
        var tablerate_free = ret.data.order_shipping.tablerate_free;
        var tablerate_nofree = ret.data.order_shipping.tablerate_nofree;

        $('#flatrate_free_order_num').text(flatrate_free.order_num);
        $('#flatrate_free_rate').text(flatrate_free.order_num_rate);
        $('#flatrate_nofree_order_num').text(flatrate_nofree.order_num);
        $('#flatrate_nofree_rate').text(flatrate_nofree.order_num_rate);
        $('#flatrate_nofree_order_total').text(flatrate_nofree.order_total);
        $('#tablerate_free_order_num').text(tablerate_free.order_num);
        $('#tablerate_free_rate').text(tablerate_free.order_num_rate);
        $('#tablerate_nofree_order_num').text(tablerate_nofree.order_num);
        $('#tablerate_nofree_rate').text(tablerate_nofree.order_num_rate);
        $('#tablerate_nofree_order_total').text(tablerate_nofree.order_total);
        //国家地域分布
        $("#country_info").html(ret.data.country_str);
        return false;
    }, function (data, ret) {
        Layer.alert(ret.msg);
        return false;
    });
}