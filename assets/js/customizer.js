( function( wp, $ ) {

	wp.customize.FeaturedAreaControl = wp.customize.Control.extend({
		ready: function() {
			let control = this,
				area = $( 'ol.featured-area', control.container ),
				value = control.setting.get(),
				featured_item_template = wp.template('featured-item'),
				search_template = wp.template('search-item');
			let timer, timer_ms = 500;
			
			$.ajax( {
				url: wpApiSettings.root + 'items',
				data: { post_status : 'draft' },
				success: function (data) {
					$(data).each( (index, item) => {
						addItemToArea(item);
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
							updateArea();
						}
					});
				},
				cache: false
			});

			// Add featured item to list
			function addItemToArea(item) {
				$(area).append(featured_item_template(item));
			}

			// Update the wholre featured area
			function updateArea() {
				let area_array = $(area).nestedSortable('toArray');
				$(area_array).each(function(index, item){
					updateItem(item, index);
				});
			}

			// Update a single features item
			function updateItem(item, index){
				const data = {
					menu_order: index,
					post_parent: item.parent_id,
				};			
				const extra_data = collectData(item);

				return $.ajax({
					method: 'POST',
					url: wpApiSettings.root + 'items/' + item.id,
					data: Object.assign(data, extra_data),
					beforeSend: function (xhr) {
						xhr.setRequestHeader( 'X-WP-Nonce', wpApiSettings.nonce );
					},
					success: function(data){
						updateSetting();
					}
				});
			}

			// Collect post data from input fields
			function collectData(item){
				const id = '#featured_item_'+item.id;
				let post_data = {};

				$(id + ' form:first', control.container).serializeArray().map(function(item) {
        			post_data[item.name] = item.value;
				});
				return post_data;
			}

			// Function for updating customizer setting
			function updateSetting(){
				const old_setting = control.setting.get();
				let area_array = $(area).nestedSortable('toArray').map(function(item, index) {
					item.post_data = collectData(item);
					return item;
				});
				const new_setting = JSON.stringify(area_array);

				if(new_setting!=old_setting){
					control.setting.set(new_setting);
				}
			}

			// Action trigged when uppdating input fields
			$(document).on('input', '.featured-item-edit-input', function(event){
				clearTimeout(timer);
				timer = setTimeout(function() {
					const list_element = $(event.currentTarget).closest('li');
					const index = $(list_element).index('ol.featured-area li');
					const area_array = $(area).nestedSortable('toArray');
					const item = area_array[index];
					updateItem(item, index, area);
				}, timer_ms);
			});

			// Action trigged when extending featured item
			$(document).on('click', '.handle', function(event){
				const item = $(this).parent();
				if( $(item).hasClass('open') ) {
					$(item).removeClass('open');
				} else {
					$('ol.featured-area li', control.container).removeClass('open');
					$(item).addClass('open');
				}
			});

			// Action trigged when clicking on a search item
			$(document).on('click', '.button-link-delete', function(event){
				const list_element = $(event.currentTarget).closest('li');
				const post_id = $(list_element).data('featured-item-id');
				
				// Show spinner
				$(list_element).find('.spinner').css('visibility', 'visible');

				return $.ajax({
					method: 'DELETE',
					url: wpApiSettings.root + 'items/' + post_id,
					beforeSend: function ( xhr ) {
						xhr.setRequestHeader( 'X-WP-Nonce', wpApiSettings.nonce );
					},
					success: function (item) {
						$(list_element).remove();
						updateSetting();
					},
				});
			});

			// Action trigged when clicking add featured item
			$(document).on('click', '.add-featured-item', function(event){
				event.preventDefault();
				$('body').toggleClass('adding-featured-items');
			});

			// Action trigged when clicking outside avalible featured items
			$(document).on('click', 'body.wp-customizer', function(event){
				if( !$(event.target).hasClass('add-featured-item') && $(event.target).closest('div#available-featured-items').length == 0 ){
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
								$('.available-featured-items-list').append(search_template(item));
							});
						},
						cache: false
					} );
				}, timer_ms);
			});

			// Action trigged when clicking on a search item
			$(document).on('click', '.search-item-tpl', function(event){
				const index = $(area).find('li').length;
				const data = { 
					post_id : $(event.currentTarget).data('search-item-id'),
					menu_order: index
				};

				return $.ajax({
					method: 'POST',
					url: wpApiSettings.root + 'items',
					data: Object.assign(data),
					beforeSend: function ( xhr ) {
						xhr.setRequestHeader( 'X-WP-Nonce', wpApiSettings.nonce );
					},
					success: function (item) {
						addItemToArea(item);
						updateSetting();
					},
				});
			});
		}
	});

	$.extend( wp.customize.controlConstructor, {
		'featured-area': wp.customize.FeaturedAreaControl,
	} );

} )( wp, jQuery );