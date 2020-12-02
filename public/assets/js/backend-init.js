define(['backend'], function (Backend) {
        //添加Backend的扩展方法
        $.extend(true, Backend, {
            api:  {
                redirect: function(url, title, icon) {
                    var that = top.window.$("ul.nav-addtabs li.active");
                    var oldId = that.find('a').attr('node-id')
                    //新增窗口
                    Fast.api.addtabs(url, title, icon);
                    //关闭原来的
                    top.window.$("ul.nav-addtabs li#tab_" + oldId + " .close-tab").trigger("click");
                },
            }
        });


        
        $('body').on('click', '[data-tips-image]', function () {
            var img = new Image();
            var imgWidth = this.getAttribute('data-width') || '480px';
            img.onload = function () {
                var $content = $(img).appendTo('body').css({background: '#fff', width: imgWidth, height: 'auto'});
                Layer.open({
                    type: 1, area: imgWidth, title: false, closeBtn: 1,
                    skin: 'layui-layer-nobg', shadeClose: true, content: $content,
                    end: function () {
                        $(img).remove();
                    },
                    success: function () {

                    }
                });
            };
            img.onerror = function (e) {

            };
            img.src = this.getAttribute('data-tips-image') || this.src;
        });
});