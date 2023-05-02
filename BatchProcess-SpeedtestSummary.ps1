<# 
    Use this to generate a fresh summary run from ALL json files.
#>

#CLEAR SCREEN
Cls

#CLEAR THE SUMMARY FILE BEFORE RUNNING (PREVENTS DUPLICATION)
$clearFile = $true;

#IF CLEAR REQUESTED
if($clearFile){

    #GET SUMMARY FILE
    $summaryCSV = "S:\Development\Bash-Shell\speedtest\summary\summary.csv";
    #SET EMPTY FILE
    "timestamp,download,upload,url" | Out-File $summaryCSV;
}

#GET RESULT FOLDER STRING
$folder = "S:\Development\Bash-Shell\speedtest\results";
#GET ALL CHILD ITEMS IN THIS FOLDER WITH FILE .json AND PIPE INTO
$items = Get-ChildItem $folder -Filter *.json;

$count = $items.Length;
$current = 1;

#FOREACH FILE IN THIS FOLDER
$items | Foreach-Object {

    $i = ($current / $count) * 100;
    Write-Progress -Activity "Files processed" -Status "$current of $count :" -PercentComplete $i
    $current = $current + 1;

    #GET THE FILESIZE
    $filesize = (Get-Item $_.FullName).Length;
    #CHECK IF THE FILESIZE IS 0 (NOT YET WRITTEN)
    if($filesize -eq 0){ 
        Write-Output ("Empty file: " + $_.FullName);
    }else{
        #PASS THROUGH TO GENERATE SUMMARY PS
        Write-Output ("Summary for " + $_.BaseName);
        .\Store-SpeedtestSummary.ps1 ($_.BaseName + ".json");
    }
}

#REGENERATE PDF AT THE END
.\Generate-CSV_PDF.ps1