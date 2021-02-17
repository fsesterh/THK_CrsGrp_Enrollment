<#1>
<?php
// Empty Step
/** @var ilDB $ilDB */
?>
<#2>
<?php
if (!$ilDB->tableExists('xcge_user_import')) {
    $fields = array(
        'id' => array(
            'type' => 'integer',
            'length' => 4,
            'notnull' => true,
            'default' => 0,
        ),
        'status' => array(
            'type' => 'integer',
            'length' => 4,
            'notnull' => true,
            'default' => 0,
        ),
        'data' => array(
            'type' => 'clob',
            'notnull' => false,
            'default' => null,
        ),
    );

    $tableName = "xcge_user_import";

    $ilDB->createTable($tableName, $fields);
    $ilDB->addPrimaryKey($tableName, array("id"));
    $ilDB->createSequence($tableName);
}
?>
<#3>
<?php
if (!$ilDB->tableColumnExists('xcge_user_import', 'user')) {
    $ilDB->addTableColumn(
        'xcge_user_import',
        'user',
        array(
            'type' => 'integer',
            'length' => 4,
            'notnull' => true,
            'default' => 0
        )
    );
}
?>
<#4>
<?php
if (!$ilDB->tableColumnExists('xcge_user_import', 'created_timestamp')) {
    $ilDB->addTableColumn(
        'xcge_user_import',
        'created_timestamp',
        array(
            'type' => 'timestamp',
            'notnull' => false,
        )
    );
}
?>
<#5>
<?php
$ilDB->modifyTableColumn(
    'xcge_user_import',
    'created_timestamp',
    array(
        'type' => 'integer',
        'length' => 8,
        'notnull' => true,
        'default' => 0
    )
);
if (!$ilDB->tableColumnExists('xcge_user_import', 'obj_id')) {
    $ilDB->addTableColumn(
        'xcge_user_import',
        'obj_id',
        array(
            'type' => 'integer',
            'length' => 4,
            'notnull' => true,
            'default' => 0
        )
    );
}
?>