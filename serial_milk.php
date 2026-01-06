<?php

if ($argc < 3) {
    echo "Usage: php serial_god_mode.php <php file> <class name>\n";
    exit(1);
}

$file = $argv[1];
$className = $argv[2];

if (!file_exists($file)) {
    die("Erreur: Fichier '$file' introuvable.\n");
}

require_once $file;

if (!class_exists($className)) {
    die("Erreur: Classe '$className' introuvable.\n");
}

try {
    $refClass = new ReflectionClass($className);
    
    // Creating object without the constructor to bypass constructor restrictions
    $object = $refClass->newInstanceWithoutConstructor();
    
    // Get props
    $properties = $refClass->getProperties();

    echo "--- Configuration des propriétés pour '$className' ---\n";

    foreach ($properties as $prop) {
        // Set property to accissible
        $prop->setAccessible(true);
        
        $name = $prop->getName();
        
        // Visibility type detection
        $visibility = 'public';
        if ($prop->isProtected()) $visibility = 'protected';
        if ($prop->isPrivate()) $visibility = 'private';

        $currentValue = $prop->getValue($object); // Default class value
        $displayDefault = is_scalar($currentValue) ? $currentValue : var_export($currentValue, true);

        echo "[$visibility] \$$name (Défaut: $displayDefault) : ";
        
        $handle = fopen("php://stdin", "r");
        $input = trim(fgets($handle));
        fclose($handle);

        // User input or default
        if ($input !== "") {
            $prop->setValue($object, $input);
        }
    }

    echo "\n--- Résultat ---\n";
    
    $serialized = serialize($object);
    
    echo "Raw:\n" . $serialized . "\n\n";
    
    echo "URL Encoded:\n" . urlencode($serialized) . "\n\n";
    
    echo "Base64:\n" . base64_encode($serialized) . "\n";

} catch (Exception $e) {
    echo "Erreur: " . $e->getMessage() . "\n";
}
