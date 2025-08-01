<?php defined( '_DOIT' ) or die( 'Restricted access' ); ?>

<?php

// Create temporary array with credit page translations
$lng_credit_page = array(
    'w' => array(
        'banner_desc' => array(
                'ro' => '<b>Mașină nouă chiar de mâine.</b> Mașina dvs. veche + diferența = cheile noii mașini într-o singură zi, cu asistență juridică completă și fără bătăi de cap.',
                'ru' => '<b>Новый автомобиль уже завтра.</b> Ваш старый авто + доплата = ключи от выбранной машины за один день,
                    с полным юридическим сопровождением и без хлопот.',
                'en' => '<b>A new car as early as tomorrow.</b> Your old car + extra payment = keys to the chosen vehicle in one day, with full legal support and no hassle.',
        ),
        'banner_title' => array(
            'ro' => 'Schimbați-vă mașina în <span class="text-red">1 zi</span> cu Trade-In de la Sauto',
            'ru' => 'Обменяйте свой автомобиль на новый за <span class="text-red">1 день</span> с Trade-In от Sauto',
            'en' => 'Exchange your car in <span class="text-red">1 day</span> with Trade-In from Sauto',
        ),
        'form' => array(
            'ro' => "<script data-b24-form='inline/46/sa0e3j' data-skip-moving=\"true\">
                    (function(w,d,u){
                        var s=d.createElement('script');s.async=true;s.src=u+'?'+(Date.now()/180000|0);
                        var h=d.getElementsByTagName('script')[0];h.parentNode.insertBefore(s,h);
                        })(window,document,'https://cdn-ru.bitrix24.ru/b33145896/crm/form/loader_46.js');
                </script>",
            'ru' => "<script data-b24-form='inline/42/u65756' data-skip-moving=\"true\">
                    (function(w,d,u){
                        var s=d.createElement('script');s.async=true;s.src=u+'?'+(Date.now()/180000|0);
                        var h=d.getElementsByTagName('script')[0];h.parentNode.insertBefore(s,h);
                    })(window,document,'https://cdn-ru.bitrix24.ru/b33145896/crm/form/loader_42.js');
                </script>",
            'en' => "<script data-b24-form='inline/44/ull2lk' data-skip-moving=\"true\">
                    (function(w,d,u){
                        var s=d.createElement('script');s.async=true;s.src=u+'?'+(Date.now()/180000|0);
                        var h=d.getElementsByTagName('script')[0];h.parentNode.insertBefore(s,h);
                        })(window,document,'https://cdn-ru.bitrix24.ru/b33145896/crm/form/loader_44.js');
                </script>",
        ),
        'advantages_title' => array(
            'ro' => 'Avantajele Trade-In la Sauto',
            'ru' => 'Преимущества Trade-In в Sauto',
            'en' => 'Advantages of Trade-In with Sauto',
        ),
        'advantages_desc' => array(
            'ro' => '',
            'ru' => 'Почему обмен в Sauto – это выгодно и удобно?',
            'en' => '',
        ),
        'advantages_item1_title' => array(
            'ro' => 'Rapid',
            'ru' => 'Быстро',
            'en' => 'Fast',
        ),
        'advantages_item1_desc' => array(
            'ro' => 'Schimbul se face într-o zi – nu rămâneți nicio clipă fără mașină. Totul se face operativ, la ora convenabilă pentru dvs.',
            'ru' => 'Обмен всего за 1 день – Вы не остаетесь без машины ни на минуту. Все делаем оперативно, в удобное для Вас время.',
            'en' => 'Exchange in just 1 day – You won\'t be left without a car for even a minute. We do everything promptly, at a time convenient for you.',
        ),
        'advantages_item2_title' => array(
            'ro' => 'Avantajos',
            'ru' => 'Выгодно',
            'en' => 'Profitable',
        ),
        'advantages_item2_desc' => array(
            'ro' => 'Evaluare corectă a mașinii dvs. la prețul pieței. Diferența se calculează transparent, fără taxe ascunse sau comisioane.',
            'ru' => 'Честная оценка Вашего авто по рыночной стоимости. Доплата рассчитывается прозрачно, никаких скрытых платежей или комиссий.',
            'en' => 'Honest evaluation of your car at market value. Extra payment is calculated transparently, with no hidden fees or commissions.',
        ),
        'advantages_item3_title' => array(
            'ro' => 'Sigur',
            'ru' => 'Безопасно',
            'en' => 'Safe',
        ),
        'advantages_item3_desc' => array(
            'ro' => 'Asistență juridică completă. Tranzacția este oficială și transparentă – toate actele sunt întocmite corect, cu garanția legalității schimbului.',
            'ru' => 'Полное юридическое сопровождение. Сделка официальная и прозрачная – все документы оформляем правильно, гарантия чистоты обмена.',
            'en' => 'Full legal support. The deal is official and transparent – we prepare all documents correctly, guaranteeing a clean exchange.',
        ),
        'advantages_item4_title' => array(
            'ro' => 'Orice marcă',
            'ru' => 'Любой авто',
            'en' => 'Any car',
        ),
        'advantages_item4_desc' => array(
            'ro' => 'Acceptăm automobile <b>de orice marcă și an de fabricație</b> (peste 3 ani – fără restricții). Nu contează ce conduceți – noi găsim o alternativă potrivită!',
            'ru' => 'Принимаем автомобили <b>любых марок и годов выпуска</b> (старше 3 лет – без ограничений).
                    Неважно, на чем Вы ездите – у нас найдется достойная замена!',
            'en' => 'We accept cars <b>of all brands and years of manufacture</b> (older than 3 years – no restrictions). No matter what you drive – we’ll find a worthy replacement!',
        ),
        'steps_title' => array(
            'ro' => 'Cum decurge procesul de schimb',
            'ru' => 'Как проходит процесс обмена',
            'en' => 'How the exchange process works',
        ),
        'steps_desc' => array(
            'ro' => 'Procesul de Trade-In la Sauto este cât se poate de comod și include câțiva pași simpli:',
            'ru' => 'Процесс обмена авто в Sauto максимально удобен и состоит из нескольких простых шагов:',
            'en' => 'The car exchange process at Sauto is as convenient as possible and consists of a few simple steps:',
        ),
        'steps_item1_title' => array(
            'ro' => 'Cerere și evaluare',
            'ru' => 'Заявка и оценка',
            'en' => 'Application and evaluation',
        ),
        'steps_item1_desc' => array(
            'ro' => 'Lăsați o cerere online sau veniți direct la salonul nostru. Expertul nostru evaluează mașina dvs. (luăm în calcul marca, anul, kilometrajul și starea) și vă comunică imediat un preț corect. <i>(O evaluare preliminară se poate face prin telefon sau prin formularul de pe site.)</i>',
            'ru' => 'Вы оставляете заявку онлайн или приезжаете к нам в автосалон. Наш эксперт проводит оценку
                            Вашего автомобиля (учитываем марку, год, пробег и состояние) и сразу называет честную стоимость.
                            <i>(Предварительную оценку можно получить по телефону или через форму на сайте.)</i>',
            'en' => 'You submit a request online or visit our dealership. Our expert evaluates your car (considering brand, year, mileage, and condition) and immediately gives a fair price. <i>(A preliminary estimate can be received by phone or through the website form.)</i>',
        ),
        'steps_item2_title' => array(
            'ro' => 'Alegerea unei mașini noi',
            'ru' => 'Выбор нового авто',
            'en' => 'Choosing a new car',
        ),
        'steps_item2_desc' => array(
            'ro' => 'În funcție de preferințele dvs., vă oferim opțiuni din stocul nostru – mașini noi sau rulate. Le inspectați personal, faceți test-drive și alegeți ce vi se potrivește. Consultantul Sauto vă oferă sfaturi profesioniste.',
            'ru' => 'На основе Ваших пожеланий мы подбираем варианты автомобилей из нашего ассортимента – новые или
                            с пробегом. Вы лично осматриваете машины, проводите тест-драйв и выбираете наиболее подходящую.
                            Менеджер Sauto дает профессиональные советы при выборе.',
            'en' => 'Based on your preferences, we offer car options from our inventory – new or used. You inspect the vehicles, take test drives, and choose the most suitable one. A Sauto manager provides professional advice.',
        ),
        'steps_item3_title' => array(
            'ro' => 'Întocmirea documentelor',
            'ru' => 'Оформление документов',
            'en' => 'Document processing',
        ),
        'steps_item3_desc' => array(
            'ro' => 'Verificăm dacă mașina dvs. este juridic „curată” și pregătim toate actele necesare pentru schimb. Nu trebuie să vă stresați cu birocrația – specialiștii noștri întocmesc contractul, procesul-verbal de predare și celelalte documente.',
            'ru' => 'Мы проверяем юридическую чистоту Вашего старого авто и готовим все необходимые документы для обмена.
                            Вам не нужно переживать о бюрократии – наши специалисты подготовят договор, акт приема-передачи и другие бумаги.',
            'en' => 'We verify the legal cleanliness of your old car and prepare all necessary documents for the exchange. You don\'t need to worry about bureaucracy – our specialists will handle the contract, delivery-acceptance act, and other paperwork.',
        ),
        'steps_item4_title' => array(
            'ro' => 'Schimbul și plata diferenței',
            'ru' => 'Обмен и доплата',
            'en' => 'Exchange and extra payment',
        ),
        'steps_item4_desc' => array(
            'ro' => 'Când totul este stabilit, încheiem tranzacția. Prețul mașinii dvs. vechi se deduce din valoarea celei noi. Plătiți diferența (cum vă e comod – cash, card sau transfer bancar). Dacă e nevoie, puteți lua credit chiar de la noi.',
            'ru' => 'Когда все согласовано, мы оформляем сделку. Стоимость Вашего старого автомобиля идет в счет оплаты
                            выбранного авто. Вы вносите доплату разницей в цене (удобным способом – наличными, картой
                            или через банковский перевод). При необходимости можно оформить кредит на недостающую сумму прямо у нас.',
            'en' => 'Once everything is agreed upon, we finalize the deal. The value of your old car goes toward the new car’s payment. You pay the price difference (in a convenient way – cash, card, or bank transfer). If necessary, you can arrange a loan for the remaining amount directly with us.',
        ),
        'steps_item5_title' => array(
            'ro' => 'Preluarea mașinii',
            'ru' => 'Получение автомобиля',
            'en' => 'Receiving your new car',
        ),
        'steps_item5_desc' => array(
            'ro' => 'Felicitări, schimbul s-a realizat! Ne dați cheile mașinii vechi și primiți cheile și actele celei noi. <b>Totul durează o zi</b>, după care plecați acasă cu noua mașină. Drum bun!',
            'ru' => 'Поздравляем, обмен состоялся! Вы отдаете нам ключи от старого авто и получаете ключи и
                            документы от нового. <b>Весь процесс занимает один день</b>, после чего Вы уезжаете домой
                            на своей “новой” машине. Приятной поездки!',
            'en' => 'Congratulations, the exchange is complete! You hand over your old car keys and receive the keys and documents for your new one. <b>The whole process takes one day</b>, and then you drive home in your "new" car. Have a great ride!',
        ),
        'trust_title' => array(
            'ro' => 'Păreri de la clienți',
            'ru' => 'Нам доверяют автовладельцы',
            'en' => 'Customer Reviews',
        ),
        'trust_item1_title' => array(
            'ro' => '',
            'ru' => 'Лицензированный автодилер',
            'en' => '',
        ),
        'trust_item2_title' => array(
            'ro' => '',
            'ru' => 'Официальная сделка, договор, прозрачность',
            'en' => '',
        ),
        'trust_item3_title' => array(
            'ro' => '',
            'ru' => 'Оплата по безналу или наличными',
            'en' => '',
        ),
        'reviews_item1_name' => array(
            'ro' => 'Alexei',
            'ru' => 'Алексей',
            'en' => 'Alexey',
        ),
        'reviews_item1_info' => array(
            'ro' => '45 ani, Chișinău',
            'ru' => '45 лет, Кишинёв',
            'en' => '45, Chișinău',
        ),
        'reviews_item1_text' => array(
            'ro' => 'Mulțumesc mult Sauto pentru munca excelentă! Am schimbat sedanul meu vechi de 10 ani pe o mașină mai nouă într-o singură zi. Nici nu credeam că poate fi atât de simplu: evaluarea a fost corectă, m-au ajutat să aleg ceva potrivit bugetului meu. A fost foarte avantajos și, cel mai important – fără stres cu actele. Acum recomand Trade-In de la Sauto tuturor prietenilor!',
            'ru' => 'Огромное спасибо Sauto за прекрасную работу! Обменял свой 10-летний седан на более свежий автомобиль буквально за один день. Даже не ожидал, что всё пройдет так просто: мою машину оценили честно, помогли выбрать новый авто под мой бюджет. Вышло очень выгодно, и самое главное – никаких хлопот с оформлением документов. Теперь советую Trade-In от Sauto всем знакомым!',
            'en' => 'Huge thanks to Sauto for a great job! Exchanged my 10-year-old sedan for a newer car in just one day. Didn\'t expect it to be so easy: my car was fairly evaluated, they helped me pick a new car within my budget. Turned out very profitable, and most importantly – no hassle with the paperwork. Now I recommend Trade-In from Sauto to everyone I know!',
        ),
        'reviews_item2_name' => array(
            'ro' => 'Ion',
            'ru' => 'Ион',
            'en' => 'Ion',
        ),
        'reviews_item2_info' => array(
            'ro' => '38 ani, București',
            'ru' => '38 лет, Бухарест',
            'en' => '38, Bucharest',
        ),
        'reviews_item2_text' => array(
            'ro' => 'Mult timp am fost nesigur dacă merită să-mi schimb mașina prin trade-in. Sauto mi-a risipit toate îndoielile. Băieții de la salon mi-au explicat tot, mi-au oferit o alternativă excelentă. Tot procesul a fost clar și rapid – dimineața am lăsat cererea, seara deja plecam cu altă mașină. Foarte mulțumit de servicii!',
            'ru' => 'Долго сомневался, стоит ли менять свою старую машину через трейд-ин. Sauto развеял все мои сомнения. Ребята из салона подробно объяснили условия, подобрали мне отличный вариант замены. Весь процесс прошёл прозрачно и действительно быстро – утром оставил заявку, к вечеру уже уехал на другой машине. Я очень доволен подходом и сервисом!',
            'en' => 'I was hesitant for a long time about trading in my old car. Sauto cleared all my doubts. The guys at the showroom explained everything in detail and found me a great replacement option. The whole process was transparent and really quick – I applied in the morning and left with another car by evening. I\'m very happy with the approach and service!',
        ),
        'reviews_item3_name' => array(
            'ro' => 'Serghei',
            'ru' => 'Сергей',
            'en' => 'Sergey',
        ),
        'reviews_item3_info' => array(
            'ro' => '52 ani, Bălți',
            'ru' => '52 года, Бельцы',
            'en' => '52, Bălți',
        ),
        'reviews_item3_text' => array(
            'ro' => 'Aveam nevoie de o mașină mai nouă pentru muncă, dar nu voiam să pierd timp cu vânzarea celei vechi. La Sauto totul a mers perfect: am venit dimineața cu vechea mașină și am plecat seara cu cea nouă. Totul s-a făcut într-o zi, actele corecte, diferența plătită fără bătăi de cap. Schimb corect și rapid. Mulțumesc pentru promptitudine și profesionalism!',
            'ru' => 'Мне нужна была машина поновее для работы, но не хотелось тратить время на продажу старой. В Sauto всё сделали в лучшем виде: приехал утром на своем старом авто, а уехал вечером на обновленном. Все процедуры заняли один день, документы оформлены правильно, деньги доплатил без лишних сложностей. Обмен прошёл честно и быстро. Спасибо за оперативность и профессионализм!',
            'en' => 'I needed a newer car for work but didn’t want to waste time selling the old one. Sauto handled everything perfectly: came in the morning with my old car and drove away in the evening with the new one. The whole procedure took one day, all documents were done right, and I paid the difference with no issues. The exchange was honest and quick. Thanks for the promptness and professionalism!',
        ),
        'faq_title' => array(
            'ro' => 'Întrebări frecvente',
            'ru' => 'FAQ',
            'en' => 'Frequently Asked Questions',
        ),
        'faq_desc' => array(
            'ro' => '',
            'ru' => 'Вопросы и ответы',
            'en' => '',
        ),
        'faq_item1_title' => array(
            'ro' => 'Ce documente sunt necesare pentru schimb?',
            'ru' => 'Какие документы нужны для обмена?',
            'en' => 'What documents are needed for the exchange?',
        ),
        'faq_item1_desc' => array(
            'ro' => 'Pentru încheierea tranzacției sunt necesare documentele standard: buletinul proprietarului, pașaportul tehnic al mașinii (PTC) și certificatul de înmatriculare. De preferat și cartea de service (dacă există) și ambele seturi de chei. Toate celelalte verificări juridice (gajuri, restricții etc.) le facem noi. Dacă va fi nevoie de ceva în plus – vă anunțăm.',
            'ru' => 'Для оформления сделки понадобятся стандартные документы на Вас и автомобиль: паспорт владельца, технический паспорт автомобиля (PTC) и свидетельство о регистрации. Также желательно предоставить сервисную книжку (если есть) и оба комплекта ключей. Все остальные юридические проверки (на залоги, ограничения и пр.) мы выполняем самостоятельно. Если что-то будет нужно дополнительно – мы подскажем.',
            'en' => 'To complete the deal, you’ll need standard documents for you and the car: owner’s ID, vehicle technical passport (PTC), and registration certificate. It’s also recommended to provide the service book (if available) and both sets of keys. All other legal checks (liens, restrictions, etc.) are handled by us. If anything else is needed – we’ll let you know.',
        ),
        'faq_item2_title' => array(
            'ro' => 'Există taxă pentru serviciul de Trade-In?',
            'ru' => 'Есть ли плата за услугу Trade-In?',
            'en' => 'Is there a fee for the Trade-In service?',
        ),
        'faq_item2_desc' => array(
            'ro' => 'Nu, nu există nicio taxă suplimentară pentru schimb. Sauto vă cumpără mașina și vă vinde alta, deci nu percepem comisioane de la client. Plătiți doar diferența dintre valoarea celor două automobile, dacă mașina nouă e mai scumpă. Fără costuri ascunse – suma este stabilită în avans și se trece în contract.',
            'ru' => 'Нет, дополнительная плата за сам обмен отсутствует. Sauto покупает Ваш автомобиль и продаёт Вам другой, поэтому никаких комиссий с клиента не взимается. Вы платите только разницу в цене между автомобилями, если новый автомобиль дороже. Никаких скрытых доплат – сумма доплаты оговаривается заранее и фиксируется в договоре.',
            'en' => 'No, there is no additional fee for the exchange itself. Sauto buys your car and sells you another, so no commissions are charged. You only pay the price difference if the new car is more expensive. No hidden fees – the extra payment amount is agreed upon in advance and fixed in the contract.',
        ),
        'faq_item3_title' => array(
            'ro' => 'În ce stare trebuie să fie mașina mea?',
            'ru' => 'В каком состоянии должен быть мой автомобиль?',
            'en' => 'What condition should my car be in?',
        ),
        'faq_item3_desc' => array(
            'ro' => 'Acceptăm automobile în orice stare, <b>chiar și cu uzură sau defecte minore</b>. Important e să funcționeze și să fie în proprietatea dvs. legală. Nu vă faceți griji pentru zgârieturi sau vârstă – evaluăm corect. Fiecare caz se discută individual, dar în general acceptăm aproape orice marcă, model și an de fabricație.',
            'ru' => 'Мы принимаем автомобили в любом состоянии, <b>даже с пробегом и небольшими недостатками</b>. Конечно, машина должна быть на ходу и принадлежать Вам на законных основаниях. Не переживайте, если есть мелкие царапины или возраст солидный – мы оцениваем авто объективно. Каждый случай обсуждается индивидуально, но в целом принимаем авто практически любых марок, моделей и годов выпуска.',
            'en' => 'We accept cars in any condition, <b>even with mileage and minor issues</b>. Of course, the car must be running and legally yours. Don’t worry about small scratches or an older age – we evaluate cars objectively. Each case is discussed individually, but in general, we accept almost all brands, models, and years.',
        ),
        'faq_item4_title' => array(
            'ro' => 'Ce fac dacă nu am toată diferența?',
            'ru' => 'Что если моей доплаты не хватает?',
            'en' => 'What if I don’t have enough money for the extra payment?',
        ),
        'faq_item4_desc' => array(
            'ro' => 'Dacă nu aveți întreaga sumă pentru diferență, vă oferim soluții convenabile. Sauto colaborează cu bănci de încredere – puteți obține un credit auto sau o rată direct în salon, în condiții avantajoase. Procesul este rapid, astfel că puteți finaliza schimbul fără amânări.',
            'ru' => 'Если у Вас не хватает средств на доплату за выбранный автомобиль, мы предложим удобные варианты. Sauto сотрудничает с надежными банками: Вы можете оформить автокредит или рассрочку прямо в нашем салоне на выгодных условиях. Процесс получения кредита проходит быстро, и Вы сможете завершить обмен, не откладывая мечту о новом авто.',
            'en' => 'If you lack the funds for the extra payment, we’ll offer convenient options. Sauto works with reliable banks: you can get a car loan or installment plan right at our showroom on favorable terms. The loan process is fast, so you can complete the exchange without delaying your dream of a new car.',
        ),
        'faq_item5_title' => array(
            'ro' => 'Trebuie să-mi vând singur mașina?',
            'ru' => 'Нужно ли мне самостоятельно продавать свою машину?',
            'en' => 'Do I need to sell my car myself?',
        ),
        'faq_item5_desc' => array(
            'ro' => 'Nu, nu trebuie să căutați cumpărător și să o vindeți pe cont propriu. Aici intervine comoditatea Trade-In – <b>noi cumpărăm direct mașina dvs.</b>, scutindu-vă de toate grijile. Pur și simplu o predați nouă și alegeți alta în schimb. Fără tranzacții separate, fără bătăi de cap – totul într-un singur loc și într-o singură zi.',
            'ru' => 'Нет, Вам не нужно искать покупателя и продавать автомобиль самому. В этом и есть удобство Trade-In – мы <b>сразу выкупаем Ваш автомобиль</b>, избавляя Вас от всех хлопот с продажей. Вы просто сдаёте старую машину нам и одновременно приобретаете другую. Никаких отдельных сделок и лишней суеты: вся операция проходит в одном месте и в одно время.',
            'en' => 'No, you don’t need to find a buyer or sell the car on your own. That’s the convenience of Trade-In – we <b>buy your car directly</b>, saving you the trouble of selling. You just hand over your old car and get another one at the same time. No separate deals or extra hassle – the whole operation is done in one place and one go.',
        ),
        'form_title' => array(
            'ro' => 'Gata de schimb?',
            'ru' => 'Готовы к обмену?',
            'en' => 'Ready to exchange?',
        ),
        'form_desc1' => array(
            'ro' => 'Nu mai amânați schimbarea mașinii – faceți-o chiar azi! Lăsați o cerere și vom pregăti o ofertă personalizată de Trade-In pentru dvs.:',
            'ru' => 'Не откладывайте обновление авто на потом – совершите обмен уже сейчас! Оставьте заявку, и мы подготовим персональное предложение Trade-In специально для Вас:',
            'en' => 'Don’t postpone upgrading your car – exchange it now! Submit a request, and we’ll prepare a personalized Trade-In offer just for you:',
        ),
        'form_desc2' => array(
            'ro' => 'Evaluare și consultanță gratuită – fără obligații. Încercați și vedeți cât de ușor este să treceți la o mașină nouă!',
            'ru' => 'Бесплатная оценка и консультация – без обязательств. Попробуйте и убедитесь, как легко перейти на новый автомобиль!',
            'en' => 'Free appraisal and consultation – no obligation. Try it and see how easy it is to switch to a new car!',
        ),
    )
);

// Determine current language from cookie or default to Romanian
$current_lang = 'ro';
if (isset($_COOKIE['lang']) && in_array($_COOKIE['lang'], array('ro', 'ru', 'en'))) {
    $current_lang = $_COOKIE['lang'];
}

// Initialize global $lng array
if (!isset($lng)) {
    $lng = array();
}
if (!isset($lng['w'])) {
    $lng['w'] = array();
}

// Merge credit page translations with existing translations
$lng['w'] = array_merge($lng['w'], $lng_credit_page['w']);

// Helper function to safely get translation
function get_translation($key, $current_lang, $lng) {
    if (!isset($lng['w'][$key])) {
        return '[MISSING: ' . $key . ']';
    }
    if (!is_array($lng['w'][$key])) {
        return '[INVALID: ' . $key . ']';
    }
    if (!isset($lng['w'][$key][$current_lang])) {
        return '[MISSING LANG: ' . $key . '[' . $current_lang . ']]';
    }
    return $lng['w'][$key][$current_lang];
}

?>
