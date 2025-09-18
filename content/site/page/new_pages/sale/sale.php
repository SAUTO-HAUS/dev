<?php defined('_DOIT') or die('Restricted access'); ?>
<link rel="stylesheet" type="text/css" href="/content/site/page/new_pages/sale/sale.css?<?=rand(0,999)?>">
<script defer src="/content/site/page/new_pages/sale/sale.js?<?=rand(0,999)?>"></script>
<?php
$advantages = [
    '- Реальная рыночная цена – продаём дороже, чем перекупы.',
    '- Полная прозрачность: договор, официальные расчёты.',
    '- Ваш автомобиль в безопасности – стоит у нас на охраняемой площадке.',
    '- Мы берём на себя рекламу, звонки, показы.',
    '- Быстрое оформление: деньги получаете сразу после продажи.',
];

$steps = [
    '1. Вы привозите автомобиль к нам в автосалон.',
    '2. Мы проводим визуальный осмотр и тест-драйв.',
    '3. Подписываем договор на оказание услуги.',
    '4. Авто проходит мойку и фотосъёмку.',
    '5. Размещаем объявления на 999.md, Facebook, Instagram, Telegram, TikTok.',
    '6. Ведём переговоры и показываем автомобиль покупателям.',
    '7. Организуем сделку и передаём вам деньги.',
];

$reasons = [
    '- Не тратите время на звонки и встречи.',
    '- Избегаете риска с поддельными деньгами и серыми схемами.',
    '- Мы умеем торговаться и получаем лучшую цену.',
    '- Покупатель доверяет автосалону больше, чем частнику.',
];

$faq = [
    [
        'question' => '– Сколько стоит услуга?',
        'answer' => 'Мы работаем по договору комиссии: процент только после продажи.',
    ],
    [
        'question' => '– Сколько времени занимает продажа?',
        'answer' => 'В среднем от нескольких дней до пары недель – зависит от модели и состояния.',
    ],
    [
        'question' => '– Что если машина не продастся?',
        'answer' => 'Вы в любой момент можете забрать авто обратно, без штрафов.',
    ],
];
?>

<div class="sale" id="sale-root">
    <div class="sale__background" aria-hidden="true">
        <span class="sale__spark sale__spark--one"></span>
        <span class="sale__spark sale__spark--two"></span>
        <span class="sale__spark sale__spark--three"></span>
    </div>

    <section class="sale-block sale-block--accent sale-hero sale-animate" id="sale-hero">
        <div class="sale-hero__inner">
            <div class="sale-hero__text">
                <h1>Продажа авто на реализацию</h1>
                <h2>Продайте свой автомобиль через Sauto Haus – быстро, выгодно и без хлопот</h2>
                <p>Вы привозите машину – мы берём всё на себя: оценку, рекламу, показы, оформление сделки. Деньги получаете вы.</p>
                <p>Продавать машину самому – значит тратить время, общаться с десятками «смотрящих», рисковать с документами и ценой. Мы решаем все эти задачи за вас: профессионально выставляем автомобиль, приводим покупателей и оформляем сделку так, чтобы вы остались в плюсе и без лишних нервов.</p>
                <div class="sale-actions">
                    <button class="sale-btn sale-btn--primary sale-btn--pulse" type="button" data-sale-form-trigger="#sale-form">Оставить заявку</button>
                </div>
            </div>
            <div class="sale-hero__visual" aria-hidden="true">
                <div class="sale-hero__orb sale-floating"></div>
                <div class="sale-hero__ring sale-floating"></div>
                <div class="sale-hero__car">
                    <svg viewBox="0 0 360 180" role="img" focusable="false">
                        <defs>
                            <linearGradient id="saleCarBody" x1="0%" y1="0%" x2="100%" y2="0%">
                                <stop offset="0%" stop-color="#ff4b4b" />
                                <stop offset="60%" stop-color="#e2001a" />
                                <stop offset="100%" stop-color="#b10014" />
                            </linearGradient>
                            <linearGradient id="saleCarGlass" x1="0%" y1="0%" x2="100%" y2="0%">
                                <stop offset="0%" stop-color="#f8fbff" />
                                <stop offset="100%" stop-color="#8bc5ff" />
                            </linearGradient>
                        </defs>
                        <path d="M40 110 C70 38 260 34 300 110 L320 110 Q330 110 330 120 L330 132 Q330 142 318 142 L298 142 Q288 160 258 160 Q218 160 206 142 L130 142 Q120 160 90 160 Q60 160 46 142 L22 142 Q10 142 10 132 L10 120 Q10 110 22 110 Z" fill="url(#saleCarBody)" opacity="0.95" />
                        <path d="M84 72 Q126 28 236 30 Q268 32 276 80 L84 80" fill="url(#saleCarGlass)" opacity="0.88" />
                        <circle cx="96" cy="148" r="24" fill="#1b1d20" />
                        <circle cx="96" cy="148" r="14" fill="#f9f9f9" />
                        <circle cx="244" cy="148" r="24" fill="#1b1d20" />
                        <circle cx="244" cy="148" r="14" fill="#f9f9f9" />
                        <path d="M60 118 L300 118" stroke="#ffffff" stroke-width="5" stroke-linecap="round" opacity="0.35" />
                        <path d="M70 132 Q92 122 112 132" stroke="#ffd300" stroke-width="6" stroke-linecap="round" />
                        <path d="M220 132 Q242 122 262 132" stroke="#ffd300" stroke-width="6" stroke-linecap="round" />
                    </svg>
                </div>
            </div>
        </div>
    </section>

    <section class="sale-block sale-animate" id="sale-advantages">
        <header class="sale-block__header">
            <div class="sale-block__marker"></div>
            <h3>Преимущества</h3>
        </header>
        <div class="sale-cards sale-scroll" data-sale-scrollable>
            <?php foreach ($advantages as $index => $item): ?>
                <article class="sale-card sale-card--adv sale-animate">
                    <span class="sale-card__icon" aria-hidden="true">
                        <span class="sale-card__spark"></span>
                        <span class="sale-card__counter">0<?= $index + 1; ?></span>
                    </span>
                    <p><?= $item; ?></p>
                </article>
            <?php endforeach; ?>
        </div>
    </section>

    <section class="sale-block sale-animate" id="sale-process">
        <header class="sale-block__header">
            <div class="sale-block__marker"></div>
            <h3>Как это работает</h3>
        </header>
        <div class="sale-timeline" data-sale-scrollable>
            <ol class="sale-timeline__list">
                <?php foreach ($steps as $step): ?>
                    <li class="sale-timeline__item sale-animate"><span><?= $step; ?></span></li>
                <?php endforeach; ?>
            </ol>
            <span class="sale-timeline__rail" aria-hidden="true"></span>
        </div>
    </section>

    <section class="sale-block sale-animate" id="sale-why">
        <header class="sale-block__header">
            <div class="sale-block__marker"></div>
            <h3>Почему это выгоднее, чем продавать самому</h3>
        </header>
        <div class="sale-highlight">
            <?php foreach ($reasons as $reason): ?>
                <article class="sale-highlight__item sale-animate">
                    <span class="sale-highlight__pulse" aria-hidden="true"></span>
                    <p><?= $reason; ?></p>
                </article>
            <?php endforeach; ?>
        </div>
    </section>

    <section class="sale-block sale-block--accent sale-animate" id="sale-security">
        <header class="sale-block__header">
            <div class="sale-block__marker"></div>
            <h3>Безопасность сделки</h3>
        </header>
        <div class="sale-security">
            <div class="sale-security__emblem" aria-hidden="true">
                <svg viewBox="0 0 96 96" focusable="false">
                    <path d="M48 8 L80 20 V42 C80 60 66 82 48 88 C30 82 16 60 16 42 V20 Z" fill="#ffffff" opacity="0.96" />
                    <path d="M48 8 L80 20 V42 C80 60 66 82 48 88 C30 82 16 60 16 42 V20 Z" stroke="#e2001a" stroke-width="4" fill="none" />
                    <path d="M34 46 L46 58 L66 34" stroke="#002e66" stroke-width="6" stroke-linecap="round" stroke-linejoin="round" />
                </svg>
            </div>
            <p>Мы работаем только официально: договор комиссии, все расчёты через банк или наличными под отчёт. Автомобиль хранится на нашей охраняемой стоянке.</p>
        </div>
    </section>

    <section class="sale-block sale-animate" id="sale-faq">
        <header class="sale-block__header">
            <div class="sale-block__marker"></div>
            <h3>FAQ</h3>
        </header>
        <div class="sale-faq" role="list">
            <?php foreach ($faq as $entry): ?>
                <article class="sale-faq__item sale-animate" role="listitem">
                    <button class="sale-faq__question" type="button" aria-expanded="false">
                        <span class="sale-faq__chevron" aria-hidden="true"></span>
                        <span class="sale-faq__label"><strong><?= $entry['question']; ?></strong></span>
                    </button>
                    <div class="sale-faq__answer" hidden>
                        <p><?= $entry['answer']; ?></p>
                    </div>
                </article>
            <?php endforeach; ?>
        </div>
    </section>

    <section class="sale-block sale-block--accent sale-animate sale-cta" id="sale-cta">
        <div class="sale-cta__inner">
            <span class="sale-cta__flare sale-floating" aria-hidden="true"></span>
            <h3>Призыв к действию</h3>
            <p>Привезите свой автомобиль сегодня – и уже завтра он появится в продаже на всех площадках. Оставьте заявку прямо сейчас!</p>
            <button class="sale-btn sale-btn--primary" type="button" data-sale-form-trigger="#sale-form">Оставить заявку</button>
        </div>
    </section>

    <section class="sale-form sale-animate" id="sale-form" hidden aria-hidden="true">
        <div class="sale-form__inner">
            <div class="sale-form__glass" aria-hidden="true"></div>
            <div class="sale-form__content">
                <script data-b24-form="inline/42/u65756" data-skip-moving="true">
                    (function(w,d,u){
                    var s=d.createElement('script');s.async=true;s.src=u+'?'+(Date.now()/180000|0);
                    var h=d.getElementsByTagName('script')[0];h.parentNode.insertBefore(s,h);
                    })(window,document,'https://cdn-ru.bitrix24.ru/b33145896/crm/form/loader_42.js');
                </script>
            </div>
        </div>
    </section>
</div>
