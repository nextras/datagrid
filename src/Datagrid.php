<?php

/**
 * This file is part of the Nextras community extensions of Nette Framework
 *
 * @license    MIT
 * @link       https://github.com/nextras
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

	/** @var array of callbacks: function(Datagrid) */
	public $onRender = [];

	/** @persistent */
	public $filter = [];

	/** @persistent */
	public $orderColumn;

	/** @persistent */
	public $orderType = self::ORDER_ASC;

	/** @persistent */
	public $page = 1;

	/** @var array */
	protected $filterDataSource = [];

	/** @var array */
	protected $columns = [];

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

	/** @var bool */
	protected $sendOnlyRowParentSnippet = false;

	/** @var array */
	protected $cellsTemplates = [];


	/**
	 * Adds column
	 * @param  string
	 * @param  string
	 * @return Column
	 */
	public function addColumn($name, $label = null)
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


	public function setPagination($itemsPerPage, $itemsCountCallback = null)
	{
		if ($itemsPerPage === false) {
			$this->paginator = null;
			$this->paginatorItemsCountCallback = null;
		} else {
			if ($itemsCountCallback === null) {
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


	public function translate($s, $count = null)
	{
		$translator = $this->getTranslator();
		return $translator === null || $s == null || $s instanceof Html // intentionally ==
			? $s
			: $translator->translate((string) $s, $count);
	}


	/*******************************************************************************/


	public function render()
	{
		if ($this->filterFormFactory) {
			$this['form']['filter']->setDefaults($this->filter);
		}

		$this->template->form = $this['form'];
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

		$this->template->sendOnlyRowParentSnippet = $this->sendOnlyRowParentSnippet;
		$this->template->cellsTemplates = $this->cellsTemplates;
		$this->template->showFilterCancel = $this->filterDataSource != $this->filterDefaults; // @ intentionaly
		$this->template->setFile(__DIR__ . '/Datagrid.latte');

		$this->onRender($this);
		$this->template->render();
	}


	public function invalidateRow($primaryValue)
	{
		if ($this->presenter->isAjax()) {
			if (isset($this->filterDataSource[$this->rowPrimaryKey])) {
				$this->filterDataSource = [$this->rowPrimaryKey => $this->filterDataSource[$this->rowPrimaryKey]];
				if (is_string($this->filterDataSource[$this->rowPrimaryKey])) {
					$this->filterDataSource[$this->rowPrimaryKey] = [$this->filterDataSource[$this->rowPrimaryKey]];
				}
			} else {
				$this->filterDataSource = [];
			}

			$this->filterDataSource[$this->rowPrimaryKey][] = $primaryValue;
			parent::redrawControl('rows');
			$this->invalidateControl('rows-' . $primaryValue);
		}
	}


	public function invalidateControl($snippet = null)
	{
		parent::redrawControl($snippet);
		if ($snippet === null || $snippet === 'rows') {
			$this->sendOnlyRowParentSnippet = true;
		}
	}


	/*******************************************************************************/


	protected function attached($presenter)
	{
		parent::attached($presenter);
		$this->filterDataSource = $this->filter;
	}


	protected function getData($key = null)
	{
		if (!$this->data) {
			$onlyRow = $key !== null && $this->presenter->isAjax();
			if (!$onlyRow && $this->paginator) {
				$itemsCount = Callback::invokeArgs($this->paginatorItemsCountCallback, [
					$this->filterDataSource,
					$this->orderColumn ? [$this->orderColumn, strtoupper($this->orderType)] : null,
				]);

				$this->paginator->setItemCount($itemsCount);
				if ($this->paginator->page !== $this->page) {
					$this->paginator->page = $this->page = 1;
				}
			}

			$this->data = Callback::invokeArgs($this->dataSourceCallback, [
				$this->filterDataSource,
				$this->orderColumn ? [$this->orderColumn, strtoupper($this->orderType)] : null,
				$onlyRow ? null : $this->paginator,
			]);
		}

		if ($key === null) {
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
	public function getter($row, $column, $need = true)
	{
		if ($this->columnGetterCallback) {
			return Callback::invokeArgs($this->columnGetterCallback, [$row, $column]);
		} else {
			if (!isset($row->$column)) {
				if ($need) {
					throw new \InvalidArgumentException("Result row does not have '{$column}' column.");
				} else {
					return null;
				}
			}

			return $row->$column;
		}
	}


	public function handleEdit($primaryValue, $cancelEditPrimaryValue = null)
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

			$this->filterDefaults = [];
			foreach ($form['filter']->controls as $name => $control) {
				$this->filterDefaults[$name] = $control->getValue();
			}
			$this->filterDefaults = $this->filterFormFilter($this->filterDefaults);

			if (!$this->filterDataSource) {
				$this->filterDataSource = $this->filterDefaults;
			}
		}

		if ($this->editFormFactory && ($this->editRowKey !== null || !empty($_POST['edit']))) {
			$data = $this->editRowKey !== null && empty($_POST) ? $this->getData($this->editRowKey) : null;
			$form['edit'] = Callback::invokeArgs($this->editFormFactory, [$data]);

			if (!isset($form['edit']['save']))
				$form['edit']->addSubmit('save', 'Save');
			if (!isset($form['edit']['cancel']))
				$form['edit']->addSubmit('cancel', 'Cancel');
			if (!isset($form['edit'][$this->rowPrimaryKey]))
				$form['edit']->addHidden($this->rowPrimaryKey);

			$form['edit'][$this->rowPrimaryKey]
				->setDefaultValue($this->editRowKey)
				->setOption('rendered', true);
		}

		if ($this->translator) {
			$form->setTranslator($this->translator);
		}

		$form->onSuccess[] = function() {}; // fix for Nette Framework 2.0.x
		$form->onSubmit[] = [$this, 'processForm'];
		return $form;
	}


	public function processForm(UI\Form $form)
	{
		$allowRedirect = true;
		if (isset($form['edit'])) {
			if ($form['edit']['save']->isSubmittedBy()) {
				if ($form['edit']->isValid()) {
					Callback::invokeArgs($this->editFormCallback, [
						$form['edit']
					]);
				} else {
					$this->editRowKey = $form['edit'][$this->rowPrimaryKey]->getValue();
					$allowRedirect = false;
				}
			}
			if ($form['edit']['cancel']->isSubmittedBy() || ($form['edit']['save']->isSubmittedBy() && $form['edit']->isValid())) {
				$editRowKey = $form['edit'][$this->rowPrimaryKey]->getValue();
				$this->invalidateRow($editRowKey);
				$this->getData($editRowKey);
			}
			if ($this->editRowKey !== null) {
				$this->invalidateRow($this->editRowKey);
			}
		}

		if (isset($form['filter'])) {
			if ($form['filter']['filter']->isSubmittedBy()) {
				$values = $form['filter']->getValues(true);
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
				$form['filter']->setValues($this->filter, true);
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


	protected function createTemplate($class = null)
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
