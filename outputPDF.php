<?php

//GET THE LATEST PDF AS BASE64 AND RETURN
//DEFINE FOLDER
$summaryFolder = 'S:\Development\Bash-Shell\speedtest\summary\pdf';

//GET THE FILES IN THIS FOLDER
$summaryPDFs = preg_grep('~\.(pdf)$~', scandir($summaryFolder, SCANDIR_SORT_ASCENDING));
$summaryPDF = end($summaryPDFs);
$summaryLink = $summaryFolder . "/" . $summaryPDF;
//echo $summaryLink;
//die();

$summary = file_get_contents($summaryLink);
$encoded = base64_decode($summary);

//header('Content-type:application/pdf; filename="' . $summaryPDF . '"');

header("Content-type:application/pdf");
header('Content-Disposition:attachment;filename="' . $summaryPDF . '"');

echo $summary;