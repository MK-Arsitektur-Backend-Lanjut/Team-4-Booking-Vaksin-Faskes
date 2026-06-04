#!/usr/bin/env python3
"""
Lightweight YAML duplicate-key detector.
Usage:
  python scripts/check_yaml_duplicates.py path/to/file.yml [other.yml ...]

This uses PyYAML and a custom loader that raises on duplicate mapping keys.
"""
import sys
from yaml import SafeLoader, load
from yaml.constructor import ConstructorError

class DupCheckLoader(SafeLoader):
    def construct_mapping(self, node, deep=False):
        mapping = {}
        for key_node, value_node in node.value:
            key = self.construct_object(key_node, deep=deep)
            if key in mapping:
                raise ConstructorError('while constructing a mapping', node.start_mark,
                                       'found duplicate key (%r)' % key, key_node.start_mark)
            value = self.construct_object(value_node, deep=deep)
            mapping[key] = value
        return mapping


def check_file(path):
    try:
        with open(path, 'r', encoding='utf-8') as f:
            load(f, Loader=DupCheckLoader)
        print(f'OK: no duplicate mapping keys detected in {path}')
    except ConstructorError as e:
        print(f'ERROR: duplicate mapping key detected in {path}:')
        print(e)
    except FileNotFoundError:
        print(f'ERROR: file not found: {path}')
    except Exception as e:
        print(f'ERROR: unexpected error while parsing {path}: {e}')


if __name__ == '__main__':
    if len(sys.argv) < 2:
        print('Usage: python scripts/check_yaml_duplicates.py file1.yml [file2.yml ...]')
        sys.exit(2)
    for p in sys.argv[1:]:
        check_file(p)
