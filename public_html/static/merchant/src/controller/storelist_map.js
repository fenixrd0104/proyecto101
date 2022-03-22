layui.define(["jquery","bMap"],function(exports){
    var $ = layui.$
    var jQuery=layui.jquery;
    var bMap=layui.bMap;
    
  

    var currentPoint;
    var marker1;
    var marker2;
    var myGeo;
   

    //创建和初始化地图函数：
    function initMap(){
        createMap();//创建地图
        setMapEvent();//设置地图事件
        addMapControl();//向地图添加控件
        addMarker();//向地图中添加marker
    }
    
    //创建地图函数：
    function createMap(){
        var map = new BMap.Map("dituContent");//在百度地图容器中创建一个地图
        myGeo = new BMap.Geocoder();
        var point = new BMap.Point(116.395645,39.929986);//定义一个中心点坐标
        map.centerAndZoom(point,12);//设定地图的中心点和坐标并将地图显示在地图容器中
        window.map = map;//将map变量存储在全局
        currentPoint = point;
    }
    
    //地图事件设置函数：
    function setMapEvent(){
        map.enableDragging();//启用地图拖拽事件，默认启用(可不写)
        map.enableScrollWheelZoom();//启用地图滚轮放大缩小
        map.enableDoubleClickZoom();//启用鼠标双击放大，默认启用(可不写)
        map.enableKeyboard();//启用键盘上下左右键移动地图
    }
    
    //地图控件添加函数：
    function addMapControl(){
        //向地图中添加缩放控件
	var ctrl_nav = new BMap.NavigationControl({anchor:BMAP_ANCHOR_TOP_LEFT,type:BMAP_NAVIGATION_CONTROL_LARGE});
	map.addControl(ctrl_nav);
        //向地图中添加缩略图控件
	var ctrl_ove = new BMap.OverviewMapControl({anchor:BMAP_ANCHOR_BOTTOM_RIGHT,isOpen:1});
	map.addControl(ctrl_ove);
        //向地图中添加比例尺控件
	var ctrl_sca = new BMap.ScaleControl({anchor:BMAP_ANCHOR_BOTTOM_LEFT});
	map.addControl(ctrl_sca);
    }
    
    // //标注点数组
    // var markerArr = [{title:"我的标记",content:"我的备注",point:"116.395932|39.934412",isOpen:0,icon:{w:21,h:21,l:0,t:0,x:6,lb:5}}
	// 	 ];
    // //创建marker
    // function addMarker(){
    //     for(var i=0;i<markerArr.length;i++){
    //         var json = markerArr[i];
    //         var p0 = json.point.split("|")[0];
    //         var p1 = json.point.split("|")[1];
    //         var point = new BMap.Point(p0,p1);
	// 		var iconImg = createIcon(json.icon);
    //         var marker = new BMap.Marker(point,{icon:iconImg});
	// 		var iw = createInfoWindow(i);
	// 		var label = new BMap.Label(json.title,{"offset":new BMap.Size(json.icon.lb-json.icon.x+10,-20)});
	// 		marker.setLabel(label);
    //         map.addOverlay(marker);
    //         label.setStyle({
    //                     borderColor:"#808080",
    //                     color:"#333",
    //                     cursor:"pointer"
    //         });
			
	// 		(function(){
	// 			var index = i;
	// 			var _iw = createInfoWindow(i);
	// 			var _marker = marker;
	// 			_marker.addEventListener("click",function(){
	// 			    this.openInfoWindow(_iw);
	// 		    });
	// 		    _iw.addEventListener("open",function(){
	// 			    _marker.getLabel().hide();
	// 		    })
	// 		    _iw.addEventListener("close",function(){
	// 			    _marker.getLabel().show();
	// 		    })
	// 			label.addEventListener("click",function(){
	// 			    _marker.openInfoWindow(_iw);
	// 		    })
	// 			if(!!json.isOpen){
	// 				label.hide();
	// 				_marker.openInfoWindow(_iw);
	// 			}
	// 		})()
    //     }
    // }
    // //创建InfoWindow
    // function createInfoWindow(i){
    //     var json = markerArr[i];
    //     var iw = new BMap.InfoWindow("<b class='iw_poi_title' title='" + json.title + "'>" + json.title + "</b><div class='iw_poi_content'>"+json.content+"</div>");
    //     return iw;
    // }
    // //创建一个Icon
    // function createIcon(json){
    //     var icon = new BMap.Icon("http://app.baidu.com/map/images/us_mk_icon.png", new BMap.Size(json.w,json.h),{imageOffset: new BMap.Size(-json.l,-json.t),infoWindowOffset:new BMap.Size(json.lb+5,1),offset:new BMap.Size(json.x,json.h)})
    //     return icon;
    // }



    //创建marker
    function addMarker() {

        marker1 = new BMap.Marker(currentPoint);        // 创建标注
        map.addOverlay(marker1);
        var opts = {
            width: 220,     // 信息窗口宽度 220-730
            height: 60,     // 信息窗口高度 60-650
            title: ""  // 信息窗口标题
        }

        var infoWindow = new BMap.InfoWindow("拖拽地图或红点，在地图上用红点标注您的店铺位置。", opts);  // 创建信息窗口对象
        marker1.openInfoWindow(infoWindow);      // 打开信息窗口

        marker2 = new BMap.Marker(currentPoint);        // 创建标注  
        map.addOverlay(marker2);                     // 将标注添加到地图中
        marker2.enableDragging();
        marker2.addEventListener("dragend", function (e) {

            myGeo.getLocation(new BMap.Point(e.point.lng, e.point.lat), function (result) {
                if (result) {

                    marker1.setPoint(new BMap.Point(e.point.lng, e.point.lat));        // 移动标注
                    marker2.setPoint(new BMap.Point(e.point.lng, e.point.lat));
                    map.panTo(new BMap.Point(e.point.lng, e.point.lat));

                    currentPoint.lng = e.point.lng;
                    currentPoint.lat = e.point.lat;
                }
            });
        });

        map.addEventListener("dragend", function showInfo() {
            var cp = map.getCenter();
            myGeo.getLocation(new BMap.Point(cp.lng, cp.lat), function (result) {
                if (result) {

                    marker1.setPoint(new BMap.Point(cp.lng, cp.lat));        // 移动标注
                    marker2.setPoint(new BMap.Point(cp.lng, cp.lat));
                    map.panTo(new BMap.Point(cp.lng, cp.lat));

                    currentPoint.lng = cp.lng;
                    currentPoint.lat = cp.lat;
                }
            });
        });

        map.addEventListener("dragging", function showInfo() {
            var cp = map.getCenter();
            marker1.setPoint(new BMap.Point(cp.lng, cp.lat));        // 移动标注
            marker2.setPoint(new BMap.Point(cp.lng, cp.lat));

            currentPoint.lng = cp.lng;
            currentPoint.lat = cp.lat;
        });

    }

    //设置区域    
    exports('setarrea',function (address, province) {
        // 将结果显示在地图上，并调整地图视野  
        window.setTimeout(function () {
            myGeo.getPoint(address, function (point) {
                if (point) {
                    marker1.setPoint(new BMap.Point(point.lng, point.lat));        // 移动标注
                    marker2.setPoint(new BMap.Point(point.lng, point.lat));
                    map.panTo(new BMap.Point(marker2.getPoint().lng, marker2.getPoint().lat));
                    map.centerAndZoom(marker2.getPoint(), map.getZoom());
                    currentPoint.lng = point.lng;
                    currentPoint.lat = point.lat;
                }
            }, province);
        }, 1000);
    });

    //定位地址
    function initarreawithpoint(lng, lat) {
        window.setTimeout(function () {
            marker1.setPoint(new BMap.Point(lng, lat));        // 移动标注
            marker2.setPoint(new BMap.Point(lng, lat));
            map.panTo(new BMap.Point(lng, lat));
            map.centerAndZoom(marker2.getPoint(), map.getZoom());
            currentPoint.lng = lng;
            currentPoint.lat = lat;
        }, 1000);
    }

    
    initMap();//创建和初始化地图 
    
    

    exports('storelist_map',{});

  });