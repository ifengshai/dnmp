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
                        { field: 'number', title: __('序号') },
                        { field: 'increment_id', title: __('订单号') },
                        { field: 'created_at', title: __('订单时间') },
                        { field: 'customer_email', title: __('支付邮箱') },
                        { field: 'prescription_type', title: __('处方类型') },
                        { field: 'coatiing_name', title: __('镀膜类型') },
                        { field: 'price', title: __('价格（镜框+镜片）') }
                        
                    ]
                ]
            });
            // 为表格绑定事件
            Table.api.bindevent(table);
            $("#sku_submit").click(function(){
                $("#sku_data").css('display','block'); 
                var sku = $("#sku").val();
                var time_str = $("#time_str").val();
                if(sku.length <= 0){
                    Layer.alert('请填写平台sku');
                    return false;
                }
                if(time_str.length <= 0){
                    Layer.alert('请选择时间');
                    return false;
                }
                Controller.api.formatter.user_data_pie();
                Controller.api.formatter.lens_data_pie();

                var params = table.bootstrapTable('getOptions')
                params.queryParams = function(params) {
         
                    //定义参数
                    var filter = {};
                    //遍历form 组装json
                    $.each($("#form").serializeArray(), function(i, field) {
                        filter[field.name] = field.value;
                    });
         
                    //参数转为json字符串
                    params.filter = JSON.stringify(filter)
                    console.info(params);
                    return params;
                }

                table.bootstrapTable('refresh',params);
            });
            $("#sku_reset").click(function(){
                $("#sku_data").css('display','none'); 
                $("#order_platform").val(1);
                $("#time_str").val('');
                $("#sku").val('');
                Controller.api.formatter.user_data_pie();
                Controller.api.formatter.lens_data_pie();

                var params = table.bootstrapTable('getOptions')
                params.queryParams = function(params) {
         
                    //定义参数
                    var filter = {};
                    //遍历form 组装json
                    $.each($("#form").serializeArray(), function(i, field) {
                        filter[field.name] = field.value;
                    });
         
                    //参数转为json字符串
                    params.filter = JSON.stringify(filter)
                    console.info(params);
                    return params;
                }

                table.bootstrapTable('refresh',params);
            });
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
                            'sku':$("#sku").val(),
                            'time_str' :  $("#time_str").val(),
                            'order_platform' :  $("#order_platform").val(),
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
                            'sku':$("#sku").val(),
                            'time_str' :  $("#time_str").val(),
                            'order_platform' :  $("#order_platform").val(),
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