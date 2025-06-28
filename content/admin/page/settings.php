<?php

echo '
<form action="" method="post">
';

if ($_POST['Изменить']){
	
	$catalog_update = $_REQUEST['catalog_update'];
		
	include ('templates/sauto/php/main/dbinfo.php');
	$database->query("UPDATE sa_my_settings SET value='$catalog_update' WHERE name='car_catalog_update_time'");
	$database->close();
}

include ('templates/sauto/php/main/dbinfo.php');
$result = mysqli_query($database,'SELECT * FROM sa_my_settings WHERE name="car_catalog_update_time"');
$row_cnt = mysqli_num_rows($result);

while ($row = mysqli_fetch_array($result)){
    $option_id = $row['id'];
	$option_name = $row['name'];
	$option_value = $row['value'];
	
	echo '<span title="Изменить расположение автомобилей в каталоге">Обновлять каталог раз в <input type="text" size="3" style="text-align:center" name="catalog_update" value="'.$option_value.'"> час(а/ов)</span>';
;}

echo '
	<input style="color:#fff; border:0; background:#5a5; cursor:pointer; font-size: 15px; float:right; margin-top:50px;" type="submit" name="Изменить" value="'.$lang_name_change.'" tabindex="20">
</form>
';	
$database->close();
//_____________________________________________________________________________________________________________________
if ($_SERVER['REMOTE_ADDR'] != '178.168.59.133aaa'){echo '
	<style>
		#open_cash {width:200px; height:100px; line-height:100px; background: #777; text-align:center; cursor:pointer; color:#fff; margin-top:200px;}
		#cash_content {width:0; height:0; padding:50px; margin-top:20px; overflow: hidden; opacity:0;}
		
		.c_gr {color:#77cc77;}
		.c_ye {color:#ccaa00;}
		
		#rashodi {width:90%; font-size:10px; background:#eee; border:1px solid #000; text-align:center;}
		
		#rashodi tr {height:50px;}
		
		#rashodi td {border:1px solid #999; width: 100px;}
		
		.grayIt {background:#dddddd;}
	</style>
	
	<div id="open_cash">Открыть расходы</div>
		
	<div id="cash_content">
	
		Выполненная работа:<br /><br /><br />
	
		1. Предподгрузка фото (<span class="c_gr">Поддержка сайта</span>) <br /><br />

		2. Резкий переход от фото к фото (<span class="c_gr">Поддержка сайта</span>) <br /><br />

		3. Доработана полоса фотографий в инфо авто (<span class="c_gr">Поддержка сайта</span>) <br /><br />

		4. Переделана система similar items +добавил поиск схожих по типу кузова (позже: Улучшил надежность) (<span class="c_gr">Поддержка сайта</span>) <br /><br />

		5. Скролл каталога пальцем (<span class="c_gr">Поддержка сайта</span>) <br /><br />

		6. Система водного знака (<span class="c_ye">10 евро</span>) <br /><br />

		7. Скачивание фоток архивом (<span class="c_ye">5 евро</span>) <br /><br />

		8. Админка Опции сайта  + система обновления (<span class="c_ye">10 евро</span>) <br /><br />

		9. Система бана (<span class="c_ye">20 евро</span>) <br /><br />

		10. Блокировка скачивания (<span class="c_ye">10 евро</span>) <br /><br />

		11. Счетчик просмотров (<span class="c_gr">Поддержка сайта</span>) <br /><br />

		12. Система сортировки каталога (<span class="c_ye">10 евро</span>) <br /><br />

		13. Админка - Машина дня (<span class="c_gr">Поддержка сайта</span>) <br /><br />

		14. Админка для видео (<span class="c_gr">Поддержка сайта</span>) <br /><br />

		15. Логотип (<span class="c_ye">20 евро</span>) <br /><br />

		16. Новые баннеры (<span class="c_ye">10 евро</span>) <br /><br />

		17. Фильтр поиска (<span class="c_ye">20 евро</span>) <br /><br />

		18. Улучшил визуализацию для моб версии (Отступы и т.д.)  (<span class="c_gr">Поддержка сайта</span>) <br /><br />

		19. Изменил карту (в контактах) (<span class="c_gr">Поддержка сайта</span>) <br /><br />

		20. Отфиксил выдачу автомибей в поиске (решены: пагинация и глюк при котором 2 раза нельзя было делать поиск) (<span class="c_gr">Поддержка сайта</span>) <br /><br />

		21. Страница инфо авто - если пункты меню пустые, теперь они не отображаются (Комфорт, безопасность, доп инфо) (<span class="c_gr">Поддержка сайта</span>) <br /><br />

		22. Добавил в админку возможность качать фотографии с водяным знаком  (<span class="c_gr">Поддержка сайта</span>) <br /><br />
		
		23. Админка - Скрытый автомобиль (<span class="c_gr">Поддержка сайта</span>)<br /><br />
		
		24. Прочие внутренние настройки/изменения (<span class="c_gr">Поддержка сайта</span>)<br /><br />
		
		ИТОГО: 115е<br /><br /><br /><br />
		
		Деньги:<br /><br /><br />

		100 евро - Админка Nolastudio (за задержку с моей стороны половина [50е])<br /><br />

		200 евро - За пожизненную поддержку Sauto<br /><br />

		200 евро - В долг (вернул)<br /><br />

		300 евро - сайт Nolastudio<br /><br />

		100 + 200 + 200 + 300 = 800<br /><br />
		
		<table id="rashodi">
			<tr>
				<td>Наименование</td>
				<td class="grayIt">Общая сумма выплат</td>
				<td>Долг(вернул)</td>
				<td class="grayIt">Сайт Nolastudio</td>
				<td>Sauto поддержка <br /> пожизненная / оплата</td>
				<td class="grayIt">Nolastudio поддержка</td>
				<td>Выполненная работа</td>
				<td class="grayIt">Админка Nolastudio</td>
				<td>Итого</td>
			</tr>
			<tr>
				<td>При пожизненной поддержке Sauto</td>
				<td class="grayIt">800</td>
				<td>200</td>
				<td class="grayIt">300</td>
				<td>200</td>
				<td class="grayIt">120</td>
				<td>115-35 = 80</td>
				<td class="grayIt">100/2 = 50</td>
				<td>150</td>
			</tr>
			<tr>
				<td>Без пожизненной поддержки Sauto</td>
				<td class="grayIt">800</td>
				<td>200</td>
				<td class="grayIt">300</td>
				<td>120</td>
				<td class="grayIt">120</td>
				<td>115</td>
				<td class="grayIt">100/2 = 50</td>
				<td>105</td>
			</tr>
		</table>
	</div>
		
	<script>
		var someVar = 0;
	
		$("#open_cash").click(function(){
			if (someVar == 0){
				someVar = 1;
				$(this).text("Закрыть расходы");
				$("#cash_content").css({"width":"100%" , "height":"100%" , opacity : 1});
			}
			else {
				someVar = 0;
				$(this).text("Открыть расходы");
				$("#cash_content").css({"width":"0" , "height":"0" , opacity : 0});
			}
		})
	</script>
';}
//_____________________________________________________________________________________________________________________
?>