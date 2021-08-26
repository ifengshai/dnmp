define(['jquery', 'bootstrap', 'backend', 'table', 'form', 'echarts'], function ($, undefined, Backend, Table, Form, echarts) {

    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                extend: {
                    index_url: 'new_product_processes/index' + location.search,
                    statistics_url: 'new_product_processes/statistics',
                    table: 'new_product_processes',
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
                        {field: 'id', title: __('Id'),operate: false},
                        {field: 'sku', title: __('Sku')},
                        {
                            field: 'status',
                                title: __('Status'),
                            custom: { 1: 'danger', 2: 'success', 3: 'blue', 4: 'orange', 5: 'gray',6:'danger',7:'success'},
                            searchList: { 1: '待提报', 2: '待采购', 3: '待入库', 4: '待带回',5:'待设计',6:'待上架',7:'已上架'},
                            formatter: Table.api.formatter.status
                        },
                        {field: 'goods_supply', title: __('Goods_supply'),operate: false},
                        {field: 'platform', title: __('同步平台'),operate: false},
                        {field: 'create_time', title: __('Create_time'), operate:'RANGE', addclass:'datetimerange', formatter: Table.api.formatter.datetime},
                        {field: 'update_time', title: __('Update_time'), operate:'RANGE', addclass:'datetimerange', formatter: Table.api.formatter.datetime,operate: false},
                        {
                            field: 'operate',
                            title: __('Operate'),
                            table: table,
                            events: Table.api.events.operate,
                            buttons: [
                                {
                                    name: 'detail',
                                    text: '操作记录',
                                    title: __('操作记录'),
                                    classname: 'btn btn-xs btn-primary btn-dialog',
                                    icon: 'fa fa-list',
                                    url: 'new_product_processes/operationLog',
                                    extend: 'data-area = \'["60%","50%"]\'',
                                    visible: true
                                },
                            ],
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
        statistics: function () {
            Controller.api.formatter.daterangepicker($("div[role=form]"));

            Controller.api.new_product_process_chart();

            $("#sku_submit").click(function () {
                var time_str = $("#time_str").val();
                if (time_str.length <= 0) {
                    Layer.alert('请选择时间');
                    return false;
                }
                Controller.api.new_product_process_chart();
            });
            $("#sku_reset").click(function () {
                $("#time_str").val('');
                Controller.api.new_product_process_chart();
            });
        },
        api: {
            bindevent: function () {
                Form.api.bindevent($("form[role=form]"));
            },
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
            new_product_process_chart: function () {
                var myChart = echarts.init($('#echart3').get(0), 'walden');
                var option = {
                    tooltip: {
                        trigger: 'item',
                    },
                    color: ['#1e239b', '#3312d4', '#632ef0', '#ba3779', '#d4793a', '#d2b54a', '#4fb495', '#dfecd4', '#d0afe1', '#f2d3c7'],
                    legend: {
                        data: [],
                        show: false
                    },
                    calculable: true,
                };
                option.series = [
                    {
                        type:'funnel',
                        left: 0,
                        top: 60,
                        bottom: 60,
                        width: '80%',
                        min: 0,
                        minSize: '0%',
                        maxSize: '100%',
                        sort: 'descending',
                        gap: 2,
                        label: {
                            normal: {
                                show: true,
                                position: 'inside',
                                formatter: function (param) {
                                    return param.data.name + '：' + param.data.value;
                                }
                            },
                        },
                        labelLine: {
                            length: 10,
                            lineStyle: {
                                width: 1,
                                type: 'solid'
                            }
                        },
                        itemStyle: {
                            borderColor: '#fff',
                            borderWidth: 1
                        },
                        emphasis: {
                            label: {
                                fontSize: 20
                            }
                        },
                    }
                ];

                Backend.api.ajax({
                    type: 'post',
                    url: 'new_product_processes/funnel',
                    data: {
                        time_str: $("#time_str").val(),
                    }
                }, function (data) {
                    option.series[0].data = data.columnData;
                    console.log(option)
                    myChart.setOption(option)
                });

                // 跳转
                myChart.on('click', function (params) {
                    console.log(params);
                    let status = params.data.status;
                    let url = 'new_product_processes?status=' + status + '&create_time=' + $("#time_str").val();
                    Backend.api.addtabs(url, "新品流程日志");
                    // window.location.href = url;
                })
            },
        }
    };
    return Controller;
});