define(['jquery', 'bootstrap', 'backend', 'table', 'form', 'jqui'], function ($, undefined, Backend, Table, Form) {

    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                searchFormVisible: true,
                pageList: [10, 25, 50, 100],
                extend: {
                    index_url: 'warehouse/check/index' + location.search,
                    add_url: 'warehouse/check/add',
                    edit_url: 'warehouse/check/edit',
                    // del_url: 'warehouse/check/del',
                    multi_url: 'warehouse/check/multi',
                    table: 'check_order',
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
                        { field: 'check_order_number', title: __('Check_order_number') },
                        { field: 'type', title: __('Type'), custom: { 1: 'success', 2: 'success' }, searchList: { 1: '采购质检', 2: '退货质检' }, formatter: Table.api.formatter.status },
                        { field: 'purchaseorder.purchase_number', title: __('Purchase_id') },
                        { field: 'orderreturn.return_order_number', title: __('退货单号') },
                        { field: 'supplier.supplier_name', title: __('Supplier_id') },
                        { field: 'remark', title: __('Remark'), operate: false },
                        {
                            field: 'status', title: __('Status'), custom: { 0: 'success', 1: 'yellow', 2: 'blue', 3: 'danger', 4: 'gray' },
                            searchList: { 0: '新建', 1: '待审核', 2: '已审核', 3: '已拒绝', 4: '已取消' },
                            formatter: Table.api.formatter.status
                        },
                        {
                            field: 'is_stock', title: __('是否已创建入库单'), custom: { 0: 'danger', 1: 'success' },
                            searchList: { 0: '否', 1: '是' },
                            formatter: Table.api.formatter.status
                        },
                        { field: 'createtime', title: __('Createtime'), operate: 'RANGE', addclass: 'datetimerange' },
                        { field: 'create_person', title: __('Create_person') },
                        {
                            field: 'operate', title: __('Operate'), table: table, events: Table.api.events.operate, buttons: [
                                {
                                    name: 'submitAudit',
                                    text: '提交审核',
                                    title: __('提交审核'),
                                    classname: 'btn btn-xs btn-success btn-ajax',
                                    icon: 'fa fa-leaf',
                                    url: 'warehouse/check/audit',
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
                                    url: 'warehouse/check/detail',
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
                                    url: 'warehouse/check/cancel',
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
                                    url: 'warehouse/check/edit',
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
                                },
                                {
                                    name: 'stock',
                                    text: '去入库',
                                    title: __('入库'),
                                    classname: 'btn btn-xs btn-success btn-dialog',
                                    icon: 'fa fa-plus',
                                    url: 'warehouse/instock/add',
                                    extend: 'data-area = \'["100%","100%"]\'',
                                    callback: function (data) {
                                        Layer.alert("接收到回传数据：" + JSON.stringify(data), { title: "回传数据" });
                                    },
                                    visible: function (row) {
                                        //返回true时按钮显示,返回false隐藏
                                        if (row.status == 2 &&　row.is_stock == 0) {
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
                    url: Config.moduleurl + '/warehouse/check/setStatus',
                    data: { ids: ids, status: 2 }
                }, function (data, ret) {
                    table.bootstrapTable('refresh');
                });
            })

            //审核拒绝
            $(document).on('click', '.btn-close', function () {
                var ids = Table.api.selectedids(table);
                Backend.api.ajax({
                    url: Config.moduleurl + '/warehouse/check/setStatus',
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
        },
        add: function () {
            Controller.api.bindevent();
            //上传文件
            $(document).on('click', '.pluploads', function () {
                var _this = $(this);
                var url = _this.parent().parent().parent().find('.unqualified_images').val();
                Fast.api.open(
                    'warehouse/check/uploads?img_url=' + url, '上传文件', {
                    callback: function (data) {
                        _this.parent().parent().parent().find('.unqualified_images').val(data.unqualified_images);
                    }
                }
                )
            })

            var purchase_id = $('.purchase_id').val();
            if (purchase_id) {
                $('.purchase_id').change();
            }

            var order_return_id = $('.order_return_id').val();
            if (order_return_id) {
                $('.order_return_id').change();
            }


            //移除
            $(document).on('click', '.btn-del', function () {
                $(this).parent().parent().remove();
            })

        },
        uploads: function () {
            Controller.api.bindevent(function (data, ret) {
                //这里是表单提交处理成功后的回调函数，接收来自php的返回数据
                Fast.api.close(data);//这里是重点
                return false;
            });

            $('.unqualified_images').change();

        },
        edit: function () {
            Controller.api.bindevent();
            //上传文件
            $(document).on('click', '.pluploads', function () {
                var _this = $(this);
                var url = _this.parent().parent().parent().find('.unqualified_images').val();
                Fast.api.open(
                    'warehouse/check/uploads?img_url=' + url, '上传文件', {
                    callback: function (data) {
                        _this.parent().parent().parent().find('.unqualified_images').val(data.unqualified_images);
                    }
                }
                )
            })

            //删除商品数据
            $(document).on('click', '.btn-del', function () {
                $(this).parent().parent().remove();
                var id = $(this).parent().parent().find('.item_id').val();
                if (id) {
                    Backend.api.ajax({
                        url: Config.moduleurl + '/warehouse/check/deleteItem',
                        data: { id: id }
                    });
                }
            })


        },
        detail: function () {
            Controller.api.bindevent();
            //上传文件
            $(document).on('click', '.pluploads', function () {
                var _this = $(this);
                var url = _this.parent().parent().parent().find('.unqualified_images').val();
                Fast.api.open(
                    'warehouse/check/uploads?img_url=' + url, '上传文件', {
                    callback: function (data) {
                        _this.parent().parent().parent().find('.unqualified_images').val(data.unqualified_images);
                    }
                }
                )
            })
        },
        api: {
            formatter: {

            },

            bindevent: function (success, error) {


                Form.api.bindevent($("form[role=form]"), success, error);

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



                //切换质检类型
                $(document).on('change', '.type', function () {
                    var type = $(this).val();
                    if (type == 1) {
                        $('.order_number').addClass('hidden');
                        $('.purchase_id_number').removeClass('hidden');
                        $('#c-order_number').val('');
                    } else {
                        $('.order_number').removeClass('hidden');
                        $('.purchase_id_number').addClass('hidden');
                        $('.purchase_id').val('');
                    }
                })

                //计算不合格数量及合格率
                $(document).on('blur', '.arrivals_num', function () {
                    var type = $('.type').val();
                    if (type == 1) {
                        var check_num = $(this).parent().prev().find('input').val();
                        var arrivals_num = $(this).val();
                        var quantity_num = $(this).parent().next().find('.quantity_num').val();
                        var sample_num = $(this).parent().next().next().find('.sample_num').val();
                        var not_quantity_num = arrivals_num * 1 - quantity_num * 1;

                        $(this).parent().next().next().next().find('input').val(not_quantity_num);
                        $(this).parent().next().next().next().next().find('input').val((quantity_num / arrivals_num * 100).toFixed(2));
                    } else if (type == 2) {
                        var arrivals_num = $(this).val();
                        var quantity_num = $(this).parent().parent().find('.quantity_num').val();
                        var not_quantity_num = arrivals_num * 1 - quantity_num * 1;
                        $(this).parent().parent().find('.unqualified_num').val(not_quantity_num);
                        $(this).parent().parent().find('.quantity_rate').val((quantity_num / arrivals_num * 100).toFixed(2));
                    }

                })

                //计算不合格数量及合格率
                $(document).on('blur', '.quantity_num', function () {
                    var type = $('.type').val();
                    if (type == 1) {
                        var check_num = $(this).parent().prev().prev().find('input').val();
                        var arrivals_num = $(this).parent().prev().find('input').val();
                        var quantity_num = $(this).val();
                        var sample_num = $(this).parent().next().find('.sample_num').val();
                        var not_quantity_num = arrivals_num * 1 - quantity_num * 1;

                        $(this).parent().next().next().find('input').val(not_quantity_num);
                        $(this).parent().next().next().next().find('input').val((quantity_num / arrivals_num * 100).toFixed(2));
                    } else if (type == 2) {
                        var arrivals_num = $(this).parent().parent().find('.arrivals_num').val();
                        var quantity_num = $(this).val();
                        var not_quantity_num = arrivals_num * 1 - quantity_num * 1;
                        $(this).parent().parent().find('.unqualified_num').val(not_quantity_num);
                        $(this).parent().parent().find('.quantity_rate').val((quantity_num / arrivals_num * 100).toFixed(2));
                    }
                })

                //计算不合格数量及合格率
                $(document).on('blur', '.sample_num', function () {
                    var check_num = $(this).parent().prev().prev().prev().find('input').val();
                    var arrivals_num = $(this).parent().prev().prev().find('input').val();
                    var quantity_num = $(this).parent().prev().find('input').val();
                    var sample_num = $(this).val();
                    var not_quantity_num = arrivals_num * 1 - quantity_num * 1;

                    $(this).parent().next().find('input').val(not_quantity_num);
                    $(this).parent().next().next().find('input').val((quantity_num / arrivals_num * 100).toFixed(2));
                })


                //采购单
                $(document).on('change', '.purchase_id', function () {
                    var id = $(this).val();
                    if (id) {
                        var url = Config.moduleurl + '/warehouse/check/getPurchaseData';
                        Backend.api.ajax({
                            url: url,
                            data: { id: id }
                        }, function (data, ret) {

                            if (data.supplier_id) {
                                $(".supplier").selectpicker('val', data.supplier_id);//默认选中
                            }
                            
                            //循环展示商品信息
                            var shtml = ' <tr><th>SKU</th><th>供应商SKU</th><th>采购数量</th><th>已质检数量</th><th>到货数量</th><th>合格数量</th><th>留样数量</th><th>不合格数量</th><th>合格率</th><th>备注</th><th>上传图片</th><th>操作</th></tr>';
                            $('.caigou table tbody').html('');

                            for (var i in data.item) {
                                var sku = data.item[i].sku;
                                if (!sku) {
                                    sku = '';
                                }

                                shtml += ' <tr><td><input id="c-purchase_remark" class="form-control sku" name="sku[]" type="text" value="' + sku + '"></td>'
                                shtml += ' <td><input id="c-purchase_remark" class="form-control" name="supplier_sku[]" type="text" value="' + data.item[i].supplier_sku + '"></td>'
                                shtml += ' <td><input id="c-purchase_remark" class="form-control purchase_num" name="purchase_num[]" type="text" redeonly value="' + data.item[i].purchase_num + '"></td>'
                                shtml += ' <td><input id="c-purchase_remark" class="form-control check_num" name="check_num[]" type="text" readonly value="' + data.item[i].check_num + '"></td>'
                                shtml += ' <td><input id="c-purchase_remark" class="form-control arrivals_num" name="arrivals_num[]" type="text"></td>'
                                shtml += ' <td><input id="c-purchase_remark" class="form-control quantity_num" name="quantity_num[]" type="text"></td>'
                                shtml += ' <td><input id="c-purchase_remark" class="form-control sample_num" name="sample_num[]" type="text"></td>'
                                shtml += ' <td><input id="c-purchase_remark" class="form-control unqualified_num" name="unqualified_num[]" type="text"></td>'
                                shtml += '  <td><input id="c-purchase_remark" class="form-control quantity_rate" name="quantity_rate[]" type="text">%</td>'
                                shtml += ' <td><input style="width: 200px;" id="c-purchase_remark" class="form-control remark" name="remark[]" type="text"></td>'
                                shtml += ' <td><input id="c-unqualified_images" style="width: 150px;" class="form-control unqualified_images" size="200" readonly name="unqualified_images[]" type="text"></td>'

                                shtml += ' <td><span><button type="button" id="plupload-unqualified_images" class="btn btn-danger pluploads" data-input-id="c-unqualified_images" data-mimetype="image/gif,image/jpeg,image/png,image/jpg,image/bmp" data-multiple="true" data-maxcount="3" data-preview-id="p-unqualified_images"><i class="fa fa-upload"></i>'
                                shtml += ' 上传</button></span>'

                                shtml += ' <a href="javascript:;" class="btn btn-danger btn-del" title="删除"><i class="fa fa-trash"></i>删除</a>'
                                shtml += ' </td>'

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


                //退货单
                $(document).on('change', '.order_return_id', function () {
                    var id = $(this).val();
                    if (id) {
                        var url = Config.moduleurl + '/warehouse/check/getOrderReturnData';
                        Backend.api.ajax({
                            url: url,
                            data: { id: id }
                        }, function (data, ret) {
                            $('#toolbar').hide();
                            $('.supplier').val(data.supplier_id);
                            //循环展示商品信息
                            var shtml = ' <tr><th>SKU</th><th>退货数量</th><th>到货数量</th><th>合格数量</th><th>不合格数量</th><th>合格率</th><th>备注</th><th>上传图片</th><th>操作</th></tr>';
                            $('.caigou table tbody').html('');
                           
                            for (var i in data) { 
                                var sku = data[i].return_sku;
                                if (!sku) {
                                    sku = '';
                                }
                                shtml += ' <tr><td><input id="c-purchase_remark" class="form-control sku" name="sku[]" type="text" value="' + sku + '"></td>'
                                shtml += ' <td>' + data[i].return_sku_qty + '</td>'
                                shtml += ' <td><input id="c-purchase_remark" class="form-control arrivals_num" name="arrivals_num[]" type="text"></td>'
                                shtml += ' <td><input id="c-purchase_remark" class="form-control quantity_num" name="quantity_num[]" type="text"></td>'
                                shtml += ' <td><input id="c-purchase_remark" class="form-control unqualified_num" name="unqualified_num[]" type="text"></td>'
                                shtml += '  <td><input id="c-purchase_remark" class="form-control quantity_rate" name="quantity_rate[]" type="text">%</td>'
                                shtml += ' <td><input style="width: 200px;" id="c-purchase_remark" class="form-control remark" name="remark[]" type="text"></td>'
                                shtml += ' <td><input id="c-unqualified_images" style="width: 150px;" class="form-control unqualified_images" size="200" readonly name="unqualified_images[]" type="text"></td>'

                                shtml += ' <td><span><button type="button" id="plupload-unqualified_images" class="btn btn-danger pluploads" data-input-id="c-unqualified_images" data-mimetype="image/gif,image/jpeg,image/png,image/jpg,image/bmp" data-multiple="true" data-maxcount="3" data-preview-id="p-unqualified_images"><i class="fa fa-upload"></i>'
                                shtml += ' 上传</button></span>'

                                shtml += ' <a href="javascript:;" class="btn btn-danger btn-del" title="删除"><i class="fa fa-trash"></i>删除</a>'
                                shtml += ' </td>'

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

                    }, function (data, ret) {
                        Fast.api.error(ret.msg);
                    });

                })



            }
        }
    };
    return Controller;
});