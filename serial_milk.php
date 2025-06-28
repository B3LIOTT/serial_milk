<?php

if ($argc < 3) {
    echo "Usage: php serial_milk.php <php file> <class name> [arg1 arg2 ...]\n";
    exit(1);
}

$file = $argv[1];
$className = $argv[2];
$args = array_slice($argv, 3);

if (!file_exists($file)) {
    echo "Error: file '$file' not found.\n";
    exit(1);
}

require_once $file;

if (!class_exists($className)) {
    echo "Error: class '$className' not found in the file.\n";
    exit(1);
}

try {
    $refClass = new ReflectionClass($className);
    $constructor = $refClass->getConstructor();

    $params = $constructor ? $constructor->getParameters() : [];
    $argsNeeded = count($params);

    // Ask for missing parameters
    if (count($args) < $argsNeeded) {
        for ($i = count($args); $i < $argsNeeded; $i++) {
            $param = $params[$i];
            $paramName = $param->getName();
            $default = $param->isDefaultValueAvailable() ? $param->getDefaultValue() : null;

            echo "Enter value for parameter \${$paramName}";
            if ($default !== null) {
                echo " [default = {$default}]";
            }
            echo ": ";
            
            $handle = fopen("php://stdin", "r");
            $line = trim(fgets($handle));

            if ($line === "" && $default !== null) {
                $value = $default;
            } else {
                // Ask user for the type
                echo "Choose type for parameter \${$paramName} (int, string, bool) [string]: ";
                $typeLine = trim(fgets($handle));
                if ($typeLine === "") {
                    $typeLine = "string"; // default type
                }

                // Convert value according to chosen type
                switch (strtolower($typeLine)) {
                    case 'int':
                        $value = (int)$line;
                        break;
                    case 'bool':
                        $value = filter_var($line, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
                        if ($value === null) {
                            echo "Invalid boolean input, defaulting to false.\n";
                            $value = false;
                        }
                        break;
                    case 'string':
                    default:
                        $value = $line;
                        break;
                }
            }
            fclose($handle);
            $args[] = $value;
        }
    }


    // Instantiate with final args
    $obj = $refClass->newInstanceArgs($args);

    echo "[Plain]\n";
    echo serialize($obj) . "\n\n";
    echo "[Base64]\n";
    echo base64_encode(serialize($obj)) . "\n";

} catch (ReflectionException $e) {
    echo "Reflection error: ", $e->getMessage(), "\n";
    exit(1);
} catch (ArgumentCountError $e) {
    echo "Error: incorrect number of arguments for the constructor.\n";
    exit(1);
} catch (Exception $e) {
    echo "General error: ", $e->getMessage(), "\n";
    exit(1);
}

?>