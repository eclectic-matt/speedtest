function OpenExcelTo-Print(){

 param(
		$filePath
	)

    Write-Output ("Getting from " + $filePath);
    $file = Get-Item $filePath
    $basePath = $filePath -replace "\.csv", "";
    $ts = Get-Date -Format "yyyyMMdd_HHmmss";

    #DEFINE A NEW OUTPUT PATH
    $outpath = "S:\Development\Bash-Shell\speedtest\summary\pdf\";
    $newfile = ($outpath + $ts + '.pdf');
    Write-Output ("Writing to " + $newfile);

    $excel = New-Object -ComObject Excel.Application
    $excel.Visible = $false;
    $excel.displayAlerts = $false;

    $workbook = $excel.Workbooks.Open($filePath, 3);
    
    #LANDSCAPE
    #$workbook.ActiveSheet.PageSetup.Orientation = 2;
    
    #OR SMALL
    $workbook.ActiveSheet.Range("A:D").Font.Size = 8;
    
    #EXPORT AS PDF
    $workbook.ExportAsFixedFormat($xlFixedFormat::xlTypePDF, $newfile)
    #$excel.Print();
    $workbook.Close($false);
    $excel.Quit();
    ##[System.Runtime.Interopservices.Marshal]::ReleaseComObject($excel)
    #Remove-Variable excel
    #$excel = $null;
    #$workbook = $null;
    Remove-Variable excel;
    Remove-Variable workbook;
}

#GET SUMMARY CSV PATH
$summaryCSV = "S:\Development\Bash-Shell\speedtest\summary\summary.csv";
#PASS TO FUNCTION
OpenExcelTo-Print $summaryCSV;