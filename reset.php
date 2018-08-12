<?php
include("common.inc");

$csv = loadCsv("$folder/submission.csv");
$record = getRecord(@$_GET["cid"], $csv);

$msg="";
$jump="";
// Search cid
if ($record){
    if (isset($_POST["reset"])){
        if(!preg_match("/\A(?=.*?[a-z])(?=.*?\d)[!-~]{5,20}+\z/i", $_POST["pass"])){
		$msg='<div class="alert alert-danger" role="alert">Password must be 5 - 20 characters, including 1 numeric character.</div>';               	
       	}else{
	        lock();
        	$record["time"] = date("n/j/Y G:i:s");
        	$record["password"] = password_hash($_POST['pass'], PASSWORD_DEFAULT);
        	saveRecord($csv, $record);
        	saveCsv("$folder/submission.csv", $csv);
        	unlock();
        	$msg='<div class="alert alert-success" role="alert">Password was successfully updated. Automatically jump to the login page.</div>';
        	// Jump to login page
        	$jump='<meta http-equiv="refresh" content="5; URL=./">';
	}
    }
}else{
    $msg='<div class="alert alert-danger" role="alert">Invaild URL. Automatically jump to the login page.</div>';
    // Jump to login page
    $jump='<meta http-equiv="refresh" content="2; URL=./">';
}
?>


<html lang="en">
<head>
<meta http-equiv="content-type" content="text/html; charset=ISO-8859-1">
<?php echo $jump;?>
<link rel="stylesheet" type="text/css" href="style.css">
<link href="//maxcdn.bootstrapcdn.com/bootstrap/3.3.6/css/bootstrap.min.css" rel="stylesheet">
</head>

<body>
<div class="container">

<H1><?php echo $sys;?></H1>
<h2>New Password</h2>

<form enctype="multipart/form-data" method="POST" action="<?php echo $_SERVER['REQUEST_URI']?>">

<div class="form-group">
<div class="row">
<div class="col-xs-4">Password</div>
<div class="col-xs-6"><input type="password" class="form-control" name="pass"/>
</div>
</div>

<div class="form-group">
<div class="row">
<div class="col-xs-4"><button type=submit name="reset" class="btn btn-primary">Set</button></div>
<div class="col-xs-6"><p><?php echo $msg;?></p></div>
</div>
</div>

</form>
</div>
</body>
</html>
