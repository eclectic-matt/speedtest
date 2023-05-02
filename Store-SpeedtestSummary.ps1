<#
S:
cd S:\Development\Bash-Shell\speedtest
set SAVESTAMP=%DATE:~0,2%_%DATE:~3,2%_%DATE:~6,4%@%TIME:~0,2%_%TIME:~3,2%_%TIME:~6,2%.00
set SAVESTAMP=%SAVESTAMP: =0%.json
speedtest --format=json > results\%SAVESTAMP%
.Store-SpeedtestSummary.ps1 %SAVESTAMP%
#>
#Cls
#GET FILENAME INPUT
$file = $args[0];
Write-Host $file;
#$file = "12_04_2023@23_03_06.54";

#GET RESULTS FOLDER\
$folder = "S:\Development\Bash-Shell\speedtest\results\";

#GENERATE FULL PATH
$fullPath = ($folder + $file);

#GET FILE DATA
$fileData = Get-Content -Path $fullPath;
#CONVERT TO JSON
$json = ConvertFrom-Json $fileData;

#Write-Output $json;
#CHECK FOR ERROR
if($null -ne $json.error){
    #MAKE CSV LINE
    $appendString = ($file + "," + $json.error + ",,");

    Write-Output ("Writing: " + $appendString);
    #APPEND TO CSV FILE
    Add-Content -LiteralPath $summaryCSV -Value $appendString;

}else{

    #GET SUMMARY DATA
    $timestamp = $json.timestamp;
    $downloadMBPS = $json.download.bandwidth / 1E6 * 8;
    $uploadMBPS = $json.upload.bandwidth / 1E6 * 8;
    $resultUrl = $json.result.url;

    #GET SUMMARY CSV PATH
    $summaryCSV = "S:\Development\Bash-Shell\speedtest\summary\summary.csv";

    #MAKE CSV LINE
    $appendString = ($timestamp + "," + $downloadMBPS + "," + $uploadMBPS + "," + $resultUrl);

    Write-Output ("Writing: " + $appendString);
    #APPEND TO CSV FILE
    Add-Content -LiteralPath $summaryCSV -Value $appendString;

}