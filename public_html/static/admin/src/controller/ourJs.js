layui.define(['jquery'],function(exports) {
    var $ = layui.jquery;
    var obj={
        status_tong:function(table,_this,status,type)
        {
            type=type==undefined?'status':type;
            var arr=table.cache;
            var data='';
            for(var i in arr){
                data=arr[i];
            }           
            var td=$(_this).parent().parent();
            var tr=td.parent().attr('data-index');
            if (status === true) {
                status = 1;
            } else {
                status = 0;
            }
            for(var i in data[tr]){
                if(i==type){
                    data[tr][i]=status;
                    break;
                }
            }
            
        }
    }
    exports("ourJs", obj);
});