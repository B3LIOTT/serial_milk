<?php
class MyClass{
    public $filename = "index.html";
    public function __toString(){
        return file_get_contents($this->filename);
    }
}
?>