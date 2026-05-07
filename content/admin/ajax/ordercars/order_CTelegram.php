<?

class Telegram
{
    /*
     * 1 В телеграмме в поике находим BotFather
     * 2 пишем ему /newbot для создания бота через которого и будут отсылаться сообщения
     * 3 Когда бот создан вы получите его token
     * 3 Далее следуем инструкции (Называем бота, добавлем фото, описание т.д.)
     * 4 Находим в поиске нашего бота и пишем ему  /start
     * 5 https://api.telegram.org/bot[token]/getUpdates По этой ссылке получаем id
     * 		вместо [token] указываем token который мы получили
     * https://api.telegram.org/bot1398519511:AAHGNlpbTutAS4QAnT7Z-eCf7_z80hJ4N2g/getUpdates По этой ссылке получаем id
     * {
     * 	"ok":true,
     * 	"result":[
     * 		{
     * 			"update_id":945410549,
     * 			"message":{"message_id":1,
     * 			"from":{
     * 				--------------"id":564183869,-------------------
     * 				"is_bot":false,
     * 				"first_name":"\u0410\u043d\u0434\u0440\u0435\u0439",
     * 				"last_name":"\u041f\u043e\u043b\u0438\u0449\u0443\u043a",
     * 				"username":"andreip1997",
     * 				"language_code":"ru"
     * 			},
     * 			"chat":{"id":564183869,
     * 				"first_name":"\u0410\u043d\u0434\u0440\u0435\u0439",
     * 				"last_name":"\u041f\u043e\u043b\u0438\u0449\u0443\u043a",
     * 				"username":"andreip1997",
     * 				"type":"private"
     * 			},

     * 			"date":1602933236,

     * 			"text":"/start",

     * 			"entities":[

     * 				{

     * 				"offset":0,

     * 				"length":6,

     * 				"type":"bot_command"

     * 				}

     * 			]

     * 		}

     * }

     * 6 id чата для отсылки сообщений в личку "id". Пример:564183869

    * https://docs.leadconverter.su/faq/populyarnye-voprosy/telegram/kak-uznat-id-telegram-kanala
     * 		Для отсылки сообщений в группу id будет со знаком "-". Пример:-472498896

     * 7 В файле include.php объявляем класс следующим образом

     * 		include($_SERVER["DOCUMENT_ROOT"]."/". WS_PANEL ."/include/CTelegram.php");

     * 		$Telegram = new Telegram(array('bot_token'=>'[token]', 'chat_id'=>'id чата'));

     * 8 Для отправки сообщения конкретному пользователю используем функцию $Telegram->send_message_user("Текст сообщения")


    1. Снимите «групповую приватность» у бота
По умолчанию BotFather создаёт бота с режимом Privacy Mode = ON, и в группах он получает только:

команды вида /mycmd@MyBot

реплай-ответы прямо на сообщения бота

Отключите режим:
@BotFather   →  /setprivacy
Выберите бота →  Disable
Или в меню /mybots → Bot Settings → Group Privacy → Turn off.
После изменения обязательно удалите бота из группы и добавьте снова – иначе Telegram не применит новый режим.
Stack Overflow
Gist

2. Разрешите добавление в группы
Если раньше в BotFather исполняли /setjoingroups, убедитесь, что стоит “Yes”. Иначе Telegram просто не даст пригласить бота в чат.
Telegram

3. Убедитесь, что бот – администратор канала/группы
Для каналов админ-права обязательны, чтобы получать channel_post.
В группах админ-права не нужны для чтения, но нужны, если бот должен сам писать (право Can Send Messages).
Stack Overflow

4. Проверьте getUpdates
curl "https://api.telegram.org/bot<Токен>/getUpdates?timeout=30&allowed_updates=message,channel_post"
message – любые сообщения в личке и группах

channel_post – посты в каналах

Если в ответе пусто, а вы уверены, что сообщения были, сбросьте смещение:
curl "https://api.telegram.org/bot<Токен>/getUpdates?offset=-1"
Затем вновь отправьте сообщение в группу/канал и повторите запрос.

     */



    // $text -Текст сообщения

    // $chat_id - id чата

    // $token - персональный токен





    var $token='';

    var $chat_id="";



    public function __construct($arr)

    {

        $this->token= $arr['bot_token'];

        $this->chat_id=$arr['chat_id'];

    }



    // https://tlgrm.ru/docs/bots/api#sendmessage
    // https://tlgrm.ru/docs/bots/api#setwebhook
    function send_message_user( $text, $replyMarkup = false){
        // $Url ="https://api.telegram.org/bot".$this->token."/getUpdates";

        $ch=curl_init();
        curl_setopt($ch, CURLOPT_URL, 'https://api.telegram.org/bot'.$this->token.'/sendMessage');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HEADER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_POST, true);

        $data = array(
            'chat_id'=> $this->chat_id,
            'text'=> ($text),
            'parse_mode'=> 'HTML',
            'disable_web_page_preview'=> true,
            // 'reply_markup' =>
        );
        if( $replyMarkup != false ){
            $data['reply_markup'] = json_encode( $replyMarkup);
        }

        // curl_setopt($ch, CURLOPT_POSTFIELDS, 'chat_id='. $this->chat_id.'&text='.urlencode($text).'&parse_mode=HTML&disable_web_page_preview=true');
        curl_setopt($ch, CURLOPT_POSTFIELDS, (http_build_query( $data)) );

        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);

        $result = curl_exec($ch);
        curl_close($ch);

        return $result;

    }


    /**
     * Отправляет до 10 фото одним «альбомом» вместе с подписью.
     *
     * @param array  $media      Массив элементов ['type'=>'photo','media'=>URL_or_file_id]
     * @param string $caption    Текст (HTML) — подпись к первой фотографии
     * @param string $parseMode  Например, 'HTML'
     * @return mixed             Ответ API
     */
    public function send_album_with_caption(array $media, string $caption = '', string $parseMode = 'HTML')
    {
        // 1) Ограничиваем до 10 элементов
        $media = array_slice($media, 0, 10);

        // 2) Добавляем подпись только к первой фотографии
        if ($caption !== '' && isset($media[0])) {
            $media[0]['caption']    = $caption;
            $media[0]['parse_mode'] = $parseMode;
        }

        // 3) Готовим поля для multipart/form-data
        $postFields = [
            'chat_id' => $this->chat_id,
        ];

        // 4) Ищем в media объекты CURLFile и заменяем media на attach://
        foreach ($media as $idx => $item) {
            if ($item['media'] instanceof \CURLFile) {
                $attachKey = "file{$idx}";
                // вместо самого CURLFile оставляем placeholder
                $media[$idx]['media'] = "attach://{$attachKey}";
                // а в postFields добавляем реальный файл
                $postFields[$attachKey] = $item['media'];
            }
        }

        // 5) JSON-кодируем описание альбома
        $postFields['media'] = json_encode($media);

        // 6) Делаем единый запрос
        $ch = curl_init("https://api.telegram.org/bot{$this->token}/sendMediaGroup");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER,    true);
        curl_setopt($ch, CURLOPT_POST,              true);
        curl_setopt($ch, CURLOPT_POSTFIELDS,        $postFields);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT,    10);

        $response = curl_exec($ch);
        curl_close($ch);

        return $response;
    }

    /**
     * Шаг 2. Отправляем текст + кнопку
     * --------------------------------
     */
    public function send_caption_with_button(  $text = "\xE2\x80\x8B",     $keyboard)
    {
        $postFields = [
            'chat_id'      => $this->chat_id,
            'text'         => $text,
            'reply_markup' => json_encode($keyboard, JSON_UNESCAPED_UNICODE),
            'disable_web_page_preview' => true,
        ];

        /*  parse_mode добавляем ТОЛЬКО если текст не «виртуально пустой»
            (иначе Telegram опять сочтёт его пустым после разбора тегов) */


        $ch = curl_init("https://api.telegram.org/bot{$this->token}/sendMessage");
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => $postFields,
            CURLOPT_CONNECTTIMEOUT => 10,
        ]);
        $response = curl_exec($ch);
        curl_close($ch);

        return $response;   // вернёт JSON с message_id
    }

}
