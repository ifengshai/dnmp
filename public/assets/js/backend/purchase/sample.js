define(['jquery', 'bootstrap', 'backend', 'table', 'form','upload'], function ($, undefined, Backend, Table, Form,Upload) {
    var Controller = {
        sample_index: function () {
            // 初始化表格参数配置
            Table.api.init({
                searchFormVisible: true,
                extend: {
                    index_url: 'purchase/sample/sample_index' + location.search,
                    add_url: 'purchase/sample/sample_import_xls',
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
                        {field: 'lend_num', title: __('借出数量'),operate:false},
                    ]
                ]
            });
            // 导入按钮事件
            Upload.api.plupload($('.btn-import'), function (data, ret) {
                Fast.api.ajax({
                    url: 'purchase/sample/sample_import_xls',
                    data: { file: data.url },
                }, function (data, ret) {
                    layer.msg('导入成功！！', { time: 3000, icon: 6 }, function () {
                        location.reload();
                    });

                });
            });

            // 为表格绑定事件
            Table.api.bindevent(table);
        },
        sample_location_index: function () {
            // 初始化表格参数配置
            Table.api.init({
                searchFormVisible: true,
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
                searchFormVisible: true,
                extend: {
                    index_url: 'purchase/sample/sample_workorder_index' + location.search,
                    add_url: 'purchase/sample/sample_workorder_add',
                    edit_url: 'purchase/sample/sample_workorder_edit',
                    del_url: 'purchase/sample/sample_workorder_del',
                    multi_url: 'purchase/sample/sample_workorder_multi',
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
                                    name: 'edit',
                                    text: __('编辑'),
                                    title: __('编辑'),
                                    classname: 'btn btn-xs btn-success btn-dialog',
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
                                    name: 'check',
                                    text: __('审核'),
                                    title: __('审核'),
                                    classname: 'btn btn-xs btn-success btn-dialog',
                                    url: 'purchase/sample/sample_workorder_detail',
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
                                    classname: 'btn btn-xs btn-danger btn-ajax',
                                    url: 'purchase/sample/sample_workorder_del',
                                    success: function (data, ret) {
                                        table.bootstrapTable('refresh');
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
                                    classname: 'btn btn-xs btn-danger btn-ajax',
                                    url: 'purchase/sample/sample_workorder_cancel',
                                    success: function (data, ret) {
                                        table.bootstrapTable('refresh');
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
            /**
             * 批量审核通过
             */
            $(document).on('click', '.btn-check-pass', function () {
                var ids = Table.api.selectedids(table);
                Backend.api.ajax({
                    url: Config.moduleurl + '/purchase/sample/sample_workorder_setstatus',
                    data: { ids: ids, status: 3 }
                }, function (data, ret) {
                    table.bootstrapTable('refresh');
                });
            })
            /**
             * 批量审核拒绝
             */
            $(document).on('click', '.btn-check-refuse', function () {
                var ids = Table.api.selectedids(table);
                Backend.api.ajax({
                    url: Config.moduleurl + '/purchase/sample/sample_workorder_setstatus',
                    data: { ids: ids, status: 4 }
                }, function (data, ret) {
                    table.bootstrapTable('refresh');
                });
            })
            /**
             * 批量审核取消
             */
            $(document).on('click', '.btn-check-cancel', function () {
                var ids = Table.api.selectedids(table);
                Backend.api.ajax({
                    url: Config.moduleurl + '/purchase/sample/sample_workorder_setstatus',
                    data: { ids: ids, status: 5 }
                }, function (data, ret) {
                    table.bootstrapTable('refresh');
                });
            })
        },
        sample_workorder_out_index: function () {
            // 初始化表格参数配置
            Table.api.init({
                searchFormVisible: true,
                extend: {
                    index_url: 'purchase/sample/sample_workorder_out_index' + location.search,
                    add_url: 'purchase/sample/sample_workorder_out_add',
                    edit_url: 'purchase/sample/sample_workorder_out_edit',
                    del_url: 'purchase/sample/sample_workorder_out_del',
                    multi_url: 'purchase/sample/sample_workorder_out_multi',
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
                                    name: 'edit',
                                    text: __('编辑'),
                                    title: __('编辑'),
                                    classname: 'btn btn-xs btn-success btn-dialog',
                                    url: 'purchase/sample/sample_workorder_out_edit',
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
                                    name: 'check',
                                    text: __('审核'),
                                    title: __('审核'),
                                    classname: 'btn btn-xs btn-success btn-dialog',
                                    url: 'purchase/sample/sample_workorder_out_detail',
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
                                    classname: 'btn btn-xs btn-danger btn-ajax',
                                    url: 'purchase/sample/sample_workorder_out_del',
                                    success: function (data, ret) {
                                        table.bootstrapTable('refresh');
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
                                    classname: 'btn btn-xs btn-danger btn-ajax',
                                    url: 'purchase/sample/sample_workorder_out_cancel',
                                    success: function (data, ret) {
                                        table.bootstrapTable('refresh');
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
            /**
             * 批量审核通过
             */
            $(document).on('click', '.btn-check-pass', function () {
                var ids = Table.api.selectedids(table);
                Backend.api.ajax({
                    url: Config.moduleurl + '/purchase/sample/sample_workorder_out_setstatus',
                    data: { ids: ids, status: 3 }
                }, function (data, ret) {
                    table.bootstrapTable('refresh');
                });
            })
            /**
             * 批量审核拒绝
             */
            $(document).on('click', '.btn-check-refuse', function () {
                var ids = Table.api.selectedids(table);
                Backend.api.ajax({
                    url: Config.moduleurl + '/purchase/sample/sample_workorder_out_setstatus',
                    data: { ids: ids, status: 4 }
                }, function (data, ret) {
                    table.bootstrapTable('refresh');
                });
            })
            /**
             * 批量审核取消
             */
            $(document).on('click', '.btn-check-cancel', function () {
                var ids = Table.api.selectedids(table);
                Backend.api.ajax({
                    url: Config.moduleurl + '/purchase/sample/sample_workorder_out_setstatus',
                    data: { ids: ids, status: 5 }
                }, function (data, ret) {
                    table.bootstrapTable('refresh');
                });
            })
        },
        sample_lendlog_index: function () {
            // 初始化表格参数配置
            Table.api.init({
                searchFormVisible: true,
                extend: {
                    index_url: 'purchase/sample/sample_lendlog_index' + location.search,
                    add_url: 'purchase/sample/sample_lendlog_add',
                    edit_url: 'purchase/sample/sample_lendlog_edit',
                    del_url: 'purchase/sample/sample_lendlog_del',
                    multi_url: 'purchase/sample/sample_lendlog_multi',
                    table: 'purchase_sample_lendlog',
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
                        {field: 'id', title: __('借出单号'),operate:false},
                        {field: 'status', title: __('状态'),searchList: {"1": __('待审核'), "2": __('已借出'), "3": __('已拒绝'), "4": __('已归还'), "5": __('已取消')}},
                        {field: 'create_user', title: __('申请人')},
                        {field: 'createtime', title: __('申请时间'), operate:'RANGE', addclass:'datetimerange', formatter: Table.api.formatter.datetime},
                        {
                            field: 'buttons',
                            width: "120px",
                            operate:false,
                            title: __('操作'),
                            table: table,
                            events: Table.api.events.operate,
                            buttons: [
                                {
                                    name: 'detail',
                                    text: __('详情'),
                                    title: __('详情'),
                                    classname: 'btn btn-xs btn-primary btn-dialog',
                                    url: 'purchase/sample/sample_lendlog_detail',
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
                                    classname: 'btn btn-xs btn-success btn-dialog',
                                    url: 'purchase/sample/sample_lendlog_edit',
                                    callback: function (data) {
                                    },
                                    visible: function(row){
                                        if(row.status_id == 1){
                                            return true;
                                        }else{
                                            return false;
                                        }
                                    }
                                },
                                {
                                    name: 'check_pass',
                                    text: __('审核通过'),
                                    title: __('审核通过'),
                                    classname: 'btn btn-xs btn-success btn-ajax',
                                    url: 'purchase/sample/sample_lendlog_check/status/2',
                                    success: function (data, ret) {
                                        table.bootstrapTable('refresh');
                                    },
                                    visible: function(row){
                                        if(row.status_id == 1){
                                            return true;
                                        }else{
                                            return false;
                                        } 
                                    }
                                },
                                {
                                    name: 'check_refuse',
                                    text: __('审核拒绝'),
                                    title: __('审核拒绝'),
                                    classname: 'btn btn-xs btn-danger btn-ajax',
                                    url: 'purchase/sample/sample_lendlog_check/status/3',
                                    success: function (data, ret) {
                                        table.bootstrapTable('refresh');
                                    },
                                    visible: function(row){
                                        if(row.status_id == 1){
                                            return true;
                                        }else{
                                            return false;
                                        }
                                    }
                                },
                                {
                                    name: 'check_back',
                                    text: __('归还'),
                                    title: __('归还'),
                                    classname: 'btn btn-xs btn-danger btn-ajax',
                                    url: 'purchase/sample/sample_lendlog_check/status/4',
                                    success: function (data, ret) {
                                        table.bootstrapTable('refresh');
                                    },
                                    visible: function(row){
                                        if(row.status_id == 2){
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
            /**
             * 批量审核通过
             */
            $(document).on('click', '.btn-check-pass', function () {
                var ids = Table.api.selectedids(table);
                Backend.api.ajax({
                    url: Config.moduleurl + '/purchase/sample/sample_lendlog_setstatus',
                    data: { ids: ids, status: 2 }
                }, function (data, ret) {
                    table.bootstrapTable('refresh');
                });
            })
            /**
             * 批量审核拒绝
             */
            $(document).on('click', '.btn-check-refuse', function () {
                var ids = Table.api.selectedids(table);
                Backend.api.ajax({
                    url: Config.moduleurl + '/purchase/sample/sample_lendlog_setstatus',
                    data: { ids: ids, status: 3 }
                }, function (data, ret) {
                    table.bootstrapTable('refresh');
                });
            })
        },
        sample_location_add: function () {
            Controller.api.bindevent();
        },
        sample_workorder_add: function () {
            Controller.api.bindevent();
            $(document).on('click', "#add_entry_product", function () {
                var number = $("#product_data > tr").length;
                var location_option = $("#select_location").html();
                var add_str = '<tr role="row" class="odd del_'+number+'"><td><input type="text" name="row[goods]['+number+'][sku]" value="" class="form-control"></td><td><input type="text" class="form-control" name="row[goods]['+number+'][stock]" value=""></td><td><select name="row[goods]['+number+'][location_id]" class="form-control supplier" required ><option value="">请选择</option>'+location_option+'</select></td><td id="del"><a href="javascript:;" onclick=del_add_tr('+number+')> 删除 </a></td></tr>';
                $('#product_data').append(add_str);
            });
        },
        sample_workorder_out_add: function () {
            Controller.api.bindevent();
            $("body").on('change', ".sku_arr", function () {
                $(this).parents('tr').find(".location").html($(this).find("option:selected").attr('data-id'))
            });
            $(document).on('click', "#add_entry_product", function () {
                var number = $("#product_data > tr").length;
                var sku_arr = $("#sku_info").html();
                var add_str = '<tr role="row" class="odd del_'+number+'"><td><select name="row[goods]['+number+'][sku]" id="sku" class="form-control sku_arr" data-live-search="true">'+sku_arr+'</select></td><td><input type="text" class="form-control" name="row[goods]['+number+'][stock]" id="sku_'+number+'"></td><td class="location"></td><td id="del"><a href="javascript:;" onclick=del_add_tr('+number+')> 删除 </a></td></tr>';
                $('#product_data').append(add_str);
            });
        },
        sample_lendlog_add: function () {
            Controller.api.bindevent();

            $("body").on('change', ".sku_arr", function () {
                $(this).parents('tr').find(".location").html($(this).find("option:selected").attr('data-id'))
            });
            $(document).on('click', "#add_entry_product", function () {
                var number = $("#product_data > tr").length;
                var sku_arr = $("#sku_info").html();
                var add_str = '<tr role="row" class="odd del_'+number+'"><td><select name="row[goods]['+number+'][sku]" id="sku" class="form-control sku_arr" data-live-search="true">'+sku_arr+'</select></td><td><input type="text" class="form-control" name="row[goods]['+number+'][lend_num]" id="sku_'+number+'"></td><td class="location"></td><td id="del"><a href="javascript:;" onclick=del_add_tr('+number+')> 删除 </a></td></tr>';
                $('#product_data').append(add_str);
            });
        },
        sample_location_edit: function () {
            Controller.api.bindevent();
        },
        sample_workorder_edit: function () {
            Controller.api.bindevent();
            $(document).on('click', "#add_product", function () {
                var number = $("#product_data > tr").length;
                var location_option = $("#select_location").html();
                var add_str = '<tr role="row" class="odd del_'+number+'"><td><input type="text" name="row[goods]['+number+'][sku]" value="" class="form-control"></td><td><input type="text" class="form-control" name="row[goods]['+number+'][stock]" value=""></td><td><select name="row[goods]['+number+'][location_id]" class="form-control supplier" required ><option value="">请选择</option>'+location_option+'</select></td><td id="del"><a href="javascript:;" onclick=del_add_tr('+number+')> 删除 </a></td></tr>';
                $('#product_data').append(add_str);
            });
        },
        sample_workorder_out_edit: function () {
            Controller.api.bindevent();
            $("body").on('change', ".sku_arr", function () {
                $(this).parents('tr').find(".location").html($(this).find("option:selected").attr('data-id'))
            });
            $(document).on('click', "#add_product", function () {
                var number = $("#product_data > tr").length;
                var sku_arr = $("#sku_info").html();
                console.log(sku_arr);
                var add_str = '<tr role="row" class="odd del_'+number+'"><td><select name="row[goods]['+number+'][sku]" id="sku" class="form-control sku_arr" data-live-search="true">'+sku_arr+'</select></td><td><input type="text" class="form-control" name="row[goods]['+number+'][stock]" id="sku_'+number+'"></td><td class="location"></td><td id="del"><a href="javascript:;" onclick=del_add_tr('+number+')> 删除 </a></td></tr>';
                $('#product_data').append(add_str);
            });
        },
        sample_lendlog_edit: function () {
            Controller.api.bindevent();
            $("body").on('change', ".sku_arr", function () {
                $(this).parents('tr').find(".location").html($(this).find("option:selected").attr('data-id'))
            });
            $(document).on('click', "#add_entry_product", function () {
                var number = $("#product_data > tr").length;
                var sku_arr = $("#sku_info").html();
                console.log(sku_arr);
                var add_str = '<tr role="row" class="odd del_'+number+'"><td><select name="row[goods]['+number+'][sku]" id="sku" class="form-control sku_arr" data-live-search="true">'+sku_arr+'</select></td><td><input type="text" class="form-control" name="row[goods]['+number+'][lend_num]" id="sku_'+number+'"></td><td class="location"></td><td id="del"><a href="javascript:;" onclick=del_add_tr('+number+')> 删除 </a></td></tr>';
                $('#product_data').append(add_str);
            });
        },
        sample_workorder_detail: function () {
            Controller.api.bindevent();
        },
        sample_workorder_out_detail: function () {
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
function select_sku(key){
    
}
/**
 * 入库删除
 * @param {key} key 
 */
function del_add_tr(key){
    $(".del_"+key).remove();
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
 * 入库/出库审核通过
 */
function workorder_check_pass(){
    $("#workorder_status").val(3);
}
/**
 * 入库/出库审核拒绝
 */
function workorder_check_refuse(){
    $("#workorder_status").val(4);
}
/**
 * 入库/出库审核取消
 */
function workorder_check_cancel(){
    $("#workorder_status").val(5);
}
