define(['jquery', 'bootstrap', 'backend', 'table', 'form','upload'], function ($, undefined, Backend, Table, Form,Upload) {

    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                searchFormVisible: true,
                extend: {
                    index_url: 'customer/wholesale_customer/index' + location.search,
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
                        {field: 'id', title: __('Id')},
                        {field: 'email', title: __('Email')},
                        {field: 'customer_name', title: __('Customer_name'), operate: false},
                        {field: 'mobile', title: __('Mobile'), operate: false},
                        {field: 'country', title: __('Country'), operate: false},
                        {field: 'site_type', title: __('Site_type'),custom: { 1: 'success', 2: 'danger', 3: 'orange', 4: 'warning', 5: 'purple', 6: 'primary' }, searchList: { 1: 'Zeelool', 2: 'Voogueme', 3: 'Nihao', 4: 'Alibaba', 5: '主动开发',6:'Wesee' },
                            formatter: Table.api.formatter.status},
                        {field: 'intention_level', title: __('Intention_level'), searchList: { 1: '低', 2: '中', 3: '高' },
                            formatter: Table.api.formatter.status},
                        { field: 'is_order', title: __('Is_order'), custom: { 1: 'danger', 2: 'success' }, searchList: { 1: '否', 2: '是' }, formatter: Table.api.formatter.status },
                        { field: 'is_behalf_of', title: __('Is_behalf_of'), custom: { 1: 'danger', 2: 'success' }, searchList: { 1: '否', 2: '是' }, formatter: Table.api.formatter.status },
                        { field: 'is_logo', title: __('Is_logo'), custom: { 1: 'danger', 2: 'success' }, searchList: { 1: '否', 2: '是' }, formatter: Table.api.formatter.status },
                        {field: 'create_time', title: __('Create_time'), operate:'RANGE', addclass:'datetimerange', formatter: Table.api.formatter.datetime},
                        {field: 'create_user_name', title: __('Create_user_id')},
                        {
                            field: 'operate', title: __('Operate'), table: table, events: Table.api.events.operate, buttons: [
                                {
                                    name: 'edit',
                                    text: '',
                                    title: __('Edit'),
                                    classname: 'btn btn-xs btn-success btn-dialog',
                                    icon: 'fa fa-pencil',
                                    url: 'customer/wholesale_customer/edit',
                                    extend: 'data-area = \'["80%","70%"]\'',
                                    callback: function (data) {
                                        Layer.alert("接收到回传数据：" + JSON.stringify(data), { title: "回传数据" });
                                    },
                                    visible: function (row) {
                                        if (Config.customer_edit == 1) {//有权限 或者创建人为当前人
                                            return true;
                                        } else {
                                            return false;
                                        }
                                    }
                                },
                                {
                                    name: 'del',
                                    text: __(''),
                                    title: __('删除'),
                                    icon: 'fa fa-trash',
                                    classname: 'btn btn-xs btn-danger btn-magic btn-ajax',
                                    url: 'customer/wholesale_customer/del',
                                    confirm: '是否删除?',
                                    success: function (data, ret) {
                                        table.bootstrapTable('refresh', {});
                                    },
                                    error: function (data, ret) {
                                        Layer.alert(ret.msg);
                                        return false;
                                    },
                                    visible: function (row) {
                                        if (Config.customer_del == 1) {//有权限 或者创建人为当前人
                                            return true;
                                        } else {
                                            return false;
                                        }
                                    }
                                },
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