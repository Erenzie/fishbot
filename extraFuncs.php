<?php

// http://www.php.net/manual/en/function.array-search.php#100057
/**
 * Gets the parent stack of a string array element if it is found within the
 * parent array
 *
 * This will not search objects within an array, though I suspect you could
 * tweak it easily enough to do that
 *
 * @param string $child The string array element to search for
 * @param array $stack The stack to search within for the child
 * @return array An array containing the parent stack for the child if found,
 *               false otherwise
 */
function getParentStack($child, $stack) {
    foreach ($stack as $k => $v) {
        if (is_array($v)) {
            // If the current element of the array is an array, recurse it and capture the return
            $return = getParentStack($child, $v);
           
            // If the return is an array, stack it and return it
            if (is_array($return)) {
                return array($k => $return);
            }
        } else {
            // Since we are not on an array, compare directly
            if ($v == $child) {
                // And if we match, stack it and return it
                return array($k => $child);
            }
        }
    }
   
    // Return false since there was nothing found
    return false;
}

/**
 * Gets the complete parent stack of a string array element if it is found
 * within the parent array
 *
 * This will not search objects within an array, though I suspect you could
 * tweak it easily enough to do that
 *
 * @param string $child The string array element to search for
 * @param array $stack The stack to search within for the child
 * @return array An array containing the parent stack for the child if found,
 *               false otherwise
 */
function getParentStackComplete($child, $stack) {
    $return = array();
    foreach ($stack as $k => $v) {
        if (is_array($v)) {
            // If the current element of the array is an array, recurse it
            // and capture the return stack
            $stack = getParentStackComplete($child, $v);
           
            // If the return stack is an array, add it to the return
            if (is_array($stack) && !empty($stack)) {
                $return[$k] = $stack;
            }
        } else {
            // Since we are not on an array, compare directly
            if ($v == $child) {
                // And if we match, stack it and return it
                $return[$k] = $child;
            }
        }
    }
   
    // Return the stack
    return empty($return) ? false: $return;
}


// http://www.php.net/manual/en/function.array-search.php#94598
function getKeyPositionInArray($haystack, $keyNeedle)
{
    $i = 0;
    foreach($haystack as $key => $value)
    {
        if($key == $keyNeedle)
        {
            return $i;
        }
        $i++;
    }
}

function getPlayerId($haystack, $needle) {
	$i = 0;
	foreach($haystack as $playerarray) {
		if($playerarray["name"] == $needle) {
			return $i;
		}
		$i++;
	}
}

// http://www.php.net/manual/en/function.unset.php#89881
# remove by key:
function array_remove_key ()
{
  $args  = func_get_args();
  return array_diff_key($args[0],array_flip(array_slice($args,1)));
}
# remove by value:
function array_remove_value ()
{
  $args = func_get_args();
  return array_diff($args[0],array_slice($args,1));
}

// http://www.php.net/manual/en/function.unset.php#74170
/**
  * array array_remove ( array input, mixed search_value [, bool strict] )
  **/
function array_remove(array &$a_Input, $m_SearchValue, $b_Strict = False) {
    $a_Keys = array_keys($a_Input, $m_SearchValue, $b_Strict);
    foreach($a_Keys as $s_Key) {
        unset($a_Input[$s_Key]);
    }
    return $a_Input;
}
   
// somewhere from the php.net page on memory_get_usage() i think? not sure
function echo_memory_usage() {
        $mem_usage = memory_get_usage(true);
       
        if ($mem_usage < 1024)
            return $mem_usage." bytes";
        elseif ($mem_usage < 1048576)
            return round($mem_usage/1024,2)." kilobytes";
        else
            return round($mem_usage/1048576,2)." megabytes";
    } 
?>
