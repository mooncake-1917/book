<!DOCTYPE html>
<html>
	<head>
		<meta charset="utf-8">
		<meta name="viewport" content="width=device-width,initial-scale=1,minimum-scale=1,maximum-scale=1,user-scalable=no" />
		<title>搜索文件-<?php echo htmlspecialchars($_POST["search-key"] ?? '') ?></title>
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
                $pdf_tree = dir("../PDFS");
                $pdf_dirs = array();
                $TREE_N = 1;
                $search_bool = false;
                while (($pdf_dirs_ = $pdf_tree->read()) !== false) {
                    if ($TREE_N != 1 && $TREE_N != 2) $pdf_dirs[] = $pdf_dirs_;
                    $TREE_N += 1;
                }
                for ($i = 0;$i < count($pdf_dirs);$i++) {
                    $pdf_dir = opendir("../PDFS/" . $pdf_dirs[$i]);
                    $pdf_items = array();
                    $DIR_N = 1;
                    while (($pdf_dir_ = readdir($pdf_dir)) !== false) {
                        if ($DIR_N != 1 && $DIR_N != 2) $pdf_items[] = $pdf_dir_;
                        $DIR_N += 1;
                    }
                    for ($j = 0;$j < count($pdf_items);$j++) {
                        $pdf_item = $pdf_items[$j];
                        if (strpos($pdf_item, $search_key) !== false) {
                            $search_bool = true;
                            echo "<div class=\"search-item\">";
                            echo "<div class=\"search-item-title\">" . str_replace($search_key, "<span class=\"search-item-text-key\">" . htmlspecialchars($search_key) . "</span>", htmlspecialchars($pdf_item)) . "</div>";
							echo "<br />";
                            echo "<div class=\"search-item-link\"><a href=\"../PDFS/" . htmlspecialchars($pdf_dirs[$i]) . "/" . htmlspecialchars($pdf_item) . "\" download>↓&nbsp;" . htmlspecialchars($pdf_dirs[$i]) . "&nbsp;&gt;&nbsp;" . htmlspecialchars($pdf_item) . "</a></div>";
                            echo "<div class=\"search-item-tree\">" . htmlspecialchars($pdf_dirs[$i]) . "</div>";
                            echo "</div>";
                        }
                    }
                }
                $pdf_tree->close();
                closedir($pdf_dir);
                if (!$search_bool) {
                    echo "<div class=\"search-item\">";
                    echo "<div class=\"search-item-title\">搜索不到相关内容！</div>";
                    echo "</div>";
                }
			?>
		</div>
		<script type="text/javascript" src="/STATIC/JS/THEME.js?v=<?php echo time(); ?>"></script>
        <script type="text/javascript" src="/STATIC/JS/SEARCH.js?v=<?php echo time(); ?>"></script>
	</body>
</html>