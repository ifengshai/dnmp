define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {

    var Controller = {
        index: function () {
            Controller.api.bindevent();
            // 初始化表格参数配置
            Table.api.init({
                extend: {
                    index_url: 'operatedatacenter/appdata/detail/index' + location.search,
                    add_url: 'operatedatacenter/appdata/detail/add',
                    edit_url: 'operatedatacenter/oappdata/detail/edit',
                    del_url: 'operatedatacenter/appdata/detail/del',
                    multi_url: 'operatedatacenter/appdata/detail/multi',
                    table: 'appdata_detail',
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
                pagination: false,
                columns: [
                    [
                        {field: 'date', title: __('日期'), visible: true, operate: false},
                        // {field: 'download_count_paid', title: __('付费下载数'), visible: true, operate: false},
                        // {field: 'ad_cost', title: __('花费'), visible: true, operate: false},
                        {field: 'sessions', title: __('会话数'), visible: true, operate: false},
                        {field: 'activeUsers', title: __('用户数'), visible: true, operate: false},
                        {field: 'first_open', title: __('首次打开'), visible: true, operate: false},
                        {field: 'order_money', title: __('订单金额'), visible: true, operate: false},
                        {field: 'order_num', title: __('订单数'),operate: false,visible:true},
                        {field: 'money_per_user', title: __('客单价'),operate: false,visible:true},
                    ]
                ],
            });
            // 为表格绑定事件
            Table.api.bindevent(table);
            $('.nav-choose ul li ul li').children('input').prop('checked',true)

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
                $('#site').val(1);
                $('#store_id').val(5);
                $('#date').val('');
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
                var site = $('#site').val();
                var store_id = $('#store_id').val();
                var date = $('#date').val();
                var field = $('#field').val();
                window.location.href=Config.moduleurl+'/operatedatacenter/appdata/detail/export?site='+site+'&store_id='+store_id+'&date='+date+'&field='+field;
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
                                    format: 'YYYY-MM-DD',
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
            bindevent: function () {
                Form.api.bindevent($("form[role=form]"));
            }
        }
    };
    return Controller;
});