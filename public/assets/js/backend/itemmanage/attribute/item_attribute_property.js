define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {

    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                searchFormVisible: true,
                extend: {
                    index_url: 'itemmanage/attribute/item_attribute_property/index' + location.search,
                    add_url: 'itemmanage/attribute/item_attribute_property/add',
                    edit_url: 'itemmanage/attribute/item_attribute_property/edit',
                    del_url: 'itemmanage/attribute/item_attribute_property/del',
                    multi_url: 'itemmanage/attribute/item_attribute_property/multi',
                    table: 'item_attribute_property',
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
                        {field: 'is_required', title: __('Is_required'),
                            searchList: { 1: '必填', 2: '选填' },
                            custom: {  2: 'yellow', 1: 'blue' },
                            formatter: Table.api.formatter.status
                        },
                        {field: 'name_cn', title: __('Name_cn')},
                        {field: 'name_en', title: __('Name_en')},
                        {field: 'input_mode', title: __('Input_mode'),
                            searchList: { 1: '单选', 2: '多选',3:'输入'},
                            custom: {  2: 'yellow', 1: 'blue',3: 'danger'},
                            formatter: Table.api.formatter.status
                        },
                        {field: 'create_person', title: __('Create_person')},
                        {field: 'update_time', title: __('Update_time'), operate:'RANGE', addclass:'datetimerange'},
                        {field: 'create_time', title: __('Create_time'), operate:'RANGE', addclass:'datetimerange'},
                        {field: 'operate', title: __('Operate'), table: table, events: Table.api.events.operate, formatter: Table.api.formatter.operate,
                            buttons: [
                                {
                                    name: 'detail',
                                    text: '详情',
                                    title: __('查看详情'),
                                    //extend: 'data-area = \'["100%","100%"]\'',
                                    classname: 'btn btn-xs btn-primary btn-dialog',
                                    icon: 'fa fa-list',
                                    url: 'itemmanage/attribute/item_attribute_property/detail'
                                    // callback: function (data) {
                                    //     Layer.alert("接收到回传数据：" + JSON.stringify(data), {title: "回传数据"});
                                    // },
                                    // visible: function (row) {
                                    //     //返回true时按钮显示,返回false隐藏
                                    //     return true;
                                    // }
                                },
                            ]
                        }
                    ]
                ]
            });

            // 为表格绑定事件
            Table.api.bindevent(table);
        },
        add: function () {
            Controller.api.bindevent();
            $(document).on('blur','#c-name_en',function(){
                var nameEn = $('#c-name_en').val();
                Backend.api.ajax({
                    url: 'itemmanage/attribute/item_attribute_property/getAjaxItemAttrProperty',
                    data: { nameEn: nameEn}
                }, function (data, ret) {
                    $('#submit').removeClass('btn-disabled disabled');
                }, function (data, ret) {
                    //失败的回调
                    $('#submit').addClass('btn-disabled disabled');
                    Layer.alert(ret.msg);
                    return false;
                });
            });

        },
        edit: function () {
            Controller.api.bindevent();
        },
        api: {
            bindevent: function () {
                Form.api.bindevent($("form[role=form]"));
                $(document).on('click', '.btn-add', function () {
                    var input_mode = $('#c-input_mode').val();
                    if(input_mode == 3){
                        Layer.alert('选择输入不需要添加属性值项');
                        return false;
                    }
                    var content = $('#table-content table tbody').html();
                    $('.caigou table tbody').append(content);
                });
                $(document).on('click', '.btn-del', function () {
                    $(this).parent().parent().remove();
                });
            }
        },
        detail:function () {
            Controller.api.bindevent();
        }
    };
    return Controller;
});