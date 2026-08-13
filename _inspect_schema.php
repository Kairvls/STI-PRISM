<?php

$lines = file('c:/xampp/htdocs/STI-PRISM/prism.sql');
$inBlock = false;

foreach ($lines as $i => $line) {
    // Start capturing at the RIS table CREATE statement
    if (str_contains($line, 'CREATE TABLE `requisition_issue_slip_table`')) {
        $inBlock = true;
    }
    if ($inBlock) {
        echo $line;
        // Stop after the closing of the CREATE TABLE block
        if (str_contains($line, 'ENGINE=') || (trim($line) === ')' && $i > 1260)) {
            if (str_contains($line, 'ENGINE=')) {
                break;
            }
        }
    }
}

