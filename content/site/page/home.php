<?php defined( '_DOIT' ) or die( 'Restricted access' );
$rtrn = '
<div class="gr">
	<h1 style="font-size:inherit; text-align:center;">'.$sa['meta']['h1'].'</h1>
	<h2>'.$lng['w']['novelty'].'</h2>
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
			$href = ($v['href'] == 'tradein') ? '/'.$_COOKIE['lang'].'/tradein' : '/'.$_COOKIE['lang'].'/services/'.$v['href'];
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
?>