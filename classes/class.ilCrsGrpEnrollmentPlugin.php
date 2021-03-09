<?php declare(strict_types=1);
/* Copyright (c) 1998-2021 ILIAS open source, Extended GPL, see docs/LICENSE */

/**
 * Class ilCrsGrpEnrollmentPlugin
 * @author Timo Müller <timomueller@databay.de>
 */
class ilCrsGrpEnrollmentPlugin extends ilUserInterfaceHookPlugin
{
    /** @var string */
    const CTYPE = 'Services';
    /** @var string */
    const CNAME = 'UIComponent';
    /** @var string */
    const SLOT_ID = 'uihk';
    /** @var string */
    const PNAME = 'CrsGrpEnrollment';
    /** @var self */
    private static $instance = null;
    /** @var bool */
    protected static $initialized = false;
    /** @var \ILIAS\DI\Container */
    protected $dic;

    /**
     * @inheritDoc
     */
    public function __construct()
    {
        global $DIC;

        parent::__construct();

        $this->dic = $DIC;
    }

    /**
     * @inheritdoc
     */
    public function getPluginName()
    {
        return self::PNAME;
    }

    /**
     * @inheritdoc
     */
    protected function init()
    {
        parent::init();
        $this->registerAutoloader();

        if (!self::$initialized) {
            self::$initialized = true;
        }
    }

    /**
     * @inheritDoc
     */
    protected function afterUninstall()
    {
        parent::afterUninstall();

        if ($this->dic->database()->tableExists('xcge_user_import')) {
            $this->dic->database()->dropTable('xcge_user_import');
        }

        $task_ids = [];
        $bucket_ids = [];

        $result = $this->dic->database()->query(
            'SELECT id, bucket_id FROM il_bt_task WHERE ' . $this->dic->database()->like(
                'type',
                'text',
                '%ILIAS\\\\Plugin\\\\CrsGrpEnrollment%'
            )
        );
        while ($row = $this->dic->database()->fetchAssoc($result)) {
            $task_ids[(int) $row['id']] = (int) $row['id'];
            $bucket_ids[(int) $row['bucket_id']] = (int) $row['bucket_id'];
        }

        $this->dic->database()->manipulate('
            DELETE FROM il_bt_value WHERE id IN (
                SELECT value_id FROM il_bt_value_to_task WHERE ' . $this->dic->database()->in(
            'task_id',
            $task_ids,
            false,
            'integer'
        ) . '
            )
        ');

        $this->dic->database()->manipulate(
            '
            DELETE FROM il_bt_value_to_task WHERE ' . $this->dic->database()->in(
                'task_id',
                $task_ids,
                false,
                'integer'
            )
        );

        $this->dic->database()->manipulate(
            '
            DELETE FROM il_bt_bucket WHERE ' . $this->dic->database()->in(
                'id',
                $bucket_ids,
                false,
                'integer'
            )
        );

        $this->dic->database()->manipulate(
            '
            DELETE FROM il_bt_task WHERE ' . $this->dic->database()->in(
                'id',
                $task_ids,
                false,
                'integer'
            )
        );
    }

    /**
     * Registers the plugin autoloader
     */
    public function registerAutoloader() : void
    {
        require_once __DIR__ . '/../vendor/autoload.php';
    }

    /**
     * @return self
     */
    public static function getInstance() : self
    {
        if (null === self::$instance) {
            return self::$instance = ilPluginAdmin::getPluginObject(
                self::CTYPE,
                self::CNAME,
                self::SLOT_ID,
                self::PNAME
            );
        }

        return self::$instance;
    }
}
