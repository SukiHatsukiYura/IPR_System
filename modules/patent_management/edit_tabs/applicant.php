<?php
if (!isset($_GET['patent_id']) || intval($_GET['patent_id']) <= 0) {
    echo '<div style="color:#f44336;text-align:center;margin:40px;">未指定专利ID</div>';
    exit;
}
$patent_id = intval($_GET['patent_id']);
echo '<div style="padding:40px;text-align:center;">这里是著录项目tab内容，专利ID：' . $patent_id . '</div>';
