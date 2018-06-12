<?php
include("common.inc");

$csv = loadCsv("$folder/submission.csv");
$record = getRecord(@$_GET["cid"], $csv);

$msg="";
//cidを探す
if ($record){
    if ($record["confirm"]!=1){
        lock();
        $record["time"] = date("n/j/Y G:i:s");
        $record["confirm"] = 1;
        saveRecord($csv, $record);
        saveCsv("$folder/submission.csv", $csv);
        unlock();

        $msg.="Account confirmed. Automatically jump to the login page.";
    }else{
        $msg.="This account has already been verified. Automatically jump to the login page.";
    }
}else{
    $msg.="Invaild URL. Automatically jump to the registration page.";
}
?>

<html lang="en">
<head>
<meta http-equiv="content-type" content="text/html; charset=ISO-8859-1">
<meta http-equiv="refresh" content="5; URL=./">
<link rel="stylesheet" type="text/css" href="style.css">
<link href="//maxcdn.bootstrapcdn.com/bootstrap/3.3.6/css/bootstrap.min.css" rel="stylesheet">
</head>


<body>
<div class="container">

<H1><?php echo $sys;?></H1>
<h2>Confirmation</h2>

<?php echo $msg;?><br>

</div>

</body>
</html>
