<?php
/**
 * Plugin Now: Inserts a timestamp.
 * 
 * @license    GPL 3 (http://www.gnu.org/licenses/gpl.html)
 * @author     Szymon Olewniczak <szymon.olewniczak@rid.pl>
 */

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
}

