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

	//Remove ] from the end
	$match = substr($match, 0, -1);
	//Remove [filterrss
	$match = substr($match, 10);

	$known_fileds = array('pubDate', 'title', 'description', 'link');
	$opposite_signs = array('>' => '<', '<' => '>', '>=' => '<=', '<=' => '>=');

	$query = preg_split('/order by/i', $match);

	$args = trim($query[0]);

	if( isset( $query[1] ) )
	{
	    $sort = trim($query[1]);
	    //ASC ist't isteresting
	    $sort = str_ireplace('asc', '', $sort);

	    $desc = false;
	    if(stripos($sort, 'desc') !== false)
	    {
		$sort = str_ireplace('desc', '', $sort);
		$desc = true;
	    }

	    //check if we define limit
	    //I believe this's enough
	    $limit = 99999999;
	    $limit_reg = '/limit\s*([0-9]*)/i';
	    if(preg_match($limit_reg, $sort, $matches) )
	    {
		$limit = (int)$matches[1];
		$sort = preg_replace($limit_reg, '', $sort);
	    }

	    $order_by = trim($sort);
	} else
	{
	    //check if we define limit
	    //I believe this's enough
	    $limit = 99999999;
	    $limit_reg = '/limit\s*([0-9]*)\s*$/i';
	    if(preg_match($limit_reg, $args, $matches) )
	    {
		$limit = (int)$matches[1];
		$query = preg_replace($limit_reg, '', $query);
	    }

	}

	$exploded = explode(' ', $args);
	$url = $exploded[0];

	//we have no arguments
	if(count($exploded) < 3)
	{
	    return array('url' => $url, 'conditions' => array(), 'order_by' => $order_by, 'desc' => $desc, 'limit' => $limit);
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
	return array('url' => $url, 'conditions' => $cond_output, 'order_by' => $order_by, 'desc' => $desc, 'limit' => $limit);
    }

    function render($mode, &$renderer, $data) {
        if($mode == 'xhtml') {

	    $filterrss =& plugin_load('helper', 'filterrss');

	    $rss = simplexml_load_file($data['url']);
	    $rss_array = array();

	    if($rss)
	    {
		$items = $rss->channel->item;
		$items_count = 0;
		foreach($items as $item)
		{
		    if( $items_count >= $data['limit'])
			break;
		    $items_count++;
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
			$entry = array();
			$entry['title'] = $item->title;
			$entry['link'] = $item->link;
			$entry['pubDate'] = strtotime($item->pubDate);
			$entry['description'] = $item->description;
			
			array_push($rss_array, $entry);

		    }
		}
		if(!empty($data['order_by']))
		{
		    switch($data['order_by'])
		    {
			case 'pubDate':
			    $rss_array = $filterrss->int_sort($rss_array, $data['order_by']);
			break;
			case 'title':
			case 'description':
			case 'link':
			    $rss_array = $filterrss->nat_sort($rss_array, $data['order_by']);
			break;
		    }
		    if($data['desc'])
		    {
			$rss_array = array_reverse($rss_array);
		    } 
		}
		foreach($rss_array as $entry)
		{
		    $renderer->doc .= '<div class="filterrss_plugin">';
		    $renderer->doc .= '<a href="'.$entry['link'].'">'.$entry['title'].'</a><br>';
		    $renderer->doc .= '<span>'.date('d.m.Y',$entry['pubDate']).'</span>';
		    if($this->getConf('bbcode') == true)
		    {
			$renderer->doc .= '<p>'.$filterrss->bbcode_parse($entry['description']).'</p>';
		    } else
		    {
			$renderer->doc .= '<p>'.$entry['description'].'</p>';
		    }
		    $renderer->doc .= '</div>';
		}
	    } else
	    {
		$renderer->doc .= 'Cannot load rss feed.';
	    }
	    return true;
        } elseif ($mode == "metadata") {
	    $renderer->meta['plugin_filterrss']['purge'] = true;
	}
        return false;
    }
}
