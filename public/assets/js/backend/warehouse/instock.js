define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {

    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                searchFormVisible: true,
                extend: {
                    index_url: 'warehouse/instock/index' + location.search,
                    add_url: 'warehouse/instock/add',
                    edit_url: 'warehouse/instock/edit',
                    del_url: 'warehouse/instock/del',
                    multi_url: 'warehouse/instock/multi',
                    table: 'in_stock',
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
                        { field: 'in_stock_number', title: __('In_stock_number') },
                        { field: 'type_id', title: __('In_stock_type') },
                        { field: 'purchase_id', title: __('Purchase_id') },
                        { field: 'order_number', title: __('Order_number') },
                        { field: 'remark', title: __('Remark') },
                        { field: 'status', title: __('Status') },
                        { field: 'createtime', title: __('Createtime'), operate: 'RANGE', addclass: 'datetimerange' },
                        { field: 'create_person', title: __('Create_person') },
                        { field: 'operate', title: __('Operate'), table: table, events: Table.api.events.operate, formatter: Table.api.formatter.operate }
                    ]
                ]
            });

            // 为表格绑定事件
            Table.api.bindevent(table);
        },
        add: function () {
            Controller.api.bindevent();
            //移除
            $(document).on('click', '.btn-del', function () {
                $(this).parent().parent().remove();
            })

            //添加
            $(document).on('click', '.btn-add', function () {
                var content = $('#table-content table tbody').html();
                $('.caigou table tbody').append(content);
            })
        },
        edit: function () {
            Controller.api.bindevent();
        },
        api: {
            bindevent: function () {
                Form.api.bindevent($("form[role=form]"));

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

                //切换合同 异步获取合同数据
                $(document).on('change', '.purchase_id', function () {
                    var id = $(this).val();
                    if (id) {
                        var url = '/admin/warehouse/instock/getPurchaseData';
                        Backend.api.ajax({
                            url: url,
                            data: { id: id }
                        }, function (data, ret) {
                            //循环展示商品信息
                            var shtml = ' <tr><th>SKU</th><th>产品名称</th><th>供应商SKU</th><th>采购数量</th><th>在途数量</th><th>到货数量</th><th>质检合格数量</th><th>留样数量</th><th>入库数量</th><th>操作</th></tr>';
                            $('.caigou table tbody').html('');
                            for (var i in data.item) {
                                shtml += ' <tr><td><input id="c-purchase_remark" class="form-control" name="sku[]" type="text" value="' + data.item[i].sku + '"></td>'
                                shtml += ' <td>' + data.item[i].product_name + '</td>'
                                shtml += ' <td>' + data.item[i].supplier_sku + '</td>'
                                shtml += ' <td>' + data.item[i].purchase_num + '</td>'
                                shtml += ' <td>' + (data.item[i].purchase_num - data.item[i].arrivals_num) + '</td>'
                                shtml += ' <td>' + data.item[i].arrivals_num + '</td>'
                                shtml += ' <td>' + data.item[i].quantity_num + '</td>'
                                shtml += ' <td>' + data.item[i].sample_num + '</td>'
                                shtml += ' <td><input id="c-in_stock_num" class="form-control" name="in_stock_num[]" type="text"></td>'
                                shtml += ' <td>'
                                shtml += ' <a href="javascript:;" class="btn btn-danger btn-del" title="删除"><i class="fa fa-trash"></i>删除</a>'
                                shtml += ' </td>'
                                shtml += ' </tr>'
                            }
                            $('.caigou table tbody').append(shtml);
                        });
                    }

                })
            }
        }
    };
    return Controller;
});