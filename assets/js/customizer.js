var $ = jQuery.noConflict();

$(function() {
	$(document).ready(function(){
		$('ol.featured-area').each(function(){
			var area = this;
			$.ajax( {
				url: wpApiSettings.root + 'items',
				success: function (data) {
					$(data).each(function(){
						addItemToArea(this, area);
					});
					$(area).nestedSortable({
						handle: '.handle',
						items: 'li',
						toleranceElement: '> div',
						maxLevels: 2,
						excludeRoot: true,
						forcePlaceholderSize: true,
						placeholder: 'placeholder',
						stop: function() { updateArea(area); }
					});
				},
				cache: false
			});
		});

		$('.add-featured-item').on('click', function(event){
			var area = $(this).parent().find('ol.featured-area');
			var item = createItem();
			item.success(function (item) {
				addItemToArea(item, area);
			});
		});

		$(document).on('click', '.featured-item-edit', function(event){
			var item = $(this).parent().parent();
			if( $(item).hasClass('open') ) {
				$(item).removeClass('open');
			} else {
				$('ol.featured-area li').removeClass('open');
				$(item).addClass('open');
			}
		});
	});

	function updateArea(area){
		area_array = $(area).nestedSortable('toArray');
		$(area_array).each(function(index){
			updateItem(this, index);
		});
	}

	function createItem(){
		var data = {
			title: 'ny title',
			excerpt: 'excerpt',
			content: 'content',
			status: 'publish',
			post_type: 'featured-content'
		};

		return $.ajax({
			method: "POST",
			url: wpApiSettings.root + 'items',
			data: data,
			beforeSend: function ( xhr ) {
		        xhr.setRequestHeader( 'X-WP-Nonce', wpApiSettings.nonce );
		    }
		});
	}

	function updateItem(item, index){
		var data = {
			menu_order: index,
			post_parent: item.parent_id,
		};

		return $.ajax({
			method: "POST",
			url: wpApiSettings.root + 'items/' + item.id,
			data: data,
			beforeSend: function ( xhr ) {
		        xhr.setRequestHeader( 'X-WP-Nonce', wpApiSettings.nonce );
		    }
		});
	}

	function addItemToArea(item, area){
		var template = wp.template('featured-item');
		var output = template(item);
		$(area).append(output);
	}
});