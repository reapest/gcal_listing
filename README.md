# Google Calendar Listing

## Introduction

Displays the list of calendars for the signed in user utilizing Google API PHP Client.
Also able to add a dummy event to a selected calendar. This event will be named "Dummy Event [int]" (with a random number at its end) and will occur at noon, next day.

## Noteworthy Files

- index.php - entry point for the demo application and front controller
- app/config/config.php - configurations
- app/models/CalendarModel.php - utilizes the Google API client to achieve the required functionality
- app/libraries/Google/ - Google API PHP client files


## Further Information

Tested with PHP 5 (5.6.12 TS and 5.5.3 NTS)
Upload files to a server and start it with index.php!