define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {

    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                extend: {
                    index_url: 'saleaftermanage/order_reissue/index' + location.search,
                    add_url: 'saleaftermanage/order_reissue/add',
                    edit_url: 'saleaftermanage/order_reissue/edit',
                    del_url: 'saleaftermanage/order_reissue/del',
                    multi_url: 'saleaftermanage/order_reissue/multi',
                    table: 'order_reissue',
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
                        {field: 'id', title: __('Id')},
                        {field: 'original_increment_id', title: __('Original_increment_id')},
                        {field: 'order_platform', title: __('Order_platform')},
                        {field: 'issue_id', title: __('Issue_id')},
                        {field: 'reissue_type', title: __('Reissue_type')},
                        {field: 'reissue_status', title: __('Reissue_status')},
                        {field: 'customer_email', title: __('Customer_email')},
                        {field: 'customer_name', title: __('Customer_name')},
                        {field: 'final_loss_amount', title: __('Final_loss_amount'), operate:'BETWEEN'},
                        {field: 'final_loss_amount_created_time', title: __('Final_loss_amount_created_time'), operate:'RANGE', addclass:'datetimerange'},
                        {field: 'final_loss_remark', title: __('Final_loss_remark')},
                        {field: 'custom_print_label', title: __('Custom_print_label')},
                        {field: 'match_frame', title: __('Match_frame')},
                        {field: 'match_frame_created_time', title: __('Match_frame_created_time'), operate:'RANGE', addclass:'datetimerange'},
                        {field: 'match_lens', title: __('Match_lens')},
                        {field: 'match_lens_created_at', title: __('Match_lens_created_at'), operate:'RANGE', addclass:'datetimerange'},
                        {field: 'send_factory', title: __('Send_factory')},
                        {field: 'send_factory_created_at', title: __('Send_factory_created_at'), operate:'RANGE', addclass:'datetimerange'},
                        {field: 'delivery', title: __('Delivery')},
                        {field: 'delivery_created_at', title: __('Delivery_created_at'), operate:'RANGE', addclass:'datetimerange'},
                        {field: 'express_delivery', title: __('Express_delivery')},
                        {field: 'express_delivery_created_at', title: __('Express_delivery_created_at'), operate:'RANGE', addclass:'datetimerange'},
                        {field: 'is_visable', title: __('Is_visable')},
                        {field: 'created_person', title: __('Created_person')},
                        {field: 'created_time', title: __('Created_time'), operate:'RANGE', addclass:'datetimerange'},
                        {field: 'check_person_id', title: __('Check_person_id')},
                        {field: 'check_time', title: __('Check_time'), operate:'RANGE', addclass:'datetimerange'},
                        {field: 'country', title: __('Country')},
                        {field: 'region', title: __('Region')},
                        {field: 'city', title: __('City')},
                        {field: 'street', title: __('Street')},
                        {field: 'telephone', title: __('Telephone')},
                        {field: 'postcode', title: __('Postcode')},
                        {field: 'address_remark', title: __('Address_remark')},
                        {field: 'operate', title: __('Operate'), table: table, events: Table.api.events.operate, formatter: Table.api.formatter.operate}
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
                //Form.api.bindevent($("form[role=form]"));
                $(document).on('blur','#c-original_increment_id',function(){
                    var ordertype = $('#c-order_platform').val();
                    var order_number = $('#c-original_increment_id').val();
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
                        if(ret.data.store_id>=2){
                            $('#c-order_source').val(2);
                        }else{
                            $('#c-order_source').val(1);
                        }
                        var item = ret.data.item;
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
                                    '<input  id="c-item_qty_ordered"  class="form-control"  type="text" value="'+newItem.qty_ordered+'">'+
                                    '</div>'+
                                    '</div>'+
                                    '</div>'+
                                    '<div class="panel-body">'+
                                    '<div class="panel-title">'+
                                    '<label class="control-label col-xs-12 col-sm-3">处方类型:</label>'+
                                    '<div class="col-xs-12 col-sm-8">'+
                                    '<input  id="c-recipe_type"  class="form-control" type="text" value="'+newItem.prescription_type+'">'+
                                    '</div>'+
                                    '</div>'+
                                    '</div>'+
                                    '<div class="panel-body">'+
                                    '<div class="panel-title">'+
                                    '<label class="control-label col-xs-12 col-sm-3">镜片类型:</label>'+
                                    '<div class="col-xs-12 col-sm-8">'+
                                    '<input  id="c-lens_type"  class="form-control"  type="text" value="'+newItem.index_type+'">'+
                                    '</div>'+
                                    '</div>'+
                                    '</div>'+
                                    '<div class="panel-body">'+
                                    '<div class="panel-title">'+
                                    '<label class="control-label col-xs-12 col-sm-3">镀膜类型:</label>'+
                                    '<div class="col-xs-12 col-sm-8">'+
                                    '<input  id="c-coating_film_type"  class="form-control"  type="text" value="'+newItem.coatiing_name+'">'+
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
                                    '<td><input id="c-right_SPH" class="form-control"  type="text" value="'+newItem.od_sph+'"></td>'+
                                    '<td><input id="c-right_CYL" class="form-control"  type="text" value="'+newItem.od_cyl+'"></td>'+
                                    '<td><input id="c-right_AXI" class="form-control"  type="text" value="'+newItem.od_axis+'"></td>'+
                                    '<td><input id="c-right_ADD" class="form-control"  type="text" value="'+newItem.od_add+'"></td>'+
                                    '<td><input id="c-right_PD" class="form-control"  type="text" value="'+newItem.pd_r+'"></td>'+
                                    '<td><input id="c-right_Prism_Horizontal" class="form-control"  type="text" value="'+newItem.od_pv+'"></td>'+
                                    '<td><input id="c-right_" class="form-control"  type="text" value="'+newItem.od_bd+'"></td>'+
                                    '<td><input id="c-purchase_remark" class="form-control"  type="text" value="'+newItem.od_pv_r+'"></td>'+
                                    '<td><input id="c-purchase_remark" class="form-control"  type="text" value="'+newItem.od_bd_r+'"></td>'+
                                    '</tr>'+
                                    '<tr>'+
                                    '<td style="text-align: center">Left(OS)</td>'+
                                    '<td><input id="c-left_SPH" class="form-control"  type="text" value="'+newItem.os_sph+'"></td>'+
                                    '<td><input id="c-left_CYL" class="form-control"  type="text" value="'+newItem.os_cyl+'"></td>'+
                                    '<td><input id="c-purchase_remark" class="form-control"  type="text" value="'+newItem.os_axis+'"></td>'+
                                    '<td><input id="c-purchase_remark" class="form-control"  type="text" value="'+newItem.os_add+'"></td>'+
                                    '<td><input id="c-purchase_remark" class="form-control"  type="text" value="'+newItem.pd_l+'"></td>'+
                                    '<td><input id="c-purchase_remark" class="form-control"  type="text" value="'+newItem.os_pv+'"></td>'+
                                    '<td><input id="c-purchase_remark" class="form-control"  type="text" value="'+newItem.os_bd+'"></td>'+
                                    '<td><input id="c-purchase_remark" class="form-control"  type="text" value="'+newItem.os_pv_r+'"></td>'+
                                    '<td><input id="c-purchase_remark" class="form-control"  type="text" value="'+newItem.os_bd_r+'"></td>'+
                                    '</tr>'+
                                    '</table>'+
                                    '</div>'+
                                    '</div>'+
                                    '</div>'+
                                    '</div>'+
                                    '</div>';
                            });
                        };
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
            },

        }
    };
    return Controller;
});