<?php

require_once dirname($_SERVER['DOCUMENT_ROOT']).'/www_stuff.php';

// Return a multidimensional array of the top navigation elements
	function get_top_navigation($visible = true) {
		global $db;
		$raw_nav = $db->query_keyed_list_assoc('id', '
				SELECT column_list
				  FROM table_name
				 ' . ( $visible ? 'WHERE visible = 1' : '' ) . '
			  ORDER BY parent ASC, sort_order ASC
		');
		
		$nav = array();
		
	// Pre-clear children arrays
		foreach($raw_nav as $id => $row)
			$raw_nav[$id]['children'] = array();
	// Create a link between parent and child
		foreach($raw_nav as $id => $row)
			if($row['parent'])
				$raw_nav[$row['parent']]['children'][$id] = true;
	// Append children to their parents
		foreach($raw_nav as $id => $menu) {
		// Only process root nodes
			if($menu['parent'])
				continue;
			$menu_children = array_keys($menu['children']);
			$menu['children'] = array();
		// ... submenu is kind of misnamed here...
			foreach($menu_children as $submenu_id) {
				$submenu = $raw_nav[$submenu_id];
				$submenu_children = array_keys($submenu['children']);
				$submenu['children'] = array();

				foreach($submenu_children as $subsubmenu_id) {
					$submenu['children'][] = $raw_nav[$subsubmenu_id];
				// Remove children from the tree
					unset($raw_nav[$subsubmenu_id]);
				} // end foreach
				$menu['children'][] = $submenu;
				unset($raw_nav[$submenu_id]);
			} // end foreach
			$nav[] = $menu;
		} // end foreach
	// Should probably check the raw array at this point to make sure that all
	// of the children got reparented.  Meh.
        return $nav;
	}
	
function returnNavArray (){

    $old_array = get_top_navigation();

    $navigation_array = ['<img src="/logo_path.png"  itemtype="http://schema.org/Organization" width="180px" itemprop="logo" alt="alt image" title="Image title">', "/"];

    $navigation_array = arrayConversion($old_array, $navigation_array);

    return $navigation_array;
}

function arrayConversion($source_array, $target_array){
    if ($source_array == null || count($source_array) == 0){
        return "";
    }

    if ($target_array == null){
        $target_array = array();
    }
    foreach ($source_array as $key => $item) {
        $tmp_array = array();
        $tmp_array[0] = $item['name'];
        // PRINT "NAME: " . $item['name'];
        $tmp_array[1] = $item['href'];
        $array_count = 2;
        if ($item != null) {
            if (array_key_exists('children', $item)) {
                if ($item['children'] != null) {
                    $tmp_array[$array_count] = array();
                    $tmp_array = arrayConversion($item['children'], $tmp_array);
                }
            }
        }
        if (count($tmp_array) > 0){
            array_push($target_array, $tmp_array);
        }
    }

    return $target_array;
}


function fullLinkName($name) {
    return dirname($_SERVER['DOCUMENT_ROOT']). $name;
}

function buildHeaderNav($formatting = "") {
    $my_array = returnNavArray();
    $text = "";
    $arrayCount = count($my_array);
    $menu_links = nextNavLayer($my_array);
    $text .= <<< HTML
        <div class="pure-menu pure-menu-custom-2 pure-menu-horizontal">
            <ul class="pure-menu-list">
                $menu_links
            <li class="pure-menu-item"><a href="#" class="pure-menu-link mobile_hide">&#160;</a></li>
            </ul>
        </div>
HTML;

    $text .= "</div>";
    return $text;
}

function nextNavLayer($current_array){
    $thing = "";
    foreach ($current_array as $key => $item){
        if ($key == 0 || $key == 1 || count($item) == 0) {
            continue;
        }
        $title = $item[0];
        $link = $item[1];
        if (array_key_exists(3, $item)){
            $inner_text = nextNavLayer($item);
            $thing .= <<< HTML
            <li class="pure-menu-item pure-menu-has-children pure-menu-allow-hover mobile-collapse-able"><img class="touch_visible" src="/img/frontpage/expand.png"><span class="small_visible menu_expand_font">Show $title</span><a href="$link" class="pure-menu-link">$title</a><span class="small_visible"></br></span>
                <ul class="pure-menu-children">
                    $inner_text
                </ul>
            <br class="mobile_br">
HTML;
        } else {
            $thing .= <<< HTML
            <li class="pure-menu-item"><a href="$link" class="pure-menu-link">$title</a></li><br class="mobile_br">
HTML;
        }
    }
    return $thing;
}



function buildFooterNav($formatting = "") {
    $current_array = returnNavArray();
    $thing = '<div class="footerNavTable padded_80_on_full" style="max-height: 20px;"><div class="footerHeadNavRow"><div class="footerNavCell"><a href="' . $current_array[1] . '">' . $current_array[0] . '</a></div></div><div class="footerNavRow">';
    foreach ($current_array as $key => $item){
        if ($key == 0 || $key == 1) {
            continue;
        }
        $title = $item[0];
        $link = $item[1];
        $second_array = $item;
        $inner_text = "";
        foreach ($second_array as $second_key => $second_item){
            if ($second_key == 0 || $second_key == 1 || count($second_item) == 0){
                continue;
            }
            $inner_text .= '<span><a href="' . $second_item[1] . '" class="footerLink">' . $second_item[0] . "</a></span>";
        }

        $thing .= <<< HTML
        <div class="footerNavCell"><a href="$link" class="footerLink" style="font-weight: bold;">$title</a>
                $inner_text
        </div>
HTML;
    }
    $thing .= "</div></div>";
    return $thing;
}

/* Formatting 0 returns as simple links. Otherwise, formatting can be set to a string which will add internal formatting. For example, send a "id='myFancyLinks" to set them all to have that id.*/
function returnNavAsLinks($an_array, $formatting = 0){
    $text = "";
    $name = "";
    $link = "";
    $this_array = $an_array;
    foreach ($this_array as $key => $value){
        if ($key == 0){
            continue;
        } else if ($key == 1){
            $link = $value;
            if ($formatting == 0) {
                $text .= getLink($this_array);
            } else {
                $text .= getLink($this_array, $formatting);
            }
            continue;
        } else if ($key < count($this_array)){
            PRINT "TEST";
            $passArray = $this_array[$key];
            $text .= returnNavAsLinks($passArray, $formatting);

        } else {
            break;
        }
    }
    return $text;
}

function returnNavAsNestedLinks($an_array, $formatting = "Null"){
    $text = "<div>";
    $name = "";
    $link = "";
    $this_array = $an_array;
    foreach ($this_array as $key => $value){
        if ($key == 0){
            continue;
        } else if ($key == 1){
            $link = $value;
            if ($formatting == "Null") {
                PRINT $formatting;
                $text .= getLink($this_array);
            } else {
                $text .= getLink($this_array, $formatting);
            }
            continue;
        } else if ($key < count($this_array)){
            $pass_array = $this_array[$key];
            $text .= returnNavAsNestedLinks($pass_array, $formatting);

        } else {
            break;
        }
    }
    return $text . "</div>";
}


function getLink ($the_array, $internal_formatting = ""){
    $my_string = "<a href='". $the_array[1] . "' " . $internal_formatting . ">" . $the_array[0] . "</a> ";
    return $my_string;
}

function getSubArray ($the_array, $which_one = 2){
    return $the_array[$which_one];
}