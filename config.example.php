<?php

// FISHBOT CONFIGURATION

$config = array();

// bot's user info
$config['nick'] = "fishbot"; // The bot's nick
$config['ident'] = "fish"; // ident
$config['realname'] = "Teh f1shb0t <°)))))><"; // the bot's "real name"
$config['nspass'] = ""; // nickserv password, blank for none

// irc server to connect to
$config['server'] = "irc.interlinked.me";
$config['port'] = 6667;
$config['channels'] = array("#Sporks", "#fishbot"); // the channel names MUST be capitalised exactly as they appear when you /join (so #Sporks is correct, #sporks is not)

// database information for mysql
$config['db-host'] = "";
$config['db-username'] = "";
$config['db-password'] = "";
$config['db-name'] = "fishbot";

// other
$config['commandchar'] = ".";

?>
