<style>
	/*
	#game {
		position:absolute;
		top:50%;
		left:50%;
		transform:translate(-50%, -50%);
	}
	*/
</style>

Game "test1"
<canvas id="game"></canvas>

<script>
	/** @type {HTMLCanvasElement} */
	const game = document.getElementById('game');
	const ctx = game.getContext('2d');
	GAME_WIDTH = game.width = 1000;
	GAME_HEIGHT = game.height = 500;

	let frameNow = 0;

	class Test {
		constructor(){
			this.img = new Image();
			this.img.src = '';
			this.x = Math.random() * game.width;
			this.y = Math.random() * game.height;
			this.spriteWidth = 300;
			this.spriteheight = 150;
			this.width = this.spriteWidth / 2.5;
			this.height = this.spriteHeight / 2.5;
			this.frame = 0;
			this.animation = Math.floor(Math.random() * 3 + 1);
		}
		update(){
			this.x += Math.random() * 3;
			this.y += Math.random() * 3;
			if (frameNow % this.animation === 0){
				this.frame > 4 ? this.frame = 0 : this.frame++;
			}
		}
		draw(){
			//ctx.drawImage(this.image, this.frame * this.spriteWidth, 0, this.spriteWidth, this.spriteHeight, this.x, this.y, this.width, this.height);
			ctx.fillRect(0,0,100,100);
		}
	}

	//for (let i=0; i<numberOfEnemies; i++){
	//	enemiesArray.push(new Enemy());
	//}
	const enemy = new Test();

	function animate(){
		ctx.clearRect(0, 0, GAME_WIDTH, GAME_HEIGHT);
		enemy.update();
		enemy.draw();
		requestAnimationFrame(animate);
		frameNow++;
	}
	animate();
</script>
2.04