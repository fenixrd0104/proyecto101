/*
 * @Author: 王世文1198627433@qq.com 
 * @Date: 2019-06-25 15:46:31 
 * @Last Modified by: 王世文1198627433@qq.com
 * @Last Modified time: 2019-06-29 14:20:50
 */

layui.define(function(exports) {

layui.use(['admin', 'table','setter','view','form'], function(){
    var $ = layui.$
    ,admin = layui.admin
    ,setter = layui.setter
    ,view = layui.view
    ,table = layui.table
    ,layer = layui.layer
    ,form = layui.form;

    table.render({
        elem: '#add_freight'
        ,url: layui.setter.baseUrl+'merchant/freight/index'  
        ,cellMinWidth: 80 
        ,cols: [[
             {field:'name', title: '模板名称',align:'center'}
            // ,{field:'shop_name', title: '商铺名称',align:'center'}
            ,{field:'type_name', title: '计费方式',align:'center'}
            ,{fixed: 'right', title:'操作', toolbar: '#table_add_freight',align:'center',width:160}
        ]]
    });

    // 添加模板
    var $ = layui.$, active = {

    // 添加
    add:function(){
    admin.popup({
        title: "添加运费模板",
        area: admin.screen() < 2 ? ['95%', '90%'] :["800px", "700px"],
        id: "LAY-popup-add_freight",
        success: function(layero, index){
        view(this.id).render('/freight/add_freight').done(function() {
            form.render(null, 'add_freight'); // 弹窗的lay-filter值            
            //监听提交
            var get_data={
                "default":{},
                "general":{
                    
                }, 
                "special":{
                    
                }
            };          
            form.on('submit(component-add-freight)', function(data){
                // console.log(data.field)
                var default_url={
                    "begin":data.field.begin, "begin_price":data.field.begin_price,
                    "inc":data.field.inc, "inc_price":data.field.inc_price
                };
                get_data.default=default_url;  
                // 运费模板
                var yf_table =  table.cache["table_yunfei"]; 
                // console.log(yf_table);                     
                var general_url=[];
                for( var i in yf_table){            
                    var general_data={                
                        "info":{
                            "begin":yf_table[i].begin_yf,
                            "begin_price":yf_table[i].begin_price_yf,
                            "inc":yf_table[i].inc_yf,
                            "inc_price":yf_table[i].inc_price_yf
                        },
                        "region":yf_table[i].region,
                        "provice":yf_table[i].provice            
                    } 
                    get_data.general =general_url;
                    general_url.push(general_data);
                }   

                // 包邮方式
                var by_table =  table.cache["table_is_parcel"]; 
                // console.log(by_table);
                var special_url=[];
                for( var i in by_table){            
                    var special_data={                
                        "range":by_table[i].range,
                        "type": by_table[i].type,
                        "region":by_table[i].region,
                        "provice":by_table[i].provice            
                    } 
                    get_data.special =special_url;
                    special_url.push(special_data);
                }   


                data.field.data =get_data;
                // console.log(data.field); 
                admin.req({
                    url: layui.setter.baseUrl+'merchant/freight/add' 
                    ,data: data.field
                    ,type:'post'
                    ,success: function(res){
                        if(res.code==1){
                            // console.log(res);
                            layer.msg(res.msg , {icon: 1,time: 1000} , function(){  
                                layer.close(index); //执行关闭 
                                layui.table.reload('add_freight'); //重载表格 
                            });
                        }else{
                            layer.msg(res.msg, {icon: 5,anim: 6,shade:0.5,time: 1000});
                        }
                    }
                }); 
            });
            
        });
        }
    });
    }
    };
    $('.test-table-reload-btn .layui-btn').on('click', function(){
        var type = $(this).data('type');
        active[type] ? active[type].call(this) : '';
    });

    

    

    table.on('tool(add_freight)',function(e){
        var data=e.data;              
        var id = e.data.id;  
        // console.log(e);       
        if(e.event==='edit'){            
            admin.popup({
            title: "编辑运费模板",
            area: admin.screen() < 2 ? ['95%', '90%'] :["800px", "700px"],
            id: "LAY-popup-add_freight",
            success: function(layero, index){
            view(this.id).render('/freight/edit_freight',data).done(function() {
                form.render(null, 'add_freight'); // 弹窗的lay-filter值
                //监听提交
                var get_data={
                    "default":{},
                    "general":{}, 
                    "special":{}
                };  
                form.on('submit(component-edit-freight)', function(data){
                    var default_url={
                        "begin":data.field.begin, "begin_price":data.field.begin_price,
                        "inc":data.field.inc, "inc_price":data.field.inc_price
                    };
                    get_data.default=default_url; 
                    // 运费模板
                    var yf_table =  table.cache["table_yunfei"]; 
                    // console.log(yf_table);    
                    var general_url=[];
                    for( var i in yf_table){            
                        var general_data={                
                            "info": yf_table[i].info,
                            "region":yf_table[i].region,
                            "provice":yf_table[i].provice            
                        } 
                        get_data.general =general_url;
                        general_url.push(general_data);
                    }   

                    // 包邮方式
                    var by_table =  table.cache["table_is_parcel"]; 
                    // console.log(by_table);
                    var special_url=[];
                    for( var i in by_table){            
                        var special_data={                
                            "range":by_table[i].range,
                            "type": by_table[i].type,
                            "region":by_table[i].region,
                            "provice":by_table[i].provice            
                        } 
                        get_data.special =special_url;
                        special_url.push(special_data);
                    }   

                    data.field.data =get_data;                     
                    data.field.id =id;                     
                    // console.log(data.field)
                    admin.req({
                        url: layui.setter.baseUrl+'merchant/freight/edit' 
                        ,data: data.field
                        ,type:'post'
                        ,success: function(res){
                            if(res.code==1){
                                // console.log(res);
                                layer.msg(res.msg , {icon: 1,time: 1000} , function(){  
                                    layer.close(index); //执行关闭 
                                    layui.table.reload('add_freight'); //重载表格 
                                });
                            }else{
                                layer.msg(res.msg, {icon: 5,anim: 6,shade:0.5,time: 1000});
                            }
                        }
                    }); 
                }); 
                
            });
            }
        });
        } else if(e.event === 'del'){
            // 删除按钮
            layer.confirm('确认删除此模板?', {icon: 3, title:'提示'}, function(index){
                admin.req({
                    url: layui.setter.baseUrl+'merchant/freight/del' 
                    ,data: {id:id}
                    ,type:'get'
                    ,success: function(res){
                      if(res.code==1){                        
                          e.del();
                          layer.close(index);                 
                      }else{
                        layer.msg(res.msg, {icon: 5,anim: 6,shade:0.5,time: 1500});
                      }
                    }
                });  
            });
        }
    });


});

    exports("freight", {});

});
