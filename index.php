<!DOCTYPE html>
<html lang="zh_CN">
	<head>
		<meta charset="utf-8">
		<meta name="viewport" content="width=device-width,initial-scale=1,minimum-scale=1,maximum-scale=1,user-scalable=no" />
		<title>实用技术知识库</title>
		<link rel="stylesheet" type="text/css" href="/STATIC/CSS/ROOT.css?v=<?php echo time(); ?>" />
		<link rel="stylesheet" type="text/css" href="/STATIC/CSS/CORE.css?v=<?php echo time(); ?>" />
		<link rel="stylesheet" type="text/css" href="/STATIC/CSS/DARK.css?v=<?php echo time(); ?>" />
	</head>
	<body <?php if (isset($_COOKIE["theme"]) && $_COOKIE["theme"] == "dark") echo 'class="dark"' ?>>
		<div id="top">
			<h1>实用技术知识库</h1>
			<div id="side-bool">
				<svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 24 24"><path fill="<?php if (isset($_COOKIE["theme"]) && $_COOKIE['theme'] == 'dark') echo '#eac67a'; else echo '#d5d5d5' ?>" d="M10 15h10v2H10zm-6 4h16v2H4zm6-8h10v2H10zm0-4h10v2H10zM4 3h16v2H4zm0 5v8l4-4z" id="side-bool-ico" /></svg>
			</div>
			<form id="search" action="/RELEASES/search.php" method="post" onsubmit="return onSubmit()">
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
						$page_path = urldecode($_SERVER["REQUEST_URI"]);
						if ($page_path != "/") {
							$page_path_list = explode("/", $page_path);
							$page_dir = $page_path_list[1];
							$page_md = $page_path_list[2];
						} else {
							$page_dir = "";
							$page_md = "";
						}
						$TREE_N = 1;
                        $MD_TREE = dir("./MARKDOWN");
						$dirs = array();
                        while (($MD_DIR = $MD_TREE->read()) !== false) {
							// if ($TREE_N != 1 && $TREE_N != 2) 
							$dirs[] = $MD_DIR;
							$TREE_N += 1;
						}
						foreach ($dirs as $key => $value) $names[$key] = iconv("UTF-8", "GBK", $value);
						array_multisort($names, SORT_ASC, $dirs);
                        $MD_TREE->close();
						for ($i = 0; $i < count($dirs); $i++) {
							if ($dirs[$i] == "." || $dirs[$i] == "..") continue;
							if ($dirs[$i] == $page_dir) echo "<li class='md-dir fuc'>" . $dirs[$i] . "</li>";
							else echo "<li class='md-dir'>" . $dirs[$i] . "</li>";
						}
                    ?>
					<li onclick="window.location.href = '/files/'">文件中心</li>
				</ul>
				<ul id="items">
					<?php
						if ($page_dir != "") {
							$items = array();
							$items_list = "";
							$DIR_N = 1;
							$MD_DIR = opendir("MARKDOWN/" . $page_dir);
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
								$items_list .= "<li class='md-items'>" . $items[$i] . "</li>";
							}
							echo $items_list !== "" ? $items_list : "<li>尚未补充</li>";
						}
					?>
				</ul>
			</div>
			<div id="main">
				<?php
					
					// 启动会话并检查登录状态
					session_start();
					
					// 如果用户未登录，重定向到登录页面
					if (!isset($_SESSION['user_id'])) {
					    header('Location: login.php');
					    exit;
					}
					
					require 'TOOLS/Parsedown.php';
					if (strpos($page_md, ".md") === false) $page_md .= ".md";
					$Parsedown = new Parsedown();
					if ($page_dir == "" || $page_md == "") echo $Parsedown->text(file_get_contents("hello.md"));
					else echo $Parsedown->text(file_get_contents("MARKDOWN/" . $page_dir . "/" . $page_md));
				?>
			</div>
		</div>
		<script type="text/javascript" src="/STATIC/JS/THEME.js?v=<?php echo time(); ?>"></script>
		<script type="text/javascript" src="/STATIC/JS/SIDE.js?v=<?php echo time(); ?>"></script>
		<script type="text/javascript" src="/STATIC/JS/MARKDOWNSEARCH.js?v=<?php echo time(); ?>"></script>
	</body>
</html>
<div id="user-info">
    <span>欢迎，<?php echo htmlspecialchars($_SESSION['username']); ?></span>
    <a href="logout.php" onclick="return confirm('确定要退出登录吗？')">退出</a>
</div>