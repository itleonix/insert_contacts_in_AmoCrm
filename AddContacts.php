<?php		//php version used 8.2.9, tested on MacOs

//если файл подключен, то устанавливаем значение переменных субдомена и ключа доступа для формирования запросов, иначе сообщение об ошибке
if ((include 'keys.php') == TRUE) {
    $subdomain = $subdom;
    $access_token = $access;                                                    
}   else {
    echo 'Error connecting keys.php file'.'\n';
}

$link = 'https://' . $subdomain . '.amocrm.ru/api/v4/contacts/custom_fields'; //Формируем URL для запроса с указанием метода к которому обращаемся
/** Формируем заголовки */
$headers = [
    'Authorization: Bearer ' . $access_token
];
/**
 * Нам необходимо инициировать запрос к серверу.
 * Воспользуемся библиотекой cURL (поставляется в составе PHP).
 * Вы также можете использовать и кроссплатформенную программу cURL, если вы не программируете на PHP.
 */
$curl = curl_init(); //Сохраняем дескриптор сеанса cURL
/** Устанавливаем необходимые опции для сеанса cURL  */
curl_setopt($curl,CURLOPT_RETURNTRANSFER, true);
curl_setopt($curl,CURLOPT_USERAGENT,'amoCRM-oAuth-client/1.0');
curl_setopt($curl,CURLOPT_URL, $link);
curl_setopt($curl,CURLOPT_HTTPHEADER, $headers);
curl_setopt($curl,CURLOPT_HEADER, false);
curl_setopt($curl,CURLOPT_SSL_VERIFYPEER, 1);
curl_setopt($curl,CURLOPT_SSL_VERIFYHOST, 2);
$out = curl_exec($curl); //Инициируем запрос к API и сохраняем ответ в переменную
$code = curl_getinfo($curl, CURLINFO_HTTP_CODE);
curl_close($curl);

$response = json_decode($out,true);

$contacts_fields = $response['_embedded']['custom_fields'];


$fields = array_flip(array(
        'PHONE', //Телефон. Варианты: WORK, WORKDD, MOB, FAX, HOME, OTHER
        'EMAIL' //Email. Варианты: WORK, PRIV, OTHER
    )
);

//Проставляем id этих полей из базы амо
foreach($contacts_fields as $field) {
    if(isset($fields[$field['code']])) {
        $fields[$field['code']] = $field['id'];
    }
}

$i = 1; //Объявление счетчика
$count = 49;    // Максимальное количество добавляемых записей до приостановке выполнения
$random_email = 'abcdefghijklmnopqrstuvwxyz';   //Набор символов для генерации email


// В цикле добавляется 20000 контактов. Добавляется каждый сгенерированный контакт, чтобы снизить нагрузку на сервер и увеличить время выполнения для того, чтобы
//не вызывать подозрений у сервера
while ($i <= 20000) { 

//Обработка дублирующихся записей с разным именем контакта
if ($i == 1000) {
    $num_first = mt_rand(1111111,9999999);
    $num_second = mt_rand(1111111,9999999);
    $email_first = substr(str_shuffle($random_email),0,10);
    $email_second = substr(str_shuffle($random_email),0,10);
} else {
    $random_num_first = mt_rand(1111111,9999999);
    $random_num_second = mt_rand(1111111,9999999);
    $random_email_first = substr(str_shuffle($random_email),0,10);
    $random_email_second = substr(str_shuffle($random_email),0,10);
}

//Генерация массива со значениями добавляемого контакта
$add_contacts['add'] = array(
    'first_name' => 'Контакт',
    'last_name' => (string)$i,
    'responsible_user_id' => 10054106,
    'custom_fields_values'=>array(
        array(
            'field_id' => (int)$fields['PHONE'],
            'values' => array(
                array(
                    'value' => '8983'.$rest = $i % 1000 == 0 ? $num_first : (string)$random_num_first
                    
                ),
                array(
                    'value' => '8983'.$rest = $i % 1000 == 0 ? $num_second : (string)$random_num_second
                    
                )
            )
        ),
        array(
            'field_id' => (int)$fields['EMAIL'],
            'values' => array(
                array(
                    'value' => ($rest = $i % 1000 == 0 ? $email_first : $random_email_first).'@test.ru'
                    
                ),
                array(
                    'value' => ($rest = $i % 1000 == 0 ? $email_second : $random_email_second).'@test.ru'
                    
                )
            )
        )
    )

);


$link = 'https://' . $subdomain . '.amocrm.ru/api/v4/contacts';
$curl = curl_init(); //Сохраняем дескриптор сеанса cURL
//Устанавливаем необходимые опции для сеанса cURL
curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
curl_setopt($curl, CURLOPT_USERAGENT, 'amoCRM-oAuth-client/1.0');
curl_setopt($curl, CURLOPT_URL, $link);
curl_setopt($curl, CURLOPT_CUSTOMREQUEST, 'POST');
curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($add_contacts)); //Созданный массив в json
curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
curl_setopt($curl, CURLOPT_HEADER, false);
curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, 1);
curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 2);
$out = curl_exec($curl);
$response=json_decode($out,true);


//На сервере установлено ограничение на добавление записей в БД (50 в сек.).
//Если добавлено 49 контактов, то скрипт приостанавливается на 1 минуту для более безопасной работы
if ($i == $count) {
    sleep(60);
    $count = $count + 49;
}

$i++;
}

print_r ($response);


?>