<?php

/////////////////////////////////////////////////////////////////////////////
// General information
/////////////////////////////////////////////////////////////////////////////

$app['basename'] = 'mode';
$app['version'] = '5.9.9.0';
$app['release'] = '1';
$app['vendor'] = 'ClearFoundation';
$app['packager'] = 'ClearFoundation';
$app['license'] = 'GPLv3';
$app['license_core'] = 'LGPLv3';
$app['summary'] = 'System Mode';
$app['description'] = 'The mode manager... master/slave/standalone.'; // FIXME: translate

/////////////////////////////////////////////////////////////////////////////
// App name and categories
/////////////////////////////////////////////////////////////////////////////

$app['name'] = lang('mode_system_mode');
$app['category'] = lang('base_category_system');
$app['subcategory'] = lang('base_subcategory_settings');

/////////////////////////////////////////////////////////////////////////////
// Packaging
/////////////////////////////////////////////////////////////////////////////
/*

$app['core_dependencies'] = array(
    'app-cron-core', 
    'ntpdate >= 4.2.4p8'
);

$app['core_file_manifest'] = array( 
   'app-date.cron' => array(
        'target' => '/etc/cron.d/app-date',
        'mode' => '0644',
        'onwer' => 'root',
        'group' => 'root',
    ),

   'timesync' => array(
        'target' => '/usr/sbin/timesync',
        'mode' => '0755',
        'onwer' => 'root',
        'group' => 'root',
    ),
);
*/