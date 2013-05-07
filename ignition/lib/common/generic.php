<?php
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Log.php');

$GLOBALS['SYNC_CONNECTIONS'] = NULL;
$GLOBALS['SYNC_CONNECTIONS_REVERSED'] = NULL;

// Check PHP Version
function checkPhpVersion($version) {
	$testVer=intval(str_replace(".", "",$version));
	$curVer=intval(str_replace(".", "",phpversion()));
	return ($curVer < $testVer)?false:true;
}

// Truncate a String
function truncate($str, $length=10, $trailing='...'){
	$length -= strlen($trailing);
	if(strlen($str) > $length){
		return substr($str,0,$length) . $trailing;
	} else {
		return $str;
	}
}

/*
extractVars
is used to strip variable=value pairs from the URI query string
normally used to format a new query string
*/
function extractVars($vars, $subject=null){
	if(is_null($subject)) {
		$subject = ($_SERVER['QUERY_STRING']);
	}

	$queryString = '';

	if (!empty($subject)){
		$params = explode("&", $subject);
		$tempVars = explode(",", $vars);
		$newParams = array();
		foreach ($params as $param) {
			$foundVar = false;
			foreach ($tempVars as $tempVar){
				$expression = sprintf("/^%s=/", trim($tempVar));
				if(preg_match($expression, $param)){
					$foundVar = true;
				}
				/*
				if(stristr($param, trim($tempVar)) != false){
				$foundVar = true;
				}*/
			}

			if(!$foundVar) array_push($newParams, $param);
		}
		if (count($newParams) != 0) {
			$queryString = "&" . implode("&", $newParams);
		}
	}
	return $queryString;
}

// Convert number of days to a string
// Requires modification
function convertDaysToString($days){
	$str = "";
	if($days <=2){
		$str = ($days*24) . " hours";
	} elseif($days == 7){
		$str = "1 week";
	} elseif($days > 7){
		$tempDays = ($days % 7);
		$tempWeeks =  (($days - $tempDays)/7);

		$tempDays .= ($tempDays > 1)?" days":" day";
		$tempWeeks .= ($tempWeeks > 1)?" weeks":" week";
		$str = $tempWeeks . " " . $tempDays;
	}
	return $str;
}

// Convert MySQL datetime format to system date format
function cDatetime($raw_datetime, $type=NULL) {
	if ( ($raw_datetime == '0000-00-00 00:00:00') || ($raw_datetime == '') ) return false;

	$year = (int)substr($raw_datetime, 0, 4);
	$month = (int)substr($raw_datetime, 5, 2);
	$day = (int)substr($raw_datetime, 8, 2);
	$hour = (int)substr($raw_datetime, 11, 2);
	$minute = (int)substr($raw_datetime, 14, 2);
	$second = (int)substr($raw_datetime, 17, 2);

	switch(strtolower($type)){
		case 'd':
			return $day;
		case 'm':
			return $month;
			break;
		case 'y':
			return $year;
			break;
		case 'h':
			return $hour;
			break;
		case 'i':
			return $minute;
			break;
		case 's':
			return $second;
			break;
		case 'shortdate':
			return date($GLOBALS['DATE_FORMAT_SHORT'], mktime($hour, $minute, $second, $month, $day, $year));
			break;
		case 'longdate':
			return date($GLOBALS['DATE_FORMAT_LONG'], mktime($hour, $minute, $second, $month, $day, $year));
			break;
		case 'time':
			return sprintf("%s:%s:%s", $hour, $minute, $second);
			break;
		case 'longdatetime':
			return date($GLOBALS['DATE_TIME_FORMAT_LONG'], mktime($hour, $minute, $second, $month, $day, $year));
			break;
		default:
			return date($GLOBALS['DATE_TIME_FORMAT'], mktime($hour, $minute, $second, $month, $day, $year));
			break;
	}
}

function now(){
	return date($GLOBALS['DB_DATE_TIME_FORMAT']);
}

// Check MySQL datetime format
function isDatetime($raw_datetime){
	if (($raw_datetime == '0000-00-00 00:00:00') || ($raw_datetime == '')){
		return false;
	} elseif(!preg_match("/^\d{4}-\d{2}-\d{2}\s\d{2}:\d{2}:\d{2}$/", $raw_datetime)){
		return false;
	} else {
		if(checkdate(cDatetime($raw_datetime, 'm'), cDatetime($raw_datetime, 'd'), cDatetime($raw_datetime, 'y'))){
			return true;
		} else {
			return false;
		}
		return true;
	}
}

// Check MySQL date format
function isDate($raw_datetime){
	if(checkdate(cDatetime($raw_datetime, 'm'), cDatetime($raw_datetime, 'd'), cDatetime($raw_datetime, 'y'))){
		return true;
	} else {
		return false;
	}
}

function getDateFormat($raw_datetime){
	if(isDate($raw_datetime)){
		return sprintf('%s/%s/%s', cDatetime($raw_datetime, 'd'), cDatetime($raw_datetime, 'm'), cDatetime($raw_datetime, 'y'));
	} else {
		return false;
	}
}


// MySQL datetime format
function getDatetime($time=NULL){
	return (!is_null($time))?date("Y-m-d H:i:s", $time):date("Y-m-d H:i:s", time());
}

// dateDiff checks the difference between two dates
// note that this uses the american date format and MySQL date formats only
function dateDiff($dateTimeBegin, $dateTimeEnd, $interval='d'){
	$dateTimeBegin = strtotime($dateTimeBegin);
	if($dateTimeBegin === -1) return ("Start Date Invalid");
	$dateTimeEnd = strtotime($dateTimeEnd);
	if($dateTimeEnd === -1) return ("End Date Invalid");

	$diff = $dateTimeEnd - $dateTimeBegin;

	switch(strtolower($interval)){
		case 's':
			return ($diff);
		case 'i':
			return (floor($diff/60));
		case 'h':
			return (floor($diff/3600));
		case 'd':
			return (floor($diff/86400));
		case 'w':
			return (floor($diff/604800));
		case 'm':
			$monthBegin = (date("Y", $dateTimeBegin)*12) + date("n", $dateTimeBegin);
			$monthEnd = (date("Y", $dateTimeEnd)*12) + date("n", $dateTimeEnd);
			$monthDiff = $monthEnd-$monthBegin;
			return $monthDiff;
		case 'y':
			return(date("Y", $dateTimeEnd) - date("Y", $dateTimeBegin));
		default:
			return (floor($diff/86400));
	}
}

function parse_search_string($search_str = '', &$objects) {
	$search_str = trim(strtolower($search_str));

	// Break up $search_str on whitespace; quoted string will be reconstructed later
	$pieces = preg_split ('/\s+/', $search_str);
	$objects = array();
	$tmpstring = '';
	$flag = '';

	for ($k=0; $k<count($pieces); $k++) {
		if(!empty($pieces[$k])) {
			while (substr($pieces[$k], 0, 1) == '(') {
				$objects[] = '(';
				if (strlen($pieces[$k]) > 1) {
					$pieces[$k] = substr($pieces[$k], 1);
				} else {
					$pieces[$k] = '';
				}
			}

			$post_objects = array();

			while (substr($pieces[$k], -1) == ')')  {
				$post_objects[] = ')';
				if (strlen($pieces[$k]) > 1) {
					$pieces[$k] = substr($pieces[$k], 0, -1);
				} else {
					$pieces[$k] = '';
				}
			}

			// Check individual words

			if ( (substr($pieces[$k], -1) != '"') && (substr($pieces[$k], 0, 1) != '"') ) {
				$objects[] = trim($pieces[$k]);

				for ($j=0; $j<count($post_objects); $j++) {
					$objects[] = $post_objects[$j];
				}
			} else {
				/* This means that the $piece is either the beginning or the end of a string.
				So, we'll slurp up the $pieces and stick them together until we get to the
				end of the string or run out of pieces.
				*/

				// Add this word to the $tmpstring, starting the $tmpstring
				$tmpstring = trim(ereg_replace('"', ' ', $pieces[$k]));

				// Check for one possible exception to the rule. That there is a single quoted word.
				if (substr($pieces[$k], -1 ) == '"') {
					// Turn the flag off for future iterations
					$flag = 'off';

					$objects[] = trim($pieces[$k]);

					for ($j=0; $j<count($post_objects); $j++) {
						$objects[] = $post_objects[$j];
					}

					unset($tmpstring);

					// Stop looking for the end of the string and move onto the next word.
					continue;
				}

				// Otherwise, turn on the flag to indicate no quotes have been found attached to this word in the string.
				$flag = 'on';

				// Move on to the next word
				$k++;

				// Keep reading until the end of the string as long as the $flag is on

				while ( ($flag == 'on') && ($k < count($pieces)) ) {
					while (substr($pieces[$k], -1) == ')') {
						$post_objects[] = ')';
						if (strlen($pieces[$k]) > 1) {
							$pieces[$k] = substr($pieces[$k], 0, -1);
						} else {
							$pieces[$k] = '';
						}
					}

					// If the word doesn't end in double quotes, append it to the $tmpstring.
					if (substr($pieces[$k], -1) != '"') {
						// Tack this word onto the current string entity
						$tmpstring .= ' ' . $pieces[$k];

						// Move on to the next word
						$k++;
						continue;
					} else {
						/* If the $piece ends in double quotes, strip the double quotes, tack the
						$piece onto the tail of the string, push the $tmpstring onto the $haves,
						kill the $tmpstring, turn the $flag "off", and return.
						*/
						$tmpstring .= ' ' . trim(ereg_replace('"', ' ', $pieces[$k]));

						// Push the $tmpstring onto the array of stuff to search for
						$objects[] = trim($tmpstring);

						for ($j=0; $j<count($post_objects); $j++) {
							$objects[] = $post_objects[$j];
						}

						unset($tmpstring);

						// Turn off the flag to exit the loop
						$flag = 'off';
					}
				}
			}
		}
	}

	// add default logical operators if needed
	if(count($objects) > 0) {
		$temp = array();

		for($i=0; $i<(count($objects)-1); $i++) {
			$temp[] = $objects[$i];
			if ( ($objects[$i] != 'and') &&
			($objects[$i] != 'or') &&
			($objects[$i] != '(') &&
			($objects[$i+1] != 'and') &&
			($objects[$i+1] != 'or') &&
			($objects[$i+1] != ')') ) {
				$temp[] = 'and';
			}
		}
		$temp[] = $objects[$i];
		$objects = $temp;
	}

	$keyword_count = 0;
	$operator_count = 0;
	$balance = 0;
	for($i=0; $i<count($objects); $i++) {
		if ($objects[$i] == '(') $balance --;
		if ($objects[$i] == ')') $balance ++;
		if ( ($objects[$i] == 'and') || ($objects[$i] == 'or') ) {
			$operator_count ++;
		} elseif ( ($objects[$i]) && ($objects[$i] != '(') && ($objects[$i] != ')') ) {
			$keyword_count ++;
		}
	}

	if ( ($operator_count < $keyword_count) && ($balance == 0) ) {
		return true;
	} else {
		return false;
	}
}

function redirectTo($url) {
	redirect('Location: '.$url);
}

function redirect($location){
	if(isset($GLOBALS['DBCONNECTION'])) $GLOBALS['DBCONNECTION']->Close();
	header($location);
	exit;
}

function debug($obj, $exit=null){
	if(DEVELOPER){
		echo "<pre>";
		var_dump($obj);
		echo "</pre>";
		if ($exit === -1) {
			trigger_error("Debug stack trace", E_USER_NOTICE);
		}
		if(!is_null($exit)){
			exit;
		}
	}
}

function number_format_money($price){
	$i = intval($price);
	$len = strlen($i);
	if($len > 4) return number_format($price, 2, '.', ' ');
	else return number_format($price, 2, '.', '');
}

function insertIntoString($str, $insert, $pos){
	$first = substr($str, 0, $pos);
	$last = substr($str, $pos);
	$last = $insert . $last;
	return $first . $last;
}

function reduceString($str, $start, $len){
	$first = substr($str, 0, $start);
	$last = substr($str, $start + $len);
	return $first . $last;
}

if (!function_exists("stripos")) {
	function stripos($str,$needle,$offset=0) {
		return strpos(strtolower($str),strtolower($needle),$offset);
	}
}

function getLastStr($hay, $need){
	$getLastStr = 0;
	$pos = strpos($hay, $need);
	if (is_int ($pos)){ //this is to decide whether it is "false" or "0"
		while($pos) {
			$getLastStr = $getLastStr + $pos + strlen($need);
			$hay = substr ($hay , $pos + strlen($need));
			$pos = strpos($hay, $need);
		}
		return $getLastStr - strlen($need);
	} else {
		return -1; //if $need wasn?t found it returns "-1" , because it could return "0" if it?s found on position "0".
	}
}

function hasValue($var){
	if(isset($var) && !empty($var)) return true;
	else return false;
}

function periodToString($period, $m = 30.4375){
	$remain = $period / 86400;
	$hack = 100000;

	if($remain >= $m){
		$temp = $remain;

		$remain /= 30.4375;
		$remain = floor($remain);
		$remain .= ($remain != 1) ? ' months' : ' month';

		$days = (($temp*$hack) % (30.4375 * $hack)) / $hack;

		if($days > 0) {
			$remain .= ' ' .floor($days);
			$remain .= ($days != 1) ? ' days' : ' day';
		}
	} else {
		$remain = floor($remain);
		$remain .= ($remain != 1) ? ' days' : ' day';
	}

	return $remain;
}

function periodToArray($period, $m = 30.4375){
	$period = $period / 86400;
	$hack = 100000;

	$results = array();
	$results['month'] = 0;
	$results['day'] = 0;

	if($period >= $m){
		$temp = $period;

		$period /= 30.4375;
		$results['month'] = floor($period);

		$days = (($temp*$hack) % (30.4375 * $hack)) / $hack;

		if($days > 0) {
			$results['day'] = ' ' .floor($days);
		}
	} else {
		$results['day'] = floor($period);
	}

	return $results;
}

function ignitionErrorHandler($errno, $errstr, $errfile, $errline){
	if(false) {
		$message = '';
		$log = new Log();
		switch ($errno) {
			case E_USER_ERROR:
				$message = "<b>My ERROR</b> [$errno] $errstr\n";
				$message .= "  Fatal error on line $errline in file $errfile";
				$message .= ", PHP " . PHP_VERSION . " (" . PHP_OS . ")\n";
				$message .= "Aborting...\n";
				$log->Add('SYSTEM ERROR - USER', $message);
				exit(1);
				break;

			case E_USER_WARNING:
				$message = "<b>My WARNING</b> [$errno] $errstr\n";
				$message .= "  Error on line $errline in file $errfile";
				$message .= ", PHP " . PHP_VERSION . " (" . PHP_OS . ")\n";
				$log->Add('SYSTEM ERROR - WARNING', $message);
				break;

			case E_USER_NOTICE:
				$message = "<b>My NOTICE</b> [$errno] $errstr\n";
				$message .= "  Error on line $errline in file $errfile";
				$message .= ", PHP " . PHP_VERSION . " (" . PHP_OS . ")\n";
				$log->Add('SYSTEM ERROR - NOTICE', $message);
				break;

			default:
				$message = "Unknown error type: [$errno] $errstr\n";
				$message .= "  Error on line $errline in file $errfile";
				$message .= ", PHP " . PHP_VERSION . " (" . PHP_OS . ")\n";
				$log->Add('SYSTEM ERROR - UNKNOWN', $message);
				break;
		}
	}

	/* Don't execute PHP internal error handler */
	return true;
}

$old_error_handler = set_error_handler("ignitionErrorHandler");

function addLog($owner, $type, $message){
	$log = new Log();
	$log->Owner = $owner;
	$log->Add($type, $message);
}

function getSyncConnections() {
	if(is_null($GLOBALS['SYNC_CONNECTIONS'])) {
		$GLOBALS['SYNC_CONNECTIONS'] = array();
		$connection['Title'] = $GLOBALS['DB_TITLE'];
		$connection['Connection'] = $GLOBALS['DBCONNECTION'];

		$GLOBALS['SYNC_CONNECTIONS'][] = $connection;

		for($i=0;$i<count($GLOBALS['SYNC_DB_TITLE']);$i++) {
			$connection = array();
			$connection['Title'] = $GLOBALS['SYNC_DB_TITLE'][$i];
			$connection['Connection'] = new MySQLConnection($GLOBALS['SYNC_DB_HOST'][$i], $GLOBALS['SYNC_DB_NAME'][$i], $GLOBALS['SYNC_DB_USERNAME'][$i], $GLOBALS['SYNC_DB_PASSWORD'][$i]);
			$connection['Domain'] = $GLOBALS['SYNC_DB_DOMAIN'][$i];

			$GLOBALS['SYNC_CONNECTIONS'][] = $connection;
		}
	}

	return $GLOBALS['SYNC_CONNECTIONS'];
}

function getSyncConnectionsReversed() {
	if(is_null($GLOBALS['SYNC_CONNECTIONS_REVERSED'])) {
		$GLOBALS['SYNC_CONNECTIONS_REVERSED'] = array();

		$connections = getSyncConnections();

		for($i=count($connections)-1;$i>=0;$i--){
			$GLOBALS['SYNC_CONNECTIONS_REVERSED'][] = $connections[$i];
		}
	}

	return $GLOBALS['SYNC_CONNECTIONS_REVERSED'];
}

function checkDNSRecords($hostName, $recType = 'MX') {
	if(!empty($hostName)) {
		exec(sprintf('nslookup -type=%s %s', $recType, $hostName), $result);

		foreach($result as $line) {
			if(eregi(sprintF('^%s', $hostName), $line)) {
				return true;
			}
		}

		return false;
	}

	return false;
}

function xml2array($xml_data) {
	$xml_parser = xml_parser_create();
	xml_parser_set_option($xml_parser, XML_OPTION_CASE_FOLDING, 0);
	xml_parser_set_option($xml_parser, XML_OPTION_SKIP_WHITE, 1);
	xml_parse_into_struct($xml_parser, $xml_data, $vals, $index);
	xml_parser_free($xml_parser);

	$params = array();
	$ptrs[0] = &$params;

	foreach ($vals as $xml_elem) {
		$level = $xml_elem['level'] - 1;

		switch ($xml_elem['type']) {
			case 'open':
				$tag_or_id = (array_key_exists ('attributes', $xml_elem)) ? $xml_elem['attributes']['ID'] : $xml_elem['tag'];
				$ptrs[$level][$tag_or_id][] = array ();
				$ptrs[$level+1] = & $ptrs[$level][$tag_or_id][count($ptrs[$level][$tag_or_id])-1];
				break;

			case 'complete':
				$ptrs[$level][$xml_elem['tag']] = (isset ($xml_elem['value'])) ? $xml_elem['value'] : '';
				break;
		}
	}
	return ($params);
}

function orderAlphabetically($str1, $str2) {
	$limit = (strlen($str1) > strlen($str2)) ? strlen($str2) : strlen($str1);

   for($i=0; $i<$limit; $i++) {
      if(substr($str1, $i, 1) > substr($str2, $i, 1)) {
         return 1;
      } elseif(substr($str1, $i, 1) < substr($str2, $i, 1)) {
         return -1;
      }
   }

   if (strlen($str1) > strlen($str2)) {
      return 1;
   } elseif(strlen($str1) < strlen($str2)) {
      return -1;
   }

   return 0;
}

function secondsToString($seconds) {
	$string = '';

	if($seconds < 60) {
		$string = sprintf('%s seconds', $seconds);
	} else {
		$minutes = floor($seconds / 60);

		if($minutes < 60) {
			$string = sprintf('%s minutes', $minutes);
		} else {
			$hours = floor($minutes / 60);

			if($hours < 24) {
				$string = sprintf('%s hours', $hours);
			} else {
				$days = floor($hours / 24);

				$string = sprintf('%s days', $days);
			}
		}
	}

	return $string;
}

function getDuration($seconds) {
	$minutes = floor($seconds / 60);
	$hours = floor($minutes / 60);

	$minutes = $minutes % 60;
	$seconds = $seconds % 60;

	$hours = ($hours < 10) ? sprintf('0%d', $hours) : $hours;
	$minutes = ($minutes < 10) ? sprintf('0%d', $minutes) : $minutes;
	$seconds = ($seconds < 10) ? sprintf('0%d', $seconds) : $seconds;

	return sprintf('%s:%s:%s', $hours, $minutes, $seconds);
}

function getLoad() {
	return 0;
	
	$os = strtolower(PHP_OS);
	
    if (strpos($os, 'win') === false) {
        if (file_exists("/proc/loadavg")) {
            $data = file_get_contents("/proc/loadavg");
            $load = explode(' ', $data);
            
            return $load[0];

        } elseif (function_exists("shell_exec")) {
            $load = explode(' ', `uptime`);

            return $load[count($load)-3];

        } else {
        	return false;
        }
    }
}

function isHtml($string) {
	return preg_match('/<([\w]+)([\s]*)([^>]+)>/', $string);	
}

function date_diff_days($date1, $date2) { 
    $current = strtotime($date1); 
    $datetime2 = strtotime($date2); 
    $count = 0; 
    while($current < $datetime2){ 
        $current = strtotime(date("Y-m-d", strtotime("+1 day", $current)));
        $count++; 
    } 
    return $count; 
}

function getDirectionalCategories(array $ids, $down = true) {
	$categories = array();
	$return = $ids;
	
	$data = new DataQuery(sprintf("SELECT %s AS ID FROM product_categories WHERE %s IN (%s)", ($down) ? 'Category_ID' : 'Category_Parent_ID', ($down) ? 'Category_Parent_ID' : 'Category_ID', implode(', ', $ids)));
	while($data->Row) {
		if($data->Row['ID'] > 0) {
			$categories[] = $data->Row['ID'];
		}
		
		$data->Next();
	}
	$data->Disconnect();
	
	if(!empty($categories)) {
		$return = array_merge($return, getDirectionalCategories($categories, $down));
	}
	
	return $return;
}

function getMailPart($mbox,$mid,$p,$partno) {
    global $htmlmsg,$plainmsg,$charset,$attachments;

    $data = ($partno) ? imap_fetchbody($mbox,$mid,$partno) : imap_body($mbox,$mid);

    if ($p->encoding==4)
        $data = quoted_printable_decode($data);
    elseif ($p->encoding==3)
        $data = base64_decode($data);

    $params = array();

    if (isset($p->parameters))
        foreach ($p->parameters as $x)
            $params[ strtolower( $x->attribute ) ] = $x->value;
    if (isset($p->dparameters))
        foreach ($p->dparameters as $x)
            $params[ strtolower( $x->attribute ) ] = $x->value;

    if (isset($params['filename']) || isset($params['name'])) {
        $filename = ($params['filename'])? $params['filename'] : $params['name'];
        $attachments[$filename] = $data;
    }

    elseif ($p->type==0 && $data) {
        if (strtolower($p->subtype)=='plain')
            $plainmsg .= trim($data) ."\n\n";
        else
            $htmlmsg .= $data . '<br /><br />';
        $charset = isset($params['charset']) ? $params['charset'] : "";
    }

    elseif ($p->type==2 && $data) {
        $plainmsg .= trim($data) ."\n\n";
    }

    if (isset($p->parts)) {
        foreach ($p->parts as $partno0=>$p2)
            getMailPart($mbox,$mid,$p2,$partno.'.'.($partno0+1));
    }
}

/*
	From Now Function
*/
	function fromNow($then, $sensitivity='d'){
		$sensitivities = array('i'=>1, 'h'=>2, 'd'=>3, 'w'=>4, 'm'=>5, 'y'=>6);
		// check and set sensitivity
		if(array_key_exists(strtolower($sensitivity), $sensitivities)){
			$sensitivity = $sensitivities[strtolower($sensitivity)];
		} else {
			$sensitivity = $sensitivities['d'];
		}
		
		$now = strtotime(now());
		$then = (is_numeric($then))?$then:strtotime($then);
		$diffSeconds = $now-$then; // the difference in seconds
		$isFuture = false;
		if($diffSeconds<0){
			$isFuture = true;
			$diffSeconds = 0-$diffSeconds;
		}
		$diffMinutes = $diffSeconds/60; // the difference in minutes
		$diffHours = $diffMinutes/60; // the difference in hours
		$diffDays = $diffHours/24; // the difference in days

		// Minutes
		if($sensitivity <= $sensitivities['i']){
			if($diffMinutes <= 1){
				return ($isFuture)?'In a moment':'A moment ago';
			} else if($diffMinutes <= 4){
				return ($isFuture)?'In a few minutes':'A few minutes ago';
			} else if($diffMinutes < 60){
				if($isFuture){
					return sprintf('In %s minutes', floor($diffMinutes)); 
				} else {
					return floor($diffMinutes) . ' minutes ago';
				}
			}
		}
		
		// Hours
		if($sensitivity <= $sensitivities['h']){
			if($diffHours < 12){
				$output = '';
				if($diffMinutes < 60){
					return ($isFuture)?'In under an hour':'Under an hour ago';
				} else if($diffMinutes < 65){
					return ($isFuture)?'In about an hour':'About an hour ago';
				} else{
					$hours = floor($diffHours);
					$mins = round($diffMinutes, 0) - ($hours*60);
					$output = sprintf('%s hour%s', $hours, (($hours>1)?'s':''));
					if($sensitivity < $sensitivities['h'] && $mins > 0){
						$output = sprintf('%s %s minute%s', $output, $mins, (($mins>1)?'s':''));
					}
					$output = ($isFuture)?'In ' . $output: $output . ' ago';
					return $output;
				}
			}
		}
		
		// Days
		if($sensitivity <= $sensitivities['d']){
			$time =  ($sensitivity < $sensitivities['d'])?' ' . date('H:i', $then): '';
			if(date('Y-m-d', $now) == date('Y-m-d', $then)){
				return 'Today' . $time;
			} else if(strtotime('yesterday') == strtotime(date('Y-m-d', $then))){
				return 'Yesterday' . $time;
			} else if(strtotime('tomorrow') == strtotime(date('Y-m-d', $then))){
				return 'Tomorrow' . $time;
			} else if(($diffDays < 7)){
				$prefix = ($isFuture)?'On ':'';
				return $prefix . date('l', $then) . $time;
			} 
		}
		
		// Weeks
		if($sensitivity == $sensitivities['w']){
			$weeks = floor($diffDays/7);
			$days = $diffDays%7;
			if(date('W Y', $now) == date('W Y', $then)){
				return 'This week';
			} else if(date('W Y', strtotime('last week')) == (date('W Y', $then))){
				return 'Last week';
			} else if(date('W Y', strtotime('next week')) == (date('W Y', $then))){
				return 'Next week';
			} else if($weeks < 4){
				$output = sprintf('%s week%s', $weeks, (($weeks>1)?'s':''));
				if($days > 0){
					$output = sprintf('%s %s day%s', $output, $days, (($days>1)?'s':''));
				}
				if($isFuture){
					return 'In ' . $output;
				} else {
					return $output . ' ago';
				}
			}  
		}
		
		// Months
		if($sensitivity == $sensitivities['m']){
			if(date('m Y', $now) == date('m Y', $then)){
				return 'This month';
			} else if(date('m Y', strtotime('last month')) == date('m Y', $then)){
				return 'Last month';
			} else if(date('m Y', strtotime('next month')) == date('m Y', $then)){
				return 'Next month';
			} else if(date('Y', $now) == date('Y', $then)) {
				return date('M', $then);
			} else {
				return date('M Y', $then);
			}
		}
		
		// Years
		if($sensitivity == $sensitivities['y']){
			if(date('Y', $now) == date('Y', $then)) {
				return 'This year';
			} elseif (date('Y', $now) == (date('Y', $then)+1)){
				return 'Last year';
			} elseif (date('Y', $now) == (date('Y', $then)-1)){
				return 'Next year';
			} else {
				return date('Y', $then);
			}
		}
		
		if(date('Y', $now) == date('Y', $then)) {
			return date('jS M', $then);
		}
		return date($GLOBALS['DATE_FORMAT_SHORT'], $then);
	}
	
// Replacment for $_REQUEST[] which is: shorter, checkes for existance, and
// has no square brackets.
function param($name, $default=null) {
	if (!is_array($name)) {
		$name = array($name);
	}
	$param = $default;
	$use = array();
	if (isset($GLOBALS['ROUTE_PARAMS'][$name[0]])) {
		$use = $GLOBALS['ROUTE_PARAMS'];
	}
	else if (isset($_POST[$name[0]])) {
		$use = $_POST;
	}
	else if (isset($_GET[$name[0]])) {
		$use = $_GET;
	}
	else if (isset($_REQUEST[$name[0]])) {
		$use = $_REQUEST;
	}
	foreach ($name as $n) {
		if (!isset($use[$n])) {
			return $default;
		}
		$use = $use[$n];
	}
	return $use;
}

function id_param($name, $default=null) {
	$value = param($name);
	return is_numeric($value) ? $value : $default;
}

function tag_build($element, $attrs, $close=true) {
	$attrHTML = array();

	foreach ($attrs as $name=>$values) {
		if (!$values) {
			continue;
		}

		if (is_array($values)) {
			$values = join(' ', $values);
		}
		$attrHTML[] = "{$name}=\"{$values}\"";
	}

	$attrHTML = join(' ', $attrHTML);
	$attrHTML = $attrHTML ? ' ' . $attrHTML : '';
	$slash = $close ? ' /' : '';
	return "<{$element}{$attrHTML}{$slash}>";
}

function element_tag_build($element, $attrs, $content) {
	$tag = tag_build($element, $attrs, false);
	return "{$tag}{$content}</{$element}>";
}

function render_partial($name, $view=array()) {
	global $session;
	extract($view);
	ob_start();
	require("{$name}_partial.php");
	$body_content = ob_get_clean();
	
	echo $body_content;
}

function srender_partial($name, $view=array()) {
	ob_start();
	render_partial($name, $view);
	return ob_get_clean();
}

function render_partials($name, $collection, $each, $view = array()) {
	global $session;
	extract($view);
	$iterator = $each.'_num';
	$num = 0;
	foreach ($collection as $$each) {
		$$iterator = $num;
		require("{$name}_partial.php");
		$num++;
	}
}

function srender_partials($name, $collection, $each, $view = array()) {
	ob_start();
	render_partials($name, $collection, $each, $view);
	return ob_get_clean();
}


// Much like array_sum combined with pluck. Looks up an index for each item
// in the outer array and sums those values.
function arraySumInner($a, $index) {
	$sum = 0;
	foreach ($a as $x) {
		if (is_array($x) && isset($x[$index])) {
			$sum += $x[$index];
		}
		else if (isset($x->$index)) {
			$sum += $x->$index;
		}
	}
	return $sum;
}

function array_avg($a) {
	return array_sum($a) / count($a);
}

function arrayAvgInner($a, $index) {
	return arraySumInner($a, $index) / count($a);
}

function mkdate($m=null,$d=null,$y=null) {
	$m = $m ? $m : date('n');
	$d = $d ? $d : date('j');
	$y = $y ? $y : date('Y');
	return mktime(0,0,0, $m,$d,$y);
}

function ukstrtotime($str) {
	return mkdate(substr($str, 3, 2), substr($str, 0, 2), substr($str, 6, 4));
}

function sqlDate($timestamp) {
	return date('Y-m-d H:i:s', $timestamp);
}
