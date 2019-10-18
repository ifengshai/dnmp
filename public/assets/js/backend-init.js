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
});