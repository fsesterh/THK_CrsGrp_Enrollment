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

use ilDatabaseUpdateStepsExecutedObjective;
use ILIAS\Refinery\Transformation;
use ILIAS\Setup;
use ILIAS\Setup\Agent;
use ILIAS\Setup\Config;
use ILIAS\Setup\Metrics;
use ILIAS\Setup\Objective;
use ILIAS\Setup\Objective\NullObjective;
use LogicException;

class SetupAgent implements Agent
{
    public const string PLUGIN_NAME = "CrsGrpEnrollment";

    public function __construct()
    {
    }

    public function hasConfig(): bool
    {
        return false;
    }

    public function getArrayToConfigTransformation(): Transformation
    {
        throw new LogicException(self::class . " has no Config.");
    }

    public function getInstallObjective(?Config $config = null): Objective
    {
        return new Setup\ObjectiveCollection(
            $this->getPluginNamespace(),
            true,
            new ilDatabaseUpdateStepsExecutedObjective(new DBUpdateSteps()),
        );
    }

    public function getUpdateObjective(?Config $config = null): Objective
    {
        return new Setup\ObjectiveCollection(
            $this->getPluginNamespace(),
            true,
            new ilDatabaseUpdateStepsExecutedObjective(new DBUpdateSteps()),
        );
    }

    public function getBuildObjective(): Objective
    {
        return new NullObjective();
    }

    public function getStatusObjective(Metrics\Storage $storage): Objective
    {
        return new NullObjective();
    }

    public function getMigrations(): array
    {
        return [];
    }

    public function getNamedObjectives(?Config $config = null): array
    {
        return [];
    }

    private function getPluginNamespace(): string
    {
        return "ILIAS\\Plugin\\" . self::PLUGIN_NAME;
    }
}
