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
        foreach ($transactions as $index => $t) {
            $sheet->setCellValue('A' . $row, $t['transaction_id'] ?? ($index + 1));
            $sheet->setCellValue('B' . $row, $t['date'] ?? '');
            $sheet->setCellValue('C' . $row, $t['sender_company_name'] ?? '');
            $sheet->setCellValue('D' . $row, $t['sender_bank_name'] ?? '');
            $sheet->setCellValue('E' . $row, $t['recipient_company_name'] ?? '');
            $sheet->setCellValue('F' . $row, $t['recipient_bank_name'] ?? '');
            $sheet->setCellValue('G' . $row, $t['percent'] ?? 0);
            $sheet->setCellValue('H' . $row, $t['transaction_amount'] ?? 0);
            $sheet->setCellValue('I' . $row, $t['interest_income'] ?? 0);
            $sheet->setCellValue('J' . $row, $t['debit_amount'] ?? 0);

            // Работа с массивом issuance
            if (!empty($t['issuance']) && is_array($t['issuance'])) {
                $amounts = array_map(fn($i) => number_format($i['amount'] ?? 0, 0, ',', ' '), $t['issuance']);
                $whoIssued = array_map(fn($i) => $i['who_issued_it'] ?? 'Админ', $t['issuance']);
                $issueDates = array_map(fn($i) => isset($i['issue_date']) ? date('d.m.Y', strtotime($i['issue_date'])) : '', $t['issuance']);
                $comments = array_map(fn($i) => '- ' . ($i['comments'] ?? ''), $t['issuance']);

                $sheet->setCellValue('K' . $row, implode(', ', $amounts));
                $sheet->setCellValue('L' . $row, implode(', ', $whoIssued));
                $sheet->setCellValue('M' . $row, implode(', ', $issueDates));
                $sheet->setCellValue('N' . $row, implode("\n\n", $comments));
            } else {
                $sheet->setCellValue('K' . $row, 0);
                $sheet->setCellValue('L' . $row, 0);
                $sheet->setCellValue('M' . $row, 0);
                $sheet->setCellValue('N' . $row, '-');
            }

            $row++;
        }

        // Итоговая строка
        $sheet->setCellValue('A' . $row, 'Сумма');
        $sheet->setCellValue('H' . $row, $transactions_sum['sum_amount'] ?? 0);
        $sheet->setCellValue('I' . $row, $transactions_sum['sum_interest_income'] ?? 0);
        $sheet->setCellValue('J' . $row, $transactions_sum['debts_amount'] ?? 0);
        $sheet->setCellValue('K' . $row, $transactions_sum['debts_issuance'] ?? 0);

        // Автоширина колонок
        foreach (range('A', 'N') as $columnID) {
            $sheet->getColumnDimension($columnID)->setAutoSize(true);
        }

        // Сохраняем файл
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

        // Заголовки — строго как в верстке
        $headers = [
            'Дата',
            'Плательщик название',
            'Получатель название',
            'Комиссия',
            'Сумма',
            'Комиссия деньгах',
            'Долг поставщика',
            'Выдал товарные д-г',
            'Дата',
            'Назначение',
        ];

        // Заголовки
        $col = 'A';
        foreach ($headers as $header) {
            $sheet->setCellValue($col . '1', $header);
            $sheet->getStyle($col . '1')->getFont()->setBold(true);
            $col++;
        }

        // Данные
        $row = 2;
        foreach ($transactions as $t) {

            $sheet->setCellValue('A' . $row, $t['date'] ?? '');
            $sheet->setCellValue('B' . $row, $t['sender_company_name'] ?? '');
            $sheet->setCellValue('C' . $row, $t['recipient_company_name'] ?? '');
            $sheet->setCellValue('D' . $row, isset($t['percent']) ? $t['percent'] . '%' : '0%');
            $sheet->setCellValue('E' . $row, $t['transaction_amount'] ?? 0);
            $sheet->setCellValue('F' . $row, $t['interest_income'] ?? 0);
            $sheet->setCellValue('G' . $row, $t['total_amount'] ?? 0);

            // Товарные деньги + даты выдачи
            if (!empty($t['issuance'])) {
                $amounts = array_map(
                    fn ($i) => number_format($i['amount'], 0, ',', ' '),
                    $t['issuance']
                );

                $dates = array_map(
                    fn ($i) => date('d.m.Y', strtotime($i['issue_date'])),
                    $t['issuance']
                );

                $sheet->setCellValue('H' . $row, implode(', ', $amounts));
                $sheet->setCellValue('I' . $row, implode(', ', $dates));
            } else {
                $sheet->setCellValue('H' . $row, 0);
                $sheet->setCellValue('I' . $row, '-');
            }

            $sheet->setCellValue('J' . $row, $t['description'] ?? '');

            $row++;
        }

        // Итоговая строка
        $sheet->setCellValue('A' . $row, 'Сумма');
        $sheet->setCellValue('B' . $row, '');
        $sheet->setCellValue('C' . $row, '');
        $sheet->setCellValue('D' . $row, '');
        $sheet->setCellValue('E' . $row, $transactions_sum['sum_amount'] ?? 0);
        $sheet->setCellValue('F' . $row, $transactions_sum['sum_interest_income'] ?? 0);
        $sheet->setCellValue('G' . $row, $transactions_sum['debts_amount'] ?? 0);
        $sheet->setCellValue('H' . $row, $transactions_sum['debts_issuance'] ?? 0);
        $sheet->setCellValue('I' . $row, '');
        $sheet->setCellValue('J' . $row, '');

        // Автоширина
        foreach (range('A', 'J') as $columnID) {
            $sheet->getColumnDimension($columnID)->setAutoSize(true);
        }

        // Сохранение
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

        // Заголовки
        $headers = [
            'Название компании',
            'Название банка',
            'Конечный остаток',
            'Дата остатка',
            'Количество транзакций',
            'Количество новых аккаунтов',
            'Возврат (клиент)',
            'Возврат (поставщик)',
            'Возврат (услуги)',
            'Покупки (поставщик)',
            'Покупки (клиент)',
            'Покупки (услуги)',
            'Расходы',
            'Доходы',
            'Статус загрузки',
        ];

        $col = 'A';
        foreach ($headers as $header) {
            $sheet->setCellValue($col . '1', $header);
            $col++;
        }

        $row = 2;
        foreach ($our_accounts as $account) {
            $sheet->setCellValue('A' . $row, $account['company_name'] ?? '');
            $sheet->setCellValue('B' . $row, $account['bank_name'] ?? '');
            $sheet->setCellValue('C' . $row, $account['final_remainder'] ?? 0);
            $sheet->setCellValue('D' . $row, $account['date_created'] ?? '');
            $sheet->setCellValue('E' . $row, $account['transactions_count'] ?? 0);
            $sheet->setCellValue('F' . $row, $account['new_accounts_count'] ?? 0);
            $sheet->setCellValue('G' . $row, $account['client_returns_count'] ?? 0);
            $sheet->setCellValue('H' . $row, $account['supplier_returns_count'] ?? 0);
            $sheet->setCellValue('I' . $row, $account['client_services_returns_count'] ?? 0);
            $sheet->setCellValue('J' . $row, $account['goods_supplier'] ?? 0);
            $sheet->setCellValue('K' . $row, $account['goods_client'] ?? 0);
            $sheet->setCellValue('L' . $row, $account['goods_client_service'] ?? 0);
            $sheet->setCellValue('M' . $row, $account['expenses'] ?? 0);
            $sheet->setCellValue('N' . $row, $account['income'] ?? 0);
            $status = !empty($account['is_expired']) ? 'Просрочено' : 'Загружено';
            $sheet->setCellValue('O' . $row, $status);

            $row++;
        }

        foreach (range('A', 'O') as $columnID) {
            $sheet->getColumnDimension($columnID)->setAutoSize(true);
        }

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

            if (!empty($t['issuance']) && is_array($t['issuance'])) {

                $amounts = [];
                $comments = [];
                $dates = [];

                foreach ($t['issuance'] as $i) {
                    if (!empty($i['amount'])) {
                        $amounts[] = number_format($i['amount'], 0, ',', ' ');
                    }
                    if (!empty($i['comments'])) {
                        $comments[] = $i['comments'];
                    }
                    if (!empty($i['issue_date'])) {
                        $dates[] = date('d.m.Y', strtotime($i['issue_date']));
                    }
                }

                $sheet->setCellValue('K' . $row, implode(', ', $amounts));
                $sheet->setCellValue('L' . $row, implode(', ', $comments));
                $sheet->setCellValue('M' . $row, implode(', ', $dates));

            } else {
                $sheet->setCellValue('K' . $row, '');
                $sheet->setCellValue('L' . $row, '');
                $sheet->setCellValue('M' . $row, '');
            }

            $row++;
        }

        // Итоговая строка
        $sheet->setCellValue('A' . $row, 'Сумма');
        $sheet->setCellValue('H' . $row, $transactions_sum['sum_amount'] ?? 0);
        $sheet->setCellValue('I' . $row, $transactions_sum['sum_interest_income'] ?? 0);
        $sheet->setCellValue('J' . $row, $transactions_sum['debts_amount'] ?? 0);

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

