define(['jquery', 'bootstrap', 'backend', 'table', 'form', 'jqui', 'bootstrap-table-jump-to', 'toastr'], function ($, undefined, Backend, Table, Form, undefined, undefined, Toastr) {

    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                showJumpto: true,
                searchFormVisible: true,
                pageSize: 10,
                pageList: [10, 25, 50, 100],
                extend: {
                    index_url: 'purchase/purchase_order/index' + location.search,
                    add_url: 'purchase/purchase_order/add',
                    edit_url: 'purchase/purchase_order/edit',
                    // del_url: 'purchase/purchase_order/del',
                    multi_url: 'purchase/purchase_order/multi',
                    import_url: 'purchase/purchase_order/logistics_info_import',
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
                        {checkbox: true},
                        {
                            field: '', title: __('序号'), formatter: function (value, row, index) {
                                var options = table.bootstrapTable('getOptions');
                                var pageNumber = options.pageNumber;
                                var pageSize = options.pageSize;
                                return (pageNumber - 1) * pageSize + 1 + index;
                            }, operate: false
                        },
                        {field: 'id', title: __('Id'), operate: false, visible: false},
                        {field: 'purchase_number', title: __('Purchase_number'), operate: 'like'},
                        {field: 'purchase_name', title: __('Purchase_name'), operate: 'like'},
                        {field: 'supplier.supplier_name', title: __('供应商名称'), operate: 'like'},
                        {field: 'product_total', title: __('Product_total'), operate: false},
                        {field: 'purchase_freight', title: __('Purchase_freight'), operate: false},
                        {field: 'purchase_total', title: __('Purchase_total'), operate: false},
                        {
                            field: 'purchase_remark',
                            title: __('采购备注'),
                            formatter: Controller.api.formatter.getClear,
                            operate: false
                        },
                        {field: 'sku', title: __('sku'), operate: 'like', visible: false},
                        {
                            field: 'purchase_status', title: __('Purchase_status'),
                            custom: {
                                0: 'success',
                                1: 'yellow',
                                2: 'blue',
                                3: 'danger',
                                4: 'gray',
                                5: 'yellow',
                                6: 'yellow',
                                7: 'success',
                                8: 'success',
                                9: 'success',
                                10: 'success'
                            },
                            searchList: {
                                0: '新建',
                                1: '待审核',
                                2: '已审核',
                                3: '已拒绝',
                                4: '已取消',
                                5: '待发货',
                                6: '待收货',
                                7: '已收货',
                                8: '已退款',
                                9: '部分签收',
                                10: '已完成'
                            },
                            addClass: 'selectpicker', data: 'multiple', operate: 'IN',
                            formatter: Table.api.formatter.status
                        },
                        {
                            field: 'payment_status', title: __('Payment_status'),
                            custom: {1: 'danger', 2: 'blue', 3: 'success'},
                            searchList: {1: '未付款', 2: '部分付款', 3: '已付款'},
                            formatter: Table.api.formatter.status
                        },
                        {
                            field: 'check_status', title: __('Check_status'),
                            custom: {0: 'danger', 1: 'blue', 2: 'success'},
                            searchList: {0: '未质检', 1: '部分质检', 2: '已质检'},
                            formatter: Table.api.formatter.status
                        },
                        {
                            field: 'stock_status', title: __('Stock_status'),
                            custom: {0: 'danger', 1: 'blue', 2: 'success'},
                            searchList: {0: '未入库', 1: '部分入库', 2: '已入库'},
                            formatter: Table.api.formatter.status
                        },
                        {
                            field: 'return_status', title: __('Return_status'),
                            custom: {0: 'danger', 1: 'blue', 2: 'success'},
                            searchList: {0: '未退回', 1: '部分退回', 2: '已退回'},
                            formatter: Table.api.formatter.status
                        },
                        {
                            field: 'is_add_logistics', title: __('Is_add_logistics'),
                            custom: {0: 'danger', 1: 'success'},
                            searchList: {0: '否', 1: '是'},
                            formatter: Table.api.formatter.status
                        },
                        {
                            field: 'is_new_product', title: __('是否为新品采购单'),
                            custom: {0: 'danger', 1: 'success'},
                            searchList: {0: '否', 1: '是'},
                            formatter: Table.api.formatter.status
                        },
                        {
                            field: 'is_sample', title: __('是否为留样采购单'),
                            custom: {0: 'danger', 1: 'success'},
                            searchList: {0: '否', 1: '是'},
                            formatter: Table.api.formatter.status
                        },
                        {field: 'arrival_time', title: __('预计出货时间'), operate: 'RANGE', addclass: 'datetimerange'},
                        {
                            field: 'factory_type', title: __('工厂类型'),
                            custom: {0: 'danger', 1: 'success'},
                            searchList: {0: '工厂', 1: '贸易'},
                            formatter: Table.api.formatter.status
                        },
                        {field: 'create_person', title: __('Create_person'), operate: 'like'},
                        {field: 'createtime', title: __('Createtime'), operate: 'RANGE', addclass: 'datetimerange'},
                        {
                            field: 'operate',
                            title: __('Operate'),
                            table: table,
                            events: Table.api.events.operate,
                            buttons: [

                                {
                                    name: 'detail',
                                    text: '详情',
                                    title: __('Detail'),
                                    classname: 'btn btn-xs  btn-primary btn-dialog',
                                    icon: 'fa fa-list',
                                    url: 'purchase/purchase_order/detail',
                                    extend: 'data-area = \'["100%","100%"]\'',
                                    callback: function (data) {
                                        Layer.alert("接收到回传数据：" + JSON.stringify(data), {title: "回传数据"});
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
                                        Layer.alert("接收到回传数据：" + JSON.stringify(data), {title: "回传数据"});
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
                                        Layer.alert("接收到回传数据：" + JSON.stringify(data), {title: "回传数据"});
                                    },
                                    visible: function (row) {
                                        //返回true时按钮显示,返回false隐藏
                                        if ((row.purchase_status == 6 || row.purchase_status == 7) && row.check_status > 0) {
                                            return false;
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
                                    url: 'purchase/purchase_order/edit',
                                    extend: 'data-area = \'["100%","100%"]\'',
                                    callback: function (data) {
                                        Layer.alert("接收到回传数据：" + JSON.stringify(data), {title: "回传数据"});
                                    },
                                    visible: function (row) {
                                        //返回true时按钮显示,返回false隐藏
                                        var arr = [0, 1];
                                        if (arr.includes(row.purchase_status) || row.purchase_type == 2) {
                                            return true;
                                        } else {
                                            return false;
                                        }
                                    }
                                },
                                {
                                    name: 'pay',
                                    text: '创建付款申请单',
                                    title: '创建付款申请单',
                                    extend: 'data-area = \'["100%", "100%"]\' data-shade = \'[0.3, "#393D49"]\'',
                                    icon: 'fa fa-plus',
                                    classname: 'btn btn-xs btn-info btn-dialog',
                                    icon: 'fa fa-remove',
                                    url: 'financepurchase/purchase_pay/add/label/purchase',
                                    callback: function (data) {
                                        Layer.alert("接收到回传数据：" + JSON.stringify(data), {title: "回传数据"});
                                    },
                                    visible: function (row) {
                                        //返回true时按钮显示,返回false隐藏
                                        if (row.can_create_pay == 1 && row.purchase_status == 2  && row.pay_type !=3) {
                                            return true;
                                        } else {
                                            return false;
                                        }

                                    }
                                },
                                /* {
                                    name: 'check',
                                    text: '去质检',
                                    title: __('质检'),
                                    classname: 'btn btn-xs btn-success btn-dialog',
                                    icon: 'fa fa-plus',
                                    url: 'warehouse/check/add/purchase_id/{id}',
                                    extend: 'data-area = \'["100%","100%"]\'',
                                    callback: function (data) {
                                        Layer.alert("接收到回传数据：" + JSON.stringify(data), { title: "回传数据" });
                                    },
                                    visible: function (row) {
                                        //返回true时按钮显示,返回false隐藏
                                        if (row.purchase_status == 6 || row.purchase_status == 7) {
                                            return true;
                                        } else {
                                            return false;
                                        }
                                    }
                                } */

                            ],
                            formatter: Table.api.formatter.operate
                        }
                    ]
                ]
            });

            // 为表格绑定事件
            Table.api.bindevent(table);

            //批量导出xls 
            $('.btn-batch-export-xls').click(function () {
                var ids = Table.api.selectedids(table);
                if (ids.length > 0) {
                    window.open(Config.moduleurl + '/purchase/purchase_order/batch_export_xls?ids=' + ids, '_blank');
                } else {
                    var options = table.bootstrapTable('getOptions');
                    var search = options.queryParams({});
                    var filter = search.filter;
                    var op = search.op;
                    window.open(Config.moduleurl + '/purchase/purchase_order/batch_export_xls?filter=' + filter + '&op=' + op, '_blank');
                }

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

            $(document).on('click', ".btn-remark", function () {
                var ids = Table.api.selectedids(table);
                if (ids.length > 1) {
                    Toastr.error('添加备注只能选择一个采购单');
                    return false;
                }
                var url = 'purchase/purchase_order/remark?ids=' + ids;
                Fast.api.open(url, __('添加备注'), {area: ['900px', '500px']});

                return false;
            });

            $(document).on('click', ".btn-logistics", function () {
                var ids = Table.api.selectedids(table);

                var url = 'purchase/purchase_order/logistics?ids=' + ids;
                Fast.api.open(url, __('录入物流单号'), {area: ['60%', '80%']});

                return false;
            });

            //审核通过
            $(document).on('click', '.btn-open', function () {
                var ids = Table.api.selectedids(table);
                Backend.api.ajax({
                    url: Config.moduleurl + '/purchase/purchase_order/setStatus',
                    data: {ids: ids, status: 2}
                }, function (data, ret) {
                    table.bootstrapTable('refresh');
                });
            })

            //审核拒绝
            $(document).on('click', '.btn-close', function () {
                var ids = Table.api.selectedids(table);
                Backend.api.ajax({
                    url: Config.moduleurl + '/purchase/purchase_order/setStatus',
                    data: {ids: ids, status: 3}
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
                    data: {status: 4}
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
                    table.bootstrapTable('refresh');
                });
            })


            //批量创建付款申请单
            $(document).on('click', ".btn-pay-order", function () {
                var ids = Table.api.selectedids(table);
                if (ids.length <= 0) {
                    Toastr.error('至少选择一个采购单');
                    return false;
                }
                layer.load();
                var url = 'financepurchase/purchase_pay/is_conditions';
                Backend.api.ajax({
                    url: url,
                    data: {
                        'ids':ids
                    }
                }, function (data, ret) {
                    var url = 'financepurchase/purchase_pay/batch_add?ids=' + ids;
                    Fast.api.open(url, __('创建付款申请单'), {area: ['900px', '500px']});
                });


               
                return false;
            });
        },
        add: function () {
            Controller.api.bindevent();
            var z = 0;
            $(document).on('click', '.btn-add', function () {
                var content = $('#table-content table tbody').html();
                $('.purchase-table tbody').append(content);

                Controller.api.bindevent();
            })

            $(document).on('click', '.btn-del', function () {

                $(this).parent().parent().remove();
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

            $(document).on('click', '.btn-arrival-del', function () {
                $(this).parent().remove();
                z--;
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
                    data: {sku: sku, supplier_id: supplier_id}
                }, function (data, ret) {
                    _this.parent().parent().find('.product_name').val(data.name);
                    _this.parent().parent().find('.supplier_sku').val(data.supplier_sku);
                }, function (data, ret) {
                    Fast.api.error(ret.msg);
                });
            })
            $(document).on('change','#factory_type',function (){
                var fac_val = $('#factory_type').val();
                if (fac_val ==0){
                    $('#is_first').show();
                    $('#time').show();
                    $('#is_first_purchase').show();
                    $('#1688_number').hide();
                    $('#customized_procurement').show();
                }else{
                    $('#is_first').hide();
                    $('#time').hide();
                    $('#1688_number').show();
                    $('#customized_procurement').hide();
                }
            });
            $(document).on('change','#pay_type',function (){
                var pay_type = $('#pay_type').val();
                if (pay_type ==1){
                    $('#pay_rate').show();
                }else{
                    $('#pay_rate').hide();
                }
            });
            $(document).on('click','.tijiao',function (){
               $('#is_new_product').attr("disabled",false);
            });
            $(document).on('click','.tijaio1',function (){
               $('#is_new_product').attr("disabled",false);
            })

            $(document).on('click', '.btn-addplus', function () {
                // var content = $('#arrival-content').html();
                // $('#arrival_div').append(content);
                var html = '<div class="list" style="margin-top:20px;">\n' +
                    '<input type="hidden" name="row[is_batch]" value="1">' +
                    '    <label class="control-label col-xs-12 col-sm-2" style="width:150px;">批次预计到货时间：</label>\n' +
                    '    <div class="col-xs-12 col-sm-3" style="margin-bottom: 20px;">\n' +
                    '        <input id="c-arrival_time" class="form-control datetimepicker arrival_time" data-rule="required" data-date-format="YYYY-MM-DD HH:mm:ss" data-use-current="true" name="batch_arrival_time[]" type="text" value="' + Config.newdatetime + '">\n' +
                    '    </div>\n' +
                    '    <a href="javascript:;" class="btn btn-danger btn-arrival-del" title="删除"><i class="fa fa-trash"></i>删除</a>\n' +
                    '    <table id="caigou-table" style="width:80%;">\n' +
                    '        <tr>\n' +
                    '            <th>SKU</td>\n' +
                    '            <th>到货数量</td>\n' +
                    '        </tr>\n';
                var els = $('.purchase-table').find('.sku');

                for (var i = 0, j = els.length; i < j; i++) {
                    var sku = els[i].value;
                    html += '<tr>\n' +
                        '            <td>\n' +
                        '                <input id="c-purchase_remark" class="form-control" readonly name="batch_sku[' + z + '][' + i + ']" value="' + sku + '" type="text">\n' +
                        '            </td>\n' +
                        '            <td><input id="c-purchase_remark" class="form-control arrival_num"  name="arrival_num[' + z + '][' + i + ']" type="text" min="1"></td>\n' +
                        '        </tr>\n';

                }
                z++;
                html += '    </table>\n' +
                    '</div>';
                $('#arrival_div').append(html);

                Controller.api.bindevent();
            })


            //异步获取供应商的数据
            $(document).on('change', '.supplier.selectpicker', function () {
                var id = $(this).val();
                Backend.api.ajax({
                    url: Config.moduleurl + '/purchase/contract/getSupplierData',
                    data: {id: id}
                }, function (data, ret) {
                    $('.supplier_address').val(data.address);
                });
            })

            if ($('.supplier.selectpicker').val()) {

                $('.supplier.selectpicker').change();
            }

            //切换合同 异步获取合同数据
            $(document).on('change', '.contract_id', function () {
                var id = $(this).val();
                if (id) {
                    var url = Config.moduleurl + '/purchase/purchase_order/getContractData';
                    Backend.api.ajax({
                        url: url,
                        data: {id: id}
                    }, function (data, ret) {
                        $('.contract_name').val(data.contract_name);
                        $('.delivery_address').val(data.delivery_address);
                        $('.delivery_stime').val(data.delivery_stime);
                        $('.delivery_etime').val(data.delivery_etime);
                        $('.contract_stime').val(data.contract_stime);
                        $('.contract_etime').val(data.contract_etime);
                        $('.contract_images').val(data.contract_images);
                        $('#c-contract_images').change();

                        $(".supplier").selectpicker('val', data.supplier_id);//默认选中
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

                        $('.freight').attr("readonly", "readonly");
                        ;

                        //总计
                        var purchase_total = data.total * 1 + data.freight * 1;
                        $('.purchase_total').val(purchase_total);


                        //循环展示商品信息
                        var shtml = ' <tr><th>SKU</td><th>产品名称</td><th>供应商sku</td><th>采购数量（个）</td><th>采购单价（元）</td><th>总价（元）</td></tr>';
                        $('.purchase-table tbody').html('');
                        $('#toolbar').remove();
                        for (var i in data.item) {
                            var sku = data.item[i].sku;
                            if (!sku) {
                                sku = '';
                            }
                            shtml += '<tr><td><input id="c-purchase_remark" class="form-control sku" name="sku[]" readonly value="' + sku + '" type="text"></td>'
                            shtml += '<td><input id="c-purchase_remark" class="form-control product_name" readonly name="product_name[]" value="' + data.item[i].product_name + '" type="text"></td>'
                            shtml += '<td><input id="c-purchase_remark" class="form-control supplier_sku" readonly name="supplier_sku[]" value="' + data.item[i].supplier_sku + '" type="text"></td>'
                            shtml += '<td><input id="c-purchase_remark" class="form-control purchase_num" readonly name="purchase_num[]" value="' + data.item[i].num + '" type="text"></td>'
                            shtml += '<td><input id="c-purchase_remark" class="form-control purchase_price" readonly name="purchase_price[]" value="' + data.item[i].price + '" type="text"></td>'
                            shtml += '<td><input id="c-purchase_remark" class="form-control goods_total" readonly name="purchase_total[]" value="' + data.item[i].total + '" type="text"></td>'
                            // shtml += '<td><a href="javascript:;" class="btn btn-danger btn-del" title="删除"><i class="fa fa-trash"></i> 删除</a></td>'
                            shtml += '</tr>'
                        }
                        $('.purchase-table tbody').append(shtml);

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

        },
        edit: function () {
            Controller.api.bindevent();

            $(document).on('click', '.btn-add', function () {
                var content = $('#table-content table tbody').html();
                $('.caigou table tbody').append(content);
                Controller.api.bindevent();

            })
            var z = $('.arrival_time').length - 1;
            $(document).on('click', '.btn-arrival-del', function () {
                $(this).parent().remove();
                z--;
            })
            $(document).on('change','#factory_type',function (){
                var fac_val = $('#factory_type').val();
                if (fac_val ==0){
                    $('#is_first').show();
                    $('#is_first_purchase').show();
                    $('#1688_number').hide();
                    $('#time').show();
                    $('#customized_procurement').show();
                }else{
                    $('#is_first').hide();
                    $('#1688_number').show();
                    $('#time').hide();
                    $('#customized_procurement').hide();
                }
            });
            $(document).on('click', '.btn-addplus', function () {
                // var content = $('#arrival-content').html();
                // $('#arrival_div').append(content);
                var html = '<div class="list" style="margin-top:20px;">\n' +
                    '<input type="hidden" name="row[is_batch]" value="1">' +
                    '    <label class="control-label col-xs-12 col-sm-2" style="width:150px;">批次预计到货时间：</label>\n' +
                    '    <div class="col-xs-12 col-sm-3" style="margin-bottom: 20px;">\n' +
                    '        <input id="c-arrival_time" class="form-control datetimepicker arrival_time" data-rule="required" data-date-format="YYYY-MM-DD HH:mm:ss" data-use-current="true" name="batch_arrival_time[]" type="text" value="">\n' +
                    '    </div>\n' +
                    '    <a href="javascript:;" class="btn btn-danger btn-arrival-del" title="删除"><i class="fa fa-trash"></i>删除</a>\n' +
                    '    <table id="caigou-table" style="width:80%;">\n' +
                    '        <tr>\n' +
                    '            <th>SKU</td>\n' +
                    '            <th>到货数量</td>\n' +
                    '        </tr>\n';
                var els = $('.purchase-table').find('.sku');

                for (var i = 0, j = els.length; i < j; i++) {
                    var sku = els[i].value;
                    html += '<tr>\n' +
                        '            <td>\n' +
                        '                <input id="c-purchase_remark" class="form-control" readonly name="batch_sku[' + z + '][' + i + ']" value="' + sku + '" type="text">\n' +
                        '            </td>\n' +
                        '            <td><input id="c-purchase_remark" class="form-control arrival_num"  name="arrival_num[' + z + '][' + i + ']" type="text"></td>\n' +
                        '        </tr>\n';

                }
                z++;
                html += '    </table>\n' +
                    '</div>';
                $('#arrival_div').append(html);

                Controller.api.bindevent();
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
                    data: {sku: sku, supplier_id: supplier_id}
                }, function (data, ret) {
                    _this.parent().parent().find('.product_name').val(data.name);
                    _this.parent().parent().find('.supplier_sku').val(data.supplier_sku);
                }, function (data, ret) {
                    Fast.api.error(ret.msg);
                });

            })

            //切换合同 异步获取合同数据
            $(document).on('change', '.contract_id', function () {
                var id = $(this).val();
                if (id) {
                    var url = Config.moduleurl + '/purchase/purchase_order/getContractData';
                    Backend.api.ajax({
                        url: url,
                        data: {id: id}
                    }, function (data, ret) {
                        $('.contract_name').val(data.contract_name);
                        $('.delivery_address').val(data.delivery_address);
                        $('.delivery_stime').val(data.delivery_stime);
                        $('.delivery_etime').val(data.delivery_etime);
                        $('.contract_stime').val(data.contract_stime);
                        $('.contract_etime').val(data.contract_etime);
                        $('.contract_images').val(data.contract_images);
                        $('#c-contract_images').change();

                        $(".supplier").selectpicker('val', data.supplier_id);//默认选中
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

                        $('.freight').attr("readonly", "readonly");
                        ;

                        //总计
                        var purchase_total = data.total * 1 + data.freight * 1;
                        $('.purchase_total').val(purchase_total);


                        //循环展示商品信息
                        var shtml = ' <tr><th>SKU</td><th>产品名称</td><th>供应商sku</td><th>采购数量（个）</td><th>采购单价（元）</td><th>总价（元）</td></tr>';
                        $('.caigou table tbody').html('');
                        $('#toolbar').remove();
                        for (var i in data.item) {
                            var sku = data.item[i].sku;
                            if (!sku) {
                                sku = '';
                            }
                            shtml += '<tr><td><input id="c-purchase_remark" class="form-control sku" name="sku[]" readonly value="' + sku + '" type="text"></td>'
                            shtml += '<td><input id="c-purchase_remark" class="form-control product_name" readonly name="product_name[]" value="' + data.item[i].product_name + '" type="text"></td>'
                            shtml += '<td><input id="c-purchase_remark" class="form-control supplier_sku" readonly name="supplier_sku[]" value="' + data.item[i].supplier_sku + '" type="text"></td>'
                            shtml += '<td><input id="c-purchase_remark" class="form-control purchase_num" readonly name="purchase_num[]" value="' + data.item[i].num + '" type="text"></td>'
                            shtml += '<td><input id="c-purchase_remark" class="form-control purchase_price" readonly name="purchase_price[]" value="' + data.item[i].price + '" type="text"></td>'
                            shtml += '<td><input id="c-purchase_remark" class="form-control goods_total" readonly name="purchase_total[]" value="' + data.item[i].total + '" type="text"></td>'
                            // shtml += '<td><a href="javascript:;" class="btn btn-danger btn-del" title="删除"><i class="fa fa-trash"></i> 删除</a></td>'
                            shtml += '</tr>'
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


            //判断合同是否有默认值
            var contract_id = $('.contract_id').val();
            if (contract_id) {
                var url = Config.moduleurl + '/purchase/purchase_order/getContractData';
                Backend.api.ajax({
                    url: url,
                    data: {id: contract_id}
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
                            data: {id: id}
                        }, function () {
                            Layer.closeAll();
                        });
                    });
                }
            })


        },
        logistics: function () {
            Controller.api.bindevent();
            $(document).on('click', '.btn-add', function () {
                var shtml = $('#logistics_div').html();
                $(this).parent().next().append(shtml);
            })

            $(document).on('click', '.btn-add-batch', function () {
                var batch_id = $(this).parent().next('.batch_id').val();
                var shtml = '<div class="form-group">' +
                    '<label class="control-label col-xs-12 col-sm-2">物流公司编码:</label>' +
                    '<div class="col-xs-12 col-sm-3">' +
                    '<input id="c-logistics_company_no" class="form-control" name="logistics_company_no[' + batch_id + '][]" value="" type="text" placeholder="请输入对应的物流公司编码">' +
                    '</div>' +
                    '<label class="control-label col-xs-12 col-sm-2">物流单号:</label>' +
                    '<div class="col-xs-12 col-sm-3">' +
                    '<input id="c-logistics_number" class="form-control" name="logistics_number[' + batch_id + '][]" type="text" placeholder="请输入物流单号">' +
                    '</div>' +
                    '<a href="javascript:;" class="btn btn-danger btn-del" title="删除"><i class="fa fa-trash"></i>删除</a>' +
                    '</div>';
                $(this).parent().next().next().append(shtml);
            })

            // $(document).on('click', '.btn-del', function () {
            //     $(this).parent().remove();
            // })

            //删除商品数据
            $(document).on('click', '.btn-del', function () {
                var id = $(this).parent().find('.logistics_ids').val();
                var _this = $(this);
                if (id) {
                    Layer.confirm(
                        __('确定要删除吗'),
                        function (index) {
                            Backend.api.ajax({
                                url: Config.moduleurl + '/purchase/purchase_order/deleteLogisticsItem',
                                data: {id: id}
                            }, function (data, ret) {
                                _this.parent().remove();
                                Layer.closeAll();
                            });
                        }
                    );

                } else {
                    $(this).parent().remove();
                }
            })

        },
        remark: function () {
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
                        data: {id: id}
                    }, function (data, ret) {
                        location.reload();
                    });
                }
            })

            //上传文件
            $(document).on('click', '.pluploads', function () {
                var _this = $(this);
                var url = $(this).attr('data-url');
                Fast.api.open(
                    'warehouse/check/uploads?img_url=' + url, '上传文件', {
                        callback: function (data) {
                            _this.parent().parent().parent().find('.unqualified_images').val(data.unqualified_images);
                        }
                    }
                )
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
                    data: {id: contract_id}
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
        product_grade_list: function () {
            // 初始化表格参数配置
            Table.api.init({
                showJumpto: true,
                searchFormVisible: true,
                pageSize: 10,
                pageList: [10, 25, 50, 100, 500, 2000],
                extend: {
                    index_url: 'purchase/purchase_order/product_grade_list' + location.search,
                    table: 'product_grade_list',
                }
            });

            var table = $("#table");

            // 初始化表格
            table.bootstrapTable({
                url: $.fn.bootstrapTable.defaults.extend.index_url,
                pk: 'id',
                sortName: 'num',
                sortOrder: 'desc',
                columns: [
                    [
                        {checkbox: true},
                        {
                            field: '', title: __('序号'), formatter: function (value, row, index) {
                                var options = table.bootstrapTable('getOptions');
                                var pageNumber = options.pageNumber;
                                var pageSize = options.pageSize;
                                return (pageNumber - 1) * pageSize + 1 + index;
                            }, operate: false
                        },
                        {field: 'supplier_name', title: __('供应商名称'), operate: 'like'},
                        {field: 'purchase_person', title: __('采购创建人'), operate: 'like'},
                        {field: 'true_sku', title: __('SKU'), operate: 'like'},
                        {field: 'grade', title: __('等级'), operate: 'like'},
                        {field: 'zeelool_sku', title: __('Zeelool_Sku'), operate: 'like'},
                        {field: 'voogueme_sku', title: __('Voogueme_Sku'), operate: 'like'},
                        {field: 'nihao_sku', title: __('Nihao_Sku'), operate: 'like'},
                        {
                            field: 'counter', title: __('总销量'), operate: false, formatter: function (value, rows) {
                                return rows.days + '天:' + rows.counter;
                            }
                        },

                        {field: 'num', title: __('30天预估销量'), operate: false},
                        {field: 'days_sales_num', title: __('日均销量'), operate: false},
                        {field: 'replenish_days', title: __('预估售卖天数'), operate: false},
                        {field: 'stock', title: __('可用库存'), operate: false},
                        {field: 'purchase_qty', title: __('在途库存'), operate: false},
                        {field: 'replenish_num', title: __('建议补货量'), operate: false},
                        {field: 'created_at', title: __('上架时间'), operate: 'RANGE', addclass: 'datetimerange'},

                    ]
                ]
            });


            // 为表格绑定事件
            Table.api.bindevent(table);
        },
        process: function () {
            // 初始化表格参数配置
            Table.api.init({
                showJumpto: true,
                searchFormVisible: true,
                pageList: [10, 25, 50, 100],
                extend: {
                    index_url: 'purchase/purchase_order/process' + location.search,
                    table: 'check_order',
                }
            });

            var table = $("#table");

            // 初始化表格
            table.bootstrapTable({
                url: $.fn.bootstrapTable.defaults.extend.index_url,
                pk: 'id',
                sortName: 'check.createtime',
                columns: [
                    [
                        {checkbox: true},
                        {field: 'id', title: __('Id')},
                        {field: 'check_order_number', title: __('质检单号'), operate: 'like'},
                        {field: 'purchaseorder.purchase_number', title: __('采购单号'), operate: 'like'},
                        {field: 'purchaseorder.create_person', title: __('采购创建人'), operate: 'like'},
                        {field: 'supplier.supplier_name', title: __('供应商'), operate: 'like'},
                        {
                            field: 'supplier.supplier_type_pattern',
                            title: __('供应商类型'),
                            searchList: {1: '工厂', 2: '贸易'},
                            formatter: Controller.api.formatter.supplier_type_pattern
                        },
                        {
                            field: 'remark',
                            title: __('质检备注'),
                            formatter: Controller.api.formatter.getClear,
                            operate: false
                        },
                        {field: 'createtime', title: __('Createtime'), operate: 'RANGE', addclass: 'datetimerange'},
                        {field: 'create_person', title: __('Create_person')},
                        {field: 'sku', title: __('sku'), operate: 'like', visible: false},

                        {
                            field: 'operate',
                            title: __('Operate'),
                            table: table,
                            events: Table.api.events.operate,
                            buttons: [

                                {
                                    name: 'detail',
                                    text: '详情',
                                    title: __('Detail'),
                                    classname: 'btn btn-xs  btn-primary  btn-dialog',
                                    icon: 'fa fa-list',
                                    url: 'warehouse/check/detail',
                                    extend: 'data-area = \'["100%","100%"]\'',
                                    callback: function (data) {
                                        Layer.alert("接收到回传数据：" + JSON.stringify(data), {title: "回传数据"});
                                    },
                                    visible: function (row) {
                                        //返回true时按钮显示,返回false隐藏
                                        return true;
                                    }
                                }

                            ],
                            formatter: Table.api.formatter.operate
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
                    data: {ids: ids, status: 2}
                }, function (data, ret) {
                    table.bootstrapTable('refresh');
                });
            })

            //审核拒绝
            $(document).on('click', '.btn-close', function () {
                var ids = Table.api.selectedids(table);
                Backend.api.ajax({
                    url: Config.moduleurl + '/warehouse/check/setStatus',
                    data: {ids: ids, status: 3}
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
                    data: {status: 4}
                }, function (data, ret) {
                    table.bootstrapTable('refresh');
                });
            })


            //批量生成退销单
            $(document).on('click', '.btn-matching', function () {
                var ids = Table.api.selectedids(table);

                Backend.api.open('warehouse/check/add_return_order/ids/' + ids, '批量生成退销单', {area: ["60%", "60%"]});

            });

            //批量导出xls
            $('.btn-batch-export-xls').click(function () {
                var ids = Table.api.selectedids(table);
                if (ids.length > 0) {
                    window.open(Config.moduleurl + '/purchase/purchase_order/process_export_xls?ids=' + ids, '_blank');
                } else {
                    var options = table.bootstrapTable('getOptions');
                    var search = options.queryParams({});
                    var filter = search.filter;
                    var op = search.op;
                    window.open(Config.moduleurl + '/purchase/purchase_order/process_export_xls?filter=' + filter + '&op=' + op, '_blank');
                }

            });





        },
        api: {
            bindevent: function () {
                $(document).on('click', '.btn-status', function () {
                    $('.status').val(1);
                })
                Form.api.bindevent($("form[role=form]"));


                //计算金额
                $(document).on('blur', '.purchase_num', function () {

                    var purchase_num = $(this).val();
                    var purchase_price = $(this).parent().next().find('.purchase_price').val();
                    if (purchase_num * 1 > 0 && purchase_price * 1 > 0) {
                        $(this).parent().next().next().find('.goods_total').val((purchase_num * 1 * purchase_price));
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
                    // $('.purchase_total').val(total + freight * 1);
                    $('.purchase_total').val((total + freight * 1));
                })

                $(document).on('blur', '.purchase_price', function () {
                    var purchase_num = $(this).parent().prev().find('.purchase_num').val();
                    var purchase_price = $(this).val();
                    if (purchase_num * 1 > 0 && purchase_price * 1 > 0) {
                        // $(this).parent().next().find('.goods_total').val(purchase_num * 1 * purchase_price);
                        $(this).parent().next().find('.goods_total').val((purchase_num * 1 * purchase_price));

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
                    // $('.purchase_total').val(total + freight * 1);
                    $('.purchase_total').val((total + freight * 1));

                })

                //运费
                $('.freight').blur(function () {
                    var total = $('.total').val();
                    var freight = $(this).val();
                    //总计
                    $('.purchase_total').val(total * 1 + freight * 1);
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

                //选中的开始时间和现在的时间比较
                $(document).on('dp.change', '.delivery_stime', function () {
                    var time_value = $(this).val();
                    var end_time = $('.delivery_etime').val();

                    function getNow(s) {
                        return s < 10 ? '0' + s : s;
                    }

                    var myDate = new Date();

                    var year = myDate.getFullYear();        //获取当前年
                    var month = myDate.getMonth() + 1;   //获取当前月
                    var date = myDate.getDate();            //获取当前日
                    var h = myDate.getHours();              //获取当前小时数(0-23)
                    var m = myDate.getMinutes() - 10;          //获取当前分钟数(0-59)
                    var s = myDate.getSeconds();
                    var now = year + '-' + getNow(month) + "-" + getNow(date) + " " + getNow(h) + ':' + getNow(m) + ":" + getNow(s);


                    if (time_value > end_time) {
                        Layer.alert('开始时间不能大于结束时间！！');
                        $(this).val(now);
                        return false;
                    }

                });


                //选中的开始时间和现在的时间比较
                $(document).on('dp.change', '.contract_stime', function () {
                    var time_value = $(this).val();
                    var end_time = $('.contract_etime').val();

                    function getNow(s) {
                        return s < 10 ? '0' + s : s;
                    }

                    var myDate = new Date();

                    var year = myDate.getFullYear();        //获取当前年
                    var month = myDate.getMonth() + 1;   //获取当前月
                    var date = myDate.getDate();            //获取当前日
                    var h = myDate.getHours();              //获取当前小时数(0-23)
                    var m = myDate.getMinutes() - 10;          //获取当前分钟数(0-59)
                    var s = myDate.getSeconds();
                    var now = year + '-' + getNow(month) + "-" + getNow(date) + " " + getNow(h) + ':' + getNow(m) + ":" + getNow(s);


                    if (time_value > end_time) {
                        Layer.alert('开始时间不能大于结束时间！！');
                        $(this).val(now);
                        return false;
                    }

                });


                //结算方式
                $(document).on('click', '.settlement_method', function () {
                    var val = $(this).val();
                    if (val == 3) {
                        $('.deposit_amount').removeClass('hidden');
                        $('.final_amount').removeClass('hidden');
                    } else {
                        $('.deposit_amount').addClass('hidden');
                        $('.final_amount').addClass('hidden');
                    }
                })


            },
            formatter: {
                supplier_type_pattern: function (value, row, index) {
                    var str = '';
                    if (value == 1) {
                        str = '工厂';
                    } else if (value == 2) {
                        str = '贸易';
                    }
                    return str;
                },
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

            }

        },
        account_purchase_order: function () {
            // 初始化表格参数配置
            Table.api.init({
                showJumpto: true,
                searchFormVisible: true,
                pageSize: 10,
                pageList: [10, 25, 50, 100],
                extend: {
                    index_url: 'purchase/purchase_order/account_purchase_order' + location.search,
                    add_url: '',
                    edit_url: '',
                    // del_url: 'purchase/purchase_order/del',
                    multi_url: '',
                    table: 'purchase_order',
                }
            });

            var table = $("#table");
            table.on('load-success.bs.table', function (e, data) {
                //这里可以获取从服务端获取的JSON数据
                //这里我们手动设置底部的值
                //console.log(data.total_money);
                $("#total-money").text(Math.round(data.total_money * 100) / 100);
                $("#return-money").text(Math.round(data.return_money * 100) / 100);

            });
            // 初始化表格
            table.bootstrapTable({
                url: $.fn.bootstrapTable.defaults.extend.index_url,
                pk: 'id',
                sortName: 'createtime',
                sortOrder: 'desc',
                columns: [
                    [
                        {checkbox: true},
                        {
                            field: '', title: __('序号'), formatter: function (value, row, index) {
                                var options = table.bootstrapTable('getOptions');
                                var pageNumber = options.pageNumber;
                                var pageSize = options.pageSize;
                                return (pageNumber - 1) * pageSize + 1 + index;
                            }, operate: false
                        },
                        {field: 'id', title: __('Id'), operate: false, visible: false},
                        {field: 'purchase_number', title: __('Purchase_number'), operate: 'like'},
                        {field: 'purchase_name', title: __('Purchase_name'), operate: 'like'},
                        {field: 'supplier.supplier_name', title: __('供应商')},
                        {field: 'purchase_total', title: __('Purchase_total'), operate: 'BETWEEN'},
                        {field: 'purchase_virtual_total', title: __('实际采购金额（元）'), operate: 'BETWEEN'},
                        {field: 'refund_amount', title: __('退款金额（元）'), operate: false},
                        {field: 'purchase_settle_money', title: __('采购结算金额（元）')},
                        //{ field: 'purchase_freight', title: __('邮费（元）') },
                        {field: 'payment_money', title: __('已付款金额')},
                        {
                            field: 'purchase_status', title: __('Purchase_status'),
                            custom: {
                                0: 'success',
                                1: 'yellow',
                                2: 'blue',
                                3: 'danger',
                                4: 'gray',
                                5: 'yellow',
                                6: 'yellow',
                                7: 'success'
                            },
                            searchList: {
                                0: '新建',
                                1: '待审核',
                                2: '已审核',
                                3: '已拒绝',
                                4: '已取消',
                                5: '待发货',
                                6: '待收货',
                                7: '已收货',
                                8: '已退款'
                            },
                            addClass: 'selectpicker', data: 'multiple', operate: 'IN',
                            formatter: Table.api.formatter.status
                        },
                        {
                            field: 'settlement_method', title: __('Settlement_method'),
                            custom: {1: 'bule', 2: 'yellow', 3: 'gray'},
                            searchList: {1: '先付款', 2: '货到付款', 3: '付定金 货到付款'},
                            formatter: Table.api.formatter.status
                        },
                        {
                            field: 'payment_status', title: __('Payment_status'),
                            custom: {1: 'danger', 2: 'blue', 3: 'success'},
                            searchList: {1: '未付款', 2: '部分付款', 3: '已付款'},
                            formatter: Table.api.formatter.status
                        },
                        {field: 'create_person', title: __('Create_person'), operate: 'like'},
                        {field: 'createtime', title: __('Createtime'), operate: 'RANGE', addclass: 'datetimerange'},
                        {field: 'pay_person', title: __('付款人'), visible: false},
                        {
                            field: 'pay_time',
                            title: __('付款时间'),
                            operate: 'RANGE',
                            addclass: 'datetimerange',
                            visible: false
                        },
                        {
                            field: 'operate',
                            title: __('Operate'),
                            table: table,
                            events: Table.api.events.operate,
                            buttons: [
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
                                    //url: "purchase/purchase_order/account_purchase_order_detail?purchase_virtual_total="+row.refund_amount,
                                    url: function (row) {
                                        //实际采购金额（元）
                                        var purchase_virtual_total = 0;
                                        if (row.purchase_virtual_total) {
                                            purchase_virtual_total = row.purchase_virtual_total;
                                        }

                                        //退款金额
                                        var refund_amount = 0;
                                        if (row.refund_amount) {
                                            refund_amount = row.refund_amount;
                                        }

                                        //退款金额
                                        var purchase_settle_money = 0;
                                        if (row.purchase_settle_money) {
                                            purchase_settle_money = row.purchase_settle_money;
                                        }

                                        return "purchase/purchase_order/account_purchase_order_detail?purchase_virtual_total=" + purchase_virtual_total + "&refund_amount=" + refund_amount + "&purchase_settle_money=" + purchase_settle_money + "&ids=" + row.id;
                                    },
                                    extend: 'data-area = \'["100%","100%"]\'',
                                    callback: function (data) {
                                        Layer.alert("接收到回传数据：" + JSON.stringify(data), {title: "回传数据"});
                                    },
                                    visible: function (row) {
                                        //返回true时按钮显示,返回false隐藏
                                        return true;
                                    }
                                },
                                {
                                    name: 'return',
                                    text: '付款',
                                    title: '付款',
                                    classname: 'btn btn-xs  btn-success  btn-dialog',
                                    icon: 'fa fa-plus',
                                    url: 'purchase/purchase_order/purchase_order_pay',
                                    extend: 'data-area = \'["60%","60%"]\'',
                                    callback: function (data) {
                                        Layer.alert("接收到回传数据：" + JSON.stringify(data), {title: "回传数据"});
                                    },
                                    visible: function (row) {
                                        return true;
                                    }
                                },
                                {
                                    name: 'detail',
                                    text: '确认退款',
                                    title: '确认退款',
                                    classname: 'btn btn-xs btn-danger btn-ajax',
                                    icon: 'fa fa-plus',
                                    url: 'purchase/purchase_order/purchase_order_affirm_refund',
                                    confirm: '确认退款吗',
                                    success: function (data, ret) {
                                        //Layer.alert(ret.msg);
                                        table.bootstrapTable('refresh');
                                        //如果需要阻止成功提示，则必须使用return false;
                                        return false;
                                    },
                                    error: function (data, ret) {
                                        Layer.alert(ret.msg);
                                        return false;
                                    },
                                    visible: function (row) {
                                        console.log(row.refund_amount);
                                        //返回true时按钮显示,返回false隐藏
                                        // if ((row.purchase_status == 8) || (row.payment_status == 1) || (row.refund_amount == 0)) {
                                        //     return false;
                                        // }
                                        if ((row.refund_amount <= 0) || (!row.refund_amount)) {
                                            return false;
                                        }
                                        return true;
                                    }
                                }

                            ],
                            formatter: Table.api.formatter.operate
                        }
                    ]
                ]
            });

            // 为表格绑定事件
            Table.api.bindevent(table);
        },
        purchase_order_pay: function () {
            Form.api.bindevent($("form[role=form]"));
        }
    };
    return Controller;
});