define(['jquery', 'bootstrap', 'backend', 'table', 'jqui', 'form'], function ($, undefined, Backend, Table, undefined, Form) {

    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                extend: {
                    index_url: 'saleaftermanage/work_order_list/index' + location.search,
                    add_url: 'saleaftermanage/work_order_list/add',
                    edit_url: 'saleaftermanage/work_order_list/edit',
                    del_url: 'saleaftermanage/work_order_list/del',
                    multi_url: 'saleaftermanage/work_order_list/multi',
                    table: 'work_order_list',
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
                        { field: 'id', title: __('Id') },
                        { field: 'work_platform', title: __('Work_platform') },
                        { field: 'work_type', title: __('Work_type') },
                        { field: 'platform_order', title: __('Platform_order') },
                        { field: 'order_pay_currency', title: __('Order_pay_currency') },
                        { field: 'order_sku', title: __('Order_sku') },
                        { field: 'work_status', title: __('Work_status') },
                        { field: 'work_level', title: __('Work_level') },
                        { field: 'problem_type_id', title: __('Problem_type_id') },
                        { field: 'problem_type_content', title: __('Problem_type_content') },
                        { field: 'problem_description', title: __('Problem_description') },
                        { field: 'create_id', title: __('Create_id') },
                        { field: 'handle_person', title: __('Handle_person') },
                        { field: 'is_check', title: __('Is_check') },
                        { field: 'check_person_id', title: __('Check_person_id') },
                        { field: 'operation_person', title: __('Operation_person') },
                        { field: 'shenhe_beizhu', title: __('Shenhe_beizhu') },
                        { field: 'create_time', title: __('Create_time'), operate: 'RANGE', addclass: 'datetimerange', formatter: Table.api.formatter.datetime },
                        { field: 'check_time', title: __('Check_time'), operate: 'RANGE', addclass: 'datetimerange', formatter: Table.api.formatter.datetime },
                        { field: 'complete_time', title: __('Complete_time'), operate: 'RANGE', addclass: 'datetimerange', formatter: Table.api.formatter.datetime },
                        { field: 'operate', title: __('Operate'), table: table, events: Table.api.events.operate, formatter: Table.api.formatter.operate }
                    ]
                ]
            });

            // 为表格绑定事件
            Table.api.bindevent(table);
        },
        add: function () {
            Controller.api.bindevent();

            //点击事件 #todo::需判断仓库或者客服
            $(document).on('click', '.problem_type', function () {
                $('.step').attr('checked', false);
                $('.step').parent().hide();
                var id = $(this).val();
                //id大于5 默认措施4
                if (id > 5) {
                    var steparr = Config.workorder['step04'];
                    for (var j = 0; j < steparr.length; j++) {
                        $('#step' + steparr[j].step_id).parent().show();
                    }
                } else {
                    var step = Config.workorder.customer_problem_group[id].step;
                    var steparr = Config.workorder[step];
                    for (var j = 0; j < steparr.length; j++) {
                        $('#step' + steparr[j].step_id).parent().show();
                    }
                }
            });

            $(document).on('click', '.step_type', function () {
                var id = $(this).val();
                if ($(this).prop('checked')) {
                    $('#step_function .step' + id).show();
                } else {
                    $('#step_function .step' + id).hide();
                }
            });

            //模糊匹配订单号
            $('#c-platform_order').autocomplete({
                source: function (request, response) {
                    var incrementId = $('#c-platform_order').val();
                    if (incrementId.length > 4) {
                        $.ajax({
                            type: "POST",
                            url: "ajax/ajaxGetLikeOrder",
                            dataType: "json",
                            cache: false,
                            async: false,
                            data: {
                                order_number: incrementId
                            },
                            success: function (json) {
                                var data = json.data;
                                response($.map(data, function (item) {
                                    return {
                                        label: item,//下拉框显示值
                                        value: item,//选中后，填充到input框的值
                                        //id:item.bankCodeInfo//选中后，填充到id里面的值
                                    };
                                }));
                            }
                        });
                    }
                },
                delay: 10,//延迟100ms便于输入
                select: function (event, ui) {
                    $("#bankUnionNo").val(ui.item.id);//取出在return里面放入到item中的属性
                },
                scroll: true,
                pagingMore: true,
                max: 5000
            });

            //失去焦点
            $('#c-platform_order').blur(function () {
                var incrementId = $(this).val();
                if (!incrementId) {
                    Toastr.error('订单号不能为空');
                    return false;
                }
                var str = incrementId.substring(0, 3);
                //判断站点
                if (str == '100' || str == '400' || str == '500') {
                    $("#c-work_platform").val(1);
                } else if (str == '130' || str == '430') {
                    $('#c-work_platform').val(2);
                } else if (str == '300' || str == '600') {
                    $('#c-work_platform').val(3);
                }
                $('.selectpicker ').selectpicker('refresh');
                var ordertype = $('#c-work_platform').val();
                $('#c-order_sku').html('');
                Backend.api.ajax({
                    url: 'saleaftermanage/work_order_list/get_sku_list',
                    data: {
                        ordertype: ordertype,
                        order_number: incrementId
                    }
                }, function (data, ret) {
                    var shtml = '<option value="">请选择</option>';
                    for (var i in data) {
                        shtml += '<option value="' + data[i] + '">' + data[i] + '</option>'
                    }
                    $('#c-order_sku').append(shtml);
                    $('.selectpicker ').selectpicker('refresh');
                })

            })



        },
        edit: function () {
            Controller.api.bindevent();
        },
        api: {
            bindevent: function () {
                Form.api.bindevent($("form[role=form]"));
            }
        }
    };
    return Controller;
});