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
        $trademark_id = intval($_POST['trademark_id']);
        $incoming_type = $_POST['incoming_type'] ?? '';
        $incoming_date = $_POST['incoming_date'] ?? '';
        $official_number = $_POST['official_number'] ?? '';
        $deadline = $_POST['deadline'] ?? null;
        $urgency = $_POST['urgency'] ?? '普通';
        $status = $_POST['status'] ?? '待处理';
        $handler_id = intval($_POST['handler_id']) ?: null;
        $content = $_POST['content'] ?? '';
        $remarks = $_POST['remarks'] ?? '';
        $creator_id = $_SESSION['user_id'];

        // 验证必填字段
        if (!$trademark_id) {
            echo json_encode(['success' => false, 'msg' => '请选择商标案件']);
            exit;
        }
        if (!$incoming_type) {
            echo json_encode(['success' => false, 'msg' => '请选择来文类型']);
            exit;
        }
        if (!$incoming_date) {
            echo json_encode(['success' => false, 'msg' => '请选择来文日期']);
            exit;
        }

        // 验证商标案件是否存在
        $check_stmt = $pdo->prepare("SELECT id FROM trademark_case_info WHERE id = ?");
        $check_stmt->execute([$trademark_id]);
        if (!$check_stmt->fetch()) {
            echo json_encode(['success' => false, 'msg' => '商标案件不存在']);
            exit;
        }

        // 处理空期限日期
        if (empty($deadline)) {
            $deadline = null;
        }

        $sql = "INSERT INTO trademark_incoming_document 
                (trademark_case_info_id, incoming_type, incoming_date, official_number, deadline, 
                 urgency, status, handler_id, content, remarks, creator_id) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

        $stmt = $pdo->prepare($sql);
        $result = $stmt->execute([
            $trademark_id,
            $incoming_type,
            $incoming_date,
            $official_number,
            $deadline,
            $urgency,
            $status,
            $handler_id,
            $content,
            $remarks,
            $creator_id
        ]);

        if ($result) {
            echo json_encode(['success' => true, 'msg' => '新增成功']);
        } else {
            echo json_encode(['success' => false, 'msg' => '新增失败']);
        }
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'msg' => '数据库异常: ' . $e->getMessage()]);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'msg' => '系统异常: ' . $e->getMessage()]);
    }
    exit;
}

// 处理更新来文记录
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_incoming') {
    header('Content-Type: application/json');

    try {
        $id = intval($_POST['id']);
        $trademark_id = intval($_POST['trademark_id']);
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
        if (!$id) {
            echo json_encode(['success' => false, 'msg' => '缺少记录ID']);
            exit;
        }
        if (!$trademark_id) {
            echo json_encode(['success' => false, 'msg' => '请选择商标案件']);
            exit;
        }
        if (!$incoming_type) {
            echo json_encode(['success' => false, 'msg' => '请选择来文类型']);
            exit;
        }
        if (!$incoming_date) {
            echo json_encode(['success' => false, 'msg' => '请选择来文日期']);
            exit;
        }

        // 验证记录是否存在
        $check_stmt = $pdo->prepare("SELECT id FROM trademark_incoming_document WHERE id = ?");
        $check_stmt->execute([$id]);
        if (!$check_stmt->fetch()) {
            echo json_encode(['success' => false, 'msg' => '来文记录不存在']);
            exit;
        }

        // 验证商标案件是否存在
        $check_stmt = $pdo->prepare("SELECT id FROM trademark_case_info WHERE id = ?");
        $check_stmt->execute([$trademark_id]);
        if (!$check_stmt->fetch()) {
            echo json_encode(['success' => false, 'msg' => '商标案件不存在']);
            exit;
        }

        // 处理空期限日期
        if (empty($deadline)) {
            $deadline = null;
        }

        $sql = "UPDATE trademark_incoming_document SET 
                trademark_case_info_id = ?, incoming_type = ?, incoming_date = ?, 
                official_number = ?, deadline = ?, urgency = ?, status = ?, 
                handler_id = ?, content = ?, remarks = ?, updated_at = NOW() 
                WHERE id = ?";

        $stmt = $pdo->prepare($sql);
        $result = $stmt->execute([
            $trademark_id,
            $incoming_type,
            $incoming_date,
            $official_number,
            $deadline,
            $urgency,
            $status,
            $handler_id,
            $content,
            $remarks,
            $id
        ]);

        if ($result) {
            echo json_encode(['success' => true, 'msg' => '更新成功']);
        } else {
            echo json_encode(['success' => false, 'msg' => '更新失败']);
        }
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'msg' => '数据库异常: ' . $e->getMessage()]);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'msg' => '系统异常: ' . $e->getMessage()]);
    }
    exit;
}

// 处理删除来文记录
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete_incoming') {
    header('Content-Type: application/json');

    try {
        $id = intval($_POST['id']);

        if (!$id) {
            echo json_encode(['success' => false, 'msg' => '缺少记录ID']);
            exit;
        }

        // 验证记录是否存在
        $check_stmt = $pdo->prepare("SELECT id FROM trademark_incoming_document WHERE id = ?");
        $check_stmt->execute([$id]);
        if (!$check_stmt->fetch()) {
            echo json_encode(['success' => false, 'msg' => '来文记录不存在']);
            exit;
        }

        // 删除记录（外键约束会自动删除相关文件记录）
        $stmt = $pdo->prepare("DELETE FROM trademark_incoming_document WHERE id = ?");
        $result = $stmt->execute([$id]);

        if ($result) {
            echo json_encode(['success' => true, 'msg' => '删除成功']);
        } else {
            echo json_encode(['success' => false, 'msg' => '删除失败']);
        }
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'msg' => '数据库异常: ' . $e->getMessage()]);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'msg' => '系统异常: ' . $e->getMessage()]);
    }
    exit;
}

// 处理获取来文记录详情
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['action']) && $_GET['action'] === 'get_incoming_detail') {
    header('Content-Type: application/json');

    try {
        $id = intval($_GET['id']);

        if (!$id) {
            echo json_encode(['success' => false, 'msg' => '缺少记录ID']);
            exit;
        }

        $sql = "SELECT d.*, t.case_code, t.case_name 
                FROM trademark_incoming_document d
                LEFT JOIN trademark_case_info t ON d.trademark_case_info_id = t.id
                WHERE d.id = ?";

        $stmt = $pdo->prepare($sql);
        $stmt->execute([$id]);
        $data = $stmt->fetch();

        if ($data) {
            echo json_encode(['success' => true, 'data' => $data]);
        } else {
            echo json_encode(['success' => false, 'msg' => '记录不存在']);
        }
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'msg' => '数据库异常: ' . $e->getMessage()]);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'msg' => '系统异常: ' . $e->getMessage()]);
    }
    exit;
}

// 如果没有匹配的操作，返回错误
header('Content-Type: application/json');
echo json_encode(['success' => false, 'msg' => '无效的操作']);
