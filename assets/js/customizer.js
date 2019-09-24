(function(wp, $) {
	wp.customize.FeaturedAreaControl = wp.customize.Control.extend({
		ready: function() {
			const control = this,
				container = this.container[0],
				areaContainer = container.querySelector("ol.featured-area"),
				addItemButton = container.querySelector(".add-featured-item");
			const { __, _x, _n, _nx } = wp.i18n;
			let featuredArea,
				settings_timer,
				search_timer,
				timer_ms = 500;

			class ListItem {
				constructor(post, parent) {
					this.id = post.id;
					this.postData = post;
					this.parent = parent;
					this.element = null;
					this.element_id = control.id + "_item_" + post.id;

					// Add item element to ol list.
					this.addItem();
				}

				// Create featured item element.
				addItem() {
					let featuredItemTemplate = wp.template("featured-item");
					this.element = areaContainer.querySelector('[data-id="' + this.id + '"]');
					if (!this.element) {
						this.element = document.createElement("li");
						this.element.id = this.element_id;
						this.element.classList.add(this.postData.post_status);
						for(var attribute in this.postData ) {
							this.element.setAttribute('data-' + attribute, this.postData[attribute]);
						}
						this.element.innerHTML = featuredItemTemplate(this.postData); // WP templating the markup.

						// Add elemtents for all the settings.
						this.element
							.querySelector(".item-delete")
							.addEventListener("click", event =>
								this.deleteItem(event)
							);
						this.element
							.querySelector(".handle")
							.addEventListener("click", event =>
								this.toggleItemEdit(event)
							);
						if (this.element.querySelector("input")) {
							var inputs = this.element
								.querySelectorAll("input");
							for (var i = 0; i < inputs.length; i++) {
								inputs[i].addEventListener("keyup", event =>
									this.updateItem(event)
								);
							}
						}
						if (this.element.querySelector(".featured-item-image-field-upload")) {
							var buttons = this.element
								.querySelectorAll(".featured-item-image-field-upload");
							for (var x = 0; x < buttons.length; x++) {
								buttons[x].addEventListener("click", event =>
									this.selectMedia(event)
								);
							}
						}
						if (this.element.querySelector(".featured-item-image-field-remove")) {
							var remove = this.element
								.querySelectorAll(".featured-item-image-field-remove");
							for (var y = 0; y < remove.length; y++) {
								remove[y].addEventListener("click", event =>
									this.removeMedia(event)
								);
							}
						}
						if (this.element.querySelector("textarea")) {
							this.element
								.querySelector("textarea")
								.addEventListener("keyup", event =>
									this.updateItem(event)
								);
						}
						if (this.element.querySelector("select")) {
							this.element
								.querySelector("select")
								.addEventListener("change", event =>
									this.updateItem(event)
								);
						}

						// If the item has a parent the add its element as a child to the parent.
						if (
							typeof this.parent !== 'undefined'
						) {
							const parentItemOl = areaContainer.querySelector('[data-id="' + this.parent + '"] ol');
							parentItemOl.appendChild(this.element);
						} else {
							areaContainer.appendChild(this.element);
						}

						// If item has parent add it to parent else add it last
						if (
							typeof this.postData.children === 'object'
						) {
							this.postData.children.forEach( child => {
								new ListItem(child, this.id);
							});
						}
					}
				}

				// Toggle the edit item view.
				toggleItemEdit(event) {
					event.preventDefault();
					const item = areaContainer.querySelector('[data-id="' + this.id + '"]');
					const open = areaContainer.querySelector("li.open");

					if (open !== null) open.classList.remove("open");
					if (open == item) {
						item.classList.remove("open");
					} else {
						item.classList.add("open");
					}
				}

				// Select media.
				selectMedia(e) {
					e.preventDefault();
					var selector = $(e.target).parent( '.featured-item-image-field-container' );
					var fcm_uploader = wp.media({
		                title: 'Select or upload image',
		                button: {
		                    text: 'Set image'
		                },
		                multiple: false
					}).on('select', () => {
						var attachment = fcm_uploader.state().get('selection').first().toJSON();
						var input = selector.find( 'input' );
						selector.find('img').attr( 'src', attachment.url).show();
						input.val(attachment.id);
						selector.find('.featured-item-image-field-remove').show();
						selector.find('.featured-item-image-field-upload').hide();
						this.setPostData(input.attr('name'), attachment.id);
						this.setPostData(input.attr('name') + '_src', attachment.url);
					}).open();
				}

				// Remove selected media.
				removeMedia(e) {
					e.preventDefault();
					var selector = $(e.target).parent('.featured-item-image-field-container');
					var input = selector.find('input');
					input.val('');
					selector.find('img').attr('src', '#').hide();

					selector.find('.featured-item-image-field-remove').hide();
					selector.find('.featured-item-image-field-upload').show();

					this.setPostData(input.attr('name'), '');
					this.setPostData(input.attr('name') + '_src', '');
				}

				// Set post data with the updated values.
				updateItem(event) {
					const key = event.target.name;
					const val = event.target.value;
					this.setPostData(event.target.name, event.target.value);
				}

				// Update item element data attributes with the new value and set the settings.
				setPostData(key, val) {
					// Due to jQuerys odd memory system. We have to change the data attribute
					// with jQuery and vanilla js setAttribute. The first line changes the 
					// value in the jQuery memory som that the nestleSortable will be updated
					// and the second line will update the DOM.
					$(this.element).data(key, val);
					this.element.setAttribute('data-' + key, val);
					this.setSettings();
				}

				// Set the settings in customizer.
				setSettings() {
					featuredArea.setSettings();
				}

				// Delete featured item from the Set and the DOM
				deleteItem() {
					let item = areaContainer.querySelector('[data-id="' + this.id + '"]');
					item.remove();
					this.setSettings();
				}
			}

			class FeaturedItemSearch {
				constructor() {
					this.active = true;
					this.featured_area = control.id;
					this.searchPanel = document.getElementById(
						"available-featured-items"
					);
					this.search('');
					document
						.getElementById("featured-items-search")
						.addEventListener("keyup", event => this.onInputChange(event));
					document.addEventListener("click", event => {
						if (
							!event.target.classList.contains(
								"add-featured-item"
							) &&
							!isChildOf(
								event.target,
								"accordion-container"
							)
						) {
							this.close();
						}
					});
				}

				// Open the search panel.
				open() {
					const body = document.querySelector("body");
					body.classList.add("adding-featured-items");
					this.active = true;
					this.search('');
				}

				// Close the search panel.
				close() {
					const body = document.querySelector("body");
					body.classList.remove("adding-featured-items");
					this.active = false;
					this.clear();
				}

				// Toggle the search panel.
				toggle() {
					const body = document.querySelector("body");
					if (body.classList.contains("adding-featured-items")) {
						this.close();
					} else {
						this.open();
					}
				}

				// Clear the search field input.
				clear() {
					this.active = false;
					document.getElementById("featured-items-search").value = "";
				}

				onInputChange(event) {
					event.preventDefault();
					const search = event.target.value;
					this.search(search);
				}

				search(search) {
					if (!this.active) return;

					const body = document.querySelector("body");
					clearTimeout(search_timer);
					search_timer = setTimeout(() => {
						body.classList.add("searching");
						var search_item_tpl = document
							.querySelectorAll(".search-item-tpl");
						[].forEach.call(search_item_tpl, function(item) {
							item.remove();
						});

						window.fetch(
							wpApiSettings.root +
								wpFeaturedContentApiSettings.base +
								"posts?s=" +
								search,
							{
								method: "GET",
								headers: {
									Accept:
										"application/json",
									"Content-Type":
										"application/json",
									"X-WP-Nonce":
										wpApiSettings.nonce
								},
								credentials: "same-origin",
							}
						)
							.then(data => data.json())
							.then(data => {
								body.classList.remove("searching");
								let featuredSearchItemTemplate = wp.template(
									"search-item"
								);
								data.forEach((obj, index) => {
									let item = document.createElement("li");
									item.id = obj.id;
									item.classList.add("search-item-tpl");
									item.innerHTML = featuredSearchItemTemplate(
										obj
									);

									document
										.querySelector(
											"#available-featured-items-list"
										)
										.appendChild(item)
										.addEventListener("click", event => {
											obj.featured_area = this.featured_area;
											// Chech if post already exist in this featured area.
											if ( featuredArea.doesExist(obj) ) {
												wp.customize.notifications.add(
													'error',
													new wp.customize.Notification( 
														'error',
														{
															dismissible: true,
															message: __( 'This post already exist in the selected featured area!', 'featured-content-manager' ),
															type: 'error'
														}
													)
												);
												return;
											}
											new ListItem(obj);
											featuredArea.setSettings();
										});
								});
							});
					}, timer_ms);
				}
			}

			class FeaturedArea {
				constructor() {
					this.searchPanel = null;
					// Add item on button click.
					addItemButton.addEventListener("click", event =>
						this.toggleSearchPanel(event)
					);

					// Load the featured area settings from customizer.
					this.loadSettings();

					// Initialize nestledSortable
					this.initSortable();
				}

				// Load the featured area settings from customizer.
				loadSettings() {
					let settings = control.setting.get();
					try {
						settings = JSON.parse(settings); 
					} catch (e) {
						settings = settings;
					}
					settings.forEach(item => {
						if ( item != null )
							new ListItem(item);
					});
				}

				// At the end of the timer set the settings in the customizer.
				setSettings() {
					clearTimeout(settings_timer);
					settings_timer = setTimeout(() => {
						let newSettings = $(areaContainer).nestedSortable(
							"toHierarchy"
						);
						control.setting.set(JSON.stringify(newSettings));
						wp.customize.previewer.refresh();
					}, timer_ms);
				}

				// Update order of the featured area.
				updateOrder(array) {
					this.setSettings();
				}

				// Check if the object exist as an element in the featured area.
				doesExist( obj ) {
					let result = false;
					if (
						areaContainer.querySelector('[data-id="' + obj.id + '"]') != null
					) {
						result = true;
					}
					return result;
				}

				// Toggle the search panel.
				toggleSearchPanel(event) {
					event.preventDefault();
					if (this.searchPanel) {
						this.searchPanel.toggle();
					} else {
						this.searchPanel = new FeaturedItemSearch();
						this.searchPanel.toggle();
					}
				}

				// Initialize jQuery nestedSortable
				initSortable() {
					$(areaContainer).nestedSortable({
						handle: ".handle",
						items: "li",
						toleranceElement: "> div",
						maxLevels: 2,
						excludeRoot: true,
						forcePlaceholderSize: true,
						placeholder: "placeholder",
						stop: e => {
							let array = $(areaContainer).nestedSortable(
								"toHierarchy",
								{ attribute: "id" }
							);
							this.updateOrder(array);
						}
					});
				}
			}

			// Initiate the featured area and loat its settings.
			featuredArea = new FeaturedArea();
		}
	});

	$.extend(wp.customize.controlConstructor, {
		"featured-area": wp.customize.FeaturedAreaControl
	});

	function isChildOf(element, classname) {
		if (!element.parentNode) return false;
		if (element.className.split(" ").indexOf(classname) >= 0) return true;
		return isChildOf(element.parentNode, classname);
	}
})(wp, jQuery);