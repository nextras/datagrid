<?php

/**
 * This file is part of the Nextras community extensions of Nette Framework
 *
 * @license    MIT
 * @link       https://github.com/nextras
 */

namespace Nextras\Datagrid;

use Nette\Localization\ITranslator;
use Nette\SmartObject;


class DefaultTranslator implements ITranslator
{
	use SmartObject;


	const TRANSLATIONS = [
		'nextras.datagrid.filter.submit' => 'Filter',
		'nextras.datagrid.filter.cancel' => 'Cancel',

		'nextras.datagrid.edit.label' => 'Edit',
		'nextras.datagrid.edit.save' => 'Save',
		'nextras.datagrid.edit.cancel' => 'Cancel',

		'nextras.datagrid.action.label' => 'Action',
		'nextras.datagrid.action.prompt' => '- select action -',
		'nextras.datagrid.action.process' => 'Do',

		'nextras.datagrid.pagination.first' => 'First',
		'nextras.datagrid.pagination.previous' => 'Previous',
		'nextras.datagrid.pagination.next' => 'Next',
		'nextras.datagrid.pagination.last' => 'Last',
	];


	public function translate($message, $count = NULL)
	{
		return isset(self::TRANSLATIONS[$message]) ? self::TRANSLATIONS[$message] : $message;
	}

}
