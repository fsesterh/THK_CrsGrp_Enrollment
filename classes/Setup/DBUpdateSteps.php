<?php

/**
 * This file is part of ILIAS, a powerful learning management system
 * published by ILIAS open source e-Learning e.V.
 *
 * ILIAS is licensed with the GPL-3.0,
 * see https://www.gnu.org/licenses/gpl-3.0.en.html
 * You should have received a copy of said license along with the
 * source code, too.
 *
 * If this is not the case or you just want to try ILIAS, you'll find
 * us at:
 * https://www.ilias.de
 * https://github.com/ILIAS-eLearning
 *
 *********************************************************************/

declare(strict_types=1);

namespace ILIAS\Plugin\CrsGrpEnrollment\Setup;

use ilDatabaseUpdateSteps;
use ilDBConstants;
use ilDBInterface;
use ilDBStepExecutionDB;
use ilDBStepReader;
use ILIAS\Plugin\CrsGrpEnrollment\Repositories\UserImportRepository;

class DBUpdateSteps implements ilDatabaseUpdateSteps
{
    public const PREFIX = "step_";

    private ilDBInterface $db;

    public function prepare(ilDBInterface $db): void
    {
        $this->db = $db;
    }

    public function install(ilDBInterface $db): void
    {
        $this->prepare($db);

        $execution_log = new ilDBStepExecutionDB($this->db, fn() => new \DateTime());
        $step_reader = new ilDBStepReader();

        $last_started_step = $execution_log->getLastStartedStep(self::class);
        $last_finished_step = $execution_log->getLastFinishedStep(self::class);

        foreach ($step_reader->readStepNumbers(self::class, self::PREFIX) as $step) {
            if ($step <= $last_finished_step) {
                continue;
            }
            $execution_log->started(self::class, $step);
            $method = self::PREFIX . $step;
            $this->$method();
            $execution_log->finished(self::class, $step);
        }
    }

    public function uninstall(ilDBInterface $db): void
    {
        $this->prepare($db);
        $this->db->manipulateF(
            "DELETE FROM il_db_steps WHERE class = %s",
            [ilDBConstants::T_TEXT],
            [self::class]
        );

        $tables_to_drop = [
            UserImportRepository::TABLE_NAME
        ];

        foreach ($tables_to_drop as $table_name) {
            $this->db->dropTable($table_name, false);
        }
    }

    public function step_1(): void
    {
        $table_name = "xcge_user_import";

        if ($this->db->tableExists($table_name)) {
            return;
        }

        $this->db->createTable(
            $table_name,
            [
                'id' => [
                    'type' => ilDBConstants::T_INTEGER,
                    'length' => 4,
                    'notnull' => false,
                ],
                'status' => [
                    'type' => ilDBConstants::T_INTEGER,
                    'length' => 4,
                    'notnull' => false,
                ],
                'data' => [
                    'type' => ilDBConstants::T_CLOB,
                    'notnull' => false,
                ],
                'user' => [
                    'type' => ilDBConstants::T_INTEGER,
                    'length' => 4,
                    'notnull' => false,
                ],
                'created_timestamp' => [
                    'type' => ilDBConstants::T_INTEGER,
                    'length' => 8,
                    'notnull' => true,
                ],
                'obj_id' => [
                    'type' => ilDBConstants::T_INTEGER,
                    'length' => 4,
                    'notnull' => true,
                ]
            ]
        );

        $this->db->addPrimaryKey($table_name, ["id"]);
        $this->db->createSequence($table_name);
    }

    public function step_2(): void
    {
        $task_ids = [];
        $bucket_ids = [];

        $result = $this->db->query(
            'SELECT id, bucket_id FROM il_bt_task WHERE ' . $this->db->like(
                'type',
                ilDBConstants::T_TEXT,
                '%ILIAS\\\\Plugin\\\\CrsGrpEnrollment%'
            )
        );
        while ($row = $this->db->fetchAssoc($result)) {
            $task_ids[(int) $row['id']] = (int) $row['id'];
            $bucket_ids[(int) $row['bucket_id']] = (int) $row['bucket_id'];
        }

        $this->db->manipulate(
            'DELETE FROM il_bt_value WHERE id IN (SELECT value_id FROM il_bt_value_to_task WHERE ' . $this->db->in(
                'task_id',
                $task_ids,
                false,
                ilDBConstants::T_INTEGER
            ) . ')'
        );

        $this->db->manipulate(
            'DELETE FROM il_bt_value_to_task WHERE ' . $this->db->in(
                'task_id',
                $task_ids,
                false,
                ilDBConstants::T_INTEGER
            )
        );

        $this->db->manipulate(
            'DELETE FROM il_bt_bucket WHERE ' . $this->db->in(
                'id',
                $bucket_ids,
                false,
                ilDBConstants::T_INTEGER
            )
        );

        $this->db->manipulate(
            'DELETE FROM il_bt_task WHERE ' . $this->db->in(
                'id',
                $task_ids,
                false,
                ilDBConstants::T_INTEGER
            )
        );
    }
}
