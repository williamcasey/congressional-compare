<?php


$json = file_get_contents("./legislators-current.json");


$data = json_decode($json, TRUE);


function bioguide($id) {
	if(strlen($id) == 7) {
		$alpha = substr($id, 0, 1);
		$numeric = substr($id, 1);
	} else {
		return FALSE;
	}
	return array(ord($alpha), $numeric);
}

function lis($id) {
	if(strlen($id) == 4) {
		$lis = (int)substr($id, -3);
	} else {
		return FALSE;
	}
	return $lis;
}

$c = 0;
for ($i=0; $i < count($data); $i++) { 

 	$current = end($data[$i]['terms']);

 	if($current['type'] == "sen") {
		
		$bioguide = bioguide($data[$i]['id']['bioguide']);
		$lis = lis($data[$i]['id']['lis']);

		echo "UPDATE `senators` SET `bioguide_id`=".$bioguide[1].",`bioguide_prefix`=".$bioguide[0]." WHERE `voter_id`=".$lis.";<br>";
		$c = $c + 1;
	}
 }

echo "<br>".$c." entries";



?>