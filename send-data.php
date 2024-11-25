<?php
// Получаем данные из POST-запроса
$data = json_decode(file_get_contents('php://input'), true);

//// Логируем полученные данные
//file_put_contents('log.txt', print_r($data, true), FILE_APPEND);

// Проверяем, что все данные существуют
if (empty($data['name']) || empty($data['phone']) || empty($data['address']) || empty($data['tree'])) {
    echo json_encode(['success' => false, 'message' => 'Все поля должны быть заполнены!']);
    exit;
}

// Извлекаем данные
$name = $data['name'];
$phone = $data['phone'];
$address = $data['address'];
$tree = $data['tree'];

// Проверка формата телефона
if (!preg_match('/^\+7\d{10}$/', $phone)) {
    echo json_encode(['success' => false, 'message' => 'Номер телефона должен начинаться с +7 и содержать 11 цифр.']);
    exit;
}

// Подключение к базе данных (MySQL пример)
$servername = "localhost:3306";
$username = "blossom2_Timur";
$password = "root1234$";
$dbname = "blossom2_christmas_tree";

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    die(json_encode(['success' => false, 'message' => "Ошибка подключения к базе данных: " . $conn->connect_error]));
}

// Сохраняем заказ в базе данных с подготовленным выражением
$stmt = $conn->prepare("INSERT INTO orders (name, phone, address, tree) VALUES (?, ?, ?, ?)");
$stmt->bind_param("ssss", $name, $phone, $address, $tree);

if ($stmt->execute()) {
    // Отправка данных в Telegram
    $telegramToken = "7112223381:AAHO_RBIpe0xwk-e8Cv_O_xKOAtpccQKUEI";
    $chatId = "466165034";

    $message = "📦 Новый заказ:\n";
    $message .= "Имя: $name\n";
    $message .= "Телефон: $phone\n";
    $message .= "Адрес: $address\n";
    $message .= "Товар: " . ($tree ? $tree : "Не указан");

    $telegramUrl = "https://api.telegram.org/bot$telegramToken/sendMessage";
    $params = [
        'chat_id' => $chatId,
        'text' => $message
    ];

    // Используем cURL для отправки сообщения в Telegram
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $telegramUrl);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $params);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_exec($ch);
    curl_close($ch);

    // Ответ в формате JSON
    echo json_encode(['success' => true, 'message' => 'Заказ успешно оформлен!']);
} else {
    // Если не удалось выполнить запрос
    echo json_encode(['success' => false, 'message' => 'Ошибка при сохранении заказа в базе данных.']);
}

// Закрываем соединение с базой данных
$conn->close();
