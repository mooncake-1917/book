var side_bool = document.getElementById("side-bool")
// 添加触摸事件支持
side_bool.addEventListener('touchstart', function(e) {
    e.preventDefault();
    let side_bool_ico = document.getElementById("side-bool-ico");
    if (side_bool_ico.getAttribute("fill") === "#d5d5d5") 
        side_bool_ico.setAttribute("fill", "#bababa");
    else if (side_bool_ico.getAttribute("fill") === "#eac67a") 
        side_bool_ico.setAttribute("fill", "#fec89c");
});

side_bool.addEventListener('touchend', function(e) {
    e.preventDefault();
    let side = document.getElementById("side");
    let side_bool_ico = document.getElementById("side-bool-ico");
    
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

// 为列表项添加触摸支持
function addTouchSupport() {
    let md_items = document.getElementsByClassName("md-items");
    let mddir = document.getElementsByClassName("md-dir");
    
    // 目录项触摸支持
    for (let i = 0; i < mddir.length; i++) {
        mddir[i].addEventListener('touchstart', function(e) {
            this.style.backgroundColor = '#fff4';
        });
        
        mddir[i].addEventListener('touchend', function(e) {
            this.style.backgroundColor = '';
            this.click();
        });
    }
    
    // 文件项触摸支持
    for (let i = 0; i < md_items.length; i++) {
        md_items[i].addEventListener('touchstart', function(e) {
            this.style.backgroundColor = '#fff4';
        });
        
        md_items[i].addEventListener('touchend', function(e) {
            this.style.backgroundColor = '';
            this.click();
        });
    }
}

// 页面加载完成后初始化触摸支持
document.addEventListener('DOMContentLoaded', function() {
    addTouchSupport();
});

// 当内容动态更新后重新初始化触摸支持
function reinitTouchSupport() {
    setTimeout(addTouchSupport, 100);
}

var pathStart
if (window.location.pathname != "/" && window.location.pathname != "/files/") {
	if (window.location.pathname.split("/")[1] == "files") pathStart = "/files/"
	else pathStart = "/"
	let md_items = document.getElementsByClassName("md-items")
	let dir_name = document.getElementsByClassName("md-dir fuc")[0].innerHTML
	for (let j = 0;j < md_items.length;j++) md_items[j].onclick = function() {
		if (window.location.pathname.split("/")[1] == "files");
		else {
			let md_name = md_items[j].innerHTML
			window.location.href = pathStart + dir_name + "/" + md_name
		}
	}
} else {
	pathStart = window.location.pathname
}

var markdown_items = document.getElementById("items")
var mddir = document.getElementsByClassName("md-dir")
var items_num = NaN
var index_main = document.getElementById("main")
for (let i = 0;i < mddir.length;i++) mddir[i].onclick = function() {
	let side = document.getElementById("side")
	let dir_name = mddir[i].innerHTML

	for (let j = 0;j < mddir.length;j++) if (j != i) mddir[j].classList.remove("fuc")
	mddir[i].classList.add("fuc")
	let httpRequest = new XMLHttpRequest()
	httpRequest.onreadystatechange = function() {
		if (this.readyState === 4 && this.status === 200) {
			markdown_items.innerHTML = this.responseText.replaceAll(/\.md/g, "")
			let md_items = document.getElementsByClassName("md-items")
			for (let j = 0;j < md_items.length;j++) md_items[j].onclick = function() {
				if (window.location.pathname.split("/")[1] == "files");
				else {
					let md_name = md_items[j].innerHTML
					window.location.href = pathStart + dir_name + "/" + md_name
				}
			}
		}
	}
	httpRequest.open('POST', "/TOOLS/GET_ITEMS.php", true)
	httpRequest.setRequestHeader("Content-type", "application/x-www-form-urlencoded")
	httpRequest.send("DirName=" + dir_name + "&PathStart=" + pathStart)
	
	if (window.innerWidth > 720 && window.innerWidth <= 1080) {
		if (items_num == i) {
			items_num = NaN
			side.classList.remove("true")
		} else {
			items_num = i
			side.classList.add("true")
		}
	}
}

var head_title = document.getElementsByTagName("h1")[0]
head_title.onclick = function() {
	window.location.href = pathStart
}