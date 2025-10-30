<?php
    $dir_name = $_POST["DirName"];
    $path_start = $_POST["PathStart"];
    $items = array();
    $items_list = "";
    $DIR_N = 1;
    if ($path_start == "/") $MD_DIR = opendir("../MARKDOWN/" . $dir_name);
    else $MD_DIR = opendir("../PDFS/" . $dir_name);
    $names = array();
    while (($MD_ITEMS = readdir($MD_DIR)) !== false) {
        // if ($DIR_N != 1 && $DIR_N != 2)
        $items[] = $MD_ITEMS;
        $DIR_N += 1;
    }
    closedir($MD_DIR);
    foreach ($items as $key => $value) $names[$key] = iconv("UTF-8", "GBK", $value);
    array_multisort($names, SORT_ASC, $items);
    for ($i = 0; $i < count($items); $i++) {
        if ($items[$i] == "." || $items[$i] == "..") continue;
        if ($path_start == "/")$items_list .= "<li class='md-items'>" . $items[$i] . "</li>";
        else $items_list .= "<li class='md-items'><a href=\"/PDFS/" . $dir_name . "/" . $items[$i] . "\" download>↓&nbsp;" . $items[$i] . "</a></li>";
    }
    echo $items_list !== "" ? $items_list : "<li>尚未补充</li>";
?>