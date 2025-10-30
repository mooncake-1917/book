<!DOCTYPE html>
<html>
	<head>
		<meta charset="utf-8">
		<meta name="viewport" content="width=device-width,initial-scale=1,minimum-scale=1,maximum-scale=1,user-scalable=no" />
		<title>实用技术知识库</title>
		<script src="/PDFObject/pdfobject.min.js?v=<?php echo time(); ?>"></script>
		<link rel="stylesheet" type="text/css" href="/STATIC/CSS/CORE.css?v=<?php echo time(); ?>" />
		<link rel="stylesheet" type="text/css" href="/STATIC/CSS/ROOT.css?v=<?php echo time(); ?>" />
		<link rel="stylesheet" type="text/css" href="/STATIC/CSS/DARK.css?v=<?php echo time(); ?>" />
	</head>
	<body <?php if (isset($_COOKIE["theme"]) && $_COOKIE["theme"] == "dark") echo 'class="dark"' ?>>
		<div id="top">
			<h1>实用技术知识库</h1>
			<div id="side-bool">
				<svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 24 24"><path fill="<?php if (isset($_COOKIE["theme"]) && $_COOKIE['theme'] == 'dark') echo '#eac67a'; else echo '#d5d5d5' ?>" d="M10 15h10v2H10zm-6 4h16v2H4zm6-8h10v2H10zm0-4h10v2H10zM4 3h16v2H4zm0 5v8l4-4z" id="side-bool-ico" /></svg>
			</div>
			<form id="search" action="/RELEASES/search-file.php" method="post" onsubmit="return onSubmit()">
				<input id="search-key" name="search-key" type="input" placeholder="请输入您要搜索的内容" />
				<button id="search-submit" type="submit">
					<svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 48 48"><g style="transition: all 1s;" fill="none" stroke="<?php if (isset($_COOKIE["theme"]) && $_COOKIE['theme'] == 'dark') echo '#eac67a'; else echo '#d5d5d5' ?>" stroke-linejoin="round" stroke-width="4" id="search-ico" ><path d="M21 38c9.389 0 17-7.611 17-17S30.389 4 21 4S4 11.611 4 21s7.611 17 17 17Z"/><path stroke-linecap="round" d="M26.657 14.343A7.98 7.98 0 0 0 21 12a7.98 7.98 0 0 0-5.657 2.343m17.879 18.879l8.485 8.485"/></g></svg>
				</button>
			</form>
			<input type="checkbox" id="theme" <?php if (isset($_COOKIE["theme"]) && $_COOKIE["theme"] == "dark") echo 'checked' ?> />
		</div>
		<div id="index">
			<div id="side">
				<ul id="tree">
					<?php
						$file_path = urldecode($_SERVER["REQUEST_URI"]);
						if ($file_path != "/files/") {
							$file_path_list = explode("/", $file_path);
							$file_dir = $file_path_list[2];
							$file_pdf = $file_path_list[3];
						} else {
							$file_dir = "";
							$file_pdf = "";
						}
						$TREE_N = 1;
                        $FILE_TREE = dir("./PDFS");
						$dirs = array();
                        while (($FILE_DIR = $FILE_TREE->read()) !== false) {
							if ($TREE_N != 1 && $TREE_N != 2) $dirs[] = $FILE_DIR;
							$TREE_N += 1;
						}
						foreach ($dirs as $key => $value) $names[$key] = iconv("UTF-8", "GBK", $value);
						array_multisort($names, SORT_ASC, $dirs);
                        $FILE_TREE->close();
						for ($i = 0; $i < count($dirs); $i++)
							if ($dirs[$i] == $file_dir) echo "<li class='md-dir fuc'>" . $dirs[$i] . "</li>";
							else echo "<li class='md-dir'>" . $dirs[$i] . "</li>";
                    ?>
					<li onclick="window.location.href = '/'">文档中心</li>
				</ul>
				<ul id="items">
					<?php
						if ($file_dir != "") {
							$items = array();
							$items_list = "";
							$DIR_N = 1;
							$FILE_DIR = opendir("PDFS/" . $file_dir);
							$names = array();
							while (($MD_ITEMS = readdir($FILE_DIR)) !== false) {
								if ($DIR_N != 1 && $DIR_N != 2) $items[] = $MD_ITEMS;
								$DIR_N += 1;
							}
							closedir($FILE_DIR);
							foreach ($items as $key => $value) $names[$key] = iconv("UTF-8", "GBK", $value);
							array_multisort($names, SORT_ASC, $items);
							for ($i = 0; $i < count($items); $i++) 
								if ($items[$i] == $file_pdf || $items[$i] == $file_pdf . ".pdf") $items_list .= "<li class='md-items fuc'><a href=\"/PDFS/" . $file_dir . "/" . $items[$i] . "\" download>↓&nbsp;" . $items[$i] . "</a></li>";
								else $items_list .= "<li class='md-items'>" . $items[$i] . "</li>";
							echo $items_list !== "" ? $items_list : "<li>尚未补充</li>";
						}
					?>
				</ul>
			</div>
			<div id="main">
				<?php
					if ($file_dir != "") echo '<div id="ifPdf"></div>';
					else {
						echo "<h1>实用技术知识库-文件中心</h1>";
						echo "<p>本站旨在为用户提供一些技术知识内容的电子书籍，帮助用户解决一部分问题。</p>";
						echo "<p>本站不享有文件的版权，文件作者保留一切权利，如有侵权，本站将迅速删除。</p>";
					}
				?>
			</div>
		</div>
		<script tyepe="text/javascript">
			PDFObject.embed("/PDFS/<?php echo $file_dir . "/" . $file_pdf; ?>", "#ifPdf")
		</script>
		<script type="text/javascript" src="/STATIC/JS/THEME.js?v=<?php echo time(); ?>"></script>
		<script type="text/javascript" src="/STATIC/JS/SIDE.js?v=<?php echo time(); ?>"></script>
		<script type="text/javascript" src="/STATIC/JS/MARKDOWNSEARCH.js?v=<?php echo time(); ?>"></script>
	</body>
</html>