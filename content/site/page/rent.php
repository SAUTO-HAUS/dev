<?php defined( '_DOIT' ) or die( 'Restricted access' );

$cr_lmt = $isMobile=='1' ? 10 : 60;

echo '
<div id="rent">
	<div class="gr t">
		<div class="bnr" style="background-image:url(/media/images/site/v2/banner_rent'.$img_frmt.');"></div>
		<div class="fltr">
			<div class="ttl">Поиск автомобиля</div>
			<div class="zone">
				<div class="sel take">
					<span class="ttl">Забрать</span>
					<select name="zone_take">
						<option select="select">Calea Mosilor 11</option>
					</select>
				</div>
				<div class="sel drop">
					<span class="ttl">Отдать</span>
					<select name="zone_drop">
						<option select="select">Calea Mosilor 11</option>
					</select>
				</div>
			</div>
			<div class="date">
				<div class="val from">
					<span class="ttl">Начало</span>
					<input type="text" class="dt f" id="date_f" name="date_f" placeholder="'.date( 'd/m/Y', time() ).'">
					<input type="text" class="tm f" name="time_f" value="10:00" placeholder="10:00" disabled>
				</div>
				<div class="val to">
					<span class="ttl">Завершение</span>
					<input type="text" class="dt t" id="date_t" name="date_t" placeholder="'.date( 'd/m/Y', ( time()+(86400*3) ) ).'">
					<input type="text" class="tm t" name="time_t" value="10:00" placeholder="10:00" disabled>
				</div>
			</div>
			<div class="sbmt">Найти автомобиль</div>
		</div>
	</div>
	<div class="gr sort">
		<div class="show"></div>
		<div class="ctrl"></div>
	</div>
	<div class="gr">
		<div class="cnt list">';
			echo rent_card($prefx, $db, $img_frmt, $lng, 'new', $cr_lmt, null);
		echo '
		</div>
	</div>
	<div class="gr pages"></div>
	<div class="gr bx">
		<div class="b">
			<div class="ttl">Аренда автомобиля в Кишиневе</div>
			<div class="txt">Наличие собственного автомобиля дает возможность комфортно передвигаться по городу и за его пределами. Однако часто бывают ситуации, когда личный транспорт недоступен. В этом случае поможет аренда авто в Кишиневе – вы можете оформить недорогой прокат автомобиля на сутки, или аренду на неделю или месяц и пользоваться машиной как своей. Компания «SAUTO» оказывает услуги по аренде автомобилей без водителя в Кишиневе на любой срок.</div>
			<div class="img" style="background-image:url(/media/images/site/v2/rent_cars'.$img_frmt.');"></div>
		</div>
		<div class="s">';
			foreach($rent_txt_arr as $k => $v){
				echo '
				<div class="it">
					<div class="img" style="background-image:url(/media/images/site/v2/'.$v['img'].');"></div>
					<div class="ttl">'.$v['ttl'].'</div>
					<div class="txt">'.$v['txt'].'</div>
				</div>';
			}
		echo '
		</div>
	</div>
	<div class="gr map">
		<script type="text/javascript" charset="utf-8" async src="https://api-maps.yandex.ru/services/constructor/1.0/js/?um=constructor%3A9436e8c74c7c261c951aabf0a86a136d71754121a416010f3a0dcd5732497317&amp;width=100%25&amp;height=720&amp;lang='.$_COOKIE['lang'].'&amp;scroll=true"></script>
	</div>
</div>';

$i=1;
$day_min_list='';
$day_list=''; 
foreach($lng['l']['date']['day'] as $k => $v){
	$ii=($i==1)?'':', ';
	$day_min_list .= $ii.'"'.$v['s'].'"';
	$day_list .= $ii.'"'.$v['l'].'"';
	$i++;
}

$i=1;
$month_list='';
foreach($lng['l']['date']['month'] as $k => $v){
	$ii=($i==1)?'':', ';
	//$ii0 = ($i>=10)?'':'0';
	//$month_min_list .= $ii.'"'.$ii0.($i).', '.$v['s'].'"';
	$month_list .= $ii.'"'.$v['l'].'"';
	$i++;
}

?>

<script>
	$( function() {
		var dateFormat = 'dd/mm/yy',
		from = $('#rent .dt.f').datepicker({
			dateFormat: "dd/mm/yy",
			firstDay:1,
			minDate: 0,
			showAnim:'slideDown',
			//defaultDate:'+1w',
			changeMonth:true,
			numberOfMonths:1,
			dayNamesMin: [ <?php echo $day_min_list; ?>],
			dayNames: [ <?php echo $day_list; ?> ],
			monthNamesShort: [ <?php echo $month_list; ?> ]
		}).on( "change", function(){ to.datepicker( "option", "minDate", getDate( this ) ); }),
		to = $('#rent .dt.t').datepicker({
			dateFormat: "dd/mm/yy",
			firstDay:1,
			minDate: 0,
			showAnim:'slideDown',
			//defaultDate:'+1w',
			changeMonth:true,
			numberOfMonths:1,
			dayNamesMin: [ <?php echo $day_min_list; ?>],
			dayNames: [ <?php echo $day_list; ?> ],
			monthNamesShort: [ <?php echo $month_list; ?> ]
		}).on( "change", function(){ from.datepicker( "option", "maxDate", getDate( this ) ); });
		function getDate( element ){
			var date;
			try { date = $.datepicker.parseDate( dateFormat, element.value ); }
			catch( error ) { date = null; }
			return date;
		}
	})
</script>

<style>
	#ui-datepicker-div {font-family:"def_l";}
	.ui-widget-header {border:none; border-bottom:1px solid #ddd; background:#fff; font-weight:normal;}
	.ui-widget-content {color:#e2001a;}
	.ui-datepicker select.ui-datepicker-month {border:1px solid #cecece; background-color:#f4f4f4; font-family:"def_l";}
	select.ui-datepicker-month > option {font-family:verdana, arial;}
	.ui-widget select {font-family:"def_l";}
	.ui-datepicker td {font-family:"def_l";}
	.ui-datepicker th {font-weight:normal;}
	.ui-datepicker-week-end > .ui-state-default {background-color:#e8e8e8;}
	.ui-state-active, .ui-widget-content .ui-state-active {background:#007fff !important;}
</style>