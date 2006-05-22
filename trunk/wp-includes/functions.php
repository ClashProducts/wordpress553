<?php

require_once(dirname(__FILE__).'/functions-compat.php');

if ( !function_exists('_') ) {
	function _($string) {
		return $string;
	}
}

function get_profile($field, $user = false) {
	global $wpdb;
	if ( !$user )
		$user = $wpdb->escape($_COOKIE[USER_COOKIE]);
	return $wpdb->get_var("SELECT $field FROM $wpdb->users WHERE user_login = '$user'");
}

function mysql2date($dateformatstring, $mysqlstring, $translate = true) {
	global $wp_locale;
	$m = $mysqlstring;
	if ( empty($m) ) {
		return false;
	}
	$i = mktime(substr($m,11,2),substr($m,14,2),substr($m,17,2),substr($m,5,2),substr($m,8,2),substr($m,0,4));

	if( 'U' == $dateformatstring )
		return $i;
	
	if ( -1 == $i || false == $i )
		$i = 0;

	if ( !empty($wp_locale->month) && !empty($wp_locale->weekday) && $translate ) {
		$datemonth = $wp_locale->get_month(date('m', $i));
		$datemonth_abbrev = $wp_locale->get_month_abbrev($datemonth);
		$dateweekday = $wp_locale->get_weekday(date('w', $i));
		$dateweekday_abbrev = $wp_locale->get_weekday_abbrev($dateweekday);
		$datemeridiem = $wp_locale->get_meridiem(date('a', $i));
		$datemeridiem_capital = $wp_locale->get_meridiem(date('A', $i));
		$dateformatstring = ' '.$dateformatstring;
		$dateformatstring = preg_replace("/([^\\\])D/", "\${1}".backslashit($dateweekday_abbrev), $dateformatstring);
		$dateformatstring = preg_replace("/([^\\\])F/", "\${1}".backslashit($datemonth), $dateformatstring);
		$dateformatstring = preg_replace("/([^\\\])l/", "\${1}".backslashit($dateweekday), $dateformatstring);
		$dateformatstring = preg_replace("/([^\\\])M/", "\${1}".backslashit($datemonth_abbrev), $dateformatstring);
		$dateformatstring = preg_replace("/([^\\\])a/", "\${1}".backslashit($datemeridiem), $dateformatstring);
		$dateformatstring = preg_replace("/([^\\\])A/", "\${1}".backslashit($datemeridiem_capital), $dateformatstring);

		$dateformatstring = substr($dateformatstring, 1, strlen($dateformatstring)-1);
	}
	$j = @date($dateformatstring, $i);
	if ( !$j ) {
	// for debug purposes
	//	echo $i." ".$mysqlstring;
	}
	return $j;
}

function current_time($type, $gmt = 0) {
	switch ($type) {
		case 'mysql':
			if ( $gmt ) $d = gmdate('Y-m-d H:i:s');
			else $d = gmdate('Y-m-d H:i:s', (time() + (get_settings('gmt_offset') * 3600)));
			return $d;
			break;
		case 'timestamp':
			if ( $gmt ) $d = time();
			else $d = time() + (get_settings('gmt_offset') * 3600);
			return $d;
			break;
	}
}

function date_i18n($dateformatstring, $unixtimestamp) {
	global $wp_locale;
	$i = $unixtimestamp;
	if ( (!empty($wp_locale->month)) && (!empty($wp_locale->weekday)) ) {
		$datemonth = $wp_locale->get_month(date('m', $i));
		$datemonth_abbrev = $wp_locale->get_month_abbrev($datemonth);
		$dateweekday = $wp_locale->get_weekday(date('w', $i));
		$dateweekday_abbrev = $wp_locale->get_weekday_abbrev($dateweekday);
		$datemeridiem = $wp_locale->get_meridiem(date('a', $i));
		$datemeridiem_capital = $wp_locale->get_meridiem(date('A', $i));
		$dateformatstring = ' '.$dateformatstring;
		$dateformatstring = preg_replace("/([^\\\])D/", "\${1}".backslashit($dateweekday_abbrev), $dateformatstring);
		$dateformatstring = preg_replace("/([^\\\])F/", "\${1}".backslashit($datemonth), $dateformatstring);
		$dateformatstring = preg_replace("/([^\\\])l/", "\${1}".backslashit($dateweekday), $dateformatstring);
		$dateformatstring = preg_replace("/([^\\\])M/", "\${1}".backslashit($datemonth_abbrev), $dateformatstring);
		$dateformatstring = preg_replace("/([^\\\])a/", "\${1}".backslashit($datemeridiem), $dateformatstring);
		$dateformatstring = preg_replace("/([^\\\])A/", "\${1}".backslashit($datemeridiem_capital), $dateformatstring);

		$dateformatstring = substr($dateformatstring, 1, strlen($dateformatstring)-1);
	}
	$j = @date($dateformatstring, $i);
	return $j;
}

function get_weekstartend($mysqlstring, $start_of_week) {
	$my = substr($mysqlstring,0,4);
	$mm = substr($mysqlstring,8,2);
	$md = substr($mysqlstring,5,2);
	$day = mktime(0,0,0, $md, $mm, $my);
	$weekday = date('w',$day);
	$i = 86400;

	if ( $weekday < get_settings('start_of_week') )
		$weekday = 7 - (get_settings('start_of_week') - $weekday);

	while ($weekday > get_settings('start_of_week')) {
		$weekday = date('w',$day);
		if ( $weekday < get_settings('start_of_week') )
			$weekday = 7 - (get_settings('start_of_week') - $weekday);

		$day = $day - 86400;
		$i = 0;
	}
	$week['start'] = $day + 86400 - $i;
	// $week['end'] = $day - $i + 691199;
	$week['end'] = $week['start'] + 604799;
	return $week;
}

function get_lastpostdate($timezone = 'server') {
	global $cache_lastpostdate, $pagenow, $wpdb;
	$add_seconds_blog = get_settings('gmt_offset') * 3600;
	$add_seconds_server = date('Z');
	if ( !isset($cache_lastpostdate[$timezone]) ) {
		switch(strtolower($timezone)) {
			case 'gmt':
				$lastpostdate = $wpdb->get_var("SELECT post_date_gmt FROM $wpdb->posts WHERE post_status = 'publish' ORDER BY post_date_gmt DESC LIMIT 1");
				break;
			case 'blog':
				$lastpostdate = $wpdb->get_var("SELECT post_date FROM $wpdb->posts WHERE post_status = 'publish' ORDER BY post_date_gmt DESC LIMIT 1");
				break;
			case 'server':
				$lastpostdate = $wpdb->get_var("SELECT DATE_ADD(post_date_gmt, INTERVAL '$add_seconds_server' SECOND) FROM $wpdb->posts WHERE post_status = 'publish' ORDER BY post_date_gmt DESC LIMIT 1");
				break;
		}
		$cache_lastpostdate[$timezone] = $lastpostdate;
	} else {
		$lastpostdate = $cache_lastpostdate[$timezone];
	}
	return $lastpostdate;
}

function get_lastpostmodified($timezone = 'server') {
	global $cache_lastpostmodified, $pagenow, $wpdb;
	$add_seconds_blog = get_settings('gmt_offset') * 3600;
	$add_seconds_server = date('Z');
	if ( !isset($cache_lastpostmodified[$timezone]) ) {
		switch(strtolower($timezone)) {
			case 'gmt':
				$lastpostmodified = $wpdb->get_var("SELECT post_modified_gmt FROM $wpdb->posts WHERE post_status = 'publish' ORDER BY post_modified_gmt DESC LIMIT 1");
				break;
			case 'blog':
				$lastpostmodified = $wpdb->get_var("SELECT post_modified FROM $wpdb->posts WHERE post_status = 'publish' ORDER BY post_modified_gmt DESC LIMIT 1");
				break;
			case 'server':
				$lastpostmodified = $wpdb->get_var("SELECT DATE_ADD(post_modified_gmt, INTERVAL '$add_seconds_server' SECOND) FROM $wpdb->posts WHERE post_status = 'publish' ORDER BY post_modified_gmt DESC LIMIT 1");
				break;
		}
		$lastpostdate = get_lastpostdate($timezone);
		if ( $lastpostdate > $lastpostmodified ) {
			$lastpostmodified = $lastpostdate;
		}
		$cache_lastpostmodified[$timezone] = $lastpostmodified;
	} else {
		$lastpostmodified = $cache_lastpostmodified[$timezone];
	}
	return $lastpostmodified;
}

function user_pass_ok($user_login,$user_pass) {
	global $cache_userdata;
	if ( empty($cache_userdata[$user_login]) ) {
		$userdata = get_userdatabylogin($user_login);
	} else {
		$userdata = $cache_userdata[$user_login];
	}
	return (md5($user_pass) == $userdata->user_pass);
}

function get_usernumposts($userid) {
	global $wpdb;
	return $wpdb->get_var("SELECT COUNT(*) FROM $wpdb->posts WHERE post_author = '$userid' AND post_type = 'post' AND post_status = 'publish'");
}

function maybe_unserialize($original) {
	if ( false !== $gm = @ unserialize($original) )
		return $gm;
	else
		return $original;
}

/* Options functions */

function get_settings($setting) {
	global $wpdb;

	$value = wp_cache_get($setting, 'options');

	if ( false === $value ) {
		if ( defined('WP_INSTALLING') )
			$wpdb->hide_errors();
		$row = $wpdb->get_row("SELECT option_value FROM $wpdb->options WHERE option_name = '$setting' LIMIT 1");
		if ( defined('WP_INSTALLING') )
			$wpdb->show_errors();

		if( is_object( $row) ) { // Has to be get_row instead of get_var because of funkiness with 0, false, null values
			$value = $row->option_value;
			wp_cache_set($setting, $value, 'options');
		} else {
			return false;
		}
	}

	// If home is not set use siteurl.
	if ( 'home' == $setting && '' == $value )
		return get_settings('siteurl');

	if ( 'siteurl' == $setting || 'home' == $setting || 'category_base' == $setting )
		$value = preg_replace('|/+$|', '', $value);

	return apply_filters( 'option_' . $setting, maybe_unserialize($value) );
}

function get_option($option) {
	return get_settings($option);
}

function get_user_option( $option, $user = 0 ) {
	global $wpdb;

	if ( empty($user) )
		$user = wp_get_current_user();
	else
		$user = get_userdata($user);

	if ( isset( $user->{$wpdb->prefix . $option} ) ) // Blog specific
		return $user->{$wpdb->prefix . $option};
	elseif ( isset( $user->{$option} ) ) // User specific and cross-blog
		return $user->{$option};
	else // Blog global
		return get_option( $option );
}

function form_option($option) {
	echo htmlspecialchars( get_option($option), ENT_QUOTES );
}

function get_alloptions() {
	global $wpdb, $wp_queries;
	$wpdb->hide_errors();
	if ( !$options = $wpdb->get_results("SELECT option_name, option_value FROM $wpdb->options WHERE autoload = 'yes'") ) {
		$options = $wpdb->get_results("SELECT option_name, option_value FROM $wpdb->options");
	}
	$wpdb->show_errors();

	foreach ($options as $option) {
		// "When trying to design a foolproof system,
		//  never underestimate the ingenuity of the fools :)" -- Dougal
		if ( 'siteurl' == $option->option_name )
			$option->option_value = preg_replace('|/+$|', '', $option->option_value);
		if ( 'home' == $option->option_name )
			$option->option_value = preg_replace('|/+$|', '', $option->option_value);
		if ( 'category_base' == $option->option_name )
			$option->option_value = preg_replace('|/+$|', '', $option->option_value);
		$value = maybe_unserialize($option->option_value);
		$all_options->{$option->option_name} = apply_filters('pre_option_' . $option->option_name, $value);
	}
	return apply_filters('all_options', $all_options);
}

function update_option($option_name, $newvalue) {
	global $wpdb;

	if ( is_string($newvalue) )
		$newvalue = trim($newvalue);

	// If the new and old values are the same, no need to update.
	$oldvalue = get_option($option_name);
	if ( $newvalue == $oldvalue ) {
		return false;
	}

	if ( false === $oldvalue ) {
		add_option($option_name, $newvalue);
		return true;
	}

	$_newvalue = $newvalue;
	if ( is_array($newvalue) || is_object($newvalue) )
		$newvalue = serialize($newvalue);

	wp_cache_set($option_name, $newvalue, 'options');

	$newvalue = $wpdb->escape($newvalue);
	$option_name = $wpdb->escape($option_name);
	$wpdb->query("UPDATE $wpdb->options SET option_value = '$newvalue' WHERE option_name = '$option_name'");
	if ( $wpdb->rows_affected == 1 ) {
		do_action("update_option_{$option_name}", array('old'=>$oldvalue, 'new'=>$_newvalue));
		return true;
	}
	return false;
}

function update_user_option( $user_id, $option_name, $newvalue, $global = false ) {
	global $wpdb;
	if ( !$global )
		$option_name = $wpdb->prefix . $option_name;
	return update_usermeta( $user_id, $option_name, $newvalue );
}

// thx Alex Stapleton, http://alex.vort-x.net/blog/
function add_option($name, $value = '', $description = '', $autoload = 'yes') {
	global $wpdb;

	// Make sure the option doesn't already exist
	if ( false !== get_option($name) )
		return;

	if ( is_array($value) || is_object($value) )
		$value = serialize($value);

	wp_cache_set($name, $value, 'options');

	$name = $wpdb->escape($name);
	$value = $wpdb->escape($value);
	$description = $wpdb->escape($description);
	$wpdb->query("INSERT INTO $wpdb->options (option_name, option_value, option_description, autoload) VALUES ('$name', '$value', '$description', '$autoload')");

	return;
}

function delete_option($name) {
	global $wpdb;
	// Get the ID, if no ID then return
	$option_id = $wpdb->get_var("SELECT option_id FROM $wpdb->options WHERE option_name = '$name'");
	if ( !$option_id ) return false;
	$wpdb->query("DELETE FROM $wpdb->options WHERE option_name = '$name'");
	wp_cache_delete($name, 'options');
	return true;
}

function add_post_meta($post_id, $key, $value, $unique = false) {
	global $wpdb, $post_meta_cache;

	if ( $unique ) {
		if ( $wpdb->get_var("SELECT meta_key FROM $wpdb->postmeta WHERE meta_key
= '$key' AND post_id = '$post_id'") ) {
			return false;
		}
	}

	$original = $value;
	if ( is_array($value) || is_object($value) )
		$value = $wpdb->escape(serialize($value));

	$wpdb->query("INSERT INTO $wpdb->postmeta (post_id,meta_key,meta_value) VALUES ('$post_id','$key','$value')");

	$post_meta_cache['$post_id'][$key][] = $original;

	return true;
}

function delete_post_meta($post_id, $key, $value = '') {
	global $wpdb, $post_meta_cache;

	if ( empty($value) ) {
		$meta_id = $wpdb->get_var("SELECT meta_id FROM $wpdb->postmeta WHERE
post_id = '$post_id' AND meta_key = '$key'");
	} else {
		$meta_id = $wpdb->get_var("SELECT meta_id FROM $wpdb->postmeta WHERE
post_id = '$post_id' AND meta_key = '$key' AND meta_value = '$value'");
	}

	if ( !$meta_id )
		return false;

	if ( empty($value) ) {
		$wpdb->query("DELETE FROM $wpdb->postmeta WHERE post_id = '$post_id'
AND meta_key = '$key'");
		unset($post_meta_cache['$post_id'][$key]);
	} else {
		$wpdb->query("DELETE FROM $wpdb->postmeta WHERE post_id = '$post_id'
AND meta_key = '$key' AND meta_value = '$value'");
		$cache_key = $post_meta_cache['$post_id'][$key];
		if ($cache_key) foreach ( $cache_key as $index => $data )
			if ( $data == $value )
				unset($post_meta_cache['$post_id'][$key][$index]);
	}

	unset($post_meta_cache['$post_id'][$key]);

	return true;
}

function get_post_meta($post_id, $key, $single = false) {
	global $wpdb, $post_meta_cache;

	if ( isset($post_meta_cache[$post_id][$key]) ) {
		if ( $single ) {
			return maybe_unserialize( $post_meta_cache[$post_id][$key][0] );
		} else {
			return maybe_unserialize( $post_meta_cache[$post_id][$key] );
		}
	}

	$metalist = $wpdb->get_results("SELECT meta_value FROM $wpdb->postmeta WHERE post_id = '$post_id' AND meta_key = '$key'", ARRAY_N);

	$values = array();
	if ( $metalist ) {
		foreach ($metalist as $metarow) {
			$values[] = $metarow[0];
		}
	}

	if ( $single ) {
		if ( count($values) ) {
			$return = maybe_unserialize( $values[0] );
		} else {
			return '';
		}
	} else {
		$return = $values;
	}

	return maybe_unserialize($return);
}

function update_post_meta($post_id, $key, $value, $prev_value = '') {
	global $wpdb, $post_meta_cache;

	$original_value = $value;
	if ( is_array($value) || is_object($value) )
		$value = $wpdb->escape(serialize($value));

	$original_prev = $prev_value;
	if ( is_array($prev_value) || is_object($prev_value) )
		$prev_value = $wpdb->escape(serialize($prev_value));

	if (! $wpdb->get_var("SELECT meta_key FROM $wpdb->postmeta WHERE meta_key
= '$key' AND post_id = '$post_id'") ) {
		return false;
	}

	if ( empty($prev_value) ) {
		$wpdb->query("UPDATE $wpdb->postmeta SET meta_value = '$value' WHERE
meta_key = '$key' AND post_id = '$post_id'");
		$cache_key = $post_meta_cache['$post_id'][$key];
		if ( !empty($cache_key) )
			foreach ($cache_key as $index => $data)
				$post_meta_cache['$post_id'][$key][$index] = $original_value;
	} else {
		$wpdb->query("UPDATE $wpdb->postmeta SET meta_value = '$value' WHERE
meta_key = '$key' AND post_id = '$post_id' AND meta_value = '$prev_value'");
		$cache_key = $post_meta_cache['$post_id'][$key];
		if ( !empty($cache_key) )
			foreach ($cache_key as $index => $data)
				if ( $data == $original_prev )
					$post_meta_cache['$post_id'][$key][$index] = $original_value;
	}

	return true;
}

// Retrieves post data given a post ID or post object.
// Handles post caching.
function &get_post(&$post, $output = OBJECT) {
	global $post_cache, $wpdb;

	if ( empty($post) ) {
		if ( isset($GLOBALS['post']) )
			$_post = & $GLOBALS['post'];
		else
			$_post = null;
	} elseif ( is_object($post) ) {
		if ( 'page' == $post->post_type )
			return get_page($post, $output);
		if ( !isset($post_cache[$post->ID]) )
			$post_cache[$post->ID] = &$post;
		$_post = & $post_cache[$post->ID];
	} else {
		if ( $_post = wp_cache_get($post, 'pages') )
			return get_page($_post, $output);
		elseif ( isset($post_cache[$post]) )
			$_post = & $post_cache[$post];
		else {
			$query = "SELECT * FROM $wpdb->posts WHERE ID = '$post' LIMIT 1";
			$_post = & $wpdb->get_row($query);
			if ( 'page' == $_post->post_type )
				return get_page($_post, $output);
			$post_cache[$post] = & $_post;
		}
	}

	if ( defined(WP_IMPORTING) )
		unset($post_cache);

	if ( $output == OBJECT ) {
		return $_post;
	} elseif ( $output == ARRAY_A ) {
		return get_object_vars($_post);
	} elseif ( $output == ARRAY_N ) {
		return array_values(get_object_vars($_post));
	} else {
		return $_post;
	}
}

function &get_children($post = 0, $output = OBJECT) {
	global $post_cache, $wpdb;

	if ( empty($post) ) {
		if ( isset($GLOBALS['post']) )
			$post_parent = & $GLOBALS['post']->post_parent;
		else
			return false;
	} elseif ( is_object($post) ) {
		$post_parent = $post->post_parent;
	} else {
		$post_parent = $post;
	}

	$post_parent = (int) $post_parent;

	$query = "SELECT * FROM $wpdb->posts WHERE post_parent = $post_parent";

	$children = $wpdb->get_results($query);

	if ( $children ) {
		foreach ( $children as $key => $child ) {
			$post_cache[$child->ID] =& $children[$key];
			$kids[$child->ID] =& $children[$key];
		}
	} else {
		return false;
	}

	if ( $output == OBJECT ) {
		return $kids;
	} elseif ( $output == ARRAY_A ) {
		foreach ( $kids as $kid )
			$weeuns[$kid->ID] = get_object_vars($kids[$kid->ID]);
		return $weeuns;
	} elseif ( $output == ARRAY_N ) {
		foreach ( $kids as $kid )
			$babes[$kid->ID] = array_values(get_object_vars($kids[$kid->ID]));
		return $babes;
	} else {
		return $kids;
	}
}

function get_page_by_path($page_path, $output = OBJECT) {
	global $wpdb;
	$page_path = rawurlencode(urldecode($page_path));
	$page_path = str_replace('%2F', '/', $page_path);
	$page_path = str_replace('%20', ' ', $page_path);
	$page_paths = '/' . trim($page_path, '/');
	$leaf_path  = sanitize_title(basename($page_paths));
	$page_paths = explode('/', $page_paths);
	foreach($page_paths as $pathdir)
		$full_path .= ($pathdir!=''?'/':'') . sanitize_title($pathdir);

	$pages = $wpdb->get_results("SELECT ID, post_name, post_parent FROM $wpdb->posts WHERE post_name = '$leaf_path' AND post_type='page'");

	if ( empty($pages) ) 
		return NULL;

	foreach ($pages as $page) {
		$path = '/' . $leaf_path;
		$curpage = $page;
		while ($curpage->post_parent != 0) {
			$curpage = $wpdb->get_row("SELECT ID, post_name, post_parent FROM $wpdb->posts WHERE ID = '$curpage->post_parent' and post_type='page'");
			$path = '/' . $curpage->post_name . $path;
		}

		if ( $path == $full_path )
			return get_page($page->ID, $output);
	}

	return NULL;
}

// Retrieves page data given a page ID or page object.
// Handles page caching.
function &get_page(&$page, $output = OBJECT) {
	global $wpdb;

	if ( empty($page) ) {
		if ( isset($GLOBALS['page']) ) {
			$_page = & $GLOBALS['page'];
			wp_cache_add($_page->ID, $_page, 'pages');
		} else {
			$_page = null;
		}
	} elseif ( is_object($page) ) {
		if ( 'post' == $page->post_type )
			return get_post($page, $output);
		wp_cache_add($page->ID, $page, 'pages');
		$_page = $page;
	} else {
		if ( isset($GLOBALS['page']) && ($page == $GLOBALS['page']->ID) ) {
			$_page = & $GLOBALS['page'];
			wp_cache_add($_page->ID, $_page, 'pages');
		} elseif ( $_page = $GLOBALS['post_cache'][$page] ) {
			return get_post($page, $output);
		} elseif ( $_page = wp_cache_get($page, 'pages') ) {
			// Got it.
		} else {
			$query = "SELECT * FROM $wpdb->posts WHERE ID= '$page' LIMIT 1";
			$_page = & $wpdb->get_row($query);
			if ( 'post' == $_page->post_type )
				return get_post($_page, $output);
			wp_cache_add($_page->ID, $_page, 'pages');
		}
	}

	if ( $output == OBJECT ) {
		return $_page;
	} elseif ( $output == ARRAY_A ) {
		return get_object_vars($_page);
	} elseif ( $output == ARRAY_N ) {
		return array_values(get_object_vars($_page));
	} else {
		return $_page;
	}
}

function get_category_by_path($category_path, $full_match = true, $output = OBJECT) {
	global $wpdb;
	$category_path = rawurlencode(urldecode($category_path));
	$category_path = str_replace('%2F', '/', $category_path);
	$category_path = str_replace('%20', ' ', $category_path);
	$category_paths = '/' . trim($category_path, '/');
	$leaf_path  = sanitize_title(basename($category_paths));
	$category_paths = explode('/', $category_paths);
	foreach($category_paths as $pathdir)
		$full_path .= ($pathdir!=''?'/':'') . sanitize_title($pathdir);

	$categories = $wpdb->get_results("SELECT cat_ID, category_nicename, category_parent FROM $wpdb->categories WHERE category_nicename = '$leaf_path'");

	if ( empty($categories) ) 
		return NULL;

	foreach ($categories as $category) {
		$path = '/' . $leaf_path;
		$curcategory = $category;
		while ($curcategory->category_parent != 0) {
			$curcategory = $wpdb->get_row("SELECT cat_ID, category_nicename, category_parent FROM $wpdb->categories WHERE cat_ID = '$curcategory->category_parent'");
			$path = '/' . $curcategory->category_nicename . $path;
		}

		if ( $path == $full_path )
			return get_category($category->cat_ID, $output);
	}

	// If full matching is not required, return the first cat that matches the leaf.
	if ( ! $full_match )
		return get_category($categories[0]->cat_ID, $output);

	return NULL;
}

// Retrieves category data given a category ID or category object.
// Handles category caching.
function &get_category(&$category, $output = OBJECT) {
	global $wpdb;

	if ( empty($category) )
		return null;

	if ( is_object($category) ) {
		wp_cache_add($category->cat_ID, $category, 'category');
		$_category = $category;
	} else {
		if ( ! $_category = wp_cache_get($category, 'category') ) {
			$_category = $wpdb->get_row("SELECT * FROM $wpdb->categories WHERE cat_ID = '$category' LIMIT 1");
			wp_cache_add($category, $_category, 'category');
		}
	}

	if ( $output == OBJECT ) {
		return $_category;
	} elseif ( $output == ARRAY_A ) {
		return get_object_vars($_category);
	} elseif ( $output == ARRAY_N ) {
		return array_values(get_object_vars($_category));
	} else {
		return $_category;
	}
}

function get_catname($cat_ID) {
	$category = &get_category($cat_ID);
	return $category->cat_name;
}

function get_all_category_ids() {
	global $wpdb;

	if ( ! $cat_ids = wp_cache_get('all_category_ids', 'category') ) {
		$cat_ids = $wpdb->get_col("SELECT cat_ID FROM $wpdb->categories");
		wp_cache_add('all_category_ids', $cat_ids, 'category');
	}

	return $cat_ids;
}

function get_all_page_ids() {
	global $wpdb;

	if ( ! $page_ids = wp_cache_get('all_page_ids', 'pages') ) {
		$page_ids = $wpdb->get_col("SELECT ID FROM $wpdb->posts WHERE post_type = 'page'");
		wp_cache_add('all_page_ids', $page_ids, 'pages');
	}

	return $page_ids;
}

function gzip_compression() {
	if ( !get_settings('gzipcompression') ) return false;

	if ( extension_loaded('zlib') ) {
		ob_start('ob_gzhandler');
	}
}


// functions to count the page generation time (from phpBB2)
// ( or just any time between timer_start() and timer_stop() )

function timer_stop($display = 0, $precision = 3) { //if called like timer_stop(1), will echo $timetotal
	global $timestart, $timeend;
	$mtime = microtime();
	$mtime = explode(' ',$mtime);
	$mtime = $mtime[1] + $mtime[0];
	$timeend = $mtime;
	$timetotal = $timeend-$timestart;
	if ( $display )
		echo number_format($timetotal,$precision);
	return $timetotal;
}

function weblog_ping($server = '', $path = '') {
	global $wp_version;
	include_once (ABSPATH . WPINC . '/class-IXR.php');

	// using a timeout of 3 seconds should be enough to cover slow servers
	$client = new IXR_Client($server, ((!strlen(trim($path)) || ('/' == $path)) ? false : $path));
	$client->timeout = 3;
	$client->useragent .= ' -- WordPress/'.$wp_version;

	// when set to true, this outputs debug messages by itself
	$client->debug = false;
	$home = trailingslashit( get_option('home') );
	if ( !$client->query('weblogUpdates.extendedPing', get_settings('blogname'), $home, get_bloginfo('rss2_url') ) ) // then try a normal ping
		$client->query('weblogUpdates.ping', get_settings('blogname'), $home);
}

function generic_ping($post_id = 0) {
	$services = get_settings('ping_sites');
	$services = preg_replace("|(\s)+|", '$1', $services); // Kill dupe lines
	$services = trim($services);
	if ( '' != $services ) {
		$services = explode("\n", $services);
		foreach ($services as $service) {
			weblog_ping($service);
		}
	}

	return $post_id;
}

// Send a Trackback
function trackback($trackback_url, $title, $excerpt, $ID) {
	global $wpdb, $wp_version;

	if ( empty($trackback_url) )
		return;

	$title = urlencode($title);
	$excerpt = urlencode($excerpt);
	$blog_name = urlencode(get_settings('blogname'));
	$tb_url = $trackback_url;
	$url = urlencode(get_permalink($ID));
	$query_string = "title=$title&url=$url&blog_name=$blog_name&excerpt=$excerpt";
	$trackback_url = parse_url($trackback_url);
	$http_request = 'POST ' . $trackback_url['path'] . ($trackback_url['query'] ? '?'.$trackback_url['query'] : '') . " HTTP/1.0\r\n";
	$http_request .= 'Host: '.$trackback_url['host']."\r\n";
	$http_request .= 'Content-Type: application/x-www-form-urlencoded; charset='.get_settings('blog_charset')."\r\n";
	$http_request .= 'Content-Length: '.strlen($query_string)."\r\n";
	$http_request .= "User-Agent: WordPress/" . $wp_version;
	$http_request .= "\r\n\r\n";
	$http_request .= $query_string;
	if ( '' == $trackback_url['port'] )
		$trackback_url['port'] = 80;
	$fs = @fsockopen($trackback_url['host'], $trackback_url['port'], $errno, $errstr, 4);
	@fputs($fs, $http_request);
/*
	$debug_file = 'trackback.log';
	$fp = fopen($debug_file, 'a');
	fwrite($fp, "\n*****\nRequest:\n\n$http_request\n\nResponse:\n\n");
	while(!@feof($fs)) {
		fwrite($fp, @fgets($fs, 4096));
	}
	fwrite($fp, "\n\n");
	fclose($fp);
*/
	@fclose($fs);

	$tb_url = addslashes( $tb_url );
	$wpdb->query("UPDATE $wpdb->posts SET pinged = CONCAT(pinged, '\n', '$tb_url') WHERE ID = '$ID'");
	return $wpdb->query("UPDATE $wpdb->posts SET to_ping = TRIM(REPLACE(to_ping, '$tb_url', '')) WHERE ID = '$ID'");
}

function make_url_footnote($content) {
	preg_match_all('/<a(.+?)href=\"(.+?)\"(.*?)>(.+?)<\/a>/', $content, $matches);
	$j = 0;
	for ($i=0; $i<count($matches[0]); $i++) {
		$links_summary = (!$j) ? "\n" : $links_summary;
		$j++;
		$link_match = $matches[0][$i];
		$link_number = '['.($i+1).']';
		$link_url = $matches[2][$i];
		$link_text = $matches[4][$i];
		$content = str_replace($link_match, $link_text.' '.$link_number, $content);
		$link_url = ((strtolower(substr($link_url,0,7)) != 'http://') && (strtolower(substr($link_url,0,8)) != 'https://')) ? get_settings('home') . $link_url : $link_url;
		$links_summary .= "\n".$link_number.' '.$link_url;
	}
	$content = strip_tags($content);
	$content .= $links_summary;
	return $content;
}


function xmlrpc_getposttitle($content) {
	global $post_default_title;
	if ( preg_match('/<title>(.+?)<\/title>/is', $content, $matchtitle) ) {
		$post_title = $matchtitle[0];
		$post_title = preg_replace('/<title>/si', '', $post_title);
		$post_title = preg_replace('/<\/title>/si', '', $post_title);
	} else {
		$post_title = $post_default_title;
	}
	return $post_title;
}

function xmlrpc_getpostcategory($content) {
	global $post_default_category;
	if ( preg_match('/<category>(.+?)<\/category>/is', $content, $matchcat) ) {
		$post_category = trim($matchcat[1], ',');
		$post_category = explode(',', $post_category);
	} else {
		$post_category = $post_default_category;
	}
	return $post_category;
}

function xmlrpc_removepostdata($content) {
	$content = preg_replace('/<title>(.+?)<\/title>/si', '', $content);
	$content = preg_replace('/<category>(.+?)<\/category>/si', '', $content);
	$content = trim($content);
	return $content;
}

function debug_fopen($filename, $mode) {
	global $debug;
	if ( $debug == 1 ) {
		$fp = fopen($filename, $mode);
		return $fp;
	} else {
		return false;
	}
}

function debug_fwrite($fp, $string) {
	global $debug;
	if ( $debug == 1 ) {
		fwrite($fp, $string);
	}
}

function debug_fclose($fp) {
	global $debug;
	if ( $debug == 1 ) {
		fclose($fp);
	}
}

function do_enclose( $content, $post_ID ) {
	global $wp_version, $wpdb;
	include_once (ABSPATH . WPINC . '/class-IXR.php');

	$log = debug_fopen(ABSPATH . '/enclosures.log', 'a');
	$post_links = array();
	debug_fwrite($log, 'BEGIN '.date('YmdHis', time())."\n");

	$pung = get_enclosed( $post_ID );

	$ltrs = '\w';
	$gunk = '/#~:.?+=&%@!\-';
	$punc = '.:?\-';
	$any = $ltrs . $gunk . $punc;

	preg_match_all("{\b http : [$any] +? (?= [$punc] * [^$any] | $)}x", $content, $post_links_temp);

	debug_fwrite($log, 'Post contents:');
	debug_fwrite($log, $content."\n");

	foreach($post_links_temp[0] as $link_test) :
		if ( !in_array($link_test, $pung) ) : // If we haven't pung it already
			$test = parse_url($link_test);
			if ( isset($test['query']) )
				$post_links[] = $link_test;
			elseif (($test['path'] != '/') && ($test['path'] != ''))
				$post_links[] = $link_test;
		endif;
	endforeach;

	foreach ($post_links as $url) :
		if ( $url != '' && !$wpdb->get_var("SELECT post_id FROM $wpdb->postmeta WHERE post_id = '$post_ID' AND meta_key = 'enclosure' AND meta_value LIKE ('$url%')") ) {
			if ( $headers = wp_get_http_headers( $url) ) {
				$len = (int) $headers['content-length'];
				$type = $wpdb->escape( $headers['content-type'] );
				$allowed_types = array( 'video', 'audio' );
				if ( in_array( substr( $type, 0, strpos( $type, "/" ) ), $allowed_types ) ) {
					$meta_value = "$url\n$len\n$type\n";
					$wpdb->query( "INSERT INTO `$wpdb->postmeta` ( `post_id` , `meta_key` , `meta_value` )
					VALUES ( '$post_ID', 'enclosure' , '$meta_value')" );
				}
			}
		}
	endforeach;
}

function wp_get_http_headers( $url, $red = 1 ) {
	global $wp_version;
	@set_time_limit( 60 );

	if ( $red > 5 )
	   return false;

	$parts = parse_url( $url );
	$file = $parts['path'] . ($parts['query'] ? '?'.$parts['query'] : '');
	$host = $parts['host'];
	if ( !isset( $parts['port'] ) )
		$parts['port'] = 80;

	$head = "HEAD $file HTTP/1.1\r\nHOST: $host\r\nUser-Agent: WordPress/" . $wp_version . "\r\n\r\n";

	$fp = @fsockopen($host, $parts['port'], $err_num, $err_msg, 3);
	if ( !$fp )
		return false;

	$response = '';
	fputs( $fp, $head );
	while ( !feof( $fp ) && strpos( $response, "\r\n\r\n" ) == false )
		$response .= fgets( $fp, 2048 );
	fclose( $fp );
	preg_match_all('/(.*?): (.*)\r/', $response, $matches);
	$count = count($matches[1]);
	for ( $i = 0; $i < $count; $i++) {
		$key = strtolower($matches[1][$i]);
		$headers["$key"] = $matches[2][$i];
	}

    $code = preg_replace('/.*?(\d{3}).*/i', '$1', $response);
    
    $headers['status_code'] = $code;
    
    if ( '302' == $code || '301' == $code )
        return wp_get_http_headers( $url, ++$red );

	preg_match('/.*([0-9]{3}).*/', $response, $return);
	$headers['response'] = $return[1]; // HTTP response code eg 204, 200, 404
	return $headers;
}

// Setup global post data.
function setup_postdata($post) {
	global $id, $postdata, $authordata, $day, $page, $pages, $multipage, $more, $numpages, $wp_query;
	global $pagenow;

	$id = $post->ID;

	$authordata = get_userdata($post->post_author);

	$day = mysql2date('d.m.y', $post->post_date);
	$currentmonth = mysql2date('m', $post->post_date);
	$numpages = 1;
	$page = get_query_var('page');
	if ( !$page )
		$page = 1;
	if ( is_single() || is_page() )
		$more = 1;
	$content = $post->post_content;
	if ( preg_match('/<!--nextpage-->/', $content) ) {
		if ( $page > 1 )
			$more = 1;
		$multipage = 1;
		$content = str_replace("\n<!--nextpage-->\n", '<!--nextpage-->', $content);
		$content = str_replace("\n<!--nextpage-->", '<!--nextpage-->', $content);
		$content = str_replace("<!--nextpage-->\n", '<!--nextpage-->', $content);
		$pages = explode('<!--nextpage-->', $content);
		$numpages = count($pages);
	} else {
		$pages[0] = $post->post_content;
		$multipage = 0;
	}
	return true;
}

// Setup global user vars.  Used by set_current_user() for back compat.
function setup_userdata($user_id = '') {
	global $user_login, $userdata, $user_level, $user_ID, $user_email, $user_url, $user_pass_md5, $user_identity;

	if ( '' == $user_id )
		$user = wp_get_current_user();
	else 
		$user = new WP_User($user_id);

	if ( 0 == $user->ID )
		return;

	$userdata = $user->data;
	$user_login	= $user->user_login;
	$user_level	= $user->user_level;
	$user_ID	= $user->ID;
	$user_email	= $user->user_email;
	$user_url	= $user->user_url;
	$user_pass_md5	= md5($user->user_pass);
	$user_identity	= $user->display_name;
}

function is_new_day() {
	global $day, $previousday;
	if ( $day != $previousday ) {
		return(1);
	} else {
		return(0);
	}
}

// Filters: these are the core of WP's plugin architecture

function merge_filters($tag) {
	global $wp_filter;
	if ( isset($wp_filter['all']) ) {
		foreach ($wp_filter['all'] as $priority => $functions) {
			if ( isset($wp_filter[$tag][$priority]) )
				$wp_filter[$tag][$priority] = array_merge($wp_filter['all'][$priority], $wp_filter[$tag][$priority]);
			else
				$wp_filter[$tag][$priority] = array_merge($wp_filter['all'][$priority], array());
			$wp_filter[$tag][$priority] = array_unique($wp_filter[$tag][$priority]);
		}
	}

	if ( isset($wp_filter[$tag]) )
		ksort( $wp_filter[$tag] );
}

function apply_filters($tag, $string) {
	global $wp_filter;

	$args = array_slice(func_get_args(), 2);

	merge_filters($tag);

	if ( !isset($wp_filter[$tag]) ) {
		return $string;
	}
	foreach ($wp_filter[$tag] as $priority => $functions) {
		if ( !is_null($functions) ) {
			foreach($functions as $function) {

				$all_args = array_merge(array($string), $args);
				$function_name = $function['function'];
				$accepted_args = $function['accepted_args'];

				if ( $accepted_args == 1 )
					$the_args = array($string);
				elseif ( $accepted_args > 1 )
					$the_args = array_slice($all_args, 0, $accepted_args);
				elseif ( $accepted_args == 0 )
					$the_args = NULL;
				else
					$the_args = $all_args;

				$string = call_user_func_array($function_name, $the_args);
			}
		}
	}
	return $string;
}

function add_filter($tag, $function_to_add, $priority = 10, $accepted_args = 1) {
	global $wp_filter;

	// check that we don't already have the same filter at the same priority
	if ( isset($wp_filter[$tag]["$priority"]) ) {
		foreach($wp_filter[$tag]["$priority"] as $filter) {
			// uncomment if we want to match function AND accepted_args
			// if ( $filter == array($function, $accepted_args) ) {
			if ( $filter['function'] == $function_to_add ) {
				return true;
			}
		}
	}

	// So the format is wp_filter['tag']['array of priorities']['array of ['array (functions, accepted_args)]']
	$wp_filter[$tag]["$priority"][] = array('function'=>$function_to_add, 'accepted_args'=>$accepted_args);
	return true;
}

function remove_filter($tag, $function_to_remove, $priority = 10, $accepted_args = 1) {
	global $wp_filter;

	// rebuild the list of filters
	if ( isset($wp_filter[$tag]["$priority"]) ) {
		foreach($wp_filter[$tag]["$priority"] as $filter) {
			if ( $filter['function'] != $function_to_remove ) {
				$new_function_list[] = $filter;
			}
		}
		$wp_filter[$tag]["$priority"] = $new_function_list;
	}
	return true;
}

// The *_action functions are just aliases for the *_filter functions, they take special strings instead of generic content

function do_action($tag, $arg = '') {
	global $wp_filter;
	$extra_args = array_slice(func_get_args(), 2);
 	if ( is_array($arg) )
 		$args = array_merge($arg, $extra_args);
	else
		$args = array_merge(array($arg), $extra_args);

	merge_filters($tag);

	if ( !isset($wp_filter[$tag]) ) {
		return;
	}
	foreach ($wp_filter[$tag] as $priority => $functions) {
		if ( !is_null($functions) ) {
			foreach($functions as $function) {

				$function_name = $function['function'];
				$accepted_args = $function['accepted_args'];

				if ( $accepted_args == 1 ) {
					if ( is_array($arg) )
						$the_args = $arg;
					else
						$the_args = array($arg);
				} elseif ( $accepted_args > 1 ) {
					$the_args = array_slice($args, 0, $accepted_args);
				} elseif ( $accepted_args == 0 ) {
					$the_args = NULL;
				} else {
					$the_args = $args;
				}

				$string = call_user_func_array($function_name, $the_args);
			}
		}
	}
}

function add_action($tag, $function_to_add, $priority = 10, $accepted_args = 1) {
	add_filter($tag, $function_to_add, $priority, $accepted_args);
}

function remove_action($tag, $function_to_remove, $priority = 10, $accepted_args = 1) {
	remove_filter($tag, $function_to_remove, $priority, $accepted_args);
}

function get_page_uri($page_id) {
	$page = get_page($page_id);
	$uri = urldecode($page->post_name);

	// A page cannot be it's own parent.
	if ( $page->post_parent == $page->ID )
		return $uri;

	while ($page->post_parent != 0) {
		$page = get_page($page->post_parent);
		$uri = urldecode($page->post_name) . "/" . $uri;
	}

	return $uri;
}

function get_posts($args) {
	global $wpdb;

	if ( is_array($args) )
		$r = &$args;
	else
		parse_str($args, $r);

	$defaults = array('numberposts' => 5, 'offset' => 0, 'category' => '',
		'orderby' => 'post_date', 'order' => 'DESC', 'include' => '', 'exclude' => '', 'meta_key' => '', 'meta_value' =>'');
	$r = array_merge($defaults, $r);
	extract($r);

	$inclusions = '';
	if ( !empty($include) ) {
		$offset = 0;	//ignore offset, category, exclude, meta_key, and meta_value params if using include
		$category = ''; 
		$exclude = '';  
		$meta_key = '';
		$meta_value = '';
		$incposts = preg_split('/[\s,]+/',$include);
		$numberposts = count($incposts);  // only the number of posts included
		if ( count($incposts) ) {
			foreach ( $incposts as $incpost ) {
				if (empty($inclusions))
					$inclusions = ' AND ( ID = ' . intval($incpost) . ' ';
				else
					$inclusions .= ' OR ID = ' . intval($incpost) . ' ';
			}
		}
	}
	if (!empty($inclusions)) 
		$inclusions .= ')';	

	$exclusions = '';
	if ( !empty($exclude) ) {
		$exposts = preg_split('/[\s,]+/',$exclude);
		if ( count($exposts) ) {
			foreach ( $exposts as $expost ) {
				if (empty($exclusions))
					$exclusions = ' AND ( ID <> ' . intval($expost) . ' ';
				else
					$exclusions .= ' AND ID <> ' . intval($expost) . ' ';
			}
		}
	}
	if (!empty($exclusions)) 
		$exclusions .= ')';

	$query ="SELECT DISTINCT * FROM $wpdb->posts " ;
	$query .= ( empty( $category ) ? "" : ", $wpdb->post2cat " ) ; 
	$query .= ( empty( $meta_key ) ? "" : ", $wpdb->postmeta " ) ; 
	$query .= " WHERE (post_type = 'post' AND post_status = 'publish') $exclusions $inclusions " ;
	$query .= ( empty( $category ) ? "" : "AND ($wpdb->posts.ID = $wpdb->post2cat.post_id AND $wpdb->post2cat.category_id = " . $category. ") " ) ;
	$query .= ( empty( $meta_key ) | empty($meta_value)  ? "" : " AND ($wpdb->posts.ID = $wpdb->postmeta.post_id AND $wpdb->postmeta.meta_key = '$meta_key' AND $wpdb->postmeta.meta_value = '$meta_value' )" ) ;
	$query .= " GROUP BY $wpdb->posts.ID ORDER BY " . $orderby . " " . $order . " LIMIT " . $offset . ',' . $numberposts ;

	$posts = $wpdb->get_results($query);

	update_post_caches($posts);

	return $posts;
}

function update_post_cache(&$posts) {
	global $post_cache;

	if ( !$posts )
		return;

	for ($i = 0; $i < count($posts); $i++) {
		$post_cache[$posts[$i]->ID] = &$posts[$i];
	}
}

function clean_post_cache($id) {
	global $post_cache;

	if ( isset( $post_cache[$id] ) )
		unset( $post_cache[$id] );
}

function update_page_cache(&$pages) {
	global $page_cache;

	if ( !$pages )
		return;

	for ($i = 0; $i < count($pages); $i++) {
		$page_cache[$pages[$i]->ID] = &$pages[$i];
		wp_cache_add($pages[$i]->ID, $pages[$i], 'pages');
	}
}


function clean_page_cache($id) {
	global $page_cache;

	if ( isset( $page_cache[$id] ) )
		unset( $page_cache[$id] );
}

function update_post_category_cache($post_ids) {
	global $wpdb, $category_cache;

	if ( empty($post_ids) )
		return;

	if ( is_array($post_ids) )
		$post_ids = implode(',', $post_ids);

	$dogs = $wpdb->get_results("SELECT post_id, category_id FROM $wpdb->post2cat WHERE post_id IN ($post_ids)");

	if ( empty($dogs) )
		return;

	foreach ($dogs as $catt)
		$category_cache[$catt->post_id][$catt->category_id] = &get_category($catt->category_id);
}

function update_post_caches(&$posts) {
	global $post_cache, $category_cache, $post_meta_cache;
	global $wpdb;

	// No point in doing all this work if we didn't match any posts.
	if ( !$posts )
		return;

	// Get the categories for all the posts
	for ($i = 0; $i < count($posts); $i++) {
		$post_id_array[] = $posts[$i]->ID;
		$post_cache[$posts[$i]->ID] = &$posts[$i];
	}

	$post_id_list = implode(',', $post_id_array);

	update_post_category_cache($post_id_list);

	// Get post-meta info
	if ( $meta_list = $wpdb->get_results("SELECT post_id, meta_key, meta_value FROM $wpdb->postmeta WHERE post_id IN($post_id_list) ORDER BY post_id, meta_key", ARRAY_A) ) {
		// Change from flat structure to hierarchical:
		$post_meta_cache = array();
		foreach ($meta_list as $metarow) {
			$mpid = $metarow['post_id'];
			$mkey = $metarow['meta_key'];
			$mval = $metarow['meta_value'];

			// Force subkeys to be array type:
			if ( !isset($post_meta_cache[$mpid]) || !is_array($post_meta_cache[$mpid]) )
				$post_meta_cache[$mpid] = array();
			if ( !isset($post_meta_cache[$mpid]["$mkey"]) || !is_array($post_meta_cache[$mpid]["$mkey"]) )
				$post_meta_cache[$mpid]["$mkey"] = array();

			// Add a value to the current pid/key:
			$post_meta_cache[$mpid][$mkey][] = $mval;
		}
	}
}

function update_category_cache() {
	return true;
}

function wp_head() {
	do_action('wp_head');
}

function wp_footer() {
	do_action('wp_footer');
}

/*
add_query_arg: Returns a modified querystring by adding
a single key & value or an associative array.
Setting a key value to emptystring removes the key.
Omitting oldquery_or_uri uses the $_SERVER value.

Parameters:
add_query_arg(newkey, newvalue, oldquery_or_uri) or
add_query_arg(associative_array, oldquery_or_uri)
*/
function add_query_arg() {
	$ret = '';
	if ( is_array(func_get_arg(0)) ) {
		if ( @func_num_args() < 2 )
			$uri = $_SERVER['REQUEST_URI'];
		else
			$uri = @func_get_arg(1);
	} else {
		if ( @func_num_args() < 3 )
			$uri = $_SERVER['REQUEST_URI'];
		else
			$uri = @func_get_arg(2);
	}

	if ( strstr($uri, '?') ) {
		$parts = explode('?', $uri, 2);
		if ( 1 == count($parts) ) {
			$base = '?';
			$query = $parts[0];
		} else {
			$base = $parts[0] . '?';
			$query = $parts[1];
		}
	}
	else if ( strstr($uri, '/') ) {
		$base = $uri . '?';
		$query = '';
	} else {
		$base = '';
		$query = $uri;
	}

	parse_str($query, $qs);
	if ( is_array(func_get_arg(0)) ) {
		$kayvees = func_get_arg(0);
		$qs = array_merge($qs, $kayvees);
	} else {
		$qs[func_get_arg(0)] = func_get_arg(1);
	}

	foreach($qs as $k => $v) {
		if ( $v != '' ) {
			if ( $ret != '' )
				$ret .= '&';
			$ret .= "$k=$v";
		}
	}
	$ret = $base . $ret;
	return trim($ret, '?');
}

function remove_query_arg($key, $query) {
	return add_query_arg($key, '', $query);
}

function add_magic_quotes($array) {
	global $wpdb;

	foreach ($array as $k => $v) {
		if ( is_array($v) ) {
			$array[$k] = add_magic_quotes($v);
		} else {
			$array[$k] = $wpdb->escape($v);
		}
	}
	return $array;
}

function wp_remote_fopen( $uri ) {
	if ( ini_get('allow_url_fopen') ) {
		$fp = fopen( $uri, 'r' );
		if ( !$fp )
			return false;
		$linea = '';
		while( $remote_read = fread($fp, 4096) )
			$linea .= $remote_read;
		fclose($fp);
		return $linea;
	} else if ( function_exists('curl_init') ) {
		$handle = curl_init();
		curl_setopt ($handle, CURLOPT_URL, $uri);
		curl_setopt ($handle, CURLOPT_CONNECTTIMEOUT, 1);
		curl_setopt ($handle, CURLOPT_RETURNTRANSFER, 1);
		$buffer = curl_exec($handle);
		curl_close($handle);
		return $buffer;
	} else {
		return false;
	}
}

function wp($query_vars = '') {
	global $wp;

	$wp->main($query_vars);
}

function status_header( $header ) {
	if ( 200 == $header )
		$text = 'OK';
	elseif ( 301 == $header )
		$text = 'Moved Permanently';
	elseif ( 302 == $header )
		$text = 'Moved Temporarily';
	elseif ( 304 == $header )
		$text = 'Not Modified';
	elseif ( 404 == $header )
		$text = 'Not Found';
	elseif ( 410 == $header )
		$text = 'Gone';

	@header("HTTP/1.1 $header $text");
	@header("Status: $header $text");
}

function nocache_headers() {
	@ header('Expires: Wed, 11 Jan 1984 05:00:00 GMT');
	@ header('Last-Modified: ' . gmdate('D, d M Y H:i:s') . ' GMT');
	@ header('Cache-Control: no-cache, must-revalidate, max-age=0');
	@ header('Pragma: no-cache');
}

function get_usermeta( $user_id, $meta_key = '') {
	global $wpdb;
	$user_id = (int) $user_id;

	if ( !empty($meta_key) ) {
		$meta_key = preg_replace('|a-z0-9_|i', '', $meta_key);
		$metas = $wpdb->get_results("SELECT meta_key, meta_value FROM $wpdb->usermeta WHERE user_id = '$user_id' AND meta_key = '$meta_key'");
	} else {
		$metas = $wpdb->get_results("SELECT meta_key, meta_value FROM $wpdb->usermeta WHERE user_id = '$user_id'");
	}

	if ( empty($metas) ) {
		if ( empty($meta_key) )
			return array();
		else
			return '';
	}

	foreach ($metas as $index => $meta) {
		@ $value = unserialize($meta->meta_value);
		if ( $value === FALSE )
			$value = $meta->meta_value;

		$values[] = $value;
	}

	if ( count($values) == 1 )
		return $values[0];
	else
		return $values;
}

function update_usermeta( $user_id, $meta_key, $meta_value ) {
	global $wpdb;
	if ( !is_numeric( $user_id ) )
		return false;
	$meta_key = preg_replace('|[^a-z0-9_]|i', '', $meta_key);

	if ( is_array($meta_value) || is_object($meta_value) )
		$meta_value = serialize($meta_value);
	$meta_value = trim( $meta_value );

	if (empty($meta_value)) {
		return delete_usermeta($user_id, $meta_key);
	}

	$cur = $wpdb->get_row("SELECT * FROM $wpdb->usermeta WHERE user_id = '$user_id' AND meta_key = '$meta_key'");
	if ( !$cur ) {
		$wpdb->query("INSERT INTO $wpdb->usermeta ( user_id, meta_key, meta_value )
		VALUES
		( '$user_id', '$meta_key', '$meta_value' )");
	} else if ( $cur->meta_value != $meta_value ) {
		$wpdb->query("UPDATE $wpdb->usermeta SET meta_value = '$meta_value' WHERE user_id = '$user_id' AND meta_key = '$meta_key'");
	} else {
		return false;
	}

	$user = get_userdata($user_id);
	wp_cache_delete($user_id, 'users');
	wp_cache_delete($user->user_login, 'userlogins');

	return true;
}

function delete_usermeta( $user_id, $meta_key, $meta_value = '' ) {
	global $wpdb;
	if ( !is_numeric( $user_id ) )
		return false;
	$meta_key = preg_replace('|[^a-z0-9_]|i', '', $meta_key);

	if ( is_array($meta_value) || is_object($meta_value) )
		$meta_value = serialize($meta_value);
	$meta_value = trim( $meta_value );

	if ( ! empty($meta_value) )
		$wpdb->query("DELETE FROM $wpdb->usermeta WHERE user_id = '$user_id' AND meta_key = '$meta_key' AND meta_value = '$meta_value'");
	else
		$wpdb->query("DELETE FROM $wpdb->usermeta WHERE user_id = '$user_id' AND meta_key = '$meta_key'");

	$user = get_userdata($user_id);
	wp_cache_delete($user_id, 'users');
	wp_cache_delete($user->user_login, 'userlogins');

	return true;
}

function register_activation_hook($file, $function) {
	$file = plugin_basename($file);

	add_action('activate_' . $file, $function);
}

function register_deactivation_hook($file, $function) {
	$file = plugin_basename($file);

	add_action('deactivate_' . $file, $function);
}

function plugin_basename($file) {
	$file = preg_replace('|\\\\+|', '\\\\', $file);
	$file = preg_replace('/^.*wp-content[\\\\\/]plugins[\\\\\/]/', '', $file);
	return $file;
}

function get_num_queries() {
	global $wpdb;
	return $wpdb->num_queries;
}

function privacy_ping_filter( $sites ) {
	if ( get_option('blog_public') )
		return $sites;
	else
		return '';
}

function bool_from_yn($yn) {
    if ($yn == 'Y') return 1;
    return 0;
}

function do_feed() {
	$feed = get_query_var('feed');

	// Remove the pad, if present.
	$feed = preg_replace('/^_+/', '', $feed);

	if ($feed == '' || $feed == 'feed')
    	$feed = 'rss2';

	$for_comments = false;
	if ( is_single() || (get_query_var('withcomments') == 1) ) {
		$feed = 'rss2';
		$for_comments = true;	
	}

	$hook = 'do_feed_' . $feed;
	do_action($hook, $for_comments);
}

function do_feed_rdf() {
	load_template(ABSPATH . 'wp-rdf.php');
}

function do_feed_rss() {
	load_template(ABSPATH . 'wp-rss.php');
}

function do_feed_rss2($for_comments) {
	if ( $for_comments ) {
		load_template(ABSPATH . 'wp-commentsrss2.php');
	} else {
		load_template(ABSPATH . 'wp-rss2.php');
	}
}

function do_feed_atom() {
	load_template(ABSPATH . 'wp-atom.php');
}

function do_robots() {
	if ( '1' != get_option('blog_public') ) {
		echo "User-agent: *\n";
		echo "Disallow: /\n";
	} else {
		echo "User-agent: *\n";
		echo "Disallow:\n";
	}
}

function is_blog_installed() {
	global $wpdb;
	$wpdb->hide_errors();
	$installed = $wpdb->get_var("SELECT option_value FROM $wpdb->options WHERE option_name = 'siteurl'");
	$wpdb->show_errors();
	return $installed;
}

function wp_nonce_url($actionurl, $action = -1) {
	return add_query_arg('_wpnonce', wp_create_nonce($action), $actionurl);
}

function wp_nonce_field($action = -1) {
	echo '<input type="hidden" name="_wpnonce" value="' . wp_create_nonce($action) . '" />';
}

?>
