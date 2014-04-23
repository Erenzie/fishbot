<?php

function isPlural($thing) {
    $maybepenis = end(explode(" ", $thing));
    if ($maybepenis == "penis" || $maybepenis == "cactus") {
        return false;
    }
    if ($maybepenis == "cacti") {
        return true;
    }
    if (substr($thing, -1) == "s" && substr($thing, -2) !== "'s") {
        return true;
    }

    return false;
}

function doActionStuff($botobj, $batobj) {
	$fb = $botobj;
	$bat = $batobj;
	if($bat->checkAttkMatch("attacks (.*) with (.*)", $fb->msg, $fb) or $bat->checkAttkMatch("stabs (.*) with (.*)", $fb->msg, $fb) or $bat->checkAttkMatch("fites (.*)", $fb->msg, $fb)) {
		if($bat->victim == $fb->botnick) {
			$fb->sndMsg($fb->chan, "get off me");
		} else {
			$result = $bat->doAttacking($bat->attacker, $bat->victim, $bat->weapon);
			/* [0]/["type"] = type of result: normal, fatalNormal, crit, fatalCrit, miss
			 * [1]/["dmg"] = damage done
			 * [2]/["hp"] = victim's new health
			 * [3]/["wep"] = "clean" weapon name */
			
			// check for fite
			if(preg_match("/\001ACTION fites (.*)\001/", $fb->msg)) {
				$result['wep'] = "the 1v1 fite irl";
			}
			
			switch($result["type"]) {
				case "miss":
					$lolo = rand(1, 3);
					if($lolo == 1) {
						// miss
						$msg = "MISS!";
					} elseif($lolo == 2) {
						$msg = "{$bat->victim} is immune to {$result['wep']}";
					} else {
						$msg = "\001ACTION calls the police\001";
					}
					$fb->sndMsg($fb->chan, $msg);
					$manualsnd = true;
					break;
				
				case "fatalNormal":
					$fb->sndMsg($fb->chan, "{$bat->victim} is fatally injured by {$result['wep']}, taking {$result['dmg']} damage. RIP");
					$bat->doRespawn($bat->victim, $fb);
					$manualsnd = true;
					break;
				
				case "fatalCrit":
					$fb->sndMsg($fb->chan, "{$bat->victim} is \002CRITICALLY HIT\002 to \002DEATH\002 by {$result['wep']}, taking {$result['dmg']} damage! RIP");
					$bat->doRespawn($bat->victim, $fb);
					$manualsnd = true;
					break;
					
				case "normal":
					if($result['dmg'] > 1500) {
						$msg = "{$bat->victim} is tremendously damaged by {$result['wep']}, taking {$result['dmg']} damage!";
					} elseif($result['dmg'] < 200) {
						$msg = "{$bat->victim} barely even felt {$result['wep']}, taking {$result['dmg']} damage.";
					} else {
						$msg = "{$bat->victim} takes {$result['dmg']} damage from {$result['wep']}.";
					}
					$manualsnd = false;
					break;
					
				case "crit":
					if($result['type'] !== "normal") { // i'm bad
						$msg = "{$bat->victim} is \002CRITICALLY HIT\002 by {$result['wep']}, taking {$result['dmg']} damage!";
					}
					$manualsnd = false;
					break;
				
				default:
					// say remaining HP
					//$fb->sndMsg($fb->chan, "{$bat->victim} now has {$result['hp']} HP.");
			}
			$matched = true;
			/*if(!$nohp) {
				$msg = $msg." They now have {$result['hp']} HP.";
			}
			$fb->sndMsg($fb->chan, $msg);*/
		}
	} elseif($bat->checkAttkMatch("throws (.*) at (.*)", $fb->msg, $fb, true) or $bat->checkAttkMatch("drops (.*) on (.*)", $fb->msg, $fb, true)) {	
		if($bat->victim == $fb->botnick) {
			$fb->sndMsg($fb->chan, "owww :(");
		} else {
			$result = $bat->doAttacking($bat->attacker, $bat->victim, $bat->weapon);
			
			$result['wep'] = ucfirst($result['wep']);
			
			if($result['wep'] == "The bass") {
				if(preg_match("/\001ACTION drops (.*) on (.*)\001/", $fb->msg)) {
					$result['wep'] = "The dubstep";
				}
			}
			
			switch($result['type']) {
				case "miss":
					// hit some other random person in the channel
					$randperson = $fb->getRandChanMember($fb->chan);
					$whoitwassupposedtohit = $bat->victim;
					$result = $bat->doAttacking($bat->attacker, $randperson, $bat->weapon, true);
					$msg = "{$bat->attacker} missed {$whoitwassupposedtohit} and instead hit {$randperson}, dealing {$result['dmg']} damage!";
					$manualsnd = false;
					break;
				
				case "fatalNormal":
				case "fatalCrit":
					$fb->sndMsg($fb->chan, "{$result['wep']} hit {$bat->victim} so hard that {$bat->they_now($bat->victim, 3)} fell over and died, taking {$result['dmg']} damage. RIP");
					$bat->doRespawn($bat->victim, $fb);
					$manualsnd = true;
					break;
				
				case "normal":
				case "crit":
					// check if the weapon is a user in the channel
					if($fb->checkUserInChan(substr($result['wep'], 4), $fb->chan)) {
						// hurt the weaponised user too
						$userweaponised = true;
						$weaponiseduser = substr($result['wep'], 4);
						// check if they're in game
						if (!$bat->checkPlayerInGame($weaponiseduser)) {
							// attacker isn't in game, add them
							$bat->addPlayer($weaponiseduser);
							echo "added weaponised user {$weaponiseduser} to game - {$bat->getPlayersString()}\n";
						}
						$wuhp = $bat->damagePlayer($weaponiseduser, $result['dmg']);
					} else { $userweaponised = false; }
					
					if($result['dmg'] > 1500) {
						// FUCK THE ENGLISH LANGUAGE
						if(isPlural($result["wep"])) {
							$msg = "{$result['wep']} severely injure";
						} else {
							$msg = "{$result['wep']} severely injures";
						}
						
						if($userweaponised) {
							$msg = $msg." {$bat->victim}, dealing {$result['dmg']} damage to both!";
						} else {
							$msg = $msg." {$bat->victim}, dealing {$result['dmg']} damage!";
						}
					} elseif($result['dmg'] < 200) {
						if($userweaponised) {
							$msg = "{$result['wep']} barely hit {$bat->victim}, dealing {$result['dmg']} damage to both.";
						} else {
							$msg = "{$result['wep']} barely hit {$bat->victim}, dealing {$result['dmg']} damage.";
						}
					} else {
						if(isPlural($result["wep"])) {
							$msg = "{$result['wep']} thwack";
						} else {
							$msg = "{$result['wep']} thwacks";
						}
						
						if($userweaponised) {
							$msg = $msg." {$bat->victim} in the face, dealing {$result['dmg']} damage to both.";
						} else {
							$msg = $msg." {$bat->victim} in the face, dealing {$result['dmg']} damage.";
						}
					}
					if($userweaponised) {
						$manualsnd = true;
						$msg = $msg." {$bat->victim} now has {$result['hp']} HP, and {$weaponiseduser} now has {$wuhp} HP.";
						$fb->sndMsg($fb->chan, $msg);
					} else {
						$manualsnd = false;
					}
					break;
				
				default:
					//$fb->sndMsg($fb->chan, "{$bat->victim} now has {$result['hp']} HP.");
			}
			$matched = true;
		}
	} elseif($bat->checkAttkMatch("casts (.*) at (.*)", $fb->msg, $fb, true) or $bat->checkAttkMatch("casts (.*) on (.*)", $fb->msg, $fb, true)) {
		if($bat->victim == $fb->botnick) {
			$fb->sndMsg($fb->chan, "I am immune to your petty spells! Muahahaha!");
		} else {
			$result = $bat->doAttacking($bat->attacker, $bat->victim, $bat->weapon);
			
			// do another the check!
			/*if($sweapon[0] !== "the") {
				// check for 's
				if(!stristr($weapon, "'s")) {
					// no 's, add the
					$weapon = "the ".$weapon;
				}
			} */
			if(!stristr($result['wep'], "'s")) {
				$wep = substr($result['wep'], 4); // remove the because no 's
			} else {
				$wep = $result['wep'];
			}
			
			switch($result['type']) {
				case "miss":
					$fb->sndMsg($fb->chan, "You failed at casting...");
					$manualsnd = true;
					break;
				
				case "fatalNormal":
				case "fatalCrit":
					$fb->sndMsg($fb->chan, "{$bat->attacker} casts a fatal spell of {$wep} at {$bat->victim}, dealing {$result['dmg']} damage. RIP");
					$bat->doRespawn($bat->victim, $fb);
					$manualsnd = true;
					break;
				
				case "normal":
				case "crit":
					$msg = "{$bat->attacker} casts {$wep} at {$bat->victim}, dealing {$result['dmg']} damage.";
					$manualsnd = false;
					break;
				
				default:
					//
			}
			$matched = true;
		}
	} elseif($bat->checkForHealCmd($fb->msg, $fb)) {
		$result = $bat->doHealing($bat->patient, $bat->healer, $bat->tool);
		
		if($result['type'] == "fail") {
			$fb->sndMsg($fb->chan, "{$bat->healer} tried to heal {$bat->patient} with {$result['tool']}, however {$bat->they_now($bat->healer, 3)} failed. :(");
			$manualsnd = true;
		} elseif($result['type'] == "success") {
			$msg = "{$bat->healer} managed to heal {$bat->patient} for {$result['healing']} HP with {$result['tool']}!";
			$manualsnd = false;
		} elseif($result['type'] == "backfire") {
			$msg = "In a freak accident, {$result['tool']} hurt {$bat->healer} for {$result['healing']} damage instead of healing {$bat->patient}!";
			$manualsnd = false;
		} elseif($result['type'] == "fatalbackfire") {
			$fb->sndMsg($fb->chan, "In a freak accident, {$result['tool']} KILLED {$bat->healer} with {$result['healing']} damage instead of healing {$bat->patient}!");
			$bat->doRespawn($bat->healer, $fb);
			$manualsnd = true;
		}
		$matched = true;
		$bat->victim = $bat->patient;
	}
	
	if($matched) {
		if(!$manualsnd) {
			if(!isset($pronoun_has)) {
				$pronoun_has = $bat->they_now($bat->victim, 1);
			}
			$msg = $msg." {$pronoun_has} {$result['hp']} HP.";
			$fb->sndMsg($fb->chan, $msg);
			unset($pronoun_has);
		}
		return true;
	} else { return false; }
}
?>
