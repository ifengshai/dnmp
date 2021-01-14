define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {

    var Controller = {
        index: function () {
            Controller.api.bindevent();
            // 初始化表格参数配置
            Table.api.init({
                extend: {
                    index_url: 'operatedatacenter/userdata/user_data_detail/index' + location.search,
                    add_url: 'operatedatacenter/userdata/user_data_detail/add',
                    edit_url: 'operatedatacenter/userdata/user_data_detail/edit',
                    del_url: 'operatedatacenter/userdata/user_data_detail/del',
                    multi_url: 'operatedatacenter/userdata/user_data_detail/multi',
                    table: 'user_data_detail',
                }
            });

            var table = $("#table");
            // 初始化表格
            table.bootstrapTable({
                url: $.fn.bootstrapTable.defaults.extend.index_url,
                pk: 'entity_id',
                sortName: 'entity_id',
                search: false,//通用搜索
                commonSearch: false,
                showToggle: false,
                showColumns: false,
                showExport: false,
                columns: [
                    [
                        {field: 'entity_id', title: __('用户ID'),visible: true,operate:false,sortable: true},
                        {field: 'email', title: __('注册邮箱'),visible: true,operate:false,sortable: true},
                        {field: 'created_at', title: __('注册时间'),visible: true,operate:false,sortable: true},
                        {field: 'order_num', title: __('总支付订单数'),visible: false,operate:false},
                        {field: 'order_amount', title: __('总订单金额'),visible: false,operate:false},
                        {field: 'point',title: __('积分余额'),visible: false,operate:false},
                        {field: 'coupon_order_num',title: __('使用优惠券订单数'),visible: false,operate:false},
                        {field: 'coupon_order_amount',title: __('使用优惠券订单金额'),visible: false,operate:false},
                        {field: 'first_order_time',title: __('首次下单时间'),visible: false,operate:false},
                        {field: 'last_order_time',title: __('最后一次下单时间'),visible: false,operate:false},
                        {field: 'recommend_order_num',title: __('推荐订单数'),visible: false,operate:false},
                        {field: 'recommend_register_num',title: __('推荐注册量'),visible: false,operate:false},
                    ]
                ],
            });
             // 为表格绑定事件
            Table.api.bindevent(table);
            
            $('.nav-choose ul li ul li').click(function(e){
                var data_name = $(this).attr('data-name');
                if(data_name != 'entity_id' && data_name != 'email' && data_name != 'created_at'){
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
                $('#time_str').val('');
                $('#customer_type').val(0);
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
                var customer_type = $('#customer_type').val();
                var field = $('#field').val();
                window.location.href=Config.moduleurl+'/operatedatacenter/userdata/user_data_detail/export?order_platform='+order_platform+'&customer_type='+customer_type+'&time_str='+time_str+'&field='+field;
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
        }
    };
    return Controller;
});