<?php
	define ('BASE_PATH',getcwd().'/');
	define ('APP_PATH',BASE_PATH.'app/');
	// app specific
	define ('APPLICATION_NAME', 'Google Calendar Listing - Artisan');
	define ('CLIENT_SECRET_JSON',BASE_PATH.'app/config/google_api_secret.json');
	define ('CREDENTIALS_SESSION', 'gcal_reapest');
	define ('VIEW_PATH', APP_PATH.'views/');
?>