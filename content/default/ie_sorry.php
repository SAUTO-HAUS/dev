 <?php defined( '_DOIT' ) or die( 'Restricted access' );

$ch = array ('http://google.ru/intl/ru/chrome/browser/', 'chrome', 'Google - Chrome');
$ff = array ('http://mozilla.org/firefox/new/', 'firefox', 'Mozilla - Firefox');
$op = array ('http://opera.com/', 'opera', 'Opera Software - Opera');
$ie = array ('http://windows.microsoft.com/ru-ru/internet-explorer/download-ie', 'ie', 'Microsoft - Internet Explorer');
$browsers = array ($ch, $ff, $op, $ie);

echo '
<style>
	table {width:100%; text-align:center; color:#333;}
	a {color:#00BCF2;}

	img {width:27%;}

	.shadowed {
    	-webkit-filter: drop-shadow(12px 12px 25px rgba(0,0,0,0.5));
    	filter: url(#drop-shadow);
    	-ms-filter: "progid:DXImageTransform.Microsoft.Dropshadow(OffX=12, OffY=12, Color="#444")";
    	filter: "progid:DXImageTransform.Microsoft.Dropshadow(OffX=12, OffY=12, Color="#444")";
	}
</style>


<svg height="0" xmlns="http://www.w3.org/2000/svg">
    <filter id="drop-shadow">
        <feGaussianBlur in="SourceAlpha" stdDeviation="4"/>
        <feOffset dx="7" dy="7" result="offsetblur"/>
        <feFlood flood-color="rgba(0,0,0,0.2)"/>
        <feComposite in2="offsetblur" operator="in"/>
        <feMerge>
            <feMergeNode/>
            <feMergeNode in="SourceGraphic"/>
        </feMerge>
    </filter>
</svg>


<table>
	
	<tr>
		<td colspan="4"><br /><br /><br /><br /><br /><br />
			Здравствуйте, приносим свои извинения за беспокойство.<br /><br />
			Для полноценной работы функционала сайта, вам желательно воспользоваться Internet Explorer\'ом версии 9.0 или выше, либо иным современным браузером.<br /><br /><br /><br />
	
			Ссылки на официальные сайты популярных браузеров:<br /><br />
		</td>
	</tr>
	
	<tr>';

	foreach ($browsers as $key){
		echo '
		<td>
			<a href="'.$key[0].'" target="_blank">
				<img  src="'._DEFAULT_IMG.'/browsers_logo/'.$key[1].'.png"><br />
				'.$key[2].'<br />
			</a><br /><br />
		</td>
		';
	}
	
	echo '
	</tr>
	
	<tr>	
		<td colspan="4">
			<a href="/">
				<img src="'._DEFAULT_IMG.'/browsers_logo/home.png" style="width:7%;"><br />
				Войти на сайт
			</a>
		</td>
	</tr>

</table>
';
?> 
