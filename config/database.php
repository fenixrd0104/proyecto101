<?php
// +----------------------------------------------------------------------
// | ThinkPHP [ WE CAN DO IT JUST THINK ]
// +----------------------------------------------------------------------
// | Copyright (c) 2006~2018 http://thinkphp.cn All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: liu21st <liu21st@gmail.com>Knife Source Network www.yiqucode.com
// +----------------------------------------------------------------------

use think\facade\Env;

return [
    // The default database connection configuration used
    'default' => Env::get('database.driver', 'mysql'),

    // database connection configuration information
    'connections' => [
        'mysql' => [
            // database type
            'type' => Env::get('database.type', 'mysql'),
            // server address
            'hostname' => Env::get('database.hostname', '127.0.0.1'),
            // data storage name
            'database' => Env::get('database.database', 'dkewl_com'),
            // username
            'username' => Env::get('database.username', 'dkewl_com'),
            // password
            'password' => Env::get('database.password', 'dkewl_com'),
            // port
            'hostport' => Env::get('database.hostport', '3306'),
            // connect to dsn
            'dsn' => '',
            // database connection parameters
            'params' => [],
            // The database encoding is utf8 by default
            'charset'         => Env::get('database.charset', 'utf8'),
           // database table prefix
            'prefix' => Env::get('database.prefix', 'think_'),
            // database debug mode
            'debug' => Env::get('database.debug', true),
            // Database deployment method: 0 centralized (single server), 1 distributed (master-slave server)
            'deploy' => 0,
            // Whether the database read and write is separated, master-slave is valid
            'rw_separate' => false,
            // The number of master servers after read and write separation
            'master_num' => 1,
            // Specify the serial number of the slave server
            'slave_no' => '',
            // Whether to strictly check whether the field exists
            'fields_strict' => true,
            // Whether SQL performance analysis is required
            'sql_explain' => false,
            // Builder class
            'builder' => '',
            // Query class
            'query' => '',
            // Do you need to disconnect and reconnect
            'break_reconnect' => false,
        ],

       // more database configuration information
    ],
    // Automatically write the timestamp field
    'auto_timestamp' => false,
    // The default time format after the time field is taken out
    'datetime_format' => 'Y-m-d H:i:s',
];
