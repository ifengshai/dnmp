define(['jquery', 'bootstrap', 'backend', 'table', 'form','upload'], function ($, undefined, Backend, Table, Form,Upload) {

    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                searchFormVisible: true,
                extend: {
                    index_url: 'finance/settle_order/index' + location.search,
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
                        {checkbox: true},
                        {field: 'id', title: __('ID'),operate:false},
                        {field: 'statement_number', title: __('结算单号'),},
                        {field: 'supplier_name', title: __('供应商名称')},
                        {field: 'account_statement', title: __('供应商账期')},
                        {field: 'wait_statement_total', title: __('结算金额'),operate:false},
                        {field: 'purchase_person', title: __('采购负责人')},
                        {field: 'status', title: __('状态'),custom: { 0: 'danger',1: 'success', 2: 'danger', 3: 'orange', 4: 'warning', 5: 'purple', 6: 'primary'}, searchList: { 0: '新建', 1: '待审核',2:'审核拒绝', 3: '待对账', 4: '待财务确认', 5: '已取消',6:'已完成'},formatter: Table.api.formatter.status},
                        {field: 'pay_type', title: __('结算类型'), searchList: { 1: '预付款',2:'全款预付', 3: '尾款'},formatter: Table.api.formatter.status},
                        {
                            field: 'operate', title: __('Operate'), table: table, events: Table.api.events.operate, buttons: [
                                 {
                                  name: 'detail',
                                  text: '详情',
                                  title: __('查看详情'),
                                  extend: 'data-area = \'["100%","100%"]\'',
                                  classname: 'btn btn-xs btn-primary btn-dialog',
                                  icon: 'fa fa-list',
                                  url: 'finance/settle_order/detail',
                                  callback: function (data) {
                                      Layer.alert("接收到回传数据：" + JSON.stringify(data), {title: "回传数据"});
                                  },
                                  visible: function (row) {
                                      //返回true时按钮显示,返回false隐藏
                                      return true;
                                  }
                              },

                                ],
                            formatter: Table.api.formatter.operate
                        }
                    ]
                ]
            });
            //审核通过
            $(document).on('click', '.btn-confirm', function () {
                var ids = Table.api.selectedids(table);
                Backend.api.ajax({
                    url: Config.moduleurl + '/finance/settle_order/confirm',
                    data: { ids: ids}
                }, function (data, ret) {
                    table.bootstrapTable('refresh');
                });
            })
            // 为表格绑定事件
            Table.api.bindevent(table);
        },
        add: function () {
            Controller.api.bindevent();
        },
        edit: function () {
            Controller.api.bindevent();
        },
        detail: function () {
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