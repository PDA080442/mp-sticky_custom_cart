# QA: внешние ссылки на корзину с `add-to-cart` и query-параметрами

Требуется включённый редирект страницы корзины на главную: **`cart_route.redirect_to_home`** (см. `docs/qa-cart-route.md`).

## Поведение

1. **Запрос вида** `/cart/?add-to-cart=123`  
   WooCommerce обрабатывает добавление на `wp_loaded` (раньше `template_redirect`). Затем срабатывает редирект на главную. В URL главной **не** попадают `add-to-cart` / `quantity` — только разрешённые маркетинговые ключи (см. ниже).

2. **Количество** `/cart/?add-to-cart=123&quantity=3`  
   Количество учитывает ядро Woo при добавлении; в целевой URL редиректа параметр `quantity` **не** переносится (список переноса — только UTM и click id).

3. **Несколько параметров** `/cart/?add-to-cart=1&utm_source=email&utm_campaign=spring&quantity=2`  
   На главную переносятся **utm_***, **gclid**, **fbclid**, **msclkid** (как у ссылок на checkout), без `add-to-cart` и без `quantity`.

4. **Checkout**  
   Редирект корзины **не** выполняется на `is_checkout()` — шаблон оформления не затрагивается.

5. **Рекламные переходы**  
   Проверить, что после редиректа в адресной строке главной сохранились UTM; в аналитике сессия ведёт себя как ожидается для вашей системы.

6. **Учёт заходов**  
   Включите **«Считать заходы на /cart/ с ?add-to-cart в URL»** — увеличивается счётчик в настройках. Это индикатор объёма таких ссылок, не уникальные пользователи.

## Фильтры

| Фильтр | Назначение |
|--------|------------|
| `mp_sticky_custom_cart_cart_redirect_url` | Базовый URL редиректа (до UTM). |
| `mp_sticky_custom_cart_cart_redirect_final_url` | Финальный URL (после UTM, второй аргумент — был ли `add-to-cart`). |
| `mp_sticky_custom_cart_checkout_preserve_query_keys` | Расширить список переносимых query-ключей (как для checkout). |

## Автопроверка

```bash
node tests/external-cart-links.test.js
```
