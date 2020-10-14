define(['jquery', 'bootstrap', 'backend', 'table', 'form', 'echartsobj'], function ($, undefined, Backend, Table, Form, EchartObj) {

    var Controller = {
        index: function () {
            Controller.api.bindevent();

            // 初始化表格参数配置
            Table.api.init({
                commonSearch: false,
                search: false,
                showExport: false,
                showColumns: false,
                showToggle: false,
                pagination: false,
                extend: {
                    index_url: 'operatedatacenter/orderdata/order_data_change/index' + location.search,
                    add_url: 'operatedatacenter/orderdata/order_data_change/add',
                    edit_url: 'operatedatacenter/orderdata/order_data_change/edit',
                    del_url: 'operatedatacenter/orderdata/order_data_change/del',
                    multi_url: 'operatedatacenter/orderdata/order_data_change/multi',
                    table: 'order_data_change',
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
                        { checkbox: true },
                        { field: 'id', title: __('日期') },
                        { field: 'id', title: __('会话数') },
                        { field: 'id', title: __('加购率') },
                        { field: 'id', title: __('会话转化率') },
                        { field: 'id', title: __('订单数') },
                        { field: 'id', title: __('客单价') },
                        { field: 'id', title: __('新增购物车数量') },
                        { field: 'id', title: __('更新购物车数量') },
                        { field: 'id', title: __('订单金额') },
                        { field: 'id', title: __('注册量') }
                    ]
                ]
            });

            // 为表格绑定事件
            Table.api.bindevent(table);

            Controller.api.formatter.order_sales_data_line();
            Controller.api.formatter.order_num_data_line();
        },
        add: function () {
            Controller.api.bindevent();
        },
        edit: function () {
            Controller.api.bindevent();
        },
        api: {
            formatter: {

                order_sales_data_line: function () {
                    var chartOptions = {
                        targetId: 'echart',
                        downLoadTitle: '图表',
                        type: 'line'
                    };
                    var options = {
                        type: 'post',
                        url: 'operatedatacenter/orderdata/order_data_change/order_sales_data_line',
                        data: {

                        }

                    }
                    EchartObj.api.ajax(options, chartOptions)
                },
                order_num_data_line: function () {
                    var chartOptions = {
                        targetId: 'echart2',
                        downLoadTitle: '图表',
                        type: 'line'
                    };
                    var options = {
                        type: 'post',
                        url: 'operatedatacenter/orderdata/order_data_change/order_num_data_line',
                        data: {

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