<?php

// Simple test to verify BPJS extraction logic without Laravel dependencies

function testBpjsExtraction($text, $format) {
    echo "Testing $format format:\n";
    echo "Input text:\n" . $text . "\n\n";

    if ($format === 'old_format') {
        if (! preg_match('/No\.Kartu[^\:]*:\s*([0-9]+)/i', $text, $m)) {
            // Alternative: find No. Kartu line and get number from next line
            $lines = explode("\n", $text);
            $bpjsNumber = null;

            foreach ($lines as $index => $line) {
                if (strpos($line, 'No. Kartu') !== false && strpos($line, 'BPJS') === false) {
                    // Get BPJS card number from next line
                    if ($index + 1 < count($lines)) {
                        $nextLine = trim($lines[$index + 1]);
                        if (preg_match('/([0-9]{13,})/', $nextLine, $bpjsMatch)) {
                            $bpjsNumber = $bpjsMatch[1];
                            break;
                        }
                    }
                }
            }

            if (!$bpjsNumber) {
                echo "ERROR: No. Kartu BPJS tidak ditemukan.\n\n";
                return false;
            }

            echo "SUCCESS: BPJS number extracted: " . $bpjsNumber . "\n\n";
            return $bpjsNumber;
        } else {
            echo "SUCCESS: BPJS number extracted: " . trim($m[1]) . "\n\n";
            return trim($m[1]);
        }
    } else {
        if (! preg_match('/No\.Kartu\s*BPJS\s*[:\s]+([0-9]+)/i', $text, $m)) {
            // Try alternative pattern without requiring colon after BPJS
            if (! preg_match('/No\.Kartu\s*BPJS\s+([0-9]+)/i', $text, $m)) {
                echo "ERROR: No. Kartu BPJS tidak ditemukan.\n\n";
                return false;
            }
        }
        echo "SUCCESS: BPJS number extracted: " . trim($m[1]) . "\n\n";
        return trim($m[1]);
    }
}

// Test old format - No. Kartu on one line, number on next line
$oldFormatText = "No.SEP: ABC123XYZ/2024
No. Kartu
1234567890123
Nama Peserta: John Doe
Tgl.SEP:
2024-01-15";

testBpjsExtraction($oldFormatText, 'old_format');

// Test new format - No. Kartu BPJS: number on same line
$newFormatText = "No.SEP Nomor: XYZ789DEF/2024
No. Kartu BPJS: 9876543210987
Nama Peserta: Jane Smith
Tgl.SEP: 15-01-2024";

testBpjsExtraction($newFormatText, 'new_format');

// Test edge case - No. Kartu with colon but number on next line
$edgeCaseText = "No.SEP: ABC123XYZ/2024
No. Kartu :
1234567890123
Nama Peserta: John Doe
Tgl.SEP:
2024-01-15";

testBpjsExtraction($edgeCaseText, 'old_format');

echo "All tests completed!\n";