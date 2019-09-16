"use strict";

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
			    itemObjects = new Map(),
			    timer = void 0,
			    search_timer = void 0,
			    timer_ms = 500;

			var ListItem = function () {
				function ListItem(post) {
					_classCallCheck(this, ListItem);

					this.key = post.ID;
					this.postData = post;
					this.featured_area = control.id;
					this.element_id = "item_" + post.ID;

					// Add item element to list
					this.addItem();
				}

				// Create featured item from the Set and the DOM


				_createClass(ListItem, [{
					key: "addItem",
					value: function addItem() {
						var _this = this;

						var item = document.getElementById(this.element_id),
						    featuredTtemTemplate = wp.template("featured-item");
						if (!item) {
							item = document.createElement("li");
							item.classList.add(this.postData.original_post_status);
							item.id = "item_" + this.key;
							item.innerHTML = featuredTtemTemplate(this.postData); // WP templating the markup
							item.querySelector(".item-delete").addEventListener("click", function (event) {
								return _this.deleteItem(event);
							});
							item.querySelector(".handle").addEventListener("click", function (event) {
								return _this.toggleItemEdit(event);
							});
							if (item.querySelector("input")) {
								var inputs = item.querySelectorAll("input");
								for (var i = 0; i < inputs.length; i++) {
									inputs[i].addEventListener("keyup", function (event) {
										return _this.updateItem(event);
									});
								}
							}
							if (item.querySelector(".featured-item-image-field-upload")) {
								var buttons = item.querySelectorAll(".featured-item-image-field-upload");
								for (var x = 0; x < buttons.length; x++) {
									buttons[x].addEventListener("click", function (event) {
										return _this.selectMedia(event);
									});
								}
							}
							if (item.querySelector(".featured-item-image-field-remove")) {
								var remove = item.querySelectorAll(".featured-item-image-field-remove");
								for (var y = 0; y < remove.length; y++) {
									remove[y].addEventListener("click", function (event) {
										return _this.removeMedia(event);
									});
								}
							}
							if (item.querySelector("textarea")) {
								item.querySelector("textarea").addEventListener("keyup", function (event) {
									return _this.updateItem(event);
								});
							}
							if (item.querySelector("select")) {
								item.querySelector("select").addEventListener("change", function (event) {
									return _this.updateItem(event);
								});
							}

							// If item has parent add it to parent else add it last
							if (this.postData.post_parent !== 0) {
								var parentItemOl = areaContainer.querySelector("#item_" + this.postData.post_parent + " ol");
								parentItemOl.appendChild(item);
							} else {
								var menu_order = areaContainer.querySelectorAll("li") ? areaContainer.querySelectorAll("li").length : 0;
								this.postData['menu_order'] = menu_order;
								areaContainer.appendChild(item);
							}
							itemObjects.set(this.key, this);
						}
					}
				}, {
					key: "getPostData",
					value: function getPostData() {
						this.postData.featured_area = this.featured_area;
						return this.postData;
					}
				}, {
					key: "setPostData",
					value: function setPostData(key, val) {
						this.postData[key] = val;
						this.setSettings();
					}
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
						}).open();
					}
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
					}
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
				}, {
					key: "updateItem",
					value: function updateItem(event) {
						var key = event.target.name;
						var val = event.target.value;
						this.setPostData(key, val);
					}
				}, {
					key: "setSettings",
					value: function setSettings() {
						featuredArea.setSettings();
					}

					// Returns an array of keys for items children

				}, {
					key: "getChildren",
					value: function getChildren() {
						var _this3 = this;

						var children = [];
						itemObjects.forEach(function (item) {
							var postData = item.getPostData();
							if (postData.post_parent == _this3.key) children.push(postData.ID);
						});
						return children;
					}

					// Delete featured item from the Set and the DOM

				}, {
					key: "deleteItem",
					value: function deleteItem() {
						var _this4 = this;

						var item = areaContainer.querySelector("#" + this.element_id);
						if (item) {
							var children = this.getChildren();
							if (children.length !== 0) {
								children.forEach(function (childID) {
									var child = itemObjects.get(parseInt(childID));
									child.deleteItem();
								});
							}
							item.remove();
						}

						window.fetch(wpApiSettings.root + wpFeaturedContentApiSettings.base + "items/" + this.key, {
							method: "DELETE",
							headers: {
								Accept: "application/json",
								"Content-Type": "application/json",
								"X-WP-Nonce": wpApiSettings.nonce
							},
							credentials: "same-origin"
						}).then(function (data) {
							return data.json();
						}).then(function (data) {
							itemObjects.delete(parseInt(_this4.key));
							featuredArea.setSettings();
						});
					}
				}]);

				return ListItem;
			}();

			var FeaturedItemSearch = function () {
				function FeaturedItemSearch() {
					var _this5 = this;

					_classCallCheck(this, FeaturedItemSearch);

					this.active = true;
					this.featured_area = control.id;
					this.searchPanel = document.getElementById("available-featured-items");
					this.search('');
					document.getElementById("featured-items-search").addEventListener("keyup", function (event) {
						return _this5.onInputChange(event);
					});
					document.addEventListener("click", function (event) {
						if (!event.target.classList.contains("add-featured-item") && !isChildOf(event.target, "accordion-container")) {
							_this5.close();
						}
					});
				}

				_createClass(FeaturedItemSearch, [{
					key: "open",
					value: function open() {
						var body = document.querySelector("body");
						body.classList.add("adding-featured-items");
						this.active = true;
						this.search('');
					}
				}, {
					key: "close",
					value: function close() {
						var body = document.querySelector("body");
						body.classList.remove("adding-featured-items");
						this.active = false;
						this.clear();
					}
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
						var _this6 = this;

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
										obj.featured_area = _this6.featured_area;

										// Chech if post already exist in this featured area.
										if (featuredArea.doesExist(obj)) {
											wp.customize.notifications.add('error', new wp.customize.Notification('error', {
												dismissible: true,
												message: __('This post already exist in the selected featured area!', 'featured-content-manager'),
												type: 'error'
											}));
											return;
										}

										window.fetch(wpApiSettings.root + wpFeaturedContentApiSettings.base + "items", {
											method: "POST",
											headers: {
												Accept: "application/json",
												"Content-Type": "application/json",
												"X-WP-Nonce": wpApiSettings.nonce
											},
											credentials: "same-origin",
											body: JSON.stringify({
												obj: obj
											})
										}).then(function (data) {
											return data.json();
										}).then(function (data) {
											data.forEach(function (item) {
												new ListItem(item);
											});
											featuredArea.setSettings();
										});
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
					var _this7 = this;

					_classCallCheck(this, FeaturedArea);

					this.searchPanel = null;
					// Add item on button click.
					addItemButton.addEventListener("click", function (event) {
						return _this7.toggleSearchPanel(event);
					});

					// Initialize nestledSortable
					this.initSortable();
				}

				_createClass(FeaturedArea, [{
					key: "loadSettings",
					value: function loadSettings() {
						var settings = control.setting.get();
						try {
							settings = JSON.parse(settings);
						} catch (e) {
							settings = settings;
						}
						settings.sort(function (a, b) {
							return a.menu_order - b.menu_order;
						});
						window.fetch(wpApiSettings.root + wpFeaturedContentApiSettings.base + "items", {
							method: "POST",
							headers: {
								Accept: "application/json",
								"Content-Type": "application/json",
								"X-WP-Nonce": wpApiSettings.nonce
							},
							credentials: "same-origin",
							body: JSON.stringify({
								settings: settings
							})
						}).then(function (data) {
							return data.json();
						}).then(function (data) {
							data.forEach(function (item) {
								if (item != null) new ListItem(item);
							});
							if (!!window.MSInputMethodContext && !!document.documentMode) {
								wp.customize.previewer.refresh();
							}
						});
					}
				}, {
					key: "setSettings",
					value: function setSettings() {

						clearTimeout(timer);
						timer = setTimeout(function () {

							var oldSettings = control.setting.get(),
							    newSettings = [];
							itemObjects.forEach(function (item) {
								newSettings.push(item.getPostData());
							});
							newSettings.sort(function (a, b) {
								return a.menu_order - b.menu_order;
							});
							if (newSettings != oldSettings) {
								control.setting.set(JSON.stringify(newSettings));

								window.fetch(wpApiSettings.root + wpFeaturedContentApiSettings.base + "settings", {
									method: "POST",
									headers: {
										Accept: "application/json",
										"Content-Type": "application/json",
										"X-WP-Nonce": wpApiSettings.nonce
									},
									credentials: "same-origin",
									body: control.setting.get()
								}).then(function (data) {
									return data.json();
								}).then(function (data) {});
							}
						}, timer_ms);
					}
				}, {
					key: "updateOrder",
					value: function updateOrder(array) {
						var newItems = new Map();
						array.forEach(function (obj, index) {
							var key = parseInt(obj.id);
							var item = itemObjects.get(key);

							item.setPostData("menu_order", index);
							item.setPostData("post_parent", obj.parent_id ? obj.parent_id : 0);
						});
					}
				}, {
					key: "doesExist",
					value: function doesExist(obj) {
						var result = false;
						itemObjects.forEach(function (item) {
							if (parseInt(obj.ID) === parseInt(item.postData.original_post_id)) {
								result = true;
							}
						});
						return result;
					}
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

					// Saves a new sticky item on localStorage.

				}, {
					key: "addItem",
					value: function addItem() {
						event.preventDefault();
						window.fetch(wpApiSettings.root + wpFeaturedContentApiSettings.base + "items").then(function (data) {
							return data.json();
						}).then(function (data) {
							new ListItem(data[0]);
						});
					}

					// Initialize jQuery nestedSortable

				}, {
					key: "initSortable",
					value: function initSortable() {
						var _this8 = this;

						$(areaContainer).nestedSortable({
							handle: ".handle",
							items: "li",
							toleranceElement: "> div",
							maxLevels: 2,
							excludeRoot: true,
							forcePlaceholderSize: true,
							placeholder: "placeholder",
							stop: function stop(e) {
								var array = $(areaContainer).nestedSortable("toArray", { attribute: "id" });
								_this8.updateOrder(array);
							}
						});
					}
				}]);

				return FeaturedArea;
			}();

			function menuOrder(a, b) {
				if (a.menu_order < b.menu_order) return -1;
				if (a.menu_order > b.menu_order) return 1;
				return 0;
			}

			featuredArea = new FeaturedArea();
			featuredArea.loadSettings();
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