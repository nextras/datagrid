<?php

/**
 * This file is part of the Nextras community extensions of Nette Framework
 *
 * Copyright (c) 2012 Jan Skrasek (http://jan.skrasek.com)
 *
 * @license    MIT
 * @link       https://github.com/nextras
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



	public function enableSort()
	{
		$this->sort = TRUE;
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
