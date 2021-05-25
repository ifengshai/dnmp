define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {

    var Controller = {
        index: function () {
            Controller.api.bindevent();
            // 初始化表格参数配置
            Table.api.init({
                extend: {
                    index_url: 'operatedatacenter/newgoodsdata/goods_data_detail/index' + location.search,
                }
            });

            var table = $("#table");
            // 初始化表格
            table.bootstrapTable({
                url: $.fn.bootstrapTable.defaults.extend.index_url,
                pk: 'id',
                sortName: 'id',
                search: false,//通用搜索
                commonSearch: false,
                showToggle: false,
                showColumns: false,
                showExport: false,
                columns: [
                    [
                        {field: 'sku', title: __('SKU'), visible: true, operate: false},
                        {
                            field: 'type',
                            title: __('类别'),
                            custom: {1: 'danger', 3: 'success'},
                            searchList: {1: '眼镜', 3: '饰品'},
                            formatter: Table.api.formatter.status,
                            visible: false,
                            operate: false
                        },
                        {field: 'goods_type', title: __('商品类别'), visible: false, operate: false},
                        {field: 'status', title: __('状态'),custom: {1: 'success', 2: 'danger',3: 'grey'},searchList: {1: '上架', 2: '售罄',3:'下架'},formatter: Table.api.formatter.status, visible: false, operate: false},
                        {field: 'stock_status', title: __('库存状态'), visible: false, operate: false},
                        {field: 'status', title: __('订单状态'), visible: false, operate: false},
                        {field: 'sales_num', title: __('最近30天销量'), visible: false, operate: false},
                        {field: 'goods_grade',title: __('产品等级'),visible: false, operate: false},
                        {field: 'stock', title: __('库存量'), visible: false, operate: false},
                        {field: 'turn_days', title: __('周转月数'), visible: false, operate: false},
                        {
                            field: 'big_spot_goods',
                            title: __('大货/现货'),
                            custom: {1: 'danger', 2: 'success'},
                            searchList: {1: '大货', 2: '现货'},
                            formatter: Table.api.formatter.status,
                            visible: false,
                            operate: false
                        },
                    ]
                ],
            });
             // 为表格绑定事件
            Table.api.bindevent(table);
            $('.nav-choose ul li ul li').eq(0).children('input').prop('checked',true)
            //2001-10-23 00:00:00 - 2020-10-23 00:00:00
            $('.nav-choose ul li ul li').click(function(e){
                var data_name = $(this).attr('data-name');
                var field = $("#field").val();
                if(field){
                    var arr = field.split(',');
                }else{
                    var arr = [];
                }
                
                if($(this).children('input').prop('checked')){
                    $(this).children('input').prop('checked',false)
                    table.bootstrapTable("hideColumn", data_name);
                    arr.forEach((element,index) => {
                        if(element == data_name){
                            arr.splice(index,1)
                        }
                    });
                    console.log(arr)
                    if($.inArray(data_name,arr) != -1){
                        arr.splice($.inArray(data_name,arr),1);
                    }
                } else{
                    $(this).children('input').prop('checked',true)
                    table.bootstrapTable("showColumn", data_name);
                    if($.inArray(data_name,arr) == -1 && data_name){
                        arr.push(data_name);
                    }
                }
                $("#field").val(arr.join(","))

            
                if ($('#table thead tr').html() == '') {
                    $('.fixed-table-pagination').hide();
                    $('.fixed-table-toolbar').hide();
                }
            })
            $(".btn-success").click(function(){
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
            $(".reset").click(function(){
                $('#order_platform').val(1);
                $('#sku').val('');
                $('#type').val(0);
                $('#sales_status').val(0);
                $('#goods_grade').val(0);
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
                var sku = $('#sku').val();
                var type = $('#type').val();
                var sales_status = $('#sales_status').val();
                var goods_grade = $('#goods_grade').val();
                var field = $('#field').val();
                window.location.href=Config.moduleurl+'/operatedatacenter/newgoodsdata/goods_data_detail/export?order_platform='+order_platform+'&sku='+sku+'&type='+type+'&sales_status='+sales_status+'&goods_grade='+goods_grade+'&field='+field;
            });
        },
        add: function () {
            Controller.api.bindevent();
        },
        edit: function () {
            Controller.api.bindevent();
        },
        api: {
            formatter: {},
            bindevent: function () {
                Form.api.bindevent($("form[role=form]"));
            }
        }
    };
    return Controller;
});