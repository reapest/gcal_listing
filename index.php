<?php
/*
 * @author György Hajdu <reapest at gmail.com>
 * 
 *---------------------------------------------------------------
 * FRONT CONTROLLER
 *---------------------------------------------------------------
 *
 * Created by gyorgyhajdu on 23/09/15.
 *
 * This is just a demo application with only one controller.
 * Controller logic runs from here as well as some template
 * handling in lack of a template engine. We assume that
 * every template file exists and is accessible.
 * Error handling (e.g. invalid $_REQUEST value handling) is
 * not part of this demonstration.
 *
 */
	require_once('./app/config/config.php');
	require_once(APP_PATH.'models/CalendarModel.php');
	
	$authCode = (isset($_POST['authCode'])) ? $_POST['authCode'] : '';
	
	$calendar = new CalendarModel();
	
	if (isset($_GET['logout'])) {
		$calendar->logout();
	}
	
	$client = $calendar->getClient($authCode);
	
	$site_title = APPLICATION_NAME;
	if ($client[0] == CalendarModel::CLIENT_ERROR_AUTH_NEEDED) { // "login" page
		$site_title .= ' | Login';
		$session_lifetime = ini_get("session.gc_maxlifetime");
		if ($session_lifetime === false) {$session_lifetime = 86400;}
		$session_lifetime_hours = floor($session_lifetime / 3600);
		$template = str_replace(
			array(
				'[AUTH_URL]',
				'[SESSION_HOURS]'
			),
			array(
				$client[1],
				$session_lifetime_hours
			),
			file_get_contents(VIEW_PATH.'login.html')
		);
	} elseif ($client[0] == CalendarModel::NO_ERRORS) {
		if (isset($_POST['addEvent']) && isset($_POST['calendarId']) && strlen($_POST['calendarId'])) { // event creator
			$site_title .= ' | Esemény';
			$event_date_ts = strtotime('tomorrow 12:00:00');
			$event_date_output = date('Y.m.d. H:i',$event_date_ts);
			$event_name = 'DummyEvent '.mt_rand(100,2000);
			$event_result = $calendar->createDummyEvent($_POST['calendarId'],$event_date_ts,$event_name);
			if ($event_result != CalendarModel::NO_ERRORS) {
				$site_title .= ' | Hiba';
				$template = file_get_contents(VIEW_PATH.'error.html');
			} else {
				$template = str_replace(
					array(
						'[EVENT_NAME]',
						'[EVENT_DATE]'
					),
					array(
						$event_name,
						$event_date_output
					),
					file_get_contents(VIEW_PATH.'event.html')
				);
			}
		} else { // listing
			$calendars = $calendar->getCalendars();
			if ($calendars[0] != CalendarModel::NO_ERRORS) {
				$site_title .= ' | Hiba';
				$template = file_get_contents(VIEW_PATH.'error.html');
			} elseif (!count($calendars[1])) {
				$site_title .= ' | Naptárak';
				$template = file_get_contents(VIEW_PATH.'listing_noitems.html');
			} else {
				$site_title .= ' | Naptárak';
				$list = '';
				$options = '';
				foreach ($calendars[1] as $cal) {
					$list .= '<div class="calendar-row">'.$cal->getSummary().'</div>';
					$options .= '<option value="'.$cal->getId().'">'.$cal->getSummary().'</option>';
				}
				$template = str_replace(
					array(
						'[CALENDAR_LIST]',
						'[CALENDAR_LIST_OPTIONS]'
					),
					array(
						$list,
						$options
					),
					file_get_contents(VIEW_PATH.'listing.html')
				);
			}
		}
	} else { // other cases (token error, authorization error, etc.)
		$site_title .= ' | Hiba';
		$template = file_get_contents(VIEW_PATH.'error.html');
	}
?>
<!DOCTYPE html>
<html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<title><?php echo $site_title; ?></title>
<link rel="stylesheet" href="./design/styles.css" />
</head>
<body>
	<?php echo $template; ?>
	<div id="footer">Hajdu György, 2015<br />reapest@gmail.com</div>
</body>
</html>