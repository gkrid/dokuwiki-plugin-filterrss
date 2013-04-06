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
class syntax_plugin_filterrss extends DokuWiki_Syntax_Plugin {

    function getPType(){
       return 'block';
    }

    function getType() { return 'substition'; }
    function getSort() { return 32; }


    function connectTo($mode) {
	$this->Lexer->addSpecialPattern('\[filterrss.*?\]',$mode,'plugin_filterrss');
    }

    function handle($match, $state, $pos, &$handler) {

	$known_fileds = array('pubDate', 'title', 'description', 'link');
	$opposite_signs = array('>' => '<', '<' => '>', '>=' => '<=', '<=' => '>=');

	$exploded = explode(' ', $match);
	$url = $exploded[1];

	//we have no arguments
	if(count($exploded) < 3)
	{
	    //Remove ] from the end
	    $url = substr($url, 0, -1);
	    return array('url' => $url, 'conditions' => array());
	}
	array_shift($exploded);
	array_shift($exploded);


	$conditions = implode('', $exploded);
	
	//Remove ] from the end
	$conditions = substr($conditions, 0, -1);

	$cond_array = explode('&&', $conditions);

	$cond_output = array();

	foreach($cond_array as $cond)
	{
	    preg_match('/(.*?)(>|<|=|>=|<=)+(.*)/', $cond, $res);
	    if(in_array($res[1], $known_fileds))
	    {
		$name = $res[1];
		$value = $res[3];
		$sign = $res[2];
	    } elseif(in_array($res[3], $known_fileds))
	    {
		$name = $res[3];
		$value = $res[1];
		$sign = $opposite_signs[$res[2]];
	    } else
	    {
		continue;
	    }

	    //remove "" and ''
	    $value = str_replace(array('"', "'"), '', $value);

	    if(!isset($cond_output[$name]))
		$cond_output[$name] = array();

		array_push($cond_output[$name], array($sign, $value));
	}
	return array('url' => $url, 'conditions' => $cond_output);
    }

    function render($mode, &$renderer, $data) {
        if($mode == 'xhtml') {

	    $filterrss =& plugin_load('helper', 'filterrss');

	    $rss = simplexml_load_file($data['url']);
	    if($rss)
	    {
		$items = $rss->channel->item;
		foreach($items as $item)
		{
		    $jump_this_entry = false;
		    foreach($data['conditions'] as $entry => $conditions)
		    {
			switch($entry)
			{
			    case 'pubDate':
				foreach($conditions as $comparison)
				{
				    $left = strtotime($item->$entry);
				    $right = strtotime($comparison[1]);
				    switch($comparison[0])
				    {
					case '>':
					    if(!($left > $right))
					    {
						$jump_this_entry = true;
						break;
					    }
					break;
					case '<':
					    if(!($left < $right))
					    {
						$jump_this_entry = true;
						break;
					    }
					break;
					case '>=':
					    if(!($left >= $right))
					    {
						$jump_this_entry = true;
						break;
					    }
					break;
					case '<=':
					    if(!($left <= $right))
					    {
						$jump_this_entry = true;
						break;
					    }
					break;
					case '=':
					    if(!($left == $right))
					    {
						$jump_this_entry = true;
						break;
					    }
					break;
				    }
				}
			    break;
			    case 'title':
			    case 'description':
			    case 'link':
				foreach($conditions as $comparison)
				{
				    $subject = $item->$entry;

				    //simple regexp option
				    $pattern ='/'. str_replace('%', '.*', preg_quote($comparison[1])).'/';

				    switch($comparison[0])
				    {
					case '=':
					    if(!preg_match($pattern, $subject))
					    {
						$jump_this_entry = true;
						break;
					    }
					break;
				    }
				}
			    break;
			}

			if($jump_this_entry == true)
			    break;
		    }
		    if($jump_this_entry == false)
		    {
			$title = $item->title;
			$link = $item->link;
			$published_on = strtotime($item->pubDate);
			$description = $item->description;
			$renderer->doc .= '<div class="filterrss_plugin">';
			$renderer->doc .= '<a href="'.$link.'">'.$title.'</a><br>';
			$renderer->doc .= '<span>'.date('d.m.Y',$published_on).'</span>';


			if($this->getConf('bbcode') == true)
			{
			    $renderer->doc .= '<p>'.$filterrss->bbcode_parse($description).'</p>';
			} else
			{
			    $renderer->doc .= '<p>'.$description.'</p>';
			}
			$renderer->doc .= '</div>';
		    }
		}
	    } else
	    {
		$renderer->doc .= 'Cannot load rss feed.';
	    }
	    return true;
        }
        return false;
    }
}
