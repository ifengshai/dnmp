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
            //增加一行镜架数据
            $(document).on('click', '.btn-add', function () {
                var rows =  document.getElementById("caigou-table-sku").rows.length;
                var content = '<tr>'+
                    '<td><input id="c-original_sku" class="form-control" name="row[item]['+rows+'][original_sku]" type="text"></td>'+
                    '<td><input id="c-original_number" class="form-control" name="row[item]['+rows+'][original_number]" type="text"></td>'+
                    '<td><input id="c-change_sku" class="form-control change_sku" name="row[item]['+rows+'][change_sku]" type="text"></td>'+
                    '<td><input id="c-change_number" class="form-control change_number" name="row[item]['+rows+'][change_number]" type="text"></td>'+
                    '<td><a href="javascript:;" class="btn btn-danger btn-del" title="删除"><i class="fa fa-trash"></i> 删除</a></td>'+
                    '</tr>';
                $('.caigou table tbody').append(content);
            });
            //删除一行镜架数据
            $(document).on('click', '.btn-del', function () {
                $(this).parent().parent().remove();
            });
            //增加一行镜片数据
            $(document).on('click','.btn-add-lens',function(){
                    var contents = '<div><div class="step1_function2">\n' +
                        '                    <div class="step1_function2_child">\n' +
                        '                        <label class="control-label col-xs-12 col-sm-3">SKU:</label>\n' +
                        '                        <div class="col-xs-12 col-sm-8">\n' +
                        '                            <select class="form-control selectpicker" name="row[lens][original_sku][]">\n' +
                        '                                <option value="0">请选择</option>\n' +
                        '                            </select>\n' +
                        '                        </div>\n' +
                        '                    </div>\n' +
                        '\n' +
                        '                    <div class="step1_function2_child">\n' +
                        '                        <label class="control-label col-xs-12 col-sm-3">Name:</label>\n' +
                        '                        <div class="col-xs-12 col-sm-8">\n' +
                        '                            <input class="form-control" name="row[lens][original_name][]" type="text" value="">\n' +
                        '                        </div>\n' +
                        '                    </div>\n' +
                        '                    <div class="step1_function2_child">\n' +
                        '                        <label class="control-label col-xs-12 col-sm-3">QTY:</label>\n' +
                        '                        <div class="col-xs-12 col-sm-8">\n' +
                        '                            <input class="form-control" name="row[lens][original_number][]" type="text" value="">\n' +
                        '                        </div>\n' +
                        '                    </div>\n' +
                        '\n' +
                        '                    <div class="step1_function2_child">\n' +
                        '                        <label class="control-label col-xs-12 col-sm-3">prescription_type:</label>\n' +
                        '                        <div class="col-xs-12 col-sm-8">\n' +
                        '                            <select class="form-control selectpicker" name="row[lens][recipe_type][]">\n' +
                        '                                <option value="0">请选择</option>\n' +
                        '                            </select>\n' +
                        '                        </div>\n' +
                        '                    </div>\n' +
                        '\n' +
                        '                </div>\n' +
                        '\n' +
                        '                <div class="step7_function3">\n' +
                        '                    <div class="panel-body">\n' +
                        '                        <div class="step7_function3_child">\n' +
                        '                            <label class="control-label col-xs-12 col-sm-3">lens_type:</label>\n' +
                        '                            <div class="col-xs-12 col-sm-8">\n' +
                        '                                <select class="form-control selectpicker" name="row[lens][lens_type][]">\n' +
                        '                                    <option value="0">请选择</option>\n' +
                        '                                </select>\n' +
                        '                            </div>\n' +
                        '                        </div>\n' +
                        '                        <div class="step7_function3_child">\n' +
                        '                            <label class="control-label col-xs-12 col-sm-3">coating_type:</label>\n' +
                        '                            <div class="col-xs-12 col-sm-8">\n' +
                        '                                <select  id="c-coating_type" class="form-control selectpicker" name="row[lens][coating_type][]">\n' +
                        '                                    <option value="0">请选择</option>\n' +
                        '                                </select>\n' +
                        '                            </div>\n' +
                        '                        </div>\n' +
                        '                        <table>\n' +
                        '                            <tbody>\n' +
                        '                            <tr>\n' +
                        '                                <td style="text-align: center">value</td>\n' +
                        '                                <td style="text-align: center">SPH</td>\n' +
                        '                                <td style="text-align: center">CYL</td>\n' +
                        '                                <td style="text-align: center">AXI</td>\n' +
                        '                                <td style="text-align: center">ADD</td>\n' +
                        '                                <td style="text-align: center">PD</td>\n' +
                        '                            </tr>\n' +
                        '                            <tr>\n' +
                        '                                <td style="text-align: center">Right(OD)</td>\n' +
                        '                                <td><input class="form-control" name="row[lens][od_sph][]" type="text" value=""></td>\n' +
                        '                                <td><input class="form-control" name="row[lens][od_cyl][]" type="text" value=""></td>\n' +
                        '                                <td><input class="form-control" name="row[lens][od_axis][]" type="text" value=""></td>\n' +
                        '                                <td><input class="form-control" name="row[lens][od_add][]" type="text" value=""></td>\n' +
                        '                                <td><input class="form-control" name="row[lens][pd_r][]" type="text" value=""></td>\n' +
                        '                            </tr>\n' +
                        '                            <tr>\n' +
                        '                                <td style="text-align: center">Left(OS)</td>\n' +
                        '                                <td><input class="form-control" name="row[lens][os_sph][]" type="text" value=""></td>\n' +
                        '                                <td><input class="form-control" name="row[lens][os_cyl][]" type="text" value=""></td>\n' +
                        '                                <td><input class="form-control" name="row[lens][os_axis][]" type="text" value=""></td>\n' +
                        '                                <td><input class="form-control" name="row[lens][os_add][]" type="text" value=""></td>\n' +
                        '                                <td><input class="form-control" name="row[lens][pd_l][]" type="text" value=""></td>\n' +
                        '                            </tr>\n' +
                        '\n' +
                        '                            <tr>\n' +
                        '                                <td style="text-align: center"></td>\n' +
                        '                                <td style="text-align: center">Prism Horizontal</td>\n' +
                        '                                <td style="text-align: center">Base Direction</td>\n' +
                        '                                <td style="text-align: center">Prism Vertical</td>\n' +
                        '                                <td style="text-align: center">Base Direction</td>\n' +
                        '                            </tr>\n' +
                        '                            <tr>\n' +
                        '                                <td style="text-align: center">Right(OD)</td>\n' +
                        '                                <td><input class="form-control" type="text" name="row[lens][od_pv][]" value=""></td>\n' +
                        '                                <td><input class="form-control" type="text" name="row[lens][od_bd][]" value=""></td>\n' +
                        '                                <td><input class="form-control" type="text" name="row[lens][od_pv_r][]" value=""></td>\n' +
                        '                                <td><input class="form-control" type="text" name="row[lens][od_bd_r][]" value=""></td>\n' +
                        '                            </tr>\n' +
                        '                            <tr>\n' +
                        '                                <td style="text-align: center">Left(OS)</td>\n' +
                        '                                <td><input class="form-control" name="row[lens][os_pv][]" type="text" value=""></td>\n' +
                        '                                <td><input class="form-control" name="row[lens][os_bd][]" type="text" value=""></td>\n' +
                        '                                <td><input class="form-control" name="row[lens][os_pv_r][]" type="text" value=""></td>\n' +
                        '                                <td><input class="form-control" name="row[lens][os_bd_r][]" type="text" value=""></td>\n' +
                        '                            </tr>\n' +
                        '                            </tbody>\n' +
                        '                        </table>\n' +
                        '                    </div>\n' +
                        '                </div>\n' +
                        '\n' +
                        '                <div class="form-group-child4_del">\n' +
                        '                    <a href="javascript:;" style="width: 50%;" class="btn btn-danger btn-del-lens" title="删除"><i class="fa fa-trash"></i>删除</a>\n' +
                        '                </div></div>';
                     $('#btn-add-lens').after(contents);
                    Controller.api.bindevent();
            });
            //删除一行镜片数据
            $(document).on('click', '.btn-del-lens', function () {
                $(this).parent().parent().remove();
            });

            //补发 start
            $(document).on('click', '.btn-add-supplement', function () {
                var contents = '<div>\n' +
                    '                    <div class="step7_function2">\n' +
                    '                        <div class="step7_function2_child">\n' +
                    '                            <label class="control-label col-xs-12 col-sm-3">SKU:</label>\n' +
                    '                            <div class="col-xs-12 col-sm-8">\n' +
                    '                                <select class="form-control selectpicker" name="row[lens][original_sku][]">\n' +
                    '                                    <option value="0">请选择</option>\n' +
                    '                                </select>\n' +
                    '                            </div>\n' +
                    '                        </div>\n' +
                    '\n' +
                    '                        <div class="step7_function2_child">\n' +
                    '                            <label class="control-label col-xs-12 col-sm-3">Name:</label>\n' +
                    '                            <div class="col-xs-12 col-sm-8">\n' +
                    '                                <input class="form-control" name="row[lens][original_name][]" type="text" value="">\n' +
                    '                            </div>\n' +
                    '                        </div>\n' +
                    '                        <div class="step7_function2_child">\n' +
                    '                            <label class="control-label col-xs-12 col-sm-3">QTY:</label>\n' +
                    '                            <div class="col-xs-12 col-sm-8">\n' +
                    '                                <input class="form-control" name="row[lens][original_number][]" type="text" value="">\n' +
                    '                            </div>\n' +
                    '                        </div>\n' +
                    '                        <div class="step7_function2_child">\n' +
                    '                            <label class="control-label col-xs-12 col-sm-3">选择已有处方:</label>\n' +
                    '                            <div class="col-xs-12 col-sm-8">\n' +
                    '                                <select class="form-control selectpicker" name="row[]">\n' +
                    '                                    <option value="0">请选择</option>\n' +
                    '                                </select>\n' +
                    '                            </div>\n' +
                    '                        </div>\n' +
                    '                        <div class="step7_function2_child">\n' +
                    '                            <label class="control-label col-xs-12 col-sm-3">prescription_type:</label>\n' +
                    '                            <div class="col-xs-12 col-sm-8">\n' +
                    '                                <select class="form-control selectpicker" name="row[lens][recipe_type][]">\n' +
                    '                                    <option value="0">请选择</option>\n' +
                    '                                </select>\n' +
                    '                            </div>\n' +
                    '                        </div>\n' +
                    '\n' +
                    '                    </div>\n' +
                    '\n' +
                    '                    <div class="step1_function3">\n' +
                    '                        <div class="panel-body">\n' +
                    '                            <div class="step1_function3_child">\n' +
                    '                                <label class="control-label col-xs-12 col-sm-3">lens_type:</label>\n' +
                    '                                <div class="col-xs-12 col-sm-8">\n' +
                    '                                    <select class="form-control selectpicker" name="row[lens][lens_type][]">\n' +
                    '                                        <option value="0">请选择</option>\n' +
                    '                                    </select>\n' +
                    '                                </div>\n' +
                    '                            </div>\n' +
                    '                            <div class="step1_function3_child">\n' +
                    '                                <label class="control-label col-xs-12 col-sm-3">coating_type:</label>\n' +
                    '                                <div class="col-xs-12 col-sm-8">\n' +
                    '                                    <select  id="c-coating_type" class="form-control selectpicker" name="row[lens][coating_type][]">\n' +
                    '                                        <option value="0">请选择</option>\n' +
                    '                                    </select>\n' +
                    '                                </div>\n' +
                    '                            </div>\n' +
                    '                            <table>\n' +
                    '                                <tbody>\n' +
                    '                                <tr>\n' +
                    '                                    <td style="text-align: center">value</td>\n' +
                    '                                    <td style="text-align: center">SPH</td>\n' +
                    '                                    <td style="text-align: center">CYL</td>\n' +
                    '                                    <td style="text-align: center">AXI</td>\n' +
                    '                                    <td style="text-align: center">ADD</td>\n' +
                    '                                    <td style="text-align: center">PD</td>\n' +
                    '                                </tr>\n' +
                    '                                <tr>\n' +
                    '                                    <td style="text-align: center">Right(OD)</td>\n' +
                    '                                    <td><input class="form-control" name="row[lens][od_sph][]" type="text" value=""></td>\n' +
                    '                                    <td><input class="form-control" name="row[lens][od_cyl][]" type="text" value=""></td>\n' +
                    '                                    <td><input class="form-control" name="row[lens][od_axis][]" type="text" value=""></td>\n' +
                    '                                    <td><input class="form-control" name="row[lens][od_add][]" type="text" value=""></td>\n' +
                    '                                    <td><input class="form-control" name="row[lens][pd_r][]" type="text" value=""></td>\n' +
                    '                                </tr>\n' +
                    '                                <tr>\n' +
                    '                                    <td style="text-align: center">Left(OS)</td>\n' +
                    '                                    <td><input class="form-control" name="row[lens][os_sph][]" type="text" value=""></td>\n' +
                    '                                    <td><input class="form-control" name="row[lens][os_cyl][]" type="text" value=""></td>\n' +
                    '                                    <td><input class="form-control" name="row[lens][os_axis][]" type="text" value=""></td>\n' +
                    '                                    <td><input class="form-control" name="row[lens][os_add][]" type="text" value=""></td>\n' +
                    '                                    <td><input class="form-control" name="row[lens][pd_l][]" type="text" value=""></td>\n' +
                    '                                </tr>\n' +
                    '\n' +
                    '                                <tr>\n' +
                    '                                    <td style="text-align: center"></td>\n' +
                    '                                    <td style="text-align: center">Prism Horizontal</td>\n' +
                    '                                    <td style="text-align: center">Base Direction</td>\n' +
                    '                                    <td style="text-align: center">Prism Vertical</td>\n' +
                    '                                    <td style="text-align: center">Base Direction</td>\n' +
                    '                                </tr>\n' +
                    '                                <tr>\n' +
                    '                                    <td style="text-align: center">Right(OD)</td>\n' +
                    '                                    <td><input class="form-control" type="text" name="row[lens][od_pv][]" value=""></td>\n' +
                    '                                    <td><input class="form-control" type="text" name="row[lens][od_bd][]" value=""></td>\n' +
                    '                                    <td><input class="form-control" type="text" name="row[lens][od_pv_r][]" value=""></td>\n' +
                    '                                    <td><input class="form-control" type="text" name="row[lens][od_bd_r][]" value=""></td>\n' +
                    '                                </tr>\n' +
                    '                                <tr>\n' +
                    '                                    <td style="text-align: center">Left(OS)</td>\n' +
                    '                                    <td><input class="form-control" name="row[lens][os_pv][]" type="text" value=""></td>\n' +
                    '                                    <td><input class="form-control" name="row[lens][os_bd][]" type="text" value=""></td>\n' +
                    '                                    <td><input class="form-control" name="row[lens][os_pv_r][]" type="text" value=""></td>\n' +
                    '                                    <td><input class="form-control" name="row[lens][os_bd_r][]" type="text" value=""></td>\n' +
                    '                                </tr>\n' +
                    '                                </tbody>\n' +
                    '                            </table>\n' +
                    '                        </div>\n' +
                    '                    </div>\n' +
                    '\n' +
                    '                    <div class="form-group-child4_del" style="margin-bottom: 20px;">\n' +
                    '                        <a href="javascript:;" style="width: 50%;" class="btn btn-danger btn-del-supplement" title="删除"><i class="fa fa-trash"></i>删除</a>\n' +
                    '                    </div>\n' +
                    '                </div>';
                $('#btn-add-supplement').after(contents);
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

            $('#platform_order').click(function(){
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