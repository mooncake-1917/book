var side_bool = document.getElementById("side-bool");

side_bool.addEventListener('touchstart', function(e) {
    e.preventDefault();
    var side_bool_ico = document.getElementById("side-bool-ico");
    if (side_bool_ico.getAttribute("fill") === "#d5d5d5")
        side_bool_ico.setAttribute("fill", "#bababa");
    else if (side_bool_ico.getAttribute("fill") === "#eac67a")
        side_bool_ico.setAttribute("fill", "#fec89c");
});

side_bool.addEventListener('touchend', function(e) {
    e.preventDefault();
    var side = document.getElementById("side");
    var side_bool_ico = document.getElementById("side-bool-ico");

    if (side_bool_ico.getAttribute("fill") === "#bababa") {
        side.classList.toggle("true");
        side_bool.innerHTML = side.classList.contains("true") ?
            '<svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 24 24"><path fill="#d5d5d5" d="M4 7h10v2H4zm0-4h16v2H4zm0 8h10v2H4zm0 4h10v2H4zm0 4h16v2H4zm16-3V8l-4 4z" id="side-bool-ico" /></svg>' :
            '<svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 24 24"><path fill="#d5d5d5" d="M10 15h10v2H10zm-6 4h16v2H4zm6-8h10v2H10zm0-4h10v2H10zM4 3h16v2H4zm0 5v8l4-4z" id="side-bool-ico" /></svg>';
    } else if (side_bool_ico.getAttribute("fill") === "#fec89c") {
        side.classList.toggle("true");
        side_bool.innerHTML = side.classList.contains("true") ?
            '<svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 24 24"><path fill="#eac67a" d="M4 7h10v2H4zm0-4h16v2H4zm0 8h10v2H4zm0 4h10v2H4zm0 4h16v2H4zm16-3V8l-4 4z" id="side-bool-ico" /></svg>' :
            '<svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 24 24"><path fill="#eac67a" d="M10 15h10v2H10zm-6 4h16v2H4zm6-8h10v2H10zm0-4h10v2H10zM4 3h16v2H4zm0 5v8l4-4z" id="side-bool-ico" /></svg>';
    }
});

function addTouchSupport() {
    var md_items = document.getElementsByClassName("md-items");
    var mddir = document.getElementsByClassName("md-dir");

    for (var i = 0; i < mddir.length; i++) {
        mddir[i].addEventListener('touchstart', function() {
            this.style.backgroundColor = '#fff4';
        });
        mddir[i].addEventListener('touchend', function() {
            this.style.backgroundColor = '';
            this.click();
        });
    }

    for (var j = 0; j < md_items.length; j++) {
        md_items[j].addEventListener('touchstart', function() {
            this.style.backgroundColor = '#fff4';
        });
        md_items[j].addEventListener('touchend', function() {
            this.style.backgroundColor = '';
            this.click();
        });
    }
}

document.addEventListener('DOMContentLoaded', function() {
    addTouchSupport();
});

function reinitTouchSupport() {
    setTimeout(addTouchSupport, 100);
}

var pathStart;
var isFilesPage = window.location.pathname.split("/")[1] === "files";
if (window.location.pathname !== "/" && window.location.pathname !== "/files/") {
    pathStart = isFilesPage ? "/files/" : "/";
    var dir_name = document.getElementsByClassName("md-dir fuc")[0].innerHTML;
    var md_items = document.getElementsByClassName("md-items");
    for (var j = 0; j < md_items.length; j++) {
        md_items[j].onclick = function() {
            if (isFilesPage) return;
            var md_name = this.innerHTML;
            window.location.href = pathStart + encodeURIComponent(dir_name) + "/" + encodeURIComponent(md_name);
        };
    }
} else {
    pathStart = window.location.pathname;
}

var markdown_items = document.getElementById("items");
var mddir = document.getElementsByClassName("md-dir");
var items_num = NaN;

for (var i = 0; i < mddir.length; i++) {
    mddir[i].onclick = function() {
        var side = document.getElementById("side");
        var dir_name = this.innerHTML;

        for (var k = 0; k < mddir.length; k++) {
            if (mddir[k] !== this) mddir[k].classList.remove("fuc");
        }
        this.classList.add("fuc");

        var httpRequest = new XMLHttpRequest();
        httpRequest.onreadystatechange = function() {
            if (this.readyState === 4 && this.status === 200) {
                markdown_items.innerHTML = this.responseText.replace(/\.md/g, "");
                var md_items = document.getElementsByClassName("md-items");
                for (var j = 0; j < md_items.length; j++) {
                    md_items[j].onclick = function() {
                        if (isFilesPage) return;
                        var md_name = this.innerHTML;
                        window.location.href = pathStart + encodeURIComponent(dir_name) + "/" + encodeURIComponent(md_name);
                    };
                }
            }
        };
        httpRequest.open('POST', "/TOOLS/GET_ITEMS.php", true);
        httpRequest.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
        httpRequest.send("DirName=" + encodeURIComponent(dir_name) + "&PathStart=" + encodeURIComponent(pathStart));

        if (window.innerWidth > 720 && window.innerWidth <= 1080) {
            if (items_num === i) {
                items_num = NaN;
                side.classList.remove("true");
            } else {
                items_num = i;
                side.classList.add("true");
            }
        }
    };
}

var head_title = document.getElementsByTagName("h1")[0];
head_title.onclick = function() {
    window.location.href = pathStart;
};
