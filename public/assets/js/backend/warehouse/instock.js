define(['jquery', 'bootstrap', 'backend', 'table', 'form', 'jqui','bootstrap-table-jump-to'], function ($, undefined, Backend, Table, Form) {

    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                showJumpto: true,
                searchFormVisible: true,
                pageList: [10, 25, 50, 100],
                extend: {
                    index_url: 'warehouse/instock/index' + location.search,
                    add_url: 'warehouse/instock/add',
                    edit_url: 'warehouse/instock/edit',
                    // del_url: 'warehouse/instock/del',
                    import_url: 'warehouse/instock/import',

                    multi_url: 'warehouse/instock/multi',
                    table: 'in_stock',
                }
            });

            var table = $("#table");

            // 初始化表格
            table.bootstrapTable({
                url: $.fn.bootstrapTable.defaults.extend.index_url,
                pk: 'id',
                sortName: 'createtime',
                columns: [
                    [
                        { checkbox: true },
                        { field: 'id', title: __('Id') },
                        { field: 'in_stock_number', title: __('In_stock_number'), operate: 'like' },
                        { field: 'instocktype.name', title: __('In_stock_type'), operate: 'like'  },
                        { field: 'checkorder.check_order_number', title: __('质检单号'), operate: 'like' },
                        { field: 'order_number', title: __('Order_number'), operate: 'like' },
                        { field: 'remark', title: __('Remark'), operate: false },
                        {
                            field: 'status', title: __('Status'), custom: { 0: 'success', 1: 'yellow', 2: 'blue', 3: 'danger', 4: 'gray' },
                            searchList: { 0: '新建', 1: '待审核', 2: '已审核', 3: '已拒绝', 4: '已取消' },
                            formatter: Table.api.formatter.status
                        },
                        { field: 'createtime', title: __('Createtime'), operate: 'RANGE', addclass: 'datetimerange' },
                        { field: 'create_person', title: __('Create_person') , operate: 'like'},
                        { field: 'sku', title: __('sku'), operate: 'like', visible: false },
                        {
                            field: 'operate', title: __('Operate'), table: table, events: Table.api.events.operate, buttons: [
                                {
                                    name: 'submitAudit',
                                    text: '提交审核',
                                    title: __('提交审核'),
                                    classname: 'btn btn-xs btn-success btn-ajax',
                                    icon: 'fa fa-leaf',
                                    url: 'warehouse/instock/audit',
                                    confirm: '确认提交审核吗',
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
                                        if (row.status == 0) {
                                            return true;
                                        } else {
                                            return false;
                                        }
                                    },
                                },
                                {
                                    name: 'detail',
                                    text: '详情',
                                    title: __('Detail'),
                                    classname: 'btn btn-xs  btn-primary  btn-dialog',
                                    icon: 'fa fa-list',
                                    url: 'warehouse/instock/detail',
                                    extend: 'data-area = \'["100%","100%"]\'',
                                    callback: function (data) {
                                        Layer.alert("接收到回传数据：" + JSON.stringify(data), { title: "回传数据" });
                                    },
                                    visible: function (row) {
                                        //返回true时按钮显示,返回false隐藏
                                        return true;
                                    }
                                },
                                {
                                    name: 'cancel',
                                    text: '取消',
                                    title: '取消',
                                    classname: 'btn btn-xs btn-danger btn-cancel',
                                    icon: 'fa fa-remove',
                                    url: 'warehouse/instock/cancel',
                                    callback: function (data) {
                                        Layer.alert("接收到回传数据：" + JSON.stringify(data), { title: "回传数据" });
                                    },
                                    visible: function (row) {
                                        //返回true时按钮显示,返回false隐藏
                                        if (row.status == 0) {
                                            return true;
                                        } else {
                                            return false;
                                        }
                                    }
                                },
                                {
                                    name: 'edit',
                                    text: '',
                                    title: __('Edit'),
                                    classname: 'btn btn-xs btn-success btn-dialog',
                                    icon: 'fa fa-pencil',
                                    url: 'warehouse/instock/edit',
                                    extend: 'data-area = \'["100%","100%"]\'',
                                    callback: function (data) {
                                        Layer.alert("接收到回传数据：" + JSON.stringify(data), { title: "回传数据" });
                                    },
                                    visible: function (row) {
                                        //返回true时按钮显示,返回false隐藏
                                        if (row.status == 0) {
                                            return true;
                                        } else {
                                            return false;
                                        }
                                    }
                                }

                            ], formatter: Table.api.formatter.operate
                        }
                    ]
                ]
            });

            // 为表格绑定事件
            Table.api.bindevent(table);

            //审核通过
            $(document).on('click', '.btn-open', function () {
                var ids = Table.api.selectedids(table);
                Backend.api.ajax({
                    url: Config.moduleurl + '/warehouse/instock/setStatus',
                    data: { ids: ids, status: 2 }
                }, function (data, ret) {
                    table.bootstrapTable('refresh');
                });
            })
            //添加
            $(document).on('click', '.btn-addd', function () {
                var options = {
                    shadeClose: false,
                    shade: [0.3, '#393D49'],
                    area: ['100%', '100%'], //弹出层宽高
                    callback: function (value) {
                        table.bootstrapTable('refresh');
                    }
                };
                Fast.api.open('warehouse/instock/add?type=2', '添加', options);
            })
            //审核拒绝
            $(document).on('click', '.btn-close', function () {
                var ids = Table.api.selectedids(table);
                Backend.api.ajax({
                    url: Config.moduleurl + '/warehouse/instock/setStatus',
                    data: { ids: ids, status: 3 }
                }, function (data, ret) {
                    table.bootstrapTable('refresh');
                });
            })

            //审核拒绝
            $(document).on('click', '.btn-cancel', function (e) {
                e.preventDefault();
                var url = $(this).attr('href');
                Backend.api.ajax({
                    url: url,
                    data: { status: 4 }
                }, function (data, ret) {
                    table.bootstrapTable('refresh');
                });
            })

            //批量导出xls 
            $('.btn-batch-export-xls').click(function () {
                var ids = Table.api.selectedids(table);
                if (ids.length > 0) {
                    window.open(Config.moduleurl + '/warehouse/instock/batch_export_xls?ids=' + ids, '_blank');
                } else {
                    var options = table.bootstrapTable('getOptions');
                    var search = options.queryParams({});
                    var filter = search.filter;
                    var op = search.op;
                    window.open(Config.moduleurl + '/warehouse/instock/batch_export_xls?filter=' + filter + '&op=' + op, '_blank');
                }
                
            });
           
        },
        add: function () {
            Controller.api.bindevent();
            $('.tdtd').hide();
            //移除
            $(document).on('click', '.btn-del', function () {
                $(this).parent().parent().remove();
            })

            var check_id = $('.check_id').val();
            if (check_id) {
                $('.check_id').change();
            }


        },
        edit: function () {
            Controller.api.bindevent();

            
            //删除商品数据
            $(document).on('click', '.btn-del', function () {
                var _this = $(this);
                var id = $(this).parent().parent().find('.item_id').val();
                if (id) {
                    Layer.confirm(__('删除不可恢复，确定删除此数据吗?'), function () {
                        _this.parent().parent().remove();
                        Backend.api.ajax({
                            url: Config.moduleurl + '/warehouse/instock/deleteItem',
                            data: {id: id}
                        }, function () {
                            Layer.closeAll();
                        });
                    });
                }
            })
        },
        detail: function () {
            Controller.api.bindevent();
        },
        api: {
            bindevent: function () {
                //提交审核
                $(document).on('click', '.btn-status', function () {
                    $('.status').val(1);
                })

                Form.api.bindevent($("form[role=form]"));
                // //切换质检类型
                // $(document).on('change', '.type', function () {
                //     var type = $(this).val();
                //     if (type == 1) {
                //         $('.order_number').addClass('hidden');
                //         $('.purchase_id_number').removeClass('hidden');
                //         $('#c-order_number').val('');
                //     } else {
                //         $('.order_number').removeClass('hidden');
                //         $('.purchase_id_number').addClass('hidden');
                //         $('.purchase_id').val('');
                //     }
                // })

                //切换采购单
                $(document).on('change', '.check_id', function () {

                    var id = $(this).val();
                    if (id) {
                        var url = Config.moduleurl + '/warehouse/instock/getCheckData';
                        Backend.api.ajax({
                            url: url,
                            data: { id: id }
                        }, function (data, ret) {
                            //循环展示商品信息
                            var shtml = ' <tr><th>SKU</th><th>供应商SKU</th><th>采购数量</th>><th>到货数量</th><th>质检合格数量</th><th>留样数量</th><th>入库数量</th><th>操作</th></tr>';
                            $('.caigou table tbody').html('');
                            $('#toolbar').hide();
                            for (var i in data) {
                                shtml += ' <input  class="form-control"  name="row[replenish_id]" type="hidden" value="' + data[i].replenish_id + '"><tr><td><input id="c-purchase_remark" class="form-control sku" name="sku[]" readonly type="text" value="' + data[i].sku + '"></td>'
                                shtml += ' <td class="supplier_sku">' + data[i].supplier_sku + '</td>'
                                shtml += ' <td>' + data[i].purchase_num + '</td>'
                                shtml += ' <td>' + data[i].arrivals_num + '</td>'
                                shtml += ' <td>' + data[i].quantity_num + '</td>'
                                shtml += ' <td><input id="c-sample_num" class="form-control" readonly oninput="if(value < 0){alert(\'只能输入正整数！\');value = 0}" name="sample_num[]" value="' + data[i].sample_num + '" type="text"></td>'
                                shtml += ' <td><input id="c-in_stock_num" class="form-control"  oninput="if(value < 0){alert(\'只能输入正整数！\');value = 0}" name="in_stock_num[]" value="' + (data[i].quantity_num - data[i].sample_num ) + '" type="text"></td>'
                                shtml += ' <input id="c-purchase_id" class="form-control"  name="purchase_id[]" value="' + data[i].purchase_id + '" type="hidden">'
                                shtml += ' <td><a href="javascript:;" class="btn btn-danger btn-del" title="删除"><i class="fa fa-trash"></i>删除</a></td>'
                                shtml += ' </tr>'
                            }
                            $('.caigou table tbody').append(shtml);


                            //模糊匹配订单
                            $('.sku').autocomplete({
                                source: function (request, response) {
                                    $.ajax({
                                        type: "POST",
                                        url: "ajax/ajaxGetLikeOriginSku",
                                        dataType: "json",
                                        cache: false,
                                        async: false,
                                        data: {
                                            origin_sku: request.term
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
                                },
                                delay: 10,//延迟100ms便于输入
                                select: function (event, ui) {
                                    $("#bankUnionNo").val(ui.item.id);//取出在return里面放入到item中的属性
                                },
                                scroll: true,
                                pagingMore: true,
                                max: 5000
                            });
                        });
                    }

                })

                //模糊匹配订单
                $('.sku').autocomplete({
                    source: function (request, response) {
                        $.ajax({
                            type: "POST",
                            url: "ajax/ajaxGetLikeOriginSku",
                            dataType: "json",
                            cache: false,
                            async: false,
                            data: {
                                origin_sku: request.term
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
                    },
                    delay: 10,//延迟100ms便于输入
                    select: function (event, ui) {
                        $("#bankUnionNo").val(ui.item.id);//取出在return里面放入到item中的属性
                    },
                    scroll: true,
                    pagingMore: true,
                    max: 5000
                });


                //新增
                $(document).on('click', '.btn-add', function () {
                    var content = $('#table-content table tbody').html();
                    $('.caigou table tbody').append(content);

                    //模糊匹配订单
                    $('.sku').autocomplete({
                        source: function (request, response) {
                            $.ajax({
                                type: "POST",
                                url: "ajax/ajaxGetLikeOriginSku",
                                dataType: "json",
                                cache: false,
                                async: false,
                                data: {
                                    origin_sku: request.term
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
                        },
                        delay: 10,//延迟100ms便于输入
                        select: function (event, ui) {
                            $("#bankUnionNo").val(ui.item.id);//取出在return里面放入到item中的属性
                        },
                        scroll: true,
                        pagingMore: true,
                        max: 5000
                    });
                })


                //获取sku信息
                $(document).on('change', '.sku', function () {
                    var sku = $(this).val();
                    var _this = $(this);
                    if (!sku) {
                        return false;
                    }
                    Backend.api.ajax({
                        url: 'ajax/getSkuList',
                        data: { sku: sku }
                    }, function (data, ret) {
                        _this.parent().parent().find('.supplier_sku').text(data.supplier_sku);
                    }, function (data, ret) {
                        Fast.api.error(ret.msg);
                    });

                })

            }
        },
        account_in_stock_order:function(){
             // 初始化表格参数配置
             Table.api.init({
                showJumpto: true,
                searchFormVisible: true,
                pageList: [10, 25, 50, 100],
                extend: {
                    index_url: 'warehouse/instock/account_in_stock_order' + location.search,
                    add_url: '',
                    edit_url: '',
                    multi_url: '',
                    table: 'in_stock',
                }
            });

            var table = $("#table");
            table.on('load-success.bs.table', function (e, data) {
                //这里可以获取从服务端获取的JSON数据
                //这里我们手动设置底部的值
                //console.log(data);
                $("#money").text(data.totalPriceInfo);

            });
            // 初始化表格
            table.bootstrapTable({
                url: $.fn.bootstrapTable.defaults.extend.index_url,
                pk: 'id',
                sortName: 'id',
                columns: [
                    [
                        { checkbox: true },
                        { field: 'id', title: __('Id'),operate:false },
                        { field: 'in_stock_number', title: __('In_stock_number') },
                        { field: 'instocktype.name', title: __('In_stock_type'),operate:false },
                        { field: 'checkorder.check_order_number', title: __('质检单号'),operate:false },
                        { field: 'order_number', title: __('Order_number'),operate:false},
                        {
                            field: 'status', title: __('Status'), custom: { 0: 'success', 1: 'yellow', 2: 'blue', 3: 'danger', 4: 'gray' },
                            searchList: { 0: '新建', 1: '待审核', 2: '已审核', 3: '已拒绝', 4: '已取消' },
                            operate:false,
                            formatter: Table.api.formatter.status
                        },
                        {
                            field:'total_money',title:__('Financial_total_money'),operate:false
                        },
                        { field: 'createtime', title: __('Createtime'), operate: 'RANGE', addclass: 'datetimerange' },
                        {
                            field: 'operate', title: __('Operate'), table: table, events: Table.api.events.operate, buttons: [
                                {
                                    name: 'submitAudit',
                                    text: '提交审核',
                                    title: __('提交审核'),
                                    classname: 'btn btn-xs btn-success btn-ajax',
                                    icon: 'fa fa-leaf',
                                    url: 'warehouse/instock/audit',
                                    confirm: '确认提交审核吗',
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
                                        if (row.status == 0) {
                                            return true;
                                        } else {
                                            return false;
                                        }
                                    },
                                },
                                {
                                    name: 'detail',
                                    text: '详情',
                                    title: __('Detail'),
                                    classname: 'btn btn-xs  btn-primary  btn-dialog',
                                    icon: 'fa fa-list',
                                    url: 'warehouse/instock/account_in_stock_order_detail',
                                    extend: 'data-area = \'["100%","100%"]\'',
                                    callback: function (data) {
                                        Layer.alert("接收到回传数据：" + JSON.stringify(data), { title: "回传数据" });
                                    },
                                    visible: function (row) {
                                        //返回true时按钮显示,返回false隐藏
                                        return true;
                                    }
                                },
                                {
                                    name: 'cancel',
                                    text: '取消',
                                    title: '取消',
                                    classname: 'btn btn-xs btn-danger btn-cancel',
                                    icon: 'fa fa-remove',
                                    url: 'warehouse/instock/cancel',
                                    callback: function (data) {
                                        Layer.alert("接收到回传数据：" + JSON.stringify(data), { title: "回传数据" });
                                    },
                                    visible: function (row) {
                                        //返回true时按钮显示,返回false隐藏
                                        if (row.status == 0) {
                                            return true;
                                        } else {
                                            return false;
                                        }
                                    }
                                },
                                {
                                    name: 'edit',
                                    text: '',
                                    title: __('Edit'),
                                    classname: 'btn btn-xs btn-success btn-dialog',
                                    icon: 'fa fa-pencil',
                                    url: 'warehouse/instock/edit',
                                    extend: 'data-area = \'["100%","100%"]\'',
                                    callback: function (data) {
                                        Layer.alert("接收到回传数据：" + JSON.stringify(data), { title: "回传数据" });
                                    },
                                    visible: function (row) {
                                        //返回true时按钮显示,返回false隐藏
                                        if (row.status == 0) {
                                            return true;
                                        } else {
                                            return false;
                                        }
                                    }
                                }

                            ], formatter: Table.api.formatter.operate
                        }
                    ]
                ]
            });
            // 为表格绑定事件
            Table.api.bindevent(table);         
        },
        account_in_stock_order_detail:function(){
            Form.api.bindevent($("form[role=form]"));
        }
    };
    return Controller;
});