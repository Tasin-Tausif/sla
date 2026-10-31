<?php

return [
    'permissions' => [
        'dashboard' => [
            'dashboard.view' => 'View the dashboard',
        ],
        'tickets' => [
            'tickets.viewAny' => 'View ticket lists',
            'tickets.view' => 'View tickets',
            'tickets.create' => 'Create tickets',
            'tickets.update' => 'Update tickets',
            'tickets.assign' => 'Assign tickets',
            'tickets.accept' => 'Accept tickets',
            'tickets.resolve' => 'Resolve tickets',
            'tickets.close' => 'Close tickets',
            'tickets.reopen' => 'Reopen tickets',
            'tickets.reply' => 'Reply to tickets',
            'tickets.internal-note' => 'Add internal notes',
            'tickets.import' => 'Import tickets',
        ],
        'configuration' => [
            'users.manage' => 'Manage users and agents',
            'roles.manage' => 'Manage roles',
            'categories.manage' => 'Manage ticket categories',
            'sla.manage' => 'Manage SLA policies and calendars',
            'audit.view' => 'View configuration history',
        ],
    ],
    'system_roles' => [
        'admin' => '*',
        'agent' => [
            'dashboard.view',
            'tickets.viewAny',
            'tickets.view',
            'tickets.update',
            'tickets.accept',
            'tickets.resolve',
            'tickets.close',
            'tickets.reopen',
            'tickets.reply',
            'tickets.internal-note',
            'tickets.import',
        ],
        'customer' => [
            'tickets.viewAny',
            'tickets.view',
            'tickets.create',
            'tickets.update',
            'tickets.reply',
        ],
    ],
];
