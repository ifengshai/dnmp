define(['jquery', 'bootstrap', 'backend', 'table', 'form', 'fast', 'bootstrap-table-jump-to'], function ($, undefined, Backend, Table, Form, Fast) {

    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                showJumpto: true,
                searchFormVisible: true,
                // searchFormTemplate: 'customformtpl',
                pageList: [10, 25, 50, 100],
                extend: {
                    index_url: 'purchase/supplier/index' + location.search,
                    add_url: 'purchase/supplier/add',
                    edit_url: 'purchase/supplier/edit',
                    // del_url: 'purchase/supplier/del',
                    multi_url: 'purchase/supplier/multi',
                    import_url: 'purchase/supplier/import',
                    table: 'supplier',
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
                        {field: 'supplier_name', title: __('Supplier_name'), operate: 'like'},
                        {field: 'email', title: __('Email'), operate: false},
                        {field: 'url', title: __('Url'), operate: false, formatter: Table.api.formatter.url},
                        {field: 'telephone', title: __('Telephone'), operate: false},
                        {field: 'purchase_person', title: __('采购负责人'), operate: 'like'},
                        {field: 'address', title: __('Address'), operate: false},
                        {field: 'linkname', title: __('Linkname'), operate: 'like'},
                        {field: 'linkphone', title: __('Linkphone'), operate: 'like'},
                        {
                            field: 'supplier_type',
                            title: __('Supplier_type'),
                            searchList: {1: '镜片', 2: '镜架', 3: '眼镜盒', 4: '镜布'},
                            formatter: Controller.api.formatter.supplier_type
                        },
                        {field: 'create_person', title: __('Create_person')},
                        {field: 'createtime', title: __('Createtime'), operate: 'RANGE', addclass: 'datetimerange'},
                        {
                            field: 'status',
                            title: __('Status'),
                            searchList: {1: '启用', 2: '禁用'},
                            formatter: Table.api.formatter.status
                        },
                        {
                            field: 'operate',
                            title: __('Operate'),
                            table: table,
                            events: Table.api.events.operate,
                            buttons: [
                                {
                                    name: 'detail',
                                    text: '详情',
                                    title: '详情',
                                    classname: 'btn btn-xs btn-primary btn-dialog',
                                    icon: 'fa fa-list',
                                    url: 'purchase/supplier/detail',
                                    callback: function (data) {
                                        Layer.alert("接收到回传数据：" + JSON.stringify(data), {title: "回传数据"});
                                    },
                                    visible: function (row) {
                                        //返回true时按钮显示,返回false隐藏
                                        return true;
                                    }
                                }],
                            formatter: Table.api.formatter.operate
                        }
                    ]
                ]
            });

            // 为表格绑定事件
            Table.api.bindevent(table);

            //启用
            $(document).on('click', '.btn-open', function () {
                var ids = Table.api.selectedids(table);
                Backend.api.ajax({
                    url: Config.moduleurl + '/purchase/supplier/setStatus',
                    data: {ids: ids, status: 1}
                }, function (data, ret) {
                    table.bootstrapTable('refresh');
                });
            })

            //禁用
            $(document).on('click', '.btn-close', function () {
                var ids = Table.api.selectedids(table);
                Backend.api.ajax({
                    url: Config.moduleurl + '/purchase/supplier/setStatus',
                    data: {ids: ids, status: 2}
                }, function (data, ret) {
                    table.bootstrapTable('refresh');
                });
            })
            //批量修改采购负责人
            $(document).on('click', '.btn-incharge', function () {
                var ids = Table.api.selectedids(table);
                var options = {
                    shadeClose: false,
                    shade: [0.3, '#393D49'],
                    area: ['50%', '70%'], //弹出层宽高
                    callback: function (value) {
                        table.bootstrapTable('refresh');
                    }
                };
                Fast.api.open('purchase/supplier/change_incharge?ids=' + ids.join(','), '批量修改-采购负责人', options);


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
            formatter: {
                supplier_type: function (value, row, index) {
                    var str = '';
                    if (value == 1) {
                        str = '镜片';
                    } else if (value == 2) {
                        str = '镜架';
                    } else if (value == 3) {
                        str = '眼镜盒';
                    } else if (value == 4) {
                        str = '镜布';
                    }
                    return str;
                }
            },
            bindevent: function () {
                Form.api.bindevent($("form[role=form]"));
            }
        }
    };
    return Controller;
});