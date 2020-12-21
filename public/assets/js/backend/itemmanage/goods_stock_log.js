define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {

    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                showJumpto: true,
                searchFormVisible: true,
                pageList: [10, 25, 50, 100],
                extend: {
                    index_url: 'itemmanage/goods_stock_log/index' + location.search,
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

                        {field: 'id', title: __('id'), operate: false},
                        {
                            field:'type',
                            title:__('大站点类型'),
                            searchList: { 1: '网站', 2: '魔晶'},
                            custom: { 1: 'yellow', 2: 'blue'},
                            formatter: Table.api.formatter.status
                        },
                        {
                            field:'site',
                            title:__('站点类型'),
                            searchList: { 0: '全部',1: 'zeelool', 2: 'Voogueme', 3: 'Nihao', 4: 'Meeloog', 5: 'Wesee' , 8: 'Amazon', 9: 'Zeelool_es', 10: 'Zeelool_de', 11: 'Zeelool_jp'},
                            custom: { 0: 'scarlet',1: 'yellow', 2: 'blue', 3: 'success', 4: 'red', 5: 'danger', 8: 'green', 9: 'brown', 10: 'gray', 11: 'purple'},
                            formatter: Table.api.formatter.status
                        },
                        {
                            field:'modular',
                            title:__('模块'),
                            searchList: { 1: '普通订单', 2: '配货', 3: '质检', 4: '审单', 5: '异常处理' , 6: '更改镜架', 7: '取消订单', 8: '补发', 9: '赠品', 10: '采购入库', 11: '出入库', 12: '盘点', 13: '调拨'},
                            formatter: Table.api.formatter.status
                        },
                        {
                            field:'change_type',
                            title:__('变动类型'),
                            searchList: { 1: '非预售下单', 2: '预售下单-虚拟仓>0', 3: '预售下单-虚拟仓<0', 4: '配货', 5: '质检拒绝-镜架报损' , 6: '审单-成功', 7: '审单-配错镜框'
                                , 8: '加工异常打回待配货', 9: '印logo异常打回待配货', 10: '更改镜架-配镜架前', 11: '更改镜架-配镜架后', 12: '取消订单-配镜架前', 13: '取消订单-配镜架后'
                                , 14:'补发', 15: '赠品', 16: '采购-有比例入库', 17: '采购-没有比例入库', 18: '手动入库', 19: '手动出库', 20: '盘盈入库', 21: '盘亏出库', 22: '库存调拨', 23: '采购单审核通过', 24: '采购单签收完成'
                            },
                            formatter: Table.api.formatter.status
                        },
                        {field: 'sku', title: __('sku'), operate: 'like'},
                        {
                            field:'number_type',
                            title:__('关联单号类型'),
                            searchList: { 1: '订单号', 2: '子订单号', 3: '入库单', 4:'出库单', 5:'盘点单', 6:'调拨单',7:'采购单'},
                            custom: {  1: 'yellow', 2: 'blue', 3: 'success', 4: 'red', 5: 'danger', 6: 'green'},
                            formatter: Table.api.formatter.status
                        },
                        {field: 'order_number', title: __('关联单号')},
                        {field: 'public_id', title: __('关联变化的id'), operate:false},
                        {
                            field:'source',
                            title:__('操作端'),
                            searchList: { 1: 'PC端', 2: 'PDA'},
                            custom: { 1: 'red', 2: 'blue'},
                            formatter: Table.api.formatter.status
                        },
                        {field: 'stock_before', title: __('总库存变动前'), operate:false},
                        {field: 'stock_change', title: __('总库存变化量'), operate:false},
                        {field: 'available_stock_before', title: __('可用库存变动前'), operate:false},
                        {field: 'available_stock_change', title: __('可用库存变化量'), operate:false},
                        {field: 'fictitious_before', title: __('虚拟仓库存变动前'), operate:false},
                        {field: 'fictitious_change', title: __('虚拟仓库存变化量'), operate:false},
                        {field: 'occupy_stock_before', title: __('订单占用变动前'), operate:false},
                        {field: 'occupy_stock_change', title: __('订单占用变化量'), operate:false},
                        {field: 'distribution_stock_before', title: __('配货占用变动前'), operate:false},
                        {field: 'distribution_stock_change', title: __('配货占用变化量'), operate:false},
                        {field: 'presell_num_before', title: __('预售变动前'), operate:false},
                        {field: 'presell_num_change', title: __('预售变化量'), operate:false},
                        // {field: 'sample_num_before', title: __('留样库存变动前'), operate:false},
                        // {field: 'sample_num_change', title: __('留样库存变化量'), operate:false},
                        {field: 'on_way_stock_before', title: __('在途库存变动前'), operate:false},
                        {field: 'on_way_stock_change', title: __('在途库存变化量'), operate:false},
                        {field: 'wait_instock_num_before', title: __('待入库变动前'), operate:false},
                        {field: 'wait_instock_num_change', title: __('待入库变化量'), operate:false},
                        {field: 'create_person', title: __('创建人'), operate:false},
                        {field: 'create_time', title: __('创建时间'), operate:false},

                    ]
                ]
            });

            // 为表格绑定事件
            Table.api.bindevent(table);
        },
        add: function () {
            Controller.api.bindevent();
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