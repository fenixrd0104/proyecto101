/*
 * @Author: 王世文1198627433@qq.com 
 * @Date: 2019-06-18 09:30:13 
 * @Last Modified by: 王世文1198627433@qq.com
 * @Last Modified time: 2019-06-27 13:50:44
 */
layui.define(function(exports) {
    layui.use(['admin','table','form','view','upload','layedit','laydate'], function(){
        var $ = layui.$
        ,admin = layui.admin
        ,table = layui.table
        ,layer = layui.layer
        ,element = layui.element
        ,layedit = layui.layedit
        ,setter = layui.setter
        ,upload = layui.upload
        ,laydate = layui.laydate
        ,view = layui.view
        ,form = layui.form
        ,device = layui.device();
        
        form.render();
        //日期时间选择器
        laydate.render({
            elem: '#test-laydate-type-datetime-start'
            ,type: 'datetime'
        });
        laydate.render({
            elem: '#test-laydate-type-datetime-end'
            ,type: 'datetime'
        });
  
  
        table.render({
        elem: '#test-table-purchase'
        ,url: layui.setter.baseUrl+'admin/purchase_order/listsOrder'
        ,toolbar: '#test-table-toolbar-purchase'
        ,parseData: function(res){ //res 即为原始返回的数据
            return {
            "code": res.code, //解析接口状态
            "msg": res.msg, //解析提示文本
            "count": res.data.count, //解析数据长度
            "data": res.data.lists //解析数据列表
            };
        }
        ,totalRow: true
      //   ,cellMinWidth: 80 //全局定义常规单元格的最小宽度，layui 2.2.1 新增
        ,cols: [[
          {field:'id', width:80, title: 'ID', sort: true,totalRowText: '合计',align:"center"}
          ,{field:'username', title: '单号',align:"center"}
          ,{field:'supplier_name', title: '供应商',align:"center"}
          ,{field:'order_status', title: '单据状态',align:"center"}
          ,{field:'receipt_status', title: '收货状态',align:"center"}
          ,{field:'order_money', width:120, title: '单据金额',totalRow: true,align:"center"}
          ,{field:'delivery_date', title: '单据日期',align:"center"} //minWidth：局部定义当前单元格的最小宽度，layui 2.2.1 新增
          ,{field:'receiving_shop', title: '收货人',align:"center"}
          ,{field:'phone', title: '电话',align:"center"}
          ,{field:'addr', title: '地址',width:200,align:"center"}
          ,{fixed: 'right', title:'操作', toolbar: '#test-table-toolbar-purchase2', width:160,align:"center"}
        ]],
        page:true
      });
      // 添加
      $('body').on('click','#new_purchase',function(){
          new_purchase();
      });
      function new_purchase(){
          admin.popup({
              title: "供应商列表",
              area: ["80%", "80%"],
              id: "LAY-popup-new_purchase",
              success: function(e, i) {
                  view(this.id).render('/purchase/new_purchase').done(function() {
                  });
              }
          });
      }










      




    });
    exports("purchase_list", {});
});
