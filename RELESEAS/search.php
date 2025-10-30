<!DOCTYPE html>
<html>
	<head>
		<meta charset="utf-8">
		<meta name="viewport" content="width=device-width,initial-scale=1,minimum-scale=1,maximum-scale=1,user-scalable=no" />
		<title>搜索知识-<?php echo htmlspecialchars($_POST["search-key"] ?? '') ?></title>
		<link rel="stylesheet" type="text/css" href="/STATIC/CSS/SEARCH.css?v=<?php echo time(); ?>" />
	</head>
	<body <?php if (isset($_COOKIE["theme"]) && $_COOKIE["theme"] == "dark") echo 'class="dark"' ?>>
		<div id="search">
			<input id="search-text" type="text" value="<?php echo htmlspecialchars($_POST["search-key"] ?? '') ?>" placeholder="请输入您要搜索的内容" />
			<button id="search-submit">搜索</button>
			<input type="checkbox" id="theme" <?php if (isset($_COOKIE["theme"]) && $_COOKIE["theme"] == "dark") echo 'checked' ?> />
        </div>
		<div id="main">
            <?php
                // 安全检查
                if (!isset($_POST["search-key"]) || empty(trim($_POST["search-key"]))) {
                    echo "<div class=\"search-item\">";
                    echo "<div class=\"search-item-title\">请输入搜索关键词</div>";
                    echo "</div>";
                    exit;
                }
                
                $search_key = trim($_POST["search-key"]);
                $md_tree = dir("../MARKDOWN");
                $md_dirs = array();
                $TREE_N = 1;
                $search_bool = false;
                while (($md_dirs_ = $md_tree->read()) !== false) {
                    if ($TREE_N != 1 && $TREE_N != 2) $md_dirs[] = $md_dirs_;
                    $TREE_N += 1;
                }
                for ($i = 0;$i < count($md_dirs);$i++) {
                    $md_dir = opendir("../MARKDOWN/" . $md_dirs[$i]);
                    $md_items = array();
                    $DIR_N = 1;
                    while (($md_dir_ = readdir($md_dir)) !== false) {
                        if ($DIR_N != 1 && $DIR_N != 2) $md_items[] = $md_dir_;
                        $DIR_N += 1;
                    }
                    for ($j = 0;$j < count($md_items);$j++) {
                        $md_item = $md_items[$j];
                        $md_inner = file_get_contents("../MARKDOWN/" . $md_dirs[$i] . "/" . $md_item);
                        if (strpos($md_inner, $search_key) !== false) {
                            $search_bool = true;
                            echo "<div class=\"search-item\">";
                            echo "<div class=\"search-item-title\">" . htmlspecialchars($md_item) . "</div>";
                            echo "<div class=\"search-item-link\">&gt;&gt;&nbsp;" . htmlspecialchars($md_dirs[$i]) . "&nbsp;&gt;&nbsp;" . htmlspecialchars($md_item) . "</div>";
                            echo "<div class=\"search-item-text\">";
                            echo str_replace($search_key, "<span class=\"search-item-text-key\">" . htmlspecialchars($search_key) . "</span>", search_fun($md_inner, $search_key));
                            echo "</div>";
                            echo "<div class=\"search-item-tree\">" . htmlspecialchars($md_dirs[$i]) . "</div>";
                            echo "</div>";
                        }
                    }
                }
                $md_tree->close();
                closedir($md_dir);
                if (!$search_bool) {
                    echo "<div class=\"search-item\">";
                    echo "<div class=\"search-item-title\">搜索不到相关内容！</div>";
                    echo "</div>";
                }
                function search_fun($innder, $key) {
                    $key_pos = strpos($innder, $key);
                    $key_after_pos = 0;
                    while ($key_pos !== false) {
                        if ($key_pos >= $key_after_pos) {
                            $key_after_pos = strpos($innder, "\n", $key_pos) ? strpos($innder, "\n", $key_pos) : strlen($innder);
                            if (strpos(substr($innder, 0, $key_pos), "\n") !== false) {
                                $key_before = substr($innder, 0, $key_pos);
                                $key_before_pos = strrpos($key_before, "\n");
                                $echo_text = substr($innder, $key_before_pos, $key_after_pos - $key_before_pos);
                                echo "<p>";
                                echo str_replace($key, "<span class=\"search-item-text-key\">" . htmlspecialchars($key) . "</span>", $echo_text);
                                echo "</p>";
                            } else {
                                $echo_text = substr($innder, 0, $key_after_pos);
                                echo "<p>";
                                echo str_replace($key, "<span class=\"search-item-text-key\">" . htmlspecialchars($key) . "</span>", $echo_text);
                                echo "</p>";
                            }
                        }
                        $key_pos = strpos($innder, $key, $key_pos + strlen($key));
                    }
                }
            ?>
		</div>
		<script type="text/javascript" src="/STATIC/JS/THEME.js?v=<?php echo time(); ?>"></script>
        <script type="text/javascript" src="/STATIC/JS/SEARCH.js?v=<?php echo time(); ?>"></script>
	</body>
</html>