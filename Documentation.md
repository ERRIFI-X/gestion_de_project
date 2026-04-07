# توثيق مشروع نظام إدارة المشاريع (Project Management System API)

هذا المستند يقدم شرحاً شاملاً للمشروع، كيفية الإعداد، والهيكلية التقنية للـ API.

---

## 1. نظرة عامة (Overview)
المشروع عبارة عن **API خلفي (Backend)** مبني بلغة PHP، مصمم لإدارة المشاريع والعملاء والمهام والجانب المالي. يعتمد النظام على معايير أمنية عالية باستخدام **JWT** ويوفر أتمتة حسابية للبيانات المالية عبر **Database Triggers**.

---

## 2. متطلبات التشغيل (Requirements)
*   **Server:** XAMPP, WAMP, أو أي خادم دمج PHP 8.0+.
*   **Database:** MySQL / MariaDB.
*   **Dependency Manager:** Composer (لإدارة مكتبة JWT).

---

## 3. إعداد المشروع (Step-by-Step Setup)

### أ- تنصيب المكتبات:
قم بتشغيل الأمر التالي في مجلد المشروع لتنزيل المكتبات اللازمة:
```bash
composer install
```

### ب- إعداد قاعدة البيانات:
النظام يتوفر على نظام **Auto-Migration**. لإنشاء الجداول والقاعدة تلقائياً، قم بزيارة الرابط التالي في متصفحك:
`http://localhost/gestiondeproject/index.php?page=database`

### ج- إعداد البيانات التجريبية (اختياري):
لتعبئة النظام ببيانات وهمية للاختبار، قم بتشغيل ملف `seed.php`:
`http://localhost/gestiondeproject/seed.php`

---

## 4. هيكلية قاعدة البيانات (Database Schema)

*   **admin:** بيانات المسؤولين (اسم المستخدم، كلمة المرور المشفرة).
*   **clients:** سجل العملاء.
*   **projects:** معلومات المشروع (الحالة، التكلفة، المبلغ المتبقي).
*   **tasks:** المهام المرتبطة بكل مشروع (الأولوية، المدة، التكلفة).
*   **services & pack_templates:** لإدارة أنواع الخدمات المقدمة مسبقاً.
*   **invoices & payments:** لإدارة الفواتير وعمليات الدفع.
*   **activity_logs:** سجل كامل لكل العمليات التي تتم في النظام.
*   **servers:** السيرفرات المرتبطة بكل مشروع (الاسم، الوصف).

---

## 5. التوثيق التقني للروابط (API Endpoints)

جميع الروابط تبدأ بـ: `index.php?page=[module]`

### أ- المصادقة (Authentication):
*   **إنشاء حساب:** `POST index.php?page=auth&action=register`
*   **تسجيل الدخول:** `POST index.php?page=auth&action=login`
    *   *المخرجات:* Token يتم استخدامه في الـ Header كـ `Authorization: Bearer [TOKEN]`.

### ب- إدارة العملاء (Clients):
*   **عرض الكل:** `GET index.php?page=clients`
*   **إضافة جديد:** `POST index.php?page=clients`
*   **تعديل:** `PUT index.php?page=clients&id=[ID]`
*   **حذف:** `DELETE index.php?page=clients&id=[ID]`

### ج- إدارة المشاريع (Projects):
*   **عرض الكل:** `GET index.php?page=projects`
*   **إضافة مشروع:** `POST index.php?page=projects`
*   **تتبع الحالة:** يمكن تحديث الحالة من `pending` إلى `completed`.

### د- المهام (Tasks):
*   **عرض مهام مشروع معين:** `GET index.php?page=tasks&project_id=[ID]`
*   **إضافة مهمة:** `POST index.php?page=tasks`

### هـ- السيرفرات (Servers):
*   **عرض الكل:** `GET index.php?page=servers`
*   **عرض سيرفرات مشروع:** `GET index.php?page=servers&project_id=[ID]`
*   **عرض سيرفر واحد:** `GET index.php?page=servers&id=[ID]`
*   **إضافة سيرفر:** `POST index.php?page=servers`
*   **تعديل سيرفر:** `PUT index.php?page=servers&id=[ID]`
*   **حذف سيرفر:** `DELETE index.php?page=servers&id=[ID]`

---

## 6. الأتمتة المالية (Financial Triggers)
تم برمجة قاعدة البيانات لتقليل الأخطاء البشرية:
1.  **عند إضافة مهمة:** يتم تحديث `total_cost` للمشروع تلقائياً.
2.  **عند إضافة دفعة (Payment):** يتم طرح القيمة من `remaining_amount` للمشروع تلقائياً.

---

## 7. الحماية (Security)
*   يتم التحقق من الـ **JWT Token** في كل طلب للمسارات المحمية.
*   تشفير كلمات المرور باستخدام `password_hash`.
*   منع طلبات الاستعلامات الضارة (SQL Injection) باستخدام **PDO Prepared Statements**.

---

**ملاحظة:** هذا المشروع مصمم ليكون "Headless"، أي أنه جاهز للربط مع أي واجهة أمامية حديثة (React, Next.js, Mobile App).
