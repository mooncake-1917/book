function onSubmit() {
    let search_key = document.getElementById("search-key").value
    if (search_key == "" || search_key == null) {
        alert("搜索内容不能为空！")
        return false
    } else return true
}