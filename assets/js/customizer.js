(function( $ ) {
	wp.customize.bind( 'ready', function() {
		const customize = this;
		const template = wp.template('featured-item');
		const searchtemplate = wp.template('search-item');
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
			$(area).append(template(item));
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
				method: 'POST',
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
		$(document).on('click', '.handle', function(event){
			const item = $(this).parent();
			if( $(item).hasClass('open') ) {
				$(item).removeClass('open');
			} else {
				$('ol.featured-area li').removeClass('open');
				$(item).addClass('open');
			}
		});

		// Action trigged when clicking add featured item
		$(document).on('click', '.add-featured-item', function(event){
			event.preventDefault();
			const target = $(event.currentTarget).parent().children('ol.featured-area');

			$('.target').removeClass('target');
			$(target).addClass('target');
			$('body').toggleClass('adding-featured-items');
		});

		// Action trigged when clicking outside avalible featured items
		$(document).on('click', 'body.wp-customizer', function(event){
			if( !$(event.target).hasClass('add-featured-item') && $(event.target).closest('div#available-featured-items').length == 0 ){
				$('.target').removeClass('target');
				$('body').removeClass('adding-featured-items');
			}
		});
		
		// Action trigged when typing in search field
		$(document).on('input', '#featured-items-search', function(event){
			const searchterm = $('#featured-items-search').val();
			clearTimeout(timer);
			timer = setTimeout(function() {
				$('#available-featured-items .spinner').css('visibility', 'visible');
				$.ajax( {
					url: '/wp-json/wp/v2/posts',
					data: { search : searchterm },
					success: function (data) {
						$('#available-featured-items .spinner').css('visibility', 'hidden');
						$('.search-item-tpl').remove();
						$(data).each( (index, item) => {
							$('.available-featured-items-list').append(searchtemplate(item));
						});
					},
					cache: false
				} );
			}, timer_ms);
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

		// Action trigged when clicking on a search item
		$(document).on('click', '.button-link-delete', function(event){
			const list_object = $(event.currentTarget).closest('li');
			const post_id = $(list_object).data('featured-item-id');
			const spinner = $(event.currentTarget).parent().children('.spinner');
			$(spinner).css('visibility', 'visible');

			return $.ajax({
				method: 'DELETE',
				url: wpApiSettings.root + 'items/' + post_id,
				beforeSend: function ( xhr ) {
					xhr.setRequestHeader( 'X-WP-Nonce', wpApiSettings.nonce );
				},
				success: function (item) {
					$(list_object).remove();
				},
			});
		});

		// Action trigged when clicking on a search item
		$(document).on('click', '.search-item-tpl', function(event){
			const data = { 
				post_id : $(event.currentTarget).data('search-item-id')
			};
			const area = $('.target');

			return $.ajax({
				method: 'POST',
				url: wpApiSettings.root + 'items',
				data: Object.assign(data),
				beforeSend: function ( xhr ) {
					xhr.setRequestHeader( 'X-WP-Nonce', wpApiSettings.nonce );
				},
				success: function (item) {
					addItemToArea(item, area);
				},
			});
		});
	});
})( jQuery );