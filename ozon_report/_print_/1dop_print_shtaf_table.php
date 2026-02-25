<?php
// Инициализация переменной
$MeData = [];

// Если данные пришли методом POST
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['myDataStrafi'])) {
    $jsonString = $_POST['myDataStrafi'];
    $MeData = json_decode($jsonString, true); // true = ассоциативный массив

    // Проверка на ошибки JSON
    if (json_last_error() !== JSON_ERROR_NONE) {
        $MeData = ['error' => 'Некорректный JSON: ' . json_last_error_msg()];
    }
}
?>
<?php if ($_SERVER['REQUEST_METHOD'] === 'POST'): ?>
    <!-- Если это POST-запрос (данные отправлены) -->
  
    <pre><?php print_r($MeData); ?></pre>

<?php else: ?>
    <!-- Если это GET-запрос (обычный переход по ссылке) -->

    <!-- Скрытая форма для отправки данных -->
    <form id="sendForm" method="POST" style="display: none;">
        <input type="hidden" name="myDataStrafi" id="dataField" value="">
    </form>

    <script>
        (function() {
            // Читаем данные из localStorage
            const jsonData = localStorage.getItem('myDataStrafi');

            if (jsonData) {
                // Записываем JSON-строку в скрытое поле формы
                document.getElementById('dataField').value = jsonData;
                // Автоматически отправляем форму
                document.getElementById('sendForm').submit();
            } else {
                // Если данных нет – показываем сообщение
                document.body.innerHTML = '<h1>❌ Данные не найдены </h1>'
                    
            }
        })();
    </script>
<?php endif; ?>

</body>
</html>
<?php
die('dddddddddddddd');

// CSS цепляем
echo "<link rel=\"stylesheet\" href=\"../css/form_dates.css\">";

// Начинаем отрисовывать таблицу 
echo <<<HTML
<div class="h80procentov">
<div class="table-container">
 
<h3 class = "shapka_tabla"> $expenses  </h3>

<table class="financial-table">
<tr>
    <th>пп</th>
    <th>Номер заказа</th>
    <th>Сумма штрафа</th>
    
 </tr>
HTML;
$string_number=0;
foreach ($arr_strafov as $number_posting=>$summa_strafa){

      
    $string_number++;
    ($string_number % 2)?$string_number_color = "even_color": $string_number_color="oder_color";
    echo <<<HTML
        <tr class="" >
            <td class ="$string_number_color"  >$string_number</td>
            <td class ="$string_number_color big_text" >$number_posting</td>
            <td class ="center_text $string_number_color" >$summa_strafa</td>
           
    

</tr>

HTML;
}

echo <<<HTML
</table>
</div>
</div>
HTML;


