define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {

    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
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

            // 初始化表格
            table.bootstrapTable({
                url: $.fn.bootstrapTable.defaults.extend.index_url,
                pk: 'id',
                sortName: 'id',
                columns: [
                    [
                        {checkbox: true},
                        {field: 'id', title: __('Id')},
                        {field: 'task_number', title: __('Task_number')},
                        {field: 'order_platform', title: __('Order_platform')},
                        {field: 'order_number', title: __('Order_number')},
                        {field: 'order_status', title: __('Order_status')},
                        {field: 'dept_id', title: __('Dept_id')},
                        {field: 'rep_id', title: __('Rep_id')},
                        {field: 'prty_id', title: __('Prty_id')},
                        {field: 'problem_id', title: __('Problem_id')},
                        {field: 'createtime', title: __('Createtime'), operate:'RANGE', addclass:'datetimerange', formatter: Table.api.formatter.datetime},
                        {field: 'operate', title: __('Operate'), table: table, events: Table.api.events.operate, formatter: Table.api.formatter.operate},
                        {field:'Item_info',title:__('Item_info')},
                        {field:'Customer_name',title:__('Customer_name')},
                        {field:'Customer_email',title:__('Customer_email')},
                        {field:'Item_name',title:__('Item_name')},
                        {field:'Item_sku',title:__('Item_sku')},
                        {field:'Item_qty_ordered',title:__('Item_qty_ordered')},
                        {field:'Recipe_type',title:__('Recipe_type')},
                        {field:'Lens_type',title:__('Lens_type')},
                        {field:'Coating_film_type',title:__('Coating_film_type')},
                    ]
                ]
            });

            // 为表格绑定事件
            Table.api.bindevent(table);
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
                //查询订单详情并生成任务单号
                $(document).on('blur','#c-order_number',function(){
                    var ordertype = $('#c-order_platform').val();
                    var order_number = $('#c-order_number').val();
                    if(ordertype<=0){
                        alert('请选择正确的平台');
                        return false;
                    }
                    Backend.api.ajax({
                        url:'saleaftermanage/sale_after_task/ajax',
                        data:{ordertype:ordertype,order_number:order_number}
                    }, function(data, ret){
                        //成功的回调
                        //alert(ret);
                        //清除html商品数据
                        $(".item_info").empty();
                        $('#c-order_status').val(ret.data.status);
                        $('#c-customer_name').val(ret.data.customer_firstname+" "+ ret.data.customer_lastname);
                        $('#c-customer_email').val(ret.data.customer_email);
                        var item = ret.data.item;
                        for(var j = 0,len = item.length; j < len; j++){
                            console.log(item[j]);
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
                                    '<input  id="c-item_name" data-rule="required" class="form-control" name="row[item_name]" type="text" value="'+ newItem.name+'">'+
                                    '</div>'+
                                    '</div>'+
                                    '</div>'+
                                    '<div class="panel-body">'+
                                    '<div class="panel-title">'+
                                    '<label class="control-label col-xs-12 col-sm-3">SKU:</label>'+
                                    '<div class="col-xs-12 col-sm-8">'+
                                    '<input  id="c-item_sku" data-rule="required" class="form-control" name="row[item_sku]" type="text" value="'+newItem.sku+'">'+
                                    '</div>'+
                                    '</div>'+
                                    '</div>'+
                                    '<div class="panel-body">'+
                                    '<div class="panel-title">'+
                                    '<label class="control-label col-xs-12 col-sm-3">数量:</label>'+
                                    '<div class="col-xs-12 col-sm-8">'+
                                    '<input  id="c-item_qty_ordered" data-rule="required" class="form-control" name="row[item_qty_ordered]" type="text" value="'+newItem.qty_ordered+'">'+
                                    '</div>'+
                                    '</div>'+
                                    '</div>'+
                                    '<div class="panel-body">'+
                                    '<div class="panel-title">'+
                                    '<label class="control-label col-xs-12 col-sm-3">处方类型:</label>'+
                                    '<div class="col-xs-12 col-sm-8">'+
                                    '<input  id="c-recipe_type" data-rule="required" class="form-control" name="row[recipe_type]" type="text" value="">'+
                                    '</div>'+
                                    '</div>'+
                                    '</div>'+
                                    '<div class="panel-body">'+
                                    '<div class="panel-title">'+
                                    '<label class="control-label col-xs-12 col-sm-3">镜片类型:</label>'+
                                    '<div class="col-xs-12 col-sm-8">'+
                                    '<input  id="c-lens_type" data-rule="required" class="form-control" name="row[lens_type]" type="text" value="">'+
                                    '</div>'+
                                    '</div>'+
                                    '</div>'+
                                    '<div class="panel-body">'+
                                    '<div class="panel-title">'+
                                    '<label class="control-label col-xs-12 col-sm-3">镀膜类型:</label>'+
                                    '<div class="col-xs-12 col-sm-8">'+
                                    '<input  id="c-coating_film_type" data-rule="required" class="form-control" name="row[coating_film_type]" type="text" value="">'+
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
                                    '<td><input id="c-right_SPH" class="form-control" name="row[purchase_remark]" type="text"></td>'+
                                    '<td><input id="c-right_CYL" class="form-control" name="row[purchase_remark]" type="text"></td>'+
                                    '<td><input id="c-right_AXI" class="form-control" name="row[purchase_remark]" type="text"></td>'+
                                    '<td><input id="c-right_ADD" class="form-control" name="row[purchase_remark]" type="text"></td>'+
                                    '<td><input id="c-right_PD" class="form-control" name="row[purchase_remark]" type="text"></td>'+
                                    '<td><input id="c-right_Prism_Horizontal" class="form-control" name="row[purchase_remark]" type="text"></td>'+
                                    '<td><input id="c-right_" class="form-control" name="row[purchase_remark]" type="text"></td>'+
                                    '<td><input id="c-purchase_remark" class="form-control" name="row[purchase_remark]" type="text"></td>'+
                                    '<td><input id="c-purchase_remark" class="form-control" name="row[purchase_remark]" type="text"></td>'+
                                    '</tr>'+
                                    '<tr>'+
                                    '<td style="text-align: center">Left(OS)</td>'+
                                    '<td><input id="c-purchase_remark" class="form-control" name="row[purchase_remark]" type="text"></td>'+
                                    '<td><input id="c-purchase_remark" class="form-control" name="row[purchase_remark]" type="text"></td>'+
                                    '<td><input id="c-purchase_remark" class="form-control" name="row[purchase_remark]" type="text"></td>'+
                                    '<td><input id="c-purchase_remark" class="form-control" name="row[purchase_remark]" type="text"></td>'+
                                    '<td><input id="c-purchase_remark" class="form-control" name="row[purchase_remark]" type="text"></td>'+
                                    '<td><input id="c-purchase_remark" class="form-control" name="row[purchase_remark]" type="text"></td>'+
                                    '<td><input id="c-purchase_remark" class="form-control" name="row[purchase_remark]" type="text"></td>'+
                                    '<td><input id="c-purchase_remark" class="form-control" name="row[purchase_remark]" type="text"></td>'+
                                    '<td><input id="c-purchase_remark" class="form-control" name="row[purchase_remark]" type="text"></td>'+
                                    '</tr>'+
                                    '</table>'+
                                    '</div>'+
                                    '</div>'+
                                    '</div>'+
                                    '</div>'+
                                    '</div>';
                            });
                        };
                        console.log(ret);
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
        }
    };
    return Controller;
});