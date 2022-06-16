(function(wp) {
	wp.customize.FeaturedAreaControl = wp.customize.Control.extend({
		ready: function() {
			const control = this,
				container = this.container[0],
				featuredAreaList = container.querySelector("ol.nested-sortable"),
				addItemButtons = container.querySelectorAll(".add-featured-item"),
				clearOverflowButton = container.querySelector(".clear-overflow");
			const { __, _x, _n, _nx } = wp.i18n;
			let featuredArea,
				settings_timer,
				search_timer,
				max,
				type,
				subtype,
				timer_ms = 500;

			class FeaturedItem {
				constructor(data, list, parent) {
					this.data = data;
					this.list = list;
					this.parent = parent;
					this.element = null;

					if(this.isFeaturedItemObject(this.data)) {

						// Add item.
						this.addItem();
					}
				}

				isFeaturedItemObject(obj) {
					return (
						obj.hasOwnProperty('id')
						&& obj.hasOwnProperty('title')
						&& obj.hasOwnProperty('id')
					);
				}

				// Create featured item element and add to list.
				addItem() {
					// Create item html element
					let featuredItemTemplate = wp.template("featured-item");
					let innerHTML = featuredItemTemplate(
						this.data
					);
					this.element = htmlToElement(innerHTML);

					// Add event listeners for item.
					this.element
						.querySelector(".button-link-delete")
						.addEventListener("click", event =>
							this.deleteItem(event)
						);
					this.element
						.querySelector(".featured-item-add")
						.addEventListener("click", event =>
							this.cloneItem(event)
						);
					this.element
						.querySelector(".handle")
						.addEventListener("click", () => {
							if ( ! Array.isArray(JSON.parse(featuredAreaList.dataset.settings)) ) {
								this.element.classList.toggle("open");
							}
						});

					this.addSettings( this.element );

					// Initiate nested sortable in new featured item.
					let nestedSortable = this.element.querySelector('.nested-sortable');
					featuredArea.initSortable(nestedSortable);

					// If the item has a parent the add its element as a child to the parent.
					// In other case place it in the list sent to the constructor.
					if (
						typeof this.parent !== 'undefined' &&
						featuredAreaList.dataset.levels > 1
					) {
						const parentItemOl = this.list.querySelector('[data-id="' + this.parent + '"] ol');
						parentItemOl.appendChild(this.element);
					} else {
						this.list.appendChild(this.element);
					}

					// If item has children then create items for them to.
					if (
						typeof this.data.children === 'object'
					) {
						this.data.children.forEach( child => {
							new FeaturedItem(child, this.list, this.data.id);
						});
					}
				}

				addSettings( element ) {
					let settings = JSON.parse( featuredAreaList.dataset.settings );
					let data = this.data;
					Object.keys(settings).forEach( key => {
						let setting = settings[key];
						let setting_key = key;
						if( 'select' === setting.type ) {
							let selectList = document.createElement('select');
							//Create and append the options
							Object.keys(setting.values).forEach( key => {
								let option = setting.values[key];
							    var optionElement = document.createElement("option");
							    optionElement.value = key;
							    optionElement.text = option;
							    optionElement.selected = ( data[setting_key] === key );

							    selectList.appendChild(optionElement);
							});
							if(undefined!==data[setting_key])
								element.dataset[key] = data[setting_key];
							element.querySelector('.settings').appendChild(selectList);
							element.addEventListener( 'change', event => {
								element.dataset[key] = event.target.value;
								this.data[key] = event.target.value;
								featuredArea.setSettings();
							});
						}
					});
				}

				// Removes the element.
				removeItem() {
					this.element.remove();
				}

				// Add featured item to featured area.
				cloneItem(event) {
					let item = new FeaturedItem(this.data, featuredAreaList);
					featuredArea.toggleSearchPanel(event);
					if ( featuredArea.isDuplicate( this.data ) ) {
						featuredArea.addErrorNotification('This item already exist in the selected featured area.');
						item.removeItem();
					} else if ( featuredArea.isFull() ) {
						featuredArea.addErrorNotification('The selected featured area is full.');
						item.removeItem();
						return;
					}
					featuredArea.setSettings();
				}

				// Delete item from the DOM and then update Settings.
				deleteItem() {
					let item = featuredAreaList.querySelector('[data-id="' + this.data.id + '"]');
					item.remove();
					featuredArea.setSettings();
				}
			}

			class FeaturedItemSearch {
				constructor() {
					this.active = true;
					this.searchResult = document.getElementById(
						"featured-items-search-list"
					);
					this.search('');

					// Event when something is written into the search input.
					document
						.getElementById("featured-items-search-input")
						.addEventListener("keyup", event => this.onInputChange(event));

					// If something outside the searchpanel i clicked.
					document.addEventListener(
						"click",
						event => {
							if (
								!event.target.classList.contains(
									"add-featured-item"
								) &&
								!isChildOf(
									event.target,
									"featured-item-container"
								)
							) {
								this.close();
							}
						}
					);

					// Event when mobile section back button is clicked.
					document.querySelector("#featured-items-search-panel .customize-section-back")
						.addEventListener("click", event => this.close());
				}

				// Show the search panel.
				open() {
					const body = document.querySelector("body");
					body.classList.add("adding-featured-items");
					this.active = true;
					this.search('');
				}

				// Hide the search panel.
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
					document.getElementById("featured-items-search-input").value = "";
				}

				// Start search when triggered.
				onInputChange(event) {
					event.preventDefault();
					const search = event.target.value;
					this.search(search);
				}

				// Start search by AJAX REST API.
				search(search) {
					// Do nothing if searchpanel is closed.
					if (!this.active) return;

					//Clear timeout and start a new to avoid race conditions.
					clearTimeout(search_timer);
					search_timer = setTimeout(() => {

						// Show searching message and remove old result.
						const body = document.querySelector("body");
						body.classList.add("searching");
						var search_item_tpl = this.searchResult
							.querySelectorAll(".featured-item-tpl");
						[].forEach.call(search_item_tpl, function(item) {
							item.remove();
						});

						// Start AJAX request.
						window.fetch(
							wpApiSettings.root +
								wpFeaturedContentApiSettings.base + "posts",
							{
								method: "POST",
								headers: {
									Accept:
										"application/json",
									"Content-Type":
										"application/json",
									"X-WP-Nonce":
										wpApiSettings.nonce
								},
								body: JSON.stringify(
									{
										"s": search,
										"type": type,
										"subtype": subtype
									}
								),
								credentials: "same-origin",
							}
						)
						.then(data => data.json())
						.then(data => {
							// Remove searching message and add result in DOM.
							body.classList.remove("searching");
							data.forEach((obj, index) => {
								new FeaturedItem(obj,this.searchResult);
							});
						});
					}, timer_ms);
				}
			}

			class FeaturedArea {
				constructor() {
					this.nestedSortables = [];

					// Set featured area globals.
					max = featuredAreaList.dataset.max;
					type = featuredAreaList.dataset.type;
					subtype = featuredAreaList.dataset.subtype.split(',');

					// Add eventlistener on add button click to toggle search panel.
					addItemButtons.forEach( (button) => {
						button.addEventListener("click", event =>
							this.toggleSearchPanel(event)
						);
					});
					
					// Clear overflow.
					clearOverflowButton.addEventListener("click", event =>
						this.clearOverflow(event)
					);
				}

				// Load the featured area settings from customizer.
				loadSettings() {
					let settings = control.setting.get();
					try {
						settings = JSON.parse(settings); 
					} catch (e) {
						console.log(e);
						settings = [ {} ];
					}

					// Remove items larger than 50.
					settings = settings.slice(0, 50);

					// Add items from settings to the DOM.
					settings.forEach(item => {
						if ( item != null ) {
							new FeaturedItem(item, featuredAreaList);
						}
					});

					this.initSortables();
				}

				// Returns object with data attributes from element.
				getDataAttributes( dataset ) {
					return Object.keys( dataset ).reduce( function( object, key ) {
				        object[ key ] = dataset[ key ]; 
				        return object;
				    }, {});
				}

				// Update the customizer control settings.
				setSettings() {
					// Set timeout to avoid race contitions.
					clearTimeout(settings_timer);
					settings_timer = setTimeout(() => {
						let settings = this.serialize( featuredAreaList );
						control.setting.set(JSON.stringify(settings));

						// Update customizer preview.
						wp.customize.previewer.refresh();
					}, timer_ms);
				}

				// Serializes the sortable list and returns array.
				serialize(sortable) {
					var serialized = [];
					var children = [].slice.call(sortable.children);
					for (var i in children) {
						var nested = children[i].querySelector('.nested-sortable');
						let attributes = this.getDataAttributes( children[i].dataset );
						serialized.push({
							...attributes,
							children: nested ? this.serialize(nested) : []
						});
					}
					return serialized;
				}

				// Check if the object exist as an element in the featured area list.
				isDuplicate( obj ) {
					let result = false;
					if (
						featuredAreaList.querySelectorAll('[data-id="' + obj.id + '"]').length > 1
					) {
						result = true;
					}
					return result;
				}

				// Check if the featured area list contiains max amount of items already.
				isFull() {
					let children = featuredAreaList.querySelectorAll('.featured-item-tpl');
					return max < children.length;
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
				
				// Clear overflow.
				clearOverflow(event) {
					event.preventDefault();
					var children = [].slice.call(featuredAreaList.querySelectorAll('.featured-item-tpl'));
					children.splice(max).forEach( child => {
						child.remove();
						this.setSettings();
					});
				}

				// Initialize sortablejs elemtens.
				initSortables() {

					// Create featured area list.
					// This lists can recive cloned items from search result list.
					let featuredAreaList = container.querySelector('.featured-area');
					this.initSortable(featuredAreaList);

					// Create search result list.
					// This list can clone each items to featured area lists.
					let searchList = document.querySelector('#featured-items-search-list');
					this.initSortable(searchList, {
					    group: {
					        name: 'nested',
					        pull: 'clone',
					        put: false // Do not allow items to be put into this list
					    },
					    animation: 150,
					    sort: false // To disable sorting: set sort to false
					});
				}

				// Initiate sortable witht default values for nest-sortables.
				initSortable(
					sortable,
					args = {
						group: 'nested',
						swapThreshold: 0.65,
						emptyInsertThreshold: 5,
						animation: 150,
						onSort: (event) => {
							this.setSettings();
						},
						onAdd: (event) => {
							if ( this.isDuplicate( event.clone.dataset ) ) {
								event.item.remove();
								this.addErrorNotification('This item already exist in the selected featured area.');
							}
						}
					}
				) {
					new Sortable(sortable, args);
				}

				addErrorNotification(message) {
					wp.customize.notifications.add(
						'error',
						new wp.customize.Notification( 
							'error',
							{
								dismissible: true,
								message: __( message, 'featured-content-manager' ),
								type: 'error'
							}
						)
					);
				}
			}

			// Initiate the featured area and loat its settings.
			featuredArea = new FeaturedArea();
			featuredArea.loadSettings();
		}
	});

	// Extend controlConstructor to our own custom FeaturedAreaControl.
	_.extend(wp.customize.controlConstructor, {
		"featured-area": wp.customize.FeaturedAreaControl
	});

	// Test if the elements is a child of a ekement with a classname.
	function isChildOf(element, classname) {
		if (!element.parentNode) return false;
		if (element.className.split(" ").indexOf(classname) >= 0) return true;
		return isChildOf(element.parentNode, classname);
	}

	// Recives a html string and returns a DOM element from it.
	function htmlToElement(html) {
	    var template = document.createElement('template');
	    html = html.trim(); // Never return a text node of whitespace as the result
	    template.innerHTML = html;
	    return template.content.firstChild;
	}

})(wp, jQuery);
