<?php defined( '_DOIT' ) or die( 'Restricted access' );

use App\Helper\PhoneHelper;

require_once($_SERVER['DOCUMENT_ROOT'] . '/content/default/includes/contact_form.php');

// Get phone number for contacts page
$generalPhone = PhoneHelper::getGeneralPhone();
$formattedPhone = PhoneHelper::formatPhone($generalPhone, 'display');

// BITRIX disabled: inline/4/nj2ojp, inline/22/wwqx7u, inline/32/wvx2ou
$current_lang = $_COOKIE['lang'] ?? 'ro';
ob_start();
sauto_contact_form(['lang' => $current_lang, 'source' => 'contacts']);
$form = ob_get_clean();
echo '
<script>
	$(document).ready(function(){
		$("#cnt > .bx > .prtr > .arw").on("click", function(){
			
			var zItems = $("#cnt > .bx > .prtr > .hider > .its");
			
			var zMove = zItems.children(".it:first-child").next().outerWidth( true );
			var zCount = zItems.children(".it").length;
			
			var zNumb = zItems.attr("data-numb");
			zCount = zCount;
			zItems.attr("data-count", zCount);
			
			var zPos = zMove*zNumb*(-1);
			
			if ( $(this).hasClass("l") ){
				//if ( parseInt(zItems.css("left"), 10) > 0 ){zItems.css("left","0px");}
				//if ( (parseInt(zItems.css("left"), 10)-$("#cnt > .bx > .prtr").width()) <zItems.width()*(-1) ){zItems.css("left",zItems.width()*(-1)+((zItems.width()/zItems.children(".it").length)*7)+"px");}
				zItems.css("left", (zPos+zMove)*3+"px").attr("data-numb", zNumb*1-1);
			} else if ( $(this).hasClass("r") ) {
				zItems.css("left", (zPos-zMove)*3+"px").attr("data-numb", zNumb*1+1);
			}
			
		})
	
	
	
	
	
	
		/*
		$("#cnt > .bx > .prtr").mousedown(mDown);
		
		function mDown(e){
			
			//stuff
			var bxPos = $("#cnt > .bx > .prtr > .hider > .its").offset().left;
			//var bxWidth = $("#cnt > .bx > .prtr > .hider > .its").outerWidth();
			
			$("#cnt > .bx > .prtr > .hider > .its").attr({ "data-m-start-time" : + new Date(), "data-m-start-pos" : e.pageX });
			$(window).mousemove(mMove).mouseup(mUp);
		}
		
		function mMove(e){
			//stuff
			var zIts = $("#cnt > .bx > .prtr > .hider > .its");
			
			var mStartPos = zIts.attr("data-m-start-pos");
			var bxPos = parseInt(zIts.css("margin-left"), 10);
			var bxMove = bxPos + ( e.pageX - mStartPos );
			
			zIts.attr({ "data-zzz" : (mStartPos+" - "+e.pageX+" = "+bxMove+"["+bxPos+"]"), "data-m-now-pos" : e.pageX, "data-bx-start" : bxPos }).css("margin-left", bxMove+"px");
		}
		
		function mUp(e){
			//$(someElement).trigger("yourcustomevent");
			
			//stuff
			$("#cnt > .bx > .prtr > .hider > .its").attr({"data-m-end-time" : + new Date(), "data-m-end-pos" : e.pageX }).removeClass("moving");
			$(window).unbind("mousemove", mMove).unbind("mouseup", mUp);
		}
		*/
	
		$("#cnt > .bx > .prtr > .hider > .its").draggable({
			axis: "x",
			//bounds: {minX: 0, minY: 0, maxX: 200, maxY: 200},
			drag: function (e, ui){
				y2 = ui.position.top;
				x2 = ui.position.left;
				if(x2<=0){$(this).css("left","0px");}
				//if(x2>100){alert("greater");}
				
				//$( ".it" ).clone().appendTo( "#cnt > .bx > .prtr > .hider > .its" )
			},
			start: function(e, ui) {
				$(this).attr({ "data-m-start-time" : + new Date(), "data-m-start-pos" : e.pageX }).addClass("moving");
			},
			stop: function(e, ui) {
				$(this).attr({"data-m-end-time" : + new Date(), "data-m-end-pos" : e.pageX }).removeClass("moving").delay(0).queue(function(){
					
					var zDstc = $(this).attr("data-m-end-pos") - $(this).attr("data-m-start-pos");
					var zTime = ($(this).attr("data-m-end-time") - $(this).attr("data-m-start-time") )/1000;
					if (zTime>2){zTime=0;}
					var prtrW = $("#cnt > .bx > .prtr").width();
					
					$(this).css( "left", ( parseInt($(this).css("left"), 10) + ( zDstc / zTime ) )+"px" ).dequeue().delay(1000).queue(function(){
						if ( parseInt($(this).css("left"), 10) > 0 ){$(this).css("left","0px");}
						if ( (parseInt($(this).css("left"), 10)-prtrW) < $(this).width()*(-1) ){$(this).css("left",$(this).width()*(-1)+(($(this).width()/$(this).children(".it").length)*7)+"px");}
						$(this).dequeue();
					})
				});
			}
		});	
	})
</script>

<?php
$generalPhone = PhoneHelper::getGeneralPhone();
$formattedPhone = PhoneHelper::formatPhone($generalPhone, "display");
?>

<div id="cnt">
	<div class="bx">
		<h1 class="ttl">'.$lng['w']['o_cntcts'].'</h1>
		<div class="gr cnt">
			<div class="it adr">
				<div class="img"></div>
				<h2 class="ttl">'.$lng['w']['address'].'</h2>
				<div class="txt">
					'.$lng['t']['x']['address'][0].'
					<ul>
						<li><a onclick="navigate(47.03038049808741, 28.855162562579835)" style="cursor:pointer;" >'.$lng['t']['x']['address'][1].'</a></li>
						<li><a onclick="navigate(47.05765228741261, 28.77507935382036)" style="cursor:pointer;" >'.$lng['t']['x']['address'][2].'</a></li>
					</ul>
				</div>
			</div>
			<div class="it scd">
				<div class="img"></div>
				<h2 class="ttl">'.$lng['w']['schedule'].'</h2>
				<div class="txt">
					'.$lng['l']['date']['day']['mon']['s'].' - '.$lng['l']['date']['day']['fri']['s'].' &emsp; <span>8:00 - 18:00</span>
					<br/>
					'.$lng['l']['date']['day']['sat']['s'].' - '.$lng['l']['date']['day']['sun']['s'].' &emsp; <span>9:00 - 16:00</span>
				</div>
			</div>
			<div class="it phn">
				<div class="img"></div>
				<h2 class="ttl">'.$lng['w']['phones'].'</h2>
				<div class="txt">
					<a href="tel:'.$generalPhone.'">'.$formattedPhone.'</a>
				</div>
			</div>
			<div class="it eml">
				<div class="img"></div>
				<h2 class="ttl">E-mail '.$lng['u']['and'].' '.$lng['w']['social'].'</h2>
				<div class="txt">
					<a href="mailto:info@sauto.md">info@sauto.md</a>
					<div class="sc">';
						foreach ($sc_ar as $k => $v){
							echo '<a class="'.$k.'" href="'.$v['url'].'" target="_blank" title="'.$v['name'].'" style="background-image:url(/media/images/site/v2/'.$v['img']['b'].');"></a>';
						}
					echo '
					</div>
				</div>
			</div>
		</div>
		
		<div class="gr mlmp">
			<div class="ml">
				<h3 class="ttl" style="display: none">'.$lng['w']['write2us'].'</h3>
				<form id="snd_msg" class="snd_msg" action="" style="display: none" method="post">
					<input class="inp use" type="text" name="name" placeholder="'.$lng['w']['ur_name'].'" title="'.$lng['w']['ur_name'].'" />
					<input class="inp use imp" type="text" name="email" placeholder="Email*" title="Email" />
					<input class="inp use imp" type="text" name="phone" placeholder="'.$lng['w']['phone_numb'].'*" title="'.$lng['w']['phone_numb'].'" />
					<textarea class="inp use txt" name="msg" spellcheck="false" placeholder="'.$lng['w']['message'].'" title="'.$lng['w']['message'].'"></textarea>
					<input class="use" type="hidden" name="page" value="'.$_SERVER['REQUEST_URI'].'" />
					<input class="use" type="hidden" name="target" value="self" />
					<div class="agmt">
						<input type="checkbox" name="agmt" id="f_agmt" class="cbx cnfrm" checked="checked" />
						<span class="txt"><label for="f_agmt">'.$lng['t']['x']['prs_dat_agr'][1].'</label> <a class="x" href="/'.$_COOKIE['lang'].'/privacy" target="_blank" title="'.$lng['t']['x']['prs_dat_agr']['ttl'].'">'.$lng['t']['x']['prs_dat_agr'][2].'</a></span>
					</div>
					<input class="btn sbmt" type="submit" value="'.$lng['w']['send'].'" onclick="event.preventDefault();" data-sent="'.$lng['w']['msg_snt'].'" data-sending="'.$lng['w']['sending'].'" data-req_fld="'.$lng['w']['req_not_filled'].'" />
				</form>
				'.$form.'
                
			</div>
			<div class="mp">
				<script type="text/javascript" charset="utf-8" async src="https://api-maps.yandex.ru/services/constructor/1.0/js/?um=constructor%3A9436e8c74c7c261c951aabf0a86a136d71754121a416010f3a0dcd5732497317&amp;width=100%25&amp;height=720&amp;lang='.$_COOKIE['lang'].'&amp;scroll=true"></script>
			</div>
		</div>
	</div>
	<div class="bx">
		<h2 class="ttl">'.$lng['w']['virtual'].'</h2>
		<div class="vrtl">
			<iframe src="https://www.google.com/maps/embed?pb=!4v1567763784246!6m8!1m7!1sCAoSLEFGMVFpcE14S05Qb2V1Sk5sNGQ3TUh4SWdNejZQTF8yUDE3Q3pXY0VHZVNM!2m2!1d47.0306146!2d28.8552507!3f242.6664445868917!4f13.389397018185335!5f0.7820865974627469" width="100%" height="450" frameborder="0" style="border:0;" allowfullscreen=""></iframe>
		</div>
	</div>
	<div class="bx">
		<h2 class="ttl">'.$lng['w']['o_prtnrs'].'</h2>
		<div class="prtr">
			<div class="hider">
				<div class="its" data-numb="0">';
				foreach($cnt_sponsor_ar as $k => $v){
					echo '
					<a class="it"'; if ($v['url']!=''){echo 'href="'.$v['url'].'"';} echo ' target="_blank" title="'.$v['name'].'" style="background-image:url(/media/images/site/partners/'.$v['img'].')"></a>';
				}
				echo '
				</div>
			</div>
			<div class="ghost"></div>
			
			<div class="arw l"></div>
			<div class="arw r"></div>
		</div>
	</div>
</div>
';

?>