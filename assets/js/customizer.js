(function(wp, $) {
	wp.customize.FeaturedAreaControl = wp.customize.Control.extend({
		ready: function() {
			const control = this,
				container = this.container[0],
				areaContainer = container.querySelector("ol.featured-area"),
				addItemButton = container.querySelector(".add-featured-item");
			let featuredArea,
				itemObjects = new Map(),
				timer,
				timer_ms = 500;

			class ListItem {
				constructor(post) {
					this.itemTimer = false;
					this.key = post.ID;
					this.postData = post;
					this.featured_area = control.id;
					this.element_id = "item_" + post.ID;

					// Add item element to list
					this.addItem();
				}

				// Create featured item from the Set and the DOM
				addItem() {
					let item = document.getElementById(this.element_id),
						featuredTtemTemplate = wp.template("featured-item");
					if (!item) {
						item = document.createElement("li");
						item.id = "item_" + this.key;
						item.innerHTML = featuredTtemTemplate(this.postData); // WP templating the markup
						item
							.querySelector(".item-delete")
							.addEventListener("click", event =>
								this.deleteItem(event)
							);
						item
							.querySelector(".handle")
							.addEventListener("click", event =>
								this.toggleItemEdit(event)
							);
						if (item.querySelector("input")) {
							item
								.querySelector("input")
								.addEventListener("keyup", event =>
									this.updateItem(event)
								);
						}
						if (item.querySelector("textarea")) {
							item
								.querySelector("textarea")
								.addEventListener("keyup", event =>
									this.updateItem(event)
								);
						}

						// If item has parent add it to parent else add it last
						if (this.postData.post_parent !== 0) {
							const parentItemOl = areaContainer.querySelector(
								"#item_" + this.postData.post_parent + " ol"
							);
							parentItemOl.appendChild(item);
						} else {
							var menu_order = ( areaContainer.querySelectorAll("li") ? areaContainer.querySelectorAll("li").length : 0 );
							this.postData['menu_order'] = menu_order;
							areaContainer.appendChild(item);
						}
						itemObjects.set(this.key, this);
					}
				}

				getPostData() {
					this.postData.featured_area = this.featured_area;
					return this.postData;
				}

				setPostData(key, val) {
					this.postData[key] = val;
					this.setSettings();
				}

				toggleItemEdit(event) {
					event.preventDefault();
					const item = document.getElementById(this.element_id);
					const open = container.querySelector("li.open");

					if (open !== null) open.classList.remove("open");
					if (open == item) {
						item.classList.remove("open");
					} else {
						item.classList.add("open");
					}
				}

				updateItem(event) {
					event.preventDefault();
					const key = event.srcElement.name;
					const val = event.srcElement.value;
					this.setPostData(key, val);
				}

				setSettings() {
					clearTimeout(this.itemTimer);
					this.itemTimer = setTimeout(() => {
						featuredArea.setSettings();
					}, timer_ms);
				}

				// Returns an array of keys for items children
				getChildren() {
					let children = [];
					itemObjects.forEach(item => {
						let postData = item.getPostData();
						if (postData.post_parent == this.key)
							children.push(postData.ID);
					});
					return children;
				}

				// Delete featured item from the Set and the DOM
				deleteItem() {
					let item = areaContainer.querySelector(
						"#" + this.element_id
					);
					if (item) {
						let children = this.getChildren();
						if (children.length !== 0) {
							children.forEach(childID => {
								let child = itemObjects.get(parseInt(childID));
								child.deleteItem();
							});
						}
						item.remove();
					}

					fetch(
						wpApiSettings.root +
							wpFeaturedContentApiSettings.base +
							"items/" + this.key,
						{
							method: "DELETE",
							headers: {
								Accept:
									"application/json",
								"Content-Type":
									"application/json",
								"X-WP-Nonce":
									wpApiSettings.nonce
							},
							credentials: "same-origin"
						}
					)
					.then(data => data.json())
					.then(data => {
						itemObjects.delete(parseInt(this.key));
						featuredArea.setSettings();
					});
				}
			}

			class FeaturedItemSearch {
				constructor() {
					this.active = true;
					this.featured_area = control.id;
					this.searchPanel = document.getElementById(
						"available-featured-items"
					);
					document
						.getElementById("featured-items-search")
						.addEventListener("keyup", event => this.search(event));
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

				open() {
					const body = document.querySelector("body");
					body.classList.add("adding-featured-items");
					this.active = true;
				}

				close() {
					const body = document.querySelector("body");
					body.classList.remove("adding-featured-items");
					this.active = false;
					this.clear();
				}

				toggle() {
					const body = document.querySelector("body");

					if (body.classList.contains("adding-featured-items")) {
						this.close();
					} else {
						this.open();
					}
				}

				clear() {
					this.active = false;
					document.getElementById("featured-items-search").value = "";
					document.querySelector(
						"#available-featured-items-list"
					).innerHTML =
						"";
				}

				search(event) {
					event.preventDefault();
					if (!this.active) return;

					const search = event.target.value;

					clearTimeout(timer);
					timer = setTimeout(() => {
						document
							.querySelectorAll(".search-item-tpl")
							.forEach(e => e.parentNode.removeChild(e));

						fetch(
							wpApiSettings.root +
								wpFeaturedContentApiSettings.base +
								"posts?s=" +
								search
						)
							.then(data => data.json())
							.then(data => {
								let featuredSearchItemTemplate = wp.template(
									"search-item"
								);
								data.forEach((obj, index) => {
									let item = document.createElement("li");
									item.id = obj.ID;
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
											fetch(
												wpApiSettings.root +
													wpFeaturedContentApiSettings.base +
													"items",
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
													credentials: "same-origin",
													body: JSON.stringify({
														obj
													})
												}
											)
												.then(data => data.json())
												.then(data => {
													data.forEach(item => {
														new ListItem(item);
													});
													featuredArea.setSettings();
												});
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

					// Initialize nestledSortable
					this.initSortable();
				}

				loadSettings() {
					let settings = JSON.parse(control.setting.get());
					fetch(
						wpApiSettings.root +
							wpFeaturedContentApiSettings.base +
							"items",
						{
							method: "POST",
							headers: {
								Accept: "application/json",
								"Content-Type": "application/json",
								"X-WP-Nonce": wpApiSettings.nonce
							},
							credentials: "same-origin",
							body: JSON.stringify({
								settings
							})
						}
					)
						.then(data => data.json())
						.then(data => {
							data.forEach(item => {
								if ( item != null )
									new ListItem(item);
							});
						});
				}

				setSettings() {
					let oldSettings = control.setting.get(),
						newSettings = [];
					itemObjects.forEach(item => {
						newSettings.push(item.getPostData());
					});
					newSettings.sort((a, b) => a.menu_order > b.menu_order);
					if (newSettings != oldSettings) {
						control.setting.set(JSON.stringify(newSettings));

						fetch(
							wpApiSettings.root +
								wpFeaturedContentApiSettings.base +
								"settings",
							{
								method: "POST",
								headers: {
									Accept: "application/json",
									"Content-Type": "application/json",
									"X-WP-Nonce": wpApiSettings.nonce
								},
								credentials: "same-origin",
								body: control.setting.get()
							}
						)
							.then(data => data.json())
							.then(data => {});
					}
				}

				updateOrder(array) {
					let newItems = new Map();
					array.forEach((obj, index) => {
						let key = parseInt(obj.id);
						let item = itemObjects.get(key);

						item.setPostData("menu_order", index);
						item.setPostData(
							"post_parent",
							obj.parent_id ? obj.parent_id : 0
						);
					});
				}

				toggleSearchPanel(event) {
					event.preventDefault();
					if (this.searchPanel) {
						this.searchPanel.toggle();
					} else {
						this.searchPanel = new FeaturedItemSearch();
						this.searchPanel.toggle();
					}
				}

				// Saves a new sticky item on localStorage.
				addItem() {
					event.preventDefault();
					fetch(
						wpApiSettings.root +
							wpFeaturedContentApiSettings.base +
							"items"
					)
						.then(data => data.json())
						.then(data => {
							new ListItem(data[0]);
						});
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
								"toArray",
								{ attribute: "id" }
							);
							this.updateOrder(array);
						}
					});
				}
			}

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
