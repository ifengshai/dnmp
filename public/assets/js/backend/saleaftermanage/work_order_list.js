define(['jquery', 'bootstrap', 'backend', 'table', 'jqui', 'form'], function ($, undefined, Backend, Table, undefined, Form) {

    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                showJumpto: true,
                searchFormVisible: true,
                pageList: [10, 25, 50, 100],
                extend: {
                    index_url: 'saleaftermanage/work_order_list/index' + location.search + '&platform_order=' + Config.platform_order,
                    add_url: 'saleaftermanage/work_order_list/add',
                    edit_url: 'saleaftermanage/work_order_list/edit',
                    del_url: 'saleaftermanage/work_order_list/del',
                    multi_url: 'saleaftermanage/work_order_list/multi',
                    import_url: 'saleaftermanage/work_order_list/import',
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
                        { field: 'work_platform', title: __('work_platform'), custom: { 1: 'blue', 2: 'danger', 3: 'orange' }, searchList: { 1: 'Z', 2: 'V', 3: 'Nh',4:'Ml',5:'We' }, formatter: Table.api.formatter.status },
                        { field: 'work_type_str', title: __('Work_type'), operate: false },
                        { field: 'work_type', title: __('Work_type'), searchList: { 1: '客服工单', 2: '仓库工单' }, visible: false, formatter: Table.api.formatter.status },
                        { field: 'platform_order', title: __('Platform_order') },
                        {
                            field: 'recept_person', title: __('承接人'), searchList: function (column) {
                                return Template('receptpersontpl', {});
                            }, visible: false
                        },
                        { field: 'order_sku', title: __('Order_sku'), operate: 'like', visible: false },

                        { field: 'create_user_name', title: __('create_user_name'), operate: 'like', visible: false },

                        /*{
                            field: 'order_sku',
                            title: __('Order_sku'),
                            operate: 'like',
                            visible: true,
                            formatter: function (value, rows) {
                                var all_user_name = '';
                                if (rows.order_sku_arr) {
                                    for (i = 0, len = rows.order_sku_arr.length; i < len; i++) {
                                        all_user_name += '<div class="step_recept"><b class="recept">' + rows.order_sku_arr[i] + '</b></div>';
                                    }
                                } else {
                                    all_user_name = '-';
                                }
                                return all_user_name;
                            },
                        },*/
                        { field: 'coupon_str', title: __('优惠券') },
                        { field: 'replacement_order', title: __('补发订单号') },
                        { field: 'work_level', title: __('Work_level'), custom: { 1: 'success', 2: 'orange', 3: 'danger' }, searchList: { 1: '低', 2: '中', 3: '高' }, formatter: Table.api.formatter.status },
                        {
                            field: 'problem_type_content',
                            title: __('Problem_type_content'),
                            align: 'left',
                            searchList: $.getJSON('saleaftermanage/work_order_list/getProblemTypeContent')
                        },
                        {
                            field: 'measure_choose_id',
                            title: __('措施'),
                            align: 'left',
                            searchList: $.getJSON('saleaftermanage/work_order_list/getMeasureContent'),
                            visible:false
                        },
                        { field: 'is_check', title: __('Is_check'), custom: { 0: 'black', 1: 'success' }, searchList: { 0: '否', 1: '是' }, formatter: Table.api.formatter.status },
                        { field: 'is_refund', title: __('是否有退款'), custom: { 0: 'black', 1: 'success' }, searchList: { 0: '否', 1: '是' }, formatter: Table.api.formatter.status },
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
                        { field: 'assign_user_id', title: __('直接审核人'), searchList: { 75: '王伟', 95: '白青青', 117: '韩雨薇' }, formatter: Table.api.formatter.status ,visible:false},
                        {
                            field: 'after_user_id',
                            title: __('recept_user'),
                            operate: false,
                            formatter: function (value, rows) {
                                var all_user_name = '';

                                if (rows.work_type == 2 && rows.is_after_deal_with == 0) {
                                    for (i = 0, len = rows.all_after_user_name.length; i < len; i++) {
                                        all_user_name += '<div class="step_recept"><b class="recept">' + rows.all_after_user_name[i] + '</b></div>';
                                    }
                                    //all_user_name += '<div class="step_recept"><b class="recept">' + rows.after_user_name + '1111</b></div>';
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
                                if (value.length > 0) {
                                    for (i = 0, len = value.length; i < len; i++) {
                                        if (value[i].operation_type == 0) {
                                            all_user_name += '<div class="step_recept"><b class="step">' + value[i].measure_content + '：</b><b class="recept text-red">未处理</b></div>';
                                        }
                                        if (value[i].operation_type == 1) {
                                            all_user_name += '<div class="step_recept"><b class="step">' + value[i].measure_content + '：</b><b class="recept text-green">处理成功</b></div>';
                                        }
                                        if (value[i].operation_type == 2) {
                                            all_user_name += '<div class="step_recept"><b class="step">' + value[i].measure_content + '：</b><b class="recept">处理失败</b></div>';
                                        }
                                    }
                                }
                                return all_user_name;
                            },
                        },

                        { field: 'work_status', title: __('work_status'), custom: { 0: 'black', 1: 'danger', 2: 'orange', 4: 'warning', 3: 'purple', 5: 'primary', 6: 'success' }, searchList: { 0: '已取消', 1: '新建', 2: '待审核', 4: '审核拒绝', 3: '待处理', 5: '部分处理', 6: '已处理' }, formatter: Table.api.formatter.status },
                        { field: 'order_type', title: __('订单类型'), searchList: { 1: '普通订单', 2: '批发单', 3: '网红单', 4: '补发单', 5: '补差价订单', 7:'paypal手动补单', 100: 'VIP订单' }, formatter: Table.api.formatter.status,visible:false},
                        { field: 'work_order_note_status', title: __('回复状态'), custom: { 0: 'gray', 1: 'success', 2: 'danger', 3: 'blank' }, searchList: { 0: '无', 1: '客服已回复', 2: '仓库已回复', 3: '财务已回复' }, formatter: Table.api.formatter.status },
                        {
                            field: 'create_time',
                            title: __('time_str'),
                            operate: false,
                            formatter: function (value, rows) {
                                var all_user_name = '';
                                all_user_name += '<div class="step_recept"><b class="step">创建时间：</b><b class="recept">' + value + '</b></div>';
                                if (rows.submit_time) {
                                    all_user_name += '<br><div class="step_recept"><b class="step">提交时间：</b><b class="recept">' + rows.submit_time + '</b></div>';
                                }
                                if (rows.check_time) {
                                    all_user_name += '<br><div class="step_recept"><b class="step">审核时间：</b><b class="recept">' + rows.check_time + '</b></div>';
                                }

                                if (rows.complete_time) {
                                    all_user_name += '<br><div class="step_recept"><b class="step">完成时间：</b><b class="recept">' + rows.complete_time + '</b></div>';
                                }

                                if (rows.cancel_time) {
                                    all_user_name += '<br><div class="step_recept"><b class="step">取消时间：</b><b class="recept">' + rows.cancel_time + '</b></div>';
                                }

                                return all_user_name;
                            },
                        },

                        { field: 'create_time', title: __('Create_time'), operate: 'RANGE', addclass: 'datetimerange', formatter: Table.api.formatter.datetime, visible: false },
                        { field: 'check_time', title: __('Check_time'), operate: 'RANGE', addclass: 'datetimerange', formatter: Table.api.formatter.datetime, visible: false },
                        { field: 'complete_time', title: __('Complete_time'), operate: 'RANGE', addclass: 'datetimerange', formatter: Table.api.formatter.datetime, visible: false },
                        {
                            field: 'buttons',
                            width: "120px",
                            operate: false,
                            title: __('回复'),
                            table: table,
                            events: Table.api.events.operate,
                            buttons: [
                                {
                                    name: 'workOrderNote',
                                    text: __('查看回复'),
                                    title: __('查看回复'),
                                    classname: 'btn btn-xs btn-primary btn-dialog',
                                    url: 'saleaftermanage/work_order_list/workordernote',
                                    callback: function (data) {
                                    }
                                },
                            ],
                            formatter: Table.api.formatter.buttons
                        },
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
                                        // if (row.work_status == 1) {
                                        //     return false;
                                        // }
                                        return true;
                                    }
                                },

                                {
                                    name: 'edit',
                                    text: __('编辑'),
                                    title: __('编辑'),
                                    classname: 'btn btn-xs btn-success btn-dialog',
                                    url: 'saleaftermanage/work_order_list/edit',
                                    extend: 'data-area = \'["100%","100%"]\'',
                                    success: function (data, ret) {
                                        table.bootstrapTable('refresh');
                                    },
                                    callback: function (data) {
                                    },
                                    visible: function (row) {
                                        if (row.work_status == 1) {//操作权限
                                            return true;
                                        } else {
                                            return false;
                                        }
                                    }
                                },
                                {
                                    name: 'process',
                                    text: __('跟单处理'),
                                    title: __('跟单处理'),
                                    classname: 'btn btn-xs btn-success btn-dialog',
                                    url: 'saleaftermanage/work_order_list/add',
                                    extend: 'data-area = \'["100%","100%"]\'',
                                    callback: function (data) {
                                    },
                                    visible: function (row) {
                                        if (row.work_type == 2 && row.is_after_deal_with == 0 && row.work_type != 6 && (row.all_after_user_arr.includes(Config.userid.toString()) || row.after_user_id == Config.userid) && row.work_status == 3) {
                                            return true;
                                        } else {
                                            return false;
                                        }
                                    }
                                },
                                {
                                    name: 'check',
                                    text: __('审核'),
                                    title: __('审核'),
                                    classname: 'btn btn-xs btn-success btn-dialog',
                                    url: 'saleaftermanage/work_order_list/detail/operate_type/2',
                                    extend: 'data-area = \'["100%","100%"]\'',
                                    callback: function (data) {
                                    },
                                    visible: function (row) {
                                        //待审核状态+需要审核+审核人(经理)，才有审核权限
                                        if (row.work_status == 2 && row.is_check == 1 && (Config.admin_id == row.assign_user_id || Config.workorder.customer_manager == Config.admin_id)) {
                                            return true;
                                        } else {
                                            return false;
                                        }
                                    }
                                },
                                {
                                    name: 'check',
                                    text: __('处理'),
                                    title: __('处理'),
                                    classname: 'btn btn-xs btn-success btn-dialog',
                                    url: 'saleaftermanage/work_order_list/detail/operate_type/3',
                                    extend: 'data-area = \'["100%","100%"]\'',
                                    callback: function (data) {
                                    },
                                    visible: function (rows) {
                                        if (!(rows.work_type == 2 && rows.is_after_deal_with == 0) && (rows.work_status == 3 || rows.work_status == 5) && rows.has_recept == 1) {
                                            return true;
                                        }
                                        return false;
                                    }
                                },
                                {
                                    name: 'cancel',
                                    text: __('取消'),
                                    title: __('取消'),
                                    classname: 'btn btn-xs btn-danger btn-ajax',
                                    url: 'saleaftermanage/work_order_list/setStatus/work_status/2',
                                    extend: 'data-area = \'["100%","100%"]\'',
                                    confirm: '确定要取消吗',
                                    success: function (data, ret) {
                                        table.bootstrapTable('refresh');
                                    },
                                    callback: function (data) {
                                    },
                                    visible: function (row) {
                                        if (row.work_status == 1 && row.create_user_id == Config.userid) {
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
                    if (field == 'create_user_name') {
                        delete filter.recept_person_id;
                        filter[field] = value;
                    } else if (field == 'recept_person_id') {
                        delete filter.create_user_name;
                        filter[field] = value;
                    } else {
                        delete filter.recept_person_id;
                        delete filter.create_user_name;
                    }
                    params.filter = JSON.stringify(filter);
                    params.op = JSON.stringify(op);
                    return params;
                };
                table.bootstrapTable('refresh', {});
                return false;
            });


            //批量打印标签    
            $('.btn-batch-printed').click(function () {
                var ids = Table.api.selectedids(table);
                window.open('work_order_list/batch_print_label/ids/' + ids, '_blank');
            });
            //批量导出xls 
            $('.btn-batch-export-xls').click(function () {
                var ids = Table.api.selectedids(table);
                if (ids.length > 0) {
                    window.open(Config.moduleurl + '/saleaftermanage/work_order_list/batch_export_xls?ids=' + ids, '_blank');
                } else {
                    var options = table.bootstrapTable('getOptions');
                    var search = options.queryParams({});
                    var filter = search.filter;
                    var op = search.op;
                    window.open(Config.moduleurl + '/saleaftermanage/work_order_list/batch_export_xls?filter=' + filter + '&op=' + op, '_blank');
                }

            });

        },
        add: function () {
            Controller.api.bindevent();

            //点击事件 #todo::需判断仓库或者客服
            $(document).on('click', '.problem_type', function () {
                console.log(Config.work_type);

                var incrementId = $('#c-platform_order').val().replace(/^\s+|\s+$/g, "");
                var vip_str = incrementId.substring(1, 4);
                if(vip_str == 'VIP'){
                    $('#order_pay_currency').val('USD');
                    $('#step2_pay_currency').val('USD');
                    $('#c-refund_money').val(29.8);
                    $('#c-refund_way').val('原路退回');
                    var site = incrementId.substring(0, 1);
                    if(site =='Z'){
                        $("#work_platform").val(1);
                    }else if(site =='V'){
                        $("#work_platform").val(2);
                    }
                    $('#order_type').val(100);
                    $('#c-order_type').val(100);
                    $('.selectpicker ').selectpicker('refresh');
                }
                $order_pay_currency = $('#order_pay_currency').val();
                if (!$order_pay_currency) {
                    Toastr.error('请先点击载入数据');
                    return false;
                }
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
                    // $('#after_user_id').val(Config.workorder.copy_group);
                    // $('#after_user').html(Config.users[Config.workorder.copy_group]);
                    //异步获取跟单人员
                    Backend.api.ajax({
                        url: 'saleaftermanage/work_order_list/getDocumentaryRule',
                    }, function (data, ret) {
                        console.log(data);
                        $('#all_after_user_id').val(data.join(','));
                        var content = '';
                        for(i=0;i<data.length;i++){
                            //$('#all_after_user').html(Config.users[data[i]]);
                            content += Config.users[data[i]]+' ';
                        }
                        $('#all_after_user').html(content);
                    },function(data,ret){
                        console.log(ret);
                        Toastr.error(ret.msg);
                        return false;
                    });
                } else { //如果是客服人员添加的工单
                    //选择的问题类型ID
                    var id = $(this).val();
                    //var all_group = Config.workOrderConfigValue.group;
                    //所有的问题类型对应措施表
                    var all_problem_step = Config.workOrderConfigValue.all_problem_step;
                    //求出选择的问题类型对应的措施
                    var choose_problem_step = all_problem_step[id];
                    if(choose_problem_step == undefined){
                        Toastr.error('选择的问题类型没有对应的措施，请重新选择问题类型或者添加措施');
                        return false;
                    }
                    //循环列出对应的措施
                    for(var j=0;j<choose_problem_step.length;j++){
                        //console.log(choose_problem_step[j].step_id);
                        $('#step' + choose_problem_step[j].step_id).parent().show();
                        $('#step' + choose_problem_step[j].step_id + '-is_check').val(choose_problem_step[j].is_check);
                        $('#step' + choose_problem_step[j].step_id + '-is_auto_complete').val(choose_problem_step[j].is_auto_complete);
                        if(choose_problem_step[j].extend_group_id !=undefined && choose_problem_step[j].extend_group_id !=0){
                            $('#step' + choose_problem_step[j].step_id + '-appoint_group').val((choose_problem_step[j].extend_group_id));
                        }else{
                            $('#step' + choose_problem_step[j].step_id + '-appoint_group').val(0);
                        }
                    }
                    //id大于5 默认措施4
                    // if (id > 5) {
                    //     var steparr = Config.workorder['step04'];
                    //     for (var j = 0; j < steparr.length; j++) {
                    //         $('#step' + steparr[j].step_id).parent().show();
                    //         //读取对应措施配置
                    //         $('#step' + steparr[j].step_id + '-is_check').val(steparr[j].is_check);
                    //         $('#step' + steparr[j].step_id + '-appoint_group').val((steparr[j].appoint_group).join(','));
                    //     }
                    // } else {
                    //     var step = Config.workorder.customer_problem_group[id].step;
                    //     var steparr = Config.workorder[step];
                    //     //console.log(steparr);
                    //     for (var j = 0; j < steparr.length; j++) {
                    //         $('#step' + steparr[j].step_id).parent().show();
                    //         //读取对应措施配置
                    //         $('#step' + steparr[j].step_id + '-is_check').val(steparr[j].is_check);
                    //         $('#step' + steparr[j].step_id + '-appoint_group').val((steparr[j].appoint_group).join(','));
                    //     }
                    // }
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

            //更改单号清空
            $('#c-platform_order').change(function () {
                $('#order_pay_currency').val('');
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
                    var input_content = '';
                    var is_check = [];
                    var appoint_group = '';
                    var username = [];
                    var appoint_users = [];
                    //判断是否出现没有承接组的情况
                    var count = 0;
                    //选中的问题类型
                    $("input[name='row[measure_choose_id][]']:checked").each(function (i) {
                        checkID[i] = $(this).val();
                        var id = $(this).val();
                        //获取承接组
                         appoint_group += $('#step' + id + '-appoint_group').val() + ',';
                         var group_id = $('#step' + id + '-appoint_group').val();
                        // var group_arr = group.split(',')
                        // var appoint_users = [];
                        // var appoint_val = [];
                        // for (var i = 0; i < group_arr.length; i++) {
                        //     //循环根据承接组Key获取对应承接人id
                        //     appoint_users.push(Config.workorder[group_arr[i]]);
                        //     appoint_val[Config.workorder[group_arr[i]]] = group_arr[i];
                        // }


                        //循环根据承接人id获取对应人名称
                        // for (var j = 0; j < appoint_users.length; j++) {
                        //     input_content += '<input type="hidden" name="row[order_recept][appoint_group][' + id + '][]" value="' + appoint_val[appoint_users[j]] + '"/>';
                        //     input_content += '<input type="hidden" name="row[order_recept][appoint_ids][' + id + '][]" value="' + appoint_users[j] + '"/>';
                        //     input_content += '<input type="hidden" name="row[order_recept][appoint_users][' + id + '][]" value="' + Config.users[appoint_users[j]] + '"/>';
                        // }
                        var choose_group = Config.workOrderConfigValue.group[group_id];
                        if(choose_group){
                            for(var j = 0;j<choose_group.length;j++){
                                input_content += '<input type="hidden" name="row[order_recept][appoint_group][' + id + '][]" value="' + group_id + '"/>';
                                input_content += '<input type="hidden" name="row[order_recept][appoint_ids][' + id + '][]" value="' + choose_group[j] + '"/>';
                                input_content += '<input type="hidden" name="row[order_recept][appoint_users][' + id + '][]" value="' + Config.users[choose_group[j]] + '"/>';                            
                            }
                        }else{
                            count = 1;
                            input_content += '<input type="hidden" name="row[order_recept][appoint_group][' + id + '][]" value="0"/>';
                            input_content += '<input type="hidden" name="row[order_recept][appoint_ids][' + id + '][]" value="' + Config.userid + '"/>';
                            input_content += '<input type="hidden" name="row[order_recept][appoint_users][' + id + '][]" value="' + Config.users[Config.userid] + '"/>';                            
                        }
                        //获取是否需要审核
                        var step_is_check = $('#step' + id + '-is_check').val();
                        is_check.push(step_is_check);
                        //是否自动审核完成 start
                        var step_is_auto_complete = $('#step' + id + '-is_auto_complete').val();
                        input_content +='<input type="hidden" name="row[order_recept][auto_complete][' + id + ']" value="' + step_is_auto_complete + '"/>';
                        //是否自动审核完成  end
                        //修改地址
                        if(id == 13){
                            changeOrderAddress();
                        }
                        //vip退款
                        if(id == 15){
                            $(".step2").show()
                        }
                    });
                    //判断如果存在1 则改为需要审核
                    if ($.inArray("1", is_check) != -1) {
                        $('#is_check').val(1);
                    } else {
                        $('#is_check').val(0);
                    }

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
                        var secondNode = $('.step' + checkID[m] + '-' + checkID[m]);
                        //console.log(secondNode);
                        if (secondNode.is(':hidden')) {
                            secondNode.show();
                        } else {
                            secondNode.hide();
                        }
                        //判断如果为处理任务时
                        if (Config.ids) {
                            // if (problem_type_id == 1 && checkID[m] == 1) {
                            //     $('.step1-1').hide();
                            //     $('.step2-1').show();
                            // }
                            // if (problem_type_id == 2 && checkID[m] == 1) {
                            //     $('.step2-1').hide();
                            //     $('.step1-1').show();
                            // }
                            // if (problem_type_id == 3 && checkID[m] == 1) {
                            //     $('.step2-1').hide();
                            //     $('.step1-1').show();
                            // }
                        }
                    }
                    
                    //判断如果为处理任务时
                    /*if (Config.ids) {
                        if (problem_type_id == 1) {
                            $('.step1-1').hide();
                            $('.step2-1').show();
                        } else if (problem_type_id == 2) {
                            $('.step2-1').hide();
                            $('.step1-1').show();
                        } else if (problem_type_id == 3) {
                            $('.step2-1').hide();
                            $('.step1-1').show();
                        }
                    }*/
                    // var appoint_group = '';
                    // var username = [];
                    // var appoint_users = [];
                    var arr = array_filter(appoint_group.split(','));
                    //循环根据承接组Key获取对应承接人id
                    //console.log(notEmpty(arr));
                    for (var i = 0; i < arr.length - 1; i++) {
                        //循环根据承接组Key获取对应承接人id
                        //appoint_users.push(Config.workorder[arr[i]]);
                        if(Config.workOrderConfigValue.group[arr[i]] !=undefined){
                            //console.log(Config.workOrderConfigValue.group[arr[i]]);
                            for(var n=0;n<Config.workOrderConfigValue.group[arr[i]].length;n++){
                                appoint_users.push(Config.workOrderConfigValue.group[arr[i]][n]);
                            }
                            
                        }
                        
                    }

                    if(count == 1){
                        appoint_users.push(Config.userid);
                    }else{
                         if(appoint_users[Config.userid]){
                            delOne(Config.userid,appoint_users);
                        }                         
                    }
                    if(checkID.length>0 && appoint_users.length === 0){
                        if(!appoint_users[Config.userid]){
                            appoint_users.push(Config.userid);
                        }
                    }else if(checkID.length === 0){
                        if(appoint_users[Config.userid]){
                            delOne(Config.userid,appoint_users);
                        } 
                    }
                    //循环根据承接人id获取对应人名称
                    appoint_users = array_filter(appoint_users);
                    for (var j = 0; j < appoint_users.length; j++) {
                        username.push(Config.users[appoint_users[j]]);
                    }
                    //console.log(Config.users);
                    //console.log(appoint_users);
                    //console.log(appoint_users[0]);
                    var users = array_filter(username);
                    //var appoint_users_content = '';
                    // for(i=0;i<appoint_users.length;i++){
                    //     appoint_users_content+=Config.users[appoint_users[i]];
                    // }
                    $('#appoint_group_users').html(users.join(','));
                    //$('#appoint_group_users').html(appoint_users_content);

                    $('#recept_person_id').val(appoint_users.join(','));

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
            });


            $(document).on('click', '.btn-del-box', function () {
                $(this).parent().parent().remove();
            });
            //赠品 end

            //补发 start
            $(document).on('click', '.btn-add-supplement', function () {
                var contents = $('#edit_lens').html();
                $('#supplement-order').after(contents);
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
                var incrementId = $(this).val().replace(/^\s+|\s+$/g, "");
                //var incrementId = replace(/^\s+|\s+$/g,"");
                if (!incrementId) {
                    Toastr.error('订单号不能为空');
                    return false;
                }
                var str = incrementId.substring(0, 2);
                //判断站点
                if (str == '10' || str == '40' || str == '50') {
                    $("#work_platform").val(1);
                } else if (str == '13' || str == '43') {
                    $('#work_platform').val(2);
                } else if (str == '30' || str == '60') {
                    $('#work_platform').val(3);
                } else if( str == '45' || str == '15'){
                    //meeloog站
                    $('#work_platform').val(4);
                } else if( str == '20' || str == '27'){
                    //wesee站
                    $('#work_platform').val(5);
                }
                $('.selectpicker ').selectpicker('refresh');

            })

            //载入数据
            $('#platform_order').click(function () {
                var incrementId = $('#c-platform_order').val().replace(/^\s+|\s+$/g, "");
                if (!incrementId) {
                    Toastr.error('订单号不能为空');
                    return false;
                }
                var str = incrementId.substring(0, 2);
                var vip_str = incrementId.substring(1, 4);
                if(vip_str == 'VIP'){
                    $('#order_pay_currency').val('USD');
                    $('#step2_pay_currency').val('USD');
                    $('#c-refund_money').val(29.8);
                    $('#c-refund_way').val('原路退回');
                    var site = incrementId.substring(0, 1);
                    if(site == 'Z'){
                        $("#work_platform").val(1);
                    }else if(site == 'V'){
                        $("#work_platform").val(2);
                    }
                    $('#order_type').val(100);
                    $('#c-order_type').val(100);
                    $('.selectpicker ').selectpicker('refresh');
                }
                else{
                    //判断站点
                    if (str == '10' || str == '40' || str == '50') {
                        $("#work_platform").val(1);
                    } else if (str == '13' || str == '43') {
                        $('#work_platform').val(2);
                    } else if (str == '30' || str == '60') {
                        $('#work_platform').val(3);
                    }else if( str == '45' || str == '15'){
                        //meeloog站
                        $('#work_platform').val(4);
                    } else if( str == '20' || str == '27'){
                        //wesee站
                        $('#work_platform').val(5);
                    }

                    var sitetype = $('#work_platform').val();
                    $('#c-order_sku').html('');
                    Layer.load();
                    Backend.api.ajax({
                        url: 'saleaftermanage/work_order_list/get_sku_list',
                        data: {
                            sitetype: sitetype,
                            order_number: incrementId
                        }
                    }, function (data, ret) {
                        Layer.closeAll();
                        $('#order_pay_currency').val(data.base_currency_code);
                        $('#step2_pay_currency').val(data.base_currency_code);
                        $('#c-rewardpoint_discount_money').val(data.mw_rewardpoint_discount);
                        $('#grand_total').val(data.grand_total);
                        $('#base_grand_total').val(data.base_grand_total);
                        $('#base_to_order_rate').val(data.base_to_order_rate); 
                        $('#order_pay_method').val(data.method);
                        $('#c-refund_way').val(data.method);
                        $('#customer_email').val(data.customer_email);
                        $('#order_type').val(data.order_type);
                        $('#c-order_type').val(data.order_type);
                        $('#is_new_version').val(data.is_new_version);
                        var shtml = '';
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
                        // //判断取消订单的状态，如果显示的话把原数据带出来，如果隐藏则不显示原数据 end                                        
                    });
                }
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
                    var is_new_version = $('#is_new_version').val();
                    //补发
                    if (value == 7 && check === true) {
                        //获取补发的信息
                        Backend.api.ajax({
                            url: 'saleaftermanage/work_order_list/ajaxGetAddress',
                            data: {
                                increment_id: increment_id,
                                site_type: site_type,
                                is_new_version: is_new_version
                            }
                        }, function (json, ret) {
                            if (json.code == 0) {
                                Toastr.error(json.msg);
                                return false;
                            }
                            var data = json.address;
                            var lens = json.lens;
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
                                    var email = data.address[i].email;
                                    if (email == null) {
                                        email = $('#customer_email').val();
                                    }
                                    $('#c-email').val(email);
                                    $('#c-telephone').val(data.address[i].telephone);
                                    $('#c-country').val(data.address[i].country_id);
                                    $('#c-country').change();
                                    if(data.address[i].region_id == '8888' || !data.address[i].region_id){
                                        $('#c-region').val(0);
                                    }else{
                                        $('#c-region').val(data.address[i].region_id);
                                    }
                                    $('#c-region1').val(data.address[i].region);
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
                                $('#c-region1').val(address.region);
                                $('#c-city').val(address.city);
                                $('#c-street').val(address.street);
                                $('#c-postcode').val(address.postcode);
                            })

                            //追加
                            lens_click_data = '<div class="margin-top:10px;">' + lens.html + '<div class="form-group-child4_del" style="width: 96%;padding-right: 0px;"><a href="javascript:;" style="width: 50%;" class="btn btn-danger btn-del-lens" title="删除"><i class="fa fa-trash"></i>删除</a></div></div>';

                            $('.selectpicker ').selectpicker('refresh');
                        });
                    }
                    //更加镜架的更改
                    var question = $('input[name="row[problem_type_id]"]:checked').val();
                    if ((Config.work_type == 1 && value == 12  && check === true) || (Config.work_type == 2 && value == 12  && check === true)) {
                        Backend.api.ajax({
                            url: 'saleaftermanage/work_order_list/ajaxGetChangeLens',
                            data: {
                                increment_id: increment_id,
                                site_type: site_type,
                                is_new_version: is_new_version
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
                                is_new_version: is_new_version
                            }
                        }, function (data, ret) {
                            $('.add_gift').html(data.html);
                            //追加
                            gift_click_data = '<div class="margin-top:10px;">' + data.html + '<div class="form-group-child4_del"  style="width: 96%;padding-right: 0px;"><a href="javascript:;" style="width: 50%;" class="btn btn-danger btn-del-lens" title="删除"><i class="fa fa-trash"></i>删除</a></div></div>';
                            $('.selectpicker ').selectpicker('refresh');
                        });
                    }

                    //判断更换镜框的状态，如果显示的话把原数据带出来，如果隐藏则不显示原数据 start
                    if (!$('.step1-1').is(':hidden')) {
                        changeFrame();
                    }
                    //判断更换镜框的状态，如果显示的话把原数据带出来，如果隐藏则不显示原数据 end
                    //判断取消订单的状态，如果显示的话把原数据带出来，如果隐藏则不显示原数据 start
                    if (!$('.step3').is(':hidden') && value == 3) {
                        cancelOrder();
                    }
                }

            });

            //处方选择填充
            $(document).on('change', '#prescription_select', function () {
                var val = $(this).val();
                var is_new_version = $('#is_new_version').val();
                var prescription = prescriptions[val];
                console.log(prescription);
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
                if (prescription.color_id) {
                    prescription_div.find('#color_type').val(prescription.color_id);
                    if(is_new_version == 0){
                        prescription_div.find('#color_type').change();
                    }
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

                if (prescription.hasOwnProperty("pd") && (prescription.pd != '')) {
                    prescription_div.find('input[name="row[replacement][pd_r][]"]').val(prescription.pd);
                    //prescription_div.find('input[name="row[replacement][pd_l][]"]').attr('disabled',true);
                }else{
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
            });

            $(document).on('click', '.btn-add-supplement-reissue', function () {
                $('#supplement-order').after(lens_click_data);
                $('.selectpicker ').selectpicker('refresh');
            });

            //如果问题类型存在，显示问题类型和措施
            //跟单处理
            if (Config.problem_id && Config.work_type == 2) {
                var id = Config.problem_id;
                var work_id = $('#work_id').val();
                $("input[name='row[problem_type_id]'][value='" + id + "']").attr("checked", true);
                //var all_group = Config.workOrderConfigValue.group;
                //所有的问题类型对应措施表
                var all_problem_step = Config.workOrderConfigValue.all_problem_step;
                //求出选择的问题类型对应的措施
                var choose_problem_step = all_problem_step[id];
                if(choose_problem_step == undefined){
                    Toastr.error('选择的问题类型没有对应的措施，请重新选择问题类型或者添加措施');
                    return false;
                }
                //循环列出对应的措施
                for(var j=0;j<choose_problem_step.length;j++){
                    //console.log(choose_problem_step[j].step_id);
                    $('#step' + choose_problem_step[j].step_id).parent().show();
                    $('#step' + choose_problem_step[j].step_id + '-is_check').val(choose_problem_step[j].is_check);
                    $('#step' + choose_problem_step[j].step_id + '-is_auto_complete').val(choose_problem_step[j].is_auto_complete);
                    if(choose_problem_step[j].extend_group_id !=undefined && choose_problem_step[j].extend_group_id !=0){
                        $('#step' + choose_problem_step[j].step_id + '-appoint_group').val((choose_problem_step[j].extend_group_id));
                    }else{
                        $('#step' + choose_problem_step[j].step_id + '-appoint_group').val(0);
                    }    
                }
                //id大于5 默认措施4
                // if (id > 4) {
                //     var steparr = Config.workorder['step04'];
                //     for (var j = 0; j < steparr.length; j++) {
                //         $('#step' + steparr[j].step_id).parent().show();
                //         //读取对应措施配置
                //         $('#step' + steparr[j].step_id + '-is_check').val(steparr[j].is_check);
                //         $('#step' + steparr[j].step_id + '-appoint_group').val((steparr[j].appoint_group).join(','));
                //     }
                // } else {
                //     $('#recept_person_group').hide();
                //     $('#after_user_group').show();
                //     $('#after_user_id').val(Config.workorder.copy_group);
                //     $('#after_user').html(Config.users[Config.workorder.copy_group]);
                //     var step = Config.workorder.warehouse_problem_group[id].step;
                //     if (step) {
                //         var steparr = Config.workorder[step];

                //         for (var j = 0; j < steparr.length; j++) {
                //             $('#step' + steparr[j].step_id).parent().show();
                //             //读取对应措施配置
                //             $('#step' + steparr[j].step_id + '-is_check').val(steparr[j].is_check);
                //             $('#step' + steparr[j].step_id + '-appoint_group').val((steparr[j].appoint_group).join(','));
                //         }
                //     }

                // }
                var checkID = [];//定义一个空数组
                var appoint_group = '';
                var input_content = '';
                var username = [];
                var appoint_users = [];
                var lens_click_data_edit;
                var gift_click_data_edit;
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
                var arr = array_filter(appoint_group.split(','));
                // var username = [];
                // var appoint_users = [];
                //循环根据承接组Key获取对应承接人id
                // for (var i = 0; i < arr.length - 1; i++) {
                //     //循环根据承接组Key获取对应承接人id
                //     appoint_users.push(Config.workorder[arr[i]]);
                // }
                //console.log(arr);
                for (var i = 0; i < arr.length - 1; i++) {
                    //循环根据承接组Key获取对应承接人id
                    //appoint_users.push(Config.workorder[arr[i]]);
                    if(Config.workOrderConfigValue.group[arr[i]] !=undefined){
                        console.log(Config.workOrderConfigValue.group[arr[i]]);
                        for(var n=0;n<Config.workOrderConfigValue.group[arr[i]].length;n++){
                            appoint_users.push(Config.workOrderConfigValue.group[arr[i]][n]);
                        }
                        
                    }
                    
                }
                //console.log(appoint_users);
                //循环根据承接人id获取对应人名称
                for (var j = 0; j < appoint_users.length; j++) {
                    username.push(Config.users[appoint_users[j]]);
                }
                console.log(username);
                var users = array_filter(username);
                var appoint_users = array_filter(appoint_users);
                $('#appoint_group_users').html(users.join(','));
                $('#recept_person_id').val(appoint_users.join(','));
                $('#recept_person_group').show();
                $('#after_user_group').hide();


                //判断更换镜框的状态，如果显示的话把原数据带出来，如果隐藏则不显示原数据 start
                if (!$('.step1-1').is(':hidden')) {
                    changeFrame(1, work_id)
                }
                //判断取消订单的状态，如果显示的话把原数据带出来，如果隐藏则不显示原数据 start
                if (!$('.step3').is(':hidden')) {
                    cancelOrder(1, work_id);
                }
                //判断取消订单的状态，如果显示的话把原数据带出来，如果隐藏则不显示原数据 end
                //判断更换处方的状态，如果显示的话把数据带出来，如果隐藏则不显示镜架数据 start
                if (!$('.step12-12').is(':hidden')) {
                    if(!checkIDss.includes(15)){
                        changeOrder(work_id, 2);
                    }
                }
                //判断更换处方的状态，如果显示的话把数据带出来，如果隐藏则不显示镜架数据 end
                //判断补发订单的状态，如果显示的话把数据带出来，如果隐藏则不显示补发数据 start
                if (!$('.step7').is(':hidden')) {
                    changeOrder(work_id, 5);
                }
                //判断补发订单的状态，如果显示的话把数据带出来，如果隐藏则不显示补发数据 end
                //判断赠品信息的状态，如果显示的话把数据带出来，如果隐藏的话则不显示赠品数据  start
                if (!$('.step6').is(':hidden')) {
                    changeOrder(work_id, 4);
                }
            }

        },
        edit: function () {
            Controller.api.bindevent();
            //进入页面展示按钮下的数据
            $("input[name='row[measure_choose_id][]']:checked").each(function (i) {
                var id = $(this).val();
                if(id == 15){
                    //vip退款显示
                    $(".step2").show();
                }
                if(id == 13){
                    //修改地址
                    changeOrderAddress();
                }
            })

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
                    //$('#after_user_id').val(Config.workorder.copy_group);
                    //$('#after_user').html(Config.users[Config.workorder.copy_group]);
                    Backend.api.ajax({
                        url: 'saleaftermanage/work_order_list/getDocumentaryRule',
                    }, function (data, ret) {
                        $('#all_after_user_id').val(data.join(','));
                        var content = '';
                        for(i=0;i<data.length;i++){
                            //$('#all_after_user').html(Config.users[data[i]]);
                            content += Config.users[data[i]]+' ';   
                        }
                        $('#all_after_user').html(content);
                    },function(data,ret){
                        Toastr.error(ret.msg);
                        return false;
                    });
                } else { //如果是客服人员添加的工单
                    // var id = $(this).val();
                    // //id大于5 默认措施4
                    // if (id > 5) {
                    //     var steparr = Config.workorder['step04'];
                    //     for (var j = 0; j < steparr.length; j++) {
                    //         $('#step' + steparr[j].step_id).parent().show();
                    //         //读取对应措施配置
                    //         $('#step' + steparr[j].step_id + '-is_check').val(steparr[j].is_check);
                    //         $('#step' + steparr[j].step_id + '-appoint_group').val((steparr[j].appoint_group).join(','));
                    //     }
                    // } else {
                    //     var step = Config.workorder.customer_problem_group[id].step;
                    //     var steparr = Config.workorder[step];
                    //     //console.log(steparr);
                    //     for (var j = 0; j < steparr.length; j++) {
                    //         $('#step' + steparr[j].step_id).parent().show();
                    //         //读取对应措施配置
                    //         $('#step' + steparr[j].step_id + '-is_check').val(steparr[j].is_check);
                    //         $('#step' + steparr[j].step_id + '-appoint_group').val((steparr[j].appoint_group).join(','));
                    //     }
                    // }
                    var id = $(this).val();
                    //var all_group = Config.workOrderConfigValue.group;
                    //所有的问题类型对应措施表
                    var all_problem_step = Config.workOrderConfigValue.all_problem_step;
                    //求出选择的问题类型对应的措施
                    var choose_problem_step = all_problem_step[id];
                    if(choose_problem_step == undefined){
                        Toastr.error('选择的问题类型没有对应的措施，请重新选择问题类型或者添加措施');
                        return false;
                    }
                    //循环列出对应的措施
                    for(var j=0;j<choose_problem_step.length;j++){
                        //console.log(choose_problem_step[j].step_id);
                        $('#step' + choose_problem_step[j].step_id).parent().show();
                        $('#step' + choose_problem_step[j].step_id + '-is_check').val(choose_problem_step[j].is_check);
                        $('#step' + choose_problem_step[j].step_id + '-is_auto_complete').val(choose_problem_step[j].is_auto_complete);
                        if(choose_problem_step[j].extend_group_id !=undefined && choose_problem_step[j].extend_group_id !=0){
                            $('#step' + choose_problem_step[j].step_id + '-appoint_group').val((choose_problem_step[j].extend_group_id));
                        }else{
                            $('#step' + choose_problem_step[j].step_id + '-appoint_group').val(0);
                        }    
                    }
                    var checkID = [];//定义一个空数组
                    $("input[name='row[measure_choose_id][]']:checked").each(function (i) {
                        console.log(i);
                        checkID[i] = $(this).val();
                    });
                    console.log(checkID);
                    for (var m = 0; m < checkID.length; m++) {
                        var node = $('.step' + checkID[m]);
                        if (node.is(':hidden')) {
                            node.show();
                        } else {
                            node.hide();
                        }
                        //判断是客服工单还是仓库工单
                        // if (1 == Config.work_type) { //客服工单
                        //     var secondNode = $('.step' + id + '-' + checkID[m]);
                        //     //var secondNode = $('.step' + checkID[m] + '-' + checkID[m]);
                        // } else if (2 == Config.work_type) { //仓库工单
                        //     if ((1 == id) && (1 == checkID[m])) {
                        //         var secondNode = $('.step2' + '-' + checkID[m]);
                        //     } else if ((id >= 2 || id <= 3) && (1 == checkID[m])) {
                        //         var secondNode = $('.step1' + '-' + checkID[m]);
                        //     } else {
                        //         var secondNode = $('.step' + id + '-' + checkID[m]);
                        //     }
                        // }
                        var secondNode = $('.step' + checkID[m] + '-' + checkID[m]);
                        console.log(checkID[m]);
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
                    var is_check = [];
                    var count = 0;
                    $("input[name='row[measure_choose_id][]']:checked").each(function (i) {
                        checkID[i] = $(this).val();
                        var id = $(this).val();
                        //获取承接组
                        appoint_group += $('#step' + id + '-appoint_group').val() + ',';
                        var group_id = $('#step' + id + '-appoint_group').val();
                        // var group = $('#step' + id + '-appoint_group').val();
                        // var group_arr = group.split(',')
                        // var appoint_users = [];
                        // var appoint_val = [];
                        // for (var i = 0; i < group_arr.length; i++) {
                        //     //循环根据承接组Key获取对应承接人id
                        //     appoint_users.push(Config.workorder[group_arr[i]]);
                        //     appoint_val[Config.workorder[group_arr[i]]] = group_arr[i];
                        // }

                        // //循环根据承接人id获取对应人名称
                        // for (var j = 0; j < appoint_users.length; j++) {
                        //     input_content += '<input type="hidden" name="row[order_recept][appoint_group][' + id + '][]" value="' + appoint_val[appoint_users[j]] + '"/>';
                        //     input_content += '<input type="hidden" name="row[order_recept][appoint_ids][' + id + '][]" value="' + appoint_users[j] + '"/>';
                        //     input_content += '<input type="hidden" name="row[order_recept][appoint_users][' + id + '][]" value="' + Config.users[appoint_users[j]] + '"/>';
                        // }
                        var choose_group = Config.workOrderConfigValue.group[group_id];
                        if(choose_group){
                            for(var j = 0;j<choose_group.length;j++){
                                input_content += '<input type="hidden" name="row[order_recept][appoint_group][' + id + '][]" value="' + group_id + '"/>';
                                input_content += '<input type="hidden" name="row[order_recept][appoint_ids][' + id + '][]" value="' + choose_group[j] + '"/>';
                                input_content += '<input type="hidden" name="row[order_recept][appoint_users][' + id + '][]" value="' + Config.users[choose_group[j]] + '"/>';                            
                            }
                        }else{
                            count =1;
                            input_content += '<input type="hidden" name="row[order_recept][appoint_group][' + id + '][]" value="0"/>';
                            input_content += '<input type="hidden" name="row[order_recept][appoint_ids][' + id + '][]" value="' + Config.userid + '"/>';
                            input_content += '<input type="hidden" name="row[order_recept][appoint_users][' + id + '][]" value="' + Config.users[Config.userid] + '"/>';                            
                        }                        
                        //编辑页面的修改地址
                        if(id == 13){
                            changeOrderAddress();
                        }
                        //vip退款
                        if(id == 15){
                            $(".step2").show()
                        }
                        //获取是否需要审核
                        var step_is_check = $('#step' + id + '-is_check').val();
                        is_check.push(step_is_check);
                        //是否自动审核完成 start
                        var step_is_auto_complete = $('#step' + id + '-is_auto_complete').val();
                        input_content +='<input type="hidden" name="row[order_recept][auto_complete][' + id + ']" value="' + step_is_auto_complete + '"/>';
                        //是否自动审核完成  end
                    });
                    //判断如果存在1 则改为需要审核
                    if ($.inArray("1", is_check) != -1) {
                        $('#is_check').val(1);
                    } else {
                        $('#is_check').val(0);
                    }
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
                        //判断是客服工单还是仓库工单
                        // if (1 == Config.work_type) { //客服工单
                        //     var secondNode = $('.step' + problem_type_id + '-' + checkID[m]);
                        // } else if (2 == Config.work_type) { //仓库工单
                        //     if ((1 == problem_type_id) && (1 == checkID[m])) {
                        //         var secondNode = $('.step2' + '-' + checkID[m]);
                        //     } else if ((problem_type_id >= 2 || problem_type_id <= 3) && (1 == checkID[m])) {
                        //         var secondNode = $('.step1' + '-' + checkID[m]);
                        //     } else {
                        //         var secondNode = $('.step' + problem_type_id + '-' + checkID[m]);
                        //     }
                        // }
                        var secondNode = $('.step' + checkID[m] + '-' + checkID[m]);
                        if (secondNode.is(':hidden')) {
                            secondNode.show();
                        } else {
                            secondNode.hide();
                        }
                    }
                    var arr = array_filter(appoint_group.split(','));
                    var username = [];
                    var appoint_users = [];
                    //循环根据承接组Key获取对应承接人id
                    for (var i = 0; i < arr.length - 1; i++) {
                        //循环根据承接组Key获取对应承接人id
                        //appoint_users.push(Config.workorder[arr[i]]);
                        if(Config.workOrderConfigValue.group[arr[i]] !=undefined){
                            //console.log(Config.workOrderConfigValue.group[arr[i]]);
                            for(var n=0;n<Config.workOrderConfigValue.group[arr[i]].length;n++){
                                appoint_users.push(Config.workOrderConfigValue.group[arr[i]][n]);
                            }
                            
                        }
                        
                    }
                    if(count == 1){
                        appoint_users.push(Config.userid);
                    }else{
                         if(appoint_users[Config.userid]){
                            delOne(Config.userid,appoint_users);
                        }                         
                    }
                    //判断如果为补价 优惠券 积分 追加自己id为承接人
                    // var self = ["8","9","10"];
                    // var intersection = checkID.filter(function(v){ return self.indexOf(v) > -1 });
                    // if (intersection.length>0) {
                    //     appoint_users.push(Config.userid);
                    // }else{
                    //     if(appoint_users[Config.userid]){
                    //         delOne(Config.userid,appoint_users);
                    //     }   
                    // }
                    //循环根据承接人id获取对应人名称
                    for (var j = 0; j < appoint_users.length; j++) {
                        username.push(Config.users[appoint_users[j]]);
                    }
                    var users = array_filter(username);
                    $('#appoint_group_users').html(users.join(','));
                    $('#recept_person_id').val(appoint_users.join(','));

                    //判断更换处方的状态，如果显示的话把数据带出来，如果隐藏则不显示镜架数据 start
                    // if (!$('.step2-1').is(':hidden')) {
                    //     changeOrder(work_id, 2);
                    // }
                    //判断更换处方的状态，如果显示的话把数据带出来，如果隐藏则不显示镜架数据 end
                    //判断补发订单的状态，如果显示的话把数据带出来，如果隐藏则不显示补发数据 start
                    // if (!$('.step7').is(':hidden')) {
                    //    changeOrder(work_id, 5);
                    // }
                    //判断补发订单的状态，如果显示的话把数据带出来，如果隐藏则不显示补发数据 end
                    //判断赠品信息的状态，如果显示的话把数据带出来，如果隐藏的话则不显示赠品数据  start
                    // if (!$('.step6').is(':hidden')) {
                    //     changeOrder(work_id, 4);
                    // }
                    //判断赠品信息的状态，如果显示的话把数据带出来，如果隐藏的话则不显示赠品数据 end                    
                }
            });
            var lens_click_data_add_edit;
            var gift_click_data_add_edit;
            var prescriptions_add_edit;
            var is_add = 0;
            $(document).on('click', 'input[name="row[measure_choose_id][]"]', function () {
                if ($("body").find('input[name="row[replacement][original_sku][]"]').length <= 0 || $("body").find('input[name="row[gift][original_sku][]"]').length <= 0) {
                    is_add = 1;
                    var value = $(this).val();
                    var check = $(this).prop('checked');
                    var increment_id = $('#c-platform_order').val();
                    var is_new_version = $('#is_new_version').val();
                    if (increment_id) {
                        var site_type = $('#work_platform').val();
                        //补发
                        if (value == 7 && check === true) {
                            //获取补发的信息
                            Backend.api.ajax({
                                url: 'saleaftermanage/work_order_list/ajaxGetAddress',
                                data: {
                                    increment_id: increment_id,
                                    site_type: site_type,
                                    is_new_version: is_new_version
                                }
                            }, function (json, ret) {
                                if (json.code == 0) {
                                    Toastr.error(json.msg);
                                    return false;
                                }
                                var data = json.address;
                                var lens = json.lens;
                                prescriptions_add_edit = data.prescriptions;
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
                                        var email = data.address[i].email;
                                        if (email == null) {
                                            email = $('#customer_email').val();
                                        }
                                        $('#c-email').val(email);
                                        $('#c-telephone').val(data.address[i].telephone);
                                        $('#c-country').val(data.address[i].country_id);
                                        $('#c-country').change();
                                        if(data.address[i].region_id == '8888' || !data.address[i].region_id){
                                            $('#c-region').val(0);
                                        }else{
                                            $('#c-region').val(data.address[i].region_id);
                                        }
                                        $('#c-region1').val(data.address[i].region);
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
                                    var email = address.email;
                                    if (email == null) {
                                        email = $('#customer_email').val();
                                    }
                                    $('#c-email').val(email);
                                    $('#c-telephone').val(address.telephone);
                                    $('#c-country').val(address.country_id);
                                    $('#c-country').change();
                                    $('#c-region').val(address.region_id);
                                    $('#c-region1').val(address.region);
                                    $('#c-city').val(address.city);
                                    $('#c-street').val(address.street);
                                    $('#c-postcode').val(address.postcode);
                                })

                                //追加
                                lens_click_data_add_edit = '<div class="margin-top:10px;">' + lens.html + '<div class="form-group-child4_del" style="width: 96%;padding-right: 0px;"><a href="javascript:;" style="width: 50%;" class="btn btn-danger btn-del-lens" title="删除"><i class="fa fa-trash"></i>删除</a></div></div>';

                                $('.selectpicker ').selectpicker('refresh');
                            });
                        }
                        //更加镜架的更改
                        var question = $('input[name="row[problem_type_id]"]:checked').val();
                        if ((Config.work_type == 1 && value == 12  && check === true) || (Config.work_type == 2 && value == 12  && check === true)) {
                            Backend.api.ajax({
                                url: 'saleaftermanage/work_order_list/ajaxGetChangeLens',
                                data: {
                                    increment_id: increment_id,
                                    site_type: site_type,
                                    is_new_version: is_new_version
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
                                    is_new_version: is_new_version
                                }
                            }, function (data, ret) {
                                $('.add_gift').html(data.html);
                                //追加
                                gift_click_data_add_edit = '<div class="margin-top:10px;">' + data.html + '<div class="form-group-child4_del"  style="width: 96%;padding-right: 0px;"><a href="javascript:;" style="width: 50%;" class="btn btn-danger btn-del-lens" title="删除"><i class="fa fa-trash"></i>删除</a></div></div>';
                                $('.selectpicker ').selectpicker('refresh');
                            });
                        }

                        //判断更换镜框的状态，如果显示的话把原数据带出来，如果隐藏则不显示原数据 start
                        if (!$('.step1-1').is(':hidden')) {
                            changeFrame();
                        }
                        //判断更换镜框的状态，如果显示的话把原数据带出来，如果隐藏则不显示原数据 end
                        //判断取消订单的状态，如果显示的话把原数据带出来，如果隐藏则不显示原数据 start
                        if (!$('.step3').is(':hidden') && value == 3) {
                            cancelOrder();
                        }
                        //判断取消订单的状态，如果显示的话把原数据带出来，如果隐藏则不显示原数据 end
                    }
                }

            });
            //处方选择填充
            $(document).on('change', '#prescription_select', function () {
                if (is_add == 1) {
                    var val = $(this).val();
                    var prescription = prescriptions_add_edit[val];
                    var prescription_div = $(this).parents('.step7_function2').next('.step1_function3');
                    console.log(prescription);
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

                    if (prescription.hasOwnProperty("pd") && prescription.pd != '') {
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
                }
            })
            $(document).on('click', '.btn-add-box-edit', function () {
                if (is_add == 1) {
                    $('.add_gift').after(gift_click_data_add_edit);
                    $('.selectpicker ').selectpicker('refresh');
                }
            });
            $(document).on('click', '.btn-add-supplement-reissue-edit', function () {
                if (is_add == 1) {
                    $('#supplement-order').after(lens_click_data_add_edit);
                    $('.selectpicker ').selectpicker('refresh');
                }
            });

        },
        detail: function () {
            Controller.api.bindevent();
            $("input[name='row[measure_choose_id][]']:checked").each(function (i) {
                var id = $(this).val();
                if(id == 15){
                    $(".step2").show();
                }
                if(id == 13){
                    changeOrderAddress();
                }
            })

        },
        //处理任务
        process: function () {
            Controller.api.bindevent();
            $("input[name='row[measure_choose_id][]']:checked").each(function (i) {
                var id = $(this).val();
                if(id == 15){
                    $(".step2").show();
                }
                if(id == 13){
                    changeOrderAddress();
                }
            })
        },
        workordernote: function () {
            Form.api.bindevent($("form[role=form]"), function (data, ret) {
                Fast.api.close();
            }, function (data, ret) {
                Toastr.success("失败");
            });
        },
        couponlist: function () {
            // 初始化表格参数配置
            Table.api.init({
                showJumpto: true,
                searchFormVisible: true,
                pageList: [10, 25, 50, 100],
                extend: {
                    index_url: 'saleaftermanage/work_order_list/couponList' + location.search,

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
                        {
                            field: '', title: __('序号'), formatter: function (value, row, index) {
                                var options = table.bootstrapTable('getOptions');
                                var pageNumber = options.pageNumber;
                                var pageSize = options.pageSize;
                                return (pageNumber - 1) * pageSize + 1 + index;
                            }, operate: false
                        },
                        { field: 'id', title: __('Id'), operate: false, visible: false },
                        { field: 'work_platform', title: __('平台'), custom: { 1: 'blue', 2: 'danger', 3: 'orange' }, searchList: { 1: 'Zeelool', 2: 'Voogueme', 3: 'Nihao' }, formatter: Table.api.formatter.status },
                        { field: 'platform_order', title: __('订单号') },
                        { field: 'coupon_describe', title: __('优惠券名称'), operate: 'like' },
                        { field: 'coupon_str', title: __('优惠码'), operate: false },
                        { field: 'create_user_name', title: __('申请人'), operate: 'like' },
                        { field: 'create_time', title: __('申请时间'), operate: 'RANGE', addclass: 'datetimerange', formatter: Table.api.formatter.datetime },

                    ]
                ]
            });

            // 为表格绑定事件
            Table.api.bindevent(table);
        },
        integrallist: function () {
            // 初始化表格参数配置
            Table.api.init({
                showJumpto: true,
                searchFormVisible: true,
                pageList: [10, 25, 50, 100],
                extend: {
                    index_url: 'saleaftermanage/work_order_list/integralList' + location.search,

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
                        {
                            field: '', title: __('序号'), formatter: function (value, row, index) {
                                var options = table.bootstrapTable('getOptions');
                                var pageNumber = options.pageNumber;
                                var pageSize = options.pageSize;
                                return (pageNumber - 1) * pageSize + 1 + index;
                            }, operate: false
                        },
                        { field: 'id', title: __('Id'), operate: false, visible: false },
                        { field: 'work_platform', title: __('平台'), custom: { 1: 'blue', 2: 'danger', 3: 'orange' }, searchList: { 1: 'Zeelool', 2: 'Voogueme', 3: 'Nihao' }, formatter: Table.api.formatter.status },
                        { field: 'platform_order', title: __('订单号') },
                        { field: 'integral', title: __('积分'), operate: 'between' },
                        { field: 'email', title: __('客户邮箱'), operate: 'like' },
                        { field: 'integral_describe', title: __('积分描述'), operate: false },
                        { field: 'create_user_name', title: __('创建人'), operate: 'like' },
                        { field: 'create_time', title: __('创建时间'), operate: 'RANGE', addclass: 'datetimerange', formatter: Table.api.formatter.datetime },

                    ]
                ]
            });

            // 为表格绑定事件
            Table.api.bindevent(table);
        },
        api: {
            bindevent: function () {
                Form.api.bindevent($("form[role=form]"));

                //删除一行镜片数据
                $(document).on('click', '.btn-del-lens', function () {
                    $(this).parent().parent().remove();
                });
                //保存草稿
                $('.btn-warning').click(function () {
                    $('.status').val(1);
                })

                //提交审核按钮
                $('.btn-status').click(function () {
                    $('.status').val(2);
                })

                //提交审核按钮
                $('.btn-check-status').click(function () {
                    $('.check-status').val(2);
                })
                //提交处理按钮
                $('.btn-process-status-error').click(function () {
                    var recept_id = $(this).data('id');
                    var note = $(this).parents('tr').find('.process-note').val();
                    if (!note) {
                        Toastr.error('处理意见不能为空');
                        return false;
                    }
                    $('.process-recept-id').val(recept_id);
                    $('.process-status').val(2);
                    $('.process-recept-note').val(note);
                })
                $('.btn-process-status-success').click(function () {

                    var recept_id = $(this).data('id');
                    var note = $(this).parents('tr').find('.process-note').val();
                    if (!note) {
                        Toastr.error('处理意见不能为空');
                        return false;
                    }
                    $('.process-recept-id').val(recept_id);
                    $('.process-status').val(1);
                    $('.process-recept-note').val(note);
                })

                //优惠券下拉切换
                $(document).on('change', '#c-check_coupon', function () {
                    if ($('#c-check_coupon').val()) {
                        $('#c-need_check_coupon').val('');
                        $('.selectpicker ').selectpicker('refresh');
                    }
                })

                //优惠券下拉切换
                $(document).on('change', '#c-need_check_coupon', function () {
                    if ($('#c-need_check_coupon').val()) {
                        $('#c-check_coupon').val('');
                        $('.selectpicker ').selectpicker('refresh');
                    }
                })

                //删除一行镜架数据
                $(document).on('click', '.btn-del', function () {
                    $(this).parent().parent().remove();
                });

                //如果问题类型存在，显示问题类型和措施
                if (Config.problem_type_id) {
                    var id = Config.problem_type_id;
                    var work_id = $('#work_id').val();
                    //row[problem_type_id]
                    $("input[name='row[problem_type_id]'][value='" + id + "']").attr("checked", true);
                    //如果是仓库工单并且是草稿状态的话
                    if(1 == Config.work_status && 2 == Config.work_type){
                        $('#step_id').hide();
                        $('#recept_person_group').hide();
                        $('#after_user_group').show();
                        // $('#after_user_id').val(Config.workorder.copy_group);
                        // $('#after_user').html(Config.users[Config.workorder.copy_group]);
                        //异步获取跟单人员
                        Backend.api.ajax({
                            url: 'saleaftermanage/work_order_list/getDocumentaryRule',
                        }, function (data, ret) {
                            //console.log(data);
                            $('#all_after_user_id').val(data.join(','));
                            var content = '';
                            for(i=0;i<data.length;i++){
                                //$('#all_after_user').html(Config.users[data[i]]);
                                content += Config.users[data[i]]+' ';
                            }
                            $('#all_after_user').html(content);
                        },function(data,ret){
                            console.log(ret);
                            Toastr.error(ret.msg);
                            return false;
                        });
                        if(2 == Config.work_type){
                            //提交审核按钮
                            $('.btn-status').click(function () {
                                $('.status').val(3);
                            })
                        }
                    }
                    //判断是客服创建还是仓库创建
                    // if (Config.work_type == 1) {
                    //     var temp_id = 5;
                    // } else if (Config.work_type == 2) {
                    //     var temp_id = 4;
                    // }

                    // //id大于5 默认措施4
                    // if (id > temp_id) {
                    //     var steparr = Config.workorder['step04'];
                    //     for (var j = 0; j < steparr.length; j++) {
                    //         $('#step' + steparr[j].step_id).parent().show();
                    //         //读取对应措施配置
                    //         $('#step' + steparr[j].step_id + '-is_check').val(steparr[j].is_check);
                    //         $('#step' + steparr[j].step_id + '-appoint_group').val((steparr[j].appoint_group).join(','));
                    //     }
                    // } else {
                    //     //判断是客服创建还是仓库创建
                    //     if (Config.work_type == 1) {
                    //         var step = Config.workorder.customer_problem_group[id].step;
                    //     } else if (Config.work_type == 2) {
                    //         $('#step_id').hide();
                    //         $('#recept_person_group').hide();
                    //         $('#after_user_group').show();
                    //         $('#after_user_id').val(Config.workorder.copy_group);
                    //         $('#after_user').html(Config.users[Config.workorder.copy_group]);
                    //         var step = Config.workorder.warehouse_problem_group[id].step;
                    //     }
                    //     var steparr = Config.workorder[step];
                    //     for (var j = 0; j < steparr.length; j++) {
                    //         $('#step' + steparr[j].step_id).parent().show();
                    //         //读取对应措施配置
                    //         $('#step' + steparr[j].step_id + '-is_check').val(steparr[j].is_check);
                    //         $('#step' + steparr[j].step_id + '-appoint_group').val((steparr[j].appoint_group).join(','));
                    //     }
                    // }
                    //var id = $(this).val();
                    //var all_group = Config.workOrderConfigValue.group;
                    //所有的问题类型对应措施表
                    var all_problem_step = Config.workOrderConfigValue.all_problem_step;
                    //求出选择的问题类型对应的措施
                    var choose_problem_step = all_problem_step[id];
                    if(choose_problem_step == undefined){
                        Toastr.error('选择的问题类型没有对应的措施，请重新选择问题类型或者添加措施');
                        return false;
                    }
                    //循环列出对应的措施
                    for(var j=0;j<choose_problem_step.length;j++){
                        //console.log(choose_problem_step[j].step_id);
                        $('#step' + choose_problem_step[j].step_id).parent().show();
                        $('#step' + choose_problem_step[j].step_id + '-is_check').val(choose_problem_step[j].is_check);
                        $('#step' + choose_problem_step[j].step_id + '-is_auto_complete').val(choose_problem_step[j].is_auto_complete);
                        if(choose_problem_step[j].extend_group_id !=undefined && choose_problem_step[j].extend_group_id !=0){
                            $('#step' + choose_problem_step[j].step_id + '-appoint_group').val((choose_problem_step[j].extend_group_id));
                        }else{
                            $('#step' + choose_problem_step[j].step_id + '-appoint_group').val(0);
                        }    
                    }
                    if (Config.measureList) {
                        var id = Config.problem_type_id;
                        var work_id = $('#work_id').val();
                        //row[problem_type_id]
                        $("input[name='row[problem_type_id]'][value='" + id + "']").attr("checked", true);
                        //判断是客服创建还是仓库创建
                        // if (Config.work_type == 1) {
                        //     var temp_id = 5;
                        // } else if (Config.work_type == 2) {
                        //     var temp_id = 4;
                        // }
                        // //id大于5 默认措施4
                        // if (id > temp_id) {
                        //     var steparr = Config.workorder['step04'];
                        //     for (var j = 0; j < steparr.length; j++) {
                        //         $('#step' + steparr[j].step_id).parent().show();
                        //         //读取对应措施配置
                        //         $('#step' + steparr[j].step_id + '-is_check').val(steparr[j].is_check);
                        //         $('#step' + steparr[j].step_id + '-appoint_group').val((steparr[j].appoint_group).join(','));
                        //     }
                        // } else {
                        //     //判断是客服创建还是仓库创建
                        //     if (Config.work_type == 1) {
                        //         var step = Config.workorder.customer_problem_group[id].step;
                        //     } else if (Config.work_type == 2) {
                        //         var step = Config.workorder.warehouse_problem_group[id].step;
                        //     }
                        //     var steparr = Config.workorder[step];
                        //     for (var j = 0; j < steparr.length; j++) {
                        //         $('#step' + steparr[j].step_id).parent().show();
                        //         //读取对应措施配置
                        //         $('#step' + steparr[j].step_id + '-is_check').val(steparr[j].is_check);
                        //         $('#step' + steparr[j].step_id + '-appoint_group').val((steparr[j].appoint_group).join(','));
                        //     }
                        // }
                        if (Config.measureList) {
                            var checkIDss = Config.measureList;//措施列表赋值给checkID
                            //console.log(checkIDss);
                            for (var m = 0; m < checkIDss.length; m++) {
                                $("input[name='row[measure_choose_id][]'][value='" + checkIDss[m] + "']").attr("checked", true);
                                var node = $('.step' + checkIDss[m]);
                                if (node.is(':hidden')) {
                                    node.show();
                                } else {
                                    node.hide();
                                }
                                //判断是客服工单还是仓库工单
                                // if (1 == Config.work_type) { //客服工单
                                //     var secondNode = $('.step' + id + '-' + checkIDss[m]);
                                // } else if (2 == Config.work_type) { //仓库工单
                                //     if ((1 == id) && (1 == checkIDss[m])) {
                                //         var secondNode = $('.step2' + '-' + checkIDss[m]);
                                //     } else if ((id >= 2 || id <= 3) && (1 == checkIDss[m])) {
                                //         var secondNode = $('.step1' + '-' + checkIDss[m]);
                                //     } else {
                                //         var secondNode = $('.step' + id + '-' + checkIDss[m]);
                                //     }
                                // }
                                var secondNode = $('.step' + checkIDss[m] + '-' + checkIDss[m]); 
                                console.log(secondNode);
                                if (secondNode.is(':hidden')) {
                                    secondNode.show();
                                } else {
                                    secondNode.hide();
                                }

                            }
                            var checkID = [];//定义一个空数组
                            var appoint_group = '';
                            var input_content = '';
                            var appoint_users = [];
                            var lens_click_data_edit;
                            var gift_click_data_edit;
                            var username = [];
                            var appoint_users = [];
                            var count = 0;
                            $("input[name='row[measure_choose_id][]']:checked").each(function (i) {
                                checkID[i] = $(this).val();
                                var id = $(this).val();
                                //获取承接组
                                appoint_group += $('#step' + id + '-appoint_group').val() + ',';
                                //var group = $('#step' + id + '-appoint_group').val();
                                //var group_arr = group.split(',')
                                //var appoint_users = [];
                                //var appoint_val = [];
                                // for (var i = 0; i < group_arr.length; i++) {
                                //     //循环根据承接组Key获取对应承接人id
                                //     appoint_users.push(Config.workorder[group_arr[i]]);
                                //     appoint_val[Config.workorder[group_arr[i]]] = group_arr[i];
                                // }

                                // //循环根据承接人id获取对应人名称
                                // for (var j = 0; j < appoint_users.length; j++) {
                                //     input_content += '<input type="hidden" name="row[order_recept][appoint_group][' + id + '][]" value="' + appoint_val[appoint_users[j]] + '"/>';
                                //     input_content += '<input type="hidden" name="row[order_recept][appoint_ids][' + id + '][]" value="' + appoint_users[j] + '"/>';
                                //     input_content += '<input type="hidden" name="row[order_recept][appoint_users][' + id + '][]" value="' + Config.users[appoint_users[j]] + '"/>';
                                // }
                                var group_id = $('#step' + id + '-appoint_group').val();
                                var choose_group = Config.workOrderConfigValue.group[group_id];
                                if(choose_group){
                                    for(var j = 0;j<choose_group.length;j++){
                                        input_content += '<input type="hidden" name="row[order_recept][appoint_group][' + id + '][]" value="' + group_id + '"/>';
                                        input_content += '<input type="hidden" name="row[order_recept][appoint_ids][' + id + '][]" value="' + choose_group[j] + '"/>';
                                        input_content += '<input type="hidden" name="row[order_recept][appoint_users][' + id + '][]" value="' + Config.users[choose_group[j]] + '"/>';                            
                                    }
                                }else{
                                    count =1;
                                    input_content += '<input type="hidden" name="row[order_recept][appoint_group][' + id + '][]" value="0"/>';
                                    input_content += '<input type="hidden" name="row[order_recept][appoint_ids][' + id + '][]" value="' + Config.userid + '"/>';
                                    input_content += '<input type="hidden" name="row[order_recept][appoint_users][' + id + '][]" value="' + Config.users[Config.userid] + '"/>';                            
                                }                                

                                //获取是否需要审核
                                if ($('#step' + id + '-is_check').val() > 0) {
                                    $('#is_check').val(1);
                                }
                                //是否自动审核完成 start
                                var step_is_auto_complete = $('#step' + id + '-is_auto_complete').val();
                                input_content +='<input type="hidden" name="row[order_recept][auto_complete][' + id + ']" value="' + step_is_auto_complete + '"/>';
                        //是否自动审核完成  end
                            });
                            //追加到元素之后
                            $("#input-hidden").append(input_content);
                            var arr = array_filter(appoint_group.split(','));
                            console.log(arr);
                            //循环根据承接组Key获取对应承接人id
                            // for (var i = 0; i < arr.length - 1; i++) {
                            //     //循环根据承接组Key获取对应承接人id
                            //     appoint_users.push(Config.workorder[arr[i]]);
                            // }
                            for (var i = 0; i < arr.length - 1; i++) {
                                //循环根据承接组Key获取对应承接人id
                                //appoint_users.push(Config.workorder[arr[i]]);
                                if(Config.workOrderConfigValue.group[arr[i]] !=undefined){
                                    //console.log(Config.workOrderConfigValue.group[arr[i]]);
                                    for(var n=0;n<Config.workOrderConfigValue.group[arr[i]].length;n++){
                                        appoint_users.push(Config.workOrderConfigValue.group[arr[i]][n]);
                                    }
                                    
                                }
                                
                            }
                            // if(count == 1){
                            //     appoint_users.push(Config.userid);
                            //     //appoint_users.push(Config.create_user_id);
                            // }else{
                            //      if(appoint_users[Config.create_user_id]){
                            //         delOne(Config.userid,appoint_users);
                            //     }                         
                            // }
                            // if(checkID.length>0 && appoint_users.length === 0){
                            //     if(!appoint_users[Config.userid]){
                            //         appoint_users.push(Config.userid);
                            //     }
                            // }else if(checkID.length === 0){
                            //     if(appoint_users[Config.userid]){
                            //         delOne(Config.userid,appoint_users);
                            //     } 
                            // }
                            if(count == 1){
                                appoint_users.push(Config.create_user_id);
                            }else{
                                 if(appoint_users[Config.create_user_id]){
                                    delOne(Config.create_user_id,appoint_users);
                                }                         
                            }
                            if(checkID.length>0 && appoint_users.length === 0){
                                if(!appoint_users[Config.create_user_id]){
                                    appoint_users.push(Config.create_user_id);
                                }
                            }else if(checkID.length === 0){
                                if(appoint_users[Config.create_user_id]){
                                    delOne(Config.create_user_id,appoint_users);
                                } 
                            }

                            //循环根据承接人id获取对应人名称
                            var appoint_users = array_filter(appoint_users);
                            console.log(appoint_users);
                            for (var j = 0; j < appoint_users.length; j++) {
                                username.push(Config.users[appoint_users[j]]);
                            }

                            var users = array_filter(username);
                            
                            $('#appoint_group_users').html(users.join(','));
                            $('#recept_person_id').val(appoint_users.join(','));
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
                        //判断更换处方的状态，如果显示的话把数据带出来，如果隐藏则不显示镜架数据 start
                        if (!$('.step12-12').is(':hidden')) {
                            if(!checkIDss.includes(15)){
                                changeOrder(work_id, 2);
                            }
                            
                        }
                        //判断更换处方的状态，如果显示的话把数据带出来，如果隐藏则不显示镜架数据 end
                        //判断补发订单的状态，如果显示的话把数据带出来，如果隐藏则不显示补发数据 start
                        if (!$('.step7').is(':hidden')) {
                            changeOrder(work_id, 5);
                        }
                        //判断补发订单的状态，如果显示的话把数据带出来，如果隐藏则不显示补发数据 end
                        //判断赠品信息的状态，如果显示的话把数据带出来，如果隐藏的话则不显示赠品数据  start
                        if (!$('.step6').is(':hidden')) {
                            changeOrder(work_id, 4);
                        }
                        //判断赠品信息的状态，如果显示的话把数据带出来，如果隐藏的话则不显示赠品数据 end
                    }
                    function changeOrder(work_id, change_type) {
                        var ordertype = $('#work_platform').val();
                        var order_number = $('#c-platform_order').val();
                        var is_new_version = $('#is_new_version').val();
                        if (!order_number) {
                            return false;
                        }
                        if (ordertype <= 0) {
                            Layer.alert('请选择正确的平台');
                            return false;
                        }
                        var operate_type = Config.operate_type;
                        Backend.api.ajax({
                            url: 'saleaftermanage/work_order_list/ajax_change_order',
                            data: { change_type: change_type, order_number: order_number, work_id: work_id, order_type: ordertype, operate_type: operate_type,is_new_version: is_new_version }
                        }, function (json, ret) {
                            //补发订单信息
                            if (5 == change_type) {
                                //读取的订单地址信息
                                var data = json.address;
                                //读取的订单镜片信息
                                var lens = json.lens;
                                //读取的存入数据库的地址
                                var real_address = json.arr;
                                prescriptions = data.prescriptions;
                                $('#supplement-order').html(lens.html);
                                var order_pay_currency = $('#order_pay_currency').val();
                                //修改地址
                                var address = '';
                                if (real_address) {
                                    $('#c-firstname').val(real_address.firstname);
                                    $('#c-lastname').val(real_address.lastname);
                                    var email = real_address.email;
                                    if (email == null) {
                                        email = $('#customer_email').val();
                                    }
                                    $('#c-email').val(email);
                                    $('#c-telephone').val(real_address.telephone);
                                    $('#c-country').val(real_address.country_id);
                                    $('#c-country').change();
                                    $('#c-region').val(real_address.region_id);
                                    $('#c-region1').val(real_address.region);
                                    $('#c-city').val(real_address.city);
                                    $('#c-street').val(real_address.street);
                                    $('#c-postcode').val(real_address.postcode);
                                    $('#c-currency_code').val(order_pay_currency);
                                    $('#shipping_type').val(real_address.shipping_type);
                                    for (var i = 0; i < data.address.length; i++) {
                                        if (i == real_address.address_type) {
                                            address += '<option value="' + i + '" selected>' + data.address[i].address_type + '</option>';
                                        } else {
                                            address += '<option value="' + i + '">' + data.address[i].address_type + '</option>';
                                        }
                                    }
                                } else {
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
                                            $('#c-region1').val(data.address[i].region);
                                            $('#c-city').val(data.address[i].city);
                                            $('#c-street').val(data.address[i].street);
                                            $('#c-postcode').val(data.address[i].postcode);
                                            $('#c-currency_code').val(order_pay_currency);
                                        } else {
                                            address += '<option value="' + i + '">' + data.address[i].address_type + '</option>';
                                        }

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
                                    $('#c-region1').val(address.region);
                                    $('#c-city').val(address.city);
                                    $('#c-street').val(address.street);
                                    $('#c-postcode').val(address.postcode);
                                })

                                //追加
                                lens_click_data_edit = '<div class="margin-top:10px;">' + json.lensform.html + '<div class="form-group-child4_del" style="width: 96%;padding-right: 0px;"><a href="javascript:;" style="width: 50%;" class="btn btn-danger btn-del-lens" title="删除"><i class="fa fa-trash"></i>删除</a></div></div>';

                                $('.selectpicker ').selectpicker('refresh');
                                //Controller.api.bindevent();            
                            } else if (2 == change_type) { //更换镜架信息
                                $('#lens_contents').html(json.lens.html);
                                $('.selectpicker').selectpicker('refresh');
                            } else if (4 == change_type) {
                                $('.add_gift').html(json.lens.html);
                                //追加
                                gift_click_data_edit = '<div class="margin-top:10px;">' + json.lensform.html + '<div class="form-group-child4_del" style="width: 96%;padding-right: 0px;"><a href="javascript:;" style="width: 50%;" class="btn btn-danger btn-del-lens" title="删除"><i class="fa fa-trash"></i>删除</a></div></div>';
                                $('.selectpicker ').selectpicker('refresh');
                            }
                        }, function (data, ret) {
                            //失败的回调
                            alert(ret.msg);
                            console.log(ret);
                            return false;
                        });
                    }
                }

                $(document).on('click', '.btn-add-box-edit', function () {
                    $('.add_gift').after(gift_click_data_edit);
                    $('.selectpicker ').selectpicker('refresh');
                });
                $(document).on('click', '.btn-add-supplement-reissue-edit', function () {
                    $('#supplement-order').after(lens_click_data_edit);
                    $('.selectpicker ').selectpicker('refresh');
                });
                $(document).on('click', 'input[name="row[measure_choose_id][]"]', function () {
                    var value = $(this).val();
                    var check = $(this).prop('checked');
                    var increment_id = $('#c-platform_order').val();
                    if (increment_id) {
                        var site_type = $('#work_platform').val();
                        //补发
                        if (value == 9 && check === true) {
                            var check_coupon = Config.workorder.check_coupon;
                            var need_check_coupon = Config.workorder.need_check_coupon;
                            check_coupon_option = '<option value="0">请选择</option>';
                            need_check_coupon_option = '<option value="0">请选择</option>';
                            for (i in check_coupon) {
                                if (check_coupon[i].site == site_type) {
                                    check_coupon_option += '<option value="' + check_coupon[i].id + '">' + check_coupon[i].desc + '</option>';
                                }
                            }
                            for (i in need_check_coupon) {
                                if (need_check_coupon[i].site == site_type) {
                                    need_check_coupon_option += '<option value="' + need_check_coupon[i].id + '">' + need_check_coupon[i].desc + '</option>';
                                }
                            }
                            $('#c-check_coupon').html(check_coupon_option);
                            $('#c-need_check_coupon').html(need_check_coupon_option);
                            $('.selectpicker ').selectpicker('refresh');
                        }
                    }
                });
                $(document).on('click', '.btn-edit-supplement-reissue', function () {
                    $('#supplement-order').after(lens_click_data);
                    $('.selectpicker ').selectpicker('refresh');
                });
                $(document).on('click', '.btn-edit-box', function () {
                    $('.add_gift').after(gift_click_data);
                    $('.selectpicker ').selectpicker('refresh');
                });
                //根据prescription_type获取lens_type
                $(document).on('change', 'select[name="row[replacement][recipe_type][]"],select[name="row[change_lens][recipe_type][]"],select[name="row[gift][recipe_type][]"]', function () {
                    var sitetype = $('#work_platform').val();
                    var prescription_type = $(this).val();
                    var is_new_version = $('#is_new_version').val();
                    if (!sitetype || !prescription_type) {
                        return false;
                    }
                    var that = $(this);
                    Backend.api.ajax({
                        url: 'saleaftermanage/work_order_list/ajaxGetLensType',
                        data: {
                            site_type: sitetype,
                            prescription_type: prescription_type,
                            is_new_version: is_new_version
                        }
                    }, function (data, ret) {
                        //console.log(data);
                        var prescription_div = that.parents('.prescription_type_step').next('div');
                        var lens_type;
                        for (var i = 0; i < data.length; i++) {
                            lens_type += '<option value="' + data[i].lens_id + '">' + data[i].lens_data_name + '</option>';
                        }
                        //console.log(lens_type);
                        prescription_div.find('#lens_type').html(lens_type);
                        if(is_new_version == 0){
                            prescription_div.find('#color_type').val('');
                        }

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
                    var is_new_version = $('#is_new_version').val();
                    var that = $(this);
                    if(is_new_version == 0 && sitetype == 1){
                        Backend.api.ajax({
                                url: 'saleaftermanage/work_order_list/ajaxGetLensType',
                                data: {
                                    site_type: sitetype,
                                    color_id: color_id,
                                    is_new_version: is_new_version
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
                    }

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
                            var province = '<option value="0">请选择</option>';
                            for (var i = 0; i < data.length; i++) {
                                province += '<option value="' + data[i].region_id + '">' + data[i].default_name + '</option>';
                            }
                            $('#c-region').html(province);
                            $('.selectpicker ').selectpicker('refresh');
                        }
                    });
                });
                //省市二级联动
                $(document).on('change', '#c-country1', function () {
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
                            var province = '<option value="0">请选择</option>';
                            for (var i = 0; i < data.length; i++) {
                                province += '<option value="' + data[i].region_id + '">' + data[i].default_name + '</option>';
                            }
                            $('#c-region2').html(province);
                            $('.selectpicker ').selectpicker('refresh');
                        }
                    });
                });
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
        var datas = { ordertype: ordertype, order_number: order_number, work_id: work_id, change_type: 1 };
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
        var datas = { ordertype: ordertype, order_number: order_number, work_id: work_id, change_type: 3 };
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
//js 删除指定数组的元素
function delOne(str,arr){
    //获取指定元素的索引
    var index = arr.indexOf(str);
    arr.splice(index,1);
    //document.write(arr);
}
//修改地址
function changeOrderAddress(){
    $("#user_address").show();
    var incrementId = $('#c-platform_order').val();
    var work_id = $('#work_id').val();
    if (!incrementId) {
        Toastr.error('订单号不能为空');
        return false;
    } else {
        var site_type = $('#work_platform').val();
        //id == 3修改地址
        //获取用户地址的信息
        Backend.api.ajax({
            url: 'saleaftermanage/work_order_list/ajaxGetAddress',
            data: {
                increment_id: incrementId,
                site_type: site_type,
                work_id: work_id,
            }
        }, function (json, ret) {
            if (json.code == 0) {
                Toastr.error(json.msg);
                return false;
            }
            var data = json.address;
            var order_pay_currency = $('#order_pay_currency').val();
            //修改地址
            var address1 = '';
            for (var i = 0; i < data.address.length; i++) {
                var address_id= data.address[i].address_id ? data.address[i].address_id : 0;
                if(i == address_id){
                    address1 += '<option value="' + i + '" selected>' + data.address[i].address_type + '</option>';
                    //补发地址自动填充第一个
                    $('#c-firstname1').val(data.address[i].firstname);
                    $('#c-lastname1').val(data.address[i].lastname);
                    var email = data.address[i].email;
                    if (email == null) {
                        email = $('#customer_email1').val();
                    }
                    $('#c-email1').val(email);
                    $('#c-telephone1').val(data.address[i].telephone);
                    $('#c-country1').val(data.address[i].country_id);
                    $('#c-country1').change();
                    if(data.address[i].region_id == '8888' || !data.address[i].region_id){
                        $('#c-region2').val(0);
                    }else{
                        $('#c-region2').val(data.address[i].region_id);
                    }
                    $('#c-region12').val(data.address[i].region);
                    $('#c-city1').val(data.address[i].city);
                    $('#c-street1').val(data.address[i].street);
                    $('#c-postcode1').val(data.address[i].postcode);
                    $('#c-currency_code1').val(order_pay_currency);
                }else{
                    address1 += '<option value="' + i + '">' + data.address[i].address_type + '</option>';
                }
            }
            $('#address_select1').html(address1);
            //选择地址切换地址
            $('#address_select1').change(function () {
                var address_id = $(this).val();
                var address = data.address[address_id];
                $('#c-firstname1').val(address.firstname);
                $('#c-lastname1').val(address.lastname);
                $('#c-email1').val(address.email);
                $('#c-telephone1').val(address.telephone);
                $('#c-country1').val(address.country_id);
                $('#c-country1').change();
                $('#c-region12').val(address.region);
                $('#c-region2').val(address.region_id);
                $('#c-city1').val(address.city);
                $('#c-street1').val(address.street);
                $('#c-postcode1').val(address.postcode);
            })
            $('.selectpicker ').selectpicker('refresh');
        });
    }
}

