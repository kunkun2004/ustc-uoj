<?php
require 'vendor/autoload.php';

// use PHPExcel;
// use PHPExcel_IOFactory;
// use PHPExcel_Style_Alignment;

// // 创建一个新的 PHPExcel 对象
// $objPHPExcel = new PHPExcel();
// $sheet = $objPHPExcel->getActiveSheet();

// // 设置表头
// $sheet->setCellValue('A1', '列1');
// $sheet->setCellValue('B1', '列2');
// $sheet->setCellValue('C1', '列3');

// // 写入数据行
// for ($i = 1; $i <= 10; $i++) {
//     $row = "行{$i}\n列1";
//     $sheet->setCellValue("A" . ($i + 1), $row);
//     $sheet->getStyle("A" . ($i + 1))->getAlignment()->setWrapText(true);

//     $row = "行{$i}\n列2";
//     $sheet->setCellValue("B" . ($i + 1), $row);
//     $sheet->getStyle("B" . ($i + 1))->getAlignment()->setWrapText(true);

//     $row = "行{$i}\n列3";
//     $sheet->setCellValue("C" . ($i + 1), $row);
//     $sheet->getStyle("C" . ($i + 1))->getAlignment()->setWrapText(true);
// }

// // 设置文件名和内容类型
// $filename = "example.xlsx";
// header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
// header('Content-Disposition: attachment; filename="' . $filename . '"');
// header('Cache-Control: max-age=0');

// // 创建 Excel 文件写入器并输出到浏览器
// $writer = PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel2007');
// $writer->save('php://output');
?>