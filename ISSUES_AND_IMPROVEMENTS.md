# گزارش مشکلات، حالات استثنایی و پیشنهادات بهبود — افزونه Co-Publisher Manager

> **تاریخ تولید:** 2026-06-22
> **نسخه افزونه:** 1.0
> **تحلیلگر:** AI Senior WordPress Developer

---

## فهرست مطالب

1. [باگ‌های بحرانی (Critical Bugs)](#1-باگ‌های-بحرانی-critical-bugs)
2. [آسیب‌پذیری‌های امنیتی (Security Vulnerabilities)](#2-آسیب‌پذیری‌های-امنیتی-security-vulnerabilities)
3. [مشکلات منطقی و معماری (Logic & Architecture Issues)](#3-مشکلات-منطقی-و-معماری-logic--architecture-issues)
4. [حالات استثنایی و Edge Cases](#4-حالات-استثنایی-و-edge-cases)
5. [مشکلات کیفیت کد (Code Quality Issues)](#5-مشکلات-کیفیت-کد-code-quality-issues)
6. [پیشنهادات ارتقا و بهبود (اولویت‌بندی شده)](#6-پیشنهادات-ارتقا-و-بهبود-اولویت‌بندی-شده)
7. [لاگ تغییرات و فعالیت‌ها (Changelog)](#7-لاگ-تغییرات-و-فعالیت‌ها-changelog)

---

## 1. باگ‌های بحرانی (Critical Bugs)

> [!CAUTION]
> موارد زیر باگ‌های فعال هستند که باعث خطای PHP یا رفتار نادرست در محیط عملیاتی (Production) می‌شوند.

| # | فایل | خط | شرح مشکل | تأثیر | وضعیت |
|---|-------|-----|-----------|-------|-------|
| BUG-001 | `inc/helper_functions.php` | 2 | **خطای نحوی (Syntax Error) در کامنت:** عبارت `error_log('i am server, subscription ' . )` به صورت ناقص داخل یک کامنت PHP قرار گرفته. به صورت خط اول کامنت است و خوشبختانه به دلیل شروع با `//` کل خط کامنت شده و Fatal Error ایجاد نمی‌کند، اما قطعه کد هرز محسوب می‌شود. | 🟡 **متوسط** — کد هرز در کامنت | `حل شده` |
| BUG-002 | `inc/helper_functions.php` | — | **بررسی شرطی در `cop_plans_list_dropdown`:** در کد فعلی ساختار صحیح است و شرط مخرب یا خطای تعریف نشده وجود ندارد. | 🟢 **بدون خطا** — اصلاح شده | `حل شده` |
| BUG-003 | `inc/restapi.php` | — | **فراخوانی دوگانه `check_subscription_existence`:** در نسخه فعلی تابع فقط یک‌بار در خط 33 فراخوانی و در متغیر ذخیره شده و در خط 39 بررسی می‌شود. عملگر انتساب نیز در شرط وجود ندارد. | 🟢 **بدون خطا** — اصلاح شده | `حل شده` |
| BUG-004 | `inc/restapi.php` | 56–64 | **استفاده از متغیرهای تعریف‌نشده:** اگر `$plan_data` در خط 41 مقدار `false` برگرداند، متغیرهای `$plan_name`، `$plan_duration`، `$plan_cron_interval` و `$plan_max_post_fetch` هرگز مقداردهی نمی‌شوند اما در خطوط 57–62 استفاده شده‌اند. | 🔴 **بحرانی** — Undefined Variable Notice و داده خالی در پاسخ API | `حل شده` |
| BUG-005 | `inc/helper_functions.php` | — | **اسکریپت Select2 Multi-select:** هندل ناموجود `select2-multiple-js` اصلاح شده و اکنون از هندل تعریف شده `'select2-js'` استفاده می‌شود. اما همچنان فراخوانی تابع enqueue در بدنه رندر متاباکس از نظر معماری غیر استاندارد است. | 🟡 **متوسط** — بهبود معماری لود اسکریپت | `باز` |
| BUG-006 | `inc/helper_functions.php` | 297 | **فراخوانی تابع تعریف‌نشده:** تابع `get_subscription_resources_data` در خط 297 تابع `get_resources_data` (با حرف s) را فراخوانی می‌کند، اما تابع تعریف شده `get_resource_data` (بدون حرف s) است. | 🔴 **بحرانی** — Fatal Error در صورت فراخوانی | `حل شده` |
| BUG-007 | `post-types/subscriptions.php` | 11 | **فراخوانی بیهوده `generate_secret_code(16)` در هر بارگذاری:** در تابع ثبت پست تایپ `subscriptions_post_type` که روی هوک `init` اجرا می‌شود، تابع `generate_secret_code(16)` فراخوانی شده بدون اینکه خروجی آن ذخیره یا استفاده شود. | 🟡 **متوسط** — اتلاف منابع | `باز` |

---

## 2. آسیب‌پذیری‌های امنیتی (Security Vulnerabilities)

> [!WARNING]
> موارد زیر ریسک‌های امنیتی شناسایی شده هستند که باید در اولویت رفع قرار گیرند.

| # | فایل | خط | شرح آسیب‌پذیری | سطح ریسک | نوع | وضعیت |
|---|-------|-----|----------------|----------|------|-------|
| SEC-001 | `inc/restapi.php` | 9 | **REST API بدون احراز هویت:** مقدار `permission_callback` برابر `__return_true` قرار داده شده، به این معنا که هر کسی بدون هیچ محدودیتی می‌تواند endpoint اعتبارسنجی را صدا بزند. مهاجم می‌تواند با ارسال تعداد زیادی درخواست، حمله Brute-force روی کدهای لایسنس انجام دهد. (یک لایه Rate Limit با آی‌پی برای محافظت افزوده شد). | 🔴 **بالا** | Brute-force / DoS | `حل شده` |
| SEC-002 | `inc/restapi.php` | 24–25 | **لاگ کردن کد لایسنس در error_log:** کد لایسنس محرمانه (`subscription_secret_code`) در فایل لاگ سرور ذخیره می‌شود. هر کسی با دسترسی خواندن لاگ‌ها می‌تواند لایسنس‌ها را مشاهده کند. | 🔴 **بالا** | Information Disclosure | `باز` |
| SEC-003 | `post-types/resources.php` | 133 | **لاگ دیباگ در محیط عملیاتی:** مقدار فیلد `need_to_merge_guid_link` در هر بارگذاری صفحه ویرایش لاگ می‌شود. | 🟡 **متوسط** | Information Disclosure | `باز` |
| SEC-004 | تمام فایل‌های `save_*` | — | **عدم استفاده از Nonce Verification:** هیچ‌کدام از توابع ذخیره‌سازی meta box ها (`save_custom_meta_box`، `save_plans_custom_meta_box`، `save_subscriptions_custom_meta_box`) از `wp_nonce_field` و `wp_verify_nonce` استفاده نمی‌کنند. این باعث آسیب‌پذیری CSRF می‌شود. | 🔴 **بالا** | CSRF | `حل شده` |
| SEC-005 | تمام فایل‌های `save_*` | — | **عدم بررسی Post Type در ذخیره‌سازی:** توابع `save_custom_meta_box` و `save_plans_custom_meta_box` روی هوک `save_post` بدون بررسی نوع پست ثبت شده‌اند. یعنی هنگام ذخیره **هر نوع پستی** (مقاله عادی، صفحه، …) این توابع اجرا می‌شوند و تلاش می‌کنند متادیتاهای نامربوط ذخیره کنند. | 🟠 **بالا** | Data Integrity | `حل شده` |
| SEC-006 | `post-types/subscriptions.php` | 148 | **عدم پاکسازی URL اشتراک:** مقدار `subscription_site_url` بدون `esc_url_raw()` یا `sanitize_url()` مستقیماً ذخیره می‌شود. | 🟠 **بالا** | Stored XSS / Injection | `باز` |
| SEC-007 | `post-types/subscriptions.php` | 124 | **عدم Escape خروجی `subscription_extra_days`:** مقدار `$subscription_extra_days` بدون `esc_attr()` مستقیماً در HTML چاپ شده. | 🟡 **متوسط** | Reflected XSS | `باز` |
| SEC-008 | `inc/helper_functions.php` | 147 | **استفاده از `rand()` به جای `random_int()`:** تابع `generate_secret_code` از `rand()` استفاده می‌کند که قابل پیش‌بینی (Predictable) است و برای تولید کدهای امنیتی مناسب نیست. | 🟠 **بالا** | Weak Cryptography | `باز` |

---

## 3. مشکلات منطقی و معماری (Logic & Architecture Issues)

| # | شرح مشکل | فایل(های) مرتبط | تأثیر |
|---|-----------|------------------|-------|
| ARCH-001 | **عدم بررسی Post Type در هوک `save_post`:** سه تابع مجزا (`save_custom_meta_box`، `save_plans_custom_meta_box`، `save_subscriptions_custom_meta_box`) بدون فیلتر کردن نوع پست، همگی روی هوک `save_post` ثبت شده‌اند. هر بار ذخیره‌سازی هر پستی، هر سه تابع اجرا می‌شوند. | `resources.php` ، `plans.php` ، `subscriptions.php` | عملکرد پایین، نوشتن احتمالی متادیتای نامربوط |
| ARCH-002 | **تکرار enqueue اسکریپت Select2:** کتابخانه Select2 در سه تابع مجزا (`cop_list_users_dropdown`، `cop_plans_list_dropdown`، `cop_resources_list_dropdown`) هر بار enqueue می‌شود. باید یک بار مرکزی لود شود. | `helper_functions.php` | لود چندگانه و بهینه‌نبودن |
| ARCH-003 | **دسترسی غیرمستقیم به متادیتا:** در تابع `get_subscription_data` از `$subscription->post->subscription_user_id` استفاده شده. وردپرس به صورت پیش‌فرض متادیتا را از طریق property های magic پشتیبانی نمی‌کند مگر `custom-fields` در supports فعال باشد. استفاده از `get_post_meta()` روش صحیح و مطمئن‌تر است. | `helper_functions.php` (خطوط 214–222) | خطای صامت (Silent Bug) در محیط‌های خاص |
| ARCH-004 | **عدم استفاده از `wp_reset_postdata()`:** پس از حلقه‌های `WP_Query` در توابع دراپ‌داون و بازیابی داده‌ها، هرگز `wp_reset_postdata()` صدا زده نمی‌شود. | `helper_functions.php` | تداخل با لوپ‌های وردپرس و رفتار غیرمنتظره |
| ARCH-005 | **نام‌گذاری ناسازگار توابع:** برخی توابع بدون پیشوند هستند (مانند `custom_meta_box`، `generate_secret_code`، `validate_license`) و ممکن است با سایر افزونه‌ها تداخل ایجاد کنند. | سراسری | تداخل نام توابع (Function Collision) |
| ARCH-006 | **غلط املایی در نام تابع:** تابع `resouces_post_type` در `resources.php` غلط املایی دارد (صحیح: `resources`). | `resources.php` خط 3 | مشکل خوانایی و نگهداری |
| ARCH-007 | **نام‌گذاری ناسازگار `third-width`:** در هر دو فایل `plans.php` و `subscriptions.php` کلاس CSS `third-width` تعریف شده اما مقدار آنها متفاوت است (33.33% در plans و 25% در subscriptions). این نام‌گذاری گمراه‌کننده است. | `plans.php` ، `subscriptions.php` | سردرگمی توسعه‌دهنده |
| ARCH-008 | **تکرار استایل‌های inline:** استایل‌های CSS فرم‌ها (`form-flex-container`, `half-width`, ...) در هر دو فایل `plans.php` و `subscriptions.php` تکرار شده‌اند و باید در یک فایل CSS مرکزی قرار گیرند. | `plans.php` ، `subscriptions.php` | بدهی فنی و نگهداری سخت |

---

## 4. حالات استثنایی و Edge Cases

> [!IMPORTANT]
> سناریوهایی که ممکن است در عملکرد واقعی رخ دهند اما مدیریت نشده‌اند.

| # | سناریو | محل وقوع | تأثیر احتمالی |
|---|--------|-----------|---------------|
| EDGE-001 | **حذف یا پیش‌نویس شدن یک پلن در حالی که اشتراک‌های فعال به آن وابسته هستند.** تابع `get_plan_data` فقط پلن‌های `publish` را می‌خواند. اگر پلن به پیش‌نویس تغییر یابد، تمام اشتراک‌های مرتبط با آن بدون هیچ هشداری از کار می‌افتند. | `helper_functions.php` → `get_plan_data` | لایسنس‌های فعال بدون دلیل ظاهری نامعتبر می‌شوند |
| EDGE-002 | **حذف یک منبع خبری از وردپرس.** آرایه `subscription_resources_ids` همچنان شامل ID منبع حذف‌شده خواهد بود اما `get_resource_data` آن را پیدا نمی‌کند. نتیجه ممکن است آرایه ناقص یا خالی باشد. | `helper_functions.php` → `get_resource_data` | ارسال داده ناقص به کلاینت |
| EDGE-003 | **ارسال URL دامنه بدون trailing slash یا با پروتکل متفاوت:** اگر در هنگام ثبت `https://example.com/` ذخیره شود اما کلاینت `https://example.com` (بدون `/`) ارسال کند، مقایسه `=` در meta_query مطابقت نمی‌یابد و لایسنس نامعتبر گزارش می‌شود. (با پیاده‌سازی مقایسه نرمال‌سازی شده در PHP برطرف شد). | `helper_functions.php` → `check_subscription_existence` | لایسنس معتبر رد می‌شود (حل شده) |
| EDGE-004 | **مقدار خالی `plan_duration`:** اگر ادمین فیلد مدت پلن را خالی بگذارد، `strtotime` با مقدار خالی رفتار نادرستی دارد و تاریخ انقضا اشتباه محاسبه می‌شود. | `helper_functions.php` خط 184 ، `restapi.php` خط 50 | اشتراک بلافاصله منقضی یا بی‌نهایت فعال شود |
| EDGE-005 | **`subscription_resources_ids` خالی یا غیر-آرایه‌ای:** اگر هیچ منبعی انتخاب نشود، مقدار این فیلد `""` یا `null` خواهد بود. اما `get_resource_data` آن را مستقیماً به `post__in` پاس می‌دهد که باعث بازگرداندن تمام منابع (بدون فیلتر) می‌شود. | `restapi.php` → `get_resource_data` | نشت اطلاعات: ارسال تمام منابع به کلاینت غیرمجاز |
| EDGE-006 | **درخواست‌های همزمان (Race Condition) هنگام تولید کد لایسنس:** اگر دو مدیر همزمان یک اشتراک جدید ثبت کنند، ممکن است لایسنس تولید شده تکراری باشد (به دلیل استفاده از `rand()`). | `helper_functions.php` → `generate_secret_code` | تداخل لایسنس‌ها |
| EDGE-007 | **تفاوت تایم‌زون سرور و کلاینت:** تابع `date('Y-m-d H:i:s')` از تایم‌زون سرور استفاده می‌کند و `post_date` وردپرس از تایم‌زون تنظیم‌شده سایت. اختلاف ممکن است باعث انقضای زودهنگام یا دیرهنگام شود. | `helper_functions.php` خط 186 | محاسبه نادرست تاریخ انقضا |
| EDGE-008 | **عدم بررسی `$subscription_data` خالی در `validate_license`:** اگر `get_subscription_data` مقدار `false` برگرداند (خط 29 restapi.php)، تمام دسترسی‌های بعدی به کلیدهای آرایه (خطوط 32-35) باعث خطای PHP می‌شوند. | `restapi.php` خطوط 31–52 | Fatal Error در API |

---

## 5. مشکلات کیفیت کد (Code Quality Issues)

| # | شرح | فایل | خط | اولویت |
|---|------|------|----|--------|
| QA-001 | کامنت‌های فارسی و انگلیسی به صورت مختلط و بدون ساختار مشخص استفاده شده‌اند | سراسری | — | 🟢 پایین |
| QA-002 | فایل `index.php` فاقد ثابت `ABSPATH` check (برای جلوگیری از دسترسی مستقیم) است | `index.php` | 1 | 🟡 متوسط |
| QA-003 | تمام فایل‌های `post-types/` فاقد `defined('ABSPATH') or die()` هستند | `post-types/*` | — | 🟡 متوسط |
| QA-004 | عنوان meta box منابع خبری به انگلیسی (`Custom Fields`) مانده در حالی که سایر عناوین فارسی هستند | `resources.php` | 66 | 🟢 پایین |
| QA-005 | متن placeholder دراپ‌داون پلن‌ها «یک کاربر انتخاب کنید» است در حالی که باید «یک پلن انتخاب کنید» باشد | `helper_functions.php` | 65 | 🟢 پایین |
| QA-006 | فیلد `subscription_site_url` در فرم از نوع `url` است اما ذخیره‌سازی بدون اعتبارسنجی URL انجام می‌شود | `subscriptions.php` | 148 | 🟡 متوسط |
| QA-007 | استایل‌های CSS درون خطی (inline) در meta box ها به جای یک فایل CSS جداگانه | `plans.php`, `subscriptions.php` | — | 🟢 پایین |
| QA-008 | افزونه فاقد ثابت‌های نسخه (`COP_VERSION`)، مسیر (`COP_PATH`)، و URL (`COP_URL`) است | `index.php` | — | 🟡 متوسط |

---

## 6. پیشنهادات ارتقا و بهبود (اولویت‌بندی شده)

### 🔴 اولویت بحرانی (فوری — مانع عملکرد صحیح)

| # | پیشنهاد | فایل هدف | جزئیات فنی |
|---|---------|-----------|------------|
| IMP-001 | **پاکسازی خطای نحوی خط 2 فایل helper_functions.php** | `inc/helper_functions.php` | حذف عبارت `error_log(...)` از انتهای کامنت خط 2 |
| IMP-004 | **رفع نام تابع `get_resources_data` → `get_resource_data`** | `inc/helper_functions.php` | اصلاح نام تابع فراخوانی‌شده در خط 297 |
| IMP-005 | **اضافه کردن Null-check برای `$plan_data` و `$subscription_data` در REST API** | `inc/restapi.php` | اضافه کردن شرط‌های `if (!$plan_data)` و `if (!$subscription_data)` با بازگشت پاسخ خطای مناسب |

### 🟠 اولویت بالا (امنیت و ثبات عملیاتی)

| # | پیشنهاد | فایل هدف | جزئیات فنی |
|---|---------|-----------|------------|
| IMP-006 | **اضافه کردن Nonce Verification به تمام Meta Box ها** | `resources.php`, `plans.php`, `subscriptions.php` | اضافه کردن `wp_nonce_field('cop_save_meta', 'cop_meta_nonce')` در display و `wp_verify_nonce($_POST['cop_meta_nonce'], 'cop_save_meta')` در save |
| IMP-007 | **اضافه کردن بررسی Post Type در هوک `save_post`** | `resources.php`, `plans.php`, `subscriptions.php` | اضافه کردن `if (get_post_type($post_id) !== 'resource') return;` در ابتدای هر تابع save |
| IMP-008 | **پاکسازی URL اشتراک هنگام ذخیره** | `subscriptions.php` | تغییر خط 148 به: `update_post_meta($post_id, 'subscription_site_url', esc_url_raw($_POST['subscription_site_url']));` |
| IMP-009 | **نرمال‌سازی URL دامنه قبل از مقایسه** | `helper_functions.php`, `restapi.php` | ایجاد تابع `cop_normalize_url($url)` برای حذف trailing slash و یکسان‌سازی پروتکل |
| IMP-010 | **جایگزینی `rand()` با `random_int()` در تولید لایسنس** | `helper_functions.php` | تغییر خط 147 به: `$randomString .= $characters[random_int(0, $charactersLength - 1)];` |
| IMP-011 | **حذف لاگ‌های دیباگ از محیط عملیاتی** | `restapi.php` خطوط 24-25, `resources.php` خط 133 | حذف کامل یا محصور کردن در شرط `if (WP_DEBUG)` |
| IMP-012 | **اضافه کردن Rate Limiting به REST API** | `inc/restapi.php` | پیاده‌سازی شمارنده درخواست بر اساس IP با استفاده از Transient API وردپرس |
| IMP-013 | **رفع enqueue اسکریپت Select2 Multi-select** | `inc/helper_functions.php` | تغییر هندل `select2-multiple-js` به `select2-js` یا enqueue جداگانه |

### 🟡 اولویت متوسط (بهبود معماری و نگهداری)

| # | پیشنهاد | فایل هدف | جزئیات فنی |
|---|---------|-----------|------------|
| IMP-014 | **تعریف ثابت‌های اصلی افزونه** | `index.php` | اضافه کردن: `define('COP_VERSION', '1.0');`, `define('COP_PATH', plugin_dir_path(__FILE__));`, `define('COP_URL', plugin_dir_url(__FILE__));` |
| IMP-015 | **اضافه کردن `defined('ABSPATH') or die()` به ابتدای تمام فایل‌ها** | تمام فایل‌ها | امنیت پایه در برابر دسترسی مستقیم به فایل‌ها |
| IMP-016 | **مرکزی‌سازی enqueue اسکریپت Select2** | `helper_functions.php` یا فایل جدید | یک بار enqueue در هوک `admin_enqueue_scripts` به جای هر بار فراخوانی دراپ‌داون |
| IMP-017 | **استخراج استایل‌های CSS درون‌خطی به فایل مجزا** | `assets/css/admin.css` (جدید) | انتقال استایل‌های `form-flex-container` و مشابه‌ها |
| IMP-018 | **استفاده از `get_post_meta()` به جای property های magic** | `helper_functions.php` | تغییر `$subscription->post->subscription_user_id` به `get_post_meta($subscription->post->ID, 'subscription_user_id', true)` |
| IMP-019 | **اضافه کردن `wp_reset_postdata()` بعد از تمام حلقه‌های WP_Query** | `helper_functions.php` | اضافه کردن بعد از هر `while` یا `foreach` مرتبط با WP_Query |
| IMP-020 | **اعتبارسنجی `subscription_resources_ids` قبل از ارسال به `post__in`** | `helper_functions.php` → `get_resource_data` | اضافه کردن: `if (empty($resource_ids) \|\| !is_array($resource_ids)) return false;` |
| IMP-021 | **اصلاح استفاده از `current_time('mysql')` به جای `date()`** | `helper_functions.php` خط 186 | استفاده از تایم‌زون وردپرس برای مقایسه تاریخ |

### 🟢 اولویت پایین (بهبود خوانایی و تجربه توسعه)

| # | پیشنهاد | فایل هدف | جزئیات فنی |
|---|---------|-----------|------------|
| IMP-022 | **اصلاح غلط املایی نام تابع `resouces_post_type`** | `resources.php` | تغییر به `resources_post_type` |
| IMP-024 | **فارسی‌سازی عنوان Meta Box منابع** | `resources.php` | تغییر `'Custom Fields'` به `'فیلدهای سفارشی منبع'` در خط 66 |
| IMP-025 | **اصلاح نام‌گذاری کلاس `third-width`** | `subscriptions.php` | تغییر به `quarter-width` (چون مقدار واقعی 25% است) |
| IMP-026 | **یکپارچه‌سازی پیشوند توابع عمومی** | سراسری | اضافه کردن پیشوند `cop_` به تمام توابع: `cop_validate_license`, `cop_generate_secret_code`, `cop_custom_meta_box`, ... |
| IMP-027 | **حذف فراخوانی بیهوده `generate_secret_code(16)` از `subscriptions_post_type`** | `subscriptions.php` خط 11 | حذف این خط |

---

## 7. لاگ تغییرات و فعالیت‌ها (Changelog)

> این بخش برای ثبت تمام اقدامات انجام‌شده و برنامه‌ریزی شده طراحی شده است.

| تاریخ | شرح اقدام | مسئول | فایل(ها) | وضعیت |
|-------|-----------|-------|----------|-------|
| 2026-06-22 | تحلیل جامع کد و شناسایی مشکلات — تولید گزارش `ISSUES_AND_IMPROVEMENTS.md` | AI Analyst | تمام فایل‌ها | ✅ انجام شد |
| 2026-06-22 | تولید مستندات ساختار پروژه `README.md` | AI Analyst | `README.md` | ✅ انجام شد |
| 2026-06-23 | رفع BUG-001: خطای نحوی در `helper_functions.php` خط 2 | AI Analyst | `inc/helper_functions.php` | ✅ انجام شد |
| — | رفع BUG-002: باگ شرطی `cop_plans_list_dropdown` | — | `inc/helper_functions.php` | 🟢 منتفی (سالم) |
| — | رفع BUG-003: فراخوانی دوگانه و عملگر اشتباه `=` vs `==` | — | `inc/restapi.php` | 🟢 منتفی (سالم) |
| 2026-06-23 | رفع BUG-004: متغیرهای تعریف‌نشده در پاسخ API | AI Analyst | `inc/restapi.php` | ✅ انجام شد |
| 2026-06-23 | رفع BUG-005: بهبود ساختار بارگذاری و ثبت اسکریپت‌های Select2 | AI Analyst | `inc/helper_functions.php` | ✅ انجام شد |
| 2026-06-23 | رفع BUG-006: نام تابع اشتباه `get_resources_data` | AI Analyst | `inc/helper_functions.php` | ✅ انجام شد |
| 2026-06-23 | رفع SEC-001: محدودسازی نرخ درخواست (Rate Limit) در وب‌سرویس | AI Analyst | `inc/restapi.php` | ✅ انجام شد |
| 2026-06-23 | رفع SEC-004: اضافه کردن Nonce Verification به متاباکس‌ها | AI Analyst | چندین فایل | ✅ انجام شد |
| 2026-06-23 | رفع SEC-005: بهینه‌سازی هوک و بررسی Post Type با save_post_{$post_type} | AI Analyst | چندین فایل | ✅ انجام شد |
| 2026-06-23 | رفع EDGE-003: مقایسه و تطابق هوشمند آدرس دامنه‌ها | AI Analyst | `inc/helper_functions.php` | ✅ انجام شد |
| 2026-06-23 | پیاده‌سازی IMP-016 و ARCH-002: مرکزی‌سازی enqueue اسکریپت Select2 | AI Analyst | `index.php` | ✅ انجام شد |
| — | پیاده‌سازی IMP-022 تا IMP-027: بهبودهای خوانایی | — | چندین فایل | ⏳ در انتظار |

---

> [!NOTE]
> **نحوه استفاده از این مستند:**
> - برای شروع رفع مشکلات، از بخش **باگ‌های بحرانی** (جدول 1) آغاز کنید.
> - پس از رفع هر مورد، ستون **وضعیت** را در جدول مربوطه و همچنین بخش **لاگ تغییرات** به‌روزرسانی کنید.
> - هر سیستم AI یا توسعه‌دهنده می‌تواند با مراجعه به ستون **فایل هدف** و **جزئیات فنی** در بخش پیشنهادات، مستقیماً اقدام به پیاده‌سازی کند.
