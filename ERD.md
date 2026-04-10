# الهيكلية البيانية لقاعدة البيانات (Entity-Relationship Diagram)

هذا الرسم يوضح بتبسيط كيف ترتبط الجداول ببعضها البعض داخل قاعدة بيانات نظام إدارة المشاريع بعد تبسيط النظام.

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
    PROJECTS ||--o{ SERVERS : "يمتلك سيرفرات / Owns Servers"
    
    INVOICES ||--o{ PAYMENTS : "تُسدد عبر / Paid via"
    
    ADMIN ||--o{ ACTIVITY_LOGS : "يقوم بنشاط / Performs"
```

### شرح مبسط للعلاقات:
1. **العميل (Clients) والمشاريع (Projects):** العميل الواحد يمكن أن يكون لديه عدة مشاريع.
2. **المشروع (Projects) والمهام (Tasks):** المشروع الواحد يُقسم إلى عدة مهام. التكلفة الإجمالية للمشروع تحسب آلياً من مجموع تكاليف المهام المرتبطة به.
3. **النظام المالي (الفواتير والمدفوعات):** الفاتورة ترتبط بمشروع وعميل. الدفعة تقلل المبلغ المتبقي للمشروع وتحدث حالة الفاتورة.
4. **السيرفرات (Servers):** ترتبط المشاريع بسيرفرات لإدارتها تقنياً.
