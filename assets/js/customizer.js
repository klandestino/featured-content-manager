(function( $ ) {
	wp.customize.bind( 'ready', function() {
		const customize = this;
		const template = wp.template('featured-item');
		let timer, timer_ms = 500;

		// For each Featured Area Control add features items and initiate nestedSortable
		customize.control( 'featured_area' ).container.find( 'ol.featured-area' ).each( (index, area) => {
			$.ajax( {
				url: wpApiSettings.root + 'items',
				data: { post_status : 'draft' },
				success: function (data) {
					$(data).each( (index, item) => {
						addItemToArea(item, area);
					});
					$(area).nestedSortable({
						handle: '.handle',
						items: 'li',
						toleranceElement: '> div',
						maxLevels: 2,
						excludeRoot: true,
						forcePlaceholderSize: true,
						placeholder: 'placeholder',
						stop: function() { 
							updateArea(area);
						}
					});
				},
				cache: false
			});
		});

		// Add featured item to list
		function addItemToArea(item, area) {
			$(area).append( template(item) );
		}

		// Update the wholre featured area
		function updateArea(area) {
			let area_array = $(area).nestedSortable('toArray');
			$(area_array).each(function(index, item){
				updateItem(item, index, area);
			});
		}

		// Update a single features item
		function updateItem(item, index, area){
			const data = {
				menu_order: index,
				post_parent: item.parent_id,
			};			
			const extra_data = collectData(item, area);

			return $.ajax({
				method: "POST",
				url: wpApiSettings.root + 'items/' + item.id,
				data: Object.assign(data, extra_data),
				beforeSend: function ( xhr ) {
					xhr.setRequestHeader( 'X-WP-Nonce', wpApiSettings.nonce );
				}
			});
			
		}

		// Collect post data from input fields
		function collectData(item, area){
			const id = '#featured_item_'+item.id;
			let post_data = {};
			$(id).find('.featured-item-settings').first().find('.featured-item-edit-input').each( ( index, element ) => {
				let obj = {};
				obj[element.attributes.name.textContent] = element.value;
				post_data = Object.assign(post_data, obj);
			});
			return post_data;
		}

		// Action trigged when extending featured item
		$(document).on('click', '.featured-item-edit', function(event){
			const item = $(this).parent().parent();
			if( $(item).hasClass('open') ) {
				$(item).removeClass('open');
			} else {
				$('ol.featured-area li').removeClass('open');
				$(item).addClass('open');
			}
		});

		// Action trigged when uppdating input fields
		$(document).on('input', '.featured-item-edit-input', function(event){
			clearTimeout(timer);
			timer = setTimeout(function() {
				const list_object = $(event.currentTarget).closest('li');
				const index = $(list_object).index('ol.featured-area li');
				const area = $(event.currentTarget).closest('ol.featured-area');
				const area_array = $(area).nestedSortable('toArray');
				const item = area_array[index];
				updateItem(item, index, area);
			}, timer_ms);
		});
	});
})( jQuery );