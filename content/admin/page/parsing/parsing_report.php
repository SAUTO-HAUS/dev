<?php defined( '_DOIT' ) or die( 'Restricted access' );

/**
 * Renders the Encar inspection/diagnosis/accident report (stored raw Korean in
 * parsing_cars.report_data) as a translated HTML block for the public car page.
 *
 * The Korean source uses small enumerations (양호 = good, 정상 = normal, 없음 =
 * none, etc.) plus fixed section/component titles. We translate the ones we know
 * and fall back to the raw Korean for anything unmapped, so the block degrades
 * gracefully if Encar adds new codes.
 */

if (!function_exists('parsing_report_tr')) {
    // Translate a single Korean term to the requested language. Unknown terms
    // return the original Korean (never blank).
    function parsing_report_tr(string $kr, string $lang): string
    {
        static $dict = null;
        if ($dict === null) {
            $dict = [
                // --- status values ---
                '양호'   => ['ro' => 'Bună',         'ru' => 'Хорошее',     'en' => 'Good'],
                '불량'   => ['ro' => 'Defect',        'ru' => 'Неисправно',  'en' => 'Faulty'],
                '없음'   => ['ro' => 'Fără',          'ru' => 'Нет',          'en' => 'None'],
                '있음'   => ['ro' => 'Prezent',       'ru' => 'Есть',         'en' => 'Present'],
                '적정'   => ['ro' => 'Normal',        'ru' => 'Норма',        'en' => 'Normal'],
                '부족'   => ['ro' => 'Insuficient',   'ru' => 'Недостаточно', 'en' => 'Insufficient'],
                '과다'   => ['ro' => 'Exces',         'ru' => 'Избыток',      'en' => 'Excess'],
                '미세누유'=> ['ro' => 'Scurgere fină', 'ru' => 'Лёгкая течь',  'en' => 'Slight leak'],
                '누유'   => ['ro' => 'Scurgere',      'ru' => 'Течь',         'en' => 'Leak'],
                '미세누수'=> ['ro' => 'Scurgere fină', 'ru' => 'Лёгкая течь',  'en' => 'Slight leak'],
                '누수'   => ['ro' => 'Scurgere',      'ru' => 'Течь',         'en' => 'Leak'],
                '정상'   => ['ro' => 'Normal',        'ru' => 'Норма',        'en' => 'Normal'],
                '교환'   => ['ro' => 'Înlocuit',      'ru' => 'Замена',       'en' => 'Replaced'],
                '교환(교체)' => ['ro' => 'Înlocuit',  'ru' => 'Замена',       'en' => 'Replaced'],
                '판금'   => ['ro' => 'Tinichigerie',  'ru' => 'Рихтовка',     'en' => 'Bodywork'],
                '용접'   => ['ro' => 'Sudură',        'ru' => 'Сварка',       'en' => 'Welding'],
                '절단'   => ['ro' => 'Secționare',    'ru' => 'Резка',        'en' => 'Cutting'],
                '용접,절단' => ['ro' => 'Sudură/secționare','ru' => 'Сварка/резка','en' => 'Welding/cutting'],
                '부식'   => ['ro' => 'Coroziune',     'ru' => 'Коррозия',     'en' => 'Corrosion'],
                '오토'   => ['ro' => 'Automată',      'ru' => 'Автомат',      'en' => 'Automatic'],
                '수동'   => ['ro' => 'Manuală',       'ru' => 'Механика',     'en' => 'Manual'],
                // diagnosis result codes (resultCode values)
                'REPLACEMENT' => ['ro' => 'Înlocuit',  'ru' => 'Замена',     'en' => 'Replaced'],
                'EXCHANGE'    => ['ro' => 'Înlocuit',  'ru' => 'Замена',     'en' => 'Replaced'],
                'WELDING'     => ['ro' => 'Sudat',     'ru' => 'Сварка',     'en' => 'Welded'],
                '판금/용접'   => ['ro' => 'Tinichigerie/sudură','ru' => 'Рихтовка/сварка','en' => 'Bodywork/welding'],
                '판금'        => ['ro' => 'Tinichigerie','ru' => 'Рихтовка',  'en' => 'Bodywork'],
                '교환/판금'   => ['ro' => 'Înlocuit/tinichigerie','ru' => 'Замена/рихтовка','en' => 'Replaced/bodywork'],
                'SHEET_METAL' => ['ro' => 'Tinichigerie','ru' => 'Рихтовка', 'en' => 'Bodywork'],
                'SCRATCH'     => ['ro' => 'Zgârietură','ru' => 'Царапина',   'en' => 'Scratch'],
                'DENT'        => ['ro' => 'Lovitură',  'ru' => 'Вмятина',    'en' => 'Dent'],

                // --- frame structure panel names (inspection.outers, Korean) ---
                '프론트 패널'      => ['ro' => 'Panou frontal',         'ru' => 'Передняя панель',       'en' => 'Front panel'],
                '크로스 멤버'      => ['ro' => 'Traversă',              'ru' => 'Поперечина',            'en' => 'Cross member'],
                '인사이드 패널'    => ['ro' => 'Panou interior',        'ru' => 'Внутренняя панель',     'en' => 'Inner panel'],
                '사이드 멤버(좌)'  => ['ro' => 'Lonjeron (stânga)',     'ru' => 'Лонжерон (лев)',        'en' => 'Side member (L)'],
                '사이드 멤버(우)'  => ['ro' => 'Lonjeron (dreapta)',    'ru' => 'Лонжерон (прав)',       'en' => 'Side member (R)'],
                '휠하우스(좌)'     => ['ro' => 'Carcasă roată (stânga)','ru' => 'Колёсная арка (лев)',   'en' => 'Wheel house (L)'],
                '휠하우스(우)'     => ['ro' => 'Carcasă roată (dreapta)','ru' => 'Колёсная арка (прав)', 'en' => 'Wheel house (R)'],
                '필러 패널(좌)'    => ['ro' => 'Stâlp (stânga)',        'ru' => 'Стойка (лев)',          'en' => 'Pillar (L)'],
                '필러 패널(우)'    => ['ro' => 'Stâlp (dreapta)',       'ru' => 'Стойка (прав)',         'en' => 'Pillar (R)'],
                '대쉬 패널'        => ['ro' => 'Panou bord',            'ru' => 'Панель приборов',       'en' => 'Dash panel'],
                '플로어 패널'      => ['ro' => 'Podea',                 'ru' => 'Пол',                   'en' => 'Floor panel'],
                '트렁크 플로어'    => ['ro' => 'Podea portbagaj',       'ru' => 'Пол багажника',         'en' => 'Trunk floor'],
                '리어 패널'        => ['ro' => 'Panou spate',           'ru' => 'Задняя панель',         'en' => 'Rear panel'],
                '패키지 트레이'    => ['ro' => 'Poliță spate',          'ru' => 'Полка',                 'en' => 'Package tray'],
                '사이드실 패널(좌)'=> ['ro' => 'Prag (stânga)',         'ru' => 'Порог (лев)',           'en' => 'Side sill (L)'],
                '사이드실 패널(우)'=> ['ro' => 'Prag (dreapta)',        'ru' => 'Порог (прав)',          'en' => 'Side sill (R)'],
                '쿼터 패널(좌)'    => ['ro' => 'Aripă spate (stânga)',  'ru' => 'Заднее крыло (лев)',    'en' => 'Quarter panel (L)'],
                '쿼터 패널(우)'    => ['ro' => 'Aripă spate (dreapta)', 'ru' => 'Заднее крыло (прав)',   'en' => 'Quarter panel (R)'],
                '루프 패널'        => ['ro' => 'Plafon',                'ru' => 'Крыша',                 'en' => 'Roof panel'],
                '프론트 휀더(좌)'  => ['ro' => 'Aripă față (stânga)',   'ru' => 'Переднее крыло (лев)',  'en' => 'Front fender (L)'],
                '프론트 휀더(우)'  => ['ro' => 'Aripă față (dreapta)',  'ru' => 'Переднее крыло (прав)', 'en' => 'Front fender (R)'],
                '프론트 사이드 멤버(좌)' => ['ro' => 'Lonjeron frontal (stânga)','ru' => 'Передний лонжерон (лев)','en' => 'Front side member (L)'],
                '프론트 사이드 멤버(우)' => ['ro' => 'Lonjeron frontal (dreapta)','ru' => 'Передний лонжерон (прав)','en' => 'Front side member (R)'],
                '인사이드 패널(좌)' => ['ro' => 'Panou interior (stânga)','ru' => 'Внутренняя панель (лев)','en' => 'Inside panel (L)'],
                '인사이드 패널(우)' => ['ro' => 'Panou interior (dreapta)','ru' => 'Внутренняя панель (прав)','en' => 'Inside panel (R)'],
                '프론트 휠하우스(좌)' => ['ro' => 'Carcasă roată față (stânga)','ru' => 'Передняя колёсная арка (лев)','en' => 'Front wheel house (L)'],
                '프론트 휠하우스(우)' => ['ro' => 'Carcasă roată față (dreapta)','ru' => 'Передняя колёсная арка (прав)','en' => 'Front wheel house (R)'],
                '후드'             => ['ro' => 'Capotă',                'ru' => 'Капот',                 'en' => 'Hood'],
                '트렁크 리드'      => ['ro' => 'Capac portbagaj',       'ru' => 'Крышка багажника',      'en' => 'Trunk lid'],
                '라디에이터 서포트(볼트체결부품)' => ['ro' => 'Suport radiator (prins în șuruburi)','ru' => 'Суппорт радиатора (на болтах)','en' => 'Radiator support (bolt-on)'],
                '라디에이터 서포트' => ['ro' => 'Suport radiator',       'ru' => 'Суппорт радиатора',     'en' => 'Radiator support'],
                '도어(좌)'         => ['ro' => 'Ușă (stânga)',          'ru' => 'Дверь (лев)',           'en' => 'Door (L)'],
                '도어(우)'         => ['ro' => 'Ușă (dreapta)',         'ru' => 'Дверь (прав)',          'en' => 'Door (R)'],
                '프론트 도어(좌)'  => ['ro' => 'Ușă față (stânga)',     'ru' => 'Передняя дверь (лев)',  'en' => 'Front door (L)'],
                '프론트 도어(우)'  => ['ro' => 'Ușă față (dreapta)',    'ru' => 'Передняя дверь (прав)', 'en' => 'Front door (R)'],
                '리어 도어(좌)'    => ['ro' => 'Ușă spate (stânga)',    'ru' => 'Задняя дверь (лев)',    'en' => 'Rear door (L)'],
                '리어 도어(우)'    => ['ro' => 'Ușă spate (dreapta)',   'ru' => 'Задняя дверь (прав)',   'en' => 'Rear door (R)'],

                // --- inner section titles (inspection.inners[].type.title) ---
                '자기진단' => ['ro' => 'Autodiagnostic',        'ru' => 'Самодиагностика',  'en' => 'Self-diagnosis'],
                '원동기'   => ['ro' => 'Motor',                  'ru' => 'Двигатель',        'en' => 'Engine'],
                '변속기'   => ['ro' => 'Transmisie',             'ru' => 'Коробка передач',  'en' => 'Transmission'],
                '동력전달' => ['ro' => 'Transmisia puterii',     'ru' => 'Силовая передача', 'en' => 'Power transmission'],
                '조향'     => ['ro' => 'Direcție',               'ru' => 'Рулевое',          'en' => 'Steering'],
                '제동'     => ['ro' => 'Frânare',                'ru' => 'Тормоза',          'en' => 'Brakes'],
                '전기'     => ['ro' => 'Sistem electric',        'ru' => 'Электрика',        'en' => 'Electrical'],
                '연료'     => ['ro' => 'Sistem de combustibil',  'ru' => 'Топливная система','en' => 'Fuel system'],

                // --- technical sub-components (inners children titles) ---
                '작동상태(공회전)' => ['ro' => 'Funcționare (ralanti)',  'ru' => 'Работа (холостой ход)',  'en' => 'Operation (idle)'],
                '작동상태'         => ['ro' => 'Stare de funcționare',   'ru' => 'Рабочее состояние',      'en' => 'Operating state'],
                '오일누유'         => ['ro' => 'Scurgeri de ulei',       'ru' => 'Утечка масла',           'en' => 'Oil leaks'],
                '냉각수누수'       => ['ro' => 'Scurgeri lichid răcire', 'ru' => 'Утечка ОЖ',              'en' => 'Coolant leaks'],
                '오일 유량'        => ['ro' => 'Nivel ulei',             'ru' => 'Уровень масла',          'en' => 'Oil level'],
                '실린더 커버(로커암 커버)' => ['ro' => 'Capac chiulasă',  'ru' => 'Крышка ГБЦ',             'en' => 'Rocker cover'],
                '실린더 헤드 / 개스킷'    => ['ro' => 'Chiulasă / garnitură','ru' => 'ГБЦ / прокладка',     'en' => 'Cylinder head / gasket'],
                '실린더 블록 / 오일팬'    => ['ro' => 'Bloc motor / baie ulei','ru' => 'Блок / поддон',    'en' => 'Block / oil pan'],
                '워터펌프'         => ['ro' => 'Pompă de apă',           'ru' => 'Водяной насос',          'en' => 'Water pump'],
                '라디에이터'       => ['ro' => 'Radiator',               'ru' => 'Радиатор',               'en' => 'Radiator'],
                '냉각수 수량'      => ['ro' => 'Nivel lichid răcire',    'ru' => 'Уровень ОЖ',             'en' => 'Coolant level'],
                '자동변속기(A/T)'  => ['ro' => 'Cutie automată (A/T)',   'ru' => 'АКПП (A/T)',             'en' => 'Automatic (A/T)'],
                '오일유량 및 상태' => ['ro' => 'Nivel și stare ulei',    'ru' => 'Уровень и состояние масла','en' => 'Oil level & state'],
                '클러치 어셈블리'  => ['ro' => 'Ansamblu ambreiaj',      'ru' => 'Сцепление в сборе',      'en' => 'Clutch assembly'],
                '등속조인트'       => ['ro' => 'Cuplaj homocinetic',     'ru' => 'ШРУС',                   'en' => 'CV joint'],
                '추친축 및 베어링' => ['ro' => 'Arbore + rulmenți',      'ru' => 'Вал и подшипники',       'en' => 'Driveshaft & bearings'],
                '디피렌셜 기어'    => ['ro' => 'Diferențial',            'ru' => 'Дифференциал',           'en' => 'Differential'],
                '동력조향 작동 오일 누유' => ['ro' => 'Scurgeri ulei servodirecție','ru' => 'Утечка масла ГУР','en' => 'Power steering leak'],
                '스티어링 펌프'    => ['ro' => 'Pompă servodirecție',    'ru' => 'Насос ГУР',              'en' => 'Steering pump'],
                '스티어링 기어(MDPS포함)' => ['ro' => 'Casetă direcție (MDPS)','ru' => 'Рулевая рейка (MDPS)','en' => 'Steering gear (MDPS)'],
                '스티어링 조인트'  => ['ro' => 'Cuplaj direcție',        'ru' => 'Рулевой шарнир',         'en' => 'Steering joint'],
                '파워고압호스'     => ['ro' => 'Furtun înaltă presiune', 'ru' => 'Шланг высокого давления','en' => 'High-pressure hose'],
                '타이로드엔드 및 볼 조인트' => ['ro' => 'Capete bară + pivoți','ru' => 'Наконечники и шаровые','en' => 'Tie rods & ball joints'],
                '브레이크 마스터 실린더오일 누유' => ['ro' => 'Scurgeri cilindru principal frână','ru' => 'Утечка ГТЦ','en' => 'Master cylinder leak'],
                '브레이크 오일 누유' => ['ro' => 'Scurgeri lichid frână','ru' => 'Утечка тормозной жидкости','en' => 'Brake fluid leak'],
                '배력장치 상태'    => ['ro' => 'Servofrână',             'ru' => 'Вакуумный усилитель',    'en' => 'Brake booster'],
                '발전기 출력'      => ['ro' => 'Alternator',             'ru' => 'Генератор',              'en' => 'Alternator'],
                '시동 모터'        => ['ro' => 'Demaror',                'ru' => 'Стартер',                'en' => 'Starter motor'],
                '와이퍼 모터 기능' => ['ro' => 'Motor ștergătoare',      'ru' => 'Мотор стеклоочистителя', 'en' => 'Wiper motor'],
                '실내송풍 모터'    => ['ro' => 'Motor ventilație',       'ru' => 'Мотор вентилятора салона','en' => 'Blower motor'],
                '라디에이터 팬 모터' => ['ro' => 'Motor ventilator radiator','ru' => 'Мотор вентилятора радиатора','en' => 'Radiator fan motor'],
                '윈도우 모터'      => ['ro' => 'Motor geamuri',          'ru' => 'Мотор стеклоподъёмника', 'en' => 'Window motor'],
                '연료누출(LP가스포함)' => ['ro' => 'Scurgeri combustibil','ru' => 'Утечка топлива',        'en' => 'Fuel leak'],
                // common-rail / manual gearbox children seen on diesel cars
                '커먼레일'       => ['ro' => 'Common rail',           'ru' => 'Common rail',          'en' => 'Common rail'],
                '수동변속기(M/T)'=> ['ro' => 'Cutie manuală (M/T)',   'ru' => 'МКПП (M/T)',           'en' => 'Manual (M/T)'],
                '기어변속장치'   => ['ro' => 'Mecanism schimbare viteze','ru' => 'Механизм КПП',       'en' => 'Gear shift mechanism'],

                // --- high-voltage system (EV / hybrid only) ---
                '고전원전기장치' => ['ro' => 'Sistem electric înaltă tensiune','ru' => 'Высоковольтная система','en' => 'High-voltage system'],
                '충전구 절연 상태' => ['ro' => 'Izolație port de încărcare','ru' => 'Изоляция порта зарядки','en' => 'Charging port insulation'],
                '구동축전지 격리 상태' => ['ro' => 'Izolare baterie de tracțiune','ru' => 'Изоляция тяговой батареи','en' => 'Drive battery isolation'],
                '고전원전기배선 상태(접속단자, 피복, 보호기구)' => ['ro' => 'Cablaj înaltă tensiune (borne, izolație, protecții)','ru' => 'Высоковольтная проводка (клеммы, изоляция, защита)','en' => 'HV wiring (terminals, insulation, guards)'],

                // --- master.detail keys (English keys -> labels) ---
                'modelYear'            => ['ro' => 'An model',          'ru' => 'Год выпуска',        'en' => 'Model year'],
                'vin'                  => ['ro' => 'VIN',               'ru' => 'VIN',                'en' => 'VIN'],
                'mileage'              => ['ro' => 'Kilometraj',        'ru' => 'Пробег',             'en' => 'Mileage'],
                'motorType'            => ['ro' => 'Tip motor',         'ru' => 'Тип двигателя',      'en' => 'Engine type'],
                'firstRegistrationDate'=> ['ro' => 'Prima înmatriculare','ru' => 'Первая регистрация','en' => 'First registration'],
                'transmissionType'     => ['ro' => 'Transmisie',        'ru' => 'Трансмиссия',        'en' => 'Transmission'],
                'boardStateType'       => ['ro' => 'Stare caroserie',   'ru' => 'Состояние кузова',   'en' => 'Body state'],
                'carStateType'         => ['ro' => 'Stare vehicul',     'ru' => 'Состояние авто',     'en' => 'Vehicle state'],
                'guarantyType'         => ['ro' => 'Garanție',          'ru' => 'Гарантия',           'en' => 'Warranty'],
                'usageChangeTypes'     => ['ro' => 'Utilizare',         'ru' => 'Использование',      'en' => 'Usage'],
                'tuning'               => ['ro' => 'Tuning',            'ru' => 'Тюнинг',             'en' => 'Tuning'],
                'recall'               => ['ro' => 'Rechemare service',   'ru' => 'Отзыв',              'en' => 'Recall'],
                'waterlog'             => ['ro' => 'Inundație',         'ru' => 'Затопление',         'en' => 'Flood'],
                // master enum titles
                '보험사보증' => ['ro' => 'Garanție asigurator', 'ru' => 'Гарантия страховщика', 'en' => 'Insurer warranty'],
                '자가보증'   => ['ro' => 'Garanție proprie',     'ru' => 'Собственная гарантия', 'en' => 'Self warranty'],
                '보증'       => ['ro' => 'Cu garanție',          'ru' => 'С гарантией',          'en' => 'Warranty'],
                '책임보험'   => ['ro' => 'Asigurare obligatorie','ru' => 'ОСАГО',                'en' => 'Liability insurance'],
                '렌트'       => ['ro' => 'Închiriere',          'ru' => 'Аренда',               'en' => 'Rental'],

                // --- option catalog names (Encar standard options) ---
                '헤드램프'            => ['ro' => 'Faruri',                    'ru' => 'Фары',                          'en' => 'Headlamps'],
                '브레이크 잠김 방지(ABS)' => ['ro' => 'ABS',                  'ru' => 'ABS',                           'en' => 'ABS'],
                '에어백'              => ['ro' => 'Airbag',                   'ru' => 'Подушка безопасности',          'en' => 'Airbag'],
                '전자제어 서스펜션(ECS)' => ['ro' => 'Suspensie electronică (ECS)','ru' => 'Электронная подвеска (ECS)', 'en' => 'Electronic suspension (ECS)'],
                '주차감지센서'        => ['ro' => 'Senzori de parcare',       'ru' => 'Парктроник',                    'en' => 'Parking sensors'],
                'CD 플레이어'         => ['ro' => 'CD player',                'ru' => 'CD-проигрыватель',              'en' => 'CD player'],
                '크루즈 컨트롤'       => ['ro' => 'Cruise control',           'ru' => 'Круиз-контроль',                'en' => 'Cruise control'],
                '앞좌석 AV 모니터'    => ['ro' => 'Monitor AV față',          'ru' => 'AV-монитор (перед)',            'en' => 'Front AV monitor'],
                '커튼/블라인드'       => ['ro' => 'Parasolare',               'ru' => 'Шторки/жалюзи',                 'en' => 'Curtains/blinds'],
                '내비게이션'          => ['ro' => 'Navigație',                'ru' => 'Навигация',                     'en' => 'Navigation'],
                '파워 도어록'         => ['ro' => 'Închidere centralizată',   'ru' => 'Центральный замок',             'en' => 'Power door lock'],
                '전동시트'            => ['ro' => 'Scaune electrice',          'ru' => 'Электросиденья',                'en' => 'Power seats'],
                '파워 윈도우'         => ['ro' => 'Geamuri electrice',         'ru' => 'Электростеклоподъёмники',       'en' => 'Power windows'],
                '열선시트'            => ['ro' => 'Scaune încălzite',          'ru' => 'Подогрев сидений',              'en' => 'Heated seats'],
                '파워 스티어링 휠'    => ['ro' => 'Servodirecție',             'ru' => 'Усилитель руля',                'en' => 'Power steering'],
                '메모리 시트'         => ['ro' => 'Scaune cu memorie',         'ru' => 'Память сидений',                'en' => 'Memory seats'],
                '통풍시트'            => ['ro' => 'Scaune ventilate',          'ru' => 'Вентиляция сидений',            'en' => 'Ventilated seats'],
                '선루프'              => ['ro' => 'Trapă',                     'ru' => 'Люк',                           'en' => 'Sunroof'],
                '가죽시트'            => ['ro' => 'Scaune din piele',          'ru' => 'Кожаные сиденья',               'en' => 'Leather seats'],
                '무선도어 잠금장치'   => ['ro' => 'Acces fără cheie uși',      'ru' => 'Беспроводной замок',            'en' => 'Wireless door lock'],
                '알루미늄 휠'         => ['ro' => 'Jante din aliaj',           'ru' => 'Легкосплавные диски',           'en' => 'Alloy wheels'],
                '미끄럼 방지(TCS)'    => ['ro' => 'Control tracțiune (TCS)',   'ru' => 'Антипробуксовка (TCS)',         'en' => 'Traction control (TCS)'],
                '에어백(사이드)'      => ['ro' => 'Airbag-uri laterale',       'ru' => 'Боковые подушки',               'en' => 'Side airbags'],
                '자동 에어컨'         => ['ro' => 'Climatronic',               'ru' => 'Климат-контроль',               'en' => 'Climate control'],
                '전동접이 사이드 미러' => ['ro' => 'Oglinzi rabatabile electric','ru' => 'Складные зеркала',             'en' => 'Power folding mirrors'],
                'ECM 룸미러'          => ['ro' => 'Oglindă ECM',               'ru' => 'ECM-зеркало',                   'en' => 'ECM mirror'],
                '스티어링 휠 리모컨'  => ['ro' => 'Comenzi pe volan',          'ru' => 'Управление на руле',            'en' => 'Steering wheel controls'],
                '타이어 공기압센서(TPMS)' => ['ro' => 'Senzori presiune anvelope','ru' => 'Датчик давления шин (TPMS)', 'en' => 'TPMS'],
                '뒷좌석 AV 모니터'    => ['ro' => 'Monitor AV spate',          'ru' => 'AV-монитор (зад)',              'en' => 'Rear AV monitor'],
                '차체자세 제어장치(ESC)' => ['ro' => 'Control stabilitate (ESC)','ru' => 'Контроль устойчивости (ESC)', 'en' => 'Stability control (ESC)'],
                '에어백(커튼)'        => ['ro' => 'Airbag-uri tip cortină',    'ru' => 'Шторки безопасности',           'en' => 'Curtain airbags'],
                '스마트키'            => ['ro' => 'Smart key',                 'ru' => 'Смарт-ключ',                    'en' => 'Smart key'],
                '후방 카메라'         => ['ro' => 'Cameră marșarier',          'ru' => 'Камера заднего вида',           'en' => 'Rear camera'],
                '파워 전동 트렁크'    => ['ro' => 'Portbagaj electric',        'ru' => 'Электропривод багажника',       'en' => 'Power trunk'],
                '루프랙'              => ['ro' => 'Bare de plafon',            'ru' => 'Рейлинги',                      'en' => 'Roof rack'],
                'AUX 단자'            => ['ro' => 'Port AUX',                  'ru' => 'Разъём AUX',                    'en' => 'AUX port'],
                'USB 단자'            => ['ro' => 'Port USB',                  'ru' => 'Разъём USB',                    'en' => 'USB port'],
                '하이패스'            => ['ro' => 'Hi-Pass (taxare)',          'ru' => 'Hi-Pass',                       'en' => 'Hi-Pass'],
                '고스트 도어 클로징'  => ['ro' => 'Închidere lină uși',        'ru' => 'Доводчик дверей',               'en' => 'Soft-close doors'],
                '레인센서'            => ['ro' => 'Senzor de ploaie',          'ru' => 'Датчик дождя',                  'en' => 'Rain sensor'],
                '열선 스티어링 휠'    => ['ro' => 'Volan încălzit',            'ru' => 'Подогрев руля',                 'en' => 'Heated steering wheel'],
                '전동 조절 스티어링 휠' => ['ro' => 'Volan reglabil electric',  'ru' => 'Электрорегулировка руля',       'en' => 'Power steering column'],
                '패들 시프트'         => ['ro' => 'Padele pe volan',           'ru' => 'Подрулевые лепестки',           'en' => 'Paddle shifters'],
                '후측방 경보 시스템'  => ['ro' => 'Avertizare unghi mort',     'ru' => 'Контроль слепых зон',           'en' => 'Blind spot warning'],
                '360도 어라운드 뷰'   => ['ro' => 'Cameră 360°',               'ru' => 'Камера 360°',                   'en' => '360° camera'],
                '차선이탈 경보 시스템(LDWS)' => ['ro' => 'Avertizare părăsire bandă','ru' => 'Контроль полосы (LDWS)',    'en' => 'Lane departure (LDWS)'],
                '전동시트(뒷좌석)'    => ['ro' => 'Scaune electrice (spate)',  'ru' => 'Электросиденья (зад)',          'en' => 'Power seats (rear)'],
                '통풍시트(뒷좌석)'    => ['ro' => 'Scaune ventilate (spate)',  'ru' => 'Вентиляция сидений (зад)',      'en' => 'Ventilated seats (rear)'],
                '마사지 시트'         => ['ro' => 'Scaune cu masaj',           'ru' => 'Сиденья с массажем',            'en' => 'Massage seats'],
                '전자식 주차브레이크(EPB)' => ['ro' => 'Frână de parcare electrică','ru' => 'Электронный стояночный тормоз','en' => 'Electronic parking brake'],
                '헤드업 디스플레이(HUD)' => ['ro' => 'Head-Up Display (HUD)',   'ru' => 'Проекционный дисплей (HUD)',    'en' => 'Head-Up Display'],
                '블루투스'            => ['ro' => 'Bluetooth',                 'ru' => 'Bluetooth',                     'en' => 'Bluetooth'],
                '오토 라이트'         => ['ro' => 'Faruri automate',           'ru' => 'Автосвет',                      'en' => 'Auto lights'],

                // --- option SUB-items (shown in parentheses next to the parent) ---
                '헤드램프(HID)'       => ['ro' => 'xenon',                    'ru' => 'ксеноновые',                    'en' => 'xenon'],
                '헤드램프(LED)'       => ['ro' => 'LED',                      'ru' => 'светодиодные',                  'en' => 'LED'],
                '에어백(운전석)'      => ['ro' => 'șofer',                    'ru' => 'водителя',                      'en' => 'driver'],
                '에어백(동승석)'      => ['ro' => 'pasager',                  'ru' => 'пассажира',                     'en' => 'passenger'],
                '주차감지센서(전방)'  => ['ro' => 'față',                     'ru' => 'передние',                      'en' => 'front'],
                '주차감지센서(후방)'  => ['ro' => 'spate',                    'ru' => 'задние',                        'en' => 'rear'],
                '크루즈 컨트롤(일반)' => ['ro' => 'standard',                 'ru' => 'стандартный',                   'en' => 'standard'],
                '크루즈 컨트롤(어댑티브)' => ['ro' => 'adaptiv',              'ru' => 'адаптивный',                    'en' => 'adaptive'],
                '커튼/블라인드(뒷좌석)' => ['ro' => 'spate',                  'ru' => 'задние сиденья',                'en' => 'rear seats'],
                '커튼/블라인드(후방)' => ['ro' => 'lunetă',                   'ru' => 'задняя часть',                  'en' => 'rear window'],
                '전동시트(운전석)'    => ['ro' => 'șofer',                    'ru' => 'водительское',                  'en' => 'driver'],
                '전동시트(동승석)'    => ['ro' => 'pasager',                  'ru' => 'пассажирское',                  'en' => 'passenger'],
                '열선시트(앞좌석)'    => ['ro' => 'față',                     'ru' => 'передние',                      'en' => 'front'],
                '열선시트(뒷좌석)'    => ['ro' => 'spate',                    'ru' => 'задние',                        'en' => 'rear'],
                '메모리 시트(운전석)' => ['ro' => 'șofer',                    'ru' => 'водителя',                      'en' => 'driver'],
                '메모리 시트(동승석)' => ['ro' => 'pasager',                  'ru' => 'пассажира',                     'en' => 'passenger'],
                '통풍시트(운전석)'    => ['ro' => 'șofer',                    'ru' => 'водительское',                  'en' => 'driver'],
                '통풍시트(동승석)'    => ['ro' => 'pasager',                  'ru' => 'пассажирское',                  'en' => 'passenger'],

                // --- diagnosis panel names (diagnosis.items[].name) ---
                'FRONT_DOOR_LEFT'   => ['ro' => 'Ușă față stânga',   'ru' => 'Передняя левая дверь',  'en' => 'Front left door'],
                'FRONT_DOOR_RIGHT'  => ['ro' => 'Ușă față dreapta',  'ru' => 'Передняя правая дверь', 'en' => 'Front right door'],
                'BACK_DOOR_LEFT'    => ['ro' => 'Ușă spate stânga',  'ru' => 'Задняя левая дверь',    'en' => 'Rear left door'],
                'BACK_DOOR_RIGHT'   => ['ro' => 'Ușă spate dreapta', 'ru' => 'Задняя правая дверь',   'en' => 'Rear right door'],
                'HOOD'              => ['ro' => 'Capotă',            'ru' => 'Капот',                'en' => 'Hood'],
                'TRUNK_LID'         => ['ro' => 'Portbagaj',         'ru' => 'Крышка багажника',     'en' => 'Trunk lid'],
                'FRONT_FENDER_LEFT' => ['ro' => 'Aripă față stânga', 'ru' => 'Переднее левое крыло',  'en' => 'Front left fender'],
                'FRONT_FENDER_RIGHT'=> ['ro' => 'Aripă față dreapta','ru' => 'Переднее правое крыло', 'en' => 'Front right fender'],
                'ROOF_PANEL'        => ['ro' => 'Plafon',            'ru' => 'Крыша',                'en' => 'Roof'],
                'QUARTER_PANEL_LEFT'=> ['ro' => 'Aripă spate stânga','ru' => 'Заднее левое крыло',   'en' => 'Rear left quarter'],
                'QUARTER_PANEL_RIGHT'=>['ro' => 'Aripă spate dreapta','ru' => 'Заднее правое крыло', 'en' => 'Rear right quarter'],

                // diagnosis result codes
                'NORMAL'   => ['ro' => 'Normal',     'ru' => 'Норма',      'en' => 'Normal'],
                'EXCHANGE' => ['ro' => 'Înlocuit',   'ru' => 'Замена',     'en' => 'Replaced'],
                'WELD'     => ['ro' => 'Sudat',      'ru' => 'Сварка',     'en' => 'Welded'],
                'CORROSION'=> ['ro' => 'Coroziune',  'ru' => 'Коррозия',   'en' => 'Corrosion'],

                // --- etcs: repair-needed + basic-items section (성능점검 footer) ---
                '수리필요'  => ['ro' => 'Necesită reparație',   'ru' => 'Требует ремонта',       'en' => 'Repair needed'],
                '기본품목'  => ['ro' => 'Dotări de bază',        'ru' => 'Базовая комплектация',  'en' => 'Basic items'],
                '외장'      => ['ro' => 'Exterior',              'ru' => 'Экстерьер',             'en' => 'Exterior'],
                '내장'      => ['ro' => 'Interior',              'ru' => 'Интерьер',              'en' => 'Interior'],
                '광택'      => ['ro' => 'Polish',                'ru' => 'Полировка',             'en' => 'Polish'],
                '룸 클리링' => ['ro' => 'Curățenie habitaclu',   'ru' => 'Химчистка салона',      'en' => 'Interior cleaning'],
                '룸클리닝'  => ['ro' => 'Curățenie habitaclu',   'ru' => 'Химчистка салона',      'en' => 'Interior cleaning'],
                '휠'        => ['ro' => 'Jante',                 'ru' => 'Диски',                 'en' => 'Wheels'],
                '타이어'    => ['ro' => 'Anvelope',              'ru' => 'Шины',                  'en' => 'Tyres'],
                '유리'      => ['ro' => 'Geamuri',               'ru' => 'Стёкла',                'en' => 'Glass'],
                '보유상태'  => ['ro' => 'Accesorii de bord',     'ru' => 'Наличие',               'en' => 'On-board kit'],
                '사용설명서'=> ['ro' => 'Manual de utilizare',   'ru' => 'Инструкция',            'en' => 'Owner manual'],
                '안전삼각대'=> ['ro' => 'Triunghi reflectorizant','ru' => 'Аварийный знак',       'en' => 'Warning triangle'],
                '잭'        => ['ro' => 'Cric',                  'ru' => 'Домкрат',               'en' => 'Jack'],
                '스패너'    => ['ro' => 'Cheie roți',            'ru' => 'Ключ',                  'en' => 'Wheel wrench'],
                '운전석 전' => ['ro' => 'Față șofer',            'ru' => 'Перед водителя',        'en' => 'Front driver'],
                '운전석 후' => ['ro' => 'Spate șofer',           'ru' => 'Зад водителя',          'en' => 'Rear driver'],
                '동반석 전' => ['ro' => 'Față pasager',          'ru' => 'Перед пассажира',       'en' => 'Front passenger'],
                '동반석 후' => ['ro' => 'Spate pasager',         'ru' => 'Зад пассажира',         'en' => 'Rear passenger'],
                '응급'      => ['ro' => 'Rezervă',               'ru' => 'Запаска',               'en' => 'Spare'],
            ];
        }
        $kr = trim($kr);
        if ($kr === '') return '';
        if (isset($dict[$kr][$lang])) return $dict[$kr][$lang];
        if (isset($dict[$kr]['ro'])) return $dict[$kr]['ro'];
        return $kr; // graceful fallback to original Korean
    }

    // UI labels for the report block.
    function parsing_report_label(string $key, string $lang): string
    {
        static $L = [
            'title'      => ['ro' => 'Istoric și inspecție (Encar)', 'ru' => 'История и диагностика (Encar)', 'en' => 'History & inspection (Encar)'],
            'body'       => ['ro' => 'Starea caroseriei',            'ru' => 'Состояние кузова',              'en' => 'Body condition'],
            'tech'       => ['ro' => 'Inspecție tehnică',            'ru' => 'Техническая диагностика',       'en' => 'Technical inspection'],
            'history'    => ['ro' => 'Istoric',                      'ru' => 'История',                       'en' => 'History'],
            'accidents'  => ['ro' => 'Daune la asigurare (total)',    'ru' => 'Страховых случаев (всего)',     'en' => 'Insurance claims (total)'],
            'my_accidents'    => ['ro' => 'Reparații la această mașină','ru' => 'Ремонт этого авто',            'en' => 'Repairs to this car'],
            'other_accidents' => ['ro' => 'Daune produse altor mașini','ru' => 'Ущерб другим авто',            'en' => 'Damage caused to others'],
            'total_loss' => ['ro' => 'Daună totală',                 'ru' => 'Полная гибель',                 'en' => 'Total loss'],
            'flood'      => ['ro' => 'Inundație',                    'ru' => 'Затопление',                    'en' => 'Flood'],
            'theft'      => ['ro' => 'Furt înregistrat',             'ru' => 'Угон',                          'en' => 'Theft record'],
            'business_use'=> ['ro' => 'Uz comercial',                'ru' => 'Коммерческое использование',    'en' => 'Commercial use'],
            'gov_use'    => ['ro' => 'Uz guvernamental',             'ru' => 'Гос. использование',            'en' => 'Government use'],
            'accident_list'=> ['ro' => 'Detalii accidente',          'ru' => 'Детали ДТП',                    'en' => 'Accident details'],
            'painted'    => ['ro' => 'Panouri vopsite',              'ru' => 'Окрашенные панели',             'en' => 'Painted panels'],
            'owners'     => ['ro' => 'Schimbări de proprietar',      'ru' => 'Смены владельца',               'en' => 'Owner changes'],
            'mileage'    => ['ro' => 'Kilometraj',                   'ru' => 'Пробег',                        'en' => 'Mileage'],
            'no_accident'=> ['ro' => 'Fără accidente',               'ru' => 'Без ДТП',                       'en' => 'No accidents'],
            'component'  => ['ro' => 'Componentă',                   'ru' => 'Компонент',                     'en' => 'Component'],
            'state'      => ['ro' => 'Stare',                        'ru' => 'Состояние',                     'en' => 'State'],
            'panel'      => ['ro' => 'Panou',                        'ru' => 'Панель',                        'en' => 'Panel'],
            'date'       => ['ro' => 'Data',                         'ru' => 'Дата',                          'en' => 'Date'],
            'cost'       => ['ro' => 'Cost reparație',               'ru' => 'Стоимость ремонта',             'en' => 'Repair cost'],
            'equipment'  => ['ro' => 'Dotări',                       'ru' => 'Комплектация',                  'en' => 'Equipment'],
            'structure'  => ['ro' => 'Structura caroseriei',         'ru' => 'Силовая структура',             'en' => 'Frame structure'],
            'inspmaster' => ['ro' => 'Date inspecție',               'ru' => 'Данные диагностики',            'en' => 'Inspection data'],
            'photos'     => ['ro' => 'Foto inspecție',               'ru' => 'Фото осмотра',                  'en' => 'Inspection photos'],
            // body diagram (public view): heading + colour legend
            'diagram'      => ['ro' => 'Starea caroseriei',          'ru' => 'Состояние кузова',              'en' => 'Body condition'],
            'leg_ok'       => ['ro' => 'Original',                   'ru' => 'Оригинал',                      'en' => 'Original'],
            'leg_exchange' => ['ro' => 'Înlocuit',                   'ru' => 'Замена',                        'en' => 'Replaced'],
            'leg_painted'  => ['ro' => 'Vopsit',                     'ru' => 'Окрашено',                      'en' => 'Painted'],
            'leg_repaired' => ['ro' => 'Reparat',                    'ru' => 'Ремонт',                        'en' => 'Repaired'],
            'tech_all_ok'  => ['ro' => 'Toate sistemele — OK',       'ru' => 'Все системы — норма',           'en' => 'All systems — OK'],
            'flag_no'      => ['ro' => 'Nu',                         'ru' => 'Нет',                           'en' => 'No'],
            'flag_yes'     => ['ro' => 'Da',                         'ru' => 'Да',                            'en' => 'Yes'],
        ];
        return $L[$key][$lang] ?? $L[$key]['ro'] ?? $key;
    }

    // Official Encar option catalog
    // (api.encar.com/v1/readside/vehicles/car/options/standard), grouped exactly
    // like Encar shows it: an ordered list of PARENT options per category, each
    // with its Korean name and (optionally) the sub-options Encar nests under it
    // (e.g. Headlamp -> HID/LED, Cruise -> normal/adaptive). Codes are NOT unique
    // across categories, so we key by (category, parentCode) and match the car's
    // own standard[] codes against parent + sub codes. Korean is translated at
    // render time via parsing_report_tr. Returns:
    //   [ catKey => [ ['p'=>parentCode,'kr'=>korean,'subs'=>[code=>korean,...]], ... ] ]
    function parsing_report_options_dict(): array
    {
        static $d = null;
        if ($d !== null) return $d;
        $d = [
            'ext' => [
                ['p' => '010', 'kr' => '선루프'],
                ['p' => '001', 'kr' => '헤드램프', 'subs' => ['029' => '헤드램프(HID)', '075' => '헤드램프(LED)']],
                ['p' => '059', 'kr' => '파워 전동 트렁크'],
                ['p' => '080', 'kr' => '고스트 도어 클로징'],
                ['p' => '024', 'kr' => '전동접이 사이드 미러'],
                ['p' => '017', 'kr' => '알루미늄 휠'],
                ['p' => '062', 'kr' => '루프랙'],
                ['p' => '082', 'kr' => '열선 스티어링 휠'],
                ['p' => '083', 'kr' => '전동 조절 스티어링 휠'],
                ['p' => '084', 'kr' => '패들 시프트'],
                ['p' => '031', 'kr' => '스티어링 휠 리모컨'],
                ['p' => '030', 'kr' => 'ECM 룸미러'],
                ['p' => '074', 'kr' => '하이패스'],
                ['p' => '006', 'kr' => '파워 도어록'],
                ['p' => '008', 'kr' => '파워 스티어링 휠'],
                ['p' => '007', 'kr' => '파워 윈도우'],
            ],
            'safety' => [
                ['p' => '002', 'kr' => '에어백', 'subs' => ['026' => '에어백(운전석)', '027' => '에어백(동승석)']],
                ['p' => '020', 'kr' => '에어백(사이드)'],
                ['p' => '056', 'kr' => '에어백(커튼)'],
                ['p' => '001', 'kr' => '브레이크 잠김 방지(ABS)'],
                ['p' => '019', 'kr' => '미끄럼 방지(TCS)'],
                ['p' => '055', 'kr' => '차체자세 제어장치(ESC)'],
                ['p' => '033', 'kr' => '타이어 공기압센서(TPMS)'],
                ['p' => '088', 'kr' => '차선이탈 경보 시스템(LDWS)'],
                ['p' => '002', 'kr' => '전자제어 서스펜션(ECS)'],
                ['p' => '003', 'kr' => '주차감지센서', 'subs' => ['085' => '주차감지센서(전방)', '032' => '주차감지센서(후방)']],
                ['p' => '086', 'kr' => '후측방 경보 시스템'],
                ['p' => '058', 'kr' => '후방 카메라'],
                ['p' => '087', 'kr' => '360도 어라운드 뷰'],
            ],
            'comfort' => [
                ['p' => '004', 'kr' => '크루즈 컨트롤', 'subs' => ['068' => '크루즈 컨트롤(일반)', '079' => '크루즈 컨트롤(어댑티브)']],
                ['p' => '095', 'kr' => '헤드업 디스플레이(HUD)'],
                ['p' => '094', 'kr' => '전자식 주차브레이크(EPB)'],
                ['p' => '023', 'kr' => '자동 에어컨'],
                ['p' => '057', 'kr' => '스마트키'],
                ['p' => '015', 'kr' => '무선도어 잠금장치'],
                ['p' => '081', 'kr' => '레인센서'],
                ['p' => '097', 'kr' => '오토 라이트'],
                ['p' => '005', 'kr' => '커튼/블라인드', 'subs' => ['092' => '커튼/블라인드(뒷좌석)', '093' => '커튼/블라인드(후방)']],
                ['p' => '005', 'kr' => '내비게이션'],
                ['p' => '004', 'kr' => '앞좌석 AV 모니터'],
                ['p' => '054', 'kr' => '뒷좌석 AV 모니터'],
                ['p' => '096', 'kr' => '블루투스'],
                ['p' => '003', 'kr' => 'CD 플레이어'],
                ['p' => '072', 'kr' => 'USB 단자'],
                ['p' => '071', 'kr' => 'AUX 단자'],
            ],
            'seats' => [
                ['p' => '014', 'kr' => '가죽시트'],
                ['p' => '006', 'kr' => '전동시트', 'subs' => ['021' => '전동시트(운전석)', '035' => '전동시트(동승석)']],
                ['p' => '089', 'kr' => '전동시트(뒷좌석)'],
                ['p' => '007', 'kr' => '열선시트', 'subs' => ['022' => '열선시트(앞좌석)', '063' => '열선시트(뒷좌석)']],
                ['p' => '008', 'kr' => '메모리 시트', 'subs' => ['051' => '메모리 시트(운전석)', '078' => '메모리 시트(동승석)']],
                ['p' => '009', 'kr' => '통풍시트', 'subs' => ['034' => '통풍시트(운전석)', '077' => '통풍시트(동승석)']],
                ['p' => '090', 'kr' => '통풍시트(뒷좌석)'],
                ['p' => '091', 'kr' => '마사지 시트'],
            ],
        ];
        return $d;
    }

    function parsing_report_options_cat(string $key, string $lang): string
    {
        static $C = [
            'ext'     => ['ro' => 'Exterior / Interior', 'ru' => 'Экстерьер / Интерьер',     'en' => 'Exterior / Interior'],
            'safety'  => ['ro' => 'Siguranță',           'ru' => 'Безопасность',             'en' => 'Safety'],
            'comfort' => ['ro' => 'Confort / Multimedia','ru' => 'Удобство / Мультимедиа',   'en' => 'Comfort / Multimedia'],
            'seats'   => ['ro' => 'Scaune',              'ru' => 'Сиденья',                  'en' => 'Seats'],
        ];
        return $C[$key][$lang] ?? $C[$key]['ro'] ?? $key;
    }

    // The diagnosis comments are long free-text Korean. Instead of showing raw
    // Korean we translate the few standard phrases Encar uses and drop the rest.
    // Returns a short translated verdict, or '' if nothing recognised.
    function parsing_report_comment(string $kr, string $lang): string
    {
        $out = [];
        // "무사고" verdict refers to the VISUAL panel diagnosis (no structural
        // accident damage on panels) — NOT the insurance history, which can still
        // show claims. Word it accordingly so it doesn't contradict the History
        // section (accidentCnt > 0).
        if (mb_strpos($kr, '무사고') !== false) {
            $out[] = ['ro' => 'Diagnoză Encar: panouri verificate, fără avarii structurale.',
                      'ru' => 'Диагностика Encar: панели проверены, без структурных повреждений.',
                      'en' => 'Encar diagnosis: panels checked, no structural damage.'][$lang] ?? '';
        }
        // "외부패널의 교환이 없는" = no outer panel replacement.
        if (mb_strpos($kr, '외부패널의 교환이 없') !== false) {
            $out[] = ['ro' => 'Fără înlocuiri de panouri exterioare.',
                      'ru' => 'Без замены внешних панелей.',
                      'en' => 'No outer panel replacements.'][$lang] ?? '';
        }
        // Extract own-damage insurance amount if present (e.g. "927,257원").
        // The amount is raw KRW with thousands separators — convert to ~€ like
        // everywhere else (KRW hidden, never shown to the buyer).
        if (preg_match('/내차\s*피해\s*(\d+)회.*?([\d,]+)\s*원/u', $kr, $m)) {
            $cnt = $m[1];
            $eur = parsing_report_value('partCost', (int)str_replace(',', '', $m[2]), $lang);
            $out[] = ['ro' => "Daună proprie: {$cnt}× ({$eur}).",
                      'ru' => "Ущерб своего авто: {$cnt}× ({$eur}).",
                      'en' => "Own damage: {$cnt}× ({$eur})."][$lang] ?? '';
        }
        // Minor repair notes: panel beating/painting (판금/도색), door adjustment
        // (도어 조정). These are important — show them translated.
        if (mb_strpos($kr, '판금') !== false || mb_strpos($kr, '도색') !== false) {
            $side = (mb_strpos($kr, '(우)') !== false ? 'dr' : (mb_strpos($kr, '(좌)') !== false ? 'st' : ''));
            $sideTxt = ['ro' => ['dr' => ' (dreapta)', 'st' => ' (stânga)', '' => ''],
                        'ru' => ['dr' => ' (прав)', 'st' => ' (лев)', '' => ''],
                        'en' => ['dr' => ' (right)', 'st' => ' (left)', '' => '']][$lang][$side] ?? '';
            $out[] = ['ro' => 'Tinichigerie/vopsire parțială aripă'.$sideTxt.'.',
                      'ru' => 'Частичная рихтовка/покраска крыла'.$sideTxt.'.',
                      'en' => 'Partial fender bodywork/paint'.$sideTxt.'.'][$lang] ?? '';
        }
        if (mb_strpos($kr, '도어') !== false && mb_strpos($kr, '조정') !== false) {
            $out[] = ['ro' => 'Ajustare ușă.',
                      'ru' => 'Регулировка двери.',
                      'en' => 'Door adjustment.'][$lang] ?? '';
        }
        return implode(' ', array_filter($out));
    }

    // Recursively render ANY Encar payload node into nested rows, translating
    // Format a scalar value for display: dates 20230621 -> 21.06.2023, mileage
    // with thousands separator + " km", and Korean won -> € (approx). $key tells
    // us how to interpret the value.
    function parsing_report_value(string $key, $v, string $lang)
    {
        $s = (string)$v;
        // Compact dates yyyymmdd -> dd.mm.yyyy
        if (in_array($key, ['firstRegistrationDate', 'firstDate', 'modelYear', 'date'], true)
            && preg_match('/^(\d{4})(\d{2})(\d{2})$/', $s, $m)) {
            return $m[3].'.'.$m[2].'.'.$m[1];
        }
        // ISO date 2025-11-09 -> 09.11.2025
        if (preg_match('/^(\d{4})-(\d{2})-(\d{2})/', $s, $m)) {
            return $m[3].'.'.$m[2].'.'.$m[1];
        }
        // Mileage -> "42.596 km"
        if ($key === 'mileage' && is_numeric($s)) {
            return number_format((int)$s, 0, '.', '.').' km';
        }
        // Won amounts -> "~y €" (KRW hidden). KRW->EUR approx 0.00057 (same fixed
        // rate as ParsingPipeline; Encar amounts are display-only here).
        if (in_array($key, ['insuranceBenefit', 'myAccidentCost', 'otherAccidentCost', 'partCost', 'laborCost', 'paintingCost'], true)
            && is_numeric($s) && (int)$s > 0) {
            $eur = (int)round((int)$s * 0.00057);
            return '~'.number_format($eur, 0, '.', ' ').' €';
        }
        return parsing_report_tr($s, $lang);
    }

    // Recursively render ANY Encar payload node into nested rows, translating
    // every Korean string we recognise. This guarantees we never drop a section
    // (e.g. frame/structure panels) just because we did not special-case it.
    // Nodes shaped like {type:{title}, statusType:{title}, children:[...]} are
    // rendered as "label : status (+ nested)". Plain scalars become "key: value".
    function parsing_report_walk($node, string $lang, int $depth = 0): string
    {
        if ($node === null || $node === '' || $node === []) return '';
        $dcl = $depth > 0 ? ' er-d'.min($depth, 3) : '';
        $tr  = fn($s) => htmlspecialchars(parsing_report_tr((string)$s, $lang));

        // Encar inspection node: {type:{title}, statusType:{title}, children}
        // OR outers node: {type:{title}, statusTypes:[{title}]} (frame damage).
        if (is_array($node) && isset($node['type']['title'])) {
            $title  = $node['type']['title'];
            $status = $node['statusType']['title'] ?? ($node['result'] ?? null);
            // outers use statusTypes (a list) instead of statusType (single).
            // These are translated per-element here, so do NOT translate the joined
            // string again below (that would leave the combined string untranslated).
            $preTranslated = false;
            if ($status === null && !empty($node['statusTypes']) && is_array($node['statusTypes'])) {
                $parts = [];
                foreach ($node['statusTypes'] as $st) {
                    if (!isset($st['title'])) continue;
                    $t = trim((string)$st['title']);
                    if ($t === '') continue;
                    // Try the whole title first; if untranslated and it contains a
                    // comma/slash, translate each piece (Encar sometimes joins them).
                    $tr1 = parsing_report_tr($t, $lang);
                    if ($tr1 === $t && preg_match('/[,\/]/u', $t)) {
                        $sub = preg_split('/\s*[,\/]\s*/u', $t);
                        $subTr = array_map(fn($x) => parsing_report_tr(trim($x), $lang), $sub);
                        $tr1 = implode('/', array_unique(array_filter($subTr)));
                    }
                    if ($tr1 !== '') $parts[] = $tr1;
                }
                // de-dupe identical translations (e.g. "Sudură, Sudură")
                $parts = array_values(array_unique($parts));
                $status = $parts ? implode(', ', $parts) : null;
                $preTranslated = true;
            }
            $good   = in_array($status, ['양호', '없음', '적정', '정상', 'NORMAL'], true);
            $stTxt  = $status === null ? '' : ($preTranslated ? $status : parsing_report_tr((string)$status, $lang));
            $kids = '';
            foreach (($node['children'] ?? []) as $c) $kids .= parsing_report_walk($c, $lang, $depth + 1);
            // Skip rows with no status AND no rendered children (e.g. M/T section
            // on an automatic car) — they would show an empty, confusing line.
            if ($stTxt === '' && $kids === '') return '';
            // A row that has children is a sub-heading (group); render it as such.
            if ($kids !== '' && $stTxt === '') {
                return '<tr class="er-grouprow"><td colspan="2" class="er-sub'.$dcl.'">'.$tr($title).'</td></tr>'.$kids;
            }
            $badge = $stTxt === '' ? '' : '<span class="er-badge '.($good ? 'er-ok' : 'er-bad').'">'.htmlspecialchars($stTxt).'</span>';
            $row = '<tr><td class="er-item'.$dcl.'">'.$tr($title).'</td>'
                 . '<td class="er-stcell">'.$badge.'</td></tr>';
            return $row.$kids;
        }

        // List of such nodes (PHP 8.0-safe list check).
        $isList = is_array($node) && ($node === [] || array_keys($node) === range(0, count($node) - 1));
        if ($isList) {
            $out = '';
            foreach ($node as $c) $out .= parsing_report_walk($c, $lang, $depth);
            return $out;
        }

        // System/internal keys we never want to display.
        static $skip = ['code', 'recordNo', 'version', 'validityStartDate', 'validityEndDate',
            'issueDate', 'registrationDate', 'inspName', 'noticeName', 'performTester',
            'supplyNum', 'coout', 'hcout', 'smout', 'colorType', 'mileageStateType',
            'engineCheck', 'trnsCheck', 'paintPartTypes', 'mainOptionTypes', 'seriousTypes',
            'tuningStateTypes', 'recallFullFillTypes', 'comments', 'detail', 'price',
            'statusItemTypes', 'inspectionSource', 'directManagement', 'formats', 'images',
            'vehicleId', 'resultCode'];

        // Associative map of scalars / sub-objects.
        if (is_array($node)) {
            $out = '';
            foreach ($node as $k => $v) {
                if (in_array($k, $skip, true)) continue;
                if ($v === null || $v === '' || $v === [] || (is_string($k) && $k !== '' && $k[0] === '_')) continue;
                if (is_array($v)) {
                    // Common Encar shape {code, title} -> show title inline as value.
                    if (isset($v['title']) && count(array_diff(array_keys($v), ['code', 'title'])) === 0) {
                        $out .= '<tr><td class="er-item'.$dcl.'">'.$tr($k).'</td><td class="er-stcell">'.$tr($v['title']).'</td></tr>';
                        continue;
                    }
                    // List of {code,title} -> comma-joined inline.
                    $titles = [];
                    if ($isListNode = ($v === [] || array_keys($v) === range(0, count($v) - 1))) {
                        foreach ($v as $el) { if (is_array($el) && isset($el['title'])) $titles[] = parsing_report_tr((string)$el['title'], $lang); }
                    }
                    if (!empty($titles)) {
                        $out .= '<tr><td class="er-item'.$dcl.'">'.$tr($k).'</td><td class="er-stcell">'.htmlspecialchars(implode(', ', $titles)).'</td></tr>';
                        continue;
                    }
                    $inner = parsing_report_walk($v, $lang, $depth + 1);
                    if ($inner !== '') $out .= '<tr class="er-grouprow"><td colspan="2" class="er-sub'.$dcl.'">'.$tr($k).'</td></tr>'.$inner;
                } else {
                    $val = is_bool($v)
                        ? parsing_report_label($v ? 'flag_yes' : 'flag_no', $lang)
                        : parsing_report_value((string)$k, $v, $lang);
                    $out .= '<tr><td class="er-item'.$dcl.'">'.$tr($k).'</td><td class="er-stcell">'.htmlspecialchars((string)$val).'</td></tr>';
                }
            }
            return $out;
        }

        return '<tr><td colspan="2" class="er-item'.$dcl.'">'.$tr((string)$node).'</td></tr>';
    }

    // Public-only compact technical inspection: render the inners tree as a grid
    // of small "system cards". Each top-level system (Motor, Transmisie, …) lists
    // its checks flattened to "name : status" lines, OK ones muted, faults red.
    if (!function_exists('parsing_report_tech_compact')) {
    // Flatten one node's leaf checks into [['name'=>, 'bad'=>bool, 'status'=>str], ...]
    // A check is flagged BAD only when its status is an explicit fault (leak,
    // faulty, excess, etc.) — see the blacklist below. Known-good and any
    // unknown/neutral status are NOT flagged red, so a new Encar code never
    // produces a false "fault". The whole-system pill counts these bad leaves.
    function parsing_report_tech_leaves($node, string $lang): array
    {
        // Korean fault statuses (and their normalised tokens). Anything matching
        // here is a real problem; everything else is treated as OK/neutral.
        static $badKr = ['불량', '누유', '누수', '미세누유', '미세누수', '과다', '부족',
                         '교환', '부식', '균열', '손상', '필요'];
        $out = [];
        if (!is_array($node)) return $out;
        $title  = $node['type']['title'] ?? null;
        $status = $node['statusType']['title'] ?? ($node['result'] ?? null);
        $kids   = $node['children'] ?? [];
        if ($status !== null && $title !== null) {
            $st = trim((string)$status);
            // Bad if it matches a fault token (whole or as part of a combined
            // status like "누유,누수"). resultCode-style EN tokens too.
            $bad = false;
            foreach ($badKr as $b) { if (mb_strpos($st, $b) !== false) { $bad = true; break; } }
            if (!$bad && in_array(strtoupper($st), ['BAD', 'FAULTY', 'LEAK', 'EXCHANGE', 'REPLACEMENT'], true)) $bad = true;
            $out[] = ['name' => parsing_report_tr((string)$title, $lang),
                      'bad' => $bad,
                      'status' => parsing_report_tr($st, $lang)];
        }
        foreach ($kids as $c) $out = array_merge($out, parsing_report_tech_leaves($c, $lang));
        return $out;
    }

    function parsing_report_tech_compact($inners, string $lang): string
    {
        if (!is_array($inners)) return '';
        $cards = '';
        foreach ($inners as $system) {
            if (!is_array($system) || !isset($system['type']['title'])) continue;
            $sysName = parsing_report_tr((string)$system['type']['title'], $lang);
            $leaves  = parsing_report_tech_leaves($system, $lang);
            // The system node itself may carry its title as the first leaf — drop a
            // leaf whose name duplicates the system header.
            $rows = '';
            $faults = 0;
            foreach ($leaves as $lf) {
                if ($lf['name'] === $sysName) continue;
                $bad = $lf['bad'];
                if ($bad) $faults++;
                $rows .= '<li class="'.($bad ? 'er-tc-bad' : 'er-tc-ok').'">'
                       . '<span class="er-tc-dot"></span>'
                       . '<span class="er-tc-n">'.htmlspecialchars($lf['name']).'</span>'
                       . '<span class="er-tc-s">'.htmlspecialchars($lf['status']).'</span></li>';
            }
            if ($rows === '') continue;
            // Per-system verdict pill in the header: ✓ when all checks pass.
            $pill = $faults === 0
                ? '<span class="er-tc-pill er-tc-pill-ok">&#10003;</span>'
                : '<span class="er-tc-pill er-tc-pill-bad">'.$faults.'</span>';
            $cards .= '<div class="er-tc-card'.($faults ? ' er-tc-card-bad' : '').'">'
                    . '<div class="er-tc-h">'.$pill.'<span>'.htmlspecialchars($sysName).'</span></div>'
                    . '<ul class="er-tc-l">'.$rows.'</ul></div>';
        }
        return $cards;
    }
    }

    // Public-only collapsible section: a <details> with the heading as the
    // clickable summary (red bar + arrow) and the given body hidden until opened.
    // $extra goes next to the title (e.g. the "all OK" verdict pill).
    if (!function_exists('parsing_report_collapsible')) {
    function parsing_report_collapsible(string $title, string $body, string $extra = ''): string
    {
        if ($body === '') return '';
        return '<div class="er-section er-section-collapse"><details class="er-collapse"><summary>'
             . '<h4>'.htmlspecialchars($title).'</h4>'.$extra
             . '<span class="er-collapse-arrow"></span></summary>'
             . '<div class="er-collapse-body">'.$body.'</div></details></div>';
    }
    }

    // The 6 official Encar panel-damage types (the X/W/C/A/U/T legend on the
    // inspection diagram). Each carries the badge letter, a colour, and a precise
    // label per language — so we show "Sudură/tinichigerie" instead of a vague
    // "Reparat". 'ok' is the original/undamaged default. Higher rank = worse, a
    // panel keeps its worst finding.
    if (!function_exists('parsing_report_damage_types')) {
    function parsing_report_damage_types(): array
    {
        return [
            // code => [letter, colour, rank, ro, ru, en]
            'ok' => ['',  '#dfe3ea', 0, 'Original',                'Оригинал',                              'Original'],
            'P'  => ['P', '#c79bd6', 1, 'Vopsit',                  'Окрашено',                              'Painted'],
            'A'  => ['A', '#9aa7bd', 2, 'Zgârieturi',              'Царапины',                              'Scratches'],
            'U'  => ['U', '#7b9e6e', 3, 'Suprafață neuniformă',    'Неровная поверхность',                  'Uneven surface'],
            'C'  => ['C', '#f0a92b', 4, 'Coroziune',               'Коррозия',                              'Corrosion'],
            'T'  => ['T', '#9c6b4a', 5, 'Deformare',               'Нарушение',                             'Deformation'],
            'W'  => ['W', '#3a78c2', 6, 'Sudură/tinichigerie',     'Сварка/металлообработка',               'Welding/sheet-metal'],
            'X'  => ['X', '#e2001a', 7, 'Înlocuit',                'Замена',                                'Replaced'],
        ];
    }
    }

    if (!function_exists('parsing_report_body_diagram')) {
    function parsing_report_body_diagram(array $diagItems, array $paints, string $lang, int $accidentCnt = 0, array $outers = []): string
    {
        // Panel slots we can place on the silhouette, keyed by the canonical code
        // used in diagnosis.items[].name.
        $slots = ['HOOD','FRONT_FENDER_LEFT','FRONT_FENDER_RIGHT','FRONT_DOOR_LEFT',
                  'FRONT_DOOR_RIGHT','BACK_DOOR_LEFT','BACK_DOOR_RIGHT','QUARTER_PANEL_LEFT',
                  'QUARTER_PANEL_RIGHT','ROOF_PANEL','TRUNK_LID'];
        $state = array_fill_keys($slots, 'ok'); // default: original/normal

        // Damage types (X/W/C/A/U/T + P painted) with letter/colour/rank/labels.
        $TYPES = parsing_report_damage_types();
        // A panel keeps its WORST finding (highest rank).
        $bump = function (&$cur, string $next) use ($TYPES) {
            $rc = $TYPES[$cur][2]  ?? 0;
            $rn = $TYPES[$next][2] ?? 0;
            if ($rn > $rc) $cur = $next;
        };
        // Map an Encar status token (code letter OR Korean text) to one of our codes.
        $statusToType = function (string $s): ?string {
            $u = strtoupper(trim($s));
            // Single-letter Encar codes come through directly.
            if (in_array($u, ['X','W','C','A','U','T','P'], true)) return $u;
            // Korean / English fallbacks.
            if (strpos($s, '교환') !== false || strpos($u, 'EXCHANGE') !== false) return 'X';
            if (strpos($s, '판금') !== false || strpos($s, '용접') !== false
                || strpos($u, 'WELD') !== false || strpos($u, 'SHEET') !== false) return 'W';
            if (strpos($s, '부식') !== false || strpos($u, 'CORROSION') !== false) return 'C';
            if (strpos($s, '흠집') !== false || strpos($u, 'SCRATCH') !== false) return 'A';
            if (strpos($s, '요철') !== false || strpos($u, 'UNEVEN') !== false) return 'U';
            if (strpos($s, '손상') !== false || strpos($u, 'DAMAGE') !== false || strpos($u, 'DEFORM') !== false) return 'T';
            if (strpos($s, '도색') !== false || strpos($u, 'PAINT') !== false) return 'P';
            return null;
        };

        // 1. Diagnosis panels (diagnosis.items) — resultCode/result → type.
        foreach ($diagItems as $it) {
            $code = $it['name'] ?? '';
            if (!isset($state[$code])) continue;
            $t = $statusToType((string)($it['resultCode'] ?? '')) ?: $statusToType((string)($it['result'] ?? ''));
            if ($t) $bump($state[$code], $t);
        }

        // 2. Painted panels (Korean titles) → painted, mapped onto the same slots.
        $paintMap = [
            '후드' => 'HOOD',
            '프론트 휀더(좌)' => 'FRONT_FENDER_LEFT',  '프론트 휀더(우)' => 'FRONT_FENDER_RIGHT',
            '프론트 펜더(좌)' => 'FRONT_FENDER_LEFT',  '프론트 펜더(우)' => 'FRONT_FENDER_RIGHT', // alt spelling 휀/펜
            '프론트 도어(좌)' => 'FRONT_DOOR_LEFT',    '프론트 도어(우)' => 'FRONT_DOOR_RIGHT',
            '도어(좌)' => 'FRONT_DOOR_LEFT',           '도어(우)' => 'FRONT_DOOR_RIGHT',
            '리어 도어(좌)' => 'BACK_DOOR_LEFT',       '리어 도어(우)' => 'BACK_DOOR_RIGHT',
            '쿼터 패널(좌)' => 'QUARTER_PANEL_LEFT',   '쿼터 패널(우)' => 'QUARTER_PANEL_RIGHT',
            '루프 패널' => 'ROOF_PANEL', '루프' => 'ROOF_PANEL',
            '트렁크 리드' => 'TRUNK_LID', '트렁크' => 'TRUNK_LID', '리어 패널' => 'TRUNK_LID',
        ];
        foreach ($paints as $pp) {
            $code = $paintMap[trim((string)($pp['title'] ?? ''))] ?? null;
            if ($code && isset($state[$code])) $bump($state[$code], 'P');
        }

        // 3. Frame / outer-panel damage (inspection.outers) — the X/W/C/A/U/T badges
        // Encar draws on the body. Each node is {type:{title: panel name},
        // statusTypes:[{code:"W", title:"판금/용접"}]}. Encar puts the badge LETTER in
        // statusTypes[].code, so prefer it; fall back to the Korean title.
        $walkOuters = function ($nodes) use (&$walkOuters, $paintMap, $statusToType, $bump, &$state) {
            foreach ((array)$nodes as $node) {
                if (!is_array($node)) continue;
                $title = trim((string)($node['type']['title'] ?? ''));
                $code  = $paintMap[$title] ?? null;
                if ($code && isset($state[$code])) {
                    // Collect every status on this node (statusTypes list or single).
                    $statuses = [];
                    foreach (($node['statusTypes'] ?? []) as $st) {
                        if (!empty($st['code']))  $statuses[] = (string)$st['code'];
                        elseif (!empty($st['title'])) $statuses[] = (string)$st['title'];
                    }
                    if (!empty($node['statusType']['code']))  $statuses[] = (string)$node['statusType']['code'];
                    elseif (!empty($node['statusType']['title'])) $statuses[] = (string)$node['statusType']['title'];
                    foreach ($statuses as $s) {
                        $t = $statusToType($s);
                        if ($t) $bump($state[$code], $t);
                    }
                }
                if (!empty($node['children'])) $walkOuters($node['children']);
            }
        };
        $walkOuters($outers);

        // Even with no panel data from Encar, still draw the car — every panel
        // defaults to "ok" (original), so the buyer always sees the silhouette
        // instead of an empty gap.

        $nm = fn($code) => htmlspecialchars(parsing_report_tr($code, $lang));

        $panels = [
            // HOOD: trapezoid from the nose curve down to the windscreen base.
            'HOOD'               => ['d' => 'M70 92 Q130 80 190 92 L182 168 Q130 158 78 168 Z',                 'bx' => 130, 'by' => 122],
            // FRONT FENDERS: wheel-arch wings beside the hood. Outer edge HUGS the
            // silhouette curve (x: 50→46 top-to-bottom) so it reaches the body edge
            // without bleeding past it.
            'FRONT_FENDER_LEFT'  => ['d' => 'M50 112 Q47 148 46 182 L78 178 L78 116 Q63 110 50 112 Z',          'bx' => 61,  'by' => 150],
            'FRONT_FENDER_RIGHT' => ['d' => 'M210 112 Q213 148 214 182 L182 178 L182 116 Q197 110 210 112 Z',   'bx' => 199, 'by' => 150],
            // FRONT DOORS: outer edge on the body line (x≈46 / 214).
            'FRONT_DOOR_LEFT'    => ['d' => 'M46 214 L80 214 L80 300 L46 300 Z',                                 'bx' => 63,  'by' => 257],
            'FRONT_DOOR_RIGHT'   => ['d' => 'M214 214 L180 214 L180 300 L214 300 Z',                             'bx' => 197, 'by' => 257],
            // REAR DOORS.
            'BACK_DOOR_LEFT'     => ['d' => 'M46 304 L80 304 L80 384 L46 384 Z',                                 'bx' => 63,  'by' => 344],
            'BACK_DOOR_RIGHT'    => ['d' => 'M214 304 L180 304 L180 384 L214 384 Z',                             'bx' => 197, 'by' => 344],
            // QUARTER PANELS: rear wings curving into the tail.
            'QUARTER_PANEL_LEFT' => ['d' => 'M46 388 L80 388 L78 462 Q60 456 50 432 Q46 410 46 388 Z',          'bx' => 62,  'by' => 420],
            'QUARTER_PANEL_RIGHT'=> ['d' => 'M214 388 L180 388 L182 462 Q200 456 210 432 Q214 410 214 388 Z',   'bx' => 198, 'by' => 420],
            // ROOF: the cabin centre between the doors.
            'ROOF_PANEL'         => ['d' => 'M88 214 L172 214 Q178 300 172 384 L88 384 Q82 300 88 214 Z',        'bx' => 130, 'by' => 300],
            // TRUNK: tail section behind the rear glass.
            'TRUNK_LID'          => ['d' => 'M78 470 Q130 480 182 470 L176 506 Q130 516 84 506 Z',               'bx' => 130, 'by' => 488],
        ];

        // Label helper for a type code (e.g. 'W' → "Sudură/tinichigerie").
        $lblOf = function (string $code) use ($TYPES, $lang) {
            $i = ['ro' => 3, 'ru' => 4, 'en' => 5][$lang] ?? 3;
            return $TYPES[$code][$i] ?? ($TYPES['ok'][$i] ?? '');
        };

        // Body silhouette path — reused as a clip so panel fills never bleed out.
        $bodyPath = 'M130 28 C92 28 64 44 56 84 C50 112 47 150 46 196 '
                  . 'C45 260 45 330 47 398 C48 446 54 486 72 510 '
                  . 'C90 532 130 536 130 536 C130 536 170 532 188 510 '
                  . 'C206 486 212 446 213 398 C215 330 215 260 214 196 '
                  . 'C213 150 210 112 204 84 C196 44 168 28 130 28 Z';

        // Build panel fills (clipped to the body) + letter badges (X/W/C/A/U/T/P).
        $panelSvg = ''; $badgeSvg = '';
        foreach ($slots as $code) {
            $p = $panels[$code];
            $st = $state[$code];                 // type code or 'ok'
            $colour = $TYPES[$st][1] ?? '#dfe3ea';
            $panelSvg .= '<path d="'.$p['d'].'" class="erd-p" style="fill:'.$colour.'">'
                       . '<title>'.$nm($code).' — '.htmlspecialchars($lblOf($st)).'</title></path>';
            if ($st !== 'ok') {
                $letter = $TYPES[$st][0] ?? '';
                $badgeSvg .= '<g class="erd-badge">'
                           . '<circle cx="'.$p['bx'].'" cy="'.$p['by'].'" r="13" style="fill:'.$colour.'"/>'
                           . '<text x="'.$p['bx'].'" y="'.($p['by'] + 5).'">'.htmlspecialchars($letter).'</text></g>';
            }
        }

        static $erdClipSeq = 0;
        $clipId = 'erd-body-clip-'.(++$erdClipSeq).'-'.substr(md5(uniqid('', true)), 0, 6);
        $svg = '<svg class="erd-svg" viewBox="0 0 260 600" preserveAspectRatio="xMidYMid meet" role="img">'
            . '<defs><clipPath id="'.$clipId.'"><path d="'.$bodyPath.'"/></clipPath></defs>'
            // wheels (drawn first, behind the body)
            . '<g class="erd-wheel">'
            . '<rect x="34" y="150" width="20" height="58" rx="9"/><rect x="206" y="150" width="20" height="58" rx="9"/>'
            . '<rect x="34" y="404" width="20" height="58" rx="9"/><rect x="206" y="404" width="20" height="58" rx="9"/>'
            . '</g>'
            // body base fill
            . '<path d="'.$bodyPath.'" class="erd-body"/>'
            // panel colours, clipped to the silhouette so edges stay inside
            . '<g clip-path="url(#'.$clipId.')" class="erd-panels">'.$panelSvg.'</g>'
            // seam lines defining every panel (clipped to the body)
            . '<g clip-path="url(#'.$clipId.')" class="erd-seams">'
            //  shoulder lines (sides of the cabin) — separate doors/roof from wings
            . '<line x1="80" y1="100" x2="80" y2="470"/><line x1="180" y1="100" x2="180" y2="470"/>'
            //  door/panel cut lines across the sides
            . '<line x1="46" y1="196" x2="80" y2="196"/><line x1="180" y1="196" x2="214" y2="196"/>'
            . '<line x1="46" y1="302" x2="80" y2="302"/><line x1="180" y1="302" x2="214" y2="302"/>'
            . '<line x1="46" y1="386" x2="80" y2="386"/><line x1="180" y1="386" x2="214" y2="386"/>'
            //  hood base + trunk seam
            . '<path d="M78 168 Q130 158 182 168"/>'
            . '<path d="M84 470 Q130 480 176 470"/>'
            //  roof side rails
            . '<path d="M88 214 Q82 300 88 384"/><path d="M172 214 Q178 300 172 384"/>'
            . '</g>'
            // glass: windscreen, roof, rear window (filled grey, on top)
            . '<g class="erd-glass">'
            . '<path d="M86 172 Q130 162 174 172 L166 210 L94 210 Z"/>'         // windscreen
            . '<rect x="96" y="216" width="68" height="166" rx="10"/>'          // roof glass
            . '<path d="M94 388 Q130 396 166 388 L176 430 Q130 440 84 430 Z"/>' // rear window
            . '</g>'
            // headlights (front) — on the nose, ABOVE the hood (hood starts ~y92)
            . '<g class="erd-lamp">'
            . '<path d="M66 70 Q82 62 98 70 L94 82 Q80 76 70 82 Z"/>'
            . '<path d="M194 70 Q178 62 162 70 L166 82 Q180 76 190 82 Z"/>'
            . '</g>'
            . '<g class="erd-lamp erd-lamp-rear">'
            . '<path d="M70 500 Q92 506 110 502 L108 514 Q90 518 74 512 Z"/>'
            . '<path d="M190 500 Q168 506 150 502 L152 514 Q170 518 186 512 Z"/>'
            . '</g>'
            // side mirrors
            . '<g class="erd-mirror"><path d="M46 204 q-14 1 -14 10 q0 8 14 7 Z"/><path d="M214 204 q14 1 14 10 q0 8 -14 7 Z"/></g>'
            // crisp body outline over everything
            . '<path d="'.$bodyPath.'" class="erd-outline"/>'
            // number badges on affected panels (top layer)
            . $badgeSvg
            . '</svg>';

        // --- Column 2: legend (only the damage types actually present) + list of
        // affected panels with their letter badge. ---
        $present = array_values(array_unique(array_values($state)));
        // Always show Original first, then each present type in rank order.
        $legend = '<span class="erd-leg"><i class="erd-dot" style="background:'.($TYPES['ok'][1]).'"></i>'
                . htmlspecialchars($lblOf('ok')).'</span>';
        foreach ($TYPES as $tc => $td) {
            if ($tc === 'ok' || !in_array($tc, $present, true)) continue;
            $legend .= '<span class="erd-leg"><i class="erd-dot" style="background:'.$td[1].'">'
                     . '<b>'.htmlspecialchars($td[0]).'</b></i>'
                     . htmlspecialchars($lblOf($tc)).'</span>';
        }
        // Affected panels (only non-ok), with the type letter badge + precise label.
        $affected = '';
        foreach ($slots as $code) {
            if ($state[$code] === 'ok') continue;
            $st = $state[$code];
            $colour = $TYPES[$st][1] ?? '#999';
            $affected .= '<li><span class="erd-num" style="background:'.$colour.'">'.htmlspecialchars($TYPES[$st][0] ?? '').'</span>'
                       . '<span class="erd-aff-nm">'.$nm($code).' — '.htmlspecialchars($lblOf($st)).'</span></li>';
        }

        $col2 = '<div class="erd-legend">'.$legend.'</div>'
              . ($affected !== ''
                    ? '<ul class="erd-affected">'.$affected.'</ul>'
                    : ($accidentCnt > 0
                        ? ''
                        : '<p class="erd-clean">'.htmlspecialchars(parsing_report_label('no_accident', $lang)).'</p>'));

        // --- Column 3: FULL panel list — every panel with its status, including
        // the ones that are Original. ---
        $fullRows = '';
        foreach ($slots as $code) {
            $st = $state[$code];
            $stLabel = $lblOf($st);
            $stCls = $st === 'ok' ? 'erd-st-ok' : 'erd-st-bad';
            $stStyle = $st === 'ok' ? '' : ' style="color:'.($TYPES[$st][1] ?? '#999').'"';
            $fullRows .= '<li><span class="erd-full-nm">'.$nm($code).'</span>'
                       . '<span class="erd-full-st '.$stCls.'"'.$stStyle.'>'.htmlspecialchars($stLabel).'</span></li>';
        }
        $col3 = '<ul class="erd-full">'.$fullRows.'</ul>';

        return '<div class="er-section erd-section"><h4>'.htmlspecialchars(parsing_report_label('diagram', $lang)).'</h4>'
            . '<div class="erd-wrap">'
            . '<div class="erd-col erd-col-car">'.$svg.'</div>'
            . '<div class="erd-col erd-col-legend">'.$col2.'</div>'
            . '<div class="erd-col erd-col-full">'.$col3.'</div>'
            . '</div></div>';
    }
    }

    // Build the full report HTML from the decoded report_data array. Returns ''
    // if there is nothing to show.
    // $part = 'all' (default, admin), 'history' (inspection only) or 'equipment'
    // (dotări only) — lets the public page split the report into two accordions.
    // $publicStyle = render the public product-page look (SVG body diagram +
    // visual equipment layout) WITHOUT hiding the VIN/photos. Lets the admin show
    // the exact same report as the product page while keeping full data visible.
    function parsing_report_html(?array $report, string $lang = 'ro', bool $hideVin = false, string $part = 'all', bool $publicStyle = false): string
    {
        if (empty($report)) return '';
        $lang = in_array($lang, ['ro', 'ru', 'en'], true) ? $lang : 'ro';
        $tr  = fn($kr) => htmlspecialchars(parsing_report_tr((string)$kr, $lang));
        $lbl = fn($k)  => htmlspecialchars(parsing_report_label($k, $lang));

        // The public visual look is triggered either by the public page ($hideVin)
        // or explicitly by the admin ($publicStyle). VIN/photos stay tied to
        // $hideVin alone, so the admin keeps them while looking identical.
        $pubLook = $hideVin || $publicStyle;

        $inspection = $report['inspection'] ?? [];
        $diagnosis  = $report['diagnosis'] ?? [];
        $record     = $report['record'] ?? [];

        // Pull the official VIN to show prominently at the top (with a copy button).
        // Prefer the full 17-char VIN injected from parsing_cars.vin (detail source);
        // the inspection VIN is often masked/partial, so only use it as a fallback.
        // $hideVin = public view: suppresses the VIN banner AND the inspection photos.
        $vin = $hideVin ? '' : (string)($report['full_vin']
            ?? ($inspection['master']['detail']['vin'] ?? ($record['vin'] ?? '')));
        $vin = strtoupper(trim($vin));

        // Each chapter is built into its own variable, then assembled in the
        // desired order at the end (History first — it matters most to a buyer).
        $s_body = $s_structure = $s_tech = $s_master = $s_photos = '';
        $s_history = $s_painted = $s_equipment = '';

        // ---- 1. Body condition (diagnosis panels) ----
        // Public look: show a visual top-down body diagram instead of the long
        // text table.
        if ($pubLook) {
            $paintsForDiag = $inspection['master']['detail']['paintPartTypes'] ?? [];
            $s_body = parsing_report_body_diagram(
                $diagnosis['items'] ?? [],
                is_array($paintsForDiag) ? $paintsForDiag : [],
                $lang,
                (int)($record['accidentCnt'] ?? 0),
                $inspection['outers'] ?? []
            );
        } elseif (!empty($diagnosis['items'])) {
            $rows = '';
            $comment = '';
            foreach ($diagnosis['items'] as $it) {
                $name = $it['name'] ?? '';
                // Comments are long free text — show separately, not as a panel row.
                if (in_array($name, ['CHECKER_COMMENT', 'OUTER_PANEL_COMMENT'], true)) {
                    $tcom = parsing_report_comment((string)($it['result'] ?? ''), $lang);
                    if ($tcom !== '') $comment .= '<p class="er-comment">'.htmlspecialchars($tcom).'</p>';
                    continue;
                }
                $resTxt = $it['resultCode'] ? parsing_report_tr((string)$it['resultCode'], $lang) : parsing_report_tr((string)($it['result'] ?? ''), $lang);
                $ok = ($it['resultCode'] ?? '') === 'NORMAL' || ($it['result'] ?? '') === '정상';
                $rows .= '<tr><td class="er-item">'.$tr($name).'</td>'
                       . '<td class="er-stcell"><span class="er-badge '.($ok ? 'er-ok' : 'er-bad').'">'.htmlspecialchars($resTxt).'</span></td></tr>';
            }
            if ($rows !== '') {
                $s_body = '<div class="er-section"><h4>'.$lbl('body').'</h4>'
                    . '<table class="er-table"><thead><tr><th>'.$lbl('panel').'</th><th>'.$lbl('state').'</th></tr></thead>'
                    . '<tbody>'.$rows.'</tbody></table>'.$comment.'</div>';
            }
        }

        // ---- 2a. Frame / structure panels (inspection.outers) ----
        if (!empty($inspection['outers'])) {
            $rows = parsing_report_walk($inspection['outers'], $lang, 0);
            if ($rows !== '') {
                $s_structure = '<div class="er-section"><h4>'.$lbl('structure').'</h4>'
                    . '<table class="er-table"><thead><tr><th>'.$lbl('component').'</th><th>'.$lbl('state').'</th></tr></thead>'
                    . '<tbody>'.$rows.'</tbody></table></div>';
            }
        }

        // ---- 2b. Technical inspection (inners) — recursive, translated ----
        if (!empty($inspection['inners'])) {
            if ($pubLook) {
                // Public: always-open, compact 2-column grid. Each top-level system
                // (Motor, Transmisie, …) becomes a small card listing its checks as
                // "name : status". A one-line verdict shows when nothing is faulty.
                $grid = parsing_report_tech_compact($inspection['inners'], $lang);
                if ($grid !== '') {
                    // Collapsed by default — opens on click.
                    $s_tech = parsing_report_collapsible($lbl('tech'), '<div class="er-tc">'.$grid.'</div>');
                }
            } else {
                $rows = parsing_report_walk($inspection['inners'], $lang, 0);
                if ($rows !== '') {
                    $s_tech = '<div class="er-section"><h4>'.$lbl('tech').'</h4>'
                        . '<table class="er-table"><thead><tr><th>'.$lbl('component').'</th><th>'.$lbl('state').'</th></tr></thead>'
                        . '<tbody>'.$rows.'</tbody></table></div>';
                }
            }
        }

        // ---- 2c. Inspection master (VIN, mileage, engine, board state, etc.) ----
        if (!empty($inspection['master']['detail'])) {
            $masterDetail = $inspection['master']['detail'];
            // Public view: never expose the VIN here (it's hidden site-wide).
            if ($hideVin) unset($masterDetail['vin']);
            $rows = parsing_report_walk($masterDetail, $lang, 0);
            if ($rows !== '') {
                $tbl = '<table class="er-table"><tbody>'.$rows.'</tbody></table>';
                $s_master = $pubLook
                    ? parsing_report_collapsible($lbl('inspmaster'), $tbl) // public look: collapsed
                    : '<div class="er-section"><h4>'.$lbl('inspmaster').'</h4>'.$tbl.'</div>';
            }
        }


        // ---- 2d. Inspection photos (real front/back shots taken by Encar) ----
        // Encar stores them at a fixed path: {host}/carsdata/cars/inspection/{id}_photoFront.jpg
        // and _photoBack.jpg. We build those URLs straight from the vehicle id, so
        // photos show even when the saved payload's images[] is missing. Whatever
        // images[] does contain is merged in (deduped) so we never lose anything.
        $imgHost = 'https://ci.encar.com';
        $photoUrls = [];
        $vid = $inspection['vehicleId'] ?? ($report['real_carid'] ?? ($report['carid'] ?? null));
        if (!empty($vid)) {
            $photoUrls[] = $imgHost.'/carsdata/cars/inspection/'.$vid.'_photoFront.jpg';
            $photoUrls[] = $imgHost.'/carsdata/cars/inspection/'.$vid.'_photoBack.jpg';
        }
        if (!empty($inspection['images']) && is_array($inspection['images'])) {
            foreach ($inspection['images'] as $im) {
                $path = $im['path'] ?? '';
                if ($path === '') continue;
                $src = preg_match('#^https?://#', $path) ? $path : $imgHost.$path;
                if (!in_array($src, $photoUrls, true)) $photoUrls[] = $src;
            }
        }
        // $hideVin also marks the public view → skip the inspection photos there.
        if ($photoUrls && !$hideVin) {
            $thumbs = '';
            // onerror: hide a tile whose image 404s; then, if NO tile is left
            // visible in the section (all photos failed), hide the whole section
            // including its "Foto inspecție" heading — no empty frames remain.
            $onerr = "this.parentNode.style.display='none';"
                   . "var s=this.closest('.er-section-photos');"
                   . "if(s&&!s.querySelector('.er-photo:not([style*=\"none\"])'))s.style.display='none';";
            foreach ($photoUrls as $src) {
                $thumbs .= '<a class="er-photo" href="'.htmlspecialchars($src).'" target="_blank" rel="noopener">'
                         . '<img src="'.htmlspecialchars($src).'" alt="" onerror="'.htmlspecialchars($onerr, ENT_QUOTES).'"></a>';
            }
            $s_photos = '<div class="er-section er-section-photos"><h4>'.$lbl('photos').'</h4>'
                . '<div class="er-photos">'.$thumbs.'</div></div>';
        }

        // ---- 3. History (record) ----
        if (!empty($record)) {
            $accCnt = (int)($record['accidentCnt'] ?? 0);
            $ownCnt = (int)($record['ownerChangeCnt'] ?? 0);
            $myAcc  = (int)($record['myAccidentCnt'] ?? 0);
            $otAcc  = (int)($record['otherAccidentCnt'] ?? 0);

            // Helper: a row that is GOOD when count is 0, BAD/red when > 0.
            $flagRow = function (string $label, int $cnt) {
                if ($cnt > 0) {
                    return '<tr><td>'.$label.'</td><td class="er-stcell"><span class="er-badge er-bad">'.$cnt.'</span></td></tr>';
                }
                return '<tr><td>'.$label.'</td><td class="er-stcell"><span class="er-badge er-ok">—</span></td></tr>';
            };

            $rows  = '<tr><td>'.$lbl('accidents').'</td><td class="er-stcell">'
                   . ($accCnt > 0 ? '<span class="er-badge er-bad">'.$accCnt.'</span>'
                                  : '<span class="er-badge er-ok">'.$lbl('no_accident').'</span>').'</td></tr>';
            // My-car vs third-party accident split (only when meaningful).
            if ($myAcc > 0 || $otAcc > 0) {
                $rows .= '<tr><td class="er-item er-d1">'.$lbl('my_accidents').'</td><td class="er-stcell">'.$myAcc.'</td></tr>'
                       . '<tr><td class="er-item er-d1">'.$lbl('other_accidents').'</td><td class="er-stcell">'.$otAcc.'</td></tr>';
            }
            $rows .= '<tr><td>'.$lbl('owners').'</td><td class="er-stcell">'.$ownCnt.'</td></tr>';
            // Serious-status flags — always show (a "—" reassures the buyer).
            $rows .= $flagRow($lbl('total_loss'), (int)($record['totalLossCnt'] ?? 0));
            $rows .= $flagRow($lbl('flood'),      (int)($record['floodTotalLossCnt'] ?? 0) + (int)($record['floodPartLossCnt'] ?? 0));
            $rows .= $flagRow($lbl('theft'),      (int)($record['robberCnt'] ?? 0));
            // Commercial / government / business use lowers value — show if present.
            if ((int)($record['business'] ?? 0) > 0) $rows .= $flagRow($lbl('business_use'), (int)$record['business']);
            if ((int)($record['government'] ?? 0) > 0) $rows .= $flagRow($lbl('gov_use'), (int)$record['government']);

            // Accident details, if any (formatted date + real repair cost in ~€).
            // Repair cost is the sum of parts + labor + paint, which is what Encar
            // shows as 수리비용 (the actual damage to this car).
            $accDetails = '';
            if (!empty($record['accidents'])) {
                $accDetails .= '<tr class="er-grouprow"><td colspan="2" class="er-sub">'.$lbl('accident_list').'</td></tr>';
                foreach ($record['accidents'] as $a) {
                    $dateTxt   = htmlspecialchars(parsing_report_value('date', $a['date'] ?? '', $lang));
                    $repairKrw = (int)($a['partCost'] ?? 0) + (int)($a['laborCost'] ?? 0) + (int)($a['paintingCost'] ?? 0);
                    $costTxt   = htmlspecialchars(parsing_report_value('partCost', $repairKrw, $lang));
                    // Date on the left, repair cost as a badge on the right — keeps
                    // it aligned with all other status cells and never gets clipped.
                    $accDetails .= '<tr><td class="er-item er-d1">'.$dateTxt.'</td>'
                                 . '<td class="er-stcell"><span class="er-badge er-cost">'.$costTxt.'</span></td></tr>';
                }
            }
            if ($pubLook) {
                // Public look: compact summary. Top stats as chips, serious flags as
                // a tight inline row, accident details as a small list.
                $chip = function ($label, $val, $bad = false) {
                    return '<div class="er-hchip'.($bad ? ' er-hchip-bad' : '').'">'
                         . '<span class="er-hchip-v">'.$val.'</span>'
                         . '<span class="er-hchip-l">'.$label.'</span></div>';
                };
                $chips  = $chip($lbl('accidents'), $accCnt > 0 ? $accCnt : '0', $accCnt > 0);
                if ($myAcc > 0 || $otAcc > 0) {
                    $chips .= $chip($lbl('my_accidents'), $myAcc, $myAcc > 0)
                            . $chip($lbl('other_accidents'), $otAcc, $otAcc > 0);
                }
                $chips .= $chip($lbl('owners'), $ownCnt);

                // Serious-status flags as compact pills. "Total loss" (Полная
                // гибель) is intentionally omitted on the public page.
                $noTxt = $lbl('flag_no');
                $flagPill = function ($label, $cnt) use ($noTxt) {
                    $bad = $cnt > 0;
                    return '<span class="er-hpill'.($bad ? ' er-hpill-bad' : ' er-hpill-ok').'">'
                         . $label.': '.($bad ? $cnt : $noTxt).'</span>';
                };
                $flags  = $flagPill($lbl('flood'), (int)($record['floodTotalLossCnt'] ?? 0) + (int)($record['floodPartLossCnt'] ?? 0));
                $flags .= $flagPill($lbl('theft'), (int)($record['robberCnt'] ?? 0));
                if ((int)($record['business'] ?? 0) > 0)   $flags .= $flagPill($lbl('business_use'), (int)$record['business']);
                if ((int)($record['government'] ?? 0) > 0) $flags .= $flagPill($lbl('gov_use'), (int)$record['government']);

                // Accident details as a clean list (date left, repair cost right).
                $accList = '';
                if (!empty($record['accidents'])) {
                    $items = '';
                    foreach ($record['accidents'] as $a) {
                        $dateTxt   = htmlspecialchars(parsing_report_value('date', $a['date'] ?? '', $lang));
                        $repairKrw = (int)($a['partCost'] ?? 0) + (int)($a['laborCost'] ?? 0) + (int)($a['paintingCost'] ?? 0);
                        $costTxt   = htmlspecialchars(parsing_report_value('partCost', $repairKrw, $lang));
                        $items .= '<li><span class="er-acc-date">'.$dateTxt.'</span>'
                                . '<span class="er-acc-cost-v">'.$costTxt.'</span></li>';
                    }
                    $accList = '<div class="er-acc-sub">'.$lbl('accident_list').'</div>'
                             . '<ul class="er-acc-list">'.$items.'</ul>';
                }

                // No section <h4> here: the card's own collapsible header already
                // reads "История", so the chips start directly under it.
                $s_history = '<div class="er-section">'
                    . '<div class="er-hist-compact">'
                    . '<div class="er-hchips">'.$chips.'</div>'
                    . '<div class="er-hpills">'.$flags.'</div>'
                    . $accList
                    . '</div></div>';
            } else {
                $s_history = '<div class="er-section"><h4>'.$lbl('history').'</h4>'
                    . '<table class="er-table"><tbody>'.$rows.$accDetails.'</tbody></table></div>';
            }
        }

        // ---- 3b. Painted panels (cosmetic repair without replacement) ----
        // Public view covers painted panels visually in the body diagram, so skip
        // this redundant text list there; keep it for the admin detail report.
        $paints = $inspection['master']['detail']['paintPartTypes'] ?? [];
        if (!$pubLook && !empty($paints) && is_array($paints)) {
            $names = [];
            foreach ($paints as $pp) {
                if (isset($pp['title'])) $names[] = htmlspecialchars(parsing_report_tr((string)$pp['title'], $lang));
            }
            if ($names) {
                $s_painted = '<div class="er-section"><h4>'.$lbl('painted').'</h4>'
                    . '<div class="er-equip"><ul class="er-optlist er-paintlist">'
                    . '<li>'.implode('</li><li>', $names).'</li></ul></div></div>';
            }
        }

        // ---- 4. Equipment / options (grouped by category, Encar layout) ----
        $optCodes = $report['options']['standard'] ?? [];
        if (!empty($optCodes)) {
            // Codes the car actually has (zero-padded to 3 digits for matching).
            $have = [];
            foreach ($optCodes as $code) $have[str_pad((string)$code, 3, '0', STR_PAD_LEFT)] = true;

            $dict  = parsing_report_options_dict();
            $byCat = ['ext' => [], 'safety' => [], 'comfort' => [], 'seats' => []];
            foreach ($dict as $cat => $parents) {
                foreach ($parents as $opt) {
                    $subs = $opt['subs'] ?? [];
                    // Which sub-options does the car have?
                    $haveSubs = [];
                    foreach ($subs as $scode => $skr) {
                        if (isset($have[$scode])) $haveSubs[] = parsing_report_tr($skr, $lang);
                    }
                    // Show the parent if the car has the parent code itself, or any
                    // of its sub-options. (Encar lists the parent whenever present.)
                    if (!isset($have[$opt['p']]) && empty($haveSubs)) continue;
                    $name = parsing_report_tr($opt['kr'], $lang);
                    if ($haveSubs) $name .= ' (' . implode(', ', $haveSubs) . ')';
                    if (!in_array($name, $byCat[$cat], true)) $byCat[$cat][] = $name; // de-dupe
                }
            }
            $optHtml = '';
            foreach (['ext', 'safety', 'comfort', 'seats'] as $cat) {
                if (empty($byCat[$cat])) continue;
                $items = '';
                foreach ($byCat[$cat] as $name) {
                    $items .= '<li>'.htmlspecialchars($name).'</li>';
                }
                $optHtml .= '<div class="er-optcat"><h5>'.htmlspecialchars(parsing_report_options_cat($cat, $lang)).'</h5>'
                          . '<ul class="er-optlist">'.$items.'</ul></div>';
            }
            if ($optHtml !== '') {
                $eqH = $hideVin ? '' : '<h4>'.$lbl('equipment').'</h4>';
                // Public look gets the prettier card-grid styling (er-equip-pub).
                $eqCls = $pubLook ? 'er-equip er-equip-pub' : 'er-equip';
                $s_equipment = '<div class="er-section">'.$eqH.'<div class="'.$eqCls.'">'.$optHtml.'</div></div>';
            }
        }

        // Assemble in display order. 'history' = everything inspection-related;
        // 'equipment' = just the dotări. 'all' keeps the original combined report.
        if ($part === 'history') {
            // Public view: the body diagram ($s_body) already shows every panel +
            // condition, so the "Frame structure" text table ($s_structure) is
            // redundant here — drop it. Admin ('all') still keeps it.
            $sections   = $s_history . $s_painted . $s_body . $s_tech . $s_master . $s_photos;
            $headTitle  = $lbl('history');
        } elseif ($part === 'equipment') {
            $sections   = $s_equipment;
            $headTitle  = $lbl('equipment');
        } else {
            $sections   = $s_history . $s_painted . $s_body . $s_structure . $s_tech . $s_master . $s_equipment . $s_photos;
            $headTitle  = $lbl('title');
        }

        if ($sections === '') return '';

        $css = '<style>
            .encar-report{--er-red:#e2001a;--er-line:#eef0f3;margin:15px 0 30px;background:#fff;border:1px solid #efefef;border-radius:18px;box-shadow:0 10px 30px rgba(20,20,40,.07);overflow:hidden;}
            /* public page: report cards carry no own margin. The top gap to the
               price table and the bottom gap to the similar-price block must be
               EQUAL. The .smlr block below uses margin-top:10px; the price table
               above has a 30px bottom margin. Pull the report up by (30px - 10px)
               so the visible top gap is also 10px. */
            .encar-report-public .encar-report{margin:0;}
            .md-price-block + .encar-report-public{margin-top:-20px;}
            /* gap between the two report cards (Istoric vs Dotări) — a clear:both
               div sits between them, so use the general sibling combinator (~). */
            .encar-report-public .encar-report ~ .encar-report{margin-top:12px;}
            /* Mobile: the price table bottom margin is 15px (not 30px), and the
               report flows in the main column, so the negative pull and the missing
               bottom gap differ. Give the report a clean 10px top+bottom gap. */
            @media (max-width:768px){
                /* Identical top & bottom gaps. Override the desktop negative pull
                   (-20px) — on mobile the price card bottom margin is zeroed and the
                   report wrapper owns equal padding top/bottom. Padding does not
                   collapse, so the top gap cannot disappear nor overlap the price. */
                .md-price-mobile-only > .md-price-block{margin-bottom:0;}
                .md-price-block + .encar-report-public{margin-top:0;}
                .encar-report-public{margin:0;padding:12px 0;}
            }
            .encar-report > .er-head{margin:0;padding:11px 22px;font-size:1.05rem;font-weight:bold;color:#fff;text-transform:uppercase;letter-spacing:.4px;background:linear-gradient(135deg,#2b2b2b 0%,#444 100%);display:flex;align-items:center;justify-content:space-between;border:none;width:100%;text-align:left;font-family:inherit;}
            .encar-report .er-body{display:none;padding:8px 28px 22px;}
            .encar-report.open .er-body{display:block;}

            /* VIN banner — prominent, monospace, with a copy button */
            .er-vin{display:flex;align-items:center;gap:12px;margin:0 0 16px;padding:12px 18px;background:linear-gradient(135deg,#1f2430,#33405a);border-radius:12px;color:#fff;box-shadow:0 6px 18px rgba(20,20,40,.12);}
            .er-vin-label{font-size:.72rem;font-weight:700;letter-spacing:1px;color:#aab4c5;text-transform:uppercase;flex:none;}
            .er-vin-code{font-family:"SFMono-Regular",Consolas,"Liberation Mono",Menlo,monospace;font-size:1.05rem;font-weight:600;letter-spacing:1.5px;color:#fff;word-break:break-all;flex:1;}
            .er-vin-copy{display:flex;align-items:center;justify-content:center;gap:5px;min-width:36px;height:32px;padding:0 11px;border:1px solid rgba(255,255,255,.25);border-radius:8px;background:rgba(255,255,255,.10);color:#fff;font-size:.8rem;font-weight:600;cursor:pointer;flex:none;transition:background .15s,border-color .15s;}
            .er-vin-copy:hover{background:rgba(255,255,255,.20);border-color:rgba(255,255,255,.45);}

            /* One unified document: chapters live inside a single bordered sheet,
               separated by a divider — but each chapter heading stays bold and
               distinct so sections remain easy to tell apart and to read. */
            .er-doc{border:1px solid var(--er-line);border-radius:16px;overflow:hidden;background:#fff;box-shadow:0 6px 22px rgba(20,20,40,.06);}
            /* public: no card-in-card — drop the inner .er-doc chrome and the wide
               body padding so content uses the outer card width. */
            .encar-report-public .er-body{padding:6px 10px 14px;}
            .encar-report-public .er-doc{border:none;border-radius:0;box-shadow:none;background:transparent;}
            .er-section{margin:0;background:#fff;}
            .er-section + .er-section{border-top:6px solid #f4f6f8;}   /* thick neutral divider between chapters */
            /* collapsible buttons sit tighter together (small gap, no thick divider) */
            .er-section-collapse{border-top:none !important;margin-top:7px;}
            .er-section h4{display:flex;align-items:center;gap:10px;margin:0;padding:14px 20px;font-size:1.02rem;font-weight:700;color:#1f2430;background:linear-gradient(180deg,#fbfcfd,#f4f6f8);border-bottom:1px solid var(--er-line);letter-spacing:.2px;}
            .er-section h4:before{content:"";width:5px;height:19px;border-radius:3px;background:var(--er-red);flex:none;box-shadow:0 0 0 3px rgba(226,0,26,.10);}

            .er-table{width:100%;border-collapse:collapse;font-size:.93rem;table-layout:fixed;}
            .er-table thead th{text-align:left;padding:9px 18px;background:#fcfcfd;border-bottom:1px solid var(--er-line);color:#9097a3;font-weight:600;font-size:.78rem;text-transform:uppercase;letter-spacing:.3px;}
            .er-table thead th:last-child{width:180px;text-align:right;}
            .er-table tbody td{padding:9px 18px;border-bottom:1px solid #f5f6f8;color:#2c333f;vertical-align:middle;word-break:break-word;}
            .er-table tbody tr:last-child td{border-bottom:none;}
            .er-table tbody tr:hover td{background:#fcfcfd;}
            /* status cell: fixed width so every badge lines up on the same column */
            .er-stcell{width:180px;text-align:right;white-space:nowrap;}

            /* item names + depth indentation with a guide line */
            .er-item{color:#2c333f;}
            .er-d1{padding-left:34px !important;position:relative;color:#525a67;}
            .er-d2{padding-left:50px !important;position:relative;color:#525a67;}
            .er-d3{padding-left:66px !important;position:relative;color:#525a67;}
            .er-d1:before,.er-d2:before,.er-d3:before{content:"";position:absolute;top:0;bottom:0;width:2px;background:#e9ebf0;}
            .er-d1:before{left:20px;}.er-d2:before{left:36px;}.er-d3:before{left:52px;}

            /* sub-heading row (a group that owns the items beneath it) */
            .er-sub{font-weight:700 !important;color:#1f2430 !important;background:#f7f8fa !important;font-size:.9rem;letter-spacing:.2px;}
            .er-grouprow + tr td{padding-top:9px;}

            /* status badges */
            .er-badge{display:inline-block;padding:3px 11px;border-radius:999px;font-size:.8rem;font-weight:600;line-height:1.3;white-space:normal;}
            .er-badge.er-ok{color:#444b58;background:#eef0f3;}
            .er-badge.er-bad{color:#c01425;background:#fde8ea;}
            .er-badge.er-cost{color:#444b58;background:#eef0f3;}
            /* legacy plain cells (body-condition table) */
            .er-table td.er-ok{color:#444b58;font-weight:600;}
            .er-table td.er-bad{color:#c01425;font-weight:600;}

            .er-comment{margin:0;padding:11px 18px;font-size:.85rem;color:#5b626f;line-height:1.45;white-space:pre-line;background:#fafbfc;border-top:1px solid var(--er-line);}

            /* equipment grid */
            .er-equip{padding:14px 18px 4px;}
            .er-optcat{margin-bottom:16px;}
            .er-optcat h5{margin:0 0 8px;font-size:.82rem;color:#9097a3;font-weight:700;text-transform:uppercase;letter-spacing:.4px;}
            .er-optlist{margin:0;padding:0;list-style:none;display:grid;grid-template-columns:1fr 1fr;gap:6px 22px;}
            .er-optlist li{position:relative;padding-left:24px;font-size:.9rem;color:#2c333f;line-height:1.45;}
            .er-optlist li:before{content:"\\2713";position:absolute;left:0;top:1px;width:16px;height:16px;border-radius:50%;background:#eef0f3;color:#444b58;font-size:.7rem;font-weight:700;display:flex;align-items:center;justify-content:center;}

            /* ---- public equipment: flat category groups (no card-in-card) ---- */
            .er-equip-pub{padding:10px 4px 4px;column-width:330px;column-gap:28px;}
            .er-equip-pub .er-optcat{margin:0 0 16px;break-inside:avoid;-webkit-column-break-inside:avoid;}
            .er-equip-pub .er-optcat h5{margin:0 0 6px;padding:0 0 6px;font-size:.74rem;color:#9097a3;font-weight:700;text-transform:uppercase;letter-spacing:.4px;border-bottom:1px solid #eef0f3;}
            /* two compact columns of items per category */
            .er-equip-pub .er-optlist{display:grid;grid-template-columns:1fr 1fr;gap:0 14px;padding:6px 0 0;}
            .er-equip-pub .er-optlist li{padding:3px 0 3px 20px;font-size:.82rem;color:#3a4250;line-height:1.3;border:none;}
            .er-equip-pub .er-optlist li:before{top:3px;width:14px;height:14px;font-size:.62rem;background:#9097a3;color:#fff;}

            /* inspection photos — two per row, each ~50% width */
            .er-photos{display:flex;flex-wrap:wrap;gap:12px;padding:14px 18px;}
            .er-photo{display:block;flex:0 0 calc(50% - 6px);max-width:calc(50% - 6px);border-radius:10px;overflow:hidden;border:1px solid var(--er-line);line-height:0;transition:transform .15s,box-shadow .15s;}
            .er-photo:hover{transform:translateY(-2px);box-shadow:0 6px 18px rgba(20,20,40,.12);}
            .er-photo img{width:100%;height:auto;display:block;}

            /* ---- visual body diagram (public view): line-art car ---- */
            /* 3 columns: [car] [legend + numbered affected] [full panel list].
               Wraps gracefully on narrow widths. */
            .erd-wrap{display:flex;gap:28px;align-items:flex-start;padding:20px 18px;flex-wrap:wrap;}
            .erd-col{min-width:0;}
            .erd-col-car{flex:0 0 auto;}
            .erd-col-legend{flex:0 1 auto;padding-right:14px;border-right:1px solid #eef0f3;}
            .erd-col-full{flex:1 1 220px;min-width:190px;}
            .erd-svg{width:150px;height:auto;display:block;filter:drop-shadow(0 4px 10px rgba(20,20,40,.08));}
            /* car body base + panels (transparent until affected, then soft fill) */
            .erd-body{fill:#f1f3f6;}
            /* panel fill comes from an inline style="fill:..." (the X/W/C/A/U/T
               colour). Default is transparent until a damage type sets a colour. */
            .erd-p{fill:transparent;transition:fill .15s;cursor:default;}
            /* seam lines: crisp, clearly separating each panel */
            .erd-seams line,.erd-seams path{fill:none;stroke:#aeb6c2;stroke-width:1.8;stroke-linecap:round;}
            /* glass / lamps / mirrors / wheels */
            .erd-glass path,.erd-glass rect{fill:#cdd7e4;stroke:#aeb6c2;stroke-width:1.2;}
            .erd-lamp path{fill:#dfe5ec;stroke:#aeb6c2;stroke-width:1;}
            .erd-lamp-rear path{fill:#f0c9cc;}
            .erd-mirror path{fill:#c4ccd8;}
            .erd-wheel rect{fill:#2c3138;}
            .erd-outline{fill:none;stroke:#7d8694;stroke-width:3;stroke-linejoin:round;}
            /* letter badges on the drawing (X/W/C/A/U/T) — colour set inline */
            .erd-badge circle{stroke:#fff;stroke-width:1.5;}
            .erd-badge text{fill:#fff;font-size:13px;font-weight:800;text-anchor:middle;font-family:inherit;}
            /* column 2: legend (vertical) + affected panels with letter badge */
            .erd-legend{display:flex;flex-direction:column;gap:9px;margin-bottom:14px;padding-bottom:14px;border-bottom:1px solid #e8ebef;}
            .erd-leg{display:inline-flex;align-items:center;gap:8px;font-size:.84rem;color:#525a67;white-space:nowrap;}
            .erd-dot{width:18px;height:18px;border-radius:5px;flex:none;border:1px solid rgba(0,0,0,.10);display:inline-flex;align-items:center;justify-content:center;}
            .erd-dot b{color:#fff;font-size:.66rem;font-weight:800;line-height:1;}
            .erd-affected{list-style:none;margin:0;padding:0;display:flex;flex-direction:column;gap:9px;}
            .erd-affected li{display:flex;align-items:center;gap:10px;font-size:.88rem;color:#2c333f;}
            .erd-num{flex:none;width:22px;height:22px;border-radius:50%;color:#fff;font-size:.78rem;font-weight:800;display:flex;align-items:center;justify-content:center;}
            .erd-aff-nm{}
            .erd-clean{margin:0;padding:10px 14px;font-size:.9rem;font-weight:600;color:#444b58;background:#eef0f3;border-radius:10px;}
            /* column 3: full panel list (every panel + status) */
            .erd-full{list-style:none;margin:0;padding:0;display:flex;flex-direction:column;gap:7px;}
            .erd-full li{display:flex;align-items:center;justify-content:space-between;gap:8px;padding:9px 14px;font-size:.88rem;color:#2c333f;background:#f1f2f4;border-radius:10px;}
            .erd-full-nm{flex:1 1 auto;min-width:0;padding-right:6px;}
            .erd-full-st{flex:0 0 auto;font-size:.74rem;font-weight:700;text-transform:uppercase;letter-spacing:.3px;white-space:nowrap;color:#9097a3;text-align:right;}
            .erd-st-ok{color:#2c333f;} .erd-st-bad{color:#c01425;}

            /* ---- compact History (public) ---- */
            .er-hist-compact{padding:14px 18px 16px;}
            /* stat chips in a responsive grid (auto-fit, ~2-4 per row) */
            .er-hchips{display:grid;grid-template-columns:repeat(auto-fit,minmax(120px,1fr));gap:10px;margin-bottom:6px;}
            .er-hchip{background:#f7f8fa;border:1px solid #eef0f3;border-radius:12px;padding:10px 12px;text-align:center;}
            .er-hchip-v{display:block;font-size:1.35rem;font-weight:700;color:#1f2430;line-height:1.1;}
            .er-hchip-l{display:block;font-size:.74rem;color:#9097a3;margin-top:3px;line-height:1.25;}
            .er-hchip-bad{background:#fde8ea;border-color:#f6c9ce;}
            .er-hchip-bad .er-hchip-v{color:#c01425;}
            .er-hpills{display:flex;flex-wrap:wrap;gap:7px;margin-top:10px;}
            .er-hpill{font-size:.78rem;font-weight:600;padding:5px 11px;border-radius:10px;white-space:nowrap;}
            .er-hpill-ok{color:#444b58;background:#eef0f3;}
            .er-hpill-bad{color:#c01425;background:#fde8ea;}
            .er-acc-sub{margin:16px 0 6px;font-size:.78rem;font-weight:700;color:#9097a3;text-transform:uppercase;letter-spacing:.4px;}
            .er-acc-list{list-style:none;margin:0;padding:0;}
            .er-acc-list li{display:flex;align-items:center;justify-content:space-between;gap:12px;padding:8px 0;font-size:.9rem;border-bottom:1px solid #f0f2f5;}
            .er-acc-list li:last-child{border-bottom:none;}
            .er-acc-date{color:#2c333f;}
            .er-acc-cost-v{font-weight:700;color:#2c333f;white-space:nowrap;}

            /* ---- collapsible section (public): heading is the clickable summary ---- */
            .er-collapse{margin:0;}
            /* dark grey gradient bar (matches the footer); one continuous fill so
               the arrow never sits on a white gap next to the heading */
            .er-collapse > summary{list-style:none;cursor:pointer;display:flex;align-items:center;background:linear-gradient(135deg,#8a8a8a,#5a5a5a);border-bottom:none;}
            .er-collapse > summary::-webkit-details-marker{display:none;}
            .er-collapse > summary:hover{background:linear-gradient(135deg,#7f7f7f,#4f4f4f);}
            /* h4 inside: white text on the dark bar; keep the red accent bar */
            .er-collapse > summary > h4{flex:1;margin:0;padding:11px 22px;font-size:1.05rem;background:none;border:none;color:#fff;}
            .er-collapse > summary > h4:before{box-shadow:0 0 0 3px rgba(255,255,255,.15);}
            .er-collapse-arrow{flex:none;width:9px;height:9px;border-right:2px solid #fff;border-bottom:2px solid #fff;transform:rotate(45deg);transition:transform .2s;margin:0 20px 0 10px;}
            /* always-open sections (Istoric, Dotări) with a darker grey header bar */
            .er-section h4.er-dark-h{background:linear-gradient(135deg,#6e6e6e,#3e3e3e);color:#fff;border-bottom:none;}
            .er-section h4.er-dark-h:before{box-shadow:0 0 0 3px rgba(255,255,255,.15);}
            .er-collapse[open] .er-collapse-arrow{transform:rotate(-135deg);}
            .er-collapse[open] > summary .er-tech-ok{display:none;}

            /* ---- compact Technical inspection (public) ---- */
            .er-tech-ok{margin-right:6px;font-size:.74rem;font-weight:600;color:#444b58;background:#eef0f3;padding:3px 10px;border-radius:10px;white-space:nowrap;text-transform:none;letter-spacing:0;}
            /* flat system groups in a masonry grid (no card-in-card), matching the
               equipment list style. */
            .er-tc{column-width:330px;column-gap:28px;padding:10px 4px 4px;}
            .er-tc-card{margin:0 0 16px;break-inside:avoid;-webkit-column-break-inside:avoid;}
            /* system heading: name + small status pill, with a bottom line */
            .er-tc-h{display:flex;align-items:center;gap:8px;margin:0 0 6px;padding:0 0 6px;font-size:.74rem;font-weight:700;color:#9097a3;text-transform:uppercase;letter-spacing:.4px;border-bottom:1px solid #eef0f3;}
            .er-tc-pill{flex:none;display:inline-flex;align-items:center;justify-content:center;min-width:18px;height:18px;padding:0 5px;border-radius:999px;font-size:.68rem;font-weight:700;color:#fff;}
            .er-tc-pill-ok{background:#9097a3;}
            .er-tc-pill-bad{background:#e2001a;}
            /* rows: status dot + name + value */
            .er-tc-l{list-style:none;margin:0;padding:2px 0 0;}
            .er-tc-l li{display:flex;align-items:center;gap:8px;padding:3px 0;font-size:.82rem;line-height:1.3;}
            .er-tc-dot{flex:none;width:6px;height:6px;border-radius:50%;background:#9097a3;}
            .er-tc-bad .er-tc-dot{background:#e2001a;}
            .er-tc-n{flex:1;color:#3a4250;}
            .er-tc-s{flex:none;font-weight:600;white-space:nowrap;color:#444b58;}
            .er-tc-bad .er-tc-n{color:#c01425;font-weight:600;}
            .er-tc-bad .er-tc-s{color:#c01425;}

            @media (max-width:600px){.encar-report .er-body{padding:8px 14px 16px;}.encar-report > .er-head{padding:14px 16px;font-size:1.05rem;}.er-optlist{grid-template-columns:1fr;}.er-table thead th,.er-table tbody td{padding:8px 12px;}.erd-wrap{justify-content:center;}.erd-col-full{flex-basis:100%;}.er-hchip{min-width:calc(50% - 5px);}.er-tc{column-width:auto !important;column-count:1;}.er-equip-pub{column-width:auto;column-count:1;}}

            /* Inside the admin modal (er-wide): no card-in-card. Drop the collapsible
               header + outer card chrome; chapters become the visual cards instead. */
            .er-wide .encar-report{margin:0;border:none;border-radius:0;box-shadow:none;overflow:visible;background:transparent;}
            .er-wide .encar-report > .er-head{display:none;}
            .er-wide .encar-report .er-body{display:block;padding:0;}
        </style>';

        // VIN banner at the top of the document, with a one-click copy button.
        $vinHtml = '';
        // VIN banner belongs to the history card only — never in the equipment one.
        if ($vin !== '' && $part !== 'equipment') {
            $vinEsc = htmlspecialchars($vin, ENT_QUOTES);
            $copyTxt = ['ro' => 'Copiat!', 'ru' => 'Скопировано!', 'en' => 'Copied!'][$lang] ?? 'Copiat!';
            $vinHtml = '<div class="er-vin">'
                . '<span class="er-vin-label">VIN</span>'
                . '<span class="er-vin-code">'.$vinEsc.'</span>'
                . '<button type="button" class="er-vin-copy" title="Copy" '
                . 'onclick="navigator.clipboard.writeText(\''.$vinEsc.'\');'
                . 'var o=this.innerHTML;this.innerHTML=\''.htmlspecialchars($copyTxt, ENT_QUOTES).'\';'
                . 'var b=this;setTimeout(function(){b.innerHTML=o;},1200);">'
                . '<svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="9" y="9" width="13" height="13" rx="2" ry="2"></rect><path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"></path></svg>'
                . '</button></div>';
        }

        return $css.'<div style="clear:both"></div><div class="encar-report open">'
            . '<div class="er-head">'.$headTitle.'</div>'
            . '<div class="er-body">'.$vinHtml.'<div class="er-doc">'.$sections.'</div></div></div>';
    }
}
