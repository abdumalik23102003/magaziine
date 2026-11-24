# Rollar va ruxsatlar (1-faza)

| Resurs | admin | moderator | operator | partner | customer |
|---|---|---|---|---|---|
| Product | hammasi | ko'rish, approve, reject | - | faqat o'z brendi: CRUD | - |
| Brand | hammasi | ko'rish | - | o'z brendi: ko'rish, tahrir | - |
| Order | hammasi | - | ko'rish, status | - | o'zinikini ko'rish |
| User | hammasi (o'zini o'chira olmaydi) | - | - | - | - |
| Post (4-faza) | hammasi | ko'rish, approve, reject, tahrir | - | o'z brendi: create/update/delete, submit | - |

Panel: `admin` -> admin, moderator, operator | `partner` -> partner, admin.

## PostPolicy metodlari (4-fazada yoziladi)
viewAny, view, create, update, delete, publish, approve, reject
- publish: faqat admin
- approve/reject: admin, moderator
- partner: create/update/delete faqat `post.brand_id` o'zining brendlaridan bo'lsa, published ga o'zi o'tkaza olmaydi (submitForReview)
