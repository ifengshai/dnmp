define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {

    var Controller = {
        index: function () {
            Controller.api.bindevent();
            // 初始化表格参数配置
            Table.api.init({
                extend: {
                    index_url: 'operatedatacenter/orderdata/order_data_detail/index' + location.search,
                    add_url: 'operatedatacenter/orderdata/order_data_detail/add',
                    edit_url: 'operatedatacenter/orderdata/order_data_detail/edit',
                    del_url: 'operatedatacenter/orderdata/order_data_detail/del',
                    multi_url: 'operatedatacenter/orderdata/order_data_detail/multi',
                    table: 'order_data_detail',
                }
            });

            var table = $("#table");
            var flag = 0;
            // 初始化表格
            table.bootstrapTable({
                url: $.fn.bootstrapTable.defaults.extend.index_url,
                pk: 'entity_id',
                sortName: 'entity_id',
                search: false,//通用搜索
                commonSearch: false,
                showToggle: false,
                showColumns: false,
                columns: [
                    [
                        {field: 'increment_id', title: __('订单编号'),visible: false,operate:false},
                        {field: 'created_at', title: __('订单时间'),visible: false,operate:false},
                        {field: 'base_grand_total', title: __('订单金额'),visible: false,operate:false},
                        {field: 'base_shipping_amount', title: __('邮费'),visible: false,operate:false},
                        {field: 'status', title: __('订单状态'),visible: false,operate:false},
                        //{field: 'assign_id', title: __('Assgin_id'),operate: false,visible:false},
                        {field: 'store_id', title: __('设备类型'), custom: { 1: 'danger', 2: 'success', 3: 'blue', 4: 'orange'}, searchList: { 1: 'PC', 4: 'M', 5: 'IOS', 6: 'Android'}, formatter: Table.api.formatter.store_id,visible: false,operate:false},
                        {field: 'protect_code',title: __('使用的code码'),visible: false,operate:false},
                        {field: 'shipping_method', title: __('快递类别'), custom: { 1: 'danger', 2: 'success'}, searchList: { 1: '平邮', 2: '商业快递'}, formatter: Table.api.formatter.store_id,visible: false,operate:false},
                        {field: 'shipping_name',title: __('收获姓名'),visible: false,operate:false},
                        {field: 'customer_email',title: __('支付邮箱'),visible: false,operate:false},
                        {field: 'customer_type',title: __('客户类型'),searchList: { 1: '普通', 2: '批发',4:'VIP' }, visible: false, formatter: Table.api.formatter.customer_type,operate:false},
                        {field: 'discount_rate',title: __('折扣百分比'),visible: false,operate:false},
                        {field: 'discount_money',title: __('折扣金额'),visible: false,operate:false},
                        {field: 'is_refund', title: __('有无退款'), custom: { 1: 'danger', 2: 'success'}, searchList: { 1: '有', 2: '无'}, formatter: Table.api.formatter.store_id,visible: false,operate:false},
                        {field: 'country_id',title: __('收获国家'),visible: false,operate:false},
                        {field: 'payment_method',title: __('支付方式'),visible: false,operate:false},
                        {field: 'frame_price',title: __('镜框价格'),visible: false,operate:false},
                        {field: 'frame_num',title: __('镜框数量'),visible: false,operate:false},
                        {field: 'lens_num',title: __('镜片数量'),visible: false,operate:false},
                        {field: 'is_box_num',title: __('配饰数量'),visible: false,operate:false},
                        {field: 'lens_price',title: __('镜片价格'),visible: false,operate:false},
                        {field: 'telephone',title: __('客户电话'),visible: false,operate:false},
                        {field: 'sku',title: __('商品sku'),visible: false,operate:false},
                        {field: 'register_time',title: __('注册时间'),visible: false,operate:false},
                        {field: 'register_email',title: __('注册邮箱'),visible: false,operate:false},
                        {field: 'work_list_num',title: __('工单数'),visible: false,operate:false},
                    ]
                ],
                onLoadSuccess: function (data) {
                    console.log(flag)
                    if(flag <= 1){
                        $('.fixed-table-pagination').hide()
                        $('.fixed-table-toolbar').hide()
                    }else{
                        $('.fixed-table-pagination').show()
                        $('.fixed-table-toolbar').show()
                    }
                    flag++;
                }
            });
             // 为表格绑定事件
            Table.api.bindevent(table);
            //2001-10-23 00:00:00 - 2020-10-23 00:00:00
            $('.nav-choose ul li ul li').click(function(e){
                var data_name = $(this).attr('data-name');

                if($(this).children('input').prop('checked')){
                    $(this).children('input').prop('checked',false)
                    table.bootstrapTable("hideColumn", data_name);
                } else{
                    $(this).children('input').prop('checked',true)
                    table.bootstrapTable("showColumn", data_name);
                }

                if ($('#table thead tr').html() != '') {
                    flag = 2;
                }
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