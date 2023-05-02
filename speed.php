<?php

//REDIRECT TO NEW PAGE
//header('Location: /speedtest/results.php');

//DEFINE FOLDER
$folder = 'S:\Development\Bash-Shell\speedtest\results';

//GET THE FILES IN THIS FOLDER
$files = scandir($folder, SCANDIR_SORT_ASCENDING);

//DEFINE FOLDER
$summaryFolder = 'S:\Development\Bash-Shell\speedtest\summary';

/*
//MOVED TO /speedtest/outputPDF.php
//GET THE FILES IN THIS FOLDER
//$summaryImages = glob($summaryFolder, SCANDIR_SORT_ASCENDING, IMG_PNG);
$summaryImages = preg_grep('~\.(png)$~', scandir($summaryFolder, SCANDIR_SORT_ASCENDING));
$summaryGraph = end($summaryImages);
//$summaryPDFs = scandir($summaryFolder, SCANDIR_SORT_ASCENDING, SCANDI)
$summaryPDFs = preg_grep('~\.(pdf)$~', scandir($summaryFolder, SCANDIR_SORT_ASCENDING));
$summaryPDF = end($summaryPDFs);
$summaryLink = $summaryFolder . "/" . $summaryPDF;
chmod($summaryLink, 777);
$pdfSymlink = symlink($summaryLink, 'summaryPDF');
//DEBUG
//var_dump($files);
*/


/*
//GET THE CURRENT CSV FILE
$csvFolder = 'S:\Development\xampp\htdocs\speedtest\csv\\';
$csvFilePath = glob("$csvFolder*.csv")[0];
$csvFile = str_ireplace($csvFolder, '', $csvFilePath);
*/
//GET THE NEWLY-GENERATED SUMMARY CSV FILE
$csvFile = "/speedtest/outputCSV.php";

$speeds = new stdClass();
$speeds->downloads = array();
$speeds->uploads = array();
$speeds->times = array();
$speeds->count = 0;
$speeds->errors = array();
$speeds->lastDay = array();

//CHECKING GAPS IN RESULTS
$prevTime = strtotime(convertFileNameToUnix('12_04_2023@18_48_00.00.json'));
$maxDiff = 0;
$maxDiffEnd = '';

foreach($files as $file){

	if(str_ireplace('.','',$file) === '') continue;

	$fullName = $folder . '/' . $file;
	if(filesize($fullName) === false) continue;
	if(filesize($fullName) === 0) continue;

	$content = file_get_contents($fullName);
	$json = json_decode($content, true);

	//if(isset($json['error'])) continue;
	if(isset($json['error'])){
		$speeds->errors[] = $file;
		continue;
	}

	$speeds->downloads[] = $json['download']['bandwidth'];
	$speeds->uploads[] = $json['upload']['bandwidth'];
	$speeds->times[] = $file;
	$speeds->count++;

	$time = strtotime(convertFileNameToUnix($file));
	if($time > strtotime('-24 hours')){
		
	}
	$diff = $prevTime - $time;
	if($diff < $maxDiff){
		$maxDiff = $diff;
		$maxDiffEnd = $file;
	}
	$prevTime = $time;
}

$longestGapEndTime = convertFileNameToUnix($maxDiffEnd);
$longestGapStartTime = date('Y-m-d H:i:s', strtotime($maxDiff . ' seconds', strtotime(convertFileNameToUnix($maxDiffEnd))));

//DEBUG
//var_dump($speeds);

//GENERATE SUMMARY VALUES
//In bits - so to convert to MBps we need to divide by 1 million (1E6) and then times by 8 (bits -> bytes).
$mbpsConvert = 1E6 / 8;
$mbps = 'MBps';

//DOWNLOADS
//highest
$highestDownload = number_format(max($speeds->downloads) / $mbpsConvert, 2);
$highestDownIndex = array_search(max($speeds->downloads), $speeds->downloads);
$highestDownTime = getHumanTimeFromFileName($speeds->times[$highestDownIndex]);
//lowest
$lowestDownload = number_format(min($speeds->downloads) / $mbpsConvert, 2);
$lowestDownIndex = array_search(min($speeds->downloads), $speeds->downloads);
$lowestDownTime = getHumanTimeFromFileName($speeds->times[$lowestDownIndex]);
//average
$averageDownload = number_format((array_sum($speeds->downloads) / $speeds->count) / $mbpsConvert, 2);

//UPLOADS
//highest
$highestUpload = number_format(max($speeds->uploads) / $mbpsConvert, 2);
$highestUpIndex = array_search(max($speeds->uploads), $speeds->uploads);
$highestUpTime = getHumanTimeFromFileName($speeds->times[$highestUpIndex]);
//lowest
$lowestUpload = number_format(min($speeds->uploads) / $mbpsConvert, 2);
$lowestUpIndex = array_search(min($speeds->uploads), $speeds->uploads);
$lowestUpTime = getHumanTimeFromFileName($speeds->times[$lowestUpIndex]);
//average
$averageUpload = number_format((array_sum($speeds->uploads) / $speeds->count) / $mbpsConvert, 2);


//-=-=-=-=-=-=-=-=-=
// LAST 24 HOURS
//-=-=-=-=-=-=-=-=-=
$last24Limit = date('Ymd_His', strtotime('-24 hour'));
//echo '<h2>Last 24 Limit: ' . $last24Limit . '</h2>';
$lastDayTimings = array_filter($speeds->times, function($time) use ($last24Limit){
	$thisTime = convertFileNameToTimestamp($time);
	//echo $thisTime . ' => ' .  $last24Limit . ' === ' . (($thisTime > $last24Limit) ? 'true' : 'false') . '<br>';
	return $thisTime > $last24Limit;
});

//var_dump($lastDayTimings);

$lastDayIndex = count($lastDayTimings);


//SLICE FROM END OF ARRAY!?!?!?!?!?

//DOWNLOADS
$lastDayDownloads = array_slice($speeds->downloads, -$lastDayIndex, $lastDayIndex, true);
//var_dump($lastDayDownloads);
$highestDownload24 = number_format(max($lastDayDownloads) / $mbpsConvert, 2);
$lowestDownload24 = number_format(min($lastDayDownloads) / $mbpsConvert, 2);
$averageDownload24 = number_format((array_sum($lastDayDownloads) / $lastDayIndex) / $mbpsConvert, 2);

//UPLOADS
$lastDayUploads = array_slice($speeds->uploads, -$lastDayIndex, $lastDayIndex, true);
$highestUpload24 = number_format(max($lastDayUploads) / $mbpsConvert, 2);
$lowestUpload24 = number_format(min($lastDayUploads) / $mbpsConvert, 2);
$averageUpload24 = number_format((array_sum($lastDayUploads) / $lastDayIndex) / $mbpsConvert, 2);




//=-=-=-=-=-=-=-=-=-=-=-=
//
//-=-=-=-=-=-=-=-=-=-=-=-=


//COUNT
$testCount = $speeds->count;

//GET THE LAST FILE NAME
$dtString = end($files);
//STORE HUMAN-READABLE TIME STRING
$lastTestRun = getHumanTimeFromFileName($dtString);

function getHumanTimeFromFileName($name){

	//STRIP .MS and .json
	$dtString = substr($name,0,-7);
	//GET FIRST 10 CHARS (DD_MM_YYYY)
	$dateString = substr($dtString,0,10);
	//REPLACE _ WITH -
	$dateString = str_ireplace('_','-',$dateString);

	//GET THE TIME STRING (11TH - 19TH CHARS)
	$timeString = substr($dtString,11,8);
	//REPLACE _ WITH :
	$timeString = str_ireplace('_',':',$timeString);

	//RETURN
	return $dateString . ' at ' . $timeString;
}

/**
 * Convert a file name (e.g. 12_04_2023@18_49_23.22.json) into a timestamp
 * @param string The file name to convert.
 * @return string The timestamp (yyyymmdd_hhiiss).
 */
function convertFileNameToTimestamp($name){
	//STRIP .MS and .json
	$dtString = substr($name,0,-7);

	//GET THE YEAR
	//$year = substr($dtString,6,4);
	$year = substr($dtString,0,4);
	//GET THE MONTH
	//$month = substr($dtString,3,2);
	$month = substr($dtString,5,2);
	//GET THE DAY
	//$day = substr($dtString,0,2);
	$day = substr($dtString,8,2);

	//GET THE HOURS
	$hours = substr($dtString,11,2);
	//GET THE MINUTES
	$mins = substr($dtString,14,2);
	//GET THE SECONDS
	$seconds = substr($dtString,17,2);

	//CONCAT AND RETURN
	return $year . $month . $day . '_' . $hours . $mins . $seconds;
}

function convertFileNameToUnix($name){
	//STRIP .MS and .json
	$dtString = substr($name,0,-7);

	//GET THE YEAR
	//$year = substr($dtString,6,4);
	$year = substr($dtString,0,4);
	//GET THE MONTH
	//$month = substr($dtString,3,2);
	$month = substr($dtString,5,2);
	//GET THE DAY
	//$day = substr($dtString,0,2);
	$day = substr($dtString,8,2);

	//GET THE HOURS
	$hours = substr($dtString,11,2);
	//GET THE MINUTES
	$mins = substr($dtString,14,2);
	//GET THE SECONDS
	$seconds = substr($dtString,17,2);

	//CONCAT AND RETURN
	//return $year . $month . $day . '_' . $hours . $mins . $seconds;
	return $year . '-' . $month . '-' . $day . ' ' . $hours . ':' . $mins . ':' . $seconds;
}


function convertSecondsToHumanReadableString($seconds){
	$units = array();
	$units['minute'] = 60;
	$units['hour'] = 3600;
	$units['day'] = 86400;
	$result = '';
	foreach(array_reverse($units) as $unit){
		if($unit > $seconds){
			continue;
		}else{
			$numUnits = floor($seconds / $unit);
			if($result){
				$result .= ', ';
			}
			$result .= $numUnits . ' ' . array_keys($units, $unit)[0];
			if($numUnits > 1){
				$result .= 's';
			}
			$seconds -= $numUnits * $unit;
		}
	}

	if($seconds > 0){
		if($result){
			$result .= ' and ';
		}
		$result .= $seconds . ' second';
		if($seconds > 1){
			$result .= 's';
		}
	}
	return $result;
}


function convertBandwidthToMBps($bandwidth){
	return number_format( ($bandwidth / 1E6) * 8, 2);
}
?>
<!DOCTYPE html>
<head>
	<link rel='stylesheet' href='/css/w3.css'>
	<link rel='stylesheet' href='/css/w3-theme-deep-purple.css'>
	<link rel='stylesheet' href='https://fonts.googleapis.com/css?family=Open+Sans'>
	<!-- Font Awesome Icon Library -->
	<!--link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css"-->
	<link href="/libraries/font-awesome/css/fontawesome.css" rel="stylesheet">
	<link href="/libraries/font-awesome/css/brands.css" rel="stylesheet">
	<link href="/libraries/font-awesome/css/solid.css" rel="stylesheet">
	<meta http-equiv="refresh" content="60" />

	<script defer src='/css/fontawesome-all.min.js'></script>
	<style>
		html,body,h1,h2,h3,h4,h5 {font-family: 'Open Sans', sans-serif}
		table { border-collapse: collapse; border: 1px solid white;}
		canvas { border: 1px solid white;}
		
	</style>
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<script>

		//GENERATE AN ARRAY OF TEST TIMINGS
		var testTimings = [
			<?php
			foreach($speeds->times as $index => $time){
				if ($index === count($speeds->times)){
					echo '"' . convertFileNameToTimestamp($time) . '"';
				}else{
					echo '"' . convertFileNameToTimestamp($time) . '",';
				}
			}
			?>
		];
		
		//GENERATE AN ARRAY OF DOWNLOAD SPEEDS
		var downloadSpeeds = [
			<?php
			foreach($speeds->downloads as $index => $dl){
				if ($index === count($speeds->downloads)){
					echo convertBandwidthToMBps($dl);
				}else{
					echo convertBandwidthToMBps($dl) . ',';
				}
			}
			?>
		];

		//GENERATE AN ARRAY OF UPLOAD SPEEDS
		var uploadSpeeds = [
			<?php
			foreach($speeds->uploads as $index => $ul){
				if ($index === count($speeds->uploads)){
					echo convertBandwidthToMBps($ul);
				}else{
					echo convertBandwidthToMBps($ul) . ',';
				}
			}
			?>
		];
	</script>
	<script src="speed.js"></script>
</head>
<body class="w3-container w3-dark-gray" onload="init()">
	
	<h1 class="w3-medium w3-center">Speed Test Results</h1>
	
	<div id="summary" class="w3-container w3-green w3-center">
		<h2 title="Test Count"><i class="fa-solid fa-hashtag"></i><?php echo $testCount; ?> Tests</h2>
		<p class="w3-small">Last Test Time: <?php echo $lastTestRun; ?></p>
		<!--p class="w3-small">Latest CSV file Time: <?php //echo $csvFile; ?></p-->

		<?php //echo '<b>Note: largest gap in results is ' . convertSecondsToHumanReadableString(abs($maxDiff)) . ' from ' . $longestGapStartTime . ' to ' . $longestGapEndTime . '</b>'; ?>
		<!--br>
		<?php //echo '<b>Error count: ' . count($speeds->errors) . ' with last recorded at ' . end($speeds->errors) . '</b>'; ?>
		<br-->
		<div class="w3-row-padding">
			<div class="w3-col s6">
				<button class="w3-orange w3-button w3-round w3-padding"><a href="<?php
						//echo '\speedtest\csv\\' . $csvFile;
						echo $csvFile;
						?>">Summary CSV <i class="fa-solid fa-table"></i></a></button>
			</div>
			<div class="w3-col s6">
				<button class="w3-yellow w3-button  w3-round w3-padding"><a href="/speedtest/outputPDF.php">Summary PDF <i class="fa-solid fa-file-alt"></i></a></button>
			</div>
			<!--div class="w3-col s4">
				<! This regenerates a full stats CSV - basic summaries now auto-generated > 
				<button class="w3-red w3-button w3-round w3-padding"><a href="/speedtest/results.php">Regen <i class="fa-solid fa-table"></i></a></button>
			</div-->
		</div>
		<br>
	</div>

		<!-- LARGE SCREENS ONLY -->
		<div id="downloads" class="w3-hide-small w3-hide-medium w3-red w3-padding w3-center">
			<h2>Downloads</h2>
			<div class="w3-row-padding">
				<div class="w3-col s12 l4">
					<h3>All Time</h3>
					<h4 title="Average Download">Average: <i class="fa-solid fa-cloud-download"></i> <?php echo $averageDownload; ?>MBps</h4>
					<p><b>Max: <?php echo $highestDownload; ?> MBps</b> <em class="w3-small"> (<?php echo $highestDownTime; ?></em>)</p>
					<p><b>Min: <?php echo $lowestDownload; ?> MBps</b><em class="w3-small"> (<?php echo $lowestDownTime; ?></em>)</p>
				</div>
				<div class="w3-col s12 l4">
					<h3>Last 24 Hours</h3>
					<h4 title="Average Download">Average: <i class="fa-solid fa-cloud-download"></i> <?php echo $averageDownload24; ?>MBps</h4>
					<p><b>Max: <?php echo $highestDownload24; ?> MBps</b> <em class="w3-small"></p>
					<p><b>Min: <?php echo $lowestDownload24; ?> MBps</b><em class="w3-small"></p>
				</div>
				<div class="w3-col s12 l4">
					<button class="w3-green w3-display-bottom w3-button w3-large w3-round w3-padding"><a onclick="downloadCanvas('downloadGraph');">View "Downloads" Graph</a></button>
				</div>
			</div>
			<br>
			<canvas id="downloadGraph" width="1600" height="900">
				Must have Javascript enabled to use this feature.
			</canvas>
		</div>

		<div id="downloadsSmall" class="w3-red w3-padding w3-center w3-hide-large">
			<h2>Downloads</h2>
			<div class="w3-row-padding">
				<div class="w3-col s12 l4">
					<h3>All Time</h3>
					<h4 title="Average Download">Average: <i class="fa-solid fa-cloud-download"></i> <?php echo $averageDownload; ?>MBps</h4>
					<p><b>Max: <?php echo $highestDownload; ?> MBps</b> <em class="w3-small"> (<?php echo $highestDownTime; ?></em>)</p>
					<p><b>Min: <?php echo $lowestDownload; ?> MBps</b><em class="w3-small"> (<?php echo $lowestDownTime; ?></em>)</p>
				</div>
				<div class="w3-col s12 l4">
					<h3>Last 24 Hours</h3>
					<h4 title="Average Download">Average: <i class="fa-solid fa-cloud-download"></i> <?php echo $averageDownload24; ?>MBps</h4>
					<p><b>Max: <?php echo $highestDownload24; ?> MBps</b> <em class="w3-small"></p>
					<p><b>Min: <?php echo $lowestDownload24; ?> MBps</b><em class="w3-small"></p>
				</div>
				<div class="w3-col s12 l4">
					<button class="w3-green w3-display-bottom w3-button w3-large w3-round w3-padding"><a onclick="downloadCanvas('downloadGraph');">View the "Downloads" Graph</a></button>
				</div>
			</div>
			<br>
			<canvas id="downloadGraphSmall" width="300" height="150">
				Must have Javascript enabled to use this feature.
			</canvas>
		</div>

		<!-- LARGE SCREENS ONLY -->
		<div id="uploads" class="w3-blue w3-padding w3-center w3-hide-small w3-hide-medium">
			<h2>Uploads</h2>
			<div class="w3-row-padding">
				<div class="w3-col s12 l4">
					<h3>All Time</h3>
					<h4 title="Average Upload">Average: <i class="fa-solid fa-cloud-upload"></i> <?php echo $averageUpload; ?>MBps</h4>
					<p><b>Max: <?php echo $highestUpload; ?> MBps</b> <em class="w3-small"> (<?php echo $highestUpTime; ?></em>)</p>
					<p><b>Min: <?php echo $lowestUpload; ?> MBps</b><em class="w3-small"> (<?php echo $lowestUpTime; ?></em>)</p>
				</div>
				<div class="w3-col s12 l4">
					<h3>Last 24 Hours</h3>
					<h4 title="Average Upload">Average: <i class="fa-solid fa-cloud-upload"></i> <?php echo $averageUpload24; ?>MBps</h4>
					<p><b>Max: <?php echo $highestUpload24; ?> MBps</b> <em class="w3-small"></p>
					<p><b>Min: <?php echo $lowestUpload24; ?> MBps</b><em class="w3-small"></p>
				</div>
				<div class="w3-col s12 l4">
					<button class="w3-green w3-display-bottom w3-button w3-large w3-round w3-padding"><a onclick="downloadCanvas('uploadGraph');">View the "Uploads" Graph</a></button>
				</div>
			</div>
			<br>
			<canvas id="uploadGraph" width="1600" height="900">
				Must have Javascript enabled to use this feature.
			</canvas>
		</div>

		<div id="uploadsSmall" class="w3-blue w3-padding w3-center w3-hide-large">
			<h2>Uploads</h2>
			<div class="w3-row-padding">
				<div class="w3-col s12 l4">
					<h3>All Time</h3>
					<h4 title="Average Upload">Average: <i class="fa-solid fa-cloud-upload"></i> <?php echo $averageUpload; ?>MBps</h4>
					<p><b>Max: <?php echo $highestUpload; ?> MBps</b> <em class="w3-small"> (<?php echo $highestUpTime; ?></em>)</p>
					<p><b>Min: <?php echo $lowestUpload; ?> MBps</b><em class="w3-small"> (<?php echo $lowestUpTime; ?></em>)</p>
				</div>
				<div class="w3-col s12 l4">
					<h3>Last 24 Hours</h3>
					<h4 title="Average Upload">Average: <i class="fa-solid fa-cloud-upload"></i> <?php echo $averageUpload24; ?>MBps</h4>
					<p><b>Max: <?php echo $highestUpload24; ?> MBps</b> <em class="w3-small"></p>
					<p><b>Min: <?php echo $lowestUpload24; ?> MBps</b><em class="w3-small"></p>
				</div>
				<div class="w3-col s12 l4">
					<button class="w3-green w3-display-bottom w3-button w3-large w3-round w3-padding"><a onclick="downloadCanvas('uploadGraph');">View the "Uploads" Graph</a></button>
				</div>
			</div>
			<br>
			<canvas id="uploadGraphSmall" width="300" height="150">
				Must have Javascript enabled to use this feature.
			</canvas>
		</div>


		<!--table class="w3-table"><tr><th>Time</th><th>MBps</th><th>Download Bps</th></tr>
					<?php /*
			foreach($speeds->downloads as $index => $download){
				$time = $speeds->times[$index];
				echo '<tr>';
				echo '<td>' . $time . '</td>';
				echo '<td>' . number_format($download / $mbpsConvert, 2) . '</td>';
				echo '<td>' . $download . '</td>';
				echo '</tr>';
			}
			*/
			?>
		</table-->

	</div>
</body>