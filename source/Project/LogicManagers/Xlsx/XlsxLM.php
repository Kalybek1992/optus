<?php

namespace Source\Project\LogicManagers\Xlsx;

use Source\Base\Constants\Settings\Path;
use Source\Base\Core\LogicManager;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Writer\Csv;


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

        // Итоговая строка
        $sheet->setCellValue('A' . $row, 'Сумма');
        $sheet->setCellValue('G' . $row, '=SUM(G2:G' . ($row - 1) . ')');

        // Статичное имя файла
        $upload_dir = Path::RESOURCES_DIR . 'unloading/';
        $file_name = 'TransactionMagazine.xlsx';
        $destination = $upload_dir . $file_name;

        // Если файл существует — удаляем
        if (file_exists($destination)) {
            unlink($destination);
        }

        // Сохраняем файл
        $writer = new Xlsx($spreadsheet);
        $writer->save($destination);

        return $file_name;
    }

    public static function transactionClientReceipts(array $transactions, array $transactions_sum): string
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        // Заголовки
        $headers = [
            '№',
            'Дата',
            'Плательщик',
            'Плательщик банк',
            'Получатель',
            'Получатель банк',
            'Комиссия %',
            'Сумма',
            'Комиссия ₽',
            'Долг',
            'Выдача',
            'Кто выдал',
            'Дата выдачи',
            'Комментарии'
        ];

        $col = 'A';
        foreach ($headers as $header) {
            $sheet->setCellValue($col . '1', $header);
            $col++;
        }

        // Данные транзакций
        $row = 2;
        foreach ($transactions as $t) {
            $sheet->setCellValue('A' . $row, $t['transaction_id']);
            $sheet->setCellValue('B' . $row, $t['date']);
            $sheet->setCellValue('C' . $row, $t['sender_company_name']);
            $sheet->setCellValue('D' . $row, $t['sender_bank_name']);
            $sheet->setCellValue('E' . $row, $t['recipient_company_name']);
            $sheet->setCellValue('F' . $row, $t['recipient_bank_name']);
            $sheet->setCellValue('G' . $row, $t['percent']);
            $sheet->setCellValue('H' . $row, $t['transaction_amount']);
            $sheet->setCellValue('I' . $row, $t['interest_income']);
            $sheet->setCellValue('J' . $row, $t['debit_amount']);
            $sheet->setCellValue('K' . $row, $t['issuance'] ?? '');
            $sheet->setCellValue('L' . $row, $t['who_issued_it'] ?? '');
            $sheet->setCellValue('M' . $row, $t['date_of_issue'] ?? '');
            $sheet->setCellValue('N' . $row, $t['comments'] ?? '');
            $row++;
        }

        // Итоговая строка
        $sheet->setCellValue('A' . $row, 'Сумма');
        $sheet->setCellValue('H' . $row, $transactions_sum['sum_amount'] ?? 0);
        $sheet->setCellValue('I' . $row, $transactions_sum['sum_interest_income'] ?? 0);
        $sheet->setCellValue('J' . $row, $transactions_sum['debts_amount'] ?? 0);

        // Можно добавить автоширину для читаемости
        foreach (range('A', 'N') as $columnID) {
            $sheet->getColumnDimension($columnID)->setAutoSize(true);
        }

        // Путь и имя файла
        $upload_dir = Path::RESOURCES_DIR . "unloading/";
        $file_name = "ClientReceipts.xlsx";
        $destination = $upload_dir . $file_name;

        if (file_exists($destination)) {
            unlink($destination);
        }

        $writer = new Xlsx($spreadsheet);
        $writer->save($destination);

        return $file_name;
    }




}

