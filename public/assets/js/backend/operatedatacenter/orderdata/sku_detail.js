define(['jquery', 'bootstrap', 'backend', 'table', 'form', 'echartsobj'], function ($, undefined, Backend, Table, Form, EchartObj) {

    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                commonSearch: false,
                search: false,
                showExport: false,
                showColumns: false,
                showToggle: false,
                pagination: false,
                extend: {
                    index_url: 'operatedatacenter/orderdata/sku_detail/index' + location.search,
                    add_url: 'operatedatacenter/orderdata/sku_detail/add',
                    edit_url: 'operatedatacenter/orderdata/sku_detail/edit',
                    del_url: 'operatedatacenter/orderdata/sku_detail/del',
                    multi_url: 'operatedatacenter/orderdata/sku_detail/multi',
                    table: 'sku_detail',
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
                        { field: 'id', title: __('序号') },
                        { field: 'id', title: __('订单号') },
                        { field: 'id', title: __('订单时间') },
                        { field: 'id', title: __('支付邮箱') },
                        { field: 'id', title: __('处方类型') },
                        { field: 'id', title: __('镀膜类型') },
                        { field: 'id', title: __('价格（镜框+镜片）') }
                        
                    ]
                ]
            });
            // 为表格绑定事件
            Table.api.bindevent(table);

            Controller.api.formatter.user_data_pie();
            Controller.api.formatter.lens_data_pie();
        },
        add: function () {
            Controller.api.bindevent();
        },
        edit: function () {
            Controller.api.bindevent();
        },
        api: {
            formatter: {

                user_data_pie: function () {
                    //库存分布
                    var chartOptions = {
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

                    var options = {
                        type: 'post',
                        url: 'operatedatacenter/orderdata/sku_detail/user_data_pie',
                        data: {

                        }

                    };
                    EchartObj.api.ajax(options, chartOptions)
                },
                lens_data_pie: function () {
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
                        url: 'operatedatacenter/orderdata/sku_detail/lens_data_pie',
                        data: {

                        }

                    };
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