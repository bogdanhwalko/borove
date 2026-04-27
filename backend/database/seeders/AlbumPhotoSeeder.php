<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class AlbumPhotoSeeder extends Seeder
{
    public function run(): void
    {
        DB::statement('SET FOREIGN_KEY_CHECKS=0');
        DB::table('photos')->truncate();
        DB::table('albums')->truncate();
        DB::statement('SET FOREIGN_KEY_CHECKS=1');

        $albumData = [
            ['Паска 2026 — громада освячує паски',         'easter-church',      '2026-04-19', ['easter-church','easter-basket','easter-candles','easter-crowd','easter-bread','easter-flowers','easter-family','easter-children','easter-morning']],
            ['Реконструкція криниці на центральній площі', 'old-well-village',   '2026-04-15', ['old-well-village','stone-well','village-workers','well-restoration','village-square','new-well']],
            ['День народження школи — 75 років',           'anniversary-party',  '2026-04-10', ['anniversary-party','school-hall','graduates-meeting','school-concert','school-teachers','school-history','school-diplomas','school-garden']],
            ['День села 2025',                             'village-celebration','2025-05-03', ['village-celebration','village-stage','village-fair','village-kids','village-dance','village-crowd','village-food','village-fireworks','village-night','village-music']],
            ['Зима в Боровому 2025',                       'winter-village',     '2025-12-25', ['winter-village','winter-road','winter-church','winter-forest','winter-kids','winter-morning','winter-yard','winter-tree','winter-frost']],
            ['Жнива 2025 — гарний врожай',                 'harvest-field',      '2025-08-18', ['harvest-field','harvest-combine','harvest-grain','harvest-workers','harvest-truck','harvest-bread']],
            ['Відкриття дитячого майданчика',              'playground-opening', '2026-03-15', ['playground-opening','playground-kids','playground-slide','playground-swing','village-children']],
            ['Великдень у Боровому 2025',                  'easter-2025',        '2025-04-20', ['easter-2025','easter-church-2','easter-procession','easter-icons','easter-community']],
            ['Осінь у Боровому 2025',                      'autumn-village',     '2025-10-10', ['autumn-village','autumn-forest','autumn-road','autumn-harvest','autumn-garden','autumn-colors']],
            ['Новий рік 2026 — свято в клубі',             'new-year-club',      '2025-12-31', ['new-year-club','new-year-tree','new-year-children','new-year-dance','new-year-lights']],
            ['Збір субботнику: прибираємо разом',          'cleanup-village',    '2026-04-05', ['cleanup-village','cleanup-team','cleanup-tools','cleanup-park','cleanup-result']],
            ['Відкриття нової бібліотеки',                 'library-new',        '2026-03-01', ['library-new','library-books','library-kids','library-reading','library-interior']],
            ['Стрій-загін: молодь садить дерева',          'tree-planting',      '2026-04-01', ['tree-planting','tree-saplings','tree-youth','tree-park']],
            ['День вишиванки 2025',                        'vyshyvanka-day',     '2025-05-15', ['vyshyvanka-day','vyshyvanka-women','vyshyvanka-children','vyshyvanka-parade','vyshyvanka-colors']],
            ['Ярмарок народних майстрів',                  'folk-market',        '2025-08-24', ['folk-market','folk-pottery','folk-embroidery','folk-woodwork','folk-crowd']],
            ['Рибний турнір на ставку',                    'fishing-tournament', '2026-03-20', ['fishing-tournament','fishing-pond','fishing-catch','fishing-winners','fishing-morning']],
            ['Концерт до Дня матері',                      'mothers-day-concert','2025-05-11', ['mothers-day-concert','mothers-day-flowers','mothers-day-stage','mothers-day-choir']],
            ['Городні роботи навесні',                     'spring-garden',      '2026-04-12', ['spring-garden','spring-planting','spring-seedlings','spring-soil','spring-flowers']],
            ['Велопробіг Борове–Костопіль',                'cycling-race',       '2026-04-06', ['cycling-race','cycling-start','cycling-road','cycling-finish','cycling-winners']],
            ['Футбольний матч: наші перемогли!',           'football-match',     '2026-03-28', ['football-match','football-team','football-goal','football-fans','football-trophy']],
            ['Дитячий карнавал на Масляну',                'carnival-kids',      '2026-03-03', ['carnival-kids','carnival-costumes','carnival-parade','carnival-games']],
            ['Відкриття сезону посівної 2026',             'sowing-season',      '2026-04-08', ['sowing-season','sowing-field','sowing-tractor','sowing-workers']],
            ['Новосілля в новому будинку',                  'new-house',         '2026-02-15', ['new-house','new-house-family','new-house-interior','new-house-celebration']],
            ['Зустріч випускників 2025',                   'graduates-2025',     '2025-06-28', ['graduates-2025','graduates-school','graduates-memories','graduates-photo','graduates-party']],
            ['Майстер-клас із вишивки',                    'embroidery-class',   '2026-03-10', ['embroidery-class','embroidery-pattern','embroidery-hands','embroidery-result']],
            ['Відкриття сезону купання',                   'swimming-season',    '2025-06-01', ['swimming-season','swimming-pond','swimming-kids','swimming-fun']],
            ['Збори громади: обговорення бюджету',         'community-meeting',  '2026-02-20', ['community-meeting','community-hall','community-vote','community-discussion']],
            ['Пасовище: перший вигін худоби',              'cattle-grazing',     '2026-04-20', ['cattle-grazing','cattle-field','cattle-herd','cattle-morning']],
            ['Фестиваль борщу',                            'borsch-festival',    '2025-07-12', ['borsch-festival','borsch-cooking','borsch-tasting','borsch-winners','borsch-crowd']],
            ['Святкування Водохреща',                      'epiphany-2026',      '2026-01-19', ['epiphany-2026','epiphany-water','epiphany-church','epiphany-ice','epiphany-ritual']],
            ['Трудовий десант: ремонт огорожі цвинтаря',  'cemetery-repair',    '2026-04-03', ['cemetery-repair','cemetery-workers','cemetery-fence','cemetery-memorial']],
            ['Літній дитячий табір 2025',                  'summer-camp',        '2025-07-05', ['summer-camp','summer-camp-kids','summer-camp-games','summer-camp-nature','summer-camp-fire']],
            ['Новорічна виставка малюнків у школі',        'xmas-art-school',    '2025-12-20', ['xmas-art-school','xmas-drawings','xmas-students','xmas-gallery']],
            ['Збір лікарських трав у лісі',               'herbs-gathering',    '2025-07-20', ['herbs-gathering','herbs-forest','herbs-plants','herbs-drying']],
            ['Спортивне свято для дітей',                  'sports-kids-day',    '2025-06-15', ['sports-kids-day','sports-running','sports-jumping','sports-prizes','sports-smiles']],
            ['Меморіал загиблим у ВВВ: вшанування',       'memorial-wwii',      '2025-05-09', ['memorial-wwii','memorial-flowers','memorial-ceremony','memorial-veterans','memorial-monument']],
            ['Ремонт дороги: до і після',                  'road-before-after',  '2026-04-22', ['road-before-after','road-workers','road-asphalt','road-finished']],
            ['Перший сніг 2025–2026',                      'first-snow-2025',    '2025-11-28', ['first-snow-2025','first-snow-morning','first-snow-children','first-snow-landscape']],
            ['Обжинки 2025',                               'harvest-festival',   '2025-08-30', ['harvest-festival','harvest-wheat','harvest-dance','harvest-bread','harvest-community']],
            ['Козацькі забави: фестиваль традицій',        'cossack-festival',   '2025-10-14', ['cossack-festival','cossack-games','cossack-costumes','cossack-fire']],
            ['Кросовий забіг «Борове–2026»',               'cross-run-2026',     '2026-04-13', ['cross-run-2026','cross-run-start','cross-run-route','cross-run-finish','cross-run-medals']],
            ['Відкриття аптечного кіоску',                 'pharmacy-opening',   '2026-02-01', ['pharmacy-opening','pharmacy-inside','pharmacy-pharmacist']],
            ['Паводок 2026: відеозвіт',                    'flood-2026',         '2026-03-25', ['flood-2026','flood-river','flood-road','flood-measures']],
            ['День Конституції 2025',                      'constitution-day',   '2025-06-28', ['constitution-day','constitution-concert','constitution-flag','constitution-community']],
            ['Тематична вечірка в клубі',                  'club-party',         '2025-11-01', ['club-party','club-costumes','club-dj','club-dance']],
            ['Виїзна фотосесія у лісі',                    'forest-photoshoot',  '2025-09-20', ['forest-photoshoot','forest-autumn','forest-people','forest-path']],
            ['Прибирання ставка: чистий берег',            'pond-cleanup',       '2026-04-18', ['pond-cleanup','pond-volunteers','pond-trash','pond-result','pond-clean']],
            ['Дитяча вистава «Котигорошко»',               'puppet-show',        '2026-02-14', ['puppet-show','puppet-stage','puppet-kids','puppet-actors']],
            ['Урок мужності для школярів',                 'courage-lesson',     '2025-10-28', ['courage-lesson','courage-veterans','courage-school','courage-ceremony']],
            ['Кулінарний конкурс «Смак Борового»',         'cooking-contest',    '2025-11-15', ['cooking-contest','cooking-dishes','cooking-jury','cooking-winner','cooking-tasting']],
            ['Ювілей жительки: 90 років Марії Іванівні',  'birthday-90',        '2026-01-10', ['birthday-90','birthday-flowers','birthday-family','birthday-cake']],
            ['Фольклорний гурток: перші виступи',          'folklore-debut',     '2025-12-06', ['folklore-debut','folklore-costumes','folklore-singing','folklore-dance']],
            ['Родинне свято «Від дідів до онуків»',        'family-festival',    '2025-09-07', ['family-festival','family-games','family-photos','family-dinner']],
            ['Осінній базар у центрі',                     'autumn-market',      '2025-10-05', ['autumn-market','autumn-vegetables','autumn-fruits','autumn-crafts','autumn-buyers']],
            ['Відкриття нового поштового відділення',      'post-office-new',    '2026-01-15', ['post-office-new','post-interior','post-ceremony','post-residents']],
            ['Квіткова виставка у бібліотеці',             'flower-show',        '2026-03-08', ['flower-show','flower-arrangements','flower-winners','flower-decoration']],
            ['Зліт волонтерів громади',                    'volunteers-day',     '2025-12-13', ['volunteers-day','volunteers-team','volunteers-work','volunteers-awards']],
            ['Посвята у першокласники',                    'first-grade',        '2025-09-01', ['first-grade','first-grade-kids','first-grade-parents','first-grade-flowers']],
            ['Поїздка до Почаєва',                         'pochaiv-trip',       '2025-08-05', ['pochaiv-trip','pochaiv-lavra','pochaiv-pilgrim','pochaiv-bus']],
            ['Відкриття шкільного музею',                  'school-museum',      '2025-10-01', ['school-museum','school-museum-exhibits','school-museum-students','school-museum-history']],
            ['Виставка-продаж меду',                       'honey-fair',         '2025-08-14', ['honey-fair','honey-jars','honey-beekeeper','honey-tasting','honey-award']],
            ['Народна забудова: традиційні хати',          'folk-architecture',  '2025-11-20', ['folk-architecture','folk-house','folk-decoration','folk-garden']],
            ['Щорічний огляд художньої самодіяльності',   'talent-show-2025',   '2025-12-05', ['talent-show-2025','talent-singing','talent-dance','talent-audience','talent-winners']],
            ['Вечір у стилі «Ретро 80-х»',                 'retro-evening',      '2025-11-08', ['retro-evening','retro-music','retro-costumes','retro-dance','retro-party']],
            ['Зустріч з письменником Олексієм Дяченком',  'writer-meeting',     '2025-10-22', ['writer-meeting','writer-books','writer-talk','writer-autograph']],
            ['Освячення нового транспортного засобу',      'vehicle-blessing',   '2026-03-17', ['vehicle-blessing','vehicle-ceremony','vehicle-crowd','vehicle-priest']],
            ['День відкритих дверей у пожежній частині',  'fire-station-open',  '2026-04-07', ['fire-station-open','fire-truck','fire-demo','fire-children','fire-station']],
            ['Перший мотошолом: урок безпеки',             'bike-safety',        '2026-03-22', ['bike-safety','bike-kids','bike-helmet','bike-road']],
            ['Конкурс квіткових клумб',                    'flowerbed-contest',  '2025-07-25', ['flowerbed-contest','flowerbed-winners','flowerbed-colors','flowerbed-garden']],
            ['Підвішене кіно: перший показ',               'outdoor-film',       '2025-08-10', ['outdoor-film','outdoor-screen','outdoor-audience','outdoor-night']],
            ['Новорічна ялинка на площі',                  'xmas-tree-square',   '2025-12-18', ['xmas-tree-square','xmas-tree-lights','xmas-tree-children','xmas-tree-ceremony']],
            ['Пуск тепла: початок опалювального сезону',  'heating-season',     '2025-10-08', ['heating-season','heating-workers','heating-pipes','heating-ceremony']],
            ['Обмін насінням: весняна традиція',           'seed-exchange',      '2026-03-30', ['seed-exchange','seed-varieties','seed-farmers','seed-market']],
            ['Дискотека у клубі: молодь веселиться',       'disco-club',         '2025-11-22', ['disco-club','disco-dance','disco-lights','disco-dj']],
            ['День захисту довкілля в школі',              'eco-day-school',     '2025-06-05', ['eco-day-school','eco-posters','eco-action','eco-students']],
            ['Освячення пасіки',                           'apiary-blessing',    '2025-05-01', ['apiary-blessing','apiary-bees','apiary-ritual','apiary-honey']],
            ['Відкриття туристичного маршруту',            'tourist-route',      '2026-04-02', ['tourist-route','tourist-forest','tourist-map','tourist-group','tourist-sign']],
            ['Покрова 2025: святковий молебень',           'pokrova-2025',       '2025-10-14', ['pokrova-2025','pokrova-church','pokrova-procession','pokrova-community']],
            ['Фото на пам\'ять: жителі 100+ років',       'centennial-photo',   '2025-09-15', ['centennial-photo','centennial-elder','centennial-family','centennial-village']],
            ['Ярмарок «Зимові дари»',                      'winter-market',      '2025-12-07', ['winter-market','winter-crafts','winter-food','winter-buyers','winter-atmosphere']],
            ['Освячення криниці на Водохреща',             'well-blessing',      '2026-01-19', ['well-blessing','well-ceremony','well-water','well-crowd']],
            ['Бджолярський гурток: перший сезон',         'beekeeping-club',    '2025-05-20', ['beekeeping-club','beekeeping-hive','beekeeping-kids','beekeeping-frame']],
            ['Майстерня різьбяра: відкриті двері',         'woodcarving',        '2026-02-08', ['woodcarving','woodcarving-tools','woodcarving-works','woodcarving-master']],
            ['Відкриття сезону полювання',                 'hunting-season',     '2025-11-01', ['hunting-season','hunting-forest','hunting-dogs','hunting-ceremony']],
            ['Бал випускників 2025',                       'graduation-ball',    '2025-06-20', ['graduation-ball','graduation-dance','graduation-flowers','graduation-parents','graduation-night']],
            ['Запуск «Свіжої хати»: ремонт переможця',    'fresh-home',         '2025-09-30', ['fresh-home','fresh-home-before','fresh-home-after','fresh-home-family']],
            ['Тематичний вечір «Пісні нашої молодості»',  'memory-concert',     '2025-10-18', ['memory-concert','memory-singer','memory-crowd','memory-stage']],
            ['Квест «Велика знахідка»',                    'quest-game',         '2026-03-05', ['quest-game','quest-kids','quest-clues','quest-prize','quest-team']],
            ['Завершення навчального року: свято в школі', 'end-of-school',      '2025-06-07', ['end-of-school','end-school-ceremony','end-school-diplomas','end-school-flowers','end-school-joy']],
            ['Оновлений центральний парк: фото',           'park-renovated',     '2026-04-17', ['park-renovated','park-paths','park-benches','park-greenery','park-kids']],
            ['Маланка 2026: традиційне свято',             'malanka-2026',       '2026-01-13', ['malanka-2026','malanka-costumes','malanka-parade','malanka-music','malanka-fire']],
            ['Лісові прогулянки: маршрут «Борівський»',   'forest-walk',        '2026-04-16', ['forest-walk','forest-path','forest-trees','forest-group','forest-nature']],
            ['Громада відзначила день народження',         'community-birthday', '2025-10-03', ['community-birthday','community-cake','community-dance','community-gathering']],
            ['Відкритий урок із природознавства у лісі',  'nature-lesson',      '2025-09-18', ['nature-lesson','nature-forest','nature-students','nature-teacher','nature-study']],
            ['Спільний будинок: традиції толоки',          'toloka-building',    '2025-08-25', ['toloka-building','toloka-workers','toloka-community','toloka-result']],
            ['Ранкова зарядка у парку: долучайтесь!',     'morning-exercise',   '2026-04-14', ['morning-exercise','morning-park','morning-people','morning-stretch']],
        ];

        $albumRecords = [];
        $photoRecords = [];

        foreach ($albumData as $i => $row) {
            [$title, $coverSeed, $date, $photos] = $row;
            $albumId = $i + 1;

            $albumRecords[] = [
                'id'         => $albumId,
                'slug'       => 'album-' . ($i + 1),
                'title'      => $title,
                'cover_seed' => $coverSeed,
                'album_date' => $date,
                'status'     => 'published',
                'created_at' => now()->subDays(count($albumData) - $i),
                'updated_at' => now()->subDays(count($albumData) - $i),
            ];

            foreach ($photos as $j => $seed) {
                $photoRecords[] = [
                    'album_id'   => $albumId,
                    'image_seed' => $seed,
                    'caption'    => null,
                    'sort_order' => $j,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }
        }

        DB::table('albums')->insert($albumRecords);
        DB::table('photos')->insert($photoRecords);
    }
}
