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


	const LANG_EN = 'en';
	const LANG_CS = 'cs';

	const TRANSLATIONS = [
		self::LANG_EN => [
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
		],
		self::LANG_CS => [
			'nextras.datagrid.filter.submit' => 'Filtrovat',
			'nextras.datagrid.filter.cancel' => 'Zrušit',

			'nextras.datagrid.edit.label' => 'Upravit',
			'nextras.datagrid.edit.save' => 'Uložit',
			'nextras.datagrid.edit.cancel' => 'Zrušit',

			'nextras.datagrid.action.label' => 'Akce',
			'nextras.datagrid.action.prompt' => '- zvolte akci -',
			'nextras.datagrid.action.process' => 'OK',

			'nextras.datagrid.pagination.first' => 'První',
			'nextras.datagrid.pagination.previous' => 'Poslední',
			'nextras.datagrid.pagination.next' => 'Další',
			'nextras.datagrid.pagination.last' => 'Předchozí',
		],
	];


	/** @var string */
	private $language;


	public function __construct($language)
	{
		if (!isset(self::TRANSLATIONS[$language])) {
			throw new InvalidArgumentException("Unsupported language '$language'");
		}
		$this->language = $language;
	}


	public function translate($message, $count = NULL)
	{
		return isset(self::TRANSLATIONS[$this->language][$message]) ? self::TRANSLATIONS[$this->language][$message] : $message;
	}

}
