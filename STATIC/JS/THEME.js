var theme = document.getElementById("theme")
theme.onclick = function() {
	let search_ico = document.getElementById("search-ico")
	let side_bool_ico = document.getElementById("side-bool-ico")
	if (document.getElementsByTagName("body")[0].classList.toggle("dark")) {
		document.cookie = "theme=dark;" + "path=/;" +"expires=" + new Date().getDate + 30
		
		search_ico.setAttribute("stroke", "#eac67a")
		side_bool_ico.setAttribute("fill", "#eac67a")
	} else {
		document.cookie = "theme=light;" + "path=/;" + "expires=" + new Date().getDate + 30

		search_ico.setAttribute("stroke", "#d5d5d5")
		side_bool_ico.setAttribute("fill", "#d5d5d5")
	}
}