"use strict";

var _typeof = typeof Symbol === "function" && typeof Symbol.iterator === "symbol" ? function (obj) { return typeof obj; } : function (obj) { return obj && typeof Symbol === "function" && obj.constructor === Symbol && obj !== Symbol.prototype ? "symbol" : typeof obj; };

var _createClass = function () { function defineProperties(target, props) { for (var i = 0; i < props.length; i++) { var descriptor = props[i]; descriptor.enumerable = descriptor.enumerable || false; descriptor.configurable = true; if ("value" in descriptor) descriptor.writable = true; Object.defineProperty(target, descriptor.key, descriptor); } } return function (Constructor, protoProps, staticProps) { if (protoProps) defineProperties(Constructor.prototype, protoProps); if (staticProps) defineProperties(Constructor, staticProps); return Constructor; }; }();

function _classCallCheck(instance, Constructor) { if (!(instance instanceof Constructor)) { throw new TypeError("Cannot call a class as a function"); } }

(function (wp, $) {
	wp.customize.FeaturedAreaControl = wp.customize.Control.extend({
		ready: function ready() {
			var control = this,
			    container = this.container[0],
			    areaContainer = container.querySelector("ol.featured-area"),
			    addItemButton = container.querySelector(".add-featured-item");
			var _wp$i18n = wp.i18n,
			    __ = _wp$i18n.__,
			    _x = _wp$i18n._x,
			    _n = _wp$i18n._n,
			    _nx = _wp$i18n._nx;

			var featuredArea = void 0,
			    settings_timer = void 0,
			    search_timer = void 0,
			    timer_ms = 500;

			var ListItem = function () {
				function ListItem(post, parent) {
					_classCallCheck(this, ListItem);

					this.id = post.id;
					this.postData = post;
					this.element_id = "item_" + post.id;
					this.parent = parent;
					this.element = null;

					// Add item element to ol list.
					this.addItem();
				}

				// Create featured item element.


				_createClass(ListItem, [{
					key: "addItem",
					value: function addItem() {
						var _this = this;

						var featuredItemTemplate = wp.template("featured-item");
						this.element = document.getElementById(this.element_id);
						if (!this.element) {
							this.element = document.createElement("li");
							this.element.classList.add(this.postData.post_status);
							this.element.id = "item_" + this.id;
							for (var attribute in this.postData) {
								this.element.setAttribute('data-' + attribute, this.postData[attribute]);
							}
							this.element.innerHTML = featuredItemTemplate(this.postData); // WP templating the markup.

							// Add elemtents for all the settings.
							this.element.querySelector(".item-delete").addEventListener("click", function (event) {
								return _this.deleteItem(event);
							});
							this.element.querySelector(".handle").addEventListener("click", function (event) {
								return _this.toggleItemEdit(event);
							});
							if (this.element.querySelector("input")) {
								var inputs = this.element.querySelectorAll("input");
								for (var i = 0; i < inputs.length; i++) {
									inputs[i].addEventListener("keyup", function (event) {
										return _this.updateItem(event);
									});
								}
							}
							if (this.element.querySelector(".featured-item-image-field-upload")) {
								var buttons = this.element.querySelectorAll(".featured-item-image-field-upload");
								for (var x = 0; x < buttons.length; x++) {
									buttons[x].addEventListener("click", function (event) {
										return _this.selectMedia(event);
									});
								}
							}
							if (this.element.querySelector(".featured-item-image-field-remove")) {
								var remove = this.element.querySelectorAll(".featured-item-image-field-remove");
								for (var y = 0; y < remove.length; y++) {
									remove[y].addEventListener("click", function (event) {
										return _this.removeMedia(event);
									});
								}
							}
							if (this.element.querySelector("textarea")) {
								this.element.querySelector("textarea").addEventListener("keyup", function (event) {
									return _this.updateItem(event);
								});
							}
							if (this.element.querySelector("select")) {
								this.element.querySelector("select").addEventListener("change", function (event) {
									return _this.updateItem(event);
								});
							}

							// If the item has a parent the add its element as a child to the parent.
							if (typeof this.parent !== 'undefined') {
								var parentItemOl = areaContainer.querySelector("#item_" + this.parent + " ol");
								parentItemOl.appendChild(this.element);
							} else {
								areaContainer.appendChild(this.element);
							}

							// If item has parent add it to parent else add it last
							if (_typeof(this.postData.children) === 'object') {
								this.postData.children.forEach(function (child) {
									new ListItem(child, _this.id);
								});
							}
						}
					}

					// Toggle the edit item view.

				}, {
					key: "toggleItemEdit",
					value: function toggleItemEdit(event) {
						event.preventDefault();
						var item = document.getElementById(this.element_id);
						var open = container.querySelector("li.open");

						if (open !== null) open.classList.remove("open");
						if (open == item) {
							item.classList.remove("open");
						} else {
							item.classList.add("open");
						}
					}

					// Select media.

				}, {
					key: "selectMedia",
					value: function selectMedia(e) {
						var _this2 = this;

						e.preventDefault();
						var selector = $(e.target).parent('.featured-item-image-field-container');
						var fcm_uploader = wp.media({
							title: 'Select or upload image',
							button: {
								text: 'Set image'
							},
							multiple: false
						}).on('select', function () {
							var attachment = fcm_uploader.state().get('selection').first().toJSON();
							var input = selector.find('input');
							selector.find('img').attr('src', attachment.url).show();
							input.val(attachment.id);
							selector.find('.featured-item-image-field-remove').show();
							selector.find('.featured-item-image-field-upload').hide();
							_this2.setPostData(input.attr('name'), attachment.id);
							_this2.setPostData(input.attr('name') + '_src', attachment.url);
						}).open();
					}

					// Remove selected media.

				}, {
					key: "removeMedia",
					value: function removeMedia(e) {
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

				}, {
					key: "updateItem",
					value: function updateItem(event) {
						var key = event.target.name;
						var val = event.target.value;
						this.setPostData(event.target.name, event.target.value);
					}

					// Update item element data attributes with the new value and set the settings.

				}, {
					key: "setPostData",
					value: function setPostData(key, val) {
						// Due to jQuerys odd memory system. We have to change the data attribute
						// with jQuery and vanilla js setAttribute. The first line changes the 
						// value in the jQuery memory som that the nestleSortable will be updated
						// and the second line will update the DOM.
						$(this.element).data(key, val);
						this.element.setAttribute('data-' + key, val);
						this.setSettings();
					}

					// Set the settings in customizer.

				}, {
					key: "setSettings",
					value: function setSettings() {
						featuredArea.setSettings();
					}

					// Delete featured item from the Set and the DOM

				}, {
					key: "deleteItem",
					value: function deleteItem() {
						var item = areaContainer.querySelector("#" + this.element_id);
						item.remove();
						this.setSettings();
					}
				}]);

				return ListItem;
			}();

			var FeaturedItemSearch = function () {
				function FeaturedItemSearch() {
					var _this3 = this;

					_classCallCheck(this, FeaturedItemSearch);

					this.active = true;
					this.featured_area = control.id;
					this.searchPanel = document.getElementById("available-featured-items");
					this.search('');
					document.getElementById("featured-items-search").addEventListener("keyup", function (event) {
						return _this3.onInputChange(event);
					});
					document.addEventListener("click", function (event) {
						if (!event.target.classList.contains("add-featured-item") && !isChildOf(event.target, "accordion-container")) {
							_this3.close();
						}
					});
				}

				// Open the search panel.


				_createClass(FeaturedItemSearch, [{
					key: "open",
					value: function open() {
						var body = document.querySelector("body");
						body.classList.add("adding-featured-items");
						this.active = true;
						this.search('');
					}

					// Close the search panel.

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
						document.getElementById("featured-items-search").value = "";
					}
				}, {
					key: "onInputChange",
					value: function onInputChange(event) {
						event.preventDefault();
						var search = event.target.value;
						this.search(search);
					}
				}, {
					key: "search",
					value: function search(_search) {
						var _this4 = this;

						if (!this.active) return;

						var body = document.querySelector("body");
						clearTimeout(search_timer);
						search_timer = setTimeout(function () {
							body.classList.add("searching");
							var search_item_tpl = document.querySelectorAll(".search-item-tpl");
							[].forEach.call(search_item_tpl, function (item) {
								item.remove();
							});

							window.fetch(wpApiSettings.root + wpFeaturedContentApiSettings.base + "posts?s=" + _search, {
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
								body.classList.remove("searching");
								var featuredSearchItemTemplate = wp.template("search-item");
								data.forEach(function (obj, index) {
									var item = document.createElement("li");
									item.id = obj.ID;
									item.classList.add("search-item-tpl");
									item.innerHTML = featuredSearchItemTemplate(obj);

									document.querySelector("#available-featured-items-list").appendChild(item).addEventListener("click", function (event) {
										obj.featured_area = _this4.featured_area;

										// Chech if post already exist in this featured area.
										if (featuredArea.doesExist(obj)) {
											wp.customize.notifications.add('error', new wp.customize.Notification('error', {
												dismissible: true,
												message: __('This post already exist in the selected featured area!', 'featured-content-manager'),
												type: 'error'
											}));
											return;
										}
										new ListItem(obj);
									});
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

					this.searchPanel = null;
					// Add item on button click.
					addItemButton.addEventListener("click", function (event) {
						return _this5.toggleSearchPanel(event);
					});

					// Load the featured area settings from customizer.
					this.loadSettings();

					// Initialize nestledSortable
					this.initSortable();
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
						settings.forEach(function (item) {
							if (item != null) new ListItem(item);
						});
					}

					// At the end of the timer set the settings in the customizer.

				}, {
					key: "setSettings",
					value: function setSettings() {
						clearTimeout(settings_timer);
						settings_timer = setTimeout(function () {
							var newSettings = $(areaContainer).nestedSortable("toHierarchy");
							console.log(newSettings);
							control.setting.set(JSON.stringify(newSettings));
							wp.customize.previewer.refresh();
						}, timer_ms);
					}

					// Update order of the featured area.

				}, {
					key: "updateOrder",
					value: function updateOrder(array) {
						this.setSettings();
					}

					// Check if the object exist as an element in the featured area.

				}, {
					key: "doesExist",
					value: function doesExist(obj) {
						var result = false;
						if (areaContainer.querySelector('#item_' + obj.ID) != null) {
							result = true;
						}
						return result;
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

					// Initialize jQuery nestedSortable

				}, {
					key: "initSortable",
					value: function initSortable() {
						var _this6 = this;

						$(areaContainer).nestedSortable({
							handle: ".handle",
							items: "li",
							toleranceElement: "> div",
							maxLevels: 2,
							excludeRoot: true,
							forcePlaceholderSize: true,
							placeholder: "placeholder",
							stop: function stop(e) {
								var array = $(areaContainer).nestedSortable("toHierarchy", { attribute: "id" });
								_this6.updateOrder(array);
							}
						});
					}
				}]);

				return FeaturedArea;
			}();

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