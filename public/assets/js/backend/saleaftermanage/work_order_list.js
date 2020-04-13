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
                        { field: 'work_platform', title: __('work_platform'), custom: { 1: 'blue', 2: 'danger', 3: 'orange' }, searchList: { 1: 'Zeelool', 2: 'Voogueme', 3: 'Nihao' }, formatter: Table.api.formatter.status },

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
                //读取是谁添加的配置console.log(Config.work_type);
                $('.step_type').attr('checked', false);
                $('.step_type').parent().hide();
                $('#appoint_group_users').html('');//切换问题类型时清空承接人
                $('#recept_person_id').val('');//切换问题类型时清空隐藏域承接人id
                $('#recept_person').val('');//切换问题类型时清空隐藏域承接人
                $('.measure').hide();            
                $('#recept_group_id').val('');    
                if(2 == Config.work_type){ //如果是仓库人员添加的工单
                    $('#step_id').hide();
                    $('#recept_person_group').hide();
                    $('#after_user_group').show();                    
                    $('#after_user_id').val(Config.workorder.copy_group);
                    $('#after_user').html(Config.users[Config.workorder.copy_group]);
                }else{ //如果是客服人员添加的工单

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
                }
            })

            //根据措施类型显示隐藏
            $(document).on('click', '.step_type', function () {
                var incrementId = $('#c-platform_order').val();
                if (!incrementId) {
                    Toastr.error('订单号不能为空');
                    return false;
                }else{
                    // 士卫
                    $('.measure').hide();
                    var problem_type_id = $("input[name='row[problem_type_id]']:checked").val();
                    var checkID = [];//定义一个空数组
                    var appoint_group = '';
                    $("input[name='row[measure_choose_id][]']:checked").each(function (i) {
                        checkID[i] = $(this).val();
                        var id = $(this).val();
                        //获取承接组
                        appoint_group += $('#step' + id + '-appoint_group').val() + ',';
                    });
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

                    //获取是否需要审核
                    if ($('#step' + id + '-is_check').val() > 0) {
                        $('.check-div').show();
                    }

                    var arr = array_filter(appoint_group.split(','));
                    var username = [];
                    var appoint_users = [];
                    //循环根据承接组Key获取对应承接人id
                    for (var i = 0; i < arr.length - 1; i++) {
                        //循环根据承接组Key获取对应承接人id
                        appoint_users.push(Config.workorder[arr[i]]);
                        //循环根据承接人id获取对应人名称
                        for (var j = 0; j < appoint_users.length; j++) {
                            username.push(Config.users[appoint_users[j]]);
                        }
                    }
                    var users = array_filter(username);
                    $('#appoint_group_users').html(users.join(','));
                    $('#recept_person_id').val(appoint_users.join(','));
                    $('#recept_person').val(users.join(','));
                }
                var users = array_filter(username);
                $('#appoint_group_users').html(users.join(','));
                $('#recept_person_id').val(appoint_users.join(','));
                $('#recept_person').val(users.join(','));
                $('#recept_group_id').val(arr.join(','));
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


            //删除一行镜架数据
            $(document).on('click', '.btn-del', function () {
                $(this).parent().parent().remove();
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
            $(document).on('click', '.btn-add-box', function () {
                var option = $('#add_box_option').html();
                var str = '<div><label style="margin-top: 10px;" class="control-label col-xs-12 col-sm-2">SKU：</label>\n' +
                    '                    <div style="margin-top: 10px;" class="col-xs-12 col-sm-8">\n' +
                    '                        <div class="dropup">\n' +
                    '                            <select class="selectpicker" name="row[order_change][change_sku][]" data-live-search="true" title="请选择">\n' + option +
                    '                            </select>\n' +
                    '                        </div>\n' +
                    '                    </div>\n' +
                    '                    <label style="margin-top: 10px;" class="control-label col-xs-12 col-sm-2">数量：</label>\n' +
                    '                    <div style="margin-top: 10px;" class="col-xs-12 col-sm-8">\n' +
                    '                        <input class="form-control" name="row[order_change][change_number][]" type="text" value="">\n' +
                    '                        <a href="javascript:;" class="btn btn-danger btn-del-box" title="删除"><i class="fa fa-trash"></i>删除</a>\n' +
                    '                    </div></div>';
                $('#add_box').append(str);
                Controller.api.bindevent();
            });
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
                    var shtml = '<option value="">请选择</option>';
                    for (var i in data) {
                        shtml += '<option value="' + data[i] + '">' + data[i] + '</option>'
                    }
                    $('#c-order_sku').append(shtml);
                    $('.selectpicker ').selectpicker('refresh');
                });
            })
            //补发点击填充数据
            var lens_click_data
            $(document).on('click','input[name="row[measure_' +
                'choose_id]"]',function(){
                var value = $(this).val();
                var check = $(this).prop('checked');
                //补发
                if(value == 7 && check === true){
                    var increment_id = $('#c-platform_order').val();
                    if(increment_id){

                        var site_type = $('#work_platform').val();
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
                                if(json.code == 0){
                                    Toastr.error(json.msg);
                                    return false;
                                }
                                var data = json.data.address;
                                var lens = json.data.lens;
                                var prescriptions = data.prescriptions;
                                $('#supplement-order').html(lens.html);
                                //修改地址
                                var address = '';
                                for(var i = 0;i<data.address.length;i++){
                                    if(i == 0){
                                        address += '<option value="'+i+'" selected>'+data.address[i].address_type+'</option>';
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
                                    }else{
                                        address += '<option value="'+i+'">'+data.address[i].address_type+'</option>';
                                    }

                                }
                                $('#address_select').html(address);
                                //选择地址切换地址
                                $('#address_select').change(function(){
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
                                lens_click_data = '<div class="margin-top:10px;">'+ lens.html+'<div class="form-group-child4_del"><a href="javascript:;" style="width: 50%;" class="btn btn-danger btn-del-lens" title="删除"><i class="fa fa-trash"></i>删除</a></div></div>';


                                //处方选择填充
                                $(document).on('change','#prescription_select',function(){
                                    var val = $(this).val();
                                    var prescription = prescriptions[val];
                                    var prescription_div = $(this).parents('.step7_function2').next('.step1_function3');
                                    prescription_div.find('input[name="row[replacement][od_sph][]"]').val(prescription.od_sph);
                                    prescription_div.find('input[name="row[replacement][os_sph][]"]').val(prescription.os_sph);
                                    prescription_div.find('input[name="row[replacement][os_cyl][]"]').val(prescription.os_cyl);
                                    prescription_div.find('input[name="row[replacement][od_cyl][]"]').val(prescription.od_cyl);
                                    prescription_div.find('input[name="row[replacement][od_axis][]"]').val(prescription.od_axis);
                                    prescription_div.find('input[name="row[replacement][os_axis][]"]').val(prescription.os_axis);

                                    //$(this).parents('.step7_function2').val('')
                                    $(this).parents('.step7_function2').find('select[name="row[replacement][recipe_type][]"]').val(prescription.prescription_type);
                                    prescription_div.find('select[name="row[replacement][coating_type][]"]').val(prescription.coating_id);
                                    prescription_div.find('select[name="row[replacement][lens_type][]"]').val(prescription.index_id);

                                    //add，pd添加
                                    if(prescription.hasOwnProperty("total_add")){
                                        prescription_div.find('input[name="row[replacement][od_add][]"]').val(prescription.total_add);
                                        //prescription_div.find('input[name="row[replacement][os_add][]"]').attr('disabled',true);
                                    }else{
                                        prescription_div.find('input[name="row[replacement][od_add][]"]').val(prescription.od_add);
                                        prescription_div.find('input[name="row[replacement][os_add][]"]').val(prescription.os_add);
                                    }
                                    if(prescription.hasOwnProperty("pd")){
                                        prescription_div.find('input[name="row[replacement][pd_r][]"]').val(prescription.pd_r);
                                        //prescription_div.find('input[name="row[replacement][pd_l][]"]').attr('disabled',true);
                                    }else{
                                        prescription_div.find('input[name="row[replacement][pd_r][]"]').val(prescription.pd_r);
                                        prescription_div.find('input[name="row[replacement][pd_l][]"]').val(prescription.pd_l);
                                    }
                                    //
                                    if(prescription.hasOwnProperty("od_pv")){
                                        prescription_div.find('input[name="row[replacement][od_pv][]"]').val(prescription.od_pv);
                                    }
                                    if(prescription.hasOwnProperty("od_bd")){
                                        prescription_div.find('input[name="row[replacement][od_bd][]"]').val(prescription.od_bd);
                                    }
                                    if(prescription.hasOwnProperty("od_pv_r")){
                                        prescription_div.find('input[name="row[replacement][od_pv_r][]"]').val(prescription.od_pv_r);
                                    }
                                    if(prescription.hasOwnProperty("od_bd_r")){
                                        prescription_div.find('input[name="row[replacement][od_bd_r][]"]').val(prescription.od_bd_r);
                                    }
                                    if(prescription.hasOwnProperty("os_pv")){
                                        prescription_div.find('input[name="row[replacement][os_pv][]"]').val(prescription.os_pv);
                                    }
                                    if(prescription.hasOwnProperty("os_bd")){
                                        prescription_div.find('input[name="row[replacement][os_bd][]"]').val(prescription.os_bd);
                                    }
                                    if(prescription.hasOwnProperty("os_pv_r")){
                                        prescription_div.find('input[name="row[replacement][os_pv_r][]"]').val(prescription.os_pv_r);
                                    }
                                    if(prescription.hasOwnProperty("od_pv")){
                                        prescription_div.find('input[name="row[replacement][os_bd_r][]"]').val(prescription.os_bd_r);
                                    }
                                    $('.selectpicker ').selectpicker('refresh');
                                })


                                $('.selectpicker ').selectpicker('refresh');
                                Controller.api.bindevent();
                            }
                        });
                        //获取
                    }else{
                        Toastr.error('请选选择订单号……');
                    }
                }
            });

            $(document).on('click','.btn-add-supplement-reissue',function(){
                $('#supplement-order').after(lens_click_data);
                $('.selectpicker ').selectpicker('refresh');
                Controller.api.bindevent();
            });

            //省市二级联动
            $(document).on('change','#c-country',function(){
                var id = $(this).val();

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
                        for(var i = 0;i<data.length;i++){
                            province += '<option value="'+data[i].region_id+'">'+data[i].default_name+'</option>';
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
        //处理任务
        process:function(){
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
            }
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