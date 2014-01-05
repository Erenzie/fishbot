<?php
require("config.php");
require("fishbot.class.php");
require("quotes.class.php");

require("bdBattle.class.php");
require("bdBattle.idk.php");

$fb = new fishbot;
$bat = new bdBattle();

$fb->channels = $config['channels'];
$fb->commandchar = $config['commandchar'];

$quotes = new quotes($config['db-host'], $config['db-username'], $config['db-password'], $config['db-name']); // paramaters in order: database host, user, password, and database name

$fb->logTo("logs/"); // doesn't actually log anymore, just prints what it /would/ log to stdout. i need to update this.

$fb->connect($config['server'], $config['port'], $config['nick'], $config['realname'], $config['ident'], $config['nspass']);

$lolfine = array();

while ($fb->recievingData()) {
    $fb->getData();
    // make sure this is a privmsg :p
    if ($fb->rawcmd == "PRIVMSG") {
        // Use the switch for normal commands, and the (else)if in the default statement of the switch for everything else
        switch ($fb->cmd) {
            case "test":
                $fb->sndMsg($fb->chan, "test");
                break;
            case "quote":
                $id = $fb->args[4];
                if ($id == "9001") {
                    $fb->sndMsg($fb->chan, "Quote 9001 - <Chazz> IT'S OVER 9000! :D :DDDDDDDDDDDDDDDDDDDDDDDDDDDDDDDDDDDDDDDDDDDDDDDDDDDDDDDDDDDDDDDDDDDDDDDDDDDDDD!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!11");
                } else {
                    $quote = $quotes->get($id);
                    if (!$quote) {
                        $fb->sndMsg($fb->chan, "Quote $id doesn't exist :(");
                    } else {
                        $fb->sndMsg($fb->chan, "Quote {$quote['id']} - {$quote['quote']}");
                    }
                }
                break;
            case "addquote":
                $id = $quotes->add($fb->allargs, $fb->nick, $fb->chan);
                if ($id) {
                    $fb->sndMsg($fb->chan, "Quote $id successfully added.");
                } else {
                    $fb->sndMsg($fb->chan, "Something went wrong when adding the quote :(");
                }
                break;
            case "delquote":
                $id = mysql_real_escape_string($fb->args[4]);
                // check if the quote being deleted exists
                if (!$quotes->checkIfExists($id)) {
                    $fb->sndMsg($fb->chan, "The quote you are trying to delete doesn't exist...how are you going to delete something that doesn't exist? :P");
                } else {
                    // check if whoever's trying to delete the quote added it or is a bot admin...
                    $adder = $quotes->getAdder($id);
                    if ($fb->nick == $adder || $fb->hasPerm("delquotes", 0)) {
                        // delete the quote
                        if ($quotes->delete($id)) {
                            $fb->sndMsg($fb->chan, "Quote $id successfully deleted.");
                        } else {
                            $fb->sndMsg($fb->chan, "Something went wrong when deleting the quote. :/");
                        }
                    } else {
                        $fb->sndMsg($fb->chan, "You didn't add that quote, so nein.");
                    }
                }
                break;
            case "quotesearch":
                // check if ignored
                if (!$fb->hasPerm("ignore", 0)) {
                    $searchcrap = $fb->allargs;
                    if ($searchcrap == "" || strlen($searchcrap) == 1 || $searchcrap == "the") {
                        $fb->sndMsg($fb->chan, "nein");
                    } else {
                        // search for quotes! :D
                        $searchcrap = mysql_real_escape_string($searchcrap);
                        $results = $quotes->getQuotesThatContain($searchcrap);
                        $numresults = mysql_num_rows($results); // slightly redundant because getQuotesThatContain already does mysql_num_rows...but doesn't put the result in a var
                        if ($numresults > 0) {
                            if ($numresults == 1) {
                                $fb->sndMsg($fb->chan, "There is 1 quote containing \"{$searchcrap}\".");
                            } else {
                                $fb->sndMsg($fb->chan, "There are " . mysql_num_rows($results) . " quotes containing \"{$searchcrap}\".");
                            }
                            $shownquotes = 0;
                            if ($numresults >= 4) {
                                while ($shownquotes != 4) {
                                    $quote = mysql_fetch_array($results);
                                    //$quote['quote'] = str_replace($searchcrap, "\x02$searchcrap\x02", $quote['quote']);
                                    $quote['quote'] = preg_replace("/(" . $searchcrap . ")/i", "\x02$1\x02", $quote['quote']);
                                    $fb->sndMsg($fb->chan, "Quote {$quote['id']} - {$quote['quote']}");
                                    sleep(1);
                                    $shownquotes++;
                                }
                                $totalshownquotes = 4;
                                $shownquotes = 0;
                                $tharismoar = 1;
                                if ($numresults != 4) {
                                    $fb->sndMsg($fb->chan, "Say \".more\" for more results.");
                                }
                            } else {
                                while ($shownquotes != $numresults) {
                                    $quote = mysql_fetch_array($results);
                                    //$quote['quote'] = str_replace($searchcrap, "\x02$searchcrap\x02", $quote['quote']);
                                    $quote['quote'] = preg_replace("/(" . $searchcrap . ")/i", "\x02$1\x02", $quote['quote']);
                                    $fb->sndMsg($fb->chan, "Quote {$quote['id']} - {$quote['quote']}");
                                    $shownquotes++;
                                }
                                $totalshownquotes = 0;
                                $shownquotes = 0;
                                $tharismoar = 0;
                            }
                        } else {
                            $fb->sndMsg($fb->chan, "There are no quotes containing \"{$searchcrap}\".");
                        }
                    }
                }
                break;
            case "keldair":
                // i honestly have no clue what this was for
                // i think it was for debugging something
                $quotes = $quotes->getQuotesThatContain("_");
                $numresults = mysql_num_rows($quotes);
                $shownquotes = 0;
                while ($shownquotes != $numresults) {
                    $quote = mysql_fetch_array($quotes);
                    $fb->sndMsg($fb->chan, $quote['quote']);
                    $shownquotes++;
                    sleep(1);
                }
                break;
            case "more":
                if ($tharismoar) {
                    if ($numresults - $totalshownquotes > 4) {
                        while ($shownquotes != 4) {
                            $quote = mysql_fetch_array($results);
                            //$quote['quote'] = str_replace($searchcrap, "\x02$searchcrap\x02", $quote['quote']);
                            $quote['quote'] = preg_replace("/(" . $searchcrap . ")/i", "\x02$1\x02", $quote['quote']);
                            $fb->sndMsg($fb->chan, "Quote {$quote['id']} - {$quote['quote']}");
                            sleep(1);
                            $shownquotes++;
                        }
                        $tharismoar = 1;
                        $shownquotes = 0;
                        $totalshownquotes = $totalshownquotes + 4;
                        $fb->sndMsg($fb->chan, "Say .more again for EVEN MOAR QUOTE SPAMMING FUN! :D");
                    } else {
                        $whatsleft = $numresults - $totalshownquotes;
                        echo "there are $whatsleft quotes left to throw to the channel\n";
                        while ($shownquotes != $whatsleft) {
                            $quote = mysql_fetch_array($results);
                            //$quote['quote'] = str_replace($searchcrap, "\x02$searchcrap\x02", $quote['quote']);
                            $quote['quote'] = preg_replace("/(" . $searchcrap . ")/i", "\x02$1\x02", $quote['quote']);
                            $fb->sndMsg($fb->chan, "Quote {$quote['id']} - {$quote['quote']}");
                            sleep(1);
                            $shownquotes++;
                        }
                        $tharismoar = 0;
                        $shownquotes = 0;
                        $totalshownquotes = 0;
                    }
                } else {
                    // there is no more to be thrown to the channel
                    $fb->sndMsg($fb->chan, "There's no more to tell you");
                }
                break;
            case "quit":
                if ($fb->hasPerm("admin")) {
                    $fb->sendRaw("QUIT :kthxbai");
                    die();
                }
                break;
            case "join":
                if ($fb->hasPerm("admin")) {
                    //$fb->sendRaw("JOIN :{$fb->allargs}");
                    $fb->joinChan($fb->allargs);
                }
                break;
            case "part":
                if ($fb->hasPerm("admin")) {
                    $fb->partChan($fb->allargs);
                }
                break;
            case "nick":
                if ($fb->hasPerm("admin")) {
                    $fb->changeNick($fb->allargs);
                }
                break;
            case "mysqlreconnect":
                mysql_connect($config['db-host'], $config['db-username'], $config['db-password']);
                mysql_select_db($config['db-name']);
                $quotes->numQuotes = mysql_num_rows(mysql_query("SELECT id FROM quotes"));
                $fb->sndMsg($fb->chan, "done");
                break;
            case "eval":
                if ($fb->hasPerm("admin")) {
                    eval($fb->allargs);
                }
                break;
            case "say":
                if ($fb->hasPerm("admin")) {
                    $chan = $fb->args[4];
                    $whattosay = substr($fb->allargs, strlen($fb->args[4]) + 1);
                    $fb->sndMsg($chan, $whattosay);
                }
                break;
            case "me":
                if ($fb->hasPerm("admin")) {
                    $chan = $fb->args[4];
                    $whattosay = substr($fb->allargs, strlen($fb->args[4]) + 1);
                    $fb->sndMsg($chan, "\001ACTION $whattosay\001");
                }
                break;
            case "addmsgreply":
                if ($fb->hasPerm("addregexes")) {
                    $stuff = explode(" ` ", $fb->allargs);
                    $stuff[0] = mysql_real_escape_string($stuff[0]);
                    $stuff[1] = mysql_real_escape_string($stuff[1]);
                    mysql_query("INSERT INTO factoidlikethings(searchfor, send, type) VALUES('{$stuff[0]}', '{$stuff[1]}', 'msg')");
                    $fb->sndMsg($fb->chan, "added");
                }
                break;
            case "addregexreply":
                if ($fb->hasPerm("addregexes")) {
                    $stuff = explode(" ` ", $fb->allargs);
                    $stuff[0] = mysql_real_escape_string($stuff[0]);
                    $stuff[1] = mysql_real_escape_string($stuff[1]);
                    mysql_query("INSERT INTO factoidlikethings(searchfor, send, type) VALUES('{$stuff[0]}', '{$stuff[1]}', 'regex')");
                    $fb->sndMsg($fb->chan, "added");
                }
                break;
            case "myperms":
                if ($fb->hasPerm("admin", 0)) {
                    $fb->sndMsg($fb->chan, "You have admin");
                } else {
                    $fb->sndMsg($fb->chan, "You don't have admin");
                }
                if ($fb->hasPerm("addregexes", 0)) {
                    $fb->sndMsg($fb->chan, "You have addregexes");
                } else {
                    $fb->sndMsg($fb->chan, "You don't have addregexes");
                }
                if ($fb->hasPerm("delregexes", 0)) {
                    $fb->sndMsg($fb->chan, "You have delregexes");
                } else {
                    $fb->sndMsg($fb->chan, "You don't have delregexes");
                }
                if ($fb->hasPerm("delquotes", 0)) {
                    $fb->sndMsg($fb->chan, "You have delquotes");
                } else {
                    $fb->sndMsg($fb->chan, "You don't have delquotes");
                }
                break;
            case "8ball":
                $responses = array(
                    "Forget about it.",
                    "Don't bet on it.",
                    "You may rely on it.",
                    "Don't count on it.",
                    "As I see it, yes.",
                    "Signs point to yes.",
                    "I have my doubts.",
                    "Who knows?",
                    "Very doubtful.",
                    "Yes.",
                    "Yes - definitely.",
                    "Definitely not.",
                    "My sources say no.",
                    "My reply is no.",
                    "Forget about it.",
                    "Most likely.",
                    "Probably.",
                    "Yes, in due time.",
                    "Outlook good.",
                    "Don't bet on it.",
                    "You will have to wait.",
                    "Better not tell you now.",
                    "It is certain.",
                    "No.",
                    "Without a doubt.",
                    "FUCKING YES.",
                    "No fucking chance."
                );
                $response = $responses[rand(0, 26)];
                $fb->sndMsg($fb->chan, "{$fb->nick}, $response");
                break;
            case "ignore":
                if ($fb->hasPerm("admin")) {
                    // check if already ignored
                    $hosttoignore = mysql_real_escape_string($fb->allargs);
                    if (!$fb->hasPerm("ignore", 0, $hosttoignore)) {
                        // ignore
                        if (mysql_query("INSERT INTO users(hostname, permissions) VALUES('$hosttoignore', 'ignore')")) {
                            $fb->sndMsg($fb->chan, "ignored");
                        } else {
                            $fb->sndMsg($fb->chan, "query failed");
                        }
                    } else {
                        $fb->sndMsg($fb->chan, "that host is already ignored");
                    }
                }
                break;
            case "hp":
                // Tells a player of the status of theirself or another player
                $player = $fb->allargs;
                if (strlen($player) == 0) {
                    $player = $fb->nick;
                }

                $hp = $bat->getPlayerHealth($player);

                if (!$hp) {
                    $fb->sndMsg($fb->chan, "{$player} has not attacked or been attacked yet.");
                } else {
                    $fb->sndMsg($fb->chan, "{$player} has {$hp} HP.");
                }
                break;
            case "setgender":
                $gender = $fb->allargs;
                if ($gender == "m") {
                    $bat->setPlayerGender($fb->nick, "m");
                } elseif ($gender == "f") {
                    $bat->setPlayerGender($fb->nick, "f");
                } elseif ($gender == "o") {
                    $bat->setPlayerGender($fb->nick, "o");
                } else {
                    $fb->sndMsg($fb->chan, "Valid options are m, f, or o (other/unspecified, uses they)");
                }
                break;
            case "shuffle":
                $a = $fb->allargs;
                $a = str_split($a);
                shuffle($a);
                $fb->sndMsg($fb->chan, implode("", $a));
                break;
            case "choose":
                $options = explode(" ", $fb->allargs);
                $fb->sndMsg($fb->chan, "{$fb->nick}, {$options[array_rand($options)]}"); // srsly php? y?
                break;
            case "appledash":
                if (strtolower($fb->nick) == "appledash" || $fb->hasPerm("admin")) {
                    $bat->players[$bat->getPlayerId($bat->players, "appledash")]["health"] = PHP_INT_MAX;
                    $fb->sndMsg($fb->chan, "Done.");
                } else {
                    $fb->sndMsg($fb->chan, "You don't have the appledash permission.");
                }
                break;
            case "dashapple":
                if (strtolower($fb->nick) == "appledash") {
                    $bat->players[$bat->getPlayerId($bat->players, "appledash")]["health"] = -1;
                    $fb->sndMsg($fb->chan, "Done.");
                } else {
                    $fb->sndMsg($fb->chan, "You don't have the appledash permission.");
                }
                break;
            default:
                if ($fb->somethingmatched == 0) { // this doesn't even work, why do I have this here? :|
                    $factoidlikethings = mysql_query("SELECT * FROM factoidlikethings");
                    while ($blargag = mysql_fetch_array($factoidlikethings)) {
                        if ($blargag['type'] == "regex") {
                            $blargag['searchfor'] = str_replace("<botnick>", $fb->botnick, $blargag['searchfor']);
                            if (preg_match($blargag['searchfor'], $fb->msg, $args)) {
                                if (substr($blargag['send'], 0, 8) == "<action>") {
                                    $blargag['send'] = substr($blargag['send'], 8);
                                    $craptosend = "\001ACTION{$blargag['send']}\001";
                                } else {
                                    $craptosend = $blargag['send'];
                                }
                                $craptosend = str_replace("<nick>", $fb->nick, $craptosend);
                                $craptosend = str_replace("<botnick>", $fb->botnick, $craptosend);
                                //print_r($args);
                                $craptosend = str_replace("<args>", $args[1], $craptosend);
                                $craptosend = str_replace("<args2>", $args[2], $craptosend);
                                $craptosend = str_replace("<randnum>", rand(1, 100), $craptosend);
                                $fb->sndMsg($fb->chan, $craptosend);
                                $fb->somethingmatched = 1;
                            }
                        } elseif ($blargag['type'] == "msg") {
                            $blargag['searchfor'] = str_replace("<botnick>", $fb->botnick, $blargag['searchfor']);
                            if ($fb->msg == $blargag['searchfor']) {
                                echo "msg match found";
                                if (substr($blargag['send'], 0, 8) == "<action>") {
                                    $blargag['send'] = substr($blargag['send'], 8);
                                    $craptosend = "\001ACTION {$blargag['send']}\001";
                                } else {
                                    $craptosend = $blargag['send'];
                                }
                                $craptosend = str_replace("<nick>", $fb->nick, $craptosend);
                                $craptosend = str_replace("<botnick>", $fb->botnick, $craptosend);
                                $fb->sndMsg($fb->chan, $craptosend);
                                $fb->somethingmatched = 1;
                            }
                        }
                    }
                    /*if(preg_match("/\001ACTION (.*) {$fb->botnick}( )?\001$/i", $fb->msg, $result)) {
                        /*if($fb->lolwut == 4) {
                            $fb->sndMsg($fb->chan, "\001ACTION awgasms\001");
                            $fb->lolwut = 0;
                        } else {
                            $fb->sndMsg($fb->chan, "\001ACTION lieks it\001");
                            $fb->lolwut++;
                        }*
                        $fb->sndMsg($fb->chan, "\001ACTION {$result[1]} {$fb->nick} back\001");
                    } else*/
                    if ($fb->msg == "what's in the cup?") {
                        if (rand(0, 1) == 0) {
                            $fb->sndMsg($fb->chan, "There's nothing to see, except BEES BEES BEES!");
                        } else {
                            $fb->sndMsg($fb->chan, "NO BEES! Just ask anyone.");
                        }
                    } elseif ($fb->msg == "{$fb->botnick}!") {
                        if ($fb->botnick == "Yppalps") {
                            $fb->sndMsg($fb->chan, "\001ACTION jumps into {$fb->nick}'s lap!\001");
                            $fb->sndMsg($fb->chan, "*peep! peep!*");
                        } else {
                            $fb->sndMsg($fb->chan, "{$fb->nick}!");
                        }
                    } elseif (preg_match("/^{$fb->botnick}(,|:) yes, (.*) would like (a|an) (.*)$/i", $fb->msg, $blah)) {
                        if ($blah[2] == "I") {
                            $fb->sndMsg($fb->chan, "\001ACTION throws {$blah[3]} {$blah[4]} at {$fb->nick}'s face\001");
                            $fb->sendRaw("KICK {$fb->chan} {$fb->nick} :FACE" . strtoupper(str_replace(" ", "", $blah[4])) . "!");
                        } else {
                            $fb->sndMsg($fb->chan, "\001ACTION throws {$blah[3]} {$blah[4]} at {$blah[2]}'s face\001");
                            if ($fb->hasPerm("pie", 0)) {
                                $fb->sendRaw("KICK {$fb->chan} {$blah[2]} :FACE" . strtoupper(str_replace(" ", "", $blah[4])) . "!");
                            }
                        }
                    } elseif (preg_match("/^{$fb->botnick}(,|:) eat (.*)$/i", $fb->msg, $blah)) {
                        $fb->sndMsg($fb->chan, "\001ACTION eats {$blah[2]}\001");
                    } elseif (preg_match("/^fish go (moo|m00)$/", $fb->msg)) {
                        $fb->sndMsg($fb->chan, "\001ACTION notes that {$fb->nick} is truly enlightened.\001");
                    } elseif (preg_match("/^fish go (.*)/i", $fb->msg, $blah)) {
                        $fb->sndMsg($fb->chan, "{$fb->nick} LIES! Fish don't go {$blah[1]}! fish go m00!");
                    } elseif (preg_match("/^{$fb->botnick}(,|:) spin the wheel for (.*)/i", $fb->msg, $blah) or preg_match("/^{$fb->botnick}(,|:), spin the wheel of misfortune for (.*)/i", $fb->msg, $blah)) {
                        if (substr($blah[2], -1) == "!") {
                            $blah[2] = substr($blah[2], 0, -1);
                        }
                        $randnick = $fb->nicklist[$fb->chan][array_rand($fb->nicklist[$fb->chan])];
                        $fb->sndMsg($fb->chan, "\001ACTION swaps {$blah[2]}'s parts with {$randnick}'s\001");
                    } elseif (preg_match("/^{$fb->botnick}(,|:) spin the wheel/i", $fb->msg, $blah)) {
                        // SPIN THE WHEEL OF MISTER FORTUNE
                        $randnick1 = $fb->nicklist[$fb->chan][array_rand($fb->nicklist[$fb->chan])];
                        $randnick2 = $fb->nicklist[$fb->chan][array_rand($fb->nicklist[$fb->chan])];
                        $fb->sndMsg($fb->chan, "\001ACTION swaps {$randnick1}'s parts with {$randnick2}'s\001");
                    } elseif (preg_match("/Lol/", $fb->msg, $blah) and !preg_match("/GLolol/", $fb->msg, $blah)) {
                        $fb->sndMsg($fb->chan, "\001ACTION beats {$fb->nick} up\001");
                        /*if(!isset($lolfine[$fb->nick])) {
                            $lolfine[$fb->nick] = 5;
                        } else {
                            $lolfine[$fb->nick] = $lolfine[$fb->nick] + 5;
                        }
                        $fb->sndMsg($fb->chan, "{$fb->nick}, you have been fined \$5 for improper capitalisation of \"lol\". Current total: \${$lolfine[$fb->nick]}");*/
                    } elseif (!doActionStuff($fb, $bat)) {
                        if (preg_match("/\001ACTION (.*) {$fb->botnick}( )?\001$/i", $fb->msg, $result)) {
                            $fb->sndMsg($fb->chan, "\001ACTION {$result[1]} {$fb->nick} back\001");
                        }
                    }
                }
        }
        $fb->cmd = "";
        $fb->msg = "";
        $fb->somethingmatched = 0;
    }
}
?>
