# Rollar va ruxsatlar (1-faza)

| Resurs | admin | moderator | operator | partner | customer |
|---|---|---|---|---|---|
| Product | hammasi | ko'rish, approve, reject | - | faqat o'z brendi: CRUD | - |
| Brand | hammasi | ko'rish | - | o'z brendi: ko'rish, tahrir | - |
| Order | hammasi | - | ko'rish, status | - | o'zinikini ko'rish |
| User | hammasi (o'zini o'chira olmaydi) | - | - | - | - |
| Post (4-faza) | hammasi | ko'rish, approve, reject, tahrir | - | o'z brendi: create/update/delete, submit | - |

Panel: `admin` -> admin, moderator, operator | `partner` -> partner, admin.

## PostPolicy metodlari (4-faza)
viewAny, view, create, update, delete, submitForReview, approve, reject, publish
- publish: faqat admin
- approve/reject: admin, moderator
- partner: create/update/delete faqat `post.brand_id` o'zining brendlaridan bo'lsa, published ga o'zi o'tkaza olmaydi (submitForReview)

## Post holatlari (PostStatus)
draft -> pending (submitForReview) -> published (approve) | rejected (reject, sabab majburiy) -> qayta pending
- admin draft/pending/rejected ni to'g'ridan-to'g'ri published qila oladi (publish)
- partner faqat draft/rejected ni tahrirlaydi/o'chiradi; published/pending ga tegolmaydi
- moderator hamkorning draft'ini ko'rmaydi (faqat pending/published/rejected)
- saytda faqat: status=published, published_at <= hozir, brendi (bor bo'lsa) faol
- partner panel hali yo'q: hamkor Post'ni hozircha faqat policy/servis orqali boshqaradi (Filament `partner` panel keyingi fazada)
