<?php
defined('_DOIT') or die('Restricted access');

// Russian insurance content
if ($_COOKIE['lang'] == 'ru') {
    $lang_offers['insurance'] = array(
        'name' => 'Страхование',
        'title'=> 'Автострахование — без головной боли и беготни',
        'text' => '
                <p>Купили автомобиль в <strong>Sauto</strong>? Поздравляем — теперь самое время сделать последний шаг перед дорогой: оформить страхование. Без лишних поездок, без очередей, без вопросов в стиле «А где же ваш оригинал справки из другой галактики?». </p>

        <p>Да, вы не ослышались. Мы — не страховая компания. Но мы сотрудничаем с проверенными страховщиками и можем сразу же оформить полис прямо у нас, в автоцентре. Заодно объясним, чем отличается ОСАГО от КАСКО и зачем нужен каждый.</p>
 <img src="/media/images/site/insurance/insurance-img1.jpg" alt="Asigurare Auto"/>
        <h3>Почему удобно оформить страхование у нас:</h3>
        <ul>
            <li><strong>Без лишних переходов</strong><br>Только что выбрали автомобиль? Страхуем его тут же, не отходя от кассы — ну или от нашего менеджера.</li>
            <li><strong>Проверенные партнёры</strong><br>Работают по-честному, не пропадают, когда приходит время выплат.</li>
            <li><strong>Консультация без умных слов</strong><br>Объясним всё простым языком. Без юридического тумана и офисного жаргона.</li>
            <li><strong>Документы — сразу на руки</strong><br>Электронный полис приходит на почту, бумажный — в конверт. По желанию — с подписью менеджера, который теперь для вас почти как семья.</li>
        </ul>
<img src="/media/images/site/insurance/insurance-img2.jpg" alt="Asigurarea la noi"/>
        <h3>Какие виды страховки можно оформить?</h3>
        <ul>
            <li><strong>ОСАГО (гражданская ответственность)</strong><br>Обязательно для всех. Покрывает ущерб, если вы случайно заденете чей-то бампер или нервную систему. <img src="/media/images/site/insurance/asig-01.svg" alt="RCA"/></li>
            <li><strong>КАСКО (добровольная страховка)</strong><br>Для тех, кто хочет спать спокойнее. Покрывает угоны, стихии и прочие сюрпризы на дороге. <img src="/media/images/site/insurance/asig-02.svg" alt="CASCO"/></li>
            <li><strong>Зелёная карта</strong><br>Если планируете путешествие за границу на своём авто. Без неё — ни туда, ни обратно.  <img src="/media/images/site/insurance/asig-03.svg" alt="VERDE"/></li>
        </ul>

        <p style="font-weight:bold; font-size:1.3rem;">Мы понимаем, как важно:</p>
        <ul>
            <li>Быть уверенным в завтрашнем дне (и в бампере)</li>
            <li>Не тратить полдня на беготню с документами</li>
            <li>Получить нормальную консультацию, а не робота с лицом инспектора</li>
        </ul>

        <p style="font-weight:bold; font-size:1.2rem;">Поэтому у нас всё — просто, честно и с уважением к вашему времени.</p>

        <p style="font-weight:bold; font-size:1.3rem;"><strong style="font-size:1.6rem">Хотите оформить страховку?</strong><br>Обратитесь к своему менеджеру в Sauto — и всё будет готово быстрее, чем вы скажете «КАСКО с франшизой».</p>'
    );
} 
// Romanian insurance content
elseif ($_COOKIE['lang'] == 'ro') {
    $lang_offers['insurance'] = array(
        'name' => 'Asigurare Auto',
        'title'=> 'Asigurare Auto — Fără bătăi de cap și alergătură',
        'text' => '
    <p>Ați cumpărat un automobil de la <strong>Sauto</strong>? Felicitări — acum este momentul perfect să faceți ultimul pas înainte de drum: să încheiați o asigurare. Fără deplasări inutile, fără cozi, fără întrebări de genul „Dar unde este originalul actului de pe altă planetă?”</p>

    <p>Da, ați auzit bine. Noi — nu suntem o companie de asigurări. Dar colaborăm cu asiguratori de încredere și putem emite polița pe loc, direct la centrul nostru auto. În același timp, vă explicăm clar diferența dintre RCA (OSAGO) și CASCO și la ce folosește fiecare.</p>
    <img src="/media/images/site/insurance/insurance-img1.jpg" alt="Asigurare Auto"/>

    <h3>De ce este convenabil să încheiați asigurarea la noi:</h3>
    <ul>
        <li><strong>Fără pași suplimentari</strong><br>Tocmai ați ales automobilul? Îl asigurăm imediat, fără să vă deplasați de la casă — sau de lângă managerul nostru.</li>
        <li><strong>Parteneri verificați</strong><br>Lucrează corect, nu dispar când vine momentul despăgubirii.</li>
        <li><strong>Consultații fără termeni complicați</strong><br>Vă explicăm totul clar, pe înțelesul tuturor. Fără limbaj juridic și fără jargon de birou.</li>
        <li><strong>Documentele — imediat</strong><br>Polița electronică vine pe email, iar cea tipărită — într-un plic. La cerere, cu semnătura managerului, care devine aproape ca un membru al familiei.</li>
    </ul>
<img src="/media/images/site/insurance/insurance-img2.jpg" alt="Asigurarea la noi"/>
    <h3>Ce tipuri de asigurări puteți încheia?</h3>
    <ul>
        <li><strong>RCA (asigurare de răspundere civilă)</strong><br>Obligatorie pentru toți. Acoperă daunele în cazul în care atingeți din greșeală bara de protecție sau nervii altcuiva. <img src="/media/images/site/insurance/asig-01.svg" alt="RCA"/></li>
        <li><strong>CASCO (asigurare voluntară)</strong><br>Pentru cei care vor să doarmă liniștiți. Acoperă furturi, fenomene naturale și alte surprize de pe drum. <img src="/media/images/site/insurance/asig-02.svg" alt="CASCO"/></li>
        <li><strong>Cartea Verde</strong><br>Dacă plănuiți să călătoriți în străinătate cu mașina personală. Fără ea — nu plecați nicăieri. <img src="/media/images/site/insurance/asig-03.svg" alt="VERDE"/></li>
    </ul>

    <p style="font-weight:bold; font-size:1.3rem;">Știm cât de important este:</p>
    <ul>
        <li>Să aveți încredere în ziua de mâine (și în bara de protecție)</li>
        <li>Să nu pierdeți jumătate de zi fugind cu acte</li>
        <li>Să primiți o explicație normală, nu un robot cu față de inspector</li>
    </ul>

    <p style="font-weight:bold; font-size:1.2rem;">De aceea, la noi totul este — simplu, corect și cu respect pentru timpul dumneavoastră.</p>

    <p style="font-weight:bold; font-size:1.3rem;"><strong style="font-size:1.6rem;">Doriți să încheiați o asigurare?</strong><br>Contactați managerul dumneavoastră de la Sauto — și totul va fi gata mai repede decât ați putea spune „CASCO cu franșiză”.</p>'
    );
}
// English insurance content
else {
    $lang_offers['insurance'] = array(
        'name' => 'Auto Insurance',
        'title'=> 'Car Insurance — No hassle, no running around',
        'text' => '
            <p>Bought a car from <strong>Sauto</strong>? Congratulations — now it\'s time to take the final step before hitting the road: getting it insured. No extra trips, no queues, no questions like "Where is your original certificate from another galaxy?".</p>

        <p>Yes, you heard that right. We are not an insurance company. But we work with trusted insurers and can issue your policy right here in our auto center. We\'ll also explain the difference between compulsory and optional insurance — and why you might need both.</p>
  <img src="/media/images/site/insurance/insurance-img1.jpg" alt="Asigurare Auto"/>
        <h3>Why is it convenient to get insured with us?</h3>
        <ul>
            <li><strong>No unnecessary stops</strong><br>Just picked your car? We can insure it right away — no need to leave our office or your sales manager\'s desk.</li>
            <li><strong>Trusted partners</strong><br>They play fair and don\'t vanish when it\'s time to pay out.</li>
            <li><strong>Consultations made simple</strong><br>We explain everything in plain language — no legal fog or office jargon.</li>
            <li><strong>Your documents — on the spot</strong><br>You\'ll get an electronic policy by email and a printed one in a nice envelope. If you like, it can even be signed by your manager — who\'s now almost like family.</li>
        </ul>
<img src="/media/images/site/insurance/insurance-img2.jpg" alt="Asigurarea la noi"/>
        <h3>What types of insurance can you get?</h3>
        <ul>
            <li><strong>Third-party liability (OSAGO)</strong><br>Mandatory for everyone. Covers damage if you accidentally bump someone\'s car — or their nerves. <img src="/media/images/site/insurance/asig-01.svg" alt="RCA"/></li>
            <li><strong>Comprehensive insurance (KASKO)</strong><br>For peace of mind. Covers theft, natural disasters, and other surprises on the road. <img src="/media/images/site/insurance/asig-02.svg" alt="CASCO"/></li>
            <li><strong>Green Card</strong><br>Planning to travel abroad with your car? You\'ll need it to go — and to come back. <img src="/media/images/site/insurance/asig-03.svg" alt="VERDE"/></li>
        </ul>

        <p style="font-weight:bold; font-size:1.3rem;">We understand how important it is to:</p>
        <ul>
            <li>Feel confident about tomorrow (and your bumper)</li>
            <li>Avoid wasting half a day on paperwork</li>
            <li>Get real advice, not a robot with a stern inspector face</li>
        </ul>

        <p style="font-weight:bold; font-size:1.2rem;">That\'s why we keep things simple, honest, and respectful of your time.</p>

        <p style="font-weight:bold; font-size:1.3rem;"><strong style="font-size:1.6rem;">Want to get insured?</strong><br>Just talk to your manager at Sauto — it\'ll be done faster than you can say "KASKO with deductible".</p>'
    );
}

// Use the detailed content if available
if (isset($lng['p']['services']['insurance']['content']) && !empty($lng['p']['services']['insurance']['content'])) {
    $lang_offers['insurance']['text'] = $lng['p']['services']['insurance']['content'];
}
?>
