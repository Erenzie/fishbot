<?php
class fishbot {
    var $conn = null;
    var $prefixes = array("!", "~", "&", "@", "%", "+");

    function connect($server, $port, $nick, $realname, $ident, $nspass = "") {
        $this->server = $server;
        $this->port = $port;
        $this->botnick = $nick;
        $this->realname = $realname;
        $this->ident = $ident;
        $this->nspass = $nspass;
        echo "Connecting to $server port $port, with the nick $nick.\n";
        $this->conn = fsockopen($server, $port, $errnr, $errstr);
        if (!$this->conn) { // check if we successfully connected
            die ("Couldn't connect. Error: $errnr - $errstr\n");
        }
        // wait a second then throw the user info at the server
        sleep(1);
        $this->sendRaw("NICK {$this->botnick}");
        $this->sendRaw("USER {$this->ident} {$this->server} blah :{$this->realname}");

        // nick lists for each channel
        $this->nicklist = array();
    }

    function sendRaw($text) {
        if (!$this->conn) {
            echo "Why are you trying to sendRaw() when you're not connected?\n";
        } else {
            //echo "SENDING: $text\n";
            fputs($this->conn, "$text\r\n");
        }
    }

    function recievingData() {
        // checks if data's being recieved over the socket.
        if (!feof($this->conn)) {
            return true;
        } else {
            return false;
        }
    }

    function getData() {
        // throws the data that's been recieved into $this->raw, and a few other vars. also, pongs back to the server and a few other things
        $this->raw = fgets($this->conn);
        if (!empty($this->raw)) {
            $this->raw = substr($this->raw, 0, -2);
            $this->args = explode(" ", $this->raw);
            $this->rawcmd = trim($this->args[1]);
            if ($this->rawcmd == "") {
                $this->rawcmd = trim($this->args[0]);
            }
            //echo "RECIEVED: {$this->raw}\n";

            if ($this->args[0] == "PING") {
                $this->sendRaw("PONG {$this->rawcmd}");
            } elseif ($this->rawcmd == "376") {
                // joins channels after the end of the motd
                if ($this->nspass != "") {
                    $this->nsIdentify($this->nspass);
                    sleep(1);
                    $this->nspass = "";
                    $config['nspass'] = "";
                }
                foreach ($this->channels as $channel) {
                    $this->joinChan($channel);
                }
            }

            $this->chan = $this->args[2];
            $this->origcmd = substr($this->args[3], 1);

            $this->hostmask = $this->args[0];
            $this->hostmask = substr($this->hostmask, 1);
            $this->nick = explode("!", $this->hostmask);
            $this->hostname = $this->nick[1];
            $this->fullhostname = $this->hostname;
            $this->hostname = explode("@", $this->hostname);
            $this->hostname = $this->hostname[1];
            $this->nick = $this->nick[0];

            $blargag = ":{$this->hostmask} PRIVMSG {$this->chan} :";
            $this->msg = substr($this->raw, strlen($blargag));

            // check if fishbot was kicked
            if ($this->rawcmd == "KICK" && $this->args[3] == $this->botnick) {
                $this->joinChan($this->chan);
            }

            // check for CTCP VERSION
            if ($this->rawcmd == "PRIVMSG" && $this->msg == "\001VERSION\001") {
                $this->sndNotice($this->nick, "fishbot by Eren - http://erenzie.ez.lv/?fishbot | http://fish.go.woo.mooo.com:1010/");
            }

            // check for a user command, and if there is one, set the variables appropriately
            if (substr($this->origcmd, 0, 1) == $this->commandchar) {
                $this->cmd = substr($this->origcmd, 1);
                $blargag = ":{$this->hostmask} PRIVMSG {$this->chan} :,{$this->cmd} ";
                $this->allargs = substr($this->raw, strlen($blargag));
            }

            // check for NAMES response
            /* :routemaster.interlinked.me 353 Missingno2 = #Sporks :@nick1 +nick2 nick3 etc
               :routemaster.interlinked.me 366 Missingno2 #Sporks :End of /NAMES list. */
            if ($this->rawcmd == "353") {
                print_r($this->args);
                $chan = $this->args[4];
                echo "chan is {$chan}\n";
                $blargag = ":{$this->hostmask} 353 {$this->botnick} = {$chan} ";
                $nicks = substr($this->raw, strlen($blargag) + 1);
                echo "nicks are: {$nicks}\n";
                $nicksa = explode(" ", $nicks);
                print_r($nicksa);
                foreach ($nicksa as $nick) {
                    // check for @ and + (other shitty modes may be added if necessary)
                    while (in_array(substr($nick, 0, 1), $this->prefixes)) {
                        $nick = substr($nick, 1);
                    }
                    array_push($this->nicklist[$chan], $nick);
                }
            }

            // this used to actually log to files
            // but now it actually only says what it would log to stdout
            // this also handles keeping track of channel nicklists
            // it did log pretty much the same way as irssi tho so you could use stuff like psig on it
            if ($this->log) {
                // check the type of command
                if ($this->rawcmd == "PRIVMSG") {
                    if (substr($this->msg, 0, 8) == "\001ACTION ") {
                        $newmsg = substr($this->msg, 8);
                        $newmsg = substr($newmsg, 0, -1);
                        $this->logLine($this->chan, "* {$this->nick} $newmsg");
                    } else {
                        $this->logLine($this->chan, "< {$this->nick}> {$this->msg}");
                    }
                } elseif ($this->rawcmd == "JOIN") {
                    // :blah JOIN :#channel
                    $logfriendlyhostname = substr($this->hostmask, strlen($this->nick) + 1);
                    $this->logLine($this->chan, "-!- {$this->nick} [{$logfriendlyhostname}] has joined {$this->chan}");

                    // is the bot joining? if so, get nick list...
                    if ($this->nick == $this->botnick) {
                        //$this->sendRaw("NAMES {$this->chan}");
                    } else {
                        // add them to the nick list
                        array_push($this->nicklist[$this->chan], $this->nick);
                    }
                } elseif ($this->rawcmd == "PART") {
                    // :blah PART #channel :reason
                    $logfriendlyhostname = substr($this->hostmask, strlen($this->nick) + 1);
                    $blargag = ":{$this->hostmask} PART {$this->chan} :";
                    $partreason = substr($this->raw, strlen($blargag));
                    $this->logLine($this->chan, "-!- {$this->nick} [{$logfriendlyhostname}] has left {$this->chan} [{$partreason}]");

                    // remove from nick list...
                    $key = array_search($this->nick, $this->nicklist[$this->chan]);
                    unset($this->nicklist[$this->chan][$key]);
                } elseif ($this->rawcmd == "QUIT") {
                    // :blah QUIT :reason
                    $logfriendlyhostname = substr($this->hostmask, strlen($this->nick) + 1);
                    $blargag = ":{$this->hostmask} QUIT :";
                    $quitreason = substr($this->raw, strlen($blargag));
                    /*foreach($this->logfiles as $logfile) {
                        // this one has to be done manually because $this->logLine assumes you want $this->logfiles[BLAH]
                        // and I don't want to bother to keep a list of the people in each channel and only shove the quit line in the logs for the channels the user was in.
                        fwrite($logfile, date("H:i")." -!- {$this->nick} [{$logfriendlyhostname}] has quit [{$quitreason}]\n");
                    }*/

                    echo "-!- {$this->nick} [{$logfriendlyhostname}] has quit [{$quitreason}]\n";

                    // remove from nick list...
                    $channels = array_keys($this->nicklist);
                    foreach ($channels as $chan) {
                        $key = array_search($this->nick, $this->nicklist[$chan]);
                        if ($key !== false) {
                            unset($this->nicklist[$chan][$key]);
                        }
                    }
                } elseif ($this->rawcmd == "MODE") {
                    // :blah MODE #channel +whatever
                    $blargag = ":{$this->hostmask} MODE {$this->chan} ";
                    $modes = substr($this->raw, strlen($blargag));
                    $this->logLine($this->chan, "-!- mode/{$this->chan} [{$modes}] by {$this->nick}");
                } elseif ($this->rawcmd == "KICK") {
                    // :blah KICK #channel user :reason
                    $blargag = ":{$this->hostmask} KICK {$this->chan} {$this->args[3]} :";
                    $reason = substr($this->raw, strlen($blargag));
                    $this->logLine($this->chan, "-!- {$this->args[3]} has been kicked from {$this->chan} by {$this->nick} [{$reason}]");

                    $key = array_search($this->args[3], $this->nicklist[$this->chan]);
                    unset($this->nicklist[$this->chan][$key]);
                } elseif ($this->rawcmd == "NICK") {
                    // :blah NICK blah
                    $newnick = substr($this->chan, 1);
                    /*foreach($this->logfiles as $logfile) {
                        // read what I said for quit. :P
                        fwrite($logfile, date("H:i")." -!- {$this->nick} is now known as {$newnick}\n");
                    }*/
                    $this->logLine($this->chan, "-!- {$this->nick} is now known as {$newnick}\n");

                    // update nick list
                    $channels = array_keys($this->nicklist);
                    foreach ($channels as $chan) {
                        $key = array_search($this->nick, $this->nicklist[$chan]);
                        if ($key !== false) {
                            unset($this->nicklist[$chan][$key]);
                            $this->nicklist[$chan][] = $newnick;
                        }
                    }
                } elseif ($this->rawcmd == "TOPIC") {
                    // :blah TOPIC #chan :newtopic
                    $blargag = ":{$this->hostmask} TOPIC {$this->chan} :";
                    $topic = substr($this->raw, strlen($blargag));
                    $this->logLine($this->chan, "-!- {$this->nick} changed the topic of {$this->chan} to: {$topic}");
                }
            }
        }
    }

    function sndMsg($target, $text) {
        $this->sendRaw("PRIVMSG $target :$text");
        //echo "<{$this->botnick} | {$target}> {$text}";
        //fwrite($this->logfiles[$target], date("H:i")." < {$this->botnick}> {$text}\n");
        if (substr($text, 0, 8) == "\001ACTION ") {
            $newmsg = substr($text, 8);
            $newmsg = substr($newmsg, 0, -1);
            $this->logLine($target, "* {$this->botnick} $newmsg");
        } else {
            $this->logLine($target, "< {$this->botnick}> {$text}"); // note to self: $this->msg != $text
        }
    }

    function sndNotice($target, $text) {
        // sends a notice to $target
        $this->sendRaw("NOTICE {$target} :{$text}");
        $this->logLine($target, "Notice to {$target}: {$text}");
    }

    function joinChan($channel, $key = "") {
        if ($key == "") {
            $this->sendRaw("JOIN :$channel");
        } else {
            $this->sendRaw("JOIN :$channel $key");
        }
        //$this->logfiles[$chan] = fopen($filename."$chan.txt", "a");

        $this->nicklist[$channel] = array(); // to be populated in getData()
    }

    function partChan($channel) {
        $this->sendRaw("PART $channel");
    }

    function nsIdentify($password) {
        $this->sndMsg("NickServ", "identify $password");
        echo "Identified to NickServ\n";
    }

    function hasPerm($permtocheckfor, $reply = 1, $host = false) {
        // depends on having made a mysql connection, most likely through the quotes class
        // because i'm fucking dumb
        if (!$host) {
            $perms = mysql_query("SELECT * FROM users WHERE hostname='{$this->fullhostname}'");
        } else {
            $perms = mysql_query("SELECT * FROM users WHERE hostname='{$host}'");
        }
        if (mysql_num_rows($perms) == 0) {
            if ($reply) {
                $this->sndMsg($this->chan, "{$this->nick}: You don't have the $permtocheckfor permission.");
            } else {
                return false;
            }
        } else {
            $perms = mysql_fetch_array($perms);
            $perms = explode(" ", $perms['permissions']);
            if ($permtocheckfor == "ignore" && in_array("admin", $perms)) {
                return false;
            }
            if (in_array($permtocheckfor, $perms) || in_array("admin", $perms)) {
                return true;
            } else {
                if ($reply) {
                    $this->sndMsg($this->chan, "{$this->nick}: You don't have the $permtocheckfor permission.");
                } else {
                    return false;
                }
            }
        }
    }

    function logTo($filename) {
        $this->log = true;
        $this->logfile = $filename;
        //foreach($this->channels as $chan) {
        //$this->logfiles[$chan] = fopen($filename."$chan.txt", "a");
        //}
        //echo "Logging enabled.";
    }

    function logLine($chan, $text) {
        //fwrite($this->logfiles[$chan], date("H:i")." {$text}\n");
        echo "\033[1m{$chan}:\033[0m {$text}\n";
    }

    function changeNick($to) {
        $this->sendRaw("NICK $to");
        $this->botnick = $to;
    }

    function getRandChanMember($chan) {
        return $this->nicklist[$chan][array_rand($this->nicklist[$chan])];
    }
    
    function checkUserInChan($user, $chan) {
		if(in_array($user, $this->nicklist[$chan])) {
			return true;
		} else {
			return false;
		}
	}
}

function pointlessErrorHandlerThingy($errno, $errstr, $errfile, $errline) { // no more annoying "undefined offset" errors! :P
    if ($errno == 8) {
        // do nothing
    } else {
        // FUCK ASCII COLOUR CODES
        echo "\033[1;31;40mError:\033[0;37;40m ({$errno}) - {$errstr} \033[1;37;40m(at line {$errline} in {$errfile})\033[0;37;40m\n";
    }
}

set_error_handler("pointlessErrorHandlerThingy");
?>
