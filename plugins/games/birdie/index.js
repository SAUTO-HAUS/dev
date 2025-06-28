/** @type {HTMLCanvasElement} */
//document.querySelectorAll('[data-foo="value"]');
//document.getElementsByTagName('body')[0];
//const body_div = document.body;

const bg_sound = new Audio('/plugins/games/birdie/media/audio/music.mp3'); bg_sound.volume = 0.2; bg_sound.loop=true;
const hit_sound = new Audio('/plugins/games/birdie/media/audio/hit.mp3'); hit_sound.volume = 0.4; hit_sound.loop=false;
const die_sound = new Audio('/plugins/games/birdie/media/audio/die.mp3'); die_sound.volume = 0.5; hit_sound.loop=false;

$(document).ready(function(){
	$('#menu > #start').on('click', function(e){
		$('#score_hud').removeClass('none');
		WORLD_SPEED = document.getElementById('speed').value * 1;
		GATES_SIZE = document.getElementById('gate_size').value * 1;
		HEALTH_QU = document.getElementById('health_qu').value *1;
		TUBES_QU = document.getElementById('tubes_qu').value *1;
		Z_TIME = Math.floor( Date.now() );
		
		for (let i=1; i<=bg_layers_qu; i++){
			bg_layers.push( new Background({pos:{x:0,y:0}, size:{w:889, h:c.height}, src:'/plugins/games/birdie/media/imgs/bg/l'+i+'.png', speed:bg_layers_speed[i] }) );
			//bg_layers.push( new Background({pos:{x:0,y:0}, size:{w:889, h:c.height}, src:'/plugins/games/birdie/media/imgs/bg/horror/l'+i+'.png', speed:-WORLD_SPEED * bg_layers_speed[i] }) );
		}
		
		plr = new Player({size:{w:70, h:55}, sprite:{w:132.0666666666667, h:103}, src:'/plugins/games/birdie/media/imgs/player/purple_bug.png', health:HEALTH_QU});//20
		
		for (let i=1; i<=TUBES_QU; i++){
			obj_layers.push( new Obj({ pos:{x:1000 + (1000 / TUBES_QU * i),y:0} }) );
		}
		
		//let objects = ['1', '2', '3'];
		//obj = new Obj({ pos:{x:1000,y:0} });
		//obj2 = new Obj({ pos:{x:1250,y:0} });
		//obj3 = new Obj({ pos:{x:1500,y:0} });
		//obj4 = new Obj({ pos:{x:1750,y:0} });
		
		$('#menu').fadeOut(500);
		
		bg_sound.play();
		animate();
	})
	
	$('#menu > #settings').on('click', function(e){ $(this).find('.bx').removeClass('none'); })
	
	$('#menu > #settings > .bx > label > input').on('input', function(e){
		const val = $(this).val()*1 % 1 == 0 && $(this).attr('name')=='gate_size' ? $(this).val()+'.0' : $(this).val();
		$(this).parent().find('.val').html( val );
	})
})

document.addEventListener('mousemove', e => {
	Object.assign(document.documentElement, {
		style: `
			--move-y: ${ (e.clientX - window.innerWidth / 2) * .025}deg;
			--move-x: ${ (e.clientY - window.innerHeight / 2) * -.05}deg;
		`
	})
})

const health_hud = document.getElementById('health_hud');
const speed_hud = document.getElementById('speed_hud');
const fps_hud = document.getElementById('fps_hud');
const distance_hud = document.getElementById('distance_hud');
const ovrl_div = document.getElementById('ovrl');
const score_hud = document.getElementById('score_hud');
const start_btn = document.getElementById('start');

const c = document.getElementById('canvas');
const ctx = c.getContext('2d');
CANVAS_WIDTH = c.width = 1000;
CANVAS_HEIGHT = c.height = 500;
//CANVAS_WIDTH = c.width = window.innerWidth;
//CANVAS_HEIGHT = c.height = window.innerHeight;

FRAMES = 1;
FPS_KICKER = 0;
DISTANCE = 0;
PLAYER_SPEED = 0;
TIMER = -1;

let frameNow = 0;
let gravity = .5;

let objs = {};
let pos = {};

let bg_layers = []; const bg_layers_qu = 5; //const bg_layers_speed = {1:0, 2:1, 3:4, 4:4.75, 5:4.975};
const bg_layers_speed = {1:1, 2:.9, 3:.8, 4:.4, 5:.1};
//let bg_layers = []; const bg_layers_qu = 7;
//const bg_layers_speed = {1:1, 2:.9, 3:.8, 4:.7, 5:.6, 6:.01, 7:.01};

let obj_layers = [];

function animate(){
	DISTANCE += ( WORLD_SPEED * FPS_KICKER ) / 75; //(75px==1m) 300px / 75 == 4 m/s (14.4 km/h)
	distance_hud.innerHTML = Math.floor(DISTANCE)+' m';
	if ( Math.floor( Date.now() ) - Z_TIME >= 1000 ){
		++TIMER;
		FPS_KICKER = 60/FRAMES;
		fps_hud.innerHTML = 'FPS:'+FRAMES;
		PLAYER_SPEED = WORLD_SPEED*60*FPS_KICKER/75 * 3.6;
		FRAMES = 1;
		Z_TIME = Math.floor( Date.now() );
		if (TIMER > 0 && TIMER % 10 === 0){WORLD_SPEED += .1;}
		//speed_hud.innerHTML = Math.round( (WORLD_SPEED * FPS_KICKER) * (-1) )+' km/h';
		speed_hud.innerHTML = Math.round(PLAYER_SPEED)+' km/h';
	}
	
	if ( plr.stts == 'died' ){ ovrl_div.classList.add('stop'); return false; }
	score_hud.innerHTML = Math.floor( DISTANCE + ( DISTANCE / (GATES_SIZE - 1) ) + WORLD_SPEED );
	
	if (plr.god_mode > 0){--plr.god_mode;}
	
	ctx.clearRect(0, 0, CANVAS_WIDTH, CANVAS_HEIGHT);
	
	bg_layers.slice().reverse().forEach(v => { v.update(); v.draw(); });
	//Object.entries(bg_layers).forEach(entry => { const [k, v] = entry; v.update(); v.draw(); })
	
	obj_layers.slice().reverse().forEach(v => { v.update(); v.draw(); });
	//obj.update(); obj.draw(); obj2.update(); obj2.draw(); obj3.update(); obj3.draw(); obj4.update(); obj4.draw();
	plr.update(); plr.draw();
	
	++frameNow; ++FRAMES;
	requestAnimationFrame(animate);
}
//animate();

window.addEventListener('keydown', function(e){
	switch (e.key){
		case 'w':
			plr.velocity.y = -8;
			plr.stts = 'jump';
			break;
		case ' ':
			plr.velocity.y = -8;
			plr.action = 'jump';
			if(e.keyCode == 32 && e.target == document.body) {
				e.preventDefault();
			}
			break;
	}
})

//window.addEventListener('click', function(e){
//	plr.velocity.y = -8;
//	plr.stts = 'jump';
//})

window.addEventListener('touchend', function(e){
	plr.velocity.y = -8;
	plr.stts = 'jump';
})

//start_btn.addEventListener('click', function(e){
	//animate();
//})

/*function getConvertedEventType(type) {
  if (isMobile()) {
    if (type === 'mousedown') {
      type = 'touchstart';
    } else if (type === 'click') {
      type = 'touchend';
    }
  }

  return type;
}*/