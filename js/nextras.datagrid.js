/**
 * This file is part of the Nextras community extensions of Nette Framework
 *
 * Copyright (c) 2012 Jan Skrasek (http://jan.skrasek.com)
 *
 * For the full copyright and license information, please view
 * the file license.txt that was distributed with this source code.
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
				$(this).parents('tr').find(':submit').click();
				e.preventDefault();
			}
		});
		$('.datagrid thead select').off('change.datagrid').on('change.datagrid', function(e) {
			$(this).parents('tr').find(':submit').click();
		});
	},
	before: function(settings) {
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

		grid.find('.editLink').each(function() {
			var href = $(this).data('grid-href');
			if (!href) {
				$(this).data('grid-href', href = $(this).attr('href'));
			}

			$(this).attr('href', href + '&' + paramName + '-cancelEditPrimaryValue=' + idToClose.join(','));
		});
	}
});
