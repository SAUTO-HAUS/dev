<?php defined( '_DOIT' ) or die( 'Restricted access' );
$rtrn = '
<div class="gr">
	<h1 style="text-align:left;">'.$sa['meta']['h1'].'</h1>
	<div class="cnt">';	
		$card = $car_card('new', 4, null); $rtrn .= $card['txt'];
	$rtrn .= '
	</div>
</div>

<div class="gr">
	<h2>'.$lng['t']['x']['top_auto'].'</h2>
	<div class="cnt">';
		$card = $car_card('top', 4, null); $rtrn .= $card['txt'];
	$rtrn .= '
	</div>
</div>

<a href="/'.$_COOKIE['lang'].'/cars" class="z_lnk">'.$lng['w']['see_all'].'</a>

<div class="gr">
	<h2>'.$lng['t']['x']['our_serv'].'</h2>
	<div class="cnt">';
		
		foreach ($grp_arr['o_serv'] as $v){
			$href = in_array($v['href'], ['tradein', 'order']) ? '/'.$_COOKIE['lang'].'/'.$v['href'] : '/'.$_COOKIE['lang'].'/services/'.$v['href'];
			$rtrn .= '
			<a href="'.$href.'" class="lnk">
				<img src="/'._SITE_IMG.'/v2/'.$v['img'].'.svg" />
				<h4 class="ttl">'.$v['ttl'].'</h4>
				<p class="txt">'.$v['txt'].'</p>
			</a>
			';
		}
	$rtrn .= '
	</div>
</div>

<div class="gr">
	<h2>'.$lng['p']['home']['ttl'].'</h2>
	<div class="txt">'.$lng['p']['home']['txt'].'</div>
</div>';

echo $rtrn;

echo '<script>(function(){var r=document.querySelector("body > .srt_row");if(!r)return;var h=document.querySelector("main .gr > h1");if(!h||!h.textContent.trim())return;h.classList.add("srt_h1");r.insertBefore(h,r.firstChild);})();</script>';
?>