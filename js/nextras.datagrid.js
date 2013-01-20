/**
 * This file is part of the Nextras community extensions of Nette Framework
 *
 * @license    MIT
 * @link       https://github.com/nextras
 * @author     Jan Skrasek
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
			e.preventDefault();
		});
		$('.datagrid tbody td:not(.col-actions)').off('click.datagrid').on('click.datagrid', function(e) {
			if (e.ctrlKey) {
				$(this).parents('tr').find('a[data-datagrid-edit]').click();
				e.preventDefault();
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
		grid.find('tr:has(input[name=edit\\[cancel\\]])').each(function(i, el) {
			$(el).find('input').get(0).focus();
			idToClose.push($(el).attr('data-grid-primary'));
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
