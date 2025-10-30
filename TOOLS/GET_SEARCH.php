<?php
    $search_key = $_POST['search-key'];
    $search_type = $_POST['search-type'];
    if ($search_type == "page") $md_tree = dir("../MARKDOWN");
    else $md_tree = dir("../PDFS/");
    $md_dirs = array();
    $TREE_N = 1;
    $search_bool = false;
    $RETURN_TEXT = "";
    while (($md_dirs_ = $md_tree->read()) !== false) {
        if ($TREE_N != 1 && $TREE_N != 2) $md_dirs[] = $md_dirs_;
        $TREE_N += 1;
    }
    for ($i = 0;$i < count($md_dirs);$i++) {
        if ($search_type == "page") $md_dir = opendir("../MARKDOWN/" . $md_dirs[$i]);
        else $md_dir = opendir("../PDFS/" . $md_dirs[$i]);
        $md_items = array();
        $DIR_N = 1;
        while (($md_dir_ = readdir($md_dir)) !== false) {
            if ($DIR_N != 1 && $DIR_N != 2) $md_items[] = $md_dir_;
            $DIR_N += 1;
        }
        // 在search.php中修改文件过滤逻辑
        for ($j = 0;$j < count($md_items);$j++) {
            $md_item = $md_items[$j];
            // 只处理.md文件
            if (strpos($md_item, '.md') === false) {
                continue;
            }
            $md_inner = file_get_contents("../MARKDOWN/" . $md_dirs[$i] . "/" . $md_item);
            if ($search_type == "page") {
                $md_inner = file_get_contents("../MARKDOWN/" . $md_dirs[$i] . "/" . $md_item);
                if (strpos($md_inner, $search_key) !== false) {
                    $search_bool = true;

                    $RETURN_TEXT .= "<div class=\"search-item\">";
                    $RETURN_TEXT .= "<div class=\"search-item-title\">" . $md_item . "</div>";
                    $RETURN_TEXT .= "<div class=\"search-item-link\">&gt;&gt;&nbsp;" . $md_dirs[$i] . "&nbsp;&gt;&nbsp;" . $md_item . "</div>";
                    $RETURN_TEXT .= "<div class=\"search-item-text\">";
                    
                    $key_pos = strpos($md_inner, $search_key);
                    $key_after_pos = 0;
                    $text_list = "";
                    while ($key_pos !== false) {
                        if ($key_pos >= $key_after_pos) {
                            $key_after_pos = strpos($md_inner, "\n", $key_pos) ? strpos($md_inner, "\n", $key_pos) : strlen($md_inner);
                            if (strpos(substr($md_inner, 0, $key_pos), "\n") !== false) {
                                $key_before = substr($md_inner, 0, $key_pos);
                                $key_before_pos = strrpos($key_before, "\n");
                                $echo_text = substr($md_inner, $key_before_pos, $key_after_pos - $key_before_pos);
                                $text_list .= "<p>";
                                $text_list .= str_replace($search_key, "<span class=\"search-item-text-key\">" . $search_key . "</span>", $echo_text);
                                $text_list .= "</p>";
                            } else {
                                $echo_text = substr($md_inner, 0, $key_after_pos);
                                $text_list .= "<p>";
                                $text_list .= str_replace($search_key, "<span class=\"search-item-text-key\">" . $search_key . "</span>", $echo_text);
                                $text_list .= "</p>";
                            }
                        }
                        $key_pos = strpos($md_inner, $search_key, $key_pos + strlen($search_key));
                    }

                    $RETURN_TEXT .= str_replace($search_key, "<span class=\"search-item-text-key\">" . $search_key . "</span>", $text_list);
                    $RETURN_TEXT .= "</div>";
                    $RETURN_TEXT .= "<div class=\"search-item-tree\">" . $md_dirs[$i] . "</div>";
                    $RETURN_TEXT .= "</div>";
                }
            } else if (strpos($md_item, $search_key) !== false) {
                $search_bool = true;

                $RETURN_TEXT .= "<div class=\"search-item\">";
                $RETURN_TEXT .= "<div class=\"search-item-title\">" . str_replace($search_key, "<span class=\"search-item-text-key\">" . $search_key . "</span>", $md_item) . "</div>";
                $RETURN_TEXT .= "<br />";
                $RETURN_TEXT .= "<div class=\"search-item-link\"><a href=\"../PDFS/" . $md_dirs[$i] . "/" . $md_item . "\" download>↓&nbsp;" . $md_dirs[$i] . "&nbsp;&gt;&nbsp;" . $md_item . "</a></div>";
                $RETURN_TEXT .= "<div class=\"search-item-tree\">" . $md_dirs[$i] . "</div>";
                $RETURN_TEXT .= "</div>";
            }
        }
    }
    
    if ($search_bool == false) {
        $RETURN_TEXT .= "<div class=\"search-item\">";
        $RETURN_TEXT .= "<div class=\"search-item-title\">搜索不到相关内容！</div>";
        $RETURN_TEXT .= "</div>";
    }
    
    $md_tree->close();
    closedir($md_dir);
    echo $RETURN_TEXT;
?>