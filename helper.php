<?php
// must be run within DokuWiki
if(!defined('DOKU_INC')) die();

if(!defined('DOKU_PLUGIN')) define('DOKU_PLUGIN',DOKU_INC.'lib/plugins/');
require_once DOKU_PLUGIN.'syntax.php';

/**
 * All DokuWiki plugins to extend the parser/rendering mechanism
 * need to inherit from this class
 */
class helper_plugin_filterrss extends dokuwiki_plugin
{
    function getMethods(){
      $result = array();
      $result[] = array(
	'name'   => 'bbcode_parse',
	'desc'   => 'parse bbcode to html',
	'params' => array('bbcode_input' => 'string'),
	'return' => array('html_output' => 'string'),
      );
      $result[] = array(
	'name'   => 'int_sort',
	'desc'   => 'numeric sort assoc array using key passed in the second argument',
	'params' => array('array' => 'array', 'key' => 'string'),
	'return' => array('sorted_array' => 'array'),
      );
      $result[] = array(
	'name'   => 'nat_sort',
	'desc'   => 'natural sort assoc array using php strnatcmp function and key passed in the second argument',
	'params' => array('array' => 'array', 'key' => 'string'),
	'return' => array('sorted_array' => 'array'),
      );
    }
    function bbcode_parse($bbcode_input)
    {
	$bbcode = array("<", ">",
		    "[list]", "[*]", "[/list]", 
		    "[img]", "[/img]", 
		    "[b]", "[/b]", 
		    "[u]", "[/u]", 
		    "[i]", "[/i]",
		    '[color=', "[/color]",
		    "[size=", "[/size]",
		    '[url=', "[/url]",
		    "[mail=", "[/mail]",
		    "[code]", "[/code]",
		    "[quote]", "[/quote]",
		    ']');
	$htmlcode = array("&lt;", "&gt;",
		    "<ul>", "<li>", "</ul>", 
		    "<img src=\"", "\">", 
		    "<b>", "</b>", 
		    "<u>", "</u>", 
		    "<i>", "</i>",
		    "<span style=\"color:", "</span>",
		    "<span style=\"font-size:", "</span>",
		    '<a href="', "</a>",
		    "<a href=\"mailto:", "</a>",
		    "<code>", "</code>",
		    "<blockquote>", "</blockquote>",
		    '">');
	$html_output = str_replace($bbcode, $htmlcode, $bbcode_input);
	$html_output = nl2br($html_output);//second pass
	return $html_output;
    }
    //Key using in curret cmp function 
    protected static $key = '';
    protected function nat_key_cmp($a_ar, $b_ar)
    {
	$a = $a_ar[self::$key];
	$b = $b_ar[self::$key];
	return strnatcmp($a, $b);
    }
    protected function int_key_cmp($a_ar, $b_ar)
    {
	$a = $a_ar[self::$key];
	$b = $b_ar[self::$key];
	if ($a == $b) {
	    return 0;
	}
	return ($a < $b) ? -1 : 1;
    }
    function int_sort($array, $key)
    {
	self::$key = $key;
	usort($array, 'self::int_key_cmp');
	return $array;
    }
    function nat_sort($array, $key)
    {
	self::$key = $key;
	usort($array, 'self::nat_key_cmp');
	return $array;
    }
}

