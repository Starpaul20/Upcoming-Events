<?php
/**
 * Upcoming Events
 * Copyright 2017 Starpaul20
 */

// Disallow direct access to this file for security reasons
if(!defined("IN_MYBB"))
{
	die("Direct initialization of this file is not allowed.<br /><br />Please make sure IN_MYBB is defined.");
}

// Neat trick for caching our custom template(s)
if(THIS_SCRIPT == 'index.php')
{
	global $templatelist;
	if(isset($templatelist))
	{
		$templatelist .= ',';
	}
	$templatelist .= 'index_upcomingevents,index_upcomingevents_event';
}

if(THIS_SCRIPT == 'portal.php')
{
	global $templatelist;
	if(isset($templatelist))
	{
		$templatelist .= ',';
	}
	$templatelist .= 'portal_upcomingevents,portal_upcomingevents_event';
}

// Tell MyBB when to run the hooks
$plugins->add_hook("index_start", "upcomingevents_run");
$plugins->add_hook("portal_start", "upcomingevents_portal");

// The information that shows up on the plugin manager
function upcomingevents_info()
{
	global $lang;
	$lang->load("upcomingevents", true);

	return array(
		"name"				=> $lang->upcomingevents_info_name,
		"description"		=> $lang->upcomingevents_info_desc,
		"website"			=> "http://galaxiesrealm.com/index.php",
		"author"			=> "Starpaul20",
		"authorsite"		=> "http://galaxiesrealm.com/index.php",
		"version"			=> "1.0.1",
		"codename"			=> "upcomingevents",
		"compatibility"		=> "18*"
	);
}

// This function runs when the plugin is activated.
function upcomingevents_activate()
{
	global $db;

	$query = $db->simple_select("settinggroups", "gid", "name='forumhome'");
	$gid = $db->fetch_field($query, "gid");

	// Insert settings
	$insertarray = array(
		'name' => 'showevents',
		'title' => 'Show Upcoming Calendar Events?',
		'description' => 'Do you want to show upcoming calendar events on the forum homepage?',
		'optionscode' => 'yesno',
		'value' => 1,
		'disporder' => 11,
		'gid' => (int)$gid
	);
	$db->insert_query("settings", $insertarray);

	$insertarray = array(
		'name' => 'eventscalendar',
		'title' => 'Events from Calendar(s)',
		'description' => 'Calendars, separated by a comma, to pull events from.',
		'optionscode' => 'text',
		'value' => 1,
		'disporder' => 12,
		'gid' => (int)$gid
	);
	$db->insert_query("settings", $insertarray);

	$insertarray = array(
		'name' => 'eventscut',
		'title' => 'Number of Days upcoming',
		'description' => 'The number of days from today for which upcoming calendar events will be shown.',
		'optionscode' => 'numeric
min=1',
		'value' => 5,
		'disporder' => 13,
		'gid' => (int)$gid
	);
	$db->insert_query("settings", $insertarray);

	$query = $db->simple_select("settinggroups", "gid", "name='portal'");
	$gid = $db->fetch_field($query, "gid");

	// Insert settings
	$insertarray = array(
		'name' => 'portal_showevents',
		'title' => 'Show Upcoming Calendar Events?',
		'description' => 'Do you want to show upcoming calendar events on the portal page?',
		'optionscode' => 'yesno',
		'value' => 1,
		'disporder' => 12,
		'gid' => (int)$gid
	);
	$db->insert_query("settings", $insertarray);

	$insertarray = array(
		'name' => 'portal_eventscalendar',
		'title' => 'Events from Calendar(s)',
		'description' => 'Calendars, separated by a comma, to pull events from.',
		'optionscode' => 'text',
		'value' => 1,
		'disporder' => 13,
		'gid' => (int)$gid
	);
	$db->insert_query("settings", $insertarray);

	$insertarray = array(
		'name' => 'portal_eventscut',
		'title' => 'Number of Days upcoming',
		'description' => 'The number of days from today for which upcoming calendar events will be shown.',
		'optionscode' => 'numeric
min=1',
		'value' => 5,
		'disporder' => 14,
		'gid' => (int)$gid
	);
	$db->insert_query("settings", $insertarray);

	rebuild_settings();

	// Insert templates
	$insert_array = array(
		'title'		=> 'index_upcomingevents',
		'template'	=> $db->escape_string('<tr><td class="tcat"><span class="smalltext"><strong>{$lang->upcoming_events}</strong></span></td></tr>
<tr>
	<td class="trow1"><span class="smalltext">{$events}</span></td>
</tr>'),
		'sid'		=> '-1',
		'version'	=> '',
		'dateline'	=> TIME_NOW
	);
	$db->insert_query("templates", $insert_array);

	$insert_array = array(
		'title'		=> 'index_upcomingevents_event',
		'template'	=> $db->escape_string('{$comma}<a href="{$event[\'link\']}" title="{$date}">{$event[\'name\']}</a>'),
		'sid'		=> '-1',
		'version'	=> '',
		'dateline'	=> TIME_NOW
	);
	$db->insert_query("templates", $insert_array);

	$insert_array = array(
		'title'		=> 'portal_upcomingevents',
		'template'	=> $db->escape_string('<table border="0" cellspacing="{$theme[\'borderwidth\']}" cellpadding="{$theme[\'tablespace\']}" class="tborder">
	<tr>
		<td class="thead"><strong>{$lang->upcoming_events}</strong></td>
	</tr>
	<tr>
		<td class="trow1">
			<span class="smalltext">
				{$events}
			</span>
		</td>
	</tr>
</table>
<br />'),
		'sid'		=> '-1',
		'version'	=> '',
		'dateline'	=> TIME_NOW
	);
	$db->insert_query("templates", $insert_array);

	$insert_array = array(
		'title'		=> 'portal_upcomingevents_event',
		'template'	=> $db->escape_string('{$comma}<a href="{$event[\'link\']}" title="{$date}">{$event[\'name\']}</a>'),
		'sid'		=> '-1',
		'version'	=> '',
		'dateline'	=> TIME_NOW
	);
	$db->insert_query("templates", $insert_array);

	// Update templates
	include MYBB_ROOT."/inc/adminfunctions_templates.php";
	find_replace_templatesets("index_boardstats", "#".preg_quote('{$birthdays}')."#i", '{$birthdays}{$calendar}');
	find_replace_templatesets("portal", "#".preg_quote('{$whosonline}')."#i", '{$whosonline}{$calendar}');
}

// This function runs when the plugin is deactivated.
function upcomingevents_deactivate()
{
	global $db;
	$db->delete_query("templates", "title IN('index_upcomingevents','index_upcomingevents_event','portal_upcomingevents','portal_upcomingevents_event')");
	$db->delete_query("settings", "name IN('showevents','eventscalendar','eventscut','portal_showevents','portal_eventscalendar','portal_eventscut')");
	rebuild_settings();

	include MYBB_ROOT."/inc/adminfunctions_templates.php";
	find_replace_templatesets("index_boardstats", "#".preg_quote('{$calendar}')."#i", '', 0);
}

// Display calendar events on index
function upcomingevents_run()
{
	global $db, $mybb, $lang, $comma, $templates, $calendar;
	$lang->load("upcomingevents");

	$calendar = '';
	if($mybb->settings['showevents'] == 1 && !empty($mybb->settings['eventscalendar']) && $mybb->settings['enablecalendar'] == 1 && $mybb->usergroup['canviewcalendar'] == 1)
	{
		require_once MYBB_ROOT."inc/functions_calendar.php";

		$eventcount = 0;
		$forthcoming = TIME_NOW + ($mybb->settings['eventscut']*60*60*24);
		$now = TIME_NOW;

		$cids = explode(',', (string)$mybb->settings['eventscalendar']);
		if(is_array($cids))
		{
			foreach($cids as $cid)
			{
				$cid_array[] = (int)$cid;
			}

			$calendarcids = implode(',', $cid_array);
			$cidwhere = "cid IN (".$calendarcids.")";
		}

		$calendar_permissions = get_calendar_permissions();

		$query = $db->simple_select("events", "*", "{$cidwhere} AND starttime < '{$forthcoming}' AND starttime > '{$now}' AND private='0' AND visible='1'", array('order_by' => 'starttime', 'order_dir' => 'asc'));
		while($event = $db->fetch_array($query))
		{
			if($calendar_permissions[$event['cid']]['canviewcalendar'] == 1)
			{
				$event['name'] = htmlspecialchars_uni($event['name']);
				$time = my_date($mybb->settings['dateformat'], $event['starttime'], 0, 0);
				$date = $lang->sprintf($lang->event_on, $time);
				$event['link'] = get_event_link($event['eid']);

				eval("\$events .= \"".$templates->get('index_upcomingevents_event', 1, 0)."\";");
				++$eventcount;
				$comma = $lang->comma;
			}
		}

		if($eventcount > 0)
		{
			eval("\$calendar = \"".$templates->get("index_upcomingevents")."\";");
		}
	}
}

// Display calendar events on portal
function upcomingevents_portal()
{
	global $db, $mybb, $lang, $comma, $templates, $theme, $calendar;
	$lang->load("upcomingevents");

	$calendar = '';
	if($mybb->settings['portal_showevents'] == 1 && !empty($mybb->settings['portal_eventscalendar']) && $mybb->settings['enablecalendar'] == 1 && $mybb->usergroup['canviewcalendar'] == 1)
	{
		require_once MYBB_ROOT."inc/functions_calendar.php";

		$eventcount = 0;
		$forthcoming = TIME_NOW + ($mybb->settings['portal_eventscut']*60*60*24);
		$now = TIME_NOW;

		$cids = explode(',', (string)$mybb->settings['portal_eventscalendar']);
		if(is_array($cids))
		{
			foreach($cids as $cid)
			{
				$cid_array[] = (int)$cid;
			}

			$calendarcids = implode(',', $cid_array);
			$cidwhere = "cid IN (".$calendarcids.")";
		}

		$calendar_permissions = get_calendar_permissions();

		$query = $db->simple_select("events", "*", "{$cidwhere} AND starttime < '{$forthcoming}' AND starttime > '{$now}' AND private='0' AND visible='1'", array('order_by' => 'starttime', 'order_dir' => 'asc'));
		while($event = $db->fetch_array($query))
		{
			if($calendar_permissions[$event['cid']]['canviewcalendar'] == 1)
			{
				$event['name'] = htmlspecialchars_uni($event['name']);
				$time = my_date($mybb->settings['dateformat'], $event['starttime'], 0, 0);
				$date = $lang->sprintf($lang->event_on, $time);
				$event['link'] = get_event_link($event['eid']);

				eval("\$events .= \"".$templates->get('portal_upcomingevents_event', 1, 0)."\";");
				++$eventcount;
				$comma = $lang->comma;
			}
		}

		if($eventcount > 0)
		{
			eval("\$calendar = \"".$templates->get("portal_upcomingevents")."\";");
		}
	}
}
