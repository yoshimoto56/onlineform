<?php
// Settings
$folder = "./files";
$conf = "Conference Name";
$sys = "Registraiton System";
$emailFrom = "user@example.com";
$cidSalt = "hogehoge";


// mbstring:TODO
mb_language("en");
mb_internal_encoding("UTF-8");

//-----------------------------------------------------------------------------
//	関数定義部。
  /**
     * ファイルポインタから行を取得し、CSVフィールドを処理する
     * @param resource handle
     * @param int length
     * @param string delimiter
     * @param string enclosure
     * @return ファイルの終端に達した場合を含み、エラー時にFALSEを返します。
     */

// Is there $user in the Database?
function is_user_exists($fname, $user){
    $csv = loadCsv($fname);
    $userPos = getPosFromKey("email", $csv);
    $i = 0;
    foreach($csv as $r){
        if($r[$userPos] == $user){
            return $i;
        }
        $i++;
    }
    return -1;
}

//
function fgetcsv_reg (&$handle, $length = null, $d = ',', $e = '"') {
    $d = preg_quote($d);
    $e = preg_quote($e);
    $_line = "";
    $eof = false;
    while (($eof != true)and(!feof($handle))) {
        $_line .= (empty($length) ? fgets($handle) : fgets($handle, $length));
        $itemcnt = preg_match_all('/'.$e.'/', $_line, $dummy);
        if ($itemcnt % 2 == 0) $eof = true;
    }
    $_csv_line = preg_replace('/(?:\\r\\n|[\\r\\n])?$/', $d, trim($_line));
    $_csv_pattern = '/('.$e.'[^'.$e.']*(?:'.$e.$e.'[^'.$e.']*)*'.$e.'|[^'.$d.']*)'.$d.'/';
    preg_match_all($_csv_pattern, $_csv_line, $_csv_matches);
    $_csv_data = $_csv_matches[1];
    for($_csv_i=0;$_csv_i<count($_csv_data);$_csv_i++){
        $_csv_data[$_csv_i]=preg_replace('/^'.$e.'(.*)'.$e.'$/s','$1',$_csv_data[$_csv_i]);
        $_csv_data[$_csv_i]=str_replace($e.$e, $e, $_csv_data[$_csv_i]);
    }
    return empty($_line) ? false : $_csv_data;
}

//	SJISのCsvをロードする。EUC-JPに変換して2次元配列にして返す。
function loadCsv($fname){
	$utf8 = @file_get_contents($fname);
	$buf = mb_convert_encoding($utf8, "EUC-JP", "UTF8");
	$fp = tmpfile();
	fwrite($fp, $buf);
	rewind($fp);
	$csv=null;
	while ($data = fgetcsv_reg($fp)) {
		$csv[] = $data;
	}
	return $csv;
}

//	2次元配列をSJISのCsvにセーブする。
function saveCsv($fname, $csv){
	$eucname = "$fname".".save.euc.csv";
	$fpSave = fopen($eucname, "w");
	foreach($csv as $r) fputcsv($fpSave, $r);
	fclose($fpSave);
	$save = mb_convert_encoding(file_get_contents($eucname), "UTF8", "EUC-JP");
	file_put_contents($fname, $save);
}

// rename file and upload
function upload($type){
	global $record, $folder;
	if (isset($_FILES[$type])){
		//	printArray($_FILES[$type]);
		$ext = strrchr($_FILES["$type"]["name"], ".");
		$base = "$folder/" . $record["oid"]. $type;
		$tmp = "$folder/" . "tmp_". $record["oid"]. $type;
		$name = $base . $ext;
		$old = glob("$base.*");
		foreach($old as $f) unlink($f);
		move_uploaded_file($_FILES["$type"]["tmp_name"], $tmp);
		if (file_exists($tmp)){
			rename($tmp, $name);
			return ( "files/".$record["oid"]. $type. $ext);
		}
	}
	return false;
}

//Linke for current file
function linkToFile($type){
	global $record, $folder;
	if (sizeof(glob("$folder/".$record["oid"].$type.".*"))>0){
		return '<a href="home.php?file='.$type.'">Current File</a>';
	}
}

//DEBUG: check array
function printArray($a){
	if (is_array($a)){
		foreach($a as $k=>$v){
			echo "$k ";
			printArray($v);
		}
	}else{
		echo " => $a<br>\n";
	}
}

//generate cid from oid
function getCid($oid){
	global $cidSalt;
	return md5($oid.$cidSalt);
}

//get position of key from csv
function getPosFromKey($key, $csv){
	if(!is_array(@$csv[0])) return -1;
	foreach($csv[0] as $k=>$v){
		if($v==$key) return $k;
	}
	return -1;
}

//get ordered item at line in csv
function getItems($line, $csv){
	if(!is_array(@$csv[0])) return -1;
        $i=0;
        $items=array();
	foreach($csv[$line] as $v){
		if($csv[2][$i]){
                        $items[]=$v;
                }
                $i++;
	}
	return $items;
}


//get record of cid
function getRecord($cid, $csv){
	$oidPos = getPosFromKey("oid", $csv);
	$record=NULL;
	$row=-1;
	if(!is_array(@$csv)) return NULL;
	foreach($csv as $key => $r){
		$c = getCid($r[$oidPos]);
		if ($c == $cid){
			if ($record){
				echo "FAITAL ERROR<br>";
				exit();
			}
			$record = $r;
			$row = $key;
		}
		//	次のコメントを解除すると、cidの一覧を表示します。
		//	echo $r[$oidPos]. " => $cid<br>\n";
	}
	if (!$record) return NULL;
	$rv = array();
	foreach($csv[0] as $k => $v){
		$rv[$v] = $record[$k];
	}
	$rv['row'] = $row;
	return $rv;
}

//save record
function saveRecord(&$csv, $record){
	$row = $record['row'];
	foreach($csv[0] as $col => $key){
		$csv[$row][$col] = $record[$key];
		//	echo "csv[$row][$col] = ". $record[$key]. "<br>";
	}
}

// Lock file
function lock(){
	global $fpLock, $folder;
	$fpLock = @fopen("$folder/lock.txt", "w");
	$count = 0;
	while(!$fpLock){
		//	echo 'lock() .. fopen("$folder/lock.txt", "w") failed.<br>\n';
		usleep(100*1000);
		$fpLock = @fopen("$folder/lock.txt", "w");
		$count ++;
		if ($count++ > 50){	//Wait for 5 sec
			?>
				<html>
				<meta http-equiv="content-type" content="text/html; charset=EUC-JP">
				<body>
					The server is busy.<br>
					Please wait a few second and reload or update the Web browser.
				</body>
				</html>
			<?php
			exit();
		}
	}
	flock($fpLock, LOCK_EX);
}

//Unlock file
function unlock(){
	global $fpLock;
	if ($fpLock){
		flock($fpLock, LOCK_UN);
		fclose($fpLock);
	}
	$fpLock = NULL;
}

//Get select option from csv file
function getOption($fname, $value){
    $body="";
    $csv = loadCsv($fname);
    $body.='<option value="00"';
    if(!isset($value)){
        $body.='is selected';
    }
    $body.='>NONE</option>';
    foreach($csv as $r){
        $body.='<option value="'.$r[0].'"';
        if($r[0] == $value){
            $body.=' selected';
        }
        $body.=">$r[1]</option>";
    }
    return $body;
}

function getLabel($fname, $value){
    $csv = loadCsv($fname);
    foreach($csv as $r){
        if($r[0] == $value){
            return $r[1];
        }
    }
    return -1;
}



//------------------------------------------------------------------------------
?>
