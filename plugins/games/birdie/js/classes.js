class Player {
	constructor({size, sprite, src, health}){
		this.x = 20; this.y = 20; this.w = size.w; this.h = size.h;
		this.spr = {x:0, y:0, w:sprite.w, h:sprite.h, qu:15, now:0}
		this.img = new Image();
		this.img.src = src;
		this.velocity = {'x':0, 'y':0};
		this.stts = 'playing';
		this.health = health;
		this.god_mode = 0;
		this.hitbox = {x:this.x + this.w/2, y:this.y + this.h/2, w:this.w/2, h:this.h/2 }
	}
	update(){
		if (this.y + this.h > c.height){
			this.velocity.y = 0;
			this.y = c.height - this.h;
		} else {
			if (this.y < 0){ this.velocity.y = 0; }
			this.velocity.y += gravity * FPS_KICKER;
			this.y += this.velocity.y;
		}
		
		if ( this.x < c.width && this.x + this.w > c.width ){ this.velocity.x = 0; this.x = c.width - this.w;}
		else if ( this.x + this.w > 0 && this.x < 0 ){ this.velocity.x = 0; this.x = 0; }
		else { this.x += this.velocity.x;}
		
		if (frameNow % Math.round(2 / FPS_KICKER) === 0){
			if (this.stts == 'hitted'){ this.spr.y = this.spr.h; }
			
			if (this.spr.now >= this.spr.qu -1){this.spr.y = 0; this.stts = 'playing';}
			
			this.spr.now = this.spr.now < this.spr.qu - 1 ? ++this.spr.now : 0;
			this.spr.x = this.spr.w * this.spr.now;
		}
		this.hitbox = {x:this.x + this.w/2 - this.hitbox.w/2, y:this.y + this.h/2 - this.hitbox.h/2, w:this.w/2, h:this.h/2 }
	}
	draw(){
		//if (plr.god_mode > 0){ ctx.fillStyle = '#99ff33'; }else{ ctx.fillStyle = '#ff9933'; }
		//ctx.fillRect(this.x, this.y, this.w, this.h);
		
		ctx.drawImage(this.img, this.spr.x, this.spr.y, this.spr.w, this.spr.h, this.x, this.y, this.w, this.h);
		//ctx.fillRect(this.hitbox.x, this.hitbox.y, this.hitbox.w, this.hitbox.h);
	}
}

class Obj {
	constructor({ pos }){
		this.x = pos.x; this.y = pos.y; this.w = 30; //this.h = 100;
		
		this.hT = Math.random() * 200 + 50;
		this.hB = c.height - this.hT - plr.h*GATES_SIZE;
		this.yB = c.height - this.hB;
		
		this.velocity = {'x':-5, 'y':0};
	}
	update(){
		this.x += -WORLD_SPEED * FPS_KICKER;
		
		if (this.x + this.w < 0){
			this.hT = Math.random() * 200 + 50;
		
			this.hB = c.height - this.hT - plr.h*GATES_SIZE;
			this.yB = c.height - this.hB;
			
			this.x = 1000;
		}
		
		if ( (plr.hitbox.x + plr.hitbox.w > this.x && plr.hitbox.x < this.x + this.w && plr.hitbox.y + plr.hitbox.h > this.y && plr.hitbox.y < this.y + this.hT) || (plr.hitbox.x + plr.hitbox.w > this.x && plr.hitbox.x < this.x + this.w && plr.hitbox.y + plr.hitbox.h > this.yB && plr.hitbox.y < this.yB + this.hB) ){
			if (plr.god_mode<=0){
				if (--plr.health <= 0){
					plr.stts = 'died';
					bg_sound.pause();
					bg_sound.currentTime = 0;
					die_sound.play();
				}else{plr.god_mode = 50; plr.stts = 'hitted'; hit_sound.play(); if (plr.spr.now > 0){plr.spr.now = 0;}}
			}
		}
		health_hud.innerHTML = plr.health;
	}
	draw(){
		ctx.fillStyle = 'green';
		ctx.fillRect(this.x, this.y, this.w, this.hT);
		ctx.fillRect(this.x, this.yB, this.w, this.hB);
	}
}

class Background {
	constructor({pos, size, src, speed}){
		this.x = pos.x; this.y = pos.y; this.w = size.w; this.h = size.h;
		this.img = new Image();
		this.img.src = src;
		this.speed = speed;
	}
	update(){
		if (this.x <= -this.w){this.x = this.w + this.x;}
		this.x += -WORLD_SPEED * this.speed * FPS_KICKER;
	}
	draw(){
		//ctx.drawImage(this.img, 0, 0, this.w, this.h, this.x, 0, this.w, this.h);
		ctx.drawImage(this.img, 0, 0, this.w, this.h, this.x, 0, this.w, this.h);
		ctx.drawImage(this.img, 0, 0, this.w, this.h, this.x+this.w, 0, this.w, this.h);
		ctx.drawImage(this.img, 0, 0, this.w, this.h, this.x+this.w*2, 0, this.w, this.h);
	}
}