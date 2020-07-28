define(['jquery', 'bootstrap', 'backend', 'table', 'form', 'jqui', 'bootstrap-select', 'bootstrap-table-jump-to'], function ($, undefined, Backend, Table, Form) {

    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                showJumpto: true,
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
                sortName: 'createtime',
                columns: [
                    [
                        { checkbox: true },
                        { field: 'id', title: __('Id') },
                        { field: 'check_order_number', title: __('Check_order_number'), operate: 'like' },
                        { field: 'purchaseorder.purchase_number', title: __('Purchase_id'), operate: 'like' },

                        { field: 'purchaseorder.create_person', title: __('采购创建人'), operate: 'like' },
                        { field: 'supplier.supplier_name', title: __('Supplier_id'), operate: 'like' },
                        { field: 'remark', title: __('Remark'), formatter: Controller.api.formatter.getClear, operate: false },
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
                        { field: 'sku', title: __('sku'), operate: 'like', visible: false },
                        {
                            field: 'is_process', title: __('是否需要处理'), custom: { 0: 'danger', 1: 'success' },
                            searchList: { 0: '否', 1: '是' }, visible: false,
                            formatter: Table.api.formatter.status
                        },
                        {
                            field: 'operate', title: __('Operate'), table: table, events: Table.api.events.operate, buttons: [

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
                                        if (row.status == 2 && row.is_stock == 0) {
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


            $(document).on('click', ".problem_desc_info", function () {
                var problem_desc = $(this).attr('name');
                //Layer.alert(problem_desc);
                Layer.open({
                    closeBtn: 1,
                    title: '问题描述',
                    area: ['900px', '500px'],
                    content: problem_desc
                });
                return false;
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


            //批量生成退销单
            $(document).on('click', '.btn-matching', function () {
                var ids = Table.api.selectedids(table);

                Backend.api.open('warehouse/check/add_return_order/ids/' + ids, '批量生成退销单', { area: ["60%", "60%"] });

            });

            //批量导出xls 
            $('.btn-batch-export-xls').click(function () {
                var ids = Table.api.selectedids(table);
                if (ids.length > 0) {
                    window.open(Config.moduleurl + '/warehouse/check/batch_export_xls?ids=' + ids, '_blank');
                } else {
                    var options = table.bootstrapTable('getOptions');
                    var search = options.queryParams({});
                    var filter = search.filter;
                    var op = search.op;
                    window.open(Config.moduleurl + '/warehouse/check/batch_export_xls?filter=' + filter + '&op=' + op, '_blank');
                }

            });


        },
        add: function () {
            Controller.api.bindevent(function () { });
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
            var batch_id = $('.batch_id').val();
            if (purchase_id && !batch_id) {
                $('.purchase_id').change();
            }

            if (batch_id) {
                $('.batch_id').change();
            }

            //移除
            $(document).on('click', '.btn-del', function () {
                $(this).parent().parent().remove();
            })

        },
        add_return_order: function () {
            Controller.api.bindevent();
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

            //审核
            $(document).on('click', '.btn-check', function () {
                var ids = $(this).attr('data-ids');
                var status = $(this).attr('data-status');
                if (!ids) {
                    Toastr.error('缺少参数');
                    return false;
                }
                Backend.api.ajax({
                    url: 'warehouse/check/setStatus',
                    data: { ids: ids, status: status }
                }, function (item, ret) {
                    parent.location.reload();
                })
            })
        },
        api: {
            formatter: {

                getClear: function (value) {
                    if (value == null || value == undefined) {
                        return '';
                    } else {
                        var tem = value;

                        if (tem.length <= 20) {
                            return tem;
                        } else {
                            return '<span class="problem_desc_info" name = "' + tem + '" style="">' + tem.substr(0, 20) + '...</span>';

                        }
                    }
                },

            },

            bindevent: function (success, error) {

                Form.api.bindevent($("form[role=form]"), success, error);

                $(document).on('click', '.btn-status', function () {
                    $('.status').val(1);
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
                    var batch_id = $('.batch_id').val();
                    var arrivals_num = $(this).val();
                    if (arrivals_num*1 < 0) {
                        Toastr.error('到货数量不能小于0');
                        $(this).val(0);
                        return false;
                    }
                    //判断是否分批
                    if (batch_id) {
                        var true_num = $(this).parent().parent().find('.should_arrival_num').val();
                    } else {
                        var true_num = $(this).parent().parent().find('.purchase_num').val();
                    }

                    /**
                     * 到货数量大于应到货数量时：不合格数量=应到数量-合格数量。不合格数量小于0时取0.
                     * 到货数量小于、等于应到会数量时：不合格数量=到货数量-合格数量
                     */
                    var quantity_num = $(this).parent().next().find('.quantity_num').val();
                    if (arrivals_num * 1 > true_num * 1) {
                        $(this).parent().parent().find('.error_type').val(1);
                        var not_quantity_num = true_num * 1 - quantity_num * 1;
                    } else if (arrivals_num * 1 < true_num) {
                        $(this).parent().parent().find('.error_type').val(2);
                        var not_quantity_num = arrivals_num * 1 - quantity_num * 1;
                    } else {
                        $(this).parent().parent().find('.error_type').val(0);
                        var not_quantity_num = arrivals_num * 1 - quantity_num * 1;
                    }

                    if (not_quantity_num < 0) {
                        not_quantity_num = 0;
                    }
                    
                    $(this).parent().next().next().next().find('input').val(not_quantity_num);
                    if (arrivals_num * 1 > 0) {
                        $(this).parent().next().next().next().next().find('input').val((quantity_num * 1 / arrivals_num * 100).toFixed(2));
                    }

                })

                //计算不合格数量及合格率
                $(document).on('blur', '.quantity_num', function () {
                    var quantity_num = $(this).val();
                    if (quantity_num*1 < 0) {
                        Toastr.error('合格数量不能小于0');
                        $(this).val(0);
                        return false;
                    }

                    var batch_id = $('.batch_id').val();
                    var arrivals_num = $(this).parent().prev().find('input').val();
                    //判断是否分批
                    if (batch_id) {
                        var true_num = $(this).parent().parent().find('.should_arrival_num').val();
                    } else {
                        var true_num = $(this).parent().parent().find('.purchase_num').val();
                    }
                    
                    if (quantity_num*1 > arrivals_num*1) {
                        $(this).val(0);
                        Toastr.error('合格数量不能大于到货数量');
                        return false;
                    }

                    if (arrivals_num * 1 > true_num * 1) {
                        var not_quantity_num = true_num * 1 - quantity_num * 1;
                    } else if (arrivals_num * 1 < true_num) {
                        var not_quantity_num = arrivals_num * 1 - quantity_num * 1;
                    } else {
                        var not_quantity_num = arrivals_num * 1 - quantity_num * 1;
                    }

                    if (not_quantity_num < 0) {
                        not_quantity_num = 0;
                    }

                    $(this).parent().next().next().find('input').val(not_quantity_num);
                    if (arrivals_num * 1 > 0) {
                        $(this).parent().next().next().next().find('input').val((quantity_num * 1 / arrivals_num * 100).toFixed(2));
                    }
                })

                //计算不合格数量及合格率
                $(document).on('blur', '.sample_num', function () {
                    var sample_num = $(this).val();
                    if (sample_num*1 < 0) {
                        Toastr.error('留样数量不能小于0');
                        $(this).val(0);
                        return false;
                    }
                    var quantity_num = $(this).parent().parent().find('.quantity_num').val();
                    if (sample_num*1 > quantity_num*1) {
                        $(this).val(0);
                        Toastr.error('样品数量不能大于合格数量');
                        return false;
                    }
                })



            
                //采购单
                $(document).on('change', '.purchase_id', function () {
                    var id = $(this).val();
                    console.log(id);
                    if (id) {
                        var url = Config.moduleurl + '/warehouse/check/getPurchaseData';
                        Backend.api.ajax({
                            url: url,
                            data: { id: id }
                        }, function (data, ret) {
                            console.log(1111);
                            console.log(data);
                            if ($('.supplier.selectpicker option').length > 1) {
                                $(".supplier").selectpicker('val', data.supplier_id);//默认选中
                            }

                            var batch_id = $('.batch_id').val();
                            if (!batch_id) {
                                var html = '';
                                if (data.batch) {
                                    for (var i in data.batch) {
                                        html += '<option value="' + data.batch[i].id + '">' + data.batch[i].batch + '</option>';
                                    }
                                }
                                $('.batch_id').append(html);
                            }


                            //循环展示商品信息
                            if (data.item) {
                                var shtml = ' <tr><th>SKU</th><th>供应商SKU</th><th>采购数量</th><th>已质检数量</th><th>到货数量</th><th>合格数量</th><th>留样数量</th><th>不合格数量</th><th>合格率</th><th>备注</th><th>上传图片</th><th>操作</th></tr>';
                                $('.caigou table tbody').html('');
                                $('#toolbar').hide();
                                for (var i in data.item) {
                                    var sku = data.item[i].sku;
                                    if (!sku) {
                                        sku = '';
                                    }

                                    var supplier_sku = data.item[i].supplier_sku;
                                    if (!supplier_sku) {
                                        supplier_sku = '';
                                    }

                                    shtml += ' <tr> <input  class="form-control error_type" name="error_type[]" type="hidden"><td><input id="c-purchase_remark" class="form-control sku" name="sku[]" readonly type="text" value="' + sku + '"></td>'
                                    shtml += ' <input id="c-purchase_remark" class="form-control" name="purchase_id[]" readonly type="hidden" value="' + data.id + '">'
                                    shtml += ' <td><input id="c-purchase_remark" class="form-control" name="supplier_sku[]" readonly type="text" value="' + supplier_sku + '"></td>'
                                    shtml += ' <td><input id="c-purchase_remark" class="form-control purchase_num" name="purchase_num[]" readonly type="text" redeonly value="' + data.item[i].purchase_num + '"></td>'
                                    shtml += ' <td><input id="c-purchase_remark" class="form-control check_num" name="check_num[]" type="text" readonly value="' + data.item[i].check_num + '"></td>'
                                    shtml += ' <td><input id="c-purchase_remark" class="form-control arrivals_num" name="arrivals_num[]" type="number"></td>'
                                    shtml += ' <td><input id="c-purchase_remark" class="form-control quantity_num" name="quantity_num[]" type="number"></td>'
                                    shtml += ' <td><input id="c-purchase_remark" class="form-control sample_num" name="sample_num[]" type="number"></td>'
                                    shtml += ' <td><input id="c-purchase_remark" class="form-control unqualified_num" name="unqualified_num[]" readonly type="text"></td>'
                                    shtml += '  <td><input id="c-purchase_remark" class="form-control quantity_rate" name="quantity_rate[]" readonly type="text">%</td>'
                                    shtml += ' <td><input style="width: 200px;" id="c-purchase_remark" class="form-control remark" name="remark[]" type="text"></td>'
                                    shtml += ' <td><input id="c-unqualified_images" style="width: 150px;" class="form-control unqualified_images" size="200" readonly name="unqualified_images[]" type="text"></td>'

                                    shtml += ' <td><span><button type="button" id="plupload-unqualified_images" class="btn btn-danger pluploads" data-input-id="c-unqualified_images" data-mimetype="image/gif,image/jpeg,image/png,image/jpg,image/bmp" data-multiple="true" data-maxcount="3" data-preview-id="p-unqualified_images"><i class="fa fa-upload"></i>'
                                    shtml += ' 上传</button></span>'

                                    shtml += ' <a href="javascript:;" class="btn btn-danger btn-del" title="删除"><i class="fa fa-trash"></i>删除</a>'
                                    shtml += ' </td>'

                                    shtml += ' </tr>'
                                }
                                $('.caigou table tbody').append(shtml);
                            }

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

                //采购单分批
                $(document).on('change', '.batch_id', function () {
                    var id = $(this).val();
                    if (id) {
                        var url = Config.moduleurl + '/warehouse/check/getItemData';
                        Backend.api.ajax({
                            url: url,
                            data: { id: id }
                        }, function (item, ret) {

                            //循环展示商品信息
                            if (item) {

                                if ($('.supplier.selectpicker option').length > 1) {
                                    $(".supplier").selectpicker('val', item[0].supplier_id);//默认选中
                                }

                                var shtml = ' <tr><th>SKU</th><th>供应商SKU</th><th>采购数量</th><th>已质检数量</th><th>应到货数量</th><th>到货数量</th><th>合格数量</th><th>留样数量</th><th>不合格数量</th><th>合格率</th><th>备注</th><th>上传图片</th><th>操作</th></tr>';
                                $('.caigou table tbody').html('');
                                $('#toolbar').hide();
                                for (var i in item) {
                                    var sku = item[i].sku;
                                    if (!sku) {
                                        sku = '';
                                    }
                                    var supplier_sku = item[i].supplier_sku;
                                    if (!supplier_sku) {
                                        supplier_sku = '';
                                    }

                                    shtml += ' <tr> <input  class="form-control error_type" name="error_type[]" type="hidden"><td><input id="c-purchase_remark" class="form-control sku" name="sku[]" readonly type="text" value="' + sku + '"></td>'
                                    shtml += ' <input id="c-purchase_remark" class="form-control" name="purchase_id[]" readonly type="hidden" value="' + item[i].purchase_id + '">'
                                    shtml += ' <td><input id="c-purchase_remark" class="form-control" name="supplier_sku[]" readonly type="text" value="' + supplier_sku + '"></td>'
                                    shtml += ' <td><input id="c-purchase_remark" class="form-control purchase_num" name="purchase_num[]" readonly type="text" redeonly value="' + item[i].purchase_num + '"></td>'
                                    shtml += ' <td><input id="c-purchase_remark" class="form-control check_num" name="check_num[]" type="text" readonly value="' + item[i].check_num + '"></td>'
                                    shtml += ' <td class="batch_arrival_num"><input class="form-control should_arrival_num" readonly name="should_arrival_num[]" type="text" value="' + item[i].arrival_num + '"></td>'
                                    shtml += ' <td><input id="c-purchase_remark" class="form-control arrivals_num" name="arrivals_num[]" type="number"></td>'
                                    shtml += ' <td><input id="c-purchase_remark" class="form-control quantity_num" name="quantity_num[]" type="number"></td>'
                                    shtml += ' <td><input id="c-purchase_remark" class="form-control sample_num" name="sample_num[]" type="number"></td>'
                                    shtml += ' <td><input id="c-purchase_remark" class="form-control unqualified_num" name="unqualified_num[]" readonly type="text"></td>'
                                    shtml += '  <td><input id="c-purchase_remark" class="form-control quantity_rate" name="quantity_rate[]" readonly type="text">%</td>'
                                    shtml += ' <td><input style="width: 200px;" id="c-purchase_remark" class="form-control remark" name="remark[]" type="text"></td>'
                                    shtml += ' <td><input id="c-unqualified_images" style="width: 150px;" class="form-control unqualified_images" size="200" readonly name="unqualified_images[]" type="text"></td>'

                                    shtml += ' <td><span><button type="button" id="plupload-unqualified_images" class="btn btn-danger pluploads" data-input-id="c-unqualified_images" data-mimetype="image/gif,image/jpeg,image/png,image/jpg,image/bmp" data-multiple="true" data-maxcount="3" data-preview-id="p-unqualified_images"><i class="fa fa-upload"></i>'
                                    shtml += ' 上传</button></span>'

                                    shtml += ' <a href="javascript:;" class="btn btn-danger btn-del" title="删除"><i class="fa fa-trash"></i>删除</a>'
                                    shtml += ' </td>'

                                    shtml += ' </tr>'
                                }
                                $('.caigou table tbody').append(shtml);
                            }

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
                    var supplier_id = $('.supplier.selectpicker').val();
                    var _this = $(this);
                    if (!sku) {
                        return false;
                    }
                    Backend.api.ajax({
                        url: 'ajax/getSkuList',
                        data: { sku: sku, supplier_id: supplier_id }
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