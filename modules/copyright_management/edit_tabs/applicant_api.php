<?php
// 版权编辑-申请人API接口
include_once(__DIR__ . '/../../../database.php');
include_once(__DIR__ . '/../../../common/functions.php');
check_access_via_framework();
session_start();

if (!isset($_SESSION['user_id'])) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'msg' => '未登录或会话超时']);
    exit;
}

if (!isset($_GET['copyright_id']) || intval($_GET['copyright_id']) <= 0) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'msg' => '未指定版权ID']);
    exit;
}
$copyright_id = intval($_GET['copyright_id']);

// 验证版权是否存在
$copyright_stmt = $pdo->prepare("SELECT id FROM copyright_case_info WHERE id = ?");
$copyright_stmt->execute([$copyright_id]);
if (!$copyright_stmt->fetch()) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'msg' => '未找到该版权信息']);
    exit;
}

// 处理AJAX请求
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    header('Content-Type: application/json');

    $action = $_POST['action'];

    if ($action === 'get_applicants') {
        try {
            // 获取申请人列表
            $sql = "SELECT * FROM copyright_case_applicant WHERE copyright_case_info_id = ? ORDER BY id ASC";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$copyright_id]);
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

            $stmt = $pdo->prepare("SELECT * FROM copyright_case_applicant WHERE id = ? AND copyright_case_info_id = ?");
            $stmt->execute([$id, $copyright_id]);
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

            $data = ['copyright_case_info_id' => $copyright_id];

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
                    if ($key !== 'copyright_case_info_id') {
                        $set[] = "$key = :$key";
                    }
                }
                $data['id'] = $id;
                $sql = "UPDATE copyright_case_applicant SET " . implode(',', $set) . " WHERE id = :id AND copyright_case_info_id = :copyright_case_info_id";
            } else {
                // 新增
                $keys = array_keys($data);
                $placeholders = ':' . implode(', :', $keys);
                $sql = "INSERT INTO copyright_case_applicant (" . implode(',', $keys) . ") VALUES ($placeholders)";
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

            $stmt = $pdo->prepare("DELETE FROM copyright_case_applicant WHERE id = ? AND copyright_case_info_id = ?");
            $result = $stmt->execute([$id, $copyright_id]);

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

    // 作者相关接口
    if ($action === 'get_authors') {
        try {
            // 获取作者列表
            $sql = "SELECT * FROM copyright_case_author WHERE copyright_case_info_id = ? ORDER BY id ASC";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$copyright_id]);
            $rows = $stmt->fetchAll();

            $html = '';
            if (empty($rows)) {
                $html = '<tr><td colspan="7" style="text-align:center;padding:20px 0;">暂无作者数据</td></tr>';
            } else {
                foreach ($rows as $index => $a) {
                    // 构建所属地区显示
                    $location = '';
                    if (!empty($a['country'])) {
                        $location = $a['country'];
                        if (!empty($a['province'])) {
                            $location .= ' ' . $a['province'];
                        }
                        if (!empty($a['city_cn'])) {
                            $location .= ' ' . $a['city_cn'];
                        }
                    }

                    $html .= '<tr data-id="' . $a['id'] . '">' .
                        '<td style="text-align:center;">' . ($index + 1) . '</td>' .
                        '<td>' . htmlspecialchars($a['name_cn'] ?? '') . '</td>' .
                        '<td>' . htmlspecialchars($a['name_en'] ?? '') . '</td>' .
                        '<td>' . htmlspecialchars($a['nationality'] ?? '') . '</td>' .
                        '<td>' . htmlspecialchars($location) . '</td>' .
                        '<td style="text-align:center;">' . ($a['is_main_author'] ? '是' : '否') . '</td>' .
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

    if ($action === 'get_author') {
        try {
            // 获取单个作者信息
            $id = intval($_POST['id'] ?? 0);
            if ($id <= 0) {
                echo json_encode(['success' => false, 'msg' => '无效的作者ID']);
                exit;
            }

            $stmt = $pdo->prepare("SELECT * FROM copyright_case_author WHERE id = ? AND copyright_case_info_id = ?");
            $stmt->execute([$id, $copyright_id]);
            $data = $stmt->fetch();

            if (!$data) {
                echo json_encode(['success' => false, 'msg' => '未找到作者信息']);
                exit;
            }

            echo json_encode(['success' => true, 'data' => $data]);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'msg' => '获取数据失败：' . $e->getMessage()]);
        }
        exit;
    }

    if ($action === 'save_author') {
        try {
            // 保存作者信息
            $id = intval($_POST['id'] ?? 0);

            // 字段列表
            $fields = [
                'name_cn',
                'name_en',
                'job_no',
                'xing_cn',
                'xing_en',
                'ming_cn',
                'ming_en',
                'nationality',
                'country',
                'is_main_author',
                'province',
                'city_cn',
                'city_en',
                'address_cn',
                'address_en',
                'department_cn',
                'department_en',
                'email',
                'id_number',
                'phone',
                'qq',
                'mobile',
                'postcode',
                'remark'
            ];

            $data = ['copyright_case_info_id' => $copyright_id];

            foreach ($fields as $field) {
                $value = $_POST[$field] ?? '';

                // 布尔字段处理
                if ($field === 'is_main_author') {
                    $value = intval($value) ? 1 : 0;
                }

                $data[$field] = $value;
            }

            if ($id > 0) {
                // 更新
                $set = [];
                foreach ($data as $key => $value) {
                    if ($key !== 'copyright_case_info_id') {
                        $set[] = "$key = :$key";
                    }
                }
                $data['id'] = $id;
                $sql = "UPDATE copyright_case_author SET " . implode(',', $set) . " WHERE id = :id AND copyright_case_info_id = :copyright_case_info_id";
            } else {
                // 新增
                $keys = array_keys($data);
                $placeholders = ':' . implode(', :', $keys);
                $sql = "INSERT INTO copyright_case_author (" . implode(',', $keys) . ") VALUES ($placeholders)";
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

    if ($action === 'delete_author') {
        try {
            // 删除作者
            $id = intval($_POST['id'] ?? 0);
            if ($id <= 0) {
                echo json_encode(['success' => false, 'msg' => '无效的作者ID']);
                exit;
            }

            $stmt = $pdo->prepare("DELETE FROM copyright_case_author WHERE id = ? AND copyright_case_info_id = ?");
            $result = $stmt->execute([$id, $copyright_id]);

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

    // 代理机构相关接口
    if ($action === 'load_agency') {
        try {
            // 获取代理机构信息
            $sql = "SELECT ca.*, a.agency_name_cn, a.agency_code 
                    FROM copyright_case_agency ca 
                    LEFT JOIN agency a ON ca.agency_id = a.id 
                    WHERE ca.copyright_case_info_id = ?";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$copyright_id]);
            $agencies = $stmt->fetchAll();

            $result = [];
            foreach ($agencies as $agency) {
                // 获取代理人信息
                $agent_sql = "SELECT aa.* FROM agency_agent aa 
                             WHERE aa.agency_id = ? AND aa.id IN (
                                 SELECT agency_agent_id FROM copyright_case_agency 
                                 WHERE copyright_case_info_id = ? AND agency_agent_id IS NOT NULL
                             )";
                $agent_stmt = $pdo->prepare($agent_sql);
                $agent_stmt->execute([$agency['agency_id'], $copyright_id]);
                $agents = $agent_stmt->fetchAll();

                // 获取联系人信息
                $contact_sql = "SELECT ac.* FROM agency_contact ac 
                               WHERE ac.agency_id = ? AND ac.id IN (
                                   SELECT agency_contact_id FROM copyright_case_agency 
                                   WHERE copyright_case_info_id = ? AND agency_contact_id IS NOT NULL
                               )";
                $contact_stmt = $pdo->prepare($contact_sql);
                $contact_stmt->execute([$agency['agency_id'], $copyright_id]);
                $contacts = $contact_stmt->fetchAll();

                $agency['agents'] = $agents;
                $agency['contacts'] = $contacts;
                $result[] = $agency;
            }

            echo json_encode(['success' => true, 'data' => $result]);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'msg' => '获取数据失败：' . $e->getMessage()]);
        }
        exit;
    }

    if ($action === 'get_all_agencies') {
        try {
            // 获取所有代理机构
            $stmt = $pdo->prepare("SELECT id, agency_name_cn, agency_code FROM agency WHERE is_active = 1 ORDER BY agency_name_cn");
            $stmt->execute();
            $agencies = $stmt->fetchAll();

            echo json_encode(['success' => true, 'data' => $agencies]);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'msg' => '获取数据失败：' . $e->getMessage()]);
        }
        exit;
    }

    if ($action === 'get_agency_agents') {
        try {
            // 获取代理机构的代理人
            $agency_id = intval($_POST['agency_id'] ?? 0);
            if ($agency_id <= 0) {
                echo json_encode(['success' => false, 'msg' => '无效的代理机构ID']);
                exit;
            }

            $stmt = $pdo->prepare("SELECT * FROM agency_agent WHERE agency_id = ? AND is_active = 1 ORDER BY name_cn");
            $stmt->execute([$agency_id]);
            $agents = $stmt->fetchAll();

            echo json_encode(['success' => true, 'data' => $agents]);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'msg' => '获取数据失败：' . $e->getMessage()]);
        }
        exit;
    }

    if ($action === 'get_agency_contacts') {
        try {
            // 获取代理机构的联系人
            $agency_id = intval($_POST['agency_id'] ?? 0);
            if ($agency_id <= 0) {
                echo json_encode(['success' => false, 'msg' => '无效的代理机构ID']);
                exit;
            }

            $stmt = $pdo->prepare("SELECT * FROM agency_contact WHERE agency_id = ? AND is_active = 1 ORDER BY name");
            $stmt->execute([$agency_id]);
            $contacts = $stmt->fetchAll();

            echo json_encode(['success' => true, 'data' => $contacts]);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'msg' => '获取数据失败：' . $e->getMessage()]);
        }
        exit;
    }

    if ($action === 'save_agency') {
        try {
            // 保存代理机构信息
            $id = intval($_POST['id'] ?? 0);
            $agency_id = intval($_POST['agency_id'] ?? 0);
            $remark = $_POST['remark'] ?? '';
            $agent_ids = $_POST['agent_ids'] ?? '';
            $contact_ids = $_POST['contact_ids'] ?? '';

            if ($agency_id <= 0) {
                echo json_encode(['success' => false, 'msg' => '请选择代理机构']);
                exit;
            }

            // 获取代理机构信息
            $agency_stmt = $pdo->prepare("SELECT * FROM agency WHERE id = ?");
            $agency_stmt->execute([$agency_id]);
            $agency_info = $agency_stmt->fetch();

            if (!$agency_info) {
                echo json_encode(['success' => false, 'msg' => '代理机构不存在']);
                exit;
            }

            $pdo->beginTransaction();

            try {
                // 删除现有的代理机构关联
                $delete_stmt = $pdo->prepare("DELETE FROM copyright_case_agency WHERE copyright_case_info_id = ?");
                $delete_stmt->execute([$copyright_id]);

                // 处理代理人和联系人
                $agent_id_array = $agent_ids ? explode(',', $agent_ids) : [];
                $contact_id_array = $contact_ids ? explode(',', $contact_ids) : [];

                // 如果没有选择代理人和联系人，创建一个基本记录
                if (empty($agent_id_array) && empty($contact_id_array)) {
                    $insert_data = [
                        'copyright_case_info_id' => $copyright_id,
                        'agency_id' => $agency_id,
                        'agency_name_cn' => $agency_info['agency_name_cn'],
                        'agency_code' => $agency_info['agency_code'],
                        'remark' => $remark
                    ];

                    $insert_sql = "INSERT INTO copyright_case_agency (copyright_case_info_id, agency_id, agency_name_cn, agency_code, remark) VALUES (?, ?, ?, ?, ?)";
                    $insert_stmt = $pdo->prepare($insert_sql);
                    $insert_stmt->execute([$copyright_id, $agency_id, $agency_info['agency_name_cn'], $agency_info['agency_code'], $remark]);
                } else {
                    // 为每个代理人创建记录
                    foreach ($agent_id_array as $agent_id) {
                        $agent_id = intval($agent_id);
                        if ($agent_id > 0) {
                            // 获取代理人信息
                            $agent_stmt = $pdo->prepare("SELECT * FROM agency_agent WHERE id = ?");
                            $agent_stmt->execute([$agent_id]);
                            $agent_info = $agent_stmt->fetch();

                            if ($agent_info) {
                                $insert_sql = "INSERT INTO copyright_case_agency (copyright_case_info_id, agency_id, agency_agent_id, agency_name_cn, agency_code, agent_name_cn, agent_license_no, remark) VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
                                $insert_stmt = $pdo->prepare($insert_sql);
                                $insert_stmt->execute([
                                    $copyright_id,
                                    $agency_id,
                                    $agent_id,
                                    $agency_info['agency_name_cn'],
                                    $agency_info['agency_code'],
                                    $agent_info['name_cn'],
                                    $agent_info['license_no'],
                                    $remark
                                ]);
                            }
                        }
                    }

                    // 为每个联系人创建记录
                    foreach ($contact_id_array as $contact_id) {
                        $contact_id = intval($contact_id);
                        if ($contact_id > 0) {
                            // 获取联系人信息
                            $contact_stmt = $pdo->prepare("SELECT * FROM agency_contact WHERE id = ?");
                            $contact_stmt->execute([$contact_id]);
                            $contact_info = $contact_stmt->fetch();

                            if ($contact_info) {
                                $insert_sql = "INSERT INTO copyright_case_agency (copyright_case_info_id, agency_id, agency_contact_id, agency_name_cn, agency_code, contact_name, contact_phone, contact_email, remark) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
                                $insert_stmt = $pdo->prepare($insert_sql);
                                $insert_stmt->execute([
                                    $copyright_id,
                                    $agency_id,
                                    $contact_id,
                                    $agency_info['agency_name_cn'],
                                    $agency_info['agency_code'],
                                    $contact_info['name'],
                                    $contact_info['mobile'],
                                    $contact_info['work_email'],
                                    $remark
                                ]);
                            }
                        }
                    }
                }

                $pdo->commit();
                echo json_encode(['success' => true, 'msg' => '保存成功']);
            } catch (Exception $e) {
                $pdo->rollBack();
                throw $e;
            }
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'msg' => '保存失败：' . $e->getMessage()]);
        }
        exit;
    }

    if ($action === 'delete_agency') {
        try {
            // 删除代理机构关联
            $id = intval($_POST['id'] ?? 0);
            if ($id <= 0) {
                echo json_encode(['success' => false, 'msg' => '无效的代理机构ID']);
                exit;
            }

            $stmt = $pdo->prepare("DELETE FROM copyright_case_agency WHERE copyright_case_info_id = ?");
            $result = $stmt->execute([$copyright_id]);

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

    echo json_encode(['success' => false, 'msg' => '未知的操作']);
    exit;
}

// 如果不是POST请求，返回错误
header('Content-Type: application/json');
echo json_encode(['success' => false, 'msg' => '无效的请求方法']);
