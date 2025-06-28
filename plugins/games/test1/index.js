/** @type {HTMLCanvasElement} */
//$(document).ready(function() {
	
const game = document.getElementById('game');
const ctx = game.getContext('2d');
GAME_WIDTH = game.width = 1000;
GAME_HEIGHT = game.height = 500;

let frameNow = 0;
const gravity = .5;

let objs = {};
let pos = {};

class Player {
	constructor(){
		this.x = 20;
		this.y = 20;
		this.w = 100;
		this.h = 100;
		this.velocity = {'x':0, 'y':0};
		this.actions = {'move_l':-5, 'move_r':5, 'jump':-12, };
		this.action = 'idle';
	}
	update(){
		
		if (this.y + this.h > game.height){
			this.velocity.y = 0;
			this.y = game.height - this.h;
		} else {
			if (this.y < 0){ this.velocity.y = 0; }
			this.velocity.y += gravity;
			this.y += this.velocity.y;
		}
		
		if ( this.x < game.width && this.x + this.w > game.width ){ this.velocity.x = 0; this.x = game.width - this.w;}
		else if ( this.x + this.w > 0 && this.x < 0 ){ this.velocity.x = 0; this.x = 0; }
		else { this.x += this.velocity.x;}
	}
	draw(){
		ctx.fillStyle = 'red';
		ctx.fillRect(this.x, this.y, this.w, this.h);
	}
}

class Obj {
	constructor({ pos }){
		this.x = pos.x;
		this.y = pos.y;
		this.w = 100;
		this.h = 100;
	}
	update(){
		if ( plr.x + plr.w > this.x && plr.x < this.x + this.w && plr.y + plr.h > this.y && plr.y < this.y + this.h){
			if ( (plr.y > this.y) && (plr.y < this.y + this.h) ){plr.y = this.y + this.h; plr.velocity.y = 0;}
			else if ( (plr.y < this.y) && (plr.y + plr.h > this.y) ){plr.y = this.y - plr.h; plr.velocity.y = plr.action=='jump' ? plr.actions.jump : 0;}
			else if ( (plr.x > this.x) && (plr.x < this.x + this.w) ){plr.x = this.x + this.w; plr.velocity.x = plr.action=='move_l' ? plr.actions.move_l : 0;}
			else if ( (plr.x < this.x) && (plr.x + plr.w > this.x) ){plr.x = this.x - plr.w; plr.velocity.x = plr.action=='move_r' ? plr.actions.move_r : 0;}
		}
	}
	draw(){
		ctx.fillStyle = 'black';
		ctx.fillRect(this.x, this.y, this.w, this.h);
	}
}

const plr = new Player();

const obj = new Obj({ pos:{x:500,y:400} });
const obj2 = new Obj({ pos:{x:560,y:300} });
const obj3 = new Obj({ pos:{x:620,y:400} });

function animate(){
	frameNow++;
	ctx.clearRect(0, 0, GAME_WIDTH, GAME_HEIGHT);
	obj.update(); obj.draw();
	obj2.update(); obj2.draw(); obj3.update(); obj3.draw();
	plr.update(); plr.draw();
	requestAnimationFrame(animate);
}
animate();

window.addEventListener('keydown', function(e){
	switch (e.key){
		case 'd':
			plr.velocity.x = 5;
			plr.action = 'move_r';
			break;
		case 'a':
			plr.velocity.x = -5;
			plr.action = 'move_l';
			break;
	}
	switch (e.key){
		case 'w':
			if (plr.velocity.y == 0){
				plr.velocity.y = -12;
				plr.action = 'jump';
			}
			break;
	}
})

window.addEventListener('keyup', function(e){
	switch (e.key){
		case 'd':
			plr.velocity.x = 0;
			break;
		case 'a':
			plr.velocity.x = 0;
			break;
	}
})

//}