<?php

/**
 * This file is part of the Nextras community extensions of Nette Framework
 *
 * @license    MIT
 * @link       https://github.com/nextras
 */

namespace Nextras\Datagrid;

use Nette;


class Column
{
	use Nette\SmartObject;

	/** @var string */
	public $name;

	/** @var string */
	public $label;

	/** @var bool */
	protected $sort = FALSE;

	/** @var bool */
	protected $required = FALSE;

	/** @var Datagrid */
	protected $grid;


	public function __construct($name, $label, Datagrid $grid)
	{
		$this->name = $name;
		$this->label = $label;
		$this->grid = $grid;
	}


	public function enableSort($default = NULL, $required = FALSE)
	{
		$this->sort = TRUE;
		$this->required = ($required && $default && $default !== Datagrid::ORDER_NONE);
		if ($default !== NULL) {
			if ($default !== Datagrid::ORDER_ASC && $default !== Datagrid::ORDER_DESC && $default !== Datagrid::ORDER_NONE) {
				throw new \InvalidArgumentException('Unknown order type.');
			}

			$this->grid->order[$this->name] = $default;
		}
		return $this;
	}


	public function canSort()
	{
		return $this->sort;
	}


	public function getNewState()
	{
		$state = $this->grid->order;
		if($this->isAsc()) {
			$state[$this->name] = Datagrid::ORDER_DESC;
		} elseif ($this->isDesc()) {
			$state[$this->name] = ($this->force ? Datagrid::ORDER_ASC : Datagrid::ORDER_NONE);
		} else {
			$state[$this->name] = Datagrid::ORDER_ASC;
		}

		return $state;
	}


	public function isAsc()
	{
		return isset($this->grid->order[$this->name]) && $this->grid->order[$this->name] === Datagrid::ORDER_ASC;
	}


	public function isDesc()
	{
		return isset($this->grid->order[$this->name]) && $this->grid->order[$this->name] === Datagrid::ORDER_DESC;
	}
}
