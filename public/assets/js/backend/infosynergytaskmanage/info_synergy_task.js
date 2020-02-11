define(['jquery', 'bootstrap', 'backend', 'table', 'form','bootstrap-table-jump-to'], function ($, undefined, Backend, Table, Form) {

    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                showJumpto: true,
                searchFormVisible: true,
                pageList: [10, 25, 50, 100],
                extend: {
                    index_url: 'infosynergytaskmanage/info_synergy_task/index' + location.search + '/synergy_order_number/' + Config.synergy_order_number,
                    add_url: 'infosynergytaskmanage/info_synergy_task/add',
                    edit_url: 'infosynergytaskmanage/info_synergy_task/edit',
                    //del_url: 'infosynergytaskmanage/info_synergy_task/del',
                    multi_url: 'infosynergytaskmanage/info_synergy_task/multi',
                    table: 'info_synergy_task',
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
                        {checkbox: true},
                        {field: '', title: __('序号'), formatter: function (value, row, index) {
                            var options = table.bootstrapTable('getOptions');
                            var pageNumber = options.pageNumber;
                            var pageSize = options.pageSize;

                            //return (pageNumber - 1) * pageSize + 1 + index;
                            return 1+index;
                            }, operate: false
                        },
                        {field: 'id', title: __('Id'),operate:false},
                        {field: 'synergy_number', title: __('Synergy_number')},
                        {field: 'synergy_order_id', title: __('Synergy_order_id'),searchList:$.getJSON('infosynergytaskmanage/info_synergy_task/getOrderType'),formatter:Controller.api.formatter.synergyOrderId},
                        // {field: 'synergy_order_id', title: __('哈哈'),searchList:{"1":"haha","2":"呵呵","3":"嘻嘻"},formatter: Table.api.formatter.status},
                        {field: 'synergy_order_number', title: __('Synergy_order_number')},
						{field:'order_skus',title:__('订单SKU')},
                        {field: 'order_platform', title: __('Order_platform'),searchList:$.getJSON('saleaftermanage/sale_after_task/getAjaxOrderPlatformList'),formatter: Controller.api.formatter.orderDevice},
                        {
                            field: 'synergy_status',
                            title:__('Synergy_status'),
                            searchList: { 0: '新建', 1: '处理中', 2: '处理完成',3:'取消'},
                            custom: { 0: 'blue', 1: 'yellow', 2: 'success',3:'red'},
                            formatter: Table.api.formatter.status
                        },
                        { field: 'is_refund',
                        title:__('Is_refund'),
                        searchList:{1:'无',2:'有'},
                        custom: { 1: 'blue', 2: 'red'},
                        formatter: Table.api.formatter.status
                        },
                        {
                          field:'refund_money',
                          title:__('Refund_money')  
                        },
                        {field: 'dept', title: __('Dept_id')},
                        {field: 'rep', title: __('Rep_id')},
                        {field: 'prty_id', title: __('Prty_id'),searchList: {0:'未选择',1:'高级',2:'中级',3:'低级'},formatter: Controller.api.formatter.prtyDevice,operate:false},
                        {field: 'synergy_task_id', title: __('Synergy_task_id'),visible:false,operate:false},
                        {field: 'infosynergytaskcategory.name', title: __('Synergy_task_id'),operate:'like'},
                        {field: 'problem_desc', title: __('problem_desc'),formatter:Controller.api.formatter.getClear,operate:false},
                        {field: 'create_person', title: __('Create_person')},
                        {field: 'create_time', title: __('Create_time'), operate:'RANGE', addclass:'datetimerange'},
                        //{field: 'operate', title: __('Operate'), table: table, events: Table.api.events.operate, formatter: Table.api.formatter.operate}
                        {field: 'operate', width: "120px", title: __('操作'), table: table,events: Table.api.events.operate,formatter: Table.api.formatter.operate,
                            buttons: [
                                {
                                    name: 'detail',
                                    text: '详情',
                                    title: __('查看详情'),
                                    extend: 'data-area = \'["100%","100%"]\'',
                                    classname: 'btn btn-xs btn-primary btn-dialog',
                                    icon: 'fa fa-list',
                                    url: 'infosynergytaskmanage/info_synergy_task/detail',
                                    callback: function (data) {
                                        Layer.alert("接收到回传数据：" + JSON.stringify(data), {title: "回传数据"});
                                    },
                                    visible: function (row) {
                                        //返回true时按钮显示,返回false隐藏
                                        return true;
                                    }
                                },
                                {
                                    name: 'edit',
                                    text: '编辑',
                                    title: __('编辑'),
                                    classname: 'btn btn-xs btn-success btn-dialog',
                                    icon: 'fa fa-pencil',
                                    url:  'infosynergytaskmanage/info_synergy_task/edit',
                                    extend: 'data-area = \'["100%","100%"]\'',
                                    callback: function (data) {
                                        Layer.alert("接收到回传数据：" + JSON.stringify(data), { title: "回传数据" });
                                    },
                                    visible: function (row) {
                                        if(row.synergy_status == 0){
                                            return true;
                                        }
                                            return false;
                                    }
                                },
                                {
                                    name: 'closed',
                                    text: '取消',
                                    title: __('Closed'),
                                    classname: 'btn btn-xs btn-danger btn-ajax',
                                    icon: 'fa fa-remove',
                                    confirm: '确定要取消吗',
                                    url: 'infosynergytaskmanage/info_synergy_task/closed',
                                    success: function (data, ret) {
                                        Layer.alert(ret.msg);
                                        $(".btn-refresh").trigger("click");
                                        //如果需要阻止成功提示，则必须使用return false;
                                        //return false;
                                    },
                                    error: function (data, ret) {
                                        Layer.alert(ret.msg);
                                        return false;
                                    },
                                    visible: function (row) {
                                        //返回true时按钮显示,返回false隐藏
                                        if(row.synergy_status == 0){
                                            return true;
                                        }
                                            return false;
                                            
                                    }

                                },
								{
                                    name: 'remark',
                                    text: '添加备注',
                                    title: __('添加备注'),
                                    classname: 'btn btn-xs btn-success btn-dialog',
                                    icon: 'fa fa-pencil',
                                    url:  'infosynergytaskmanage/info_synergy_task/add_remark',
                                    extend: 'data-area = \'["100%","100%"]\'',
                                    callback: function (data) {
                                        Layer.alert("接收到回传数据：" + JSON.stringify(data), { title: "回传数据" });
                                    },
                                    visible: function (row) {
                                        if(row.synergy_status == 2){
                                            return true;
                                        }
                                            return false;
                                    }
                                }
                                // {
                                //     name: 'handleComplete',
                                //     text: '处理完成',
                                //     title: __('处理完成'),
                                //     classname: 'btn btn-xs btn-success btn-ajax',
                                //     icon: 'fa fa-pencil',
                                //     url: Config.moduleurl + '/infosynergytaskmanage/info_synergy_task/handleComplete',
                                //     confirm: '确认要处理完成吗',
                                //     success: function (data, ret) {
                                //         Layer.alert(ret.msg);
                                //         $(".btn-refresh").trigger("click");
                                //         //如果需要阻止成功提示，则必须使用return false;
                                //         //return false;
                                //     },
                                //     error: function (data, ret) {
                                //         Layer.alert(ret.msg);
                                //         return false;
                                //     },
                                //     visible: function (row) {
                                //         //返回true时按钮显示,返回false隐藏
                                //         if (row.synergy_status != 2) {
                                //             return true;
                                //         } else {
                                //             return false;
                                //         }
                                //     }
                                // },
                            ]
                        },
                    ]
                ]
            });
            $('.panel-heading a[data-toggle="tab"]').on('shown.bs.tab', function (e) {
                var field = $(this).data("field");
                var value = $(this).data("value");
                var options = table.bootstrapTable('getOptions');
                options.pageNumber = 1;
                var queryParams = options.queryParams;
                options.queryParams = function (params) {
                    var params = queryParams(params);
                    var filter = params.filter ? JSON.parse(params.filter) : {};
                    var op     = {};
                     if(field == 'create_person'){
                        delete filter.rep_id;
                        filter[field] = value;
                    }else if(field == 'rep_id'){
                        delete filter.create_person;
                        filter[field] = value;
                        op[field] = 'LIKE';
                    }else{
                        //delete op.rep_id;
                        delete filter.rep_id;
                        delete filter.create_person;
                    }
                    params.filter = JSON.stringify(filter);
                    params.op     = JSON.stringify(op);
                    return params;
                };
                table.bootstrapTable('refresh', {});
                return false;
            });
            //为表格绑定事件
            Table.api.bindevent(table);
            $(document).on('click',".problem_desc_info",function(){
                var problem_desc = $(this).attr('name');
                //Layer.alert(problem_desc);
                Layer.open({
                    closeBtn: 1,
                    title: '问题描述',
                    area: ['900px', '500px'],
                    content:problem_desc
                });
                return false;
            });
            //处理任务
            $(document).on('click','.btn-handle',function(){
                var ids = Table.api.selectedids(table);
                Backend.api.open('infosynergytaskmanage/info_synergy_task/handle_task/ids/'+ids,'处理任务',{area:["100%", "100%"]});
                //window.location.href = 'saleaftermanage/sale_after_task/handle_task/ids/'+ids;
            });
            $(document).on('click', '.btn-handle-complete', function () {
                var ids = Table.api.selectedids(table);
                Layer.confirm(
                    __('确定要处理完成吗'),
                    function (index) {
                        Backend.api.ajax({
                            url: "infosynergytaskmanage/info_synergy_task/handleComplete",
                            data: { ids: ids }
                        }, function (data, ret) {
                            table.bootstrapTable('refresh');
                            Layer.close(index);
                        });
                    }
                );
            });
                        //批量导出xls 
            $('.btn-batch-export-xls').click(function () {
                var ids = Table.api.selectedids(table);
                if (ids.length > 0) {
                    window.open(Config.moduleurl + '/infosynergytaskmanage/info_synergy_task/batch_export_xls?ids=' + ids, '_blank');
                } else {
                    var options = table.bootstrapTable('getOptions');
                    var search = options.queryParams({});
                    var filter = search.filter;
                    var op = search.op;
                    window.open(Config.moduleurl + '/infosynergytaskmanage/info_synergy_task/batch_export_xls?filter=' + filter + '&op=' + op, '_blank');
                }
                
            });
        },
        add: function () {
            Controller.api.bindevent();
        },
        edit: function () {
            Controller.api.bindevent();

        },
        api: {
            bindevent: function () {
                Form.api.bindevent($("form[role=form]"));
                //判断输入的sku的数量和sku编码是否符合需求
                $(document).on('change','.change_sku',function(){
                    var change_sku = $(this).val();
                    var change_number    = $(this).parent().next().children().val();
                    var order_platform = $('#c-order_platform').val();
                    if(order_platform<1){
                        Layer.alert('请选择订单所在平台');
                        return false;
                    }
                    if(change_sku == ''){
                        Layer.alert('请填写更改镜架的sku');
                        return false;
                    }
                    if(change_number<1){
                        Layer.alert('更改的数量不能小于1');
                        return false;
                    }
                    Backend.api.ajax({
                        url:'itemmanage/item_platform_sku/checkPlatformSkuAndQty',
                        data:{change_sku:change_sku,change_number:change_number,order_platform:order_platform}
                    }, function(data, ret){
                        $('.btn-success').removeClass('btn-disabled disabled');
                        return false;
                    }, function(data, ret){
                        //失败的回调
                        Layer.alert(ret.msg);
                        $('.btn-success').addClass('btn-disabled disabled');
                        return false;
                    });
                });
                //判断输入的sku的数量和sku编码是否符合需求
                $(document).on('change','.change_number',function(){
                    var change_number = $(this).val();
                    var change_sku    = $(this).parent().prev().children().val();
                    var order_platform = $('#c-order_platform').val();
                    if(order_platform<1){
                        Layer.alert('请选择订单所在平台');
                        return false;
                    }
                    if(change_sku == ''){
                        Layer.alert('请填写更改镜架的sku');
                        return false;
                    }
                    if(change_number<1){
                        Layer.alert('更改的数量不能小于1');
                        return false;
                    }
                    Backend.api.ajax({
                        url:'itemmanage/item_platform_sku/checkPlatformSkuAndQty',
                        data:{change_sku:change_sku,change_number:change_number,order_platform:order_platform}
                    }, function(data, ret){
                        // console.log(ret.data);
                        $('.btn-success').removeClass('btn-disabled disabled');
                        return false;
                    }, function(data, ret){
                        //失败的回调
                        Layer.alert(ret.msg);
                        $('.btn-success').addClass('btn-disabled disabled');
                        return false;
                    });
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
                    var contents = '<div class="col-lg-12">' +
                        '</div>' +
                        '<div class="col-xs-6 col-md-4" style="margin-left:5.6666%;" >' +
                        '<div class="panel bg-blue">' +
                        '<div class="panel-body">' +
                        '<div class="panel-title">' +
                        '<label class="control-label col-xs-12 col-sm-3">name:</label>' +
                        '<div class="col-xs-12 col-sm-8">' +
                        '<input  id="c-item_name"  class="form-control" name="row[lens][original_name][]" type="text" value="">' +
                        '</div>' +
                        '</div>' +
                        '</div>' +
                        '<div class="panel-body">' +
                        '<div class="panel-title">' +
                        '<label class="control-label col-xs-12 col-sm-3">SKU:</label>' +
                        '<div class="col-xs-12 col-sm-8">' +
                        '<input  id="c-item_sku" class="form-control" name="row[lens][original_sku][]"  type="text" value="">' +
                        '</div>' +
                        '</div>' +
                        '</div>' +
                        '<div class="panel-body">' +
                        '<div class="panel-title">' +
                        '<label class="control-label col-xs-12 col-sm-3">qty_ordered:</label>' +
                        '<div class="col-xs-12 col-sm-8">' +
                        '<input  id="c-item_qty_ordered"  class="form-control" name="row[lens][original_number][]"  type="text" value="">' +
                        '</div>' +
                        '</div>' +
                        '</div>' +
                        '<div class="panel-body">' +
                        '<div class="panel-title">' +
                        '<label class="control-label col-xs-12 col-sm-3">prescription_type:</label>' +
                        '<div class="col-xs-12 col-sm-8">' +
                        '<input  id="c-recipe_type"  class="form-control" type="text" name="row[lens][recipe_type][]" value="">' +
                        '</div>' +
                        '</div>' +
                        '</div>' +
                        '<div class="panel-body">' +
                        '<div class="panel-title">' +
                        '<label class="control-label col-xs-12 col-sm-3">lens_type:</label>' +
                        '<div class="col-xs-12 col-sm-8">' +
                        '<input  id="c-lens_type"  class="form-control" name="row[lens][lens_type][]" type="text" value="">' +
                        '</div>' +
                        '</div>' +
                        '</div>' +
                        '<div class="panel-body">' +
                        '<div class="panel-title">' +
                        '<label class="control-label col-xs-12 col-sm-3">coating_type:</label>' +
                        '<div class="col-xs-12 col-sm-8">' +
                        '<input  id="c-coating_film_type"  class="form-control" name="row[lens][coating_type][]"  type="text" value="">' +
                        '</div>' +
                        '</div>' +
                        '</div>' +
                        '</div>' +
                        '</div>' +
                        '<div class="col-xs-6 col-md-7">' +
                        '<div class="panel bg-aqua-gradient">' +
                        '<div class="panel-body">' +
                        '<div class="ibox-title">' +
                        '<table id="caigou-table-lens">' +
                        '<tr>' +
                        '<td colspan="10" style="text-align: center">Prescription_type</td>' +
                        '</tr>' +
                        '<tr>' +
                        '<td style="text-align: center">value</td>' +
                        '<td style="text-align: center">SPH</td>' +
                        '<td style="text-align: center">CYL</td>' +
                        '<td style="text-align: center">AXI</td>' +
                        '<td style="text-align: center">ADD</td>' +
                        '<td style="text-align: center">PD</td>' +
                        '<td style="text-align: center">Prism Horizontal</td>' +
                        '<td style="text-align: center">Base Direction</td>' +
                        '<td style="text-align: center">Prism Vertical</td>' +
                        '<td style="text-align: center">Base Direction</td>' +
                        '</tr>' +
                        '<tr>' +
                        '<td style="text-align: center">Right(OD)</td>' +
                        '<td><input id="c-right_SPH" class="form-control" name="row[lens][od_sph][]"  type="text" value=""></td>' +
                        '<td><input id="c-right_CYL" class="form-control" name="row[lens][od_cyl][]"  type="text" value=""></td>' +
                        '<td><input id="c-right_AXI" class="form-control" name="row[lens][od_axis][]" type="text" value=""></td>' +
                        '<td><input id="c-right_ADD" class="form-control" name="row[lens][od_add][]"  type="text" value=""></td>' +
                        '<td><input id="c-right_PD" class="form-control"  name="row[lens][pd_r][]"    type="text" value=""></td>' +
                        '<td><input id="c-right_Prism_Horizontal" class="form-control" name="row[lens][od_pv][]"  type="text" value=""></td>' +
                        '<td><input id="c-right_bd" class="form-control"  type="text" name="row[lens][od_bd][]"  value=""></td>' +
                        '<td><input id="c-purchase_remark" class="form-control"  type="text" name="row[lens][od_pv_r][]" value=""></td>' +
                        '<td><input id="c-purchase_remark" class="form-control"  type="text" name="row[lens][od_bd_r][]" value=""></td>' +
                        '</tr>' +
                        '<tr>' +
                        '<td style="text-align: center">Left(OS)</td>' +
                        '<td><input id="c-left_SPH" class="form-control" name="row[lens][os_sph][]" type="text" value=""></td>' +
                        '<td><input id="c-left_CYL" class="form-control" name="row[lens][os_cyl][]" type="text" value=""></td>' +
                        '<td><input id="c-purchase_remark" class="form-control" name="row[lens][os_axis][]"  type="text" value=""></td>' +
                        '<td><input id="c-purchase_remark" class="form-control" name="row[lens][os_add][]"   type="text" value=""></td>' +
                        '<td><input id="c-purchase_remark" class="form-control" name="row[lens][pd_l][]"    type="text" value=""></td>' +
                        '<td><input id="c-purchase_remark" class="form-control" name="row[lens][os_pv][]"   type="text" value=""></td>' +
                        '<td><input id="c-purchase_remark" class="form-control" name="row[lens][os_bd][]" type="text" value=""></td>' +
                        '<td><input id="c-purchase_remark" class="form-control" name="row[lens][os_pv_r][]" type="text" value=""></td>' +
                        '<td><input id="c-purchase_remark" class="form-control" name="row[lens][os_bd_r][]" type="text" value=""></td>' +
                        '</tr>' +
                        '</table>' +
                        '</div>' +
                        '</div>' +
                        '</div>' +
                        '<div>'+
                        '<a href="javascript:;" class="btn btn-danger btn-del-lens" title="删除"><i class="fa fa-trash"></i>删除</a>'+
                        '</div>'+
                        '</div>';
                     $('.item_info').append(contents);

                });
                //删除一行镜片数据
                $(document).on('click', '.btn-del-lens', function () {
                    $(this).parent().parent().prev().remove();
                    $(this).parent().parent().remove();

                });
                //承接部门和承接人二级联动
                $(document).on('change','#choose_dept_id',function(){
                    var arrIds = $(this).val();
                    if(arrIds == null){
                        Layer.alert('请选择承接部门');
                        return false;
                    }
                    var arrStr = arrIds.join("&");
                    //根据承接部门查找出承接人
                    Backend.api.ajax({
                        url:'infosynergytaskmanage/info_synergy_task/ajaxFindRecipient',
                        data:{arrIds:arrStr}
                    }, function(data, ret){
                        // console.log(ret.data);
                        var rs = ret.data;
                        var x;
                        $("#choose_rep_id").html('');
                        var str = '';
                        for( x in rs ){
                            str +='<option value="'+x+'">' + rs[x]+'</option>';
                        }
                        $("#choose_rep_id").append(str);
                        $("#choose_rep_id").selectpicker('refresh');
                        return false;
                    }, function(data, ret){
                        //失败的回调
                        alert(ret.msg);
                        console.log(ret);
                        return false;
                    });
                    //console.log($(this).val());
                });
                //选中镜片和镜架事件
                $("input[name='row[synergy_task_id]']").click(function(){
                     var vals = $(this).val();
                    var orderPlatform = $('#c-order_platform').val();
                    var orderNumber   = $('#c-synergy_order_number').val();
                    var synergyOrderId = $('#c-synergy_order_id').val();
                     if( (vals == 12)){ //更改镜架                        
                         if( synergyOrderId == 2){
                             Backend.api.ajax({
                                 url:'saleaftermanage/sale_after_task/ajax',
                                 data:{ordertype:orderPlatform,order_number:orderNumber}
                             }, function(data, ret){
                                $(".recipe-info").remove();
                                $(".item_info").empty();
                                $("#c-change_type").val(1);
                                 var item = ret.data;
                                 $('#customer_info').after(function(){
                                     var Str = '';
                                     Str+=  '<div class="caigou frame-info item_info" style="margin-top:15px;margin-left:10%;">'+
                                         '<p style="font-size: 16px;"><b>更改镜架</b></p>'+
                                         '<div>'+
                                         '<div id="toolbar" class="toolbar">'+
                                         '<a href="javascript:;" class="btn btn-success btn-add" title="增加"><i class="fa fa-plus"></i> 增加</a>'+
                                         '</div>'+
                                         '<table id="caigou-table-sku">'+
                                         '<tr>'+
                                         '<th>原SKU</th>'+
                                         '<th>原数量(+增加)</th>'+
                                         '<th>新SKU/th>'+
                                         '<th>新数量(-减少)</th>'+
                                         '<th>操作</th>'+
                                         '</tr>';
                                     for(var j = 0,len = item.length; j <len; j++) {
                                         var newItem = item[j];
                                         var m = j+1;
                                         Str +='<tr>';
                                         Str +='<td><input id="c-original_sku" class="form-control" name="row[item]['+m+'][original_sku]" type="text" value="'+newItem.sku+'"></td>';
                                         Str +='<td><input id="c-original_number" class="form-control" name="row[item]['+m+'][original_number]" type="text" value="'+Math.round(newItem.qty_ordered)+'"></td>';
                                         Str +='<td><input id="c-change_sku" class="form-control change_sku" name="row[item]['+m+'][change_sku]" type="text"></td>';
                                         Str +='<td><input id="c-change_number" class="form-control change_number" name="row[item]['+m+'][change_number]" type="text"></td>';
                                         Str +='<td><a href="javascript:;" class="btn btn-danger btn-del" title="删除"><i class="fa fa-trash"></i>删除</a></td>';
                                         Str += '</tr>';
                                     }
                                     Str+='</table>'+
                                         '</div>'+
                                         '</div>';
                                     return Str;
                                 });
                              //    var item = ret.data.item;
                              //    for(var j = 0,len = item.length; j < len; j++){
                              //        //var newItem = item[j];
                              //
                              //        $('#customer_info').after(function(){
                              //
                              //        }
                              //    return false;
                              // }
                             }, function(data, ret){
                                 //失败的回调
                                 alert(ret.msg);
                                 console.log(ret);
                                 return false;
                             });
                         }
                     }else if(vals == 13){ //修改处方参数                     
                         if( synergyOrderId == 2){
                             Backend.api.ajax({
                                 url:'saleaftermanage/sale_after_task/ajax',
                                 data:{ordertype:orderPlatform,order_number:orderNumber}
                             }, function(data, ret){
                                $(".frame-info").remove();
                                 $(".item_info").empty();
                                 $("#c-change_type").val(2);
                                    var item = ret.data;
                                     $('#customer_info').after(function(){
                                         var str2 = '';
                                          str2+= '<div class="row recipe-info item_info" style="margin-top:15px;margin-left:7.6666%;">'+
                                                 '<p style="font-size: 16px;"><b>更改镜片</b></p>'+
                                                 '<div>'+
                                                 '<div id="toolbar" class="toolbar">'+
                                                 '<a href="javascript:;" class="btn btn-success btn-add-lens" title="增加"><i class="fa fa-plus"></i> 增加</a>'+
                                                 '</div>';
                                         for(var j = 0,len = item.length; j < len; j++) {
                                             var newItem = item[j];
                                             str2 += '<div class="col-lg-12">' +
                                                 '</div>' +
                                                 '<div class="col-xs-6 col-md-4" style="margin-left:5.6666%;" >' +
                                                 '<div class="panel bg-blue">' +
                                                 '<div class="panel-body">' +
                                                 '<div class="panel-title">' +
                                                 '<label class="control-label col-xs-12 col-sm-3">商品名称:</label>' +
                                                 '<div class="col-xs-12 col-sm-8">' +
                                                 '<input  id="c-item_name"  class="form-control"  type="text" name="row[lens][original_name][]" value="' + newItem.name + '">' +
                                                 '</div>' +
                                                 '</div>' +
                                                 '</div>' +
                                                 '<div class="panel-body">' +
                                                 '<div class="panel-title">' +
                                                 '<label class="control-label col-xs-12 col-sm-3">SKU:</label>' +
                                                 '<div class="col-xs-12 col-sm-8">' +
                                                 '<input  id="c-item_sku" class="form-control"  type="text" name="row[lens][original_sku][]" value="' + newItem.sku + '">' +
                                                 '</div>' +
                                                 '</div>' +
                                                 '</div>' +
                                                 '<div class="panel-body">' +
                                                 '<div class="panel-title">' +
                                                 '<label class="control-label col-xs-12 col-sm-3">数量:</label>' +
                                                 '<div class="col-xs-12 col-sm-8">' +
                                                 '<input  id="c-item_qty_ordered"  class="form-control"  type="text" name="row[lens][original_number][]" value="' + Math.round(newItem.qty_ordered) + '">' +
                                                 '</div>' +
                                                 '</div>' +
                                                 '</div>' +
                                                 '<div class="panel-body">' +
                                                 '<div class="panel-title">' +
                                                 '<label class="control-label col-xs-12 col-sm-3">处方类型:</label>' +
                                                 '<div class="col-xs-12 col-sm-8">' +
                                                 '<input  id="c-recipe_type"  class="form-control" type="text" name="row[lens][recipe_type][]" value="' + (newItem.prescription_type !=undefined ? newItem.prescription_type : "") + '">' +
                                                 '</div>' +
                                                 '</div>' +
                                                 '</div>';
                                                 if(orderPlatform == 3){
                                                    str2+='<div class="panel-body">' +
                                                    '<div class="panel-title">' +
                                                    '<label class="control-label col-xs-12 col-sm-3">基片类型:</label>' +
                                                    '<div class="col-xs-12 col-sm-8">' +
                                                    '<input  id="c-lens_type"  class="form-control"  type="text" name="row[lens][second_name][]" value="' + (newItem.second_name !=undefined ? newItem.second_name : "")+ '">' +
                                                    '</div>' +
                                                    '</div>' +
                                                    '</div>' +
                                                    '<div class="panel-body">' +
                                                    '<div class="panel-title">' +
                                                    '<label class="control-label col-xs-12 col-sm-3">镜片类型:</label>' +
                                                    '<div class="col-xs-12 col-sm-8">' +
                                                    '<input  id="c-lens_type"  class="form-control"  type="text" name="row[lens][lens_type][]" value="' + (newItem.third_name !=undefined ? newItem.third_name : "")+ '">' +
                                                    '</div>' +
                                                    '</div>' +
                                                    '</div>' +
                                                    '<div class="panel-body">' +
                                                    '<div class="panel-title">' +
                                                    '<label class="control-label col-xs-12 col-sm-3">折射率:</label>' +
                                                    '<div class="col-xs-12 col-sm-8">' +
                                                    '<input  id="c-lens_type"  class="form-control"  type="text" name="row[lens][zsl][]" value="' + (newItem.zsl !=undefined ? newItem.zsl : "")+ '">' +
                                                    '</div>' +
                                                    '</div>' +
                                                    '</div>' +
                                                    '<div class="panel-body">' +
                                                    '<div class="panel-title">' +
                                                    '<label class="control-label col-xs-12 col-sm-3">镀膜类型:</label>' +
                                                    '<div class="col-xs-12 col-sm-8">' +
                                                    '<input  id="c-lens_type"  class="form-control"  type="text" name="row[lens][coating_type][]" value="' + (newItem.four_name !=undefined ? newItem.four_name : "")+ '">' +
                                                    '</div>' +
                                                    '</div>' +
                                                    '</div>';
                                                 }else{
                                                   str2+='<div class="panel-body">' +
                                                    '<div class="panel-title">' +
                                                    '<label class="control-label col-xs-12 col-sm-3">镜片类型:</label>' +
                                                    '<div class="col-xs-12 col-sm-8">' +
                                                    '<input  id="c-lens_type"  class="form-control"  type="text" name="row[lens][lens_type][]" value="' + (newItem.index_type !=undefined ? newItem.index_type : "")+ '">' +
                                                    '</div>' +
                                                    '</div>' +
                                                    '</div>'+
                                                    '<div class="panel-body">' +
                                                    '<div class="panel-title">' +
                                                    '<label class="control-label col-xs-12 col-sm-3">镀膜类型:</label>' +
                                                    '<div class="col-xs-12 col-sm-8">' +
                                                    '<input  id="c-coating_film_type"  class="form-control"  type="text" name="row[lens][coating_type][]" value="' + (newItem.coatiing_name!=undefined ? newItem.coatiing_name : "") + '">' +
                                                    '</div>' +
                                                    '</div>' +
                                                    '</div>'; 
                                                 }
                                                 str2+='</div>' +
                                                 '</div>' +
                                                 '<div class="col-xs-6 col-md-7">' +
                                                 '<div class="panel bg-aqua-gradient">' +
                                                 '<div class="panel-body">' +
                                                 '<div class="ibox-title">' +
                                                 '<table id="caigou-table-lens">' +
                                                 '<tr>' +
                                                 '<td colspan="10" style="text-align: center">处方参数</td>' +
                                                 '</tr>' +
                                                 '<tr>' +
                                                 '<td style="text-align: center">参数</td>' +
                                                 '<td style="text-align: center">SPH</td>' +
                                                 '<td style="text-align: center">CYL</td>' +
                                                 '<td style="text-align: center">AXI</td>' +
                                                 '<td style="text-align: center">ADD</td>' +
                                                 '<td style="text-align: center">PD</td>' +
                                                 '<td style="text-align: center">Prism Horizontal</td>' +
                                                 '<td style="text-align: center">Base Direction</td>' +
                                                 '<td style="text-align: center">Prism Vertical</td>' +
                                                 '<td style="text-align: center">Base Direction</td>' +
                                                 '</tr>' +
                                                 '<tr>' +
                                                 '<td style="text-align: center">Right(OD)</td>' +
                                                 '<td><input id="c-right_SPH" class="form-control"  type="text" name="row[lens][od_sph][]" value="' + (newItem.od_sph  != undefined ? newItem.od_sph : "") + '"></td>' +
                                                 '<td><input id="c-right_CYL" class="form-control"  type="text" name="row[lens][od_cyl][]" value="' + (newItem.od_cyl  != undefined ? newItem.od_cyl : "") + '"></td>' +
                                                 '<td><input id="c-right_AXI" class="form-control"  type="text" name="row[lens][od_axis][]" value="'+ (newItem.od_axis != undefined ? newItem.od_axis : "") + '"></td>';
                                                 if(orderPlatform<3){
                                                    if(newItem.total_add){
                                                        str2+= '<td><input id="c-right_ADD" class="form-control"  type="text" name="row[lens][od_add][]" value="' + (newItem.total_add  != undefined ? newItem.total_add : "") + '"></td>';
                                                    }else{
                                                        str2+= '<td><input id="c-right_ADD" class="form-control"  type="text" name="row[lens][od_add][]" value="' + (newItem.os_add  != undefined ? newItem.os_add : "") + '"></td>';
                                                    }
                                                 }else{
                                                    if(newItem.prescription_type == 'Reading Glasses' && newItem.os_add>0 && newItem.od_add>0){
                                                        str2+= '<td><input id="c-right_ADD" class="form-control"  type="text" name="row[lens][od_add][]" value="' + (newItem.od_add  != undefined ? newItem.od_add : "") + '"></td>';
                                                    }else{
                                                        str2+= '<td rowspan="2"><input style="height:62px;"  id="c-right_ADD" class="form-control"  type="text" name="row[lens][od_add][]" value="' + (newItem.total_add  != undefined ? newItem.total_add : "") + '"></td>';
                                                    }
                                                 }
                                                 if(newItem.pdcheck == 'on'){
                                                     str2+= '<td><input id="c-right_PD" class="form-control"  type="text"  name="row[lens][pd_r][]" value="'   + (newItem.pd_r  != undefined ? newItem.pd_r : "") + '"></td>';
                                                 }else{
                                                    str2+= '<td rowspan="2"><input style="height:62px;" id="c-right_PD" class="form-control"  type="text"  name="row[lens][pd_r][]" value="'   + (newItem.pd  != undefined ? newItem.pd : "") + '"></td>';
                                                 }   
                                                 str2+='<td><input id="c-right_Prism_Horizontal" class="form-control" name="row[lens][od_pv][]" type="text" value="' + (newItem.od_pv != undefined ? newItem.od_pv: "") + '"></td>' +
                                                 '<td><input id="c-right_" class="form-control"  type="text" name="row[lens][od_bd][]" value="' + (newItem.od_bd != undefined ? newItem.od_bd:"")+ '"></td>' +
                                                 '<td><input id="c-purchase_remark" class="form-control"  type="text" name="row[lens][od_pv_r][]" value="' + (newItem.od_pv_r != undefined ? newItem.od_pv_r:"") + '"></td>' +
                                                 '<td><input id="c-purchase_remark" class="form-control"  type="text" name="row[lens][od_bd_r][]" value="' + (newItem.od_bd_r != undefined ? newItem.od_bd_r:"") + '"></td>' +
                                                 '</tr>' +
                                                 '<tr>' +
                                                 '<td style="text-align: center">Left(OS)</td>' +
                                                 '<td><input id="c-left_SPH" class="form-control"  type="text" name="row[lens][os_sph][]" value="' + (newItem.os_sph != undefined ? newItem.os_sph : "")+ '"></td>' +
                                                 '<td><input id="c-left_CYL" class="form-control"  type="text" name="row[lens][os_cyl][]" value="' + (newItem.os_cyl != undefined ? newItem.os_cyl :"") + '"></td>' +
                                                 '<td><input id="c-purchase_remark" class="form-control"  type="text" name="row[lens][os_axis][]" value="' + (newItem.os_axis != undefined ? newItem.os_axis :"")+ '"></td>';
                                                 if(orderPlatform<3){
                                                    if(!newItem.total_add){
                                                        str2+='<td><input id="c-purchase_remark" class="form-control"  type="text" name="row[lens][os_add][]" value="' + (newItem.od_add  != undefined ? newItem.od_add : "") + '"></td>';
                                                    }
                                                 }else{
                                                    if(newItem.prescription_type == 'Reading Glasses' && newItem.os_add>0 && newItem.od_add>0){
                                                        str2+='<td><input id="c-purchase_remark" class="form-control"  type="text" name="row[lens][os_add][]" value="' + (newItem.os_add  != undefined ? newItem.os_add : "") + '"></td>';
                                                    }
                                                 }
                                                 if(newItem.pdcheck == 'on'){
                                                        str2+='<td><input id="c-purchase_remark" class="form-control"  type="text" name="row[lens][pd_l][]" value="' + (newItem.pd_l  != undefined ? newItem.pd_l : "") + '"></td>';
                                                 }
                                                 str2+='<td><input id="c-purchase_remark" class="form-control"  type="text" name="row[lens][os_pv][]" value="' + (newItem.os_pv != undefined ? newItem.os_pv : "") + '"></td>' +
                                                 '<td><input id="c-purchase_remark" class="form-control"  type="text" name="row[lens][os_bd][]" value="' + (newItem.os_bd != undefined ? newItem.os_bd : "")+ '"></td>' +
                                                 '<td><input id="c-purchase_remark" class="form-control"  type="text" name="row[lens][os_pv_r][]" value="' + (newItem.os_pv_r!= undefined ? newItem.os_pv_r : "") + '"></td>' +
                                                 '<td><input id="c-purchase_remark" class="form-control"  type="text" name="row[lens][os_bd_r][]" value="' + (newItem.os_bd_r!= undefined ? newItem.os_bd_r : "") + '"></td>' +
                                                 '</tr>' +
                                                 '</table>' +
                                                 '</div>' +
                                                 '</div>' +
                                                 '</div>' +
                                                 '<div>'+
                                                 '<a href="javascript:;" class="btn btn-danger btn-del-lens" title="删除"><i class="fa fa-trash"></i>删除</a>'+
                                                 '</div>'+
                                                 '</div>';
                                         }
                                         str2+='</div>';
                                         return str2;
                                     });

                             }, function(data, ret){
                                 //失败的回调
                                 alert(ret.msg);
                                 console.log(ret);
                                 return false;
                             });
                         }
                    }else if((vals == 14) || (vals == 37) ||(vals == 38)){
                        if( synergyOrderId == 2){
                            Backend.api.ajax({
                                url:'saleaftermanage/sale_after_task/ajax',
                                data:{ordertype:orderPlatform,order_number:orderNumber}
                            }, function(data, ret){
                               $(".recipe-info").remove();
                               $(".item_info").empty();
                               $("#c-change_type").val(3);
                                var item = ret.data;
                                $('#customer_info').after(function(){
                                    var Str = '';
                                    Str+=  '<div class="caigou frame-info item_info" style="margin-top:15px;margin-left:10%;">'+
                                        '<p style="font-size: 16px;"><b>取消订单</b></p>'+
                                        '<div>'+
                                        '<table id="caigou-table-sku">'+
                                        '<tr>'+
                                        '<th>原始SKU</th>'+
                                        '<th>取消数量</th>'+
                                        '</tr>';
                                    for(var j = 0,len = item.length; j <len; j++) {
                                        var newItem = item[j];
                                        var m = j+1;
                                        Str +='<tr>';
                                        Str +='<td><input id="c-original_sku" class="form-control" readonly name="row[item]['+m+'][original_sku]" type="text" value="'+newItem.sku+'"></td>';
                                        Str +='<td><input id="c-original_number" class="form-control" name="row[item]['+m+'][original_number]"  type="text" value="'+Math.round(newItem.qty_ordered)+'"></td>';
                                        Str += '</tr>';
                                    }
                                    Str+='</table>'+
                                        '</div>'+
                                        '</div>';
                                    return Str;
                                });
                            }, function(data, ret){
                                //失败的回调
                                alert(ret.msg);
                                console.log(ret);
                                return false;
                            });
                        }
                    }else{
                        //$(".item_info").empty();
                        $(".item_info").remove();
                    }
                });
                //显示/隐藏三级问题
                $(document).on('click','.issueLevel',function(){
                    var issueId = $(this).attr("id");
                    var vals = $(this).offset().left+10;
                    var node = $('#display-'+issueId);
                    if(node.is(':hidden')){
                        $('.three_level').hide();
                        node.css("marginLeft",vals);
                        node.show();
                    }else{
                        node.hide();
                    }
                });
            },
            formatter:{
                orderDevice:function (value) {
                    var str2 = '';
                    if(value == 1){
                        str2= 'zeelool';
                    }else if(value==2){
                        str2= 'voogueme';
                    }else if(value==3){
                        str2 = 'nihao';
                    }
                    return str2;
                },
                prtyDevice: function (value) {
                    var str = '';
                    if (value == 1) {
                        str = '<span style = "color:red;">高级</span>';
                    } else if (value == 2) {
                        str = '<span style = "color:blue;">中级</span>';
                    } else if(value == 3){
                        str = '低级';
                    }
                    return str;
                },
                getClear:function(value){
                    if (value == null || value == undefined) {
                        return '';
                    } else {
                         var tem = value;
                            // .replace(/&lt;/g, "<")
                            // .replace(/&gt;/g, ">")
                            // .replace(/&quot;/g, "\"")
                            // .replace(/&apos;/g, "'")
                            // .replace(/&amp;/g, "&")
                            // .replace(/&nbsp;/g, '').replace(/<\/?.+?\/?>/g, '').replace(/<[^>]+>/g, "")
                           //.replace(/<\/?.+?\/?>/g, '').replace(/<[^>]+>/g, "")
                        if(tem.length<=10){
                            //console.log(row.id);
                            return tem;
                        }else{
                            return tem.substr(0, 10)+'<span class="problem_desc_info" name = "'+tem+'" style="color:red;">...</span>';

                        }
                    }
                },
                synergyOrderId:function(value){
                    var synergyOrderIdStr = '';
                    switch(value) {
                        case 2:
                            synergyOrderIdStr = '订单';
                            break;
                        case 3:
                            synergyOrderIdStr = '采购单';
                            break;
                        case 4:
                            synergyOrderIdStr = '质检单';
                            break;
                        case 5:
                            synergyOrderIdStr = '入库单';
                            break;
                        case 6:
                            synergyOrderIdStr = '出库单';
                            break;
                        case 7:
                            synergyOrderIdStr = '库存盘点单';
                            break;
                        default:
                            synergyOrderIdStr = '无';
                            break;
                    }
                    return synergyOrderIdStr;
                },
            }
        },
        detail:function(){
            Form.api.bindevent($("form[role=form]"));
        },
        handle_task:function(){
            Form.api.bindevent($("form[role=form]"));
            $(document).on('click', '.button_complete', function () {
                $('#c-synergy-status').val(2);
            });
            //承接部门和承接人二级联动
            $(document).on('change','#choose_dept_id',function(){
                var arrIds = $(this).val();
                if(arrIds == null){
                    Layer.alert('请选择承接部门');
                    return false;
                }
                var arrStr = arrIds.join("&");
                //根据承接部门查找出承接人
                Backend.api.ajax({
                    url:'infosynergytaskmanage/info_synergy_task/ajaxFindRecipient',
                    data:{arrIds:arrStr}
                }, function(data, ret){
                    // console.log(ret.data);
                    var rs = ret.data;
                    var x;
                    $("#choose_rep_id").html('');
                    var str = '';
                    for( x in rs ){
                        str +='<option value="'+x+'">' + rs[x]+'</option>';
                    }
                    $("#choose_rep_id").append(str);
                    $("#choose_rep_id").selectpicker('refresh');
                    return false;
                }, function(data, ret){
                    //失败的回调
                    alert(ret.msg);
                    console.log(ret);
                    return false;
                });
                //console.log($(this).val());
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
                var contents = '<div class="col-lg-12">' +
                    '</div>' +
                    '<div class="col-xs-6 col-md-4" style="margin-left:5.6666%;" >' +
                    '<div class="panel bg-blue">' +
                    '<div class="panel-body">' +
                    '<div class="panel-title">' +
                    '<label class="control-label col-xs-12 col-sm-3">商品名称:</label>' +
                    '<div class="col-xs-12 col-sm-8">' +
                    '<input  id="c-item_name"  class="form-control" name="row[lens][original_name][]" type="text" value="">' +
                    '</div>' +
                    '</div>' +
                    '</div>' +
                    '<div class="panel-body">' +
                    '<div class="panel-title">' +
                    '<label class="control-label col-xs-12 col-sm-3">SKU:</label>' +
                    '<div class="col-xs-12 col-sm-8">' +
                    '<input  id="c-item_sku" class="form-control" name="row[lens][original_sku][]"  type="text" value="">' +
                    '</div>' +
                    '</div>' +
                    '</div>' +
                    '<div class="panel-body">' +
                    '<div class="panel-title">' +
                    '<label class="control-label col-xs-12 col-sm-3">数量:</label>' +
                    '<div class="col-xs-12 col-sm-8">' +
                    '<input  id="c-item_qty_ordered"  class="form-control" name="row[lens][original_number][]"  type="text" value="">' +
                    '</div>' +
                    '</div>' +
                    '</div>' +
                    '<div class="panel-body">' +
                    '<div class="panel-title">' +
                    '<label class="control-label col-xs-12 col-sm-3">处方类型:</label>' +
                    '<div class="col-xs-12 col-sm-8">' +
                    '<input  id="c-recipe_type"  class="form-control" type="text" name="row[lens][recipe_type][]" value="">' +
                    '</div>' +
                    '</div>' +
                    '</div>' +
                    '<div class="panel-body">' +
                    '<div class="panel-title">' +
                    '<label class="control-label col-xs-12 col-sm-3">镜片类型:</label>' +
                    '<div class="col-xs-12 col-sm-8">' +
                    '<input  id="c-lens_type"  class="form-control" name="row[lens][lens_type][]" type="text" value="">' +
                    '</div>' +
                    '</div>' +
                    '</div>' +
                    '<div class="panel-body">' +
                    '<div class="panel-title">' +
                    '<label class="control-label col-xs-12 col-sm-3">镀膜类型:</label>' +
                    '<div class="col-xs-12 col-sm-8">' +
                    '<input  id="c-coating_film_type"  class="form-control" name="row[lens][coating_type][]"  type="text" value="">' +
                    '</div>' +
                    '</div>' +
                    '</div>' +
                    '</div>' +
                    '</div>' +
                    '<div class="col-xs-6 col-md-7">' +
                    '<div class="panel bg-aqua-gradient">' +
                    '<div class="panel-body">' +
                    '<div class="ibox-title">' +
                    '<table id="caigou-table-lens">' +
                    '<tr>' +
                    '<td colspan="10" style="text-align: center">处方参数</td>' +
                    '</tr>' +
                    '<tr>' +
                    '<td style="text-align: center">参数</td>' +
                    '<td style="text-align: center">SPH</td>' +
                    '<td style="text-align: center">CYL</td>' +
                    '<td style="text-align: center">AXI</td>' +
                    '<td style="text-align: center">ADD</td>' +
                    '<td style="text-align: center">PD</td>' +
                    '<td style="text-align: center">Prism Horizontal</td>' +
                    '<td style="text-align: center">Base Direction</td>' +
                    '<td style="text-align: center">Prism Vertical</td>' +
                    '<td style="text-align: center">Base Direction</td>' +
                    '</tr>' +
                    '<tr>' +
                    '<td style="text-align: center">Right(OD)</td>' +
                    '<td><input id="c-right_SPH" class="form-control" name="row[lens][od_sph][]"  type="text" value=""></td>' +
                    '<td><input id="c-right_CYL" class="form-control" name="row[lens][od_cyl][]"  type="text" value=""></td>' +
                    '<td><input id="c-right_AXI" class="form-control" name="row[lens][od_axis][]" type="text" value=""></td>' +
                    '<td><input id="c-right_ADD" class="form-control" name="row[lens][od_add][]"  type="text" value=""></td>' +
                    '<td><input id="c-right_PD" class="form-control"  name="row[lens][pd_r][]"    type="text" value=""></td>' +
                    '<td><input id="c-right_Prism_Horizontal" class="form-control" name="row[lens][od_pv][]"  type="text" value=""></td>' +
                    '<td><input id="c-right_bd" class="form-control"  type="text" name="row[lens][od_bd][]"  value=""></td>' +
                    '<td><input id="c-purchase_remark" class="form-control"  type="text" name="row[lens][od_pv_r][]" value=""></td>' +
                    '<td><input id="c-purchase_remark" class="form-control"  type="text" name="row[lens][od_bd_r][]" value=""></td>' +
                    '</tr>' +
                    '<tr>' +
                    '<td style="text-align: center">Left(OS)</td>' +
                    '<td><input id="c-left_SPH" class="form-control" name="row[lens][os_sph][]" type="text" value=""></td>' +
                    '<td><input id="c-left_CYL" class="form-control" name="row[lens][os_cyl][]" type="text" value=""></td>' +
                    '<td><input id="c-purchase_remark" class="form-control" name="row[lens][os_axis][]"  type="text" value=""></td>' +
                    '<td><input id="c-purchase_remark" class="form-control" name="row[lens][os_add][]"   type="text" value=""></td>' +
                    '<td><input id="c-purchase_remark" class="form-control" name="row[lens][pd_l][]"    type="text" value=""></td>' +
                    '<td><input id="c-purchase_remark" class="form-control" name="row[lens][os_pv][]"   type="text" value=""></td>' +
                    '<td><input id="c-purchase_remark" class="form-control" name="row[lens][os_bd][]" type="text" value=""></td>' +
                    '<td><input id="c-purchase_remark" class="form-control" name="row[lens][os_pv_r][]" type="text" value=""></td>' +
                    '<td><input id="c-purchase_remark" class="form-control" name="row[lens][os_bd_r][]" type="text" value=""></td>' +
                    '</tr>' +
                    '</table>' +
                    '</div>' +
                    '</div>' +
                    '</div>' +
                    '<div>'+
                    '<a href="javascript:;" class="btn btn-danger btn-del-lens" title="删除"><i class="fa fa-trash"></i>删除</a>'+
                    '</div>'+
                    '</div>';
                    $('.item_info').append(contents);

            });
            //删除一行镜片数据
            $(document).on('click', '.btn-del-lens', function () {
                $(this).parent().parent().prev().remove();
                $(this).parent().parent().remove();

            });
            //选中镜片和镜架事件
            $("input[name='row[synergy_task_id]']").click(function(){
                    var vals = $(this).val();
                var orderPlatform = $('#c-order_platform').val();
                var orderNumber   = $('#c-synergy_order_number').val();
                var synergyOrderId = $('#c-synergy_order_id').val();
                    if( (vals == 12)){ //更改镜架                        
                        if( synergyOrderId == 2){
                            Backend.api.ajax({
                                url:'saleaftermanage/sale_after_task/ajax',
                                data:{ordertype:orderPlatform,order_number:orderNumber}
                            }, function(data, ret){
                            $(".recipe-info").remove();
                            $(".item_info").empty();
                            $("#c-change_type").val(1);
                                var item = ret.data;
                                $('#customer_info').after(function(){
                                    var Str = '';
                                    Str+=  '<div class="caigou frame-info item_info" style="margin-top:15px;margin-left:10%;">'+
                                        '<p style="font-size: 16px;"><b>更改镜架</b></p>'+
                                        '<div>'+
                                        '<div id="toolbar" class="toolbar">'+
                                        '<a href="javascript:;" class="btn btn-success btn-add" title="增加"><i class="fa fa-plus"></i> 增加</a>'+
                                        '</div>'+
                                        '<table id="caigou-table-sku">'+
                                        '<tr>'+
                                        '<th>原始SKU</th>'+
                                        '<th>原始数量</th>'+
                                        '<th>变更SKU</th>'+
                                        '<th>变更数量</th>'+
                                        '<th>操作</th>'+
                                        '</tr>';
                                    for(var j = 0,len = item.length; j <len; j++) {
                                        var newItem = item[j];
                                        var m = j+1;
                                        Str +='<tr>';
                                        Str +='<td><input id="c-original_sku" class="form-control" name="row[item]['+m+'][original_sku]" type="text" value="'+newItem.sku+'"></td>';
                                        Str +='<td><input id="c-original_number" class="form-control" name="row[item]['+m+'][original_number]" type="text" value="'+Math.round(newItem.qty_ordered)+'"></td>';
                                        Str +='<td><input id="c-change_sku" class="form-control change_sku" name="row[item]['+m+'][change_sku]" type="text"></td>';
                                        Str +='<td><input id="c-change_number" class="form-control change_number" name="row[item]['+m+'][change_number]" type="text"></td>';
                                        Str +='<td><a href="javascript:;" class="btn btn-danger btn-del" title="删除"><i class="fa fa-trash"></i>删除</a></td>';
                                        Str += '</tr>';
                                    }
                                    Str+='</table>'+
                                        '</div>'+
                                        '</div>';
                                    return Str;
                                });
                            //    var item = ret.data.item;
                            //    for(var j = 0,len = item.length; j < len; j++){
                            //        //var newItem = item[j];
                            //
                            //        $('#customer_info').after(function(){
                            //
                            //        }
                            //    return false;
                            // }
                            }, function(data, ret){
                                //失败的回调
                                alert(ret.msg);
                                console.log(ret);
                                return false;
                            });
                        }
                    }else if(vals == 13){ //修改处方参数                     
                        if( synergyOrderId == 2){
                            Backend.api.ajax({
                                url:'saleaftermanage/sale_after_task/ajax',
                                data:{ordertype:orderPlatform,order_number:orderNumber}
                            }, function(data, ret){
                            $(".frame-info").remove();
                                $(".item_info").empty();
                                $("#c-change_type").val(2);
                                var item = ret.data;
                                    $('#customer_info').after(function(){
                                        var str2 = '';
                                        str2+= '<div class="row recipe-info item_info" style="margin-top:15px;margin-left:7.6666%;">'+
                                                '<p style="font-size: 16px;"><b>更改镜片</b></p>'+
                                                '<div>'+
                                                '<div id="toolbar" class="toolbar">'+
                                                '<a href="javascript:;" class="btn btn-success btn-add-lens" title="增加"><i class="fa fa-plus"></i> 增加</a>'+
                                                '</div>';
                                        for(var j = 0,len = item.length; j < len; j++) {
                                            var newItem = item[j];
                                            str2 += '<div class="col-lg-12">' +
                                                '</div>' +
                                                '<div class="col-xs-6 col-md-4" style="margin-left:5.6666%;" >' +
                                                '<div class="panel bg-blue">' +
                                                '<div class="panel-body">' +
                                                '<div class="panel-title">' +
                                                '<label class="control-label col-xs-12 col-sm-3">商品名称:</label>' +
                                                '<div class="col-xs-12 col-sm-8">' +
                                                '<input  id="c-item_name"  class="form-control"  type="text" name="row[lens][original_name][]" value="' + newItem.name + '">' +
                                                '</div>' +
                                                '</div>' +
                                                '</div>' +
                                                '<div class="panel-body">' +
                                                '<div class="panel-title">' +
                                                '<label class="control-label col-xs-12 col-sm-3">SKU:</label>' +
                                                '<div class="col-xs-12 col-sm-8">' +
                                                '<input  id="c-item_sku" class="form-control"  type="text" name="row[lens][original_sku][]" value="' + newItem.sku + '">' +
                                                '</div>' +
                                                '</div>' +
                                                '</div>' +
                                                '<div class="panel-body">' +
                                                '<div class="panel-title">' +
                                                '<label class="control-label col-xs-12 col-sm-3">数量:</label>' +
                                                '<div class="col-xs-12 col-sm-8">' +
                                                '<input  id="c-item_qty_ordered"  class="form-control"  type="text" name="row[lens][original_number][]" value="' + Math.round(newItem.qty_ordered) + '">' +
                                                '</div>' +
                                                '</div>' +
                                                '</div>' +
                                                '<div class="panel-body">' +
                                                '<div class="panel-title">' +
                                                '<label class="control-label col-xs-12 col-sm-3">处方类型:</label>' +
                                                '<div class="col-xs-12 col-sm-8">' +
                                                '<input  id="c-recipe_type"  class="form-control" type="text" name="row[lens][recipe_type][]" value="' + (newItem.prescription_type !=undefined ? newItem.prescription_type : "") + '">' +
                                                '</div>' +
                                                '</div>' +
                                                '</div>';
                                                if(orderPlatform == 3){
                                                str2+='<div class="panel-body">' +
                                                '<div class="panel-title">' +
                                                '<label class="control-label col-xs-12 col-sm-3">基片类型:</label>' +
                                                '<div class="col-xs-12 col-sm-8">' +
                                                '<input  id="c-lens_type"  class="form-control"  type="text" name="row[lens][second_name][]" value="' + (newItem.second_name !=undefined ? newItem.second_name : "")+ '">' +
                                                '</div>' +
                                                '</div>' +
                                                '</div>' +
                                                '<div class="panel-body">' +
                                                '<div class="panel-title">' +
                                                '<label class="control-label col-xs-12 col-sm-3">镜片类型:</label>' +
                                                '<div class="col-xs-12 col-sm-8">' +
                                                '<input  id="c-lens_type"  class="form-control"  type="text" name="row[lens][lens_type][]" value="' + (newItem.third_name !=undefined ? newItem.third_name : "")+ '">' +
                                                '</div>' +
                                                '</div>' +
                                                '</div>' +
                                                '<div class="panel-body">' +
                                                '<div class="panel-title">' +
                                                '<label class="control-label col-xs-12 col-sm-3">折射率:</label>' +
                                                '<div class="col-xs-12 col-sm-8">' +
                                                '<input  id="c-lens_type"  class="form-control"  type="text" name="row[lens][zsl][]" value="' + (newItem.zsl !=undefined ? newItem.zsl : "")+ '">' +
                                                '</div>' +
                                                '</div>' +
                                                '</div>' +
                                                '<div class="panel-body">' +
                                                '<div class="panel-title">' +
                                                '<label class="control-label col-xs-12 col-sm-3">镀膜类型:</label>' +
                                                '<div class="col-xs-12 col-sm-8">' +
                                                '<input  id="c-lens_type"  class="form-control"  type="text" name="row[lens][coating_type][]" value="' + (newItem.four_name !=undefined ? newItem.four_name : "")+ '">' +
                                                '</div>' +
                                                '</div>' +
                                                '</div>';
                                                }else{
                                                str2+='<div class="panel-body">' +
                                                '<div class="panel-title">' +
                                                '<label class="control-label col-xs-12 col-sm-3">镜片类型:</label>' +
                                                '<div class="col-xs-12 col-sm-8">' +
                                                '<input  id="c-lens_type"  class="form-control"  type="text" name="row[lens][lens_type][]" value="' + (newItem.index_type !=undefined ? newItem.index_type : "")+ '">' +
                                                '</div>' +
                                                '</div>' +
                                                '</div>'+
                                                '<div class="panel-body">' +
                                                '<div class="panel-title">' +
                                                '<label class="control-label col-xs-12 col-sm-3">镀膜类型:</label>' +
                                                '<div class="col-xs-12 col-sm-8">' +
                                                '<input  id="c-coating_film_type"  class="form-control"  type="text" name="row[lens][coating_type][]" value="' + (newItem.coatiing_name!=undefined ? newItem.coatiing_name : "") + '">' +
                                                '</div>' +
                                                '</div>' +
                                                '</div>'; 
                                                }
                                                str2+='</div>' +
                                                '</div>' +
                                                '<div class="col-xs-6 col-md-7">' +
                                                '<div class="panel bg-aqua-gradient">' +
                                                '<div class="panel-body">' +
                                                '<div class="ibox-title">' +
                                                '<table id="caigou-table-lens">' +
                                                '<tr>' +
                                                '<td colspan="10" style="text-align: center">处方参数</td>' +
                                                '</tr>' +
                                                '<tr>' +
                                                '<td style="text-align: center">参数</td>' +
                                                '<td style="text-align: center">SPH</td>' +
                                                '<td style="text-align: center">CYL</td>' +
                                                '<td style="text-align: center">AXI</td>' +
                                                '<td style="text-align: center">ADD</td>' +
                                                '<td style="text-align: center">PD</td>' +
                                                '<td style="text-align: center">Prism Horizontal</td>' +
                                                '<td style="text-align: center">Base Direction</td>' +
                                                '<td style="text-align: center">Prism Vertical</td>' +
                                                '<td style="text-align: center">Base Direction</td>' +
                                                '</tr>' +
                                                '<tr>' +
                                                '<td style="text-align: center">Right(OD)</td>' +
                                                '<td><input id="c-right_SPH" class="form-control"  type="text" name="row[lens][od_sph][]" value="' + (newItem.od_sph  != undefined ? newItem.od_sph : "") + '"></td>' +
                                                '<td><input id="c-right_CYL" class="form-control"  type="text" name="row[lens][od_cyl][]" value="' + (newItem.od_cyl  != undefined ? newItem.od_cyl : "") + '"></td>' +
                                                '<td><input id="c-right_AXI" class="form-control"  type="text" name="row[lens][od_axis][]" value="'+ (newItem.od_axis != undefined ? newItem.od_axis : "") + '"></td>';
                                                if(orderPlatform<3){
                                                if(newItem.total_add){
                                                    str2+= '<td><input id="c-right_ADD" class="form-control"  type="text" name="row[lens][od_add][]" value="' + (newItem.total_add  != undefined ? newItem.total_add : "") + '"></td>';
                                                }else{
                                                    str2+= '<td><input id="c-right_ADD" class="form-control"  type="text" name="row[lens][od_add][]" value="' + (newItem.os_add  != undefined ? newItem.os_add : "") + '"></td>';
                                                }
                                                }else{
                                                if(newItem.prescription_type == 'Reading Glasses' && newItem.os_add>0 && newItem.od_add>0){
                                                    str2+= '<td><input id="c-right_ADD" class="form-control"  type="text" name="row[lens][od_add][]" value="' + (newItem.od_add  != undefined ? newItem.od_add : "") + '"></td>';
                                                }else{
                                                    str2+= '<td rowspan="2"><input style="height:62px;"  id="c-right_ADD" class="form-control"  type="text" name="row[lens][od_add][]" value="' + (newItem.total_add  != undefined ? newItem.total_add : "") + '"></td>';
                                                }
                                                }
                                                if(newItem.pdcheck == 'on'){
                                                    str2+= '<td><input id="c-right_PD" class="form-control"  type="text"  name="row[lens][pd_r][]" value="'   + (newItem.pd_r  != undefined ? newItem.pd_r : "") + '"></td>';
                                                }else{
                                                str2+= '<td rowspan="2"><input style="height:62px;" id="c-right_PD" class="form-control"  type="text"  name="row[lens][pd_r][]" value="'   + (newItem.pd  != undefined ? newItem.pd : "") + '"></td>';
                                                }   
                                                str2+='<td><input id="c-right_Prism_Horizontal" class="form-control" name="row[lens][od_pv][]" type="text" value="' + (newItem.od_pv != undefined ? newItem.od_pv: "") + '"></td>' +
                                                '<td><input id="c-right_" class="form-control"  type="text" name="row[lens][od_bd][]" value="' + (newItem.od_bd != undefined ? newItem.od_bd:"")+ '"></td>' +
                                                '<td><input id="c-purchase_remark" class="form-control"  type="text" name="row[lens][od_pv_r][]" value="' + (newItem.od_pv_r != undefined ? newItem.od_pv_r:"") + '"></td>' +
                                                '<td><input id="c-purchase_remark" class="form-control"  type="text" name="row[lens][od_bd_r][]" value="' + (newItem.od_bd_r != undefined ? newItem.od_bd_r:"") + '"></td>' +
                                                '</tr>' +
                                                '<tr>' +
                                                '<td style="text-align: center">Left(OS)</td>' +
                                                '<td><input id="c-left_SPH" class="form-control"  type="text" name="row[lens][os_sph][]" value="' + (newItem.os_sph != undefined ? newItem.os_sph : "")+ '"></td>' +
                                                '<td><input id="c-left_CYL" class="form-control"  type="text" name="row[lens][os_cyl][]" value="' + (newItem.os_cyl != undefined ? newItem.os_cyl :"") + '"></td>' +
                                                '<td><input id="c-purchase_remark" class="form-control"  type="text" name="row[lens][os_axis][]" value="' + (newItem.os_axis != undefined ? newItem.os_axis :"")+ '"></td>';
                                                if(orderPlatform<3){
                                                if(!newItem.total_add){
                                                    str2+='<td><input id="c-purchase_remark" class="form-control"  type="text" name="row[lens][os_add][]" value="' + (newItem.od_add  != undefined ? newItem.od_add : "") + '"></td>';
                                                }
                                                }else{
                                                if(newItem.prescription_type == 'Reading Glasses' && newItem.os_add>0 && newItem.od_add>0){
                                                    str2+='<td><input id="c-purchase_remark" class="form-control"  type="text" name="row[lens][os_add][]" value="' + (newItem.os_add  != undefined ? newItem.os_add : "") + '"></td>';
                                                }
                                                }
                                                if(newItem.pdcheck == 'on'){
                                                    str2+='<td><input id="c-purchase_remark" class="form-control"  type="text" name="row[lens][pd_l][]" value="' + (newItem.pd_l  != undefined ? newItem.pd_l : "") + '"></td>';
                                                }
                                                str2+='<td><input id="c-purchase_remark" class="form-control"  type="text" name="row[lens][os_pv][]" value="' + (newItem.os_pv != undefined ? newItem.os_pv : "") + '"></td>' +
                                                '<td><input id="c-purchase_remark" class="form-control"  type="text" name="row[lens][os_bd][]" value="' + (newItem.os_bd != undefined ? newItem.os_bd : "")+ '"></td>' +
                                                '<td><input id="c-purchase_remark" class="form-control"  type="text" name="row[lens][os_pv_r][]" value="' + (newItem.os_pv_r!= undefined ? newItem.os_pv_r : "") + '"></td>' +
                                                '<td><input id="c-purchase_remark" class="form-control"  type="text" name="row[lens][os_bd_r][]" value="' + (newItem.os_bd_r!= undefined ? newItem.os_bd_r : "") + '"></td>' +
                                                '</tr>' +
                                                '</table>' +
                                                '</div>' +
                                                '</div>' +
                                                '</div>' +
                                                '<div>'+
                                                '<a href="javascript:;" class="btn btn-danger btn-del-lens" title="删除"><i class="fa fa-trash"></i>删除</a>'+
                                                '</div>'+
                                                '</div>';
                                        }
                                        str2+='</div>';
                                        return str2;
                                    });

                            }, function(data, ret){
                                //失败的回调
                                alert(ret.msg);
                                console.log(ret);
                                return false;
                            });
                        }
                }else if(vals == 14){
                    if( synergyOrderId == 2){
                        Backend.api.ajax({
                            url:'saleaftermanage/sale_after_task/ajax',
                            data:{ordertype:orderPlatform,order_number:orderNumber}
                        }, function(data, ret){
                            $(".recipe-info").remove();
                            $(".item_info").empty();
                            $("#c-change_type").val(3);
                            var item = ret.data;
                            $('#customer_info').after(function(){
                                var Str = '';
                                Str+=  '<div class="caigou frame-info item_info" style="margin-top:15px;margin-left:10%;">'+
                                    '<p style="font-size: 16px;"><b>取消订单</b></p>'+
                                    '<div>'+
                                    '<table id="caigou-table-sku">'+
                                    '<tr>'+
                                    '<th>原始SKU</th>'+
                                    '<th>取消数量</th>'+
                                    '</tr>';
                                for(var j = 0,len = item.length; j <len; j++) {
                                    var newItem = item[j];
                                    var m = j+1;
                                    Str +='<tr>';
                                    Str +='<td><input id="c-original_sku" class="form-control" readonly name="row[item]['+m+'][original_sku]" type="text" value="'+newItem.sku+'"></td>';
                                    Str +='<td><input id="c-original_number" class="form-control" name="row[item]['+m+'][original_number]"  type="text" value="'+Math.round(newItem.qty_ordered)+'"></td>';
                                    Str += '</tr>';
                                }
                                Str+='</table>'+
                                    '</div>'+
                                    '</div>';
                                return Str;
                            });
                        }, function(data, ret){
                            //失败的回调
                            alert(ret.msg);
                            console.log(ret);
                            return false;
                        });
                    }
                }else{
                    //$(".item_info").empty();
                    $(".item_info").remove();
                }
            });
            //显示/隐藏三级问题
            $(document).on('click','.issueLevel',function(){
                var issueId = $(this).attr("id");
                var vals = $(this).offset().left+10;
                var node = $('#display-'+issueId);
                if(node.is(':hidden')){
                    $('.three_level').hide();
                    node.css("marginLeft",vals);
                    node.show();
                }else{
                    node.hide();
                }
            });
        },
		add_remark:function(){
            Form.api.bindevent($("form[role=form]"));
        },
    };
    return Controller;
});