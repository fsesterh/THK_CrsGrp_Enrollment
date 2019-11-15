<?php
/**
 * This file is part of SebastianFeldmann\Git.
 *
 * (c) Sebastian Feldmann <sf@sebastian-feldmann.info>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace SebastianFeldmann\Git\Command\DiffTree;

use SebastianFeldmann\Git\Command\Base;

/**
 * Class ChangedFiles
 *
 * @package SebastianFeldmann\Git
 * @author  Sebastian Feldmann <sf@sebastian-feldmann.info>
 * @link    https://github.com/sebastianfeldmann/git
 * @since   Class available since Release 2.0.1
 */
class ChangedFiles extends Base
{
    /**
     * @var string
     */
    private $from;

    /**
     * @var string
     */
    private $to;

    /**
     * @param  string $from
     * @return \SebastianFeldmann\Git\Command\DiffTree\ChangedFiles
     */
    public function fromRevision(string $from): ChangedFiles
    {
        $this->from = $from;
        return $this;
    }

    /**
     * @param  string $to
     * @return \SebastianFeldmann\Git\Command\DiffTree\ChangedFiles
     */
    public function toRevision(string $to): ChangedFiles
    {
        $this->to = $to;
        return $this;
    }

    /**
     * Return the command to execute.
     *
     * @return string
     * @throws \RuntimeException
     */
    protected function getGitCommand(): string
    {
        return 'diff-tree --no-commit-id --name-only -r ' . $this->getVersionsToCompare();
    }

    /**
     * Return the from commit id to diff
     *
     * @return string
     */
    protected function getVersionsToCompare(): string
    {
        return escapeshellarg($this->from) . (empty($this->to) ? '' : ' ' . escapeshellarg($this->to));
    }
}