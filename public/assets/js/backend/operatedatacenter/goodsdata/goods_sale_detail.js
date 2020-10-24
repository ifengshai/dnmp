define(['jquery', 'bootstrap', 'backend', 'addtabs', 'table', 'form', 'echartsobj', 'echarts-theme', 'template', 'custom-css'], function ($, undefined, Backend, Datatable, Table, Form, EchartObj, undefined, Template) {

    var Controller = {
        index: function () {
            Controller.api.formatter.daterangepicker($("form[role=form1]"));
            Form.api.bindevent($("form[role=form]"));
            var time = $('#create_time').val();
            var platform = $('#order_platform').val();

            Backend.api.ajax({
                url: "operatedatacenter/goodsdata/goods_sale_detail/top_sale_list",
                data: {
                    'time': time,
                    'platform': platform
                }
            }, function (data, ret) {
                $('#frame_money').text(ret.rows.frame_money);
                $('#frame_sales_num').text(ret.rows.frame_sales_num);
                $('#frame_avg_money').text(ret.rows.frame_avg_money);
                $('#frame_onsales_num').text(ret.rows.frame_onsales_num);
                $('#decoration_money').text(ret.rows.decoration_money);
                $('#decoration_sales_num').text(ret.rows.decoration_sales_num);
                $('#decoration_avg_money').text(ret.rows.decoration_avg_money);
                $('#decoration_onsales_num').text(ret.rows.decoration_onsales_num);
                $('#frame_in_print_num').text(ret.rows.frame_in_print_num);
                $('#frame_in_print_rate').text(ret.rows.frame_in_print_rate);
                $('#decoration_in_print_num').text(ret.rows.decoration_in_print_num);
                $('#decoration_in_print_rate').text(ret.rows.decoration_in_print_rate);
                $('#frame_new_money').text(ret.rows.frame_new_money);
                $('#decoration_new_money').text(ret.rows.decoration_new_money);
                $('#frame_order_customer').text(ret.rows.frame_order_customer);
                $('#frame_avg_customer').text(ret.rows.frame_avg_customer);
                $('#decoration_order_customer').text(ret.rows.decoration_order_customer);
                $('#decoration_avg_customer').text(ret.rows.decoration_avg_customer);
                $('#frame_new_num').text(ret.rows.frame_new_num);
                $('#decoration_new_num').text(ret.rows.decoration_new_num);
                $('#frame_new_in_print_num').text(ret.rows.frame_new_in_print_num);
                $('#frame_new_in_print_rate').text(ret.rows.frame_new_in_print_rate);
                $('#decoration_new_in_print_num').text(ret.rows.decoration_new_in_print_num);
                $('#decoration_new_in_print_rate').text(ret.rows.decoration_new_in_print_rate);

            });

            // 初始化表格参数配置
            Table.api.init({
                commonSearch: false,
                search: false,
                showExport: true,
                showColumns: true,
                showToggle: true,
                pagination: false,
                extend: {
                    index_url: 'operatedatacenter/goodsdata/goods_sale_detail/index' + location.search + '?time=' + Config.create_time + '&site=' + Config.label + '&type=list',
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
                        {
                            field: '', title: __('序号'), formatter: function (value, row, index) {
                                var options = table.bootstrapTable('getOptions');
                                var pageNumber = options.pageNumber;
                                var pageSize = options.pageSize;
                                return (pageNumber - 1) * pageSize + 1 + index;
                            }, operate: false
                        },
                        {field: 'platformsku', title: __('平台SKU'), operate: false},
                        {field: 'sku', title: __('SKU'), operate: false},
                        // { field: 'name', title: __('商品名称'), operate: false },
                        // { field: 'type_name', title: __('分类'), operate: false },
                        {field: 'sales_num', title: __('销量'), operate: false},
                        {field: 'available_stock', title: __('虚拟仓库存'), sortable: true, operate: false},
                        {field: 'grade', title: __('产品等级'), operate: false},

                        {
                            field: 'is_up', title: __('平台上下架状态'), operate: false,
                            custom: {1: 'success', 2: 'danger'},
                            searchList: {1: '上架', 2: '下架'},
                            formatter: Table.api.formatter.status
                        },


                    ]
                ]
            });

            // 为表格绑定事件
            Table.api.bindevent(table);
            $("#sku_submit").click(function () {
                var time = $('#create_time').val();
                var platform = $('#order_platform').val();
                // Form.api.bindevent($("form[role=form]"));
                //
                // table.bootstrapTable('refresh', params);
                Backend.api.ajax({
                    url: "operatedatacenter/goodsdata/goods_sale_detail/top_sale_list",
                    data: {
                        'time': time,
                        'platform': platform
                    }
                }, function (data, ret) {
                    $('#frame_money').text(ret.rows.frame_money);
                    $('#frame_sales_num').text(ret.rows.frame_sales_num);
                    $('#frame_avg_money').text(ret.rows.frame_avg_money);
                    $('#frame_onsales_num').text(ret.rows.frame_onsales_num);
                    $('#decoration_money').text(ret.rows.decoration_money);
                    $('#decoration_sales_num').text(ret.rows.decoration_sales_num);
                    $('#decoration_avg_money').text(ret.rows.decoration_avg_money);
                    $('#decoration_onsales_num').text(ret.rows.decoration_onsales_num);
                    $('#frame_in_print_num').text(ret.rows.frame_in_print_num);
                    $('#frame_in_print_rate').text(ret.rows.frame_in_print_rate);
                    $('#decoration_in_print_num').text(ret.rows.decoration_in_print_num);
                    $('#decoration_in_print_rate').text(ret.rows.decoration_in_print_rate);
                    $('#frame_new_money').text(ret.rows.frame_new_money);
                    $('#decoration_new_money').text(ret.rows.decoration_new_money);
                    $('#frame_order_customer').text(ret.rows.frame_order_customer);
                    $('#frame_avg_customer').text(ret.rows.frame_avg_customer);
                    $('#decoration_order_customer').text(ret.rows.decoration_order_customer);
                    $('#decoration_avg_customer').text(ret.rows.decoration_avg_customer);
                    $('#frame_new_num').text(ret.rows.frame_new_num);
                    $('#decoration_new_num').text(ret.rows.decoration_new_num);
                    $('#frame_new_in_print_num').text(ret.rows.frame_new_in_print_num);
                    $('#frame_new_in_print_rate').text(ret.rows.frame_new_in_print_rate);
                    $('#decoration_new_in_print_num').text(ret.rows.decoration_new_in_print_num);
                    $('#decoration_new_in_print_rate').text(ret.rows.decoration_new_in_print_rate);

                }, function (data, ret) {
                    alert(ret.msg);
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
        },
    };
    return Controller;
});