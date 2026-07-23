<?php defined( '_DOIT' ) or die( 'Restricted access' );

/**
 * Frontend B2B translations (ro/ru/en).
 * Self-contained, like crm_lang.php and parsing_lang.php: newer modules in this
 * project carry their own strings instead of extending the global $lng array.
 */

if (!function_exists('b2b_lang')) {

    function b2b_lang_all(): array
    {
        return [
            'ro' => [
                // nav
                'brand'            => 'Partener B2B',
                'cabinet'          => 'Cabinet B2B',
                'header_register'  => 'Înregistrare',
                'login'            => 'Autentificare B2B',
                'register'         => 'Înregistrare B2B',
                'logout'           => 'Ieșire',
                'back_to_site'     => 'Înapoi la site',

                // registration form
                'reg_title'        => 'Cont de partener B2B',
                'reg_sub'          => 'Completați datele de contact. Contul devine activ după validarea de către administrator.',
                'person_type'       => 'Tip persoană',
                'person_company'    => 'Persoană juridică',
                'person_individual' => 'Persoană fizică',
                'full_name'         => 'Nume, prenume',
                'login_field'       => 'Login',
                'email'            => 'Email',
                'phone'            => 'Telefon',
                'password'         => 'Parolă',
                'password_repeat'  => 'Repetă parola',
                'password_hint'    => 'Minimum 8 caractere.',
                'login_hint'       => 'Minimum 4 caractere: litere, cifre, . _ -',
                'phone_hint'       => 'Pe acest număr veți primi codul de autentificare.',
                'reg_submit'       => 'Creează cont',
                'reg_have_account' => 'Aveți deja cont?',
                'reg_success_ttl'  => 'Cont creat cu succes',
                'reg_success'      => 'Contul a fost trimis spre verificare. Veți fi contactat când Super Adminul validează accesul.',
                'pass_mismatch'    => 'Parolele nu coincid.',

                // login
                'login_title'      => 'Autentificare partener',
                'login_sub'        => 'Introduceți datele de acces în contul de partener.',
                'login_submit'     => 'Continuă',
                'login_no_account' => 'Nu aveți cont?',

                // cabinet
                'tab_cars'         => 'Mașini de interes',
                'tab_invoices'     => 'Conturi de plată',
                'tab_requests'     => 'Cereri transmise',
                'tab_activity'     => 'Istoric activitate',
                'welcome'          => 'Bine ați venit',
                'status'           => 'Status',
                'regions'          => 'Regiuni permise',
                'no_cars'          => 'Nu aveți încă mașini salvate. Salvați o mașină din catalog pentru a o urmări aici.',
                'saved_cars'       => 'Salvate',
                'viewed_cars'      => 'Vizualizate',
                'no_invoices'      => 'Nu ați generat încă niciun cont de plată.',
                'no_requests'      => 'Nu ați transmis încă nicio cerere.',
                'no_activity'      => 'Nicio activitate înregistrată.',
                'browse_catalog'   => 'Vezi catalogul',
                'invoice_no'       => 'Număr',
                'invoice_car'      => 'Mașină',
                'invoice_amount'   => 'Avans',
                'invoice_date'     => 'Data',
                'invoice_open'     => 'Deschide',
                'req_comment'      => 'Comentariu',
                'req_status'       => 'Status',
                'remove'           => 'Elimină',

                // statuses
                'st_pending'       => 'În așteptare',
                'st_active'        => 'Activ',
                'st_blocked'       => 'Blocat',
                'st_new'           => 'Nouă',
                'st_seen'          => 'Văzută',
                'st_approved'      => 'Aprobată',
                'st_rejected'      => 'Respinsă',
                'st_issued'        => 'Emis',
                'st_sent'          => 'Transmis',
                'st_paid'          => 'Achitat',
                'st_cancelled'     => 'Anulat',

                // regions
                'rg_korea'         => 'Coreea',
                'rg_europe'        => 'Europa',
                'rg_china'         => 'China',
                'rg_usa'           => 'SUA',

                // audit actions
                'ac_login'               => 'Autentificare',
                'ac_login_failed'        => 'Autentificare eșuată',
                'ac_logout'              => 'Ieșire din cont',
                'ac_page_view'           => 'Vizualizare mașină',
                'ac_generate_invoice'    => 'Generare cont de plată',
                'ac_send_to_admin'       => 'Cerere către Super Admin',
                'ac_region_denied'       => 'Acces refuzat pe regiune',
                'ac_save_car'            => 'Mașină salvată',
                'ac_unsave_car'          => 'Mașină eliminată',
                'ac_register'            => 'Cont creat',
                'ac_password_reset'      => 'Parolă schimbată',
                'ac_status_changed'      => 'Status modificat',
                'ac_permissions_changed' => 'Permisiuni modificate',

                // generic errors
                'err_network'      => 'Eroare de rețea. Încercați din nou.',
                'required'         => 'Câmp obligatoriu',
            ],

            'ru' => [
                'brand'            => 'B2B партнёр',
                'cabinet'          => 'Кабинет B2B',
                'header_register'  => 'Регистрация',
                'login'            => 'Вход B2B',
                'register'         => 'Регистрация B2B',
                'logout'           => 'Выход',
                'back_to_site'     => 'Вернуться на сайт',

                'reg_title'        => 'Аккаунт B2B партнёра',
                'reg_sub'          => 'Заполните контактные данные. Аккаунт активируется после проверки администратором.',
                'person_type'       => 'Тип лица',
                'person_company'    => 'Юридическое лицо',
                'person_individual' => 'Физическое лицо',
                'full_name'         => 'Имя, фамилия',
                'login_field'       => 'Логин',
                'email'            => 'Email',
                'phone'            => 'Телефон',
                'password'         => 'Пароль',
                'password_repeat'  => 'Повторите пароль',
                'password_hint'    => 'Минимум 8 символов.',
                'login_hint'       => 'Минимум 4 символа: буквы, цифры, . _ -',
                'phone_hint'       => 'На этот номер придёт код подтверждения.',
                'reg_submit'       => 'Создать аккаунт',
                'reg_have_account' => 'Уже есть аккаунт?',
                'reg_success_ttl'  => 'Аккаунт создан',
                'reg_success'      => 'Аккаунт отправлен на проверку. С вами свяжутся, когда Супер Админ подтвердит доступ.',
                'pass_mismatch'    => 'Пароли не совпадают.',

                'login_title'      => 'Вход для партнёров',
                'login_sub'        => 'Введите данные доступа к партнёрскому аккаунту.',
                'login_submit'     => 'Продолжить',
                'login_no_account' => 'Нет аккаунта?',

                'tab_cars'         => 'Интересующие авто',
                'tab_invoices'     => 'Счета на оплату',
                'tab_requests'     => 'Отправленные заявки',
                'tab_activity'     => 'История активности',
                'welcome'          => 'Добро пожаловать',
                'status'           => 'Статус',
                'regions'          => 'Доступные регионы',
                'no_cars'          => 'Пока нет сохранённых авто. Сохраните авто из каталога, чтобы следить за ним здесь.',
                'saved_cars'       => 'Сохранённые',
                'viewed_cars'      => 'Просмотренные',
                'no_invoices'      => 'Вы ещё не создавали счетов на оплату.',
                'no_requests'      => 'Вы ещё не отправляли заявок.',
                'no_activity'      => 'Активность не зафиксирована.',
                'browse_catalog'   => 'Смотреть каталог',
                'invoice_no'       => 'Номер',
                'invoice_car'      => 'Автомобиль',
                'invoice_amount'   => 'Аванс',
                'invoice_date'     => 'Дата',
                'invoice_open'     => 'Открыть',
                'req_comment'      => 'Комментарий',
                'req_status'       => 'Статус',
                'remove'           => 'Удалить',

                'st_pending'       => 'Ожидает',
                'st_active'        => 'Активен',
                'st_blocked'       => 'Заблокирован',
                'st_new'           => 'Новая',
                'st_seen'          => 'Просмотрена',
                'st_approved'      => 'Одобрена',
                'st_rejected'      => 'Отклонена',
                'st_issued'        => 'Выставлен',
                'st_sent'          => 'Отправлен',
                'st_paid'          => 'Оплачен',
                'st_cancelled'     => 'Отменён',

                'rg_korea'         => 'Корея',
                'rg_europe'        => 'Европа',
                'rg_china'         => 'Китай',
                'rg_usa'           => 'США',

                'ac_login'               => 'Вход',
                'ac_login_failed'        => 'Неудачный вход',
                'ac_logout'              => 'Выход',
                'ac_page_view'           => 'Просмотр авто',
                'ac_generate_invoice'    => 'Создание счёта',
                'ac_send_to_admin'       => 'Заявка Супер Админу',
                'ac_region_denied'       => 'Отказ по региону',
                'ac_save_car'            => 'Авто сохранено',
                'ac_unsave_car'          => 'Авто удалено',
                'ac_register'            => 'Аккаунт создан',
                'ac_password_reset'      => 'Пароль изменён',
                'ac_status_changed'      => 'Статус изменён',
                'ac_permissions_changed' => 'Права изменены',

                'err_network'      => 'Ошибка сети. Попробуйте ещё раз.',
                'required'         => 'Обязательное поле',
            ],

            'en' => [
                'brand'            => 'B2B partner',
                'cabinet'          => 'B2B cabinet',
                'header_register'  => 'Register',
                'login'            => 'B2B login',
                'register'         => 'B2B registration',
                'logout'           => 'Log out',
                'back_to_site'     => 'Back to site',

                'reg_title'        => 'B2B partner account',
                'reg_sub'          => 'Fill in your contact details. The account is activated after an administrator validates it.',
                'person_type'       => 'Person type',
                'person_company'    => 'Legal entity',
                'person_individual' => 'Individual',
                'full_name'         => 'Full name',
                'login_field'       => 'Login',
                'email'            => 'Email',
                'phone'            => 'Phone',
                'password'         => 'Password',
                'password_repeat'  => 'Repeat password',
                'password_hint'    => 'Minimum 8 characters.',
                'login_hint'       => 'At least 4 characters: letters, digits, . _ -',
                'phone_hint'       => 'Your login code will be sent to this number.',
                'reg_submit'       => 'Create account',
                'reg_have_account' => 'Already have an account?',
                'reg_success_ttl'  => 'Account created',
                'reg_success'      => 'Your account was submitted for review. You will be contacted once the Super Admin validates access.',
                'pass_mismatch'    => 'Passwords do not match.',

                'login_title'      => 'Partner login',
                'login_sub'        => 'Enter your partner account credentials.',
                'login_submit'     => 'Continue',
                'login_no_account' => 'No account yet?',

                'tab_cars'         => 'Cars of interest',
                'tab_invoices'     => 'Payment invoices',
                'tab_requests'     => 'Sent requests',
                'tab_activity'     => 'Activity log',
                'welcome'          => 'Welcome',
                'status'           => 'Status',
                'regions'          => 'Allowed regions',
                'no_cars'          => 'No saved cars yet. Save a car from the catalog to track it here.',
                'saved_cars'       => 'Saved',
                'viewed_cars'      => 'Viewed',
                'no_invoices'      => 'You have not generated any payment invoice yet.',
                'no_requests'      => 'You have not sent any request yet.',
                'no_activity'      => 'No activity recorded.',
                'browse_catalog'   => 'Browse catalog',
                'invoice_no'       => 'Number',
                'invoice_car'      => 'Car',
                'invoice_amount'   => 'Advance',
                'invoice_date'     => 'Date',
                'invoice_open'     => 'Open',
                'req_comment'      => 'Comment',
                'req_status'       => 'Status',
                'remove'           => 'Remove',

                'st_pending'       => 'Pending',
                'st_active'        => 'Active',
                'st_blocked'       => 'Blocked',
                'st_new'           => 'New',
                'st_seen'          => 'Seen',
                'st_approved'      => 'Approved',
                'st_rejected'      => 'Rejected',
                'st_issued'        => 'Issued',
                'st_sent'          => 'Sent',
                'st_paid'          => 'Paid',
                'st_cancelled'     => 'Cancelled',

                'rg_korea'         => 'Korea',
                'rg_europe'        => 'Europe',
                'rg_china'         => 'China',
                'rg_usa'           => 'USA',

                'ac_login'               => 'Login',
                'ac_login_failed'        => 'Failed login',
                'ac_logout'              => 'Logout',
                'ac_page_view'           => 'Car viewed',
                'ac_generate_invoice'    => 'Invoice generated',
                'ac_send_to_admin'       => 'Request to Super Admin',
                'ac_region_denied'       => 'Region access denied',
                'ac_save_car'            => 'Car saved',
                'ac_unsave_car'          => 'Car removed',
                'ac_register'            => 'Account created',
                'ac_password_reset'      => 'Password changed',
                'ac_status_changed'      => 'Status changed',
                'ac_permissions_changed' => 'Permissions changed',

                'err_network'      => 'Network error. Please try again.',
                'required'         => 'Required field',
            ],
        ];
    }

    /** Strings for the current language, falling back to Romanian. */
    function b2b_lang(?string $lang = null): array
    {
        $lang = $lang ?? ($_COOKIE['lang'] ?? 'ro');
        $all  = b2b_lang_all();
        return $all[$lang] ?? $all['ro'];
    }

    /** One HTML-escaped string. */
    function b2b_t(string $key, ?string $lang = null): string
    {
        $t = b2b_lang($lang);
        return htmlspecialchars($t[$key] ?? $key, ENT_QUOTES, 'UTF-8');
    }
}
