<?php

namespace App\Services;

use App\Services\ProfileQuery;

class SchemaGenerator
{
    protected string $url;
    protected string $siteName;
    protected string $siteDesc;

    public function __construct()
    {
        $this->url = trailingslashit(home_url());
        $this->siteName = get_bloginfo('name');
        $this->siteDesc = get_bloginfo('description');
    }

    public function render(): string
    {
        $graph = $this->buildGraph();

        if (empty($graph)) {
            return '';
        }

        $schema = [
            '@context' => 'https://schema.org',
            '@graph'   => $graph,
        ];

        // JSON_UNESCAPED_UNICODE - сохраняет кириллицу читаемой
        return "<script type=\"application/ld+json\">\n" .
            json_encode($schema, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT) .
            "\n</script>";
    }

    private function buildGraph(): array
    {
        $graph = [];

        // 3. Контекст
        if (is_front_page()) {
            $graph[] = $this->getWebSite();
            $graph[] = $this->getOrganization();
            $graph[] = $this->getCollectionPage('Анкеты эскорт агентства');
        } elseif (is_page_template('template-privacy.blade.php')) {
            $graph[] = $this->getPrivacyPolicyPage();
            $graph[] = $this->getBreadcrumbs();
        } elseif (is_home()) {
            $graph[] = $this->getBlogCollectionPage();
            $graph[] = $this->getBlogEntity();
            $graph[] = $this->getBreadcrumbs();
        } elseif (is_tax('city')) {
            // Специальная обработка для городов
            $graph[] = $this->getCityArchivePage();
            $graph[] = $this->getBreadcrumbs();
        } elseif (is_archive()) {
            $graph[] = $this->getCollectionPage(get_the_archive_title());
            $graph[] = $this->getBreadcrumbs();
        } elseif (is_page_template('template-contact.blade.php')) {
            $graph[] = $this->getContactPage();
            $graph[] = $this->getBreadcrumbs();
        } elseif (is_page_template('template-about.blade.php')) {
            $graph[] = $this->getAboutPage();
            $graph[] = $this->getBreadcrumbs();
        } elseif (is_page_template('template-work.blade.php')) {
            // --- НОВОЕ: Страница вакансии ---
            $graph[] = $this->getWorkWebPage();     // WebPage с mainEntity
            $graph[] = $this->getJobPostingEntity(); // Сама вакансия
            $graph[] = $this->getBreadcrumbs();
        } elseif (is_singular('post')) {
            $graph[] = $this->getBlogPostItemPage(); // Страница
            $graph[] = $this->getSingleBlogPosting(); // Статья (с полным текстом)
            $graph[] = $this->getBreadcrumbs();
        } elseif (is_singular('profile')) {
            $graph[] = $this->getProfileItemPage(); // Страница
            $graph[] = $this->getProfileProduct();  // Продукт
            $graph[] = $this->getBreadcrumbs();     // Крошки
        } elseif (is_page_template('template-faq.blade.php')) {
            $graph[] = $this->getFAQPage();
            $graph[] = $this->getBreadcrumbs();
        } elseif (is_post_type_archive('profile') || is_page_template(['template-incall.blade.php', 'template-outcall.blade.php', 'template-verified.blade.php', 'template-elite.blade.php', 'template-cheap.blade.php', 'template-profiles.blade.php'])) {
            $graph[] = $this->getCollectionPage(get_the_title());
            $graph[] = $this->getBreadcrumbs();
        } elseif (is_page()) {
            $graph[] = $this->getWebPage();
            $graph[] = $this->getBreadcrumbs();
        }

        return $graph;
    }
    /**
     * ItemPage для статьи
     */
    private function getBlogPostItemPage(): array
    {
        global $post;
        $currentUrl = $this->getCurrentUrl();

        // Заголовки и описания с учетом ACF SEO
        $name = get_field('seo_title', $post->ID) ?: get_the_title();
        $description = get_field('seo_description', $post->ID) ?: get_the_excerpt();

        return [
            '@type' => 'ItemPage',
            '@id'   => $currentUrl . '#webpage',
            'url'   => $currentUrl,
            'name'  => $name,
            'description' => $description,
            'isPartOf' => ['@id' => $this->url . '#website'],
            'breadcrumb' => ['@id' => $currentUrl . '#breadcrumb'],
            // Ссылка на сущность Статьи
            'mainEntity' => ['@id' => $currentUrl . '#post']
        ];
    }

    /**
     * BlogPosting (Полная статья)
     */
    private function getSingleBlogPosting(): array
    {
        global $post;
        $currentUrl = $this->getCurrentUrl();

        $headline = get_the_title();
        $description = get_field('seo_description', $post->ID) ?: get_the_excerpt();

        $entity = [
            '@type' => 'BlogPosting',
            '@id'   => $currentUrl . '#post',
            'mainEntityOfPage' => ['@id' => $currentUrl . '#webpage'],
            'headline' => $headline,
            'description' => $description,
            'datePublished' => get_the_date('c'),
            'dateModified' => get_the_modified_date('c'),
            'author' => ['@id' => $this->url . '#organization'],
            'publisher' => ['@id' => $this->url . '#organization'],
            // Полный текст статьи для AI и сниппетов
            'articleBody' => wp_strip_all_tags($post->post_content),
        ];

        // Изображение
        if (has_post_thumbnail($post->ID)) {
            $imgId = get_post_thumbnail_id($post->ID);
            $imgData = wp_get_attachment_image_src($imgId, 'large');
            if ($imgData) {
                $entity['image'] = [
                    '@type' => 'ImageObject',
                    'url' => $imgData[0],
                    'width' => $imgData[1],
                    'height' => $imgData[2]
                ];
            }
        }

        return $entity;
    }

    private function getBlogCollectionPage(): array
    {
        $blogPageId = get_option('page_for_posts');

        // Определяем пагинацию
        $paged = get_query_var('paged') ?: 1;
        $isPaged = $paged > 1;

        // 1. Формируем Название
        $name = get_field('seo_title', $blogPageId)
            ?: get_field('custom_h1', $blogPageId)
            ?: get_the_title($blogPageId);

        if ($isPaged) {
            $name .= ' | Страница ' . $paged;
        }

        // 2. Описание (Только для первой страницы)
        $description = '';
        if (!$isPaged) {
            $description = get_field('seo_description', $blogPageId)
                ?: get_field('intro_text', $blogPageId)
                ?: (has_excerpt($blogPageId) ? get_the_excerpt($blogPageId) : 'Новости и статьи ' . $this->siteName);
        }

        // Даты
        $published = $blogPageId ? get_the_date('c', $blogPageId) : date('c');
        $modified = $blogPageId ? get_the_modified_date('c', $blogPageId) : date('c');

        return [
            '@type' => 'CollectionPage',
            '@id'   => $this->getCurrentUrl() . '#webpage',
            'url'   => $this->getCurrentUrl(),
            'name'  => $name,
            'description' => $description,
            'isPartOf' => ['@id' => $this->url . '#website'],
            'about'    => ['@id' => $this->url . '#organization'],
            'datePublished' => $published,
            'dateModified' => $modified,
            'mainEntity' => ['@id' => $this->getCurrentUrl() . '#blog']
        ];
    }

    /**
     * 2. Сущность Blog + ItemList (Посты)
     */
    private function getBlogEntity(): array
    {
        $blogPageId = get_option('page_for_posts');

        $name = get_field('custom_h1', $blogPageId)
            ?: get_the_title($blogPageId);

        $entity = [
            '@type' => 'Blog',
            '@id'   => $this->getCurrentUrl() . '#blog',
            'name'  => $name,
            'publisher' => ['@id' => $this->url . '#organization'],
        ];

        global $wp_query;
        $posts = $wp_query->posts;

        // Расчет смещения для пагинации
        $paged = get_query_var('paged') ?: 1;
        $postsPerPage = get_option('posts_per_page'); // Стандартная настройка WP
        $positionOffset = ($paged - 1) * $postsPerPage;

        if (!empty($posts)) {
            $itemListElement = [];
            foreach ($posts as $i => $post) {
                $postUrl = trailingslashit(get_permalink($post->ID));

                // Сквозная позиция
                $currentPosition = $positionOffset + $i + 1;

                $blogPosting = [
                    '@type' => 'BlogPosting',
                    '@id'   => $postUrl . '#blogposting',
                    'url'   => $postUrl,
                    'headline' => $post->post_title,
                    'datePublished' => get_the_date('c', $post->ID),
                    'dateModified' => get_the_modified_date('c', $post->ID),
                    'author' => ['@id' => $this->url . '#organization'],
                    'publisher' => ['@id' => $this->url . '#organization'],
                    'mainEntityOfPage' => $postUrl
                ];

                if (has_post_thumbnail($post->ID)) {
                    $imgId = get_post_thumbnail_id($post->ID);
                    $imgData = wp_get_attachment_image_src($imgId, 'large');
                    if ($imgData) {
                        $blogPosting['image'] = [
                            '@type' => 'ImageObject',
                            'url' => $imgData[0],
                            'width' => $imgData[1],
                            'height' => $imgData[2]
                        ];
                    }
                }

                $itemListElement[] = [
                    '@type' => 'ListItem',
                    'position' => $currentPosition, // ИСПОЛЬЗУЕМ СМЕЩЕНИЕ
                    'item' => $blogPosting
                ];
            }

            $entity['mainEntity'] = [
                '@type' => 'ItemList',
                'numberOfItems' => count($itemListElement),
                'itemListElement' => $itemListElement
            ];
        }

        return $entity;
    }

    private function getPrivacyPolicyPage(): array
    {
        $currentUrl = $this->getCurrentUrl();

        return [
            '@type' => 'PrivacyPolicy',
            '@id'   => $currentUrl . '#webpage',
            'url'   => $currentUrl,
            'name'  => get_the_title(),
            // Описание берем из ACF Intro или генерируем стандартное
            'description' => get_field('intro_text') ?: 'Политика обработки персональных данных и конфиденциальности пользователей.',

            // Даты важны для юридических документов
            'datePublished' => get_the_date('c'),
            'dateModified'  => get_the_modified_date('c'),

            'isPartOf' => ['@id' => $this->url . '#website'],
            'breadcrumb' => ['@id' => $currentUrl . '#breadcrumb'],

            // Издатель политики - наша Организация
            'publisher' => ['@id' => $this->url . '#organization']
        ];
    }

    private function getContactPage(): array
    {
        $currentUrl = $this->getCurrentUrl();

        return [
            '@type' => 'ContactPage',
            '@id'   => $currentUrl . '#webpage',
            'url'   => $currentUrl,
            'name'  => get_the_title(), // Например: "Контакты Ero Massage"
            // Описание берем из ACF Intro или дефолтное
            'description' => get_field('intro_text') ?: 'Свяжитесь с нами для уточнения деталей.',
            'isPartOf' => ['@id' => $this->url . '#website'],
            'breadcrumb' => ['@id' => $currentUrl . '#breadcrumb'],

            // ВАЖНО: Связываем страницу контактов с Организацией
            'mainEntity' => ['@id' => $this->url . '#organization']
        ];
    }

    // --- СУЩНОСТИ ---

    private function getWebSite(): array
    {
        return [
            '@type' => 'WebSite',
            '@id'   => $this->url . '#website',
            'url'   => $this->url,
            'name'  => $this->siteName,
            'publisher' => ['@id' => $this->url . '#organization']
        ];
    }

    private function getAboutPage(): array
    {
        $currentUrl = $this->getCurrentUrl();

        return [
            '@type' => 'AboutPage',
            '@id'   => $currentUrl . '#webpage',
            'url'   => $currentUrl,
            'name'  => get_the_title(), // Например: "О компании Ero Massage"
            // Описание берем из ACF Intro (краткое описание) или дефолтное
            'description' => get_field('intro_text') ?: $this->siteDesc,
            'isPartOf' => ['@id' => $this->url . '#website'],
            'breadcrumb' => ['@id' => $currentUrl . '#breadcrumb'],

            'mainEntity' => ['@id' => $this->url . '#organization']
        ];
    }

    private function getOrganization(): array
    {
        $logo = get_field('schema_logo', 'option');
        $legalName = get_field('schema_legal_name', 'option');
        $email = get_field('schema_email', 'option');
        $phone = get_field('schema_phone', 'option') ?: get_field('global_wa', 'option'); // Используем отдельное поле для LD-JSON или fallback на WhatsApp

        return [
            '@type' => 'Organization',
            '@id'   => $this->url . '#organization',
            'url'   => $this->url,
            'name'  => $this->siteName,
            'legalName' => $legalName ?: $this->siteName,
            'logo'  => [
                '@type' => 'ImageObject',
                'url'   => $logo
            ],
            'email' => $email,
            'contactPoint' => [
                '@type' => 'ContactPoint',
                'telephone' => $phone,
                'contactType' => 'customer support',
                'areaServed' => 'KZ',
                'availableLanguage' => ['Russian', 'English']
            ],
        ];
    }

    private function getWorkWebPage(): array
    {
        return [
            '@type' => 'WebPage',
            '@id'   => $this->getCurrentUrl() . '#webpage',
            'url'   => $this->getCurrentUrl(),
            'name'  => get_the_title(),
            'description' => get_field('intro_text') ?: 'Вакансия модели в эскорт агентство.',
            'isPartOf' => ['@id' => $this->url . '#website'],
            'breadcrumb' => ['@id' => $this->getCurrentUrl() . '#breadcrumb'],
            'mainEntity' => ['@id' => $this->getCurrentUrl() . '#job']
        ];
    }

    private function getJobPostingEntity(): array
    {
        // Берем данные из глобальных настроек (адрес офиса)
        $address = get_field('schema_address', 'option');
        $logo = get_field('schema_logo', 'option');

        // Берем данные из текущей страницы (преимущества и требования)
        $intro = get_field('intro_text');
        $benefits = get_field('work_benefits'); // Repeater: title, description
        $requirements = get_field('work_requirements'); // Repeater: text

        // Генерируем HTML описание для Google Jobs
        $descriptionHtml = "<p>" . ($intro ?: 'Приглашаем девушек на высокооплачиваемую работу.') . "</p>";

        if ($requirements) {
            $descriptionHtml .= "<h3>Требования:</h3><ul>";
            foreach ($requirements as $req) {
                $descriptionHtml .= "<li>" . $req['text'] . "</li>";
            }
            $descriptionHtml .= "</ul>";
        }

        if ($benefits) {
            $descriptionHtml .= "<h3>Условия и Преимущества:</h3><ul>";
            foreach ($benefits as $ben) {
                $descriptionHtml .= "<li><strong>" . $ben['title'] . ":</strong> " . $ben['description'] . "</li>";
            }
            $descriptionHtml .= "</ul>";
        }

        return [
            '@type' => 'JobPosting',
            '@id'   => $this->getCurrentUrl() . '#job',
            'title' => get_the_title(),
            'description' => $descriptionHtml,
            'identifier' => [
                '@type' => 'PropertyValue',
                'name' => $this->siteName,
                'value' => 'JOB-' . get_the_ID()
            ],
            'datePosted' => get_the_date('Y-m-d'),
            'validThrough' => date('Y-m-d', strtotime('+1 year')), // Вакансия всегда актуальна (+1 год)
            'employmentType' => ['FULL_TIME', 'PART_TIME', 'CONTRACTOR'],
            'hiringOrganization' => [
                '@type' => 'Organization',
                'name' => $this->siteName,
                'sameAs' => $this->url,
                'logo' => $logo,
                '@id' => $this->url . '#organization'
            ],
            'jobLocation' => [
                '@type' => 'Place',
                'address' => [
                    '@type' => 'PostalAddress',
                    'streetAddress' => $address,
                    'addressLocality' => 'Алматы',
                    'postalCode' => '050000',
                    'addressCountry' => 'KZ'
                ]
            ],
            'baseSalary' => [
                '@type' => 'MonetaryAmount',
                'currency' => 'KZT',
                'value' => [
                    '@type' => 'QuantitativeValue',
                    'minValue' => 200000,
                    'maxValue' => 1000000,
                    'unitText' => 'MONTH'
                ]
            ]
        ];
    }

    /**
     * Специальная обработка для страниц городов
     */
    private function getCityArchivePage(): array
    {
        $current_city = get_queried_object();
        $currentUrl = $this->getCurrentUrl();
        
        // Определяем пагинацию
        $paged = get_query_var('paged') ?: get_query_var('page') ?: 1;
        $isPaged = $paged > 1;
        
        // Получаем SEO данные из ACF полей термина
        $city_name = $current_city ? $current_city->name : 'Город';
        $title = get_field('seo_title', $current_city) ?: get_field('custom_h1', $current_city) ?: $city_name;
        $description = get_field('seo_description', $current_city) ?: get_field('description', $current_city) ?: '';
        
        // Добавляем пагинацию к title
        if ($isPaged) {
            $title .= ' | Страница ' . $paged;
        }
        
        $collection = [
            '@type' => 'CollectionPage',
            '@id'   => $currentUrl . '#webpage',
            'url'   => $currentUrl,
            'name'  => $title,
            'description' => wp_strip_all_tags($description),
            'isPartOf' => ['@id' => $this->url . '#website'],
            'about'    => ['@id' => $this->url . '#organization'],
        ];
        
        // Добавляем ItemList с анкетами города
        $itemList = $this->getCityProfilesList($current_city, $paged);
        if (!empty($itemList)) {
            $collection['mainEntity'] = $itemList;
        }
        
        return $collection;
    }
    
    /**
     * Получает список анкет для города
     */
    private function getCityProfilesList($city, $paged): array
    {
        global $wp_query;
        $posts = $wp_query->posts;
        $postsPerPage = $wp_query->get('posts_per_page') ?: 12;
        
        if (empty($posts)) {
            return [];
        }
        
        $positionOffset = ($paged - 1) * $postsPerPage;
        $itemListElement = [];
        
        foreach ($posts as $i => $post) {
            if ($post->post_type !== 'profile') {
                continue;
            }
            
            $postUrl = trailingslashit(get_permalink($post->ID));
            $currentPosition = $positionOffset + $i + 1;
            
            // Характеристики анкеты
            $props = [];
            $pid = $post->ID;
            
            if (has_term('vip', 'vip', $pid)) $props[] = ['@type' => 'PropertyValue', 'name' => 'Статус', 'value' => 'VIP'];
            if (has_term('verified', 'verified', $pid)) $props[] = ['@type' => 'PropertyValue', 'name' => 'Статус', 'value' => 'Проверенная'];
            if (has_term('independent', 'independent', $pid)) $props[] = ['@type' => 'PropertyValue', 'name' => 'Тип работы', 'value' => 'Индивидуалка'];
            if (strtotime($post->post_date) > strtotime('-7 days')) $props[] = ['@type' => 'PropertyValue', 'name' => 'Статус', 'value' => 'Новая'];
            
            $item = [
                '@type' => 'Person',
                'name' => $post->post_title,
                'url' => $postUrl,
                'image' => get_the_post_thumbnail_url($pid, 'large'),
                'brand' => ['@id' => $this->url . '#organization'],
            ];
            
            if (!empty($props)) {
                $item['additionalProperty'] = $props;
            }
            
            $itemListElement[] = [
                '@type' => 'ListItem',
                'position' => $currentPosition,
                'item' => $item
            ];
        }
        
        if (!empty($itemListElement)) {
            return [
                '@type' => 'ItemList',
                'name' => $city->name . ' - Анкеты',
                'numberOfItems' => count($itemListElement),
                'itemListElement' => $itemListElement
            ];
        }
        
        return [];
    }

    /**
     * Генерирует CollectionPage со списком анкет (ItemList)
     */
    private function getCollectionPage(string $title): array
    {
        $currentUrl = $this->getCurrentUrl();

        // Определяем текущую страницу пагинации
        $paged = get_query_var('paged') ?: get_query_var('page') ?: 1;
        $isPaged = $paged > 1;

        $desc = '';

        // 1. Описание заполняем ТОЛЬКО для первой страницы
        if (!$isPaged) {
            if (is_page() || is_front_page()) {
                $pageId = get_queried_object_id();
                $desc = get_field('seo_description', $pageId)
                    ?: get_field('intro_text', $pageId)
                    ?: (has_excerpt($pageId) ? get_the_excerpt($pageId) : '');
            } elseif (is_archive()) {
                $desc = get_the_archive_description();
            }

            if (empty($desc)) {
                $desc = $this->siteDesc;
            }
        }

        $cleanDesc = wp_strip_all_tags($desc);
        $cleanDesc = trim(preg_replace('/\s+/', ' ', $cleanDesc));

        $title = wp_strip_all_tags(preg_replace('/^[\w\s]+:\s/iu', '', $title));

        // 2. Если это пагинация, добавляем номер страницы в заголовок Schema
        if ($isPaged) {
            $title .= ' | Страница ' . $paged;
        }

        $collection = [
            '@type' => 'CollectionPage',
            '@id'   => $currentUrl . '#webpage',
            'url'   => $currentUrl,
            'name'  => $title,
            'description' => $cleanDesc,
            'isPartOf' => ['@id' => $this->url . '#website'],
            'about'    => ['@id' => $this->url . '#organization'],
        ];

        // Получаем посты
        global $wp_query;
        $posts = [];
        $postsPerPage = 48;

        if (is_page_template(['template-incall.blade.php', 'template-outcall.blade.php', 'template-verified.blade.php', 'template-elite.blade.php', 'template-cheap.blade.php', 'template-profiles.blade.php'])) {
            if (class_exists('App\Services\ProfileQuery')) {
                $query = ProfileQuery::get();
                $posts = $query->posts;
                $postsPerPage = $query->get('posts_per_page'); // Берем реальное кол-во из запроса
            }
        } elseif (!empty($wp_query->posts)) {
            $posts = $wp_query->posts;
            $postsPerPage = $wp_query->get('posts_per_page');
        }

        $positionOffset = ($paged - 1) * $postsPerPage;

        if (!empty($posts)) {
            $itemListElement = [];
            foreach ($posts as $i => $post) {
                $postUrl = trailingslashit(get_permalink($post->ID));
                $item = [];

                $currentPosition = $positionOffset + $i + 1;

                if ($post->post_type === 'profile') {
                    $props = [];
                    $pid = $post->ID;
                    if (has_term('vip', 'vip', $pid)) $props[] = ['@type' => 'PropertyValue', 'name' => 'Статус', 'value' => 'VIP'];
                    if (has_term('verified', 'verified', $pid)) $props[] = ['@type' => 'PropertyValue', 'name' => 'Статус', 'value' => 'Проверенная'];
                    if (has_term('independent', 'independent', $pid)) $props[] = ['@type' => 'PropertyValue', 'name' => 'Тип работы', 'value' => 'Индивидуалка'];
                    if (strtotime($post->post_date) > strtotime('-7 days')) $props[] = ['@type' => 'PropertyValue', 'name' => 'Статус', 'value' => 'Новая'];

                    $item = [
                        '@type' => 'Person',
                        'name' => $post->post_title,
                        'url' => $postUrl,
                        'image' => get_the_post_thumbnail_url($pid, 'large'),
                        'brand' => ['@id' => $this->url . '#organization'],
                    ];

                    if (!empty($props)) {
                        $item['additionalProperty'] = $props;
                    }
                } elseif ($post->post_type === 'post') {
                    $item = [
                        '@type' => 'BlogPosting',
                        'headline' => $post->post_title,
                        'url' => $postUrl,
                        'datePublished' => get_the_date('c', $post->ID),
                        'author' => ['@id' => $this->url . '#organization'],
                        'image' => get_the_post_thumbnail_url($post->ID, 'medium_large'),
                    ];
                }

                if (!empty($item)) {
                    $itemListElement[] = [
                        '@type' => 'ListItem',
                        'position' => $currentPosition, // <-- ИСПОЛЬЗУЕМ СКВОЗНУЮ ПОЗИЦИЮ
                        'item' => $item
                    ];
                }
            }

            if (!empty($itemListElement)) {
                $collection['mainEntity'] = [
                    '@type' => 'ItemList',
                    'name' => $title,
                    'numberOfItems' => count($itemListElement),
                    'itemListElement' => $itemListElement
                ];
            }
        }

        return $collection;
    }

    private function getFAQPage(): array
    {
        $currentUrl = $this->getCurrentUrl();

        // Получаем массив вопросов из ACF
        $faqItems = get_field('faq_list');
        $questions = [];

        if ($faqItems) {
            foreach ($faqItems as $item) {
                $questions[] = [
                    '@type' => 'Question',
                    'name' => $item['question'], // Вопрос
                    'acceptedAnswer' => [
                        '@type' => 'Answer',
                        // Очищаем ответ от HTML тегов, чтобы получить чистый текст как в примере
                        'text' => wp_strip_all_tags($item['answer'])
                    ]
                ];
            }
        }

        return [
            '@type' => 'FAQPage',
            '@id'   => $currentUrl . '#webpage',
            'url'   => $currentUrl,
            'name'  => get_the_title(), // Заголовок страницы (напр. "Часто задаваемые вопросы")
            // Описание берем из ACF Intro или дефолтное описание сайта
            'description' => get_field('intro_text') ?: $this->siteDesc,
            'isPartOf' => ['@id' => $this->url . '#website'],
            'breadcrumb' => ['@id' => $currentUrl . '#breadcrumb'],
            // Ссылка на организацию как издателя
            'publisher' => ['@id' => $this->url . '#organization'],
            // Массив вопросов
            'mainEntity' => $questions
        ];
    }

    private function getProfileItemPage(): array
    {
        global $post;
        $id = $post->ID;
        $currentUrl = $this->getCurrentUrl();

        // Заголовки и описания с учетом ACF SEO
        $name = get_field('seo_title', $id) ?: get_the_title();
        $description = get_field('seo_description', $id) ?: get_the_excerpt();

        return [
            '@type' => 'ItemPage',
            '@id'   => $currentUrl . '#webpage',
            'url'   => $currentUrl,
            'name'  => $name,
            'description' => $description,
            'isPartOf' => ['@id' => $this->url . '#website'],
            'mainEntity' => ['@id' => $currentUrl . '#product'], // Ссылка на продукт
            'breadcrumb' => ['@id' => $currentUrl . '#breadcrumb']
        ];
    }

    /**
     * Product (Анкета)
     */
    private function getProfileProduct(): array
    {
        global $post;
        $id = $post->ID;
        $currentUrl = $this->getCurrentUrl();

        // 1. Изображения (Главное + Галерея)
        $images = [];
        // Главное фото
        if (has_post_thumbnail($id)) {
            $images[] = [
                '@type' => 'ImageObject',
                '@id'   => $currentUrl . '#primaryimage',
                'url'   => get_the_post_thumbnail_url($id, 'full'),
                'name'  => get_the_title(),
                'thumbnail' => [
                    '@type' => 'ImageObject',
                    'url'   => get_the_post_thumbnail_url($id, 'thumbnail')
                ]
            ];
        }
        // Галерея
        $galleryIds = get_field('gallery', $id);
        if ($galleryIds) {
            foreach ($galleryIds as $imgData) {
                // Если возвращает массив (зависит от настроек ACF), берем url
                $imgUrl = is_array($imgData) ? $imgData['url'] : wp_get_attachment_url($imgData);
                $images[] = [
                    '@type' => 'ImageObject',
                    'url'   => $imgUrl
                ];
            }
        }

        // 2. Характеристики (AdditionalProperty)
        $props = [];

        // Простые поля
        $simpleFields = [
            'age' => 'Возраст',
            'height' => 'Рост',
            'weight' => 'Вес',
        ];
        foreach ($simpleFields as $key => $label) {
            if ($val = get_field($key, $id)) {
                $props[] = ['@type' => 'PropertyValue', 'name' => $label, 'value' => $val];
            }
        }

        // Таксономии
        $taxonomies = [
            'hair_color' => 'Цвет волос',
            'breast_size' => 'Грудь',
            'breast_type' => 'Тип груди',
            'body_type' => 'Телосложение',
            'nationality' => 'Национальность',
            'intimate' => 'Интимная стрижка',
            'piercing' => 'Пирсинг',
            'smoker' => 'Курит',
            'inoutcall' => 'Выезд/Аппарт',
            'language' => 'Языки',
        ];

        foreach ($taxonomies as $slug => $label) {
            $terms = get_the_terms($id, $slug);
            if ($terms && !is_wp_error($terms)) {
                $values = array_map(fn($t) => $t->name, $terms);
                $props[] = [
                    '@type' => 'PropertyValue',
                    'name' => $label,
                    'value' => implode(', ', $values)
                ];
            }
        }

        // 1. VIP
        if (has_term('vip', 'vip', $id)) {
            $props[] = ['@type' => 'PropertyValue', 'name' => 'Статус', 'value' => 'VIP'];
        }

        // 2. Проверенная
        if (has_term('verified', 'verified', $id)) {
            $props[] = ['@type' => 'PropertyValue', 'name' => 'Статус', 'value' => 'Проверенная'];
        }

        // 3. Индивидуалка
        if (has_term('independent', 'independent', $id)) {
            $props[] = ['@type' => 'PropertyValue', 'name' => 'Тип работы', 'value' => 'Индивидуалка'];
        }

        // 4. Новая (по дате)
        if (strtotime(get_the_date('', $id)) > strtotime('-7 days')) {
            $props[] = ['@type' => 'PropertyValue', 'name' => 'Статус', 'value' => 'Новая'];
        }

        // 3. Предложения (Offers)
        $priceData = get_field('price', $id);
        $offers = [];
        $currency = $priceData['currency'] ?? 'KZT';

        // Карта цен: ключ ACF => Название тарифа
        $priceMap = [
            'price_1h' => ['name' => 'Аренда 1 час', 'unit' => 'HUR', 'qty' => 1],
            'price_2h' => ['name' => 'Аренда 2 часа', 'unit' => 'HUR', 'qty' => 2],
            'price_4h' => ['name' => 'Аренда 4 часа', 'unit' => 'HUR', 'qty' => 4],
            'price_night' => ['name' => 'Ночь', 'unit' => 'DAY', 'qty' => 0.5], // Условно
            'price_day' => ['name' => 'Сутки', 'unit' => 'DAY', 'qty' => 1],
        ];

        foreach ($priceMap as $key => $data) {
            if (!empty($priceData[$key])) {
                $offer = [
                    '@type' => 'Offer',
                    'name' => $data['name'] . ' (Апартаменты/Выезд)', // Упрощаем, если цена одна
                    'price' => $priceData[$key],
                    'priceCurrency' => $currency,
                    'availability' => 'https://schema.org/InStock',
                    'url' => $currentUrl
                ];

                // Unit Price Specification (для Google Shopping/Merchant)
                $offer['priceSpecification'] = [
                    '@type' => 'UnitPriceSpecification',
                    'price' => $priceData[$key],
                    'priceCurrency' => $currency,
                    'unitCode' => $data['unit'],
                    'referenceQuantity' => [
                        '@type' => 'QuantitativeValue',
                        'value' => (string)$data['qty'],
                        'unitCode' => $data['unit']
                    ]
                ];

                $offers[] = $offer;
            }
        }

        // 4. Отзывы и Рейтинг
        $reviewsList = get_field('reviews_list', $id);
        $reviewsSchema = [];
        $aggregateRating = null;

        if ($reviewsList && is_array($reviewsList)) {
            $totalRating = 0;
            $count = 0;

            foreach ($reviewsList as $rev) {
                $rating = floatval($rev['rating']); // У нас 0.9, переводим в 5-балльную?
                // В JSON rating 0.90. Обычно schema ждет 1-5.
                // Если 0.90 это 90% (4.5), то умножаем на 5.
                $normalizedRating = $rating <= 1 ? $rating * 5 : $rating;

                $reviewsSchema[] = [
                    '@type' => 'Review',
                    'author' => ['@type' => 'Person', 'name' => $rev['author']],
                    'datePublished' => date('Y-m-d', strtotime($rev['date'])),
                    'reviewBody' => wp_strip_all_tags($rev['content']),
                    'reviewRating' => [
                        '@type' => 'Rating',
                        'ratingValue' => number_format($normalizedRating, 1)
                    ]
                ];

                $totalRating += $normalizedRating;
                $count++;
            }

            if ($count > 0) {
                $aggregateRating = [
                    '@type' => 'AggregateRating',
                    'ratingValue' => number_format($totalRating / $count, 1),
                    'reviewCount' => $count
                ];
            }
        }

        // СБОРКА ПРОДУКТА
        $product = [
            '@type' => 'Product',
            '@id'   => $currentUrl . '#product',
            'name'  => get_the_title(),
            'description' => wp_strip_all_tags(get_the_content()),
            'brand' => ['@id' => $this->url . '#organization'],
            'image' => $images,
            'additionalProperty' => $props,
            'offers' => $offers
        ];

        if (!empty($reviewsSchema)) {
            $product['review'] = $reviewsSchema;
        }
        if ($aggregateRating) {
            $product['aggregateRating'] = $aggregateRating;
        }

        // Potential Actions (Избранное / Отзыв)
        /*         $product['potentialAction'] = [
            [
                '@type' => 'WantAction',
                'name' => 'В избранное',
                'target' => $currentUrl . '#wishlist' // Упростили, т.к. нет реального роута избранного
            ],
            [
                '@type' => 'ReviewAction',
                'name' => 'Оставить отзыв',
                'target' => $currentUrl . '#reviews'
            ]
        ]; */

        return $product;
    }

    private function getWebPage(): array
    {
        return [
            '@type' => 'WebPage',
            '@id'   => $this->getCurrentUrl() . '#webpage',
            'url'   => $this->getCurrentUrl(),
            'name'  => get_the_title(),
            'isPartOf' => ['@id' => $this->url . '#website'],
        ];
    }

    private function getBreadcrumbs(): array
    {
        $items = [];
        $position = 1;

        // 1. Главная
        $items[] = [
            '@type' => 'ListItem',
            'position' => $position++,
            'name' => 'Главная',
            'item' => $this->url
        ];

        // 2. Промежуточные звенья

        // Для постов и архивов блога добавляем ссылку на "Блог"
        $blogPageId = get_option('page_for_posts');
        if ($blogPageId && (is_singular('post') || is_category() || is_tag() || is_date())) {
            $items[] = [
                '@type' => 'ListItem',
                'position' => $position++,
                'name' => get_the_title($blogPageId),
                'item' => trailingslashit(get_permalink($blogPageId))
            ];
        }

        // 3. Текущая страница
        if (!is_front_page()) {
            $title = get_the_title();

            if (is_home()) {
                $title = $blogPageId ? get_the_title($blogPageId) : 'Блог';
            }
            // Для архивов берем заголовок архива
            elseif (is_archive()) {
                $title = get_the_archive_title();
                $title = preg_replace('/^[\w\s]+:\s/iu', '', $title); // Убираем "Рубрика: "
                $title = wp_strip_all_tags($title); // Убираем HTML теги
            }
            // Для таксономий городов тоже очищаем от HTML
            elseif (is_tax('city')) {
                $city = get_queried_object();
                $title = $city ? $city->name : 'Город';
                $title = wp_strip_all_tags($title); // Дополнительная очистка
            }

            $items[] = [
                '@type' => 'ListItem',
                'position' => $position++,
                'name' => $title,
                'item' => $this->getCurrentUrl()
            ];
        }

        return [
            '@type' => 'BreadcrumbList',
            '@id'   => $this->getCurrentUrl() . '#breadcrumb',
            'itemListElement' => $items
        ];
    }

    private function getCurrentUrl(): string
    {
        global $wp;
        return trailingslashit(home_url(add_query_arg([], $wp->request)));
    }
}
