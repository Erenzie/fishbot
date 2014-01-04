<?php

require("extraFuncs.php");

class bdBattle {
    var $players = array();
    var $playersnames = array(); // i'm bad


    function addPlayer($player) {
        /* Adds a player to the game
         *
         * $player - player to add to the game
         */
        if (in_array($player, $this->playersnames)) {
            return false;
        } else {
            $this->players[] = array("name" => $player, "health" => 10000);
            $this->playersnames[] = $player;
            $this->playersalive++;
            return true;
        }
    }

    function getPlayersString() {
        /* Returns a string of all players currently in the game, joined by , */
        if (!empty($this->players)) {
            $players = implode(", ", $this->playersnames);
            return $players;
        } else {
            return false;
        }
    }

    function checkPlayerInGame($player) {
        /* Checks if a player is currently in the game */
        if (!in_array($player, $this->playersnames)) {
            return false;
        } else {
            return true;
        }
    }

    function getPlayerHealth($player) {
        /* Returns the current health of a player
         *
         * $player - player to get the health of
         */
        if (!$this->checkPlayerInGame($player)) {
            return false;
        } else {
            $id = $this->getPlayerId($this->players, $player);
            return $this->players[$id]["health"];
        }
    }

    function damagePlayer($player, $damage) {
        /* Deals damage to a player
         *
         * $player - player to deal damage to
         * $damage - amount of damage to deal
         *
         * returns player's health after the damage is dealt, 0 if player is dead
         */
        if (!$this->checkPlayerInGame($player)) {
            return false;
        } else {
            $id = $this->getPlayerId($this->players, $player);
            $health = $this->players[$id]["health"];

            // check if damage > health
            if ($damage > $health) {
                $newhealth = 0;
            } else {
                $newhealth = $health - $damage;
            }

            $this->players[$id]["health"] = $newhealth;

            return $newhealth;
        }
    }

    function getPlayerId($haystack, $needle) {
        /* Returns the id of a player in the players array
         *
         * $haystack - should only ever be $this->players, idk why i don't hardcode that in
         * $needle - name of the player to search for
         */
        $i = 0;

        if (!in_array($needle, $this->playersnames)) {
            return false;
        }

        foreach ($haystack as $playerarray) {
            if ($playerarray["name"] == $needle) {
                return $i;
            }
            $i++;
        }
    }

    function getNumPlayers() {
        /* Returns number of players in the game */

        return count($this->playersnames);
    }

    function getCleanWepName($weapon) {
        /* Cleans the name of what someone's attacking someone with */
        $sweapon = explode(" ", $weapon);
        // check for a/an
        if ($sweapon[0] == "a") {
            $weapon = substr($weapon, 2);
        } elseif ($sweapon[0] == "an") {
            $weapon = substr($weapon, 3);
        }
        // check for his/her/its/their/eir/zir (yes. all of those.)
        switch ($sweapon[0]) {
            case "his":
            case "her":
            case "its":
            case "eir":
            case "zir":
            case "hir":
                $weapon = substr($weapon, 4);
                $pronounused = 1;
                break;
            case "their":
                $weapon = substr($weapon, 6);
                $pronounused = 1;
                break;

            default:
                $pronounused = 0;
        }

        // /me attacks blah with the blah
        // /me attacks blah with blah's blah
        // /me attacks blah with their blah
        // /me attacks blah with their blah's blah
        // check for "the"
        if ($sweapon[0] !== "the") {
            // check for 's
            if (!stristr($weapon, "'s")) {
                // no 's, add the
                if ($pronounused) {
                    $weapon = "{$this->attacker}'s {$weapon}";
                } else {
                    $weapon = "the " . $weapon;
                }
            }
        }

        return $weapon;
    }

    function getCleanToolName($tool) {
        /* getCleanWepName() but for healing; too lazy to change $weapon */
        $stool = explode(" ", $tool);
        // check for a/an/the
        if ($stool[0] == "a") {
            $tool = substr($tool, 2);
        } elseif ($stool[0] == "an") {
            $tool = substr($tool, 3);
        } elseif ($stool[0] == "the") {
            $tool = substr($tool, 4);
        }

        // check for his/her/its/their/eir/zir (yes. all of those.)
        switch ($stool[0]) {
            case "his":
            case "her":
            case "its":
            case "eir":
            case "zir":
            case "hir":
                $pronounused = 1;
                break;
            case "their":
                $pronounused = 1;
                break;

            default:
                $pronounused = 0;
        }

        // check for "their"
        if (!$pronounused) {
            // add their
            if (!stristr($tool, "'s")) {
                $tool = "\"{$tool}\"";
            }
        }

        return $tool;
    }

    function doRespawn($player, $botobj) {
        /* Resets the health of a player to 10,000
         * $botobj is for sending the respawn msg */

        $id = $this->getPlayerId($this->players, $player);
        $this->players[$id]["health"] = 10000;

        // add to death count
        $numdeaths = $this->addToDeathCount($player);
        if ($numdeaths == 1 or substr($numdeaths, -1) == "1" && $numdeaths !== 11) {
            $a = "{$numdeaths}st";
        } elseif ($numdeaths == 2 or substr($numdeaths, -1) == "2" && $numdeaths !== 12) {
            $a = "{$numdeaths}nd";
        } elseif ($numdeaths == 3 or substr($numdeaths, -1) == "3" && $numdeaths !== 13) {
            $a = "{$numdeaths}rd";
        } else {
            $a = "{$numdeaths}th";
        }

        // pick a respawn method
        $lolo = rand(1, 2);
        $they_have = $this->they_now($player, 2);
        if ($lolo == 1) {
            $botobj->sndMsg($botobj->chan, "However, through the use of ancient magic rituals, {$they_have} been reborn with full health for the {$a} time.");
        } else {
            $botobj->sndMsg($botobj->chan, "However, thanks to new technology, {$they_have} respawned with full health for the {$a} time.");
        }
    }

    function checkIsVictimSelf($victim) {
        switch ($victim) {
            case "himself":
            case "herself":
            case "itself":
            case "eirself":
            case "zirself":
            case "hirself":
            case "theirself":
            case "self":
                return true;

            default:
                return false;
        }
    }

    function doAttacking($attacker, $victim, $weapon, $nofail = false) {
        /* Does all the attacking of stuff and returns an array with the following:
         * [0]/["type"] = type of result: normal, fatalNormal, crit, fatalCrit, miss
         * [1]/["dmg"] = damage done
         * [2]/["hp"] = victim's new health
         * [3]/["wep"] = clean weapon name */

        $result = array();

        if (!$this->checkPlayerInGame($attacker)) {
            // attacker isn't in game, add them
            $this->addPlayer($attacker);
            echo "added attacker {$attacker} to game - {$this->getPlayersString()}\n";
        }

        if (!$this->checkPlayerInGame($victim)) {
            // victim isn't in game, add them
            $this->addPlayer($victim);
            echo "added victim {$victim} to game - {$this->getPlayersString()}\n";
        }

        $weapon = $this->getCleanWepName($weapon);

        $decideIfCrit = rand(1, 100);
        if ($decideIfCrit > 90) {
            $isCrit = true;
        } else {
            $isCrit = false;
        }

        if ($isCrit) {
            $damagetodeal = rand(3000, 10000);
        } else {
            $damagetodeal = rand(1, 3000);
        }

        if (!$nofail) {
            $decideIfMiss = rand(1, 100);
            if ($decideIfMiss > 90) {
                $isMiss = true;
                $damagetodeal = 0;
            } else {
                $isMiss = false;
            }
        } else {
            $isMiss = false;
        }

        $attackedhp = $this->damagePlayer($victim, $damagetodeal);

        if ($isMiss) {
            $result["type"] = "miss";
        } elseif ($isCrit && $attackedhp !== 0) {
            $result["type"] = "crit";
        } elseif ($isCrit && $attackedhp == 0) {
            $result["type"] = "fatalCrit";
        } elseif (!$isCrit && $attackedhp !== 0) {
            $result["type"] = "normal";
        } elseif (!$isCrit && $attackedhp == 0) {
            $result["type"] = "fatalNormal";
        }

        $result["dmg"] = $damagetodeal;
        $result["hp"] = $attackedhp;
        $result["wep"] = $weapon;

        print_r($result);
        return $result;
    }

    function checkAttkMatch($regex, $string, $botobj, $altorder = false) {
        /* $altorder = true; for "/me throws <weapon> at <victim"
         * $altorder = false; for "/me attacks <victim> with <weapon>" */

        /* /^\001ACTION attacks (.*) with (.*)\001$/i
         * /^\001ACTION throws (.*) at (.*)\001$/i */
        // /^\001ACTION {$text}\001$/i
        if (preg_match("/^\001ACTION {$regex}\001$/i", $string, $blah)) {
            $this->attacker = $botobj->nick;
            if ($altorder) {
                $victim = $blah[2];
            } else {
                $victim = $blah[1];
            }
            $this->victim = trim($victim);
            if ($this->checkIsVictimSelf($victim)) {
                $this->victim = $botobj->nick;
            }
            if ($altorder) {
                $this->weapon = $blah[1];
            } else {
                $this->weapon = $blah[2];
            }

            return true;
        } else {
            $this->attacker = null;
            $this->victim = null;
            $this->weapon = null;

            return false;
        }
    }

    function checkForHealCmd($string, $botobj) {
        if (preg_match("/^\001ACTION heals (.*) with (.*)\001$/i", $string, $blah)) {
            $this->healer = $botobj->nick;
            $this->patient = trim($blah[1]);
            if ($this->checkIsVictimSelf($this->patient)) {
                $this->patient = $botobj->nick;
            }
            $this->tool = $blah[2];
            return true;
        } else {
            $this->healer = null;
            $this->patient = null;
            $this->tool = null;
            return false;
        }
    }

    function doHealing($patient, $healer, $tool) {
        /* Heals a player and returns an array with the following:
         * [0]/["type"] = type of result: fail or success
         * [1]/["healing"] = amount of healing done
         * [2]/["hp"] = victim's new health
         * [3]/["tool"] = "clean" tool name */

        $result = array();

        if (!$this->checkPlayerInGame($patient)) {
            // attacker isn't in game, add them
            $this->addPlayer($patient);
            echo "added patient {$patient} to game - {$this->getPlayersString()}\n";
        }

        if (!$this->checkPlayerInGame($healer)) {
            // victim isn't in game, add them
            $this->addPlayer($healer);
            echo "added healer {$healer} to game - {$this->getPlayersString()}\n";
        }

        $tool = $this->getCleanToolName($tool);

        $toheal = rand(1, 1500);

        $decideIfSuccess = rand(1, 100);
        if ($decideIfSuccess < 50) {
            $isSuccess = true;
        } else {
            $isSuccess = false;
        }

        if ($isSuccess) {
            $newhp = $this->damagePlayer($patient, "-" . $toheal);
            $result['type'] = "success";
        } else {
            // decide if it should backfire or just fail
            $decideIfBackfire = rand(1, 100);
            if ($decideIfBackfire > 70) {
                // backfire!
                $newhp = $this->damagePlayer($healer, $toheal);
                if ($newhp == 0) {
                    $result['type'] = "fatalbackfire";
                } else {
                    $result['type'] = "backfire";
                }
            } else {
                $toheal = 0;
                $newhp = $this->damagePlayer($patient, 0);
                $result['type'] = "fail";
            }
        }

        $result["healing"] = $toheal;
        $result["hp"] = $newhp;
        $result["tool"] = $tool;

        print_r($result);
        return $result;
    }

    function getNumDeaths($player) {
        $player = mysql_real_escape_string($player);

        $result = mysql_query("SELECT * FROM battleusers WHERE nick='{$player}'");
        if (!mysql_num_rows($result)) {
            // player isn't in db
            echo "numdeaths = 0\n";
            return false;
        } else {
            $a = mysql_fetch_array($result);
            print_r($a);
            $numdeaths = $a[1];
            return $numdeaths;
        }
    }

    function addToDeathCount($player) {
        $numdeaths = $this->getNumDeaths($player);
        $player = mysql_real_escape_string($player);
        if (!$numdeaths) {
            // add new record to db
            $result = mysql_query("INSERT INTO battleusers(nick, deaths) VALUES('{$player}', 1)");
            return 1;
        } else {
            // update
            $numdeaths++;
            $result = mysql_query("UPDATE battleusers SET deaths = '{$numdeaths}' WHERE nick = '{$player}'");
            return $numdeaths;
        }
    }

    function they_now_gen($gender, $type) {
        /* $type = 1 - "He now has", "She now has", "They now have"
         * $type = 2 - "he has been", "she has been", "they have been" */

        if ($type == 1) {
            if ($gender == "m") {
                return "He now has";
            } elseif ($gender == "f") {
                return "She now has";
            } else {
                return "They now have";
            }
        } elseif ($type == 2) {
            if ($gender == "m") {
                return "he has";
            } elseif ($gender == "f") {
                return "she has";
            } else {
                return "they have";
            }
        } elseif ($type == 3) {
            if ($gender == "m") {
                return "he";
            } elseif ($gender == "f") {
                return "she";
            } else {
                return "they";
            }
        }
    }

    function getPlayerGender($player) {
        $player = mysql_real_escape_string($player);

        $result = mysql_query("SELECT * FROM battleusers WHERE nick='{$player}'");
        if (!mysql_num_rows($result)) {
            // player isn't in db
            return "o"; // unspecified gender
        } else {
            $a = mysql_fetch_array($result);
            $gender = $a[2];
            return $gender;
        }
    }

    function they_now($player, $type) {
        $gender = $this->getPlayerGender($player);
        return $this->they_now_gen($gender, $type);
    }

    function setPlayerGender($player, $gender) {
        $numdeaths = $this->getNumDeaths($player); // to check if player is in db
        $player = mysql_real_escape_string($player);
        if (!$numdeaths) {
            // add new record to db
            $result = mysql_query("INSERT INTO battleusers(nick, deaths, gender) VALUES('{$player}', 1, '{$gender}')");
            echo "setting {$player}'s gender to {$gender} and adding new db row\n";
            return true;
        } else {
            // update
            $result = mysql_query("UPDATE battleusers SET gender = '{$gender}' WHERE nick = '{$player}'");
            echo "setting {$player}'s gender to {$gender}\n";
            return true;
        }
    }
}

?>
