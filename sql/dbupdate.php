<#1>
<?php
// Empty Step
/** @var $ilDB ilDBInterface */
?>
<#2>
<?php
if (!$ilDB->tableExists('xcge_user_import')) {
    $fields = [
        'id' => [
            'type' => 'integer',
            'length' => 4,
            'notnull' => false,
        ],
        'status' => [
            'type' => 'integer',
            'length' => 4,
            'notnull' => false,
        ],
        'data' => [
            'type' => 'clob',
            'notnull' => false,
        ],
    ];

    $tableName = "xcge_user_import";

    $ilDB->createTable($tableName, $fields);
    $ilDB->addPrimaryKey($tableName, ["id"]);
    $ilDB->createSequence($tableName);
}
?>
<#3>
<?php
if (!$ilDB->tableColumnExists('xcge_user_import', 'user')) {
    $ilDB->addTableColumn(
        'xcge_user_import',
        'user',
        [
            'type' => 'integer',
            'length' => 4,
            'notnull' => false,
        ]
    );
}
?>
<#4>
<?php
if (!$ilDB->tableColumnExists('xcge_user_import', 'created_timestamp')) {
    $ilDB->addTableColumn(
        'xcge_user_import',
        'created_timestamp',
        [
            'type' => 'timestamp',
            'notnull' => false,
            'default' => null
        ]
    );
}
?>
<#5>
<?php
if ($ilDB->tableColumnExists('xcge_user_import', 'created_timestamp')) {
    $ilDB->dropTableColumn('xcge_user_import', 'created_timestamp');
}

if (!$ilDB->tableColumnExists('xcge_user_import', 'created_timestamp')) {
    $ilDB->addTableColumn(
        'xcge_user_import',
        'created_timestamp',
        [
            'type' => 'integer',
            'length' => 8,
            'notnull' => true,
        ]
    );
}
?>
<#6>
<?php
if (!$ilDB->tableColumnExists('xcge_user_import', 'obj_id')) {
    $ilDB->addTableColumn(
        'xcge_user_import',
        'obj_id',
        [
            'type' => 'integer',
            'length' => 4,
            'notnull' => true,
        ]
    );
}
?>
<#7>
<?php
$task_ids = [];
$bucket_ids = [];

$result = $ilDB->query(
    'SELECT id, bucket_id FROM il_bt_task WHERE ' . $ilDB->like(
        'type',
        'text',
        '%ILIAS\\\\Plugin\\\\CrsGrpEnrollment%'
    )
);
while ($row = $ilDB->fetchAssoc($result)) {
    $task_ids[(int) $row['id']] = (int) $row['id'];
    $bucket_ids[(int) $row['bucket_id']] = (int) $row['bucket_id'];
}

$ilDB->manipulate(
    'DELETE FROM il_bt_value WHERE id IN (SELECT value_id FROM il_bt_value_to_task WHERE ' . $ilDB->in(
        'task_id',
        $task_ids,
        false,
        'integer'
    ) . ')'
);

$ilDB->manipulate(
    'DELETE FROM il_bt_value_to_task WHERE ' . $ilDB->in(
        'task_id',
        $task_ids,
        false,
        'integer'
    )
);

$ilDB->manipulate(
    'DELETE FROM il_bt_bucket WHERE ' . $ilDB->in(
        'id',
        $bucket_ids,
        false,
        'integer'
    )
);

$ilDB->manipulate(
    'DELETE FROM il_bt_task WHERE ' . $ilDB->in(
        'id',
        $task_ids,
        false,
        'integer'
    )
);

?>