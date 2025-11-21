<?php

namespace Source\Project\LogicManagers\Xlsx;

use Source\Base\Constants\Settings\Path;
use Source\Base\Core\LogicManager;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;


class XlsxLM extends LogicManager
{

    public static function transactionMagazine(array $transactions): string
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        $headers = [
            '№',
            'Дата',
            'Плательщик название',
            'Плательщик банк',
            'Получатель название',
            'Получатель банк',
            'Сумма'
        ];

        $col = 'A';
        foreach ($headers as $header) {
            $sheet->setCellValue($col . '1', $header);
            $col++;
        }

        // Вставляем строки с данными
        $row = 2;
        foreach ($transactions as $t) {
            $sheet->setCellValue('A' . $row, $t['transaction_id']);
            $sheet->setCellValue('B' . $row, $t['date']);
            $sheet->setCellValue('C' . $row, $t['sender_company_name']);
            $sheet->setCellValue('D' . $row, $t['sender_bank_name']);
            $sheet->setCellValue('E' . $row, $t['recipient_company_name']);
            $sheet->setCellValue('F' . $row, $t['recipient_bank_name']);
            $sheet->setCellValue('G' . $row, $t['transaction_amount']);
            $row++;
        }

        // Добавляем строку "Сумма"
        $sheet->setCellValue('A' . $row, 'Сумма');
        $sheet->setCellValue('G' . $row, '=SUM(G2:G' . ($row - 1) . ')');

        $upload_dir = Path::RESOURCES_DIR . 'uploads/';
        $file_name = bin2hex(random_bytes(16)) . '.xlsx';
        $destination = $upload_dir . $file_name;

        // Сохраняем файл
        $writer = new Xlsx($spreadsheet);
        $writer->save($destination);


        return $file_name;
    }



}

