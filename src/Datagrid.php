<?php

/**
 * This file is part of the Nextras community extensions of Nette Framework
 *
 * @license    MIT
 * @link       https://github.com/nextras
 */

namespace Nextras\Datagrid;

use Nette\Application\UI;
use Nette\Bridges\ApplicationLatte\Template;
use Nette\Forms\Container;
use Nette\Forms\Controls\Button;
use Nette\Forms\Controls\Checkbox;
use Nette\Utils\Html;
use Nette\Utils\Paginator;
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

	/** @var callable|null */
	protected $columnGetterCallback;

	/** @var callable */
	protected $dataSourceCallback;

	/** @var callable|null */
	protected $editFormFactory;

	/** @var callable|null */
	protected $editFormCallback;

	/** @var callable|null */
	protected $filterFormFactory;

	/** @var array */
	protected $filterDefaults;

	/** @var array */
	protected $globalActions = [];

	/** @var Paginator */
	protected $paginator;

	/** @var ITranslator */
	protected $translator;

	/** @var callable|null */
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
		return $this->columns[$name] = new Column($name, $label, $this);
	}


	/**
	 * @param  string $name
	 * @return Column
	 */
	public function getColumn($name)
	{
		if (!isset($this->columns[$name])) {
			throw new \InvalidArgumentException("Unknown column $name.");
		}
		return $this->columns[$name];
	}


	public function setRowPrimaryKey($columnName)
	{
		$this->rowPrimaryKey = (string) $columnName;
	}


	public function getRowPrimaryKey()
	{
		return $this->rowPrimaryKey;
	}


	public function setColumnGetterCallback(callable $getterCallback = null)
	{
		$this->columnGetterCallback = $getterCallback;
	}


	public function getColumnGetterCallback()
	{
		return $this->columnGetterCallback;
	}


	public function setDataSourceCallback(callable $dataSourceCallback)
	{
		$this->dataSourceCallback = $dataSourceCallback;
	}


	public function getDataSourceCallback()
	{
		return $this->dataSourceCallback;
	}


	public function setEditFormFactory(callable $editFormFactory = null)
	{
		$this->editFormFactory = $editFormFactory;
	}


	public function getEditFormFactory()
	{
		return $this->editFormFactory;
	}


	public function setEditFormCallback(callable $editFormCallback = null)
	{
		$this->editFormCallback = $editFormCallback;
	}


	public function getEditFormCallback()
	{
		return $this->editFormCallback;
	}


	public function setFilterFormFactory(callable $filterFormFactory = null)
	{
		$this->filterFormFactory = $filterFormFactory;
	}


	public function getFilterFormFactory()
	{
		return $this->filterFormFactory;
	}


	public function addGlobalAction($name, $label, callable $action)
	{
		$this->globalActions[$name] = [$label, $action];
	}


	public function setPagination($itemsPerPage, callable $itemsCountCallback = null)
	{
		if ($itemsPerPage === false) {
			$this->paginator = null;
			$this->paginatorItemsCountCallback = null;
		} else {
			if ($itemsCountCallback === null) {
				throw new \InvalidArgumentException('Items count callback must be set.');
			}

			$this->paginator = new Paginator();
			$this->paginator->itemsPerPage = $itemsPerPage;
			$this->paginatorItemsCountCallback = $itemsCountCallback;
		}
	}


	/**
	 * @param string|Template $path
	 */
	public function addCellsTemplate($path)
	{
		if ($path instanceof Template) {
			$path = $path->getFile();
		}
		if (!file_exists($path)) {
			throw new \InvalidArgumentException("Template '{$path}' does not exist.");
		}
		$this->cellsTemplates[] = $path;
	}


	public function getCellsTemplates()
	{
		$templates = $this->cellsTemplates;
		$templates[] = __DIR__ . '/Datagrid.blocks.latte';
		return $templates;
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
		$this->template->sendOnlyRowParentSnippet = $this->sendOnlyRowParentSnippet;
		$this->template->cellsTemplates = $this->getCellsTemplates();
		$this->template->showFilterCancel = $this->filterDataSource != $this->filterDefaults; // @ intentionaly
		$this->template->setFile(__DIR__ . '/Datagrid.latte');

		$this->onRender($this);
		$this->template->render();
	}


	public function redrawRow($primaryValue)
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
			$this->redrawControl('rows-' . $primaryValue);
		}
	}


	public function redrawControl($snippet = null)
	{
		parent::redrawControl($snippet);
		if ($snippet === null || $snippet === 'rows') {
			$this->sendOnlyRowParentSnippet = true;
		}
	}


	/** @deprecated */
	function invalidateRow($primaryValue)
	{
		trigger_error(__METHOD__ . '() is deprecated; use $this->redrawRow($primaryValue) instead.', E_USER_DEPRECATED);
		$this->redrawRow($primaryValue);
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
				$itemsCount = call_user_func(
					$this->paginatorItemsCountCallback,
					$this->filterDataSource,
					$this->orderColumn ? [$this->orderColumn, strtoupper($this->orderType)] : null
				);

				$this->paginator->setItemCount($itemsCount);
				if ($this->paginator->page !== $this->page) {
					$this->paginator->page = $this->page = 1;
				}
			}

			$this->data = call_user_func(
				$this->dataSourceCallback,
				$this->filterDataSource,
				$this->orderColumn ? [$this->orderColumn, strtoupper($this->orderType)] : null,
				$onlyRow ? null : $this->paginator
			);
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
			return call_user_func($this->columnGetterCallback, $row, $column, $need);
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
		if ($this->presenter->isAjax()) {
			$this->redrawRow($primaryValue);
			if ($cancelEditPrimaryValue) {
				foreach (explode(',', $cancelEditPrimaryValue) as $pv) {
					$this->redrawRow($pv);
				}
			}
		}
	}


	public function handleSort()
	{
		if ($this->presenter->isAjax()) {
			$this->redrawControl('rows');
		}
	}


	public function createComponentForm()
	{
		$form = new UI\Form;

		if ($this->filterFormFactory) {
			$form['filter'] = call_user_func($this->filterFormFactory);
			if (!isset($form['filter']['filter'])) {
				$form['filter']->addSubmit('filter', $this->translate('Filter'));
			}
			if (!isset($form['filter']['cancel'])) {
				$form['filter']->addSubmit('cancel', $this->translate('Cancel'));
			}

			$this->prepareFilterDefaults($form['filter']);
			if (!$this->filterDataSource) {
				$this->filterDataSource = $this->filterDefaults;
			}
		}

		if ($this->editFormFactory && ($this->editRowKey !== null || !empty($_POST['edit']))) {
			$data = $this->editRowKey !== null && empty($_POST) ? $this->getData($this->editRowKey) : null;
			$form['edit'] = call_user_func($this->editFormFactory, $data);

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

		if ($this->globalActions) {
			$actions = array_map(function($row) { return $row[0]; }, $this->globalActions);
			$form['actions'] = new Container();
			$form['actions']->addSelect('action', 'Action', $actions)
				->setPrompt('- select action -');

			$rows = [];
			foreach ($this->getData() as $row) {
				$rows[$this->getter($row, $this->rowPrimaryKey)] = null;
			}
			$form['actions']->addCheckboxList('items', '', $rows);
			$form['actions']->addSubmit('process', 'Do');
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
					call_user_func($this->editFormCallback, $form['edit']);
				} else {
					$this->editRowKey = $form['edit'][$this->rowPrimaryKey]->getValue();
					$allowRedirect = false;
				}
			}
			if ($form['edit']['cancel']->isSubmittedBy() || ($form['edit']['save']->isSubmittedBy() && $form['edit']->isValid())) {
				$editRowKey = $form['edit'][$this->rowPrimaryKey]->getValue();
				$this->redrawRow($editRowKey);
				$this->getData($editRowKey);
			}
			if ($this->editRowKey !== null) {
				$this->redrawRow($this->editRowKey);
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
				$this->redrawControl('rows');
			} elseif ($form['filter']['cancel']->isSubmittedBy()) {
				if ($this->paginator) {
					$this->page = $this->paginator->page = 1;
				}
				$this->filter = $this->filterDataSource = $this->filterDefaults;
				$form['filter']->setValues($this->filter, true);
				$this->redrawControl('rows');
			}
		}

		if (isset($form['actions'])) {
			if ($form['actions']['process']->isSubmittedBy()) {
				$action = $form['actions']['action']->getValue();
				if ($action) {
					$ids = $form['actions']['items']->getValue();
					$callback = $this->globalActions[$action][1];
					$callback($ids, $this);
					$this->data = null;
					$form['actions']->setValues(['action' => null, 'items' => []]);
				}
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
			$this->redrawControl('rows');
		}
	}


	private function prepareFilterDefaults(Container $container)
	{
		$this->filterDefaults = [];
		foreach ($container->controls as $name => $control) {
			if ($control instanceof Button) {
				continue;
			}

			$value = $control->getValue();
			$isNonEmptyValue =
				(is_array($value) && !empty($value))
				|| (is_string($value) && strlen($value) > 0)
				|| (!is_array($value) && !is_string($value) && $value !== null);
			if ($isNonEmptyValue) {
				$this->filterDefaults[$name] = $value;
			}
		}
	}


	private function filterFormFilter(array $values)
	{
		$filtered = [];
		foreach ($values as $key => $val) {
			$default = isset($this->filterDefaults[$key]) ? $this->filterDefaults[$key] : null;
			if ($default !== $val) {
				$filtered[$key] = $val;
			}
		}
		return $filtered;
	}
}
