(window._stack = window._stack || []).push([function (di, DOM) {
	DOM.getByClassName('grid').forEach(function (grid) {
		DOM.addListener(grid, 'click', function (evt) {
			var link = DOM.closest(evt.target, 'a'),
					frm = grid.getElementsByTagName('form').item(0);

			if (link && link.hasAttribute('data-datagrid-edit')) {
				evt.preventDefault();

				var btns = frm.elements.namedItem('edit[cancel]') || [],
						data = {};

				if (btns.tagName) {
					btns = [btns];
				}

				data[DOM.getData(grid, 'grid-name') + '-cancelEditPrimaryValue'] = btns
						.map(function (btn) {
							return DOM.getByClassName('grid-primary-value', DOM.closest(btn, 'tr'))[0].value;
						})
						.join(',');

				di.getService('page').open(link.href, 'get', data);
			}
		});
	});
}, {
	DOM: 'Utils.DOM'
}]);
