define(['jquery', 'bootstrap', 'backend', 'table', 'form','jqui','custom-css','bootstrap-table-jump-to'], function ($, undefined, Backend, Table, Form) {

    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({ 
                showJumpto: true,
                searchFormVisible: true,
                pageList: [10, 25, 50, 100],
                //searchFormTemplate: 'customformtpl',
                extend: {
                    index_url: 'saleaftermanage/sale_after_task/index' + location.search + '/task_number/' + Config.task_number,
                    add_url: 'saleaftermanage/sale_after_task/add',
                    edit_url: 'saleaftermanage/sale_after_task/edit',
                    //del_url: 'saleaftermanage/sale_after_task/del',
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
                //Layer.alert(problem_desc);
                Layer.open({
                    closeBtn: 1,
                    title: '问题描述',
                    area: ['900px', '500px'],
                    content:problem_desc
                });
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
                        {field: '', title: __('序号'), formatter: function (value, row, index) {
                            var options = table.bootstrapTable('getOptions');
                            var pageNumber = options.pageNumber;
                            var pageSize = options.pageSize;

                            //return (pageNumber - 1) * pageSize + 1 + index;
                            return 1+index;
                            }, operate: false
                        },
                        // {field: 'id', title: __('Id'),operate:false},
                        {field: 'task_number', title: __('Task_number')},
                        {
                            field:'task_status',
                            title:__('Task_status'),
                            searchList:{0:'未处理',1:'处理中',2:'已完成',3:'已取消'},
                            custom: { 0: 'blue', 1: 'yellow', 2: 'success',3:'red'},
                            formatter:Table.api.formatter.status
                        },
                        {field: 'order_platform',searchList:$.getJSON('saleaftermanage/sale_after_task/getAjaxOrderPlatformList'), title: __('Order_platform'),formatter: Controller.api.formatter.devicess},
                        {
                            field:'order_source',
                            title:__('Order_source'),
                            searchList:{1:'pc端',2:'移动端'},
                            custom: { 1: 'blue', 2: 'yellow'},
                            formatter: Table.api.formatter.status
                        },
                        {field: 'order_number', title: __('Order_number')},
                        {field:'order_skus',title:__('Order_skus')},
                        {field: 'customer_name',title:__('Customer_name'),operate:false},
                        {field: 'customer_email',title:__('Customer_email'),operate:false},
                        {field: 'order_status', title: __('Order_status'),searchList:{'canceled':'canceled','closed':'closed','complete':'complete','creditcard_failed':'creditcard_failed','creditcard_pending':'creditcard_pending','free_processing':'free_processing','holded':'holded','payment_review':'payment_review','paypal_canceled_reversal':'paypal_canceled_reversal','paypal_reversed':'paypal_reversed','pending':'pending','processing':'processing'}},
                        //{field: 'dept_id', title: __('Dept_id'),operate:false},
                        {field: 'rep_id', title: __('Rep_id'),operate:false},
                        { field: 'is_refund',
                          title:__('Is_refund'),
                          searchList:{1:'无',2:'有'},
                          custom: { 1: 'blue', 2: 'red'},
                          formatter: Table.api.formatter.status
                        },
                        {
                            field:'refund_money',
                            title:__('Refund_money'),
                            operate: 'between', formatter: Controller.api.formatter.float_format  
                        },
                        {field: 'prty_id', title: __('Prty_id'),searchList: {1:'高级',2:'中级',3:'低级'},formatter: Controller.api.formatter.device},
                        {field: 'saleAfterIssue.id', title: __('Problem_id'),searchList:$.getJSON('saleaftermanage/sale_after_task/ajaxGetIssueList'),visible:false},
                        /* {field: 'sale_after_issue.name', title: __('Problem_id'),operate:false}, */
						{field: 'problem', title: __('Problem_id'),operate:false},
                        {field: 'problem_desc', title: __('problem_desc'),formatter:Controller.api.formatter.getClear,operate:false},
                        {field: 'create_person', title: __('Create_person')},
                        {field: 'create_time', title: __('Createtime'), operate:'RANGE', addclass:'datetimerange', formatter: Table.api.formatter.datetime},
						{field: 'complete_time', title: __('完成时间'), operate:'RANGE', addclass:'datetimerange'},
                        // {field: 'operate', title: __('Operate'), table: table, events: Table.api.events.operate, formatter: Table.api.formatter.operate,
                        //     buttons:[{
                        //         name:'detail',
                        //     }]
                        // },
                        {
                            field: 'operate',width: "120px", title: __('Operate'), table: table,events: Table.api.events.operate,formatter: Table.api.formatter.operate,
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
                                {
                                    name: 'edit',
                                    text: '编辑',
                                    title: __('编辑'),
                                    classname: 'btn btn-xs btn-success btn-dialog',
                                    icon: 'fa fa-pencil',
                                    url:  'saleaftermanage/sale_after_task/edit',
                                    extend: 'data-area = \'["100%","100%"]\'',
                                    callback: function (data) {
                                        Layer.alert("接收到回传数据：" + JSON.stringify(data), { title: "回传数据" });
                                    },
                                    visible: function (row) {
                                        if(row.task_status == 0){
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
                                    url: 'saleaftermanage/sale_after_task/closed',
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
                                        if(row.task_status == 0){
                                            return true;
                                        }
                                            return false;
                                            
                                    }

                                }
                            ]
                        },
                    ]
                ]
            });
            $('.panel-heading a[data-toggle="tab"]').on('shown.bs.tab', function (e) {
                var field = $(this).data("field");
                var value = $(this).data("value");
                console.log(field);
                console.log(value);
                var options = table.bootstrapTable('getOptions');
                options.pageNumber = 1;
                var queryParams = options.queryParams;
                options.queryParams = function (params) {
                    var params = queryParams(params);
                    var filter = params.filter ? JSON.parse(params.filter) : {};
                    var op     = params.op ? JSON.parse(params.op) : {};
                    if(field == 'create_person'){
                        delete filter.rep_id;
                        filter[field] = value;
                    }else if(field == 'rep_id'){
                        delete filter.create_person;
                        filter[field] = value;
                    }else{
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
            // 为表格绑定事件
            Table.api.bindevent(table);
            //处理任务
            $(document).on('click','.btn-handle',function(){
                var ids = Table.api.selectedids(table);
                Backend.api.open('saleaftermanage/sale_after_task/handle_task/ids/'+ids,'处理任务',{area:["100%", "100%"]});
                //window.location.href = 'saleaftermanage/sale_after_task/handle_task/ids/'+ids;
            });
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
            //批量导出xls 
            $('.btn-batch-export-xls').click(function () {
                var ids = Table.api.selectedids(table);
                if (ids.length > 0) {
                    window.open(Config.moduleurl + '/saleaftermanage/sale_after_task/batch_export_xls?ids=' + ids, '_blank');
                } else {
                    var options = table.bootstrapTable('getOptions');
                    var search = options.queryParams({});
                    var filter = search.filter;
                    var op = search.op;
                    window.open(Config.moduleurl + '/saleaftermanage/sale_after_task/batch_export_xls?filter=' + filter + '&op=' + op, '_blank');
                }
                
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
                        location.href='saleaftermanage/sale_after_task/completeAjax?idss='+idss;
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
                float_format: function (value, row, index) {
                    return parseFloat(value).toFixed(2);
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
                        //$('#c-order_sku').val()
                        $('#c-customer_name').val(firstname+" "+ lastname);
                        $('#c-customer_email').val(email);
                        if(ret.data[0].store_id>=2){
                            $('#c-order_source').val(2);
                        }else{
                            $('#c-order_source').val(1);
                        }
                        var item = ret.data;
                        console.log(item);
                        for(var j = 0,len = item.length; j < len; j++){
                            //console.log(item[j]);
                            var newItem = item[j];
                            //console.log(newItem.name);
                            $('#customer_info').after(function(){
                            //    var  Str= '<div class="row item_info" style="margin-top:15px;margin-left:7.6666%;" >'+
                            //         '<div class="col-lg-12">'+
                            //         '</div>'+
                            //         '<div class="col-xs-6 col-md-4" class="handle_table">'+
                            //         '<div class="panel bg-blue">'+
                            //         '<div class="panel-body">'+
                            //         '<div class="panel-title">'+
                            //         '<label class="control-label col-xs-12 col-sm-3"><td>商品名称:</td></label>'+
                            //         '<div class="col-xs-12 col-sm-8">'+
                            //         '<input  id="c-item_name"  class="form-control"  type="text" value="'+ newItem.name+'">'+
                            //         '</div>'+
                            //         '</div>'+
                            //         '</div>'+
                            //         '<div class="panel-body">'+
                            //         '<div class="panel-title">'+
                            //         '<label class="control-label col-xs-12 col-sm-3">SKU:</label>'+
                            //         '<div class="col-xs-12 col-sm-8">'+
                            //         '<input  id="c-item_sku" class="form-control"  type="text" value="'+newItem.sku+'">'+
                            //         '</div>'+
                            //         '</div>'+
                            //         '</div>'+
                            //         '<div class="panel-body">'+
                            //         '<div class="panel-title">'+
                            //         '<label class="control-label col-xs-12 col-sm-3">数量:</label>'+
                            //         '<div class="col-xs-12 col-sm-8">'+
                            //         '<input  id="c-item_qty_ordered"  class="form-control"  type="text" value="'+Math.round(newItem.qty_ordered)+'">'+
                            //         '</div>'+
                            //         '</div>'+
                            //         '</div>'+
                            //         '<div class="panel-body">'+
                            //         '<div class="panel-title">'+
                            //         '<label class="control-label col-xs-12 col-sm-3">处方类型:</label>'+
                            //         '<div class="col-xs-12 col-sm-8">'+
                            //         '<input  id="c-recipe_type"  class="form-control" type="text" value="'+(newItem.prescription_type != undefined ? newItem.prescription_type : '')+'">'+
                            //         '</div>'+
                            //         '</div>'+
                            //         '</div>'+
                            //         '<div class="panel-body">'+
                            //         '<div class="panel-title">'+
                            //         '<label class="control-label col-xs-12 col-sm-3">镜片类型:</label>'+
                            //         '<div class="col-xs-12 col-sm-8">'+
                            //         '<input  id="c-lens_type"  class="form-control"  type="text" value="'+(newItem.index_type != undefined ? newItem.index_type : '')+'">'+
                            //         '</div>'+
                            //         '</div>'+
                            //         '</div>'+
                            //         '<div class="panel-body">'+
                            //         '<div class="panel-title">'+
                            //         '<label class="control-label col-xs-12 col-sm-3">镀膜类型:</label>'+
                            //         '<div class="col-xs-12 col-sm-8">'+
                            //         '<input  id="c-coating_film_type"  class="form-control"  type="text" value="'+(newItem.coatiing_name !=undefined ? newItem.coatiing_name : '')+'">'+
                            //         '</div>'+
                            //         '</div>'+
                            //         '</div>'+
                            //         '</div>'+
                            //         '</div>'+
                            //         '<div class="col-xs-6 col-md-7" style="float:right;">'+
                            //         '<table id="caigou-table" class="handle_table">'+
                            //         '<tr>'+
                            //         '<td colspan="10" style="text-align: center;">处方参数</td>'+
                            //         '</tr>'+
                            //         '<tr>'+
                            //         '<td>参数</td>'+
                            //         '<td>SPH</td>'+
                            //         '<td>CYL</td>'+
                            //         '<td>AXI</td>'+
                            //         '<td>ADD</td>'+
                            //         '<td>PD</td>'+
                            //         '<td>Prism Horizontal</td>'+
                            //         '<td>Base Direction</td>'+
                            //         '<td>Prism Vertical</td>'+
                            //         '<td>Base Direction</td>'+
                            //         '</tr>'+
                            //         '<tr>'+
                            //         '<td>Right(OD)</td>'+
                            //         '<td>'+(newItem.od_sph !=undefined ? newItem.od_sph : '')+'</td>'+
                            //         '<td>'+(newItem.od_cyl  != undefined ? newItem.od_cyl : "")+'</td>'+
                            //         '<td>'+(newItem.od_axis != undefined ? newItem.od_axis : "")+'</td>';
                            //         if(newItem.total_add){
                            //             Str+= '<td rowspan="2">'+(newItem.total_add  != undefined ? newItem.total_add : "")+'</td>';
                            //         }else{
                            //             Str+='<td>'+(newItem.os_add  != undefined ? newItem.os_add : "")+'</td>';
                            //         }
                            //        Str+= '<td>'+(newItem.pd_r    != undefined ? newItem.pd_r: "")+'</td>'+
                            //         '<td>'+(newItem.od_pv != undefined ? newItem.od_pv: "")+'</td>'+
                            //         '<td>'+(newItem.od_bd != undefined ? newItem.od_bd:"")+'</td>'+
                            //         '<td>'+(newItem.od_pv_r != undefined ? newItem.od_pv_r:"")+'</td>'+
                            //         '<td>'+(newItem.od_bd_r != undefined ? newItem.od_bd_r:"")+'</td>'+
                            //         '</tr>'+
                            //         '<tr>'+
                            //         '<td>Left(OS)</td>'+
                            //         '<td>'+(newItem.os_sph != undefined ? newItem.os_sph : "")+'</td>'+
                            //         '<td>'+(newItem.os_cyl != undefined ? newItem.os_cyl :"")+'</td>'+
                            //         '<td>'+(newItem.os_axis != undefined ? newItem.os_axis :"")+'</td>';
                            //         if(!newItem.total_add){
                            //             Str+='<td>'+(newItem.od_add != undefined ? newItem.od_add :"")+'</td>';
                            //         }
                            //             Str+='<td>'+(newItem.pd_l != undefined ? newItem.pd_l :"")+'</td>'+
                            //         '<td>'+(newItem.os_pv != undefined ? newItem.os_pv : "")+'</td>'+
                            //         '<td>'+(newItem.os_bd != undefined ? newItem.os_bd : "")+'</td>'+
                            //         '<td>'+(newItem.os_pv_r!= undefined ? newItem.os_pv_r : "")+'</td>'+
                            //         '<td>'+(newItem.os_bd_r!= undefined ? newItem.os_bd_r : "")+'</td>'+
                            //         '</tr>'+
                            //         '</table>'+
                            //         '</div>'+
                            //         '</div>';
                                var  Str= '<div class="row item_info" style="margin-top:15px;margin-left:10.6666%;" >'+
                                '<div class="col-lg-12">'+
                                '</div>'+
                                '<div class="col-xs-6 col-md-4">'+
                                '<div>'+
                                '<table id="caigou-table" class="handle_table1">'+
                                '<tr>'+
                                   '<td>name</td>'+
                                   '<td style="width:90%;">'+newItem.name+'</td>'+
                                '</tr>'+
                                '<tr>'+
                                   '<td>SKU</td>'+
                                   '<td style="width:90%;">'+newItem.sku+'</td>'+
                                '</tr>'+
                                '<tr>'+
                                    '<td>qty_ordered</td>'+
                                    '<td style="width:90%;">'+Math.round(newItem.qty_ordered)+'</td>'+
                                '</tr>'+
                                '<tr>'+
                                    '<td>prescription_type</td>'+
                                    '<td style="width:90%;">'+(newItem.prescription_type != undefined ? newItem.prescription_type : '')+'</td>'+
                                '</tr>';
                                if(ordertype ==3){
                                    Str+='<tr>'+
                                    '<td>second_name</td>'+
                                    '<td style="width:90%;">'+(newItem.second_name != undefined ? newItem.second_name : '')+'</td>'+
                                    '</tr>'+
                                    '<tr>'+
                                    '<td>third_name</td>'+
                                    '<td style="width:90%;">'+(newItem.third_name != undefined ? newItem.third_name : '')+'</td>'+
                                    '</tr>'+
                                    '<tr>'+
                                    '<tr>'+
                                    '<td>zsl</td>'+
                                    '<td style="width:90%;">'+(newItem.zsl != undefined ? newItem.zsl : '')+'</td>'+
                                    '</tr>'+
                                    '<tr>'+
                                    '<tr>'+
                                    '<td>four_name</td>'+
                                    '<td style="width:90%;">'+(newItem.four_name != undefined ? newItem.four_name : '')+'</td>'+
                                    '</tr>'+
                                    '<tr>';

                                }else{
                                    Str+='<tr>'+
                                    '<td>index_type</td>'+
                                    '<td style="width:90%;">'+(newItem.index_type != undefined ? newItem.index_type : '')+'</td>'+
                                    '</tr>'+
                                    '<tr>'+
                                    '<td>coatiing_name</td>'+
                                    '<td style="width:90%;">'+(newItem.coatiing_name !=undefined ? newItem.coatiing_name : '')+'</td>'+
                                    '</tr>';
                                }
                               Str+='</table>'+
                                '</div>'+
                                '</div>'+
                                '<div class="col-xs-6 col-md-7" style="float:right;">'+
                                '<table id="caigou-table" class="handle_table2">'+
                                '<tr>'+
                                '<td colspan="10" style="text-align: center;">Prescription</td>'+
                                '</tr>'+
                                '<tr>'+
                                '<td>value</td>'+
                                '<td>SPH</td>'+
                                '<td>CYL</td>'+
                                '<td>AXI</td>'+
                                '<td>ADD</td>'+
                                '<td>PD</td>'+
                                '<td>Prism Horizontal</td>'+
                                '<td>Base Direction</td>'+
                                '<td>Prism Vertical</td>'+
                                '<td>Base Direction</td>'+
                                '</tr>'+
                                '<tr>'+
                                '<td>Right(OD)</td>'+
                                '<td>'+(newItem.od_sph !=undefined ? newItem.od_sph : '')+'</td>'+
                                '<td>'+(newItem.od_cyl  != undefined ? newItem.od_cyl : "")+'</td>'+
                                '<td>'+(newItem.od_axis != undefined ? newItem.od_axis : "")+'</td>';
                                if(ordertype<3){
                                    if(newItem.total_add){
                                        Str+= '<td rowspan="2">'+(newItem.total_add  != undefined ? newItem.total_add : "")+'</td>';
                                    }else{
                                        Str+='<td>'+(newItem.os_add  != undefined ? newItem.os_add : "")+'</td>';
                                    }
                                }else{
                                    if(newItem.prescription_type == 'Reading Glasses' && newItem.os_add>0 && newItem.od_add>0){
                                        Str+='<td>'+(newItem.od_add  != undefined ? newItem.od_add : "")+'</td>';
                                    }else{
                                        Str+='<td rowspan="2">'+(newItem.total_add  != undefined ? newItem.total_add : "")+'</td>';
                                    }
                                }

                                if(newItem.pdcheck == 'on'){
                                    Str+= '<td>'+(newItem.pd_r  != undefined ? newItem.pd_r : "")+'</td>';
                                }else{
                                    Str+='<td rowspan="2">'+(newItem.pd  != undefined ? newItem.pd : "")+'</td>';
                                }
                                Str+= '<td>'+(newItem.od_pv != undefined ? newItem.od_pv: "")+'</td>'+
                                '<td>'+(newItem.od_bd != undefined ? newItem.od_bd:"")+'</td>'+
                                '<td>'+(newItem.od_pv_r != undefined ? newItem.od_pv_r:"")+'</td>'+
                                '<td>'+(newItem.od_bd_r != undefined ? newItem.od_bd_r:"")+'</td>'+
                                '</tr>'+
                                '<tr>'+
                                '<td>Left(OS)</td>'+
                                '<td>'+(newItem.os_sph != undefined ? newItem.os_sph : "")+'</td>'+
                                '<td>'+(newItem.os_cyl != undefined ? newItem.os_cyl :"")+'</td>'+
                                '<td>'+(newItem.os_axis != undefined ? newItem.os_axis :"")+'</td>';
                                if(ordertype<3){
                                    if(!newItem.total_add){
                                        Str+='<td>'+(newItem.od_add  != undefined ? newItem.od_add : "")+'</td>';
                                    }
                                }else{
                                    if(newItem.prescription_type == 'Reading Glasses' && newItem.os_add>0 && newItem.od_add>0){
                                        Str+='<td>'+(newItem.os_add  != undefined ? newItem.os_add : "")+'</td>';
                                    }
                                }

                                if(newItem.pdcheck == 'on'){
                                    Str+= '<td>'+(newItem.pd_l  != undefined ? newItem.pd_l : "")+'</td>';
                                }
                                Str+='<td>'+(newItem.os_pv != undefined ? newItem.os_pv : "")+'</td>'+
                                '<td>'+(newItem.os_bd != undefined ? newItem.os_bd : "")+'</td>'+
                                '<td>'+(newItem.os_pv_r!= undefined ? newItem.os_pv_r : "")+'</td>'+
                                '<td>'+(newItem.os_bd_r!= undefined ? newItem.os_bd_r : "")+'</td>'+
                                '</tr>'+
                                '</table>'+
                                '</div>'+
                                '</div>';
                                return Str;
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
            }
        },
        detail:function(){
            Form.api.bindevent($("form[role=form]"));
        },
        handle_task:function(){
            Form.api.bindevent($("form[role=form]"));
            $(document).on('click', '.button_complete', function () {
                $('#c-task_status').val(2);
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
        }
    };
    return Controller;
});