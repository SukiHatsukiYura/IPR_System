import os
import re

# 项目根目录（请根据实际情况修改）
PROJECT_ROOT = os.path.join(os.getcwd(), "modules")

# 正确的插入代码
INSERT_CODE = """include_once(__DIR__ . '/../../../database.php');
check_access_via_framework();
"""


def fix_include_syntax(filepath):
    """修正所有include_once(__DIR__ + ... 为 include_once(__DIR__ . ..."""
    with open(filepath, 'r', encoding='utf-8') as f:
        content = f.read()
    new_content = re.sub(r"include_once\(__DIR__ \+ ", "include_once(__DIR__ . ", content)
    if new_content != content:
        with open(filepath, 'w', encoding='utf-8') as f:
            f.write(new_content)
        print(f'已修正include_once语法: {filepath}')
        return True
    return False


def should_insert(content):
    # 已有检测函数或已包含database.php则跳过
    return ('check_access_via_framework' not in content) and ('database.php' not in content)


def process_php_file(filepath):
    fix_include_syntax(filepath)
    with open(filepath, 'r', encoding='utf-8') as f:
        content = f.read()
    if not should_insert(content):
        return False
    # 找到<?php后插入
    idx = content.find('<?php')
    if idx == -1:
        return False
    idx += len('<?php')
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
