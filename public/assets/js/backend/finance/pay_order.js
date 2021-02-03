define(['jquery', 'bootstrap', 'backend', 'table', 'form','upload'], function ($, undefined, Backend, Table, Form,Upload) {

    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                searchFormVisible: true,
                extend: {
                    index_url: 'finance/pay_order/index' + location.search,
                    add_url: 'finance/pay_order/add',
                    edit_url: 'finance/pay_order/edit',
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
                        {field: 'id', title: __('序号'),operate:false},
                        {field: 'pay_number', title: __('付款单号'),},
                        {field: 'supplier_name', title: __('供应商名称')},
                        {field: 'status', title: __('状态'),custom: { 1: 'danger', 2: 'success', 3: 'orange', 4: 'warning', 5: 'purple', 6: 'primary' , 7: 'primary'}, searchList: { 1: '新建', 2: '待审核', 3: '待付款', 4: '待上传发票', 5: '已完成',6:'已拒绝' ,7:'已取消'},formatter: Table.api.formatter.status},
                        {field: 'create_user', title: __('创建人')},
                        {field: 'create_time', title: __('创建时间'), operate: 'RANGE', addclass: 'datetimerange', formatter: Table.api.formatter.datetime},
                        {field: 'check_user', title: __('审批人')},
                        {
                            field: 'operate', title: __('Operate'), table: table, events: Table.api.events.operate, buttons: [
                                {
                                    name: 'edit',
                                    text: __('编辑'),
                                    title: __('编辑'),
                                    classname: 'btn btn-xs btn-success btn-dialog',
                                    url: 'finance/pay_order/edit',
                                    extend: 'data-area = \'["80%","70%"]\'',
                                    callback: function (data) {
                                    },
                                    visible: function (row) {
                                        console.log(Config.now_user);
                                        if(row.status == 1 && Config.now_user == row.create_user){
                                            return true;
                                        }else{
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
                                  url: 'finance/pay_order/detail',
                                  callback: function (data) {
                                      Layer.alert("接收到回传数据：" + JSON.stringify(data), {title: "回传数据"});
                                  },
                                  visible: function (row) {
                                      //返回true时按钮显示,返回false隐藏
                                      return true;
                                  }
                              },
                              {
                                name: 'pay',
                                text: __('付款'),
                                title: __('付款'),
                                classname: 'btn btn-xs btn-danger btn-ajax',
                                url: 'finance/pay_order/pay',
                                extend: 'data-area = \'["100%","100%"]\'',
                                confirm: '确定要付款吗',
                                success: function (data, ret) {
                                    table.bootstrapTable('refresh');
                                },
                                callback: function (data) {
                                },
                                visible: function (row) {
                                    if (row.status == 3) {
                                        return true;
                                    } else {
                                        return false;
                                    }
                                }
                            },
                            {
                                name: 'check',
                                text: __('审核'),
                                title: __('审核'),
                                classname: 'btn btn-xs btn-danger btn-ajax',
                                url: 'finance/pay_order/setStatus?status=3',
                                extend: 'data-area = \'["100%","100%"]\'',
                                confirm: '确定要审核通过吗',
                                success: function (data, ret) {
                                    table.bootstrapTable('refresh');
                                },
                                callback: function (data) {
                                },
                                visible: function (row) {
                                    if (row.status == 2) {
                                        return true;
                                    } else {
                                        return false;
                                    }
                                }
                            },
                            {
                                name: 'refuse',
                                text: __('拒绝'),
                                title: __('拒绝'),
                                classname: 'btn btn-xs btn-danger btn-ajax',
                                url: 'finance/pay_order/setStatus?status=6',
                                extend: 'data-area = \'["100%","100%"]\'',
                                confirm: '确定要拒绝吗',
                                success: function (data, ret) {
                                    table.bootstrapTable('refresh');
                                },
                                callback: function (data) {
                                },
                                visible: function (row) {
                                if (row.status == 2) {
                                        return true;
                                    } else {
                                        return false;
                                    }
                                }
                            },
                            {
                                name: 'cancel',
                                text: __('取消'),
                                title: __('取消'),
                                classname: 'btn btn-xs btn-danger btn-ajax',
                                url: 'finance/pay_order/setStatus?status=7',
                                extend: 'data-area = \'["100%","100%"]\'',
                                confirm: '确定要取消吗',
                                success: function (data, ret) {
                                    table.bootstrapTable('refresh');
                                },
                                callback: function (data) {
                                },
                                visible: function (row) {
                                    if (row.status == 1) {
                                        return true;
                                    } else {
                                        return false;
                                    }
                                }
                            },
                            {
                                name: 'upload',
                                text: '上传发票',
                                title: __('上传发票'),
                                classname: 'btn btn-xs btn-success btn-dialog',
                                icon: 'fa fa-pencil',
                                url: 'finance/pay_order/upload',
                                extend: 'data-area = \'["80%","70%"]\'',
                                success: function (data, ret) {
                                    table.bootstrapTable('refresh');
                                },
                                callback: function (data) {
                                    Layer.alert("接收到回传数据：" + JSON.stringify(data), { title: "回传数据" });
                                },
                                visible: function (row) {
                                    if (row.status == 4) {
                                        return true;
                                    } else {
                                        return false;
                                    }
                                }
                            },
                                ],
                            formatter: Table.api.formatter.operate
                        }
                    ]
                ]
            });
            //上传文件
            $(document).on('click', '.pluploads', function () {
                var _this = $(this);
                var url = _this.parent().parent().parent().find('.unqualified_images').val();
                Fast.api.open(
                    'finance/pay_order/upload?img_url=' + url, '上传文件', {
                    callback: function (data) {
                        _this.parent().parent().parent().find('.unqualified_images').val(data.unqualified_images);
                    }
                }
                )
            })
            // 为表格绑定事件
            Table.api.bindevent(table);
        },
        upload: function () {
            Controller.api.bindevent(function (data, ret) {
                //这里是表单提交处理成功后的回调函数，接收来自php的返回数据
                Fast.api.close(data);//这里是重点
                return false;
            });

            $('.unqualified_images').change();

        },
        add: function () {
            $(document).on('click', '#save', function () {
                $("#status").val(1);
                $("#add-form").submit();
            });
            $(document).on('click', '#submit', function () {
                $("#status").val(2);
                $("#add-form").submit();
            });
            Controller.api.bindevent();
        },
        edit: function () {
            $(document).on('click', '#save', function () {
                $("#status").val(1);
                $("#add-form").submit();
            });
            $(document).on('click', '#submit', function () {
                $("#status").val(2);
                $("#add-form").submit();
            });
            Controller.api.bindevent();
        },
        detail: function () {
            var index = 0;
            $(document).on('click', '#imgs', function(){
                var src = this.src; //图片地址
                index = layer.open({
                    type: 1, //open的类型 1为页面层
                    title:'发票',
                    shadeClose: true,  //点击遮罩关闭
                    shade: "background-color: #000", //遮罩的颜色以及透明度(与官网不同)
                    content: '<div id="layui-layer-photos" style="width: 100%;"><img src="'+src+'" style="width: 100%;"/></div>' 
                });
            });
            //点击图片关闭大图
            $(document).on('click', '#layui-layer-photos', function(){
                layer.close(index);
            });
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