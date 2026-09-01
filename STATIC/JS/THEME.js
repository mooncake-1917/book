var theme = document.getElementById("theme");
theme.onclick = function() {
    var search_ico = document.getElementById("search-ico");
    var side_bool_ico = document.getElementById("side-bool-ico");
    var dark = document.getElementsByTagName("body")[0].classList.toggle("dark");
    var expires = new Date(Date.now() + 30 * 24 * 60 * 60 * 1000).toUTCString();
    if (dark) {
        document.cookie = "theme=dark;path=/;expires=" + expires;
        if (search_ico) search_ico.setAttribute("stroke", "#eac67a");
        if (side_bool_ico) side_bool_ico.setAttribute("fill", "#eac67a");
    } else {
        document.cookie = "theme=light;path=/;expires=" + expires;
        if (search_ico) search_ico.setAttribute("stroke", "#d5d5d5");
        if (side_bool_ico) side_bool_ico.setAttribute("fill", "#d5d5d5");
    }
};
