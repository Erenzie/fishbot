<?php
class quotes {
	function __construct($dbhost, $dbuser, $dbpass, $dbname) {
		$mysqlconn = mysql_connect($dbhost, $dbuser, $dbpass);
		if(!$mysqlconn) {
			die("Couldn't connect to the MySQL server. :(");
		}
		mysql_select_db($dbname);
		$this->numQuotes = mysql_num_rows(mysql_query("SELECT id FROM quotes"));
	}
	
	function reconnect($dbhost, $dbuser, $dbpass, $dbname) {
		$mysqlconn = mysql_connect($dbhost, $dbuser, $dbpass);
		if(!$mysqlconn) {
			die("Couldn't connect to the MySQL server. :(");
		}
		mysql_select_db($dbname);
		$this->numQuotes = mysql_num_rows(mysql_query("SELECT id FROM quotes"));
	}
	
	function get($id = "") {
		// Get a certain quote ID.
		// If no ID specified, get a random quote
		if($id != "") {
			$id = mysql_real_escape_string($id);
		} else  {
			// doing it this way will on occasion result in an id that doesn't exist
			// oh well
			$mostrecent = mysql_query("SELECT id FROM quotes ORDER BY id DESC LIMIT 1");
			$mostrecent = mysql_fetch_array($mostrecent);
			$id = rand(1, $mostrecent['id']);
		}
		$quote = mysql_query("SELECT * FROM quotes WHERE id='$id'");
		if(mysql_num_rows($quote) == 0) {
			return false;
		} else {
			$quote = mysql_fetch_array($quote);
			return $quote;
		}
	}
	
	function getQuotesThatContain($blah) {
		$quotes = mysql_query("SELECT * FROM quotes WHERE quote LIKE '%$blah%' ORDER BY id DESC");
		if(mysql_num_rows($quotes) == 0) {
			return false;
		} else {
			return $quotes;
		}
	}
	
	function checkIfExists($id) {
		$quote = mysql_query("SELECT id FROM quotes WHERE id='$id'");
		$quote = mysql_num_rows($quote);
		return $quote;
	}
	
	function getAdder($id) {
		$adder = mysql_query("SELECT adder FROM quotes WHERE id='$id'");
		if($adder) {
			return $adder;
		} else {
			return false;
		}
	}
	
	function add($content, $adder, $channel, $network) {
		$origcontent = $content;
		$origadder = $adder;
		$origchannel = $channel;
		$orignetwork = $network;
		$content = mysql_real_escape_string($content);
		$adder = mysql_real_escape_string($adder);
		$channel = mysql_real_escape_string($channel);
		$network = mysql_real_escape_string($network);
		$time = time();
		// now add the quote
		$added = mysql_query("INSERT INTO quotes(quote, adder, added, channel, network) VALUES('{$content}', '{$adder}', '{$time}', '{$channel}', '{$network}')");
		if($added) {
			// get the id of the quote
			$quoteid = mysql_query("SELECT id FROM quotes WHERE quote='$content' AND adder='$adder' AND added='{$time}'");
			$quoteid = mysql_fetch_array($quoteid);
			$quoteid = $quoteid['id'];
			return $quoteid;
		} else {
			// stupid mysql disconnections... >:|
			return false;
		}
	}
	
	function delete($id) {
		$deleted = mysql_query("DELETE FROM quotes WHERE id='$id'");
		if($deleted) {
			return true;
		} else {
			return false;
		}
	}
}
?>
