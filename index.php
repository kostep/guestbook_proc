<?php
////TestTestTest

session_start();
// header.php : файл с  началом html, заголовком и кодировкой.
include_once('header.php');
// db_connection.php : файл с инфой о базе данных.
include_once('db_connection.php');
// functions.php : файл с , собственно, всякими разными функциями.
include_once('functions.php');
// Подключаюсь к базе данных и обрабатываю ошибки если что.
$db_link=mysqli_connect($host,$username,$password,$db_name);

// Самое начало, связываюсь с базой данных. Если всё работает, то окей!
if(!$db_link)
{
    echo '</br>';
    echo 'Ошибка, невозможно установить соединение с базой данных! </br>';
    echo 'Код ошибки errno: '.mysqli_connect_errno().'</br>';
    echo 'Текст ошибки error: '.mysqli_connect_error().'</br> </br>';
}
// Если ошибок нет, то, собственно, в этом else лежит основной код.
else
{
    // Начало div id=header
    echo'<div id="header">';
    
// Получаю информацию о сервере базы данных и о кодировке.
    echo 'Соединение с сервером установлено! </br>
         Командующий, веду передачу! </br>';


    echo 'Информация о сервере: '.mysqli_get_host_info($db_link).'</br>';
    echo 'Информация о текущей кодировке: '.mysqli_character_set_name($db_link).'</br></br>';

// Если кодировка не utf8, то принудительно перекодирую обмен информацией. Во имя бога-машины!
    if(mysqli_character_set_name($db_link)!='utf8')
    {
        mysqli_set_charset($db_link, 'utf8');
        echo 'Кодировка заменена на : '.mysqli_character_set_name($db_link).'</br></br>';
    }
    echo'</div>';
    //Конец div id=header


//Предварительная подготовка для сортировки, обрабатываю данные из $_GET
    // Я должен учитывать :
    // 1) На какой странице находится пользователь, -- это с помощью _$GET узнаю
    // 2) По сколько записей ему выдавать, -- это задаю руками, хотя в прицнипе можно где-то динамически через базу
    // 3) По каким полям сортировать. По умолчанию должно быть так :  самые свежие записи на первой странице,
    // а самые старые записи на последней.
    // Узнаю, есть ли вообще $_GET['page'], если переменной такой ещё нет, то ставим в переменную  с которой буду работать, 0
    // т.е. стартовая страница, если в $_GET['page']  что-то есть, то убираю оттуда (всё кроме цифр и +-) с помощью
    // filter_var( ... , FILTER_SANITIZE_NUMBER_INT)(это если бы была попытка взлома как-бы)
    // и уже с очищенной переменной работаю.


    // Узнаю, сколько всего записей в таблице guestbook_entry
    $total_entries_count=get_total_entries_count($db_link, 'guestbook_entry');

    // $page_offset -- сколько показывать записей за раз
    $entries_offset=5;
    // Дальше обрабатываю данные из $_GET см выше.
    if(((!isset($_GET['page']))or($_GET['page']<0))&&(!isset($_POST['page'])))
        {
            $current_page=0;
        }
    elseif(isset($_GET['page']))
        {
            // Фильтрую, чтобы осталось только целое число, мало ли что там через строку подсунули
            $current_page=intval($_GET['page']);
        }
    elseif(isset($_POST['page']))
        {

            $current_page=intval($_POST['page']);

        }

    // Потом проверяю, не подставили ли через $_GET/$POST слишком большое значение страницы, если это случилось
    // вывожу самую последнюю страницу. Её определяю путём арифметического колдунства.
    if($current_page*$entries_offset>=$total_entries_count)
    {

        $current_page=floor($total_entries_count/$entries_offset);

        if($current_page*$entries_offset==$total_entries_count)
        {
            $current_page=$current_page-1;
        }

    }
    // floor выше возвращает тип float , поэтому проезжась ещё раз intval, исключительно из
    // эстетических соображений.
    $current_page=intval($current_page);




    // Обрабатываю данные из $_GET ['sort'] и $_GET['type'] . Учитывая, что дальше у меня в функции выдачи
    // get_entries_from_guestbook_entry будет жёсткий выбор из значений белого списка сортировки,
    // то смысла в filter_var(...) нет, но в рамках паранойи...
    // СДЕЛАТЬ белый список по $_GET['type']

    if(!isset($_GET['sort'])&&!isset($_POST['sort']))
        {
            $sorting_order='timedate';
        }
    elseif(isset($_GET['sort']))
        {
            $sorting_order=filter_var($_GET['sort'] ,FILTER_SANITIZE_STRING);
        }
    elseif(isset($_POST['sort']))
        {
            $sorting_order=filter_var($_POST['sort'] ,FILTER_SANITIZE_STRING);

        }
    if(!isset($_GET['type'])&&!isset($_POST['type']))
        {
            $sorting_type='down';
        }
    elseif(isset($_GET['type']))
        {
            $sorting_type=filter_var($_GET['type'] ,FILTER_SANITIZE_STRING);
        }
    elseif(isset($_POST['sort']))
        {
            $sorting_type=filter_var($_POST['type'] ,FILTER_SANITIZE_STRING);

        }


    // Вот тут я и показываю записи из гостевой.

    //Начало div id= upper т.е. div вывовода записей из гостевой
    // Функция get_entries_from_guestbook_entry вытаскивает из базы запрашиваемые данные, учитывая сортировку и номер
    // страницы
    echo'<div id="upper">';
    $array_of_entries=get_entries_from_guestbook_entry($db_link ,$current_page, $entries_offset, $sorting_order, $sorting_type);
    $ku=1;
    foreach($array_of_entries as $key=>$value)
    {
        // Чтобы не писать для каждого значения, делаю filter_var_array. Нужно если в базу всё-таки пролез
        // html/javascript код. Чтобы он не исполнялся на странице.
        // Хотел сделать красиво, чтобы в динамике выдавалась инфа из базы данных, вне зависимости от html
        // кода, но для "красивости" вёрстки решил всё-таки по тупому делать.
        filter_var_array($value, FILTER_SANITIZE_FULL_SPECIAL_CHARS);
        echo'
                    <div class="entry_container">
                        <div class="entry_left">
                            <p class="username"><span class="username_title">Автор: </span>'.$value['username'].'</p>
                            <p class="email"><span class="email_title">Почта: </span>'.$value['email'].'</p>
                            <p class="site"><span class="site_title">Сайт: </span><!--<a href="'.$value['homepage'].'">-->'.$value['homepage'].'</p>
                            <p class="entry_date"><span class="entry_date_title">Отправлено: </span>'.$value['timedate'].'</p>
                        </div>
                        <div class="entry_right">
                            <p class="entry_text_title">Сообщение:</p> <p class="entry_text"> '.nl2br($value['text']).'</p>
                        </div>
                    </div>';

    }
    echo'<p class="links">
            <a href="index.php?sort=timedate&type=down&page='.$current_page.'">убыв</a>| дата |<a href="index.php?sort=timedate&type=up&page='.$current_page.'">возр</a>
            <a href="index.php?sort=username&type=down&page='.$current_page.'">убыв</a>| имя |<a href="index.php?sort=username&type=up&page='.$current_page.'">возр</a>
            <a href="index.php?sort=email&type=down&page='.$current_page.'">убыв</a>| email |<a href="index.php?sort=email&type=up&page='.$current_page.'">возр</a>
            </br></br>';
    if(($current_page-1)>=0)
    {
        echo'
                <a href="index.php?sort='.$sorting_order.'&type='.$sorting_type.'&page='.($current_page-1).'"> <-- предыдущая страница </a> ';
    }
    // Тут разделитель в вёрстке вывожу, если и показываю и ту и другую ссылку.
    if((($current_page-1)>=0)&&($total_entries_count>($current_page+1)*$entries_offset))
    {
        echo '||';
    }
    if($total_entries_count>($current_page+1)*$entries_offset)
    {
        echo'
                <a href="index.php?sort='.$sorting_order.'&type='.$sorting_type.'&page='.($current_page+1).'">  следующая страница --> </a>';
    }

    echo '</p>';

    echo'</div>';
    //Конец div id=upper т.е. div с выводом записей из гостевой



//тут должно быть несколько if_else

    //Первый if_else если у нас  $_POST пусто, значит в форму ничего не вводил пользователь. Показываю пустую форму.
    if(empty($_POST))
    {

        // Собственно выводим необходимые поля, разбирая массив, из моей функции get_fields_for_entry_form для вывода полей.
        $array_of_fields=get_fields_for_entry_form($db_link);
        // Это переменная obligatory_start, которая превращается из пустой в звёздочку, если поле формы обязательно для заполнения.
        $obligatory_star='';
        // Начало div id=under
        echo'
        <div id="under">
            <div id="error_box">
            <span class="error_text">
            <!--
                Ошибка,раз! <br>
                Ошибка, два!<br>
                Ошибка, три! <br>
                Ошибка, четыре!<br>
                Ошибка, пять!<br> -->
            </span>
            </div>
    
        <div id="form_entry">
            <form class="form_one" method="post" action="index.php">
            <div class="form_inner_box">
                <div class="form_left_box"><span class="form_title">Введите имя/псевдоним<span class="red_color">*</span>:</span></div>
                <div class="form_right_box"><input type="text" name="username" size="40" maxlength="100"></input></div>
            </div>
            <div class="form_inner_box">
                <div class="form_left_box"><span class="form_title">Введите email<span class="red_color">*</span>:</span></div>
                <div class="form_right_box"><input type="text" name="email" size="40" maxlength="100"></input></div>
            </div>
            <div class="form_inner_box">
                <div class="form_left_box"><span class="form_title">Введите Ваш сайт:</span></div>
                <div class="form_right_box"><input type="text" name="homepage" size="40" maxlength="100"></input></div>
            </div>
            <div class="form_inner_textarea_box">
                <div class="form_left_box"><span class="form_title">Введите текст сообщения<span class="red_color">*</span>:</span></div>
                <div class="form_textarea_box"><textarea name="text" style="width: 80%; height: 80%; resize: none;"></textarea></div>
            </div>
            <div class="form_inner_box">
                <div class="form_left_box"><span class="form_title">Введите каптчу<span class="red_color">*</span>:</span></div>
                <div class="form_captcha_text_box"><input type="text" name="captcha_code" size="20" maxlength="20"></input></div>
                <div class="form_captcha_image_box"><img src="captcha.php"></div>
            </div>
            <div class="form_inner_box">
                <div class="form_button_box"><button  class="button_style_1" type="submit">Отправить сообщение</button></div>
            </div>
            <input type="hidden" name="browser"  value="'.htmlspecialchars($_SERVER['HTTP_USER_AGENT']).'">
            <input type="hidden" name="ip" value="'.htmlspecialchars($_SERVER['REMOTE_ADDR']).'">
            <input type="hidden" name="sort" value="'.$sorting_order.'">
            <input type="hidden" name="type" value="'.$sorting_type.'">
            <input type="hidden" name="page" value="'.$current_page.'">            
            </form>
        </div>

        </div>';
        // Конец div id=under

    }

    //Второй elseif . ЕСЛИ в $_POST что-то есть, значит  форму заполнил пользователь, и теперь нужно бы проверить заполнили ли её целиком.
    // Если всё хорошо, то в конце этого elseif заношу данные в базу!
    elseif(!empty($_POST))
        {   $guestbook_entry_error_check=guestbook_entry_obligatory_fields_error_check($db_link,$_POST);
            $guestbook_entry_error_check.=guestbook_entry_captcha_check($_POST);
            // Здесь, если $guestbook_entry_error_check не пустой, значит есть ошибки в заполнении полей, об
            // этом нижележащий if

            if($guestbook_entry_error_check!='')
                {
                    //начало div under
                    echo'
                        <div id="under">
                            <div id="error_box">
                            <span class="error_text">
                            '.$guestbook_entry_error_check.'
                            </span>
                            </div>
                    
                        <div id="form_entry">
                            <form class="form_one" method="post" action="index.php">
                            <div class="form_inner_box">
                                <div class="form_left_box"><span class="form_title">Введите имя/псевдоним<span class="red_color">*</span>:</span></div>
                                <div class="form_right_box"><input type="text" name="username" size="40" maxlength="100" value="'.$_POST['username'].'"></input></div>
                            </div>
                            <div class="form_inner_box">
                                <div class="form_left_box"><span class="form_title">Введите email<span class="red_color">*</span>:</span></div>
                                <div class="form_right_box"><input type="text" name="email" size="40" maxlength="100" value="'.$_POST['email'].'"></input></div>
                            </div>
                            <div class="form_inner_box">
                                <div class="form_left_box"><span class="form_title">Введите Ваш сайт:</span></div>
                                <div class="form_right_box"><input type="text" name="homepage" size="40" maxlength="100" value="'.$_POST['homepage'].'"></input></div>
                            </div>
                            <div class="form_inner_textarea_box">
                                <div class="form_left_box"><span class="form_title">Введите текст сообщения<span class="red_color">*</span>:</span></div>
                                <div class="form_textarea_box"><textarea name="text" style="width: 80%; height: 80%; resize: none;">'.$_POST['text'].'</textarea></div>
                            </div>
                            <div class="form_inner_box">
                                <div class="form_left_box"><span class="form_title">Введите каптчу<span class="red_color">*</span>:</span></div>
                                <div class="form_captcha_text_box"><input type="text" name="captcha_code" size="20" maxlength="20"></input></div>
                                <div class="form_captcha_image_box"><img src="captcha.php"></div>
                            </div>
                             
                            <div class="form_inner_box">
                                <input type="hidden" name="browser"  value="'.htmlspecialchars($_SERVER['HTTP_USER_AGENT']).'">
                                <input type="hidden" name="ip" value="'.htmlspecialchars($_SERVER['REMOTE_ADDR']).'">
                                <input type="hidden" name="sort" value="'.$sorting_order.'">
                                <input type="hidden" name="type" value="'.$sorting_type.'">
                                <input type="hidden" name="page" value="'.$current_page.'">  
                                <div class="form_button_box"><button  class="button_style_1" type="submit">Отправить сообщение</button></div>
                            </div>
         
                            </form>
                        </div>
                
                        </div>';
                        // конец div under
                    /*
                    // Выдаю строку с ошибками
                    echo '</br>'.$guestbook_entry_error_check;

                    echo '</br>Показываю форму для ввода записей </br>';
                    echo 'Отправьте сообщение, заполнив форму ниже! Поля, обязательные для заполнения, отмечены <span class="red_color">*</span>. </br>';

                    // Вот тут показываю форму для ввода записей, снабжая текстовыми комментариями в том числе об обязательных полях
                    echo ' <form  method="post" action="index.php">';

                    // Собственно вывожу необходимые поля, разбирая массив, из моей функции get_fields_for_entry_form для вывода полей.
                    $array_of_fields=get_fields_for_entry_form($db_link);
                    // Это переменная obligatory_start, которая превращается из пустой в звёздочку, если поле формы обязательно для заполнения.
                    $obligatory_star='';
                    foreach($array_of_fields as $key=>$value)
                    {   if($value['input_obligatory']==1)
                            {
                                $obligatory_star='<span class="red_color">*</span>';
                            }
                        else
                            {
                                $obligatory_star='';
                            }
                        // Смотрю, -- если тип поля text, то ставлю тут <textarea итд></textarea> , а не <input type="text" итд>
                        if($value['field_type']=='text_field_type')
                        {
                            echo 'Заполните поле '.$value['field_html_name'].$obligatory_star.'<textarea name="'.$value['field_name'].'" rows="10" cols="20">'.$_POST[$value['field_name']].'</textarea> </br>';
                        }
                        else
                        {
                            echo 'Заполните поле '.$value['field_html_name'].$obligatory_star.'<input name="'.$value['field_name'].'" type="text" maxlength="255" size="25" value="'.$_POST[$value['field_name']].'"> </br>';
                        }
                    }
                    ;
                    // Добавляю скрытые поля ip и browser, чтобы записать их  в базу данных. В рамках паранойи
                    // защиты от XSS инъекций обрабатываю htmlspecialchars и эти поля тоже.
                    echo '<input type="hidden" name="browser"  value="'.htmlspecialchars($_SERVER['HTTP_USER_AGENT']).'">';
                    echo '<input type="hidden" name="ip" value="'.htmlspecialchars($_SERVER['REMOTE_ADDR']).'">';
                    echo '<input type="hidden" name="sort" value="'.$sorting_order.'">
                          <input type="hidden" name="type" value="'.$sorting_type.'">
                          <input type="hidden" name="page" value="'.$current_page.'">';
                    // Добавляю проверку каптчи, файл с каптчой отдельный, -- это captcha.php

                    echo '<img src="captcha.php">';
                    echo '<input type="text" name="captcha_code" value=""> </br>';
                    echo '<input type="submit" value="отправить сообщение">
                          </form>'; */
                }

            // Если ошибок нет, то заношу записи в базу данных и потом показываю кнопку, чтобы вернуться на начальную(?)
            // страницу. Об этом нижележащий else
            else
                {

                    // функция для записи в базу данных
                    guestbook_entry_database_input($db_link,$_POST);

                    echo'<meta http-equiv="refresh" content="0; URL=form_success.php">';

                }
        }

}




include_once('ender.php');
?>