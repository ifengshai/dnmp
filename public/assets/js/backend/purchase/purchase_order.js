define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {

    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                searchFormVisible: true,
                pageSize: 10,
                pageList: [10, 25, 50, 100],
                extend: {
                    index_url: 'purchase/purchase_order/index' + location.search,
                    add_url: 'purchase/purchase_order/add',
                    edit_url: 'purchase/purchase_order/edit',
                    // del_url: 'purchase/purchase_order/del',
                    multi_url: 'purchase/purchase_order/multi',
                    table: 'purchase_order',
                }
            });

            var table = $("#table");

            // 初始化表格
            table.bootstrapTable({
                url: $.fn.bootstrapTable.defaults.extend.index_url,
                pk: 'id',
                sortName: 'createtime',
                sortOrder: 'desc',
                columns: [
                    [
                        { checkbox: true },
                        {
                            field: '', title: __('序号'), formatter: function (value, row, index) {
                                var options = table.bootstrapTable('getOptions');
                                var pageNumber = options.pageNumber;
                                var pageSize = options.pageSize;
                                return (pageNumber - 1) * pageSize + 1 + index;
                            }, operate: false
                        },
                        { field: 'id', title: __('Id'), operate: false, visible: false },
                        { field: 'purchase_number', title: __('Purchase_number') },
                        { field: 'purchase_name', title: __('Purchase_name') },
                        { field: 'product_total', title: __('Product_total'), operate: 'BETWEEN' },
                        { field: 'purchase_freight', title: __('Purchase_freight'), operate: 'BETWEEN' },
                        { field: 'purchase_total', title: __('Purchase_total'), operate: 'BETWEEN' },
                        {
                            field: 'purchase_status', title: __('Purchase_status'),
                            custom: { 0: 'success', 1: 'yellow', 2: 'blue', 3: 'danger', 4: 'gray', 5: 'yellow', 6: 'yellow', 7: 'success' },
                            searchList: { 0: '新建', 1: '待审核', 2: '已审核', 3: '已拒绝', 4: '已取消', 5: '待发货', 6: '待收货', 7: '已收货', 8: '已退款' },
                            addClass: 'selectpicker', data: 'multiple', operate: 'IN',
                            formatter: Table.api.formatter.status
                        },
                        {
                            field: 'payment_status', title: __('Payment_status'),
                            custom: { 1: 'danger', 2: 'blue', 3: 'success' },
                            searchList: { 1: '未付款', 2: '部分付款', 3: '已付款' },
                            formatter: Table.api.formatter.status
                        },
                        {
                            field: 'check_status', title: __('Check_status'),
                            custom: { 0: 'danger', 1: 'blue', 2: 'success' },
                            searchList: { 0: '未质检', 1: '部分质检', 2: '已质检' },
                            formatter: Table.api.formatter.status
                        },
                        {
                            field: 'stock_status', title: __('Stock_status'),
                            custom: { 0: 'danger', 1: 'blue', 2: 'success' },
                            searchList: { 0: '未入库', 1: '部分入库', 2: '已入库' },
                            formatter: Table.api.formatter.status
                        },
                        {
                            field: 'return_status', title: __('Return_status'),
                            custom: { 0: 'danger', 1: 'blue', 2: 'success' },
                            searchList: { 0: '未退回', 1: '部分退回', 2: '已退回' },
                            formatter: Table.api.formatter.status
                        },
                        {
                            field: 'is_add_logistics', title: __('Is_add_logistics'),
                            custom: { 0: 'danger', 1: 'success' },
                            searchList: { 0: '否', 1: '是' },
                            formatter: Table.api.formatter.status
                        },
                        {
                            field: 'is_new_product', title: __('Is_new_product'),
                            custom: { 0: 'danger', 1: 'success' },
                            searchList: { 0: '否', 1: '是' },
                            formatter: Table.api.formatter.status
                        },
                        { field: 'create_person', title: __('Create_person') },
                        { field: 'createtime', title: __('Createtime'), operate: 'RANGE', addclass: 'datetimerange' },
                        {
                            field: 'operate', title: __('Operate'), table: table, events: Table.api.events.operate, buttons: [
                                {
                                    name: 'submitAudit',
                                    text: '提交审核',
                                    title: __('提交审核'),
                                    classname: 'btn btn-xs btn-success btn-ajax',
                                    icon: 'fa fa-leaf',
                                    url: 'purchase/purchase_order/audit',
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
                                        if (row.purchase_status == 0) {
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
                                    classname: 'btn btn-xs  btn-primary btn-dialog',
                                    icon: 'fa fa-list',
                                    url: 'purchase/purchase_order/detail',
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
                                    url: 'purchase/purchase_order/cancel',
                                    callback: function (data) {
                                        Layer.alert("接收到回传数据：" + JSON.stringify(data), { title: "回传数据" });
                                    },
                                    visible: function (row) {
                                        //返回true时按钮显示,返回false隐藏
                                        if (row.purchase_status == 0) {
                                            return true;
                                        } else {
                                            return false;
                                        }

                                    }
                                },
                                {
                                    name: 'return',
                                    text: '退销',
                                    title: '退销',
                                    classname: 'btn btn-xs  btn-success  btn-dialog',
                                    icon: 'fa fa-plus',
                                    url: 'purchase/purchase_return/add',
                                    extend: 'data-area = \'["100%","100%"]\'',
                                    callback: function (data) {
                                        Layer.alert("接收到回传数据：" + JSON.stringify(data), { title: "回传数据" });
                                    },
                                    visible: function (row) {
                                        //返回true时按钮显示,返回false隐藏
                                        if (row.purchase_status == 7 && row.return_status != 2 && row.check_status != 0) {
                                            return true;
                                        } else {
                                            return false;
                                        }
                                    }
                                },
                                {
                                    name: 'detail',
                                    text: '录入物流单号',
                                    title: '录入物流单号',
                                    classname: 'btn btn-xs  btn-success  btn-dialog',
                                    icon: 'fa fa-plus',
                                    url: 'purchase/purchase_order/logistics',
                                    extend: 'data-area = \'["50%","60%"]\'',
                                    callback: function (data) {
                                        Layer.alert("接收到回传数据：" + JSON.stringify(data), { title: "回传数据" });
                                    },
                                    visible: function (row) {
                                        //返回true时按钮显示,返回false隐藏
                                        if (row.purchase_type == 2) {
                                            return false;
                                        }
                                        return true;
                                    }
                                },
                                {
                                    name: 'edit',
                                    text: '',
                                    title: __('Edit'),
                                    classname: 'btn btn-xs btn-success btn-dialog',
                                    icon: 'fa fa-pencil',
                                    url: 'purchase/purchase_order/edit',
                                    extend: 'data-area = \'["100%","100%"]\'',
                                    callback: function (data) {
                                        Layer.alert("接收到回传数据：" + JSON.stringify(data), { title: "回传数据" });
                                    },
                                    visible: function (row) {
                                         //返回true时按钮显示,返回false隐藏
                                         if (row.purchase_status == 0) {
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
                    url: Config.moduleurl + '/purchase/purchase_order/setStatus',
                    data: { ids: ids, status: 2 }
                }, function (data, ret) {
                    table.bootstrapTable('refresh');
                });
            })

            //审核拒绝
            $(document).on('click', '.btn-close', function () {
                var ids = Table.api.selectedids(table);
                Backend.api.ajax({
                    url: Config.moduleurl + '/purchase/purchase_order/setStatus',
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
                    data: { status: 4 }
                }, function (data, ret) {
                    table.bootstrapTable('refresh');
                });
            })

            //批量匹配SKU
            $(document).on('click', '.btn-matching', function (e) {
                e.preventDefault();
                var url = Config.moduleurl + '/purchase/purchase_order/matching';
                layer.load();
                Backend.api.ajax({
                    url: url,
                    data: {}
                }, function (data, ret) {
                    layer.closeAll();
                    //table.bootstrapTable('refresh');
                });
            })
        },
        add: function () {
            Controller.api.bindevent();
            $(document).on('click', '.btn-add', function () {
                var content = $('#table-content table tbody').html();
                $('.caigou table tbody').append(content);

                Controller.api.bindevent();
            })

            $(document).on('click', '.btn-del', function () {
                $(this).parent().parent().remove();
            })



            //异步获取供应商的数据
            $(document).on('change', '.supplier', function () {
                var id = $(this).val();
                Backend.api.ajax({
                    url: Config.moduleurl + '/purchase/contract/getSupplierData',
                    data: { id: id }
                }, function (data, ret) {
                    $('.supplier_address').val(data.address);
                });
            })


            if ($('.supplier').val()) {
                $('.supplier').change();
            }

        },
        edit: function () {
            Controller.api.bindevent();

            $(document).on('click', '.btn-add', function () {
                var content = $('#table-content table tbody').html();
                $('.caigou table tbody').append(content);


                Controller.api.bindevent();

            })


            //判断合同是否有默认值
            var contract_id = $('.contract_id').val();
            if (contract_id) {
                var url = Config.moduleurl + '/purchase/purchase_order/getContractData';
                Backend.api.ajax({
                    url: url,
                    data: { id: contract_id }
                }, function (data, ret) {
                    $('.contract_name').val(data.contract_name);
                    $('.delivery_address').val(data.delivery_address);
                    $('.delivery_stime').val(data.delivery_stime);
                    $('.delivery_etime').val(data.delivery_etime);
                    $('.contract_stime').val(data.contract_stime);
                    $('.contract_etime').val(data.contract_etime);
                    $('.contract_images').val(data.contract_images);
                    $('#c-contract_images').change();
                });
            }

            //删除商品数据
            $(document).on('click', '.btn-del', function () {
                var _this = $(this);
                var id = $(this).parent().parent().find('.item_id').val();
                if (id) {
                    Layer.confirm(__('确定删除此数据吗?'), function () {
                        _this.parent().parent().remove();
                        Backend.api.ajax({
                            url: Config.moduleurl + '/purchase/purchase_order/deleteItem',
                            data: { id: id }
                        }, function () {
                            Layer.closeAll();
                        });
                    });
                }
            })



        },
        logistics: function () {
            Controller.api.bindevent();
        },
        logisticsDetail: function () {
            Controller.api.bindevent();
        },
        checkdetail: function () {
            Controller.api.bindevent();
            //确认差异
            $(document).on('click', '.btn-add', function () {
                Layer.load();
                var id = $(this).data('id');
                if (id) {
                    Backend.api.ajax({
                        url: Config.moduleurl + '/purchase/purchase_order/confirmDiff',
                        data: { id: id }
                    }, function (data, ret) {
                        location.reload();
                    });
                }
            })

        },
        detail: function () {
            Controller.api.bindevent();
            //判断合同是否有默认值
            var contract_id = $('.contract_id').val();
            if (contract_id) {
                var url = Config.moduleurl + '/purchase/purchase_order/getContractData';
                Backend.api.ajax({
                    url: url,
                    data: { id: contract_id }
                }, function (data, ret) {
                    $('.contract_name').val(data.contract_name);
                    $('.delivery_address').val(data.delivery_address);
                    $('.delivery_stime').val(data.delivery_stime);
                    $('.delivery_etime').val(data.delivery_etime);
                    $('.contract_stime').val(data.contract_stime);
                    $('.contract_etime').val(data.contract_etime);
                    $('.contract_images').val(data.contract_images);
                    $('#c-contract_images').change();
                });
            }
        },
        api: {
            bindevent: function () {
                $(document).on('click', '.btn-status', function () {
                    $('.status').val(1);
                })
                Form.api.bindevent($("form[role=form]"));

                //切换合同 异步获取合同数据
                $(document).on('change', '.contract_id', function () {
                    var id = $(this).val();
                    if (id) {
                        var url = Config.moduleurl + '/purchase/purchase_order/getContractData';
                        Backend.api.ajax({
                            url: url,
                            data: { id: id }
                        }, function (data, ret) {
                            $('.contract_name').val(data.contract_name);
                            $('.delivery_address').val(data.delivery_address);
                            $('.delivery_stime').val(data.delivery_stime);
                            $('.delivery_etime').val(data.delivery_etime);
                            $('.contract_stime').val(data.contract_stime);
                            $('.contract_etime').val(data.contract_etime);
                            $('.contract_images').val(data.contract_images);
                            $('#c-contract_images').change();

                            $(".supplier").val(data.supplier_id);
                            $(".supplier_address").val(data.supplier_address);
                            $(".total").val(data.total);
                            $(".freight").val(data.freight);
                            $(".deposit_amount").val(data.deposit_amount);
                            $(".final_amount").val(data.final_amount);
                            $(".settlement_method").val(data.settlement_method);
                            $('.address').val(data.delivery_address);
                            if (data.settlement_method == 3) {
                                $('.deposit_amount').removeClass('hidden');
                                $('.final_amount').removeClass('hidden');
                            }

                            //总计
                            var purchase_total = data.total * 1 + data.freight * 1;
                            $('.purchase_total').val(purchase_total);


                            //循环展示商品信息
                            var shtml = ' <tr><th>SKU</td><th>产品名称</td><th>供应商sku</td><th>采购数量（个）</td><th>采购单价（元）</td><th>总价（元）</td><th>操作</td></tr>';
                            $('.caigou table tbody').html('');
                            for (var i in data.item) {
                                shtml += '<tr><td><input id="c-purchase_remark" class="form-control" name="sku[]" value="' + data.item[i].sku + '" type="text"></td>'
                                shtml += '<td><input id="c-purchase_remark" class="form-control" name="product_name[]" value="' + data.item[i].product_name + '" type="text"></td>'
                                shtml += '<td><input id="c-purchase_remark" class="form-control" name="supplier_sku[]" value="' + data.item[i].supplier_sku + '" type="text"></td>'
                                shtml += '<td><input id="c-purchase_remark" class="form-control" name="purchase_num[]" value="' + data.item[i].num + '" type="text"></td>'
                                shtml += '<td><input id="c-purchase_remark" class="form-control" name="purchase_price[]" value="' + data.item[i].price + '" type="text"></td>'
                                shtml += '<td><input id="c-purchase_remark" class="form-control" name="purchase_total[]" value="' + data.item[i].total + '" type="text"></td>'
                                shtml += '<td><a href="javascript:;" class="btn btn-danger btn-del" title="删除"><i class="fa fa-trash"></i> 删除</a></td>'
                                shtml += '</tr>'
                            }
                            $('.caigou table tbody').append(shtml);
                        });
                    }

                })


                //计算金额
                $('.purchase_num').blur(function () {

                    var purchase_num = $(this).val();
                    var purchase_price = $(this).parent().next().find('.purchase_price').val();
                    if (purchase_num * 1 > 0 && purchase_price * 1 > 0) {
                        $(this).parent().next().next().find('.goods_total').val(purchase_num * 1 * purchase_price);
                    }
                    var total = 0;
                    $('.goods_total').each(function () {
                        var purchase_total = $(this).val();
                        total += purchase_total * 1;
                    })
                    //商品总价
                    $('.total').val(total);
                    //运费
                    var freight = $('.freight').val();
                    //总计
                    $('.purchase_total').val(total + freight * 1);
                })

                $('.purchase_price').blur(function () {
                    var purchase_num = $(this).parent().prev().find('.purchase_num').val();
                    var purchase_price = $(this).val();
                    if (purchase_num * 1 > 0 && purchase_price * 1 > 0) {
                        $(this).parent().next().find('.goods_total').val(purchase_num * 1 * purchase_price);
                    }
                    var total = 0;
                    $('.goods_total').each(function () {
                        var purchase_total = $(this).val();
                        total += purchase_total * 1;
                    })
                    //商品总价
                    $('.total').val(total);
                    //运费
                    var freight = $('.freight').val();
                    //总计
                    $('.purchase_total').val(total + freight * 1);
                })

                //运费
                $('.freight').blur(function () {
                    var total = $('.total').val();
                    var freight = $(this).val();
                    //总计
                    $('.purchase_total').val(total * 1 + freight * 1);
                })
            }
        }
    };
    return Controller;
});