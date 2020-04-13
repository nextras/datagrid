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

	/** @var string */
	protected $sort = FALSE;

	/** @var Datagrid */
	protected $grid;

	/** @var array */
	protected $attributes = [];


	public function __construct($name, $label, Datagrid $grid)
	{
		$this->name = $name;
		$this->label = $label;
		$this->grid = $grid;
	}


	/**
	 * @param string $name
	 * @param mixed $value
	 * @return self
	 */
	public function setAttribute($name, $value = true)
	{
		$this->attributes[$name] = $value;
		return $this;
	}


	/**
	 * @param string $name
	 * @return bool
	 */
	public function hasAttribute($name)
	{
		return isset($this->attributes[$name]);
	}


	/**
	 * @param string $name
	 * @param mixed $default
	 * @return self
	 */
	public function getAttribute($name, $default = null)
	{
		return $this->attributes[$name] ?? $default;
	}


	public function enableSort($default = NULL)
	{
		$this->sort = TRUE;
		if ($default !== NULL) {
			if ($default !== Datagrid::ORDER_ASC && $default !== Datagrid::ORDER_DESC) {
				throw new \InvalidArgumentException('Unknown order type.');
			}

			$this->grid->orderColumn = $this->name;
			$this->grid->orderType = $default;
		}
		return $this;
	}


	public function canSort()
	{
		return $this->sort;
	}


	public function getNewState()
	{
		if ($this->isAsc()) {
			return Datagrid::ORDER_DESC;
		} elseif ($this->isDesc()) {
			return '';
		} else {
			return Datagrid::ORDER_ASC;
		}
	}


	public function isAsc()
	{
		return $this->grid->orderColumn === $this->name && $this->grid->orderType === Datagrid::ORDER_ASC;
	}


	public function isDesc()
	{
		return $this->grid->orderColumn === $this->name && $this->grid->orderType === Datagrid::ORDER_DESC;
	}
}
