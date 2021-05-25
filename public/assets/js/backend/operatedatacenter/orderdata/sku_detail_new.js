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
                extend: {
                    index_url: 'operatedatacenter/orderdata/sku_detail_new/index' + location.search,
                    add_url: 'operatedatacenter/orderdata/sku_detail_new/add',
                    edit_url: 'operatedatacenter/orderdata/sku_detail_new/edit',
                    del_url: 'operatedatacenter/orderdata/sku_detail_new/del',
                    multi_url: 'operatedatacenter/orderdata/sku_detail_new/multi',
                    table: 'sku_detail_new',
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
                        { field: 'id', title: __('序号') },
                        { field: 'sku', title: __('Sku') },
                        { field: 'increment_id', title: __('订单号') },
                        { field: 'payment_time', title: __('订单时间') },
                        { field: 'customer_email', title: __('支付邮箱') },
                        { field: 'prescription_type', title: __('处方类型') },
                        { field: 'coating_name', title: __('镀膜类型') },
                        { field: 'frame_price', title: __('镜框价格') },
                        { field: 'index_price', title: __('镜片价格') }

                    ]
                ]
            });
            // 为表格绑定事件
            Table.api.bindevent(table);
            $("#sku_submit").click(function(){
                var sku = $("#sku").val();
                var time_str = $("#time_str").val();
                if(time_str.length <= 0){
                    Layer.alert('请选择时间');
                    return false;
                }

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
            $("#export").click(function(){
                var order_platform = $('#order_platform').val();
                var time_str = $('#time_str').val();
                var sku = $('#sku').val();
                if(sku.length <= 0){
                    Layer.alert('请填写平台sku');
                    return false;
                }
                if(time_str.length <= 0){
                    Layer.alert('请选择时间');
                    return false;
                }
                window.location.href=Config.moduleurl+'/operatedatacenter/orderdata/sku_detail_new/export?order_platform='+order_platform+'&time_str='+time_str+'&sku='+sku;
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
                
            },
            bindevent: function () {
                Form.api.bindevent($("form[role=form]"));
            }
        }
    };
    return Controller;
});