# magaziine: keyingi fazalar ro'yxati

## Hozirgi holat

| Faza | Mazmuni | Holat |
|---|---|---|
| 1–5 | Do'kon, rollar, blog, hamkor paneli | tayyor (hamkor panelida 2 ta eski test yiqiladi, 8-faza) |
| 6 | Support chat, mijoz tomoni | tayyor, 8 ta test yashil |
| 7A | Operator inbox, tayyor javoblar, moderatsiya navbati | kod berilgan; `ModerationQueueTest` natijasi hali yuborilmagan |
| 7B–7C | Audit log, bildirishnoma, dashboard, admin nazorati | rejada |
| 8–14 | Xato tuzatish, bosh sahifa dropdownlari, manzil/xarita | rejada (quyida) |
| 15–21 | Qo'shimcha g'oyalar | ixtiyoriy, qaysi biri kerakligini tanlang |

Tavsiya etilgan tartib: 8 → 7B → 7C → 9 → 10 → 11 → 12 → (13, 14) → ixtiyoriylar.
Xarita ishi 10 → 11 → 12 tartibida bo'lishi shart (har biri oldingisiga tayanadi).

Har fazani yuborishda quyidagi UMUMIY SHARTLAR blokini VAZIFA dan oldin qo'shing.

---

## UMUMIY SHARTLAR (har fazada bir xil)

1. Loyiha: Laravel 13, PHP 8.5, Sail (MySQL 8.4), Filament v5, Blade + Tailwind "neon" mavzu (layouts/app). Repo: abdumalik23102003/magaziine. Men macOS zsh da ishlayman, buyruqlar ./vendor/bin/sail orqali.
2. Javob formati: .sh skript fayl, ichida faqat cat heredoc: cat > yo'l <<'EOF' ... EOF. Mavjud faylni o'zgartirish kerak bo'lsa perl -0pi -e bilan literal anchor ishlat; anchor topilmasa skript xato bilan to'xtasin (perl dan oldin grep -qF bilan tekshir, chunki perl -i xatoda faylni buzishi mumkin). sed -i ishlatma. Shell bloklarida # izoh yozma (zsh). Skript qayta yurgizilsa ham ikki marta patch qilmasin (idempotent).
3. Boshlashdan oldin repo holatini o'qima, men kerakli fayllarni o'zim yuboraman. Bor narsani qayta yaratma, nomlarni mavjud kod bilan moslashtir. Taxmin qilgan nomlaringni (rol qiymatlari, ustun nomlari, factory nomlari) javob oxirida "Taxminlar" ro'yxatida yoz. Oldingi faza kodi yo'q bo'lsa, to'xta va ayt.
4. Struktura: nozik controller, mantiq app/Services, validatsiya FormRequest, enum app/Enums, ruxsat Policy, view resources/views/{pages,partials,components}, sahifa JS public/js/pages, pul App\Support\Money (sent), slug bilan route binding, Filament v5 (Schemas/Tables, recordActions).
5. Xavfsizlik: role mass-assign bo'lmasin, har amal Policy orqali, throttle, fayl yuklash validatsiyasi, Blade da {{ }}, JS da textContent.
6. Har fazaga PHPUnit testlar (RefreshDatabase, mavjud factory lardan foydalan) va oxirida buyruqlar: migrate, pint --dirty, test. Yurgizib bo'lmagan narsani "yurgizilmadi" deb yoz. Skriptni sintaksis tekshiruvidan (bash -n, php -l) o'tkaz.
7. Uzunlik: javob limitga sig'sin. Sig'masa A/B qismga bo'l va "A tugadi, B ni so'ra" de. Tushuntirish o'zbekcha, qisqa. Commit/push qilma.
8. Tashqi xizmat (geokoder, pochta) bo'lsa: real User-Agent/email .env orqali, timeout, xatolar Log::warning ga yoziladi (jim yutilmasin), testlarda Http::fake / Mail::fake / Notification::fake.

---

## FAZA 7B. Audit log va bildirishnomalar

VAZIFA (7A dagi inbox, canned replies va moderatsiya navbati bor deb hisobla):

1. Audit log: oddiy `audit_logs` jadvali (spatie/laravel-activitylog ishlatma, qo'shimcha paket kerak emas). Ustunlar: id, actor_id nullable (users), action (string, masalan role.changed), subject_type, subject_id, old_values json nullable, new_values json nullable, ip nullable, created_at. Indekslar: (subject_type, subject_id), (actor_id, created_at), action.
2. `App\Services\AuditLogger` (record(action, subject, old, new)). Parol, token, to'liq manzil va telefon kabi maydonlar yozilmasin (maskalash ro'yxati config da).
3. Yoziladigan hodisalar: rol o'zgarishi, foydalanuvchi faolsizlantirilishi/yoqilishi, mahsulot va post approve/reject (sabab bilan), buyurtma statusi o'zgarishi, suhbat transferi. Yozuv ProductService/PostService/OrderService ichidan emas, observer yoki servis chaqiruvi orqali; mavjud servislar imzosi o'zgarmasin.
4. Filament `ActivityResource`: faqat o'qish (create/edit/delete yo'q), faqat admin ko'radi, filtr: harakat turi, aktor, sana oralig'i; ro'yxatda old/new farqi.
5. Bildirishnomalar (ShouldQueue, queue driver = database): hamkor post/mahsulot yuborganda moderatorlarga Filament database notification; approve/reject bo'lganda hamkorga (database + mail, rad etishda sabab matni); yangi chat suhbati ochilganda operatorlarga. Filament panelda database notifications yoqilsin.
6. Mail matnlari ingliz yoki o'zbek (config orqali), shablon blade markdown mail.

Shartlar:
- Audit jadvaliga faqat qo'shish mumkin: modelda update/delete bloklangan, Policy da ham yo'q.
- Aktor yo'q holat (seeder, console) qo'llab-quvvatlansin.
- Notification xatosi asosiy amalni (approve/reject) bekor qilmasin.
- Sezgir ma'lumot (parol, remember_token, phone, address) old/new ga tushmasin.

Testlar: har hodisa uchun audit yozuvi paydo bo'ladi; ActivityResource faqat admin uchun ochiladi, operator/moderator 403; yozuvni tahrirlash/o'chirish mumkin emas; sezgir maydon maskalanadi; Notification::fake bilan to'g'ri qabul qiluvchilar (moderatorlar, hamkor, operatorlar), rad etish xabarida sabab bor; Mail::fake; Notification xatosida approve baribir o'tadi.

---

## FAZA 7C. Admin to'liq nazorati va rolga qarab dashboard

VAZIFA (7B bor deb hisobla):

1. UserResource kengaytmasi (rol bor bo'lsa faqat yetishmaganini qo'sh): rol berish, hamkorni brendga biriktirish, faolsizlantirish. Admin o'zini pasaytira/o'chira olmaydi (mavjud test shuni kutadi).
2. Admin uchun brend/post/mahsulot yaratish va tahrirlash: brend tanlash Select (barcha brendlar). Barcha suhbatlar (SupportInbox filtri) va barcha buyurtmalar admin uchun ochiq.
3. Dashboard rolga qarab (Filament widgetlar canView orqali):
   - admin: foydalanuvchilar, buyurtmalar, sotuv, navbat soni, ochiq suhbatlar;
   - moderator: navbat soni (mahsulot/post alohida) va oxirgi 7 kun approve/reject soni;
   - operator: ochiq suhbatlar, biriktirilmaganlar, o'rtacha birinchi javob vaqti (daqiqa);
   - hamkor: mavjud 5-faza widgetlari o'zgarmasin.
4. Hisob-kitoblar servisda (`DashboardStatsService`), widgetda emas; kesh 60 soniya.

Shartlar:
- Rol qiymatlari faqat `UserRole` enum dan, yangi rol qo'shilmasin.
- Har widget faqat o'z roli uchun ko'rinadi, boshqa rol URL orqali ham ko'ra olmaydi.
- Birinchi javob vaqti: mijoz birinchi xabaridan operator birinchi javobigacha; javobsiz suhbatlar hisobga olinmaydi.

Testlar: har rol faqat o'z widgetini ko'radi; admin o'zini pasaytira olmaydi; admin istalgan brendga mahsulot yaratadi; hamkor boshqa brendga yarata olmaydi; o'rtacha javob vaqti to'g'ri hisoblanadi (Carbon::setTestNow); keshni tozalash.

---

## FAZA 8. Hamkor paneli: 2 ta yiqilgan testni tuzatish

VAZIFA: `PartnerPanelTest` dagi ikki test (brand profile ro'yxati 302 qaytaradi; dashboard `/partner` da `Sales, last 30 days` ko'rinmaydi) sababini topib tuzat. Men `ListBrandProfiles.php`, `BrandProfileResource.php`, `PartnerStats.php`, `PartnerPanelProvider.php` fayllarini yuboraman.

Shartlar:
- Avval sababni isbotla (qaysi qator, nega), keyin tuzat. Testni yumshatib "o'tkazib yuborma".
- Agar ListBrandProfiles bitta brendda tahrir sahifasiga yo'naltirsa, qaror ber: testni o'zgartirish yoki xatti-harakatni o'zgartirish, va nima uchun ekanini yoz.
- Hamkor boshqa brend ma'lumotini ko'ra olmasligi o'zgarmasin.

Testlar: mavjud ikki test yashil; yangi regressiya testi (widget har doim panelda ro'yxatdan o'tgan).

---

## FAZA 9. Bosh sahifa: 2 ta dropdown (Sahifalar va Katalog)

VAZIFA (1–6 fazalar bor deb hisobla):

1. Headerda ikkita dropdown:
   - "Sahifalar": Bosh sahifa, Katalog, Brendlar, Blog, Istaklar, Savat, Buyurtmalarim, Profil, Yordam (chat). Login holatiga qarab ko'rinadigan bandlar o'zgaradi.
   - "Katalog": kategoriyalar (daraxt, 2 daraja), tanlangan brendlar (is_featured, eng ko'pi 8, "Barcha brendlar" havolasi), "Chegirmalar" havolasi, oxirgi 3 ta post.
2. `config/navigation.php` da "Sahifalar" bandlari (nom, route, auth sharti); "Katalog" ma'lumoti `NavigationService` dan, kesh bilan; kategoriya/brend/post o'zgarganda kesh tozalansin (mavjud brand carousel keshi uslubida).
3. Public JS: `public/js/dropdowns.js`: ochish/yopish, tashqariga bosganda yopilsin, Esc, Tab/strelka tugmalari, aria-expanded/aria-controls, bittasi ochilganda ikkinchisi yopilsin. Mobil ko'rinishda accordion.
4. Neon mavzuga mos, layouts/app headerida; mavjud HeaderActionsTest va LayoutTest buzilmasin.

Shartlar:
- Faqat faol brendlar va faol kategoriyalar; kutilayotgan (pending) mahsulotlar sanalmasin; sotuvda mahsuloti bo'lmagan kategoriya yashirin (yoki sozlama bilan).
- Hamma matn {{ }} bilan, havolalar route() orqali, slug bilan.
- JS holati yo'q bo'lsa ham havolalar ishlasin (progressive enhancement: `<details>` yoki oddiy ro'yxat zaxirasi).
- N+1 bo'lmasin: bitta so'rovda kategoriya daraxti.

Testlar: bandlar render bo'ladi; guest/auth bandlari farqi; nofaol brend/kategoriya ko'rinmaydi; pending mahsulot hisoblanmaydi; kesh brend o'zgarganda tozalanadi; XSS (nom ichida HTML) escape bo'ladi; so'rovlar soni chegaradan oshmaydi.

---

## FAZA 10. Manzil ma'lumotlar bazasi strukturasi

VAZIFA (checkout, orders, users bor deb hisobla). Xarita va O'zbekiston manzillari uchun struktura:

1. Yangi `addresses` jadvali (saqlangan manzillar): id, user_id (cascade), label (Uy, Ish...), recipient_name, phone, country_code (char 2, default UZ), region (viloyat/shahar), district (tuman), city, mahalla nullable, street, house, apartment nullable, entrance nullable, floor nullable, landmark nullable (mo'ljal), postal_code nullable, latitude decimal(10,7) nullable, longitude decimal(10,7) nullable, formatted_address text nullable, geo_provider nullable, geo_place_id nullable, geocoded_at nullable, is_default bool, timestamps. Indekslar: (user_id, is_default), (latitude, longitude).
2. `orders` ga yetkazib berish surati (snapshot) ustunlari: ship_country_code, ship_region, ship_district, ship_city, ship_mahalla, ship_street, ship_house, ship_apartment, ship_landmark, ship_postal_code, ship_latitude, ship_longitude, ship_formatted, ship_source (enum: map, manual). Mavjud `address` va `phone` ustunlari qoladi (eski buyurtmalar buzilmasin); yangi buyurtmada `address` yig'ma matn sifatida to'ldiriladi.
3. Modellar: Address (user policy), casts (decimal:7), `App\Support\GeoPoint` value object (lat, lng, validatsiya), `App\Enums\AddressSource`.
4. `config/delivery.php`: ruxsat etilgan hudud (O'zbekiston bbox), standart markaz (Toshkent), standart zoom.
5. Mavjud buyurtmalar uchun ship_* ustunlari nullable bo'lsin; backfill yo'q.

Shartlar:
- Hamkor panelida mijozning to'liq manzili, telefoni va koordinatasi ko'rinmasin (mavjud test: faqat shahar nomi). ship_city ruxsat etiladi, qolgani yo'q.
- Koordinata chegarasi: lat -90..90, lng -180..180, hamda config dagi bbox ichida.
- Mass-assignment: user_id, is_default faqat servis orqali.
- Migratsiya `down()` to'liq bo'lsin.

Testlar: migratsiya ustunlari bor (LayoutTest dagi kabi sxema testi); Address policy (boshqa user manzilini ko'ra/o'zgartira olmaydi); GeoPoint noto'g'ri qiymatni rad etadi; hamkor buyurtma ro'yxatida ship_street/koordinata ko'rinmaydi; eski buyurtma (ship_* null) sahifalarni buzmaydi.

---

## FAZA 11. Geokoding xizmati (o'zbekcha manzil)

VAZIFA (10-faza bor deb hisobla). Mavjud `LocationService` (header joylashuvi) ni buzmasdan, manzil uchun alohida xizmat:

1. `App\Contracts\Geocoder` interfeysi: reverse(GeoPoint): ?AddressData, search(string $q, int $limit): array. Birinchi implementatsiya: Nominatim (`accept-language=uz`, `addressdetails=1`, `countrycodes=uz` qidiruvda). Keyinchalik Yandex/Google ga almashtirish oson bo'lsin (config orqali).
2. `AddressData` DTO: region, district, city, mahalla, street, house, postal_code, country_code, formatted, lat, lng, place_id.
3. O'zbekcha moslashtirish (mapper): Nominatim kalitlarini maydonlarga: state/region → region, county/city_district/suburb → district, city/town/village → city, neighbourhood/quarter → mahalla, road/pedestrian → street, house_number → house. Toshkent shahri uchun (region = "Toshkent shahri") alohida qoida. Nom uzbekcha (lotin) bo'lsin: kirill kelsa lotinga o'giruvchi (`UzTransliterator`) ishlatilsin; "tumani", "viloyati" suffikslari bir xil shaklda.
4. Endpointlar (nozik controller, FormRequest): `POST /geo/reverse` (lat,lng), `GET /geo/search?q=`. Throttle: reverse 30/min, search 20/min. Javob JSON: maydonlar + `confidence` (to'liq/qisman).
5. Kesh: reverse 4 xonali koordinata bo'yicha, 24 soat; search 1 soat. Nominatim siyosati: 1 so'rov/soniya (server tomonidan rate limiter), real User-Agent.
6. Xatolar: timeout, 403/429, bo'sh javob alohida holat sifatida Log::warning ga yoziladi; foydalanuvchiga aniq xabar (422 "manzil topilmadi, qo'lda kiriting").

Shartlar:
- Hudud tekshiruvi: koordinata O'zbekiston bbox dan tashqarida bo'lsa reverse chaqirilmaydi, 422 qaytadi.
- Qidiruv matni uzunligi 3–120, sanitizatsiya.
- Tashqi javob to'g'ridan-to'g'ri frontga uzatilmaydi, faqat mapper natijasi.

Testlar (Http::fake, haqiqiy tarmoq yo'q): Toshkent, Samarqand, qishloq (village) namunalari to'g'ri maydonlarga tushadi; kirill nomi lotinga o'giriladi; 403/timeout/bo'sh javob 422 va log; kesh ikkinchi so'rovni tashqariga yubormaydi; throttle; bbox dan tashqari koordinata; search natijalari cheklangan va normallashtirilgan.

---

## FAZA 12. Checkout: xarita va avtomatik to'ldirish

VAZIFA (10 va 11-fazalar bor deb hisobla):

1. Checkout sahifasiga xarita (Leaflet + OpenStreetMap, API kalit kerak emas; Leaflet fayllari `public/vendor/leaflet` ga lokal, CDN emas). Neon mavzuga mos (qorong'i plitkalar yoki CSS filtr), attribution ko'rinib tursin (OSM talabi).
2. Xarita bilan ishlash: bosganda yoki marker sudraganda nuqta belgilanadi → `/geo/reverse` chaqiriladi → forma maydonlari o'zbekcha avtomatik to'ldiriladi (viloyat, tuman, shahar, mahalla, ko'cha, uy, indeks, mo'ljal). "Mening joylashuvim" tugmasi (geolocation). Qidiruv maydoni (debounce 600 ms, natijani tanlasa xarita shu yerga o'tadi).
3. Maydonlar tahrirlanadi: foydalanuvchi qo'lda o'zgartirsa `ship_source` = manual, o'zgartirmasa map. Xarita ishlamasa (JS o'chiq, geokoder xato) forma qo'lda to'ldiriladigan oddiy holatda qoladi.
4. Server tomoni: `CheckoutRequest` yangi maydonlarni validatsiya qiladi (ship_* , lat/lng bbox ichida), `OrderService` buyurtmaga snapshot sifatida yozadi, `address` yig'ma matni avtomatik yasaladi. Yetkazib berish narxi mavjud mantiqda qoladi.
5. Kirgan foydalanuvchi uchun "Manzilni saqlash" belgisi (10-fazadagi addresses ga yozadi) va saqlangan manzildan tanlash.
6. Holatlar: yuklanmoqda, topilmadi, hudud tashqarisida (xabar o'zbekcha), xato va qayta urinish. Hamma matn textContent bilan.

Shartlar:
- Koordinata va maydonlarga ishonma: serverda qayta tekshir (bbox, uzunlik, majburiy maydonlar), xarita bo'lmasa ham buyurtma qo'lda manzil bilan o'tsin.
- Mavjud CheckoutTest buzilmasin (eski maydonlar bilan ham o'tadi yoki testlar moslashtiriladi va sababi yoziladi).
- Nominatim siyosati: foydalanuvchi yozayotganda emas, tanlaganda so'rov.
- Mobil qurilmada xarita balandligi va scroll to'qnashmasin (xarita ichida sahifa scroll bloklanmasin).
- Telefon va to'liq manzil hamkorga ko'rinmasligi saqlansin.

Testlar: checkout sahifasida xarita konteyneri va konfiguratsiya bor; buyurtma ship_* snapshot bilan yaratiladi; `address` yig'ma matni; bbox dan tashqari koordinata 422; manual manzil (koordinatasiz) ham o'tadi; ship_source to'g'ri yoziladi; saqlangan manzil boshqa userniki bo'lsa rad etiladi; hamkor buyurtmasida manzil ko'rinmaydi; JS faylida innerHTML yo'q.

---

## FAZA 13. Yetkazib berish zonalari va narxi (ixtiyoriy)

VAZIFA (12-faza bor deb hisobla):

1. `delivery_zones` jadvali: nom, tur (radius/poligon), markaz lat/lng + radius_km yoki GeoJSON poligon, fee (sent), free_from (sent) nullable, is_active, sort.
2. `DeliveryZoneService`: nuqta qaysi zonaga tushishini aniqlaydi (radius: haversine; poligon: ray casting), narxni qaytaradi. Zona yo'q bo'lsa "bu hududga yetkazilmaydi".
3. Filament `DeliveryZoneResource` (faqat admin). Checkout xaritasida zona chegarasi chiziladi va narx yangilanadi.
4. Mavjud yetkazib berish narxi/bepul chegara mantig'i (config) zona yo'q bo'lganda zaxira bo'lib qoladi.

Shartlar: pul faqat sentda, App\Support\Money orqali; zona tekshiruvi serverda ham bajariladi (frontga ishonma).

Testlar: radius ichida/tashqarida; poligon ichida/tashqarida/chegarada; bepul chegara; nofaol zona; zona yo'q holatda zaxira narx; admin bo'lmagan resursni ocha olmaydi.

---

## FAZA 14. Profilda saqlangan manzillar (ixtiyoriy)

VAZIFA (10 va 12-faza bor deb hisobla): profil sahifasida manzillar ro'yxati, qo'shish/tahrirlash/o'chirish (xarita bilan, 12-fazadagi komponent qayta ishlatiladi), asosiy manzilni belgilash, checkoutda asosiy manzil avtomatik tanlansin.

Shartlar: AddressPolicy, bitta foydalanuvchida faqat bitta is_default, maksimum 10 manzil (config), FormRequest, throttle.

Testlar: CRUD faqat o'zniki; ikkinchi default avvalgisini o'chiradi; limit; boshqa user manziliga 403/404; checkout default manzil bilan to'ldiriladi.

---

## Ixtiyoriy fazalar (qaysi biri kerakligini tanlang)

### FAZA 15. Email tasdiqlash va buyurtma xati
MustVerifyEmail, tasdiqlanmagan foydalanuvchi checkout qila olmasligi (sozlama bilan), buyurtma tasdiqlash xati (queue), xat ichida buyurtma tarkibi va yetkazib berish shahri (to'liq manzilsiz). Testlar: Mail::fake, tasdiqlash oqimi, tasdiqlanmagan checkout rad etiladi, xatda maxfiy ma'lumot yo'q.

### FAZA 16. Mahsulot sharhlari (moderatsiya bilan)
Faqat mahsulotni sotib olgan foydalanuvchi, 1–5 baho + matn (max 1000), moderator navbatiga tushadi (7A navbat kengaytiriladi), tasdiqlangandan keyin ko'rinadi, o'rtacha baho keshlanadi. Testlar: faqat xaridor yoza oladi, bitta mahsulotga bitta sharh, pending ko'rinmaydi, XSS escape, o'rtacha baho.

### FAZA 17. Hamkor komissiyasi va hisobot
Brend yoki hamkor bo'yicha komissiya foizi, har buyurtma qatoriga komissiya surati, hamkor panelida oylik hisobot va CSV yuklash, admin uchun umumiy hisobot. Pul faqat sentda. Testlar: komissiya hisobi, bekor qilingan buyurtma kirmaydi, hamkor faqat o'z hisobotini ko'radi, CSV tarkibi.

### FAZA 18. Shikoyat (report) tugmasi
Mahsulot, post va sharhga "Shikoyat" tugmasi, sabab turi + izoh, moderator navbatida alohida bo'lim, throttle va takroriy shikoyatdan himoya. Testlar: guest/auth, takror yo'q, moderator ko'radi, navbatdan yopish.

### FAZA 19. Brendga obuna (follow)
Foydalanuvchi brendga obuna bo'ladi, profilda obunalar ro'yxati, brendda yangi post chiqqanda (approved) obunachilarga bildirishnoma (queue). Testlar: toggle, faqat kirgan foydalanuvchi, post tasdiqlanganda bildirishnoma ketadi, pending da ketmaydi.

### FAZA 20. CSV eksport (admin)
Buyurtmalar, foydalanuvchilar (maxfiy maydonlarsiz), mahsulotlar uchun Filament eksport; katta jadval uchun chunk; audit logga "eksport qilindi" yozuvi. Testlar: faqat admin, ustunlar, maxfiy maydon yo'q, audit yozuvi.

### FAZA 21. Support chat qo'shimchalari
Suhbat oxirida mijoz bahosi (CSAT, 1–5), "operator yozmoqda" belgisi, Laravel Reverb ga o'tish (MessageSent eventi tayyor, `routes/channels.php` da `support.conversation.{id}` private kanal avtorizatsiyasi, polling zaxira bo'lib qoladi), suhbat transkriptini emailga yuborish. Testlar: kanal avtorizatsiyasi (faqat egasi va operator), baho faqat yopilgan suhbatga, broadcast config orqali yoqiladi/o'chadi.

---

## Eslatmalar

- Xarita ishi uchun hozir Nominatim 403 xatosi tuzatilgan bo'lishi kerak (`LOCATION_USER_AGENT` da haqiqiy email). 11-fazada alohida manzil geokoderi uchun ham shu User-Agent ishlatiladi.
- O'zbekiston manzillari OSM da har joyda to'liq emas: mahalla va uy raqami ko'p holatda bo'sh keladi. Shuning uchun 12-fazada maydonlar doim qo'lda tahrirlanadi va majburiy maydonlar faqat shahar/tuman va ko'cha yoki mo'ljal.
- Yandex Maps yoki Google Maps ga o'tmoqchi bo'lsangiz (aniqroq O'zbekiston ma'lumoti, lekin API kalit va to'lov kerak), 11-fazadagi `Geocoder` interfeysi shu uchun mo'ljallangan.
