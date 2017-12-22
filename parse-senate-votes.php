<?php

/*
* parse-votes.php
*/

class parse_votes {

	//create vars for mysql connection
	var $mysql = NULL;
	var $err = NULL;

	//define fields in "votes" table
	var $votes_fields = array('congress', 'session', 'vote_id', 'category', 'requires', 'title', 'bill', 'sponsor', 'date', 'result');
	//define fields in "vote_values" table
	var $values_fields = array('congress', 'session', 'vote_id', 'senator', 'value');

	//create array to group all parsed metadata for insertion in one transaction
	var $meta_inserts = array();

	//on initialization connect to MySQL database using PDO
	function __construct($host, $user, $pass, $db, $charset="utf8mb4") {
		if(!$this->mysql = new PDO('mysql:host='.$host.';dbname='.$db.';charset='.$charset, $user, $pass)) {
			echo "Could not to the connect to the MySQL database";
			return FALSE;
		}
		$this->mysql->query('SET profiling = 1');
		return TRUE;
	}

	//function for timing MySQL queries I wrote for debugging
	function timing($total = FALSE) {
		$show = $this->mysql->query('show profiles');
		$result = $show->fetchAll(PDO::FETCH_ASSOC);
		if(!$total) {
			$last = array_values(array_slice($result, -1))[0];
			return $last['Duration'];
		} else {
			$overall = 0;
			foreach ($result as $q) {
				$overall = $overall + $q['Duration'];
			}
			return count($result)." queries executed in ".$overall." seconds.\n";
		}
	}

	//parses metadata for roll call votes from JSON files, which will then be inserted into database
	function parse_meta($json, $group=FALSE) {
		$tree = json_decode($json);

		$congress = $tree->{'congress'};
		//converts "session" string, which indicates the year, into an integer
		$session = (int)$tree->{'session'};
		$vote = $tree->{'number'};
		$category = $tree->{'category'};
		$requires = $tree->{'requires'};
		//sets two variable to their default NULL values 
		$bill = NULL;
		$sponsor = NULL;
		//determines vote type to get "title", in which the value is nested
	    if(isset($tree->{'bill'})) {
	    	$title = $tree->{'bill'}->{'title'};
	    	$bill = $tree->{'bill'}->{'number'};
	    } elseif(isset($tree->{'nomination'})) {
	    	$title = $tree->{'nomination'}->{'title'};
	    } elseif(isset($tree->{'treaty'})) {
	    	$title = $tree->{'treaty'}->{'title'};
	    } else {
	    	$title = NULL;
	    }
	    $date = $tree->{'date'};
	    $result = $tree->{'result_text'};

	    $meta = array('congress' => $congress, 'session' => $session, 'vote_id' => $vote, 'category' => $category, 'requires' => $requires, 'title' => $title, 'bill' => $bill, 'sponsor' => $sponsor, 'date' => $date, 'result' => $result);

	    if($group == TRUE) {
			$this->meta_inserts[] = $meta;
			return TRUE;
		}

		return $meta;
	}

	//parses data values for roll call votes from JSON files, which will then be inserted into database
	function parse_values($json, $group=FALSE) {
		$tree = json_decode($json);
		$votes = array();

		$congress = $tree->{'congress'};
		//converts "session" string, which indicates the year, into an integer
		$session = (int)$tree->{'session'};
		$vote = $tree->{'number'};

		//for every type of vote value (e.g. "Nay", "Yea"), add each vote value to main $vote array
		foreach ($tree->{'votes'}->{'Nay'} as $value) {
			if ($value != "VP") {
				$senator = (int)(substr($value->{'id'}, 1));
				$votes[] = array('congress' => $congress, 'session' => $session, 'vote_id' => $vote, 'senator' => $senator, 'value' => 0);
			}
		}
		foreach ($tree->{'votes'}->{'Yea'} as $value) {
			if ($value != "VP") {
				$senator = (int)(substr($value->{'id'}, 1));
				$votes[] = array('congress' => $congress, 'session' => $session, 'vote_id' => $vote, 'senator' => $senator, 'value' => 1);
			}
		}
		foreach ($tree->{'votes'}->{'Not Voting'} as $value) {
			$senator = (int)(substr($value->{'id'}, 1));
			$votes[] = array('congress' => $congress, 'session' => $session, 'vote_id' => $vote, 'senator' => $senator, 'value' => 2);
		}
		foreach ($tree->{'votes'}->{'Present'} as $value) {
			$senator = (int)(substr($value->{'id'}, 1));
			$votes[] = array('congress' => $congress, 'session' => $session, 'vote_id' => $vote, 'senator' => $senator, 'value' => 3);
		}

		return $votes;
	}

	//function to create placeholder values in sql queries for prepared statements
	function placeholders($text, $count=0){
	    $result = array();
	    if($count > 0){
	        for($x=0; $x<$count; $x++){
	            $result[] = $text;
	        }
	    }
	    return implode(",", $result);
	}

	//adds metadata to `votes` table in database
	function add_meta($meta) {
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
		$sql = "INSERT INTO `senate_votes` (`".implode("`,`", $this->votes_fields)."`) VALUES ".implode(',', $question_marks);
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
	function add_values($votes) {
		//begin mysql transaction
		$this->mysql->beginTransaction();
		$insert_values = array();
		foreach($votes as $v){
			//create blank prepared values for query
		    $question_marks[] = '('.$this->placeholders('?', sizeof($v)).')';
		    //add values to be inserted to array, and prevent any duplicates
		    $insert_values = array_merge($insert_values, array_values($v));
		}
		var_dump($insert_values);
		//create query
		$sql = "INSERT INTO `senate_vote_values` (`".implode("`,`", $this->values_fields)."`) VALUES ".implode(',', $question_marks);
		echo $sql;
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

//acccept argument from command line for the filepath to dir with JSON files
//$files = glob($argv[1]."*.json");
$files = "./115s112.json";

//fails and successes
$f = 0;
$s = 0;
//loop through all files, parse each vote's metdata, and insert vote values into db
//foreach($files as $j) {
	$json = file_get_contents($files);
	//echo $json;
	$add->parse_meta($json, TRUE);
	$votes = $add->parse_values($json);
	if($add->add_values($votes)) {
		$s = $s + 1;
	} else {
		$f = $f + 1;
	}
//}
//print successes and failures
echo $s." vote values from files successfully parsed and inserted. ".$f." files were not parsed and inserted.\n";

//insert vote metadata into "votes table"

if($add->add_meta($add->meta_inserts)) {
	echo "Vote metadata inserted correctly into database.\n";
} else {
	echo "Vote metadata not inserted correctly into database.\n";
}

echo $add->timing(TRUE);

?>