<?php

//GET THE LATEST PDF AS BASE64 AND RETURN

header("Content-Type: text/csv");
header("Content-Disposition: attachment; filename=summary.csv");

//DEFINE FOLDER
$summaryLink = "S:\Development\Bash-Shell\speedtest\summary\summary.csv";

$summary = file_get_contents($summaryLink);
$encoded = base64_decode($summary);

echo $summary;