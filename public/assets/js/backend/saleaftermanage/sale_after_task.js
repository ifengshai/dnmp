define(['jquery', 'bootstrap', 'backend', 'table', 'form','jqui'], function ($, undefined, Backend, Table, Form) {

    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                searchFormVisible: true,
                //searchFormTemplate: 'customformtpl',
                extend: {
                    index_url: 'saleaftermanage/sale_after_task/index' + location.search,
                    add_url: 'saleaftermanage/sale_after_task/add',
                    edit_url: 'saleaftermanage/sale_after_task/edit',
                    del_url: 'saleaftermanage/sale_after_task/del',
                    multi_url: 'saleaftermanage/sale_after_task/multi',
                    table: 'sale_after_task',
                }
            });
            var table = $("#table");
            $(".btn-add").data("area", ["100%", "100%"]);
            table.on('post-body.bs.table', function (e, settings, json, xhr) {
                $(".btn-editone").data("area", ["100%", "100%"]);
            });
            $(document).on('click',".problem_desc_info",function(){
                var problem_desc = $(this).attr('name');
                Layer.alert(problem_desc);
                return false;
            });
            // 初始化表格
            table.bootstrapTable({
                url: $.fn.bootstrapTable.defaults.extend.index_url,
                pk: 'id',
                sortName: 'id',
                columns: [
                    [
                        {checkbox: true},
                        //{data:("area",["100%","100%"])},
                        {field:'numberId',title:'序号'},
                        {field: 'id', title: __('Id'),operate:false},
                        {field: 'task_number', title: __('Task_number')},
                        {field:'task_status',title:__('Task_status'),searchList:{0:'未处理',1:'处理中',2:'已完成'},formatter:Controller.api.formatter.task_status},
                        {field: 'order_platform',searchList:$.getJSON('saleaftermanage/sale_after_task/getAjaxOrderPlatformList'), title: __('Order_platform'),formatter: Controller.api.formatter.devicess},
                        {field:'order_source',title:__('Order_source'),searchList:{1:'pc端',2:'web端'},visible: false},
                        {field: 'order_number', title: __('Order_number')},
                        {field: 'customer_name',title:__('Customer_name'),operate:false},
                        {field: 'customer_email',title:__('Customer_email'),operate:false},
                        {field: 'order_status', title: __('Order_status'),searchList:{'canceled':'canceled','closed':'closed','complete':'complete','creditcard_failed':'creditcard_failed','creditcard_pending':'creditcard_pending','free_processing':'free_processing','holded':'holded','payment_review':'payment_review','paypal_canceled_reversal':'paypal_canceled_reversal','paypal_reversed':'paypal_reversed','pending':'pending','processing':'processing'}},
                        {field: 'dept_id', title: __('Dept_id'),operate:false},
                        {field: 'rep_id', title: __('Rep_id'),operate:false},
                        {field: 'prty_id', title: __('Prty_id'),searchList: {1:'高级',2:'中级',3:'低级'},formatter: Controller.api.formatter.device},
                        {field: 'saleAfterIssue.id', title: __('Problem_id'),searchList:$.getJSON('saleaftermanage/sale_after_task/ajaxGetIssueList'),visible:false},
                        {field: 'sale_after_issue.name', title: __('Problem_id'),operate:false},
                        {field: 'problem_desc', title: __('problem_desc'),formatter:Controller.api.formatter.getClear,operate:false},
                        {field: 'create_person', title: __('Create_person')},
                        {field: 'createtime', title: __('Createtime'), operate:'RANGE', addclass:'datetimerange', formatter: Table.api.formatter.datetime},
                        // {field: 'operate', title: __('Operate'), table: table, events: Table.api.events.operate, formatter: Table.api.formatter.operate,
                        //     buttons:[{
                        //         name:'detail',
                        //     }]
                        // },
                        {
                            field: 'operate', title: __('Operate'), table: table, events: Table.api.events.operate, extend: 'data-area = \'["100%","100%"]\'',
                            buttons: [
                                {
                                    name: 'detail',
                                    text: '详情',
                                    title: __('查看详情'),
                                    extend: 'data-area = \'["100%","100%"]\'',
                                    classname: 'btn btn-xs btn-primary btn-dialog',
                                    icon: 'fa fa-list',
                                    url: 'saleaftermanage/sale_after_task/detail',
                                    callback: function (data) {
                                        Layer.alert("接收到回传数据：" + JSON.stringify(data), {title: "回传数据"});
                                    },
                                    visible: function (row) {
                                        //返回true时按钮显示,返回false隐藏
                                        return true;
                                    }
                                },
                            ],formatter: Table.api.formatter.operate
                        },
                    ]
                ]
            });
            $('.panel-heading a[data-toggle="tab"]').on('shown.bs.tab', function (e) {
                var field = $(this).data("field");
                var value = $(this).data("value");
                var options = table.bootstrapTable('getOptions');
                options.pageNumber = 1;
                options.queryParams = function (params) {
                    var filter = {};
                    if (value !== '') {
                        filter[field] = value;
                    }
                    params.filter = JSON.stringify(filter);
                    return params;
                };
                table.bootstrapTable('refresh', {});
                return false;
            });
            // 为表格绑定事件
            Table.api.bindevent(table);
            //处理任务完成
            $(document).on('click', '.btn-handle-complete', function () {
                var ids = Table.api.selectedids(table);
                Layer.confirm(
                    __('确定要处理完成吗'),
                    function (index) {
                        Backend.api.ajax({
                            url: "saleaftermanage/sale_after_task/completeAjaxAll",
                            data: { ids: ids }
                        }, function (data, ret) {
                            table.bootstrapTable('refresh');
                            Layer.close(index);
                        });
                    }
                );
            });

        },
        add: function () {
            Controller.api.bindevent();

        },
        edit: function () {
            Controller.api.bindevent();
            //处理完成
            $('#button_complete').click(function () {
                var idss = $('#c-id').val();
                Layer.confirm(
                    __('确定要处理完成吗?'),
                    {icon: 3, title: __('Warning'), offset: 0, shadeClose: true},
                    // function (index) {
                    //     Layer.close(index);
                    //     Backend.api.ajax({
                    //         url:'saleaftermanage/sale_after_task/completeAjax',
                    //         data:{idss:idss},
                    //     }, function(data, ret){
                    //         Fast.api.redirect('/admin/saleaftermanage/sale_after_task?ref=addtabs');
                    //     }, function(data, ret){
                    //         console.log('失败的回调');
                    //     });

                    // }
                    function (){
                        location.href='/admin/saleaftermanage/sale_after_task/completeAjax?idss='+idss;
                    },function(){
                        return false;
                    }
                );

            });
        },
        api: {
            formatter: {
                device: function (value) {
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
                devicess:function (value) {
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
                task_status:function (value) {
                    var task_status = '';
                    if(value == 0){
                        task_status = '<span style="color:red;">未处理</span>';
                    }else if(value == 1){
                        task_status = '<span style="color:blue;">处理中</span>';
                    }else{
                        task_status = '处理完成';
                    }
                    return task_status;
                },
                getClear:function(value){
                    if (value == null || value == undefined) {
                        return '';
                    } else {
                         var tem = value
                            .replace(/&lt;/g, "<")
                            .replace(/&gt;/g, ">")
                            .replace(/&quot;/g, "\"")
                            .replace(/&apos;/g, "'")
                            .replace(/&amp;/g, "&")
                            .replace(/&nbsp;/g, '').replace(/<\/?.+?\/?>/g, '').replace(/<[^>]+>/g, "")
                        if(tem.length<=10){
                            //console.log(row.id);
                            return tem;
                        }else{
                            return tem.substr(0, 10)+'<span class="problem_desc_info" name = "'+tem+'" style="color:red;">...</span>';

                        }
                    }
                },

            },
            //$(document).on('click',"#problem_desc_info");
            bindevent: function () {
                Form.api.bindevent($("form[role=form]"),function (data,ret) {
                    //console.log(ret);
                    location.href= ret.url;
                });
                //模糊匹配订单号
                $('#c-order_number').autocomplete({
                    source:function(request,response){
                        var incrementId = $('#c-order_number').val();
                        var orderType = $('#c-order_platform').val();
                        if(incrementId.length>2){
                            $.ajax({
                                type:"POST",
                                url:"saleaftermanage/order_return/ajaxGetLikeOrder",
                                dataType : "json",
                                cache : false,
                                async : false,
                                data : {
                                    orderType:orderType,order_number:incrementId
                                },
                                success : function(json) {
                                     var data = json.data;
                                    response($.map(data,function(item){
                                        return {
                                            label:item,//下拉框显示值
                                            value:item,//选中后，填充到input框的值
                                            //id:item.bankCodeInfo//选中后，填充到id里面的值
                                        };
                                    }));
                                }
                            });
                        }
                    },
                    delay: 10,//延迟100ms便于输入
                    select : function(event, ui) {
                        $("#bankUnionNo").val(ui.item.id);//取出在return里面放入到item中的属性
                    },
                    scroll:true,
                    pagingMore:true,
                    max:5000
                });
                //查询订单详情并生成任务单号
                $(document).on('blur','#c-order_number',function(){
                    var ordertype = $('#c-order_platform').val();
                    var order_number = $('#c-order_number').val();
                    if(ordertype<=0){
                        Layer.alert('请选择正确的平台');
                        return false;
                    }
                    Backend.api.ajax({
                        url:'saleaftermanage/sale_after_task/ajax',
                        data:{ordertype:ordertype,order_number:order_number}
                    }, function(data, ret){
                        //成功的回调
                        //alert(ret);
                        //清除html商品数据
                        var email = ret.data[0].customer_email != undefined ? ret.data[0].customer_email : '';
                        var firstname =  ret.data[0].customer_firstname != undefined ? ret.data[0].customer_firstname : '';
                        var lastname  =  ret.data[0].customer_lastname != undefined ? ret.data[0].customer_lastname : '';
                        $(".item_info").empty();
                        $('#c-order_status').val(ret.data[0].status);
                        $('#c-customer_name').val(firstname+" "+ lastname);
                        $('#c-customer_email').val(email);
                        if(ret.data.store_id>=2){
                            $('#c-order_source').val(2);
                        }else{
                            $('#c-order_source').val(1);
                        }
                        var item = ret.data;
                        for(var j = 0,len = item.length; j < len; j++){
                            //console.log(item[j]);
                            var newItem = item[j];
                            //console.log(newItem.name);
                            $('#customer_info').after(function(){
                                return '<div class="row item_info" style="margin-top:15px;margin-left:7.6666%;" >'+
                                    '<div class="col-lg-12">'+
                                    '</div>'+
                                    '<div class="col-xs-6 col-md-4">'+
                                    '<div class="panel bg-blue">'+
                                    '<div class="panel-body">'+
                                    '<div class="panel-title">'+
                                    '<label class="control-label col-xs-12 col-sm-3">商品名称:</label>'+
                                    '<div class="col-xs-12 col-sm-8">'+
                                    '<input  id="c-item_name"  class="form-control"  type="text" value="'+ newItem.name+'">'+
                                    '</div>'+
                                    '</div>'+
                                    '</div>'+
                                    '<div class="panel-body">'+
                                    '<div class="panel-title">'+
                                    '<label class="control-label col-xs-12 col-sm-3">SKU:</label>'+
                                    '<div class="col-xs-12 col-sm-8">'+
                                    '<input  id="c-item_sku" class="form-control"  type="text" value="'+newItem.sku+'">'+
                                    '</div>'+
                                    '</div>'+
                                    '</div>'+
                                    '<div class="panel-body">'+
                                    '<div class="panel-title">'+
                                    '<label class="control-label col-xs-12 col-sm-3">数量:</label>'+
                                    '<div class="col-xs-12 col-sm-8">'+
                                    '<input  id="c-item_qty_ordered"  class="form-control"  type="text" value="'+Math.round(newItem.qty_ordered)+'">'+
                                    '</div>'+
                                    '</div>'+
                                    '</div>'+
                                    '<div class="panel-body">'+
                                    '<div class="panel-title">'+
                                    '<label class="control-label col-xs-12 col-sm-3">处方类型:</label>'+
                                    '<div class="col-xs-12 col-sm-8">'+
                                    '<input  id="c-recipe_type"  class="form-control" type="text" value="'+(newItem.prescription_type != undefined ? newItem.prescription_type : '')+'">'+
                                    '</div>'+
                                    '</div>'+
                                    '</div>'+
                                    '<div class="panel-body">'+
                                    '<div class="panel-title">'+
                                    '<label class="control-label col-xs-12 col-sm-3">镜片类型:</label>'+
                                    '<div class="col-xs-12 col-sm-8">'+
                                    '<input  id="c-lens_type"  class="form-control"  type="text" value="'+(newItem.index_type != undefined ? newItem.index_type : '')+'">'+
                                    '</div>'+
                                    '</div>'+
                                    '</div>'+
                                    '<div class="panel-body">'+
                                    '<div class="panel-title">'+
                                    '<label class="control-label col-xs-12 col-sm-3">镀膜类型:</label>'+
                                    '<div class="col-xs-12 col-sm-8">'+
                                    '<input  id="c-coating_film_type"  class="form-control"  type="text" value="'+(newItem.coatiing_name !=undefined ? newItem.coatiing_name : '')+'">'+
                                    '</div>'+
                                    '</div>'+
                                    '</div>'+
                                    '</div>'+
                                    '</div>'+
                                    '<div class="col-xs-6 col-md-7">'+
                                    '<div class="panel bg-aqua-gradient">'+
                                    '<div class="panel-body">'+
                                    '<div class="ibox-title">'+
                                    '<table id="caigou-table">'+
                                    '<tr>'+
                                    '<td colspan="10" style="text-align: center">处方参数</td>'+
                                    '</tr>'+
                                    '<tr>'+
                                    '<td style="text-align: center">参数</td>'+
                                    '<td style="text-align: center">SPH</td>'+
                                    '<td style="text-align: center">CYL</td>'+
                                    '<td style="text-align: center">AXI</td>'+
                                    '<td style="text-align: center">ADD</td>'+
                                    '<td style="text-align: center">PD</td>'+
                                    '<td style="text-align: center">Prism Horizontal</td>'+
                                    '<td style="text-align: center">Base Direction</td>'+
                                    '<td style="text-align: center">Prism Vertical</td>'+
                                    '<td style="text-align: center">Base Direction</td>'+
                                    '</tr>'+
                                    '<tr>'+
                                    '<td style="text-align: center">Right(OD)</td>'+
                                    '<td><input id="c-right_SPH" class="form-control"  type="text" value="'+(newItem.od_sph !=undefined ? newItem.od_sph : '')+'"></td>'+
                                    '<td><input id="c-right_CYL" class="form-control"  type="text" value="'+(newItem.od_cyl  != undefined ? newItem.od_cyl : "")+'"></td>'+
                                    '<td><input id="c-right_AXI" class="form-control"  type="text" value="'+(newItem.od_axis != undefined ? newItem.od_axis : "")+'"></td>'+
                                    '<td><input id="c-right_ADD" class="form-control"  type="text" value="'+(newItem.od_add  != undefined ? newItem.od_add : "")+'"></td>'+
                                    '<td><input id="c-right_PD" class="form-control"  type="text" value="'+(newItem.pd_r    != undefined ? newItem.pd_r: "")+'"></td>'+
                                    '<td><input id="c-right_Prism_Horizontal" class="form-control"  type="text" value="'+(newItem.od_pv != undefined ? newItem.od_pv: "")+'"></td>'+
                                    '<td><input id="c-right_" class="form-control"  type="text" value="'+(newItem.od_bd != undefined ? newItem.od_bd:"")+'"></td>'+
                                    '<td><input id="c-purchase_remark" class="form-control"  type="text" value="'+(newItem.od_pv_r != undefined ? newItem.od_pv_r:"")+'"></td>'+
                                    '<td><input id="c-purchase_remark" class="form-control"  type="text" value="'+(newItem.od_bd_r != undefined ? newItem.od_bd_r:"")+'"></td>'+
                                    '</tr>'+
                                    '<tr>'+
                                    '<td style="text-align: center">Left(OS)</td>'+
                                    '<td><input id="c-left_SPH" class="form-control"  type="text" value="'+(newItem.os_sph != undefined ? newItem.os_sph : "")+'"></td>'+
                                    '<td><input id="c-left_CYL" class="form-control"  type="text" value="'+(newItem.os_cyl != undefined ? newItem.os_cyl :"")+'"></td>'+
                                    '<td><input id="c-purchase_remark" class="form-control"  type="text" value="'+(newItem.os_axis != undefined ? newItem.os_axis :"")+'"></td>'+
                                    '<td><input id="c-purchase_remark" class="form-control"  type="text" value="'+(newItem.os_add != undefined ? newItem.os_add :"")+'"></td>'+
                                    '<td><input id="c-purchase_remark" class="form-control"  type="text" value="'+(newItem.pd_l != undefined ? newItem.pd_l :"")+'"></td>'+
                                    '<td><input id="c-purchase_remark" class="form-control"  type="text" value="'+(newItem.os_pv != undefined ? newItem.os_pv : "")+'"></td>'+
                                    '<td><input id="c-purchase_remark" class="form-control"  type="text" value="'+(newItem.os_bd != undefined ? newItem.os_bd : "")+'"></td>'+
                                    '<td><input id="c-purchase_remark" class="form-control"  type="text" value="'+(newItem.os_pv_r!= undefined ? newItem.os_pv_r : "")+'"></td>'+
                                    '<td><input id="c-purchase_remark" class="form-control"  type="text" value="'+(newItem.os_bd_r!= undefined ? newItem.os_bd_r : "")+'"></td>'+
                                    '</tr>'+
                                    '</table>'+
                                    '</div>'+
                                    '</div>'+
                                    '</div>'+
                                    '</div>'+
                                    '</div>';
                            });
                        }
                        //console.log(ret);
                        //console.log($('#c-order_status').val());
                        return false;
                    }, function(data, ret){
                        //失败的回调
                        alert(ret.msg);
                        console.log(ret);
                        return false;
                    });
                });
                //显示/隐藏三级问题
                $(document).on('click','.issueLevel',function(){
                    var issueId = $(this).attr("id");
                    var node = $('#display-'+issueId);
                    if(node.is(':hidden')){
                        node.show();
                    }else{
                        node.hide();
                    }
                });
            }
        },
        detail:function(){
            Form.api.bindevent($("form[role=form]"));
        }
    };
    return Controller;
});