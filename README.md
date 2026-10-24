# Vite for WordPress

<p align="left">
  <a href="https://wordpress.org/"><img src="https://img.shields.io/badge/WordPress-6.0%2B%20%7C%207.x-21759B?style=flat-square&logo=wordpress&logoColor=white" alt="WordPress" /></a>
  <a href="https://vitejs.dev/"><img src="https://img.shields.io/badge/Vite-8.x-646CFF?style=flat-square&logo=vite&logoColor=white" alt="Vite" /></a>
  <a href="https://www.typescriptlang.org/"><img src="https://img.shields.io/badge/TypeScript-7.x-3178C6?style=flat-square&logo=typescript&logoColor=white" alt="TypeScript" /></a>
  <a href="https://sass-lang.com/"><img src="https://img.shields.io/badge/Sass-SCSS-CC6699?style=flat-square&logo=sass&logoColor=white" alt="Sass" /></a>
  <a href="https://www.php.net/"><img src="https://img.shields.io/badge/PHP-8.0%2B-777BB4?style=flat-square&logo=php&logoColor=white" alt="PHP" /></a>
  <a href="LICENSE"><img src="https://img.shields.io/badge/License-MIT-blue?style=flat-square" alt="License" /></a>
</p>

Чистая, производительная и масштабируемая стартовая тема для WordPress на базе **Vite**, **TypeScript** и **SCSS**, оптимизированная под верстку заказных сайтов, блоков **Gutenberg** и **ACF PRO**.

## Особенности темы

- **Мгновенный HMR** — обновление SCSS и TypeScript в браузере при сохранении без перезагрузки страницы.
- **Live-Reload для PHP** — при правке шаблонов темы (`header.php`, `index.php` и т.д.) страница автоматически перезагружается.
- **Современный CSS-Reset** — встроенный `_reset.scss` со сбросом отступов, нормализацией блочной модели, оптимизацией типографики и доступности (a11y / `prefers-reduced-motion`).
- **Изоляция Gutenberg & ACF** — две раздельные точки входа (`main` для сайта и `editor` для блоков в админке WP).
- **Безопасные пути на билде (`base: './'`)** — относительные пути к картинкам и шрифтам в CSS/JS гарантируют, что ассеты не сломаются при переносе сайта на любой домен или в подпапку.
- **Хелпер `vite_asset()`** — подключение картинок и медиафайлов из `src/images/` в PHP-шаблонах с поддержкой HMR и хешированных путей в продакшене.
- **Упаковка в ZIP по команде (`npm run zip`)** — быстрая сборка чистого `.zip` архива темы без исходников для установки в админке WordPress.

## Структура проекта

```text
vite-wordpress/
├── dist/                      # Скомпилированные бандлы (создаются при npm run build)
│   ├── .vite/manifest.json    # Манифест с хешами файлов
│   └── assets/                # Стили, скрипты и медиа
├── inc/
│   ├── vite.php               # Движок интеграции Vite (HMR + manifest loader + vite_asset)
│   ├── template-functions.php # Дополнительные функции темы
│   └── template-tags.php      # Пользовательские теги шаблонов
├── scripts/
│   └── zip.js                 # Скрипт создания продакшен ZIP-архива темы
├── src/                       # Исходный код проекта (чистый лист)
│   ├── main.ts                # Главная точка входа фронтенда
│   ├── editor.ts              # Точка входа для Gutenberg и ACF-блоков
│   ├── vite-env.d.ts          # Декларации типов Vite
│   ├── scss/
│   │   ├── _reset.scss        # Базовый CSS-ресет
│   │   ├── main.scss          # Основные стили фронтенда (подключает _reset)
│   │   └── editor.scss        # Стили редактора Gutenberg и превью ACF
│   └── ts/
│       └── app.ts             # Логика фронтенда
├── functions.php              # Подключение ядра темы и inc/vite.php
├── style.css                  # Метаданные темы + системные классы WP
├── tsconfig.json              # Конфигурация TypeScript (@/ alias)
├── vite.config.ts             # Конфигурация Vite
├── package.json               # Зависимости и npm-скрипты
└── LICENSE                    # Лицензия MIT
```

## Доступные команды

### 1. Установка зависимостей
```bash
npm install
```

### 2. Режим разработки (HMR)
```bash
npm run dev
```
Откройте сайт в браузере (например, `http://templater.local`). Тема автоматически обнаружит работающий локальный сервер Vite (`http://localhost:5173`) и подключит ассеты в режиме горячей перезагрузки.

### 3. Сборка для продакшена
```bash
npm run build
```
Vite скомпилирует оптимизированные минифицированные бандлы с хешами в папку `dist/`. При выключенном dev-сервере WordPress будет автоматически отдавать собранные ассеты из `dist/`.

### 4. Упаковка темы в ZIP
```bash
npm run zip
```
Автоматически запускает сборку проекта и упаковывает тему в чистый ZIP-архив `vite-wordpress.zip` (исключая `node_modules`, `src/`, конфиги и файлы разработки). Полученный файл можно сразу загружать через админку WordPress: **Внешний вид → Темы → Добавить новую → Загрузить тему**.

## Архитектура блоков ACF & Gutenberg

Для создания кастомных блоков рекомендуется использовать компонентную структуру:
```text
blocks/
└── hero/
    ├── block.json             # Метаданные блока (название, иконка, категория)
    ├── hero.php               # PHP-шаблон рендера блока
    ├── hero.scss              # Стили блока (импортируются в main.scss и editor.scss)
    └── hero.ts                # Интерактивная логика блока
```

## Использование картинок в PHP

Для вывода картинок из `src/images/` в PHP-шаблонах используйте функцию `vite_asset()`:
```php
<img src="<?php echo esc_url( vite_asset( 'src/images/logo.svg' ) ); ?>" alt="Logo">
```
- В Dev-режиме ссылка ведет на `http://localhost:5173/src/images/logo.svg`.
- В Prod-режиме ссылка берется из `dist/.vite/manifest.json` с актуальным хешем.

## Автор

- **d1oxsnd** — [GitHub Профиль](https://github.com/d1oxsnd) | [Репозиторий проекта](https://github.com/d1oxsnd/vite-for-wordpress)

## Лицензия

[MIT](LICENSE) © 2026 [d1oxsnd](https://github.com/d1oxsnd)
