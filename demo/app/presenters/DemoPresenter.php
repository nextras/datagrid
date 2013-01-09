<?php

final class DemoPresenter extends Nette\Application\UI\Presenter
{

	private $connection;

	public function renderDefault()
	{
	}

	public function renderLight()
	{
	}

	public function renderFull()
	{
	}

	public function renderPagination()
	{
	}

	// ========================================================================

	public function createComponentLightDatagrid()
	{
		$grid = new Nextras\Datagrid\Datagrid;
		$grid->addColumn('id');
		$grid->addColumn('firstname')->enableSort();
		$grid->addColumn('surname')->enableSort();
		$grid->addColumn('gender')->enableSort();
		$grid->addColumn('birthday')->enableSort();
		$grid->addColumn('virtual-gender', "Virtual gender");

		$grid->setDataSourceCallback($this->getDataLight);

		$grid->setFilterFormFactory(function() {
			$form = new Nette\Forms\Container;
			$form->addText('firstname');
			$form->addText('surname');
			$form->addSelect('gender', NULL, array('male' => 'male', 'female' => 'female'))->setPrompt('---');
			return $form;
		});

		$grid->setEditFormFactory(function($row) {
			$form = new Nette\Forms\Container;
			$form->addText('firstname');
			$form->addText('surname');
			if ($row) {
				$form->setDefaults($row);
			}
			return $form;
		});

		$grid->setEditFormCallback($this->saveData);

		$grid->addCellsTemplate(APP_DIR . '/../../bootstrap-style/@datagrid.latte');
		$grid->addCellsTemplate(__DIR__ . '/../templates/Demo/@light_cells.latte');
		return $grid;
	}

	public function getDataLight($filter, $order)
	{
		$filters = array();
		foreach ($filter as $k => $v) {
			if ($k === 'gender')
				$filters[$k] = $v;
			else
				$filters[$k. ' LIKE ?'] = "%$v%";
		}

		$selection = $this->connection->table('user')->where($filters);
		if ($order) {
			$selection->order(implode(' ', $order));
		}

		return $selection->limit(30);
	}

	public function saveData($data)
	{
		$this->flashMessage('Saving data: ' . json_encode($data->getValues()));
	}

	// ========================================================================

	public function injectConnection(Nette\Database\Connection $connection)
	{
		$this->connection = $connection;
	}

	public function beforeRender()
	{
		parent::beforeRender();
		$this->invalidateControl('flashes');
	}

}
