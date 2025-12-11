<?php
// Test file to check SCENTS array
require_once __DIR__ . "/includes/config.php";

$SCENTS = [
  ["th"=>"สีขาว. กลิ่นต้นตำรับ",   "en"=>"Original",             "code"=>"001"],
  ["th"=>"สีเหลือง. กลิ่นไพล",        "en"=>"Zingiber cassumunar", "code"=>"002"],
  ["th"=>"สีเขียว. กลิ่นเสลดพังพอน",  "en"=>"Barleria Oil",        "code"=>"003"],
  ["th"=>"สีเขียว. กลิ่นตะไคร้หอม",  "en"=>"Lemongrass",          "code"=>"004"],
  ["th"=>"สีเขียว. กลิ่นหญ้าเอ็นยืด", "en"=>"Plantain",            "code"=>"005"],
  ["th"=>"สีม่วง. กลิ่นลาเวนเดอร์",   "en"=>"Lavender",            "code"=>"006"],
  ["th"=>"สีขาว. กลิ่นยูคาลิปตัส",    "en"=>"Eucalyptus",          "code"=>"007"],
  ["th"=>"สีขาว. กลิ่นมะลิ",          "en"=>"Jasmine",             "code"=>"008"],
  ["th"=>"สีชมพู. กลิ่นกุหลาบ",       "en"=>"Rose",                "code"=>"009"],
  ["th"=>"สีเหลืองอ่อน. กลิ่นขิงมินท์", "en"=>"Ginger Mint",       "code"=>"010"],
  ["th"=>"สีขาว. กลิ่นดอกโมก",        "en"=>"Water jasmine",       "code"=>"011"],
  ["th"=>"สีขาว. กลิ่นน้ำมันมะพร้าว", "en"=>"Coconut Oil",         "code"=>"012"],
];

echo "<!DOCTYPE html><html><head><meta charset='utf-8'><title>Test Scents</title></head><body>";
echo "<h1>Total Scents: " . count($SCENTS) . "</h1>";
echo "<ol>";
foreach($SCENTS as $i => $s) {
    echo "<li>" . ($i+1) . ". " . htmlspecialchars($s['th']) . " (" . htmlspecialchars($s['en']) . ") - Code: " . $s['code'] . "</li>";
}
echo "</ol>";
echo "<p>Timestamp: " . date('Y-m-d H:i:s') . "</p>";
echo "</body></html>";
?>
