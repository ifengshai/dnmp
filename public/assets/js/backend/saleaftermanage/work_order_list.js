define(['jquery', 'bootstrap', 'backend', 'table', 'jqui', 'form'], function ($, undefined, Backend, Table, undefined, Form) {

    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                showJumpto: true,
                searchFormVisible: true,
                pageList: [10, 25, 50, 100],
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
                        { field: 'work_platform', title: __('work_platform'), custom: { 1: 'blue', 2: 'danger', 3: 'orange' }, searchList: { 1: 'Zeelool', 2: 'Voogueme', 3: 'Nihao' }, formatter: Table.api.formatter.status },
                        { field: 'work_type_str', title: __('Work_type') },
                        { field: 'platform_order', title: __('Platform_order') },
                        /*{ field: 'order_sku', title: __('Order_sku') },*/

                        {
                            field: 'order_sku',
                            title: __('Order_sku'),
                            operate: false,
                            formatter: function (value, rows) {
                                var all_user_name = '';
                                if (value) {
                                    for (i = 0, len = rows.order_sku_arr.length; i < len; i++) {
                                        all_user_name += '<div class="step_recept"><b class="recept">' + rows.order_sku_arr[i] + '</b></div>';
                                    }
                                } else {
                                    all_user_name = '-';
                                }
                                return all_user_name;
                            },
                        },

                        { field: 'work_level', title: __('Work_level'), custom: { 1: 'success', 2: 'orange', 3: 'danger' }, searchList: { 1: '低', 2: '中', 3: '高' }, formatter: Table.api.formatter.status },
                        { field: 'problem_type_content', title: __('Problem_type_content') },
                        { field: 'is_check', title: __('Is_check'), custom: { 0: 'black', 1: 'success' }, searchList: { 0: '否', 1: '是' }, formatter: Table.api.formatter.status },

                        /*{ field: 'create_user_name', title: __('create_user_name') },*/
                        {
                            field: 'create_user_name',
                            title: __('about_user'),
                            operate: false,
                            formatter: function (value, rows) {
                                var all_user_name = '';
                                all_user_name += '<div class="step_recept"><b class="step">工单创建人：</b><b class="recept">' + rows.create_user_name + '</b></div>';
                                if (rows.is_check == 1) {
                                    all_user_name += '<div class="step_recept"><b class="step">直接审核人：</b><b class="recept">' + rows.assign_user_name + '</b></div>';
                                    if (rows.operation_user_id != 0) {
                                        all_user_name += '<div class="step_recept"><b class="step">实际审核人：</b><b class="recept">' + rows.operation_user_name + '</b></div>';
                                    }

                                }

                                return all_user_name;
                            },
                        },
                        {
                            field: 'after_user_id',
                            title: __('recept_user'),
                            operate: false,
                            formatter: function (value, rows) {
                                var all_user_name = '';

                                if (rows.work_type == 2 && rows.is_after_deal_with == 0) {
                                    all_user_name += '<div class="step_recept"><b class="recept">' + rows.after_user_name + '</b></div>';
                                } else {
                                    if (rows.step_num) {
                                        for (i = 0, len = rows.step_num.length; i < len; i++) {
                                            if (rows.step_num[i].recept_user == '') {
                                                rows.step_num[i].recept_user = 'system';
                                            }
                                            all_user_name += '<div class="step_recept"><b class="step">' + rows.step_num[i].measure_content + '：</b><b class="recept">' + rows.step_num[i].recept_user + '</b></div>';
                                        }
                                    }
                                }
                                return all_user_name;
                            },
                        },
                        {
                            field: 'step_num',
                            title: __('step_status'),
                            operate: false,
                            formatter: function (value, rows) {
                                var all_user_name = '';
                                if (value) {
                                    for (i = 0, len = value.length; i < len; i++) {
                                        if (value[i].operation_type == 0) {
                                            all_user_name += '<div class="step_recept"><b class="step">' + value[i].measure_content + '：</b><b class="recept">未处理</b></div>';
                                        }
                                        if (value[i].operation_type == 1) {
                                            all_user_name += '<div class="step_recept"><b class="step">' + value[i].measure_content + '：</b><b class="recept">处理成功</b></div>';
                                        }
                                        if (value[i].operation_type == 2) {
                                            all_user_name += '<div class="step_recept"><b class="step">' + value[i].measure_content + '：</b><b class="recept">处理失败</b></div>';
                                        }
                                    }
                                }
                                return all_user_name;
                            },
                        },

                        { field: 'work_status', title: __('work_status'), custom: { 0: 'black', 1: 'danger', 2: 'success', 4: 'success', 3: 'success', 5: 'success', 6: 'success', 7: 'success', 8: 'success' }, searchList: { 0: '已取消', 1: '新建', 2: '待审核', 4: '审核拒绝', 3: '待处理', 5: '部分处理', 6: '处理完成', 7: '处理失败', 8: '已撤销' }, formatter: Table.api.formatter.status },
                        {
                            field: 'create_time',
                            title: __('time_str'),
                            operate: false,
                            formatter: function (value, rows) {
                                var all_user_name = '';
                                all_user_name += '<div class="step_recept"><b class="step">创建时间：</b><b class="recept">' + value + '</b></div>';

                                return all_user_name;
                            },
                        },

                        /* { field: 'create_time', title: __('Create_time'), operate: 'RANGE', addclass: 'datetimerange', formatter: Table.api.formatter.datetime },
                         { field: 'check_time', title: __('Check_time'), operate: 'RANGE', addclass: 'datetimerange', formatter: Table.api.formatter.datetime },
                         { field: 'complete_time', title: __('Complete_time'), operate: 'RANGE', addclass: 'datetimerange', formatter: Table.api.formatter.datetime },*/
                        {
                            field: 'buttons',
                            width: "120px",
                            operate: false,
                            title: __('操作'),
                            table: table,
                            events: Table.api.events.operate,
                            buttons: [
                                {
                                    name: 'detail',
                                    text: '详情',
                                    title: __('查看详情'),
                                    extend: 'data-area = \'["100%","100%"]\'',
                                    classname: 'btn btn-xs btn-primary btn-dialog',
                                    icon: 'fa fa-list',
                                    url: 'saleaftermanage/work_order_list/detail',
                                    callback: function (data) {
                                        Layer.alert("接收到回传数据：" + JSON.stringify(data), { title: "回传数据" });
                                    },
                                    visible: function (row) {
                                        //返回true时按钮显示,返回false隐藏
                                        return true;
                                    }
                                },
                                {
                                    name: 'del',
                                    text: __('删除'),
                                    title: __('删除'),
                                    classname: 'btn btn-xs btn-success btn-magic btn-ajax',
                                    url: 'demand/it_web_demand/del',
                                    success: function (data, ret) {
                                        table.bootstrapTable('refresh');
                                    },
                                    callback: function (data) {
                                    },
                                    visible: function (row) {
                                        if (row.status == 1 || row.status == 2) {
                                            if (row.demand_del && row.is_entry_user_hidden == 1) {//操作权限
                                                return true;
                                            }
                                        } else {
                                            return false;
                                        }
                                    }
                                },
                                {
                                    name: 'edit',
                                    text: __('编辑'),
                                    title: __('编辑'),
                                    classname: 'btn btn-xs btn-primary btn-dialog',
                                    url: 'demand/it_web_demand/edit/demand_type/2',
                                    success: function (data, ret) {
                                        table.bootstrapTable('refresh');
                                    },
                                    callback: function (data) {
                                    },
                                    visible: function (row) {
                                        if (row.status == 1 || row.status == 2) {
                                            if (row.demand_del && row.is_entry_user_hidden == 1) {//操作权限
                                                return true;
                                            }
                                        } else {
                                            return false;
                                        }
                                    }
                                },
                                {
                                    name: 'process',
                                    text: __('处理'),
                                    title: __('处理'),
                                    classname: 'btn btn-xs btn-success btn-dialog',
                                    url: 'saleaftermanage/work_order_list/add',
                                    extend: 'data-area = \'["100%","100%"]\'',
                                    callback: function (data) {
                                    },
                                    visible: function (row) {
                                        if (row.work_type == 2 && row.is_after_deal_with == 0) {
                                            return true;
                                        } else {
                                            return false;
                                        }
                                    }
                                }
                            ],
                            formatter: Table.api.formatter.buttons
                        },
                    ]
                ]
            });

            // 为表格绑定事件
            Table.api.bindevent(table);
            //选项卡切换
            $('.panel-heading a[data-toggle="tab"]').on('shown.bs.tab', function (e) {
                var field = $(this).data("field");
                var value = $(this).data("value");
                var options = table.bootstrapTable('getOptions');
                options.pageNumber = 1;
                var queryParams = options.queryParams;
                options.queryParams = function (params) {
                    var params = queryParams(params);
                    var filter = params.filter ? JSON.parse(params.filter) : {};
                    var op = params.op ? JSON.parse(params.op) : {};
                    if (field == 'create_person') {
                        delete filter.recept_person_id;
                        filter[field] = value;
                    } else if (field == 'recept_person_id') {
                        delete filter.create_person;
                        filter[field] = value;
                    } else {
                        delete filter.recept_person_id;
                        delete filter.create_person;
                    }
                    params.filter = JSON.stringify(filter);
                    params.op = JSON.stringify(op);
                    return params;
                };
                table.bootstrapTable('refresh', {});
                return false;
            });

        },
        add: function () {

            Controller.api.bindevent();

            //提交审核按钮
            $('.btn-status').click(function () {
                $('.status').val(2);
            })

            //点击事件 #todo::需判断仓库或者客服
            $(document).on('click', '.problem_type', function () {
                //读取是谁添加的配置console.log(Config.work_type);
                $('.step_type').attr('checked', false);
                $('.step_type').parent().hide();
                $('#appoint_group_users').html('');//切换问题类型时清空承接人
                $('#recept_person').val('');//切换问题类型时清空隐藏域承接人
                $('.measure').hide();
                $('#recept_group_id').val('');
                if (2 == Config.work_type) { //如果是仓库人员添加的工单
                    $('#step_id').hide();
                    $('#recept_person_group').hide();
                    $('#after_user_group').show();
                    $('#after_user_id').val(Config.workorder.copy_group);
                    $('#after_user').html(Config.users[Config.workorder.copy_group]);
                } else { //如果是客服人员添加的工单

                    var id = $(this).val();
                    //id大于5 默认措施4
                    if (id > 5) {
                        var steparr = Config.workorder['step04'];
                        for (var j = 0; j < steparr.length; j++) {
                            $('#step' + steparr[j].step_id).parent().show();
                            //读取对应措施配置
                            $('#step' + steparr[j].step_id + '-is_check').val(steparr[j].is_check);
                            $('#step' + steparr[j].step_id + '-appoint_group').val((steparr[j].appoint_group).join(','));
                        }
                    } else {
                        var step = Config.workorder.customer_problem_group[id].step;
                        var steparr = Config.workorder[step];
                        console.log(steparr);
                        for (var j = 0; j < steparr.length; j++) {
                            $('#step' + steparr[j].step_id).parent().show();
                            //读取对应措施配置
                            $('#step' + steparr[j].step_id + '-is_check').val(steparr[j].is_check);
                            $('#step' + steparr[j].step_id + '-appoint_group').val((steparr[j].appoint_group).join(','));
                        }
                    }
                    var checkID = [];//定义一个空数组
                    $("input[name='row[measure_choose_id][]']:checked").each(function (i) {
                        checkID[i] = $(this).val();
                    });
                    for (var m = 0; m < checkID.length; m++) {
                        var node = $('.step' + checkID[m]);
                        if (node.is(':hidden')) {
                            node.show();
                        } else {
                            node.hide();
                        }
                        var secondNode = $('.step' + id + '-' + checkID[m]);
                        if (secondNode.is(':hidden')) {
                            secondNode.show();
                        } else {
                            secondNode.hide();
                        }
                    }
                    //判断更换镜框的状态，如果显示的话把原数据带出来，如果隐藏则不显示原数据 start
                    if (!$('.step1-1').is(':hidden')) {
                        changeFrame()
                    }
                    //判断更换镜框的状态，如果显示的话把原数据带出来，如果隐藏则不显示原数据 end
                    //判断取消订单的状态，如果显示的话把原数据带出来，如果隐藏则不显示原数据 start
                    if (!$('.step3').is(':hidden')) {
                        cancelOrder();
                    }
                    //判断取消订单的状态，如果显示的话把原数据带出来，如果隐藏则不显示原数据 end                   
                }
            })

            //根据措施类型显示隐藏
            $(document).on('click', '.step_type', function () {
                $("#input-hidden").html('');
                var incrementId = $('#c-platform_order').val();
                if (!incrementId) {
                    Toastr.error('订单号不能为空');
                    return false;
                } else {
                    $('.measure').hide();
                    var problem_type_id = $("input[name='row[problem_type_id]']:checked").val();
                    var checkID = [];//定义一个空数组
                    var appoint_group = '';
                    var input_content = '';
                    $("input[name='row[measure_choose_id][]']:checked").each(function (i) {
                        checkID[i] = $(this).val();
                        var id = $(this).val();
                        //获取承接组
                        appoint_group += $('#step' + id + '-appoint_group').val() + ',';
                        var group = $('#step' + id + '-appoint_group').val();
                        var group_arr = group.split(',')
                        var appoint_users = [];
                        var appoint_val = [];
                        for (var i = 0; i < group_arr.length; i++) {
                            //循环根据承接组Key获取对应承接人id
                            appoint_users.push(Config.workorder[group_arr[i]]);
                            appoint_val[Config.workorder[group_arr[i]]] = group_arr[i];
                        }

                        //循环根据承接人id获取对应人名称
                        for (var j = 0; j < appoint_users.length; j++) {
                            input_content += '<input type="hidden" name="row[order_recept][appoint_group][' + id + '][]" value="' + appoint_val[appoint_users[j]] + '"/>';
                            input_content += '<input type="hidden" name="row[order_recept][appoint_ids][' + id + '][]" value="' + appoint_users[j] + '"/>';
                            input_content += '<input type="hidden" name="row[order_recept][appoint_users][' + id + '][]" value="' + Config.users[appoint_users[j]] + '"/>';
                        }

                        //获取是否需要审核
                        if ($('#step' + id + '-is_check').val() > 0) {
                            $('#is_check').val(1);
                        } else {
                            $('#is_check').val(0);
                        }
                    });
                    //追加到元素之后
                    $("#input-hidden").append(input_content);

                    

                    //一般措施
                    for (var m = 0; m < checkID.length; m++) {
                        var node = $('.step' + checkID[m]);
                        if (node.is(':hidden')) {
                            node.show();
                        } else {
                            node.hide();
                        }
                        //二级措施
                        var secondNode = $('.step' + problem_type_id + '-' + checkID[m]);
                        if (secondNode.is(':hidden')) {
                            secondNode.show();
                        } else {
                            secondNode.hide();
                        }
                    }

                    //判断如果为处理任务时
                    if (Config.ids) {
                        if (problem_type_id == 1) {
                            $('.measure').hide();
                            $('.step2-1').show();
                        } else if (problem_type_id == 2) {
                            $('.measure').hide();
                            $('.step1-1').show();
                        }  else if (problem_type_id == 3) {
                            $('.measure').hide();
                            $('.step1-1').show();
                        }
                    }

                    var id = $(this).val();
                    var arr = array_filter(appoint_group.split(','));
                    var username = [];
                    var appoint_users = [];
                    //循环根据承接组Key获取对应承接人id
                    for (var i = 0; i < arr.length - 1; i++) {
                        //循环根据承接组Key获取对应承接人id
                        appoint_users.push(Config.workorder[arr[i]]);
                    }

                    //循环根据承接人id获取对应人名称
                    for (var j = 0; j < appoint_users.length; j++) {
                        username.push(Config.users[appoint_users[j]]);
                    }

                    var users = array_filter(username);
                    var appoint_users = array_filter(appoint_users);
                    $('#appoint_group_users').html(users.join(','));
                    $('#recept_person_id').val(appoint_users.join(','));

                    //判断更换镜框的状态，如果显示的话把原数据带出来，如果隐藏则不显示原数据 start
                    if (!$('.step1-1').is(':hidden')) {
                        changeFrame();
                    }
                    //判断更换镜框的状态，如果显示的话把原数据带出来，如果隐藏则不显示原数据 end
                    //判断取消订单的状态，如果显示的话把原数据带出来，如果隐藏则不显示原数据 start
                    if (!$('.step3').is(':hidden')) {
                        cancelOrder();
                    }
                    //判断取消订单的状态，如果显示的话把原数据带出来，如果隐藏则不显示原数据 end
                }
            });
            //增加一行镜架数据
            $(document).on('click', '.btn-add-frame', function () {
                var rows = document.getElementById("caigou-table-sku").rows.length;
                var content = '<tr>' +
                    '<td><input class="form-control" name="row[change_frame][original_sku][]" type="text"></td>' +
                    '<td><input class="form-control" name="row[change_frame][original_number][]" type="text"></td>' +
                    '<td><input class="form-control change_sku" name="row[change_frame][change_sku][]" type="text"></td>' +
                    '<td><input class="form-control change_number" name="row[change_frame][change_number][]" type="text"></td>' +
                    '<td><a href="javascript:;" class="btn btn-danger btn-del" title="删除"><i class="fa fa-trash"></i> 删除</a></td>' +
                    '</tr>';
                $('#caigou-table-sku tbody').append(content);
            });
            //增加一行镜片数据
            $(document).on('click', '.btn-add-lens', function () {
                var contents = $('#edit_lens').html();
                $('#lens_contents').after(contents);
                Controller.api.bindevent();
            });
            //删除一行镜片数据
            $(document).on('click', '.btn-del-lens', function () {
                $(this).parent().parent().remove();
            });

            //赠品 start
            // $(document).on('click', '.btn-add-box', function () {
            //     var option = $('#add_box_option').html();
            //     var str = '<div><label style="margin-top: 10px;" class="control-label col-xs-12 col-sm-2">SKU：</label>\n' +
            //         '                    <div style="margin-top: 10px;" class="col-xs-12 col-sm-8">\n' +
            //         '                        <div class="dropup">\n' +
            //         '                            <select class="selectpicker" name="row[order_change][change_sku][]" data-live-search="true" title="请选择">\n' + option +
            //         '                            </select>\n' +
            //         '                        </div>\n' +
            //         '                    </div>\n' +
            //         '                    <label style="margin-top: 10px;" class="control-label col-xs-12 col-sm-2">数量：</label>\n' +
            //         '                    <div style="margin-top: 10px;" class="col-xs-12 col-sm-8">\n' +
            //         '                        <input class="form-control" name="row[order_change][change_number][]" type="text" value="">\n' +
            //         '                        <a href="javascript:;" class="btn btn-danger btn-del-box" title="删除"><i class="fa fa-trash"></i>删除</a>\n' +
            //         '                    </div></div>';
            //     $('#add_box').append(str);
            //     Controller.api.bindevent();
            // });
            $(document).on('click', '.btn-del-box', function () {
                $(this).parent().parent().remove();
            });
            //赠品 end

            //补发 start
            $(document).on('click', '.btn-add-supplement', function () {
                var contents = $('#edit_lens').html();
                $('#supplement-order').after(contents);
                Controller.api.bindevent();
            });
            $(document).on('click', '.btn-del-supplement', function () {
                $(this).parent().parent().remove();
            });
            //补发 end

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
                    $("#work_platform").val(1);
                } else if (str == '130' || str == '430') {
                    $('#work_platform').val(2);
                } else if (str == '300' || str == '600') {
                    $('#work_platform').val(3);
                }
                $('.selectpicker ').selectpicker('refresh');

            })

            //根据订单号获取数据
            $('#platform_order').click(function () {
                var incrementId = $('#c-platform_order').val();
                var sitetype = $('#work_platform').val();
                $('#c-order_sku').html('');
                Backend.api.ajax({
                    url: 'saleaftermanage/work_order_list/get_sku_list',
                    data: {
                        sitetype: sitetype,
                        order_number: incrementId
                    }
                }, function (data, ret) {
                    $('#order_pay_currency').val(data.base_currency_code);
                    $('#order_pay_method').val(data.method);
                    $('#c-refund_way').val(data.method);
                    $('#customer_email').val(data.customer_email);
                    var shtml = '<option value="">请选择</option>';
                    for (var i in data.sku) {
                        shtml += '<option value="' + data.sku[i] + '">' + data.sku[i] + '</option>'
                    }
                    $('#c-order_sku').append(shtml);
                    $('.selectpicker ').selectpicker('refresh');
                    //判断更换镜框的状态，如果显示的话把原数据带出来，如果隐藏则不显示原数据 start
                    if (!$('.step1-1').is(':hidden')) {
                        changeFrame()
                    }
                    //判断更换镜框的状态，如果显示的话把原数据带出来，如果隐藏则不显示原数据 end
                    //判断取消订单的状态，如果显示的话把原数据带出来，如果隐藏则不显示原数据 start
                    if (!$('.step3').is(':hidden')) {
                        cancelOrder();
                    }
                    //判断取消订单的状态，如果显示的话把原数据带出来，如果隐藏则不显示原数据 end                                        
                });
            })
            //补发点击填充数据
            var lens_click_data;
            var gift_click_data;
            var prescriptions;
            $(document).on('click', 'input[name="row[measure_choose_id][]"]', function () {

                var value = $(this).val();
                var check = $(this).prop('checked');
                var increment_id = $('#c-platform_order').val();
                if (increment_id) {
                    var site_type = $('#work_platform').val();
                    //补发
                    if (value == 7 && check === true) {
                        //获取补发的信息
                        $.ajax({
                            type: "POST",
                            url: "saleaftermanage/work_order_list/ajaxGetAddress",
                            dataType: "json",
                            cache: false,
                            async: false,
                            data: {
                                increment_id: increment_id,
                                site_type: site_type,
                            },
                            success: function (json) {
                                if (json.code == 0) {
                                    Toastr.error(json.msg);
                                    return false;
                                }
                                var data = json.data.address;
                                var lens = json.data.lens;
                                prescriptions = data.prescriptions;
                                $('#supplement-order').html(lens.html);
                                var order_pay_currency = $('#order_pay_currency').val();
                                //修改地址
                                var address = '';
                                for (var i = 0; i < data.address.length; i++) {
                                    if (i == 0) {
                                        address += '<option value="' + i + '" selected>' + data.address[i].address_type + '</option>';
                                        //补发地址自动填充第一个
                                        $('#c-firstname').val(data.address[i].firstname);
                                        $('#c-lastname').val(data.address[i].lastname);
                                        $('#c-email').val(data.address[i].email);
                                        $('#c-telephone').val(data.address[i].telephone);
                                        $('#c-country').val(data.address[i].country_id);
                                        $('#c-country').change();
                                        $('#c-region').val(data.address[i].region_id);
                                        $('#c-city').val(data.address[i].city);
                                        $('#c-street').val(data.address[i].street);
                                        $('#c-postcode').val(data.address[i].postcode);
                                        $('#c-currency_code').val(order_pay_currency);
                                    } else {
                                        address += '<option value="' + i + '">' + data.address[i].address_type + '</option>';
                                    }

                                }
                                $('#address_select').html(address);
                                //选择地址切换地址
                                $('#address_select').change(function () {
                                    var address_id = $(this).val();
                                    var address = data.address[address_id];
                                    $('#c-firstname').val(address.firstname);
                                    $('#c-lastname').val(address.lastname);
                                    $('#c-email').val(address.email);
                                    $('#c-telephone').val(address.telephone);
                                    $('#c-country').val(address.country_id);
                                    $('#c-country').change();
                                    $('#c-region').val(address.region_id);
                                    $('#c-city').val(address.city);
                                    $('#c-street').val(address.street);
                                    $('#c-postcode').val(address.postcode);
                                })

                                //追加
                                lens_click_data = '<div class="margin-top:10px;">' + lens.html + '<div class="form-group-child4_del"><a href="javascript:;" style="width: 50%;" class="btn btn-danger btn-del-lens" title="删除"><i class="fa fa-trash"></i>删除</a></div></div>';

                                $('.selectpicker ').selectpicker('refresh');
                                Controller.api.bindevent();
                            }
                        });
                    }
                    //更加镜架的更改
                    var question = $('input[name="row[problem_type_id]"]:checked').val();
                    if (value == 1 && question == 2 && check === true) {
                        Backend.api.ajax({
                            url: 'saleaftermanage/work_order_list/ajaxGetChangeLens',
                            data: {
                                increment_id: increment_id,
                                site_type: site_type,
                            }
                        }, function (data, ret) {
                            $('#lens_contents').html(data.html);
                            $('.selectpicker ').selectpicker('refresh');
                        });
                    }
                    //赠品
                    if (value == 6 && check == true) {
                        Backend.api.ajax({
                            url: 'saleaftermanage/work_order_list/ajaxGetGiftLens',
                            data: {
                                increment_id: increment_id,
                                site_type: site_type,
                            }
                        }, function (data, ret) {
                            $('.add_gift').html(data.html);
                            //追加
                            gift_click_data = '<div class="margin-top:10px;">' + data.html + '<div class="form-group-child4_del"><a href="javascript:;" style="width: 50%;" class="btn btn-danger btn-del-lens" title="删除"><i class="fa fa-trash"></i>删除</a></div></div>';
                            $('.selectpicker ').selectpicker('refresh');
                        });
                    }
                } else {
                    Toastr.error('请选选择订单号……');
                }

            });
            //处方选择填充
            $(document).on('change', '#prescription_select', function () {
                var val = $(this).val();
                var prescription = prescriptions[val];

                var prescription_div = $(this).parents('.step7_function2').next('.step1_function3');
                prescription_div.find('input').val('');
                prescription_div.find('input[name="row[replacement][od_sph][]"]').val(prescription.od_sph);
                prescription_div.find('input[name="row[replacement][os_sph][]"]').val(prescription.os_sph);
                prescription_div.find('input[name="row[replacement][os_cyl][]"]').val(prescription.os_cyl);
                prescription_div.find('input[name="row[replacement][od_cyl][]"]').val(prescription.od_cyl);
                prescription_div.find('input[name="row[replacement][od_axis][]"]').val(prescription.od_axis);
                prescription_div.find('input[name="row[replacement][os_axis][]"]').val(prescription.os_axis);

                //$(this).parents('.step7_function2').val('')
                $(this).parents('.step7_function2').find('select[name="row[replacement][recipe_type][]"]').val(prescription.prescription_type);
                $(this).parents('.step7_function2').find('select[name="row[replacement][recipe_type][]"]').change();
                prescription_div.find('select[name="row[replacement][coating_type][]"]').val(prescription.coating_id);



                //判断是否是彩色镜片
                if (prescription.color_id > 0) {
                    prescription_div.find('#color_type').val(prescription.color_id);
                    prescription_div.find('#color_type').change();
                }
                prescription_div.find('#lens_type').val(prescription.index_id);
                //add，pd添加
                if (prescription.hasOwnProperty("total_add")) {
                    prescription_div.find('input[name="row[replacement][od_add][]"]').val(prescription.total_add);
                    //prescription_div.find('input[name="row[replacement][os_add][]"]').attr('disabled',true);
                } else {
                    prescription_div.find('input[name="row[replacement][od_add][]"]').val(prescription.od_add);
                    prescription_div.find('input[name="row[replacement][os_add][]"]').val(prescription.os_add);
                }
                if (prescription.hasOwnProperty("pd")) {
                    prescription_div.find('input[name="row[replacement][pd_r][]"]').val(prescription.pd);
                    //prescription_div.find('input[name="row[replacement][pd_l][]"]').attr('disabled',true);
                } else {
                    prescription_div.find('input[name="row[replacement][pd_r][]"]').val(prescription.pd_r);
                    prescription_div.find('input[name="row[replacement][pd_l][]"]').val(prescription.pd_l);
                }
                //
                if (prescription.hasOwnProperty("od_pv")) {
                    prescription_div.find('input[name="row[replacement][od_pv][]"]').val(prescription.od_pv);
                }
                if (prescription.hasOwnProperty("od_bd")) {
                    prescription_div.find('input[name="row[replacement][od_bd][]"]').val(prescription.od_bd);
                }
                if (prescription.hasOwnProperty("od_pv_r")) {
                    prescription_div.find('input[name="row[replacement][od_pv_r][]"]').val(prescription.od_pv_r);
                }
                if (prescription.hasOwnProperty("od_bd_r")) {
                    prescription_div.find('input[name="row[replacement][od_bd_r][]"]').val(prescription.od_bd_r);
                }
                if (prescription.hasOwnProperty("os_pv")) {
                    prescription_div.find('input[name="row[replacement][os_pv][]"]').val(prescription.os_pv);
                }
                if (prescription.hasOwnProperty("os_bd")) {
                    prescription_div.find('input[name="row[replacement][os_bd][]"]').val(prescription.os_bd);
                }
                if (prescription.hasOwnProperty("os_pv_r")) {
                    prescription_div.find('input[name="row[replacement][os_pv_r][]"]').val(prescription.os_pv_r);
                }
                if (prescription.hasOwnProperty("od_pv")) {
                    prescription_div.find('input[name="row[replacement][os_bd_r][]"]').val(prescription.os_bd_r);
                }

                $('.selectpicker ').selectpicker('refresh');
            })
            $(document).on('click', '.btn-add-box', function () {
                $('.add_gift').after(gift_click_data);
                $('.selectpicker ').selectpicker('refresh');
                Controller.api.bindevent();
            });
            $(document).on('click', '.btn-add-supplement-reissue', function () {
                $('#supplement-order').after(lens_click_data);
                $('.selectpicker ').selectpicker('refresh');
                Controller.api.bindevent();
            });
            //根据prescription_type获取lens_type
            $(document).on('change', 'select[name="row[replacement][recipe_type][]"],select[name="row[change_lens][recipe_type][]"],select[name="row[gift][recipe_type][]"]', function () {
                var sitetype = $('#work_platform').val();
                var prescription_type = $(this).val();
                if (!sitetype || !prescription_type) {
                    return false;
                }
                var that = $(this);
                Backend.api.ajax({
                    url: 'saleaftermanage/work_order_list/ajaxGetLensType',
                    data: {
                        site_type: sitetype,
                        prescription_type: prescription_type
                    }
                }, function (data, ret) {
                    var prescription_div = that.parents('.prescription_type_step').next('div');
                    var lens_type;
                    for (var i = 0; i < data.length; i++) {
                        lens_type += '<option value="' + data[i].lens_id + '">' + data[i].lens_data_name + '</option>';
                    }
                    prescription_div.find('#lens_type').html(lens_type);
                    prescription_div.find('#color_type').val('');
                    $('.selectpicker ').selectpicker('refresh');
                }, function (data, ret) {
                    var prescription_div = that.parents('.prescription_type_step').next('div');
                    prescription_div.find('#lens_type').html('');
                    $('.selectpicker ').selectpicker('refresh');
                }
                );
            });
            //根据color_type获取lens_type
            $(document).on('change', 'select[name="row[replacement][color_id][]"],select[name="row[change_lens][color_id][]"],select[name="row[gift][color_id][]"]', function () {
                var sitetype = $('#work_platform').val();
                var color_id = $(this).val();
                var that = $(this);
                Backend.api.ajax({
                    url: 'saleaftermanage/work_order_list/ajaxGetLensType',
                    data: {
                        site_type: sitetype,
                        color_id: color_id
                    }
                }, function (data, ret) {
                    var prescription_div = that.parents('.panel-body');
                    var color_type;
                    for (var i = 0; i < data.length; i++) {
                        color_type += '<option value="' + data[i].lens_id + '">' + data[i].lens_data_name + '</option>';
                    }
                    prescription_div.find('#lens_type').html(color_type);
                    $('.selectpicker ').selectpicker('refresh');
                }, function (data, ret) {
                    var prescription_div = that.parents('.step1_function3');
                    prescription_div.find('#lens_type').html('');
                    $('.selectpicker ').selectpicker('refresh');
                }
                );
            })

            //省市二级联动
            $(document).on('change', '#c-country', function () {
                var id = $(this).val();
                if (!id) {
                    return false;
                }
                $.ajax({
                    type: "POST",
                    url: "saleaftermanage/work_order_list/ajaxGetProvince",
                    dataType: "json",
                    cache: false,
                    async: false,
                    data: {
                        country_id: id,
                    },
                    success: function (json) {
                        var data = json.province;
                        var province = '';
                        for (var i = 0; i < data.length; i++) {
                            province += '<option value="' + data[i].region_id + '">' + data[i].default_name + '</option>';
                        }
                        $('#c-region').html(province);
                        $('.selectpicker ').selectpicker('refresh');
                    }
                });
            });

        },
        edit: function () {
            Controller.api.bindevent();
        },
        detail: function () {
            Controller.api.bindevent();
        },
        //处理任务
        process: function () {
            Controller.api.bindevent();
            //点击事件 #todo::需判断仓库或者客服
            $(document).on('click', '.problem_type', function () {
                $('.step_type').attr('checked', false);
                $('.step_type').parent().hide();
                $('#appoint_group_users').html('');//切换问题类型时清空承接人
                $('#recept_person_id').val('');//切换问题类型时清空隐藏域承接人id
                $('#recept_person').val('');//切换问题类型时清空隐藏域承接人
                $('.measure').hide();
                var id = $(this).val();

                //判断是客服创建还是仓库创建
                if (Config.work_type == 1) {
                    var temp_id = 5;
                } else if (Config.work_type == 2) {
                    var temp_id = 4;
                }

                //id大于5 默认措施4
                if (id > temp_id) {
                    var steparr = Config.workorder['step04'];
                    for (var j = 0; j < steparr.length; j++) {
                        $('#step' + steparr[j].step_id).parent().show();
                        //读取对应措施配置
                        $('#step' + steparr[j].step_id + '-is_check').val(steparr[j].is_check);
                        $('#step' + steparr[j].step_id + '-appoint_group').val((steparr[j].appoint_group).join(','));
                    }
                } else {
                    //判断是客服创建还是仓库创建
                    if (Config.work_type == 1) {
                        var step = Config.workorder.customer_problem_group[id].step;
                    } else if (Config.work_type == 2) {
                        var step = Config.workorder.warehouse_problem_group[id].step;
                    }

                    var steparr = Config.workorder[step];
                    console.log(steparr);
                    for (var j = 0; j < steparr.length; j++) {
                        $('#step' + steparr[j].step_id).parent().show();
                        //读取对应措施配置
                        $('#step' + steparr[j].step_id + '-is_check').val(steparr[j].is_check);
                        $('#step' + steparr[j].step_id + '-appoint_group').val((steparr[j].appoint_group).join(','));
                    }
                }
                var checkID = [];//定义一个空数组
                $("input[name='row[measure_choose_id][]']:checked").each(function (i) {
                    checkID[i] = $(this).val();
                });
                for (var m = 0; m < checkID.length; m++) {
                    var node = $('.step' + checkID[m]);
                    if (node.is(':hidden')) {
                        node.show();
                    } else {
                        node.hide();
                    }
                    var secondNode = $('.step' + id + '-' + checkID[m]);
                    if (secondNode.is(':hidden')) {
                        secondNode.show();
                    } else {
                        secondNode.hide();
                    }
                }
            })
        },
        api: {
            bindevent: function () {
                Form.api.bindevent($("form[role=form]"));
                //删除一行镜架数据
                $(document).on('click', '.btn-del', function () {
                    $(this).parent().parent().remove();
                });
                //点击事件 #todo::需判断仓库或者客服
                $(document).on('click', '.problem_type', function () {
                    //读取是谁添加的配置console.log(Config.work_type);
                    $('.step_type').attr('checked', false);
                    $('.step_type').parent().hide();
                    $('#appoint_group_users').html('');//切换问题类型时清空承接人
                    $('#recept_person_id').val('');//切换问题类型时清空隐藏域承接人id
                    $('#recept_person').val('');//切换问题类型时清空隐藏域承接人
                    $('.measure').hide();
                    $('#recept_group_id').val('');
                    if (2 == Config.work_type) { //如果是仓库人员添加的工单
                        $('#step_id').hide();
                        $('#recept_person_group').hide();
                        $('#after_user_group').show();
                        $('#after_user_id').val(Config.workorder.copy_group);
                        $('#after_user').html(Config.users[Config.workorder.copy_group]);
                    } else { //如果是客服人员添加的工单

                        var id = $(this).val();
                        //id大于5 默认措施4
                        if (id > 5) {
                            var steparr = Config.workorder['step04'];
                            for (var j = 0; j < steparr.length; j++) {
                                $('#step' + steparr[j].step_id).parent().show();
                                //读取对应措施配置
                                $('#step' + steparr[j].step_id + '-is_check').val(steparr[j].is_check);
                                $('#step' + steparr[j].step_id + '-appoint_group').val((steparr[j].appoint_group).join(','));
                            }
                        } else {
                            var step = Config.workorder.customer_problem_group[id].step;
                            var steparr = Config.workorder[step];
                            console.log(steparr);
                            for (var j = 0; j < steparr.length; j++) {
                                $('#step' + steparr[j].step_id).parent().show();
                                //读取对应措施配置
                                $('#step' + steparr[j].step_id + '-is_check').val(steparr[j].is_check);
                                $('#step' + steparr[j].step_id + '-appoint_group').val((steparr[j].appoint_group).join(','));
                            }
                        }
                        var checkID = [];//定义一个空数组
                        $("input[name='row[measure_choose_id][]']:checked").each(function (i) {
                            checkID[i] = $(this).val();
                        });
                        for (var m = 0; m < checkID.length; m++) {
                            var node = $('.step' + checkID[m]);
                            if (node.is(':hidden')) {
                                node.show();
                            } else {
                                node.hide();
                            }
                            var secondNode = $('.step' + id + '-' + checkID[m]);
                            if (secondNode.is(':hidden')) {
                                secondNode.show();
                            } else {
                                secondNode.hide();
                            }
                        }
                        //判断更换镜框的状态，如果显示的话把原数据带出来，如果隐藏则不显示原数据 start
                        if (!$('.step1-1').is(':hidden')) {
                            changeFrame()
                        }
                        //判断更换镜框的状态，如果显示的话把原数据带出来，如果隐藏则不显示原数据 end
                        //判断取消订单的状态，如果显示的话把原数据带出来，如果隐藏则不显示原数据 start
                        if (!$('.step3').is(':hidden')) {
                            cancelOrder();
                        }
                        //判断取消订单的状态，如果显示的话把原数据带出来，如果隐藏则不显示原数据 end                   
                    }
                })

                //根据措施类型显示隐藏
                $(document).on('click', '.step_type', function () {
                    $("#input-hidden").html('');
                    var incrementId = $('#c-platform_order').val();
                    if (!incrementId) {
                        Toastr.error('订单号不能为空');
                        return false;
                    } else {
                        $('.measure').hide();
                        var problem_type_id = $("input[name='row[problem_type_id]']:checked").val();
                        var checkID = [];//定义一个空数组
                        var appoint_group = '';
                        var input_content = '';
                        $("input[name='row[measure_choose_id][]']:checked").each(function (i) {
                            checkID[i] = $(this).val();
                            var id = $(this).val();
                            //获取承接组
                            appoint_group += $('#step' + id + '-appoint_group').val() + ',';
                            var group = $('#step' + id + '-appoint_group').val();
                            var group_arr = group.split(',')
                            var appoint_users = [];
                            var appoint_val = [];
                            for (var i = 0; i < group_arr.length; i++) {
                                //循环根据承接组Key获取对应承接人id
                                appoint_users.push(Config.workorder[group_arr[i]]);
                                appoint_val[Config.workorder[group_arr[i]]] = group_arr[i];
                            }

                            //循环根据承接人id获取对应人名称
                            for (var j = 0; j < appoint_users.length; j++) {
                                input_content += '<input type="hidden" name="row[order_recept][appoint_group][' + id + '][]" value="' + appoint_val[appoint_users[j]] + '"/>';
                                input_content += '<input type="hidden" name="row[order_recept][appoint_ids][' + id + '][]" value="' + appoint_users[j] + '"/>';
                                input_content += '<input type="hidden" name="row[order_recept][appoint_users][' + id + '][]" value="' + Config.users[appoint_users[j]] + '"/>';
                            }

                            //获取是否需要审核
                            if ($('#step' + id + '-is_check').val() > 0) {
                                $('#is_check').val(1);
                            }
                        });
                        //追加到元素之后
                        $("#input-hidden").append(input_content);
                        //一般措施
                        for (var m = 0; m < checkID.length; m++) {
                            var node = $('.step' + checkID[m]);
                            if (node.is(':hidden')) {
                                node.show();
                            } else {
                                node.hide();
                            }
                            //二级措施
                            var secondNode = $('.step' + problem_type_id + '-' + checkID[m]);
                            if (secondNode.is(':hidden')) {
                                secondNode.show();
                            } else {
                                secondNode.hide();
                            }
                        }
                        var id = $(this).val();
                        var arr = array_filter(appoint_group.split(','));
                        var username = [];
                        var appoint_users = [];
                        //循环根据承接组Key获取对应承接人id
                        for (var i = 0; i < arr.length - 1; i++) {
                            //循环根据承接组Key获取对应承接人id
                            appoint_users.push(Config.workorder[arr[i]]);
                        }

                        //循环根据承接人id获取对应人名称
                        for (var j = 0; j < appoint_users.length; j++) {
                            username.push(Config.users[appoint_users[j]]);
                        }

                        var users = array_filter(username);
                        $('#appoint_group_users').html(users.join(','));

                        //判断更换镜框的状态，如果显示的话把原数据带出来，如果隐藏则不显示原数据 start
                        if (!$('.step1-1').is(':hidden')) {
                            changeFrame();
                        }
                        //判断更换镜框的状态，如果显示的话把原数据带出来，如果隐藏则不显示原数据 end
                        //判断取消订单的状态，如果显示的话把原数据带出来，如果隐藏则不显示原数据 start
                        if (!$('.step3').is(':hidden')) {
                            cancelOrder();
                        }
                        //判断取消订单的状态，如果显示的话把原数据带出来，如果隐藏则不显示原数据 end
                    }
                });

                //如果问题类型存在，显示问题类型和措施
                if (Config.problem_type_id) {
                    var id = Config.problem_type_id;
                    var work_id = $('#work_id').val();
                    //row[problem_type_id]
                    $("input[name='row[problem_type_id]'][value='" + id + "']").attr("checked", true);

                    //判断是客服创建还是仓库创建
                    if (Config.work_type == 1) {
                        var temp_id = 5;
                    } else if (Config.work_type == 2) {
                        var temp_id = 4;
                    }

                    //id大于5 默认措施4
                    if (id > temp_id) {
                        var steparr = Config.workorder['step04'];
                        for (var j = 0; j < steparr.length; j++) {
                            $('#step' + steparr[j].step_id).parent().show();
                            //读取对应措施配置
                            $('#step' + steparr[j].step_id + '-is_check').val(steparr[j].is_check);
                            $('#step' + steparr[j].step_id + '-appoint_group').val((steparr[j].appoint_group).join(','));
                        }
                    } else {
                        //判断是客服创建还是仓库创建
                        if (Config.work_type == 1) {
                            var step = Config.workorder.customer_problem_group[id].step;
                        } else if (Config.work_type == 2) {
                            var step = Config.workorder.warehouse_problem_group[id].step;
                        }
                        var steparr = Config.workorder[step];
                        for (var j = 0; j < steparr.length; j++) {
                            $('#step' + steparr[j].step_id).parent().show();
                            //读取对应措施配置
                            $('#step' + steparr[j].step_id + '-is_check').val(steparr[j].is_check);
                            $('#step' + steparr[j].step_id + '-appoint_group').val((steparr[j].appoint_group).join(','));
                        }
                    }
                    if (Config.measureList) {
                        var checkID = Config.measureList;//措施列表赋值给checkID
                        for (var m = 0; m < checkID.length; m++) {
                            $("input[name='row[measure_choose_id][]'][value='" + checkID[m] + "']").attr("checked", true);
                            var node = $('.step' + checkID[m]);
                            if (node.is(':hidden')) {
                                node.show();
                            } else {
                                node.hide();
                            }
                            var secondNode = $('.step' + id + '-' + checkID[m]);
                            if (secondNode.is(':hidden')) {
                                secondNode.show();
                            } else {
                                secondNode.hide();
                            }
                        }
                    }

                    //判断更换镜框的状态，如果显示的话把原数据带出来，如果隐藏则不显示原数据 start
                    if (!$('.step1-1').is(':hidden')) {
                        changeFrame(1, work_id)
                    }
                    //判断更换镜框的状态，如果显示的话把原数据带出来，如果隐藏则不显示原数据 end
                    //判断取消订单的状态，如果显示的话把原数据带出来，如果隐藏则不显示原数据 start
                    if (!$('.step3').is(':hidden')) {
                        cancelOrder(1, work_id);
                    }
                    //判断取消订单的状态，如果显示的话把原数据带出来，如果隐藏则不显示原数据 end

                    
                }
            },
        }
    };
    return Controller;
});

//过滤数组重复项
function array_filter(arr) {
    var new_arr = [];
    for (var i = 0; i < arr.length; i++) {
        var items = arr[i];
        //判断元素是否存在于new_arr中，如果不存在则插入到new_arr的最后
        if ($.inArray(items, new_arr) == -1) {
            new_arr.push(items);
        }
    }
    return new_arr;
}
//js 函数读取更换镜架信息
function changeFrame(is_edit = 0, work_id = 0) {
    var ordertype = $('#work_platform').val();
    var order_number = $('#c-platform_order').val();
    if (!order_number) {
        return false;
    }
    if (ordertype <= 0) {
        Toastr.error('请选择正确的平台');
        return false;
    }
    if (1 == is_edit) { //是编辑的话
        var urls = 'saleaftermanage/work_order_list/ajax_edit_order';
        var datas = { ordertype: ordertype, order_number: order_number, work_id: work_id };
    } else { //是新增的话
        var urls = 'saleaftermanage/work_order_list/ajax_get_order';
        var datas = { ordertype: ordertype, order_number: order_number };
    }
    Backend.api.ajax({
        url: urls,
        data: datas
    }, function (data, ret) {
        if (!data) {
            return false;
        }
        //删除添加的tr
        $('#change-frame tr:gt(0)').remove();
        var item = ret.data;
        var Str = '';
        if (1 == is_edit) {
            for (var j = 0, len = item.length; j < len; j++) {
                Str += '<tr>';
                Str += '<td><input  class="form-control" name="row[change_frame][original_sku][]" type="text" value="' + item[j].original_sku + '" readonly></td>';
                Str += '<td><input  class="form-control" name="row[change_frame][original_number][]" type="text" value="1" readonly></td>';
                Str += '<td><input  class="form-control" name="row[change_frame][change_sku][]" type="text" value="' + item[j].change_sku + '"></td>';
                Str += '<td><input  class="form-control" name="row[change_frame][change_number][]" type="text" value="1" readonly></td>';
                // Str +='<td><a href="javascript:;" class="btn btn-danger btn-del" title="删除"><i class="fa fa-trash"></i>删除</a></td>';
                Str += '</tr>';
            }
            $("#change-frame tbody").append(Str);
            return false;
        }
        for (var j = 0, len = item.length; j < len; j++) {
            Str += '<tr>';
            Str += '<td><input  class="form-control" name="row[change_frame][original_sku][]" type="text" value="' + item[j] + '" readonly></td>';
            Str += '<td><input  class="form-control" name="row[change_frame][original_number][]" type="text" value="1" readonly></td>';
            Str += '<td><input  class="form-control" name="row[change_frame][change_sku][]" type="text"></td>';
            Str += '<td><input  class="form-control" name="row[change_frame][change_number][]" type="text" value="1" readonly></td>';
            // Str +='<td><a href="javascript:;" class="btn btn-danger btn-del" title="删除"><i class="fa fa-trash"></i>删除</a></td>';
            Str += '</tr>';
        }
        $("#change-frame tbody").append(Str);
        return false;
    }, function (data, ret) {
        //失败的回调
        Toastr.error(ret.msg);
        return false;
    });
}
//js 函数读取取消订单信息
function cancelOrder(is_edit = 0, work_id = 0) {
    var ordertype = $('#work_platform').val();
    var order_number = $('#c-platform_order').val();
    if (!order_number) {
        return false;
    }
    if (ordertype <= 0) {
        Toastr.error('请选择正确的平台');
        return false;
    }
    if (1 == is_edit) {
        var urls = 'saleaftermanage/work_order_list/ajax_edit_order';
        var datas = { ordertype: ordertype, order_number: order_number, work_id: work_id };
    } else {
        var urls = 'saleaftermanage/work_order_list/ajax_get_order';
        var datas = { ordertype: ordertype, order_number: order_number };
    }
    Backend.api.ajax({
        url: urls,
        data: datas
    }, function (data, ret) {
        //删除添加的tr
        $('#cancel-order tr:gt(0)').remove();
        var item = ret.data;
        var Str = '';
        if (1 == is_edit) {
            for (var j = 0, len = item.length; j < len; j++) {
                Str += '<tr>';
                Str += '<td><input  class="form-control" readonly name="row[cancel_order][original_sku][]" type="text" value="' + item[j].original_sku + '" readonly></td>';
                Str += '<td><input  class="form-control" name="row[cancel_order][original_number][]"  type="text" value="1" readonly></td>';
                Str += '<td><a href="javascript:;" class="btn btn-danger btn-del" title="删除"><i class="fa fa-trash"></i>删除</a></td>';
                Str += '</tr>';
            }
            $("#cancel-order tbody").append(Str);
            return false;
        }
        for (var j = 0, len = item.length; j < len; j++) {
            Str += '<tr>';
            Str += '<td><input  class="form-control" readonly name="row[cancel_order][original_sku][]" type="text" value="' + item[j] + '" readonly></td>';
            Str += '<td><input  class="form-control" name="row[cancel_order][original_number][]"  type="text" value="1" readonly></td>';
            Str += '<td><a href="javascript:;" class="btn btn-danger btn-del" title="删除"><i class="fa fa-trash"></i>删除</a></td>';
            Str += '</tr>';
        }
        $("#cancel-order tbody").append(Str);
        return false;
    }, function (data, ret) {
        //失败的回调
        Toastr.error(ret.msg);
        return false;
    });
}