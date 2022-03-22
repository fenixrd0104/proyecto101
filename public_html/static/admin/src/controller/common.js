/**

 @Name：layuiAdmin 公共业务
 @Author：贤心
 @Site：http://www.layui.com/admin/
 @License：LPPL
    
 */
 
layui.define(function(exports){
  var $ = layui.$
  ,layer = layui.layer
  ,laytpl = layui.laytpl
  ,setter = layui.setter
  ,view = layui.view
  ,admin = layui.admin
  


  //公共业务的逻辑处理可以写在此处，切换任何页面都会执行
  //……

  
  
  //退出
  admin.events.logout = function(){
    //执行退出接口
    admin.req({
      url: layui.setter.baseUrl+'admin/login/loginOut'
      ,type: 'get'
      ,data: {}
      ,success: function(res){ //这里要说明一下：done 是只有 response 的 code 正常才会执行。而 succese 则是只要 http 为 200 就会执行
        if(res.code==1){
           //清空本地记录的 token，并跳转到登入页
            layer.msg(res.msg, {
              icon: 1,
              time: 1000
            }, function(){
              admin.exit();
            });
        }
       
      }
    });
  };
  // 清除缓存
  admin.events.clear_chach = function(){
   
    admin.req({
      url: layui.setter.baseUrl+'admin/index/clear'
      ,type: 'get'
      ,data: {}
      ,success: function(res){ //这里要说明一下：done 是只有 response 的 code 正常才会执行。而 succese 则是只要 http 为 200 就会执行
        if(res.code==1){
            layer.msg(res.msg, {
              icon: 1,
              time: 1500,
              anim:5
            });
        }else{
          layer.msg(res.msg, {icon: 5,anim: 6,shade:0.5,time: 1500});
        }
       
      }
    });
  };

  
  //对外暴露的接口
  exports('common', {});
});