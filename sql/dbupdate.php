<#1>
<?php
// Empty Step
?>
<#2>
<?php
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

/** @var ilDB $ilDB */
global $ilDB;
$tableName = "xcge_user_import";

$ilDB->createTable($tableName, $fields);
$ilDB->addPrimaryKey($tableName, array("id"));
$ilDB->createSequence($tableName);
?>
<#3>
<?php
/** @var ilDB $ilDB */
global $ilDB;
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
?>
<#4>
<?php
/** @var ilDB $ilDB */
global $ilDB;
$ilDB->addTableColumn(
    'xcge_user_import',
    'created_timestamp',
    array(
        'type' => 'timestamp',
        'notnull' => false,
    )
);
?>
