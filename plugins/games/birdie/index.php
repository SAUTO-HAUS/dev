<link rel="stylesheet" type="text/css" href="/plugins/games/birdie/css/main.css?<?php echo time(); ?>">

<div id="game">
	<div id="health_hud" class="hud"></div>
	<div id="speed_hud" class="hud"></div>
	<div id="distance_hud" class="hud"></div>
	<div id="fps_hud" class="hud"></div>
	<div id="ovrl">
		<div id="score_hud" class="none">0</div>
		<div id="menu">
			<div class="bg"></div>
			<img class="logo" src="/plugins/games/birdie/media/imgs/logo.webp" />
			<div class="btn" id="start"><label>Play</label></div>
			<div class="btn" id="settings">
				<label>Settings</label>
				<div id="settings_box" class="bx none">
					<label>Speed	 <span class="val">5</span><input type="range" id="speed" name="speed" min="3" max="12" value="5" step="1" /></label>
					<label>Gate size <span class="val">3.0</span><input type="range" id="gate_size" name="gate_size" min="1.5" max="5" value="3" step=".5" /></label>
					<label>Health <span class="val">3</span><input type="range" id="health_qu" name="health_qu" min="1" max="10" value="3" step="1" /></label>
					<label>Tubes <span class="val">3</span><input type="range" id="tubes_qu" name="tubes_qu" min="1" max="20" value="3" step="1" /></label>
					<!--<label>Difficulty<span class="val">1</span><input type="range" id="difficulty" name="difficulty" min="1" max="10" value="1" step="1" /></label>-->
				</div>
			</div>
		</div>
	</div>
	<canvas id="canvas"></canvas>
</div>

<script src="/plugins/games/birdie/js/classes.js?<?php echo time(); ?>"></script>
<script src="/plugins/games/birdie/index.js?<?php echo time(); ?>"></script>


<!--2.28-->