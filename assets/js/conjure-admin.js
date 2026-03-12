import "../css/conjure-admin.css";

jQuery(function ($) {
	const $sortable = $(".js-conjure-step-sortable");
	const $activationButtons = $(".js-conjure-activation-button");
	const $navLinks = $(".js-conjure-admin-nav-link");
	const $panels = $(".js-conjure-admin-panel");

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
			$checkbox.prop("checked", !$checkbox.is(":checked")).trigger("change");
			updateActivationButton($button, $checkbox);

			const $form = $checkbox.closest("form");
			if ($form.length) {
				$form.trigger("submit");
			}
		});

		$checkbox.on("change", function () {
			updateActivationButton($button, $checkbox);
		});
	});

	if ($navLinks.length && $panels.length) {
		const $activeTabField = $("#conjurewp-active-tab");

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
		};

		$navLinks.on("click", function () {
			const targetId = $(this).data("panel");

			if (targetId) {
				setActivePanel(targetId);
			}
		});

		const hashTarget = window.location.hash.replace("#", "");

		if (hashTarget && document.getElementById(hashTarget)) {
			setActivePanel(hashTarget);
		} else {
			setActivePanel("conjure-overview");
		}
	}

	if ($sortable.length) {
		$sortable.sortable({
			axis: "y",
			handle: ".js-conjure-step-handle",
			placeholder: "conjure-step-sortable__placeholder",
			forcePlaceholderSize: true,
		});
	}
});
