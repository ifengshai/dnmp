define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {
    var product_arrlist = [];
    var product_editarrlist = [];
    var Controller = {
        sample_index: function () {
            // 初始化表格参数配置
            Table.api.init({
                extend: {
                    index_url: 'purchase/sample/sample_index' + location.search,
                    add_url: 'purchase/sample/sample_add',
                    edit_url: 'purchase/sample/sample_edit',
                    del_url: 'purchase/sample/sample_del',
                    multi_url: 'purchase/sample/multi',
                    table: 'purchase_sample',
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
                        {field: 'id', title: __('序号'),operate:false},
                        {field: 'sku', title: __('SKU')},
                        {field: 'product_name', title: __('商品名称'),operate:false},
                        {field: 'location', title: __('库位号'),operate:false},
                        {field: 'stock', title: __('留样库存'),operate:false},
                        {field: 'is_lend', title: __('是否借出'),searchList: {"1": __('是'), "0": __('否')}},
                        {field: 'is_lend_num', title: __('借出数量'),operate:false},
                    ]
                ]
            });

            // 为表格绑定事件
            Table.api.bindevent(table);
        },
        sample_location_index: function () {
            // 初始化表格参数配置
            Table.api.init({
                extend: {
                    index_url: 'purchase/sample/sample_location_index' + location.search,
                    add_url: 'purchase/sample/sample_location_add',
                    edit_url: 'purchase/sample/sample_location_edit',
                    del_url: 'purchase/sample/sample_location_del',
                    multi_url: 'purchase/sample/multi',
                    table: 'purchase_sample_location',
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
                        {field: 'id', title: __('库位ID')},
                        {field: 'location', title: __('库位号')},
                        {field: 'createtime', title: __('创建时间'), operate:'RANGE', addclass:'datetimerange', formatter: Table.api.formatter.datetime},
                        {field: 'create_user', title: __('创建人')},
                        {field: 'operate', title: __('操作'), table: table, events: Table.api.events.operate, formatter: Table.api.formatter.operate}
                    ]
                ]
            });

            // 为表格绑定事件
            Table.api.bindevent(table);
        },
        sample_workorder_index: function () {
            // 初始化表格参数配置
            Table.api.init({
                extend: {
                    index_url: 'purchase/sample/sample_workorder_index' + location.search,
                    add_url: 'purchase/sample/sample_workorder_add',
                    edit_url: 'purchase/sample/sample_workorder_edit',
                    del_url: 'purchase/sample/sample_workorder_del',
                    multi_url: 'purchase/sample/multi',
                    table: 'purchase_sample_workorder',
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
                        {field: 'id', title: __('库位ID'),operate:false},
                        {field: 'location_number', title: __('库位号')},
                        {field: 'status', title: __('状态'),searchList: {"1": __('新建'), "2": __('待审核'), "3": __('已审核'), "4": __('已拒绝'), "5": __('已取消')}},
                        {field: 'create_user', title: __('创建人')},
                        {field: 'createtime', title: __('创建时间'), operate:'RANGE', addclass:'datetimerange', formatter: Table.api.formatter.datetime},
                        {
                            field: 'buttons',
                            width: "120px",
                            operate:false,
                            title: __('操作'),
                            table: table,
                            events: Table.api.events.operate,
                            buttons: [
                                {
                                    name: 'check',
                                    text: __('审核'),
                                    title: __('审核'),
                                    classname: 'btn btn-xs btn-primary btn-dialog',
                                    url: 'purchase/sample/sample_workorder_detail',
                                    callback: function (data) {
                                    },
                                    visible: function(row){
                                        return true;
                                    }
                                },
                                {
                                    name: 'edit',
                                    text: __('编辑'),
                                    title: __('编辑'),
                                    classname: 'btn btn-xs btn-primary btn-dialog',
                                    url: 'purchase/sample/sample_workorder_edit',
                                    callback: function (data) {
                                    },
                                    visible: function(row){
                                        if(row.status_id < 3){
                                            return true;
                                        }else{
                                            return false;
                                        }
                                    }
                                },
                                {
                                    name: 'del',
                                    text: __('删除'),
                                    title: __('删除'),
                                    classname: 'btn btn-xs btn-primary btn-dialog',
                                    url: 'purchase/sample/sample_workorder_del',
                                    callback: function (data) {
                                    },
                                    visible: function(row){
                                        if(row.status_id < 3){
                                            return true;
                                        }else{
                                            return false;
                                        }
                                    }
                                },
                                {
                                    name: 'cancel',
                                    text: __('取消'),
                                    title: __('取消'),
                                    classname: 'btn btn-xs btn-primary btn-dialog',
                                    url: 'purchase/sample/sample_workorder_cancel',
                                    callback: function (data) {
                                    },
                                    visible: function(row){
                                        if(row.status_id < 3){
                                            return true;
                                        }else{
                                            return false;
                                        }
                                    }
                                },
                            ],
                            formatter: Table.api.formatter.buttons
                        },
                    ]
                ]
            });

            // 为表格绑定事件
            Table.api.bindevent(table);
        },
        sample_location_add: function () {
            Controller.api.bindevent();
        },
        sample_workorder_add: function () {
            Controller.api.bindevent();

            $(document).on('click', "#add_entry_product", function () {
                var location_id = $('#location').val();
                if(isNaN(parseInt(location_id))){
                    layer.alert('无效的选择');
                    return false;
                }
                var location = $("#location option:selected").text();

                var sku = $("#sku").val();
                var stock = $("#stock").val();

                product_arrlist.push(sku+'_'+stock+'_'+location_id);
                var product_str = product_arrlist.join(',');
                $("#product_list_data").val(product_str);
                var add_str = '<tr role="row" class="odd del_'+sku+'"><td>'+sku+'</td><td>'+stock+'</td><td>'+location+'</td><td id="del"><a href="javascript:;" onclick=del_add_tr("'+sku+'")> Ｘ </a></td></tr>';
                $('#product_data').append(add_str);
            });
        },
        sample_location_edit: function () {
            Controller.api.bindevent();
        },
        sample_workorder_edit: function () {
            Controller.api.bindevent();
        },
        sample_workorder_detail: function () {
            Controller.api.bindevent();
        },
        api: {
            bindevent: function () {
                Form.api.bindevent($("form[role=form]"));
                $(document).on('click', "#add_product", function () {
                    var location_id = $('#location').val();
                    if(isNaN(parseInt(location_id))){
                        layer.alert('无效的选择');
                        return false;
                    }
                    var location = $("#location option:selected").text();

                    var sku = $("#sku").val();
                    var stock = $("#stock").val();

                    var product_data = $("#product_list_data").val();
                    if(product_data.length == 0){
                        product_data = sku+'_'+stock+'_'+location_id;
                    }else{
                        product_data = product_data+','+sku+'_'+stock+'_'+location_id;
                    }
                    $("#product_list_data").val(product_data);
                    var add_str = '<tr role="row" class="odd del_'+sku+'"><td>'+sku+'</td><td>'+stock+'</td><td>'+location+'</td><td id="del"><a href="javascript:;" onclick=del_add_tr("'+sku+'")> Ｘ </a></td></tr>';
                    $('#product_data').append(add_str);
                });
            }
        }
    };
    return Controller;
});

function del_add_tr(sku,stock,location_id){
    $(".del_"+sku).remove();
    var product_list_str = $("#product_list_data").val();
    var product_list_arr = product_list_str.split(',');
    var str = sku+'_'+stock+'_'+location_id;
    product_list_arr.splice($.inArray(str,product_list_arr),1);
    var product_str = product_list_arr.join(',');
    $("#product_list_data").val(product_str);
}
/**
 * 库位添加保存草稿
 */
function save(){
    $('#status').val(1);
}
/**
 * 库位添加保存审核
 */
function check(){
    $('#status').val(2);
}
/**
 * 入库审核通过
 */
function workorder_check_pass(){
    $("#workorder_status").val(3);
}
/**
 * 入库审核拒绝
 */
function workorder_check_refuse(){
    $("#workorder_status").val(4);
}
/**
 * 入库审核取消
 */
function workorder_check_cancel(){
    $("#workorder_status").val(5);
}
