<?php
include_once(__DIR__ . '/../../../database.php');
include_once(__DIR__ . '/../../../common/functions.php');
check_access_via_framework();
session_start();

if (!isset($_SESSION['user_id'])) {
    header('Location: /login.php');
    exit;
}

// 处理新增来文记录
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add_incoming') {
    header('Content-Type: application/json');

    try {
        $patent_id = intval($_POST['patent_id']);
        $incoming_type = $_POST['incoming_type'] ?? '';
        $incoming_date = $_POST['incoming_date'] ?? '';
        $official_number = $_POST['official_number'] ?? '';
        $deadline = $_POST['deadline'] ?? null;
        $urgency = $_POST['urgency'] ?? '普通';
        $status = $_POST['status'] ?? '待处理';
        $handler_id = intval($_POST['handler_id']) ?: null;
        $content = $_POST['content'] ?? '';
        $remarks = $_POST['remarks'] ?? '';

        // 验证必填字段
        if (!$patent_id || !$incoming_type || !$incoming_date) {
            throw new Exception('请填写必填字段');
        }

        // 验证专利是否存在
        $patent_stmt = $pdo->prepare("SELECT id FROM patent_case_info WHERE id = ?");
        $patent_stmt->execute([$patent_id]);
        if (!$patent_stmt->fetch()) {
            throw new Exception('指定的专利案件不存在');
        }

        // 插入来文记录
        $sql = "INSERT INTO patent_incoming_document (
            patent_case_info_id, incoming_type, incoming_date, official_number, 
            deadline, urgency, status, handler_id, content, remarks, 
            creator_id, created_at
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())";

        $stmt = $pdo->prepare($sql);
        $result = $stmt->execute([
            $patent_id,
            $incoming_type,
            $incoming_date,
            $official_number,
            $deadline ?: null,
            $urgency,
            $status,
            $handler_id,
            $content,
            $remarks,
            $_SESSION['user_id']
        ]);

        if ($result) {
            echo json_encode(['success' => true, 'msg' => '来文记录添加成功']);
        } else {
            throw new Exception('保存失败');
        }
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'msg' => $e->getMessage()]);
    }
    exit;
}

// 处理更新来文记录
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_incoming') {
    header('Content-Type: application/json');

    try {
        $id = intval($_POST['id']);
        $incoming_type = $_POST['incoming_type'] ?? '';
        $incoming_date = $_POST['incoming_date'] ?? '';
        $official_number = $_POST['official_number'] ?? '';
        $deadline = $_POST['deadline'] ?? null;
        $urgency = $_POST['urgency'] ?? '普通';
        $status = $_POST['status'] ?? '待处理';
        $handler_id = intval($_POST['handler_id']) ?: null;
        $content = $_POST['content'] ?? '';
        $remarks = $_POST['remarks'] ?? '';

        // 验证必填字段
        if (!$id || !$incoming_type || !$incoming_date) {
            throw new Exception('请填写必填字段');
        }

        // 更新来文记录
        $sql = "UPDATE patent_incoming_document SET 
            incoming_type = ?, incoming_date = ?, official_number = ?, 
            deadline = ?, urgency = ?, status = ?, handler_id = ?, 
            content = ?, remarks = ?, updated_at = NOW()
            WHERE id = ?";

        $stmt = $pdo->prepare($sql);
        $result = $stmt->execute([
            $incoming_type,
            $incoming_date,
            $official_number,
            $deadline ?: null,
            $urgency,
            $status,
            $handler_id,
            $content,
            $remarks,
            $id
        ]);

        if ($result) {
            echo json_encode(['success' => true, 'msg' => '来文记录更新成功']);
        } else {
            throw new Exception('更新失败');
        }
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'msg' => $e->getMessage()]);
    }
    exit;
}

// 处理删除来文记录
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete_incoming') {
    header('Content-Type: application/json');

    try {
        $id = intval($_POST['id']);
        if (!$id) {
            throw new Exception('无效的记录ID');
        }

        $stmt = $pdo->prepare("DELETE FROM patent_incoming_document WHERE id = ?");
        $result = $stmt->execute([$id]);

        if ($result) {
            echo json_encode(['success' => true, 'msg' => '来文记录删除成功']);
        } else {
            throw new Exception('删除失败');
        }
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'msg' => $e->getMessage()]);
    }
    exit;
}

// 处理获取来文详情请求
if (isset($_GET['action']) && $_GET['action'] === 'get_incoming_detail') {
    header('Content-Type: application/json');
    $id = intval($_GET['id']);

    if (!$id) {
        echo json_encode(['success' => false, 'msg' => '无效的记录ID']);
        exit;
    }

    try {
        $sql = "SELECT d.*, p.case_code, p.case_name 
                FROM patent_incoming_document d
                LEFT JOIN patent_case_info p ON d.patent_case_info_id = p.id
                WHERE d.id = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$id]);
        $document = $stmt->fetch();

        if ($document) {
            echo json_encode(['success' => true, 'data' => $document]);
        } else {
            echo json_encode(['success' => false, 'msg' => '记录不存在']);
        }
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'msg' => '获取数据失败']);
    }
    exit;
}

// 如果没有匹配的action，返回错误
http_response_code(400);
exit('无效的请求');
