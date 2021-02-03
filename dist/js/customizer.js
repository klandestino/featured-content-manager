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
			    timer_ms = 500;

			var FeaturedItem = function () {
				function FeaturedItem(data, list, parent) {
					_classCallCheck(this, FeaturedItem);

					this.data = data;
					this.list = list;
					this.parent = parent;

					// Add item.
					this.addItem();
				}

				// Create featured item element and add to list.


				_createClass(FeaturedItem, [{
					key: "addItem",
					value: function addItem() {
						var _this = this;

						// Create item html element
						var featuredItemTemplate = wp.template("featured-item");
						var innerHTML = featuredItemTemplate(this.data);
						var element = htmlToElement(innerHTML);

						// Add event listeners for item.
						element.querySelector(".button-link-delete").addEventListener("click", function (event) {
							return _this.deleteItem(event);
						});
						element.querySelector(".featured-item-edit").addEventListener("click", function (event) {
							return _this.toggleItemEdit(event);
						});

						// If the item has a parent the add its element as a child to the parent.
						// In other case place it in the list sent to the constructor.
						if (typeof this.parent !== 'undefined') {
							var parentItemOl = this.list.querySelector('[data-id="' + this.parent + '"] ol');
							parentItemOl.appendChild(element);
						} else {
							this.list.appendChild(element);
						}

						// If item has children then create items for them to.
						if (_typeof(this.data.children) === 'object') {
							this.data.children.forEach(function (child) {
								new FeaturedItem(child, _this.list, _this.data.id);
							});
						}
					}

					// Toggle the edit item view.

				}, {
					key: "toggleItemEdit",
					value: function toggleItemEdit(event) {
						event.preventDefault();
						var item = featuredAreaList.querySelector('[data-id="' + this.data.id + '"]');
						var open = featuredAreaList.querySelector("li.open");

						if (open !== null) open.classList.remove("open");
						if (open == item) {
							item.classList.remove("open");
						} else {
							item.classList.add("open");
						}
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
					var _this2 = this;

					_classCallCheck(this, FeaturedItemSearch);

					this.active = true;
					this.searchResult = document.getElementById("featured-items-search-list");
					this.search('');

					// Event when something is written into the search input.
					document.getElementById("featured-items-search-input").addEventListener("keyup", function (event) {
						return _this2.onInputChange(event);
					});

					// If something outside the searchpanel i clicked.
					document.addEventListener("click", function (event) {
						if (!event.target.classList.contains("add-featured-item") && !isChildOf(event.target, "featured-item-container")) {
							_this2.close();
						}
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
						var _this3 = this;

						// Do nothing if searchpanel is closed.
						if (!this.active) return;

						//Clear timeout and start a new to avoid race conditions.
						clearTimeout(search_timer);
						search_timer = setTimeout(function () {

							// Show searching message and remove old result.
							var body = document.querySelector("body");
							body.classList.add("searching");
							var search_item_tpl = _this3.searchResult.querySelectorAll(".featured-item-tpl");
							[].forEach.call(search_item_tpl, function (item) {
								item.remove();
							});

							// Start AJAX request.
							window.fetch(wpApiSettings.root + wpFeaturedContentApiSettings.base + "posts?s=" + _search + "&type=" + type, {
								method: "GET",
								headers: {
									Accept: "application/json",
									"Content-Type": "application/json",
									"X-WP-Nonce": wpApiSettings.nonce
								},
								credentials: "same-origin"
							}).then(function (data) {
								return data.json();
							}).then(function (data) {
								// Remove searching message and add result in DOM.
								body.classList.remove("searching");
								data.forEach(function (obj, index) {
									new FeaturedItem(obj, _this3.searchResult);
								});
							});
						}, timer_ms);
					}
				}]);

				return FeaturedItemSearch;
			}();

			var FeaturedArea = function () {
				function FeaturedArea() {
					var _this4 = this;

					_classCallCheck(this, FeaturedArea);

					this.nestedSortables = [];

					// Set featured area globals.
					max = featuredAreaList.dataset.max;
					type = featuredAreaList.dataset.type;

					// Add eventlistener on add button click to toggle search panel.
					addItemButton.addEventListener("click", function (event) {
						return _this4.toggleSearchPanel(event);
					});

					// Load the featured area settings from customizer.
					this.loadSettings();

					// Initialize nestledSortable
					this.initSortables();
				}

				// Load the featured area settings from customizer.


				_createClass(FeaturedArea, [{
					key: "loadSettings",
					value: function loadSettings() {
						var settings = control.setting.get();
						try {
							settings = JSON.parse(settings);
						} catch (e) {
							settings = settings;
						}

						// Remove items larger than the featured are max value.
						settings = settings.slice(0, max);

						// Add items from settings to the DOM.
						settings.forEach(function (item) {
							if (item != null) {
								new FeaturedItem(item, featuredAreaList);
							}
						});
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
						var _this5 = this;

						// Set timeout to avoid race contitions.
						clearTimeout(settings_timer);
						settings_timer = setTimeout(function () {
							var settings = _this5.serialize(featuredAreaList);
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
						var _this6 = this;

						var sortables = [].slice.call(container.querySelectorAll('.nested-sortable'));

						// Loop through each nested sortable element.
						// These lists can recive cloned items from search result list.
						for (var i = 0; i < sortables.length; i++) {
							this.nestedSortables[i] = new Sortable(sortables[i], {
								group: 'nested',
								swapThreshold: 0.65,
								emptyInsertThreshold: 42,
								onSort: function onSort(evt) {
									_this6.setSettings();
								},
								onAdd: function onAdd(evt) {
									if (_this6.isDuplicate(evt.clone.dataset)) {
										evt.item.remove();
										_this6.addErrorNotification('This item already exist in the selected featured area.');
									} else if (_this6.isFull()) {
										evt.item.remove();
										_this6.addErrorNotification('The selected featured area is full.');
									}
								}
							});
						}

						// Create search result list.
						// This list can clone each items to featured area lists.
						var searchList = document.querySelector('#featured-items-search-list');
						new Sortable(searchList, {
							group: {
								name: 'nested',
								pull: 'clone',
								put: false // Do not allow items to be put into this list
							},
							sort: false // To disable sorting: set sort to false
						});
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