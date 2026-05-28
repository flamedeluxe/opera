<?php

declare(strict_types=1);

/**
 * Обработка формы обратной связи на /contacts/ (JSON для page-contacts.js).
 *
 * @return array{status: string, show_errors?: bool, errors?: list<string>}
 */
function uuopera_contacts_feedback_handle(): array
{
    if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
        return ['status' => 'error', 'show_errors' => true, 'errors' => ['Неверный метод запроса.']];
    }

    $name = trim((string) ($_POST['name'] ?? ''));
    $phone = trim((string) ($_POST['phone'] ?? ''));
    $message = trim((string) ($_POST['message'] ?? ''));
    $subject = trim((string) ($_POST['subject'] ?? 'Обратная связь'));
    $policy = trim((string) ($_POST['policy'] ?? ''));

    $errors = [];
    if ($name === '' || !preg_match('/^[а-яА-ЯёЁ\- ]{2,}$/u', $name)) {
        $errors[] = 'Укажите имя (только кириллица, не менее 2 символов).';
    }
    if ($phone === '' || !preg_match('/^\+7 \(\d{3}\) \d{3}-\d{2}-\d{2}$/', $phone)) {
        $errors[] = 'Укажите телефон в формате +7 (XXX) XXX-XX-XX.';
    }
    if ($policy === '') {
        $errors[] = 'Необходимо согласие на обработку персональных данных.';
    }
    if ($errors !== []) {
        return ['status' => 'error', 'show_errors' => true, 'errors' => $errors];
    }

    $to = 'uuopera@govrb.ru';
    if (class_exists(\Bitrix\Main\Config\Option::class)) {
        $opt = trim((string) \Bitrix\Main\Config\Option::get('uuopera', 'contacts_feedback_email', ''));
        if ($opt !== '' && filter_var($opt, FILTER_VALIDATE_EMAIL)) {
            $to = $opt;
        }
    }

    $body = "Тема: {$subject}\n"
        . "Имя: {$name}\n"
        . "Телефон: {$phone}\n"
        . "Сообщение:\n{$message}\n"
        . "\n---\n"
        . 'Отправлено: ' . date('Y-m-d H:i:s') . "\n"
        . 'IP: ' . ($_SERVER['REMOTE_ADDR'] ?? '') . "\n";

    $sent = uuopera_contacts_feedback_send_mail($to, $subject . ' — uuopera.ru', $body);

    if (!$sent) {
        return [
            'status' => 'error',
            'show_errors' => true,
            'errors' => ['Не удалось отправить сообщение. Попробуйте позже или напишите на ' . $to . '.'],
        ];
    }

    return ['status' => 'success'];
}

function uuopera_contacts_feedback_send_mail(string $to, string $mailSubject, string $body): bool
{
    if (class_exists(\Bitrix\Main\Mail\Mail::class)) {
        try {
            if ((bool) \Bitrix\Main\Mail\Mail::send([
                'TO' => $to,
                'SUBJECT' => $mailSubject,
                'BODY' => $body,
                'CHARSET' => 'UTF-8',
                'CONTENT_TYPE' => 'text/plain',
                'HEADER' => [],
            ])) {
                return true;
            }
        } catch (\Throwable) {
        }
    }

    $headers = "Content-Type: text/plain; charset=UTF-8\r\n";
    $encodedSubject = '=?UTF-8?B?' . base64_encode($mailSubject) . '?=';

    if (@mail($to, $encodedSubject, $body, $headers)) {
        return true;
    }

    $docRoot = rtrim((string) ($_SERVER['DOCUMENT_ROOT'] ?? ''), '/');
    if ($docRoot !== '') {
        $logFile = $docRoot . '/upload/contacts_feedback.log';
        $line = date('c') . "\n{$body}\n---\n";
        if (@file_put_contents($logFile, $line, FILE_APPEND | LOCK_EX) !== false) {
            return true;
        }
    }

    return false;
}

function uuopera_contacts_form_action_url(string $configured): string
{
    $configured = trim($configured);
    if ($configured === '' || str_contains($configured, '/wp-json/')) {
        return '/local/ajax/contacts-feedback.php';
    }

    return $configured;
}
