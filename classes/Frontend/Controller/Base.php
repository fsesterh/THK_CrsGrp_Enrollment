<?php
/* Copyright (c) 1998-2019 ILIAS open source, Extended GPL, see docs/LICENSE */

namespace ILIAS\Plugin\Proctorio\Frontend\Controller;

use \ILIAS\DI\Container;
use \ILIAS\UI\Factory;
use \ILIAS\UI\Renderer;
use \Psr\Http\Message\ServerRequestInterface;

/**
 * @author Michael Jansen <mjansen@databay.de>
 */
abstract class Base
{
	const CTX_IS_BASE_CLASS = 'baseClass';
	const CTX_IS_COMMAND_CLASS = 'cmdClass';
	const CTX_IS_COMMAND = 'cmd';
	const CTX_IS_CTRL_OBJ_TYPE = 'ctrl_obj_type';
	const CTX_IS_CTRL_OBJ_ID = 'ctrl_obj_id';

	/**
	 * The main controller of the Plugin
	 * @var \ilProctorioUIHookGUI
	 */
	public $coreController;

	/** @var ServerRequestInterface */
	protected $request;

	/** @var \ilTemplate */
	protected $tpl;

	/** @var Factory */
	protected $uiFactory;

	/** @var \ilCtrl */
	protected $ctrl;

	/** @var Renderer */
	protected $uiRenderer;

	/** @var Container */
	protected $dic;

	/** @var \ilObjuser */
	protected $user;

	/**
	 * @var array
	 */
	protected $parameters = [];

	/**
	 * Base constructor.
	 * @param \ilProctorioUIHookGUI $controller
	 * @param Container              $dic
	 */
	final public function __construct(\ilProctorioUIHookGUI $controller, Container $dic)
	{
		$this->coreController = $controller;
		$this->dic            = $dic;

		$this->request    = $dic->http()->request();
		$this->ctrl       = $dic->ctrl();
		$this->tpl        = $dic->ui()->mainTemplate();
		$this->user       = $dic->user();
		$this->uiRenderer = $dic->ui()->renderer();
		$this->uiFactory  = $dic->ui()->factory();

		$this->init();
	}

	/**
	 * @param string $name
	 * @param array  $arguments
	 * @return mixed
	 */
	final public function __call(string $name, array $arguments)
	{
		return call_user_func_array([$this, $this->getDefaultCommand()], []);
	}

	/**
	 * @return string
	 */
	abstract public function getDefaultCommand() : string;

	/**
	 *
	 */
	protected function init()
	{
	}

	/**
	 * @return \ilProctorioUIHookGUI
	 */
	public function getCoreController() : \ilProctorioUIHookGUI
	{
		return $this->coreController;
	}

	/**
	 * @param string $a_context
	 * @param string $a_value_a
	 * @param string $a_value_b
	 * @return bool
	 */
	final public function isContext($a_context, string $a_value_a = '', string $a_value_b = '') : bool
	{
		switch ($a_context) {
			case self::CTX_IS_BASE_CLASS:
			case self::CTX_IS_COMMAND_CLASS:
				$class = isset($_GET[$a_context]) ? $_GET[$a_context] : '';
				return strlen($class) > 0 && in_array(strtolower($class),
						array_map('strtolower', (array) $a_value_a));

			case self::CTX_IS_COMMAND:
				$cmd = isset($_GET[$a_context]) ? $_GET[$a_context] : '';
				return strlen($cmd) > 0 && in_array(strtolower($cmd), array_map('strtolower', (array) $a_value_a));

			case self::CTX_IS_CTRL_OBJ_TYPE:
				return strtolower($this->dic['ilObjDataCache']->lookupType($this->dic['ilObjDataCache']->lookupObjId((int) $_GET['ref_id']))) == strtolower($a_value_a);

			case self::CTX_IS_CTRL_OBJ_ID:
				return $this->dic['ilObjDataCache']->lookupObjId((int) $_GET['ref_id']) == $a_value_a;
		}

		return false;
	}

	/**
	 * @return Container
	 */
	public function getDic() : Container
	{
		return $this->dic;
	}

	/**
	 * @return string
	 * @throws \ReflectionException
	 */
	final public function getControllerName() : string
	{
		return (new \ReflectionClass($this))->getShortName();
	}
}