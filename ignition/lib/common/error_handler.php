<?php

require_once('Zend/Log.php');
require_once('Zend/Mail.php');
require_once('Zend/Log/Writer/Mail.php');
require('FirePHPCore/fb.php');

/*
	Global Error Log
*/
function createErrorLog() {
	$mail = new Zend_Mail();
	$mail->setFrom($GLOBALS['EMAIL_ERRORS_FROM'])->addTo($GLOBALS['EMAIL_DEVELOPER_LOG']);

	$writer = new Zend_Log_Writer_Mail($mail);

	// Set subject text for use; summary of number of errors is appended to the
	// subject line before sending the message.
	$writer->setSubjectPrependText(sprintf("Error report: %s", isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : sprintf('php %s', implode(' ', $_SERVER['argv']))));

	$log = new Zend_Log;
	$log->addWriter($writer);
	return $log;
}

$errorLog = createErrorLog();


/*
	Custom error handling.
*/
function evance_error_body($backtrace, $body='') {
		$errorText = $body ? "\n$body\n" : '';
		$errorText .= "<div style=\"max-height: 12em; overflow: hidden;\" onclick=\"if (this.style.maxHeight) { this.style.maxHeight=''; } else { this.style.maxHeight='12em'; }\">";

		$errorText .= "\nBACKTRACE\n";
		$errorText .= "=========\n";
		foreach($backtrace as $i=>$l){
			$argprint = array();

			if(isset($l['args'])){
				foreach ($l['args'] as $arg) {
					$val = is_object($arg) ? "class " . get_class($arg) : strval($arg);
					$argprint[] = $val;
				}
			}

			$argprint = join(', ', $argprint);
			$inclass = isset($l['class']) ? $l['class'] : '';
			$intype = isset($l['type']) ? $l['type'] : '';
			$infunction = isset($l['function']) ? $l['function'] : '';

			$errorText .= "[$i] {$inclass}{$intype}{$infunction}($argprint)\n";
			if(isset($l['file']) && $l['file']) $errorText .= "[{$l['file']}:{$l['line']}]";
			$errorText .= "\n\n";
		}

		$errorText .= "</div>\n";
		return $errorText;
}

function evance_error_support_message() {
	return "An error has occured with the website. If possible, please contact {$GLOBALS['EMAIL_SUPPORT']} for support, and to alert us to the issue.";
}

function evance_error_handler($errno, $errstr, $errfile, $errline, $body=''){
	global $errorLog;

	$errno = $errno & error_reporting();
	if($errno == 0) return;

	// Limit the number of errors shown on a single page.
	static $error_count = 0;
	$error_count++;
	$full_error = true;

	if ($error_count > (isset($GLOBALS['MAX_ERROR_TRACES']) ? $GLOBALS['MAX_ERROR_TRACES'] : 5)) {
		$full_error = false;
	}

	if(!defined('E_STRICT'))            define('E_STRICT', 2048);
	if(!defined('E_RECOVERABLE_ERROR')) define('E_RECOVERABLE_ERROR', 4096);

	// Capture all output. We may not want to print it to the screen.
	$errorText = '';

	$errorText .= "<pre>\n<b>";
	switch($errno){
		case E_ERROR:               $errorText .= "Error";                  break;
		case E_WARNING:             $errorText .= "Warning";                break;
		case E_PARSE:               $errorText .= "Parse Error";            break;
		case E_NOTICE:              $errorText .= "Notice";                 break;
		case E_CORE_ERROR:          $errorText .= "Core Error";             break;
		case E_CORE_WARNING:        $errorText .= "Core Warning";           break;
		case E_COMPILE_ERROR:       $errorText .= "Compile Error";          break;
		case E_COMPILE_WARNING:     $errorText .= "Compile Warning";        break;
		case E_USER_ERROR:          $errorText .= "User Error";             break;
		case E_USER_WARNING:        $errorText .= "User Warning";           break;
		case E_USER_NOTICE:         $errorText .= "User Notice";            break;
		case E_STRICT:              $errorText .= "Strict Notice";          break;
		case E_RECOVERABLE_ERROR:   $errorText .= "Recoverable Error";      break;
		default:                    $errorText .= "Unknown error ($errno)"; break;
	}
	$errorText .= ":</b> <i>$errstr</i> in <b>$errfile</b> on line <b>$errline</b>\n\n";
	if($full_error && function_exists('debug_backtrace')){
		$backtrace = debug_backtrace();
		array_shift($backtrace);
		array_shift($backtrace);

		$errorText .= evance_error_body($backtrace, $body);
	}
	$errorText .= "</pre>\n";

	// Display or log errors.
	if (ini_get('display_errors')) {
		echo $errorText;
	}

	if (ini_get('log_errors')) {
		if($errorLog) {
			$errorLog->err(strip_tags($errorText));
		}
	}


	// Die on fatal errors.
	if(isset($GLOBALS['error_fatal'])){
		if($GLOBALS['error_fatal'] & $errno) die(evance_error_support_message());
	}
}

function evance_exception_handler($e) {
	$cls = get_class($e);
	$code = $e->getCode() ? " (" . $e->getCode() . ")" : '';
	$msg = $e->getMessage();
	$line = $e->getLine();
	$file = $e->getFile();
	
	$errorText = "<pre>\n";
	$errorText .= "<b>{$cls}{$code}:</b> <i>{$msg}</i> in {$file} on line {$line}\n";
	$errorText .= evance_error_body($e->getTrace());
	$errorText .= "</pre>\n";

	// Display or log errors.
	if (ini_get('display_errors')) {
		echo $errorText;
	}

	if (ini_get('log_errors')) {
		if($errorLog) {
			$errorLog->err(strip_tags($errorText));
		}
	}

	// Die on fatal errors.
	echo evance_error_support_message();
}

function evance_php_error_handler($errno, $errstr, $errfile, $errline){
	evance_error_handler($errno, $errstr, $errfile, $errline);
}

function error_fatal($mask = NULL){
    if(!is_null($mask)){
        $GLOBALS['error_fatal'] = $mask;
    }
    return $GLOBALS['error_fatal'];
}

// Always use our custom error handler. Live site might log errors.
set_error_handler('evance_php_error_handler');
set_exception_handler('evance_exception_handler');
error_reporting(E_ALL);

if (DEVELOPER) {
	// Force displaying of errors even on live sites when in DEVELOPER mode.
	ini_set('display_errors', 1);
	// Switch off email logging if we are seeing the errors anyway.
	ini_set('log_errors', 0);
	error_fatal(E_ALL);
} else {
	ini_set('display_errors', 1);
	ini_set('log_errors', 1);
	
	// Disable FirePHP.
	FB::setEnabled(false);
	error_fatal(E_ALL^E_NOTICE);
}
