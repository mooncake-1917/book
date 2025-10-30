<?php
    $dir_name = $_POST["DirName"];
    $md_name = $_POST["MdName"];
    echo file_get_contents("../MARKDOWN/" . $dir_name . "/" . $md_name . ".md");
?>