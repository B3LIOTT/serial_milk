import sys
import base64
import re


class Class:
    def __init__(self, name=""):
        self.class_name = name
        self.pub_vars = {}  # {var_name: (value, type)}

    def serialize(self):
        s = f'O:{len(self.class_name)}:"{self.class_name}":{len(self.pub_vars)}:{{'
        for var, (val, typ) in self.pub_vars.items():
            s += f's:{len(var)}:"{var}";'
            if typ == 's':
                val_len = len(val)
                s += f's:{val_len}:"{val}";'
            elif typ == 'i':
                s += f'i:{val};'
            else:
                raise NotImplementedError(f"Type {typ} not implemented")
        s += '}'

        print("[Plain]")
        print(s)
        print("\n[Base64]")
        print(base64.b64encode(s.encode()).decode())
        return s


def extract_php_class(filepath):
    with open(filepath, 'r') as f:
        content = f.read()

    content = re.sub(r'<\?php', '', content)
    content = re.sub(r'\?>', '', content)
    content = re.sub(r'//.*$', '', content, flags=re.MULTILINE)
    content = re.sub(r'/\*.*?\*/', '', content, flags=re.DOTALL)

    # Find class
    class_pattern = r'class\s+(\w+)\s*\{([^}]*)\}'
    class_match = re.search(class_pattern, content, re.DOTALL | re.IGNORECASE)
    
    if not class_match:
        raise ValueError("No PHP class found")
    
    class_name = class_match.group(1)
    class_body = class_match.group(2)
    
    struct = Class(class_name)
    
    # Initialized variables
    var_patterns = [
        r'public\s+\$(\w+)\s*=\s*["\']([^"\']*)["\'];',  # public string
        r'public\s+\$(\w+)\s*=\s*(\d+);',                # public integer
        r'\$(\w+)\s*=\s*["\']([^"\']*)["\'];',           # string
        r'\$(\w+)\s*=\s*(\d+);',                         # integer
    ]
    
    initialized_vars = set()
    for pattern in var_patterns:
        matches = re.findall(pattern, class_body)
        for match in matches:
            var_name = match[0]
            value = match[1]
            initialized_vars.add(var_name)
            
            if value.isdigit() or (value.startswith('-') and value[1:].isdigit()):
                struct.pub_vars[var_name] = (int(value), 'i')
            else:
                struct.pub_vars[var_name] = (value, 's')
    
    # Uninitialized variables
    uninit_patterns = [
        r'public\s+\$(\w+);',          # public $var;
        r'var\s+\$(\w+);',             # var $var;
        r'private\s+\$(\w+);',         # private $var;
        r'protected\s+\$(\w+);',       # protected $var;
    ]
    
    uninitialized_vars = set()
    for pattern in uninit_patterns:
        matches = re.findall(pattern, class_body)
        for match in matches:
            var_name = match
            if var_name not in initialized_vars:
                uninitialized_vars.add(var_name)
    
    # Define uninitialized variables
    if uninitialized_vars:
        print(f"\n[Info] Uninitialized variables detected in class '{class_name}':")
        for var in sorted(uninitialized_vars):
            print(f"  - ${var}")
        
        print("\nDo you want to define these variables? (y/n): ", end="")
        if input().lower() in ['y', 'yes']:
            for var in sorted(uninitialized_vars):
                while True:
                    print(f"\nDefining ${var}:")
                    print("  1. String")
                    print("  2. Integer")
                    choice = input("Choose type (1-2): ").strip()
                    
                    if choice == '1':
                        value = input(f"Value for ${var} (string): ")
                        struct.pub_vars[var] = (value, 's')
                        break
                    elif choice == '2':
                        try:
                            value = int(input(f"Value for ${var} (integer): "))
                            struct.pub_vars[var] = (value, 'i')
                            break
                        except ValueError:
                            print("Error: Please enter a valid integer.")
                    else:
                        print("Invalid choice. Please choose 1 or 2.")
        print()
    
    return struct


def main(filepath: str):
    try:
        cls = extract_php_class(filepath)
        cls.serialize()
    except Exception as e:
        print(f"[Error] {e}")
        return 1
    return 0


if __name__ == "__main__":
    if len(sys.argv) != 2:
        print(f"Usage: {sys.argv[0]} <filepath>")
        print("filepath is the path of a file containing the class you want to serialize")
        sys.exit(1)

    sys.exit(main(sys.argv[1]))
