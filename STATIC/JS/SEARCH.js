var search_input = document.getElementById("search-text");
var search_button = document.getElementById("search-submit");
var search_title = document.getElementsByTagName("title")[0];
var index_main = document.getElementById("main");

var isPageSearch = window.location.pathname.indexOf("search-file.php") === -1;

function bindLinks() {
    var links = document.getElementsByClassName("search-item-link");
    for (var i = 0; i < links.length; i++) {
        (function(link) {
            if (link.tagName.toLowerCase() === 'a') return; // PDF 下载链接，保留默认行为
            link.onclick = function() {
                var text = link.innerText || link.textContent || "";
                var parts = text.replace(">> ", "").split(" > ");
                var dir = parts[0];
                var md = parts[1];
                if (md && isPageSearch) {
                    window.location.href = "/" + encodeURIComponent(dir) + "/" + encodeURIComponent(md);
                }
            };
        })(links[i]);
    }
}

bindLinks();

search_input.addEventListener("keyup", function(event) {
    event.preventDefault();
    if (event.keyCode === 13) search_button.click();
});

search_button.onclick = function() {
    var value = search_input.value;
    if (value === "" || value == null) {
        alert("搜索内容不能为空！");
    } else {
        var httpRequest = new XMLHttpRequest();
        httpRequest.onreadystatechange = function() {
            if (this.readyState === 4 && this.status === 200) {
                if (isPageSearch) {
                    search_title.textContent = "搜索知识-" + value;
                } else {
                    search_title.textContent = "搜索文件-" + value;
                }
                index_main.innerHTML = this.responseText;
                bindLinks();
            }
        };
        httpRequest.open('POST', "/TOOLS/GET_SEARCH.php", true);
        httpRequest.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
        httpRequest.send("search-key=" + encodeURIComponent(value) + "&search-type=" + (isPageSearch ? "page" : "file"));
    }
};
