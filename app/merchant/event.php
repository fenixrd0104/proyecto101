<?php
// This is the admin application event definition file automatically generated by the system
return [
    'bind' => [
      // 'UserLogin' => 'app\event\UserLogin',
        // more event bindings
    ],
'listen' => [
        'MemberGroupChange' => ['app\merchant\listener\MemberGroupChange'],
        'MemberReferidReg' => ['app\merchant\listener\MemberReferidReg'],
        // more event listeners
    ],
];
