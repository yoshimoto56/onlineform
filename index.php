<?php
session_start();

include("common.inc");

if( isset($_SESSION['user']) != "") {
  header("Location: home.php");
}

if (isset($_POST["create"])){
	if (preg_match("/^([a-zA-Z0-9])+([a-zA-Z0-9\._-])*@([a-zA-Z0-9_-])+([a-zA-Z0-9\._-]+)+$/", @$_POST["email"])){
		//User already exists?
		if(is_user_exists("$folder/submission.csv",$_POST["email"])!=-1){
			$msg='<div class="alert alert-danger" role="alert">'.$_POST["email"].' is already used.</div>';
	        }
		else{
	        if(!preg_match("/\A(?=.*?[a-z])(?=.*?\d)[!-~]{5,20}+\z/i", $_POST["pass"])){
			$msg='<div class="alert alert-danger" role="alert">Password must be 5 - 20 characters, including 1 numeric character.</div>';               	
        	}else{
			lock();
			$csv = loadCsv("$folder/submission.csv");
			$oidPos = getPosFromKey("oid", $csv);
			$userPos = getPosFromKey("user", $csv);
			$passPos = getPosFromKey("password", $csv);
			$emailPos = getPosFromKey("email", $csv);
			$timePos = getPosFromKey("time", $csv);

			//Create new oid
			$oids=array();
			foreach($csv as $r) $oids[] = $r[$oidPos];
			array_shift($oids);
			$endR = end($csv);
			$oid = $endR[$oidPos]+1;
			while(in_array($oid, $oids)) $oid++;

			//Create new record
			$record = array_pad(array(), sizeof($csv[0]), "");
			$record[$userPos] = "guest";
			$record[$oidPos] = $oid;
			$record[$emailPos] =  htmlspecialchars($_POST["email"]);
			$record[$timePos] = htmlspecialchars(date("n/j/Y G:i:s"));
			$record[$passPos] = crypt($_POST['pass']);
			array_push($csv, $record);
			saveCsv("$folder/submission.csv", $csv);
			unlock();

			//Update cid
			$fp = fopen("$folder/cids.txt", "w");
			$oids[] = $oid;
			sort($oids, SORT_NUMERIC);
			foreach($oids as $oid){
				fwrite($fp, $oid . "\t" . getCid($oid) . "\n");
			}
			fclose($fp);
	
			//Generate mail body
			$url = "https://" . $_SERVER['HTTP_HOST'];
			$url .= substr($_SERVER['REQUEST_URI'], 0, strrpos($_SERVER['REQUEST_URI'], "/"));
			$url .= "/confirm.php?cid=" . getCid($oid);
			$from = $emailFrom;
			$to = $_POST["email"];
			$title = $conf. " Sponsor Account Confirmation";
			$body = "Thank you for registering the $conf. sponsor account.\n".
				"\n".
				"Please verify your account from the following URL\n".
				"$url\n". 
				"If you have any technical problems or questions, please contact\n$emailFrom.\n".
				"\n".
				"Kind regards,\n".
				"$sys\n";
			if (mb_send_mail($to, $title, $body, "From: $from")){
				$msg='<div class="alert alert-success" role="alert">Confirmation email has been sent to '.$to.'.</div>';
			}else{
				$msg='<div class="alert alert-danger" role="alert">Invalid email address.</div>';
			}
		}
		}
	}else{
		$msg='<div class="alert alert-danger" role="alert">Please input valid email address.</div>';
	}
}

//LOGIN
if (isset($_POST["login"])){
	//User search
	$email = $_POST["email"];
	$uid = is_user_exists("$folder/submission.csv",$email);
	if($uid == -1){
		$msg.="Account $email does not exist.";
        }else{
		$csv = loadCsv("$folder/submission.csv");
		$passPos = getPosFromKey("password", $csv);
		$confirmPos = getPosFromKey("confirm", $csv);
		$oidPos = getPosFromKey("oid", $csv);
		$pass = $csv[$uid][$passPos];
		$confirm = $csv[$uid][$confirmPos];
		$oid = $csv[$uid][$oidPos];
		if($confirm==1){
			if(crypt($_POST['pass'],$pass) === $pass){
		        	$url = "https://" . $_SERVER['HTTP_HOST'];
		        	$url .= substr($_SERVER['REQUEST_URI'], 0, strrpos($_SERVER['REQUEST_URI'], "/"));
		        	$url .= "/home.php";
		        	$_SESSION['user'] = $oid;
		        	header("location: home.php");
		        	exit();
		        }else{
				$msg='<div class="alert alert-danger" role="alert">Invalid password.</div>';
			}
		}else{
			$msg='<div class="alert alert-danger" role="alert">This account has not been verified.</div>';
		}
	}
}
//RESET
if (isset($_POST["reset"])){
	//User search
	$email = $_POST["email"];
	$uid = is_user_exists("$folder/submission.csv",$email);
	if($uid == -1){
		$msg.="Account $email does not exist.";
        }else{
		//Generate mail body
		$csv = loadCsv("$folder/submission.csv");
		$oidPos = getPosFromKey("oid", $csv);
		$oid = $csv[$uid][$oidPos];
		$url = "https://" . $_SERVER['HTTP_HOST'];
		$url .= substr($_SERVER['REQUEST_URI'], 0, strrpos($_SERVER['REQUEST_URI'], "/"));
		$url .= "/reset.php?cid=" . getCid($oid);
		$from = $emailFrom;
		$to = $_POST["email"];
		$title = $conf. " Reset Password";
		$body =	"Please reset password from the following URL\n".
			"$url\n". 
			"If you have any technical problems or questions, please contact\n$emailFrom.\n".
			"\n".
			"Kind regards,\n".
			"IEEE WHC 2019 Sponsor Registration System\n";
		if (mb_send_mail($to, $title, $body, "From: $from")){
			$msg='<div class="alert alert-success" role="alert">An email has been sent to '.$email.' to reset your password.</div>';
		}else{
			$msg='<div class="alert alert-danger" role="alert">Invalid email address.</div>';
		}
	}
}


//Form
?>

<html lang="en">
<head>
<meta http-equiv="content-type" content="text/html; charset=ISO-8859-1">
<link rel="stylesheet" type="text/css" href="style.css">
<link href="//maxcdn.bootstrapcdn.com/bootstrap/3.3.6/css/bootstrap.min.css" rel="stylesheet">

<title><?php echo $sys;?></title>
</head>


<body>
<div class="container">

<H1><?php echo $sys;?></H1>
<h2>Log In</h2>
<div class="row">
<div class="col-xs-12">
<p>
To begin, log in with your email address and password.<br>
If you are unsure about whether or not you have an account, or have forgotten your password, go to the Reset Password screen.
</p>
</div>
</div>

<form enctype="multipart/form-data" method="POST" action="<?php echo $_SERVER['REQUEST_URI']?>">

<div class="form-group">
<div class="row">
<div class="col-xs-4"><label>Email</label></div>
<div class="col-xs-6"><input type="email" class="form-control" name="email" required aria-required="true"></div>
</div>
</div>

<div class="form-group">
<div class="row">
<div class="col-xs-4"><label>Password</label></div>
<div class="col-xs-6"><input type="password" class="form-control" name="pass"></div>
</div>
</div>

<div class="form-group">
<div class="row">
<div class="col-xs-4"><button type="submit" name="login" class="btn btn-primary">Log In</button></div>
<div class="col-xs-6"></div>
<div class="col-xs-2"><button type="submit" name="reset" class="btn btn-default">Reset Password</button></div>
</div>
</div>

<div class="form-group">
<div class="row">
<div class="col-xs-4"><button type="submit" name="create" class="btn btn-success">Create An Account</button></div>
<div class="col-xs-6"><p><?php echo $msg;?></p></div>
</div>
</div>

</form>

</div>
</body>
</html>
