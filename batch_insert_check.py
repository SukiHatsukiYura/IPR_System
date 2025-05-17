import os

# 项目根目录（请根据实际情况修改）
PROJECT_ROOT = os.path.join(os.getcwd(), "modules")

# 要插入的代码
INSERT_CODE = """include_once(__DIR__ + '/../../../database.php');
check_access_via_framework();
"""


def should_insert(content):
    # 已有检测函数或已包含database.php则跳过
    return ('check_access_via_framework' not in content) and ('database.php' not in content)


def process_php_file(filepath):
    with open(filepath, 'r', encoding='utf-8') as f:
        content = f.read()
    if not should_insert(content):
        return False
    # 找到<?php后插入
    idx = content.find('<?php')
    if idx == -1:
        return False
    idx += len('<?php')
    # 保留<?php后原有注释
    new_content = content[:idx] + '\n' + INSERT_CODE + content[idx:]
    with open(filepath, 'w', encoding='utf-8') as f:
        f.write(new_content)
    print(f'已插入: {filepath}')
    return True


def main():
    for root, dirs, files in os.walk(PROJECT_ROOT):
        for file in files:
            if file.endswith('.php'):
                filepath = os.path.join(root, file)
                process_php_file(filepath)


if __name__ == '__main__':
    main()
