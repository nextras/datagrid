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
use Nette\Application\UI;



class Datagrid extends UI\Control
{

	/** @persistent */
	public $filter = array();

	/** @persistent */
	public $orderColumn;

	/** @persistent */
	public $orderType = 'asc';

	/** @var array */
	protected $filterDataSource = array();

	/** @var array */
	protected $columns = array();

	/** @var Nette\Callback */
	protected $columnGetterCallback;

	/** @var Nette\Callback */
	protected $dataSourceCallback;

	/** @var mixed */
	protected $editFormFactory;

	/** @var mixed */
	protected $editFormCallback;

	/** @var Nette\Callback */
	protected $filterFormFactory;

	/** @var mixed */
	protected $editRowKey;

	/** @var string */
	protected $rowPrimaryKey;

	/** @var mixed */
	protected $data;

	/** @var string */
	protected $cellsTemplate;



	/**
	 * Adds column
	 * @param  string
	 * @param  string
	 */
	public function addColumn($name, $label = NULL)
	{
		if (!$this->rowPrimaryKey) {
			$this->rowPrimaryKey = $name;
		}
		return $this->columns[] = new Column($name, $label ?: ucfirst($name));
	}



	public function setRowPrimaryKey($columnName)
	{
		$this->rowPrimaryKey = (string) $columnName;
	}



	public function getRowPrimaryKey()
	{
		return $this->rowPrimaryKey;
	}



	public function setColumnGetterCallback($getterCallback)
	{
		$this->columnGetterCallback = new Nette\Callback($getterCallback);
	}



	public function getColumnGetterCallback()
	{
		return $this->columnGetterCallback;
	}



	public function setDataSourceCallback($dataSourceCallback)
	{
		$this->dataSourceCallback = new Nette\Callback($dataSourceCallback);
	}



	public function getDataSourceCallback()
	{
		return $this->dataSourceCallback;
	}



	public function setEditFormFactory($editFormFactory)
	{
		$this->editFormFactory = $editFormFactory;
	}



	public function getEditFormFactory()
	{
		return $this->editFormFactory;
	}



	public function setEditFormCallback($editFormCallback)
	{
		$this->editFormCallback = $editFormCallback;
	}



	public function getEditFormCallback()
	{
		return $this->editFormCallback;
	}



	public function setFilterFormFactory($filterFormFactory)
	{
		$this->filterFormFactory = new Nette\Callback($filterFormFactory);
	}



	public function getFilterFormFactory()
	{
		return $this->filterFormFactory;
	}



	public function setCellsTemplate($path)
	{
		$this->cellsTemplate = $path;
	}


	public function getCellsTemplate()
	{
		return $this->cellsTemplate;
	}



	/*******************************************************************************/



	public function render()
	{
		if ($this->filterFormFactory) {
			$this['form']['filter']->setDefaults($this->filter);
		}

		$this->template->data = $this->getData();
		$this->template->columns = $this->columns;
		$this->template->editRowKey = $this->editRowKey;
		$this->template->rowPrimaryKey = $this->rowPrimaryKey;
		$this->template->cellsTemplate = file_exists($this->cellsTemplate) ? $this->cellsTemplate : NULL;
		$this->template->setFile(__DIR__ . '/Datagrid.latte');
		$this->template->render();
	}



	public function invalidateRow($primaryValue)
	{
		if ($this->presenter->isAjax()) {
			if (isset($this->filterDataSource[$this->rowPrimaryKey]) && is_string($this->filterDataSource[$this->rowPrimaryKey]))
				$this->filterDataSource[$this->rowPrimaryKey] = array($this->filterDataSource[$this->rowPrimaryKey]);

			$this->filterDataSource[$this->rowPrimaryKey][] = $primaryValue;
			$this->invalidateControl('rows');
			$this->invalidateControl('rows-' . $primaryValue);
		}
	}



	/*******************************************************************************/



	protected function attached($presenter)
	{
		parent::attached($presenter);
		$this->filterDataSource = $this->filter;
	}



	protected function getData($key = NULL)
	{
		if (!$this->data) {
			$this->data = $this->dataSourceCallback->invokeArgs(array($this->filterDataSource, array($this->orderColumn, strtoupper($this->orderType))));
		}

		if ($key === NULL) {
			return $this->data;
		}

		foreach ($this->data as $row) {
			if ($this->getter($row, $this->rowPrimaryKey) == $key) {
				return $row;
			}
		}

		throw new \Exception('Row not found');
	}



	/**
	 * @internal
	 * @ignore
	 */
	public function getter($row, $column)
	{
		if ($this->columnGetterCallback) {
			return $this->columnGetterCallback->invokeArgs(array($row, $column));
		} else {
			return $row->$column;
		}
	}



	public function handleEdit($primaryValue, $cancelEditPrimaryValue = NULL)
	{
		$this->editRowKey = $primaryValue;
		$this->invalidateRow($primaryValue);
		if ($cancelEditPrimaryValue) {
			foreach (explode(',', $cancelEditPrimaryValue) as $pv) {
				$this->invalidateRow($pv);
			}
		}
	}



	public function handleSort()
	{
		if ($this->presenter->isAjax()) {
			$this->template->echoSnippets = TRUE;
			$this->invalidateControl('rows');
		} else {
			$this->redirect('this');
		}
	}



	public function createComponentForm()
	{
		$form = new UI\Form;

		if ($this->editFormFactory && ($this->editRowKey || !empty($_POST))) {
			$data = $this->editRowKey ? $this->getData($this->editRowKey) : array();
			$form['edit'] = Nette\Callback::create($this->editFormFactory)->invokeArgs(array($data));

			if (!isset($form['edit']['save']))
				$form['edit']->addSubmit('save', 'Save');
			if (!isset($form['edit']['cancel']))
				$form['edit']->addSubmit('cancel', 'Cancel');
			if (!isset($form['edit'][$this->rowPrimaryKey]))
				$form['edit']->addHidden($this->rowPrimaryKey);

			$form['edit'][$this->rowPrimaryKey]->setOption('rendered', TRUE);
		}

		if ($this->filterFormFactory) {
			$form['filter'] = $this->filterFormFactory->invoke();
			if (!isset($form['filter']['filter'])) {
				$form['filter']->addSubmit('filter', 'Filter');
			}
			if (!isset($form['filter']['cancel'])) {
				$form['filter']->addSubmit('cancel', 'Cancel');
			}
		}

		$form->onSuccess[] = $this->processForm;
		return $form;
	}



	public function processForm(UI\Form $form)
	{
		if (isset($form['edit'])) {
			if ($form['edit']['save']->isSubmittedBy()) {
				Nette\Callback::create($this->editFormCallback)->invokeArgs(array(
					$form['edit']
				));
			}

			$this->invalidateRow($form['edit'][$this->rowPrimaryKey]->getValue());
		}

		if (!isset($form['filter'])) {
			return;
		}

		if ($form['filter']['filter']->isSubmittedBy()) {
			$values = $form['filter']->getValues(TRUE);
			unset($values['filter']);
			$values = array_filter($values, function($val) {
				return strlen($val) > 0;
			});
			$this->filter = $this->filterDataSource = $values;
			$this->template->echoSnippets = TRUE;
			$this->invalidateControl('rows');
		} elseif ($form['filter']['cancel']->isSubmittedBy()) {
			$this->filter = $this->filterDataSource = array();
			$form['filter']->setValues(array(), TRUE);
			$this->template->echoSnippets = TRUE;
			$this->invalidateControl('rows');
		}
	}

}

