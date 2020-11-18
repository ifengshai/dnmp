define(['jquery', 'bootstrap', 'backend', 'table', 'form', 'bootstrap-table-jump-to'], function ($, undefined, Backend, Table, Form) {

    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                showJumpto: true,
                searchFormVisible: true,
                pageList: [10, 25, 50, 100],
                extend: {
                    index_url: 'warehouse/purchase_return/index' + location.search,
                    multi_url: 'warehouse/purchase_return/multi',
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
                        { field: 'return_type', title: __('Return_type'), custom: { 1: 'success', 2: 'success', 3: 'success', 4: 'success' }, searchList: { 1: '仅退款', 2: '退货退款', 3: '调换货', 4: '仅退货' }, formatter: Table.api.formatter.status },
                        {
                            field: 'status', title: __('status'),
                            custom: { 0: 'success', 1: 'yellow', 2: 'blue', 3: 'blue', 4: 'green', 5: 'gray' },
                            searchList: { 0: '新建', 1: '待发货', 2: '已发货', 3: '已核对', 4: '已退款', 5: '已取消' },
                            addClass: 'selectpicker', data: 'multiple', operate: 'IN',
                            formatter: Table.api.formatter.status
                        },
                        { field: 'createtime', title: __('Createtime'), operate: 'RANGE', addclass: 'datetimerange' },
                        { field: 'create_person', title: __('Create_person') },
                        {
                            field: 'operate', title: __('Operate'), table: table, events: Table.api.events.operate, buttons: [
                                {
                                    name: 'detail',
                                    text: '详情',
                                    title: __('Detail'),
                                    classname: 'btn btn-xs  btn-primary  btn-dialog',
                                    icon: 'fa fa-list',
                                    url: 'warehouse/purchase_return/detail',
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
                                    name: 'detail',
                                    text: '录入物流单号',
                                    title: '录入物流单号',
                                    classname: 'btn btn-xs  btn-success  btn-dialog',
                                    icon: 'fa fa-plus',
                                    url: 'warehouse/purchase_return/logistics',
                                    extend: 'data-area = \'["50%","60%"]\'',
                                    callback: function (data) {
                                        Layer.alert("接收到回传数据：" + JSON.stringify(data), { title: "回传数据" });
                                    },
                                    visible: function (row) {
                                        //返回true时按钮显示,返回false隐藏
                                        return true;
                                    }
                                },

                            ], formatter: Table.api.formatter.operate
                        }
                    ]
                ]
            });

            // 为表格绑定事件
            Table.api.bindevent(table);

            //打印
            $('.print').click(function () {
                var ids = Table.api.selectedids(table);
                console.log(ids);
                if (ids.length <= 0) {
                    Layer.alert('请先选择单据！！');
                    return false;
                }
                window.open(Config.moduleurl + '/warehouse/purchase_return/print?ids=' + ids.join(','), '_blank');
            })

            //批量导出xls 
            $('.btn-batch-export-xls').click(function () {
                var ids = Table.api.selectedids(table);
                if (ids.length > 0) {
                    window.open(Config.moduleurl + '/purchase/purchase_return/batch_export_xls?ids=' + ids, '_blank');
                } else {
                    var options = table.bootstrapTable('getOptions');
                    var search = options.queryParams({});
                    var filter = search.filter;
                    var op = search.op;
                    window.open(Config.moduleurl + '/purchase/purchase_return/batch_export_xls?filter=' + filter + '&op=' + op, '_blank');
                }

            });

            //批量录入物流单号
            $(document).on('click', ".btn-logistics", function () {
                var ids = Table.api.selectedids(table);
                if (ids.length <= 0) {
                    Layer.alert('请先选择单据！！');
                    return false;
                }
                var url = 'warehouse/purchase_return/logistics/do_type/1?ids=' + ids;
                Fast.api.open(url, __('录入物流单号'), {area: ['50%', '60%']});
            });

        },
        add: function () {
            Controller.api.bindevent();
            //移除
            $(document).on('click', '.btn-del', function () {
                $(this).parent().parent().remove();
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
                        url: Config.moduleurl + '/warehouse/purchase_return/deleteItem',
                        data: { id: id }
                    });
                }
            })
        },
        detail: function () {
            Controller.api.bindevent();
        },
        logistics: function () {
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
                            var shtml = ' <tr><th>SKU</th><th>产品名称</th><th>供应商SKU</th><th>采购数量</th><th>到货数量</th><th>合格数量</th><th>不合格数量</th><th>合格率</th><th>已退数量</th><th>退销数量</th><th>操作</th></tr>';
                            $('.caigou table tbody').html('');
                            for (var i in data.item) {
                                var sku = data.item[i].sku;
                                if (!sku) {
                                    sku = '';
                                }

                                // var num = data.item[i].purchase_num * 1 - data.item[i].arrivals_num * 1;
                                shtml += ' <tr><td><input id="c-purchase_remark" class="form-control" name="sku[]" type="text" value="' + sku + '"></td>'
                                shtml += ' <td><input id="c-purchase_remark" class="form-control" disabled  type="text" value="' + data.item[i].product_name + '"></td>'
                                shtml += ' <td><input id="c-purchase_remark" class="form-control" disabled type="text" value="' + data.item[i].supplier_sku + '"></td>'
                                shtml += ' <td><input id="c-purchase_remark" class="form-control purchase_num" disabled type="text" redeonly value="' + data.item[i].purchase_num + '"></td>'
                                shtml += ' <td><input id="c-purchase_remark" class="form-control arrivals_num" disabled type="text" value="' + data.item[i].arrivals_num + '"></td>'
                                // shtml += ' <td><input id="c-purchase_remark" class="form-control arrivals_num" disabled  type="text" value="' + num + '"></td>'
                                shtml += ' <td><input id="c-purchase_remark" class="form-control quantity_num" disabled type="text" value="' + data.item[i].quantity_num + '"></td>'
                                shtml += ' <td><input id="c-purchase_remark" class="form-control unqualified_num" disabled  type="text" value="' + data.item[i].unqualified_num + '"></td>'
                                shtml += ' <td><input id="c-purchase_remark" class="form-control sample_num" disabled  type="text" value="' + Math.round(data.item[i].quantity_num / data.item[i].arrivals_num * 100) + '%' + '"></td>'
                                shtml += ' <td><input  id="c-purchase_remark" class="form-control" disabled  type="text" value="' + data.item[i].return_num + '"></td>'

                                shtml += ' <td><input id="c-return_num"  class="form-control return_num" data-price="' + data.item[i].purchase_price + '" size="200"  name="return_num[]" type="text" ></td>'
                                shtml += ' <td><a href="javascript:;" class="btn btn-danger btn-del" title="删除"><i class="fa fa-trash"></i>删除</a>'
                                shtml += ' </td>'
                                shtml += ' </tr>'
                            }
                            $('.caigou table tbody').append(shtml);
                        }, function (data, ret) {
                            $('.layer-footer').find('.btn-success').addClass('disabled');
                        });
                    }

                })
            }
        }
    };
    return Controller;
});