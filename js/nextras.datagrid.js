/**
 * This file is part of the Nextras community extensions of Nette Framework
 *
 * Copyright (c) 2012 Jan Skrasek (http://jan.skrasek.com)
 *
 * @license    MIT
 * @link       https://github.com/nextras
 */

$.nette.ext('datagrid', {
	init: function() {
		var datagrid = this;
		this.grids = $('.datagrid').each(function() {
			datagrid.load($(this));
		});
	},
	load: function() {
		$('.datagrid input').off('keypress.datagrid').on('keypress.datagrid', function(e) {
			if (e.which == 13) {
				$(this).parents('tr').find('input[name=filter\\[filter\\]]').click();
				e.preventDefault();
			}
		});
		$('.datagrid thead select').off('change.datagrid').on('change.datagrid', function(e) {
			$(this).parents('tr').find('input[name=filter\\[filter\\]]').click();
		});
		$('.datagrid tbody td:not(.col-actions)').click(function(e) {
			if (e.ctrlKey) {
				$(this).parents('tr').find('a[data-datagrid-edit]').click();
				e.preventDefault();
				return false;
			}
		});
	},
	before: function(xhr, settings) {
		this.grid = settings.nette.el.parents('.datagrid');
	},
	success: function() {
		this.load(this.grid);
	}
}, {
	activeGrid: null,
	load: function(grid) {
		var idToClose = [];
		var paramName = grid.attr('data-grid-name');
		grid.find('tr:has(input[name=edit\\[cancel\\]])').each(function() {
			idToClose.push($(this).attr('data-grid-primary'));
		});

		if (idToClose.length == 0) {
			return;
		}

		grid.find('a[data-datagrid-edit]').each(function() {
			var href = $(this).data('grid-href');
			if (!href) {
				$(this).data('grid-href', href = $(this).attr('href'));
			}

			$(this).attr('href', href + '&' + paramName + '-cancelEditPrimaryValue=' + idToClose.join(','));
		});
	}
});
