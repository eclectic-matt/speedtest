S:

cd S:\Development\Bash-Shell\speedtest

set SAVESTAMP=%DATE:~6,4%_%DATE:~3,2%_%DATE:~0,2%@%TIME:~0,2%_%TIME:~3,2%_%TIME:~6,2%.00

REM set SAVESTAMP=%DATE:~0,2%_%DATE:~3,2%_%DATE:~6,4%@%TIME:~0,2%_%TIME:~3,2%_%TIME:~6,2%.00

set SAVESTAMP=%SAVESTAMP: =0%.json

speedtest --format=json > results\%SAVESTAMP%

Powershell.exe -executionpolicy remotesigned -File  S:\Development\Bash-Shell\speedtest\Store-SpeedtestSummary.ps1 %SAVESTAMP%

powershell.exe -executionpolicy remotesigned -File S:\Development\Bash-Shell\speedtest\Generate-CSV_PDF.ps1