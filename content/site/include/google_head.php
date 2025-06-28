<?php defined( '_DOIT' ) or die( 'Restricted access' ); ?>

<!-- Google Map -->
<style>
	#map_canvas {
		width: 695px;
		height: 300px;
	}
</style>
    
<script src="https://maps.googleapis.com/maps/api/js"></script>

<script>
	function initialize() {
		var mapCanvas = document.getElementById('map_canvas');
		var mapOptions = {
			center: new google.maps.LatLng(47.0368202, 28.82579),
			zoom: 17,
			streetViewControl: false,
			zoomControlOptions: {position : google.maps.ControlPosition.LEFT_CENTER},
			mapTypeId: google.maps.MapTypeId.SATELLITE
		}
		var marker = new google.maps.Marker({
			position: new google.maps.LatLng(47.0363202, 28.82546),
			title:"Sauto",
			animation: google.maps.Animation.BOUNCE,
			icon: 'templates/sauto/images/sauto_google.png'
		});
		var styles = [ { "stylers": [ { "visibility": "simplified" }, { "hue": "#ff0000" }, { "invert_lightness": true }, { "saturation": -74 } ] } ];
		var map = new google.maps.Map(mapCanvas, mapOptions)
			marker.setMap(map);
			map.setOptions({styles: styles});
	}
	google.maps.event.addDomListener(window, 'load', initialize);
</script>
<!-- End Google Map --> 

<?php include_once("analyticstracking.php"); ?>

<!-- Google Tag Manager -->
<script>(function(w,d,s,l,i){w[l]=w[l]||[];w[l].push({'gtm.start':
new Date().getTime(),event:'gtm.js'});var f=d.getElementsByTagName(s)[0],
j=d.createElement(s),dl=l!='dataLayer'?'&l='+l:'';j.async=true;j.src=
'//www.googletagmanager.com/gtm.js?id='+i+dl;f.parentNode.insertBefore(j,f);
})(window,document,'script','dataLayer','GTM-MG9WJ9');</script>
<!-- End Google Tag Manager -->