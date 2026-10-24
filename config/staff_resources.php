<?php

declare(strict_types=1);

/*
 * Game data editable in the staff panel at /admin/data/{key}. Replaces
 * staff_cities.php, staff_houses.php, staff_courses.php, staff_crimes.php,
 * staff_items.php (types and items), staff_shops.php and staff_jobs.php.
 *
 * Field types: string, text, int, money, bool, select (from another table),
 * formula (crime success formula), pivot (many-to-many checkboxes), effects (item
 * effects editor), user (a player ID, optional, must exist), choice (fixed
 * 'options' => [value => label]).
 * Options per resource:
 *   protected_ids       rows that can never be deleted
 *   reassign_on_delete  [table, column, new value] applied before deleting
 *   block_delete_if     [table, column, message] refuse while rows still point here
 */

$text = ['type' => 'text', 'rules' => 'string|max:65535'];

return [
    'cities' => [
        'title' => 'Cities',
        'singular' => 'city',
        'table' => 'cities',
        'permission' => 'manage_cities',
        'fields' => [
            'name' => ['label' => 'Name', 'type' => 'string', 'rules' => 'required|string|max:255', 'unique' => true],
            'description' => ['label' => 'Description'] + $text,
            'min_level' => ['label' => 'Minimum level', 'type' => 'int', 'rules' => 'required|integer|min:1', 'default' => 1],
        ],
        'columns' => ['name', 'min_level'],
        'protected_ids' => [1],
        'reassign_on_delete' => [['users', 'city_id', 1], ['shops', 'city_id', 1]],
    ],

    'houses' => [
        'title' => 'Houses',
        'singular' => 'house',
        'table' => 'houses',
        'permission' => 'manage_houses',
        'fields' => [
            'name' => ['label' => 'Name', 'type' => 'string', 'rules' => 'required|string|max:255'],
            'price' => ['label' => 'Price', 'type' => 'money', 'rules' => 'required|integer|min:0'],
            'will' => ['label' => 'Maximum will', 'type' => 'int', 'rules' => 'required|integer|min:1', 'unique' => true],
        ],
        'columns' => ['name', 'price', 'will'],
        'protected_ids' => [1],
        'reassign_on_delete' => [['users', 'house_id', 1]],
    ],

    'courses' => [
        'title' => 'Courses',
        'singular' => 'course',
        'table' => 'courses',
        'permission' => 'manage_courses',
        'fields' => [
            'name' => ['label' => 'Name', 'type' => 'string', 'rules' => 'required|string|max:255'],
            'description' => ['label' => 'Description'] + $text,
            'cost' => ['label' => 'Cost (money)', 'type' => 'money', 'rules' => 'required|integer|min:0'],
            'energy' => ['label' => 'Cost (energy)', 'type' => 'int', 'rules' => 'required|integer|min:0'],
            'days' => ['label' => 'Length (days)', 'type' => 'int', 'rules' => 'required|integer|min:1', 'default' => 1],
            'strength' => ['label' => 'Strength gain', 'type' => 'int', 'rules' => 'required|integer|min:0', 'default' => 0],
            'agility' => ['label' => 'Agility gain', 'type' => 'int', 'rules' => 'required|integer|min:0', 'default' => 0],
            'guard' => ['label' => 'Guard gain', 'type' => 'int', 'rules' => 'required|integer|min:0', 'default' => 0],
            'labour' => ['label' => 'Labour gain', 'type' => 'int', 'rules' => 'required|integer|min:0', 'default' => 0],
            'iq' => ['label' => 'IQ gain', 'type' => 'int', 'rules' => 'required|integer|min:0', 'default' => 0],
        ],
        'columns' => ['name', 'cost', 'days'],
    ],

    'crime-groups' => [
        'title' => 'Crime groups',
        'singular' => 'crime group',
        'table' => 'crime_groups',
        'permission' => 'manage_crimes',
        'order_by' => 'sort_order',
        'fields' => [
            'name' => ['label' => 'Name', 'type' => 'string', 'rules' => 'required|string|max:100'],
            'sort_order' => ['label' => 'Order', 'type' => 'int', 'rules' => 'required|integer|min:0', 'unique' => true],
        ],
        'columns' => ['sort_order', 'name'],
        'block_delete_if' => [['crimes', 'group_id', 'This group still has crimes. Move or delete them first.']],
    ],

    'crimes' => [
        'title' => 'Crimes',
        'singular' => 'crime',
        'table' => 'crimes',
        'permission' => 'manage_crimes',
        'order_by' => 'group_id, brave_cost',
        'fields' => [
            'name' => ['label' => 'Name', 'type' => 'string', 'rules' => 'required|string|max:100'],
            'group_id' => ['label' => 'Group', 'type' => 'select', 'source' => 'crime_groups'],
            'brave_cost' => ['label' => 'Brave cost', 'type' => 'int', 'rules' => 'required|integer|min:1'],
            'success_formula' => [
                'label' => 'Success % formula',
                'type' => 'formula',
                'default' => '((WILL*0.8)/2.5)+(LEVEL/4)',
                'hint' => 'Arithmetic using LEVEL, CRIMEXP, EXP, WILL and IQ.',
            ],
            'success_money' => ['label' => 'Success money', 'type' => 'money', 'rules' => 'required|integer|min:0', 'default' => 0],
            'success_crystals' => ['label' => 'Success crystals', 'type' => 'int', 'rules' => 'required|integer|min:0', 'default' => 0],
            'success_item_id' => ['label' => 'Success item', 'type' => 'select', 'source' => 'items', 'nullable' => true],
            'crime_exp' => ['label' => 'Crime XP given', 'type' => 'int', 'rules' => 'required|integer|min:0', 'default' => 0],
            'jail_minutes' => ['label' => 'Jail time (minutes)', 'type' => 'int', 'rules' => 'required|integer|min:0', 'default' => 0],
            'jail_reason' => ['label' => 'Jail reason', 'type' => 'string', 'rules' => 'string|max:255'],
            'initial_text' => ['label' => 'Initial text'] + $text,
            'success_text' => ['label' => 'Success text', 'hint' => '{money} is replaced with the amount won.'] + $text,
            'failure_text' => ['label' => 'Failure text'] + $text,
            'jail_text' => ['label' => 'Jail text'] + $text,
        ],
        'columns' => ['name', 'group_id', 'brave_cost', 'success_money'],
    ],

    'item-types' => [
        'title' => 'Item types',
        'singular' => 'item type',
        'table' => 'item_types',
        'permission' => 'manage_items',
        'fields' => [
            'name' => ['label' => 'Name', 'type' => 'string', 'rules' => 'required|string|max:255', 'unique' => true],
        ],
        'columns' => ['name'],
        'block_delete_if' => [['items', 'type_id', 'Items still use this type. Change or delete them first.']],
    ],

    'items' => [
        'title' => 'Items',
        'singular' => 'item',
        'table' => 'items',
        'permission' => 'manage_items',
        'order_by' => 'type_id, name',
        'fields' => [
            'name' => ['label' => 'Name', 'type' => 'string', 'rules' => 'required|string|max:255'],
            'type_id' => ['label' => 'Type', 'type' => 'select', 'source' => 'item_types', 'nullable' => true],
            'description' => ['label' => 'Description'] + $text,
            'buy_price' => ['label' => 'Shop price', 'type' => 'money', 'rules' => 'required|integer|min:0', 'default' => 0],
            'sell_price' => ['label' => 'Sell value', 'type' => 'money', 'rules' => 'required|integer|min:0', 'default' => 0],
            'buyable' => ['label' => 'Can be bought in shops', 'type' => 'bool', 'default' => 1],
            'weapon' => ['label' => 'Weapon power', 'type' => 'int', 'rules' => 'required|integer|min:0', 'default' => 0],
            'armor' => ['label' => 'Armor defense', 'type' => 'int', 'rules' => 'required|integer|min:0', 'default' => 0],
            'effects' => ['label' => 'Effects when used', 'type' => 'effects', 'max' => 3, 'hint' => 'Leave the stat empty to switch an effect off. Percentages of energy, will, brave and health are of the maximum.'],
        ],
        'columns' => ['name', 'type_id', 'buy_price', 'weapon', 'armor'],
    ],

    'shops' => [
        'title' => 'Shops',
        'singular' => 'shop',
        'table' => 'shops',
        'permission' => 'manage_shops',
        'fields' => [
            'name' => ['label' => 'Name', 'type' => 'string', 'rules' => 'required|string|max:255'],
            'description' => ['label' => 'Description'] + $text,
            'city_id' => ['label' => 'City', 'type' => 'select', 'source' => 'cities'],
            'items' => [
                'label' => 'Stock',
                'type' => 'pivot',
                'pivot' => 'shop_items',
                'local_key' => 'shop_id',
                'foreign_key' => 'item_id',
                'source' => 'items',
            ],
        ],
        'columns' => ['name', 'city_id'],
    ],

    'jobs' => [
        'title' => 'Jobs',
        'singular' => 'job',
        'table' => 'jobs',
        'permission' => 'manage_jobs',
        'fields' => [
            'name' => ['label' => 'Name', 'type' => 'string', 'rules' => 'required|string|max:255'],
            'description' => ['label' => 'Description', 'type' => 'string', 'rules' => 'string|max:255'],
            'owner' => ['label' => 'Owner', 'type' => 'string', 'rules' => 'string|max:255'],
            'first_rank_id' => ['label' => 'First rank', 'type' => 'select', 'source' => 'job_ranks', 'nullable' => true,
                'hint' => 'Create the job, add its ranks, then come back and choose the first one.'],
        ],
        'columns' => ['name', 'owner', 'first_rank_id'],
    ],

    'job-ranks' => [
        'title' => 'Job ranks',
        'singular' => 'job rank',
        'table' => 'job_ranks',
        'permission' => 'manage_jobs',
        'order_by' => 'job_id, pay',
        'fields' => [
            'job_id' => ['label' => 'Job', 'type' => 'select', 'source' => 'jobs'],
            'name' => ['label' => 'Rank name', 'type' => 'string', 'rules' => 'required|string|max:255'],
            'pay' => ['label' => 'Pay', 'type' => 'money', 'rules' => 'required|integer|min:0', 'default' => 10],
            'strength_gain' => ['label' => 'Strength gain', 'type' => 'int', 'rules' => 'required|integer|min:0', 'default' => 0],
            'labour_gain' => ['label' => 'Labour gain', 'type' => 'int', 'rules' => 'required|integer|min:0', 'default' => 0],
            'iq_gain' => ['label' => 'IQ gain', 'type' => 'int', 'rules' => 'required|integer|min:0', 'default' => 0],
            'strength_required' => ['label' => 'Strength required', 'type' => 'int', 'rules' => 'required|integer|min:0', 'default' => 1],
            'labour_required' => ['label' => 'Labour required', 'type' => 'int', 'rules' => 'required|integer|min:0', 'default' => 1],
            'iq_required' => ['label' => 'IQ required', 'type' => 'int', 'rules' => 'required|integer|min:0', 'default' => 1],
        ],
        'columns' => ['job_id', 'name', 'pay'],
    ],

    'organised-crimes' => [
        'title' => 'Organised crimes',
        'singular' => 'organised crime',
        'table' => 'organised_crimes',
        'permission' => 'manage_gangs',
        'fields' => [
            'name' => ['label' => 'Name', 'type' => 'string', 'rules' => 'required|string|max:255'],
            'members_required' => ['label' => 'Members needed', 'type' => 'int', 'rules' => 'required|integer|min:1', 'default' => 2],
            'min_money' => ['label' => 'Minimum payout', 'type' => 'money', 'rules' => 'required|integer|min:0', 'default' => 0],
            'max_money' => ['label' => 'Maximum payout', 'type' => 'money', 'rules' => 'required|integer|min:0', 'default' => 0],
            'start_text' => ['label' => 'Start text'] + $text,
            'success_text' => ['label' => 'Success text'] + $text,
            'failure_text' => ['label' => 'Failure text'] + $text,
        ],
        'columns' => ['name', 'members_required', 'max_money'],
    ],

    // Legacy staff_gangs.php (record, credit, edit name / tag / finances / staff / capacity / announcement).
    'gangs' => [
        'title' => 'Gangs',
        'singular' => 'gang',
        'table' => 'gangs',
        'permission' => 'manage_gangs',
        'order_by' => 'name',
        'fields' => [
            'name' => ['label' => 'Name', 'type' => 'string', 'rules' => 'required|string|max:255', 'unique' => true],
            'prefix' => ['label' => 'Tag', 'type' => 'string', 'rules' => 'string|max:12'],
            'description' => ['label' => 'Description'] + $text,
            'announcement' => ['label' => 'Announcement'] + $text,
            'money' => ['label' => 'Vault money', 'type' => 'money', 'rules' => 'required|integer|min:0', 'default' => 0],
            'crystals' => ['label' => 'Vault crystals', 'type' => 'int', 'rules' => 'required|integer|min:0', 'default' => 0],
            'respect' => ['label' => 'Respect', 'type' => 'int', 'rules' => 'required|integer|min:1', 'default' => 100],
            'capacity' => ['label' => 'Capacity', 'type' => 'int', 'rules' => 'required|integer|min:1', 'default' => 5],
            'president_id' => ['label' => 'President (player ID)', 'type' => 'user'],
            'vice_president_id' => ['label' => 'Vice-president (player ID)', 'type' => 'user'],
        ],
        'columns' => ['name', 'prefix', 'respect', 'money', 'capacity'],
    ],

    // Legacy staff_forums.php. Gang forums are created automatically from the gang's page.
    'forums' => [
        'title' => 'Forums',
        'singular' => 'forum',
        'table' => 'forums',
        'permission' => 'manage_forums',
        'order_by' => 'access, sort_order, name',
        'fields' => [
            'name' => ['label' => 'Name', 'type' => 'string', 'rules' => 'required|string|max:255'],
            'description' => ['label' => 'Description', 'type' => 'string', 'rules' => 'string|max:255'],
            'access' => ['label' => 'Who can see it', 'type' => 'choice', 'options' => ['public' => 'Everyone', 'staff' => 'Staff (use_staff_forums)', 'gang' => 'One gang (automatic)'], 'default' => 'public'],
            'sort_order' => ['label' => 'Sort order', 'type' => 'int', 'rules' => 'required|integer', 'default' => 0],
        ],
        'columns' => ['name', 'access', 'description'],
    ],
];
