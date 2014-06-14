<?php

/**
 * This file is part of the Nextras community extensions of Nette Framework
 *
 * @license    MIT
 * @link       https://github.com/nextras
 * @author     Jan Skrasek
 */

namespace Nextras\Datagrid;

use Nette\Application\UI;
use Nette\Templating\IFileTemplate;
use Nette\Utils\Html;
use Nette\Utils\Paginator;
use Nette\Utils\Callback;
use Nette\Localization\ITranslator;



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

	/** @var callback */
	protected $columnGetterCallback;

	/** @var callback */
	protected $dataSourceCallback;

	/** @var mixed */
	protected $editFormFactory;

	/** @var mixed */
	protected $editFormCallback;

	/** @var callback */
	protected $filterFormFactory;

	/** @var array */
	protected $filterDefaults;

	/** @var Paginator */
	protected $paginator;

	/** @var ITranslator */
	protected $translator;

	/** @var callback */
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
	 * @return Column
	 */
	public function addColumn($name, $label = NULL)
	{
		if (!$this->rowPrimaryKey) {
			$this->rowPrimaryKey = $name;
		}

		$label = $label ? $this->translate($label) : ucfirst($name);
		return $this->columns[] = new Column($name, $label, $this);
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
		Callback::check($getterCallback);
		$this->columnGetterCallback = $getterCallback;
	}



	public function getColumnGetterCallback()
	{
		return $this->columnGetterCallback;
	}



	public function setDataSourceCallback($dataSourceCallback)
	{
		Callback::check($dataSourceCallback);
		$this->dataSourceCallback = $dataSourceCallback;
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
		Callback::check($editFormCallback);
		$this->editFormCallback = $editFormCallback;
	}



	public function getEditFormCallback()
	{
		return $this->editFormCallback;
	}



	public function setFilterFormFactory($filterFormFactory)
	{
		Callback::check($filterFormFactory);
		$this->filterFormFactory = $filterFormFactory;
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

			Callback::check($itemsCountCallback);
			$this->paginator = new Paginator();
			$this->paginator->itemsPerPage = $itemsPerPage;
			$this->paginatorItemsCountCallback = $itemsCountCallback;
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



	public function setTranslator(ITranslator $translator)
	{
		$this->translator = $translator;
	}



	public function getTranslator()
	{
		return $this->translator;
	}



	public function translate($s, $count = NULL)
	{
		$translator = $this->getTranslator();
		return $translator === NULL || $s == NULL || $s instanceof Html // intentionally ==
			? $s
			: $translator->translate((string) $s, $count);
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
			if ($cellsTemplate instanceof IFileTemplate) {
				$cellsTemplate = $cellsTemplate->getFile();
			}
			if (!file_exists($cellsTemplate)) {
				throw new \RuntimeException("Cells template '{$cellsTemplate}' does not exist.");
			}
		}

		$this->template->cellsTemplates = $this->cellsTemplates;
		$this->template->showFilterCancel = $this->filterDataSource != $this->filterDefaults; // @ intentionaly
		$this->template->setFile(__DIR__ . '/Datagrid.latte');
		$this->template->render();
	}



	public function invalidateRow($primaryValue)
	{
		if ($this->presenter->isAjax()) {
			if (isset($this->filterDataSource[$this->rowPrimaryKey])) {
				$this->filterDataSource = array($this->rowPrimaryKey => $this->filterDataSource[$this->rowPrimaryKey]);
				if (is_string($this->filterDataSource[$this->rowPrimaryKey])) {
					$this->filterDataSource[$this->rowPrimaryKey] = array($this->filterDataSource[$this->rowPrimaryKey]);
				}
			} else {
				$this->filterDataSource = array();
			}

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
			$onlyRow = $key !== NULL && $this->presenter->isAjax();
			if (!$onlyRow && $this->paginator) {
				$itemsCount = Callback::invokeArgs($this->paginatorItemsCountCallback, array(
					$this->filterDataSource,
					$this->orderColumn ? array($this->orderColumn, strtoupper($this->orderType)) : NULL,
				));

				$this->paginator->setItemCount($itemsCount);
				if ($this->paginator->page !== $this->page) {
					$this->paginator->page = $this->page = 1;
				}
			}

			$this->data = Callback::invokeArgs($this->dataSourceCallback, array(
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
			return Callback::invokeArgs($this->columnGetterCallback, array($row, $column));
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

		if ($this->filterFormFactory) {
			$form['filter'] = Callback::invoke($this->filterFormFactory);
			if (!isset($form['filter']['filter'])) {
				$form['filter']->addSubmit('filter', $this->translate('Filter'));
			}
			if (!isset($form['filter']['cancel'])) {
				$form['filter']->addSubmit('cancel', $this->translate('Cancel'));
			}

			$this->filterDefaults = array();
			foreach ($form['filter']->controls as $name => $control) {
				$this->filterDefaults[$name] = $control->getValue();
			}
			$this->filterDefaults = $this->filterFormFilter($this->filterDefaults);

			if (!$this->filterDataSource) {
				$this->filterDataSource = $this->filterDefaults;
			}
		}

		if ($this->editFormFactory && ($this->editRowKey !== NULL || !empty($_POST['edit']))) {
			$data = $this->editRowKey !== NULL && empty($_POST) ? $this->getData($this->editRowKey) : NULL;
			$form['edit'] = Callback::invokeArgs($this->editFormFactory, array($data));

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

		if ($this->translator) {
			$form->setTranslator($this->translator);
		}

		$form->onSuccess[] = function() {}; // fix for Nette Framework 2.0.x
		$form->onSubmit[] = $this->processForm;
		return $form;
	}



	public function processForm(UI\Form $form)
	{
		$allowRedirect = TRUE;
		if (isset($form['edit'])) {
			if ($form['edit']['save']->isSubmittedBy()) {
				if ($form['edit']->isValid()) {
					Callback::invokeArgs($this->editFormCallback, array(
						$form['edit']
					));
				} else {
					$this->editRowKey = $form['edit'][$this->rowPrimaryKey]->getValue();
					$allowRedirect = FALSE;
				}
			}
			if ($form['edit']['cancel']->isSubmittedBy() || ($form['edit']['save']->isSubmittedBy() && $form['edit']->isValid())) {
				$editRowKey = $form['edit'][$this->rowPrimaryKey]->getValue();
				$this->invalidateRow($editRowKey);
				$this->getData($editRowKey);
			}
			if ($this->editRowKey !== NULL) {
				$this->invalidateRow($this->editRowKey);
			}
		}

		if (isset($form['filter'])) {
			if ($form['filter']['filter']->isSubmittedBy()) {
				$values = $form['filter']->getValues(TRUE);
				unset($values['filter']);
				$values = $this->filterFormFilter($values);
				if ($this->paginator) {
					$this->page = $this->paginator->page = 1;
				}
				$this->filter = $this->filterDataSource = $values;
				$this->invalidateControl('rows');
			} elseif ($form['filter']['cancel']->isSubmittedBy()) {
				if ($this->paginator) {
					$this->page = $this->paginator->page = 1;
				}
				$this->filter = $this->filterDataSource = $this->filterDefaults;
				$form['filter']->setValues($this->filter, TRUE);
				$this->invalidateControl('rows');
			}
		}

		if (!$this->presenter->isAjax() && $allowRedirect) {
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



	protected function createTemplate($class = NULL)
	{
		$template = parent::createTemplate($class);
		if ($translator = $this->getTranslator()) {
			$template->setTranslator($translator);
		}
		return $template;
	}



	public function handlePaginate()
	{
		if ($this->presenter->isAjax()) {
			$this->invalidateControl('rows');
		} else {
			$this->redirect('this');
		}
	}


	private function filterFormFilter($values)
	{
		return array_filter($values, function($val) {
			if (is_array($val)) {
				return !empty($val);
			}
			if (is_string($val)) {
				return strlen($val) > 0;
			}
			return $val !== null;
		});
	}

}
