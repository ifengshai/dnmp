define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {
    var product_arrlist = [];
    var product_editarrlist = [];
    var lend_arrlist = [];
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
                    url: Config.moduleurl + '/purchase/sample/sample_workorder_setStatus',
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
                    url: Config.moduleurl + '/purchase/sample/sample_workorder_setStatus',
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
                    url: Config.moduleurl + '/purchase/sample/sample_workorder_setStatus',
                    data: { ids: ids, status: 5 }
                }, function (data, ret) {
                    table.bootstrapTable('refresh');
                });
            })
        },
        sample_workorder_out_index: function () {
            // 初始化表格参数配置
            Table.api.init({
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
                    url: Config.moduleurl + '/purchase/sample/sample_workorder_out_setStatus',
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
                    url: Config.moduleurl + '/purchase/sample/sample_workorder_out_setStatus',
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
                    url: Config.moduleurl + '/purchase/sample/sample_workorder_out_setStatus',
                    data: { ids: ids, status: 5 }
                }, function (data, ret) {
                    table.bootstrapTable('refresh');
                });
            })
        },
        sample_lendlog_index: function () {
            // 初始化表格参数配置
            Table.api.init({
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
                    url: Config.moduleurl + '/purchase/sample/sample_lendlog_setStatus',
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
                    url: Config.moduleurl + '/purchase/sample/sample_lendlog_setStatus',
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
                var location_id = $('#location').val();
                if(isNaN(parseInt(location_id))){
                    layer.alert('无效的选择');
                    return false;
                }
                var location = $("#location option:selected").text();

                var sku = $("#sku").val();
                var stock = $("#stock").val();
                var str = sku+'_'+stock+'_'+location_id;
                var arr = [],
                    sku_arr = []
                if ($("#product_list_data").val()) {
                    arr = $("#product_list_data").val().split(',')
                    sku_arr = $("#sku_arr").val().split(',')
                    if($.inArray(sku, sku_arr) != -1){
                        layer.alert('sku不能重复');
                        return false;
                    }
                }
                arr.push(str)
                sku_arr.push(sku)
                $("#sku_arr").val(sku_arr.join(','));
                $("#product_list_data").val(arr.join(','));
                var add_str = '<tr role="row" class="odd del_'+sku+'"><td>'+sku+'</td><td>'+stock+'</td><td>'+location+'</td><td id="del"><a href="javascript:;" onclick=del_add_tr("'+sku+'","'+stock+'","'+location_id+'")> Ｘ </a></td></tr>';
                $('#product_data').append(add_str);
            });
        },
        sample_workorder_out_add: function () {
            Controller.api.bindevent();
            $(document).on('change', "#sku", function () {
                var location = $('#sku').val();
                if(location.length == 0){
                    layer.alert('无效的选择');
                    return false;
                }
                $("#location").html(location);
            });
            $(document).on('click', "#add_entry_product", function () {
                var sku = $("#sku option:selected").text();
                var location = $('#sku').val();
                if(location.length == 0){
                    layer.alert('无效的选择');
                    return false;
                }
                $("#location").html(location);

                var arr = [],
                    sku_arr = []
                if($("#product_list_data").val()){
                    arr = $("#product_list_data").val().split(',');
                    sku_arr = $("#sku_arr").val().split(',');
                    if($.inArray(sku, sku_arr) != -1){
                        layer.alert('sku不能重复');
                        return false;
                    }
                }
                sku_arr.push(sku)
                arr.push(sku+'_'+$("#stock").val())
                $("#sku_arr").val(sku_arr.join(','));
                $("#product_list_data").val(arr.join(','));
                var add_str = '<tr role="row" class="odd del_'+sku+'"><td>'+sku+'</td><td>'+$("#stock").val()+'</td><td>'+location+'</td><td id="del"><a href="javascript:;" onclick=del_lend_tr("'+sku+'","'+$("#stock").val()+'")> Ｘ </a></td></tr>';
                $('#product_data').append(add_str);
            });
        },
        sample_lendlog_add: function () {
            Controller.api.bindevent();

            $(document).on('change', "#sku", function () {
                var sku = $("#sku option:selected").text();
                var location = $('#sku').val();
                if(location.length == 0){
                    layer.alert('无效的选择');
                    return false;
                }
                $("#location").html(location);
            });
            $(document).on('click', "#add_entry_product", function () {
                var sku = $("#sku option:selected").text();
                var location = $('#sku').val();
                if(location.length == 0){
                    layer.alert('无效的选择');
                    return false;
                }
                $("#location").html(location);

                var arr = [],
                    sku_arr = []
                if($("#product_list_data").val()){
                    arr = $("#product_list_data").val().split(',');
                    sku_arr = $("#sku_arr").val().split(',');
                    if($.inArray(sku, sku_arr) != -1){
                        layer.alert('sku不能重复');
                        return false;
                    }
                }
                sku_arr.push(sku)
                arr.push(sku+'_'+$("#lend_num").val())
                $("#sku_arr").val(sku_arr.join(','));
                $("#product_list_data").val(arr.join(','));
                var add_str = '<tr role="row" class="odd del_'+sku+'"><td>'+sku+'</td><td>'+$("#lend_num").val()+'</td><td>'+location+'</td><td id="del"><a href="javascript:;" onclick=del_lend_tr("'+sku+'","'+$("#lend_num").val()+'")> Ｘ </a></td></tr>';
                $('#product_data').append(add_str);
            });

        },
        sample_location_edit: function () {
            Controller.api.bindevent();
        },
        sample_workorder_edit: function () {
            Controller.api.bindevent();
            $(document).on('click', "#add_product", function () {
                var location_id = $('#location').val();
                if(isNaN(parseInt(location_id))){
                    layer.alert('无效的选择');
                    return false;
                }
                var location = $("#location option:selected").text();

                var sku = $("#sku").val();
                var stock = $("#stock").val();

                var arr = [],
                    sku_arr = []
                    str = sku+'_'+stock+'_'+location_id;
                
                if($("#product_list_data").val()){
                    arr = $("#product_list_data").val().split(',');
                    sku_arr = $("#sku_arr").val().split(',');
                    if($.inArray(sku, sku_arr) != -1){
                        layer.alert('sku不能重复');
                        return false;
                    }
                }
                sku_arr.push(sku)
                arr.push(str)
                $("#sku_arr").val(sku_arr.join(','));
                $("#product_list_data").val(arr.join(','));
                var add_str = '<tr role="row" class="odd del_'+sku+'"><td>'+sku+'</td><td>'+stock+'</td><td>'+location+'</td><td id="del"><a href="javascript:;" onclick=del_add_tr("'+sku+'","'+stock+'","'+location_id+'")> Ｘ </a></td></tr>';
                $('#product_data').append(add_str);
            });
        },
        sample_workorder_out_edit: function () {
            Controller.api.bindevent();
            $(document).on('change', "#sku", function () {
                var sku = $("#sku option:selected").text();
                var location = $('#sku').val();
                if(location.length == 0){
                    layer.alert('无效的选择');
                    return false;
                }
                $("#location").html(location);
            });
            $(document).on('click', "#add_product", function () {
                var sku = $("#sku option:selected").text();
                var location = $('#sku').val();
                if(location.length == 0){
                    layer.alert('无效的选择');
                    return false;
                }
                $("#location").html(location);

                var arr = [],
                    sku_arr = []
                if($("#product_list_data").val()){
                    arr = $("#product_list_data").val().split(',')
                    sku_arr = $("#sku_arr").val().split(',')
                    if($.inArray(sku, sku_arr) != -1){
                        layer.alert('sku不能重复');
                        return false;
                    }
                }
                sku_arr.push(sku)
                arr.push(sku+'_'+$("#stock").val())
                $("#sku_arr").val(sku_arr.join(','));
                $("#product_list_data").val(arr.join(','));
                var add_str = '<tr role="row" class="odd del_'+sku+'"><td>'+sku+'</td><td>'+$("#stock").val()+'</td><td>'+location+'</td><td id="del"><a href="javascript:;" onclick=del_lend_tr("'+sku+'","'+$("#stock").val()+'")> Ｘ </a></td></tr>';
                $('#product_data').append(add_str);
            });
        },
        sample_lendlog_edit: function () {
            Controller.api.bindevent();
            $(document).on('change', "#sku", function () {
                var sku = $("#sku option:selected").text();
                var location = $('#sku').val();
                if(location.length == 0){
                    layer.alert('无效的选择');
                    return false;
                }
                $("#location").html(location);
            });
            $(document).on('click', "#add_entry_product", function () {
                var sku = $("#sku option:selected").text();
                var location = $('#sku').val();
                if(location.length == 0){
                    layer.alert('无效的选择');
                    return false;
                }
                $("#location").html(location);

                var arr = [],
                    sku_arr = []
                if($("#product_list_data").val()){
                    arr = $("#product_list_data").val().split(',')
                    sku_arr = $("#sku_arr").val().split(',')
                    if($.inArray(sku, sku_arr) != -1){
                        layer.alert('sku不能重复');
                        return false;
                    }
                }
                sku_arr.push(sku)
                arr.push(sku+'_'+$("#lend_num").val())
                $("#sku_arr").val(sku_arr.join(','));
                $("#product_list_data").val(arr.join(','));
                var add_str = '<tr role="row" class="odd del_'+sku+'"><td>'+sku+'</td><td>'+$("#lend_num").val()+'</td><td>'+location+'</td><td id="del"><a href="javascript:;" onclick=del_lend_tr("'+sku+'","'+$("#lend_num").val()+'")> Ｘ </a></td></tr>';
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
/**
 * 入库删除
 * @param {sku} sku 
 * @param {库存} stock 
 * @param {库位id} location_id 
 */
function del_add_tr(sku,stock,location_id){
    $(".del_"+sku).remove();
    var arr = $("#product_list_data").val().split(',');
    var str = sku+'_'+stock+'_'+location_id;
    var sku_arr = $("#sku_arr").val().split(',');
    sku_arr.splice($.inArray(sku, sku_arr),1)
    arr.splice($.inArray(str, arr),1)
    $("#sku_arr").val(sku_arr.join(','));
    $("#product_list_data").val(arr.join(','));
}
/**
 * 借出记录删除
 * @param {sku} sku 
 * @param {借出数量} lend_num 
 */
function del_lend_tr(sku,lend_num){
    $(".del_"+sku).remove();
    var arr = $("#product_list_data").val().split(',');
    var str = sku+'_'+lend_num;
    var sku_arr = $("#sku_arr").val().split(',');    
    sku_arr.splice($.inArray(sku, sku_arr),1)
    arr.splice($.inArray(str, arr),1)
    $("#sku_arr").val(sku_arr.join(','))
    $("#product_list_data").val(arr.join(','))
    
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
