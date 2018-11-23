<?php
// Функция, которая подсчитывает  общее число записей в таблице
function get_total_entries_count($db_link, $table_name)
    {
        // Подсчитываю, сколько всего вообще записей в таблице guestbook_entry, -- это нужно для постраничного выведения
        $entry_fields_total_count_result=mysqli_query($db_link, 'SELECT COUNT(*) FROM '.$table_name.'');
        if (!$entry_fields_total_count_result)
        {
            echo'</br> Ошибка запроса к базе данных: '.mysqli_error($db_link).'</br>';
            exit;
        }
        else
        {
            $entry_fields_total_count_fetch=mysqli_fetch_array($entry_fields_total_count_result);

        }
        mysqli_free_result($entry_fields_total_count_result);
        // Собственно я получаю массив из 1 элемента и по ключу [0] узнаю результат
        // запроса выше 'SELECT COUNT(*) FROM '.$table_name.''
        return $entry_fields_total_count_fetch[0];
    }


// Функция, которая вытаскивает из базы данных записи в гостевой книге. Возвращает: массив с этими записями.
function get_entries_from_guestbook_entry($db_link, $start_page, $entries_offset, $sorting_order, $sorting_type)
    {

        //echo '</br> Стартовая страница тест в функции:'.$start_page;
        //Сначала ищу в таблице guestbook_entry_fields поля, которые отмечены как видимые для показа в гостевой книге.
        //Видимость обозначена как: output_visible=1. Потом я из этих полей составляю второй запрос к таблице
        //guestbook_entry, чтобы не всё поля оттуда выводить, а только нужные мне.

        $needed_fields_result=mysqli_query($db_link, 'SELECT field_name FROM guestbook_entry_fields WHERE output_visible=1');
        if(!$needed_fields_result)
            {
                echo'</br> Ошибка запроса к базе данных: '.mysqli_error($db_link).'</br>';
                exit;
            }
        else
            {
                $needed_fields_fetch=mysqli_fetch_all($needed_fields_result, MYSQLI_ASSOC);
                // $needed_fields_string , -- это строка, в которую я записываю названия полей для последующего запроса.
                // foreach одинарный, т.к. знаю точно, какой указатель массива нужен: 'field_name' т.к.
                // в строку записываю $value['field_name'] с запятыми и пробелами, потом лишние запятые/пробелы убираю.
                $needed_fields_string='';
                foreach($needed_fields_fetch as $key=>$value)
                {

                    $needed_fields_string.=$value['field_name'].', ';

                }
                //Удаляю лишнюю запятую с пробелом ', ' из конца строки, чтобы был правильный синтаксис запроса
                $needed_fields_string=trim($needed_fields_string, ', ');
            }


        // Делаю фильтрацию методов сортировки, чтобы ВДРУГ ничего не могли мне прислать в базу данных
        // Сначала пишу массив с допустимыми параметрами сортировки для SQL запроса .
        // Потом проверяю если то, что пришло со страницы сайта совпадает с одним из способов сортировки (см. массив)
        // то сортирую. Если не совпадает, то ставлю сортировку по умолчанию по времени добавления, самые новые - на первой
        // странице, самые старые на последней
        $sort_order_array=array(
            '0'=>'timedate',
            '1'=>'username',
            '2'=>'email'
        );
        // Небольшое учебное извращение, проще было написать foreach и забыть.
        // reset - перевод ключа массива в начало, current($array) -- текущий ЭЛЕМЕНТ массива , next - перевод ключа массива
        // на одну позицию вперёд
        // Теперь про сортировку записей, по какому полю их сортировать для вывода на страницу сайта
        // Перед этим выставляю $sorting_order_cleared по умолчанию. Если пришло какое-то другое значение, и оно совпало
        // с допустимыми ( а они хрантяся в заранее предопределённом массиве) , то присваиваю вот это новое
        // если нет, тогда оставляю по умолчанию т.е. timedate
        $sorting_order_cleared='timedate';
        reset($sort_order_array);
        while(current($sort_order_array))
            {
                //echo current($sort_order_array);
                if($sorting_order==current($sort_order_array))
                    {
                        $sorting_order_cleared=current($sort_order_array);
                    }
                next($sort_order_array);
            }
        // Фильтрую, по убыванию я сортирую или по возрастанию. $sorting_type по умолчанию, DESC т.е. по убывани.
        // Довольно тупо. Если sorting_type - up , то делаем инкримент $sorting_type_cleared='INC'. во всех других случаях, в т.ч. если там попытались
        // передать кривые данные, делаем декремент $sorting_type_cleared='DESC' . Всё.
        $sorting_type_cleared='DESC';
        if($sorting_type=='up')
            {
                $sorting_type_cleared='ASC';
            }
        else
            {
                $sorting_type_cleared='DESC';
            }


        $start_field=$start_page*$entries_offset;
        $result=mysqli_query($db_link, 'SELECT '.$needed_fields_string.' FROM guestbook_entry ORDER BY '.$sorting_order_cleared.' '.$sorting_type_cleared.' LIMIT '.$start_field.' ,'.$entries_offset.' ');
        if(!$result)
            {
                echo'</br> Ошибка запроса к базе данных: '.mysqli_error($db_link).'</br>';
                exit;
            }
        else
            {
                $function_return = mysqli_fetch_all($result, MYSQLI_ASSOC);
                // Собственно, выводит функция просто массив, состоящий из СЮРПРИЗ массивов. Вложенные массивы это и есть строки таблицы.
                // То есть каждая строка таблицы представлена в виде массива. В index.php я уже их разбираю и обрабатываю, чтобы красиво
                // выводить на экран.


                mysqli_free_result($result);
                mysqli_free_result($needed_fields_result);
                return $function_return;
            }
    }
// Функция, которая выводит поля для того, что нам нужно. Конкретно здесь, -- это поля для ввода формы
// Слушайте, а тут можно же сделать так, чтобы она поля выводила
// И для ввода данных и для вывода, только входящие менять типа function get_fileds...($db_link, ВВОД_ФОРМЫ) или тут
// function get_fields...($db_link, ВЫВОД_ФОРМЫ)
function get_fields_for_entry_form($db_link)
    {
        $input_fields_result=mysqli_query($db_link, 'SELECT field_name, field_html_name, field_type, input_obligatory FROM guestbook_entry_fields WHERE input_visible=1');
        if(!$input_fields_result)
            {
                echo'</br> Ошибка запроса к базе данных: '.mysqli_error($db_link).'</br>';
                exit;
            }
        else
            {
                $input_fields_fetch=mysqli_fetch_all($input_fields_result, MYSQLI_ASSOC);

                mysqli_free_result($input_fields_result);
                return $input_fields_fetch;
            }
    }
// Это функция проверки , заполнены ли обязательные для заполнения поля. Извините за тавтологию. Какие поля обязательные
// и при этом заполняемые самим пользователем, я узнаЮ обращаясь к базе данных, ну а потом сравниваю что мне принёс
// массив $_POST и там уже по обстоятельствам!
function guestbook_entry_obligatory_fields_error_check($db_link, $post)
    {

        $obligatory_fields_result=mysqli_query($db_link, 'SELECT field_name, field_html_name, field_type, input_obligatory FROM guestbook_entry_fields WHERE input_visible=1');
        if(!$obligatory_fields_result)
        {
            echo'</br> Ошибка запроса к базе данных: '.mysqli_error($db_link).'</br>';
            exit;
        }
        else
        {
            //Переменная, в которую я заношу текст об ошибках.
            $error_string='';
            $obligatory_fields_fetch=mysqli_fetch_all($obligatory_fields_result, MYSQLI_ASSOC);
            foreach($obligatory_fields_fetch as $key=>$value)
                {
                    // Начало проверки. Смотрю, если поле пустое и одновременно поставлен флаг, что в него нужно
                    // обязательно что-то написать. В этом случае выдаю ошибку, что поле не заполнено.
                    // С помощью trim($post[$value['field_name']]) проверяю, не ввёл ли пользователь одни пробелы
                    if(((trim($post[$value['field_name']])=='')&&$value['input_obligatory']==1))
                        {
                            $error_string.='Заполните поле  '.$value['field_html_name'].'!  </br>';
                            //echo '</br> тестирую что этов: '.$value['field_type'].'</br>';
                        }
                    // Продолжение проверки, если поле оказалось непустым, т.е. пользователь что-то туда написал
                    // Тогда смотрю на тип поля, и в соответствие с этим применяю к нему нужную обработку.

                    // Честно говоря, хотелось прямо сделать красиво, через filter_var и чтобы всё прямо динамически
                    // было и опции фильтра типа FILTER_VALIDATE_EMAIL  прямо брались из базы и подставлялись,
                    // но стандартные средства фильтруют только email и url ,
                    // а согласно ТЗ нужно ещё проверять username пользователя, чтобы были только
                    // буквы/цифры (только латиницей, но наверное добавлю и кирилицу, хотя там морока с буквой ё)
                    // и проверять то, чтобы в поле для ввода текста не было html тэгов.
                    // Упоминается, что как-то можно это сделать через callback в filter_var но как пока я не разобрался
                    // Поэтому буду делать по старинке!
                    elseif($post[$value['field_name']]!='')
                        {
                            // Всего проверять нужно 4 типа полей. Правильное имя(только буквы и цифры) ,
                            // правильный email, правильный url и правильный текст (отсутствие html тегов в поле для текста).

                            if($value['field_type']=='name_field_type')
                                {
                                    //регулярное выражение ~^[a-zA-Zа-яА-я0-9ёЁ]+$~u означает, что от начала ^ до конца $
                                    // строки мы встречаем только упомянутые диапазоны букв и цифр [a-zA-Zа-яА-Я0-9ёЁ]
                                    // при этом минимальный размер строки 1 символ , -- это обознчается + после квадратных
                                    // скобок диапазона.  Тильды ~ по краям обозначают начало и конец регулярного выражения,
                                    // вместо них можно было ставить # или / . ёЁ добавлены из-за подозрения на то, что в
                                    // а-я А-Я диапазон  буквы ё и Ё не включены
                                    //  ВАЖНО1111 добавлена u после ~
                                    // (то есть после конца регулярного выражения, уже в зоне, где опции можно ставить)
                                    // , латинская буква "u"  включает поддержку юникода, чтобы работали русские буквы.
                                    // В старых версиях php может не работать.
                                    // Модификатор u доступен в PHP 4.1.0 и выше для Unix-платформ, и в PHP 4.2.3 и выше для Windows платформ
                                    if(!preg_match('~^[а-яА-ЯёЁa-zA-Z0-9]+$~u', $post[$value['field_name']] ))
                                        {
                                            $error_string.='<span class="red_color">  В поле: '.$value['field_html_name'].' введены недопустимые символы </span></br>';
                                        }

                                }
                            elseif($value['field_type']=='email_field_type')
                                {
                                    // Тут применяю встроенную PHP функцию filter_var с фильтром по email --
                                    // FILTER_VALIDATE_EMAIL
                                    if(!filter_var($post[$value['field_name']], FILTER_VALIDATE_EMAIL))
                                        {
                                            $error_string.='<span class="red_color">  В поле: '.$value['field_html_name'].' введён недопустимый email</span></br>';
                                        }

                                }
                            elseif($value['field_type']=='url_field_type')
                                {
                                    // Безумная версия регулярки от Диего(?) https://gist.github.com/dperini/729294




                                    // Честно говоря, не самый лучший вариант, но регулярные выражения работают очень криво.
                                    // Что делаю тут. Смотрю, если после обратоки функцией filter_var с фильтром FILTER_SANITIZE_STRING (который теги сносит, это от XSS) строка с сайтом
                                    // изменила размер, значит там были теги и такое нельзя. Одновременно с этим делаю проверку url этой же функцией filter_var с фильтром
                                    // FILTER_VALIDATE_URL
                                    // По хорошему нужно просто всё почистить с помощью FILTER_SANITIZE_STRING и заносить в базу, НО в ТЗ написано о том, что ввод недопустим, то есть
                                    // необходимо добиться (как я понимаю) чтобы пользователь просто не мог вводить атакующие штуки физически.
                                    // var_dump( filter_var($post[$value['field_name']], FILTER_SANITIZE_STRING));
                                    if((!filter_var($post[$value['field_name']], FILTER_VALIDATE_URL))||(filter_var($post[$value['field_name']], FILTER_SANITIZE_STRING)!=$post[$value['field_name']]))
                                    {
                                        $error_string.='<span class="red_color">  В поле: '.$value['field_html_name'].' введён недопустимый сайт</span></br>';
                                    }

                                }
                            elseif($value['field_type']=='text_field_type')
                                {
                                    //Здесь проверяю, чтобы нельзя было вводить в текстовое поле html теги.
                                    // Проверка простая, вырезаю теги функцией и потом сравниваю, до и после.
                                    // Если значения не равны, то проверку поле не прошло
                                    if(strip_tags($post[$value['field_name']])!=$post[$value['field_name']])
                                        {
                                            $error_string.='<span class="red_color">  В поле: '.$value['field_html_name'].' нельзя использовать html теги </span></br>';
                                        }
                                }

                        }




                }
            mysqli_free_result($obligatory_fields_result);
            return $error_string;

        }


    }

// Функция проверки каптчи. Не хочу проверять каптчу внутри проверки полей т.е. в  function guestbook_entry_obligatory_fields_error_check,
// потому что тогда нарушается стройность
// автоматизированной проверки полей по таблице guestbook_entry_fields
function guestbook_entry_captcha_check($post)
    {
        if($_SESSION['captcha_code']==$post['captcha_code'])
        {
            $error_string='';
        }
        else
        {
            $error_string='<span class="red_color"> Каптча введена неверно, Вы -- робот (но это неточно).  </span> </br>';
        }
        return $error_string;
    }

// Функция, которая и заносит запись в базу данных
function guestbook_entry_database_input($db_link, $post)
    {
        // сначала получаю поля, которые я хочу записать в базу данных.
        // поля получаю из таблицы полей guestbook_entry_fields
        // вообще-то можно не заморачиваться с таким динамическим исполнением, но вот хочется.
        // input_automatically=0 в запросе нужен, чтобы не вытаскивать timedate и другие подобные поля
        // которые заносятся автоматически в БД при записи.

        $fields_result=mysqli_query($db_link, 'SELECT field_name FROM guestbook_entry_fields WHERE input_auto=0 ');
        // обрабатываю ошибки базы данных если ВДРУГ
        if(!$fields_result)
        {
            echo'</br> Ошибка запроса к базе данных: '.mysqli_error($db_link).'</br>';
            exit;
        }
        // если всё в порядке, то собственно основной код.
        else
        {
            //Объявляю переменные. $insert_columns это название столбцов,  в которые я буду заносить информацию, а
            // $insert_values это собственно значения. Обе этих переменных, -- строки, которые я собираю в цикле
            //  а потом вставляю в MYSQL запрос.
            $insert_columns='';
            $insert_values='';
            $fields_fetch=mysqli_fetch_all($fields_result, MYSQLI_ASSOC);
            foreach($fields_fetch as $key=>$value)
                {
                    // собственно собираю строки. в $insert_columns ставлю `` , в принципе обратные кавычки ``
                    // вокруг имён таблиц/стобцов обязательны только если имена таблиц/столбцов совпадают с специальными
                    // зарезервированными словами mysql (типа AND, OR и прочих).
                    // чтобы интерпретатор отличал где у нас названия, а где спец слова.
                    // в $insert_values палки с апострофами \' чтобы поставить апострофы ' в запрос.
                    // Добавляю запятые в качестве разделителей.
                    // В результате получаю что-то типа INSERT INTO `guestbook`_entry (`username`, `text`) VALUES('Иван', 'Привет!') .
                    // Ещё обрабатываю строковые переменные с помощью mysqli_real_escape_string,
                    // чтобы  как-то обезопасить от SQL инъекций.
                    // Если что непонятно, выведите запрос эхой, будет наглядно.
                    $insert_columns.='`'.$value['field_name'].'`, ';
                    $insert_values.='\''.mysqli_real_escape_string($db_link,$post[$value['field_name']]).'\', ';

                }
            // вот тут отрезаю запятую в конце, чтобы был правильный синтаксис запроса
            $insert_columns=trim($insert_columns, ', ');
            $insert_values=trim($insert_values, ', ');

            // Эха для тестирования MySQL запроса
            // echo 'INSERT INTO `guestbook_entry` ('.$insert_columns.')  VALUES ('.$insert_values.')';

            mysqli_query($db_link, 'INSERT INTO `guestbook_entry` ('.$insert_columns.') VALUES ('.$insert_values.')');

        }
        mysqli_free_result($fields_result);

    }
?>