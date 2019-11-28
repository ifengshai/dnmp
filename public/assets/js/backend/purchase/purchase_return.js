define(['jquery', 'bootstrap', 'backend', 'table', 'form', 'jqui','bootstrap-table-jump-to'], function ($, undefined, Backend, Table, Form) {

    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                showJumpto: true,
                searchFormVisible: true,
                pageList: [10, 25, 50, 100],
                extend: {
                    index_url: 'purchase/purchase_return/index' + location.search,
                    add_url: 'purchase/purchase_return/add',
                    edit_url: 'purchase/purchase_return/edit',
                    // del_url: 'purchase/purchase_return/del',
                    multi_url: 'purchase/purchase_return/multi',
                    table: 'purchase_return',
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
                        { field: 'return_number', title: __('Return_number'), operate: 'like' },
                        { field: 'purchaseorder.purchase_number', title: __('Purchase_id'), operate: 'like' },
                        { field: 'supplier.supplier_name', title: __('Supplier_id'), operate: 'like' },
                        { field: 'return_type', title: __('Return_type'), custom: { 1: 'success', 2: 'success', 3: 'success' }, searchList: { 1: '仅退款', 2: '退货退款', 3: '调换货' }, formatter: Table.api.formatter.status },
                        {
                            field: 'status', title: __('status'),
                            custom: { 0: 'success', 1: 'yellow', 2: 'blue', 3: 'blue', 4: 'green', 5: 'gray' },
                            searchList: { 0: '新建', 1: '待发货', 2: '已发货', 3: '已核对', 4: '已退款', 5: '已取消' },
                            addClass: 'selectpicker', data: 'multiple', operate: 'IN',
                            formatter: Table.api.formatter.status
                        },
                        { field: 'supplier_linkname', title: __('Supplier_linkname'), operate: 'like' },
                        { field: 'supplier_linkphone', title: __('Supplier_linkphone'), operate: 'like' },
                        { field: 'supplier_address', title: __('Supplier_address'), operate: 'like' },
                        { field: 'createtime', title: __('Createtime'), operate: 'RANGE', addclass: 'datetimerange' },
                        { field: 'create_person', title: __('Create_person'), operate: 'like' },

                        {
                            field: 'operate', title: __('Operate'), table: table, events: Table.api.events.operate, buttons: [
                                {
                                    name: 'detail',
                                    text: '详情',
                                    title: __('Detail'),
                                    classname: 'btn btn-xs  btn-primary  btn-dialog',
                                    icon: 'fa fa-list',
                                    url: 'purchase/purchase_return/detail',
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
                                    name: 'edit',
                                    text: '',
                                    title: __('Edit'),
                                    classname: 'btn btn-xs btn-success btn-dialog',
                                    icon: 'fa fa-pencil',
                                    url: 'purchase/purchase_return/edit',
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
                                    name: 'cancel',
                                    text: '取消',
                                    title: '取消',
                                    classname: 'btn btn-xs btn-danger btn-cancel',
                                    icon: 'fa fa-remove',
                                    url: 'purchase/purchase_return/cancel',
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


                            ], formatter: Table.api.formatter.operate
                        }
                    ]
                ]
            });

            // 为表格绑定事件
            Table.api.bindevent(table);

            //提交
            $(document).on('click', '.btn-open', function () {
                var ids = Table.api.selectedids(table);
                Backend.api.ajax({
                    url: Config.moduleurl + '/purchase/purchase_return/setStatus',
                    data: { ids: ids, status: 1 }
                }, function (data, ret) {
                    table.bootstrapTable('refresh');
                });
            })

            //已核对
            $(document).on('click', '.btn-close', function () {
                var ids = Table.api.selectedids(table);
                Backend.api.ajax({
                    url: Config.moduleurl + '/purchase/purchase_return/setStatus',
                    data: { ids: ids, status: 3 }
                }, function (data, ret) {
                    table.bootstrapTable('refresh');
                });
            })

            //审核取消
            $(document).on('click', '.btn-cancel', function (e) {
                e.preventDefault();
                var url = $(this).attr('href');
                Backend.api.ajax({
                    url: url,
                    data: { status: 5 }
                }, function (data, ret) {
                    table.bootstrapTable('refresh');
                });
            })
        },
        add: function () {
            Controller.api.bindevent();
            //移除
            $(document).on('click', '.btn-del', function () {
                $(this).parent().parent().remove();
                
                var all_price = 0;
                $('.return_num').each(function () {
                    var num = $(this).val();
                    var price = $(this).data('price');
                    all_price = all_price + num * 1 * price * 1;
                })
                $('.return_money').val(all_price);
            })

            $(document).on('blur', '.return_num', function () {
                var all_price = 0;
                $('.return_num').each(function () {
                    var num = $(this).val();
                    var price = $(this).data('price');
                    all_price = all_price + num * 1 * price * 1;
                })
                $('.return_money').val(all_price);
            })
            if ($('.purchase_id').val()) {
                $('.purchase_id').change();
            }
        },
        edit: function () {
            Controller.api.bindevent();

            //删除商品数据
            $(document).on('click', '.btn-del', function () {
                $(this).parent().parent().remove();
                var id = $(this).parent().parent().find('.item_id').val();
                if (id) {
                    Backend.api.ajax({
                        url: Config.moduleurl + '/purchase/purchase_return/deleteItem',
                        data: { id: id }
                    });
                }
            })
        },
        detail: function () {
            Controller.api.bindevent();
        },
        api: {
            bindevent: function () {
                Form.api.bindevent($("form[role=form]"));

                //切换合同 异步获取合同数据
                $(document).on('change', '.purchase_id', function () {
                    var id = $(this).val();
                    if (id) {
                        var url = Config.moduleurl + '/purchase/purchase_return/getPurchaseData';
                        Backend.api.ajax({
                            url: url,
                            data: { id: id }
                        }, function (data, ret) {

                            $('.supplier').val(data.supplier_id);
                            $('.purchase_total').val(data.purchase_total);
                            //循环展示商品信息
                            var shtml = ' <tr><th>SKU</th><th>采购单价</th><th>供应商SKU</th><th>采购数量</th><th>到货数量</th><th>合格数量</th><th>不合格数量</th><th>合格率</th><th>退销数量</th><th>操作</th></tr>';
                            $('.caigou table tbody').html('');
                            var return_money = 0;
                            for (var i in data.item) {
                                return_money += data.item[i].purchase_price*data.item[i].unqualified_num;

                                var sku = data.item[i].sku;
                                if (!sku) {
                                    sku = '';
                                }

                                // var num = data.item[i].purchase_num * 1 - data.item[i].arrivals_num * 1;

                                var supplier_sku = data.item[i].supplier_sku ?　data.item[i].supplier_sku : '';
                                var percent = data.item[i].arrivals_num > 0 ? Math.round(data.item[i].quantity_num / data.item[i].arrivals_num * 100) : 0;
                                shtml += '<tr><input  class="form-control" name="item_id[]" type="hidden" readonly value="' + data.item[i].ids + '">'
                                shtml += ' <td><input id="c-purchase_remark" class="form-control sku" name="sku[]" type="text" readonly value="' + sku + '"></td>'
                               
                                shtml += ' <td><input id="c-purchase_remark" class="form-control" disabled  type="text" value="' + data.item[i].purchase_price + '"></td>'
                                shtml += ' <td><input id="c-purchase_remark" class="form-control" disabled type="text" value="' + supplier_sku + '"></td>'
                                shtml += ' <td><input id="c-purchase_remark" class="form-control purchase_num" disabled type="text" redeonly value="' + data.item[i].purchase_num + '"></td>'
                                shtml += ' <td><input id="c-purchase_remark" class="form-control arrivals_num" disabled type="text" value="' + data.item[i].arrivals_num + '"></td>'
                                // shtml += ' <td><input id="c-purchase_remark" class="form-control arrivals_num" disabled  type="text" value="' + num + '"></td>'
                                shtml += ' <td><input id="c-purchase_remark" class="form-control quantity_num" disabled type="text" value="' + data.item[i].quantity_num + '"></td>'
                                shtml += ' <td><input id="c-purchase_remark" class="form-control unqualified_num" disabled  type="text" value="' + data.item[i].unqualified_num + '"></td>'
                                shtml += ' <td><input id="c-purchase_remark" class="form-control sample_num" disabled  type="text" value="' + percent + '%' + '"></td>'
                                //shtml += ' <td><input  id="c-purchase_remark" class="form-control" disabled  type="text" value="' + data.item[i].return_num + '"></td>'

                                shtml += ' <td><input id="c-return_num"  class="form-control return_num" data-price="' + data.item[i].purchase_price + '" size="200" readonly  name="return_num[]" type="text" value="' + data.item[i].unqualified_num + '"></td>'
                                shtml += ' <td><a href="javascript:;" class="btn btn-danger btn-del" title="删除"><i class="fa fa-trash"></i>删除</a>'
                                shtml += ' </td>'
                                shtml += ' </tr>'

                            }
                            $('.return_money').val(return_money);
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

                        }, function (data, ret) {
                            $('.layer-footer').find('.btn-success').addClass('disabled');

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
                        _this.parent().parent().find('.product_name').val(data.name);
                        _this.parent().parent().find('.supplier_sku').val(data.supplier_sku);
                    }, function (data, ret) {
                        Fast.api.error(ret.msg);
                    });

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
            }
        }
    };
    return Controller;
});