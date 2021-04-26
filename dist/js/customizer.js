"use strict";

var _extends = Object.assign || function (target) { for (var i = 1; i < arguments.length; i++) { var source = arguments[i]; for (var key in source) { if (Object.prototype.hasOwnProperty.call(source, key)) { target[key] = source[key]; } } } return target; };

var _typeof = typeof Symbol === "function" && typeof Symbol.iterator === "symbol" ? function (obj) { return typeof obj; } : function (obj) { return obj && typeof Symbol === "function" && obj.constructor === Symbol && obj !== Symbol.prototype ? "symbol" : typeof obj; };

var _createClass = function () { function defineProperties(target, props) { for (var i = 0; i < props.length; i++) { var descriptor = props[i]; descriptor.enumerable = descriptor.enumerable || false; descriptor.configurable = true; if ("value" in descriptor) descriptor.writable = true; Object.defineProperty(target, descriptor.key, descriptor); } } return function (Constructor, protoProps, staticProps) { if (protoProps) defineProperties(Constructor.prototype, protoProps); if (staticProps) defineProperties(Constructor, staticProps); return Constructor; }; }();

function _classCallCheck(instance, Constructor) { if (!(instance instanceof Constructor)) { throw new TypeError("Cannot call a class as a function"); } }

(function (wp) {
	wp.customize.FeaturedAreaControl = wp.customize.Control.extend({
		ready: function ready() {
			var control = this,
			    container = this.container[0],
			    featuredAreaList = container.querySelector("ol.nested-sortable"),
			    addItemButton = container.querySelector(".add-featured-item");
			var _wp$i18n = wp.i18n,
			    __ = _wp$i18n.__,
			    _x = _wp$i18n._x,
			    _n = _wp$i18n._n,
			    _nx = _wp$i18n._nx;

			var featuredArea = void 0,
			    settings_timer = void 0,
			    search_timer = void 0,
			    max = void 0,
			    type = void 0,
			    subtype = void 0,
			    timer_ms = 500;

			var FeaturedItem = function () {
				function FeaturedItem(data, list, parent) {
					_classCallCheck(this, FeaturedItem);

					this.data = data;
					this.list = list;
					this.parent = parent;
					this.element = null;

					if (this.isFeaturedItemObject(this.data)) {

						// Add item.
						this.addItem();
					}
				}

				_createClass(FeaturedItem, [{
					key: "isFeaturedItemObject",
					value: function isFeaturedItemObject(obj) {
						return obj.hasOwnProperty('id') && obj.hasOwnProperty('title') && obj.hasOwnProperty('id');
					}

					// Create featured item element and add to list.

				}, {
					key: "addItem",
					value: function addItem() {
						var _this = this;

						// Create item html element
						var featuredItemTemplate = wp.template("featured-item");
						var innerHTML = featuredItemTemplate(this.data);
						this.element = htmlToElement(innerHTML);

						// Add event listeners for item.
						this.element.querySelector(".button-link-delete").addEventListener("click", function (event) {
							return _this.deleteItem(event);
						});
						this.element.querySelector(".featured-item-add").addEventListener("click", function (event) {
							return _this.cloneItem(event);
						});
						this.element.querySelector(".handle").addEventListener("click", function (event) {
							return _this.element.classList.toggle("open");
						});

						this.addSettings(this.element);

						// Initiate nested sortable in new featured item.
						var nestedSortable = this.element.querySelector('.nested-sortable');
						featuredArea.initSortable(nestedSortable);

						// If the item has a parent the add its element as a child to the parent.
						// In other case place it in the list sent to the constructor.
						if (typeof this.parent !== 'undefined' && featuredAreaList.dataset.levels > 1) {
							var parentItemOl = this.list.querySelector('[data-id="' + this.parent + '"] ol');
							parentItemOl.appendChild(this.element);
						} else {
							this.list.appendChild(this.element);
						}

						// If item has children then create items for them to.
						if (_typeof(this.data.children) === 'object') {
							this.data.children.forEach(function (child) {
								new FeaturedItem(child, _this.list, _this.data.id);
							});
						}
					}
				}, {
					key: "addSettings",
					value: function addSettings(element) {
						var _this2 = this;

						var settings = JSON.parse(featuredAreaList.dataset.settings);
						var data = this.data;
						Object.keys(settings).forEach(function (key) {
							var setting = settings[key];
							var setting_key = key;
							if ('select' === setting.type) {
								var selectList = document.createElement('select');
								//Create and append the options
								Object.keys(setting.values).forEach(function (key) {
									var option = setting.values[key];
									var optionElement = document.createElement("option");
									optionElement.value = key;
									optionElement.text = option;
									optionElement.selected = data[setting_key] === key;

									selectList.appendChild(optionElement);
								});
								element.querySelector('.settings').appendChild(selectList);
								element.addEventListener('change', function (event) {
									element.dataset[key] = event.target.value;
									_this2.data[key] = event.target.value;
									featuredArea.setSettings();
								});
							}
						});
					}

					// Removes the element.

				}, {
					key: "removeItem",
					value: function removeItem() {
						this.element.remove();
					}

					// Add featured item to featured area.

				}, {
					key: "cloneItem",
					value: function cloneItem(event) {
						var item = new FeaturedItem(this.data, featuredAreaList);
						featuredArea.toggleSearchPanel(event);
						if (featuredArea.isDuplicate(this.data)) {
							featuredArea.addErrorNotification('This item already exist in the selected featured area.');
							item.removeItem();
						} else if (featuredArea.isFull()) {
							featuredArea.addErrorNotification('The selected featured area is full.');
							item.removeItem();
							return;
						}
						featuredArea.setSettings();
					}

					// Delete item from the DOM and then update Settings.

				}, {
					key: "deleteItem",
					value: function deleteItem() {
						var item = featuredAreaList.querySelector('[data-id="' + this.data.id + '"]');
						item.remove();
						featuredArea.setSettings();
					}
				}]);

				return FeaturedItem;
			}();

			var FeaturedItemSearch = function () {
				function FeaturedItemSearch() {
					var _this3 = this;

					_classCallCheck(this, FeaturedItemSearch);

					this.active = true;
					this.searchResult = document.getElementById("featured-items-search-list");
					this.search('');

					// Event when something is written into the search input.
					document.getElementById("featured-items-search-input").addEventListener("keyup", function (event) {
						return _this3.onInputChange(event);
					});

					// If something outside the searchpanel i clicked.
					document.addEventListener("click", function (event) {
						if (!event.target.classList.contains("add-featured-item") && !isChildOf(event.target, "featured-item-container")) {
							_this3.close();
						}
					});

					// Event when mobile section back button is clicked.
					document.querySelector("#featured-items-search-panel .customize-section-back").addEventListener("click", function (event) {
						return _this3.close();
					});
				}

				// Show the search panel.


				_createClass(FeaturedItemSearch, [{
					key: "open",
					value: function open() {
						var body = document.querySelector("body");
						body.classList.add("adding-featured-items");
						this.active = true;
						this.search('');
					}

					// Hide the search panel.

				}, {
					key: "close",
					value: function close() {
						var body = document.querySelector("body");
						body.classList.remove("adding-featured-items");
						this.active = false;
						this.clear();
					}

					// Toggle the search panel.

				}, {
					key: "toggle",
					value: function toggle() {
						var body = document.querySelector("body");
						if (body.classList.contains("adding-featured-items")) {
							this.close();
						} else {
							this.open();
						}
					}

					// Clear the search field input.

				}, {
					key: "clear",
					value: function clear() {
						this.active = false;
						document.getElementById("featured-items-search-input").value = "";
					}

					// Start search when triggered.

				}, {
					key: "onInputChange",
					value: function onInputChange(event) {
						event.preventDefault();
						var search = event.target.value;
						this.search(search);
					}

					// Start search by AJAX REST API.

				}, {
					key: "search",
					value: function search(_search) {
						var _this4 = this;

						// Do nothing if searchpanel is closed.
						if (!this.active) return;

						//Clear timeout and start a new to avoid race conditions.
						clearTimeout(search_timer);
						search_timer = setTimeout(function () {

							// Show searching message and remove old result.
							var body = document.querySelector("body");
							body.classList.add("searching");
							var search_item_tpl = _this4.searchResult.querySelectorAll(".featured-item-tpl");
							[].forEach.call(search_item_tpl, function (item) {
								item.remove();
							});

							var items_in_list = featuredAreaList.querySelectorAll('.featured-item-tpl');
							var item_ids_in_list = Array();
							if (items_in_list.length > 1) {
								items_in_list.forEach(function (item) {
									return item_ids_in_list.push(Number(item.dataset.id));
								});
							}

							// Start AJAX request.
							window.fetch(wpApiSettings.root + wpFeaturedContentApiSettings.base + "posts", {
								method: "POST",
								headers: {
									Accept: "application/json",
									"Content-Type": "application/json",
									"X-WP-Nonce": wpApiSettings.nonce
								},
								body: JSON.stringify({
									"s": _search,
									"type": type,
									"subtype": subtype,
									'not_in': item_ids_in_list
								}),
								credentials: "same-origin"
							}).then(function (data) {
								return data.json();
							}).then(function (data) {
								// Remove searching message and add result in DOM.
								body.classList.remove("searching");
								data.forEach(function (obj, index) {
									new FeaturedItem(obj, _this4.searchResult);
								});
							});
						}, timer_ms);
					}
				}]);

				return FeaturedItemSearch;
			}();

			var FeaturedArea = function () {
				function FeaturedArea() {
					var _this5 = this;

					_classCallCheck(this, FeaturedArea);

					this.nestedSortables = [];

					// Set featured area globals.
					max = featuredAreaList.dataset.max;
					type = featuredAreaList.dataset.type;
					subtype = featuredAreaList.dataset.subtype.split(',');

					// Add eventlistener on add button click to toggle search panel.
					addItemButton.addEventListener("click", function (event) {
						return _this5.toggleSearchPanel(event);
					});
				}

				// Load the featured area settings from customizer.


				_createClass(FeaturedArea, [{
					key: "loadSettings",
					value: function loadSettings() {
						var settings = control.setting.get();
						try {
							settings = JSON.parse(settings);
						} catch (e) {
							console.log(e);
							settings = [{}];
						}

						// Remove items larger than 50.
						settings = settings.slice(0, 50);

						// Add items from settings to the DOM.
						settings.forEach(function (item) {
							if (item != null) {
								new FeaturedItem(item, featuredAreaList);
							}
						});

						this.initSortables();
					}

					// Returns object with data attributes from element.

				}, {
					key: "getDataAttributes",
					value: function getDataAttributes(dataset) {
						return Object.keys(dataset).reduce(function (object, key) {
							object[key] = dataset[key];
							return object;
						}, {});
					}

					// Update the customizer control settings.

				}, {
					key: "setSettings",
					value: function setSettings() {
						var _this6 = this;

						// Set timeout to avoid race contitions.
						clearTimeout(settings_timer);
						settings_timer = setTimeout(function () {
							var settings = _this6.serialize(featuredAreaList);
							control.setting.set(JSON.stringify(settings));

							// Update customizer preview.
							wp.customize.previewer.refresh();
						}, timer_ms);
					}

					// Serializes the sortable list and returns array.

				}, {
					key: "serialize",
					value: function serialize(sortable) {
						var serialized = [];
						var children = [].slice.call(sortable.children);
						for (var i in children) {
							var nested = children[i].querySelector('.nested-sortable');
							var attributes = this.getDataAttributes(children[i].dataset);
							serialized.push(_extends({}, attributes, {
								children: nested ? this.serialize(nested) : []
							}));
						}
						return serialized;
					}

					// Check if the object exist as an element in the featured area list.

				}, {
					key: "isDuplicate",
					value: function isDuplicate(obj) {
						var result = false;
						if (featuredAreaList.querySelectorAll('[data-id="' + obj.id + '"]').length > 1) {
							result = true;
						}
						return result;
					}

					// Check if the featured area list contiains max amount of items already.

				}, {
					key: "isFull",
					value: function isFull() {
						var children = featuredAreaList.querySelectorAll('.featured-item-tpl');
						return max < children.length;
					}

					// Toggle the search panel.

				}, {
					key: "toggleSearchPanel",
					value: function toggleSearchPanel(event) {
						event.preventDefault();
						if (this.searchPanel) {
							this.searchPanel.toggle();
						} else {
							this.searchPanel = new FeaturedItemSearch();
							this.searchPanel.toggle();
						}
					}

					// Initialize sortablejs elemtens.

				}, {
					key: "initSortables",
					value: function initSortables() {

						// Create featured area list.
						// This lists can recive cloned items from search result list.
						var featuredAreaList = container.querySelector('.featured-area');
						this.initSortable(featuredAreaList);

						// Create search result list.
						// This list can clone each items to featured area lists.
						var searchList = document.querySelector('#featured-items-search-list');
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

				}, {
					key: "initSortable",
					value: function initSortable(sortable) {
						var _this7 = this;

						var args = arguments.length > 1 && arguments[1] !== undefined ? arguments[1] : {
							group: 'nested',
							swapThreshold: 0.65,
							emptyInsertThreshold: 5,
							animation: 150,
							onSort: function onSort(event) {
								_this7.setSettings();
							},
							onAdd: function onAdd(event) {
								if (_this7.isDuplicate(event.clone.dataset)) {
									event.item.remove();
									_this7.addErrorNotification('This item already exist in the selected featured area.');
								}
							}
						};

						new Sortable(sortable, args);
					}
				}, {
					key: "addErrorNotification",
					value: function addErrorNotification(message) {
						wp.customize.notifications.add('error', new wp.customize.Notification('error', {
							dismissible: true,
							message: __(message, 'featured-content-manager'),
							type: 'error'
						}));
					}
				}]);

				return FeaturedArea;
			}();

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