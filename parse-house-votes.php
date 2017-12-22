<?php

ini_set('memory_limit', '2048M');
ini_set('max_execution_time', 800);

$startMemory = memory_get_usage();

class parse_votes {

	//create vars for mysql connection
	var $mysql = NULL;
	var $err = NULL;

	//define fields in "votes" table
	var $votes_fields = array('congress', 'session', 'vote_id', 'category', 'requires', 'question', 'bill', 'sponsor', 'subject', 'date', 'result');
	//define fields in "vote_values" table
	var $values_fields = array('congress', 'session', 'vote_id', 'bioguide_id', 'bioguide_prefix', 'value');

	//create array to group all parsed metadata for insertion in one transaction
	var $meta_inserts = array();
	var $val_inserts = array();

	var $val_count = 0;

	//on initialization connect to MySQL database using PDO
	function __construct($host, $user, $pass, $db, $charset="utf8mb4") {
		if(!$this->mysql = new PDO('mysql:host='.$host.';dbname='.$db.';charset='.$charset, $user, $pass)) {
			echo "Could not to the connect to the MySQL database";
			return FALSE;
		}
		return TRUE;
	}

	//parses bioguide ids
	private function bioguide($id) {
		if(strlen($id) == 7) {
			$alpha = substr($id, 0, 1);
			$numeric = substr($id, 1);
			$alpha = ord($alpha);
			return array($alpha, $numeric);
		} else {
			return FALSE;
		}
	}

	//parses metadata for roll call votes from JSON files, which will then be inserted into database
	public function parse_meta($json, $group=FALSE) {
		$tree = json_decode($json);

		$congress = $tree->{'congress'};
		//converts "session" string, which indicates the year, into an integer
		$session = (int)$tree->{'session'};
		$vote = $tree->{'number'};
		$category = $tree->{'category'};
		$requires = $tree->{'requires'};
		$question = $tree->{'question'};
		//sets variable to their default NULL values 
		$bill = NULL;
		$sponsor = NULL;
		$subject = NULL;
		//if vote is on a bill, then set the variable to the bill number
	    if(isset($tree->{'bill'})) {
	    	$bill = $tree->{'bill'}->{'number'};
	    } 

	    $date = $tree->{'date'};
	    $result = $tree->{'result_text'};

	    $meta = array('congress' => $congress, 'session' => $session, 'vote_id' => $vote, 'category' => $category, 'requires' => $requires, 'question' => $question, 'bill' => $bill, 'sponsor' => $sponsor, 'subject' => $subject, 'date' => $date, 'result' => $result);

	    if($group == TRUE) {
			$this->meta_inserts[] = $meta;
			return TRUE;
		}

		return $meta;
	}

	public function vote_keys($votes) {
		$out = array();
		if(isset($votes['No'])) {
			$out[0] = 'No';
		} elseif(isset($votes['Nay'])) {
			$out[0] = 'Nay';
		} else { 
			return FALSE; 
		}
		if(isset($votes['Aye'])) {
			$out[1] = 'Aye';
		} elseif(isset($votes['Yea'])) {
			$out[1] = 'Yea';
		} else { 
			return FALSE; 
		}
		return $out;
	}

	//parses data values for roll call votes from JSON files, which will then be inserted into database
	public function parse_values($json, $group=FALSE) {
		$tree = json_decode($json, TRUE);
		$votes = array();

		$congress = $tree['congress'];
		//converts "session" string, which indicates the year, into an integer
		$session = (int)$tree['session'];
		$vote = $tree['number'];

		//for every type of vote value (e.g. "Nay", "Yea"), add each vote value to main $vote array

		$keys = $this->vote_keys($tree['votes']);

		if($keys == FALSE) {
			return FALSE;
		}

		//if($congress != 115) {
			foreach ($tree['votes'][$keys[0]] as $value) {
				if ($value != "VP") {
					$bio_id = $this->bioguide($value['id']);
					$votes[] = array('congress' => $congress, 'session' => $session, 'vote_id' => $vote, 'bioguide_id' => $bio_id[1], 'bioguide_prefix' => $bio_id[0], 'value' => 0);
					$this->val_count = $this->val_count + 1;
				}
			}
			foreach ($tree['votes'][$keys[1]] as $value) {
				if ($value != "VP") {
					$bio_id = $this->bioguide($value['id']);
					$votes[] = array('congress' => $congress, 'session' => $session, 'vote_id' => $vote, 'bioguide_id' => $bio_id[1], 'bioguide_prefix' => $bio_id[0], 'value' => 1);
					$this->val_count = $this->val_count + 1;
				}
			}
		//} else {
			//return TRUE;
		//}
		foreach ($tree['votes']['Not Voting'] as $value) {
			$bio_id = $this->bioguide($value['id']);
			$votes[] = array('congress' => $congress, 'session' => $session, 'vote_id' => $vote, 'bioguide_id' => $bio_id[1], 'bioguide_prefix' => $bio_id[0], 'value' => 2);
			$this->val_count = $this->val_count + 1;
		}
		foreach ($tree['votes']['Present'] as $value) {
			$bio_id = $this->bioguide($value['id']);
			$votes[] = array('congress' => $congress, 'session' => $session, 'vote_id' => $vote, 'bioguide_id' => $bio_id[1], 'bioguide_prefix' => $bio_id[0], 'value' => 3);
			$this->val_count = $this->val_count + 1;
		}

		if($group == TRUE) {
			$this->val_inserts[] = $votes;
			return TRUE;
		}

		return $votes;
	}

	//function to create placeholder values in sql queries for prepared statements
	private function placeholders($text, $count=0){
	    $result = array();
	    if($count > 0){
	        for($x=0; $x<$count; $x++){
	            $result[] = $text;
	        }
	    }
	    return implode(",", $result);
	}

	//adds metadata to `votes` table in database
	public function add_meta($meta) {
		//begin mysql transaction
		$this->mysql->beginTransaction();
		$insert_values = array();
		foreach($meta as $m){
			//create blank prepared values for query
		    $question_marks[] = '('.$this->placeholders('?', sizeof($m)).')';
		    //add values to be inserted to array, and prevent any duplicates
		    $insert_values = array_merge($insert_values, array_values($m));
		}
		//create query
		$sql = "INSERT INTO `house_votes` (`".implode("`,`", $this->votes_fields)."`) VALUES ".implode(',', $question_marks);
		//create prepared insert statement from the query
		$stmt = $this->mysql->prepare($sql);
		//execute prepared statement and output any errors
		try {
		    $stmt->execute($insert_values);
		} catch (PDOException $e) {
	    	throw $e;
	   	} 
		//end transaction
		if($this->mysql->commit()) {
			return TRUE;
		} else {
			return FALSE;
		}
	}

	//adds vote data values to `vote_values` table
	public function add_values($votes) {
		//begin mysql transaction
		$this->mysql->beginTransaction();
		$insert_values = array();
		foreach($votes as $v){
			//create blank prepared values for query
		    $question_marks[] = '('.$this->placeholders('?', sizeof($v)).')';
		    //add values to be inserted to array, and prevent any duplicates
		    $insert_values = array_merge($insert_values, array_values($v));
		}
		//create query
		$sql = "INSERT INTO `house_vote_values` (`".implode("`,`", $this->values_fields)."`) VALUES ".implode(',', $question_marks);
		//create prepared insert statement from the query
		$stmt = $this->mysql->prepare($sql);
		//execute prepared statement and output any errors
		try {
		    $stmt->execute($insert_values);
		} catch (PDOException $e) {
	    	throw $e;
	   	} 
		//end transaction
		if($this->mysql->commit()) {
			return TRUE;
		} else {
			return FALSE;
		}
	}

}

$add = new parse_votes("localhost", "root", "", "congress");

$list = file_get_contents('./house-files');
$files = explode(PHP_EOL, $list);

//$files = array("./house/110/2007/h1013/data.json", "./house/111/2009/h418/data.json", "./house/112/2011/h546/data.json", "./house/113/2013/h123/data.json", "./house/114/2015/h344/data.json", "./house/115/h94/data.json");



$fp = fopen('./house-values-complete', 'w');

$s = 0;
$f = 0;

foreach($files as $file) {
	$json = file_get_contents($file);
	$tree = json_decode($json, TRUE);
	//var_dump(array_keys($tree['votes']));
	//var_dump($add->vote_keys($tree['votes']));
	$vals = $add->parse_values($json);
	if($vals != FALSE) {
		foreach($vals as $item) {
			fwrite($fp, implode(",", $item)."\r\n");
		}
	} else {
		$f = $f + 1;
	}
}

fclose($fp);

//var_dump($add->val_inserts);

/*
$megabyte = 1024 * 1024;

$size1 = count($add->val_inserts);
$size2 = count($add->val_inserts, COUNT_RECURSIVE);

$mem = memory_get_usage() - $startMemory;

echo "\n# of files that couldn't be parsed: ".$f."\n";
echo "# of files successfully parsed: ".$s."\n\n";

echo "# of elements on first level of array: ".$size1."\n";
echo "# of elements cumulatively in the array: ".$size2."\n\n";
echo "Approximate size of array: ".round($mem / $megabyte, 2)." MB\n";
echo "Size in bytes: ".$mem."\n\n";



echo "Total # of vote values parsed: ".$add->val_count."\n\n";
*/


/*
foreach ($files as $file) {
	$json = file_get_contents($file);
	$meta = $add->parse_meta($json, TRUE);
}

echo "\n# of votes successfully parsed: ".count($add->meta_inserts)."\n";

if($add->add_meta($add->meta_inserts)) {
	echo "Votes successfully inserted into database.\n\n";
} else {
	echo "Errors occured when inserting votes into database.\n\n";
}
*/

$time = round(microtime(true) - $_SERVER["REQUEST_TIME_FLOAT"], 2);
echo "Process Time: {$time} seconds\n";




?>