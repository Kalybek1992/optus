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

        // Заголовки
        $headers = [
            '№',
            'Дата',
            'Плательщик название',
            'Плательщик банк',
            'Получатель название',
            'Получатель банк',
            'Сумма'
        ];

        // Записываем заголовки
        $col = 'A';
        foreach ($headers as $header) {
            $sheet->setCellValue($col . '1', $header);
            $col++;
        }

        // Записываем данные
        $row = 2;
        foreach ($transactions as $t) {
            $sheet->setCellValue('A' . $row, $t['transaction_id'] ?? '');
            $sheet->setCellValue('B' . $row, $t['date'] ?? '');
            $sheet->setCellValue('C' . $row, $t['sender_company_name'] ?? '');
            $sheet->setCellValue('D' . $row, $t['sender_bank_name'] ?? '');
            $sheet->setCellValue('E' . $row, $t['recipient_company_name'] ?? '');
            $sheet->setCellValue('F' . $row, $t['recipient_bank_name'] ?? '');
            $sheet->setCellValue('G' . $row, $t['transaction_amount'] ?? 0);

            // Wrap text для всех ячеек строки
            foreach (range('A', 'G') as $c) {
                $sheet->getStyle($c . $row)->getAlignment()->setWrapText(true);
            }

            $row++;
        }

        // Итоговая строка с суммой
        $sheet->setCellValue('A' . $row, 'Сумма');
        $sheet->setCellValue('G' . $row, '=SUM(G2:G' . ($row - 1) . ')');

        // Wrap text для итоговой строки
        foreach (range('A', 'G') as $c) {
            $sheet->getStyle($c . $row)->getAlignment()->setWrapText(true);
        }

        // Автоширина колонок
        foreach (range('A', 'G') as $columnID) {
            $sheet->getColumnDimension($columnID)->setAutoSize(true);
        }

        // Сохраняем файл
        $upload_dir = Path::RESOURCES_DIR . 'unloading/';
        $file_name = 'TransactionMagazine.xlsx';
        $destination = $upload_dir . $file_name;

        if (file_exists($destination)) {
            unlink($destination);
        }

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

    public static function clientServicesReceiptsDate(array $transactions, array $transactions_sum): string
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        // Заголовки (СТРОГО как в таблице)
        $headers = [
            'Дата',
            'Плательщик название',
            'Плательщик банк',
            'Получатель название',
            'Получатель банк',
            'Комиссия %',
            'Сумма',
            'Комиссия деньгах',
            'Долг клиенту',
            'Назначение',
        ];

        // Запись заголовков
        $col = 'A';
        foreach ($headers as $header) {
            $sheet->setCellValue($col . '1', $header);
            $col++;
        }

        // Данные
        $row = 2;
        foreach ($transactions as $t) {
            $sheet->setCellValue('A' . $row, $t['date'] ?? '');
            $sheet->setCellValue('B' . $row, $t['sender_company_name'] ?? '');
            $sheet->setCellValue('C' . $row, $t['sender_bank_name'] ?? '');
            $sheet->setCellValue('D' . $row, $t['recipient_company_name'] ?? '');
            $sheet->setCellValue('E' . $row, $t['recipient_bank_name'] ?? '');
            $sheet->setCellValue('F' . $row, $t['percent'] . '%');
            $sheet->setCellValue('G' . $row, $t['transaction_amount'] ?? 0);
            $sheet->setCellValue('H' . $row, $t['interest_income'] ?? 0);
            $sheet->setCellValue('I' . $row, $t['total_amount'] ?? 0);
            $sheet->setCellValue('J' . $row, $t['description'] ?? '');
            $row++;
        }


        $sheet->setCellValue('A' . $row, 'Сумма');
        $sheet->setCellValue('G' . $row, $transactions_sum['sum_amount'] ?? 0);
        $sheet->setCellValue('H' . $row, $transactions_sum['sum_interest_income'] ?? 0);
        $sheet->setCellValue('I' . $row, $transactions_sum['debts_amount'] ?? 0);

        // Автоширина колонок
        foreach (range('A', 'J') as $columnID) {
            $sheet->getColumnDimension($columnID)->setAutoSize(true);
        }

        // Сохранение
        $upload_dir = Path::RESOURCES_DIR . 'unloading/';
        $file_name = 'ClientServicesReceiptsDate.xlsx';
        $destination = $upload_dir . $file_name;

        if (file_exists($destination)) {
            unlink($destination);
        }

        $writer = new Xlsx($spreadsheet);
        $writer->save($destination);

        return $file_name;
    }

    public static function suppliersSendingsDate(array $transactions, array $transactions_sum): string
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        // Заголовки точно как в таблице
        $headers = [
            'Дата',
            'Плательщик названия',
            'Плательщик банк',
            'Получатель название',
            'Получатель банк',
            'Комиссия',
            'Сумма',
            'Комиссия деньгах',
            'Долг поставщика',
            'Назначение',
        ];

        // Запись заголовков
        $col = 'A';
        foreach ($headers as $header) {
            $sheet->setCellValue($col . '1', $header);
            $col++;
        }

        // Данные транзакций
        $row = 2;
        foreach ($transactions as $t) {
            $sheet->setCellValue('A' . $row, $t['date'] ?? '');
            $sheet->setCellValue('B' . $row, $t['sender_company_name'] ?? '');
            $sheet->setCellValue('C' . $row, $t['sender_bank_name'] ?? '');
            $sheet->setCellValue('D' . $row, $t['recipient_company_name'] ?? '');
            $sheet->setCellValue('E' . $row, $t['recipient_bank_name'] ?? '');
            $sheet->setCellValue('F' . $row, isset($t['percent']) ? $t['percent'] . '%' : '0%');
            $sheet->setCellValue('G' . $row, $t['transaction_amount'] ?? 0);
            $sheet->setCellValue('H' . $row, $t['interest_income'] ?? 0);
            $sheet->setCellValue('I' . $row, $t['total_amount'] ?? 0);
            $sheet->setCellValue('J' . $row, $t['description'] ?? '');
            $row++;
        }

        // Итоговая строка
        $sheet->setCellValue('A' . $row, 'Сумма');
        $sheet->setCellValue('B' . $row, '');
        $sheet->setCellValue('C' . $row, '');
        $sheet->setCellValue('D' . $row, '');
        $sheet->setCellValue('E' . $row, '');
        $sheet->setCellValue('F' . $row, '');
        $sheet->setCellValue('G' . $row, $transactions_sum['sum_amount'] ?? 0);
        $sheet->setCellValue('H' . $row, $transactions_sum['sum_interest_income'] ?? 0);
        $sheet->setCellValue('I' . $row, $transactions_sum['debts_amount'] ?? 0);
        $sheet->setCellValue('J' . $row, '');

        // Автоширина колонок
        foreach (range('A', 'J') as $columnID) {
            $sheet->getColumnDimension($columnID)->setAutoSize(true);
        }

        // Сохраняем файл
        $upload_dir = Path::RESOURCES_DIR . 'unloading/';
        $file_name = 'SuppliersSendingsDate.xlsx';
        $destination = $upload_dir . $file_name;

        if (file_exists($destination)) {
            unlink($destination);
        }

        $writer = new Xlsx($spreadsheet);
        $writer->save($destination);

        return $file_name;
    }

    public static function getCourierFinances(array $finances): string
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        // Заголовки точно как в таблице
        $headers = [
            'Дата',
            'День',
            'Сумма',
            'Категории',
            'От поставщика',
            'Комментарии',
            'Статус',
        ];

        // Записываем заголовки
        $col = 'A';
        foreach ($headers as $header) {
            $sheet->setCellValue($col . '1', $header);
            $col++;
        }

        // Записываем данные
        $row = 2;
        foreach ($finances as $f) {
            $sheet->setCellValue('A' . $row, $f['date'] ?? '');
            $sheet->setCellValue('B' . $row, $f['dey'] ?? '');
            $sheet->setCellValue('C' . $row, $f['amount'] ?? 0);
            $sheet->setCellValue('D' . $row, $f['category'] ?? '');
            $sheet->setCellValue('E' . $row, $f['supplier_name'] ?? '');
            $sheet->setCellValue('F' . $row, $f['comments'] ?? '');

            // Статус в читаемом виде
            $status = match($f['status'] ?? '') {
                'confirm_courier' => 'Не принят',
                'pending' => 'Не обработан',
                'processed' => 'Принят',
                default => 'Неизвестно',
            };
            $sheet->setCellValue('G' . $row, $status);

            $row++;
        }

        // Автоширина колонок
        foreach (range('A', 'G') as $columnID) {
            $sheet->getColumnDimension($columnID)->setAutoSize(true);
        }

        // Сохраняем файл
        $upload_dir = Path::RESOURCES_DIR . 'unloading/';
        $file_name = 'CourierFinances.xlsx';
        $destination = $upload_dir . $file_name;

        if (file_exists($destination)) {
            unlink($destination);
        }

        $writer = new Xlsx($spreadsheet);
        $writer->save($destination);

        return $file_name;
    }

    public static function getExpenses(array $expenses): string
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        // Заголовки точно как в таблице, последнюю колонку (Возврат) пропускаем
        $headers = [
            'Дата',
            'Описание',
            'Сумма',
            'Отправитель:Компания',
            'Отправитель:Банк',
            'Получатель:Компания',
            'Получатель:Банк',
            'Комментарии',
            'Категории',
        ];

        // Записываем заголовки
        $col = 'A';
        foreach ($headers as $header) {
            $sheet->setCellValue($col . '1', $header);
            $col++;
        }

        // Записываем данные
        $row = 2;
        foreach ($expenses as $e) {
            $sheet->setCellValue('A' . $row, $e['date'] ?? '');
            $sheet->setCellValue('B' . $row, $e['description'] ?? '');
            $sheet->setCellValue('C' . $row, $e['amount'] ?? 0);
            $sheet->setCellValue('D' . $row, $e['sender_company_name'] ?? '');
            $sheet->setCellValue('E' . $row, $e['sender_bank_name'] ?? '');
            $sheet->setCellValue('F' . $row, $e['company_name'] ?? '');
            $sheet->setCellValue('G' . $row, $e['bank_name'] ?? '');
            $sheet->setCellValue('H' . $row, $e['comments'] ?? '');
            $sheet->setCellValue('I' . $row, $e['category'] ?? '');
            $row++;
        }

        // Автоширина колонок
        foreach (range('A', 'I') as $columnID) {
            $sheet->getColumnDimension($columnID)->setAutoSize(true);
        }

        // Сохраняем файл
        $upload_dir = Path::RESOURCES_DIR . 'unloading/';
        $file_name = 'GetExpenses.xlsx';
        $destination = $upload_dir . $file_name;

        if (file_exists($destination)) {
            unlink($destination);
        }

        $writer = new Xlsx($spreadsheet);
        $writer->save($destination);

        return $file_name;
    }

    public static function getTransferYourself(array $transactions, array $transactions_sum): string
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        // Заголовки строго как в таблице
        $headers = [
            'Дата',
            'Описание',
            'Сумма',
            'Отправитель: Название компании',
            'Отправитель: Название банка',
            'Получатель: Название компании',
            'Получатель: Название банка',
        ];

        // Записываем заголовки в первую строку
        $col = 'A';
        foreach ($headers as $header) {
            $sheet->setCellValue($col . '1', $header);
            $col++;
        }

        // Записываем данные транзакций
        $row = 2;
        foreach ($transactions as $t) {
            $sheet->setCellValue('A' . $row, $t['date'] ?? '');
            $sheet->setCellValue('B' . $row, $t['description'] ?? '');
            $sheet->setCellValue('C' . $row, $t['transaction_amount'] ?? 0);
            $sheet->setCellValue('D' . $row, $t['sender_company_name'] ?? '');
            $sheet->setCellValue('E' . $row, $t['sender_bank_name'] ?? '');
            $sheet->setCellValue('F' . $row, $t['recipient_company_name'] ?? '');
            $sheet->setCellValue('G' . $row, $t['recipient_bank_name'] ?? '');
            $row++;
        }

        // Итоговая строка
        $sheet->setCellValue('A' . $row, 'Сумма');
        $sheet->setCellValue('B' . $row, '');
        $sheet->setCellValue('C' . $row, $transactions_sum['sum_amount'] ?? 0);
        $sheet->setCellValue('D' . $row, '');
        $sheet->setCellValue('E' . $row, '');
        $sheet->setCellValue('F' . $row, '');
        $sheet->setCellValue('G' . $row, '');

        // Автоширина колонок
        foreach (range('A', 'G') as $columnID) {
            $sheet->getColumnDimension($columnID)->setAutoSize(true);
        }

        // Сохраняем файл
        $upload_dir = Path::RESOURCES_DIR . 'unloading/';
        $file_name = 'TransferYourself.xlsx';
        $destination = $upload_dir . $file_name;

        if (file_exists($destination)) {
            unlink($destination);
        }

        $writer = new Xlsx($spreadsheet);
        $writer->save($destination);

        return $file_name;
    }

    public static function getExpensesStockBalances(array $expenses): string
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        $headers = [
            'Дата',
            'Описание',
            'Сумма',
            'Отправитель: Название компании',
            'Отправитель: Название банка',
            'Получатель: Название компании',
            'Получатель: Название банка',
        ];

        // Записываем заголовки
        $col = 'A';
        foreach ($headers as $header) {
            $sheet->setCellValue($col . '1', $header);
            $col++;
        }

        // Записываем данные
        $row = 2;
        foreach ($expenses as $e) {
            $sheet->setCellValue('A' . $row, $e['date'] ?? '');
            $sheet->setCellValue('B' . $row, $e['description'] ?? '');
            $sheet->setCellValue('C' . $row, $e['amount'] ?? 0);
            $sheet->setCellValue('D' . $row, $e['sender_company_name'] ?? '');
            $sheet->setCellValue('E' . $row, $e['sender_bank_name'] ?? '');
            $sheet->setCellValue('F' . $row, $e['company_name'] ?? '');
            $sheet->setCellValue('G' . $row, $e['bank_name'] ?? '');
            $row++;
        }

        // Автоширина колонок
        foreach (range('A', 'G') as $columnID) {
            $sheet->getColumnDimension($columnID)->setAutoSize(true);
        }

        // Сохраняем файл
        $upload_dir = Path::RESOURCES_DIR . 'unloading/';
        $file_name = 'GetExpensesStockBalances.xlsx';
        $destination = $upload_dir . $file_name;

        if (file_exists($destination)) {
            unlink($destination);
        }

        $writer = new Xlsx($spreadsheet);
        $writer->save($destination);

        return $file_name;
    }

    public static function archiveOfExtracts(array $our_accounts): string
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        // Заголовки, как в таблице
        $headers = [
            'Название компании',
            'Название банка',
            'Конечный остаток',
            'Дата остатка',
            'Количество транзакций',
            'Количество новых аккаунтов',
            'Количество обновленных аккаунтов',
            'Загрузка',
        ];

        // Запись заголовков
        $col = 'A';
        foreach ($headers as $header) {
            $sheet->setCellValue($col . '1', $header);
            $col++;
        }

        // Данные
        $row = 2;
        foreach ($our_accounts as $account) {

            $sheet->setCellValue('A' . $row, $account['company_name'] ?? '');
            $sheet->setCellValue('B' . $row, $account['bank_name'] ?? '');
            $sheet->setCellValue('C' . $row, $account['final_remainder'] ?? 0);
            $sheet->setCellValue('D' . $row, $account['date_created'] ?? '');
            $sheet->setCellValue('E' . $row, $account['transactions_count'] ?? 0);
            $sheet->setCellValue('F' . $row, $account['new_accounts_count'] ?? 0);
            $sheet->setCellValue('G' . $row, $account['bank_accounts_updated'] ?? 0);

            // Статус загрузки
            $status = !empty($account['is_expired']) ? 'Просрочено' : 'Загружено';
            $sheet->setCellValue('H' . $row, $status);

            $row++;
        }

        // Автоширина колонок
        foreach (range('A', 'H') as $columnID) {
            $sheet->getColumnDimension($columnID)->setAutoSize(true);
        }

        // Сохранение
        $uploadDir = Path::RESOURCES_DIR . 'unloading/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }

        $fileName = 'ArchiveOfExtracts.xlsx';
        $destination = $uploadDir . $fileName;

        if (file_exists($destination)) {
            unlink($destination);
        }

        $writer = new Xlsx($spreadsheet);
        $writer->save($destination);

        return $fileName;
    }

    public static function supplierClientReceiptsDate(array $transactions, array $transactions_sum): string
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
            'Комментарии',
            'Дата выдачи'
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
            $sheet->setCellValue('L' . $row, $t['comments'] ?? '');
            $sheet->setCellValue('M' . $row, $t['date_of_issue'] ?? '');
            $row++;
        }


        // Итоговая строка
        $sheet->setCellValue('A' . $row, 'Сумма');
        $sheet->setCellValue('H' . $row, $transactions_sum['sum_amount'] ?? 0);
        $sheet->setCellValue('I' . $row, $transactions_sum['sum_interest_income'] ?? 0);
        $sheet->setCellValue('J' . $row, $transactions_sum['debts_amount'] ?? 0);

        // Можно добавить автоширину для читаемости
        foreach (range('A', 'M') as $columnID) {
            $sheet->getColumnDimension($columnID)->setAutoSize(true);
        }

        // Путь и имя файла
        $upload_dir = Path::RESOURCES_DIR . "unloading/";
        $file_name = "SupplierClientReceiptsDate.xlsx";
        $destination = $upload_dir . $file_name;

        if (file_exists($destination)) {
            unlink($destination);
        }

        $writer = new Xlsx($spreadsheet);
        $writer->save($destination);

        return $file_name;
    }


}

