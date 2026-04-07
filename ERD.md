# الهيكلية البيانية لقاعدة البيانات (Entity-Relationship Diagram)

هذا الرسم يوضح بتبسيط كيف ترتبط الجداول ببعضها البعض داخل قاعدة بيانات نظام إدارة المشاريع.

```mermaid
erDiagram
    CLIENTS {
        int id PK
        varchar name
        varchar email
    }
    
    PROJECTS {
        int id PK
        int client_id FK
        int pack_template_id FK
        varchar name
        decimal total_cost
        decimal remaining_amount
    }
    
    TASKS {
        int id PK
        int project_id FK
        varchar title
        decimal total_cost
    }
    
    INVOICES {
        int id PK
        int project_id FK
        int client_id FK
        decimal amount
    }
    
    PAYMENTS {
        int id PK
        int project_id FK
        int client_id FK
        int invoice_id FK
        decimal amount
    }
    
    PACK_TEMPLATES {
        int id PK
        varchar name
    }
    
    SERVICES {
        int id PK
        varchar name
        decimal price
    }
    
    PACK_SERVICES {
        int id PK
        int pack_template_id FK
        int service_id FK
    }
    
    PROJECT_SERVICES {
        int id PK
        int project_id FK
        int service_id FK
    }
    
    ADMIN {
        int id PK
        varchar username
    }
    
    ACTIVITY_LOGS {
        int id PK
        int user_id FK
        varchar action
    }

    SERVERS {
        int id PK
        int project_id FK
        varchar name
        text description
    }

    %% العلاقات بين الجداول
    CLIENTS ||--o{ PROJECTS : "يملك / Has"
    CLIENTS ||--o{ INVOICES : "يتلقى / Receives"
    CLIENTS ||--o{ PAYMENTS : "يقوم بـ / Makes"
    
    PROJECTS ||--o{ TASKS : "يحتوي على / Contains"
    PROJECTS ||--o{ INVOICES : "يُصدر لها / Generates"
    PROJECTS ||--o{ PAYMENTS : "يُسدد لها / Receives Payment"
    PROJECTS ||--o{ PROJECT_SERVICES : "يتضمن خدمات / Includes"
    PROJECTS ||--o{ SERVERS : "يمتلك سيرفرات / Owns Servers"
    
    PACK_TEMPLATES ||--o{ PROJECTS : "يُطبق على / Applied to"
    PACK_TEMPLATES ||--o{ PACK_SERVICES : "يحتوي على / Contains"
    
    SERVICES ||--o{ PACK_SERVICES : "جزء من باقة / Part of"
    SERVICES ||--o{ PROJECT_SERVICES : "يُقدم في مشروع / Assigned to"
    
    INVOICES ||--o{ PAYMENTS : "تُسدد عبر / Paid via"
    
    ADMIN ||--o{ ACTIVITY_LOGS : "يقوم بنشاط / Performs"
```

### شرح مبسط للعلاقات:
1. **العميل (Clients) والمشاريع (Projects):** العميل الواحد يمكن أن يكون لديه عدة مشاريع **(علاقة 1 إلى متعدد)**.
2. **المشروع (Projects) والمهام (Tasks):** المشروع الواحد يُقسم إلى عدة مهام. ولدينا هنا **(Triggers)** تعمل آلياً بحيث أنه كلما زادت تكلفة مهمة، تزداد تكلفة المشروع بأكمله `total_cost`.
3. **الخدمات (Services)، الباقات (Pack Templates) والمشاريع:**
   - السيرفر يوفر خدمات مفردة.
   - يمكن تجميع هذه الخدمات في "باقة" (Pack Template).
   - المشروع يمكن أن يعتمد على "باقة" مسبقة الصنع أو خدمات فردية مخصصة.
4. **النظام المالي (الفواتير والمدفوعات):**
   - الفاتورة (Invoice) ترتبط بمشروع وعميل.
   - الدفعة (Payment) يمكن أن تُسدد فاتورة محددة، أو تُسدد جزءاً من تكلفة المشروع بشكل عام. بمجرد إضافة دفعة، يقوم الـ Trigger بتقليل `remaining_amount` للمشروع تلقائياً.
5. **لوحة التحكم (Admin):** تسجل كافة تحركات الإدارة وتُحفظ كـ (Activity Logs) مرتبطة بالـ (Admin ID).
