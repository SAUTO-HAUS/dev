<?php defined('_DOIT') or die('Restricted access'); ?>
<link rel="stylesheet" type="text/css" href="/content/site/page/new_pages/sale/sale.css?<?=rand(0,999)?>">
<script defer src="/content/site/page/new_pages/sale/sale.js?<?=rand(0,999)?>"></script>

<div class="sale-page" id="sale-page">
    <div class="sale-page__decor" aria-hidden="true">
        <span class="sale-page__blob sale-page__blob--one"></span>
        <span class="sale-page__blob sale-page__blob--two"></span>
        <span class="sale-page__spark sale-page__spark--one"></span>
        <span class="sale-page__spark sale-page__spark--two"></span>
    </div>

    <section class="sale-section sale-section--accent sale-section--hero sale-animate" id="sale-hero">
        <div class="sale-section__inner">
            <div class="sale-hero__copy">
                <h1>Продажа авто на реализацию</h1>
                <h2>Продайте свой автомобиль через Sauto Haus – быстро, выгодно и без хлопот</h2>
                <p>Вы привозите машину – мы берём всё на себя: оценку, рекламу, показы, оформление сделки. Деньги получаете вы.</p>
                <p>Продавать машину самому – значит тратить время, общаться с десятками «смотрящих», рисковать с документами и ценой. Мы решаем все эти задачи за вас: профессионально выставляем автомобиль, приводим покупателей и оформляем сделку так, чтобы вы остались в плюсе и без лишних нервов.</p>
                <div class="sale-actions">
                    <button class="sale-btn sale-btn--primary" type="button" data-sale-open="#sale-form">Оставить заявку</button>
                    <div class="sale-actions__note">Привезите авто – остальное сделаем мы</div>
                </div>
            </div>
            <div class="sale-hero__media" aria-hidden="true">
                <div class="sale-hero__planet"></div>
                <div class="sale-hero__ring"></div>
                <div class="sale-hero__car">
                    <svg viewBox="0 0 320 180" focusable="false" role="img">
                        <defs>
                            <linearGradient id="saleHeroBody" x1="0%" y1="50%" x2="100%" y2="50%">
                                <stop offset="0%" stop-color="#ff5252" />
                                <stop offset="60%" stop-color="#e2001a" />
                                <stop offset="100%" stop-color="#a90016" />
                            </linearGradient>
                            <linearGradient id="saleHeroGlass" x1="0%" y1="0%" x2="100%" y2="0%">
                                <stop offset="0%" stop-color="#f6f9ff" />
                                <stop offset="100%" stop-color="#8fc5ff" />
                            </linearGradient>
                        </defs>
                        <path d="M24 120 C50 44 236 40 280 120 L300 120 Q312 120 312 132 L312 146 Q312 158 296 158 L270 158 Q254 174 228 174 Q206 174 196 158 L124 158 Q108 174 80 174 Q54 174 40 158 L16 158 Q8 158 8 146 L8 132 Q8 120 24 120 Z" fill="url(#saleHeroBody)" opacity="0.95"></path>
                        <path d="M66 74 Q112 24 216 26 Q250 28 258 86 L66 86 Z" fill="url(#saleHeroGlass)" opacity="0.9"></path>
                        <circle cx="88" cy="150" r="26" fill="#111317"></circle>
                        <circle cx="88" cy="150" r="16" fill="#f5f5f5"></circle>
                        <circle cx="236" cy="150" r="26" fill="#111317"></circle>
                        <circle cx="236" cy="150" r="16" fill="#f5f5f5"></circle>
                        <path d="M54 128 L262 128" stroke="#ffffff" stroke-width="6" stroke-linecap="round" opacity="0.25"></path>
                        <path d="M72 142 Q96 132 118 142" stroke="#ffd33f" stroke-width="6" stroke-linecap="round"></path>
                        <path d="M214 142 Q238 132 260 142" stroke="#ffd33f" stroke-width="6" stroke-linecap="round"></path>
                    </svg>
                </div>
                <div class="sale-hero__spark sale-hero__spark--one"></div>
                <div class="sale-hero__spark sale-hero__spark--two"></div>
            </div>
        </div>
    </section>

    <section class="sale-section sale-animate" id="sale-advantages">
        <div class="sale-section__header">
            <span class="sale-section__tag">Преимущества</span>
            <h3>Преимущества</h3>
        </div>
        <div class="sale-cards" data-sale-scroll>
            <article class="sale-card">
                <div class="sale-card__icon sale-card__icon--chart"></div>
                <p>- Реальная рыночная цена – продаём дороже, чем перекупы.</p>
            </article>
            <article class="sale-card">
                <div class="sale-card__icon sale-card__icon--document"></div>
                <p>- Полная прозрачность: договор, официальные расчёты.</p>
            </article>
            <article class="sale-card">
                <div class="sale-card__icon sale-card__icon--shield"></div>
                <p>- Ваш автомобиль в безопасности – стоит у нас на охраняемой площадке.</p>
            </article>
            <article class="sale-card">
                <div class="sale-card__icon sale-card__icon--megaphone"></div>
                <p>- Мы берём на себя рекламу, звонки, показы.</p>
            </article>
            <article class="sale-card">
                <div class="sale-card__icon sale-card__icon--speed"></div>
                <p>- Быстрое оформление: деньги получаете сразу после продажи.</p>
            </article>
        </div>
    </section>

    <section class="sale-section sale-animate" id="sale-process">
        <div class="sale-section__header">
            <span class="sale-section__tag">Как это работает</span>
            <h3>Как это работает</h3>
        </div>
        <div class="sale-timeline" data-sale-scroll>
            <ol class="sale-timeline__list">
                <li class="sale-timeline__item"><span>1. Вы привозите автомобиль к нам в автосалон.</span></li>
                <li class="sale-timeline__item"><span>2. Мы проводим визуальный осмотр и тест-драйв.</span></li>
                <li class="sale-timeline__item"><span>3. Подписываем договор на оказание услуги.</span></li>
                <li class="sale-timeline__item"><span>4. Авто проходит мойку и фотосъёмку.</span></li>
                <li class="sale-timeline__item"><span>5. Размещаем объявления на 999.md, Facebook, Instagram, Telegram, TikTok.</span></li>
                <li class="sale-timeline__item"><span>6. Ведём переговоры и показываем автомобиль покупателям.</span></li>
                <li class="sale-timeline__item"><span>7. Организуем сделку и передаём вам деньги.</span></li>
            </ol>
            <span class="sale-timeline__line" aria-hidden="true"></span>
        </div>
    </section>

    <section class="sale-section sale-section--split sale-animate" id="sale-why">
        <div class="sale-section__header">
            <span class="sale-section__tag">Почему это выгоднее</span>
            <h3>Почему это выгоднее, чем продавать самому</h3>
        </div>
        <div class="sale-compare">
            <article class="sale-compare__item">
                <div class="sale-compare__icon sale-compare__icon--time"></div>
                <p>- Не тратите время на звонки и встречи.</p>
            </article>
            <article class="sale-compare__item">
                <div class="sale-compare__icon sale-compare__icon--money"></div>
                <p>- Избегаете риска с поддельными деньгами и серыми схемами.</p>
            </article>
            <article class="sale-compare__item">
                <div class="sale-compare__icon sale-compare__icon--bargain"></div>
                <p>- Мы умеем торговаться и получаем лучшую цену.</p>
            </article>
            <article class="sale-compare__item">
                <div class="sale-compare__icon sale-compare__icon--trust"></div>
                <p>- Покупатель доверяет автосалону больше, чем частнику.</p>
            </article>
        </div>
    </section>

    <section class="sale-section sale-section--accent sale-section--security sale-animate" id="sale-security">
        <div class="sale-section__inner sale-section__inner--security">
            <div class="sale-security__badge" aria-hidden="true">
                <span class="sale-security__shield"></span>
                <span class="sale-security__glow"></span>
            </div>
            <div class="sale-security__text">
                <h3>Безопасность сделки</h3>
                <p>Мы работаем только официально: договор комиссии, все расчёты через банк или наличными под отчёт. Автомобиль хранится на нашей охраняемой стоянке.</p>
            </div>
        </div>
    </section>

    <section class="sale-section sale-animate" id="sale-faq">
        <div class="sale-section__header">
            <span class="sale-section__tag">FAQ</span>
            <h3>FAQ</h3>
        </div>
        <div class="sale-faq" role="list">
            <article class="sale-faq__item" role="listitem">
                <button class="sale-faq__question" type="button" aria-expanded="false">
                    <span class="sale-faq__icon"></span>
                    <span class="sale-faq__label"><strong>– Сколько стоит услуга?</strong></span>
                </button>
                <div class="sale-faq__answer" hidden>
                    <p>Мы работаем по договору комиссии: процент только после продажи.</p>
                </div>
            </article>
            <article class="sale-faq__item" role="listitem">
                <button class="sale-faq__question" type="button" aria-expanded="false">
                    <span class="sale-faq__icon"></span>
                    <span class="sale-faq__label"><strong>– Сколько времени занимает продажа?</strong></span>
                </button>
                <div class="sale-faq__answer" hidden>
                    <p>В среднем от нескольких дней до пары недель – зависит от модели и состояния.</p>
                </div>
            </article>
            <article class="sale-faq__item" role="listitem">
                <button class="sale-faq__question" type="button" aria-expanded="false">
                    <span class="sale-faq__icon"></span>
                    <span class="sale-faq__label"><strong>– Что если машина не продастся?</strong></span>
                </button>
                <div class="sale-faq__answer" hidden>
                    <p>Вы в любой момент можете забрать авто обратно, без штрафов.</p>
                </div>
            </article>
        </div>
    </section>

    <section class="sale-section sale-section--accent sale-section--cta sale-animate" id="sale-cta">
        <div class="sale-cta">
            <div class="sale-cta__glow" aria-hidden="true"></div>
            <h3>Призыв к действию</h3>
            <p>Привезите свой автомобиль сегодня – и уже завтра он появится в продаже на всех площадках. Оставьте заявку прямо сейчас!</p>
            <button class="sale-btn sale-btn--primary sale-btn--large" type="button" data-sale-open="#sale-form">Оставить заявку</button>
        </div>
    </section>

    <section class="sale-section sale-section--form sale-animate" id="sale-form" hidden aria-hidden="true">
        <div class="sale-form">
            <div class="sale-form__glass" aria-hidden="true"></div>
            <div class="sale-form__body">
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
