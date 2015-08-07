#Variables
$yesterday = (Get-Date).AddDays(-1).ToString("yyyyMMdd");
$exchangedir = "\\data\ExchangeLogs\*"+$yesterday+"*.LOG";
$trackingdir = "\\data\exchange_tracking\csv\";
$s_header = "datetime,clientip,clienthostname,serverip,serverhostname,sourcecontext,connectorid,source,eventid,internalmessageid,messageid,recipientaddress,recipientstatus,totalbytes,recipientcount,relatedrecipientaddress,reference,messagesubject,senderaddress,returnpath,messageinfo,directionality,tenantid,originalclientip,originalserverip,customdata" + $vbCrLf;
$logfile = $trackingdir + $yesterday + ".LOG";
$csvfile = $trackingdir + $yesterday + ".csv";
#Add a header to a master log file
Add-Content -Encoding UTF8 -path $logfile -value $s_header;
#Loop through yesterday's exchange logs and append them to one big Log file
foreach ($file in Get-ChildItem $exchangedir) {
    #Determine the number of lines in a log file and then add everything after the master log file
    $lines = Get-Content $file | Measure-Object -Line;
    $contentLines = $lines.Lines - 5;
    $logContent = Get-Content $file | Select-Object -last $contentLines;
    Add-Content -Encoding UTF8 -path $logfile -value $logContent;
}
#Remove Byte Order Mark
$MyFile = Get-Content $logfile
$Utf8NoBomEncoding = New-Object System.Text.UTF8Encoding($False)
[System.IO.File]::WriteAllLines($logfile, $MyFile, $Utf8NoBomEncoding)
#Create a smaller Log file with only the necessary columns for a push into CouchDB
$LogQuery = New-Object -ComObject "MSUtil.LogQuery";
$InputFormat = New-Object -ComObject "MSUtil.LogQuery.CSVInputFormat";
$OutputFormat = New-Object -ComObject "MSUtil.LogQuery.CSVOutputFormat";
$SQLQuery = "SELECT datetime,clientip,serverip,eventid,internalmessageid,recipientaddress,totalbytes,recipientcount,relatedrecipientaddress,reference,messagesubject,senderaddress,returnpath,directionality,tenantid,originalserverip INTO '" + $csvfile + "' FROM '" + $logfile + "'";
$InputFormat.headerRow=1;
$InputFormat.nFields=-1;
$InputFormat.nSkipLines=0;
$InputFormat.iTsFormat="yyyy-MM-dd hh:mm:ss";
$InputFormat.fixedFields=1;
$InputFormat.dtLines=10;
$OutputFormat.headers="AUTO";
$OutputFormat.oTsFormat="yyyy-MM-dd hh:mm:ss";
$OutputFormat.fileMode=1;
$rtnVal = $LogQuery.ExecuteBatch($SQLQuery, $InputFormat, $OutputFormat);
$OutputFormat = $null;
$InputFormat = $null;
$LogQuery = $null;
#Delete Master Log File and only leave trimmed Csv
Remove-Item $logfile