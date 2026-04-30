<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class ShopProductSeeder extends Seeder
{
    public function run(): void
    {
        $testNicknames = ['seed_vasyl', 'seed_halyna', 'seed_mykola', 'seed_iryna', 'seed_petro'];

        // Cascade-delete via FK: purchase_requests → products → shops → users
        DB::table('users')->whereIn('nickname', $testNicknames)->delete();

        $userData = [
            ['last_name' => 'Коваленко', 'first_name' => 'Василь',  'patronymic' => 'Іванович',    'street' => 'вул. Центральна, 1', 'nickname' => 'seed_vasyl',  'phone' => '0671112233'],
            ['last_name' => 'Мельник',   'first_name' => 'Галина',  'patronymic' => 'Петрівна',    'street' => 'вул. Шкільна, 5',    'nickname' => 'seed_halyna', 'phone' => '0504445566'],
            ['last_name' => 'Бондар',    'first_name' => 'Микола',  'patronymic' => 'Олексійович', 'street' => 'вул. Лісова, 12',    'nickname' => 'seed_mykola', 'phone' => '0667778899'],
            ['last_name' => 'Савченко',  'first_name' => 'Ірина',   'patronymic' => 'Василівна',   'street' => 'вул. Польова, 8',    'nickname' => 'seed_iryna',  'phone' => '0932223344'],
            ['last_name' => 'Лісовий',   'first_name' => 'Петро',   'patronymic' => 'Миколайович', 'street' => 'вул. Садова, 3',     'nickname' => 'seed_petro',  'phone' => '0673210011'],
        ];

        $userIds = [];
        foreach ($userData as $u) {
            $userIds[] = DB::table('users')->insertGetId(array_merge($u, [
                'password'   => Hash::make('password'),
                'is_admin'   => false,
                'created_at' => now(),
                'updated_at' => now(),
            ]));
        }

        $shopData = [
            ['name' => 'Господарський кут',   'description' => 'Товари для дому та городу. Якісно та недорого.'],
            ['name' => 'Галинина крамниця',   'description' => 'Домашня випічка, варення, соління — все своє.'],
            ['name' => 'Майстерня Миколи',    'description' => 'Вироби з дерева, ремонт меблів, різьблення.'],
            ['name' => 'Квіти від Ірини',     'description' => 'Кімнатні та садові рослини, розсада, букети.'],
            ['name' => 'Петрів куток',         'description' => 'Сільськогосподарська продукція, м\'ясо, яйця.'],
        ];

        $shopIds = [];
        foreach ($shopData as $i => $s) {
            $shopIds[] = DB::table('shops')->insertGetId(array_merge($s, [
                'user_id'    => $userIds[$i],
                'created_at' => now(),
                'updated_at' => now(),
            ]));
        }

        // 20 products per shop = 100 total
        $productTitles = [
            // Shop 0 — Господарський кут (household / garden)
            ['Лопата штикова сталева', 'Відро оцинковане 10 л', 'Шланг поливальний 20 м', 'Граблі залізні', 'Сапка городня',
             'Ножиці садові', 'Мотузка нейлонова 50 м', 'Рукавиці робочі (пара)', 'Фарба для парканів 1 кг', 'Цвяхи будівельні 1 кг',
             'Молоток столярний', 'Рулетка 5 м', 'Мастило WD-40', 'Насос ножний для коліс', 'Ліхтарик LED',
             'Ящик для інструментів', 'Шурупи по дереву набір', 'Замок навісний', 'Сітка для курника 10 м', 'Піддон пластиковий'],
            // Shop 1 — Галинина крамниця (home food)
            ['Варення з полуниці 0,5 л', 'Мед лісовий 1 кг', 'Соління огірків 3 л', 'Квашена капуста 1 кг', 'Домашнє масло 500 г',
             'Яблучний оцет домашній', 'Сушені гриби 200 г', 'Варення з аличі 0,5 л', 'Томатна паста домашня', 'Пиріг з капустою',
             'Хліб житній домашній', 'Бублики з маком 6 шт', 'Пряники медові 500 г', 'Сметана домашня 400 г', 'Творог свіжий 500 г',
             'Молоко козяче 1 л', 'Яйця курячі 10 шт', 'Сало солоне 500 г', 'Ковбаса домашня 1 кг', 'Копчений карась'],
            // Shop 2 — Майстерня Миколи (woodwork)
            ['Полиця настінна дерев\'яна', 'Рамка для фото 20×30', 'Табурет дерев\'яний', 'Кошик з лози', 'Дерев\'яна ложка різьблена',
             'Підставка під квіти', 'Дошка обробна велика', 'Скринька для прикрас', 'Вішалка настінна', 'Рамка для дзеркала',
             'Ліжко дерев\'яне (під замовлення)', 'Столик журнальний', 'Лавка садова', 'Секція паркану дерев\'яна', 'Скринька для саду',
             'Іграшковий будиночок', 'Підставка для ножів', 'Годинникова рамка', 'Меблі для лялькового будинку', 'Дерев\'яний пазл'],
            // Shop 3 — Квіти від Ірини (plants)
            ['Герань садова', 'Троянда плетиста', 'Фіалка кімнатна', 'Суниця ремонтантна (розсада)', 'Кактус у горщику',
             'Розсада помідорів', 'Розсада болгарського перцю', 'Розсада капусти', 'Монстера кімнатна', 'Хлорофітум',
             'Плющ ампельний', 'Алое вера', 'Лаванда садова', 'Чорнобривці (розсада)', 'Бархатці жовті',
             'Петунія ампельна', 'Калія кімнатна', 'Бегонія клубнева', 'Цибулини гіацинту', 'Цибулини нарцисів'],
            // Shop 4 — Петрів куток (farm produce)
            ['Свинина домашня 1 кг', 'Куряче м\'ясо домашнє 1 кг', 'Яйця домашні 20 шт', 'Молоко коров\'яче 2 л', 'Картопля молода 1 кг',
             'Цибуля ріпчаста 2 кг', 'Часник 500 г', 'Морква свіжа 1 кг', 'Буряк 1 кг', 'Гарбуз 3–4 кг',
             'Петрушка пучок', 'Кріп свіжий пучок', 'Салат листовий', 'Огірки свіжі 1 кг', 'Помідори 1 кг',
             'Перець солодкий 500 г', 'Кабачки 2 шт', 'Квасоля суха 500 г', 'Горох лущений 500 г', 'Кукурудза 3 качани'],
        ];

        $descriptions = [
            'Відмінна якість, власного виробництва.',
            'Свіже, натуральне, без добавок.',
            'Ручна робота, довговічне.',
            'Доглянуте, готове до використання.',
            'З власного господарства, є в наявності.',
            'Замовлення за телефоном, самовивіз.',
            'Можлива доставка по селу.',
            'Свіже щотижня, постійний продаж.',
        ];

        $imageSeeds = [
            ['shovel-tool', 'bucket-tool', 'garden-hose', 'rake-garden', 'hoe-garden', 'scissors-garden', 'rope-craft', 'gloves-work', 'fence-paint', 'nails-box',
             'hammer-tool', 'tape-measure', 'wd40-spray', 'pump-tire', 'flashlight-led', 'toolbox-red', 'screws-set', 'padlock-door', 'chicken-wire', 'plastic-tray'],
            ['strawberry-jam', 'honey-jar', 'pickled-cucumbers', 'sauerkraut', 'butter-homemade', 'apple-vinegar', 'dried-mushrooms', 'plum-jam', 'tomato-paste', 'cabbage-pie',
             'rye-bread', 'bagels-poppy', 'honey-cookies', 'sour-cream', 'cottage-cheese', 'goat-milk', 'chicken-eggs', 'salted-lard', 'homemade-sausage', 'smoked-fish'],
            ['wooden-shelf', 'photo-frame-wood', 'wooden-stool', 'wicker-basket', 'carved-spoon', 'flower-stand', 'cutting-board', 'jewelry-box', 'wall-rack', 'mirror-frame',
             'wooden-bed', 'coffee-table', 'garden-bench', 'wooden-fence-plank', 'garden-chest', 'toy-house-wood', 'knife-block', 'clock-frame', 'doll-furniture', 'wood-puzzle'],
            ['geranium-flower', 'climbing-rose', 'violet-purple', 'strawberry-plant', 'cactus-small', 'tomato-seedling', 'pepper-seedling', 'cabbage-seedling', 'monstera-leaf', 'chlorophytum-pot',
             'ivy-hanging', 'aloe-plant', 'lavender-bush', 'marigold-orange', 'tagetes-yellow', 'petunia-pink', 'calla-white', 'begonia-red', 'hyacinth-bulb', 'narcissus-field'],
            ['pork-fresh', 'chicken-farm', 'eggs-basket', 'cow-milk-fresh', 'new-potatoes', 'onion-fresh', 'garlic-head', 'carrots-bunch', 'beet-pile', 'pumpkin-orange',
             'parsley-fresh', 'dill-herbs', 'lettuce-green', 'cucumbers-garden', 'tomatoes-harvest', 'bell-pepper-red', 'zucchini-green', 'beans-dry', 'peas-pod', 'corn-fresh'],
        ];

        $prices = [
            [150, 120, 350,  80,  90, 180, 200,  60, 280,  45, 180, 120,  95, 150,  85, 320,  55, 180, 250,  75],
            [ 85, 320,  95,  75, 180,  65, 140,  90,  80,  95, 120,  65, 180,  95, 120,  85,  60, 180, 350, 120],
            [650, 280,1200, 450, 180, 380, 320, 750, 420, 580,8500,2800,1900,1200, 680, 850, 320, 480,1200, 380],
            [ 45, 180,  35,  40,  25,  30,  25,  30, 580,  45,  35,  45, 120,  35,  30,  25,  25,  30,  55,  55],
            [350, 320, 120,  80,  45,  35,  95,  30,  25,  85,  15,  15,  20,  55,  60,  40,  30,  60,  55,  30],
        ];

        $productsByShop = [];
        for ($shopIdx = 0; $shopIdx < 5; $shopIdx++) {
            $productsByShop[$shopIdx] = [];
            for ($j = 0; $j < 20; $j++) {
                $seed = $imageSeeds[$shopIdx][$j];
                $id = DB::table('products')->insertGetId([
                    'shop_id'     => $shopIds[$shopIdx],
                    'title'       => $productTitles[$shopIdx][$j],
                    'description' => $descriptions[($shopIdx * 4 + $j) % count($descriptions)],
                    'price'       => $prices[$shopIdx][$j],
                    'photo_path'  => 'https://picsum.photos/seed/' . $seed . '/400/400',
                    'created_at'  => now()->subDays(rand(1, 60)),
                    'updated_at'  => now()->subDays(rand(0, 5)),
                ]);
                $productsByShop[$shopIdx][] = $id;
            }
        }

        $messages = [
            'Цікавить ціна та доставка. Є в наявності?',
            'Хочу купити, можна зв\'язатися?',
            null,
            'Чи є ще? Потрібно два екземпляри.',
            'Скільки коштує з доставкою?',
            null,
            'Дуже цікавить, телефонуйте.',
            'Чи свіже? Хочу взяти для сім\'ї.',
            null,
        ];

        $requestRecords = [];
        $used = [];

        for ($shopIdx = 0; $shopIdx < 5; $shopIdx++) {
            $otherShops = array_values(array_filter([0, 1, 2, 3, 4], fn($x) => $x !== $shopIdx));
            $requestCount = 0;
            $shuffledProds = $productsByShop[$shopIdx];
            shuffle($shuffledProds);

            foreach ($shuffledProds as $productId) {
                if ($requestCount >= 8) break;
                shuffle($otherShops);
                $numBuyers = rand(1, 2);
                foreach (array_slice($otherShops, 0, $numBuyers) as $buyerShopIdx) {
                    $buyerId = $userIds[$buyerShopIdx];
                    $key = $productId . '_' . $buyerId;
                    if (isset($used[$key])) continue;
                    $used[$key] = true;
                    $requestRecords[] = [
                        'product_id' => $productId,
                        'buyer_id'   => $buyerId,
                        'message'    => $messages[array_rand($messages)],
                        'created_at' => now()->subDays(rand(0, 30)),
                        'updated_at' => now()->subDays(rand(0, 30)),
                    ];
                    $requestCount++;
                    if ($requestCount >= 8) break;
                }
            }
        }

        if (!empty($requestRecords)) {
            DB::table('purchase_requests')->insert($requestRecords);
        }
    }
}
