/**
 * Conjure WP Main JavaScript
 *
 * @package Conjure WP
 */

var Conjure = (function ($) {
	var t;
	var drawer_opened;

	// Callbacks from form button clicks.
	var callbacks = {
		install_child: function (btn) {
			var installer = new ChildTheme();
			installer.init(btn);
		},
		activate_license: function (btn) {
			var license = new ActivateLicense();
			license.init(btn);
		},
		install_plugins: function (btn) {
			var plugins = new PluginManager();
			plugins.init(btn);
		},
		install_content: function (btn) {
			var content = new ContentManager();
			content.init(btn);
		},
	};

	function window_loaded() {
		var body = $(".conjure__body"),
			body_loading = $(".conjure__body--loading"),
			body_exiting = $(".conjure__body--exiting"),
			drawer_trigger = $("#conjure__drawer-trigger"),
			drawer_opening = "conjure__drawer--opening";

		drawer_opened = "conjure__drawer--open";

		setTimeout(function () {
			body.addClass("loaded");

			// Debug: Check drawer content on page load
			var drawerContent = $(".js-conjure-drawer-import-content");
			var drawerItems = $(".conjure__drawer--import-content__list-item");
			console.log("=== Conjure Debug - Page Loaded ===");
			console.log("Drawer content HTML:", drawerContent.html());
			console.log("Drawer items count:", drawerItems.length);
			if (drawerItems.length === 0) {
				console.error(
					"ERROR: No import options loaded! Check PHP get_import_data_info() and get_import_steps_html()"
				);
			}
		}, 100);

		drawer_trigger.on("click", function () {
			body.toggleClass(drawer_opened);

			// Debug: Check if drawer content exists
			var drawerContent = $(".js-conjure-drawer-import-content");
			var drawerItems = $(".conjure__drawer--import-content__list-item");
			console.log("Conjure Debug - Drawer clicked");
			console.log(
				"Drawer content element exists:",
				drawerContent.length > 0
			);
			console.log("Drawer items count:", drawerItems.length);
			if (drawerItems.length > 0) {
				console.log("First drawer item:", drawerItems.first().html());
			} else {
				console.log(
					"WARNING: No drawer items found! The import options list is empty."
				);
			}
		});

		// Initialize Server Health dropdown
		init_server_health_dropdown();

		// Initialize health telemetry live checks
		init_health_telemetry_live_checks();

		// Allow clicking anywhere on the content list item (outside of the upload zone) to toggle the checkbox.
		document.addEventListener("click", function (event) {
			var listItem = event.target.closest(
				".conjure__drawer--import-content__list-item"
			);

			if (!listItem) {
				return;
			}

			// Ignore clicks that originate inside the upload zone UI.
			if (event.target.closest(".conjure__upload-zone")) {
				return;
			}

			// Let native behaviour handle label and checkbox clicks.
			if (
				event.target.closest("label") ||
				event.target.tagName === "INPUT"
			) {
				return;
			}

			var checkbox = listItem.querySelector(
				".js-conjure-upload-checkbox"
			);

			if (!checkbox) {
				return;
			}

			event.preventDefault();
			checkbox.checked = !checkbox.checked;
			checkbox.dispatchEvent(new Event("change", { bubbles: true }));
		});

		// Initialize file upload handlers
		init_file_uploads();

		$(".conjure__button--proceed:not(.conjure__button--closer)").click(
			function (e) {
				e.preventDefault();
				var goTo = this.getAttribute("href");

				body.addClass("exiting");

				setTimeout(function () {
					window.location = goTo;
				}, 400);
			}
		);

		$(".conjure__button--closer").on("click", function (e) {
			body.removeClass(drawer_opened);

			e.preventDefault();
			var goTo = this.getAttribute("href");

			setTimeout(function () {
				body.addClass("exiting");
			}, 600);

			setTimeout(function () {
				window.location = goTo;
			}, 1100);
		});

		$(".button-next").on("click", function (e) {
			e.preventDefault();
			var loading_button = conjure_loading_button(this);
			if (!loading_button) {
				return false;
			}
			var data_callback = $(this).data("callback");
			if (
				data_callback &&
				typeof callbacks[data_callback] !== "undefined"
			) {
				// We have to process a callback before continue with form submission.
				callbacks[data_callback](this);
				return false;
			} else {
				return true;
			}
		});

		// Handle demo selection on CONTENT step (original behavior).
		$(document).on("change", ".js-conjure-demo-import-select", function () {
			var selectedIndex = $(this).val();

			$(".js-conjure-select-spinner").show();

			$.post(
				conjure_params.ajaxurl,
				{
					action: "conjure_update_selected_import_data_info",
					wpnonce: conjure_params.wpnonce,
					selected_index: selectedIndex,
				},
				function (response) {
					if (response.success) {
						// Handle the new response format with import_info_html.
						var importInfoHtml =
							response.data.import_info_html || response.data;
						$(".js-conjure-drawer-import-content").html(
							importInfoHtml
						);

						// If demo-specific plugins are available, store them for later use.
						if (
							response.data.has_plugins &&
							response.data.demo_plugins
						) {
							// Store in data attribute for potential future use.
							$(".js-conjure-drawer-import-content").data(
								"demo-plugins",
								response.data.demo_plugins
							);

							// Log for debugging (remove in production if desired).
							if (console && console.log) {
								console.log(
									"Demo-specific plugins loaded:",
									response.data.demo_plugins
								);
							}
						}
					} else {
						alert(conjure_params.texts.something_went_wrong);
					}

					$(".js-conjure-select-spinner").hide();
				}
			).fail(function () {
				$(".js-conjure-select-spinner").hide();
				alert(conjure_params.texts.something_went_wrong);
			});
		});

		// Handle demo selection on PLUGINS step (new behavior for demo-specific plugins).
		$(document).on(
			"change",
			".js-conjure-demo-select-plugins",
			function () {
				var selectedIndex = $(this).val();

				if (!selectedIndex) {
					return;
				}

				// Save the selection and reload to show filtered plugins.
				$.post(
					conjure_params.ajaxurl,
					{
						action: "conjure_update_selected_import_data_info",
						wpnonce: conjure_params.wpnonce,
						selected_index: selectedIndex,
					},
					function (response) {
						if (response.success) {
							// Reload the page to show filtered plugins.
							window.location.reload();
						} else {
							alert(conjure_params.texts.something_went_wrong);
						}
					}
				).fail(function () {
					alert(conjure_params.texts.something_went_wrong);
				});
			}
		);
	}

	function ChildTheme() {
		var body = $(".conjure__body");
		var complete,
			notice = $("#child-theme-text");

		function ajax_callback(r) {
			if (typeof r.done !== "undefined") {
				setTimeout(function () {
					notice.addClass("lead");
				}, 0);
				setTimeout(function () {
					notice.addClass("success");
					notice.html(r.message);
				}, 600);

				complete();
			} else {
				notice.addClass("lead error");
				notice.html(r.error);
			}
		}

		function do_ajax() {
			jQuery
				.post(
					conjure_params.ajaxurl,
					{
						action: "conjure_child_theme",
						wpnonce: conjure_params.wpnonce,
					},
					ajax_callback
				)
				.fail(ajax_callback);
		}

		return {
			init: function (btn) {
				complete = function () {
					setTimeout(function () {
						$(".conjure__body").addClass("js--finished");
					}, 1500);

					body.removeClass(drawer_opened);

					setTimeout(function () {
						$(".conjure__body").addClass("exiting");
					}, 3500);

					setTimeout(function () {
						window.location.href = btn.href;
					}, 4000);
				};
				do_ajax();
			},
		};
	}

	function ActivateLicense() {
		var body = $(".conjure__body");
		var wrapper = $(".conjure__content--license-key");
		var complete,
			notice = $("#license-text");

		function ajax_callback(r) {
			if (typeof r.success !== "undefined" && r.success) {
				notice.siblings(".error-message").remove();
				setTimeout(function () {
					notice.addClass("lead");
				}, 0);
				setTimeout(function () {
					notice.addClass("success");
					notice.html(r.message);
				}, 600);
				complete();
			} else {
				$(".js-conjure-license-activate-button")
					.removeClass("conjure__button--loading")
					.data("done-loading", "no");
				notice.siblings(".error-message").remove();
				wrapper.addClass("has-error");
				notice.html(r.message);
				notice.siblings(".error-message").addClass("lead error");
			}
		}

		function do_ajax() {
			wrapper.removeClass("has-error");

			jQuery
				.post(
					conjure_params.ajaxurl,
					{
						action: "conjure_activate_license",
						wpnonce: conjure_params.wpnonce,
						license_key: $(".js-license-key").val(),
					},
					ajax_callback
				)
				.fail(ajax_callback);
		}

		return {
			init: function (btn) {
				complete = function () {
					setTimeout(function () {
						$(".conjure__body").addClass("js--finished");
					}, 1500);

					body.removeClass(drawer_opened);

					setTimeout(function () {
						$(".conjure__body").addClass("exiting");
					}, 3500);

					setTimeout(function () {
						window.location.href = btn.href;
					}, 4000);
				};
				do_ajax();
			},
		};
	}

	function PluginManager() {
		var body = $(".conjure__body");
		var complete;
		var items_completed = 0;
		var current_item = "";
		var $current_node;
		var current_item_hash = "";

		function ajax_callback(response) {
			var currentSpan = $current_node.find("label");
			if (
				typeof response === "object" &&
				typeof response.message !== "undefined"
			) {
				currentSpan
					.removeClass("installing success error")
					.addClass(response.message.toLowerCase());

				// Check if ALL plugins are complete (server says we're done).
				if (
					typeof response.completed !== "undefined" &&
					response.completed
				) {
					// Mark current as done and complete the step.
					if ($current_node && !$current_node.data("done_item")) {
						items_completed++;
						$current_node.data("done_item", 1);
					}
					complete();
				}
				// The plugin is done (installed, updated and activated).
				else if (typeof response.done != "undefined" && response.done) {
					// CRITICAL: Mark this plugin as DONE before moving to next to prevent loops.
					if ($current_node && !$current_node.data("done_item")) {
						items_completed++;
						$current_node.data("done_item", 1);
					}
					find_next();
				} else if (typeof response.url != "undefined") {
					// We have an ajax url action to perform.
					if (response.hash == current_item_hash) {
						currentSpan
							.removeClass("installing success")
							.addClass("error");
						find_next();
					} else {
						current_item_hash = response.hash;
						jQuery
							.post(response.url, response, ajax_callback)
							.fail(ajax_callback);
					}
				} else {
					// Error processing this plugin.
					find_next();
				}
			} else {
				// Unknown response format, move on anyway to prevent infinite loops.
				if ($current_node && !$current_node.data("done_item")) {
					items_completed++;
					$current_node.data("done_item", 1);
				}
				find_next();
			}
		}

		function process_current() {
			if (current_item) {
				// Check for checkbox (recommended plugins) or hidden input (required plugins)
				var $check = $current_node.find("input:checkbox");
				var $hidden = $current_node.find("input[type=hidden]");

				// Install if: checkbox is checked OR hidden input exists (required plugin)
				if (
					($check.length > 0 && $check.is(":checked")) ||
					$hidden.length > 0
				) {
					// Use custom installer
					jQuery
						.post(
							conjure_params.ajaxurl,
							{
								action: "conjure_install_plugin",
								wpnonce: conjure_params.wpnonce,
								slug: current_item,
							},
							ajax_callback
						)
						.fail(ajax_callback);
				} else {
					$current_node.addClass("skipping");
					setTimeout(find_next, 300);
				}
			}
		}

		function find_next() {
			if ($current_node) {
				if (!$current_node.data("done_item")) {
					items_completed++;
					$current_node.data("done_item", 1);
				}
				$current_node.find(".spinner").css("visibility", "hidden");
			}
			// Only select plugin items (not headers), and exclude already active plugins
			var $li = $(
				".conjure__drawer--install-plugins li[data-slug]:not(.plugin-active)"
			);
			$li.each(function () {
				var $item = $(this);

				if ($item.data("done_item")) {
					return true;
				}

				current_item = $item.data("slug");
				$current_node = $item;
				process_current();
				return false;
			});
			if (items_completed >= $li.length) {
				// finished all plugins!
				complete();
			}
		}

		return {
			init: function (btn) {
				$(".conjure__drawer--install-plugins").addClass("installing");
				$(".conjure__drawer--install-plugins")
					.find("input")
					.prop("disabled", true);
				complete = function () {
					setTimeout(function () {
						$(".conjure__body").addClass("js--finished");
					}, 1000);

					body.removeClass(drawer_opened);

					setTimeout(function () {
						$(".conjure__body").addClass("exiting");
					}, 3000);

					setTimeout(function () {
						window.location.href = btn.href;
					}, 3500);
				};
				find_next();
			},
		};
	}
	function ContentManager() {
		var body = $(".conjure__body");
		var complete;
		var items_completed = 0;
		var current_item = "";
		var $current_node;
		var current_item_hash = "";
		var current_content_import_items = 1;
		var total_content_import_items = 0;
		var progress_bar_interval;

		function ajax_callback(response) {
			var currentSpan = $current_node.find("label");
			if (
				typeof response == "object" &&
				typeof response.message !== "undefined"
			) {
				currentSpan.addClass(response.message.toLowerCase());

				if (
					typeof response.num_of_imported_posts !== "undefined" &&
					0 < total_content_import_items
				) {
					current_content_import_items =
						"all" === response.num_of_imported_posts
							? total_content_import_items
							: response.num_of_imported_posts;
					update_progress_bar();
				}

				if (typeof response.url !== "undefined") {
					// we have an ajax url action to perform.
					if (response.hash === current_item_hash) {
						currentSpan.addClass("status--failed");
						find_next();
					} else {
						current_item_hash = response.hash;

						// Fix the undefined selected_index issue on new AJAX calls.
						if (typeof response.selected_index === "undefined") {
							response.selected_index =
								$(".js-conjure-demo-import-select").val() || 0;
						}

						jQuery
							.post(response.url, response, ajax_callback)
							.fail(ajax_callback); // Recursion.
					}
				} else if (typeof response.done !== "undefined") {
					// Finished processing this plugin, move onto next.
					find_next();
				} else {
					// Error processing this plugin.
					find_next();
				}
			} else {
				console.log(response);
				// Error - try again with next plugin.
				currentSpan.addClass("status--error");
				find_next();
			}
		}

		function process_current() {
			if (current_item) {
				var $check = $current_node.find("input:checkbox");
				if ($check.is(":checked")) {
					jQuery
						.post(
							conjure_params.ajaxurl,
							{
								action: "conjure_content",
								wpnonce: conjure_params.wpnonce,
								content: current_item,
								selected_index:
									$(".js-conjure-demo-import-select").val() ||
									0,
							},
							ajax_callback
						)
						.fail(ajax_callback);
				} else {
					$current_node.addClass("skipping");
					setTimeout(find_next, 300);
				}
			}
		}

		function find_next() {
			var do_next = false;
			if ($current_node) {
				if (!$current_node.data("done_item")) {
					items_completed++;
					$current_node.data("done_item", 1);
				}
				$current_node.find(".spinner").css("visibility", "hidden");
			}
			var $items = $(".conjure__drawer--import-content__list-item");
			var $enabled_items = $(
				".conjure__drawer--import-content__list-item input:checked"
			);
			$items.each(function () {
				if (current_item == "" || do_next) {
					current_item = $(this).data("content");
					$current_node = $(this);
					process_current();
					do_next = false;
				} else if ($(this).data("content") == current_item) {
					do_next = true;
				}
			});
			if (items_completed >= $items.length) {
				complete();
			}
		}

		function init_content_import_progress_bar() {
			if (
				!$(
					".conjure__drawer--import-content__list-item .checkbox-content"
				).is(":checked")
			) {
				return false;
			}

			jQuery.post(
				conjure_params.ajaxurl,
				{
					action: "conjure_get_total_content_import_items",
					wpnonce: conjure_params.wpnonce,
					selected_index:
						$(".js-conjure-demo-import-select").val() || 0,
				},
				function (response) {
					total_content_import_items = response.data;

					if (0 < total_content_import_items) {
						update_progress_bar();

						// Change the value of the progress bar constantly for a small amount (0,2% per sec), to improve UX.
						progress_bar_interval = setInterval(function () {
							current_content_import_items =
								current_content_import_items +
								total_content_import_items / 500;
							update_progress_bar();
						}, 1000);
					}
				}
			);
		}

		function valBetween(v, min, max) {
			return Math.min(max, Math.max(min, v));
		}

		function update_progress_bar() {
			$(".js-conjure-progress-bar").css(
				"width",
				(current_content_import_items / total_content_import_items) *
					100 +
					"%"
			);

			var $percentage = valBetween(
				(current_content_import_items / total_content_import_items) *
					100,
				0,
				99
			);

			$(".js-conjure-progress-bar-percentage").html(
				Math.round($percentage) + "%"
			);

			if (
				1 ===
				current_content_import_items / total_content_import_items
			) {
				clearInterval(progress_bar_interval);
			}
		}

		return {
			init: function (btn) {
				$(".conjure__drawer--import-content").addClass("installing");
				$(".conjure__drawer--import-content")
					.find("input")
					.prop("disabled", true);
				complete = function () {
					$.post(conjure_params.ajaxurl, {
						action: "conjure_import_finished",
						wpnonce: conjure_params.wpnonce,
						selected_index:
							$(".js-conjure-demo-import-select").val() || 0,
					});

					setTimeout(function () {
						$(".js-conjure-progress-bar-percentage").html("100%");
					}, 100);

					setTimeout(function () {
						body.removeClass(drawer_opened);
					}, 500);

					setTimeout(function () {
						$(".conjure__body").addClass("js--finished");
					}, 1500);

					setTimeout(function () {
						$(".conjure__body").addClass("exiting");
					}, 3400);

					setTimeout(function () {
						window.location.href = btn.href;
					}, 4000);
				};
				init_content_import_progress_bar();
				find_next();
			},
		};
	}

	function init_server_health_dropdown() {
		var serverHealthHeader = document.getElementById(
			"server-health-header"
		);
		var serverHealthInfo = document.getElementById("server-health-info");

		if (serverHealthHeader && serverHealthInfo) {
			serverHealthHeader.addEventListener("click", function () {
				serverHealthInfo.classList.toggle("open");
			});
		}
	}

	function init_health_telemetry_live_checks() {
		var healthTelemetry = document.querySelector(
			".conjure__drawer--health-telemetry"
		);

		if (!healthTelemetry) {
			return;
		}

		var checkInterval = null;
		var checkIntervalMs = 30000; // Check every 30 seconds

		function updateHealthMetrics() {
			if (!conjure_params || !conjure_params.ajaxurl || !conjure_params.wpnonce) {
				return;
			}

			jQuery.post(
				conjure_params.ajaxurl,
				{
					action: "conjure_get_health_metrics",
					wpnonce: conjure_params.wpnonce,
				},
				function (response) {
					if (response.success && response.data) {
						var metrics = response.data;

						// Update memory metric
						var memoryEl = healthTelemetry.querySelector(
							".health-metric-value-memory"
						);
						if (memoryEl && metrics.memory_limit) {
							var memoryHtml = metrics.memory_limit.formatted;
							if (!metrics.memory_limit.meets_req) {
								memoryHtml +=
									' <span class="metric-warning">(Min: ' +
									metrics.memory_limit.min_required +
									"MB)</span>";
							}
							memoryEl.innerHTML = memoryHtml;
							memoryEl.setAttribute(
								"data-current",
								metrics.memory_limit.value
							);
						}

						// Update execution time metric
						var executionEl = healthTelemetry.querySelector(
							".health-metric-value-execution"
						);
						if (executionEl && metrics.max_execution) {
							var executionHtml = metrics.max_execution.formatted;
							if (!metrics.max_execution.meets_req) {
								executionHtml +=
									' <span class="metric-warning">(Min: ' +
									metrics.max_execution.min_required +
									"s)</span>";
							}
							executionEl.innerHTML = executionHtml;
							executionEl.setAttribute(
								"data-current",
								metrics.max_execution.value
							);
						}

						// Update MySQL version
						var mysqlEl = healthTelemetry.querySelector(
							".health-metric-value-mysql"
						);
						if (mysqlEl && metrics.mysql_version) {
							mysqlEl.textContent = metrics.mysql_version;
						}

						// Update health status
						var meetsRequirements =
							metrics.meets_requirements === true ||
							metrics.meets_requirements === "1";
						healthTelemetry.setAttribute(
							"data-health-status",
							meetsRequirements ? "healthy" : "warning"
						);

						var healthIndicator = healthTelemetry.querySelector(
							".health-indicator"
						);
						if (healthIndicator) {
							healthIndicator.className =
								"health-indicator health-indicator--" +
								(meetsRequirements ? "healthy" : "warning");
						}

						var statusText = healthTelemetry.querySelector(
							".health-status-text"
						);
						if (statusText) {
							statusText.textContent = meetsRequirements
								? "Healthy"
								: "Needs Attention";
						}

						// Update remediation links
						var remediationSection = healthTelemetry.querySelector(
							".health-remediation"
						);
						if (remediationSection) {
							if (
								!metrics.remediation_links ||
								metrics.remediation_links.length === 0
							) {
								remediationSection.style.display = "none";
							} else {
								remediationSection.style.display = "block";
								var linksList = remediationSection.querySelector(
									".health-remediation-links"
								);
								if (linksList) {
									linksList.innerHTML = "";
									metrics.remediation_links.forEach(function (
										link
									) {
										var li = document.createElement("li");
										var a = document.createElement("a");
										a.href = link.url;
										a.target = "_blank";
										a.rel = "noopener noreferrer";
										a.className = "health-remediation-link";
										a.setAttribute(
											"data-bottleneck",
											link.type
										);
										a.textContent = link.title;
										var externalSpan = document.createElement(
											"span"
										);
										externalSpan.className =
											"health-link-external";
										externalSpan.textContent = "↗";
										a.appendChild(externalSpan);
										li.appendChild(a);
										linksList.appendChild(li);
									});
								}
							}
						}

						// Pulse animation to indicate live update
						var pulseEl = healthTelemetry.querySelector(
							".health-pulse"
						);
						if (pulseEl) {
							pulseEl.classList.add("pulse-active");
							setTimeout(function () {
								pulseEl.classList.remove("pulse-active");
							}, 500);
						}
					}
				}
			).fail(function () {
				// Silently fail - don't interrupt user experience
			});
		}

		// Initial check
		updateHealthMetrics();

		// Set up periodic checks
		checkInterval = setInterval(updateHealthMetrics, checkIntervalMs);

		// Stop checks when drawer is closed (optional optimization)
		var drawer = document.querySelector(
			".conjure__drawer--import-content"
		);
		if (drawer) {
			var observer = new MutationObserver(function (mutations) {
				mutations.forEach(function (mutation) {
					if (
						mutation.type === "attributes" &&
						mutation.attributeName === "class"
					) {
						var isOpen = drawer.classList.contains(
							"conjure__drawer--open"
						);
						if (!isOpen && checkInterval) {
							clearInterval(checkInterval);
							checkInterval = null;
						} else if (
							isOpen &&
							!checkInterval &&
							document.contains(healthTelemetry)
						) {
							checkInterval = setInterval(
								updateHealthMetrics,
								checkIntervalMs
							);
						}
					}
				});
			});

			observer.observe(drawer, {
				attributes: true,
				attributeFilter: ["class"],
			});
		}

		// Clean up on page unload
		window.addEventListener("beforeunload", function () {
			if (checkInterval) {
				clearInterval(checkInterval);
			}
		});
	}

	function set_upload_item_expanded(checkbox, forcedState) {
		if (!checkbox) {
			return;
		}

		var item = checkbox.closest(".conjure__drawer--upload__item");
		if (!item) {
			return;
		}

		var shouldExpand =
			typeof forcedState === "boolean" ? forcedState : checkbox.checked;

		if (shouldExpand) {
			item.classList.add("conjure__drawer--upload__item--expanded");
		} else {
			item.classList.remove("conjure__drawer--upload__item--expanded");
		}
	}

	function init_file_uploads() {
		var uploadZones = document.querySelectorAll(".conjure__upload-zone");

		if (uploadZones.length === 0) {
			return; // No upload zones, exit early
		}

		// Handle checkbox change to toggle upload zone visibility
		var checkboxes = document.querySelectorAll(".js-conjure-upload-checkbox");
		checkboxes.forEach(function (checkbox) {
			// Ensure initial state matches checkbox value.
			set_upload_item_expanded(checkbox);

			checkbox.addEventListener("change", function () {
				set_upload_item_expanded(checkbox);
			});
		});

		// Handle label click to toggle upload zone when checkbox is disabled
		var labels = document.querySelectorAll(".conjure__upload-label");
		labels.forEach(function (label) {
			label.addEventListener("click", function (e) {
				var checkboxId = label.getAttribute("for");
				var checkbox = document.getElementById(checkboxId);
				var item = label.closest(".conjure__drawer--upload__item");

				// If checkbox is disabled, manually toggle the upload zone
				if (checkbox.disabled) {
					e.preventDefault();
					item.classList.toggle("conjure__drawer--upload__item--expanded");
				}
			});
		});

		// Handle click to open WordPress media uploader
		uploadZones.forEach(function (zone) {
			var fileType = zone.getAttribute("data-type");
			var acceptedTypes = zone.getAttribute("data-accept");

			zone.addEventListener("click", function (e) {
				// Don't open uploader if clicking remove button
				if (e.target.closest(".conjure__remove-file")) {
					return;
				}

				// Don't open if file already uploaded
				if (zone.classList.contains("has-file")) {
					return;
				}

				e.preventDefault();

				// Create WordPress media uploader
				var uploader = wp.media({
					title: "Select " + fileType + " file",
					button: {
						text: "Use this file",
					},
					multiple: false,
				});

				// When file is selected
				uploader.on("select", function () {
					var attachment = uploader
						.state()
						.get("selection")
						.first()
						.toJSON();

					// Validate file type before uploading
					if (
						!validate_file_type(attachment, acceptedTypes, fileType)
					) {
						return;
					}

					// Get the file from WordPress
					fetch_and_upload_attachment(attachment, fileType);
				});

				// Open the uploader
				uploader.open();
			});
		});

		// Handle file removal
		document.addEventListener("click", function (e) {
			if (e.target.closest(".conjure__remove-file")) {
				e.preventDefault();
				e.stopPropagation();
				var btn = e.target.closest(".conjure__remove-file");
				var fileType = btn.getAttribute("data-type");
				remove_file(fileType);
			}
		});
	}

	function validate_file_type(attachment, acceptedTypes, fileType) {
		if (!attachment || !attachment.filename) {
			show_error_message(fileType, "Invalid file selected.");
			return false;
		}

		var filename = attachment.filename.toLowerCase();
		var acceptedTypesArray = acceptedTypes.split(",").map(function (type) {
			return type.trim();
		});

		var isValid = acceptedTypesArray.some(function (type) {
			return filename.endsWith(type);
		});

		if (!isValid) {
			var acceptedTypesText = acceptedTypesArray.join(", ");
			show_error_message(
				fileType,
				"Invalid file type. Please select a file with one of these extensions: " +
					acceptedTypesText
			);
			return false;
		}

		return true;
	}

	function show_error_message(fileType, message) {
		var zone = document.querySelector(
			'.conjure__upload-zone[data-type="' + fileType + '"]'
		);

		if (!zone) return;

		// Remove any existing error messages
		var existingError = zone.querySelector(".conjure__upload-error");
		if (existingError) {
			existingError.remove();
		}

		// Add error class to zone
		zone.classList.add("conjure__upload-zone--error");

		// Create and show error message
		var errorDiv = document.createElement("div");
		errorDiv.className = "conjure__upload-error";
		errorDiv.textContent = message;

		var prompt = zone.querySelector(".conjure__upload-prompt");
		if (prompt) {
			prompt.after(errorDiv);
		}

		// Remove error after 5 seconds
		setTimeout(function () {
			errorDiv.style.opacity = "0";
			errorDiv.style.transition = "opacity 0.3s";
			setTimeout(function () {
				errorDiv.remove();
			}, 300);
			zone.classList.remove("conjure__upload-zone--error");
		}, 5000);
	}

	function fetch_and_upload_attachment(attachment, fileType) {
		var zone = document.querySelector(
			'.conjure__upload-zone[data-type="' + fileType + '"]'
		);
		var progress = zone.querySelector(".conjure__upload-progress");
		var prompt = zone.querySelector(".conjure__upload-prompt");
		var success = zone.querySelector(".conjure__upload-success");

		// Show progress
		prompt.style.display = "none";
		success.style.display = "none";
		progress.style.display = "block";

		// Send attachment ID to server to process
		var formData = new URLSearchParams();
		formData.append("action", "conjure_upload_from_media");
		formData.append("attachment_id", attachment.id);
		formData.append("file_type", fileType);
		formData.append("wpnonce", conjure_params.wpnonce);

		fetch(conjure_params.ajaxurl, {
			method: "POST",
			headers: {
				"Content-Type": "application/x-www-form-urlencoded",
			},
			body: formData.toString(),
		})
			.then(function (res) {
				return res.json();
			})
			.then(function (response) {
				if (response.success) {
					// Update UI to show success
					zone.classList.add("has-file");
					progress.style.display = "none";

					// Update file info
					zone.querySelector(".conjure__file-name").textContent =
						response.data.filename;
					zone.querySelector(".conjure__file-size").textContent =
						response.data.size;
					success.style.display = "flex";
					var removeButton = zone.querySelector(
						".conjure__remove-file"
					);
					if (removeButton) {
						removeButton.style.display = "inline-flex";
					}

					// Enable checkbox
					var checkbox = document.getElementById(
						"default_content_" + fileType
					);
					checkbox.disabled = false;
					checkbox.checked = true;
					set_upload_item_expanded(checkbox, true);
				} else {
					// Show error
					progress.style.display = "none";
					prompt.style.display = "flex";
					show_error_message(
						fileType,
						response.data.message ||
							conjure_params.texts.something_went_wrong
					);
				}
			})
			.catch(function (error) {
				progress.style.display = "none";
				prompt.style.display = "flex";
				show_error_message(
					fileType,
					"Upload failed: " +
						(error || conjure_params.texts.something_went_wrong)
				);
			});
	}

	function upload_file(file, fileType) {
		var zone = document.querySelector(
			'.conjure__upload-zone[data-type="' + fileType + '"]'
		);
		var progress = zone.querySelector(".conjure__upload-progress");
		var prompt = zone.querySelector(".conjure__upload-prompt");
		var success = zone.querySelector(".conjure__upload-success");

		// Show progress
		prompt.style.display = "none";
		success.style.display = "none";
		progress.style.display = "block";

		// Create form data
		var formData = new FormData();
		formData.append("file", file);
		formData.append("file_type", fileType);
		formData.append("action", "conjure_upload_file");
		formData.append("wpnonce", conjure_params.wpnonce);

		// Upload file
		fetch(conjure_params.ajaxurl, {
			method: "POST",
			body: formData,
		})
			.then(function (res) {
				return res.json();
			})
			.then(function (response) {
				if (response.success) {
					// Update UI to show success
					zone.classList.add("has-file");
					progress.style.display = "none";

					// Update file info
					zone.querySelector(".conjure__file-name").textContent =
						response.data.filename;
					zone.querySelector(".conjure__file-size").textContent =
						response.data.size;
					success.style.display = "flex";
					var removeButton = zone.querySelector(
						".conjure__remove-file"
					);
					if (removeButton) {
						removeButton.style.display = "inline-flex";
					}

					// Enable checkbox
					var checkbox = document.getElementById(
						"default_content_" + fileType
					);
					checkbox.disabled = false;
					checkbox.checked = true;
					set_upload_item_expanded(checkbox, true);

					// Clear file input
					var fileInput = zone.querySelector(".conjure__file-input");
					if (fileInput) fileInput.value = "";
				} else {
					// Show error
					progress.style.display = "none";
					prompt.style.display = "flex";
					show_error_message(
						fileType,
						response.data.message ||
							conjure_params.texts.something_went_wrong
					);
				}
			})
			.catch(function (error) {
				progress.style.display = "none";
				prompt.style.display = "flex";
				show_error_message(
					fileType,
					"Upload failed: " +
						(error || conjure_params.texts.something_went_wrong)
				);
			});
	}

	function remove_file(fileType) {
		var zone = document.querySelector(
			'.conjure__upload-zone[data-type="' + fileType + '"]'
		);
		var prompt = zone.querySelector(".conjure__upload-prompt");
		var success = zone.querySelector(".conjure__upload-success");

		var formData = new URLSearchParams();
		formData.append("action", "conjure_delete_uploaded_file");
		formData.append("file_type", fileType);
		formData.append("wpnonce", conjure_params.wpnonce);

		fetch(conjure_params.ajaxurl, {
			method: "POST",
			headers: {
				"Content-Type": "application/x-www-form-urlencoded",
			},
			body: formData.toString(),
		})
			.then(function (res) {
				return res.json();
			})
			.then(function (response) {
				if (response.success) {
					// Update UI
					zone.classList.remove("has-file");
					success.style.display = "none";
					prompt.style.display = "flex";
					var removeButton = zone.querySelector(
						".conjure__remove-file"
					);
					if (removeButton) {
						removeButton.style.display = "none";
					}
					var fileName = zone.querySelector(".conjure__file-name");
					if (fileName) {
						fileName.textContent = "";
					}
					var fileSize = zone.querySelector(".conjure__file-size");
					if (fileSize) {
						fileSize.textContent = "";
					}

					// Disable and uncheck checkbox
					var checkbox = document.getElementById(
						"default_content_" + fileType
					);
					if (!checkbox) {
						return;
					}

					if (checkbox.dataset.manualUpload === "1") {
						checkbox.disabled = true;
						checkbox.checked = false;
						set_upload_item_expanded(checkbox, false);
					} else {
						set_upload_item_expanded(checkbox, checkbox.checked);
					}
				} else {
					show_error_message(
						fileType,
						response.data.message ||
							conjure_params.texts.something_went_wrong
					);
				}
			})
			.catch(function (error) {
				show_error_message(
					fileType,
					"Failed to remove file: " +
						(error || conjure_params.texts.something_went_wrong)
				);
			});
	}

	function conjure_loading_button(btn) {
		var $button = jQuery(btn);

		if ($button.data("done-loading") == "yes") {
			return false;
		}

		var completed = false;

		var _modifier =
			$button.is("input") || $button.is("button") ? "val" : "text";

		$button.data("done-loading", "yes");

		$button.addClass("conjure__button--loading");
		$button.prop("disabled", true);

		return {
			done: function () {
				completed = true;
				$button.prop("disabled", false);
			},
		};
	}

	return {
		init: function () {
			t = this;
			$(window_loaded);
		},
		callback: function (func) {
			console.log(func);
			console.log(this);
		},
	};
})(jQuery);

Conjure.init();
