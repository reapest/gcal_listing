<?php
	require_once('./app/config/config.php');
	
	/**
	 * CalendarModel
	 *
	 * @author György Hajdu <reapest at gmail.com>
	 */
	class CalendarModel {
		
		const NO_ERRORS						= 0; // OK
		const CLIENT_ERROR_COULD_NOT_FETCH	= 1; // $client member is set but refreshing token failed
		const CLIENT_ERROR_AUTH_ERROR		= 2; // unable to authorize user
		const CLIENT_ERROR_AUTH_NEEDED		= 3; // user must be authorized first
		const CLIENT_ERROR_TOKEN_ERROR		= 4; // unable to get token for authorized user
		const CALENDAR_NO_USER				= 5; // an authorized user is needed
		const SERVICE_NOT_ALLOWED			= 6; // service not allowed
		const EVENT_CREATION_ERROR			= 7; // event could not be created
		
		private $scope = '';
		private $client = null;
		private $service = null;
		
		/**
		 * Loads library and sets scope
		 */
		public function __construct() {
			session_start();
			require_once(APP_PATH.'libraries/Google/autoload.php');
			//$this->scope = Google_Service_Calendar::CALENDAR_READONLY;
			$this->scope = Google_Service_Calendar::CALENDAR;
		}
		
		/**
		 * Returns an authorized API client.
		 * @param string $authCode obtained from Google's app authorization dialog
		 * @return array with error code and Google_Client the authorized client object
		 *  or null if error occured or authorization URL
		 */
		public function getClient($authCode='') {
			// try to fetch client from class member
			if ($this->client != null && !strlen($authCode)) {
				return ($this->refreshToken()) ?
					array(self::NO_ERRORS, $this->client) : 
					array(self::CLIENT_ERROR_COULD_NOT_FETCH, null);
			}
			
			$this->client = new Google_Client();
			$this->client->setApplicationName(APPLICATION_NAME);
			$this->client->setScopes($this->scope);
			$this->client->setAuthConfigFile(CLIENT_SECRET_JSON);
			$this->client->setAccessType('offline');
			
			$accessToken = null;
			try {
				if (strlen($authCode)) { // authenticate by code
					$accessToken = $this->client->authenticate($authCode);
					$_SESSION[CREDENTIALS_SESSION] = $accessToken;
				} elseif (isset($_SESSION[CREDENTIALS_SESSION])) { // authenticate by stored data
					$accessToken = $_SESSION[CREDENTIALS_SESSION];
				}
			} catch (Exception $e) { // Google or I/O exception
				return array(self::CLIENT_ERROR_AUTH_ERROR, null);
			}
			
			if ($accessToken == null) { // authorization required
				return array(self::CLIENT_ERROR_AUTH_NEEDED, $this->client->createAuthUrl());
			}
			
			// try to set access token:
			try {
				$this->client->setAccessToken($accessToken);
				if (!$this->refreshToken()) {
					throw new Exception('Unable to refresh token');
				}
			} catch (Exception $e) {
				return array(self::CLIENT_ERROR_TOKEN_ERROR, null);
			}
			
			return array(self::NO_ERRORS, $this->client);
		}
		
		/**
		 * Returns the upcoming 10 events
		 * @return array with error code and Event array or null if error occured
		 */
		public function getEvents() {
			$init_res = $this->initService();
			if ($init_res != self::NO_ERRORS) {return array($init_res, null);}
			
			$calendarId = 'primary';
			$optParams = array(
				'maxResults' => 10,
				'orderBy' => 'startTime',
				'singleEvents' => TRUE,
				'timeMin' => date('c'),
			);
			$results = $this->service->events->listEvents($calendarId, $optParams);
			return ($results && count($results->getItems())) ?
				array(self::NO_ERRORS, $results->getItems()) :
				array(self::NO_ERRORS, array());
		}
		
		/**
		 * Returns the list of calendars
		 * @return array with error code and Calendar array or null if error occured
		 */
		public function getCalendars() {
			$init_res = $this->initService();
			if ($init_res != self::NO_ERRORS) {return array($init_res, null);}
			
			$results = $this->service->calendarList->listCalendarList();
			return ($results && count($results->getItems())) ?
				array(self::NO_ERRORS, $results->getItems()) :
				array(self::NO_ERRORS, array());
		}
		
		/**
		 * Creates a simple dummy event with a duration of 1 hour
		 * @param string $calendar_id calendar ID
		 * @param int $date_from_ts Unix TS, from date
		 * @param string $name event name
		 * @return error code
		 */
		public function createDummyEvent($calendar_id,$date_from_ts,$name) {
			$init_res = $this->initService();
			if ($init_res != self::NO_ERRORS) {return array($init_res, null);}
			
			$date_to_ts = $date_from_ts + 3600;
			$location = 'Elsewhere';
			
			$event = new Google_Service_Calendar_Event();
			$event->setSummary($name);
			$event->setLocation($location);
			$start = new Google_Service_Calendar_EventDateTime();
			$start->setDateTime(date('c',$date_from_ts));
			$event->setStart($start);
			$end = new Google_Service_Calendar_EventDateTime();
			$end->setDateTime(date('c',$date_to_ts));
			$event->setEnd($end);
			try {
				$createdEvent = $this->service->events->insert($calendar_id, $event);
				return self::NO_ERRORS;
			} catch (Exception $e) {
				return self::EVENT_CREATION_ERROR;
			}
			return $createdEvent;
		}
		
		/**
		 * Forgets the access token to simulate logging out
		 * @return void
		 */
		public function logout() {
			unset($_SESSION[CREDENTIALS_SESSION]);
			$this->client = null;
			$this->service = null;
		}
		
		/**
		 * Obtain new access token if it has been expired and store it
		 * @return boolean true if actions were successful
		 */
		private function refreshToken() {
			if ($this->client == null) {return false;}
			try {
				if ($this->client->isAccessTokenExpired()) {
					$this->client->refreshToken($this->client->getRefreshToken());
					$_SESSION[CREDENTIALS_SESSION] = $this->client->getAccessToken();
				}
				return true;
			} catch (Exception $e) { // something went terribly wrong
				return false;
			}
		}
		
		private function initService() {
			if ($this->client == null) {return self::CALENDAR_NO_USER;}
			if ($this->service == null) {
				try {
					$this->service = new Google_Service_Calendar($this->client);
				} catch (Exception $e) {
					return self::SERVICE_NOT_ALLOWED;
				}
			}
			return self::NO_ERRORS;
		}
	}
?>