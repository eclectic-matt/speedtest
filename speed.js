function init(){
	//console.log('Starting...');
	var settings = {};

	//DOWNLOAD GRAPH
	settings.bgColour = 'white';
	settings.lineColour = 'red';
	settings.axisColour = 'grey';
	settings.textColour = 'grey';
	settings.averageColour = 'blue';
	generateGraph(downloadSpeeds, 'downloadGraph', settings);

	//UPLOAD GRAPH
	settings.lineColour = 'blue';
	settings.averageColour = 'red';
	generateGraph(uploadSpeeds, 'uploadGraph', settings);
}


function generateGraph(data, canvasName, settings){

	//===============
	// DATA SETUP
	//===============
	var maxValue = Math.max.apply(null, data);

	//CALCULATE AVERAGE VALUE
	var sumValues = data.reduce((partialSum, a) => partialSum + a, 0);
	//DIVIDE BY COUNT TO GET AVERAGE
	var averageValue = (sumValues / data.length);
	//SCALE DOWN BY MAX
	averageValue = averageValue / maxValue;
	//console.log(canvasName, averageValue);
	//SET MIN AT 0 FOR NOW
	minValue = 0;

	//SCALE THE DOWNLOAD VALUES DOWN
	var scaledData = data.map(x => x / maxValue);

	//DAY AND HOUR
	var previousDay = '';
	var previousHour = '18';	//SKIP FIRST HOUR (TOO NARROW!)

	//===============
	// CANVAS SETUP
	//===============
	//SET UP CANVAS
	var cnv = document.getElementById(canvasName);
	ctx = cnv.getContext("2d");
	
	//SET CANVAS VARIABLES
	var canvasHeight = cnv.height;
	var canvasWidth = cnv.width;

	//CLEAR CANVAS
	ctx.fillStyle = settings.bgColour;
	ctx.fillRect(0, 0, canvasWidth, canvasHeight);

	var paddingX = 25;	//LEFT - RIGHT PADDING
	var paddingY = 25; //TOP - BOTTOM PADDING
	//canvasHeight = canvasHeight - (2 * paddingY);

	//SET STARTING POINTS
	var currentX = 0;
	//var currentX = 0 + paddingX;
	var currentY = canvasHeight - (scaledData[0] * canvasHeight);
	//var currentY = canvasHeight - (scaledData[0] * canvasHeight);

	//SET X-SPACING
	var xSpace = canvasWidth / (scaledData.length + 1);

	//===============
	// TREND LINE
	//===============
	//START TREND LINE
	ctx.fillStyle = settings.lineColour;
	ctx.beginPath();
	ctx.lineWidth  = 3;
	ctx.strokeStyle = settings.lineColour;
	ctx.setLineDash([]);

	//FIRST PASS - DRAW TREND LINE + AVERAGE
	for(point in scaledData){

		//START AT THE CURRENT POSITION
		ctx.moveTo(currentX, currentY);

		nextX = currentX + xSpace;
		nextY = canvasHeight - (scaledData[point] * canvasHeight);
		ctx.lineTo(nextX, nextY);

		currentX = nextX;
		currentY = nextY;
	}
	ctx.stroke();
	ctx.closePath();

	//===============
	// AVERAGE LINE
	//===============
	//DRAW AVERAGE LINE
	ctx.fillStyle = settings.averageColour;
	ctx.strokeStyle = settings.averageColour;
	//ctx.setLineDash([2, 2]);
	ctx.beginPath();
	ctx.lineWidth = 5;
	ctx.moveTo(0, canvasHeight + (averageValue * canvasHeight));
	ctx.lineTo(canvasWidth, canvasHeight + (averageValue * canvasHeight));
	ctx.stroke();
	
	//AVERAGE TEXT
	ctx.font = '20px arial';
	let printValue = (averageValue * maxValue).toFixed(2);
	ctx.fillText(printValue, canvasWidth - 50, canvasHeight - (averageValue * canvasHeight) - 5);
	ctx.fill();
	ctx.closePath();

	//===============
	// FILL SMALL CANVAS
	//===============
	//FILL SMALL CANVAS NOW (DO NOT COPY AXIS INFO)
	var smallCnv = document.getElementById(canvasName + 'Small');
	smallCnv.height = smallCnv.width * (cnv.height / cnv.width);

	var smallCanvasCtx = smallCnv.getContext('2d');
	smallCanvasCtx.imageSmoothingEnabled = true;
	smallCanvasCtx.imageSmoothingQuality = "high";
	smallCanvasCtx.drawImage(cnv, 0, 0, smallCnv.width, smallCnv.height);

	//===============
	// AXIS
	//===============
	//DRAW LEFT AXIS
	var segments = 8;
	var segmentYDiff = (canvasHeight) / segments;
	ctx.fillStyle = settings.averageColour;
	ctx.strokeStyle = settings.averageColour;
	ctx.setLineDash([1,5]);
	ctx.lineWidth  = 3;
	ctx.font = '20px arial';

	for (let i = 0; i <= segments; i++){
		//DRAW AXIS LINE
		ctx.beginPath();
		ctx.moveTo(0, (i * segmentYDiff));
		ctx.lineTo(canvasWidth, (i * segmentYDiff));
		ctx.stroke();
		//DRAW AXIS VALUE
		let value = (maxValue - (maxValue * (i / segments))).toFixed(2);
		ctx.fillText(value, 1, i * segmentYDiff + 20);
		ctx.fill();
		ctx.closePath();
	}

	//===============
	// DATE AND HOUR
	//===============

	//RESET CURRENT
	currentX = 0;
	currentY = canvasHeight - (scaledData[0] * canvasHeight);
	ctx.beginPath();
	ctx.lineWidth  = 1;
	ctx.font = '14px arial';

	//SECOND PASS FOR DATE AND TIME
	for(point in scaledData){
		//PRINT DATE AND HOUR MARKERS
		ctx.fillStyle = settings.textColour;
		ctx.setLineDash([2, 2]);

		//GET CURRENT DAY AND HOUR
		//currentDay = testTimings[point].substring(0,8);
		currentDay = testTimings[point].substring(0,8);
		currentHour = testTimings[point].substring(9,11);

		//ARE WE ON A NEW DAY?
		if(currentDay !== previousDay){
			
			ctx.fillText(currentDay, currentX + 5, canvasHeight - 30);
			ctx.moveTo(currentX, 0);
			ctx.lineTo(currentX, canvasHeight);
			previousDay = currentDay;
		}

		/*if(currentHour !== previousHour){
			ctx.font = '10px arial';
			ctx.fillText(currentHour + '00', currentX, canvasHeight - 10);
			ctx.moveTo(currentX, 0);
			ctx.lineTo(currentX, canvasHeight);
			previousHour = currentHour;
		}*/

		//RESET STYLES
		ctx.fillStyle = settings.lineColour;
		ctx.setLineDash([]);
		currentX = currentX + xSpace;
		currentY = canvasHeight - (scaledData[point] * canvasHeight);
	}

	ctx.stroke();
	ctx.closePath();
}

function downloadCanvas(id){
	let canvas = document.getElementById(id);
	let img = canvas.toDataURL("image/png");
	//NOT ALLOWED ("TOP-FRAME NAVIGATION NOT PERMITTED")
	//window.location = img;
	//CREATE NEW WINDOW
	let newWindow = window.open();
	//WRITE THE IMAGE TO AN IFRAME ON THE NEW WINDOW
	newWindow.document.write('<iframe src="' + img + '" frameborder="0" style="border:0; top:0px; left:0px; bottom:0px; right:0px; width:100%; height:100%;" allowfullscreen></iframe>');
}
