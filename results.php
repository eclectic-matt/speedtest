<?php

/**
 * Generates a new set of data if there are new JSON files available, then serves speed.php which shows the latest data.
 */

//DEFINE FOLDER
$folder = 'S:\Development\Bash-Shell\speedtest\results';

//GET THE FILES IN THIS FOLDER
$files = scandir($folder, SCANDIR_SORT_ASCENDING);

//DETERMINE IF NEW DATA FOUND (ASSUMED YES)
$regenerateFlag = true;

//GET THE LAST FILE
$lastFile = end($files);
$fullName = $folder . '/' . $lastFile;
if(
	(filesize($fullName) === false) ||
	(filesize($fullName) === 0)
){
	//LATEST FILE NOT WRITTEN - DO NOT REGENERATE
	$regenerateFlag = false;
}else{

	//GET THE TIMESTAMP FROM THE FILE NAME
	$lastFileTime = convertFileNameToTimestamp($lastFile);
	//echo $lastFileTime . PHP_EOL;

	//GET THE TIMESTAMP FROM THE CURRENT CSV FILE
	$csvFolder = 'S:\Development\xampp\htdocs\speedtest\csv\\';
	$csvFilePath = glob("$csvFolder*.csv")[0];
	$csvFile = str_ireplace($csvFolder, '', $csvFilePath);
	$csvFileTime = str_ireplace('.csv', '', $csvFile);
	//echo $csvFileTime . PHP_EOL;

	//IF THE CSV FILE TIME DOES NOT MATCH THE LATEST JSON FILE
	if($csvFileTime !== $lastFileTime){

		//WE HAVE NEW DATA - REGENERATE
		$regenerateFlag = true;
	}
}



if($regenerateFlag){
	//echo 'Regenerating';
	regenerateData($csvFilePath);
	//echo 'Regenerated Data...';

}


//NOW LOAD DATA
//$speeds = loadData();

//NOW DISPLAY RESULTS
header('Location: /speed.php');






function regenerateData($csv){

	//echo 'Loading ' . $csv . PHP_EOL;
	//CHECK IF THE CSV CAN BE ACCESSED
	if(filesize($csv) === false){
		throw new Exception('Cannot open CSV');
	}

	//COPY FILE CONTENTS TO ARCHIVE
	$archiveName = str_ireplace('S:\Development\xampp\htdocs\speedtest\csv','S:\Development\xampp\htdocs\speedtest\archive',$csv);
	//echo 'Archiving to ' . $archiveName . '<br>';

	file_put_contents($archiveName, file_get_contents($csv));

	//CLEAR CSV CONTENTS AT START OF RUN
	file_put_contents($csv, "");

	//CHECK IF THE CSV IS EMPTY
	//if(filesize($csv) === 0){

	//CSV FILE EMPTY (CLEAN RUN) SO GET HEADERS
	$headers = generateHeaders();
	$headerString =  implode(',', $headers) . "\n";
	//echo 'Headers generated: ' . $headerString . '<br>';
	//WRITE HEADERS TO FILE
	file_put_contents($csv, $headerString);
	//}

	$csvFile = file_get_contents($csv);
	$csvLines = explode("\n", $csvFile);
	//GET LAST LINE
	$lastCsvLine = $csvLines[count($csvLines) - 1];
	//echo $lastCsvLine;
	//IF THE LINE IS THE HEADER LINE
	if(substr($lastCsvLine, 0, 3) === 'type'){
		//WRITE *EVERY* RESULT
		$lastResultsFile = null;
	}else{
		//GET THE FIRST TIMESTAMP
		$lastResultsFile = substr($lastCsvLine, 0, 27);
	}

	//GET THE RESULTS FILES
	$folder = 'S:\Development\Bash-Shell\speedtest\results';
	$files = scandir($folder, SCANDIR_SORT_ASCENDING);

	//echo 'Processing data from ' . count($files) . ' files....<br>';

	$newFileContent = '';
	//ITERATE FILES
	foreach($files as $file){

		if(str_ireplace('.','',$file) === ''){
			echo 'Skipping dot file...(' . $file . ')<br>';
			continue;
		}
	
		$fullName = $folder . '/' . $file;
		if(filesize($fullName) === false){
			//echo 'Skipping file with no size...(' . $file . ')<br>';
			continue;
		}
		if(filesize($fullName) === 0){
			//echo 'Skipping empty file...(' . $file . ')<br>';
			continue;
		}

		//echo 'Getting data from ' . $file . '<br>';

		//GET THE FILE CONTENT
		$content = file_get_contents($fullName);
		$json = json_decode($content, true);

		if(isset($json['error'])){
		//if(isset($json->error)){
			///echo 'Skipping error file...(' . $file . ')<br>';
			//$speeds->errors[] = $file;
			continue;
		}

		//RESET VARIABLES
		$newLineData = '';
		$data = array();

		foreach(array_keys($json) as $key){
			$current = $json[$key];
			if(is_array($current)){
				foreach(array_keys($current) as $subKey){
					
					$subData = $current[$subKey];
					if(is_array($subData)){
						foreach(array_keys($subData) as $subSubKey){
							$data[] = $current[$subKey][$subSubKey];
							//echo 'Processing ' . $current[$subKey][$subSubKey] . '<br>';
						}
					}else{
						$data[] = $current[$subKey];
						//echo 'Processing ' . $current[$subKey] . '<br>';
					}
				}
			}else{
				$data[] = $current;
				//echo 'Processing ' . $current . '<br>';
			}
		}
		
		$newLineData = implode(',', array_values($data));
		$newLineData .= "\n";
		file_put_contents($csv, $newLineData,FILE_APPEND );
	}

	$csvFolder = 'S:\Development\xampp\htdocs\speedtest\csv\\';
	$fileName = str_ireplace($csvFolder, '', $csv);
	$newFileName = str_ireplace($fileName, $file, $csv);
	$newFileName = str_ireplace('.00.json', '.csv', $newFileName);
	//RENAME
	rename($csv, $newFileName);
}

function generateHeaders(){
	
	//echo 'Generating Headers now...<br>';
	//THE RESULTS FOLDER
	$folder = 'S:\Development\Bash-Shell\speedtest\results';
	//GET ALL RESULTS FILES
	$files = scandir($folder, SCANDIR_SORT_ASCENDING);

	//GET A SUITABLE FILE (INDEX 2 IS THE OLDEST ACTUAL JSON FILE)
	$example = $files[2];
	//
	$fullName = $folder . '/' . $example;
	$content = file_get_contents($fullName);
	$json = json_decode($content, true);
	//STORE HEADERS 
	$headers = array_keys($json);

	//AN ARRAY OF DOT-SPACED NAMES TO STORE AS CSV HEADINGS
	$headingNames = array();
	//ITERATE HEADERS
	foreach($headers as $header){

		//GET THE CURRENT HEADER
		$fullHeader = $json[$header];
		//IF THIS ITSELF IF AN ARRAY
		if(is_array($fullHeader)){
			//ITERATE THIS ARRAY
			foreach(array_keys($fullHeader) as $fh){
				if(is_array($fullHeader[$fh])){
					foreach(array_keys($fullHeader[$fh]) as $ffh){
						$headingNames[] = $header . '.' . $fh . '.' . $ffh;
					}
				}else{
					//STORE AS DOT-SPACED HEADING
					$headingNames[] = $header . '.' . $fh;
				}
			}
		}else{
			//STORE AS TOP-LEVEL HEADING
			$headingNames[] = $header;
		}
	}
	return $headingNames;
}

function loadData(){

	$returnData = new stdClass();
	$downloadBandwidthIndex = 6;
	$returnData->downloads = array();
	$uploadBandwidthIndex = 10;
	$returnData->uploads = array();
	$timingIndex = 1;
	$returnData->times = array();

	//GET THE CURRENT CSV FILE
	$csvFolder = 'S:\Development\xampp\htdocs\speedtest\csv\\';
	$csvFilePath = glob("$csvFolder*.csv")[0];
	$csvFile = str_ireplace($csvFolder, '', $csvFilePath);
	$csvFileTime = str_ireplace('.csv', '', $csvFile);

	//LOAD FILE
	$data = file_get_contents($csvFilePath);
	$lines = explode('\n', $data);
	foreach($lines as $line){
		//SKIP HEADER ROW
		if(substr($line, 0, 4) === 'type') continue;
		//EXPLODE ON COMMA
		$lineData = explode(',', $line);
		$returnData->times[] = $lineData[$timingIndex];
		//echo 'Reading result from ' .$lineData[$timingIndex];
		$returnData->downloads[] = $lineData[$downloadBandwidthIndex];
		$returnData->uploads[] = $lineData[$uploadBandwidthIndex];
	}

	$mbpsConvert = 1E6 / 8;

	//DOWNLOADS
	//highest
	//$returnData['summary'] = array();
	//$returnData['summary']['downloads'] = array();
	//$returnData['summary']['downloads']['highest'] = array();
	$returnData->summary = new stdClass();
	$returnData->summary->downloads = new stdClass();
	$returnData->summary->downloads->highest = new stdClass();
	$returnData->summary->downloads->highest->MBps = number_format(max($returnData->downloads) / $mbpsConvert, 2);
	$highestDownIndex = array_search(max($returnData->downloads), $returnData->downloads);
	$returnData->summary->downloads->highest->time = getHumanTimeFromFileName($returnData->times[$highestDownIndex]);

	return $returnData;
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

	//COUNT
	$testCount = $speeds->count;

	//GET THE LAST FILE NAME
	$dtString = end($files);
	//STORE HUMAN-READABLE TIME STRING
	$lastTestRun = getHumanTimeFromFileName($dtString);


	return $returnData;
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
	$year = substr($dtString,6,4);
	//GET THE MONTH
	$month = substr($dtString,3,2);
	//GET THE DAY
	$day = substr($dtString,0,2);

	//GET THE HOURS
	$hours = substr($dtString,11,2);
	//GET THE MINUTES
	$mins = substr($dtString,14,2);
	//GET THE SECONDS
	$seconds = substr($dtString,17,2);

	//CONCAT AND RETURN
	return $year . $month . $day . '_' . $hours . $mins . $seconds;
}
