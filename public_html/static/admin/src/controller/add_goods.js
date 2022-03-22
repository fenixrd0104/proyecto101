/*
 * @Author: 王世文1198627433@qq.com 
 * @Date: 2019-06-25 15:46:31 
 * @Last Modified by: 王世文1198627433@qq.com
 * @Last Modified time: 2019-07-08 14:42:03
 */

layui.define(function(exports) {

layui.use(['admin','table','form','view','upload','layedit'], function(){
var $ = layui.$
    ,admin = layui.admin
    ,table = layui.table
    ,layer = layui.layer
    ,element = layui.element
    ,layedit = layui.layedit
    ,setter = layui.setter
    ,upload = layui.upload
    ,view = layui.view
    ,form = layui.form
    ,device = layui.device();
        
    // 图片上传
    window.see_img=function(){
        var i = $('#goods_img').val();
        layer.photos({
            photos: {
                title: "查看",
                data: [{ src: i }]
            },
            shade: 0.5,
            closeBtn: 1,
            anim: 5
        });
    };
    //多图片上传
    upload.render({
        elem: '#LAY_avatarUpload'
        ,url: layui.setter.baseUrl+'admin/upload/upload'
        ,multiple: true
        ,done: function(res){
            $('#goods_img').val(res.data);
            layer.msg(res.msg); 
            form.render();
        },error:function(res){
            layer.msg(res.msg);
        }
    });
    // 商品详情  富文本编辑器
    layedit.set({
        uploadImage: {
            url: layui.setter.baseUrl+'admin/upload/upload' //接口url
            ,type: 'post' //默认post
        }
    });
    var editIndex = layedit.build('goods_detail');
    //多图片上传
    upload.render({
        elem: '#upload_goods_images'
        ,url: layui.setter.baseUrl+'admin/upload/upload'
        ,multiple: true
        ,before: function(obj){
            //预读本地文件示例，不支持ie8
            obj.preview(function(index, file, result){
            // $('#upload_goods_images_box').append('<img src="'+ result +'" alt="'+ file.name +'" class="layui-upload-img">');
            });
        }
        ,done: function(res){
            var img='<div class="img_box"><img src="'+ res.data +'" alt=""><span class="layui-btn layui-btn-danger del_img">删除</span><a class="layui-btn layuiadmin-btn-list look_images" data-src="'+res.data+'"><i class="layui-icon layui-icon-search layuiadmin-button-btn"></i></a><input type="hidden" name="goods_images[]" value="'+ res.data +'"></div>';
            //上传完毕
            $('#upload_goods_images_box').append(img);
            layer.msg(res.msg); 
        }
    });
    // 删除
    $('body').on('click','.del_img',function(){
        // console.log($(this).parents('.img_box'));
        $(this).parents('.img_box').remove();
    });
    // 查看图片
    $('body').on('click','.look_images',function(){
        layer.photos({
            photos: {
                title: "查看头像",
                data: [{ src: $(this).attr('data-src') }]
            },
            shade: 0.5,
            closeBtn: 1,
            anim: 5
        });
    });

    admin.req({
        url: layui.setter.baseUrl+'admin/goods/addGoods' //实际使用请改成服务端真实接口
        ,data:{}
        ,type:'get'
        ,success: function(res){
        if(res.code==1){
            var list1="<option value=''>--请选择分类--</option>";
            var list4="<option value=''>--请选择分类--</option>";
            var list2="<option value=''>--请选择运费模板--</option>";
            var list3="<option value=''>--请选择品牌--</option>";
            var list5="<option value=''>--请选择供应商--</option>";
            for( var i in res.data.typeList){
                list1 += "<option value='"+res.data.typeList[i].id+"' >"+res.data.typeList[i].name+"</option> ";
            }
            for( var i in res.data.categoryList){
                list4 += "<option value='"+res.data.categoryList[i].id+"' >"+res.data.categoryList[i].name+"</option> ";
            }
            for( var i in res.data.fid){
                list2 += "<option value='"+res.data.fid[i].id+"' >"+res.data.fid[i].name+"</option> ";
            }
            for( var i in res.data.brandList){
                list3 += "<option value='"+res.data.brandList[i].id+"' >"+res.data.brandList[i].name+"</option> ";
            }
            for( var i in res.data.supplierLists){
                list5 += "<option value='"+i+"' >"+res.data.supplierLists[i]+"</option> ";
            }
            $('#cat_id_1').html(list4);
            $('#extend_cat_id_1').html(list4);
            $('#fid').html(list2);
            $('#brandList').html(list3);
            $('#supplier').html(list5);
            // 菜单            
            // var list6="<option value=''>--请选择菜单--</option>";
            // for( var i in res.data.caidan){
            //     list6 += "<option value='"+res.data.caidan[i].id+"' >"+res.data.caidan[i].name+"</option> ";
            // }
            // $('#caidan').html(list6);
            form.render();
        }else{
            layer.msg(res.msg, {icon: 5,anim: 6,shade:0.5,time: 1500});
        }
        }
    });
    
    // 更改类型
    form.on('select(cat_id_1)',function(e){
        get_data(e.value,'cat_id_2');
        $('#cat_id_3').html('');
    });
    form.on('select(cat_id_2)',function(e){
        get_data(e.value,'cat_id_3');
    });
    form.on('select(extend_cat_id_1)',function(e){
        get_data(e.value,'extend_cat_id_2');
        $('#extend_cat_id_3').html('');
    });
    form.on('select(extend_cat_id_2)',function(e){
        get_data(e.value,'extend_cat_id_3');
    });

    // 分类筛选  封装
    function get_data(type_id,elem){
    admin.req({
        url: layui.setter.baseUrl+'admin/goods/ajaxGetCategory' //实际使用请改成服务端真实接口
        ,data: {pid:type_id},
        type:'get',
        success: function(res){
            if(res.code==1){
                var list='<option value="">--请选择--</option>';
                for( var i in  res.data){
                    list += "<option value='"+res.data[i].id+"' >"+res.data[i].name+"</option> ";
                }
                $('#'+elem).html(list);
                form.render();
            }else{
                layer.msg(res.msg);
            }       
        } 
    }); 
    }

    

    // 更改菜单
    form.on('select(top_cate)',function(e){
        var id=e.value;
        if(e.value == 4 || e.value == 2){
            var list7 = '<div class="layui-col-md3 t_r ">赠送优惠券</div>'+
                            '<div class="layui-col-md5">'+
                            '<input type="text" name="give_integral" value="0" autocomplete="off" placeholder="请输入" class="layui-input">'+
                        '</div>'+
                        '<div class="layui-col-md4 layui-word-aux">订单完成后赠送的优惠券</div>';
            $("#give_integral").html(list7);

        }else{
           $("#give_integral").html('<input type="hidden" name="give_integral" value="0">');
        }
        if(e.value == 3){
            var list8 = '<div class="layui-col-md3 t_r" >优惠券兑换</div>'+
                            '<div class="layui-col-md5">'+
                            '<input type="text" name="exchange_integral" id="ex_integral" value="0" autocomplete="off" placeholder="请输入" class="layui-input">'+
                            '</div>'+
                            '<div class="layui-col-md4 layui-word-aux">如果设置0，则不支持优惠券抵扣</div>';
            $("#exchange_integral").html(list8);
        }else{
            $("#exchange_integral").html('<input type="hidden" name="exchange_integral" value="0">');
        }
        // 类型
        admin.req({
            url: layui.setter.baseUrl+'admin/goods/catetype' //实际使用请改成服务端真实接口
            ,data: {id:id}
            ,type:'get'
            ,success: function(res){
                if(res.code==1){
                    var list6="<option value=''>--请选择菜单--</option>";
                    for( var i in res.data){
                        list6 += "<option value='"+res.data[i].id+"' >"+res.data[i].name+"</option> ";
                    }
                    $('#caidan').html(list6);
                    form.render(); 
                }else{
                    layer.msg(res.msg);
                }       
            } 
        });
    });

    // 更改菜单
    form.on('select(choose_caidan_id)',function(e){
        var id=e.value;
        // 类型
        admin.req({
            url: layui.setter.baseUrl+'admin/goods/getTypeList' //实际使用请改成服务端真实接口
            ,data: {select_id:id}
            ,type:'get'
            ,success: function(res){
                if(res.code==1){
                    // console.log(res.data)
                    var list="<option value=''>--全部类型--</option>";
                    for( var i in res.data){
                        list += "<option value='"+res.data[i].id+"' >"+res.data[i].name+"</option> ";
                    }    
                $('#leixing').html(list);
                    form.render(); 
                }else{
                    layer.msg(res.msg);
                }       
            } 
        });
    });
    

    // 更改类型
    form.on('select(choose_type_id)',function(e){
        get_data_cate(e.value);
    });

    function get_data_cate(type_id){
        admin.req({
            url: layui.setter.baseUrl+'admin/goods/ajaxGetAttr' //实际使用请改成服务端真实接口
            ,data: {type_id:type_id},
            type:'get',
            success: function(res){
                if(res.code==1){
                    $('#leixing_1').html(res.data.specTpl);
                    $('#leixing_2').html(res.data.attributeTpl);
                    setpl_img();
                    form.render();
                }else{
                    $('#leixing_1').html('');
                    $('#leixing_2').html('');
                    // layer.msg(res.msg);
                }       
            } 
        }); 
    }

    var ch_name='';
    var ch_val='';
    var change_java=[];
    window.choosed = function (that) {
        // console.log(156+'****'+that);
        $(that).toggleClass('choosed');
        var list='';
        $(".choosed").each(function(){
            list+='spec_arr['+$(this).attr("spec_id")+'][]='+$(this).attr("item_id")+'&';
        });
        var params=list;
        admin.req({
            url: layui.setter.baseUrl+'admin/goods/ajaxGetSpecInput'
            ,data: params,
            type:'get',
            success: function(res){
                if(res.code==1){
                    $('#tableTpl').html(res.data);
                    get_inp_val();
                    hbdyg();
                    form.render();
                }else{
                    layer.msg(res.msg);
                }       
            } 
        }); 
    }
    // 给规格input加blur事件
    function get_inp_val() {
        console.log(change_java);
        
        for(var i in change_java){
            $('.secondfloor input[name="'+change_java[i].ch_name+'"]').val( change_java[i].ch_val );
        }
        // $('.secondfloor input[name="item[96][spec_sku]"]').val('123');
        $('#tableTpl .secondfloor input[type="text"]').each(function() {
            var val=$(this).val();
            // console.log(val);
            $(this).attr('onblur','change_val(this);');
            
        })
    };
    // blur事件
    window.change_val=function($this) {
        ch_name=$($this).attr('name');
        ch_val=$($this).val();
        // console.log(ch_name+'------name');
        // console.log(ch_val+'------val');
        change_java.push({'ch_name':ch_name,'ch_val':ch_val});
        // console.log(change_java);
    }

    // 选择商品规格旁边的img
    function setpl_img(){
        $("#leixing_1 .spec_img").each(function(index,element){
            var id = $(element).attr('id');
            var item_id = $(element).attr('item_id');
            var pic  = $(element).attr('pic');

            var img='<div class="pic_div" id="pic_div'+item_id+'"></div><i class="layui-icon layui-icon-close-fill lay_ico layui-hide" onclick="delete_img(this);"></i>';
            $('#item_pic_'+item_id).html(img);            
            upload.render({
                elem: '#pic_div'+item_id
                ,url: layui.setter.baseUrl+'admin/upload/upload'
                ,multiple: true
                ,done: function(res){
                    console.log(res);
                    var img='<img src="'+ res.data +'" alt="">';
                    //上传完毕
                    $('#pic_div'+item_id).html(img);  
                    $('#item_pic_'+item_id+' .lay_ico').removeClass('layui-hide');    
                    $('#item_pic_'+item_id).attr('pic',res.data); 
                    $("input[name='item_img["+item_id+"]']").val(res.data); 
                    layer.msg(res.msg); 
                }
            });
        });
    }
    // 删除图片
    window.delete_img = function (that) {
        var id = $(that).parents('span').attr('id');
        var item_id = $(that).parents('span').attr('item_id');  
    
        $('#pic_div'+item_id).html("");
        $('#item_pic_'+item_id+' .lay_ico').addClass('layui-hide');   
        $("input[name='item_img["+item_id+"]']").val(""); 
    }
    
    
    function hbdyg() {
        var tab = document.getElementById("tableTpl"); //要合并的tableID
        var maxCol = 2, val, count, start;  //maxCol：合并单元格作用到多少列
        if (tab != null) {
            for (var col = maxCol - 1; col >= 0; col--) {
                count = 1;
                val = "";
                for (var i = 0; i < tab.rows.length; i++) {
                    if (val == tab.rows[i].cells[col].innerHTML) {
                        count++;
                    } else {
                        if (count > 1) { //合并
                            start = i - count;
                            tab.rows[start].cells[col].rowSpan = count;
                            for (var j = start + 1; j < i; j++) {
                                tab.rows[j].cells[col].style.display = "none";
                            }
                            count = 1;
                        }
                        val = tab.rows[i].cells[col].innerHTML;
                    }
                }
                if (count > 1) { //合并，最后几行相同的情况下
                    start = i - count;
                    tab.rows[start].cells[col].rowSpan = count;
                    for (var j = start + 1; j < i; j++) {
                        tab.rows[j].cells[col].style.display = "none";
                    }
                }
            }
        }
    }

    
    
    form.render();



});
exports("add_goods", {});
});
