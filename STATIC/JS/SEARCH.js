var search_input = document.getElementById("search-text")
var search_button = document.getElementById("search-submit")
var search_title = document.getElementsByTagName("title")[0]
var index_main = document.getElementById("main")
let search_item_link = document.getElementsByClassName("search-item-link")
for (let i = 0; i < search_item_link.length; i++) search_item_link[i].onclick = function() {
    let search_item_link_path = search_item_link[i].innerHTML.replace("&gt;&gt;&nbsp;").split("&nbsp;&gt;&nbsp;")
    let search_item_link_dir = search_item_link_path[0].replaceAll(undefined, "")
    let search_item_link_md = search_item_link_path[1]
    if (window.location.pathname == "/search/") window.location.href = "/" + search_item_link_dir + "/" + search_item_link_md
    // else window.location.href = "/files/" + search_item_link_dir + "/" + search_item_link_md
}
search_input.addEventListener("keyup", function(event) {
    event.preventDefault()
    if (event.keyCode === 13) search_button.click()
})
search_button.onclick = function() {
    if (search_input.value == "" || search_input.value == null) alert("搜索内容不能为空！")
    else {
        let httpRequest = new XMLHttpRequest()
        httpRequest.onreadystatechange = function() {
            if (this.readyState === 4 && this.status === 200) {
                if (window.location.pathname == "/search/") search_title.innerHTML = "搜索知识-" + search_input.value
                else search_title.innerHTML = "搜索文件-" + search_input.value
                index_main.innerHTML = this.responseText
                let search_item_link = document.getElementsByClassName("search-item-link")
                for (let i = 0; i < search_item_link.length; i++) search_item_link[i].onclick = function() {
                    let search_item_link_path = search_item_link[i].innerHTML.replace("&gt;&gt;&nbsp;").split("&nbsp;&gt;&nbsp;")
                    let search_item_link_dir = search_item_link_path[0].replaceAll(undefined, "")
                    let search_item_link_md = search_item_link_path[1]
                    if (window.location.pathname == "/search/") window.location.href = "/" + search_item_link_dir + "/" + search_item_link_md
                    // else window.location.href = "/files/" + search_item_link_dir + "/" + search_item_link_md
                }
            }
        }
        httpRequest.open('POST', "/TOOLS/GET_SEARCH.php", true)
        httpRequest.setRequestHeader("Content-type", "application/x-www-form-urlencoded")
        if (window.location.pathname == "/search/") httpRequest.send("search-key=" + search_input.value + "&search-type=page")
        else httpRequest.send("search-key=" + search_input.value + "&search-type=file")
    }
}