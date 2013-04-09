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
use Nette\Application\UI;
use Nette\Utils\Paginator;



class Datagrid extends UI\Control
{

	/** @var string */
	const ORDER_ASC = 'asc';

	/** @var string */
	const ORDER_DESC = 'desc';

	/** @persistent */
	public $filter = array();

	/** @persistent */
	public $orderColumn;

	/** @persistent */
	public $orderType = self::ORDER_ASC;

	/** @persistent */
	public $page = 1;

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

	/** @var Nette\Utils\Paginator */
	protected $paginator;

	/** @var Nette\Callback */
	protected $paginatorItemsCountCallback;

	/** @var mixed */
	protected $editRowKey;

	/** @var string */
	protected $rowPrimaryKey;

	/** @var mixed */
	protected $data;

	/** @var array */
	protected $cellsTemplates = array();



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
		return $this->columns[] = new Column($name, $label ?: ucfirst($name), $this);
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



	public function setPagination($itemsPerPage, $itemsCountCallback = NULL)
	{
		if ($itemsPerPage === FALSE) {
			$this->paginator = NULL;
			$this->paginatorItemsCountCallback = NULL;
		} else {
			if ($itemsCountCallback === NULL) {
				throw new \InvalidArgumentException('Items count callback must be set.');
			}

			$this->paginator = new Paginator();
			$this->paginator->itemsPerPage = $itemsPerPage;
			$this->paginatorItemsCountCallback = new Nette\Callback($itemsCountCallback);
		}
	}



	public function addCellsTemplate($path)
	{
		$this->cellsTemplates[] = $path;
	}



	public function getCellsTemplate()
	{
		return $this->cellsTemplates;
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
		$this->template->paginator = $this->paginator;

		foreach ($this->cellsTemplates as &$cellsTemplate) {
			if ($cellsTemplate instanceof Nette\Templating\IFileTemplate) {
				$cellsTemplate = $cellsTemplate->getFile();
			}
			if (!file_exists($cellsTemplate)) {
				throw new \RuntimeException("Cells template '{$cellsTemplate}' does not exist.");
			}
		}

		$this->template->cellsTemplates = $this->cellsTemplates;
		$this->template->setFile(__DIR__ . '/Datagrid.latte');
		$this->template->render();
	}



	public function invalidateRow($primaryValue)
	{
		if ($this->presenter->isAjax()) {
			if (isset($this->filterDataSource[$this->rowPrimaryKey]) && is_string($this->filterDataSource[$this->rowPrimaryKey]))
				$this->filterDataSource[$this->rowPrimaryKey] = array($this->filterDataSource[$this->rowPrimaryKey]);

			$this->filterDataSource[$this->rowPrimaryKey][] = $primaryValue;
			parent::invalidateControl('rows');
			$this->invalidateControl('rows-' . $primaryValue);
		}
	}



	public function invalidateControl($snippet = NULL)
	{
		parent::invalidateControl($snippet);
		if ($snippet === NULL || $snippet === 'rows') {
			$this->template->echoSnippets = TRUE;
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
			$onlyRow = $key && $this->presenter->isAjax();
			if (!$onlyRow) {
				$itemsCount = $this->paginatorItemsCountCallback->invokeArgs(array(
					$this->filter,
					$this->orderColumn ? array($this->orderColumn, strtoupper($this->orderType)) : NULL,
				));

				$this->paginator->setItemCount($itemsCount);
				if ($this->paginator->page !== $this->page) {
					$this->paginator->page = $this->page = 1;
				}
			}

			$this->data = $this->dataSourceCallback->invokeArgs(array(
				$this->filterDataSource,
				$this->orderColumn ? array($this->orderColumn, strtoupper($this->orderType)) : NULL,
				$onlyRow ? NULL : $this->paginator,
			));
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
	public function getter($row, $column, $need = TRUE)
	{
		if ($this->columnGetterCallback) {
			return $this->columnGetterCallback->invokeArgs(array($row, $column));
		} else {
			if (!isset($row->$column)) {
				if ($need) {
					throw new \InvalidArgumentException("Result row does not have '{$column}' column.");
				} else {
					return NULL;
				}
			}

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
			$this->invalidateControl('rows');
		} else {
			$this->redirect('this');
		}
	}



	public function createComponentForm()
	{
		$form = new UI\Form;

		if ($this->editFormFactory && ($this->editRowKey || !empty($_POST))) {
			$data = $this->editRowKey ? $this->getData($this->editRowKey) : NULL;
			$form['edit'] = Nette\Callback::create($this->editFormFactory)->invokeArgs(array($data));

			if (!isset($form['edit']['save']))
				$form['edit']->addSubmit('save', 'Save');
			if (!isset($form['edit']['cancel']))
				$form['edit']->addSubmit('cancel', 'Cancel');
			if (!isset($form['edit'][$this->rowPrimaryKey]))
				$form['edit']->addHidden($this->rowPrimaryKey);

			$form['edit'][$this->rowPrimaryKey]
				->setDefaultValue($this->editRowKey)
				->setOption('rendered', TRUE);
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

		$form->onSubmit[] = $this->processForm;
		return $form;
	}



	public function processForm(UI\Form $form)
	{
		if (isset($form['edit'])) {
			if ($form['edit']['save']->isSubmittedBy() && $form->isValid()) {
				Nette\Callback::create($this->editFormCallback)->invokeArgs(array(
					$form['edit']
				));
			}

			$this->invalidateRow($form['edit'][$this->rowPrimaryKey]->getValue());
		}

		if (isset($form['filter'])) {
			if ($form['filter']['filter']->isSubmittedBy()) {
				$values = $form['filter']->getValues(TRUE);
				unset($values['filter']);
				$values = array_filter($values, function($val) {
					return strlen($val) > 0;
				});
				if ($this->paginator) {
					$this->page = $this->paginator->page = 1;
				}
				$this->filter = $this->filterDataSource = $values;
				$this->invalidateControl('rows');
			} elseif ($form['filter']['cancel']->isSubmittedBy()) {
				if ($this->paginator) {
					$this->page = $this->paginator->page = 1;
				}
				$this->filter = $this->filterDataSource = array();
				$form['filter']->setValues(array(), TRUE);
				$this->invalidateControl('rows');
			}
		}

		if (!$this->presenter->isAjax()) {
			$this->redirect('this');
		}
	}



	public function loadState(array $params)
	{
		parent::loadState($params);
		if ($this->paginator) {
			$this->paginator->page = $this->page;
		}
	}



	public function handlePaginate()
	{
		if ($this->presenter->isAjax()) {
			$this->invalidateControl('rows');
		} else {
			$this->redirect('this');
		}
	}

}
