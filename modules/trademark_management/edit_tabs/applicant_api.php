<?php
// 商标编辑-申请人API接口
include_once(__DIR__ . '/../../../database.php');
include_once(__DIR__ . '/../../../common/functions.php');
check_access_via_framework();
session_start();

if (!isset($_SESSION['user_id'])) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'msg' => '未登录或会话超时']);
    exit;
}

if (!isset($_GET['trademark_id']) || intval($_GET['trademark_id']) <= 0) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'msg' => '未指定商标ID']);
    exit;
}
$trademark_id = intval($_GET['trademark_id']);

// 验证商标是否存在
$trademark_stmt = $pdo->prepare("SELECT id FROM trademark_case_info WHERE id = ?");
$trademark_stmt->execute([$trademark_id]);
if (!$trademark_stmt->fetch()) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'msg' => '未找到该商标信息']);
    exit;
}

// 处理AJAX请求
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    header('Content-Type: application/json');

    $action = $_POST['action'];

    if ($action === 'get_applicant_list') {
        try {
            // 获取申请人列表
            $sql = "SELECT * FROM trademark_case_applicant WHERE trademark_case_info_id = ? ORDER BY id ASC";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$trademark_id]);
            $rows = $stmt->fetchAll();

            $html = '';
            if (empty($rows)) {
                $html = '<tr><td colspan="8" style="text-align:center;padding:20px 0;">暂无申请人数据</td></tr>';
            } else {
                foreach ($rows as $index => $a) {
                    $area = htmlspecialchars(($a['province'] ?? '') . ($a['city_cn'] ? ' ' . $a['city_cn'] : '') . ($a['district'] ? ' ' . $a['district'] : ''));
                    $html .= '<tr data-id="' . $a['id'] . '">' .
                        '<td style="text-align:center;">' . ($index + 1) . '</td>' .
                        '<td>' . htmlspecialchars($a['name_cn'] ?? '') . '</td>' .
                        '<td>' . htmlspecialchars($a['applicant_type'] ?? '') . '</td>' .
                        '<td>' . htmlspecialchars($a['entity_type'] ?? '') . '</td>' .
                        '<td>' . $area . '</td>' .
                        '<td>' . htmlspecialchars($a['phone'] ?? '') . '</td>' .
                        '<td style="text-align:center;">' . ($a['is_first_contact'] ? '是' : '否') . '</td>' .
                        '<td style="text-align:center;">' .
                        '<button type="button" class="btn-mini btn-edit">✎</button>' .
                        '<button type="button" class="btn-mini btn-del" style="color:#f44336;">✖</button>' .
                        '</td>' .
                        '</tr>';
                }
            }

            echo json_encode([
                'success' => true,
                'html' => $html
            ]);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'msg' => '获取数据失败：' . $e->getMessage()]);
        }
        exit;
    }

    if ($action === 'get_applicant') {
        try {
            // 获取单个申请人信息
            $id = intval($_POST['id'] ?? 0);
            if ($id <= 0) {
                echo json_encode(['success' => false, 'msg' => '无效的申请人ID']);
                exit;
            }

            $stmt = $pdo->prepare("SELECT * FROM trademark_case_applicant WHERE id = ? AND trademark_case_info_id = ?");
            $stmt->execute([$id, $trademark_id]);
            $data = $stmt->fetch();

            if (!$data) {
                echo json_encode(['success' => false, 'msg' => '未找到申请人信息']);
                exit;
            }

            echo json_encode(['success' => true, 'data' => $data]);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'msg' => '获取数据失败：' . $e->getMessage()]);
        }
        exit;
    }

    if ($action === 'save_applicant') {
        try {
            // 保存申请人信息
            $id = intval($_POST['id'] ?? 0);

            // 字段列表
            $fields = [
                'case_type',
                'applicant_type',
                'entity_type',
                'name_cn',
                'name_en',
                'name_xing_cn',
                'name_xing_en',
                'is_first_contact',
                'is_receipt_title',
                'receipt_title',
                'credit_code',
                'contact_person',
                'phone',
                'email',
                'province',
                'city_cn',
                'city_en',
                'district',
                'postcode',
                'address_cn',
                'address_en',
                'department_cn',
                'department_en',
                'id_type',
                'id_number',
                'is_fee_reduction',
                'fee_reduction_start',
                'fee_reduction_end',
                'fee_reduction_code',
                'cn_agent_code',
                'pct_agent_code',
                'is_fee_monitor',
                'country',
                'nationality',
                'business_license',
                'remark'
            ];

            $data = ['trademark_case_info_id' => $trademark_id];

            foreach ($fields as $field) {
                $value = $_POST[$field] ?? '';

                // 日期字段处理
                $date_fields = ['fee_reduction_start', 'fee_reduction_end'];
                if (in_array($field, $date_fields) && $value === '') {
                    $value = null;
                }

                // 布尔字段处理
                $bool_fields = ['is_first_contact', 'is_receipt_title', 'is_fee_reduction', 'is_fee_monitor'];
                if (in_array($field, $bool_fields)) {
                    $value = intval($value) ? 1 : 0;
                }

                $data[$field] = $value;
            }

            if ($id > 0) {
                // 更新
                $set = [];
                foreach ($data as $key => $value) {
                    if ($key !== 'trademark_case_info_id') {
                        $set[] = "$key = :$key";
                    }
                }
                $data['id'] = $id;
                $sql = "UPDATE trademark_case_applicant SET " . implode(',', $set) . " WHERE id = :id AND trademark_case_info_id = :trademark_case_info_id";
            } else {
                // 新增
                $keys = array_keys($data);
                $placeholders = ':' . implode(', :', $keys);
                $sql = "INSERT INTO trademark_case_applicant (" . implode(',', $keys) . ") VALUES ($placeholders)";
            }

            $stmt = $pdo->prepare($sql);
            $result = $stmt->execute($data);

            if ($result) {
                echo json_encode(['success' => true, 'msg' => '保存成功']);
            } else {
                echo json_encode(['success' => false, 'msg' => '保存失败']);
            }
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'msg' => '保存失败：' . $e->getMessage()]);
        }
        exit;
    }

    if ($action === 'delete_applicant') {
        try {
            // 删除申请人
            $id = intval($_POST['id'] ?? 0);
            if ($id <= 0) {
                echo json_encode(['success' => false, 'msg' => '无效的申请人ID']);
                exit;
            }

            $stmt = $pdo->prepare("DELETE FROM trademark_case_applicant WHERE id = ? AND trademark_case_info_id = ?");
            $result = $stmt->execute([$id, $trademark_id]);

            if ($result) {
                echo json_encode(['success' => true, 'msg' => '删除成功']);
            } else {
                echo json_encode(['success' => false, 'msg' => '删除失败']);
            }
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'msg' => '删除失败：' . $e->getMessage()]);
        }
        exit;
    }



    // 代理机构相关操作
    if ($action === 'search_agencies') {
        try {
            // 搜索代理机构
            $keyword = $_POST['keyword'] ?? '';
            $sql = "SELECT id, agency_name_cn, agency_code FROM agency WHERE is_active = 1";
            $params = [];

            if (!empty($keyword)) {
                $sql .= " AND (agency_name_cn LIKE ? OR agency_code LIKE ?)";
                $params[] = "%$keyword%";
                $params[] = "%$keyword%";
            }

            $sql .= " ORDER BY agency_name_cn LIMIT 20";

            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            $agencies = $stmt->fetchAll();

            echo json_encode(['success' => true, 'data' => $agencies]);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'msg' => '搜索失败：' . $e->getMessage()]);
        }
        exit;
    }

    if ($action === 'get_agency_agents') {
        try {
            // 获取代理机构的代理人列表
            $agency_id = intval($_POST['agency_id'] ?? 0);
            if ($agency_id <= 0) {
                echo json_encode(['success' => false, 'msg' => '无效的代理机构ID']);
                exit;
            }

            $sql = "SELECT id, name_cn, license_no FROM agency_agent WHERE agency_id = ? AND is_active = 1 ORDER BY name_cn";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$agency_id]);
            $agents = $stmt->fetchAll();

            echo json_encode(['success' => true, 'data' => $agents]);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'msg' => '获取代理人失败：' . $e->getMessage()]);
        }
        exit;
    }

    if ($action === 'get_agency_contacts') {
        try {
            // 获取代理机构的联系人列表
            $agency_id = intval($_POST['agency_id'] ?? 0);
            if ($agency_id <= 0) {
                echo json_encode(['success' => false, 'msg' => '无效的代理机构ID']);
                exit;
            }

            $sql = "SELECT id, name, mobile, work_email FROM agency_contact WHERE agency_id = ? AND is_active = 1 ORDER BY name";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$agency_id]);
            $contacts = $stmt->fetchAll();

            echo json_encode(['success' => true, 'data' => $contacts]);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'msg' => '获取联系人失败：' . $e->getMessage()]);
        }
        exit;
    }

    if ($action === 'save_agency') {
        try {
            $pdo->beginTransaction();

            $agency_id = $_POST['agency_id'] ?? '';
            $remark = $_POST['remark'] ?? '';
            $agent_ids = $_POST['agent_ids'] ?? '';
            $contact_ids = $_POST['contact_ids'] ?? '';

            if (empty($agency_id)) {
                throw new Exception('请选择代理机构');
            }

            // 获取代理机构信息
            $agency_stmt = $pdo->prepare("SELECT agency_name_cn, agency_code FROM agency WHERE id = ?");
            $agency_stmt->execute([$agency_id]);
            $agency_info = $agency_stmt->fetch();

            if (!$agency_info) {
                throw new Exception('代理机构不存在');
            }

            // 先删除该商标案件的所有代理机构记录（因为现在是单选模式）
            $delete_stmt = $pdo->prepare("DELETE FROM trademark_case_agency WHERE trademark_case_info_id = ?");
            $delete_stmt->execute([$trademark_id]);

            // 处理代理人和联系人ID
            $agent_id_array = !empty($agent_ids) ? explode(',', $agent_ids) : [];
            $contact_id_array = !empty($contact_ids) ? explode(',', $contact_ids) : [];

            // 为每个选中的代理人创建记录
            if (!empty($agent_id_array)) {
                foreach ($agent_id_array as $agent_id) {
                    if (!empty($agent_id)) {
                        // 获取代理人信息
                        $agent_stmt = $pdo->prepare("SELECT name_cn, license_no FROM agency_agent WHERE id = ?");
                        $agent_stmt->execute([$agent_id]);
                        $agent_info = $agent_stmt->fetch();

                        $insert_stmt = $pdo->prepare("
                            INSERT INTO trademark_case_agency (
                                trademark_case_info_id, agency_id, agency_agent_id, 
                                agency_name_cn, agency_code, agent_name_cn, agent_license_no, remark
                            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?)
                        ");
                        $insert_stmt->execute([
                            $trademark_id,
                            $agency_id,
                            $agent_id,
                            $agency_info['agency_name_cn'],
                            $agency_info['agency_code'],
                            $agent_info['name_cn'] ?? '',
                            $agent_info['license_no'] ?? '',
                            $remark
                        ]);
                    }
                }
            }

            // 为每个选中的联系人创建记录
            if (!empty($contact_id_array)) {
                foreach ($contact_id_array as $contact_id) {
                    if (!empty($contact_id)) {
                        // 获取联系人信息
                        $contact_stmt = $pdo->prepare("SELECT name, mobile, work_email FROM agency_contact WHERE id = ?");
                        $contact_stmt->execute([$contact_id]);
                        $contact_info = $contact_stmt->fetch();

                        $insert_stmt = $pdo->prepare("
                            INSERT INTO trademark_case_agency (
                                trademark_case_info_id, agency_id, agency_contact_id,
                                agency_name_cn, agency_code, contact_name, contact_phone, contact_email, remark
                            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
                        ");
                        $insert_stmt->execute([
                            $trademark_id,
                            $agency_id,
                            $contact_id,
                            $agency_info['agency_name_cn'],
                            $agency_info['agency_code'],
                            $contact_info['name'] ?? '',
                            $contact_info['mobile'] ?? '',
                            $contact_info['work_email'] ?? '',
                            $remark
                        ]);
                    }
                }
            }

            // 如果没有选择代理人和联系人，至少创建一条基本记录
            if (empty($agent_id_array) && empty($contact_id_array)) {
                $insert_stmt = $pdo->prepare("
                    INSERT INTO trademark_case_agency (
                        trademark_case_info_id, agency_id, agency_name_cn, agency_code, remark
                    ) VALUES (?, ?, ?, ?, ?)
                ");
                $insert_stmt->execute([
                    $trademark_id,
                    $agency_id,
                    $agency_info['agency_name_cn'],
                    $agency_info['agency_code'],
                    $remark
                ]);
            }

            $pdo->commit();
            echo json_encode(['success' => true, 'msg' => '保存成功']);
        } catch (Exception $e) {
            $pdo->rollBack();
            echo json_encode(['success' => false, 'msg' => '保存失败：' . $e->getMessage()]);
        }
        exit;
    }

    if ($action === 'delete_agency') {
        try {
            // 删除代理机构
            $id = intval($_POST['id'] ?? 0);
            if ($id <= 0) {
                echo json_encode(['success' => false, 'msg' => '无效的代理机构ID']);
                exit;
            }

            $stmt = $pdo->prepare("DELETE FROM trademark_case_agency WHERE id = ? AND trademark_case_info_id = ?");
            $result = $stmt->execute([$id, $trademark_id]);

            if ($result) {
                echo json_encode(['success' => true, 'msg' => '删除成功']);
            } else {
                echo json_encode(['success' => false, 'msg' => '删除失败']);
            }
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'msg' => '删除失败：' . $e->getMessage()]);
        }
        exit;
    }

    if ($action === 'get_all_agencies') {
        try {
            $sql = "SELECT id, agency_name_cn, agency_code FROM agency WHERE is_active = 1 ORDER BY agency_name_cn";
            $stmt = $pdo->prepare($sql);
            $stmt->execute();
            $agencies = $stmt->fetchAll(PDO::FETCH_ASSOC);

            echo json_encode(['success' => true, 'data' => $agencies]);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'msg' => '获取代理机构失败：' . $e->getMessage()]);
        }
        exit;
    }

    if ($action === 'load_agency') {
        try {
            // 加载商标案件的代理机构信息
            $sql = "SELECT tca.*, a.agency_name_cn, a.agency_code 
                    FROM trademark_case_agency tca 
                    LEFT JOIN agency a ON tca.agency_id = a.id 
                    WHERE tca.trademark_case_info_id = ?";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$trademark_id]);
            $agencies = $stmt->fetchAll();

            if (!empty($agencies)) {
                // 只取第一个代理机构（因为现在是单选模式）
                $agency = $agencies[0];

                // 获取关联的代理人
                $agent_sql = "SELECT aa.id, aa.name_cn, aa.license_no, aa.phone 
                             FROM agency_agent aa 
                             WHERE aa.agency_id = ? AND aa.id IN (
                                 SELECT agency_agent_id FROM trademark_case_agency 
                                 WHERE trademark_case_info_id = ? AND agency_agent_id IS NOT NULL
                             )";
                $agent_stmt = $pdo->prepare($agent_sql);
                $agent_stmt->execute([$agency['agency_id'], $trademark_id]);
                $agency['agents'] = $agent_stmt->fetchAll();

                // 获取关联的联系人
                $contact_sql = "SELECT ac.id, ac.name, ac.mobile, ac.work_email 
                               FROM agency_contact ac 
                               WHERE ac.agency_id = ? AND ac.id IN (
                                   SELECT agency_contact_id FROM trademark_case_agency 
                                   WHERE trademark_case_info_id = ? AND agency_contact_id IS NOT NULL
                               )";
                $contact_stmt = $pdo->prepare($contact_sql);
                $contact_stmt->execute([$agency['agency_id'], $trademark_id]);
                $agency['contacts'] = $contact_stmt->fetchAll();

                echo json_encode(['success' => true, 'data' => [$agency]]);
            } else {
                echo json_encode(['success' => true, 'data' => []]);
            }
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'msg' => '加载数据失败：' . $e->getMessage()]);
        }
        exit;
    }

    echo json_encode(['success' => false, 'msg' => '未知操作']);
    exit;
}

// 如果不是POST请求或没有action参数，返回错误
header('Content-Type: application/json');
echo json_encode(['success' => false, 'msg' => '无效的请求']);
exit;
