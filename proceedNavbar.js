
// Desktop dropdown toggle
function toggleDropdown() {
	const menu = document.getElementById("categoryMenu");
	const arrow = document.getElementById("arrowIcon");

	menu.classList.toggle("show");
	arrow.classList.toggle("rotate");
}

// Close desktop dropdown when category selected
document.querySelectorAll("#categoryMenu a").forEach((item) => {
	item.addEventListener("click", function () {
		document.getElementById("categoryMenu").classList.remove("show");
		document.getElementById("arrowIcon").classList.remove("rotate");
	});
});

// Mobile sidebar functions
function openSidebar() {
	document.getElementById("mobileSidebar").classList.add("open");
	document.getElementById("overlay").classList.add("show");
	document.body.style.overflow = "hidden";
}

function closeSidebar() {
	document.getElementById("mobileSidebar").classList.remove("open");
	document.getElementById("overlay").classList.remove("show");
	document.body.style.overflow = "auto";
}

// Mobile category toggle
function toggleMobileCategories() {
	const list = document.getElementById("mobileCategoryList");
	const arrow = document.getElementById("mobileCategoryArrow");

	list.classList.toggle("show");
	arrow.classList.toggle("rotate");
}

// Close sidebar when category selected
document.querySelectorAll("#mobileCategoryList a").forEach((item) => {
	item.addEventListener("click", function () {
		closeSidebar();
	});
});

// Close sidebar on escape key
document.addEventListener("keydown", function (e) {
	if (e.key === "Escape") {
		closeSidebar();
	}
	
});

// Profile dropdown toggle
(function() {
	const dropdowns = document.querySelectorAll(".profile-dropdown-wrapper");
	dropdowns.forEach(wrapper => {
		const logo = wrapper.querySelector(".mt-logo");
		const menu = wrapper.querySelector(".profile-dropdown-menu");
		
		logo.addEventListener("click", function(e) {
			e.stopPropagation();
			menu.classList.toggle("show");
		});
	});

	// Close dropdown when clicking outside
	document.addEventListener("click", function() {
		document.querySelectorAll(".profile-dropdown-menu").forEach(menu => {
			menu.classList.remove("show");
		});
	});
})();
