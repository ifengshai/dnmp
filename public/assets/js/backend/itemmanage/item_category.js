define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {

    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                extend: {
                    index_url: 'itemmanage/item_category/index' + location.search,
                    add_url: 'itemmanage/item_category/add',
                    edit_url: 'itemmanage/item_category/edit',
                    del_url: 'itemmanage/item_category/del',
                    multi_url: 'itemmanage/item_category/multi',
                    table: 'item_category',
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
                        {field: '', title: __('序号'), formatter: function (value, row, index) {
                            var options = table.bootstrapTable('getOptions');
                            var pageNumber = options.pageNumber;
                            var pageSize = options.pageSize;

                            //return (pageNumber - 1) * pageSize + 1 + index;
                            return 1+index;
                            }, operate: false
                        },
                        {field: 'id', title: __('Id')},
                        {field: 'pid', title: __('Pid')},
                        {field: 'name', title: __('Name')},
                        {field: 'is_putaway', title: __('Is_putaway'),
                         searchList: { 1: '上架', 2: '下架' },
                         custom: {  0: 'yellow', 1: 'blue' },
                         formatter: Table.api.formatter.status
                        },
                        {field:'level',title:__('Level'),
                         searchList:{1:'一级分类',2:'二级分类',3:'三级分类'},
                         custom:{1:'red',2:'blue',3:'yellow'},
                         formatter:Table.api.formatter.status
                        },
                        {field:'attribute_group_id',title:__('Attribute_group_id')},
                        {
                            field:'is_upload_zeelool',
                            title:__('Is_upload_zeelool'),
                            searchList:{1:'是',2:'否'},
                            custom:{1:'blue',2:'yellow'},
                            formatter:Table.api.formatter.status
                        },
                        {
                            field:'is_upload_voogueme',
                            title:__('Is_upload_voogueme'),
                            searchList:{1:'是',2:'否'},
                            custom:{1:'blue',2:'yellow'},
                            formatter:Table.api.formatter.status
                        },
                        {
                            field:'is_upload_nihao',
                            title:__('is_upload_nihao'),
                            searchList:{1:'是',2:'否'},
                            custom:{1:'blue',2:'yellow'},
                            formatter:Table.api.formatter.status
                        },
                        {field: 'create_time', title: __('Create_time'), operate:'RANGE', addclass:'datetimerange'},
                        {field: 'operate', title: __('Operate'), table: table, events: Table.api.events.operate, formatter: Table.api.formatter.operate}
                    ]
                ]
            });

            // 为表格绑定事件
            Table.api.bindevent(table);
            $(document).on('click', '.btn-upload', function () {
                 var ids = Table.api.selectedids(table);
                 var platformId = $(this).attr("id");
                 Layer.confirm(
                    __('确定要传至对应的平台吗'),
                    function (index) {
                        Backend.api.ajax({
                            url: Config.moduleurl + "/itemmanage/item_category/uploadItemCategory",
                            data: { ids: ids,platformId:platformId }
                        }, function (data, ret) {
                            table.bootstrapTable('refresh');
                            Layer.close(index);
                        });
                    }
                );
            });
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