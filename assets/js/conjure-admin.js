import "../css/conjure-admin.css";

jQuery(function ($) {
	const $sortable = $(".js-conjure-step-sortable");
	const $activationButtons = $(".js-conjure-activation-button");
	const $navLinks = $(".js-conjure-admin-nav-link");
	const $panels = $(".js-conjure-admin-panel");
	const $orderSaveStatus = $(".js-conjure-order-save-status");
	const $wizardOrderList = $("#conjure-wizard-order-list");
	const $wizardStepCount = $(".js-conjure-wizard-step-count");

	const updateActivationButton = function ($button, $checkbox) {
		const isActive = $checkbox.is(":checked");
		const activateLabel = $button.data("activate-label");
		const deactivateLabel = $button.data("deactivate-label");

		$button
			.toggleClass("is-active", isActive)
			.toggleClass("is-inactive", !isActive)
			.attr("aria-pressed", isActive ? "true" : "false")
			.text(isActive ? deactivateLabel : activateLabel);
	};

	$activationButtons.each(function () {
		const $button = $(this);
		const targetId = $button.data("target");
		const $checkbox = $("#" + targetId);

		if (!$checkbox.length) {
			return;
		}

		updateActivationButton($button, $checkbox);

		$button.on("click", function () {
			if ($button.is(":disabled") || $checkbox.is(":disabled")) {
				return;
			}

			$checkbox.prop("checked", !$checkbox.is(":checked")).trigger("change");
			updateActivationButton($button, $checkbox);
		});

		$checkbox.on("change", function () {
			updateActivationButton($button, $checkbox);
		});
	});

	const $settingsForm = $("#conjure-admin-settings-form");

	if ($settingsForm.length) {
		$settingsForm.on("submit", function () {
			const $activePanel = $panels.filter(".is-active").first();

			if ($activePanel.length) {
				const $activeTabField = $("#conjurewp-active-tab");

				if ($activeTabField.length) {
					$activeTabField.val($activePanel.attr("id"));
				}
			}
		});
	}

	if ($navLinks.length && $panels.length) {
		const $activeTabField = $("#conjurewp-active-tab");

		const syncTabUrl = function (targetId) {
			const url = new URL(window.location.href);

			if (targetId === "conjure-overview") {
				url.searchParams.delete("conjurewp_tab");
			} else {
				url.searchParams.set("conjurewp_tab", targetId);
			}

			window.history.replaceState(null, "", url.toString());
		};

		const setActivePanel = function (targetId) {
			$navLinks.each(function () {
				const $link = $(this);
				const isActive = $link.data("panel") === targetId;

				$link
					.toggleClass("is-active", isActive)
					.attr("aria-selected", isActive ? "true" : "false")
					.attr("tabindex", isActive ? "0" : "-1");
			});

			$panels.each(function () {
				const $panel = $(this);
				const isActive = $panel.attr("id") === targetId;

				$panel
					.toggleClass("is-active", isActive)
					.prop("hidden", !isActive)
					.attr("aria-hidden", isActive ? "false" : "true");
			});

			if ($activeTabField.length) {
				$activeTabField.val(targetId);
			}

			syncTabUrl(targetId);
		};

		$navLinks.on("click", function () {
			const targetId = $(this).data("panel");

			if (targetId) {
				setActivePanel(targetId);
			}
		});

		const resolveInitialTab = function () {
			const params = new URLSearchParams(window.location.search);
			const queryTab = params.get("conjurewp_tab");
			const hashTarget = window.location.hash.replace("#", "");

			if (queryTab && document.getElementById(queryTab)) {
				return queryTab;
			}

			if (hashTarget && document.getElementById(hashTarget)) {
				return hashTarget;
			}

			const $activePanel = $panels.filter(".is-active").first();

			if ($activePanel.length) {
				return $activePanel.attr("id");
			}

			return "conjure-overview";
		};

		setActivePanel(resolveInitialTab());
	}

	const $connectorsPanel = $("#conjure-connectors");
	const connectorConfig = window.conjureAdminConnectors || {};
	let orderSaveTimer = null;
	let orderSaveRequest = null;

	const setOrderSaveStatus = function (state, message) {
		if (!$orderSaveStatus.length) {
			return;
		}

		$orderSaveStatus
			.removeClass("is-saving is-saved is-error")
			.text(message || "");

		if (state) {
			$orderSaveStatus.addClass(state);
		}
	};

	const collectStepOrder = function () {
		const order = [];

		$wizardOrderList.find('input[name="step_order[]"]').each(function () {
			const value = $(this).val();

			if (value) {
				order.push(value);
			}
		});

		return order;
	};

	const initWizardOrderSortable = function () {
		if (!$wizardOrderList.length || !$.fn.sortable) {
			return;
		}

		if ($wizardOrderList.hasClass("ui-sortable")) {
			$wizardOrderList.sortable("destroy");
		}

		$wizardOrderList.sortable({
			axis: "y",
			handle: ".js-conjure-step-handle",
			placeholder: "conjure-step-sortable__placeholder",
			forcePlaceholderSize: true,
			update: function () {
				if (!connectorConfig.ajaxurl || !connectorConfig.nonce) {
					return;
				}

				window.clearTimeout(orderSaveTimer);

				if (orderSaveRequest && orderSaveRequest.abort) {
					orderSaveRequest.abort();
				}

				setOrderSaveStatus("is-saving", connectorConfig.strings?.orderSaving || "Saving order...");

				orderSaveTimer = window.setTimeout(function () {
					orderSaveRequest = $.post(connectorConfig.ajaxurl, {
						action: "conjure_save_step_order",
						nonce: connectorConfig.nonce,
						step_order: collectStepOrder(),
					})
						.done(function (response) {
							if (!response || !response.success) {
								setOrderSaveStatus(
									"is-error",
									connectorConfig.strings?.orderSaveFailed || "Could not save wizard order."
								);
								return;
							}

							setOrderSaveStatus(
								"is-saved",
								connectorConfig.strings?.orderSaved || "Wizard order saved."
							);

							window.setTimeout(function () {
								setOrderSaveStatus("", "");
							}, 1800);
						})
						.fail(function () {
							setOrderSaveStatus(
								"is-error",
								connectorConfig.strings?.orderSaveFailed || "Could not save wizard order."
							);
						});
				}, 350);
			},
		});
	};

	const refreshWizardOrderList = function (html, stepCount) {
		if (!$wizardOrderList.length || !html) {
			return;
		}

		$wizardOrderList.html(html);
		initWizardOrderSortable();

		if (typeof stepCount !== "undefined" && $wizardStepCount.length) {
			$wizardStepCount.text(String(stepCount));
		}
	};

	const fetchWizardOrderList = function () {
		if (!connectorConfig.ajaxurl || !connectorConfig.nonce) {
			return;
		}

		$.post(connectorConfig.ajaxurl, {
			action: "conjure_get_wizard_order",
			nonce: connectorConfig.nonce,
		}).done(function (response) {
			if (!response || !response.success || !response.data) {
				return;
			}

			refreshWizardOrderList(response.data.html, response.data.wizard_step_count);
		});
	};

	initWizardOrderSortable();

	const parseConnectorField = function (fieldName) {
		if (!fieldName) {
			return null;
		}

		const enabledMatch = fieldName.match(/^connector_enabled\[(.+)\]$/);

		if (enabledMatch) {
			return {
				connectorId: enabledMatch[1],
				field: "enabled",
			};
		}

		const featureMatch = fieldName.match(/^connector_features\[(.+)\]\[(.+)\]$/);

		if (featureMatch) {
			return {
				connectorId: featureMatch[1],
				field: "feature",
				featureId: featureMatch[2],
			};
		}

		return null;
	};

	const getConnectorCard = function ($input) {
		return $input.closest(".conjure-admin-connector-card");
	};

	const setConnectorCardState = function ($card, state) {
		if (!$card.length) {
			return;
		}

		$card.removeClass("is-saving is-saved is-error");

		if (state) {
			$card.addClass(state);
		}
	};

	const revertConnectorInput = function ($input, previousValue) {
		$input.data("conjure-reverting", true);
		$input.prop("checked", previousValue);

		if ($input.hasClass("conjure-admin-activation-checkbox")) {
			const $button = $connectorsPanel.find(
				'.js-conjure-activation-button[data-target="' + $input.attr("id") + '"]'
			);

			if ($button.length) {
				updateActivationButton($button, $input);
			}
		}

		$input.removeData("conjure-reverting");
	};

	const saveConnectorSetting = function ($input, parsed) {
		if (!connectorConfig.ajaxurl || !connectorConfig.nonce) {
			return;
		}

		const $card = getConnectorCard($input);
		const previousValue = !$input.is(":checked");
		const requestData = {
			action: connectorConfig.action || "conjure_save_connector_setting",
			nonce: connectorConfig.nonce,
			connector_id: parsed.connectorId,
			field: parsed.field,
			value: $input.is(":checked") ? 1 : 0,
		};

		if ("feature" === parsed.field) {
			requestData.feature_id = parsed.featureId;
		}

		setConnectorCardState($card, "is-saving");
		$input.prop("disabled", true);

		$.post(connectorConfig.ajaxurl, requestData)
			.done(function (response) {
				if (!response || !response.success) {
					revertConnectorInput($input, previousValue);
					setConnectorCardState($card, "is-error");
					window.setTimeout(function () {
						setConnectorCardState($card, "");
					}, 2500);
					return;
				}

				setConnectorCardState($card, "is-saved");
				window.setTimeout(function () {
					setConnectorCardState($card, "");
				}, 1200);

				if (response.data && response.data.wizard_order_html) {
					refreshWizardOrderList(
						response.data.wizard_order_html,
						response.data.wizard_step_count
					);
				} else if ("enabled" === parsed.field || "feature" === parsed.field) {
					fetchWizardOrderList();
				}
			})
			.fail(function () {
				revertConnectorInput($input, previousValue);
				setConnectorCardState($card, "is-error");
				window.setTimeout(function () {
					setConnectorCardState($card, "");
				}, 2500);

				if (connectorConfig.strings && connectorConfig.strings.saveFailed) {
					window.console.error(connectorConfig.strings.saveFailed);
				}
			})
			.always(function () {
				$input.prop("disabled", false);
			});
	};

	if ($connectorsPanel.length && connectorConfig.ajaxurl) {
		$connectorsPanel.on(
			"change",
			"input.conjure-admin-activation-checkbox, input.conjure-admin-toggle__input",
			function () {
				const $input = $(this);

				if ($input.is(":disabled") || $input.data("conjure-reverting")) {
					return;
				}

				const parsed = parseConnectorField($input.attr("name"));

				if (!parsed) {
					return;
				}

				saveConnectorSetting($input, parsed);
			}
		);
	}
});
