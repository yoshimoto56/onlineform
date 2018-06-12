<?php
session_start();

include("common.inc");

if(!isset($_SESSION['user'])) {
  header("Location: index.php");
  exit;
}

// logout.php?logoutにアクセスしたユーザーをログアウトする
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
$items = getItems(0, $format);
$types = getItems(1, $format);
$segments = getItems(2, $format);
$titles = getItems(3, $format);
$placeholders = getItems(4, $format);


?>


<html lang="en">
<head>
<meta http-equiv="content-type" content="text/html; charset=ISO-8859-1">
<link rel="stylesheet" type="text/css" href="style.css">
<link href="//maxcdn.bootstrapcdn.com/bootstrap/3.3.6/css/bootstrap.min.css" rel="stylesheet">
</head>

<body>
<h1><?php echo $sys;?></h1>
<h2>List View</h2>

<?php

echo '<table border="1">';
    echo '<tr>';
    $i=0;
    foreach($items as $item){
        echo "<td>$titles[$i]</td>";
        $i++;
    }
    echo '</tr>';
$u = 0;
foreach($csv as $r){
    if($u>0){
    echo '<tr>';
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
    echo '</tr>';
    }
    $u++;
}
echo '</table>';

?>

<a href="home.php">Home</a>

</body>
</html>
