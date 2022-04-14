<?php
session_start();

include("common.inc");

if(!isset($_SESSION['user'])) {
  header("Location: index.php");
  exit;
}

// Load user file
$cid = getCid($_SESSION['user']);
$csv = loadCsv("$folder/submission.csv");
$record = getRecord($cid, $csv);
$email = $record["email"];
$user = $record["user"];

// Load format file
$format = loadCsv("$folder/format.csv");
$items = getItems(0, $format);
$types = getItems(1, $format);
$segments = getItems(2, $format);
$titles = getItems(3, $format);
$placeholders = getItems(4, $format);
$explanations = getItems(5, $format);
$mandatories = getItems(6, $format); 
$csvlists = getItems(7, $format);

$sections = array("Contact", "Sponsorship", "Exhibition");

// Get item from database
foreach($items as $item){
    $value[]=$record["$item"];
}

if(isset($_GET['state'])){
    $state = $_GET['state'];
    if($state == 0){
        $msg='<div class="alert alert-success" role="alert"><strong>Updated!</strong></div>';
    }else if($state == 1){
        $msg='<div class="alert alert-danger" role="alert">Invalid email address.</div>';
    }else if($state == 2){
        $msg='<div class="alert alert-danger" role="alert">The email address is already used.</div>';
    }
}

if (isset($_GET['file'])){
	unlock();
	$files = glob("$folder/".$record["oid"].$_GET['file'].".*");
	if (function_exists('mime_content_type')){
		header("Content-type: ". mime_content_type($files[0]));
	}
	header("Content-Disposition: attachment; filename=".$files[0]);
	readfile($files[0]);
    //header("Location: home.php");
    exit;
}

if($_SERVER['REQUEST_METHOD']==='POST'){
    if(isset($_POST['update'])) {
        if ($record){
            if(!preg_match("/^([a-zA-Z0-9])+([a-zA-Z0-9\._-])*@([a-zA-Z0-9_-])+([a-zA-Z0-9\._-]+)+$/", @$_POST["email"])){
             $state=1;

         }else if($record["email"]!=$_POST["email"]&&is_user_exists("$folder/submission.csv",$_POST["email"])!=-1){
             $state=2;
         }else{
            lock();
            $record["time"] = date("n/j/Y G:i:s");
            if(empty($record["priority"])){$record["priority"] = date("n/j/Y G:i:s");}
            $i = 0;
            foreach($items as $item){
                if($types[$i] == "file"){
                 if($_FILES["$item"]["name"]!=""){
                    $file = upload("$item");
                    if ($item!=false){
                     $url = "https://" . $_SERVER['HTTP_HOST'];
                     $url .= substr($_SERVER['REQUEST_URI'], 0, strrpos($_SERVER['REQUEST_URI'], "/"));
                     $record["$item"] = htmlspecialchars($url."/$file");
                 }
             }
         }else{
            $record["$item"] = htmlspecialchars($_POST["$item"]);
        }
        $i++;
    }

    rename("$folder/submission.csv", "$folder/backup/data" . date("Y_md_Hi_s"). ".csv");
    saveRecord($csv, $record);
    saveCsv("$folder/submission.csv", $csv);
    unlock();
    $state = 0;
}
}
}
$url = "home.php?state=$state";
header("Location:".$url);
exit;
}


?>

<html lang="en">
<head>
    <meta http-equiv="content-type" content="text/html; charset=ISO-8859-1">
    <link rel="stylesheet" type="text/css" href="style.css">
    <link href="//maxcdn.bootstrapcdn.com/bootstrap/3.3.6/css/bootstrap.min.css" rel="stylesheet">


    <title><?php echo $sys;?></title>
</head>


<body>
    <script src="//code.jquery.com/jquery-1.11.2.min.js"></script>
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/1.12.4/jquery.min.js"></script>
    <script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/js/bootstrap.min.js"></script>
    <script type="text/javascript">
        $(document).on('change', ':file', function() {
            var input = $(this),
            numFiles = input.get(0).files ? input.get(0).files.length : 1,
            label = input.val().replace(/\\/g, '/').replace(/.*\//, '');
            input.parent().parent().next(':text').val(label);
        });
    </script>


    <nav class="navbar navbar-default">
       <div class="container-fluid">
          <div class="navbar-header">
             <button type="button" class="navbar-toggle collapsed" data-toggle="collapse" data-target="#navbarEexample">
                <span class="sr-only">Toggle navigation</span>
                <span class="icon-bar"></span>
                <span class="icon-bar"></span>
                <span class="icon-bar"></span>
            </button>
            <p class="navbar-brand"><?php echo $sys;?></p>
        </div>

        <div class="collapse navbar-collapse" id="navbarEexample">
         <ul class="nav navbar-nav">
            <?php if($user=="admin")echo '<li><a href="view.php">List View</a></li>';?>
            <?php
            $i = 0;
            while($i<max($segments)){
                echo '<li><a href="#sec'.$i.'">'.$sections[$i].'</a></li>';
                $i++;
            }
            ?>
        </ul>
        <ul class="nav navbar-nav navbar-right">
            <li class="dropdown">
               <a href="#" class="dropdown-toggle" data-toggle="dropdown" role="button">Account: <?php echo $email;?><span class="caret"></span></a>
               <ul class="dropdown-menu" role="menu">
                  <li><a href="logout.php">Log out</a></li>
              </ul>
          </li>
      </ul>
  </div>
</div>
</nav>

<div class="container">
    <div class="row">
        <div class="col-xs-1">
        </div>
        <div class="col-xs-10">
            <h2><?php echo $hometitle; ?></h2>

            <p>
                <?php echo $homeexplanation; ?>
            </p>


        </div>
        <div class="col-xs-1">
        </div>
    </div>


    <div class="row">
        <?php echo $msg;?>
    </div>

    <form enctype="multipart/form-data" method="POST" action="<?php echo $_SERVER['REQUEST_URI']?>">
        <?php

        $out="";
        $cnt = 0;
        while($cnt<max($segments)){
            $i=0;
            $out.='<h2 id="sec'.$cnt.'">'.$sections[$cnt].'</h2>';
            foreach($items as $item){
                if( $segments[$i] == $cnt + 1){
                    $out.='<div class="form-group"><div class="row">';        
                    $out.='<div class="col-xs-4"><label>'.$titles[$i];
                    if($mandatories[$i]){
                        $out.='<p class="text-danger">*</p>';
                    }
                    $out.='</label>';
                    if($explanations[$i]!=""){
                        $out.="<br>$explanations[$i]";
                    }
                    $out.='</div><div class="col-xs-6">';
                    if($types[$i] == "csv"){
                        $out.='<select class="form-control" name="'.$item.'">';
                        
                        //$out.=getOption("$folder/$item.csv",$value[$i]);
                        $out.=getOption("$folder/$csvlists[$i]",$value[$i]);
                        $out.='</select>';
                    }else if($types[$i] == "file"){
                        $out.='<input type="hidden"  class="form-control" name="MAX_FILE_SIZE" value="<?php echo 40*1024*1024?>"';
                        if($mandatories[$i]){
                            $out.='required aria-required="true"';
                        }
                        $out.='/>';
                        $out.='<label class="input-group-btn"><span class="btn btn-primary">Choose File<input type="file"  class="form-control" name="'.$item.'" style="display:none" size=40></span></label><input type="text" class="form-control" readonly="">'.linkToFile("$item");
                    }else if($types[$i] == "textarea"){
                        $out.='<textarea class="form-control" name="'.$item.'" rows="5" placeholder="'.$placeholders[$i].'">'.$value[$i].'</textarea>';
                    }else{
                        $out.='<input type="'.$type.'" class="form-control" name="'.$item.'" placeholder="'.$placeholders[$i].'" value="'.$value[$i].'"';
                        if($mandatories[$i]){
                            $out.='required aria-required="true"';
                        }
                        $out.='>';
                    }
                    $out.='</div>';
                    $out.='</div></div>';
                }
                $i++;
            }
            $out.='<button type="submit" name="update" class="btn btn-success">Update</button><hr>';
            $cnt++;
        }
        echo $out;


        ?>
    </form>
</div>
</body>
</html>