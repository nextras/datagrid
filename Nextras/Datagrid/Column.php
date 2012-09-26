<?php

/**
 * This file is part of the Nextras community extensions of Nette Framework
 *
 * Copyright (c) 2012 Jan Skrasek (http://jan.skrasek.com)
 *
 * For the full copyright and license information, please view
 * the file license.txt that was distributed with this source code.
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


	public function __construct($name, $label)
	{
		$this->name = $name;
		$this->label = $label;
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

}
