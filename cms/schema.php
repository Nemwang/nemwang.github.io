<?php
// Everything the admin panel can edit is described here. The admin forms are
// generated from this file, so adding a field = adding one line below and
// printing it in the page with s('field_name').
//
// Field types: text, textarea, number, bool, select, image, file, heading.

$starHelp = 'Wrap words in *stars* to colour them. A new line starts a new line on the site.';

return [

    'settings' => [

        'general' => [
            'label' => 'Name & logo',
            'icon' => 'id-card',
            'description' => 'Your name, logo and how the site appears in Google.',
            'fields' => [
                'identity_heading' => ['label' => 'Identity', 'type' => 'heading'],
                'site_name' => ['label' => 'Site name', 'type' => 'text', 'default' => 'Portfolio', 'help' => 'First line next to the logo.'],
                'site_tagline' => ['label' => 'Tagline', 'type' => 'text', 'default' => 'Nemwang', 'help' => 'Second line next to the logo.'],
                'logo' => ['label' => 'Logo', 'type' => 'image', 'default' => 'images/brand.png'],
                'favicon' => ['label' => 'Favicon (browser tab icon)', 'type' => 'image', 'default' => 'favicon.png'],
                'seo_heading' => ['label' => 'Search & sharing', 'type' => 'heading'],
                'meta_description' => ['label' => 'Site description', 'type' => 'textarea', 'default' => '', 'help' => 'Shown by Google under the page title, and in link previews. Each page can have its own; this is used where a page has none. If empty, the start of your About me text is used.'],
                'share_image' => ['label' => 'Link preview image', 'type' => 'image', 'default' => '', 'help' => 'Shown when your site is shared on LinkedIn, Facebook, WhatsApp or X. Best at 1200 × 630. If empty, the introduction background is used.'],
                'meta_author' => ['label' => 'Author', 'type' => 'text', 'default' => 'Jhalanath Nembang'],
                'head_code' => ['label' => 'Extra code for <head>', 'type' => 'textarea', 'default' => '<meta name="p:domain_verify" content="5ab49e436cf97cd3c676d31b55eed21a"/>', 'help' => 'Verification tags, analytics snippets. Printed as-is on every page.'],
            ],
        ],

        'layout' => [
            'label' => 'Header & footer',
            'icon' => 'layout',
            'description' => 'What shows in the header, the slide-out menu and the bottom bar.',
            'fields' => [
                'header_heading' => ['label' => 'Header', 'type' => 'heading'],
                'header_show_logo' => ['label' => 'Show the logo', 'type' => 'bool', 'default' => '1'],
                'header_show_name' => ['label' => 'Show the site name and tagline', 'type' => 'bool', 'default' => '1'],
                'header_show_social' => ['label' => 'Show social media icons in the header', 'type' => 'bool', 'default' => '1'],
                'show_loader' => ['label' => 'Show the loading animation', 'type' => 'bool', 'default' => '1'],
                'footer_heading' => ['label' => 'Footer', 'type' => 'heading'],
                'footer_copyright' => ['label' => 'Copyright line (bottom of every page)', 'type' => 'text', 'default' => '© Nemwang {year}', 'help' => 'Write {year} for the current year; it updates by itself.'],
                'menu_copyright' => ['label' => 'Text at the bottom of the menu', 'type' => 'textarea', 'default' => "© Nemwang {year}. All Rights Reserved\nDesign by J.Nemwang", 'help' => 'Write {year} for the current year.'],
                'menu_show_social' => ['label' => 'Show social media icons in the menu', 'type' => 'bool', 'default' => '1'],
                'show_language' => ['label' => 'Show the language label', 'type' => 'bool', 'default' => '1'],
                'language_label' => ['label' => 'Language label', 'type' => 'text', 'default' => 'Eng'],
            ],
        ],

        'home' => [
            'label' => 'Home sections',
            'icon' => 'home',
            'description' => 'Turn each section of the home page on or off and edit its text and images.',
            'fields' => [
                'page_heading_' => ['label' => 'Page', 'type' => 'heading'],
                'home_enabled' => ['label' => 'Page is online', 'type' => 'bool', 'default' => '1'],
                'home_title' => ['label' => 'Browser tab title', 'type' => 'text', 'default' => 'portfolio', 'help' => 'Also the title Google shows. Your name plus what you do works best, e.g. "Jhalanath Nembang — Web Developer & UI/UX Designer".', 'wide' => true],
                'home_description' => ['label' => 'Description for Google and link previews', 'type' => 'textarea', 'default' => '', 'help' => 'About 150 characters. Leave empty to use the site description from Name & logo.'],

                'intro_heading_' => ['label' => '1. Introduction', 'type' => 'heading'],
                'intro_enabled' => ['label' => 'Show this section', 'type' => 'bool', 'default' => '1'],
                'intro_side' => ['label' => 'Side label', 'type' => 'text', 'default' => 'Introduction'],
                'intro_heading' => ['label' => 'Heading', 'type' => 'textarea', 'default' => "*Hello*\nMy Name is *Jhalanath Nembang*", 'help' => $starHelp],
                'intro_sub' => ['label' => 'Line under the heading', 'type' => 'text', 'default' => 'Welcome to My *Portfolio*', 'help' => $starHelp],
                'intro_bg' => ['label' => 'Background image', 'type' => 'image', 'default' => 'images/bg/portfolio.jpg'],
                'intro_cv_button' => ['label' => 'Show a "Download CV" button', 'type' => 'bool', 'default' => '1', 'help' => 'Uses the CV file from the Resume section.'],

                'about_heading_' => ['label' => '2. About me', 'type' => 'heading'],
                'about_enabled' => ['label' => 'Show this section', 'type' => 'bool', 'default' => '1'],
                'about_side' => ['label' => 'Side label', 'type' => 'text', 'default' => 'what I do'],
                'about_subtitle' => ['label' => 'Small title', 'type' => 'text', 'default' => 'About me'],
                'about_heading' => ['label' => 'Heading', 'type' => 'textarea', 'default' => "My mission is\n*design & develop* the best Websites around", 'help' => $starHelp],
                'about_text' => ['label' => 'Text', 'type' => 'textarea', 'default' => 'Highly motivated computer science student with strong problem solving & analytical skills looking to break into Digital Marketing also creating your UI/UX Design for your website and webapp . A team player with good communication skills and an aptitude for programming to deliver meaningful products.', 'help' => 'The bars beside it are under "Skill bars".'],

                'skills_heading_' => ['label' => '3. Technical skills', 'type' => 'heading'],
                'skills_enabled' => ['label' => 'Show this section', 'type' => 'bool', 'default' => '1'],
                'skills_side' => ['label' => 'Side label', 'type' => 'text', 'default' => 'Things I can do'],
                'skills_heading' => ['label' => 'Heading', 'type' => 'text', 'default' => 'Technical Skills', 'help' => 'The boxes are under "Technical skills".'],

                'services_heading_' => ['label' => '4. Services', 'type' => 'heading'],
                'services_enabled' => ['label' => 'Show this section', 'type' => 'bool', 'default' => '1'],
                'services_side' => ['label' => 'Side label', 'type' => 'text', 'default' => 'Services'],
                'services_subtitle' => ['label' => 'Small title', 'type' => 'text', 'default' => 'My services'],
                'services_heading' => ['label' => 'Heading', 'type' => 'textarea', 'default' => 'I like *to make* things easy and fun', 'help' => $starHelp],
                'services_image' => ['label' => 'Image', 'type' => 'image', 'default' => 'images/portfolio.jpg'],
                'services_badge_number' => ['label' => 'Badge number', 'type' => 'text', 'default' => '', 'help' => 'Big number on the badge over the image, for example years of experience.'],
                'services_badge_text' => ['label' => 'Badge text', 'type' => 'textarea', 'default' => "working\nhard to gain\nExperience"],

                'work_heading_' => ['label' => '5. Featured projects', 'type' => 'heading'],
                'work_enabled' => ['label' => 'Show this section', 'type' => 'bool', 'default' => '1'],
                'work_side' => ['label' => 'Side label', 'type' => 'text', 'default' => 'Selected work'],
                'work_subtitle' => ['label' => 'Small title', 'type' => 'text', 'default' => 'Featured projects'],
                'work_heading' => ['label' => 'Heading', 'type' => 'textarea', 'default' => 'Some of my *recent work*', 'help' => $starHelp],
                'work_button' => ['label' => 'Button text', 'type' => 'text', 'default' => 'See all projects', 'help' => 'Shows up to 3 projects: the ones switched to "Show on the home page" under Projects, or the first 3 if none are.', 'wide' => true],

                'resume_heading_' => ['label' => '6. Resume', 'type' => 'heading'],
                'resume_enabled' => ['label' => 'Show this section', 'type' => 'bool', 'default' => '1'],
                'resume_bg' => ['label' => 'Background image', 'type' => 'image', 'default' => 'images/bg/bg.jpg'],
                'resume_left_title' => ['label' => 'Left column title', 'type' => 'text', 'default' => 'Education'],
                'resume_right_title' => ['label' => 'Right column title', 'type' => 'text', 'default' => 'Experience'],
                'resume_cv_label' => ['label' => 'Download link text', 'type' => 'text', 'default' => 'Download Resume'],
                'resume_cv' => ['label' => 'CV file (PDF)', 'type' => 'file', 'default' => 'file/CV Jhalanath Nembang.pdf'],
                'resume_cv_filename' => ['label' => 'File name visitors get', 'type' => 'text', 'default' => 'Portfolio_jhalanath_nembang.pdf'],
                'resume_cv_button' => ['label' => 'Show a "Download CV" button under the resume', 'type' => 'bool', 'default' => '1', 'help' => 'Visible on every screen size, including phones.'],

                'contact_heading_' => ['label' => '7. Contact', 'type' => 'heading'],
                'contact_enabled' => ['label' => 'Show this section', 'type' => 'bool', 'default' => '1'],
                'contact_side' => ['label' => 'Side label', 'type' => 'text', 'default' => 'Contact'],
                'contact_heading' => ['label' => 'Heading', 'type' => 'text', 'default' => 'Get In Touch'],
                'contact_intro' => ['label' => 'Text above your contact details', 'type' => 'textarea', 'default' => 'Have a project in mind? Send me a message or reach me directly.'],
                'contact_email' => ['label' => 'Email address', 'type' => 'text', 'default' => 'nemwang.soooman@gmail.com', 'help' => 'Shown as a link visitors can click. Leave empty to hide.'],
                'contact_phone' => ['label' => 'Phone number', 'type' => 'text', 'default' => '', 'help' => 'With country code, e.g. +977 98XXXXXXXX. Leave empty to hide.'],
                'contact_whatsapp' => ['label' => 'WhatsApp number', 'type' => 'text', 'default' => '', 'help' => 'With country code, e.g. +977 98XXXXXXXX. Adds a WhatsApp button. Leave empty to hide.'],
                'contact_viber' => ['label' => 'Viber number', 'type' => 'text', 'default' => '', 'help' => 'With country code. Adds a Viber button. Leave empty to hide.'],
                'contact_location' => ['label' => 'Location', 'type' => 'text', 'default' => '', 'help' => 'e.g. Kathmandu, Nepal. Leave empty to hide.'],
                'whatsapp_float' => ['label' => 'Show a floating WhatsApp button on every page', 'type' => 'bool', 'default' => '1', 'help' => 'Only appears when a WhatsApp number is filled in.'],
                'contact_button' => ['label' => 'Button text', 'type' => 'text', 'default' => 'Send Message'],
                'contact_success_title' => ['label' => 'Success message title', 'type' => 'text', 'default' => 'Thank you'],
                'contact_success_text' => ['label' => 'Success message text', 'type' => 'text', 'default' => 'Your message is successfully sent...'],
                'contact_error_title' => ['label' => 'Error message title', 'type' => 'text', 'default' => 'Sorry'],
                'contact_error_text' => ['label' => 'Error message text', 'type' => 'text', 'default' => 'Something went wrong'],
                'contact_notify_email' => ['label' => 'Email me new messages at', 'type' => 'text', 'default' => '', 'help' => 'Optional. Messages are always saved under "Messages"; this also sends you a copy.'],
            ],
        ],

        'ux' => [
            'label' => 'Page settings',
            'icon' => 'settings',
            'description' => 'Title and heading of the UI/UX designs page.',
            'fields' => [
                'ux_enabled' => ['label' => 'Page is online', 'type' => 'bool', 'default' => '1'],
                'ux_title' => ['label' => 'Browser tab title', 'type' => 'text', 'default' => 'Portfolio-Jhalanath Nemwang', 'wide' => true],
                'ux_description' => ['label' => 'Description for Google and link previews', 'type' => 'textarea', 'default' => '', 'help' => 'About 150 characters. Leave empty to use the site description from Name & logo.'],
                'ux_heading' => ['label' => 'Heading', 'type' => 'text', 'default' => 'UX/UI Designs'],
            ],
        ],

        'projects' => [
            'label' => 'Page settings',
            'icon' => 'settings',
            'description' => 'Title, heading and labels of the Projects page.',
            'fields' => [
                'projects_enabled' => ['label' => 'Page is online', 'type' => 'bool', 'default' => '1'],
                'projects_title' => ['label' => 'Browser tab title', 'type' => 'text', 'default' => 'Projects — Portfolio', 'wide' => true, 'help' => 'Each project page uses the project name followed by this.'],
                'projects_description' => ['label' => 'Description for Google and link previews', 'type' => 'textarea', 'default' => '', 'help' => 'About 150 characters. Leave empty to use the site description from Name & logo. Each project page uses that project description.'],
                'projects_text_heading_' => ['label' => 'Text on the page', 'type' => 'heading'],
                'projects_subtitle' => ['label' => 'Small title', 'type' => 'text', 'default' => 'My work'],
                'projects_heading' => ['label' => 'Heading', 'type' => 'text', 'default' => 'Projects'],
                'projects_intro' => ['label' => 'Text under the heading', 'type' => 'textarea', 'default' => 'Websites, designs and digital marketing work I have done for clients.'],
                'projects_all_label' => ['label' => 'Filter button for everything', 'type' => 'text', 'default' => 'All'],
                'projects_visit_label' => ['label' => 'Website button text', 'type' => 'text', 'default' => 'Visit website'],
                'projects_back_label' => ['label' => 'Back link text', 'type' => 'text', 'default' => 'All projects'],
                'projects_share_label' => ['label' => 'Label before the social icons', 'type' => 'text', 'default' => 'Share:'],
            ],
        ],
    ],

    // "title" names the field shown in the admin list; "thumb" an image field to preview.
    'collections' => [

        'menu' => [
            'label' => 'Menu',
            'icon' => 'list',
            'description' => 'Links in the slide-out menu. The order here is the order on the site.',
            'singular' => 'menu item',
            'title' => 'label',
            'subtitle' => 'url',
            'fields' => [
                'label' => ['label' => 'Label', 'type' => 'text'],
                'url' => ['label' => 'Link', 'type' => 'text', 'help' => 'A page (ux.php, projects.php, ./ for home), a full address (https://...), or a home section: #page1 Introduction, #page2 About, #page3 Skills, #page4 Services, #page5 Resume, #page6 Contact.'],
                'show_on' => ['label' => 'Show on', 'type' => 'select', 'default' => 'all', 'options' => [
                    'all' => 'Every page',
                    'home' => 'Home page only',
                    'inner' => 'Every page except home',
                    'ux' => 'UI/UX page only',
                    'projects' => 'Projects page only',
                ]],
            ],
        ],

        'social' => [
            'label' => 'Social media',
            'icon' => 'share',
            'description' => 'Your profiles, shown as icons in the header, the menu and the project slides.',
            'singular' => 'social link',
            'title' => 'network',
            'subtitle' => 'url',
            'fields' => [
                'network' => ['label' => 'Network', 'type' => 'select', 'default' => 'website', 'options' => array_map(fn($n) => $n[0], social_networks())],
                'url' => ['label' => 'Link to your profile', 'type' => 'text'],
            ],
        ],

        'progress' => [
            'label' => 'Skill bars',
            'icon' => 'bars',
            'description' => 'The percentage bars beside the About me text.',
            'singular' => 'skill bar',
            'title' => 'label',
            'subtitle' => 'percent',
            'fields' => [
                'label' => ['label' => 'Skill', 'type' => 'text'],
                'percent' => ['label' => 'Level (0-100)', 'type' => 'number', 'default' => '50', 'min' => 0, 'max' => 100, 'suffix' => '%'],
            ],
        ],

        'skills' => [
            'label' => 'Technical skills',
            'icon' => 'code',
            'description' => 'The boxes in the Technical skills section. Hovering a box shows its background image.',
            'singular' => 'skill box',
            'title' => 'title',
            'subtitle' => 'description',
            'fields' => [
                'title' => ['label' => 'Title', 'type' => 'text'],
                'description' => ['label' => 'Description', 'type' => 'textarea'],
                'link' => ['label' => 'Link when clicked', 'type' => 'text', 'help' => 'Optional, for example ux.php or projects.php.'],
                'background' => ['label' => 'Background shown on hover', 'type' => 'image'],
            ],
        ],

        'services' => [
            'label' => 'Services',
            'icon' => 'briefcase',
            'description' => 'The numbered list in the Services section.',
            'singular' => 'service',
            'title' => 'title',
            'fields' => [
                'title' => ['label' => 'Service', 'type' => 'text', 'help' => 'The 01, 02... numbers are added automatically.'],
                'link' => ['label' => 'Link', 'type' => 'text', 'help' => 'Optional.'],
            ],
        ],

        'resume' => [
            'label' => 'Resume entries',
            'icon' => 'file-text',
            'description' => 'Education and experience entries in the Resume section.',
            'singular' => 'resume entry',
            'title' => 'title',
            'subtitle' => 'place',
            'fields' => [
                'column' => ['label' => 'Column', 'type' => 'select', 'default' => 'left', 'options' => ['left' => 'Left (Education)', 'right' => 'Right (Experience)']],
                'title' => ['label' => 'Title', 'type' => 'text'],
                'place' => ['label' => 'School / company', 'type' => 'text'],
                'date' => ['label' => 'Dates', 'type' => 'text'],
                'text' => ['label' => 'Details', 'type' => 'textarea', 'help' => 'One point per line.'],
            ],
        ],

        'gallery' => [
            'label' => 'UI/UX designs',
            'icon' => 'image',
            'description' => 'Your UI/UX design images, shown on the UI/UX Designs page.',
            'singular' => 'UI/UX design',
            'view' => 'grid',
            'title' => 'name',
            'thumb' => 'image',
            'fields' => [
                'name' => ['label' => 'Name', 'type' => 'text'],
                'image' => ['label' => 'Image', 'type' => 'image'],
            ],
        ],

        'projects' => [
            'label' => 'Projects',
            'icon' => 'folder',
            'description' => 'Your work, shown as cards on the Projects page. Each project also gets its own page.',
            'singular' => 'project',
            'view' => 'grid',
            'title' => 'name',
            'subtitle' => 'category',
            'thumb' => 'background',
            'fields' => [
                'category' => ['label' => 'Category', 'type' => 'text', 'default' => 'WordPress', 'suggest' => true, 'help' => 'Each category becomes a filter button. Pick an existing one or type a new one, e.g. Web development, UI/UX, Graphics.'],
                'heading' => ['label' => 'Heading', 'type' => 'text', 'default' => 'WordPress Website Development'],
                'name' => ['label' => 'Project name', 'type' => 'text'],
                'description' => ['label' => 'Description', 'type' => 'textarea'],
                'background' => ['label' => 'Main image', 'type' => 'image', 'help' => 'Shown on the project card and at the top of the project page. A wide screenshot works best.'],
                'logo' => ['label' => 'Client logo', 'type' => 'image'],
                'client' => ['label' => 'Client', 'type' => 'text'],
                'completion' => ['label' => 'Completion', 'type' => 'text'],
                'type' => ['label' => 'Project type', 'type' => 'text', 'default' => 'WordPress Website Development'],
                'services' => ['label' => 'Other services', 'type' => 'text'],
                'link_label' => ['label' => 'Website link text', 'type' => 'text'],
                'link_url' => ['label' => 'Website address', 'type' => 'text'],
                'featured' => ['label' => 'Show on the home page', 'type' => 'bool', 'default' => '0', 'help' => 'Featured projects appear in the Featured projects section of the home page (up to 3).'],
            ],
        ],
    ],

    // Admin sidebar: section title => the public page it belongs to and its entries of [kind, key].
    'nav' => [
        'Whole site' => ['url' => '../', 'entries' => [['settings', 'general'], ['settings', 'layout'], ['collection', 'menu'], ['collection', 'social']]],
        'Home page' => ['url' => '../', 'entries' => [['settings', 'home'], ['collection', 'progress'], ['collection', 'skills'], ['collection', 'services'], ['collection', 'resume']]],
        'UI/UX page' => ['url' => '../ux.php', 'entries' => [['settings', 'ux'], ['collection', 'gallery']]],
        'Projects page' => ['url' => '../projects.php', 'entries' => [['settings', 'projects'], ['collection', 'projects']]],
    ],
];
