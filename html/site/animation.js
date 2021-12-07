let canvasElem = document.getElementById('canvas');

let ctx = canvasElem.getContext('2d');

function degToRad(degrees) {
  return degrees * Math.PI / 180;
};

function shuffle(array) {
  var currentIndex = array.length,  randomIndex;

  // While there remain elements to shuffle...
  while (currentIndex != 0) {

    // Pick a remaining element...
    randomIndex = Math.floor(Math.random() * currentIndex);
    currentIndex--;

    // And swap it with the current element.
    [array[currentIndex], array[randomIndex]] = [
      array[randomIndex], array[currentIndex]];
  }

  return array;
}

function drawCart(ctx, size, x, y) {
  let cesta= new Path2D();
  cesta.moveTo(x, y);
  cesta.lineTo(x + size * 20, y + size * 70);
  cesta.lineTo(x + size * 100, y + size * 70);
  cesta.lineTo(x + size * 120, y);
  cesta.closePath();
  //asa izq
  cesta.moveTo(x + size * 20, y);
  cesta.lineTo(x + size * 46,y - size * 34);
  //asa der
  cesta.moveTo(x + size * 100, y);
  cesta.lineTo(x + size * 74, y - size * 34);
  ctx.lineWidth = size * 6;
  ctx.lineJoin = "round";
  ctx.lineCap = "round";
  ctx.strokeStyle = "#efb8fb";
  ctx.stroke(cesta);
}

function drawCircle(ctx, radius, x, y, color) {
  ctx.fillStyle = color;
  ctx.beginPath();
  ctx.arc(x, y, radius, degToRad(0), degToRad(360), false);
  ctx.fill();
}

function drawSquare(ctx, length, x, y, color) {
  ctx.fillStyle = color;
  ctx.fillRect(x, y, length, length);
}

function drawTriangle(ctx, length, x, y, color) {
  ctx.fillStyle = color;

  ctx.beginPath();
  ctx.moveTo(x, y);
  ctx.lineTo(x + length, y);
  let triHeight = (length/2) * Math.tan(degToRad(60));
  ctx.lineTo(x + (length/2), y + triHeight);
  ctx.lineTo(x, y);
  ctx.fill();
}

function draw(){
  drawCart(ctx, 2.5, 30, 110);
  
  //ctx.rotate(angle * Math.PI / 180);
  function drawCartSquare(){
    let xpos=Math.floor(Math.random() * 180);
    drawSquare(ctx, 60, 60 + xpos, 126, 'blue');
  }

  function drawCartCircle(){
    let xpos=Math.floor(Math.random() * 130);
    drawCircle(ctx, 50, 115 + xpos, 200, 'green');
  }
  
  function drawCartTriangle(){
    let xpos=Math.floor(Math.random() * 140);
    drawTriangle(ctx, 80, 70 + xpos, 205, 'orange');
  }
  
  let drawFunctions=[drawCartSquare, drawCartCircle, drawCartTriangle];

  let total=2;
  let all=Math.floor(Math.random() * 2);
  if (!all) {
    total=Math.floor(Math.random() * 3);
    drawFunctions=shuffle(drawFunctions);
  }
  for(let i=0; i<drawFunctions.length; i++) {
    if (i <= total) drawFunctions[i]();
  }
}

let count=0;

function animation() {
  // Clear canvas
  ctx.clearRect(0, 0, canvasElem.width, canvasElem.height);
  draw();
  count ++;
  if (count>3) {
    count=0;
    interval=5100;
  }
  else {
    interval=2100;
  }
  setTimeout(function() {
      window.requestAnimationFrame(animation);
      // código que genera una fotograma
  }, interval);
}
window.requestAnimationFrame(animation);