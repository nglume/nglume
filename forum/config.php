<?php if (!defined('APPLICATION')) {
    exit();
}

// Conversations
$Configuration['Conversations']['Version'] = '2.2b1';

// Database
$Configuration['Database']['Name'] = getenv('MYSQL_DATABASE');
$Configuration['Database']['Host'] = getenv('VANILLADATABASE_PORT_3306_TCP_ADDR');
$Configuration['Database']['User'] = getenv('MYSQL_USER');
$Configuration['Database']['Password'] = getenv('MYSQL_DATABASE');

// EnabledApplications
$Configuration['EnabledApplications']['Conversations'] = 'conversations';
$Configuration['EnabledApplications']['Vanilla'] = 'vanilla';

// EnabledPlugins
$Configuration['EnabledPlugins']['GettingStarted'] = 'GettingStarted';
$Configuration['EnabledPlugins']['HtmLawed'] = 'HtmLawed';

// Garden
$Configuration['Garden']['Title'] = 'Spira';
$Configuration['Garden']['Cookie']['Salt'] = '';
$Configuration['Garden']['Cookie']['Domain'] = '';
$Configuration['Garden']['Registration']['ConfirmEmail'] = true;
$Configuration['Garden']['Email']['SupportName'] = 'Spira';
$Configuration['Garden']['InputFormatter'] = 'Html';
$Configuration['Garden']['Version'] = '2.2b1';
$Configuration['Garden']['Cdns']['Disable'] = false;
$Configuration['Garden']['CanProcessImages'] = true;
$Configuration['Garden']['Installed'] = false;

// Plugins
$Configuration['Plugins']['GettingStarted']['Dashboard'] = '1';

// Routes
$Configuration['Routes']['DefaultController'] = 'discussions';

// Vanilla
$Configuration['Vanilla']['Version'] = '2.2b1';
