<?php

/**
 * This file is part of the Nextras community extensions of Nette Framework
 *
 * @license    MIT
 * @link       https://github.com/nextras
 * @author     Jan Skrasek
 */

namespace Nextras\Datagrid;

use Nette;



class Column extends Nette\Object
{

	/** @var string */
	public $name;

	/** @var string */
	public $label;

	/** @var string */
	protected $sort = FALSE;

	/** @var Datagrid */
	protected $grid;



	public function __construct($name, $label, Datagrid $grid)
	{
		$this->name = $name;
		$this->label = $label;
		$this->grid = $grid;
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
			return NULL;
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
