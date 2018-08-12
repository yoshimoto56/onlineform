<?php
session_start();

include("common.inc");

if(!isset($_SESSION['user'])) {
  header("Location: index.php");
  exit;
}

if(isset($_POST['logout'])) {
  session_destroy();
  unset($_SESSION['user']);
  header("Location: index.php");
  exit;
}

$cid = getCid($_SESSION['user']);
$csv = loadCsv("$folder/submission.csv");
$record = getRecord($cid, $csv);

if($record['user']!=admin) {
  header("Location: index.php");
  exit;
}

$format = loadCsv("$folder/format.csv");
$items = getAllItems(0, $format);
$titles = getAllItems(3, $format);


if($_SERVER['REQUEST_METHOD']==='POST'){
    if(isset($_POST['update'])) {
        if($_FILES["format"]["name"]!=""){
            manage("format");
        }
        header("Location:view.php");
        exit;
    }
    if(isset($_POST['download'])) {
	$files = glob("$folder/submission.csv");
	if (function_exists('mime_content_type')){
		header("Content-type: ". mime_content_type($files[0]));
	}
	header("Content-Disposition: attachment; filename=".$files[0]);
	readfile($files[0]);
        header("Location: view.php");
	exit;
    }
}

?>


<html lang="en">
<head>
<meta http-equiv="content-type" content="text/html; charset=ISO-8859-1">
<link rel="stylesheet" type="text/css" href="style.css">
<link href="//maxcdn.bootstrapcdn.com/bootstrap/3.3.6/css/bootstrap.min.css" rel="stylesheet">
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
</head>

<body>


<div class="container">
<h1><?php echo $sys;?></h1>
<h2>List View</h2>


<?php

echo '<table class="table">';
    echo '<thead><tr>';
    $i=0;
    foreach($items as $item){
        echo "<td>$titles[$i]</td>";
        $i++;
    }
    echo '</thead></tr>';
$u = 0;
foreach($csv as $r){
    if($u>0){
    echo '<thead><tr>';
    $i=0;
    $cid = getCid($r[0]);
    $data = getRecord($cid, $csv);
    foreach($items as $item){
        if($types[$i]=="file"){
            echo '<td>';
            if($data[$item]){
                echo '<a href = "'.$data[$item].'"target=_blank>File</a>';
            }
            echo '</td>';
        }
        else if($types[$i]=="csv"){
            echo '<td>'.getLabel("$folder/$item.csv",$data["$item"]).'</td>';
        }else{
            echo '<td>'.$data["$item"].'</td>';
        }
        $i++;
    }
    echo '</thead></tr>';
    }
    $u++;
}
echo '</table>';
?>
<form enctype="multipart/form-data" method="POST" action="<?php echo $_SERVER['REQUEST_URI']?>">
<button type="submit" name="download" class="btn btn-success">Download</button>
</form>

<hr>
<h2>Format Manager</h2>
<p>Format data is available <a href = <?php echo $formatsheet?> target=_blank>here</a>. Please DO NOT change column order and number of columns. </p>
<form enctype="multipart/form-data" method="POST" action="<?php echo $_SERVER['REQUEST_URI']?>">
<?php
    $out="";
    $out.='<div class="form-group"><div class="row">';        
    $out.='<div class="col-xs-4"><label>Format file</label></div>';
    $out.='<div class="col-xs-6">';
    $out.='<input type="hidden"  class="form-control" name="MAX_FILE_SIZE" value="<?php echo 40*1024*1024?>" />';
    $out.='<label class="input-group-btn"><span class="btn btn-primary">Choose File<input type="file"  class="form-control" name="format" style="display:none" size=40></span></label><input type="text" class="form-control" readonly="">';
    $out.='</div>';
    $out.='</div></div>';
    $out.='<button type="submit" name="update" class="btn btn-success">Update</button><hr>';
    $cnt++;
    echo $out;

?>
    </form>

    <a href="home.php">Home</a>

</div></div>


</body>
</html>
