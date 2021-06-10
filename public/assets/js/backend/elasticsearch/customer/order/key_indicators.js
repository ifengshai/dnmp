define(['jquery', 'bootstrap', 'backend', 'addtabs', 'table', 'form', 'echartsobj', 'echarts', 'echarts-theme', 'template', 'custom-css'], function ($, undefined, Backend, Datatable, Table, Form, EchartObj, undefined, Template) {

    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                extend: {
                    index_url: 'elasticsearch/customer/order/key_indicators/index' + location.search,
                    table: 'key_indicators_view',
                }
            });
            Controller.api.formatter.daterangepicker($("div[role=form]"));
            //订单数据概况折线图
            order_data_view();
            Controller.api.formatter.line_chart();
            $("#sku_submit").click(function () {
                order_data_view();
                Controller.api.formatter.line_chart();
            });
            $("#sku_reset").click(function () {
                $("#order_platform").val(1);
                $("#time_str").val('');
                $("#compare_time_str").val('');
            });
            $(document).on('change', '#type', function () {
                Controller.api.formatter.line_chart();
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
                                    console.log(param)
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
                                data: ['订单数', '活跃用户数']
                            },
                            xAxis: [
                                {
                                    type: 'category'
                                }
                            ],
                            yAxis: [
                                {
                                    type: 'value',
                                    name: '订单数',
                                    axisLabel: {
                                        formatter: '{value} '
                                    }
                                },
                                {
                                    type: 'value',
                                    name: '活跃用户数',
                                    axisLabel: {
                                        formatter: '{value} '
                                    }
                                }
                            ],
                        }
                    };

                    var options = {
                        type: 'post',
                        url: 'elasticsearch/customer/order/key_indicators/ajaxGetEcharts',
                        data: {
                            order_platform: $("#order_platform").val(),
                            time_str: $("#time_str").val(),
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
        url: 'elasticsearch/customer/order/key_indicators/ajaxGetPurchase',
        data: { order_platform: order_platform, time_str: time_str,compare_time_str: compare_time_str,type:3}
    }, function (data, ret) {
        var order_num = ret.data.orderNum;
        var order_unit_price = ret.data.allAvgPrice;
        var sales_total_money = ret.data.allDaySalesAmount;
        var shipping_total_money = ret.data.allShippingAmount;


        var price_ranges_data = ret.data.priceRangesData;
        var ship_type = ret.data.shipTypeData;

        var compare_order_num_rate = ret.data.compareOrderNumRate;
        var compare_order_unit_price_rate = ret.data.compareAllAvgPriceRate;
        var compare_sales_total_money_rate = ret.data.compareAllDaySalesAmountRate;
        var compare_shipping_total_money_rate = ret.data.compareAllShippingAmountRate;
        if(compare_time_str.length > 0){
            $('.rate_class').show();
        }
        if(compare_time_str.length <= 0){
            $('.rate_class').hide();
        }

        $('#order_num').text(order_num);

        if(compare_order_num_rate >= 0){
            var huan_rate1 = '<img src="/shangsheng.png">';
        }else{
            var huan_rate1 = '<img src="/xiadie.png">';
        }
        $('#huan_order_num').html(huan_rate1+compare_order_num_rate);
        $('#order_unit_price').html(order_unit_price);
        if(compare_order_unit_price_rate >= 0){
            var huan_rate2 = '<img src="/shangsheng.png">';
        }else{
            var huan_rate2 = '<img src="/xiadie.png">';
        }
        $('#huan_order_unit_price').html(huan_rate2+compare_order_unit_price_rate);
        $('#sales_total_money').html(sales_total_money);

        if(compare_sales_total_money_rate >= 0){
            var huan_rate3 = '<img src="/shangsheng.png">';
        }else{
            var huan_rate3 = '<img src="/xiadie.png">';
        }
        $('#huan_sales_total_money').html(huan_rate3+compare_sales_total_money_rate);
        $('#shipping_total_money').html(shipping_total_money);

        if(compare_shipping_total_money_rate >= 0){
            var huan_rate4 = '<img src="/shangsheng.png">';
        }else{
            var huan_rate4 = '<img src="/xiadie.png">';
        }
        $('#huan_shipping_total_money').html(huan_rate4+compare_shipping_total_money_rate);

        //订单金额分布表数据
        var order_total0 = price_ranges_data[0];
        var order_total20 = price_ranges_data[20];
        var order_total30 = price_ranges_data[30];
        var order_total40 = price_ranges_data[40];
        var order_total50 = price_ranges_data[50];
        var order_total60 = price_ranges_data[60];
        var order_total80 = price_ranges_data[80];
        var order_total100 = price_ranges_data[100];
        var order_total200 = price_ranges_data[200];

        $('#ordernum0').text(order_total0.doc_count);
        $('#ordernum_rate0').text(order_total0.rate);
        $('#ordernum20').text(order_total20.doc_count);
        $('#ordernum_rate20').text(order_total20.rate);
        $('#ordernum30').text(order_total30.doc_count);
        $('#ordernum_rate30').text(order_total30.rate);
        $('#ordernum40').text(order_total40.doc_count);
        $('#ordernum_rate40').text(order_total40.rate);
        $('#ordernum50').text(order_total50.doc_count);
        $('#ordernum_rate50').text(order_total50.rate);
        $('#ordernum60').text(order_total60.doc_count);
        $('#ordernum_rate60').text(order_total60.rate);
        $('#ordernum80').text(order_total80.doc_count);
        $('#ordernum_rate80').text(order_total80.rate);
        $('#ordernum100').text(order_total100.doc_count);
        $('#ordernum_rate100').text(order_total100.rate);
        $('#ordernum200').text(order_total200.doc_count);
        $('#ordernum_rate200').text(order_total200.rate);
        $('#flatrate_free_order_num').text('');
        $('#flatrate_free_rate').text('');
        $('#flatrate_nofree_order_num').text('');
        $('#flatrate_nofree_rate').text('');
        $('#flatrate_nofree_order_total').text('');
        $('#tablerate_free_order_num').text('');
        $('#tablerate_free_rate').text('');
        $('#tablerate_nofree_order_num').text('');
        $('#tablerate_nofree_rate').text('');
        $('#tablerate_nofree_order_total').text('')
        //订单运费数据统计
        if(ship_type[0]) {
            var flatrate_free = ship_type[0];
            $('#flatrate_free_order_num').text(flatrate_free.doc_count);
            $('#flatrate_free_rate').text(flatrate_free.rate);
        }
        if(ship_type[1]) {
            var flatrate_nofree = ship_type[1];
            $('#flatrate_nofree_order_num').text(flatrate_nofree.doc_count);
            $('#flatrate_nofree_rate').text(flatrate_nofree.rate);
            var allShippingAmount = flatrate_nofree.allShippingAmount.value
            $('#flatrate_nofree_order_total').text(allShippingAmount.toFixed(2));
        }
        if(ship_type[2]) {
            var tablerate_free = ship_type[2];
            $('#tablerate_free_order_num').text(tablerate_free.doc_count);
            $('#tablerate_free_rate').text(tablerate_free.rate);
        }
        if(ship_type[3]) {
            var tablerate_nofree = ship_type[3];
            $('#tablerate_nofree_order_num').text(tablerate_nofree.doc_count);
            $('#tablerate_nofree_rate').text(tablerate_nofree.rate);
            var nofreeallShippingAmount = tablerate_nofree.allShippingAmount.value;
            $('#tablerate_nofree_order_total').text(nofreeallShippingAmount.toFixed(2))
        }


        //国家地域分布
        $("#country_info").html(ret.data.countryStr);
        $("#store_info").html(ret.data.storeDataStr);
        return false;
    }, function (data, ret) {
        Layer.alert(ret.msg);
        return false;
    });
}