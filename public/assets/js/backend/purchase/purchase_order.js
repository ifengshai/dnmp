define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {

    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                searchFormVisible: true,
                extend: {
                    index_url: 'purchase/purchase_order/index' + location.search,
                    add_url: 'purchase/purchase_order/add',
                    edit_url: 'purchase/purchase_order/edit',
                    del_url: 'purchase/purchase_order/del',
                    multi_url: 'purchase/purchase_order/multi',
                    table: 'purchase_order',
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
                        { field: 'purchase_number', title: __('Purchase_number') },
                        { field: 'purchase_name', title: __('Purchase_name') },
                        { field: 'purchase_remark', title: __('Purchase_remark') },
                        { field: 'contract_id', title: __('Contract_id') },
                        { field: 'supplier_id', title: __('Supplier_id') },
                        { field: 'item_id', title: __('Item_id') },
                        { field: 'create_person', title: __('Create_person') },
                        { field: 'createtime', title: __('Createtime'), operate: 'RANGE', addclass: 'datetimerange' },
                        { field: 'updatetime', title: __('Updatetime'), operate: 'RANGE', addclass: 'datetimerange' },
                        { field: 'product_total', title: __('Product_total'), operate: 'BETWEEN' },
                        { field: 'purchase_freight', title: __('Purchase_freight'), operate: 'BETWEEN' },
                        { field: 'purchase_total', title: __('Purchase_total'), operate: 'BETWEEN' },
                        { field: 'settlement_method', title: __('Settlement_method') },
                        { field: 'deposit_amount', title: __('Deposit_amount'), operate: 'BETWEEN' },
                        { field: 'final_amount', title: __('Final_amount'), operate: 'BETWEEN' },
                        { field: 'delivery_stime', title: __('Delivery_stime'), operate: 'RANGE', addclass: 'datetimerange' },
                        { field: 'delivery_etime', title: __('Delivery_etime'), operate: 'RANGE', addclass: 'datetimerange' },
                        { field: 'delivery_address', title: __('Delivery_address') },
                        { field: 'purchase_status', title: __('Purchase_status') },
                        { field: 'is_add_logistics', title: __('Is_add_logistics') },
                        { field: 'is_new_product', title: __('Is_new_product') },
                        { field: 'payment_status', title: __('Payment_status') },
                        { field: 'payment_images', title: __('Payment_images'), events: Table.api.events.image, formatter: Table.api.formatter.images },
                        { field: 'payment_money', title: __('Payment_money'), operate: 'BETWEEN' },
                        { field: 'payment_time', title: __('Payment_time'), operate: 'RANGE', addclass: 'datetimerange' },
                        { field: 'payment_remark', title: __('Payment_remark') },
                        { field: 'payment_person', title: __('Payment_person') },
                        { field: 'check_status', title: __('Check_status') },
                        { field: 'stock_status', title: __('Stock_status') },
                        { field: 'return_status', title: __('Return_status') },
                        { field: 'operate', title: __('Operate'), table: table, events: Table.api.events.operate, formatter: Table.api.formatter.operate }
                    ]
                ]
            });

            // 为表格绑定事件
            Table.api.bindevent(table);
        },
        add: function () {
            Controller.api.bindevent();
            $(document).on('click', '.btn-add', function () {
                var content = $('#table-content table tbody').html();
                $('.caigou table tbody').append(content);
            })

            $(document).on('click', '.btn-del', function () {
                $(this).parent().parent().remove();
            })


            //切换合同 异步获取合同数据
            $(document).on('change', '.contract_id', function () {
                var id = $(this).val();
                var url = '/admin/purchase/purchase_order/getContractData';
                Backend.api.ajax({
                    url: url,
                    data: {id:id}
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
                    var purchase_total = data.total *1 + data.freight*1;
                    $('.purchase_total').val(purchase_total);


                    //循环展示商品信息
                    var shtml = ' <tr><th>SKU</td><th>产品名称</td><th>供应商sku</td><th>采购数量（个）</td><th>采购单价（元）</td><th>总价（元）</td><th>操作</td></tr>';
                    $('.caigou table tbody').html('');
                    for(var i in data.item) {
                        shtml += '<tr><td><input id="c-purchase_remark" class="form-control" name="sku[]" value="'+ data.item[i].sku +'" type="text"></td>'
                        shtml += '<td><input id="c-purchase_remark" class="form-control" name="product_name[]" value="'+ data.item[i].product_name +'" type="text"></td>'
                        shtml += '<td><input id="c-purchase_remark" class="form-control" name="supplier_sku[]" value="'+ data.item[i].supplier_sku +'" type="text"></td>'
                        shtml += '<td><input id="c-purchase_remark" class="form-control" name="purchase_num[]" value="'+ data.item[i].num +'" type="text"></td>'
                        shtml += '<td><input id="c-purchase_remark" class="form-control" name="purchase_price[]" value="'+ data.item[i].price +'" type="text"></td>'
                        shtml += '<td><input id="c-purchase_remark" class="form-control" name="purchase_total[]" value="'+ data.item[i].total +'" type="text"></td>'
                        shtml += '<td><a href="javascript:;" class="btn btn-danger btn-del" title="删除"><i class="fa fa-trash"></i> 删除</a></td>'
                        shtml += '</tr>'
                    }
                    $('.caigou table tbody').append(shtml);
                });
            })


            //异步获取供应商的数据
            $(document).on('change', '.supplier', function () {
                var id = $(this).val();
                Backend.api.ajax({
                    url: '/admin/purchase/contract/getSupplierData',
                    data: { id: id }
                }, function (data, ret) {
                    $('.supplier_address').val(data.address);    
                });
            })
        },
        edit: function () {
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