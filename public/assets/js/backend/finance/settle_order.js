define(['jquery', 'bootstrap', 'backend', 'table', 'form','upload'], function ($, undefined, Backend, Table, Form,Upload) {

    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                searchFormVisible: true,
                extend: {
                    index_url: 'finance/settle_order/index' + location.search,
                    add_url: 'customer/wholesale_customer/add',
                    edit_url: 'customer/wholesale_customer/edit',
                    del_url: 'customer/wholesale_customer/del',
                    import_url: 'customer/wholesale_customer/import',
                    table: 'wholesale_customer',
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
                        {field: 'id', title: __('结算单号'),},
                        {field: 'email', title: __('供应商名称')},
                        {field: 'email', title: __('供应商账期')},
                        {field: 'email', title: __('结算金额')},
                        {field: 'email', title: __('采购负责人')},
                        {field: 'status', title: __('状态'),custom: { 1: 'danger', 2: 'success', 3: 'orange', 4: 'warning', 5: 'purple', 6: 'primary' , 7: 'primary'}, searchList: { 1: '新建', 2: '待审核', 3: '待对账', 4: '待财务确认', 5: '已完成',6:'已拒绝' ,7:'已取消'},formatter: Table.api.formatter.status},
                        {field: 'id', title: __('结算类型'),visible:false},
                        {
                            field: 'operate', title: __('Operate'), table: table, events: Table.api.events.operate, buttons: [
                                 {
                                  name: 'detail',
                                  text: '详情',
                                  title: __('查看详情'),
                                  extend: 'data-area = \'["80%","70%"]\'',
                                  classname: 'btn btn-xs btn-primary btn-dialog',
                                  icon: 'fa fa-list',
                                  url: 'customer/wholesale_customer/detail',
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

            // 为表格绑定事件
            Table.api.bindevent(table);

            // 导入按钮事件
            Upload.api.plupload($('.btn-import'), function (data, ret) {
                Fast.api.ajax({
                    url: 'customer/wholesale_customer/import',
                    data: { file: data.url },
                }, function (data, ret) {
                    layer.msg('导入成功！！', { time: 3000, icon: 6 }, function () {
                        location.reload();
                    });

                });
            });


            //批量导出xls
            $('.btn-batch-export-xls').click(function () {
                var ids = Table.api.selectedids(table);
                if (ids.length > 0) {
                    window.open(Config.moduleurl + '/customer/wholesale_customer/batch_export_xls?ids=' + ids, '_blank');
                } else {
                    var options = table.bootstrapTable('getOptions');
                    var search = options.queryParams({});
                    var filter = search.filter;
                    var op = search.op;
                    window.open(Config.moduleurl + '/customer/wholesale_customer/batch_export_xls?filter=' + filter + '&op=' + op, '_blank');
                }

            });
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