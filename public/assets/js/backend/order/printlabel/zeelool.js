define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {

    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                searchFormVisible: true,
                searchFormTemplate: 'customformtpl',                
                extend: {
                    index_url: 'order/printlabel/zeelool/index' + location.search,
                    add_url: 'order/printlabel/zeelool/add',
                    edit_url: 'order/printlabel/zeelool/edit',
                    del_url: 'order/printlabel/zeelool/del',
                    multi_url: 'order/printlabel/zeelool/multi',
                    table: 'sales_flat_order',
                }
            });

            var table = $("#table");

            // 初始化表格
            table.bootstrapTable({
                url: $.fn.bootstrapTable.defaults.extend.index_url,
                pk: 'entity_id',
                sortName: 'entity_id',
                columns: [
                    [
                        {checkbox: true},
                        {field: 'entity_id', title: __('记录标识')},
                        {field: 'increment_id', title: __('订单号')},
                        {field: 'status', title: __('状态'),searchList: {"processing":__('processing'),"free_processing":__('free_processing')}},
                        {field: 'base_grand_total', title: __('订单金额'), operate:false , formatter: Controller.api.formatter.float_format},
                        {field: 'base_shipping_amount', title: __('运费'), operate:false , formatter: Controller.api.formatter.float_format},
                                                                       
                        {field: 'total_qty_ordered', title: __('SKU数量'), operate:false , formatter: Controller.api.formatter.int_format},   
                        {field: 'custom_print_label', title: __('打印标签') , operate:false ,formatter: Controller.api.formatter.printLabel},                        
                        {field: 'custom_print_label_created_at', operate:false ,title: __('打印标签时间')},

                        // {field: 'coupon_code', title: __('Coupon_code')},

                        // {field: 'shipping_description', title: __('Shipping_description')},
                        // {field: 'store_id', title: __('Store_id'), formatter: Controller.api.formatter.device},
                        // {field: 'customer_id', title: __('Customer_id')},
                        // {field: 'base_discount_amount', title: __('Base_discount_amount'), operate:'BETWEEN', formatter: Controller.api.formatter.float_format},                      
                   
                        // {field: 'quote_id', title: __('Quote_id')},
                                          
                        // {field: 'base_currency_code', title: __('Base_currency_code')},
                        // {field: 'customer_email', title: __('Customer_email')},
                        // {field: 'customer_firstname', title: __('Customer_firstname')},
                        // {field: 'customer_lastname', title: __('Customer_lastname')},
                     
                        {field: 'custom_is_match_frame', title: __('配镜架'),operate:false ,formatter: Controller.api.formatter.printLabel},
                        {field: 'custom_is_match_lens', title: __('配镜片'),operate:false ,formatter: Controller.api.formatter.printLabel},
                        {field: 'custom_is_send_factory', title: __('加工'),operate:false ,formatter: Controller.api.formatter.printLabel},
                        {field: 'custom_is_delivery', title: __('提货'),operate:false ,formatter: Controller.api.formatter.printLabel},
                    ]
                ]
            });

            // 为表格绑定事件
            Table.api.bindevent(table);

            //批量打印标签    
            $('.btn-batch-printed').click(function () {
                console.log('id_params');
                var ids = Table.api.selectedids(table);
                // console.log(ids);
                var id_params = '';
                $.each(table.bootstrapTable('getSelections'), function (index, row) {
                    // console.log(row); 
                    id_params += row['entity_id']+',';                       
                });
                console.log(id_params);

                // var ids = Table.api.selectedids(table);

                window.open('/admin/order/printlabel/zeelool/batch_print_label/id_params/'+id_params,'_blank');
            });

                        //批量导出xls 
            $('.btn-batch-export-xls').click(function () {
                console.log('id_params');
                var ids = Table.api.selectedids(table);
                // console.log(ids);
                var id_params = '';
                $.each(table.bootstrapTable('getSelections'), function (index, row) {
                    // console.log(row); 
                    id_params += row['entity_id']+',';                       
                });
                console.log(id_params);

                // var ids = Table.api.selectedids(table);

                window.open('/admin/order/printlabel/zeelool/batch_export_xls/id_params/'+id_params,'_blank');
            });     

            //批量标记已打印    
            $('.btn-tag-printed').click(function () {
                var ids = Table.api.selectedids(table);
                // console.log(ids);
                var id_params = '';
                $.each(table.bootstrapTable('getSelections'), function (index, row) {
                    // console.log(row); 
                    id_params += row['entity_id']+',';                       
                });
                console.log(id_params);

                var ids = Table.api.selectedids(table);
                Layer.confirm(
                    __('确定要这%s条记录 标记为 【已打印标签】么?', ids.length),
                    {icon: 3, title: __('Warning'), offset: 0, shadeClose: true},
                    function (index) {
                            // Table.api.multi("del", ids, table, that);
                            console.log('开始执行');
                            Layer.close(index);

                            Backend.api.ajax({
                                url:'/admin/order/printlabel/zeelool/tag_printed',
                                // url:"{:url('tag_printed')}",
                                data:{id_params:id_params},
                                type:'get'
                            }, function(data, ret){

                                console.log(data);
                                console.log(ret);
                                if(data == 'success'){
                                    console.log('成功的回调');
                                    table.bootstrapTable('refresh');
                                }
                            }, function(data, ret){

                                console.log('失败的回调');
                            });

                        }
                    );     

            })
        },
        add: function () {
            Controller.api.bindevent();
        },
        edit: function () {
            Controller.api.bindevent();
        },
        api: {

            formatter: {
                device: function (value, row, index) {
                    var str = '';
                    if (value == 1) {
                        str = '电脑';
                    } else if (value == 4) {
                        str = '移动';
                    } else {
                        str = '未知';
                    }
                    return str;
                },
                printLabel: function (value, row, index) {
                    var str = '';              
                    if (value == 0) {
                        str = '否';
                    } else if (value == 1) {
                        str = '<span style="font-weight:bold;color:#18bc9c;">是</span>';
                    } else {
                        str = '未知';
                    }
                    return str;
                },                
                float_format: function (value, row, index) {                                      
                    return parseFloat(value).toFixed(2);
                },
                int_format: function (value, row, index) {                                      
                    return parseInt(value);
                },
            },
            bindevent: function () {
                Form.api.bindevent($("form[role=form]"));
            }
        }
    };
    return Controller;
});